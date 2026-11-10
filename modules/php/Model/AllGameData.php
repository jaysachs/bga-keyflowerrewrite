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

use Bga\Games\keyflowerrewrite\Utils\Arrays;
use InvalidArgumentException;

class AllGameData {
    public function __construct()
    {
        $this->greens = [];
        $this->bagWorkers = new Bag([]);
        $this->skills = new SkillPile([]);
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
    }

    /** @var list<Worker> */
    public array $greens;

    public Bag $bagWorkers;

    public SkillPile $skills;

    public ResourceStock $resources;

    public TilesStock $tilesStock;

    /** @var list<Village> */
    public array $villages;

    public Market $market;
    public Dock $dock;

    public ?Season $season;

    /** @var list<int> */
    public array $playerIds;

    /**
     * @param list<int> $playerIds
     */
    public static function newGameDataFor(array $playerIds): AllGameData {
        $numPlayers = count($playerIds);
        if ($numPlayers < 2 || $numPlayers > 6) {
            throw new InvalidArgumentException("player count must be between 2 and 6");
        }
        $lastId = 0;

        $result = new AllGameData();
        $result->playerIds = $playerIds;
        // workers
        $bagWorkers = [];
        foreach ([WorkerType::RED, WorkerType::BLUE, WorkerType::YELLOW] as $wt) {
            for ($i = 0; $i < 40; $i++) {
                $bagWorkers[] = new Worker(++$lastId, $wt);
            }
        }
        shuffle($bagWorkers);
        $result->bagWorkers = new Bag($bagWorkers);

        $result->greens = [];
        for ($i = 0; $i < 20; ++$i) {
            $result->greens[] = new Worker(++$lastId, WorkerType::GREEN);
        }

        // skills
        $result->skills = SkillPile::forNewGame();

        // resources
        $result->resources = ResourceStock::forNewGame();

        $result->tilesStock = TilesStock::forNewGame($numPlayers);

        $homes = TileType::homeTiles();
        shuffle($homes);
        array_splice($homes, $numPlayers);
        $min = TileType::HOME6;
        foreach ($homes as $home) {
            if ($home->value < $min->value) {
                $min = $home;
            }
        }

        $lastTileId = 500;
        $result->villages = [];
        $winterTiles = & $result->tilesStock->forSeason(Season::WINTER);
        $perPlayer = intval(count($winterTiles) / $numPlayers);
        foreach ($playerIds as $playerId) {
            $workers = $result->bagWorkers->take(8);
            $wts = array_splice($winterTiles, 0, $perPlayer);
            /** @var TileType */
            $home = array_shift($homes);
            if ($home == $min) {
                // start player
                // FIXME: id should be distinguished, or start player shouldn't even be a worker
                $workers[] = new Worker(++$lastId, WorkerType::PURPLE);
            }
            $result->villages[] = new Village(
                $playerId,
                [new VillageTile(++$lastTileId, $home, RowCol::fromRowCol(0, 0), 1, false, [], [])],
                $workers,
                [],
                $wts);
        }

        $result->dock = Dock::forNewGame($numPlayers);
        $result->market = new Market([]);

        $result->season = null;

        return $result;
    }

}