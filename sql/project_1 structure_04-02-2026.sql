-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Feb 02, 2026 at 07:30 AM
-- Server version: 10.11.14-MariaDB-0ubuntu0.24.04.1
-- PHP Version: 8.4.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `project_1`
--

-- --------------------------------------------------------

--
-- Table structure for table `annotations`
--

CREATE TABLE `annotations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `match_id` bigint(20) UNSIGNED NOT NULL,
  `target_type` enum('match_video','clip') NOT NULL,
  `target_id` bigint(20) UNSIGNED NOT NULL,
  `timestamp_second` int(11) UNSIGNED NOT NULL,
  `tool_type` varchar(32) NOT NULL,
  `drawing_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`drawing_data`)),
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `club_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `entity_type` varchar(40) NOT NULL,
  `entity_id` bigint(20) UNSIGNED NOT NULL,
  `action` varchar(20) NOT NULL,
  `before_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`before_json`)),
  `after_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`after_json`)),
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clips`
--

CREATE TABLE `clips` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `match_id` bigint(20) UNSIGNED NOT NULL,
  `event_id` bigint(20) UNSIGNED NOT NULL,
  `clip_id` int(11) NOT NULL,
  `clip_name` varchar(120) NOT NULL,
  `start_second` int(11) NOT NULL,
  `end_second` int(11) NOT NULL,
  `duration_seconds` int(11) NOT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `generation_source` enum('manual','event_auto','ai_suggested') NOT NULL DEFAULT 'event_auto',
  `generation_version` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `is_valid` tinyint(1) NOT NULL DEFAULT 1,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clip_jobs`
--

CREATE TABLE `clip_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `match_id` bigint(20) UNSIGNED NOT NULL,
  `event_id` bigint(20) UNSIGNED DEFAULT NULL,
  `clip_id` bigint(20) UNSIGNED DEFAULT NULL,
  `status` enum('pending','processing','completed','failed') NOT NULL DEFAULT 'pending',
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload`)),
  `error_message` varchar(255) DEFAULT NULL,
  `completed_note` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clip_reviews`
--

CREATE TABLE `clip_reviews` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `clip_id` bigint(20) UNSIGNED NOT NULL,
  `reviewed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clubs`
--

CREATE TABLE `clubs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `competitions`
--

CREATE TABLE `competitions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `club_id` bigint(20) UNSIGNED NOT NULL,
  `season_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `type` varchar(20) NOT NULL DEFAULT 'cup',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `competition_teams`
--

CREATE TABLE `competition_teams` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `competition_id` bigint(20) UNSIGNED NOT NULL,
  `team_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `derived_stats`
--

CREATE TABLE `derived_stats` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `match_id` bigint(20) UNSIGNED NOT NULL,
  `events_version_used` bigint(20) UNSIGNED NOT NULL,
  `computed_at` datetime NOT NULL DEFAULT current_timestamp(),
  `payload_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload_json`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `match_id` bigint(20) UNSIGNED NOT NULL,
  `period_id` bigint(20) UNSIGNED DEFAULT NULL,
  `match_second` int(11) NOT NULL,
  `minute` int(11) NOT NULL,
  `minute_extra` int(11) NOT NULL DEFAULT 0,
  `team_side` enum('home','away','unknown') NOT NULL DEFAULT 'unknown',
  `event_type_id` bigint(20) UNSIGNED NOT NULL,
  `importance` tinyint(3) UNSIGNED NOT NULL DEFAULT 2,
  `phase` enum('unknown','build_up','transition','defensive_block','set_piece') NOT NULL DEFAULT 'unknown',
  `is_penalty` tinyint(1) DEFAULT 0,
  `match_player_id` bigint(20) UNSIGNED DEFAULT NULL,
  `player_id` bigint(20) UNSIGNED DEFAULT NULL,
  `opponent_detail` varchar(120) DEFAULT NULL,
  `outcome` varchar(60) DEFAULT NULL,
  `zone` varchar(60) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `match_period_id` bigint(20) UNSIGNED DEFAULT NULL,
  `clip_id` bigint(20) UNSIGNED DEFAULT NULL,
  `clip_start_second` int(11) DEFAULT NULL,
  `clip_end_second` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `event_snapshots`
