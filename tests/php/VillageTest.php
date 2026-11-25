<?php

declare(strict_types=1);

use Bga\Games\keyflowerrewrite\Model\{
    MarketLot,
    SkillSet,
    TileType,
    Village,
    VillageTile,
    Worker,
    WorkerColor,
    WorkerGroup
};

final class VillageTest extends TestBase {
    const PLAYER_ID = 42;
    const TILE_ID = 1001;


    private Village $village;

    public function setUp(): void {
        $this->village = new Village(self::PLAYER_ID, [], WorkerGroup::of(), SkillSet::of(), [], [], false);
    }

    private int $lastWorkerId = 5000;

    private function makeWorker(WorkerColor $color): Worker{
        return new Worker($this->lastWorkerId++, $color);
    }

    // private function workerGroupMixed(WorkerColor ... $color ): WorkerGroup {
    //     return WorkerGroup::of(... array_map(fn ($c) => $this->makeWorker($c), $color));
    // }

    private function workerGroup(WorkerColor $color, int $n = 1): WorkerGroup {
        return WorkerGroup::of(... array_map(fn ($c) => $this->makeWorker($color), range(1, $n)));
    }

    private int $lastTileId = 4000;
    private function makeTile(TileType $type, int $playerId = self::PLAYER_ID): VillageTile {
        return new VillageTile($this->lastTileId++, $type, 100, 1, self::PLAYER_ID);
    }

    private function makeLot(TileType $type): MarketLot {
        return new MarketLot($this->lastTileId++, $type);
    }

    public function testCannotActivate_unactivated_noStock_noBids_noMovableBids(): void {
        $tile = $this->makeTile(TileType::ALEHOUSE);

        $this->assertFalse($this->village->canActivate($tile, []), "no workers or movable bids shouldn't be able to activate");
    }

    public function testCanActivate_unactivated_stock_noBids_noMovableBids(): void {
        $tile = $this->makeTile(TileType::ALEHOUSE);
        $this->village->workers()->add($this->makeWorker(WorkerColor::BLUE));

        $this->assertTrue($this->village->canActivate($tile, []), "worker in stock can activate an empty tile");
    }

    public function testCanActivate_unactivated_stock_existingBid_noMovableBids(): void {
        $tile = $this->makeLot(TileType::ALEHOUSE);
        $tile->bid(self::PLAYER_ID, WorkerGroup::of($this->makeWorker(WorkerColor::BLUE)));

        $this->village->workers()->add($this->makeWorker(WorkerColor::BLUE));

        $this->assertTrue($this->village->canActivate($tile, []), "worker in stock can activate an tile with a same color bid");
    }

    public function testCannotActivate_unactivated_stock_existingBid_noMovableBids(): void {
        $tile = $this->makeLot(TileType::ALEHOUSE);
        $tile->bid(self::PLAYER_ID, WorkerGroup::of($this->makeWorker(WorkerColor::RED)));

        $this->village->workers()->add($this->makeWorker(WorkerColor::BLUE));

        $this->assertFalse($this->village->canActivate($tile, []), "worker in stock cannot activate a tile with different color bid");
    }

    public function testCannotActivate_activated_stockInsufficient_existingActivation_noMovableBids(): void {
        $tile = $this->makeTile(TileType::ALEHOUSE);
        $tile->productionActivations()->add($this->workerGroup(WorkerColor::BLUE));

        $this->village->workers()->add($this->makeWorker(WorkerColor::BLUE));

        $this->assertFalse($this->village->canActivate($tile, []), "worker in stock cannot activate a tile with different color bid");
    }

    public function testCanActivate_activated_stock_existingActivation_noMovableBids(): void {
        $tile = $this->makeTile(TileType::ALEHOUSE);
        $tile->productionActivations()->add($this->workerGroup(WorkerColor::BLUE));

        $this->village->workers()->addGroup($this->workerGroup(WorkerColor::BLUE, 2));

        $this->assertTrue($this->village->canActivate($tile, []), "worker in stock can activate an already activiated tile");
    }

    public function testCannotActivate_unactivated_stock_fullyActivated_noMovableBids(): void {
        $tile = $this->makeTile(TileType::ALEHOUSE);
        // 3+ is full up
        $tile->productionActivations()->add($this->workerGroup(WorkerColor::BLUE, 3));
        // more than is there
        $this->village->workers()->addGroup($this->workerGroup(WorkerColor::BLUE, 4));

        $this->assertFalse($this->village->canActivate($tile, []), "cannot activate a fully activated tile");
    }

     public function doNottestCannotActivate_activated_stock_existingActivationAndBid_differentColors1_noMovableBids(): void {
        // FIXME: this test exposes that we need to track history of bids and activations
        //   or, do what the rules suggest and lay tiles down if they're non-matching.
        // Although, maybe not: boat4a doesn't let you counter with a different color from activations
        $tile = $this->makeLot(TileType::ALEHOUSE);
        // first bid by blue but then a boat4b holder activated with RED
        $tile->bid(self::PLAYER_ID, $this->workerGroup(WorkerColor::BLUE));
        $tile->productionActivations()->add($this->workerGroup(WorkerColor::RED));

        $this->village->workers()->addGroup($this->workerGroup(WorkerColor::BLUE, 2));

        $this->assertTrue($this->village->canActivate($tile, []), "worker in stock can activate an already activiated tile");
    }



