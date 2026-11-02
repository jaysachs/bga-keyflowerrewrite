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

class Model {
    /**
     * @param non-empty-list<int> $playerIds
     */
    public static function newGame(array $playerIds): void {
        $numPlayers = count($playerIds);

        $lastId = 0;

        // workers
        $bagWorkers = [];
        foreach ([WorkerType::RED, WorkerType::BLUE, WorkerType::YELLOW] as $wt) {
            for ($i = 0; $i < 40; $i++) {
                $bagWorkers[] = new Worker(++$lastId, $wt);
            }
        }
        shuffle($bagWorkers);

        $startPlayerWorker = new Worker(++$lastId, WorkerType::PURPLE);

        $greens = [];
        for ($i = 0; $i < 20; ++$i) {
            $greens[] = new Worker(++$lastId, WorkerType::GREEN);
        }

        // skills
        $lastId = 100;
        $skills = [];
        foreach (SkillType::cases() as $st) {
            for ($i = 0; $i < 20; ++$i) {
                $skills[] = new Skill(++$lastId, $st);
            }
        }

        // resources
        $lastId = 200;
        // FIXME: create a ResourceStock / ResourceGroup class?
        $resources = [];
        foreach (ResourceType::cases() as $rt) {
            $c = $rt == ResourceType::GOLD ? 48 : 24;
            $r = [];
            for ($i = 0; $i < $c; ++$i) {
                $r[] = new Resource(++$lastId, $rt);
            }
            $resources[$rt->value] = $r;
        }

        // tiles
        $lastId = 300;
        $springTypes = TileType::springTiles();
        shuffle($springTypes);
        array_splice($springTypes, $numPlayers + 4);
        $springTiles = [];
        foreach ($springTypes as $tt) {
            $springTiles[] = new Tile($lastId++, $tt);
        }

        $summerTypes = TileType::summerTiles();
        shuffle($summerTypes);
        array_splice($summerTypes, $numPlayers + 4);
        $summerTiles = [];
        foreach ($summerTypes as $tt) {
            $summerTiles[] = new Tile($lastId++, $tt);
        }

        $fallTypes = TileType::fallTiles();
        shuffle($fallTypes);
        array_splice($fallTypes, $numPlayers + 4);
        $fallTiles = [];
        foreach ($fallTypes as $tt) {
            $fallTiles[] = new Tile($lastId++, $tt);
        }

        $winterTypes = TileType::winterTiles();
        shuffle($winterTypes);
        $perPlayer = $numPlayers < 5 ? 3 : 2;
        array_splice($winterTypes, $numPlayers * $perPlayer);
        $winterTiles = [];
        foreach ($winterTypes as $tt) {
            $winterTiles[] = new Tile($lastId++, $tt);
        }

        $boatTiles = [];
        foreach (TileType::standardBoats($numPlayers) as $tt) {
            $boatTiles[] = new Tile(++$lastId, $tt);
        }

        $startTiles = [];
        foreach (TileType::startPlayerTiles($numPlayers) as $tt) {
            $startTiles[] = new Tile(++$lastId, $tt);
        }

        $homes = TileType::homeTiles();
        shuffle($homes);
        array_splice($homes, $numPlayers);
        $min = min($homes, TileType::HOME6);

        $villages = [];
        foreach ($playerIds as $playerId) {
            $workers = array_splice($bagWorkers, 0, 8);
            $wts = array_splice($winterTiles, 0, $perPlayer);
            $home = array_splice($homes, 0, 1)[0];
            if ($home == $min) {
                $workers[] = new Worker(++$lastId, WorkerType::PURPLE);
            }
            $villages[] = new Village(
                $playerId,
                [new Tile(++$lastId, $home)],
                $workers,
                [],
                $wts);
        }

        /*
         At this point, we have:
          $bagWorkers - bag of workers
          $greens - green worker stock
          $skills - pile of skills
          $villages - village for each player
             which includes initial workers, initial winter tiles (before selection)
                   and starting home tile
          $boatTiles - the available standard boats
          $startTiles - the avialable start tiles
          $springTiles
          $summerTiles
          $fallTiles   - the tiles to be used in the various seasons
        */
    }

    /**
     * note that activePlayerId may be the current active player in a
     * multiplayer active state.
     */
    public function __construct(public readonly int $activePlayerId)
    {
    }

    /**
     * @param list<int> $moved_bid_ids the tiles to move bids from
     */
    public function bid(int $market_tile_id, array $moved_bid_ids, int $numWorkersFromStock, WorkerType $workerType): void {
        /*
         verify:
            1. market_tile_id is in the market
            2. moved_bid_ids refer to tiles in the market
            3. moved_bid_ids are currently losing
            4. moved_bid_ids are unique
            5. if there already is a bid on this lot, by this player, add it to moved_bid_ids ??
            6. all moved_bid groups have the same type
            7. worker_type is same as all moved_bid groups
            8. if there are already activations or other player's bids on this tile, worker type should match
                unless player has boat4a
        */
        /*
        Assuming we pass verification:
            1. combine all moved bids into one bid group, removing them from the current lot
            2. from player's stock, move workers of appropriate type into the bid group
            3. set the bid group on the market lot
        */
            /*
            Update the DB.
            */
        }
}