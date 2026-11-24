<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

use Bga\Games\keyflowerrewrite\Model\{
    Season,
    TileType,
};

final class TileTypeTest extends TestBase
{
    public function testStandardBoatsFlippable(): void {
        foreach (TileType::initialStandardBoats(6) as $bt) {
            $this->assertTrue($bt->flip() !== null, "boat {$bt->value} should be flippable");
        }
    }

    public function testRoads(): void {
        foreach (TileType::cases() as $tt) {
            $season = $tt->season();
            if ($tt->isTurnOrder()) {
                $this->assertEquals([], $tt->roads());
            }
            else if ($tt->isBoat()) {
                $this->assertEquals([], $tt->roads());
            }
            else if ($tt->isHome()) {
                $this->assertEquals(5, count($tt->roads()));
            }
            else if ($season == Season::SPRING) {
                $this->assertEquals(4, count($tt->roads()));
            }
            else if ($season == Season::SUMMER) {
                $this->assertEquals(3, count($tt->roads()));
            }
            else if ($season == Season::FALL) {
                $this->assertEquals(2, count($tt->roads()));
            }
            else if ($season == Season::WINTER) {
                $this->assertEquals(1, count($tt->roads()));
            } else {
                $this->assertTrue(false, "Unclassified tile {$tt->value}");
            }
        }
    }

    public function testSanityChecks(): void
    {
        $this->assertEquals(6, count(TileType::initialHomeTiles()));

        $sts = TileType::initialSeasonTiles(Season::SPRING);
        $this->assertEquals(12, count($sts));
        $this->assertEquals(0, count(array_filter($sts, fn ($t) => $t->isBoat())));

        $sts = TileType::initialSeasonTiles(Season::SUMMER);
        $this->assertEquals(12, count($sts));
        $this->assertEquals(4, count(array_filter($sts, fn ($t) => $t->isBoat())));
        $this->assertEquals(4, count(array_filter($sts, fn ($t) => $t->isBoat() && $t->flip() !== null)));

        $sts = TileType::initialSeasonTiles(Season::FALL);
        $this->assertEquals(12, count($sts));
        $this->assertEquals(0, count(array_filter($sts, fn ($t) => $t->isBoat())));

        $sts = TileType::initialSeasonTiles(Season::WINTER);
        $this->assertEquals(12, count($sts));
        $this->assertEquals(0, count(array_filter($sts, fn ($t) => $t->isBoat())));

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
            if ($tt->isTurnOrder()) {$i++;}
            foreach (Season::cases() as $season) {
                if ($tt->season() == $season) { $i++; }
            }
            $this->assertTrue($i == 1, "{$tt->value} is $i");
        }
    }

    public function testTranslations(): void {
        foreach (TileType::cases() as $tt) {
            $d = $tt->description();
            $this->assertTrue(strcmp($d["summary"],"") !== 0 || strcmp($d["scoring"] ?? "", "") !== 0,
                              "Tile {$tt->value} does not have either a description summary or scoring");
            $n = $tt->translatedName();
            $this->assertTrue(strcmp($n, "") !== 0, "Tile {$tt->value} does not have a translated name '{$n}'");
        }
    }
}
