-- Insert remaining data for match_id=19
SET FOREIGN_KEY_CHECKS=0;

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
INSERT INTO `playlists` (`id`, `match_id`, `title`, `notes`, `created_at`, `updated_at`, `deleted_at`) VALUES
(5, 19, 'Goals', NULL, '2026-01-17 20:59:19', NULL, NULL),
(6, 19, 'Corners', NULL, '2026-01-18 01:55:48', NULL, NULL),
(7, 19, 'Funny', NULL, '2026-01-18 01:56:12', NULL, NULL);

SET FOREIGN_KEY_CHECKS=1;

SELECT 'Remaining data inserted successfully!' AS status;
