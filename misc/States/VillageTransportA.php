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
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\keyflowerrewrite\Game;

    /*
    45 => array("name" => "villageTransportA",
                "description" => clienttranslate('${actplayer} must select which resource to transport (${capacity} remaining moves)'),
                "descriptionmyturn" => clienttranslate('${you} must select which resource to transport (${capacity} remaining moves) or '),
                "type" => "activeplayer",
                "args" => "argVillageTransportA",
                "possibleactions" => array( "actSelectResource", "actEndTransport" ),
                "transitions" => array( "selectResource" => 46, "endTransport" => 44, "zombiePass" => 50)
    ),
    */

class VillageTransportA extends AbstractState
{
    function __construct(Game $game)
    {
        parent::__construct(
            game: $game,
            id: 45,
            type: StateType::ACTIVE_PLAYER,
            description: clienttranslate('${actplayer} must select which resource to transport (${capacity} remaining moves)'),
            descriptionMyTurn: clienttranslate('${you} must select which resource to transport (${capacity} remaining moves) or '),
            updateGameProgression: false,
        );
    }

    function getArgs(): mixed
    {
        /*
         "descriptionmyturn" => clienttranslate('${you} must select which resource to transport or '),
         "possibleactions" => array( "selectResource", "endTransport" ),
         */

        $player_id = $this->game->getActivePlayerId();
        $sql = "SELECT player_transport FROM player WHERE player_id='" . $player_id . "'";
        $transport = $this->game->getUniqueValueFromDB($sql);


        return array(
            'resources' => $this->game->getPlayerResources($this->game->getActivePlayerId()),
            'capacity' => $transport
        );
    }

    #[PossibleAction]
    function actEndTransport(int $active_player_id): mixed
    {
        $sql = "UPDATE player SET player_transport='0' WHERE (player_id = '" . $active_player_id . "')";
        $this->game->DbQuery($sql);
        return VillageBuilding::class;
    }

    #[PossibleAction]
    public function actSelectResource(int $active_player_id, int $rid): mixed
    {
        if (parent::doActSelectResource($active_player_id, $rid)) {
            // we shouldn't get here!
            throw new \Exception("supposed to go back from VillageTransportA?!?!");
        }
        return VillageTransportB::class;

    }
    function zombie(): mixed {
        return EndTurn::class;
    }
}
