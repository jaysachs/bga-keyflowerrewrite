import { KFGameDatas, KFPlayer, ResourceType, Season, SkillType, TileType, WorkerColor } from "../kfdata";

/**
 * Names in the player's language, from the server's tables (`gamedatas.translations`). It has no
 * HTML of its own; components that show names are constructed with it. Reads the tables when
 * asked, so it can be created before the game data arrives. Each name falls back to the raw value
 * (e.g. "keystonequarry", "blue") if the server sent none for it.
 */
export class TranslationComponent {

    public constructor(private bga: Bga<KFPlayer, KFGameDatas>) {
    }

    /** A tile's name, e.g. "Keystone quarry". */
    public tileName(tiletype: TileType): string {
        return this.name('tiles', tiletype);
    }

    /** A worker color, e.g. "blue". */
    public color(color: WorkerColor): string {
        return this.name('colors', color);
    }

    /** A season, e.g. "Autumn" (the server's "fall"). */
    public season(season: Season): string {
        return this.name('seasons', season);
    }

    /** A resource, e.g. "stone". */
    public resource(resource: ResourceType): string {
        return this.name('resources', resource);
    }

    /** A tool (skill tile), e.g. "pickaxe". */
    public skill(skill: SkillType): string {
        return this.name('skills', skill);
    }

    private name(table: keyof KFGameDatas['translations'], item: string): string {
        const names: Record<string, string> = this.bga.gameui.gamedatas.translations[table]!;
        const name = names[item];
        return name ? _(name) : item;
    }
}
