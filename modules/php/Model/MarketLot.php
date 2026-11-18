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

class MarketLot {

    // private array $activations = [];
    /** @param array<int,WorkerGroup> $bids key is playerId */
    public function __construct(
        public readonly int $tile_id,
        public TileType $type,
        private array $bids = [],
        private ProductionActivations $productionActivations = new ProductionActivations()) {
        // validate we have no bids, or a single high bid.
        $this->highBid();
    }

    public static function fromStock(StockTile $st): MarketLot {
        return new MarketLot($st->tile_id, $st->type);
    }

    public function bid(int $playerId, WorkerGroup $bid): void {
        if ($bid->size() == 0) {
            throw new InvalidArgumentException("bid must be non-empty");
        }
        if (isset($this->bids[$playerId])) {
            $this->bids[$playerId] = $this->bids[$playerId]->combineWith($bid);
        } else {
            $this->bids[$playerId] = $bid;
        }
        if ($this->bids[$playerId] != $this->highBid()) {
            throw new InvalidArgumentException("new bid must be high bid");
        }
    }

    /** @return array<int, WorkerGroup>  key is playerId */
    public function bids(): array {
        return $this->bids;
    }

    public function bidBy(int $playerId): ?WorkerGroup {
        if (isset($this->bids[$playerId])) {
            return $this->bids[$playerId];
        }
        return null;
    }

    public function biddingColor(): ?WorkerColor {
        // TODO: take into account any activations.
        // color of lowest bid is the bid color
        $lowest = null;
        foreach ($this->bids as $playerId => $bid) {
            if ($lowest === null || $bid->size() < $lowest->size()) {
                $lowest = $bid;
            }
        }
        return $lowest === null ? null : $lowest->color();
    }

    /** Returns true if the player has a non-empty bid that is not the high bid */
    public function hasNonWinningBid(int $playerId): bool {
        $bid = $this->bidBy($playerId);
        if ($bid === null || count($bid->workers()) == 0) {
            // never bid, or already moved a losing bid
            return false;
        }
        return $this->highBid() !== $bid;
    }

    public function highBid(): WorkerGroup {
        /** @var WorkerGroup|null */
        $winner = null;
        $winners = 0;
        foreach ($this->bids as $playerId => $bid) {
            if ($winner === null || $bid->size() > $winner->size()) {
                $winner = $bid;
                $winners = 1;
            } else if ($bid->size() == $winner->size()) {
                $winners++;
            }
        }
        if ($winners > 1) {
            $sz = $winner->size();
            throw new InvalidArgumentException("multiple high bids: {$sz}");
        }
        // return $winner;
        return $winner === null ? new WorkerGroup([]) : $winner;
    }

    public function productionActivations(): ProductionActivations { return $this->productionActivations; }
}
