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

enum TileType: string
{
    case HOME1 = "home1";
    case HOME2 = "home2";
    case HOME3 = "home3";
    case HOME4 = "home4";
    case HOME5 = "home5";
    case HOME6 = "home6";

    case HOME1_U = "home1_u";
    case HOME2_U = "home2_u";
    case HOME3_U = "home3_u";
    case HOME4_U = "home4_u";
    case HOME5_U = "home5_u";
    case HOME6_U = "home6_u";

        // spring
    case ALEHOUSE = "alehouse";
    case APPRENTICEHALL = "apprenticehall";
    case GOLDMINE = "goldmine";
    case KEYMINE = "keymine";
    case KEYSTONEQUARRY = "keystonequarry";
    case KEYWOOD = "keywood";
    case MINER = "miner";
    case PEDDLAR = "peddlar";
    case QUARRYMAN = "quarryman";
    case STABLE = "stable";
    case WOODCUTTER = "woodcutter";
    case WORKSHOP = "workshop";

    case ALEHOUSE_U = "alehouse_u";
    case APPRENTICEHALL_U = "apprenticehall_u";
    case GOLDMINE_U = "goldmine_u";
    case KEYMINE_U = "keymine_u";
    case KEYSTONEQUARRY_U = "keystonequarry_u";
    case KEYWOOD_U = "keywood_u";
    case MINER_U = "miner_u";
    case PEDDLAR_U = "peddlar_u";
    case QUARRYMAN_U = "quarryman_u";
    case STABLE_U = "stable_u";
    case WOODCUTTER_U = "woodcutter_u";
    case WORKSHOP_U = "workshop_u";

        // summer
    case BREWER = "brewer";
    case CARPENTER = "carpenter";
    case FARRIER = "farrier";
    case HIRINGFAIR = "hiringfair";
    case MASON = "mason";
    case SMELTER = "smelter";
    case STORE = "store";
    case TAVERN = "tavern";

    case BREWER_U = "brewer_u";
    case CARPENTER_U = "carpenter_u";
    case FARRIER_U = "farrier_u";
    case HIRINGFAIR_U = "hiringfair_u";
    case MASON_U = "mason_u";
    case SMELTER_U = "smelter_u";
    case STORE_U = "store_u";
    case TAVERN_U = "tavern_u";

        // fall tiles
    case BARN = "barn";
    case BLACKSMITH = "blacksmith";
    case FAIR = "fair";
    case FORGE = "forge";
    case GOLDSMITH = "goldsmith";
    case INN = "inn";
    case SAWMILL = "sawmill";
    case SCULPTOR = "sculptor";
    case STONEYARD = "stoneyard";
    case TIMBERYARD = "timberyard";
    case WAINWRIGHT = "wainwright";
    case WELL = "well";

    case BARN_U = "barn_u";
    case BLACKSMITH_U = "blacksmith_u";
    case FAIR_U = "fair_u";
    case FORGE_U = "forge_u";
    case GOLDSMITH_U = "goldsmith_u";
    case INN_U = "inn_u";
    case SAWMILL_U = "sawmill_u";
    case SCULPTOR_U = "sculptor_u";
    case STONEYARD_U = "stoneyard_u";
    case TIMBERYARD_U = "timberyard_u";
    case WAINWRIGHT_U = "wainwright_u";
    case WELL_U = "well_u";

        // winter
    case APOTHECARY = "apothecary";
    case CRAFTSMANSGUILD = "craftsmansguild";
    case JEWELLER = "jeweller";
    case KEYGUILD = "keyguild";
    case KEYMARKET = "keymarket";
    case KEYTHEDRAL = "keythedral";
    case MERCERSGUILD = "mercersguild";
    case SCHOLAR = "scholar";
    case SCRIBES = "scribes";
    case VILLAGEHALL = "villagehall";
    case WATERMILL = "watermill";
    case WINDMILL = "windmill";

        // boats
    case BOAT1A = "boat1a";
    case BOAT1B = "boat1b";
    case BOAT2A = "boat2a";
    case BOAT2B = "boat2b";
    case BOAT3A = "boat3a";
    case BOAT3B = "boat3b";
    case BOAT4A = "boat4a";
    case BOAT4B = "boat4b";
    case KEYFLOWER = "keyflower";
    case SEABASTION = "seabastion";
    case SEABREESE = "seabreese";
    case FLIPPER = "flipper";
    case INVINCIBLE = "invincible";
    case WHITEWIND = "whitewind";

    case KEYFLOWER_U = "keyflower_u";
    case SEABASTION_U = "seabastion_u";
    case SEABREESE_U = "seabreese_u";
    case FLIPPER_U = "flipper_u";
    case INVINCIBLE_U = "invincible_u";
    case WHITEWIND_U = "whitewind_u";

    case TURN_ORDER_2 = "turnorder2";
    case TURN_ORDER_3 = "turnorder3";
    case TURN_ORDER_36 = "turnorder36";
    case TURN_ORDER_4 = "turnorder4";
    case TURN_ORDER_46 = "turnorder46";
    case TURN_ORDER_56A = "turnorder56a";
    case TURN_ORDER_56B = "turnorder56b";

    public function flip(): ?TileType
    {
        return match ($this) {
            TileType::PEDDLAR => TileType::PEDDLAR_U,
            TileType::BREWER => TileType::BREWER_U,
            TileType::CARPENTER => TileType::CARPENTER_U,
            TileType::HIRINGFAIR => TileType::HIRINGFAIR_U,
            TileType::MASON => TileType::MASON_U,
            TileType::SMELTER => TileType::SMELTER_U,
            TileType::STORE => TileType::STORE_U,
            TileType::TAVERN => TileType::TAVERN_U,
            TileType::BOAT1A => TileType::BOAT1B,
            TileType::BOAT2A => TileType::BOAT2B,
            TileType::BOAT3A => TileType::BOAT3B,
            TileType::BOAT4A => TileType::BOAT4B,

            TileType::HOME1 => TileType::HOME1_U,
            TileType::HOME2 => TileType::HOME2_U,
            TileType::HOME3 => TileType::HOME3_U,
            TileType::HOME4 => TileType::HOME4_U,
            TileType::HOME5 => TileType::HOME5_U,
            TileType::HOME6 => TileType::HOME6_U,

            TileType::ALEHOUSE => TileType::ALEHOUSE_U,
            TileType::APPRENTICEHALL => TileType::APPRENTICEHALL_U,
            TileType::GOLDMINE => TileType::GOLDMINE_U,
            TileType::KEYMINE => TileType::KEYMINE_U,
            TileType::KEYSTONEQUARRY => TileType::KEYSTONEQUARRY_U,
            TileType::KEYWOOD => TileType::KEYWOOD_U,
            TileType::MINER => TileType::MINER_U,
            TileType::QUARRYMAN => TileType::QUARRYMAN_U,
            TileType::STABLE => TileType::STABLE_U,
            TileType::WOODCUTTER => TileType::WOODCUTTER_U,
            TileType::WORKSHOP => TileType::WORKSHOP_U,
            TileType::FARRIER => TileType::FARRIER_U,

            TileType::BARN => TileType::BARN_U,
            TileType::BLACKSMITH => TileType::BLACKSMITH_U,
            TileType::FAIR => TileType::FAIR_U,
            TileType::FORGE => TileType::FORGE_U,
            TileType::GOLDSMITH => TileType::GOLDSMITH_U,
            TileType::INN => TileType::INN_U,
            TileType::SAWMILL => TileType::SAWMILL_U,
            TileType::SCULPTOR => TileType::SCULPTOR_U,
            TileType::STONEYARD => TileType::STONEYARD_U,
            TileType::TIMBERYARD => TileType::TIMBERYARD_U,
            TileType::WAINWRIGHT => TileType::WAINWRIGHT_U,
            TileType::WELL => TileType::WELL_U,

            TileType::KEYFLOWER => TileType::KEYFLOWER_U,
            TileType::SEABASTION => TileType::SEABASTION_U,
            TileType::SEABREESE => TileType::SEABREESE_U,
            TileType::FLIPPER => TileType::FLIPPER_U,
            TileType::INVINCIBLE => TileType::INVINCIBLE_U,
            TileType::WHITEWIND => TileType::WHITEWIND_U,
            default => null,
        };
    }

