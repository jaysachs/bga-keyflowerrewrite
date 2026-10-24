import { Game } from "../keyflower";
import { View } from "../view";
import { BaseHandler } from "./base";

export class VillageUpgradeChoiceHandler extends BaseHandler {
  constructor(game: Game) { super(game); }

  onEnteringState(args: any) {
    if (this.game.bga.gameui.player_id != this.game.bga.players.getActivePlayerId()) {
      return;
    }
    for (const id in args.upgradeChoice) {
      const choice = args.upgradeChoice[id];
      let currentChoice = "";
      for (const cid in choice) {
        const item = choice[cid];
        currentChoice =
          currentChoice +
          '<div class="' +
          item.table +
          " " +
          item.table +
          item.type +
          '"></div>';
      }
      this.game.bga.statusBar.addActionButton(
        _(currentChoice),
        e => this.onUpgradeCostChoice(e),
        {id: "upgradeCostButton_" + id, color: 'secondary' });
    }
  }

  onUpgradeCostChoice(evt: Event): void {
    console.log("upgrade cost choice clicked");
    this.game.stopEvent(evt);

    const coords = View.targetId(evt).split("_");
    const id = coords[1];
    if (this.game.bga.actions.checkAction("actSelectPaymentMethod")) {
      this.game.bga.actions.performAction("actSelectPaymentMethod", { id: id });
    }
  }
}
