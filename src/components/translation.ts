import { KFGameDatas, KFPlayer, ResourceType, Season, SkillType, TileDescription, TileType, WorkerColor } from "../kfdata";

/**
 * Names in the player's language, from the server's tables (`gamedatas.translations`). It has no
 * HTML of its own; components that show names are constructed with it. Reads the tables when
 * asked, so it can be created before the game data arrives. Each name falls back to the raw value
 * (e.g. "keystonequarry", "blue") if the server sent none for it.
 */
export class TranslationComponent {

    private translations: typeof this.bga.gameui.gamedatas.translations;

    public constructor(private bga: Bga<KFPlayer, KFGameDatas>) {}

    public setup(): void {
        this.translations = this.bga.gameui.gamedatas.translations;
    }

    public tileDescription(tiletype: TileType): TileDescription {
        const desc = this.translations.tile_descriptions[tiletype];
        return {
            summary: this.translated(desc?.summary, tiletype),
            upgrade: this.translated(desc?.upgrade),
            points: this.translated(desc?.points),
            notes: this.translated(desc?.notes)
        }
    }

    /** A tile's name, e.g. "Keystone quarry". */
    public tileName(tiletype: TileType): string {
        return this.translated(this.translations.tiles[tiletype], tiletype);
    }

    /** A worker color, e.g. "blue". */
    public color(color: WorkerColor): string {
        return this.translated(this.translations.colors[color], color);
    }

    /** A season, e.g. "Autumn" (the server's "fall"). */
    public season(season: Season): string {
        return this.translated(this.translations.seasons[season], season);
    }

    /** A resource, e.g. "stone". */
    public resource(resource: ResourceType): string {
        return this.translated(this.translations.resources[resource], resource);
    }

    /** A tool (skill tile), e.g. "pickaxe". */
    public skill(skill: SkillType): string {
        return this.translated(this.translations.skills[skill], skill);
    }

    private translated(s: string | undefined, fallback: string = ""): string {
        return s ? _(s) : fallback;
    }
}
