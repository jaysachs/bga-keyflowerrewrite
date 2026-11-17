import { KFGameDatas, KFPlayer, TileType } from "../kfdata";

/**
 * Names in the player's language, from the server's tables (`gamedatas.translations`). It has no
 * HTML of its own; components that show names are constructed with it. Reads the tables when
 * asked, so it can be created before the game data arrives.
 */
export class TranslationComponent {

    public constructor(private bga: Bga<KFPlayer, KFGameDatas>) {
    }

    /** A tile's name, e.g. "Keystone quarry"; the raw type if the server sent no name for it. */
    public tileName(tiletype: TileType): string {
        const name = this.bga.gameui.gamedatas.translations?.tiles?.[tiletype];
        return name ? _(name) : tiletype;
    }
}
