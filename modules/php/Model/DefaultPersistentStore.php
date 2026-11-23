<?php

/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * Keyflower implementation : © Jay Sachs <vagabond@covariant.org>
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

enum PieceType: string {
    case WORKER = "worker";
    case RESOURCE = "resource";
    case SKILL = "skill";
    case STARTPLAYER = "start";
    case TILE = "tile";
}

enum LocType: string {
    case VILLAGE = "village";
    case MARKET = "market";
    case DOCK = "dock";
    case TURNORDER = "turnorder";
    case BAG = "bag";
    case TILE = "tile";
    case GREENS = "greens";
    case SKILLPILE = "skillpile";
    case STOCK = "stock";
    case BOAT = "boat";
    case WON = "won";
    case SPRING = Season::SPRING->value;
    case SUMMER = Season::SUMMER->value;
    case FALL = Season::FALL->value;
    case WINTER = Season::WINTER->value;

    public static function fromSeason(Season $season): LocType {
        return LocType::tryFrom($season->value);
    }

    public function toSeason(): Season {
        return Season::from($this->value);
    }
}

class DefaultPersistentStore implements PersistentStore {
    public function __construct(private Db $db, private Globals $globals) {}

    private const GLOBAL_SEASON = 'season';
    private const GLOBAL_PASSES = 'passes';

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
               WHEN piece_type = '" . PieceType::TILE->value . "' THEN ''
               ELSE piece_type
             END, loc_type, loc_id, loc_pos") as $row) {
            $loc_type = LocType::from($row['loc_type']);
            /** @var PieceType */
            $piece_type = PieceType::from($row['piece_type']);
            $subtype = $row['piece_subtype'];
            $id = intval($row['id']);
            $pid = intval($row['player_id']);
            $loc_id = intval($row['loc_id']);
            $loc_pos = intval($row['loc_pos']);

