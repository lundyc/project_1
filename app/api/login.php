<?php

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/csrf.php';
require_once __DIR__ . '/../lib/user_repository.php';
require_once __DIR__ . '/../lib/rate_limit.php';
require_once __DIR__ . '/../lib/security_event_log.php';

// Boot the session if necessary
auth_boot();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Validate CSRF token first
try {
    require_csrf_token();
} catch (CsrfException $e) {
    log_csrf_failure('login', null);
    $_SESSION['login_error'] = 'Invalid CSRF token - please try logging in again';
    redirect('/login');
}

// Check rate limiting on login attempts
$rateLimit = check_rate_limit($email, 'login', false);
if (!$rateLimit['allowed']) {
    log_rate_limit_exceeded('login', RATE_LIMIT_MAX_ATTEMPTS, $rateLimit['reset_in']);
    $_SESSION['login_error'] = 'Too many failed login attempts. Please try again in ' . ceil($rateLimit['reset_in'] / 60) . ' minutes.';
    redirect('/login');
}

// Attempt to locate the user by email
$user = find_user_by_email($email);

// Verify credentials; if invalid, return to login with rate limiting
if (!$user || !password_verify($password, $user['password_hash'])) {
    record_failed_attempt($email, 'login');
    log_failed_auth($email, $user ? 'invalid_password' : 'user_not_found');
    $_SESSION['login_error'] = 'Invalid email or password';
    redirect('/login');
}

// Clear rate limit on successful login
clear_rate_limit($email, 'login');

// Successful login – regenerate session ID to prevent session fixation
session_regenerate_id(true);

// Store minimal user details in the session
$_SESSION['user_id'] = (int)$user['id'];
$_SESSION['user'] = [
    'id'          => (int)$user['id'],
    'club_id'     => $user['club_id'],
    'display_name'=> $user['display_name'],
];

// Load user roles into the session
$_SESSION['roles'] = load_user_roles((int)$user['id']);

// Log successful authentication
log_successful_auth((int)$user['id'], $_SESSION['roles']);

// Initialize CSRF token for subsequent requests
get_csrf_token();

redirect('/matches');