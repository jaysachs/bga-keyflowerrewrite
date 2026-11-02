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
    41 => array("name" => "productionCost",
                "description" => clienttranslate('${actplayer} must select a method of payment'),
                "descriptionmyturn" => clienttranslate('${you} must select your method of payment: '),
                "type" => "activeplayer",
                "args" => "argProductionCost",
                "possibleactions" => array( "actDiscardGoods" ),
                "transitions" => array( "discardGoods" => 42, "zombiePass" => 50 )
    ),
    */

class ProductionCost extends AbstractState
{
    function __construct(Game $game)
    {
        parent::__construct(
            game: $game,
            id: 41,
            type: StateType::ACTIVE_PLAYER,
    		description: clienttranslate('${actplayer} must select a method of payment'),
    		descriptionMyTurn: clienttranslate('${you} must select your method of payment: '),
            updateGameProgression: false,
        );
    }

    public function getArgs(): array
    {
        return [
            'productionCosts' => $this->getProductionCostChoices()
        ];
    }

    private function getProductionCost(string $tid, int $side): array
    {
        // Find out cost
        $sql = "SELECT produce_id, produce_map map FROM produce WHERE (produce_tile_id='" . $tid . "') AND (produce_tile_side='" . $side . "') AND (produce_type = 'cost')";
        $cost_data = $this->game->getCollectionFromDb($sql);

        $cost_items = array();
        foreach ($cost_data as $cd) {
            array_push($cost_items, $cd['map']);
        }
        return $cost_items;
    }


    private function getProductionCostChoices() : array
    {
        $sql = "SELECT move_location FROM move";
        $tid = $this->game->getUniqueValueFromDB($sql);
        $sql = "SELECT tile_side FROM tile WHERE (tile_id = '" . $tid . "')";
        $side = $this->game->getUniqueValueFromDB($sql);

        $cost_items = $this->getProductionCost($tid, $side);

        $item_combinations = $this->game->getCombinations($cost_items, array(), null, false);

        $choices = array();
        foreach ($item_combinations as $combination) {  // will have multiple options, but each could contain a single item or an array.
            if (is_int($combination)) {  // Force single items into an array.
                $dummy = array();
                array_push($dummy, $combination);
                $combination = $dummy;
            }
            $current = array();
            foreach ($combination as $item) { // items within the combination.
                array_push($current, $this->game->getMapItem($item));
            }
            array_push($choices, $current);
        }

        return $choices;
    }

	function zombie(int $player_id): mixed {
        return EndTurn::class;
    }

    #[PossibleAction]
    function actDiscardGoods(int $active_player_id, int $id): mixed
    {
        $productionCostChoices = $this->getProductionCostChoices();
        if ($id > sizeOf($productionCostChoices)) {
            throw new \BgaUserException("Invalid selection");
        }

        /* FIXME: these are unused.
        // Get tile data
        $sql = "SELECT move_location FROM move WHERE (move_worker_id IS NULL)";
        $tid = $this->game->getUniqueValueFromDB($sql);
        $sql = "SELECT tile_side side, tile_location location, tile_location_arg pid FROM tile WHERE (tile_id = '" . $tid . "')";
        $tile_data = $this->game->getObjectFromDb($sql);
        $side = $tile_data['side'];
        */

        $chosen = $productionCostChoices[$id];
        foreach ($chosen as $good) {
            $this->game->payWith($good['table'], $good['type']);
        }

        $this->notify->player($active_player_id, 'handUpdate', '', $this->game->getHand($active_player_id));
        return VillageProduction::class;
    }
}
