import { KFGameDatas, KFPlayer, Village, VillageTile, Worker } from "../kfdata";
import { BaseComponent } from "../libs/basecomponent";
import { Html } from "../libs/html";
import { MoreAnimations } from "../libs/more-animations";
import { RowCol } from "../rowcol";
import { activationRows, activationSummary } from "./activations";
import { TranslationComponent } from "./translation";

/**
 * All players' villages, yours first, then the others in turn order.
 * Each village is a map of its tiles at their positions, turned to their orientation.
 * The CSS does the layout from each tile's row, column and orientation; sizes come from
 * --kf-tile-w and --kf-tile-h, so a zoom only has to change those.
 */
export class VillageComponent extends BaseComponent<any> {

    private selectable = new Set<number>();
    private selected: number | null = null;

    public constructor(private bga: Bga<KFPlayer, KFGameDatas>, private translations: TranslationComponent,
        private animations: MoreAnimations) {
        super();
    }

    /** The village tiles that can be chosen (to use them); an empty list makes the villages read-only. */
    public setSelectable(tileIds: number[]): void {
        this.selectable = new Set(tileIds);
        this.refreshMarks();
    }

    public setSelected(tileId: number | null): void {
        this.selected = tileId;
        this.refreshMarks();
    }

    /**
     * Tiles that can be chosen act as buttons, and get an outline in a layer of its own over them (the
     * tile itself is clipped to its hex, which would cut a glow off), like the market's tiles.
     */
    private refreshMarks(): void {
        document.querySelectorAll('.kf_village_map .kf_tile_mark').forEach(m => m.remove());
        Object.values(this.bga.gameui.gamedatas.villages).flatMap(v => v.tiles).forEach(t => {
            const el = document.getElementById(`kf_tile_${t.tile_id}`);
            if (!el) { return; }
            const can = this.selectable.has(t.tile_id), chosen = this.selected == t.tile_id;
            el.classList.toggle('kf_selectable', can);
            // Every tile is a button: a choosable one is chosen, the others show their rules (keyflower.ts)
            el.setAttribute('role', 'button');
            el.tabIndex = 0;
            el.setAttribute('aria-label', [this.translations.tileName(t.tiletype), activationSummary(t.activations)].filter(x => x).join('. '));
            if (can) {
                el.setAttribute('aria-pressed', String(chosen));
                el.removeAttribute('aria-haspopup');
            } else {
                el.removeAttribute('aria-pressed');
                el.setAttribute('aria-haspopup', 'dialog');
            }
            if (can || chosen) {
                el.after(Html.div({
                    classes: ['kf_tile_mark', chosen ? 'kf_selected' : ''],
                    attrs: { 'aria-hidden': 'true' },
                    style: [`--kf-row: ${el.style.getPropertyValue('--kf-row')}`, `--kf-col: ${el.style.getPropertyValue('--kf-col')}`],
                }));
            }
        });
    }

    public setup(): HTMLElement {
        const gamedatas = this.bga.gameui.gamedatas;
        return Html.div({ id: 'kf_villages' },
            ...this.playerOrder(gamedatas).map(pid => this.villageDiv(gamedatas.villages[pid]!, gamedatas.players[pid]!))
        );
    }

    /** Player ids starting with the current player, if playing, then around the table. */
    private playerOrder(gamedatas: KFGameDatas): number[] {
        const order = gamedatas.playerorder.map(Number);
        const me = this.bga.players.getCurrentPlayerId();
        const i = order.indexOf(me);
        return i < 0 ? order : [...order.slice(i), ...order.slice(0, i)];
    }

