<?php

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/match_permissions.php';
require_once __DIR__ . '/../../lib/db.php';

if (!function_exists('match_wizard_log_path')) {
          function match_wizard_log_path(): ?string
          {
                    static $path = null;
                    if ($path !== null) {
                              return $path === false ? null : $path;
                    }
                    $root = realpath(__DIR__ . '/../../..');
                    if ($root === false) {
                              $path = false;
                              return null;
                    }
                    $dir = $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs';
                    @mkdir($dir, 0777, true);
                    if (!is_dir($dir) || !is_writable($dir)) {
                              error_log('[match_wizard_log] Unable to write logs to ' . $dir . ' (needs write permissions)');
                              $path = false;
                              return null;
                    }
                    $candidate = $dir . DIRECTORY_SEPARATOR . 'match_wizard_debug.log';
                    if (file_exists($candidate) && !is_writable($candidate)) {
                              error_log('[match_wizard_log] Log file exists but is not writable: ' . $candidate);
                              $path = false;
                              return null;
                    }
                    return $path = $candidate;
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

if (!function_exists('log_stage_entry')) {
          function log_stage_entry(?int $matchId, string $stage, string $message, array $context = []): void
          {
                    $allowed = ['php', 'spawn', 'poll'];
                    $stageLabel = in_array($stage, $allowed, true) ? $stage : 'php';
                    log_match_wizard_event($matchId, $stageLabel, $message, $context);
          }
}

if (!function_exists('sanitize_environment_array')) {
          function sanitize_environment_array(array $values): array
          {
                    $result = [];
                    $maskPatterns = ['PASS', 'SECRET', 'TOKEN', 'KEY', 'AUTH'];
                    foreach ($values as $key => $value) {
                              $upper = strtoupper($key);
                              $masked = false;
                              foreach ($maskPatterns as $pattern) {
                                        if (strpos($upper, $pattern) !== false) {
                                                  $result[$key] = '***';
                                                  $masked = true;
                                                  break;
                                        }
                              }
                              if ($masked) {
                                        continue;
                              }
                              if (is_scalar($value)) {
                                        $result[$key] = $value;
                              } elseif (is_array($value)) {
                                        $result[$key] = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                              } else {
                                        $result[$key] = (string)$value;
                              }
                    }
                    return $result;
          }
}

if (!function_exists('veo_spawn_log_path')) {
          function veo_spawn_log_path(): ?string
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
                    return $path = $dir . DIRECTORY_SEPARATOR . 'veo_spawn_debug.log';
          }
}

if (!function_exists('record_veo_spawn_entry')) {
          function record_veo_spawn_entry(array $entry): void
          {
                    $path = veo_spawn_log_path();
                    if (!$path) {
                              return;
                    }
                    $lines = ['[SPAWN] ' . date('c')];
                    foreach ($entry as $key => $value) {
                              if (is_array($value)) {
                                        $value = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                              } else {
                                        $value = (string)$value;
                              }
                              $safe = str_replace(["\r", "\n"], ['\\r', '\\n'], trim($value));
                              $lines[] = sprintf('%s=%s', $key, $safe);
                    }
                    $lines[] = '';
                    file_put_contents($path, implode(PHP_EOL, $lines), FILE_APPEND | LOCK_EX);
          }
}

auth_boot();
require_auth();

header('Content-Type: application/json');

$projectRoot = realpath(__DIR__ . '/../../..');
if ($projectRoot === false) {
          http_response_code(500);
          echo json_encode(['ok' => false, 'error' => 'Unable to resolve project root', 'error_code' => 'project_root_missing']);
          exit;
}

$veoLog = $projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'veo_download.log';
@mkdir(dirname($veoLog), 0777, true);

function log_veo_activity(int $matchId, string $message, array $context = []): void
{
          global $veoLog;
          log_stage_entry($matchId, 'php', $message, $context);
          $line = date('Y-m-d H:i:s') . ' [VEO][match:' . $matchId . '] ' . $message;
          if (!empty($context)) {
                    $encoded = json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                    if ($encoded !== false) {
                              $line .= ' ' . $encoded;
                    }
          }
          if (isset($veoLog)) {
                    $logDir = dirname($veoLog);
                    $canWrite = is_dir($logDir) && is_writable($logDir);
                    if ($canWrite && (!file_exists($veoLog) || is_writable($veoLog))) {
                              file_put_contents($veoLog, $line . PHP_EOL, FILE_APPEND);
                    } else {
                              error_log('[veo_download_log] Unable to write to ' . $veoLog);
                    }
          }
}

function respond_error(int $status, string $message, string $errorCode, ?int $matchId = null, array $context = []): void
{
          log_stage_entry($matchId, 'php', $message, array_merge(['error_code' => $errorCode], $context));
          http_response_code($status);
          echo json_encode(array_merge([
                    'ok' => false,
                    'error' => $message,
                    'error_code' => $errorCode,
                    'match_id' => $matchId,
          ], $context), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
          exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
          respond_error(405, 'Method not allowed', 'method_not_allowed');
}

$input = $_POST;
$rawInput = file_get_contents('php://input');
if (empty($input) && $rawInput) {
          $decoded = json_decode($rawInput, true);
          if (is_array($decoded)) {
                    $input = $decoded;
          }
}

$matchId = isset($matchId) ? (int)$matchId : (int)($input['match_id'] ?? 0);
$veoUrl = trim($input['veo_url'] ?? $input['url'] ?? '');

if ($matchId <= 0) {
          respond_error(400, 'Match id required', 'match_id_required');
}

if ($veoUrl === '' || !preg_match('#^https?://.+#i', $veoUrl)) {
          respond_error(422, 'Invalid VEO URL', 'invalid_veo_url', $matchId, ['payload' => $input]);
}

log_veo_activity($matchId, 'Start payload recorded', [
          'veo_url' => $veoUrl,
          'payload' => $input,
]);



// New structure: videos/matches/match_{id}_standard.mp4
$filename = 'match_' . $matchId . '_standard.mp4';
$publicPath = $filename; // Only store filename in DB
$absolutePath = $projectRoot . DIRECTORY_SEPARATOR . 'videos' . DIRECTORY_SEPARATOR . 'matches' . DIRECTORY_SEPARATOR . $filename;
@mkdir(dirname($absolutePath), 0777, true);
log_stage_entry($matchId, 'php', 'Configured video path', ['public' => $publicPath, 'absolute' => $absolutePath]);

$progressDir = $projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'video_progress';
@mkdir($progressDir, 0777, true);
if (!is_dir($progressDir) || !is_writable($progressDir)) {
          respond_error(500, 'Progress directory not writable', 'progress_dir_not_writable', $matchId, ['progress_dir' => $progressDir]);
}
$progressFile = $progressDir . DIRECTORY_SEPARATOR . $matchId . '.json';
if (file_exists($progressFile) && !is_writable($progressFile)) {
          respond_error(500, 'Progress file not writable', 'progress_file_not_writable', $matchId, ['progress_file' => $progressFile]);
}

$pyDir = $projectRoot . DIRECTORY_SEPARATOR . 'py';
$script = $pyDir . DIRECTORY_SEPARATOR . 'veo_downloader.py';
if (!file_exists($script)) {
          respond_error(500, 'Downloader script missing', 'python_not_started', $matchId, ['script' => $script]);
}

$pdo = db();
$matchStmt = $pdo->prepare('SELECT club_id FROM matches WHERE id = :id LIMIT 1');
$matchStmt->execute(['id' => $matchId]);
$match = $matchStmt->fetch();

if (!$match) {
          respond_error(404, 'Match not found', 'match_not_found', $matchId);
}

$user = current_user();
$roles = $_SESSION['roles'] ?? [];
if (!can_manage_match_for_club($user, $roles, (int)$match['club_id'])) {
          respond_error(403, 'Forbidden', 'match_forbidden', $matchId);
}

// Store only filename in source_path
$insertData = [
          'match_id' => $matchId,
          'source_type' => 'veo',
          'source_url' => $veoUrl,
          'source_path' => $filename,
          'download_status' => 'starting',
          'download_progress' => 0,
          'error_message' => null,
];
          log_stage_entry($matchId, 'php', 'Preparing match video record', $insertData);

try {
          $pdo->prepare('DELETE FROM match_videos WHERE match_id = :match_id')->execute(['match_id' => $matchId]);
          $sql = 'INSERT INTO match_videos (match_id, source_type, source_url, source_path, download_status, download_progress, error_message, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())';
          $stmt = $pdo->prepare($sql);
          $stmt->execute(array_values($insertData));
} catch (PDOException $e) {
          respond_error(500, 'Database error', 'match_video_db_error', $matchId, ['db_error' => $e->getMessage()]);
}

$initialProgress = [
          'status' => 'starting',
          'stage' => 'boot',
          'heartbeat' => date('c'),
          'percent' => 0,
          'downloaded_bytes' => 0,
          'total_bytes' => 0,
          'message' => 'Preparing download',
          'path' => $publicPath,
          'pid' => null,
          'error_code' => null,
          'last_seen_at' => date('c'),
          'updated_at' => date('c'),
];

if (file_put_contents($progressFile, json_encode($initialProgress, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) === false) {
          respond_error(500, 'Progress file not writable', 'progress_file_not_writable', $matchId, ['progress_file' => $progressFile]);
}
log_stage_entry($matchId, 'php', 'Initialized progress file', ['path' => $progressFile]);
log_veo_activity($matchId, 'Progress file created', ['progress_file' => $progressFile]);

$pythonCandidates = [
          $pyDir . DIRECTORY_SEPARATOR . '.venv' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'python',
          '/usr/bin/python3',
          'python3',
];
$pythonPath = null;
$usingVirtualEnv = false;
$permissionIssue = false;
foreach ($pythonCandidates as $candidate) {
          if (!file_exists($candidate)) {
                    continue;
          }
          if (!is_executable($candidate)) {
                    $permissionIssue = true;
                    continue;
          }
          $pythonPath = $candidate;
          if (strpos($candidate, DIRECTORY_SEPARATOR . '.venv' . DIRECTORY_SEPARATOR) !== false) {
                    $usingVirtualEnv = true;
          }
          break;
}

if ($pythonPath === null) {
          $errorCode = $permissionIssue ? 'permission_denied' : 'python_not_started';
          $errorMessage = $permissionIssue ? 'Python interpreter not executable' : 'Python interpreter not available';
          respond_error(500, $errorMessage, $errorCode, $matchId, ['candidates' => $pythonCandidates]);
}

log_stage_entry($matchId, 'php', 'Python interpreter resolved', ['python_path' => $pythonPath, 'virtualenv' => $usingVirtualEnv ? 'active' : 'missing']);
if (!$usingVirtualEnv) {
          log_stage_entry($matchId, 'php', 'Virtualenv not loaded; falling back to system Python', ['python_path' => $pythonPath]);
}

$stdoutLog = $projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'veo_downloader_stdout_' . $matchId . '.log';
$stderrLog = $projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'veo_downloader_stderr_' . $matchId . '.log';
@mkdir(dirname($stdoutLog), 0777, true);
$runtimeLog = $stdoutLog;

$cmd = sprintf(
          'nohup %s %s %d %s >> %s 2>> %s & echo $!',
          escapeshellcmd($pythonPath),
          escapeshellarg($script),
          $matchId,
          escapeshellarg($veoUrl),
          escapeshellarg($stdoutLog),
          escapeshellarg($stderrLog)
);

$sanitizedEnv = sanitize_environment_array($_ENV);
$sanitizedServer = sanitize_environment_array($_SERVER);
$spawnContext = [
          'cmd' => $cmd,
          'python_path' => $pythonPath,
          'virtualenv' => $usingVirtualEnv ? 'active' : 'missing',
          'project_root' => $projectRoot,
          'script' => $script,
          'stdout_log' => $stdoutLog,
          'stderr_log' => $stderrLog,
          'progress_file' => $progressFile,
          'video_path' => $publicPath,
          'cwd' => getcwd(),
          'user' => get_current_user(),
          'uid' => function_exists('posix_getuid') ? posix_getuid() : null,
          'euid' => function_exists('posix_geteuid') ? posix_geteuid() : null,
          'env.PATH' => getenv('PATH') ?: '',
          'env.VIRTUAL_ENV' => getenv('VIRTUAL_ENV') ?: 'none',
          'env' => $sanitizedEnv,
          'server' => $sanitizedServer,
];

$descriptorSpec = [
          1 => ['pipe', 'w'],
          2 => ['pipe', 'w'],
];
log_stage_entry($matchId, 'spawn', 'Downloader spawn command prepared', [
          'cmd' => $cmd,
          'stdout_path' => $stdoutLog,
          'stderr_path' => $stderrLog,
]);
$process = proc_open($cmd, $descriptorSpec, $pipes, $projectRoot);
if (!is_resource($process)) {
          log_stage_entry($matchId, 'spawn', 'Downloader spawn command failed', ['cmd' => $cmd]);
          respond_error(500, 'Unable to spawn downloader process', 'python_not_started', $matchId, ['cmd' => $cmd]);
}

$stdout = stream_get_contents($pipes[1]);
$stderr = stream_get_contents($pipes[2]);
foreach ($pipes as $pipe) {
          fclose($pipe);
}
$status = proc_get_status($process);
$pid = $status['pid'] ?? null;
$exitCode = proc_close($process);
if (!$pid) {
          if (preg_match('/\d+/', trim($stdout), $matches)) {
                    $pid = (int)$matches[0];
          }
}

$spawnContext['stdout'] = $stdout;
$spawnContext['stderr'] = $stderr;
$spawnContext['pid'] = $pid;
$spawnContext['exit_code'] = $exitCode;
record_veo_spawn_entry($spawnContext);

log_stage_entry($matchId, 'spawn', 'Downloader spawn summary', [
          'pid' => $pid,
          'exit_code' => $exitCode,
          'cwd' => $spawnContext['cwd'] ?? null,
          'user' => $spawnContext['user'] ?? null,
          'uid' => $spawnContext['uid'] ?? null,
          'euid' => $spawnContext['euid'] ?? null,
          'env.PATH' => $spawnContext['env.PATH'] ?? null,
          'env.VIRTUAL_ENV' => $spawnContext['env.VIRTUAL_ENV'] ?? null,
          'stdout_path' => $stdoutLog,
          'stderr_path' => $stderrLog,
          'cmd' => $cmd,
]);

log_stage_entry($matchId, 'spawn', 'Downloader spawn result', [
          'pid' => $pid,
          'exit_code' => $exitCode,
          'stdout_path' => $stdoutLog,
          'stderr_path' => $stderrLog,
          'stdout' => trim($stdout),
          'stderr' => trim($stderr),
]);

if ($exitCode !== 0) {
          log_stage_entry($matchId, 'spawn', 'Downloader spawn failed', ['exit_code' => $exitCode, 'stderr' => trim($stderr)]);
          respond_error(500, 'Downloader spawn failed', 'python_not_started', $matchId, ['stderr' => trim($stderr)]);
}

log_stage_entry($matchId, 'php', 'Downloader process started', [
          'pid' => $pid,
          'cmd' => $cmd,
          'stdout_log' => $stdoutLog,
          'stderr_log' => $stderrLog,
]);
log_veo_activity($matchId, 'Spawning downloader command');

$finalProgress = $initialProgress;
$finalProgress['pid'] = $pid;
$finalProgress['message'] = 'Downloader launched';
$finalProgress['last_seen_at'] = date('c');
$finalProgress['updated_at'] = date('c');

if (file_put_contents($progressFile, json_encode($finalProgress, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) === false) {
          respond_error(500, 'Unable to update progress status', 'progress_file_not_writable', $matchId, ['progress_file' => $progressFile]);
}

$response = [
          'ok' => true,
          'status' => 'starting',
          'match_id' => $matchId,
          'video_path' => $publicPath,
          'spawn' => [
                    'pid' => $pid,
                    'cmd' => $cmd,
                    'exit_code' => $exitCode,
                    'stdout' => trim($stdout),
                    'stderr' => trim($stderr),
                    'stdout_log' => '/storage/logs/veo_downloader_stdout_' . $matchId . '.log',
                    'stderr_log' => '/storage/logs/veo_downloader_stderr_' . $matchId . '.log',
          ],
          'diagnostics' => [
                    'progress_file' => '/storage/video_progress/' . $matchId . '.json',
                    'log_path' => '/storage/logs/match_wizard_debug.log',
                    'spawn_log' => '/storage/logs/veo_spawn_debug.log',
          ],
];
if (!$usingVirtualEnv) {
          $response['warning_code'] = 'venv_not_loaded';
}

echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
