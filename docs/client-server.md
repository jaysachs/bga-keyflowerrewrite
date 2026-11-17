# Client-server interface

## Aspirational principles, goals, assumptions

1. Game logic / rules implemented just once, typically in the server
2. Server must offer "all possible legal moves"; strive to provide client sufficient information to "know" which interactions are legal.
3. Minimize the number of back/forth communications. Specifically, prefer to send all legal moves for all pieces in one request, rather than offering "here are pieces with legal moves" and then when a piece is selected, send the legal moves for that piece. This may not be feasible, for example due to excessive payload size.

## Specific interactions

### Bidding

This is for the PlayerTurn state.

Server provides client information on all biddable tiles, which includes what color and minimum number of workers required to bid. Also available should be which existing bids are "losing", and thus moveable.

```ts
interface BidInfo {
    /** The color a bid must be, or null if any color may be used. */
    color: WorkerColor | null;
    /** The smallest total that would be the high bid. */
    min: number;
    /** Whether the player has an outbid bid here that could be moved to another tile. */
    moveable: boolean;
    /** the player's current bid, which may be "reinforced" */
    current_bid: { color: WorkerColor; num: number; workers: Worker[] } | null;
    /** Whether bids on this tile must match the current color (reflects boat4a) */
    must_match: boolean;
}

interface PlayerTurnArgs {
    /** What the active player may bid on one market lot (PlayerTurn args, in market order). */
    bidding_info: BidInfo[];
}
```

The server call to make a bid is

```ts
actBid({
    market_tile_id: number;
    moved_bid_ids: number[]; // tile_ids from which to move bids
    numWorkersFromStock: number;
    color: WorkerColor;
})
```


### Activation

This is for the PlayerTurn state.

Activations will be rendered in distinct rows on the activated tile, top to bottom in order of the activations. (There can be at most 3 activations of a tile.) Ordering within an activation (to handle e.g. boat4b) will be done client-side.

For several tiles a player needs to supply a component (resource, skill, worker) ("cost") to activate it. Some costs are "fixed" (e.g. a specific worker type), others involve a choice (e.g. any skill). This is similar to activation rewards; some awards are "fixed" (e.g. a set number and type of resource or worker), others involve a choice (e.g. a resource type), and still others involve game state change to pick a random component (e.g. worker from bag or skill from pile). And "upgrade" is the most different kind of reward, and requires separate state/handling.

Here is a proposal for handling activations. We extend the state data returned by PlayerTurn
```ts
interface PlayerTurnArgs {
    bidding_info: BidInfo[];
}
```
to be
```ts
interface PlayerTurnArgs {
    bidding_info: BidInfo[];

    // array keys are tile IDs
    activation_info: ActivationInfo[];
}

type ActivationCostType = 'pickaxe' | 'saw' | 'anvil' | 'anyskill' | 'red' | 'yellow' | 'blue' | 'green' | 'anyworker' | 'none';
type ActivationRewardType = 'green' | 'worker' | 'skill' | 'gold' | 'wood' | 'iron' | 'stone' | 'irongold'
                        | 'woodgold' | 'stonegold' | 'resource' | 'upgrade';

// Describes for a give tile (market lot or tile in a village) the activation cost and reward
interface ActivationInfo {
    tile_id: number;
    // owning palyer id if in village; if 0/null means it's a market lot
    owner_player_id: number | null;

    activation_cost_qty: number;
    activation_cost_type: ActivationCostType;

    activation_reward_type: ActivationRewardType;
    activation_reward_qty: number;

    /** The color an activation must be, or null if any color may be used. */
    color: WorkerColor | null;
    /** The smallest number of workers required to activate */
    min: number;
    /** The largest number of workers that can be used to activate */
    max: number;
    /** Whether the player has an outbid bid here that could be moved to another tile. */
    moveable: boolean;
    /** Whether activations on this tile must match the current color (reflects boat4b) */
    must_match: boolean;

    // could include
    //   available_resources: { type: ResourceType; id: number }[];
    // for use in selecting resources prior to confirmation
    // but the client should already have the ResourceStock information which includes this.
}
```

