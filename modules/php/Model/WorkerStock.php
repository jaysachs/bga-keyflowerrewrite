<?php

/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * Keyflower implementation : © Jay Sachs <vagabond@covariant.org>
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

class WorkerStock {
    private WorkerGroup $bag;
    private WorkerGroup $greens;

    public function __construct() {
        $this->bag = new WorkerGroup();
        $this->greens = new WorkerGroup();
    }

    public function greens(): WorkerGroup {
        return $this->greens;
    }

    public function bag(): WorkerGroup {
        return $this->bag;
    }

    public static function forNewGame(): WorkerStock {
        $lastId = 2000;
        $result = new WorkerStock();
        foreach ([WorkerColor::RED, WorkerColor::BLUE, WorkerColor::YELLOW] as $wt) {
            for ($i = 0; $i < 40; $i++) {
                $result->bag->add(new Worker(++$lastId, $wt));
            }
        }
        for ($i = 0; $i < 20; ++$i) {
            $result->greens->add(new Worker(++$lastId, WorkerColor::GREEN));
        }
        $result->validate();
        return $result;
    }

    private function validate(): void {
        $allIds = array_merge(
            array_map(fn (Worker $w) => $w->id, $this->greens->allItems()),
            array_map(fn (Worker $w) => $w->id, $this->bag->allItems()));
        if (count($allIds) != count(array_unique($allIds))) {
            throw new InvalidArgumentException("duplicate worker added");
        };
        foreach ($this->greens->allItems() as $green) {
            if ($green->color != WorkerColor::GREEN) {
                throw new InvalidArgumentException("green workers should be green but found {$green->color->value}");
            }
        }
    }

    public function putGreen(Worker $worker): void {
        if ($worker->color != WorkerColor::GREEN) {
            throw new InvalidArgumentException("Only green workers go in green supply");
        }
        $this->greens->add($worker);
        $this->validate();
    }

    public function putInBag(Worker $worker): void {
        $this->bag->add($worker);
        $this->validate();
    }

    public function takeGreens(int $n): WorkerGroup {
        return $this->greens->takeNWorkersOfColor($n, WorkerColor::GREEN);
    }

    public function takeFromBag(int $n): WorkerGroup {
        return $this->bag->takeNWorkersOfColor($n, WorkerColor::ANY);
    }

    public function bagSize(): int {
        return $this->bag->size();
    }

    public function greensSize(): int {
        return $this->greens->size();
    }
}
