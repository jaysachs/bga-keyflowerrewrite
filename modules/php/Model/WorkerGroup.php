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
use Override;

// FIXME: Consider calling this BidGroup, and then can correctly validate single-color.
// Compare that to an ActivationGroup, which is allowed to have multiple colors.
class WorkerGroup implements Marshalable {

    /** @var array<int,Worker> */
    private array $workers;

    /** @param array<Worker> $workers */
    public function __construct(array $workers = [])
    {
        $this->workers = [];
        foreach ($workers as $worker) {
            $this->add($worker);
        }
    }

    public static function of(Worker... $ws): WorkerGroup {
        return new WorkerGroup($ws);
    }

    public function isEmpty(): bool {
        return count($this->workers) == 0;
    }

    /**
     * @return WorkerColor color, NONE if no workers, ANY if multiple colors
     */
    public function color(): WorkerColor {
        // FIXME: return ANY if no workers?
        $color = WorkerColor::NONE;
        foreach ($this->workers as $worker) {
            if ($color === WorkerColor::NONE) {
                $color = $worker->color;
            }
            else if ($color != $worker->color) {
                // FIXME: null means "multiple" ... is that ok?
                return WorkerColor::ANY;
            }
        }
        return $color;
    }

    public function add(Worker $worker): void {
        if (isset($this->workers[$worker->id])) {
            throw new InvalidArgumentException("cannot add worker twice to group");
        }
        $this->workers[$worker->id] = $worker;
    }

    /** @return array<int,Worker> */
    public function workers(): array {
        return $this->workers;
    }

    /** @return list<int> */
    public function workerIds(): array {
        return array_keys($this->workers);
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

    #[Override]
    public function marshal(): mixed
    {
        return array_map(fn ($w) => $w->marshal(), array_values($this->workers));
    }
}
