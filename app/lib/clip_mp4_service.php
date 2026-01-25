<?php
// This script generates an mp4 for a given clip if it does not exist.
require_once __DIR__ . '/../lib/clip_repository.php';
require_once __DIR__ . '/../lib/video_repository.php';

function ensure_clip_mp4_exists($clip) {
    $matchId = $clip['match_id'];
    $clipId = $clip['id'];
    $start = $clip['start_second'];
    $duration = $clip['duration_seconds'];
    $clipName = $clip['clip_name'] ?? 'clip';
    $documentRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', DIRECTORY_SEPARATOR);
    $sourceVideo = $documentRoot . "/videos/matches/match_{$matchId}_standard.mp4";
    $clipsDir = $documentRoot . "/videos/clips";
    $targetPath = $clipsDir . "/match_{$matchId}_{$clipId}.mp4";
    if (!is_file($sourceVideo)) {
        log_clip_mp4_error($matchId, $clipId, "Source video missing: $sourceVideo");
        throw new Exception('match_video_missing');
    }
    if (!is_dir($clipsDir)) {
        @mkdir($clipsDir, 0775, true);
    }
    if (is_file($targetPath) && filesize($targetPath) > 0) {
        return $targetPath;
    }
    $cmd = sprintf(
        'ffmpeg -hide_banner -loglevel error -ss %d -i %s -t %d -c copy -y %s 2>&1',
        $start,
        escapeshellarg($sourceVideo),
        $duration,
        escapeshellarg($targetPath)
    );
    $output = [];
    $exitCode = 0;
    exec($cmd, $output, $exitCode);
    $logMsg = "[" . date('Y-m-d H:i:s') . "] ffmpeg cmd: $cmd\nOutput: " . implode("\n", $output) . "\nExit code: $exitCode\n";
    log_clip_mp4_error($matchId, $clipId, $logMsg);
    if ($exitCode !== 0 || !is_file($targetPath) || filesize($targetPath) === 0) {
        log_clip_mp4_error($matchId, $clipId, "Clip generation failed for $targetPath");
        throw new Exception('clip_generation_failed');
    }
    return $targetPath;

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
