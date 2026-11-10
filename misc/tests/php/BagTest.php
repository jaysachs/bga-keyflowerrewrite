<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

use Bga\Games\keyflowerrewrite\Model\{
    Bag,
    TileType,
    Worker,
    WorkerType,
};

final class BagTest extends TestCase
{
    public function testTake(): void {
        $bag = new Bag([
            new Worker(1, WorkerType::YELLOW),
            new Worker(2, WorkerType::BLUE),
            new Worker(3, WorkerType::RED)
        ]);
        $w = $bag->take(2);
        $this->assertEquals(2, count($w));
        $this->assertEquals(1, count($bag->workers));

        $w = $bag->take(2);
        $this->assertEquals(1, count($w));
        $this->assertEquals(0, count($bag->workers));
    }
}
