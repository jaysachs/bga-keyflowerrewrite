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

    case START2 = "start2";
    case START3 = "start3";
    case START36 = "start36";
    case START4 = "start4";
    case START46 = "start46";
    case START56A = "start56a";
    case START56B = "start56b";

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
            // TODO: should this throw an exception? We'd then need an "isFlippable"
            //  but that can be determined by "isStartingSpringTile()", "isStandardBoat" etc.
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
            TileType::START2 => $playerCount == 2,
            TileType::START3 => $playerCount == 3,
            TileType::START36 => $playerCount >= 3,
            TileType::START4 => $playerCount == 4,
            TileType::START46 => $playerCount >= 4,
            TileType::START56A => $playerCount >= 5,
            TileType::START56B => $playerCount >= 5,
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

    public function isStartPlayer(): bool
    {
        return match ($this) {
            TileType::START2 => true,
            TileType::START3 => true,
            TileType::START36 => true,
            TileType::START4 => true,
            TileType::START46 => true,
            TileType::START56A => true,
            TileType::START56B => true,
            default => false,
        };
    }

    /** @return list<TileType>  is actuall non-empty-list */
    public static function startPlayerTiles(int $playerCount): array
    {
        return array_values(array_filter(TileType::cases(), fn($t) => $t->isStartPlayer() && $t->usedInPlayerCount($playerCount)));
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

    /** @return non-empty-list<TileType> */
    public static function initialSpringTiles(): array
    {
        return [
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
            TileType::WORKSHOP,
        ];
    }

    /** @return non-empty-list<TileType> */
    public static function initialSummerTiles(): array
    {
        return [
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
            TileType::TAVERN,
        ];
    }

    /** @return non-empty-list<TileType> */
    public static function initialFallTiles(): array
    {
        return [
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
            TileType::WELL,
        ];
    }

    /** @return non-empty-list<TileType> */
    public static function initialWinterTiles(): array
    {
        return [
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
            TileType::WINDMILL,
        ];
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

    public function isSpring(): bool
    {
        return array_find(self::initialSpringTiles(), fn($t) => $t == $this || $t->flip() == $this) !== null;
    }

    public function isSummer(): bool
    {
        return array_find(self::initialSummerTiles(), fn($t) => $t == $this || $t->flip() == $this) !== null;
    }

    public function isFall(): bool
    {
        return array_find(self::initialFallTiles(), fn($t) => $t == $this || $t->flip() == $this) !== null;
    }

    public function isWinter(): bool
    {
        return array_search($this, self::initialWinterTiles()) !== false;
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
            TileType::START2 => clienttranslate("Start player 1a"),
            TileType::START3 => clienttranslate("Start player 2a"),
            TileType::START36 => clienttranslate("Start player 1b"),
            TileType::START4 => clienttranslate("Start player 3a"),
            TileType::START46 => clienttranslate("Start player 2b"),
            TileType::START56A => clienttranslate("Start player 3b"),
            TileType::START56B => clienttranslate("Start player 4"),
        };
    }
}
