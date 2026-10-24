import { Game } from "../keyflower";
import { AnimationList } from "../more-animations";
import { IDS } from "../view";
import { BaseHandler } from "./base";

export class BoatSelectionHandler extends BaseHandler {
  constructor(game: Game) { super(game); }

  onEnteringState(args: any) {
    this.updatePossibleBoats(args);
  }

  updatePossibleBoats(args) {
    this.game.removeClassFromAll("possibleBoat");
    this.game.disconnectAll();
    const possibleBoats = args.possibleBoats;

    for (const boat in possibleBoats) {
      $("tile_" + boat).classList.add("possibleBoat");
    }

    //       this.connectClass('possibleBoat', 'onclick', 'onBoatClick');
    //            this.addTooltipToClass( 'possibleBoat', '', _('Select boat to claim its supplies') );
  }

  async notif_selectedBoat(args: any) {
    console.log("selectedBoat", args);
    const type = args.type;

    if (args.winter == 0) {
      $(type + "_" + args.boat_id).remove();
    } else {
      // Attribute winter tile
      $(type + "_" + args.boat_id).style.backgroundColor =
          this.game.hexToRgba(this.bga.gameui.gamedatas.players[args.player_id]!.color);
    }

    //            if (type == 'tile') {
    ///                this.bga.gameui.gamedatas.market = args.market_data;
    //                this.displayMarket();
    //            }
    return Promise.resolve();
  }

    // from selecting a boat, for all players.
  async notif_handUpdateVisible(args: {
    player_id: number;
    origin: number;
    workers_to_add: {
      card_id: number;
      card_type: number;
    }[];
    skills_to_add: {
      card_id: number;
      card_type: number;
    }[];
  }) {
    const anims: AnimationList = [];
    // Workers
    for (const worker of args.workers_to_add) {
      anims.push(() => this.game.animationManager.slideOutAndDestroy(
          $('worker_mini_' + worker.card_id),
          $(IDS.playerBoard(args.player_id)),
          {}
        )
      )
    }

    // Skills
    for (const skill of args.skills_to_add) {
      anims.push(() => this.game.animationManager.slideOutAndDestroy(
          $('skill_mini_' + skill.card_id),
          $(IDS.playerBoard(args.player_id)),
          {}
        )
      )
    }
    await this.game.moreAnimations.playInterval(anims);
  }

}
