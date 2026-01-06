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

auth_boot();
require_auth();

header('Content-Type: application/json');

try {
          require_csrf_token();
} catch (CsrfException $e) {
          api_error('invalid_csrf', 403, [], $e);
}

$input = api_read_request_body();
$matchId = isset($matchId) ? (int)$matchId : (int)($input['match_id'] ?? 0);
if ($matchId <= 0) {
          api_error('invalid_match', 400);
}

$annotationId = isset($input['annotation_id']) ? (int)$input['annotation_id'] : 0;
if ($annotationId <= 0) {
          api_error('invalid_annotation', 400);
}

$match = get_match($matchId);
if (!$match) {
          api_error('not_found', 404);
}

$user = current_user();
$roles = $_SESSION['roles'] ?? [];
$canView = can_view_match($user, $roles, (int)$match['club_id']);
if (!$canView) {
          api_error('forbidden', 403);
}

try {
          require_match_lock($matchId, (int)$user['id']);
} catch (MatchLockException $e) {
          api_error('lock_required', 200);
}

$annotation = annotation_find($annotationId);
if (!$annotation || (int)$annotation['match_id'] !== $matchId) {
          api_error('not_found', 404);
}

$targetType = strtolower(trim((string)($input['target_type'] ?? $annotation['target_type'])));
$targetId = isset($input['target_id']) ? (int)$input['target_id'] : (int)$annotation['target_id'];
if ($targetType !== $annotation['target_type'] || $targetId !== (int)$annotation['target_id']) {
          api_error('target_mismatch', 400);
}

$timestamp = isset($input['timestamp_second']) ? max(0, (int)$input['timestamp_second']) : (int)($annotation['timestamp_second'] ?? 0);
$rawDrawing = $input['drawing_data'] ?? null;
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
          api_error('invalid_payload', 400);
}

$notes = array_key_exists('notes', $input)
          ? (is_string($input['notes']) ? trim((string)$input['notes']) : null)
          : $annotation['notes'] ?? null;

try {
          $updated = annotation_update($annotationId, $matchId, (int)$timestamp, $drawingData, $notes);
          api_success(['annotation' => $updated]);
} catch (\Throwable $e) {
          api_error('server_error', 500, [], $e);
}
