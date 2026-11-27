import { KFGameDatas, KFPlayer, SkillType, WorkerColor } from "../kfdata";
import { Html } from "../libs/html";
import { TooltipManager } from "../libs/tooltips";
import { ACTIVE_PLAYERS_CHANGED } from "../states/base";
import { accessibleDialog, TileRulesComponent } from "./tilerules";
import { TranslationComponent } from "./translation";
import { playingPlayerId } from "../libs/players";

const WORKER_COLORS: WorkerColor[] = ['red', 'yellow', 'blue', 'green'];
const SKILLS: SkillType[] = ['anvil', 'pickaxe', 'saw'];

/**
 * BGA's player panels, as in the mockup. BGA's own parts stay where BGA puts them; this adds, with
 * the CSS: the player's color for the avatar's ring, the active player's mark (kf_active: a bar down
 * the panel's edge and a "Your turn" or "Playing" chip), the start player's meeple after their name
 * (with a tooltip), and in your own panel your workers and tools, and a button with your winter tiles'
 * count that shows them large in a dialog. The server sends these to you only.
 */
export class PanelsComponent {

    private winterDialog: PopinDialog | undefined;

    public constructor(private bga: Bga<KFPlayer, KFGameDatas>, private translations: TranslationComponent,
        private tooltips: TooltipManager, private tileRules: TileRulesComponent) {
    }

    public setup(): void {
        const gamedatas = this.bga.gameui.gamedatas;
        const me = playingPlayerId(this.bga);
        // The players' ids are the keys; the player objects don't always carry them.
        Object.entries(gamedatas.players).forEach(([key, player]) => {
            const id = Number(key);
            this.panel(id).style.setProperty('--kf-player-color', `#${player.color}`);
            this.bga.playerPanels.getElement(id).append(this.chips(id == me));
        });
        this.setStartPlayer(gamedatas.startPlayerId);
        this.markActivePlayers();
        // BaseState reports every change of active players
        document.addEventListener(ACTIVE_PLAYERS_CHANGED, () => this.markActivePlayers());
        this.updatePrivateRow();
    }

    /**
     * Moves the start player's meeple to this player's panel, right after their name: no visible label
     * (once known, it isn't needed), hover or tap shows one. BGA places the online status and flag
     * itself, absolutely, so following the name is what keeps it clear of them.
     */
    public setStartPlayer(startPlayerId: number): void {
        Object.keys(this.bga.gameui.gamedatas.players).map(Number).forEach(id => {
            this.panel(id).classList.toggle('kf_start_player', id == startPlayerId);
            document.getElementById(`kf_start_${id}`)?.remove();
        });
        const meeple = Html.span({ id: `kf_start_${startPlayerId}`, classes: 'kf_starter', attrs: { role: 'img', 'aria-label': _('Start player') } });
        document.querySelector(`#player_name_${startPlayerId} > a`)?.after(meeple);
        if (meeple.isConnected) {
            this.tooltips.add(meeple, Html.span({ text: _('Start player') }));
        }
    }

    /**
     * Marks the panels of the players who must act now (kf_active: the bar and the turn chip).
     * BGA's own hourglass markers aren't reliable for this.
     */
    public markActivePlayers(): void {
        Object.keys(this.bga.gameui.gamedatas.players).map(Number).forEach(id => {
            this.panel(id).classList.toggle('kf_active', this.bga.players.isPlayerActive(id));
        });
    }

    /** The whole of BGA's panel for this player (getElement gives the game's own area inside it). */
    private panel(id: number): HTMLElement {
        return this.bga.playerPanels.getElement(id).closest<HTMLElement>('.player-board')!;
    }