            switch ($piece_type) {
                case PieceType::STARTPLAYER:
                    $result->villages[$pid]->setHasStartPlayer(true);
                    break;
                case PieceType::TILE:
                    $type = TileType::from($subtype);
                    $tileTypes[$id] = $type;
                    switch ($loc_type) {
                        case LocType::VILLAGE:
                            $result->villages[$pid]->placeTile(new VillageTile($id, $type, $loc_id, $loc_pos, $pid));
                            $acts[$id] = [[],[],[]];
                            break;
                        case LocType::MARKET:
                            $bids[$id] = array_fill_keys($playerIds, []);
                            $acts[$id] = [];
                            // $result->market->addTile(new MarketLot($id, $type));
                            break;
                        case LocType::DOCK:
                            $result->dock->add(new DockTile($id, $type));
                            break;
                        case LocType::TURNORDER:
                            $result->tilesStock->addStartTile(new StockTile($id, $type));
                            break;
                        case LocType::WON:
                            $result->villages[$pid]->addWonTile(new StockTile($id, $type));
                            break;
                        default:
                            $season = $loc_type->toSeason();
                            $result->tilesStock->addSeasonTile($season, new StockTile($id, $type));
                    }
                    break;
                case PieceType::WORKER:
                    $worker = new Worker($id, WorkerColor::from($subtype));
                    switch ($loc_type) {
                        case LocType::BAG:
                            $result->workerStock->putInBag($worker);
                            break;
                        case LocType::GREENS:
                            $result->workerStock->putGreen($worker);
                            break;
                        case LocType::BOAT:
                            $boat_id = $loc_id;
                            $boat = $result->dock->boatById($boat_id);
                            if ($boat === null) {
                                throw new InvalidArgumentException("couldn't find boat {$boat_id}");
                            }
                            $boat->addPassenger($worker);
                            break;
                        case LocType::TILE:
                            // activations
                            $acts[$loc_id][$loc_pos][] = $worker;
                            break;
                        case LocType::VILLAGE:
                            $result->villages[$pid]->addWorker($worker);
                            break;
                        case LocType::MARKET:
                            if (!$pid)  {
                                throw new InvalidArgumentException("bid worker not attached to player");
                            }
                            $bids[$loc_id][$pid][] = $worker;
                            break;
                        default:
                            throw new InvalidArgumentException("unknown loc_type {$loc_type->value} for worker piece");
                    }
                    break;
                case PieceType::SKILL:
                    $skill = new Skill($id, SkillType::from($subtype));
                    switch ($loc_type) {
                        case LocType::VILLAGE:
                            $result->villages[$pid]->addSkill($skill);
                            break;
                        case LocType::SKILLPILE:
                            $result->skillPile->add($skill);
                            break;
                        case LocType::BOAT:
                            $boat_id = $loc_id;
                            $boat = $result->dock->boatById($boat_id);
                            if ($boat === null) {
                                throw new InvalidArgumentException("couldn't find boat {$boat_id}");
                            }
                            $boat->addCargo($skill);
                            break;
                        // case 'scoring':
                        default:
                            throw new InvalidArgumentException("unknown loc_type {$loc_type->value} for skill piece");
                    }
                    break;
                case PieceType::RESOURCE:
                    $resource = new Resource($id, ResourceType::from($subtype));
                    switch ($loc_type) {
                        case LocType::STOCK:
                            $result->resourceStock->put($resource);
                            break;
                        case LocType::TILE:
                            $vt = $result->villages[$pid]->tileById($loc_id);
                            if ($vt === null) {
                                throw new InvalidArgumentException("couldn't find village tile {$loc_id} for player {$pid}");
                            }
                            $vt->addResource($resource);
                            break;
                        // case 'scoring':
                        default:
                            throw new InvalidArgumentException("unknown loc_type {$loc_type->value} for resource");
                    }
                    break;
                default:
                    // FIXME: this should not be needed!
                    /** @phpstan-ignore encapsedStringPart.nonString */
                    throw new InvalidArgumentException("Unexpected piece type {$piece_type->value} for piece");
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
                foreach ($result->villages as $village) {
                    $v = $village->tileById($tid);
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
                    $v->productionActivations()->add(new WorkerGroup($a));
                }
            }
        }
        return $result;
    }

    private function stringVal(?string $s): string {
        return $s === null ? "NULL" : "'{$s}'";
    }

    private function intVal(?int $i): string {
        return $i === null ? "NULL" : "{$i}";
    }

    /** @param array<int> $ids */
    private function sqlUpdateLocation(
        array $ids,
        LocType $loc_type,
        ?int $loc_id = null,
        ?int $loc_pos = null,
        ?int $pid = null): string {
        return
            "UPDATE pieces
                SET loc_type='{$loc_type->value}',
                    loc_id={$this->intVal($loc_id)},
                    player_id={$this->intVal($pid)},
                    loc_pos={$this->intVal($loc_pos)}
              WHERE id IN ( " . implode(",", $ids) . " )";
    }

    private function vals(
        int $id,
        PieceType $piece_type,
        LocType $loc_type,
        ?string $subtype = null,
        ?int $loc_id = null,
        ?int $loc_pos = null,
        ?int $pid = null): string {
        return '('  . implode(',', [
            $id,
            $this->stringVal($piece_type->value),
            $this->stringVal($subtype),
            $this->stringVal($loc_type->value),
            $this->intVal($loc_id),
            $this->intVal($loc_pos),
            $this->intVal($pid)
        ]) . ')';
    }

    /**
     * @return list<string>
     */
    private function workerVals(LocType $loc, WorkerGroup $wg, ?int $loc_id = null, ?int $pid = null): array {
        $result = [];
        foreach ($wg->allItems() as $pos => $w) {
            $result[] = $this->vals($w->id, PieceType::WORKER, $loc, $w->color->value, $loc_id, $pos, $pid);
        }
        return $result;
    }

    /**
     * @return list<string>
     */
     function skillVals(LocType $loc, SkillSet $ss, ?int $loc_id = null, ?int $pid = null): array {
        $result = [];
        foreach ($ss->allItems() as $pos => $w) {
            $result[] = $this->vals($w->id, PieceType::SKILL, $loc, $w->type->value, $loc_id, $pos, $pid);
        }
        return $result;
    }

    /** @return list<string> */
    private function activationVals(ProductionActivations $productionActivations, int $tile_id): array {
        $result = [];
        foreach ($productionActivations->activations() as $apos => $activation) {
            // FIXME: use workervals ?
            foreach ($activation->allItems() as $worker) {
                $result[] =  $this->vals($worker->id, PieceType::WORKER, LocType::TILE, $worker->color->value, $tile_id, $apos);
            }
        }
        return $result;
    }

    /**
     * @param list<Resource> $resources
     * @return list<string>
     */
    private function resourceVals(array $resources, ?int $tile_id = null, ?int $pid = null): array {
        $loc_type = $tile_id ? LocType::TILE : LocType::STOCK;
        $loc_id = $tile_id ? $tile_id : null;
        return array_map(
            fn ($r) => $this->vals($r->id, PieceType::RESOURCE, $loc_type, $r->type->value, $loc_id, null, $pid),
            $resources);
    }

    public function saveAllData(AllGameData $data): void {
        $values = [];

        array_push($values, ... $this->workervals(LocType::GREENS, $data->workerStock->greens()));
        array_push($values, ... $this->workervals(LocType::BAG, $data->workerStock->bag()));
        array_push($values, ... $this->skillVals(LocType::SKILLPILE, $data->skillPile));
        foreach ($data->market->lots() as $pos => $mt) {
            $values[] = $this->vals($mt->tile_id, PieceType::TILE, LocType::MARKET, $mt->type->value, null, $pos, null);
            array_push($values, ... $this->activationVals($mt->productionActivations(), $mt->tile_id));
            foreach ($mt->bids() as $pid => $bid) {
                foreach ($bid->allItems() as $bpos => $worker) {
                    $values[] = $this->vals($worker->id, PieceType::WORKER, LocType::TILE, $worker->color->value, $mt->tile_id, $bpos, $pid);
                }
            }
            // TODO: add bids
        }
        array_push($values, ... $this->resourceVals($data->resourceStock->allItems()));
        foreach ($data->dock->boats() as $pos => $mt) {
            $values[] = $this->vals($mt->id, PieceType::TILE, LocType::DOCK, $mt->type->value, null, $pos);
            array_push($values, ... $this->workervals(LocType::BOAT, $mt->passengers(), $mt->id));
            array_push($values, ... $this->skillvals(LocType::BOAT, $mt->cargo(), $mt->id));
        }
        foreach ($data->villages as $pid => $village) {
            foreach ($village->tiles() as $pos => $vt) {
                $values[] = $this->vals($vt->tile_id, PieceType::TILE, LocType::VILLAGE, $vt->type->value, $vt->rowcol, $vt->orientation, $village->playerId);
                array_push($values, ... $this->activationVals($vt->productionActivations(), $vt->tile_id));
                array_push($values, ... $this->resourceVals($vt->resources()->allItems(), $vt->tile_id, $pid));
            }
            array_push($values, ... $this->workervals(LocType::VILLAGE, $village->workers(), null, $village->playerId));
            array_push($values, ... $this->skillVals(LocType::VILLAGE, $village->skills, null, $village->playerId));
            if ($village->hasStartPlayer()) {
                $values[] = $this->vals(1, PieceType::STARTPLAYER, LocType::VILLAGE, null, null, null, $village->playerId);
            }
            foreach ($village->wonTiles() as $pos => $tile) {
                $values[] = $this->vals($tile->tile_id, PieceType::TILE, LocType::WON, $tile->type->value, null, $pos, $village->playerId);
            }
        }

        foreach (Season::cases() as $season) {
            foreach ($data->tilesStock->forSeason($season) as $pos => $tile) {
                $values[] = $this->vals($tile->tile_id, PieceType::TILE, LocType::fromSeason($season), $tile->type->value, null, $pos, null);
            }
        }
        foreach ($data->tilesStock->startTurnTiles() as $pos => $tile) {
            $values[] = $this->vals($tile->tile_id, PieceType::TILE, LocType::TURNORDER, $tile->type->value, null, $pos, null);
        }
        if (count($values) > 0) {
            $this->db->execute(
                "REPLACE INTO `pieces`
                (id, piece_type, piece_subtype, loc_type, loc_id, loc_pos, player_id)
                VALUES " . implode(",\n", $values));
        }
        if ($data->season !== null) {
            $this->globals->set(self::GLOBAL_SEASON, $data->season->value);
        }
    }

    public function updateActivation(
        int $player_id,
        int $tile_id,
        int $activationPos,
        WorkerGroup $group,
        ProductionResult $productionResult,
        ActivationPayment $payment,
        ?VillageTile $destTile): void {
        $this->db->execute($this->sqlUpdateLocation(
            array_map(fn ($w) => $w->id, $group->allItems()),
            LocType::TILE, $tile_id, $activationPos, $player_id)
        );
        if ($payment->worker) {
            $this->db->execute($this->sqlUpdateLocation([$payment->worker->id], LocType::BAG));
        }
        if ($payment->skill) {
            $this->db->execute($this->sqlUpdateLocation([$payment->skill->id], LocType::SKILLPILE));
        }
        if (!$productionResult->workers->isEmpty()) {
            $this->db->execute($this->sqlUpdateLocation(
                array_keys($productionResult->workers->allItems()),
                LocType::VILLAGE, null, null, $player_id)
            );
        }
        else if (!$productionResult->skills->isEmpty()) {
            $this->db->execute($this->sqlUpdateLocation(
                array_map(fn ($w) => $w->id, $productionResult->skills->allItems()),
                LocType::VILLAGE, null, null, $player_id)
            );
        }
        else if (!$productionResult->resources->isEmpty()) {
            if ($destTile === null) {
                throw new InvalidArgumentException("must specify destTile for resource production awards");
            }
            $this->db->execute($this->sqlUpdateLocation(
                array_map(fn ($w) => $w->id, $productionResult->resources->allItems()),
                LocType::TILE, $destTile->tile_id, null, $player_id)
            );
        } else {
            // FIXME: do nothing for upgrades? or store a global counter of upgrade moves remaining?
        }
    }

    public function updateBidWorkerLocations(int $player_id, MarketLot $bidTile, WorkerGroup $newBid): void {
        $this->db->execute($this->sqlUpdateLocation(
            array_map(fn ($w) => $w->id, $newBid->allItems()),
            LocType::MARKET, $bidTile->tile_id, null, $player_id)
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