<?php

require_once __DIR__ . '/../../../lib/auth.php';
require_once __DIR__ . '/../../../lib/match_repository.php';
require_once __DIR__ . '/../../../lib/match_permissions.php';
require_once __DIR__ . '/../../../lib/annotation_repository.php';
require_once __DIR__ . '/../../../lib/api_response.php';

auth_boot();
require_auth();

header('Content-Type: application/json');

$matchId = isset($matchId) ? (int)$matchId : (int)($_GET['match_id'] ?? 0);
if ($matchId <= 0) {
          api_error('invalid_match', 400);
}

$match = get_match($matchId);
if (!$match) {
          api_error('not_found', 404);
}

$user = current_user();
$roles = $_SESSION['roles'] ?? [];
$canView = can_view_match($user, $roles, (int)$match['club_id']) || can_manage_match_for_club($user, $roles, (int)$match['club_id']);
if (!$canView) {
          api_error('forbidden', 403);
}

$targetType = strtolower(trim((string)($_GET['target_type'] ?? 'match_video')));
$targetId = isset($_GET['target_id']) ? (int)$_GET['target_id'] : 0;
if ($targetType === 'match_video' && $targetId <= 0) {
          $targetId = isset($match['video_id']) ? (int)$match['video_id'] : 0;
}

if (!in_array($targetType, ['match_video', 'clip'], true) || $targetId <= 0) {
          api_error('invalid_target', 400);
}

$annotations = [];
if (annotation_target_exists($matchId, $targetType, $targetId)) {
          $annotations = annotation_list_for_target($matchId, $targetType, $targetId);
}

api_success(['annotations' => $annotations]);
