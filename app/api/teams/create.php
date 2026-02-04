<?php

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/csrf.php';
require_once __DIR__ . '/../../lib/team_repository.php';
require_once __DIR__ . '/../../lib/match_permissions.php';

auth_boot();
require_auth();

// Validate CSRF token for state-changing operation
try {
    require_csrf_token();
} catch (CsrfException $e) {
    http_response_code(403);
    die('Invalid CSRF token');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
          http_response_code(405);
          exit;
}

$user = current_user();
$roles = $_SESSION['roles'] ?? [];
$clubId = (int)($_POST['club_id'] ?? 0);
$name = trim($_POST['name'] ?? '');

if (!$clubId || $name === '') {
          $_SESSION['match_form_error'] = 'Team name is required';
          redirect('/matches/create' . ($clubId ? '?club_id=' . $clubId : ''));
}

if (!can_manage_matches($user, $roles)) {
          http_response_code(403);
          exit;
}

if (!in_array('platform_admin', $roles, true) && (!isset($user['club_id']) || (int)$user['club_id'] !== $clubId)) {
          http_response_code(403);
          exit;
}

try {
          create_team_for_club($clubId, $name, 'club');
          $_SESSION['match_form_success'] = 'Team created';
} catch (\Throwable $e) {
          $_SESSION['match_form_error'] = 'Unable to create team';
}

redirect('/matches/create?club_id=' . $clubId);
