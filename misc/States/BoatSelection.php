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
use Bga\GameFramework\UserException;
use Bga\Games\keyflowerrewrite\Game;

    /*
    72 => array("name" => "boatSelection",
                "description" => clienttranslate('${actplayer} must select a boat'),
                "descriptionmyturn" => clienttranslate('${you} must select a boat'),
                "type" => "activeplayer",
                "args" => "argBoatSelection",
                "possibleactions" => array( "actSelectBoat" ),
                "transitions" => array( "selectBoat" => 71, "flipper" => 172 )
    ),
    */

class BoatSelection extends AbstractState
{
    function __construct(Game $game)
    {
        parent::__construct(
            game: $game,
            id: 72,
            type: StateType::ACTIVE_PLAYER,
    		description: clienttranslate('${actplayer} must select a boat'),
    		descriptionMyTurn: clienttranslate('${you} must select a boat'),
            updateGameProgression: false,
        );
    }

    public function getArgs(): array
    {
        $type = 'boatZone';
        if ($this->game->getSeason() == 4) {
            $type = 'tile';
        }
        return [
            'possibleBoats' => $this->game->getRemainingBoats(), 'type' => $type
        ];
    }


    public function onEnteringState(int $activePlayerId): mixed {
        // The last player to choose has no choice left: give them the last
        // boat instead of waiting for them to click it.
        $boats = $this->game->getRemainingBoats();
        if (count($boats) == 1) {
            return $this->actSelectBoat($activePlayerId, (int) array_key_first($boats));
        }
        return null;
    }

	function zombie(int $player_id): mixed {
        // Select first boat
        $sql = "SELECT tile_id FROM tile WHERE (tile_type='boat' AND tile_location_arg='0') LIMIT 1";
        $tid = $this->game->getUniqueValueFromDB($sql);
        return $this->actSelectBoat($player_id, $tid);
    }

    #[PossibleAction]
    function actSelectBoat(int $active_player_id, int $tid): mixed
    {
        $season = $this->game->getSeason();

        //check boat hasn't already been cleared (e.g. tile_location_arg = 0) then distribute its cargo to the player, update the location_arg, and mark the player as having selected a boat player_plass=FALSE;

        $sql = "SELECT tile_location_arg FROM tile WHERE (tile_id = '{$tid}') and tile_type='boat'";
        $boat_data = $this->game->getUniqueValueFromDB($sql);
        if ($boat_data === null || $boat_data != 0) {
            throw new UserException(_("You must choose a boat tile"));
        }


        // if( $season != 4 ) // Boat 1a and 1b works also on Winter !!
        {
            // SPECIAL: Boat 1A (+2 workers)
            if ($this->game->ownsTile($active_player_id, 33, 0)) {
                $this->game->workers->pickCards(2, 'bag', $active_player_id);
            }

            // SPECIAL: Boat 1B (+green worker)
            if ($this->game->ownsTile($active_player_id, 33, 1)) {
                $this->game->workers->pickCards(1, 'table', $active_player_id);
            }
        }


        // Transfer the cargo to the player.
        if ($season == 4) {
            // Add the boat to the players market winnings.
            $sql = "UPDATE tile SET tile_location_arg = '" . $active_player_id . "', tile_location='village', tile_type='winter' WHERE tile_id = '" . $tid . "'";
            $this->game->DbQuery($sql);
            $sql = "INSERT INTO market (market_tile_id) VALUES ('" . $tid . "')";
            $this->game->DbQuery($sql);
            $sql = "DELETE FROM village WHERE (village_tile_id = '" . $tid . "')";
            $this->game->DbQuery($sql);
            $this->notify->all("selectedBoat", clienttranslate('${player_name} selects boat ${boat}'), array(
                'boat_id' => $tid,
                'type' => 'tile',
                'boat' => $this->game->TILES[$tid]['name'],
                'i18n' => array('boat'),
                'winter' => 1,
                'player_id' => $this->game->getActivePlayerId()
            ));
        } else {
            $workers_to_add = $this->game->getObjectListFromDB("SELECT card_id, card_type FROM worker WHERE (card_location='cargo') AND (card_location_arg='" . $tid . "')");
            $skills_to_add = $this->game->getObjectListFromDB("SELECT card_id, card_type FROM skill WHERE (card_location='cargo') AND (card_location_arg='" . $tid . "')");

            $sql = "UPDATE worker SET card_location='hand', card_location_arg='" . $active_player_id . "' WHERE (card_location='cargo') AND (card_location_arg='" . $tid . "')";
            $this->game->DbQuery($sql);
            $sql = "UPDATE skill SET card_location='hand', card_location_arg='" . $active_player_id . "' WHERE (card_location='cargo') AND (card_location_arg='" . $tid . "')";
            $this->game->DbQuery($sql);

            // Mark the cargo as taken.
            $sql = "UPDATE tile SET tile_location_arg = '" . $active_player_id . "' WHERE tile_id = '" . $tid . "'";
            $this->game->DbQuery($sql);
            $this->notify->player($active_player_id, 'handUpdate', '',  $this->game->getHand($active_player_id));
            $this->notify->all('handUpdateVisible', '', array(
                'workers_to_add' => $workers_to_add,
                'skills_to_add' => $skills_to_add,
                'origin' => $tid,
                'player_id' => $active_player_id
            ));

            // Move on to the next state
            $this->notify->all("selectedBoat", clienttranslate('${player_name} selects boat ${boat}'), array(
                'player_id' => $active_player_id,
                'boat_id' => $tid,
                'type' => 'boatZone',
                'boat' => $this->game->TILES[$tid]['name'],
                'i18n' => array('boat'),
                'winter' => 0
            ));
        }
        // Mark the player as having chosen a boat.
        $sql = "UPDATE player SET player_passed = FALSE WHERE player_id = '" . $active_player_id . "'";
        $this->game->DbQuery($sql);

        if ($tid == 10 && $season == 4) {
            // Particular case : Flipper  => may upgrade a tile
            return VillageUpgradeFree::class;
        } else {
            return TurnOrder::class;
        }
    }
 }
