<?php

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/csrf.php';
require_once __DIR__ . '/../../lib/match_permissions.php';
require_once __DIR__ . '/../../lib/match_player_repository.php';
require_once __DIR__ . '/../../lib/match_repository.php';
require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../../lib/player_name_helper.php';

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

$matchId = isset($input['match_id']) ? (int)$input['match_id'] : 0;
$teamSide = isset($input['team_side']) ? trim((string)$input['team_side']) : '';
$playerId = isset($input['player_id']) ? (int)$input['player_id'] : 0;
$shirtNumberRaw = isset($input['shirt_number']) ? trim((string)$input['shirt_number']) : '';
$positionLabel = isset($input['position_label']) ? trim((string)$input['position_label']) : '';
$isStarting = !empty($input['is_starting']) && ($input['is_starting'] === '1' || $input['is_starting'] === 1 || $input['is_starting'] === true);
$isCaptain = !empty($input['is_captain']) && ($input['is_captain'] === '1' || $input['is_captain'] === 1 || $input['is_captain'] === true);

if ($matchId <= 0 || !in_array($teamSide, ['home', 'away'], true)) {
          respond_json(422, ['ok' => false, 'error' => 'Invalid lineup data']);
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

$pdo = db();
if ($playerId <= 0) {
          respond_json(422, ['ok' => false, 'error' => 'Player is required']);
}

$playerStmt = $pdo->prepare('SELECT id, first_name, last_name, primary_position FROM players WHERE id = :id AND club_id = :club_id LIMIT 1');
$playerStmt->execute(['id' => $playerId, 'club_id' => (int)$match['club_id']]);
$playerRow = $playerStmt->fetch();
if (!$playerRow) {
          respond_json(404, ['ok' => false, 'error' => 'Player not found']);
}
if ($positionLabel === '') {
          $positionLabel = $playerRow['primary_position'] ?: null;
}

if (find_match_player_by_player($matchId, $teamSide, $playerId)) {
          respond_json(409, ['ok' => false, 'error' => 'Player already in lineup']);
}

$shirtNumber = $shirtNumberRaw === '' ? null : (int)$shirtNumberRaw;
if ($isCaptain) {
          clear_team_captain($matchId, $teamSide);
}

$newId = insert_match_player([
          'match_id' => $matchId,
          'team_side' => $teamSide,
          'player_id' => $playerId > 0 ? $playerId : null,
          'shirt_number' => $shirtNumber,
          'position_label' => $positionLabel ?: null,
          'is_starting' => $isStarting ? 1 : 0,
          'is_captain' => $isCaptain ? 1 : 0,
]);

$record = get_match_player($newId);

respond_json(200, ['ok' => true, 'match_player' => $record]);
