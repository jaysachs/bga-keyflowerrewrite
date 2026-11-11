import { KFGameDatas, KFPlayer, Village, VillageTile } from "../kfdata";
import { BaseComponent } from "../libs/basecomponent";
import { Html } from "../libs/html";
import { tileArtPosition } from "../tiles";

/**
 * Splits a packed rowcol (100 * row + col, see RowCol.php) into its row and column.
 * Works for negative rows and columns, as long as the column stays within -49..49.
 */
export function unpackRowCol(rowcol: number): { row: number, col: number } {
    const col = ((rowcol % 100) + 150) % 100 - 50;
    return { row: (rowcol - col) / 100, col };
}

/**
 * Where a tile sits in its village map, in tile widths (x) and tile heights (y).
 * Flat-topped hexes in doubled coordinates: a column is 3/4 of a tile wide, a row half a tile high.
 */
function hexOffset(row: number, col: number): { x: number, y: number } {
    return { x: col * 0.75, y: row * 0.5 };
}

/**
 * All players' villages, yours first, then the others in turn order.
 * Each village is a map of its tiles at their positions, turned to their orientation.
 * Sizes come from CSS (--kf-tile-w, --kf-tile-h), so a zoom only has to change those.
 */
export class VillageComponent extends BaseComponent<any> {

    public constructor(private bga: Bga<KFPlayer, KFGameDatas>) {
        super();
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
        const placed = village.tiles.map(t => ({ tile: t, ...unpackRowCol(t.rowcol) }));
        const offsets = placed.map(p => hexOffset(p.row, p.col));
        const minX = Math.min(...offsets.map(o => o.x)), minY = Math.min(...offsets.map(o => o.y));
        const width = Math.max(...offsets.map(o => o.x)) - minX + 1;
        const height = Math.max(...offsets.map(o => o.y)) - minY + 1;

        return Html.div({
                id: `kf_village_${village.player_id}`,
                classes: 'kf_village',
                attrs: { 'data-kf-player': String(village.player_id) },
                style: `--kf-player-color: #${player.color}`,
            },
            Html.div({ classes: 'kf_village_name', text: player.name }),
            Html.div({
                    classes: 'kf_village_map',
                    style: [`--kf-cols: ${width}`, `--kf-rows: ${height}`],
                },
                ...placed.map((p, i) => this.tileDiv(p.tile, offsets[i]!.x - minX, offsets[i]!.y - minY))
            )
        );
    }

    private tileDiv(tile: VillageTile, x: number, y: number): HTMLElement {
        const art = tileArtPosition(tile.tiletype, tile.upgraded);
        return Html.div({
            id: `kf_tile_${tile.tile_id}`,
            classes: ['kf_tile', tile.upgraded ? 'kf_upgraded' : ''],
            attrs: {
                'data-kf-tiletype': tile.tiletype,
                'data-kf-rowcol': String(tile.rowcol),
                'data-kf-orientation': String(tile.orientation),
            },
            // Orientation 1 is the tile as printed; each step after it turns the tile 60 degrees counter-clockwise.
            style: [
                `--kf-x: ${x}`,
                `--kf-y: ${y}`,
                `--kf-turn: ${-(tile.orientation - 1) * 60}deg`,
                art ? `background-position: ${art}` : '',
            ],
            title: tile.tiletype,
        });
    }
}
