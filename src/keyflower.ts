/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Keyflower implementation : © Adam Dewbery adam.dewbery@gmail.com & Radu Stefan <radu124@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 */

// replacements for format_block

import { BaseGame } from "./basegame";
import { KFGameDatas, KFPlayer } from "./kfdata";
import { AnimationList } from "./more-animations";
import { BoatSelectionHandler } from "./states/boat_selection";
import { PlayerTurnHandler } from "./states/player_turn";
import { ProductionChoiceHandler } from "./states/production_choice";
import { ProductionCostHandler } from "./states/production_cost";
import { ScoringSelectHandler } from "./states/scoring_select";
import { VillageConstructionAHandler } from "./states/village_construction_A";
import { VillageTransportAHandler } from "./states/village_transport_A";
import { VillageTransportBHandler } from "./states/village_transport_B";
import { VillageUpgradeHandler } from "./states/village_upgrade";
import { VillageUpgradeChoiceHandler } from "./states/village_upgrade_choice";
import { WinterSelectionHandler } from "./states/winter_selection";
import { IDS, View } from "./view";

// URL of BGA's site theme (shared interface images); not in bga-framework.d.ts.
declare const g_themeurl: string;

await importDojoLibs(["ebg/counter", "ebg/stock", "ebg/scrollmap"]);

type locationType = 'bid' | 'action' | 'assski' | 'assign';

export class Game extends BaseGame<KFPlayer, KFGameDatas> {

  winterTiles: Stock;
  private villages: ScrollMap[];
  private kf_connections: HTMLElement[];
  private currentPlacedTileParent: HTMLElement | null;
  private useBoat4b: boolean;
  private placeWorkerPopup?: dijit.TooltipDialog;
  private currentRotationAngle: number;
  private currentPlacedTileX: string | undefined;
  private currentPlacedTileY: string | undefined;
  private outbid_to_color: {};
  lastSelectedLocation: { type: locationType, id: string };

  constructor(bga: Bga<KFPlayer, KFGameDatas>) {
    super(bga);
    Object.assign(this, bga);
    console.log("keyflower constructor");

    // winter Tiles stock.
    this.winterTiles = new ebg.stock();
    this.winterTiles.onItemCreate = this.setupWinterTile.bind(this);
    // Here, you can init the global variables of your user interface
    // Example:
    // this.myGlobalValue = 0;

    // Use this to hold the scrollable player areas.
    this.villages = [];

    // Dojo connections.
    this.kf_connections = [];

    this.currentPlacedTileParent = null;

    this.bga.states.register('PlayerTurn', new PlayerTurnHandler(this));
    this.bga.states.register('VillageUpgrade', new VillageUpgradeHandler(this));
    this.bga.states.register('VillageUpgradeChoice', new VillageUpgradeChoiceHandler(this));
    this.bga.states.register('BoatSelection', new BoatSelectionHandler(this));
    this.bga.states.register('ScoringSelect', new ScoringSelectHandler(this));
    this.bga.states.register('VillageConstructionA', new VillageConstructionAHandler(this));
    this.bga.states.register('WinterSelection', new WinterSelectionHandler(this));
    this.bga.states.register('VillageTransportA', new VillageTransportAHandler(this));
    this.bga.states.register('VillageTransportB', new VillageTransportBHandler(this));
    this.bga.states.register('ProductionChoice', new ProductionChoiceHandler(this));
    this.bga.states.register('ProductionCost', new ProductionCostHandler(this));
  }

  /*
          setup:

          This method must set up the game user interface according to current game situation specified
          in parameters.

          The method is called each time the game interface is displayed to a player, ie:
          _ when the game starts
          _ when a player refreshes the game page (F5)

          "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
      */

  setup(gamedatas: KFGameDatas): void {
    console.log("Starting game setup");
    // Setting up player boards

    const labelOk = _("Ok");
    const labelCancel = _("Cancel");

    // Put current player first, if playing at this game.
    const playerOrder = ([] as number[]).concat(this.bga.gameui.gamedatas.playerorder);
    const ix = playerOrder.indexOf(this.bga.gameui.player_id);
    if (ix >= 0) {
      playerOrder.splice(ix, 1);
      playerOrder.unshift(this.bga.gameui.player_id);
    }
    this.bga.gameArea.getElement().insertAdjacentHTML('beforeend', `
            <div id="market_map" class="whiteblock"></div>
            <div id="playerareas">` +
              playerOrder.reduce((a, p) => a + `
              <div id="playerArea_${p}" class="playerArea whiteblock">
                <span class="player-name" style="color: #${gamedatas.players[p]!.color}">${gamedatas.players[p]!.name}</span>
                <div id="map_container_${p}" class="map_container">
                  <div id="map_scrollable_${p}" class="map_scrollable"></div>
                  <div id="map_surface_${p}" class="map_surface"></div>
                  <div id="map_scrollable_oversurface_${p}" class="map_scrollable_oversurface">
                    <div id="tile_rotator_${p}" class="tile_rotator">
                      <div class="rotate_left"></div>
                      <div class="rotate_right"></div>
                      <div class="buttons">
                        <div class="bgabutton bgabutton_red placeTile_cancel">${labelCancel}</div>
                        <div class="bgabutton bgabutton_blue placeTile_ok">${labelOk}</div>
                      </div>
                    </div>
                  </div>
                  <a id="movetop_${p}" class="movetop" "href="#"></a>
                  <a id="moveleft_${p}" class="moveleft" "href="#"></a>
                  <a id="moveright_${p}" class="moveright" "href="#"></a>
                  <a id="movedown_${p}" class="movedown" "href="#"></a>
                </div>
              </div>`,
            "") + `
            </div>
            <div id="afterall" style="position:relative"></div>`);

    for (const pid in gamedatas.players) {
      const player_id = Number(pid);

      this.setupVillage(Number(player_id));

      // Initialise players boards

      $(IDS.playerBoard(player_id)).insertAdjacentHTML('beforeend',
        `<div class="kfplayer_board" id="kfplayer_board_${player_id}"></div>`
      );
      $(IDS.playerBoard(player_id)).insertAdjacentHTML('beforeend',
        `<div class="player_area" id="${IDS.playerArea(player_id)}"></div>`
      );
      const pa = $(IDS.playerArea(player_id));
      pa.insertAdjacentHTML('beforeend',
        `<div class="starter" id="starter_${player_id}"></div>`
      );

      if (this.bga.gameui.player_id == player_id) {
        pa.insertAdjacentHTML('beforeend',
          `<div class="cp_workers" id="cp_workers"></div>
           <div class="cp_skills" id="cp_skills"></div>
           <div class="cp_winter" id="cp_winter"></div>`
        );

        //winter tiles.
        this.winterTiles.create(this.bga.gameui, $("cp_winter") as HTMLDivElement, 64, 56);
        this.winterTiles.image_items_per_row = 16;
        this.winterTiles.setSelectionMode(0);
        this.addWinterTiles();
      } else {
        pa.style.height = "34px";
      }
      pa.insertAdjacentHTML('beforeend', '<div class="clear"></div>');
    }

    // TODO: Set up your game interface here, according to "gamedatas"

    // Display starter token
    if (gamedatas.starter > 0) {
      $("starter_" + gamedatas.starter).appendChild(
        View.worker_elem({type: 5, id: "starter", num: ""})
      );
    }

    this.displayHandTokens();
    this.displayMarket();
    this.displayMap();

    // Only display pending moves to active player.
    if (this.bga.players.getActivePlayerId() == this.bga.gameui.player_id) {
      this.displayMoves(gamedatas.moves);
    }

    // Display opponents stock - not for now.
    // Display table stocks - not for now.

    document.body.onclick = this.onCloseWorkerPopup.bind(this);

    document.querySelectorAll(".rotate_left")
      .forEach((e: HTMLElement) => e.onclick = this.onRotateTile.bind(this));
    document.querySelectorAll(".rotate_right")
      .forEach((e: HTMLElement) => e.onclick = this.onRotateTile.bind(this));
    document.querySelectorAll(".placeTile_cancel")
      .forEach((e: HTMLElement) => e.onclick = this.onCancelPlaceTile.bind(this));
    document.querySelectorAll(".placeTile_ok")
      .forEach((e: HTMLElement) => e.onclick = this.onConfirmPlaceTile.bind(this));

    this.adaptVillageZones();
    document.body.onresize = this.adaptVillageZones.bind(this);

    this.setupNotifications();

    console.log("Ending game setup");
  }

