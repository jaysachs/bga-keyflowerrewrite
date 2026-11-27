import { AnimationManager } from "../bgalibs/bga-animations";
import { MarketComponent, workerIcons } from "../components/market";
import { PanelsComponent } from "../components/panels";
import { TranslationComponent } from "../components/translation";
import { TileRulesComponent } from "../components/tilerules";
import { VillageComponent } from "../components/village";
import { KFGameDatas, KFPlayer, MarketLot, ResourceType, Resource, Skill, SkillType, Village, VillageTile, Worker, WorkerColor } from "../kfdata";
import { Child, Html } from "../libs/html";
import { BaseState } from "./base";
import { playingPlayerId } from "../libs/players";

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

/** What a use costs: a worker or tool of a kind, any worker or tool, or nothing. */
type ActivationCost = 'none' | 'workerred' | 'workerblue' | 'workeryellow' | 'workerany' | 'skillpickaxe' | 'skillsaw' | 'skillanvil' | 'skillany';

/** A tile the active player may use, in the market or any village (PlayerTurn args). */
interface ActivationInfo {
    tile_id: number;
    /** The tile's color so far (its bids or first use), or null. */
    color: WorkerColor | null;
    /** Whether a use must be in `color` (false with boat 4b). */
    must_match: boolean;
    /** The smallest and largest number of workers for the next use. */
    min: number;
    max: number;
    cost: ActivationCost;
    /** What it produces; the picker needs it for `resource1` (a basic resource of your choice). Not sent yet: see RESOURCE_CHOICE. */
    reward?: string;
}

interface PlayerTurnArgs {
    bidding_info: BidInfo[];
    activation_info?: ActivationInfo[];
}

/** Tiles whose use produces a basic resource of your choice, until activation_info sends the reward. */
const RESOURCE_CHOICE = new Set(['workshop']);
const BASIC_RESOURCES: ResourceType[] = ['iron', 'stone', 'wood'];
const COLORS: WorkerColor[] = ['red', 'yellow', 'blue', 'green'];
const SKILLS: SkillType[] = ['anvil', 'pickaxe', 'saw'];

/** The bid or use being chosen in the status bar. */
interface Pick {
    tileId: number;
    /** Bid on a market tile, or use a tile (in the market or a village). */
    mode: 'bid' | 'use';
    /** Whether the tile can be both bid on and used (a market tile), so the picker offers the switch. */
    both: boolean;
    color: WorkerColor | null;
    /** Workers from your stock. */
    fromStock: number;
    /** Tiles whose outbid workers move onto this bid or use. */
    moved: number[];
    /** For a use that costs any worker or any tool: what you pay with. */
    payWith: WorkerColor | SkillType | null;
    /** For a use that produces a resource of your choice. */
    resource: ResourceType | null;
    /** A use that may mix colors (boat 4b: the server sends must_match false), counted per color in mix. */
    mixing: boolean;
    mix: Partial<Record<WorkerColor, number>>;
}

/**
 * Your turn: bid on a market tile, use a tile (in the market or any village), or pass.
 * Tap a tile and the status bar asks for workers, as in the mockup: one row per color you
 * can use, where tapping the third worker takes three, and your outbid bids elsewhere as
 * extra buttons in their color's row. A market tile you could both bid on and use offers a
 * switch; a use also asks what to pay with (when it costs any worker or tool) and which
 * resource to take (Workshop). Your bid shows on the tile as you choose; Bid or Use sends it.
 */
export class PlayerTurnState extends BaseState {

    private args: PlayerTurnArgs | null = null;
    private pick: Pick | null = null;
    private readonly onTile = (mt: MarketLot) => this.startPick(mt.id);
    private readonly onVillageTile = (t: VillageTile) => this.startPick(t.tile_id);

