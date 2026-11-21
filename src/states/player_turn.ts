import { AnimationManager } from "../bgalibs/bga-animations";
import { MarketComponent, workerIcons } from "../components/market";
import { PanelsComponent } from "../components/panels";
import { TranslationComponent } from "../components/translation";
import { TileRulesComponent } from "../components/tilerules";
import { VillageComponent } from "../components/village";
import { KFGameDatas, KFPlayer, MarketLot, Worker, WorkerColor, Resource, Skill, Workers, workerList } from "../kfdata";
import { Html } from "../libs/html";
import { BaseState } from "./base";

/** What the active player may bid on one market tile (PlayerTurn args, in market order). */
interface BidInfo {
    /** The market tile this is about. */
    tile_id: number;
    /** The color a bid must be, or null if any color may be used. */
    color: WorkerColor | null;
    /** The smallest total that would be the high bid. */
    min: number;
    /** Whether the player has an outbid bid here that could be moved to another tile. */
    moveable: boolean;
    current_bid: { color: WorkerColor; num: number; workers: Worker[] } | null;
    /** Whether bids on this tile must match the current color (reflects boat4a) */
    must_match: boolean;
}

interface PlayerTurnArgs {
    bidding_info: BidInfo[];
}

/** The bid being chosen in the status bar. */
interface Pick {
    tileId: number;
    color: WorkerColor | null;
    /** Workers from your stock. */
    fromStock: number;
    /** Tiles whose outbid workers move onto this bid. */
    moved: number[];
}

/**
 * Your turn: bid on a market tile, or pass. (Using a tile comes later.)
 * Tap a tile and the status bar asks for workers, as in the mockup: one row per color you
 * can use, where tapping the third worker takes three, and your outbid bids elsewhere as
 * extra buttons in their color's row. Your workers show on the tile as you choose; Bid sends it.
 */
export class PlayerTurnState extends BaseState {

    private args: PlayerTurnArgs | null = null;
    private pick: Pick | null = null;
    private readonly onTile = (mt: MarketLot) => this.startPick(mt.id);

    constructor(bga: Bga<KFPlayer, KFGameDatas>, animationManager: AnimationManager, private market: MarketComponent,
        private villages: VillageComponent, private translations: TranslationComponent, private panels: PanelsComponent, private tileRules: TileRulesComponent) {
        super(bga, animationManager);
    }

    override onEnteringState(args: PlayerTurnArgs, isCurrentPlayerActive: boolean): void {
        if (!isCurrentPlayerActive) { return; }
        this.args = args;
        this.market.addHandler(this.onTile);
        this.showChoices();
    }

    /**
     * Workers added to a bid; moved outbid workers come with them. `moved_from` lists the tiles the
     * moved groups leave: they're taken off there here, so everything happens in this one notification.
     */
    async notif_bidMade(args: { player_id: number, marketlot_id: number, bid: Worker[], moved_from: number[] }) {
        // Your bid is now on the tile: stop showing it as pending, or it's counted twice
        // until the turn moves on.
        if (args.player_id == this.bga.players.getCurrentPlayerId()) {
            this.market.setPending(null);
        }
        // The bid flies to its tile from where a moved group was drawn, or, with workers from
        // stock only, from the player's panel. Read before the moved groups leave their tiles.
        const from = args.moved_from.map(id => this.market.bidRect(id, args.player_id)).find(r => r)
            ?? this.panelOrigin(args.player_id, `.kf_worker[data-kf-color="${args.bid[0]?.color}"]`);
        args.moved_from.forEach(id => this.market.removeBid(id, args.player_id));
        this.market.addToBid(args.marketlot_id, args.player_id, args.bid);
        // Without any visible origin (no panel shown) the bid just appears.
        if (from) {
            await this.market.flyBid(args.marketlot_id, args.player_id, from);
        }
        // Your stock is only sent to you: take out the workers that are now on the tile.
        const village = this.bga.gameui.gamedatas.villages[args.player_id];
        if (village?.workers) {
            const ids = new Set(args.bid.map(w => w.id));
            village.workers = village.workers.filter(w => !ids.has(w.id));
            this.panels.updatePrivateRow();   // your workers' counts
        }
    }

