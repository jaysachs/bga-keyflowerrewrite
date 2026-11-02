CREATE TABLE IF NOT EXISTS `pieces` (

  -- 'tile', 'worker', 'resource', 'skill'
  piece_type varchar(10) NOT NULL,

  id int unsigned NOT NULL AUTO_INCREMENT,

  -- tile: the tile name, lowercase no spaces: 'home4', 'keymine', 'wainwright', 'boat4a'
  -- worker: the worker color: 'yellow', 'red', 'green', 'blue', 'purple'
  -- resource: 'stone', 'wood', 'ore', 'gold'
  piece_subtype varchar(10),

  loc_type varchar(10) NOT NULL,
  loc_id: int,
  loc_pos: int,
  player_id int,

  -- only for village tiles and standard boats; can we encode this somewhere else?
  upgraded: tinyint,
  PRIMARY KEY(`piece_type`, `id`),
  -- TODO: consider this constraint ... may have to have non-null
  -- loc_id / loc_pos / player_id, using 0 to mean "unset"
  UNIQUE(`loc_type`, `loc_id`, `loc_pos`, `player_id`),
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
