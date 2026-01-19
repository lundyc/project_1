<?php

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/match_permissions.php';
require_once __DIR__ . '/../../lib/match_player_repository.php';
require_once __DIR__ . '/../../lib/match_repository.php';

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

$substitutionDeleteError = 'This player cannot be removed because they were involved in a substitution.';
try {
          delete_match_player($matchPlayerId);
} catch (\RuntimeException $e) {
          if ($e->getMessage() === $substitutionDeleteError) {
                    respond_json(409, ['ok' => false, 'error' => 'Player cannot be removed because they were involved in a substitution.']);
          }
          respond_json(500, ['ok' => false, 'error' => $e->getMessage() ?: 'Unable to remove player']);
}

respond_json(200, ['ok' => true]);
