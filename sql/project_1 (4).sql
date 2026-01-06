-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jan 04, 2026 at 07:28 PM
-- Server version: 10.11.13-MariaDB-0ubuntu0.24.04.1
-- PHP Version: 8.4.14

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
(66, 1, 1, 'event', 203, 'create', NULL, '{\"id\":203,\"match_id\":1,\"period_id\":3,\"match_second\":2821,\"minute\":47,\"minute_extra\":0,\"team_side\":\"unknown\",\"event_type_id\":14,\"importance\":1,\"phase\":\"unknown\",\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":\"First Half\",\"created_by\":1,\"created_at\":\"2026-01-02 09:51:48\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Period End\",\"event_type_key\":\"period_end\",\"match_player_name\":null,\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"tags\":[]}', '2026-01-02 09:51:48');

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
(11, 1, 188, 0, 'Auto clip – Goal @ 1039s', 1009, 1069, 60, 1, '2025-12-28 15:11:09', NULL, NULL, 'event_auto', 3, 1, NULL),
(12, 1, 189, 0, 'Auto clip – Shot @ 1039s', 1009, 1069, 60, 1, '2025-12-28 15:11:09', NULL, NULL, 'event_auto', 3, 1, NULL),
(13, 1, 200, 0, 'Auto clip – Chance @ 1039s', 1009, 1069, 60, 1, '2025-12-28 15:11:09', NULL, NULL, 'event_auto', 3, 1, NULL),
(14, 1, 190, 0, 'Auto clip – Foul @ 1492s', 1462, 1522, 60, 1, '2025-12-28 15:11:09', NULL, NULL, 'event_auto', 3, 1, NULL),
(15, 1, 199, 0, 'Auto clip – Shot @ 3085s', 3055, 3115, 60, 1, '2025-12-28 15:11:09', NULL, NULL, 'event_auto', 3, 1, NULL);

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
(1, 1, 188, NULL, 'completed', '{\"clip_id\": 1, \"match_id\": 1, \"event_id\": 188, \"start_second\": 1024, \"end_second\": 1049, \"source_video\": \"/videos/matches/match_1/source/veo\", \"source_path\": \"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2025-12-27 22:36:52', '2025-12-28 05:17:05'),
(2, 1, 189, NULL, 'completed', '{\"clip_id\": 2, \"match_id\": 1, \"event_id\": 189, \"start_second\": 1031, \"end_second\": 1045, \"source_video\": \"/videos/matches/match_1/source/veo\", \"source_path\": \"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2025-12-27 22:36:52', '2025-12-28 05:17:05'),
(3, 1, 200, NULL, 'completed', '{\"clip_id\": 3, \"match_id\": 1, \"event_id\": 200, \"start_second\": 1031, \"end_second\": 1045, \"source_video\": \"/videos/matches/match_1/source/veo\", \"source_path\": \"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2025-12-27 22:36:52', '2025-12-28 05:17:05'),
(4, 1, 190, NULL, 'completed', '{\"clip_id\": 4, \"match_id\": 1, \"event_id\": 190, \"start_second\": 1486, \"end_second\": 1496, \"source_video\": \"/videos/matches/match_1/source/veo\", \"source_path\": \"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2025-12-27 22:36:52', '2025-12-28 05:17:05'),
(5, 1, 199, NULL, 'completed', '{\"clip_id\": 5, \"match_id\": 1, \"event_id\": 199, \"start_second\": 3077, \"end_second\": 3091, \"source_video\": \"/videos/matches/match_1/source/veo\", \"source_path\": \"videos/matches/match_1/source/veo/standard/match_1_standard.mp4\"}', NULL, NULL, '2025-12-27 22:36:52', '2025-12-28 05:17:05');

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

--
-- Dumping data for table `clubs`
--

