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

class Market {
    /**
     * @param array<int,MarketTile> $tiles key is position (1+)
     */
    public function __construct(private array $tiles) {}

    public function marketTileById(int $tileId): ?MarketTile {
        foreach ($this->tiles as $tile) {
            if ($tile->id == $tileId) {
                return $tile;
            }
        }
        return null;
    }

    public function replenish(Season $season, TilesStock $stock): void {
        // FIXME: should position be 1-based
        array_push($this->tiles, ... array_map(fn ($t) => MarketTile::fromStock($t), $stock->takeTilesFor($season)));
    }

    /** @return array<int,MarketTile>  keyed by position */
    public function tiles(): array {
        return $this->tiles;
    }
}
