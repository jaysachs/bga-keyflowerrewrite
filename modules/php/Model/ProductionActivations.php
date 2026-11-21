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

class ProductionActivations {
    private const MAX_WORKERS = 6;
    /** @param array<int,WorkerGroup>  $activations */
    public function __construct(private array $activations = []) { }

    /** @return array<int,WorkerGroup> */
    public function activations(): array { return $this->activations; }

    public function totalWorkers(): int {
        return array_reduce($this->activations(), fn (int $carry, WorkerGroup $wg) => $carry + $wg->size(), 0);
    }

    public function lastActivationSize(): int {
        $mr = $this->mostRecent();
        return $mr ? $mr->size() : 0;
    }

    public function mostRecent(): ?WorkerGroup {
        if (count($this->activations()) == 0) { return null; }
        return $this->activations[count($this->activations)-1];
    }

    public function add(WorkerGroup $wg): void {
        $mr = $this->mostRecent();
        if ($mr && $mr->size() >= $wg->size()) {
            throw new InvalidArgumentException("activation must be larger than most recent ({$mr->size()})");
        }
        if ($wg->size() + $this->totalWorkers() > self::MAX_WORKERS) {
            throw new InvalidArgumentException("cannot be more than 6 activating workers on a tile");
        }
        $this->activations[] = $wg;
    }

    public function currentColor(): ?WorkerColor {
        if (isset($this->activations[0])) {
            return $this->activations[0]->color();
        }
        return null;
    }

    public function isActivatable(): bool {
        return $this->maxAllowededToActivate() >= $this->minRequiredToActivate();
    }

    public function minRequiredToActivate(): int {
        return ($this->mostRecent()?->size() ?? 0) + 1;
    }

    public function maxAllowededToActivate(): int {
        return self::MAX_WORKERS - $this->lastActivationSize();
    }
}