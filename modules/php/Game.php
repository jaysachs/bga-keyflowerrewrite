<?php

/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Keyflower implementation : © Adam Dewbery adam.dewbery@gmail.com
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * newkeyflower.game.php
 *
 * This is the main file for your game logic.
 *
 * In this PHP file, you are going to defines the rules of the game.
 *
 */

declare(strict_types=1);

namespace Bga\Games\keyflowerrewrite;

use \Bga\GameFramework\UserException;
use \Bga\GameFramework\Components\Deck;
use UnexpectedValueException;

class Game extends \Bga\GameFramework\Table
{
    public Deck $workers;
    public Deck $resources;
    public Deck $skills;
    private array $cache;
    public array $TILES;

    function __construct()
    {
        parent::__construct();

		$logDecorator = new LogDecorator(\Closure::fromCallable($this->getPlayerNameById(...)));
		$this->notify->addDecorator($logDecorator->playerNames(...));

        require 'material.inc.php';

        self::initGameStateLabels(array(
            "season" => 10
        ));

        // Globals..
        $this->workers = $this->deckFactory->createDeck("worker");
        $this->workers->init("worker");
        $this->resources = $this->deckFactory->createDeck("resource");
        $this->resources->init("resource");
        $this->skills = $this->deckFactory->createDeck("skill");
        $this->skills->init("skill");

        $this->cache = array();
    }

    function clearCache(): void {
        $this->cache = [];
    }

    /*
        setupNewGame:

        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame($players, $options = array())
    {
        $sql = "DELETE FROM player WHERE 1 ";
        self::DbQuery($sql);

        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        //                        red,   green, blue, brown, orange, black
        $default_colors = array("ff0000", "00d27f", "00aedb", "ffa500", "a200ff", "000000");

        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
        $sql = "INSERT INTO player (player_id, player_color, player_name) VALUES ";
        $values = array();
        foreach ($players as $player_id => $player) {
            $color = array_shift($default_colors);
            $values[] = "('" . $player_id . "','$color','" . addslashes($player['player_name']) . "')";
        }
        $sql .= implode(',', $values);
        self::DbQuery($sql);
        self::reattributeColorsBasedOnPreferences($players, array("ff0000", "00d27f", "00aedb", "ffa500", "a200ff", "000000"));
        self::reloadPlayersBasicInfos();

        /************ Start the game initialization *****/

        $this->playerStats->init(
            [
                'points_tile',
                'points_workers',
                'points_skills',
                'points_resource',
                'points_winterboats',
                'points_gold',
                'village_size',
                'tile_flipped',
                'transport_capacity',
                'meeple_bid',
                'meeple_action',
                'meeple_foreignaction',
                'meeple_bidaction',
                'resource_produced'
            ],
            0
        );

        // TODO: setup the initial game situation here

        self::setGameStateInitialValue('season', 0);

        // A: Create stock of Red (type=1), Blue (type=2), Yellow (type=3) workers (40 each).

        $mixedWorkers = array(
            array('type' => 1, 'type_arg' => 0, 'nbr' => 40),
            array('type' => 2, 'type_arg' => 0, 'nbr' => 40),
            array('type' => 3, 'type_arg' => 0, 'nbr' => 40)
        );
        $this->workers->createCards($mixedWorkers, $location = 'bag', $location_arg = null);
        $this->workers->shuffle('bag');


        // B: Create separate stock of 20 Green (type=4) workers.
        $greenWorkers = array(
            array('type' => 4, 'type_arg' => 0, 'nbr' => 20),
            array('type' => 5, 'type_arg' => 0, 'nbr' => 1)
        );
        $this->workers->createCards($greenWorkers, $location = 'table', $location_arg = null);


        // C: Create separate stocks for 48 Gold (type=4), 24 iron(type=1), 24 stone (type=2), 24 wood (type=3);
        $mixedResources = array(
            array('type' => 1, 'type_arg' => 0, 'nbr' => 24),
            array('type' => 2, 'type_arg' => 0, 'nbr' => 24),
            array('type' => 3, 'type_arg' => 0, 'nbr' => 24),
            array('type' => 4, 'type_arg' => 0, 'nbr' => 48)
        );
        $this->resources->createCards($mixedResources, $location = 'bag', $location_arg = null);
        $this->resources->shuffle('bag');

        // D: Make sure opponents stocks are not visible.... (at least not colours/types anyhow!)


        // E: Create a mixed stock of skill tiles (Anvil (1), Pick Axe (2), Saw (3)) 16 each.
        $mixedSkills = array(
            array('type' => 1, 'type_arg' => 0, 'nbr' => 16),
            array('type' => 2, 'type_arg' => 0, 'nbr' => 16),
            array('type' => 3, 'type_arg' => 0, 'nbr' => 16)
        );
        $this->skills->createCards($mixedSkills, $location = 'bag', $location_arg = null);
        $this->skills->shuffle('bag');

        // F: Draw 8 workers for each player.
        $random = array();
        $lowest = 6;
        $count = 0;
        ksort($players);
        $lowId = 0;
        foreach ($players as $player_id => $player) {

            $count++;
            $this->workers->pickCards(8, 'bag', $player_id);

            // G: Assign random home tile to each player
            $rand = bga_rand(1, 6);  // Home tiles are 1-6.
            while (in_array($rand, $random)) {
                $rand = bga_rand(1, 6);
            }
            array_push($random, $rand);
            if ($rand < $lowest) {
                $lowest = $rand;
                $lowId = $player_id;
            }

            $sql = "UPDATE tile SET tile_location='village', tile_location_arg='" . $player_id . "' WHERE tile_id='" . $rand . "'";
            self::DbQuery($sql);

            $sql = "INSERT INTO village (village_x, village_y, village_tile_id) VALUES ('0','0','" . $rand . "')";
            self::DbQuery($sql);
        }

        for ($a = 1; $a < 7; $a++) {
            if (!in_array($a, $random)) {
                $sql = "UPDATE tile SET tile_location='discard' WHERE tile_id='" . $a . "'";
                self::DbQuery($sql);
            }
        }

        // H: Assign start worker to player with lowest home tile. (and create it).
        $sql = "UPDATE worker SET card_location='hand', card_location_arg='" . $lowId . "' WHERE card_type='5'";
        self::DbQuery($sql);

        // I: Limit stock of boat tiles.
        $playerCount = sizeOf($players);
        $cut = 6 + $playerCount;
        $sql = "UPDATE tile SET tile_type='discard' WHERE (tile_id>" . $cut . ") AND (tile_id < 13)";
        self::DbQuery($sql);
        // Place the others on the map.
        /*
        for ($a = 0; $a<sizeOf($players); $a++) {
            $sql = "INSERT INTO village (village_x, village_y, village_tile_id) VALUES ('0','".(-$a)."','".(7+$a)."')";
            self::DbQuery( $sql );
        }
         */

        // J: Limit stock of turn order tiles.  // Assume side 0 shows side that would be used in 5-6 player game.
        if ($playerCount < 5) {
            $cut = 11 + $playerCount;
            $sql = "UPDATE tile SET tile_type='discard' WHERE (tile_id > " . $cut . ") AND (tile_id < 17)";
            self::DbQuery($sql);
            $sql = "UPDATE tile SET tile_side='1' WHERE (tile_id = '" . $cut . "')";
            self::DbQuery($sql);
        }