    /**
     * A tile used (in the market or a village): its workers become a new row on the tile, flying from
     * where they were (an outbid bid they leave, or the player's panel). Your stock loses them and gains
     * the workers and tools produced; resources go to a tile, which needs the destination tile (to come).
     */
    async notif_tileActivated(args: {
        tile_id: number, player_id: number, workers: Workers,
        produced: { workers: Workers, resources: Resource[] | Record<string, Resource>, skills: Skill[] | Record<string, Skill>, upgrades: number },
    }) {
        const workers = workerList(args.workers);
        const produced = { workers: workerList(args.produced?.workers), skills: Object.values(args.produced?.skills ?? {}) };
        const ids = new Set(workers.map(w => w.id));
        const fromBid = this.bga.gameui.gamedatas.market
            .find(mt => mt.bids.some(b => b.player_id == args.player_id && b.workers.some(w => ids.has(w.id))));
        const from = (fromBid && this.market.bidRect(fromBid.id, args.player_id))
            ?? this.panelOrigin(args.player_id, `.kf_worker[data-kf-color="${workers[0]?.color}"]`);
        this.market.takeFromBids(args.player_id, workers);
        const inMarket = !!this.market.tile(args.tile_id);
        if (inMarket) { this.market.addUse(args.tile_id, workers); } else { this.villages.addUse(args.tile_id, workers); }
        if (from) {
            await (inMarket ? this.market.flyUse(args.tile_id, from) : this.villages.flyUse(args.tile_id, from));
        }
        // Your stock is only sent to you.
        const village = this.bga.gameui.gamedatas.villages[args.player_id];
        if (village?.workers) {
            village.workers = [...village.workers.filter(w => !ids.has(w.id)), ...produced.workers];
            village.skills = [...(village.skills ?? []), ...produced.skills];
            this.panels.updatePrivateRow();
        }
    }

    /** An outbid bid moved to another tile. For the log only: bidMade or tileActivated, which follows, moves it. */
    async notif_bidMoved(args: { player_id: number, tile_id: number }) {
    }

    /**
     * Where something a player gives comes from in their panel: for you, the item in your private
     * row (`item`, a selector: a worker of a color now; later also tools or resources, e.g. paying
     * for an activation); for other players, whose stock is private, their avatar.
     */
    private panelOrigin(playerId: number, item: string): DOMRect | undefined {
        const candidates = [
            document.querySelector(`#overall_player_board_${playerId} .kf_private ${item}`),
            document.getElementById(`avatarwrap_${playerId}`),
            document.getElementById(`overall_player_board_${playerId}`),
        ];
        return candidates.map(c => c?.getBoundingClientRect()).find(r => r && r.width > 0);
    }

    override onLeavingState(args: any, isCurrentPlayerActive: boolean): void {
        this.market.removeHandler(this.onTile);
        this.market.setSelectable([]);
        this.market.setSelected(null);
        this.market.setPending(null);
        this.args = null;
        this.pick = null;
    }

    /** The turn's first step: tiles you can bid on are highlighted, and Pass. */
    private showChoices(): void {
        this.pick = null;
        this.market.setSelected(null);
        this.market.setPending(null);
        this.market.setSelectable(this.bga.gameui.gamedatas.market.filter(mt => this.colorsFor(mt.id).length > 0).map(mt => mt.id));
        const gs = this.bga.gameui.gamedatas.gamestate as any;
        this.bga.statusBar.setTitle(gs.descriptionmyturn ?? '', gs.args);
        this.bga.statusBar.removeActionButtons();
        this.bga.statusBar.addActionButton(_('Pass'), () => this.bga.actions.performAction('actPass'), { color: 'secondary' });
    }

    // --- what you have ---

    private stock(): Worker[] {
        return this.bga.gameui.gamedatas.villages[this.bga.players.getCurrentPlayerId()]?.workers ?? [];
    }

    private stockOf(color: WorkerColor): number {
        return this.stock().filter(w => w.color == color).length;
    }

    private info(tileId: number): BidInfo | undefined {
        return this.args?.bidding_info.find(b => b.tile_id == tileId);
    }

