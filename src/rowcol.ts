/** A hex position in doubled coordinates (see RowCol.php): Home is at row 0, column 0. */
export class RowCol {
    public constructor(public readonly row: number, public readonly col: number) {}

    /**
     * Splits a packed rowcol (100 * row + col) into its row and column.
     * Works for negative rows and columns, as long as the column stays within -49..49.
     */
    public static unpack(rowcol: number): RowCol {
        const col = ((rowcol % 100) + 150) % 100 - 50;
        return new RowCol((rowcol - col) / 100, col);
    }

    /** The packed form the server uses, the inverse of unpack. */
    public pack(): number {
        return this.row * 100 + this.col;
    }
}
