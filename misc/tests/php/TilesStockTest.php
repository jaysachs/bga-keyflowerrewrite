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
        $this->assertTrue(array_reduce($a, fn ($carry, $t) => $carry && $c($t), true), $msg);
    }

    public function testForNewGame_summerTiles(): void {
        $stock = TilesStock::forNewGame(6);
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
            fn (TileType $t) =>  ! $t->isBoat() ||
                /** @phpstan-ignore property.nonObject */
                array_search($t->flip()->value, $summerTileValues) === false,
            "summer tiles should all be non-boats or not a the flip side of an existing boat");
    }
}
