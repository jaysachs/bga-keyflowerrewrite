<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

use Bga\Games\keyflowerrewrite\Model\{
    TileType,
};

final class TileTypeTest extends TestCase
{
    public function testRoads(): void {
        foreach (TileType::cases() as $tt) {
            if ($tt->isStartPlayer()) {
                $this->assertEquals([], $tt->roads());
            }
            else if ($tt->isBoat()) {
                $this->assertEquals([], $tt->roads());
            }
            else if ($tt->isHome()) {
                $this->assertEquals(5, count($tt->roads()));
            }
            else if ($tt->isSpring()) {
                $this->assertEquals(4, count($tt->roads()));
            }
            else if ($tt->isSummer()) {
                $this->assertEquals(3, count($tt->roads()));
            }
            else if ($tt->isFall()) {
                $this->assertEquals(2, count($tt->roads()));
            }
            else if ($tt->isWinter()) {
                $this->assertEquals(1, count($tt->roads()));
            } else {
                $this->assertTrue(false, "Unclassified tile {$tt->value}");
            }
        }
    }

    public function testAllClassified(): void
    {
        foreach (TileType::cases() as $tt) {
            $i = 0;
            if ($tt->isStandardBoat()) {$i++;}
            if ($tt->isHome()) {$i++;}
            if ($tt->isStartPlayer()) {$i++;}
            if (array_search($tt, TileType::springTiles()) !== false) {$i++;}
            if (array_search($tt, TileType::summerTiles()) !== false) {$i++;}
            if (array_search($tt, TileType::fallTiles()) !== false) {$i++;}
            if (array_search($tt, TileType::winterTiles()) !== false) {$i++;}
            $this->assertTrue($i == 1, "{$tt->value} is $i");
        }
    }
}
