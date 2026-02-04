<?php

require_once __DIR__ . '/../../../lib/auth.php';
require_once __DIR__ . '/../../../lib/csrf.php';
require_once __DIR__ . '/../../../lib/club_repository.php';
require_once __DIR__ . '/../../../lib/team_repository.php';

auth_boot();
require_role('platform_admin');

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

$clubId = isset($_POST['club_id']) ? (int)$_POST['club_id'] : 0;
$name = trim($_POST['name'] ?? '');
$teamType = trim($_POST['team_type'] ?? 'club');

if ($name === '' || $clubId <= 0) {
          $_SESSION['team_form_error'] = 'Team name and club are required';
          $_SESSION['team_form_input'] = ['name' => $name, 'club_id' => $clubId, 'team_type' => $teamType];
          redirect('/admin/teams/create');
}

$allowedTypes = ['club', 'opponent'];
if (!in_array($teamType, $allowedTypes, true)) {
          $_SESSION['team_form_error'] = 'Invalid team type';
          $_SESSION['team_form_input'] = ['name' => $name, 'club_id' => $clubId, 'team_type' => $teamType];
          redirect('/admin/teams/create');
}

if (!get_club_by_id($clubId)) {
          $_SESSION['team_form_error'] = 'Club not found';
          $_SESSION['team_form_input'] = ['name' => $name, 'club_id' => $clubId, 'team_type' => $teamType];
          redirect('/admin/teams/create');
}

try {
          create_team_for_club($clubId, $name, $teamType);
          $_SESSION['team_form_success'] = 'Team created successfully';
} catch (\Throwable $e) {
          $_SESSION['team_form_error'] = 'Unable to create team';
          $_SESSION['team_form_input'] = ['name' => $name, 'club_id' => $clubId, 'team_type' => $teamType];
          redirect('/admin/teams/create');
}

redirect('/admin/teams');
