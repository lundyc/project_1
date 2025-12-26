<?php

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/match_repository.php';
require_once __DIR__ . '/../../lib/match_permissions.php';
require_once __DIR__ . '/../../lib/event_repository.php';
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
$eventId = (int)($_POST['event_id'] ?? 0);

if ($matchId <= 0 || $eventId <= 0) {
          api_error('invalid_match', 400);
}

$match = get_match($matchId);
if (!$match) {
          api_error('not_found', 404);
}

$existing = event_get_by_id($eventId);
if (!$existing || (int)$existing['match_id'] !== $matchId) {
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

try {
          $before = event_get_by_id($eventId);
          event_delete($eventId, (int)$user['id']);
          $version = bump_events_version($matchId);
          audit((int)$match['club_id'], (int)$user['id'], 'event', $eventId, 'delete', json_encode($before), null);

          api_success([
                    'event' => null,
                    'meta' => ['events_version' => $version],
          ]);
} catch (\Throwable $e) {
          api_error('server_error', 500, [], $e);
}