    private villageDiv(village: Village, player: KFPlayer): HTMLElement {
        // Tiles are placed relative to Home, so tiles left of or above it have negative rows and
        // columns. Each tile gets its row and column counted from the village's top-left instead,
        // and the map is sized to the village's span. The CSS turns rows and columns into positions.
        const rcs = village.tiles.map(t => RowCol.unpack(t.rowcol));
        const minRow = Math.min(...rcs.map(rc => rc.row)), maxRow = Math.max(...rcs.map(rc => rc.row));
        const minCol = Math.min(...rcs.map(rc => rc.col)), maxCol = Math.max(...rcs.map(rc => rc.col));

        return Html.div({
                id: `kf_village_${village.player_id}`,
                classes: 'kf_village',
                attrs: { 'data-kf-player': String(village.player_id), role: 'region', 'aria-labelledby': `kf_village_name_${village.player_id}` },
                style: `--kf-player-color: #${player.color}`,
            },
            // A heading, so screen readers can jump from village to village (it names the village's region),
            // with the player's avatar, their name, and "(you)" on yours, as in the mockup
            Html.div({ id: `kf_village_name_${village.player_id}`, classes: 'kf_village_name', attrs: { role: 'heading', 'aria-level': 3 } },
                Html.img({ classes: 'kf_avatar', attrs: { src: this.bga.players.getPlayerAvatarUrl(village.player_id, 32), alt: '' } }),
                Html.span({ text: player.name }),
                village.player_id == this.bga.players.getCurrentPlayerId() ? Html.span({ classes: 'kf_village_you', text: _('(you)') }) : undefined),
            // The map scrolls sideways when the village is wider than the screen (phones); the name stays.
            Html.div({ classes: 'kf_village_scroll' },
                Html.div({
                        classes: 'kf_village_map',
                        style: [`--kf-row-span: ${maxRow - minRow}`, `--kf-col-span: ${maxCol - minCol}`],
                    },
                    ...village.tiles.flatMap((t, i) => this.tileDivs(t, rcs[i]!.row - minRow, rcs[i]!.col - minCol))
                )
            )
        );
    }

    /**
     * The tile, and the workers used on it this season in a layer of their own at the same place,
     * so they stay upright while the tile turns to its orientation.
     */
    private tileDivs(tile: VillageTile, row: number, col: number): HTMLElement[] {
        const uses = activationRows(tile.activations);
        if (uses) {
            uses.id = `kf_uses_${tile.tile_id}`;
            uses.style.cssText = `--kf-row: ${row}; --kf-col: ${col}`;
        }
        return uses ? [this.tileDiv(tile, row, col), uses] : [this.tileDiv(tile, row, col)];
    }

    /** The village tile with this id, in anyone's village. */
    public tile(tileId: number): VillageTile | undefined {
        return Object.values(this.bga.gameui.gamedatas.villages).flatMap(v => v.tiles).find(t => t.tile_id == tileId);
    }

    /** Adds a use of a village tile (tileActivated): its workers as a new row over the tile. */
    public addUse(tileId: number, workers: Worker[]): void {
        const tile = this.tile(tileId);
        const tileEl = document.getElementById(`kf_tile_${tileId}`);
        if (!tile || !tileEl) { console.error(`addUse: no village tile ${tileId}`); return; }
        (tile.activations ??= []).push([...workers]);
        const uses = activationRows(tile.activations)!;
        uses.id = `kf_uses_${tileId}`;
        uses.style.cssText = `--kf-row: ${tileEl.style.getPropertyValue('--kf-row')}; --kf-col: ${tileEl.style.getPropertyValue('--kf-col')}`;
        const old = document.getElementById(uses.id);
        if (old) { old.replaceWith(uses); } else { tileEl.after(uses); }
    }

    /** Animates the newest use on a village tile from where its workers came from. */
    public async flyUse(tileId: number, from: DOMRect): Promise<void> {
        const row = document.querySelector<HTMLElement>(`#kf_uses_${tileId} .kf_use:last-child`);
        if (row) { await this.animations.animateMove(row, from); }
    }

    /** row and col are counted from the village's top-left; data-kf-rowcol keeps the real position. */
    private tileDiv(tile: VillageTile, row: number, col: number): HTMLElement {
        const el = Html.div({
            id: `kf_tile_${tile.tile_id}`,
            classes: 'kf_tile',
            attrs: {
                'data-kf-tile': tile.tiletype,
                'data-kf-rowcol': String(tile.rowcol),
                'data-kf-orientation': String(tile.orientation),
            },
            style: [`--kf-row: ${row}`, `--kf-col: ${col}`],
            title: this.translations.tileName(tile.tiletype),
        });
        // A button from the start: until it can be chosen, a tap shows its rules (keyflower.ts)
        Object.entries({ role: 'button', tabindex: '0', 'aria-haspopup': 'dialog',
            'aria-label': [this.translations.tileName(tile.tiletype), activationSummary(tile.activations)].filter(x => x).join('. ') })
            .forEach(([k, v]) => el.setAttribute(k, v));
        const choose = () => { if (this.selectable.has(tile.tile_id)) { this.dispatch(tile); } };
        el.addEventListener('click', choose);
        el.addEventListener('keydown', e => { if (e.key == 'Enter' || e.key == ' ') { e.preventDefault(); choose(); } });
        return el;
    }
}
