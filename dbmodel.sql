-- ------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> &
-- Emmanuel Colin <ecolin@boardgamearena.com>
-- Texas 42 implementation : © Stardust Spikes <sdspikes@cs.stanford.edu> &
-- Jason Turner-Maier <jasonptm@gmail.com> &
-- Ilya Sherman <ishermandom@gmail.com>
--
-- This code has been produced on the BGA studio platform for use on
-- http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-- -----

-- dbmodel.sql
-- Docs: https://en.doc.boardgamearena.com/Game_database_model:_dbmodel.sql
-- Note that the database itself and the standard tables ("global", "stats",
-- "gamelog" and "player") are already created and must not be created here.

-- Note: The database schema is created from this file when the game starts.
-- If you modify this file, you have to restart a game to see your changes.

-- TODO(isherman): Do we need this? Probably...
-- add info about first player
ALTER TABLE `player` ADD `player_first` BOOLEAN NOT NULL DEFAULT '0';

-- Note: The base schema is defined by https://en.doc.boardgamearena.com/Deck.
CREATE TABLE IF NOT EXISTS `dominoes` (
  `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  -- The fields `card_type` and `card_type_arg` are unused, but are required to
  -- exist due to the base schema. 42 instead uses `high` and `low` (below) to
  -- represent the number of pips.
  `card_type` varchar(16) NOT NULL,
  `card_type_arg` int(11) NOT NULL,
  `card_location` varchar(16) NOT NULL,
  `card_location_arg` int(11) NOT NULL,
  -- The number of pips on the higher and lower ends of the domino.
  `high` tinyint(1) NOT NULL,
  `low` tinyint(1) NOT NULL,
  PRIMARY KEY (`card_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
