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

enum ActivationReward: string {
    case WORKER_GREEN = "workergreen";
    case WORKER_GREEN_2 = "workergreen2";
    case WORKER_ANY = "workerany";
    case WORKER_ANY_2 = "workerany2";
    case WORKER_ANY_3 = "workerany3";
    case SKILL_ANY = "skillany";
    case SKILL_ANY_2 = "skillany2";
    case SKILL_ANY_3 = "skillany3";
    case GOLD = "gold";
    case GOLD_2 = "gold2";
    case GOLD_3 = "gold3";
    case IRON = "iron";
    case IRON_2 = "iron2";
    case IRON_3 = "iron3";
    case WOOD = "wood";
    case WOOD_2 = "wood2";
    case WOOD_3 = "wood3";
    case STONE = "stone";
    case STONE_2 = "stone2";
    case STONE_3 = "stone3";
    case IRON_GOLD = "irongold";
    case STONE_GOLD = "stonegold";
    case WOOD_GOLD = "woodgold";
    case RESOURCE_1 = "resource1"; // choice
    case RESOURCE_3 = "resource3"; // one of each
    case UPGRADE_1 = "upgrade1";
    case UPGRADE_2 = "upgrade2";
    case UPGRADE_3 = "upgrade3";
    case UPGRADE_4 = "upgrade4";
    case UPGRADE_5 = "upgrade5";
    case UPGRADE_6 = "upgrade6";

    case NONE = "none";
}