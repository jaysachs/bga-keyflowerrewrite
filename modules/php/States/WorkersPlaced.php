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


class WorkersPlaced extends AbstractState
{
    function __construct(Game $game)
    {
        parent::__construct(
            game: $game,
            id: 40,
            type: StateType::GAME,
            description: clienttranslate('Checking the worker placement'),
            updateGameProgression: false,
        );
    }

    public function onEnteringState(): mixed {

        $sql = "SELECT * FROM move";
        $move_data = $this->game->getObjectFromDb($sql);

        if ($move_data['move_type'] == 'bid') {

            // bid
            $this->game->finaliseMove();
            return EndTurn::class;
        }


        // Action.

        // Get tile id and side.
        $tid = $move_data['move_location'];
        $sql = "SELECT tile_side FROM tile WHERE (tile_id = '" . $tid . "')";
        $side = $this->game->getUniqueValueFromDB($sql);

        // Find out cost
        $sql = "SELECT produce_id, produce_map map FROM produce WHERE (produce_tile_id='" . $tid . "') AND (produce_tile_side='" . $side . "') AND (produce_type = 'cost')";
        $cost_data = $this->game->getCollectionFromDb($sql);

        $cost_items = array();
        foreach ($cost_data as $cd) {
            array_push($cost_items, $cd['map']);
        }

        // Check payment combinations
        $item_combinations = $this->game->getCombinations($cost_items, array(), null, false);

        if ($item_combinations === null) {
            throw new UserException(clienttranslate("You cannot afford the activation cost of this tile"));
        }

        if (sizeOf($item_combinations) > 1) {
            return ProductionCost::class;
        }
        // No choice

        if (sizeOf($item_combinations) == 1) {
            $this->payForGoods($item_combinations);
        }

        return VillageProduction::class;
    }

    private function payForGoods(array $combination)
    {
        /*
           array of goods to pay.
         */
        foreach ($combination as $good) {
            $map = $this->game->getMapItem($good);
            $this->game->payWith($map['table'], $map['type']);
        }
    }
}
