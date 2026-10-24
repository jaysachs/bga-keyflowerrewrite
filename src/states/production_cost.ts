import { Game } from "../keyflower";
import { BaseHandler } from "./base";

export class ProductionCostHandler extends BaseHandler {
  constructor(game: Game) { super(game); }

  onEnteringState(args: any) {
    for (const id in args.productionCosts) {
      const choice = args.productionCosts[id];
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
      this.bga.statusBar.addActionButton(_(currentChoice), e => this.onProduceCostChoice(e, id),
      { id: "produceCostButton_" + id, color: 'secondary' });
    }
  }

  onProduceCostChoice(evt: Event, id: string) {
    console.log("produce cost choice clicked");
    this.game.stopEvent(evt);

    // const coords = targetId(evt).split("_");
    // const id = coords[1];
    if (this.bga.actions.checkAction("actDiscardGoods")) {
      this.bga.actions.performAction("actDiscardGoods", { id: id });
    }
  }
}
