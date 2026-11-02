<?php

/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * zooloretto implementation : © Jay Sachs <vagabond@covariant.org>
 *
 * Copyright 2025 Jay Sachs <vagabond@covariant.org>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 */

declare(strict_types=1);

namespace Bga\Games\keyflowerrewrite\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\UserException;
use Bga\Games\keyflowerrewrite\Game;

abstract class AbstractState extends GameState
{
    private array $colors;

    function __construct(
        protected Game $game,
        int $id,
        StateType $type,
        string $description = '',
        string $descriptionMyTurn = '',
        bool $updateGameProgression = false
    ) {
        parent::__construct(
            game: $game,
            id: $id,
            type: $type,
            name: null,
            description: $description,
            descriptionMyTurn: $descriptionMyTurn,
            updateGameProgression: $updateGameProgression);

        $this->colors = [
            1 => [
                'name' => clienttranslate('red'),
                'nametr' => _('red')
            ],
            2 => [
                'name' => clienttranslate('yellow'),
                'nametr' => _('yellow')
            ],
            3 => [
                'name' => clienttranslate('blue'),
                'nametr' => _('blue')
            ],
            4 => [
                'name' => clienttranslate('green'),
                'nametr' => _('green')

            ],
        ];
    }

    protected function giveExtraTime(int $player_id, ?int $specificTime = null): void {
        $this->game->giveExtraTime($player_id, $specificTime);
    }

    protected function activeNextPlayer(): void {
        $this->game->activeNextPlayer();
    }

    // This is shared by VillageTransportA and VillageTransportB, so it's hoisted here.
    protected function doActSelectResource(int $active_player_id, int $rid): bool
    {
        $rids = $this->game->getPlayerResources($active_player_id);

        $valid = false;
        $poss_rid = [];
        foreach ($rids as $poss_rid) {
            if ($poss_rid['card_id'] == $rid) {
                $valid = true;
                break;
            }
        }

        if (!$valid) {
            throw new UserException("Invalid selection");
        }

        // Is there already a resource selected?
        $previous_selections = $this->game->getSelectedResource();
        $select_new_resource = 1;
        if (count($previous_selections) == 0) {
            // First resource selected => do nothing !
        } else {
            // On same tile ??
            $previous_selection = $previous_selections[0];

            if ($poss_rid['tile_id'] == $previous_selection['tile_id']) {
                // Is this resource alread selected?
                $bAlreadySelected = false;
                foreach ($previous_selections as $ress) {
                    if ($ress['id'] == $rid)
                        $bAlreadySelected = true;
                }

                if ($bAlreadySelected) {
                    $select_new_resource = 0;
                } else {
                    // ===> multiple selection on same tile
                }
            } else {
                // Reset and select the new one
                $sql = "UPDATE resource SET card_type_arg='0' WHERE (card_type_arg='1') AND (card_location='tile') ";
                $this->game->DbQuery($sql);
            }
        }


        $sql = "UPDATE resource SET card_type_arg='$select_new_resource' WHERE (card_id = '" . $rid . "')";
        $this->game->DbQuery($sql);

        // return if unselected the only selection
        return ($select_new_resource == 0 && count($previous_selections) == 1);
        //  {
        //     // We
        //     $this->gamestate->nextState('changeResource');
        // } else
        //     $this->gamestate->nextState('selectResource');
    }



