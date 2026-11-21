export interface KFPlayer extends Player {}

export type TileType = string; // TODO: enum this here?

export type WorkerColor = 'red' | 'blue' | 'yellow' | 'green';
export interface Worker {
    id: number;
    color: WorkerColor;
}
export type SkillType = 'pickaxe' | 'saw' | 'anvil';
export type ResourceType = 'stone' | 'wood' | 'iron' | 'gold';
export interface Skill {
    id: number;
    type: SkillType;
}

// sequence x workers, e.g. [ /* first activation */ 0 => [ workers ... ], /* second */ 1 => [ ... ] ]
export type Activations = Worker[][];

export interface VillageTile {
    tile_id: number;
    tiletype: TileType;
    rowcol: number;
    orientation: number; // 1-6
    activations: Activations;
}

export type Season = 'spring' | 'summer' | 'fall' | 'winter';
/** A tile up for auction in the market, with the bids on it. */
export interface MarketLot {
    id: number;
    tiletype: TileType;
    bids: {
        player_id: number;
        workers: Worker[];
    }[];
    activations: Activations;
}
export type Market = MarketLot[];

export interface Boat {
    id: number;
    boattype: string;
    passengers: Worker[];
    cargo: {id:number; type: SkillType}[];
}
export type Dock = Boat[];

export interface StockTile {
    id: number;
    tiletype: TileType;
}

export interface Village {
    has_start_player: boolean;
    player_id: number;
    tiles: VillageTile[];
    workers: Worker[] | undefined;
    winter_tiles: StockTile[] | undefined;
    skills: Skill[] | undefined;
    // for now at least, reuse the StockTile interface
    won_tiles: StockTile[] | undefined;
}

export interface KFGameDatas extends Gamedatas<KFPlayer> {
    season: Season;
    startPlayerId: number;
    dock: Dock;
    market: Market;
    // key is player_id
    villages: Record<number,Village>;
    skillCount: number;
    bagCount: number;
    greenCount: number;
    translations: {
        tiles: Record<TileType,string>;
        resources: Record<ResourceType,string>;
        skills: Record<SkillType,string>;
        colors: Record<WorkerColor,string>;
        seasons: Record<Season,string>;
    }
}