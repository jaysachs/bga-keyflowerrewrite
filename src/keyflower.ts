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
import { VillageComponent } from "./components/village";
import { KFGameDatas, KFPlayer, Worker, WorkerColor } from "./kfdata";
import { BaseGame } from "./libs/basegame";
import { Html } from "./libs/html";
import { PlayerTurnState } from "./states/player_turn";
import { SeasonSetupState } from "./states/season_setup";
import { IDS, View } from "./view";

export class Game extends BaseGame<KFPlayer, KFGameDatas> {

    private marketComponent: MarketComponent;
    private dockComponent: DockComponent;
    private villageComponent: VillageComponent;

    constructor(bga: Bga<KFPlayer, KFGameDatas>) {
        super(bga);

        this.marketComponent = new MarketComponent(bga);
        this.dockComponent = new DockComponent(bga);
        this.villageComponent = new VillageComponent(bga);
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
            this.dockComponent.setup(),
            this.villageComponent.setup());
        this.bga.gameArea.getElement().appendChild(mainElem);
    }

    private registerStates(): void {
        this.bga.states.register('PlayerTurn', new PlayerTurnState(this.bga, this.animationManager, this.marketComponent));
        this.bga.states.register('SeasonSetup', new SeasonSetupState(this.bga, this.animationManager));
    }

    /** Workers added to a bid; moved outbid workers come with them. */
    async notif_bidMade(args: { player_id: number, markettileid: number, bid: { id: number, color: WorkerColor }[] }) {
        this.marketComponent.addToBid(args.markettileid, args.player_id, args.bid as Worker[]);
        // Your stock is only sent to you: take out the workers that are now on the tile.
        const village = this.bga.gameui.gamedatas.villages[args.player_id];
        if (village?.workers) {
            const ids = new Set(args.bid.map(w => w.id));
            village.workers = village.workers.filter(w => !ids.has(w.id));
        }
    }

    /** An outbid bid moved off a tile. The server sends the tile's id as `tiletype`. */
    async notif_bidMoved(args: { player_id: number, tiletype: number | string }) {
        this.marketComponent.removeBid(Number(args.tiletype), args.player_id);
    }

    private makeHtml(marketEl: HTMLElement, dockEl: HTMLElement, villagesEl: HTMLElement): HTMLElement {
        return Html.div({ id: 'kf_main',
            classes: this.bga.players.isCurrentPlayerSpectator() ? ['kf_is_spectator'] : []},
                dockEl, marketEl, villagesEl
        );
    }
}