  // Adapt village's zones to screen and village size
  private adaptVillageZones(): void {
    console.log("adaptVillageZones");


    // const whole_zone = dojo.position("playerareas");
    // const whole_zone_width = whole_zone.w;
    const whole_zone_width = $("playerareas").getBoundingClientRect().width
    const max_map_container_width = whole_zone_width - 20;

    const tile_height = 112;
    const tile_width = 128;
    let margin = 10;

    if (this.bga.states.getCurrentMainStateName() == "VillageConstructionA") {
      // During construction, increase margin
      margin = 50;
    }

    for (const player_id in this.bga.gameui.gamedatas.players) {
      // At first, for each zone, get the bounding rect so we an adapt the zone size

      let min_x = 0;
      let min_y = 0;
      let max_x = 0;
      let max_y = 0;

      const parseLenPx = (styleVal: string): number => {
        if (styleVal.endsWith("px")) {
          return Number(styleVal.substring(0, styleVal.length - 2));
        }
        console.log("Attempt to extract length from non-pixel value:", styleVal);
        return 0;
      }

      document.querySelectorAll("#playerArea_" + player_id + " .tile")
        .forEach((node:HTMLElement) => {
          // Do not consider "tile_select"
          if (node.id.indexOf("select") == -1 || node.id.split("_").length == 5) {
            const pos_x = parseLenPx(node.style.left);
            const pos_y = parseLenPx(node.style.top);

            min_x = Math.min(min_x, pos_x);
            max_x = Math.max(max_x, pos_x + tile_width);
            min_y = Math.min(min_y, pos_y);
            max_y = Math.max(max_y, pos_y + tile_height);
          }
        });

      //                alert( min_x+','+max_x+' => '+min_y+','+max_y );
      min_x -= margin;
      min_y -= margin;
      max_x += margin;
      max_y += margin;

      const zone_w = max_x - min_x;
      const zone_h = max_y - min_y;
      const center_x = (min_x + max_x) / 2;
      const center_y = (min_y + max_y) / 2;

      const real_zone_width = Math.min(max_map_container_width, zone_w);

      // Then, redim each zone so it can take less space on the screen.
      $("map_container_" + player_id).style.width = real_zone_width + "px"
      $("map_container_" + player_id).style.height = zone_h + "px";

      // Then, center the map to the center of the elements
      this.villages[player_id]!.scrollto(-center_x, -center_y);

      // If the width of the map fits in the screen, disable map scrolling, otherwise enable it
      if (zone_w > max_map_container_width) {
        // Enable scrolling
        this.villages[player_id]!.enableScrolling();
      } else {
        // Disable
        this.villages[player_id]!.disableScrolling();
      }
    }
  }

  ///////////////////////////////////////////////////
  //// Utility methods

  /*

          Here, you can defines some utility methods that you can use everywhere in your javascript
          script.

      */
  private getNthPlayerId(n: number): string {
    let count = 0;
    for (const array_id in this.bga.gameui.gamedatas.players) {
      if (count == n) return array_id;
      count++;
    }
    return "";
  }

  private getIndexOfPlayerId(pid: number): number {
    let count = 0;
    for (const array_id in this.bga.gameui.gamedatas.players) {
      count++;
      if (array_id == String(pid)) return count;
    }
    return -1;
  }

  private getPlayersNumber(): number {
    return Object.keys(this.bga.gameui.gamedatas.players).length;
  }
  private getCurrentPlayerNum(): number {
    return this.getIndexOfPlayerId(this.bga.gameui.player_id);
  }

  private setupVillage(pid: number): void {
    // Set up scroll map.
    this.villages[pid] = new ebg.scrollmap();

    this.villages[pid].create(
      $("map_container_" + pid),
      $("map_scrollable_" + pid),
      $("map_surface_" + pid),
      $("map_scrollable_oversurface_" + pid)
    );
    this.villages[pid].setupOnScreenArrows(150);

    // Add action connections
    //            $('movetop_' + pid). onclick = this.onMoveTop.bind(this);
    //            $('moveleft_' + pid).onclick = this.onMoveLeft.bind(this);
    //            $('moveright_' + pid).onclick = this.onMoveRight.bind(this);
    //            $('movedown_' + pid).onclick = this.onMoveDown.bind(this);

    // Add hex placeholders.
    const ver_scale = 112; // 75 * root(3) / 2
    const hor_scale = (128 * 3) / 4;
    for (let x = -7; x <= 7; x++) {
      for (let y = -7; y <= 7; y++) {
        const top = ((x - 1) * ver_scale) / 2 + y * ver_scale;
        const left = x * hor_scale - 64;
        $("map_scrollable_" + pid).insertAdjacentHTML('beforeend',
          View.villagetile({
            left: left,
            top: top,
            pid: pid,
            x: x,
            y: y,
          })
        );
      }
    }
  }

  private displayMap(): void {
    //  this.displayVillage();
    this.displayVillages();
    this.displayRiver();
  }

