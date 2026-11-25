import { ResourceType, Season, SkillType, WorkerColor } from "../kfdata";
import { Child, Html } from "../libs/html";
import { TextFormatter } from "../libs/textformatter";
import { TranslationComponent } from "./translation";

const COLORS: string[] = ['red', 'yellow', 'blue', 'green'] satisfies WorkerColor[];
const SEASONS: string[] = ['spring', 'summer', 'fall', 'winter'] satisfies Season[];
const RESOURCES: string[] = ['iron', 'stone', 'wood', 'gold'] satisfies ResourceType[];
const SKILLS: string[] = ['anvil', 'pickaxe', 'saw'] satisfies SkillType[];

/** A piece of a text with `${name}`s: plain text, or a name with the punctuation right after it. */
export type TextPart = { text: string } | { name: string, punct: string };

/**
 * Splits a text at its `${name}`s. Punctuation right after a name goes with it, so the name's icon and
 * the full stop never split across lines ("3 stone", its icon, "." stay together).
 */
export function splitNames(text: string): TextPart[] {
    const pieces = text.split(/\$\{(\w+)\}/);
    const out: TextPart[] = [];
    for (let i = 0; i < pieces.length; i++) {
        if (i % 2 == 0) {
            if (pieces[i]) { out.push({ text: pieces[i]! }); }
            continue;
        }
        const punct = pieces[i + 1]?.match(/^[.,;:!?)]+/)?.[0] ?? '';
        pieces[i + 1] = (pieces[i + 1] ?? '').slice(punct.length);
        out.push({ name: pieces[i]!, punct });
    }
    return out;
}

/**
 * The game's words with their icons, drawn the same everywhere: in the log and the status bar (the
 * server's message arguments, e.g. `${color}` sent as "blue") and in the tile rules (names written in
 * the text, e.g. `${stone}`). The word is translated and always shown, for screen readers and
 * colorblind players; the icon is hidden from screen readers. Seasons put the icon first, as in the
 * status bar's heading; the others put it after the word, as the rules read ("3 stone", then the stone).
 */
export class WordsComponent {

    public constructor(private translations: TranslationComponent) {}

    /**
     * In the log and the status bar: these arguments become the word and its icon (tiles just their
     * translated name). They're replaced with HTML, so notification handlers shouldn't read `color`,
     * `season`, `resource`, `skill` or `tiletype`; they use ids. Values the game doesn't know are left as
     * they are.
     */
    public registerFormatters(formatter: TextFormatter): void {
        formatter.registerFormatter('color', args => this.wrap(this.known(args.color, COLORS) ? this.color(args.color) : args.color));
        formatter.registerFormatter('season', args => this.wrap(this.known(args.season, SEASONS) ? this.season(args.season) : args.season));
        formatter.registerFormatter('resource', args => this.wrap(this.known(args.resource, RESOURCES) ? this.resource(args.resource) : args.resource));
        formatter.registerFormatter('skill', args => this.wrap(this.known(args.skill, SKILLS) ? this.tool(args.skill) : args.skill));
        formatter.registerFormatter('tiletype', args => Html.span({ text: this.translations.tileName(args.tiletype) }));
    }

    /** A worker color: "blue", then the blue meeple. */
    public color(color: WorkerColor): HTMLElement {
        return this.withIcon(['kf_color'], this.translations.color(color),
            Html.span({ classes: ['kf_icon', 'kf_worker'], attrs: { 'data-kf-color': color, 'aria-hidden': 'true' } }));
    }

    /** A season: its icon (Daniel's drawings), then "Spring". */
    public season(season: Season): HTMLElement {
        return Html.span({ classes: ['kf_with_icon', 'kf_season'] },
            Html.span({ classes: ['kf_icon', 'kf_season_icon'], attrs: { 'data-kf-season': season, 'aria-hidden': 'true' } }),
            this.translations.season(season));
    }

    /** A resource: "stone", then the stone. */
    public resource(resource: ResourceType): HTMLElement {
        return this.withIcon([], this.translations.resource(resource),
            Html.span({ classes: ['kf_icon', 'kf_icon_resource'], attrs: { 'data-kf-kind': resource, 'aria-hidden': 'true' } }));
    }

    /** A tool (skill tile): "saw", then the saw. */
    public tool(skill: SkillType): HTMLElement {
        return this.withIcon([], this.translations.skill(skill),
            Html.span({ classes: ['kf_icon', 'kf_icon_tool'], attrs: { 'data-kf-kind': skill, 'aria-hidden': 'true' } }));
    }

    /** "point" or "points", then the points icon. */
    public points(plural: boolean = true): HTMLElement {
        return this.withIcon([], plural ? _('points') : _('point'),
            Html.span({ classes: ['kf_icon', 'kf_icon_points'], attrs: { 'aria-hidden': 'true' } }));
    }

    /**
     * A text with `${name}`s (the tile rules), each name as its word and icon. Generic names
     * ("${workers}", "${skill}") are just the word; names the game doesn't know stay as they are.
     */
    public format(text: string): Child[] {
        return splitNames(text).map(p => 'text' in p
            ? p.text
            : Html.span({ classes: 'kf_nowrap' }, this.named(p.name), p.punct));
    }

    /** A name from a text: "blue", "stone", "saw", "points", "workers"… */
    public named(name: string): Child {
        if (COLORS.includes(name)) { return this.color(name as WorkerColor); }
        if (SEASONS.includes(name)) { return this.season(name as Season); }
        if (RESOURCES.includes(name)) { return this.resource(name as ResourceType); }
        if (SKILLS.includes(name)) { return this.tool(name as SkillType); }
        switch (name) {
            case 'point': return this.points(false);
            case 'points': return this.points(true);
            case 'worker': return _('worker');
            case 'workers': return _('workers');
            case 'skill': return _('tool');
            case 'skills': return _('tools');
            case 'resource': return _('resource');
            case 'resources': return _('resources');
            default: return name;
        }
    }

    private withIcon(classes: string[], word: string, icon: HTMLElement): HTMLElement {
        return Html.span({ classes: ['kf_with_icon', ...classes] }, word, icon);
    }

    private known(value: unknown, values: string[]): value is any {
        return typeof value == 'string' && values.includes(value);
    }

    /** For the log: the word and icon, or an unknown value as plain text. */
    private wrap(content: HTMLElement | unknown): HTMLElement {
        return content instanceof HTMLElement ? content : Html.span({ text: String(content ?? '') });
    }
}