    /**
     * This is different than "flippable"; standard boats can be flipped but not upgraded.
     * Technically, this is
     *   (a) initial home tiles
     *   (b) initial spring, summer and fall tiles excluding boats
     */
    public function isUpgradable(): bool
    {
        return match ($this) {
            TileType::FAIR,
            TileType::PEDDLAR,
            TileType::BREWER,
            TileType::CARPENTER,
            TileType::HIRINGFAIR,
            TileType::MASON,
            TileType::SMELTER,
            TileType::STORE,
            TileType::TAVERN,
            TileType::INN,
            TileType::WAINWRIGHT,
            TileType::HOME1,
            TileType::HOME2,
            TileType::HOME3,
            TileType::HOME4,
            TileType::HOME5,
            TileType::HOME6,
            TileType::ALEHOUSE,
            TileType::APPRENTICEHALL,
            TileType::GOLDMINE,
            TileType::KEYMINE,
            TileType::KEYSTONEQUARRY,
            TileType::KEYWOOD,
            TileType::MINER,
            TileType::QUARRYMAN,
            TileType::STABLE,
            TileType::WOODCUTTER,
            TileType::WORKSHOP,
            TileType::FARRIER => true,
            default => false,
        };
    }

    /** @return non-empty-list<TileType> */
    public static function initialHomeTiles(): array
    {
        return [
            TileType::HOME1,
            TileType::HOME2,
            TileType::HOME3,
            TileType::HOME4,
            TileType::HOME5,
            TileType::HOME6,
        ];
    }

    public function usedInPlayerCount(int $playerCount): bool
    {
        return match ($this) {
            TileType::SEABREESE => $playerCount >= 3,
            TileType::FLIPPER => $playerCount >= 4,
            TileType::INVINCIBLE => $playerCount >= 5,
            TileType::WHITEWIND => $playerCount >= 6,
            TileType::TURN_ORDER_2 => $playerCount == 2,
            TileType::TURN_ORDER_3 => $playerCount == 3,
            TileType::TURN_ORDER_36 => $playerCount >= 3,
            TileType::TURN_ORDER_4 => $playerCount == 4,
            TileType::TURN_ORDER_46 => $playerCount >= 4,
            TileType::TURN_ORDER_56A => $playerCount >= 5,
            TileType::TURN_ORDER_56B => $playerCount >= 5,
            default => true,
        };
    }

    /** @return list<TileType> */
    private static function allStandardBoats(): array
    {
        return [
            TileType::KEYFLOWER,
            TileType::SEABASTION,
            TileType::SEABREESE,
            TileType::FLIPPER,
            TileType::INVINCIBLE,
            TileType::WHITEWIND,
        ];
    }

    /** @return list<TileType>  is actuall non-empty-list */
    public static function initialStandardBoats(int $playerCount): array
    {
        return array_values(array_filter(self::allStandardBoats(), fn($t) => $t->usedInPlayerCount($playerCount)));
    }

    public function isTurnOrder(): bool
    {
        return match ($this) {
            TileType::TURN_ORDER_2 => true,
            TileType::TURN_ORDER_3 => true,
            TileType::TURN_ORDER_36 => true,
            TileType::TURN_ORDER_4 => true,
            TileType::TURN_ORDER_46 => true,
            TileType::TURN_ORDER_56A => true,
            TileType::TURN_ORDER_56B => true,
            default => false,
        };
    }

    /** @return list<TileType>  is actuall non-empty-list */
    public static function turnOrderTiles(int $playerCount): array
    {
        return array_values(array_filter(TileType::cases(), fn($t) => $t->isTurnOrder() && $t->usedInPlayerCount($playerCount)));
    }

    public function activationReward(): ActivationReward
    {
        return match ($this) {
            TileType::FAIR => ActivationReward::WORKER_GREEN,
            TileType::INN => ActivationReward::WORKER_ANY,
            TileType::WAINWRIGHT => ActivationReward::UPGRADE_4,
            TileType::HOME1 =>  ActivationReward::UPGRADE_1,
            TileType::HOME2 =>  ActivationReward::UPGRADE_1,
            TileType::HOME3 =>  ActivationReward::UPGRADE_1,
            TileType::HOME4 =>  ActivationReward::UPGRADE_1,
            TileType::HOME5 =>  ActivationReward::UPGRADE_1,
            TileType::HOME6 =>  ActivationReward::UPGRADE_1,
            TileType::ALEHOUSE => ActivationReward::WORKER_ANY,
            TileType::APPRENTICEHALL => ActivationReward::SKILL_ANY,
            TileType::GOLDMINE => ActivationReward::GOLD,
            TileType::KEYMINE => ActivationReward::IRON,
            TileType::KEYSTONEQUARRY => ActivationReward::STONE,
            TileType::KEYWOOD => ActivationReward::WOOD,
            TileType::MINER => ActivationReward::IRON_2,
            TileType::PEDDLAR => ActivationReward::WORKER_GREEN,
            TileType::QUARRYMAN => ActivationReward::STONE_2,
            TileType::STABLE => ActivationReward::UPGRADE_2,
            TileType::WOODCUTTER => ActivationReward::WOOD_2,
            TileType::WORKSHOP => ActivationReward::RESOURCE_1,
            TileType::BREWER => ActivationReward::SKILL_ANY_2,
            TileType::CARPENTER => ActivationReward::WOOD_3,
            TileType::FARRIER => ActivationReward::UPGRADE_3,
            TileType::HIRINGFAIR => ActivationReward::SKILL_ANY_3,
            TileType::MASON => ActivationReward::STONE_3,
            TileType::SMELTER => ActivationReward::IRON_3,
            TileType::STORE => ActivationReward::WORKER_GREEN,
            TileType::TAVERN => ActivationReward::WORKER_ANY_2,

            TileType::HOME1_U => ActivationReward::UPGRADE_2,
            TileType::HOME2_U => ActivationReward::UPGRADE_2,
            TileType::HOME3_U => ActivationReward::UPGRADE_2,
            TileType::HOME4_U => ActivationReward::UPGRADE_2,
            TileType::HOME5_U => ActivationReward::UPGRADE_2,
            TileType::HOME6_U => ActivationReward::UPGRADE_2,
            TileType::FAIR_U => ActivationReward::WORKER_GREEN_2,
            TileType::INN_U => ActivationReward::WORKER_ANY_3,
            TileType::WAINWRIGHT_U => ActivationReward::UPGRADE_6,
            TileType::ALEHOUSE_U => ActivationReward::WORKER_ANY_2,
            TileType::APPRENTICEHALL_U => ActivationReward::SKILL_ANY_2,
            TileType::GOLDMINE_U => ActivationReward::GOLD_2,
            TileType::KEYMINE_U => ActivationReward::IRON_3,
            TileType::KEYSTONEQUARRY_U => ActivationReward::STONE_3,
            TileType::KEYWOOD_U => ActivationReward::WOOD_3,
            TileType::MINER_U => ActivationReward::IRON_GOLD,
            TileType::PEDDLAR_U => ActivationReward::WORKER_GREEN_2,
            TileType::QUARRYMAN_U => ActivationReward::STONE_GOLD,
            TileType::STABLE_U => ActivationReward::UPGRADE_4,
            TileType::WOODCUTTER_U => ActivationReward::WOOD_GOLD,
            TileType::WORKSHOP_U => ActivationReward::RESOURCE_3,
            TileType::BREWER_U => ActivationReward::WORKER_ANY_3,
            TileType::CARPENTER_U => ActivationReward::GOLD_3,
            TileType::FARRIER_U => ActivationReward::UPGRADE_5,
            TileType::HIRINGFAIR_U => ActivationReward::SKILL_ANY_3,
            TileType::MASON_U => ActivationReward::GOLD_3,
            TileType::SMELTER_U => ActivationReward::GOLD_3,
            TileType::STORE_U => ActivationReward::WORKER_GREEN_2,
            TileType::TAVERN_U => ActivationReward::WORKER_ANY_3,

            default => throw new InvalidArgumentException("tile type {$this->value} cannot be activated"), //ActivationCost::NONE,
        };
    }

