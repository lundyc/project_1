<?php

require_once __DIR__ . '/../../../lib/auth.php';
require_once __DIR__ . '/../../../lib/csrf.php';
require_once __DIR__ . '/../../../lib/audit_service.php';
require_once __DIR__ . '/../../../lib/player_repository.php';
require_once __DIR__ . '/../../../lib/player_input.php';

auth_boot();
require_role('platform_admin');

// Validate CSRF token for state-changing operation
try {
    require_csrf_token();
} catch (CsrfException $e) {
    http_response_code(403);
    die('Invalid CSRF token');
}

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

$input = $_POST;
$rawInput = file_get_contents('php://input');
if (empty($input) && $rawInput) {
          $decoded = json_decode($rawInput, true);
          if (is_array($decoded)) {
                    $input = $decoded;
          }
}

$payload = normalize_player_payload($input, $clubId);

if (!$payload['first_name'] && !$payload['last_name']) {
          $_SESSION['player_edit_error'] = 'First name or last name is required.';
          $_SESSION['player_edit_input'] = $input;
          $_SESSION['player_edit_target'] = $playerId;
          redirect('/admin/players/' . $playerId . '/edit');
}

$before = json_encode($player, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
update_player_for_club($playerId, $clubId, $payload);

$after = json_encode(array_merge($player, $payload), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
audit($clubId, (int)$user['id'], 'player', $playerId, 'updated', $before, $after);

$_SESSION['player_flash_success'] = 'Player updated.';
redirect('/admin/players');
