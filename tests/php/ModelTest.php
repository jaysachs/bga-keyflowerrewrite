<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

use Bga\Games\keyflowerrewrite\Model\{
    ActivationPayment,
    ActivationResult,
    AllGameData,
    BidResult,
    Market,
    MarketLot,
    Model,
    MovedBid,
    PersistentStore,
    ProductionResult,
    RowCol,
    Season,
    TilesStock,
    TileType,
    Village,
    VillageTile,
    Worker,
    WorkerColor,
    WorkerGroup
};
use Bga\Games\keyflowerrewrite\Utils\Log;

class TestStore implements PersistentStore {
    public int $passes = 0;

    function __construct(public AllGameData $allData) {}

    #[Override]
    public function readAllData(): AllGameData
    {
        return $this->allData;
    }

    #[Override]
    public function saveAllData(AllGameData $data): void
    {
        $this->allData = $data;
    }

    #[Override]
    public function updateBidWorkerLocations(int $player_id, MarketLot $bidTile, WorkerGroup $newBid): void
    {
    }

    public function updateActivation(
        int $player_id,
        int $tile_id,
        int $activationPos,
        WorkerGroup $group,
        ProductionResult $productionResult,
        ActivationPayment $payment,
        ?VillageTile $destTile): void
    {

    }

    #[Override]
    public function incPasses(): void
    {
        $this->passes++;
    }

    #[Override]
    public function numPasses(): int
    {
        return $this->passes;
    }
    #[Override]
    public function clearPasses(): void
    {
        $this->passes = 0;
    }
}

final class ModelTest extends TestCase {

    private function assertThrows(Closure $c, string $msg = "should have thrown exception"): void {
        try {
            $c();
            $this->assertTrue(false, $msg);
        } catch (InvalidArgumentException $e) {
            $this->assertTrue(true, $msg);
        }
    }

    public function testNextSeason(): void {
        $pids = [self::PID1, self::PID2];
        $ps = new TestStore(AllGameData::newGameDataFor($pids));
        // make it non-identical
        $ps->saveAllData(AllGameData::newGameDataFor($pids));
        $this->model = new Model(0, $ps);

        foreach (Season::cases() as $season) {
            $result = $this->model->nextSeason();

            $this->assertEquals($season, $result["season"]);

            $this->assertTrue(array_search($result['startPlayerId'], $pids) !== false, "should have start player");

            $newData = $ps->readAllData();
            $lots = $newData->market->lots();
            // 2p has 6 tiles plus 1 start player to bid on
            $this->assertEquals($season == Season::WINTER ? 1 : 7, count($lots), "{$season->value}");

            foreach ($lots as $lot) {
                $this->assertTrue($lot->type->season() == $season || $lot->type->isTurnOrder(), "lots should be spring or start player");
            }

            $boats = $newData->dock->boats();
            $expectedBoats = match ($season) {
                Season::SPRING, Season::SUMMER => [TileType::KEYFLOWER, TileType::SEABASTION],
                Season::FALL => [TileType::KEYFLOWER_U, TileType::SEABASTION],
                Season::WINTER => [TileType::KEYFLOWER_U, TileType::SEABASTION_U]
            };
            $this->assertEquals($expectedBoats, array_map(fn ($b) => $b->type, $boats));
            foreach ($boats as $boat) {
                $this->assertEquals($boat->type->passengerCapacity(), count($boat->passengers()));
                $this->assertEquals($boat->type->cargoCapacity(), count($boat->cargo()));

                // simulate end of season stuff
                $boat->takeAllCargo();
                $boat->takeAllPassengers();
            }

            // simulate end of season stuff
            $newData->market->removeUnbidLots();
        }
    }

    private int $lastWorkerId = 1000;
    private int $lastTileId = 2000;
    private int $lastLotId = 3000;
    /** @var list<TileType> */
    private array $homeTiles;

    private Model $model;
    private TestStore $ps;

