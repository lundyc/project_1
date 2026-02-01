CREATE TABLE `derived_stats` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `match_id` bigint(20) UNSIGNED NOT NULL,
  `events_version_used` bigint(20) UNSIGNED NOT NULL,
  `computed_at` datetime NOT NULL DEFAULT current_timestamp(),
  `payload_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload_json`)),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_stats_match_version` (`match_id`,`events_version_used`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