    /**
     * @param int[] $counts
     * @param int[] $colors
     * @param int[] $outbids
     */
    protected function doPlaceWorkersWithLocation(bool $finalScoring, int $player_id, string $type, int $id, int $color_id, int $count, array $colors, array $counts, array $outbids): mixed
    {
        $this->selectLocation($player_id, $type, $id);

        /** @var int[] */
        $color_to_count = array();

        if (count($colors) > 0) {
            reset($counts);
            foreach ($colors as $cid) {
                $count = current($counts);
                next($counts);

                $color_to_count[intval($cid)] = $count;
            }
        } else {
            $color_to_count = array($color_id => $count);
        }

        // $type: worker type
        // $count: how many workers to place
        /*
         * For now, assuming only called from meeple selector (e.g. no outbid meeples).
         */

        // Find out where the workers are going
        $sql = "SELECT * FROM move WHERE (move_worker_id IS NULL AND move_pid='$player_id')";
        $move_overview = $this->game->getObjectFromDb($sql);


        if ($move_overview['move_type'] != 'assski')
            $worker_data = $this->game->getHandData('worker', $player_id);
        else
            $worker_data = $this->game->getHandData('skill', $player_id);

        $fixed_type = self::getFixedWorkerType();

        // Check outbids
        if (count($outbids) > 0) {
            $possible_outbids = self::getOutbidWorkers($player_id);

            foreach ($outbids as $outbid_requested) {
                $bThisOutbidPossible = false;

                foreach ($possible_outbids as $outbid) {
                    if ($outbid['tid'] == $outbid_requested) {
                        $bThisOutbidPossible = true;
                    }
                }

                if (! $bThisOutbidPossible)
                    throw new UserException("Invalid outbid : " . $outbid_requested);
            }
        }

        $count = 0;
        foreach ($color_to_count as $color => $thiscount) {
            if ($thiscount > 0) // Note: thiscount could be zero if we only have outbids
            {
                $count += $thiscount;

                if (isset($worker_data[$color]) && ($worker_data[$color] >= $count)) {
                    // okay !
                } else
                    throw new UserException("Invalid movexx");
            }
        }

        $possible_wids = array();

        if ($move_overview['move_type'] != 'assski') {
            foreach ($color_to_count as $color => $thiscount) {
                // Grab available workers of the correct colour.
                $sql = "SELECT card_id id, card_type color FROM worker WHERE (card_type = '" . $color . "') AND (card_location = 'hand') AND (card_location_arg = '" . $player_id . "') LIMIT " . $thiscount;
                $new_possible_wids = $this->game->getObjectListFromDB($sql);
                foreach ($new_possible_wids as $item) {
                    $possible_wids[] = $item;
                }
            }
        }


        // Grab workers from outbids
        $initial_count = $count;
        $losing_bids = array();
        $color_to_count_with_outbids = $color_to_count;

        if (count($outbids) > 0) {
            $sql = "SELECT card_id id, card_type_arg source,card_type color
                    FROM worker
                    INNER JOIN market ON market_id=card_type_arg
                    WHERE card_location='bid' AND card_location_arg='$player_id' AND market_tile_id IN ('" . implode("','", $outbids) . "')";
            $additional_wids = $this->game->getObjectListFromDB($sql);

            foreach ($additional_wids as $wid) {
                $possible_wids[] = array('id' => $wid['id'], 'color' => $wid['color']);
                $losing_bids[$wid['id']] = $wid['source'];
                $color = intval($wid['color']);
                if (! isset($color_to_count_with_outbids[$color]))
                    $color_to_count_with_outbids[$color] = 0;
                $color_to_count_with_outbids[$color]++;
            }

            $count += count($additional_wids);
        }

        // Check that colors matchs
        foreach ($color_to_count_with_outbids as $color => $thiscount) {
            if (! $finalScoring) {
                if (($fixed_type != 0) && ($fixed_type != $color)) {
                    // SPECIAL TILE: BOAT 4 (A and B).
                    if ((($move_overview['move_type'] == 'bid') && ((!$this->game->ownsTile($player_id, 36, 0)) || ($fixed_type == 4))) || (($move_overview['move_type'] == 'action') && (!$this->game->ownsTile($player_id, 36, 1)))) {
                        throw new UserException(sprintf(clienttranslate("Worker colours for a tile need to match (you must use only %s workers here)"), $this->colors[$fixed_type]['nametr']));
                    }
                }
            }
        }

        if ($fixed_type == 0 && count($color_to_count_with_outbids) > 1) {
            // Additional check when we place multicolored meeples as the first action on a bid / tile
            // There is NO way we can do this (this is forbidden for bidding, and this is forbidden for action even when we got the boat

            throw new UserException(clienttranslate("Worker colours for a tile need to match"));
        }

        switch ($move_overview['move_type']) {

            case 'bid':
                /* Workers are a bid for a market tile.
                     * Need to check that the bid will be a winning bid before accepting.
                     * Bids stored in worker table: location='bid'. location_arg=pid type_arg=mid */

                // Check the tile's existing bids
                $sql = "SELECT card_location_arg player_id, COUNT(card_location_arg) num, card_type FROM worker WHERE (card_location='bid') AND (card_type_arg='" . $move_overview['move_location'] . "') GROUP BY card_location_arg";
                $previous = $this->game->getCollectionFromDb($sql);
                $prev_bid = 0;
                $existing_bid = 0;
                $existing_bid_color = null;
                foreach ($previous as $p) {
                    if ($p['player_id'] == $player_id) {
                        $existing_bid = $p['num']; // Get our existing bid to top up.
                        $existing_bid_color = $p['card_type'];
                    } else {
                        if ($p['num'] > $prev_bid) {
                            $prev_bid = $p['num']; // Get the highest bid from other players.
                        }
                    }
                }

                if ($existing_bid > 0) {
                    if (! $finalScoring && $this->game->ownsTile($player_id, 36, 0)) {
                        // We must check again fixed type, to check the case where we try to use boat 4a to add different color meeple for bidding

                        foreach ($color_to_count_with_outbids as $color => $thiscount) {
                            if (($fixed_type != 0) && ($existing_bid_color != $color)) {
                                throw new UserException(sprintf(clienttranslate("Worker colours for a tile need to match (you must use only %s workers here)"), $this->colors[$existing_bid_color]['nametr']));
                            }
                        }
                    }
                }

                // Check that bid will be a winning bid.
                // (note: if we don't need an immediate confirm, we do this at the end)
                if ($count + $existing_bid > $prev_bid) {

                    // Add the workers to the bid.
                    $wids = array();
                    $meeple_to_slide = array();
                    foreach ($possible_wids as $pwid) {
                        $wid = $pwid['id'];
                        array_push($wids, $wid);

                        // Apply move to the database.
                        $sql = "UPDATE worker SET card_location='bid', card_type_arg='" . $move_overview['move_location'] . "' WHERE (card_id='" . $wid . "')";
                        $this->game->DbQuery($sql);

                        $meeple_to_slide[$wid] = $pwid['color'];
                    }

                    // Get this tile name
                    $tile_id = $this->game->getUniqueValueFromDB("SELECT market_tile_id FROM market WHERE market_id = '" . $move_overview['move_location'] . "' ");

                    $this->playerStats->inc('meeple_bid', $initial_count, $player_id);

                    // Notify players of the moves.
                    foreach ($color_to_count as $color => $thiscount) {
                        $nbr = $color_to_count_with_outbids[$color];

                        $this->notify->all("workersPlaced", clienttranslate('${player_name} places ${nbr} ${color} worker(s) to bid on ${tile_name}.'), array(
                            'i18n' => array('color', 'tile_name'),
                            'player_id' => $player_id,
                            'worker_ids' => $wids,
                            'location' => $move_overview['move_location'],
                            'type' => 'bid',
                            // 'bidData' => self::getBiddingData(),
                            'nbr' => $nbr,
                            'color' => $this->colors[$color]['name'],
                            'tile_name' => $this->game->TILES[$tile_id]['name'],
                            'meeple_to_slide' => $meeple_to_slide,
                            'color_code' => $color,
                            'losing' => $losing_bids
                        ));
                    }
                } else {
                    throw new UserException(sprintf(clienttranslate("You must place more workers than the previous highest bid (more than %s)"), $prev_bid));
                }

                break;

            case 'action':
                /* Workers are activating a tile.
                     * Need to check quantity is more than last activation, but no more than 6 on tile.
                     * Need to check colour matches?
                     * Data in worker table: location='action' location_arg='tile id' type_arg = 1,2,3 */

                // See if the tile has already been activated.
                $sql = "SELECT card_type_arg arg, COUNT(*) num FROM worker WHERE (card_location='action') AND (card_location_arg = '" . $move_overview['move_location'] . "') GROUP BY card_type_arg";
                $previous = $this->game->getCollectionFromDb($sql);
                $prev_bid = 0;
                $action_num = 0;
                $total_bid = 0;
                foreach ($previous as $p) {
                    $total_bid = $total_bid + $p['num']; // Count the total meeples on the tile.
                    if ($p['arg'] > $action_num) {
                        $action_num = $p['arg']; // Identify the last activation.
                        $prev_bid = $p['num']; // Get the meeple count from last activation.
                    }
                }

                // Check the bid quantity is valid.
                if (($count <= $prev_bid) || ($total_bid + $count > 6)) {
                    throw new UserException(sprintf(clienttranslate("You must place more workers than used for the last activation (more than %s), and the total workers on the tile must not exceed six"), $prev_bid));
                }

                if (count($color_to_count) > 1 && $prev_bid == 0) {
                    throw new UserException(_("You cannot use the power of 4b boat if the tile has not been used before for production"));
                }

                // Place the workers
                $wids = array();
                $meeple_to_slide = array();
                foreach ($possible_wids as $pwid) {
                    $wid = $pwid['id'];
                    array_push($wids, $wid);

                    // Update the database with the activation.
                    $sql = "UPDATE worker SET card_location='action', card_location_arg='" . $move_overview['move_location'] . "', card_type_arg='" . ($action_num + 1) . "' WHERE (card_id='" . $wid . "')";
                    $this->game->DbQuery($sql);

                    $meeple_to_slide[$wid] = $pwid['color'];
                }

                // Get this tile name
                $tile_id = $move_overview['move_location'];

                $this->playerStats->inc('meeple_action', $initial_count, $player_id);

                if (! $this->game->ownsTile($player_id, $tile_id)) {
                    // Where is this tile?

                    $sql = "SELECT tile_location FROM tile WHERE tile_id='" . $move_overview['move_location'] . "'";
                    $tile_loc = $this->game->getUniqueValueFromDB($sql);

                    if ($tile_loc == 'table')
                        $this->playerStats->inc('meeple_bidaction', $initial_count, $player_id);
                    else
                        $this->playerStats->inc('meeple_foreignaction', $initial_count, $player_id);
                }

                // Notify the players
                foreach ($color_to_count as $color => $thiscount) {
                    $nbr = $color_to_count_with_outbids[$color];

                    $this->notify->all("workersPlaced", clienttranslate('${player_name} places ${nbr} ${color} worker(s) to activate ${tile_name}.'), array(
                        'i18n' => array('color', 'tile_name'),
                        'player_id' => $player_id,
                        'worker_ids' => $wids,
                        'location' => $move_overview['move_location'],
                        'type' => 'action',
                        'type_arg' => ($action_num + 1),
                        //'actionData' => self::getActionData(),
                        //'bidData' => self::getBiddingData(),
                        'nbr' => $nbr,
                        'color' => $this->colors[$color]['name'],
                        'tile_name' => $this->game->TILES[$tile_id]['name'],
                        'meeple_to_slide' => $meeple_to_slide,
                        'color_code' => $color,
                        'losing' => $losing_bids
                    ));
                }

                break;

            case 'assign':

                // Just assign workers to this tile

                // Place the workers
                $wids = array();
                $meeple_to_slide = array();
                foreach ($possible_wids as $pwid) {
                    $wid = $pwid['id'];
                    array_push($wids, $wid);

                    // Update the database with the activation.
                    $sql = "UPDATE worker SET card_location='action', card_location_arg='" . $move_overview['move_location'] . "', card_type_arg='1' WHERE (card_id='" . $wid . "')";
                    $this->game->DbQuery($sql);

                    $meeple_to_slide[$wid] = $pwid['color'];
                }

                // Get this tile name
                $tile_id = $move_overview['move_location'];

                // Notify the players
                foreach ($color_to_count as $color => $thiscount) {
                    $this->notify->all("workersPlaced", '', array(
                        'player_id' => $player_id,
                        'worker_ids' => $wids,
                        'location' => $move_overview['move_location'],
                        'type' => 'action',
                        'type_arg' => 1,
                        'nbr' => $thiscount,
                        'color' => $this->colors[$color]['name'],
                        'meeple_to_slide' => $meeple_to_slide,
                        'color_code' => $color,
                        'losing' => $losing_bids
                    ));
                }

                $this->game->calculateAndUpdateScoreOnTiles(array($move_overview['move_location']), $player_id);


                break;
            case 'assski':

                foreach ($color_to_count as $color => $thiscount) {
                    // Grab available skills of the correct colour.
                    $sql = "SELECT card_id id, card_type color FROM skill WHERE (card_type = '" . $color . "') AND (card_location = 'hand') AND (card_location_arg = '" . $player_id . "') LIMIT " . $thiscount;
                    $new_possible_wids = $this->game->getObjectListFromDB($sql);
                    foreach ($new_possible_wids as $item) {
                        $possible_wids[] = $item;
                    }
                }


                // Place the workers
                $wids = array();
                $meeple_to_slide = array();
                foreach ($possible_wids as $pwid) {
                    $wid = $pwid['id'];
                    array_push($wids, $wid);

                    // Update the database with the activation.
                    $sql = "UPDATE skill SET card_location='action', card_location_arg='" . $move_overview['move_location'] . "', card_type_arg='1' WHERE (card_id='" . $wid . "')";
                    $this->game->DbQuery($sql);

                    $meeple_to_slide[$wid] = $pwid['color'];
                }

                // Get this tile name
                $tile_id = $move_overview['move_location'];

                // Notify the players
                foreach ($color_to_count as $color => $thiscount) {
                    $this->notify->all("assignSkill", '', array(
                        'player_id' => $player_id,
                        'worker_ids' => $wids,
                        'location' => $move_overview['move_location'],
                        'type' => 'action',
                        'type_arg' => 1,
                        'nbr' => $thiscount,
                        'color' => $this->colors[$color]['name'],
                        'meeple_to_slide' => $meeple_to_slide,
                        'color_code' => $color,
                        'losing' => $losing_bids
                    ));
                }

                $this->game->calculateAndUpdateScoreOnTiles(array($move_overview['move_location']), $player_id);

                break;
        }

        // Notify player(s) of the move.
        $this->notify->player($player_id, 'moveMade', '', array('move' => $this->game->getMoveData(), 'type' => $this->game->getMoveType()));
        $this->notify->player($player_id, 'handUpdate', '', $this->game->getHand($player_id));


        if (! $finalScoring) {
            return WorkersPlaced::class;
        }
        $this->game->DbQuery("DELETE FROM move WHERE move_pid='$player_id'");
        return null;
    }

