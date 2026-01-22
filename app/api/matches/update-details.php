<?php
/**
 * Match Details Update - Wrapper for compatibility
 * Delegates to main update.php endpoint
 */
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/match_repository.php';
require_once __DIR__ . '/../../lib/match_permissions.php';
require_once __DIR__ . '/../../lib/db.php';

auth_boot();
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method Not Allowed';
    exit;
}

$matchId = (int)($_POST['match_id'] ?? 0);

if (!$matchId) {
    $_SESSION['match_form_error'] = 'Invalid match ID';
    redirect('/matches');
    exit;
}

$match = get_match($matchId);

if (!$match) {
    http_response_code(404);
    echo '404 Not Found';
    exit;
}

$user = current_user();
$roles = $_SESSION['roles'] ?? [];

if (!can_manage_match_for_club($user, $roles, (int)$match['club_id'])) {
    http_response_code(403);
    echo '403 Forbidden';
    exit;
}

// Delegate to main update logic by forwarding the request
// This maintains backward compatibility while using the centralized update logic
$wantsJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

// Forward POST data to the main update endpoint
$_POST['match_id'] = $matchId;

// Include the main update logic
include __DIR__ . '/update.php';