--

CREATE TABLE `event_snapshots` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `event_id` bigint(20) UNSIGNED NOT NULL,
  `match_id` bigint(20) UNSIGNED NOT NULL,
  `snapshot_json` longtext NOT NULL CHECK (json_valid(`snapshot_json`)),
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `event_suggestions`
--

CREATE TABLE `event_suggestions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `match_id` bigint(20) UNSIGNED NOT NULL,
  `suggested_event_type_id` bigint(20) UNSIGNED NOT NULL,
  `suggested_match_second` int(11) NOT NULL,
  `confidence` decimal(5,2) NOT NULL,
  `source` enum('video','audio','model') NOT NULL,
  `status` enum('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `reviewed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `event_tags`
--

CREATE TABLE `event_tags` (
  `event_id` bigint(20) UNSIGNED NOT NULL,
  `tag_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `event_types`
--

CREATE TABLE `event_types` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `club_id` bigint(20) UNSIGNED NOT NULL,
  `type_key` varchar(50) NOT NULL,
  `label` varchar(80) NOT NULL,
  `default_importance` tinyint(3) UNSIGNED NOT NULL DEFAULT 2,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `formations`
--

CREATE TABLE `formations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `format` enum('11-a-side','9-a-side','8-a-side','7-a-side','5-a-side','other') NOT NULL,
  `formation_key` varchar(32) NOT NULL,
  `label` varchar(64) NOT NULL,
  `player_count` tinyint(3) UNSIGNED NOT NULL,
  `is_fixed` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `formation_positions`
--

CREATE TABLE `formation_positions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `formation_id` bigint(20) UNSIGNED NOT NULL,
  `slot_index` tinyint(3) UNSIGNED NOT NULL,
  `position_label` varchar(8) NOT NULL,
  `left_percent` decimal(6,3) NOT NULL,
  `bottom_percent` decimal(6,3) NOT NULL,
  `rotation_deg` smallint(6) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `keyboard_profiles`
--

CREATE TABLE `keyboard_profiles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(80) NOT NULL DEFAULT 'Default',
  `is_default` tinyint(1) NOT NULL DEFAULT 1,
  `bindings_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`bindings_json`)),
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `league_intelligence_matches`
--

CREATE TABLE `league_intelligence_matches` (
  `match_id` bigint(20) UNSIGNED NOT NULL,
  `competition_id` bigint(20) UNSIGNED DEFAULT NULL,
  `season_id` bigint(20) UNSIGNED DEFAULT NULL,
  `home_team_id` bigint(20) UNSIGNED DEFAULT NULL,
  `away_team_id` bigint(20) UNSIGNED DEFAULT NULL,
  `kickoff_at` datetime DEFAULT NULL,
  `home_goals` smallint(5) UNSIGNED DEFAULT NULL,
  `away_goals` smallint(5) UNSIGNED DEFAULT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'scheduled',
  `neutral_location` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `matches`
--

CREATE TABLE `matches` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `club_id` bigint(20) UNSIGNED NOT NULL,
  `season_id` bigint(20) UNSIGNED DEFAULT NULL,
  `competition_id` bigint(20) UNSIGNED DEFAULT NULL,
  `home_team_id` bigint(20) UNSIGNED NOT NULL,
  `away_team_id` bigint(20) UNSIGNED NOT NULL,
  `kickoff_at` datetime DEFAULT NULL,
  `match_video` varchar(255) DEFAULT NULL,
  `venue` varchar(160) DEFAULT NULL,
  `referee` varchar(120) DEFAULT NULL,
  `attendance` int(11) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `events_version` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `clips_version` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `derived_version` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `match_formations`
--

CREATE TABLE `match_formations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `match_id` bigint(20) UNSIGNED NOT NULL,
  `team_side` enum('home','away') NOT NULL,
  `match_period_id` bigint(20) UNSIGNED DEFAULT NULL,
  `match_second` int(11) NOT NULL,
  `minute` int(11) NOT NULL,
  `minute_extra` int(11) NOT NULL DEFAULT 0,
  `format` enum('11-a-side','9-a-side','8-a-side','7-a-side','5-a-side','other') NOT NULL,
  `formation_key` varchar(32) NOT NULL,
  `layout_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`layout_json`)),
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `match_locks`
--

CREATE TABLE `match_locks` (
  `match_id` bigint(20) UNSIGNED NOT NULL,
  `locked_by` bigint(20) UNSIGNED NOT NULL,
  `locked_at` datetime NOT NULL DEFAULT current_timestamp(),
  `last_heartbeat_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `match_periods`
--

CREATE TABLE `match_periods` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `match_id` bigint(20) UNSIGNED NOT NULL,
  `period_key` varchar(32) NOT NULL,
  `label` varchar(64) NOT NULL,
  `start_second` int(11) NOT NULL,
  `end_second` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `match_players`
--

CREATE TABLE `match_players` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `match_id` bigint(20) UNSIGNED NOT NULL,
  `team_side` enum('home','away') NOT NULL,
  `player_id` bigint(20) UNSIGNED DEFAULT NULL,
  `shirt_number` int(11) DEFAULT NULL,
  `position_label` varchar(40) DEFAULT NULL,
  `is_starting` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `is_captain` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `match_sessions`
--

CREATE TABLE `match_sessions` (
  `match_id` int(11) NOT NULL,
  `playing` tinyint(1) NOT NULL DEFAULT 0,
  `base_time_seconds` double NOT NULL DEFAULT 0,
  `playback_rate` double NOT NULL DEFAULT 1,
  `updated_at_ms` bigint(20) NOT NULL,
  `control_owner_user_id` int(11) DEFAULT NULL,
  `control_owner_name` varchar(255) DEFAULT NULL,
  `control_owner_socket_id` varchar(128) DEFAULT NULL,
  `control_expires_at_ms` bigint(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `match_substitutions`
--

CREATE TABLE `match_substitutions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `match_id` bigint(20) UNSIGNED NOT NULL,
  `team_side` enum('home','away') NOT NULL,
  `match_period_id` bigint(20) UNSIGNED DEFAULT NULL,
  `match_second` int(11) NOT NULL,
  `minute` int(11) NOT NULL,
  `minute_extra` int(11) NOT NULL DEFAULT 0,
  `player_off_match_player_id` bigint(20) UNSIGNED NOT NULL,
  `player_on_match_player_id` bigint(20) UNSIGNED NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `event_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `match_videos`
--

CREATE TABLE `match_videos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `match_id` bigint(20) UNSIGNED NOT NULL,
  `source_path` varchar(255) NOT NULL,
  `thumbnail_path` varchar(255) DEFAULT NULL,
  `duration_seconds` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `source_type` enum('upload','veo') NOT NULL DEFAULT 'upload',
  `source_url` text DEFAULT NULL,
  `download_status` enum('pending','downloading','completed','failed') NOT NULL DEFAULT 'pending',
  `download_progress` tinyint(3) UNSIGNED DEFAULT 0,
  `error_message` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `players`
--

CREATE TABLE `players` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `club_id` bigint(20) UNSIGNED NOT NULL,
  `team_id` bigint(20) UNSIGNED DEFAULT NULL,
  `first_name` varchar(80) DEFAULT NULL,
  `last_name` varchar(80) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `primary_position` varchar(40) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `players_display_name_archive`
--

CREATE TABLE `players_display_name_archive` (
  `id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `club_id` bigint(20) UNSIGNED NOT NULL,
  `display_name` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `archived_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `player_team_season`
--

CREATE TABLE `player_team_season` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `club_id` bigint(20) UNSIGNED NOT NULL,
  `player_id` bigint(20) UNSIGNED NOT NULL,
  `team_id` bigint(20) UNSIGNED NOT NULL,
  `season_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `playlists`
--

CREATE TABLE `playlists` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `match_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(160) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `playlist_clips`
--

CREATE TABLE `playlist_clips` (
  `playlist_id` bigint(20) UNSIGNED NOT NULL,
  `clip_id` bigint(20) UNSIGNED NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `role_key` varchar(40) NOT NULL,
  `label` varchar(80) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seasons`
--

CREATE TABLE `seasons` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `club_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(80) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stat_overrides`
--

CREATE TABLE `stat_overrides` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `match_id` bigint(20) UNSIGNED NOT NULL,
  `key_name` varchar(60) NOT NULL,
  `value_num` decimal(10,2) DEFAULT NULL,
  `value_text` varchar(200) DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tags`
--

CREATE TABLE `tags` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `club_id` bigint(20) UNSIGNED NOT NULL,
  `tag_key` varchar(60) NOT NULL,
  `label` varchar(80) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teams`
--

CREATE TABLE `teams` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `club_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `team_type` varchar(30) NOT NULL DEFAULT 'club',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `club_id` bigint(20) UNSIGNED DEFAULT NULL,
  `email` varchar(160) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `display_name` varchar(120) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `annotations`
--
ALTER TABLE `annotations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_annotations_match` (`match_id`),
  ADD KEY `idx_annotations_target` (`target_type`,`target_id`);

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_club` (`club_id`),
  ADD KEY `idx_audit_user` (`user_id`),
  ADD KEY `idx_audit_entity` (`entity_type`,`entity_id`),
  ADD KEY `idx_audit_created` (`created_at`);

--
-- Indexes for table `clips`
--
ALTER TABLE `clips`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_clips_match` (`match_id`),
  ADD KEY `idx_clips_event` (`event_id`),
  ADD KEY `fk_clips_created_by` (`created_by`),
  ADD KEY `fk_clips_updated_by` (`updated_by`);

--
-- Indexes for table `clip_jobs`
--
ALTER TABLE `clip_jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_clip_jobs_status` (`status`),
  ADD KEY `idx_clip_jobs_match` (`match_id`),
  ADD KEY `idx_clip_jobs_event` (`event_id`),
  ADD KEY `fk_clip_jobs_clip` (`clip_id`);

--
-- Indexes for table `clip_reviews`
--
ALTER TABLE `clip_reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_clip_review` (`clip_id`),
  ADD KEY `fk_review_user` (`reviewed_by`);

--
-- Indexes for table `clubs`
--
ALTER TABLE `clubs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_clubs_name` (`name`);

--
-- Indexes for table `competitions`
--
ALTER TABLE `competitions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_comp_club` (`club_id`),
  ADD KEY `idx_comp_season` (`season_id`);

--
-- Indexes for table `competition_teams`
--
ALTER TABLE `competition_teams`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_comp_team` (`competition_id`,`team_id`),
  ADD KEY `idx_competition` (`competition_id`),
  ADD KEY `idx_team` (`team_id`);

--
-- Indexes for table `derived_stats`
--
ALTER TABLE `derived_stats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_stats_match_version` (`match_id`,`events_version_used`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_events_match` (`match_id`),
  ADD KEY `idx_events_period` (`period_id`),
  ADD KEY `idx_events_type` (`event_type_id`),
  ADD KEY `idx_events_time` (`match_second`),
  ADD KEY `idx_events_team` (`team_side`),
  ADD KEY `idx_events_match_player` (`match_player_id`),
  ADD KEY `idx_events_player` (`player_id`),
  ADD KEY `fk_events_created_by` (`created_by`),
  ADD KEY `fk_events_updated_by` (`updated_by`),
  ADD KEY `idx_events_match_period` (`match_period_id`);

--
-- Indexes for table `event_snapshots`
--
ALTER TABLE `event_snapshots`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_event_snapshot` (`event_id`),
  ADD KEY `fk_snapshot_match` (`match_id`);

--
-- Indexes for table `event_suggestions`
--
ALTER TABLE `event_suggestions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_suggestions_match` (`match_id`),
  ADD KEY `fk_suggestion_event_type` (`suggested_event_type_id`),
  ADD KEY `fk_suggestion_user` (`reviewed_by`);

--
-- Indexes for table `event_tags`
--
ALTER TABLE `event_tags`
  ADD PRIMARY KEY (`event_id`,`tag_id`),
  ADD KEY `idx_event_tags_tag` (`tag_id`);

--
-- Indexes for table `event_types`
--
ALTER TABLE `event_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_event_types_key` (`club_id`,`type_key`),
  ADD KEY `idx_event_types_club` (`club_id`);

--
-- Indexes for table `formations`
--
ALTER TABLE `formations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_formations_format_key` (`format`,`formation_key`);

--
-- Indexes for table `formation_positions`
--
ALTER TABLE `formation_positions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_formation_slot` (`formation_id`,`slot_index`);

--
-- Indexes for table `keyboard_profiles`
--
ALTER TABLE `keyboard_profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_kb_user` (`user_id`);

--
-- Indexes for table `league_intelligence_matches`
--
ALTER TABLE `league_intelligence_matches`
  ADD PRIMARY KEY (`match_id`),
  ADD KEY `idx_li_competition` (`competition_id`),
  ADD KEY `idx_li_season` (`season_id`),
  ADD KEY `idx_li_home_team` (`home_team_id`),
  ADD KEY `idx_li_away_team` (`away_team_id`),
  ADD KEY `idx_li_kickoff` (`kickoff_at`);

--
-- Indexes for table `matches`
--
ALTER TABLE `matches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_matches_club` (`club_id`),
  ADD KEY `idx_matches_season` (`season_id`),
  ADD KEY `idx_matches_comp` (`competition_id`),
  ADD KEY `idx_matches_home` (`home_team_id`),
  ADD KEY `idx_matches_away` (`away_team_id`),
  ADD KEY `fk_matches_created_by` (`created_by`);

--
-- Indexes for table `match_formations`
--
ALTER TABLE `match_formations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_match_team` (`match_id`,`team_side`);

--
-- Indexes for table `match_locks`
--
ALTER TABLE `match_locks`
  ADD PRIMARY KEY (`match_id`),
  ADD KEY `idx_locks_user` (`locked_by`),
  ADD KEY `idx_locks_heartbeat` (`last_heartbeat_at`);

--
-- Indexes for table `match_periods`
--
ALTER TABLE `match_periods`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_match_periods_match` (`match_id`),
  ADD KEY `idx_match_periods_match_key` (`match_id`,`period_key`);

--
-- Indexes for table `match_players`
--
ALTER TABLE `match_players`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mp_match` (`match_id`),
  ADD KEY `idx_mp_player` (`player_id`),
  ADD KEY `idx_mp_side` (`team_side`);

--
-- Indexes for table `match_sessions`
--
ALTER TABLE `match_sessions`
  ADD PRIMARY KEY (`match_id`);

--
-- Indexes for table `match_substitutions`
--
ALTER TABLE `match_substitutions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_subs_match` (`match_id`),
  ADD KEY `idx_subs_team` (`team_side`),
  ADD KEY `idx_subs_time` (`match_second`),
  ADD KEY `idx_subs_player_off` (`player_off_match_player_id`),
  ADD KEY `idx_subs_player_on` (`player_on_match_player_id`),
  ADD KEY `fk_subs_period` (`match_period_id`),
  ADD KEY `fk_subs_event` (`event_id`),
  ADD KEY `fk_subs_created_by` (`created_by`);

--
-- Indexes for table `match_videos`
--
ALTER TABLE `match_videos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mv_match` (`match_id`);

--
-- Indexes for table `players`
--
ALTER TABLE `players`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_players_club` (`club_id`),
  ADD KEY `idx_players_team` (`team_id`),
  ADD KEY `idx_players_active` (`is_active`),
  ADD KEY `idx_first_name` (`first_name`),
  ADD KEY `idx_last_name` (`last_name`),
  ADD KEY `idx_first_last` (`first_name`,`last_name`),
  ADD KEY `idx_club_active` (`club_id`,`is_active`);

--
-- Indexes for table `player_team_season`
--
ALTER TABLE `player_team_season`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_pts_unique` (`club_id`,`player_id`,`team_id`,`season_id`),
  ADD KEY `idx_pts_club` (`club_id`),
  ADD KEY `idx_pts_player` (`player_id`),
  ADD KEY `idx_pts_team` (`team_id`),
  ADD KEY `idx_pts_season` (`season_id`);

--
-- Indexes for table `playlists`
--
ALTER TABLE `playlists`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_playlists_match` (`match_id`);

--
-- Indexes for table `playlist_clips`
--
ALTER TABLE `playlist_clips`
  ADD PRIMARY KEY (`playlist_id`,`clip_id`),
  ADD KEY `idx_playlist_clips_clip` (`clip_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_roles_key` (`role_key`);

--
-- Indexes for table `seasons`
--
ALTER TABLE `seasons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_seasons_club` (`club_id`);

--
-- Indexes for table `stat_overrides`
--
ALTER TABLE `stat_overrides`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_overrides_match` (`match_id`),
  ADD KEY `idx_overrides_key` (`key_name`),
  ADD KEY `fk_override_user` (`created_by`);

--
-- Indexes for table `tags`
--
ALTER TABLE `tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_tags_key` (`club_id`,`tag_key`),
  ADD KEY `idx_tags_club` (`club_id`);

--
-- Indexes for table `teams`
--
ALTER TABLE `teams`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_teams_club` (`club_id`),
  ADD KEY `idx_teams_type` (`team_type`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_email` (`email`),
  ADD KEY `idx_users_club` (`club_id`);

--
-- Indexes for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`user_id`,`role_id`),
  ADD KEY `idx_user_roles_role` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `annotations`
--
ALTER TABLE `annotations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clips`
--
ALTER TABLE `clips`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clip_jobs`
--
ALTER TABLE `clip_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clip_reviews`
--
ALTER TABLE `clip_reviews`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clubs`
--
ALTER TABLE `clubs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `competitions`
--
ALTER TABLE `competitions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `competition_teams`
--
ALTER TABLE `competition_teams`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `derived_stats`
--
ALTER TABLE `derived_stats`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `event_snapshots`
--
ALTER TABLE `event_snapshots`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `event_suggestions`
--
ALTER TABLE `event_suggestions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `event_types`
--
ALTER TABLE `event_types`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `formations`
--
ALTER TABLE `formations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `formation_positions`
--
ALTER TABLE `formation_positions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `keyboard_profiles`
--
ALTER TABLE `keyboard_profiles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `matches`
--
ALTER TABLE `matches`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `match_formations`
--
ALTER TABLE `match_formations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `match_periods`
--
ALTER TABLE `match_periods`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `match_players`
--
ALTER TABLE `match_players`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `match_substitutions`
--
ALTER TABLE `match_substitutions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `match_videos`
--
ALTER TABLE `match_videos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `players`
--
ALTER TABLE `players`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `player_team_season`
--
ALTER TABLE `player_team_season`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `playlists`
--
ALTER TABLE `playlists`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seasons`
--
ALTER TABLE `seasons`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stat_overrides`
--
ALTER TABLE `stat_overrides`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tags`
--
ALTER TABLE `tags`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teams`
--
ALTER TABLE `teams`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `annotations`
--
ALTER TABLE `annotations`
  ADD CONSTRAINT `fk_annotations_match` FOREIGN KEY (`match_id`) REFERENCES `matches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD CONSTRAINT `fk_audit_club` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `clips`
--
ALTER TABLE `clips`
  ADD CONSTRAINT `fk_clips_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_clips_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_clips_match` FOREIGN KEY (`match_id`) REFERENCES `matches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_clips_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
