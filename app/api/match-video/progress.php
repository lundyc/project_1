<?php

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/match_repository.php';
require_once __DIR__ . '/../../lib/match_permissions.php';

if (!function_exists('match_wizard_log_path')) {
          function match_wizard_log_path(): ?string
          {
                    static $path;
                    if ($path !== null) {
                              return $path;
                    }
                    $root = realpath(__DIR__ . '/../../..');
                    if ($root === false) {
                              return null;
                    }
                    $dir = $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs';
                    @mkdir($dir, 0777, true);
                    return $path = $dir . DIRECTORY_SEPARATOR . 'match_wizard_debug.log';
          }
}

if (!function_exists('log_match_wizard_event')) {
          function log_match_wizard_event(?int $matchId, string $stage, string $message, array $context = []): void
          {
                    $logPath = match_wizard_log_path();
                    $timestamp = date('c');
                    $parts = ["[$timestamp]", "[stage:$stage]"];
                    if ($matchId !== null) {
                              $parts[] = "[match:$matchId]";
                    }
                    $parts[] = $message;
                    if ($context) {
                              $encoded = json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                              if ($encoded !== false) {
                                        $parts[] = $encoded;
                              }
                    }
                    $line = implode(' ', $parts);
                    if ($logPath) {
                              file_put_contents($logPath, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
                    }
                    error_log($line);
          }
}

function respond_error(int $status, string $message, string $errorCode, ?int $matchId = null, array $context = []): void
{
          log_match_wizard_event($matchId, 'progress_error', $message, array_merge(['error_code' => $errorCode], $context));
          http_response_code($status);
          echo json_encode(array_merge([
                    'ok' => false,
                    'match_id' => $matchId,
                    'status' => 'pending',
                    'percent' => 0,
                    'downloaded' => 0,
                    'total' => 0,
                    'message' => $message,
                    'path' => null,
                    'updated_at' => null,
                    'error_code' => $errorCode,
          ], $context), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
          exit;
}

auth_boot();
require_auth();

header('Content-Type: application/json');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
          respond_error(405, 'Method not allowed', 'method_not_allowed');
}

$matchId = isset($matchId) ? (int)$matchId : (int)($_GET['match_id'] ?? 0);
if ($matchId <= 0) {
          respond_error(400, 'Match id required', 'match_id_required');
}

$match = get_match($matchId);
if (!$match) {
          respond_error(404, 'Match not found', 'match_not_found', $matchId);
}

$user = current_user();
$roles = $_SESSION['roles'] ?? [];
if (!can_view_match($user, $roles, (int)$match['club_id'])) {
          respond_error(403, 'Forbidden', 'match_forbidden', $matchId);
}

$projectRoot = realpath(__DIR__ . '/../../..');
if ($projectRoot === false) {
          respond_error(500, 'Unable to resolve project root', 'project_root_missing', $matchId);
}

$progressDir = $projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'video_progress';
@mkdir($progressDir, 0777, true);
$progressFile = $progressDir . DIRECTORY_SEPARATOR . $matchId . '.json';

if (!is_file($progressFile)) {
          respond_error(200, 'Progress file missing', 'progress_file_missing', $matchId, ['progress_issue' => 'missing', 'progress_file' => $progressFile]);
}

$contents = file_get_contents($progressFile);
if ($contents === false || trim($contents) === '') {
          respond_error(200, 'Progress file unreadable', 'progress_file_not_readable', $matchId, ['progress_issue' => 'unreadable', 'progress_file' => $progressFile]);
}

$decoded = json_decode($contents, true);
if (!is_array($decoded)) {
          respond_error(200, 'Progress file corrupt', 'progress_file_invalid', $matchId, ['progress_issue' => 'invalid_json', 'progress_file' => $progressFile]);
}

$status = strtolower(trim((string)($decoded['status'] ?? 'pending')));
$stage = trim((string)($decoded['stage'] ?? 'pending'));
$percent = isset($decoded['percent']) ? (int)$decoded['percent'] : 0;
$downloaded = isset($decoded['downloaded_bytes']) ? (int)$decoded['downloaded_bytes'] : 0;
$total = isset($decoded['total_bytes']) ? (int)$decoded['total_bytes'] : 0;
$message = trim((string)($decoded['message'] ?? $decoded['error'] ?? ''));
$errorCode = $decoded['error_code'] ?? null;
$pid = $decoded['pid'] ?? null;
$updatedAt = $decoded['updated_at'] ?? $decoded['updatedAt'] ?? null;
$lastSeenAt = $decoded['last_seen_at'] ?? $updatedAt;
$path = $decoded['path'] ?? null;

if ($status === 'completed' && $path) {
          $trimmed = rtrim($path, '/');
          $path = $trimmed . '/standard/match_' . $matchId . '_standard.mp4';
}

$progressIssue = null;
if ($lastSeenAt) {
          $lastSeenTs = strtotime($lastSeenAt);
          if ($lastSeenTs !== false && (time() - $lastSeenTs) > 10) {
                    $progressIssue = 'stale_progress';
          }
}

log_match_wizard_event($matchId, 'progress_polled', 'Progress file polled', [
          'status' => $status,
          'stage' => $stage,
          'percent' => $percent,
          'pid' => $pid,
          'progress_issue' => $progressIssue,
          'error_code' => $errorCode,
]);

$response = [
          'ok' => true,
          'match_id' => $matchId,
          'status' => $status === '' ? 'pending' : $status,
          'stage' => $stage === '' ? 'pending' : $stage,
          'pid' => $pid,
          'percent' => $percent,
          'downloaded' => $downloaded,
          'total' => $total,
          'message' => $message ?: null,
          'path' => $path,
          'updated_at' => $updatedAt,
          'last_seen_at' => $lastSeenAt,
          'error_code' => $errorCode,
          'progress_issue' => $progressIssue,
          'progress_file' => $progressFile,
          'diagnostics' => [
                    'log_path' => '/storage/logs/match_wizard_debug.log',
                    'progress_issue' => $progressIssue,
                    'last_seen_at' => $lastSeenAt,
          ],
];

echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
