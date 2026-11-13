<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

use Bga\Games\keyflowerrewrite\Model\{
    MarketTile,
    TileType,
    Worker,
    WorkerColor,
    WorkerGroup,
};

final class MarketTileTest extends TestCase
{
    public function testCreationAndGetters() : void{
        $activations = [new WorkerGroup([new Worker(2, WorkerColor::RED)])];
        $bids = [98 => new WorkerGroup([new Worker(3, WorkerColor::BLUE)])];
        $mt = new MarketTile(1000, TileType::ALEHOUSE, $activations, $bids);
        $this->assertEquals(1000, $mt->id);
        $this->assertEquals(TileType::ALEHOUSE, $mt->type);
        $this->assertEquals($activations, $mt->activations);
        $this->assertEquals($bids, $mt->bids);
        $this->assertNull($mt->bidBy(97));
        $this->assertEquals($bids[98], $mt->bidBy(98));
    }

    public function testAddToBid(): void {
        $bids = [98 => new WorkerGroup([new Worker(3, WorkerColor::BLUE)])];
        $mt = new MarketTile(1000, TileType::ALEHOUSE, [], $bids);
        $mt->addToBid(98, new Worker(4, WorkerColor::BLUE));
        $this->assertEquals([98 => new WorkerGroup([new Worker(3, WorkerColor::BLUE), new Worker(4, WorkerColor::BLUE) ])], $mt->bids);

        // FIXME: should this error?
        $mt->addToBid(97, new Worker(6, WorkerColor::BLUE));
        $this->assertEquals([97 => new WorkerGroup([new Worker(6, WorkerColor::BLUE)]), 98 => new WorkerGroup([new Worker(3, WorkerColor::BLUE), new Worker(4, WorkerColor::BLUE) ])], $mt->bids);
    }

    public function testBiddingColor(): void {
        $bids = [98 => new WorkerGroup([new Worker(3, WorkerColor::BLUE)])];
        $mt = new MarketTile(1000, TileType::ALEHOUSE, [], $bids);
        $this->assertEquals(WorkerColor::BLUE, $mt->biddingColor());
        $mt = new MarketTile(1000, TileType::ALEHOUSE, [], []);
        $this->assertNull($mt->biddingColor());
    }

    public function testHasNonWinningBid(): void {
        $bids = [98 => new WorkerGroup([new Worker(3, WorkerColor::BLUE)]),
                 97 => new WorkerGroup([new Worker(4, WorkerColor::BLUE), new Worker(6, WorkerColor::BLUE)])];
        $mt = new MarketTile(1000, TileType::ALEHOUSE, [], $bids);
        $this->assertTrue($mt->hasNonWinningBid(98), "98 has only bid 1 blue");
        $this->assertFalse($mt->hasNonWinningBid(99), "99 has not made a bid");
        $this->assertFalse($mt->hasNonWinningBid(97), "97 has the winning bid");
    }

    public function testHighBid(): void {
        $bids = [98 => new WorkerGroup([new Worker(3, WorkerColor::BLUE)]),
                 97 => new WorkerGroup([new Worker(4, WorkerColor::BLUE), new Worker(6, WorkerColor::BLUE)])];
        $mt = new MarketTile(1000, TileType::ALEHOUSE, [], $bids);
        $this->assertEquals($bids[97], $mt->highBid());

        $mt = new MarketTile(1000, TileType::ALEHOUSE, [], []);
        $this->assertEquals(new WorkerGroup([]), $mt->highBid());
    }

    public function testBidSuccessful(): void {
        $bids = [98 => new WorkerGroup([new Worker(3, WorkerColor::BLUE)]),
                 97 => new WorkerGroup([new Worker(4, WorkerColor::BLUE), new Worker(5, WorkerColor::BLUE)])];

        $mt = new MarketTile(1000, TileType::ALEHOUSE, [], []);
        $mt->bid(98, $bids[98]);
        $mt->bid(97, $bids[97]);
        $this->assertEquals($bids, $mt->bids);
        $newBid = new WorkerGroup([new Worker(6, WorkerColor::BLUE), new Worker(7, WorkerColor::BLUE)]);
        $mt->bid(98, $newBid);
        $wg = new WorkerGroup(array_merge($bids[98]->workers(), $newBid->workers()));
        $this->assertEquals([97 => $bids[97], 98 => $wg], $mt->bids);
        $this->assertEquals($wg, $mt->highBid());
    }

    public function testBidTooLow(): void {
        $bids = [98 => new WorkerGroup([new Worker(3, WorkerColor::BLUE)]),
                 97 => new WorkerGroup([new Worker(4, WorkerColor::BLUE), new Worker(5, WorkerColor::BLUE)])];

        $mt = new MarketTile(1000, TileType::ALEHOUSE, [], []);
        $mt->bid(97, $bids[97]);
        try {
            $mt->bid(98, $bids[98]);
            $this->assertTrue(false, "should have failed bid");
        } catch (InvalidArgumentException $e) {
            $this->assertTrue(true, "got expected exception");
        }
   }

    public function donttestBidWrongColor(): void {
        $bids = [98 => new WorkerGroup([new Worker(3, WorkerColor::YELLOW)]),
                 97 => new WorkerGroup([new Worker(4, WorkerColor::BLUE), new Worker(5, WorkerColor::BLUE)])];

        $mt = new MarketTile(1000, TileType::ALEHOUSE, [], []);
        $mt->bid(98, $bids[98]);
        // FIXME: so, we can only do limited validation here (need to allow boat4a to take effect)
        //  We can either pass an optional boolean to bid() that says to allow change of color
        //  or, perhaps, take all validation out and have MarketTile be a "data object".
        try {
            $mt->bid(97, $bids[97]);
            $this->assertTrue(false, "should have failed bid");
        } catch (InvalidArgumentException $e) {
            $this->assertTrue(true, "got expected exception");
        }
   }
   }
