<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

use Bga\Games\keyflowerrewrite\Model\{
    Worker,
    WorkerStock,
    WorkerColor,
};

final class WorkerStockTest extends TestCase
{
    public function testTakeFromGreens(): void {
        $stock = new WorkerStock([
            new Worker(1, WorkerColor::YELLOW),
            new Worker(2, WorkerColor::BLUE),
            new Worker(3, WorkerColor::RED)
        ], [
            new Worker(4, WorkerColor::GREEN),
            new Worker(5, WorkerColor::GREEN),
            new Worker(6, WorkerColor::GREEN),
        ]);
        $w = $stock->takeGreens(2);
        $this->assertEquals(2, count($w));
        $this->assertEquals(1, count($stock->greens()));

        $w = $stock->takeGreens(2);
        $this->assertEquals(1, count($w));
        $this->assertEquals(0, $stock->greensSize());
    }

    public function testTakeFromStock(): void {
        $stock = new WorkerStock([
            new Worker(1, WorkerColor::YELLOW),
            new Worker(2, WorkerColor::BLUE),
            new Worker(3, WorkerColor::RED)
        ], []);
        $w = $stock->takeFromBag(2);
        $this->assertEquals(2, count($w));
        $this->assertEquals(1, count($stock->bagWorkers()));

        $w = $stock->takeFromBag(2);
        $this->assertEquals(1, count($w));
        $this->assertEquals(0, count($stock->bagWorkers()));
    }
}
