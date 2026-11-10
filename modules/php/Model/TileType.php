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

enum TileType: string {
    case HOME1 = "home1";
    case HOME2 = "home2";
    case HOME3 = "home3";
    case HOME4 = "home4";
    case HOME5 = "home5";
    case HOME6 = "home6";

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

    // summer
    case BREWER = "brewer";
    case CARPENTER = "carpenter";
    case FARRIER = "farrier";
    case HIRINGFAIR = "hiringfair";
    case MASON = "mason";
    case SMELTER = "smelter";
    case STORE = "store";
    case TAVERN = "tavern";

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
    case BOAT4B = "boat5b";
    case KEYFLOWER = "keyflower";
    case SEABASTION = "seabastion";
    case SEABREESE = "seabreese";
    case FLIPPER = "flipper";
    case INVINCIBLE = "invincible";
    case WHITEWIND = "whitewind";

    case START2 = "start2";
    case START3 = "start3";
    case START36 = "start36";
    case START4 = "start4";
    case START46 = "start46";
    case START56A = "start56a";
    case START56B = "start56b";

    /**
     * @return non-empty-list<TileType>
     */
    public static function homeTiles(): array {
        return [
            TileType::HOME1,
            TileType::HOME2,
            TileType::HOME3,
            TileType::HOME4,
            TileType::HOME5,
            TileType::HOME6,
        ];
    }

