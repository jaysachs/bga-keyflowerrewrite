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

use Override;

enum WorkerColor: string implements Marshalable {
    case BLUE = "blue";
    case RED = "red";
    case YELLOW = "yellow";
    case GREEN = "green";
    case ANY = "any";
    case NONE = "none";

    public function translatedName(): string {
        return match ($this) {
            self::BLUE => clienttranslate("blue"),
            self::RED => clienttranslate("red"),
            self::YELLOW => clienttranslate("yellow"),
            self::GREEN => clienttranslate("green"),
            self::ANY => clienttranslate("any"),
            self::NONE => clienttranslate("none"),
        };
    }

    public function isConcrete(): bool {
        return match ($this) {
            self::BLUE, self::RED, self::YELLOW, self::GREEN => true,
            default => false
        };
    }

    /** @return list<WorkerColor> */
    public static function concreteCases(): array {
        return [self::BLUE, self::RED, self::YELLOW, self::GREEN];
    }

    #[Override]
    public function marshal(): ?string {
        return match ($this) {
            self::ANY,self::NONE => null,
            default => $this->value
        };
    }
}
