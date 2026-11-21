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

  loc_type varchar(9) NOT NULL,
  loc_id: int,
  loc_pos: int,
  player_id int,

  -- only for village tiles and standard boats; can we encode this somewhere else?
  upgraded: tinyint,

  PRIMARY KEY(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
```



| `piece_type` | possible `piece_subtype`s |
| ------------ | ------------------------- |
| `worker`     | `blue`, `red`, `yellow`, `green` |
| `resource`   | `iron`, `stone`, `wood`, `gold` |
| `tile`       | `boat1a`, `home1`, `goldmine`, `keythedral`, `turnorder1`, ... |
| `skill`      | `pickaxe`, `anvil`, `saw` |
| `start`      | - |

| `piece_type` | `loc_type`  | what    | `loc_id` | `loc_pos` | `player_id`? |
| ------------ | ------------| ------- | ------- | ------ | ----- |
| `start`      | `village`   | startplayer token | - | - | Y |
| `tile`       | `village`   | tile placed in a village | encoded rowcol | tile orientation | Y |
| `tile`       | `market`    | tile in market | - | position in market | |
| `tile`       | `dock`      | boat tile at dock | - | position in dock | |
| `tile`       | `turnorder` | tile in turn order tiles stock | - | - | |
| `tile`       | `spring`    | tile in spring stock | - | - | |
| `tile`       | `summer`    | tile in summer stock | - | - | |
| `tile`       | `fall`      | tile in fall stock | - | - | |
| `tile`       | `winter`    | tile in winter stock or player house | - | - | *: if specified, Y (the selecting player) |
| `tile`       | `won`       | won tile not yet placed in village  | - | position | Y |
| `tile`       | `box`       | out of play | - | - | |
| `worker`     | `market`    | worker in bid | `tile_id` in market | - | Y |
| `worker`     | `tile`      | worker in an activation | `tile_id` in some village or market | activation number (sequence) | |
| `worker`     | `village`   | (hidden) worker in a player's house | - | - | Y |
| `worker`     | `bag`       | worker in the bag | - | - | |
| `worker`     | `greens`    | green worker in stock | - | - | |
| `worker`     | `boat`      | workers on boat | boat id | position | |
| `skill`      | `village`   | (hidden) skill in player's house | - | - | Y |
| `skill`      | `skillpile` | skill in skill stock | - | position | |
| `skill`      | `boat`      | new skills on boat | boat id | position | |
| `resource`   | `stock`     | resource in stock | - | - | |
| `resource`   | `tile`      | resource on a tile | tile id | - | |
| `skill`      | `scoring`   | end-game scoring assignment  | tile id | position | |
| `worker`     | `scoring`   | end-game scoring assignment | tile_id | position | |

Questions:
1. should we have a separate "scoring" location for resources/skills/workers assigned at the end of the game?
2. should we require player_id for things for which it can be inferred? e.g. skills on a tile at end of game ... the skills belong to the tile's owner.
3. how best to represent initial dealt winter tiles? as a separate loc_type, or just re-use `winter` but decide off of `player_id`?
4. Is unset (for e.g. `player_id`) NULL or 0?
5. Can we eliminate `won` and just use `village` with a NULL `loc_id` ?
6. Can we use tile_id for ordering in dock, market? Could we even use id for ordering for workers/skills on boats?