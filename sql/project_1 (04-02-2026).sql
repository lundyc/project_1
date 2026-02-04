-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Feb 01, 2026 at 07:26 PM
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

--
-- Dumping data for table `clips`
--

INSERT INTO `clips` (`id`, `match_id`, `event_id`, `clip_id`, `clip_name`, `start_second`, `end_second`, `duration_seconds`, `created_by`, `created_at`, `updated_by`, `updated_at`, `generation_source`, `generation_version`, `is_valid`, `deleted_at`) VALUES
(63, 19, 322, 0, '', 6051, 6111, 0, 1, '2026-01-18 03:10:29', NULL, NULL, 'event_auto', 1, 1, NULL),
(64, 19, 312, 0, '', 5416, 5476, 0, 1, '2026-01-18 03:11:31', NULL, NULL, 'event_auto', 1, 1, NULL),
(65, 19, 289, 0, '', 2631, 2691, 0, 1, '2026-01-18 03:11:38', NULL, NULL, 'event_auto', 1, 1, NULL),
(66, 19, 282, 0, '', 2104, 2164, 0, 1, '2026-01-18 03:11:43', NULL, NULL, 'event_auto', 1, 1, NULL),
(67, 19, 302, 0, '', 4776, 4836, 0, 1, '2026-01-18 03:15:50', NULL, NULL, 'event_auto', 1, 1, NULL),
(68, 19, 267, 0, '', 794, 854, 0, 1, '2026-01-18 03:16:04', NULL, NULL, 'event_auto', 1, 1, NULL),
(69, 19, 275, 0, '', 1486, 1546, 0, 1, '2026-01-18 03:16:10', NULL, NULL, 'event_auto', 1, 1, NULL),
(70, 19, 304, 0, '', 4983, 5043, 0, 1, '2026-01-18 03:16:17', NULL, NULL, 'event_auto', 1, 1, NULL),
(71, 19, 304, 0, '', 4983, 5043, 0, 1, '2026-01-18 03:16:22', NULL, NULL, 'event_auto', 1, 1, NULL),
(72, 19, 317, 0, '', 5412, 5472, 0, 1, '2026-01-18 03:16:24', NULL, NULL, 'event_auto', 1, 1, NULL),
(73, 19, 316, 0, '', 5747, 5807, 0, 1, '2026-01-18 03:16:27', NULL, NULL, 'event_auto', 1, 1, NULL),
(74, 19, 269, 0, '', 969, 1029, 0, 1, '2026-01-18 03:16:31', NULL, NULL, 'event_auto', 1, 1, NULL),
(88, 22, 525, 0, 'Auto clip – Free Kick @ 247s', 217, 277, 60, 1, '2026-01-25 15:08:58', NULL, NULL, 'event_auto', 1, 1, NULL),
(89, 22, 526, 0, 'Auto clip – Shot @ 309s', 279, 339, 60, 1, '2026-01-25 15:10:12', NULL, NULL, 'event_auto', 1, 1, NULL),
(90, 22, 527, 0, 'Auto clip – Free Kick @ 386s', 356, 416, 60, 1, '2026-01-25 15:11:33', NULL, NULL, 'event_auto', 1, 1, NULL),
(91, 22, 528, 0, 'Auto clip – Shot @ 436s', 406, 466, 60, 1, '2026-01-25 15:12:05', NULL, NULL, 'event_auto', 1, 1, NULL),
(92, 22, 529, 0, 'Auto clip – Goal @ 544s', 514, 574, 60, 1, '2026-01-25 15:13:59', NULL, NULL, 'event_auto', 1, 1, NULL),
(93, 22, 530, 0, 'Auto clip – Free Kick @ 644s', 614, 674, 60, 1, '2026-01-25 15:15:28', NULL, NULL, 'event_auto', 1, 1, NULL),
(94, 22, 531, 0, 'Auto clip – Free Kick @ 725s', 695, 755, 60, 1, '2026-01-25 15:16:35', NULL, NULL, 'event_auto', 1, 1, NULL),
(95, 22, 532, 0, 'Auto clip – Free Kick @ 764s', 734, 794, 60, 1, '2026-01-25 15:17:04', NULL, NULL, 'event_auto', 1, 1, NULL),
(96, 22, 533, 0, 'Auto clip – Highlight @ 835s', 805, 865, 60, 1, '2026-01-25 15:18:16', NULL, NULL, 'event_auto', 1, 1, NULL),
(97, 22, 534, 0, 'Auto clip – Shot @ 901s', 871, 931, 60, 1, '2026-01-25 15:19:28', NULL, NULL, 'event_auto', 1, 1, NULL),
(98, 22, 535, 0, 'Auto clip – Shot @ 989s', 959, 1019, 60, 1, '2026-01-25 15:21:14', NULL, NULL, 'event_auto', 1, 1, NULL),
(99, 22, 536, 0, 'Auto clip – Corner @ 999s', 969, 1029, 60, 1, '2026-01-25 15:21:26', NULL, NULL, 'event_auto', 1, 1, NULL),
(100, 22, 537, 0, 'Auto clip – Free Kick @ 1101s', 1071, 1131, 60, 1, '2026-01-25 15:22:59', NULL, NULL, 'event_auto', 1, 1, NULL),
(101, 22, 538, 0, 'Auto clip – Free Kick @ 1153s', 1123, 1183, 60, 1, '2026-01-25 15:23:43', NULL, NULL, 'event_auto', 1, 1, NULL),
(102, 22, 539, 0, 'Auto clip – Free Kick @ 1266s', 1236, 1296, 60, 1, '2026-01-25 15:25:35', NULL, NULL, 'event_auto', 1, 1, NULL),
(103, 22, 540, 0, 'Auto clip – Yellow Card @ 1277s', 1247, 1307, 60, 1, '2026-01-25 15:25:51', NULL, NULL, 'event_auto', 1, 1, NULL),
(104, 22, 541, 0, 'Auto clip – Free Kick @ 1332s', 1302, 1362, 60, 1, '2026-01-25 15:26:37', NULL, NULL, 'event_auto', 1, 1, NULL),
(105, 22, 542, 0, 'Auto clip – Goal @ 1419s', 1389, 1449, 60, 1, '2026-01-25 15:28:10', NULL, NULL, 'event_auto', 1, 1, NULL),
(106, 22, 543, 0, 'Auto clip – Shot @ 1570s', 1540, 1600, 60, 1, '2026-01-25 15:30:15', NULL, NULL, 'event_auto', 1, 1, NULL),
(107, 22, 544, 0, 'Auto clip – Shot @ 1620s', 1590, 1650, 60, 1, '2026-01-25 15:30:40', NULL, NULL, 'event_auto', 1, 1, NULL),
(108, 22, 545, 0, 'Auto clip – Chance @ 1614s', 1584, 1644, 60, 1, '2026-01-25 15:30:49', NULL, NULL, 'event_auto', 1, 1, NULL),
(109, 22, 546, 0, 'Auto clip – Free Kick @ 1787s', 1757, 1817, 60, 1, '2026-01-25 15:33:13', NULL, NULL, 'event_auto', 1, 1, NULL),
(110, 22, 547, 0, 'Auto clip – Free Kick @ 2016s', 1986, 2046, 60, 1, '2026-01-25 15:36:39', NULL, NULL, 'event_auto', 1, 1, NULL),
(111, 22, 548, 0, 'Auto clip – Goal @ 2080s', 2050, 2110, 60, 1, '2026-01-25 15:37:18', NULL, NULL, 'event_auto', 1, 1, NULL),
(112, 22, 549, 0, 'Auto clip – Shot @ 2078s', 2048, 2108, 60, 1, '2026-01-25 15:37:29', NULL, NULL, 'event_auto', 1, 1, NULL),
(113, 22, 550, 0, 'Auto clip – Shot @ 2432s', 2402, 2462, 60, 1, '2026-01-25 15:41:25', NULL, NULL, 'event_auto', 1, 1, NULL),
(114, 22, 551, 0, 'Auto clip – Corner @ 2439s', 2409, 2469, 60, 1, '2026-01-25 15:41:32', NULL, NULL, 'event_auto', 1, 1, NULL),
(115, 22, 552, 0, 'Auto clip – Goal @ 2477s', 2447, 2507, 60, 1, '2026-01-25 15:42:00', NULL, NULL, 'event_auto', 1, 1, NULL),
(116, 22, 553, 0, 'Auto clip – Free Kick @ 2587s', 2557, 2617, 60, 1, '2026-01-25 15:43:16', NULL, NULL, 'event_auto', 1, 1, NULL),
(117, 22, 554, 0, 'Auto clip – Shot @ 2805s', 2775, 2835, 60, 1, '2026-01-25 15:46:09', NULL, NULL, 'event_auto', 1, 1, NULL),
(118, 22, 557, 0, 'Auto clip – Shot @ 3719s', 3689, 3749, 60, 1, '2026-01-25 15:47:20', NULL, NULL, 'event_auto', 1, 1, NULL),
(119, 22, 558, 0, 'Auto clip – Free Kick @ 3809s', 3779, 3839, 60, 1, '2026-01-25 15:48:31', NULL, NULL, 'event_auto', 1, 1, NULL),
(120, 22, 559, 0, 'Auto clip – Yellow Card @ 3820s', 3790, 3850, 60, 1, '2026-01-25 15:48:43', NULL, NULL, 'event_auto', 1, 1, NULL),
(121, 22, 560, 0, 'Auto clip – Free Kick @ 4139s', 4109, 4169, 60, 1, '2026-01-25 15:51:44', NULL, NULL, 'event_auto', 1, 1, NULL),
(122, 22, 561, 0, 'Auto clip – Shot @ 4237s', 4207, 4267, 60, 1, '2026-01-25 15:53:07', NULL, NULL, 'event_auto', 1, 1, NULL),
(123, 22, 562, 0, 'Auto clip – Corner @ 4240s', 4210, 4270, 60, 1, '2026-01-25 15:53:11', NULL, NULL, 'event_auto', 1, 1, NULL),
(124, 22, 563, 0, 'Auto clip – Free Kick @ 4367s', 4337, 4397, 60, 1, '2026-01-25 15:54:53', NULL, NULL, 'event_auto', 1, 1, NULL),
(125, 22, 564, 0, 'Auto clip – Yellow Card @ 4372s', 4342, 4402, 60, 1, '2026-01-25 15:55:00', NULL, NULL, 'event_auto', 1, 1, NULL),
(126, 22, 565, 0, 'Auto clip – Yellow Card @ 4388s', 4358, 4418, 60, 1, '2026-01-25 15:55:07', NULL, NULL, 'event_auto', 1, 1, NULL),
(127, 22, 566, 0, 'Auto clip – Free Kick @ 4495s', 4465, 4525, 60, 1, '2026-01-25 15:55:55', NULL, NULL, 'event_auto', 1, 1, NULL),
(128, 22, 567, 0, 'Auto clip – Yellow Card @ 4501s', 4471, 4531, 60, 1, '2026-01-25 15:56:05', NULL, NULL, 'event_auto', 1, 1, NULL),
(129, 22, 568, 0, 'Auto clip – Free Kick @ 4563s', 4533, 4593, 60, 1, '2026-01-25 15:56:22', NULL, NULL, 'event_auto', 1, 1, NULL),
(130, 22, 569, 0, 'Auto clip – Free Kick @ 4606s', 4576, 4636, 60, 1, '2026-01-25 15:56:56', NULL, NULL, 'event_auto', 1, 1, NULL),
(131, 22, 570, 0, 'Auto clip – Yellow Card @ 4620s', 4590, 4650, 60, 1, '2026-01-25 15:57:16', NULL, NULL, 'event_auto', 1, 1, NULL),
(132, 22, 571, 0, 'Auto clip – Shot @ 4801s', 4771, 4831, 60, 1, '2026-01-25 15:59:11', NULL, NULL, 'event_auto', 1, 1, NULL),
(133, 22, 572, 0, 'Auto clip – Shot @ 4893s', 4863, 4923, 60, 1, '2026-01-25 16:00:21', NULL, NULL, 'event_auto', 1, 1, NULL),
(134, 22, 573, 0, 'Auto clip – Shot @ 5040s', 5010, 5070, 60, 1, '2026-01-25 16:01:31', NULL, NULL, 'event_auto', 1, 1, NULL),
(135, 22, 574, 0, 'Auto clip – Shot @ 5113s', 5083, 5143, 60, 1, '2026-01-25 16:02:48', NULL, NULL, 'event_auto', 1, 1, NULL),
(136, 22, 575, 0, 'Auto clip – Corner @ 5128s', 5098, 5158, 60, 1, '2026-01-25 16:03:11', NULL, NULL, 'event_auto', 1, 1, NULL),
(137, 22, 576, 0, 'Auto clip – Free Kick @ 5183s', 5153, 5213, 60, 1, '2026-01-25 16:03:51', NULL, NULL, 'event_auto', 1, 1, NULL),
(138, 22, 577, 0, 'Auto clip – Corner @ 5247s', 5217, 5277, 60, 1, '2026-01-25 16:04:25', NULL, NULL, 'event_auto', 1, 1, NULL),
(139, 22, 578, 0, 'Auto clip – Free Kick @ 5431s', 5401, 5461, 60, 1, '2026-01-25 16:05:42', NULL, NULL, 'event_auto', 1, 1, NULL),
(140, 22, 579, 0, 'Auto clip – Free Kick @ 5524s', 5494, 5554, 60, 1, '2026-01-25 16:06:37', NULL, NULL, 'event_auto', 1, 1, NULL),
(141, 22, 580, 0, 'Auto clip – Shot @ 5560s', 5530, 5590, 60, 1, '2026-01-25 16:06:54', NULL, NULL, 'event_auto', 1, 1, NULL),
(142, 22, 581, 0, 'Auto clip – Highlight @ 5671s', 5641, 5701, 60, 1, '2026-01-25 16:07:32', NULL, NULL, 'event_auto', 1, 1, NULL),
(143, 22, 582, 0, 'Auto clip – Highlight @ 5686s', 5656, 5716, 60, 1, '2026-01-25 16:07:57', NULL, NULL, 'event_auto', 1, 1, NULL),
(144, 22, 583, 0, 'Auto clip – Shot @ 5689s', 5659, 5719, 60, 1, '2026-01-25 16:08:15', NULL, NULL, 'event_auto', 1, 1, NULL),
(145, 22, 584, 0, 'Auto clip – Free Kick @ 5860s', 5830, 5890, 60, 1, '2026-01-25 16:10:44', NULL, NULL, 'event_auto', 1, 1, NULL),
(146, 22, 585, 0, 'Auto clip – Free Kick @ 5887s', 5857, 5917, 60, 1, '2026-01-25 16:11:11', NULL, NULL, 'event_auto', 1, 1, NULL),
(147, 22, 586, 0, 'Auto clip – Free Kick @ 6179s', 6149, 6209, 60, 1, '2026-01-25 16:13:51', NULL, NULL, 'event_auto', 1, 1, NULL),
(148, 22, 587, 0, 'Auto clip – Free Kick @ 6263s', 6233, 6293, 60, 1, '2026-01-25 16:14:42', NULL, NULL, 'event_auto', 1, 1, NULL),
(149, 22, 588, 0, 'Auto clip – Shot @ 6343s', 6313, 6373, 60, 1, '2026-01-25 16:15:59', NULL, NULL, 'event_auto', 1, 1, NULL),
(150, 22, 598, 0, 'Auto clip – Shot @ 5703s', 5673, 5733, 60, 1, '2026-01-31 11:24:16', NULL, NULL, 'event_auto', 1, 1, NULL),
(151, 23, 600, 0, 'Auto clip – Shot @ 82s', 52, 112, 60, 1, '2026-02-01 16:19:48', NULL, NULL, 'event_auto', 1, 1, NULL),
(153, 23, 602, 0, 'Auto clip – Free Kick @ 171s', 141, 201, 60, 1, '2026-02-01 16:22:40', NULL, NULL, 'event_auto', 1, 1, NULL),
(154, 23, 603, 0, 'Auto clip – Corner @ 301s', 271, 331, 60, 1, '2026-02-01 16:25:59', NULL, NULL, 'event_auto', 1, 1, NULL),
(155, 23, 604, 0, 'Auto clip – Shot @ 320s', 290, 350, 60, 1, '2026-02-01 16:26:20', NULL, NULL, 'event_auto', 1, 1, NULL),
(156, 23, 605, 0, 'Auto clip – Shot @ 451s', 421, 481, 60, 1, '2026-02-01 16:29:05', NULL, NULL, 'event_auto', 1, 1, NULL),
(157, 23, 606, 0, 'Auto clip – Free Kick @ 652s', 622, 682, 60, 1, '2026-02-01 16:31:51', NULL, NULL, 'event_auto', 1, 1, NULL),
(158, 23, 607, 0, 'Auto clip – Free Kick @ 996s', 966, 1026, 60, 1, '2026-02-01 16:35:42', NULL, NULL, 'event_auto', 1, 1, NULL),
(159, 23, 608, 0, 'Auto clip – Shot @ 1196s', 1166, 1226, 60, 1, '2026-02-01 16:37:58', NULL, NULL, 'event_auto', 1, 1, NULL),
(160, 23, 609, 0, 'Auto clip – Shot @ 1326s', 1296, 1356, 60, 1, '2026-02-01 16:39:21', NULL, NULL, 'event_auto', 1, 1, NULL),
(161, 23, 610, 0, 'Auto clip – Corner @ 1327s', 1297, 1357, 60, 1, '2026-02-01 16:39:23', NULL, NULL, 'event_auto', 1, 1, NULL),
(162, 23, 611, 0, 'Auto clip – Shot @ 1363s', 1333, 1393, 60, 1, '2026-02-01 16:39:51', NULL, NULL, 'event_auto', 1, 1, NULL),
(163, 23, 612, 0, 'Auto clip – Corner @ 1366s', 1336, 1396, 60, 1, '2026-02-01 16:39:54', NULL, NULL, 'event_auto', 1, 1, NULL),
(164, 23, 613, 0, 'Auto clip – Shot @ 1431s', 1401, 1461, 60, 1, '2026-02-01 16:40:56', NULL, NULL, 'event_auto', 1, 1, NULL),
(165, 23, 614, 0, 'Auto clip – Corner @ 1577s', 1547, 1607, 60, 1, '2026-02-01 16:42:47', NULL, NULL, 'event_auto', 1, 1, NULL),
(166, 23, 615, 0, 'Auto clip – Goal @ 1610s', 1580, 1640, 60, 1, '2026-02-01 16:43:07', NULL, NULL, 'event_auto', 1, 1, NULL),
(167, 23, 616, 0, 'Auto clip – Free Kick @ 1772s', 1742, 1802, 60, 1, '2026-02-01 16:43:54', NULL, NULL, 'event_auto', 1, 1, NULL),
(168, 23, 617, 0, 'Auto clip – Corner @ 1948s', 1918, 1978, 60, 1, '2026-02-01 16:47:03', NULL, NULL, 'event_auto', 1, 1, NULL),
(169, 23, 618, 0, 'Auto clip – Corner @ 1976s', 1946, 2006, 60, 1, '2026-02-01 16:47:15', NULL, NULL, 'event_auto', 1, 1, NULL),
(170, 23, 619, 0, 'Auto clip – Shot @ 1988s', 1958, 2018, 60, 1, '2026-02-01 16:47:25', NULL, NULL, 'event_auto', 1, 1, NULL),
(171, 23, 620, 0, 'Auto clip – Goal @ 2052s', 2022, 2082, 60, 1, '2026-02-01 16:48:31', NULL, NULL, 'event_auto', 1, 1, NULL),
(172, 23, 621, 0, 'Auto clip – Highlight @ 2126s', 2096, 2156, 60, 1, '2026-02-01 16:49:34', NULL, NULL, 'event_auto', 1, 1, NULL),
(173, 23, 622, 0, 'Auto clip – Corner @ 2220s', 2190, 2250, 60, 1, '2026-02-01 16:51:02', NULL, NULL, 'event_auto', 1, 1, NULL),
(174, 23, 623, 0, 'Auto clip – Shot @ 2388s', 2358, 2418, 60, 1, '2026-02-01 16:52:13', NULL, NULL, 'event_auto', 1, 1, NULL),
(175, 23, 624, 0, 'Auto clip – Goal @ 2595s', 2565, 2625, 60, 1, '2026-02-01 16:54:19', NULL, NULL, 'event_auto', 1, 1, NULL),
(176, 23, 625, 0, 'Auto clip – Highlight @ 2698s', 2668, 2728, 60, 1, '2026-02-01 16:55:47', NULL, NULL, 'event_auto', 1, 1, NULL),
(177, 23, 626, 0, 'Auto clip – Free Kick @ 2755s', 2725, 2785, 60, 1, '2026-02-01 16:56:19', NULL, NULL, 'event_auto', 1, 1, NULL),
(178, 23, 627, 0, 'Auto clip – Corner @ 2802s', 2772, 2832, 60, 1, '2026-02-01 16:56:46', NULL, NULL, 'event_auto', 1, 1, NULL),
(179, 23, 631, 0, 'Auto clip – Corner @ 3959s', 3929, 3989, 60, 1, '2026-02-01 17:00:10', NULL, NULL, 'event_auto', 1, 1, NULL),
(180, 23, 632, 0, 'Auto clip – Free Kick @ 3987s', 3957, 4017, 60, 1, '2026-02-01 17:00:24', NULL, NULL, 'event_auto', 1, 1, NULL),
(181, 23, 633, 0, 'Auto clip – Goal @ 4082s', 4052, 4112, 60, 1, '2026-02-01 17:01:46', NULL, NULL, 'event_auto', 1, 1, NULL),
(182, 23, 634, 0, 'Auto clip – Shot @ 4156s', 4126, 4186, 60, 1, '2026-02-01 17:02:25', NULL, NULL, 'event_auto', 1, 1, NULL),
(183, 23, 635, 0, 'Auto clip – Shot @ 4191s', 4161, 4221, 60, 1, '2026-02-01 17:02:50', NULL, NULL, 'event_auto', 1, 1, NULL),
(184, 23, 636, 0, 'Auto clip – Free Kick @ 4231s', 4201, 4261, 60, 1, '2026-02-01 17:03:12', NULL, NULL, 'event_auto', 1, 1, NULL),
(185, 23, 637, 0, 'Auto clip – Shot @ 4454s', 4424, 4484, 60, 1, '2026-02-01 17:06:09', NULL, NULL, 'event_auto', 1, 1, NULL),
(186, 23, 638, 0, 'Auto clip – Free Kick @ 4495s', 4465, 4525, 60, 1, '2026-02-01 17:06:22', NULL, NULL, 'event_auto', 1, 1, NULL),
(187, 23, 639, 0, 'Auto clip – Shot @ 4572s', 4542, 4602, 60, 1, '2026-02-01 17:07:22', NULL, NULL, 'event_auto', 1, 1, NULL),
(188, 23, 640, 0, 'Auto clip – Free Kick @ 4620s', 4590, 4650, 60, 1, '2026-02-01 17:08:05', NULL, NULL, 'event_auto', 1, 1, NULL),
(189, 23, 641, 0, 'Auto clip – Shot @ 4657s', 4627, 4687, 60, 1, '2026-02-01 17:08:41', NULL, NULL, 'event_auto', 1, 1, NULL),
(190, 23, 642, 0, 'Auto clip – Free Kick @ 4676s', 4646, 4706, 60, 1, '2026-02-01 17:09:49', NULL, NULL, 'event_auto', 1, 1, NULL),
(191, 23, 643, 0, 'Auto clip – Free Kick @ 4778s', 4748, 4808, 60, 1, '2026-02-01 17:11:02', NULL, NULL, 'event_auto', 1, 1, NULL),
(192, 23, 644, 0, 'Auto clip – Free Kick @ 4941s', 4911, 4971, 60, 1, '2026-02-01 17:13:03', NULL, NULL, 'event_auto', 1, 1, NULL),
(193, 23, 645, 0, 'Auto clip – Free Kick @ 4971s', 4941, 5001, 60, 1, '2026-02-01 17:13:19', NULL, NULL, 'event_auto', 1, 1, NULL),
(194, 23, 646, 0, 'Auto clip – Corner @ 5061s', 5031, 5091, 60, 1, '2026-02-01 17:14:07', NULL, NULL, 'event_auto', 1, 1, NULL),
(195, 23, 647, 0, 'Auto clip – Free Kick @ 5088s', 5058, 5118, 60, 1, '2026-02-01 17:14:20', NULL, NULL, 'event_auto', 1, 1, NULL),
(196, 23, 648, 0, 'Auto clip – Free Kick @ 5135s', 5105, 5165, 60, 1, '2026-02-01 17:14:53', NULL, NULL, 'event_auto', 1, 1, NULL),
(197, 23, 649, 0, 'Auto clip – Free Kick @ 5169s', 5139, 5199, 60, 1, '2026-02-01 17:15:22', NULL, NULL, 'event_auto', 1, 1, NULL),
(198, 23, 650, 0, 'Auto clip – Corner @ 5317s', 5287, 5347, 60, 1, '2026-02-01 17:17:03', NULL, NULL, 'event_auto', 1, 1, NULL),
(199, 23, 651, 0, 'Auto clip – Goal @ 5352s', 5322, 5382, 60, 1, '2026-02-01 17:17:27', NULL, NULL, 'event_auto', 1, 1, NULL),
(200, 23, 652, 0, 'Auto clip – Shot @ 5687s', 5657, 5717, 60, 1, '2026-02-01 17:20:51', NULL, NULL, 'event_auto', 1, 1, NULL),
(201, 23, 653, 0, 'Auto clip – Free Kick @ 5750s', 5720, 5780, 60, 1, '2026-02-01 17:21:30', NULL, NULL, 'event_auto', 1, 1, NULL),
(202, 23, 654, 0, 'Auto clip – Shot @ 5800s', 5770, 5830, 60, 1, '2026-02-01 17:21:48', NULL, NULL, 'event_auto', 1, 1, NULL),
(203, 23, 655, 0, 'Auto clip – Free Kick @ 5896s', 5866, 5926, 60, 1, '2026-02-01 17:22:57', NULL, NULL, 'event_auto', 1, 1, NULL),
(204, 23, 656, 0, 'Auto clip – Shot @ 5972s', 5942, 6002, 60, 1, '2026-02-01 17:23:20', NULL, NULL, 'event_auto', 1, 1, NULL),
(205, 23, 657, 0, 'Auto clip – Goal @ 6021s', 5991, 6051, 60, 1, '2026-02-01 17:23:52', NULL, NULL, 'event_auto', 1, 1, NULL),
(206, 23, 658, 0, 'Auto clip – Corner @ 6100s', 6070, 6130, 60, 1, '2026-02-01 17:24:31', NULL, NULL, 'event_auto', 1, 1, NULL),
(207, 23, 659, 0, 'Auto clip – Shot @ 6126s', 6096, 6156, 60, 1, '2026-02-01 17:24:44', NULL, NULL, 'event_auto', 1, 1, NULL),
(208, 23, 660, 0, 'Auto clip – Shot @ 6192s', 6162, 6222, 60, 1, '2026-02-01 17:25:56', NULL, NULL, 'event_auto', 1, 1, NULL),
(209, 23, 661, 0, 'Auto clip – Free Kick @ 6228s', 6198, 6258, 60, 1, '2026-02-01 17:26:32', NULL, NULL, 'event_auto', 1, 1, NULL),
(210, 23, 662, 0, 'Auto clip – Free Kick @ 6239s', 6209, 6269, 60, 1, '2026-02-01 17:26:38', NULL, NULL, 'event_auto', 1, 1, NULL),
(211, 23, 663, 0, 'Auto clip – Shot @ 6277s', 6247, 6307, 60, 1, '2026-02-01 17:27:13', NULL, NULL, 'event_auto', 1, 1, NULL),
(212, 23, 664, 0, 'Auto clip – Goal @ 6280s', 6250, 6310, 60, 1, '2026-02-01 17:27:22', NULL, NULL, 'event_auto', 1, 1, NULL),
(213, 23, 665, 0, 'Auto clip – Corner @ 6478s', 6448, 6508, 60, 1, '2026-02-01 17:29:26', NULL, NULL, 'event_auto', 1, 1, NULL),
(214, 23, 666, 0, 'Auto clip – Goal @ 6513s', 6483, 6543, 60, 1, '2026-02-01 17:29:48', NULL, NULL, 'event_auto', 1, 1, NULL);

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
(48, 19, 259, NULL, 'pending', '{\"match_id\":3,\"event_id\":259,\"start_second\":158,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 21:08:40', NULL),
(49, 19, 260, NULL, 'pending', '{\"match_id\":3,\"event_id\":260,\"start_second\":234,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 21:09:25', NULL),
(50, 19, 261, NULL, 'pending', '{\"match_id\":3,\"event_id\":261,\"start_second\":242,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 21:09:43', NULL),
(51, 19, 262, NULL, 'pending', '{\"match_id\":3,\"event_id\":262,\"start_second\":260,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 21:10:53', NULL),
(52, 19, 263, NULL, 'pending', '{\"match_id\":3,\"event_id\":263,\"start_second\":267,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 21:10:59', NULL),
(53, 19, 264, NULL, 'pending', '{\"match_id\":3,\"event_id\":264,\"start_second\":279,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 21:12:30', NULL),
(54, 19, 265, NULL, 'pending', '{\"match_id\":3,\"event_id\":265,\"start_second\":540,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 21:18:39', NULL),
(55, 19, 266, NULL, 'pending', '{\"match_id\":3,\"event_id\":266,\"start_second\":640,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 21:20:55', NULL),
(56, 19, 267, NULL, 'pending', '{\"match_id\":3,\"event_id\":267,\"start_second\":794,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 21:23:33', NULL),
(57, 19, NULL, NULL, 'pending', '{\"match_id\":3,\"event_id\":268,\"start_second\":821,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 21:23:59', NULL),
(58, 19, 269, NULL, 'pending', '{\"match_id\":3,\"event_id\":269,\"start_second\":969,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 21:27:59', NULL),
(59, 19, 270, NULL, 'pending', '{\"match_id\":3,\"event_id\":270,\"start_second\":1128,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 21:30:54', NULL),
(60, 19, 271, NULL, 'pending', '{\"match_id\":3,\"event_id\":271,\"start_second\":1273,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 21:33:44', NULL),
(61, 19, 272, NULL, 'pending', '{\"match_id\":3,\"event_id\":272,\"start_second\":1302,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 21:40:06', NULL),
(62, 19, 273, NULL, 'pending', '{\"match_id\":3,\"event_id\":273,\"start_second\":1434,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 21:42:38', NULL),
(63, 19, 274, NULL, 'pending', '{\"match_id\":3,\"event_id\":274,\"start_second\":1486,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 21:43:37', NULL),
(64, 19, 275, NULL, 'pending', '{\"match_id\":3,\"event_id\":275,\"start_second\":1486,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 21:43:41', NULL),
(65, 19, 276, NULL, 'pending', '{\"match_id\":3,\"event_id\":276,\"start_second\":1506,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 21:44:13', NULL),
(66, 19, 277, NULL, 'pending', '{\"match_id\":3,\"event_id\":277,\"start_second\":1582,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 21:45:30', NULL),
(67, 19, 278, NULL, 'pending', '{\"match_id\":3,\"event_id\":278,\"start_second\":1654,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 21:47:13', NULL),
(68, 19, 279, NULL, 'pending', '{\"match_id\":3,\"event_id\":279,\"start_second\":1700,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 21:48:20', NULL),
(69, 19, 280, NULL, 'pending', '{\"match_id\":3,\"event_id\":280,\"start_second\":1741,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 21:52:40', NULL),
(70, 19, 281, NULL, 'pending', '{\"match_id\":3,\"event_id\":281,\"start_second\":1964,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 21:56:25', NULL),
(71, 19, 282, NULL, 'pending', '{\"match_id\":3,\"event_id\":282,\"start_second\":2104,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 21:59:00', NULL),
(72, 19, 283, NULL, 'pending', '{\"match_id\":3,\"event_id\":283,\"start_second\":333,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 22:06:10', NULL),
(73, 19, NULL, NULL, 'pending', '{\"match_id\":3,\"event_id\":284,\"start_second\":554,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 22:09:52', NULL),
(74, 19, 285, NULL, 'pending', '{\"match_id\":3,\"event_id\":285,\"start_second\":2282,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 22:15:53', NULL),
(75, 19, 286, NULL, 'pending', '{\"match_id\":3,\"event_id\":286,\"start_second\":2362,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 22:17:13', NULL),
(76, 19, 287, NULL, 'pending', '{\"match_id\":3,\"event_id\":287,\"start_second\":2368,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 22:17:20', NULL),
(77, 19, 288, NULL, 'pending', '{\"match_id\":3,\"event_id\":288,\"start_second\":2522,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 22:21:04', NULL),
(78, 19, 289, NULL, 'pending', '{\"match_id\":3,\"event_id\":289,\"start_second\":2631,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 22:22:55', NULL),
(79, 19, 292, NULL, 'pending', '{\"match_id\":3,\"event_id\":292,\"start_second\":3587,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 22:28:30', NULL),
(80, 19, 293, NULL, 'pending', '{\"match_id\":3,\"event_id\":293,\"start_second\":3633,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 22:29:46', NULL),
(81, 19, 294, NULL, 'pending', '{\"match_id\":3,\"event_id\":294,\"start_second\":3892,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 22:46:46', NULL),
(82, 19, 295, NULL, 'pending', '{\"match_id\":3,\"event_id\":295,\"start_second\":3949,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 22:47:47', NULL),
(83, 19, 296, NULL, 'pending', '{\"match_id\":3,\"event_id\":296,\"start_second\":4163,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 22:51:21', NULL),
(84, 19, 297, NULL, 'pending', '{\"match_id\":3,\"event_id\":297,\"start_second\":4257,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 22:52:58', NULL),
(85, 19, 298, NULL, 'pending', '{\"match_id\":3,\"event_id\":298,\"start_second\":4417,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 22:55:41', NULL),
(86, 19, 299, NULL, 'pending', '{\"match_id\":3,\"event_id\":299,\"start_second\":4491,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 22:56:55', NULL),
(87, 19, 300, NULL, 'pending', '{\"match_id\":3,\"event_id\":300,\"start_second\":4593,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 22:58:37', NULL),
(88, 19, 301, NULL, 'pending', '{\"match_id\":3,\"event_id\":301,\"start_second\":4639,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 22:59:37', NULL),
(89, 19, 302, NULL, 'pending', '{\"match_id\":3,\"event_id\":302,\"start_second\":4776,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 23:02:09', NULL),
(90, 19, 303, NULL, 'pending', '{\"match_id\":3,\"event_id\":303,\"start_second\":4952,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 23:05:05', NULL),
(91, 19, 304, NULL, 'pending', '{\"match_id\":3,\"event_id\":304,\"start_second\":4983,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 23:05:36', NULL),
(92, 19, 305, NULL, 'pending', '{\"match_id\":3,\"event_id\":305,\"start_second\":5041,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 23:06:40', NULL),
(93, 19, 306, NULL, 'pending', '{\"match_id\":3,\"event_id\":306,\"start_second\":5095,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 23:08:10', NULL),
(94, 19, 307, NULL, 'pending', '{\"match_id\":3,\"event_id\":307,\"start_second\":5188,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 23:09:58', NULL),
(95, 19, 308, NULL, 'pending', '{\"match_id\":3,\"event_id\":308,\"start_second\":5193,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 23:12:30', NULL),
(96, 19, 309, NULL, 'pending', '{\"match_id\":3,\"event_id\":309,\"start_second\":5301,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 23:18:10', NULL),
(97, 19, 310, NULL, 'pending', '{\"match_id\":3,\"event_id\":310,\"start_second\":5366,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 23:19:15', NULL),
(98, 19, 311, NULL, 'pending', '{\"match_id\":3,\"event_id\":311,\"start_second\":5396,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 23:19:48', NULL),
(99, 19, 312, NULL, 'pending', '{\"match_id\":3,\"event_id\":312,\"start_second\":5416,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 23:20:10', NULL),
(100, 19, 313, NULL, 'pending', '{\"match_id\":3,\"event_id\":313,\"start_second\":5540,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 23:22:09', NULL),
(101, 19, 314, NULL, 'pending', '{\"match_id\":3,\"event_id\":314,\"start_second\":5633,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 23:23:45', NULL),
(102, 19, 315, NULL, 'pending', '{\"match_id\":3,\"event_id\":315,\"start_second\":5661,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 23:24:13', NULL),
(103, 19, 316, NULL, 'pending', '{\"match_id\":3,\"event_id\":316,\"start_second\":5747,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 23:25:43', NULL),
(104, 19, 317, NULL, 'pending', '{\"match_id\":3,\"event_id\":317,\"start_second\":5412,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 23:26:09', NULL),
(105, 19, 318, NULL, 'pending', '{\"match_id\":3,\"event_id\":318,\"start_second\":5771,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 23:26:55', NULL),
(106, 19, 319, NULL, 'pending', '{\"match_id\":3,\"event_id\":319,\"start_second\":5809,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 23:27:33', NULL),
(107, 19, 320, NULL, 'pending', '{\"match_id\":3,\"event_id\":320,\"start_second\":5976,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 23:30:21', NULL),
(108, 19, 321, NULL, 'pending', '{\"match_id\":3,\"event_id\":321,\"start_second\":6003,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 23:30:55', NULL),
(109, 19, 322, NULL, 'pending', '{\"match_id\":3,\"event_id\":322,\"start_second\":6051,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 23:31:47', NULL),
(110, 19, 323, NULL, 'pending', '{\"match_id\":3,\"event_id\":323,\"start_second\":6150,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 23:33:24', NULL),
(111, 19, NULL, NULL, 'pending', '{\"match_id\":3,\"event_id\":324,\"start_second\":6211,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 23:35:58', NULL),
(112, 19, NULL, NULL, 'pending', '{\"match_id\":3,\"event_id\":326,\"start_second\":260,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 23:56:00', NULL),
(113, 19, NULL, NULL, 'pending', '{\"match_id\":3,\"event_id\":327,\"start_second\":2062,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 23:56:06', NULL),
(127, 19, NULL, NULL, 'pending', '{\"match_id\":19,\"event_id\":467,\"start_second\":5193,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_19/source/veo/standard/match_19_standard.mp4\"}', NULL, NULL, '2026-01-20 08:28:42', NULL),
(128, 19, 468, NULL, 'pending', '{\"match_id\":19,\"event_id\":468,\"start_second\":3642,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_19/source/veo/standard/match_19_standard.mp4\"}', NULL, NULL, '2026-01-20 08:29:29', NULL),
(129, 19, NULL, NULL, 'pending', '{\"match_id\":19,\"event_id\":481,\"start_second\":0,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_19/source/veo/standard/match_19_standard.mp4\"}', NULL, NULL, '2026-01-21 20:25:46', NULL),
(130, 22, 525, NULL, 'pending', '{\"match_id\":22,\"event_id\":525,\"start_second\":217,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 15:08:58', NULL),
(131, 22, 526, NULL, 'pending', '{\"match_id\":22,\"event_id\":526,\"start_second\":279,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 15:10:12', NULL),
(132, 22, 527, NULL, 'pending', '{\"match_id\":22,\"event_id\":527,\"start_second\":356,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 15:11:33', NULL),
(133, 22, 528, NULL, 'pending', '{\"match_id\":22,\"event_id\":528,\"start_second\":406,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 15:12:05', NULL),
(134, 22, 529, NULL, 'pending', '{\"match_id\":22,\"event_id\":529,\"start_second\":514,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 15:13:59', NULL),
(135, 22, 530, NULL, 'pending', '{\"match_id\":22,\"event_id\":530,\"start_second\":614,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 15:15:28', NULL),
(136, 22, 531, NULL, 'pending', '{\"match_id\":22,\"event_id\":531,\"start_second\":695,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 15:16:35', NULL),
(137, 22, 532, NULL, 'pending', '{\"match_id\":22,\"event_id\":532,\"start_second\":734,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 15:17:04', NULL),
(138, 22, 533, NULL, 'pending', '{\"match_id\":22,\"event_id\":533,\"start_second\":805,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 15:18:16', NULL),
(139, 22, 534, NULL, 'pending', '{\"match_id\":22,\"event_id\":534,\"start_second\":871,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 15:19:28', NULL),
(140, 22, 535, NULL, 'pending', '{\"match_id\":22,\"event_id\":535,\"start_second\":959,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 15:21:14', NULL),
(141, 22, 536, NULL, 'pending', '{\"match_id\":22,\"event_id\":536,\"start_second\":969,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 15:21:26', NULL),
(142, 22, 537, NULL, 'pending', '{\"match_id\":22,\"event_id\":537,\"start_second\":1071,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 15:22:59', NULL),
(143, 22, 538, NULL, 'pending', '{\"match_id\":22,\"event_id\":538,\"start_second\":1123,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 15:23:43', NULL),
(144, 22, 539, NULL, 'pending', '{\"match_id\":22,\"event_id\":539,\"start_second\":1236,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 15:25:35', NULL),
(145, 22, 540, NULL, 'pending', '{\"match_id\":22,\"event_id\":540,\"start_second\":1247,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 15:25:51', NULL),
(146, 22, 541, NULL, 'pending', '{\"match_id\":22,\"event_id\":541,\"start_second\":1302,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 15:26:37', NULL),
(147, 22, 542, NULL, 'pending', '{\"match_id\":22,\"event_id\":542,\"start_second\":1389,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 15:28:10', NULL),
(148, 22, 543, NULL, 'pending', '{\"match_id\":22,\"event_id\":543,\"start_second\":1540,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 15:30:15', NULL),
(149, 22, 544, NULL, 'pending', '{\"match_id\":22,\"event_id\":544,\"start_second\":1590,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 15:30:40', NULL),
(150, 22, 545, NULL, 'pending', '{\"match_id\":22,\"event_id\":545,\"start_second\":1584,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 15:30:49', NULL),
(151, 22, 546, NULL, 'pending', '{\"match_id\":22,\"event_id\":546,\"start_second\":1757,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 15:33:13', NULL),
(152, 22, 547, NULL, 'pending', '{\"match_id\":22,\"event_id\":547,\"start_second\":1986,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 15:36:39', NULL),
(153, 22, 548, NULL, 'pending', '{\"match_id\":22,\"event_id\":548,\"start_second\":2050,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 15:37:18', NULL),
(154, 22, 549, NULL, 'pending', '{\"match_id\":22,\"event_id\":549,\"start_second\":2048,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 15:37:29', NULL),
(155, 22, 550, NULL, 'pending', '{\"match_id\":22,\"event_id\":550,\"start_second\":2402,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 15:41:25', NULL),
(156, 22, 551, NULL, 'pending', '{\"match_id\":22,\"event_id\":551,\"start_second\":2409,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 15:41:32', NULL),
(157, 22, 552, NULL, 'pending', '{\"match_id\":22,\"event_id\":552,\"start_second\":2447,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 15:42:00', NULL),
(158, 22, 553, NULL, 'pending', '{\"match_id\":22,\"event_id\":553,\"start_second\":2557,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 15:43:16', NULL),
(159, 22, 554, NULL, 'pending', '{\"match_id\":22,\"event_id\":554,\"start_second\":2775,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 15:46:09', NULL),
(160, 22, 557, NULL, 'pending', '{\"match_id\":22,\"event_id\":557,\"start_second\":3689,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 15:47:20', NULL),
(161, 22, 558, NULL, 'pending', '{\"match_id\":22,\"event_id\":558,\"start_second\":3779,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 15:48:31', NULL),
(162, 22, 559, NULL, 'pending', '{\"match_id\":22,\"event_id\":559,\"start_second\":3790,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 15:48:43', NULL),
(163, 22, 560, NULL, 'pending', '{\"match_id\":22,\"event_id\":560,\"start_second\":4109,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 15:51:44', NULL),
(164, 22, 561, NULL, 'pending', '{\"match_id\":22,\"event_id\":561,\"start_second\":4207,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 15:53:07', NULL),
(165, 22, 562, NULL, 'pending', '{\"match_id\":22,\"event_id\":562,\"start_second\":4210,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 15:53:11', NULL),
(166, 22, 563, NULL, 'pending', '{\"match_id\":22,\"event_id\":563,\"start_second\":4337,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 15:54:53', NULL),
(167, 22, 564, NULL, 'pending', '{\"match_id\":22,\"event_id\":564,\"start_second\":4342,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 15:55:00', NULL),
(168, 22, 565, NULL, 'pending', '{\"match_id\":22,\"event_id\":565,\"start_second\":4358,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 15:55:07', NULL),
(169, 22, 566, NULL, 'pending', '{\"match_id\":22,\"event_id\":566,\"start_second\":4465,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 15:55:55', NULL),
(170, 22, 567, NULL, 'pending', '{\"match_id\":22,\"event_id\":567,\"start_second\":4471,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 15:56:05', NULL),
(171, 22, 568, NULL, 'pending', '{\"match_id\":22,\"event_id\":568,\"start_second\":4533,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 15:56:22', NULL),
(172, 22, 569, NULL, 'pending', '{\"match_id\":22,\"event_id\":569,\"start_second\":4576,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 15:56:56', NULL),
(173, 22, 570, NULL, 'pending', '{\"match_id\":22,\"event_id\":570,\"start_second\":4590,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 15:57:16', NULL),
(174, 22, 571, NULL, 'pending', '{\"match_id\":22,\"event_id\":571,\"start_second\":4771,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 15:59:11', NULL),
(175, 22, 572, NULL, 'pending', '{\"match_id\":22,\"event_id\":572,\"start_second\":4863,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 16:00:21', NULL),
(176, 22, 573, NULL, 'pending', '{\"match_id\":22,\"event_id\":573,\"start_second\":5010,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 16:01:31', NULL),
(177, 22, 574, NULL, 'pending', '{\"match_id\":22,\"event_id\":574,\"start_second\":5083,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 16:02:48', NULL),
(178, 22, 575, NULL, 'pending', '{\"match_id\":22,\"event_id\":575,\"start_second\":5098,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 16:03:11', NULL),
(179, 22, 576, NULL, 'pending', '{\"match_id\":22,\"event_id\":576,\"start_second\":5153,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 16:03:51', NULL),
(180, 22, 577, NULL, 'pending', '{\"match_id\":22,\"event_id\":577,\"start_second\":5217,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 16:04:25', NULL),
(181, 22, 578, NULL, 'pending', '{\"match_id\":22,\"event_id\":578,\"start_second\":5401,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 16:05:42', NULL),
(182, 22, 579, NULL, 'pending', '{\"match_id\":22,\"event_id\":579,\"start_second\":5494,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 16:06:37', NULL),
(183, 22, 580, NULL, 'pending', '{\"match_id\":22,\"event_id\":580,\"start_second\":5530,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 16:06:54', NULL),
(184, 22, 581, NULL, 'pending', '{\"match_id\":22,\"event_id\":581,\"start_second\":5641,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 16:07:32', NULL),
(185, 22, 582, NULL, 'pending', '{\"match_id\":22,\"event_id\":582,\"start_second\":5656,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 16:07:57', NULL),
(186, 22, 583, NULL, 'pending', '{\"match_id\":22,\"event_id\":583,\"start_second\":5659,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 16:08:15', NULL),
(187, 22, 584, NULL, 'pending', '{\"match_id\":22,\"event_id\":584,\"start_second\":5830,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 16:10:44', NULL),
(188, 22, 585, NULL, 'pending', '{\"match_id\":22,\"event_id\":585,\"start_second\":5857,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 16:11:11', NULL),
(189, 22, 586, NULL, 'pending', '{\"match_id\":22,\"event_id\":586,\"start_second\":6149,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 16:13:51', NULL),
(190, 22, 587, NULL, 'pending', '{\"match_id\":22,\"event_id\":587,\"start_second\":6233,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 16:14:42', NULL),
(191, 22, 588, NULL, 'pending', '{\"match_id\":22,\"event_id\":588,\"start_second\":6313,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-25 16:15:59', NULL),
(192, 22, 598, NULL, 'pending', '{\"match_id\":22,\"event_id\":598,\"start_second\":5673,\"duration_seconds\":60,\"source_path\":\"video_22.mp4\"}', NULL, NULL, '2026-01-31 11:24:16', NULL),
(193, 23, 600, NULL, 'pending', '{\"match_id\":23,\"event_id\":600,\"start_second\":52,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 16:19:48', NULL),
(194, 23, NULL, NULL, 'pending', '{\"match_id\":23,\"event_id\":601,\"start_second\":2332,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 16:20:28', NULL),
(195, 23, 602, NULL, 'pending', '{\"match_id\":23,\"event_id\":602,\"start_second\":141,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 16:22:40', NULL),
(196, 23, 603, NULL, 'pending', '{\"match_id\":23,\"event_id\":603,\"start_second\":271,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 16:25:59', NULL),
(197, 23, 604, NULL, 'pending', '{\"match_id\":23,\"event_id\":604,\"start_second\":290,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 16:26:20', NULL),
(198, 23, 605, NULL, 'pending', '{\"match_id\":23,\"event_id\":605,\"start_second\":421,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 16:29:05', NULL),
(199, 23, 606, NULL, 'pending', '{\"match_id\":23,\"event_id\":606,\"start_second\":622,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 16:31:51', NULL),
(200, 23, 607, NULL, 'pending', '{\"match_id\":23,\"event_id\":607,\"start_second\":966,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 16:35:42', NULL),
(201, 23, 608, NULL, 'pending', '{\"match_id\":23,\"event_id\":608,\"start_second\":1166,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 16:37:58', NULL),
(202, 23, 609, NULL, 'pending', '{\"match_id\":23,\"event_id\":609,\"start_second\":1296,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 16:39:21', NULL),
(203, 23, 610, NULL, 'pending', '{\"match_id\":23,\"event_id\":610,\"start_second\":1297,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 16:39:23', NULL),
(204, 23, 611, NULL, 'pending', '{\"match_id\":23,\"event_id\":611,\"start_second\":1333,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 16:39:51', NULL),
(205, 23, 612, NULL, 'pending', '{\"match_id\":23,\"event_id\":612,\"start_second\":1336,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 16:39:54', NULL),
(206, 23, 613, NULL, 'pending', '{\"match_id\":23,\"event_id\":613,\"start_second\":1401,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 16:40:56', NULL),
(207, 23, 614, NULL, 'pending', '{\"match_id\":23,\"event_id\":614,\"start_second\":1547,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 16:42:47', NULL),
(208, 23, 615, NULL, 'pending', '{\"match_id\":23,\"event_id\":615,\"start_second\":1580,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 16:43:07', NULL),
(209, 23, 616, NULL, 'pending', '{\"match_id\":23,\"event_id\":616,\"start_second\":1742,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 16:43:54', NULL),
(210, 23, 617, NULL, 'pending', '{\"match_id\":23,\"event_id\":617,\"start_second\":1918,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 16:47:03', NULL),
(211, 23, 618, NULL, 'pending', '{\"match_id\":23,\"event_id\":618,\"start_second\":1946,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 16:47:15', NULL),
(212, 23, 619, NULL, 'pending', '{\"match_id\":23,\"event_id\":619,\"start_second\":1958,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 16:47:25', NULL),
(213, 23, 620, NULL, 'pending', '{\"match_id\":23,\"event_id\":620,\"start_second\":2022,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 16:48:31', NULL),
(214, 23, 621, NULL, 'pending', '{\"match_id\":23,\"event_id\":621,\"start_second\":2096,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 16:49:34', NULL),
(215, 23, 622, NULL, 'pending', '{\"match_id\":23,\"event_id\":622,\"start_second\":2190,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 16:51:02', NULL),
(216, 23, 623, NULL, 'pending', '{\"match_id\":23,\"event_id\":623,\"start_second\":2358,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 16:52:13', NULL),
(217, 23, 624, NULL, 'pending', '{\"match_id\":23,\"event_id\":624,\"start_second\":2565,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 16:54:19', NULL),
(218, 23, 625, NULL, 'pending', '{\"match_id\":23,\"event_id\":625,\"start_second\":2668,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 16:55:47', NULL),
(219, 23, 626, NULL, 'pending', '{\"match_id\":23,\"event_id\":626,\"start_second\":2725,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 16:56:19', NULL),
(220, 23, 627, NULL, 'pending', '{\"match_id\":23,\"event_id\":627,\"start_second\":2772,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 16:56:46', NULL),
(221, 23, 631, NULL, 'pending', '{\"match_id\":23,\"event_id\":631,\"start_second\":3929,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 17:00:10', NULL),
(222, 23, 632, NULL, 'pending', '{\"match_id\":23,\"event_id\":632,\"start_second\":3957,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 17:00:24', NULL),
(223, 23, 633, NULL, 'pending', '{\"match_id\":23,\"event_id\":633,\"start_second\":4052,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 17:01:46', NULL),
(224, 23, 634, NULL, 'pending', '{\"match_id\":23,\"event_id\":634,\"start_second\":4126,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 17:02:25', NULL),
(225, 23, 635, NULL, 'pending', '{\"match_id\":23,\"event_id\":635,\"start_second\":4161,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 17:02:50', NULL),
(226, 23, 636, NULL, 'pending', '{\"match_id\":23,\"event_id\":636,\"start_second\":4201,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 17:03:12', NULL),
(227, 23, 637, NULL, 'pending', '{\"match_id\":23,\"event_id\":637,\"start_second\":4424,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 17:06:09', NULL),
(228, 23, 638, NULL, 'pending', '{\"match_id\":23,\"event_id\":638,\"start_second\":4465,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 17:06:22', NULL),
(229, 23, 639, NULL, 'pending', '{\"match_id\":23,\"event_id\":639,\"start_second\":4542,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 17:07:22', NULL),
(230, 23, 640, NULL, 'pending', '{\"match_id\":23,\"event_id\":640,\"start_second\":4590,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 17:08:05', NULL),
(231, 23, 641, NULL, 'pending', '{\"match_id\":23,\"event_id\":641,\"start_second\":4627,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 17:08:41', NULL),
(232, 23, 642, NULL, 'pending', '{\"match_id\":23,\"event_id\":642,\"start_second\":4646,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 17:09:49', NULL),
(233, 23, 643, NULL, 'pending', '{\"match_id\":23,\"event_id\":643,\"start_second\":4748,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 17:11:02', NULL),
(234, 23, 644, NULL, 'pending', '{\"match_id\":23,\"event_id\":644,\"start_second\":4911,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 17:13:03', NULL),
(235, 23, 645, NULL, 'pending', '{\"match_id\":23,\"event_id\":645,\"start_second\":4941,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 17:13:19', NULL),
(236, 23, 646, NULL, 'pending', '{\"match_id\":23,\"event_id\":646,\"start_second\":5031,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 17:14:07', NULL),
(237, 23, 647, NULL, 'pending', '{\"match_id\":23,\"event_id\":647,\"start_second\":5058,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 17:14:20', NULL),
(238, 23, 648, NULL, 'pending', '{\"match_id\":23,\"event_id\":648,\"start_second\":5105,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 17:14:53', NULL),
(239, 23, 649, NULL, 'pending', '{\"match_id\":23,\"event_id\":649,\"start_second\":5139,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 17:15:22', NULL),
(240, 23, 650, NULL, 'pending', '{\"match_id\":23,\"event_id\":650,\"start_second\":5287,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 17:17:03', NULL),
(241, 23, 651, NULL, 'pending', '{\"match_id\":23,\"event_id\":651,\"start_second\":5322,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 17:17:27', NULL),
(242, 23, 652, NULL, 'pending', '{\"match_id\":23,\"event_id\":652,\"start_second\":5657,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 17:20:51', NULL),
(243, 23, 653, NULL, 'pending', '{\"match_id\":23,\"event_id\":653,\"start_second\":5720,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 17:21:30', NULL),
(244, 23, 654, NULL, 'pending', '{\"match_id\":23,\"event_id\":654,\"start_second\":5770,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 17:21:48', NULL),
(245, 23, 655, NULL, 'pending', '{\"match_id\":23,\"event_id\":655,\"start_second\":5866,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 17:22:57', NULL),
(246, 23, 656, NULL, 'pending', '{\"match_id\":23,\"event_id\":656,\"start_second\":5942,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 17:23:20', NULL),
(247, 23, 657, NULL, 'pending', '{\"match_id\":23,\"event_id\":657,\"start_second\":5991,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 17:23:52', NULL),
(248, 23, 658, NULL, 'pending', '{\"match_id\":23,\"event_id\":658,\"start_second\":6070,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 17:24:31', NULL),
(249, 23, 659, NULL, 'pending', '{\"match_id\":23,\"event_id\":659,\"start_second\":6096,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 17:24:44', NULL),
(250, 23, 660, NULL, 'pending', '{\"match_id\":23,\"event_id\":660,\"start_second\":6162,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 17:25:56', NULL),
(251, 23, 661, NULL, 'pending', '{\"match_id\":23,\"event_id\":661,\"start_second\":6198,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 17:26:32', NULL),
(252, 23, 662, NULL, 'pending', '{\"match_id\":23,\"event_id\":662,\"start_second\":6209,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 17:26:38', NULL),
(253, 23, 663, NULL, 'pending', '{\"match_id\":23,\"event_id\":663,\"start_second\":6247,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 17:27:13', NULL),
(254, 23, 664, NULL, 'pending', '{\"match_id\":23,\"event_id\":664,\"start_second\":6250,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 17:27:22', NULL),
(255, 23, 665, NULL, 'pending', '{\"match_id\":23,\"event_id\":665,\"start_second\":6448,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 17:29:26', NULL),
(256, 23, 666, NULL, 'pending', '{\"match_id\":23,\"event_id\":666,\"start_second\":6483,\"duration_seconds\":60,\"source_path\":\"video_23.mp4\"}', NULL, NULL, '2026-02-01 17:29:48', NULL);

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
(75, 88, NULL, 'pending', NULL, NULL),
(76, 89, NULL, 'pending', NULL, NULL),
(77, 90, NULL, 'pending', NULL, NULL),
(78, 91, NULL, 'pending', NULL, NULL),
(79, 92, NULL, 'pending', NULL, NULL),
(80, 93, NULL, 'pending', NULL, NULL),
(81, 94, NULL, 'pending', NULL, NULL),
(82, 95, NULL, 'pending', NULL, NULL),
(83, 96, NULL, 'pending', NULL, NULL),
(84, 97, NULL, 'pending', NULL, NULL),
(85, 98, NULL, 'pending', NULL, NULL),
(86, 99, NULL, 'pending', NULL, NULL),
(87, 100, NULL, 'pending', NULL, NULL),
(88, 101, NULL, 'pending', NULL, NULL),
(89, 102, NULL, 'pending', NULL, NULL),
(90, 103, NULL, 'pending', NULL, NULL),
(91, 104, NULL, 'pending', NULL, NULL),
(92, 105, NULL, 'pending', NULL, NULL),
(93, 106, NULL, 'pending', NULL, NULL),
(94, 107, NULL, 'pending', NULL, NULL),
(95, 108, NULL, 'pending', NULL, NULL),
(96, 109, NULL, 'pending', NULL, NULL),
(97, 110, NULL, 'pending', NULL, NULL),
(98, 111, NULL, 'pending', NULL, NULL),
(99, 112, NULL, 'pending', NULL, NULL),
(100, 113, NULL, 'pending', NULL, NULL),
(101, 114, NULL, 'pending', NULL, NULL),
(102, 115, NULL, 'pending', NULL, NULL),
(103, 116, NULL, 'pending', NULL, NULL),
(104, 117, NULL, 'pending', NULL, NULL),
(105, 118, NULL, 'pending', NULL, NULL),
(106, 119, NULL, 'pending', NULL, NULL),
(107, 120, NULL, 'pending', NULL, NULL),
(108, 121, NULL, 'pending', NULL, NULL),
(109, 122, NULL, 'pending', NULL, NULL),
(110, 123, NULL, 'pending', NULL, NULL),
(111, 124, NULL, 'pending', NULL, NULL),
(112, 125, NULL, 'pending', NULL, NULL),
(113, 126, NULL, 'pending', NULL, NULL),
(114, 127, NULL, 'pending', NULL, NULL),
(115, 128, NULL, 'pending', NULL, NULL),
(116, 129, NULL, 'pending', NULL, NULL),
(117, 130, NULL, 'pending', NULL, NULL),
(118, 131, NULL, 'pending', NULL, NULL),
(119, 132, NULL, 'pending', NULL, NULL),
(120, 133, NULL, 'pending', NULL, NULL),
(121, 134, NULL, 'pending', NULL, NULL),
(122, 135, NULL, 'pending', NULL, NULL),
(123, 136, NULL, 'pending', NULL, NULL),
(124, 137, NULL, 'pending', NULL, NULL),
(125, 138, NULL, 'pending', NULL, NULL),
(126, 139, NULL, 'pending', NULL, NULL),
(127, 140, NULL, 'pending', NULL, NULL),
(128, 141, NULL, 'pending', NULL, NULL),
(129, 142, NULL, 'pending', NULL, NULL),
(130, 143, NULL, 'pending', NULL, NULL),
(131, 144, NULL, 'pending', NULL, NULL),
(132, 145, NULL, 'pending', NULL, NULL),
(133, 146, NULL, 'pending', NULL, NULL),
(134, 147, NULL, 'pending', NULL, NULL),
(135, 148, NULL, 'pending', NULL, NULL),
(136, 149, NULL, 'pending', NULL, NULL),
(137, 150, NULL, 'pending', NULL, NULL),
(138, 151, NULL, 'pending', NULL, NULL),
(140, 153, NULL, 'pending', NULL, NULL),
(141, 154, NULL, 'pending', NULL, NULL),
(142, 155, NULL, 'pending', NULL, NULL),
(143, 156, NULL, 'pending', NULL, NULL),
(144, 157, NULL, 'pending', NULL, NULL),
(145, 158, NULL, 'pending', NULL, NULL),
(146, 159, NULL, 'pending', NULL, NULL),
(147, 160, NULL, 'pending', NULL, NULL),
(148, 161, NULL, 'pending', NULL, NULL),
(149, 162, NULL, 'pending', NULL, NULL),
(150, 163, NULL, 'pending', NULL, NULL),
(151, 164, NULL, 'pending', NULL, NULL),
(152, 165, NULL, 'pending', NULL, NULL),
(153, 166, NULL, 'pending', NULL, NULL),
(154, 167, NULL, 'pending', NULL, NULL),
(155, 168, NULL, 'pending', NULL, NULL),
(156, 169, NULL, 'pending', NULL, NULL),
(157, 170, NULL, 'pending', NULL, NULL),
(158, 171, NULL, 'pending', NULL, NULL),
(159, 172, NULL, 'pending', NULL, NULL),
(160, 173, NULL, 'pending', NULL, NULL),
(161, 174, NULL, 'pending', NULL, NULL),
(162, 175, NULL, 'pending', NULL, NULL),
(163, 176, NULL, 'pending', NULL, NULL),
(164, 177, NULL, 'pending', NULL, NULL),
(165, 178, NULL, 'pending', NULL, NULL),
(166, 179, NULL, 'pending', NULL, NULL),
(167, 180, NULL, 'pending', NULL, NULL),
(168, 181, NULL, 'pending', NULL, NULL),
(169, 182, NULL, 'pending', NULL, NULL),
(170, 183, NULL, 'pending', NULL, NULL),
(171, 184, NULL, 'pending', NULL, NULL),
(172, 185, NULL, 'pending', NULL, NULL),
(173, 186, NULL, 'pending', NULL, NULL),
(174, 187, NULL, 'pending', NULL, NULL),
(175, 188, NULL, 'pending', NULL, NULL),
(176, 189, NULL, 'pending', NULL, NULL),
(177, 190, NULL, 'pending', NULL, NULL),
(178, 191, NULL, 'pending', NULL, NULL),
(179, 192, NULL, 'pending', NULL, NULL),
(180, 193, NULL, 'pending', NULL, NULL),
(181, 194, NULL, 'pending', NULL, NULL),
(182, 195, NULL, 'pending', NULL, NULL),
(183, 196, NULL, 'pending', NULL, NULL),
(184, 197, NULL, 'pending', NULL, NULL),
(185, 198, NULL, 'pending', NULL, NULL),
(186, 199, NULL, 'pending', NULL, NULL),
(187, 200, NULL, 'pending', NULL, NULL),
(188, 201, NULL, 'pending', NULL, NULL),
(189, 202, NULL, 'pending', NULL, NULL),
(190, 203, NULL, 'pending', NULL, NULL),
(191, 204, NULL, 'pending', NULL, NULL),
(192, 205, NULL, 'pending', NULL, NULL),
(193, 206, NULL, 'pending', NULL, NULL),
(194, 207, NULL, 'pending', NULL, NULL),
(195, 208, NULL, 'pending', NULL, NULL),
(196, 209, NULL, 'pending', NULL, NULL),
(197, 210, NULL, 'pending', NULL, NULL),
(198, 211, NULL, 'pending', NULL, NULL),
(199, 212, NULL, 'pending', NULL, NULL),
(200, 213, NULL, 'pending', NULL, NULL),
(201, 214, NULL, 'pending', NULL, NULL);

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
  `season_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `type` varchar(20) NOT NULL DEFAULT 'cup',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `competitions`
--

INSERT INTO `competitions` (`id`, `club_id`, `season_id`, `name`, `type`, `created_at`, `updated_at`) VALUES
(2, 1, 1, 'Fourth Division', 'league', '2026-01-19 12:59:33', '2026-01-20 09:02:09'),
(3, 1, 1, 'Finest Carmats South Region Challenge Cup', 'cup', '2026-01-19 12:59:33', '2026-01-20 09:02:09'),
(4, 1, 1, '3 Pillars Financial Planning Scottish Communities Cup', 'cup', '2026-01-19 12:59:33', '2026-01-20 09:02:09'),
(5, 1, 1, 'Strathclyde Demolition West Of Scotland League Cup', 'cup', '2026-01-19 12:59:33', '2026-01-20 09:02:09'),
(12, 1, 1, 'Finest Carmats South Region Challenge Cup - Round 2', 'cup', '2026-01-28 10:01:46', NULL),
(13, 1, 1, '3 Pillars Financial Planning Scottish Communities Cup - Round 2', 'cup', '2026-01-28 10:19:56', NULL),
(14, 1, 1, 'Strathclyde Demolition West Of Scotland League Cup - Round 1', 'cup', '2026-01-28 10:22:19', NULL),
(15, 1, 1, 'Strathclyde Demolition West Of Scotland League Cup - Round 2', 'cup', '2026-01-28 10:22:19', NULL),
(16, 1, 1, 'Finest Carmats South Region Challenge Cup - Round 1', 'cup', '2026-01-28 10:22:19', NULL);

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

--
-- Dumping data for table `derived_stats`
--

INSERT INTO `derived_stats` (`id`, `match_id`, `events_version_used`, `computed_at`, `payload_json`) VALUES
(1, 23, 1, '2026-02-01 16:19:17', '{\n    \"computed_at\": \"2026-02-01 16:19:17\",\n    \"events_version_used\": 1,\n    \"by_type_team\": {\n        \"goal\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"shot\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"shot_on_target\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"shot_off_target\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"chance\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"corner\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"free_kick\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"penalty\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"foul\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"yellow_card\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"red_card\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"mistake\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"good_play\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"highlight\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        }\n    },\n    \"phase_2\": {\n        \"by_period\": {\n            \"1H\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 1\n            },\n            \"2H\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            },\n            \"ET\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            },\n            \"other\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            }\n        },\n        \"per_15_minute\": [\n            {\n                \"label\": \"0-15\",\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 1\n            }\n        ],\n        \"importance_weighted\": {\n            \"by_team\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 1\n            },\n            \"by_type_team\": {\n                \"goal\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"shot\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"shot_on_target\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"shot_off_target\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"chance\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"corner\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"free_kick\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"penalty\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"foul\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"yellow_card\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"red_card\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"mistake\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"good_play\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"highlight\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                }\n            }\n        }\n    },\n    \"totals\": {\n        \"set_pieces\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"cards\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"highlights\": {\n            \"total\": 0,\n            \"by_team\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            }\n        }\n    }\n}'),
(2, 23, 2, '2026-02-01 16:20:01', '{\n    \"computed_at\": \"2026-02-01 16:20:01\",\n    \"events_version_used\": 2,\n    \"by_type_team\": {\n        \"goal\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"shot\": {\n            \"home\": 0,\n            \"away\": 1,\n            \"unknown\": 0\n        },\n        \"shot_on_target\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"shot_off_target\": {\n            \"home\": 0,\n            \"away\": 1,\n            \"unknown\": 0\n        },\n        \"chance\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"corner\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"free_kick\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"penalty\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"foul\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"yellow_card\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"red_card\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"mistake\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"good_play\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"highlight\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        }\n    },\n    \"phase_2\": {\n        \"by_period\": {\n            \"1H\": {\n                \"home\": 0,\n                \"away\": 1,\n                \"unknown\": 1\n            },\n            \"2H\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            },\n            \"ET\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            },\n            \"other\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            }\n        },\n        \"per_15_minute\": [\n            {\n                \"label\": \"0-15\",\n                \"home\": 0,\n                \"away\": 1,\n                \"unknown\": 1\n            }\n        ],\n        \"importance_weighted\": {\n            \"by_team\": {\n                \"home\": 0,\n                \"away\": 3,\n                \"unknown\": 1\n            },\n            \"by_type_team\": {\n                \"goal\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"shot\": {\n                    \"home\": 0,\n                    \"away\": 3,\n                    \"unknown\": 0\n                },\n                \"shot_on_target\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"shot_off_target\": {\n                    \"home\": 0,\n                    \"away\": 3,\n                    \"unknown\": 0\n                },\n                \"chance\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"corner\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"free_kick\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"penalty\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"foul\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"yellow_card\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"red_card\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"mistake\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"good_play\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"highlight\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                }\n            }\n        }\n    },\n    \"totals\": {\n        \"set_pieces\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"cards\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"highlights\": {\n            \"total\": 0,\n            \"by_team\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            }\n        }\n    }\n}'),
(3, 23, 3, '2026-02-01 16:20:42', '{\n    \"computed_at\": \"2026-02-01 16:20:42\",\n    \"events_version_used\": 3,\n    \"by_type_team\": {\n        \"goal\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"shot\": {\n            \"home\": 0,\n            \"away\": 2,\n            \"unknown\": 0\n        },\n        \"shot_on_target\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"shot_off_target\": {\n            \"home\": 0,\n            \"away\": 2,\n            \"unknown\": 0\n        },\n        \"chance\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"corner\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"free_kick\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"penalty\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"foul\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"yellow_card\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"red_card\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"mistake\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"good_play\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"highlight\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        }\n    },\n    \"phase_2\": {\n        \"by_period\": {\n            \"1H\": {\n                \"home\": 0,\n                \"away\": 2,\n                \"unknown\": 1\n            },\n            \"2H\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            },\n            \"ET\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            },\n            \"other\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            }\n        },\n        \"per_15_minute\": [\n            {\n                \"label\": \"0-15\",\n                \"home\": 0,\n                \"away\": 1,\n                \"unknown\": 1\n            },\n            {\n                \"label\": \"30-45\",\n                \"home\": 0,\n                \"away\": 1,\n                \"unknown\": 0\n            }\n        ],\n        \"importance_weighted\": {\n            \"by_team\": {\n                \"home\": 0,\n                \"away\": 6,\n                \"unknown\": 1\n            },\n            \"by_type_team\": {\n                \"goal\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"shot\": {\n                    \"home\": 0,\n                    \"away\": 6,\n                    \"unknown\": 0\n                },\n                \"shot_on_target\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"shot_off_target\": {\n                    \"home\": 0,\n                    \"away\": 6,\n                    \"unknown\": 0\n                },\n                \"chance\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"corner\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"free_kick\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"penalty\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"foul\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"yellow_card\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"red_card\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"mistake\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"good_play\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"highlight\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                }\n            }\n        }\n    },\n    \"totals\": {\n        \"set_pieces\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"cards\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"highlights\": {\n            \"total\": 0,\n            \"by_team\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            }\n        }\n    }\n}'),
(4, 23, 7, '2026-02-01 16:26:40', '{\n    \"computed_at\": \"2026-02-01 16:26:40\",\n    \"events_version_used\": 7,\n    \"by_type_team\": {\n        \"goal\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"shot\": {\n            \"home\": 0,\n            \"away\": 2,\n            \"unknown\": 0\n        },\n        \"shot_on_target\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"shot_off_target\": {\n            \"home\": 0,\n            \"away\": 2,\n            \"unknown\": 0\n        },\n        \"chance\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"corner\": {\n            \"home\": 0,\n            \"away\": 1,\n            \"unknown\": 0\n        },\n        \"free_kick\": {\n            \"home\": 1,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"penalty\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"foul\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"yellow_card\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"red_card\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"mistake\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"good_play\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"highlight\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        }\n    },\n    \"phase_2\": {\n        \"by_period\": {\n            \"1H\": {\n                \"home\": 1,\n                \"away\": 3,\n                \"unknown\": 1\n            },\n            \"2H\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            },\n            \"ET\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            },\n            \"other\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            }\n        },\n        \"per_15_minute\": [\n            {\n                \"label\": \"0-15\",\n                \"home\": 1,\n                \"away\": 3,\n                \"unknown\": 1\n            }\n        ],\n        \"importance_weighted\": {\n            \"by_team\": {\n                \"home\": 2,\n                \"away\": 8,\n                \"unknown\": 1\n            },\n            \"by_type_team\": {\n                \"goal\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"shot\": {\n                    \"home\": 0,\n                    \"away\": 6,\n                    \"unknown\": 0\n                },\n                \"shot_on_target\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"shot_off_target\": {\n                    \"home\": 0,\n                    \"away\": 6,\n                    \"unknown\": 0\n                },\n                \"chance\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"corner\": {\n                    \"home\": 0,\n                    \"away\": 2,\n                    \"unknown\": 0\n                },\n                \"free_kick\": {\n                    \"home\": 2,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"penalty\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"foul\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"yellow_card\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"red_card\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"mistake\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"good_play\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"highlight\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                }\n            }\n        }\n    },\n    \"totals\": {\n        \"set_pieces\": {\n            \"home\": 1,\n            \"away\": 1,\n            \"unknown\": 0\n        },\n        \"cards\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"highlights\": {\n            \"total\": 0,\n            \"by_team\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            }\n        }\n    }\n}'),
(5, 23, 5, '2026-02-01 16:26:40', '{\n    \"computed_at\": \"2026-02-01 16:26:40\",\n    \"events_version_used\": 5,\n    \"by_type_team\": {\n        \"goal\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"shot\": {\n            \"home\": 0,\n            \"away\": 2,\n            \"unknown\": 0\n        },\n        \"shot_on_target\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"shot_off_target\": {\n            \"home\": 0,\n            \"away\": 2,\n            \"unknown\": 0\n        },\n        \"chance\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"corner\": {\n            \"home\": 0,\n            \"away\": 1,\n            \"unknown\": 0\n        },\n        \"free_kick\": {\n            \"home\": 1,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"penalty\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"foul\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"yellow_card\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"red_card\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"mistake\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"good_play\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"highlight\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        }\n    },\n    \"phase_2\": {\n        \"by_period\": {\n            \"1H\": {\n                \"home\": 1,\n                \"away\": 3,\n                \"unknown\": 1\n            },\n            \"2H\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            },\n            \"ET\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            },\n            \"other\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            }\n        },\n        \"per_15_minute\": [\n            {\n                \"label\": \"0-15\",\n                \"home\": 1,\n                \"away\": 3,\n                \"unknown\": 1\n            }\n        ],\n        \"importance_weighted\": {\n            \"by_team\": {\n                \"home\": 2,\n                \"away\": 8,\n                \"unknown\": 1\n            },\n            \"by_type_team\": {\n                \"goal\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"shot\": {\n                    \"home\": 0,\n                    \"away\": 6,\n                    \"unknown\": 0\n                },\n                \"shot_on_target\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"shot_off_target\": {\n                    \"home\": 0,\n                    \"away\": 6,\n                    \"unknown\": 0\n                },\n                \"chance\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"corner\": {\n                    \"home\": 0,\n                    \"away\": 2,\n                    \"unknown\": 0\n                },\n                \"free_kick\": {\n                    \"home\": 2,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"penalty\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"foul\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"yellow_card\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"red_card\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"mistake\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"good_play\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"highlight\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                }\n            }\n        }\n    },\n    \"totals\": {\n        \"set_pieces\": {\n            \"home\": 1,\n            \"away\": 1,\n            \"unknown\": 0\n        },\n        \"cards\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"highlights\": {\n            \"total\": 0,\n            \"by_team\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            }\n        }\n    }\n}'),
(6, 23, 31, '2026-02-01 16:57:37', '{\n    \"computed_at\": \"2026-02-01 16:57:37\",\n    \"events_version_used\": 31,\n    \"by_type_team\": {\n        \"goal\": {\n            \"home\": 1,\n            \"away\": 2,\n            \"unknown\": 0\n        },\n        \"shot\": {\n            \"home\": 2,\n            \"away\": 10,\n            \"unknown\": 0\n        },\n        \"shot_on_target\": {\n            \"home\": 0,\n            \"away\": 2,\n            \"unknown\": 0\n        },\n        \"shot_off_target\": {\n            \"home\": 1,\n            \"away\": 6,\n            \"unknown\": 0\n        },\n        \"chance\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"corner\": {\n            \"home\": 0,\n            \"away\": 8,\n            \"unknown\": 0\n        },\n        \"free_kick\": {\n            \"home\": 2,\n            \"away\": 3,\n            \"unknown\": 0\n        },\n        \"penalty\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"foul\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"yellow_card\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"red_card\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"mistake\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"good_play\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"highlight\": {\n            \"home\": 1,\n            \"away\": 1,\n            \"unknown\": 0\n        }\n    },\n    \"phase_2\": {\n        \"by_period\": {\n            \"1H\": {\n                \"home\": 5,\n                \"away\": 22,\n                \"unknown\": 2\n            },\n            \"2H\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            },\n            \"ET\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            },\n            \"other\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 1\n            }\n        },\n        \"per_15_minute\": [\n            {\n                \"label\": \"0-15\",\n                \"home\": 1,\n                \"away\": 5,\n                \"unknown\": 1\n            },\n            {\n                \"label\": \"15-30\",\n                \"home\": 2,\n                \"away\": 8,\n                \"unknown\": 0\n            },\n            {\n                \"label\": \"30-45\",\n                \"home\": 2,\n                \"away\": 7,\n                \"unknown\": 0\n            },\n            {\n                \"label\": \"45-60\",\n                \"home\": 0,\n                \"away\": 2,\n                \"unknown\": 2\n            }\n        ],\n        \"importance_weighted\": {\n            \"by_team\": {\n                \"home\": 14,\n                \"away\": 58,\n                \"unknown\": 5\n            },\n            \"by_type_team\": {\n                \"goal\": {\n                    \"home\": 5,\n                    \"away\": 10,\n                    \"unknown\": 0\n                },\n                \"shot\": {\n                    \"home\": 8,\n                    \"away\": 34,\n                    \"unknown\": 0\n                },\n                \"shot_on_target\": {\n                    \"home\": 0,\n                    \"away\": 6,\n                    \"unknown\": 0\n                },\n                \"shot_off_target\": {\n                    \"home\": 3,\n                    \"away\": 18,\n                    \"unknown\": 0\n                },\n                \"chance\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"corner\": {\n                    \"home\": 0,\n                    \"away\": 16,\n                    \"unknown\": 0\n                },\n                \"free_kick\": {\n                    \"home\": 4,\n                    \"away\": 6,\n                    \"unknown\": 0\n                },\n                \"penalty\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"foul\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"yellow_card\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"red_card\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"mistake\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"good_play\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"highlight\": {\n                    \"home\": 2,\n                    \"away\": 2,\n                    \"unknown\": 0\n                }\n            }\n        }\n    },\n    \"totals\": {\n        \"set_pieces\": {\n            \"home\": 2,\n            \"away\": 11,\n            \"unknown\": 0\n        },\n        \"cards\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"highlights\": {\n            \"total\": 2,\n            \"by_team\": {\n                \"home\": 1,\n                \"away\": 1,\n                \"unknown\": 0\n            }\n        }\n    }\n}'),
(7, 23, 30, '2026-02-01 16:57:37', '{\n    \"computed_at\": \"2026-02-01 16:57:37\",\n    \"events_version_used\": 30,\n    \"by_type_team\": {\n        \"goal\": {\n            \"home\": 1,\n            \"away\": 2,\n            \"unknown\": 0\n        },\n        \"shot\": {\n            \"home\": 2,\n            \"away\": 10,\n            \"unknown\": 0\n        },\n        \"shot_on_target\": {\n            \"home\": 0,\n            \"away\": 2,\n            \"unknown\": 0\n        },\n        \"shot_off_target\": {\n            \"home\": 1,\n            \"away\": 6,\n            \"unknown\": 0\n        },\n        \"chance\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"corner\": {\n            \"home\": 0,\n            \"away\": 8,\n            \"unknown\": 0\n        },\n        \"free_kick\": {\n            \"home\": 2,\n            \"away\": 3,\n            \"unknown\": 0\n        },\n        \"penalty\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"foul\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"yellow_card\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"red_card\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"mistake\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"good_play\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"highlight\": {\n            \"home\": 1,\n            \"away\": 1,\n            \"unknown\": 0\n        }\n    },\n    \"phase_2\": {\n        \"by_period\": {\n            \"1H\": {\n                \"home\": 5,\n                \"away\": 22,\n                \"unknown\": 2\n            },\n            \"2H\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            },\n            \"ET\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            },\n            \"other\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 1\n            }\n        },\n        \"per_15_minute\": [\n            {\n                \"label\": \"0-15\",\n                \"home\": 1,\n                \"away\": 5,\n                \"unknown\": 1\n            },\n            {\n                \"label\": \"15-30\",\n                \"home\": 2,\n                \"away\": 8,\n                \"unknown\": 0\n            },\n            {\n                \"label\": \"30-45\",\n                \"home\": 2,\n                \"away\": 7,\n                \"unknown\": 0\n            },\n            {\n                \"label\": \"45-60\",\n                \"home\": 0,\n                \"away\": 2,\n                \"unknown\": 2\n            }\n        ],\n        \"importance_weighted\": {\n            \"by_team\": {\n                \"home\": 14,\n                \"away\": 58,\n                \"unknown\": 5\n            },\n            \"by_type_team\": {\n                \"goal\": {\n                    \"home\": 5,\n                    \"away\": 10,\n                    \"unknown\": 0\n                },\n                \"shot\": {\n                    \"home\": 8,\n                    \"away\": 34,\n                    \"unknown\": 0\n                },\n                \"shot_on_target\": {\n                    \"home\": 0,\n                    \"away\": 6,\n                    \"unknown\": 0\n                },\n                \"shot_off_target\": {\n                    \"home\": 3,\n                    \"away\": 18,\n                    \"unknown\": 0\n                },\n                \"chance\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"corner\": {\n                    \"home\": 0,\n                    \"away\": 16,\n                    \"unknown\": 0\n                },\n                \"free_kick\": {\n                    \"home\": 4,\n                    \"away\": 6,\n                    \"unknown\": 0\n                },\n                \"penalty\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"foul\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"yellow_card\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"red_card\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"mistake\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"good_play\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"highlight\": {\n                    \"home\": 2,\n                    \"away\": 2,\n                    \"unknown\": 0\n                }\n            }\n        }\n    },\n    \"totals\": {\n        \"set_pieces\": {\n            \"home\": 2,\n            \"away\": 11,\n            \"unknown\": 0\n        },\n        \"cards\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"highlights\": {\n            \"total\": 2,\n            \"by_team\": {\n                \"home\": 1,\n                \"away\": 1,\n                \"unknown\": 0\n            }\n        }\n    }\n}'),
(8, 23, 53, '2026-02-01 17:18:26', '{\n    \"computed_at\": \"2026-02-01 17:18:26\",\n    \"events_version_used\": 53,\n    \"by_type_team\": {\n        \"goal\": {\n            \"home\": 1,\n            \"away\": 4,\n            \"unknown\": 0\n        },\n        \"shot\": {\n            \"home\": 5,\n            \"away\": 14,\n            \"unknown\": 0\n        },\n        \"shot_on_target\": {\n            \"home\": 1,\n            \"away\": 2,\n            \"unknown\": 0\n        },\n        \"shot_off_target\": {\n            \"home\": 3,\n            \"away\": 8,\n            \"unknown\": 0\n        },\n        \"chance\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"corner\": {\n            \"home\": 0,\n            \"away\": 11,\n            \"unknown\": 0\n        },\n        \"free_kick\": {\n            \"home\": 8,\n            \"away\": 8,\n            \"unknown\": 0\n        },\n        \"penalty\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"foul\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"yellow_card\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"red_card\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"mistake\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"good_play\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"highlight\": {\n            \"home\": 1,\n            \"away\": 1,\n            \"unknown\": 0\n        }\n    },\n    \"phase_2\": {\n        \"by_period\": {\n            \"1H\": {\n                \"home\": 14,\n                \"away\": 34,\n                \"unknown\": 3\n            },\n            \"2H\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            },\n            \"ET\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            },\n            \"other\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 1\n            }\n        },\n        \"per_15_minute\": [\n            {\n                \"label\": \"0-15\",\n                \"home\": 1,\n                \"away\": 5,\n                \"unknown\": 1\n            },\n            {\n                \"label\": \"15-30\",\n                \"home\": 2,\n                \"away\": 8,\n                \"unknown\": 0\n            },\n            {\n                \"label\": \"30-45\",\n                \"home\": 2,\n                \"away\": 7,\n                \"unknown\": 0\n            },\n            {\n                \"label\": \"45-60\",\n                \"home\": 0,\n                \"away\": 2,\n                \"unknown\": 2\n            },\n            {\n                \"label\": \"60-75\",\n                \"home\": 3,\n                \"away\": 5,\n                \"unknown\": 1\n            },\n            {\n                \"label\": \"75-90\",\n                \"home\": 6,\n                \"away\": 7,\n                \"unknown\": 0\n            }\n        ],\n        \"importance_weighted\": {\n            \"by_team\": {\n                \"home\": 35,\n                \"away\": 90,\n                \"unknown\": 6\n            },\n            \"by_type_team\": {\n                \"goal\": {\n                    \"home\": 5,\n                    \"away\": 20,\n                    \"unknown\": 0\n                },\n                \"shot\": {\n                    \"home\": 17,\n                    \"away\": 50,\n                    \"unknown\": 0\n                },\n                \"shot_on_target\": {\n                    \"home\": 3,\n                    \"away\": 6,\n                    \"unknown\": 0\n                },\n                \"shot_off_target\": {\n                    \"home\": 9,\n                    \"away\": 24,\n                    \"unknown\": 0\n                },\n                \"chance\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"corner\": {\n                    \"home\": 0,\n                    \"away\": 22,\n                    \"unknown\": 0\n                },\n                \"free_kick\": {\n                    \"home\": 16,\n                    \"away\": 16,\n                    \"unknown\": 0\n                },\n                \"penalty\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"foul\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"yellow_card\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"red_card\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"mistake\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"good_play\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"highlight\": {\n                    \"home\": 2,\n                    \"away\": 2,\n                    \"unknown\": 0\n                }\n            }\n        }\n    },\n    \"totals\": {\n        \"set_pieces\": {\n            \"home\": 8,\n            \"away\": 19,\n            \"unknown\": 0\n        },\n        \"cards\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"highlights\": {\n            \"total\": 2,\n            \"by_team\": {\n                \"home\": 1,\n                \"away\": 1,\n                \"unknown\": 0\n            }\n        }\n    }\n}');
INSERT INTO `derived_stats` (`id`, `match_id`, `events_version_used`, `computed_at`, `payload_json`) VALUES
(9, 23, 52, '2026-02-01 17:18:26', '{\n    \"computed_at\": \"2026-02-01 17:18:26\",\n    \"events_version_used\": 52,\n    \"by_type_team\": {\n        \"goal\": {\n            \"home\": 1,\n            \"away\": 4,\n            \"unknown\": 0\n        },\n        \"shot\": {\n            \"home\": 5,\n            \"away\": 14,\n            \"unknown\": 0\n        },\n        \"shot_on_target\": {\n            \"home\": 1,\n            \"away\": 2,\n            \"unknown\": 0\n        },\n        \"shot_off_target\": {\n            \"home\": 3,\n            \"away\": 8,\n            \"unknown\": 0\n        },\n        \"chance\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"corner\": {\n            \"home\": 0,\n            \"away\": 11,\n            \"unknown\": 0\n        },\n        \"free_kick\": {\n            \"home\": 8,\n            \"away\": 8,\n            \"unknown\": 0\n        },\n        \"penalty\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"foul\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"yellow_card\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"red_card\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"mistake\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"good_play\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"highlight\": {\n            \"home\": 1,\n            \"away\": 1,\n            \"unknown\": 0\n        }\n    },\n    \"phase_2\": {\n        \"by_period\": {\n            \"1H\": {\n                \"home\": 14,\n                \"away\": 34,\n                \"unknown\": 3\n            },\n            \"2H\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            },\n            \"ET\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            },\n            \"other\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 1\n            }\n        },\n        \"per_15_minute\": [\n            {\n                \"label\": \"0-15\",\n                \"home\": 1,\n                \"away\": 5,\n                \"unknown\": 1\n            },\n            {\n                \"label\": \"15-30\",\n                \"home\": 2,\n                \"away\": 8,\n                \"unknown\": 0\n            },\n            {\n                \"label\": \"30-45\",\n                \"home\": 2,\n                \"away\": 7,\n                \"unknown\": 0\n            },\n            {\n                \"label\": \"45-60\",\n                \"home\": 0,\n                \"away\": 2,\n                \"unknown\": 2\n            },\n            {\n                \"label\": \"60-75\",\n                \"home\": 3,\n                \"away\": 5,\n                \"unknown\": 1\n            },\n            {\n                \"label\": \"75-90\",\n                \"home\": 6,\n                \"away\": 7,\n                \"unknown\": 0\n            }\n        ],\n        \"importance_weighted\": {\n            \"by_team\": {\n                \"home\": 35,\n                \"away\": 90,\n                \"unknown\": 6\n            },\n            \"by_type_team\": {\n                \"goal\": {\n                    \"home\": 5,\n                    \"away\": 20,\n                    \"unknown\": 0\n                },\n                \"shot\": {\n                    \"home\": 17,\n                    \"away\": 50,\n                    \"unknown\": 0\n                },\n                \"shot_on_target\": {\n                    \"home\": 3,\n                    \"away\": 6,\n                    \"unknown\": 0\n                },\n                \"shot_off_target\": {\n                    \"home\": 9,\n                    \"away\": 24,\n                    \"unknown\": 0\n                },\n                \"chance\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"corner\": {\n                    \"home\": 0,\n                    \"away\": 22,\n                    \"unknown\": 0\n                },\n                \"free_kick\": {\n                    \"home\": 16,\n                    \"away\": 16,\n                    \"unknown\": 0\n                },\n                \"penalty\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"foul\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"yellow_card\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"red_card\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"mistake\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"good_play\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"highlight\": {\n                    \"home\": 2,\n                    \"away\": 2,\n                    \"unknown\": 0\n                }\n            }\n        }\n    },\n    \"totals\": {\n        \"set_pieces\": {\n            \"home\": 8,\n            \"away\": 19,\n            \"unknown\": 0\n        },\n        \"cards\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"highlights\": {\n            \"total\": 2,\n            \"by_team\": {\n                \"home\": 1,\n                \"away\": 1,\n                \"unknown\": 0\n            }\n        }\n    }\n}'),
(10, 23, 68, '2026-02-01 17:30:49', '{\n    \"computed_at\": \"2026-02-01 17:30:49\",\n    \"events_version_used\": 68,\n    \"by_type_team\": {\n        \"goal\": {\n            \"home\": 2,\n            \"away\": 6,\n            \"unknown\": 0\n        },\n        \"shot\": {\n            \"home\": 9,\n            \"away\": 19,\n            \"unknown\": 0\n        },\n        \"shot_on_target\": {\n            \"home\": 2,\n            \"away\": 5,\n            \"unknown\": 0\n        },\n        \"shot_off_target\": {\n            \"home\": 5,\n            \"away\": 8,\n            \"unknown\": 0\n        },\n        \"chance\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"corner\": {\n            \"home\": 0,\n            \"away\": 13,\n            \"unknown\": 0\n        },\n        \"free_kick\": {\n            \"home\": 11,\n            \"away\": 9,\n            \"unknown\": 0\n        },\n        \"penalty\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"foul\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"yellow_card\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"red_card\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"mistake\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"good_play\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"highlight\": {\n            \"home\": 1,\n            \"away\": 1,\n            \"unknown\": 0\n        }\n    },\n    \"phase_2\": {\n        \"by_period\": {\n            \"1H\": {\n                \"home\": 21,\n                \"away\": 42,\n                \"unknown\": 3\n            },\n            \"2H\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            },\n            \"ET\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            },\n            \"other\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 1\n            }\n        },\n        \"per_15_minute\": [\n            {\n                \"label\": \"0-15\",\n                \"home\": 1,\n                \"away\": 5,\n                \"unknown\": 1\n            },\n            {\n                \"label\": \"15-30\",\n                \"home\": 2,\n                \"away\": 8,\n                \"unknown\": 0\n            },\n            {\n                \"label\": \"30-45\",\n                \"home\": 2,\n                \"away\": 7,\n                \"unknown\": 0\n            },\n            {\n                \"label\": \"45-60\",\n                \"home\": 0,\n                \"away\": 2,\n                \"unknown\": 2\n            },\n            {\n                \"label\": \"60-75\",\n                \"home\": 3,\n                \"away\": 5,\n                \"unknown\": 1\n            },\n            {\n                \"label\": \"75-90\",\n                \"home\": 6,\n                \"away\": 7,\n                \"unknown\": 0\n            },\n            {\n                \"label\": \"90-105\",\n                \"home\": 7,\n                \"away\": 6,\n                \"unknown\": 0\n            },\n            {\n                \"label\": \"105-120\",\n                \"home\": 0,\n                \"away\": 2,\n                \"unknown\": 0\n            }\n        ],\n        \"importance_weighted\": {\n            \"by_team\": {\n                \"home\": 55,\n                \"away\": 115,\n                \"unknown\": 6\n            },\n            \"by_type_team\": {\n                \"goal\": {\n                    \"home\": 10,\n                    \"away\": 30,\n                    \"unknown\": 0\n                },\n                \"shot\": {\n                    \"home\": 31,\n                    \"away\": 69,\n                    \"unknown\": 0\n                },\n                \"shot_on_target\": {\n                    \"home\": 6,\n                    \"away\": 15,\n                    \"unknown\": 0\n                },\n                \"shot_off_target\": {\n                    \"home\": 15,\n                    \"away\": 24,\n                    \"unknown\": 0\n                },\n                \"chance\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"corner\": {\n                    \"home\": 0,\n                    \"away\": 26,\n                    \"unknown\": 0\n                },\n                \"free_kick\": {\n                    \"home\": 22,\n                    \"away\": 18,\n                    \"unknown\": 0\n                },\n                \"penalty\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"foul\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"yellow_card\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"red_card\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"mistake\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"good_play\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"highlight\": {\n                    \"home\": 2,\n                    \"away\": 2,\n                    \"unknown\": 0\n                }\n            }\n        }\n    },\n    \"totals\": {\n        \"set_pieces\": {\n            \"home\": 11,\n            \"away\": 22,\n            \"unknown\": 0\n        },\n        \"cards\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"highlights\": {\n            \"total\": 2,\n            \"by_team\": {\n                \"home\": 1,\n                \"away\": 1,\n                \"unknown\": 0\n            }\n        }\n    }\n}'),
(11, 23, 67, '2026-02-01 17:30:49', '{\n    \"computed_at\": \"2026-02-01 17:30:49\",\n    \"events_version_used\": 67,\n    \"by_type_team\": {\n        \"goal\": {\n            \"home\": 2,\n            \"away\": 6,\n            \"unknown\": 0\n        },\n        \"shot\": {\n            \"home\": 9,\n            \"away\": 19,\n            \"unknown\": 0\n        },\n        \"shot_on_target\": {\n            \"home\": 2,\n            \"away\": 5,\n            \"unknown\": 0\n        },\n        \"shot_off_target\": {\n            \"home\": 5,\n            \"away\": 8,\n            \"unknown\": 0\n        },\n        \"chance\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"corner\": {\n            \"home\": 0,\n            \"away\": 13,\n            \"unknown\": 0\n        },\n        \"free_kick\": {\n            \"home\": 11,\n            \"away\": 9,\n            \"unknown\": 0\n        },\n        \"penalty\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"foul\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"yellow_card\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"red_card\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"mistake\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"good_play\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"highlight\": {\n            \"home\": 1,\n            \"away\": 1,\n            \"unknown\": 0\n        }\n    },\n    \"phase_2\": {\n        \"by_period\": {\n            \"1H\": {\n                \"home\": 21,\n                \"away\": 42,\n                \"unknown\": 3\n            },\n            \"2H\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            },\n            \"ET\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            },\n            \"other\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 1\n            }\n        },\n        \"per_15_minute\": [\n            {\n                \"label\": \"0-15\",\n                \"home\": 1,\n                \"away\": 5,\n                \"unknown\": 1\n            },\n            {\n                \"label\": \"15-30\",\n                \"home\": 2,\n                \"away\": 8,\n                \"unknown\": 0\n            },\n            {\n                \"label\": \"30-45\",\n                \"home\": 2,\n                \"away\": 7,\n                \"unknown\": 0\n            },\n            {\n                \"label\": \"45-60\",\n                \"home\": 0,\n                \"away\": 2,\n                \"unknown\": 2\n            },\n            {\n                \"label\": \"60-75\",\n                \"home\": 3,\n                \"away\": 5,\n                \"unknown\": 1\n            },\n            {\n                \"label\": \"75-90\",\n                \"home\": 6,\n                \"away\": 7,\n                \"unknown\": 0\n            },\n            {\n                \"label\": \"90-105\",\n                \"home\": 7,\n                \"away\": 6,\n                \"unknown\": 0\n            },\n            {\n                \"label\": \"105-120\",\n                \"home\": 0,\n                \"away\": 2,\n                \"unknown\": 0\n            }\n        ],\n        \"importance_weighted\": {\n            \"by_team\": {\n                \"home\": 55,\n                \"away\": 115,\n                \"unknown\": 6\n            },\n            \"by_type_team\": {\n                \"goal\": {\n                    \"home\": 10,\n                    \"away\": 30,\n                    \"unknown\": 0\n                },\n                \"shot\": {\n                    \"home\": 31,\n                    \"away\": 69,\n                    \"unknown\": 0\n                },\n                \"shot_on_target\": {\n                    \"home\": 6,\n                    \"away\": 15,\n                    \"unknown\": 0\n                },\n                \"shot_off_target\": {\n                    \"home\": 15,\n                    \"away\": 24,\n                    \"unknown\": 0\n                },\n                \"chance\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"corner\": {\n                    \"home\": 0,\n                    \"away\": 26,\n                    \"unknown\": 0\n                },\n                \"free_kick\": {\n                    \"home\": 22,\n                    \"away\": 18,\n                    \"unknown\": 0\n                },\n                \"penalty\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"foul\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"yellow_card\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"red_card\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"mistake\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"good_play\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"highlight\": {\n                    \"home\": 2,\n                    \"away\": 2,\n                    \"unknown\": 0\n                }\n            }\n        }\n    },\n    \"totals\": {\n        \"set_pieces\": {\n            \"home\": 11,\n            \"away\": 22,\n            \"unknown\": 0\n        },\n        \"cards\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"highlights\": {\n            \"total\": 2,\n            \"by_team\": {\n                \"home\": 1,\n                \"away\": 1,\n                \"unknown\": 0\n            }\n        }\n    }\n}');

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

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `match_id`, `period_id`, `match_second`, `minute`, `minute_extra`, `team_side`, `event_type_id`, `importance`, `phase`, `is_penalty`, `match_player_id`, `player_id`, `opponent_detail`, `outcome`, `zone`, `notes`, `created_by`, `created_at`, `updated_by`, `updated_at`, `match_period_id`, `clip_id`, `clip_start_second`, `clip_end_second`) VALUES
(258, 19, 7, 14, 0, 0, 'unknown', 13, 1, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, 'First Half', 1, '2026-01-17 20:45:26', NULL, '2026-01-23 09:38:46', NULL, NULL, NULL, NULL),
(259, 19, 5, 188, 3, 0, 'home', 15, 3, 'unknown', 0, 69, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-01-17 21:08:40', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(260, 19, 5, 264, 4, 0, 'home', 15, 3, 'unknown', 0, 68, NULL, NULL, 'on_target', NULL, NULL, 1, '2026-01-17 21:09:25', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(263, 19, 5, 297, 4, 0, 'home', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 21:10:59', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(264, 19, 5, 309, 5, 0, 'home', 15, 3, 'unknown', 0, 62, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-01-17 21:12:30', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(265, 19, 5, 570, 9, 0, 'home', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 21:18:39', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(266, 19, 5, 670, 11, 0, 'away', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 21:20:55', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(267, 19, 5, 824, 13, 0, 'home', 4, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 21:23:33', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(269, 19, 5, 999, 16, 0, 'away', 4, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 21:27:59', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(270, 19, 5, 1158, 19, 0, 'away', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 21:30:54', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(271, 19, 5, 1303, 21, 0, 'away', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 21:33:44', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(272, 19, 5, 1332, 22, 0, 'away', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 21:40:06', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(273, 19, 5, 1464, 24, 0, 'home', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 21:42:38', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(274, 19, 5, 1516, 25, 0, 'home', 15, 3, 'unknown', 0, 69, NULL, NULL, 'on_target', NULL, NULL, 1, '2026-01-17 21:43:37', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(275, 19, 5, 1516, 25, 0, 'home', 4, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 21:43:41', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(276, 19, 5, 1536, 25, 0, 'home', 15, 3, 'unknown', 0, 60, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-01-17 21:44:13', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(277, 19, 5, 1612, 26, 0, 'home', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 21:45:30', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(278, 19, 5, 1684, 28, 0, 'away', 15, 3, 'unknown', 0, NULL, NULL, NULL, 'on_target', NULL, NULL, 1, '2026-01-17 21:47:13', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(279, 19, 5, 1730, 28, 0, 'home', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 21:48:20', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(280, 19, 5, 1771, 29, 0, 'home', 15, 3, 'unknown', 0, 62, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-01-17 21:52:40', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(281, 19, 5, 1994, 33, 0, 'away', 15, 3, 'unknown', 0, NULL, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-01-17 21:56:25', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(282, 19, 5, 2134, 35, 0, 'home', 16, 5, 'unknown', 0, 66, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 21:59:00', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(283, 19, 5, 363, 6, 0, 'away', 15, 3, 'unknown', 0, NULL, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-01-17 22:06:10', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(285, 19, 5, 2312, 38, 0, 'home', 15, 3, 'unknown', 0, 64, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-01-17 22:15:53', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(286, 19, 5, 2392, 39, 0, 'home', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 22:17:13', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(287, 19, 7, 2398, 39, 0, 'away', 8, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 22:17:20', NULL, '2026-01-19 23:15:58', NULL, NULL, NULL, NULL),
(288, 19, 5, 2552, 42, 0, 'home', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 22:21:04', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(289, 19, 5, 2661, 44, 0, 'home', 16, 5, 'unknown', 0, 68, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 22:22:55', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(290, 19, 7, 2710, 45, 0, 'unknown', 14, 1, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, 'First Half', 1, '2026-01-17 22:23:51', NULL, '2026-01-23 09:39:51', NULL, NULL, NULL, NULL),
(291, 19, 8, 3526, 58, 14, 'unknown', 13, 1, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, 'Second Half', 1, '2026-01-17 22:26:56', NULL, '2026-01-23 09:39:55', NULL, NULL, NULL, NULL),
(292, 19, 5, 3617, 60, 16, 'home', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 22:28:30', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(293, 19, 5, 3663, 61, 16, 'home', 15, 3, 'unknown', 0, 65, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-01-17 22:29:46', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(294, 19, 5, 3922, 65, 21, 'home', 15, 3, 'unknown', 0, 69, NULL, NULL, 'on_target', NULL, NULL, 1, '2026-01-17 22:46:46', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(295, 19, 5, 3979, 66, 22, 'home', 15, 3, 'unknown', 0, 60, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-01-17 22:47:47', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(296, 19, 5, 4193, 69, 25, 'home', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 22:51:21', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(297, 19, 5, 4287, 71, 27, 'home', 15, 3, 'unknown', 0, 65, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-01-17 22:52:58', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(298, 19, 5, 4447, 74, 29, 'away', 15, 3, 'unknown', 0, NULL, NULL, NULL, 'on_target', NULL, NULL, 1, '2026-01-17 22:55:41', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(299, 19, 5, 4521, 75, 31, 'away', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 22:56:55', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(300, 19, 5, 4623, 77, 32, 'home', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 22:58:37', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(301, 19, 5, 4669, 77, 33, 'home', 15, 3, 'unknown', 0, 65, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-01-17 22:59:37', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(302, 19, 5, 4806, 80, 35, 'home', 12, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 23:02:09', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(303, 19, 5, 4982, 83, 38, 'home', 4, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 23:05:05', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(304, 19, 5, 5013, 83, 39, 'home', 4, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 23:05:36', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(305, 19, 5, 5071, 84, 40, 'home', 15, 3, 'unknown', 0, 64, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-01-17 23:06:40', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(306, 19, 5, 5125, 85, 41, 'away', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 23:08:10', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(307, 19, 5, 5218, 86, 42, 'home', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 23:09:58', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(308, 19, 7, 5223, 87, 42, 'home', 8, 2, 'unknown', 0, 60, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 23:12:30', NULL, '2026-01-20 08:28:30', NULL, NULL, NULL, NULL),
(309, 19, 5, 5331, 88, 44, 'home', 15, 3, 'unknown', 0, 68, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-01-17 23:18:10', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(310, 19, 5, 5396, 89, 45, 'home', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 23:19:15', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(311, 19, 5, 5426, 90, 46, 'home', 15, 3, 'unknown', 0, 65, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-01-17 23:19:48', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(312, 19, 5, 5446, 90, 46, 'home', 16, 5, 'unknown', 0, 60, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 23:20:10', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(313, 19, 5, 5570, 92, 48, 'home', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 23:22:09', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(314, 19, 5, 5663, 94, 50, 'home', 15, 3, 'unknown', 0, 62, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-01-17 23:23:45', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(315, 19, 5, 5691, 94, 50, 'home', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 23:24:13', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(316, 19, 5, 5777, 96, 52, 'home', 4, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 23:25:43', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(317, 19, 5, 5442, 90, 46, 'home', 4, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 23:26:09', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(318, 19, 5, 5801, 96, 52, 'home', 15, 3, 'unknown', 0, 70, NULL, NULL, 'on_target', NULL, NULL, 1, '2026-01-17 23:26:55', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(319, 19, 5, 5839, 97, 53, 'away', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 23:27:33', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(320, 19, 5, 6006, 100, 55, 'home', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 23:30:21', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(321, 19, 5, 6033, 100, 56, 'home', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 23:30:55', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(322, 19, 5, 6081, 101, 57, 'home', 16, 5, 'unknown', 0, 65, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 23:31:47', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(323, 19, 5, 6180, 103, 58, 'home', 15, 3, 'unknown', 0, 68, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-01-17 23:33:24', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(325, 19, 8, 6241, 104, 59, 'unknown', 14, 1, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, 'Second Half', 1, '2026-01-17 23:37:21', NULL, '2026-01-23 09:42:29', NULL, NULL, NULL, NULL),
(402, 1, NULL, 0, 0, 0, 'home', 16, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 18:06:55', NULL, NULL, NULL, NULL, NULL, NULL),
(403, 1, NULL, 0, 29, 0, 'away', 16, 3, 'unknown', 0, 101, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 18:06:55', NULL, '2026-01-21 18:18:17', NULL, NULL, NULL, NULL),
(404, 1, NULL, 0, 58, 0, 'away', 16, 3, 'unknown', 0, 101, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 18:06:55', NULL, '2026-01-21 18:31:06', NULL, NULL, NULL, NULL),
(405, 1, NULL, 0, 75, 0, 'away', 16, 3, 'unknown', 0, 101, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 18:06:55', NULL, '2026-01-21 18:31:25', NULL, NULL, NULL, NULL),
(406, 1, NULL, 0, 60, 0, 'away', 16, 3, 'unknown', 0, 102, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 18:06:55', NULL, '2026-01-21 18:31:19', NULL, NULL, NULL, NULL),
(407, 2, NULL, 0, 44, 0, 'home', 16, 3, 'unknown', 0, 117, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 18:06:55', NULL, '2026-01-21 19:19:21', NULL, NULL, NULL, NULL),
(408, 2, NULL, 0, 45, 0, 'home', 16, 3, 'unknown', 0, 117, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 18:06:55', NULL, '2026-01-21 19:19:30', NULL, NULL, NULL, NULL),
(409, 2, NULL, 0, 66, 0, 'home', 16, 3, 'unknown', 0, 117, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 18:06:55', NULL, '2026-01-21 19:19:39', NULL, NULL, NULL, NULL),
(410, 2, NULL, 0, 69, 0, 'home', 16, 3, 'unknown', 0, 117, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 18:06:55', NULL, '2026-01-21 19:19:49', NULL, NULL, NULL, NULL),
(411, 2, NULL, 0, 75, 0, 'home', 16, 3, 'unknown', 0, NULL, NULL, NULL, 'own_goal', NULL, NULL, 1, '2026-01-19 18:06:55', NULL, '2026-01-21 20:00:25', NULL, NULL, NULL, NULL),
(412, 2, NULL, 0, 57, 0, 'away', 16, 3, 'unknown', 0, 114, NULL, NULL, 'own_goal', NULL, NULL, 1, '2026-01-19 18:06:55', NULL, '2026-01-21 19:33:41', NULL, NULL, NULL, NULL),
(413, 3, NULL, 0, 0, 0, 'home', 16, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 18:06:55', NULL, NULL, NULL, NULL, NULL, NULL),
(414, 3, NULL, 0, 0, 0, 'home', 16, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 18:06:55', NULL, NULL, NULL, NULL, NULL, NULL),
(415, 3, NULL, 0, 0, 0, 'home', 16, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 18:06:55', NULL, NULL, NULL, NULL, NULL, NULL),
(416, 3, NULL, 0, 0, 0, 'home', 16, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 18:06:55', NULL, NULL, NULL, NULL, NULL, NULL),
(417, 4, NULL, 0, 0, 0, 'away', 16, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 18:06:55', NULL, NULL, NULL, NULL, NULL, NULL),
(418, 4, NULL, 0, 0, 0, 'away', 16, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 18:06:55', NULL, NULL, NULL, NULL, NULL, NULL),
(419, 5, NULL, 0, 0, 0, 'home', 16, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 18:06:55', NULL, NULL, NULL, NULL, NULL, NULL),
(420, 5, NULL, 0, 0, 0, 'home', 16, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 18:06:55', NULL, NULL, NULL, NULL, NULL, NULL),
(421, 5, NULL, 0, 0, 0, 'home', 16, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 18:06:55', NULL, NULL, NULL, NULL, NULL, NULL),
(422, 5, NULL, 0, 0, 0, 'home', 16, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 18:06:55', NULL, NULL, NULL, NULL, NULL, NULL),
(423, 5, NULL, 0, 0, 0, 'home', 16, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 18:06:55', NULL, NULL, NULL, NULL, NULL, NULL),
(424, 5, NULL, 0, 39, 0, 'away', 16, 3, 'unknown', 0, 175, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 18:06:55', NULL, '2026-01-21 21:02:05', NULL, NULL, NULL, NULL),
(425, 6, NULL, 0, 0, 0, 'away', 16, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 18:06:55', NULL, NULL, NULL, NULL, NULL, NULL),
(426, 7, NULL, 0, 0, 0, 'home', 16, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 18:06:55', NULL, NULL, NULL, NULL, NULL, NULL),
(427, 7, NULL, 0, 0, 0, 'home', 16, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 18:06:55', NULL, NULL, NULL, NULL, NULL, NULL),
(428, 7, NULL, 0, 73, 0, 'away', 16, 3, 'unknown', 0, 215, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 18:06:55', NULL, '2026-01-21 21:15:21', NULL, NULL, NULL, NULL),
(429, 8, NULL, 0, 0, 0, 'home', 16, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 18:06:55', NULL, NULL, NULL, NULL, NULL, NULL),
(430, 8, NULL, 0, 0, 0, 'home', 16, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 18:06:55', NULL, NULL, NULL, NULL, NULL, NULL),
(431, 9, NULL, 0, 0, 0, 'home', 16, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 18:06:55', NULL, NULL, NULL, NULL, NULL, NULL),
(432, 9, NULL, 0, 29, 0, 'away', 16, 3, 'unknown', 0, 245, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 18:06:55', NULL, '2026-01-21 21:27:22', NULL, NULL, NULL, NULL),
(433, 9, NULL, 0, 29, 0, 'away', 16, 3, 'unknown', 0, 241, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 18:06:55', NULL, '2026-01-21 21:27:33', NULL, NULL, NULL, NULL),
(434, 10, NULL, 0, 42, 0, 'home', 16, 3, 'unknown', 0, 264, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 18:06:55', NULL, '2026-01-22 07:01:46', NULL, NULL, NULL, NULL),
(435, 10, NULL, 0, 0, 0, 'away', 16, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 18:06:55', NULL, NULL, NULL, NULL, NULL, NULL),
(436, 10, NULL, 0, 0, 0, 'away', 16, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 18:06:55', NULL, NULL, NULL, NULL, NULL, NULL),
(437, 10, NULL, 0, 0, 0, 'away', 16, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 18:06:55', NULL, NULL, NULL, NULL, NULL, NULL),
(438, 11, NULL, 0, 39, 0, 'home', 16, 3, 'unknown', 0, 285, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 18:06:55', NULL, '2026-01-22 07:53:07', NULL, NULL, NULL, NULL),
(439, 11, NULL, 0, 24, 0, 'away', 16, 3, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 18:06:55', NULL, '2026-01-22 07:53:24', NULL, NULL, NULL, NULL),
(440, 11, NULL, 0, 47, 0, 'away', 16, 3, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 18:06:55', NULL, '2026-01-22 07:53:32', NULL, NULL, NULL, NULL),
(441, 11, NULL, 0, 61, 0, 'away', 16, 3, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 18:06:55', NULL, '2026-01-22 07:53:39', NULL, NULL, NULL, NULL),
(442, 13, NULL, 0, 0, 0, 'home', 16, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 18:06:55', NULL, NULL, NULL, NULL, NULL, NULL),
(443, 13, NULL, 0, 0, 0, 'home', 16, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 18:06:55', NULL, NULL, NULL, NULL, NULL, NULL),
(444, 13, NULL, 0, 0, 0, 'home', 16, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 18:06:55', NULL, NULL, NULL, NULL, NULL, NULL),
(445, 13, NULL, 0, 0, 0, 'home', 16, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 18:06:55', NULL, NULL, NULL, NULL, NULL, NULL),
(446, 13, NULL, 0, 0, 0, 'home', 16, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 18:06:55', NULL, NULL, NULL, NULL, NULL, NULL),
(447, 13, NULL, 0, 92, 0, 'away', 16, 3, 'unknown', 0, 325, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 18:06:55', NULL, '2026-01-22 08:25:08', NULL, NULL, NULL, NULL),
(448, 14, NULL, 0, 1, 0, 'home', 16, 3, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 18:06:55', NULL, '2026-01-22 08:36:13', NULL, NULL, NULL, NULL),
(449, 14, NULL, 0, 11, 0, 'away', 16, 3, 'unknown', 0, 338, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 18:06:55', NULL, '2026-01-22 08:36:29', NULL, NULL, NULL, NULL),
(450, 14, NULL, 0, 11, 0, 'away', 16, 3, 'unknown', 0, 338, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 18:06:55', NULL, '2026-01-22 08:36:38', NULL, NULL, NULL, NULL),
(451, 15, NULL, 0, 0, 0, 'home', 16, 3, 'unknown', 0, 408, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 18:06:55', NULL, '2026-01-23 11:34:43', NULL, NULL, NULL, NULL),
(452, 15, NULL, 0, 0, 0, 'home', 16, 3, 'unknown', 0, 405, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 18:06:55', NULL, '2026-01-23 11:34:56', NULL, NULL, NULL, NULL),
(453, 15, NULL, 0, 0, 0, 'away', 16, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 18:06:55', NULL, NULL, NULL, NULL, NULL, NULL),
(454, 16, NULL, 0, 0, 0, 'away', 16, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 18:06:55', NULL, NULL, NULL, NULL, NULL, NULL),
(462, 18, NULL, 0, 0, 0, 'home', 16, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 18:06:55', NULL, NULL, NULL, NULL, NULL, NULL),
(463, 18, NULL, 0, 0, 0, 'away', 16, 3, 'unknown', 0, 353, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 18:06:55', NULL, '2026-01-23 11:16:47', NULL, NULL, NULL, NULL),
(464, 18, NULL, 0, 0, 0, 'away', 16, 3, 'unknown', 0, 351, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 18:06:55', NULL, '2026-01-23 11:17:01', NULL, NULL, NULL, NULL),
(465, 18, NULL, 0, 0, 0, 'away', 16, 3, 'unknown', 0, 354, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 18:06:55', NULL, '2026-01-23 11:17:15', NULL, NULL, NULL, NULL),
(466, 18, NULL, 0, 0, 0, 'away', 16, 3, 'unknown', 0, 352, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-19 18:06:55', NULL, '2026-01-23 11:25:44', NULL, NULL, NULL, NULL),
(468, 19, 7, 3672, 61, 17, 'home', 8, 2, 'unknown', 0, 64, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-20 08:29:29', NULL, NULL, NULL, NULL, NULL, NULL),
(472, 1, NULL, 0, 5, 0, 'away', 8, 3, 'unknown', 0, 95, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-21 18:51:57', NULL, NULL, NULL, NULL, NULL, NULL),
(473, 1, NULL, 0, 58, 0, 'away', 8, 3, 'unknown', 0, 99, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-21 18:52:08', NULL, NULL, NULL, NULL, NULL, NULL),
(474, 1, NULL, 0, 61, 0, 'away', 8, 3, 'unknown', 0, 94, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-21 18:52:16', NULL, NULL, NULL, NULL, NULL, NULL),
(478, 2, NULL, 0, 51, 0, 'home', 8, 3, 'unknown', 0, 114, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-21 19:34:37', NULL, NULL, NULL, NULL, NULL, NULL),
(479, 3, NULL, 0, 78, 0, 'away', 8, 3, 'unknown', 0, 142, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-21 20:15:50', NULL, NULL, NULL, NULL, NULL, NULL),
(480, 4, NULL, 0, 69, 0, 'home', 8, 3, 'unknown', 0, 152, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-21 20:23:37', NULL, NULL, NULL, NULL, NULL, NULL),
(488, 5, NULL, 0, 41, 0, 'away', 8, 3, 'unknown', 0, 166, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-21 21:02:26', NULL, NULL, NULL, NULL, NULL, NULL),
(489, 5, NULL, 0, 43, 0, 'away', 8, 3, 'unknown', 0, 175, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-21 21:02:37', NULL, NULL, NULL, NULL, NULL, NULL),
(490, 5, NULL, 0, 53, 0, 'away', 8, 3, 'unknown', 0, 171, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-21 21:02:46', NULL, NULL, NULL, NULL, NULL, NULL),
(491, 6, NULL, 0, 39, 0, 'home', 8, 3, 'unknown', 0, 186, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-21 21:09:24', NULL, NULL, NULL, NULL, NULL, NULL),
(492, 6, NULL, 0, 42, 0, 'home', 8, 3, 'unknown', 0, 184, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-21 21:09:34', NULL, NULL, NULL, NULL, NULL, NULL),
(493, 6, NULL, 0, 92, 0, 'home', 8, 3, 'unknown', 0, 189, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-21 21:09:44', NULL, NULL, NULL, NULL, NULL, NULL),
(494, 7, NULL, 0, 44, 0, 'away', 8, 3, 'unknown', 0, 207, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-21 21:15:45', NULL, NULL, NULL, NULL, NULL, NULL),
(495, 7, NULL, 0, 90, 0, 'away', 8, 3, 'unknown', 0, 206, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-21 21:15:56', NULL, NULL, NULL, NULL, NULL, NULL),
(496, 7, NULL, 0, 93, 0, 'away', 8, 3, 'unknown', 0, 217, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-21 21:16:02', NULL, NULL, NULL, NULL, NULL, NULL),
(497, 8, NULL, 0, 78, 0, 'away', 8, 3, 'unknown', 0, 226, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-21 21:22:46', NULL, NULL, NULL, NULL, NULL, NULL),
(498, 8, NULL, 0, 94, 0, 'away', 8, 3, 'unknown', 0, 228, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-21 21:22:53', NULL, NULL, NULL, NULL, NULL, NULL),
(499, 9, NULL, 0, 90, 0, 'away', 8, 3, 'unknown', 0, 245, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-21 21:27:47', NULL, NULL, NULL, NULL, NULL, NULL),
(500, 9, NULL, 0, 92, 0, 'away', 8, 3, 'unknown', 0, 241, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-21 21:27:55', NULL, NULL, NULL, NULL, NULL, NULL),
(501, 10, NULL, 0, 10, 0, 'home', 8, 3, 'unknown', 0, 266, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-22 07:09:53', NULL, NULL, NULL, NULL, NULL, NULL),
(502, 10, NULL, 0, 32, 0, 'home', 8, 3, 'unknown', 0, 256, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-22 07:10:02', NULL, NULL, NULL, NULL, NULL, NULL),
(503, 10, NULL, 0, 54, 0, 'home', 8, 3, 'unknown', 0, 265, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-22 07:10:12', NULL, NULL, NULL, NULL, NULL, NULL),
(504, 10, NULL, 0, 56, 0, 'home', 8, 3, 'unknown', 0, 264, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-22 07:10:21', NULL, NULL, NULL, NULL, NULL, NULL),
(505, 10, NULL, 0, 83, 0, 'home', 8, 3, 'unknown', 0, 256, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-22 07:10:30', NULL, NULL, NULL, NULL, NULL, NULL),
(506, 10, NULL, 0, 83, 0, 'home', 9, 3, 'unknown', 0, 256, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-22 07:10:30', NULL, NULL, NULL, NULL, NULL, NULL),
(507, 11, NULL, 0, 29, 0, 'home', 8, 3, 'unknown', 0, 284, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-22 07:53:52', NULL, NULL, NULL, NULL, NULL, NULL),
(508, 11, NULL, 0, 43, 0, 'home', 8, 3, 'unknown', 0, 282, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-22 07:53:59', NULL, NULL, NULL, NULL, NULL, NULL),
(509, 11, NULL, 0, 66, 0, 'home', 8, 3, 'unknown', 0, 283, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-22 07:54:05', NULL, NULL, NULL, NULL, NULL, NULL),
(510, 12, NULL, 0, 50, 0, 'away', 8, 3, 'unknown', 0, 302, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-22 08:06:04', NULL, NULL, NULL, NULL, NULL, NULL),
(511, 12, NULL, 0, 58, 0, 'away', 8, 3, 'unknown', 0, 294, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-22 08:06:13', NULL, NULL, NULL, NULL, NULL, NULL),
(512, 13, NULL, 0, 29, 0, 'away', 8, 3, 'unknown', 0, 318, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-22 08:29:17', NULL, NULL, NULL, NULL, NULL, NULL),
(513, 13, NULL, 0, 54, 0, 'away', 8, 3, 'unknown', 0, 323, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-22 08:29:28', NULL, NULL, NULL, NULL, NULL, NULL),
(514, 14, NULL, 0, 63, 0, 'away', 8, 3, 'unknown', 0, 337, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-22 08:36:52', NULL, NULL, NULL, NULL, NULL, NULL),
(515, 14, NULL, 0, 71, 0, 'away', 8, 3, 'unknown', 0, 333, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-22 08:37:03', NULL, NULL, NULL, NULL, NULL, NULL),
(516, 17, NULL, 0, 0, 0, 'home', 16, 3, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-22 08:58:43', NULL, NULL, NULL, NULL, NULL, NULL),
(517, 17, NULL, 0, 0, 0, 'home', 16, 3, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-22 08:58:52', NULL, NULL, NULL, NULL, NULL, NULL),
(518, 17, NULL, 0, 0, 0, 'home', 16, 3, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-22 08:59:00', NULL, NULL, NULL, NULL, NULL, NULL),
(519, 17, NULL, 0, 0, 0, 'away', 16, 3, 'unknown', 0, 379, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-22 08:59:08', NULL, '2026-01-23 11:29:52', NULL, NULL, NULL, NULL),
(520, 17, NULL, 0, 0, 0, 'away', 16, 3, 'unknown', 0, 375, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-22 08:59:15', NULL, '2026-01-23 11:30:03', NULL, NULL, NULL, NULL),
(521, 17, NULL, 0, 0, 0, 'away', 16, 3, 'unknown', 0, 372, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-22 08:59:30', NULL, '2026-01-23 11:34:07', NULL, NULL, NULL, NULL),
(522, 17, NULL, 0, 0, 0, 'away', 16, 3, 'unknown', 0, 374, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-22 08:59:40', NULL, '2026-01-23 11:34:16', NULL, NULL, NULL, NULL),
(524, 22, 9, 82, 1, 0, 'unknown', 13, 1, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, 'First Half', 1, '2026-01-25 15:01:41', NULL, NULL, NULL, NULL, NULL, NULL),
(525, 22, 9, 247, 4, 0, 'home', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-25 15:08:58', NULL, NULL, NULL, NULL, NULL, NULL),
(526, 22, 9, 309, 5, 0, 'away', 15, 3, 'unknown', 0, 438, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-01-25 15:10:12', NULL, NULL, NULL, NULL, NULL, NULL),
(527, 22, 9, 386, 6, 0, 'away', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-25 15:11:33', NULL, NULL, NULL, NULL, NULL, NULL),
(528, 22, 9, 436, 7, 0, 'away', 15, 3, 'unknown', 0, 442, NULL, NULL, 'on_target', NULL, NULL, 1, '2026-01-25 15:12:05', NULL, NULL, NULL, NULL, NULL, NULL),
(529, 22, 9, 544, 9, 0, 'away', 16, 5, 'unknown', 0, 443, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-25 15:13:59', NULL, NULL, NULL, NULL, NULL, NULL),
(530, 22, 9, 644, 10, 0, 'away', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-25 15:15:28', NULL, NULL, NULL, NULL, NULL, NULL),
(531, 22, 9, 725, 12, 0, 'home', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-25 15:16:35', NULL, NULL, NULL, NULL, NULL, NULL),
(532, 22, 9, 764, 12, 0, 'home', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-25 15:17:04', NULL, NULL, NULL, NULL, NULL, NULL),
(533, 22, 9, 835, 13, 0, 'home', 12, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-25 15:18:16', NULL, NULL, NULL, NULL, NULL, NULL),
(534, 22, 9, 901, 15, 0, 'away', 15, 3, 'unknown', 0, 438, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-01-25 15:19:28', NULL, NULL, NULL, NULL, NULL, NULL),
(535, 22, 9, 989, 16, 0, 'away', 15, 3, 'unknown', 0, 444, NULL, NULL, 'on_target', NULL, NULL, 1, '2026-01-25 15:21:14', NULL, NULL, NULL, NULL, NULL, NULL),
(536, 22, 9, 999, 16, 0, 'away', 4, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-25 15:21:26', NULL, NULL, NULL, NULL, NULL, NULL),
(537, 22, 9, 1101, 18, 0, 'home', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-25 15:22:59', NULL, NULL, NULL, NULL, NULL, NULL),
(538, 22, 9, 1153, 19, 0, 'home', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-25 15:23:43', NULL, NULL, NULL, NULL, NULL, NULL),
(539, 22, 9, 1266, 21, 0, 'home', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-25 15:25:35', NULL, NULL, NULL, NULL, NULL, NULL),
(540, 22, 9, 1277, 21, 0, 'away', 8, 2, 'unknown', 0, 435, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-25 15:25:51', NULL, NULL, NULL, NULL, NULL, NULL),
(541, 22, 9, 1332, 22, 0, 'home', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-25 15:26:37', NULL, NULL, NULL, NULL, NULL, NULL),
(542, 22, 9, 1419, 23, 0, 'away', 16, 5, 'unknown', 0, 438, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-25 15:28:10', NULL, NULL, NULL, NULL, NULL, NULL),
(543, 22, 9, 1570, 26, 0, 'away', 15, 3, 'unknown', 0, 442, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-01-25 15:30:15', NULL, NULL, NULL, NULL, NULL, NULL),
(544, 22, 9, 1620, 27, 0, 'away', 15, 3, 'unknown', 0, 443, NULL, NULL, 'on_target', NULL, NULL, 1, '2026-01-25 15:30:40', NULL, NULL, NULL, NULL, NULL, NULL),
(545, 22, 9, 1614, 26, 0, 'away', 3, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-25 15:30:49', NULL, NULL, NULL, NULL, NULL, NULL),
(546, 22, 9, 1787, 29, 0, 'home', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-25 15:33:13', NULL, NULL, NULL, NULL, NULL, NULL),
(547, 22, 9, 2016, 33, 0, 'away', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-25 15:36:39', NULL, NULL, NULL, NULL, NULL, NULL),
(548, 22, 9, 2080, 34, 0, 'away', 16, 5, 'unknown', 0, 435, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-25 15:37:18', NULL, NULL, NULL, NULL, NULL, NULL),
(549, 22, 9, 2078, 34, 0, 'away', 15, 3, 'unknown', 0, 442, NULL, NULL, 'on_target', NULL, NULL, 1, '2026-01-25 15:37:29', NULL, NULL, NULL, NULL, NULL, NULL),
(550, 22, 9, 2432, 40, 0, 'away', 15, 3, 'unknown', 0, 440, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-01-25 15:41:25', NULL, NULL, NULL, NULL, NULL, NULL),
(551, 22, 9, 2439, 40, 0, 'away', 4, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-25 15:41:32', NULL, NULL, NULL, NULL, NULL, NULL),
(552, 22, 9, 2477, 41, 0, 'away', 16, 5, 'unknown', 0, 437, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-25 15:42:00', NULL, NULL, NULL, NULL, NULL, NULL),
(553, 22, 9, 2587, 43, 0, 'home', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-25 15:43:16', NULL, NULL, NULL, NULL, NULL, NULL),
(554, 22, 9, 2805, 46, 0, 'away', 15, 3, 'unknown', 0, 442, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-01-25 15:46:09', NULL, NULL, NULL, NULL, NULL, NULL),
(555, 22, 9, 2811, 46, 0, 'unknown', 14, 1, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, 'First Half', 1, '2026-01-25 15:46:15', NULL, NULL, NULL, NULL, NULL, NULL),
(557, 22, 11, 3719, 61, 16, 'home', 15, 3, 'unknown', 0, NULL, NULL, NULL, 'on_target', NULL, NULL, 1, '2026-01-25 15:47:20', NULL, '2026-01-26 11:51:54', NULL, NULL, NULL, NULL),
(558, 22, 11, 3809, 63, 17, 'home', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-25 15:48:31', NULL, '2026-01-26 11:51:54', NULL, NULL, NULL, NULL),
(559, 22, 11, 3820, 63, 17, 'home', 8, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-25 15:48:43', NULL, '2026-01-26 11:51:54', NULL, NULL, NULL, NULL),
(560, 22, 11, 4139, 68, 23, 'away', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-25 15:51:44', NULL, '2026-01-26 11:51:54', NULL, NULL, NULL, NULL),
(561, 22, 11, 4237, 70, 24, 'away', 15, 3, 'unknown', 0, 442, NULL, NULL, 'on_target', NULL, NULL, 1, '2026-01-25 15:53:07', NULL, '2026-01-26 11:51:54', NULL, NULL, NULL, NULL),
(562, 22, 11, 4240, 70, 24, 'away', 4, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-25 15:53:11', NULL, '2026-01-26 11:51:54', NULL, NULL, NULL, NULL),
(563, 22, 11, 4367, 72, 26, 'away', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-25 15:54:53', NULL, '2026-01-26 11:51:54', NULL, NULL, NULL, NULL),
(564, 22, 11, 4372, 72, 27, 'away', 8, 2, 'unknown', 0, 442, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-25 15:55:00', NULL, '2026-01-26 11:51:54', NULL, NULL, NULL, NULL),
(565, 22, 11, 4388, 73, 27, 'home', 8, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-25 15:55:07', NULL, '2026-01-26 11:51:54', NULL, NULL, NULL, NULL),
(566, 22, 11, 4495, 74, 29, 'away', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-25 15:55:55', NULL, '2026-01-26 11:51:54', NULL, NULL, NULL, NULL),
(567, 22, 11, 4501, 75, 29, 'away', 8, 2, 'unknown', 0, 438, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-25 15:56:05', NULL, '2026-01-26 11:51:54', NULL, NULL, NULL, NULL),
(568, 22, 11, 4563, 76, 30, 'home', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-25 15:56:22', NULL, '2026-01-26 11:51:54', NULL, NULL, NULL, NULL),
(569, 22, 11, 4606, 76, 30, 'away', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-25 15:56:56', NULL, '2026-01-26 11:51:54', NULL, NULL, NULL, NULL),
(570, 22, 11, 4620, 77, 31, 'home', 8, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-25 15:57:16', NULL, '2026-01-26 11:51:54', NULL, NULL, NULL, NULL),
(571, 22, 11, 4801, 80, 34, 'away', 15, 3, 'unknown', 0, 439, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-01-25 15:59:11', NULL, '2026-01-26 11:51:54', NULL, NULL, NULL, NULL),
(572, 22, 11, 4893, 81, 35, 'away', 15, 3, 'unknown', 0, 443, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-01-25 16:00:21', NULL, '2026-01-26 11:51:54', NULL, NULL, NULL, NULL),
(573, 22, 11, 5040, 84, 38, 'home', 15, 3, 'unknown', 0, NULL, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-01-25 16:01:31', NULL, '2026-01-26 11:51:54', NULL, NULL, NULL, NULL),
(574, 22, 11, 5113, 85, 39, 'home', 15, 3, 'unknown', 0, NULL, NULL, NULL, 'on_target', NULL, NULL, 1, '2026-01-25 16:02:48', NULL, '2026-01-26 11:51:54', NULL, NULL, NULL, NULL),
(575, 22, 11, 5128, 85, 39, 'home', 4, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-25 16:03:11', NULL, '2026-01-26 11:51:54', NULL, NULL, NULL, NULL),
(576, 22, 11, 5183, 86, 40, 'home', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-25 16:03:51', NULL, '2026-01-26 11:51:54', NULL, NULL, NULL, NULL),
(577, 22, 11, 5247, 87, 41, 'home', 4, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-25 16:04:25', NULL, '2026-01-26 11:51:54', NULL, NULL, NULL, NULL),
(578, 22, 11, 5431, 90, 44, 'away', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-25 16:05:42', NULL, '2026-01-26 11:51:54', NULL, NULL, NULL, NULL),
(579, 22, 11, 5524, 92, 46, 'away', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-25 16:06:37', NULL, '2026-01-26 11:51:54', NULL, NULL, NULL, NULL),
(580, 22, 11, 5560, 92, 46, 'away', 15, 3, 'unknown', 0, 444, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-01-25 16:06:54', NULL, '2026-01-26 11:51:54', NULL, NULL, NULL, NULL),
(581, 22, 11, 5671, 94, 48, 'away', 12, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-25 16:07:32', NULL, '2026-01-26 11:51:54', NULL, NULL, NULL, NULL),
(582, 22, 11, 5686, 94, 48, 'away', 12, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-25 16:07:57', NULL, '2026-01-26 11:51:54', NULL, NULL, NULL, NULL),
(583, 22, 11, 5689, 94, 48, 'home', 15, 3, 'unknown', 0, NULL, NULL, NULL, 'on_target', NULL, NULL, 1, '2026-01-25 16:08:15', NULL, '2026-01-26 11:51:54', NULL, NULL, NULL, NULL),
(584, 22, 11, 5860, 97, 51, 'away', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-25 16:10:44', NULL, '2026-01-26 11:51:54', NULL, NULL, NULL, NULL),
(585, 22, 11, 5887, 98, 52, 'home', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-25 16:11:11', NULL, '2026-01-26 11:51:54', NULL, NULL, NULL, NULL),
(586, 22, 11, 6179, 102, 57, 'home', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-25 16:13:51', NULL, '2026-01-26 11:51:54', NULL, NULL, NULL, NULL),
(587, 22, 11, 6263, 104, 58, 'home', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-25 16:14:42', NULL, '2026-01-26 11:51:54', NULL, NULL, NULL, NULL),
(588, 22, 11, 6343, 105, 59, 'home', 15, 3, 'unknown', 0, NULL, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-01-25 16:15:59', NULL, '2026-01-26 11:51:54', NULL, NULL, NULL, NULL),
(595, 22, 11, 3687, 61, 15, 'unknown', 13, 1, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, 'Second Half', 1, '2026-01-25 17:02:31', NULL, '2026-01-26 11:51:54', NULL, NULL, NULL, NULL),
(596, 22, 11, 6470, 107, 0, 'unknown', 14, 3, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, 'Second Half', 1, '2026-01-25 17:02:56', NULL, '2026-01-26 11:51:54', NULL, NULL, NULL, NULL),
(597, 22, 11, 6470, 107, 61, 'unknown', 14, 1, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, 'Second Half', 1, '2026-01-25 17:02:56', NULL, '2026-01-26 11:51:54', NULL, NULL, NULL, NULL),
(598, 22, 9, 5703, 95, 49, 'home', 15, 3, 'unknown', 0, 445, NULL, NULL, 'on_target', NULL, NULL, 1, '2026-01-31 11:24:16', NULL, NULL, NULL, NULL, NULL, NULL),
(599, 23, 12, 6, 0, 0, 'unknown', 13, 1, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, 'First Half', 1, '2026-02-01 16:13:11', NULL, NULL, NULL, NULL, NULL, NULL),
(600, 23, 12, 82, 1, 0, 'away', 15, 3, 'unknown', 0, NULL, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-02-01 16:19:48', NULL, NULL, NULL, NULL, NULL, NULL),
(602, 23, 12, 171, 2, 0, 'home', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-02-01 16:22:40', NULL, NULL, NULL, NULL, NULL, NULL),
(603, 23, 12, 301, 5, 0, 'away', 4, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-02-01 16:25:59', NULL, NULL, NULL, NULL, NULL, NULL),
(604, 23, 12, 320, 5, 0, 'away', 15, 3, 'unknown', 0, NULL, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-02-01 16:26:20', NULL, NULL, NULL, NULL, NULL, NULL),
(605, 23, 12, 451, 7, 0, 'away', 15, 3, 'unknown', 0, NULL, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-02-01 16:29:05', NULL, NULL, NULL, NULL, NULL, NULL),
(606, 23, 12, 652, 10, 0, 'away', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-02-01 16:31:51', NULL, NULL, NULL, NULL, NULL, NULL),
(607, 23, 12, 996, 16, 0, 'away', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-02-01 16:35:42', NULL, NULL, NULL, NULL, NULL, NULL),
(608, 23, 12, 1196, 19, 0, 'home', 15, 3, 'unknown', 0, 458, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-02-01 16:37:58', NULL, NULL, NULL, NULL, NULL, NULL),
(609, 23, 12, 1326, 22, 0, 'away', 15, 3, 'unknown', 0, NULL, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-02-01 16:39:21', NULL, NULL, NULL, NULL, NULL, NULL),
(610, 23, 12, 1327, 22, 0, 'away', 4, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-02-01 16:39:23', NULL, NULL, NULL, NULL, NULL, NULL),
(611, 23, 12, 1363, 22, 0, 'away', 15, 3, 'unknown', 0, NULL, NULL, NULL, 'on_target', NULL, NULL, 1, '2026-02-01 16:39:51', NULL, NULL, NULL, NULL, NULL, NULL),
(612, 23, 12, 1366, 22, 0, 'away', 4, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-02-01 16:39:54', NULL, NULL, NULL, NULL, NULL, NULL),
(613, 23, 12, 1431, 23, 0, 'away', 15, 3, 'unknown', 0, NULL, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-02-01 16:40:55', NULL, NULL, NULL, NULL, NULL, NULL),
(614, 23, 12, 1577, 26, 0, 'away', 4, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-02-01 16:42:47', NULL, NULL, NULL, NULL, NULL, NULL),
(615, 23, 12, 1610, 26, 0, 'away', 16, 5, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-02-01 16:43:07', NULL, NULL, NULL, NULL, NULL, NULL),
(616, 23, 12, 1772, 29, 0, 'home', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-02-01 16:43:54', NULL, NULL, NULL, NULL, NULL, NULL),
(617, 23, 12, 1948, 32, 0, 'away', 4, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-02-01 16:47:03', NULL, NULL, NULL, NULL, NULL, NULL),
(618, 23, 12, 1976, 32, 0, 'away', 4, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-02-01 16:47:15', NULL, NULL, NULL, NULL, NULL, NULL),
(619, 23, 12, 1988, 33, 0, 'away', 15, 3, 'unknown', 0, NULL, NULL, NULL, 'on_target', NULL, NULL, 1, '2026-02-01 16:47:25', NULL, NULL, NULL, NULL, NULL, NULL),
(620, 23, 12, 2052, 34, 0, 'away', 16, 5, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-02-01 16:48:31', NULL, NULL, NULL, NULL, NULL, NULL),
(621, 23, 12, 2126, 35, 0, 'away', 12, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-02-01 16:49:34', NULL, NULL, NULL, NULL, NULL, NULL),
(622, 23, 12, 2220, 37, 0, 'away', 4, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-02-01 16:51:02', NULL, NULL, NULL, NULL, NULL, NULL),
(623, 23, 12, 2388, 39, 0, 'away', 15, 3, 'unknown', 0, NULL, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-02-01 16:52:13', NULL, NULL, NULL, NULL, NULL, NULL),
(624, 23, 12, 2595, 43, 0, 'home', 16, 5, 'unknown', 0, 461, NULL, NULL, NULL, NULL, NULL, 1, '2026-02-01 16:54:19', NULL, NULL, NULL, NULL, NULL, NULL),
(625, 23, 12, 2698, 44, 0, 'home', 12, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-02-01 16:55:47', NULL, NULL, NULL, NULL, NULL, NULL),
(626, 23, 12, 2755, 45, 0, 'away', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-02-01 16:56:19', NULL, NULL, NULL, NULL, NULL, NULL),
(627, 23, 12, 2802, 46, 0, 'away', 4, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-02-01 16:56:46', NULL, NULL, NULL, NULL, NULL, NULL),
(628, 23, 13, 6746, 111, 0, 'unknown', 14, 3, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, 'First Half', 1, '2026-02-01 16:57:33', NULL, '2026-02-01 17:45:55', NULL, NULL, NULL, NULL),
(629, 23, 12, 2863, 47, 0, 'unknown', 14, 1, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, 'First Half', 1, '2026-02-01 16:57:33', NULL, NULL, NULL, NULL, NULL, NULL),
(630, 23, 13, 3777, 62, 16, 'unknown', 13, 1, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, 'Second Half', 1, '2026-02-01 16:58:23', NULL, '2026-02-01 17:45:04', NULL, NULL, NULL, NULL),
(631, 23, 12, 3959, 65, 19, 'away', 4, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-02-01 17:00:10', NULL, NULL, NULL, NULL, NULL, NULL),
(632, 23, 12, 3987, 66, 19, 'home', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-02-01 17:00:24', NULL, NULL, NULL, NULL, NULL, NULL),
(633, 23, 12, 4082, 68, 21, 'away', 16, 5, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-02-01 17:01:46', NULL, NULL, NULL, NULL, NULL, NULL),
(634, 23, 12, 4156, 69, 22, 'away', 15, 3, 'unknown', 0, NULL, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-02-01 17:02:25', NULL, NULL, NULL, NULL, NULL, NULL),
(635, 23, 12, 4191, 69, 23, 'home', 15, 3, 'unknown', 0, 461, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-02-01 17:02:50', NULL, NULL, NULL, NULL, NULL, NULL),
(636, 23, 12, 4231, 70, 23, 'away', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-02-01 17:03:12', NULL, NULL, NULL, NULL, NULL, NULL),
(637, 23, 12, 4454, 74, 27, 'home', 15, 3, 'unknown', 0, 460, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-02-01 17:06:09', NULL, NULL, NULL, NULL, NULL, NULL),
(638, 23, 12, 4495, 74, 28, 'away', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-02-01 17:06:22', NULL, NULL, NULL, NULL, NULL, NULL),
(639, 23, 12, 4572, 76, 29, 'away', 15, 3, 'unknown', 0, NULL, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-02-01 17:07:22', NULL, NULL, NULL, NULL, NULL, NULL),
(640, 23, 12, 4620, 77, 30, 'home', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-02-01 17:08:05', NULL, NULL, NULL, NULL, NULL, NULL),
(641, 23, 12, 4657, 77, 30, 'home', 15, 3, 'unknown', 0, 456, NULL, NULL, 'on_target', NULL, NULL, 1, '2026-02-01 17:08:41', NULL, NULL, NULL, NULL, NULL, NULL),
(642, 23, 12, 4676, 77, 31, 'away', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-02-01 17:09:49', NULL, NULL, NULL, NULL, NULL, NULL),
(643, 23, 12, 4778, 79, 32, 'home', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-02-01 17:11:02', NULL, NULL, NULL, NULL, NULL, NULL),
(644, 23, 12, 4941, 82, 35, 'home', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-02-01 17:13:03', NULL, NULL, NULL, NULL, NULL, NULL),
(645, 23, 12, 4971, 82, 36, 'away', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-02-01 17:13:19', NULL, NULL, NULL, NULL, NULL, NULL),
(646, 23, 12, 5061, 84, 37, 'away', 4, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-02-01 17:14:07', NULL, NULL, NULL, NULL, NULL, NULL),
(647, 23, 12, 5088, 84, 38, 'home', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-02-01 17:14:20', NULL, NULL, NULL, NULL, NULL, NULL),
(648, 23, 12, 5135, 85, 38, 'home', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-02-01 17:14:53', NULL, NULL, NULL, NULL, NULL, NULL),
(649, 23, 12, 5169, 86, 39, 'away', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-02-01 17:15:22', NULL, NULL, NULL, NULL, NULL, NULL),
(650, 23, 12, 5317, 88, 41, 'away', 4, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-02-01 17:17:03', NULL, NULL, NULL, NULL, NULL, NULL),
(651, 23, 12, 5352, 89, 42, 'away', 16, 5, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-02-01 17:17:27', NULL, NULL, NULL, NULL, NULL, NULL),
(652, 23, 12, 5687, 94, 48, 'home', 15, 3, 'unknown', 0, 460, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-02-01 17:20:51', NULL, NULL, NULL, NULL, NULL, NULL),
(653, 23, 12, 5750, 95, 49, 'home', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-02-01 17:21:30', NULL, NULL, NULL, NULL, NULL, NULL),
(654, 23, 12, 5800, 96, 49, 'home', 15, 3, 'unknown', 0, 463, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-02-01 17:21:48', NULL, NULL, NULL, NULL, NULL, NULL),
(655, 23, 12, 5896, 98, 51, 'away', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-02-01 17:22:57', NULL, NULL, NULL, NULL, NULL, NULL),
(656, 23, 12, 5972, 99, 52, 'away', 15, 3, 'unknown', 0, NULL, NULL, NULL, 'on_target', NULL, NULL, 1, '2026-02-01 17:23:20', NULL, NULL, NULL, NULL, NULL, NULL),
(657, 23, 12, 6021, 100, 53, 'away', 16, 5, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-02-01 17:23:52', NULL, NULL, NULL, NULL, NULL, NULL),
(658, 23, 12, 6100, 101, 54, 'away', 4, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-02-01 17:24:31', NULL, NULL, NULL, NULL, NULL, NULL),
(659, 23, 12, 6126, 102, 55, 'away', 15, 3, 'unknown', 0, NULL, NULL, NULL, 'on_target', NULL, NULL, 1, '2026-02-01 17:24:44', NULL, NULL, NULL, NULL, NULL, NULL),
(660, 23, 12, 6192, 103, 56, 'away', 15, 3, 'unknown', 0, NULL, NULL, NULL, 'on_target', NULL, NULL, 1, '2026-02-01 17:25:56', NULL, NULL, NULL, NULL, NULL, NULL),
(661, 23, 12, 6228, 103, 57, 'home', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-02-01 17:26:32', NULL, NULL, NULL, NULL, NULL, NULL),
(662, 23, 12, 6239, 103, 57, 'home', 5, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-02-01 17:26:38', NULL, NULL, NULL, NULL, NULL, NULL),
(663, 23, 12, 6277, 104, 57, 'home', 15, 3, 'unknown', 0, 467, NULL, NULL, 'on_target', NULL, NULL, 1, '2026-02-01 17:27:13', NULL, NULL, NULL, NULL, NULL, NULL),
(664, 23, 12, 6280, 104, 57, 'home', 16, 5, 'unknown', 0, 455, NULL, NULL, NULL, NULL, NULL, 1, '2026-02-01 17:27:22', NULL, NULL, NULL, NULL, NULL, NULL),
(665, 23, 12, 6478, 107, 61, 'away', 4, 2, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-02-01 17:29:26', NULL, NULL, NULL, NULL, NULL, NULL),
(666, 23, 12, 6513, 108, 61, 'away', 16, 5, 'unknown', 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-02-01 17:29:48', NULL, NULL, NULL, NULL, NULL, NULL);

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
(80, 525, 22, '{\"id\":525,\"match_id\":22,\"period_id\":9,\"match_second\":247,\"minute\":4,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 15:08:58\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-01-25 15:08:58'),
(81, 526, 22, '{\"id\":526,\"match_id\":22,\"period_id\":9,\"match_second\":309,\"minute\":5,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":438,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"off_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 15:10:12\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"Cameron McIntyre\",\"match_player_shirt\":5,\"match_player_team_side\":\"away\",\"match_player_position\":\"RB\",\"period_label\":\"First Half\",\"player_first_name\":\"Cameron\",\"player_last_name\":\"McIntyre\",\"tags\":[]}', '2026-01-25 15:10:12'),
(82, 527, 22, '{\"id\":527,\"match_id\":22,\"period_id\":9,\"match_second\":386,\"minute\":6,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 15:11:33\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-01-25 15:11:33'),
(83, 528, 22, '{\"id\":528,\"match_id\":22,\"period_id\":9,\"match_second\":436,\"minute\":7,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":442,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"on_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 15:12:05\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"Aaron Robertson\",\"match_player_shirt\":9,\"match_player_team_side\":\"away\",\"match_player_position\":\"CM\",\"period_label\":\"First Half\",\"player_first_name\":\"Aaron\",\"player_last_name\":\"Robertson\",\"tags\":[]}', '2026-01-25 15:12:05'),
(84, 529, 22, '{\"id\":529,\"match_id\":22,\"period_id\":9,\"match_second\":544,\"minute\":9,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":443,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 15:13:59\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":\"David Sawyers\",\"match_player_shirt\":10,\"match_player_team_side\":\"away\",\"match_player_position\":\"ST\",\"period_label\":\"First Half\",\"player_first_name\":\"David\",\"player_last_name\":\"Sawyers\",\"tags\":[]}', '2026-01-25 15:13:59'),
(85, 530, 22, '{\"id\":530,\"match_id\":22,\"period_id\":9,\"match_second\":644,\"minute\":10,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 15:15:28\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-01-25 15:15:28'),
(86, 531, 22, '{\"id\":531,\"match_id\":22,\"period_id\":9,\"match_second\":725,\"minute\":12,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 15:16:35\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-01-25 15:16:35'),
(87, 532, 22, '{\"id\":532,\"match_id\":22,\"period_id\":9,\"match_second\":764,\"minute\":12,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 15:17:04\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-01-25 15:17:04'),
(88, 533, 22, '{\"id\":533,\"match_id\":22,\"period_id\":9,\"match_second\":835,\"minute\":13,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":12,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 15:18:16\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Highlight\",\"event_type_key\":\"highlight\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-01-25 15:18:16'),
(89, 534, 22, '{\"id\":534,\"match_id\":22,\"period_id\":9,\"match_second\":901,\"minute\":15,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":438,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"off_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 15:19:28\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"Cameron McIntyre\",\"match_player_shirt\":5,\"match_player_team_side\":\"away\",\"match_player_position\":\"RB\",\"period_label\":\"First Half\",\"player_first_name\":\"Cameron\",\"player_last_name\":\"McIntyre\",\"tags\":[]}', '2026-01-25 15:19:28'),
(90, 535, 22, '{\"id\":535,\"match_id\":22,\"period_id\":9,\"match_second\":989,\"minute\":16,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":444,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"on_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 15:21:14\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"Euan Anderson\",\"match_player_shirt\":11,\"match_player_team_side\":\"away\",\"match_player_position\":\"ST\",\"period_label\":\"First Half\",\"player_first_name\":\"Euan\",\"player_last_name\":\"Anderson\",\"tags\":[]}', '2026-01-25 15:21:14'),
(91, 536, 22, '{\"id\":536,\"match_id\":22,\"period_id\":9,\"match_second\":999,\"minute\":16,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":4,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 15:21:26\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Corner\",\"event_type_key\":\"corner\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-01-25 15:21:26'),
(92, 537, 22, '{\"id\":537,\"match_id\":22,\"period_id\":9,\"match_second\":1101,\"minute\":18,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 15:22:59\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-01-25 15:22:59'),
(93, 538, 22, '{\"id\":538,\"match_id\":22,\"period_id\":9,\"match_second\":1153,\"minute\":19,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 15:23:43\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-01-25 15:23:43'),
(94, 539, 22, '{\"id\":539,\"match_id\":22,\"period_id\":9,\"match_second\":1266,\"minute\":21,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 15:25:35\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-01-25 15:25:35'),
(95, 540, 22, '{\"id\":540,\"match_id\":22,\"period_id\":9,\"match_second\":1277,\"minute\":21,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":8,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":435,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 15:25:51\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Yellow Card\",\"event_type_key\":\"yellow_card\",\"match_player_name\":\"Jamie Stirling\",\"match_player_shirt\":2,\"match_player_team_side\":\"away\",\"match_player_position\":\"CB\",\"period_label\":\"First Half\",\"player_first_name\":\"Jamie\",\"player_last_name\":\"Stirling\",\"tags\":[]}', '2026-01-25 15:25:51'),
(96, 541, 22, '{\"id\":541,\"match_id\":22,\"period_id\":9,\"match_second\":1332,\"minute\":22,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 15:26:37\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-01-25 15:26:37'),
(97, 542, 22, '{\"id\":542,\"match_id\":22,\"period_id\":9,\"match_second\":1419,\"minute\":23,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":438,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 15:28:10\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":\"Cameron McIntyre\",\"match_player_shirt\":5,\"match_player_team_side\":\"away\",\"match_player_position\":\"RB\",\"period_label\":\"First Half\",\"player_first_name\":\"Cameron\",\"player_last_name\":\"McIntyre\",\"tags\":[]}', '2026-01-25 15:28:10'),
(98, 543, 22, '{\"id\":543,\"match_id\":22,\"period_id\":9,\"match_second\":1570,\"minute\":26,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":442,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"off_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 15:30:15\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"Aaron Robertson\",\"match_player_shirt\":9,\"match_player_team_side\":\"away\",\"match_player_position\":\"CM\",\"period_label\":\"First Half\",\"player_first_name\":\"Aaron\",\"player_last_name\":\"Robertson\",\"tags\":[]}', '2026-01-25 15:30:15'),
(99, 544, 22, '{\"id\":544,\"match_id\":22,\"period_id\":9,\"match_second\":1620,\"minute\":27,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":443,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"on_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 15:30:40\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"David Sawyers\",\"match_player_shirt\":10,\"match_player_team_side\":\"away\",\"match_player_position\":\"ST\",\"period_label\":\"First Half\",\"player_first_name\":\"David\",\"player_last_name\":\"Sawyers\",\"tags\":[]}', '2026-01-25 15:30:40'),
(100, 545, 22, '{\"id\":545,\"match_id\":22,\"period_id\":9,\"match_second\":1614,\"minute\":26,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":3,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 15:30:49\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Chance\",\"event_type_key\":\"chance\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-01-25 15:30:49'),
(101, 546, 22, '{\"id\":546,\"match_id\":22,\"period_id\":9,\"match_second\":1787,\"minute\":29,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 15:33:13\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-01-25 15:33:13'),
(102, 547, 22, '{\"id\":547,\"match_id\":22,\"period_id\":9,\"match_second\":2016,\"minute\":33,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 15:36:39\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-01-25 15:36:39'),
(103, 548, 22, '{\"id\":548,\"match_id\":22,\"period_id\":9,\"match_second\":2080,\"minute\":34,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":435,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 15:37:18\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":\"Jamie Stirling\",\"match_player_shirt\":2,\"match_player_team_side\":\"away\",\"match_player_position\":\"CB\",\"period_label\":\"First Half\",\"player_first_name\":\"Jamie\",\"player_last_name\":\"Stirling\",\"tags\":[]}', '2026-01-25 15:37:18'),
(104, 549, 22, '{\"id\":549,\"match_id\":22,\"period_id\":9,\"match_second\":2078,\"minute\":34,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":442,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"on_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 15:37:29\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"Aaron Robertson\",\"match_player_shirt\":9,\"match_player_team_side\":\"away\",\"match_player_position\":\"CM\",\"period_label\":\"First Half\",\"player_first_name\":\"Aaron\",\"player_last_name\":\"Robertson\",\"tags\":[]}', '2026-01-25 15:37:29'),
(105, 550, 22, '{\"id\":550,\"match_id\":22,\"period_id\":9,\"match_second\":2432,\"minute\":40,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":440,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"off_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 15:41:25\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"Adam Love\",\"match_player_shirt\":7,\"match_player_team_side\":\"away\",\"match_player_position\":\"CM\",\"period_label\":\"First Half\",\"player_first_name\":\"Adam\",\"player_last_name\":\"Love\",\"tags\":[]}', '2026-01-25 15:41:25'),
(106, 551, 22, '{\"id\":551,\"match_id\":22,\"period_id\":9,\"match_second\":2439,\"minute\":40,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":4,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 15:41:32\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Corner\",\"event_type_key\":\"corner\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-01-25 15:41:32'),
(107, 552, 22, '{\"id\":552,\"match_id\":22,\"period_id\":9,\"match_second\":2477,\"minute\":41,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":437,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 15:42:00\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":\"Ross Agnew\",\"match_player_shirt\":4,\"match_player_team_side\":\"away\",\"match_player_position\":\"CB\",\"period_label\":\"First Half\",\"player_first_name\":\"Ross\",\"player_last_name\":\"Agnew\",\"tags\":[]}', '2026-01-25 15:42:00'),
(108, 553, 22, '{\"id\":553,\"match_id\":22,\"period_id\":9,\"match_second\":2587,\"minute\":43,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 15:43:16\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-01-25 15:43:16'),
(109, 554, 22, '{\"id\":554,\"match_id\":22,\"period_id\":9,\"match_second\":2805,\"minute\":46,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":442,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"off_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 15:46:09\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"Aaron Robertson\",\"match_player_shirt\":9,\"match_player_team_side\":\"away\",\"match_player_position\":\"CM\",\"period_label\":\"First Half\",\"player_first_name\":\"Aaron\",\"player_last_name\":\"Robertson\",\"tags\":[]}', '2026-01-25 15:46:09'),
(110, 557, 22, '{\"id\":557,\"match_id\":22,\"period_id\":9,\"match_second\":3719,\"minute\":61,\"minute_extra\":16,\"team_side\":\"home\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"on_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 15:47:20\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-01-25 15:47:20'),
(111, 558, 22, '{\"id\":558,\"match_id\":22,\"period_id\":9,\"match_second\":3809,\"minute\":63,\"minute_extra\":17,\"team_side\":\"home\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 15:48:31\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-01-25 15:48:31'),
(112, 559, 22, '{\"id\":559,\"match_id\":22,\"period_id\":9,\"match_second\":3820,\"minute\":63,\"minute_extra\":17,\"team_side\":\"home\",\"event_type_id\":8,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 15:48:43\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Yellow Card\",\"event_type_key\":\"yellow_card\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-01-25 15:48:43'),
(113, 560, 22, '{\"id\":560,\"match_id\":22,\"period_id\":9,\"match_second\":4139,\"minute\":68,\"minute_extra\":23,\"team_side\":\"away\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 15:51:44\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-01-25 15:51:44'),
(114, 561, 22, '{\"id\":561,\"match_id\":22,\"period_id\":9,\"match_second\":4237,\"minute\":70,\"minute_extra\":24,\"team_side\":\"away\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":442,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"on_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 15:53:07\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"Aaron Robertson\",\"match_player_shirt\":9,\"match_player_team_side\":\"away\",\"match_player_position\":\"CM\",\"period_label\":\"First Half\",\"player_first_name\":\"Aaron\",\"player_last_name\":\"Robertson\",\"tags\":[]}', '2026-01-25 15:53:07'),
(115, 562, 22, '{\"id\":562,\"match_id\":22,\"period_id\":9,\"match_second\":4240,\"minute\":70,\"minute_extra\":24,\"team_side\":\"away\",\"event_type_id\":4,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 15:53:11\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Corner\",\"event_type_key\":\"corner\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-01-25 15:53:11'),
(116, 563, 22, '{\"id\":563,\"match_id\":22,\"period_id\":9,\"match_second\":4367,\"minute\":72,\"minute_extra\":26,\"team_side\":\"away\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 15:54:53\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-01-25 15:54:53'),
(117, 564, 22, '{\"id\":564,\"match_id\":22,\"period_id\":9,\"match_second\":4372,\"minute\":72,\"minute_extra\":27,\"team_side\":\"away\",\"event_type_id\":8,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":442,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 15:55:00\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Yellow Card\",\"event_type_key\":\"yellow_card\",\"match_player_name\":\"Aaron Robertson\",\"match_player_shirt\":9,\"match_player_team_side\":\"away\",\"match_player_position\":\"CM\",\"period_label\":\"First Half\",\"player_first_name\":\"Aaron\",\"player_last_name\":\"Robertson\",\"tags\":[]}', '2026-01-25 15:55:00'),
(118, 565, 22, '{\"id\":565,\"match_id\":22,\"period_id\":9,\"match_second\":4388,\"minute\":73,\"minute_extra\":27,\"team_side\":\"home\",\"event_type_id\":8,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 15:55:07\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Yellow Card\",\"event_type_key\":\"yellow_card\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-01-25 15:55:07'),
(119, 566, 22, '{\"id\":566,\"match_id\":22,\"period_id\":9,\"match_second\":4495,\"minute\":74,\"minute_extra\":29,\"team_side\":\"away\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 15:55:55\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-01-25 15:55:55'),
(120, 567, 22, '{\"id\":567,\"match_id\":22,\"period_id\":9,\"match_second\":4501,\"minute\":75,\"minute_extra\":29,\"team_side\":\"away\",\"event_type_id\":8,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":438,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 15:56:05\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Yellow Card\",\"event_type_key\":\"yellow_card\",\"match_player_name\":\"Cameron McIntyre\",\"match_player_shirt\":5,\"match_player_team_side\":\"away\",\"match_player_position\":\"RB\",\"period_label\":\"First Half\",\"player_first_name\":\"Cameron\",\"player_last_name\":\"McIntyre\",\"tags\":[]}', '2026-01-25 15:56:05'),
(121, 568, 22, '{\"id\":568,\"match_id\":22,\"period_id\":9,\"match_second\":4563,\"minute\":76,\"minute_extra\":30,\"team_side\":\"home\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 15:56:22\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-01-25 15:56:22'),
(122, 569, 22, '{\"id\":569,\"match_id\":22,\"period_id\":9,\"match_second\":4606,\"minute\":76,\"minute_extra\":30,\"team_side\":\"away\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 15:56:56\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-01-25 15:56:56'),
(123, 570, 22, '{\"id\":570,\"match_id\":22,\"period_id\":9,\"match_second\":4620,\"minute\":77,\"minute_extra\":31,\"team_side\":\"home\",\"event_type_id\":8,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 15:57:16\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Yellow Card\",\"event_type_key\":\"yellow_card\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-01-25 15:57:16'),
(124, 571, 22, '{\"id\":571,\"match_id\":22,\"period_id\":9,\"match_second\":4801,\"minute\":80,\"minute_extra\":34,\"team_side\":\"away\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":439,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"off_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 15:59:11\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"Jack Hanlon\",\"match_player_shirt\":6,\"match_player_team_side\":\"away\",\"match_player_position\":\"CM\",\"period_label\":\"First Half\",\"player_first_name\":\"Jack\",\"player_last_name\":\"Hanlon\",\"tags\":[]}', '2026-01-25 15:59:11'),
(125, 572, 22, '{\"id\":572,\"match_id\":22,\"period_id\":9,\"match_second\":4893,\"minute\":81,\"minute_extra\":35,\"team_side\":\"away\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":443,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"off_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 16:00:21\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"David Sawyers\",\"match_player_shirt\":10,\"match_player_team_side\":\"away\",\"match_player_position\":\"ST\",\"period_label\":\"First Half\",\"player_first_name\":\"David\",\"player_last_name\":\"Sawyers\",\"tags\":[]}', '2026-01-25 16:00:21'),
(126, 573, 22, '{\"id\":573,\"match_id\":22,\"period_id\":9,\"match_second\":5040,\"minute\":84,\"minute_extra\":38,\"team_side\":\"home\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"off_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 16:01:31\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-01-25 16:01:31'),
(127, 574, 22, '{\"id\":574,\"match_id\":22,\"period_id\":9,\"match_second\":5113,\"minute\":85,\"minute_extra\":39,\"team_side\":\"home\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"on_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 16:02:48\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-01-25 16:02:48'),
(128, 575, 22, '{\"id\":575,\"match_id\":22,\"period_id\":9,\"match_second\":5128,\"minute\":85,\"minute_extra\":39,\"team_side\":\"home\",\"event_type_id\":4,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 16:03:11\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Corner\",\"event_type_key\":\"corner\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-01-25 16:03:11'),
(129, 576, 22, '{\"id\":576,\"match_id\":22,\"period_id\":9,\"match_second\":5183,\"minute\":86,\"minute_extra\":40,\"team_side\":\"home\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 16:03:51\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-01-25 16:03:51'),
(130, 577, 22, '{\"id\":577,\"match_id\":22,\"period_id\":9,\"match_second\":5247,\"minute\":87,\"minute_extra\":41,\"team_side\":\"home\",\"event_type_id\":4,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 16:04:25\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Corner\",\"event_type_key\":\"corner\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-01-25 16:04:25'),
(131, 578, 22, '{\"id\":578,\"match_id\":22,\"period_id\":9,\"match_second\":5431,\"minute\":90,\"minute_extra\":44,\"team_side\":\"away\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 16:05:42\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-01-25 16:05:42'),
(132, 579, 22, '{\"id\":579,\"match_id\":22,\"period_id\":9,\"match_second\":5524,\"minute\":92,\"minute_extra\":46,\"team_side\":\"away\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 16:06:37\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-01-25 16:06:37'),
(133, 580, 22, '{\"id\":580,\"match_id\":22,\"period_id\":9,\"match_second\":5560,\"minute\":92,\"minute_extra\":46,\"team_side\":\"away\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":444,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"off_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 16:06:54\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"Euan Anderson\",\"match_player_shirt\":11,\"match_player_team_side\":\"away\",\"match_player_position\":\"ST\",\"period_label\":\"First Half\",\"player_first_name\":\"Euan\",\"player_last_name\":\"Anderson\",\"tags\":[]}', '2026-01-25 16:06:54'),
(134, 581, 22, '{\"id\":581,\"match_id\":22,\"period_id\":9,\"match_second\":5671,\"minute\":94,\"minute_extra\":48,\"team_side\":\"away\",\"event_type_id\":12,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 16:07:32\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Highlight\",\"event_type_key\":\"highlight\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-01-25 16:07:32'),
(135, 582, 22, '{\"id\":582,\"match_id\":22,\"period_id\":9,\"match_second\":5686,\"minute\":94,\"minute_extra\":48,\"team_side\":\"away\",\"event_type_id\":12,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 16:07:57\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Highlight\",\"event_type_key\":\"highlight\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-01-25 16:07:57'),
(136, 583, 22, '{\"id\":583,\"match_id\":22,\"period_id\":9,\"match_second\":5689,\"minute\":94,\"minute_extra\":48,\"team_side\":\"home\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"on_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 16:08:15\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-01-25 16:08:15'),
(137, 584, 22, '{\"id\":584,\"match_id\":22,\"period_id\":9,\"match_second\":5860,\"minute\":97,\"minute_extra\":51,\"team_side\":\"away\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 16:10:44\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-01-25 16:10:44'),
(138, 585, 22, '{\"id\":585,\"match_id\":22,\"period_id\":9,\"match_second\":5887,\"minute\":98,\"minute_extra\":52,\"team_side\":\"home\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 16:11:11\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-01-25 16:11:11');
INSERT INTO `event_snapshots` (`id`, `event_id`, `match_id`, `snapshot_json`, `created_at`) VALUES
(139, 586, 22, '{\"id\":586,\"match_id\":22,\"period_id\":9,\"match_second\":6179,\"minute\":102,\"minute_extra\":57,\"team_side\":\"home\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 16:13:51\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-01-25 16:13:51'),
(140, 587, 22, '{\"id\":587,\"match_id\":22,\"period_id\":9,\"match_second\":6263,\"minute\":104,\"minute_extra\":58,\"team_side\":\"home\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 16:14:42\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-01-25 16:14:42'),
(141, 588, 22, '{\"id\":588,\"match_id\":22,\"period_id\":9,\"match_second\":6343,\"minute\":105,\"minute_extra\":59,\"team_side\":\"home\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"off_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-25 16:15:59\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-01-25 16:15:59'),
(142, 598, 22, '{\"id\":598,\"match_id\":22,\"period_id\":9,\"match_second\":5703,\"minute\":95,\"minute_extra\":49,\"team_side\":\"home\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":445,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"on_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-01-31 11:24:16\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"Aaron Tait\",\"match_player_shirt\":12,\"match_player_team_side\":\"away\",\"match_player_position\":\"CB\",\"period_label\":\"First Half\",\"player_first_name\":\"Aaron\",\"player_last_name\":\"Tait\",\"tags\":[]}', '2026-01-31 11:24:16'),
(143, 600, 23, '{\"id\":600,\"match_id\":23,\"period_id\":12,\"match_second\":82,\"minute\":1,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"off_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 16:19:48\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-02-01 16:19:48'),
(145, 602, 23, '{\"id\":602,\"match_id\":23,\"period_id\":12,\"match_second\":171,\"minute\":2,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 16:22:40\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-02-01 16:22:40'),
(146, 603, 23, '{\"id\":603,\"match_id\":23,\"period_id\":12,\"match_second\":301,\"minute\":5,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":4,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 16:25:59\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Corner\",\"event_type_key\":\"corner\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-02-01 16:25:59'),
(147, 604, 23, '{\"id\":604,\"match_id\":23,\"period_id\":12,\"match_second\":320,\"minute\":5,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"off_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 16:26:20\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-02-01 16:26:20'),
(148, 605, 23, '{\"id\":605,\"match_id\":23,\"period_id\":12,\"match_second\":451,\"minute\":7,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"off_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 16:29:05\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-02-01 16:29:05'),
(149, 606, 23, '{\"id\":606,\"match_id\":23,\"period_id\":12,\"match_second\":652,\"minute\":10,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 16:31:51\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-02-01 16:31:51'),
(150, 607, 23, '{\"id\":607,\"match_id\":23,\"period_id\":12,\"match_second\":996,\"minute\":16,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 16:35:42\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-02-01 16:35:42'),
(151, 608, 23, '{\"id\":608,\"match_id\":23,\"period_id\":12,\"match_second\":1196,\"minute\":19,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":458,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"off_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 16:37:58\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"Jack Hanlon\",\"match_player_shirt\":7,\"match_player_team_side\":\"home\",\"match_player_position\":\"CM\",\"period_label\":\"First Half\",\"player_first_name\":\"Jack\",\"player_last_name\":\"Hanlon\",\"tags\":[]}', '2026-02-01 16:37:58'),
(152, 609, 23, '{\"id\":609,\"match_id\":23,\"period_id\":12,\"match_second\":1326,\"minute\":22,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"off_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 16:39:21\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-02-01 16:39:21'),
(153, 610, 23, '{\"id\":610,\"match_id\":23,\"period_id\":12,\"match_second\":1327,\"minute\":22,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":4,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 16:39:23\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Corner\",\"event_type_key\":\"corner\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-02-01 16:39:23'),
(154, 611, 23, '{\"id\":611,\"match_id\":23,\"period_id\":12,\"match_second\":1363,\"minute\":22,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"on_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 16:39:51\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-02-01 16:39:51'),
(155, 612, 23, '{\"id\":612,\"match_id\":23,\"period_id\":12,\"match_second\":1366,\"minute\":22,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":4,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 16:39:54\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Corner\",\"event_type_key\":\"corner\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-02-01 16:39:54'),
(156, 613, 23, '{\"id\":613,\"match_id\":23,\"period_id\":12,\"match_second\":1431,\"minute\":23,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"off_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 16:40:55\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-02-01 16:40:56'),
(157, 614, 23, '{\"id\":614,\"match_id\":23,\"period_id\":12,\"match_second\":1577,\"minute\":26,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":4,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 16:42:47\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Corner\",\"event_type_key\":\"corner\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-02-01 16:42:47'),
(158, 615, 23, '{\"id\":615,\"match_id\":23,\"period_id\":12,\"match_second\":1610,\"minute\":26,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 16:43:07\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-02-01 16:43:07'),
(159, 616, 23, '{\"id\":616,\"match_id\":23,\"period_id\":12,\"match_second\":1772,\"minute\":29,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 16:43:54\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-02-01 16:43:54'),
(160, 617, 23, '{\"id\":617,\"match_id\":23,\"period_id\":12,\"match_second\":1948,\"minute\":32,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":4,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 16:47:03\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Corner\",\"event_type_key\":\"corner\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-02-01 16:47:03'),
(161, 618, 23, '{\"id\":618,\"match_id\":23,\"period_id\":12,\"match_second\":1976,\"minute\":32,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":4,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 16:47:15\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Corner\",\"event_type_key\":\"corner\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-02-01 16:47:15'),
(162, 619, 23, '{\"id\":619,\"match_id\":23,\"period_id\":12,\"match_second\":1988,\"minute\":33,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"on_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 16:47:25\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-02-01 16:47:25'),
(163, 620, 23, '{\"id\":620,\"match_id\":23,\"period_id\":12,\"match_second\":2052,\"minute\":34,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 16:48:31\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-02-01 16:48:31'),
(164, 621, 23, '{\"id\":621,\"match_id\":23,\"period_id\":12,\"match_second\":2126,\"minute\":35,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":12,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 16:49:34\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Highlight\",\"event_type_key\":\"highlight\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-02-01 16:49:34'),
(165, 622, 23, '{\"id\":622,\"match_id\":23,\"period_id\":12,\"match_second\":2220,\"minute\":37,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":4,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 16:51:02\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Corner\",\"event_type_key\":\"corner\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-02-01 16:51:02'),
(166, 623, 23, '{\"id\":623,\"match_id\":23,\"period_id\":12,\"match_second\":2388,\"minute\":39,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"off_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 16:52:13\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-02-01 16:52:13'),
(167, 624, 23, '{\"id\":624,\"match_id\":23,\"period_id\":12,\"match_second\":2595,\"minute\":43,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":461,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 16:54:19\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":\"Euan Anderson\",\"match_player_shirt\":10,\"match_player_team_side\":\"home\",\"match_player_position\":\"ST\",\"period_label\":\"First Half\",\"player_first_name\":\"Euan\",\"player_last_name\":\"Anderson\",\"tags\":[]}', '2026-02-01 16:54:19'),
(168, 625, 23, '{\"id\":625,\"match_id\":23,\"period_id\":12,\"match_second\":2698,\"minute\":44,\"minute_extra\":0,\"team_side\":\"home\",\"event_type_id\":12,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 16:55:47\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Highlight\",\"event_type_key\":\"highlight\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-02-01 16:55:47'),
(169, 626, 23, '{\"id\":626,\"match_id\":23,\"period_id\":12,\"match_second\":2755,\"minute\":45,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 16:56:19\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-02-01 16:56:19'),
(170, 627, 23, '{\"id\":627,\"match_id\":23,\"period_id\":12,\"match_second\":2802,\"minute\":46,\"minute_extra\":0,\"team_side\":\"away\",\"event_type_id\":4,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 16:56:46\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Corner\",\"event_type_key\":\"corner\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-02-01 16:56:46'),
(171, 631, 23, '{\"id\":631,\"match_id\":23,\"period_id\":12,\"match_second\":3959,\"minute\":65,\"minute_extra\":19,\"team_side\":\"away\",\"event_type_id\":4,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 17:00:10\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Corner\",\"event_type_key\":\"corner\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-02-01 17:00:10'),
(172, 632, 23, '{\"id\":632,\"match_id\":23,\"period_id\":12,\"match_second\":3987,\"minute\":66,\"minute_extra\":19,\"team_side\":\"home\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 17:00:24\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-02-01 17:00:24'),
(173, 633, 23, '{\"id\":633,\"match_id\":23,\"period_id\":12,\"match_second\":4082,\"minute\":68,\"minute_extra\":21,\"team_side\":\"away\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 17:01:46\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-02-01 17:01:46'),
(174, 634, 23, '{\"id\":634,\"match_id\":23,\"period_id\":12,\"match_second\":4156,\"minute\":69,\"minute_extra\":22,\"team_side\":\"away\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"off_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 17:02:25\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-02-01 17:02:25'),
(175, 635, 23, '{\"id\":635,\"match_id\":23,\"period_id\":12,\"match_second\":4191,\"minute\":69,\"minute_extra\":23,\"team_side\":\"home\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":461,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"off_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 17:02:50\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"Euan Anderson\",\"match_player_shirt\":10,\"match_player_team_side\":\"home\",\"match_player_position\":\"ST\",\"period_label\":\"First Half\",\"player_first_name\":\"Euan\",\"player_last_name\":\"Anderson\",\"tags\":[]}', '2026-02-01 17:02:50'),
(176, 636, 23, '{\"id\":636,\"match_id\":23,\"period_id\":12,\"match_second\":4231,\"minute\":70,\"minute_extra\":23,\"team_side\":\"away\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 17:03:12\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-02-01 17:03:12'),
(177, 637, 23, '{\"id\":637,\"match_id\":23,\"period_id\":12,\"match_second\":4454,\"minute\":74,\"minute_extra\":27,\"team_side\":\"home\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":460,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"off_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 17:06:09\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"David Sawyers\",\"match_player_shirt\":9,\"match_player_team_side\":\"home\",\"match_player_position\":\"ST\",\"period_label\":\"First Half\",\"player_first_name\":\"David\",\"player_last_name\":\"Sawyers\",\"tags\":[]}', '2026-02-01 17:06:09'),
(178, 638, 23, '{\"id\":638,\"match_id\":23,\"period_id\":12,\"match_second\":4495,\"minute\":74,\"minute_extra\":28,\"team_side\":\"away\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 17:06:22\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-02-01 17:06:22'),
(179, 639, 23, '{\"id\":639,\"match_id\":23,\"period_id\":12,\"match_second\":4572,\"minute\":76,\"minute_extra\":29,\"team_side\":\"away\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"off_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 17:07:22\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-02-01 17:07:22'),
(180, 640, 23, '{\"id\":640,\"match_id\":23,\"period_id\":12,\"match_second\":4620,\"minute\":77,\"minute_extra\":30,\"team_side\":\"home\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 17:08:05\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-02-01 17:08:05'),
(181, 641, 23, '{\"id\":641,\"match_id\":23,\"period_id\":12,\"match_second\":4657,\"minute\":77,\"minute_extra\":30,\"team_side\":\"home\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":456,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"on_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 17:08:41\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"Ross Agnew\",\"match_player_shirt\":5,\"match_player_team_side\":\"home\",\"match_player_position\":\"CB\",\"period_label\":\"First Half\",\"player_first_name\":\"Ross\",\"player_last_name\":\"Agnew\",\"tags\":[]}', '2026-02-01 17:08:41'),
(182, 642, 23, '{\"id\":642,\"match_id\":23,\"period_id\":12,\"match_second\":4676,\"minute\":77,\"minute_extra\":31,\"team_side\":\"away\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 17:09:49\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-02-01 17:09:49'),
(183, 643, 23, '{\"id\":643,\"match_id\":23,\"period_id\":12,\"match_second\":4778,\"minute\":79,\"minute_extra\":32,\"team_side\":\"home\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 17:11:02\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-02-01 17:11:02'),
(184, 644, 23, '{\"id\":644,\"match_id\":23,\"period_id\":12,\"match_second\":4941,\"minute\":82,\"minute_extra\":35,\"team_side\":\"home\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 17:13:03\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-02-01 17:13:03'),
(185, 645, 23, '{\"id\":645,\"match_id\":23,\"period_id\":12,\"match_second\":4971,\"minute\":82,\"minute_extra\":36,\"team_side\":\"away\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 17:13:19\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-02-01 17:13:19'),
(186, 646, 23, '{\"id\":646,\"match_id\":23,\"period_id\":12,\"match_second\":5061,\"minute\":84,\"minute_extra\":37,\"team_side\":\"away\",\"event_type_id\":4,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 17:14:07\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Corner\",\"event_type_key\":\"corner\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-02-01 17:14:07'),
(187, 647, 23, '{\"id\":647,\"match_id\":23,\"period_id\":12,\"match_second\":5088,\"minute\":84,\"minute_extra\":38,\"team_side\":\"home\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 17:14:20\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-02-01 17:14:20'),
(188, 648, 23, '{\"id\":648,\"match_id\":23,\"period_id\":12,\"match_second\":5135,\"minute\":85,\"minute_extra\":38,\"team_side\":\"home\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 17:14:53\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-02-01 17:14:53'),
(189, 649, 23, '{\"id\":649,\"match_id\":23,\"period_id\":12,\"match_second\":5169,\"minute\":86,\"minute_extra\":39,\"team_side\":\"away\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 17:15:22\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-02-01 17:15:22'),
(190, 650, 23, '{\"id\":650,\"match_id\":23,\"period_id\":12,\"match_second\":5317,\"minute\":88,\"minute_extra\":41,\"team_side\":\"away\",\"event_type_id\":4,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 17:17:03\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Corner\",\"event_type_key\":\"corner\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-02-01 17:17:03'),
(191, 651, 23, '{\"id\":651,\"match_id\":23,\"period_id\":12,\"match_second\":5352,\"minute\":89,\"minute_extra\":42,\"team_side\":\"away\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 17:17:27\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-02-01 17:17:27'),
(192, 652, 23, '{\"id\":652,\"match_id\":23,\"period_id\":12,\"match_second\":5687,\"minute\":94,\"minute_extra\":48,\"team_side\":\"home\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":460,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"off_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 17:20:51\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"David Sawyers\",\"match_player_shirt\":9,\"match_player_team_side\":\"home\",\"match_player_position\":\"ST\",\"period_label\":\"First Half\",\"player_first_name\":\"David\",\"player_last_name\":\"Sawyers\",\"tags\":[]}', '2026-02-01 17:20:51'),
(193, 653, 23, '{\"id\":653,\"match_id\":23,\"period_id\":12,\"match_second\":5750,\"minute\":95,\"minute_extra\":49,\"team_side\":\"home\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 17:21:30\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-02-01 17:21:30'),
(194, 654, 23, '{\"id\":654,\"match_id\":23,\"period_id\":12,\"match_second\":5800,\"minute\":96,\"minute_extra\":49,\"team_side\":\"home\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":463,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"off_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 17:21:48\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"Adam Love\",\"match_player_shirt\":12,\"match_player_team_side\":\"home\",\"match_player_position\":\"CM\",\"period_label\":\"First Half\",\"player_first_name\":\"Adam\",\"player_last_name\":\"Love\",\"tags\":[]}', '2026-02-01 17:21:48'),
(195, 655, 23, '{\"id\":655,\"match_id\":23,\"period_id\":12,\"match_second\":5896,\"minute\":98,\"minute_extra\":51,\"team_side\":\"away\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 17:22:57\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-02-01 17:22:57'),
(196, 656, 23, '{\"id\":656,\"match_id\":23,\"period_id\":12,\"match_second\":5972,\"minute\":99,\"minute_extra\":52,\"team_side\":\"away\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"on_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 17:23:20\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-02-01 17:23:20'),
(197, 657, 23, '{\"id\":657,\"match_id\":23,\"period_id\":12,\"match_second\":6021,\"minute\":100,\"minute_extra\":53,\"team_side\":\"away\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 17:23:52\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-02-01 17:23:52'),
(198, 658, 23, '{\"id\":658,\"match_id\":23,\"period_id\":12,\"match_second\":6100,\"minute\":101,\"minute_extra\":54,\"team_side\":\"away\",\"event_type_id\":4,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 17:24:31\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Corner\",\"event_type_key\":\"corner\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-02-01 17:24:31');
INSERT INTO `event_snapshots` (`id`, `event_id`, `match_id`, `snapshot_json`, `created_at`) VALUES
(199, 659, 23, '{\"id\":659,\"match_id\":23,\"period_id\":12,\"match_second\":6126,\"minute\":102,\"minute_extra\":55,\"team_side\":\"away\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"on_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 17:24:44\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-02-01 17:24:44'),
(200, 660, 23, '{\"id\":660,\"match_id\":23,\"period_id\":12,\"match_second\":6192,\"minute\":103,\"minute_extra\":56,\"team_side\":\"away\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"on_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 17:25:56\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-02-01 17:25:56'),
(201, 661, 23, '{\"id\":661,\"match_id\":23,\"period_id\":12,\"match_second\":6228,\"minute\":103,\"minute_extra\":57,\"team_side\":\"home\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 17:26:32\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-02-01 17:26:32'),
(202, 662, 23, '{\"id\":662,\"match_id\":23,\"period_id\":12,\"match_second\":6239,\"minute\":103,\"minute_extra\":57,\"team_side\":\"home\",\"event_type_id\":5,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 17:26:38\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Free Kick\",\"event_type_key\":\"free_kick\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-02-01 17:26:38'),
(203, 663, 23, '{\"id\":663,\"match_id\":23,\"period_id\":12,\"match_second\":6277,\"minute\":104,\"minute_extra\":57,\"team_side\":\"home\",\"event_type_id\":15,\"importance\":3,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":467,\"player_id\":null,\"opponent_detail\":null,\"outcome\":\"on_target\",\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 17:27:13\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Shot\",\"event_type_key\":\"shot\",\"match_player_name\":\"Tyler Love\",\"match_player_shirt\":17,\"match_player_team_side\":\"home\",\"match_player_position\":\"ST\",\"period_label\":\"First Half\",\"player_first_name\":\"Tyler\",\"player_last_name\":\"Love\",\"tags\":[]}', '2026-02-01 17:27:13'),
(204, 664, 23, '{\"id\":664,\"match_id\":23,\"period_id\":12,\"match_second\":6280,\"minute\":104,\"minute_extra\":57,\"team_side\":\"home\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":455,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 17:27:22\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":\"Jamie Stirling\",\"match_player_shirt\":4,\"match_player_team_side\":\"home\",\"match_player_position\":\"CB\",\"period_label\":\"First Half\",\"player_first_name\":\"Jamie\",\"player_last_name\":\"Stirling\",\"tags\":[]}', '2026-02-01 17:27:22'),
(205, 665, 23, '{\"id\":665,\"match_id\":23,\"period_id\":12,\"match_second\":6478,\"minute\":107,\"minute_extra\":61,\"team_side\":\"away\",\"event_type_id\":4,\"importance\":2,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 17:29:26\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Corner\",\"event_type_key\":\"corner\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-02-01 17:29:26'),
(206, 666, 23, '{\"id\":666,\"match_id\":23,\"period_id\":12,\"match_second\":6513,\"minute\":108,\"minute_extra\":61,\"team_side\":\"away\",\"event_type_id\":16,\"importance\":5,\"phase\":\"unknown\",\"is_penalty\":0,\"match_player_id\":null,\"player_id\":null,\"opponent_detail\":null,\"outcome\":null,\"zone\":null,\"notes\":null,\"created_by\":1,\"created_at\":\"2026-02-01 17:29:48\",\"updated_by\":null,\"updated_at\":null,\"match_period_id\":null,\"clip_id\":null,\"clip_start_second\":null,\"clip_end_second\":null,\"event_type_label\":\"Goal\",\"event_type_key\":\"goal\",\"match_player_name\":\"Unknown Player\",\"match_player_shirt\":null,\"match_player_team_side\":null,\"match_player_position\":null,\"period_label\":\"First Half\",\"player_first_name\":null,\"player_last_name\":null,\"tags\":[]}', '2026-02-01 17:29:48');

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
(17, 1, 'off_side', 'Off Side', 3, '2025-12-20 21:03:35'),
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

--
-- Dumping data for table `league_intelligence_matches`
--

INSERT INTO `league_intelligence_matches` (`match_id`, `competition_id`, `season_id`, `home_team_id`, `away_team_id`, `kickoff_at`, `home_goals`, `away_goals`, `status`, `neutral_location`, `created_at`, `updated_at`) VALUES
(1, 2, 1, 4, 2, '2025-07-26 14:30:00', 1, 4, 'completed', 0, '2026-01-19 18:05:50', '2026-01-28 11:47:03'),
(2, 2, 1, 2, 6, '2025-07-30 19:30:00', 5, 1, 'completed', 0, '2026-01-19 18:05:50', '2026-01-21 20:00:25'),
(3, 2, 1, 7, 2, '2025-08-02 14:00:00', 4, 0, 'completed', 0, '2026-01-19 18:05:50', '2026-01-21 20:15:50'),
(4, 2, 1, 2, 8, '2025-08-09 14:00:00', 0, 2, 'completed', 0, '2026-01-19 18:05:50', '2026-01-21 20:23:37'),
(6, 2, 1, 2, 10, '2025-08-23 14:00:00', 0, 1, 'completed', 0, '2026-01-19 18:05:50', '2026-01-21 21:09:44'),
(7, 2, 1, 11, 2, '2025-08-30 14:00:00', 2, 1, 'completed', 0, '2026-01-19 18:05:50', '2026-01-21 21:16:02'),
(8, 2, 1, 12, 2, '2025-09-06 14:00:00', 2, 0, 'completed', 0, '2026-01-19 18:05:50', '2026-01-21 21:22:53'),
(10, 2, 1, 2, 14, '2025-09-27 14:00:00', 1, 3, 'completed', 0, '2026-01-19 18:05:50', '2026-01-29 19:44:12'),
(12, 2, 1, 16, 2, '2025-10-11 14:00:00', 0, 0, 'completed', 0, '2026-01-19 18:05:50', '2026-01-22 08:06:13'),
(14, 2, 1, 18, 2, '2025-10-25 14:00:00', 1, 2, 'completed', 0, '2026-01-19 18:05:50', '2026-01-22 08:37:03'),
(15, 2, 1, 2, 19, '2025-11-08 14:00:00', 2, 1, 'completed', 0, '2026-01-19 18:05:50', '2026-01-23 11:34:56'),
(16, 2, 1, 2, 20, '2025-11-22 13:30:00', 0, 1, 'completed', 0, '2026-01-19 18:05:50', NULL),
(17, 2, 1, 21, 2, '2025-11-29 13:30:00', 3, 4, 'completed', 0, '2026-01-19 18:05:50', '2026-01-23 11:34:16'),
(18, 2, 1, 3, 2, '2025-12-13 13:30:00', 1, 4, 'completed', 0, '2026-01-19 18:05:50', '2026-01-23 17:09:41'),
(19, 2, 1, 2, 4, '2026-01-17 13:30:00', 4, 0, 'completed', 0, '2026-01-17 20:37:29', '2026-01-27 19:29:14'),
(22, 2, 1, 6, 2, '2026-01-23 13:30:00', 0, 4, 'completed', 0, '2026-01-23 22:04:42', '2026-01-31 11:24:56'),
(23, 2, 1, 2, 7, '2026-01-31 14:00:00', 2, 6, 'completed', 0, '2026-01-28 14:50:45', '2026-02-01 17:30:49'),
(1000000000000, 2, 1, 39, 3, '2025-07-26 13:00:00', 1, 1, 'completed', 0, '2026-01-28 14:45:37', NULL),
(1000000000001, 2, 1, 39, 4, '2025-07-30 18:30:00', 3, 1, 'completed', 0, '2026-01-28 14:45:37', NULL),
(1000000000002, 2, 1, 6, 39, '2025-08-02 13:00:00', 1, 1, 'completed', 0, '2026-01-28 14:45:37', NULL),
(1000000000003, 2, 1, 10, 39, '2025-09-06 13:00:00', 3, 3, 'completed', 0, '2026-01-28 14:45:38', NULL),
(1000000000004, 2, 1, 3, 39, '2026-01-17 14:00:00', 1, 0, 'completed', 0, '2026-01-28 14:45:39', NULL),
(1000000000005, 2, 1, 4, 39, '2026-01-24 13:30:00', 0, 1, 'completed', 0, '2026-01-28 14:45:41', NULL),
(1000000000006, 2, 1, 39, 6, '2026-01-31 14:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:45:43', NULL),
(1000000000007, 2, 1, 39, 10, '2026-03-07 14:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:45:46', NULL),
(1000000000008, 2, 1, 11, 19, '2025-08-02 13:00:00', 1, 2, 'completed', 0, '2026-01-28 14:45:55', NULL),
(1000000000009, 2, 1, 19, 10, '2025-08-09 13:00:00', 2, 0, 'completed', 0, '2026-01-28 14:45:55', NULL),
(1000000000010, 2, 1, 3, 19, '2025-11-15 14:00:00', 1, 4, 'completed', 0, '2026-01-28 14:45:55', NULL),
(1000000000011, 2, 1, 19, 39, '2025-11-29 14:00:00', 2, 2, 'completed', 0, '2026-01-28 14:45:56', NULL),
(1000000000012, 2, 1, 19, 11, '2026-01-31 13:30:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:45:57', NULL),
(1000000000013, 2, 1, 19, 6, '2026-02-07 14:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:45:59', NULL),
(1000000000014, 2, 1, 10, 19, '2026-02-14 14:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:46:00', NULL),
(1000000000015, 2, 1, 20, 19, '2026-03-14 14:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:46:02', NULL),
(1000000000016, 2, 1, 19, 3, '2026-03-28 14:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:46:05', NULL),
(1000000000017, 2, 1, 39, 19, '2026-04-18 13:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:46:07', NULL),
(1000000000018, 2, 1, 19, 4, '2026-05-02 13:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:46:09', NULL),
(1000000000019, 2, 1, 6, 19, '2026-05-16 13:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:46:12', NULL),
(1000000000020, 2, 1, 6, 21, '2025-07-26 13:00:00', 1, 5, 'completed', 0, '2026-01-28 14:46:21', NULL),
(1000000000021, 2, 1, 21, 11, '2025-08-09 13:00:00', 3, 2, 'completed', 0, '2026-01-28 14:46:21', NULL),
(1000000000022, 2, 1, 10, 21, '2025-08-12 18:00:00', 4, 1, 'completed', 0, '2026-01-28 14:46:22', NULL),
(1000000000023, 2, 1, 21, 18, '2025-10-18 13:00:00', 1, 2, 'completed', 0, '2026-01-28 14:46:22', NULL),
(1000000000024, 2, 1, 19, 21, '2025-10-25 13:00:00', 1, 2, 'completed', 0, '2026-01-28 14:46:23', NULL),
(1000000000025, 2, 1, 3, 21, '2025-11-08 14:00:00', 1, 4, 'completed', 0, '2026-01-28 14:46:25', NULL),
(1000000000026, 2, 1, 21, 6, '2026-01-17 13:30:00', 1, 1, 'completed', 0, '2026-01-28 14:46:29', NULL),
(1000000000027, 2, 1, 21, 4, '2026-02-07 13:30:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:46:31', NULL),
(1000000000028, 2, 1, 21, 10, '2026-02-21 14:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:46:33', NULL),
(1000000000029, 2, 1, 21, 20, '2026-03-21 14:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:46:35', NULL),
(1000000000030, 2, 1, 18, 21, '2026-03-28 14:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:46:38', NULL),
(1000000000031, 2, 1, 21, 19, '2026-04-04 13:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:46:40', NULL),
(1000000000032, 2, 1, 21, 3, '2026-04-11 13:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:46:43', NULL),
(1000000000033, 2, 1, 2, 21, '2026-04-18 13:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:46:45', NULL),
(1000000000034, 2, 1, 21, 39, '2026-05-02 13:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:46:48', NULL),
(1000000000035, 2, 1, 4, 21, '2026-05-16 13:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:46:50', NULL),
(1000000000036, 2, 1, 8, 18, '2025-07-26 13:15:00', 1, 3, 'completed', 0, '2026-01-28 14:46:58', NULL),
(1000000000037, 2, 1, 19, 8, '2025-07-29 19:15:00', 4, 1, 'completed', 0, '2026-01-28 14:46:58', NULL),
(1000000000038, 2, 1, 8, 21, '2025-08-02 13:00:00', 3, 3, 'completed', 0, '2026-01-28 14:46:59', NULL),
(1000000000040, 2, 1, 4, 8, '2025-08-23 13:00:00', 2, 5, 'completed', 0, '2026-01-28 14:47:00', NULL),
(1000000000041, 2, 1, 8, 39, '2025-08-30 13:00:00', 0, 1, 'completed', 0, '2026-01-28 14:47:01', NULL),
(1000000000042, 2, 1, 8, 6, '2025-09-06 13:00:00', 7, 0, 'completed', 0, '2026-01-28 14:47:03', NULL),
(1000000000043, 2, 1, 8, 11, '2025-10-04 13:00:00', 1, 1, 'completed', 0, '2026-01-28 14:47:04', NULL),
(1000000000044, 2, 1, 8, 3, '2025-10-11 13:00:00', 3, 1, 'completed', 0, '2026-01-28 14:47:06', NULL),
(1000000000045, 2, 1, 10, 8, '2025-10-25 13:00:00', 1, 5, 'completed', 0, '2026-01-28 14:47:08', NULL),
(1000000000046, 2, 1, 8, 12, '2025-11-08 14:00:00', 3, 3, 'completed', 0, '2026-01-28 14:47:10', NULL),
(1000000000047, 2, 1, 14, 8, '2025-11-29 13:30:00', 0, 5, 'completed', 0, '2026-01-28 14:47:12', NULL),
(1000000000048, 2, 1, 8, 16, '2025-12-20 14:00:00', 5, 0, 'completed', 0, '2026-01-28 14:47:14', NULL),
(1000000000049, 2, 1, 18, 8, '2026-01-17 13:30:00', 1, 6, 'completed', 0, '2026-01-28 14:47:16', NULL),
(1000000000050, 2, 1, 8, 19, '2026-01-24 14:00:00', 2, 0, 'completed', 0, '2026-01-28 14:47:19', NULL),
(1000000000051, 2, 1, 21, 8, '2026-01-31 13:30:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:47:21', NULL),
(1000000000052, 2, 1, 20, 8, '2026-02-07 13:30:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:47:24', NULL),
(1000000000053, 2, 1, 8, 2, '2026-02-14 14:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:47:26', NULL),
(1000000000054, 2, 1, 39, 8, '2026-02-21 14:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:47:29', NULL),
(1000000000055, 2, 1, 8, 4, '2026-02-28 14:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:47:31', NULL),
(1000000000056, 2, 1, 6, 8, '2026-03-07 14:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:47:34', NULL),
(1000000000057, 2, 1, 3, 8, '2026-03-21 14:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:47:37', NULL),
(1000000000058, 2, 1, 11, 8, '2026-03-28 14:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:47:42', NULL),
(1000000000059, 2, 1, 8, 10, '2026-04-04 13:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:47:42', NULL),
(1000000000060, 2, 1, 12, 8, '2026-04-11 13:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:47:44', NULL),
(1000000000061, 2, 1, 8, 14, '2026-04-18 13:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:47:47', NULL),
(1000000000062, 2, 1, 16, 8, '2026-05-02 13:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:47:50', NULL),
(1000000000063, 2, 1, 8, 20, '2026-05-16 13:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:47:52', NULL),
(1000000000064, 2, 1, 10, 16, '2025-07-26 13:00:00', 0, 0, 'completed', 0, '2026-01-28 14:48:03', NULL),
(1000000000065, 2, 1, 14, 16, '2025-08-02 13:00:00', 0, 5, 'completed', 0, '2026-01-28 14:48:03', NULL),
(1000000000066, 2, 1, 3, 16, '2025-08-09 13:00:00', 0, 2, 'completed', 0, '2026-01-28 14:48:03', NULL),
(1000000000067, 2, 1, 16, 20, '2025-08-13 19:00:00', 1, 1, 'completed', 0, '2026-01-28 14:48:04', NULL),
(1000000000068, 2, 1, 18, 16, '2025-08-16 13:00:00', 3, 4, 'completed', 0, '2026-01-28 14:48:04', NULL),
(1000000000069, 2, 1, 16, 19, '2025-09-06 13:30:00', 0, 1, 'completed', 0, '2026-01-28 14:48:06', NULL),
(1000000000070, 2, 1, 21, 16, '2025-09-27 13:00:00', 6, 3, 'completed', 0, '2026-01-28 14:48:06', NULL),
(1000000000072, 2, 1, 16, 4, '2025-10-25 13:00:00', 3, 0, 'completed', 0, '2026-01-28 14:48:08', NULL),
(1000000000073, 2, 1, 16, 6, '2025-11-08 14:30:00', 4, 2, 'completed', 0, '2026-01-28 14:48:11', NULL),
(1000000000074, 2, 1, 16, 39, '2025-12-13 14:30:00', 1, 2, 'completed', 0, '2026-01-28 14:48:11', NULL),
(1000000000075, 2, 1, 16, 11, '2026-01-10 14:00:00', 4, 3, 'completed', 0, '2026-01-28 14:48:13', NULL),
(1000000000076, 2, 1, 16, 10, '2026-01-17 14:30:00', 2, 1, 'completed', 0, '2026-01-28 14:48:15', NULL),
(1000000000077, 2, 1, 16, 14, '2026-01-31 14:45:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:48:16', NULL),
(1000000000078, 2, 1, 16, 3, '2026-02-14 14:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:48:18', NULL),
(1000000000079, 2, 1, 20, 16, '2026-02-21 14:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:48:20', NULL),
(1000000000080, 2, 1, 16, 18, '2026-02-28 14:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:48:23', NULL),
(1000000000081, 2, 1, 19, 16, '2026-03-07 14:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:48:25', NULL),
(1000000000082, 2, 1, 16, 21, '2026-03-14 14:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:48:27', NULL),
(1000000000083, 2, 1, 2, 16, '2026-03-21 14:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:48:29', NULL),
(1000000000084, 2, 1, 4, 16, '2026-04-04 13:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:48:32', NULL),
(1000000000085, 2, 1, 6, 16, '2026-04-11 13:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:48:34', NULL),
(1000000000086, 2, 1, 11, 16, '2026-05-16 13:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:48:36', NULL),
(1000000000087, 2, 1, 39, 11, '2025-08-23 13:00:00', 3, 1, 'completed', 0, '2026-01-28 14:48:44', NULL),
(1000000000088, 2, 1, 11, 4, '2025-09-06 13:00:00', 6, 0, 'completed', 0, '2026-01-28 14:48:44', NULL),
(1000000000089, 2, 1, 6, 11, '2025-09-27 13:00:00', 1, 3, 'completed', 0, '2026-01-28 14:48:45', NULL),
(1000000000090, 2, 1, 11, 3, '2025-10-25 13:00:00', 4, 1, 'completed', 0, '2026-01-28 14:48:45', NULL),
(1000000000091, 2, 1, 11, 10, '2025-11-08 14:00:00', 1, 3, 'completed', 0, '2026-01-28 14:48:46', NULL),
(1000000000092, 2, 1, 10, 11, '2026-02-07 13:30:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:48:46', NULL),
(1000000000093, 2, 1, 11, 39, '2026-02-28 14:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:48:47', NULL),
(1000000000094, 2, 1, 4, 11, '2026-03-07 14:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:48:48', NULL),
(1000000000095, 2, 1, 11, 6, '2026-03-14 14:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:48:49', NULL),
(1000000000096, 2, 1, 3, 11, '2026-04-04 13:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:48:50', NULL),
(1000000000097, 2, 1, 18, 11, '2025-07-30 18:30:00', 6, 3, 'completed', 0, '2026-01-28 14:49:00', NULL),
(1000000000098, 2, 1, 10, 18, '2025-08-02 13:00:00', 3, 4, 'completed', 0, '2026-01-28 14:49:00', NULL),
(1000000000099, 2, 1, 20, 18, '2025-09-06 13:00:00', 2, 2, 'completed', 0, '2026-01-28 14:49:01', NULL),
(1000000000100, 2, 1, 3, 18, '2025-09-27 13:00:00', 3, 3, 'completed', 0, '2026-01-28 14:49:02', NULL),
(1000000000101, 2, 1, 18, 19, '2025-10-11 13:00:00', 3, 1, 'completed', 0, '2026-01-28 14:49:03', NULL),
(1000000000103, 2, 1, 18, 39, '2025-11-08 14:00:00', 2, 1, 'completed', 0, '2026-01-28 14:49:05', NULL),
(1000000000104, 2, 1, 18, 4, '2025-11-29 13:30:00', 4, 1, 'completed', 0, '2026-01-28 14:49:05', NULL),
(1000000000105, 2, 1, 11, 18, '2026-01-24 14:00:00', 1, 0, 'completed', 0, '2026-01-28 14:49:06', NULL),
(1000000000106, 2, 1, 18, 10, '2026-01-31 13:30:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:49:07', NULL),
(1000000000107, 2, 1, 18, 20, '2026-03-07 14:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:49:08', NULL),
(1000000000108, 2, 1, 18, 3, '2026-03-14 14:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:49:08', NULL),
(1000000000109, 2, 1, 19, 18, '2026-03-21 14:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:49:09', NULL),
(1000000000110, 2, 1, 2, 18, '2026-04-04 13:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:49:10', NULL),
(1000000000111, 2, 1, 39, 18, '2026-04-11 13:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:49:10', NULL),
(1000000000112, 2, 1, 4, 18, '2026-04-18 13:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:49:11', NULL),
(1000000000113, 2, 1, 18, 6, '2026-05-02 13:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:49:11', NULL),
(1000000000114, 2, 1, 10, 6, '2025-10-11 13:00:00', 5, 2, 'completed', 0, '2026-01-28 14:49:19', NULL),
(1000000000115, 2, 1, 3, 10, '2025-12-20 13:30:00', 1, 1, 'completed', 0, '2026-01-28 14:49:19', NULL),
(1000000000116, 2, 1, 10, 4, '2026-03-14 14:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:49:20', NULL),
(1000000000117, 2, 1, 6, 10, '2026-03-21 14:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:49:20', NULL),
(1000000000118, 2, 1, 10, 3, '2026-04-18 13:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:49:21', NULL),
(1000000000119, 2, 1, 4, 3, '2025-08-02 13:00:00', 0, 2, 'completed', 0, '2026-01-28 14:49:27', NULL),
(1000000000120, 2, 1, 3, 4, '2026-01-31 14:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:49:27', NULL),
(1000000000121, 2, 1, 11, 20, '2025-07-26 13:00:00', 1, 1, 'completed', 0, '2026-01-28 14:49:33', NULL),
(1000000000122, 2, 1, 20, 10, '2025-08-16 13:00:00', 2, 1, 'completed', 0, '2026-01-28 14:49:34', NULL),
(1000000000123, 2, 1, 3, 20, '2025-08-23 13:00:00', 2, 3, 'completed', 0, '2026-01-28 14:49:34', NULL),
(1000000000124, 2, 1, 20, 39, '2025-10-25 13:00:00', 0, 0, 'completed', 0, '2026-01-28 14:49:34', NULL),
(1000000000125, 2, 1, 20, 6, '2025-11-29 13:30:00', 4, 0, 'completed', 0, '2026-01-28 14:49:35', NULL),
(1000000000126, 2, 1, 20, 11, '2026-01-17 13:30:00', 5, 1, 'completed', 0, '2026-01-28 14:49:35', NULL),
(1000000000127, 2, 1, 10, 20, '2026-01-24 13:30:00', 2, 2, 'completed', 0, '2026-01-28 14:49:36', NULL),
(1000000000128, 2, 1, 20, 3, '2026-02-28 14:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:49:37', NULL),
(1000000000129, 2, 1, 39, 20, '2026-04-04 13:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:49:38', NULL),
(1000000000130, 2, 1, 20, 4, '2026-04-11 13:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:49:40', NULL),
(1000000000131, 2, 1, 6, 20, '2026-04-18 13:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:49:41', NULL),
(1000000000137, 2, 1, 2, 39, '2026-02-07 14:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:49:53', NULL),
(1000000000138, 2, 1, 2, 11, '2026-02-21 14:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:49:53', NULL),
(1000000000139, 2, 1, 10, 2, '2026-02-28 14:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:49:53', NULL),
(1000000000140, 2, 1, 20, 2, '2026-03-28 14:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:49:53', NULL),
(1000000000141, 2, 1, 19, 2, '2026-04-11 13:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:49:53', NULL),
(1000000000142, 2, 1, 2, 3, '2026-05-02 13:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:49:54', NULL),
(1000000000143, 2, 1, 39, 2, '2026-05-16 13:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:49:55', NULL),
(1000000000144, 2, 1, 20, 14, '2025-08-09 13:00:00', 1, 1, 'completed', 0, '2026-01-28 14:50:05', NULL),
(1000000000145, 2, 1, 14, 18, '2025-08-13 18:00:00', 1, 3, 'completed', 0, '2026-01-28 14:50:05', NULL),
(1000000000146, 2, 1, 19, 14, '2025-08-23 13:00:00', 2, 1, 'completed', 0, '2026-01-28 14:50:05', NULL),
(1000000000147, 2, 1, 3, 14, '2025-08-30 13:00:00', 0, 3, 'completed', 0, '2026-01-28 14:50:05', NULL),
(1000000000148, 2, 1, 14, 21, '2025-09-06 12:30:00', 1, 2, 'completed', 0, '2026-01-28 14:50:06', NULL),
(1000000000150, 2, 1, 14, 39, '2025-10-11 13:00:00', 6, 0, 'completed', 0, '2026-01-28 14:50:08', NULL),
(1000000000151, 2, 1, 4, 14, '2025-10-18 13:00:00', 1, 3, 'completed', 0, '2026-01-28 14:50:09', NULL),
(1000000000152, 2, 1, 14, 6, '2025-10-25 13:00:00', 5, 1, 'completed', 0, '2026-01-28 14:50:10', NULL),
(1000000000153, 2, 1, 14, 10, '2025-11-22 14:00:00', 3, 1, 'completed', 0, '2026-01-28 14:50:11', NULL),
(1000000000154, 2, 1, 11, 14, '2025-12-20 14:00:00', 1, 4, 'completed', 0, '2026-01-28 14:50:12', NULL),
(1000000000155, 2, 1, 14, 3, '2026-01-24 14:00:00', 3, 2, 'completed', 0, '2026-01-28 14:50:13', NULL),
(1000000000156, 2, 1, 14, 20, '2026-02-14 14:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:50:14', NULL),
(1000000000157, 2, 1, 18, 14, '2026-02-21 14:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:50:15', NULL),
(1000000000158, 2, 1, 14, 19, '2026-02-28 14:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:50:17', NULL),
(1000000000159, 2, 1, 21, 14, '2026-03-07 14:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:50:18', NULL),
(1000000000160, 2, 1, 14, 2, '2026-03-14 14:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:50:20', NULL),
(1000000000161, 2, 1, 39, 14, '2026-03-21 14:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:50:21', NULL),
(1000000000162, 2, 1, 14, 4, '2026-03-28 14:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:50:23', NULL),
(1000000000163, 2, 1, 6, 14, '2026-04-04 13:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:50:24', NULL),
(1000000000164, 2, 1, 14, 11, '2026-05-02 13:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:50:26', NULL),
(1000000000165, 2, 1, 10, 14, '2026-05-16 13:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:50:28', NULL),
(1000000000166, 2, 1, 19, 7, '2025-07-26 13:00:00', 4, 1, 'completed', 0, '2026-01-28 14:50:39', NULL),
(1000000000168, 2, 1, 39, 7, '2025-08-09 13:00:00', 0, 6, 'completed', 0, '2026-01-28 14:50:40', NULL),
(1000000000169, 2, 1, 7, 4, '2025-08-16 13:00:00', 8, 0, 'completed', 0, '2026-01-28 14:50:40', NULL),
(1000000000170, 2, 1, 6, 7, '2025-08-23 13:00:00', 1, 1, 'completed', 0, '2026-01-28 14:50:40', NULL),
(1000000000171, 2, 1, 21, 7, '2025-08-30 13:00:00', 6, 3, 'completed', 0, '2026-01-28 14:50:40', NULL),
(1000000000172, 2, 1, 7, 3, '2025-09-06 13:00:00', 2, 1, 'completed', 0, '2026-01-28 14:50:41', NULL),
(1000000000173, 2, 1, 7, 8, '2025-09-27 13:00:00', 2, 1, 'completed', 0, '2026-01-28 14:50:41', NULL),
(1000000000174, 2, 1, 7, 10, '2025-10-18 13:00:00', 2, 0, 'completed', 0, '2026-01-28 14:50:41', NULL),
(1000000000175, 2, 1, 12, 7, '2025-10-25 13:00:00', 2, 1, 'completed', 0, '2026-01-28 14:50:41', NULL),
(1000000000176, 2, 1, 11, 7, '2025-11-01 14:00:00', 2, 1, 'completed', 0, '2026-01-28 14:50:42', NULL),
(1000000000177, 2, 1, 7, 14, '2025-11-08 14:00:00', 3, 3, 'completed', 0, '2026-01-28 14:50:42', NULL),
(1000000000178, 2, 1, 7, 12, '2025-11-15 13:30:00', 4, 5, 'completed', 0, '2026-01-28 14:50:42', NULL),
(1000000000179, 2, 1, 16, 7, '2025-11-29 14:30:00', 1, 2, 'completed', 0, '2026-01-28 14:50:43', NULL),
(1000000000180, 2, 1, 7, 20, '2025-12-20 13:30:00', 3, 0, 'completed', 0, '2026-01-28 14:50:43', NULL),
(1000000000181, 2, 1, 7, 19, '2026-01-17 13:30:00', 4, 0, 'completed', 0, '2026-01-28 14:50:43', NULL),
(1000000000182, 2, 1, 7, 21, '2026-01-24 13:30:00', 3, 1, 'completed', 0, '2026-01-28 14:50:44', NULL),
(1000000000184, 2, 1, 18, 7, '2026-02-07 13:30:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:50:45', NULL),
(1000000000185, 2, 1, 7, 39, '2026-02-14 14:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:50:45', NULL),
(1000000000186, 2, 1, 4, 7, '2026-02-21 14:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:50:46', NULL),
(1000000000187, 2, 1, 7, 6, '2026-02-28 14:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:50:46', NULL),
(1000000000188, 2, 1, 3, 7, '2026-03-07 14:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:50:47', NULL),
(1000000000189, 2, 1, 8, 7, '2026-03-14 14:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:50:48', NULL),
(1000000000190, 2, 1, 7, 11, '2026-03-21 14:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:50:49', NULL),
(1000000000191, 2, 1, 10, 7, '2026-03-28 14:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:50:49', NULL),
(1000000000192, 2, 1, 14, 7, '2026-04-11 13:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:50:50', NULL),
(1000000000193, 2, 1, 7, 16, '2026-04-18 13:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:50:51', NULL),
(1000000000194, 2, 1, 20, 7, '2026-05-02 13:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:50:52', NULL),
(1000000000195, 2, 1, 7, 18, '2026-05-16 13:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:50:53', NULL),
(1000000000196, 2, 1, 12, 14, '2025-07-26 13:00:00', 4, 0, 'completed', 0, '2026-01-28 14:51:27', NULL),
(1000000000197, 2, 1, 12, 20, '2025-08-02 13:00:00', 1, 2, 'completed', 0, '2026-01-28 14:51:27', NULL),
(1000000000198, 2, 1, 18, 12, '2025-08-09 13:00:00', 2, 4, 'completed', 0, '2026-01-28 14:51:27', NULL),
(1000000000199, 2, 1, 12, 19, '2025-08-12 18:45:00', 1, 2, 'completed', 0, '2026-01-28 14:51:27', NULL),
(1000000000200, 2, 1, 21, 12, '2025-08-23 13:00:00', 2, 1, 'completed', 0, '2026-01-28 14:51:28', NULL),
(1000000000201, 2, 1, 16, 12, '2025-08-30 13:00:00', 2, 1, 'completed', 0, '2026-01-28 14:51:28', NULL),
(1000000000203, 2, 1, 39, 12, '2025-09-27 13:00:00', 2, 3, 'completed', 0, '2026-01-28 14:51:28', NULL),
(1000000000204, 2, 1, 12, 4, '2025-10-11 13:00:00', 4, 0, 'completed', 0, '2026-01-28 14:51:28', NULL),
(1000000000205, 2, 1, 6, 12, '2025-10-18 13:00:00', 2, 2, 'completed', 0, '2026-01-28 14:51:28', NULL),
(1000000000206, 2, 1, 10, 12, '2025-11-29 13:30:00', 1, 0, 'completed', 0, '2026-01-28 14:51:28', NULL),
(1000000000207, 2, 1, 12, 11, '2025-12-06 14:00:00', 3, 1, 'completed', 0, '2026-01-28 14:51:28', NULL),
(1000000000208, 2, 1, 14, 12, '2026-01-17 14:00:00', 4, 4, 'completed', 0, '2026-01-28 14:51:28', NULL),
(1000000000209, 2, 1, 12, 16, '2026-01-24 14:00:00', 3, 3, 'completed', 0, '2026-01-28 14:51:28', NULL),
(1000000000210, 2, 1, 20, 12, '2026-01-31 13:30:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:51:29', NULL),
(1000000000211, 2, 1, 12, 3, '2026-02-06 19:45:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:51:29', NULL),
(1000000000212, 2, 1, 12, 18, '2026-02-14 14:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:51:29', NULL),
(1000000000213, 2, 1, 19, 12, '2026-02-21 14:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:51:29', NULL),
(1000000000214, 2, 1, 12, 21, '2026-02-28 14:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:51:29', NULL),
(1000000000215, 2, 1, 2, 12, '2026-03-07 14:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:51:29', NULL),
(1000000000216, 2, 1, 12, 39, '2026-03-14 14:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:51:29', NULL),
(1000000000217, 2, 1, 4, 12, '2026-03-21 14:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:51:29', NULL),
(1000000000218, 2, 1, 12, 6, '2026-03-28 14:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:51:29', NULL),
(1000000000219, 2, 1, 11, 12, '2026-04-18 13:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:51:30', NULL),
(1000000000220, 2, 1, 12, 10, '2026-05-02 13:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:51:30', NULL),
(1000000000221, 2, 1, 3, 12, '2026-05-16 13:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:51:30', NULL),
(1000000000222, 2, 1, 4, 6, '2025-08-09 13:00:00', 0, 1, 'completed', 0, '2026-01-28 14:51:35', NULL),
(1000000000223, 2, 1, 6, 3, '2025-08-16 13:00:00', 2, 0, 'completed', 0, '2026-01-28 14:51:35', NULL),
(1000000000224, 2, 1, 6, 4, '2026-02-14 14:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:51:35', NULL),
(1000000000225, 2, 1, 3, 6, '2026-02-21 14:00:00', NULL, NULL, 'scheduled', 0, '2026-01-28 14:51:35', NULL);

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
(1, 1, 1, 2, 4, 2, '2025-07-26 14:30:00', NULL, NULL, NULL, NULL, 'ready', NULL, 23, 0, 0, 1, '2026-01-19 18:05:50', '2026-01-28 11:47:03'),
(2, 1, 1, 2, 2, 6, '2025-07-30 19:30:00', NULL, NULL, NULL, NULL, 'ready', NULL, 12, 0, 0, 1, '2026-01-19 18:05:50', '2026-01-21 20:00:25'),
(3, 1, 1, 2, 7, 2, '2025-08-02 14:00:00', NULL, NULL, NULL, NULL, 'ready', NULL, 1, 0, 0, 1, '2026-01-19 18:05:50', '2026-01-21 20:15:50'),
(4, 1, 1, 2, 2, 8, '2025-08-09 14:00:00', NULL, NULL, NULL, NULL, 'ready', NULL, 1, 0, 0, 1, '2026-01-19 18:05:50', '2026-01-21 20:23:37'),
(5, 1, 1, 3, 9, 2, '2025-08-16 14:30:00', NULL, NULL, NULL, NULL, 'ready', NULL, 4, 0, 0, 1, '2026-01-19 18:05:50', '2026-01-21 21:02:46'),
(6, 1, 1, 2, 2, 10, '2025-08-23 14:00:00', NULL, NULL, NULL, NULL, 'ready', NULL, 3, 0, 0, 1, '2026-01-19 18:05:50', '2026-01-21 21:09:44'),
(7, 1, 1, 2, 11, 2, '2025-08-30 14:00:00', NULL, NULL, NULL, NULL, 'ready', NULL, 4, 0, 0, 1, '2026-01-19 18:05:50', '2026-01-21 21:16:02'),
(8, 1, 1, 2, 12, 2, '2025-09-06 14:00:00', NULL, NULL, NULL, NULL, 'ready', NULL, 2, 0, 0, 1, '2026-01-19 18:05:50', '2026-01-21 21:22:53'),
(9, 1, 1, 4, 13, 2, '2025-09-20 14:30:00', NULL, NULL, NULL, NULL, 'ready', NULL, 4, 0, 0, 1, '2026-01-19 18:05:50', '2026-01-21 21:27:55'),
(10, 1, 1, 2, 2, 14, '2025-09-27 14:00:00', NULL, NULL, NULL, NULL, 'ready', NULL, 6, 0, 10, 1, '2026-01-19 18:05:50', '2026-01-29 19:44:12'),
(11, 1, 1, 5, 2, 15, '2025-10-04 14:00:00', NULL, 'Campbell Park', NULL, NULL, 'ready', NULL, 7, 0, 0, 1, '2026-01-19 18:05:50', '2026-01-22 07:54:05'),
(12, 1, 1, 2, 16, 2, '2025-10-11 14:00:00', NULL, NULL, NULL, NULL, 'ready', NULL, 2, 0, 0, 1, '2026-01-19 18:05:50', '2026-01-22 08:06:13'),
(13, 1, 1, 4, 17, 2, '2025-10-18 14:30:00', NULL, NULL, NULL, NULL, 'ready', NULL, 3, 0, 0, 1, '2026-01-19 18:05:50', '2026-01-22 08:32:13'),
(14, 1, 1, 2, 18, 2, '2025-10-25 14:00:00', NULL, NULL, NULL, NULL, 'ready', NULL, 5, 0, 0, 1, '2026-01-19 18:05:50', '2026-01-22 08:37:03'),
(15, 1, 1, 2, 2, 19, '2025-11-08 14:00:00', NULL, NULL, NULL, NULL, 'ready', NULL, 2, 0, 0, 1, '2026-01-19 18:05:50', '2026-01-23 11:34:56'),
(16, 1, 1, 2, 2, 20, '2025-11-22 13:30:00', NULL, NULL, NULL, NULL, 'ready', NULL, 0, 0, 0, 1, '2026-01-19 18:05:50', NULL),
(17, 1, 1, 2, 21, 2, '2025-11-29 13:30:00', NULL, NULL, NULL, NULL, 'ready', NULL, 31, 0, 27, 1, '2026-01-19 18:05:50', '2026-01-23 11:34:16'),
(18, 1, 1, 2, 3, 2, '2025-12-13 13:30:00', NULL, NULL, NULL, NULL, 'ready', NULL, 5, 0, 5, 1, '2026-01-19 18:05:50', '2026-01-23 17:09:41'),
(19, 1, 1, 2, 2, 4, '2026-01-17 13:30:00', NULL, 'Winton Park', NULL, NULL, 'ready', NULL, 89, 0, 65, 1, '2026-01-17 20:37:29', '2026-01-27 19:29:14'),
(22, 1, 1, 2, 6, 2, '2026-01-23 13:30:00', NULL, NULL, NULL, NULL, 'ready', NULL, 74, 63, 68, 1, '2026-01-23 22:04:42', '2026-01-31 11:24:56'),
(23, 1, 1, 2, 2, 7, '2026-01-31 14:00:00', NULL, NULL, NULL, NULL, 'ready', NULL, 68, 64, 67, 1, '2026-01-28 16:48:26', '2026-02-01 17:30:49');

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
(24, 19, 'away', NULL, 0, 0, 0, '11-a-side', '3-4-3', NULL, NULL, 1, '2026-01-19 18:28:52', '2026-01-23 13:49:18'),
(25, 19, 'home', NULL, 0, 0, 0, '11-a-side', '3-4-3', NULL, NULL, 1, '2026-01-19 18:28:53', '2026-01-23 13:49:18'),
(26, 1, 'home', NULL, 0, 0, 0, '11-a-side', '3-4-3', NULL, NULL, 1, '2026-01-20 14:08:06', NULL),
(27, 1, 'away', NULL, 0, 0, 0, '11-a-side', '3-4-3', NULL, NULL, 1, '2026-01-20 14:08:06', NULL),
(28, 22, 'home', NULL, 0, 0, 0, '11-a-side', '3-4-3', NULL, NULL, 1, '2026-01-26 16:45:18', '2026-01-27 17:53:26'),
(29, 22, 'away', NULL, 0, 0, 0, '11-a-side', '3-4-3', NULL, NULL, 1, '2026-01-26 16:45:18', '2026-01-27 17:53:26'),
(30, 23, 'home', NULL, 0, 0, 0, '11-a-side', '3-4-3', NULL, NULL, 1, '2026-02-01 19:00:13', '2026-02-01 19:00:46'),
(31, 23, 'away', NULL, 0, 0, 0, '11-a-side', '3-4-3', NULL, NULL, 1, '2026-02-01 19:00:14', '2026-02-01 19:00:46');

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
(1, 1, '2026-01-20 14:08:02', '2026-01-21 18:17:58'),
(10, 2, '2026-01-29 19:44:12', '2026-01-29 19:44:47'),
(13, 2, '2026-01-21 20:25:21', '2026-01-21 20:25:21'),
(16, 1, '2026-01-20 07:34:15', '2026-01-20 07:34:25'),
(17, 1, '2026-01-22 10:10:37', '2026-01-22 10:10:37'),
(18, 1, '2026-01-23 09:42:59', '2026-01-23 17:13:24'),
(19, 1, '2026-01-21 20:31:36', '2026-01-31 11:56:39'),
(22, 2, '2026-01-31 11:32:39', '2026-01-31 11:55:47'),
(23, 1, '2026-01-29 08:36:53', '2026-02-01 19:00:45');

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
(7, 19, 'first_half', 'First Half', 14, 2710, '2026-01-17 20:45:26', '2026-01-17 22:23:51'),
(8, 19, 'second_half', 'Second Half', 3705, 6241, '2026-01-17 22:26:56', '2026-01-17 23:38:06'),
(9, 22, 'first_half', 'First Half', 82, 2811, '2026-01-25 15:01:41', '2026-01-25 15:46:15'),
(11, 22, 'second_half', 'Second Half', 3687, 6470, '2026-01-25 17:02:31', '2026-01-25 17:02:56'),
(12, 23, 'first_half', 'First Half', 6, 2863, '2026-02-01 16:13:11', '2026-02-01 16:57:33'),
(13, 23, 'second_half', 'Second Half', 3777, 6748, '2026-02-01 16:58:23', '2026-02-01 17:42:34');

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

--
-- Dumping data for table `match_players`
--

INSERT INTO `match_players` (`id`, `match_id`, `team_side`, `player_id`, `shirt_number`, `position_label`, `is_starting`, `created_at`, `is_captain`) VALUES
(59, 19, 'home', 1, 1, 'GK', 1, '2026-01-17 21:02:50', 0),
(60, 19, 'home', 3, 2, 'CB', 1, '2026-01-17 21:03:21', 0),
(61, 19, 'home', 4, 3, 'CB', 1, '2026-01-17 21:03:28', 0),
(62, 19, 'home', 15, 4, 'CB', 1, '2026-01-17 21:03:37', 0),
(63, 19, 'home', 20, 5, 'LM', 1, '2026-01-17 21:04:10', 0),
(64, 19, 'home', 21, 6, 'CM', 1, '2026-01-17 21:04:45', 1),
(65, 19, 'home', 7, 7, 'CM', 1, '2026-01-17 21:04:57', 0),
(66, 19, 'home', 9, 8, 'CM', 1, '2026-01-17 21:05:11', 0),
(67, 19, 'home', 22, 9, 'RM', 1, '2026-01-17 21:05:47', 0),
(68, 19, 'home', 10, 10, 'ST', 1, '2026-01-17 21:05:57', 0),
(69, 19, 'home', 23, 11, 'ST', 1, '2026-01-17 21:06:27', 0),
(70, 19, 'home', 12, NULL, NULL, 0, '2026-01-17 21:06:40', 0),
(71, 19, 'home', 17, NULL, NULL, 0, '2026-01-17 21:06:43', 0),
(72, 19, 'home', 6, NULL, NULL, 0, '2026-01-17 21:06:48', 0),
(73, 19, 'home', 18, NULL, NULL, 0, '2026-01-17 21:08:20', 0),
(91, 1, 'away', 25, 1, 'GK', 1, '2026-01-21 15:58:01', 0),
(92, 1, 'away', 6, 2, NULL, 1, '2026-01-21 16:54:09', 0),
(93, 1, 'away', 20, 3, NULL, 1, '2026-01-21 17:29:32', 0),
(94, 1, 'away', 26, 4, NULL, 1, '2026-01-21 17:30:13', 0),
(95, 1, 'away', 15, 5, NULL, 1, '2026-01-21 17:31:23', 0),
(96, 1, 'away', 21, 6, NULL, 1, '2026-01-21 17:38:33', 0),
(99, 1, 'away', 5, 7, NULL, 1, '2026-01-21 17:47:48', 0),
(100, 1, 'away', 27, 8, NULL, 1, '2026-01-21 17:47:55', 1),
(101, 1, 'away', 18, 9, NULL, 1, '2026-01-21 17:48:02', 0),
(102, 1, 'away', 28, 10, NULL, 1, '2026-01-21 17:48:18', 0),
(103, 1, 'away', 29, 11, NULL, 1, '2026-01-21 17:48:34', 0),
(105, 1, 'away', 30, 12, NULL, 0, '2026-01-21 17:49:45', 0),
(106, 1, 'away', 31, 14, NULL, 0, '2026-01-21 17:49:54', 0),
(107, 1, 'away', 4, 15, NULL, 0, '2026-01-21 17:50:00', 0),
(108, 1, 'away', 10, 16, NULL, 0, '2026-01-21 17:50:06', 0),
(109, 1, 'away', 12, 17, NULL, 0, '2026-01-21 17:50:17', 0),
(110, 1, 'away', 2, 18, 'ST', 0, '2026-01-21 17:50:23', 0),
(111, 1, 'away', 32, 19, NULL, 0, '2026-01-21 17:50:34', 0),
(112, 2, 'home', 1, 1, 'GK', 1, '2026-01-21 19:16:14', 0),
(113, 2, 'home', 2, 2, 'ST', 1, '2026-01-21 19:16:21', 0),
(114, 2, 'home', 6, 3, NULL, 1, '2026-01-21 19:16:26', 0),
(115, 2, 'home', 26, 4, NULL, 0, '2026-01-21 19:16:31', 0),
(116, 2, 'home', 15, 5, NULL, 1, '2026-01-21 19:16:36', 0),
(117, 2, 'home', 27, 6, NULL, 0, '2026-01-21 19:17:07', 1),
(118, 2, 'home', 5, 7, NULL, 1, '2026-01-21 19:17:16', 0),
(119, 2, 'home', 4, 8, NULL, 1, '2026-01-21 19:17:22', 0),
(120, 2, 'home', 18, 9, NULL, 0, '2026-01-21 19:17:30', 0),
(121, 2, 'home', 28, 10, NULL, 0, '2026-01-21 19:17:37', 0),
(122, 2, 'home', 29, 11, NULL, 1, '2026-01-21 19:17:45', 0),
(123, 2, 'home', 30, 12, NULL, 0, '2026-01-21 19:18:05', 0),
(124, 2, 'home', 31, 14, NULL, 0, '2026-01-21 19:18:13', 0),
(125, 2, 'home', 12, 15, NULL, 1, '2026-01-21 19:18:20', 0),
(126, 2, 'home', 10, 16, NULL, 1, '2026-01-21 19:18:29', 0),
(127, 2, 'home', 32, 17, NULL, 1, '2026-01-21 19:18:37', 0),
(128, 2, 'home', 13, 18, NULL, 0, '2026-01-21 19:18:59', 0),
(129, 2, 'home', 3, 19, 'GK', 1, '2026-01-21 19:41:02', 0),
(130, 3, 'away', 1, 1, 'GK', 1, '2026-01-21 20:08:17', 0),
(131, 3, 'away', 6, 2, NULL, 1, '2026-01-21 20:08:21', 0),
(132, 3, 'away', 34, 3, NULL, 0, '2026-01-21 20:08:39', 0),
(133, 3, 'away', 26, 4, NULL, 1, '2026-01-21 20:08:45', 0),
(134, 3, 'away', 15, 5, NULL, 1, '2026-01-21 20:08:51', 0),
(135, 3, 'away', 27, 6, NULL, 1, '2026-01-21 20:08:56', 1),
(136, 3, 'away', 5, 7, NULL, 0, '2026-01-21 20:09:02', 0),
(137, 3, 'away', 4, 8, NULL, 0, '2026-01-21 20:09:08', 0),
(138, 3, 'away', 18, 9, NULL, 1, '2026-01-21 20:09:13', 0),
(139, 3, 'away', 28, 10, NULL, 0, '2026-01-21 20:09:20', 0),
(140, 3, 'away', 29, 11, NULL, 1, '2026-01-21 20:09:27', 0),
(141, 3, 'away', 30, 12, NULL, 0, '2026-01-21 20:09:46', 0),
(142, 3, 'away', 3, 14, 'GK', 1, '2026-01-21 20:09:54', 0),
(143, 3, 'away', 12, 15, NULL, 1, '2026-01-21 20:10:04', 0),
(144, 3, 'away', 10, 16, NULL, 1, '2026-01-21 20:10:12', 0),
(145, 3, 'away', 11, 17, NULL, 1, '2026-01-21 20:10:19', 0),
(146, 3, 'away', 2, 18, 'ST', 0, '2026-01-21 20:10:30', 0),
(147, 4, 'home', 19, 1, NULL, 1, '2026-01-21 20:20:34', 0),
(148, 4, 'home', 5, 2, NULL, 0, '2026-01-21 20:20:38', 0),
(149, 4, 'home', 20, 3, NULL, 1, '2026-01-21 20:20:43', 0),
(150, 4, 'home', 15, 4, NULL, 1, '2026-01-21 20:20:48', 0),
(151, 4, 'home', 3, 5, 'GK', 1, '2026-01-21 20:20:53', 0),
(152, 4, 'home', 26, 6, NULL, 1, '2026-01-21 20:21:01', 0),
(153, 4, 'home', 12, 7, NULL, 0, '2026-01-21 20:21:07', 0),
(154, 4, 'home', 27, 8, NULL, 1, '2026-01-21 20:21:13', 1),
(155, 4, 'home', 18, 9, NULL, 1, '2026-01-21 20:21:19', 0),
(156, 4, 'home', 11, 10, NULL, 1, '2026-01-21 20:21:25', 0),
(157, 4, 'home', 4, 11, NULL, 0, '2026-01-21 20:21:30', 0),
(158, 4, 'home', 28, 12, NULL, 1, '2026-01-21 20:21:45', 0),
(159, 4, 'home', 30, 14, NULL, 1, '2026-01-21 20:21:51', 0),
(160, 4, 'home', 10, 15, NULL, 0, '2026-01-21 20:21:56', 0),
(161, 4, 'home', 32, 16, NULL, 0, '2026-01-21 20:22:01', 0),
(162, 4, 'home', 31, 17, NULL, 0, '2026-01-21 20:22:06', 0),
(163, 4, 'home', 13, 18, NULL, 1, '2026-01-21 20:22:12', 0),
(164, 4, 'home', 35, 19, NULL, 0, '2026-01-21 20:22:32', 0),
(165, 5, 'away', 1, 1, 'GK', 1, '2026-01-21 20:25:22', 0),
(166, 5, 'away', 7, 2, NULL, 0, '2026-01-21 20:25:38', 0),
(167, 5, 'away', 36, 3, NULL, 0, '2026-01-21 20:25:58', 0),
(168, 5, 'away', 26, 4, NULL, 0, '2026-01-21 20:26:05', 1),
(169, 5, 'away', 3, 5, 'GK', 0, '2026-01-21 20:26:12', 0),
(170, 17, 'home', 15, 1, 'RW', 1, '2026-01-21 20:31:47', 0),
(171, 5, 'away', 13, 6, NULL, 0, '2026-01-21 20:43:43', 0),
(172, 5, 'away', 18, 7, NULL, 1, '2026-01-21 20:43:48', 0),
(173, 5, 'away', 22, 8, NULL, 1, '2026-01-21 20:47:38', 0),
(174, 5, 'away', 35, 9, NULL, 1, '2026-01-21 20:53:06', 0),
(175, 5, 'away', 11, 10, NULL, 1, '2026-01-21 20:56:53', 0),
(176, 5, 'away', 10, 11, NULL, 1, '2026-01-21 20:56:56', 0),
(177, 5, 'away', 30, 12, NULL, 1, '2026-01-21 20:57:30', 0),
(178, 5, 'away', 17, 14, NULL, 0, '2026-01-21 20:57:38', 0),
(179, 5, 'away', 4, 15, NULL, 1, '2026-01-21 20:57:48', 0),
(180, 5, 'away', 15, 16, NULL, 1, '2026-01-21 20:57:56', 0),
(181, 5, 'away', 12, 17, NULL, 1, '2026-01-21 20:58:01', 0),
(182, 5, 'away', 32, 18, NULL, 0, '2026-01-21 20:58:10', 0),
(183, 5, 'away', 20, 19, NULL, 1, '2026-01-21 20:58:16', 0),
(184, 6, 'home', 1, 1, 'GK', 1, '2026-01-21 21:06:31', 0),
(185, 6, 'home', 7, 2, NULL, 1, '2026-01-21 21:06:37', 0),
(186, 6, 'home', 36, 3, NULL, 0, '2026-01-21 21:06:44', 0),
(187, 6, 'home', 15, 4, NULL, 1, '2026-01-21 21:06:49', 0),
(188, 6, 'home', 3, 5, 'GK', 1, '2026-01-21 21:06:55', 0),
(189, 6, 'home', 21, 6, NULL, 1, '2026-01-21 21:07:00', 0),
(190, 6, 'home', 10, 7, NULL, 1, '2026-01-21 21:07:06', 0),
(191, 6, 'home', 22, 8, NULL, 0, '2026-01-21 21:07:15', 0),
(192, 6, 'home', 35, 9, NULL, 0, '2026-01-21 21:07:24', 1),
(193, 6, 'home', 11, 10, NULL, 0, '2026-01-21 21:07:31', 0),
(194, 6, 'home', 18, 11, NULL, 1, '2026-01-21 21:07:39', 0),
(195, 6, 'home', 37, 12, NULL, 1, '2026-01-21 21:08:12', 0),
(196, 6, 'home', 29, 14, NULL, 1, '2026-01-21 21:08:16', 0),
(197, 6, 'home', 13, 15, NULL, 1, '2026-01-21 21:08:22', 0),
(198, 6, 'home', 23, 16, NULL, 1, '2026-01-21 21:08:27', 0),
(199, 6, 'home', 17, 17, NULL, 0, '2026-01-21 21:08:34', 0),
(200, 6, 'home', 20, 18, NULL, 0, '2026-01-21 21:08:51', 0),
(201, 6, 'home', 4, 19, NULL, 0, '2026-01-21 21:08:57', 0),
(202, 7, 'away', 1, 1, 'GK', 1, '2026-01-21 21:13:05', 0),
(203, 7, 'away', 2, 2, 'ST', 0, '2026-01-21 21:13:10', 0),
(204, 7, 'away', 29, 3, NULL, 0, '2026-01-21 21:13:15', 0),
(205, 7, 'away', 3, 4, 'GK', 0, '2026-01-21 21:13:20', 0),
(206, 7, 'away', 15, 5, NULL, 1, '2026-01-21 21:13:26', 0),
(207, 7, 'away', 21, 6, NULL, 1, '2026-01-21 21:13:32', 0),
(208, 7, 'away', 10, 7, NULL, 0, '2026-01-21 21:13:38', 0),
(209, 7, 'away', 6, 8, NULL, 1, '2026-01-21 21:13:44', 0),
(210, 7, 'away', 35, 9, NULL, 1, '2026-01-21 21:13:52', 1),
(211, 7, 'away', 37, 10, NULL, 1, '2026-01-21 21:13:59', 0),
(212, 7, 'away', 18, 11, NULL, 0, '2026-01-21 21:14:04', 0),
(213, 7, 'away', 17, 12, NULL, 1, '2026-01-21 21:14:16', 0),
(214, 7, 'away', 20, 14, NULL, 0, '2026-01-21 21:14:25', 0),
(215, 7, 'away', 11, 15, NULL, 1, '2026-01-21 21:14:33', 0),
(216, 7, 'away', 23, 16, NULL, 1, '2026-01-21 21:14:43', 0),
(217, 7, 'away', 13, 17, NULL, 1, '2026-01-21 21:14:48', 0),
(218, 7, 'away', 36, 18, NULL, 1, '2026-01-21 21:14:58', 0),
(219, 7, 'away', 12, 19, NULL, 0, '2026-01-21 21:15:04', 0),
(220, 8, 'away', 1, 1, 'GK', 1, '2026-01-21 21:19:14', 0),
(221, 8, 'away', 6, 2, NULL, 1, '2026-01-21 21:19:24', 0),
(222, 8, 'away', 36, 3, NULL, 0, '2026-01-21 21:19:30', 0),
(223, 8, 'away', 15, 4, NULL, 1, '2026-01-21 21:20:23', 0),
(224, 8, 'away', 17, 5, NULL, 1, '2026-01-21 21:20:31', 1),
(225, 8, 'away', 21, 6, NULL, 1, '2026-01-21 21:20:39', 0),
(226, 8, 'away', 5, 7, NULL, 0, '2026-01-21 21:20:44', 0),
(227, 8, 'away', 22, 8, NULL, 0, '2026-01-21 21:20:49', 0),
(228, 8, 'away', 11, 9, NULL, 1, '2026-01-21 21:20:55', 0),
(229, 8, 'away', 18, 10, NULL, 1, '2026-01-21 21:21:00', 0),
(230, 8, 'away', 29, 11, NULL, 0, '2026-01-21 21:21:08', 0),
(231, 8, 'away', 12, 12, NULL, 1, '2026-01-21 21:21:31', 0),
(232, 8, 'away', 13, 14, NULL, 1, '2026-01-21 21:21:36', 0),
(233, 8, 'away', 20, 15, NULL, 1, '2026-01-21 21:21:45', 0),
(234, 8, 'away', 23, 16, NULL, 1, '2026-01-21 21:21:51', 0),
(235, 8, 'away', 4, 17, NULL, 0, '2026-01-21 21:22:09', 0),
(236, 8, 'away', 19, 18, 'GK', 0, '2026-01-21 21:22:23', 0),
(237, 9, 'away', 1, 1, 'GK', 1, '2026-01-21 21:24:59', 0),
(238, 9, 'away', 22, 2, NULL, 0, '2026-01-21 21:25:04', 0),
(239, 9, 'away', 29, 3, NULL, 0, '2026-01-21 21:25:09', 0),
(240, 9, 'away', 15, 4, NULL, 1, '2026-01-21 21:25:14', 0),
(241, 9, 'away', 3, 5, 'GK', 1, '2026-01-21 21:25:19', 0),
(242, 9, 'away', 4, 6, NULL, 1, '2026-01-21 21:25:24', 0),
(243, 9, 'away', 17, 7, NULL, 0, '2026-01-21 21:25:32', 1),
(244, 9, 'away', 21, 8, NULL, 0, '2026-01-21 21:25:38', 0),
(245, 9, 'away', 37, 9, NULL, 1, '2026-01-21 21:25:45', 0),
(246, 9, 'away', 11, 10, NULL, 0, '2026-01-21 21:25:51', 0),
(247, 9, 'away', 23, 11, NULL, 1, '2026-01-21 21:25:56', 0),
(248, 9, 'away', 2, 12, 'ST', 1, '2026-01-21 21:26:14', 0),
(249, 9, 'away', 36, 14, NULL, 0, '2026-01-21 21:26:23', 0),
(250, 9, 'away', 12, 15, NULL, 1, '2026-01-21 21:26:30', 0),
(251, 9, 'away', 20, 16, NULL, 1, '2026-01-21 21:26:37', 0),
(252, 9, 'away', 5, 17, NULL, 1, '2026-01-21 21:26:46', 0),
(253, 9, 'away', 13, 18, NULL, 1, '2026-01-21 21:26:52', 0),
(254, 9, 'away', 19, 19, NULL, 0, '2026-01-21 21:26:57', 0),
(256, 10, 'home', 5, 2, NULL, 1, '2026-01-22 06:58:11', 0),
(257, 10, 'home', 29, 3, NULL, 0, '2026-01-22 06:58:18', 0),
(258, 10, 'home', 15, 4, NULL, 1, '2026-01-22 06:58:24', 0),
(259, 10, 'home', 3, 5, 'GK', 1, '2026-01-22 06:58:29', 0),
(260, 10, 'home', 4, 6, NULL, 1, '2026-01-22 06:58:35', 0),
(262, 10, 'home', 21, 8, NULL, 0, '2026-01-22 06:58:45', 0),
(263, 10, 'home', 37, 9, NULL, 1, '2026-01-22 06:58:51', 0),
(264, 10, 'home', 11, 10, NULL, 1, '2026-01-22 06:58:56', 0),
(265, 10, 'home', 1, 1, 'GK', 1, '2026-01-22 06:59:27', 0),
(266, 10, 'home', 17, 7, NULL, 1, '2026-01-22 06:59:52', 1),
(267, 10, 'home', 23, 11, NULL, 1, '2026-01-22 06:59:59', 0),
(268, 10, 'home', 12, 12, NULL, 1, '2026-01-22 07:00:16', 0),
(269, 10, 'home', 36, 14, NULL, 1, '2026-01-22 07:00:32', 0),
(270, 10, 'home', 19, 15, NULL, 0, '2026-01-22 07:00:41', 0),
(271, 10, 'home', 2, 16, 'ST', 0, '2026-01-22 07:00:49', 0),
(272, 10, 'home', 20, 17, NULL, 0, '2026-01-22 07:00:57', 0),
(273, 10, 'home', 13, 18, NULL, 0, '2026-01-22 07:01:02', 0),
(274, 10, 'home', 10, 19, NULL, 0, '2026-01-22 07:01:12', 0),
(275, 11, 'home', 19, 1, NULL, 1, '2026-01-22 07:15:39', 0),
(276, 11, 'home', 22, 2, NULL, 0, '2026-01-22 07:46:05', 0),
(277, 11, 'home', 36, 3, NULL, 0, '2026-01-22 07:50:06', 0),
(278, 11, 'home', 15, 4, NULL, 1, '2026-01-22 07:50:12', 0),
(279, 11, 'home', 3, 5, NULL, 0, '2026-01-22 07:50:33', 0),
(280, 11, 'home', 4, 6, NULL, 1, '2026-01-22 07:50:40', 0),
(281, 11, 'home', 17, 7, NULL, 0, '2026-01-22 07:51:01', 1),
(282, 11, 'home', 21, 8, NULL, 0, '2026-01-22 07:51:12', 0),
(283, 11, 'home', 38, 9, NULL, 1, '2026-01-22 07:51:25', 0),
(284, 11, 'home', 11, 10, NULL, 1, '2026-01-22 07:51:31', 0),
(285, 11, 'home', 23, 11, NULL, 1, '2026-01-22 07:51:39', 0),
(286, 11, 'home', 12, 12, NULL, 0, '2026-01-22 07:51:59', 0),
(287, 11, 'home', 20, 14, NULL, 1, '2026-01-22 07:52:05', 0),
(288, 11, 'home', 29, 15, NULL, 1, '2026-01-22 07:52:12', 0),
(289, 11, 'home', 13, 16, NULL, 1, '2026-01-22 07:52:18', 0),
(290, 11, 'home', 2, 17, NULL, 1, '2026-01-22 07:52:24', 0),
(291, 11, 'home', 10, 18, NULL, 1, '2026-01-22 07:52:29', 0),
(292, 11, 'home', 1, 19, 'GK', 0, '2026-01-22 07:52:40', 0),
(293, 12, 'away', 1, 1, 'GK', 1, '2026-01-22 08:03:30', 0),
(294, 12, 'away', 2, 2, NULL, 0, '2026-01-22 08:03:36', 0),
(295, 12, 'away', 29, 3, NULL, 0, '2026-01-22 08:03:41', 0),
(296, 12, 'away', 15, 4, NULL, 1, '2026-01-22 08:03:46', 0),
(297, 12, 'away', 3, 5, NULL, 0, '2026-01-22 08:03:50', 0),
(298, 12, 'away', 4, 6, NULL, 1, '2026-01-22 08:03:55', 0),
(299, 12, 'away', 17, 7, NULL, 1, '2026-01-22 08:04:01', 1),
(300, 12, 'away', 21, 8, NULL, 0, '2026-01-22 08:04:10', 0),
(301, 12, 'away', 38, 9, NULL, 1, '2026-01-22 08:04:17', 0),
(302, 12, 'away', 11, 10, NULL, 0, '2026-01-22 08:04:21', 0),
(303, 12, 'away', 23, 11, NULL, 1, '2026-01-22 08:04:32', 0),
(304, 12, 'away', 36, 12, NULL, 1, '2026-01-22 08:04:41', 0),
(305, 12, 'away', 22, 14, NULL, 1, '2026-01-22 08:04:48', 0),
(306, 12, 'away', 10, 15, NULL, 1, '2026-01-22 08:04:53', 0),
(307, 12, 'away', 18, 16, NULL, 1, '2026-01-22 08:04:58', 0),
(308, 12, 'away', 6, 17, NULL, 1, '2026-01-22 08:05:03', 0),
(309, 12, 'away', 12, 18, NULL, 0, '2026-01-22 08:05:08', 0),
(310, 12, 'away', 13, 19, NULL, 0, '2026-01-22 08:05:14', 0),
(311, 13, 'away', 1, 1, 'GK', 1, '2026-01-22 08:09:02', 0),
(312, 13, 'away', 22, 2, NULL, 0, '2026-01-22 08:09:08', 0),
(313, 13, 'away', 36, 3, NULL, 0, '2026-01-22 08:09:11', 0),
(314, 13, 'away', 15, 4, NULL, 1, '2026-01-22 08:09:15', 0),
(315, 13, 'away', 3, 5, NULL, 0, '2026-01-22 08:09:20', 0),
(316, 13, 'away', 4, 6, NULL, 1, '2026-01-22 08:09:26', 0),
(317, 13, 'away', 17, 7, NULL, 1, '2026-01-22 08:09:32', 1),
(318, 13, 'away', 21, 8, NULL, 1, '2026-01-22 08:09:54', 0),
(319, 13, 'away', 23, 9, NULL, 0, '2026-01-22 08:09:58', 0),
(320, 13, 'away', 11, 10, NULL, 0, '2026-01-22 08:10:03', 0),
(321, 13, 'away', 18, 11, NULL, 1, '2026-01-22 08:10:08', 0),
(322, 13, 'away', 5, 12, NULL, 1, '2026-01-22 08:10:18', 0),
(323, 13, 'away', 20, 14, NULL, 1, '2026-01-22 08:10:26', 0),
(324, 13, 'away', 12, 15, NULL, 1, '2026-01-22 08:10:31', 0),
(325, 13, 'away', 10, 16, NULL, 1, '2026-01-22 08:10:34', 0),
(326, 13, 'away', 2, 17, NULL, 1, '2026-01-22 08:10:39', 0),
(327, 13, 'away', 19, 18, 'GK', 0, '2026-01-22 08:10:43', 0),
(328, 13, 'away', 13, 19, NULL, 0, '2026-01-22 08:10:49', 0),
(329, 14, 'away', 1, 1, 'GK', 1, '2026-01-22 08:32:53', 0),
(330, 14, 'away', 5, 2, NULL, 0, '2026-01-22 08:32:57', 0),
(331, 14, 'away', 20, 3, NULL, 1, '2026-01-22 08:33:01', 0),
(332, 14, 'away', 15, 4, NULL, 1, '2026-01-22 08:33:06', 0),
(333, 14, 'away', 3, 5, NULL, 1, '2026-01-22 08:33:14', 0),
(334, 14, 'away', 4, 6, NULL, 1, '2026-01-22 08:33:22', 0),
(335, 14, 'away', 7, 7, NULL, 0, '2026-01-22 08:33:26', 0),
(337, 14, 'away', 21, 8, NULL, 1, '2026-01-22 08:33:57', 1),
(338, 14, 'away', 10, 9, NULL, 1, '2026-01-22 08:34:02', 0),
(339, 14, 'away', 11, 10, NULL, 0, '2026-01-22 08:34:09', 0),
(340, 14, 'away', 23, 11, NULL, 1, '2026-01-22 08:34:14', 0),
(341, 14, 'away', 2, 12, NULL, 1, '2026-01-22 08:34:32', 0),
(342, 14, 'away', 18, 14, NULL, 1, '2026-01-22 08:34:37', 0),
(343, 14, 'away', 13, 15, NULL, 1, '2026-01-22 08:34:43', 0),
(344, 14, 'away', 19, 16, 'GK', 0, '2026-01-22 08:34:47', 0),
(345, 18, 'away', 1, 1, 'GK', 1, '2026-01-22 09:53:54', 0),
(346, 18, 'away', 2, 2, NULL, 1, '2026-01-22 09:54:04', 0),
(347, 18, 'away', 3, 3, NULL, 1, '2026-01-22 09:54:26', 0),
(348, 18, 'away', 4, 4, NULL, 1, '2026-01-22 09:58:53', 0),
(349, 18, 'away', 5, 5, NULL, 1, '2026-01-22 09:59:00', 0),
(350, 18, 'away', 6, 6, NULL, 1, '2026-01-22 09:59:08', 0),
(351, 18, 'away', 7, 7, NULL, 1, '2026-01-22 09:59:11', 0),
(352, 18, 'away', 21, 8, NULL, 1, '2026-01-22 09:59:22', 1),
(353, 18, 'away', 9, 9, NULL, 1, '2026-01-22 09:59:32', 0),
(354, 18, 'away', 10, 10, NULL, 1, '2026-01-22 09:59:40', 0),
(355, 18, 'away', 11, 11, NULL, 1, '2026-01-22 09:59:50', 0),
(356, 18, 'away', 12, 12, NULL, 0, '2026-01-22 10:00:06', 0),
(357, 18, 'away', 13, 14, NULL, 0, '2026-01-22 10:06:57', 0),
(358, 18, 'away', 22, 15, NULL, 0, '2026-01-22 10:07:23', 0),
(359, 18, 'away', 15, 16, NULL, 0, '2026-01-22 10:07:34', 0),
(360, 18, 'away', 24, 17, NULL, 0, '2026-01-22 10:08:11', 0),
(361, 18, 'away', 17, 18, NULL, 0, '2026-01-22 10:08:18', 0),
(362, 18, 'away', 18, 19, NULL, 0, '2026-01-22 10:08:27', 0),
(363, 18, 'away', 19, 20, 'GK', 0, '2026-01-22 10:08:32', 0),
(364, 17, 'away', 1, 1, 'GK', 1, '2026-01-22 10:13:25', 0),
(365, 17, 'away', 2, 2, NULL, 1, '2026-01-22 10:13:36', 0),
(366, 17, 'away', 3, 3, NULL, 1, '2026-01-22 10:13:42', 0),
(367, 17, 'away', 4, 4, NULL, 1, '2026-01-22 10:13:49', 0),
(368, 17, 'away', 5, 5, NULL, 1, '2026-01-22 10:13:56', 0),
(369, 17, 'away', 6, 6, NULL, 1, '2026-01-22 10:14:03', 0),
(370, 17, 'away', 23, 7, NULL, 1, '2026-01-22 10:14:11', 0),
(371, 17, 'away', 21, 8, NULL, 1, '2026-01-22 10:14:20', 1),
(372, 17, 'away', 11, 9, NULL, 1, '2026-01-22 10:14:27', 0),
(373, 17, 'away', 10, 10, NULL, 1, '2026-01-22 10:14:37', 0),
(374, 17, 'away', 9, 11, NULL, 1, '2026-01-22 10:14:55', 0),
(375, 17, 'away', 12, 12, NULL, 0, '2026-01-22 10:15:09', 0),
(376, 17, 'away', 13, 14, NULL, 0, '2026-01-22 10:15:16', 0),
(377, 17, 'away', 18, 15, NULL, 0, '2026-01-22 10:15:22', 0),
(378, 17, 'away', 19, 16, 'GK', 0, '2026-01-22 10:15:28', 0),
(379, 17, 'away', 24, 17, NULL, 0, '2026-01-22 10:19:10', 0),
(380, 17, 'away', 17, 18, NULL, 0, '2026-01-22 10:19:18', 0),
(381, 16, 'home', 1, 1, 'GK', 1, '2026-01-22 10:20:03', 0),
(382, 16, 'home', 15, 2, NULL, 1, '2026-01-22 10:20:08', 0),
(383, 16, 'home', 3, 3, NULL, 1, '2026-01-22 10:20:16', 0),
(384, 16, 'home', 4, 4, NULL, 1, '2026-01-22 10:20:22', 0),
(385, 16, 'home', 5, 5, NULL, 1, '2026-01-22 10:20:30', 0),
(386, 16, 'home', 20, 6, NULL, 1, '2026-01-22 10:20:36', 0),
(387, 16, 'home', 7, 7, NULL, 1, '2026-01-22 10:20:41', 0),
(388, 16, 'home', 21, 8, NULL, 1, '2026-01-22 10:20:49', 1),
(389, 16, 'home', 12, 9, NULL, 1, '2026-01-22 10:20:56', 0),
(390, 16, 'home', 18, 10, NULL, 1, '2026-01-22 10:20:59', 0),
(391, 16, 'home', 40, 11, NULL, 1, '2026-01-22 10:21:25', 0),
(392, 16, 'home', 6, 12, NULL, 0, '2026-01-22 10:21:36', 0),
(393, 16, 'home', 13, 14, NULL, 0, '2026-01-22 10:21:42', 0),
(394, 16, 'home', 2, 15, NULL, 0, '2026-01-22 10:21:47', 0),
(395, 16, 'home', 19, 16, 'GK', 0, '2026-01-22 10:22:02', 0),
(396, 16, 'home', 9, 17, NULL, 0, '2026-01-22 10:22:17', 0),
(397, 16, 'home', 24, 18, NULL, 0, '2026-01-22 10:22:23', 0),
(398, 16, 'home', 41, 19, 'GK', 0, '2026-01-22 10:23:12', 0),
(399, 15, 'home', 1, 1, 'GK', 1, '2026-01-22 10:24:11', 0),
(400, 15, 'home', 15, 2, NULL, 1, '2026-01-22 10:24:17', 0),
(401, 15, 'home', 3, 3, NULL, 1, '2026-01-22 10:24:23', 0),
(402, 15, 'home', 4, 4, NULL, 1, '2026-01-22 10:24:29', 0),
(403, 15, 'home', 22, 5, NULL, 1, '2026-01-22 10:24:55', 0),
(404, 15, 'home', 20, 6, NULL, 1, '2026-01-22 10:25:01', 0),
(405, 15, 'home', 7, 7, NULL, 1, '2026-01-22 10:25:06', 0),
(406, 15, 'home', 21, 8, NULL, 1, '2026-01-22 10:25:17', 1),
(407, 15, 'home', 10, 9, NULL, 1, '2026-01-22 10:25:22', 0),
(408, 15, 'home', 11, 10, NULL, 1, '2026-01-22 10:25:27', 0),
(409, 15, 'home', 23, 11, NULL, 1, '2026-01-22 10:25:36', 0),
(410, 15, 'home', 17, 12, NULL, 0, '2026-01-22 10:25:46', 0),
(411, 15, 'home', 13, 14, NULL, 0, '2026-01-22 10:25:50', 0),
(412, 15, 'home', 29, 15, NULL, 0, '2026-01-22 10:25:57', 0),
(413, 15, 'home', 18, 16, NULL, 0, '2026-01-22 10:26:04', 0),
(414, 15, 'home', 12, 17, NULL, 0, '2026-01-22 10:26:11', 0),
(434, 22, 'away', 1, 1, 'GK', 1, '2026-01-25 14:56:57', 0),
(435, 22, 'away', 15, 2, 'CB', 1, '2026-01-25 14:57:13', 0),
(436, 22, 'away', 4, 3, 'CB', 1, '2026-01-25 14:57:19', 0),
(437, 22, 'away', 3, 4, 'CB', 1, '2026-01-25 14:57:24', 0),
(438, 22, 'away', 22, 5, 'RB', 1, '2026-01-25 14:57:38', 0),
(439, 22, 'away', 7, 6, 'CM', 1, '2026-01-25 14:57:42', 0),
(440, 22, 'away', 12, 7, 'CM', 1, '2026-01-25 14:57:48', 0),
(441, 22, 'away', 20, 8, 'LB', 1, '2026-01-25 14:57:52', 0),
(442, 22, 'away', 23, 9, 'CM', 1, '2026-01-25 14:57:58', 0),
(443, 22, 'away', 10, 10, 'ST', 1, '2026-01-25 14:58:03', 0),
(444, 22, 'away', 11, 11, 'ST', 1, '2026-01-25 14:58:09', 1),
(445, 22, 'away', 17, 12, 'CB', 0, '2026-01-25 14:58:23', 0),
(446, 22, 'away', 18, 14, 'ST', 0, '2026-01-25 14:58:33', 0),
(447, 22, 'away', 2, 15, 'RB', 0, '2026-01-25 14:58:37', 0),
(448, 22, 'away', 24, 16, 'ST', 0, '2026-01-25 14:58:41', 0),
(449, 22, 'away', 42, 17, 'ST', 0, '2026-01-25 14:58:45', 0),
(450, 22, 'away', 13, 18, 'CM', 0, '2026-01-25 14:58:48', 0),
(451, 22, 'away', 41, 19, 'GK', 0, '2026-01-25 15:00:46', 0),
(452, 23, 'home', 1, 1, 'GK', 1, '2026-02-01 16:15:03', 0),
(453, 23, 'home', 22, 2, 'RB', 1, '2026-02-01 16:15:08', 0),
(454, 23, 'home', 20, 3, 'LB', 1, '2026-02-01 16:15:15', 0),
(455, 23, 'home', 15, 4, 'CB', 1, '2026-02-01 16:15:21', 0),
(456, 23, 'home', 3, 5, 'CB', 1, '2026-02-01 16:15:27', 0),
(457, 23, 'home', 4, 6, 'CB', 1, '2026-02-01 16:15:33', 0),
(458, 23, 'home', 7, 7, 'CM', 1, '2026-02-01 16:15:37', 0),
(459, 23, 'home', 21, 8, 'CM', 1, '2026-02-01 16:15:47', 1),
(460, 23, 'home', 10, 9, 'ST', 1, '2026-02-01 16:15:52', 0),
(461, 23, 'home', 11, 10, 'ST', 1, '2026-02-01 16:15:58', 0),
(462, 23, 'home', 23, 11, 'CM', 1, '2026-02-01 16:16:03', 0),
(463, 23, 'home', 12, 12, 'CM', 0, '2026-02-01 16:16:17', 0),
(464, 23, 'home', 2, 14, 'RB', 0, '2026-02-01 16:16:23', 0),
(465, 23, 'home', 18, 15, 'ST', 0, '2026-02-01 16:16:28', 0),
(466, 23, 'home', 24, 16, 'ST', 0, '2026-02-01 16:16:32', 0),
(467, 23, 'home', 42, 17, 'ST', 0, '2026-02-01 16:16:38', 0),
(468, 23, 'home', 13, 18, 'CM', 0, '2026-02-01 16:16:44', 0),
(469, 23, 'home', 41, 19, 'GK', 0, '2026-02-01 16:16:52', 0);

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

--
-- Dumping data for table `match_sessions`
--

INSERT INTO `match_sessions` (`match_id`, `playing`, `base_time_seconds`, `playback_rate`, `updated_at_ms`, `control_owner_user_id`, `control_owner_name`, `control_owner_socket_id`, `control_expires_at_ms`, `created_at`, `updated_at`) VALUES
(10, 0, 0, 1, 1769715857742, NULL, NULL, NULL, NULL, '2026-01-29 19:44:14', '2026-01-29 19:44:56'),
(19, 0, 0, 1, 1769860304417, NULL, NULL, NULL, NULL, '2026-01-27 19:29:18', '2026-01-31 11:56:45'),
(22, 0, 6343, 1, 1769860547568, NULL, NULL, NULL, NULL, '2026-01-27 13:44:25', '2026-01-31 11:55:50'),
(23, 0, 2128.646, 1, 1769973418133, NULL, NULL, NULL, NULL, '2026-01-29 08:36:55', '2026-02-01 19:18:57');

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

--
-- Dumping data for table `match_substitutions`
--

INSERT INTO `match_substitutions` (`id`, `match_id`, `team_side`, `match_period_id`, `match_second`, `minute`, `minute_extra`, `player_off_match_player_id`, `player_on_match_player_id`, `reason`, `notes`, `event_id`, `created_by`, `created_at`, `updated_at`) VALUES
(4, 1, 'away', NULL, 0, 55, 0, 96, 107, NULL, NULL, NULL, 1, '2026-01-21 19:14:38', '2026-01-21 19:14:38'),
(5, 1, 'away', NULL, 0, 77, 0, 102, 108, NULL, NULL, NULL, 1, '2026-01-21 19:15:03', '2026-01-21 19:15:03'),
(6, 1, 'away', NULL, 0, 83, 0, 94, 105, NULL, NULL, NULL, 1, '2026-01-21 19:15:17', '2026-01-21 19:15:17'),
(9, 2, 'home', NULL, 0, 77, 0, 121, 126, NULL, NULL, NULL, 1, '2026-01-21 19:41:44', '2026-01-21 19:41:44'),
(10, 2, 'home', NULL, 0, 84, 0, 115, 129, NULL, NULL, NULL, 1, '2026-01-21 19:43:16', '2026-01-21 19:43:16'),
(11, 2, 'home', NULL, 0, 84, 0, 120, 125, NULL, NULL, NULL, 1, '2026-01-21 19:48:22', '2026-01-21 19:48:22'),
(12, 2, 'home', NULL, 0, 84, 0, 117, 127, NULL, NULL, NULL, 1, '2026-01-21 19:48:36', '2026-01-21 19:48:36'),
(13, 3, 'away', NULL, 0, 53, 0, 132, 144, NULL, NULL, NULL, 1, '2026-01-21 20:16:18', '2026-01-21 20:16:18'),
(14, 3, 'away', NULL, 0, 60, 0, 136, 142, NULL, NULL, NULL, 1, '2026-01-21 20:16:33', '2026-01-21 20:16:33'),
(15, 3, 'away', NULL, 0, 60, 0, 137, 143, NULL, NULL, NULL, 1, '2026-01-21 20:16:47', '2026-01-21 20:16:47'),
(16, 3, 'away', NULL, 0, 60, 0, 139, 145, NULL, NULL, NULL, 1, '2026-01-21 20:16:59', '2026-01-21 20:16:59'),
(17, 4, 'home', NULL, 0, 46, 0, 148, 159, NULL, NULL, NULL, 1, '2026-01-21 20:24:01', '2026-01-21 20:24:01'),
(18, 4, 'home', NULL, 0, 66, 0, 153, 158, NULL, NULL, NULL, 1, '2026-01-21 20:24:14', '2026-01-21 20:24:14'),
(19, 4, 'home', NULL, 0, 79, 0, 157, 163, NULL, NULL, NULL, 1, '2026-01-21 20:24:27', '2026-01-21 20:24:27'),
(20, 5, 'away', NULL, 0, 0, 0, 168, 180, NULL, NULL, NULL, 1, '2026-01-21 21:03:09', '2026-01-21 21:03:09'),
(21, 5, 'away', NULL, 0, 63, 0, 171, 179, NULL, NULL, NULL, 1, '2026-01-21 21:03:25', '2026-01-21 21:03:25'),
(22, 5, 'away', NULL, 0, 63, 0, 166, 181, NULL, NULL, NULL, 1, '2026-01-21 21:03:39', '2026-01-21 21:03:39'),
(23, 5, 'away', NULL, 0, 83, 0, 167, 177, NULL, NULL, NULL, 1, '2026-01-21 21:03:52', '2026-01-21 21:03:52'),
(24, 5, 'away', NULL, 0, 83, 0, 169, 183, NULL, NULL, NULL, 1, '2026-01-21 21:04:03', '2026-01-21 21:04:03'),
(25, 6, 'home', NULL, 0, 46, 0, 193, 195, NULL, NULL, NULL, 1, '2026-01-21 21:10:03', '2026-01-21 21:10:03'),
(26, 6, 'home', NULL, 0, 57, 0, 186, 196, NULL, NULL, NULL, 1, '2026-01-21 21:10:20', '2026-01-21 21:10:20'),
(27, 6, 'home', NULL, 0, 78, 0, 191, 197, NULL, NULL, NULL, 1, '2026-01-21 21:10:33', '2026-01-21 21:10:33'),
(28, 6, 'home', NULL, 0, 78, 0, 192, 198, NULL, NULL, NULL, 1, '2026-01-21 21:10:44', '2026-01-21 21:10:44'),
(29, 7, 'away', NULL, 0, 46, 0, 205, 213, NULL, NULL, NULL, 1, '2026-01-21 21:16:21', '2026-01-21 21:16:21'),
(30, 7, 'away', NULL, 0, 46, 0, 203, 217, NULL, NULL, NULL, 1, '2026-01-21 21:16:37', '2026-01-21 21:16:37'),
(31, 7, 'away', NULL, 0, 70, 0, 204, 215, NULL, NULL, NULL, 1, '2026-01-21 21:16:48', '2026-01-21 21:16:48'),
(32, 7, 'away', NULL, 0, 70, 0, 208, 218, NULL, NULL, NULL, 1, '2026-01-21 21:17:02', '2026-01-21 21:17:02'),
(33, 7, 'away', NULL, 0, 77, 0, 212, 216, NULL, NULL, NULL, 1, '2026-01-21 21:17:12', '2026-01-21 21:17:12'),
(34, 8, 'away', NULL, 0, 48, 0, 222, 233, NULL, NULL, NULL, 1, '2026-01-21 21:23:17', '2026-01-21 21:23:17'),
(35, 8, 'away', NULL, 0, 75, 0, 230, 234, NULL, NULL, NULL, 1, '2026-01-21 21:23:29', '2026-01-21 21:23:29'),
(36, 8, 'away', NULL, 0, 80, 0, 227, 231, NULL, NULL, NULL, 1, '2026-01-21 21:23:41', '2026-01-21 21:23:41'),
(37, 8, 'away', NULL, 0, 80, 0, 226, 232, NULL, NULL, NULL, 1, '2026-01-21 21:24:09', '2026-01-21 21:24:09'),
(38, 9, 'away', NULL, 0, 46, 0, 243, 250, NULL, NULL, NULL, 1, '2026-01-21 21:28:12', '2026-01-21 21:28:12'),
(39, 9, 'away', NULL, 0, 67, 0, 246, 252, NULL, NULL, NULL, 1, '2026-01-21 21:28:23', '2026-01-21 21:28:23'),
(40, 9, 'away', NULL, 0, 78, 0, 239, 248, NULL, NULL, NULL, 1, '2026-01-21 21:28:33', '2026-01-21 21:28:33'),
(41, 9, 'away', NULL, 0, 78, 0, 238, 251, NULL, NULL, NULL, 1, '2026-01-21 21:28:45', '2026-01-21 21:28:45'),
(42, 9, 'away', NULL, 0, 78, 0, 244, 253, NULL, NULL, NULL, 1, '2026-01-21 21:28:55', '2026-01-21 21:28:55'),
(43, 10, 'home', NULL, 0, 67, 0, 262, 268, NULL, NULL, NULL, 1, '2026-01-22 07:11:08', '2026-01-22 07:11:08'),
(44, 10, 'home', NULL, 0, 67, 0, 257, 269, NULL, NULL, NULL, 1, '2026-01-22 07:11:20', '2026-01-22 07:11:20'),
(48, 11, 'home', NULL, 0, 72, 0, 277, 290, NULL, NULL, NULL, 1, '2026-01-22 07:55:42', '2026-01-22 07:55:42'),
(49, 11, 'home', NULL, 0, 72, 0, 279, 291, NULL, NULL, NULL, 1, '2026-01-22 07:55:59', '2026-01-22 07:55:59'),
(50, 11, 'home', NULL, 0, 80, 0, 282, 288, NULL, NULL, NULL, 1, '2026-01-22 07:56:13', '2026-01-22 07:56:13'),
(51, 11, 'home', NULL, 0, 80, 0, 276, 289, NULL, NULL, NULL, 1, '2026-01-22 07:56:25', '2026-01-22 07:56:25'),
(52, 11, 'home', NULL, 0, 72, 0, 281, 287, 'tactical', NULL, NULL, 1, '2026-01-22 07:57:12', '2026-01-22 07:57:12'),
(53, 12, 'away', NULL, 0, 63, 0, 295, 304, NULL, NULL, NULL, 1, '2026-01-22 08:06:34', '2026-01-22 08:06:34'),
(54, 12, 'away', NULL, 0, 63, 0, 297, 305, NULL, NULL, NULL, 1, '2026-01-22 08:06:47', '2026-01-22 08:06:47'),
(55, 12, 'away', NULL, 0, 79, 0, 294, 306, NULL, NULL, NULL, 1, '2026-01-22 08:06:58', '2026-01-22 08:06:58'),
(56, 12, 'away', NULL, 0, 79, 0, 302, 307, NULL, NULL, NULL, 1, '2026-01-22 08:07:13', '2026-01-22 08:07:13'),
(57, 12, 'away', NULL, 0, 79, 0, 300, 308, NULL, NULL, NULL, 1, '2026-01-22 08:07:34', '2026-01-22 08:07:34'),
(58, 13, 'away', NULL, 0, 46, 0, 315, 322, NULL, NULL, NULL, 1, '2026-01-22 08:29:44', '2026-01-22 08:29:44'),
(59, 13, 'away', NULL, 0, 46, 0, 313, 323, NULL, NULL, NULL, 1, '2026-01-22 08:30:04', '2026-01-22 08:30:04'),
(60, 13, 'away', NULL, 0, 64, 0, 312, 324, NULL, NULL, NULL, 1, '2026-01-22 08:30:16', '2026-01-22 08:30:16'),
(61, 13, 'away', NULL, 0, 69, 0, 320, 325, NULL, NULL, NULL, 1, '2026-01-22 08:30:31', '2026-01-22 08:30:31'),
(62, 13, 'away', NULL, 0, 81, 0, 319, 326, NULL, NULL, NULL, 1, '2026-01-22 08:30:50', '2026-01-22 08:30:50'),
(63, 14, 'away', NULL, 0, 65, 0, 330, 341, NULL, NULL, NULL, 1, '2026-01-22 08:37:18', '2026-01-22 08:37:18'),
(64, 14, 'away', NULL, 0, 78, 0, 339, 342, NULL, NULL, NULL, 1, '2026-01-22 08:37:29', '2026-01-22 08:37:29'),
(65, 14, 'away', NULL, 0, 93, 0, 335, 343, NULL, NULL, NULL, 1, '2026-01-22 08:37:41', '2026-01-22 08:37:41'),
(66, 22, 'away', NULL, 0, 88, 0, 435, 445, NULL, NULL, NULL, 1, '2026-01-25 16:17:55', '2026-01-25 16:17:55'),
(67, 22, 'away', NULL, 0, 77, 0, 438, 446, NULL, NULL, NULL, 1, '2026-01-25 16:18:10', '2026-01-25 16:18:10'),
(68, 22, 'away', NULL, 0, 88, 0, 444, 448, NULL, NULL, NULL, 1, '2026-01-25 16:18:23', '2026-01-25 16:18:23'),
(69, 22, 'away', NULL, 0, 77, 0, 443, 449, NULL, NULL, NULL, 1, '2026-01-25 16:18:32', '2026-01-25 16:18:32'),
(70, 22, 'away', NULL, 0, 88, 0, 439, 450, NULL, NULL, NULL, 1, '2026-01-25 16:18:46', '2026-01-25 16:18:46'),
(71, 23, 'home', NULL, 0, 60, 0, 453, 465, NULL, NULL, NULL, 1, '2026-02-01 17:33:25', '2026-02-01 17:33:25'),
(72, 23, 'home', NULL, 0, 60, 0, 457, 463, NULL, NULL, NULL, 1, '2026-02-01 17:33:39', '2026-02-01 17:33:39'),
(73, 23, 'home', NULL, 0, 72, 0, 462, 467, NULL, NULL, NULL, 1, '2026-02-01 17:33:53', '2026-02-01 17:33:53'),
(74, 23, 'home', NULL, 0, 77, 0, 461, 466, NULL, NULL, NULL, 1, '2026-02-01 17:34:15', '2026-02-01 17:34:15');

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

--
-- Dumping data for table `match_videos`
--

INSERT INTO `match_videos` (`id`, `match_id`, `source_path`, `thumbnail_path`, `duration_seconds`, `created_at`, `source_type`, `source_url`, `download_status`, `download_progress`, `error_message`) VALUES
(6, 18, 'match_18_standard.mp4', 'thumbnail_18.jpg', 6686, '2026-01-23 17:08:31', 'veo', 'https://app.veo.co/matches/20251213-rossvale-1-4-saltcoats-8ae3733c/', 'completed', 100, NULL),
(7, 19, 'match_19_standard.mp4', 'thumbnail_19.jpg', 6325, '2026-01-23 17:10:26', 'veo', 'https://app.veo.co/matches/20260117-saltcoats-4-0-campbelltown-1586c41b/', 'completed', 100, NULL),
(8, 22, 'match_22_standard.mp4', 'thumbnail_22.jpg', 6559, '2026-01-25 08:29:39', 'veo', 'https://app.veo.co/matches/20260124-2026-01-24-132903-ce97b5e6/', 'completed', 100, NULL),
(9, 23, 'match_23_standard.mp4', 'thumbnail_23.jpg', 6784, '2026-02-01 15:57:50', 'veo', 'https://app.veo.co/matches/20260131-2026-01-31-135949-feebb5e0/', 'completed', 100, NULL);

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

--
-- Dumping data for table `players`
--

INSERT INTO `players` (`id`, `club_id`, `team_id`, `first_name`, `last_name`, `dob`, `primary_position`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 'Callum', 'Robertson', NULL, 'GK', 1, '2026-01-09 09:09:56', '2026-01-23 11:57:34'),
(2, 1, 2, 'Ryan', 'Ritchie', NULL, 'RB', 1, '2026-01-09 09:09:56', '2026-01-23 11:59:13'),
(3, 1, 2, 'Ross', 'Agnew', NULL, 'CB', 1, '2026-01-09 09:09:56', '2026-01-23 11:07:08'),
(4, 1, 2, 'Andrew', 'McIntyre', NULL, 'CB', 1, '2026-01-09 09:09:56', '2026-01-23 11:07:53'),
(5, 1, 2, 'Jack', 'Cousar', NULL, 'RM', 1, '2026-01-09 09:09:56', '2026-01-23 11:54:07'),
(6, 1, 2, 'Mark', 'Beveridge', NULL, 'LB', 1, '2026-01-09 09:09:56', '2026-01-23 11:08:49'),
(7, 1, 2, 'Jack', 'Hanlon', NULL, 'CM', 1, '2026-01-09 09:09:56', '2026-01-23 11:54:26'),
(9, 1, 2, 'Brian', 'McCullough', NULL, 'CM', 1, '2026-01-09 09:09:56', '2026-01-23 11:57:55'),
(10, 1, 2, 'David', 'Sawyers', NULL, 'ST', 1, '2026-01-09 09:09:56', '2026-01-23 11:55:34'),
(11, 1, 2, 'Euan', 'Anderson', NULL, 'ST', 1, '2026-01-09 09:09:56', '2026-01-23 11:58:28'),
(12, 1, 2, 'Adam', 'Love', NULL, 'CM', 1, '2026-01-09 09:09:56', '2026-01-23 11:54:58'),
(13, 1, 2, 'Rian', 'Eaglesham', NULL, 'CM', 1, '2026-01-09 09:09:56', '2026-01-23 11:55:18'),
(14, 1, 2, 'G.', 'McIntyre', NULL, NULL, 1, '2026-01-09 09:09:56', NULL),
(15, 1, 2, 'Jamie', 'Stirling', NULL, 'CB', 1, '2026-01-09 09:09:56', '2026-01-23 11:07:35'),
(17, 1, 2, 'Aaron', 'Tait', NULL, 'CB', 1, '2026-01-09 09:09:56', '2026-01-23 11:08:29'),
(18, 1, 2, 'Adam', 'Kamara', NULL, 'ST', 1, '2026-01-09 09:09:56', '2026-01-23 11:55:48'),
(19, 1, 2, 'Aaron', 'Hussey', NULL, 'GK', 1, '2026-01-09 09:09:56', '2026-01-22 07:48:07'),
(20, 1, 2, 'Reiss', 'Love', NULL, 'LB', 1, '2026-01-17 21:04:05', '2026-01-23 12:01:28'),
(21, 1, 2, 'Rudi', 'Johnston', NULL, 'CM', 1, '2026-01-17 21:04:38', '2026-01-23 11:54:40'),
(22, 1, 2, 'Cameron', 'McIntyre', NULL, 'RB', 1, '2026-01-17 21:05:41', '2026-01-23 11:52:51'),
(23, 1, 2, 'Aaron', 'Robertson', NULL, 'CM', 1, '2026-01-17 21:06:25', '2026-01-23 11:58:10'),
(24, 1, 2, 'Lewis', 'Donaghy', NULL, 'ST', 1, '2026-01-17 21:08:04', '2026-01-23 11:56:00'),
(25, 1, 2, 'Chris', 'Lamb', NULL, 'GK', 0, '2026-01-21 14:54:51', '2026-01-21 15:00:18'),
(26, 1, 2, 'Craig', 'Breen', NULL, 'CB', 1, '2026-01-21 15:26:44', '2026-01-23 11:51:20'),
(27, 1, NULL, 'Greg', 'Forbes', NULL, NULL, 0, '2026-01-21 17:41:20', NULL),
(28, 1, NULL, 'Dylan', 'McClintock', NULL, NULL, 0, '2026-01-21 17:48:16', NULL),
(29, 1, NULL, 'Scott', 'Havlin', NULL, NULL, 0, '2026-01-21 17:48:31', NULL),
(30, 1, 2, 'Chris', 'Brogan', NULL, NULL, 0, '2026-01-21 17:49:06', '2026-01-22 07:49:03'),
(31, 1, NULL, 'Callum', 'Wilson', NULL, NULL, 0, '2026-01-21 17:49:19', NULL),
(32, 1, 2, 'Kyle', 'Smith', NULL, 'ST', 1, '2026-01-21 17:50:33', '2026-01-23 13:50:16'),
(34, 1, NULL, 'R', 'Campbell', NULL, NULL, 0, '2026-01-21 20:08:36', NULL),
(35, 1, NULL, 'J', 'McKenna', NULL, NULL, 0, '2026-01-21 20:22:29', NULL),
(36, 1, 2, 'Stewart', 'Morgan', NULL, 'LB', 0, '2026-01-21 20:25:52', '2026-01-23 11:58:50'),
(37, 1, NULL, 'Greg', 'McGuire', NULL, NULL, 0, '2026-01-21 21:08:09', NULL),
(38, 1, NULL, 'M', 'Young', NULL, NULL, 0, '2026-01-22 07:51:23', NULL),
(40, 1, NULL, 'K', 'Appiah', NULL, NULL, 0, '2026-01-22 10:21:24', NULL),
(41, 1, 2, 'Gavin', 'McQuillan', NULL, 'GK', 1, '2026-01-22 10:23:09', '2026-01-25 15:00:38'),
(42, 1, 2, 'Tyler', 'Love', NULL, 'ST', 1, '2026-01-23 13:49:38', '2026-01-23 13:49:57');

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

--
-- Dumping data for table `players_display_name_archive`
--

INSERT INTO `players_display_name_archive` (`id`, `club_id`, `display_name`, `first_name`, `last_name`, `archived_at`) VALUES
(1, 1, 'C. Robertson', 'C.', 'Robertson', '2026-01-22 07:34:58'),
(2, 1, 'R. Ritchie', 'R.', 'Ritchie', '2026-01-22 07:34:58'),
(3, 1, 'R. Agnew', 'R.', 'Agnew', '2026-01-22 07:34:58'),
(4, 1, 'A. McIntyre', 'A.', 'McIntyre', '2026-01-22 07:34:58'),
(5, 1, 'J. Cousar', 'J.', 'Cousar', '2026-01-22 07:34:58'),
(6, 1, 'M. Beveridge', 'M.', 'Beveridge', '2026-01-22 07:34:58'),
(7, 1, 'J. Hanlon', 'J.', 'Hanlon', '2026-01-22 07:34:58'),
(9, 1, 'B. McCullough', 'B.', 'McCullough', '2026-01-22 07:34:58'),
(10, 1, 'D. Sawyers', 'David', 'Sawyers', '2026-01-22 07:34:58'),
(11, 1, 'E. Anderson', 'E.', 'Anderson', '2026-01-22 07:34:58'),
(12, 1, 'A. Love', 'A.', 'Love', '2026-01-22 07:34:58'),
(13, 1, 'R. Eaglesham', 'R.', 'Eaglesham', '2026-01-22 07:34:58'),
(14, 1, 'G. McIntyre', 'G.', 'McIntyre', '2026-01-22 07:34:58'),
(15, 1, 'J. Stirling', 'J.', 'Stirling', '2026-01-22 07:34:58'),
(16, 1, 'I. Donachy', 'I.', 'Donachy', '2026-01-22 07:34:58'),
(17, 1, 'A. Tait', 'A.', 'Tait', '2026-01-22 07:34:58'),
(18, 1, 'A. Kamara', 'A.', 'Kamara', '2026-01-22 07:34:58'),
(19, 1, 'A. Hussey', 'A.', 'Hussey', '2026-01-22 07:34:58'),
(20, 1, 'R. Love', 'Reiss', 'Love', '2026-01-22 07:34:58'),
(21, 1, 'R. Johnston', 'Rudi', 'Johnston', '2026-01-22 07:34:58'),
(22, 1, 'C. McIntyre', 'Cameron', 'McIntyre', '2026-01-22 07:34:58'),
(23, 1, 'A. Robertson', 'Aaron', 'Robertson', '2026-01-22 07:34:58'),
(24, 1, 'L. Donachy', 'Lewis', 'Donachy', '2026-01-22 07:34:58'),
(25, 1, 'C. Lamb', 'Chris', 'Lamb', '2026-01-22 07:34:58'),
(26, 1, 'C. Breen', 'Craig', 'Breen', '2026-01-22 07:34:58'),
(27, 1, 'G. Forbes', 'Greg', 'Forbes', '2026-01-22 07:34:58'),
(28, 1, 'D. McClintock', 'Dylan', 'McClintock', '2026-01-22 07:34:58'),
(29, 1, 'S. Havlin', 'Scott', 'Havlin', '2026-01-22 07:34:58'),
(30, 1, 'C. Brogan', 'C', 'Brogan', '2026-01-22 07:34:58'),
(31, 1, 'C. Wilson', 'Callum', 'Wilson', '2026-01-22 07:34:58'),
(32, 1, 'K. Smith', 'Kyle', 'Smith', '2026-01-22 07:34:58'),
(33, 1, 'R. Easglesham', 'Rian', 'Easglesham', '2026-01-22 07:34:58'),
(34, 1, 'R. Campbell', 'R', 'Campbell', '2026-01-22 07:34:58'),
(35, 1, 'J. McKenna', 'J', 'McKenna', '2026-01-22 07:34:58'),
(36, 1, 'S. Morgan', 'Stewart', 'Morgan', '2026-01-22 07:34:58'),
(37, 1, 'G. McGuire', 'Greg', 'McGuire', '2026-01-22 07:34:58');

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
(1, 19, 'Goals', NULL, '2026-01-17 20:59:19', NULL, NULL),
(2, 19, 'Corners', NULL, '2026-01-18 01:55:48', NULL, NULL),
(3, 19, 'Funny', NULL, '2026-01-18 01:56:12', NULL, NULL),
(8, 22, 'Goals', NULL, '2026-01-25 16:19:46', '2026-01-25 17:18:42', '2026-01-25 17:18:42'),
(9, 22, 'Funny', NULL, '2026-01-25 16:19:56', '2026-01-25 17:18:44', '2026-01-25 17:18:44'),
(10, 22, 'Goals', NULL, '2026-01-25 17:18:57', '2026-01-25 17:47:47', '2026-01-25 17:47:47'),
(11, 22, 'Funny', NULL, '2026-01-25 17:19:00', '2026-01-25 17:47:44', '2026-01-25 17:47:44'),
(12, 22, 'Funny', NULL, '2026-01-25 17:48:05', '2026-01-25 17:56:21', '2026-01-25 17:56:21'),
(13, 22, 'Goals', NULL, '2026-01-25 17:48:11', '2026-01-25 17:56:15', '2026-01-25 17:56:15'),
(14, 22, 'Goals', NULL, '2026-01-25 17:56:47', '2026-01-25 18:06:27', '2026-01-25 18:06:27'),
(15, 22, 'Funny', NULL, '2026-01-25 18:06:34', '2026-01-25 19:26:02', '2026-01-25 19:26:02'),
(16, 22, 'Goals', NULL, '2026-01-25 18:20:50', '2026-01-25 18:56:18', '2026-01-25 18:56:18'),
(17, 22, 'Goals', NULL, '2026-01-25 18:56:23', '2026-01-25 19:15:38', '2026-01-25 19:15:38'),
(18, 22, 'Funny', NULL, '2026-01-25 19:26:47', '2026-01-25 19:27:38', '2026-01-25 19:27:38'),
(19, 22, 'Goals', NULL, '2026-01-25 19:26:52', '2026-01-25 19:28:45', '2026-01-25 19:28:45'),
(20, 22, 'Funny', NULL, '2026-01-25 19:32:21', '2026-01-25 19:38:06', '2026-01-25 19:38:06'),
(21, 22, 'Funny', NULL, '2026-01-25 19:39:02', NULL, NULL),
(22, 22, 'Shots', NULL, '2026-01-26 08:10:54', NULL, NULL),
(23, 23, 'Goals', NULL, '2026-02-01 17:39:00', NULL, NULL),
(24, 23, 'Funny', NULL, '2026-02-01 17:39:46', NULL, NULL),
(25, 23, 'Shots', NULL, '2026-02-01 17:40:03', NULL, NULL);

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
(1, 63, 3),
(1, 64, 2),
(1, 65, 1),
(1, 66, 0),
(3, 67, 0),
(5, 63, 3),
(5, 64, 2),
(5, 65, 1),
(5, 66, 0),
(7, 67, 0),
(8, 92, 0),
(8, 105, 1),
(8, 111, 2),
(8, 115, 3),
(9, 96, 0),
(9, 143, 1),
(10, 92, 0),
(10, 105, 1),
(10, 111, 2),
(10, 115, 3),
(11, 96, 0),
(11, 143, 1),
(12, 96, 0),
(12, 143, 1),
(13, 92, 0),
(13, 105, 1),
(13, 111, 2),
(13, 115, 3),
(14, 92, 0),
(14, 105, 1),
(14, 111, 2),
(14, 115, 3),
(16, 92, 0),
(16, 105, 1),
(16, 111, 2),
(16, 115, 3),
(17, 92, 0),
(18, 96, 0),
(18, 143, 1),
(19, 96, 0),
(20, 96, 0),
(21, 96, 0),
(21, 143, 1),
(22, 89, 0),
(22, 97, 1),
(22, 118, 2),
(22, 122, 3),
(22, 135, 4),
(23, 166, 0),
(23, 171, 1),
(23, 175, 2),
(23, 181, 3),
(23, 199, 4),
(23, 205, 5),
(23, 212, 6),
(23, 214, 7),
(24, 172, 0),
(24, 176, 1);

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
(2, 1, 'Saltcoats Victoria', 'club', '2025-12-19 12:08:47', '2026-01-28 00:57:37'),
(3, 3, 'Rossvale', 'opponent', '2025-12-21 14:12:12', '2026-01-28 11:51:20'),
(4, 3, 'Campbeltown Pupils', 'opponent', '2026-01-17 18:35:09', '2026-01-28 11:51:20'),
(6, 3, 'Wishaw', 'opponent', '2026-01-19 12:59:33', '2026-01-28 11:51:20'),
(7, 3, 'Vale of Leven', 'opponent', '2026-01-19 12:59:33', '2026-01-28 11:51:20'),
(8, 3, 'East Kilbride YM', 'opponent', '2026-01-19 12:59:33', '2026-01-28 11:51:20'),
(9, 3, 'Easthouses Lily', 'opponent', '2026-01-19 12:59:33', '2026-01-28 11:51:20'),
(10, 3, 'Newmains United', 'opponent', '2026-01-19 12:59:33', '2026-01-28 11:51:20'),
(11, 3, 'Giffnock', 'opponent', '2026-01-19 12:59:33', '2026-01-28 11:51:20'),
(12, 3, 'West Park United', 'opponent', '2026-01-19 12:59:33', '2026-01-28 11:51:20'),
(13, 3, 'Coupar Angus', 'opponent', '2026-01-19 12:59:33', '2026-01-28 11:51:20'),
(14, 3, 'St. Peter\'s', 'opponent', '2026-01-19 12:59:33', '2026-01-28 11:51:20'),
(15, 3, 'Neilston', 'opponent', '2026-01-19 12:59:33', '2026-01-28 11:51:20'),
(16, 3, 'Eglinton', 'opponent', '2026-01-19 12:59:33', '2026-01-28 11:51:20'),
(17, 3, 'Dyce', 'opponent', '2026-01-19 12:59:33', '2026-01-28 11:51:20'),
(18, 3, 'Irvine Victoria', 'opponent', '2026-01-19 12:59:33', '2026-01-28 11:51:20'),
(19, 3, 'Carluke Rovers', 'opponent', '2026-01-19 12:59:33', '2026-01-28 11:51:20'),
(20, 3, 'Royal Albert', 'opponent', '2026-01-19 12:59:33', '2026-01-28 11:51:20'),
(21, 3, 'East Kilbride Thistle', 'opponent', '2026-01-19 12:59:33', '2026-01-28 11:51:20'),
(39, 3, 'BSC Glasgow', 'opponent', '2026-01-28 01:02:31', '2026-01-28 11:51:20'),
(40, 3, 'Forth Wanderers', 'opponent', '2026-01-28 10:01:46', '2026-01-28 11:51:20'),
(41, 3, 'Blairgowrie', 'opponent', '2026-01-28 10:19:56', '2026-01-28 11:51:20'),
(42, 3, 'Cambuslang Rangers', 'opponent', '2026-01-28 10:22:19', '2026-01-28 11:51:20'),
(43, 3, 'Thorn Athletic', 'opponent', '2026-01-28 10:22:19', '2026-01-28 11:51:20'),
(44, 3, 'Beith Juniors', 'opponent', '2026-01-28 10:22:19', '2026-01-28 11:51:20'),
(45, 3, 'Edinburgh Community', 'opponent', '2026-01-28 10:22:19', '2026-01-28 11:51:20'),
(46, 3, 'Bellshill Athletic', 'opponent', '2026-01-28 10:22:19', '2026-01-28 11:51:20'),
(47, 3, 'Benburb', 'opponent', '2026-01-28 11:50:40', NULL);

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
(1, 1, 'colin@lundy.me.uk', '$2y$10$OPVaYgj3JeOdQ/9CkX2Mm.9y.UzGxSUcrIevUO3EaM/R6zTZYkToS', 'Platform Admin', 1, '2025-12-18 07:29:17', '2026-01-26 15:30:23'),
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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=215;

--
-- AUTO_INCREMENT for table `clip_jobs`
--
ALTER TABLE `clip_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=257;

