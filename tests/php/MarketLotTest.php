<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

use Bga\Games\keyflowerrewrite\Model\{
    MarketLot,
    TileType,
    Worker,
    WorkerColor,
    WorkerGroup,
};

final class MarketLotTest extends TestCase
{
    public function testCreationAndGetters() : void{
        $bids = [98 => WorkerGroup::of(new Worker(3, WorkerColor::BLUE))];
        $mt = new MarketLot(1000, TileType::ALEHOUSE, $bids);
        $this->assertEquals(1000, $mt->tile_id);
        $this->assertEquals(TileType::ALEHOUSE, $mt->type);
        // $this->assertEquals($activations, $mt->activations);
        $this->assertEquals($bids, $mt->bids());
        $this->assertNull($mt->bidBy(97));
        $this->assertEquals($bids[98], $mt->bidBy(98));
    }

    public function testBiddingColor(): void {
        $bids = [98 => WorkerGroup::of(new Worker(3, WorkerColor::BLUE))];
        $mt = new MarketLot(1000, TileType::ALEHOUSE, $bids);
        $this->assertEquals(WorkerColor::BLUE, $mt->currentColor());
        $mt = new MarketLot(1000, TileType::ALEHOUSE);
        $this->assertEquals(WorkerColor::NONE, $mt->currentColor());
    }

    public function testHasNonWinningBid(): void {
        $bids = [98 => WorkerGroup::of(new Worker(3, WorkerColor::BLUE)),
                 97 => WorkerGroup::of(new Worker(4, WorkerColor::BLUE), new Worker(6, WorkerColor::BLUE))];
        $mt = new MarketLot(1000, TileType::ALEHOUSE, $bids);
        $this->assertTrue($mt->hasNonWinningBid(98), "98 has only bid 1 blue");
        $this->assertFalse($mt->hasNonWinningBid(99), "99 has not made a bid");
        $this->assertFalse($mt->hasNonWinningBid(97), "97 has the winning bid");
    }

    public function testHighBid(): void {
        $bids = [98 => WorkerGroup::of(new Worker(3, WorkerColor::BLUE)),
                 97 => WorkerGroup::of(new Worker(4, WorkerColor::BLUE), new Worker(6, WorkerColor::BLUE))];
        $mt = new MarketLot(1000, TileType::ALEHOUSE, $bids);
        $this->assertEquals($bids[97], $mt->highBid());

        $mt = new MarketLot(1000, TileType::ALEHOUSE);
        $this->assertEquals(WorkerGroup::of(), $mt->highBid());
    }

    public function testBidSuccessful(): void {
        $bids = [98 => WorkerGroup::of(new Worker(3, WorkerColor::BLUE)),
                 97 => WorkerGroup::of(new Worker(4, WorkerColor::BLUE), new Worker(5, WorkerColor::BLUE))];

        $mt = new MarketLot(1000, TileType::ALEHOUSE, $bids);
        $this->assertEquals($bids, $mt->bids());
        $newBid = WorkerGroup::of(new Worker(6, WorkerColor::BLUE), new Worker(7, WorkerColor::BLUE));
        $mt->bid(98, $newBid);
        $wg = $bids[98]->combineWith($newBid);
        $this->assertEquals([97 => $bids[97], 98 => $wg], $mt->bids());
        $this->assertEquals($wg, $mt->highBid());
    }

    public function testBidTooLow(): void {
        $bids = [98 => WorkerGroup::of(new Worker(3, WorkerColor::BLUE)),
                 97 => WorkerGroup::of(new Worker(4, WorkerColor::BLUE), new Worker(5, WorkerColor::BLUE))];

        $mt = new MarketLot(1000, TileType::ALEHOUSE);
        $mt->bid(97, $bids[97]);
        try {
            $mt->bid(98, $bids[98]);
            $this->assertTrue(false, "should have failed bid");
        } catch (InvalidArgumentException $e) {
            $this->assertTrue(true, "got expected exception");
        }
   }

    public function donttestBidWrongColor(): void {
        $bids = [98 => WorkerGroup::of(new Worker(3, WorkerColor::YELLOW)),
                 97 => WorkerGroup::of(new Worker(4, WorkerColor::BLUE), new Worker(5, WorkerColor::BLUE))];

        $mt = new MarketLot(1000, TileType::ALEHOUSE);
        $mt->bid(98, $bids[98]);
        // FIXME: so, we can only do limited validation here (need to allow boat4a to take effect)
        //  We can either pass an optional boolean to bid() that says to allow change of color
        //  or, perhaps, take all validation out and have MarketLot be a "data object".
        try {
            $mt->bid(97, $bids[97]);
            $this->assertTrue(false, "should have failed bid");
        } catch (InvalidArgumentException $e) {
            $this->assertTrue(true, "got expected exception");
        }
   }
}
