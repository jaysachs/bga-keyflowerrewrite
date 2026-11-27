<?php

/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * Keyflower implementation : © Jay Sachs <vagabond@covariant.org>
 *
 * Copyright 2026 Jay Sachs <vagabond@covariant.org>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 */

declare(strict_types=1);

namespace Bga\Games\keyflowerrewrite\Model;

use Bga\Games\keyflowerrewrite\Utils\Log;
use InvalidArgumentException;
use UnexpectedValueException;

class Model {

    /**
     * note that activePlayerId may be the current active player in a
     * multiplayer active state.
     */
    public function __construct(private readonly int $activePlayerId, private PersistentStore $ps) {}

    private ?AllGameData $_allData = null;

    public function allData(): AllGameData {
        if ($this->_allData === null) {
            $this->_allData = $this->ps->readAllData();
        }
        return $this->_allData;
    }

    /** @return array{startPlayerId:int,season:Season} */
    public function nextSeason(): array {

        $curr = $this->allData()->season;
        $season = match ($curr) {
            null => Season::SPRING,
            Season::SPRING => Season::SUMMER,
            Season::SUMMER => Season::FALL,
            Season::FALL => Season::WINTER,
            default => throw new UnexpectedValueException("no next season after {$curr->value}")
        };
        $this->allData()->season = $season;

        $this->dock()->flipForSeason($season);

        // shuffle bag workers?

        $this->dock()->replenish($this->allData()->workerStock, $this->allData()->skillPile);

        $this->market()->replenish($season, $this->allData()->tilesStock);

        $startPlayerId = 0;
        foreach ($this->allData()->villages as $village) {
            if ($village->hasStartPlayer()) {
                if ($startPlayerId <> 0) {
                    throw new InvalidArgumentException("two players have start player?!");
                }
                $startPlayerId = $village->playerId;
            }
        }
        if ($startPlayerId == 0) {
            throw new InvalidArgumentException("no players have start player?!");
        }

        $this->ps->saveAllData($this->allData());

        return [
            'season' => $season,
            'startPlayerId' => $startPlayerId
        ];
    }

    public function season(): ?Season {
        return $this->allData()->season;
    }

    public function dock(): Dock {
        return $this->allData()->dock;
    }

    public function market(): Market {
        return $this->allData()->market;
    }

    /** @return array<int,Village> */
    private function & villages(): array {
        return $this->allData()->villages;
    }

    private function villageFor(int $playerId): Village {
        $villages = & $this->villages();
        return $villages[$playerId];
    }

    public function village(): Village {
        return $this->villageFor($this->activePlayerId);
    }

    /**
     * @param array<int,int> $moved_bid_ids
     * @return array{group:WorkerGroup,movedBids:list<MovedBid>}
     */
    private function assembleValidatedWorkerGroup(array $moved_bid_ids, int $numBlue, int $numRed, int $numYellow, int $numGreen): array {
        if (count(array_unique($moved_bid_ids)) != count($moved_bid_ids)) {
            throw new InvalidArgumentException("cannot move the same bid multiple times");
        }
        $numWorkersFromStock = $numBlue + $numRed + $numYellow + $numGreen;
        $workerColor = null;
        if ($numWorkersFromStock == max($numBlue, $numRed, $numYellow, $numGreen)) {
            if ($numBlue > 0) { $workerColor = WorkerColor::BLUE; }
            else if ($numRed > 0) { $workerColor = WorkerColor::RED; }
            else if ($numYellow > 0) { $workerColor = WorkerColor::YELLOW; }
            else if ($numGreen > 0) { $workerColor = WorkerColor::GREEN; }
        }
        /** @var list<MovedBid> */
        $moved = [];
        foreach ($moved_bid_ids as $movedBidTileId) {
            $movedBidTile = $this->market()->marketLotById($movedBidTileId);
            $bid = $movedBidTile->bidBy($this->activePlayerId);
            if ($bid === null) {
                throw new InvalidArgumentException("no bid to move on market tile {$movedBidTileId}");
            }
            if ($bid === $movedBidTile->highBid()) {
                throw new InvalidArgumentException("cannot move a high bid {$movedBidTileId}");
            }
            if ($workerColor === null) {
                $workerColor = $bid->color();
            }
            else if ($bid->color() != $workerColor) {
                throw new InvalidArgumentException("cannot mix colors in a bid");
            }
            // FIXME: remove from market tile? Or leave empty?
            $moved[] = new MovedBid($bid, $movedBidTile);
        }
        if ($numWorkersFromStock <= 0 && count($moved) == 0) {
            throw new InvalidArgumentException("no workers bid from stock, no bids moved");
        }

        $group = new WorkerGroup();
        $group->addGroup($this->village()->takeWorkersFromStock(WorkerColor::BLUE, $numBlue));
        $group->addGroup($this->village()->takeWorkersFromStock(WorkerColor::RED, $numRed));
        $group->addGroup($this->village()->takeWorkersFromStock(WorkerColor::YELLOW, $numYellow));
        $group->addGroup($this->village()->takeWorkersFromStock(WorkerColor::GREEN, $numGreen));
        if ($group->size() < $numWorkersFromStock) {
            throw new InvalidArgumentException("Insufficent workers from hand");
        }
        foreach ($moved as $movedBid) {
            $group->addGroup($movedBid->bid);
        }

        return [
            "group" => $group,
            "movedBids" => $moved,
        ];

    }