    private function selectLocation(int $player_id, string $type, int $id): void
    {
        //type can be bid, or action.   id will then be market id or tile id respectively.

        // Now, check if this is a possible move and, if so, process.
        switch ($type) {
            case 'bid':
                $sql = "SELECT market_id FROM market WHERE market_id = '" . $id . "'";
                $lot_data = $this->game->getCollectionFromDb($sql);
                if (sizeOf($lot_data) == 0) {
                    throw new UserException("Invalid move");
                } else {
                    $sql = "INSERT INTO move (move_pid, move_type, move_location) VALUES ('" . $player_id . "','" . $type . "','" . $id . "')";
                    $this->game->DbQuery($sql);
                }
                break;
            case 'action':
            case 'assign':
            case 'assski':
                $sql = "SELECT tile_type type, tile_location location FROM tile WHERE (tile_id = '" . $id . "')";
                $tile_data = $this->game->getObjectFromDb($sql);
                if (($tile_data === null) || (($tile_data['location'] != 'village') && ($tile_data['type'] != self::getSeasonString($this->game->getSeason())))) {
                    throw new UserException("Invalid move");
                } else {
                    $sql = "INSERT INTO move (move_pid, move_type, move_location) VALUES ('" . $player_id . "','" . $type . "','" . $id . "')";
                    $this->game->DbQuery($sql);
                }
                break;
            default:
                throw new UserException("Invalid move");
        }
    }

