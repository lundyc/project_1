<?php

require_once __DIR__ . '/../middleware/require_admin.php';

route('/admin/players', function () {
          require_admin();

          $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
          if ($method === 'GET') {
                    require __DIR__ . '/../views/pages/admin/players/list.php';
                    return;
          }

          if ($method === 'POST') {
                    require __DIR__ . '/../api/admin/players/create.php';
                    return;
          }

          http_response_code(405);
          echo '405 Method Not Allowed';
});

route('/admin/players/create', function () {
          require_admin();

          if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
                    http_response_code(405);
                    echo '405 Method Not Allowed';
                    return;
          }

          require __DIR__ . '/../views/pages/admin/players/form.php';
});

route('/admin/players/{id}', function () {
          require_admin();

          $playerId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
          if ($playerId <= 0) {
                    http_response_code(404);
                    echo '404 Not Found';
                    return;
          }

          $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
          if ($method === 'GET') {
                    require __DIR__ . '/../views/pages/admin/players/profile.php';
                    return;
          }

          if ($method === 'POST') {
                    require __DIR__ . '/../api/admin/players/update.php';
                    return;
          }

          http_response_code(405);
          echo '405 Method Not Allowed';
});

route('/admin/players/{id}/edit', function () {
          require_admin();

          if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
                    http_response_code(405);
                    echo '405 Method Not Allowed';
                    return;
          }

          require __DIR__ . '/../views/pages/admin/players/form.php';
});

route('/admin/players/{id}/delete', function () {
          require_admin();

          $playerId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

          if ($playerId <= 0) {
                    http_response_code(404);
                    echo '404 Not Found';
                    return;
          }

          if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
                    http_response_code(405);
                    echo '405 Method Not Allowed';
                    return;
          }

          require __DIR__ . '/../api/admin/players/delete.php';
});
