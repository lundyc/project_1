<?php

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/csrf.php';
require_once __DIR__ . '/../../lib/club_repository.php';

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

$clubId = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($clubId <= 0) {
          $_SESSION['club_form_error'] = 'Club ID is required';
          redirect('/admin/clubs');
}

if ($clubId === 3) {
          $_SESSION['club_form_error'] = 'Opponents club cannot be deleted';
          redirect('/admin/clubs');
}

if (!get_club_by_id($clubId)) {
          $_SESSION['club_form_error'] = 'Club not found';
          redirect('/admin/clubs');
}

try {
          if (!delete_club($clubId)) {
                    $_SESSION['club_form_error'] = 'Unable to delete club';
                    redirect('/admin/clubs');
          }
          $_SESSION['club_form_success'] = 'Club deleted successfully';
} catch (\Throwable $e) {
          $_SESSION['club_form_error'] = 'Unable to delete club (remove related data first)';
}

redirect('/admin/clubs');