    /**
     * @param list<int> $moved_bid_ids the tiles to move bids from
     */
    public function bid(int $market_tile_id, array $moved_bid_ids, int $numWorkersFromStock, WorkerColor $workerColor): BidResult {

        $hasBoat4a = $this->village()->hasTileType(TileType::BOAT4A);
        $market = $this->market();

        $lot = $market->marketLotById($market_tile_id);

        $currentColor = $lot->currentColor();
        if ($currentColor->isConcrete() && $workerColor != $currentColor) {
            if (!$hasBoat4a || $currentColor == WorkerColor::GREEN) {
                throw new InvalidArgumentException("bids must match bid color");
            }
        }

        if (count(array_unique($moved_bid_ids)) != count($moved_bid_ids)) {
            throw new InvalidArgumentException("cannot move the same bid multiple times");
        }

        // be tolerant of including target tile; it will get combined.
        $moved_bid_ids = array_filter($moved_bid_ids, fn ($id) => $id != $market_tile_id);

        $numBlue = $workerColor == WorkerColor::BLUE ? $numWorkersFromStock : 0;
        $numRed = $workerColor == WorkerColor::RED ? $numWorkersFromStock : 0;
        $numYellow = $workerColor == WorkerColor::YELLOW ? $numWorkersFromStock : 0;
        $numGreen = $workerColor == WorkerColor::GREEN ? $numWorkersFromStock : 0;
        $assembled = $this->assembleValidatedWorkerGroup($moved_bid_ids, $numBlue, $numRed, $numYellow, $numGreen);

        $newBid = $assembled["group"];
        $lot->bid($this->activePlayerId, $newBid);

        $this->ps->updateBidWorkerLocations($this->activePlayerId, $lot, $newBid);
        $this->ps->clearPasses();

        return new BidResult($newBid, $lot->tile_id, $assembled["movedBids"]);
    }

