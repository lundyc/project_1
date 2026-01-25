-- League Intelligence match store
-- Run this script against the project_1 database to ensure the analytics match store exists.

CREATE TABLE IF NOT EXISTS `league_intelligence_matches` (
  `match_id` BIGINT UNSIGNED NOT NULL,
  `competition_id` BIGINT UNSIGNED,
  `season_id` BIGINT UNSIGNED,
  `home_team_id` BIGINT UNSIGNED,
  `away_team_id` BIGINT UNSIGNED,
  `kickoff_at` DATETIME,
  `home_goals` SMALLINT UNSIGNED,
  `away_goals` SMALLINT UNSIGNED,
  `status` VARCHAR(32) NOT NULL DEFAULT 'scheduled',
  `neutral_location` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT current_timestamp(),
  `updated_at` DATETIME DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`match_id`),
  KEY `idx_li_competition` (`competition_id`),
  KEY `idx_li_season` (`season_id`),
  KEY `idx_li_home_team` (`home_team_id`),
  KEY `idx_li_away_team` (`away_team_id`),
  KEY `idx_li_kickoff` (`kickoff_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