    protected function getOutbidWorkers(int $active_player_id): array
    {
        // Check which market id's the player has bid on.
        $sql = "SELECT market_tile_id tid, card_type_arg mid, card_type color, COUNT( card_id ) cnt
                FROM worker
                INNER JOIN market ON market_id=card_type_arg
                WHERE (card_location='bid') AND (card_location_arg='" . $this->game->getActivePlayerId() . "')
                GROUP BY card_type_arg";
        $mids = $this->game->getCollectionFromDB($sql);

        $result = array();
        foreach ($mids as $mid) {
            // If the player is not the high bidder then the workers have been outbid.
            if (self::getHighestBidder($mid['mid']) != $active_player_id) {

                $result[] = array('count' => $mid['cnt'], 'color' => $mid['color'], 'tid' => $mid['tid'], 'mid' => $mid['mid']);
            }
        }

        return $result;
    }

   private function getHighestBidder(string $mid): int
    {
        $sql = "SELECT card_location_arg pid, COUNT(*) num FROM worker WHERE (card_location='bid') AND (card_type_arg='" . $mid . "') GROUP BY card_location_arg";
        $bidders = $this->game->getCollectionFromDB($sql);
        $highest = 0;
        $pid = 0;

        foreach ($bidders as $bid) {
            if ($bid['num'] > $highest) {
                $highest = $bid['num'];
                $pid = intval($bid['pid']);
            }
        }

        return $pid;
    }

