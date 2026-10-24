import { Game } from "../keyflower";
import { View } from "../view";
import { BaseHandler } from "./base";

export class ProductionChoiceHandler extends BaseHandler {
  constructor(game: Game) { super(game); }

  onEnteringState(args: any) {
    for (const id in args.productionChoices) {
      const choice = args.productionChoices[id];
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
      this.bga.statusBar.addActionButton(_(currentChoice),
        e => this.onProduceChoice(e),
        { id: "produceButton_" + id, color: 'secondary' });
    }
  }

  onProduceChoice(evt: Event) {
    console.log("produce choice clicked");
    this.game.stopEvent(evt);

    const coords = View.targetId(evt).split("_");
    const id = coords[1];
    if (this.bga.actions.checkAction("actSelectGoods")) {
      this.bga.actions.performAction("actSelectGoods", { id: id });
    }
  }

}
