<?php

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/club_repository.php';

auth_boot();
require_role('platform_admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
          http_response_code(405);
          exit;
}

$name = trim($_POST['name'] ?? '');

if ($name === '') {
          $_SESSION['club_form_error'] = 'Club name is required';
          redirect('/admin/clubs');
}

try {
          create_club($name);
          $_SESSION['club_form_success'] = 'Club created successfully';
} catch (\Throwable $e) {
          $_SESSION['club_form_error'] = 'Unable to create club';
}

redirect('/admin/clubs');
