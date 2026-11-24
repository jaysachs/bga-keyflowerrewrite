import { ResourceType, SkillType, TileType, WorkerColor } from "../kfdata";
import { Child, Html } from "../libs/html";
import { TranslationComponent } from "./translation";

const COLORS: string[] = ['red', 'yellow', 'blue', 'green'] satisfies WorkerColor[];
const RESOURCES: string[] = ['iron', 'stone', 'wood', 'gold'] satisfies ResourceType[];
const SKILLS: string[] = ['anvil', 'pickaxe', 'saw'] satisfies SkillType[];

/**
 * A tile's rules, from the server's descriptions (`translations.tile_descriptions`): what it does, how
 * it's upgraded, how it scores, and notes, whichever it has, one list item each. The `${…}` names in
 * the text become the word followed by its icon, as in the log ("1 stone", then the stone), so the
 * text teaches the icons on the tiles. Generic ones ("${workers}", "${skill}") are just the word.
 */
export class TileRulesComponent {
    private dialog: PopinDialog | undefined;

    public constructor(private translations: TranslationComponent) {}

    /** The rules as a list, in the order the tile reads: what it does, upgrade, scoring, notes. */
    public list(tiletype: TileType): HTMLElement {
        const d = this.translations.tileDescription(tiletype);
        const parts = [d.summary, d.upgrade, d.scoring, d.notes].filter((s): s is string => !!s);
        return Html.ul({ classes: 'kf_rules' }, ...parts.map(s => Html.li({}, ...this.format(s))));
    }

    /** A "Rules" button that shows the tile with its rules in a dialog. */
    public button(tiletype: TileType): HTMLButtonElement {
        // BGA's grey button, as for Pass: a secondary action beside the picker's Bid
        const b = Html.button({ classes: ['bgabutton', 'bgabutton_gray', 'kf_rules_button'], text: _('Rules'), attrs: { 'aria-haspopup': 'dialog' } });
        b.addEventListener('click', () => this.show(tiletype));
        return b;
    }

    /** The tile large, with its name as the title and its rules beside it (under it on phones). */
    public show(tiletype: TileType): void {
        const name = this.translations.tileName(tiletype);
        const content = Html.div({ classes: 'kf_rules_dialog' },
            Html.div({ classes: 'kf_tile', attrs: { 'data-kf-tile': tiletype, role: 'img', 'aria-label': name } }),
            this.list(tiletype));
        this.dialog?.destroy();
        this.dialog = new ebg.popindialog();
        this.dialog.create('kf_rules_dialog');
        this.dialog.setTitle(name);
        this.dialog.setContent(content.outerHTML);
        this.dialog.show();
    }

    /**
     * The text with each `${name}` replaced by its word and icon. Punctuation right after one stays on
     * its line ("3 stone", its icon and the full stop never split).
     */
    private format(text: string): Child[] {
        const parts = text.split(/\$\{(\w+)\}/);
        const out: Child[] = [];
        for (let i = 0; i < parts.length; i++) {
            if (i % 2 == 0) {
                out.push(parts[i]);
                continue;
            }
            const punct = parts[i + 1]?.match(/^[.,;:!?)]+/)?.[0] ?? '';
            parts[i + 1] = (parts[i + 1] ?? '').slice(punct.length);
            out.push(Html.span({ classes: 'kf_nowrap' }, this.token(parts[i]!), punct));
        }
        return out;
    }

    private token(name: string): Child {
        if (COLORS.includes(name)) {
            return Html.span({ classes: 'kf_color' },
                this.translations.color(name as WorkerColor),
                Html.span({ classes: 'kf_worker', attrs: { 'data-kf-color': name, 'aria-hidden': 'true' } }));
        }
        if (RESOURCES.includes(name)) {
            return this.withIcon(this.translations.resource(name as ResourceType), 'kf_icon_resource', name);
        }
        if (SKILLS.includes(name)) {
            return this.withIcon(this.translations.skill(name as SkillType), 'kf_icon_tool', name);
        }
        switch (name) {
            case 'point': return this.withIcon(_('point'), 'kf_icon_points', 'points');
            case 'points': return this.withIcon(_('points'), 'kf_icon_points', 'points');
            case 'worker': return _('worker');
            case 'workers': return _('workers');
            case 'skill': return _('tool');
            case 'skills': return _('tools');
            case 'resource': return _('resource');
            case 'resources': return _('resources');
            default: return name;
        }
    }

    private withIcon(word: string, cls: string, kind: string): HTMLElement {
        return Html.span({ classes: 'kf_with_icon' },
            word,
            Html.span({ classes: cls, attrs: { 'data-kf-kind': kind, 'aria-hidden': 'true' } }));
    }
}