    public function usedInPlayerCount(int $playerCount): bool {
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

    /**
     * @return list<TileType>
     */
    public static function standardBoats(int $playerCount): array {
        return array_values(array_filter(TileType::cases(), fn ($t) => $t->isStandardBoat() && $t->usedInPlayerCount($playerCount)));
    }

    public function isStartPlayer(): bool {
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

    /**
     * @return list<TileType>
     */
    public static function startPlayerTiles(int $playerCount): array {
        return array_values(array_filter(TileType::cases(), fn ($t) => $t->isStartPlayer() && $t->usedInPlayerCount($playerCount)));
    }

    public function activationReward(bool $upgraded = false): ActivationReward {
        $homeReward = $upgraded ? ActivationReward::UPGRADE_2 : ActivationReward::UPGRADE_1;
        return match ($this) {
            TileType::FAIR => $upgraded ? ActivationReward::WORKER_GREEN_2 : ActivationReward::WORKER_GREEN,
            TileType::INN => $upgraded ? ActivationReward::WORKER_ANY_3 : ActivationReward::WORKER_ANY,
            TileType::WAINWRIGHT => $upgraded ? ActivationReward::UPGRADE_6 : ActivationReward::UPGRADE_4,
            TileType::HOME1 => $homeReward,
            TileType::HOME2 => $homeReward,
            TileType::HOME3 => $homeReward,
            TileType::HOME4 => $homeReward,
            TileType::HOME5 => $homeReward,
            TileType::HOME6 => $homeReward,
            TileType::ALEHOUSE => $upgraded ? ActivationReward::WORKER_ANY_2 : ActivationReward::WORKER_ANY,
            TileType::APPRENTICEHALL => $upgraded ? ActivationReward::SKILL_ANY_2 : ActivationReward::SKILL_ANY,
            TileType::GOLDMINE => $upgraded ? ActivationReward::GOLD_2 : ActivationReward::GOLD,
            TileType::KEYMINE => $upgraded ? ActivationReward::IRON_3 : ActivationReward::IRON,
            TileType::KEYSTONEQUARRY => $upgraded ? ActivationReward::STONE_3 : ActivationReward::STONE,
            TileType::KEYWOOD => $upgraded ? ActivationReward::WOOD_3 : ActivationReward::WOOD,
            TileType::MINER => $upgraded ? ActivationReward::IRON_GOLD : ActivationReward::IRON_2,
            TileType::PEDDLAR => $upgraded ? ActivationReward::WORKER_GREEN_2 : ActivationReward::WORKER_GREEN,
            TileType::QUARRYMAN => $upgraded ? ActivationReward::STONE_GOLD : ActivationReward::STONE_2,
            TileType::STABLE => $upgraded ? ActivationReward::UPGRADE_4 : ActivationReward::UPGRADE_2,
            TileType::WOODCUTTER => $upgraded ? ActivationReward::WOOD_GOLD : ActivationReward::WOOD_2,
            TileType::WORKSHOP => $upgraded ? ActivationReward::RESOURCE_3 : ActivationReward::RESOURCE_1,
            TileType::BREWER => $upgraded ? ActivationReward::WORKER_ANY_3 : ActivationReward::SKILL_ANY_2,
            TileType::CARPENTER => $upgraded ? ActivationReward::GOLD_3 : ActivationReward::WOOD_3,
            TileType::FARRIER => $upgraded ? ActivationReward::UPGRADE_5 : ActivationReward::UPGRADE_3,
            TileType::HIRINGFAIR => $upgraded ? ActivationReward::SKILL_ANY_3 : ActivationReward::SKILL_ANY_3,
            TileType::MASON => $upgraded ? ActivationReward::GOLD_3 : ActivationReward::STONE_3,
            TileType::SMELTER => $upgraded ? ActivationReward::GOLD_3 : ActivationReward::IRON_3,
            TileType::STORE => $upgraded ? ActivationReward::WORKER_GREEN_2 : ActivationReward::WORKER_GREEN,
            TileType::TAVERN => $upgraded ? ActivationReward::WORKER_ANY_3 : ActivationReward::WORKER_ANY_2,
            default => throw new InvalidArgumentException("tile type {$this->value} cannot be activated"), //ActivationCost::NONE,
        };
    }

    public function activationCost(): ActivationCost {
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
            default => throw new InvalidArgumentException("tile type {$this->value} cannot be activated"), //ActivationCost::NONE,
        };
    }

    /** @return non-empty-list<TileType> */
    public static function springTiles(): array {
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
    public static function summerTiles(): array {
        return [
            TileType::BOAT1A,
            TileType::BOAT1B,
            TileType::BOAT2A,
            TileType::BOAT2B,
            TileType::BOAT3A,
            TileType::BOAT3B,
            TileType::BOAT4A,
            TileType::BOAT4B,
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
    public static function fallTiles(): array {
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
    public static function winterTiles(): array {
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

    public function isHome(): bool {
        return match ($this) {
            TileType::HOME1,
            TileType::HOME2,
            TileType::HOME3,
            TileType::HOME4,
            TileType::HOME5,
            TileType::HOME6 => true,
            default => false,
        };
    }

    public function isSpring(): bool {
        return array_search($this, self::springTiles()) !== false;
    }

    public function isSummer(): bool {
        return array_search($this, self::summerTiles()) !== false;
    }

    public function isFall(): bool {
        return array_search($this, self::fallTiles()) !== false;
    }

    public function isWinter(): bool {
        return array_search($this, self::winterTiles()) !== false;
    }

    public function isBoat(): bool {
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
            TileType::WHITEWIND => true,
            default => false,
        };
    }

    public function isStandardBoat(): bool {
        return match ($this) {
            TileType::KEYFLOWER,
            TileType::SEABASTION,
            TileType::SEABREESE,
            TileType::FLIPPER,
            TileType::INVINCIBLE,
            TileType::WHITEWIND => true,
            default => false,
        };
    }

    public function passengerCapacity(bool $upgraded): int {
        return match ($this) {
            TileType::KEYFLOWER => $upgraded ? 3 : 7,
            TileType::SEABASTION => $upgraded ? 0 : 6,
            TileType::SEABREESE => $upgraded ? 4 : 6,
            TileType::FLIPPER => $upgraded ? 2 : 5,
            TileType::INVINCIBLE => $upgraded ? 3 : 5,
            TileType::WHITEWIND => 4,
            default => 0,
        };
    }

    public function cargoCapacity(bool $upgraded): int {
        return match ($this) {
            TileType::KEYFLOWER => $upgraded ? 2 : 1,
            TileType::SEABASTION => $upgraded ? 0 : 2,
            TileType::SEABREESE => 1,
            TileType::FLIPPER => 2,
            TileType::INVINCIBLE => 1,
            TileType::WHITEWIND => $upgraded ? 1 : 2,
            default => 0,
        };
    }

    public function flipsIn(Season $season): bool {
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
    public function roads(): array {
        return match ($this) {
            TileType::BARN => [4,5],
            TileType::BLACKSMITH => [1,6],
            TileType::FAIR => [2,3],
            TileType::FORGE => [4,6],
            TileType::GOLDSMITH => [2,5],
            TileType::INN => [2,4],
            TileType::SAWMILL => [1,4],
            TileType::SCULPTOR => [3,6],
            TileType::STONEYARD => [1,5],
            TileType::TIMBERYARD => [3,5],
            TileType::WAINWRIGHT => [2,6],
            TileType::WELL => [1,3],

            TileType::HOME1 => [1,2,3,5,6],
            TileType::HOME2 => [1,2,3,4,6],
            TileType::HOME3 => [2,3,4,5,6],
            TileType::HOME4 => [1,2,3,4,5],
            TileType::HOME5 => [1,3,4,5,6],
            TileType::HOME6 => [1,2,4,5,6],

            TileType::ALEHOUSE => [1,4,5,6],
            TileType::APPRENTICEHALL => [1,2,4,5],
            TileType::GOLDMINE => [1,3,4,6],
            TileType::KEYMINE => [1,2,3,4],
            TileType::KEYSTONEQUARRY => [1,2,4,6],
            TileType::KEYWOOD => [1,2,4,5],
            TileType::MINER => [1,3,4,5],
            TileType::PEDDLAR => [1,2,4,6],
            TileType::QUARRYMAN => [1,3,4,6],
            TileType::STABLE => [1,3,4,5],
            TileType::WOODCUTTER => [3,4,5,6],
            TileType::WORKSHOP => [1,2,3,4],

            TileType::BREWER => [2,5,6],
            TileType::CARPENTER => [1,2,5],
            TileType::FARRIER => [3,4,6],
            TileType::HIRINGFAIR => [1,3,6],
            TileType::MASON => [3,5,6],
            TileType::SMELTER => [1,4,5],
            TileType::STORE => [1,3,4],
            TileType::TAVERN => [2,3,5],

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

            default => [],
        };
    }
}
