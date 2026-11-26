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

class SkillSet implements Marshalable {

    /** @var array<int,Skill> */
    private array $skills;

    /** @param array<Skill> $skills */
    public function __construct(array $skills = [])
    {
        $this->skills = [];
        foreach ($skills as $skill) {
            $this->add($skill);
        }
    }

    public static function forNewGame() : SkillSet {
        $lastId = 4000;
        $ss = new SkillSet();
        foreach (SkillType::cases() as $st) {
            for ($i = 0; $i < 16; ++$i) {
                $ss->add(new Skill(++$lastId, $st));
            }
        }
        return $ss;
    }

    /** @return SkillSet */
    public function takeN(int $n): SkillSet {
        /** @var list<int> */
        $ids = array_keys($this->skills);
        Arrays::shuffle($ids);
        $ss = new SkillSet();
        if ($n > count($ids)) {
            $n = count($ids);
        }
        for ($i = 0; $i < $n; $i++) {
            /** @var int $id */
            $id = array_shift($ids);
            $ss->add($this->skills[$id]);
            unset($this->skills[$id]);
        }
        return $ss;
    }

    public function hasMatching(SkillType $type): bool {
        return array_find($this->skills, fn ($sk) => $type->matches($sk->type)) !== null;
    }

    public static function of(Skill... $ws): SkillSet {
        return new SkillSet($ws);
    }

    public function isEmpty(): bool {
        return count($this->skills) == 0;
    }

    public function takeSkillById(int $id): Skill {
        $skill = $this->skillById($id);
        if (!$skill) {
            throw new InvalidArgumentException("no skill with id {$id}");
        }
        unset($this->skills[$id]);
        return $skill;
    }

    public function skillById(int $id): ?Skill {
        return isset($this->skills[$id]) ? $this->skills[$id] : null;
    }

    public function add(Skill $skill): void {
        if (isset($this->skills[$skill->id])) {
            throw new InvalidArgumentException("cannot add skill twice to set");
        }
        $this->skills[$skill->id] = $skill;
    }

    /* does not mutate this */
    public function addSet(SkillSet $other): void {
        foreach ($other->skills as $s) {
            $this->add($s);
        }
    }

    /** @return array<int,Skill> */
    public function allItems(): array {
        return $this->skills;
    }

    public function size(): int {
        return count($this->skills);
    }

    public function marshal(): mixed
    {
        return array_map(fn ($s) => $s->marshal(), array_values($this->skills));
    }
}
