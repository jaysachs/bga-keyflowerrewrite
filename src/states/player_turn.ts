import { AnimationManager } from "../bgalibs/bga-animations";
import { MarketComponent, tileName, workerIcons } from "../components/market";
import { BidInfo, KFGameDatas, KFPlayer, MarketTile, PlayerTurnArgs, Worker, WorkerColor } from "../kfdata";
import { Html } from "../libs/html";
import { BaseState } from "./base";

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
 * Tap a tile and the status bar asks for workers, as in the mockup: one row per colour you
 * can use, where tapping the third worker takes three, and your outbid bids elsewhere as
 * extra buttons in their colour's row. Your workers show on the tile as you choose; Confirm sends it.
 */
export class PlayerTurnState extends BaseState {

    private args: PlayerTurnArgs | null = null;
    private pick: Pick | null = null;
    private readonly onTile = (mt: MarketTile) => this.startPick(mt.id);

    constructor(bga: Bga<KFPlayer, KFGameDatas>, animationManager: AnimationManager, private market: MarketComponent) {
        super(bga, animationManager);
    }

    override onEnteringState(args: PlayerTurnArgs, isCurrentPlayerActive: boolean): void {
        if (!isCurrentPlayerActive) { return; }
        this.args = args;
        this.market.addHandler(this.onTile);
        this.showChoices();
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

    /** bidinfo comes in market order. */
    private info(tileId: number): BidInfo | undefined {
        const i = this.bga.gameui.gamedatas.market.findIndex(mt => mt.id == tileId);
        return i < 0 ? undefined : this.args?.bidinfo[i];
    }

    /** Your outbid bids on other tiles, in a colour, which could move onto this one. */
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

    /** The colours you could bid on this tile with: allowed, and enough of them. */
    private colorsFor(tileId: number): WorkerColor[] {
        const info = this.info(tileId);
        if (!info) { return []; }
        const fixed = info.current_bid?.color ?? info.color;
        const colors: WorkerColor[] = fixed ? [fixed] : ['red', 'yellow', 'blue', 'green'];
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
        const name = tileName(mt.tiletype);
        const need = this.needed(p.tileId);
        const total = p.color ? this.total(p) : 0;
        const fixed = this.info(p.tileId)?.current_bid?.color ?? this.info(p.tileId)?.color ?? null;
        const already = this.info(p.tileId)?.current_bid?.num ?? 0;

        this.market.setPending(p.color && total > 0 ? { tileId: p.tileId, color: p.color, num: total, movedFrom: p.moved } : null);

        const title = already && fixed ? _('Bid on ${tile}: at least ${n} more ${color}')
            : fixed ? _('Bid on ${tile}: at least ${n} ${color}')
            : _('Bid on ${tile}: at least ${n} of one colour');
        this.bga.statusBar.setTitle(title, { tile: name, n: need, color: fixed ? colorName(fixed) : '' });
        // Every tap redraws the picker; keyboard focus returns to the button that had it.
        const focused = (document.activeElement as HTMLElement | null)?.dataset['kfKey'];
        this.bga.statusBar.removeActionButtons();

        const rows = this.colorsFor(p.tileId).map(color => {
            const stock = this.stockOf(color);
            const on = p.color == color;
            return Html.div({ classes: 'kf_pick_row', attrs: { role: 'group', 'aria-label': colorName(color) } },
                ...Array.from({ length: stock }, (_x, i) => {
                    const pressed = on && i < p.fromStock;
                    // The number says what a tap takes: the third worker takes three.
                    const b = Html.button({
                            classes: ['kf_pick_worker', 'kf_pick_one', pressed ? 'kf_on' : ''],
                            attrs: { 'aria-pressed': String(pressed), 'aria-label': `${i + 1} ${colorName(color)}`, 'data-kf-key': `${color}:${i + 1}` },
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
                    const from = tileName(this.market.tile(m.tileId)!.tiletype);
                    const b = Html.button({
                            classes: ['kf_pick_worker', 'kf_pick_group', pressed ? 'kf_on' : ''],
                            attrs: { 'aria-pressed': String(pressed), 'aria-label': `${m.num} ${colorName(color)}, ${_('outbid on')} ${from}`, 'data-kf-key': `from:${m.tileId}` },
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

        this.bga.statusBar.addActionButton(_('Cancel'), () => this.showChoices(), { color: 'secondary' });
        // Bid is never disabled: pressed too early, it says what's missing and keeps focus.
        const missing = this.missing(p, need, total);
        const bid = this.bga.statusBar.addActionButton(
            missing ? _('Bid') : `${_('Bid')} ${total} ${colorName(p.color!)}`,
            () => missing ? this.explain(msg, missing) : this.confirm());
        bid.setAttribute('aria-describedby', 'kf_pick_msg');

        if (focused) {
            document.querySelector<HTMLElement>(`#generalactions [data-kf-key="${focused}"]`)?.focus();
        }
    }

    /** What the bid still needs, or null when it can be sent. */
    private missing(p: Pick, need: number, total: number): string | null {
        if (p.color && total >= need) { return null; }
        if (p.color && total > 0) {
            return fill(_('Choose ${n} more ${color}'), { n: need - total, color: colorName(p.color) });
        }
        const color = p.color ?? this.info(p.tileId)?.current_bid?.color ?? this.info(p.tileId)?.color ?? null;
        return color
            ? fill(_('Choose at least ${n} ${color}'), { n: need, color: colorName(color) })
            : need == 1 ? _('Choose a worker')
            : fill(_('Choose at least ${n} workers of one colour'), { n: need });
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

function colorName(c: WorkerColor): string {
    return { red: _('red'), yellow: _('yellow'), blue: _('blue'), green: _('green') }[c] ?? c;
}
