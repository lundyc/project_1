-- Restore match_id=3 as match_id=19
SET FOREIGN_KEY_CHECKS=0;

-- events table
INSERT INTO `events` (`id`, `match_id`, `period_id`, `match_second`, `minute`, `minute_extra`, `team_side`, `event_type_id`, `importance`, `phase`, `match_player_id`, `player_id`, `opponent_detail`, `outcome`, `zone`, `notes`, `created_by`, `created_at`, `updated_by`, `updated_at`, `match_period_id`, `clip_id`, `clip_start_second`, `clip_end_second`) VALUES
(258, 19, 5, 14, 0, 0, 'unknown', 13, 1, 'unknown', NULL, NULL, NULL, NULL, NULL, 'First Half', 1, '2026-01-17 20:45:26', NULL, NULL, NULL, NULL, NULL, NULL),
(259, 19, 5, 188, 3, 0, 'home', 15, 3, 'unknown', 69, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-01-17 21:08:40', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(260, 19, 5, 264, 4, 0, 'home', 15, 3, 'unknown', 68, NULL, NULL, 'on_target', NULL, NULL, 1, '2026-01-17 21:09:25', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(263, 19, 5, 297, 4, 0, 'home', 5, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 21:10:59', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(264, 19, 5, 309, 5, 0, 'home', 15, 3, 'unknown', 62, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-01-17 21:12:30', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(265, 19, 5, 570, 9, 0, 'home', 5, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 21:18:39', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(266, 19, 5, 670, 11, 0, 'away', 5, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 21:20:55', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(267, 19, 5, 824, 13, 0, 'home', 4, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 21:23:33', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(269, 19, 5, 999, 16, 0, 'away', 4, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 21:27:59', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(270, 19, 5, 1158, 19, 0, 'away', 5, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 21:30:54', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(271, 19, 5, 1303, 21, 0, 'away', 5, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 21:33:44', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(272, 19, 5, 1332, 22, 0, 'away', 5, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 21:40:06', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(273, 19, 5, 1464, 24, 0, 'home', 5, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 21:42:38', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(274, 19, 5, 1516, 25, 0, 'home', 15, 3, 'unknown', 69, NULL, NULL, 'on_target', NULL, NULL, 1, '2026-01-17 21:43:37', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(275, 19, 5, 1516, 25, 0, 'home', 4, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 21:43:41', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(276, 19, 5, 1536, 25, 0, 'home', 15, 3, 'unknown', 60, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-01-17 21:44:13', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(277, 19, 5, 1612, 26, 0, 'home', 5, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 21:45:30', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(278, 19, 5, 1684, 28, 0, 'away', 15, 3, 'unknown', NULL, NULL, NULL, 'on_target', NULL, NULL, 1, '2026-01-17 21:47:13', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(279, 19, 5, 1730, 28, 0, 'home', 5, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 21:48:20', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(280, 19, 5, 1771, 29, 0, 'home', 15, 3, 'unknown', 62, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-01-17 21:52:40', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(281, 19, 5, 1994, 33, 0, 'away', 15, 3, 'unknown', NULL, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-01-17 21:56:25', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(282, 19, 5, 2134, 35, 0, 'home', 16, 5, 'unknown', 66, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 21:59:00', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(283, 19, 5, 363, 6, 0, 'away', 15, 3, 'unknown', NULL, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-01-17 22:06:10', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(285, 19, 5, 2312, 38, 0, 'home', 15, 3, 'unknown', 64, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-01-17 22:15:53', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(286, 19, 5, 2392, 39, 0, 'home', 5, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 22:17:13', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(287, 19, 5, 2398, 39, 0, 'unknown', 8, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 22:17:20', NULL, '2026-01-18 11:35:49', NULL, NULL, NULL, NULL),
(288, 19, 5, 2552, 42, 0, 'home', 5, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 22:21:04', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(289, 19, 5, 2661, 44, 0, 'home', 16, 5, 'unknown', 68, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 22:22:55', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(290, 19, 5, 2710, 45, 0, 'unknown', 14, 1, 'unknown', NULL, NULL, NULL, NULL, NULL, 'First Half', 1, '2026-01-17 22:23:51', NULL, NULL, NULL, NULL, NULL, NULL),
(291, 19, 5, 3526, 58, 14, 'unknown', 13, 1, 'unknown', NULL, NULL, NULL, NULL, NULL, 'Second Half', 1, '2026-01-17 22:26:56', NULL, NULL, NULL, NULL, NULL, NULL),
(292, 19, 5, 3617, 60, 16, 'home', 5, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 22:28:30', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(293, 19, 5, 3663, 61, 16, 'home', 15, 3, 'unknown', 65, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-01-17 22:29:46', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(294, 19, 5, 3922, 65, 21, 'home', 15, 3, 'unknown', 69, NULL, NULL, 'on_target', NULL, NULL, 1, '2026-01-17 22:46:46', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(295, 19, 5, 3979, 66, 22, 'home', 15, 3, 'unknown', 60, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-01-17 22:47:47', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(296, 19, 5, 4193, 69, 25, 'home', 5, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 22:51:21', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(297, 19, 5, 4287, 71, 27, 'home', 15, 3, 'unknown', 65, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-01-17 22:52:58', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(298, 19, 5, 4447, 74, 29, 'away', 15, 3, 'unknown', NULL, NULL, NULL, 'on_target', NULL, NULL, 1, '2026-01-17 22:55:41', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(299, 19, 5, 4521, 75, 31, 'away', 5, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 22:56:55', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(300, 19, 5, 4623, 77, 32, 'home', 5, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 22:58:37', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(301, 19, 5, 4669, 77, 33, 'home', 15, 3, 'unknown', 65, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-01-17 22:59:37', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(302, 19, 5, 4806, 80, 35, 'home', 12, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 23:02:09', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(303, 19, 5, 4982, 83, 38, 'home', 4, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 23:05:05', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(304, 19, 5, 5013, 83, 39, 'home', 4, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 23:05:36', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(305, 19, 5, 5071, 84, 40, 'home', 15, 3, 'unknown', 64, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-01-17 23:06:40', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(306, 19, 5, 5125, 85, 41, 'away', 5, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 23:08:10', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(307, 19, 5, 5218, 86, 42, 'home', 5, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 23:09:58', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(308, 19, 5, 5223, 87, 42, 'unknown', 8, 2, 'unknown', 60, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 23:12:30', NULL, '2026-01-18 11:36:19', NULL, NULL, NULL, NULL),
(309, 19, 5, 5331, 88, 44, 'home', 15, 3, 'unknown', 68, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-01-17 23:18:10', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(310, 19, 5, 5396, 89, 45, 'home', 5, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 23:19:15', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(311, 19, 5, 5426, 90, 46, 'home', 15, 3, 'unknown', 65, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-01-17 23:19:48', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(312, 19, 5, 5446, 90, 46, 'home', 16, 5, 'unknown', 60, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 23:20:10', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(313, 19, 5, 5570, 92, 48, 'home', 5, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 23:22:09', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(314, 19, 5, 5663, 94, 50, 'home', 15, 3, 'unknown', 62, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-01-17 23:23:45', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(315, 19, 5, 5691, 94, 50, 'home', 5, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 23:24:13', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(316, 19, 5, 5777, 96, 52, 'home', 4, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 23:25:43', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(317, 19, 5, 5442, 90, 46, 'home', 4, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 23:26:09', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(318, 19, 5, 5801, 96, 52, 'home', 15, 3, 'unknown', 70, NULL, NULL, 'on_target', NULL, NULL, 1, '2026-01-17 23:26:55', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(319, 19, 5, 5839, 97, 53, 'away', 5, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 23:27:33', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(320, 19, 5, 6006, 100, 55, 'home', 5, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 23:30:21', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(321, 19, 5, 6033, 100, 56, 'home', 5, 2, 'unknown', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 23:30:55', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(322, 19, 5, 6081, 101, 57, 'home', 16, 5, 'unknown', 65, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-17 23:31:47', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(323, 19, 5, 6180, 103, 58, 'home', 15, 3, 'unknown', 68, NULL, NULL, 'off_target', NULL, NULL, 1, '2026-01-17 23:33:24', NULL, '2026-01-19 16:15:12', NULL, NULL, NULL, NULL),
(325, 19, 5, 6241, 104, 59, 'unknown', 13, 1, 'unknown', NULL, NULL, NULL, NULL, NULL, 'Second Half', 1, '2026-01-17 23:37:21', NULL, NULL, NULL, NULL, NULL, NULL);

-- clips table
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
(74, 19, 269, 0, '', 969, 1029, 0, 1, '2026-01-18 03:16:31', NULL, NULL, 'event_auto', 1, 1, NULL);

-- clip_jobs table
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
(113, 19, NULL, NULL, 'pending', '{\"match_id\":3,\"event_id\":327,\"start_second\":2062,\"duration_seconds\":60,\"source_path\":\"videos/matches/match_3/source/veo/standard/match_3_standard.mp4\"}', NULL, NULL, '2026-01-17 23:56:06', NULL);

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
(22, 19, 'home', NULL, 0, 0, 0, '11-a-side', '3-5-2', NULL, NULL, 1, '2026-01-17 21:02:35', '2026-01-19 16:00:35'),
(23, 19, 'away', NULL, 0, 0, 0, '11-a-side', '3-4-3', NULL, NULL, 1, '2026-01-17 21:02:35', '2026-01-19 16:00:31');

-- match_periods table
INSERT INTO `match_periods` (`id`, `match_id`, `period_id`, `start_second`, `end_second`, `created_at`) VALUES
(5, 19, 'first_half', 'First Half', 14, 2710, '2026-01-17 20:45:26', '2026-01-17 22:23:51'),
(6, 19, 'second_half', 'Second Half', 3705, 6241, '2026-01-17 22:26:56', '2026-01-17 23:38:06');

-- match_videos table
INSERT INTO `match_videos` (`id`, `match_id`, `video_label`, `source_type`, `source_path`, `uploaded_path`, `fps`, `duration_seconds`, `uploaded_filename`, `metadata_json`, `created_at`, `updated_at`) VALUES
(4, 19, '/videos/raw/match_3_4K.mp4', NULL, '2026-01-18 11:26:35', 'veo', 'https://app.veo.co/matches/20260117-saltcoats-4-0-campbelltown-1586c41b/', '', 0, NULL);

-- derived_stats table
INSERT INTO `derived_stats` (`id`, `match_id`, `team_side`, `passes`, `tackles`, `shots_on_target`, `shots_off_target`, `corners`, `fouls_committed`, `chances`, `offsides`, `saves`, `blocks`, `interceptions`, `clearances`, `yellow_cards`, `red_cards`, `possession_percentage`, `created_at`, `updated_at`) VALUES
(55, 19, 77, '2026-01-19 16:00:03', '{\n    \"computed_at\": \"2026-01-19 16:00:03\",\n    \"events_version_used\": 77,\n    \"by_type_team\": {\n        \"goal\": {\n            \"home\": 0,\n            \"away\": 4,\n            \"unknown\": 0\n        },\n        \"shot\": {\n            \"home\": 4,\n            \"away\": 22,\n            \"unknown\": 0\n        },\n        \"shot_on_target\": {\n            \"home\": 2,\n            \"away\": 4,\n            \"unknown\": 0\n        },\n        \"shot_off_target\": {\n            \"home\": 2,\n            \"away\": 14,\n            \"unknown\": 0\n        },\n        \"chance\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"corner\": {\n            \"home\": 1,\n            \"away\": 7,\n            \"unknown\": 0\n        },\n        \"free_kick\": {\n            \"home\": 7,\n            \"away\": 16,\n            \"unknown\": 0\n        },\n        \"penalty\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"foul\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"yellow_card\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 2\n        },\n        \"red_card\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"mistake\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"good_play\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 0\n        },\n        \"highlight\": {\n            \"home\": 0,\n            \"away\": 1,\n            \"unknown\": 0\n        }\n    },\n    \"phase_2\": {\n        \"by_period\": {\n            \"1H\": {\n                \"home\": 12,\n                \"away\": 46,\n                \"unknown\": 6\n            },\n            \"2H\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            },\n            \"ET\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            },\n            \"other\": {\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 0\n            }\n        },\n        \"per_15_minute\": [\n            {\n                \"label\": \"0-15\",\n                \"home\": 2,\n                \"away\": 6,\n                \"unknown\": 1\n            },\n            {\n                \"label\": \"15-30\",\n                \"home\": 5,\n                \"away\": 7,\n                \"unknown\": 0\n            },\n            {\n                \"label\": \"30-45\",\n                \"home\": 1,\n                \"away\": 5,\n                \"unknown\": 1\n            },\n            {\n                \"label\": \"45-60\",\n                \"home\": 0,\n                \"away\": 0,\n                \"unknown\": 2\n            },\n            {\n                \"label\": \"60-75\",\n                \"home\": 1,\n                \"away\": 6,\n                \"unknown\": 0\n            },\n            {\n                \"label\": \"75-90\",\n                \"home\": 2,\n                \"away\": 10,\n                \"unknown\": 1\n            },\n            {\n                \"label\": \"90-105\",\n                \"home\": 1,\n                \"away\": 12,\n                \"unknown\": 1\n            }\n        ],\n        \"importance_weighted\": {\n            \"by_team\": {\n                \"home\": 28,\n                \"away\": 122,\n                \"unknown\": 8\n            },\n            \"by_type_team\": {\n                \"goal\": {\n                    \"home\": 0,\n                    \"away\": 20,\n                    \"unknown\": 0\n                },\n                \"shot\": {\n                    \"home\": 12,\n                    \"away\": 74,\n                    \"unknown\": 0\n                },\n                \"shot_on_target\": {\n                    \"home\": 6,\n                    \"away\": 12,\n                    \"unknown\": 0\n                },\n                \"shot_off_target\": {\n                    \"home\": 6,\n                    \"away\": 42,\n                    \"unknown\": 0\n                },\n                \"chance\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"corner\": {\n                    \"home\": 2,\n                    \"away\": 14,\n                    \"unknown\": 0\n                },\n                \"free_kick\": {\n                    \"home\": 14,\n                    \"away\": 32,\n                    \"unknown\": 0\n                },\n                \"penalty\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"foul\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"yellow_card\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 4\n                },\n                \"red_card\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"mistake\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"good_play\": {\n                    \"home\": 0,\n                    \"away\": 0,\n                    \"unknown\": 0\n                },\n                \"highlight\": {\n                    \"home\": 0,\n                    \"away\": 2,\n                    \"unknown\": 0\n                }\n            }\n        }\n    },\n    \"totals\": {\n        \"set_pieces\": {\n            \"home\": 8,\n            \"away\": 23,\n            \"unknown\": 0\n        },\n        \"cards\": {\n            \"home\": 0,\n            \"away\": 0,\n            \"unknown\": 2\n        },\n        \"highlights\": {\n            \"total\": 1,\n            \"by_team\": {\n                \"home\": 0,\n                \"away\": 1,\n                \"unknown\": 0\n            }\n        }\n    }\n}');

-- playlists table
INSERT INTO `playlists` (`id`, `match_id`, `name`, `created_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(5, 19, 'Goals', NULL, '2026-01-17 20:59:19', NULL, NULL),
(6, 19, 'Corners', NULL, '2026-01-18 01:55:48', NULL, NULL),
(7, 19, 'Funny', NULL, '2026-01-18 01:56:12', NULL, NULL);

SET FOREIGN_KEY_CHECKS=1;