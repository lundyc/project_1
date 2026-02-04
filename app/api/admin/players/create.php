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

$context = require_club_admin_access();
$user = $context['user'];
$clubId = $context['club_id'];

$input = $_POST;
$rawInput = file_get_contents('php://input');
if (empty($input) && $rawInput) {
          $decoded = json_decode($rawInput, true);
          if (is_array($decoded)) {
                    $input = $decoded;
          }
}

$firstName = isset($input['first_name']) ? trim((string)$input['first_name']) : '';
$lastName = isset($input['last_name']) ? trim((string)$input['last_name']) : '';
if ($firstName === '' && $lastName === '') {
          $_SESSION['player_create_error'] = 'First name or last name is required.';
          $_SESSION['player_create_input'] = $input;
          redirect('/admin/players/create');
}

$payload = normalize_player_payload($input, $clubId);

$playerId = create_player_for_club($clubId, $payload);

$auditData = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
audit($clubId, (int)$user['id'], 'player', $playerId, 'created', null, $auditData);

$_SESSION['player_flash_success'] = 'Player created.';
redirect('/admin/players');
