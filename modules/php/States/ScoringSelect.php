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

use Bga\GameFramework\Actions\Types\IntArrayParam;
use Bga\GameFramework\StateType;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\UserException;
use Bga\Games\keyflowerrewrite\Game;

    /*
    91 => array("name" => "scoringSelect",
                "description" => clienttranslate('Everyone must move resources and/or assign workers and skills to tiles for the scoring'),
                "descriptionmyturn" => clienttranslate('${you} must move resources and/or assign workers and skills to tiles for the scoring.'),
                "type" => "multipleactiveplayer",
               // "args" => "argScoringSelect",
                "action" => "stScoringSelect",
                "possibleactions" => array( "actPlaceWorkersWithLocation", "actScoringAssign", "actFinishArranging", "actConvertPurple" ),
                "transitions" => array( "finishArranging" => 90 )
    ),
    */

class ScoringSelect extends AbstractState
{
    function __construct(Game $game)
    {
        parent::__construct(
            game: $game,
            id: 91,
            type: StateType::MULTIPLE_ACTIVE_PLAYER,
    		description: clienttranslate('Everyone must move resources and/or assign workers and skills to tiles for the scoring'),
    		descriptionMyTurn: clienttranslate('${you} must move resources and/or assign workers and skills to tiles for the scoring.'),
            updateGameProgression: false,
        );
    }

    public function getArgs(): array
    {
        return [
            'resources' => $this->getResourcesForScoringMove()
        ];
    }

    public function onEnteringState()
    {
        $this->gamestate->setAllPlayersMultiactive();
    }

    private function getResourcesForScoringMove()
    {
        // Returns an array of all resources, skills and workers that can be moved to a scoring tile.

        $result = array();

        $player_id = $this->game->getCurrentPlayerId();

        $sql = "SELECT r.card_id FROM resource r LEFT JOIN tile t ON (r.card_location_arg = t.tile_id) WHERE (r.card_location='tile') AND (t.tile_location_arg='" . $player_id . "')";
        $resources = $this->game->getCollectionFromDB($sql);

        $workertypes = array();
        $worker_data = $this->game->getHandData('worker', $this->game->getCurrentPlayerId());
        for ($a = 1; $a <= 5; $a++) {
            if (isset($worker_data[$a])) {
                array_push($workertypes, $a);
            }
        }

        $skilltypes = array();
        $skill_data = $this->game->getHandData('skill', $this->game->getCurrentPlayerId());
        for ($a = 1; $a < 4; $a++) {
            if (isset($skill_data[$a])) {
                array_push($skilltypes, $a);
            }
        }

        return array('resources' => $resources, 'skills' => $skilltypes, 'workers' => $workertypes);
    }

	function zombie(int $player_id): mixed {
        // Mark the player as having completed scoring choices.
        $sql = "UPDATE player SET player_passed = TRUE WHERE player_id = '" . $player_id . "'";
        $this->game->DbQuery($sql);
        $this->multiactiveZombieFinish($player_id);
        return null;
    }

