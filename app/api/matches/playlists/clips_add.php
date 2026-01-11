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
$clipId = isset($input['clip_id']) ? (int)$input['clip_id'] : 0;
$sortOrder = isset($input['sort_order']) ? (int)$input['sort_order'] : null;

if ($playlistId <= 0 || $clipId <= 0) {
          api_respond_with_json(422, ['ok' => false, 'error' => 'playlist_and_clip_required']);
}

playlist_api_require_playlist($playlistId, $matchId);

try {
          $insertedClip = playlist_service_add_clip($playlistId, $matchId, $clipId, $sortOrder);
} catch (RuntimeException $e) {
          $message = $e->getMessage();
          if ($message === 'duplicate_clip') {
                    api_respond_with_json(409, ['ok' => false, 'error' => 'clip_already_in_playlist']);
          }
          if ($message === 'clip_not_found' || $message === 'playlist_not_found') {
                    api_respond_with_json(404, ['ok' => false, 'error' => $message]);
          }
          throw $e;
}

$auditPayload = [
          'playlist_id' => $playlistId,
          'clip_id' => $clipId,
          'sort_order' => $insertedClip['sort_order'] ?? null,
];

audit(
          (int)$match['club_id'],
          (int)$user['id'],
          'playlist_clip',
          $playlistId,
          'add',
          null,
          json_encode($auditPayload)
);

api_respond_with_json(200, ['ok' => true, 'clip' => $insertedClip]);
