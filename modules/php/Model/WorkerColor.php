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


enum WorkerColor: string implements Marshalable {
    case BLUE = "blue";
    case RED = "red";
    case YELLOW = "yellow";
    case GREEN = "green";
    case ONE = "one";
    case MANY = "many";
    case NONE = "none";

    public function translatedName(): string {
        return match ($this) {
            self::BLUE => clienttranslate("blue"),
            self::RED => clienttranslate("red"),
            self::YELLOW => clienttranslate("yellow"),
            self::GREEN => clienttranslate("green"),
            self::ONE => clienttranslate("one"),
            self::MANY => clienttranslate("many"),
            self::NONE => clienttranslate("none"),
        };
    }

    public function isConcrete(): bool {
        return match ($this) {
            self::ONE, self::MANY, self::NONE => false,
            default => true
        };
    }

    /** @return list<WorkerColor> */
    public static function concreteCases(): array {
        return array_values(array_filter(self::cases(), fn (WorkerColor $s) => $s->isConcrete()));
    }

    // "satisfies"
    public function matches(WorkerColor $other): bool {
        // FIXME: think about the right semantics for ANY and NONE
        if ($this === self::NONE) {
            return true;
        }
        if (!$this->isConcrete()) {
            return false;
        }
        if ($other === self::ONE || $other === self::NONE || $other === self::MANY) {
            return true;
        }
        return $this === $other;
    }

    // FIXME: not clear how to handle non-concrete ones
    public function plus(WorkerColor $other): WorkerColor {
        if ($this->isConcrete()) {
            if ($other === $this) {
                return $this;
            }
            if ($other->isConcrete()) {
                return WorkerColor::MANY;
            }
            if ($other === WorkerColor::ONE || $other === WorkerColor::NONE) {
                return $this;
            }
            // if ($other == WorkerColor::MANY) {
            return $other;
        }
        if ($this === WorkerColor::NONE) {
            return $other;
        }
        if ($this == WorkerColor::MANY) {
            return $this;
        }
        // $this == WorkerColor::ONE
        return $other;
    }

    public function marshal(): ?string {
        return match ($this) {
            self::ONE, self::MANY,self::NONE => null,
            default => $this->value
        };
    }
}
