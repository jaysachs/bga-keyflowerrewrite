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

    static string $foo = "";
    /** @return array{startPlayerId:int,season:Season} */
    public function nextSeason(): array {

        $curr = $this->allData()->season;
        self::$foo = self::$foo . "," . ($curr === null ? "null" : $curr->value);
        $foo = self::$foo;
        $season = match ($curr) {
            null => Season::SPRING,
            Season::SPRING => Season::SUMMER,
            Season::SUMMER => Season::FALL,
            Season::FALL => Season::WINTER,
            default => throw new UnexpectedValueException("no next season after {$foo} {$curr->value}")
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
            // throw new InvalidArgumentException("no players have start player?!");
            $startPlayerId = $this->allData()->playerIds[0];
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

    private function village(): Village {
        return $this->villageFor($this->activePlayerId);
    }

    /**
     * @param list<int> $moved_bid_ids the tiles to move bids from
     */
    public function bid(int $market_tile_id, array $moved_bid_ids, int $numWorkersFromStock, WorkerColor $workerColor): BidResult {

        $hasBoat4a = $this->village()->hasTileType(TileType::BOAT4A);
        $market = $this->market();

        $bidTile = $market->marketTileById($market_tile_id);
        if ($bidTile === null) {
            throw new InvalidArgumentException("no market tile {$market_tile_id}");
        }

        $currentColor = $bidTile->biddingColor();
        if ($currentColor !== null && $workerColor != $currentColor && !$hasBoat4a) {
            throw new InvalidArgumentException("bids must match bid color");
        }

        if (count(array_unique($moved_bid_ids)) != count($moved_bid_ids)) {
            throw new InvalidArgumentException("cannot move the same bid multiple times");
        }

        /** @var list<WorkerGroup> */
        $movedBids = [];
        /** @var array<int,WorkerGroup> */
        $moved = [];
        foreach ($moved_bid_ids as $movedBidTileId) {
            $movedBidTile = $market->marketTileById($movedBidTileId);
            if ($movedBidTile === null) {
                throw new InvalidArgumentException("no market tile {$movedBidTileId}");
            }
            if ($movedBidTile == $bidTile) {
                // TODO: figure out if this should be ok; if it is allowed, the
                //       subsequent validations will need adjustment.
                throw new InvalidArgumentException("cannot move bid onto itself");
            }
            $bid = $movedBidTile->bidBy($this->activePlayerId);
            // FIXME: remove the bid, or accept empty worker groups.
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
            $moved[$movedBidTileId] = new WorkerGroup($bid->workers());
            $movedBids[] = $bid;
        }
        if ($numWorkersFromStock <= 0 && count($movedBids) == 0) {
            throw new InvalidArgumentException("no workers bid from stock, no bids moved");
        }


        // FIXME: handle 0 workers from stock.
        // TODO: allow empty worker groups?
        $newBid = ($numWorkersFromStock > 0)
            ? $this->village()->takeWorkersFromStock($workerColor, $numWorkersFromStock)
            : array_shift($movedBids);

        foreach ($movedBids as $movedBid) {
            $newBid->combineWith($movedBid);
        }

        $bidTile->bid($this->activePlayerId, $newBid);

        /* Update the DB */
        $this->ps->updateBidWorkerLocations($this->activePlayerId, $bidTile, $newBid);
        $this->ps->clearPasses();

        return new BidResult($newBid, $bidTile->id, $moved);
    }

    public function passTurn(): void {
        $this->ps->incPasses();
    }

    public function numPassed(): int {
        return $this->ps->numPasses();
    }
}