        // K: Limit stocks of Spring, Summer, Autumn tiles.
        self::limitTiles('spring', $playerCount + 4);
        self::limitTiles('summer', $playerCount + 4);
        self::limitTiles('autumn', $playerCount + 4);
        if ($playerCount < 5) {
            self::limitTiles('winter', $playerCount * 3);
        } else {
            self::limitTiles('winter', $playerCount * 2);
        }

        // Summer boats - NEED TO SELECT A RANDOM SIDE....
        for ($a = 33; $a < 37; $a++) {
            $sql = "UPDATE tile SET tile_side = '" . bga_rand(0, 1) . "' WHERE tile_id='" . $a . "'";
            self::DbQuery($sql);
        }

        //L: Deal winter tiles.
        $sql = "SELECT tile_id FROM tile WHERE tile_type='winter'";
        $winterTiles = self::getCollectionFromDb($sql);
        shuffle($winterTiles);
        while (sizeOf($winterTiles) > 0) {
            foreach ($players as $player_id => $player) {
                $tile_id = array_pop($winterTiles);
                $sql = "UPDATE tile SET tile_location='hand', tile_location_arg='" . $player_id . "' WHERE (tile_id = '" . $tile_id['tile_id'] . "')";
                self::DbQuery($sql);
            }
        }

