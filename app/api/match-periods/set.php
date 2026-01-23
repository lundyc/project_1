require_once __DIR__ . '/../../lib/event_repository.php';
// --- Auto-repair: ensure every ended period has a period_end event ---
function ensure_period_end_event($matchId, $periodKey, $label, $endSecond, $userId, $clubId) {
    if ($endSecond === null) return;
    $events = event_list_for_match($matchId);
    $found = false;
    foreach ($events as $ev) {
        if (($ev['event_type_key'] === 'period_end') &&
            ((isset($ev['period_key']) && $ev['period_key'] === $periodKey) ||
             (isset($ev['notes']) && trim($ev['notes']) === $label)) &&
            ((int)$ev['match_second'] === (int)$endSecond)) {
            $found = true;
            break;
        }
    }
    if (!$found) {
        // Find event_type_id for period_end
        $eventTypeId = get_event_type_id_by_key($clubId, 'period_end');
        if (!$eventTypeId) {
            $eventTypeId = ensure_event_type_exists($clubId, 'period_end', 'Period End', 3);
        }
        $data = [
            'period_id' => null,
            'match_second' => $endSecond,
            'minute' => floor($endSecond / 60),
            'minute_extra' => 0,
            'team_side' => null,
            'event_type_id' => $eventTypeId,
            'importance' => 3,
            'phase' => null,
            'is_penalty' => 0,
            'match_player_id' => null,
            'opponent_detail' => null,
            'outcome' => null,
            'zone' => null,
            'notes' => $label,
            'period_key' => $periodKey,
        ];
        event_create($matchId, $data, [], $userId);
    }
}
<?php

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/match_repository.php';
require_once __DIR__ . '/../../lib/match_permissions.php';
require_once __DIR__ . '/../../lib/match_period_repository.php';

auth_boot();
require_auth();

header('Content-Type: application/json');

$matchId = isset($matchId) ? (int)$matchId : (int)($_POST['match_id'] ?? ($_GET['match_id'] ?? 0));

$payload = [];
$raw = file_get_contents('php://input');
if ($raw) {
          $decoded = json_decode($raw, true);
          if (is_array($decoded)) {
                    $payload = $decoded;
          }
}

$period = isset($payload['period']) ? trim((string)$payload['period']) : trim((string)($_POST['period'] ?? ''));
$videoTime = null;
if (isset($payload['video_time'])) {
          $videoTime = (int)$payload['video_time'];
} elseif (isset($_POST['video_time'])) {
          $videoTime = (int)$_POST['video_time'];
}

if ($matchId <= 0 || $period === '' || $videoTime === null || $videoTime < 0) {
          http_response_code(400);
          echo json_encode(['ok' => false, 'error' => 'invalid_params']);
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
if (!can_manage_match_for_club($user, $roles, (int)$match['club_id'])) {
          http_response_code(403);
          echo json_encode(['ok' => false, 'error' => 'forbidden']);
          exit;
}

if (!preg_match('/^([a-z0-9_]+)_(start|end)$/i', $period, $matches)) {
          http_response_code(400);
          echo json_encode(['ok' => false, 'error' => 'invalid_period']);
          exit;
}

$periodKey = strtolower($matches[1]);
$periodAction = strtolower($matches[2]);

$labelMap = [
          'first_half' => 'First Half',
          'second_half' => 'Second Half',
          'extra_time_1' => 'Extra Time 1',
          'extra_time_2' => 'Extra Time 2',
          'penalties' => 'Penalties',
];

$label = $labelMap[$periodKey] ?? ucwords(str_replace('_', ' ', $periodKey));
$currentSecond = max(0, $videoTime);


// --- On match load: auto-repair for all periods with end_second set but missing period_end event ---
$periods = get_match_periods($matchId);
$user = current_user();
$roles = $_SESSION['roles'] ?? [];
$clubId = (int)$match['club_id'];
foreach ($periods as $p) {
    if ($p['end_second'] !== null) {
        ensure_period_end_event($matchId, $p['period_key'], $p['label'], $p['end_second'], $user['id'], $clubId);
    }
}

$active = get_active_match_period($matchId);
$activePeriodId = $active['id'] ?? null;
$activeKey = $active['period_key'] ?? null;

try {
          if ($periodAction === 'start') {
                    if ($active && $activeKey !== $periodKey) {
                              http_response_code(409);
                              echo json_encode(['ok' => false, 'error' => 'another_active_period']);
                              exit;
                    }
                    $result = upsert_match_period_time($matchId, $activeKey === $periodKey ? (int)$activePeriodId : null, $label, $currentSecond, null, $periodKey, true);
                    $activeId = $result['period_id'];

          } else {
                    if (!$active || $activeKey !== $periodKey) {
                              http_response_code(409);
                              echo json_encode(['ok' => false, 'error' => 'period_not_active']);
                              exit;
                    }
                    $result = upsert_match_period_time($matchId, (int)$activePeriodId, $label, null, $currentSecond, $periodKey);
                    $activeId = null;
                    // After ending, ensure period_end event exists
                    ensure_period_end_event($matchId, $periodKey, $label, $currentSecond, $user['id'], $clubId);
          }

          echo json_encode([
                    'ok' => true,
                    'period' => $periodKey,
                    'action' => $periodAction,
                    'periods' => $result['periods'],
                    'active_period_id' => $activeId,
          ]);
} catch (\Throwable $e) {
          http_response_code(500);
          echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