    /**
     * @param MarketLot|VillageTile $tile
     */
    private function computeActivationCost(mixed $tile, ?int $skill_pmt_id, ?int $worker_pmt_id): ActivationPayment {
        $skill = null;
        if ($skill_pmt_id !== null) {
            $skill = $this->village()->skills->skillById($skill_pmt_id);
            if (!$skill) {
                throw new InvalidArgumentException("No skill {$skill_pmt_id} found");
            }
        }
        $worker = null;
        if ($worker_pmt_id !== null) {
            $worker = $this->village()->workerById($worker_pmt_id);
            if (!$worker) {
                throw new InvalidArgumentException("No worker {$worker_pmt_id} found");
            }
        }
        if ($worker && $skill) {
            throw new InvalidArgumentException("both skill and worker provided");
        }
        switch ($tile->type->activationCost()) {
            case ActivationCost::SKILL_ANY:
                if (! $skill) {
                    throw new InvalidArgumentException("skill required to active, non provided");
                }
                break;
            case ActivationCost::SKILL_ANVIL:
                if (! $skill) {
                    throw new InvalidArgumentException("anvil skill required to active, non provided");
                }
                if ($skill->type != SkillType::ANVIL) {
                    throw new InvalidArgumentException("anvil skill required to active, but {$skill->type->value} provided");
                }
                break;
            case ActivationCost::SKILL_PICKAXE:
                if (! $skill) {
                    throw new InvalidArgumentException("pickaxe skill required to active, non provided");
                }
                if ($skill->type != SkillType::PICKAXE) {
                    throw new InvalidArgumentException("pickaxe skill required to active, but {$skill->type->value} provided");
                }
                break;
            case ActivationCost::SKILL_SAW:
                if (! $skill) {
                    throw new InvalidArgumentException("saw skill required to active, non provided");
                }
                if ($skill->type != SkillType::SAW) {
                    throw new InvalidArgumentException("saw skill required to active, but {$skill->type->value} provided");
                }
                break;
            case ActivationCost::WORKER_ANY:
                if (!$worker) {
                    throw new InvalidArgumentException("worker required for activation, none provided");
                }
                break;
            case ActivationCost::WORKER_BLUE:
                if (!$worker) {
                    throw new InvalidArgumentException("blue worker required for activation, none provided");
                }
                if ($worker->color != WorkerColor::BLUE) {
                    throw new InvalidArgumentException("blue worker required for activation, {$worker->color->value} provided");
                }
                break;
            case ActivationCost::WORKER_RED:
                if (!$worker) {
                    throw new InvalidArgumentException("red worker required for activation, none provided");
                }
                if ($worker->color != WorkerColor::RED) {
                    throw new InvalidArgumentException("red worker required for activation, {$worker->color->value} provided");
                }
                break;
            case ActivationCost::WORKER_YELLOW:
                if (!$worker) {
                    throw new InvalidArgumentException("yellow worker required for activation, none provided");
                }
                if ($worker->color != WorkerColor::YELLOW) {
                    throw new InvalidArgumentException("yellow worker required for activation, {$worker->color->value} provided");
                }
                break;
            default:
                if ($worker || $skill) {
                    throw new InvalidArgumentException("no skill or worker required for activation but one provided");
                }
        }
        return new ActivationPayment($worker, $skill);
    }

    /**
     * @param list<int> $moved_bid_ids the tiles to move bids from
     */
    public function activate(
        int $tile_id,
        array $moved_bid_ids,
        int $numBlue,
        int $numRed,
        int $numYellow,
        int $numGreen,
        ?int $skill_pmt_id = null,
        ?int $worker_pmt_id = null,
        ?ResourceType $requested_reward_type = null): ActivationResult {

        $hasBoat4b = $this->village()->hasTileType(TileType::BOAT4B);

        /** @var MarketLot|VillageTile|null */
        $t = array_find($this->market()->lots(), fn (MarketLot $mt) => $mt->tile_id == $tile_id);
        if ($t === null) {
            foreach ($this->villages() as $village) {
                $t = array_find($village->tiles(), fn ($vt) => $vt->tile_id == $tile_id);
                if ($t) {
                    break;
                }
            }
            if ($t === null) {
                throw new InvalidArgumentException("no activatable tile {$tile_id}");
            }
        }
        $rewardType = $t->type->activationReward();
        if ($rewardType == ActivationReward::NONE) {
            throw new InvalidArgumentException("tile {$t->tile_id} {$t->type->value} is not activatable");
        }

        $activationInfo = $this->assembleValidatedWorkerGroup($moved_bid_ids, $numBlue, $numRed, $numYellow, $numGreen);
        $activatingWorkerGroup = $activationInfo["group"];

        $currentColor = $t->currentColor();
        if ($currentColor->isConcrete()) {
            if ($activatingWorkerGroup->color() !== $currentColor && !$hasBoat4b) {
                throw new InvalidArgumentException("activations must match tile color {$currentColor->value} != {$activatingWorkerGroup->color()->value}");
            }
        } else {
            if (!$activatingWorkerGroup->color()->isConcrete()) {
                throw new InvalidArgumentException("activations on empty tile must have a single color");
            }
        }

        $payment = $this->computeActivationCost($t, $skill_pmt_id, $worker_pmt_id);
        $this->allData()->applyActivationPayment($this->activePlayerId, $payment);

        $t->productionActivations()->add($activatingWorkerGroup);

        $result = $this->allData()->takeActivationRewards($rewardType, $requested_reward_type);

        // TODO: maybe want $destTile as part of ProductionResult; move takeActivationRewards back onto Model?
        // TODO: consider that AllGameData is not that different from Model ... except that it doesnt' persist.
        //       that's an interesting avenue to explore.
        $destTile = null;
        if (!$result->resources->isEmpty()) {
            if ($t instanceof VillageTile) {
                if ($t->playerId == $this->activePlayerId) {
                    $destTile = $t;
                } else {
                    $destTile = $this->village()->homeTile();
                }
            } else {
                $destTile = $this->village()->homeTile();
            }
            foreach ($result->resources->allItems() as $resource) {
                $destTile->addResource($resource);
            }
        } else if (!$result->skills->isEmpty()) {
            $this->village()->skills->addSet($result->skills);
        } else if (!$result->workers->isEmpty()) {
            $this->village()->addWorkerGroup($result->workers);
        }

        $this->ps->updateActivation(
            $this->activePlayerId,
            $t->tile_id,
            count($t->productionActivations()->activations()) - 1,
            $activatingWorkerGroup,
            $result,
            $payment,
            $destTile);
        $this->ps->clearPasses();

        return new ActivationResult($t, $result, $activatingWorkerGroup, $activationInfo["movedBids"], $destTile);
    }