    #[PossibleAction]
    public function actPlaceWorkersWithLocation(string $type, int $id, int $color_id, int $count, #[IntArrayParam()] ?array $colors, #[IntArrayParam()] ?array $counts, #[IntArrayParam()] ?array $outbids): mixed
    {
        return $this->doPlaceWorkersWithLocation(true, intval($this->game->getCurrentPlayerId()), $type, $id, $color_id, $count, $colors ?? [], $counts ?? [], $outbids ?? []);
    }

    #[PossibleAction]
    function actFinishArranging()
    {
        $player_id = intval($this->game->getCurrentPlayerId()); // Note: multiplayer

        $sql = "UPDATE player SET player_passed = TRUE WHERE player_id = '" . $player_id . "'";
        $this->game->DbQuery($sql);

        $this->updateFinalStats($player_id);

        $this->gamestate->setPlayerNonMultiactive($player_id, YearEnd::class);
    }

    #[PossibleAction]
    function actConvertPurple(string $to, int $currentPlayerId)
    {
        $this->game->checkAction("actConvertPurple");

        // Check this player is the first player
        $player_id = $currentPlayerId;
        $first_player = $this->game->getUniqueValueFromDB("SELECT card_location_arg FROM worker WHERE card_type='5'");

        if ($player_id != $first_player)
            throw new UserException("You are not the first player");

        $type = substr($to, 1, 1);
        $category = '';
        $resources_to_add = [];

        if (substr($to, 0, 1) == 'w') {
            // Worker
            if ($type == 1)
                $id = $this->game->produceGood(1, null, $player_id);
            else if ($type == 2)
                $id = $this->game->produceGood(2, null, $player_id);
            else if ($type == 3)
                $id = $this->game->produceGood(4, null, $player_id);
            else if ($type == 4)
                $id = $this->game->produceGood(8, null, $player_id);

            $category = 'worker';
        } else if (substr($to, 0, 1) == 's') {
            // Skill
            if ($type == 1)
                $id = $this->game->produceGood(16, null, $player_id);
            else if ($type == 2)
                $id = $this->game->produceGood(32, null, $player_id);
            else if ($type == 3)
                $id = $this->game->produceGood(64, null, $player_id);

            $category = 'skill';
        } else if (substr($to, 0, 1) == 'r') {
            // Resource (we must choose the best location for it)
            $sql = "SELECT tile_id tid FROM tile WHERE (tile_type = 'home') AND (tile_location_arg='$player_id')";
            $home_tile = $this->game->getUniqueValueFromDB($sql);
            $best_location = $home_tile;
            $best_location_value = 0;

            $to_produce = 0;
            if ($this->game->ownsTile($player_id, 41)) // Barn
            {
                $best_location = 41;
                $best_location_value = 1;
                if ($this->game->ownsTile($player_id, 41, 1))
                    $best_location_value = 3;
            }


            if ($type == 1) {
                $to_produce = 128;

                if ($this->game->ownsTile($player_id, 42)) // Blacksmith
                {
                    if ($this->game->ownsTile($player_id, 42, 1)) {
                        $best_location = 42;
                        $best_location_value = 3;
                    } else if ($best_location_value < 2) {
                        $best_location = 42;
                        $best_location_value = 2;
                    }
                }
            } else if ($type == 2) {
                $to_produce = 256;

                if ($this->game->ownsTile($player_id, 49)) // Stone yard
                {
                    if ($this->game->ownsTile($player_id, 49, 1)) {
                        $best_location = 49;
                        $best_location_value = 3;
                    } else if ($best_location_value < 2) {
                        $best_location = 49;
                        $best_location_value = 2;
                    }
                }
            } else if ($type == 3) {
                $to_produce = 512;

                if ($this->game->ownsTile($player_id, 50)) // Timber yard
                {
                    if ($this->game->ownsTile($player_id, 50, 1)) {
                        $best_location = 50;
                        $best_location_value = 3;
                    } else if ($best_location_value < 2) {
                        $best_location = 50;
                        $best_location_value = 2;
                    }
                }
            } else if ($type == 4) {
                $to_produce = 1024;

                if ($this->game->ownsTile($player_id, 42)) // Blacksmith
                {
                    if ($this->game->ownsTile($player_id, 42, 1)) {
                        $best_location = 42;
                        $best_location_value = 3;
                    } else if ($best_location_value < 2) {
                        $best_location = 42;
                        $best_location_value = 2;
                    }
                }
                if ($this->game->ownsTile($player_id, 49)) // Stone yard
                {
                    if ($this->game->ownsTile($player_id, 49, 1)) {
                        $best_location = 49;
                        $best_location_value = 3;
                    } else if ($best_location_value < 2) {
                        $best_location = 49;
                        $best_location_value = 2;
                    }
                }
                if ($this->game->ownsTile($player_id, 50)) // Timber yard
                {
                    if ($this->game->ownsTile($player_id, 50, 1)) {
                        $best_location = 50;
                        $best_location_value = 3;
                    } else if ($best_location_value < 2) {
                        $best_location = 50;
                        $best_location_value = 2;
                    }
                }
            } // else if this is a resource

            // Now, produce
            $id = $this->game->produceGood($to_produce, $best_location, $player_id);

            $category = 'resource';


            $resources_to_add = array($id => $type);
        }


        $html_resources = '<div class="' . $category . '_mini ' . $category . '_mini' . $type . ' nofloat"></div> ';
        $this->notify->all("simpleNote", clienttranslate('${player_name} converts its first player meeple into a ${ress}'), array(
            'player_id' => $player_id,
            'ress' => $html_resources

        ));


        if ($category == 'resource') {
            $this->notify->all("production", '', array(
                'player_id' => $player_id,
                'ress' => $html_resources,
                'to_add' => $resources_to_add,
                'source' => $home_tile,
                'destination' => $best_location

            ));
        }

        $this->notify->player($player_id, 'handUpdate', '', $this->game->getHand($player_id));

        // Remove purple
        $sql = "UPDATE worker SET card_location='discarded',card_location_arg='0' WHERE card_type='5'";
        $this->game->DbQuery($sql);

        $this->notify->all('firstPlayer', '', array('player_id' => null));

        $this->game->calculateAndUpdateScoreOnAllTiles();
    }

    #[PossibleAction]
        function actScoringAssign(int $currentPlayerId, string $type, string $id, string $to)
    {
        $this->game->checkAction('actScoringAssign');

        $player_id = $currentPlayerId;

        if (!$this->game->ownsTile($player_id, $to))
            throw new UserException(clienttranslate("You do not own this tile"));

        // Check that destination tile can handle this type of resource for scoring purpose
        if ($this->game->TILES[$to]['score'] == '')
            throw new UserException(sprintf(clienttranslate("%s cannot receive any worker/resource/skill during final scoring phase"), $this->game->TILES[$to]['name']));

        if ($this->game->TILES[$to]['score'] != $type) {
            if ($this->game->TILES[$to]['score'] == 'resource')
                throw new UserException(clienttranslate("You should only move resources on this tile during final scoring phase"));
            if ($this->game->TILES[$to]['score'] == 'skill')
                throw new UserException(clienttranslate("You should only place skills on this tile during final scoring phase"));
            if ($this->game->TILES[$to]['score'] == 'worker')
                throw new UserException(clienttranslate("You should only place workers on this tile during final scoring phase"));
        }

        if ($type == 'resource') {
            // Check this resource is own by this player

            $resource = $this->game->getObjectFromDB("SELECT t.tile_id, r.card_type
                                FROM resource r
                                INNER JOIN tile t ON (r.card_location_arg = t.tile_id)
                                WHERE (r.card_location='tile') AND (t.tile_location_arg='" . $player_id . "') AND r.card_id='$id'");

            $origin_tile = $resource['tile_id'];
            $resource_type = $resource['card_type'];

            if ($origin_tile === null)
                throw new UserException(clienttranslate("You do not own this resource"));



            //Move the resource.
            $sql = "UPDATE resource SET card_type_arg='0', card_location_arg='" . $to . "' WHERE card_id='" . $id . "'";
            $this->game->DbQuery($sql);


            //Notify and move on to next state.
            $this->notify->all("transportResource", '', array(
                'player_id' => $player_id,
                'from' => $origin_tile,
                'to' => $to,
                'resource' => '<div class="resource resource' . $resource_type . '"></div>',
                'resource_id' => $id,
                'resource_type' => $resource_type
            ));

            $this->game->calculateAndUpdateScoreOnTiles(array($origin_tile, $to), $player_id);
        } else if ($type == 'skill') {
            // Take back all skills from tile
            $sql = "UPDATE skill SET card_location='hand', card_location_arg='$player_id', card_type_arg='1' WHERE card_location='action' AND card_location_arg='" . $to . "'";
            $this->game->DbQuery($sql);

            $this->notify->player($player_id, 'takeBackWorkers', '', array('tid' => $to));
            $this->notify->player($player_id, 'handUpdate', '', $this->game->getHand($player_id));

            $this->game->calculateAndUpdateScoreOnTiles(array($to), $player_id);
        } else if ($type == 'worker') {
            // Take back all workers from tile
            $sql = "UPDATE worker SET card_location='hand', card_location_arg='$player_id', card_type_arg='1' WHERE card_location='action' AND card_location_arg='" . $to . "'";
            $this->game->DbQuery($sql);

            $this->notify->player($player_id, 'takeBackWorkers', '', array('tid' => $to));
            $this->notify->player($player_id, 'handUpdate', '', $this->game->getHand($player_id));

            $this->game->calculateAndUpdateScoreOnTiles(array($to), $player_id);
        }
    }

    // Update final stats at the end of the game for given player
    private function updateFinalStats(int $pid)
    {
        $tiles = $this->game->getCollectionFromDB("SELECT tile_id, tile_scored, tile_goldscored FROM tile WHERE tile_location='village' AND tile_location_arg='$pid'");

        foreach ($tiles as $tile) {
            $total = intval($tile['tile_scored']);
            $gold = intval($tile['tile_goldscored']);
            $score = $total - $gold;

            if ($gold > 0) {
                $this->playerStats->inc('points_gold', $gold, $pid);
            }
            if ($score > 0) {
                if (in_array($tile['tile_id'], array(53, 54, 57, 62))) {
                    $this->playerStats->inc('points_workers', $score, $pid);
                    $score = 0;
                }
                if (in_array($tile['tile_id'], array(56, 60, 61))) {
                    $this->playerStats->inc('points_skills', $score, $pid);
                    $score = 0;
                }
                if (in_array($tile['tile_id'], array(41, 42, 49, 50, 59, 63, 64))) {
                    $this->playerStats->inc('points_resource', $score, $pid);
                    $score = 0;
                }
                if (in_array($tile['tile_id'], array(7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17))) {
                    $this->playerStats->inc('points_winterboats', $score, $pid);
                    $score = 0;
                }

                if ($score > 0)    // Then, we assume the rest of the points are from printed value
                {
                    $this->playerStats->inc('points_tile', $score, $pid);
                }
            }
        }

        $this->playerStats->set('transport_capacity', $this->game->getTransportCapacity($pid), $pid);

        // Count tiles
        $sql = "SELECT COUNT( tile_id ) FROM tile WHERE tile_location='village' AND tile_location_arg='$pid'";
        $this->playerStats->set('village_size', intval($this->game->getUniqueValueFromDB($sql)), $pid);
    }
}
