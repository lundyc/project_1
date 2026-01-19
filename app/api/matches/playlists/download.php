<?php

require_once __DIR__ . '/../../../lib/auth.php';
require_once __DIR__ . '/../../../lib/api_helpers.php';
require_once __DIR__ . '/../../../lib/match_permissions.php';
require_once __DIR__ . '/../../../lib/match_repository.php';
require_once __DIR__ . '/../../../lib/playlist_service.php';
require_once __DIR__ . '/../../../lib/playlist_repository.php';
require_once __DIR__ . '/../../../lib/event_repository.php';
require_once __DIR__ . '/common.php';

auth_boot();
require_auth();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
          api_respond_with_json(405, ['ok' => false, 'error' => 'method_not_allowed']);
}

$matchId = isset($matchId) ? (int)$matchId : (int)($_GET['match_id'] ?? 0);
if ($matchId <= 0) {
          api_respond_with_json(400, ['ok' => false, 'error' => 'match_id_required']);
}

playlist_api_require_view($matchId);

$playlistId = isset($_GET['playlist_id']) ? (int)$_GET['playlist_id'] : 0;
if ($playlistId <= 0) {
          api_respond_with_json(400, ['ok' => false, 'error' => 'playlist_id_required']);
}

$playlist = playlist_api_require_playlist($playlistId, $matchId);
$clips = playlist_get_clips($playlistId);
if (empty($clips)) {
          api_respond_with_json(422, ['ok' => false, 'error' => 'playlist_empty']);
}

$documentRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', DIRECTORY_SEPARATOR);
$videoRelative = '/videos/matches/match_' . $matchId . '/source/veo/standard/match_' . $matchId . '_standard.mp4';
$sourceVideo = $documentRoot . $videoRelative;
if (!is_file($sourceVideo)) {
          api_respond_with_json(404, ['ok' => false, 'error' => 'match_video_missing']);
}

set_time_limit(0);

$tempRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'playlist_downloads';
if (!is_dir($tempRoot)) {
          @mkdir($tempRoot, 0775, true);
}