INSERT INTO `clubs` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'Saltcoats Victoria F.C.', '2025-12-18 13:10:26', NULL),
(2, 'Test', '2025-12-18 14:33:11', NULL);

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
(1, 1, NULL, 'West of Scotland Div 4', '2025-12-23 10:56:43', NULL);

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
(14, 1, 0, '2025-12-25 14:22:49', '{\"computed_at\":\"2025-12-25 14:22:49\",\"events_version_used\":0,\"by_type_team\":{\"goal\":{\"home\":0,\"away\":0,\"unknown\":0},\"shot\":{\"home\":0,\"away\":0,\"unknown\":0},\"chance\":{\"home\":0,\"away\":0,\"unknown\":0},\"corner\":{\"home\":0,\"away\":0,\"unknown\":0},\"free_kick\":{\"home\":0,\"away\":0,\"unknown\":0},\"penalty\":{\"home\":0,\"away\":0,\"unknown\":0},\"foul\":{\"home\":0,\"away\":0,\"unknown\":0},\"yellow_card\":{\"home\":0,\"away\":0,\"unknown\":0},\"red_card\":{\"home\":0,\"away\":0,\"unknown\":0},\"mistake\":{\"home\":0,\"away\":0,\"unknown\":0},\"good_play\":{\"home\":0,\"away\":0,\"unknown\":0},\"highlight\":{\"home\":0,\"away\":0,\"unknown\":0}},\"totals\":{\"set_pieces\":{\"home\":0,\"away\":0,\"unknown\":0},\"cards\":{\"home\":0,\"away\":0,\"unknown\":0},\"highlights\":{\"total\":0,\"by_team\":{\"home\":0,\"away\":0,\"unknown\":0}}}}'),
(15, 1, 32, '2025-12-26 17:08:03', '{\"computed_at\":\"2025-12-26 17:08:03\",\"events_version_used\":32,\"by_type_team\":{\"goal\":{\"home\":1,\"away\":0,\"unknown\":0},\"shot\":{\"home\":2,\"away\":1,\"unknown\":0},\"chance\":{\"home\":0,\"away\":1,\"unknown\":0},\"corner\":{\"home\":0,\"away\":0,\"unknown\":0},\"free_kick\":{\"home\":0,\"away\":0,\"unknown\":0},\"penalty\":{\"home\":0,\"away\":0,\"unknown\":0},\"foul\":{\"home\":3,\"away\":0,\"unknown\":0},\"yellow_card\":{\"home\":3,\"away\":0,\"unknown\":0},\"red_card\":{\"home\":0,\"away\":0,\"unknown\":0},\"mistake\":{\"home\":0,\"away\":0,\"unknown\":0},\"good_play\":{\"home\":0,\"away\":0,\"unknown\":0},\"highlight\":{\"home\":0,\"away\":0,\"unknown\":0}},\"totals\":{\"set_pieces\":{\"home\":0,\"away\":0,\"unknown\":0},\"cards\":{\"home\":3,\"away\":0,\"unknown\":0},\"highlights\":{\"total\":0,\"by_team\":{\"home\":0,\"away\":0,\"unknown\":0}}}}'),
(16, 1, 34, '2025-12-26 18:44:33', '{\n    \"computed_at\": \"2025-12-26 18:44:33\",\n    \"events_version_used\": 34,\n    \"by_type_team\": {\n        \"goal\": {\n            \"home\": 0,\n            \"away\": 1,\n            \"unknown\": 0\n        },\n        \"shot\": {\n            \"home\": 1,\n            \"away\": 2,\n            \"unknown\": 0\n        },\n        \"chance\": {\n            \"home\": 0,\n            \"away\": 1,\n            \"unknown\": 0\n        },\n        \"corner\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"free_kick\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"penalty\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"foul\": {\n            \"home\": 3,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"yellow_card\": {\n            \"home\": 3,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"red_card\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"mistake\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"good_play\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"highlight\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        }\n    },\n    \"phase_2\": {\n        \"by_period\": {\n            \"1H\": {\n                \"home\": 7,\n                \"away\": 3,\n                \"unknown\": 0\n            },\n            \"2H\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            },\n            \"ET\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            },\n            \"other\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            }\n        },\n        \"per_15_minute\": [\n            {\n                \"label\": \"15-30\",\n                \"home\": 5,\n                \"away\": 2,\n                \"unknown\": 0\n            },\n            {\n                \"label\": \"45-60\",\n                \"home\": 0,\n                \"away\": 1,\n                \"unknown\": 0\n            },\n            {\n                \"label\": \"60-75\",\n                \"home\": 1,\n                \"away\": 0,\n                \"unknown\": 0\n            },\n            {\n                \"label\": \"75-90\",\n                \"home\": 1,\n                \"away\": 0,\n                \"unknown\": 0\n            }\n        ],\n        \"importance_weighted\": {\n            \"by_team\": {\n                \"home\": 18,\n                \"away\": 10,\n                \"unknown\": 0\n            },\n            \"by_type_team\": {\n                \"goal\": {\n                    \"home\": 0,\n                    \"away\": 5,\n                    \"unknown\": 0\n                },\n                \"shot\": {\n                    \"home\": 3,\n                    \"away\": 8,\n                    \"unknown\": 0\n                },\n                \"chance\": {\n                    \"home\": 0,\n                    \"away\": 2,\n                    \"unknown\": 0\n                },\n                \"corner\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"free_kick\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"penalty\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"foul\": {\n                    \"home\": 9,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"yellow_card\": {\n                    \"home\": 6,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"red_card\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"mistake\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"good_play\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"highlight\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                }\n            }\n        }\n    },\n    \"totals\": {\n        \"set_pieces\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"cards\": {\n            \"home\": 3,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"highlights\": {\n            \"total\": 0,\n            \"by_team\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            }\n        }\n    }\n}'),
(17, 1, 36, '2025-12-27 11:22:13', '{\n    \"computed_at\": \"2025-12-27 11:22:13\",\n    \"events_version_used\": 36,\n    \"by_type_team\": {\n        \"goal\": {\n            \"home\": 0,\n            \"away\": 1,\n            \"unknown\": 0\n        },\n        \"shot\": {\n            \"home\": 1,\n            \"away\": 2,\n            \"unknown\": 0\n        },\n        \"chance\": {\n            \"home\": 0,\n            \"away\": 1,\n            \"unknown\": 0\n        },\n        \"corner\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"free_kick\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"penalty\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"foul\": {\n            \"home\": 3,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"yellow_card\": {\n            \"home\": 3,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"red_card\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"mistake\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"good_play\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"highlight\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        }\n    },\n    \"phase_2\": {\n        \"by_period\": {\n            \"1H\": {\n                \"home\": 7,\n                \"away\": 3,\n                \"unknown\": 0\n            },\n            \"2H\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            },\n            \"ET\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            },\n            \"other\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            }\n        },\n        \"per_15_minute\": [\n            {\n                \"label\": \"15-30\",\n                \"home\": 5,\n                \"away\": 2,\n                \"unknown\": 0\n            },\n            {\n                \"label\": \"45-60\",\n                \"home\": 0,\n                \"away\": 1,\n                \"unknown\": 0\n            },\n            {\n                \"label\": \"60-75\",\n                \"home\": 1,\n                \"away\": 0,\n                \"unknown\": 0\n            },\n            {\n                \"label\": \"75-90\",\n                \"home\": 1,\n                \"away\": 0,\n                \"unknown\": 0\n            }\n        ],\n        \"importance_weighted\": {\n            \"by_team\": {\n                \"home\": 18,\n                \"away\": 10,\n                \"unknown\": 0\n            },\n            \"by_type_team\": {\n                \"goal\": {\n                    \"home\": 0,\n                    \"away\": 5,\n                    \"unknown\": 0\n                },\n                \"shot\": {\n                    \"home\": 3,\n                    \"away\": 8,\n                    \"unknown\": 0\n                },\n                \"chance\": {\n                    \"home\": 0,\n                    \"away\": 2,\n                    \"unknown\": 0\n                },\n                \"corner\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"free_kick\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"penalty\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"foul\": {\n                    \"home\": 9,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"yellow_card\": {\n                    \"home\": 6,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"red_card\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"mistake\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"good_play\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"highlight\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                }\n            }\n        }\n    },\n    \"totals\": {\n        \"set_pieces\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"cards\": {\n            \"home\": 3,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"highlights\": {\n            \"total\": 0,\n            \"by_team\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            }\n        }\n    }\n}'),
(18, 1, 39, '2025-12-27 18:34:34', '{\n    \"computed_at\": \"2025-12-27 18:34:34\",\n    \"events_version_used\": 39,\n    \"by_type_team\": {\n        \"goal\": {\n            \"home\": 0,\n            \"away\": 1,\n            \"unknown\": 0\n        },\n        \"shot\": {\n            \"home\": 2,\n            \"away\": 2,\n            \"unknown\": 0\n        },\n        \"chance\": {\n            \"home\": 0,\n            \"away\": 1,\n            \"unknown\": 0\n        },\n        \"corner\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"free_kick\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"penalty\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"foul\": {\n            \"home\": 3,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"yellow_card\": {\n            \"home\": 3,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"red_card\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"mistake\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"good_play\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"highlight\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        }\n    },\n    \"phase_2\": {\n        \"by_period\": {\n            \"1H\": {\n                \"home\": 8,\n                \"away\": 3,\n                \"unknown\": 0\n            },\n            \"2H\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            },\n            \"ET\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            },\n            \"other\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            }\n        },\n        \"per_15_minute\": [\n            {\n                \"label\": \"15-30\",\n                \"home\": 6,\n                \"away\": 2,\n                \"unknown\": 0\n            },\n            {\n                \"label\": \"45-60\",\n                \"home\": 0,\n                \"away\": 1,\n                \"unknown\": 0\n            },\n            {\n                \"label\": \"60-75\",\n                \"home\": 1,\n                \"away\": 0,\n                \"unknown\": 0\n            },\n            {\n                \"label\": \"75-90\",\n                \"home\": 1,\n                \"away\": 0,\n                \"unknown\": 0\n            }\n        ],\n        \"importance_weighted\": {\n            \"by_team\": {\n                \"home\": 21,\n                \"away\": 10,\n                \"unknown\": 0\n            },\n            \"by_type_team\": {\n                \"goal\": {\n                    \"home\": 0,\n                    \"away\": 5,\n                    \"unknown\": 0\n                },\n                \"shot\": {\n                    \"home\": 6,\n                    \"away\": 8,\n                    \"unknown\": 0\n                },\n                \"chance\": {\n                    \"home\": 0,\n                    \"away\": 2,\n                    \"unknown\": 0\n                },\n                \"corner\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"free_kick\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"penalty\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"foul\": {\n                    \"home\": 9,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"yellow_card\": {\n                    \"home\": 6,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"red_card\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"mistake\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"good_play\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"highlight\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                }\n            }\n        }\n    },\n    \"totals\": {\n        \"set_pieces\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"cards\": {\n            \"home\": 3,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"highlights\": {\n            \"total\": 0,\n            \"by_team\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            }\n        }\n    }\n}'),
(19, 1, 41, '2026-01-04 10:45:59', '{\n    \"computed_at\": \"2026-01-04 10:45:59\",\n    \"events_version_used\": 41,\n    \"by_type_team\": {\n        \"goal\": {\n            \"home\": 0,\n            \"away\": 1,\n            \"unknown\": 0\n        },\n        \"shot\": {\n            \"home\": 2,\n            \"away\": 2,\n            \"unknown\": 0\n        },\n        \"chance\": {\n            \"home\": 0,\n            \"away\": 1,\n            \"unknown\": 0\n        },\n        \"corner\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"free_kick\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"penalty\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"foul\": {\n            \"home\": 3,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"yellow_card\": {\n            \"home\": 3,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"red_card\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"mistake\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"good_play\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"highlight\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        }\n    },\n    \"phase_2\": {\n        \"by_period\": {\n            \"1H\": {\n                \"home\": 8,\n                \"away\": 3,\n                \"unknown\": 2\n            },\n            \"2H\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            },\n            \"ET\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            },\n            \"other\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            }\n        },\n        \"per_15_minute\": [\n            {\n                \"label\": \"0-15\",\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 1\n            },\n            {\n                \"label\": \"15-30\",\n                \"home\": 6,\n                \"away\": 2,\n                \"unknown\": 0\n            },\n            {\n                \"label\": \"45-60\",\n                \"home\": 0,\n                \"away\": 1,\n                \"unknown\": 1\n            },\n            {\n                \"label\": \"60-75\",\n                \"home\": 1,\n                \"away\": 0,\n                \"unknown\": 0\n            },\n            {\n                \"label\": \"75-90\",\n                \"home\": 1,\n                \"away\": 0,\n                \"unknown\": 0\n            }\n        ],\n        \"importance_weighted\": {\n            \"by_team\": {\n                \"home\": 21,\n                \"away\": 10,\n                \"unknown\": 2\n            },\n            \"by_type_team\": {\n                \"goal\": {\n                    \"home\": 0,\n                    \"away\": 5,\n                    \"unknown\": 0\n                },\n                \"shot\": {\n                    \"home\": 6,\n                    \"away\": 8,\n                    \"unknown\": 0\n                },\n                \"chance\": {\n                    \"home\": 0,\n                    \"away\": 2,\n                    \"unknown\": 0\n                },\n                \"corner\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"free_kick\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"penalty\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"foul\": {\n                    \"home\": 9,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"yellow_card\": {\n                    \"home\": 6,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"red_card\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"mistake\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"good_play\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"highlight\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                }\n            }\n        }\n    },\n    \"totals\": {\n        \"set_pieces\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"cards\": {\n            \"home\": 3,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"highlights\": {\n            \"total\": 0,\n            \"by_team\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            }\n        }\n    }\n}');

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
(188, 1, 3, 1039, 17, 0, 'away', 16, 5, 'unknown', 3, NULL, NULL, NULL, NULL, NULL, 1, '2025-12-26 16:27:27', NULL, '2025-12-27 22:34:36', NULL, 1, 1024, 1049),
(189, 1, 3, 1039, 17, 0, 'home', 15, 3, 'unknown', NULL, NULL, NULL, 'on_target', NULL, NULL, 1, '2025-12-26 16:27:28', NULL, '2025-12-27 22:34:36', NULL, 2, 1031, 1045),
(190, 1, 3, 1492, 24, 0, 'home', 17, 3, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-12-26 16:30:02', NULL, '2025-12-27 22:34:36', NULL, 4, 1486, 1496),
(191, 1, 3, 1492, 24, 0, 'home', 17, 3, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-12-26 16:40:19', NULL, NULL, NULL, NULL, NULL, NULL),
(192, 1, 3, 1492, 24, 0, 'home', 8, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-12-26 16:40:21', NULL, NULL, NULL, NULL, NULL, NULL),
(193, 1, 3, 1492, 24, 0, 'home', 8, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-12-26 16:40:21', NULL, NULL, NULL, NULL, NULL, NULL),
(195, 1, 3, 4326, 72, 0, 'home', 17, 3, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-12-26 16:47:07', NULL, NULL, NULL, NULL, NULL, NULL),
(198, 1, 3, 4878, 81, 0, 'home', 8, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-12-26 16:49:40', NULL, NULL, NULL, NULL, NULL, NULL),
(199, 1, 3, 3085, 51, 0, 'away', 15, 3, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-12-26 16:50:17', NULL, '2025-12-27 22:34:36', NULL, 5, 3077, 3091),
(200, 1, 3, 1039, 17, 0, 'away', 3, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-12-26 16:54:00', NULL, '2025-12-27 22:34:36', NULL, 3, 1031, 1045),
(201, 1, 3, 1181, 17, 0, 'home', 15, 3, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-12-27 13:22:32', NULL, '2025-12-27 13:22:53', NULL, NULL, NULL, NULL),
(202, 1, 3, 7, 0, 0, 'unknown', 13, 1, 'unknown', 3, NULL, NULL, NULL, NULL, 'First Half', 1, '2026-01-02 09:49:29', NULL, NULL, NULL, NULL, NULL, NULL),
(203, 1, 3, 2821, 47, 0, 'unknown', 14, 1, 'unknown', NULL, NULL, NULL, NULL, NULL, 'First Half', 1, '2026-01-02 09:51:48', NULL, NULL, NULL, NULL, NULL, NULL);

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
(6, 188, 1, '{\"event_id\":188,\"id\":188,\"match_id\":1,\"match_second\":1039,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"clip_id\":6,\"clip_name\":\"Auto clip \\u2013 Goal @ 1039s\",\"generation_source\":\"event_auto\",\"generation_version\":2,\"clip_start_second\":1009,\"clip_end_second\":1069,\"clip_review_status\":\"pending\"}', '2025-12-28 15:11:09'),
(7, 189, 1, '{\"event_id\":189,\"id\":189,\"match_id\":1,\"match_second\":1039,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"clip_id\":7,\"clip_name\":\"Auto clip \\u2013 Shot @ 1039s\",\"generation_source\":\"event_auto\",\"generation_version\":2,\"clip_start_second\":1009,\"clip_end_second\":1069,\"clip_review_status\":\"pending\"}', '2025-12-28 15:11:09'),
(8, 200, 1, '{\"event_id\":200,\"id\":200,\"match_id\":1,\"match_second\":1039,\"event_type_label\":\"Chance\",\"event_type_key\":\"chance\",\"clip_id\":8,\"clip_name\":\"Auto clip \\u2013 Chance @ 1039s\",\"generation_source\":\"event_auto\",\"generation_version\":2,\"clip_start_second\":1009,\"clip_end_second\":1069,\"clip_review_status\":\"pending\"}', '2025-12-28 15:11:09'),
(9, 190, 1, '{\"event_id\":190,\"id\":190,\"match_id\":1,\"match_second\":1492,\"event_type_label\":\"Foul\",\"event_type_key\":\"foul\",\"clip_id\":9,\"clip_name\":\"Auto clip \\u2013 Foul @ 1492s\",\"generation_source\":\"event_auto\",\"generation_version\":2,\"clip_start_second\":1462,\"clip_end_second\":1522,\"clip_review_status\":\"pending\"}', '2025-12-28 15:11:09'),
(10, 199, 1, '{\"event_id\":199,\"id\":199,\"match_id\":1,\"match_second\":3085,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"clip_id\":10,\"clip_name\":\"Auto clip \\u2013 Shot @ 3085s\",\"generation_source\":\"event_auto\",\"generation_version\":2,\"clip_start_second\":3055,\"clip_end_second\":3115,\"clip_review_status\":\"pending\"}', '2025-12-28 15:11:09');

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
(18, 1, 'turnover', 'Turnover', 2, '2025-12-20 21:03:35');

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
(1, 1, NULL, NULL, 2, 3, NULL, NULL, NULL, NULL, NULL, 'draft', NULL, 41, 10, 41, 1, '2025-12-25 14:17:47', '2026-01-04 10:45:59');

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
(1, 1, '2025-12-29 14:17:07', '2026-01-04 17:31:39');

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
(3, 1, 'first_half', 'First Half', 7, 2821, '2025-12-26 11:58:57', '2026-01-02 09:51:48');

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
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `match_players`
--

INSERT INTO `match_players` (`id`, `match_id`, `team_side`, `player_id`, `display_name`, `shirt_number`, `position_label`, `is_starting`, `created_at`) VALUES
(3, 1, 'away', 2, 'Player 1', NULL, 'Striker', 1, '2025-12-26 17:15:12'),
(4, 1, 'away', 1, 'Player 2', NULL, 'Striker', 1, '2025-12-26 17:15:16');

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
(1, 1, '/videos/matches/match_1/source/veo/standard/match_1_standard.mp4', 6686, '2025-12-25 14:17:54', 'veo', 'https://app.veo.co/matches/20251213-rossvale-1-4-saltcoats-8ae3733c/', 'completed', 100, NULL);

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
(1, 1, 2, 'Player', '2', 'Player 2', NULL, 'Striker', 1, '2025-12-23 11:06:43', '2025-12-24 10:54:15'),
(2, 1, 2, 'Player', '1', 'Player 1', NULL, 'Striker', 1, '2025-12-23 17:04:03', '2025-12-24 10:54:09');

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
(3, 1, 'Rossvale', 'club', '2025-12-21 14:12:12', NULL);

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
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `clips`
--
ALTER TABLE `clips`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `clip_jobs`
--
ALTER TABLE `clip_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `clip_reviews`
--
ALTER TABLE `clip_reviews`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `clubs`
--
ALTER TABLE `clubs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `competitions`
--
ALTER TABLE `competitions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `derived_stats`
--
ALTER TABLE `derived_stats`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=204;

--
-- AUTO_INCREMENT for table `event_snapshots`
--
ALTER TABLE `event_snapshots`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `event_suggestions`
--
ALTER TABLE `event_suggestions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `event_types`
--
ALTER TABLE `event_types`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `keyboard_profiles`
--
ALTER TABLE `keyboard_profiles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `matches`
--
ALTER TABLE `matches`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `match_periods`
--
ALTER TABLE `match_periods`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `match_players`
--
ALTER TABLE `match_players`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `match_videos`
--
ALTER TABLE `match_videos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `players`
--
ALTER TABLE `players`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `player_team_season`
--
ALTER TABLE `player_team_season`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

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
