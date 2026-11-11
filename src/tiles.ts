// Tile art: where each server tile type sits in img/tiles-large.jpg, a sprite of 16 x 8 tiles (256 x 223 px each).
// [column, row] of the face shown, then [column, row] of the upgraded face for tiles that have one.
// Boats and turn order tiles have a fixed face per type. Taken from the old game's CSS (.tile_<number>_<side>).
import { TileType } from "./kfdata";

const ART: Record<string, [number, number] | [number, number, number, number]> = {
    home1: [13, 2, 14, 2],
    home2: [15, 2, 0, 3],
    home3: [1, 3, 2, 3],
    home4: [3, 3, 4, 3],
    home5: [5, 3, 6, 3],
    home6: [7, 3, 8, 3],
    alehouse: [0, 4, 1, 4],
    apprenticehall: [2, 4, 3, 4],
    goldmine: [4, 4, 5, 4],
    keymine: [6, 4, 7, 4],
    keystonequarry: [8, 4, 9, 4],
    keywood: [10, 4, 11, 4],
    miner: [12, 4, 13, 4],
    peddlar: [14, 4, 15, 4],
    quarryman: [0, 5, 1, 5],
    stable: [2, 5, 3, 5],
    woodcutter: [4, 5, 5, 5],
    workshop: [6, 5, 7, 5],
    brewer: [8, 5, 9, 5],
    carpenter: [10, 5, 11, 5],
    farrier: [12, 5, 13, 5],
    hiringfair: [14, 5, 15, 5],
    mason: [0, 6, 1, 6],
    smelter: [2, 6, 3, 6],
    store: [4, 6, 5, 6],
    tavern: [6, 6, 7, 6],
    barn: [0, 0, 1, 0],
    blacksmith: [2, 0, 3, 0],
    fair: [4, 0, 5, 0],
    forge: [6, 0, 7, 0],
    goldsmith: [8, 0, 9, 0],
    inn: [10, 0, 11, 0],
    sawmill: [12, 0, 13, 0],
    sculptor: [14, 0, 15, 0],
    stoneyard: [0, 1, 1, 1],
    timberyard: [2, 1, 3, 1],
    wainwright: [4, 1, 5, 1],
    well: [6, 1, 7, 1],
    apothecary: [9, 6],
    craftsmansguild: [10, 6],
    jeweller: [11, 6],
    keyguild: [12, 6],
    keymarket: [13, 6],
    keythedral: [14, 6],
    mercersguild: [15, 6],
    scholar: [0, 7],
    scribes: [1, 7],
    villagehall: [2, 7],
    watermill: [3, 7],
    windmill: [4, 7],
    boat1a: [8, 1],
    boat1b: [9, 1],
    boat2a: [10, 1],
    boat2b: [11, 1],
    boat3a: [12, 1],
    boat3b: [13, 1],
    boat4a: [14, 1],
    boat5b: [15, 1],
    keyflower: [0, 2],
    seabastion: [2, 2],
    seabreese: [4, 2],
    flipper: [6, 2],
    invincible: [8, 2],
    whitewind: [10, 2],
    start2: [9, 3],
    start3: [11, 3],
    start4: [13, 3],
    start36: [10, 3],
    start46: [12, 3],
    start56a: [14, 3],
    start56b: [15, 3],
};

export const SPRITE_COLUMNS = 16;
export const SPRITE_ROWS = 8;

/** CSS background-position for a tile's face, or undefined for an unknown type. */
export function tileArtPosition(tiletype: TileType, upgraded = false): string | undefined {
    const a = ART[tiletype];
    if (!a) return undefined;
    const [col, row] = upgraded && a.length === 4 ? [a[2], a[3]] : [a[0], a[1]];
    return `${col * -100}% ${row * -100}%`;
}
