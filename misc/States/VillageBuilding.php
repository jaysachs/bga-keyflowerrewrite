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
    44 => array("name" => "villageBuilding",
                "description" => clienttranslate('Village upgrades'),
                "type" => "game",
                "action" => "stVillageBuilding",
                "transitions" => array( "transportAvailable" => 45, "upgradeAvailable" => 47, "buildingComplete" => 50 )
    ),
    */
class VillageBuilding extends AbstractState
{
    function __construct(Game $game)
    {
        parent::__construct(
            game: $game,
            id: 44,
            type: StateType::GAME,
            description: clienttranslate('Village upgrades'),
            updateGameProgression: false,
        );
    }

    public function onEnteringState(): mixed {
        // Checks if there are transport / upgrade actions available and directs to the necessary states.

        $player_id = $this->game->getCurrentPlayerId();
        $sql = "SELECT player_transport transport, player_upgrade upgrade FROM player WHERE (player_id = '" . $player_id . "')";
        $building_data = $this->game->getObjectFromDB($sql);

        if ($building_data['transport'] > 0) {
            // Check if transport actions still exist, and any resources exist to transport.

            if ((sizeOf($this->game->getPlayerResources($player_id)) > 0)) {
                return VillageTransportA::class;
            }
            $this->notify->all('simpleNote', clienttranslate('${player_name} has no resources to transport'), array('player_id' => $player_id));
        }
        if (($building_data['upgrade'] > 0)) {

            // Check if upgrade actions still exist and upgradable tiles exist...
            if ((sizeOF($this->game->getUpgradeableTiles($player_id)) > 0)) {
                return VillageUpgrade::class;
            }
            $this->notify->all('simpleNote', clienttranslate('${player_name} cannot upgrade any tile'), array('player_id' => $player_id));
        }

        $this->game->finaliseMove();
        return EndTurn::class;
    }

}
