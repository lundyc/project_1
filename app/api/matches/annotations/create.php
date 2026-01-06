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

auth_boot();
require_auth();

header('Content-Type: application/json');

$routePath = '/api/matches/annotations/create';
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'POST';
$clientIp = $_SERVER['REMOTE_ADDR'] ?? null;
$clientUserAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

try {
          require_csrf_token();
} catch (CsrfException $e) {
          log_api_error([
                    'route' => $routePath,
                    'method' => $requestMethod,
                    'ip' => $clientIp,
                    'user_agent' => $clientUserAgent,
                    'layer' => 'api',
                    'fn' => 'matches.annotations.create',
                    'message' => 'CSRF validation failed during annotation creation',
                    'level' => 'warning',
                    'exception' => $e,
          ]);
          api_error('invalid_csrf', 403, [], $e);
}

$input = api_read_request_body();
$sanitizedPayload = sanitizeAnnotationPayload($input);
$matchId = isset($matchId) ? (int)$matchId : (int)($input['match_id'] ?? 0);
$targetType = strtolower(trim((string)($input['target_type'] ?? '')));
$targetId = isset($input['target_id']) ? (int)$input['target_id'] : 0;
$timestampRaw = $input['timestamp_second'] ?? null;
$timestamp = is_numeric($timestampRaw) ? (int)$timestampRaw : null;
$rawDrawing = $input['drawing_data'] ?? null;
$notes = isset($input['notes']) ? trim((string)$input['notes']) : null;

$sanitizedPayload['match_id'] = $matchId;
$sanitizedPayload['target_type'] = $targetType;
$sanitizedPayload['target_id'] = $targetId;
$sanitizedPayload['timestamp_second'] = $timestamp;

$user = null;
$buildLogContext = static function (array $extra = []) use (
          $routePath,
          $requestMethod,
          $clientIp,
          $clientUserAgent,
          &$sanitizedPayload,
          &$matchId,
          &$targetType,
          &$targetId,
          &$timestamp,
          &$user
): array {
          $context = [
                    'route' => $routePath,
                    'method' => $requestMethod,
                    'ip' => $clientIp,
                    'user_agent' => $clientUserAgent,
                    'layer' => 'api',
                    'fn' => 'matches.annotations.create',
                    'payload' => $sanitizedPayload,
                    'match_id' => $matchId,
                    'target_type' => $targetType,
                    'target_id' => $targetId,
                    'timestamp_second' => $timestamp,
          ];

          if (isset($user['id'])) {
                    $context['user_id'] = (int)$user['id'];
          }

          return array_merge($context, $extra);
};

if ($matchId <= 0) {
          log_api_error($buildLogContext([
                    'message' => 'Validation failed: match_id missing',
                    'validation' => 'match_id_required',
                    'level' => 'warning',
          ]));
          api_error('invalid_match', 400);
}

$match = get_match($matchId);
if (!$match) {
          log_api_error($buildLogContext([
                    'message' => 'Validation failed: match not found',
                    'level' => 'warning',
                    'reason' => 'match_not_found',
          ]));
          api_error('not_found', 404);
}

$user = current_user();
$roles = $_SESSION['roles'] ?? [];
$canView = can_view_match($user, $roles, (int)$match['club_id']);
if (!$canView) {
          log_api_error($buildLogContext([
                    'message' => 'Permission denied while creating annotation',
                    'level' => 'warning',
                    'reason' => 'permission_denied',
          ]));
          api_error('forbidden', 403);
}

if (!in_array($targetType, ['match_video', 'clip'], true)) {
          log_api_error($buildLogContext([
                    'message' => 'Validation failed: target_type invalid',
                    'validation' => 'target_type_invalid',
                    'level' => 'warning',
          ]));
          api_error('invalid_target', 400);
}

if ($targetId <= 0) {
          log_api_error($buildLogContext([
                    'message' => 'Validation failed: target_id missing',
                    'validation' => 'target_id_required',
                    'level' => 'warning',
          ]));
          api_error('invalid_target', 400);
}

if (!is_int($timestamp) || $timestamp < 0) {
          log_api_error($buildLogContext([
                    'message' => 'Validation failed: timestamp_second invalid',
                    'validation' => 'timestamp_invalid',
                    'level' => 'warning',
          ]));
          api_error('invalid_payload', 400, ['detail' => 'timestamp_second must be a positive integer']);
}

