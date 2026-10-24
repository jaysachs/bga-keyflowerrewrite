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
    74 => array("name" => "seasonEndB",
                "description" => '',
                "type" => "game",
                "action" => "stSeasonEndB",
                "transitions" => array( "startConstruction" => 75, "finishSeasonB" => 80 )
                ),
                */

class SeasonEndB extends AbstractState
{
    function __construct(Game $game)
    {
        parent::__construct(
            game: $game,
            id: 74,
            type: StateType::GAME,
            updateGameProgression: false,
        );
    }

    public function onEnteringState(): mixed {
        $sql = "SELECT COUNT(*) FROM market m LEFT JOIN tile t ON (m.market_tile_id = t.tile_id) WHERE (t.tile_location='village')";
        $remainingInMarket = $this->game->getUniqueValueFromDB($sql);

        if ($remainingInMarket > 0) {
            // Tiles still to be added to the villages.
            while ($this->game->getUniqueValueFromDB("SELECT COUNT(*) FROM market m LEFT JOIN tile t ON (m.market_tile_id = t.tile_id) WHERE (t.tile_location='village') AND (t.tile_location_arg='" . $this->game->getActivePlayerId() . "')") == 0) {
                $this->game->activeNextPlayer();
            }
            // Active player has a tile to be added.
            return VillageConstructionA::class;
        } else {
            // Building complete
            $this->gamestate->changeActivePlayer($this->game->getStarter());
            return SeasonEndC::class;
        }
    }
}
