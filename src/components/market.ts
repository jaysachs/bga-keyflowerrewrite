import { KFGameDatas, KFPlayer, MarketLot, Worker, WorkerColor } from "../kfdata";
import { BaseComponent } from "../libs/basecomponent";
import { Html } from "../libs/html";
import { MoreAnimations } from "../libs/more-animations";
import { TranslationComponent } from "./translation";

/** One bid as shown on a tile. */
interface ShownBid {
    playerId: number;
    color: WorkerColor;
    num: number;
    outbid: boolean;
    /** Your outbid workers that the bid being chosen would move away. */
    moving: boolean;
    /** Workers the bid being chosen adds; 0 for bids already made. */
    added: number;
}

/** The edges used for each number of players, in seat order (see seatEdges). */
const SEAT_EDGES: Record<number, number[]> = {
    1: [3], 2: [3, 0], 3: [3, 5, 1], 4: [3, 4, 0, 1], 5: [3, 4, 5, 0, 1], 6: [3, 4, 5, 0, 1, 2],
};

/** A bid being chosen: shown on its tile until it's confirmed or cancelled. */
export interface PendingBid {
    tileId: number;
    color: WorkerColor;
    num: number;
    /** Tiles your outbid workers would move from. */
    movedFrom: number[];
}

/**
 * The market: the tiles up for auction this season, each with every player's bid on it,
 * the high bid first. While you choose a bid, the tiles you can bid on are buttons and
 * a click on one is dispatched to the handlers with its market tile.
 */
export class MarketComponent extends BaseComponent<MarketLot> {

    private selectable = new Set<number>();
    private selected: number | null = null;
    private pending: PendingBid | null = null;

    public constructor(private bga: Bga<KFPlayer, KFGameDatas>, private translations: TranslationComponent,
        private animations: MoreAnimations) {
        super();
    }

    public setup(): HTMLElement {
        return Html.div({ id: 'kf_market' },
            ...this.bga.gameui.gamedatas.market.map(mt => this.lotDiv(mt))
        );
    }

    public tile(tileId: number): MarketLot | undefined {
        return this.bga.gameui.gamedatas.market.find(mt => mt.id == tileId);
    }

    /** The lot for a tile id from the server, or undefined with an error if the market doesn't have it. */
    private lot(tileId: number, from: string): MarketLot | undefined {
        const mt = this.tile(tileId);
        if (!mt) { console.error(`${from}: no market tile ${tileId}`); }
        return mt;
    }

    /** Adds workers to a player's bid on a tile (bidMade sends only the workers added). */
    public addToBid(tileId: number, playerId: number, workers: Worker[]): void {
        const mt = this.lot(tileId, 'addToBid');
        if (!mt) { return; }
        const bid = mt.bids.find(b => b.player_id == playerId);
        if (bid) {
            bid.workers.push(...workers.filter(w => !bid.workers.some(x => x.id == w.id)));
        } else {
            mt.bids.push({ player_id: playerId, workers: [...workers] });
        }
        this.redraw(tileId);
    }

    /** Takes a player's bid off a tile (a bid moved to another tile). */
    public removeBid(tileId: number, playerId: number): void {
        const mt = this.lot(tileId, 'removeBid');
        if (!mt) { return; }
        mt.bids = mt.bids.filter(b => b.player_id != playerId);
        this.redraw(tileId);
    }

    /**
     * The element showing a player's bid on a tile. Each bid is drawn twice, along the tile's edge and
     * as a row under the tile, and the CSS shows one of them depending on the layout's width; this
     * returns the one that's shown (the other has no size).
     */
    private shownBid(tileId: number, playerId: number): HTMLElement | undefined {
        return Array.from(document.querySelectorAll<HTMLElement>(`#kf_lot_${tileId} [data-kf-player="${playerId}"]`))
            .find(e => e.getBoundingClientRect().width > 0);
    }

    /** Where a player's bid on a tile is drawn now. */
    public bidRect(tileId: number, playerId: number): DOMRect | undefined {
        return this.shownBid(tileId, playerId)?.getBoundingClientRect();
    }

