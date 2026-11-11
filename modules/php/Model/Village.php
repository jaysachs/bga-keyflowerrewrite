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

class Village {
    /**
     * @param list<VillageTile> $tiles  key is encoded row/col
     * @param list<Worker> $workers FIXME: for now this includes start player
     * @param list<Skill> $skills
     * @param list<StockTile> $winterTiles
     */
    public function __construct(public readonly int $playerId, public array $tiles, public array $workers, public array $skills, public array $winterTiles, public bool $hasStartPlayer) {}

    /** @return WorkerGroup */
    public function takeWorkersFromStock(WorkerType $type, int $num): WorkerGroup {
        $result = [];
        $workers = $this->workers;
        for ($i = 0; $i < $num; ++$i) {
            $idx = array_find_key($workers, fn ($w) => $w->type == $type);
            if ($idx !== null) {
                $result[] = array_slice($workers, $idx, 1)[0];
            }
        }
        if (count($result) != $num) {
            throw new InvalidArgumentException("Not enough workers of type {$type->value} in stock");
        }
        return new WorkerGroup($this->playerId, $workers);
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