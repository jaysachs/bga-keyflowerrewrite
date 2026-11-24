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
        $ps = Arrays::powerset([1,2,0]);
        usort($ps, function (array $a, array $b): int {
            if (count($a) < count($b)) {
                return -1;
            }
            if (count($a) > count($b)) {
                return 1;
            }
            sort($a);
            sort($b);
            for ($i = 0; $i < count($a); $i++) {
                $x = $a[$i] - $b[$i];
                if ($x <> 0) { return $x; }
            }
            return 0;
        });
        $this->assertEqualsCanonicalizing($ps, [[], [0], [1], [2], [0,1], [0,2], [1,2], [0,1,2]]);
    }
}