  setupWorkerSelectPanel(target: string) {
    console.log("setupWorkerSelectPanel");

    // Create possible types
    let possibleTypes: Record<number, string> = {
      1: $("worker_workerpanel_" + this.bga.gameui.player_id + "_1").innerHTML,
      2: $("worker_workerpanel_" + this.bga.gameui.player_id + "_2").innerHTML,
      3: $("worker_workerpanel_" + this.bga.gameui.player_id + "_3").innerHTML,
      4: $("worker_workerpanel_" + this.bga.gameui.player_id + "_4").innerHTML,
    };
    let basetype = "worker";

    if (this.lastSelectedLocation?.type == "assski") {
      // Use skills instead
      possibleTypes = {
        1: $("skill_" + this.bga.gameui.player_id + "_1").innerHTML,
        2: $("skill_" + this.bga.gameui.player_id + "_2").innerHTML,
        3: $("skill_" + this.bga.gameui.player_id + "_3").innerHTML,
      };
      basetype = "skill";
    }

    if (this.bga.players.isCurrentPlayerActive()) {
      if (typeof this.placeWorkerPopup != "undefined") {
        this.placeWorkerPopup.destroy();
        delete this.placeWorkerPopup;
      }

      const tile_name = this.bga.gameui.gamedatas.tiles[this.lastSelectedLocation.id].name;

      let html = "";
      if (this.lastSelectedLocation.type == "assign") {
        html =
          '<div id="placeworkerPopup">' +
          View.worker_panel(_("Which workers do you like to assign here for scoring?")) +
          "</div>";
      } else if (this.lastSelectedLocation.type == "assski") {
        html =
          '<div id="placeworkerPopup">' +
          View.worker_panel(_("Which skills do you like to assign here for scoring?")) +
          "</div>";
      } else if (this.lastSelectedLocation.type == "action") {
          html =
            '<div id="placeworkerPopup">' +
            View.worker_panel(
                _(tile_name) +
                " : " +
                _("How many workers would you like to use?")
            ) +
            "</div>";
      } else {
          html =
            '<div id="placeworkerPopup">' +
            View.worker_panel(_("How many workers would you like to use?")) +
            "</div>";
      }

      const anchor = target;

      this.placeWorkerPopup = new dijit.TooltipDialog({
        id: "placeworkerPopup",
        content: html,
      });

      dijit.popup.open({
        popup: this.placeWorkerPopup,
        around: $(anchor),
        closable: true,
      });

      for (const w_type in possibleTypes) {
        const elem = $("worker_line_" + w_type);
        for (let n = 0; n <= Number(possibleTypes[w_type]); n++) {
          if (this.lastSelectedLocation.type != "assski") {
            elem.appendChild(
              View.worker_elem({
                type: Number(w_type),
                id: "workerselect_" + w_type + "_" + n,
                num: n,
              })
            );
          } else {
            elem.appendChild(
              View.skill_elem({
                type: Number(w_type),
                id: "workerselect_" + w_type + "_" + n,
                num: n,
              })
            );
          }

          if (n == 0) {
            // hidden by default
            $(basetype + "_workerselect_" + w_type + "_0").style.display = "none";
          }
        }
      }
      document.querySelectorAll(".worker_line ." + basetype + "_hand")
        .forEach(e => e.classList.add("possibleType"));

      // Link workers to options.
      this.connectClass("possibleType", (e) => this.onWorkerClick(e));
      this.bga.gameui.addTooltipToClass(
        "possibleType",
        "",
        _("Place this number of workers")
      );

      //  Check for outbid workers
      if (
        this.bga.gameui.gamedatas.gamestate.args &&
        typeof this.bga.gameui.gamedatas.gamestate.args.outbid != "undefined"
      ) {
        const outbids = this.bga.gameui.gamedatas.gamestate.args.outbid;
        this.outbid_to_color = {};
        if (outbids.length > 0) {
          $("worker_panel").insertAdjacentHTML('beforeend',
            '<div id="outbid_bids"><h4>' +
            _("... plus take back my outbid workers :") +
            "</h4></div>"
          );
          let nb = 0;

          for (const i in outbids) {
            const outbid = outbids[i];

            if (this.lastSelectedLocation.type == "bid" && outbid.mid == this.lastSelectedLocation.id) {
              // We are bidding on this tile where we get this losing bid => we should not propose to use these outbid workers here
            } else {
              nb++;
              $("outbid_bids").insertAdjacentHTML('beforeend',
                '<div id="outbid_item_' +
                outbid.tid +
                '" class="outbid_item">' +
                View.worker({ type: outbid.color, id: "outbid_" + outbid.tid, num: outbid.count }) +
                _("from") +
                " " +
                _(this.bga.gameui.gamedatas.tiles[outbid.tid]!.name) +
                "</div>"
              );
              this.outbid_to_color[outbid.tid] = outbid.color;
            }
          }

          if (nb == 0) {
            $("outbid_bids").remove();
          }
        }
        document.querySelectorAll(".outbid_item")
          .forEach((e:HTMLElement) => e.onclick = this.onUpdateOutbid.bind(this));
      }

      const worker_panel = $("worker_panel");
      if (this.lastSelectedLocation.type == "assign") {
        // Take back all !
        worker_panel.insertAdjacentHTML('beforeend',
          '<span id="reassign_all" class="bgabutton bgabutton_blue">' +
          _("Take back all workers") +
          "</span>"
        );

        $("reassign_all").onclick =
          () => this.bga.actions.performAction("actScoringAssign", {
              type: "worker",
              id: this.lastSelectedLocation.id,
              to: this.lastSelectedLocation.id,
            });
      } else if (this.lastSelectedLocation.type == "assski") {
        // Take back all !
        worker_panel.insertAdjacentHTML('beforeend',
          '<span id="reassign_all" class="bgabutton bgabutton_blue">' +
          _("Take back all skills") +
          "</span>"
        );

        $("reassign_all").onclick =
           () => this.bga.actions.performAction("actScoringAssign", {
              type: "skill",
              id: this.lastSelectedLocation.id,
              to: this.lastSelectedLocation.id,
            }
          );
      }

      // Check for boat 4b (can place multiple workers from different colors)
      // Note: this works only for production and not for bidding
      this.useBoat4b = false;
      if (target.substring(0, 5) == "tile_") {
        if (document.querySelectorAll("#playerArea_" + this.bga.gameui.player_id + " .tile_36_1").length > 0) {
          worker_panel.insertAdjacentHTML('beforeend',
            '<div id="boat_4b"><span id="use_boat_4b" class="bgabutton bgabutton_blue">' +
            _("Place workers with different colors (Boat 4b)") +
            "</span>" +
            '<div id="confirm_use_boat_4b_wrap" style="display:none">' +
            "<p>" +
            _("Select the number of meeple from each color, then press :") +
            "</p>" +
            '<span  id="confirm_use_boat_4b" class="bgabutton bgabutton_blue">' +
            _("Ok") +
            "</span></div>" +
            "</div>"
          );

          $("use_boat_4b").onclick =
             (evt: Event) => {
              this.stopEvent(evt);

              this.useBoat4b = true;
              $("use_boat_4b").style.display = "none";
              $("confirm_use_boat_4b_wrap").style.display = "block";
            };

          $("confirm_use_boat_4b").onclick = (e) => this.onWorkerClick(e);
        }
      }
    }
  }

  private onUpdateOutbid(evt: Event) {
    this.stopEvent(evt);

    // outbid_item_<id>
    const outbid_id = View.targetId(evt).substring(12);

    const num_workers = $("worker_outbid_" + outbid_id).innerHTML;
    const color = this.outbid_to_color[outbid_id];
    const outbidElem = $("outbid_item_" + outbid_id);
    if (!outbidElem.classList.contains("selected")) {
      outbidElem.classList.add("selected");

      // Add num_workers to all possibilities

      document.querySelectorAll("#worker_line_" + color + " .worker_hand")
        .forEach(node => {
          node.innerHTML = String(Number(node.innerHTML) + Number(num_workers));
        });
    } else {
      outbidElem.classList.remove("selected");

      document.querySelectorAll("#worker_line_" + color + " .worker_hand")
        .forEach(node => {
          node.innerHTML = String(Number(node.innerHTML) - Number(num_workers));
        });
    }

    const elem = $("worker_workerselect_" + color + "_0")
    if (Number(elem.innerHTML) == 0) {
      elem.style.display = "none";
    } else {
      elem.style.display = "inline-block";
    }
  }

  private onCloseWorkerPopup(evt: Event): void {
    if (typeof this.placeWorkerPopup != "undefined") {
      dijit.popup.close(this.placeWorkerPopup);
    }
  }

  private displayHandTokens(): void {
    this.displayHandWorkers();
    this.displayHandSkills();
  }

  private displayHandSkills(): void {
    const cp_skills = $("cp_skills");
    if (cp_skills) {
      cp_skills.replaceChildren();
      for (let a = 1; a < 4; a++) {
        let num = 0;
        if (this.bga.gameui.gamedatas.hand.skills[a]) {
          num = this.bga.gameui.gamedatas.hand.skills[a].number;
        }
        cp_skills.appendChild(
          View.skill_elem({
            type: a,
            id: this.bga.gameui.player_id + "_" + a,
            num: num,
          })
        );
      }
    }
  }

  private displayHandWorkers(): void {
    const cp_workers = $("cp_workers");
    if (cp_workers) {
      cp_workers.replaceChildren();
      for (let a = 1; a < 5; a++) {
        let num = 0;
        if (this.bga.gameui.gamedatas.hand.workers[a]) {
          num = this.bga.gameui.gamedatas.hand.workers[a]!.number;
          if (this.bga.gameui.player_id == this.bga.players.getActivePlayerId()) {
            if (
              this.bga.gameui.gamedatas.moves != null &&
              this.bga.gameui.gamedatas.moves.type == a
            ) {
              num = num - Object.keys(this.bga.gameui.gamedatas.moves.move).length;
            }
          }
        }
        cp_workers.appendChild(
          View.worker_elem({
             type: a,
             id: "workerpanel_" + this.bga.gameui.player_id + "_" + a,
             num: num,
           })
        );
      }
    }
  }

  private addWinterTiles(): void {
    for (const tid in this.bga.gameui.gamedatas.winter) {
      const tile_id = Number(tid);
      this.winterTiles.addItemType(
        tile_id,
        tile_id,
        g_gamethemeurl + "img/tiles.png",
        tile_id + 52
      );
      this.winterTiles.addToStockWithId(tile_id, tid);
    }
  }

  // And here is our "setupNewCard":
  private setupWinterTile(card_div: HTMLElement, tile_id: string, card_id: string): void {
    // Add a special tooltip on the card:
    this.bga.gameui.addTooltipHtml(card_div.id, this.getTileTooltipHTML(tile_id, 0));
  }

  private displayMarket(): void {
    // Destroy last seasons lots.
    document.querySelectorAll(".marketLot").forEach(e => e.remove());

    // Display updated market (Turn & Season tiles)
    const market = this.bga.gameui.gamedatas.market;
    const numPlayers = Object.keys(this.bga.gameui.gamedatas.players).length;
    let count = 0;
    for (const mid in market) {
      count++;
      const m = market[mid];
      $("market_map").insertAdjacentHTML('beforeend',
        View.marketLot(m.mid),

      );
      $("marketLot_" + m.mid).insertAdjacentHTML('beforeend',
        View.tileBox(m.mid)
      );
      $("tileBox_" + m.mid).insertAdjacentHTML('beforeend',
        View.tile({
          type: 1,
          id: m.tile_id,
          side: m.side,
          orient: 1,
          scored: this.bga.gameui.gamedatas.tiles[m.tile_id]!.score,
        })
      );
      this.addTileTooltip(m.tile_id, m.side);
      this.displayActions(m.tile_id);
      this.displayResources(m.tile_id);
      $("tile_" + m.tile_id).onclick = this.onActionTileSelect.bind(this);

      if (m.pid != 0) {
        $("marketLot_" + m.mid).style.backgroundColor =
          this.hexToRgba(this.bga.gameui.gamedatas.players[m.pid]!.color);
      } else {
        for (let player_num = 1; player_num <= numPlayers; player_num++) {
          this.displayBiddingBox(player_num, m.mid);
        }
      }
    }

    this.adaptVillageZones();

    // Resize the market to fit the number of lots.
    //   const marketHeight = (1+Math.floor((count - 1) / 6))*185;
  }

