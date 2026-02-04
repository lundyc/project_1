<?php

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/csrf.php';
require_once __DIR__ . '/../../lib/match_permissions.php';
require_once __DIR__ . '/../../lib/match_player_repository.php';
require_once __DIR__ . '/../../lib/match_repository.php';

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

$matchPlayerId = isset($input['id']) ? (int)$input['id'] : (isset($input['match_player_id']) ? (int)$input['match_player_id'] : 0);
if ($matchPlayerId <= 0) {
          respond_json(400, ['ok' => false, 'error' => 'Match player required']);
}

$matchPlayer = get_match_player($matchPlayerId);
if (!$matchPlayer) {
          respond_json(404, ['ok' => false, 'error' => 'Lineup entry not found']);
}

$match = get_match((int)$matchPlayer['match_id']);
if (!$match) {
          respond_json(404, ['ok' => false, 'error' => 'Match not found']);
}

$user = current_user();
$roles = $_SESSION['roles'] ?? [];

if (!can_manage_match_for_club($user, $roles, (int)$match['club_id'])) {
          respond_json(403, ['ok' => false, 'error' => 'Unauthorized']);
}

$shirtNumberRaw = isset($input['shirt_number']) ? trim((string)$input['shirt_number']) : '';
$positionLabel = isset($input['position_label']) ? trim((string)$input['position_label']) : '';
$isStarting = isset($input['is_starting']) && in_array($input['is_starting'], [1, '1', true], true);
$isCaptain = isset($input['is_captain']) && in_array($input['is_captain'], [1, '1', true], true);

if ($isCaptain) {
          clear_team_captain((int)$matchPlayer['match_id'], (string)$matchPlayer['team_side']);
}

update_match_player($matchPlayerId, [
          'shirt_number' => $shirtNumberRaw === '' ? null : (int)$shirtNumberRaw,
          'position_label' => $positionLabel === '' ? null : $positionLabel,
          'is_starting' => $isStarting,
          'is_captain' => $isCaptain,
]);

$updated = get_match_player($matchPlayerId);

respond_json(200, ['ok' => true, 'match_player' => $updated]);
