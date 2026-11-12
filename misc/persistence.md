# Persistence

## Observations, assumptions, principles
  - don't store tile static data, e.g. season, name, activation cost, etc. in the DB
  - There are no new items created during the game
    - so we can easily statically populate this at game creation
      based on number of players
    - In fact, we could always add all components, and then mark things as
      "discarded" (or "in box") if they're not used at that player count.
  - We will use a single table for all components / state
    - This means "inflating" the game state is a single full-table scrape

## Committed / uncommitted changes

Can store "pending" moves
  - As deltas to this single table in another table with the same columns
    with a sequence_id to group things into atomic units
  - Or could just add a seq_id to the main table, with 0 meaning "committed"
    (or keep a "high watermark" somewhere external)
  - Since we have multi-player active and want to allow undo, we need
    an "acting_player_id" column

It's most straightforward to keep separate table.

Note that we'll need to propagate the "pending-ness" out to the client.
For example, when placing tiles in a village, we want to indicate just
which tiles are in that "pending" state.

## Proposed schema

```sql
CREATE TABLE IF NOT EXISTS `pieces` (

  -- 'tile', 'worker', 'resource', 'skill', 'startplayer'
  piece_type varchar(12) NOT NULL,

  id int unsigned NOT NULL AUTO_INCREMENT,

  -- tile: the tile name, lowercase no spaces: 'home4', 'keymine', 'wainwright', 'boat4a'
  -- worker: the worker color: 'yellow', 'red', 'green', 'blue'
  -- resource: 'stone', 'wood', 'iron', 'gold'
  piece_subtype varchar(20),

  loc_type varchar(12) NOT NULL,
  loc_id: int,
  loc_pos: int,
  player_id int,

  -- only for village tiles and standard boats; can we encode this somewhere else?
  upgraded: tinyint,

  PRIMARY KEY(`piece_type`, `id`),

  -- TODO: consider this constraint ... may have to have non-null
  -- loc_id / loc_pos / player_id, using 0 to mean "unset"
  -- It will also require specifying "positions" or a "loc_id" for
  -- locations like "box", and means that skills in the skill pile
  --   and resources in the resource stocks also need "positions" or "loc_id"s.
  -- UNIQUE(`loc_type`, `loc_id`, `loc_pos`, `player_id`),
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
```



| `piece_type` | possible `piece_subtype`s |
| ------------ | ------------------------- |
| `worker` | `blue`, `red`, `yellow`, `green` |
| `resource` | `iron`, `stone`, `wood`, `gold` |
| `tile`   | `boat1a`, `home1`, `goldmine`, `keythedral`, `startplayer1` |
| `skill`  | `pickaxe`, `anvil`, `saw` |
| `startplayer` | none |

| `piece_type` | `loc_type` | what | meaning of `loc_id` | meaning of `loc_pos` | requires `player_id`|
| -------- | ------ | ------- | ------- | ------ | ----- |
| `startplayer` | `village` | startplayer token | - | - | Y |
|  `tile`  | `village` | tile placed in a village | encoded rowcol | tile orientation | Y |
|  `tile`  | `market` | tile in market | - | position in market | |
|  `tile`  | `dock`   | boat tile at dock | - | position in dock | |
|  `tile`  | `start`  | tile in start player tiles stock | - | position in pile | |
|  `tile`  | `spring` | tile in spring stock | - | position in pile | |
|  `tile`  | `summer` | tile in summer stock | - | position in pile | |
|  `tile`  | `fall` | tile in fall stock | - | position in pile | |
|  `tile`  | `winter` | tile in winter stock or player house | - | position | *: if specified, Y (the selecting player) |
|  `tile`  | `won` | won tile not yet placed in village  | - | position | Y |
|  `tile`  | `box` | out of play | - | "position"? | |
|  `worker`  | `market` | worker in bid | `tile_id` in market | position in the bidgroup | Y |
|  `worker`  | `tile` | worker in an activation | `tile_id` in some village or market | position (1-6) in the activation spots | |
| `worker` | `village` | (hidden) worker in a player's house | - | position | Y |
| `worker` | `bag` | worker in the bag | - | position | |
| `worker` | `greens` | green worker stock | - | position | |
| `worker` | `boat` | new works on boat | boat id | position | |
| `skill` | `village` | (hidden) skill in player's house | - | position | Y |
| `skill` | `skillpile` | skill in skill stock | - | position | |
| `skill` | `boat` | new skills on boat | boat id | position | |
| `resource` | `stock` | iron resource in stock | - | position | |
| `resource` | `stock` | wood resource in stock | - | position | |
| `resource` | `stock` | stone resource in stock | - | position | |
| `resource` | `stock` | gold resource in stock | - | position | |
| `resource` | `tile` | resource on a tile | tile id | position | |
| `skill` | `scoring` | end-game scoring assignment  | tile id | position | |
| `worker` | `scoring` | end-game scoring assignment | tile_id | position | |

Questions:
1. should we have a separate "scoring" location for resources/skills/workers assigned at the end of the game?
2. ~~should we have a separate "wontiles" location for tiles won but not yet placed?~~ **YES**
3. should we require player_id for things for which it can be inferred? e.g. skills on a tile at end of game ... the skills belong to the tile's owner.
4. ~~should we have a separate "winter tiles in hand" location? or can we depend on the existence/absence of player_id along with the "winter" location?~~  **NO**, we'll use `player_id` non-zero for this.
5. Can we eliminate `upgraded`? It's only for tiles and boats. We can't use e.g. negation of id, because that breaks the piece identity. We could, though, use the `piece_subtype` (append a trailing "_u"?)
6. Is unset (for e.g. `player_id`) NULL or 0? Relates on whether we want to include a `UNIQUE` constraint or not.
