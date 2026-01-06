<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../lib/api_response.php';
require_once __DIR__ . '/../../../lib/playlist_player_state.php';
require_once __DIR__ . '/helpers.php';

$playlistId = playlist_admin_validate_playlist_id();
$state = playlist_player_state_read($playlistId);

api_success([
          'state' => $state,
]);
