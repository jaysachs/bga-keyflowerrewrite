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
    90 => array("name" => "yearEnd",
                "description" => clienttranslate('Scoring the year'),
                "type" => "game",
                "action" => "stYearEnd",
                "updateGameProgression" => true,
                "transitions" => array( "startScoringArrangement"=> 91, "endGame" => 99)
    ),
*/
class YearEnd extends AbstractState
{
    const END_GAME_STATE = 99;

    function __construct(Game $game)
    {
        parent::__construct(
            game: $game,
            id: 90,
            type: StateType::GAME,
            description: clienttranslate('Scoring the year'),
            updateGameProgression: true,
        );
    }
    public function onEnteringState(): mixed {
        //Let each player organise their resources for scoring (Can use the player_passed flag).
        $sql = "SELECT player_id FROM player WHERE player_passed = FALSE";
        $left_to_arrange = $this->game->getCollectionFromDB($sql);
        if (sizeOf($left_to_arrange) == 0) {
            return self::END_GAME_STATE;
        }
        while ($this->game->getUniqueValueFromDB("SELECT player_passed FROM player WHERE player_id = '" . $this->game->getActivePlayerId() . "'")) {
            $player_id = $this->game->activeNextPlayer();
        }
        $this->game->giveExtraTime(intval($this->game->getActivePlayerId()));
        return ScoringSelect::class;
    }
}
