<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class TestBase extends TestCase
{
    protected function assertThrows(Closure $c, string $msg = "should have thrown exception"): void {
        try {
            $c();
            $this->assertTrue(false, $msg);
        } catch (InvalidArgumentException $e) {
            $this->assertTrue(true, $msg);
        }
    }

    /**  @param array<mixed> $a */
    protected function assertAllTrue(array $a, \Closure $c, string $msg): void {
        $this->assertTrue(self::allTrue($a, $c), $msg);
    }

    /** @param array<mixed> $a */
    protected static function allTrue(array $a, \Closure $c): bool {
        return array_reduce($a, fn ($carry, $t) => $carry && $c($t), true);
    }


}
