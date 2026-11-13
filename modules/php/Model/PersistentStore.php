<?php

/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * zooloretto implementation : © Jay Sachs <vagabond@covariant.org>
 *
 * Copyright 2026 Jay Sachs <vagabond@covariant.org>
 *
 * Licensed under the Apache License; Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing; software
 * distributed under the License is distributed on an "AS IS" BASIS;
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND; either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 */

declare(strict_types=1);

namespace Bga\Games\keyflowerrewrite\Model;

use Bga\GameFramework\Db\Globals;
use Bga\Games\keyflowerrewrite\Utils\Db;
use InvalidArgumentException;

class PersistentStore {
    public function __construct(private Db $db, private Globals $globals)
    {
        $this->TILE = 'tile';
        $this->RESOURCE = 'resource';
        $this->SKILL = 'skill';
        $this->WORKER = 'worker';
        $this->STARTPLAYER = 'startplayer';

        $this->VILLAGE = 'village';
        $this->MARKET = 'market';
        $this->DOCK = 'dock';
        $this->START = 'start';
        $this->BAG = 'bag';
        $this->GREENS = 'greens';
        $this->SKILLPILE = 'skillpile';
        $this->STOCK = 'stock';
        $this->BOAT = 'boat';
    }

    private const GLOBAL_SEASON = 'season';
    private const GLOBAL_PASSES = 'passes';

    private readonly string $TILE;
    private readonly string $RESOURCE;
    private readonly string $SKILL;
    private readonly string $WORKER;
    private readonly string $STARTPLAYER;

    private readonly string $VILLAGE;
    private readonly string $MARKET;
    private readonly string $DOCK;
    private readonly string $START;
    private readonly string $BAG;
    private readonly string $GREENS;
    private readonly string $BOAT;
    private readonly string $SKILLPILE;
    private readonly string $STOCK;

    // private readonly string $SCORING;
    // private readonly string $BOX;
    // private readonly string $WON;

