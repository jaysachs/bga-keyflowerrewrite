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

use Bga\Games\keyflowerrewrite\Utils\Arrays;
use InvalidArgumentException;

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
        $color = WorkerColor::NONE;
        foreach ($this->workers as $worker) {
            $color = $color->plus($worker->color);
        }
        return $color;
    }

    public function takeNWorkersOfColor(int $n, WorkerColor $color): WorkerGroup {
        $result = new WorkerGroup();
        if ($n <= 0) {
            return $result;
        }

        $ids = array_keys($this->workers);
        Arrays::shuffle($ids);

        while ($n > 0) {
            $id = array_shift($ids);
            if ($id === null) {
                break;
            }
            $worker = $this->workers[$id];
            if ($worker->color->matches($color)) {
                $n--;
                $result->add($worker);
            }
        }
        foreach ($result->allItems() as $w) {
            unset($this->workers[$w->id]);
        }
        return $result;
    }

    public function matching(WorkerColor $color): WorkerGroup {
        return WorkerGroup::of(... array_filter($this->workers, fn ($w) => $color->matches($w->color)));
    }

    public function hasMatching(WorkerColor $color): bool {
        return array_find($this->workers, fn ($w) => $w->color->matches($color)) !== null;
    }

    public function takeWorkerById(int $id): Worker {
        if (!isset($this->workers[$id])) {
            throw new InvalidArgumentException("no worker with id {$id}");
        }
        $w = $this->workers[$id];
        unset($this->workers[$id]);
        return $w;
    }

    public function workerById(int $id): ?Worker {
        return isset($this->workers[$id]) ? $this->workers[$id] : null;
    }

    public function add(Worker $worker): void {
        if (isset($this->workers[$worker->id])) {
            throw new InvalidArgumentException("cannot add worker twice to group");
        }
        if (!$worker->color->isConcrete()) {
            throw new InvalidArgumentException("cannot add uncolored worker");
        }
        $this->workers[$worker->id] = $worker;
    }

    public function addGroup(WorkerGroup $other): void {
        foreach ($other->workers as $w) {
            $this->add($w);
        }
    }

    /** @return array<int,Worker> */
    public function allItems(): array {
        return $this->workers;
    }

    public function size(): int {
        return count($this->workers);
    }

    public function marshal(): mixed
    {
        return array_map(fn ($w) => $w->marshal(), array_values($this->workers));
    }
}