--
-- AUTO_INCREMENT for table `clip_reviews`
--
ALTER TABLE `clip_reviews`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=202;

--
-- AUTO_INCREMENT for table `clubs`
--
ALTER TABLE `clubs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `competitions`
--
ALTER TABLE `competitions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `competition_teams`
--
ALTER TABLE `competition_teams`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `derived_stats`
--
ALTER TABLE `derived_stats`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=667;

--
-- AUTO_INCREMENT for table `event_snapshots`
--
ALTER TABLE `event_snapshots`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=207;

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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `formation_positions`
--
ALTER TABLE `formation_positions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=119;

--
-- AUTO_INCREMENT for table `keyboard_profiles`
--
ALTER TABLE `keyboard_profiles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `matches`
--
ALTER TABLE `matches`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `match_formations`
--
ALTER TABLE `match_formations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `match_periods`
--
ALTER TABLE `match_periods`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `match_players`
--
ALTER TABLE `match_players`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=470;

--
-- AUTO_INCREMENT for table `match_substitutions`
--
ALTER TABLE `match_substitutions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT for table `match_videos`
--
ALTER TABLE `match_videos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `players`
--
ALTER TABLE `players`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `player_team_season`
--
ALTER TABLE `player_team_season`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `playlists`
--
ALTER TABLE `playlists`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
