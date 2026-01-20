-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jan 19, 2026 at 05:52 PM
-- Server version: 10.11.13-MariaDB-0ubuntu0.24.04.1
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
) ;

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

--
-- Dumping data for table `audit_log`
--

INSERT INTO `audit_log` (`id`, `club_id`, `user_id`, `entity_type`, `entity_id`, `action`, `before_json`, `after_json`, `created_at`) VALUES
(1, 1, 1, 'event', 180, 'create', NULL, '{\"id\":180,\"match_id\":1,\"period_id\":3,\"match_second\":0,\"minute\":0,\"minute_extra\":0,\"team_side\":\"unknown\",\"event_type_id\":13,\"importance\":1,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":\"First Half\",\"created_by\":1,\"created_at\":\"2025-12-26 11:58:57\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"event_type_label\":\"Period Start\",\"event_type_key\":\"period_start\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"tags\":[]}', '2025-12-26 11:58:58'),
(2, 1, 1, 'event', 181, 'create', NULL, '{\"id\":181,\"match_id\":1,\"period_id\":3,\"match_second\":0,\"minute\":0,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":17,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2025-12-26 11:59:02\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"event_type_label\":\"Foul\",\"event_type_key\":\"foul\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"tags\":[]}', '2025-12-26 11:59:02'),
(3, 1, 1, 'event', 182, 'create', NULL, '{\"id\":182,\"match_id\":1,\"period_id\":3,\"match_second\":0,\"minute\":0,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2025-12-26 15:50:19\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"tags\":[]}', '2025-12-26 15:50:20'),
(4, 1, 1, 'event', 180, 'delete', '{\"id\":180,\"match_id\":1,\"period_id\":3,\"match_second\":0,\"minute\":0,\"minute_extra\":0,\"team_side\":\"unknown\",\"event_type_id\":13,\"importance\":1,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":\"First Half\",\"created_by\":1,\"created_at\":\"2025-12-26 11:58:57\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"event_type_label\":\"Period Start\",\"event_type_key\":\"period_start\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"tags\":[]}', NULL, '2025-12-26 15:51:16'),
(5, 1, 1, 'event', 182, 'delete', '{\"id\":182,\"match_id\":1,\"period_id\":3,\"match_second\":0,\"minute\":0,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2025-12-26 15:50:19\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"tags\":[]}', NULL, '2025-12-26 15:51:16'),
(6, 1, 1, 'event', 181, 'delete', '{\"id\":181,\"match_id\":1,\"period_id\":3,\"match_second\":0,\"minute\":0,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":17,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2025-12-26 11:59:02\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"event_type_label\":\"Foul\",\"event_type_key\":\"foul\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"tags\":[]}', NULL, '2025-12-26 15:51:16'),
(7, 1, 1, 'event', 183, 'create', NULL, '{\"id\":183,\"match_id\":1,\"period_id\":3,\"match_second\":0,\"minute\":0,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":4,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2025-12-26 15:51:35\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"event_type_label\":\"Corner\",\"event_type_key\":\"corner\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"tags\":[]}', '2025-12-26 15:51:35'),
(8, 1, 1, 'event', 184, 'create', NULL, '{\"id\":184,\"match_id\":1,\"period_id\":3,\"match_second\":0,\"minute\":0,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2025-12-26 15:51:37\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"tags\":[]}', '2025-12-26 15:51:37'),
(9, 1, 1, 'event', 185, 'create', NULL, '{\"id\":185,\"match_id\":1,\"period_id\":3,\"match_second\":0,\"minute\":0,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":17,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2025-12-26 15:51:55\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"event_type_label\":\"Foul\",\"event_type_key\":\"foul\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"tags\":[]}', '2025-12-26 15:51:55'),
(10, 1, 1, 'event', 186, 'create', NULL, '{\"id\":186,\"match_id\":1,\"period_id\":3,\"match_second\":0,\"minute\":0,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":17,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2025-12-26 15:54:16\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"event_type_label\":\"Foul\",\"event_type_key\":\"foul\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"tags\":[]}', '2025-12-26 15:54:16'),
(11, 1, 1, 'event', 187, 'create', NULL, '{\"id\":187,\"match_id\":1,\"period_id\":3,\"match_second\":1039,\"minute\":17,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":17,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2025-12-26 16:11:32\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"event_type_label\":\"Foul\",\"event_type_key\":\"foul\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"tags\":[]}', '2025-12-26 16:11:32'),
(12, 1, 1, 'event', 183, 'delete', '{\"id\":183,\"match_id\":1,\"period_id\":3,\"match_second\":0,\"minute\":0,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":4,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2025-12-26 15:51:35\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"event_type_label\":\"Corner\",\"event_type_key\":\"corner\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"tags\":[]}', NULL, '2025-12-26 16:11:49'),
(13, 1, 1, 'event', 184, 'delete', '{\"id\":184,\"match_id\":1,\"period_id\":3,\"match_second\":0,\"minute\":0,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2025-12-26 15:51:37\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"tags\":[]}', NULL, '2025-12-26 16:11:49'),
(14, 1, 1, 'event', 185, 'delete', '{\"id\":185,\"match_id\":1,\"period_id\":3,\"match_second\":0,\"minute\":0,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":17,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2025-12-26 15:51:55\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"event_type_label\":\"Foul\",\"event_type_key\":\"foul\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"tags\":[]}', NULL, '2025-12-26 16:11:49'),
(15, 1, 1, 'event', 187, 'delete', '{\"id\":187,\"match_id\":1,\"period_id\":3,\"match_second\":1039,\"minute\":17,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":17,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2025-12-26 16:11:32\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"event_type_label\":\"Foul\",\"event_type_key\":\"foul\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"tags\":[]}', NULL, '2025-12-26 16:11:49'),
(16, 1, 1, 'event', 186, 'delete', '{\"id\":186,\"match_id\":1,\"period_id\":3,\"match_second\":0,\"minute\":0,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":17,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2025-12-26 15:54:16\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"event_type_label\":\"Foul\",\"event_type_key\":\"foul\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"tags\":[]}', NULL, '2025-12-26 16:11:49'),
(17, 1, 1, 'event', 188, 'create', NULL, '{\"id\":188,\"match_id\":1,\"period_id\":3,\"match_second\":1039,\"minute\":17,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2025-12-26 16:27:27\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"tags\":[]}', '2025-12-26 16:27:27'),
(18, 1, 1, 'event', 189, 'create', NULL, '{\"id\":189,\"match_id\":1,\"period_id\":3,\"match_second\":1039,\"minute\":17,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2025-12-26 16:27:28\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"tags\":[]}', '2025-12-26 16:27:28'),
(19, 1, 1, 'event', 190, 'create', NULL, '{\"id\":190,\"match_id\":1,\"period_id\":3,\"match_second\":1492,\"minute\":24,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":17,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2025-12-26 16:30:02\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"event_type_label\":\"Foul\",\"event_type_key\":\"foul\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"tags\":[]}', '2025-12-26 16:30:02'),
(20, 1, 1, 'event', 191, 'create', NULL, '{\"id\":191,\"match_id\":1,\"period_id\":3,\"match_second\":1492,\"minute\":24,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":17,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2025-12-26 16:40:19\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"event_type_label\":\"Foul\",\"event_type_key\":\"foul\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"tags\":[]}', '2025-12-26 16:40:19'),
(21, 1, 1, 'event', 192, 'create', NULL, '{\"id\":192,\"match_id\":1,\"period_id\":3,\"match_second\":1492,\"minute\":24,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":8,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2025-12-26 16:40:21\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"event_type_label\":\"Yellow Card\",\"event_type_key\":\"yellow_card\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"tags\":[]}', '2025-12-26 16:40:21'),
(22, 1, 1, 'event', 193, 'create', NULL, '{\"id\":193,\"match_id\":1,\"period_id\":3,\"match_second\":1492,\"minute\":24,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":8,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2025-12-26 16:40:21\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"event_type_label\":\"Yellow Card\",\"event_type_key\":\"yellow_card\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"tags\":[]}', '2025-12-26 16:40:21'),
(23, 1, 1, 'event', 194, 'create', NULL, '{\"id\":194,\"match_id\":1,\"period_id\":3,\"match_second\":4326,\"minute\":72,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":17,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2025-12-26 16:45:25\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"event_type_label\":\"Foul\",\"event_type_key\":\"foul\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"tags\":[]}', '2025-12-26 16:45:25'),
(24, 1, 1, 'event', 194, 'delete', '{\"id\":194,\"match_id\":1,\"period_id\":3,\"match_second\":4326,\"minute\":72,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":17,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2025-12-26 16:45:25\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"event_type_label\":\"Foul\",\"event_type_key\":\"foul\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"tags\":[]}', NULL, '2025-12-26 16:47:04'),
(25, 1, 1, 'event', 195, 'create', NULL, '{\"id\":195,\"match_id\":1,\"period_id\":3,\"match_second\":4326,\"minute\":72,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":17,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2025-12-26 16:47:07\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"event_type_label\":\"Foul\",\"event_type_key\":\"foul\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"tags\":[]}', '2025-12-26 16:47:07'),
(26, 1, 1, 'event', 196, 'create', NULL, '{\"id\":196,\"match_id\":1,\"period_id\":3,\"match_second\":4878,\"minute\":81,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":8,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2025-12-26 16:47:15\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"event_type_label\":\"Yellow Card\",\"event_type_key\":\"yellow_card\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"tags\":[]}', '2025-12-26 16:47:15'),
(27, 1, 1, 'event', 196, 'delete', '{\"id\":196,\"match_id\":1,\"period_id\":3,\"match_second\":4878,\"minute\":81,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":8,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2025-12-26 16:47:15\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"event_type_label\":\"Yellow Card\",\"event_type_key\":\"yellow_card\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"tags\":[]}', NULL, '2025-12-26 16:47:20'),
(28, 1, 1, 'event', 197, 'create', NULL, '{\"id\":197,\"match_id\":1,\"period_id\":3,\"match_second\":4878,\"minute\":81,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":8,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2025-12-26 16:47:21\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"event_type_label\":\"Yellow Card\",\"event_type_key\":\"yellow_card\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"tags\":[]}', '2025-12-26 16:47:21'),
(29, 1, 1, 'event', 197, 'delete', '{\"id\":197,\"match_id\":1,\"period_id\":3,\"match_second\":4878,\"minute\":81,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":8,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2025-12-26 16:47:21\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"event_type_label\":\"Yellow Card\",\"event_type_key\":\"yellow_card\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"tags\":[]}', NULL, '2025-12-26 16:49:38'),
(30, 1, 1, 'event', 198, 'create', NULL, '{\"id\":198,\"match_id\":1,\"period_id\":3,\"match_second\":4878,\"minute\":81,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":8,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2025-12-26 16:49:40\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"event_type_label\":\"Yellow Card\",\"event_type_key\":\"yellow_card\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"tags\":[]}', '2025-12-26 16:49:40'),
(31, 1, 1, 'event', 199, 'create', NULL, '{\"id\":199,\"match_id\":1,\"period_id\":3,\"match_second\":3085,\"minute\":51,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2025-12-26 16:50:17\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"tags\":[]}', '2025-12-26 16:50:17'),
(32, 1, 1, 'event', 200, 'create', NULL, '{\"id\":200,\"match_id\":1,\"period_id\":3,\"match_second\":1039,\"minute\":17,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":3,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2025-12-26 16:54:00\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"event_type_label\":\"Chance\",\"event_type_key\":\"chance\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"tags\":[]}', '2025-12-26 16:54:00'),
(33, 1, 1, 'event', 188, 'update', '{\"id\":188,\"match_id\":1,\"period_id\":3,\"match_second\":1039,\"minute\":17,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2025-12-26 16:27:27\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"tags\":[]}', '{\"id\":188,\"match_id\":1,\"period_id\":3,\"match_second\":1039,\"minute\":17,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"match_player_id\":3,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2025-12-26 16:27:27\",\"updated_by\":null,\"updated_at\":\"2025-12-26 17:16:00\",\"match_period_id\":null,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":\"Player 1\",\"match_player_shirt\":null,\"match_player_team_side\":\"away\",\"match_player_position\":\"Striker\",\"period_label\":\"First Half\",\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"tags\":[]}', '2025-12-26 17:16:00'),
(34, 1, 1, 'event', 188, 'update', '{\"id\":188,\"match_id\":1,\"period_id\":3,\"match_second\":1039,\"minute\":17,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"match_player_id\":3,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2025-12-26 16:27:27\",\"updated_by\":null,\"updated_at\":\"2025-12-26 17:16:00\",\"match_period_id\":null,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":\"Player 1\",\"match_player_shirt\":null,\"match_player_team_side\":\"away\",\"match_player_position\":\"Striker\",\"period_label\":\"First Half\",\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"tags\":[]}', '{\"id\":188,\"match_id\":1,\"period_id\":3,\"match_second\":1039,\"minute\":17,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"match_player_id\":3,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2025-12-26 16:27:27\",\"updated_by\":null,\"updated_at\":\"2025-12-26 17:16:00\",\"match_period_id\":null,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":\"Player 1\",\"match_player_shirt\":null,\"match_player_team_side\":\"away\",\"match_player_position\":\"Striker\",\"period_label\":\"First Half\",\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"tags\":[]}', '2025-12-26 17:16:12'),
(35, 1, 1, 'event', 195, 'update', '{\"id\":195,\"match_id\":1,\"period_id\":3,\"match_second\":4326,\"minute\":72,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":17,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2025-12-26 16:47:07\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"event_type_label\":\"Foul\",\"event_type_key\":\"foul\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"tags\":[]}', '{\"id\":195,\"match_id\":1,\"period_id\":3,\"match_second\":4326,\"minute\":72,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":17,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2025-12-26 16:47:07\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"event_type_label\":\"Foul\",\"event_type_key\":\"foul\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"tags\":[]}', '2025-12-27 10:40:58'),
(36, 1, 1, 'event', 189, 'update', '{\"id\":189,\"match_id\":1,\"period_id\":3,\"match_second\":1039,\"minute\":17,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2025-12-26 16:27:28\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"tags\":[]}', '{\"id\":189,\"match_id\":1,\"period_id\":3,\"match_second\":1039,\"minute\":17,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"on_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2025-12-26 16:27:28\",\"updated_by\":null,\"updated_at\":\"2025-12-27 10:41:45\",\"match_period_id\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"tags\":[]}', '2025-12-27 10:41:45'),
(37, 1, 1, 'event', 201, 'create', NULL, '{\"id\":201,\"match_id\":1,\"period_id\":3,\"match_second\":1039,\"minute\":17,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2025-12-27 13:22:32\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"tags\":[]}', '2025-12-27 13:22:32'),
(38, 1, 1, 'event', 201, 'update', '{\"id\":201,\"match_id\":1,\"period_id\":3,\"match_second\":1039,\"minute\":17,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2025-12-27 13:22:32\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"tags\":[]}', '{\"id\":201,\"match_id\":1,\"period_id\":3,\"match_second\":1181,\"minute\":17,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2025-12-27 13:22:32\",\"updated_by\":null,\"updated_at\":\"2025-12-27 13:22:53\",\"match_period_id\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"tags\":[]}', '2025-12-27 13:22:53'),
(39, 1, 1, 'event', 189, 'update', '{\"id\":189,\"match_id\":1,\"period_id\":3,\"match_second\":1039,\"minute\":17,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"on_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2025-12-26 16:27:28\",\"updated_by\":null,\"updated_at\":\"2025-12-27 10:41:45\",\"match_period_id\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"tags\":[]}', '{\"id\":189,\"match_id\":1,\"period_id\":3,\"match_second\":1039,\"minute\":17,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"on_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2025-12-26 16:27:28\",\"updated_by\":null,\"updated_at\":\"2025-12-27 10:41:45\",\"match_period_id\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"tags\":[]}', '2025-12-27 13:23:05'),
(40, 1, 1, 'clip_review', 1, 'review_approved', '{\"status\":\"pending\",\"reviewed_by\":null,\"reviewed_at\":null}', '{\"status\":\"approved\",\"reviewed_by\":1,\"reviewed_at\":\"2025-12-28 14:53:10\"}', '2025-12-28 14:53:10'),
(41, 1, 1, 'clip_review', 2, 'review_approved', '{\"status\":\"pending\",\"reviewed_by\":null,\"reviewed_at\":null}', '{\"status\":\"approved\",\"reviewed_by\":1,\"reviewed_at\":\"2025-12-28 14:53:26\"}', '2025-12-28 14:53:26'),
(42, 1, 1, 'clip_review', 3, 'review_approved', '{\"status\":\"pending\",\"reviewed_by\":null,\"reviewed_at\":null}', '{\"status\":\"approved\",\"reviewed_by\":1,\"reviewed_at\":\"2025-12-28 14:53:28\"}', '2025-12-28 14:53:28'),
(43, 1, 1, 'clip_review', 4, 'review_approved', '{\"status\":\"pending\",\"reviewed_by\":null,\"reviewed_at\":null}', '{\"status\":\"approved\",\"reviewed_by\":1,\"reviewed_at\":\"2025-12-28 14:53:29\"}', '2025-12-28 14:53:29'),
(44, 1, 1, 'clip_review', 5, 'review_approved', '{\"status\":\"pending\",\"reviewed_by\":null,\"reviewed_at\":null}', '{\"status\":\"approved\",\"reviewed_by\":1,\"reviewed_at\":\"2025-12-28 14:53:29\"}', '2025-12-28 14:53:29'),
(45, 1, 1, 'clip', 6, 'create', NULL, '{\"id\":6,\"match_id\":1,\"event_id\":188,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Goal @ 1039s\",\"start_second\":1009,\"end_second\":1069,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2025-12-28 14:53:45\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":2,\"is_valid\":1,\"deleted_at\":null}', '2025-12-28 14:53:45'),
(46, 1, 1, 'clip', 7, 'create', NULL, '{\"id\":7,\"match_id\":1,\"event_id\":189,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Shot @ 1039s\",\"start_second\":1009,\"end_second\":1069,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2025-12-28 14:53:45\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":2,\"is_valid\":1,\"deleted_at\":null}', '2025-12-28 14:53:45'),
(47, 1, 1, 'clip', 8, 'create', NULL, '{\"id\":8,\"match_id\":1,\"event_id\":200,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Chance @ 1039s\",\"start_second\":1009,\"end_second\":1069,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2025-12-28 14:53:45\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":2,\"is_valid\":1,\"deleted_at\":null}', '2025-12-28 14:53:45'),
(48, 1, 1, 'clip', 9, 'create', NULL, '{\"id\":9,\"match_id\":1,\"event_id\":190,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Foul @ 1492s\",\"start_second\":1462,\"end_second\":1522,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2025-12-28 14:53:45\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":2,\"is_valid\":1,\"deleted_at\":null}', '2025-12-28 14:53:45'),
(49, 1, 1, 'clip', 10, 'create', NULL, '{\"id\":10,\"match_id\":1,\"event_id\":199,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Shot @ 3085s\",\"start_second\":3055,\"end_second\":3115,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2025-12-28 14:53:45\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":2,\"is_valid\":1,\"deleted_at\":null}', '2025-12-28 14:53:45'),
(50, 1, 1, 'clip', 6, 'regenerated', NULL, NULL, '2025-12-28 14:53:45'),
(51, 1, 1, 'clip', 7, 'regenerated', NULL, NULL, '2025-12-28 14:53:45'),
(52, 1, 1, 'clip', 8, 'regenerated', NULL, NULL, '2025-12-28 14:53:45'),
(53, 1, 1, 'clip', 9, 'regenerated', NULL, NULL, '2025-12-28 14:53:45'),
(54, 1, 1, 'clip', 10, 'regenerated', NULL, NULL, '2025-12-28 14:53:45'),
(55, 1, 1, 'clip', 11, 'create', NULL, '{\"id\":11,\"match_id\":1,\"event_id\":188,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Goal @ 1039s\",\"start_second\":1009,\"end_second\":1069,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2025-12-28 15:11:09\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":3,\"is_valid\":1,\"deleted_at\":null}', '2025-12-28 15:11:09'),
(56, 1, 1, 'clip', 12, 'create', NULL, '{\"id\":12,\"match_id\":1,\"event_id\":189,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Shot @ 1039s\",\"start_second\":1009,\"end_second\":1069,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2025-12-28 15:11:09\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":3,\"is_valid\":1,\"deleted_at\":null}', '2025-12-28 15:11:09'),
(57, 1, 1, 'clip', 13, 'create', NULL, '{\"id\":13,\"match_id\":1,\"event_id\":200,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Chance @ 1039s\",\"start_second\":1009,\"end_second\":1069,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2025-12-28 15:11:09\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":3,\"is_valid\":1,\"deleted_at\":null}', '2025-12-28 15:11:09'),
(58, 1, 1, 'clip', 14, 'create', NULL, '{\"id\":14,\"match_id\":1,\"event_id\":190,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Foul @ 1492s\",\"start_second\":1462,\"end_second\":1522,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2025-12-28 15:11:09\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":3,\"is_valid\":1,\"deleted_at\":null}', '2025-12-28 15:11:09'),
(59, 1, 1, 'clip', 15, 'create', NULL, '{\"id\":15,\"match_id\":1,\"event_id\":199,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Shot @ 3085s\",\"start_second\":3055,\"end_second\":3115,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2025-12-28 15:11:09\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":3,\"is_valid\":1,\"deleted_at\":null}', '2025-12-28 15:11:09'),
(60, 1, 1, 'clip', 11, 'regenerated', NULL, NULL, '2025-12-28 15:11:09'),
(61, 1, 1, 'clip', 12, 'regenerated', NULL, NULL, '2025-12-28 15:11:09'),
(62, 1, 1, 'clip', 13, 'regenerated', NULL, NULL, '2025-12-28 15:11:09'),
(63, 1, 1, 'clip', 14, 'regenerated', NULL, NULL, '2025-12-28 15:11:09'),
(64, 1, 1, 'clip', 15, 'regenerated', NULL, NULL, '2025-12-28 15:11:09'),
(65, 1, 1, 'event', 202, 'create', NULL, '{\"id\":202,\"match_id\":1,\"period_id\":3,\"match_second\":7,\"minute\":0,\"minute_extra\":0,\"team_side\":\"unknown\",\"event_type_id\":13,\"importance\":1,\"phase\":\"unknown\",\"match_player_id\":3,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":\"First Half\",\"created_by\":1,\"created_at\":\"2026-01-02 09:49:29\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Period Start\",\"event_type_key\":\"period_start\",\"match_player_name\":\"Player 1\",\"match_player_shirt\":null,\"match_player_team_side\":\"away\",\"match_player_position\":\"Striker\",\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-02 09:49:29'),
(66, 1, 1, 'event', 203, 'create', NULL, '{\"id\":203,\"match_id\":1,\"period_id\":3,\"match_second\":2821,\"minute\":47,\"minute_extra\":0,\"team_side\":\"unknown\",\"event_type_id\":14,\"importance\":1,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":\"First Half\",\"created_by\":1,\"created_at\":\"2026-01-02 09:51:48\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Period End\",\"event_type_key\":\"period_end\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-02 09:51:48'),
(67, 1, 1, 'playlist', 1, 'create', NULL, '{\"id\":1,\"match_id\":1,\"title\":\"test\",\"notes\":null,\"created_at\":\"2026-01-05 17:16:05\",\"updated_at\":null,\"deleted_at\":null}', '2026-01-05 17:16:05'),
(68, 1, 1, 'playlist', 2, 'create', NULL, '{\"id\":2,\"match_id\":1,\"title\":\"test\",\"notes\":null,\"created_at\":\"2026-01-05 17:17:11\",\"updated_at\":null,\"deleted_at\":null}', '2026-01-05 17:17:11'),
(69, 1, 1, 'event', 188, 'delete', '{\"id\":188,\"match_id\":1,\"period_id\":3,\"match_second\":1039,\"minute\":17,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"match_player_id\":3,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2025-12-26 16:27:27\",\"updated_by\":null,\"updated_at\":\"2025-12-27 22:34:36\",\"match_period_id\":null,\"clip_id\":11,\"clip_start_second\":1009,\"clip_end_second\":1069,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":\"Player 1\",\"match_player_shirt\":null,\"match_player_team_side\":\"away\",\"match_player_position\":\"Striker\",\"period_label\":\"First Half\",\"tags\":[]}', NULL, '2026-01-06 09:37:21'),
(70, 1, 1, 'event', 189, 'delete', '{\"id\":189,\"match_id\":1,\"period_id\":3,\"match_second\":1039,\"minute\":17,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"on_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2025-12-26 16:27:28\",\"updated_by\":null,\"updated_at\":\"2025-12-27 22:34:36\",\"match_period_id\":null,\"clip_id\":12,\"clip_start_second\":1009,\"clip_end_second\":1069,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', NULL, '2026-01-06 09:37:34'),
(71, 1, 1, 'event', 200, 'delete', '{\"id\":200,\"match_id\":1,\"period_id\":3,\"match_second\":1039,\"minute\":17,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":3,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2025-12-26 16:54:00\",\"updated_by\":null,\"updated_at\":\"2025-12-27 22:34:36\",\"match_period_id\":null,\"clip_id\":13,\"clip_start_second\":1009,\"clip_end_second\":1069,\"event_type_label\":\"Chance\",\"event_type_key\":\"chance\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', NULL, '2026-01-06 09:37:39'),
(72, 1, 1, 'event', 201, 'delete', '{\"id\":201,\"match_id\":1,\"period_id\":3,\"match_second\":1181,\"minute\":17,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2025-12-27 13:22:32\",\"updated_by\":null,\"updated_at\":\"2025-12-27 13:22:53\",\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', NULL, '2026-01-06 09:37:57'),
(73, 1, 1, 'event', 191, 'delete', '{\"id\":191,\"match_id\":1,\"period_id\":3,\"match_second\":1492,\"minute\":24,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":17,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2025-12-26 16:40:19\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Foul\",\"event_type_key\":\"foul\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', NULL, '2026-01-06 09:38:03'),
(74, 1, 1, 'event', 195, 'delete', '{\"id\":195,\"match_id\":1,\"period_id\":3,\"match_second\":4326,\"minute\":72,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":17,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2025-12-26 16:47:07\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Foul\",\"event_type_key\":\"foul\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', NULL, '2026-01-06 09:38:10'),
(75, 1, 1, 'event', 202, 'delete', '{\"id\":202,\"match_id\":1,\"period_id\":3,\"match_second\":7,\"minute\":0,\"minute_extra\":0,\"team_side\":\"unknown\",\"event_type_id\":13,\"importance\":1,\"phase\":\"unknown\",\"match_player_id\":3,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":\"First Half\",\"created_by\":1,\"created_at\":\"2026-01-02 09:49:29\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Period Start\",\"event_type_key\":\"period_start\",\"match_player_name\":\"Player 1\",\"match_player_shirt\":null,\"match_player_team_side\":\"away\",\"match_player_position\":\"Striker\",\"period_label\":\"First Half\",\"tags\":[]}', NULL, '2026-01-06 09:38:26'),
(76, 1, 1, 'event', 190, 'delete', '{\"id\":190,\"match_id\":1,\"period_id\":3,\"match_second\":1492,\"minute\":24,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":17,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2025-12-26 16:30:02\",\"updated_by\":null,\"updated_at\":\"2025-12-27 22:34:36\",\"match_period_id\":null,\"clip_id\":14,\"clip_start_second\":1462,\"clip_end_second\":1522,\"event_type_label\":\"Foul\",\"event_type_key\":\"foul\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', NULL, '2026-01-06 09:38:26');
INSERT INTO `audit_log` (`id`, `club_id`, `user_id`, `entity_type`, `entity_id`, `action`, `before_json`, `after_json`, `created_at`) VALUES
(77, 1, 1, 'event', 192, 'delete', '{\"id\":192,\"match_id\":1,\"period_id\":3,\"match_second\":1492,\"minute\":24,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":8,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2025-12-26 16:40:21\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Yellow Card\",\"event_type_key\":\"yellow_card\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', NULL, '2026-01-06 09:38:26'),
(78, 1, 1, 'event', 193, 'delete', '{\"id\":193,\"match_id\":1,\"period_id\":3,\"match_second\":1492,\"minute\":24,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":8,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2025-12-26 16:40:21\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Yellow Card\",\"event_type_key\":\"yellow_card\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', NULL, '2026-01-06 09:38:26'),
(79, 1, 1, 'event', 203, 'delete', '{\"id\":203,\"match_id\":1,\"period_id\":3,\"match_second\":2821,\"minute\":47,\"minute_extra\":0,\"team_side\":\"unknown\",\"event_type_id\":14,\"importance\":1,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":\"First Half\",\"created_by\":1,\"created_at\":\"2026-01-02 09:51:48\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Period End\",\"event_type_key\":\"period_end\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', NULL, '2026-01-06 09:38:26'),
(80, 1, 1, 'event', 198, 'delete', '{\"id\":198,\"match_id\":1,\"period_id\":3,\"match_second\":4878,\"minute\":81,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":8,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2025-12-26 16:49:40\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Yellow Card\",\"event_type_key\":\"yellow_card\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', NULL, '2026-01-06 09:38:26'),
(81, 1, 1, 'event', 199, 'delete', '{\"id\":199,\"match_id\":1,\"period_id\":3,\"match_second\":3085,\"minute\":51,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2025-12-26 16:50:17\",\"updated_by\":null,\"updated_at\":\"2025-12-27 22:34:36\",\"match_period_id\":null,\"clip_id\":15,\"clip_start_second\":3055,\"clip_end_second\":3115,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', NULL, '2026-01-06 09:38:26'),
(82, 1, 1, 'event', 204, 'create', NULL, '{\"id\":204,\"match_id\":1,\"period_id\":null,\"match_second\":4335,\"minute\":72,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-06 13:13:10\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":null,\"tags\":[]}', '2026-01-06 13:13:10'),
(83, 1, 1, 'clip', 16, 'create', NULL, '{\"id\":16,\"match_id\":1,\"event_id\":204,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Goal @ 4335s\",\"start_second\":4305,\"end_second\":4365,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2026-01-06 13:13:10\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null}', '2026-01-06 13:13:10'),
(84, 1, 1, 'clip', 16, 'generated', NULL, NULL, '2026-01-06 13:13:10'),
(85, 1, 1, 'event', 204, 'delete', '{\"id\":204,\"match_id\":1,\"period_id\":null,\"match_second\":4335,\"minute\":72,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-06 13:13:10\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":16,\"clip_start_second\":4305,\"clip_end_second\":4365,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":null,\"tags\":[]}', NULL, '2026-01-07 11:12:12'),
(86, 1, 1, 'event', 205, 'create', NULL, '{\"id\":205,\"match_id\":1,\"period_id\":3,\"match_second\":337,\"minute\":5,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-07 12:10:43\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-07 12:10:43'),
(87, 1, 1, 'clip', 17, 'create', NULL, '{\"id\":17,\"match_id\":1,\"event_id\":205,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Goal @ 337s\",\"start_second\":307,\"end_second\":367,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2026-01-07 12:10:43\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null}', '2026-01-07 12:10:43'),
(88, 1, 1, 'clip', 17, 'generated', NULL, NULL, '2026-01-07 12:10:43'),
(89, 1, 1, 'playlist_clip', 1, 'add', NULL, '{\"playlist_id\":1,\"clip_id\":17,\"sort_order\":0}', '2026-01-07 12:43:22'),
(90, 1, 1, 'event', 206, 'create', NULL, '{\"id\":206,\"match_id\":1,\"period_id\":3,\"match_second\":1773,\"minute\":29,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":8,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-07 14:53:39\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Yellow Card\",\"event_type_key\":\"yellow_card\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-07 14:53:39'),
(91, 1, 1, 'clip', 18, 'create', NULL, '{\"id\":18,\"match_id\":1,\"event_id\":206,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Yellow Card @ 1773s\",\"start_second\":1743,\"end_second\":1803,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2026-01-07 14:53:39\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null}', '2026-01-07 14:53:39'),
(92, 1, 1, 'clip', 18, 'generated', NULL, NULL, '2026-01-07 14:53:39'),
(93, 1, 1, 'playlist_clip', 2, 'add', NULL, '{\"playlist_id\":2,\"clip_id\":18,\"sort_order\":0}', '2026-01-07 14:53:47'),
(94, 1, 1, 'event', 207, 'create', NULL, '{\"id\":207,\"match_id\":1,\"period_id\":3,\"match_second\":1773,\"minute\":29,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":17,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-07 14:59:44\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Foul\",\"event_type_key\":\"foul\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-07 14:59:44'),
(95, 1, 1, 'clip', 19, 'create', NULL, '{\"id\":19,\"match_id\":1,\"event_id\":207,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Foul @ 1773s\",\"start_second\":1743,\"end_second\":1803,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2026-01-07 14:59:44\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null}', '2026-01-07 14:59:44'),
(96, 1, 1, 'clip', 19, 'generated', NULL, NULL, '2026-01-07 14:59:44'),
(97, 1, 1, 'event', 208, 'create', NULL, '{\"id\":208,\"match_id\":1,\"period_id\":3,\"match_second\":1433,\"minute\":23,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-07 14:59:56\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-07 14:59:56'),
(98, 1, 1, 'clip', 20, 'create', NULL, '{\"id\":20,\"match_id\":1,\"event_id\":208,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Free Kick @ 1433s\",\"start_second\":1403,\"end_second\":1463,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2026-01-07 14:59:56\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null}', '2026-01-07 14:59:56'),
(99, 1, 1, 'clip', 20, 'generated', NULL, NULL, '2026-01-07 14:59:56'),
(100, 1, 1, 'event', 209, 'create', NULL, '{\"id\":209,\"match_id\":1,\"period_id\":3,\"match_second\":1433,\"minute\":23,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":4,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-07 14:59:56\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Corner\",\"event_type_key\":\"corner\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-07 14:59:56'),
(101, 1, 1, 'clip', 21, 'create', NULL, '{\"id\":21,\"match_id\":1,\"event_id\":209,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Corner @ 1433s\",\"start_second\":1403,\"end_second\":1463,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2026-01-07 14:59:56\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null}', '2026-01-07 14:59:56'),
(102, 1, 1, 'clip', 21, 'generated', NULL, NULL, '2026-01-07 14:59:56'),
(103, 1, 1, 'event', 210, 'create', NULL, '{\"id\":210,\"match_id\":1,\"period_id\":3,\"match_second\":1433,\"minute\":23,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-07 14:59:57\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-07 14:59:57'),
(104, 1, 1, 'clip', 22, 'create', NULL, '{\"id\":22,\"match_id\":1,\"event_id\":210,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Shot @ 1433s\",\"start_second\":1403,\"end_second\":1463,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2026-01-07 14:59:57\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null}', '2026-01-07 14:59:57'),
(105, 1, 1, 'clip', 22, 'generated', NULL, NULL, '2026-01-07 14:59:57'),
(106, 1, 1, 'event', 211, 'create', NULL, '{\"id\":211,\"match_id\":1,\"period_id\":3,\"match_second\":1433,\"minute\":23,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":9,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-07 14:59:58\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Red Card\",\"event_type_key\":\"red_card\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-07 14:59:58'),
(107, 1, 1, 'clip', 23, 'create', NULL, '{\"id\":23,\"match_id\":1,\"event_id\":211,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Red Card @ 1433s\",\"start_second\":1403,\"end_second\":1463,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2026-01-07 14:59:58\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null}', '2026-01-07 14:59:58'),
(108, 1, 1, 'clip', 23, 'generated', NULL, NULL, '2026-01-07 14:59:58'),
(109, 1, 1, 'playlist_clip', 1, 'add', NULL, '{\"playlist_id\":1,\"clip_id\":22,\"sort_order\":1}', '2026-01-07 15:00:59'),
(110, 1, 1, 'playlist_clip', 2, 'add', NULL, '{\"playlist_id\":2,\"clip_id\":20,\"sort_order\":1}', '2026-01-07 15:01:05'),
(111, 1, 1, 'event', 205, 'delete', '{\"id\":205,\"match_id\":1,\"period_id\":3,\"match_second\":337,\"minute\":5,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-07 12:10:43\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":17,\"clip_start_second\":307,\"clip_end_second\":367,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', NULL, '2026-01-08 08:24:18'),
(112, 1, 1, 'event', 208, 'delete', '{\"id\":208,\"match_id\":1,\"period_id\":3,\"match_second\":1433,\"minute\":23,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-07 14:59:56\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":20,\"clip_start_second\":1403,\"clip_end_second\":1463,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', NULL, '2026-01-08 08:24:23'),
(113, 1, 1, 'event', 209, 'delete', '{\"id\":209,\"match_id\":1,\"period_id\":3,\"match_second\":1433,\"minute\":23,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":4,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-07 14:59:56\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":21,\"clip_start_second\":1403,\"clip_end_second\":1463,\"event_type_label\":\"Corner\",\"event_type_key\":\"corner\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', NULL, '2026-01-08 08:24:27'),
(114, 1, 1, 'event', 210, 'delete', '{\"id\":210,\"match_id\":1,\"period_id\":3,\"match_second\":1433,\"minute\":23,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-07 14:59:57\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":22,\"clip_start_second\":1403,\"clip_end_second\":1463,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', NULL, '2026-01-08 08:24:33'),
(115, 1, 1, 'event', 211, 'delete', '{\"id\":211,\"match_id\":1,\"period_id\":3,\"match_second\":1433,\"minute\":23,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":9,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-07 14:59:58\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":23,\"clip_start_second\":1403,\"clip_end_second\":1463,\"event_type_label\":\"Red Card\",\"event_type_key\":\"red_card\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', NULL, '2026-01-08 08:24:36'),
(116, 1, 1, 'event', 206, 'delete', '{\"id\":206,\"match_id\":1,\"period_id\":3,\"match_second\":1773,\"minute\":29,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":8,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-07 14:53:39\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":18,\"clip_start_second\":1743,\"clip_end_second\":1803,\"event_type_label\":\"Yellow Card\",\"event_type_key\":\"yellow_card\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', NULL, '2026-01-08 08:24:40'),
(117, 1, 1, 'event', 207, 'delete', '{\"id\":207,\"match_id\":1,\"period_id\":3,\"match_second\":1773,\"minute\":29,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":17,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-07 14:59:44\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":19,\"clip_start_second\":1743,\"clip_end_second\":1803,\"event_type_label\":\"Foul\",\"event_type_key\":\"foul\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', NULL, '2026-01-08 08:24:47'),
(118, 1, 1, 'event', 212, 'create', NULL, '{\"id\":212,\"match_id\":1,\"period_id\":3,\"match_second\":1221,\"minute\":20,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":4,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-08 08:43:18\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Corner\",\"event_type_key\":\"corner\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-08 08:43:18'),
(119, 1, 1, 'clip', 24, 'create', NULL, '{\"id\":24,\"match_id\":1,\"event_id\":212,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Corner @ 1221s\",\"start_second\":1191,\"end_second\":1251,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2026-01-08 08:43:18\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null}', '2026-01-08 08:43:18'),
(120, 1, 1, 'clip', 24, 'generated', NULL, NULL, '2026-01-08 08:43:18'),
(121, 1, 1, 'playlist_clip', 2, 'add', NULL, '{\"playlist_id\":2,\"clip_id\":24,\"sort_order\":0}', '2026-01-08 08:51:45'),
(122, 1, 1, 'playlist_clip', 1, 'add', NULL, '{\"playlist_id\":1,\"clip_id\":24,\"sort_order\":0}', '2026-01-08 08:58:58'),
(123, 1, 1, 'event', 213, 'create', NULL, '{\"id\":213,\"match_id\":1,\"period_id\":3,\"match_second\":2199,\"minute\":36,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-08 09:01:16\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-08 09:01:16'),
(124, 1, 1, 'clip', 25, 'create', NULL, '{\"id\":25,\"match_id\":1,\"event_id\":213,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Goal @ 2199s\",\"start_second\":2169,\"end_second\":2229,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2026-01-08 09:01:16\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null}', '2026-01-08 09:01:16'),
(125, 1, 1, 'clip', 25, 'generated', NULL, NULL, '2026-01-08 09:01:16'),
(126, 1, 1, 'playlist_clip', 1, 'add', NULL, '{\"playlist_id\":1,\"clip_id\":25,\"sort_order\":1}', '2026-01-08 09:01:31'),
(127, 1, 1, 'playlist_clip', 2, 'add', NULL, '{\"playlist_id\":2,\"clip_id\":25,\"sort_order\":1}', '2026-01-08 09:12:44'),
(128, 1, 1, 'playlist_clip', 1, 'remove', '{\"id\":24,\"match_id\":1,\"event_id\":212,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Corner @ 1221s\",\"start_second\":1191,\"end_second\":1251,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2026-01-08 08:43:18\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null,\"sort_order\":0}', NULL, '2026-01-08 09:35:06'),
(129, 1, 1, 'playlist_clip', 1, 'remove', '{\"id\":25,\"match_id\":1,\"event_id\":213,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Goal @ 2199s\",\"start_second\":2169,\"end_second\":2229,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2026-01-08 09:01:16\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null,\"sort_order\":1}', NULL, '2026-01-08 09:35:12'),
(130, 1, 1, 'playlist_clip', 1, 'add', NULL, '{\"playlist_id\":1,\"clip_id\":25,\"sort_order\":0}', '2026-01-08 09:35:23'),
(131, 1, 1, 'playlist_clip', 1, 'add', NULL, '{\"playlist_id\":1,\"clip_id\":24,\"sort_order\":1}', '2026-01-08 09:37:45'),
(132, 1, 1, 'playlist_clip', 1, 'remove', '{\"id\":25,\"match_id\":1,\"event_id\":213,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Goal @ 2199s\",\"start_second\":2169,\"end_second\":2229,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2026-01-08 09:01:16\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null,\"sort_order\":0}', NULL, '2026-01-08 09:37:51'),
(133, 1, 1, 'playlist_clip', 1, 'remove', '{\"id\":24,\"match_id\":1,\"event_id\":212,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Corner @ 1221s\",\"start_second\":1191,\"end_second\":1251,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2026-01-08 08:43:18\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null,\"sort_order\":1}', NULL, '2026-01-08 09:37:52'),
(134, 1, 1, 'playlist_clip', 1, 'add', NULL, '{\"playlist_id\":1,\"clip_id\":25,\"sort_order\":0}', '2026-01-08 09:37:59'),
(135, 1, 1, 'playlist_clip', 1, 'add', NULL, '{\"playlist_id\":1,\"clip_id\":24,\"sort_order\":1}', '2026-01-08 09:38:04'),
(136, 1, 1, 'playlist_clip', 2, 'remove', '{\"id\":24,\"match_id\":1,\"event_id\":212,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Corner @ 1221s\",\"start_second\":1191,\"end_second\":1251,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2026-01-08 08:43:18\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null,\"sort_order\":0}', NULL, '2026-01-08 09:58:27'),
(137, 1, 1, 'playlist_clip', 2, 'remove', '{\"id\":25,\"match_id\":1,\"event_id\":213,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Goal @ 2199s\",\"start_second\":2169,\"end_second\":2229,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2026-01-08 09:01:16\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null,\"sort_order\":1}', NULL, '2026-01-08 09:58:37'),
(138, 1, 1, 'playlist_clip', 1, 'remove', '{\"id\":25,\"match_id\":1,\"event_id\":213,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Goal @ 2199s\",\"start_second\":2169,\"end_second\":2229,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2026-01-08 09:01:16\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null,\"sort_order\":0}', NULL, '2026-01-08 09:58:42'),
(139, 1, 1, 'playlist_clip', 1, 'remove', '{\"id\":24,\"match_id\":1,\"event_id\":212,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Corner @ 1221s\",\"start_second\":1191,\"end_second\":1251,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2026-01-08 08:43:18\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null,\"sort_order\":1}', NULL, '2026-01-08 09:58:45'),
(140, 1, 1, 'playlist_clip', 2, 'add', NULL, '{\"playlist_id\":2,\"clip_id\":25,\"sort_order\":0}', '2026-01-08 09:58:51'),
(141, 1, 1, 'playlist_clip', 2, 'add', NULL, '{\"playlist_id\":2,\"clip_id\":24,\"sort_order\":1}', '2026-01-08 09:58:57'),
(142, 1, 1, 'event', 214, 'create', NULL, '{\"id\":214,\"match_id\":1,\"period_id\":3,\"match_second\":1191,\"minute\":19,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":17,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-08 10:11:12\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Foul\",\"event_type_key\":\"foul\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-08 10:11:12'),
(143, 1, 1, 'clip', 26, 'create', NULL, '{\"id\":26,\"match_id\":1,\"event_id\":214,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Foul @ 1191s\",\"start_second\":1161,\"end_second\":1221,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2026-01-08 10:11:12\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null}', '2026-01-08 10:11:12'),
(144, 1, 1, 'clip', 26, 'generated', NULL, NULL, '2026-01-08 10:11:12'),
(145, 1, 1, 'event', 215, 'create', NULL, '{\"id\":215,\"match_id\":1,\"period_id\":3,\"match_second\":1191,\"minute\":19,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":17,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-08 10:11:16\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Foul\",\"event_type_key\":\"foul\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-08 10:11:16'),
(146, 1, 1, 'clip', 27, 'create', NULL, '{\"id\":27,\"match_id\":1,\"event_id\":215,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Foul @ 1191s\",\"start_second\":1161,\"end_second\":1221,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2026-01-08 10:11:16\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null}', '2026-01-08 10:11:16'),
(147, 1, 1, 'clip', 27, 'generated', NULL, NULL, '2026-01-08 10:11:16'),
(148, 1, 1, 'event', 216, 'create', NULL, '{\"id\":216,\"match_id\":1,\"period_id\":3,\"match_second\":1191,\"minute\":19,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":17,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-08 10:11:18\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Foul\",\"event_type_key\":\"foul\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-08 10:11:18'),
(149, 1, 1, 'clip', 28, 'create', NULL, '{\"id\":28,\"match_id\":1,\"event_id\":216,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Foul @ 1191s\",\"start_second\":1161,\"end_second\":1221,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2026-01-08 10:11:18\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null}', '2026-01-08 10:11:18'),
(150, 1, 1, 'clip', 28, 'generated', NULL, NULL, '2026-01-08 10:11:18'),
(151, 1, 1, 'event', 217, 'create', NULL, '{\"id\":217,\"match_id\":1,\"period_id\":3,\"match_second\":1191,\"minute\":19,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":17,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-08 10:11:22\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Foul\",\"event_type_key\":\"foul\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-08 10:11:22'),
(152, 1, 1, 'clip', 29, 'create', NULL, '{\"id\":29,\"match_id\":1,\"event_id\":217,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Foul @ 1191s\",\"start_second\":1161,\"end_second\":1221,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2026-01-08 10:11:22\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null}', '2026-01-08 10:11:22'),
(153, 1, 1, 'clip', 29, 'generated', NULL, NULL, '2026-01-08 10:11:22'),
(154, 1, 1, 'event', 218, 'create', NULL, '{\"id\":218,\"match_id\":1,\"period_id\":null,\"match_second\":4713,\"minute\":78,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":17,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-08 10:18:34\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Foul\",\"event_type_key\":\"foul\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":null,\"tags\":[]}', '2026-01-08 10:18:34'),
(155, 1, 1, 'clip', 30, 'create', NULL, '{\"id\":30,\"match_id\":1,\"event_id\":218,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Foul @ 4713s\",\"start_second\":4683,\"end_second\":4743,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2026-01-08 10:18:34\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null}', '2026-01-08 10:18:34'),
(156, 1, 1, 'clip', 30, 'generated', NULL, NULL, '2026-01-08 10:18:34'),
(157, 1, 1, 'event', 219, 'create', NULL, '{\"id\":219,\"match_id\":1,\"period_id\":null,\"match_second\":4713,\"minute\":78,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":17,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-08 10:19:05\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Foul\",\"event_type_key\":\"foul\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":null,\"tags\":[]}', '2026-01-08 10:19:05'),
(158, 1, 1, 'clip', 31, 'create', NULL, '{\"id\":31,\"match_id\":1,\"event_id\":219,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Foul @ 4713s\",\"start_second\":4683,\"end_second\":4743,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2026-01-08 10:19:05\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null}', '2026-01-08 10:19:05'),
(159, 1, 1, 'clip', 31, 'generated', NULL, NULL, '2026-01-08 10:19:05'),
(160, 1, 2, 'event', 220, 'create', NULL, '{\"id\":220,\"match_id\":1,\"period_id\":3,\"match_second\":420,\"minute\":7,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":2,\"created_at\":\"2026-01-08 14:35:45\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-08 14:35:45'),
(161, 1, 2, 'clip', 32, 'create', NULL, '{\"id\":32,\"match_id\":1,\"event_id\":220,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Goal @ 420s\",\"start_second\":390,\"end_second\":450,\"duration_seconds\":60,\"created_by\":2,\"created_at\":\"2026-01-08 14:35:45\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null}', '2026-01-08 14:35:45'),
(162, 1, 2, 'clip', 32, 'generated', NULL, NULL, '2026-01-08 14:35:45'),
(163, 1, 2, 'event', 220, 'delete', '{\"id\":220,\"match_id\":1,\"period_id\":3,\"match_second\":420,\"minute\":7,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":2,\"created_at\":\"2026-01-08 14:35:45\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', NULL, '2026-01-08 14:37:43'),
(164, 1, 2, 'event', 221, 'create', NULL, '{\"id\":221,\"match_id\":1,\"period_id\":3,\"match_second\":420,\"minute\":7,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":2,\"created_at\":\"2026-01-08 14:37:48\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-08 14:37:48'),
(165, 1, 2, 'event', 221, 'delete', '{\"id\":221,\"match_id\":1,\"period_id\":3,\"match_second\":420,\"minute\":7,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":2,\"created_at\":\"2026-01-08 14:37:48\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', NULL, '2026-01-08 14:37:59'),
(166, 1, 2, 'event', 222, 'create', NULL, '{\"id\":222,\"match_id\":1,\"period_id\":3,\"match_second\":2657,\"minute\":44,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":17,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":2,\"created_at\":\"2026-01-08 14:44:56\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Foul\",\"event_type_key\":\"foul\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-08 14:44:56'),
(167, 1, 2, 'clip', 33, 'create', NULL, '{\"id\":33,\"match_id\":1,\"event_id\":222,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Foul @ 2657s\",\"start_second\":2627,\"end_second\":2687,\"duration_seconds\":60,\"created_by\":2,\"created_at\":\"2026-01-08 14:44:56\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null}', '2026-01-08 14:44:56'),
(168, 1, 2, 'clip', 33, 'generated', NULL, NULL, '2026-01-08 14:44:56'),
(169, 1, 2, 'event', 223, 'create', NULL, '{\"id\":223,\"match_id\":1,\"period_id\":null,\"match_second\":3953,\"minute\":65,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":3,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":2,\"created_at\":\"2026-01-08 14:45:27\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Chance\",\"event_type_key\":\"chance\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":null,\"tags\":[]}', '2026-01-08 14:45:27'),
(170, 1, 2, 'clip', 34, 'create', NULL, '{\"id\":34,\"match_id\":1,\"event_id\":223,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Chance @ 3953s\",\"start_second\":3923,\"end_second\":3983,\"duration_seconds\":60,\"created_by\":2,\"created_at\":\"2026-01-08 14:45:27\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null}', '2026-01-08 14:45:27'),
(171, 1, 2, 'clip', 34, 'generated', NULL, NULL, '2026-01-08 14:45:27'),
(172, 1, 2, 'event', 224, 'create', NULL, '{\"id\":224,\"match_id\":1,\"period_id\":null,\"match_second\":3977,\"minute\":66,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":3,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":2,\"created_at\":\"2026-01-08 14:45:50\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Chance\",\"event_type_key\":\"chance\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":null,\"tags\":[]}', '2026-01-08 14:45:50'),
(173, 1, 2, 'clip', 35, 'create', NULL, '{\"id\":35,\"match_id\":1,\"event_id\":224,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Chance @ 3977s\",\"start_second\":3947,\"end_second\":4007,\"duration_seconds\":60,\"created_by\":2,\"created_at\":\"2026-01-08 14:45:50\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null}', '2026-01-08 14:45:50'),
(174, 1, 2, 'clip', 35, 'generated', NULL, NULL, '2026-01-08 14:45:50'),
(175, 1, 2, 'event', 225, 'create', NULL, '{\"id\":225,\"match_id\":1,\"period_id\":null,\"match_second\":5666,\"minute\":94,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":2,\"created_at\":\"2026-01-08 14:46:55\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":null,\"tags\":[]}', '2026-01-08 14:46:55'),
(176, 1, 2, 'clip', 36, 'create', NULL, '{\"id\":36,\"match_id\":1,\"event_id\":225,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Shot @ 5666s\",\"start_second\":5636,\"end_second\":5696,\"duration_seconds\":60,\"created_by\":2,\"created_at\":\"2026-01-08 14:46:55\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null}', '2026-01-08 14:46:55'),
(177, 1, 2, 'clip', 36, 'generated', NULL, NULL, '2026-01-08 14:46:55'),
(178, 1, 2, 'event', 226, 'create', NULL, '{\"id\":226,\"match_id\":1,\"period_id\":null,\"match_second\":5670,\"minute\":94,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":4,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":2,\"created_at\":\"2026-01-08 14:46:59\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Corner\",\"event_type_key\":\"corner\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":null,\"tags\":[]}', '2026-01-08 14:46:59'),
(179, 1, 2, 'clip', 37, 'create', NULL, '{\"id\":37,\"match_id\":1,\"event_id\":226,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Corner @ 5670s\",\"start_second\":5640,\"end_second\":5700,\"duration_seconds\":60,\"created_by\":2,\"created_at\":\"2026-01-08 14:46:59\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null}', '2026-01-08 14:46:59'),
(180, 1, 2, 'clip', 37, 'generated', NULL, NULL, '2026-01-08 14:46:59'),
(181, 1, 2, 'event', 227, 'create', NULL, '{\"id\":227,\"match_id\":1,\"period_id\":null,\"match_second\":5731,\"minute\":95,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":2,\"created_at\":\"2026-01-08 14:47:43\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":null,\"tags\":[]}', '2026-01-08 14:47:43'),
(182, 1, 2, 'clip', 38, 'create', NULL, '{\"id\":38,\"match_id\":1,\"event_id\":227,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Goal @ 5731s\",\"start_second\":5701,\"end_second\":5761,\"duration_seconds\":60,\"created_by\":2,\"created_at\":\"2026-01-08 14:47:43\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null}', '2026-01-08 14:47:43'),
(183, 1, 2, 'clip', 38, 'generated', NULL, NULL, '2026-01-08 14:47:43'),
(184, 1, 2, 'event', 228, 'create', NULL, '{\"id\":228,\"match_id\":1,\"period_id\":null,\"match_second\":5875,\"minute\":97,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":3,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":2,\"created_at\":\"2026-01-08 14:48:01\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Chance\",\"event_type_key\":\"chance\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":null,\"tags\":[]}', '2026-01-08 14:48:01'),
(185, 1, 2, 'clip', 39, 'create', NULL, '{\"id\":39,\"match_id\":1,\"event_id\":228,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Chance @ 5875s\",\"start_second\":5845,\"end_second\":5905,\"duration_seconds\":60,\"created_by\":2,\"created_at\":\"2026-01-08 14:48:01\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null}', '2026-01-08 14:48:01'),
(186, 1, 2, 'clip', 39, 'generated', NULL, NULL, '2026-01-08 14:48:01'),
(187, 1, 2, 'event', 229, 'create', NULL, '{\"id\":229,\"match_id\":1,\"period_id\":null,\"match_second\":5888,\"minute\":98,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":4,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":2,\"created_at\":\"2026-01-08 14:48:12\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Corner\",\"event_type_key\":\"corner\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":null,\"tags\":[]}', '2026-01-08 14:48:12'),
(188, 1, 2, 'clip', 40, 'create', NULL, '{\"id\":40,\"match_id\":1,\"event_id\":229,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Corner @ 5888s\",\"start_second\":5858,\"end_second\":5918,\"duration_seconds\":60,\"created_by\":2,\"created_at\":\"2026-01-08 14:48:12\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null}', '2026-01-08 14:48:12'),
(189, 1, 2, 'clip', 40, 'generated', NULL, NULL, '2026-01-08 14:48:12');
INSERT INTO `audit_log` (`id`, `club_id`, `user_id`, `entity_type`, `entity_id`, `action`, `before_json`, `after_json`, `created_at`) VALUES
(190, 1, 2, 'event', 230, 'create', NULL, '{\"id\":230,\"match_id\":1,\"period_id\":null,\"match_second\":6040,\"minute\":100,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":2,\"created_at\":\"2026-01-08 14:48:20\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":null,\"tags\":[]}', '2026-01-08 14:48:20'),
(191, 1, 2, 'clip', 41, 'create', NULL, '{\"id\":41,\"match_id\":1,\"event_id\":230,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Free Kick @ 6040s\",\"start_second\":6010,\"end_second\":6070,\"duration_seconds\":60,\"created_by\":2,\"created_at\":\"2026-01-08 14:48:20\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null}', '2026-01-08 14:48:20'),
(192, 1, 2, 'clip', 41, 'generated', NULL, NULL, '2026-01-08 14:48:20'),
(193, 1, 1, 'event', 231, 'create', NULL, '{\"id\":231,\"match_id\":1,\"period_id\":3,\"match_second\":2169,\"minute\":36,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-09 11:31:22\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-09 11:31:22'),
(194, 1, 1, 'clip', 42, 'create', NULL, '{\"id\":42,\"match_id\":1,\"event_id\":231,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Goal @ 2169s\",\"start_second\":2139,\"end_second\":2199,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2026-01-09 11:31:22\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null}', '2026-01-09 11:31:22'),
(195, 1, 1, 'clip', 42, 'generated', NULL, NULL, '2026-01-09 11:31:22'),
(196, 1, 1, 'playlist_clip', 2, 'add', NULL, '{\"playlist_id\":2,\"clip_id\":36,\"sort_order\":2}', '2026-01-09 11:42:38'),
(197, 1, 1, 'event', 232, 'create', NULL, '{\"id\":232,\"match_id\":1,\"period_id\":3,\"match_second\":7,\"minute\":0,\"minute_extra\":0,\"team_side\":\"unknown\",\"event_type_id\":13,\"importance\":1,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":\"First Half\",\"created_by\":1,\"created_at\":\"2026-01-09 11:43:56\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Period Start\",\"event_type_key\":\"period_start\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-09 11:43:56'),
(198, 1, 1, 'playlist', 2, 'rename', '{\"id\":2,\"match_id\":1,\"title\":\"test\",\"notes\":null,\"created_at\":\"2026-01-05 17:17:11\",\"updated_at\":null,\"deleted_at\":null,\"team_sides\":[]}', '{\"id\":2,\"match_id\":1,\"title\":\"Goals\",\"notes\":null,\"created_at\":\"2026-01-05 17:17:11\",\"updated_at\":\"2026-01-09 12:40:40\",\"deleted_at\":null,\"team_sides\":[]}', '2026-01-09 12:40:40'),
(199, 1, 1, 'playlist', 1, 'rename', '{\"id\":1,\"match_id\":1,\"title\":\"test\",\"notes\":null,\"created_at\":\"2026-01-05 17:16:05\",\"updated_at\":null,\"deleted_at\":null,\"team_sides\":[]}', '{\"id\":1,\"match_id\":1,\"title\":\"Croners\",\"notes\":null,\"created_at\":\"2026-01-05 17:16:05\",\"updated_at\":\"2026-01-09 12:40:46\",\"deleted_at\":null,\"team_sides\":[]}', '2026-01-09 12:40:46'),
(200, 1, 1, 'playlist_clip', 2, 'add', NULL, '{\"playlist_id\":2,\"clip_id\":29,\"sort_order\":3}', '2026-01-09 12:51:18'),
(201, 1, 1, 'event', 233, 'create', NULL, '{\"id\":233,\"match_id\":1,\"period_id\":null,\"match_second\":0,\"minute\":0,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"match_player_id\":14,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-09 13:59:59\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":\"A. Kamara\",\"match_player_shirt\":null,\"match_player_team_side\":\"home\",\"match_player_position\":\"ST\",\"period_label\":null,\"tags\":[]}', '2026-01-09 13:59:59'),
(202, 1, 1, 'clip', 43, 'create', NULL, '{\"id\":43,\"match_id\":1,\"event_id\":233,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Goal\",\"start_second\":0,\"end_second\":30,\"duration_seconds\":30,\"created_by\":1,\"created_at\":\"2026-01-09 13:59:59\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null}', '2026-01-09 13:59:59'),
(203, 1, 1, 'clip', 43, 'generated', NULL, NULL, '2026-01-09 13:59:59'),
(204, 1, 1, 'event', 233, 'delete', '{\"id\":233,\"match_id\":1,\"period_id\":null,\"match_second\":0,\"minute\":0,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"match_player_id\":14,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-09 13:59:59\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":43,\"clip_start_second\":0,\"clip_end_second\":30,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":\"A. Kamara\",\"match_player_shirt\":null,\"match_player_team_side\":\"home\",\"match_player_position\":\"ST\",\"period_label\":null,\"tags\":[]}', NULL, '2026-01-09 14:00:30'),
(205, 1, 1, 'event', 213, 'delete', '{\"id\":213,\"match_id\":1,\"period_id\":3,\"match_second\":2199,\"minute\":36,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-08 09:01:16\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":25,\"clip_start_second\":2169,\"clip_end_second\":2229,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', NULL, '2026-01-09 14:00:39'),
(206, 1, 1, 'event', 231, 'delete', '{\"id\":231,\"match_id\":1,\"period_id\":3,\"match_second\":2169,\"minute\":36,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-09 11:31:22\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":42,\"clip_start_second\":2139,\"clip_end_second\":2199,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', NULL, '2026-01-09 14:00:48'),
(207, 1, 1, 'event', 227, 'delete', '{\"id\":227,\"match_id\":1,\"period_id\":null,\"match_second\":5731,\"minute\":95,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":2,\"created_at\":\"2026-01-08 14:47:43\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":38,\"clip_start_second\":5701,\"clip_end_second\":5761,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":null,\"tags\":[]}', NULL, '2026-01-09 14:00:55'),
(208, 1, 1, 'event', 234, 'create', NULL, '{\"id\":234,\"match_id\":1,\"period_id\":3,\"match_second\":1814,\"minute\":30,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"match_player_id\":17,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-09 14:01:10\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":\"A. Tait\",\"match_player_shirt\":null,\"match_player_team_side\":\"home\",\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-09 14:01:10'),
(209, 1, 1, 'clip', 44, 'create', NULL, '{\"id\":44,\"match_id\":1,\"event_id\":234,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Goal @ 1814s\",\"start_second\":1784,\"end_second\":1844,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2026-01-09 14:01:10\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null}', '2026-01-09 14:01:10'),
(210, 1, 1, 'clip', 44, 'generated', NULL, NULL, '2026-01-09 14:01:10'),
(211, 1, 1, 'event', 225, 'delete', '{\"id\":225,\"match_id\":1,\"period_id\":null,\"match_second\":5666,\"minute\":94,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":2,\"created_at\":\"2026-01-08 14:46:55\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":36,\"clip_start_second\":5636,\"clip_end_second\":5696,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":null,\"tags\":[]}', NULL, '2026-01-09 14:19:21'),
(212, 1, 1, 'event', 212, 'delete', '{\"id\":212,\"match_id\":1,\"period_id\":3,\"match_second\":1221,\"minute\":20,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":4,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-08 08:43:18\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":24,\"clip_start_second\":1191,\"clip_end_second\":1251,\"event_type_label\":\"Corner\",\"event_type_key\":\"corner\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', NULL, '2026-01-09 14:22:21'),
(213, 1, 1, 'event', 217, 'delete', '{\"id\":217,\"match_id\":1,\"period_id\":3,\"match_second\":1191,\"minute\":19,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":17,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-08 10:11:22\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":29,\"clip_start_second\":1161,\"clip_end_second\":1221,\"event_type_label\":\"Foul\",\"event_type_key\":\"foul\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', NULL, '2026-01-09 14:26:25'),
(214, 1, 1, 'event', 222, 'delete', '{\"id\":222,\"match_id\":1,\"period_id\":3,\"match_second\":2657,\"minute\":44,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":17,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":2,\"created_at\":\"2026-01-08 14:44:56\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":33,\"clip_start_second\":2627,\"clip_end_second\":2687,\"event_type_label\":\"Foul\",\"event_type_key\":\"foul\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', NULL, '2026-01-09 14:26:31'),
(215, 1, 1, 'event', 216, 'delete', '{\"id\":216,\"match_id\":1,\"period_id\":3,\"match_second\":1191,\"minute\":19,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":17,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-08 10:11:18\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":28,\"clip_start_second\":1161,\"clip_end_second\":1221,\"event_type_label\":\"Foul\",\"event_type_key\":\"foul\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', NULL, '2026-01-09 14:26:38'),
(216, 1, 1, 'event', 230, 'delete', '{\"id\":230,\"match_id\":1,\"period_id\":null,\"match_second\":6040,\"minute\":100,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":2,\"created_at\":\"2026-01-08 14:48:20\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":41,\"clip_start_second\":6010,\"clip_end_second\":6070,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":null,\"tags\":[]}', NULL, '2026-01-09 14:26:47'),
(217, 1, 1, 'event', 229, 'delete', '{\"id\":229,\"match_id\":1,\"period_id\":null,\"match_second\":5888,\"minute\":98,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":4,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":2,\"created_at\":\"2026-01-08 14:48:12\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":40,\"clip_start_second\":5858,\"clip_end_second\":5918,\"event_type_label\":\"Corner\",\"event_type_key\":\"corner\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":null,\"tags\":[]}', NULL, '2026-01-09 14:26:53'),
(218, 1, 1, 'event', 226, 'delete', '{\"id\":226,\"match_id\":1,\"period_id\":null,\"match_second\":5670,\"minute\":94,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":4,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":2,\"created_at\":\"2026-01-08 14:46:59\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":37,\"clip_start_second\":5640,\"clip_end_second\":5700,\"event_type_label\":\"Corner\",\"event_type_key\":\"corner\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":null,\"tags\":[]}', NULL, '2026-01-09 14:26:58'),
(219, 1, 1, 'event', 224, 'delete', '{\"id\":224,\"match_id\":1,\"period_id\":null,\"match_second\":3977,\"minute\":66,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":3,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":2,\"created_at\":\"2026-01-08 14:45:50\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":35,\"clip_start_second\":3947,\"clip_end_second\":4007,\"event_type_label\":\"Chance\",\"event_type_key\":\"chance\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":null,\"tags\":[]}', NULL, '2026-01-09 14:27:04'),
(220, 1, 1, 'event', 228, 'delete', '{\"id\":228,\"match_id\":1,\"period_id\":null,\"match_second\":5875,\"minute\":97,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":3,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":2,\"created_at\":\"2026-01-08 14:48:01\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":39,\"clip_start_second\":5845,\"clip_end_second\":5905,\"event_type_label\":\"Chance\",\"event_type_key\":\"chance\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":null,\"tags\":[]}', NULL, '2026-01-09 14:27:10'),
(221, 1, 1, 'event', 223, 'delete', '{\"id\":223,\"match_id\":1,\"period_id\":null,\"match_second\":3953,\"minute\":65,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":3,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":2,\"created_at\":\"2026-01-08 14:45:27\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":34,\"clip_start_second\":3923,\"clip_end_second\":3983,\"event_type_label\":\"Chance\",\"event_type_key\":\"chance\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":null,\"tags\":[]}', NULL, '2026-01-09 14:27:15'),
(222, 1, 1, 'event', 215, 'delete', '{\"id\":215,\"match_id\":1,\"period_id\":3,\"match_second\":1191,\"minute\":19,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":17,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-08 10:11:16\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":27,\"clip_start_second\":1161,\"clip_end_second\":1221,\"event_type_label\":\"Foul\",\"event_type_key\":\"foul\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', NULL, '2026-01-09 14:27:20'),
(223, 1, 1, 'event', 219, 'delete', '{\"id\":219,\"match_id\":1,\"period_id\":null,\"match_second\":4713,\"minute\":78,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":17,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-08 10:19:05\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":31,\"clip_start_second\":4683,\"clip_end_second\":4743,\"event_type_label\":\"Foul\",\"event_type_key\":\"foul\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":null,\"tags\":[]}', NULL, '2026-01-09 14:27:27'),
(224, 1, 1, 'event', 214, 'delete', '{\"id\":214,\"match_id\":1,\"period_id\":3,\"match_second\":1191,\"minute\":19,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":17,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-08 10:11:12\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":26,\"clip_start_second\":1161,\"clip_end_second\":1221,\"event_type_label\":\"Foul\",\"event_type_key\":\"foul\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', NULL, '2026-01-09 14:27:41'),
(225, 1, 1, 'event', 218, 'delete', '{\"id\":218,\"match_id\":1,\"period_id\":null,\"match_second\":4713,\"minute\":78,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":17,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-08 10:18:34\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":30,\"clip_start_second\":4683,\"clip_end_second\":4743,\"event_type_label\":\"Foul\",\"event_type_key\":\"foul\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":null,\"tags\":[]}', NULL, '2026-01-09 14:27:48'),
(226, 1, 1, 'event', 235, 'create', NULL, '{\"id\":235,\"match_id\":1,\"period_id\":3,\"match_second\":4713,\"minute\":78,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":14,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"on_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-09 14:28:14\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"A. Kamara\",\"match_player_shirt\":null,\"match_player_team_side\":\"home\",\"match_player_position\":\"ST\",\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-09 14:28:14'),
(227, 1, 1, 'clip', 45, 'create', NULL, '{\"id\":45,\"match_id\":1,\"event_id\":235,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Shot @ 4713s\",\"start_second\":4683,\"end_second\":4743,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2026-01-09 14:28:14\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null}', '2026-01-09 14:28:14'),
(228, 1, 1, 'clip', 45, 'generated', NULL, NULL, '2026-01-09 14:28:14'),
(229, 1, 1, 'event', 235, 'delete', '{\"id\":235,\"match_id\":1,\"period_id\":3,\"match_second\":4713,\"minute\":78,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":14,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"on_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-09 14:28:14\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":45,\"clip_start_second\":4683,\"clip_end_second\":4743,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"A. Kamara\",\"match_player_shirt\":null,\"match_player_team_side\":\"home\",\"match_player_position\":\"ST\",\"period_label\":\"First Half\",\"tags\":[]}', NULL, '2026-01-09 14:28:41'),
(230, 1, 1, 'event', 236, 'create', NULL, '{\"id\":236,\"match_id\":1,\"period_id\":3,\"match_second\":4713,\"minute\":78,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":14,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"on_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-09 14:28:46\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"A. Kamara\",\"match_player_shirt\":null,\"match_player_team_side\":\"home\",\"match_player_position\":\"ST\",\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-09 14:28:46'),
(231, 1, 1, 'clip', 46, 'create', NULL, '{\"id\":46,\"match_id\":1,\"event_id\":236,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Shot @ 4713s\",\"start_second\":4683,\"end_second\":4743,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2026-01-09 14:28:46\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null}', '2026-01-09 14:28:46'),
(232, 1, 1, 'clip', 46, 'generated', NULL, NULL, '2026-01-09 14:28:46'),
(233, 1, 1, 'event', 236, 'delete', '{\"id\":236,\"match_id\":1,\"period_id\":3,\"match_second\":4713,\"minute\":78,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":14,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"on_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-09 14:28:46\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":46,\"clip_start_second\":4683,\"clip_end_second\":4743,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"A. Kamara\",\"match_player_shirt\":null,\"match_player_team_side\":\"home\",\"match_player_position\":\"ST\",\"period_label\":\"First Half\",\"tags\":[]}', NULL, '2026-01-09 14:31:38'),
(234, 1, 1, 'event', 237, 'create', NULL, '{\"id\":237,\"match_id\":1,\"period_id\":3,\"match_second\":1671,\"minute\":27,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":17,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"on_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-09 14:36:08\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"A. Tait\",\"match_player_shirt\":null,\"match_player_team_side\":\"home\",\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-09 14:36:08'),
(235, 1, 1, 'clip', 47, 'create', NULL, '{\"id\":47,\"match_id\":1,\"event_id\":237,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Shot @ 1671s\",\"start_second\":1641,\"end_second\":1701,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2026-01-09 14:36:08\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null}', '2026-01-09 14:36:08'),
(236, 1, 1, 'clip', 47, 'generated', NULL, NULL, '2026-01-09 14:36:08'),
(237, 1, 1, 'event', 238, 'create', NULL, '{\"id\":238,\"match_id\":1,\"period_id\":3,\"match_second\":2274,\"minute\":37,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-09 14:50:03\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-09 14:50:03'),
(238, 1, 1, 'clip', 48, 'create', NULL, '{\"id\":48,\"match_id\":1,\"event_id\":238,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Goal @ 2274s\",\"start_second\":2244,\"end_second\":2304,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2026-01-09 14:50:03\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null}', '2026-01-09 14:50:03'),
(239, 1, 1, 'clip', 48, 'generated', NULL, NULL, '2026-01-09 14:50:03'),
(240, 1, 1, 'event', 239, 'create', NULL, '{\"id\":239,\"match_id\":1,\"period_id\":3,\"match_second\":2274,\"minute\":37,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"on_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-09 14:50:20\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-09 14:50:20'),
(241, 1, 1, 'clip', 49, 'create', NULL, '{\"id\":49,\"match_id\":1,\"event_id\":239,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Shot @ 2274s\",\"start_second\":2244,\"end_second\":2304,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2026-01-09 14:50:20\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null}', '2026-01-09 14:50:20'),
(242, 1, 1, 'clip', 49, 'generated', NULL, NULL, '2026-01-09 14:50:20'),
(243, 1, 1, 'event', 239, 'delete', '{\"id\":239,\"match_id\":1,\"period_id\":3,\"match_second\":2274,\"minute\":37,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"on_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-09 14:50:20\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":49,\"clip_start_second\":2244,\"clip_end_second\":2304,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', NULL, '2026-01-09 14:50:46'),
(244, 1, 1, 'event', 237, 'delete', '{\"id\":237,\"match_id\":1,\"period_id\":3,\"match_second\":1671,\"minute\":27,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":17,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"on_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-09 14:36:08\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":47,\"clip_start_second\":1641,\"clip_end_second\":1701,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"A. Tait\",\"match_player_shirt\":null,\"match_player_team_side\":\"home\",\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', NULL, '2026-01-09 14:50:51'),
(245, 1, 1, 'event', 234, 'delete', '{\"id\":234,\"match_id\":1,\"period_id\":3,\"match_second\":1814,\"minute\":30,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"match_player_id\":17,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-09 14:01:10\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":44,\"clip_start_second\":1784,\"clip_end_second\":1844,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":\"A. Tait\",\"match_player_shirt\":null,\"match_player_team_side\":\"home\",\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', NULL, '2026-01-09 14:51:01'),
(246, 1, 1, 'event', 238, 'delete', '{\"id\":238,\"match_id\":1,\"period_id\":3,\"match_second\":2274,\"minute\":37,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-09 14:50:03\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":48,\"clip_start_second\":2244,\"clip_end_second\":2304,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', NULL, '2026-01-09 14:51:09'),
(247, 1, 1, 'event', 240, 'create', NULL, '{\"id\":240,\"match_id\":1,\"period_id\":3,\"match_second\":2820,\"minute\":47,\"minute_extra\":0,\"team_side\":\"unknown\",\"event_type_id\":14,\"importance\":1,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":\"First Half\",\"created_by\":1,\"created_at\":\"2026-01-09 14:52:50\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Period End\",\"event_type_key\":\"period_end\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-09 14:52:50'),
(248, 1, 1, 'event', 241, 'create', NULL, '{\"id\":241,\"match_id\":1,\"period_id\":3,\"match_second\":2820,\"minute\":47,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":3,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-09 14:57:11\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Chance\",\"event_type_key\":\"chance\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-09 14:57:11'),
(249, 1, 1, 'clip', 50, 'create', NULL, '{\"id\":50,\"match_id\":1,\"event_id\":241,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Chance @ 2820s\",\"start_second\":2790,\"end_second\":2850,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2026-01-09 14:57:11\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null}', '2026-01-09 14:57:11'),
(250, 1, 1, 'clip', 50, 'generated', NULL, NULL, '2026-01-09 14:57:11'),
(251, 1, 1, 'event', 242, 'create', NULL, '{\"id\":242,\"match_id\":1,\"period_id\":3,\"match_second\":2820,\"minute\":47,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":8,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":18,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-09 15:24:25\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Yellow Card\",\"event_type_key\":\"yellow_card\",\"match_player_name\":\"B. McCullough\",\"match_player_shirt\":null,\"match_player_team_side\":\"home\",\"match_player_position\":\"CM\",\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-09 15:24:25'),
(252, 1, 1, 'clip', 51, 'create', NULL, '{\"id\":51,\"match_id\":1,\"event_id\":242,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Yellow Card @ 2820s\",\"start_second\":2790,\"end_second\":2850,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2026-01-09 15:24:25\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null}', '2026-01-09 15:24:25'),
(253, 1, 1, 'clip', 51, 'generated', NULL, NULL, '2026-01-09 15:24:25'),
(254, 1, 2, 'event', 243, 'create', NULL, '{\"id\":243,\"match_id\":1,\"period_id\":3,\"match_second\":1770,\"minute\":29,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":17,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":2,\"created_at\":\"2026-01-09 17:21:03\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Foul\",\"event_type_key\":\"foul\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-09 17:21:03'),
(255, 1, 2, 'clip', 52, 'create', NULL, '{\"id\":52,\"match_id\":1,\"event_id\":243,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Foul @ 1770s\",\"start_second\":1740,\"end_second\":1800,\"duration_seconds\":60,\"created_by\":2,\"created_at\":\"2026-01-09 17:21:03\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null}', '2026-01-09 17:21:03'),
(256, 1, 2, 'clip', 52, 'generated', NULL, NULL, '2026-01-09 17:21:03'),
(257, 1, 2, 'event', 243, 'delete', '{\"id\":243,\"match_id\":1,\"period_id\":3,\"match_second\":1770,\"minute\":29,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":17,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":2,\"created_at\":\"2026-01-09 17:21:03\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":52,\"clip_start_second\":1740,\"clip_end_second\":1800,\"event_type_label\":\"Foul\",\"event_type_key\":\"foul\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', NULL, '2026-01-09 17:22:04'),
(258, 1, 2, 'event', 244, 'create', NULL, '{\"id\":244,\"match_id\":1,\"period_id\":3,\"match_second\":1770,\"minute\":29,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":17,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":2,\"created_at\":\"2026-01-09 17:22:09\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Foul\",\"event_type_key\":\"foul\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-09 17:22:09'),
(259, 1, 2, 'event', 245, 'create', NULL, '{\"id\":245,\"match_id\":1,\"period_id\":null,\"match_second\":3705,\"minute\":61,\"minute_extra\":0,\"team_side\":\"unknown\",\"event_type_id\":13,\"importance\":1,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":\"Second Half\",\"created_by\":2,\"created_at\":\"2026-01-09 17:27:10\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Period Start\",\"event_type_key\":\"period_start\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":null,\"tags\":[]}', '2026-01-09 17:27:10'),
(260, 1, 1, 'playlist_clip', 2, 'add', NULL, '{\"playlist_id\":2,\"clip_id\":51,\"sort_order\":0}', '2026-01-10 11:48:16'),
(261, 1, 1, 'playlist', 2, 'delete', '{\"id\":2,\"match_id\":1,\"title\":\"Goals\",\"notes\":null,\"created_at\":\"2026-01-05 17:17:11\",\"updated_at\":\"2026-01-09 12:40:40\",\"deleted_at\":null,\"team_sides\":[]}', NULL, '2026-01-10 11:56:33'),
(262, 1, 1, 'playlist', 3, 'create', NULL, '{\"id\":3,\"match_id\":1,\"title\":\"Test\",\"notes\":null,\"created_at\":\"2026-01-10 11:57:26\",\"updated_at\":null,\"deleted_at\":null,\"team_sides\":[]}', '2026-01-10 11:57:26'),
(263, 1, 1, 'playlist', 3, 'delete', '{\"id\":3,\"match_id\":1,\"title\":\"Test\",\"notes\":null,\"created_at\":\"2026-01-10 11:57:26\",\"updated_at\":null,\"deleted_at\":null,\"team_sides\":[]}', NULL, '2026-01-10 11:59:31'),
(264, 1, 1, 'playlist', 1, 'delete', '{\"id\":1,\"match_id\":1,\"title\":\"Croners\",\"notes\":null,\"created_at\":\"2026-01-05 17:16:05\",\"updated_at\":\"2026-01-09 12:40:46\",\"deleted_at\":null,\"team_sides\":[]}', NULL, '2026-01-10 11:59:35'),
(265, 1, 1, 'playlist', 4, 'create', NULL, '{\"id\":4,\"match_id\":1,\"title\":\"Goal\",\"notes\":null,\"created_at\":\"2026-01-10 15:25:18\",\"updated_at\":null,\"deleted_at\":null,\"team_sides\":[]}', '2026-01-10 15:25:18'),
(266, 1, 1, 'playlist_clip', 4, 'add', NULL, '{\"playlist_id\":4,\"clip_id\":50,\"sort_order\":0}', '2026-01-10 15:25:30'),
(267, 1, 1, 'event', 246, 'create', NULL, '{\"id\":246,\"match_id\":1,\"period_id\":4,\"match_second\":6588,\"minute\":109,\"minute_extra\":0,\"team_side\":\"unknown\",\"event_type_id\":14,\"importance\":1,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":\"Second Half\",\"created_by\":1,\"created_at\":\"2026-01-10 15:58:14\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Period End\",\"event_type_key\":\"period_end\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"Second Half\",\"tags\":[]}', '2026-01-10 15:58:14'),
(268, 1, 1, 'event', 247, 'create', NULL, '{\"id\":247,\"match_id\":1,\"period_id\":4,\"match_second\":4147,\"minute\":69,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":12,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-13 16:40:59\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Highlight\",\"event_type_key\":\"highlight\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"Second Half\",\"tags\":[]}', '2026-01-13 16:40:59'),
(269, 1, 1, 'clip', 53, 'create', NULL, '{\"id\":53,\"match_id\":1,\"event_id\":247,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Highlight @ 4147s\",\"start_second\":4117,\"end_second\":4177,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2026-01-13 16:40:59\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null}', '2026-01-13 16:40:59'),
(270, 1, 1, 'clip', 53, 'generated', NULL, NULL, '2026-01-13 16:40:59'),
(271, 1, 1, 'event', 248, 'create', NULL, '{\"id\":248,\"match_id\":1,\"period_id\":4,\"match_second\":4148,\"minute\":69,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":12,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-13 16:40:59\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Highlight\",\"event_type_key\":\"highlight\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"Second Half\",\"tags\":[]}', '2026-01-13 16:40:59'),
(272, 1, 1, 'clip', 54, 'create', NULL, '{\"id\":54,\"match_id\":1,\"event_id\":248,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Highlight @ 4148s\",\"start_second\":4118,\"end_second\":4178,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2026-01-13 16:40:59\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null}', '2026-01-13 16:40:59'),
(273, 1, 1, 'clip', 54, 'generated', NULL, NULL, '2026-01-13 16:40:59'),
(274, 1, 1, 'event', 248, 'delete', '{\"id\":248,\"match_id\":1,\"period_id\":4,\"match_second\":4148,\"minute\":69,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":12,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-13 16:40:59\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":54,\"clip_start_second\":4118,\"clip_end_second\":4178,\"event_type_label\":\"Highlight\",\"event_type_key\":\"highlight\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"Second Half\",\"tags\":[]}', NULL, '2026-01-13 16:41:14'),
(275, 1, 1, 'event', 249, 'create', NULL, '{\"id\":249,\"match_id\":1,\"period_id\":4,\"match_second\":4321,\"minute\":72,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"match_player_id\":14,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-14 18:12:49\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":\"A. Kamara\",\"match_player_shirt\":null,\"match_player_team_side\":\"home\",\"match_player_position\":\"ST\",\"period_label\":\"Second Half\",\"tags\":[]}', '2026-01-14 18:12:49'),
(276, 1, 1, 'clip', 55, 'create', NULL, '{\"id\":55,\"match_id\":1,\"event_id\":249,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Goal @ 4321s\",\"start_second\":4291,\"end_second\":4351,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2026-01-14 18:12:49\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null}', '2026-01-14 18:12:49'),
(277, 1, 1, 'clip', 55, 'generated', NULL, NULL, '2026-01-14 18:12:49'),
(278, 1, 1, 'event', 242, 'update', '{\"id\":242,\"match_id\":1,\"period_id\":3,\"match_second\":2820,\"minute\":47,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":8,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-09 15:24:25\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":51,\"clip_start_second\":2790,\"clip_end_second\":2850,\"event_type_label\":\"Yellow Card\",\"event_type_key\":\"yellow_card\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '{\"id\":242,\"match_id\":1,\"period_id\":3,\"match_second\":2820,\"minute\":47,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":8,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":40,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-09 15:24:25\",\"updated_by\":null,\"updated_at\":\"2026-01-16 06:59:57\",\"match_period_id\":null,\"clip_id\":51,\"clip_start_second\":2790,\"clip_end_second\":2850,\"event_type_label\":\"Yellow Card\",\"event_type_key\":\"yellow_card\",\"match_player_name\":\"C. Robertson\",\"match_player_shirt\":1,\"match_player_team_side\":\"home\",\"match_player_position\":\"GK\",\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-16 06:59:57'),
(279, 1, 1, 'event', 242, 'update', '{\"id\":242,\"match_id\":1,\"period_id\":3,\"match_second\":2820,\"minute\":47,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":8,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":40,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-09 15:24:25\",\"updated_by\":null,\"updated_at\":\"2026-01-16 06:59:57\",\"match_period_id\":null,\"clip_id\":51,\"clip_start_second\":2790,\"clip_end_second\":2850,\"event_type_label\":\"Yellow Card\",\"event_type_key\":\"yellow_card\",\"match_player_name\":\"C. Robertson\",\"match_player_shirt\":1,\"match_player_team_side\":\"home\",\"match_player_position\":\"GK\",\"period_label\":\"First Half\",\"tags\":[]}', '{\"id\":242,\"match_id\":1,\"period_id\":3,\"match_second\":2820,\"minute\":47,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":8,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":40,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-09 15:24:25\",\"updated_by\":null,\"updated_at\":\"2026-01-16 06:59:57\",\"match_period_id\":null,\"clip_id\":51,\"clip_start_second\":2790,\"clip_end_second\":2850,\"event_type_label\":\"Yellow Card\",\"event_type_key\":\"yellow_card\",\"match_player_name\":\"C. Robertson\",\"match_player_shirt\":1,\"match_player_team_side\":\"home\",\"match_player_position\":\"GK\",\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-16 06:59:59');
INSERT INTO `audit_log` (`id`, `club_id`, `user_id`, `entity_type`, `entity_id`, `action`, `before_json`, `after_json`, `created_at`) VALUES
(280, 1, 1, 'event', 242, 'update', '{\"id\":242,\"match_id\":1,\"period_id\":3,\"match_second\":2820,\"minute\":47,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":8,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":40,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-09 15:24:25\",\"updated_by\":null,\"updated_at\":\"2026-01-16 06:59:57\",\"match_period_id\":null,\"clip_id\":51,\"clip_start_second\":2790,\"clip_end_second\":2850,\"event_type_label\":\"Yellow Card\",\"event_type_key\":\"yellow_card\",\"match_player_name\":\"C. Robertson\",\"match_player_shirt\":1,\"match_player_team_side\":\"home\",\"match_player_position\":\"GK\",\"period_label\":\"First Half\",\"tags\":[]}', '{\"id\":242,\"match_id\":1,\"period_id\":3,\"match_second\":2820,\"minute\":47,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":8,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":40,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-09 15:24:25\",\"updated_by\":null,\"updated_at\":\"2026-01-16 06:59:57\",\"match_period_id\":null,\"clip_id\":51,\"clip_start_second\":2790,\"clip_end_second\":2850,\"event_type_label\":\"Yellow Card\",\"event_type_key\":\"yellow_card\",\"match_player_name\":\"C. Robertson\",\"match_player_shirt\":1,\"match_player_team_side\":\"home\",\"match_player_position\":\"GK\",\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-16 06:59:59'),
(281, 1, 1, 'event', 242, 'update', '{\"id\":242,\"match_id\":1,\"period_id\":3,\"match_second\":2820,\"minute\":47,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":8,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":40,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-09 15:24:25\",\"updated_by\":null,\"updated_at\":\"2026-01-16 06:59:57\",\"match_period_id\":null,\"clip_id\":51,\"clip_start_second\":2790,\"clip_end_second\":2850,\"event_type_label\":\"Yellow Card\",\"event_type_key\":\"yellow_card\",\"match_player_name\":\"C. Robertson\",\"match_player_shirt\":1,\"match_player_team_side\":\"home\",\"match_player_position\":\"GK\",\"period_label\":\"First Half\",\"tags\":[]}', '{\"id\":242,\"match_id\":1,\"period_id\":3,\"match_second\":2820,\"minute\":47,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":8,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":40,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-09 15:24:25\",\"updated_by\":null,\"updated_at\":\"2026-01-16 06:59:57\",\"match_period_id\":null,\"clip_id\":51,\"clip_start_second\":2790,\"clip_end_second\":2850,\"event_type_label\":\"Yellow Card\",\"event_type_key\":\"yellow_card\",\"match_player_name\":\"C. Robertson\",\"match_player_shirt\":1,\"match_player_team_side\":\"home\",\"match_player_position\":\"GK\",\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-16 07:00:00'),
(282, 1, 1, 'event', 242, 'update', '{\"id\":242,\"match_id\":1,\"period_id\":3,\"match_second\":2820,\"minute\":47,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":8,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":40,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-09 15:24:25\",\"updated_by\":null,\"updated_at\":\"2026-01-16 06:59:57\",\"match_period_id\":null,\"clip_id\":51,\"clip_start_second\":2790,\"clip_end_second\":2850,\"event_type_label\":\"Yellow Card\",\"event_type_key\":\"yellow_card\",\"match_player_name\":\"C. Robertson\",\"match_player_shirt\":1,\"match_player_team_side\":\"home\",\"match_player_position\":\"GK\",\"period_label\":\"First Half\",\"tags\":[]}', '{\"id\":242,\"match_id\":1,\"period_id\":3,\"match_second\":2820,\"minute\":47,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":8,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":40,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-09 15:24:25\",\"updated_by\":null,\"updated_at\":\"2026-01-16 06:59:57\",\"match_period_id\":null,\"clip_id\":51,\"clip_start_second\":2790,\"clip_end_second\":2850,\"event_type_label\":\"Yellow Card\",\"event_type_key\":\"yellow_card\",\"match_player_name\":\"C. Robertson\",\"match_player_shirt\":1,\"match_player_team_side\":\"home\",\"match_player_position\":\"GK\",\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-16 07:00:00'),
(283, 1, 1, 'event', 242, 'update', '{\"id\":242,\"match_id\":1,\"period_id\":3,\"match_second\":2820,\"minute\":47,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":8,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":40,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-09 15:24:25\",\"updated_by\":null,\"updated_at\":\"2026-01-16 06:59:57\",\"match_period_id\":null,\"clip_id\":51,\"clip_start_second\":2790,\"clip_end_second\":2850,\"event_type_label\":\"Yellow Card\",\"event_type_key\":\"yellow_card\",\"match_player_name\":\"C. Robertson\",\"match_player_shirt\":1,\"match_player_team_side\":\"home\",\"match_player_position\":\"GK\",\"period_label\":\"First Half\",\"tags\":[]}', '{\"id\":242,\"match_id\":1,\"period_id\":3,\"match_second\":2820,\"minute\":47,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":8,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":40,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-09 15:24:25\",\"updated_by\":null,\"updated_at\":\"2026-01-16 06:59:57\",\"match_period_id\":null,\"clip_id\":51,\"clip_start_second\":2790,\"clip_end_second\":2850,\"event_type_label\":\"Yellow Card\",\"event_type_key\":\"yellow_card\",\"match_player_name\":\"C. Robertson\",\"match_player_shirt\":1,\"match_player_team_side\":\"home\",\"match_player_position\":\"GK\",\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-16 07:00:05'),
(284, 1, 1, 'event', 242, 'update', '{\"id\":242,\"match_id\":1,\"period_id\":3,\"match_second\":2820,\"minute\":47,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":8,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":40,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-09 15:24:25\",\"updated_by\":null,\"updated_at\":\"2026-01-16 06:59:57\",\"match_period_id\":null,\"clip_id\":51,\"clip_start_second\":2790,\"clip_end_second\":2850,\"event_type_label\":\"Yellow Card\",\"event_type_key\":\"yellow_card\",\"match_player_name\":\"C. Robertson\",\"match_player_shirt\":1,\"match_player_team_side\":\"home\",\"match_player_position\":\"GK\",\"period_label\":\"First Half\",\"tags\":[]}', '{\"id\":242,\"match_id\":1,\"period_id\":3,\"match_second\":2820,\"minute\":47,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":8,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":40,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-09 15:24:25\",\"updated_by\":null,\"updated_at\":\"2026-01-16 06:59:57\",\"match_period_id\":null,\"clip_id\":51,\"clip_start_second\":2790,\"clip_end_second\":2850,\"event_type_label\":\"Yellow Card\",\"event_type_key\":\"yellow_card\",\"match_player_name\":\"C. Robertson\",\"match_player_shirt\":1,\"match_player_team_side\":\"home\",\"match_player_position\":\"GK\",\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-16 07:00:06'),
(285, 1, 1, 'event', 242, 'update', '{\"id\":242,\"match_id\":1,\"period_id\":3,\"match_second\":2820,\"minute\":47,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":8,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":40,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-09 15:24:25\",\"updated_by\":null,\"updated_at\":\"2026-01-16 06:59:57\",\"match_period_id\":null,\"clip_id\":51,\"clip_start_second\":2790,\"clip_end_second\":2850,\"event_type_label\":\"Yellow Card\",\"event_type_key\":\"yellow_card\",\"match_player_name\":\"C. Robertson\",\"match_player_shirt\":1,\"match_player_team_side\":\"home\",\"match_player_position\":\"GK\",\"period_label\":\"First Half\",\"tags\":[]}', '{\"id\":242,\"match_id\":1,\"period_id\":3,\"match_second\":2820,\"minute\":47,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":8,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":40,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-09 15:24:25\",\"updated_by\":null,\"updated_at\":\"2026-01-16 06:59:57\",\"match_period_id\":null,\"clip_id\":51,\"clip_start_second\":2790,\"clip_end_second\":2850,\"event_type_label\":\"Yellow Card\",\"event_type_key\":\"yellow_card\",\"match_player_name\":\"C. Robertson\",\"match_player_shirt\":1,\"match_player_team_side\":\"home\",\"match_player_position\":\"GK\",\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-16 07:00:06'),
(286, 1, 1, 'event', 242, 'update', '{\"id\":242,\"match_id\":1,\"period_id\":3,\"match_second\":2820,\"minute\":47,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":8,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":40,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-09 15:24:25\",\"updated_by\":null,\"updated_at\":\"2026-01-16 06:59:57\",\"match_period_id\":null,\"clip_id\":51,\"clip_start_second\":2790,\"clip_end_second\":2850,\"event_type_label\":\"Yellow Card\",\"event_type_key\":\"yellow_card\",\"match_player_name\":\"C. Robertson\",\"match_player_shirt\":1,\"match_player_team_side\":\"home\",\"match_player_position\":\"GK\",\"period_label\":\"First Half\",\"tags\":[]}', '{\"id\":242,\"match_id\":1,\"period_id\":3,\"match_second\":2820,\"minute\":47,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":8,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":40,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-09 15:24:25\",\"updated_by\":null,\"updated_at\":\"2026-01-16 06:59:57\",\"match_period_id\":null,\"clip_id\":51,\"clip_start_second\":2790,\"clip_end_second\":2850,\"event_type_label\":\"Yellow Card\",\"event_type_key\":\"yellow_card\",\"match_player_name\":\"C. Robertson\",\"match_player_shirt\":1,\"match_player_team_side\":\"home\",\"match_player_position\":\"GK\",\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-16 07:00:47'),
(287, 1, 1, 'event', 242, 'update', '{\"id\":242,\"match_id\":1,\"period_id\":3,\"match_second\":2820,\"minute\":47,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":8,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":40,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-09 15:24:25\",\"updated_by\":null,\"updated_at\":\"2026-01-16 06:59:57\",\"match_period_id\":null,\"clip_id\":51,\"clip_start_second\":2790,\"clip_end_second\":2850,\"event_type_label\":\"Yellow Card\",\"event_type_key\":\"yellow_card\",\"match_player_name\":\"C. Robertson\",\"match_player_shirt\":1,\"match_player_team_side\":\"home\",\"match_player_position\":\"GK\",\"period_label\":\"First Half\",\"tags\":[]}', '{\"id\":242,\"match_id\":1,\"period_id\":3,\"match_second\":2820,\"minute\":47,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":8,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":40,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-09 15:24:25\",\"updated_by\":null,\"updated_at\":\"2026-01-16 06:59:57\",\"match_period_id\":null,\"clip_id\":51,\"clip_start_second\":2790,\"clip_end_second\":2850,\"event_type_label\":\"Yellow Card\",\"event_type_key\":\"yellow_card\",\"match_player_name\":\"C. Robertson\",\"match_player_shirt\":1,\"match_player_team_side\":\"home\",\"match_player_position\":\"GK\",\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-16 07:00:47'),
(288, 1, 1, 'event', 242, 'update', '{\"id\":242,\"match_id\":1,\"period_id\":3,\"match_second\":2820,\"minute\":47,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":8,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":40,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-09 15:24:25\",\"updated_by\":null,\"updated_at\":\"2026-01-16 06:59:57\",\"match_period_id\":null,\"clip_id\":51,\"clip_start_second\":2790,\"clip_end_second\":2850,\"event_type_label\":\"Yellow Card\",\"event_type_key\":\"yellow_card\",\"match_player_name\":\"C. Robertson\",\"match_player_shirt\":1,\"match_player_team_side\":\"home\",\"match_player_position\":\"GK\",\"period_label\":\"First Half\",\"tags\":[]}', '{\"id\":242,\"match_id\":1,\"period_id\":3,\"match_second\":2820,\"minute\":47,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":8,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":40,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-09 15:24:25\",\"updated_by\":null,\"updated_at\":\"2026-01-16 06:59:57\",\"match_period_id\":null,\"clip_id\":51,\"clip_start_second\":2790,\"clip_end_second\":2850,\"event_type_label\":\"Yellow Card\",\"event_type_key\":\"yellow_card\",\"match_player_name\":\"C. Robertson\",\"match_player_shirt\":1,\"match_player_team_side\":\"home\",\"match_player_position\":\"GK\",\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-16 07:00:47'),
(289, 1, 1, 'event', 242, 'update', '{\"id\":242,\"match_id\":1,\"period_id\":3,\"match_second\":2820,\"minute\":47,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":8,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":40,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-09 15:24:25\",\"updated_by\":null,\"updated_at\":\"2026-01-16 06:59:57\",\"match_period_id\":null,\"clip_id\":51,\"clip_start_second\":2790,\"clip_end_second\":2850,\"event_type_label\":\"Yellow Card\",\"event_type_key\":\"yellow_card\",\"match_player_name\":\"C. Robertson\",\"match_player_shirt\":1,\"match_player_team_side\":\"home\",\"match_player_position\":\"GK\",\"period_label\":\"First Half\",\"tags\":[]}', '{\"id\":242,\"match_id\":1,\"period_id\":3,\"match_second\":2820,\"minute\":47,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":8,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":40,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-09 15:24:25\",\"updated_by\":null,\"updated_at\":\"2026-01-16 06:59:57\",\"match_period_id\":null,\"clip_id\":51,\"clip_start_second\":2790,\"clip_end_second\":2850,\"event_type_label\":\"Yellow Card\",\"event_type_key\":\"yellow_card\",\"match_player_name\":\"C. Robertson\",\"match_player_shirt\":1,\"match_player_team_side\":\"home\",\"match_player_position\":\"GK\",\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-16 07:06:32'),
(290, 1, 1, 'event', 241, 'update', '{\"id\":241,\"match_id\":1,\"period_id\":3,\"match_second\":2820,\"minute\":47,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":3,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-09 14:57:11\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":50,\"clip_start_second\":2790,\"clip_end_second\":2850,\"event_type_label\":\"Chance\",\"event_type_key\":\"chance\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '{\"id\":241,\"match_id\":1,\"period_id\":3,\"match_second\":2820,\"minute\":47,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":3,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":46,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-09 14:57:11\",\"updated_by\":null,\"updated_at\":\"2026-01-16 07:07:04\",\"match_period_id\":null,\"clip_id\":50,\"clip_start_second\":2790,\"clip_end_second\":2850,\"event_type_label\":\"Chance\",\"event_type_key\":\"chance\",\"match_player_name\":\"J. Hanlon\",\"match_player_shirt\":7,\"match_player_team_side\":\"home\",\"match_player_position\":\"RM\",\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-16 07:07:04'),
(291, 1, 1, 'event', 244, 'update', '{\"id\":244,\"match_id\":1,\"period_id\":3,\"match_second\":1770,\"minute\":29,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":17,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":2,\"created_at\":\"2026-01-09 17:22:09\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Foul\",\"event_type_key\":\"foul\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '{\"id\":244,\"match_id\":1,\"period_id\":3,\"match_second\":1770,\"minute\":29,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":17,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":2,\"created_at\":\"2026-01-09 17:22:09\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Foul\",\"event_type_key\":\"foul\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-16 07:18:39'),
(292, 1, 2, 'clip', 56, 'create', NULL, '{\"id\":56,\"match_id\":1,\"event_id\":244,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Foul @ 1770s\",\"start_second\":1740,\"end_second\":1800,\"duration_seconds\":60,\"created_by\":2,\"created_at\":\"2026-01-16 07:18:39\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null}', '2026-01-16 07:18:39'),
(293, 1, 2, 'clip', 56, 'generated', NULL, NULL, '2026-01-16 07:18:39'),
(294, 1, 1, 'event', 244, 'update', '{\"id\":244,\"match_id\":1,\"period_id\":3,\"match_second\":1770,\"minute\":29,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":17,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":2,\"created_at\":\"2026-01-09 17:22:09\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":56,\"clip_start_second\":1740,\"clip_end_second\":1800,\"event_type_label\":\"Foul\",\"event_type_key\":\"foul\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '{\"id\":244,\"match_id\":1,\"period_id\":3,\"match_second\":1770,\"minute\":29,\"minute_extra\":0,\"team_side\":\"unknown\",\"event_type_id\":17,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":47,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":2,\"created_at\":\"2026-01-09 17:22:09\",\"updated_by\":null,\"updated_at\":\"2026-01-16 07:46:36\",\"match_period_id\":null,\"clip_id\":56,\"clip_start_second\":1740,\"clip_end_second\":1800,\"event_type_label\":\"Foul\",\"event_type_key\":\"foul\",\"match_player_name\":\"R. Johnston\",\"match_player_shirt\":8,\"match_player_team_side\":\"home\",\"match_player_position\":\"CM\",\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-16 07:46:36'),
(295, 1, 1, 'event', 257, 'create', NULL, '{\"id\":257,\"match_id\":1,\"period_id\":3,\"match_second\":1627,\"minute\":27,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"match_player_id\":46,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-16 21:47:06\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":\"J. Hanlon\",\"match_player_shirt\":7,\"match_player_team_side\":\"home\",\"match_player_position\":\"RM\",\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-16 21:47:06'),
(296, 1, 1, 'clip', 57, 'create', NULL, '{\"id\":57,\"match_id\":1,\"event_id\":257,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Goal @ 1627s\",\"start_second\":1597,\"end_second\":1657,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2026-01-16 21:47:06\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null}', '2026-01-16 21:47:06'),
(297, 1, 1, 'clip', 57, 'generated', NULL, NULL, '2026-01-16 21:47:06'),
(298, 1, 1, 'playlist_clip', 4, 'add', NULL, '{\"playlist_id\":4,\"clip_id\":57,\"sort_order\":1}', '2026-01-16 21:50:09'),
(299, 1, 1, 'event', 258, 'create', NULL, '{\"id\":258,\"match_id\":3,\"period_id\":5,\"match_second\":14,\"minute\":0,\"minute_extra\":0,\"team_side\":\"unknown\",\"event_type_id\":13,\"importance\":1,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":\"First Half\",\"created_by\":1,\"created_at\":\"2026-01-17 20:45:26\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Period Start\",\"event_type_key\":\"period_start\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 20:45:26'),
(300, 1, 1, 'playlist', 5, 'create', NULL, '{\"id\":5,\"match_id\":3,\"title\":\"Goals\",\"notes\":null,\"created_at\":\"2026-01-17 20:59:19\",\"updated_at\":null,\"deleted_at\":null,\"team_sides\":[]}', '2026-01-17 20:59:19'),
(301, 1, 1, 'event', 259, 'create', NULL, '{\"id\":259,\"match_id\":3,\"period_id\":5,\"match_second\":188,\"minute\":3,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":69,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"off_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 21:08:40\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"A. Robertson\",\"match_player_shirt\":11,\"match_player_team_side\":\"home\",\"match_player_position\":\"ST\",\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 21:08:40'),
(302, 1, 1, 'event', 260, 'create', NULL, '{\"id\":260,\"match_id\":3,\"period_id\":5,\"match_second\":264,\"minute\":4,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":68,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"on_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 21:09:25\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"D. Sawyer\",\"match_player_shirt\":10,\"match_player_team_side\":\"home\",\"match_player_position\":\"ST\",\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 21:09:25'),
(303, 1, 1, 'event', 261, 'create', NULL, '{\"id\":261,\"match_id\":3,\"period_id\":5,\"match_second\":272,\"minute\":4,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":17,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 21:09:43\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Foul\",\"event_type_key\":\"foul\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 21:09:43'),
(304, 1, 1, 'event', 262, 'create', NULL, '{\"id\":262,\"match_id\":3,\"period_id\":5,\"match_second\":290,\"minute\":4,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":17,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 21:10:53\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Foul\",\"event_type_key\":\"foul\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 21:10:53'),
(305, 1, 1, 'event', 263, 'create', NULL, '{\"id\":263,\"match_id\":3,\"period_id\":5,\"match_second\":297,\"minute\":4,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 21:10:59\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 21:10:59'),
(306, 1, 1, 'event', 264, 'create', NULL, '{\"id\":264,\"match_id\":3,\"period_id\":5,\"match_second\":309,\"minute\":5,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":62,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"off_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 21:12:30\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"J. Stirling\",\"match_player_shirt\":4,\"match_player_team_side\":\"home\",\"match_player_position\":\"CB\",\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 21:12:30'),
(307, 1, 1, 'event', 265, 'create', NULL, '{\"id\":265,\"match_id\":3,\"period_id\":5,\"match_second\":570,\"minute\":9,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 21:18:39\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 21:18:39'),
(308, 1, 1, 'event', 266, 'create', NULL, '{\"id\":266,\"match_id\":3,\"period_id\":5,\"match_second\":670,\"minute\":11,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 21:20:55\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 21:20:55'),
(309, 1, 1, 'event', 267, 'create', NULL, '{\"id\":267,\"match_id\":3,\"period_id\":5,\"match_second\":824,\"minute\":13,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":4,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 21:23:33\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Corner\",\"event_type_key\":\"corner\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 21:23:33'),
(310, 1, 1, 'event', 268, 'create', NULL, '{\"id\":268,\"match_id\":3,\"period_id\":5,\"match_second\":851,\"minute\":14,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":4,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 21:23:59\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Corner\",\"event_type_key\":\"corner\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 21:23:59'),
(311, 1, 1, 'event', 268, 'delete', '{\"id\":268,\"match_id\":3,\"period_id\":5,\"match_second\":851,\"minute\":14,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":4,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 21:23:59\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Corner\",\"event_type_key\":\"corner\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', NULL, '2026-01-17 21:25:17'),
(312, 1, 1, 'event', 269, 'create', NULL, '{\"id\":269,\"match_id\":3,\"period_id\":5,\"match_second\":999,\"minute\":16,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":4,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 21:27:59\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Corner\",\"event_type_key\":\"corner\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 21:27:59'),
(313, 1, 1, 'event', 270, 'create', NULL, '{\"id\":270,\"match_id\":3,\"period_id\":5,\"match_second\":1158,\"minute\":19,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 21:30:54\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 21:30:54'),
(314, 1, 1, 'event', 271, 'create', NULL, '{\"id\":271,\"match_id\":3,\"period_id\":5,\"match_second\":1303,\"minute\":21,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 21:33:44\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 21:33:44'),
(315, 1, 1, 'event', 272, 'create', NULL, '{\"id\":272,\"match_id\":3,\"period_id\":5,\"match_second\":1332,\"minute\":22,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 21:40:06\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 21:40:06'),
(316, 1, 1, 'event', 273, 'create', NULL, '{\"id\":273,\"match_id\":3,\"period_id\":5,\"match_second\":1464,\"minute\":24,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 21:42:38\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 21:42:38'),
(317, 1, 1, 'event', 274, 'create', NULL, '{\"id\":274,\"match_id\":3,\"period_id\":5,\"match_second\":1516,\"minute\":25,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":69,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"on_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 21:43:37\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"A. Robertson\",\"match_player_shirt\":11,\"match_player_team_side\":\"home\",\"match_player_position\":\"ST\",\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 21:43:37'),
(318, 1, 1, 'event', 275, 'create', NULL, '{\"id\":275,\"match_id\":3,\"period_id\":5,\"match_second\":1516,\"minute\":25,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":4,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 21:43:41\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Corner\",\"event_type_key\":\"corner\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 21:43:41'),
(319, 1, 1, 'event', 276, 'create', NULL, '{\"id\":276,\"match_id\":3,\"period_id\":5,\"match_second\":1536,\"minute\":25,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":60,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"off_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 21:44:13\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"R. Agnew\",\"match_player_shirt\":2,\"match_player_team_side\":\"home\",\"match_player_position\":\"CB\",\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 21:44:13'),
(320, 1, 1, 'event', 277, 'create', NULL, '{\"id\":277,\"match_id\":3,\"period_id\":5,\"match_second\":1612,\"minute\":26,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 21:45:30\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 21:45:30'),
(321, 1, 1, 'event', 278, 'create', NULL, '{\"id\":278,\"match_id\":3,\"period_id\":5,\"match_second\":1684,\"minute\":28,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"on_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 21:47:13\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 21:47:13'),
(322, 1, 1, 'event', 279, 'create', NULL, '{\"id\":279,\"match_id\":3,\"period_id\":5,\"match_second\":1730,\"minute\":28,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 21:48:20\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 21:48:20'),
(323, 1, 1, 'event', 280, 'create', NULL, '{\"id\":280,\"match_id\":3,\"period_id\":5,\"match_second\":1771,\"minute\":29,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":62,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"off_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 21:52:40\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"J. Stirling\",\"match_player_shirt\":4,\"match_player_team_side\":\"home\",\"match_player_position\":\"CB\",\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 21:52:40'),
(324, 1, 1, 'event', 281, 'create', NULL, '{\"id\":281,\"match_id\":3,\"period_id\":5,\"match_second\":1994,\"minute\":33,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"off_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 21:56:25\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 21:56:25'),
(325, 1, 1, 'event', 282, 'create', NULL, '{\"id\":282,\"match_id\":3,\"period_id\":5,\"match_second\":2134,\"minute\":35,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"match_player_id\":66,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 21:59:00\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":\"B. McCullough\",\"match_player_shirt\":8,\"match_player_team_side\":\"home\",\"match_player_position\":\"CM\",\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 21:59:00'),
(326, 1, 1, 'event', 283, 'create', NULL, '{\"id\":283,\"match_id\":3,\"period_id\":5,\"match_second\":363,\"minute\":6,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"off_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 22:06:10\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 22:06:10'),
(327, 1, 1, 'event', 284, 'create', NULL, '{\"id\":284,\"match_id\":3,\"period_id\":5,\"match_second\":584,\"minute\":9,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 22:09:52\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 22:09:52'),
(328, 1, 1, 'event', 284, 'delete', '{\"id\":284,\"match_id\":3,\"period_id\":5,\"match_second\":584,\"minute\":9,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 22:09:52\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', NULL, '2026-01-17 22:10:30'),
(329, 1, 1, 'event', 285, 'create', NULL, '{\"id\":285,\"match_id\":3,\"period_id\":5,\"match_second\":2312,\"minute\":38,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":64,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"off_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 22:15:53\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"R. Johnston\",\"match_player_shirt\":6,\"match_player_team_side\":\"home\",\"match_player_position\":\"CM\",\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 22:15:53'),
(330, 1, 1, 'event', 286, 'create', NULL, '{\"id\":286,\"match_id\":3,\"period_id\":5,\"match_second\":2392,\"minute\":39,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 22:17:13\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 22:17:13'),
(331, 1, 1, 'event', 287, 'create', NULL, '{\"id\":287,\"match_id\":3,\"period_id\":5,\"match_second\":2398,\"minute\":39,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":8,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 22:17:20\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Yellow Card\",\"event_type_key\":\"yellow_card\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 22:17:20'),
(332, 1, 1, 'event', 288, 'create', NULL, '{\"id\":288,\"match_id\":3,\"period_id\":5,\"match_second\":2552,\"minute\":42,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 22:21:04\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 22:21:04'),
(333, 1, 1, 'event', 289, 'create', NULL, '{\"id\":289,\"match_id\":3,\"period_id\":5,\"match_second\":2661,\"minute\":44,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"match_player_id\":68,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 22:22:55\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":\"D. Sawyer\",\"match_player_shirt\":10,\"match_player_team_side\":\"home\",\"match_player_position\":\"ST\",\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 22:22:55'),
(334, 1, 1, 'event', 290, 'create', NULL, '{\"id\":290,\"match_id\":3,\"period_id\":5,\"match_second\":2710,\"minute\":45,\"minute_extra\":0,\"team_side\":\"unknown\",\"event_type_id\":14,\"importance\":1,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":\"First Half\",\"created_by\":1,\"created_at\":\"2026-01-17 22:23:51\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Period End\",\"event_type_key\":\"period_end\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 22:23:51');
INSERT INTO `audit_log` (`id`, `club_id`, `user_id`, `entity_type`, `entity_id`, `action`, `before_json`, `after_json`, `created_at`) VALUES
(335, 1, 1, 'event', 291, 'create', NULL, '{\"id\":291,\"match_id\":3,\"period_id\":5,\"match_second\":3526,\"minute\":58,\"minute_extra\":14,\"team_side\":\"unknown\",\"event_type_id\":13,\"importance\":1,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":\"Second Half\",\"created_by\":1,\"created_at\":\"2026-01-17 22:26:56\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Period Start\",\"event_type_key\":\"period_start\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 22:26:56'),
(336, 1, 1, 'event', 292, 'create', NULL, '{\"id\":292,\"match_id\":3,\"period_id\":5,\"match_second\":3617,\"minute\":60,\"minute_extra\":16,\"team_side\":\"home\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 22:28:30\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 22:28:30'),
(337, 1, 1, 'event', 293, 'create', NULL, '{\"id\":293,\"match_id\":3,\"period_id\":5,\"match_second\":3663,\"minute\":61,\"minute_extra\":16,\"team_side\":\"home\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":65,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"off_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 22:29:46\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"J. Hanlon\",\"match_player_shirt\":7,\"match_player_team_side\":\"home\",\"match_player_position\":\"CM\",\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 22:29:46'),
(338, 1, 1, 'event', 294, 'create', NULL, '{\"id\":294,\"match_id\":3,\"period_id\":5,\"match_second\":3922,\"minute\":65,\"minute_extra\":21,\"team_side\":\"home\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":69,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"on_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 22:46:46\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"A. Robertson\",\"match_player_shirt\":11,\"match_player_team_side\":\"home\",\"match_player_position\":\"ST\",\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 22:46:46'),
(339, 1, 1, 'event', 295, 'create', NULL, '{\"id\":295,\"match_id\":3,\"period_id\":5,\"match_second\":3979,\"minute\":66,\"minute_extra\":22,\"team_side\":\"home\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":60,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"off_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 22:47:47\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"R. Agnew\",\"match_player_shirt\":2,\"match_player_team_side\":\"home\",\"match_player_position\":\"CB\",\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 22:47:47'),
(340, 1, 1, 'event', 296, 'create', NULL, '{\"id\":296,\"match_id\":3,\"period_id\":5,\"match_second\":4193,\"minute\":69,\"minute_extra\":25,\"team_side\":\"home\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 22:51:21\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 22:51:21'),
(341, 1, 1, 'event', 297, 'create', NULL, '{\"id\":297,\"match_id\":3,\"period_id\":5,\"match_second\":4287,\"minute\":71,\"minute_extra\":27,\"team_side\":\"home\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":65,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"off_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 22:52:58\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"J. Hanlon\",\"match_player_shirt\":7,\"match_player_team_side\":\"home\",\"match_player_position\":\"CM\",\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 22:52:58'),
(342, 1, 1, 'event', 298, 'create', NULL, '{\"id\":298,\"match_id\":3,\"period_id\":5,\"match_second\":4447,\"minute\":74,\"minute_extra\":29,\"team_side\":\"away\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"on_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 22:55:41\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 22:55:41'),
(343, 1, 1, 'event', 299, 'create', NULL, '{\"id\":299,\"match_id\":3,\"period_id\":5,\"match_second\":4521,\"minute\":75,\"minute_extra\":31,\"team_side\":\"away\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 22:56:55\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 22:56:55'),
(344, 1, 1, 'event', 300, 'create', NULL, '{\"id\":300,\"match_id\":3,\"period_id\":5,\"match_second\":4623,\"minute\":77,\"minute_extra\":32,\"team_side\":\"home\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 22:58:37\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 22:58:37'),
(345, 1, 1, 'event', 301, 'create', NULL, '{\"id\":301,\"match_id\":3,\"period_id\":5,\"match_second\":4669,\"minute\":77,\"minute_extra\":33,\"team_side\":\"home\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":65,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"off_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 22:59:37\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"J. Hanlon\",\"match_player_shirt\":7,\"match_player_team_side\":\"home\",\"match_player_position\":\"CM\",\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 22:59:37'),
(346, 1, 1, 'event', 302, 'create', NULL, '{\"id\":302,\"match_id\":3,\"period_id\":5,\"match_second\":4806,\"minute\":80,\"minute_extra\":35,\"team_side\":\"home\",\"event_type_id\":12,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 23:02:09\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Highlight\",\"event_type_key\":\"highlight\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 23:02:09'),
(347, 1, 1, 'event', 303, 'create', NULL, '{\"id\":303,\"match_id\":3,\"period_id\":5,\"match_second\":4982,\"minute\":83,\"minute_extra\":38,\"team_side\":\"home\",\"event_type_id\":4,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 23:05:05\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Corner\",\"event_type_key\":\"corner\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 23:05:05'),
(348, 1, 1, 'event', 304, 'create', NULL, '{\"id\":304,\"match_id\":3,\"period_id\":5,\"match_second\":5013,\"minute\":83,\"minute_extra\":39,\"team_side\":\"home\",\"event_type_id\":4,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 23:05:36\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Corner\",\"event_type_key\":\"corner\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 23:05:36'),
(349, 1, 1, 'event', 305, 'create', NULL, '{\"id\":305,\"match_id\":3,\"period_id\":5,\"match_second\":5071,\"minute\":84,\"minute_extra\":40,\"team_side\":\"home\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":64,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"off_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 23:06:40\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"R. Johnston\",\"match_player_shirt\":6,\"match_player_team_side\":\"home\",\"match_player_position\":\"CM\",\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 23:06:40'),
(350, 1, 1, 'event', 306, 'create', NULL, '{\"id\":306,\"match_id\":3,\"period_id\":5,\"match_second\":5125,\"minute\":85,\"minute_extra\":41,\"team_side\":\"away\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 23:08:10\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 23:08:10'),
(351, 1, 1, 'event', 307, 'create', NULL, '{\"id\":307,\"match_id\":3,\"period_id\":5,\"match_second\":5218,\"minute\":86,\"minute_extra\":42,\"team_side\":\"home\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 23:09:58\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 23:09:58'),
(352, 1, 1, 'event', 308, 'create', NULL, '{\"id\":308,\"match_id\":3,\"period_id\":5,\"match_second\":5223,\"minute\":87,\"minute_extra\":42,\"team_side\":\"home\",\"event_type_id\":8,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":60,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 23:12:30\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Yellow Card\",\"event_type_key\":\"yellow_card\",\"match_player_name\":\"R. Agnew\",\"match_player_shirt\":2,\"match_player_team_side\":\"home\",\"match_player_position\":\"CB\",\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 23:12:30'),
(353, 1, 1, 'event', 309, 'create', NULL, '{\"id\":309,\"match_id\":3,\"period_id\":5,\"match_second\":5331,\"minute\":88,\"minute_extra\":44,\"team_side\":\"home\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":68,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"off_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 23:18:10\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"D. Sawyer\",\"match_player_shirt\":10,\"match_player_team_side\":\"home\",\"match_player_position\":\"ST\",\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 23:18:10'),
(354, 1, 1, 'event', 310, 'create', NULL, '{\"id\":310,\"match_id\":3,\"period_id\":5,\"match_second\":5396,\"minute\":89,\"minute_extra\":45,\"team_side\":\"home\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 23:19:15\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 23:19:15'),
(355, 1, 1, 'event', 311, 'create', NULL, '{\"id\":311,\"match_id\":3,\"period_id\":5,\"match_second\":5426,\"minute\":90,\"minute_extra\":46,\"team_side\":\"home\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":65,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"off_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 23:19:48\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"J. Hanlon\",\"match_player_shirt\":7,\"match_player_team_side\":\"home\",\"match_player_position\":\"CM\",\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 23:19:48'),
(356, 1, 1, 'event', 312, 'create', NULL, '{\"id\":312,\"match_id\":3,\"period_id\":5,\"match_second\":5446,\"minute\":90,\"minute_extra\":46,\"team_side\":\"home\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"match_player_id\":60,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 23:20:10\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":\"R. Agnew\",\"match_player_shirt\":2,\"match_player_team_side\":\"home\",\"match_player_position\":\"CB\",\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 23:20:10'),
(357, 1, 1, 'event', 313, 'create', NULL, '{\"id\":313,\"match_id\":3,\"period_id\":5,\"match_second\":5570,\"minute\":92,\"minute_extra\":48,\"team_side\":\"home\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 23:22:09\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 23:22:09'),
(358, 1, 1, 'event', 314, 'create', NULL, '{\"id\":314,\"match_id\":3,\"period_id\":5,\"match_second\":5663,\"minute\":94,\"minute_extra\":50,\"team_side\":\"home\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":62,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"off_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 23:23:45\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"J. Stirling\",\"match_player_shirt\":4,\"match_player_team_side\":\"home\",\"match_player_position\":\"CB\",\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 23:23:45'),
(359, 1, 1, 'event', 315, 'create', NULL, '{\"id\":315,\"match_id\":3,\"period_id\":5,\"match_second\":5691,\"minute\":94,\"minute_extra\":50,\"team_side\":\"home\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 23:24:13\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 23:24:13'),
(360, 1, 1, 'event', 316, 'create', NULL, '{\"id\":316,\"match_id\":3,\"period_id\":5,\"match_second\":5777,\"minute\":96,\"minute_extra\":52,\"team_side\":\"home\",\"event_type_id\":4,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 23:25:43\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Corner\",\"event_type_key\":\"corner\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 23:25:43'),
(361, 1, 1, 'event', 317, 'create', NULL, '{\"id\":317,\"match_id\":3,\"period_id\":5,\"match_second\":5442,\"minute\":90,\"minute_extra\":46,\"team_side\":\"home\",\"event_type_id\":4,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 23:26:09\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Corner\",\"event_type_key\":\"corner\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 23:26:09'),
(362, 1, 1, 'event', 318, 'create', NULL, '{\"id\":318,\"match_id\":3,\"period_id\":5,\"match_second\":5801,\"minute\":96,\"minute_extra\":52,\"team_side\":\"home\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":70,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"on_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 23:26:55\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"A. Love\",\"match_player_shirt\":null,\"match_player_team_side\":\"home\",\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 23:26:55'),
(363, 1, 1, 'event', 319, 'create', NULL, '{\"id\":319,\"match_id\":3,\"period_id\":5,\"match_second\":5839,\"minute\":97,\"minute_extra\":53,\"team_side\":\"away\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 23:27:33\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 23:27:33'),
(364, 1, 1, 'event', 320, 'create', NULL, '{\"id\":320,\"match_id\":3,\"period_id\":5,\"match_second\":6006,\"minute\":100,\"minute_extra\":55,\"team_side\":\"home\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 23:30:21\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 23:30:21'),
(365, 1, 1, 'event', 321, 'create', NULL, '{\"id\":321,\"match_id\":3,\"period_id\":5,\"match_second\":6033,\"minute\":100,\"minute_extra\":56,\"team_side\":\"home\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 23:30:55\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 23:30:55'),
(366, 1, 1, 'event', 322, 'create', NULL, '{\"id\":322,\"match_id\":3,\"period_id\":5,\"match_second\":6081,\"minute\":101,\"minute_extra\":57,\"team_side\":\"home\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"match_player_id\":65,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 23:31:47\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":\"J. Hanlon\",\"match_player_shirt\":7,\"match_player_team_side\":\"home\",\"match_player_position\":\"CM\",\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 23:31:47'),
(367, 1, 1, 'event', 323, 'create', NULL, '{\"id\":323,\"match_id\":3,\"period_id\":5,\"match_second\":6180,\"minute\":103,\"minute_extra\":58,\"team_side\":\"home\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":68,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"off_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 23:33:24\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"D. Sawyer\",\"match_player_shirt\":10,\"match_player_team_side\":\"home\",\"match_player_position\":\"ST\",\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 23:33:24'),
(368, 1, 1, 'event', 324, 'create', NULL, '{\"id\":324,\"match_id\":3,\"period_id\":5,\"match_second\":6241,\"minute\":104,\"minute_extra\":59,\"team_side\":\"home\",\"event_type_id\":12,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 23:35:58\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Highlight\",\"event_type_key\":\"highlight\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 23:35:58'),
(369, 1, 1, 'event', 324, 'delete', '{\"id\":324,\"match_id\":3,\"period_id\":5,\"match_second\":6241,\"minute\":104,\"minute_extra\":59,\"team_side\":\"home\",\"event_type_id\":12,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 23:35:58\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Highlight\",\"event_type_key\":\"highlight\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', NULL, '2026-01-17 23:36:53'),
(370, 1, 1, 'event', 325, 'create', NULL, '{\"id\":325,\"match_id\":3,\"period_id\":5,\"match_second\":6241,\"minute\":104,\"minute_extra\":59,\"team_side\":\"unknown\",\"event_type_id\":13,\"importance\":1,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":\"Second Half\",\"created_by\":1,\"created_at\":\"2026-01-17 23:37:21\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Period Start\",\"event_type_key\":\"period_start\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 23:37:21'),
(371, 1, 1, 'event', 326, 'create', NULL, '{\"id\":326,\"match_id\":3,\"period_id\":5,\"match_second\":290,\"minute\":4,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":17,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 23:56:00\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Foul\",\"event_type_key\":\"foul\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 23:56:00'),
(372, 1, 1, 'event', 327, 'create', NULL, '{\"id\":327,\"match_id\":3,\"period_id\":5,\"match_second\":2092,\"minute\":34,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":17,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 23:56:06\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Foul\",\"event_type_key\":\"foul\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-17 23:56:06'),
(373, 1, 1, 'event', 327, 'delete', '{\"id\":327,\"match_id\":3,\"period_id\":5,\"match_second\":2092,\"minute\":34,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":17,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 23:56:06\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Foul\",\"event_type_key\":\"foul\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', NULL, '2026-01-17 23:56:44'),
(374, 1, 1, 'event', 326, 'delete', '{\"id\":326,\"match_id\":3,\"period_id\":5,\"match_second\":290,\"minute\":4,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":17,\"importance\":3,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 23:56:00\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Foul\",\"event_type_key\":\"foul\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', NULL, '2026-01-17 23:56:45'),
(375, 1, 1, 'playlist', 6, 'create', NULL, '{\"id\":6,\"match_id\":3,\"title\":\"Corners\",\"notes\":null,\"created_at\":\"2026-01-18 01:55:48\",\"updated_at\":null,\"deleted_at\":null,\"team_sides\":[]}', '2026-01-18 01:55:48'),
(376, 1, 1, 'playlist', 7, 'create', NULL, '{\"id\":7,\"match_id\":3,\"title\":\"Funny\",\"notes\":null,\"created_at\":\"2026-01-18 01:56:12\",\"updated_at\":null,\"deleted_at\":null,\"team_sides\":[]}', '2026-01-18 01:56:12'),
(377, 1, 1, 'playlist_clip', 5, 'add', NULL, '{\"playlist_id\":5,\"clip_id\":63,\"sort_order\":0}', '2026-01-18 03:11:24'),
(378, 1, 1, 'playlist_clip', 5, 'add', NULL, '{\"playlist_id\":5,\"clip_id\":312,\"sort_order\":1}', '2026-01-18 03:11:31'),
(379, 1, 1, 'playlist_clip', 5, 'add', NULL, '{\"playlist_id\":5,\"clip_id\":289,\"sort_order\":2}', '2026-01-18 03:11:38'),
(380, 1, 1, 'playlist_clip', 5, 'add', NULL, '{\"playlist_id\":5,\"clip_id\":282,\"sort_order\":3}', '2026-01-18 03:11:43'),
(381, 1, 1, 'playlist_clip', 7, 'add', NULL, '{\"playlist_id\":7,\"clip_id\":302,\"sort_order\":0}', '2026-01-18 03:15:50'),
(382, 1, 1, 'playlist_clip', 6, 'add', NULL, '{\"playlist_id\":6,\"clip_id\":267,\"sort_order\":0}', '2026-01-18 03:16:04'),
(383, 1, 1, 'playlist_clip', 6, 'add', NULL, '{\"playlist_id\":6,\"clip_id\":275,\"sort_order\":1}', '2026-01-18 03:16:10'),
(384, 1, 1, 'playlist_clip', 6, 'add', NULL, '{\"playlist_id\":6,\"clip_id\":304,\"sort_order\":2}', '2026-01-18 03:16:17'),
(385, 1, 1, 'playlist_clip', 6, 'add', NULL, '{\"playlist_id\":6,\"clip_id\":304,\"sort_order\":3}', '2026-01-18 03:16:22'),
(386, 1, 1, 'playlist_clip', 6, 'add', NULL, '{\"playlist_id\":6,\"clip_id\":317,\"sort_order\":4}', '2026-01-18 03:16:24'),
(387, 1, 1, 'playlist_clip', 6, 'add', NULL, '{\"playlist_id\":6,\"clip_id\":316,\"sort_order\":5}', '2026-01-18 03:16:27'),
(388, 1, 1, 'playlist_clip', 6, 'add', NULL, '{\"playlist_id\":6,\"clip_id\":269,\"sort_order\":6}', '2026-01-18 03:16:31'),
(389, 1, 1, 'playlist_clip', 5, 'remove', '{\"id\":63,\"match_id\":3,\"event_id\":322,\"clip_id\":0,\"clip_name\":\"\",\"start_second\":6051,\"end_second\":6111,\"duration_seconds\":0,\"created_by\":1,\"created_at\":\"2026-01-18 03:10:29\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null,\"sort_order\":0}', NULL, '2026-01-18 09:25:04'),
(390, 1, 1, 'playlist_clip', 5, 'remove', '{\"id\":64,\"match_id\":3,\"event_id\":312,\"clip_id\":0,\"clip_name\":\"\",\"start_second\":5416,\"end_second\":5476,\"duration_seconds\":0,\"created_by\":1,\"created_at\":\"2026-01-18 03:11:31\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null,\"sort_order\":1}', NULL, '2026-01-18 09:25:05'),
(391, 1, 1, 'playlist_clip', 5, 'remove', '{\"id\":65,\"match_id\":3,\"event_id\":289,\"clip_id\":0,\"clip_name\":\"\",\"start_second\":2631,\"end_second\":2691,\"duration_seconds\":0,\"created_by\":1,\"created_at\":\"2026-01-18 03:11:38\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null,\"sort_order\":2}', NULL, '2026-01-18 09:25:07'),
(392, 1, 1, 'playlist_clip', 5, 'remove', '{\"id\":66,\"match_id\":3,\"event_id\":282,\"clip_id\":0,\"clip_name\":\"\",\"start_second\":2104,\"end_second\":2164,\"duration_seconds\":0,\"created_by\":1,\"created_at\":\"2026-01-18 03:11:43\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null,\"sort_order\":3}', NULL, '2026-01-18 09:25:08'),
(393, 1, 1, 'playlist_clip', 5, 'add', NULL, '{\"playlist_id\":5,\"clip_id\":66,\"sort_order\":0}', '2026-01-18 09:33:08'),
(394, 1, 1, 'playlist_clip', 5, 'add', NULL, '{\"playlist_id\":5,\"clip_id\":65,\"sort_order\":1}', '2026-01-18 09:33:15'),
(395, 1, 1, 'playlist_clip', 5, 'add', NULL, '{\"playlist_id\":5,\"clip_id\":64,\"sort_order\":2}', '2026-01-18 09:33:21'),
(396, 1, 1, 'playlist_clip', 5, 'add', NULL, '{\"playlist_id\":5,\"clip_id\":63,\"sort_order\":3}', '2026-01-18 09:33:24'),
(397, 1, 1, 'playlist_clip', 5, 'remove', '{\"id\":66,\"match_id\":3,\"event_id\":282,\"clip_id\":0,\"clip_name\":\"\",\"start_second\":2104,\"end_second\":2164,\"duration_seconds\":0,\"created_by\":1,\"created_at\":\"2026-01-18 03:11:43\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null,\"sort_order\":0}', NULL, '2026-01-18 09:37:24'),
(398, 1, 1, 'playlist_clip', 5, 'remove', '{\"id\":65,\"match_id\":3,\"event_id\":289,\"clip_id\":0,\"clip_name\":\"\",\"start_second\":2631,\"end_second\":2691,\"duration_seconds\":0,\"created_by\":1,\"created_at\":\"2026-01-18 03:11:38\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null,\"sort_order\":1}', NULL, '2026-01-18 09:37:26'),
(399, 1, 1, 'playlist_clip', 5, 'remove', '{\"id\":64,\"match_id\":3,\"event_id\":312,\"clip_id\":0,\"clip_name\":\"\",\"start_second\":5416,\"end_second\":5476,\"duration_seconds\":0,\"created_by\":1,\"created_at\":\"2026-01-18 03:11:31\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null,\"sort_order\":2}', NULL, '2026-01-18 09:37:27'),
(400, 1, 1, 'playlist_clip', 5, 'remove', '{\"id\":63,\"match_id\":3,\"event_id\":322,\"clip_id\":0,\"clip_name\":\"\",\"start_second\":6051,\"end_second\":6111,\"duration_seconds\":0,\"created_by\":1,\"created_at\":\"2026-01-18 03:10:29\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null,\"sort_order\":3}', NULL, '2026-01-18 09:37:28'),
(401, 1, 1, 'playlist_clip', 5, 'add', NULL, '{\"playlist_id\":5,\"clip_id\":66,\"sort_order\":0}', '2026-01-18 09:37:44'),
(402, 1, 1, 'playlist_clip', 5, 'remove', '{\"id\":66,\"match_id\":3,\"event_id\":282,\"clip_id\":0,\"clip_name\":\"\",\"start_second\":2104,\"end_second\":2164,\"duration_seconds\":0,\"created_by\":1,\"created_at\":\"2026-01-18 03:11:43\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null,\"sort_order\":0}', NULL, '2026-01-18 09:42:39'),
(403, 1, 1, 'playlist_clip', 5, 'add', NULL, '{\"playlist_id\":5,\"clip_id\":66,\"sort_order\":0}', '2026-01-18 09:42:45'),
(404, 1, 1, 'playlist_clip', 5, 'remove', '{\"id\":66,\"match_id\":3,\"event_id\":282,\"clip_id\":0,\"clip_name\":\"\",\"start_second\":2104,\"end_second\":2164,\"duration_seconds\":0,\"created_by\":1,\"created_at\":\"2026-01-18 03:11:43\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null,\"sort_order\":0}', NULL, '2026-01-18 09:54:35'),
(405, 1, 1, 'playlist_clip', 6, 'remove', '{\"id\":68,\"match_id\":3,\"event_id\":267,\"clip_id\":0,\"clip_name\":\"\",\"start_second\":794,\"end_second\":854,\"duration_seconds\":0,\"created_by\":1,\"created_at\":\"2026-01-18 03:16:04\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null,\"sort_order\":0}', NULL, '2026-01-18 09:54:45'),
(406, 1, 1, 'playlist_clip', 6, 'remove', '{\"id\":69,\"match_id\":3,\"event_id\":275,\"clip_id\":0,\"clip_name\":\"\",\"start_second\":1486,\"end_second\":1546,\"duration_seconds\":0,\"created_by\":1,\"created_at\":\"2026-01-18 03:16:10\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null,\"sort_order\":1}', NULL, '2026-01-18 09:54:46'),
(407, 1, 1, 'playlist_clip', 6, 'remove', '{\"id\":70,\"match_id\":3,\"event_id\":304,\"clip_id\":0,\"clip_name\":\"\",\"start_second\":4983,\"end_second\":5043,\"duration_seconds\":0,\"created_by\":1,\"created_at\":\"2026-01-18 03:16:17\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null,\"sort_order\":2}', NULL, '2026-01-18 09:54:47'),
(408, 1, 1, 'playlist_clip', 6, 'remove', '{\"id\":71,\"match_id\":3,\"event_id\":304,\"clip_id\":0,\"clip_name\":\"\",\"start_second\":4983,\"end_second\":5043,\"duration_seconds\":0,\"created_by\":1,\"created_at\":\"2026-01-18 03:16:22\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null,\"sort_order\":3}', NULL, '2026-01-18 09:54:48'),
(409, 1, 1, 'playlist_clip', 6, 'remove', '{\"id\":72,\"match_id\":3,\"event_id\":317,\"clip_id\":0,\"clip_name\":\"\",\"start_second\":5412,\"end_second\":5472,\"duration_seconds\":0,\"created_by\":1,\"created_at\":\"2026-01-18 03:16:24\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null,\"sort_order\":4}', NULL, '2026-01-18 09:54:48'),
(410, 1, 1, 'playlist_clip', 6, 'remove', '{\"id\":73,\"match_id\":3,\"event_id\":316,\"clip_id\":0,\"clip_name\":\"\",\"start_second\":5747,\"end_second\":5807,\"duration_seconds\":0,\"created_by\":1,\"created_at\":\"2026-01-18 03:16:27\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null,\"sort_order\":5}', NULL, '2026-01-18 09:54:49'),
(411, 1, 1, 'playlist_clip', 6, 'remove', '{\"id\":74,\"match_id\":3,\"event_id\":269,\"clip_id\":0,\"clip_name\":\"\",\"start_second\":969,\"end_second\":1029,\"duration_seconds\":0,\"created_by\":1,\"created_at\":\"2026-01-18 03:16:31\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null,\"sort_order\":6}', NULL, '2026-01-18 09:54:50'),
(412, 1, 1, 'playlist_clip', 5, 'add', NULL, '{\"playlist_id\":5,\"clip_id\":66,\"sort_order\":0}', '2026-01-18 09:54:58'),
(413, 1, 1, 'playlist_clip', 5, 'add', NULL, '{\"playlist_id\":5,\"clip_id\":65,\"sort_order\":1}', '2026-01-18 09:55:06'),
(414, 1, 1, 'playlist_clip', 5, 'add', NULL, '{\"playlist_id\":5,\"clip_id\":64,\"sort_order\":2}', '2026-01-18 09:55:10'),
(415, 1, 1, 'playlist_clip', 5, 'add', NULL, '{\"playlist_id\":5,\"clip_id\":63,\"sort_order\":3}', '2026-01-18 09:55:12'),
(416, 1, 1, 'event', 287, 'update', '{\"id\":287,\"match_id\":3,\"period_id\":5,\"match_second\":2398,\"minute\":39,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":8,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 22:17:20\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Yellow Card\",\"event_type_key\":\"yellow_card\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '{\"id\":287,\"match_id\":3,\"period_id\":5,\"match_second\":2398,\"minute\":39,\"minute_extra\":0,\"team_side\":\"unknown\",\"event_type_id\":8,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 22:17:20\",\"updated_by\":null,\"updated_at\":\"2026-01-18 11:35:49\",\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Yellow Card\",\"event_type_key\":\"yellow_card\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-18 11:35:49'),
(417, 1, 1, 'event', 308, 'update', '{\"id\":308,\"match_id\":3,\"period_id\":5,\"match_second\":5223,\"minute\":87,\"minute_extra\":42,\"team_side\":\"home\",\"event_type_id\":8,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":60,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 23:12:30\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Yellow Card\",\"event_type_key\":\"yellow_card\",\"match_player_name\":\"R. Agnew\",\"match_player_shirt\":2,\"match_player_team_side\":\"home\",\"match_player_position\":\"CB\",\"period_label\":\"First Half\",\"tags\":[]}', '{\"id\":308,\"match_id\":3,\"period_id\":5,\"match_second\":5223,\"minute\":87,\"minute_extra\":42,\"team_side\":\"unknown\",\"event_type_id\":8,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":60,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-17 23:12:30\",\"updated_by\":null,\"updated_at\":\"2026-01-18 11:36:19\",\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Yellow Card\",\"event_type_key\":\"yellow_card\",\"match_player_name\":\"R. Agnew\",\"match_player_shirt\":2,\"match_player_team_side\":\"home\",\"match_player_position\":\"CB\",\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-18 11:36:19'),
(418, 1, 1, 'event', 247, 'delete', '{\"id\":247,\"match_id\":1,\"period_id\":null,\"match_second\":4147,\"minute\":69,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":12,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-13 16:40:59\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":53,\"clip_start_second\":4117,\"clip_end_second\":4177,\"event_type_label\":\"Highlight\",\"event_type_key\":\"highlight\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":null,\"tags\":[]}', NULL, '2026-01-19 12:43:51'),
(419, 1, 1, 'event', 242, 'delete', '{\"id\":242,\"match_id\":1,\"period_id\":null,\"match_second\":2820,\"minute\":47,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":8,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":40,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-09 15:24:25\",\"updated_by\":null,\"updated_at\":\"2026-01-16 06:59:57\",\"match_period_id\":null,\"clip_id\":51,\"clip_start_second\":2790,\"clip_end_second\":2850,\"event_type_label\":\"Yellow Card\",\"event_type_key\":\"yellow_card\",\"match_player_name\":\"C. Robertson\",\"match_player_shirt\":1,\"match_player_team_side\":\"home\",\"match_player_position\":\"GK\",\"period_label\":null,\"tags\":[]}', NULL, '2026-01-19 12:43:57'),
(420, 1, 1, 'event', 241, 'delete', '{\"id\":241,\"match_id\":1,\"period_id\":null,\"match_second\":2820,\"minute\":47,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":3,\"importance\":2,\"phase\":\"unknown\",\"match_player_id\":46,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-09 14:57:11\",\"updated_by\":null,\"updated_at\":\"2026-01-16 07:07:04\",\"match_period_id\":null,\"clip_id\":50,\"clip_start_second\":2790,\"clip_end_second\":2850,\"event_type_label\":\"Chance\",\"event_type_key\":\"chance\",\"match_player_name\":\"J. Hanlon\",\"match_player_shirt\":7,\"match_player_team_side\":\"home\",\"match_player_position\":\"RM\",\"period_label\":null,\"tags\":[]}', NULL, '2026-01-19 12:44:04'),
(421, 1, 1, 'event', 328, 'create', NULL, '{\"id\":328,\"match_id\":1,\"period_id\":null,\"match_second\":1817,\"minute\":30,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-19 12:45:21\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":null,\"tags\":[]}', '2026-01-19 12:45:21'),
(422, 1, 1, 'clip', 75, 'create', NULL, '{\"id\":75,\"match_id\":1,\"event_id\":328,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Goal @ 1817s\",\"start_second\":1787,\"end_second\":1847,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2026-01-19 12:45:21\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null}', '2026-01-19 12:45:21'),
(423, 1, 1, 'clip', 75, 'generated', NULL, NULL, '2026-01-19 12:45:21'),
(424, 1, 1, 'event', 329, 'create', NULL, '{\"id\":329,\"match_id\":1,\"period_id\":null,\"match_second\":4612,\"minute\":76,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-19 12:45:29\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":null,\"tags\":[]}', '2026-01-19 12:45:29'),
(425, 1, 1, 'clip', 76, 'create', NULL, '{\"id\":76,\"match_id\":1,\"event_id\":329,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Goal @ 4612s\",\"start_second\":4582,\"end_second\":4642,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2026-01-19 12:45:29\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null}', '2026-01-19 12:45:29'),
(426, 1, 1, 'clip', 76, 'generated', NULL, NULL, '2026-01-19 12:45:29');
INSERT INTO `audit_log` (`id`, `club_id`, `user_id`, `entity_type`, `entity_id`, `action`, `before_json`, `after_json`, `created_at`) VALUES
(427, 1, 1, 'event', 330, 'create', NULL, '{\"id\":330,\"match_id\":1,\"period_id\":null,\"match_second\":5074,\"minute\":84,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-19 12:45:38\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":null,\"tags\":[]}', '2026-01-19 12:45:38'),
(428, 1, 1, 'clip', 77, 'create', NULL, '{\"id\":77,\"match_id\":1,\"event_id\":330,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Goal @ 5074s\",\"start_second\":5044,\"end_second\":5104,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2026-01-19 12:45:38\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null}', '2026-01-19 12:45:38'),
(429, 1, 1, 'clip', 77, 'generated', NULL, NULL, '2026-01-19 12:45:38'),
(430, 1, 1, 'event', 392, 'create', NULL, '{\"id\":392,\"match_id\":1,\"period_id\":null,\"match_second\":193,\"minute\":3,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-19 16:27:10\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":null,\"tags\":[]}', '2026-01-19 16:27:10'),
(431, 1, 1, 'clip', 78, 'create', NULL, '{\"id\":78,\"match_id\":1,\"event_id\":392,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Goal @ 193s\",\"start_second\":163,\"end_second\":223,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2026-01-19 16:27:10\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null}', '2026-01-19 16:27:10'),
(432, 1, 1, 'clip', 78, 'generated', NULL, NULL, '2026-01-19 16:27:10'),
(433, 1, 1, 'event', 393, 'create', NULL, '{\"id\":393,\"match_id\":1,\"period_id\":null,\"match_second\":859,\"minute\":14,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-19 16:27:19\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":null,\"tags\":[]}', '2026-01-19 16:27:19'),
(434, 1, 1, 'clip', 79, 'create', NULL, '{\"id\":79,\"match_id\":1,\"event_id\":393,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Goal @ 859s\",\"start_second\":829,\"end_second\":889,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2026-01-19 16:27:19\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null}', '2026-01-19 16:27:19'),
(435, 1, 1, 'clip', 79, 'generated', NULL, NULL, '2026-01-19 16:27:19'),
(436, 1, 1, 'event', 394, 'create', NULL, '{\"id\":394,\"match_id\":1,\"period_id\":null,\"match_second\":1378,\"minute\":22,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-19 16:27:27\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":null,\"tags\":[]}', '2026-01-19 16:27:27'),
(437, 1, 1, 'clip', 80, 'create', NULL, '{\"id\":80,\"match_id\":1,\"event_id\":394,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Goal @ 1378s\",\"start_second\":1348,\"end_second\":1408,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2026-01-19 16:27:27\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null}', '2026-01-19 16:27:27'),
(438, 1, 1, 'clip', 80, 'generated', NULL, NULL, '2026-01-19 16:27:27'),
(439, 1, 1, 'event', 395, 'create', NULL, '{\"id\":395,\"match_id\":1,\"period_id\":null,\"match_second\":1971,\"minute\":32,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-19 16:27:35\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":null,\"tags\":[]}', '2026-01-19 16:27:35'),
(440, 1, 1, 'clip', 81, 'create', NULL, '{\"id\":81,\"match_id\":1,\"event_id\":395,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Goal @ 1971s\",\"start_second\":1941,\"end_second\":2001,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2026-01-19 16:27:35\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null}', '2026-01-19 16:27:35'),
(441, 1, 1, 'clip', 81, 'generated', NULL, NULL, '2026-01-19 16:27:35'),
(442, 1, 1, 'event', 396, 'create', NULL, '{\"id\":396,\"match_id\":1,\"period_id\":null,\"match_second\":2732,\"minute\":45,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-19 16:27:45\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":null,\"tags\":[]}', '2026-01-19 16:27:45'),
(443, 1, 1, 'clip', 82, 'create', NULL, '{\"id\":82,\"match_id\":1,\"event_id\":396,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Goal @ 2732s\",\"start_second\":2702,\"end_second\":2762,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2026-01-19 16:27:45\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null}', '2026-01-19 16:27:45'),
(444, 1, 1, 'clip', 82, 'generated', NULL, NULL, '2026-01-19 16:27:45'),
(445, 1, 1, 'event', 392, 'delete', '{\"id\":392,\"match_id\":1,\"period_id\":null,\"match_second\":193,\"minute\":3,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-19 16:27:10\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":78,\"clip_start_second\":163,\"clip_end_second\":223,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":null,\"tags\":[]}', NULL, '2026-01-19 17:47:16'),
(446, 1, 1, 'event', 393, 'delete', '{\"id\":393,\"match_id\":1,\"period_id\":null,\"match_second\":859,\"minute\":14,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-19 16:27:19\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":79,\"clip_start_second\":829,\"clip_end_second\":889,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":null,\"tags\":[]}', NULL, '2026-01-19 17:47:20'),
(447, 1, 1, 'event', 394, 'delete', '{\"id\":394,\"match_id\":1,\"period_id\":null,\"match_second\":1378,\"minute\":22,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-19 16:27:27\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":80,\"clip_start_second\":1348,\"clip_end_second\":1408,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":null,\"tags\":[]}', NULL, '2026-01-19 17:47:24'),
(448, 1, 1, 'event', 395, 'delete', '{\"id\":395,\"match_id\":1,\"period_id\":null,\"match_second\":1971,\"minute\":32,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-19 16:27:35\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":81,\"clip_start_second\":1941,\"clip_end_second\":2001,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":null,\"tags\":[]}', NULL, '2026-01-19 17:47:28'),
(449, 1, 1, 'event', 396, 'delete', '{\"id\":396,\"match_id\":1,\"period_id\":null,\"match_second\":2732,\"minute\":45,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-19 16:27:45\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":82,\"clip_start_second\":2702,\"clip_end_second\":2762,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":null,\"tags\":[]}', NULL, '2026-01-19 17:47:33'),
(450, 1, 1, 'event', 397, 'create', NULL, '{\"id\":397,\"match_id\":1,\"period_id\":null,\"match_second\":2732,\"minute\":45,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-19 17:48:01\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":null,\"tags\":[]}', '2026-01-19 17:48:01'),
(451, 1, 1, 'clip', 83, 'create', NULL, '{\"id\":83,\"match_id\":1,\"event_id\":397,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Goal @ 2732s\",\"start_second\":2702,\"end_second\":2762,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2026-01-19 17:48:01\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null}', '2026-01-19 17:48:01'),
(452, 1, 1, 'clip', 83, 'generated', NULL, NULL, '2026-01-19 17:48:01'),
(453, 1, 1, 'event', 398, 'create', NULL, '{\"id\":398,\"match_id\":1,\"period_id\":null,\"match_second\":3009,\"minute\":50,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-19 17:48:09\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":null,\"tags\":[]}', '2026-01-19 17:48:09'),
(454, 1, 1, 'clip', 84, 'create', NULL, '{\"id\":84,\"match_id\":1,\"event_id\":398,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Goal @ 3009s\",\"start_second\":2979,\"end_second\":3039,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2026-01-19 17:48:09\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null}', '2026-01-19 17:48:09'),
(455, 1, 1, 'clip', 84, 'generated', NULL, NULL, '2026-01-19 17:48:09'),
(456, 1, 1, 'event', 399, 'create', NULL, '{\"id\":399,\"match_id\":1,\"period_id\":null,\"match_second\":3152,\"minute\":52,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-19 17:48:15\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":null,\"tags\":[]}', '2026-01-19 17:48:15'),
(457, 1, 1, 'clip', 85, 'create', NULL, '{\"id\":85,\"match_id\":1,\"event_id\":399,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Goal @ 3152s\",\"start_second\":3122,\"end_second\":3182,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2026-01-19 17:48:15\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null}', '2026-01-19 17:48:15'),
(458, 1, 1, 'clip', 85, 'generated', NULL, NULL, '2026-01-19 17:48:15'),
(459, 1, 1, 'event', 400, 'create', NULL, '{\"id\":400,\"match_id\":1,\"period_id\":null,\"match_second\":3380,\"minute\":56,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-19 17:48:20\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":null,\"tags\":[]}', '2026-01-19 17:48:20'),
(460, 1, 1, 'clip', 86, 'create', NULL, '{\"id\":86,\"match_id\":1,\"event_id\":400,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Goal @ 3380s\",\"start_second\":3350,\"end_second\":3410,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2026-01-19 17:48:20\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null}', '2026-01-19 17:48:20'),
(461, 1, 1, 'clip', 86, 'generated', NULL, NULL, '2026-01-19 17:48:20'),
(462, 1, 1, 'event', 401, 'create', NULL, '{\"id\":401,\"match_id\":1,\"period_id\":null,\"match_second\":3487,\"minute\":58,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-19 17:48:24\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":null,\"tags\":[]}', '2026-01-19 17:48:24'),
(463, 1, 1, 'clip', 87, 'create', NULL, '{\"id\":87,\"match_id\":1,\"event_id\":401,\"clip_id\":0,\"clip_name\":\"Auto clip \\u2013 Goal @ 3487s\",\"start_second\":3457,\"end_second\":3517,\"duration_seconds\":60,\"created_by\":1,\"created_at\":\"2026-01-19 17:48:24\",\"updated_by\":null,\"updated_at\":null,\"generation_source\":\"event_auto\",\"generation_version\":1,\"is_valid\":1,\"deleted_at\":null}', '2026-01-19 17:48:24'),
(464, 1, 1, 'clip', 87, 'generated', NULL, NULL, '2026-01-19 17:48:24');

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

--
-- Dumping data for table `clips`
--

INSERT INTO `clips` (`id`, `match_id`, `event_id`, `clip_id`, `clip_name`, `start_second`, `end_second`, `duration_seconds`, `created_by`, `created_at`, `updated_by`, `updated_at`, `generation_source`, `generation_version`, `is_valid`, `deleted_at`) VALUES
(55, 1, 249, 0, 'Auto clip – Goal @ 4321s', 4291, 4351, 60, 1, '2026-01-14 18:12:49', NULL, NULL, 'event_auto', 1, 1, NULL),
(56, 1, 244, 0, 'Auto clip – Foul @ 1770s', 1740, 1800, 60, 2, '2026-01-16 07:18:39', 2, '2026-01-16 07:46:36', 'event_auto', 2, 1, NULL),
(57, 1, 257, 0, 'Auto clip – Goal @ 1627s', 1597, 1657, 60, 1, '2026-01-16 21:47:06', NULL, NULL, 'event_auto', 1, 1, NULL),
(63, 3, 322, 0, '', 6051, 6111, 0, 1, '2026-01-18 03:10:29', NULL, NULL, 'event_auto', 1, 1, NULL),
(64, 3, 312, 0, '', 5416, 5476, 0, 1, '2026-01-18 03:11:31', NULL, NULL, 'event_auto', 1, 1, NULL),
(65, 3, 289, 0, '', 2631, 2691, 0, 1, '2026-01-18 03:11:38', NULL, NULL, 'event_auto', 1, 1, NULL),
(66, 3, 282, 0, '', 2104, 2164, 0, 1, '2026-01-18 03:11:43', NULL, NULL, 'event_auto', 1, 1, NULL),
(67, 3, 302, 0, '', 4776, 4836, 0, 1, '2026-01-18 03:15:50', NULL, NULL, 'event_auto', 1, 1, NULL),
(68, 3, 267, 0, '', 794, 854, 0, 1, '2026-01-18 03:16:04', NULL, NULL, 'event_auto', 1, 1, NULL),
(69, 3, 275, 0, '', 1486, 1546, 0, 1, '2026-01-18 03:16:10', NULL, NULL, 'event_auto', 1, 1, NULL),
(70, 3, 304, 0, '', 4983, 5043, 0, 1, '2026-01-18 03:16:17', NULL, NULL, 'event_auto', 1, 1, NULL),
(71, 3, 304, 0, '', 4983, 5043, 0, 1, '2026-01-18 03:16:22', NULL, NULL, 'event_auto', 1, 1, NULL),
(72, 3, 317, 0, '', 5412, 5472, 0, 1, '2026-01-18 03:16:24', NULL, NULL, 'event_auto', 1, 1, NULL),
(73, 3, 316, 0, '', 5747, 5807, 0, 1, '2026-01-18 03:16:27', NULL, NULL, 'event_auto', 1, 1, NULL),
(74, 3, 269, 0, '', 969, 1029, 0, 1, '2026-01-18 03:16:31', NULL, NULL, 'event_auto', 1, 1, NULL),
(75, 1, 328, 0, 'Auto clip – Goal @ 1817s', 1787, 1847, 60, 1, '2026-01-19 12:45:21', NULL, NULL, 'event_auto', 1, 1, NULL),
(76, 1, 329, 0, 'Auto clip – Goal @ 4612s', 4582, 4642, 60, 1, '2026-01-19 12:45:29', NULL, NULL, 'event_auto', 1, 1, NULL),
(77, 1, 330, 0, 'Auto clip – Goal @ 5074s', 5044, 5104, 60, 1, '2026-01-19 12:45:38', NULL, NULL, 'event_auto', 1, 1, NULL),
(83, 1, 397, 0, 'Auto clip – Goal @ 2732s', 2702, 2762, 60, 1, '2026-01-19 17:48:01', NULL, NULL, 'event_auto', 1, 1, NULL),
(84, 1, 398, 0, 'Auto clip – Goal @ 3009s', 2979, 3039, 60, 1, '2026-01-19 17:48:09', NULL, NULL, 'event_auto', 1, 1, NULL),
(85, 1, 399, 0, 'Auto clip – Goal @ 3152s', 3122, 3182, 60, 1, '2026-01-19 17:48:15', NULL, NULL, 'event_auto', 1, 1, NULL),
(86, 1, 400, 0, 'Auto clip – Goal @ 3380s', 3350, 3410, 60, 1, '2026-01-19 17:48:20', NULL, NULL, 'event_auto', 1, 1, NULL),
(87, 1, 401, 0, 'Auto clip – Goal @ 3487s', 3457, 3517, 60, 1, '2026-01-19 17:48:24', NULL, NULL, 'event_auto', 1, 1, NULL);

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

--
-- Dumping data for table `clip_jobs`
--

INSERT INTO `clip_jobs` (`id`, `match_id`, `event_id`, `clip_id`, `status`, `payload`, `error_message`, `completed_note`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, NULL, 'completed', '{\"clip_id\": 1, \"match_id\": 1, \"event_id\": 188, \"start_second\": 1024, \"end_second\": 1049, \"source_video\": \"/videos/matches/match_1/source/veo\", \"source_path\": \"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2025-12-27 22:36:52', '2025-12-28 05:17:05'),
(2, 1, NULL, NULL, 'completed', '{\"clip_id\": 2, \"match_id\": 1, \"event_id\": 189, \"start_second\": 1031, \"end_second\": 1045, \"source_video\": \"/videos/matches/match_1/source/veo\", \"source_path\": \"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2025-12-27 22:36:52', '2025-12-28 05:17:05'),
(3, 1, NULL, NULL, 'completed', '{\"clip_id\": 3, \"match_id\": 1, \"event_id\": 200, \"start_second\": 1031, \"end_second\": 1045, \"source_video\": \"/videos/matches/match_1/source/veo\", \"source_path\": \"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2025-12-27 22:36:52', '2025-12-28 05:17:05'),
(4, 1, NULL, NULL, 'completed', '{\"clip_id\": 4, \"match_id\": 1, \"event_id\": 190, \"start_second\": 1486, \"end_second\": 1496, \"source_video\": \"/videos/matches/match_1/source/veo\", \"source_path\": \"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2025-12-27 22:36:52', '2025-12-28 05:17:05'),
(5, 1, NULL, NULL, 'completed', '{\"clip_id\": 5, \"match_id\": 1, \"event_id\": 199, \"start_second\": 3077, \"end_second\": 3091, \"source_video\": \"/videos/matches/match_1/source/veo\", \"source_path\": \"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2025-12-27 22:36:52', '2025-12-28 05:17:05'),
(6, 1, NULL, NULL, 'pending', '{\"match_id\":1,\"event_id\":204,\"start_second\":4305,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2026-01-06 13:13:10', NULL),
(7, 1, NULL, NULL, 'pending', '{\"match_id\":1,\"event_id\":205,\"start_second\":307,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2026-01-07 12:10:43', NULL),
(8, 1, NULL, NULL, 'pending', '{\"match_id\":1,\"event_id\":206,\"start_second\":1743,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2026-01-07 14:53:39', NULL),
(9, 1, NULL, NULL, 'pending', '{\"match_id\":1,\"event_id\":207,\"start_second\":1743,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2026-01-07 14:59:44', NULL),
(10, 1, NULL, NULL, 'pending', '{\"match_id\":1,\"event_id\":208,\"start_second\":1403,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2026-01-07 14:59:56', NULL),
(11, 1, NULL, NULL, 'pending', '{\"match_id\":1,\"event_id\":209,\"start_second\":1403,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2026-01-07 14:59:56', NULL),
(12, 1, NULL, NULL, 'pending', '{\"match_id\":1,\"event_id\":210,\"start_second\":1403,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2026-01-07 14:59:57', NULL),
(13, 1, NULL, NULL, 'pending', '{\"match_id\":1,\"event_id\":211,\"start_second\":1403,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2026-01-07 14:59:58', NULL),
(14, 1, NULL, NULL, 'pending', '{\"match_id\":1,\"event_id\":212,\"start_second\":1191,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2026-01-08 08:43:18', NULL),
(15, 1, NULL, NULL, 'pending', '{\"match_id\":1,\"event_id\":213,\"start_second\":2169,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2026-01-08 09:01:16', NULL),
(16, 1, NULL, NULL, 'pending', '{\"match_id\":1,\"event_id\":214,\"start_second\":1161,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2026-01-08 10:11:12', NULL),
(17, 1, NULL, NULL, 'pending', '{\"match_id\":1,\"event_id\":215,\"start_second\":1161,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2026-01-08 10:11:16', NULL),
(18, 1, NULL, NULL, 'pending', '{\"match_id\":1,\"event_id\":216,\"start_second\":1161,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2026-01-08 10:11:18', NULL),
(19, 1, NULL, NULL, 'pending', '{\"match_id\":1,\"event_id\":217,\"start_second\":1161,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2026-01-08 10:11:22', NULL),
(20, 1, NULL, NULL, 'pending', '{\"match_id\":1,\"event_id\":218,\"start_second\":4683,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2026-01-08 10:18:34', NULL),
(21, 1, NULL, NULL, 'pending', '{\"match_id\":1,\"event_id\":219,\"start_second\":4683,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2026-01-08 10:19:05', NULL),
(22, 1, NULL, NULL, 'pending', '{\"match_id\":1,\"event_id\":220,\"start_second\":390,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2026-01-08 14:35:45', NULL),
(23, 1, NULL, NULL, 'pending', '{\"match_id\":1,\"event_id\":221,\"start_second\":390,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2026-01-08 14:37:48', NULL),
(24, 1, NULL, NULL, 'pending', '{\"match_id\":1,\"event_id\":222,\"start_second\":2627,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2026-01-08 14:44:56', NULL),
(25, 1, NULL, NULL, 'pending', '{\"match_id\":1,\"event_id\":223,\"start_second\":3923,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2026-01-08 14:45:27', NULL),
(26, 1, NULL, NULL, 'pending', '{\"match_id\":1,\"event_id\":224,\"start_second\":3947,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2026-01-08 14:45:50', NULL),
(27, 1, NULL, NULL, 'pending', '{\"match_id\":1,\"event_id\":225,\"start_second\":5636,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2026-01-08 14:46:55', NULL),
(28, 1, NULL, NULL, 'pending', '{\"match_id\":1,\"event_id\":226,\"start_second\":5640,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2026-01-08 14:46:59', NULL),
(29, 1, NULL, NULL, 'pending', '{\"match_id\":1,\"event_id\":227,\"start_second\":5701,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2026-01-08 14:47:43', NULL),
(30, 1, NULL, NULL, 'pending', '{\"match_id\":1,\"event_id\":228,\"start_second\":5845,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2026-01-08 14:48:01', NULL),
(31, 1, NULL, NULL, 'pending', '{\"match_id\":1,\"event_id\":229,\"start_second\":5858,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2026-01-08 14:48:12', NULL),
(32, 1, NULL, NULL, 'pending', '{\"match_id\":1,\"event_id\":230,\"start_second\":6010,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2026-01-08 14:48:20', NULL),
(33, 1, NULL, NULL, 'pending', '{\"match_id\":1,\"event_id\":231,\"start_second\":2139,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2026-01-09 11:31:22', NULL),
(34, 1, NULL, NULL, 'pending', '{\"match_id\":1,\"event_id\":234,\"start_second\":1784,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2026-01-09 14:01:10', NULL),
(35, 1, NULL, NULL, 'pending', '{\"match_id\":1,\"event_id\":235,\"start_second\":4683,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2026-01-09 14:28:14', NULL),
(36, 1, NULL, NULL, 'pending', '{\"match_id\":1,\"event_id\":236,\"start_second\":4683,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2026-01-09 14:28:46', NULL),
(37, 1, NULL, NULL, 'pending', '{\"match_id\":1,\"event_id\":237,\"start_second\":1641,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2026-01-09 14:36:08', NULL),
(38, 1, NULL, NULL, 'pending', '{\"match_id\":1,\"event_id\":238,\"start_second\":2244,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2026-01-09 14:50:03', NULL),
(39, 1, NULL, NULL, 'pending', '{\"match_id\":1,\"event_id\":239,\"start_second\":2244,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2026-01-09 14:50:20', NULL),
(40, 1, NULL, NULL, 'pending', '{\"match_id\":1,\"event_id\":241,\"start_second\":2790,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2026-01-09 14:57:11', NULL),
(41, 1, NULL, NULL, 'pending', '{\"match_id\":1,\"event_id\":242,\"start_second\":2790,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2026-01-09 15:24:25', NULL),
(42, 1, NULL, NULL, 'pending', '{\"match_id\":1,\"event_id\":243,\"start_second\":1740,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2026-01-09 17:21:03', NULL),
(43, 1, 244, NULL, 'pending', '{\"match_id\":1,\"event_id\":244,\"start_second\":1740,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2026-01-09 17:22:09', NULL),
(44, 1, NULL, NULL, 'pending', '{\"match_id\":1,\"event_id\":247,\"start_second\":4117,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2026-01-13 16:40:59', NULL),
(45, 1, NULL, NULL, 'pending', '{\"match_id\":1,\"event_id\":248,\"start_second\":4118,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2026-01-13 16:40:59', NULL),
(46, 1, 249, NULL, 'pending', '{\"match_id\":1,\"event_id\":249,\"start_second\":4291,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2026-01-14 18:12:49', NULL),
(47, 1, 257, NULL, 'pending', '{\"match_id\":1,\"event_id\":257,\"start_second\":1597,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2026-01-16 21:47:06', NULL),
(48, 3, 259, NULL, 'pending', '{\"match_id\":3,\"event_id\":259,\"start_second\":158,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 21:08:40', NULL),
(49, 3, 260, NULL, 'pending', '{\"match_id\":3,\"event_id\":260,\"start_second\":234,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 21:09:25', NULL),
(50, 3, 261, NULL, 'pending', '{\"match_id\":3,\"event_id\":261,\"start_second\":242,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 21:09:43', NULL),
(51, 3, 262, NULL, 'pending', '{\"match_id\":3,\"event_id\":262,\"start_second\":260,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 21:10:53', NULL),
(52, 3, 263, NULL, 'pending', '{\"match_id\":3,\"event_id\":263,\"start_second\":267,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 21:10:59', NULL),
(53, 3, 264, NULL, 'pending', '{\"match_id\":3,\"event_id\":264,\"start_second\":279,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 21:12:30', NULL),
(54, 3, 265, NULL, 'pending', '{\"match_id\":3,\"event_id\":265,\"start_second\":540,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 21:18:39', NULL),
(55, 3, 266, NULL, 'pending', '{\"match_id\":3,\"event_id\":266,\"start_second\":640,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 21:20:55', NULL),
(56, 3, 267, NULL, 'pending', '{\"match_id\":3,\"event_id\":267,\"start_second\":794,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 21:23:33', NULL),
(57, 3, NULL, NULL, 'pending', '{\"match_id\":3,\"event_id\":268,\"start_second\":821,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 21:23:59', NULL),
(58, 3, 269, NULL, 'pending', '{\"match_id\":3,\"event_id\":269,\"start_second\":969,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 21:27:59', NULL),
(59, 3, 270, NULL, 'pending', '{\"match_id\":3,\"event_id\":270,\"start_second\":1128,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 21:30:54', NULL),
(60, 3, 271, NULL, 'pending', '{\"match_id\":3,\"event_id\":271,\"start_second\":1273,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 21:33:44', NULL),
(61, 3, 272, NULL, 'pending', '{\"match_id\":3,\"event_id\":272,\"start_second\":1302,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 21:40:06', NULL),
(62, 3, 273, NULL, 'pending', '{\"match_id\":3,\"event_id\":273,\"start_second\":1434,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 21:42:38', NULL),
(63, 3, 274, NULL, 'pending', '{\"match_id\":3,\"event_id\":274,\"start_second\":1486,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 21:43:37', NULL),
(64, 3, 275, NULL, 'pending', '{\"match_id\":3,\"event_id\":275,\"start_second\":1486,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 21:43:41', NULL),
(65, 3, 276, NULL, 'pending', '{\"match_id\":3,\"event_id\":276,\"start_second\":1506,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 21:44:13', NULL),
(66, 3, 277, NULL, 'pending', '{\"match_id\":3,\"event_id\":277,\"start_second\":1582,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 21:45:30', NULL),
(67, 3, 278, NULL, 'pending', '{\"match_id\":3,\"event_id\":278,\"start_second\":1654,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 21:47:13', NULL),
(68, 3, 279, NULL, 'pending', '{\"match_id\":3,\"event_id\":279,\"start_second\":1700,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 21:48:20', NULL),
(69, 3, 280, NULL, 'pending', '{\"match_id\":3,\"event_id\":280,\"start_second\":1741,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 21:52:40', NULL),
(70, 3, 281, NULL, 'pending', '{\"match_id\":3,\"event_id\":281,\"start_second\":1964,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 21:56:25', NULL),
(71, 3, 282, NULL, 'pending', '{\"match_id\":3,\"event_id\":282,\"start_second\":2104,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 21:59:00', NULL),
(72, 3, 283, NULL, 'pending', '{\"match_id\":3,\"event_id\":283,\"start_second\":333,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 22:06:10', NULL),
(73, 3, NULL, NULL, 'pending', '{\"match_id\":3,\"event_id\":284,\"start_second\":554,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 22:09:52', NULL),
(74, 3, 285, NULL, 'pending', '{\"match_id\":3,\"event_id\":285,\"start_second\":2282,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 22:15:53', NULL),
(75, 3, 286, NULL, 'pending', '{\"match_id\":3,\"event_id\":286,\"start_second\":2362,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 22:17:13', NULL),
(76, 3, 287, NULL, 'pending', '{\"match_id\":3,\"event_id\":287,\"start_second\":2368,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 22:17:20', NULL),
(77, 3, 288, NULL, 'pending', '{\"match_id\":3,\"event_id\":288,\"start_second\":2522,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 22:21:04', NULL),
(78, 3, 289, NULL, 'pending', '{\"match_id\":3,\"event_id\":289,\"start_second\":2631,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 22:22:55', NULL),
(79, 3, 292, NULL, 'pending', '{\"match_id\":3,\"event_id\":292,\"start_second\":3587,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 22:28:30', NULL),
(80, 3, 293, NULL, 'pending', '{\"match_id\":3,\"event_id\":293,\"start_second\":3633,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 22:29:46', NULL),
(81, 3, 294, NULL, 'pending', '{\"match_id\":3,\"event_id\":294,\"start_second\":3892,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 22:46:46', NULL),
(82, 3, 295, NULL, 'pending', '{\"match_id\":3,\"event_id\":295,\"start_second\":3949,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 22:47:47', NULL),
(83, 3, 296, NULL, 'pending', '{\"match_id\":3,\"event_id\":296,\"start_second\":4163,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 22:51:21', NULL),
(84, 3, 297, NULL, 'pending', '{\"match_id\":3,\"event_id\":297,\"start_second\":4257,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 22:52:58', NULL),
(85, 3, 298, NULL, 'pending', '{\"match_id\":3,\"event_id\":298,\"start_second\":4417,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 22:55:41', NULL),
(86, 3, 299, NULL, 'pending', '{\"match_id\":3,\"event_id\":299,\"start_second\":4491,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 22:56:55', NULL),
(87, 3, 300, NULL, 'pending', '{\"match_id\":3,\"event_id\":300,\"start_second\":4593,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 22:58:37', NULL),
(88, 3, 301, NULL, 'pending', '{\"match_id\":3,\"event_id\":301,\"start_second\":4639,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 22:59:37', NULL),
(89, 3, 302, NULL, 'pending', '{\"match_id\":3,\"event_id\":302,\"start_second\":4776,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 23:02:09', NULL),
(90, 3, 303, NULL, 'pending', '{\"match_id\":3,\"event_id\":303,\"start_second\":4952,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 23:05:05', NULL),
(91, 3, 304, NULL, 'pending', '{\"match_id\":3,\"event_id\":304,\"start_second\":4983,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 23:05:36', NULL),
(92, 3, 305, NULL, 'pending', '{\"match_id\":3,\"event_id\":305,\"start_second\":5041,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 23:06:40', NULL),
(93, 3, 306, NULL, 'pending', '{\"match_id\":3,\"event_id\":306,\"start_second\":5095,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 23:08:10', NULL),
(94, 3, 307, NULL, 'pending', '{\"match_id\":3,\"event_id\":307,\"start_second\":5188,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 23:09:58', NULL),
(95, 3, 308, NULL, 'pending', '{\"match_id\":3,\"event_id\":308,\"start_second\":5193,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 23:12:30', NULL),
(96, 3, 309, NULL, 'pending', '{\"match_id\":3,\"event_id\":309,\"start_second\":5301,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 23:18:10', NULL),
(97, 3, 310, NULL, 'pending', '{\"match_id\":3,\"event_id\":310,\"start_second\":5366,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 23:19:15', NULL),
(98, 3, 311, NULL, 'pending', '{\"match_id\":3,\"event_id\":311,\"start_second\":5396,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 23:19:48', NULL),
(99, 3, 312, NULL, 'pending', '{\"match_id\":3,\"event_id\":312,\"start_second\":5416,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 23:20:10', NULL),
(100, 3, 313, NULL, 'pending', '{\"match_id\":3,\"event_id\":313,\"start_second\":5540,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 23:22:09', NULL),
(101, 3, 314, NULL, 'pending', '{\"match_id\":3,\"event_id\":314,\"start_second\":5633,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 23:23:45', NULL),
(102, 3, 315, NULL, 'pending', '{\"match_id\":3,\"event_id\":315,\"start_second\":5661,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 23:24:13', NULL),
(103, 3, 316, NULL, 'pending', '{\"match_id\":3,\"event_id\":316,\"start_second\":5747,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 23:25:43', NULL),
(104, 3, 317, NULL, 'pending', '{\"match_id\":3,\"event_id\":317,\"start_second\":5412,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 23:26:09', NULL),
(105, 3, 318, NULL, 'pending', '{\"match_id\":3,\"event_id\":318,\"start_second\":5771,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 23:26:55', NULL),
(106, 3, 319, NULL, 'pending', '{\"match_id\":3,\"event_id\":319,\"start_second\":5809,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 23:27:33', NULL),
(107, 3, 320, NULL, 'pending', '{\"match_id\":3,\"event_id\":320,\"start_second\":5976,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 23:30:21', NULL),
(108, 3, 321, NULL, 'pending', '{\"match_id\":3,\"event_id\":321,\"start_second\":6003,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 23:30:55', NULL),
(109, 3, 322, NULL, 'pending', '{\"match_id\":3,\"event_id\":322,\"start_second\":6051,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 23:31:47', NULL),
(110, 3, 323, NULL, 'pending', '{\"match_id\":3,\"event_id\":323,\"start_second\":6150,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 23:33:24', NULL),
(111, 3, NULL, NULL, 'pending', '{\"match_id\":3,\"event_id\":324,\"start_second\":6211,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 23:35:58', NULL),
(112, 3, NULL, NULL, 'pending', '{\"match_id\":3,\"event_id\":326,\"start_second\":260,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 23:56:00', NULL),
(113, 3, NULL, NULL, 'pending', '{\"match_id\":3,\"event_id\":327,\"start_second\":2062,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 23:56:06', NULL),
(114, 1, 328, NULL, 'pending', '{\"match_id\":1,\"event_id\":328,\"start_second\":1787,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2026-01-19 12:45:21', NULL),
(115, 1, 329, NULL, 'pending', '{\"match_id\":1,\"event_id\":329,\"start_second\":4582,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2026-01-19 12:45:29', NULL),
(116, 1, 330, NULL, 'pending', '{\"match_id\":1,\"event_id\":330,\"start_second\":5044,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2026-01-19 12:45:38', NULL),
(117, 1, NULL, NULL, 'pending', '{\"match_id\":1,\"event_id\":392,\"start_second\":163,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2026-01-19 16:27:10', NULL),
(118, 1, NULL, NULL, 'pending', '{\"match_id\":1,\"event_id\":393,\"start_second\":829,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2026-01-19 16:27:19', NULL),
(119, 1, NULL, NULL, 'pending', '{\"match_id\":1,\"event_id\":394,\"start_second\":1348,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2026-01-19 16:27:27', NULL),
(120, 1, NULL, NULL, 'pending', '{\"match_id\":1,\"event_id\":395,\"start_second\":1941,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2026-01-19 16:27:35', NULL),
(121, 1, NULL, NULL, 'pending', '{\"match_id\":1,\"event_id\":396,\"start_second\":2702,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2026-01-19 16:27:45', NULL),
(122, 1, 397, NULL, 'pending', '{\"match_id\":1,\"event_id\":397,\"start_second\":2702,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2026-01-19 17:48:01', NULL),
(123, 1, 398, NULL, 'pending', '{\"match_id\":1,\"event_id\":398,\"start_second\":2979,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2026-01-19 17:48:09', NULL),
(124, 1, 399, NULL, 'pending', '{\"match_id\":1,\"event_id\":399,\"start_second\":3122,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2026-01-19 17:48:15', NULL),
(125, 1, 400, NULL, 'pending', '{\"match_id\":1,\"event_id\":400,\"start_second\":3350,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2026-01-19 17:48:20', NULL),
(126, 1, 401, NULL, 'pending', '{\"match_id\":1,\"event_id\":401,\"start_second\":3457,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2026-01-19 17:48:24', NULL);

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

--
-- Dumping data for table `clip_reviews`
--

INSERT INTO `clip_reviews` (`id`, `clip_id`, `reviewed_by`, `status`, `notes`, `reviewed_at`) VALUES
(45, 55, NULL, 'pending', NULL, NULL),
(59, 56, NULL, 'pending', NULL, NULL),
(61, 57, NULL, 'pending', NULL, NULL),
(62, 75, NULL, 'pending', NULL, NULL),
(63, 76, NULL, 'pending', NULL, NULL),
(64, 77, NULL, 'pending', NULL, NULL),
(70, 83, NULL, 'pending', NULL, NULL),
(71, 84, NULL, 'pending', NULL, NULL),
(72, 85, NULL, 'pending', NULL, NULL),
(73, 86, NULL, 'pending', NULL, NULL),
(74, 87, NULL, 'pending', NULL, NULL);

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

--
-- Dumping data for table `clubs`
--

INSERT INTO `clubs` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'Saltcoats Victoria F.C.', '2025-12-18 13:10:26', NULL),
(2, 'Test', '2025-12-18 14:33:11', NULL),
(3, 'Opponents', '2026-01-19 15:12:10', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `competitions`
--

CREATE TABLE `competitions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `club_id` bigint(20) UNSIGNED NOT NULL,
  `season_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(120) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `competitions`
--

INSERT INTO `competitions` (`id`, `club_id`, `season_id`, `name`, `created_at`, `updated_at`) VALUES
(2, 1, NULL, 'Fourth Division', '2026-01-19 12:59:33', NULL),
(3, 1, NULL, 'Finest Carmats South Region Challenge Cup', '2026-01-19 12:59:33', '2026-01-19 14:57:22'),
(4, 1, NULL, '3 Pillars Financial Planning Scottish Communities Cup', '2026-01-19 12:59:33', '2026-01-19 14:57:22'),
(5, 1, NULL, 'Strathclyde Demolition West Of Scotland League Cup', '2026-01-19 12:59:33', '2026-01-19 14:57:22');

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

--
-- Dumping data for table `derived_stats`
--

INSERT INTO `derived_stats` (`id`, `match_id`, `events_version_used`, `computed_at`, `payload_json`) VALUES
(55, 3, 77, '2026-01-19 16:00:03', '{\n    \"computed_at\": \"2026-01-19 16:00:03\",\n    \"events_version_used\": 77,\n    \"by_type_team\": {\n        \"goal\": {\n            \"home\": 0,\n            \"away\": 4,\n            \"unknown\": 0\n        },\n        \"shot\": {\n            \"home\": 4,\n            \"away\": 22,\n            \"unknown\": 0\n        },\n        \"shot_on_target\": {\n            \"home\": 2,\n            \"away\": 4,\n            \"unknown\": 0\n        },\n        \"shot_off_target\": {\n            \"home\": 2,\n            \"away\": 14,\n            \"unknown\": 0\n        },\n        \"chance\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"corner\": {\n            \"home\": 1,\n            \"away\": 7,\n            \"unknown\": 0\n        },\n        \"free_kick\": {\n            \"home\": 7,\n            \"away\": 16,\n            \"unknown\": 0\n        },\n        \"penalty\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"foul\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"yellow_card\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 2\n        },\n        \"red_card\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"mistake\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"good_play\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"highlight\": {\n            \"home\": 0,\n            \"away\": 1,\n            \"unknown\": 0\n        }\n    },\n    \"phase_2\": {\n        \"by_period\": {\n            \"1H\": {\n                \"home\": 12,\n                \"away\": 46,\n                \"unknown\": 6\n            },\n            \"2H\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            },\n            \"ET\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            },\n            \"other\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            }\n        },\n        \"per_15_minute\": [\n            {\n                \"label\": \"0-15\",\n                \"home\": 2,\n                \"away\": 6,\n                \"unknown\": 1\n            },\n            {\n                \"label\": \"15-30\",\n                \"home\": 5,\n                \"away\": 7,\n                \"unknown\": 0\n            },\n            {\n                \"label\": \"30-45\",\n                \"home\": 1,\n                \"away\": 5,\n                \"unknown\": 1\n            },\n            {\n                \"label\": \"45-60\",\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 2\n            },\n            {\n                \"label\": \"60-75\",\n                \"home\": 1,\n                \"away\": 6,\n                \"unknown\": 0\n            },\n            {\n                \"label\": \"75-90\",\n                \"home\": 2,\n                \"away\": 10,\n                \"unknown\": 1\n            },\n            {\n                \"label\": \"90-105\",\n                \"home\": 1,\n                \"away\": 12,\n                \"unknown\": 1\n            }\n        ],\n        \"importance_weighted\": {\n            \"by_team\": {\n                \"home\": 28,\n                \"away\": 122,\n                \"unknown\": 8\n            },\n            \"by_type_team\": {\n                \"goal\": {\n                    \"home\": 0,\n                    \"away\": 20,\n                    \"unknown\": 0\n                },\n                \"shot\": {\n                    \"home\": 12,\n                    \"away\": 74,\n                    \"unknown\": 0\n                },\n                \"shot_on_target\": {\n                    \"home\": 6,\n                    \"away\": 12,\n                    \"unknown\": 0\n                },\n                \"shot_off_target\": {\n                    \"home\": 6,\n                    \"away\": 42,\n                    \"unknown\": 0\n                },\n                \"chance\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"corner\": {\n                    \"home\": 2,\n                    \"away\": 14,\n                    \"unknown\": 0\n                },\n                \"free_kick\": {\n                    \"home\": 14,\n                    \"away\": 32,\n                    \"unknown\": 0\n                },\n                \"penalty\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"foul\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"yellow_card\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 4\n                },\n                \"red_card\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"mistake\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"good_play\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"highlight\": {\n                    \"home\": 0,\n                    \"away\": 2,\n                    \"unknown\": 0\n                }\n            }\n        }\n    },\n    \"totals\": {\n        \"set_pieces\": {\n            \"home\": 8,\n            \"away\": 23,\n            \"unknown\": 0\n        },\n        \"cards\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 2\n        },\n        \"highlights\": {\n            \"total\": 1,\n            \"by_team\": {\n                \"home\": 0,\n                \"away\": 1,\n                \"unknown\": 0\n            }\n        }\n    }\n}'),
(56, 1, 166, '2026-01-19 16:24:55', '{\n    \"computed_at\": \"2026-01-19 16:24:55\",\n    \"events_version_used\": 166,\n    \"by_type_team\": {\n        \"goal\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"shot\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"shot_on_target\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"shot_off_target\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"chance\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"corner\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"free_kick\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"penalty\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"foul\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"yellow_card\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"red_card\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"mistake\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"good_play\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"highlight\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        }\n    },\n    \"phase_2\": {\n        \"by_period\": {\n            \"1H\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            },\n            \"2H\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            },\n            \"ET\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            },\n            \"other\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            }\n        },\n        \"per_15_minute\": [],\n        \"importance_weighted\": {\n            \"by_team\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            },\n            \"by_type_team\": {\n                \"goal\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"shot\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"shot_on_target\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"shot_off_target\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"chance\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"corner\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"free_kick\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"penalty\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"foul\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"yellow_card\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"red_card\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"mistake\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"good_play\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"highlight\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                }\n            }\n        }\n    },\n    \"totals\": {\n        \"set_pieces\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"cards\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"highlights\": {\n            \"total\": 0,\n            \"by_team\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            }\n        }\n    }\n}'),
(57, 1, 171, '2026-01-19 16:27:51', '{\n    \"computed_at\": \"2026-01-19 16:27:51\",\n    \"events_version_used\": 171,\n    \"by_type_team\": {\n        \"goal\": {\n            \"home\": 1,\n            \"away\": 4,\n            \"unknown\": 0\n        },\n        \"shot\": {\n            \"home\": 1,\n            \"away\": 4,\n            \"unknown\": 0\n        },\n        \"shot_on_target\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"shot_off_target\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"chance\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"corner\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"free_kick\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"penalty\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"foul\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"yellow_card\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"red_card\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"mistake\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"good_play\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"highlight\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        }\n    },\n    \"phase_2\": {\n        \"by_period\": {\n            \"1H\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            },\n            \"2H\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            },\n            \"ET\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            },\n            \"other\": {\n                \"home\": 1,\n                \"away\": 4,\n                \"unknown\": 0\n            }\n        },\n        \"per_15_minute\": [\n            {\n                \"label\": \"0-15\",\n                \"home\": 1,\n                \"away\": 1,\n                \"unknown\": 0\n            },\n            {\n                \"label\": \"15-30\",\n                \"home\": 0,\n                \"away\": 1,\n                \"unknown\": 0\n            },\n            {\n                \"label\": \"30-45\",\n                \"home\": 0,\n                \"away\": 1,\n                \"unknown\": 0\n            },\n            {\n                \"label\": \"45-60\",\n                \"home\": 0,\n                \"away\": 1,\n                \"unknown\": 0\n            }\n        ],\n        \"importance_weighted\": {\n            \"by_team\": {\n                \"home\": 5,\n                \"away\": 20,\n                \"unknown\": 0\n            },\n            \"by_type_team\": {\n                \"goal\": {\n                    \"home\": 5,\n                    \"away\": 20,\n                    \"unknown\": 0\n                },\n                \"shot\": {\n                    \"home\": 5,\n                    \"away\": 20,\n                    \"unknown\": 0\n                },\n                \"shot_on_target\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"shot_off_target\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"chance\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"corner\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"free_kick\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"penalty\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"foul\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"yellow_card\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"red_card\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"mistake\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"good_play\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"highlight\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                }\n            }\n        }\n    },\n    \"totals\": {\n        \"set_pieces\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"cards\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"highlights\": {\n            \"total\": 0,\n            \"by_team\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            }\n        }\n    }\n}'),
(58, 1, 176, '2026-01-19 17:47:48', '{\n    \"computed_at\": \"2026-01-19 17:47:48\",\n    \"events_version_used\": 176,\n    \"by_type_team\": {\n        \"goal\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"shot\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"shot_on_target\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"shot_off_target\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"chance\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"corner\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"free_kick\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"penalty\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"foul\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"yellow_card\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"red_card\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"mistake\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"good_play\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"highlight\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        }\n    },\n    \"phase_2\": {\n        \"by_period\": {\n            \"1H\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            },\n            \"2H\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            },\n            \"ET\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            },\n            \"other\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            }\n        },\n        \"per_15_minute\": [],\n        \"importance_weighted\": {\n            \"by_team\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            },\n            \"by_type_team\": {\n                \"goal\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"shot\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"shot_on_target\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"shot_off_target\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"chance\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"corner\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"free_kick\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"penalty\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"foul\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"yellow_card\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"red_card\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"mistake\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"good_play\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"highlight\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                }\n            }\n        }\n    },\n    \"totals\": {\n        \"set_pieces\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"cards\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"highlights\": {\n            \"total\": 0,\n            \"by_team\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            }\n        }\n    }\n}');

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

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `match_id`, `period_id`, `match_second`, `minute`, `minute_extra`, `team_side`, `event_type_id`, `importance`, `phase`, `match_player_id`, `player_id`, `opponent_detail`, `outcome`, `zone`, `notes`, `created_by`, `created_at`, `updated_by`, `updated_at`, `match_period_id`, `clip_id`, `clip_start_second`, `clip_end_second`) VALUES
(258, 3, 5, 14, 0, 0, 'unknown', 13, 1, 'unknown', NULL, NULL, NULL, NULL, NULL, 'First Half', 1, '2026-01-17 20:45:26', NULL, NULL, NULL, NULL, NULL, NULL),
(259, 3, 5, 188, 3, 0, 'home', 15, 3, 'unknown', 69, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-01-17 21:08:40', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(260, 3, 5, 264, 4, 0, 'home', 15, 3, 'unknown', 68, NULL, NULL, 'on_target', NULL, NULL, 1, '2026-01-17 21:09:25', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(263, 3, 5, 297, 4, 0, 'home', 5, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 21:10:59', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(264, 3, 5, 309, 5, 0, 'home', 15, 3, 'unknown', 62, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-01-17 21:12:30', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(265, 3, 5, 570, 9, 0, 'home', 5, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 21:18:39', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(266, 3, 5, 670, 11, 0, 'away', 5, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 21:20:55', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(267, 3, 5, 824, 13, 0, 'home', 4, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 21:23:33', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(269, 3, 5, 999, 16, 0, 'away', 4, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 21:27:59', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(270, 3, 5, 1158, 19, 0, 'away', 5, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 21:30:54', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(271, 3, 5, 1303, 21, 0, 'away', 5, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 21:33:44', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(272, 3, 5, 1332, 22, 0, 'away', 5, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 21:40:06', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(273, 3, 5, 1464, 24, 0, 'home', 5, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 21:42:38', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(274, 3, 5, 1516, 25, 0, 'home', 15, 3, 'unknown', 69, NULL, NULL, 'on_target', NULL, NULL, 1, '2026-01-17 21:43:37', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(275, 3, 5, 1516, 25, 0, 'home', 4, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 21:43:41', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(276, 3, 5, 1536, 25, 0, 'home', 15, 3, 'unknown', 60, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-01-17 21:44:13', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(277, 3, 5, 1612, 26, 0, 'home', 5, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 21:45:30', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(278, 3, 5, 1684, 28, 0, 'away', 15, 3, 'unknown', NULL, NULL, NULL, 'on_target', NULL, NULL, 1, '2026-01-17 21:47:13', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(279, 3, 5, 1730, 28, 0, 'home', 5, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 21:48:20', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(280, 3, 5, 1771, 29, 0, 'home', 15, 3, 'unknown', 62, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-01-17 21:52:40', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(281, 3, 5, 1994, 33, 0, 'away', 15, 3, 'unknown', NULL, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-01-17 21:56:25', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(282, 3, 5, 2134, 35, 0, 'home', 16, 5, 'unknown', 66, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 21:59:00', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(283, 3, 5, 363, 6, 0, 'away', 15, 3, 'unknown', NULL, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-01-17 22:06:10', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(285, 3, 5, 2312, 38, 0, 'home', 15, 3, 'unknown', 64, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-01-17 22:15:53', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(286, 3, 5, 2392, 39, 0, 'home', 5, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 22:17:13', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(287, 3, 5, 2398, 39, 0, 'unknown', 8, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 22:17:20', NULL, '2026-01-18 11:35:49', NULL, NULL, NULL, NULL),
(288, 3, 5, 2552, 42, 0, 'home', 5, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 22:21:04', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(289, 3, 5, 2661, 44, 0, 'home', 16, 5, 'unknown', 68, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 22:22:55', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(290, 3, 5, 2710, 45, 0, 'unknown', 14, 1, 'unknown', NULL, NULL, NULL, NULL, NULL, 'First Half', 1, '2026-01-17 22:23:51', NULL, NULL, NULL, NULL, NULL, NULL),
(291, 3, 5, 3526, 58, 14, 'unknown', 13, 1, 'unknown', NULL, NULL, NULL, NULL, NULL, 'Second Half', 1, '2026-01-17 22:26:56', NULL, NULL, NULL, NULL, NULL, NULL),
(292, 3, 5, 3617, 60, 16, 'home', 5, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 22:28:30', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(293, 3, 5, 3663, 61, 16, 'home', 15, 3, 'unknown', 65, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-01-17 22:29:46', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(294, 3, 5, 3922, 65, 21, 'home', 15, 3, 'unknown', 69, NULL, NULL, 'on_target', NULL, NULL, 1, '2026-01-17 22:46:46', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(295, 3, 5, 3979, 66, 22, 'home', 15, 3, 'unknown', 60, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-01-17 22:47:47', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(296, 3, 5, 4193, 69, 25, 'home', 5, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 22:51:21', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(297, 3, 5, 4287, 71, 27, 'home', 15, 3, 'unknown', 65, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-01-17 22:52:58', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(298, 3, 5, 4447, 74, 29, 'away', 15, 3, 'unknown', NULL, NULL, NULL, 'on_target', NULL, NULL, 1, '2026-01-17 22:55:41', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(299, 3, 5, 4521, 75, 31, 'away', 5, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 22:56:55', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(300, 3, 5, 4623, 77, 32, 'home', 5, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 22:58:37', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(301, 3, 5, 4669, 77, 33, 'home', 15, 3, 'unknown', 65, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-01-17 22:59:37', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(302, 3, 5, 4806, 80, 35, 'home', 12, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 23:02:09', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(303, 3, 5, 4982, 83, 38, 'home', 4, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 23:05:05', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(304, 3, 5, 5013, 83, 39, 'home', 4, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 23:05:36', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(305, 3, 5, 5071, 84, 40, 'home', 15, 3, 'unknown', 64, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-01-17 23:06:40', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(306, 3, 5, 5125, 85, 41, 'away', 5, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 23:08:10', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(307, 3, 5, 5218, 86, 42, 'home', 5, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 23:09:58', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(308, 3, 5, 5223, 87, 42, 'unknown', 8, 2, 'unknown', 60, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 23:12:30', NULL, '2026-01-18 11:36:19', NULL, NULL, NULL, NULL),
(309, 3, 5, 5331, 88, 44, 'home', 15, 3, 'unknown', 68, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-01-17 23:18:10', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(310, 3, 5, 5396, 89, 45, 'home', 5, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 23:19:15', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(311, 3, 5, 5426, 90, 46, 'home', 15, 3, 'unknown', 65, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-01-17 23:19:48', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(312, 3, 5, 5446, 90, 46, 'home', 16, 5, 'unknown', 60, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 23:20:10', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(313, 3, 5, 5570, 92, 48, 'home', 5, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 23:22:09', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(314, 3, 5, 5663, 94, 50, 'home', 15, 3, 'unknown', 62, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-01-17 23:23:45', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(315, 3, 5, 5691, 94, 50, 'home', 5, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 23:24:13', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(316, 3, 5, 5777, 96, 52, 'home', 4, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 23:25:43', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(317, 3, 5, 5442, 90, 46, 'home', 4, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 23:26:09', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(318, 3, 5, 5801, 96, 52, 'home', 15, 3, 'unknown', 70, NULL, NULL, 'on_target', NULL, NULL, 1, '2026-01-17 23:26:55', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(319, 3, 5, 5839, 97, 53, 'away', 5, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 23:27:33', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(320, 3, 5, 6006, 100, 55, 'home', 5, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 23:30:21', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(321, 3, 5, 6033, 100, 56, 'home', 5, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 23:30:55', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(322, 3, 5, 6081, 101, 57, 'home', 16, 5, 'unknown', 65, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 23:31:47', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(323, 3, 5, 6180, 103, 58, 'home', 15, 3, 'unknown', 68, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-01-17 23:33:24', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(325, 3, 5, 6241, 104, 59, 'unknown', 13, 1, 'unknown', NULL, NULL, NULL, NULL, NULL, 'Second Half', 1, '2026-01-17 23:37:21', NULL, NULL, NULL, NULL, NULL, NULL),
(332, 5, NULL, 0, 0, 0, 'home', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(333, 5, NULL, 0, 0, 0, 'away', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(334, 5, NULL, 0, 0, 0, 'away', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(335, 5, NULL, 0, 0, 0, 'away', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(336, 5, NULL, 0, 0, 0, 'away', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(337, 6, NULL, 0, 0, 0, 'home', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(338, 6, NULL, 0, 0, 0, 'home', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(339, 6, NULL, 0, 0, 0, 'home', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(340, 6, NULL, 0, 0, 0, 'home', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(341, 6, NULL, 0, 0, 0, 'home', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(342, 6, NULL, 0, 0, 0, 'away', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(343, 7, NULL, 0, 0, 0, 'home', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(344, 7, NULL, 0, 0, 0, 'home', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(345, 7, NULL, 0, 0, 0, 'home', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(346, 7, NULL, 0, 0, 0, 'home', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(347, 8, NULL, 0, 0, 0, 'away', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(348, 8, NULL, 0, 0, 0, 'away', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(349, 9, NULL, 0, 0, 0, 'home', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(350, 9, NULL, 0, 0, 0, 'home', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(351, 9, NULL, 0, 0, 0, 'home', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(352, 9, NULL, 0, 0, 0, 'home', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(353, 9, NULL, 0, 0, 0, 'home', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(354, 9, NULL, 0, 0, 0, 'away', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(355, 10, NULL, 0, 0, 0, 'away', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(356, 11, NULL, 0, 0, 0, 'home', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(357, 11, NULL, 0, 0, 0, 'home', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(358, 11, NULL, 0, 0, 0, 'away', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(359, 12, NULL, 0, 0, 0, 'home', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(360, 12, NULL, 0, 0, 0, 'home', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(361, 13, NULL, 0, 0, 0, 'home', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(362, 13, NULL, 0, 0, 0, 'away', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(363, 13, NULL, 0, 0, 0, 'away', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(364, 14, NULL, 0, 0, 0, 'home', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(365, 14, NULL, 0, 0, 0, 'away', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(366, 14, NULL, 0, 0, 0, 'away', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(367, 14, NULL, 0, 0, 0, 'away', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(368, 15, NULL, 0, 0, 0, 'home', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(369, 15, NULL, 0, 0, 0, 'away', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(370, 15, NULL, 0, 0, 0, 'away', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(371, 15, NULL, 0, 0, 0, 'away', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(372, 17, NULL, 0, 0, 0, 'home', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(373, 17, NULL, 0, 0, 0, 'home', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(374, 17, NULL, 0, 0, 0, 'home', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(375, 17, NULL, 0, 0, 0, 'home', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(376, 17, NULL, 0, 0, 0, 'home', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(377, 17, NULL, 0, 0, 0, 'away', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(378, 18, NULL, 0, 0, 0, 'home', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(379, 18, NULL, 0, 0, 0, 'away', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(380, 18, NULL, 0, 0, 0, 'away', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(381, 19, NULL, 0, 0, 0, 'home', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(382, 19, NULL, 0, 0, 0, 'home', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(383, 19, NULL, 0, 0, 0, 'away', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(384, 20, NULL, 0, 0, 0, 'away', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(385, 21, NULL, 0, 0, 0, 'home', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(386, 21, NULL, 0, 0, 0, 'home', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(387, 21, NULL, 0, 0, 0, 'home', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(388, 21, NULL, 0, 0, 0, 'away', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(389, 21, NULL, 0, 0, 0, 'away', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(390, 21, NULL, 0, 0, 0, 'away', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(391, 21, NULL, 0, 0, 0, 'away', 16, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 13:01:27', NULL, NULL, NULL, NULL, NULL, NULL),
(397, 1, NULL, 2732, 45, 0, 'home', 16, 5, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 17:48:01', NULL, NULL, NULL, NULL, NULL, NULL),
(398, 1, NULL, 3009, 50, 0, 'away', 16, 5, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 17:48:09', NULL, NULL, NULL, NULL, NULL, NULL),
(399, 1, NULL, 3152, 52, 0, 'away', 16, 5, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 17:48:15', NULL, NULL, NULL, NULL, NULL, NULL),
(400, 1, NULL, 3380, 56, 0, 'away', 16, 5, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 17:48:20', NULL, NULL, NULL, NULL, NULL, NULL),
(401, 1, NULL, 3487, 58, 0, 'away', 16, 5, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 17:48:24', NULL, NULL, NULL, NULL, NULL, NULL);

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

--
-- Dumping data for table `event_snapshots`
--

INSERT INTO `event_snapshots` (`id`, `event_id`, `match_id`, `snapshot_json`, `created_at`) VALUES
(75, 397, 1, '{\"id\":397,\"match_id\":1,\"period_id\":null,\"match_second\":2732,\"minute\":45,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-19 17:48:01\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":null,\"tags\":[]}', '2026-01-19 17:48:01'),
(76, 398, 1, '{\"id\":398,\"match_id\":1,\"period_id\":null,\"match_second\":3009,\"minute\":50,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-19 17:48:09\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":null,\"tags\":[]}', '2026-01-19 17:48:09'),
(77, 399, 1, '{\"id\":399,\"match_id\":1,\"period_id\":null,\"match_second\":3152,\"minute\":52,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-19 17:48:15\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":null,\"tags\":[]}', '2026-01-19 17:48:15'),
(78, 400, 1, '{\"id\":400,\"match_id\":1,\"period_id\":null,\"match_second\":3380,\"minute\":56,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-19 17:48:20\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":null,\"tags\":[]}', '2026-01-19 17:48:20'),
(79, 401, 1, '{\"id\":401,\"match_id\":1,\"period_id\":null,\"match_second\":3487,\"minute\":58,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-19 17:48:24\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":\"\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":null,\"tags\":[]}', '2026-01-19 17:48:24');

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

--
-- Dumping data for table `event_types`
--

INSERT INTO `event_types` (`id`, `club_id`, `type_key`, `label`, `default_importance`, `created_at`) VALUES
(3, 1, 'chance', 'Chance', 2, '2025-12-20 21:03:33'),
(4, 1, 'corner', 'Corner', 2, '2025-12-20 21:03:33'),
(5, 1, 'free_kick', 'Free Kick', 2, '2025-12-20 21:03:33'),
(6, 1, 'penalty', 'Penalty', 2, '2025-12-20 21:03:33'),
(8, 1, 'yellow_card', 'Yellow Card', 2, '2025-12-20 21:03:33'),
(9, 1, 'red_card', 'Red Card', 2, '2025-12-20 21:03:33'),
(10, 1, 'mistake', 'Mistake', 2, '2025-12-20 21:03:33'),
(11, 1, 'good_play', 'Good Play', 2, '2025-12-20 21:03:33'),
(12, 1, 'highlight', 'Highlight', 2, '2025-12-20 21:03:33'),
(13, 1, 'period_start', 'Period Start', 3, '2025-12-20 21:03:35'),
(14, 1, 'period_end', 'Period End', 3, '2025-12-20 21:03:35'),
(15, 1, 'shot', 'Shot', 3, '2025-12-20 21:03:35'),
(16, 1, 'goal', 'Goal', 5, '2025-12-20 21:03:35'),
(17, 1, 'foul', 'Foul', 3, '2025-12-20 21:03:35'),
(18, 1, 'turnover', 'Turnover', 2, '2025-12-20 21:03:35'),
(20, 1, 'substitution', 'Substitution', 2, '2026-01-15 16:12:19');

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

--
-- Dumping data for table `formations`
--

INSERT INTO `formations` (`id`, `format`, `formation_key`, `label`, `player_count`, `is_fixed`, `created_at`) VALUES
(1, '11-a-side', '4-4-2', '4-4-2', 11, 1, '2026-01-15 09:51:14'),
(2, '11-a-side', '4-3-3', '4-3-3', 11, 1, '2026-01-15 09:51:14'),
(3, '11-a-side', '4-5-1', '4-5-1', 11, 1, '2026-01-15 09:51:14'),
(4, '11-a-side', '3-5-2', '3-5-2', 11, 1, '2026-01-15 09:51:14'),
(5, '11-a-side', '3-4-3', '3-4-3', 11, 1, '2026-01-15 09:51:14'),
(6, '11-a-side', '4-2-3-1', '4-2-3-1', 11, 1, '2026-01-15 09:51:14'),
(7, '11-a-side', 'custom', 'Custom', 11, 0, '2026-01-15 09:51:14'),
(8, '11-a-side', 'unset', 'Unset', 11, 1, '2026-01-15 09:51:14');

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

--
-- Dumping data for table `formation_positions`
--

INSERT INTO `formation_positions` (`id`, `formation_id`, `slot_index`, `position_label`, `left_percent`, `bottom_percent`, `rotation_deg`) VALUES
(32, 1, 0, 'GK', 50.000, 0.000, 0),
(33, 1, 1, 'LB', 0.000, 33.333, 0),
(34, 1, 2, 'CB', 33.333, 33.333, 0),
(35, 1, 3, 'CB', 66.667, 33.333, 0),
(36, 1, 4, 'RB', 100.000, 33.333, 0),
(37, 1, 5, 'LM', 0.000, 66.667, 0),
(38, 1, 6, 'CM', 33.333, 66.667, 0),
(39, 1, 7, 'CM', 66.667, 66.667, 0),
(40, 1, 8, 'RM', 100.000, 66.667, 0),
(41, 1, 9, 'ST', 25.000, 100.000, 0),
(42, 1, 10, 'ST', 75.000, 100.000, 0),
(47, 2, 0, 'GK', 50.000, 0.000, 0),
(48, 2, 1, 'LB', 0.000, 33.333, 0),
(49, 2, 2, 'CB', 33.333, 33.333, 0),
(50, 2, 3, 'CB', 66.667, 33.333, 0),
(51, 2, 4, 'RB', 100.000, 33.333, 0),
(52, 2, 5, 'CM', 25.000, 66.667, 0),
(53, 2, 6, 'CM', 50.000, 66.667, 0),
(54, 2, 7, 'CM', 75.000, 66.667, 0),
(55, 2, 8, 'LW', 15.000, 100.000, 0),
(56, 2, 9, 'ST', 50.000, 100.000, 0),
(57, 2, 10, 'RW', 85.000, 100.000, 0),
(63, 4, 0, 'GK', 50.000, 0.000, 0),
(64, 4, 1, 'CB', 20.000, 33.333, 0),
(65, 4, 2, 'CB', 50.000, 33.333, 0),
(66, 4, 3, 'CB', 80.000, 33.333, 0),
(67, 4, 4, 'LM', 0.000, 66.667, 0),
(68, 4, 5, 'CM', 25.000, 66.667, 0),
(69, 4, 6, 'CM', 50.000, 66.667, 0),
(70, 4, 7, 'CM', 75.000, 66.667, 0),
(71, 4, 8, 'RM', 100.000, 66.667, 0),
(72, 4, 9, 'ST', 35.000, 100.000, 0),
(73, 4, 10, 'ST', 65.000, 100.000, 0),
(78, 5, 0, 'GK', 50.000, 0.000, 0),
(79, 5, 1, 'CB', 20.000, 33.333, 0),
(80, 5, 2, 'CB', 50.000, 33.333, 0),
(81, 5, 3, 'CB', 80.000, 33.333, 0),
(82, 5, 4, 'LM', 15.000, 66.667, 0),
(83, 5, 5, 'CM', 40.000, 66.667, 0),
(84, 5, 6, 'CM', 60.000, 66.667, 0),
(85, 5, 7, 'RM', 85.000, 66.667, 0),
(86, 5, 8, 'LW', 20.000, 100.000, 0),
(87, 5, 9, 'ST', 50.000, 100.000, 0),
(88, 5, 10, 'RW', 80.000, 100.000, 0),
(93, 6, 0, 'GK', 50.000, 0.000, 0),
(94, 6, 1, 'LB', 0.000, 33.333, 0),
(95, 6, 2, 'CB', 33.333, 33.333, 0),
(96, 6, 3, 'CB', 66.667, 33.333, 0),
(97, 6, 4, 'RB', 100.000, 33.333, 0),
(98, 6, 5, 'DM', 40.000, 66.667, 0),
(99, 6, 6, 'DM', 60.000, 66.667, 0),
(100, 6, 7, 'LM', 20.000, 85.000, 0),
(101, 6, 8, 'AM', 50.000, 85.000, 0),
(102, 6, 9, 'RM', 80.000, 85.000, 0),
(103, 6, 10, 'ST', 50.000, 100.000, 0),
(108, 3, 0, 'GK', 50.000, 0.000, 0),
(109, 3, 1, 'LB', 0.000, 33.333, 0),
(110, 3, 2, 'CB', 33.333, 33.333, 0),
(111, 3, 3, 'CB', 66.667, 33.333, 0),
(112, 3, 4, 'RB', 100.000, 33.333, 0),
(113, 3, 5, 'LM', 0.000, 66.667, 0),
(114, 3, 6, 'CM', 25.000, 66.667, 0),
(115, 3, 7, 'CM', 50.000, 66.667, 0),
(116, 3, 8, 'CM', 75.000, 66.667, 0),
(117, 3, 9, 'RM', 100.000, 66.667, 0),
(118, 3, 10, 'ST', 50.000, 100.000, 0);

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

--
-- Dumping data for table `matches`
--

INSERT INTO `matches` (`id`, `club_id`, `season_id`, `competition_id`, `home_team_id`, `away_team_id`, `kickoff_at`, `match_video`, `venue`, `referee`, `attendance`, `status`, `notes`, `events_version`, `clips_version`, `derived_version`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 2, 3, 2, '2026-01-08 17:19:00', NULL, '', '', NULL, 'ready', NULL, 181, 79, 176, 1, '2025-12-25 14:17:47', '2026-01-19 17:48:24'),
(3, 1, 1, 2, 2, 4, '2026-01-17 13:30:00', NULL, NULL, NULL, NULL, 'ready', NULL, 77, 0, 77, 1, '2026-01-17 20:37:29', '2026-01-19 15:05:12'),
(5, 1, 1, 2, 4, 2, '2025-07-26 14:30:00', NULL, NULL, NULL, NULL, 'ready', NULL, 0, 0, 0, 1, '2026-01-19 13:01:27', NULL),
(6, 1, 1, 2, 2, 6, '2025-07-30 19:30:00', NULL, NULL, NULL, NULL, 'ready', NULL, 0, 0, 0, 1, '2026-01-19 13:01:27', NULL),
(7, 1, 1, 2, 7, 2, '2025-08-02 14:00:00', NULL, NULL, NULL, NULL, 'ready', NULL, 0, 0, 0, 1, '2026-01-19 13:01:27', NULL),
(8, 1, 1, 2, 2, 8, '2025-08-09 14:00:00', NULL, NULL, NULL, NULL, 'ready', NULL, 0, 0, 0, 1, '2026-01-19 13:01:27', NULL),
(9, 1, 1, 3, 9, 2, '2025-08-16 14:30:00', NULL, NULL, NULL, NULL, 'ready', NULL, 0, 0, 0, 1, '2026-01-19 13:01:27', NULL),
(10, 1, 1, 2, 2, 10, '2025-08-23 14:00:00', NULL, NULL, NULL, NULL, 'ready', NULL, 0, 0, 0, 1, '2026-01-19 13:01:27', NULL),
(11, 1, 1, 2, 11, 2, '2025-08-30 14:00:00', NULL, NULL, NULL, NULL, 'ready', NULL, 0, 0, 0, 1, '2026-01-19 13:01:27', NULL),
(12, 1, 1, 2, 12, 2, '2025-09-06 14:00:00', NULL, NULL, NULL, NULL, 'ready', NULL, 0, 0, 0, 1, '2026-01-19 13:01:27', NULL),
(13, 1, 1, 4, 13, 2, '2025-09-20 14:30:00', NULL, NULL, NULL, NULL, 'ready', NULL, 0, 0, 0, 1, '2026-01-19 13:01:27', NULL),
(14, 1, 1, 2, 2, 14, '2025-09-27 14:00:00', NULL, NULL, NULL, NULL, 'ready', NULL, 0, 0, 0, 1, '2026-01-19 13:01:27', NULL),
(15, 1, 1, 5, 2, 15, '2025-10-04 14:00:00', NULL, NULL, NULL, NULL, 'ready', NULL, 0, 0, 0, 1, '2026-01-19 13:01:27', NULL),
(16, 1, 1, 2, 16, 2, '2025-10-11 14:00:00', NULL, NULL, NULL, NULL, 'ready', NULL, 0, 0, 0, 1, '2026-01-19 13:01:27', NULL),
(17, 1, 1, 4, 17, 2, '2025-10-18 14:30:00', NULL, NULL, NULL, NULL, 'ready', NULL, 0, 0, 0, 1, '2026-01-19 13:01:27', '2026-01-19 14:57:32'),
(18, 1, 1, 2, 18, 2, '2025-10-25 14:00:00', NULL, NULL, NULL, NULL, 'ready', NULL, 0, 0, 0, 1, '2026-01-19 13:01:27', NULL),
(19, 1, 1, 2, 2, 19, '2025-11-08 14:00:00', NULL, NULL, NULL, NULL, 'ready', NULL, 0, 0, 0, 1, '2026-01-19 13:01:27', NULL),
(20, 1, 1, 2, 2, 20, '2025-11-22 13:30:00', NULL, NULL, NULL, NULL, 'ready', NULL, 0, 0, 0, 1, '2026-01-19 13:01:27', NULL),
(21, 1, 1, 2, 21, 2, '2025-11-29 13:30:00', NULL, NULL, NULL, NULL, 'ready', NULL, 0, 0, 0, 1, '2026-01-19 13:01:27', NULL);

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

--
-- Dumping data for table `match_formations`
--

INSERT INTO `match_formations` (`id`, `match_id`, `team_side`, `match_period_id`, `match_second`, `minute`, `minute_extra`, `format`, `formation_key`, `layout_json`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(5, 1, 'home', NULL, 0, 0, 0, '11-a-side', '4-4-2', NULL, NULL, 1, '2026-01-15 11:43:46', '2026-01-19 16:26:11'),
(21, 1, 'away', NULL, 0, 0, 0, '11-a-side', '3-4-3', NULL, NULL, 1, '2026-01-15 12:16:46', '2026-01-19 16:26:11'),
(22, 3, 'home', NULL, 0, 0, 0, '11-a-side', '3-5-2', NULL, NULL, 1, '2026-01-17 21:02:35', '2026-01-19 16:00:35'),
(23, 3, 'away', NULL, 0, 0, 0, '11-a-side', '3-4-3', NULL, NULL, 1, '2026-01-17 21:02:35', '2026-01-19 16:00:31');

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

--
-- Dumping data for table `match_locks`
--

INSERT INTO `match_locks` (`match_id`, `locked_by`, `locked_at`, `last_heartbeat_at`) VALUES
(1, 1, '2026-01-19 16:25:06', '2026-01-19 17:48:20'),
(3, 1, '2026-01-17 20:44:30', '2026-01-19 17:51:52');

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

--
-- Dumping data for table `match_periods`
--

INSERT INTO `match_periods` (`id`, `match_id`, `period_key`, `label`, `start_second`, `end_second`, `created_at`, `updated_at`) VALUES
(5, 3, 'first_half', 'First Half', 14, 2710, '2026-01-17 20:45:26', '2026-01-17 22:23:51'),
(6, 3, 'second_half', 'Second Half', 3705, 6241, '2026-01-17 22:26:56', '2026-01-17 23:38:06');

-- --------------------------------------------------------

--
-- Table structure for table `match_players`
--

CREATE TABLE `match_players` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `match_id` bigint(20) UNSIGNED NOT NULL,
  `team_side` enum('home','away') NOT NULL,
  `player_id` bigint(20) UNSIGNED DEFAULT NULL,
  `display_name` varchar(120) NOT NULL,
  `shirt_number` int(11) DEFAULT NULL,
  `position_label` varchar(40) DEFAULT NULL,
  `is_starting` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `is_captain` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `match_players`
--

INSERT INTO `match_players` (`id`, `match_id`, `team_side`, `player_id`, `display_name`, `shirt_number`, `position_label`, `is_starting`, `created_at`, `is_captain`) VALUES
(59, 3, 'away', 1, '', 1, 'GK', 1, '2026-01-17 21:02:50', 0),
(60, 3, 'away', 3, '', 2, 'CB', 1, '2026-01-17 21:03:21', 0),
(61, 3, 'away', 4, '', 3, 'CB', 1, '2026-01-17 21:03:28', 0),
(62, 3, 'away', 15, '', 4, 'CB', 1, '2026-01-17 21:03:37', 0),
(63, 3, 'away', 20, '', 5, 'LM', 1, '2026-01-17 21:04:10', 0),
(64, 3, 'away', 21, '', 6, 'CM', 1, '2026-01-17 21:04:45', 1),
(65, 3, 'away', 7, '', 7, 'CM', 1, '2026-01-17 21:04:57', 0),
(66, 3, 'away', 9, '', 8, 'CM', 1, '2026-01-17 21:05:11', 0),
(67, 3, 'away', 22, '', 9, 'RM', 1, '2026-01-17 21:05:47', 0),
(68, 3, 'away', 10, '', 10, 'ST', 1, '2026-01-17 21:05:57', 0),
(69, 3, 'away', 23, '', 11, 'ST', 1, '2026-01-17 21:06:27', 0),
(70, 3, 'away', 12, '', NULL, NULL, 0, '2026-01-17 21:06:40', 0),
(71, 3, 'away', 17, '', NULL, NULL, 0, '2026-01-17 21:06:43', 0),
(72, 3, 'away', 6, '', NULL, NULL, 0, '2026-01-17 21:06:48', 0),
(73, 3, 'away', 18, '', NULL, NULL, 0, '2026-01-17 21:08:20', 0),
(75, 1, 'away', 10, '', NULL, 'LW', 1, '2026-01-19 16:26:39', 0);

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
  `reason` enum('tactical','injury','fitness','disciplinary','unknown') NOT NULL DEFAULT 'unknown',
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
  `duration_seconds` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `source_type` enum('upload','veo') NOT NULL DEFAULT 'upload',
  `source_url` text DEFAULT NULL,
  `download_status` enum('pending','downloading','completed','failed') NOT NULL DEFAULT 'pending',
  `download_progress` tinyint(3) UNSIGNED DEFAULT 0,
  `error_message` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `match_videos`
--

INSERT INTO `match_videos` (`id`, `match_id`, `source_path`, `duration_seconds`, `created_at`, `source_type`, `source_url`, `download_status`, `download_progress`, `error_message`) VALUES
(1, 1, '/videos/matches/match_1/source/veo/standard/match_1_standard.mp4', 6686, '2025-12-25 14:17:54', 'veo', 'https://app.veo.co/matches/20251213-rossvale-1-4-saltcoats-8ae3733c/', 'completed', 100, NULL),
(4, 3, '/videos/raw/match_3_4K.mp4', NULL, '2026-01-18 11:26:35', 'veo', 'https://app.veo.co/matches/20260117-saltcoats-4-0-campbelltown-1586c41b/', '', 0, NULL);

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
  `display_name` varchar(120) NOT NULL,
  `dob` date DEFAULT NULL,
  `primary_position` varchar(40) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `players`
--

INSERT INTO `players` (`id`, `club_id`, `team_id`, `first_name`, `last_name`, `display_name`, `dob`, `primary_position`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 'C.', 'Robertson', 'C. Robertson', NULL, 'GK', 1, '2026-01-09 09:09:56', '2026-01-09 10:14:04'),
(2, 1, 2, 'R.', 'Ritchie', 'R. Ritchie', NULL, 'ST', 1, '2026-01-09 09:09:56', '2026-01-09 10:14:07'),
(3, 1, 2, 'R.', 'Agnew', 'R. Agnew', NULL, 'GK', 1, '2026-01-09 09:09:56', '2026-01-09 10:14:50'),
(4, 1, 2, 'A.', 'McIntyre', 'A. McIntyre', NULL, NULL, 1, '2026-01-09 09:09:56', NULL),
(5, 1, 2, 'J.', 'Cousar', 'J. Cousar', NULL, NULL, 1, '2026-01-09 09:09:56', NULL),
(6, 1, 2, 'M.', 'Beveridge', 'M. Beveridge', NULL, NULL, 1, '2026-01-09 09:09:56', NULL),
(7, 1, 2, 'J.', 'Hanlon', 'J. Hanlon', NULL, NULL, 1, '2026-01-09 09:09:56', NULL),
(8, 1, 2, 'R.', 'Johnston', 'R. Johnston', NULL, NULL, 1, '2026-01-09 09:09:56', NULL),
(9, 1, 2, 'B.', 'McCullough', 'B. McCullough', NULL, NULL, 1, '2026-01-09 09:09:56', NULL),
(10, 1, 2, 'D.', 'Sawyer', 'D. Sawyer', NULL, NULL, 1, '2026-01-09 09:09:56', NULL),
(11, 1, 2, 'E.', 'Anderson', 'E. Anderson', NULL, NULL, 1, '2026-01-09 09:09:56', NULL),
(12, 1, 2, 'A.', 'Love', 'A. Love', NULL, NULL, 1, '2026-01-09 09:09:56', NULL),
(13, 1, 2, 'R.', 'Eaglesham', 'R. Eaglesham', NULL, NULL, 1, '2026-01-09 09:09:56', NULL),
(14, 1, 2, 'G.', 'McIntyre', 'G. McIntyre', NULL, NULL, 1, '2026-01-09 09:09:56', NULL),
(15, 1, 2, 'J.', 'Stirling', 'J. Stirling', NULL, NULL, 1, '2026-01-09 09:09:56', NULL),
(16, 1, 2, 'I.', 'Donachy', 'I. Donachy', NULL, NULL, 1, '2026-01-09 09:09:56', NULL),
(17, 1, 2, 'A.', 'Tait', 'A. Tait', NULL, NULL, 1, '2026-01-09 09:09:56', NULL),
(18, 1, 2, 'A.', 'Kamara', 'A. Kamara', NULL, NULL, 1, '2026-01-09 09:09:56', NULL),
(19, 1, 2, 'A.', 'Hussey', 'A. Hussey', NULL, NULL, 1, '2026-01-09 09:09:56', NULL),
(20, 1, 2, 'Reiss', 'Love', 'R. Love', NULL, '', 1, '2026-01-17 21:04:05', NULL),
(21, 1, 2, 'Rudi', 'Johnston', 'R. Johnston', NULL, '', 1, '2026-01-17 21:04:38', NULL),
(22, 1, 2, 'Cameron', 'McIntyre', 'C. McIntyre', NULL, '', 1, '2026-01-17 21:05:41', NULL),
(23, 1, 2, 'Aaron', 'Robertson', 'A. Robertson', NULL, '', 1, '2026-01-17 21:06:25', NULL),
(24, 1, 2, 'Lewis', 'Donachy', 'L. Donachy', NULL, NULL, 1, '2026-01-17 21:08:04', NULL);

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

--
-- Dumping data for table `playlists`
--

INSERT INTO `playlists` (`id`, `match_id`, `title`, `notes`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 'Croners', NULL, '2026-01-05 17:16:05', '2026-01-10 11:59:35', '2026-01-10 11:59:35'),
(2, 1, 'Goals', NULL, '2026-01-05 17:17:11', '2026-01-10 11:56:33', '2026-01-10 11:56:33'),
(3, 1, 'Test', NULL, '2026-01-10 11:57:26', '2026-01-10 11:59:31', '2026-01-10 11:59:31'),
(4, 1, 'Goal', NULL, '2026-01-10 15:25:18', NULL, NULL),
(5, 3, 'Goals', NULL, '2026-01-17 20:59:19', NULL, NULL),
(6, 3, 'Corners', NULL, '2026-01-18 01:55:48', NULL, NULL),
(7, 3, 'Funny', NULL, '2026-01-18 01:56:12', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `playlist_clips`
--

CREATE TABLE `playlist_clips` (
  `playlist_id` bigint(20) UNSIGNED NOT NULL,
  `clip_id` bigint(20) UNSIGNED NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `playlist_clips`
--

INSERT INTO `playlist_clips` (`playlist_id`, `clip_id`, `sort_order`) VALUES
(4, 57, 1),
(5, 63, 3),
(5, 64, 2),
(5, 65, 1),
(5, 66, 0),
(7, 67, 0);

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `role_key` varchar(40) NOT NULL,
  `label` varchar(80) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `role_key`, `label`) VALUES
(1, 'platform_admin', 'Platform Admin'),
(2, 'club_admin', 'Club Admin'),
(3, 'analyst', 'Analyst'),
(4, 'viewer', 'Viewer');

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

--
-- Dumping data for table `seasons`
--

INSERT INTO `seasons` (`id`, `club_id`, `name`, `start_date`, `end_date`, `created_at`) VALUES
(1, 1, '2025 / 2026', NULL, NULL, '2025-12-23 10:57:30');

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

--
-- Dumping data for table `teams`
--

INSERT INTO `teams` (`id`, `club_id`, `name`, `team_type`, `created_at`, `updated_at`) VALUES
(1, 1, 'Winton', 'club', '2025-12-19 12:08:29', NULL),
(2, 1, 'Saltcoats', 'club', '2025-12-19 12:08:47', NULL),
(3, 1, 'Rossvale', 'club', '2025-12-21 14:12:12', '2026-01-19 17:18:42'),
(4, 1, 'Campbeltown Pupils', 'opponent', '2026-01-17 18:35:09', '2026-01-19 17:18:42'),
(6, 1, 'Wishaw', 'opponent', '2026-01-19 12:59:33', '2026-01-19 17:18:42'),
(7, 1, 'Vale of Leven', 'opponent', '2026-01-19 12:59:33', '2026-01-19 17:18:42'),
(8, 1, 'East Kilbride YM', 'opponent', '2026-01-19 12:59:33', '2026-01-19 17:18:42'),
(9, 1, 'Easthouses Lily', 'opponent', '2026-01-19 12:59:33', '2026-01-19 17:18:42'),
(10, 1, 'Newmains United', 'opponent', '2026-01-19 12:59:33', '2026-01-19 17:18:42'),
(11, 1, 'Giffnock', 'opponent', '2026-01-19 12:59:33', '2026-01-19 17:18:42'),
(12, 1, 'West Park United', 'opponent', '2026-01-19 12:59:33', '2026-01-19 17:18:42'),
(13, 1, 'Coupar Angus', 'opponent', '2026-01-19 12:59:33', '2026-01-19 17:18:42'),
(14, 1, 'St. Peter\'s', 'opponent', '2026-01-19 12:59:33', '2026-01-19 17:18:42'),
(15, 1, 'Neilston', 'opponent', '2026-01-19 12:59:33', '2026-01-19 17:18:42'),
(16, 1, 'Eglinton', 'opponent', '2026-01-19 12:59:33', '2026-01-19 17:18:42'),
(17, 1, 'Dyce', 'opponent', '2026-01-19 12:59:33', '2026-01-19 17:18:42'),
(18, 1, 'Irvine Victoria', 'opponent', '2026-01-19 12:59:33', '2026-01-19 17:18:42'),
(19, 1, 'Carluke Rovers', 'opponent', '2026-01-19 12:59:33', '2026-01-19 17:18:42'),
(20, 1, 'Royal Albert', 'opponent', '2026-01-19 12:59:33', '2026-01-19 17:18:42'),
(21, 1, 'East Kilbride Thistle', 'opponent', '2026-01-19 12:59:33', '2026-01-19 17:18:42');

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

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `club_id`, `email`, `password_hash`, `display_name`, `is_active`, `created_at`, `updated_at`) VALUES
(1, NULL, 'colin@lundy.me.uk', '$2y$10$OPVaYgj3JeOdQ/9CkX2Mm.9y.UzGxSUcrIevUO3EaM/R6zTZYkToS', 'Platform Admin', 1, '2025-12-18 07:29:17', NULL),
(2, NULL, 'test@lundy.me.uk', '$2y$12$0YrsWog7DHPXekBJWML6W.O5SPTLZ63zK/RfJlA0gHDqo5eQjFloW', 'Test Admin', 1, '2025-12-29 14:03:30', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_roles`
--

INSERT INTO `user_roles` (`user_id`, `role_id`) VALUES
(1, 1),
(2, 1);

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
  ADD KEY `idx_players_active` (`is_active`);

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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=465;

--
-- AUTO_INCREMENT for table `clips`
--
ALTER TABLE `clips`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;

--
-- AUTO_INCREMENT for table `clip_jobs`
--
ALTER TABLE `clip_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=127;

--
-- AUTO_INCREMENT for table `clip_reviews`
--
ALTER TABLE `clip_reviews`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT for table `clubs`
--
ALTER TABLE `clubs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `competitions`
--
ALTER TABLE `competitions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `derived_stats`
--
ALTER TABLE `derived_stats`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=402;

--
-- AUTO_INCREMENT for table `event_snapshots`
--
ALTER TABLE `event_snapshots`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- AUTO_INCREMENT for table `event_suggestions`
--
ALTER TABLE `event_suggestions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `event_types`
--
ALTER TABLE `event_types`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `formations`
--
ALTER TABLE `formations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `formation_positions`
--
ALTER TABLE `formation_positions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=123;

--
-- AUTO_INCREMENT for table `keyboard_profiles`
--
ALTER TABLE `keyboard_profiles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `matches`
--
ALTER TABLE `matches`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `match_formations`
--
ALTER TABLE `match_formations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `match_periods`
--
ALTER TABLE `match_periods`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `match_players`
--
ALTER TABLE `match_players`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT for table `match_substitutions`
--
ALTER TABLE `match_substitutions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `match_videos`
--
ALTER TABLE `match_videos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `players`
--
ALTER TABLE `players`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `player_team_season`
--
ALTER TABLE `player_team_season`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `playlists`
--
ALTER TABLE `playlists`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `seasons`
--
ALTER TABLE `seasons`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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

--
-- Constraints for table `clip_jobs`
--
ALTER TABLE `clip_jobs`
  ADD CONSTRAINT `fk_clip_jobs_clip` FOREIGN KEY (`clip_id`) REFERENCES `clips` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_clip_jobs_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_clip_jobs_match` FOREIGN KEY (`match_id`) REFERENCES `matches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `clip_reviews`
--
ALTER TABLE `clip_reviews`
  ADD CONSTRAINT `fk_review_clip` FOREIGN KEY (`clip_id`) REFERENCES `clips` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_review_user` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `competitions`
--
ALTER TABLE `competitions`
  ADD CONSTRAINT `fk_comp_club` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_comp_season` FOREIGN KEY (`season_id`) REFERENCES `seasons` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `derived_stats`
--
ALTER TABLE `derived_stats`
  ADD CONSTRAINT `fk_stats_match` FOREIGN KEY (`match_id`) REFERENCES `matches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `fk_events_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_events_match` FOREIGN KEY (`match_id`) REFERENCES `matches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_events_match_period` FOREIGN KEY (`match_period_id`) REFERENCES `match_periods` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_events_match_player` FOREIGN KEY (`match_player_id`) REFERENCES `match_players` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_events_period` FOREIGN KEY (`period_id`) REFERENCES `match_periods` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_events_player` FOREIGN KEY (`player_id`) REFERENCES `players` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_events_type` FOREIGN KEY (`event_type_id`) REFERENCES `event_types` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_events_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `event_snapshots`
--
ALTER TABLE `event_snapshots`
  ADD CONSTRAINT `fk_snapshot_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_snapshot_match` FOREIGN KEY (`match_id`) REFERENCES `matches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `event_suggestions`
--
ALTER TABLE `event_suggestions`
  ADD CONSTRAINT `fk_suggestion_event_type` FOREIGN KEY (`suggested_event_type_id`) REFERENCES `event_types` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_suggestion_match` FOREIGN KEY (`match_id`) REFERENCES `matches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_suggestion_user` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `event_tags`
--
ALTER TABLE `event_tags`
  ADD CONSTRAINT `fk_event_tags_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_event_tags_tag` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `event_types`
--
ALTER TABLE `event_types`
  ADD CONSTRAINT `fk_event_types_club` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `formation_positions`
--
ALTER TABLE `formation_positions`
  ADD CONSTRAINT `fk_fp_formation` FOREIGN KEY (`formation_id`) REFERENCES `formations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `keyboard_profiles`
--
ALTER TABLE `keyboard_profiles`
  ADD CONSTRAINT `fk_kb_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `matches`
--
ALTER TABLE `matches`
  ADD CONSTRAINT `fk_matches_away_team` FOREIGN KEY (`away_team_id`) REFERENCES `teams` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_matches_club` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_matches_comp` FOREIGN KEY (`competition_id`) REFERENCES `competitions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_matches_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_matches_home_team` FOREIGN KEY (`home_team_id`) REFERENCES `teams` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_matches_season` FOREIGN KEY (`season_id`) REFERENCES `seasons` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `match_locks`
--
ALTER TABLE `match_locks`
  ADD CONSTRAINT `fk_lock_match` FOREIGN KEY (`match_id`) REFERENCES `matches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_lock_user` FOREIGN KEY (`locked_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `match_periods`
--
ALTER TABLE `match_periods`
  ADD CONSTRAINT `fk_match_periods_match` FOREIGN KEY (`match_id`) REFERENCES `matches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `match_players`
--
ALTER TABLE `match_players`
  ADD CONSTRAINT `fk_mp_match` FOREIGN KEY (`match_id`) REFERENCES `matches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mp_player` FOREIGN KEY (`player_id`) REFERENCES `players` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `match_substitutions`
--
ALTER TABLE `match_substitutions`
  ADD CONSTRAINT `fk_subs_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_subs_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_subs_match` FOREIGN KEY (`match_id`) REFERENCES `matches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_subs_period` FOREIGN KEY (`match_period_id`) REFERENCES `match_periods` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_subs_player_off` FOREIGN KEY (`player_off_match_player_id`) REFERENCES `match_players` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_subs_player_on` FOREIGN KEY (`player_on_match_player_id`) REFERENCES `match_players` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE;

--
-- Constraints for table `match_videos`
--
ALTER TABLE `match_videos`
  ADD CONSTRAINT `fk_match_video_match` FOREIGN KEY (`match_id`) REFERENCES `matches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `players`
--
ALTER TABLE `players`
  ADD CONSTRAINT `fk_players_club` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_players_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `player_team_season`
--
ALTER TABLE `player_team_season`
  ADD CONSTRAINT `fk_pts_club` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pts_player` FOREIGN KEY (`player_id`) REFERENCES `players` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pts_season` FOREIGN KEY (`season_id`) REFERENCES `seasons` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pts_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `playlists`
--
ALTER TABLE `playlists`
  ADD CONSTRAINT `fk_playlists_match` FOREIGN KEY (`match_id`) REFERENCES `matches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `playlist_clips`
--
ALTER TABLE `playlist_clips`
  ADD CONSTRAINT `fk_playlist_clips_clip` FOREIGN KEY (`clip_id`) REFERENCES `clips` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_playlist_clips_playlist` FOREIGN KEY (`playlist_id`) REFERENCES `playlists` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `seasons`
--
ALTER TABLE `seasons`
  ADD CONSTRAINT `fk_seasons_club` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `stat_overrides`
--
ALTER TABLE `stat_overrides`
  ADD CONSTRAINT `fk_override_match` FOREIGN KEY (`match_id`) REFERENCES `matches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_override_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `tags`
--
ALTER TABLE `tags`
  ADD CONSTRAINT `fk_tags_club` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `teams`
--
ALTER TABLE `teams`
  ADD CONSTRAINT `fk_teams_club` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_club` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD CONSTRAINT `fk_user_roles_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_user_roles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
