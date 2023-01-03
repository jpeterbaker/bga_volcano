
-- ------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- Volcano implementation : © Jonathan Baker <babamots@gmail.com>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-- -----

-- dbmodel.sql

CREATE TABLE IF NOT EXISTS `Pieces` (
     `piece_id` int NOT NULL PRIMARY KEY AUTO_INCREMENT,
     -- Piece color (0 for a cap)
     `color` tinyint UNSIGNED NOT NULL,
     -- Piece size (1-3)
     `pips` tinyint UNSIGNED NOT NULL,
     -- Position coordinates (0-4 or 5, null if captured)
     `x` tinyint UNSIGNED DEFAULT NULL,
     `y` tinyint UNSIGNED DEFAULT NULL,
     -- Stack position (0 on bottom, null if captured)
     `z` tinyint UNSIGNED DEFAULT NULL,
     -- Owning player (null piece is on the board)
     -- Corresponds to player.player_id
     `owner_id` int UNSIGNED DEFAULT NULL,
     -- Saved values for reverting to start of turn
     `saved_x` tinyint UNSIGNED DEFAULT NULL,
     `saved_y` tinyint UNSIGNED DEFAULT NULL,
     `saved_z` tinyint UNSIGNED DEFAULT NULL,
     `saved_owner_id` int UNSIGNED DEFAULT NULL
) ENGINE=InnoDB;

