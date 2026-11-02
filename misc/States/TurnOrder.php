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
    71 => array("name" => "turnOrder",
                "description" => clienttranslate('Arranging turn order'),
                "type" => "game",
                "action" => "stTurnOrder",
                "transitions" => array( "playerChosen" => 72, "boatsSelected" => 74 )
    ),
    */

class TurnOrder extends AbstractState
{
    function __construct(Game $game)
    {
        parent::__construct(
            game: $game,
            id: 71,
            type: StateType::GAME,
            description: clienttranslate('Arranging turn order'),
            updateGameProgression: false,
        );
    }

    public function onEnteringState(): mixed {

        //F: Turn order Tiles (inc. boats).


        // Need to see if there are bids on the turn order tiles. These get to choose first.
        // The starter counter 'might' move. The remaining players then choose based on order from start counter.
        // IF the start counter didn't move, it needs to move at end of this stage...

        $sql = "SELECT COUNT(*) FROM player WHERE player_passed=TRUE";
        $num = $this->game->getUniqueValueFromDB($sql);

        if ($num > 0) {
            // Players still to pick a boat. Find out who is next...
            $sql = "SELECT m.market_id mid, t.tile_id tid FROM market m LEFT JOIN tile t ON (m.market_tile_id = t.tile_id) WHERE (t.tile_type = 'turn') ORDER BY t.tile_id";
            $turn_tiles = $this->game->getCollectionFromDb($sql);

            // going to use the turn tile. simply: who won the bid? Return their bid, they are up next.
            foreach ($turn_tiles as $tt) {
                $sql = "SELECT DISTINCT card_location_arg FROM worker WHERE (card_location='bid') AND (card_type_arg='" . $tt['mid'] . "')";
                $pid = $this->game->getUniqueValueFromDB($sql);


                if ($pid !== null) {
                    // Return the bid to the bag.
                    $sql = "UPDATE worker SET card_location='bag', card_location_arg='0', card_type_arg='0' WHERE (card_type_arg='" . $tt['mid'] . "') AND (card_location='bid')";
                    $this->game->DbQuery($sql);

                    //Set as start player if correct turn tile.
                    if (($tt['tid'] == 16) || ($tt['tid'] == $this->game->getPlayerCount() + 11)) {
                        $sql = "UPDATE worker SET card_location_arg='" . $pid . "' WHERE card_type='5'";
                        $this->game->DbQuery($sql);
                        $sql = "UPDATE tile SET tile_location_arg='" . $pid . "' WHERE tile_id='" . $tt['tid'] . "'";
                        $this->game->DbQuery($sql);

                        $this->notify->all('firstPlayer', '', array('player_id' => $pid));
                    }

                    if ($this->game->getSeason() == 4) {
                        // Add the turn tile to the player
                        $sql = "UPDATE tile SET tile_type='winter', tile_location='village', tile_location_arg='" . $pid . "' WHERE tile_id='" . $tt['tid'] . "'";
                        $this->game->DbQuery($sql);
                    } else {
                        // Discard the used turn tile.
                        $sql = "DELETE FROM market WHERE market_id = '" . $tt['mid'] . "'";
                        $this->game->DbQuery($sql);
                    }

                    $bMustTakeBoat = $this->game->getUniqueValueFromDB("SELECT player_passed FROM player WHERE player_id='$pid'");

                    if ($bMustTakeBoat) {
                        $this->gamestate->changeActivePlayer($pid);
                        return BoatSelection::class;
                    }
                }
            }

            // ... no more turn tiles


            // select start player, and loop round until you find the next player who hasn't chosen...
            $this->gamestate->changeActivePlayer($this->game->getStarter());
            while ($this->hasChosenBoat($this->game->getActivePlayerId())) {
                $this->game->activeNextPlayer();
            }
            return BoatSelection::class;
        } else {

            // All players have picked.
            $this->gamestate->changeActivePlayer($this->game->getStarter());

            // Check if starter was changed.
            $sql = "SELECT tile_location_arg FROM tile WHERE tile_type='turn' AND tile_location_arg <> '0'";
            $turn_data = $this->game->getCollectionFromDb($sql);

            if ($this->game->getSeason() != 4) { // Note : in winter we do not pass the purple to the next player ...
                if (sizeOf($turn_data) == 0) {
                    $this->game->activeNextPlayer();
                    $sql = "UPDATE worker SET card_location_arg = '" . $this->game->getActivePlayerId() . "' WHERE card_type='5'";
                    $this->game->DbQuery($sql);

                    $this->notify->all('firstPlayer', clienttranslate('No one picked the first player (purple meeple) tile so it goes to the next player.'), array('player_id' => $this->game->getActivePlayerId()));
                }
            }

            // Reset markers ready for next season.
            $sql = "UPDATE tile SET tile_location_arg = '0' WHERE (tile_type = 'boat') OR (tile_type = 'turn')";
            $this->game->DbQuery($sql);
            return SeasonEndB::class;
        }
    }

    private function hasChosenBoat($pid): bool
    {
        $sql = "SELECT player_passed FROM player WHERE player_id = '" . $pid . "'";
        return (!$this->game->getUniqueValueFromDB($sql));
    }
}
