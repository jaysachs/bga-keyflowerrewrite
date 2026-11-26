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

class Dock implements Marshalable {
    // TODO: convert to array<id, DockTile>
    /** @var list<DockTile> $boats */
    private  array $boats;
    public function __construct() {
    }

    public function add(DockTile $boat): void {
        $this->boats[] = $boat;
        $this->validate();
    }

    public function boatById(int $id): ?DockTile {
        return array_find($this->boats, fn (DockTile $b, int $_i) => $b->id == $id);
    }

    /** @return list<DockTile> */
    public function boats(): array {
        return $this->boats;
    }

    private function validate(): void {
        $ids = [];
        foreach ($this->boats as $boat) {
            if (!$boat->type->isStandardBoat()) {
                throw new InvalidArgumentException("Non-standard boat {$boat->type->value} added to Dock");
            }
            $ids[] = $boat->id;
        }
        if (count(array_unique($ids)) != count($this->boats)) {
            throw new InvalidArgumentException("Duplicate boats added to Dock");
        }
    }

    public function replenish(WorkerStock $workerStock, SkillSet $skillPile): void {
        foreach ($this->boats as $boat) {
            $boat->refill($workerStock, $skillPile);
        }
    }

    /** @return list<DockTile> the flipped boats */
    public function flipForSeason(Season $season): array {
        $flipped = [];
        foreach ($this->boats as $boat) {
            if ($boat->type->flipsIn($season)) {
                $boat->flip();
                $flipped[] = $boat;
            }
        }
        return $flipped;
    }

    public static function forNewGame(int $numPlayers): Dock {
        $lastId = 400;
        $dock = new Dock();
        foreach (TileType::initialStandardBoats($numPlayers) as $tt) {
            $dock->add(new DockTile(++$lastId, $tt));
        }
        return $dock;
    }

    /**
     * @return array<mixed>
     */
    public function marshal(): array {
        return array_map(fn ($b) => $b->marshal(), $this->boats);
    }
}