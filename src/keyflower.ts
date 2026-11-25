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
import { PanelsComponent } from "./components/panels";
import { TileRulesComponent } from "./components/tilerules";
import { TranslationComponent } from "./components/translation";
import { VillageComponent } from "./components/village";
import { KFGameDatas, KFPlayer, Season, WorkerColor } from "./kfdata";
import { BaseGame } from "./libs/basegame";
import { Html } from "./libs/html";
import { activePlayersChanged } from "./states/base";
import { PlayerTurnState } from "./states/player_turn";
import { SeasonSetupState } from "./states/season_setup";
import { IDS, View } from "./view";

const WORKER_COLORS: WorkerColor[] = ['red', 'yellow', 'blue', 'green'];
const SEASONS: Season[] = ['spring', 'summer', 'fall', 'winter'];

export class Game extends BaseGame<KFPlayer, KFGameDatas> {

    private translationComponent: TranslationComponent;
    private marketComponent: MarketComponent;
    private dockComponent: DockComponent;
    private villageComponent: VillageComponent;
    private panelsComponent: PanelsComponent;
    private tileRulesComponent: TileRulesComponent;

    constructor(bga: Bga<KFPlayer, KFGameDatas>) {
        super(bga);

        this.translationComponent = new TranslationComponent(bga);
        // In the log and the status bar: worker colors as the word, then the meeple; tiles by their
        // translated names (the server sends the tile type); seasons as their icon, then the name. These
        // replace the arguments with HTML, so notification handlers shouldn't read `color`, `tiletype` or
        // `season`; they use ids.
        this.textFormatter.registerFormatter('color', args => this.colorWithWorker(args.color));
        this.textFormatter.registerFormatter('tiletype', args => Html.span({ text: this.translationComponent.tileName(args.tiletype) }));
        this.textFormatter.registerFormatter('season', args => this.seasonWithIcon(args.season));
        this.marketComponent = new MarketComponent(bga, this.translationComponent, this.moreAnimations);
        this.dockComponent = new DockComponent(bga, this.translationComponent);
        this.villageComponent = new VillageComponent(bga, this.translationComponent, this.moreAnimations);
        this.tileRulesComponent = new TileRulesComponent(this.translationComponent);
        this.panelsComponent = new PanelsComponent(bga, this.translationComponent, this.tooltipManager, this.tileRulesComponent);
    }

    override async setup(gamedatas: KFGameDatas) {
        super.setup(gamedatas);

        this.registerStates();

        this.bga.notifications.setupPromiseNotifications({
            // logger: console.log,
            handlers: [this, ...this.bga.states.getStateClasses()],
            onEnd: () => this.logManager.processLogs()
        });

        this.translationComponent.setup();
        const mainElem = this.makeHtml(
            this.marketComponent.setup(),
            this.dockComponent.setup(),
            this.villageComponent.setup());
        this.bga.gameArea.getElement().appendChild(mainElem);
        this.panelsComponent.setup();
        this.showRulesOnTap(mainElem);
        this.showSeason(gamedatas.season);
    }

    /**
     * A line in the season's color along the status bar's bottom, as in the mockup. The season is named
     * once, in the market's heading. The season the page loaded in, like that heading.
     */
    private showSeason(season: Season): void {
        const bar = document.getElementById('page-title');
        if (bar) { bar.dataset['kfSeason'] = season; }
    }

    /**
     * Tapping a tile you can't choose right now (in the market, a village or the boats) shows its rules,
     * as the mockup's tile dialog does; a choosable one (.kf_selectable) does what it did. Enter and
     * Space do the same on village tiles (market tiles are buttons already).
     */
    private showRulesOnTap(main: HTMLElement): void {
        const tileOf = (e: Event) => {
            // The tile itself, or the market button or boat it's in (Enter on the button, a tap on cargo)
            const hit = (e.target as Element | null)?.closest<HTMLElement>('[data-kf-tile], .kf_lot_tile, .kf_boat');
            const tile = hit?.matches('[data-kf-tile]') ? hit : hit?.querySelector<HTMLElement>('[data-kf-tile]');
            return tile && main.contains(tile) && !tile.closest('.kf_selectable') && !hit!.classList.contains('kf_selectable') ? tile : null;
        };
        main.addEventListener('click', e => {
            const tile = tileOf(e);
            if (tile) { this.tileRulesComponent.show(tile.dataset['kfTile']!); }
        });
        main.addEventListener('keydown', e => {
            const tile = tileOf(e);
            if (tile && tile.getAttribute('role') == 'button' && (e.key == 'Enter' || e.key == ' ')) {
                e.preventDefault();
                this.tileRulesComponent.show(tile.dataset['kfTile']!);
            }
        });
    }

    /**
     * BGA calls this when a turn starts (before the state's onEnteringState), so the active player may
     * have changed. Changes within a state come to BaseState.onPlayerActivationChange instead.
     */
    onUpdateActionButtons(stateName: string, args: any): void {
        activePlayersChanged();
    }

    /**
     * A worker color as its word (translated) and its meeple. The word stays, for screen readers and
     * colorblind players; the meeple is hidden from screen readers. Anything else is left as it is.
     */
    private colorWithWorker(value: unknown): HTMLElement {
        if (typeof value != 'string' || !WORKER_COLORS.includes(value as WorkerColor)) {
            return Html.span({ text: String(value ?? '') });
        }
        const color = value as WorkerColor;
        return Html.span({ classes: 'kf_color' },
            this.translationComponent.color(color),
            Html.span({ classes: 'kf_worker', attrs: { 'data-kf-color': color, 'aria-hidden': 'true' } }));
    }

    /** A season as its icon (Daniel's drawings), then its name (translated); anything else as it is. */
    private seasonWithIcon(value: unknown): HTMLElement {
        if (typeof value != 'string' || !SEASONS.includes(value as Season)) {
            return Html.span({ text: String(value ?? '') });
        }
        const season = value as Season;
        return Html.span({ classes: 'kf_season' },
            Html.span({ classes: 'kf_season_icon', attrs: { 'data-kf-season': season, 'aria-hidden': 'true' } }),
            this.translationComponent.season(season));
    }

    private registerStates(): void {
        this.bga.states.register('PlayerTurn', new PlayerTurnState(this.bga, this.animationManager, this.marketComponent, this.villageComponent, this.translationComponent, this.panelsComponent, this.tileRulesComponent));
        this.bga.states.register('SeasonSetup', new SeasonSetupState(this.bga, this.animationManager));
    }

    private makeHtml(marketEl: HTMLElement, dockEl: HTMLElement, villagesEl: HTMLElement): HTMLElement {
        return Html.div({ id: 'kf_main',
            classes: this.bga.players.isCurrentPlayerSpectator() ? ['kf_is_spectator'] : []},
                marketEl, dockEl, villagesEl
        );
    }
}
