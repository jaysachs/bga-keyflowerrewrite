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

    public function testSanityChecks(): void
    {
        $this->assertEquals(6, count(TileType::initialHomeTiles()));
        $this->assertEquals(12, count(TileType::initialSpringTiles()));
        $this->assertEquals(0, count(array_filter(TileType::initialSpringTiles(), fn ($t) => $t->isBoat())));
        $this->assertEquals(16, count(TileType::initialSummerTiles()));
        $this->assertEquals(8, count(array_filter(TileType::initialSummerTiles(), fn ($t) => $t->isBoat())));
        $this->assertEquals(12, count(TileType::initialFallTiles()));
        $this->assertEquals(0, count(array_filter(TileType::initialFallTiles(), fn ($t) => $t->isBoat())));
        $this->assertEquals(12, count(TileType::initialWinterTiles()));
        $this->assertEquals(0, count(array_filter(TileType::initialWinterTiles(), fn ($t) => $t->isBoat())));

        for ($p = 2; $p <= 6; ++$p) {
            $this->assertEquals($p, count(TileType::initialStandardBoats($p)));
        }
    }

    public function testAllClassified(): void
    {
        foreach (TileType::cases() as $tt) {
            $i = 0;
            if ($tt->isStandardBoat()) {$i++;}
            if ($tt->isHome()) {$i++;}
            if ($tt->isStartPlayer()) {$i++;}
            if ($tt->isSpring()) {$i++;}
            if ($tt->isSummer()) {$i++;}
            if ($tt->isFall()) {$i++;}
            if ($tt->isWinter()) {$i++;}
            $this->assertTrue($i == 1, "{$tt->value} is $i");
        }
    }
}
