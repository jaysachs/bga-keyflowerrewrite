<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

use Bga\Games\keyflowerrewrite\Model\{
    ProductionActivations,
    Worker,
    WorkerColor,
    WorkerGroup,
};

final class ProductionActivationsTest extends TestCase
{
    private function assertThrows(Closure $c, string $msg = "should have thrown exception"): void {
        try {
            $c();
            $this->assertTrue(false, $msg);
        } catch (InvalidArgumentException $e) {
            $this->assertTrue(true, $msg);
        }
    }

    public function testEmpty(): void {
        $pa = new ProductionActivations([]);
        $this->assertTrue($pa->isActivatable(), "empty activations should be activatable");
        $this->assertEquals(6, $pa->maxAllowededToActivate());
        $this->assertEquals(1, $pa->minRequiredToActivate());
        $this->assertEquals(0, $pa->totalWorkers());
        $this->assertEquals(WorkerColor::NONE, $pa->currentColor());
        $this->assertEquals([], $pa->activations());
        $this->assertEquals(0, $pa->lastActivationSize());
        $this->assertEquals(null, $pa->mostRecent());
    }

    private int $lastId = 0;
    private function workerGroup(WorkerColor $color, int $size = 1): WorkerGroup {
        $wg = new WorkerGroup();
        for ($i = 0; $i < $size; ++$i) {
            $wg->add(new Worker($this->lastId++, $color));
        }
        return $wg;
    }

    public function testOneActivation(): void {
        $wg = $this->workerGroup(WorkerColor::BLUE);
        $pa = new ProductionActivations([]);
        $pa->add($wg);

        $this->assertTrue($pa->isActivatable(), "should be activatable");
        $this->assertEquals(5, $pa->maxAllowededToActivate());
        $this->assertEquals(2, $pa->minRequiredToActivate());
        $this->assertEquals(1, $pa->totalWorkers());
        $this->assertEquals(WorkerColor::BLUE, $pa->currentColor());
        $this->assertEquals([$wg], $pa->activations());
        $this->assertEquals(1, $pa->lastActivationSize());
        $this->assertEquals($wg, $pa->mostRecent());

        $this->assertThrows(fn () => $pa->add($wg));
    }

    public function testTwoActivations(): void {
        $wg1 = $this->workerGroup(WorkerColor::BLUE);
        $wg2 = $this->workerGroup(WorkerColor::BLUE, 2);
        $pa = new ProductionActivations([]);
        $pa->add($wg1);
        $pa->add($wg2);
        $this->assertTrue($pa->isActivatable(), "should be activatable");
        $this->assertEquals(3, $pa->maxAllowededToActivate());
        $this->assertEquals(3, $pa->minRequiredToActivate());
        $this->assertEquals(3, $pa->totalWorkers());
        $this->assertEquals(WorkerColor::BLUE, $pa->currentColor());
        $this->assertEquals([$wg1, $wg2], $pa->activations());
        $this->assertEquals(2, $pa->lastActivationSize());
        $this->assertEquals($wg2, $pa->mostRecent());

        $this->assertThrows(fn () => $pa->add($wg1));
        $this->assertThrows(fn () => $pa->add($wg2));
    }

    public function testThreeActivations(): void {
        $wg1 = $this->workerGroup(WorkerColor::BLUE);
        $wg2 = $this->workerGroup(WorkerColor::BLUE, 2);
        $wg3 = $this->workerGroup(WorkerColor::BLUE, 3);
        $pa = new ProductionActivations([]);
        $pa->add($wg1);
        $pa->add($wg2);
        $pa->add($wg3);

        $this->assertFalse($pa->isActivatable(), "should not be activatable");
        $this->assertEquals(0, $pa->maxAllowededToActivate());
        $this->assertEquals(4, $pa->minRequiredToActivate());
        $this->assertEquals(6, $pa->totalWorkers());
        $this->assertEquals(WorkerColor::BLUE, $pa->currentColor());
        $this->assertEquals([$wg1, $wg2, $wg3], $pa->activations());
        $this->assertEquals(3, $pa->lastActivationSize());
        $this->assertEquals($wg3, $pa->mostRecent());

        $this->assertThrows(fn () => $pa->add($wg1));
        $this->assertThrows(fn () => $pa->add($wg2));
        $this->assertThrows(fn () => $pa->add($wg3));
        $this->assertThrows(fn () => $pa->add($this->workerGroup(WorkerColor::BLUE, 4)));
    }

    public function testFirstActivation2(): void {
        $wg2 = $this->workerGroup(WorkerColor::BLUE, 2);
        $pa = new ProductionActivations([]);
        $pa->add($wg2);

        $this->assertTrue($pa->isActivatable(), "should be activatable");
        $this->assertEquals(4, $pa->maxAllowededToActivate());
        $this->assertEquals(3, $pa->minRequiredToActivate());
        $this->assertEquals(2, $pa->totalWorkers());
        $this->assertEquals(WorkerColor::BLUE, $pa->currentColor());
        $this->assertEquals([$wg2], $pa->activations());
        $this->assertEquals(2, $pa->lastActivationSize());
        $this->assertEquals($wg2, $pa->mostRecent());
    }

    public function testFirstActivation4(): void {
        $wg = $this->workerGroup(WorkerColor::BLUE, 4);
        $pa = new ProductionActivations([]);
        $pa->add($wg);

        $this->assertFalse($pa->isActivatable(), "should not be activatable");
        $this->assertEquals(2, $pa->maxAllowededToActivate());
        $this->assertEquals(5, $pa->minRequiredToActivate());
        $this->assertEquals(4, $pa->totalWorkers());
        $this->assertEquals(WorkerColor::BLUE, $pa->currentColor());
        $this->assertEquals([$wg], $pa->activations());
        $this->assertEquals(4, $pa->lastActivationSize());
        $this->assertEquals($wg, $pa->mostRecent());
    }
}