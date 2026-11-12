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

class DockTile {
    /**
     * @param list<Worker> $passengers
     * @param list<Skill> $cargo
     */
    public function __construct(public readonly int $id, public TileType $type, public array $passengers = [], public array $cargo = []) {
        if (!$this->type->isStandardBoat()) {
            throw new InvalidArgumentException("only standard boats permitted at docks {$this->type->value}");
        }
    }

    public function refill(Bag $bag, SkillPile $skillPile): void {
        if (count($this->passengers) != 0 || count($this->cargo) != 0) {
            throw new InvalidArgumentException("boat {$this->type->value} not empty");
        }
        $this->passengers = $bag->take($this->type->passengerCapacity());
        $this->cargo = $skillPile->take($this->type->cargoCapacity());
    }

    public function flip(): void {
        $next = $this->type->flip();
        if ($next === null) {
            throw new InvalidArgumentException("can't flip {$this->type->value}");
        }
        $this->type = $next;
    }
}
