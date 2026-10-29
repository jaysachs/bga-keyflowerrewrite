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
    80 => array("name" => "seasonEndC",
                "description" => clienttranslate('Ending the season'),
                "type" => "game",
                "action" => "stSeasonEndC",
                "transitions" => array( "nextSeason" => 10, "winterSetup" => 85, "endYear" => 90 )
    ),
    */

class SeasonEndC extends AbstractState
{
    function __construct(Game $game)
    {
        parent::__construct(
            game: $game,
            id: 80,
            type: StateType::GAME,
            description: clienttranslate('Ending the season'),
            updateGameProgression: false,
        );
    }

        // 1. affect this score to the scoring tile
    // 2. update player's total score
    // 3. notify
    private function updateScoreOnTile(int $tile_id, int $score, $player_id): void
    {
        $sql = "UPDATE tile SET tile_scored='$score' WHERE tile_id='$tile_id'";
        $this->game->DbQuery($sql);

        $this->notify->all('tileScored', '', array('tid' => $tile_id, 'score' => $score));

        $this->game->updateScore($player_id);
    }
        // Scores all winter boats, except White Wind that use worker (scored later)
    // (+adjacente tiles)
    private function scoreWinterBoats()
    {
        // Get all the village tiles.
        $sql = "SELECT t.tile_id tid, t.tile_side side, t.tile_location_arg pid FROM village v LEFT JOIN tile t ON (t.tile_id = v.village_tile_id) ORDER BY t.tile_location_arg";
        $village_data = $this->game->getCollectionFromDB($sql);

        $players = $this->game->loadPlayersBasicInfos();

        // Calculate the score for each tile.
        foreach ($village_data as $tile) {

            // Get tile data. The DB returns strings; updateScoreOnTile() takes ints.
            $tid = intval($tile['tid']);
            $side = $tile['side'];
            $pid = intval($tile['pid']);

            // Get the victory conditions for the tile.
            $sql = "SELECT * FROM victory WHERE (victory_tile_id = '" . $tid . "') AND (victory_tile_side = '" . $side . "')";
            $victory_data = $this->game->getCollectionFromDB($sql);

            // Work out a score for each victory condition that may exist.
            foreach ($victory_data as $vd) {
                $score = 0;
                $vp = $vd['victory_points'];
                // Need to do different things to get a score for each type.
                switch ($vd['victory_type']) {

                    // VP for each adjacent tile
                    case 'adjacent':
                        $score = $this->game->adjacentCount($tid) * $vp;
                        break;
                    // Sea breeze: VP for boats connected to home tile.
                    case 'boat':
                        $score = $this->getConnectedBoatScore($pid);
                        break;
                    // Sea bastion: VP for longest loop
                    case 'loop':
                        $score =  $this->getLongestLoop($pid) * $vp;
                        break;
                    // Keyflower
                    case 'transport':
                        $score = $this->game->getTransportCapacity($pid) * $vp;
                        break;
                    default:
                        break;
                }
                if ($score > 0) {
                    $this->updateScoreOnTile($tid, $score, $pid);
                    $this->notify->all('simpleNote', clienttranslate('${player_name} scores ${points} with ${tile}'), array(
                        'player_name' => $players[$pid]['player_name'],
                        'points' => $score,
                        'tile' => $this->game->TILES[$tid]['name'],
                        'i18n' => array('tile')
                    ));
                }
            }
        }
    }

    public function onEnteringState(): mixed {
        // Move on to the next season
        $currentSeason = $this->game->getSeason();
        switch ($currentSeason) {
            case 3:
                // Need to set up for winter.
                return WinterSelection::class;
            case 4:
                // End of the year.

                // Score all winter boats first (they are not related to resource/worker/skills management)
                $this->scoreWinterBoats();
                $this->game->calculateAndUpdateScoreOnAllTiles();

                return YearEnd::class;
            default:
                return SeasonSetup::class;
        }
    }

