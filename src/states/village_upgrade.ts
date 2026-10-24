import { Game } from "../keyflower";
import { View } from "../view";
import { BaseHandler } from "./base";

export class VillageUpgradeHandler extends BaseHandler {
  constructor(game: Game) { super(game); }

  onEnteringState(args: any) {
    if (this.game.bga.gameui.player_id == this.game.bga.players.getActivePlayerId()) {
      this.game.bga.statusBar.addActionButton(_("skip upgrade(s)"), e => this.onSkipUpgrade(e));
    }
    this.updateUpgradeTiles(args.upgradeTiles);
  }

  private onSkipUpgrade(evt: Event) {
    if (this.game.bga.actions.checkAction("actSkipUpgrade")) {
      this.game.bga.actions.performAction("actSkipUpgrade", {});
    }
  }

  private updateUpgradeTiles(tiles) {
    console.log("updateUpgradeTiles");

    this.game.disconnectAll();
    this.game.removeClassFromAll("possibleUpgradeTile");

    for (const id in tiles) {
      const telem = $("tile_" + tiles[id])
      telem.classList.add("possibleUpgradeTile");
      telem.insertAdjacentHTML('beforeend',
        View.tile_select(tiles[id])
      );
    }

    //      this.connectClass('possibleUpgradeTile', 'onclick', 'onUpgradeTileClick');
    //      this.addTooltipToClass( 'possibleUpgradeTile', '', _('Upgrade this tile') );
  }
}
