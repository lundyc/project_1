<?php

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/api_response.php';
require_once __DIR__ . '/../../lib/match_repository.php';
require_once __DIR__ . '/../../lib/match_permissions.php';
require_once __DIR__ . '/../../lib/clip_review_service.php';
require_once __DIR__ . '/../../lib/phase3.php';

auth_boot();
require_auth();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
          api_error('method_not_allowed', 405);
}

$matchId = isset($matchId) ? (int)$matchId : 0;
$clipId = isset($clipId) ? (int)$clipId : 0;

if ($matchId <= 0 || $clipId <= 0) {
          api_error('invalid_clip', 400);
}

if (!phase3_is_enabled()) {
          api_error('phase3_disabled', 403);
}

$match = get_match($matchId);
if (!$match) {
          api_error('not_found', 404);
}

$user = current_user();
$roles = $_SESSION['roles'] ?? [];
$canAccessVideoLab = in_array('analyst', $roles, true)
          || in_array('club_admin', $roles, true)
          || in_array('platform_admin', $roles, true);
if (!$canAccessVideoLab || !can_view_match($user, $roles, (int)($match['club_id'] ?? 0))) {
          api_error('forbidden', 403);
}

$action = strtolower(trim($_GET['action'] ?? ''));
if (!in_array($action, ['approve', 'reject'], true)) {
          api_error('invalid_action', 400);
}

$status = $action === 'approve' ? 'approved' : 'rejected';

try {
          $result = clip_review_service_review_clip(
                    $matchId,
                    $clipId,
                    (int)($user['id'] ?? 0),
                    $status,
                    (int)($match['club_id'] ?? 0)
          );
          api_success([
                    'clip' => $result['clip'],
                    'summary' => $result['summary'],
          ]);
} catch (InvalidArgumentException $e) {
          $mapping = [
                    'invalid_action' => 400,
                    'clip_not_found' => 404,
                    'review_not_pending' => 409,
          ];
          $statusCode = $mapping[$e->getMessage()] ?? 422;
          api_error($e->getMessage(), $statusCode, [], $e);
} catch (RuntimeException $e) {
          if ($e->getMessage() === 'phase3_disabled') {
                    api_error('phase3_disabled', 403, [], $e);
          }
          api_error('server_error', 500, [], $e);
} catch (\Throwable $e) {
          api_error('server_error', 500, [], $e);
}