    public function setUp(): void {
        $this->homeTiles = TileType::initialHomeTiles();
        $pids = [self::PID1, self::PID2];

        $data = AllGameData::newGameDataFor($pids);
        $data->market->removeUnbidLots();
        $this->ps = new TestStore($data);
        // make it non-identical
        $this->ps->saveAllData(AllGameData::newGameDataFor($pids));

        $this->model = new Model(self::PID1, $this->ps);
        $allData = $this->model->allData();
        $allData->villages = [
            self::PID1 => $this->makeVillage(self::PID1),
            self::PID2 => $this->makeVillage(self::PID2)
        ];
    }

    private const PID1 = 101;
    private const PID2 = 102;

    private function makeWorker(WorkerColor $color): Worker {
        return new Worker($this->lastWorkerId++, $color);
    }

    /** @param array<int,WorkerGroup> $bids */
    private function addLot(TileType $type, array $bids = []): MarketLot {
        $lot = new MarketLot($this->lastLotId++, $type, $bids);
        $this->model->market()->addLot($lot);
        return $lot;
    }


    private function initializeVillageWorkers(WorkerColor $color, int $num) : WorkerGroup {
        $wg = $this->makeWorkerGroup($color, $num);
        foreach ($wg->workers() as $worker) {
            $this->model->village()->addWorker($worker);
        }
        return $wg;
    }

    private function makeWorkerGroup(WorkerColor $color, int $num) : WorkerGroup {
        $w = [];
        for ($i = 0; $i < $num; ++$i) {
            $w[] = $this->makeWorker($color);
        }
        return new WorkerGroup($w);
    }

    private function makeVillage(int $pid, int $red = 0, int $blue = 0, int $yellow = 0, int $green = 0): Village {
        $workers = [];
        for ($i = 0; $i < $red; $i++) {
            $workers[] = $this->makeWorker(WorkerColor::RED);
        }
        for ($i = 0; $i < $blue; $i++) {
            $workers[] = $this->makeWorker(WorkerColor::BLUE);
        }
        for ($i = 0; $i < $yellow; $i++) {
            $workers[] = $this->makeWorker(WorkerColor::YELLOW);
        }
        /** @var TileType */
        $home = array_shift($this->homeTiles);
        return new Village($pid, [ new VillageTile($this->lastTileId++, $home, 1, 0) ], $workers, [], [], [], false);
    }

    public function testActivate_emptyLot(): void {
        $lot1 = $this->addLot(TileType::ALEHOUSE);
        $worker = $this->makeWorker(WorkerColor::YELLOW);
        $this->model->allData()->workerStock->takeFromBag(200);
        $this->model->AllData()->workerStock->putInBag($worker);
        $wg = $this->initializeVillageWorkers(WorkerColor::BLUE, 1);

        $result = $this->model->activate($lot1->tile_id, [], 1, 0, 0, 0);

        $this->assertEquals(new ActivationResult($lot1, new ProductionResult([$worker], null, null, null), $wg, []), $result);
    }

    public function testActivate_matchesColor(): void {
        $lot1 = $this->addLot(TileType::ALEHOUSE);
        $lot1->bid(SELF::PID1, new WorkerGroup([$this->makeWorker(WorkerColor::BLUE)]));
        $worker = $this->makeWorker(WorkerColor::YELLOW);
        $this->model->allData()->workerStock->takeFromBag(200);
        $this->model->AllData()->workerStock->putInBag($worker);
        $wg = $this->initializeVillageWorkers(WorkerColor::BLUE, 1);

        $result = $this->model->activate($lot1->tile_id, [], 1, 0, 0, 0);

        $this->assertEquals(new ActivationResult($lot1, new ProductionResult([$worker], null, null, null), $wg, []), $result);
    }

    public function testActivate_doesNotMatchBidColor(): void {
        $lot1 = $this->addLot(TileType::ALEHOUSE);
        $lot1->bid(SELF::PID1, new WorkerGroup([$this->makeWorker(WorkerColor::YELLOW)]));
        $this->initializeVillageWorkers(WorkerColor::BLUE, 1);

        $this->assertThrows(fn () => $this->model->activate($lot1->tile_id, [], 1, 0, 0, 0), "color mismatch should fail");
    }

    public function testActivate_doesNotMatchActivationColor(): void {
        $lot1 = $this->addLot(TileType::ALEHOUSE);
        $lot1->productionActivations()->add(new WorkerGroup([$this->makeWorker(WorkerColor::YELLOW)]));
        $this->initializeVillageWorkers(WorkerColor::BLUE, 1);

        $this->assertThrows(fn () => $this->model->activate($lot1->tile_id, [], 1, 0, 0, 0), "color mismatch should fail");
    }

