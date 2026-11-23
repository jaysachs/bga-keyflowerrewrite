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

class AllGameData {
    /** @param list<int> $playerIds */
    public function __construct(public readonly array $playerIds, public ?Season $season)
    {
        $this->workerStock = new WorkerStock();
        $this->skillPile = new SkillPile();
        $this->resourceStock = new ResourceStock();
        $this->tilesStock = new TilesStock();
        $this->villages = [];
        foreach ($this->playerIds as $pid) {
            $this->villages[$pid] = new Village($pid, [], new WorkerGroup(), [], [], [], false);
        }

        $this->market = new Market();
        $this->dock = new Dock();
    }

    public WorkerStock $workerStock;

    public SkillPile $skillPile;

    public ResourceStock $resourceStock;

    public TilesStock $tilesStock;

    // TODO: would like too make readonly
    /** @var array<int,Village> */
    public array $villages;

    public Market $market;
    public Dock $dock;

    /**
     * @param list<int> $playerIds
     */
    public static function newGameDataFor(array $playerIds): AllGameData {
        $numPlayers = count($playerIds);
        if ($numPlayers < 2 || $numPlayers > 6) {
            throw new InvalidArgumentException("player count must be between 2 and 6");
        }

        $result = new AllGameData($playerIds, null);
        $result->workerStock = WorkerStock::forNewGame();
        $result->skillPile = SkillPile::forNewGame();
        $result->resourceStock = ResourceStock::forNewGame();
        $result->tilesStock = TilesStock::forNewGame($numPlayers);

        $homes = TileType::initialHomeTiles();
        shuffle($homes);
        array_splice($homes, $numPlayers);
        $min = TileType::HOME6;
        foreach ($homes as $home) {
            if ($home->value < $min->value) {
                $min = $home;
            }
        }

        $lastTileId = 1100;
        $winterTiles = $result->tilesStock->takeTilesFor(Season::WINTER);
        $perPlayer = intval(count($winterTiles) / $numPlayers);
        foreach ($playerIds as $playerId) {
            $workers = $result->workerStock->takeFromBag(8);
            $wts = array_splice($winterTiles, 0, $perPlayer);
            /** @var TileType */
            $home = array_shift($homes);
            $result->villages[$playerId] = new Village(
                $playerId,
                [new VillageTile(++$lastTileId, $home, RowCol::fromRowCol(0, 0), 1, $playerId)],
                $workers,
                [],
                $wts,
                [],
                $home == $min);
        }

        $result->dock = Dock::forNewGame($numPlayers);
        $result->market = new Market();

        $result->season = null;

        return $result;
    }

    public function applyActivationPayment(int $playerId, ActivationPayment $payment): void {
        $worker = $payment->worker;
        $skill = $payment->skill;

        if ($worker) {
            $this->workerStock->putInBag($worker);
            $this->villages[$playerId]->takeWorker($worker->id);
        } else if ($skill) {
            $this->skillPile->put($skill);
            $this->villages[$playerId]->takeSkill($skill->id);
        }

    }

