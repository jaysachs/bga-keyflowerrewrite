<?php

/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * babylonia implementation : © Jay Sachs <vagabond@covariant.org>
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
use Bga\Games\keyflowerrewrite\Game;

/*
    70 => array("name" => "seasonEndA",
                "description" => clienttranslate('Tidying up the season'),
                "type" => "game",
                "action" => "stSeasonEndA",
                "transitions" => array( "finishSeasonA" => 71 )
    ),
    */

class SeasonEndA extends AbstractState
{
    function __construct(Game $game)
    {
        parent::__construct(
            game: $game,
            id: 70,
            type: StateType::GAME,
            description: clienttranslate('Tidying up the season'),
            updateGameProgression: false,
        );
    }

    public function onEnteringState(): mixed {
        // Season has ended.

        //A: Any outbid workers returned home.
        //B: Village tiles not bid for are discarded.
        //C: Collect tiles that won (e.g. assign them to village) (and workers on them)
        //D: Discard bid to bag
        $winning_pids = $this->returnOutbidWorkersAndUnbidTiles();

        //E: Collect Home village workers
        $this->collectVillageWorkers();
        $players = $this->game->loadPlayersBasicInfos();
        foreach ($players as $player_id => $player) {
            $this->notify->player($player_id, 'handUpdate', '', $this->game->getHand($player_id));
        }

        $this->notify->all("marketUpdate", clienttranslate('Unsuccessful bids and unclaimed tiles are discarded'), array(
            'market_data' => $this->game->getMarketData(),
            'bid_data' => $this->game->getBiddingData(),
            'action_data' => $this->game->getActionData()
        ));
        return TurnOrder::class;
    }

    function collectVillageWorkers()
    {
        $sql = "SELECT w.card_id wid, w.card_location_arg tid, t.tile_location_arg pid FROM worker w LEFT JOIN tile t ON (w.card_location_arg = t.tile_id) WHERE (w.card_location='action')";
        $worker_data = $this->game->getCollectionFromDb($sql);
        foreach ($worker_data as $wd) {
            $sql = "UPDATE worker SET card_location='hand', card_location_arg='" . $wd['pid'] . "' WHERE (card_id='" . $wd['wid'] . "')";
            $this->game->DbQuery($sql);
        }
    }

    function returnOutbidWorkersAndUnbidTiles()
    {
        // Get Market data
        $result = array();
        $tile_to_player = array();
        $sql = "SELECT m.market_id mid, m.market_tile_id tid, t.tile_type type FROM market m LEFT JOIN tile t ON (m.market_tile_id = t.tile_id)";
        $market = $this->game->getCollectionFromDb($sql);
        foreach ($market as $lot) {
            $sql = "SELECT card_location_arg pid, COUNT(card_location_arg) num FROM worker WHERE (card_location='bid') AND (card_type_arg='" . $lot['mid'] . "') GROUP BY card_location_arg";
            $bids = $this->game->getCollectionFromDb($sql);
            $winning_bid = 0;
            $winning_pid = '';
            foreach ($bids as $bid) {
                if ($bid['num'] > $winning_bid) {
                    $winning_bid = $bid['num'];
                    $winning_pid = $bid['pid'];
                }
            }
            if ($winning_pid == '') {
                // if no winning_pid then return the tile.
                $sql = "DELETE FROM market WHERE (market_id='" . $lot['mid'] . "')";
                $this->game->DbQuery($sql);

                $this->notify->all('removeMarketTile', '', array('lot_id' => $lot['mid']));
            } else {
                // other bids need returning.
                $sql = "SELECT card_id, card_type, card_location_arg player_id, " . $lot['mid'] . " mid FROM  worker WHERE (card_type_arg='" . $lot['mid'] . "') AND (card_location='bid') AND (card_location_arg <> '" . $winning_pid . "')";
                $workers_to_return = $this->game->getObjectListFromDB($sql);

                // FIXME: return typed info, numbers should be numbers.
                $this->notify->all('returnBiddingMeeple', '', array('to_return' => $workers_to_return));

                $sql = "UPDATE worker SET card_type_arg='0', card_location='hand' WHERE (card_type_arg='" . $lot['mid'] . "') AND (card_location='bid') AND (card_location_arg <> '" . $winning_pid . "')";
                $this->game->DbQuery($sql);

                if ($lot['type'] != 'turn') {

                    // Assign the tile to the winner.
                    $sql = "UPDATE tile SET tile_location='village', tile_location_arg='" . $winning_pid . "' WHERE (tile_id='" . $lot['tid'] . "')";
                    $this->game->DbQuery($sql);

                    $tile_to_player[$lot['mid']] = $winning_pid;

                    // Return the bid to the bag.
                    $sql = "UPDATE worker SET card_location='bag', card_location_arg='0', card_type_arg='0' WHERE (card_type_arg='" . $lot['mid'] . "') AND (card_location='bid')";
                    $this->game->DbQuery($sql);
                }

                if (!in_array($winning_pid, $result)) {
                    array_push($result, $winning_pid);
                }
            }
        }
        // DO I NEED TO NOTIFY ANYONE HERE?! (changed hand details, market lots, and worker placement).

        $this->notify->all('bidding_results', '', $tile_to_player);

        return $result;
    }
}
