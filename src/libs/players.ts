/**
 * Who is looking at the game. BGA's getCurrentPlayerId() is the viewer's user id, which a spectator
 * has too, so "mine" also needs a check that the viewer plays at this table.
 */

/** Your player id when you play at this table, or null when you're watching. */
export function playingPlayerId<P extends Player, G extends Gamedatas<P>>(bga: Bga<P, G>): number | null {
    const id = bga.players.getCurrentPlayerId();
    return bga.gameui.gamedatas.players[id] ? id : null;
}

/** The players' ids in seat order, starting with yours when you play. */
export function seatOrder<P extends Player, G extends Gamedatas<P>>(bga: Bga<P, G>): number[] {
    const order = bga.gameui.gamedatas.playerorder.map(Number);
    const i = order.indexOf(playingPlayerId(bga) ?? -1);
    return i < 0 ? order : [...order.slice(i), ...order.slice(0, i)];
}