    /** Your outbid bids on other tiles, in a color, which could move onto this one. */
    private movable(tileId: number, color: WorkerColor): { tileId: number, num: number }[] {
        const me = this.bga.players.getCurrentPlayerId();
        return this.bga.gameui.gamedatas.market
            .filter(mt => mt.id != tileId && this.info(mt.id)?.moveable)
            .map(mt => ({ tileId: mt.id, workers: mt.bids.find(b => b.player_id == me)?.workers ?? [] }))
            .filter(m => m.workers.length > 0 && m.workers[0]!.color == color)
            .map(m => ({ tileId: m.tileId, num: m.workers.length }));
    }

    /** How many more workers this tile needs from you to be the high bid. */
    private needed(tileId: number): number {
        const info = this.info(tileId);
        return info ? info.min - (info.current_bid?.num ?? 0) : 0;
    }

    /** The colors you could bid on this tile with: allowed, and enough of them. */
    private colorsFor(tileId: number): WorkerColor[] {
        const info = this.info(tileId);
        if (!info) { return []; }
        const fixedColor = info.current_bid?.color ?? info.color;
        const colors: WorkerColor[] = fixedColor ? [fixedColor] : ['red', 'yellow', 'blue', 'green'];
        return colors.filter(c =>
            this.stockOf(c) + this.movable(tileId, c).reduce((n, m) => n + m.num, 0) >= this.needed(tileId));
    }

    // --- choosing a bid ---

    private startPick(tileId: number): void {
        if (this.pick?.tileId == tileId) { return; }
        const colors = this.colorsFor(tileId);
        this.pick = { tileId, color: colors.length == 1 ? colors[0]! : null, fromStock: 0, moved: [] };
        this.market.setSelected(tileId);
        this.showPick();
    }

    private total(p: Pick): number {
        return p.fromStock + p.moved.reduce((n, id) => n + (this.movable(p.tileId, p.color!).find(m => m.tileId == id)?.num ?? 0), 0);
    }

    private setColor(p: Pick, color: WorkerColor): void {
        if (p.color != color) {
            p.color = color;
            p.fromStock = 0;
            p.moved = [];
        }
    }