        /************ End of the game initialization *****/
        return 10;
    }


    // Limits the stock of a season's tiles to a given number.
    function limitTiles(string $season, int $num): void
    {
        $toDiscard = 12 - $num;
        $discard = array();

        switch ($season) {
            case 'spring':
                $min = 17;
                $max = 28;
                break;
            case 'summer':
                $min = 29;
                $max = 40;
                break;
            case 'autumn':
                $min = 41;
                $max = 52;
                break;
            case 'winter':
                $min = 53;
                $max = 64;
                break;
            default:
                throw new UnexpectedValueException("unknown season {$season}");
        }

        while (sizeOf($discard) < $toDiscard) {
            $rand = bga_rand($min, $max);
            while (in_array($rand, $discard)) {
                $rand = bga_rand($min, $max);
            }
            array_push($discard, $rand);
            $sql = "UPDATE tile SET tile_type='discard' WHERE tile_id='" . $rand . "'";
            self::DbQuery($sql);
        }
    }

    /*
        getAllDatas:

        Gather all informations about current game situation (visible by the current player).

        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas(int $current_player_id): array
    {
        $result = array('players' => array());

        //What do they need to see:
        // A: Their hand: skills, workers, resources, starter, winter tiles
        // B: All played village tiles.
        // C: boats
        // D: turn-order tiles
        // E: market lots.
        // F: Opponent data: total workers/skills/resources?

        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.

        $result['players'] = self::getPlayerData();
        $result['hand'] = self::getHand($current_player_id);
        $result['winter'] = self::getWinterTiles($current_player_id);
        $result['workers'] = self::getHandData('worker', $current_player_id);
        $result['skills'] = self::getHandData('skill', $current_player_id);
        $result['resources'] = self::getResources();
        $result['cargo'] = self::getCargo();
        $result['moves'] = array('move' => self::getMoveData(), 'type' => self::getMoveType());
        $result['starter'] = self::getStarter();
        $result['market'] = self::getMarketData();
        $result['village'] = self::getVillageData();
        $result['bids'] = self::getBiddingData();
        $result['actions'] = self::getActionData();
        $result['assignedSkills'] = self::getAssignedSkills();
        $result['villages'] = self::getVillagesData();
        $result['tiles'] = $this->TILES;

        return $result;
    }

    /*
        getGameProgression:

        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).

        This method is called each time we are in a game state with the "updateGameProgression" property set to true
        (see states.inc.php)
    */
    function getGameProgression()
    {
        // For now only increment based on season. Later could always add in increments based upon 'played workers'.
        // played workers / available workers
        $sql = "SELECT COUNT(*) FROM worker WHERE (card_location = 'hand')";
        $unplayed = self::getUniqueValueFromDB($sql);

        $sql = "SELECT COUNT(*) FROM worker WHERE (card_location IN ('bag', 'cargo', 'table'))";
        $unavailable = self::getUniqueValueFromDB($sql);

        $available = 141 - $unavailable;

        $fraction = 25 * ($available - $unplayed) / $available;
        $season = 25 * (self::getSeason() - 1);
        return $season + $fraction;
    }




    //////////////////////////////////////////////////////////////////////////////
    //////////// Data functions
    ////////////

    function getSeason(): int {
        return intval(self::getGameStateValue('season'));
    }

    function getPlayerData(): array
    {
        $sql = "SELECT player_id id, player_score score, player_color color FROM player ";
        return self::getCollectionFromDb($sql);
    }

    function updateScore($player_id)
    {
        $score = self::recalculateBasicScores($player_id);
        $this->notify->all('updateScore', '', array('player_id' => $player_id, 'score' => $score));
    }

    function getHand(int $current_player_id): array
    {
        return [
            'workers' => self::getHandData('worker', $current_player_id),
            'skills' => self::getHandData('skill', $current_player_id)
        ];
    }

    function getMarketData()
    {
        $sql = "SELECT m.market_id mid, m.market_tile_id tile_id, t.tile_side side, t.tile_location_arg pid, t.tile_location location FROM market m LEFT JOIN tile t ON (m.market_tile_id = t.tile_id)";
        return self::getObjectListFromDB($sql);
    }

    function getVillageData()
    {
        $sql = "SELECT v.*, t.tile_side side, t.tile_orientation orient, t.tile_type type FROM village v LEFT JOIN tile t ON (v.village_tile_id = t.tile_id)";
        return self::getObjectListFromDB($sql);
    }

    // Returns the village data (grouped by player id)
    function getVillagesData()
    {
        $result = array();
        $sql = "SELECT player_id FROM player";
        $pids = self::getCollectionFromDb($sql);

        foreach ($pids as $pid) {

            $sql = "SELECT v.village_x x, v.village_y y, v.village_tile_id tid, t.tile_side side, t.tile_orientation orient, t.tile_type type, t.tile_scored scored FROM village v LEFT JOIN tile t ON (v.village_tile_id = t.tile_id) WHERE (t.tile_location_arg = '" . $pid['player_id'] . "')";
            $result[$pid['player_id']] = self::getObjectListFromDB($sql);
        }

        return $result;
    }

    function getStarter()
    {
        $sql = "SELECT card_location_arg id FROM worker WHERE (card_type='5')";
        return self::getUniqueValueFromDb($sql);
    }

    function getHandData($type, $pid)
    {
        $sql = "SELECT card_type type, count(card_type) number FROM " . $type . " WHERE (card_location='hand') AND (card_location_arg = '" . $pid . "') GROUP BY card_type ";
        return self::getCollectionFromDb($sql);
    }

    function getMoveData()
    {
        $sql = "SELECT * from move WHERE (move_worker_id IS NOT NULL)";
        return self::getCollectionFromDb($sql);
    }

    function getCargoData($type, $bid)
    {
        $sql = "SELECT card_id id, card_type type FROM " . $type . " WHERE (card_location='cargo') AND (card_location_arg='" . $bid . "')";
        return self::getObjectListFromDB($sql);
    }

    function getCargo()
    {
        $result = array();
        $sql = "SELECT tile_id bid FROM tile WHERE tile_type = 'boat'";
        $boat_data = self::getCollectionFromDb($sql);
        foreach ($boat_data as $bd) {
            $result[$bd['bid']] = array('side' => self::getTileSide($bd['bid']), 'workers' => self::getCargoData('worker', $bd['bid']), 'skills' => self::getCargoData('skill', $bd['bid']));
        }
        return $result;
    }

    function getTileSide($tid)
    {
        $sql = "SELECT tile_side FROM tile WHERE (tile_id = '" . $tid . "')";
        return self::getUniqueValueFromDB($sql);
    }

    function getResources()
    {
        $result = array();
        $sql = "SELECT tile_id tid FROM tile WHERE tile_location = 'village'";
        $tile_data = self::getCollectionFromDb($sql);
        foreach ($tile_data as $td) {
            $result[$td['tid']] = self::getTileResources($td['tid']);
        }
        return $result;
    }

    function getTileResources($tid)
    {
        $sql = "SELECT card_id id, card_type type FROM resource WHERE (card_location = 'tile') AND (card_location_arg='" . $tid . "')";
        return self::getCollectionFromDb($sql);
    }


    function getWinterTiles($pid)
    {
        $sql = "SELECT tile_id id FROM tile WHERE (tile_location='hand') AND (tile_location_arg = '" . $pid . "')";
        return self::getCollectionFromDb($sql);
    }

    function getBiddingData()
    {
        // Bids will only be in the market area. Need to know: lot, player_id, worker_id.
        $sql = "SELECT card_id wid, card_type_arg mid, card_location_arg pid, card_type type FROM worker WHERE (card_location='bid')";
        return self::getCollectionFromDb($sql);
    }

    function getActionData()
    {
        // Bids will only be in the market area. Need to know: lot, player_id, worker_id.
        $sql = "SELECT card_id wid, card_type_arg action_num, card_location_arg tile_id, card_type type FROM worker WHERE (card_location='action')";
        return self::getCollectionFromDb($sql);
    }
    function getAssignedSkills()
    {
        $sql = "SELECT card_id wid, card_type_arg action_num, card_location_arg tile_id, card_type type FROM skill WHERE (card_location='action')";
        return self::getCollectionFromDb($sql);
    }

    private function notPassed(): void
    {
        $sql = "UPDATE player SET player_passed = FALSE";
        self::DbQuery($sql);
    }

    function getMoveType()
    {
        $sql = "SELECT move_worker_id wid FROM move WHERE (move_worker_id IS NOT NULL)";
        $move_data = self::getCollectionFromDb($sql);
        $wid = 0;
        foreach ($move_data as $md) {
            $wid = $md['wid'];
        }
        if ($wid != 0) {
            $sql = "SELECT card_type FROM worker WHERE (card_id = '" . $wid . "')";
            return self::getUniqueValueFromDB($sql);
        }
        return '';
    }

    function getSelectionData()
    {
        //This will pass on the selected move.
        $sql = "SELECT * FROM move";  // For now, send everything...
        return self::getCollectionFromDb($sql);
    }

    function getRemainingBoats()
    {
        // WHERE WOULD YOU MARK THE BOAT TO SAY IT HAS BEEN CLAIMED....?
        $sql = "SELECT tile_id FROM tile WHERE (tile_type = 'boat') AND (tile_location_arg='0')";
        return self::getCollectionFromDb($sql);
    }


    //////////////////////////////////////////////////////////////////////////////
    //////////// Utility functions
    ////////////

    function safetyCheck($state): int
    {
        // Check that this player is active and that this action is possible at this moment
        self::checkAction($state);
        return intval(self::getActivePlayerId());
    }

    function freeSpace($x, $y)
    {
        $sql = "SELECT v.village_tile_id FROM village v LEFT JOIN tile t ON (v.village_tile_id = t.tile_id) WHERE (v.village_x='" . $x . "') AND (v.village_y='" . $y . "') AND (t.tile_location_arg='" . self::getCurrentPlayerId() . "')";
        $tile_data = self::getCollectionFromDb($sql);
        return (sizeOf($tile_data) == 0);
    }

    function getConstructionTile()
    {
        $sql = "SELECT tile_id FROM tile WHERE (tile_location='village') AND (tile_type_arg='1') AND (tile_location_arg='" . self::getCurrentPlayerId() . "')";
        return self::getUniqueValueFromDB($sql);
    }

    function finaliseMove(): void
    {
        $sql = "DELETE FROM move";
        self::DbQuery($sql);
        self::notPassed();
    }

    function getGoodsFromMap($map, $upgrade)
    {
        //SPECIAL: Boat 3a (resources wild for upgrades).
        $special = ($upgrade && self::ownsTile(self::getActivePlayerId(), 35, 0));
        $used_special = false;

        $options = array();
        $goodId = 1;
        while ($goodId <= $map) {
            if (($map & $goodId) == $goodId) {

                if ($special && (!$used_special) && ($goodId >= 128) && ($goodId <= 1024)) {
                    array_push($options, 128);
                    array_push($options, 256);
                    array_push($options, 512);
                    array_push($options, 1024);
                    $used_special = true;
                } else {
                    array_push($options, $goodId);
                }
            }
            $goodId *= 2;
        }
        return $options;
    }

    function goodCount($good, $list)
    {
        /*
           counts how many times $good appears in $list
         */

        $result = 0;
        foreach ($list as $item) {
            if ($good == $item) {
                $result++;
            }
        }
        return $result;
    }

    function getResourceCount($table, $type)
    {
        return self::getUniqueValueFromDB("SELECT COUNT(*) FROM " . $table . " WHERE (card_type='" . $type . "') AND (card_location='hand') AND (card_location_arg='" . self::getCurrentPlayerId() . "')");
    }

    function isGoodAvailable($good, $spent, $supply): bool
    {
        /*
         $good : good to be checked for availability.
         $spent: goods already promised.
         */

        $map = self::getMapItem($good);

        // How many of this good have already been promised.
        $expenditure = self::goodCount($good, $spent);
        if ($supply !== null) {
            $available = self::goodCount($good, $supply);
        } else {
            $available = self::getResourceCount($map['table'], $map['type']);
        }

        return ($available > $expenditure);
    }


    function getCombinations($goods, $paid, $supply, $upgrade): ?array
    {
        /*
         $goods: items still to be purchased.
         $paid: items already spent
         $supply: items available to pay with.
         returns null if not possible
         */

        $result = array();
        if (sizeOf($goods) == 0) return $result;

        // Get payment options
        $map = array_pop($goods);
        $payment_options = self::getGoodsFromMap($map, $upgrade);

        $viable_options = array();
        foreach ($payment_options as $option) {
            // Check we have one available (after considering paid goods)
            if (self::isGoodAvailable($option, $paid, $supply)) {
                // Add to the valid options.
                array_push($viable_options, $option);
            }
        }

        // Can subsequent goods be purchased.
        $options = array();
        foreach ($viable_options as $vo) {
            if (sizeOf($goods) > 0) {
                $nextPaid = $paid;
                array_push($nextPaid, $vo);
                $subsequent_combinations = self::getCombinations($goods, $nextPaid, $supply, $upgrade);
                if ($subsequent_combinations != null) {
                    // FIXME: delete this
                    /*
                    if (is_int($subsequent_combinations)) { //convert to array.
                        $combination = array();
                        array_push($combination, $subsequent_combinations);
                        array_push($combination, $vo);
                        array_push($options, $combination);
                    } else 
                        */
                    {
                        foreach ($subsequent_combinations as $combination) {
                            if (is_int($combination)) {
                                $dummy = array();
                                array_push($dummy, $combination);
                                $combination = $dummy;
                            }
                            array_push($combination, $vo);
                            array_push($options, $combination);
                        }
                    }
                }
            } else {
                array_push($options, $vo);
            }
        }

        // If valid option(s) return.
        if (sizeOf($options) == 0) {
            return null;
        } else {
            return $options;
        }
    }


    function payWith($table, $type)
    {
        $sql = "SELECT card_id FROM " . $table . " WHERE ((card_location_arg='" . self::getCurrentPlayerId() . "') AND (card_location='hand') AND (card_type = '" . $type . "')) LIMIT 1";
        $id = self::getUniqueValueFromDB($sql);
        $sql = "UPDATE " . $table . " SET card_location='bag', card_location_arg='1' WHERE (card_id='" . $id . "')";
        self::DbQuery($sql);
        switch ($table) {
            case 'worker':
                $this->workers->shuffle('bag');
                break;
            case 'resource':
                $this->resources->shuffle('bag');
                break;
            case 'skill':
                $this->skills->shuffle('bag');
                break;
            default:
                break;
        }
    }

    function getMapItem($good)
    {
        switch ($good) {
            case 1:
                return array('table' => 'worker', 'type' => 1);
            case 2:
                return array('table' => 'worker', 'type' => 2);
            case 4:
                return array('table' => 'worker', 'type' => 3);
            case 8:
                return array('table' => 'worker', 'type' => 4);
            case 16:
                return array('table' => 'skill', 'type' => 1);
            case 32:
                return array('table' => 'skill', 'type' => 2);
            case 64:
                return array('table' => 'skill', 'type' => 3);
            case 128:
                return array('table' => 'resource', 'type' => 1);
            case 256:
                return array('table' => 'resource', 'type' => 2);
            case 512:
                return array('table' => 'resource', 'type' => 3);
            case 1024:
                return array('table' => 'resource', 'type' => 4);
            default:
                break;
        }
    }

    function produceGood($item, $destination, $pid = null)
    {
        /*
         $item: the item to be produced.
         $destination: the tile on which to send the produce.
         */
        $item_data = self::getMapItem($item);
        $sql = "SELECT card_id FROM " . $item_data['table'] . " WHERE ((card_location='table') OR (card_location='bag')) AND (card_type = '" . $item_data['type'] . "') LIMIT 1";
        $id = self::getUniqueValueFromDB($sql);

        if ($item_data['table'] == 'resource') { // resource sent to tile.
            $card_location = 'tile';
            $card_location_arg = $destination;
        } else {  // worker, skill sent to hand.
            $card_location = 'hand';
            if ($pid !== null)
                $card_location_arg = $pid;
            else
                $card_location_arg = self::getActivePlayerId();
        }

        self::DbQuery("UPDATE " . $item_data['table'] . " SET card_location='" . $card_location . "', card_location_arg='" . $card_location_arg . "' WHERE (card_id='" . $id . "')");

        return $id;
    }

    function splitMapIntoSingleItems($map)
    {

        $result = array();
        while ($map > 0) {
            $itemCode = self::splitItemFromMap($map);
            $map = $map - $itemCode;
            array_push($result, self::getMapItem($itemCode));
        }
        return $result;
    }

    function splitItemFromMap($map)
    {
        $i = 1;
        while ($i <= $map) {
            $i *= 2;
        }
        $i /= 2;
        return $i;
    }

    function isRewardChoice($items): bool
    {
        /*
         $items: a list of items
         checks that none of them have options (e.g. are a map).
         */
        $result = true;

        foreach ($items as $item) {
            $result = ($result and (self::isBagGood($item) or self::isSingleGood($item)));
        }

        return (!$result);
    }

    function isBagGood($item): bool
    {
        return (($item == 7) || ($item == 112));
    }

    function isSingleGood($item): bool
    {
        $a = 1;
        while ($a <= $item) {
            if ($a == $item) return true;
            $a *= 2;
        }
        return false;
    }

    function getPlayerResources($player_id)
    {
        $sql = "SELECT r.card_id, r.card_location_arg tile_id FROM resource r LEFT JOIN tile t ON (r.card_location_arg = t.tile_id) WHERE (r.card_location='tile') AND (t.tile_location_arg='" . $player_id . "')";
        return self::getCollectionFromDb($sql);
    }

    function getSelectedResource()
    {
        $sql = "SELECT card_id id,card_type color, card_location_arg tile_id FROM resource WHERE (card_location='tile') AND (card_type_arg = '1')";
        return self::getObjectListFromDB($sql);
    }

    function getResourceMapFromType($table, $type)
    {

        switch ($table) {

            case 'worker':
                switch ($type) {
                    case 1:
                        return 1;
                    case 2:
                        return 2;
                    case 3:
                        return 4;
                    case 4:
                        return 8;
                }
                break;

            case 'skill':
                switch ($type) {
                    case 1:
                        return 16;
                    case 2:
                        return 32;
                    case 3:
                        return 64;
                }
                break;

            case 'resource':
                switch ($type) {
                    case 1:
                        return 128;
                    case 2:
                        return 256;
                    case 3:
                        return 512;
                    case 4:
                        return 1024;
                }
                break;
        }
    }

    function getUpgradeCostOptions($tid): ?array
    {

        // Get the resources on the tile.
        $sql = "SELECT card_id rid, card_type type FROM resource WHERE (card_location='tile') AND (card_location_arg='" . $tid . "')";
        $resource_data = self::getCollectionFromDB($sql);
        $resources = array();
        foreach ($resource_data as $rd) {
            array_push($resources, self::getResourceMapFromType('resource', $rd['type']));
        }

        // Get the tiles in the hand.
        $player_id = self::getCurrentPlayerId();
        $sql = "SELECT card_id sid, card_type type FROM skill WHERE (card_location='hand') AND (card_location_arg='" . $player_id . "')";
        $skill_data = self::getCollectionFromDB($sql);
        foreach ($skill_data as $sd) {
            array_push($resources, self::getResourceMapFromType('skill', $sd['type']));
        }

        // Get the upgrade requirements.
        $sql = "SELECT produce_id id, produce_map map FROM produce WHERE (produce_tile_id='" . $tid . "') AND (produce_type='upgrade')";
        $cost_data = self::getCollectionFromDB($sql);
        $costs = array();
        foreach ($cost_data as $cd) {
            array_push($costs, $cd['map']);
        }

        // Check if the cost can be met.
        return self::getCombinations($costs, array(), $resources, true);
    }

    function getUpgradeableTiles($player_id, $bFree = false)
    {
        /*
         Get a list of the players tiles that are still on side 1.
         For each, if the resources on the tile match the upgrade maps then return it.
         */

        $result = array();

        // Get this player's tiles (that haven't been upgraded already).
        $sql = "SELECT tile_id FROM tile WHERE (tile_location='village') AND (tile_location_arg='" . $player_id . "') AND (tile_side='0') AND (tile_type <> 'winter')";
        $player_tiles = self::getCollectionFromDB($sql);

        foreach ($player_tiles as $pt) {

            $tid = $pt['tile_id'];

            if ($bFree)
                array_push($result, $tid);
            else {
                $payment_possibilities = self::getUpgradeCostOptions($tid);
                if ($payment_possibilities !== null && count($payment_possibilities) > 0) {
                    array_push($result, $tid);
                }
            }
        }

        return $result;
    }

    function getCountOnTile($tid, $map)
    {
        // need to count the number of resources that fit the map... For now, just resources.

        $count = 0;
        $count += self::checkTileForItem('resource', $tid, $map);
        $count += self::checkTileForItem('skill', $tid, $map);
        $count += self::checkTileForItem('worker', $tid, $map);

        return $count;
    }

    function checkTileForItem($table, $tid, $map)
    {

        $count = 0;

        $sql = "SELECT card_id rid, card_type type FROM " . $table . " WHERE (card_location='tile') AND (card_location_arg='" . $tid . "')";
        $tile_resources = self::getCollectionFromDB($sql);

        foreach ($tile_resources as $res) {
            // BOAT 3B (resources wild for scoring).
            if ((($table == 'worker') && ($res['type'] == 5)) || (($table == 'resource') && (self::ownsTile(self::getActivePlayerId(), 35, 1))) || (self::fitsMap(self::getResourceMapFromType($table, $res['type']), $map))) {
                $count++;
            }
        }

        return $count;
    }

    function fitsMap($item, $map)
    {
        $valid = self::splitMapIntoSingleItems($map);
        return in_array($item, $valid);
    }

    //////////////////////////////////////////////////////////////////////////////
    //////////// Scoring functions
    ////////////



    function getOtherTileScore($player_id)
    {
        $sql = "SELECT SUM( tile_scored )
                FROM tile
                WHERE tile_location='village' AND tile_location_arg='" . $player_id . "'";
        return self::getUniqueValueFromDB($sql);
    }

    function getTransportCapacity($pid)
    {

        // Get player tiles
        $sql = "SELECT t.tile_id tid, t.tile_side side FROM village v LEFT JOIN tile t ON (v.village_tile_id = t.tile_id) WHERE (t.tile_location_arg='" . $pid . "')";
        $player_tiles = self::getCollectionFromDB($sql);

        $transport = 0;
        foreach ($player_tiles as $tile) {
            // Check each tile for transport capacity.

            $sql = "SELECT produce_id FROM produce WHERE (produce_tile_id = '" . $tile['tid'] . "') AND (produce_tile_side='" . $tile['side'] . "') AND (produce_type='reward') AND (produce_map='2048')";
            $produce_data = self::getCollectionFromDB($sql);

            $transport = $transport + sizeOf($produce_data);
        }

        // SPECIAL: BOAT 2b (doubles transport capacity).
        if (self::ownsTile($pid, 34, 1)) {
            $transport = $transport * 2;
        }

        return $transport;
    }

    function recalculateBasicScores($player_id)
    {
        $score = self::getOtherTileScore($player_id);
        $sql = "UPDATE player SET player_score='" . $score . "' WHERE player_id='" . $player_id . "'";
        self::DbQuery($sql);

        return $score;
    }

    // Called at the beginning of winter to process the initial score for tiles
    function calculateAndUpdateScoreOnAllTiles()
    {
        $players = self::loadPlayersBasicInfos();

        foreach ($players as $player_id => $player) {

            $sql = "SELECT tile_id FROM tile WHERE tile_location='village' AND tile_location_arg='$player_id'";
            $tile_ids = self::getObjectListFromDB($sql, true);

            self::calculateAndUpdateScoreOnTiles($tile_ids, $player_id);
        }
    }

    // 1. calculate scores on each of these tiles, depending on assigned resources
    //    NOTE : this does NOT includes winter boat tiles scored with scoreWinterBoats method !
    //    NOTE : we count each gold as +1 if not scored on the tile
    // 2. update score displayed on this tile, and total score for this player
    function calculateAndUpdateScoreOnTiles($tile_ids, $player_id)
    {
        $current_scores = self::getCollectionFromDB("SELECT tile_id, tile_scored FROM tile WHERE tile_id IN ('" . implode("','", $tile_ids) . "')", true);

        foreach ($tile_ids as $tile_id) {
            $scorecomponents = self::calculateScoreOnTile($tile_id);
            $score = $scorecomponents['score'];
            $goldscore = $scorecomponents['gold'];

            if ($score != $current_scores[$tile_id]) {
                $sql = "UPDATE tile SET tile_scored='$score',tile_goldscored='$goldscore' WHERE tile_id='$tile_id'";
                self::DbQuery($sql);

                $this->notify->all('tileScored', '', array('tid' => $tile_id, 'score' => $score));
            }
        }

        self::updateScore($player_id);
    }

    function getCountForScoring($tid, $item_category, $item_type, $player_id, $bGoldAsJoker = true)
    {

        $count = 0;

        if ($item_category == 'resource' && is_int($item_type) && self::ownsTile($player_id, 35, 1))
            $item_type = 'any';  // BOAT 3B (resources wild for scoring).

        $sql = "SELECT card_id rid, card_type type FROM " . $item_category . " WHERE (card_location IN ('action','tile') ) AND (card_location_arg='" . $tid . "')";
        $tile_resources = self::getCollectionFromDB($sql);

        foreach ($tile_resources as $res) {

            if ($item_type == 'any')
                $count++;
            else if (is_int($item_type) && ($res['type'] == $item_type || ($bGoldAsJoker && $item_category == 'resource' && $res['type'] == 4)))  // We require a specific item, and we got it OR we have gold
            {
                $count++;
            }


            //          // BOAT 3B (resources wild for scoring).
            ////        if ((($table == 'worker') && ($res['type'] == 5)) || (($table == 'resource') && (self::ownsTile(self::getActivePlayerId(), 35, 1))) || (self::fitsMap(self::getResourceMapFromType($table, $res['type']), $map))) {
            //         $count++;
            //   }
        }

        return $count;
    }

    function calculateScoreOnTile($tile_id)
    {
        $score = 0;
        $goldscore = 0;

        // Get the village tiles.
        $sql = "SELECT t.tile_id tid, t.tile_side side, t.tile_location_arg pid, t.tile_scored scored FROM tile t WHERE t.tile_id='$tile_id'";
        $tile = self::getObjectFromDB($sql);

        // Get tile data.
        $tid = $tile['tid'];
        $side = $tile['side'];
        $pid = $tile['pid'];

        // Gold management
        $bAddGoldToThisTileScore = true;
        $bCanTransmuteResources = (self::ownsTile($pid, 35, 1));

        // Get the victory conditions for the tile.
        $sql = "SELECT * FROM victory WHERE (victory_tile_id = '" . $tid . "') AND (victory_tile_side = '" . $side . "')";
        $victory_data = self::getCollectionFromDB($sql);


        // Work out a score for each victory condition that may exist.

        $set = false;
        $set_data = array();

        foreach ($victory_data as $vd) {

            // Need to do different things to get a score for each type.

            switch ($vd['victory_type']) {

                // Simply VP for the tile
                case 'standard':
                    $score = $vd['victory_points'];
                    break;


                // VP multiple for each matching resource on the tile
                case 'tile': {
                        if ($tile_id == 41) //Barn
                        {
                            $base = 1;
                            if ($tile['side'] == 1)
                                $base = 2;

                            $score = $base * self::getCountForScoring($tile_id, 'resource', 'any', $pid);
                        }
                        if ($tile_id == 42) //Blacksmith
                        {
                            $base = 2;
                            if ($tile['side'] == 1)
                                $base = 3;

                            $score = $base * self::getCountForScoring($tile_id, 'resource', 1, $pid);
                        }
                        if ($tile_id == 49) //Stone yard
                        {
                            $base = 2;
                            if ($tile['side'] == 1)
                                $base = 3;

                            $score = $base * self::getCountForScoring($tile_id, 'resource', 2, $pid);
                        }

                        if ($tile_id == 50) //Timber
                        {
                            $base = 2;
                            if ($tile['side'] == 1)
                                $base = 3;

                            $score = $base * self::getCountForScoring($tile_id, 'resource', 3, $pid);
                        }


                        $bAddGoldToThisTileScore = false; // All "joker" resource is counted for the scoring
                    }


                    break;

                // VP multiple for a fixed quantity of matching items on the tile.
                case 'quantity': {
                        if ($tile_id == 12) //Whitewind
                        {
                            //If acquired at the end of the game grants $1$ for each worker the player has, that has not been scored on another tile
                            $score = 1 * self::getCountForScoring($tile_id, 'worker', 'any', $pid);
                        } else if ($tile_id == 53) //Apothecary
                        {
                            // Grants $3$ for each 5 workers assigned to this tile (workers played on the player's village in the last round can be used).
                            $score = 3 * floor(self::getCountForScoring($tile_id, 'worker', 'any', $pid) / 5);
                        } else if ($tile_id == 55) //Jeweller
                        {
                            // Grants $2$ for each 1 gold assigned to this tile
                            $score = 2 *  self::getCountForScoring($tile_id, 'resource', 4, $pid);
                            $bAddGoldToThisTileScore = false;
                            $goldscore = $score;
                        } else if ($tile_id == 56) //Key guild
                        {
                            // Grants $10$ for each set of 5 tools assigned to this tile.
                            $score = 10 * floor(self::getCountForScoring($tile_id, 'skill', 'any', $pid) / 5);
                        } else if ($tile_id == 57) //Key market
                        {
                            // Grants $2$ for each green worker assigned to this tile,
                            $score = 2 * (self::getCountForScoring($tile_id, 'worker', 4, $pid));
                        } else if ($tile_id == 60) //Scholar
                        {
                            // Grants $3$ for each 1 tool assigned to this tile in a set of tools of the same type.

                            $score = max(
                                3 *  self::getCountForScoring($tile_id, 'skill', 1, $pid),
                                max(
                                    3 *  self::getCountForScoring($tile_id, 'skill', 2, $pid),
                                    3 *  self::getCountForScoring($tile_id, 'skill', 3, $pid)
                                )
                            );
                        } else if ($tile_id == 62) //Village hall
                        {
                            //Grants $1$ for each 1 worker in a set of workers of the same type assigned to this tile

                            $score = max(
                                1 *  self::getCountForScoring($tile_id, 'worker', 1, $pid),
                                1 *  self::getCountForScoring($tile_id, 'worker', 2, $pid),
                                1 *  self::getCountForScoring($tile_id, 'worker', 3, $pid),
                                1 *  self::getCountForScoring($tile_id, 'worker', 4, $pid)
                            );
                        } else if ($tile_id == 63) //Watermill
                        {
                            // Grants $1$ for each 1 resource in a set of resources of the same type assigned to this tile

                            $score = max(
                                1 *  self::getCountForScoring($tile_id, 'resource', 1, $pid),
                                1 *  self::getCountForScoring($tile_id, 'resource', 2, $pid),
                                1 *  self::getCountForScoring($tile_id, 'resource', 3, $pid),
                                1 *  self::getCountForScoring($tile_id, 'resource', 4, $pid)
                            );
                            $bAddGoldToThisTileScore = false; // As gold add +1 to each type

                        } else if ($tile_id == 64) // Windmill
                        {
                            // Grants $5$ for each set of 5 resources of any type assigned to this tile

                            if ($bCanTransmuteResources) {
                                // Note: each resource can be gold, you can't score more than that, anyway.
                                $goldscore = self::getCountForScoring($tile_id, 'resource', 'any', $pid);
                                $score = 0; // don't give points for windmill when scored as gold.

                            } else {
                                $gold_count =  self::getCountForScoring($tile_id, 'resource', 4, $pid);
                                $total_count = self::getCountForScoring($tile_id, 'resource', 'any', $pid);
                                $package_count = floor($total_count / 5);
                                $remaining_ress = $total_count - 5 * $package_count;
                                $remaining_gold = min($gold_count, $remaining_ress);

                                $score = 5 * $package_count + $remaining_gold;

                                $bAddGoldToThisTileScore = false;
                                $goldscore = $remaining_gold;
                            }
                        }
                    }


                    break;


                // Collect the set conditions and run through separately.
                case 'set':

                    if ($tile_id == 61) //Scribes
                    {   //Grants $10$ for each set of 1 anvil, 1 pick and 1 saw assigned to this tile
                        $score = 10 * min(
                            self::getCountForScoring($tile_id, 'skill', 1, $pid),
                            self::getCountForScoring($tile_id, 'skill', 2, $pid),
                            self::getCountForScoring($tile_id, 'skill', 3, $pid)
                        );
                    } else if ($tile_id == 59) //Mercers guild
                    {   // Grants $5$ for each set of 1 wood, 1 iron, 1 stone assigned to this tile (the resources can be found anywhere in the player's village; gold can be used as substitute).

                        if ($bCanTransmuteResources) {
                            $total_ress =  self::getCountForScoring($tile_id, 'resource', 'any', $pid);
                            $max_possible_set = floor($total_ress / 3);
                            $remaining_gold = $total_ress - 3 * $max_possible_set;
                        } else {

                            $count = array(
                                1 => self::getCountForScoring($tile_id, 'resource', 1, $pid, false),
                                2 => self::getCountForScoring($tile_id, 'resource', 2, $pid, false),
                                3 => self::getCountForScoring($tile_id, 'resource', 3, $pid, false)
                            );
                            $remaining_gold = self::getCountForScoring($tile_id, 'resource', 4, $pid);
                            $gold_used = array(1 => 0, 2 => 0, 3 => 0);

                            while ($remaining_gold > 0) {
                                $fewest = min($count);
                                $fewest_ressid = null;
                                foreach ($count as $ress => $resscount) {
                                    if ($resscount == $fewest) {
                                        // Found the fewest !
                                        $fewest_ressid = $ress;
                                    }
                                }

                                $count[$fewest_ressid]++;
                                $gold_used[$fewest_ressid]++;
                                $remaining_gold--;
                            }

                            $max_possible_set = min($count);

                            // Then, get back unused gold
                            foreach ($count as $ress => $resscount) {
                                if ($resscount > $max_possible_set)    // some resources are wasted
                                {
                                    if ($gold_used[$ress] > 0)   // and we used gold there!
                                    {
                                        $remaining_gold += min($gold_used[$ress], $resscount - $max_possible_set);
                                    }
                                }
                            }
                        }

                        $score = 5 * $max_possible_set + $remaining_gold;
                        $bAddGoldToThisTileScore = false;
                        $goldscore = $remaining_gold;
                    } else if ($tile_id == 54) //Craftsmens guild
                    {
                        //Grants $3$ for each set of 1 red, 1 yellow and 1 blue worker assigned to this tile
                        $count = array(
                            1 => self::getCountForScoring($tile_id, 'worker', 1, $pid),
                            2 => self::getCountForScoring($tile_id, 'worker', 2, $pid),
                            3 => self::getCountForScoring($tile_id, 'worker', 3, $pid)
                        );
                        /*
                        // FIXME: remove this.
                        $remaining_gold = self::getCountForScoring($tile_id, 'worker', 4, $pid);  // Note: green worker = substitute
                        $remaining_gold = 0;  // UPDATED : NOT SUBSTITUTE !! GREEN CANNOT BE USED THIS WAY
                        $gold_used = array(1 => 0, 2 => 0, 3 => 0);

                        while ($remaining_gold > 0) {
                            $fewest = min($count);
                            $fewest_ressid = null;
                            foreach ($count as $ress => $resscount) {
                                if ($resscount == $fewest) {
                                    // Found the fewest !
                                    $fewest_ressid = $ress;
                                }
                            }

                            $count[$fewest_ressid]++;
                            $gold_used[$fewest_ressid]++;
                            $remaining_gold--;
                        }
                        */

                        $max_possible_set = min($count);

                        $score = 3 * $max_possible_set;
                    }


                    break;


                /// WINTER BOATS : already counter + cannot be updated !
                case 'adjacent':
                case 'boat':
                case 'loop':
                case 'transport':
                    $score = $tile['scored'];
                    break;


                default:
                    break;
            }


            /*     // VP for matching a fixed set of items - can assume only one row for standard game - not for expansions...    Could run into trouble here with gold (or starter token) as it could be used for multiple colours...
            if ($set) {

                $scores = self::getSetScore($tid, $set_data);
            }
         */
        }

        // Check the tile for unused Gold (or other resources with boat 3b).
        if ($bAddGoldToThisTileScore) {
            $goldscore = self::getCountForScoring($tid, 'resource', $bCanTransmuteResources ? 'any' : 4, $pid);
            $score = $score + $goldscore;
        }


        return array('score' => $score, 'gold' => $goldscore);
    }

    //////////////////////////////////////////////////////////////////////////////
    //////////// Map functions
    ////////////

    function getAdjacentTileCoords($tile_coords, $num)
    {

        $x = $tile_coords['x'];
        $y = $tile_coords['y'];

        switch ($num) {
            case 1:
                $y--;
                break;
            case 2:
                $x++;
                $y--;
                break;
            case 3:
                $x++;
                break;
            case 4:
                $y++;
                break;
            case 5:
                $x--;
                $y++;
                break;
            case 6:
                $x--;
            default:
                break;
        }

        return array('x' => $x, 'y' => $y);
    }

    function getTileEdge($tid, $num)
    {
        $tile_edges = self::getTileEdgesData($tid);
        $edge_num = self::getCorrectedEdgeNum($tile_edges['orient'], $num);
        return $tile_edges['edge' . $edge_num];
    }

    function getCorrectedEdgeNum($orient, $num)
    {
        return (1 + (($orient + $num - 2) % 6));
    }

    function getTileEdgesData($tid)
    {

        if (! isset($this->cache['tileEdgeData']))
            $this->cache['tileEdgeData'] = array();

        if (!isset($this->cache['tileEdgeData'][$tid])) {
            $sql = "SELECT tile_orientation orient, tile_edgetype1 edge1, tile_edgetype2 edge2, tile_edgetype3 edge3, tile_edgetype4 edge4, tile_edgetype5 edge5, tile_edgetype5 edge5, tile_edgetype6 edge6 FROM tile WHERE (tile_id='" . $tid . "')";
            $this->cache['tileEdgeData'][$tid] = self::getObjectFromDB($sql);
        }
        return $this->cache['tileEdgeData'][$tid];
    }

    function getTileId($x, $y, $pid)
    {

        if (! isset($this->cache['tileId']))
            $this->cache['tileId'] = array();

        $key = $pid . '_' . $x . '_' . $y;

        if (!isset($this->cache['tileId'][$key])) {
            $sql = "SELECT v.village_tile_id FROM village v LEFT JOIN tile t ON (v.village_tile_id = t.tile_id) WHERE (v.village_x='" . $x . "') AND (v.village_y='" . $y . "') AND (t.tile_location_arg = '" . $pid . "')";
            $id = self::getUniqueValueFromDB($sql);

            if ($id !== null)
                $this->cache['tileId'][$key] = $id;
            else
                $this->cache['tileId'][$key] = 0;
        }
        return $this->cache['tileId'][$key] == 0 ? null : $this->cache['tileId'][$key];
    }

    function getTileCoords($tid)
    {

        if (! isset($this->cache['tileCoords']))
            $this->cache['tileCoords'] = array();

        if (!isset($this->cache['tileCoords'][$tid])) {
            $sql = "SELECT v.village_x x, v.village_y y, t.tile_location_arg pid FROM village v LEFT JOIN tile t ON (v.village_tile_id = t.tile_id) WHERE (village_tile_id='" . $tid . "')";
            $id = self::getObjectFromDB($sql);

            if ($id !== null)
                $this->cache['tileCoords'][$tid] = $id;
            else
                $this->cache['tileCoords'][$tid] = 0;
        }
        return $this->cache['tileCoords'][$tid] == 0 ? null : $this->cache['tileCoords'][$tid];
    }

    function adjacentCount($tid)
    {

        $tile_coords = self::getTileCoords($tid);
        $count = 0;

        for ($a = 1; $a <= 6; $a++) {

            // Grab adjacent tile
            $adjacent_coords = self::getAdjacentTileCoords($tile_coords, $a);
            $adjacent_tid = self::getTileId($adjacent_coords['x'], $adjacent_coords['y'], $tile_coords['pid']);
            // If it doesn't exist, no issue,( DEPRECATED : WHY ??) and ignore central boats.
            if (($adjacent_tid !== null) /*&& ($adjacent_coords['x'] != 0)*/) {
                $count++;
            }
        }

        return $count;
    }


    function getTileOwner($tid)
    {
        /* returns the tile owner (or null if not owned). */
        $sql = "SELECT tile_location_arg pid FROM tile WHERE tile_id='" . $tid . "'";
        $pid = self::getCollectionFromDB($sql); // could be null.
        if (sizeOf($pid) > 0) {

            foreach ($pid as $p) {
                return $p['pid'];
            }
        } else {
            return NULL;
        }
    }

    function ownsTile($pid, $tid, $side = null)
    {

        if (! isset($this->cache['ownsTile']))
            $this->cache['ownsTile'] = array();

        $key = $tid;

        if (!isset($this->cache['ownsTile'][$key])) {
            $sql = "SELECT tile_location_arg pid, tile_side side FROM tile WHERE tile_id = '$tid'";
            $tile_data = self::getObjectFromDB($sql);

            $this->cache['ownsTile'][$key] = $tile_data;
        } else {
            $tile_data = $this->cache['ownsTile'][$key];
        }

        if ($side === null)
            $bCorrectSide = true;
        else
            $bCorrectSide = ($tile_data['side'] == $side);


        return ($bCorrectSide && ($tile_data['pid'] == $pid));
    }

    function payForTileUpgrade(int $player_id, array $payment_options)
    {
        //Grab tile id
        $sql = "SELECT tile_id FROM tile WHERE (tile_type_arg='1') AND (tile_location='village') AND (tile_location_arg='" . $player_id . "')";
        $tid = self::getUniqueValueFromDB($sql);  // is this unique?!

        // Grab resources on tile, and skills in hand
        $sql = "SELECT card_id id, card_type type FROM resource WHERE card_location='tile' AND card_location_arg='" . $tid . "'";
        $tile_resources = self::getCollectionFromDB($sql);
        $sql = "SELECT card_id id, card_type type FROM skill WHERE card_location='hand' AND card_location_arg='" . $player_id . "'";
        $hand_skills = self::getCollectionFromDB($sql);

        $html_cost = '';
        $resource_ids = array();

        foreach ($payment_options as $po) { // should only be one.
            if (is_int($po)) { // pop stand-alone items into array for easier processing below.
                $p = $po;
                $po = array();
                array_push($po, $p);
            }

            foreach ($po as $item) {  //something wrong with this line - invalid argument?! (upgrading with anvil).
                $paid = false;
                foreach ($tile_resources as $key => $tr) {
                    if ((!$paid) && (self::getResourceMapFromType('resource', $tr['type']) == $item)) {
                        $sql = "UPDATE resource SET card_location='bag', card_location_arg='1' WHERE card_id='" . $tr['id'] . "'";
                        self::dbquery($sql);
                        unset($tile_resources[$key]);
                        $paid = true;

                        $html_cost .= '<div class="resource resource' . $tr['type'] . '"></div>';
                        $resource_ids[] = $tr['id'];
                    }
                }
                foreach ($hand_skills as $key => $hs) {
                    if ((!$paid) && (self::getResourceMapFromType('skill', $hs['type']) == $item)) {
                        $sql = "UPDATE skill SET card_location='bag', card_location_arg='1' WHERE card_id='" . $hs['id'] . "'";
                        self::dbquery($sql);
                        unset($hand_skills[$key]);
                        $paid = true;

                        $html_cost .= '<div class="skill skill' . $hs['type'] . '"></div>';
                    }
                }
            }
        }


        // Update number of upgrades remaining
        $sql = "SELECT player_upgrade FROM player WHERE player_id='" . $player_id . "'";
        $upgrade = self::getUniqueValueFromDB($sql);

        $upgrade--;
        $sql = "UPDATE player SET player_upgrade='" . $upgrade . "' WHERE player_id='" . $player_id . "'";
        self::DbQuery($sql);

        //Shuffle the bags.
        $this->workers->shuffle('bag');
        $this->skills->shuffle('bag');
        $this->resources->shuffle('bag');

        // Get points before / after upgrade
        $before = self::getUniqueValueFromDB("SELECT victory_points FROM victory WHERE victory_tile_id='$tid' AND victory_tile_side='0' AND victory_type='standard'");
        $after = self::getUniqueValueFromDB("SELECT victory_points FROM victory WHERE victory_tile_id='$tid' AND victory_tile_side='1' AND victory_type='standard'");
        if ($before === null)
            $before = 0;
        if ($after === null)
            $after = 0;
        $win = $after - $before;

        //Upgrade the tile
        $sql = "UPDATE tile SET tile_side='1', tile_type_arg='0', tile_scored='$after' WHERE tile_id='" . $tid . "'";
        self::dbquery($sql);


        $args = array(
            'i18n' => array('tile'),
            'player_id' => $player_id,
            'tile' => $this->TILES[$tid]['name'],
            'tile_id' => $tid,
            'cost' => $html_cost,
            'resource_ids' => $resource_ids,
            'points' => $win
        );

        $this->notify->player($player_id, 'handUpdate', '', self::getHand($player_id));

        $this->playerStats->inc('tile_flipped', 1, $player_id);

        if ($win == 0) {
            $this->notify->all("upgrade", clienttranslate('${player_name} upgrades ${tile} for ${cost}'), $args);
        } else {
            self::updateScore($player_id);

            $this->notify->all("upgrade", clienttranslate('${player_name} upgrades ${tile} for ${cost} and scores ${points} points.'), $args);
        }
    }

    //////////////////////////////////////////////////////////////////////////////
    //////////// Game state arguments
    ////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    /*

    Example for game state "MyGameState":

    function argMyGameState()
    {
        // Get some values from the current game situation in database...

        // return values:
        return array(
            'variable1' => $value1,
            'variable2' => $value2,
            ...
        );
    }
    */
    //////////////////////////////////////////////////////////////////////////////
    //////////// Game state actions
    ////////////

    /*
        Here, you can create methods defined as "game state actions" (see "action" property in states.inc.php).
        The action method of state X is called everytime the current game state is set to X.
    */

    /*

    Example for game state "MyGameState":

    function stMyGameState()
    {
        // Do some stuff ...

        // (very often) go to another gamestate
        $this->gamestate->nextState( 'some_gamestate_transition' );
    }
    */

    function stWinterConfirmation()
    {
        // Put the winter tiles in the market?
    }

    //////////////////////////////////////////////////////////////////////////////
    //////////// Zombie
    ////////////

    /*
        zombieTurn:

        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).
    */

    function zombieTurn($state, $active_player)
    {
        $statename = $state['name'];

        if ($state['type'] == "activeplayer") {
            switch ($statename) {
                default:
                    $this->gamestate->nextState("zombiePass");
                    break;
            }

            return;
        }

        if ($state['type'] == "multipleactiveplayer") {
            // Make sure player is in a non blocking status for role turn
            $sql = "
                UPDATE  player
                SET     player_is_multiactive = 0
                WHERE   player_id = $active_player
            ";
            self::DbQuery($sql);

            $this->gamestate->updateMultiactiveOrNextState('');
            return;
        }

        throw new UserException("Zombie mode not supported at this game state: " . $statename);
    }
}