        /** @return array<int,list<int>> lot_id x player_ids */
    private function returnLosingBids(): array {
        /** @var array<int,list<int>> lot_id x player_ids */
        $result = [];
        foreach ($this->market()->lots() as $lot) {
            $pids = [];
            $losingBids = $lot->removeLosingBids();
            foreach ($losingBids as $pid => $bid) {
                $pids[] = $pid;
                $this->allData()->villages[$pid]->addWorkerGroup($bid);
            }
            $result[$lot->tile_id] = $pids;
        }
        return $result;
    }

    /** @return list<MarketLot> discarded lot IDs */
    private function discardUnbidLots(): array {
        $lots = $this->market()->removeUnbidLots();
        foreach ($lots as $lot) {
            $pa = $lot->productionActivations();
            foreach ($pa->activations() as $activation) {
                $this->allData()->workerStock->putAllInBag($activation);
            }
        }
        return $lots;
    }

    /**
     * returns:
     *   awarded: array<int,int> lot_id x player_id of awarded tiles
     *      -- that's sufficient for client to return winning bids to bag
     *      -- and to know which player got each tile
     *      -- and to know which player to give all workers on the tile
     *  @return array<int,int>
     */
    private function awardWonTiles(): array {
        $result = [];
        foreach ($this->market()->lots() as $lot) {
            if ($lot->type->isTurnOrder()) {
                continue;
            }
            $winningPlayerId = $lot->winningPlayerId();
            $result[$lot->tile_id] = $winningPlayerId;
            $winningBid = $lot->highBid();
            $this->allData()->workerStock->putAllInBag($winningBid);
            $village = $this->allData()->villages[$winningPlayerId];
            $village->addWonTile(new StockTile($lot->tile_id, $lot->type));
            $pa = $lot->productionActivations();
            foreach ($pa->activations() as $activation) {
                $village->addWorkerGroup($activation->takeNWorkersOfColor(100, WorkerColor::MANY));
            }
            $lot->removeAllBids();
        }
        $this->market()->removeLots(array_keys($result));
        return $result;
    }

    /**
     * @return array<int,list<int>> playerId x village tile IDs with workers to reclaim
     */
    private function collectVillageWorkers(): array {
        $result = [];
        foreach ($this->allData()->villages as $pid => $village) {
            $result[$pid] = [];
            foreach ($village->tiles() as $tile) {
                $pa = $tile->productionActivations();
                foreach ($pa->activations() as $activation) {
                    $village->addWorkerGroup($activation->takeNWorkersOfColor(100, WorkerColor::MANY));
                }
                if (count($pa->activations()) > 0) {
                    $result[$pid][] = $tile->tile_id;
                }
            }
        }
        return $result;
    }


    /** @return array{losingBids:array<int,list<int>>,discardedLots:list<MarketLot>,awardedLots:array<int,int>,collectedWorkers:array<int,list<int>>} */
    public function endSeason(): array {
        $losingBids = $this->returnLosingBids();
        $discardedLots = $this->discardUnbidLots();
        $awardedLots = $this->awardWonTiles();
        $collectedWorkers = $this->collectVillageWorkers();
        // FIXME: optimize this!
        $this->ps->saveAllData($this->allData());
        return [
            "losingBids" => $losingBids,
            "discardedLots" => $discardedLots,
            "awardedLots" => $awardedLots,
            "collectedWorkers" => $collectedWorkers
        ];
    }

    public function passTurn(): void {
        $this->ps->incPasses();
    }

    public function numPassed(): int {
        return $this->ps->numPasses();
    }
}