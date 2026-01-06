<?php

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/api_helpers.php';
require_once __DIR__ . '/../../lib/match_permissions.php';
require_once __DIR__ . '/../../lib/match_repository.php';
require_once __DIR__ . '/../../lib/playlist_service.php';
require_once __DIR__ . '/../../lib/audit_service.php';
require_once __DIR__ . '/playlists/common.php';

auth_boot();
require_auth();

$matchId = isset($matchId) ? (int)$matchId : (int)($_GET['match_id'] ?? 0);
if ($matchId <= 0) {
          api_respond_with_json(400, ['ok' => false, 'error' => 'match_id_required']);
}

$context = playlist_api_require_manage($matchId);
$match = $context['match'];
$user = $context['user'];

$input = api_read_request_body();
$playlistId = isset($input['playlist_id']) ? (int)$input['playlist_id'] : 0;
$clipId = isset($input['clip_id']) ? (int)$input['clip_id'] : 0;

if ($playlistId <= 0 || $clipId <= 0) {
          api_respond_with_json(422, ['ok' => false, 'error' => 'playlist_and_clip_required']);
}

$playlist = playlist_api_require_playlist($playlistId, $matchId);
$clipEntry = null;
try {
          $clipEntry = playlist_service_remove_clip($playlistId, $matchId, $clipId);
} catch (RuntimeException $e) {
          if ($e->getMessage() === 'clip_not_in_playlist') {
                    api_respond_with_json(404, ['ok' => false, 'error' => 'clip_not_in_playlist']);
          }
          throw $e;
}

audit(
          (int)$match['club_id'],
          (int)$user['id'],
          'playlist_clip',
          $playlistId,
          'remove',
          json_encode($clipEntry),
          null
);

api_respond_with_json(200, ['ok' => true]);
