<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/match_repository.php';
require_once __DIR__ . '/../../lib/match_permissions.php';
require_once __DIR__ . '/../../lib/event_action_stack.php';
require_once __DIR__ . '/../../lib/api_response.php';
require_once __DIR__ . '/../../lib/csrf.php';
require_once __DIR__ . '/../../lib/match_lock_service.php';
require_once __DIR__ . '/../../lib/match_lock_guard.php';
require_once __DIR__ . '/../../lib/match_version_service.php';
require_once __DIR__ . '/../../lib/audit_service.php';

auth_boot();
require_auth();

header('Content-Type: application/json');

try {
          require_csrf_token();
} catch (CsrfException $e) {
          api_error('invalid_csrf', 403, [], $e);
}

$matchId = isset($matchId) ? (int)$matchId : (int)($_POST['match_id'] ?? 0);

if ($matchId <= 0) {
          api_error('invalid_match', 400);
}

$match = get_match($matchId);
if (!$match) {
          api_error('not_found', 404);
}

$user = current_user();
$roles = $_SESSION['roles'] ?? [];
$canEdit = in_array('platform_admin', $roles, true) || in_array('club_admin', $roles, true) || in_array('analyst', $roles, true);
$canManage = $canEdit && can_manage_match_for_club($user, $roles, (int)$match['club_id']);

if (!$canManage) {
          api_error('forbidden', 403);
}

try {
          require_match_lock($matchId, (int)$user['id']);
} catch (MatchLockException $e) {
          error_log(sprintf('[lock-failed] match=%d user=%d reason=%s', $matchId, (int)$user['id'], $e->getMessage()));
          api_error('lock_required', 200);
}

$actionResult = undo_last_action($matchId, (int)$user['id']);
if (!$actionResult) {
          api_error('no_actions', 400);
}

$version = bump_events_version($matchId);
$auditEventId = $actionResult['audit_event_id'] ?? null;
$auditType = $actionResult['audit_type'] ?? 'update';
if (array_key_exists('audit_before', $actionResult)) {
          $auditBefore = $actionResult['audit_before'] === null ? null : json_encode($actionResult['audit_before']);
} else {
          $auditBefore = null;
}
if (array_key_exists('audit_after', $actionResult)) {
          $auditAfter = $actionResult['audit_after'] === null ? null : json_encode($actionResult['audit_after']);
} else {
          $auditAfter = null;
}
if ($auditEventId !== null) {
          audit((int)$match['club_id'], (int)$user['id'], 'event', $auditEventId, $auditType, $auditBefore, $auditAfter);
}

api_success([
          'event' => $actionResult['event'] ?? null,
          'meta' => [
                    'events_version' => $version,
                    'action_stack' => get_event_action_stack_status($matchId, (int)$user['id']),
          ],
]);
