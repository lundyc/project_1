-- Insert missing data for match_id=19
SET FOREIGN_KEY_CHECKS=0;

-- match_players table
INSERT INTO `match_players` (`id`, `match_id`, `team_side`, `player_id`, `display_name`, `shirt_number`, `position_label`, `is_starting`, `created_at`, `is_captain`) VALUES
(59, 19, 'away', 1, '', 1, 'GK', 1, '2026-01-17 21:02:50', 0),
(60, 19, 'away', 3, '', 2, 'CB', 1, '2026-01-17 21:03:21', 0),
(61, 19, 'away', 4, '', 3, 'CB', 1, '2026-01-17 21:03:28', 0),
(62, 19, 'away', 15, '', 4, 'CB', 1, '2026-01-17 21:03:37', 0),
(63, 19, 'away', 20, '', 5, 'LM', 1, '2026-01-17 21:04:10', 0),
(64, 19, 'away', 21, '', 6, 'CM', 1, '2026-01-17 21:04:45', 1),
(65, 19, 'away', 7, '', 7, 'CM', 1, '2026-01-17 21:04:57', 0),
(66, 19, 'away', 9, '', 8, 'CM', 1, '2026-01-17 21:05:11', 0),
(67, 19, 'away', 22, '', 9, 'RM', 1, '2026-01-17 21:05:47', 0),
(68, 19, 'away', 10, '', 10, 'ST', 1, '2026-01-17 21:05:57', 0),
(69, 19, 'away', 23, '', 11, 'ST', 1, '2026-01-17 21:06:27', 0),
(70, 19, 'away', 12, '', NULL, NULL, 0, '2026-01-17 21:06:40', 0),
(71, 19, 'away', 17, '', NULL, NULL, 0, '2026-01-17 21:06:43', 0),
(72, 19, 'away', 6, '', NULL, NULL, 0, '2026-01-17 21:06:48', 0),
(73, 19, 'away', 18, '', NULL, NULL, 0, '2026-01-17 21:08:20', 0);

-- match_formations table
INSERT INTO `match_formations` (`id`, `match_id`, `team_side`, `formation_id`, `created_at`) VALUES
(3, 19, 'away', 3, '2026-01-17 21:07:08'),
(4, 19, 'home', 3, '2026-01-17 22:44:23');

-- match_periods table
INSERT INTO `match_periods` (`id`, `match_id`, `period_id`, `start_second`, `end_second`, `created_at`) VALUES
(5, 19, 3, 14, 2710, '2026-01-17 22:42:44'),
(6, 19, 4, 3526, 6241, '2026-01-17 22:42:44');

-- match_videos table
INSERT INTO `match_videos` (`id`, `match_id`, `video_label`, `source_type`, `source_path`, `uploaded_path`, `fps`, `duration_seconds`, `uploaded_filename`, `metadata_json`, `created_at`, `updated_at`) VALUES
(3, 19, NULL, 'veo', 'videos/matches/match_19/source/veo', NULL, 50, 6243, NULL, NULL, '2026-01-17 20:44:30', '2026-01-19 17:51:52');

-- playlists table
INSERT INTO `playlists` (`id`, `match_id`, `name`, `created_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 19, 'Goals', 1, '2026-01-17 22:43:25', NULL, NULL),
(2, 19, 'Chances & Near Misses', 1, '2026-01-17 22:43:38', NULL, NULL),
(3, 19, 'Test', 1, '2026-01-18 03:22:12', NULL, NULL);

SET FOREIGN_KEY_CHECKS=1;

SELECT 'Missing data inserted successfully' AS status;
