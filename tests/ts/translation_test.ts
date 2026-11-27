import assert from 'node:assert/strict'
import { after, before, describe, it } from 'node:test'
import type { KFGameDatas } from '../../src/kfdata'

const tables: KFGameDatas['translations'] = {
  tiles: { keystonequarry: 'Keystone quarry' },
  colors: { red: '', blue: 'Blue', yellow: '', green: '' },
  seasons: { spring: '', summer: '', fall: 'Autumn', winter: '' },
  resources: { stone: 'Stone', wood: '', iron: '', gold: '' },
  skills: { pickaxe: 'Pickaxe', saw: '', anvil: '' },
}

const localized: Record<string, string> = {
  'Keystone quarry': 'Cantera',
  Blue: 'Azul',
  Autumn: 'Otoño',
  Stone: 'Piedra',
  Pickaxe: 'Pico',
  'Produces 1 ${stone}.': 'Produce 1 ${stone}.',
}

describe('TranslationComponent', () => {
  let TranslationComponent: typeof import('../../src/components/translation').TranslationComponent
  const originalTranslate = Object.getOwnPropertyDescriptor(globalThis, '_')

  before(async () => {
    // Tile descriptions call BGA's global translator when the module is loaded.
    Object.defineProperty(globalThis, '_', {
      configurable: true,
      value: (text: string) => localized[text] ?? text,
    })
    const translation = await import('../../src/components/translation')
    TranslationComponent = translation.TranslationComponent
  })

  after(() => {
    if (originalTranslate) {
      Object.defineProperty(globalThis, '_', originalTranslate)
    } else {
      Reflect.deleteProperty(globalThis, '_')
    }
  })

  function setUpTranslations() {
    const translations = new TranslationComponent()
    translations.setup(tables)
    return translations
  }

  it('translates names from all five tables', () => {
    const translations = setUpTranslations()
    assert.equal(translations.tileName('keystonequarry'), 'Cantera')
    assert.equal(translations.color('blue'), 'Azul')
    assert.equal(translations.season('fall'), 'Otoño')
    assert.equal(translations.resource('stone'), 'Piedra')
    assert.equal(translations.skill('pickaxe'), 'Pico')
  })

  it('uses the original keys for missing or empty table entries', () => {
    const translations = setUpTranslations()
    assert.equal(translations.tileName('woodcutter'), 'woodcutter')
    assert.equal(translations.color('red'), 'red')
    assert.equal(translations.season('winter'), 'winter')
    assert.equal(translations.resource('wood'), 'wood')
    assert.equal(translations.skill('saw'), 'saw')
  })

  it('translates tile rules and retains icon placeholders', () => {
    const translations = setUpTranslations()
    assert.deepEqual(translations.tileDescription('keystonequarry'), {
      summary: 'Produce 1 ${stone}.',
      upgrade: 'Can be upgraded for 1 ${pickaxe} to produce 3 ${stone}.',
      scoring: 'Grants 3 ${points} when upgraded.',
      notes: '',
    })
  })

  it('returns empty rule text for a tile without a description', () => {
    const translations = setUpTranslations()
    assert.deepEqual(translations.tileDescription('unknown-tile'), {
      summary: '', upgrade: '', scoring: '', notes: '',
    })
  })
})
