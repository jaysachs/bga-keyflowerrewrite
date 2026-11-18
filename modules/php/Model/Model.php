<?php

/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * zooloretto implementation : © Jay Sachs <vagabond@covariant.org>
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
    private function assembleValidatedWorkerGroup(array $moved_bid_ids, int $numWorkersFromStock, WorkerColor $workerColor): array {
        if (count(array_unique($moved_bid_ids)) != count($moved_bid_ids)) {
            throw new InvalidArgumentException("cannot move the same bid multiple times");
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
            if ($bid->color() != $workerColor) {
                throw new InvalidArgumentException("cannot mix colors in a bid");
            }
            // FIXME: remove from market tile? Or leave empty?
            $moved[] = new MovedBid(new WorkerGroup($bid->workers()), $movedBidTile);
        }
        if ($numWorkersFromStock <= 0 && count($moved) == 0) {
            throw new InvalidArgumentException("no workers bid from stock, no bids moved");
        }


        $stockWorkers = $this->village()->takeWorkersFromStock($workerColor, $numWorkersFromStock);
        if (count($stockWorkers) < $numWorkersFromStock) {
            throw new InvalidArgumentException("Insufficent workers from hand");
        }
        $group = new WorkerGroup($stockWorkers);
        foreach ($moved as $movedBid) {
            $group = $group->combineWith($movedBid->bid);
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

        $currentColor = $lot->biddingColor();
        if ($currentColor !== null && $workerColor != $currentColor) {
            if (!$hasBoat4a || $currentColor == WorkerColor::GREEN) {
                throw new InvalidArgumentException("bids must match bid color");
            }
        }

        if (count(array_unique($moved_bid_ids)) != count($moved_bid_ids)) {
            throw new InvalidArgumentException("cannot move the same bid multiple times");
        }

        // be tolerant of including target tile; it will get combined.
        $moved_bid_ids = array_filter($moved_bid_ids, fn ($id) => $id != $market_tile_id);

        $assembled = $this->assembleValidatedWorkerGroup($moved_bid_ids, $numWorkersFromStock, $workerColor);

        $newBid = $assembled["group"];
        $lot->bid($this->activePlayerId, $newBid);

        $this->ps->updateBidWorkerLocations($this->activePlayerId, $lot, $newBid);
        $this->ps->clearPasses();

        return new BidResult($newBid, $lot->tile_id, $assembled["movedBids"]);
    }

    /**
     * @param list<int> $moved_bid_ids the tiles to move bids from
     */
    public function activate(
        int $tile_id,
        array $moved_bid_ids,
        int $numWorkersFromStock,
        WorkerColor $workerColor,
        ?int $skill_pmt_id,
        ?int $worker_pmt_id,
        ?ResourceType $requested_reward_type): ActivationResult {
        $t = array_find($this->market()->lots(), fn (MarketLot $mt) => $mt->tile_id == $tile_id);
        if ($t === null) {
            foreach ($this->villages() as $village) {
                $t = array_find($village->tiles(), fn ($vt) => $vt->tile_id == $tile_id);
                if ($t) {
                    break;
                }
            }
            throw new InvalidArgumentException("no activatable tile {$tile_id}");
        }
        $rewardType = $t->type->activationReward();
        if ($rewardType == ActivationReward::NONE) {
            throw new InvalidArgumentException("tile {$tile_id} {$t->type->value} is not activatable");
        }
        $skill = null;
        if ($skill_pmt_id !== null) {
            $skill = array_find($this->village()->skills(), fn ($s) => $s->id == $skill_pmt_id);
            if (!$skill) {
                throw new InvalidArgumentException("No skill {$skill_pmt_id} found");
            }
        }
        $worker = null;
        if ($worker_pmt_id !== null) {
            $worker = array_find($this->village()->workers(), fn ($w) => $w->id == $worker_pmt_id);
            if (!$worker) {
                throw new InvalidArgumentException("No worker {$worker_pmt_id} found");
            }
        }
        if ($worker && $skill) {
            throw new InvalidArgumentException("both skill and worker provided");
        }
        switch ($t->type->activationCost()) {
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
        }
        if ($worker) {
            $this->allData()->workerStock->putInBag($worker);
            // TODO: remove from village
        } else if ($skill) {
            $this->allData()->skillPile->put($skill);
            // TODO: remove from village
        }

        // TODO: need to handle boat4b
        $assembled = $this->assembleValidatedWorkerGroup($moved_bid_ids, $numWorkersFromStock, $workerColor);

        $t->activationSet()->add($assembled["group"]);

        $rewards = $this->allData()->takeActivationRewards($rewardType, $requested_reward_type);

        $this->ps->updateActivatedWorkerLocations($this->activePlayerId, $t->tile_id, $t->activationSet()->lastActivationSize(), $assembled["group"]);
        $this->ps->updateRewards($rewards);
        $this->ps->clearPasses();

        /*
         verify:
            1. tile_id is a tile in some village or the market
            2. the tile has an activation reward
            3. moved_bid_ids refer to active player's bids
            4. moved_bid_ids are currently losing
            5. moved_bid_ids are unique
            6. all moved_bid groups have the same type
            7. worker_type is same as all moved_bid groups
            8. if there are already activations or bids on this tile, worker type should match
                unless player has boat4b
        */
        return new ActivationResult(false);
    }

    public function passTurn(): void {
        $this->ps->incPasses();
    }

    public function numPassed(): int {
        return $this->ps->numPasses();
    }
}