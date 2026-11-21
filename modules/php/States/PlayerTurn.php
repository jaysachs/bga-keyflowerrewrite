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
use Bga\Games\keyflowerrewrite\Model\ActivationResult;
use Bga\Games\keyflowerrewrite\Model\ActivationReward;
use Bga\Games\keyflowerrewrite\Model\MarketLot;
use Bga\Games\keyflowerrewrite\Model\Marshaller;
use Bga\Games\keyflowerrewrite\Model\MovedBid;
use Bga\Games\keyflowerrewrite\Model\ProductionActivations;
use Bga\Games\keyflowerrewrite\Model\ResourceType;
use Bga\Games\keyflowerrewrite\Model\TileType;
use Bga\Games\keyflowerrewrite\Model\VillageTile;
use Bga\Games\keyflowerrewrite\Model\WorkerColor;

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

    /**
     * @param VillageTile|MarketLot $tile
     * @return array<string,mixed>
     */
    private function activateableInfo( mixed $tile, bool $hasBoat4b): ?array {
        $bc = $tile->currentColor();
        $a = $tile->productionActivations();
        if ($tile->type->activationReward() == ActivationReward::NONE || !$a->isActivatable()) {
            return null;
        }
        $cost = $tile->type->activationCost();
        // FIXME: filter out if player can't afford the cost?
        return [
            "must_match" => !$hasBoat4b,
            "tile_id" => $tile->tile_id,
            "color" => $bc === null ? null : $bc->value,
            "min" => $a->minRequiredToActivate(),
            "max" => $a->maxAllowededToActivate(),
            "cost" => $cost->value,
        ];
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
        $hasBoat4a = $model->village()->hasTileType(TileType::BOAT4A);
        $hasBoat4b = $model->village()->hasTileType(TileType::BOAT4B);

        /** @var array<array<string,mixed>> */
        $activationInfo = [];
        foreach ($model->market()->lots() as $tile) {
            $ai = $this->activateableInfo($tile, $hasBoat4b);
            if ($ai) {
                $activationInfo[] = $ai;
            }
        }
        foreach ($model->allData()->villages as $pid => $village) {
            foreach ($village->tiles() as $tile) {
                $ai = $this->activateableInfo($tile, $hasBoat4b);
                if ($ai) {
                    $activationInfo[] = $ai;
                }
            }
        }
        return [
            "bidding_info" => array_map(
                function (MarketLot $lot) use (&$active_player_id, $hasBoat4a): array {
                    $bc = $lot->currentColor();
                    $bid = $lot->bidBy($active_player_id);
                    return [
                        "must_match" => !$hasBoat4a  || $bc === WorkerColor::GREEN,
                        "tile_id" => $lot->tile_id,
                        "color" => $bc === null ? null : $bc->value,
                        "min" => $lot->highBid()->size()+1,
                        "moveable" => $lot->hasNonWinningBid($active_player_id),
                        // TODO: determine if this is needed
                        "current_bid" => $bid === null ? null : [
                            "color" => Marshaller::color($bid->color())   ,
                            "num" => $bid->size(),
                            "workers" => Marshaller::workers($bid->workers()),
                        ]
                    ];
                },
                $model->market()->lots()),
            "activation_info" => $activationInfo,
        ];
    }

    /**
     * @param list<int> $moved_bid_ids the tiles to move bids from
     */
    #[PossibleAction]
    public function actActivate(
        int $active_player_id,
        int $tile_id,
        #[IntArrayParam()] array $moved_bid_ids,
        ?int $num_blue,
        ?int $num_red,
        ?int $num_yellow,
        ?int $num_green,
        ?int $skill_pmt_id,
        ?int $worker_pmt_id,
        ?string $resource_type_reward): mixed {

        $model = $this->createModel($active_player_id);
        $requested_resource = $resource_type_reward ? ResourceType::from($resource_type_reward): null;
        $activationResult = $model->activate($tile_id, $moved_bid_ids, $num_blue ?? 0, $num_red ?? 0, $num_yellow ?? 0, $num_green ?? 0, $skill_pmt_id, $worker_pmt_id, $requested_resource);

        $this->notifyMovedBids($active_player_id, $activationResult->movedBids);

        $this->notify->all(
            'tileActivated',
            '${player_name} activated tile ${tiletype}', [
                'tile_id' => $tile_id,
                'tiletype' => $activationResult->tile->type->value,
                'player_id' => $active_player_id,
                'workers' => Marshaller::workers($activationResult->activatingWorkerGroup->workers()),
                'produced' => [
                    'workers' => Marshaller::workers($activationResult->prodResult->workers),
                    'resources' => Marshaller::resources($activationResult->prodResult->resources),
                    'skills' => Marshaller::skills($activationResult->prodResult->skills),
                    'upgrades' => $activationResult->prodResult->upgrades,
                    'dest_tile_id' => $activationResult->destTile?->tile_id
                ]
            ]);
        if ($activationResult->prodResult->upgrades > 0) {
            // FIXME: transition to upgrade state
            return null;
        } else {
            return NextPlayer::class;
        }
    }

    /** @param list<MovedBid> $movedBids */
    private function notifyMovedBids(int $active_player_id, array $movedBids): void {
        foreach ($movedBids as $mb) {
            $this->game->notify->all(
                'bidMoved',
                '${player_name} moved bid of ${num} ${color} from ${tiletype}',
                [
                    'num' => count($mb->bid->workers()),
                    'color' => Marshaller::color($mb->bid->color()),
                    'tile_id' => $mb->sourceTile->tile_id,
                    'tiletype' => $mb->sourceTile->type->value,
                    'player_id' => $active_player_id,
                ]
            );
        }
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

        $this->notifyMovedBids($active_player_id, $bidResult->movedBids);
        $this->game->notify->all(
            'bidMade',
            '${player_name} bid ${num} ${color} on ${tiletype}',
            [
                'player_id' => $active_player_id,
                'marketlot_id' => $market_tile_id,
                'num' => count($bid->workers()),
                'color' => Marshaller::color($bid->color()),
                'tiletype' => $model->market()->marketLotById($market_tile_id)->type->value,
                // the tiles the moved bids (bidMoved, just before) leave, so the client can show the whole bid in one step
                'moved_from' => array_map(fn ($mb) => $mb->sourceTile->tile_id, $bidResult->movedBids),
                'bid' => array_map(fn ($w) => [
                    // FIXME: do we need position too?
                    'id' => $w->id,
                    'color' => $w->color->value
                ], $bid->workers())
            ]
        );
        return NextPlayer::class;
    }

    #[PossibleAction]
    public function actPass(int $active_player_id): mixed {
        $this->createModel($active_player_id)->passTurn();
        $this->notify->all(
            'playerPassed',
            '${player_name} passed their turn',
            [
                'player_id' => $active_player_id
            ]
        );
        return NextPlayer::class;
    }

    public function zombie(int $playerId): mixed {
        return 99;
    }

}