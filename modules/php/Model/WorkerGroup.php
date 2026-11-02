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

class WorkerGroup {
    /** @param list<Worker> $workers */
    public function __construct(public array $workers)
    {
        $color = null;
        foreach ($workers as $worker) {
            if ($color === null) {
                $color = $worker->type;
            } else if ($worker->type != $color) {
                throw new InvalidArgumentException("worker groups must be of a single color");
            }
        }
        if ($color == null) {
            throw new InvalidArgumentException("empty worker group");
        }
    }

    public function color(): WorkerType {
        return $this->workers[0]->type;
    }

    public function size(): int {
        return count($this->workers);
    }

    public function combineWith(WorkerGroup $other): void {
        if ($other->workers[0]->type != $this->workers[0]->type) {
            throw new InvalidArgumentException("can't combine workers of different colors");
        }
        $this->workers += $other->workers;
        $other->workers = [];
    }
}
