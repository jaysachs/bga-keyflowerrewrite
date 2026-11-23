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

class ResourceSet implements Marshalable {

    /** @var array<string,list<Resource>> */
    private array $resources;

    public function __construct() {
        $this->resources = array_fill_keys(array_map(fn ($t) => $t->value, ResourceType::cases()), []);
    }

    public static function of(Resource... $res): ResourceSet {
        $result = new ResourceSet();
        foreach ($res as $r) {
            $result->put($r);
        }
        return $result;
    }

    /** @return list<Resource> */
    public function allItems(): array {
        $result = [];
        foreach ($this->resources as $t => $rts) {
            array_push($result, ... $rts);
        }
        return $result;
    }

    private function verifyUniqueIds(): void {
        $allIds = array_map(fn (Resource $r) => $r->id, array_merge(... array_values($this->resources)));
        if (count($allIds) != count(array_unique($allIds))) {
            throw new InvalidArgumentException("duplicate resource IDs");
        };
    }

    public function put(Resource $res): void {
        $this->resources[$res->type->value][] = $res;
        $this->verifyUniqueIds();
    }


    public function size(): int {
        return array_reduce($this->resources, fn ($carry, $rts) => $carry + count($rts), 0);
    }

    public function isEmpty(): bool {
        return $this->size() == 0;
    }

    public function putAll(ResourceSet $other): void {
        foreach ($other->resources as $t => $rts) {
            array_shift($this->resources[$t], ... $rts);
        }
        $this->verifyUniqueIds();
    }

    public function take(ResourceType $rt, int $num = 1): ResourceSet {
        $items = array_splice($this->resources[$rt->value], 0, $num);
        $result = new ResourceSet();
        foreach ($items as $resource) {
            $result->put($resource);
        }
        return $result;
    }

    public static function forNewGame(): ResourceSet {
        $lastId = 3000;
        $stock = new ResourceSet();
        foreach (ResourceType::cases() as $rt) {
            $c = $rt == ResourceType::GOLD ? 48 : 24;
            for ($i = 0; $i < $c; ++$i) {
                $stock->put(new Resource(++$lastId, $rt));
            }
        }
        return $stock;
    }

    /** @return array<mixed> */
    public function marshal(): mixed {

        return array_merge(array_map(fn ($rs) => array_map(fn (Resource $r) => $r->marshal(), $rs), $this->resources));
    }
}