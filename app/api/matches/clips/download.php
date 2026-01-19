<?php

require_once __DIR__ . '/../../../lib/auth.php';
require_once __DIR__ . '/../../../lib/api_helpers.php';
require_once __DIR__ . '/../../../lib/match_permissions.php';
require_once __DIR__ . '/../../../lib/match_repository.php';
require_once __DIR__ . '/../../../lib/clip_repository.php';

auth_boot();
require_auth();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
          api_respond_with_json(405, ['ok' => false, 'error' => 'method_not_allowed']);
}

$matchId = isset($matchId) ? (int)$matchId : (int)($_GET['match_id'] ?? 0);
if ($matchId <= 0) {
          api_respond_with_json(400, ['ok' => false, 'error' => 'match_id_required']);
}

playlist_api_require_view($matchId);

$clipId = isset($clipId) ? (int)$clipId : (int)($_GET['clip_id'] ?? 0);
if ($clipId <= 0) {
          api_respond_with_json(400, ['ok' => false, 'error' => 'clip_id_required']);
}

$clip = playlist_get_clip_for_match($clipId, $matchId);
if (!$clip) {
          api_respond_with_json(404, ['ok' => false, 'error' => 'clip_not_found']);
}

$documentRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', DIRECTORY_SEPARATOR);
$videoRelative = '/videos/matches/match_' . $matchId . '/source/veo/standard/match_' . $matchId . '_standard.mp4';
$sourceVideo = $documentRoot . $videoRelative;
if (!is_file($sourceVideo)) {
          api_respond_with_json(404, ['ok' => false, 'error' => 'match_video_missing']);
}

set_time_limit(0);

$tempRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'clip_downloads';
if (!is_dir($tempRoot)) {
          @mkdir($tempRoot, 0775, true);
}

try {
          $sessionDir = create_temp_playlist_dir($tempRoot, $clipId);
          register_shutdown_function(static function () use ($sessionDir) {
                    remove_directory_recursive($sessionDir);
          });

          // Calculate clip boundaries
          $start = isset($clip['start_second']) ? (int)$clip['start_second'] : null;
          $end = isset($clip['end_second']) ? (int)$clip['end_second'] : null;
          $duration = isset($clip['duration_seconds']) ? (int)$clip['duration_seconds'] : null;
          
          // If duration is not set, calculate from end_second
          if ($duration === null && $end !== null) {
                    $duration = $end - $start;
          }
          
          // If no valid duration yet, try to calculate from event
          if (($duration === null || $duration <= 0) && isset($clip['event_id']) && $clip['event_id']) {
                    require_once __DIR__ . '/../../../lib/event_repository.php';
                    $event = event_get_by_id((int)$clip['event_id']);
                    if ($event) {
                              $eventSecond = (int)($event['match_second'] ?? 0);
                              $start = max(0, $eventSecond - 30);
                              $end = $eventSecond + 30;
                              $duration = $end - $start;
                    }
          }
          
          if ($start === null || $start < 0) {
                    api_respond_with_json(422, ['ok' => false, 'error' => 'clip_has_invalid_start']);
          }
          
          if ($duration === null || $duration <= 0) {
                    api_respond_with_json(422, ['ok' => false, 'error' => 'clip_has_invalid_duration']);
          }
          
          // Generate filename
          $clipName = $clip['clip_name'] ?? 'clip';
          $fileName = slugify($clipName) . '.mp4';
          $targetPath = $sessionDir . DIRECTORY_SEPARATOR . $fileName;
          
          create_clip_segment($sourceVideo, $targetPath, $start, $duration);
          
          if (!is_file($targetPath) || filesize($targetPath) <= 0) {
                    throw new RuntimeException('clip_generation_failed');
          }
          
          // Stream the file to the client
          header('Content-Type: video/mp4');
          header('Content-Length: ' . filesize($targetPath));
          header('Content-Disposition: attachment; filename="' . basename($fileName) . '"');
          header('Cache-Control: no-cache, no-store, must-revalidate');
          header('Pragma: no-cache');
          header('Expires: 0');
          
          readfile($targetPath);
          exit;
          
} catch (\Throwable $e) {
          error_log('Clip download error: ' . $e->getMessage());
          api_respond_with_json(500, [
                    'ok' => false,
                    'error' => 'clip_download_failed',
                    'detail' => $e->getMessage()
          ]);
}
