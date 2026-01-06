-- Phase 1 – Add playlists, playlist clips, and annotations structures.

CREATE TABLE `playlists` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `match_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(160) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_playlists_match` (`match_id`),
  CONSTRAINT `fk_playlists_match` FOREIGN KEY (`match_id`) REFERENCES `matches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `playlist_clips` (
  `playlist_id` bigint(20) UNSIGNED NOT NULL,
  `clip_id` bigint(20) UNSIGNED NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`playlist_id`,`clip_id`),
  KEY `idx_playlist_clips_clip` (`clip_id`),
  CONSTRAINT `fk_playlist_clips_playlist` FOREIGN KEY (`playlist_id`) REFERENCES `playlists` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_playlist_clips_clip` FOREIGN KEY (`clip_id`) REFERENCES `clips` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `annotations` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `match_id` bigint(20) UNSIGNED NOT NULL,
  `target_type` enum('match_video','clip') NOT NULL,
  `target_id` bigint(20) UNSIGNED NOT NULL,
  `timestamp_second` int(11) UNSIGNED NOT NULL,
  `tool_type` varchar(32) NOT NULL DEFAULT 'line',
  `drawing_data` json DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_annotations_match` (`match_id`),
  KEY `idx_annotations_target` (`target_type`,`target_id`),
  CONSTRAINT `fk_annotations_match` FOREIGN KEY (`match_id`) REFERENCES `matches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CHECK (`drawing_data` IS NULL OR json_valid(`drawing_data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