    private function getConnectedBoatScore($pid): int
    {

        // Get home tile id.
        $sql = "SELECT tile_id tid,  tile_orientation orient, tile_edgetype1 edge1, tile_edgetype2 edge2, tile_edgetype3 edge3, tile_edgetype4 edge4, tile_edgetype5 edge5, tile_edgetype6 edge6 FROM tile WHERE (tile_type='home') AND (tile_location_arg='" . $pid . "')";
        $tile_data = $this->game->getObjectFromDB($sql);

        $direction = 0;
        // Get the water direction
        for ($a = 1; $a <= 6; $a++) {
            if ($tile_data['edge' . $a] == 2) {
                $direction = 1 + (($a - $tile_data['orient'] + 6) % 6);
            }
        }

        $tid = $tile_data['tid'];
        $tile_coords = $this->game->getTileCoords($tid);

        $count = 0;
        $stop = false;

        // Check for adjacent tiles.
        while ($tid !== null) {
            // Grab adjacent tile

            $tile_coords = $this->game->getAdjacentTileCoords($tile_coords, $direction);
            $tid = $this->game->getTileId($tile_coords['x'], $tile_coords['y'], $pid);

            // Due to boat 2a, we should check tiles are boat tiles and match river direction.
            if (($tid !== null) && (!$stop)) {

                $adjacent_edge = $this->game->getTileEdge($tid, ($direction + 3) % 6);
                if ($adjacent_edge == 2) { // must be river
                    $count++;
                } else {
                    $stop = true; // Stop scoring as soon as break in the river.
                }
            }
        }

        // Not sure if this needs capping at 32 for expansions, but should be okay for standard (4 summer + 1 = max 5 boats).
        $score = 0;
        if ($count > 0) {
            $score = pow(2, $count);
        }

        return $score;
    }

    function getLongestLoop($pid)
    {
        // Calculate the longest loop of road for the given player.

        // Start at each tile.
        // Link to each neighbour by visiting roads. (need to keep track of used 'roads').


        $sql = "SELECT tile_id FROM tile WHERE (tile_location='village') AND (tile_location_arg='" . $pid . "')";
        $player_tiles = $this->game->getCollectionFromDB($sql);

        $length = 0;
        $best_loop = array();

        foreach ($player_tiles as $pt) {
            $path = array();
            array_push($path, $pt['tile_id']);
            $loop = $this->extendToLongestLoop($path, $pid);

            if (count(array_unique($loop)) > $length)
                $best_loop = $loop;

            $length = max($length, count(array_unique($loop)));
        }

        return $length;
    }

    function extendToLongestLoop(array $path, $pid)
    {
        // Path is the list of tids that is forming the path.

        // for each adjacent tile, grab the longest loop extending from that.
        // return the one with the longest length.

        $start = $path[0];

        $tid = $path[sizeOf($path) - 1];
        $tile_coords = $this->game->getTileCoords($tid);

        $best_loop = array();
        $best_loop_length = 0;
        //var_dump( implode( ',',$path ) );

        $roads_already_used = array();
        $previous = null;
        foreach ($path as $tile_id) {
            if ($previous !== null)
                $roads_already_used[] = min($previous, $tile_id) . '-' . max($previous, $tile_id);

            $previous = $tile_id;
        }

        for ($a = 1; $a <= 6; $a++) {

            $currentpath = $path;

            // needs to be a road.
            if ($this->game->getTileEdge($tid, $a) == 1) {

                // Grab adjacent tile
                $adjacent_coords = $this->game->getAdjacentTileCoords($tile_coords, $a);
                $adjacent_tid = $this->game->getTileId($adjacent_coords['x'], $adjacent_coords['y'], $tile_coords['pid']);

                $road_used = min($tid, $adjacent_tid) . '-' . max($tid, $adjacent_tid);

                // If it doesn't exist, no issue, and ignore other player's tiles.
                // need to check that this path hasn't already been covered.. (either forwards or backwards).
                if (($adjacent_tid !== null) && ($this->game->ownsTile($pid, $adjacent_tid))) {

                    $opposite = $a + 3;
                    if ($opposite > 6)
                        $opposite -= 6;

                    if ($this->game->getTileEdge($adjacent_tid, $opposite) == 1) {
                        // This is not a loop, so try to complete the loop first
                        if (!in_array($road_used, $roads_already_used)) {

                            $my_loop = array();

                            if ($adjacent_tid != $start) {

                                // add move to path.
                                array_push($currentpath, $adjacent_tid);
                                $my_loop = $this->extendToLongestLoop($currentpath, $pid);
                            } else {
                                // I found a complete loop !
                                $my_loop = $currentpath;
                            }

                            $loop_length = count(array_unique($my_loop));
                            if ($loop_length > $best_loop_length && $loop_length >= 3) {
                                $best_loop = $my_loop;
                                $best_loop_length = count(array_unique($best_loop));
                            }
                        }
                    } else {
                        /*var_dump( $tid );
                    var_dump( $a );
                    var_dump( $adjacent_tid );
                        var_dump( $opposite );
                        die('ok');*/
                    }
                }
            }
        }

        return $best_loop;
    }

}
