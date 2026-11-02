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

use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\StateType;
use Bga\GameFramework\UserException;
use Bga\Games\keyflowerrewrite\Game;

    /*
    85 => array("name" => "winterSelection",
                "description" => clienttranslate('All players must select their winter tiles'),
                "descriptionmyturn" => clienttranslate('You must select your winter tiles'),
                "type" => "multipleactiveplayer",
                "action" => "stWinterSelection",
                "args" => "argWinterSelection",
                "possibleactions" => array( "actSelectWinterTiles" ),
                "transitions" => array( "selectWinterTiles" => 10 )
    ),
    */
class WinterSelection extends AbstractState
{
    function __construct(Game $game)
    {
        parent::__construct(
            game: $game,
            id: 85,
            type: StateType::MULTIPLE_ACTIVE_PLAYER,
            description: clienttranslate('All players must select their winter tiles'),
            descriptionMyTurn: clienttranslate('You must select your winter tiles'),
            updateGameProgression: false,
        );
    }

    function onEnteringState(): void
    {
        $this->gamestate->setAllPlayersMultiactive();
    }

   #[PossibleAction]
    function actSelectWinterTiles(int $currentPlayerId, string $wids)
    {

        $wids = explode(',', $wids);
        $player_id = $currentPlayerId;
        $possible_wids = $this->game->getWinterTiles($player_id);

        foreach ($possible_wids as $pw) {
            if (!in_array($pw['id'], $wids)) {
                $sql = "UPDATE tile SET tile_type='discard' WHERE tile_id=('" . $pw['id'] . "')";
                $this->game->DbQuery($sql);
            } else {
                $sql = "UPDATE tile SET tile_location='table', tile_location_arg='0' WHERE tile_id=('" . $pw['id'] . "')";
                $this->game->DbQuery($sql);
            }
        }

        $this->gamestate->setPlayerNonMultiactive($player_id, SeasonSetup::class);
    }


    function zombie(): mixed {
        // FIXME: use common logic for zombie in multiplayer active states
        return EndTurn::class;
    }

}
