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
