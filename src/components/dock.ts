import { Boat, KFGameDatas, KFPlayer, SkillType, WorkerColor } from "../kfdata";
import { BaseComponent } from "../libs/basecomponent";
import { Html } from "../libs/html";
import { TranslationComponent } from "./translation";


const COLOR_ORDER: WorkerColor[] = ['red', 'yellow', 'blue', 'green'];

/**
 * The boats, after the market: each boat tile with its passengers on the meeple outlines and its tools
 * on the "?" squares, as on the table (positions in the CSS, per boat face). The cargo is sized and
 * placed in % of the tile, so it scales with it.
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

    /**
     * The boat's tile, then its workers and tools in order; the CSS puts each on its space for that
     * boat face (data-kf-boat), so new art only means new positions in the CSS.
     */
    private boatDiv(boat: Boat): HTMLElement {
        const workers = [...boat.passengers].sort((a, b) => COLOR_ORDER.indexOf(a.color) - COLOR_ORDER.indexOf(b.color));
        return Html.div({ id: `kf_boat_${boat.id}`, classes: 'kf_boat', attrs: { role: 'img', 'aria-label': this.label(boat), 'data-kf-boat': boat.boattype } },
            Html.div({ id: `kf_tile_${boat.id}`, classes: 'kf_tile', attrs: { 'data-kf-tile': boat.boattype } }),
            Html.div({ classes: 'kf_boat_workers' },
                ...workers.map(w => Html.span({ classes: ['kf_worker', 'kf_boat_item'], attrs: { 'data-kf-color': w.color } }))),
            Html.div({ classes: 'kf_boat_tools' },
                ...boat.cargo.map(s => Html.span({ classes: ['kf_boat_tool', 'kf_boat_item'], attrs: { 'data-kf-kind': s.type } }))));
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
