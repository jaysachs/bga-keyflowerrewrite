import assert from 'node:assert/strict'
import { describe, it } from 'node:test'
import { center, range } from '../../src/libs/utils'

describe('range tests', () => {
  it('verifies range produces the correct sequence', () => {
    const r = range(3, 8);
    assert.deepEqual([3,4,5,6,7,8], r);
  })
})

describe('center tests', () => {
  it('verifies center produces the correct point', () => {
    const rect = {left: 10, top: 20, width: 30, height: 50 };
    const c = center(rect);
    assert.deepEqual({x: 25, y: 45}, c);
  })
})
