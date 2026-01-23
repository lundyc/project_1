<?php

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/admin_user_repository.php';

auth_boot();
require_role('platform_admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
          http_response_code(405);
          exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$displayName = trim($_POST['display_name'] ?? '');
$clubId = $_POST['club_id'] ?? null;
$roleIds = $_POST['role_ids'] ?? [];

$clubId = $clubId === '' ? null : (int)$clubId;
$roleIds = array_map('intval', (array)$roleIds);

if ($email === '' || $password === '' || $displayName === '' || empty($roleIds)) {
          $_SESSION['user_form_error'] = 'Email, password, display name, and at least one role are required';
          redirect('/admin/users');
}

try {
          create_user($email, $password, $displayName, $clubId, $roleIds);
          $_SESSION['user_form_success'] = 'User created successfully';
} catch (\Throwable $e) {
          $_SESSION['user_form_error'] = 'Unable to create user';
}

redirect('/admin/users');
