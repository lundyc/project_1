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
$title = trim((string)($input['title'] ?? ''));
$notesRaw = isset($input['notes']) ? trim((string)$input['notes']) : null;
$notes = $notesRaw === '' ? null : $notesRaw;

$playlist = null;
try {
          $playlist = playlist_service_create_playlist((int)$matchId, (int)$user['id'], $title, $notes);
} catch (InvalidArgumentException $e) {
          api_respond_with_json(422, ['ok' => false, 'error' => $e->getMessage()]);
}

audit(
          (int)$match['club_id'],
          (int)$user['id'],
          'playlist',
          (int)$playlist['id'],
          'create',
          null,
          json_encode($playlist)
);

api_respond_with_json(201, ['ok' => true, 'playlist' => $playlist]);
