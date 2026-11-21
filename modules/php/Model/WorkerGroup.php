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

// FIXME: Consider calling this BidGroup, and then can correctly validate single-color.
// Compare that to an ActivationGroup, which is allowed to have multiple colors.
class WorkerGroup {

    /** @var array<int,Worker> */
    private array $workers;

    /** @param array<int,Worker> $workers */
    public function __construct(array $workers)
    {
        $this->workers = [];
        $color = null;
        // FIXME: if boat4b in play, need to by pass this check if making
        //   a group for activation
        foreach ($workers as $worker) {
            if ($color === null) {
                $color = $worker->color;
            } else if ($worker->color != $color) {
                // throw new InvalidArgumentException("worker groups must be of a single color, got {$color->value} and {$worker->color->value}");
            }
            $this->workers[$worker->id] = $worker;
        }
    }

    /**
     * @return WorkerColor|null color, of null if (a) no workers or (b) multiple colors
     */
    public function color(): ?WorkerColor {
        $color = null;
        foreach ($this->workers as $worker) {
            if ($color === null) {
                $color = $worker->color;
            }
            else if ($color != $worker->color) {
                // FIXME: null means "multiple" ... is that ok?
                return null;
            }
        }
        return $color;
    }

    /** @return array<int,Worker> */
    public function workers(): array {
        return $this->workers;
    }

    public function size(): int {
        return count($this->workers);
    }

    /* does not mutate this */
    public function combineWith(WorkerGroup $other): WorkerGroup {
        $workers = [];
        array_push($workers, ... $this->workers);
        array_push($workers, ... $other->workers);
        return new WorkerGroup($workers);
    }
}
