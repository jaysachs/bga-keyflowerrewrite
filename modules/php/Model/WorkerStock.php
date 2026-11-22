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
    /** @var list<Worker> $bag */
    private array $bag = [];
    /** @var list<Worker> $greens */
    private array $greens = [];

    public function __construct() {}

    /** @return list<Worker> */
    public function greens(): array {
        return $this->greens;
    }

    /** @return list<Worker> */
    public function bagWorkers(): array {
        return $this->bag;
    }

    public static function forNewGame(): WorkerStock {
        $lastId = 2000;
        $result = new WorkerStock();
        foreach ([WorkerColor::RED, WorkerColor::BLUE, WorkerColor::YELLOW] as $wt) {
            for ($i = 0; $i < 40; $i++) {
                $result->bag[] = new Worker(++$lastId, $wt);
            }
        }
        shuffle($result->bag);
        for ($i = 0; $i < 20; ++$i) {
            $result->greens[] = new Worker(++$lastId, WorkerColor::GREEN);
        }
        $result->validate();
        return $result;
    }

    private function validate(): void {
        $allIds = array_merge(
            array_map(fn (Worker $w) => $w->id, $this->greens),
            array_map(fn (Worker $w) => $w->id, $this->bag));
        if (count($allIds) != count(array_unique($allIds))) {
            throw new InvalidArgumentException("duplicate worker added");
        };
        foreach ($this->greens as $green) {
            if ($green->color != WorkerColor::GREEN) {
                throw new InvalidArgumentException("green workers should be green but found {$green->color->value}");
            }
        }
    }

    public function putGreen(Worker $worker): void {
        if ($worker->color != WorkerColor::GREEN) {
            throw new InvalidArgumentException("Only green workers go in green supply");
        }
        $this->greens[] = $worker;
        $this->validate();
    }

    public function putInBag(Worker $worker): void {
        $this->bag[] = $worker;
        $this->validate();
    }

    /** @return list<Worker> */
    public function takeGreens(int $n): array {
        return array_splice($this->greens, 0, $n);
    }

    /** @return list<Worker> */
    public function takeFromBag(int $n): array {
        return array_splice($this->bag, 0, $n);
    }

    public function bagSize(): int {
        return count($this->bag);
    }

    public function greensSize(): int {
        return count($this->greens);
    }
}