try {
          $sessionDir = create_temp_playlist_dir($tempRoot, $playlistId);
          register_shutdown_function(static function () use ($sessionDir) {
                    remove_directory_recursive($sessionDir);
          });

          $generatedFiles = [];
          $generatedCount = 0;
          $skipped = [];
          foreach ($clips as $clip) {
                    $start = isset($clip['start_second']) ? (int)$clip['start_second'] : null;
                    $end = isset($clip['end_second']) ? (int)$clip['end_second'] : null;
                    $duration = isset($clip['duration_seconds']) ? (int)$clip['duration_seconds'] : null;
                    
                    // If duration is not set, calculate from end_second
                    if ($duration === null && $end !== null) {
                              $duration = $end - $start;
                    }
                    
                    // If no valid duration yet, try to calculate from event (for clips created with event_id as clip_id)
                    if (($duration === null || $duration <= 0) && isset($clip['event_id']) && $clip['event_id']) {
                              $event = event_get_by_id((int)$clip['event_id']);
                              if ($event) {
                                        $eventSecond = (int)($event['match_second'] ?? 0);
                                        $start = max(0, $eventSecond - 30);
                                        $end = $eventSecond + 30;
                                        $duration = $end - $start;
                              }
                    }
                    
                    if ($start === null || $start < 0) {
                              $skipped[] = ['reason' => 'missing_start', 'clip_id' => $clip['id'] ?? $clip['clip_id'] ?? '?'];
                              continue;
                    }
                    if ($duration === null || $duration <= 0) {
                              $skipped[] = ['reason' => 'missing_duration', 'clip_id' => $clip['id'] ?? $clip['clip_id'] ?? '?', 'duration' => $duration];
                              continue;
                    }
                    $generatedCount += 1;
                    $fileName = sprintf('%02d_%s.mp4', $generatedCount, slugify($clip['clip_name'] ?? 'clip'));
                    $targetPath = $sessionDir . DIRECTORY_SEPARATOR . $fileName;
                    create_clip_segment($sourceVideo, $targetPath, $start, $duration);
                    if (is_file($targetPath) && filesize($targetPath) > 0) {
                              $generatedFiles[] = $targetPath;
                    }
          }

          if (empty($generatedFiles)) {
                    throw new RuntimeException('clip_generation_failed: ' . json_encode(['skipped' => $skipped, 'clips_count' => count($clips)]));
          }

          $zipPath = $sessionDir . DIRECTORY_SEPARATOR . 'playlist.zip';
          $zip = new ZipArchive();
          if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
                    throw new RuntimeException('zip_creation_failed');
          }

          foreach ($generatedFiles as $filePath) {
                    $zip->addFile($filePath, basename($filePath));
          }

          $zip->close();

          $downloadName = slugify($playlist['title'] ?? 'playlist_' . $playlistId) . '.zip';
          if (!$downloadName) {
                    $downloadName = 'playlist.zip';
          }

          if (!is_file($zipPath)) {
                    throw new RuntimeException('archive_missing');
          }

          while (ob_get_level()) {
                    ob_end_clean();
          }

          header('Content-Type: application/zip');
          header('Content-Length: ' . (string)filesize($zipPath));
          header('Content-Disposition: attachment; filename="' . $downloadName . '"');
          header('Cache-Control: private, max-age=0');

          $zipStream = fopen($zipPath, 'rb');
          if ($zipStream) {
                    while (!feof($zipStream)) {
                              echo fread($zipStream, 8192);
                    }
                    fclose($zipStream);
          }

          exit;
} catch (\Throwable $ex) {
          $sessionDir = isset($sessionDir) ? $sessionDir : null;
          if ($sessionDir) {
                    remove_directory_recursive($sessionDir);
          }
          api_respond_with_json(500, [
                    'ok' => false,
                    'error' => 'playlist_download_failed',
                    'detail' => $ex->getMessage(),
          ]);
}

function slugify(string $text): string
{
          $slug = preg_replace('/[^A-Za-z0-9]+/', '_', $text);
          $slug = trim($slug ?? '', '_');
          return $slug !== '' ? $slug : 'playlist';
}

function create_temp_playlist_dir(string $base, int $playlistId): string
{
          $id = bin2hex(random_bytes(6));
          $dir = $base . DIRECTORY_SEPARATOR . 'playlist_' . $playlistId . '_' . $id;
          if (!is_dir($dir)) {
                    @mkdir($dir, 0775, true);
          }
          return $dir;
}

function remove_directory_recursive(string $dir): void
{
          if (!is_dir($dir)) {
                    return;
          }
          $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::CHILD_FIRST
          );
          foreach ($iterator as $item) {
                    if ($item->isDir()) {
                              @rmdir($item->getPathname());
                    } else {
                              @unlink($item->getPathname());
                    }
          }
          @rmdir($dir);
}

function create_clip_segment(string $source, string $target, int $start, int $duration): void
{
          $command = [
                    'ffmpeg',
                    '-hide_banner',
                    '-loglevel',
                    'error',
                    '-ss',
                    (string)$start,
                    '-i',
                    $source,
                    '-t',
                    (string)$duration,
                    '-c',
                    'copy',
                    '-y',
                    $target,
          ];

          $descriptors = [
                    0 => ['pipe', 'r'],
                    1 => ['pipe', 'w'],
                    2 => ['pipe', 'w'],
          ];

          $process = proc_open($command, $descriptors, $pipes);
          if (!is_resource($process)) {
                    throw new RuntimeException('clip_generation_process_failed');
          }

          fclose($pipes[0]);
          fclose($pipes[1]);
          $stderr = stream_get_contents($pipes[2]);
          fclose($pipes[2]);

          $exitCode = proc_close($process);
          if ($exitCode !== 0 || !is_file($target) || filesize($target) === 0) {
                    throw new RuntimeException('ffmpeg_failed: ' . trim($stderr));
          }
}
