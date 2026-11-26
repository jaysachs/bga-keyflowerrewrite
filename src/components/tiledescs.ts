import { TileType } from "../kfdata";

export interface TileDescription {
    // what the tile does ("Produces 2 stone.");
    summary?: string;

    // how to upgrade it ("Can be upgraded for 1 pickaxe to produce 1 gold and 1 stone.");
    upgrade?: string;

    // scoring, when upgraded or at the end of the game ("Grants 3 points when upgraded.");
    scoring?: string;

    // notes ("Workers scored here cannot be scored on another tile.").
    notes?: string;
}

export const TILE_DESCRIPTIONS : Record<TileType,TileDescription> = {
    home1: {
        summary: _("Allows transporting resources over 1 tile, then upgrading 1 tile."),
        scoring: _("Grants 5 ${points} when upgraded."),
        upgrade: _("Can be upgraded for 1 ${stone} and 1 ${wood} to allow transporting resources over 2 tiles, then upgrading 1 tile.")
    },
    home2: {
        summary: _("Allows transporting resources over 1 tile, then upgrading 1 tile."),
        scoring: _("Grants 5 ${points} when upgraded."),
        upgrade: _("Can be upgraded for 1 ${iron} and 1 ${stone} to allow transporting resources over 2 tiles, then upgrading 1 tile.")
    },
    home3: {
        summary: _("Allows transporting resources over 1 tile, then upgrading 1 tile."),
        scoring: _("Grants 5 ${points} when upgraded."),
        upgrade: _("Can be upgraded for 1 ${wood} and 1 ${iron} to allow transporting resources over 2 tiles, then upgrading 1 tile.")
    },
    home4: {
        summary: _("Allows transporting resources over 1 tile, then upgrading 1 tile."),
        scoring: _("Grants 5 ${points} when upgraded."),
        upgrade: _("Can be upgraded for 1 ${wood} and 1 ${stone} to allow transporting resources over 2 tiles, then upgrading 1 tile.")
    },
    home5: {
        summary: _("Allows transporting resources over 1 tile, then upgrading 1 tile."),
        scoring: _("Grants 5 ${points} when upgraded."),
        upgrade: _("Can be upgraded for 1 ${stone} and 1 ${iron} to allow transporting resources over 2 tiles, then upgrading 1 tile.")
    },
    home6: {
        summary: _("Allows transporting resources over 1 tile, then upgrading 1 tile."),
        scoring: _("Grants 5 ${points} when upgraded."),
        upgrade: _("Can be upgraded for 1 ${iron} and 1 ${wood} to allow transporting resources over 2 tiles, then upgrading 1 tile.")
    },
    home1_u: {
        summary: _("Allows transporting resources over 2 tiles, then upgrading 1 tile."),
        scoring: _("Grants 5 ${points}.")
    },
    home2_u: {
        summary: _("Allows transporting resources over 2 tiles, then upgrading 1 tile."),
        scoring: _("Grants 5 ${points}.")
    },
    home3_u: {
        summary: _("Allows transporting resources over 2 tiles, then upgrading 1 tile."),
        scoring: _("Grants 5 ${points}.")
    },
    home4_u: {
        summary: _("Allows transporting resources over 2 tiles, then upgrading 1 tile."),
        scoring: _("Grants 5 ${points}.")
    },
    home5_u: {
        summary: _("Allows transporting resources over 2 tiles, then upgrading 1 tile."),
        scoring: _("Grants 5 ${points}.")
    },
    home6_u: {
        summary: _("Allows transporting resources over 2 tiles, then upgrading 1 tile."),
        scoring: _("Grants 5 ${points}.")
    },
    alehouse: {
        summary: _("Brings 1 ${worker}."),
        upgrade: _("Can be upgraded for 1 ${iron}, 1 ${stone} and 1 ${wood} to enable bringing 2 ${workers}.")
    },
    apprenticehall: {
        summary: _("Produces 1 ${skill}."),
        scoring: _("Grants 3 ${points} when upgraded."),
        upgrade: _("Can be upgraded for 1 ${stone} and 1 ${wood} to produce 2 ${skills}.")
    },
    goldmine: {
        summary: _("Produces 1 ${gold}."),
        scoring: _("Grants 3 ${points} when upgraded."),
        upgrade: _("Can be upgraded for 1 ${iron} and 1 ${stone} to produce 2 ${gold}.")
    },
    keymine: {
        summary: _("Produces 1 ${iron}."),
        scoring: _("Grants 3 ${points} when upgraded."),
        upgrade: _("Can be upgraded for 1 ${anvil} to produce 3 ${iron}.")
    },
    keystonequarry: {
        summary: _("Produces 1 ${stone}."),
        scoring: _("Grants 3 ${points} when upgraded."),
        upgrade: _("Can be upgraded for 1 ${pickaxe} to produce 3 ${stone}.")
    },
    keywood: {
        summary: _("Produces 1 ${wood}."),
        scoring: _("Grants 3 ${points} when upgraded."),
        upgrade: _("Can be upgraded for 1 ${saw} to produce 3 ${wood}.")
    },
    miner: {
        summary: _("Produces 2 ${iron}."),
        scoring: _("Grants 3 ${points} when upgraded."),
        upgrade: _("Can be upgraded for 1 ${anvil} to produce 1 ${gold} and 1 ${iron}.")
    },
    peddlar: {
        summary: _("Brings 1 ${green} but 1 ${yellow} has to be discarded in exchange."),
        upgrade: _("Can be upgraded for 1 ${gold} to enable bringing 2 ${green}.")
    },
    quarryman: {
        summary: _("Produces 2 ${stone}."),
        scoring: _("Grants 3 ${points} when upgraded."),
        upgrade: _("Can be upgraded for 1 ${pickaxe} to produce 1 ${gold} and 1 ${stone}.")
    },
    stable: {
        summary: _("Allows transporting resources over 2 tiles, then upgrading 1 tile."),
        scoring: _("Grants 5 ${points} when upgraded."),
        upgrade: _("Can be upgraded for 1 ${wood} and 1 ${iron} to allow transporting resources over 4 tiles, then upgrading 2 tiles.")
    },
    woodcutter: {
        summary: _("Produces 2 ${wood}."),
        scoring: _("Grants 3 ${points} when upgraded."),
        upgrade: _("Can be upgraded for 1 ${saw} to produce 1 ${gold} and 1 ${wood}.")
    },
    workshop: {
        summary: _("Produces 1 basic resource: ${iron}, ${stone} or ${wood}."),
        scoring: _("Grants 3 ${points} when upgraded."),
        upgrade: _("Can be upgraded for 2 ${resources} to produce 1 ${iron}, 1 ${stone} and 1 ${wood}.")
    },
    alehouse_u: {
        summary: _("Brings 2 ${workers}.")
    },
    apprenticehall_u: {
        summary: _("Produces 2 ${skills}."),
        scoring: _("Grants 3 ${points}.")
    },
    goldmine_u: {
        summary: _("Produces 2 ${gold}."),
        scoring: _("Grants 3 ${points}.")
    },
    keymine_u: {
        summary: _("Produces 3 ${iron}."),
        scoring: _("Grants 3 ${points}.")
    },
    keystonequarry_u: {
        summary: _("Produces 3 ${stone}."),
        scoring: _("Grants 3 ${points}.")
    },
    keywood_u: {
        summary: _("Produces 3 ${wood}."),
        scoring: _("Grants 3 ${points}.")
    },
    miner_u: {
        summary: _("Produces 1 ${gold} and 1 ${iron}."),
        scoring: _("Grants 3 ${points}.")
    },
    peddlar_u: {
        summary: _("Brings 2 ${green} but 1 ${yellow} has to be discarded in exchange.")
    },
    quarryman_u: {
        summary: _("Produces 1 ${gold} and 1 ${stone}."),
        scoring: _("Grants 3 ${points}.")
    },
    stable_u: {
        summary: _("Allows transporting resources over 4 tiles, then upgrading 2 tiles."),
        scoring: _("Grants 5 ${points}.")
    },
    woodcutter_u: {
        summary: _("Produces 1 ${gold} and 1 ${wood}."),
        scoring: _("Grants 3 ${points}.")
    },
    workshop_u: {
        summary: _("Produces 1 ${iron}, 1 ${stone} and 1 ${wood}."),
        scoring: _("Grants 3 ${points}.")
    },
    brewer: {
        summary: _("Brings 2 ${workers} but 1 ${skill} is consumed in the process."),
        scoring: _("Grants 3 ${points} when upgraded."),
        upgrade: _("Can be upgraded for 1 ${stone} and 1 ${iron} to enable bringing 3 ${workers}.")
    },
    carpenter: {
        summary: _("Produces 3 ${wood} but 1 ${saw} is consumed in the process."),
        scoring: _("Grants 7 ${points} when upgraded."),
        upgrade: _("Can be upgraded for 3 ${wood} to produce 3 ${gold}.")
    },
    farrier: {
        summary: _("Allows transporting resources over 3 tiles, then upgrading 1 tile."),
        scoring: _("Grants 5 ${points} when upgraded."),
        upgrade: _("Can be upgraded for 2 ${resources} to allow transporting resources over 5 tiles, then upgrading 2 tiles.")
    },
    hiringfair: {
        summary: _("Produces 2 ${skills} but 1 ${skill} is consumed in the process."),
        scoring: _("Grants 5 ${points} when upgraded."),
        upgrade: _("Can be upgraded for 1 ${wood} and 1 ${iron} to produce 3 ${skills}.")
    },
    mason: {
        summary: _("Produces 3 ${stone} but 1 ${pickaxe} is consumed in the process."),
        scoring: _("Grants 7 ${points} when upgraded."),
        upgrade: _("Can be upgraded for 3 ${stone} to produce 3 ${gold}.")
    },
    smelter: {
        summary: _("Produces 3 ${iron} but 1 ${anvil} is consumed in the process."),
        scoring: _("Grants 7 ${points} when upgraded."),
        upgrade: _("Can be upgraded for 3 ${iron} to produce 3 ${gold}.")
    },
    store: {
        summary: _("Brings 1 ${green} but 1 ${blue} has to be discarded in exchange."),
        scoring: _("Grants 3 ${points} when upgraded."),
        upgrade: _("Can be upgraded for 1 ${gold} to enable bringing 2 ${green}.")
    },
    tavern: {
        summary: _("Brings 2 ${workers} but 1 ${worker} has to be discarded in exchange."),
        upgrade: _("Can be upgraded for 1 ${iron}, 1 ${stone} and 1 ${wood} to enable bringing 3 ${workers}.")
    },
    brewer_u: {
        summary: _("Brings 3 ${workers} but 1 ${skill} is consumed in the process."),
        scoring: _("Grants 3 ${points}.")
    },
    carpenter_u: {
        summary: _("Produces 3 ${gold} but 1 ${saw} is consumed in the process."),
        scoring: _("Grants 7 ${points}.")
    },
    farrier_u: {
        summary: _("Allows transporting resources over 5 tiles, then upgrading 2 tiles."),
        scoring: _("Grants 5 ${points}.")
    },
    hiringfair_u: {
        summary: _("Produces 3 ${skills} but 1 ${skill} is consumed in the process."),
        scoring: _("Grants 5 ${points}.")
    },
    mason_u: {
        summary: _("Produces 3 ${gold} but 1 ${pickaxe} is consumed in the process."),
        scoring: _("Grants 7 ${points}.")
    },
    smelter_u: {
        summary: _("Produces 3 ${gold} but 1 ${anvil} is consumed in the process."),
        scoring: _("Grants 7 ${points}.")
    },
    store_u: {
        summary: _("Brings 2 ${green} but 1 ${blue} has to be discarded in exchange."),
        scoring: _("Grants 3 ${points}.")
    },
    tavern_u: {
        summary: _("Brings 3 ${workers} but 1 ${worker} has to be discarded in exchange.")
    },
    barn: {
        summary: _("Grants 1 ${point} for each resource assigned to this tile (the resource must be found on this tile)."),
        scoring: _("Resources scored here cannot be scored elsewhere."),
        upgrade: _("Can be upgraded for 1 ${skill} to grant 2 ${points} for each assigned resource.")
    },
    blacksmith: {
        summary: _("Grants 2 ${points} for each ${iron} assigned to this tile (the resource must be found on this tile)."),
        scoring: _("Resources scored here cannot be scored elsewhere."),
        upgrade: _("Can be upgraded for 1 ${anvil} to grant 3 ${points} for ${iron} on this tile.")
    },
    fair: {
        summary: _("Brings 1 ${green} but 1 ${red} has to be discarded in exchange."),
        scoring: _("Grants 5 ${points} when upgraded."),
        upgrade: _("Can be upgraded for 1 ${gold} to enable bringing 2 ${green}.")
    },
    forge: {
        scoring: _("Grants 3 ${points} or 10 ${points} when upgraded."),
        upgrade: _("Can be upgraded for 3 ${iron}.")
    },
    goldsmith: {
        scoring: _("Grants 7 ${points} when upgraded."),
        upgrade: _("Can be upgraded for 2 ${gold}.")
    },
    inn: {
        summary: _("Brings 1 ${worker}."),
        upgrade: _("Can be upgraded for 1 ${skill} and 1 ${resource} to enable bringing 3 ${workers}.")
    },
    sawmill: {
        scoring: _("Grants 3 ${points} or 10 ${points} when upgraded."),
        upgrade: _("Can be upgraded for 3 ${wood}.")
    },
    sculptor: {
        scoring: _("Grants 3 ${points} or 10 ${points} when upgraded."),
        upgrade: _("Can be upgraded for 3 ${stone}.")
    },
    stoneyard: {
        scoring: _("Grants 2 ${points} for each ${stone} assigned to this tile (the resource must be found on this tile)."),
        upgrade: _("Can be upgraded for 1 ${pickaxe} to grant 3 ${points} for ${stone} on this tile."),
        notes: _("Resources scored here cannot be scored elsewhere.")
    },
    timberyard: {
        scoring: _("Grants 2 ${points} for each ${wood} assigned to this tile (the resource must be found on this tile)."),
        upgrade: _("Can be upgraded for 1 ${saw} to grant 3 ${points} for ${wood} on this tile."),
        notes: _("Resources scored here cannot be scored elsewhere.")
    },
    wainwright: {
        summary: _("Allows transporting resources over 4 tiles, then upgrading 2 tiles."),
        scoring: _("Grants 5 ${points} when upgraded."),
        upgrade: _("Can be upgraded for 2 ${resources} to allow transporting resources over 6 tiles, then upgrading 2 tiles.")
    },
    well: {
        scoring: _("Grants 3 ${points} or 7 ${points} when upgraded."),
        upgrade: _("Can be upgraded for 3 ${resources}.")
    },
    barn_u: {
        scoring: _("Grants 2 ${points} for each resource assigned to this tile (the resource must be found on this tile)."),
        notes: _("Resources scored here cannot be scored elsewhere.")
    },
    blacksmith_u: {
        scoring: _("Grants 3 ${points} for each ${iron} assigned to this tile (the resource must be found on this tile)."),
        notes: _("Resources scored here cannot be scored elsewhere.")
    },
    fair_u: {
        summary: _("Brings 2 ${green} but 1 ${red} has to be discarded in exchange."),
        scoring: _("Grants 5 ${points}.")
    },
    forge_u: {
        scoring: _("Grants 10 ${points}.")
    },
    goldsmith_u: {
        scoring: _("Grants 7 ${points}.")
    },
    inn_u: {
        summary: _("Brings 3 ${workers}.")
    },
    sawmill_u: {
        scoring: _("Grants 10 ${points}.")
    },
    sculptor_u: {
        scoring: _("Grants 10 ${points}.")
    },
    stoneyard_u: {
        scoring: _("Grants 3 ${points} for each ${stone} assigned to this tile (the resource must be found on this tile)."),
        notes: _("Resources scored here cannot be scored elsewhere.")
    },
    timberyard_u: {
        scoring: _("Grants 3 ${points} for each ${wood} assigned to this tile (the resource must be found on this tile)."),
        notes: _("Resources scored here cannot be scored elsewhere.")
    },
    wainwright_u: {
        summary: _("Allows transporting resources over 6 tiles, then upgrading 2 tiles."),
        scoring: _("Grants 5 ${points}.")
    },
    well_u: {
        scoring: _("Grants 7 ${points}.")
    },
    apothecary: {
        scoring: _("Grants 3 ${points} for each 5 workers assigned to this tile (workers played on the player's village in the last round can be used). "),
        notes: _("Workers scored here cannot be scored on another tile.")
    },
    craftsmansguild: {
        scoring: _("Grants 3 ${points} for each set of 1 ${red}, 1 ${yellow} and 1 ${blue} assigned to this tile (workers played on the player's village in the last round can be used). "),
        notes: _("Workers scored here cannot be scored on another tile.")
    },
    jeweller: {
        scoring: _("Grants 2 ${points} for each 1 ${gold} assigned to this tile (the gold can be found anywhere in the player's village). "),
        notes: _("Gold score here cannot also be scored separately or on another tile.")
    },
    keyguild: {
        scoring: _("Grants 10 ${points} for each set of 5 ${skills} assigned to this tile. "),
        notes: _("Skills scored here cannot be scored on another tile.")
    },
    keymarket: {
        scoring: _("Grants 2 ${points} for each green worker assigned to this tile, (workers played on the player's village in the last round can be used). "),
        notes: _("Workers scored here cannot be scored on another tile.")
    },
    keythedral: {
        scoring: _("Grants 12 ${points}.")
    },
    mercersguild: {
        scoring: _("Grants 5 ${points} for each set of 1 ${wood}, 1 ${iron}, 1 ${stone} assigned to this tile (the resources can be found anywhere in the player's village; gold can be used as substitute). "),
        notes: _("Resources scored here cannot be scored on another tile.")
    },
    scholar: {
        scoring: _("Grants 3 ${points} for each 1 skill assigned to this tile in a set of skills of the same type. "),
        notes: _("Skills scored here cannot be scored on another tile.")
    },
    scribes: {
        scoring: _("Grants 10 ${points} for each set of 1 anvil, 1 pick and 1 saw assigned to this tile. "),
        notes: _("Skills scored here cannot be scored on another tile.")
    },
    villagehall: {
        scoring: _("Grants 1 ${point} for each 1 worker in a set of workers of the same type assigned to this tile (workers played on the player's village in the last round can be used). "),
        notes: _("Workers scored here cannot be scored on another tile.")
    },
    watermill: {
        scoring: _("Grants 1 ${point} for each 1 resource in a set of resources of the same type assigned to this tile (the resources can be found anywhere in the player's village). "),
        notes: _("Resources scored here cannot be scored on another tile.")
    },
    windmill: {
        scoring: _("Grants 5 ${points} for each set of 5 resources of any type assigned to this tile (the resources can be found anywhere in the player's village). "),
        notes: _("Resources scored here cannot be scored on another tile.")
    },
    boat1a: {
        summary: _("When taking workers from the boats at the end of the summer and autumn, take 2 additional workers from the bag if available. When taking a boat tile at the end of the winter, take 2 workers from the bag (after all winning bids have been placed in the bag).")
    },
    boat1b: {
        summary: _("When taking workers from the boats at the end of the summer and autumn and when taking a boat at the end of the winter, take one green worker from the supply if available.")
    },
    boat2a: {
        summary: _("When transporting resources, the resources may be transported across fields as well as across roads. When placing tiles, edges do not need to match (including water). Only properly connected roads will be counted for scoring purposes.")
    },
    boat2b: {
        summary: _("All your transport and upgrade capacity is doubled. If you own the Keyflower boat at the end of the game, boat's score is doubled.")
    },
    boat3a: {
        summary: _("When upgrading, any type of resource can be used in place of any other type of resource.")
    },
    boat3b: {
        summary: _("For end-game scoring purposes, you can score any resource in place of any other resource.")
    },
    boat4a: {
        summary: _("You can counter-bid another player's ${red}, ${yellow} or ${blue} (but not ${green}) bid with workers of another color (all your workers in that bid have to be of the same color though).")
    },
    boat4b: {
        summary: _("If a tile has been already bid for, or it has been used for production, you don't have to follow the color when placing workers on the tile.")
    },
    keyflower: {
        summary: _("In spring and summer, brings 7 ${workers} and 1 ${skill}, in autumn, 3 ${workers} and 2 ${skills}."),
        scoring: _("If acquired at the end of the game, grants 1 ${point} for each 1 transport capacity.")
    },
    seabastion: {
        summary: _("In spring, summer and autumn, brings 6 ${workers} and 2 ${skills}."),
        scoring: _("If acquired at the end of the game, grants 1 ${point} for each tile which is connected by roads as part of a loop which connects back to the starting point without using the same road twice.")
    },
    seabreese: {
        summary: _("In spring brings 6 ${workers} and 1 ${skill}, in summer and autumn, 4 ${workers} and 1 ${skill}."),
        scoring: _("If acquired at the end of the game, grants 2/4/8/16/32 ${points} for 1/2/3/4/5 river tiles connected to the home tile.")
    },
    flipper: {
        summary: _("In spring and summer brings 5 ${workers} and 2 ${skills}, in autumn, 2 ${workers} and 2 ${skills}."),
        scoring: _("If acquired at the end of the game allows the player to flip one tile without paying the upgrade cost and grants 2 ${points}.")
    },
    invincible: {
        summary: _("In spring and summer brings 5 ${workers} and 1 ${skill}, in autumn, 3 ${workers} and 1 ${skill}."),
        scoring: _("If acquired at the end of the game grants 5 ${points}.")
    },
    whitewind: {
        summary: _("In spring and summer brings 4 ${workers} and 2 ${skills}, in autumn, 4 ${workers} and 1 ${skill}."),
        scoring: _("If acquired at the end of the game grants 1 ${point} for each ${worker} the player has, that has not been scored on another tile.")
    },
    keyflower_u: {
        summary: _("In spring and summer, brings 7 ${workers} and 1 ${skill}, in autumn, 3 ${workers} and 2 ${skills}."),
        scoring: _("If acquired at the end of the game, grants 1 ${point} for each 1 transport capacity.")
    },
    seabastion_u: {
        summary: _("In spring, summer and autumn, brings 6 ${workers} and 2 ${skills}."),
        scoring: _("If acquired at the end of the game, grants 1 ${point} for each tile which is connected by roads as part of a loop which connects back to the starting point without using the same road twice.")
    },
    seabreese_u: {
        summary: _("In spring brings 6 ${workers} and 1 ${skill}, in summer and autumn, 4 ${workers} and 1 ${skill}."),
        scoring: _("If acquired at the end of the game, grants 2, 4, 8, 16 or 32 ${points} for 1-5 river tiles connected to the home tile.")
    },
    flipper_u: {
        summary: _("In spring and summer brings 5 ${workers} and 2 ${skills}, in autumn, 2 ${workers} and 2 ${skills}."),
        scoring: _("If acquired at the end of the game allows the player to flip one tile without paying the upgrade cost and grants 2 ${points}.")
    },
    invincible_u: {
        summary: _("In spring and summer brings 5 ${workers} and 1 ${skill}, in autumn, 3 ${workers} and 1 ${skill}."),
        scoring: _("If acquired at the end of the game grants 5 ${points}.")
    },
    whitewind_u: {
        summary: _("In spring and summer brings 4 ${workers} and 2 ${skills}, in autumn, 4 ${workers} and 1 ${skill}."),
        scoring: _("If acquired at the end of the game grants 1 ${point} for each worker the player has, that has not been scored on another tile.")
    },
    turnorder2: {
        summary: _("Determines player order when bidding for the boats. Gives the first player token to the auction winner."),
        scoring: _("At the end of winter, can be added to the player's village, granting 1 ${point} for each neighboring tile.")
    },
    turnorder3: {
        summary: _("Determines player order when bidding for the boats. Gives the first player token to the auction winner."),
        scoring: _("At the end of winter, can be added to the player's village, granting 1 ${point} for each neighboring tile.")
    },
    turnorder36: {
        summary: _("Determines player order when bidding for the boats."),
        scoring: _("At the end of winter, can be added to the player's village, granting 1 ${point} for each neighboring tile.")
    },
    turnorder4: {
        summary: _("Determines player order when bidding for the boats. Gives the first player token to the auction winner."),
        scoring: _("At the end of winter, can be added to the player's village, granting 1 ${point} for each neighboring tile.")
    },
    turnorder46: {
        summary: _("Determines player order when bidding for the boats. Gives the first player token to the auction winner."),
        scoring: _("At the end of winter, can be added to the player's village, granting 1 ${point} for each neighboring tile.")
    },
    turnorder56a: {
        summary: _("Determines player order when bidding for the boats. Gives the first player token to the auction winner."),
        scoring: _("At the end of winter, can be added to the player's village, granting 1 ${point} for each neighboring tile.")
    },
    turnorder56b: {
        summary: _("Determines player order when bidding for the boats. Gives the first player token to the auction winner."),
        scoring: _("At the end of winter, can be added to the player's village, granting 1 ${point} for each neighboring tile.")
    }
}
