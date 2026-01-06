<?php

require_once __DIR__ . '/../../../lib/auth.php';
require_once __DIR__ . '/../../../lib/match_repository.php';
require_once __DIR__ . '/../../../lib/match_permissions.php';
require_once __DIR__ . '/../../../lib/annotation_repository.php';
require_once __DIR__ . '/../../../lib/api_response.php';
require_once __DIR__ . '/../../../lib/api_helpers.php';
require_once __DIR__ . '/../../../lib/csrf.php';
require_once __DIR__ . '/../../../lib/match_lock_service.php';
require_once __DIR__ . '/../../../lib/match_lock_guard.php';
require_once __DIR__ . '/../../../lib/error_logger.php';

$input = api_read_request_body();

$routePath = '/api/matches/annotations/delete';
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'POST';
$clientIp = $_SERVER['REMOTE_ADDR'] ?? null;
$clientUserAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

auth_boot();
require_auth();

header('Content-Type: application/json');

$sanitizedPayload = sanitizeAnnotationDeletePayload($input);
$matchId = isset($sanitizedPayload['match_id']) ? (int)$sanitizedPayload['match_id'] : 0;
$annotationId = isset($sanitizedPayload['annotation_id']) ? (int)$sanitizedPayload['annotation_id'] : 0;
$sanitizedPayload['match_id'] = $matchId;
$sanitizedPayload['annotation_id'] = $annotationId;

$user = null;
$buildLogContext = static function (array $extra = []) use (
          $routePath,
          $requestMethod,
          $clientIp,
          $clientUserAgent,
          &$sanitizedPayload,
          &$matchId,
          &$annotationId,
          &$user
): array {
          $context = [
                    'route' => $routePath,
                    'method' => $requestMethod,
                    'ip' => $clientIp,
                    'user_agent' => $clientUserAgent,
                    'layer' => 'api',
                    'fn' => 'matches.annotations.delete',
                    'payload' => $sanitizedPayload,
                    'match_id' => $matchId,
                    'annotation_id' => $annotationId,
          ];

          if (isset($user['id'])) {
                    $context['user_id'] = (int)$user['id'];
          }

          return array_merge($context, $extra);
};

try {
          require_csrf_token();
} catch (CsrfException $e) {
          log_api_error($buildLogContext([
                    'message' => 'CSRF validation failed during annotation deletion',
                    'validation' => 'csrf_required',
                    'level' => 'warning',
                    'exception' => $e,
          ]));
          api_error('invalid_csrf', 403, [], $e);
}

if ($matchId <= 0) {
          log_api_error($buildLogContext([
                    'message' => 'Validation failed: match_id missing or invalid',
                    'validation' => 'match_id_required',
                    'level' => 'warning',
          ]));
          api_error('invalid_match', 400);
}

if ($annotationId <= 0) {
          log_api_error($buildLogContext([
                    'message' => 'Validation failed: annotation_id missing or invalid',
                    'validation' => 'annotation_id_required',
                    'level' => 'warning',
          ]));
          api_error('invalid_annotation', 400);
}

$match = get_match($matchId);
if (!$match) {
          log_api_error($buildLogContext([
                    'message' => 'Validation failed: match not found',
                    'reason' => 'match_not_found',
                    'level' => 'warning',
          ]));
          api_error('not_found', 404);
}

$user = current_user();
$roles = $_SESSION['roles'] ?? [];
$canView = can_view_match($user, $roles, (int)$match['club_id']);
if (!$canView) {
          log_api_error($buildLogContext([
                    'message' => 'Permission denied while deleting annotation',
                    'reason' => 'permission_denied',
                    'level' => 'warning',
          ]));
          api_error('forbidden', 403);
}

try {
          require_match_lock($matchId, (int)$user['id']);
} catch (MatchLockException $e) {
          log_api_error($buildLogContext([
                    'message' => 'Match lock required before deleting annotation',
                    'reason' => 'lock_required',
                    'level' => 'warning',
                    'exception' => $e,
          ]));
          api_error('lock_required', 200);
}

$annotation = annotation_find($annotationId);
if (!$annotation || (int)$annotation['match_id'] !== $matchId) {
          log_api_error($buildLogContext([
                    'message' => 'Validation failed: annotation not found for match',
                    'reason' => 'annotation_not_found',
                    'level' => 'warning',
          ]));
          api_error('not_found', 404);
}

try {
          annotation_delete($annotationId);
          api_success(['success' => true]);
} catch (\Throwable $e) {
          log_api_error($buildLogContext([
                    'message' => 'Annotation deletion failed',
                    'level' => 'error',
                    'exception' => $e,
          ]));
          api_error('server_error', 500, [], $e);
}

function sanitizeAnnotationDeletePayload(array $payload): array
{
          return [
                    'match_id' => isset($payload['match_id']) ? (int)$payload['match_id'] : null,
                    'annotation_id' => isset($payload['annotation_id']) ? (int)$payload['annotation_id'] : null,
          ];
}
