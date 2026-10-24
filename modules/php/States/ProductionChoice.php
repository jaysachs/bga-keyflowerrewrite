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
    43 => array("name" => "productionChoice",
                "description" => clienttranslate('${actplayer} must select which good(s) to produce'),
                "descriptionmyturn" => clienttranslate('${you} must select which good(s) to produce: '),
                "type" => "activeplayer",
                "args" => "argProductionChoice",
                "possibleactions" => array( "actSelectGoods" ),
                "transitions" => array( "selectGoods" => 50, "zombiePass" => 50 )
    ),
    */

class ProductionChoice extends AbstractState
{
    function __construct(Game $game)
    {
        parent::__construct(
            game: $game,
            id: 43,
            type: StateType::ACTIVE_PLAYER,
    		description: clienttranslate('${actplayer} must select which good(s) to produce'),
    		descriptionMyTurn: clienttranslate('${you} must select which good(s) to produce: '),
            updateGameProgression: false,
        );
    }

    public function getArgs(): array
    {
        //Return the possible production choices...
        // "possibleactions" => array( "selectGoods" ),
        // This is where there is a choice of production (e.g. tile gives options...). Quite simply, list them... To display we will need: 'resource/skill/worker', and 'type'
        return [ 'productionChoices' => $this->getProductionChoices() ];
    }

    private function getProductionChoices()
    {
        // Need to grab the tile in question. Get the production map(s). Work out the choices. Return the array as 'worker/skill/resource'/'1/2/3/4' pairs.

        // Get tile data
        $sql = "SELECT move_location FROM move WHERE (move_worker_id IS NULL)";
        $tid = $this->game->getUniqueValueFromDB($sql);
        $sql = "SELECT tile_side side, tile_location location, tile_location_arg pid FROM tile WHERE (tile_id = '" . $tid . "')";
        $tile_data = $this->game->getObjectFromDb($sql);
        $side = $tile_data['side'];

        // Get reward data
        $sql = "SELECT produce_id, produce_map map FROM produce WHERE (produce_tile_id='" . $tid . "') AND (produce_tile_side='" . $side . "') AND (produce_type = 'reward')";
        $reward_data = $this->game->getCollectionFromDb($sql);

        $reward_items = array();
        foreach ($reward_data as $rd) {
            array_push($reward_items, $rd['map']);
        }

        return $this->collateRewardChoices($reward_items);
    }

    function collateRewardChoices($reward_items)
    {
        $choices = array();

        $single = array();
        $multiple = array();
        foreach ($reward_items as $item) {
            if ($this->game->isBagGood($item) || $this->game->isSingleGood($item)) {
                array_push($single, $this->game->getMapItem($item));
            } else {
                array_push($multiple, $this->game->splitMapIntoSingleItems($item));
            }
        }

        array_push($choices, $single);
        foreach ($multiple as $m) {
            $new_options = array();
            $choices_before = $choices;
            foreach ($m as $i) { // get the individual choices from the item array.
                foreach ($choices_before as $cb) {
                    $altered = $cb;
                    array_push($altered, $i); // add i to the choices.
                    array_push($new_options, $altered);
                }
            }
            $choices = $new_options;
        }

        return $choices;
    }

	function zombie(int $player_id): mixed {
        return EndTurn::class;
    }

    #[PossibleAction]
    function actSelectGoods(int $active_player_id, int $id): mixed
    {
        $productionChoices = $this->getProductionChoices();
        if ($id > sizeOf($productionChoices)) {
            throw new \BgaUserException("Invalid selection");
        }

        // Get tile data
        $sql = "SELECT move_location FROM move WHERE (move_worker_id IS NULL)";
        $tid = $this->game->getUniqueValueFromDB($sql);
        $sql = "SELECT tile_side side, tile_location location, tile_location_arg pid FROM tile WHERE (tile_id = '" . $tid . "')";
        $tile_data = $this->game->getObjectFromDb($sql);
        $side = $tile_data['side'];

        // Reward destination
        if (($tile_data['location'] == 'village') && ($tile_data['pid'] == $active_player_id)) {
            $destination = $tid;
        } else {
            // home tile
            $sql = "SELECT tile_id tid FROM tile WHERE (tile_type = 'home') AND (tile_location_arg='" . $this->game->getCurrentPlayerId() . "')";
            $destination = $this->game->getUniqueValueFromDB($sql);
        }

        $chosen = $productionChoices[$id];
        $html_resources = '';
        $resources_to_add = array();

        foreach ($chosen as $good) {
            $item_id = $this->game->produceGood($this->game->getResourceMapFromType($good['table'], $good['type']), $destination);

            $html_resources .= '<div class="resource resource' . $good['type'] . '"></div> ';
            $resources_to_add[$item_id] = $good['type'];
        }

        $this->notify->player($active_player_id, 'handUpdate', '', $this->game->getHand($active_player_id));
        $this->notify->all("production", clienttranslate('${player_name} produces ${ress}'), array(
            'player_id' => $this->game->getActivePlayerId(),
            'ress' => $html_resources,
            'to_add' => $resources_to_add,
            'source' => $tid,
            'destination' => $destination
        ));

        $this->playerStats->inc('resource_produced', count($resources_to_add), $active_player_id);

        $this->game->finaliseMove();
        return EndTurn::class;
    }
}
