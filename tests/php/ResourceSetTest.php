<?php

declare(strict_types=1);

use Bga\Games\keyflowerrewrite\Model\{
    Resource,
    ResourceType,
    ResourceSet,
};

final class ResourceSetTest extends TestBase
{
    public function testEmpty() : void {
        $rs = new ResourceSet();

        $this->assertTrue($rs->isEmpty(), "empty set should be empty");
        $this->assertEquals([], $rs->allItems());
        $this->assertEquals(0, $rs->size());
        $this->assertEquals(null, $rs->resourceById(1));
    }

    public function testMarshal(): void {
        $rs = ResourceSet::of(
            new Resource(3, ResourceType::WOOD),
            new Resource(4, ResourceType::WOOD),
            new Resource(5, ResourceType::STONE));

        $this->assertEquals(
            [ [ "id" => 3, "type" => ResourceType::WOOD->value ], [ "id" => 4, "type" => ResourceType::WOOD->value ], [ "id" => 5, "type" => ResourceType::STONE->value ] ],
            $rs->marshal()
        );
    }

    public function testBasic() : void{
        $rs = new ResourceSet();
        $r1 = new Resource(3, ResourceType::WOOD);
        $r2 = new Resource(5, ResourceType::STONE);
        $rs->add($r1);
        $rs->add($r2);

        $this->assertEquals(2, $rs->size());
        $this->assertEquals($r1, $rs->resourceById(3));
        $this->assertEquals($r1, $rs->resourceById(3));
        $this->assertEquals($r2, $rs->resourceById(5));
        $this->assertEquals(null, $rs->resourceById(1));
        $this->assertEquals(2, $rs->size());
    }

    public function testNoDupes(): void {
        $rs = new ResourceSet();
        $r1 = new Resource(3, ResourceType::WOOD);
        $r2 = new Resource(3, ResourceType::STONE);
        $rs->add($r1);
        $this->assertThrows(fn() => $rs->add($r1));
        $this->assertThrows(fn() => $rs->add($r2));
    }

    public function testTakeN(): void {
        $rs = new ResourceSet();
        $r1 = new Resource(3, ResourceType::WOOD);
        $r2 = new Resource(5, ResourceType::STONE);
        $r3 = new Resource(7, ResourceType::WOOD);

        $rs = ResourceSet::of($r1, $r2, $r3);

        $got = $rs->take(ResourceType::STONE);
        $this->assertEquals(2, $rs->size());
        $this->assertEquals(1, $got->size());

        $got = $rs->take(ResourceType::WOOD, 5);
        $this->assertEquals(0, $rs->size());
        $this->assertEquals(2, $got->size());
    }

    public function testAddSet() : void{
        $rs = new ResourceSet();
        $r1 = new Resource(3, ResourceType::WOOD);
        $r2 = new Resource(5, ResourceType::STONE);
        $rs->add($r1);
        $rs->add($r2);

        $rs2 = new ResourceSet();
        $r3 = new Resource(7, ResourceType::WOOD);
        $rs2->add($r3);

        $rs2->addAll($rs);

        $this->assertEquals(2, $rs->size());
        $this->assertEquals(3, $rs2->size());
        $this->assertEquals($r1, $rs2->resourceById(3));
        $this->assertEquals($r2, $rs2->resourceById(5));
        $this->assertEquals($r3, $rs2->resourceById(7));

        $this->assertEquals($r1, $rs->resourceById(3));
        $this->assertEquals($r2, $rs->resourceById(5));
        $this->assertEquals(null, $rs->resourceById(7));
    }
}