    /**
     * Animates a player's bid on a tile from where its workers came from (`from`: where a moved bid
     * was, or the player's panel). Resolves when done, at once when animations are off.
     */
    public async flyBid(tileId: number, playerId: number, from: DOMRect): Promise<void> {
        const el = this.shownBid(tileId, playerId);
        if (el) {
            await this.animations.animateMove(el, from);
        }
    }

    /** The tiles that can be clicked; an empty list makes the market read-only. */
    public setSelectable(tileIds: number[]): void {
        const changed = [...this.selectable, ...tileIds];
        this.selectable = new Set(tileIds);
        changed.forEach(id => this.redraw(id));
    }

    public setSelected(tileId: number | null): void {
        const old = this.selected;
        this.selected = tileId;
        [old, tileId].forEach(id => id !== null && this.redraw(id));
    }

    /** Shows a bid being chosen on its tile, and marks the outbid workers it would move. */
    public setPending(pending: PendingBid | null): void {
        const touched = (p: PendingBid | null) => p ? [p.tileId, ...p.movedFrom] : [];
        const ids = new Set([...touched(this.pending), ...touched(pending)]);
        this.pending = pending;
        ids.forEach(id => this.redraw(id));
    }

    private redraw(tileId: number): void {
        const mt = this.tile(tileId);
        const old = document.getElementById(`kf_lot_${tileId}`);
        if (mt && old) {
            old.replaceWith(this.lotDiv(mt));
        }
    }

    /** Bids, highest first. Equal bids keep the order they were made in, as on the server. */
    private sortedBids(mt: MarketLot): MarketLot['bids'] {
        return mt.bids
            .filter(b => b.workers.length > 0)
            .map((b, i) => ({ b, i }))
            .sort((x, y) => y.b.workers.length - x.b.workers.length || x.i - y.i)
            .map(x => x.b);
    }

    /**
     * The edge of the tile each player's bids sit on, as on the table: you at the bottom, then the
     * others clockwise in turn order, spread around the tile when there are fewer than six.
     * Edges are numbered clockwise from the top: 0 top, 1 upper right … 3 bottom … 5 upper left.
     */
    private seatEdges(): Map<number, number> {
        const order = this.bga.gameui.gamedatas.playerorder.map(Number);
        const i = order.indexOf(this.bga.players.getCurrentPlayerId());
        const seats = i < 0 ? order : [...order.slice(i), ...order.slice(0, i)];
        const edges = SEAT_EDGES[seats.length] ?? SEAT_EDGES[6]!;
        return new Map(seats.map((pid, k) => [pid, edges[k]!]));
    }

    /** What to show for each bid on a tile, including the one being chosen. */
    private shownBids(mt: MarketLot): ShownBid[] {
        const me = this.bga.players.getCurrentPlayerId();
        const pending = this.pending?.tileId == mt.id ? this.pending : null;
        const moving = this.pending?.movedFrom.includes(mt.id) ?? false;
        const bids = this.sortedBids(mt);
        const shown: ShownBid[] = bids.map((b, i) => {
            const mine = b.player_id == me;
            // Your pending workers on a tile you already bid on are added to that bid.
            const added = mine && pending ? pending.num : 0;
            return { playerId: b.player_id, color: b.workers[0]!.color, num: b.workers.length + added, outbid: i > 0, moving: mine && moving, added };
        });
        if (pending && !bids.some(b => b.player_id == me)) {
            shown.push({ playerId: me, color: pending.color, num: pending.num, outbid: false, moving: false, added: pending.num });
        }
        return shown;
    }

