<?php

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/match_repository.php';
require_once __DIR__ . '/../../lib/match_permissions.php';
require_once __DIR__ . '/../../lib/clip_regeneration_service.php';
require_once __DIR__ . '/../../lib/api_response.php';

auth_boot();
require_auth();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
          api_error('method_not_allowed', 405);
}

$matchId = isset($matchId) ? (int)$matchId : 0;

if ($matchId <= 0) {
          api_error('invalid_match', 400);
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
if (!$canAccessVideoLab || !can_view_match($user, $roles, (int)$match['club_id'])) {
          api_error('forbidden', 403);
}

try {
          $result = clip_regeneration_service_regenerate_match($matchId, (int)($user['id'] ?? 0));
          api_success([
                    'meta' => [
                              'clips_version' => $result['clips_version'],
                              'regenerated' => $result['regenerated'],
                    ],
          ]);
} catch (InvalidArgumentException $e) {
          clip_regeneration_service_handle_match_client_error($e);
} catch (RuntimeException $e) {
          if ($e->getMessage() === 'phase3_disabled') {
                    api_error('phase3_disabled', 403, [], $e);
          }
          api_error($e->getMessage(), 409, [], $e);
} catch (\Throwable $e) {
          api_error('server_error', 500, [], $e);
}

function clip_regeneration_service_handle_match_client_error(InvalidArgumentException $exception): void
{
          $mapping = [
                    'match_not_found' => 404,
          ];
          $code = $exception->getMessage();
          $status = $mapping[$code] ?? 422;
          api_error($code, $status, [], $exception);
}
