<?php

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/match_repository.php';
require_once __DIR__ . '/../../lib/match_permissions.php';
require_once __DIR__ . '/../../lib/match_period_repository.php';

auth_boot();
require_auth();

$acceptsJson = (bool)preg_match('#json#i', $_SERVER['HTTP_ACCEPT'] ?? '') || (bool)preg_match('#json#i', $_SERVER['CONTENT_TYPE'] ?? '');
if ($acceptsJson) {
          header('Content-Type: application/json');
}

$raw = file_get_contents('php://input');
$payload = [];
if ($raw) {
          $decoded = json_decode($raw, true);
          if (is_array($decoded)) {
                    $payload = $decoded;
          }
}

$matchId = isset($matchId) ? (int)$matchId : (int)($_POST['match_id'] ?? ($payload['match_id'] ?? 0));
$preset = $_POST['preset'] ?? ($payload['preset'] ?? '');

if ($matchId <= 0) {
          http_response_code(400);
          if ($acceptsJson) {
                    echo json_encode(['ok' => false, 'error' => 'match_id_required']);
                    exit;
          }
          exit;
}

$match = get_match($matchId);
if (!$match) {
          http_response_code(404);
          if ($acceptsJson) {
                    echo json_encode(['ok' => false, 'error' => 'not_found']);
                    exit;
          }
          echo 'Match not found';
          exit;
}

$user = current_user();
$roles = $_SESSION['roles'] ?? [];

if (!can_manage_match_for_club($user, $roles, (int)$match['club_id'])) {
          http_response_code(403);
          if ($acceptsJson) {
                    echo json_encode(['ok' => false, 'error' => 'forbidden']);
                    exit;
          }
          exit;
}

$presets = [
          '11' => [
                    ['label' => 'First Half', 'minutes_planned' => 45],
                    ['label' => 'Second Half', 'minutes_planned' => 45],
          ],
          '9' => [
                    ['label' => 'First Half', 'minutes_planned' => 30],
                    ['label' => 'Second Half', 'minutes_planned' => 30],
          ],
          '7' => [
                    ['label' => 'First Half', 'minutes_planned' => 25],
                    ['label' => 'Second Half', 'minutes_planned' => 25],
          ],
];

if (!isset($presets[$preset])) {
          if ($acceptsJson) {
                    http_response_code(422);
                    echo json_encode(['ok' => false, 'error' => 'Invalid preset selection']);
                    exit;
          }
          $_SESSION['periods_error'] = 'Invalid preset selection';
          redirect('/matches/' . $matchId . '/periods');
}

$periods = [];
foreach ($presets[$preset] as $idx => $period) {
          $periods[] = [
                    'period_index' => $idx,
                    'period_type' => 'normal',
                    'label' => $period['label'],
                    'minutes_planned' => $period['minutes_planned'],
          ];
}

try {
          replace_match_periods($matchId, $periods);
          if ($acceptsJson) {
                    echo json_encode(['ok' => true, 'periods' => get_match_periods($matchId)]);
                    exit;
          }
          $_SESSION['periods_success'] = 'Preset periods applied';
} catch (\Throwable $e) {
          if ($acceptsJson) {
                    http_response_code(500);
                    echo json_encode(['ok' => false, 'error' => 'Unable to apply preset']);
                    exit;
          }
          $_SESSION['periods_error'] = 'Unable to apply preset';
}

redirect('/matches/' . $matchId . '/periods');
