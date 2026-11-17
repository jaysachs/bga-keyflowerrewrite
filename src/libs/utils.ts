export function range(start: number, end: number): number[] {
    return Array.from({ length: (end - start + 1) }, (v, k) => k + start);
}

/** The center of a rectangle, e.g. from getBoundingClientRect(). */
export function center(r: DOMRect): { x: number, y: number } {
    return { x: r.left + r.width / 2, y: r.top + r.height / 2 };
}