    private lotDiv(mt: MarketLot): HTMLElement {
        const canClick = this.selectable.has(mt.id);
        const shown = this.shownBids(mt);
        const edges = this.seatEdges();

        const tileButton = Html.button({
                classes: ['kf_lot_tile', canClick ? 'kf_selectable' : '', this.selected == mt.id ? 'kf_selected' : ''],
                attrs: {
                    'aria-label': `${this.translations.tileName(mt.tiletype)}. ${this.bidSummary(this.sortedBids(mt))}`,
                    'aria-disabled': canClick ? null : true,
                    'aria-pressed': this.selected == mt.id ? true : null,
                },
            },
            Html.div({ id: `kf_tile_${mt.id}`, classes: 'kf_tile', attrs: { 'data-kf-tile': mt.tiletype }, title: this.translations.tileName(mt.tiletype) })
        );
        tileButton.addEventListener('click', () => {
            if (this.selectable.has(mt.id)) {
                this.dispatch(mt);
            }
        });

        // Bids sit along the tile's edges; where the market is too narrow for that, the CSS shows
        // them as rows under the tile instead. The tile button's label reads them out either way.
        return Html.div({ id: `kf_lot_${mt.id}`, classes: 'kf_lot', attrs: { 'data-kf-market-tile': mt.id } },
            Html.div({ classes: 'kf_lot_box' },
                tileButton,
                ...shown.map(b => this.edgeBid(b, edges.get(b.playerId) ?? 3)),
            ),
            Html.div({ classes: 'kf_bids', attrs: { 'aria-hidden': true } }, ...shown.map(b => this.bidRow(b))),
        );
    }

    private bidClasses(b: ShownBid, base: string): string[] {
        return [base, b.outbid && !b.added ? 'kf_outbid' : '', b.moving ? 'kf_moving' : '', b.added ? 'kf_pending' : ''];
    }

    /** A bid along one edge: the player's avatar, kept upright, then their workers. */
    private edgeBid(b: ShownBid, edge: number): HTMLElement {
        const player = this.bga.gameui.gamedatas.players[b.playerId]!;
        return Html.div({
                classes: this.bidClasses(b, 'kf_ebid'),
                attrs: { 'data-kf-player': b.playerId, 'data-kf-edge': edge, 'aria-hidden': true },
                style: `--kf-player-color: #${player.color}`,
            },
            Html.img({ classes: 'kf_avatar', attrs: { src: this.bga.players.getPlayerAvatarUrl(b.playerId, 32), alt: '' } }),
            workerIcons(b.color, b.num),
            b.added ? Html.span({ classes: 'kf_chip', text: `+${b.added}` }) : undefined,
            b.moving ? Html.span({ classes: 'kf_chip', text: `−${b.num}` }) : undefined,
        );
    }

    /** A bid as a row under the tile, for narrow screens. */
    private bidRow(b: ShownBid): HTMLElement {
        const player = this.bga.gameui.gamedatas.players[b.playerId]!;
        return Html.div({
                classes: this.bidClasses(b, 'kf_bid'),
                attrs: { 'data-kf-player': b.playerId },
                style: `--kf-player-color: #${player.color}`,
            },
            Html.span({ classes: 'kf_bid_player', text: player.name }),
            workerIcons(b.color, b.num),
            b.added ? Html.span({ classes: 'kf_chip', text: `+${b.added}` }) : undefined,
            b.moving ? Html.span({ classes: 'kf_chip', text: `−${b.num}` }) : undefined,
            b.outbid && !b.added && !b.moving ? Html.span({ classes: 'kf_tag', text: _('outbid') }) : undefined,
        );
    }

    private bidSummary(bids: MarketLot['bids']): string {
        if (bids.length == 0) { return _('No bids'); }
        const players = this.bga.gameui.gamedatas.players;
        return bids.map((b, i) => `${players[b.player_id]!.name}: ${b.workers.length} ${b.workers[0]!.color}${i == 0 ? '' : ', ' + _('outbid')}`).join('; ');
    }
}

/** Workers as icons: up to three, or one and a count past that. */
export function workerIcons(color: WorkerColor, num: number): HTMLElement {
    const icon = () => Html.span({ classes: 'kf_worker', attrs: { 'data-kf-color': color } });
    return Html.span({ classes: 'kf_workers', attrs: { 'aria-label': `${num} ${color}` } },
        ...(num > 3
            ? [icon(), Html.span({ classes: 'kf_count', text: `×${num}` })]
            : Array.from({ length: num }, icon))
    );
}
