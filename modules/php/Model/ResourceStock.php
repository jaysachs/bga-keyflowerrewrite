<?php

/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * zooloretto implementation : © Jay Sachs <vagabond@covariant.org>
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

use Bga\Games\keyflowerrewrite\Utils\Arrays;

class ResourceStock {

    /** @var array<string,list<Resource>> */
    private array $resources;

    /** @param list<Resource> $allresources */
    public function __construct(array $allresources) {
        $this->resources = array_fill_keys(array_map(fn ($t) => $t->value, ResourceType::cases()), []);
        foreach ($allresources as $res) {
            $this->resources[$res->type->value][] = $res;
        }
    }

    /** @return list<Resource> */
    public function & ofType(ResourceType $type): array {
        return $this->resources[$type->value];
    }

    public static function forNewGame(): ResourceStock {
        $lastId = 200;
        $resources = [];
        foreach (ResourceType::cases() as $rt) {
            $c = $rt == ResourceType::GOLD ? 48 : 24;
            for ($i = 0; $i < $c; ++$i) {
                $resources[] = new Resource(++$lastId, $rt);
            }
        }
        return new ResourceStock($resources);
    }
}