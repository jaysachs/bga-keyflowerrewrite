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

class TurnOrderAndUnloadBoats extends AbstractState
{
    function __construct(Game $game)
    {
        parent::__construct(
            game: $game,
            id: 25,
            type: StateType::GAME,
            description: clienttranslate('Turn order and boats'),
            updateGameProgression: false,
        );
    }

    function onEnteringState(int $active_player_id): mixed {
        $model = $this->createModel(0);
        $stuff = $model->processNextTurnOrderTile();
        if ($stuff !== null) {
            // notify
            if ($stuff["needsBoat"]) {
                $this->gamestate?->changeActivePlayer($stuff["winningPlayerId"]);
                return SelectBoat::class;
            }
            $stuff = $model->processNextTurnOrderTile();
        }
        $nextPlayerNeedingBoat = $model->nextPlayerNeedingBoat();
        if ($nextPlayerNeedingBoat !== null) {
            $this->gamestate?->changeActivePlayer($nextPlayerNeedingBoat);
            return SelectBoat::class;
        }

        return ExpandVillages::class;
    }
}