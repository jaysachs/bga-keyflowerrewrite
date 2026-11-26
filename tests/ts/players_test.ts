import { describe, it } from 'node:test';
import assert from 'node:assert/strict';
import { playingPlayerId, seatOrder } from '../../src/libs/players';

/** Just what the helpers read: who's looking, and the table's players in seat order. */
function bga(viewer: number, playerorder: number[]): any {
    return {
        players: { getCurrentPlayerId: () => viewer },
        gameui: { gamedatas: { playerorder, players: Object.fromEntries(playerorder.map(id => [id, { id }])) } },
    };
}

describe('players', () => {
    it('gives your id when you play, and null when you watch', () => {
        assert.equal(playingPlayerId(bga(20, [10, 20, 30])), 20);
        assert.equal(playingPlayerId(bga(99, [10, 20, 30])), null);
    });

    it('starts the seat order with you, then goes around the table', () => {
        assert.deepEqual(seatOrder(bga(20, [10, 20, 30])), [20, 30, 10]);
        assert.deepEqual(seatOrder(bga(10, [10, 20, 30])), [10, 20, 30]);
    });

    it('keeps the seat order as it is for a spectator', () => {
        assert.deepEqual(seatOrder(bga(99, [10, 20, 30])), [10, 20, 30]);
    });

    it('reads seat order ids sent as strings', () => {
        assert.deepEqual(seatOrder(bga(30, ['10', '20', '30'] as any)), [30, 10, 20]);
    });
});