    public function activationCost(): ActivationCost
    {
        return match ($this) {
            TileType::FAIR => ActivationCost::WORKER_RED,
            TileType::PEDDLAR => ActivationCost::WORKER_YELLOW,
            TileType::BREWER => ActivationCost::SKILL_ANY,
            TileType::CARPENTER => ActivationCost::SKILL_SAW,
            TileType::HIRINGFAIR => ActivationCost::SKILL_ANY,
            TileType::MASON => ActivationCost::SKILL_PICKAXE,
            TileType::SMELTER => ActivationCost::SKILL_PICKAXE,
            TileType::STORE => ActivationCost::WORKER_BLUE,
            TileType::TAVERN => ActivationCost::WORKER_ANY,

            TileType::INN => ActivationCost::NONE,
            TileType::WAINWRIGHT => ActivationCost::NONE,
            TileType::HOME1 => ActivationCost::NONE,
            TileType::HOME2 => ActivationCost::NONE,
            TileType::HOME3 => ActivationCost::NONE,
            TileType::HOME4 => ActivationCost::NONE,
            TileType::HOME5 => ActivationCost::NONE,
            TileType::HOME6 => ActivationCost::NONE,
            TileType::ALEHOUSE => ActivationCost::NONE,
            TileType::APPRENTICEHALL => ActivationCost::NONE,
            TileType::GOLDMINE => ActivationCost::NONE,
            TileType::KEYMINE => ActivationCost::NONE,
            TileType::KEYSTONEQUARRY => ActivationCost::NONE,
            TileType::KEYWOOD => ActivationCost::NONE,
            TileType::MINER => ActivationCost::NONE,
            TileType::QUARRYMAN => ActivationCost::NONE,
            TileType::STABLE => ActivationCost::NONE,
            TileType::WOODCUTTER => ActivationCost::NONE,
            TileType::WORKSHOP => ActivationCost::NONE,
            TileType::FARRIER => ActivationCost::NONE,

            TileType::FAIR_U => ActivationCost::WORKER_RED,
            TileType::PEDDLAR_U => ActivationCost::WORKER_YELLOW,
            TileType::BREWER_U => ActivationCost::SKILL_ANY,
            TileType::CARPENTER_U => ActivationCost::SKILL_SAW,
            TileType::HIRINGFAIR_U => ActivationCost::SKILL_ANY,
            TileType::MASON_U => ActivationCost::SKILL_PICKAXE,
            TileType::SMELTER_U => ActivationCost::SKILL_PICKAXE,
            TileType::STORE_U => ActivationCost::WORKER_BLUE,
            TileType::TAVERN_U => ActivationCost::WORKER_ANY,
            TileType::INN_U => ActivationCost::NONE,
            TileType::WAINWRIGHT_U => ActivationCost::NONE,
            TileType::HOME1_U => ActivationCost::NONE,
            TileType::HOME2_U => ActivationCost::NONE,
            TileType::HOME3_U => ActivationCost::NONE,
            TileType::HOME4_U => ActivationCost::NONE,
            TileType::HOME5_U => ActivationCost::NONE,
            TileType::HOME6_U => ActivationCost::NONE,
            TileType::ALEHOUSE_U => ActivationCost::NONE,
            TileType::APPRENTICEHALL_U => ActivationCost::NONE,
            TileType::GOLDMINE_U => ActivationCost::NONE,
            TileType::KEYMINE_U => ActivationCost::NONE,
            TileType::KEYSTONEQUARRY_U => ActivationCost::NONE,
            TileType::KEYWOOD_U => ActivationCost::NONE,
            TileType::MINER_U => ActivationCost::NONE,
            TileType::QUARRYMAN_U => ActivationCost::NONE,
            TileType::STABLE_U => ActivationCost::NONE,
            TileType::WOODCUTTER_U => ActivationCost::NONE,
            TileType::WORKSHOP_U => ActivationCost::NONE,
            TileType::FARRIER_U => ActivationCost::NONE,

            default => throw new InvalidArgumentException("tile type {$this->value} cannot be activated"), //ActivationCost::NONE,
        };
    }

    public function isHome(): bool
    {
        return match ($this) {
            TileType::HOME1,
            TileType::HOME2,
            TileType::HOME3,
            TileType::HOME4,
            TileType::HOME5,
            TileType::HOME6,
            TileType::HOME1_U,
            TileType::HOME2_U,
            TileType::HOME3_U,
            TileType::HOME4_U,
            TileType::HOME5_U,
            TileType::HOME6_U => true,
            default => false,
        };
    }

    /** @return list<TileType> */
    public static function initialSeasonTiles(Season $season): array {
        return array_values(
            array_filter(TileType::cases(),
                         fn ($t) => $t->season() == $season
                                    && ($season == Season::WINTER || $t->flip() !== null)));
    }

    public function season(): ?Season {
        return match ($this) {
            TileType::ALEHOUSE,
            TileType::APPRENTICEHALL,
            TileType::GOLDMINE,
            TileType::KEYMINE,
            TileType::KEYSTONEQUARRY,
            TileType::KEYWOOD,
            TileType::MINER,
            TileType::PEDDLAR,
            TileType::QUARRYMAN,
            TileType::STABLE,
            TileType::WOODCUTTER,
            TileType::WORKSHOP => Season::SPRING,

            TileType::BOAT1A,
            TileType::BOAT2A,
            TileType::BOAT3A,
            TileType::BOAT4A,
            TileType::BREWER,
            TileType::CARPENTER,
            TileType::FARRIER,
            TileType::HIRINGFAIR,
            TileType::MASON,
            TileType::SMELTER,
            TileType::STORE,
            TileType::TAVERN => Season::SUMMER,

            TileType::BARN,
            TileType::BLACKSMITH,
            TileType::FAIR,
            TileType::FORGE,
            TileType::GOLDSMITH,
            TileType::INN,
            TileType::SAWMILL,
            TileType::SCULPTOR,
            TileType::STONEYARD,
            TileType::TIMBERYARD,
            TileType::WAINWRIGHT,
            TileType::WELL => Season::FALL,

            TileType::APOTHECARY,
            TileType::CRAFTSMANSGUILD,
            TileType::JEWELLER,
            TileType::KEYGUILD,
            TileType::KEYMARKET,
            TileType::KEYTHEDRAL,
            TileType::MERCERSGUILD,
            TileType::SCHOLAR,
            TileType::SCRIBES,
            TileType::VILLAGEHALL,
            TileType::WATERMILL,
            TileType::WINDMILL => Season::WINTER,

            default => $this->unflip() !== null ? $this->unflip()->season() : null,
        };
    }

