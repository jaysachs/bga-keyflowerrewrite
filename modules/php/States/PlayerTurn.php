<?php

/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * zooloretto implementation : © Jay Sachs <vagabond@covariant.org>
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
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\Table;
use Bga\GameFramework\UserException;
use Bga\Games\keyflowerrewrite\Game;
use Bga\Games\keyflowerrewrite\Model\Model;
use Bga\Games\keyflowerrewrite\Model\WorkerType;
use Override;

class PlayerTurn extends GameState {
    public function __construct(Table $game)
    {
        parent::__construct(
            game: $game,
            id: 200,
            type: StateType::ACTIVE_PLAYER,
            name: null,
            description: _('${actplayer} must either bid for a tile, activate a tile, or pass.'),
            descriptionMyTurn: _('${you} must either bid for a tile, activate a tile, or pass'),
            updateGameProgression: true);
    }

    /**
     * @param list<int> $moved_bid_ids the tiles to move bids from
     */
    #[PossibleAction]
    public function actActivate(int $active_player_id, int $tile_id,array $moved_bid_ids, int $workersFromStock, WorkerType $workerType): void {
        /*
         verify:
            1. tile_id is a tile in some village
            2. the tile has an activation reward
            3. moved_bid_ids refer to active player's bids
            4. moved_bid_ids are currently losing
            5. moved_bid_ids are unique
            6. all moved_bid groups have the same type
            7. worker_type is same as all moved_bid groups
            8. if there are already activations or bids on this tile, worker type should match
                unless player has boat4b
        */
    }

    /**
     * @param list<int> $moved_bid_ids the tiles to move bids from
     */
    #[PossibleAction]
    public function actBid(int $active_player_id, int $market_tile_id, array $moved_bid_ids, int $numWorkersFromStock, WorkerType $workerType): void {
        $model = new Model($active_player_id);

        $model->bid($market_tile_id, $moved_bid_ids, $numWorkersFromStock, $workerType);

        /*
        $this->game->notify(...);

        $this->game->activeNextPlayer();
        */

    }
}