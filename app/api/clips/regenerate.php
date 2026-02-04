<?php

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/csrf.php';
require_once __DIR__ . '/../../lib/api_helpers.php';
require_once __DIR__ . '/../../lib/match_repository.php';
require_once __DIR__ . '/../../lib/match_permissions.php';
require_once __DIR__ . '/../../lib/event_repository.php';
require_once __DIR__ . '/../../lib/clip_repository.php';
require_once __DIR__ . '/../../lib/playlist_service.php';
require_once __DIR__ . '/../../lib/clip_mp4_service.php';
require_once __DIR__ . '/../../lib/audit_service.php';
require_once __DIR__ . '/../../lib/match_version_service.php';

auth_boot();
require_auth();

// Validate CSRF token for state-changing operation
try {
    require_csrf_token();
} catch (CsrfException $e) {
    http_response_code(403);
    die('Invalid CSRF token');
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
          api_respond_with_json(405, ['ok' => false, 'error' => 'method_not_allowed']);
}

$clipId = isset($clipId) ? (int)$clipId : (int)($_GET['id'] ?? 0);
if ($clipId <= 0) {
          api_respond_with_json(400, ['ok' => false, 'error' => 'clip_id_required']);
}

$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM clips WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $clipId]);
$clip = $stmt->fetch();
if (!$clip) {
          api_respond_with_json(404, ['ok' => false, 'error' => 'clip_not_found']);
}

$match = get_match((int)$clip['match_id']);
if (!$match) {
          api_respond_with_json(404, ['ok' => false, 'error' => 'match_not_found']);
}

$user = current_user();
$roles = $_SESSION['roles'] ?? [];
$canEdit = in_array('platform_admin', $roles, true) || in_array('club_admin', $roles, true) || in_array('analyst', $roles, true);
$canManage = $canEdit && can_manage_match_for_club($user, $roles, (int)$match['club_id']);

if (!$canManage) {
          api_respond_with_json(403, ['ok' => false, 'error' => 'forbidden']);
}

if (!is_legacy_auto_clip($clip)) {
          api_respond_with_json(422, ['ok' => false, 'error' => 'clip_not_legacy']);
}

$eventId = isset($clip['event_id']) ? (int)$clip['event_id'] : 0;
if ($eventId <= 0) {
          api_respond_with_json(422, ['ok' => false, 'error' => 'clip_missing_event']);
}

$event = event_get_by_id($eventId);
if (!$event || (int)$event['match_id'] !== (int)$match['id']) {
          api_respond_with_json(404, ['ok' => false, 'error' => 'event_not_found']);
}

$newClipName = generate_clip_name_from_event($event, (int)$match['id']);

          $existingPath = clip_mp4_service_get_clip_filesystem_path($clip);
          if ($existingPath && is_file($existingPath)) {
                    @unlink($existingPath);
          }
          clip_file_helper_forget_clip_path($clipId);

try {
          $updateStmt = $pdo->prepare(
                    'UPDATE clips
             SET clip_name = :clip_name,
                 generation_source = :generation_source,
                 generation_version = :generation_version,
                 updated_by = :updated_by,
                 updated_at = NOW()
             WHERE id = :id'
          );
          $updateStmt->execute([
                    'clip_name' => $newClipName,
                    'generation_source' => 'manual_regenerate',
                    'generation_version' => 4,
                    'updated_by' => (int)($user['id'] ?? 0),
                    'id' => $clipId,
          ]);

          $afterStmt = $pdo->prepare('SELECT * FROM clips WHERE id = :id LIMIT 1');
          $afterStmt->execute(['id' => $clipId]);
          $updatedClip = $afterStmt->fetch();
          if (!$updatedClip) {
                    throw new RuntimeException('clip_missing_after_update');
          }

          if (isset($updatedClip['start_second'])) {
                    $updatedClip['start_second'] = (int)$updatedClip['start_second'];
          }
          if (!isset($updatedClip['duration_seconds']) || $updatedClip['duration_seconds'] === null) {
                    if (isset($updatedClip['end_second']) && isset($updatedClip['start_second'])) {
                              $updatedClip['duration_seconds'] = (int)$updatedClip['end_second'] - (int)$updatedClip['start_second'];
                    }
          } else {
                    $updatedClip['duration_seconds'] = (int)$updatedClip['duration_seconds'];
          }

          $updatedClip['is_legacy_auto_clip'] = is_legacy_auto_clip($updatedClip);

          ensure_clip_mp4_exists($updatedClip);

          $version = bump_clips_version((int)$match['id']);

          $oldClipJson = json_encode($clip);
          $newClipJson = json_encode($updatedClip);
          audit(
                    (int)$match['club_id'],
                    (int)($user['id'] ?? 0),
                    'clip',
                    $clipId,
                    'regenerate',
                    $oldClipJson,
                    $newClipJson
          );

          api_respond_with_json(200, [
                    'ok' => true,
                    'clip' => $updatedClip,
                    'meta' => ['clips_version' => $version],
          ]);
} catch (\Throwable $e) {
          error_log('Clip regeneration failed: ' . $e->getMessage());
          api_respond_with_json(500, ['ok' => false, 'error' => 'clip_regeneration_failed', 'detail' => $e->getMessage()]);
}
