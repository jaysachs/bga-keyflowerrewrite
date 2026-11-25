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
        $ps = Arrays::powerset([1, 0, 2]);
        $this->assertEqualsCanonicalizing($ps, [[], [0], [1], [2], [0,1], [0,2], [1,2], [0,1,2]]);
    }
}
