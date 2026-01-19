-- Add canonical formation definitions and tie matches to their selected formation.

CREATE TABLE `formations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `format` VARCHAR(32) NOT NULL,
  `formation_key` VARCHAR(64) NOT NULL,
  `label` VARCHAR(128) NOT NULL,
  `player_count` INT UNSIGNED NOT NULL,
  `is_fixed` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_formations_format_key` (`format`, `formation_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `formation_positions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `formation_id` BIGINT UNSIGNED NOT NULL,
  `slot_index` INT UNSIGNED NOT NULL,
  `position_label` VARCHAR(40) NOT NULL,
  `left_percent` DECIMAL(5,4) NOT NULL,
  `bottom_percent` DECIMAL(5,4) NOT NULL,
  `rotation_deg` SMALLINT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_formation_positions_slot` (`formation_id`, `slot_index`),
  KEY `idx_formation_positions_formation` (`formation_id`),
  CONSTRAINT `fk_formation_positions_formation` FOREIGN KEY (`formation_id`) REFERENCES `formations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `matches`
  ADD COLUMN `home_formation_id` BIGINT UNSIGNED DEFAULT NULL,
  ADD COLUMN `away_formation_id` BIGINT UNSIGNED DEFAULT NULL,
  ADD KEY `idx_matches_home_formation` (`home_formation_id`),
  ADD KEY `idx_matches_away_formation` (`away_formation_id`),
  ADD CONSTRAINT `fk_matches_home_formation` FOREIGN KEY (`home_formation_id`) REFERENCES `formations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_matches_away_formation` FOREIGN KEY (`away_formation_id`) REFERENCES `formations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
