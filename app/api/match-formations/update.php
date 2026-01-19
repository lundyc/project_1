<?php

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/match_repository.php';
require_once __DIR__ . '/../../lib/match_permissions.php';
require_once __DIR__ . '/../../lib/formation_repository.php';

auth_boot();
require_auth();

function respond_json(int $status, array $payload): void
{
          http_response_code($status);
          header('Content-Type: application/json');
          echo json_encode($payload);
          exit;
}

$input = $_POST;
$rawInput = file_get_contents('php://input');
if (empty($input) && $rawInput) {
          $decoded = json_decode($rawInput, true);
          if (is_array($decoded)) {
                    $input = $decoded;
          }
}

$matchId = isset($input['match_id']) ? (int)$input['match_id'] : 0;
$teamSide = isset($input['team_side']) ? strtolower(trim((string)$input['team_side'])) : '';
$formationId = $input['formation_id'] ?? null;
$format = isset($input['format']) ? trim((string)$input['format']) : '';
$formationKey = isset($input['formation_key']) ? trim((string)$input['formation_key']) : '';
if ($formationId !== null && $formationId !== '') {
          $formationId = (int)$formationId;
          if ($formationId <= 0) {
                    $formationId = null;
          }
} else {
          $formationId = null;
}

if ($matchId <= 0 || !in_array($teamSide, ['home', 'away'], true)) {
          respond_json(422, ['ok' => false, 'error' => 'Invalid selection']);
}

$match = get_match($matchId);
if (!$match) {
          respond_json(404, ['ok' => false, 'error' => 'Match not found']);
}

$user = current_user();
$roles = $_SESSION['roles'] ?? [];
if (!can_manage_match_for_club($user, $roles, (int)$match['club_id'])) {
          respond_json(403, ['ok' => false, 'error' => 'Unauthorized']);
}

if ($formationId === null || $format === '' || $formationKey === '') {
          respond_json(422, ['ok' => false, 'error' => 'Formation selection is required']);
}

$formation = find_formation_by_id($formationId);
if (!$formation) {
          respond_json(404, ['ok' => false, 'error' => 'Formation not found']);
}

$userId = $user['id'] ?? null;
$matchPeriodId = $input['match_period_id'] ?? null;
if ($matchPeriodId === '') {
          $matchPeriodId = null;
}
$matchSecond = isset($input['match_second']) ? (int)$input['match_second'] : 0;
$minute = isset($input['minute']) ? (int)$input['minute'] : 0;
$minuteExtra = isset($input['minute_extra']) ? (int)$input['minute_extra'] : 0;
$layoutJsonValue = isset($input['layout_json']) ? trim((string)$input['layout_json']) : null;
if ($layoutJsonValue === '') {
          $layoutJsonValue = null;
}

record_match_formation_selection(
          $matchId,
          $teamSide,
          $format,
          $formationKey,
          [
                    'match_period_id' => $matchPeriodId,
                    'match_second' => $matchSecond,
                    'minute' => $minute,
                    'minute_extra' => $minuteExtra,
                    'layout_json' => $layoutJsonValue,
                    'notes' => null,
                    'created_by' => $userId,
          ]
);

$homeActive = get_active_match_formation($matchId, 'home');
$awayActive = get_active_match_formation($matchId, 'away');

respond_json(200, [
          'ok' => true,
          'match_formations' => [
                    'home' => isset($homeActive['formation_id']) ? (int)$homeActive['formation_id'] : null,
                    'away' => isset($awayActive['formation_id']) ? (int)$awayActive['formation_id'] : null,
          ],
]);
