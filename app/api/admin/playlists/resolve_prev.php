<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../lib/api_response.php';
require_once __DIR__ . '/../../../lib/playlist_playback_service.php';
require_once __DIR__ . '/helpers.php';

$playlistId = playlist_admin_validate_playlist_id();
$currentClipId = isset($_GET['current_clip_id']) ? (int)$_GET['current_clip_id'] : null;

try {
          $payload = playlist_playback_build_queue($playlistId);
} catch (\RuntimeException $e) {
          playlist_admin_handle_runtime_exception($e);
} catch (\Throwable $e) {
          api_error('playlist_prev_failed', 500, [], $e);
}

$clip = playlist_playback_previous_clip($payload['queue'], $currentClipId);

api_success([
          'requested_clip_id' => $currentClipId,
          'queue_mode' => $payload['mode'],
          'full_match_video_url' => $payload['full_match_video_url'],
          'clip' => $clip,
]);
