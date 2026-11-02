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
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\keyflowerrewrite\Game;

    /*
    47 => array("name" => "villageUpgrade",
                "description" => clienttranslate('${actplayer} must select which tile to upgrade'),
                "descriptionmyturn" => clienttranslate('${you} must select which tile to upgrade or '),
                "type" => "activeplayer",
                "args" => "argVillageUpgrade",
                "possibleactions" => array( "actSelectUpgradeTile", "actSkipUpgrade" ),
                "transitions" => array( "selectUpgradeTile" => 48, "skipUpgrade" => 44, "zombiePass" => 50)
    ),
    */

class VillageUpgrade extends AbstractState
{
    function __construct(Game $game)
    {
        parent::__construct(
            game: $game,
            id: 47,
            type: StateType::ACTIVE_PLAYER,
            description: clienttranslate('${actplayer} must select which tile to upgrade'),
            descriptionMyTurn: clienttranslate('${you} must select which tile to upgrade or '),
            updateGameProgression: false,
        );
    }

    function getArgs(): mixed
    {
        /*
         "descriptionmyturn" => clienttranslate('${you} must select which tile to upgrade'),
                         "transitions" => array( "selectUpgradeTile" => 48, "passUpgrades" => 44)
         */

        // Only return those tiles that have the resources on them to match the cost of the upgrade.
        return [
            'upgradeTiles' => $this->game->getUpgradeableTiles($this->game->getCurrentPlayerId())
        ];
    }



    #[PossibleAction]
    function actSkipUpgrade(int $active_player_id): mixed
    {
        $sql = "UPDATE player SET player_upgrade='0' WHERE (player_id = '" . $active_player_id . "')";
        $this->game->DbQuery($sql);
        return VillageBuilding::class;
    }



    #[PossibleAction]
    function actSelectUpgradeTile(int $active_player_id, int $tid): mixed
    {

        $bFree = false;
        if ($this->game->checkAction("actSelectUpgradeTile", false))
            $bFree = true;

        // check tile can be upgraded.
        $upgradeTiles = $this->game->getUpgradeableTiles($active_player_id, $bFree);

        if (!in_array($tid, $upgradeTiles)) {
            throw new \BgaUserException("Invalid selection");
        }

        $sql = "UPDATE tile SET tile_type_arg='1' WHERE (tile_id='" . $tid . "')";
        $this->game->DbQuery($sql);

        return VillageUpgradePayment::class;
    }

    function zombie(): mixed {
        return EndTurn::class;
    }
}
