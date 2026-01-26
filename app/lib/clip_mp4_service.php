<?php
// This script generates an mp4 for a given clip if it does not exist.
require_once __DIR__ . '/../lib/clip_repository.php';
require_once __DIR__ . '/../lib/video_repository.php';
require_once __DIR__ . '/clip_file_helper.php';

function ensure_clip_mp4_exists($clip) {
    $matchId = $clip['match_id'];
    $clipId = isset($clip['id']) ? (int)$clip['id'] : null;
    $start = isset($clip['start_second']) ? (int)$clip['start_second'] : 0;
    $duration = isset($clip['duration_seconds']) ? (int)$clip['duration_seconds'] : 0;
    $clipName = $clip['clip_name'] ?? 'clip';
    $documentRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', DIRECTORY_SEPARATOR);
    $sourceVideo = $documentRoot . "/videos/matches/match_{$matchId}_standard.mp4";
    $clipsDir = $documentRoot . "/videos/clips";
    if (!is_file($sourceVideo)) {
        log_clip_mp4_error($matchId, $clipId ?? 'new', "Source video missing: $sourceVideo");
        throw new Exception('match_video_missing');
    }
    if (!is_dir($clipsDir)) {
        @mkdir($clipsDir, 0775, true);
    }
    $canonicalPath = $clipId ? $clipsDir . "/match_{$matchId}_{$clipId}.mp4" : null;
    $primaryPath = clip_mp4_service_resolve_primary_clip_path($clip, $clipsDir, $clipName, $canonicalPath);

    if (is_file($primaryPath) && filesize($primaryPath) > 0) {
        clip_mp4_service_register_clip_path($clipId, $primaryPath);
        return $primaryPath;
    }

    $cmd = sprintf(
        'ffmpeg -hide_banner -loglevel error -ss %d -i %s -t %d -c copy -y %s 2>&1',
        $start,
        escapeshellarg($sourceVideo),
        $duration,
        escapeshellarg($primaryPath)
    );
    $output = [];
    $exitCode = 0;
    exec($cmd, $output, $exitCode);
    $logMsg = "[" . date('Y-m-d H:i:s') . "] ffmpeg cmd: $cmd\nOutput: " . implode("\n", $output) . "\nExit code: $exitCode\n";
    log_clip_mp4_error($matchId, $clipId ?? 'new', $logMsg);

    if ($exitCode !== 0 || !is_file($primaryPath) || filesize($primaryPath) === 0) {
        log_clip_mp4_error($matchId, $clipId ?? 'new', "Clip generation failed for $primaryPath");
        throw new Exception('clip_generation_failed');
    }

    clip_mp4_service_register_clip_path($clipId, $primaryPath);

    return $primaryPath;

}

function log_clip_mp4_error($matchId, $clipId, $msg) {
    $documentRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', DIRECTORY_SEPARATOR);
    $logDir = $documentRoot . '/storage/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0775, true);
    }
    $logFile = $logDir . "/clip_mp4_{$matchId}_{$clipId}.log";
    file_put_contents($logFile, $msg . "\n", FILE_APPEND);
}

function clip_mp4_service_resolve_primary_clip_path(array $clip, string $clipsDir, string $clipName, ?string $canonicalPath): string {
    $providedPath = clip_mp4_service_existing_clip_path($canonicalPath, $clip);
    $baseName = clip_mp4_service_build_clip_basename($clipName);
    if ($providedPath && is_file($providedPath)) {
        $currentBase = pathinfo($providedPath, PATHINFO_FILENAME);
        if ($currentBase !== $baseName) {
            $baseName = clip_mp4_service_find_unique_basename($baseName, $clipsDir, $providedPath);
            $newPath = $clipsDir . DIRECTORY_SEPARATOR . $baseName . '.mp4';
            if (strcasecmp($providedPath, $newPath) !== 0) {
                @rename($providedPath, $newPath);
            }
            return $newPath;
        }
        return $providedPath;
    }

    $uniqueBase = clip_mp4_service_find_unique_basename($baseName, $clipsDir);
    return $clipsDir . DIRECTORY_SEPARATOR . $uniqueBase . '.mp4';
}

function clip_mp4_service_existing_clip_path(?string $canonicalPath, array $clip): ?string {
    $providedPath = isset($clip['mp4_file_path']) ? $clip['mp4_file_path'] : null;
    if ($providedPath && is_file($providedPath)) {
        $resolved = realpath($providedPath);
        if ($resolved) {
            return $resolved;
        }
    }

    $clipId = isset($clip['id']) ? (int)$clip['id'] : (isset($clip['clip_id']) ? (int)$clip['clip_id'] : 0);
    if ($clipId > 0) {
        $registered = clip_file_helper_get_registered_clip_path($clipId);
        if ($registered) {
            return $registered;
        }
    }

    if ($canonicalPath && is_file($canonicalPath)) {
        $resolved = realpath($canonicalPath);
        if ($resolved) {
            return $resolved;
        }
    }
    return null;
}

function clip_mp4_service_build_clip_basename(string $clipName): string {
    $text = trim($clipName);
    if ($text === '') {
        return 'clip';
    }

    $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT', $text);
    if ($transliterated !== false) {
        $text = $transliterated;
    }

    $text = preg_replace('/\s+/', '_', $text);
    $text = preg_replace('/[^A-Za-z0-9_\(\)\+]+/', '_', $text);
    $text = preg_replace('/_+/', '_', $text);
    $text = trim($text, '_');

    return $text !== '' ? $text : 'clip';
}

function clip_mp4_service_find_unique_basename(string $base, string $dir, ?string $ignorePath = null): string {
    $candidate = $base;
    $counter = 1;
    while (true) {
        $path = $dir . DIRECTORY_SEPARATOR . $candidate . '.mp4';
        if (file_exists($path)) {
            if ($ignorePath && realpath($path) === realpath($ignorePath)) {
                return $candidate;
            }
            $counter++;
            $candidate = $base . '_' . $counter;
            continue;
        }
        return $candidate;
    }
}

function clip_mp4_service_register_clip_path(?int $clipId, string $path): void
{
          if (!$clipId || !$path) {
                    return;
          }
          clip_file_helper_register_clip_path($clipId, $path);
}

function clip_mp4_service_get_clip_filesystem_path(array $clip): ?string
{
          $matchId = isset($clip['match_id']) ? (int)$clip['match_id'] : 0;
          $clipId = isset($clip['id']) ? (int)$clip['id'] : (isset($clip['clip_id']) ? (int)$clip['clip_id'] : 0);
          if ($matchId <= 0 || $clipId <= 0) {
                    return null;
          }

          $canonicalPath = clip_file_helper_get_canonical_path($matchId, $clipId);
          $existing = clip_mp4_service_existing_clip_path($canonicalPath, $clip);
          if ($existing && is_file($existing)) {
                    return $existing;
          }

          return null;
}

function clip_mp4_service_get_clip_web_path(array $clip): ?string
{
          $fsPath = clip_mp4_service_get_clip_filesystem_path($clip);
          if ($fsPath === null) {
                    return null;
          }
          return clip_file_helper_absolute_to_public_path($fsPath);
}