  hexToRgba(hex: string): string {
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return result
      ? "rgba(" + parseInt(result[1]!, 16) + "," + parseInt(result[2]!, 16) + "," + parseInt(result[3]!, 16) + ",0.2)"
      : "default";
  }

  private displayBiddingBox(player_num: number, mid: string): void {
    // Display the player's bidding box.
    const pid = this.bga.gameui.gamedatas.players[this.getNthPlayerId(player_num - 1)].id;
    const player_color =
      this.bga.gameui.gamedatas.players[this.getNthPlayerId(player_num - 1)].color;
    const bbid = "biddingBox_" + mid + "_" + pid;
    const bglow = "biddingGlow_" + mid + "_" + pid;
    const num_players = this.getPlayersNumber();
    const bid_positions = [
      [],
      [0], // 1 player - testing
      [0, 3], // 2 players
      [0, 2, 4], // 3 players
      [0, 1, 3, 4], // 4 players
      [0, 1, 2, 3, 4], // 5 players
      [0, 1, 2, 3, 4, 5], // 6 players
    ][num_players]!;

    let bid_position: number;
    if (this.bga.players.isCurrentPlayerSpectator()) {
      bid_position = (player_num + num_players) % num_players;
    } else {
      bid_position =
        (player_num - this.getCurrentPlayerNum() + num_players) % num_players;
    }
    bid_position = bid_positions[bid_position]!;

    if (! document.getElementById(bglow)) {
      $("marketLot_" + mid).insertAdjacentHTML('beforeend',
        View.biddingGlow({
          bid: bid_position,
          mid: mid,
          pid: pid,
          color: player_color,
        })
      );
    }
    if (document.getElementById(bbid)) {
      $(bbid).replaceChildren();
    } else {
      $("marketLot_" + mid).insertAdjacentHTML('beforeend',
        View.biddingBox( {
          bid: bid_position,
          mid: mid,
          pid: pid,
        })
      );
    }
    this.displayBids(pid, mid);
  }

  private displayBids(pid: number, mid: string) {
    const bbid = "biddingBox_" + mid + "_" + pid;
    $(bbid).replaceChildren();

    let count = 0;
    let type = 0;

    for (const bid in this.bga.gameui.gamedatas.bids) {
      const b = this.bga.gameui.gamedatas.bids[bid]!;
      if (b.mid == mid && b.pid == pid) {
        count++;
        type = b.type;
      }
    }

    if (count > 0) {
      $(bbid).appendChild(
        View.worker_elem({
           id: mid + "_" + pid,
           type: type,
           num: count,
         })
      );
    }
  }

  private displayActions(tile_id: string): void {
    // Set up action (and sub) boxes.
    const abid = "actionBox_" + tile_id;
    if (!document.getElementById(abid)) {
      $("tile_" + tile_id).insertAdjacentHTML('beforeend',
        View.actionBox(tile_id)
      );
    }
    const elem = $(abid);
    elem.replaceChildren();
    elem.insertAdjacentHTML('beforeend',
      View.actionSub(tile_id, 1)
    );
    elem.insertAdjacentHTML('beforeend',
      View.actionSub(tile_id, 2)
    );
    elem.insertAdjacentHTML('beforeend',
      View.actionSub(tile_id, 3)
    );

    // Place the workers in the boxes.
    for (const aid in this.bga.gameui.gamedatas.actions) {
      const a = this.bga.gameui.gamedatas.actions[aid];
      if (a?.tile_id == tile_id) {
        $("actionSub_" + tile_id + "_" + a.action_num).appendChild(
          View.worker_mini_elem({ id: a.wid, type: a.type })
        );
      }
    }

    for (const aid in this.bga.gameui.gamedatas.assignedSkills) {
      const a = this.bga.gameui.gamedatas.assignedSkills[aid];
      if (a?.tile_id == tile_id) {
        $("actionSub_" + tile_id + "_" + a.action_num).insertAdjacentHTML('beforeend',
          View.skill_mini({ id: a.wid, type: a.type })
        );
      }
    }
  }

  private displayResources(tile_id: string) {
    // Set up resource zone.
    const rzid = "resourceZone_" + tile_id;
    if (!document.getElementById(rzid)) {
      $("tile_" + tile_id).insertAdjacentHTML('beforeend',View.resourceZone(tile_id));
    }
    const rz = $(rzid);
    rz.replaceChildren();

    // Place the resources in the zone.
    const tiles = this.bga.gameui.gamedatas.resources[tile_id];
    for (const tile in tiles) {
      const el = View.resource_mini_elem({
          id: tiles[tile].id,
          type: tiles[tile].type,
        });
      rz.appendChild(el);
      el.onclick = this.onResourceClick.bind(this);
    }

    this.updateResourceDisplay(tile_id);
  }

  private updateResourceDisplay(tile_id: string): void {
    const rzid = "resourceZone_" + tile_id;
    const ress = document.querySelectorAll("#" + rzid + " .resource_mini");
    const ress_nbr = ress.length;

    let left = "0px";
    if (ress_nbr > 5) {
      left = "-15px";
    } else if ( ress_nbr > 3) {
      left = "-8px";
    }
    ress.forEach((e:HTMLElement) => e.style.marginLeft = left)
  }

  private displayRiver(): void {
    document.querySelectorAll(".boattile").forEach(e => e.remove());
    const elem = $("market_map");
    for (const bid in this.bga.gameui.gamedatas.cargo) {
      elem.insertAdjacentHTML('afterbegin',
        View.tile({
          type: "0 boattile",
          id: bid,
          side: this.bga.gameui.gamedatas.cargo[bid]!.side,
          orient: 90,
          scored: this.bga.gameui.gamedatas.tiles[bid]!.score,
        })
      );
      this.addTileTooltip(bid, this.bga.gameui.gamedatas.cargo[bid]!.side);
      this.displayBoat(bid);
    }
  }

  private displayVillages(): void {
    const village = this.bga.gameui.gamedatas.villages;
    document.querySelectorAll(".village").forEach(e => e.replaceChildren());

    // Display Villages (inc. boats)
    for (const vid in village) {
      const villagedata = village[vid];

      for (const vd in villagedata) {
        const v = villagedata[vd];

        $("tile_" + v.tid)?.remove();

        $("map_scrollable_oversurface_" + vid).insertAdjacentHTML('beforeend',
          View.tile({
            type: v.type,
            id: v.tid,
            side: v.side,
            orient: v.orient,
            scored: this.bga.gameui.gamedatas.tiles[v.tid]!.score,
          })
        );
        this.bga.gameui.placeOnObject(
          "tile_" + v.tid,
          IDS.villageTile(Number(vid), v.x, v.y)
        );

        $("tile_" + v.tid).onclick = this.onActionTileSelect.bind(this);

        this.addTileTooltip(v.tid, v.side);
        this.displayActions(v.tid);
        this.displayResources(v.tid);

        if (v.scored > 0) {
          const elem = $("scored_" + v.tid);
          elem.style.display = "block";
          elem.innerHTML = v.scored;
        }
      }
    }
  }

  private displayBoat(bid: string): void {
    $("boatZone_" + bid)?.remove();
    const boat = this.bga.gameui.gamedatas.cargo[bid]!;
    let item_no = 0;
    const side = boat.side == 0 ? "a" : "b";

    if (Object.keys(boat.workers).length > 0 ||
        Object.keys(boat.skills).length > 0) {
      $("tile_" + bid).insertAdjacentHTML('beforeend',
        View.boatZone(bid)
      );
      for (const cwid in boat.workers) {
        const cw = boat.workers[cwid];
        $("boatZone_" + bid).appendChild(
          View.worker_mini_elem({
            id: cw.id,
            type: cw.type,
            classes: ["boatworkerpos_" + bid + side + "_" + item_no],
          })
        );
        item_no++;
      }
      for (const csid in boat.skills) {
        const cs = boat.skills[csid];
        $("boatZone_" + bid).insertAdjacentHTML('beforeend',
          View.skill_mini({
            id: cs.id,
            type: cs.type + " boatworkerpos_" + bid + side + "_" + item_no,
          })
        );
        item_no++;
      }
    }
    $("tile_" + bid).onclick = this.onActionTileSelect.bind(this);
  }

