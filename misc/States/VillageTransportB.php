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
    46 => array("name" => "villageTransportB",
                "description" => clienttranslate('${actplayer} must select a destination for the ${resource} (${capacity} remaining moves)'),
                "descriptionmyturn" => clienttranslate('${you} must select a destination for the ${resource} (${capacity} remaining moves) or '),
                "type" => "activeplayer",
                "args" => "argVillageTransportB",
                "possibleactions" => array( "actTransportResource", "actChangeResource","actSelectResource" ),
                "transitions" => array( "transportResource" => 44, "changeResource" => 45, "zombiePass" => 50, "selectResource" => 46)
    ),
    */

class VillageTransportB extends AbstractState
{
    function __construct(Game $game)
    {
        parent::__construct(
            game: $game,
            id: 46,
            type: StateType::ACTIVE_PLAYER,
            description: clienttranslate('${actplayer} must select a destination for the ${resource} (${capacity} remaining moves)'),
            descriptionMyTurn: clienttranslate('${you} must select a destination for the ${resource} (${capacity} remaining moves) or '),
            updateGameProgression: false,
        );
    }

    function getArgs(): mixed
    {
        /*
         "descriptionmyturn" => clienttranslate('${you} must select a destination for the resource or '),
         "possibleactions" => array( "transportResource", "changeResource" ),
         */
        $player_id = $this->game->getActivePlayerId();
        $sql = "SELECT player_transport FROM player WHERE player_id='" . $player_id . "'";
        $transport = $this->game->getUniqueValueFromDB($sql);

        $selectedResources = $this->game->getSelectedResource();
        $firstSelectedResource = reset($selectedResources);

        $resource = '';
        foreach ($selectedResources as $ress) {
            $resource .= '<div class="imgtxt resource resource' . $ress['color'] . '"></div>';
        }

        return [
            'selection' => $selectedResources,
            'resources' => $this->game->getPlayerResources($this->game->getActivePlayerId()),
            'capacity' => $transport,
            'resource' => $resource,
            'resourceDestinations' => $this->getResourceDestinations($firstSelectedResource['id'], count($selectedResources))
        ];
    }

    #[PossibleAction]
    function actChangeResource(int $active_player_id)
    {
        $sql = "UPDATE resource SET card_type_arg='0' WHERE (card_type_arg='1') AND (card_location='tile') ";
        $this->game->DbQuery($sql);
        return VillageTransportA::class;
    }


    #[PossibleAction]
    function actTransportResource(int $active_player_id, int $tid)
    {
        $sql = "SELECT card_id, card_location_arg, card_type FROM resource WHERE (card_location='tile') AND (card_type_arg='1')";
        $resourcesInfos = $this->game->getObjectListFromDB($sql);

        foreach ($resourcesInfos as $resourceInfos) {
            $rid = $resourceInfos['card_id'];
            $tile_origin_id = $resourceInfos['card_location_arg'];
            $resource_type = $resourceInfos['card_type'];

            // Check valid destination
            $rds = $this->getResourceDestinations($rid);
            // var_dump( $rds );
            //die('ouet');
            $cost = null;
            $valid = false;
            foreach ($rds as $poss_dest) {
                if ($poss_dest['id'] == $tid) {
                    $cost = $poss_dest['cost'];
                }
            }

            if ($cost === null) {
                throw new UserException(clienttranslate("Your remaining transport capacity is not big enough to do this"));
            }

            //Need to update remaining transport movements.
            $sql = "SELECT player_transport FROM player WHERE player_id='" . $active_player_id . "'";
            $transport = $this->game->getUniqueValueFromDB($sql);
            $transport = $transport - $cost;

            $sql = "UPDATE player SET player_transport='" . $transport . "' WHERE player_id='" . $active_player_id . "'";
            $this->game->dbquery($sql);

            //Move the resource.
            $sql = "UPDATE resource SET card_type_arg='0', card_location_arg='" . $tid . "' WHERE card_id='" . $rid . "'";
            $this->game->DbQuery($sql);

            //Notify and move on to next state.
            $this->notify->all("transportResource", clienttranslate('${player_name} moves a ${resource} from ${tile1} to ${tile2} (cost ${cost})'), array(
                'i18n' => array('tile1', 'tile2'),
                'player_id' => $this->game->getActivePlayerId(),
                'from' => $tile_origin_id,
                'to' => $tid,
                'tile1' => $this->game->TILES[$tile_origin_id]['name'],
                'tile2' => $this->game->TILES[$tid]['name'],
                'resource' => '<div class="resource resource' . $resource_type . '"></div>',
                'resource_id' => $rid,
                'resource_type' => $resource_type,
                'cost' => $cost
            ));
        }

        return VillageBuilding::class;
    }

    #[PossibleAction]
    public function actSelectResource(int $active_player_id, int $rid): mixed
    {
        if (parent::doActSelectResource($active_player_id, $rid)) {
            return VillageTransportA::class;
        }
        return null;

    }

    function zombie(): mixed {
        return EndTurn::class;
    }

