<?php

require_once __DIR__ . '/../../../lib/auth.php';
require_once __DIR__ . '/../../../lib/team_repository.php';

auth_boot();
require_role('platform_admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
          http_response_code(405);
          exit;
}

$teamId = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($teamId <= 0) {
          $_SESSION['team_form_error'] = 'Team ID is required';
          redirect('/admin/teams');
}

if (!get_team_by_id($teamId)) {
          $_SESSION['team_form_error'] = 'Team not found';
          redirect('/admin/teams');
}

try {
          if (!delete_team($teamId)) {
                    $_SESSION['team_form_error'] = 'Unable to delete team';
                    redirect('/admin/teams');
          }
          $_SESSION['team_form_success'] = 'Team deleted successfully';
} catch (\Throwable $e) {
          $_SESSION['team_form_error'] = 'Unable to delete team (remove related data first)';
}

redirect('/admin/teams');
