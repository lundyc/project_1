<?php

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/csrf.php';
require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../../lib/match_permissions.php';

auth_boot();
require_auth();

// Validate CSRF token for state-changing operation
try {
    require_csrf_token();
} catch (CsrfException $e) {
    http_response_code(403);
    die('Invalid CSRF token');
}

header('Content-Type: application/json');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
          http_response_code(405);
          echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
          exit;
}

$matchId = isset($matchId) ? (int)$matchId : 0;
if ($matchId <= 0) {
          http_response_code(400);
          echo json_encode(['ok' => false, 'error' => 'Match id required']);
          exit;
}

$pdo = db();
$stmt = $pdo->prepare('SELECT m.club_id, mv.source_path FROM matches m LEFT JOIN match_videos mv ON mv.match_id = m.id WHERE m.id = :id ORDER BY mv.id DESC LIMIT 1');
$stmt->execute(['id' => $matchId]);
$row = $stmt->fetch();

if (!$row) {
          http_response_code(404);
          echo json_encode(['ok' => false, 'error' => 'Match not found']);
          exit;
}

$user = current_user();
$roles = $_SESSION['roles'] ?? [];
if (!can_manage_match_for_club($user, $roles, (int)$row['club_id'])) {
          http_response_code(403);
          echo json_encode(['ok' => false, 'error' => 'Forbidden']);
          exit;
}

$projectRoot = realpath(__DIR__ . '/../../..');
if ($projectRoot === false) {
          http_response_code(500);
          echo json_encode(['ok' => false, 'error' => 'Unable to resolve project root']);
          exit;
}

$progressPath = $projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'video_progress' . DIRECTORY_SEPARATOR . $matchId . '.json';
$cancelPath = $progressPath . '.cancel';
@mkdir(dirname($progressPath), 0777, true);

// Flag cancellation for the Python hook to observe.
file_put_contents($cancelPath, 'cancelled');

$path = $row['source_path'] ?: '/videos/raw/match_' . $matchId . '_raw.mp4';

$update = $pdo->prepare('UPDATE match_videos SET download_status = :status, download_progress = :progress, error_message = :error, source_path = :path WHERE match_id = :match_id ORDER BY id DESC LIMIT 1');
$update->execute([
          'status' => 'failed',
          'progress' => 0,
          'error' => 'Cancelled by user',
          'path' => $path,
          'match_id' => $matchId,
]);

$payload = [
          'status' => 'failed',
          'percent' => 0,
          'downloaded_bytes' => 0,
          'downloaded_mb' => 0,
          'downloaded_gb' => 0,
          'total_bytes' => 0,
          'message' => 'Cancelled by user',
          'error' => 'Cancelled by user',
          'path' => $path,
          'updated_at' => date('Y-m-d H:i:s'),
];
$tmpPath = $progressPath . '.tmp';
file_put_contents($tmpPath, json_encode($payload));
@rename($tmpPath, $progressPath);

echo json_encode(['ok' => true, 'status' => 'failed', 'message' => 'Cancelled']);