  private addTileTooltip(tid: string, side: number): void {
    const html = this.getTileTooltipHTML(tid, side);
    this.bga.gameui.addTooltipHtml("tile_" + tid, html);
  }

  private getTileTooltipHTML(tid: string, side: number): string {
    console.log(`looking for tooltip for tile '${tid}'`);
    let html = '<div class="tile_tooltip_wrap">';

    // Title
    html += "<h3>" + _(this.bga.gameui.gamedatas.tiles[tid].name) + "</h3>";
    html += "<hr>";

    // Image
    html +=
      '<div class="tooltip_tile tooltip_tile_' +
      tid +
      "_" +
      side +
      '"></div>';

    // Description
    if (side == 0) {
      html +=
        "<p>" +
        this.get_tile_description_formatted(
          _(this.bga.gameui.gamedatas.tiles[tid].description)
        ) +
        "</p>";
    } else {
      html +=
        "<p>" +
        this.get_tile_description_formatted(
          _(this.bga.gameui.gamedatas.tiles[Number(tid) + 64]!.description)
        ) +
        "</p>";
    }

    // Action
    if (typeof this.bga.gameui.gamedatas.tiles[tid].boat == "undefined") {
      // This is not a boat
      html +=
        "<p>" +
        "<img class='imgtext' src='" +
        g_themeurl +
        "img/layout/help_click.png' alt='' />" +
        _("Place a worker here to activate the tile") +
        "</p>";
    }

    html += "</div>";

    return html;
  }

  private displayMoves(moves) {
    const type = moves.type;
    const move = moves.move;
    let count = 0;

    for (const m in move) {
      count++;
      const id = "worker_mini_" + move[m].move_worker_id;
      let destination;
      if (move[m].move_type == "bid") {
        destination =
          "biddingBox_" + move[m].move_location + "_" + this.bga.gameui.player_id;
      } else {
        destination = "actionSub_" + move[m].move_location + "_3";
      }

      // Destroy previous versions (e.g. already placed workers).
      $(id)?.remove();

      // Create new worker
      $(destination).appendChild(
        View.worker_mini_elem({
          id: move[m].move_worker_id,
          type: type,
        })
      );
    }

    // update hand with correct value.
    this.displayHandWorkers();
  }

  removeClassFromAll(className: string): void {
    document.querySelectorAll("." + className).forEach((e:HTMLElement) => e.classList.remove(className));
  }

  updateVillageDestinations(destinations: {x:number, y:number}[]) {
    // This connection is handled slightly differently...
    this.removeClassFromAll("possibleDestination");
    document.querySelectorAll("#playerareas .tile_select").forEach(e => e.remove());

    for (const id in destinations) {
      const x = destinations[id]!.x;
      const y = destinations[id]!.y;
      const tid = this.bga.gameui.player_id + "_" + x + "_" + y;
      $("map_scrollable_oversurface_" + this.bga.gameui.player_id).insertAdjacentHTML('beforeend',
        View.tile_select(tid)
      );
      $('tile_select_' + tid).classList.add("possibleDestination");
      this.bga.gameui.placeOnObject(
        'tile_select_' + tid,
        IDS.villageTile(this.bga.gameui.player_id, x, y)
      );
    }

    this.connectClass("possibleDestination", this.onVillageClick.bind(this));
    this.bga.gameui.addTooltipToClass(
      "possibleDestination",
      "",
      _("Place the tile here")
    );

    this.adaptVillageZones();
  }

  updateTransportableResources(resources) {
    console.log("updateTransportResources");

    this.disconnectAll();
    this.removeClassFromAll("possibleResource");

    for (const id in resources) {
      $("resource_mini_" + id).classList.add("possibleResource");
    }

    //            this.connectClass('possibleResource', 'onclick', 'onResourceClick');
    this.bga.gameui.addTooltipToClass(
      "possibleResource",
      "",
      _("Select resource to transport")
    );
  }

  /**
   * Utility to connect an event to all elements of the same css class.
   */
  connectClass(className: string, handler: (evt: MouseEvent) => any) {
    const list = document.querySelectorAll(`.${className}`);
    list.forEach((element: HTMLElement) => {
      element.onclick = handler;
      this.kf_connections.push(element);
    });
  }

  /**
   * Utility to remove all registered events.
   */
  disconnectAll() {
    this.kf_connections.forEach((element :HTMLElement) => element.onclick = null);
    this.kf_connections = [];
  }

  ///////////////////////////////////////////////////
  //// Player's action

  /*

          Here, you are defining methods to handle player's action (ex: results of mouse click on
          game objects).

          Most of the time, these methods:
          _ check the action is possible at this game state.
          _ make a call to the game server

      */

  private onActionTileSelect(evt: Event) {
    console.log("action tile clicked");
    this.stopEvent(evt);
    const target = evt.currentTarget as HTMLElement;
    const tgtid = target.id;
    const id = tgtid.split("_")[1]!;

    // These should check current state, not what actions are permitted.

    if (this.bga.actions.checkAction("actScoringAssign", true)) {
      // Depending on the tile, we may want to place workers, resources or skills here.

      // Is there a resource selected ?

      const current = document.querySelectorAll(".selectedResource");
      if (current.length == 1) {
        const coords = current.item(0).id.split("_");
        const ressid = coords[2];

        // Assign this resource to this tile
        this.bga.actions.performAction("actScoringAssign", {
          type: "resource",
          id: ressid,
          to: id,
        });
      } else {
        // No resources selected ? Well, we may want to place workers/skill here

        if (this.bga.gameui.gamedatas.tiles[id].score == "worker") {
          this.lastSelectedLocation = { type: "assign", id: id };
          this.setupWorkerSelectPanel(tgtid);
        } else if (this.bga.gameui.gamedatas.tiles[id].score == "skill") {
          this.lastSelectedLocation = { type: "assski", id: id };
          this.setupWorkerSelectPanel(tgtid);
        }
      }
    } else if (this.bga.actions.checkAction("actPlaceWorkersWithLocation", true)) {
      if (!target.classList.contains("possibleLocation")) {
        console.error("onActionTileSelect on a non possible location");
        return;
      }
      // We don't select location anymore : we directly ask how many workers the player wants to place here
      this.lastSelectedLocation = { type: "action", id: id };
      this.setupWorkerSelectPanel(tgtid);
    } else if (this.bga.actions.checkAction("actTransportResource", true)) {
      this.bga.actions.performAction("actTransportResource", { tid: id });
    } else if (this.bga.actions.checkAction("actSelectUpgradeTile", true)) {
      if (!target.classList.contains("possibleUpgradeTile")) {
        // This is not a possible move => the click does nothing
        return;
      }

      this.bga.actions.performAction("actSelectUpgradeTile", { tid: id });
    } else if (this.bga.states.getCurrentMainStateName().toLowerCase() == 'villageconstructiona') {
               // this.bga.actions.checkAction("actSelectTile", true)) {
      if (!target.classList.contains("possibleVillageTile")) {
        return;
      }
      // Select this tile for moving
      this.cancelCurrentPlacedTile();

      $("tile_" + id).classList.add("tile_selected");
    } else if (this.bga.actions.checkAction("actSelectBoat")) {
      this.bga.actions.performAction("actSelectBoat", { tid: id });
    }
  }

