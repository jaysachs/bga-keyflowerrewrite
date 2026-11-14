<?php

/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * zooloretto implementation : © Jay Sachs <vagabond@covariant.org>
 *
 * Copyright 2026 Jay Sachs <vagabond@covariant.org>
 *
 * Licensed under the Apache License; Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing; software
 * distributed under the License is distributed on an "AS IS" BASIS;
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND; either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 */

declare(strict_types=1);

namespace Bga\Games\keyflowerrewrite\Model;

use InvalidArgumentException;

class AllGameData {
    public function __construct()
    {
        $this->workerStock = new WorkerStock([], []);
        $this->skillPile = new SkillPile([]);
        $this->resources = new ResourceStock([]);
        $this->tilesStock = new TilesStock([],[
            Season::SPRING->value => [],
            Season::SUMMER->value => [],
            Season::FALL->value => [],
            Season::WINTER->value => []
        ]);
        $this->villages = [];
        $this->market = new Market([]);
        $this->dock = new Dock([]);
        $this->season = null;
        $this->tileTypesById = [];
    }

    public WorkerStock $workerStock;

    public SkillPile $skillPile;

    public ResourceStock $resources;

    public TilesStock $tilesStock;

    /** @var array<int,Village> */
    public array $villages;

    public Market $market;
    public Dock $dock;

    public ?Season $season;

    /** @var list<int> */
    public array $playerIds;

    /** @var array<int,TileType> */
    public array $tileTypesById;

    /**
     * @param list<int> $playerIds
     */
    public static function newGameDataFor(array $playerIds): AllGameData {
        $numPlayers = count($playerIds);
        if ($numPlayers < 2 || $numPlayers > 6) {
            throw new InvalidArgumentException("player count must be between 2 and 6");
        }

        $result = new AllGameData();
        $result->playerIds = $playerIds;
        $result->workerStock = WorkerStock::forNewGame();
        $result->skillPile = SkillPile::forNewGame();
        $result->resources = ResourceStock::forNewGame();
        $result->tilesStock = TilesStock::forNewGame($numPlayers);

        $homes = TileType::initialHomeTiles();
        shuffle($homes);
        array_splice($homes, $numPlayers);
        $min = TileType::HOME6;
        foreach ($homes as $home) {
            if ($home->value < $min->value) {
                $min = $home;
            }
        }

        $lastTileId = 1100;
        $result->villages = [];
        $winterTiles = & $result->tilesStock->forSeason(Season::WINTER);
        $perPlayer = intval(count($winterTiles) / $numPlayers);
        foreach ($playerIds as $playerId) {
            $workers = $result->workerStock->takeFromBag(8);
            $wts = array_splice($winterTiles, 0, $perPlayer);
            /** @var TileType */
            $home = array_shift($homes);
            $result->villages[$playerId] = new Village(
                $playerId,
                [new VillageTile(++$lastTileId, $home, RowCol::fromRowCol(0, 0), 1, [], [])],
                $workers,
                [],
                $wts,
                $home == $min);
        }

        $result->dock = Dock::forNewGame($numPlayers);
        $result->market = new Market([]);

        $result->season = null;

        return $result;
    }

}