    constructor(bga: Bga<KFPlayer, KFGameDatas>, animationManager: AnimationManager, private market: MarketComponent,
        private villages: VillageComponent, private translations: TranslationComponent, private panels: PanelsComponent, private tileRules: TileRulesComponent) {
        super(bga, animationManager);
        // Escape cancels the picker, as it closes dialogs; not while typing (BGA's chat), and not when a
        // dialog has already used it (it calls preventDefault)
        document.addEventListener('keydown', e => {
            const typing = (e.target as Element | null)?.closest('input, textarea, [contenteditable="true"]');
            if (e.key == 'Escape' && this.pick && !typing && !e.defaultPrevented) { e.preventDefault(); this.cancelPick(); }
        });
    }

    override onEnteringState(args: PlayerTurnArgs, isCurrentPlayerActive: boolean): void {
        if (!isCurrentPlayerActive) { return; }
        this.args = args;
        this.market.addHandler(this.onTile);
        this.villages.addHandler(this.onVillageTile);
        this.showChoices();
    }

    /**
     * Workers added to a bid; moved outbid workers come with them. `moved_from` lists the tiles the
     * moved groups leave: they're taken off there here, so everything happens in this one notification.
     */
    async notif_bidMade(args: { player_id: number, marketlot_id: number, bid: Worker[], moved_from: number[] }) {
        // Your bid is now on the tile: stop showing it as pending, or it's counted twice
        // until the turn moves on.
        if (args.player_id == playingPlayerId(this.bga)) {
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
     * where they were (an outbid bid they leave, or the player's panel). `moved_from` lists the tiles the
     * moved bids leave, as in bidMade. Your stock loses the workers and gains the workers and tools
     * produced; resources go onto the destination tile (the tile itself, or Home for a market tile), flying
     * from the tile that was used.
     */
    async notif_tileActivated(args: {
        tile_id: number, player_id: number, workers: Worker[], moved_from: number[],
        produced: { workers: Worker[], resources: Resource[], skills: Skill[], upgrades: number, dest_tile_id: number | null },
    }) {
        const workers = args.workers;
        const produced = { workers: args.produced?.workers ?? [], skills: args.produced?.skills ?? [] };
        const ids = new Set(workers.map(w => w.id));
        // Read before the moved bids leave their tiles.
        const from = args.moved_from.map(id => this.market.bidRect(id, args.player_id)).find(r => r)
            ?? this.panelOrigin(args.player_id, `.kf_worker[data-kf-color="${workers[0]?.color}"]`);
        args.moved_from.forEach(id => this.market.removeBid(id, args.player_id));
        const inMarket = !!this.market.tile(args.tile_id);
        if (inMarket) { this.market.addUse(args.tile_id, workers); } else { this.villages.addUse(args.tile_id, workers); }
        if (from) {
            await (inMarket ? this.market.flyUse(args.tile_id, from) : this.villages.flyUse(args.tile_id, from));
        }
        const dest = args.produced?.dest_tile_id, resources = args.produced?.resources ?? [];
        if (dest != null && resources.length) {
            const used = dest == args.tile_id ? undefined : document.getElementById(`kf_tile_${args.tile_id}`)?.getBoundingClientRect();
            await this.villages.addResources(dest, resources, used);
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
        this.villages.removeHandler(this.onVillageTile);
        this.market.setSelectable([]);
        this.market.setSelected(null);
        this.market.setPending(null);
        this.villages.setSelectable([]);
        this.villages.setSelected(null);
        document.getElementById('page-title')?.classList.remove('kf_picking');
        this.args = null;
        this.pick = null;
    }

    /** The turn's first step: tiles you can bid on or use are highlighted, and Pass. */
    private showChoices(): void {
        this.pick = null;
        document.getElementById('page-title')?.classList.remove('kf_picking');
        this.market.setSelected(null);
        this.market.setPending(null);
        this.villages.setSelected(null);
        const market = this.bga.gameui.gamedatas.market.map(mt => mt.id);
        this.market.setSelectable(market.filter(id => this.colorsFor(id).length > 0 || this.usable(id)));
        this.villages.setSelectable((this.args?.activation_info ?? []).map(a => a.tile_id).filter(id => !market.includes(id) && this.usable(id)));
        const gs = this.bga.gameui.gamedatas.gamestate as any;
        this.bga.statusBar.setTitle(gs.descriptionmyturn ?? '', gs.args);
        this.bga.statusBar.removeActionButtons();
        this.bga.statusBar.addActionButton(_('Pass'), () => this.bga.actions.performAction('actPass'), { color: 'secondary' });
    }

    // --- what you have ---

    /** Your village's data (with your stock and tools, sent only to you), when you play. */
    private myVillage(): Village | undefined {
        const me = playingPlayerId(this.bga);
        return me == null ? undefined : this.bga.gameui.gamedatas.villages[me];
    }

    private stock(): Worker[] {
        return this.myVillage()?.workers ?? [];
    }

    private stockOf(color: WorkerColor): number {
        return this.stock().filter(w => w.color == color).length;
    }

    private skills(): Skill[] {
        return this.myVillage()?.skills ?? [];
    }

    private info(tileId: number): BidInfo | undefined {
        return this.args?.bidding_info.find(b => b.tile_id == tileId);
    }

    private useInfo(tileId: number): ActivationInfo | undefined {
        return this.args?.activation_info?.find(a => a.tile_id == tileId);
    }

    /**
     * Your outbid bids, in a color, which could move onto this tile: on other tiles to bid, and also
     * the one on this tile itself to use it (the rules allow it; the designer confirmed, see #63).
     */
    private movable(tileId: number, color: WorkerColor, action: 'bid' | 'use'): { tileId: number, num: number }[] {
        const me = playingPlayerId(this.bga);
        return this.bga.gameui.gamedatas.market
            .filter(mt => (action == 'use' || mt.id != tileId) && this.info(mt.id)?.moveable)
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
        const colors: WorkerColor[] = fixedColor ? [fixedColor] : COLORS;
        return colors.filter(c =>
            this.stockOf(c) + this.movable(tileId, c, 'bid').reduce((n, m) => n + m.num, 0) >= this.needed(tileId));
    }

    // --- using a tile ---

    /** The worker color a use's cost asks for (a fixed one, or the one chosen for "any worker"). */
    private costWorker(info: ActivationInfo, p?: Pick | null): WorkerColor | null {
        const fixed = { workerred: 'red', workerblue: 'blue', workeryellow: 'yellow' }[info.cost as string] as WorkerColor | undefined;
        if (fixed) { return fixed; }
        return info.cost == 'workerany' && p?.payWith && (COLORS as string[]).includes(p.payWith) ? p.payWith as WorkerColor : null;
    }

    /** The tool a use's cost asks for (a fixed one, or the one chosen for "any tool"). */
    private costSkill(info: ActivationInfo, p?: Pick | null): SkillType | null {
        const fixed = { skillpickaxe: 'pickaxe', skillsaw: 'saw', skillanvil: 'anvil' }[info.cost as string] as SkillType | undefined;
        if (fixed) { return fixed; }
        return info.cost == 'skillany' && p?.payWith && (SKILLS as string[]).includes(p.payWith) ? p.payWith as SkillType : null;
    }

    /** Whether you have what the use costs. */
    private canPay(info: ActivationInfo): boolean {
        switch (info.cost) {
            case 'none': return true;
            case 'workerany': return this.stock().length > 0;
            case 'skillany': return this.skills().length > 0;
            default: {
                const w = this.costWorker(info), k = this.costSkill(info);
                return w ? this.stockOf(w) > 0 : k ? this.skills().some(s => s.type == k) : true;
            }
        }
    }

    /** Workers of a color you can place on a use: your stock, less one if it pays the cost. */
    private usableStock(info: ActivationInfo, color: WorkerColor, p?: Pick | null): number {
        return this.stockOf(color) - (this.costWorker(info, p) == color ? 1 : 0);
    }

    /** The colors you could use this tile with: allowed, and enough of them. */
    private useColorsFor(tileId: number): WorkerColor[] {
        const info = this.useInfo(tileId);
        if (!info || !this.canPay(info)) { return []; }
        const has = (c: WorkerColor) => this.usableStock(info, c) + this.movable(tileId, c, 'use').reduce((n, m) => n + m.num, 0);
        // Mixing (boat 4b): every color you have counts towards the minimum together
        if (!info.must_match) { return COLORS.filter(c => has(c) > 0); }
        const colors = info.color ? [info.color] : COLORS;
        return colors.filter(c => has(c) >= info.min);
    }

    private usable(tileId: number): boolean {
        const info = this.useInfo(tileId), colors = this.useColorsFor(tileId);
        if (info && !info.must_match) {
            return colors.reduce((n, c) => n + this.usableStock(info, c) + this.movable(tileId, c, 'use').reduce((k, m) => k + m.num, 0), 0) >= info.min;
        }
        return colors.length > 0;
    }

    private needsResource(tileId: number): boolean {
        const info = this.useInfo(tileId);
        const type = this.market.tile(tileId)?.tiletype ?? this.villages.tile(tileId)?.tiletype;
        return info?.reward == 'resource1' || (!info?.reward && !!type && RESOURCE_CHOICE.has(type));
    }

    // --- choosing a bid or use ---

    private startPick(tileId: number): void {
        if (this.pick?.tileId == tileId) { return; }
        const inMarket = !!this.market.tile(tileId);
        const canBid = inMarket && this.colorsFor(tileId).length > 0, canUse = this.usable(tileId);
        if (!canBid && !canUse) { return; }
        this.pick = { tileId, mode: canBid ? 'bid' : 'use', both: canBid && canUse, color: null, fromStock: 0, moved: [], payWith: null, resource: null, mixing: false, mix: {} };
        this.setMode(this.pick, this.pick.mode);
        this.market.setSelected(inMarket ? tileId : null);
        this.villages.setSelected(inMarket ? null : tileId);
        this.showPick();
        // Selecting redraws the tile, which loses keyboard focus: it goes to the picker's first choice
        // instead, so a screen reader reads where you are (the switch, or the first worker)
        document.querySelector<HTMLElement>('#generalactions [role="radio"][aria-checked="true"], #generalactions .kf_pick_worker')?.focus();
    }

    /** Back from the picker to the tile it was opened from (Cancel removes the button that had focus). */
    private cancelPick(): void {
        const tileId = this.pick?.tileId;
        this.showChoices();
        if (tileId != null) {
            document.querySelector<HTMLElement>(`#kf_lot_${tileId} .kf_lot_tile, #kf_tile_${tileId}`)?.focus();
        }
    }

    /** Switches between bidding and using; the choice starts over, with the only color preselected. */
    private setMode(p: Pick, mode: 'bid' | 'use'): void {
        const colors = mode == 'bid' ? this.colorsFor(p.tileId) : this.useColorsFor(p.tileId);
        const mixing = mode == 'use' && this.useInfo(p.tileId)?.must_match === false;
        Object.assign(p, { mode, mixing, color: !mixing && colors.length == 1 ? colors[0]! : null, fromStock: 0, moved: [], payWith: null, resource: null, mix: {} });
    }

    private total(p: Pick): number {
        return Object.values(this.colorCounts(p)).reduce((n, k) => n + k, 0);
    }

    /** The workers a pick places, per color: from your stock, and the outbid groups it moves. */
    private colorCounts(p: Pick): Partial<Record<WorkerColor, number>> {
        const counts: Partial<Record<WorkerColor, number>> = p.mixing ? { ...p.mix } : p.color ? { [p.color]: p.fromStock } : {};
        COLORS.forEach(c => this.movable(p.tileId, c, p.mode).filter(m => p.moved.includes(m.tileId)).forEach(m => { counts[c] = (counts[c] ?? 0) + m.num; }));
        return counts;
    }

    /** "1 red, 1 yellow": the colors a pick places, for the button. */
    private countsText(p: Pick): string {
        return COLORS.filter(c => (this.colorCounts(p)[c] ?? 0) > 0).map(c => `${this.colorCounts(p)[c]} ${this.translations.color(c)}`).join(', ');
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
        const use = p.mode == 'use';
        const info = use ? this.useInfo(p.tileId)! : null;
        const type = this.market.tile(p.tileId)?.tiletype ?? this.villages.tile(p.tileId)!.tiletype;
        const name = this.translations.tileName(type);
        const need = use ? info!.min : this.needed(p.tileId);
        const room = use ? info!.max : Infinity;
        const total = this.total(p);
        const fixedColor = use ? (info!.must_match ? info!.color : null)
            : this.info(p.tileId)?.current_bid?.color ?? this.info(p.tileId)?.color ?? null;
        const already = use ? 0 : this.info(p.tileId)?.current_bid?.num ?? 0;

        this.market.setPending(!use && p.color && total > 0 ? { tileId: p.tileId, color: p.color, num: total, movedFrom: p.moved } : null);

        // As in the mockup: the title says what this is, the rule line under it what it takes.
        this.bga.statusBar.setTitle(use ? _('Use ${tile}') : _('Bid on ${tile}'), { tile: name });
        document.getElementById('page-title')?.classList.add('kf_picking');
        // The tile's rules, one tap away while choosing (as in the mockup, beside the title)
        document.getElementById('pagemaintitletext')?.append(' ', this.tileRules.button(type));
        // Every tap redraws the picker; keyboard focus returns to the button that had it.
        const focused = (document.activeElement as HTMLElement | null)?.dataset['kfKey'];
        this.bga.statusBar.removeActionButtons();
        // The rule line: what the move takes, with the color's meeple. Pressing Bid or Use too early shows
        // what's missing here instead, in red (a live region, so it's read out).
        const ruleText = p.mixing ? (need == 1 ? _('At least 1 worker, any colors. Your boat 4b lets you mix colors here')
                : fill(_('At least ${n} workers, any colors. Your boat 4b lets you mix colors here'), { n: need }))
            : !use && already && fixedColor ? fill(_('At least ${n} more ${color}'), { n: need, color: this.translations.color(fixedColor) })
            : fixedColor ? fill(_('At least ${n} ${color}'), { n: need, color: this.translations.color(fixedColor) })
            : fill(_('At least ${n} of one color'), { n: need });
        const msg = Html.p({ id: 'kf_pick_msg', classes: 'kf_pick_rule', attrs: { role: 'status' } },
            ruleText, fixedColor ? Html.span({ classes: 'kf_worker', attrs: { 'data-kf-color': fixedColor, 'aria-hidden': 'true' } }) : undefined);

        const modes = p.both ? Html.div({ classes: 'kf_pick_modes', attrs: { role: 'radiogroup', 'aria-label': fill(_('What to do with ${tile}'), { tile: name }) } },
            ...(['bid', 'use'] as const).map(m => {
                // A radio group: one Tab stop (the chosen half), the arrow keys move between the two
                const b = Html.button({ text: m == 'bid' ? _('Bid on it') : _('Use it'), attrs: { role: 'radio', 'aria-checked': p.mode == m, tabindex: p.mode == m ? 0 : -1, 'data-kf-key': `mode:${m}` } });
                const choose = (to: 'bid' | 'use') => {
                    if (p.mode != to) { this.setMode(p, to); this.showPick(); }
                    document.querySelector<HTMLElement>(`#generalactions [data-kf-key="mode:${to}"]`)?.focus();
                };
                b.addEventListener('click', () => choose(m));
                b.addEventListener('keydown', e => {
                    if (['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(e.key)) { e.preventDefault(); choose(m == 'bid' ? 'use' : 'bid'); }
                });
                return b;
            })) : undefined;

        const colors = use ? this.useColorsFor(p.tileId) : this.colorsFor(p.tileId);
        const rows = colors.map(color => {
            const stock = use ? this.usableStock(info!, color, p) : this.stockOf(color);
            const on = p.mixing || p.color == color;
            const fromStock = p.mixing ? p.mix[color] ?? 0 : on ? p.fromStock : 0;
            return Html.div({ classes: 'kf_pick_row', attrs: { role: 'group', 'aria-label': this.translations.color(color) } },
                ...Array.from({ length: Math.max(0, stock) }, (_x, i) => {
                    const pressed = i < fromStock;
                    // The number says what a tap takes: the third worker takes three.
                    const b = Html.button({
                            classes: ['kf_pick_worker', 'kf_pick_one', pressed ? 'kf_on' : ''],
                            attrs: { 'aria-pressed': pressed, 'aria-label': `${i + 1} ${this.translations.color(color)}`, 'data-kf-key': `${color}:${i + 1}` },
                        },
                        workerIcons(color, 1),
                        Html.span({ classes: 'kf_pick_num', text: String(i + 1), attrs: { 'aria-hidden': 'true' } }));
                    // Tapping the nth worker takes n; tapping the last one taken again takes one fewer.
                    b.addEventListener('click', () => {
                        const next = fromStock == i + 1 ? i : i + 1;
                        const others = on ? this.total(p) - fromStock : 0;
                        if (next + others > room) { this.explain(msg, this.roomText(room)); return; }
                        if (p.mixing) { p.mix[color] = next; } else { this.setColor(p, color); p.fromStock = next; }
                        this.showPick();
                    });
                    return b;
                }),
                ...this.movable(p.tileId, color, p.mode).map(m => {
                    const pressed = on && p.moved.includes(m.tileId);
                    // Your outbid bid on the tile you're using says so, rather than naming it again
                    const from = m.tileId == p.tileId ? null : this.translations.tileName(this.market.tile(m.tileId)!.tiletype);
                    const where = from ? `${_('outbid on')} ${from}` : _('outbid here');
                    const b = Html.button({
                            classes: ['kf_pick_worker', 'kf_pick_group', pressed ? 'kf_on' : ''],
                            attrs: { 'aria-pressed': pressed, 'aria-label': `${m.num} ${this.translations.color(color)}, ${where}`, 'data-kf-key': `from:${m.tileId}` },
                        },
                        workerIcons(color, m.num),
                        Html.span({ text: where }));
                    b.addEventListener('click', () => {
                        if (!pressed && (on ? this.total(p) : 0) + m.num > room) { this.explain(msg, this.roomText(room)); return; }
                        if (!p.mixing) { this.setColor(p, color); }
                        p.moved = pressed ? p.moved.filter(id => id != m.tileId) : [...p.moved, m.tileId];
                        this.showPick();
                    });
                    return b;
                }),
            );
        });
        const choices = use ? this.choices(p, info!) : [];
        // As in the mockup: the rule line and the switch under the title, the workers beside them, the
        // buttons at the right (stacked on phones; the CSS lays it out).
        // The workers, and each choice row, are one Tab stop each, moved inside with the arrow keys
        const rowsEl = Html.div({ classes: 'kf_pick_rows', attrs: { role: 'group', 'aria-labelledby': 'kf_pick_label' } }, ...rows);
        [rowsEl, ...choices].forEach(group => this.rove(group, focused));
        const workers = Html.div({ classes: 'kf_pick_workers' },
            Html.div({ id: 'kf_pick_label', classes: 'kf_pick_label', text: _('Tap the workers to use') }),
            ...choices,
            rowsEl);
        const actions = Html.div({ classes: 'kf_pick_actions' });
        const pick = Html.div({ classes: 'kf_pick' }, msg, modes, workers, actions);
        document.getElementById('generalactions')!.appendChild(pick);

        // Bid or Use first and Cancel after it, as in other BGA games.
        // It's never disabled: pressed too early, it says what's missing and keeps focus.
        const missing = this.missing(p, need, total);
        const verb = use ? _('Use') : _('Bid');
        const go = this.bga.statusBar.addActionButton(
            missing ? verb : p.mixing ? `${verb} ${this.countsText(p)}` : `${verb} ${total} ${this.translations.color(p.color!)}`,
            () => missing ? this.explain(msg, missing) : this.confirm(), { destination: actions });
        go.setAttribute('aria-describedby', 'kf_pick_msg');
        // Cancel in red and set apart on the right: BGA's guidelines reserve red for cancel, undo and exit
        // ("mandatory for UX consistency"), and grey for disabled.
        this.bga.statusBar.addActionButton(_('Cancel'), () => this.cancelPick(), { color: 'alert', classes: 'kf_cancel', destination: actions });

        if (focused) {
            document.querySelector<HTMLElement>(`#generalactions [data-kf-key="${focused}"]`)?.focus();
        }
    }

    /**
     * One Tab stop for a group of buttons (the one last used, else the first chosen, else the first);
     * the arrow keys move inside it: left and right along it, up and down to the same place in the row
     * before or after (one row per color), Home and End to its ends.
     */
    private rove(group: HTMLElement, focusedKey?: string): void {
        const items = Array.from(group.querySelectorAll<HTMLButtonElement>('button'));
        if (!items.length) { return; }
        const start = items.find(b => b.dataset['kfKey'] == focusedKey) ?? items.find(b => b.getAttribute('aria-pressed') == 'true') ?? items[0]!;
        items.forEach(b => { b.tabIndex = b == start ? 0 : -1; });
        const rows = Array.from(group.querySelectorAll<HTMLElement>('.kf_pick_row'));
        group.addEventListener('keydown', e => {
            const at = e.target as HTMLButtonElement, i = items.indexOf(at);
            if (i < 0) { return; }
            let next: HTMLButtonElement | undefined;
            if (e.key == 'ArrowRight') { next = items[i + 1]; }
            else if (e.key == 'ArrowLeft') { next = items[i - 1]; }
            else if (e.key == 'Home') { next = items[0]; }
            else if (e.key == 'End') { next = items[items.length - 1]; }
            else if (e.key == 'ArrowDown' || e.key == 'ArrowUp') {
                const r = rows.findIndex(row => row.contains(at));
                if (r < 0) { next = items[i + (e.key == 'ArrowDown' ? 1 : -1)]; }
                else {
                    const to = rows[r + (e.key == 'ArrowDown' ? 1 : -1)];
                    const col = Array.from(rows[r]!.querySelectorAll('button')).indexOf(at);
                    const there = to ? Array.from(to.querySelectorAll<HTMLButtonElement>('button')) : [];
                    next = there[Math.min(col, there.length - 1)];
                }
            }
            else { return; }
            e.preventDefault();
            if (next) { items.forEach(b => { b.tabIndex = -1; }); next.tabIndex = 0; next.focus(); }
        });
    }

    private roomText(room: number): string {
        return fill(_('Room for ${n} more: 6 workers at most on a tile'), { n: room });
    }

    /** A use's extra choices: what to pay with (when it costs any worker or tool), and which resource to take. */
    private choices(p: Pick, info: ActivationInfo): HTMLElement[] {
        const out: HTMLElement[] = [];
        const row = (label: string, options: { key: string, value: string, content: Child[], label: string }[], chosen: string | null, set: (v: string) => void) => {
            out.push(Html.div({ classes: 'kf_pick_choice', attrs: { role: 'group', 'aria-label': label } },
                Html.span({ classes: 'kf_pick_label', text: label, attrs: { 'aria-hidden': 'true' } }),
                ...options.map(o => {
                    const b = Html.button({ classes: ['kf_pick_worker', chosen == o.value ? 'kf_on' : ''], attrs: { 'aria-pressed': chosen == o.value, 'aria-label': o.label, 'data-kf-key': o.key } }, ...o.content);
                    b.addEventListener('click', () => { set(o.value); this.showPick(); });
                    return b;
                })));
        };
        if (info.cost == 'workerany') {
            row(_('Pay with'), COLORS.filter(c => this.stockOf(c) > 0).map(c => ({ key: `pay:${c}`, value: c, content: [workerIcons(c, 1)], label: fill(_('Pay with 1 ${color}'), { color: this.translations.color(c) }) })),
                p.payWith, v => { p.payWith = v as WorkerColor; if (p.color == v && p.fromStock > this.usableStock(info, v as WorkerColor, p)) { p.fromStock = this.usableStock(info, v as WorkerColor, p); } });
        }
        if (info.cost == 'skillany') {
            row(_('Pay with'), SKILLS.filter(k => this.skills().some(s => s.type == k)).map(k => ({ key: `pay:${k}`, value: k, content: [Html.span({ classes: 'kf_icon_skill', attrs: { 'data-kf-kind': k } }), this.translations.skill(k)], label: fill(_('Pay with 1 ${tool}'), { tool: this.translations.skill(k) }) })),
                p.payWith, v => { p.payWith = v as SkillType; });
        }
        if (this.needsResource(p.tileId)) {
            row(_('Take'), BASIC_RESOURCES.map(r => ({ key: `res:${r}`, value: r, content: [Html.span({ classes: 'kf_icon_resource', attrs: { 'data-kf-kind': r, 'aria-hidden': 'true' } }), this.translations.resource(r)], label: fill(_('Take 1 ${resource}'), { resource: this.translations.resource(r) }) })),
                p.resource, v => { p.resource = v as ResourceType; });
        }
        return out;
    }

    /** What the bid or use still needs, or null when it can be sent. */
    private missing(p: Pick, need: number, total: number): string | null {
        if (p.mode == 'use') {
            const info = this.useInfo(p.tileId)!;
            if ((info.cost == 'workerany' || info.cost == 'skillany') && !p.payWith) { return _('Choose what to pay with'); }
            if (this.needsResource(p.tileId) && !p.resource) { return _('Choose a resource to take'); }
        }
        if (p.mixing) {
            return total >= need ? null : total > 0 ? fill(_('Choose ${n} more'), { n: need - total })
                : need == 1 ? _('Choose a worker') : fill(_('Choose at least ${n} workers'), { n: need });
        }
        if (p.color && total >= need) { return null; }
        if (p.color && total > 0) {
            return fill(_('Choose ${n} more ${color}'), { n: need - total, color: this.translations.color(p.color) });
        }
        const color = p.color ?? (p.mode == 'use' ? (this.useInfo(p.tileId)?.must_match ? this.useInfo(p.tileId)?.color : null)
            : this.info(p.tileId)?.current_bid?.color ?? this.info(p.tileId)?.color) ?? null;
        return color
            ? fill(_('Choose at least ${n} ${color}'), { n: need, color: this.translations.color(color) })
            : need == 1 ? _('Choose a worker')
            : fill(_('Choose at least ${n} workers of one color'), { n: need });
    }

    private explain(msg: HTMLElement, text: string): void {
        // Cleared first, so pressing again reads it out again; the rule line turns red until the next tap.
        msg.textContent = '';
        msg.classList.add('kf_pick_error');
        setTimeout(() => { msg.textContent = text; }, 30);   // not requestAnimationFrame: it waits for the next frame, which a tab in the background never draws
    }

    private confirm(): void {
        const p = this.pick;
        if (!p || (!p.color && !p.mixing)) { return; }
        if (p.mode == 'bid') {
            this.bga.actions.performAction('actBid', {
                market_tile_id: p.tileId,
                moved_bid_ids: p.moved.join(','),
                numWorkersFromStock: p.fromStock,
                color: p.color,
            });
            return;
        }
        // A use: workers per color (the server takes several colors for boat 4b), what pays the cost, and the resource taken.
        const info = this.useInfo(p.tileId)!;
        const args: Record<string, string | number> = { tile_id: p.tileId, moved_bid_ids: p.moved.join(',') };
        // From your stock only: the moved groups' workers come with moved_bid_ids
        if (p.mixing) { COLORS.forEach(c => { if (p.mix[c]) { args[`num_${c}`] = p.mix[c]!; } }); } else { args[`num_${p.color}`] = p.fromStock; }
        const payColor = this.costWorker(info, p), paySkill = this.costSkill(info, p);
        if (payColor) {
            // A worker of that color beyond the ones placed: the last of them in your stock.
            const pay = this.stock().filter(w => w.color == payColor).pop();
            if (pay) { args['worker_pmt_id'] = pay.id; }
        }
        if (paySkill) {
            const pay = this.skills().find(s => s.type == paySkill);
            if (pay) { args['skill_pmt_id'] = pay.id; }
        }
        if (p.resource) { args['resource_type_reward'] = p.resource; }
        this.bga.actions.performAction('actActivate', args);
    }
}

/** Fills ${name} placeholders in a translated string. */
function fill(text: string, args: Record<string, string | number>): string {
    return text.replace(/\$\{(\w+)\}/g, (m, k: string) => k in args ? String(args[k]) : m);
}
