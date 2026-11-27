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
use Bga\Games\keyflowerrewrite\Model\MarketLot;
use Bga\Games\keyflowerrewrite\Model\Model;
use Bga\Games\keyflowerrewrite\Model\Season;

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

    /** @return array{winningPlayerId:int,needsBoat:bool,lotId:int}|null */
    function processNextTurnOrderTile(Model $model): ?array {
        $wonTurnOrderTiles = array_filter(
            $model->market()->lots(),
            fn (MarketLot $lot) => $lot->type->isTurnOrder() && !$lot->highBid()->isEmpty());
        if (count($wonTurnOrderTiles) == 0) {
            return null;
        }
        // FIXME: verify this sort is right precedence
        usort($wonTurnOrderTiles, fn ($t1, $t2) => strcmp($t1->type->value, $t2->type->value));
        $lot = array_shift($wonTurnOrderTiles);
        $winningBid = $lot->highBid();
        $winningPlayerId = $lot->winningPlayerId();
        $model->allData()->workerStock->putAllInBag($winningBid);
        $lot->removeAllBids();
        if ($lot->type->awardsStartPlayer()) {
           // $model->awardStartPlayerTo($winningPlayerId);
        }
        return [
            "winningPlayerId" => $winningPlayerId,
            // "startPlayerTo" => ...
            "lotId" => $lot->tile_id,
            "needsBoat" => true,
        ];
    }

    function onEnteringState(int $active_player_id): mixed {
        $model = $this->createModel(0);
        $stuff = $this->processNextTurnOrderTile($model);
        if ($stuff !== null) {
            // notify
            if ($stuff["needsBoat"]) {
                $this->gamestate?->changeActivePlayer($stuff["winningPlayerId"]);
                return SelectBoat::class;
            }
            $stuff = $this->processNextTurnOrderTile($model);
        }

        /** @var list<int> */
        $needBoats = $this->globals->get("PlayersNeedingBoats");
        $nextPlayerNeedingBoat = array_shift($needBoats);
        if ($nextPlayerNeedingBoat !== null) {
            $this->globals->set("PlayersNeedingBoats", $needBoats);
            $this->gamestate?->changeActivePlayer($nextPlayerNeedingBoat);
            return SelectBoat::class;
        }

        return ExpandVillages::class;
    }
}