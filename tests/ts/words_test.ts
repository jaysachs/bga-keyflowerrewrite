import assert from 'node:assert/strict'
import { describe, it } from 'node:test'
import { splitNames } from '../../src/components/words'

describe('splitNames tests', () => {
  it('leaves text without names as it is', () => {
    assert.deepEqual(splitNames('Gain 1 point.'), [{ text: 'Gain 1 point.' }]);
  })

  it('splits out each name', () => {
    assert.deepEqual(splitNames('Take 2 ${stone} or 1 ${gold}'), [
      { text: 'Take 2 ' }, { name: 'stone', punct: '' }, { text: ' or 1 ' }, { name: 'gold', punct: '' },
    ]);
  })

  it('keeps punctuation right after a name with it', () => {
    assert.deepEqual(splitNames('Scores 3 ${points}. Then ${workers}, ${saw}).'), [
      { text: 'Scores 3 ' }, { name: 'points', punct: '.' }, { text: ' Then ' },
      { name: 'workers', punct: ',' }, { text: ' ' }, { name: 'saw', punct: ').' },
    ]);
  })

  it('handles names next to each other and at the start', () => {
    assert.deepEqual(splitNames('${blue}${red} first'), [
      { name: 'blue', punct: '' }, { name: 'red', punct: '' }, { text: ' first' },
    ]);
  })

  it('leaves a broken translation as text, and still finds the names in it', () => {
    assert.deepEqual(splitNames('${${blue} {red}$}$}${}$$'), [
      { text: '${' }, { name: 'blue', punct: '' }, { text: ' {red}$}$}${}$$' },
    ]);
  })
})
