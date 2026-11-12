import { KFGameDatas, KFPlayer, Village, VillageTile } from "../kfdata";
import { BaseComponent } from "../libs/basecomponent";
import { Html } from "../libs/html";
import { RowCol, unpackRowCol } from "../rowcol";

/**
 * Where a hex sits relative to Home, in tile widths (x) and tile heights (y).
 * Flat-topped hexes in doubled coordinates: a column is 3/4 of a tile wide, a row half a tile high.
 * The CSS places tiles with the same formula, from --kf-row and --kf-col.
 */
function hexOffset(rc: RowCol): { x: number, y: number } {
    return { x: rc.col * 0.75, y: rc.row * 0.5 };
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
        // Tiles are placed relative to Home, which can be anywhere in the village: tiles left of
        // or above it have negative offsets. The map is sized to the village's extent, and the
        // origin (Home's corner) is moved in by the leftmost and topmost offsets, so every tile shows.
        const offsets = village.tiles.map(t => hexOffset(unpackRowCol(t.rowcol)));
        const minX = Math.min(...offsets.map(o => o.x)), maxX = Math.max(...offsets.map(o => o.x));
        const minY = Math.min(...offsets.map(o => o.y)), maxY = Math.max(...offsets.map(o => o.y));

        return Html.div({
                id: `kf_village_${village.player_id}`,
                classes: 'kf_village',
                attrs: { 'data-kf-player': String(village.player_id) },
                style: `--kf-player-color: #${player.color}`,
            },
            Html.div({ classes: 'kf_village_name', text: player.name }),
            Html.div({
                    classes: 'kf_village_map',
                    // in tiles: the map's width and height, and where Home's corner sits in it
                    style: [`--kf-map-w: ${maxX - minX + 1}`, `--kf-map-h: ${maxY - minY + 1}`, `--kf-origin-x: ${-minX}`, `--kf-origin-y: ${-minY}`],
                },
                Html.div({ classes: 'kf_village_origin' },
                    ...village.tiles.map(t => this.tileDiv(t))
                )
            )
        );
    }

    private tileDiv(tile: VillageTile): HTMLElement {
        const rc = unpackRowCol(tile.rowcol);
        const attrs: Record<string, string> = {
            'data-kf-tile': tile.tiletype,
            'data-kf-rowcol': String(tile.rowcol),
            'data-kf-orientation': String(tile.orientation),
        };
        if (tile.upgraded) {
            attrs['data-kf-upgraded'] = '';
        }
        return Html.div({
            id: `kf_tile_${tile.tile_id}`,
            classes: 'kf_tile',
            attrs,
            // Orientation 1 is the tile as printed; each step after it turns the tile 60 degrees counter-clockwise.
            style: [`--kf-row: ${rc.row}`, `--kf-col: ${rc.col}`, `--kf-turn: ${-(tile.orientation - 1) * 60}deg`],
            title: tile.tiletype,
        });
    }
}