$drawingData = null;
if (is_string($rawDrawing)) {
          $decoded = json_decode($rawDrawing, true);
          if (is_array($decoded)) {
                    $drawingData = $decoded;
          }
} elseif (is_array($rawDrawing)) {
          $drawingData = $rawDrawing;
}

if (!is_array($drawingData)) {
          log_api_error($buildLogContext([
                    'message' => 'Validation failed: drawing_data missing or malformed',
                    'validation' => 'drawing_data_required',
                    'level' => 'warning',
          ]));
          api_error('invalid_payload', 400, ['detail' => 'drawing_data must be provided']);
}

$toolType = $drawingData['tool'] ?? null;
if (!is_string($toolType) || $toolType === '') {
          log_api_error($buildLogContext([
                    'message' => 'Validation failed: tool_type missing inside drawing_data',
                    'validation' => 'tool_type_required',
                    'level' => 'warning',
          ]));
          api_error('invalid_payload', 400, ['detail' => 'tool_type is required inside drawing_data']);
}

$drawingJsonCheck = json_encode($drawingData, JSON_UNESCAPED_UNICODE);
if ($drawingJsonCheck === false) {
          $jsonError = json_last_error_msg();
          log_api_error($buildLogContext([
                    'message' => 'Drawing data JSON encoding failed before persistence',
                    'json_error' => $jsonError,
                    'level' => 'warning',
          ]));
          api_error('invalid_payload', 400, ['detail' => 'drawing_data could not be encoded', 'json_error' => $jsonError]);
}

if (!annotation_target_exists($matchId, $targetType, $targetId)) {
          log_api_error($buildLogContext([
                    'message' => 'Validation failed: annotation target not part of match',
                    'reason' => 'target_mismatch',
                    'level' => 'warning',
          ]));
          api_error('target_not_found', 404);
}

try {
          require_match_lock($matchId, (int)$user['id']);
} catch (MatchLockException $e) {
          api_error('lock_required', 200);
}

try {
          $annotation = annotation_create($matchId, $targetType, $targetId, max(0, $timestamp), $drawingData, $notes);
          api_success(['annotation' => $annotation]);
} catch (\Throwable $e) {
          log_api_error($buildLogContext([
                    'message' => 'Annotation persistence failed',
                    'level' => 'error',
                    'exception' => $e,
          ]));
          $debugPayload = [];
          if (is_debug_enabled()) {
                    $debugPayload = [
                              'exception' => [
                                        'message' => $e->getMessage(),
                                        'file' => $e->getFile(),
                                        'line' => $e->getLine(),
                                        'trace' => explode("\n", $e->getTraceAsString()),
                              ],
                              'payload' => $sanitizedPayload,
                    ];
          }
          api_error('server_error', 500, [], $e, $debugPayload);
}

function sanitizeAnnotationPayload(array $payload): array
{
          $sanitized = [
                    'match_id' => isset($payload['match_id']) ? (int)$payload['match_id'] : null,
                    'target_type' => $payload['target_type'] ?? null,
                    'target_id' => isset($payload['target_id']) ? (int)$payload['target_id'] : null,
                    'timestamp_second' => isset($payload['timestamp_second']) ? (int)$payload['timestamp_second'] : null,
                    'notes' => isset($payload['notes']) ? trim((string)$payload['notes']) : null,
          ];

          $drawingData = $payload['drawing_data'] ?? null;
          if (is_array($drawingData)) {
                    $sanitized['drawing_data'] = [
                              'tool' => $drawingData['tool'] ?? null,
                              'points_count' => is_array($drawingData['points']) ? count($drawingData['points']) : null,
                    ];
                    if (!isset($sanitized['tool_type'])) {
                              $sanitized['tool_type'] = $drawingData['tool'] ?? null;
                    }
          } else {
                    $sanitized['drawing_data'] = $drawingData !== null ? '[non-array]' : null;
          }

          if (isset($payload['tool_type'])) {
                    $sanitized['tool_type'] = $payload['tool_type'];
          }

          return $sanitized;
}
