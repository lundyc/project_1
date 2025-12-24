<?php

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/user_repository.php';

auth_boot();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
          http_response_code(405);
          exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

$user = find_user_by_email($email);

if (!$user || !password_verify($password, $user['password_hash'])) {
          $_SESSION['login_error'] = 'Invalid email or password';
          redirect('/login');
}

$_SESSION['user'] = [
          'id' => (int)$user['id'],
          'club_id' => $user['club_id'],
          'display_name' => $user['display_name'],
];

$_SESSION['roles'] = load_user_roles((int)$user['id']);

redirect('/');
