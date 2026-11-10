/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Keyflower implementation : © Adam Dewbery adam.dewbery@gmail.com & Radu Stefan <radu124@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 */

import { DockComponent } from "./components/dock";
import { MarketComponent } from "./components/market";
import { KFGameDatas, KFPlayer } from "./kfdata";
import { BaseGame } from "./libs/basegame";
import { Html } from "./libs/html";
import { PlayerTurnState } from "./states/player_turn";
import { SeasonSetupState } from "./states/season_setup";
import { IDS, View } from "./view";

export class Game extends BaseGame<KFPlayer, KFGameDatas> {

    private marketComponent: MarketComponent;
    private dockComponent: DockComponent;

    constructor(bga: Bga<KFPlayer, KFGameDatas>) {
        super(bga);

        this.marketComponent = new MarketComponent(bga);
        this.dockComponent = new DockComponent(bga);
    }

    override async setup(gamedatas: KFGameDatas) {
        super.setup(gamedatas);

        this.registerStates();

        this.bga.notifications.setupPromiseNotifications({
            // logger: console.log,
            handlers: [this, ...this.bga.states.getStateClasses()],
            onEnd: () => this.logManager.processLogs()
        });

        const mainElem = this.makeHtml(
            this.marketComponent.setup(),
            this.dockComponent.setup());
        this.bga.gameArea.getElement().appendChild(mainElem);
    }

    private registerStates(): void {
        this.bga.states.register('PlayerTurn', new PlayerTurnState(this.bga, this.animationManager));
        this.bga.states.register('SeasonSetup', new SeasonSetupState(this.bga, this.animationManager));
    }

    private makeHtml(marketEl, dockEl: HTMLElement): HTMLElement {
        return Html.div({ id: 'kf_main',
            classes: this.bga.players.isCurrentPlayerSpectator() ? ['kf_is_spectator'] : []},
                dockEl, marketEl
        );
    }
}
