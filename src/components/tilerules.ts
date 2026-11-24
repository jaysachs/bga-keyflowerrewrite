import { TileType } from "../kfdata";
import { Html } from "../libs/html";
import { TranslationComponent } from "./translation";
import { WordsComponent } from "./words";

/**
 * Makes a BGA dialog, just shown, usable with a screen reader and the keyboard: BGA's popin isn't
 * announced as a dialog, keeps focus behind it, ignores Escape, and its close icon has no name. It
 * becomes a modal dialog named by its title, focus moves to its Close button, Escape (or Close)
 * closes it, and focus goes back where it was.
 */
export function accessibleDialog(dialog: PopinDialog, id: string): void {
    const opener = document.activeElement as HTMLElement | null;
    const box = document.getElementById(`popin_${id}`);
    if (!box) { return; }
    box.setAttribute('role', 'dialog');
    box.setAttribute('aria-modal', 'true');
    box.setAttribute('aria-labelledby', `popin_${id}_title`);
    const close = document.getElementById(`popin_${id}_close`);
    close?.setAttribute('role', 'button');
    close?.setAttribute('aria-label', _('Close'));
    const done = () => {
        dialog.destroy();
        if (opener?.isConnected) { opener.focus(); }
    };
    dialog.replaceCloseCallback(done);
    box.addEventListener('keydown', e => {
        // Escape anywhere in it; Space on Close too (it's a link, which only Enter activates)
        if (e.key == 'Escape' || (e.key == ' ' && e.target == close)) { e.preventDefault(); done(); }
    });
    close?.focus();
}

/**
 * A tile's rules, from the server's descriptions (`translations.tile_descriptions`): what it does, how
 * it's upgraded, how it scores, and notes, whichever it has, one list item each. The `${…}` names in
 * the text become the word and its icon, as in the log ("1 stone", then the stone), so the text
 * teaches the icons on the tiles (WordsComponent).
 */
export class TileRulesComponent {
    private dialog: PopinDialog | undefined;

    public constructor(private translations: TranslationComponent, private words: WordsComponent) {}

    /** The rules as a list, in the order the tile reads: what it does, upgrade, scoring, notes. */
    public list(tiletype: TileType): HTMLElement {
        const d = this.translations.tileDescription(tiletype);
        const parts = [d.summary, d.upgrade, d.scoring, d.notes].filter((s): s is string => !!s);
        return Html.ul({ classes: 'kf_rules' }, ...parts.map(s => Html.li({}, ...this.words.format(s))));
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
        accessibleDialog(this.dialog, 'kf_rules_dialog');
    }
}
