import { Activations, Worker } from "../kfdata";
import { Html } from "../libs/html";

/**
 * The workers used on a tile this season, as in the mockup: one row per use, in the order they
 * were made, over the tile's illustration. Each use needs at least one more worker than the last,
 * so the rows also show what the next use needs (last row + 1). Workers in a row are sorted by
 * color, then id, so the tile looks the same after a reload. The rows never turn with the tile.
 */
export function activationRows(activations: Activations | undefined, classes: string[] = []): HTMLElement | undefined {
    if (!activations?.length) { return undefined; }
    return Html.div({ classes: ['kf_uses', ...classes], attrs: { 'aria-hidden': true } },
        ...activations.map(use => Html.div({ classes: 'kf_use', style: `--n: ${use.length}` },
            ...sorted(use).map(w => Html.span({ classes: 'kf_worker', attrs: { 'data-kf-color': w.color } })))));
}

/** "used 1 red, then 2 red", for a tile's label; empty when unused. */
export function activationSummary(activations: Activations | undefined): string {
    if (!activations?.length) { return ''; }
    return _('used') + ' ' + activations.map(use => {
        const byColor = new Map<string, number>();
        use.forEach(w => byColor.set(w.color, (byColor.get(w.color) ?? 0) + 1));
        return Array.from(byColor, ([c, n]) => `${n} ${c}`).join(' + ');
    }).join(', ' + _('then') + ' ');
}

function sorted(workers: Worker[]): Worker[] {
    return [...workers].sort((a, b) => a.color.localeCompare(b.color) || a.id - b.id);
}
