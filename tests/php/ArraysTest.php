<?php

declare(strict_types=1);

use Bga\Games\keyflowerrewrite\Utils\{
    Arrays,
};

final class ArraysTest extends TestBase
{
    public function testEmpty() : void {
        $this->assertEquals([[]], Arrays::powerset([]));
    }

    public function testSingleton(): void {
        $this->assertEqualsCanonicalizing([[], [1]], Arrays::powerset([1]));
    }

    public function testThree(): void {
        $this->assertEqualsCanonicalizing([[], [1], [2], [3], [1,2], [1,3], [2,3], [1,2,3]], Arrays::powerset([1,2,3]));
    }
}