    public function readAllData(): AllGameData {
        $playerIds = array_map(fn ($p) => intval($p), $this->db->getSingleFieldList('SELECT player_id FROM player'));
        $result = new AllGameData();

        /** @var list<Worker> */
        $greens = [];
        /** @var list<Worker> */
        $bag = [];
        /** @var list<Resource> */
        $resources = [];
        /** @var list<Skill> */
        $skills = [];
        /** @var list<DockTile> */
        $dockTiles = [];
        /** @var list<MarketTile> */
        $marketTiles = [];
        /** @var list<StockTile> */
        $startTiles = [];
        /** @var array<string,list<StockTile>> */
        $seasonTiles = array_fill_keys(array_map(fn ($s) => $s->value, Season::cases()),[]);
        /** @ var array<int,list<VillageTile>> */
        /** @var array<int,Village> */
        $villages = [];
        foreach ($playerIds as $pid) {
            $villages[$pid] = new Village($pid, [], [], [], [], false);
        }

        foreach ($this->db->getObjectList(
            "SELECT piece_type, id, piece_subtype, loc_type, loc_id, loc_pos, player_id
             FROM pieces
             ORDER BY CASE
               WHEN piece_type = '{$this->TILE}' THEN ''
               ELSE piece_type
             END, loc_type, loc_id, loc_pos") as $row) {
            $loc_type = $row['loc_type'];
            $piece_type = $row['piece_type'];
            $subtype = $row['piece_subtype'];
            $id = intval($row['id']);
            $pid = intval($row['player_id']);
            $loc_id = intval($row['loc_id']);
            $loc_pos = intval($row['loc_pos']);
            switch ($piece_type) {
                case $this->STARTPLAYER:
                    $villages[$pid]->hasStartPlayer = true;
                    break;
                case $this->TILE:
                    $type = TileType::from($subtype);
                    switch ($loc_type) {
                        case $this->VILLAGE:
                            $villages[$pid]->tiles[] = new VillageTile($id, $type, $loc_id, $loc_pos);
                            break;
                        case $this->MARKET:
                            $marketTiles[] = new MarketTile($id, $type, []);
                            break;
                        case $this->DOCK:
                            $dockTiles[] = new DockTile($id, $type, [], [] );
                            break;
                        case $this->START:
                            $startTiles[] = new StockTile($id, $type);
                            break;
                        default:
                            $season = Season::from($loc_type);
                            $seasonTiles[$season->value][] = new StockTile($id, $type);
                    }
                    break;
                case $this->WORKER:
                    $worker = new Worker($id, WorkerColor::from($subtype));
                    switch ($loc_type) {
                        case $this->BAG:
                            $bag[] = $worker;
                            break;
                        case $this->GREENS:
                            $greens[] = $worker;
                            break;
                        case $this->BOAT:
                            $boat_id = $loc_id;
                            $boat = array_find($dockTiles, fn ($b) => $b->id == $boat_id);
                            if ($boat === null) {
                                throw new InvalidArgumentException("couldn't find boat {$boat_id}");
                            }
                            $boat->passengers[] = $worker;
                            break;
                        case $this->TILE:
                            // activations
                            $vid = $loc_id;
                            $v = array_find($villages[$pid]->tiles, fn ($vt) => $vid == $vt->tile_id);
                            if ($v === null) {
                                $v = array_find($marketTiles, fn ($vt) => $vid == $vt->id);
                            }
                            if ($v === null) {
                                throw new InvalidArgumentException("couldn't find village tile for player {$pid} tile {$vid}");
                            }
                            // FIXME: need position!
                            // $v->activations[] = $worker;
                            break;
                        case $this->VILLAGE:
                            $vid = $loc_id;
                            $villages[$pid]->workers[] = $worker;
                            break;
                        case $this->MARKET:
                            $mid = $loc_id;
                            $m = array_find($marketTiles, fn ($mt) => $mid == $mt->id);
                            if ($m === null) {
                                throw new InvalidArgumentException("couldn't find market tile {$mid}");
                            }
                            if (!$pid)  {
                                throw new InvalidArgumentException("bid worker not attached to player");
                            }
                            $m->addToBid($pid, $worker);
                            break;
                        default:
                            throw new InvalidArgumentException("unknown loc_type {$loc_type} for worker piece");
                    }
                    break;
                case $this->SKILL:
                    $skill = new Skill($id, SkillType::from($subtype));
                    switch ($loc_type) {
                        case $this->VILLAGE:
                            $vid = $loc_id;
                            $villages[$pid]->skills[] = $skill;
                            break;
                        case $this->SKILLPILE:
                            $skills[] = $skill;
                            break;
                        case $this->BOAT:
                            $boat_id = $loc_id;
                            $boat = array_find($dockTiles, fn ($b) => $b->id == $boat_id);
                            if ($boat === null) {
                                throw new InvalidArgumentException("couldn't find boat {$boat_id}");
                            }
                            $boat->cargo[] = $skill;
                            break;
                        // case 'scoring':
                        default:
                            throw new InvalidArgumentException("unknown loc_type {$loc_type} for skill piece");
                    }
                    break;
                case $this->RESOURCE:
                    $resource = new Resource($id, ResourceType::from($subtype));
                    switch ($loc_type) {
                        case $this->STOCK:
                            $resources[] = $resource;
                            break;
                        case $this->TILE:
                            $vid = $loc_id;
                            $v = array_find($villages[$pid]->tiles, fn ($vt) => $vid == $vt->tile_id);
                            if ($v === null) {
                                throw new InvalidArgumentException("couldn't find village tile for player {$pid} tile {$vid}");
                            }
                            $v->resources[] = $resource;
                            break;
                        // case 'scoring':
                        default:
                            throw new InvalidArgumentException("unknown loc_type {$loc_type} for resource");
                    }
                    break;
                default:
                    throw new InvalidArgumentException("Unexpected piece type {$piece_type}");
            }
            // TODO populate data
        }

        $seasonString = $this->globals->get(self::GLOBAL_SEASON, null);
        /** @phpstan-ignore argument.type */
        $result->season = $seasonString === null ? null : Season::from(strval($seasonString));
        $result->playerIds = $playerIds;
        $result->villages = $villages;
        $result->dock = new Dock($dockTiles);
        $result->market = new Market($marketTiles);
        $result->tilesStock = new TilesStock($startTiles, $seasonTiles);
        $result->workerStock = new WorkerStock($bag, $greens);
        $result->resources = new ResourceStock($resources);
        $result->skillPile = new SkillPile($skills);
        return $result;
    }

    /**
     * @param array<int,Worker> $ws
     * @return list<string>
     */
    private function workerVals(string $loc, array $ws, ?int $loc_id = null, ?int $pid = null): array {
        $result = [];
        foreach ($ws as $pos => $w) {
            $p = $pid === null ? 'NULL' : $pid;
            $l = $loc_id === null ? 'NULL' : $loc_id;
            $result[] = "('{$this->WORKER}', {$w->id}, '{$w->color->value}','{$loc}', ${l}, {$pos}, {$p})";
        }
        return $result;
    }

    /**
     * @param list<Skill> $ws
     * @return list<string>
     */
     function skillVals(string $loc, array $ws, ?int $loc_id = null, ?int $pid = null): array {
        $result = [];
        foreach ($ws as $pos => $w) {
            $p = $pid === null ? 'NULL' : $pid;
            $l = $loc_id === null ? 'NULL' : $loc_id;
            $result[] = "('{$this->SKILL}', {$w->id}, '{$w->type->value}','{$loc}', {$l}, {$pos}, {$p})";
        }
        return $result;
    }

    public function saveAllData(AllGameData $data): string {
        $values = [];

        $noPid = 'NULL';
        $noLocId = 'NULL';
        $noSubtype = 'NULL';
        array_push($values, ... $this->workervals($this->GREENS, $data->workerStock->greens()));
        array_push($values, ... $this->workervals($this->BAG, $data->workerStock->bagWorkers()));
        array_push($values, ... $this->skillVals($this->SKILLPILE, $data->skillPile->skills()));
        foreach ($data->market->tiles() as $pos => $mt) {
            $values[] = "('{$this->TILE}', {$mt->id}, '{$mt->type->value}','{$this->MARKET}', {$noLocId}, {$pos}, {$noPid})";
            // TODO: add bids and activations
        }
        foreach ($data->dock->boats as $pos => $mt) {
            $values[] = "('tile', {$mt->id}, '{$mt->type->value}','{$this->DOCK}', {$noLocId}, {$pos}, {$noPid})";
            array_push($values, ... $this->workervals($this->BOAT, $mt->passengers, $mt->id));
            array_push($values, ... $this->skillvals($this->BOAT, $mt->cargo, $mt->id));
        }
        foreach ($data->villages as $village) {
            foreach ($village->tiles as $pos => $vt) {
                $values[] = "('{$this->TILE}', {$vt->tile_id}, '{$vt->type->value}', '{$this->VILLAGE}', {$vt->rowcol}, {$vt->orientation}, {$village->playerId})";
                // FIXME: this doesn't work.
                foreach ($vt->activations as $wg) {
                    array_push($values, ... $this->workerVals($this->TILE, $wg->workers()));
                }
            }
            array_push($values, ... $this->workervals($this->VILLAGE, $village->workers, null, $village->playerId));
            array_push($values, ... $this->skillVals($this->VILLAGE, $village->skills, null, $village->playerId));
            if ($village->hasStartPlayer()) {
                array_push($values, "('{$this->STARTPLAYER}', 1, {$noSubtype}, '{$this->VILLAGE}', {$noLocId}, 0, {$village->playerId})");
            }
        }

        foreach (Season::cases() as $season) {
            foreach ($data->tilesStock->forSeason($season) as $pos => $tile) {
                $values[] = "('{$this->TILE}', {$tile->id}, '{$tile->type->value}', '{$season->value}', {$noLocId}, {$pos}, {$noPid})";
            }
        }
        foreach ($data->tilesStock->startTurnTiles() as $pos => $tile) {
            $values[] = "('{$this->TILE}', {$tile->id}, '{$tile->type->value}', '{$this->START}', {$noLocId}, {$pos}, {$noPid})";
        }
        if (count($values) > 0) {
        $this->db->execute(
            "REPLACE INTO `pieces`
             (piece_type, id, piece_subtype, loc_type, loc_id, loc_pos, player_id)
             VALUES " . implode(",\n", $values));
        }
        if ($data->season !== null) {
            $this->globals->set(self::GLOBAL_SEASON, $data->season->value);
        }
        return "".count($values);
    }

    public function updateBidWorkerLocations(int $player_id, MarketTile $bidTile, WorkerGroup $newBid): void {
        $ids = array_map(fn ($w) => $w->id, $newBid->workers());
        $case = "CASE id "
            . implode(" ", array_map(fn ($i, $p) => "WHEN {$i} THEN {$p}", $ids, array_keys($ids)))
            . " END";

        $this->db->execute(
            "UPDATE pieces
             SET loc_type='{$this->MARKET}',
                 loc_id={$bidTile->id},
                 player_id={$player_id},
                 loc_pos={$case}
             WHERE id IN ( " . implode(",", $ids) . " )"
        );
    }

    public function clearPasses(): void {
        $this->globals->set(self::GLOBAL_PASSES, 0);
    }

    public function incPasses(): void {
        $this->globals->inc(self::GLOBAL_PASSES, 1);
    }

    public function numPasses(): int {
        /** @phpstan-ignore argument.type */
        return intval($this->globals->get(self::GLOBAL_PASSES));
    }
}