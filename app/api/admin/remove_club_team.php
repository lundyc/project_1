<?php

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/csrf.php';
require_once __DIR__ . '/../../lib/club_repository.php';
require_once __DIR__ . '/../../lib/team_repository.php';

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
$teamId = isset($_POST['team_id']) ? (int)$_POST['team_id'] : 0;

if ($clubId <= 0 || $teamId <= 0) {
          $_SESSION['club_team_error'] = 'Invalid club or team selection';
          redirect('/admin/clubs');
}

if (!get_club_by_id($clubId)) {
          $_SESSION['club_team_error'] = 'Club not found';
          redirect('/admin/clubs');
}

$team = get_team_by_id($teamId);
if (!$team) {
          $_SESSION['club_team_error'] = 'Team not found';
          redirect('/admin/clubs/' . $clubId . '/edit');
}

if ((int)$team['club_id'] !== $clubId) {
          $_SESSION['club_team_error'] = 'Team is not assigned to this club';
          redirect('/admin/clubs/' . $clubId . '/edit');
}

$unassignedClubId = 3; // Opponents placeholder club.

try {
          update_team_club($teamId, $unassignedClubId);
          $_SESSION['club_team_success'] = 'Team removed from club';
} catch (\Throwable $e) {
          $_SESSION['club_team_error'] = 'Unable to remove team from club';
}

redirect('/admin/clubs/' . $clubId . '/edit');