    public function testCanActivate_unactivated_noStock_noBids_movableBids(): void {
        $tile = $this->makeTile(TileType::ALEHOUSE);
        $bids = [$this->workerGroup(WorkerColor::BLUE, 1)];

        $this->assertTrue($this->village->canActivate($tile, $bids), "worker in moveable bid can activate an empty tile");
    }

    public function testCannotActivate_unactivated_noStock_bids_movableBids(): void {
        $tile = $this->makeLot(TileType::ALEHOUSE);
        $bids = [$this->workerGroup(WorkerColor::RED, 1)];
        $tile->bid(self::PLAYER_ID, $this->workerGroup(WorkerColor::BLUE));

        $this->assertFalse($this->village->canActivate($tile, $bids), "worker in moveable bid cannot activate of other color");
    }

    public function testCanActivate_unactivated_stock_bids_movableBids(): void {
        $tile = $this->makeLot(TileType::ALEHOUSE);
        $this->village->workers()->add($this->makeWorker(WorkerColor::RED));
        $tile->bid(self::PLAYER_ID, $this->workerGroup(WorkerColor::BLUE));
        $bids = [$this->workerGroup(WorkerColor::BLUE)];

        $this->assertTrue($this->village->canActivate($tile, $bids), "moveable bid right color but stock wrong can activate");
    }

    public function testCannotActivate_activated_nostock_noBids_movableBids(): void {
        $tile = $this->makeLot(TileType::ALEHOUSE);
        $this->village->workers()->add($this->makeWorker(WorkerColor::RED));
        $tile->productionActivations()->add($this->workerGroup(WorkerColor::BLUE, 2));
        $bids = [$this->workerGroup(WorkerColor::BLUE), $this->workerGroup(WorkerColor::BLUE)];

        $this->assertFalse($this->village->canActivate($tile, $bids), "moveable bids right color but insufficient to activate");
    }

    public function testCanActivate_activated_stock_noBids_movableBids_combined(): void {
        $tile = $this->makeLot(TileType::ALEHOUSE);
        $this->village->workers()->add($this->makeWorker(WorkerColor::BLUE));
        $tile->productionActivations()->add($this->workerGroup(WorkerColor::BLUE, 1));
        $bids = [$this->workerGroup(WorkerColor::BLUE)];

        $this->assertTrue($this->village->canActivate($tile, $bids), "moveable bid right color sufficient when combined with stock to activate");
    }

    public function testCanActivate_activated_nostock_noBids_movableBids_combined(): void {
        $tile = $this->makeLot(TileType::ALEHOUSE);
        $this->village->workers()->add($this->makeWorker(WorkerColor::RED));
        $tile->productionActivations()->add($this->workerGroup(WorkerColor::BLUE, 1));
        $bids = [$this->workerGroup(WorkerColor::BLUE), $this->workerGroup(WorkerColor::BLUE)];

        $this->assertTrue($this->village->canActivate($tile, $bids), "moveable bids right color sufficient when combined to activate");
    }

    public function testCanActivate_activated_nostock_noBids_movableBids(): void {
        $tile = $this->makeLot(TileType::ALEHOUSE);
        $this->village->workers()->add($this->makeWorker(WorkerColor::RED));
        $tile->productionActivations()->add($this->workerGroup(WorkerColor::BLUE));
        $bids = [$this->workerGroup(WorkerColor::BLUE, 2)];

        $this->assertTrue($this->village->canActivate($tile, $bids), "moveable bid right color sufficient to activate");
    }

    public function testCannotActivate_activated_stock_noBids_movableBids_workerPmt(): void {
        $tile = $this->makeLot(TileType::TAVERN); // requires worker
        $this->village->workers()->add($this->makeWorker(WorkerColor::BLUE)); // required to activate
        $tile->productionActivations()->add($this->workerGroup(WorkerColor::BLUE, 1));
        $bids = [$this->workerGroup(WorkerColor::BLUE)];

        $this->assertFalse($this->village->canActivate($tile, $bids), "moveable bid right color sufficient when combined with stock to activate");
    }

    public function testCanActivate_activated_stock_noBids_movableBids_workerPmt(): void {
        $tile = $this->makeLot(TileType::TAVERN); // requires worker
        $this->village->workers()->add($this->makeWorker(WorkerColor::BLUE));
        $this->village->workers()->add($this->makeWorker(WorkerColor::RED));
        $tile->productionActivations()->add($this->workerGroup(WorkerColor::BLUE, 1));
        $bids = [$this->workerGroup(WorkerColor::BLUE)];

        $this->assertTrue($this->village->canActivate($tile, $bids), "moveable bid right color sufficient when combined with stock to activate, worker pmt ");
    }

    // TODO: tests for boat4b
}