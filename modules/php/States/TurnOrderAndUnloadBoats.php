<?php

/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * babylonia implementation : © Jay Sachs <vagabond@covariant.org>
 *
 * Copyright 2026 Jay Sachs <vagabond@covariant.org>
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
use Bga\Games\keyflowerrewrite\Model\Season;

class TurnOrderAndUnloadBoats extends AbstractState
{
    function __construct(Game $game)
    {
        parent::__construct(
            game: $game,
            id: 24,
            type: StateType::GAME,
            description: clienttranslate('Turn order and boats'),
            updateGameProgression: false,
        );
    }

    function onEnteringState(int $active_player_id): mixed {
        $model = $this->createModel(0);
        // while ($anyRemainingTurnOrderTilesWithWinningBids) {
            /*
                choose the highest precedence tile
                move the winning bid workers to the bag (technically the rules do this at the end ... does that matter?)
                    in Winter, award the turn order tile to the player (this is out of order with rules but does it matter?)
                Give the start player token if the tile offers that as a reward.
                if the winning player has NOT made a boat selection:
                    set the active player
                    transition to ChooseBoat state
            */
        // }

        // if ($model->allData()->dock->hasUndeliveredBoats()) {
            /*
                choose the next player who did not select a boat in clockwise order from start player
                transition to ChooseBoat
            */
        // }


        // return ExpandVillages::class;

        if ($model->season() == Season::WINTER) {
            // return AllocatePiecesForScoring::class;
            return 99;
        }
        return SeasonSetup::class;
    }
}