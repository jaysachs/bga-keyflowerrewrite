import assert from 'node:assert/strict'
import { describe, it } from 'node:test'
import { range } from '../../src/libs/utils'

describe('range tests', () => {
  it('verifies range produces the correct sequence', () => {
    const r = range(3,8);
    assert.deepEqual([3,4,5,6,7,8], r)
  })
})

describe('center tests', () => {
  it('verifies center produces the correct point', () => {
    // FIXME: how to get DOMRrect available here?
    /*
    const rect = new DOMRect(10, 20, 30, 40)
    const c = center(rect);
    assert.deepEqual({x: 10, y: 20},c)
    */
  })
})
