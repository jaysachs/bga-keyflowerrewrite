import { describe, it } from 'node:test';
import assert from 'node:assert/strict';
import { resourceCounts } from '../../src/libs/resources';
import { Resource, ResourceType } from '../../src/kfdata';

const res = (...types: ResourceType[]): Resource[] => types.map((type, id) => ({ id, type }));

describe('resourceCounts', () => {
    it('counts each kind, in the order wood, stone, iron, gold', () => {
        assert.deepEqual(resourceCounts(res('gold', 'iron', 'wood', 'iron', 'stone')), [['wood', 1], ['stone', 1], ['iron', 2], ['gold', 1]]);
    });

    it('leaves out the kinds there are none of', () => {
        assert.deepEqual(resourceCounts(res('iron', 'iron')), [['iron', 2]]);
    });

    it('is empty for no resources, or none sent', () => {
        assert.deepEqual(resourceCounts([]), []);
        assert.deepEqual(resourceCounts(undefined), []);
    });
});
