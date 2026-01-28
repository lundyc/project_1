<?php

require_once __DIR__ . '/../../../lib/auth.php';
require_once __DIR__ . '/../../../lib/club_repository.php';
require_once __DIR__ . '/../../../lib/team_repository.php';

auth_boot();
require_role('platform_admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
          http_response_code(405);
          exit;
}

$teamId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$clubId = isset($_POST['club_id']) ? (int)$_POST['club_id'] : 0;
$name = trim($_POST['name'] ?? '');
$teamType = trim($_POST['team_type'] ?? 'club');

if ($teamId <= 0 || $name === '' || $clubId <= 0) {
          $_SESSION['team_form_error'] = 'Team name and club are required';
          $_SESSION['team_form_input'] = ['name' => $name, 'club_id' => $clubId, 'team_type' => $teamType];
          redirect('/admin/teams/' . $teamId . '/edit');
}

$allowedTypes = ['club', 'opponent'];
if (!in_array($teamType, $allowedTypes, true)) {
          $_SESSION['team_form_error'] = 'Invalid team type';
          $_SESSION['team_form_input'] = ['name' => $name, 'club_id' => $clubId, 'team_type' => $teamType];
          redirect('/admin/teams/' . $teamId . '/edit');
}

if (!get_club_by_id($clubId)) {
          $_SESSION['team_form_error'] = 'Club not found';
          $_SESSION['team_form_input'] = ['name' => $name, 'club_id' => $clubId, 'team_type' => $teamType];
          redirect('/admin/teams/' . $teamId . '/edit');
}

if (!get_team_by_id($teamId)) {
          $_SESSION['team_form_error'] = 'Team not found';
          redirect('/admin/teams');
}

try {
          $ok = update_team($teamId, $clubId, $name, $teamType);
          if (!$ok) {
                    $_SESSION['team_form_error'] = 'Unable to update team';
                    $_SESSION['team_form_input'] = ['name' => $name, 'club_id' => $clubId, 'team_type' => $teamType];
                    redirect('/admin/teams/' . $teamId . '/edit');
          }
          $_SESSION['team_form_success'] = 'Team updated successfully';
} catch (\Throwable $e) {
          $_SESSION['team_form_error'] = 'Unable to update team';
          $_SESSION['team_form_input'] = ['name' => $name, 'club_id' => $clubId, 'team_type' => $teamType];
          redirect('/admin/teams/' . $teamId . '/edit');
}

redirect('/admin/teams/' . $teamId . '/edit');
