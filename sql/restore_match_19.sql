-- Restore match_id=3 as match_id=19
SET FOREIGN_KEY_CHECKS=0;

-- matches table
INSERT INTO `matches` (`id`, `club_id`, `season_id`, `competition_id`, `home_team_id`, `away_team_id`, `kickoff_at`, `match_video`, `venue`, `referee`, `attendance`, `status`, `notes`, `events_version`, `clips_version`, `derived_version`, `created_by`, `created_at`, `updated_at`) VALUES
(19, 1, 1, 2, 2, 4, '2026-01-17 13:30:00', NULL, NULL, NULL, NULL, 'ready', NULL, 77, 0, 77, 1, '2026-01-17 20:37:29', '2026-01-19 15:05:12'),

-- events table
INSERT INTO `events` (`id`, `match_id`, `period_id`, `match_second`, `minute`, `minute_extra`, `team_side`, `event_type_id`, `importance`, `phase`, `match_player_id`, `player_id`, `opponent_detail`, `outcome`, `zone`, `notes`, `created_by`, `created_at`, `updated_by`, `updated_at`, `match_period_id`, `clip_id`, `clip_start_second`, `clip_end_second`) VALUES

-- clips table
INSERT INTO `clips` (`id`, `match_id`, `event_id`, `clip_id`, `clip_name`, `start_second`, `end_second`, `duration_seconds`, `created_by`, `created_at`, `updated_by`, `updated_at`, `generation_source`, `generation_version`, `is_valid`, `deleted_at`) VALUES

-- clip_jobs table
-- No clip jobs found

-- match_players table
INSERT INTO `match_players` (`id`, `match_id`, `player_id`, `team_side`, `shirt_number`, `position`, `is_starting`, `created_by`, `created_at`, `updated_at`) VALUES

-- match_formations table
INSERT INTO `match_formations` (`id`, `match_id`, `team_side`, `formation_id`, `created_at`) VALUES

-- match_periods table
INSERT INTO `match_periods` (`id`, `match_id`, `period_id`, `start_second`, `end_second`, `created_at`) VALUES

-- match_videos table
INSERT INTO `match_videos` (`id`, `match_id`, `video_label`, `source_type`, `source_path`, `uploaded_path`, `fps`, `duration_seconds`, `uploaded_filename`, `metadata_json`, `created_at`, `updated_at`) VALUES

-- derived_stats table
INSERT INTO `derived_stats` (`id`, `match_id`, `team_side`, `passes`, `tackles`, `shots_on_target`, `shots_off_target`, `corners`, `fouls_committed`, `chances`, `offsides`, `saves`, `blocks`, `interceptions`, `clearances`, `yellow_cards`, `red_cards`, `possession_percentage`, `created_at`, `updated_at`) VALUES

-- playlists table
INSERT INTO `playlists` (`id`, `match_id`, `name`, `created_by`, `created_at`, `updated_at`, `deleted_at`) VALUES

-- match_locks table
-- No active match locks

SET FOREIGN_KEY_CHECKS=1;
