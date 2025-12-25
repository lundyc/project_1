<?php

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/match_repository.php';
require_once __DIR__ . '/../../lib/match_permissions.php';

auth_boot();
require_auth();

header('Content-Type: application/json');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
          http_response_code(405);
          echo json_encode(['status' => 'pending', 'error' => 'Method not allowed']);
          exit;
}

$matchId = isset($matchId) ? (int)$matchId : (int)($_GET['match_id'] ?? 0);
if ($matchId <= 0) {
          http_response_code(400);
          echo json_encode(['status' => 'pending', 'error' => 'Match id required']);
          exit;
}

$match = get_match($matchId);
if (!$match) {
          http_response_code(404);
          echo json_encode(['status' => 'pending', 'error' => 'Match not found']);
          exit;
}

$user = current_user();
$roles = $_SESSION['roles'] ?? [];
if (!can_view_match($user, $roles, (int)$match['club_id'])) {
          http_response_code(403);
          echo json_encode(['status' => 'pending', 'error' => 'Forbidden']);
          exit;
}

$projectRoot = realpath(__DIR__ . '/../../..');
if ($projectRoot === false) {
          http_response_code(500);
          echo json_encode(['status' => 'pending', 'error' => 'Unable to resolve project root']);
          exit;
}

$progressDir = $projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'video_progress';
$progressFile = $progressDir . DIRECTORY_SEPARATOR . $matchId . '.json';

$defaultResponse = [
          'ok' => false,
          'match_id' => $matchId,
          'status' => 'pending',
          'percent' => 0,
          'downloaded' => 0,
          'total' => 0,
          'message' => 'Waiting to start download',
          'path' => null,
          'updated_at' => null,
];

if (!is_file($progressFile)) {
          echo json_encode($defaultResponse);
          exit;
}

$contents = file_get_contents($progressFile);
if ($contents === false || trim($contents) === '') {
          echo json_encode($defaultResponse);
          exit;
}

$decoded = json_decode($contents, true);
if (!is_array($decoded)) {
          echo json_encode($defaultResponse);
          exit;
}

$status = strtolower(trim((string)($decoded['status'] ?? 'pending')));
$percent = isset($decoded['percent']) ? (int)$decoded['percent'] : 0;
$downloaded = isset($decoded['downloaded_bytes']) ? (int)$decoded['downloaded_bytes'] : 0;
$total = isset($decoded['total_bytes']) ? (int)$decoded['total_bytes'] : 0;
$message = trim((string)($decoded['message'] ?? $decoded['error'] ?? ''));
$updatedAt = $decoded['updated_at'] ?? $decoded['updatedAt'] ?? null;
$path = $decoded['path'] ?? null;

if ($status === 'completed' && $path) {
          $trimmed = rtrim($path, '/');
          $path = $trimmed . '/standard/match_' . $matchId . '_standard.mp4';
}

echo json_encode([
          'ok' => true,
          'match_id' => $matchId,
          'status' => $status === '' ? 'pending' : $status,
          'percent' => $percent,
          'downloaded' => $downloaded,
          'total' => $total,
          'message' => $message ?: null,
          'path' => $path,
          'updated_at' => $updatedAt,
]);
