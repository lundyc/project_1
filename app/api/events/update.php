<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/match_repository.php';
require_once __DIR__ . '/../../lib/match_permissions.php';
require_once __DIR__ . '/../../lib/event_repository.php';
require_once __DIR__ . '/../../lib/match_lock_service.php';
require_once __DIR__ . '/../../lib/match_version_service.php';
require_once __DIR__ . '/../../lib/audit_service.php';
require_once __DIR__ . '/../../lib/match_period_repository.php';

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

$matchSecond = (int)($_POST['match_second'] ?? 0);
$eventTypeId = (int)($_POST['event_type_id'] ?? 0);

if ($matchSecond < 0 || $eventTypeId <= 0) {
          http_response_code(422);
          echo json_encode(['ok' => false, 'error' => 'invalid_payload']);
          exit;
}

$periodId = isset($_POST['period_id']) && $_POST['period_id'] !== '' ? (int)$_POST['period_id'] : null;
$minute = isset($_POST['minute']) && $_POST['minute'] !== '' ? (int)$_POST['minute'] : null;
$minuteExtra = isset($_POST['minute_extra']) && $_POST['minute_extra'] !== '' ? (int)$_POST['minute_extra'] : null;
$teamSide = $_POST['team_side'] ?? 'unknown';
if (!in_array($teamSide, ['home', 'away', 'unknown'], true)) {
          $teamSide = 'unknown';
}
$importance = max(1, min(5, (int)($_POST['importance'] ?? 3)));
$phase = trim((string)($_POST['phase'] ?? ''));
$matchPlayerId = isset($_POST['match_player_id']) && $_POST['match_player_id'] !== '' ? (int)$_POST['match_player_id'] : null;
$opponentDetail = trim((string)($_POST['opponent_detail'] ?? ''));
$outcome = trim((string)($_POST['outcome'] ?? ''));
$zone = trim((string)($_POST['zone'] ?? ''));
$notes = trim((string)($_POST['notes'] ?? ''));
$tagIds = array_filter(array_map('intval', $_POST['tag_ids'] ?? []));

if ($periodId !== null) {
          $pstmt = db()->prepare('SELECT id FROM match_periods WHERE id = :id AND match_id = :match_id LIMIT 1');
          $pstmt->execute(['id' => $periodId, 'match_id' => $matchId]);
          if (!$pstmt->fetch()) {
                    http_response_code(422);
                    echo json_encode(['ok' => false, 'error' => 'invalid_period']);
                    exit;
          }
}

if ($periodId === null) {
          $autoPeriods = get_match_periods($matchId);
          foreach ($autoPeriods as $p) {
                    $start = $p['start_second'] ?? null;
                    $end = $p['end_second'] ?? null;
                    if ($start !== null && $end !== null && $matchSecond >= $start && $matchSecond <= $end) {
                              $periodId = (int)$p['id'];
                              break;
                    }
                    if ($start !== null && $end === null && $matchSecond >= $start) {
                              $periodId = (int)$p['id'];
                              break;
                    }
          }
}

if ($matchPlayerId !== null) {
          $plStmt = db()->prepare('SELECT id FROM match_players WHERE id = :id AND match_id = :match_id LIMIT 1');
          $plStmt->execute(['id' => $matchPlayerId, 'match_id' => $matchId]);
          if (!$plStmt->fetch()) {
                    http_response_code(422);
                    echo json_encode(['ok' => false, 'error' => 'invalid_player']);
                    exit;
          }
}

$payload = [
          'period_id' => $periodId,
          'match_second' => $matchSecond,
          'minute' => $minute,
          'minute_extra' => $minuteExtra,
          'team_side' => $teamSide,
          'event_type_id' => $eventTypeId,
          'importance' => $importance,
          'phase' => $phase !== '' ? $phase : null,
          'match_player_id' => $matchPlayerId,
          'opponent_detail' => $opponentDetail !== '' ? $opponentDetail : null,
          'outcome' => $outcome !== '' ? $outcome : null,
          'zone' => $zone !== '' ? $zone : null,
          'notes' => $notes !== '' ? $notes : null,
];

try {
          $before = get_event($eventId);
          update_event($eventId, $payload, $tagIds, (int)$user['id']);
          $after = get_event($eventId);
          $version = bump_events_version($matchId);
          audit((int)$match['club_id'], (int)$user['id'], 'event', $eventId, 'update', json_encode($before), json_encode($after));

          echo json_encode([
                    'ok' => true,
                    'event' => $after,
                    'meta' => ['events_version' => $version],
          ]);
} catch (\Throwable $e) {
          http_response_code(500);
          echo json_encode([
                    'ok' => false,
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
          ]);
}
