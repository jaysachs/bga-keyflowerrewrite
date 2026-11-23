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
        $stock = new WorkerStock();
        $stock->putInBag(new Worker(1, WorkerColor::YELLOW));
        $stock->putInBag(new Worker(2, WorkerColor::BLUE));
        $stock->putInBag(new Worker(3, WorkerColor::RED));
        $stock->putGreen(new Worker(4, WorkerColor::GREEN));
        $stock->putGreen(new Worker(5, WorkerColor::GREEN));
        $stock->putGreen(new Worker(6, WorkerColor::GREEN));
        $w = $stock->takeGreens(2);
        $this->assertEquals(2, $w->size());
        $this->assertEquals(1, count($stock->greens()));

        $w = $stock->takeGreens(2);
        $this->assertEquals(1, $w->size());
        $this->assertEquals(0, $stock->greensSize());
    }

    public function testTakeFromStock(): void {
        $stock = new WorkerStock();
        $stock->putInBag(new Worker(1, WorkerColor::YELLOW));
        $stock->putInBag(new Worker(2, WorkerColor::BLUE));
        $stock->putInBag(new Worker(3, WorkerColor::RED));
        $w = $stock->takeFromBag(2);
        $this->assertEquals(2, $w->size());
        $this->assertEquals(1, count($stock->bagWorkers()));

        $w = $stock->takeFromBag(2);
        $this->assertEquals(1, $w->size());
        $this->assertEquals(0, count($stock->bagWorkers()));
    }
}
