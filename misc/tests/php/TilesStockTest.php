<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

use Bga\Games\keyflowerrewrite\Model\{
    Season,
    TilesStock,
    TileType,
};

final class TilesStockTest extends TestCase
{
    /**  @param array<mixed> $a */
    private function assertAllTrue(array $a, \Closure $c, string $msg): void {
        $this->assertTrue(self::allTrue($a, $c), $msg);
    }

    /** @param array<mixed> $a */
    private static function allTrue(array $a, \Closure $c): bool {
        return array_reduce($a, fn ($carry, $t) => $carry && $c($t), true);
    }

    public function testForNewGame_summerTiles(): void {
        $stock = TilesStock::forNewGame(6);
        /** @var list<TileType> */
        $summerTileTypes = array_map(fn ($t) => $t->type, $stock->forSeason(Season::SUMMER));
        $summerTileValues = array_map(fn ($t) => $t->value, $summerTileTypes);
        $this->assertTrue(count($summerTileTypes) == 10, "should be 10 summer tiles for 6p");
        $this->assertTrue(count(array_unique($summerTileValues)) == 10, "should be 6 unique summer tiles for 6");
        $this->assertTrue(count(array_filter($summerTileTypes, fn ($t) => $t->isBoat())) >= 2, "with 6p, there should be at least 2 boats");
        $this->assertTrue(count(array_filter($summerTileTypes, fn ($t) => $t->isBoat())) <= 4, "with 6p, there should be at most 4 boats");
        $this->assertAllTrue(
            $summerTileTypes,
            fn (TileType $t) => $t->isSummer(),
            "summer tiles should be ... summer tiles");
        $this->assertAllTrue(
            $summerTileTypes,
            function (TileType $t) use (&$summerTileTypes) {
                if (! $t->isBoat()) { return true; }
                // if it's a flipped boat, ensure no other summer tile flips to this tone.
                $flipped = $t->flip();
                if ($flipped === null) {
                    return self::allTrue($summerTileTypes, fn(TileType $t2) => $t2->flip() !== $t);
                }
                // if a non-flipped boat, ensure we don't flip to another existing summer tile
                return self::allTrue($summerTileTypes, fn($t2) => $t2 !== $flipped);
            },
            "summer tiles should all be non-boats or not a the flip side of an existing boat");
    }
}
