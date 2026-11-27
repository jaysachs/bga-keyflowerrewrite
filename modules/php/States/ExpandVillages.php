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

use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\StateType;
use Bga\Games\keyflowerrewrite\Game;
use Bga\Games\keyflowerrewrite\Model\Season;

class ExpandVillages extends AbstractState
{
    function __construct(Game $game)
    {
        parent::__construct(
            game: $game,
            id: 27,
            type: StateType::MULTIPLE_ACTIVE_PLAYER,
            description: clienttranslate('Players are expanding their villages'),
            descriptionMyTurn: clienttranslate('Players are expanding their villages'),
            updateGameProgression: false,
        );
    }

    function onEnteringState(): void {
        $this->gamestate?->setAllPlayersMultiactive();
    }

    #[PossibleAction]
    public function actPlaceTile(int $active_player_id, int $tile_id, int $rowcol, int $orientation): void {
        // no notification needed
    }

    #[PossibleAction]
    public function actReturnTile(int $active_player_id, int $tile_id): void {
        // no notification needed
    }

    #[PossibleAction]
    public function actConfirm(int $active_player_id): void {
        $model = $this->createModel($active_player_id);

        // commit all pending placements, and notify every player

        $nextState = $model->season() == Season::WINTER ? ScoringAllocation::class : SeasonSetup::class;
        $this->gamestate?->setPlayerNonMultiactive($active_player_id, $nextState);
    }
}
