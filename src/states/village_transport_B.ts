import { Game } from "../keyflower";
import { View } from "../view";
import { BaseHandler } from "./base";

export class VillageTransportBHandler extends BaseHandler {
  constructor(game: Game) { super(game); }

  onEnteringState(args: any) {
    this.game.updateTransportableResources(args.resources);
    for (const i in args.selection) {
      $("resource_mini_" + args.selection[i].id).classList.add("selectedResource");
    }
    this.updateResourceDestinations(args.resourceDestinations);
    this.bga.statusBar.addActionButton(_("change resource"),
      () => this.bga.actions.performAction("actChangeResource", {}));
  }

  private updateResourceDestinations(destinations) {
    console.log("updateResourceDestinations");

    this.game.disconnectAll();
    this.game.removeClassFromAll("possibleResourceDestination");

    for (const id in destinations) {
      $("tile_" + destinations[id].id).classList.add("possibleResourceDestination");
      $("tile_" + destinations[id].id).insertAdjacentHTML('beforeend',
        View.tile_select(destinations[id].id)
      );
    }
  }


  onLeavingState(): void {
    super.onLeavingState();
    this.game.removeClassFromAll("selectedResource");
  }
}
