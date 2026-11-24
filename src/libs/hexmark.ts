/**
 * The line around a tile you can choose, or have chosen, as in the mockup: blue (#1d5ea8, 3px; 6px when
 * chosen) over a white edge, on the hex's own edge. An SVG over the tile (the tile itself is clipped
 * to its hex, which would cut a line or glow off), drawn at the same width whatever the tile's size.
 */
export function hexMark(selected: boolean): SVGSVGElement {
    const ns = 'http://www.w3.org/2000/svg';
    const svg = document.createElementNS(ns, 'svg');
    svg.setAttribute('class', selected ? 'kf_hexmark kf_selected' : 'kf_hexmark');
    svg.setAttribute('viewBox', '0 0 100 100');
    svg.setAttribute('preserveAspectRatio', 'none');
    svg.setAttribute('aria-hidden', 'true');
    for (const cls of ['kf_hexmark_under', 'kf_hexmark_line']) {
        const p = document.createElementNS(ns, 'polygon');
        p.setAttribute('points', '25,0 75,0 100,50 75,100 25,100 0,50');
        p.setAttribute('class', cls);
        svg.append(p);
    }
    return svg;
}
