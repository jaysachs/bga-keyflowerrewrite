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

use Bga\Games\keyflowerrewrite\Utils\Arrays;
use Bga\Games\keyflowerrewrite\Utils\Log;
use InvalidArgumentException;

class Village {
    /**
     * @param array<int,VillageTile> $tiles  key is encoded row/col
     * @param list<StockTile> $winterTiles
     * @param list<StockTile> $wonTiles
     */
    public function __construct(
        public readonly int $playerId,
        private array $tiles,
        private WorkerGroup $workers,
        public SkillSet $skills,
        private array $winterTiles,
        private array $wonTiles,
        private bool $hasStartPlayer) {}

    public function takeWorkersFromStock(WorkerColor $color, int $num): WorkerGroup {
        return $this->workers->takeNWorkersOfColor($num, $color);
    }

    /** @return array<int,VillageTile> */
    public function tiles(): array {
        return $this->tiles;
    }

    public function workers(): WorkerGroup {
        return $this->workers;
    }

    // FIXME: do we need these 3 forwarders now?

    public function workerById(int $id): ?Worker {
        return $this->workers->workerById($id);
    }
    public function addWorker(Worker $worker): void {
        $this->workers->add($worker);
    }

    public function addWorkerGroup(WorkerGroup $wg): void {
        $this->workers->addGroup($wg);
    }

    public function takeWorker(int $id): void {
        $this->workers->takeWorkerById($id);
    }

    public function addSkill(Skill $skill): void {
        $this->skills->add($skill);
    }

    public function takeSkill(int $id): void {
        $this->skills->takeSkillById($id);
    }

    /** @return list<StockTile> */
    public function winterTiles(): array {
        return $this->winterTiles;
    }

    /** @return list<StockTile> */
    public function wonTiles(): array {
        return $this->wonTiles;
    }

    public function addWonTile(StockTile $tile): void {
        $this->wonTiles[] = $tile;
    }

    public function placeTile(VillageTile $tile): void {
        // TODO: validate placement!
        $this->tiles[$tile->rowcol] = $tile;
    }

    public function setHasStartPlayer(bool $hasStart): void {
        $this->hasStartPlayer = $hasStart;
    }

    public function hasStartPlayer(): bool {
        return $this->hasStartPlayer;
    }

    public function tileById(int $tile_id): ?VillageTile {
        foreach ($this->tiles as $vtile) {
            if ($vtile->tile_id == $tile_id) {
                return $vtile;
            }
        }
        return null;
    }

    public function homeTile(): VillageTile {
        foreach ($this->tiles as $vtile) {
            if ($vtile->type->isHome()) {
                return $vtile;
            }
        }
        throw new InvalidArgumentException("no home tile on village for {$this->playerId}");
    }

    public function hasTileType(TileType $tileType): bool {
        foreach ($this->tiles as $vtile) {
            if ($vtile->type == $tileType) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return array<mixed>
     */
    public function marshal(bool $allData = false): array {
        $result = [
            'tiles' => array_map(
                fn ($vt) => [
                    'tile_id' => $vt->tile_id,
                    'tiletype' => $vt->type->value,
                    'rowcol' => $vt->rowcol,
                    'orientation' => $vt->orientation,
                    'activations' => $vt->productionActivations()->marshal()
                ],
                $this->tiles()
            ),
            'won_tiles' => array_map(
                fn ($wt) => [
                    'tile_id' => $wt->tile_id,
                    'tiletype' => $wt->type->value,
                ],
                $this->wonTiles()
            ),
            'player_id' => $this->playerId,
            'has_start_player' => $this->hasStartPlayer(),
        ];
        if ($allData) {
            $result['workers'] = $this->workers()->marshal();
            $result['skills'] = $this->skills->marshal();
        }
        return $result;
    }

    /**
     * @param VillageTile|MarketLot $tile
     * @param array<int,WorkerGroup> $moveableBids  keyed by tile_id, though not used at present
     */
    public function canActivate(mixed $tile, array $moveableBids): bool {
        $hasBoat4b = $this->hasTileType(TileType::BOAT4B);
        $pa = $tile->productionActivations();
        if (!$pa->isActivatable()) {
            Log::error("{$tile->type->value} not activatable");
            return false;
        }
        $currentColor = $tile->currentColor();
        $cost = $tile->type->activationCost();
        $requiredWorkerColor = WorkerColor::NONE;
        $requiredSkill = null;
        switch ($cost) {
            case ActivationCost::SKILL_ANVIL:
                $requiredSkill = SkillType::ANVIL;
                break;
            case ActivationCost::SKILL_SAW:
                $requiredSkill = SkillType::SAW;
                break;
            case ActivationCost::SKILL_PICKAXE:
                $requiredSkill = SkillType::PICKAXE;
                break;
            case ActivationCost::SKILL_ANY:
                $requiredSkill = SkillType::ANY;
                break;
            case ActivationCost::WORKER_BLUE:
                $requiredWorkerColor = WorkerColor::BLUE;
                break;
            case ActivationCost::WORKER_RED:
                $requiredWorkerColor = WorkerColor::RED;
                break;
            case ActivationCost::WORKER_YELLOW:
                $requiredWorkerColor = WorkerColor::YELLOW;
                break;
            case ActivationCost::WORKER_ANY:
                $requiredWorkerColor = WorkerColor::ONE;
                break;
        }
        if ($requiredSkill !== null && !$this->skills->hasMatching($requiredSkill)) {
            Log::error("does not have {$requiredSkill->value}");
            return false;
        }
        if ($requiredWorkerColor !== WorkerColor::NONE && !$this->workers->hasMatching($requiredWorkerColor)) {
            Log::error("does not have {$requiredWorkerColor->value}");
            return false;
        }
        // TODO: keep track that we might need to take a worker.

        $bidCombos = Arrays::powerset(array_values($moveableBids));
        foreach ($bidCombos as $bidCombo) {
            $size = 0;
            $color = WorkerColor::NONE;
            foreach ($bidCombo as $bid) {
                $size += $bid->size();
                $color = $color->plus($bid->color());
            }
            if (!$color->matches($currentColor)) {
                Log::error("{$color->value} doesn't match {$currentColor->value}");
                if (! $hasBoat4b) {
                    continue;
                }
            }

            if ($size > $pa->maxAllowedToActivate()) {
                Log::error("size too big {$size} {$pa->maxAllowedToActivate()}");
                continue;
            }
            $stock = WorkerGroup::of(... $this->workers->allItems());
            // if specific color required to pay, remove it here
            if ($requiredWorkerColor->isConcrete()) {
                if ($stock->takeNWorkersOfColor(1, $requiredWorkerColor)->isEmpty()) {
                    Log::error("couldn't find {$requiredWorkerColor->value} meeple in stock for payment");
                    continue;
                }
            }
            $matchingStockCount = $stock->matching($currentColor)->size();
            // check for "any" worker after grabbing matching
            if ($requiredWorkerColor === WorkerColor::ONE) {
                if ($matchingStockCount === $stock->size()) {
                    Log::error("taking ONE worker {$matchingStockCount} {$stock->size()}");
                    $matchingStockCount--;
                }
            }
            if ($size + $matchingStockCount < $pa->minRequiredToActivate()) {
                Log::error("not enough meeples: {$size} {$matchingStockCount}, {$pa->minRequiredToActivate()}");
                continue;
            }
            return true;
        }
        return false;
    }
}