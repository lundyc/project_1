<?php

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/match_repository.php';
require_once __DIR__ . '/../../lib/match_permissions.php';

auth_boot();
require_auth();

header('Content-Type: application/json');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
          http_response_code(405);
          echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
          exit;
}

$matchId = isset($matchId) ? (int)$matchId : (int)($_POST['match_id'] ?? 0);
if ($matchId <= 0) {
          http_response_code(400);
          echo json_encode(['ok' => false, 'error' => 'Match id required']);
          exit;
}

$match = get_match($matchId);
if (!$match) {
          http_response_code(404);
          echo json_encode(['ok' => false, 'error' => 'Match not found']);
          exit;
}

$user = current_user();
$roles = $_SESSION['roles'] ?? [];
if (!can_manage_match_for_club($user, $roles, (int)$match['club_id'])) {
          http_response_code(403);
          echo json_encode(['ok' => false, 'error' => 'Forbidden']);
          exit;
}

if (($match['video_source_type'] ?? '') !== 'veo') {
          http_response_code(400);
          echo json_encode(['ok' => false, 'error' => 'Match is not configured for VEO downloads']);
          exit;
}

$veoUrl = trim($match['video_source_url'] ?? '');
if ($veoUrl === '') {
          http_response_code(422);
          echo json_encode(['ok' => false, 'error' => 'VEO URL missing from match metadata']);
          exit;
}

$projectRoot = realpath(__DIR__ . '/../../..');
if ($projectRoot === false) {
          http_response_code(500);
          echo json_encode(['ok' => false, 'error' => 'Unable to resolve project root']);
          exit;
}

$logFile = $projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'veo_download.log';
@mkdir(dirname($logFile), 0777, true);

function log_retry(int $matchId, string $message, string $logFile): void
{
          $prefix = '[VEO RETRY][match:' . $matchId . '] ';
          $line = date('Y-m-d H:i:s') . ' ' . $prefix . $message;
          error_log($prefix . $message);
          file_put_contents($logFile, $line . PHP_EOL, FILE_APPEND);
}

log_retry($matchId, 'Retry requested (match data=' . json_encode([
          'match_id' => $matchId,
          'veo_url' => $veoUrl,
]) . ')', $logFile);

$progressDir = $projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'video_progress';
@mkdir($progressDir, 0777, true);
$progressFile = $progressDir . DIRECTORY_SEPARATOR . $matchId . '.json';
$cancelFile = $progressFile . '.cancel';

if (is_file($progressFile)) {
          @unlink($progressFile);
}
if (is_file($cancelFile)) {
          @unlink($cancelFile);
}

try {
          upsert_match_video($matchId, [
                    'source_type' => 'veo',
                    'source_url' => $veoUrl,
                    'source_path' => '/videos/matches/match_' . $matchId . '/source/veo',
                    'download_status' => 'pending',
                    'download_progress' => 0,
                    'error_message' => null,
          ]);
} catch (\Throwable $e) {
          log_retry($matchId, 'Failed to reset DB: ' . $e->getMessage(), $logFile);
          http_response_code(500);
          echo json_encode(['ok' => false, 'error' => 'Unable to reset download state']);
          exit;
}

$pyDir = $projectRoot . DIRECTORY_SEPARATOR . 'py';
$script = $pyDir . DIRECTORY_SEPARATOR . 'veo_downloader.py';
if (!is_file($script)) {
          log_retry($matchId, 'Downloader script missing: ' . $script, $logFile);
          http_response_code(500);
          echo json_encode(['ok' => false, 'error' => 'Downloader script missing']);
          exit;
}

$python = $pyDir . DIRECTORY_SEPARATOR . '.venv' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'python';
if (!is_file($python)) {
          $python = '/usr/bin/python3';
}
if (!is_file($python)) {
          $python = 'python3';
}

$runtimeLog = $projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'veo_downloader_runtime.log';
@mkdir(dirname($runtimeLog), 0777, true);

// Capture yt-dlp output so failures are visible; the previous /dev/null redirect hid the immediate exit.
$cmd = sprintf(
          'cd %s && %s %s %d %s >> %s 2>&1 &',
          escapeshellarg($projectRoot),
          escapeshellcmd($python),
          escapeshellarg($script),
          $matchId,
          escapeshellarg($veoUrl),
          escapeshellarg($runtimeLog)
);

log_retry($matchId, 'Spawning downloader (cmd=' . $cmd . ')', $logFile);
exec($cmd);

echo json_encode(['ok' => true, 'status' => 'pending']);
