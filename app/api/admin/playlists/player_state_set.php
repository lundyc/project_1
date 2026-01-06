<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../lib/api_response.php';
require_once __DIR__ . '/../../../lib/api_helpers.php';
require_once __DIR__ . '/../../../lib/playlist_player_state.php';
require_once __DIR__ . '/helpers.php';

$playlistId = playlist_admin_validate_playlist_id();
$payload = api_read_request_body();

$allowed = ['mode', 'current_clip_id', 'current_time', 'autoplay_next', 'loop_clip'];
$filtered = array_intersect_key($payload, array_flip($allowed));

$state = playlist_player_state_write($playlistId, $filtered);

api_success([
          'state' => $state,
]);
