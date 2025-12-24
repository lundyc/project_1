<?php

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/match_permissions.php';
require_once __DIR__ . '/../../lib/match_player_repository.php';

auth_boot();
require_auth();

function respond_json(int $status, array $payload): void
{
          http_response_code($status);
          header('Content-Type: application/json');
          echo json_encode($payload);
          exit;
}

$user = current_user();
$roles = $_SESSION['roles'] ?? [];
$clubId = isset($_GET['club_id']) ? (int)$_GET['club_id'] : (int)($user['club_id'] ?? 0);

if ($clubId <= 0) {
          respond_json(400, ['ok' => false, 'error' => 'Club required']);
}

if (!can_view_match($user, $roles, $clubId)) {
          respond_json(403, ['ok' => false, 'error' => 'Unauthorized']);
}

$players = get_club_players($clubId);
respond_json(200, ['ok' => true, 'players' => $players]);
