export interface KFPlayer extends Player {}

export type TileType = string; // TODO: enum this here?

export type WorkerColor = 'red' | 'blue' | 'yellow' | 'green';
export interface Worker {
    id: number;
    color: WorkerColor;
}
export type SkillType = 'pickaxe' | 'saw' | 'anvil';
export interface Skill {
    id: number;
    type: SkillType;
}
export interface Activations {
    // TODO flesh this out
}
export interface VillageTile {
    tile_id: number;
    tiletype: TileType;
    rowcol: number;
    orientation: number; // 1-6
    upgraded: boolean;
    activations: Activations;
}
export type Season = 'spring' | 'summer' | 'fall' | 'winter';
export interface MarketTile {
    id: number;
    tiletype: TileType;
    bids: {
        player_id: number;
        workers: Worker[];
    }
    activations: Activations;
}
export type Market = MarketTile[];
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
}