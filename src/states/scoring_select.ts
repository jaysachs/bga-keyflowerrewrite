import { Game } from "../keyflower";
import { View } from "../view";
import { BaseHandler } from "./base";

export class ScoringSelectHandler extends BaseHandler {
  constructor(game: Game) { super(game); }

  onEnteringState(args: any, isCurrentPlayerActive: boolean) {
    this.onPlayerActivationChange(args, isCurrentPlayerActive);
  }

  onPlayerActivationChange(args: any, isCurrentPlayerActive: boolean) {
    if (!isCurrentPlayerActive) { return; }
    this.updateScoringSelectTiles();
    $("market_map").style.display = "none";

    if (document.querySelectorAll("#starter_" + this.bga.gameui.player_id + " #worker_starter").length > 0) {
      this.game.bga.statusBar.addActionButton(
        this.game.bga.gameui.format_string(_("Convert my ${purple} into ..."), {
          purple:
            '<div class="worker_mini worker_mini5 imgtext" style="display:inline-block;float:none;"></div>',
        }),
        e => this.onConvertPurple(e),
        {id: "convertPurple", color: 'secondary'}
      );
    }

    this.bga.statusBar.addActionButton(_("Confirm my scoring arrangement"), e => this.onFinishArrange(e));
  }


  updateScoringSelectTiles() {
    console.log("updateScoringSelectTiles");

    document.querySelectorAll("#playerArea_" + this.bga.gameui.player_id + " .scored_skill").forEach(
      node => {
        // tile_<id>
        const id = node.id.substring(5);
        const telem = $("tile_" + id);
        telem.classList.add("possibleScoringDestination");
        telem.insertAdjacentHTML('beforeend',
          View.tile_select(id)
        );
      }
    );
    document.querySelectorAll("#playerArea_" + this.bga.gameui.player_id + " .scored_resource").forEach(
      node => {
        // tile_<id>
        const id = node.id.substring(5);
        const telem = $("tile_" + id);
        telem.classList.add("possibleScoringDestination");
        telem.insertAdjacentHTML('beforeend',
          View.tile_select(id)
        );
      }
    );
    document.querySelectorAll("#playerArea_" + this.bga.gameui.player_id + " .scored_worker").forEach(
      node => {
        // tile_<id>
        const id = node.id.substring(5);
        const telem = $("tile_" + id);
        telem.classList.add("possibleScoringDestination");
        telem.insertAdjacentHTML('beforeend',
          View.tile_select(id)
        );
      }
    );
  }

  onConvertPurple(evt: Event) {
    if ($("convertPurple")) {
      $("convertPurple").remove();
      this.bga.dialogs.showMessage(
        _("You can get an extra worker / resource / skill of your choice"),
        "info"
      );
    }

    const tgtid = View.targetId(evt);

    if (tgtid == "convertPurple") {
      this.bga.statusBar.addActionButton(
        // FIXME this probably doesn't work ...
        '<div class="worker_hand worker1"></div>',
        e => this.onConvertPurple(e),
        { id: "convertPurplew1", color: 'secondary' });
      this.bga.statusBar.addActionButton(
        '<div class="worker_hand worker2"></div>',
        e => this.onConvertPurple(e),
        { id: "convertPurplew2", color: 'secondary' }
      );
      this.bga.statusBar.addActionButton(
        '<div class="worker_hand worker3"></div>',
        e => this.onConvertPurple(e),
        { id: "convertPurplew3", color: 'secondary' }
      );
      this.bga.statusBar.addActionButton(
        '<div class="worker_hand worker4"></div>',
        e => this.onConvertPurple(e),
        {id: "convertPurplew4", color: 'secondary'}
      );

      this.bga.statusBar.addActionButton(
        '<div class="resource resource1"></div>',
        e => this.onConvertPurple(e),
        {id: "convertPurpler1", color: 'secondary' });
      this.bga.statusBar.addActionButton(
        '<div class="resource resource2"></div>',
        e => this.onConvertPurple(e),
        {id: "convertPurpler2", color: 'secondary' });
      this.bga.statusBar.addActionButton(
        '<div class="resource resource3"></div>',
        e => this.onConvertPurple(e),
        {id: "convertPurpler3", color: 'secondary' });
      this.bga.statusBar.addActionButton(
        '<div class="resource resource4"></div>',
        e => this.onConvertPurple(e),
        {id: "convertPurpler4", color: 'secondary' });

      this.bga.statusBar.addActionButton(
        '<div class="skill skill1"></div>',
        e => this.onConvertPurple(e),
        {id: "convertPurples1", color: 'secondary' });
      this.bga.statusBar.addActionButton(
        '<div class="skill skill2"></div>',
        e => this.onConvertPurple(e),
        {id: "convertPurples2", color: 'secondary' });
      this.bga.statusBar.addActionButton(
        '<div class="skill skill3"></div>',
        e => this.onConvertPurple(e),
        {id: "convertPurples3", color: 'secondary' });
    } else {
      // Get an extra !
      const extra = tgtid.substring(13);
      this.bga.actions.performAction("actConvertPurple", { to: extra });
    }
  }

  onFinishArrange(evt: Event) {
    // Check that this action is possible at this moment
    if (this.bga.actions.checkAction("actFinishArranging")) {
      const areyousure = _(
        "This will END your game. Your score will be ${score} points. Have you assigned all of your resources / workers / skills tokens to their optimal positions?"
      );
      this.bga.dialogs.confirmation(
        this.bga.gameui.format_string(areyousure, {
          score: $("player_score_" + this.bga.gameui.player_id).innerHTML,
        })).then ( (r) => {
          if (r) {
            this.bga.actions.performAction("actFinishArranging", {})
          }});
    }
  }

}
