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


class VillageTile {
    public function __construct(
        public readonly int $tile_id,
        public readonly TileType $type,
        public readonly int $rowcol,
        public readonly int $orientation,
        // FIXME: this is somewhat redundant; consider removing it.
        public readonly int $playerId,
        private ResourceSet $resources = new ResourceSet(),
        private ProductionActivations $productionActivations = new ProductionActivations()) { }

    public function resources(): ResourceSet { return $this->resources; }

    public function addResource(Resource $res): void {
        $this->resources->put($res);
    }

    public function currentColor(): WorkerColor {
        return $this->productionActivations->currentColor();
    }

    public function productionActivations(): ProductionActivations { return $this->productionActivations; }
}