Client flow will critically depend on the activation reward. For fixed known rewards, the client could "give" the reward to the player before any server interaction (At a minimum it could "show" the actual reward.). It's also possible that the client calls the server, which triggers notifications, but permits undo.  (This is possibly ideal.) For randomized rewards, the client should prompt for confirmation before the server call. It seems best if upgrades proceed to a different state entirely, where resources are "tentatively" moved to new tiles to perform upgrades. The process of doing upgrades should at a minimum be restartable, and ideally incrementally undoable.

Undoable moves should be visible only to the active player, until the activation is "committed". "Committed" depends on the type of activation: random rewards are immediately committed; non-upgrades are committed

This analysis suggests the follow server actions:

```ts
actActivate( args: {
    // These first four are *identical* to what is used for `actBid`
    tile_id: number;
    moved_bids: number[]; // tile ID(s) containing losing bids being used to support this activation
    workersFromStock: number;
    workerColor: workerColor;

    // payment of the "cost" of the activation. (Could maybe combine into one field.)
    skill_id: number | null;
    worker_id: number | null;

    // For tiles offering player choice of a resource type to receive
    resource_reward: ResourceType | null;
})
```

The responding notification will be:

```ts
notif_activationReward(args: {
    tile_id: number; // activated tile
    reward_type: ActivationRewardType;
    skills: { type: SkillType; id: number }[];
    resources: { type: ResourceType; id: number } [];
    workers: { type: WorkerColor; id: number }[];
})
```
For workers/skills not on the board (e.g. `getElementById(idFor(thing.id)) === null`), the client animate them from their source (resource stock, worker bag, green stock, skill pile).

For fixed known rewards, we could include an undo but prefer to keep that client-side only. There should be sufficient information in the `ActivationInfo` record for the client to "simulate" the reward.

As mentioned previously, upgrades will actually enter a new game state `UpgradeTile`.

#### Upgrade

(This is a "sub" state of `PlayerTurn`.)

The big choice here is whether to make a server call for each resource move, or one call for all moves.

Propose that at a minimum, the server returns a "matrix" describing the cost in moves from each tile in the player's village, and the cost to upgrade each tile:

```ts
interface UpgradeTileArgs {
    // key is the source tile ID for moveable resoruces
    move_cost: Record<number,{ destination_tile_id: number; moves: number }[]>;
    // key is the potentially upgradeable tile
    upgrade_cost: Record<number,{
        // There are other possible representations, this is just a strawman proposal
        gold: number;
        iron: number;
        wood: number;
        stone: number;
        anyresource: number;
        pickaxe: number;
        saw: number;
        anvil: number;
        anyskill: number;
    }>
}

actMoveResource({
    src_tile_id: number;
    resource_id: number;
    dest_tile_id: number;
})

notif_ResourceMoved(args {
    src_tile_id: number;
    resource_id: number;
    dest_tile_id: number;
    moves_remaining: number;
})

actUpgradeTile({
    tile_id: number;
    skill_id: number[] | null;
    resource_id: number[] | null;
})

notif_TileUpgraded({
    tile_id: number;
    skill_id: number[] | null;
    resource_id: number[] | null;
})

//  The server just knows what to undo (move or upgradeTile), and also issues an appropriate
//  `notif_ResourceMoved`, which includes an "update" to remaining resource moves.
actUndo({})

// Requried:
actUpgradeDone({})
// which sends notifs to *all players* on the moves/upgrade, and then transitions to the next player's turn.
```


### Placing tiles

This is for the `PlaceTiles` state, which happens after all players have passed and chosen boats.

Initial idea is that we return "which tiles can be placed where in what orientation". Each time a tile is placed, we hit the server for updated possibilities.

Placement of any tile is a server action, returning a new "set of possible placements". Note that moving a tile just placed will need to obey placement constraints. If you need to place a tile where prior placements make it conflict, those prior placements need to be moved first. During placement we permit an "un-place", which moves a tile back to the pending set. If "un-placement" would cause disconnection of one or more other tiles, that would be not permitted, with a message indicating why not (e.g. "Brewer cannot be returned to won tiles because it would disconnect Carpenter").
