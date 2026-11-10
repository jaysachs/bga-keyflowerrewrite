export interface KFPlayer extends Player {}

export type WorkerColor = 'red' | 'blue' | 'yellow' | 'green'  /* | 'purple' */;
export interface Worker {
    id: number;
    color: WorkerColor;
}
export type SkillType = 'pickaxe' | 'saw' | 'anvil';
export interface Skill {
    id: number;
    type: SkillType;
}
export interface VillageTile {
    tiletype: string;
    rowcol: number;
    orientation: number; // 1-6
}
export type Season = 'spring' | 'summer' | 'fall' | 'winter';
export interface MarketTile {
    id: number;
    tiletype: string;
    bids: {
        player_id: number;
        workers: Worker[];
    }
}
export type Market = MarketTile[];
export interface Boat {
    id: number;
    boattype: string;
    passengers: Worker[];
    cargo: {id:number; type: SkillType}[];
}
export type Dock = Boat[];

export interface Village {
    tiles: VillageTile[];
    workers: Worker[];
    skills: Skill[];
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