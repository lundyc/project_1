<?php

require_once __DIR__ . '/../../lib/admin_user_repository.php';
require_once __DIR__ . '/../../lib/csrf.php';
session_start();
require_role('platform_admin');
require_csrf_token();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method Not Allowed';
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$email = trim($_POST['email'] ?? '');
$displayName = trim($_POST['display_name'] ?? '');
$clubId = isset($_POST['club_id']) && $_POST['club_id'] !== '' ? (int)$_POST['club_id'] : null;

$roles = $_POST['roles'] ?? [];
$password = isset($_POST['password']) ? trim($_POST['password']) : null;
$is_active = isset($_POST['is_active']) && $_POST['is_active'] == '1' ? 1 : 0;

if (!$id || !$email || !$displayName) {
    $_SESSION['user_form_error'] = 'Missing required fields.';
    header('Location: /admin/users/' . $id . '/edit');
    exit;
}

try {
    update_user($id, $email, $displayName, $clubId, $roles, $password, $is_active);
    $_SESSION['user_form_success'] = 'User updated successfully.';
    header('Location: /admin/users/' . $id . '/edit');
    exit;
} catch (Exception $e) {
    $_SESSION['user_form_error'] = 'Update failed.';
    header('Location: /admin/users/' . $id . '/edit');
    exit;
}
