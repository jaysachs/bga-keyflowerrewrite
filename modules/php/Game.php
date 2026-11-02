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
 * In this PHP file, you are going to defines the rules of the game.
 *
 */

declare(strict_types=1);

namespace Bga\Games\keyflowerrewrite;

class Game extends \Bga\GameFramework\Table
{
    function __construct()
    {
        parent::__construct();

		$logDecorator = new LogDecorator(\Closure::fromCallable($this->getPlayerNameById(...)));
		$this->notify->addDecorator($logDecorator->playerNames(...));

        self::initGameStateLabels(array(
            "season" => 10
        ));

    }

    /*
        setupNewGame:

        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    /**
     * @param array<int,array{player_name:string}> $players
     * @param array<string,mixed> $options
     */
    protected function setupNewGame($players, $options = [])
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

        /************ End of the game initialization *****/
        return 10;
    }
    /*
        getAllDatas:

        Gather all informations about current game situation (visible by the current player).

        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
        /**
         * @return array<string,mixed>
         */
    protected function getAllDatas(int $current_player_id): array
    {
        return [];
    }

    /*
        getGameProgression:

        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).

        This method is called each time we are in a game state with the "updateGameProgression" property set to true
        (see states.inc.php)
    */
    function getGameProgression(): int
    {
        return 0;
    }
}
