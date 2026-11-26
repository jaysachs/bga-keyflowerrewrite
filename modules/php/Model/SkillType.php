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


enum SkillType: string {
    case ANVIL = "anvil";
    case PICKAXE = "pickaxe";
    case SAW = "saw";
    case ANY = "any";
    case NONE = "none";

    public function isConcrete(): bool {
        return match ($this) {
            self::ANY, self::NONE => false,
            default => true
        };
    }

    /** @return list<SkillType> */
    public static function concreteCases(): array {
        return array_values(array_filter(self::cases(), fn (SkillType $s) => $s->isConcrete()));
    }

    // "satisfies"
    public function matches(SkillType $other): bool {
        // FIXME: think about the right semantics for ANY and NONE
        if (!$this->isConcrete()) {
            return false;
        }
        if ($other === self::ANY || $other == self::NONE) {
            return true;
        }
        return $this === $other;
    }

    public function translatedName(): string {
        return match ($this) {
            SkillType::ANVIL => clienttranslate("anvil"),
            SkillType::PICKAXE => clienttranslate("pickaxe"),
            SkillType::SAW => clienttranslate("saw"),
            SkillType::ANY => "",
            SkillType::NONE => "",
        };
    }

    public function marshal(): ?string {
        return match ($this) {
            self::ANY, self::NONE => null,
            default => $this->value
        };
    }
}
