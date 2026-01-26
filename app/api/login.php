<?php

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/csrf.php';
require_once __DIR__ . '/../lib/user_repository.php';

// Boot the session if necessary
auth_boot();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Attempt to locate the user by email
$user = find_user_by_email($email);

// Verify credentials; if invalid, return to login
if (!$user || !password_verify($password, $user['password_hash'])) {
    $_SESSION['login_error'] = 'Invalid email or password';
    redirect('/login');
}

// Successful login – store minimal user details in the session
$_SESSION['user'] = [
    'id'          => (int)$user['id'],
    'club_id'     => $user['club_id'],
    'display_name'=> $user['display_name'],
];

// Load user roles into the session
$_SESSION['roles'] = load_user_roles((int)$user['id']);

// Initialize CSRF token for subsequent requests
get_csrf_token();

/*
 * Redirect to a sensible landing page. Previously this always redirected to `/`,
 * but there is no route registered for the root path which resulted in a
 * 404 error. Send users to the statistics dashboard instead, which is the
 * primary landing page after login. Using a leading slash ensures the
 * generated URL respects the configured base path.
 */
redirect('/stats');