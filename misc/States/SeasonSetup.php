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
use UnexpectedValueException;

class SeasonSetup extends AbstractState
{
    function __construct(Game $game)
    {
        parent::__construct(
            game: $game,
            id: 10,
            type: StateType::GAME,
            description: clienttranslate('Setting up season'),
            updateGameProgression: false,
        );
    }

    public function onEnteringState(): mixed {
        // Roll on to the next season
        $lastSeason = $this->game->getSeason();
        $currentSeason = match ($lastSeason) {
            0 => 'spring',
            1 => 'summer',
            2 => 'autumn',
            3 => 'winter',
            default => throw new UnexpectedValueException("unknown season {$lastSeason}")
        };
        $this->game->setGameStateValue('season', $lastSeason + 1);

        $this->game->workers->shuffle('bag');

        // A: Boats: Flip to correct side for the season. Unless winter, replenish their reward (special rule re: order).
        switch ($currentSeason) {  // boats are 7-12 (assume first side is spring for all.
            case 'summer':  //boat 9 needs to be flipped.
                $sql = "UPDATE tile SET tile_side = '1', tile_type_arg='6' WHERE tile_id = '9'";
                $this->game->DbQuery($sql);
                break;
            case 'autumn':  //boats 7,10,11,12 needs to be flipped (9 was flipped for summer).
                $sql = "UPDATE tile SET tile_side = '1', tile_type_arg='2' WHERE tile_id = '7'";
                $this->game->DbQuery($sql);
                $sql = "UPDATE tile SET tile_side = '1', tile_type_arg='8' WHERE tile_id = '10'";
                $this->game->DbQuery($sql);
                $sql = "UPDATE tile SET tile_side = '1', tile_type_arg='10' WHERE tile_id = '11'";
                $this->game->DbQuery($sql);
                $sql = "UPDATE tile SET tile_side = '1', tile_type_arg='12' WHERE tile_id = '12'";
                $this->game->DbQuery($sql);
                break;
            case 'winter':  // flip all remaining boats
                $sql = "UPDATE tile SET tile_side = '1', tile_type_arg='0' WHERE tile_id = '8'";
                $this->game->DbQuery($sql);
                break;

            default:
                break;
        }

        //Replenish Stocks
        if ($currentSeason != 'winter') {
            $cargo_requests = $this->game->getObjectListFromDB("SELECT t.tile_id, t.tile_type_arg id, c.cargo_reward_workers workers, c.cargo_reward_skills skills FROM tile t RIGHT JOIN cargo c ON (t.tile_type_arg = c.cargo_id) WHERE (t.tile_type = 'boat')");
            $freeWorkers = $this->game->workers->countCardInLocation('bag');
            $freeSkills = $this->game->skills->countCardInLocation('bag');
            $requiredWorkers = 0;
            $requiredSkills = 0;
            foreach ($cargo_requests as $cargo_id => $cargo) {
                $requiredWorkers = $requiredWorkers + $cargo['workers'];
                $requiredSkills = $requiredSkills + $cargo['skills'];
            }
            if ($freeWorkers >= $requiredWorkers) {
                //allocate all at once
                foreach ($cargo_requests as $cargo_id => $cargo) {
                    $this->game->workers->pickCardsForLocation($cargo['workers'], 'bag', 'cargo', $cargo['tile_id'], false);
                }
            } else {
                $allocated = 0;
                // need to go through one by one...
                $allottedWorkers = array();
                while ($freeWorkers > $allocated) {
                    foreach ($cargo_requests as $cargo_id => $cargo) {
                        if (!isset($allottedWorkers[$cargo_id])) {
                            $allottedWorkers[$cargo_id] = 0;
                        }
                        if (($allottedWorkers[$cargo_id]  < $cargo['workers']) && ($freeWorkers > $allocated)) {
                            $this->game->workers->pickCardForLocation('bag', 'cargo', $cargo['tile_id']);
                            $allocated++;
                            $allottedWorkers[$cargo_id] = $allottedWorkers[$cargo_id] + 1;
                        }
                    }
                }
            }
            if ($freeSkills >= $requiredSkills) {
                //allocate all at once.
                foreach ($cargo_requests as $cargo_id => $cargo) {
                    $this->game->skills->pickCardsForLocation($cargo['skills'], 'bag', 'cargo', $cargo['tile_id'], false);
                }
            } else {
                // need to go through one by one...
                $allocated = 0;
                // need to go through one by one...
                $allottedSkills = array();
                while ($freeSkills > $allocated) {
                    foreach ($cargo_requests as $cargo_id => $cargo) {
                        if (!isset($allottedSkills[$cargo_id])) {
                            $allottedSkills[$cargo_id] = 0;
                        }
                        if (($allottedSkills[$cargo_id]  < $cargo['skills']) && ($freeSkills > $allocated)) {
                            $this->game->skills->pickCardForLocation('bag', 'cargo', $cargo['tile_id']);
                            $allocated++;
                            $allottedSkills[$cargo_id] = $allottedSkills[$cargo_id] + 1;
                        }
                    }
                }
            }
        }
        $this->notify->all("cargoUpdate", clienttranslate('The ships arrive with new cargo'), array('cargo' => $this->game->getCargo()));

        // B: Create the market lots from this season's stock. Winter selection can be done during the tidy up of the autumn season.
        $sql = "SELECT tile_id FROM tile WHERE (tile_type = '" . $currentSeason . "') OR (tile_type='turn')";
        $villageTiles = $this->game->getCollectionFromDb($sql);
        foreach ($villageTiles as $tile_id => $tile) {
            $sql = "INSERT INTO market (market_tile_id) VALUES ('" . $tile['tile_id'] . "')";
            $this->game->DbQuery($sql);
        }
        $this->notify->all("marketUpdate", clienttranslate('The market is updated with new tiles for ${season}'), array(
            'market_data' => $this->game->getMarketData(),
            'bid_data' => $this->game->getBiddingData(),
            'action_data' => $this->game->getActionData(),
            'season' => $currentSeason,
            'i18n' => 'season'
        ));


        // C: Activate first player (the one with starter worker).
        $sql = "SELECT card_location_arg FROM worker WHERE card_type='5'";
        $starter_id = $this->game->getCollectionFromDb($sql);

        // will only be one.
        foreach ($starter_id as $sid => $starter) {
            $this->gamestate->changeActivePlayer($sid);
        }

        return PlayerTurn::class;
    }
}