    public function testActivate_costPaid(): void {
        $lot1 = $this->addLot(TileType::FAIR);
        $this->model->allData()->workerStock->takeGreens(100);
        $reward = $this->makeWorker(WorkerColor::GREEN);
        $this->model->allData()->workerStock->putGreen($reward);
        $pmt = $this->makeWorker(WorkerColor::RED);
        $this->model->village()->addWorker($pmt);
        $wg = $this->initializeVillageWorkers(WorkerColor::BLUE, 1);

        $result = $this->model->activate($lot1->tile_id, [], 1, 0, 0, 0, null, $pmt->id);

        $this->assertEquals(new ActivationResult($lot1, new ProductionResult([$reward], null, null, null), $wg, []), $result);
    }

    public function testActivate_costNotPaid(): void {
        $lot1 = $this->addLot(TileType::FAIR);
        $this->initializeVillageWorkers(WorkerColor::BLUE, 1);

        $this->assertThrows(fn () => $this->model->activate($lot1->tile_id, [], 1, 0, 0, 0), "cost not paid should fail");
    }

    public function testBid_emptyLot(): void {
        $lot1 = $this->addLot(TileType::ALEHOUSE);
        $wg = $this->initializeVillageWorkers(WorkerColor::BLUE, 1);

        $result = $this->model->bid($lot1->tile_id, [], 1, WorkerColor::BLUE);

        $this->assertEquals(new BidResult($wg, $lot1->tile_id, []), $result);

        // also verify store was called
    }

    public function testBid_insufficientWorkers(): void {
        $lot1 = $this->addLot(TileType::ALEHOUSE);

        $this->assertThrows(fn () => $this->model->bid($lot1->tile_id, [], 5, WorkerColor::BLUE));

        // also verify store was NOT called
    }

    public function testBid_mustMatchActivationColor(): void {
        $lot1 = $this->addLot(TileType::ALEHOUSE);
        $lot1->productionActivations()->add(new WorkerGroup([$this->makeWorker(WorkerColor::YELLOW)]));
        $this->initializeVillageWorkers(WorkerColor::BLUE, 1);

        $this->assertThrows(fn () => $this->model->bid($lot1->tile_id, [], 1, WorkerColor::BLUE), "color mismatch should fail");
    }

    public function testBid_overbiddingSucceeds(): void {
        $lot1 = $this->addLot(TileType::ALEHOUSE, [self::PID2 => $this->makeWorkerGroup(WorkerColor::RED, 2)]);
        $wg = $this->initializeVillageWorkers(WorkerColor::RED, 3);

        $result = $this->model->bid($lot1->tile_id, [], 3, WorkerColor::RED);

        $this->assertEquals(new BidResult($wg, $lot1->tile_id, []), $result);

        // also verify store was called
    }

    public function testBid_overbiddingInsufficientLess(): void {
        $lot1 = $this->addLot(TileType::ALEHOUSE, [self::PID2 => $this->makeWorkerGroup(WorkerColor::RED, 2)]);
        $this->initializeVillageWorkers(WorkerColor::RED, 1);

        $this->assertThrows(fn () => $this->model->bid($lot1->tile_id, [], 1, WorkerColor::RED));
    }

    public function testBid_overbiddingInsufficientEquals(): void {
        $lot1 = $this->addLot(TileType::ALEHOUSE, [self::PID2 => $this->makeWorkerGroup(WorkerColor::RED, 2)]);
        $this->initializeVillageWorkers(WorkerColor::RED, 3);

        $this->assertThrows(fn () => $this->model->bid($lot1->tile_id, [], 2, WorkerColor::RED));
    }

    public function testBid_overbiddingWrongColor(): void {
        $lot1 = $this->addLot(TileType::ALEHOUSE, [self::PID2 => $this->makeWorkerGroup(WorkerColor::YELLOW, 2)]);
        $this->initializeVillageWorkers(WorkerColor::RED, 3);

        $this->assertThrows(fn () => $this->model->bid($lot1->tile_id, [], 3, WorkerColor::RED));
    }

