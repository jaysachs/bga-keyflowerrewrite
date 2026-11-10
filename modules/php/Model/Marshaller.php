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

namespace Bga\Games\keyflowerrewrite\Model;

use Bga\Games\keyflowerrewrite\Model\Dock;
use Bga\Games\keyflowerrewrite\Model\Market;
use Bga\Games\keyflowerrewrite\Model\Village;

class Marshaller {

    /** @return array<mixed> */
    private static function worker(Worker $w): array {
        return [
            'id' => $w->id,
            'color' => $w->type->value,
        ];
    }

    /**
     * @param array<int,Worker> $ws
     * @return array<mixed>
     */
    private static function workers(array $ws): array {
        return array_map(fn ($w) => self::worker($w), $ws);
    }

    /** @return array<mixed> */
    private static function skill(Skill $s): array {
        return [
            'id' => $s->id,
            'type' => $s->type->value,
        ];
    }

    /**
     * @param array<int,Skill> $ss
     * @return array<mixed>
     */
    private static function skills(array $ss): array {
        return array_map(fn ($s) => self::skill($s), $ss);
    }

    /**
     * @return array<mixed>
     */
    public static function village(Village $village, bool $allData = false): array {
        $result = [
            'tiles' => array_map(
                fn ($vt) => [
                    'tile_id' => $vt->id,
                    'tiletype' => $vt->type->value,
                    'rowcol' => $vt->rowcol,
                    'orientation' => $vt->orientation,
                ],
                $village->tiles
            ),
            'player_id' => $village->playerId,
        ];
        if ($allData) {
            $result['workers'] = self::workers($village->workers);
            $result['skills'] = self::skills($village->skills);
        }
        return $result;
    }

    /**
     * @return array<mixed>
     */
    public static function dock(Dock $dock): array {
        return array_map(
            fn ($b) => [
                'id' => $b->id,
                'boattype' => $b->type->value,
                // FIXME: need positions
                'passengers' => self::workers($b->passengers),
                'cargo' => self::skills($b->cargo)
            ],
            $dock->boats
        );
    }

    /**
     * @return array<mixed>
     */
    public static function market(Market $market): array {
        return array_map(
            fn ($mt) => [
                'id' => $mt->id,
                'tiletype' => $mt->type->value,
                'bids' => array_map(fn ($b) => [
                    'player_id' => $b->playerId,
                    // FIXME: need positions?
                    'workers' => self::workers($b->workers),
                ], $mt->bids)
            ],
            $market->tiles()
        );
    }
}