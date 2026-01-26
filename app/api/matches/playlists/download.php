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

require_once __DIR__ . '/../../../lib/clip_mp4_service.php';

// Check each clip has an mp4
$missingMp4s = [];
$clipEntries = [];
foreach ($clips as $clip) {
          $clipId = $clip['id'] ?? $clip['clip_id'] ?? null;
          if (!$clipId) {
                    continue;
          }
          $mp4Path = clip_mp4_service_get_clip_filesystem_path($clip);
          if (!$mp4Path || !is_file($mp4Path) || filesize($mp4Path) === 0) {
                    $missingMp4s[] = $clipId;
                    continue;
          }
          $clipEntries[] = [
                    'clip' => $clip,
                    'path' => $mp4Path,
          ];
}
if (!empty($missingMp4s)) {
    api_respond_with_json(404, ['ok' => false, 'error' => 'clip_mp4_missing', 'missing_clips' => $missingMp4s]);
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
          $fileNameRegistry = [];
          $totalClips = count($clipEntries);
          $padWidth = max(2, strlen((string)$totalClips));

          foreach ($clipEntries as $entryIndex => $entry) {
                    $clipRow = $entry['clip'];
                    $isLegacy = is_legacy_auto_clip($clipRow);
                    if ($isLegacy) {
                              $eventName = null;
                              if (!empty($clipRow['event_id'])) {
                                        $eventData = event_get_by_id((int)$clipRow['event_id']);
                                        if ($eventData) {
                                                  $eventName = generate_clip_name_from_event($eventData, $matchId);
                                        }
                              }
                              $baseName = $eventName !== null
                                        ? $eventName
                                        : playlist_service_slugify_filename($clipRow['clip_name'] ?? 'clip', 'clip', ['_', '(', ')', '+']);
                    } else {
                              $baseName = playlist_service_slugify_filename($clipRow['clip_name'] ?? 'clip', 'clip', ['_', '(', ')', '+']);
                    }
                    $uniqueBase = playlist_service_make_unique_clip_name($baseName, $fileNameRegistry);
                    $generatedCount++;
                    $fileName = sprintf('%0' . $padWidth . 'd_%s.mp4', $generatedCount, $uniqueBase);
                    $targetPath = $sessionDir . DIRECTORY_SEPARATOR . $fileName;
                    copy($entry['path'], $targetPath);
                    if (is_file($targetPath) && filesize($targetPath) > 0) {
                              $generatedFiles[] = $targetPath;
                    }
          }

          if (empty($generatedFiles)) {
                    throw new RuntimeException('no_clips_to_zip');
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

          $match = get_match($matchId);
          $zipBase = playlist_service_build_playlist_zip_filename($playlist, $match);
          $downloadName = $zipBase . '.zip';
          if (trim($downloadName) === '.zip') {
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
