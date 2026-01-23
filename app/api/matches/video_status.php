<?php

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../../lib/match_permissions.php';

auth_boot();
require_auth();

header('Content-Type: application/json');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
          http_response_code(405);
          echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
          exit;
}

$matchId = isset($matchId) ? (int)$matchId : (int)($_GET['match_id'] ?? 0);
if ($matchId <= 0) {
          http_response_code(400);
          echo json_encode(['ok' => false, 'error' => 'Match id required']);
          exit;
}

$pdo = db();
$stmt = $pdo->prepare('SELECT club_id FROM matches WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $matchId]);
$match = $stmt->fetch();

if (!$match) {
          http_response_code(404);
          echo json_encode(['ok' => false, 'error' => 'Match not found']);
          exit;
}

$user = current_user();
$roles = $_SESSION['roles'] ?? [];
if (!can_view_match($user, $roles, (int)$match['club_id'])) {
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
$progressFile = $projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'video_progress' . DIRECTORY_SEPARATOR . $matchId . '.json';

if (!is_file($progressFile)) {
          echo json_encode([
                    'ok' => true,
                    'status' => 'pending',
                    'percent' => 0,
                    'downloaded_bytes' => 0,
                    'downloaded_mb' => 0,
                    'downloaded_gb' => 0,
                    'total_bytes' => 0,
                    'message' => 'Waiting to start download',
          ]);
          exit;
}

$decoded = json_decode((string)file_get_contents($progressFile), true);
if (!is_array($decoded)) {
          echo json_encode([
                    'ok' => true,
                    'status' => 'pending',
                    'percent' => 0,
                    'downloaded_bytes' => 0,
                    'downloaded_mb' => 0,
                    'downloaded_gb' => 0,
                    'total_bytes' => 0,
                    'message' => 'Waiting to start download',
          ]);
          exit;
}

echo json_encode(array_merge(['ok' => true], $decoded));
