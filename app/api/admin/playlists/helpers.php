<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../lib/api_response.php';

function playlist_admin_validate_playlist_id(): int
{
          $playlistId = isset($_GET['playlist_id']) ? (int)$_GET['playlist_id'] : 0;
          if ($playlistId <= 0) {
                    api_error('playlist_id_required', 400);
          }
          return $playlistId;
}

function playlist_admin_handle_runtime_exception(\RuntimeException $exception): void
{
          $code = $exception->getMessage();
          switch ($code) {
                    case 'playlist_not_found':
                    case 'match_not_found':
                              api_error($code, 404);
                              break;
                    case 'match_video_missing':
                              api_error($code, 409);
                              break;
                    case 'playlist_invalid_match':
                              api_error($code, 400);
                              break;
                    default:
                              api_error('playlist_service_error', 400);
          }
}