    private function getLocationTileId(): string
    {
        // Get the tile id.
        $sql = "SELECT move_type type, move_location location FROM move WHERE (move_worker_id IS NULL)";
        $move_data = $this->game->getObjectFromDb($sql);
        if ($move_data['type'] == 'bid') {
            $sql = "SELECT market_tile_id tid FROM market WHERE (market_id = '" . $move_data['location'] . "')";
            $tile_id = $this->game->getUniqueValueFromDB($sql);
        } else {
            $tile_id = $move_data['location'];
        }
        return $tile_id;
    }

    private function getFixedWorkerType(): int
    {
        /* Identifies the primary colour for a tile (or returns zero if not set yet)
         * Colour will be present if workers bid or activated on tile.
         * All colours will be the same unless someone holds Boat 4b.
         */

        $tid = self::getLocationTileId();

        $colours = array();
        $colours_bids = array();
        $colours_actions = array();

        // Check the worker table for any actions.
        $sql = "SELECT card_type type FROM worker WHERE (card_location = 'action') AND (card_location_arg = '" . $tid . "') GROUP BY card_type";
        $action_data = $this->game->getCollectionFromDb($sql);
        foreach ($action_data as $ad) {
            $type = intval($ad['type']);
            array_push($colours, $type);
            array_push($colours_actions, $type);
        }

        // Check the market for any bids.
        $sql = "SELECT market_id FROM market WHERE (market_tile_id = '" . $tid . "')";
        $mids = $this->game->getCollectionFromDB($sql);
        $mid = '';
        if (sizeOf($mids) > 0) {
            // current market lot.
            foreach ($mids as $md) {
                $mid = $md['market_id'];
            }
            // Grab workers bid on the tile.
            $sql = "SELECT card_type type FROM worker WHERE (card_location = 'bid') AND (card_type_arg = '" . $mid . "') GROUP BY card_type";
            $bid_data = $this->game->getCollectionFromDb($sql);
            foreach ($bid_data as $bd) {
                $type = intval($bd['type']);
                if (!in_array($type, $colours)) {
                    array_push($colours, $type);
                }
                array_push($colours_bids, $type);
            }
        }

        switch (sizeOf($colours)) {
            case 0:
                return 0; // no workers present.
            case 1:
                return array_pop($colours); // workers all the same colour.
            default: // more than 1 colour - boat active.

                $boat4owner = $this->game->getTileOwner(36);
                if ($boat4owner != 0) {
                    if ($this->game->ownsTile($boat4owner, 36, 0)) {
                        $boat4a = true;
                    } else {
                        $boat4a = false;
                    }
                } else {
                    $boat4a = false;
                }

                if ($boat4a) {
                    // bids could be wild.
                    if (sizeOf($colours_actions) > 0) {
                        return array_pop($colours_actions);
                    }
                    // Multiple colours from bids only. Reconsider bids, but ignore boat owner.
                    $sql = "SELECT card_type type FROM worker WHERE (card_location = 'bid') AND (card_type_arg = '" . $mid . "') AND (card_location_arg <> '" . $boat4owner . "') GROUP BY card_type";
                    return $this->game->getUniqueValueFromDB($sql);
                }
                // actions could be wild.

                if (sizeOf($colours_bids) > 0) {
                    return array_pop($colours_bids);
                } 
                // Multiple colours from actions only. First action will be correct colour.
                $sql = "SELECT card_type type FROM worker WHERE (card_location = 'action') AND (card_location_arg = '" . $tid . "') AND (card_type_arg='1') GROUP BY card_type LIMIT 0,1";
                return intval($this->game->getUniqueValueFromDB($sql));
        }
    }

    protected function getSeasonString(int $id): string
    {
        $seasons = array('', clienttranslate('spring'), clienttranslate('summer'), clienttranslate('autumn'), clienttranslate('winter'));
        return $seasons[$id];
    }

    protected function multiactiveZombieFinish(int $player_id): void {
        $sql = "
            UPDATE  player
            SET     player_is_multiactive = 0
            WHERE   player_id = $player_id
        ";
        $this->game->DbQuery( $sql );

        $this->gamestate->updateMultiactiveOrNextState( '' );
    }

}
