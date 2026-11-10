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

class MarketTile {
    /**
     * @param list<WorkerGroup> $activations
     * @param array<int,WorkerGroup> $bids key is playerId
     */
    public function __construct(
        public readonly int $id,
        public TileType $type,
        public array $activations,
        public array $bids = []) {
    }

    public static function fromStock(StockTile $st): MarketTile {
        return new MarketTile($st->id, $st->type, [], []);
    }

    public function bid(int $playerId, WorkerGroup $bid): void {
        if (isset($this->bids[$playerId])) {
            $this->bids[$playerId]->combineWith($bid);
        } else {
            $this->bids[$playerId] = $bid;
        }
        if ($this->bids[$playerId] != $this->highBid()) {
            throw new InvalidArgumentException("new bid must be high bid");
        }
    }

    public function bidBy(int $playerId): ?WorkerGroup {
        if (isset($this->bids[$playerId])) {
            return $this->bids[$playerId];
        }
        return null;
    }

    public function biddingColor(): ?WorkerType {
        // color of lowest bid is the bid color
        $lowest = null;
        foreach ($this->bids as $playerId => $bid) {
            if ($lowest === null || $bid->size() < $lowest->size()) {
                $lowest = $bid;
            }
        }
        return $lowest === null ? null : $lowest->color();
    }

    public function highBid(): ?WorkerGroup {
        /** @var WorkerGroup|null */
        $winner = null;
        foreach ($this->bids as $playerId => $bid) {
            if ($winner === null || $bid->size() > $winner->size()) {
                $winner = $bid;
            }
        }
        return $winner;
    }
}
