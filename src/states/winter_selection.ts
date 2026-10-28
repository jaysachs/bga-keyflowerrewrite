import { Game } from "../keyflower";
import { BaseHandler } from "./base";

export class WinterSelectionHandler extends BaseHandler {
  constructor(game: Game) { super(game); }

  onEnteringState(args: any, isCurrentPlayerActive: boolean) {
    this.onPlayerActivationChange(args, isCurrentPlayerActive);
  }

  onPlayerActivationChange(args: any, isCurrentPlayerActive: boolean) {
    if (!isCurrentPlayerActive) { return; }

    this.game.winterTiles.setSelectionMode(2);
    // This runs on entering the state and again when the player's activation
    // changes (on page load, both): start from an empty status bar so the
    // button isn't doubled.
    this.bga.statusBar.removeActionButtons();
    this.bga.statusBar.addActionButton(_("Confirm Winter Selection"), e => this.onConfirmWinter(e));
  }

  async onConfirmWinter(evt: Event) {
    console.log("winter confirm pressed");
    this.game.stopEvent(evt);

    // need to check at least one winter tile is selected, and get the tile id(s).
    const ids = this.game.winterTiles.getSelectedItems();

    if (this.bga.actions.checkAction("actSelectWinterTiles") && ids.length >= 1) {
      // need to send array of the selected tiles.
      let wids = "";
      for (const id in ids) {
        wids += ids[id].id + ",";
      }
      wids = wids.substring(0, wids.length - 1);
      this.bga.actions.performAction("actSelectWinterTiles", { wids: wids }).then(() => this.game.winterTiles.removeAll())
    }
  }

}
