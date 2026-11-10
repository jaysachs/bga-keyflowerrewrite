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

use Bga\GameFramework\Table as GameFrameworkTable;
use Bga\Games\keyflowerrewrite\Model\AllGameData;
use Bga\Games\keyflowerrewrite\Model\Marshaller;
use Bga\Games\keyflowerrewrite\Model\Model;
use Bga\Games\keyflowerrewrite\Model\PersistentStore;
use Bga\Games\keyflowerrewrite\States\SeasonSetup;
use Bga\Games\keyflowerrewrite\Utils\Arrays;
use Bga\Games\keyflowerrewrite\Utils\DefaultDb;
use Bga\Games\keyflowerrewrite\Utils\Log;
use Bga\Games\keyflowerrewrite\Utils\Logger;

class GameLogger implements Logger {
	function __construct(private GameFrameworkTable $table) { }

	public function debug(string $msg): void {
		$this->table->debug($msg);
	}

	public function dump(string $prefix, mixed $obj): void {
		$this->table->dump($prefix, $obj);
	}

	public function error(string $msg): void {
		$this->table->error($msg);
	}

	public function trace(string $msg): void {
		$this->table->trace($msg);
	}

	public function warn(string $msg): void {
		$this->table->warn($msg);
	}
}

class Game extends \Bga\GameFramework\Table
{
	// private Stats $stats;
    private PersistentStore $ps;

    function __construct()
    {
        parent::__construct();

		Log::setImpl(new GameLogger($this));
		$logDecorator = new LogDecorator(\Closure::fromCallable($this->getPlayerNameById(...)));
		$this->notify->addDecorator($logDecorator->playerNames(...));
        $this->bga->notify->alwaysMergePrivate();
 		// $this->stats = Stats::createForGame($this);
        $this->ps = new PersistentStore(new DefaultDb(), $this->globals);

		$this->initGameStateLabels([]);
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
        $default_colors = ["ff0000", "00d27f", "00aedb", "ffa500", "a200ff", "000000"];
        $colors = $default_colors;
        Arrays::shuffle($colors);
        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
        $sql = "INSERT INTO player (player_id, player_color, player_name) VALUES ";
        $values = [];
        foreach ($players as $player_id => $player) {
            $color = array_shift($colors);
            $values[] = "('" . $player_id . "','$color','" . addslashes($player['player_name']) . "')";
        }
        $sql .= implode(',', $values);
        self::DbQuery($sql);
        self::reattributeColorsBasedOnPreferences($players, $default_colors);
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

        $data = AllGameData::newGameDataFor(array_keys($players));
        $this->ps->saveAllData($data);

        return SeasonSetup::class;
    }

    public function createModel(int $active_player_id): Model {
        return new Model($active_player_id, $this->ps);
    }

    /**
     * @return array<string,mixed>
     */
    protected function getAllDatas(int $current_player_id): array
    {
        $model = $this->createModel($current_player_id);
        $data = $model->allData();

        $startPlayerId = 0;
        $sv = [];
        foreach ($data->villages as $village) {
            $playerId = $village->playerId;
            if ($village->hasStartPlayer()) {
                $startPlayerId = $playerId;
            }
            $sv[$playerId] = Marshaller::village($village, $current_player_id == $playerId);
        }
        return [
            'season' => $data->season?->value,
            'startPlayerId' => $startPlayerId,
            'dock' => Marshaller::dock($data->dock),
            'market' => Marshaller::market($data->market),
            'skillCount' => $data->skills->size(),
            'greenCount' => count($data->greens),
            'bagCount' => $data->bagWorkers->size(),
            'villages' => $sv,
        ];
    }

    function getGameProgression(): int
    {
        return 0;
    }
}