    public function isBoat(): bool
    {
        return match ($this) {
            TileType::BOAT1A,
            TileType::BOAT1B,
            TileType::BOAT2A,
            TileType::BOAT2B,
            TileType::BOAT3A,
            TileType::BOAT3B,
            TileType::BOAT4A,
            TileType::BOAT4B,
            TileType::KEYFLOWER,
            TileType::SEABASTION,
            TileType::SEABREESE,
            TileType::FLIPPER,
            TileType::INVINCIBLE,
            TileType::WHITEWIND,
            TileType::KEYFLOWER_U,
            TileType::SEABASTION_U,
            TileType::SEABREESE_U,
            TileType::FLIPPER_U,
            TileType::INVINCIBLE_U,
            TileType::WHITEWIND_U => true,
            default => false,
        };
    }

    public function isStandardBoat(): bool
    {
        return array_find(self::initialStandardBoats(6), fn($t) => $t == $this || $t->flip() == $this) !== null;
    }

    public function passengerCapacity(): int
    {
        return match ($this) {
            TileType::KEYFLOWER => 7,
            TileType::SEABASTION => 6,
            TileType::SEABREESE => 6,
            TileType::FLIPPER => 5,
            TileType::INVINCIBLE => 5,
            TileType::WHITEWIND => 4,

            TileType::KEYFLOWER_U => 3,
            TileType::SEABASTION_U => 0,
            TileType::SEABREESE_U => 4,
            TileType::FLIPPER_U => 2,
            TileType::INVINCIBLE_U => 3,
            TileType::WHITEWIND_U => 4,
            default => 0,
        };
    }

    public function cargoCapacity(): int
    {
        return match ($this) {
            TileType::KEYFLOWER => 1,
            TileType::SEABASTION => 2,
            TileType::SEABREESE => 1,
            TileType::FLIPPER => 2,
            TileType::INVINCIBLE => 1,
            TileType::WHITEWIND => 2,

            TileType::KEYFLOWER_U => 2,
            TileType::SEABASTION_U => 0,
            TileType::SEABREESE_U => 1,
            TileType::FLIPPER_U => 2,
            TileType::INVINCIBLE_U => 1,
            TileType::WHITEWIND_U => 1,
            default => 0,
        };
    }

    public function flipsIn(Season $season): bool
    {
        return match ($this) {
            TileType::KEYFLOWER => $season == Season::FALL,
            TileType::SEABASTION => $season == Season::WINTER,
            TileType::SEABREESE => $season == Season::SUMMER,
            TileType::FLIPPER => $season == Season::FALL,
            TileType::INVINCIBLE => $season == Season::FALL,
            TileType::WHITEWIND => $season == Season::FALL,
            default => false,
        };
    }

    /** @return list<int> */
    public function roads(): array
    {
        return match ($this) {
            TileType::BARN => [4, 5],
            TileType::BLACKSMITH => [1, 6],
            TileType::FAIR => [2, 3],
            TileType::FORGE => [4, 6],
            TileType::GOLDSMITH => [2, 5],
            TileType::INN => [2, 4],
            TileType::SAWMILL => [1, 4],
            TileType::SCULPTOR => [3, 6],
            TileType::STONEYARD => [1, 5],
            TileType::TIMBERYARD => [3, 5],
            TileType::WAINWRIGHT => [2, 6],
            TileType::WELL => [1, 3],

            TileType::HOME1 => [1, 2, 3, 5, 6],
            TileType::HOME2 => [1, 2, 3, 4, 6],
            TileType::HOME3 => [2, 3, 4, 5, 6],
            TileType::HOME4 => [1, 2, 3, 4, 5],
            TileType::HOME5 => [1, 3, 4, 5, 6],
            TileType::HOME6 => [1, 2, 4, 5, 6],

            TileType::ALEHOUSE => [1, 4, 5, 6],
            TileType::APPRENTICEHALL => [1, 2, 4, 5],
            TileType::GOLDMINE => [1, 3, 4, 6],
            TileType::KEYMINE => [1, 2, 3, 4],
            TileType::KEYSTONEQUARRY => [1, 2, 4, 6],
            TileType::KEYWOOD => [1, 2, 4, 5],
            TileType::MINER => [1, 3, 4, 5],
            TileType::PEDDLAR => [1, 2, 4, 6],
            TileType::QUARRYMAN => [1, 3, 4, 6],
            TileType::STABLE => [1, 3, 4, 5],
            TileType::WOODCUTTER => [3, 4, 5, 6],
            TileType::WORKSHOP => [1, 2, 3, 4],

            TileType::BREWER => [2, 5, 6],
            TileType::CARPENTER => [1, 2, 5],
            TileType::FARRIER => [3, 4, 6],
            TileType::HIRINGFAIR => [1, 3, 6],
            TileType::MASON => [3, 5, 6],
            TileType::SMELTER => [1, 4, 5],
            TileType::STORE => [1, 3, 4],
            TileType::TAVERN => [2, 3, 5],

            TileType::APOTHECARY => [5],
            TileType::CRAFTSMANSGUILD => [5],
            TileType::JEWELLER => [3],
            TileType::KEYGUILD => [2],
            TileType::KEYMARKET => [6],
            TileType::KEYTHEDRAL => [3],
            TileType::MERCERSGUILD => [4],
            TileType::SCHOLAR => [1],
            TileType::SCRIBES => [6],
            TileType::VILLAGEHALL => [1],
            TileType::WATERMILL => [2],
            TileType::WINDMILL => [4],

            TileType::BARN_U => [4, 5],
            TileType::BLACKSMITH_U => [1, 6],
            TileType::FAIR_U => [2, 3],
            TileType::FORGE_U => [4, 6],
            TileType::GOLDSMITH_U => [2, 5],
            TileType::INN_U => [2, 4],
            TileType::SAWMILL_U => [1, 4],
            TileType::SCULPTOR_U => [3, 6],
            TileType::STONEYARD_U => [1, 5],
            TileType::TIMBERYARD_U => [3, 5],
            TileType::WAINWRIGHT_U => [2, 6],
            TileType::WELL_U => [1, 3],

            TileType::HOME1_U => [1, 2, 3, 5, 6],
            TileType::HOME2_U => [1, 2, 3, 4, 6],
            TileType::HOME3_U => [2, 3, 4, 5, 6],
            TileType::HOME4_U => [1, 2, 3, 4, 5],
            TileType::HOME5_U => [1, 3, 4, 5, 6],
            TileType::HOME6_U => [1, 2, 4, 5, 6],

            TileType::ALEHOUSE_U => [1, 4, 5, 6],
            TileType::APPRENTICEHALL_U => [1, 2, 4, 5],
            TileType::GOLDMINE_U => [1, 3, 4, 6],
            TileType::KEYMINE_U => [1, 2, 3, 4],
            TileType::KEYSTONEQUARRY_U => [1, 2, 4, 6],
            TileType::KEYWOOD_U => [1, 2, 4, 5],
            TileType::MINER_U => [1, 3, 4, 5],
            TileType::PEDDLAR_U => [1, 2, 4, 6],
            TileType::QUARRYMAN_U => [1, 3, 4, 6],
            TileType::STABLE_U => [1, 3, 4, 5],
            TileType::WOODCUTTER_U => [3, 4, 5, 6],
            TileType::WORKSHOP_U => [1, 2, 3, 4],

            TileType::BREWER_U => [2, 5, 6],
            TileType::CARPENTER_U => [1, 2, 5],
            TileType::FARRIER_U => [3, 4, 6],
            TileType::HIRINGFAIR_U => [1, 3, 6],
            TileType::MASON_U => [3, 5, 6],
            TileType::SMELTER_U => [1, 4, 5],
            TileType::STORE_U => [1, 3, 4],
            TileType::TAVERN_U => [2, 3, 5],
            default => [],
        };
    }

