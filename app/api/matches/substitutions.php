<?php

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/csrf.php';
require_once __DIR__ . '/../../lib/match_permissions.php';
require_once __DIR__ . '/../../lib/match_repository.php';
require_once __DIR__ . '/../../lib/match_player_repository.php';
require_once __DIR__ . '/../../lib/match_version_service.php';
require_once __DIR__ . '/../../lib/event_repository.php';
require_once __DIR__ . '/../../lib/match_substitution_repository.php';
require_once __DIR__ . '/../../lib/db.php';

auth_boot();
require_auth();

// Validate CSRF token for state-changing operation
try {
    require_csrf_token();
} catch (CsrfException $e) {
    http_response_code(403);
    die('Invalid CSRF token');
}

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

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$routeMatchId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$matchId = $routeMatchId > 0 ? $routeMatchId : (int)($input['match_id'] ?? 0);

if ($matchId <= 0) {
          respond_json(422, ['ok' => false, 'error' => 'Invalid match data']);
}

$match = get_match($matchId);
if (!$match) {
          respond_json(404, ['ok' => false, 'error' => 'Match not found']);
}

$user = current_user();
$roles = $_SESSION['roles'] ?? [];

if (!in_array($method, ['POST'])) {
          respond_json(405, ['ok' => false, 'error' => 'Method not allowed']);
}

$teamSide = trim(strtolower((string)($input['team_side'] ?? '')));
$matchSecond = isset($input['match_second']) ? (int)$input['match_second'] : 0;
$minute = isset($input['minute']) ? $input['minute'] : null;
$minuteExtra = array_key_exists('minute_extra', $input) && $input['minute_extra'] !== '' ? (int)$input['minute_extra'] : null;
$playerOffId = isset($input['player_off_match_player_id']) ? (int)$input['player_off_match_player_id'] : 0;
$playerOnId = isset($input['player_on_match_player_id']) ? (int)$input['player_on_match_player_id'] : 0;
$reasonRaw = strtolower(trim((string)($input['reason'] ?? '')));
$allowedReasons = ['tactical', 'injury', 'fitness', 'disciplinary', 'unknown'];
$reason = in_array($reasonRaw, $allowedReasons, true) ? $reasonRaw : 'unknown';

if (!in_array($teamSide, ['home', 'away'], true)) {
          respond_json(422, ['ok' => false, 'error' => 'Invalid match data']);
}

$minuteValue = $minute !== null ? (int)$minute : null;
if ($minuteValue === null || $minuteValue < 0) {
          respond_json(422, ['ok' => false, 'error' => 'Minute is required']);
}

$matchSecondValue = $matchSecond < 0 ? 0 : $matchSecond;
$minuteExtraValue = $minuteExtra !== null ? max(0, $minuteExtra) : null;

if ($playerOffId <= 0 || $playerOnId <= 0 || $playerOffId === $playerOnId) {
          respond_json(422, ['ok' => false, 'error' => 'Player selection is invalid']);
}

if (!can_manage_match_for_club($user, $roles, (int)$match['club_id'])) {
          respond_json(403, ['ok' => false, 'error' => 'Unauthorized']);
}

$playerOff = get_match_player($playerOffId);
$playerOn = get_match_player($playerOnId);
if (!$playerOff || !$playerOn) {
          respond_json(404, ['ok' => false, 'error' => 'Player not found']);
}

if ((int)($playerOff['match_id'] ?? 0) !== $matchId || (int)($playerOn['match_id'] ?? 0) !== $matchId) {
          respond_json(422, ['ok' => false, 'error' => 'Player does not belong to match']);
}

$offSide = strtolower((string)($playerOff['team_side'] ?? ''));
$onSide = strtolower((string)($playerOn['team_side'] ?? ''));
if ($offSide !== $teamSide || $onSide !== $teamSide) {
          respond_json(422, ['ok' => false, 'error' => 'Player team mismatch']);
}

if ((int)$playerOff['is_starting'] !== 1) {
          respond_json(409, ['ok' => false, 'error' => 'Player off is not on pitch']);
}

if ((int)$playerOn['is_starting'] === 1) {
          respond_json(409, ['ok' => false, 'error' => 'Player on is already on pitch']);
}

$positionLabel = trim((string)($playerOff['position_label'] ?? ''));
if ($positionLabel === '') {
          $positionLabel = trim((string)($playerOn['position_label'] ?? ''));
}
$positionLabel = $positionLabel !== '' ? $positionLabel : null;

$playerOffCaptain = isset($playerOff['is_captain']) ? (int)$playerOff['is_captain'] : 0;
$playerOnCaptain = isset($playerOn['is_captain']) ? (int)$playerOn['is_captain'] : 0;

$pdo = db();
$pdo->beginTransaction();

try {
          $eventId = null;
          try {
                    $eventTypeId = ensure_event_type_exists((int)$match['club_id'], 'substitution', 'Substitution', 2);
                    $eventPayload = [
                              'match_second' => $matchSecondValue,
                              'minute' => $minuteValue,
                              'minute_extra' => $minuteExtraValue,
                              'team_side' => $teamSide,
                              'event_type_id' => $eventTypeId,
                              'importance' => 2,
                              'phase' => 'unknown',
                              'match_player_id' => $playerOnId,
                              'notes' => 'Reason: ' . ucfirst($reason),
                    ];
                    $eventId = event_create($matchId, $eventPayload, [], (int)$user['id'], false);
          } catch (\Throwable $eventError) {
                    error_log(sprintf('Substitution event skipped for match %d: %s', $matchId, $eventError->getMessage()));
                    $eventId = null;
          }

          $substitutionId = insert_match_substitution([
                    'match_id' => $matchId,
                    'team_side' => $teamSide,
                    'match_second' => $matchSecondValue,
                    'minute' => $minuteValue,
                    'minute_extra' => $minuteExtraValue,
                    'player_off_match_player_id' => $playerOffId,
                    'player_on_match_player_id' => $playerOnId,
                    'reason' => $reason,
                    'event_id' => $eventId,
                    'created_by' => (int)($user['id'] ?? 0),
          ]);

          $eventsVersion = bump_events_version($matchId);
          $pdo->commit();
} catch (\Throwable $e) {
          $pdo->rollBack();
          respond_json(500, ['ok' => false, 'error' => $e->getMessage() ?: 'Unable to save substitution']);
}

$substitutions = get_match_substitutions($matchId);
$inserted = null;
foreach ($substitutions as $record) {
          if ((int)$record['id'] === $substitutionId) {
                    $inserted = $record;
                    break;
          }
}

respond_json(200, [
          'ok' => true,
          'substitution' => $inserted ? $inserted : ['id' => $substitutionId],
          'substitutions' => $substitutions,
          'events_version' => $eventsVersion ?? null,
]);
