<?php

/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * babylonia implementation : © Jay Sachs <vagabond@covariant.org>
 *
 * Copyright 2025 Jay Sachs <vagabond@covariant.org>
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

namespace Bga\Games\keyflowerrewrite\States;

use Bga\GameFramework\StateType;
use Bga\Games\keyflowerrewrite\Game;


    /*
    48 => array("name" => "villageUpgradePayment",
                "description" => clienttranslate('Village upgrades'),
                "type" => "game",
                "action" => "stVillageUpgradePayment",
                "transitions" => array( "payForUpgrade" => 44, "paymentChoice" => 49 )
    ),
    */
class VillageUpgradePayment extends AbstractState
{
    function __construct(Game $game)
    {
        parent::__construct(
            game: $game,
            id: 48,
            type: StateType::GAME,
            description: clienttranslate('Village upgrades'),
            updateGameProgression: false,
        );
    }

    public function onEnteringState(): mixed {
        // Checks if the village Upgrade payment has a degree of choice and either makes it automatically, or passes to the choice game state.

        $player_id = intval($this->game->getCurrentPlayerId());

        $sql = "SELECT tile_id FROM tile WHERE (tile_type_arg='1') AND (tile_location='village') AND (tile_location_arg='" . $player_id . "')";
        $tid = $this->game->getUniqueValueFromDB($sql);  // is this unique?!

        $payment_options = $this->game->getUpgradeCostOptions($tid);
        if (sizeOf($payment_options) > 1) {
            return VillageUpgradeChoice::class;
        }
        $this->game->payForTileUpgrade($player_id, $payment_options);
        return VillageBuilding::class;
    }

}
