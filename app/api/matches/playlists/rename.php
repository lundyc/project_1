<?php

require_once __DIR__ . '/../../../lib/auth.php';
require_once __DIR__ . '/../../../lib/api_helpers.php';
require_once __DIR__ . '/../../../lib/match_permissions.php';
require_once __DIR__ . '/../../../lib/match_repository.php';
require_once __DIR__ . '/../../../lib/playlist_service.php';
require_once __DIR__ . '/../../../lib/audit_service.php';
require_once __DIR__ . '/common.php';

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
$title = trim((string)($input['title'] ?? ''));

if ($playlistId <= 0 || $title === '') {
          api_respond_with_json(422, ['ok' => false, 'error' => 'playlist_id_and_title_required']);
}

$playlist = playlist_api_require_playlist($playlistId, $matchId);
$before = $playlist;

$updated = null;
try {
          $updated = playlist_service_update_playlist($playlistId, $matchId, ['title' => $title]);
} catch (InvalidArgumentException $e) {
          api_respond_with_json(422, ['ok' => false, 'error' => $e->getMessage()]);
}

audit(
          (int)$match['club_id'],
          (int)$user['id'],
          'playlist',
          $playlistId,
          'rename',
          json_encode($before),
          json_encode($updated)
);

api_respond_with_json(200, ['ok' => true, 'playlist' => $updated]);
