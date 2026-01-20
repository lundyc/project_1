-- Competition/Season structure update
-- 1) Add competition type (league|cup)
-- 2) Require season_id on competitions
-- 3) Add competition_teams membership table

-- Add type column with default 'cup'
ALTER TABLE competitions
  ADD COLUMN `type` varchar(20) NOT NULL DEFAULT 'cup' AFTER `name`;

-- Backfill types
UPDATE competitions SET `type` = 'league' WHERE name = 'Fourth Division';
UPDATE competitions SET `type` = 'cup' WHERE `type` NOT IN ('league','cup');

-- Backfill season_id using the earliest season per club when missing
UPDATE competitions c
JOIN (
  SELECT club_id, MIN(id) AS season_id
  FROM seasons
  GROUP BY club_id
) s ON s.club_id = c.club_id
SET c.season_id = s.season_id
WHERE c.season_id IS NULL;

-- Make season_id required
ALTER TABLE competitions
  MODIFY `season_id` bigint(20) unsigned NOT NULL;

-- Competition ↔ teams membership
CREATE TABLE IF NOT EXISTS competition_teams (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `competition_id` bigint(20) unsigned NOT NULL,
  `team_id` bigint(20) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_comp_team` (`competition_id`,`team_id`),
  KEY `idx_competition` (`competition_id`),
  KEY `idx_team` (`team_id`),
  CONSTRAINT `fk_competition_teams_comp` FOREIGN KEY (`competition_id`) REFERENCES `competitions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_competition_teams_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