    private function getResourceDestinations(int $rid, int $ress_nbr = 1)
    {
        /*
         This method needs to identify any tiles within a distance of the transport limit that the resource can move to ALONG ROADS.

         Rough idea:
         1. Use the resource id to
         2. Grab the current tile id.
         3. Grab the transport limit.
         4. Create two arrays of destination-cost pairs (checked and unchecked).
         5. Put the home tile in the unchecked array with cost 0.
         6. While there are items in the unchecked array:
                i) find adjacent tiles by road (possibility of boat interference later here).
                ii) see if adjacent in checked array. If not, is it in unchecked? (update cost if cheaper), or add if not with cost +1.
                iii) transfer tile from unchecked to checked.
         7. Finally, remove the current tile from the checked array before returning (players can move resource away and back at a cost of 2 transport if they want to make useless moves).
         */

        // Get current Tile Id
        $sql = "SELECT card_location_arg FROM resource WHERE (card_id = '" . $rid . "')";
        $tid = $this->game->getUniqueValueFromDB($sql);
        $original_tid = $tid;

        // Get Transport Limit
        $player_id = $this->game->getActivePlayerId();
        $sql = "SELECT player_transport FROM player WHERE (player_id = '" . $player_id . "')";
        $transport = $this->game->getUniqueValueFromDB($sql);

        if ($ress_nbr > 1) {
            $transport = floor($transport / $ress_nbr);
        }


        // Get tiles id
        $sql = "SELECT tile_id, tile_orientation orient, tile_edgetype1 edge1, tile_edgetype2 edge2, tile_edgetype3 edge3, tile_edgetype4 edge4, tile_edgetype5 edge5, tile_edgetype5 edge5, tile_edgetype6 edge6
                FROM tile
                WHERE tile_location='village' AND tile_location_arg='$player_id'";
        $tiles_by_id = $this->game->getCollectionFromDB($sql);

        // Get tiles by positions
        $sql = "SELECT v.village_x x, v.village_y y, t.tile_id
                FROM village v
                LEFT JOIN tile t ON (v.village_tile_id = t.tile_id)
                WHERE (t.tile_location_arg='$player_id')";

        $xy_to_tiles = $this->game->getDoubleKeyCollectionFromDB($sql, true);

        // Create arrays for checking tiles.
        $unchecked = array();
        $checked = array();

        // Initialise with current tile.
        $unchecked[$tid] = array('id' => $tid, 'cost' => 0);

        // Check tiles until finished
        while (count($unchecked) > 0) {

            //A: Grab an unchecked tile and get its basic data (id, cost, coordinates).
            $currentTile = array_pop($unchecked);
            $ctid = $currentTile['id'];
            $cc = $currentTile['cost'];
            $adjacent = array();
            $tile_coord = $this->game->getTileCoords($ctid);
            $cx = $tile_coord['x'];
            $cy = $tile_coord['y'];

            //B: Check which edges have roads (unless boat interference!)
            $tile_data = $tiles_by_id[$ctid];

            for ($a = 1; $a <= 6; $a++) {
                // SPECIAL: Boat 2a (need not travel along roads)
                if (($tile_data['edge' . $a] == 1) || ($this->game->ownsTile($player_id, 34, 0))) {
                    array_push($adjacent, 1 + (($a - $tile_data['orient'] + 6) % 6));
                }
            }

            //C: See if a tile exists at the end of the road.
            //   (note: only if cost did not match current limit)
            if ($cc < $transport) {
                foreach ($adjacent as $adj) {
                    $x = $cx;
                    $y = $cy;
                    switch ($adj) {
                        case 1:
                            $y--;
                            break;
                        case 2:
                            $x++;
                            $y--;
                            break;
                        case 3:
                            $x++;
                            break;
                        case 4:
                            $y++;
                            break;
                        case 5:
                            $x--;
                            $y++;
                            break;
                        case 6:
                            $x--;
                        default:
                            break;
                    }

                    if (isset($xy_to_tiles[$x]) && isset($xy_to_tiles[$x][$y])) {
                        $adjacent_tid = $xy_to_tiles[$x][$y];

                        //D: Adjacent tile exists. See if it is in one of our arrays.

                        $found = false;

                        if (isset($checked[$adjacent_tid])) {
                            $found = true;
                            $ctile = $checked[$adjacent_tid];

                            $uCost = $ctile['cost'];
                            if ($uCost > $cc + 1) {
                                // E: need to update as cheaper path to this tile.
                                // => put it BACK to unchecked with a cheaper cost
                                $unchecked[$adjacent_tid] = array('id' => $adjacent_tid, 'cost' => ($cc + 1));
                                // ... and remove it from checked
                                unset($checked[$adjacent_tid]);
                            }
                        }


                        if (!$found) {

                            if (isset($unchecked[$adjacent_tid])) {
                                $found = true;
                                $ctile = $unchecked[$adjacent_tid];
                                $uCost = $ctile['cost'];
                                if ($uCost > $cc + 1) {
                                    // E: need to update as cheaper path to this tile.
                                    $unchecked[$adjacent_tid]['cost'] = $cc + 1;
                                }
                            }
                        }

                        //F: It is a new tile so add it to our unchecked array.
                        if (!$found) {
                            $unchecked[$adjacent_tid] = array('id' => $adjacent_tid, 'cost' => ($cc + 1));
                        }
                    }
                }
            } // endif: cost < transport


            //G: Transfer tile to checked pile.
            $checked[$currentTile['id']] = $currentTile;
        }

        //H: Remove the original tile before returning.
        unset($checked[$original_tid]);

        return $checked;
    }
}
