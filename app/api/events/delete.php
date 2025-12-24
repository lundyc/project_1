<?php

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/match_repository.php';
require_once __DIR__ . '/../../lib/match_permissions.php';
require_once __DIR__ . '/../../lib/event_repository.php';
require_once __DIR__ . '/../../lib/match_lock_service.php';
require_once __DIR__ . '/../../lib/match_version_service.php';
require_once __DIR__ . '/../../lib/audit_service.php';

auth_boot();
require_auth();

header('Content-Type: application/json');

$matchId = isset($matchId) ? (int)$matchId : (int)($_POST['match_id'] ?? 0);
$eventId = (int)($_POST['event_id'] ?? 0);

if ($matchId <= 0 || $eventId <= 0) {
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

$existing = get_event($eventId);
if (!$existing || (int)$existing['match_id'] !== $matchId) {
          http_response_code(404);
          echo json_encode(['ok' => false, 'error' => 'not_found']);
          exit;
}

$user = current_user();
$roles = $_SESSION['roles'] ?? [];
$canEdit = in_array('platform_admin', $roles, true) || in_array('club_admin', $roles, true) || in_array('analyst', $roles, true);
$canManage = $canEdit && can_manage_match_for_club($user, $roles, (int)$match['club_id']);

if (!$canManage) {
          http_response_code(403);
          echo json_encode(['ok' => false, 'error' => 'forbidden']);
          exit;
}

$lock = findLock($matchId);
if (!$lock || (int)$lock['locked_by'] !== (int)$user['id'] || !isLockFresh($lock['last_heartbeat_at'])) {
          echo json_encode(['ok' => false, 'error' => 'lock_required']);
          exit;
}

try {
          $before = get_event($eventId);
          delete_event($eventId, (int)$user['id']);
          $version = bump_events_version($matchId);
          audit((int)$match['club_id'], (int)$user['id'], 'event', $eventId, 'delete', json_encode($before), null);

          echo json_encode([
                    'ok' => true,
                    'event' => null,
                    'meta' => ['events_version' => $version],
          ]);
} catch (\Throwable $e) {
          http_response_code(500);
          echo json_encode(['ok' => false, 'error' => 'server_error']);
}
