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

class EndOfSeason extends AbstractState
{
    function __construct(Game $game)
    {
        parent::__construct(
            game: $game,
            id: 24,
            type: StateType::GAME,
            description: clienttranslate('End of season'),
            updateGameProgression: true,
        );
    }

    function onEnteringState(): mixed {
        $model = $this->createModel(0);
        $endSeasonData = $model->endSeason();
        $losingBids = $endSeasonData["losingBids"];
        $this->notify->all('losingBidsReturned', "Losing bids returned", [
            'losing_bids' => array_map(fn ($lotId, $pids) => [
                'lot_id' => $lotId,
                // players who had losing bids on that tile
                'player_ids' => $pids
            ], array_keys($losingBids), $losingBids),
        ]);

        $this->notify->all('discardingUnbidLots', "Un-bid-for market tiles discarded", [
            // tile_ids of disarded lots. activations on them should go to bag.
            'discarded_lot_ids' => array_map(fn ($lot) => $lot->tile_id, $endSeasonData["discardedLots"])
        ]);

        $awardedLots = $endSeasonData["awardedLots"];
        $this->notify->all('wonTilesAwarded', "Won market tiles awarded to players",[
            'awarded_tiles' => array_map(fn ($lotId, $playerId) => [
                'lot_id' => $lotId,
                'player_id' => $playerId
            ], $awardedLots, array_keys($awardedLots))
        ]);

        $collectedWorkers = $endSeasonData["collectedWorkers"];
        $this->notify->all('collectActivatedWorkers', "Workers return from village tiles",[
            'tiles_with_workers' => array_map(fn ($pid, $tileIds) => [
                'player_id' => $pid,
                // tile ids of village tiles that had workers to collect and put in stock
                'tile_ids' => $tileIds
            ], array_keys($collectedWorkers), $collectedWorkers)
        ]);

        return TurnOrderAndUnloadBoats::class;
    }
}