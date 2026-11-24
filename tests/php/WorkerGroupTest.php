<?php

declare(strict_types=1);

use Bga\Games\keyflowerrewrite\Model\{
    Worker,
    WorkerColor,
    WorkerGroup,
};

final class WorkerGroupTest extends TestBase
{
    public function testEmpty() : void {
        $wg = new WorkerGroup();

        $this->assertTrue($wg->isEmpty(), "empty set should be empty");
        $this->assertEquals([], $wg->allItems());
        $this->assertEquals(0, $wg->size());
        $this->assertEquals(null, $wg->workerById(1));
    }

    public function testMarshal(): void {
        $wg = WorkerGroup::of(
            new Worker(3, WorkerColor::RED),
            new Worker(4, WorkerColor::BLUE),
            new Worker(5, WorkerColor::RED));

        $this->assertEquals(
            [ [ "id" => 3, "color" => WorkerColor::RED->value ], [ "id" => 4, "color" => WorkerColor::BLUE->value ], [ "id" => 5, "color" => WorkerColor::RED->value ] ],
            $wg->marshal()
        );
    }

    public function testBasic() : void{
        $wg = new WorkerGroup();
        $w1 = new Worker(3, WorkerColor::BLUE);
        $w2 = new Worker(5, WorkerColor::RED);
        $wg->add($w1);
        $wg->add($w2);

        $this->assertEquals(2, $wg->size());
        $this->assertEquals($w1, $wg->workerById(3));
        $this->assertEquals($w1, $wg->workerById(3));
        $this->assertEquals($w2, $wg->workerById(5));
        $this->assertEquals(null, $wg->workerById(1));
        $this->assertEquals(2, $wg->size());
    }

    public function testAddAndTake(): void {
        $wg = new WorkerGroup();
        $w1 = new Worker(3, WorkerColor::BLUE);
        $w2 = new Worker(5, WorkerColor::RED);
        $wg->add($w1);
        $wg->add($w2);

        $this->assertEquals(2, $wg->size());
        $this->assertEquals($w1, $wg->takeWorkerById(3));
        $this->assertEquals(null, $wg->workerById(3));
        $this->assertThrows(fn() => $wg->takeWorkerById(3));
        $this->assertEquals(1, $wg->size());
        $this->assertEquals($w2, $wg->takeWorkerById(5));
        $this->assertTrue($wg->isEmpty(), "should be empty");
        $this->assertEquals(0, $wg->size());
    }

    public function testNoDupes(): void {
        $wg = new WorkerGroup();
        $w1 = new Worker(3, WorkerColor::BLUE);
        $w2 = new Worker(3, WorkerColor::RED);
        $wg->add($w1);

        $this->assertThrows(fn() => $wg->add($w1));
        $this->assertThrows(fn() => $wg->add($w2));
    }

    public function testTakeN(): void {
        $wg = new WorkerGroup();
        $w1 = new Worker(3, WorkerColor::BLUE);
        $w2 = new Worker(5, WorkerColor::RED);
        $w3 = new Worker(7, WorkerColor::BLUE);

        $wg = WorkerGroup::of($w1, $w2, $w3);

        $got = $wg->takeNWorkersOfColor(1, WorkerColor::RED);
        $this->assertEquals(2, $wg->size());
        $this->assertEquals(1, $got->size());

        $got = $wg->takeNWorkersOfColor(5, WorkerColor::BLUE);
        $this->assertEquals(0, $wg->size());
        $this->assertEquals(2, $got->size());
    }

    public function testAddSet() : void{
        $wg = new WorkerGroup();
        $w1 = new Worker(3, WorkerColor::BLUE);
        $w2 = new Worker(5, WorkerColor::RED);
        $wg->add($w1);
        $wg->add($w2);

        $wg2 = new WorkerGroup();
        $w3 = new Worker(7, WorkerColor::BLUE);
        $wg2->add($w3);

        $wg2->addGroup($wg);

        $this->assertEquals(2, $wg->size());
        $this->assertEquals(3, $wg2->size());
        $this->assertEquals($w1, $wg2->workerById(3));
        $this->assertEquals($w2, $wg2->workerById(5));
        $this->assertEquals($w3, $wg2->workerById(7));

        $this->assertEquals($w1, $wg->workerById(3));
        $this->assertEquals($w2, $wg->workerById(5));
        $this->assertEquals(null, $wg->workerById(7));
    }
}
