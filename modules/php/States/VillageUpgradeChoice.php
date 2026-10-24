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

use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\StateType;
use Bga\Games\keyflowerrewrite\Game;


    /*
    49 => array("name" => "villageUpgradeChoice",
                "description" => clienttranslate('${actplayer} must choose a payment method'),
                "descriptionmyturn" => clienttranslate('${you} must choose your payment method'),
                "type" => "activeplayer",
                "args" => "argVillageUpgradeChoice",
                "possibleactions" => array( "actSelectPaymentMethod" ),
                "transitions" => array( "selectPaymentMethod" => 44, "zombiePass" => 50)
    ),
    */
class VillageUpgradeChoice extends AbstractState
{
    function __construct(Game $game)
    {
        parent::__construct(
            game: $game,
            id: 49,
            type: StateType::ACTIVE_PLAYER,
            description: clienttranslate('${actplayer} must choose a payment method'),
            descriptionMyTurn: clienttranslate('${you} must choose your payment method'),
            updateGameProgression: false,
        );
    }

    function getArgs(): mixed
    {
        /*
         "descriptionmyturn" => clienttranslate('${you} must choose your payment method'),
         "possibleactions" => array( "selectPaymentMethod" ),
         */
        return [
            'upgradeChoice' => $this->getUpgradeCostChoice()
        ];
    }

    #[PossibleAction]
    function actSelectPaymentMethod(int $active_player_id, int $id): mixed
    {
        /*
         id is the index of the array
         */
        $upgradeCostChoices = $this->getUpgradeCostChoice();
        if ($id > sizeOf($upgradeCostChoices)) {
            throw new \BgaUserException("Invalid selection");
        }

        $payment_options = array();
        $chosen = $upgradeCostChoices[$id];
        foreach ($chosen as $good) {
            array_push($payment_options, $this->game->getResourceMapFromType($good['table'], $good['type']));
        }

        $this->game->payForTileUpgrade($active_player_id, $payment_options);

        return VillageBuilding::class;
    }

    private function getUpgradeCostChoice(): array
    {

        $player_id = $this->game->getCurrentPlayerId();

        $sql = "SELECT tile_id FROM tile WHERE (tile_type_arg='1') AND (tile_location='village') AND (tile_location_arg='" . $player_id . "')";
        $tid = $this->game->getUniqueValueFromDB($sql);  // is this unique?!

        $payment_options = $this->game->getUpgradeCostOptions($tid);

        // Remove multiple payment options
        foreach ($payment_options as $option_id => $option) {
            if (is_array($payment_options[$option_id])) {
                asort($payment_options[$option_id]);
                $payment_options[$option_id] = implode('#', $payment_options[$option_id]);
            }
        }
        $payment_options = array_unique($payment_options);
        foreach ($payment_options as $option_id => $option) {
            $payment_options[$option_id] = explode('#', $payment_options[$option_id]);
        }

        $choices = array();
        foreach ($payment_options as $option) {
            $current = array();
            // FIXME: delete this
            /*
            if (is_int($option)) {
                $dummy = array();
                array_push($dummy, $option);
                $option = $dummy;
            }
            */
            foreach ($option as $item) {
                $data = $this->game->getMapItem($item);
                array_push($current, array('table' => $data['table'], 'type' => $data['type']));
            }
            array_push($choices, $current);
        }

        return $choices;
    }

    function zombie(): mixed {
        return EndTurn::class;
    }

}