  private onWorkerClick(evt: Event) {
    console.log("worker clicked");
    this.stopEvent(evt);

    let color_id = 0;
    let count = 0;

    let colors: number[] = [];
    let counts: number[] = [];

    const target = evt.currentTarget as HTMLElement;
    const tgtid = target.id;
    if (tgtid != "confirm_use_boat_4b") {
      const coords = tgtid.split("_");
      color_id = Number(coords[2]);
      count = Number(coords[3]);

      if (this.useBoat4b) {
        const currentNumber = document.querySelectorAll("#worker_line_" + color_id + " .boat4b_selected");
        if (currentNumber.length == 0) {
          // No selection on this line
          target.classList.add("boat4b_selected");
        } else {
          if (currentNumber.item(0).id == tgtid) {
            target.classList.remove("boat4b_selected");
          } else {
            $(currentNumber.item(0).id).classList.remove("boat4b_selected");
            target.classList.add("boat4b_selected");
          }
        }
        return;
      }
    }

    if (tgtid == "confirm_use_boat_4b") {
      // Make array with "color_id" and "count"
      const selections = document.querySelectorAll(".boat4b_selected");

      if (selections.length == 0) {
        this.bga.dialogs.showMessage(_("You did not select any workers"), "info");
        return;
      } else {
        selections.forEach(node => {
          const coords = node.id.split("_");
          colors.push(Number(coords[2]));
          counts.push(Number(coords[3]));
        });
      }
    }

    //const type = coords[2];
    if (
      this.bga.actions.checkAction("actPlaceWorkersWithLocation", true) ||
      this.bga.actions.checkAction("actScoringAssign")
    ) {
      // Get outbids
      const outbids = document.querySelectorAll(".outbid_item.selected");
      let outbids_ids : number[] = [];
      let moved = 0;
      outbids.forEach(outbid => {
        // outbid_item_<id>
        const outbid_id = outbid.id.substring(12);
        outbids_ids.push(Number(outbid_id));
        moved += Number($("worker_outbid_" + outbid_id).innerHTML);
      });

      console.log("onWorkerClick", this.lastSelectedLocation);

      const tileBoxElem =
      (this.lastSelectedLocation.type == "action" || this.lastSelectedLocation.type == "assign")
        ? $('tile_' + this.lastSelectedLocation.id).parentElement
        : $('tileBox_' + this.lastSelectedLocation.id);

      console.log(tileBoxElem);

      document.querySelectorAll(".pendingAction").forEach(e => e.classList.remove("pendingAction"));
      tileBoxElem.classList.add('pendingAction');

      const tileElId = tileBoxElem?.firstElementChild.id;
      // const tile_id = tileElId?.split('_')[1];
      // FIXME: this seems more corret, but are there cases where it breaks?
      // Probably should set lastSelectedLocation.id for bids as well ...
      const tile_id = this.lastSelectedLocation.type == 'bid' ? tileElId?.split('_')[1] : String(this.lastSelectedLocation.id);

      console.log(tileElId, tile_id);

      this.confirmPlay(tile_id, {
        type: this.lastSelectedLocation.type,
        id: this.lastSelectedLocation.id,
        color_id: color_id,
        count: count,
        colors: colors,
        counts: counts,
        outbids: outbids_ids,
      }, moved);
    }

    if (typeof this.placeWorkerPopup != "undefined") {
      dijit.popup.close(this.placeWorkerPopup);
    }
  }

  // `moved` is the number of outbid workers taken back from other lots and
  // added to this placement, on top of `args.count` workers from the hand.
  private async confirmPlay(tile_id: string, args: { type: locationType, id: string, color_id: number, count: number, outbids: number[], colors: number[], counts: number[]}, moved: number = 0) {
    this.bga.statusBar.removeActionButtons();
    const workersHtml = (num: number) =>
      `<span style='display: inline-block; float:none' class='worker_hand worker${args.color_id}'>${num}</span>`;
    const workersSpan = workersHtml(args.count);
    const confirmTileId = 'confirm_tile';
    // The tile's name followed by its picture, e.g. "Woodcutter [icon]".
    const tileName = _(this.bga.gameui.gamedatas.tiles[Number(tile_id)]?.name ?? "");
    const tileSpan = tileName +
      ` <span id='${confirmTileId}' style='display:inline-block; float:none; position: static; height: 32px; width: auto; aspect-ratio: 1.14; vertical-align: middle;' class='tile_background orientation1 tile_${tile_id}_0'></span>`;
    if (args.type == "bid") {
      // Show the resulting bid, not just the workers being added: a raise
      // from 1 to 3 should read "3", whether the workers come from the hand
      // or from outbid tiles.
      const existing = Number(document.getElementById(IDS.worker(args.id, this.bga.gameui.player_id))?.innerText || 0);
      const added = args.count + moved;
      const totalSpan = workersHtml(existing + added);
      const title = existing > 0
        ? _("Raise your bid on ${tileSpan} to ${workersSpan}?")
        : _("Bid ${workersSpan} for ${tileSpan}?");
      this.bga.statusBar.setTitle(title, { workersSpan: totalSpan, tileSpan: tileSpan });
    } else if (args.type == "action") {
      this.bga.statusBar.setTitle(_("Place ${workersSpan} on ${tileSpan}?"),
      { workersSpan: workersSpan, tileSpan: tileSpan });
    } else {
      // FIXME?? This is for assigning workers during scoring? maybe?
    }

    // FIXME: lookup side in market or playerArea as appropriate
    const html = this.getTileTooltipHTML(tile_id, 0 /* side */);
    this.bga.gameui.addTooltipHtml(confirmTileId, html);

    this.bga.statusBar.addActionButton(_("Confirm"), () => {
      document.querySelectorAll(".pendingAction").forEach(e => e.classList.remove("pendingAction"));
      this.bga.actions.performAction("actPlaceWorkersWithLocation", args).then(() =>
        document.querySelectorAll(".pendingAction").forEach(e => e.classList.remove("pendingAction")));
    });
    this.bga.statusBar.addActionButton(_("Cancel"), () => {
      document.querySelectorAll(".pendingAction").forEach(e => e.classList.remove("pendingAction"));
      this.bga.states.restoreServerGameState();
    }, { color: 'secondary' });
  }

  private async onVillageClick(evt: Event) {
    console.log("village clicked", this.bga.states.getCurrentMainStateName());
    this.stopEvent(evt);

    const target = evt.currentTarget as HTMLElement;
    if (!target.classList.contains("possibleDestination")) {
      // This is not a possible move => the click does nothing
      return;
    }

    const coords = target.id.split("_");
    const x = coords[3];
    const y = coords[4];

    const selectedTile = document.querySelectorAll(".tile_selected");
    if (selectedTile.length != 1) {
      this.bga.dialogs.showMessage(_("You should select one of your tile first"), "info");
      return;
    }

    this.cancelCurrentTilePlacement();

    const tile_id = selectedTile.item(0).id.substring(5); // tile_<id>

    if (this.bga.states.getCurrentMainStateName().toLowerCase() == 'villageconstructiona') {
        // this.bga.actions.checkAction("actPlaceTile")) {
      this.currentPlacedTileX = x;
      this.currentPlacedTileY = y;

      // We must move the tile to the village, and display rotation controls
      this.currentRotationAngle = 0;

      let tileElem = $("tile_" + tile_id);
      this.currentPlacedTileParent = tileElem.parentElement;
      await this.animationManager.slideAndAttach(tileElem, $(IDS.villageTile(this.bga.gameui.player_id, Number(x), Number(y))))
        .then(async () => {
          this.bga.gameui.attachToNewParent(
            "tile_" + tile_id,
            "map_scrollable_oversurface_" + this.bga.gameui.player_id
          );
          // changed parent, grab it again.
          tileElem = $("tile_" + tile_id)

          let side = 0;
          if (document.querySelectorAll("#tile_" + tile_id + " .tile_" + tile_id + "_1").length == 1) {
            side = 1;
          }

          tileElem.onclick = this.onActionTileSelect.bind(this);
          this.addTileTooltip(tile_id, side);

          const elem = $("tile_rotator_" + this.bga.gameui.player_id);
          elem.style.display = "block";
          this.bga.gameui.placeOnObjectPos(
            elem,
            "tile_" + tile_id,
            55,
            -50
          );
        })
    }
    console.log("village click end");
  }

  private onConfirmPlaceTile(evt: Event) {
    this.stopEvent(evt);

    const selectedTile = document.querySelectorAll(".tile_selected");
    const tile_id = selectedTile.item(0).id.substring(5); // tile_<id>

    let rotation = (Math.round(-this.currentRotationAngle / 60) % 6) + 1;

    while (rotation < 1) {
      rotation += 6;
    }

    if (this.bga.actions.checkAction("actConfirmPlaceTile")) {
      this.bga.actions.performAction("actConfirmPlaceTile", {
        tid: tile_id,
        x: this.currentPlacedTileX,
        y: this.currentPlacedTileY,
        orientation: rotation,
      });
    }
  }

  private onCancelPlaceTile(evt: Event) {
    this.stopEvent(evt);
    this.cancelCurrentPlacedTile();
  }

  private cancelCurrentPlacedTile() {
    this.cancelCurrentTilePlacement();
    this.removeClassFromAll("tile_selected");
  }

