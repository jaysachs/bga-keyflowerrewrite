* Tile placement rotate -- arrows interfere with "Ok" if "cancel" is long
* Confirmation: include "in your village" / "in ${other_player} village" in confirmation prompt
* Confirmation: thread through / look up current "side" of a tile for use in tooltip (and icon)
* Confirmation: include *total* workers being placed not additional (maybe even include detail, e.g. "3 moved, 1 added" ?)
* Bug in season cleaning, returning workers. For worker on a tile in a player's village (at least the "home" tile), the opponent hits a JS error on animating its return. It fails on this notify:
  * ⇦ returnBiddingMeeple {"to_return":[{"card_id":"70","card_type":"2","player_id":"2405406","mid":"4"},{"card_id":"112","card_type":"2","player_id":"2405406","mid":"4"}]}
  * not sure why it's a near duplicate, when there was only one meeple on the tile
  * "mid" is actually the tile_id as best I can tell
  *
  ```
  12:06:11.093 Notif returnBiddingMeeple
Object { to_return: (2) […] }

to_return: Array [ {…}, {…} ]


0: Object { card_id: "70", card_type: "2", player_id: "2405406", … }



card_id: "70"



card_type: "2"



mid: "4"



player_id: "2405406"



<prototype>: Object { … }



<global>: Window https://studio.boardgamearena.com/1/keyflowerrewrite?table=923808&testuser=2405407


1: Object { card_id: "112", card_type: "2", player_id: "2405406", … }


length: 2


<prototype>: Array []


<global>: Window https://studio.boardgamearena.com/1/keyflowerrewrite?table=923808&testuser=2405407

<prototype>: Object { … }

<global>: Window https://studio.boardgamearena.com/1/keyflowerrewrite?table=923808&testuser=2405407
ly_studio.js:1:489831
12:06:11.093 worker is
<div id="removed-element-1784649971607-0.5382112964162917" class="worker_hand worker2">
Game.js:2192:21
12:06:11.093 slidesource is
<div id="biddingBox_4_2405406" class="biddingBox biddingBox3">
Game.js:2194:21
12:06:11.093 worker is
<div id="removed-element-1784649971607-0.5382112964162917" class="worker_hand worker2">
Game.js:2192:21
12:06:11.093 slidesource is
<div id="biddingBox_4_2405406" class="biddingBox biddingBox3">
```
 * JS error: ```
 12:06:12.111 Uncaught (in promise) TypeError: can't access property "appendChild", toElement is null
    attachToElement https://x.boardgamearena.net/data/game-libs/bga-animations/1.x/dist/bga-animations.esm.js:418
    addFixedSpace https://x.boardgamearena.net/data/game-libs/bga-animations/1.x/dist/bga-animations.esm.js:204
    addAnimatedSpace https://x.boardgamearena.net/data/game-libs/bga-animations/1.x/dist/bga-animations.esm.js:212
    addAnimatedSpaceIfNecessary https://x.boardgamearena.net/data/game-libs/bga-animations/1.x/dist/bga-animations.esm.js:254
    slideOutAndDestroy https://x.boardgamearena.net/data/game-libs/bga-animations/1.x/dist/bga-animations.esm.js:862
    __awaiter https://x.boardgamearena.net/data/game-libs/bga-animations/1.x/dist/bga-animations.esm.js:54
    __awaiter https://x.boardgamearena.net/data/game-libs/bga-animations/1.x/dist/bga-animations.esm.js:50
    slideOutAndDestroy https://x.boardgamearena.net/data/game-libs/bga-animations/1.x/dist/bga-animations.esm.js:852
    fadeOutAndDestroy https://x.boardgamearena.net/data/game-libs/bga-animations/1.x/dist/bga-animations.esm.js:885
    __awaiter https://x.boardgamearena.net/data/game-libs/bga-animations/1.x/dist/bga-animations.esm.js:54
    __awaiter https://x.boardgamearena.net/data/game-libs/bga-animations/1.x/dist/bga-animations.esm.js:50
    fadeOutAndDestroy https://x.boardgamearena.net/data/game-libs/bga-animations/1.x/dist/bga-animations.esm.js:877
    notif_returnBiddingMeeple https://static.studio.boardgamearena.com/data/themereleases/current/games/keyflowerrewrite/999999-9999//modules/js/Game.js:2195
    playSequentially https://x.boardgamearena.net/data/game-libs/bga-animations/1.x/dist/bga-animations.esm.js:1036
    ```