    /**
     * Redraws your workers, tools and winter tiles. Call whenever they may have changed, e.g. after
     * your bids, which take workers from your stock.
     */
    public updatePrivateRow(): void {
        const me = playingPlayerId(this.bga);
        const village = me == null ? undefined : this.bga.gameui.gamedatas.villages[me];
        if (me == null || !village?.workers) { return; }   // spectators
        const panelEl = this.bga.playerPanels.getElement(me);
        let row = panelEl.querySelector<HTMLElement>('.kf_private');
        if (!row) {
            row = Html.div({ classes: 'kf_private', attrs: { role: 'group', 'aria-label': _('Your workers, tools and winter tiles') } });
            panelEl.append(row);
        }
        const winter = village.winter_tiles ?? [];
        row.replaceChildren(
            ...WORKER_COLORS.map(c => this.counted('kf_worker', { 'data-kf-color': c }, village.workers!.filter(w => w.color == c).length, workerLabel(c))),
            ...SKILLS.map(s => this.counted('kf_icon_skill', { 'data-kf-kind': s }, (village.skills ?? []).filter(k => k.type == s).length, skillLabel(s))),
            ...(winter.length > 0 ? [this.winterButton(winter.length)] : []),
        );
    }

    /** Your winter tiles' count; too small to read in the panel, so the button shows them in a dialog. */
    private winterButton(n: number): HTMLElement {
        const button = Html.button({
                classes: 'kf_winter_button',
                attrs: { 'aria-haspopup': 'dialog', 'aria-label': _('Your winter tiles: ${n}').replace('${n}', String(n)) },
            },
            Html.span({ classes: 'kf_winter_icon', attrs: { 'aria-hidden': 'true' } }),
            Html.span({ text: String(n) }));
        button.addEventListener('click', () => this.showWinterTiles());
        return button;
    }

    /** Your winter tiles, each at its size on the board with its name and rules beside it. */
    private showWinterTiles(): void {
        const me = playingPlayerId(this.bga);
        const tiles = (me == null ? undefined : this.bga.gameui.gamedatas.villages[me])?.winter_tiles ?? [];
        // Under the title, what they are first, then the tiles
        const content = Html.div({ classes: 'kf_winter_dialog' },
            Html.p({ classes: 'kf_winter_note', text: _('Only you can see them.') }),
            Html.div({ classes: 'kf_winter_tiles' },
                ...tiles.map(t => {
                    const name = this.translations.tileName(t.tiletype);
                    return Html.div({ classes: 'kf_winter_tile' },
                        Html.div({ classes: 'kf_tile', attrs: { 'data-kf-tile': t.tiletype, role: 'img', 'aria-label': name } }),
                        Html.div({ classes: 'kf_winter_text' },
                            Html.h3({ text: name }),
                            this.tileRules.list(t.tiletype)));
                })));
        this.winterDialog?.destroy();
        this.winterDialog = new ebg.popindialog();
        this.winterDialog.create('kf_winter_dialog');
        this.winterDialog.setTitle(_('Your winter tiles'));
        this.winterDialog.setContent(content.outerHTML);
        this.winterDialog.show();
        accessibleDialog(this.winterDialog, 'kf_winter_dialog');
    }

    /** "Your turn"/"Playing" (the CSS shows it on the active player's panel only, and not on phones). */
    private chips(mine: boolean): HTMLElement {
        return Html.div({ classes: 'kf_chips' },
            Html.span({ classes: ['kf_pchip', 'kf_chip_turn'], text: mine ? _('Your turn') : _('Playing') }));
    }

    /** An icon with its count beside it. */
    private counted(cls: string, attrs: Record<string, string>, n: number, label: string): HTMLElement {
        return Html.span({ classes: 'kf_counted' },
            Html.span({ classes: cls, attrs: { ...attrs, role: 'img', 'aria-label': label } }),
            Html.span({ classes: 'kf_count', text: String(n) }));
    }
}

function workerLabel(c: WorkerColor): string {
    return { red: _('red workers'), yellow: _('yellow workers'), blue: _('blue workers'), green: _('green workers') }[c];
}

function skillLabel(s: SkillType): string {
    return { anvil: _('anvils'), pickaxe: _('pickaxes'), saw: _('saws') }[s];
}