    public function unflip(): ?TileType {
        /** @var array<string,TileType>|null */
        static $unflipped = null;
        if ($unflipped === null) {
            $unflipped = [];
            foreach (TileType::cases() as $other) {
                $f = $other->flip();
                if ($f !== null) {
                    $unflipped[$f->value] = $other;
                }
            }
        }
        return isset($unflipped[$this->value]) ? $unflipped[$this->value] : null;
    }

    public function translatedName(): string
    {
        return match ($this) {
            TileType::HOME1, TileType::HOME1_U => clienttranslate("Home 1"),
            TileType::HOME2, TileType::HOME2_U => clienttranslate("Home 2"),
            TileType::HOME3, TileType::HOME3_U => clienttranslate("Home 3"),
            TileType::HOME4, TileType::HOME4_U => clienttranslate("Home 4"),
            TileType::HOME5, TileType::HOME5_U => clienttranslate("Home 5"),
            TileType::HOME6, TileType::HOME6_U => clienttranslate("Home 6"),

            // spring
            TileType::ALEHOUSE, TileType::ALEHOUSE_U => clienttranslate("Alehouse"),
            TileType::APPRENTICEHALL, TileType::APPRENTICEHALL_U => clienttranslate("Apprentice hall"),
            TileType::GOLDMINE, TileType::GOLDMINE_U => clienttranslate("Goldmine"),
            TileType::KEYMINE, TileType::KEYMINE_U => clienttranslate("Keymine"),
            TileType::KEYSTONEQUARRY, TileType::KEYSTONEQUARRY_U => clienttranslate("Keystone quarry"),
            TileType::KEYWOOD, TileType::KEYWOOD_U => clienttranslate("Keywood"),
            TileType::MINER, TileType::MINER_U => clienttranslate("Miner"),
            TileType::PEDDLAR, TileType::PEDDLAR_U => clienttranslate("Peddlar"),
            TileType::QUARRYMAN, TileType::QUARRYMAN_U => clienttranslate("Quarryman"),
            TileType::STABLE, TileType::STABLE_U => clienttranslate("Stable"),
            TileType::WOODCUTTER, TileType::WOODCUTTER_U => clienttranslate("Woodcutter"),
            TileType::WORKSHOP, TileType::WORKSHOP_U => clienttranslate("Workshop"),

            // summer
            TileType::BREWER, TileType::BREWER_U => clienttranslate("Brewer"),
            TileType::CARPENTER, TileType::CARPENTER_U => clienttranslate("Carpenter"),
            TileType::FARRIER, TileType::FARRIER_U => clienttranslate("Farrier"),
            TileType::HIRINGFAIR, TileType::HIRINGFAIR_U => clienttranslate("Hiring fair"),
            TileType::MASON, TileType::MASON_U => clienttranslate("Mason"),
            TileType::SMELTER, TileType::SMELTER_U => clienttranslate("Smelter"),
            TileType::STORE, TileType::STORE_U => clienttranslate("Store"),
            TileType::TAVERN, TileType::TAVERN_U => clienttranslate("Tavern"),

            // fall tiles
            TileType::BARN, TileType::BARN_U => clienttranslate("Barn"),
            TileType::BLACKSMITH, TileType::BLACKSMITH_U => clienttranslate("Blacksmith"),
            TileType::FAIR, TileType::FAIR_U => clienttranslate("Fair"),
            TileType::FORGE, TileType::FORGE_U => clienttranslate("Forge"),
            TileType::GOLDSMITH, TileType::GOLDSMITH_U => clienttranslate("Goldsmith"),
            TileType::INN, TileType::INN_U => clienttranslate("Inn"),
            TileType::SAWMILL, TileType::SAWMILL_U => clienttranslate("Sawmill"),
            TileType::SCULPTOR, TileType::SCULPTOR_U => clienttranslate("Sculptor"),
            TileType::STONEYARD, TileType::STONEYARD_U => clienttranslate("Stoneyard"),
            TileType::TIMBERYARD, TileType::TIMBERYARD_U => clienttranslate("Timberyard"),
            TileType::WAINWRIGHT, TileType::WAINWRIGHT_U => clienttranslate("Wainwright"),
            TileType::WELL, TileType::WELL_U => clienttranslate("Well"),

            // winter
            TileType::APOTHECARY => clienttranslate("Apothecary"),
            TileType::CRAFTSMANSGUILD => clienttranslate("Craftsmen's guild"),
            TileType::JEWELLER => clienttranslate("Jeweller"),
            TileType::KEYGUILD => clienttranslate("Key guild"),
            TileType::KEYMARKET => clienttranslate("Key market"),
            TileType::KEYTHEDRAL => clienttranslate("Keythedral"),
            TileType::MERCERSGUILD => clienttranslate("Mercer's guild"),
            TileType::SCHOLAR => clienttranslate("Scholar"),
            TileType::SCRIBES => clienttranslate("Scribes"),
            TileType::VILLAGEHALL => clienttranslate("Village hall"),
            TileType::WATERMILL => clienttranslate("Watermill"),
            TileType::WINDMILL => clienttranslate("Windmill"),

            // boats
            TileType::BOAT1A => clienttranslate("Boat 1a"),
            TileType::BOAT1B => clienttranslate("Boat 1b"),
            TileType::BOAT2A => clienttranslate("Boat 2a"),
            TileType::BOAT2B => clienttranslate("Boat 2b"),
            TileType::BOAT3A => clienttranslate("Boat 3a"),
            TileType::BOAT3B => clienttranslate("Boat 3b"),
            TileType::BOAT4A => clienttranslate("Boat 4a"),
            TileType::BOAT4B => clienttranslate("Boat 4b"),
            TileType::KEYFLOWER, TileType::KEYFLOWER_U => clienttranslate("Keyflower"),
            TileType::SEABASTION, TileType::SEABASTION_U => clienttranslate("Sea Bastion"),
            TileType::SEABREESE, TileType::SEABREESE_U => clienttranslate("Sea Breese"),
            TileType::FLIPPER, TileType::FLIPPER_U => clienttranslate("Flipper"),
            TileType::INVINCIBLE, TileType::INVINCIBLE_U => clienttranslate("Invincible"),
            TileType::WHITEWIND, TileType::WHITEWIND_U => clienttranslate("Whitewind"),

            // FIXME: how to translate these?
            TileType::TURN_ORDER_2 => clienttranslate("Turn order 1a"),
            TileType::TURN_ORDER_3 => clienttranslate("Turn order 2a"),
            TileType::TURN_ORDER_36 => clienttranslate("Turn order 1b"),
            TileType::TURN_ORDER_4 => clienttranslate("Turn order 3a"),
            TileType::TURN_ORDER_46 => clienttranslate("Turn order 2b"),
            TileType::TURN_ORDER_56A => clienttranslate("Turn order 3b"),
            TileType::TURN_ORDER_56B => clienttranslate("Turn order 4"),
        };
    }

