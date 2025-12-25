<?php

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/match_permissions.php';
require_once __DIR__ . '/../../lib/db.php';

auth_boot();
require_auth();

header('Content-Type: application/json');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
          http_response_code(405);
          echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
          exit;
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

$projectRoot = realpath(__DIR__ . '/../../..');
if ($projectRoot === false) {
          http_response_code(500);
          echo json_encode(['ok' => false, 'error' => 'Unable to resolve project root']);
          exit;
}

$logFile = $projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'veo_download.log';
@mkdir(dirname($logFile), 0777, true);

function log_veo(int $matchId, string $message, string $logFile): void
{
          $prefix = '[VEO][match:' . $matchId . '] ';
          $line = date('Y-m-d H:i:s') . ' ' . $prefix . $message;
          error_log($prefix . $message);
          file_put_contents($logFile, $line . PHP_EOL, FILE_APPEND);
}

log_veo($matchId, 'API start payload=' . json_encode($input), $logFile);

if ($matchId <= 0) {
          http_response_code(400);
          echo json_encode(['ok' => false, 'error' => 'Match id required']);
          exit;
}

$veoUrl = trim($input['veo_url'] ?? $input['url'] ?? '');
if ($veoUrl === '' || !preg_match('#^https?://.+#i', $veoUrl)) {
          http_response_code(422);
          echo json_encode(['ok' => false, 'error' => 'Invalid VEO URL']);
          exit;
}

$filename = 'match_' . $matchId . '_raw.mp4';
$publicPath = '/videos/raw/' . $filename;
$absolutePath = $projectRoot . DIRECTORY_SEPARATOR . 'videos' . DIRECTORY_SEPARATOR . 'raw' . DIRECTORY_SEPARATOR . $filename;
@mkdir(dirname($absolutePath), 0777, true);
log_veo($matchId, 'Output path (public)=' . $publicPath . ' absolute=' . $absolutePath, $logFile);

$pdo = db();
$matchStmt = $pdo->prepare('SELECT club_id FROM matches WHERE id = :id LIMIT 1');
$matchStmt->execute(['id' => $matchId]);
$match = $matchStmt->fetch();

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
$insertData = [
          'match_id' => $matchId,
          'source_type' => 'veo',
          'source_url' => $veoUrl,
          'source_path' => $publicPath, // must never be null
          'download_status' => 'starting',
          'download_progress' => 0,
          'error_message' => null,
];
log_veo($matchId, 'DB params=' . json_encode($insertData), $logFile);

try {
          $pdo->prepare('DELETE FROM match_videos WHERE match_id = :match_id')->execute(['match_id' => $matchId]);
          $sql = 'INSERT INTO match_videos (match_id, source_type, source_url, source_path, download_status, download_progress, error_message, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())';
          $stmt = $pdo->prepare($sql);
          $stmt->execute(array_values($insertData));
          $videoId = (int)$pdo->lastInsertId();
} catch (PDOException $e) {
          log_veo($matchId, 'Database error: ' . $e->getMessage(), $logFile);
          http_response_code(500);
          echo json_encode(['ok' => false, 'error' => 'Database error']);
          exit;
}

$pyDir = $projectRoot . DIRECTORY_SEPARATOR . 'py';
$script = $pyDir . DIRECTORY_SEPARATOR . 'veo_downloader.py';
if (!file_exists($script)) {
          log_veo($matchId, 'Script missing: ' . $script, $logFile);
          http_response_code(500);
          echo json_encode(['ok' => false, 'error' => 'Downloader script missing']);
          exit;
}

$python = $pyDir . DIRECTORY_SEPARATOR . '.venv' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'python';

$progressDir = $projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'video_progress';
@mkdir($progressDir, 0777, true);
$progressFile = $progressDir . DIRECTORY_SEPARATOR . $matchId . '.json';

$initialProgress = [
          'status' => 'starting',
          'percent' => 0,
          'downloaded_bytes' => 0,
          'total_bytes' => 0,
          'message' => 'Starting download',
          'updated_at' => date('Y-m-d H:i:s'),
];
file_put_contents($progressFile, json_encode($initialProgress));

$cmd = sprintf(
          '%s %s %d %s > /dev/null 2>&1 &',
          escapeshellcmd($python),
          escapeshellarg($script),
          $matchId,
          escapeshellarg($veoUrl)
);

log_veo($matchId, 'Spawning downloader (detached) cmd=' . $cmd, $logFile);
exec($cmd);

echo json_encode([
          'ok' => true,
          'status' => 'starting',
          'video_path' => $publicPath,
]);