    private showPick(): void {
        const p = this.pick!;
        const mt = this.market.tile(p.tileId)!;
        const name = this.translations.tileName(mt.tiletype);
        const need = this.needed(p.tileId);
        const total = p.color ? this.total(p) : 0;
        const fixedColor = this.info(p.tileId)?.current_bid?.color ?? this.info(p.tileId)?.color ?? null;
        const already = this.info(p.tileId)?.current_bid?.num ?? 0;

        this.market.setPending(p.color && total > 0 ? { tileId: p.tileId, color: p.color, num: total, movedFrom: p.moved } : null);

        const title = already && fixedColor ? _('Bid on ${tile}: at least ${n} more ${color}')
            : fixedColor ? _('Bid on ${tile}: at least ${n} ${color}')
            : _('Bid on ${tile}: at least ${n} of one color');
        this.bga.statusBar.setTitle(title, { tile: name, n: need, color: fixedColor ?? '' });   // the color formatter adds the word and its meeple
        // The tile's rules, one tap away while choosing (as in the mockup, beside the title)
        document.getElementById('pagemaintitletext')?.append(' ', this.tileRules.button(mt.tiletype));
        // Every tap redraws the picker; keyboard focus returns to the button that had it.
        const focused = (document.activeElement as HTMLElement | null)?.dataset['kfKey'];
        this.bga.statusBar.removeActionButtons();

        const rows = this.colorsFor(p.tileId).map(color => {
            const stock = this.stockOf(color);
            const on = p.color == color;
            return Html.div({ classes: 'kf_pick_row', attrs: { role: 'group', 'aria-label': this.translations.color(color) } },
                ...Array.from({ length: stock }, (_x, i) => {
                    const pressed = on && i < p.fromStock;
                    // The number says what a tap takes: the third worker takes three.
                    const b = Html.button({
                            classes: ['kf_pick_worker', 'kf_pick_one', pressed ? 'kf_on' : ''],
                            attrs: { 'aria-pressed': pressed, 'aria-label': `${i + 1} ${this.translations.color(color)}`, 'data-kf-key': `${color}:${i + 1}` },
                        },
                        workerIcons(color, 1),
                        Html.span({ classes: 'kf_pick_num', text: String(i + 1), attrs: { 'aria-hidden': 'true' } }));
                    // Tapping the nth worker takes n; tapping the last one taken again takes one fewer.
                    b.addEventListener('click', () => {
                        this.setColor(p, color);
                        p.fromStock = p.fromStock == i + 1 ? i : i + 1;
                        this.showPick();
                    });
                    return b;
                }),
                ...this.movable(p.tileId, color).map(m => {
                    const pressed = on && p.moved.includes(m.tileId);
                    const from = this.translations.tileName(this.market.tile(m.tileId)!.tiletype);
                    const b = Html.button({
                            classes: ['kf_pick_worker', 'kf_pick_group', pressed ? 'kf_on' : ''],
                            attrs: { 'aria-pressed': pressed, 'aria-label': `${m.num} ${this.translations.color(color)}, ${_('outbid on')} ${from}`, 'data-kf-key': `from:${m.tileId}` },
                        },
                        workerIcons(color, m.num),
                        Html.span({ text: `${_('outbid on')} ${from}` }));
                    b.addEventListener('click', () => {
                        this.setColor(p, color);
                        p.moved = pressed ? p.moved.filter(id => id != m.tileId) : [...p.moved, m.tileId];
                        this.showPick();
                    });
                    return b;
                }),
            );
        });
        // Says what's missing when Bid is pressed too early (a live region, so it's read out).
        const msg = Html.p({ id: 'kf_pick_msg', classes: 'kf_pick_msg', attrs: { role: 'status' } });
        const pick = Html.div({ classes: 'kf_pick' }, ...rows, msg);
        // On phones BGA zooms the status bar out (0.77 at 375px wide), which would make the
        // 44px worker buttons 34px; the picker zooms back in so they stay full size.
        const zoom = parseFloat(getComputedStyle(document.getElementById('page-title') ?? document.body).zoom) || 1;
        if (zoom < 1) { pick.style.zoom = String(1 / zoom); }
        document.getElementById('generalactions')!.appendChild(pick);

        // Bid first and Cancel after it, as in other BGA games.
        // Bid is never disabled: pressed too early, it says what's missing and keeps focus.
        const missing = this.missing(p, need, total);
        const bid = this.bga.statusBar.addActionButton(
            missing ? _('Bid') : `${_('Bid')} ${total} ${this.translations.color(p.color!)}`,
            () => missing ? this.explain(msg, missing) : this.confirm());
        bid.setAttribute('aria-describedby', 'kf_pick_msg');
        // Cancel in red and set apart on the right: BGA's guidelines reserve red for cancel, undo and exit
        // ("mandatory for UX consistency"), and grey for disabled.
        this.bga.statusBar.addActionButton(_('Cancel'), () => this.showChoices(), { color: 'alert', classes: 'kf_cancel' });

        if (focused) {
            document.querySelector<HTMLElement>(`#generalactions [data-kf-key="${focused}"]`)?.focus();
        }
    }

    /** What the bid still needs, or null when it can be sent. */
    private missing(p: Pick, need: number, total: number): string | null {
        if (p.color && total >= need) { return null; }
        if (p.color && total > 0) {
            return fill(_('Choose ${n} more ${color}'), { n: need - total, color: this.translations.color(p.color) });
        }
        const color = p.color ?? this.info(p.tileId)?.current_bid?.color ?? this.info(p.tileId)?.color ?? null;
        return color
            ? fill(_('Choose at least ${n} ${color}'), { n: need, color: this.translations.color(color) })
            : need == 1 ? _('Choose a worker')
            : fill(_('Choose at least ${n} workers of one color'), { n: need });
    }

    private explain(msg: HTMLElement, text: string): void {
        // Cleared first, so pressing again reads it out again.
        msg.textContent = '';
        requestAnimationFrame(() => { msg.textContent = text; });
    }

    private confirm(): void {
        const p = this.pick;
        if (!p || !p.color) { return; }
        this.bga.actions.performAction('actBid', {
            market_tile_id: p.tileId,
            moved_bid_ids: p.moved.join(','),
            numWorkersFromStock: p.fromStock,
            color: p.color,
        });
    }
}

/** Fills ${name} placeholders in a translated string. */
function fill(text: string, args: Record<string, string | number>): string {
    return text.replace(/\$\{(\w+)\}/g, (m, k: string) => k in args ? String(args[k]) : m);
}
