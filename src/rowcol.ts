/** A hex position in doubled coordinates (see RowCol.php): Home is at row 0, column 0. */
export type RowCol = {
    row: number;
    col: number;
};

/**
 * Splits a packed rowcol (100 * row + col) into its row and column.
 * Works for negative rows and columns, as long as the column stays within -49..49.
 */
export function unpackRowCol(rowcol: number): RowCol {
    const col = ((rowcol % 100) + 150) % 100 - 50;
    return { row: (rowcol - col) / 100, col };
}
