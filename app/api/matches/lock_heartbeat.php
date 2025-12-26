<?php

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/match_repository.php';
require_once __DIR__ . '/../../lib/match_permissions.php';
require_once __DIR__ . '/../../lib/match_lock_service.php';
require_once __DIR__ . '/../../lib/api_response.php';
require_once __DIR__ . '/../../lib/csrf.php';

auth_boot();
require_auth();

header('Content-Type: application/json');

try {
          require_csrf_token();
} catch (CsrfException $e) {
          api_error('invalid_csrf', 403, [], $e);
}

$matchId = isset($matchId) ? (int)$matchId : (int)($_POST['match_id'] ?? 0);

if ($matchId <= 0) {
          http_response_code(400);
          echo json_encode(['ok' => false, 'error' => 'invalid_match']);
          exit;
}

$match = get_match($matchId);
if (!$match) {
          http_response_code(404);
          echo json_encode(['ok' => false, 'error' => 'not_found']);
          exit;
}

$user = current_user();
$roles = $_SESSION['roles'] ?? [];
$canEdit = in_array('platform_admin', $roles, true) || in_array('club_admin', $roles, true) || in_array('analyst', $roles, true);
$canManage = can_manage_match_for_club($user, $roles, (int)$match['club_id']);

if (!$canEdit || (!$canManage && !in_array('platform_admin', $roles, true))) {
          echo json_encode([
                    'ok' => false,
                    'locked_by' => null,
                    'locked_at' => null,
                    'last_heartbeat_at' => null,
                    'mode' => 'readonly',
          ]);
          exit;
}

$result = refreshHeartbeat($matchId, (int)$user['id']);
echo json_encode($result);
