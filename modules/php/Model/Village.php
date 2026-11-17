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


class Village {
    /**
     * @param array<int,VillageTile> $tiles  key is encoded row/col
     * @param list<Worker> $workers
     * @param list<Skill> $skills
     * @param list<StockTile> $winterTiles
     * @param list<StockTile> $wonTiles
     */
    public function __construct(
        public readonly int $playerId,
        private array $tiles,
        private array $workers,
        private array $skills,
        private array $winterTiles,
        private array $wonTiles,
        private bool $hasStartPlayer) {}

    /** @return list<Worker> */
    public function takeWorkersFromStock(WorkerColor $color, int $num): array {
        $result = [];
        $workers = $this->workers;
        for ($i = 0; $i < $num; ++$i) {
            $idx = array_find_key($workers, fn ($w) => $w->color == $color);
            if ($idx !== null) {
                $result[] = array_splice($workers, $idx, 1)[0];
            }
        }
        return $result;
    }

    /** @return array<int,VillageTile> */
    public function tiles(): array {
        return $this->tiles;
    }

    /** @return list<Worker> */
    public function workers(): array {
        return $this->workers;
    }

    public function addWorker(Worker $worker): void {
        $this->workers[] = $worker;
    }

    /** @return list<Skill> */
    public function skills(): array {
        return $this->skills;
    }

    public function addSkill(Skill $skill): void {
        $this->skills[] = $skill;
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

    public function hasTileType(TileType $tileType): bool {
        foreach ($this->tiles as $vtile) {
            if ($vtile->type == $tileType) {
                return true;
            }
        }
        return false;
    }
}