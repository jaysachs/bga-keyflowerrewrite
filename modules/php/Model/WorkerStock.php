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

class WorkerStock {
    /**
     * @param list<Worker> $bagworkers
     * @param list<Worker> $greens
     **/
    public function __construct(private array $bagworkers, private array $greens) {
        foreach ($greens as $green) {
            if ($green->color != WorkerColor::GREEN) {
                throw new InvalidArgumentException("green workers should be green but found {$green->color->value}");
            }
        }
    }

    /** @return list<Worker> */
    public function greens(): array {
        return $this->greens;
    }

    /** @return list<Worker> */
    public function bagWorkers(): array {
        return $this->bagworkers;
    }

    public static function forNewGame(): WorkerStock {
        $lastId = 2000;
        $result = new WorkerStock([], []);
        foreach ([WorkerColor::RED, WorkerColor::BLUE, WorkerColor::YELLOW] as $wt) {
            for ($i = 0; $i < 40; $i++) {
                $result->bagworkers[] = new Worker(++$lastId, $wt);
            }
        }
        shuffle($result->bagworkers);
        for ($i = 0; $i < 20; ++$i) {
            $result->greens[] = new Worker(++$lastId, WorkerColor::GREEN);
        }
        return $result;
    }

    /** @return list<Worker> */
    public function takeGreens(int $n): array {
        return array_splice($this->greens, 0, $n);
    }

    /** @return list<Worker> */
    public function takeFromBag(int $n): array {
        return array_splice($this->bagworkers, 0, $n);
    }

    public function bagSize(): int {
        return count($this->bagworkers);
    }

    public function greensSize(): int {
        return count($this->greens);
    }
}
