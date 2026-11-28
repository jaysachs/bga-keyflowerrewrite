import { Resource, ResourceType } from "../kfdata";

/** The order resources are shown in, on tiles and wherever they're listed. */
export const RESOURCE_ORDER: ResourceType[] = ['wood', 'stone', 'iron', 'gold'];

/** How many of each kind there are, in RESOURCE_ORDER, leaving out the kinds with none. */
export function resourceCounts(resources: Resource[] | undefined): [ResourceType, number][] {
    return RESOURCE_ORDER.map(r => [r, (resources ?? []).filter(x => x.type == r).length] as [ResourceType, number]).filter(([, n]) => n > 0);
}
