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

use Bga\GameFramework\Actions\Types\IntArrayParam;
use Bga\GameFramework\StateType;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\keyflowerrewrite\Game;

    /*
    30 => array(
    		"name" => "playerTurn",
    		"description" => clienttranslate('${actplayer} must either bid for a tile, activate a tile, or pass.'),
    		"descriptionmyturn" => clienttranslate('${you} must either bid for a tile, activate a tile, or '),
    		"type" => "activeplayer",
            "args" => "argPlayerTurn",
    		"possibleactions" => array(
                "actPlaceWorkersWithLocation",
                "actPass"
            ),
            "updateGameProgression" => true,
            "transitions" => array( "workersPlaced" => 40, "pass" => 50)
    ),
    */

class PlayerTurn extends AbstractState
{
    function __construct(Game $game)
    {
        parent::__construct(
            game: $game,
            id: 30,
            type: StateType::ACTIVE_PLAYER,
    		description: clienttranslate('${actplayer} must either bid for a tile, activate a tile, or pass.'),
    		descriptionMyTurn: clienttranslate('${you} must either bid for a tile, activate a tile, or '),
            updateGameProgression: true,
        );
    }

    public function getArgs(int $active_player_id): array
    {
        // Return possible location for a worker to be placed.
        return [
            'possibleLocations' => $this->getPossibleLocations(),
            'possibleBids' => $this->getPossibleBids(),
            'outbid' => $this->getOutbidWorkers($active_player_id)
        ];
    }

	function zombie(int $player_id): mixed {
        return $this->actPass();
    }

    #[PossibleAction]
    public function actPass(): mixed
    {
        $this->notify->all("playerPass", clienttranslate('${player_name} passes.'), array('player_name' => $this->game->getActivePlayerName()));
        $sql = "UPDATE player SET player_passed = TRUE WHERE player_id = '" . $this->game->getActivePlayerId() . "'";
        $this->game->DbQuery($sql);
        return EndTurn::class;
    }

    #[PossibleAction]
    public function actPlaceWorkersWithLocation(int $active_player_id, string $type, int $id, int $color_id, int $count, #[IntArrayParam()] ?array $colors, #[IntArrayParam()] ?array $counts, #[IntArrayParam()] ?array $outbids): mixed
    {
        return $this->doPlaceWorkersWithLocation(false, $active_player_id, $type, $id, $color_id, $count, $colors ?? [], $counts ?? [], $outbids ?? []);
    }

    private function getPossibleLocations()
    {
        // This will essentially be all of the village tiles and the seasons new stock.
        $sql = "SELECT tile_id id FROM tile WHERE (tile_location = 'village') OR (tile_type = '" . $this->getSeasonString($this->game->getSeason()) . "')";
        return $this->game->getCollectionFromDb($sql);
    }

    private function getPossibleBids(): array
    {
        // This will essentially be all of the market tiles.
        $sql = "SELECT market_id id FROM market";
        return $this->game->getCollectionFromDb($sql);
    }
}
