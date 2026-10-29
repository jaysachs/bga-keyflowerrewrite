import { Game } from "../keyflower";
import { KFGameDatas, KFPlayer } from "../kfdata";

export abstract class BaseHandler {
  protected bga: Bga<KFPlayer, KFGameDatas>
  constructor(protected game: Game) { this.bga = game.bga; }

  onLeavingState(): void {
    console.log("Leaving state: " + this.constructor.name);

    document.querySelectorAll(".bidbutton").forEach(e => e.remove());
    this.removeTileSelects();
    document.querySelectorAll("#worker_panel").forEach(e => e.remove());
  }

  protected removeTileSelects(): void {
    document.querySelectorAll(".tile_select").forEach(e => e.remove());
  }
}
