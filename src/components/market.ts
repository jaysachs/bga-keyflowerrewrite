import { KFGameDatas, KFPlayer, MarketTile, Worker, WorkerColor } from "../kfdata";
import { BaseComponent } from "../libs/basecomponent";
import { Html } from "../libs/html";

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
export class MarketComponent extends BaseComponent<MarketTile> {

    private selectable = new Set<number>();
    private selected: number | null = null;
    private pending: PendingBid | null = null;

    public constructor(private bga: Bga<KFPlayer, KFGameDatas>) {
        super();
    }

    public setup(): HTMLElement {
        return Html.div({ id: 'kf_market' },
            ...this.bga.gameui.gamedatas.market.map(mt => this.lotDiv(mt))
        );
    }

    public tile(tileId: number): MarketTile | undefined {
        return this.bga.gameui.gamedatas.market.find(mt => mt.id == tileId);
    }

    /** Adds workers to a player's bid on a tile (bidMade sends only the workers added). */
    public addToBid(tileId: number, playerId: number, workers: Worker[]): void {
        const mt = this.tile(tileId);
        if (!mt) { return; }
        const bid = mt.bids.find(b => b.player_id == playerId);
        if (bid) {
            bid.workers.push(...workers.filter(w => !bid.workers.some(x => x.id == w.id)));
        } else {
            mt.bids.push({ player_id: playerId, workers: [...workers] });
        }
        this.redraw(tileId);
    }

    /** Takes a player's bid off a tile (bidMoved). */
    public removeBid(tileId: number, playerId: number): void {
        const mt = this.tile(tileId);
        if (!mt) { return; }
        mt.bids = mt.bids.filter(b => b.player_id != playerId);
        this.redraw(tileId);
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
    private sortedBids(mt: MarketTile): MarketTile['bids'] {
        return mt.bids
            .filter(b => b.workers.length > 0)
            .map((b, i) => ({ b, i }))
            .sort((x, y) => y.b.workers.length - x.b.workers.length || x.i - y.i)
            .map(x => x.b);
    }

    private lotDiv(mt: MarketTile): HTMLElement {
        const me = this.bga.players.getCurrentPlayerId();
        const bids = this.sortedBids(mt);
        const pending = this.pending?.tileId == mt.id ? this.pending : null;
        const moving = this.pending?.movedFrom.includes(mt.id) ?? false;
        const canClick = this.selectable.has(mt.id);

        const tileButton = Html.button({
                classes: ['kf_lot_tile', canClick ? 'kf_selectable' : '', this.selected == mt.id ? 'kf_selected' : ''],
                attrs: {
                    'aria-label': `${tileName(mt.tiletype)}. ${this.bidSummary(bids)}`,
                    ...(canClick ? {} : { 'aria-disabled': 'true' }),
                    ...(this.selected == mt.id ? { 'aria-pressed': 'true' } : {}),
                },
            },
            Html.div({ id: `kf_tile_${mt.id}`, classes: 'kf_tile', attrs: { 'data-kf-tile': mt.tiletype }, title: tileName(mt.tiletype) })
        );
        tileButton.addEventListener('click', () => {
            if (this.selectable.has(mt.id)) {
                this.dispatch(mt);
            }
        });

        return Html.div({ id: `kf_lot_${mt.id}`, classes: 'kf_lot', attrs: { 'data-kf-market-tile': String(mt.id) } },
            tileButton,
            Html.div({ classes: 'kf_bids' },
                ...bids.map((b, i) => {
                    const mine = b.player_id == me;
                    // Your pending workers on a tile you already bid on are added to that row.
                    const added = mine && pending ? pending.num : 0;
                    return this.bidRow(b.player_id, b.workers[0]!.color, b.workers.length + added, {
                        outbid: i > 0, moving: mine && moving, added,
                    });
                }),
                pending && !bids.some(b => b.player_id == me)
                    ? this.bidRow(me, pending.color, pending.num, { outbid: false, moving: false, added: pending.num })
                    : undefined,
            )
        );
    }

    private bidRow(playerId: number, color: WorkerColor, num: number, o: { outbid: boolean, moving: boolean, added: number }): HTMLElement {
        const player = this.bga.gameui.gamedatas.players[playerId];
        return Html.div({
                classes: ['kf_bid', o.outbid && !o.added ? 'kf_outbid' : '', o.moving ? 'kf_moving' : '', o.added ? 'kf_pending' : ''],
                attrs: { 'data-kf-player': String(playerId) },
                style: `--kf-player-color: #${player?.color ?? '888888'}`,
            },
            Html.span({ classes: 'kf_bid_player', text: player?.name ?? String(playerId) }),
            workerIcons(color, num),
            o.added ? Html.span({ classes: 'kf_chip', text: `+${o.added}` }) : undefined,
            o.moving ? Html.span({ classes: 'kf_chip', text: `−${num}` }) : undefined,
            o.outbid && !o.added && !o.moving ? Html.span({ classes: 'kf_tag', text: _('outbid') }) : undefined,
        );
    }

    private bidSummary(bids: MarketTile['bids']): string {
        if (bids.length == 0) { return _('No bids'); }
        const players = this.bga.gameui.gamedatas.players;
        return bids.map((b, i) => `${players[b.player_id]?.name}: ${b.workers.length} ${b.workers[0]!.color}${i == 0 ? '' : ', ' + _('outbid')}`).join('; ');
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

/** A readable name until the server sends translated names: "keystonequarry" stays as it is. */
export function tileName(tiletype: string): string {
    const t = tiletype.replace(/_u$/, '');
    return t.charAt(0).toUpperCase() + t.slice(1);
}
