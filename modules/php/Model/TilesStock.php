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

// FIXME: should this data just be in the Market object?
//  These are never physically seen in the adaptation ...
class TilesStock {

    /**
     * @param list<StockTile> $startTiles
     * @param array<string,list<StockTile>> $seasonTiles
     */
    public function __construct(private array $startTiles, private array $seasonTiles) {}

    /** @return list<StockTile> */
    public function & forSeason(Season $season): array {
        return $this->seasonTiles[$season->value];
    }

    /** @return list<StockTile> */
    public function & startTurnTiles(): array {
        return $this->startTiles;
    }

    /** @return list<StockTile> */
    public function takeTilesFor(Season $season): array {
        $result = $this->startTiles;
        array_push($result, ... $this->seasonTiles[$season->value]);
        $this->startTiles = [];
        $this->seasonTiles[$season->value] = [];
        return $result;
    }

    public static function forNewGame(int $numPlayers): TilesStock {
        $lastId = 1000;
        $stock = new TilesStock([], []);

        $springTypes = TileType::initialSpringTiles();
        shuffle($springTypes);
        array_splice($springTypes, $numPlayers + 4);
        $stock->seasonTiles[Season::SPRING->value] = [];
        foreach ($springTypes as $tt) {
            $stock->seasonTiles[Season::SPRING->value][] = new StockTile($lastId++, $tt);
        }

        $summerTypes = TileType::initialSummerTiles();
        shuffle($summerTypes);
        array_splice($summerTypes, $numPlayers + 4);
        $stock->seasonTiles[Season::SUMMER->value] = [];
        foreach ($summerTypes as $tt) {
            if ($tt->isBoat()) {
                if (random_int(0, 1)) {
                    $tt = $tt->flip();
                    if ($tt === null) {
                        throw new InvalidArgumentException("summer boat should be flippable!");
                    }
                }
            }
            $stock->seasonTiles[Season::SUMMER->value][] = new StockTile($lastId++, $tt);
        }

        $fallTypes = TileType::initialFallTiles();
        shuffle($fallTypes);
        array_splice($fallTypes, $numPlayers + 4);
        $stock->seasonTiles[Season::FALL->value] = [];
        foreach ($fallTypes as $tt) {
            $stock->seasonTiles[Season::FALL->value][] = new StockTile($lastId++, $tt);
        }

        $winterTypes = TileType::initialWinterTiles();
        shuffle($winterTypes);
        $perPlayer = $numPlayers < 5 ? 3 : 2;
        array_splice($winterTypes, $numPlayers * $perPlayer);
        $stock->seasonTiles[Season::WINTER->value] = [];
        foreach ($winterTypes as $tt) {
            $stock->seasonTiles[Season::WINTER->value][] = new StockTile($lastId++, $tt);
        }

        $stock->startTiles = [];
        foreach (TileType::startPlayerTiles($numPlayers) as $tt) {
            $stock->startTiles[] = new StockTile(++$lastId, $tt);
        }

        return $stock;
    }
}