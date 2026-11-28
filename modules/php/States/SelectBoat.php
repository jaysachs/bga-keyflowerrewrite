<?php

/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * Keyflower implementation : © Jay Sachs <vagabond@covariant.org>
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
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\keyflowerrewrite\Game;
use Bga\Games\keyflowerrewrite\Model\DockTile;
use Bga\Games\keyflowerrewrite\Model\Model;
use Bga\Games\keyflowerrewrite\Model\SkillSet;
use Bga\Games\keyflowerrewrite\Model\WorkerGroup;
use InvalidArgumentException;

class SelectBoat extends AbstractState {
    public function __construct(Game $game)
    {
        parent::__construct(
            game: $game,
            id: 26,
            type: StateType::ACTIVE_PLAYER,
            description: clienttranslate('${actplayer} must select a boat'),
            descriptionMyTurn: clienttranslate('${you} must select a boat'),
            updateGameProgression: true);
    }

    /** @return array<string,mixed> */
    public function getArgs(int $active_player_id): array {
        $model = $this->createModel($active_player_id);
        return [
            'available_boat_ids' => $model->availableBoatIds()
        ];
    }

    public function onEnteringState(int $active_player_id): mixed {
        $model = $this->createModel($active_player_id);
        $available = $model->availableBoatIds();
        if (count($available) === 1) {
            return $this->actSelectBoat($active_player_id, $available[0]);
        }
        return null;
    }

    #[PossibleAction]
    public function actSelectBoat(int $active_player_id, int $tile_id): mixed {

        $model = $this->createModel($active_player_id);

        $data = $model->selectBoat($tile_id);

        $boat = $data["boat"];
        $this->notify->all('boatSelected', 'Player ${player_name} selected boat ${tile}', [
            'player_id' => $active_player_id,
            'boat_id' => $boat->id,
            'tile' => $boat->type->marshal(),
            'cargo' => $data["cargo"]->marshal(),
            'passengers' => $data["passengers"]->marshal()
        ]);

        return TurnOrderAndUnloadBoats::class;
    }

    public function zombie(int $playerId): mixed {
        $model = $this->createModel($playerId);
        $available = $model->availableBoatIds();
        return $this->actSelectBoat($playerId, $available[0]);
    }

}