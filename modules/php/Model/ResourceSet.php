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

use Bga\Games\keyflowerrewrite\Utils\Arrays;
use InvalidArgumentException;

class ResourceSet implements Marshalable {

    /** @var array<int,Resource> */
    private array $resources;

    public function __construct() {
        $this->resources = [];
    }

    public static function of(Resource... $res): ResourceSet {
        $result = new ResourceSet();
        foreach ($res as $r) {
            $result->add($r);
        }
        return $result;
    }

    /** @return list<Resource> */
    public function allItems(): array {
        return array_values($this->resources);
    }

    public function add(Resource $res): void {
        if (isset($this->resources[$res->id])) {
            throw new InvalidArgumentException("cannot add resource twice to set");
        }
        $this->resources[$res->id] = $res;
    }


    public function size(): int {
        return count($this->resources);
    }

    public function isEmpty(): bool {
        return $this->size() == 0;
    }

    public function addAll(ResourceSet $other): void {
        foreach ($other->resources as $res) {
            $this->add($res);
        }
    }

    public function takeResourceById(int $id): Resource {
        $res = $this->resourceById($id);
        if (!$res) {
            throw new InvalidArgumentException("no resource with id {$id}");
        }
        unset($this->resources[$id]);
        return $res;
    }

    public function resourceById(int $id): ?Resource {
        return isset($this->resources[$id]) ? $this->resources[$id] : null;
    }

    public function take(ResourceType $type, int $n = 1): ResourceSet {
        $result = new ResourceSet();
        if ($n <= 0) {
            return $result;
        }

        $ids = array_keys($this->resources);
        Arrays::shuffle($ids);

        while ($n > 0) {
            $id = array_shift($ids);
            if ($id === null) {
                break;
            }
            $r = $this->resources[$id];
            if ($r->type == $type) {
                $n--;
                $result->add($r);
            }
        }
        foreach ($result->allItems() as $r) {
            unset($this->resources[$r->id]);
        }
        return $result;
    }

    public static function forNewGame(): ResourceSet {
        $lastId = 3000;
        $stock = new ResourceSet();
        foreach (ResourceType::cases() as $rt) {
            $c = $rt == ResourceType::GOLD ? 48 : 24;
            for ($i = 0; $i < $c; ++$i) {
                $stock->add(new Resource(++$lastId, $rt));
            }
        }
        return $stock;
    }

    /** @return array<mixed> */
    public function marshal(): mixed {

        return array_merge(array_map(fn (Resource $r) => $r->marshal(), array_values($this->resources)));
    }
}