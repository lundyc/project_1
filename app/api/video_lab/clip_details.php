<?php

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/api_response.php';
require_once __DIR__ . '/../../lib/match_repository.php';
require_once __DIR__ . '/../../lib/match_permissions.php';
require_once __DIR__ . '/../../lib/clip_review_service.php';

auth_boot();
require_auth();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
          api_error('method_not_allowed', 405);
}

$matchId = isset($matchId) ? (int)$matchId : 0;
$clipId = isset($clipId) ? (int)$clipId : 0;

if ($matchId <= 0 || $clipId <= 0) {
          api_error('invalid_clip', 400);
}

$match = get_match($matchId);
if (!$match) {
          api_error('not_found', 404);
}

$user = current_user();
$roles = $_SESSION['roles'] ?? [];
$canAccessVideoLab = in_array('analyst', $roles, true)
          || in_array('club_admin', $roles, true)
          || in_array('platform_admin', $roles, true);
if (!$canAccessVideoLab || !can_view_match($user, $roles, (int)($match['club_id'] ?? 0))) {
          api_error('forbidden', 403);
}

$clip = clip_review_service_get_clip_details($matchId, $clipId);
if (!$clip) {
          api_error('clip_not_found', 404);
}

api_success(['clip' => $clip]);
