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

namespace Bga\Games\keyflowerrewrite\Model;

use InvalidArgumentException;

class Market implements Marshalable {
    /** @var list<MarketLot> */
    private array $lots = [];
    public function __construct() {
        $this->validate();
    }

    public function marketLotById(int $tileId): MarketLot {
        foreach ($this->lots as $tile) {
            if ($tile->tile_id == $tileId) {
                return $tile;
            }
        }
        throw new InvalidArgumentException("no market tile with id {$tileId}");
    }

    private function validate(): void {
        $ids = array_map(fn (MarketLot $t) => $t->tile_id, $this->lots);
        if (count($ids) != count(array_unique($ids))) {
            $x = '';
            $seen =[];
            foreach ($this->lots as $t) {
                $id = $t->tile_id;
                if (isset($seen[$id])) {
                    $seen[$id]++;
                    $x .= "{$t->type->value}({$id})";
                } else {
                    $seen[$id] = 1;
                }
            }
            throw new InvalidArgumentException("non unique tiles in market: {$x}");
        }
    }

    public function addLot(MarketLot $lot): void {
        $this->lots[] = $lot;
        $this->validate();
    }

    public function lotByTileId(int $id): ?MarketLot {
        return array_find($this->lots, fn ($t) => $t->tile_id == $id);
    }

    public function replenish(Season $season, TilesStock $stock): void {
        array_push($this->lots, ... array_map(fn ($t) => MarketLot::fromStock($t), $stock->takeTilesFor($season)));
        $this->validate();
    }

    /** @return list<MarketLot> lot ids discarded */
    public function removeUnbidLots(): array {
        $result = [];
        $keep = [];
        foreach ($this->lots as $lot) {
            if ($lot->type->isTurnOrder() || $lot->highBid()->size() > 0) {
                $keep[] = $lot;
            }
            else {
                $result[] = $lot;
            }
        }
        $this->lots = $keep;
        return $result;
    }

    /** @return array<int,MarketLot>  keyed by position */
    public function lots(): array {
        return $this->lots;
    }

    /** @return list<WorkerGroup> */
    public function losingBidsFor(int $playerId): array {
        $result = [];
        foreach ($this->lots as $lot) {
            if ($lot->hasNonWinningBid($playerId)) {
                /** @var WorkerGroup */
                $bid = $lot->bidBy($playerId);
                $result[] = $bid;
            }
        }
        return $result;
    }

    /**
     * @return array<array<string,mixed>>
     */
    public function marshal(): array {
        return array_map(
            fn ($mt) => [
                'id' => $mt->tile_id,
                'tiletype' => $mt->type->value,
                'activations' => $mt->productionActivations()->marshal(),
                'bids' => array_map(fn ($b, $pid) => [
                        'player_id' => $pid,
                        'workers' => $b->marshal(),
                    ],
                    $mt->bids(),
                    array_keys($mt->bids())
                )
            ],
            $this->lots()
        );
    }

}
