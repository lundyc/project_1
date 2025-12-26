<?php

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/video_repository.php';

function admin_videos_controller_index(): void
{
          $access = require_club_admin_access();
          $clubId = $access['club_id'] ?? null;

          $videos = get_all_videos($clubId);
          $processingQueue = [];
          $failedVideos = [];

          foreach ($videos as $video) {
                    $status = strtolower((string)($video['download_status'] ?? 'pending'));
                    if (in_array($status, ['pending', 'downloading'], true)) {
                              $progress = read_video_progress((int)$video['match_id']);
                              $processingQueue[] = [
                                        'id' => (int)$video['id'],
                                        'match_id' => (int)$video['match_id'],
                                        'match_label' => trim(($video['home_team'] ?? '') . ' vs ' . ($video['away_team'] ?? '')),
                                        'status' => $status,
                                        'progress' => $progress['data']['percent'] ?? 0,
                                        'updated_at' => $progress['data']['last_seen_at'] ?? $video['created_at'],
                              ];
                    }

                    if ($status === 'failed') {
                              $failedVideos[] = $video;
                    }
          }

          $title = 'Videos';
          require __DIR__ . '/../../views/pages/admin/videos/list.php';
}

function admin_videos_controller_profile(int $videoId): void
{
          $access = require_club_admin_access();
          $clubId = $access['club_id'] ?? null;

          $video = get_video($videoId);
          if (!$video || ($clubId > 0 && (int)$video['club_id'] !== (int)$clubId)) {
                    http_response_code(404);
                    echo '404 Not Found';
                    return;
          }

          $fileMetadata = get_video_files((int)$video['match_id'], $video['source_path']);
          $progress = read_video_progress((int)$video['match_id']);

          $title = 'Video ' . ($video['match_id'] ?? '');
          require __DIR__ . '/../../views/pages/admin/videos/profile.php';
}