  private async cancelCurrentTilePlacement() {
    const selectedTile = document.querySelectorAll(".tile_selected");
    if (selectedTile.length > 0) {
      $("tile_rotator_" + this.bga.gameui.player_id).style.display = "none";

      if (this.currentPlacedTileParent !== null) {
        const tile_id = selectedTile.item(0).id.substring(5); // tile_<id>
        const elem = this.bga.gameui.attachToNewParent("tile_" + tile_id, this.currentPlacedTileParent);
        await this.animationManager.playParallel([
          () => this.moreAnimations.slideToDefaultPos(elem),
          () => this.moreAnimations.rotateTo($("tile_background_" + tile_id), 0)
        ]).then(() => {
          this.currentPlacedTileParent = null;
          this.currentRotationAngle = 0;
          const side =
            (document.querySelectorAll("#tile_" + tile_id + " .tile_" + tile_id + "_1").length == 1)
            ? 1
            : 0;
          // FIXME: is this needed?
          this.addTileTooltip(tile_id, side);
          $("tile_" + tile_id).onclick = this.onActionTileSelect.bind(this);
        })
      }
    }
  }

  private async onRotateTile(evt: Event) {
    this.stopEvent(evt);
    const selectedTile = document.querySelector(".tile_selected");
    if (!selectedTile) {
      console.warn("no selected tile?");
      return;
    }
    const tile_id = selectedTile.id.substring(5); // tile_<id>

    if ((evt.currentTarget as HTMLElement).classList.contains("rotate_left")) {
      this.currentRotationAngle -= 60;
    } else {
      this.currentRotationAngle += 60;
    }
    const el = $("tile_background_" + tile_id);
    await this.moreAnimations.rotateTo(el, this.currentRotationAngle);
    if (this.currentRotationAngle > 180) {
      this.currentRotationAngle -= 360;
      el.style.transform = `rotate(${this.currentRotationAngle}deg)`;
    }
    if (this.currentRotationAngle <= -180) {
      this.currentRotationAngle += 360;
      el.style.transform = `rotate(${this.currentRotationAngle}deg)`;
    }
  }

  private onResourceClick(evt: Event) {
    console.log("resource clicked");
    this.stopEvent(evt);

    const target = evt.currentTarget as HTMLElement;
    const coords = target.id.split("_");
    const id = coords[2];

    if (this.bga.actions.checkAction("actScoringAssign", true)) {
      const current = document.querySelectorAll(".selectedResource");
      if (current.length == 0) {
        $("resource_mini_" + id).classList.add("selectedResource");
      } else {
        this.removeClassFromAll("selectedResource");
        if (current[0] != evt.currentTarget) {
          $("resource_mini_" + id).classList.add("selectedResource");
        }
      }

      return;
    }

    if (!target.classList.contains("possibleResource")) {
      // This is not a possible move => the click does nothing
      return;
    }

    if (this.bga.actions.checkAction("actSelectResource")) {
      this.bga.actions.performAction("actSelectResource", { rid: id });
    }
  }

  private setupNotifications() {
    this.bga.notifications.setupPromiseNotifications({
      logger: console.log,
      handlers: [this, ...this.bga.states.getStateClasses()],
    });
  }

  // TODO: from this point and below, you can write your game notifications handling methods

  // selecting a boat, active players
  async notif_handUpdate(args: any) {
    this.bga.gameui.gamedatas.hand = args;
    this.displayHandTokens();
    return Promise.resolve();
  }

 async notif_marketUpdate(args: any) {
    // By exception, with this notification, we recreate the whole things because this is a fresh new start of a season.
    this.bga.gameui.gamedatas.market = args.market_data;
    this.bga.gameui.gamedatas.bids = args.bid_data;
    this.bga.gameui.gamedatas.actions = args.action_data;

    // Remove all workers on villages
    document.querySelectorAll(".actionBox .worker_mini").forEach(e => e.remove());

    this.displayMarket();
    this.displayRiver();
    return Promise.resolve();
  }

  async notif_moveMade(args: any) {
    this.bga.gameui.gamedatas.moves = args;
    this.displayMoves(args);
    return Promise.resolve();
  }

  async notif_workersPlaced(args: any) {
    //    this.bga.gameui.gamedatas.bids = args.bidData;  // Even an action might have used outbid workers.
    //  this.displayMarket(); // Any outbid workers could have come from any lot.
    //   if (args.type == 'action') {
    //          this.bga.gameui.gamedatas.actions = args.actionData;
    //          this.displayActions(args.location);
    //   }

    if (args.type == "bid") {
      const bidding_box_id =
        "biddingBox_" + args.location + "_" + args.player_id;

      const bb = $(bidding_box_id);
      if (bb) {
        if ( !$(IDS.worker(args.location, args.player_id)) ) {
          // Create it
          bb.appendChild(
            View.worker_elem({
              id: args.location + "_" + args.player_id,
              type: args.color_code,
              num: 0,
            })
          );
        }
      }

      // Slide workers from player's panel to bidding location
      const anims: AnimationList = [];
      for (const i in args.meeple_to_slide) {
        const type = args.meeple_to_slide[i];

        if (type == args.color_code) {
          let slidesource = $(IDS.playerBoard(args.player_id));

          if (typeof args.losing[i] != "undefined") {
            // This meeple is from a losing bid
            const losing = $(IDS.worker(args.losing[i], args.player_id));
            // slidesource will be deleted, use its parent.
            slidesource = losing.parentElement as HTMLElement;
            anims.push(() => this.animationManager.fadeOutAndDestroy(losing));
          }
          anims.push(() => this.animationManager
            .slideFloatingElement(View.worker_elem({type: type}), slidesource, bb)
            .then(() => {
              const workerElem = $(IDS.worker(args.location, args.player_id));
              // Increment workers number on animation end
              workerElem.innerText = String(Number(workerElem.innerHTML) + 1);
            })
          );
        }
      }
      await this.moreAnimations.playInterval(anims);
    } else if (args.type == "action") {
      const action_num = args.type_arg;
      const tile_id = args.location;

      const anims: AnimationList = [];
      for (const worker_id in args.meeple_to_slide) {
        const color = args.meeple_to_slide[worker_id];

        if (color == args.color_code) {
          const elem = View.worker_mini_elem({id: worker_id, type: color});
          $("actionSub_" + tile_id + "_" + action_num).appendChild(elem);
          elem.style.display = "none";

          let slidesource = $(IDS.playerBoard(args.player_id));

          if (typeof args.losing[worker_id] != "undefined") {
            // This meeple is from a losing bid
            const losing = $(IDS.worker(args.losing[worker_id], args.player_id));
            // slidesource will be deleted, use its parent.
            slidesource = losing.parentElement as HTMLElement;
            anims.push(() => this.animationManager.fadeOutAndDestroy(losing));
          }

          anims.push(() => this.animationManager
            .slideFloatingElement(View.worker_elem({type: color}), slidesource, $("tile_" + tile_id))
            .then(() => elem.style.display = "inline-block" )
          );
        }
      }
      await this.moreAnimations.playInterval(anims);
    }
  }

  async notif_assignSkill(args: any) {
    const action_num = args.type_arg;
    const tile_id = args.location;

    const anims : AnimationList = [];
    for (const worker_id in args.meeple_to_slide) {
      const color = args.meeple_to_slide[worker_id];

      if (color == args.color_code) {
        const elem = View.skill_mini_elem({id: worker_id, type: color});
        $("actionSub_" + tile_id + "_" + action_num).appendChild(elem);
        elem.style.display = "none";

        anims.push(() => this.animationManager
          .slideFloatingElement(View.skill_elem({type: color}), $(IDS.playerBoard(args.player_id)), $("tile_" + tile_id))
          .then(() => elem.style.display = "inline-block" )
        );
      }
    }
    await this.moreAnimations.playInterval(anims);
  }

  async notif_cargoUpdate(args: any) {
    this.bga.gameui.gamedatas.cargo = args.cargo;
    this.displayRiver();
    return Promise.resolve();
  }

  async notif_production(args: any) {
    console.log("notif: production");
    //    this.bga.gameui.gamedatas.resources = args.resources;
    //  this.displayVillages();

    const tile_id = args.destination;

    const rzid = "resourceZone_" + tile_id;
    const rz = $(rzid);
    const anims: AnimationList = [];
    // Place the resources in the zone
    const delay = 500;
    for (const id in args.to_add) {
      const type = args.to_add[id];
      const elem = View.resource_mini_elem({ id: id, type: type });
      elem.style.display = "none";
      rz.appendChild(elem);

      if (args.player_id == this.bga.gameui.player_id) {
        elem.onclick = this.onResourceClick.bind(this);
      }

      anims.push(() => this.animationManager.slideFloatingElement(
        // FIXME: can this use mini?
        View.resource_elem({type: type}),
        $("tile_" + args.source),
        $("tile_" + tile_id)
      ).then(() => {
          elem.style.display = "inline-block";
          this.updateResourceDisplay(tile_id);
        }
      ));
    }
    await this.moreAnimations.playInterval(anims);
  }

