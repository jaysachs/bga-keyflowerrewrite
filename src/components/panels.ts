import { KFGameDatas, KFPlayer, SkillType, WorkerColor } from "../kfdata";
import { Html } from "../libs/html";
import { TranslationComponent } from "./translation";

const WORKER_COLORS: WorkerColor[] = ['red', 'yellow', 'blue', 'green'];
const SKILLS: SkillType[] = ['anvil', 'pickaxe', 'saw'];

/**
 * BGA's player panels, laid out as in the mockup (the CSS does the layout): each panel gets its
 * player's color for the avatar's ring, chips under the name ("Your turn" or "Playing" on the
 * active player's panel, "Start player"), and in your own panel your workers, tools, outbid groups
 * and winter tiles, which the server sends to you only.
 */
export class PanelsComponent {

    public constructor(private bga: Bga<KFPlayer, KFGameDatas>, private translations: TranslationComponent) {
    }

    public setup(): void {
        const gamedatas = this.bga.gameui.gamedatas;
        const me = this.bga.players.getCurrentPlayerId();
        // The players' ids are the keys; the player objects don't always carry them.
        Object.entries(gamedatas.players).forEach(([key, player]) => {
            const id = Number(key);
            document.getElementById(`overall_player_board_${id}`)?.style.setProperty('--kf-player-color', `#${player.color}`);
            this.bga.playerPanels.getElement(id).append(this.chips(id, id == me, id == gamedatas.startPlayerId));
            document.getElementById(`overall_player_board_${id}`)?.classList.toggle('kf_start_player', id == gamedatas.startPlayerId);
            if (id == gamedatas.startPlayerId) {
                // The meeple has no visible label (once known, it isn't needed); hover or tap shows one.
                this.bga.gameui.addTooltip(`kf_start_${id}`, _('Start player'), '');
            }
        });
        this.markActive();
        this.updatePrivateRow();
    }

    /**
     * Marks the panels of the players who must act now (kf_active: the outline and the turn chip).
     * Call on every change of active player; BGA's own hourglass markers aren't reliable for this.
     */
    public markActive(): void {
        Object.keys(this.bga.gameui.gamedatas.players).map(Number).forEach(id => {
            document.getElementById(`overall_player_board_${id}`)?.classList.toggle('kf_active', this.bga.players.isPlayerActive(id));
        });
    }

    /**
     * Redraws your workers, tools, outbid groups and winter tiles. Call whenever they may have
     * changed: after any bid (yours takes workers from your stock, anyone's can outbid you).
     */
    public updatePrivateRow(): void {
        const me = this.bga.players.getCurrentPlayerId();
        const village = this.bga.gameui.gamedatas.villages[me];
        if (!village?.workers) { return; }   // spectators
        const host = this.bga.playerPanels.getElement(me);
        let row = host.querySelector<HTMLElement>('.kf_private');
        if (!row) {
            row = Html.div({ classes: 'kf_private', attrs: { role: 'group', 'aria-label': _('Your workers, tools and winter tiles') } });
            host.append(row);
        }
        row.replaceChildren(
            ...WORKER_COLORS.map(c => this.counted('kf_worker', { 'data-kf-color': c }, village.workers!.filter(w => w.color == c).length, workerLabel(c))),
            ...SKILLS.map(s => this.counted('kf_icon_skill', { 'data-kf-kind': s }, (village.skills ?? []).filter(k => k.type == s).length, skillLabel(s))),
            this.outbidGroups(me),
            Html.div({ classes: 'kf_private_winter', attrs: { role: 'group', 'aria-label': _('Your winter tiles') } },
                ...(village.winter_tiles ?? []).map(t => {
                    const name = this.translations.tileName(t.tiletype);
                    return Html.div({ classes: 'kf_tile', title: name, attrs: { 'data-kf-tile': t.tiletype, role: 'img', 'aria-label': name } });
                })),
        );
    }

    /** "Your turn"/"Playing" (the CSS shows it on the active player's panel only) and "Start player". */
    private chips(id: number, mine: boolean, start: boolean): HTMLElement {
        return Html.div({ classes: 'kf_chips' },
            Html.span({ classes: ['kf_pchip', 'kf_chip_turn'], text: mine ? _('Your turn') : _('Playing') }),
            start
                ? Html.span({ id: `kf_start_${id}`, classes: ['kf_pchip', 'kf_chip_start'] },
                    Html.span({ classes: 'kf_starter', attrs: { 'aria-hidden': true } }),
                    document.createTextNode(_('Start player')))
                : undefined);
    }

    /** An icon with its count beside it. */
    private counted(cls: string, attrs: Record<string, string>, n: number, label: string): HTMLElement {
        return Html.span({ classes: 'kf_counted' },
            Html.span({ classes: cls, attrs: { ...attrs, role: 'img', 'aria-label': label } }),
            Html.span({ classes: 'kf_count', text: String(n) }));
    }

    /** Your bids that someone has outbid: each a group of one color that can move as a whole. */
    private outbidGroups(me: number): HTMLElement {
        const box = Html.div({ classes: 'kf_private_outbid' });
        this.bga.gameui.gamedatas.market.forEach(lot => {
            const mine = lot.bids.find(b => b.player_id == me && b.workers.length > 0);
            const top = Math.max(0, ...lot.bids.map(b => b.workers.length));
            if (!mine || mine.workers.length >= top) { return; }
            const color = mine.workers[0]!.color;
            const text = _('Outbid on ${tile}').replace('${tile}', this.translations.tileName(lot.tiletype));
            const group = this.counted('kf_worker', { 'data-kf-color': color }, mine.workers.length, workerLabel(color));
            group.classList.add('kf_outbid_group');
            group.setAttribute('aria-label', `${workerLabel(color)}: ${mine.workers.length}, ${text}`);
            group.append(Html.span({ classes: 'kf_outbid_where', text }));
            box.append(group);
        });
        return box;
    }
}

function workerLabel(c: WorkerColor): string {
    return { red: _('red workers'), yellow: _('yellow workers'), blue: _('blue workers'), green: _('green workers') }[c];
}

function skillLabel(s: SkillType): string {
    return { anvil: _('anvils'), pickaxe: _('pickaxes'), saw: _('saws') }[s];
}
