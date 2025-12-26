<?php

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/match_repository.php';
require_once __DIR__ . '/../lib/video_repository.php';

function dashboard_readable_bytes(int $bytes): string
{
          if ($bytes <= 0) {
                    return '0 B';
          }

          $units = ['B', 'KB', 'MB', 'GB', 'TB'];
          $i = 0;
          while ($bytes >= 1024 && $i < count($units) - 1) {
                    $bytes /= 1024;
                    $i++;
          }

          return round($bytes, $i === 0 ? 0 : 2) . ' ' . $units[$i];
}

function dashboard_controller_show(): void
{
          $user = current_user() ?? [];
          $clubId = !empty($user['club_id']) ? (int)$user['club_id'] : null;

          $totalMatches = count_matches($clubId);
          $matchesWithVideos = count_matches_with_videos($clubId);
          $statusCounts = count_video_statuses($clubId);
          $processingCount = $statusCounts['downloading'] ?? 0;
          $failedCount = $statusCounts['failed'] ?? 0;

          $matchIdsWithVideos = get_video_match_ids($clubId);
          $storageBytes = sum_video_storage_for_matches($matchIdsWithVideos);

          $recentMatches = get_recent_matches($clubId, 5);
          $recentVideos = get_recent_video_ingestions($clubId, 8);

          $activeDownloads = [];
          foreach ($recentVideos as $video) {
                    $status = strtolower((string)($video['download_status'] ?? 'pending'));
                    if (!in_array($status, ['downloading', 'pending', 'failed'], true)) {
                              continue;
                    }

                    $progress = read_video_progress((int)$video['match_id']);
                    $percent = $progress['data']['percent'] ?? null;
                    $lastSeen = $progress['data']['last_seen_at'] ?? $video['created_at'];

                    $activeDownloads[] = [
                              'match_id' => (int)$video['match_id'],
                              'status' => $status,
                              'progress' => $percent !== null ? min(100, max(0, (int)$percent)) : 0,
                              'message' => $progress['data']['message'] ?? ($video['error_message'] ?? null),
                              'last_seen' => $lastSeen,
                              'match_label' => trim(($video['home_team'] ?? '') . ' vs ' . ($video['away_team'] ?? '')),
                              'source' => $video['source_type'] ?? 'upload',
                    ];
          }

          $projectRoot = video_repository_project_root();
          $runtimeLog = $projectRoot ? $projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'veo_downloader_runtime.log' : null;
          $workerStatus = 'Offline';
          $lastHeartbeat = null;
          if ($runtimeLog && is_file($runtimeLog)) {
                    $timestamp = filemtime($runtimeLog);
                    if ($timestamp !== false) {
                              $delta = time() - $timestamp;
                              $workerStatus = $delta <= 600 ? 'Online' : 'Idle';
                              $lastHeartbeat = date('M j, Y H:i', $timestamp);
                    }
          }

          $systemHealth = [
                    'pythonWorker' => [
                              'status' => $workerStatus,
                              'last_seen' => $lastHeartbeat ?? 'Unknown',
                    ],
                    'diskUsage' => [
                              'label' => 'Video storage',
                              'value' => dashboard_readable_bytes($storageBytes),
                    ],
                    'lastIngest' => [
                              'label' => 'Last ingest',
                              'value' => !empty($recentVideos[0]['created_at']) ? date('M j, Y H:i', strtotime($recentVideos[0]['created_at'])) : 'N/A',
                    ],
          ];

          $dashboardData = [
                    'totalMatches' => $totalMatches,
                    'matchesWithVideos' => $matchesWithVideos,
                    'processingCount' => $processingCount,
                    'failedCount' => $failedCount,
                    'videosStored' => array_sum($statusCounts),
                    'analysisReady' => $statusCounts['completed'] ?? 0,
                    'storageBytes' => $storageBytes,
                    'storageLabel' => dashboard_readable_bytes($storageBytes),
                    'recentMatches' => $recentMatches,
                    'recentVideos' => $recentVideos,
                    'activeDownloads' => $activeDownloads,
                    'systemHealth' => $systemHealth,
                    'activeClub' => $user['club_name'] ?? ($user['club_id'] ? 'Club #' . $user['club_id'] : 'Club Portal'),
                    'videoStatusCounts' => $statusCounts,
                    'processingQueueCount' => count($activeDownloads),
                    'storageUsageLabel' => dashboard_readable_bytes($storageBytes),
          ];

          $title = 'Dashboard';
          require __DIR__ . '/../views/pages/dashboard.php';
}
