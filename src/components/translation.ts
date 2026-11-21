import { KFGameDatas, KFPlayer, ResourceType, Season, SkillType, TileDescription, TileType, WorkerColor } from "../kfdata";

/**
 * Names in the player's language, from the server's tables (`gamedatas.translations`). It has no
 * HTML of its own; components that show names are constructed with it. Reads the tables when
 * asked, so it can be created before the game data arrives. Each name falls back to the raw value
 * (e.g. "keystonequarry", "blue") if the server sent none for it.
 */
export class TranslationComponent {

    private readonly translations: typeof this.bga.gameui.gamedatas.translations;

    public constructor(private bga: Bga<KFPlayer, KFGameDatas>) {
        this.translations = bga.gameui.gamedatas.translations;
    }

    public tileDescription(tiletype: TileType): TileDescription {
        const desc = this.translations.tile_descriptions[tiletype];
        return {
            summary: this.translated(desc?.summary, tiletype),
            upgrade_requirements: this.translated(desc?.upgrade_requirements),
            points: this.translated(desc?.points),
            scoring_note: this.translated(desc?.scoring_note)
        }
    }

    /** A tile's name, e.g. "Keystone quarry". */
    public tileName(tiletype: TileType): string {
        return this.name(this.translations.tiles, tiletype);
    }

    /** A worker color, e.g. "blue". */
    public color(color: WorkerColor): string {
        return this.name(this.translations.colors, color);
    }

    /** A season, e.g. "Autumn" (the server's "fall"). */
    public season(season: Season): string {
        return this.name(this.translations.seasons, season);
    }

    /** A resource, e.g. "stone". */
    public resource(resource: ResourceType): string {
        return this.name(this.translations.resources, resource);
    }

    /** A tool (skill tile), e.g. "pickaxe". */
    public skill(skill: SkillType): string {
        return this.name(this.translations.skills, skill);
    }

    private name(names: Record<string, string>, item: string): string {
        return this.translated(names[item], item);
    }

    private translated(s: string | undefined, fallback: string = ""): string {
        return s ? _(s) : fallback;
    }
}