  async notif_transportResource(args: any) {
    console.log("notif: transportResource");

    // Note : almost like production

    const tile_id = args.to;
    const type = args.resource_type;
    const id = args.resource_id;

    const rzid = "resourceZone_" + tile_id;

    // Remove the old one
    $("resource_mini_" + id).remove();

    // Place the resources in the zone
    const elem = View.resource_mini_elem({ id: id, type: type });
    elem.style.display = "none";
    $(rzid).appendChild(elem);

    if (args.player_id == this.bga.gameui.player_id) {
      elem.onclick = this.onResourceClick.bind(this);
    }

    await this.animationManager
      .slideFloatingElement(View.resource_elem({type: type}), $("tile_" + args.from),$("tile_" + tile_id))
      .then(() => {
        elem.style.display = "inline-block";
        this.updateResourceDisplay(args.from);
        this.updateResourceDisplay(tile_id);
      }
    );
  }

  async notif_upgrade(args: {tile_id: string, resource_ids: string[]}) {
    console.log("notif: upgrade");
    //     this.bga.gameui.gamedatas.village = args.villages;
    //   this.bga.gameui.gamedatas.resources = args.resources;
    // this.displayPlayerScores(args.scores);
    // this.displayVillages();

    const tileElem = $("tile_" + args.tile_id);
    const anims: AnimationList = [];
    anims.push(
      () => this.moreAnimations.fadeOut(tileElem),
      () => this.moreAnimations.fadeIn(tileElem),
      () => this.moreAnimations.fadeOut(tileElem),
      () => this.moreAnimations.fadeIn(tileElem),
      () => this.moreAnimations.fadeOut(tileElem)
        .then(() => {
          // Flip this tile
          const elem = $("tile_background_" + args.tile_id);
          elem.classList.remove("tile_" + args.tile_id + "_0");
          elem.classList.add("tile_" + args.tile_id + "_1");

        }),
      () => this.moreAnimations.fadeIn(tileElem)
    );
    // Consume resources
    for (const i in args.resource_ids) {
      anims.push(() => this.animationManager.fadeOutAndDestroy($("resource_mini_" + args.resource_ids[i])));
    }
    await this.animationManager.playSequentially(anims)
      .then(() => {
        this.updateResourceDisplay(args.tile_id);
        // Change tooltip
        this.addTileTooltip(args.tile_id, 1);
      }
    );
  }

  async notif_expansion(args: any) {
    console.log("notif:expansion");
    this.bga.gameui.gamedatas.players = args;
    return Promise.resolve();
  }

  get_tile_description_formatted(description: string): string {
    return description
      .replace(
        /\$1\$/g,
        '<span class="desc_vps">' + _("1 victory point") + "</span>"
      )
      .replace(
        /\$([0-9]*)\$/g,
        '<span class="desc_vps">' + _("$1 victory points") + "</span>"
      )
      .replace(/#([^#]*)#/g, '<span class="desc_upgcost">$1</span>')
      .replace(/@([^@]*)@/g, '<span class="desc_produce">$1</span>')
      .replace(/\^([^^]*)\^/g, '<span class="desc_cost">$1</span>');
  }

  async notif_removeMarketTile(args: {lot_id: string}) {
    // Removing this tile from the market
    const elem = $("marketLot_" + args.lot_id);
    await this.animationManager.playSequentially([
      () => this.moreAnimations.fadeOut(elem),
      () => this.moreAnimations.fadeIn(elem),
      () => this.moreAnimations.fadeOut(elem),
      () => this.moreAnimations.fadeIn(elem),
      () => this.moreAnimations.fadeOut(elem)
    ]);
  }

  async notif_returnBiddingMeeple(args: { to_return: { mid: number, player_id: number, card_type: number, count: number }[]}) {
    const anims: AnimationList = [];
    // Return each losing bid to its owner: one entry per bid, with its number of workers.
    for (const bid of args.to_return) {
      const worker = $(IDS.worker(String(bid.mid), bid.player_id));
      const slidesource = worker.parentElement!;
      anims.push(() => this.animationManager.fadeOutAndDestroy(worker));
      anims.push(() => this.animationManager
        .slideFloatingElement(View.worker_elem({type: bid.card_type, num: bid.count}), slidesource, $(IDS.playerBoard(bid.player_id)))
      );
    }
    await this.animationManager.playSequentially(anims);
  }

  async notif_bidding_results(args: any) {
    console.log("bidding results", args);

    for (const mid in args) {
      const pid = args[mid];
      $("marketLot_" + mid).style.backgroundColor =
        this.hexToRgba(this.bga.gameui.gamedatas.players[pid]!.color);
    }

    // Then, remove all remaining meeples on bidding
    const anims: AnimationList = [];
    document.querySelectorAll("#market_map .worker_hand")
      .forEach((node: HTMLElement) => anims.push(
        () => this.animationManager.fadeOutAndDestroy(node/* ,500, 800 */))
      );
    await this.animationManager.playParallel(anims);
  }

  async notif_placeTile(args: {
      tile_id: string,
      rotation: number,
      player_id: number,
      x: number,
      y: number,
      newPossibleDestinations: { x: number, y: number }[] }) {
    // Placing a new tile in the village

    const tile_id = args.tile_id;
    let tileElem = $("tile_" + tile_id);
    // tileElem.style.backgroundColor = "transparent";

    this.bga.gameui.attachToNewParent(tileElem, 'afterall');
    const villageEl = $(IDS.villageTile(args.player_id, args.x, args.y));
    // parent changed, grab it again
    tileElem = $("tile_" + tile_id);
    await this.animationManager.playParallel([
        () => this.moreAnimations.slideToObject(tileElem, villageEl),
        () => this.moreAnimations.rotateTo($("tile_background_" + tile_id), -60 * (args.rotation - 1)),
      ])
      .then(async () => {
      this.bga.gameui.attachToNewParent(
        tileElem,
        "map_scrollable_oversurface_" + args.player_id
      );
      // parent changed, grab it again
      tileElem = $("tile_" + tile_id);

      const side = (document.querySelectorAll("#tile_" + tile_id + " .tile_" + tile_id + "_1").length == 1)
        ? 1
        : 0
      this.addTileTooltip(tile_id, side);
      tileElem.onclick = this.onActionTileSelect.bind(this);
      // $("tile_background_" + tile_id).style.transform = '';
      // await this.moreAnimations.slideToObject(tileElem, villageEl).then(() => this.adaptVillageZones()).then(() => this.logPos("tile at end", tileElem));
      this.adaptVillageZones();
    });
    if (args.player_id == this.bga.gameui.player_id) {
      // We have to remove the edition thing
      $("tile_rotator_" + this.bga.gameui.player_id).style.display = "none";
      this.removeClassFromAll("tile_selected");
      this.currentRotationAngle = 0;
      this.currentPlacedTileParent = null;

      this.updateVillageDestinations(args.newPossibleDestinations);
    }
  }

  async notif_updateScore(args: any) {
    this.bga.playerPanels.getScoreCounter(args.player_id).toValue(args.score);
    return Promise.resolve();
  }

  async notif_firstPlayer(args: any) {
    const starterElem = $("worker_starter");
    if (args.player_id) {
      const dest = $("starter_" + args.player_id);
      await this.animationManager.slideAndAttach(starterElem, dest).then(() =>
        dest.appendChild(starterElem)
      );
    } else {
      $("convertPurplew1")?.remove();
      $("convertPurplew2")?.remove();
      $("convertPurplew3")?.remove();
      $("convertPurplew4")?.remove();

      $("convertPurpler1")?.remove();
      $("convertPurpler2")?.remove();
      $("convertPurpler3")?.remove();
      $("convertPurpler4")?.remove();

      $("convertPurples1")?.remove();
      $("convertPurples2")?.remove();
      $("convertPurples3")?.remove();
      await this.animationManager.fadeOutAndDestroy(starterElem);
    }
  }

  async notif_tileScored(args: any) {
    const elem = $("scored_" + args.tid);
    if (args.score > 0) {
      elem.style.display = "block";
    } else {
      elem.style.display = "none";
    }
    elem.innerHTML = args.score;
    return Promise.resolve();
  }

  async notif_takeBackWorkers(args: any) {
    $("actionSub_" + args.tid + "_1").replaceChildren();
    return Promise.resolve();
  }

  stopEvent(evt: Event): void {
    evt.stopPropagation();
    evt.preventDefault();
  }
}