    public function takeActivationRewards(ActivationReward $rewardType, ?ResourceType $requested): ProductionResult {
        $skills = null;
        $resources = null;
        $workers = new WorkerGroup();
        $upgrades = null;

        switch ($rewardType) {
            case ActivationReward::RESOURCE_1:
                if (!$requested) {
                    throw new InvalidArgumentException("selected resource reward requires a specific type requested");
                }
                $resources = $this->resourceStock->take($requested, 1);
                $requested = null;
                break;
            case ActivationReward::GOLD:
                $resources = $this->resourceStock->take(ResourceType::GOLD, 1);
                break;
            case ActivationReward::GOLD_2:
                $resources = $this->resourceStock->take(ResourceType::GOLD, 2);
                break;
            case ActivationReward::GOLD_3:
                $resources = $this->resourceStock->take(ResourceType::GOLD, 3);
                break;
            case ActivationReward::WOOD:
                $resources = $this->resourceStock->take(ResourceType::WOOD, 1);
                break;
            case ActivationReward::WOOD_2:
                $resources = $this->resourceStock->take(ResourceType::WOOD, 2);
                break;
            case ActivationReward::WOOD_3:
                $resources = $this->resourceStock->take(ResourceType::WOOD, 3);
                break;
            case ActivationReward::IRON:
                $resources = $this->resourceStock->take(ResourceType::IRON, 1);
                break;
            case ActivationReward::IRON_2:
                $resources = $this->resourceStock->take(ResourceType::IRON, 2);
                break;
            case ActivationReward::IRON_3:
                $resources = $this->resourceStock->take(ResourceType::IRON, 3);
                break;
            case ActivationReward::STONE:
                $resources = $this->resourceStock->take(ResourceType::STONE, 1);
                break;
            case ActivationReward::STONE_2:
                $resources = $this->resourceStock->take(ResourceType::STONE, 2);
                break;
            case ActivationReward::STONE_3:
                $resources = $this->resourceStock->take(ResourceType::STONE, 3);
                break;
            case ActivationReward::IRON_GOLD:
                $resources = array_merge(
                    $resources = $this->resourceStock->take(ResourceType::IRON, 1),
                    $resources = $this->resourceStock->take(ResourceType::GOLD, 1)
                );
                break;
            case ActivationReward::WOOD_GOLD:
                $resources = array_merge(
                    $resources = $this->resourceStock->take(ResourceType::WOOD, 1),
                    $resources = $this->resourceStock->take(ResourceType::GOLD, 1)
                );
                break;
            case ActivationReward::STONE_GOLD:
                $resources = array_merge(
                    $resources = $this->resourceStock->take(ResourceType::STONE, 1),
                    $resources = $this->resourceStock->take(ResourceType::GOLD, 1)
                );
                break;
            case ActivationReward::RESOURCE_3:
                 $resources = array_merge(
                    $resources = $this->resourceStock->take(ResourceType::WOOD, 1),
                    $resources = $this->resourceStock->take(ResourceType::STONE, 1),
                    $resources = $this->resourceStock->take(ResourceType::IRON, 1)
                );
                break;
            case ActivationReward::SKILL_ANY:
                $skills = $this->skillPile->take(1);
                break;
            case ActivationReward::SKILL_ANY_2:
                $skills = $this->skillPile->take(2);
                break;
            case  ActivationReward::SKILL_ANY_3:
                $skills = $this->skillPile->take(3);
                break;
            case ActivationReward::WORKER_ANY:
                $workers = $this->workerStock->takeFromBag(1);
                break;
            case ActivationReward::WORKER_ANY_2:
                $workers = $this->workerStock->takeFromBag(2);
                break;
            case ActivationReward::WORKER_ANY_3:
                $workers = $this->workerStock->takeFromBag(3);
                break;
            case ActivationReward::WORKER_GREEN:
                $workers = $this->workerStock->takeGreens(1);
                break;
            case ActivationReward::WORKER_GREEN_2:
                $workers = $this->workerStock->takeGreens(2);
                break;
            case ActivationReward::UPGRADE_1:
                $upgrades = 1;
                break;
            case ActivationReward::UPGRADE_2:
                $upgrades = 2;
                break;
            case ActivationReward::UPGRADE_3:
                $upgrades = 3;
                break;
            case ActivationReward::UPGRADE_4:
                $upgrades = 4;
                break;
            case ActivationReward::UPGRADE_5:
                $upgrades = 5;
                break;
            case ActivationReward::UPGRADE_6:
                $upgrades = 6;
                break;
            default:
                throw new InvalidArgumentException("Unknown reward type: {$rewardType->value}");
        }
        if ($requested) {
            throw new InvalidArgumentException("cannot specify a resource type");
        }
        return new ProductionResult($workers, $resources, $skills, $upgrades);
    }

}