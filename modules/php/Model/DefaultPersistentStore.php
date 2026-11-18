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

class DefaultPersistentStore implements PersistentStore {
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
        $this->WON = 'won';
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
    private readonly string $WON;

    // private readonly string $SCORING;
    // private readonly string $BOX;

    public function readAllData(): AllGameData {
        $playerIds = array_map(fn ($p) => intval($p), $this->db->getSingleFieldList('SELECT player_id FROM player'));
        /** @var string|null */
        $seasonString = $this->globals->get(self::GLOBAL_SEASON, null);
        $result = new AllGameData($playerIds, $seasonString === null ? null : Season::from(strval($seasonString)));

        /** @var array<int,array<int,list<Worker>>> tile x playerid x workers */
        $bids = [];

        /** @var array<int,array<int,list<Worker>>> tile x activation row x workers */
        $acts = [];

        /** @var array<int,TileType> */
        $tileTypes = [];

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
                    $result->villages[$pid]->setHasStartPlayer(true);
                    break;
                case $this->TILE:
                    $type = TileType::from($subtype);
                    $tileTypes[$id] = $type;
                    switch ($loc_type) {
                        case $this->VILLAGE:
                            $result->villages[$pid]->placeTile(new VillageTile($id, $type, $loc_id, $loc_pos));
                            $acts[$id] = [];
                            break;
                        case $this->MARKET:
                            $bids[$id] = array_fill_keys($playerIds, []);
                            $acts[$id] = [];
                            // $result->market->addTile(new MarketLot($id, $type));
                            break;
                        case $this->DOCK:
                            $result->dock->add(new DockTile($id, $type, [], [] ));
                            break;
                        case $this->START:
                            $result->tilesStock->addStartTile(new StockTile($id, $type));
                            break;
                        case $this->WON:
                            $result->villages[$pid]->addWonTile(new StockTile($id, $type));
                            break;
                        default:
                            $season = Season::from($loc_type);
                            $result->tilesStock->addSeasonTile($season, new StockTile($id, $type));
                    }
                    break;
                case $this->WORKER:
                    $worker = new Worker($id, WorkerColor::from($subtype));
                    switch ($loc_type) {
                        case $this->BAG:
                            $result->workerStock->putInBag($worker);
                            break;
                        case $this->GREENS:
                            $result->workerStock->putGreen($worker);
                            break;
                        case $this->BOAT:
                            $boat_id = $loc_id;
                            $boat = $result->dock->boatById($boat_id);
                            if ($boat === null) {
                                throw new InvalidArgumentException("couldn't find boat {$boat_id}");
                            }
                            $boat->addPassenger($worker);
                            break;
                        case $this->TILE:
                            // activations
                            if (!isset($acts[$loc_id][$loc_pos])) {
                                $acts[$loc_id][$loc_pos] = [];
                            }
                            $acts[$loc_id][$loc_pos][] = $worker;
                            break;
                        case $this->VILLAGE:
                            $vid = $loc_id;
                            $result->villages[$pid]->addWorker($worker);
                            break;
                        case $this->MARKET:
                            if (!$pid)  {
                                throw new InvalidArgumentException("bid worker not attached to player");
                            }
                            $bids[$loc_id][$pid][] = $worker;
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
                            $result->villages[$pid]->addSkill($skill);
                            break;
                        case $this->SKILLPILE:
                            $result->skillPile->put($skill);
                            break;
                        case $this->BOAT:
                            $boat_id = $loc_id;
                            $boat = $result->dock->boatById($boat_id);
                            if ($boat === null) {
                                throw new InvalidArgumentException("couldn't find boat {$boat_id}");
                            }
                            $boat->addCargo($skill);
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
                            $result->resourceStock->put($resource);
                            break;
                        case $this->TILE:
                            $vid = $loc_id;
                            $v = array_find($result->villages[$pid]->tiles(), fn ($vt) => $vid == $vt->tile_id);
                            if ($v === null) {
                                throw new InvalidArgumentException("couldn't find village tile for player {$pid} tile {$vid}");
                            }
                            $v->addResource($resource);
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

        foreach ($bids as $mid => $pbids) {
            $wgb = array_map(fn ($b) => new WorkerGroup($b), array_filter($pbids, fn (array $pbid) => count($pbid) > 0));
            $result->market->addLot(new MarketLot($mid, $tileTypes[$mid], $wgb));
        }
        foreach ($acts as $tid => $pacts) {
            $v = $result->market->lotByTileId($tid);
            if ($v === null) {
                foreach ($result->playerIds as $pid) {
                    $v = array_find($result->villages[$pid]->tiles(), fn ($vt) => $tid == $vt->tile_id);
                    if ($v !== null) {
                        break;
                    }
                }
            }
            if ($v === null) {
                throw new InvalidArgumentException("couldn't find tile {$tid}");
            }
            foreach ($pacts as $a) {
                if (count($a) > 0) {
                    $v->activationSet()->add(new WorkerGroup($a));
                }
            }
        }
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
            $result[] = "('{$this->WORKER}', {$w->id}, '{$w->color->value}','{$loc}', {$l}, {$pos}, {$p})";
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

    /** @return list<string> */
    private function activationVals(ActivationSet $activationSet, int $tile_id): array {
        $result = [];
        foreach ($activationSet->activations() as $apos => $activation) {
            foreach ($activation->workers() as $worker) {
                $result[] = "('{$this->WORKER}', {$worker->id}, '{$worker->color->value}', '{$this->TILE}', {$tile_id}, {$apos}, 'NULL')";
            }
        }
        return $result;
    }

    public function saveAllData(AllGameData $data): void {
        $values = [];

        $noPid = 'NULL';
        $noLocId = 'NULL';
        $noSubtype = 'NULL';

        array_push($values, ... $this->workervals($this->GREENS, $data->workerStock->greens()));
        array_push($values, ... $this->workervals($this->BAG, $data->workerStock->bagWorkers()));
        array_push($values, ... $this->skillVals($this->SKILLPILE, $data->skillPile->skills()));
        foreach ($data->market->lots() as $pos => $mt) {
            $values[] = "('{$this->TILE}', {$mt->tile_id}, '{$mt->type->value}','{$this->MARKET}', {$noLocId}, {$pos}, {$noPid})";
            array_push($values, ... $this->activationVals($mt->activationSet(), $mt->tile_id));
            foreach ($mt->bids() as $pid => $bid) {
                foreach ($bid->workers() as $bpos => $worker) {
                    $values[] = "('{$this->WORKER}', {$worker->id}, '{$worker->color->value}', '{$this->TILE}', {$mt->tile_id}, {$bpos}, {$pid})";
                }
            }
            // TODO: add bids
        }
        foreach ($data->dock->boats() as $pos => $mt) {
            $values[] = "('tile', {$mt->id}, '{$mt->type->value}','{$this->DOCK}', {$noLocId}, {$pos}, {$noPid})";
            array_push($values, ... $this->workervals($this->BOAT, $mt->passengers(), $mt->id));
            array_push($values, ... $this->skillvals($this->BOAT, $mt->cargo(), $mt->id));
        }
        foreach ($data->villages as $village) {
            foreach ($village->tiles() as $pos => $vt) {
                $values[] = "('{$this->TILE}', {$vt->tile_id}, '{$vt->type->value}', '{$this->VILLAGE}', {$vt->rowcol}, {$vt->orientation}, {$village->playerId})";
                array_push($values, ... $this->activationVals($vt->activationSet(), $vt->tile_id));
            }
            array_push($values, ... $this->workervals($this->VILLAGE, $village->workers(), null, $village->playerId));
            array_push($values, ... $this->skillVals($this->VILLAGE, $village->skills(), null, $village->playerId));
            if ($village->hasStartPlayer()) {
                array_push($values, "('{$this->STARTPLAYER}', 1, {$noSubtype}, '{$this->VILLAGE}', {$noLocId}, 0, {$village->playerId})");
            }
            foreach ($village->wonTiles() as $tile) {
                $values[] = "('{$this->TILE}', {$tile->tile_id}, '{$tile->type->value}, '{self::WON}', {$noLocId}, NULL, {$village->playerId})";
            }
        }

        foreach (Season::cases() as $season) {
            foreach ($data->tilesStock->forSeason($season) as $pos => $tile) {
                $values[] = "('{$this->TILE}', {$tile->tile_id}, '{$tile->type->value}', '{$season->value}', {$noLocId}, {$pos}, {$noPid})";
            }
        }
        foreach ($data->tilesStock->startTurnTiles() as $pos => $tile) {
            $values[] = "('{$this->TILE}', {$tile->tile_id}, '{$tile->type->value}', '{$this->START}', {$noLocId}, {$pos}, {$noPid})";
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
    }

    public function updateBidWorkerLocations(int $player_id, MarketLot $bidTile, WorkerGroup $newBid): void {
        $ids = array_map(fn ($w) => $w->id, $newBid->workers());
        $case = "CASE id "
            . implode(" ", array_map(fn ($i, $p) => "WHEN {$i} THEN {$p}", $ids, array_keys($ids)))
            . " END";

        $this->db->execute(
            "UPDATE pieces
             SET loc_type='{$this->MARKET}',
                 loc_id={$bidTile->tile_id},
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