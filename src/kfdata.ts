export interface KFPlayer extends Player {
  bottom: number;
  top: number;
}

export interface KFGameDatas extends Gamedatas<KFPlayer> {
  playerorder: number[];
  starter: number;
  hand: { skills: { number: number }, workers: { number: number } };
  moves: {type: number, move: {} };
  bids: { mid: string, pid: number, type: number }[];
  actions: { wid: string, tile_id: string, type: string, action_num: number}[];
  tiles: { name: string, score: string, description: string }[];
  assignedSkills: { wid: string, type: string, action_num: number, tile_id: string}[];
  villages: {}[];
  market: { mid: string, tile_id: number, side: number };
  cargo: { workers:{ id: string, type: string }[], skills:{id: string}[], side: number }[];
  winter: number[];
  resources: { id: string, type: string }[];
}