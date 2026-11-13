import { KFGameDatas, KFPlayer, Village, VillageTile } from "../kfdata";
import { BaseComponent } from "../libs/basecomponent";
import { Html } from "../libs/html";
import { RowCol } from "../rowcol";

/**
 * All players' villages, yours first, then the others in turn order.
 * Each village is a map of its tiles at their positions, turned to their orientation.
 * The CSS does the layout from each tile's row, column and orientation; sizes come from
 * --kf-tile-w and --kf-tile-h, so a zoom only has to change those.
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
        // Tiles are placed relative to Home, so tiles left of or above it have negative rows and
        // columns. Each tile gets its row and column counted from the village's top-left instead,
        // and the map is sized to the village's span. The CSS turns rows and columns into positions.
        const rcs = village.tiles.map(t => RowCol.unpack(t.rowcol));
        const minRow = Math.min(...rcs.map(rc => rc.row)), maxRow = Math.max(...rcs.map(rc => rc.row));
        const minCol = Math.min(...rcs.map(rc => rc.col)), maxCol = Math.max(...rcs.map(rc => rc.col));

        return Html.div({
                id: `kf_village_${village.player_id}`,
                classes: 'kf_village',
                attrs: { 'data-kf-player': String(village.player_id) },
                style: `--kf-player-color: #${player.color}`,
            },
            Html.div({ classes: 'kf_village_name', text: player.name }),
            Html.div({
                    classes: 'kf_village_map',
                    style: [`--kf-row-span: ${maxRow - minRow}`, `--kf-col-span: ${maxCol - minCol}`],
                },
                ...village.tiles.map((t, i) => this.tileDiv(t, rcs[i]!.row - minRow, rcs[i]!.col - minCol))
            )
        );
    }

    /** row and col are counted from the village's top-left; data-kf-rowcol keeps the real position. */
    private tileDiv(tile: VillageTile, row: number, col: number): HTMLElement {
        return Html.div({
            id: `kf_tile_${tile.tile_id}`,
            classes: 'kf_tile',
            attrs: {
                'data-kf-tile': tile.tiletype,
                'data-kf-rowcol': String(tile.rowcol),
                'data-kf-orientation': String(tile.orientation),
            },
            style: [`--kf-row: ${row}`, `--kf-col: ${col}`],
            title: tile.tiletype,
        });
    }
}
