import { Game } from "../keyflower";
import { BaseHandler } from "./base";

export class VillageTransportAHandler extends BaseHandler {
  constructor(game: Game) { super(game); }

  onEnteringState(args: any) {
    this.game.updateTransportableResources(args.resources);
    this.bga.statusBar.addActionButton(_("End Transport Actions"),
      () => this.bga.actions.performAction("actEndTransport", {}));
  }
}