    /** @return array{summary:string,upgrade?:string,scoring?:string,notes?:string} */
    public function description(): array
    {
        return match ($this) {
            TileType::HOME1,
            TileType::HOME2,
            TileType::HOME3,
            TileType::HOME4,
            TileType::HOME5,
            TileType::HOME6 => [
                'summary' =>
                    clienttranslate('Allows transporting resources over 1 tile, then upgrading 1 tile.'),
                'upgrade' =>
                    clienttranslate('Can be upgraded for #1 stone and 1 wood# to allow transporting resources over 2 tiles, then upgrading 1 tile.'),
                'notes' =>
                    clienttranslate('Grants 5 ${points} when upgraded.')
            ],
            TileType::KEYFLOWER => [
                'summary' =>
                    clienttranslate('In spring and summer, brings 7 workers and 1 tool, in autumn, 3 workers and 2 tools.'),
                'notes' =>
                    clienttranslate('If acquired at the end of the game, grants 1 ${points} for each 1 transport capacity.')
            ],
            TileType::SEABASTION => [
                'summary' =>
                    clienttranslate('In spring, summer and autumn, brings 6 workers and 2 tools.'),
                'notes' =>
                    clienttranslate('If acquired at the end of the game, grants 1 ${points} for each tile which is connected by roads as part of a loop which connects back to the starting point without using the same road twice.'),
            ],
            TileType::SEABREESE => [
                'summary' =>
                    clienttranslate('In spring brings 6 workers and 1 tool, in summer and autumn, 4 workers and 1 tool.'),
                'notes' => clienttranslate('If acquired at the end of the game, grants 2, 4, 8, 16 or 32 ${points} for 1-5 river tiles connected to the home tile.')
            ],
            TileType::FLIPPER => [
                'summary' =>
                    clienttranslate('In spring and summer brings 5 workers and 2 tools, in autumn, 2 workers and 2 tools.'),
                'notes' => clienttranslate('If acquired at the end of the game allows the player to flip one tile without paying the upgrade cost and grants 2 ${points}.'),
            ],
            TileType::INVINCIBLE => [
                'summary' =>
                    clienttranslate('In spring and summer brings 5 workers and 1 tool, in autumn, 3 workers and 1 tool.'),
                'notes' =>
                    clienttranslate('If acquired at the end of the game grants 5 ${points}.'),
            ],
            /*
            TileType::WHITEWIND =>
            clienttranslate('In spring and summer brings 4 workers and 2 tools, in autumn, 4 workers and 1 tool.<hr/>If acquired at the end of the game grants $1$ for each worker the player has, that has not been scored on another tile.'),
            TileType::TURN_ORDER_2 =>
            clienttranslate('Determines player order when bidding for the boats.<hr/>At the end of winter, can be added to the player's village, granting $1$ for each neighboring tile.'),
            TileType::TURN_ORDER_3 =>
            clienttranslate('Determines player order when bidding for the boats.<hr/>At the end of winter, can be added to the player's village, granting $1$ for each neighboring tile.'),
            TileType::TURN_ORDER_36 =>
            clienttranslate('Determines player order when bidding for the boats.<hr/>At the end of winter, can be added to the player's village, granting $1$ for each neighboring tile.'),
            TileType::TURN_ORDER_4 =>
            clienttranslate('Determines player order when bidding for the boats. Gives the first player token to the auction winner.<hr/>At the end of winter, can be added to the player's village, granting $1$ for each neighboring tile.'),
            TileType::QUARRYMAN =>
            clienttranslate('Produces @2 stone@.<hr/>Can be upgraded for #1 pickaxe# to produce @1 gold and 1 stone@.<hr/>Grants $3$ when upgraded.'),
            TileType::STABLE =>
            clienttranslate('Allows transporting resources over 2 tiles, then upgrading 1 tile.<hr/>Can be upgraded for #1 wood and 1 iron# to allow transporting resources over 4 tiles, then upgrading 2 tiles.<hr/>Grants $5$ when upgraded.'),
            TileType::WOODCUTTER =>
            clienttranslate('Produces @2 wood@.<hr/>Can be upgraded for #1 saw# to produce @1 gold and 1 wood@.<hr/>Grants $3$ when upgraded.'),
            TileType::WORKSHOP =>
            clienttranslate('Produces @1 basic resource: iron, stone or wood@.<hr/>Can be upgraded for #2 resources# to produce @1 iron, 1 stone and 1 wood@.<hr/>Grants $3$ when upgraded.'),
            TileType::KEYSTONEQUARRY =>
            clienttranslate('Produces @1 stone@.<hr/>Can be upgraded for #1 pickaxe# to produce @3 stone@.<hr/>Grants $3$ when upgraded.'),
            TileType::KEYWOOD =>
            clienttranslate('Produces @1 wood@.<hr/>Can be upgraded for #1 saw# to produce @3 wood@.<hr/>Grants $3$ when upgraded.'),
            TileType::ALEHOUSE =>
            clienttranslate('Brings @1 random worker@.<hr/>Can be upgraded for #1 iron, 1 stone and 1 wood# to enable bringing @2 random workers@.'),
            TileType::APPRENTICEHALL =>
            clienttranslate('Produces @1 tool@.<hr/>Can be upgraded for #1 stone and 1 wood# to produce @2 tools@.<hr/>Grants $3$ when upgraded.'),
            TileType::GOLDMINE =>
            clienttranslate('Produces @1 gold@.<hr/>Can be upgraded for #1 iron and 1 stone# to produce @2 gold@.<hr/>Grants $3$ when upgraded.'),
            TileType::KEYMINE =>
            clienttranslate('Produces @1 iron@.<hr/>Can be upgraded for #1 anvil# to produce @3 iron@.<hr/>Grants $3$ when upgraded.'),
            TileType::MINER =>
            clienttranslate('Produces @2 iron@.<hr/>Can be upgraded for #1 anvil# to produce @1 gold and 1 iron@.<hr/>Grants $3$ when upgraded.'),
            TileType::PEDDLAR =>
            clienttranslate('Brings @1 green worker@ but ^1 yellow worker^ has to be discarded in exchange.<hr/>Can be upgraded for #1 gold# to enable bringing @2 green workers@.'),
            TileType::FARRIER =>
            clienttranslate('Allows transporting resources over 3 tiles, then upgrading 1 tile.<hr/>Can be upgraded for #2 resources# to allow transporting resources over 5 tiles, then upgrading 2 tiles.<hr/>Grants $5$ when upgraded.'),
            TileType::HIRINGFAIR =>
            clienttranslate('Produces @2 tools@ but ^1 tool^ is consumed in the process.<hr/>Can be upgraded for #1 wood and 1 iron# to produce @3 tools@.<hr/>Grants $5$ when upgraded.'),
            TileType::MASON =>
            clienttranslate('Produces @3 stone@ but ^1 pickaxe^ is consumed in the process.<hr/>Can be upgraded for #3 stone# to produce @3 gold@.<hr/>Grants $7$ when upgraded.'),
            TileType::SMELTER =>
            clienttranslate('Produces @3 iron@ but ^1 anvil^ is consumed in the process.<hr/>Can be upgraded for #3 iron# to produce @3 gold@.<hr/>Grants $7$ when upgraded.'),
            TileType::BOAT1A =>
            clienttranslate('When taking workers from the boats at the end of the summer and autumn, take 2 additional workers from the bag if available. When taking a boat tile at the end of the winter, take 2 workers from the bag (after all winning bids have been placed in the bag).'),
            TileType::BOAT2A =>
            clienttranslate('When transporting resources, the resources may be transported across fields as well as across roads. When placing tiles, edges do not need to match (including water). Only properly connected roads will be counted for scoring purposes.'),
            TileType::BOAT3A =>
            clienttranslate('When upgrading, any type of resource can be used in place of any other type of resource.'),
            TileType::BOAT4A =>
            clienttranslate('You can counter-bid another player's red,yellow or blue (but not green) bid with workers of another color (all your workers in that bid have to be of the same color though).'),
            TileType::BREWER =>
            clienttranslate('Brings @2 random workers@ but ^1 tool^ is consumed in the process.<hr/>Can be upgraded for #1 stone and 1 iron# to enable bringing @3 random workers@.<hr/>Grants $3$ when upgraded.'),
            TileType::CARPENTER =>
            clienttranslate('Produces @3 wood@ but ^1 saw^ is consumed in the process.<hr/>Can be upgraded for #3 wood# to produce @3 gold@.<hr/>Grants $7$ when upgraded.'),
            TileType::STORE =>
            clienttranslate('Brings @1 green worker@ but ^1 blue worker^ has to be discarded in exchange.<hr/>Can be upgraded for #1 gold# to enable bringing @2 green workers@.<hr/>Grants $3$ when upgraded.'),
            TileType::TAVERN =>
            clienttranslate('Brings @2 random workers@ but ^1 worker^ has to be discarded in exchange.<hr/>Can be upgraded for #1 iron, 1 stone and 1 wood# to enable bringing @3 random workers@.'),
            TileType::BARN =>
            clienttranslate('Grants $1$ for each resource assigned to this tile (the resource must be found on this tile).<hr/>Can be upgraded for #1 tool# to grant $2$ for each assigned resource.<hr/>Resources scored here cannot be scored elsewhere.'),
            TileType::BLACKSMITH =>
            clienttranslate('Grants $2$ for each iron assigned to this tile (the resource must be found on this tile).<hr/>Can be upgraded for #1 anvil# to grant $3$ for iron on this tile.<hr/>Resources scored here cannot be scored elsewhere.'),
            TileType::FAIR =>
            clienttranslate('Brings @1 green worker@ but ^1 red worker^ has to be discarded in exchange.<hr/>Can be upgraded for #1 gold# to enable bringing @2 green workers@.<hr/>Grants $5$ when upgraded.'),
            TileType::FORGE =>
            clienttranslate('Can be upgraded for #3 iron#.<hr/>Grants $3$ or $10$ when upgraded.'),
            TileType::GOLDSMITH =>
            clienttranslate('Can be upgraded for #2 gold#.<hr/>Grants $7$ when upgraded.'),
            TileType::INN =>
            clienttranslate('Brings @1 random worker@.<hr/>Can be upgraded for #1 tool and 1 resource# to enable bringing @3 random workers@.'),
            TileType::SAWMILL =>
            clienttranslate('Can be upgraded for #3 wood#.<hr/>Grants $3$ or $10$ when upgraded.'),
            TileType::SCULPTOR =>
            clienttranslate('Can be upgraded for #3 stone#.<hr/>Grants $3$ or $10$ when upgraded.'),
            TileType::STONEYARD =>
            clienttranslate('Grants $2$ for each stone assigned to this tile (the resource must be found on this tile).<hr/>Can be upgraded for #1 pickaxe# to grant $3$ for stone on this tile.<hr/>Resources scored here cannot be scored elsewhere.'),
            TileType::TIMBERYARD =>
            clienttranslate('Grants $2$ for each wood assigned to this tile (the resource must be found on this tile).<hr/>Can be upgraded for #1 saw# to grant $3$ for wood on this tile.<hr/>Resources scored here cannot be scored elsewhere.'),
            TileType::WAINWRIGHT =>
            clienttranslate('Allows transporting resources over 4 tiles, then upgrading 2 tiles.<hr/>Can be upgraded for #2 resources# to allow transporting resources over 6 tiles, then upgrading 2 tiles.<hr/>Grants $5$ when upgraded.'),
            TileType::WELL =>
            clienttranslate('Can be upgraded for #3 resources#.<hr/>Grants $3$ or $7$ when upgraded.'),
            TileType::APOTHECARY =>
            clienttranslate('Grants $3$ for each 5 workers assigned to this tile (workers played on the player's village in the last round can be used). <hr/>Workers scored here cannot be scored on another tile.'),
            TileType::CRAFTSMANSGUILD =>
            clienttranslate('Grants $3$ for each set of 1 red, 1 yellow and 1 blue worker assigned to this tile (workers played on the player's village in the last round can be used). <hr/>Workers scored here cannot be scored on another tile.'),
            TileType::JEWELLER =>
            clienttranslate('Grants $2$ for each 1 gold assigned to this tile (the gold can be found anywhere in the player's village). <hr/>Gold score here cannot also be scored separately or on another tile.'),
            TileType::KEYGUILD =>
            clienttranslate('Grants $10$ for each set of 5 tools assigned to this tile. <hr/>Tools scored here cannot be scored on another tile.'),
            TileType::KEYMARKET =>
            clienttranslate('Grants $2$ for each green worker assigned to this tile, (workers played on the player's village in the last round can be used). <hr/>Workers scored here cannot be scored on another tile.'),
            TileType::KEYTHEDRAL =>
            clienttranslate('<hr/>Grants $12$.'),
            TileType::MERCERSGUILD =>
            clienttranslate('Grants $5$ for each set of 1 wood, 1 iron, 1 stone assigned to this tile (the resources can be found anywhere in the player's village; gold can be used as substitute). <hr/>Resources scored here cannot be scored on another tile.'),
            TileType::SCHOLAR =>
            clienttranslate('Grants $3$ for each 1 tool assigned to this tile in a set of tools of the same type. <hr/>Tools scored here cannot be scored on another tile.'),
            TileType::SCRIBES =>
            clienttranslate('Grants $10$ for each set of 1 anvil, 1 pick and 1 saw assigned to this tile. <hr/>Tools scored here cannot be scored on another tile.'),
            TileType::VILLAGEHALL =>
            clienttranslate('Grants $1$ for each 1 worker in a set of workers of the same type assigned to this tile (workers played on the player's village in the last round can be used). <hr/>Workers scored here cannot be scored on another tile.'),
            TileType::WATERMILL =>
            clienttranslate('Grants $1$ for each 1 resource in a set of resources of the same type assigned to this tile (the resources can be found anywhere in the player's village). <hr/>Resources scored here cannot be scored on another tile.'),
            TileType::WINDMILL =>
            clienttranslate('Grants $5$ for each set of 5 resources of any type assigned to this tile (the resources can be found anywhere in the player's village). <hr/>Resources scored here cannot be scored on another tile.'),
            TileType::HOME1_U =>
            clienttranslate('Allows transporting resources over 2 tiles, then upgrading 1 tile.<hr/>Grants $5$.'),
            TileType::HOME2_U =>
            clienttranslate('Allows transporting resources over 2 tiles, then upgrading 1 tile.<hr/>Grants $5$.'),
            TileType::HOME3_U =>
            clienttranslate('Allows transporting resources over 2 tiles, then upgrading 1 tile.<hr/>Grants $5$.'),
            TileType::HOME4_U =>
            clienttranslate('Allows transporting resources over 2 tiles, then upgrading 1 tile.<hr/>Grants $5$.'),
            TileType::HOME5_U =>
            clienttranslate('Allows transporting resources over 2 tiles, then upgrading 1 tile.<hr/>Grants $5$.'),
            TileType::HOME6_U =>
            clienttranslate('Allows transporting resources over 2 tiles, then upgrading 1 tile.<hr/>Grants $5$.'),
            TileType::KEYFLOWER_U =>
            clienttranslate('In spring and summer, brings 7 workers and 1 tool, in autumn, 3 workers and 2 tools.<hr/>If acquired at the end of the game, grants $1$ for each 1 transport capacity.'),
            TileType::SEABASTION_U =>
            clienttranslate('In spring, summer and autumn, brings 6 workers and 2 tools.<hr/>If acquired at the end of the game, grants $1$ for each tile which is connected by roads as part of a loop which connects back to the starting point without using the same road twice.'),
            TileType::SEABREESE_U =>
            clienttranslate('In spring brings 6 workers and 1 tool, in summer and autumn, 4 workers and 1 tool.<hr/>If acquired at the end of the game, grants 2, 4, 8, 16 or 32 $$ for 1-5 river tiles connected to the home tile.'),
            TileType::FLIPPER_U =>
            clienttranslate('In spring and summer brings 5 workers and 2 tools, in autumn, 2 workers and 2 tools.<hr/>If acquired at the end of the game allows the player to flip one tile without paying the upgrade cost and grants $2$.'),
            TileType::INVINCIBLE_U =>
            clienttranslate('In spring and summer brings 5 workers and 1 tool, in autumn, 3 workers and 1 tool.<hr/>If acquired at the end of the game grants $5$.'),
            TileType::WHITEWIND_U =>
            clienttranslate('In spring and summer brings 4 workers and 2 tools, in autumn, 4 workers and 1 tool.<hr/>If acquired at the end of the game grants $1$ for each worker the player has, that has not been scored on another tile.'),
            TileType::TURN_ORDER_46 =>
            clienttranslate('Determines player order when bidding for the boats. Gives the first player token to the auction winner.<hr/>At the end of winter, can be added to the player's village, granting $1$ for each neighboring tile.'),
            TileType::TURN_ORDER_56A =>
            clienttranslate('Determines player order when bidding for the boats. Gives the first player token to the auction winner.<hr/>At the end of winter, can be added to the player's village, granting $1$ for each neighboring tile.'),
            TileType::TURN_ORDER_56B =>
            clienttranslate('Determines player order when bidding for the boats. Gives the first player token to the auction winner.<hr/>At the end of winter, can be added to the player's village, granting $1$ for each neighboring tile.'),
            TileType::QUARRYMAN_U =>
            clienttranslate('Produces @1 gold and 1 stone@.<hr/>Grants $3$.'),
            TileType::STABLE_U =>
            clienttranslate('Allows transporting resources over 4 tiles, then upgrading 2 tiles.<hr/>Grants $5$.'),
            TileType::WOODCUTTER_U =>
            clienttranslate('Produces @1 gold and 1 wood@.<hr/>Grants $3$.'),
            TileType::WORKSHOP_U =>
            clienttranslate('Produces @1 iron, 1 stone and 1 wood@.<hr/>Grants $3$.'),
            TileType::KEYSTONEQUARRY_U =>
            clienttranslate('Produces @3 stone@.<hr/>Grants $3$.'),
            TileType::KEYWOOD_U =>
            clienttranslate('Produces @3 wood@.<hr/>Grants $3$.'),
            TileType::ALEHOUSE_U =>
            clienttranslate('Brings @2 random workers@.'),
            TileType::APPRENTICEHALL_U =>
            clienttranslate('Produces @2 tools@.<hr/>Grants $3$.'),
            TileType::GOLDMINE_U =>
            clienttranslate('Produces @2 gold@.<hr/>Grants $3$.'),
            TileType::KEYMINE_U =>
            clienttranslate('Produces @3 iron@.<hr/>Grants $3$.'),
            TileType::MINER_U =>
            clienttranslate('Produces @1 gold and 1 iron@.<hr/>Grants $3$.'),
            TileType::PEDDLAR_U =>
            clienttranslate('Brings @2 green workers@ but ^1 yellow worker^ has to be discarded in exchange.'),
            TileType::FARRIER_U =>
            clienttranslate('Allows transporting resources over 5 tiles, then upgrading 2 tiles.<hr/>Grants $5$.'),
            TileType::HIRINGFAIR_U =>
            clienttranslate('Produces @3 tools@ but ^1 tool^ is consumed in the process.<hr/>Grants $5$.'),
            TileType::MASON_U =>
            clienttranslate('Produces @3 gold@ but ^1 pickaxe^ is consumed in the process.<hr/>Grants $7$.'),
            TileType::SMELTER_U =>
            clienttranslate('Produces @3 gold@ but ^1 anvil^ is consumed in the process.<hr/>Grants $7$.'),
            TileType::BOAT1B =>
            clienttranslate('When taking workers from the boats at the end of the summer and autumn and when taking a boat at the end of the winter, take one green worker from the supply if available.'),
            TileType::BOAT2B =>
            clienttranslate('All your transport and upgrade capacity is doubled. If you own the Keyflower boat at the end of the game, boat's score is doubled.'),
            TileType::BOAT3B =>
            clienttranslate('For end-game scoring purposes, you can score any resource in place of any other resource.'),
            TileType::BOAT4B =>
            clienttranslate('If a tile has been already bid for, or it has been used for production, you don't have to follow the color when placing workers on the tile.'),
            TileType::BREWER_U =>
            clienttranslate('Brings @3 random workers@ but ^1 tool^ is consumed in the process.<hr/>Grants $3$.'),
            TileType::CARPENTER_U =>
            clienttranslate('Produces @3 gold@ but ^1 saw^ is consumed in the process.<hr/>Grants $7$.'),
            TileType::STORE_U =>
            clienttranslate('Brings @2 green workers@ but ^1 blue worker^ has to be discarded in exchange.<hr/>Grants $3$.'),
            TileType::TAVERN_U =>
            clienttranslate('Brings @3 random workers@ but ^1 worker^ has to be discarded in exchange.'),
            TileType::BARN_U =>
            clienttranslate('Grants $2$ for each resource assigned to this tile (the resource must be found on this tile).<hr/>Resources scored here cannot be scored elsewhere.'),
            TileType::BLACKSMITH_U =>
            clienttranslate('Grants $3$ for each iron assigned to this tile (the resource must be found on this tile).<hr/>Resources scored here cannot be scored elsewhere.'),
            TileType::FAIR_U =>
            clienttranslate('Brings @2 green workers@ but ^1 red worker^ has to be discarded in exchange.<hr/>Grants $5$.'),
            TileType::FORGE_U =>
            clienttranslate('<hr/>Grants $10$.'),
            TileType::GOLDSMITH_U =>
            clienttranslate('<hr/>Grants $7$.'),
            TileType::INN_U =>
            clienttranslate('Brings @3 random workers@.'),
            TileType::SAWMILL_U =>
            clienttranslate('<hr/>Grants $10$.'),
            TileType::SCULPTOR_U =>
            clienttranslate('<hr/>Grants $10$.'),
            TileType::STONEYARD_U =>
            clienttranslate('Grants $3$ for each stone assigned to this tile (the resource must be found on this tile).<hr/>Resources scored here cannot be scored elsewhere.'),
            TileType::TIMBERYARD_U =>
            clienttranslate('Grants $3$ for each wood assigned to this tile (the resource must be found on this tile).<hr/>Resources scored here cannot be scored elsewhere.'),
            TileType::WAINWRIGHT_U =>
            clienttranslate('Allows transporting resources over 6 tiles, then upgrading 2 tiles.<hr/>Grants $5$.'),
            TileType::WELL_U =>
            clienttranslate('<hr/>Grants $7$.')
            */
            default => [
                'summary' => $this->value,
            ]
        };
    }
}