    public function testBid_overbiddingDifferentColorWithBoat4a(): void {
        $lot1 = $this->addLot(TileType::ALEHOUSE, [self::PID2 => $this->makeWorkerGroup(WorkerColor::RED, 2)]);
        $this->model->village()->placeTile(new VillageTile(50, TileType::BOAT4A, RowCol::fromRowCol(1,1), 1));
        $wg = $this->initializeVillageWorkers(WorkerColor::BLUE, 3);

        $result = $this->model->bid($lot1->tile_id, [], 3, WorkerColor::BLUE);

        $this->assertEquals(new BidResult($wg, $lot1->tile_id, []), $result);
    }

    public function testBid_overbiddingWithBoat4aNotAllowedForGreen(): void {
        $lot1 = $this->addLot(TileType::ALEHOUSE, [self::PID2 => $this->makeWorkerGroup(WorkerColor::GREEN, 2)]);
        $this->model->village()->placeTile(new VillageTile(50, TileType::BOAT4A, RowCol::fromRowCol(1,1), 1));
        $this->initializeVillageWorkers(WorkerColor::BLUE, 3);

        $this->assertThrows(fn() => $this->model->bid($lot1->tile_id, [], 3, WorkerColor::BLUE));
    }

    public function testBid_moveBid(): void {
        $lot1 = $this->addLot(TileType::ALEHOUSE, []);
        $bid1 = $this->makeWorkerGroup(WorkerColor::RED, 1);
        $lot2 = $this->addLot(TileType::WOODCUTTER, [
            self::PID1 => $bid1,
            self::PID2 => $this->makeWorkerGroup(WorkerColor::RED, 2)
        ]);

        $result = $this->model->bid($lot1->tile_id, [$lot2->tile_id], 0, WorkerColor::RED);

        $this->assertEquals(new BidResult($bid1, $lot1->tile_id, [new MovedBid($bid1, $lot2)]), $result);
    }

    public function testBid_moveBidCantMoveWinningBid(): void {
        $lot1 = $this->addLot(TileType::ALEHOUSE, []);
        $bid1 = $this->makeWorkerGroup(WorkerColor::RED, 3);
        $lot2 = $this->addLot(TileType::WOODCUTTER, [
            self::PID1 => $bid1,
            self::PID2 => $this->makeWorkerGroup(WorkerColor::RED, 2)
        ]);

        $this->assertThrows(fn () => $this->model->bid($lot1->tile_id, [$lot2->tile_id], 0, WorkerColor::RED));
    }

    public function testBid_moveBidWithHandWorkers(): void {
        $lot1 = $this->addLot(TileType::ALEHOUSE, [
            self::PID2 => $this->makeWorkerGroup(WorkerColor::RED, 1),
        ]);
        $bid1 = $this->makeWorkerGroup(WorkerColor::RED, 1);
        $lot2 = $this->addLot(TileType::WOODCUTTER, [
            self::PID1 => $bid1,
            self::PID2 => $this->makeWorkerGroup(WorkerColor::RED, 2)
        ]);
        $wg = $this->initializeVillageWorkers(WorkerColor::RED, 2);

        $result = $this->model->bid($lot1->tile_id, [$lot2->tile_id], 2, WorkerColor::RED);

        $wg = $wg->combineWith($bid1);
        $this->assertEquals(new BidResult($wg, $lot1->tile_id, [new MovedBid($bid1, $lot2)]), $result);
    }

    public function testBid_moveBidMustHaveOneColor(): void {
        $lot1 = $this->addLot(TileType::ALEHOUSE, [
            self::PID2 => $this->makeWorkerGroup(WorkerColor::RED, 1),
        ]);
        $bid1 = $this->makeWorkerGroup(WorkerColor::RED, 1);
        $lot2 = $this->addLot(TileType::WOODCUTTER, [
            self::PID1 => $bid1,
            self::PID2 => $this->makeWorkerGroup(WorkerColor::RED, 2)
        ]);
        $this->initializeVillageWorkers(WorkerColor::BLUE, 3);

        $this->assertThrows(fn () => $result = $this->model->bid($lot1->tile_id, [$lot2->tile_id], 1, WorkerColor::BLUE));
    }
}