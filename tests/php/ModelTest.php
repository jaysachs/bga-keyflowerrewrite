<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

use Bga\Games\keyflowerrewrite\Model\{
    AllGameData,
    Market,
    MarketLot,
    Model,
    MovedBid,
    PersistentStore,
    RowCol,
    Season,
    TileType,
    Village,
    VillageTile,
    Worker,
    WorkerColor,
    WorkerGroup
};

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

    public function testNextSeason(): void {
        $pids = [self::PID1, self::PID2];
        $ps = new TestStore(AllGameData::newGameDataFor($pids));
        // make it non-identical
        $ps->saveAllData(AllGameData::newGameDataFor($pids));
        $model = new Model(0, $ps);

        foreach (Season::cases() as $season) {
            $result = $model->nextSeason();

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

    public function setUp(): void {
        $this->homeTiles = TileType::initialHomeTiles();
    }

    private function makeWorker(WorkerColor $color): Worker {
        return new Worker($this->lastWorkerId++, $color);
    }

    private function makeWorkerGroup(WorkerColor $color, int $num) : WorkerGroup {
        $w = [];
        for ($i = 0; $i < $num; ++$i) {
            $w[] = new Worker($this->lastWorkerId++, $color);
        }
        return new WorkerGroup($w);
    }

    private function makeVillage(int $pid, int $red, int $blue, int $yellow, int $green = 0): Village {
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
        return new Village($pid, [ new VillageTile($this->lastTileId++, $home, 1, 0) ], $workers, [], [], false);
    }

    private function assertWorkerGroupMatches(WorkerGroup $wg, WorkerColor $color, int $num): void {
        $found = 0;
        foreach ($wg->workers() as $w) {
            $this->assertEquals($color, $w->color);
            $found++;
        }
        $this->assertEquals($num, $found);
    }

    private const PID1 = 101;
    private const PID2 = 102;

    private function initialModel(): Model {
        $pids = [self::PID1, self::PID2];

        $ps = new TestStore(AllGameData::newGameDataFor($pids));
        // make it non-identical
        $ps->saveAllData(AllGameData::newGameDataFor($pids));

        $model = new Model(self::PID1, $ps);
        $allData = $model->allData();
        $allData->villages = [
            self::PID1 => $this->makeVillage(self::PID1, 4, 4, 0),
            self::PID2 => $this->makeVillage(self::PID2, 4, 0, 4)
        ];
        return $model;
    }

    /** @param array<int,WorkerGroup> $bids */
    private function makeLot(TileType $type, array $bids = []): MarketLot {
        return new MarketLot($this->lastLotId++, $type, $bids);
    }

    public function testBid_emptyLot(): void {
        $model = $this->initialModel();
        $lot1 = $this->makeLot(TileType::ALEHOUSE);
        $model->allData()->market->addLot($lot1);

        $result = $model->bid($lot1->id, [], 1, WorkerColor::BLUE);

        $this->assertEquals($lot1->id, $result->bidTileId);
        $this->assertEquals([], $result->movedBids);
        $this->assertWorkerGroupMatches($result->bid, WorkerColor::BLUE, 1);

        // also verify store was called
    }

    public function testBid_insufficientWorkers(): void {
        $model = $this->initialModel();
        $lot1 = $this->makeLot(TileType::ALEHOUSE);
        $model->allData()->market->addLot($lot1);

        $this->assertThrows(fn () => $model->bid($lot1->id, [], 5, WorkerColor::BLUE));

        // also verify store was NOT called
    }

    private function assertThrows(Closure $c, string $msg = "should have thrown exception"): void {
        try {
            $c();
            $this->assertTrue(false, $msg);
        } catch (InvalidArgumentException $e) {
            $this->assertTrue(true, $msg);
        }
    }

    public function testBid_overbiddingSucceeds(): void {
        $model = $this->initialModel();
        $lot1 = $this->makeLot(TileType::ALEHOUSE, [self::PID2 => $this->makeWorkerGroup(WorkerColor::RED, 2)]);
        $model->allData()->market->addLot($lot1);

        $result = $model->bid($lot1->id, [], 3, WorkerColor::RED);

        $this->assertEquals($lot1->id, $result->bidTileId);
        $this->assertEquals([], $result->movedBids);
        $this->assertWorkerGroupMatches($result->bid, WorkerColor::RED, 3);

        // also verify store was called
    }

    public function testBid_overbiddingInsufficientLess(): void {
        $model = $this->initialModel();
        $lot1 = $this->makeLot(TileType::ALEHOUSE, [self::PID2 => $this->makeWorkerGroup(WorkerColor::RED, 2)]);
        $model->allData()->market->addLot($lot1);

        $this->assertThrows(fn () => $model->bid($lot1->id, [], 1, WorkerColor::RED));
    }

    public function testBid_overbiddingInsufficientEquals(): void {
        $model = $this->initialModel();
        $lot1 = $this->makeLot(TileType::ALEHOUSE, [self::PID2 => $this->makeWorkerGroup(WorkerColor::RED, 2)]);
        $model->allData()->market->addLot($lot1);

        $this->assertThrows(fn () => $model->bid($lot1->id, [], 2, WorkerColor::RED));
    }

    public function testBid_overbiddingWrongColor(): void {
        $model = $this->initialModel();
        $lot1 = $this->makeLot(TileType::ALEHOUSE, [self::PID2 => $this->makeWorkerGroup(WorkerColor::RED, 2)]);
        $model->allData()->market->addLot($lot1);

        $this->assertThrows(fn () => $model->bid($lot1->id, [], 3, WorkerColor::BLUE));
    }

    public function testBid_overbiddingDifferentColorWithBoat4a(): void {
        $model = $this->initialModel();
        $lot1 = $this->makeLot(TileType::ALEHOUSE, [self::PID2 => $this->makeWorkerGroup(WorkerColor::RED, 2)]);
        $model->allData()->market->addLot($lot1);
        $model->allData()->villages[self::PID1]->placeTile(new VillageTile(50, TileType::BOAT4A, RowCol::fromRowCol(1,1), 1));

        $result = $model->bid($lot1->id, [], 3, WorkerColor::BLUE);

        $this->assertEquals($lot1->id, $result->bidTileId);
        $this->assertEquals([], $result->movedBids);
        $this->assertWorkerGroupMatches($result->bid, WorkerColor::BLUE, 3);
    }

    public function testBid_overbiddingWithBoat4aNotAllowedForGreen(): void {
        $model = $this->initialModel();
        $lot1 = $this->makeLot(TileType::ALEHOUSE, [self::PID2 => $this->makeWorkerGroup(WorkerColor::GREEN, 2)]);
        $model->allData()->market->addLot($lot1);
        $model->allData()->villages[self::PID1]->placeTile(new VillageTile(50, TileType::BOAT4A, RowCol::fromRowCol(1,1), 1));

        $this->assertThrows(fn() => $model->bid($lot1->id, [], 3, WorkerColor::BLUE));
    }

    public function testBid_moveBid(): void {
        $model = $this->initialModel();
        $lot1 = $this->makeLot(TileType::ALEHOUSE, []);
        $bid1 = $this->makeWorkerGroup(WorkerColor::RED, 1);
        $lot2 = $this->makeLot(TileType::WOODCUTTER, [
            self::PID1 => $bid1,
            self::PID2 => $this->makeWorkerGroup(WorkerColor::RED, 2)
        ]);
        $model->allData()->market->addLot($lot1);
        $model->allData()->market->addLot($lot2);

        $result = $model->bid($lot1->id, [$lot2->id], 0, WorkerColor::RED);

        $this->assertEquals($lot1->id, $result->bidTileId);
        $this->assertEquals([new MovedBid($bid1, $lot2)], $result->movedBids);
        $this->assertWorkerGroupMatches($result->bid, WorkerColor::RED, 1);
    }

    public function testBid_moveBidCantMoveWinningBid(): void {
        $model = $this->initialModel();
        $lot1 = $this->makeLot(TileType::ALEHOUSE, []);
        $bid1 = $this->makeWorkerGroup(WorkerColor::RED, 3);
        $lot2 = $this->makeLot(TileType::WOODCUTTER, [
            self::PID1 => $bid1,
            self::PID2 => $this->makeWorkerGroup(WorkerColor::RED, 2)
        ]);
        $model->allData()->market->addLot($lot1);
        $model->allData()->market->addLot($lot2);

        $this->assertThrows(fn () => $model->bid($lot1->id, [$lot2->id], 0, WorkerColor::RED));
    }

    public function testBid_moveBidWithHandWorkers(): void {
        $model = $this->initialModel();
        $lot1 = $this->makeLot(TileType::ALEHOUSE, [
            self::PID2 => $this->makeWorkerGroup(WorkerColor::RED, 1),
        ]);
        $bid1 = $this->makeWorkerGroup(WorkerColor::RED, 1);
        $lot2 = $this->makeLot(TileType::WOODCUTTER, [
            self::PID1 => $bid1,
            self::PID2 => $this->makeWorkerGroup(WorkerColor::RED, 2)
        ]);
        $model->allData()->market->addLot($lot1);
        $model->allData()->market->addLot($lot2);

        $result = $model->bid($lot1->id, [$lot2->id], 1, WorkerColor::RED);

        $this->assertEquals($lot1->id, $result->bidTileId);
        $this->assertEquals([new MovedBid($bid1, $lot2)], $result->movedBids);
        $this->assertWorkerGroupMatches($result->bid, WorkerColor::RED, 2);
    }

    public function testBid_moveBidMustHaveOneColor(): void {
        $model = $this->initialModel();
        $lot1 = $this->makeLot(TileType::ALEHOUSE, [
            self::PID2 => $this->makeWorkerGroup(WorkerColor::RED, 1),
        ]);
        $bid1 = $this->makeWorkerGroup(WorkerColor::RED, 1);
        $lot2 = $this->makeLot(TileType::WOODCUTTER, [
            self::PID1 => $bid1,
            self::PID2 => $this->makeWorkerGroup(WorkerColor::RED, 2)
        ]);
        $model->allData()->market->addLot($lot1);
        $model->allData()->market->addLot($lot2);

        $this->assertThrows(fn () => $result = $model->bid($lot1->id, [$lot2->id], 1, WorkerColor::BLUE));
    }
}