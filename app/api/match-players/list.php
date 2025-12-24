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

$matchId = isset($_GET['match_id']) ? (int)$_GET['match_id'] : 0;
if ($matchId <= 0) {
          respond_json(400, ['ok' => false, 'error' => 'Match id is required']);
}

$match = get_match($matchId);
if (!$match) {
          respond_json(404, ['ok' => false, 'error' => 'Match not found']);
}

$user = current_user();
$roles = $_SESSION['roles'] ?? [];

if (!can_view_match($user, $roles, (int)$match['club_id'])) {
          respond_json(403, ['ok' => false, 'error' => 'Unauthorized']);
}

$players = get_match_players($matchId);
respond_json(200, ['ok' => true, 'match_players' => $players]);
