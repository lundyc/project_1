<?php

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/api_helpers.php';
require_once __DIR__ . '/../../lib/match_permissions.php';
require_once __DIR__ . '/../../lib/match_repository.php';
require_once __DIR__ . '/../../lib/playlist_service.php';
require_once __DIR__ . '/playlists/common.php';

auth_boot();
require_auth();

$matchId = isset($matchId) ? (int)$matchId : (int)($_GET['match_id'] ?? 0);
if ($matchId <= 0) {
          api_respond_with_json(400, ['ok' => false, 'error' => 'match_id_required']);
}

$playlistId = isset($playlistId) ? (int)$playlistId : (int)($_GET['playlist_id'] ?? 0);
if ($playlistId <= 0) {
          api_respond_with_json(400, ['ok' => false, 'error' => 'playlist_id_required']);
}

playlist_api_require_view($matchId);

try {
          $result = playlist_service_get_with_clips($playlistId, $matchId);
} catch (RuntimeException $e) {
          if ($e->getMessage() === 'playlist_not_found') {
                    api_respond_with_json(404, ['ok' => false, 'error' => 'playlist_not_found']);
          }
          throw $e;
}

$playlist = $result['playlist'];
$clips = $result['clips'];

api_respond_with_json(200, [
          'ok' => true,
          'playlist' => $playlist,
          'clips' => $clips,
]);
