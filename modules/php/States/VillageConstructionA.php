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
    75 => array("name" => "villageConstructionA",
                "description" => clienttranslate('Everyone must place new tiles in villages'),
                "descriptionmyturn" => clienttranslate('${you} must place your new tiles in your village'),
                "type" => "multipleactiveplayer",
                "args" => "argVillageConstructionA",
                "action" => "stVillageConstructionA",
                "possibleactions" => array(
                    "actConfirmPlaceTile",
                ),
                "transitions" => array( "endConstruction" => 74 )
    ),
    */
class VillageConstructionA extends AbstractState
{
    function __construct(Game $game)
    {
        parent::__construct(
            game: $game,
            id: 75,
            type: StateType::MULTIPLE_ACTIVE_PLAYER,
            description: clienttranslate('Everyone must place new tiles in villages'),
            descriptionMyTurn: clienttranslate('${you} must place your new tiles in your village'),
            updateGameProgression: false,
        );
    }

    function getArgs(): mixed
    {
        return array(
            'available' => self::getAvailableVillageTiles(),
            'destinations' => self::getPossibleDestinations()
        );
    }

    function onEnteringState(): void
    {
        // Active all players that have tiles to build

        $players_to_active = $this->game->getObjectListFromDB("SELECT DISTINCT t.tile_location_arg
            FROM market m
            LEFT JOIN tile t ON (m.market_tile_id = t.tile_id)
                 WHERE (t.tile_location='village') ", true);

        $this->gamestate->setPlayersMultiactive($players_to_active, SeasonEndB::class);
    }

    function getAvailableVillageTiles()
    {
        // return village tiles from the market....
        $sql = "SELECT m.market_tile_id tid, t.tile_location_arg player_id FROM market m LEFT JOIN tile t ON (m.market_tile_id = t.tile_id) WHERE (t.tile_location='village') ";
        return $this->game->getCollectionFromDb($sql, true);
    }

    function getPossibleDestinations($player_id = null)
    {

        if ($player_id === null) {
            $result = array();

            // Do it for ALL players
            $players = $this->game->loadPlayersBasicInfos();
            foreach ($players as $player_id => $player) {
                $result[$player_id] = self::getPossibleDestinations($player_id);
            }
            return $result;
        } else {
            $sql = "SELECT v.village_x x, v.village_y y, v.village_id
                    FROM village v
                    LEFT JOIN tile t ON (v.village_tile_id = t.tile_id)
                    WHERE (t.tile_location_arg='$player_id')";

            $all_tiles = $this->game->getDoubleKeyCollectionFromDB($sql, true);
            $result = array();
            $in_array = array();

            foreach ($all_tiles as $x => $tiles) {
                foreach ($tiles as $y => $tile_id) {
                    // calculate all the neighbouring cells and return if free spaces.
                    if (self::freeSpaceNew($x + 1, $y, $all_tiles) && ! isset($in_array[($x + 1) . '_' . $y])) {
                        array_push($result, array('x' => $x + 1, 'y' => $y));
                        $in_array[($x + 1) . '_' . $y] = true;
                    }
                    if (self::freeSpaceNew($x - 1, $y, $all_tiles) && ! isset($in_array[($x - 1) . '_' . $y])) {
                        array_push($result, array('x' => $x - 1, 'y' => $y));
                        $in_array[($x - 1) . '_' . $y] = true;
                    }
                    if (self::freeSpaceNew($x, $y + 1, $all_tiles) && ! isset($in_array[($x) . '_' . ($y + 1)])) {
                        array_push($result, array('x' => $x, 'y' => $y + 1));
                        $in_array[($x) . '_' . ($y + 1)] = true;
                    }
                    if (self::freeSpaceNew($x, $y - 1, $all_tiles) && ! isset($in_array[($x) . '_' . ($y - 1)])) {
                        array_push($result, array('x' => $x, 'y' => $y - 1));
                        $in_array[($x) . '_' . ($y - 1)] = true;
                    }
                    if (self::freeSpaceNew($x + 1, $y - 1, $all_tiles) && ! isset($in_array[($x + 1) . '_' . ($y - 1)])) {
                        array_push($result, array('x' => $x + 1, 'y' => $y - 1));
                        $in_array[($x + 1) . '_' . ($y - 1)] = true;
                    }
                    if (self::freeSpaceNew($x - 1, $y + 1, $all_tiles) && ! isset($in_array[($x - 1) . '_' . ($y + 1)])) {
                        array_push($result, array('x' => $x - 1, 'y' => $y + 1));
                        $in_array[($x - 1) . '_' . ($y + 1)] = true;
                    }
                }
            }

            return $result;
        }
    }

    function freeSpaceNew($x, $y, $tiles): bool
    {
        if (isset($tiles[$x])) {
            if (isset($tiles[$x][$y]))
                return false;
        }

        return true;
    }

    #[PossibleAction]
    function actConfirmPlaceTile(int $currentPlayerId, int $tid, int $x, int $y, int $orientation)
    {
        $player_id = $currentPlayerId;

        $village_tiles = self::getAvailableVillageTiles();
        if (!$village_tiles[$tid]) {
            throw new UserException("Invalid selection");
        }


        // need to check that coordinates are valid.
        $possibleDestinations = self::getPossibleDestinations($player_id);

        $found = false;
        foreach ($possibleDestinations as $pd) {
            if (($pd['x'] == $x) && ($pd['y'] == $y)) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            throw new UserException("Invalid destination");
        }

        $sql = "INSERT INTO village (village_x, village_y, village_tile_id) VALUES ('" . $x . "','" . $y . "','" . $tid . "')";
        $this->game->DbQuery($sql);

        $sql = "UPDATE tile SET tile_orientation='" . $orientation . "' WHERE tile_id='" . $tid . "'";
        $this->game->DbQuery($sql);

        $this->game->clearCache();

        if (!self::roadsMatch($tid)) {  //OR SPECIAL BOAT!

            if ($this->game->ownsTile($player_id, 34, 0)) {
                // Has boat 2a => can place tile even if road does not match.
            } else
                throw new UserException(clienttranslate("You must make sure all roads and river matches"));
        }

        $points = $this->game->getUniqueValueFromDB("SELECT victory_points FROM victory WHERE victory_tile_id='$tid' AND victory_tile_side='0' AND victory_type='standard'");

        if ($points === null)
            $points = 0;


        $sql = "UPDATE tile SET tile_type_arg='0', tile_scored='$points' WHERE tile_id='" . $tid . "'";
        $this->game->DbQuery($sql);
        $sql = "DELETE FROM market WHERE market_tile_id='" . $tid . "'";
        $this->game->DbQuery($sql);


        $args = array(
            'player_id' => $player_id,
            'tile_id' => $tid,
            'x' => $x,
            'y' => $y,
            'rotation' => $orientation,
            'tile' => $this->game->TILES[$tid]['name'],
            'newPossibleDestinations' => self::getPossibleDestinations($player_id),
            'i18n' => array('tile'),
            'points' => $points
        );

        if ($points == 0)
            $text = clienttranslate('${player_name} places ${tile}');
        else {
            $text = clienttranslate('${player_name} places ${tile} and scores ${points} points');
            $this->game->updateScore($player_id);
        }
        $this->notify->all('placeTile', $text, $args);


        // inactive player if no more tiles
        if ($this->game->getUniqueValueFromDB("SELECT COUNT(*) FROM market m LEFT JOIN tile t ON (m.market_tile_id = t.tile_id) WHERE (t.tile_location='village') AND (t.tile_location_arg='$player_id')") == 0) {
            $this->gamestate->setPlayerNonMultiactive($player_id, SeasonEndB::class);
        }
    }

        private function roadsMatch($tid)
    {
        /*
         Checks that roads match when added to a village. Only called when confirming the placement (at the moment).
         Roads must meet other roads or an empty tile. Fields/Water may meet.
         */

        $roadsMatch = true;

        $tile_coords = $this->game->getTileCoords($tid);

        //SPECIAL: Boat 2a (roads need not match).
        if ($this->game->ownsTile($tile_coords['pid'], 34, 0)) {
            return true;
        }


        for ($a = 1; $a <= 6; $a++) {

            // Grab adjacent tile
            $adjacent_coords = $this->game->getAdjacentTileCoords($tile_coords, $a);
            $adjacent_tid = $this->game->getTileId($adjacent_coords['x'], $adjacent_coords['y'], $tile_coords['pid']); // not yet multi friendly.

            // If it doesn't exist, no issue.
            if ($adjacent_tid !== null) {
                $current_edge = $this->game->getTileEdge($tid, $a);
                $adjacent_edge = $this->game->getTileEdge($adjacent_tid, 1 + (($a + 2) % 6));
                $roadsMatch = ($roadsMatch and ($current_edge == $adjacent_edge));
            }
        }

        return $roadsMatch;
    }

    function zombie(int $currentPlayerId): mixed {
        // FIXME: This doesn't follow the rules, as won tiles should be placed
        //   and available for other plaers to place in future turns.
        $sql = "SELECT market_tile_id FROM market m LEFT JOIN tile t ON (m.market_tile_id = t.tile_id) WHERE (t.tile_location='village') AND (t.tile_location_arg='" . $currentPlayerId . "')";

        $tiles = $this->game->getObjectListFromDB($sql, true);

        if (count($tiles) > 0) {
            $sql = "DELETE FROM market WHERE market_tile_id IN ('" . implode("','", $tiles) . "')";
            $this->game->DbQuery($sql);
        }

        $this->gamestate->setPlayerNonMultiactive($currentPlayerId, SeasonEndB::class);
        return null;
    }

}
