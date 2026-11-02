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
use Bga\GameFramework\UserException;
use Bga\Games\keyflowerrewrite\Game;


class VillageProduction extends AbstractState
{
    function __construct(Game $game)
    {
        parent::__construct(
            game: $game,
            id: 42,
            type: StateType::GAME,
            description: clienttranslate('Dispatching goods'),
            updateGameProgression: false,
        );
    }

    public function onEnteringState(): mixed {
        // production paid for... now release the reward.   is there a choice? is it transport?
        // "transitions" => array( "transport" => 45, "produce" => 50, "produceChoice" => 43 )

        // Get tile data
        $sql = "SELECT move_location FROM move WHERE (move_worker_id IS NULL)";
        $tid = $this->game->getUniqueValueFromDB($sql);
        $sql = "SELECT tile_side side, tile_location location, tile_location_arg pid FROM tile WHERE (tile_id = '" . $tid . "')";
        $tile_data = $this->game->getObjectFromDb($sql);
        $side = $tile_data['side'];
        $player_id = intval($this->game->getCurrentPlayerId());

        // Reward destination
        if (($tile_data['location'] == 'village') && ($tile_data['pid'] == $player_id)) {
            $destination = $tid;
        } else {
            // home tile
            $sql = "SELECT tile_id tid FROM tile WHERE (tile_type = 'home') AND (tile_location_arg='" . $this->game->getCurrentPlayerId() . "')";
            $destination = $this->game->getUniqueValueFromDB($sql);
        }

        // Reward data
        $sql = "SELECT produce_id, produce_map map FROM produce WHERE (produce_tile_id='" . $tid . "') AND (produce_tile_side='" . $side . "') AND (produce_type = 'reward')";
        $reward_data = $this->game->getCollectionFromDb($sql);
        $reward_items = array();
        foreach ($reward_data as $rd) {
            array_push($reward_items, $rd['map']);
        }

        if (count($reward_items) == 0) {
            throw new UserException(_("This tile has no activation power"));
        }

        $html_resources = '';

        if ($this->game->isRewardChoice($reward_items)) {
            return ProductionChoice::class;
        }
        $transport = 0;
        $upgrade = 0;
        $produceGoods = false;
        $resources_to_add = array();
        foreach ($reward_items as $good) {
            if ($good == 2048) {
                $transport++;
            } elseif ($good == 4096) {
                $upgrade++;
            } else {
                $produceGoods = true;
                if ($this->game->isBagGood($good)) {
                    // Bag item.
                    switch ($good) {
                        case 7:
                            $cards = $this->game->workers->pickCards(1, 'bag', $player_id);
                            $color = $cards[0]['type'];
                            $this->notify->player($player_id, 'simpleNote', clienttranslate('(for your information you get a ${item})'), array('item' => '<div class="worker_mini worker_mini' . $color . ' nofloat"></div>'));

                            $html_resources .= '<div class="worker_mini worker_mini0 nofloat"></div> ';
                            break;
                        case 112:

                            $cards = $this->game->skills->pickCards(1, 'bag', $player_id);
                            $skill = $cards[0]['type'];
                            $this->notify->player($player_id, 'simpleNote', clienttranslate('(for your information you get a ${item})'), array('item' => '<div class="skill_mini skill_mini' . $skill . ' nofloat"></div>'));

                            $html_resources .= '<div class="skill_mini skill_mini0 nofloat"></div> ';
                            break;
                    }
                } else {

                    $gooditem = $this->game->getMapItem($good);

                    $item_id = $this->game->produceGood($good, $destination);

                    if ($gooditem['table'] == 'resource') {
                        $resources_to_add[$item_id] = $gooditem['type'];
                        $html_resources .= '<div class="resource resource' . $gooditem['type'] . '"></div> ';
                    } else if ($gooditem['table'] == 'worker')
                        $html_resources .= '<div class="worker_mini worker_mini' . $gooditem['type'] . ' nofloat"></div> ';
                    else if ($gooditem['table'] == 'skill')
                        $html_resources .= '<div class="skill skill' . $gooditem['type'] . '"></div> ';
                }
            }
        }
        if ($produceGoods) {
            $this->notify->player($player_id, 'handUpdate', '', $this->game->getHand($player_id));

            //$resources = $this->game->getResources();
            $this->notify->all("production", clienttranslate('${player_name} gets ${ress}'), array(
                'player_id' => $this->game->getActivePlayerId(),
                'ress' => $html_resources,
                'to_add' => $resources_to_add,
                'source' => $tid,
                'destination' => $destination
            ));

            $this->playerStats->inc('resource_produced', count($resources_to_add), $player_id);
        }

        if ($transport + $upgrade > 0) {

            // SPECIAL: Boat 2b (Transport/Upgrade doubled).
            if ($this->game->ownsTile($player_id, 34, 1)) {
                $transport = $transport * 2;
                $upgrade = $upgrade * 2;
            }

            $sql = "UPDATE player SET player_transport='" . $transport . "', player_upgrade='" . $upgrade . "' WHERE (player_id='" . $player_id . "')";
            $this->game->DbQuery($sql);
            return VillageBuilding::class;
        }

        $this->game->finaliseMove();
        return EndTurn::class;
    }
}
