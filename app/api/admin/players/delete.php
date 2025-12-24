<?php

require_once __DIR__ . '/../../../lib/auth.php';
require_once __DIR__ . '/../../../lib/audit_service.php';
require_once __DIR__ . '/../../../lib/player_repository.php';

auth_boot();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
          http_response_code(405);
          echo '405 Method Not Allowed';
          exit;
}

if (!isset($playerId)) {
          http_response_code(400);
          echo '400 Bad Request';
          exit;
}

$context = require_club_admin_access();
$user = $context['user'];
$clubId = $context['club_id'];

$player = get_player_by_id($playerId, $clubId);
if (!$player) {
          http_response_code(404);
          echo '404 Not Found';
          exit;
}

$before = json_encode($player, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
deactivate_player($playerId, $clubId);

$after = json_encode(['is_active' => 0], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
audit($clubId, (int)$user['id'], 'player', $playerId, 'deleted', $before, $after);

$_SESSION['player_flash_success'] = 'Player marked inactive.';
redirect('/admin/players');
