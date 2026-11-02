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
    50 => array("name" => "endTurn",
                "description" => clienttranslate('Ending the turn'),
                "type" => "game",
                "action" => "stEndTurn",
                "transitions" => array( "nextPlayer" => 30, "endSeason" => 70 )
    ),
    */

class EndTurn extends AbstractState
{
    function __construct(Game $game)
    {
        parent::__construct(
            game: $game,
            id: 50,
            type: StateType::GAME,
            description: clienttranslate('Ending the turn'),
            updateGameProgression: false,
        );
    }

    public function onEnteringState(): mixed {
        // Check if all passed (end of season).
        $sql = "SELECT COUNT(*) FROM player WHERE player_passed = FALSE";
        $playersStillToPass = $this->game->getUniqueValueFromDb($sql);
        if ($playersStillToPass == 0) {
            return SeasonEndA::class;
        }
        $player_id = $this->game->activeNextPlayer();
        $this->game->giveExtraTime($player_id);
        return PlayerTurn::class;
    }
}
