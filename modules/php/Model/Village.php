<?php

/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * Keyflower implementation : © Jay Sachs <vagabond@covariant.org>
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

class Village {
    /**
     * @param array<int,VillageTile> $tiles  key is encoded row/col
     * @param list<StockTile> $winterTiles
     * @param list<StockTile> $wonTiles
     */
    public function __construct(
        public readonly int $playerId,
        private array $tiles,
        private WorkerGroup $workers,
        public SkillSet $skills,
        private array $winterTiles,
        private array $wonTiles,
        private bool $hasStartPlayer) {}

    public function takeWorkersFromStock(WorkerColor $color, int $num): WorkerGroup {
        return $this->workers->takeNWorkersOfColor($num, $color);
    }

    /** @return array<int,VillageTile> */
    public function tiles(): array {
        return $this->tiles;
    }

    public function workers(): WorkerGroup {
        return $this->workers;
    }

    // FIXME: do we need these 3 forwarders now?

    public function workerById(int $id): ?Worker {
        return $this->workers->workerById($id);
    }
    public function addWorker(Worker $worker): void {
        $this->workers->add($worker);
    }

    public function addWorkerGroup(WorkerGroup $wg): void {
        $this->workers->addGroup($wg);
    }

    public function takeWorker(int $id): void {
        $this->workers->takeWorkerById($id);
    }

    public function addSkill(Skill $skill): void {
        $this->skills->add($skill);
    }

    public function takeSkill(int $id): void {
        $this->skills->takeSkillById($id);
    }

    /** @return list<StockTile> */
    public function winterTiles(): array {
        return $this->winterTiles;
    }

    /** @return list<StockTile> */
    public function wonTiles(): array {
        return $this->wonTiles;
    }

    public function addWonTile(StockTile $tile): void {
        $this->wonTiles[] = $tile;
    }

    public function placeTile(VillageTile $tile): void {
        // TODO: validate placement!
        $this->tiles[$tile->rowcol] = $tile;
    }

    public function setHasStartPlayer(bool $hasStart): void {
        $this->hasStartPlayer = $hasStart;
    }

    public function hasStartPlayer(): bool {
        return $this->hasStartPlayer;
    }

    public function tileById(int $tile_id): ?VillageTile {
        foreach ($this->tiles as $vtile) {
            if ($vtile->tile_id == $tile_id) {
                return $vtile;
            }
        }
        return null;
    }

    public function homeTile(): VillageTile {
        foreach ($this->tiles as $vtile) {
            if ($vtile->type->isHome()) {
                return $vtile;
            }
        }
        throw new InvalidArgumentException("no home tile on village for {$this->playerId}");
    }

    public function hasTileType(TileType $tileType): bool {
        foreach ($this->tiles as $vtile) {
            if ($vtile->type == $tileType) {
                return true;
            }
        }
        return false;
    }
}