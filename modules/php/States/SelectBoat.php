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
            'available_boat_ids' => array_map(
                fn ($t) => $t->id,
                array_filter(
                    $model->dock()->boats(),
                    fn (DockTile $t) => ! ($t->passengers()->isEmpty() && $t->cargo()->isEmpty())
                )
            )
        ];
    }

    public function onEnteringState(int $active_player_id): mixed {
        $model = $this->createModel($active_player_id);
        if (count($model->dock()->boats()) === 1) {
            $boat_id = $model->dock()->boats()[0]->id;
            return $this->actSelectBoat($active_player_id, $boat_id);
        }
        return null;
    }

    /** @return array{boat:DockTile,cargo:SkillSet,passengers:WorkerGroup} */
    private function selectBoat(Model $model, int $boat_id): array {
        $boat = $model->dock()->boatById($boat_id);
        if ($boat === null) {
            throw new InvalidArgumentException("no boat {$boat_id}");
        }
        // FIXME: is this check sufficient or do we need explicit tracking of selected boats?
        if ($boat->cargo()->isEmpty() && $boat->passengers()->isEmpty()) {
            throw new InvalidArgumentException("boat {$boat->type->value} already selected");
        }
        $cargo = $boat->takeAllCargo();
        $passengers = $boat->takeAllPassengers();
        $model->village()->workers()->addGroup($passengers);
        $model->village()->skills->addSet($cargo);

        $model->hackSaveAll();

        return [
            "boat" => $boat,
            "cargo" => $cargo,
            "passengers" => $passengers
        ];
    }

    #[PossibleAction]
    public function actSelectBoat(int $active_player_id, int $tile_id): mixed {

        $model = $this->createModel($active_player_id);

        $data = $this->selectBoat($model, $tile_id);
        // put cargo and passengers from boat into village

        $boat = $data["boat"];
        $this->notify->all('boatSelected', 'Player {$player_name} selected boat {$tile}', [
            'player_id' => $active_player_id,
            'boat_id' => $boat->id,
            'tile' => $boat->type->marshal(),
            'cargo' => $data["cargo"]->marshal(),
            'passengers' => $data["passengers"]->marshal()
        ]);

        return TurnOrderAndUnloadBoats::class;
    }

    public function zombie(int $playerId): mixed {
        return TurnOrderAndUnloadBoats::class;
    }

}