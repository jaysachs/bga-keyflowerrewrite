import { Game } from "../keyflower";
import { View } from "../view";
import { BaseHandler } from "./base";

export class VillageConstructionAHandler extends BaseHandler {
  constructor(game: Game) { super(game); }

  onEnteringState(args: any, isCurrentPlayerActive: boolean) {
    this.onPlayerActivationChange(args, isCurrentPlayerActive);
  }

  onPlayerActivationChange(args: any, isCurrentPlayerActive: boolean) {
    // This runs on entering the state and again when the player's activation
    // changes (on page load, both): start without overlays, and leave none
    // once the player is done.
    this.removeTileSelects();
    if (!isCurrentPlayerActive) { return; }
    // Remove eventual remaining bids
    document.querySelectorAll(".biddingGlow").forEach((e:HTMLElement) => e.style.display = "none");

    this.updateVillageTileSelection(args.available);
    if (typeof args.destinations[this.bga.gameui.player_id] != "undefined") {
      // FIXME: find better home?
      this.game.updateVillageDestinations(
        args.destinations[this.bga.gameui.player_id]
      );
    }
  }

  updateVillageTileSelection(tiles) {
    this.game.removeClassFromAll("possibleVillageTile");
    this.game.disconnectAll();

    for (const tid in tiles) {
      if (tiles[tid] == this.bga.gameui.player_id) {
        const telem = $("tile_" + tid);
        telem.classList.add("possibleVillageTile");
        telem.insertAdjacentHTML('beforeend',
          View.tile_select(tid)
        );
      }
    }

    //this.connectClass('possibleVillageTile', 'onclick', 'onTileSelect');
    //this.addTooltipToClass( 'possibleVillageTile', '', _('Select tile to place in your village') );
  }

}
