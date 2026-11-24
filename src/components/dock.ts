import { Boat, KFGameDatas, KFPlayer, SkillType, WorkerColor } from "../kfdata";
import { BaseComponent } from "../libs/basecomponent";
import { Html } from "../libs/html";
import { TranslationComponent } from "./translation";

type Point = [number, number];

/**
 * Where the cargo goes on each boat face (% of the tile): w = the white meeple outlines for workers,
 * t = the "?" squares for tools, in reading order. Measured from tiles-large.jpg (the centres of the
 * white shapes). Faces without spaces (the winter sides) aren't listed.
 */
const BOAT_SPACES: Record<string, { w: Point[], t: Point[] }> = {
    keyflower: { w: [[39.3, 33.2], [52.4, 33.1], [39.3, 47.9], [52.8, 47.9], [39.3, 62.1], [52.8, 62.4], [39.2, 76.5]], t: [[52.7, 76.9]] },
    keyflower_u: { w: [[37.6, 34.2], [50.8, 34.0], [44.2, 48.5]], t: [[37.1, 63.6], [51.6, 63.3]] },
    seabastion: { w: [[43.3, 30.7], [56.4, 30.8], [43.3, 45.2], [56.8, 45.3], [43.3, 59.5], [56.7, 59.5]], t: [[43.6, 74.4], [57.2, 74.3]] },
    seabreese: { w: [[38.9, 35.2], [52.0, 35.2], [38.8, 49.7], [52.4, 49.7], [38.8, 64.0], [52.3, 64.4]], t: [[45.4, 79.3]] },
    seabreese_u: { w: [[36.1, 33.9], [49.3, 33.9], [36.1, 48.5], [49.3, 48.5]], t: [[42.8, 63.6]] },
    flipper: { w: [[41.1, 27.2], [34.1, 42.2], [47.1, 42.0], [34.1, 56.8], [47.5, 56.8]], t: [[33.8, 72.6], [47.9, 72.6]] },
    flipper_u: { w: [[35.4, 37.9], [48.5, 37.8]], t: [[34.8, 52.9], [49.0, 52.8]] },
    invincible: { w: [[42.4, 30.5], [55.6, 30.4], [42.4, 45.1], [55.9, 45.1], [49.1, 60.2]], t: [[49.0, 75.3]] },
    invincible_u: { w: [[40.0, 31.2], [53.2, 31.2], [46.6, 46.0]], t: [[47.1, 61.4]] },
    whitewind: { w: [[39.8, 39.1], [52.7, 39.1], [39.8, 53.7], [53.2, 53.8]], t: [[39.7, 69.4], [53.9, 69.4]] },
    whitewind_u: { w: [[39.6, 33.0], [52.7, 33.2], [39.5, 47.8], [52.9, 47.8]], t: [[46.3, 63.2]] },
};

const COLOR_ORDER: WorkerColor[] = ['red', 'yellow', 'blue', 'green'];

/**
 * The boats, after the market: each boat tile with its passengers on the meeple outlines and its tools
 * on the "?" squares, as on the table. The cargo is sized and placed in % of the tile, so it scales
 * with it.
 */
export class DockComponent extends BaseComponent<any> {

    public constructor(private bga: Bga<KFPlayer, KFGameDatas>, private translations: TranslationComponent) {
        super();
    }

    public setup(): HTMLElement {
        const dock = this.bga.gameui.gamedatas.dock;
        const open = this.isOpen();
        // The label folds the boats away, as in the mockup: they matter at the end of a season, and take
        // room the rest of it (a screen's worth on phones). Remembered on this device.
        const fold = Html.button({ id: 'kf_dock_title', classes: 'kf_dock_fold', attrs: { 'aria-expanded': open, 'aria-controls': 'kf_boats' } },
            Html.span({ classes: 'kf_chevron', attrs: { 'aria-hidden': 'true' } }),
            _('Boats'));
        const boats = Html.div({ id: 'kf_boats', classes: 'kf_boats' }, ...dock.map(boat => this.boatDiv(boat)));
        boats.hidden = !open;
        fold.addEventListener('click', () => {
            const nowOpen = boats.hidden;
            boats.hidden = !nowOpen;
            fold.setAttribute('aria-expanded', String(nowOpen));
            try { localStorage.setItem('kf-boats-open', nowOpen ? '1' : '0'); } catch { /* storage blocked: just not remembered */ }
        });
        return Html.div({ id: 'kf_dock', attrs: { role: 'group', 'aria-labelledby': 'kf_dock_title' } },
            Html.div({ classes: 'kf_dock_title', attrs: { role: 'heading', 'aria-level': 3 } }, fold),
            boats);
    }

    private isOpen(): boolean {
        try { return localStorage.getItem('kf-boats-open') !== '0'; } catch { return true; }
    }

    private boatDiv(boat: Boat): HTMLElement {
        const spaces = BOAT_SPACES[boat.boattype] ?? { w: [], t: [] };
        const workers = [...boat.passengers].sort((a, b) => COLOR_ORDER.indexOf(a.color) - COLOR_ORDER.indexOf(b.color));
        const at = (p: Point | undefined) => p ? `left: ${p[0]}%; top: ${p[1]}%` : 'display: none';
        return Html.div({ id: `kf_boat_${boat.id}`, classes: 'kf_boat', attrs: { role: 'img', 'aria-label': this.label(boat) } },
            Html.div({ id: `kf_tile_${boat.id}`, classes: 'kf_tile', attrs: { 'data-kf-tile': boat.boattype } }),
            ...workers.map((w, i) => Html.span({ classes: ['kf_worker', 'kf_boat_item'], attrs: { 'data-kf-color': w.color }, style: at(spaces.w[i]) })),
            ...boat.cargo.map((s, i) => Html.span({ classes: ['kf_boat_tool', 'kf_boat_item'], attrs: { 'data-kf-kind': s.type }, style: at(spaces.t[i]) })));
    }

    /** For screen readers, e.g. "Keyflower: 3 red, 2 yellow, 2 blue workers; saw". */
    private label(boat: Boat): string {
        const counts = COLOR_ORDER.map(c => [c, boat.passengers.filter(w => w.color == c).length] as const).filter(([, n]) => n > 0);
        const workers = counts.length ? `${counts.map(([c, n]) => `${n} ${this.translations.color(c)}`).join(', ')} ${_('workers')}` : '';
        const tools = boat.cargo.map(s => this.translations.skill(s.type as SkillType)).join(', ');
        const cargo = [workers, tools].filter(x => x).join('; ');
        return cargo ? `${this.translations.tileName(boat.boattype)}: ${cargo}` : this.translations.tileName(boat.boattype);
    }
}
