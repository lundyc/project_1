<?php

require_once __DIR__ . '/../middleware/require_admin.php';

function playlist_admin_dispatch_api(string $file): void
{
          $playlistId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
          $_GET['playlist_id'] = $playlistId;
          require __DIR__ . '/../api/admin/playlists/' . $file;
}

route('/admin/playlists/{id}/queue', function () {
          require_admin();
          if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
                    http_response_code(405);
                    echo '405 Method Not Allowed';
                    return;
          }
          playlist_admin_dispatch_api('queue.php');
});

route('/admin/playlists/{id}/resolve-next', function () {
          require_admin();
          if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
                    http_response_code(405);
                    echo '405 Method Not Allowed';
                    return;
          }
          playlist_admin_dispatch_api('resolve_next.php');
});

route('/admin/playlists/{id}/resolve-prev', function () {
          require_admin();
          if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
                    http_response_code(405);
                    echo '405 Method Not Allowed';
                    return;
          }
          playlist_admin_dispatch_api('resolve_prev.php');
});

route('/admin/playlists/{id}/player-state', function () {
          require_admin();
          $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
          if ($method === 'GET') {
                    playlist_admin_dispatch_api('player_state_get.php');
                    return;
          }
          if ($method === 'POST') {
                    playlist_admin_dispatch_api('player_state_set.php');
                    return;
          }
          http_response_code(405);
          echo '405 Method Not Allowed';
});
