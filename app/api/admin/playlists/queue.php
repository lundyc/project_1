<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../lib/api_response.php';
require_once __DIR__ . '/../../../lib/playlist_playback_service.php';
require_once __DIR__ . '/helpers.php';

$playlistId = playlist_admin_validate_playlist_id();
$mode = $_GET['mode'] ?? null;

try {
          $payload = playlist_playback_build_queue($playlistId, $mode);
} catch (\RuntimeException $e) {
          playlist_admin_handle_runtime_exception($e);
} catch (\Throwable $e) {
          api_error('playlist_queue_failed', 500, [], $e);
}

$playlist = $payload['playlist'];
$match = $payload['match'];

api_success([
          'playlist' => [
                    'id' => isset($playlist['id']) ? (int)$playlist['id'] : $playlistId,
                    'match_id' => isset($playlist['match_id']) ? (int)$playlist['match_id'] : null,
                    'title' => $playlist['title'] ?? null,
                    'notes' => $playlist['notes'] ?? null,
          ],
          'match' => [
                    'id' => isset($match['id']) ? (int)$match['id'] : null,
                    'club_id' => isset($match['club_id']) ? (int)$match['club_id'] : null,
                    'video_source_path' => $match['video_source_path'] ?? null,
          ],
          'mode' => $payload['mode'],
          'full_match_video_url' => $payload['full_match_video_url'],
          'queue' => $payload['queue'],
          'queue_length' => count($payload['queue']),
]);
