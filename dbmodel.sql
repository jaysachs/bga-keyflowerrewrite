CREATE TABLE IF NOT EXISTS `pieces` (
  id int unsigned NOT NULL,

  -- 'tile', 'worker', 'resource', 'skill', 'startplayer'
  piece_type varchar(12) NOT NULL,

  -- tile: the tile name, lowercase no spaces: 'home4', 'keymine', 'wainwright', 'boat4a'
  -- worker: the worker color: 'yellow', 'red', 'green', 'blue'
  -- resource: 'stone', 'wood', 'iron', 'gold'
  piece_subtype varchar(20),

  loc_type varchar(12) NOT NULL,
  loc_id int,
  loc_pos int,
  player_id int,

  PRIMARY KEY(`id`)

  -- TODO: consider this constraint ... may have to have non-null
  -- loc_id / loc_pos / player_id, using 0 to mean "unset"
  -- It will also require specifying "positions" or a "loc_id" for
  -- locations like "box", and means that skills in the skill pile
  --   and resources in the resource stocks also need "positions" or "loc_id"s.
  -- UNIQUE(`loc_type`, `loc_id`, `loc_pos`, `player_id`),
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
