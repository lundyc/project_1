<?php

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/match_repository.php';
require_once __DIR__ . '/../../lib/match_permissions.php';
require_once __DIR__ . '/../../lib/match_period_repository.php';

auth_boot();
require_auth();

header('Content-Type: application/json');

$matchId = isset($matchId) ? (int)$matchId : (int)($_POST['match_id'] ?? ($_GET['match_id'] ?? 0));

$raw = file_get_contents('php://input');
$payload = [];
if ($raw) {
          $decoded = json_decode($raw, true);
          if (is_array($decoded)) {
                    $payload = $decoded;
          }
}

$periodId = isset($payload['period_id']) ? (int)$payload['period_id'] : (int)($_POST['period_id'] ?? 0);
$currentSecond = isset($payload['current_second']) ? (int)$payload['current_second'] : (int)($_POST['current_second'] ?? 0);
$periodLabel = isset($payload['label']) ? trim((string)$payload['label']) : trim((string)($_POST['label'] ?? ''));

if ($matchId <= 0 || ($periodId <= 0 && $periodLabel === '')) {
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

$columns = match_period_columns();
if (!in_array('end_second', $columns, true)) {
          http_response_code(500);
          echo json_encode(['ok' => false, 'error' => 'end_second_column_missing']);
          exit;
}

$pdo = db();
$row = null;
$resolvedPeriodLabel = $periodLabel;

if ($periodId > 0) {
          $stmt = $pdo->prepare('SELECT id, start_second, label FROM match_periods WHERE id = :id AND match_id = :match_id LIMIT 1');
          $stmt->execute(['id' => $periodId, 'match_id' => $matchId]);
          $row = $stmt->fetch(PDO::FETCH_ASSOC);
} elseif ($periodLabel !== '') {
          $stmt = $pdo->prepare('SELECT id, start_second, label FROM match_periods WHERE match_id = :match_id AND label = :label ORDER BY id ASC LIMIT 1');
          $stmt->execute(['match_id' => $matchId, 'label' => $periodLabel]);
          $row = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$row) {
          http_response_code(404);
          echo json_encode(['ok' => false, 'error' => 'period_not_found']);
          exit;
}

$resolvedPeriodId = (int)$row['id'];
if ($row['start_second'] === null) {
          http_response_code(409);
          echo json_encode(['ok' => false, 'error' => 'period_not_started']);
          exit;
}

$active = get_active_match_period($matchId);
if ($active && (int)$active['id'] !== $resolvedPeriodId) {
          http_response_code(409);
          echo json_encode(['ok' => false, 'error' => 'another_active_period']);
          exit;
}

$labelToUse = $resolvedPeriodLabel !== '' ? $resolvedPeriodLabel : ($row['label'] ?? '');
$result = upsert_match_period_time($matchId, $resolvedPeriodId, $labelToUse, null, max(0, $currentSecond));

echo json_encode([
          'ok' => true,
          'periods' => $result['periods'],
          'active_period_id' => null,
]);
