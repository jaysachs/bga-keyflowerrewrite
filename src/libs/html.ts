/** An attribute value: numbers and booleans are written as text; null or undefined leaves the attribute out. */
export type AttrValue = string | number | boolean | null | undefined;

export interface AttrMaker {
    toRecord(): Record<string, AttrValue>;
}

export type AttrLike = AttrMaker | Record<string, AttrValue>;

/** A child: an element, or text (a string, added as a text node); undefined is skipped. */
export type Child = Node | string | undefined;

export class Html {
    private static makeElem(ty: string, args: { id?: string | undefined, text?: string | undefined, title?: string, attrs?: AttrLike, classes?: (string | string[]), style?: (string | string[]) }, ...children: Child[]): HTMLElement {
        const e = document.createElement(ty);
        if (args.id) { e.id = args.id; }
        if (args.classes) {
            if (typeof (args.classes) == "string") { args.classes = [args.classes]; }
            args.classes.forEach(c => { if (c) e.classList.add(c) });
        }
        if (args.title) {
            e.title = args.title;
        }
        if (args.style) {
            if (typeof (args.style) == "string") { args.style = [args.style]; }
            e.style = args.style.join(';');
        }
        if (args.text) {
            e.innerText = args.text;
        }
        if (args.attrs) {
            const r: Record<string, AttrValue> = typeof (args.attrs as any).toRecord == "function"
                ? (args.attrs as AttrMaker).toRecord()
                : args.attrs as Record<string, AttrValue>;
            Object.entries(r).forEach(([k, v]) => { if (v !== null && v !== undefined) { e.setAttribute(k, String(v)); } });
        }
        children.forEach(c => { if (c !== undefined && c !== '') { e.append(c); } });
        return e;
    }

    public static div(args: { id?: string | undefined, text?: string | undefined, title?: string, attrs?: AttrLike, classes?: (string | string[]), style?: (string | string[]) }, ...children: Child[]) {
        return this.makeElem('div', args, ...children);
    }

    public static span(args: { id?: string | undefined, text?: string | undefined, title?: string, attrs?: AttrLike, classes?: (string | string[]), style?: (string | string[]) }, ...children: Child[]) {
        return this.makeElem('span', args, ...children);
    }

    public static p(args: { id?: string | undefined, text?: string | undefined, title?: string, attrs?: AttrLike, classes?: (string | string[]), style?: (string | string[]) }, ...children: Child[]) {
        return this.makeElem('p', args, ...children);
    }

    public static img(args: { id?: string | undefined, title?: string, attrs?: AttrLike, classes?: (string | string[]), style?: (string | string[]) }) {
        return this.makeElem('img', args) as HTMLImageElement;
    }

    public static button(args: { id?: string | undefined, text?: string | undefined, title?: string, attrs?: AttrLike, classes?: (string | string[]), style?: (string | string[]) }, ...children: Child[]) {
        const b = this.makeElem('button', args, ...children);
        b.setAttribute('type', 'button');
        return b as HTMLButtonElement;
    }

    /**
    * Returns the index of the given element among its parent's child elements or -1 if no parent.
    */
    static indexInParent(el: Element): number {
        return Array.from(el.parentElement?.children ?? []).findIndex(e => e == el);
    }
}