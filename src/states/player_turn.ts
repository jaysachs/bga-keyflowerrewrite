import { Game } from "../keyflower";
import { View } from "../view";
import { BaseHandler } from "./base";

export class PlayerTurnHandler extends BaseHandler {
  constructor(game: Game) { super(game); }

  onEnteringState(args: any) {
    this.game.disconnectAll();
    if (this.game.bga.gameui.player_id == this.game.bga.players.getActivePlayerId()) {
      this.updatePossibleLocations(args.possibleLocations);
      this.updatePossibleBids(args.possibleBids);
      this.game.bga.statusBar.addActionButton(_("Pass"), e => this.bga.actions.performAction("actPass", {}));
    }
  }

  private updatePossibleBids(possibleBids) {
    for (const x in possibleBids) {
      $("marketLot_" + possibleBids[x].id).insertAdjacentHTML('beforeend',
        View.biddingButton(possibleBids[x].id)
      );
    }

    this.game.connectClass("bidbutton", (e) => this.onBidSelect(e));
    this.game.bga.gameui.addTooltipToClass("bidbutton", "", _("Place a bid on the tile"));
  }

  private onBidSelect(evt: Event) {
    console.log("bidbox clicked");
    this.game.stopEvent(evt);
    const target = evt.currentTarget as HTMLElement;
    const tgtid = target.id;

    if (!target.classList.contains("bidbutton")) {
      return;
    }

    const coords = tgtid.split("_");
    const id = coords[1];
    if (this.game.bga.actions.checkAction("actPlaceWorkersWithLocation")) {
      // We don't select location anymore : we directly ask how many workers the player wants to place here

      this.game.lastSelectedLocation = { type: "bid", id: id! };
      this.game.setupWorkerSelectPanel(tgtid);
    }
  }

  updatePossibleLocations(possibleLocations) {
    // Remove current possible moves
    this.game.removeClassFromAll("possibleLocation");

    for (const x in possibleLocations) {
      if ($("tile_" + possibleLocations[x].id)) {
        $("tile_" + possibleLocations[x].id).classList.add("possibleLocation");
        $("tile_" + possibleLocations[x].id).insertAdjacentHTML('beforeend',
          View.tile_select(possibleLocations[x].id)
        );
        //       this.addTooltipHtml( 'tile_select_' + possibleLocations[ x ].id, _('Place a worker here to activate the tile<hr>' + this.kf_tooltips[possibleLocations[ x ].id]), '');
      }
    }
    // dojo.query( '.possibleLocation' ).connect( 'onclick', this, 'onActionTileSelect');
    // this.addTooltipToClass( 'tile_select', '', _('Place a worker here to activate the tile') );
  }
}
