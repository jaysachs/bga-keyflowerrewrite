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

class TilesStock {

    /** @var list<StockTile>*/
    private array $startTiles = [];
    /** @var array<string,list<StockTile>>  */
    private array $seasonTiles;

    public function __construct() {
        $this->seasonTiles = array_fill_keys(array_map(fn ($s) => $s->value, Season::cases()), []);
    }

    public function addStartTile(StockTile $tile): void {
        if (!$tile->type->isTurnOrder()) {
            throw new InvalidArgumentException("can't add non-start tile to start tiles");
        }
        $this->startTiles[] = $tile;
        $this->validate();
    }

    public function addSeasonTile(Season $season, StockTile $tile): void {
        $ts = $tile->type->season();
        if ($ts === null) {
            throw new InvalidArgumentException("attempt to add non-season tile to TilesStock {$tile->type->value}");
        }
        if ($ts != $season) {
            throw new InvalidArgumentException("attempt to add {$tile->type->value} tile to TilesStock {$season->value}");
        }
        $this->seasonTiles[$season->value][] = $tile;
        $this->validate();
    }

    private function validate(): void {
        $ids = array_keys($this->startTiles);
        if (count($ids) != count(array_unique($ids))) {
            throw new InvalidArgumentException("non-unique start tiles");
        }
        $ids = [];
        foreach (Season::cases() as $season) {
            foreach ($this->forSeason($season) as $st) {
                if ($st->type->season() != $season) {
                    throw new InvalidArgumentException("wrong season for tile {$st->type->value}, expected {$season->value} ");
                }
                array_push($ids, $st->tile_id);
            }
        }
        if (count($ids) != count(array_unique($ids))) {
            throw new InvalidArgumentException("non-unique tiles");
        }
    }

    /** @return list<StockTile> */
    public function forSeason(Season $season): array {
        return $this->seasonTiles[$season->value];
    }

    /** @return list<StockTile> */
    public function startTurnTiles(): array {
        return $this->startTiles;
    }

    /** @return list<StockTile> */
    public function takeTilesFor(Season $season): array {
        $result = [];
        // FIXME: does this logic belong here?
        if ($season == Season::SPRING) {
            array_push($result, ... $this->startTiles);
            $this->startTiles = [];
        }
        array_push($result, ... $this->seasonTiles[$season->value]);
        $this->seasonTiles[$season->value] = [];
        return $result;
    }

    public static function forNewGame(int $numPlayers): TilesStock {
        $lastId = 1000;
        $stock = new TilesStock();

        $springTypes = TileType::initialSeasonTiles(Season::SPRING);
        shuffle($springTypes);
        array_splice($springTypes, $numPlayers + 4);
        foreach ($springTypes as $tt) {
            $stock->addSeasonTile(Season::SPRING, new StockTile($lastId++, $tt));
        }

        $summerTypes = TileType::initialSeasonTiles(Season::SUMMER);
        shuffle($summerTypes);
        array_splice($summerTypes, $numPlayers + 4);
        foreach ($summerTypes as $tt) {
            if ($tt->isBoat()) {
                if (random_int(0, 1)) {
                    $tt = $tt->flip();
                    if ($tt === null) {
                        throw new InvalidArgumentException("summer boat should be flippable!");
                    }
                }
            }
            $stock->addSeasonTile(Season::SUMMER, new StockTile($lastId++, $tt));
        }

        $fallTypes = TileType::initialSeasonTiles(Season::FALL);
        shuffle($fallTypes);
        array_splice($fallTypes, $numPlayers + 4);
        foreach ($fallTypes as $tt) {
            $stock->addSeasonTile(Season::FALL, new StockTile($lastId++, $tt));
        }

        $winterTypes = TileType::initialSeasonTiles(Season::WINTER);
        shuffle($winterTypes);
        $perPlayer = $numPlayers < 5 ? 3 : 2;
        array_splice($winterTypes, $numPlayers * $perPlayer);
        foreach ($winterTypes as $tt) {
            $stock->addSeasonTile(Season::WINTER, new StockTile($lastId++, $tt));
        }

        foreach (TileType::turnOrderTiles($numPlayers) as $tt) {
            $stock->addStartTile(new StockTile($lastId++, $tt));
        }

        return $stock;
    }
}