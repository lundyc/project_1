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

$name = trim($_POST['name'] ?? '');

if ($name === '') {
          $_SESSION['club_form_error'] = 'Club name is required';
          $_SESSION['club_form_input'] = ['name' => $name];
          redirect('/admin/clubs/create');
}

try {
          create_club($name);
          $_SESSION['club_form_success'] = 'Club created successfully';
} catch (\Throwable $e) {
          $_SESSION['club_form_error'] = 'Unable to create club';
          $_SESSION['club_form_input'] = ['name' => $name];
          redirect('/admin/clubs/create');
}

redirect('/admin/clubs');
