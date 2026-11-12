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

use Bga\GameFramework\Actions\Types\IntArrayParam;
use Bga\GameFramework\StateType;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\keyflowerrewrite\Game;
use Bga\Games\keyflowerrewrite\Model\MarketTile;
use Bga\Games\keyflowerrewrite\Model\Marshaller;
use Bga\Games\keyflowerrewrite\Model\WorkerColor;
use Bga\Games\keyflowerrewrite\Model\WorkerType;

class PlayerTurn extends AbstractState {
    public function __construct(Game $game)
    {
        parent::__construct(
            game: $game,
            id: 22,
            type: StateType::ACTIVE_PLAYER,
            description: clienttranslate('${actplayer} must either bid for a tile, activate a tile, or pass.'),
            descriptionMyTurn: clienttranslate('${you} must either bid for a tile, activate a tile, or pass'),
            updateGameProgression: true);
    }

    /** @return mixed[] */
    public function getArgs(int $active_player_id): array {
        /*
           For bidding, we return info on each tile in the market, indicating
            (1) what color is required (or null if no color is fixed or the player has boat4a)
            (2) the minimum number of workers for a legal bid
            (3) whether the player's current bid on the tile is movable
            (4) TODO: also include the color/size of the player's current bid?
        */
        $model = $this->createModel($active_player_id);
        return [
            "bidinfo" => array_map(
                function (MarketTile $mt) use (&$active_player_id) : array {
                    $hb = $mt->highBid();
                    $bc = $mt->biddingColor();
                    return [
                        "color" => $bc === null ? null : $bc->value,
                        "min" => $hb->size()+1,
                        "moveable" => $mt->hasNonWinningBid($active_player_id),
                    ];
                },
                $model->market()->tiles())
        ];
    }

    /**
     * @param list<int> $moved_bid_ids the tiles to move bids from
     */
    #[PossibleAction]
    public function actActivate(int $active_player_id, int $tile_id,array $moved_bid_ids, int $workersFromStock, WorkerColor $workerColor): void {
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
    public function actBid(int $active_player_id, int $market_tile_id, #[IntArrayParam()] array $moved_bid_ids, int $numWorkersFromStock, string $color): mixed {
        $model = $this->createModel($active_player_id);
        $workerColor = WorkerColor::from($color);
        $bidResult = $model->bid($market_tile_id, $moved_bid_ids, $numWorkersFromStock, $workerColor);
        $bid = $bidResult->bid;

        foreach ($bidResult->movedBids as $bt => $mb) {
            $this->game->notify->all(
                'bidMoved',
                '${player_name} moved bid of ${num} ${color} from ${markettile}',
                [
                    'num' => count($mb->workers()),
                    'color' => Marshaller::color($mb->color()),
                    'markettile' => $bt,
                    'player_id' => $active_player_id,
                ]
            );
        }
        $this->game->notify->all(
            'bidMade',
            '${player_name} bid ${num} ${color} workers on ${tiletype}',
            [
                'player_id' => $active_player_id,
                'markettileid' => $market_tile_id,
                'num' => count($bid->workers()),
                'color' => Marshaller::color($bid->color()),
                // @phpstan-ignore property.nonObject
                'tiletype' => $model->market()->marketTileById($market_tile_id)->type->value,
                'bid' => array_map(fn ($w) => [
                    // FIXME: do we need position too?
                    'id' => $w->id,
                    'type' => $w->color->value
                ], $bid->workers())
            ]
        );
        return NextPlayer::class;
    }

    #[PossibleAction]
    public function actPass(int $active_player_id): mixed {
        $this->createModel($active_player_id)->passTurn();
        // TODO
        return NextPlayer::class;
    }

    public function zombie(int $playerId): mixed {
        return 99;
    }

}