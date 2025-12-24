<?php

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/match_repository.php';
require_once __DIR__ . '/../../lib/match_permissions.php';
require_once __DIR__ . '/../../lib/clip_repository.php';
require_once __DIR__ . '/../../lib/match_lock_service.php';

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

$clipName = trim((string)($_POST['clip_name'] ?? ''));
$startSecond = (int)($_POST['start_second'] ?? 0);
$endSecond = (int)($_POST['end_second'] ?? 0);

if ($startSecond < 0 || $endSecond <= 0) {
          http_response_code(422);
          echo json_encode(['ok' => false, 'error' => 'invalid_payload']);
          exit;
}

$payload = [
          'clip_name' => $clipName !== '' ? $clipName : null,
          'start_second' => $startSecond,
          'end_second' => $endSecond,
];

try {
          $result = create_clip($matchId, (int)$match['club_id'], (int)$user['id'], $eventId, $payload);
          echo json_encode([
                    'ok' => true,
                    'clip' => $result['clip'],
                    'meta' => ['clips_version' => $result['version']],
          ]);
} catch (\InvalidArgumentException $e) {
          http_response_code(422);
          echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
} catch (\Throwable $e) {
          http_response_code(500);
          echo json_encode(['ok' => false, 'error' => 'server_error']);
}
