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

class ProductionResult {
    /**
     * @param int|null $upgrades
     */
    public function __construct(
        public readonly WorkerGroup $workers, // = new WorkerGroup(),
        public readonly ResourceSet $resources,
        public readonly SkillSet $skills,
        public readonly ?int $upgrades) {
            // TODO: keep this validation?
            $specified = ($workers->isEmpty() ? 0 : 1) + ($resources->isEmpty() ? 0 : 1) + ($skills->isEmpty() ? 0 : 1) + ($upgrades !== null ? 1 : 0);
            if ($specified != 1) {
                throw new InvalidArgumentException("must specify exactly one of workers, resources, skills, and upgrades");
            }
        }

}