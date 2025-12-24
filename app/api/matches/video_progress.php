<?php

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/match_repository.php';
require_once __DIR__ . '/../../lib/match_permissions.php';

auth_boot();
require_auth();

header('Content-Type: application/json');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
          http_response_code(405);
          echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
          exit;
}

$matchId = isset($matchId) ? (int)$matchId : 0;

$matchId = (int)$matchId;
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

$projectRoot = realpath(__DIR__ . '/../../..');
if ($projectRoot === false) {
          http_response_code(500);
          echo json_encode(['ok' => false, 'error' => 'Unable to resolve project root']);
          exit;
}

$progressFile = $projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'veo_progress' . DIRECTORY_SEPARATOR . $matchId . '.json';
$outputFile = $projectRoot . DIRECTORY_SEPARATOR . 'videos' . DIRECTORY_SEPARATOR . 'matches' . DIRECTORY_SEPARATOR . $matchId . DIRECTORY_SEPARATOR . 'original.mp4';
$webPath = '/videos/matches/' . $matchId . '/original.mp4';

$status = $match['video_download_status'] ?? 'pending';
$progress = (int)($match['video_download_progress'] ?? 0);
$errorMessage = $match['video_error_message'] ?? null;

if (is_file($progressFile)) {
          $decoded = json_decode((string)file_get_contents($progressFile), true);
          if (is_array($decoded)) {
                    $status = $decoded['status'] ?? $status;
                    $progress = isset($decoded['progress']) ? (int)$decoded['progress'] : $progress;
                    if (isset($decoded['error'])) {
                              $errorMessage = $decoded['error'];
                    }
          }
}

$validStatuses = ['pending', 'downloading', 'completed', 'failed'];
if (!in_array($status, $validStatuses, true)) {
          $status = 'pending';
}

if (file_exists($outputFile) && $status !== 'failed') {
          $status = 'completed';
          $progress = 100;
}

if ($status === 'completed' && $progress < 100) {
          $progress = 100;
}

$progress = max(0, min(100, $progress));

upsert_match_video($matchId, [
          'source_type' => 'veo',
          'source_url' => $match['video_source_url'] ?? null,
          'source_path' => $webPath,
          'download_status' => $status,
          'download_progress' => $progress,
          'error_message' => $errorMessage ? substr((string)$errorMessage, 0, 255) : null,
]);

$response = [
          'ok' => true,
          'status' => $status,
          'progress' => $progress,
];

if ($errorMessage) {
          $response['error'] = $errorMessage;
}

if ($status === 'completed') {
          $response['video_path'] = $webPath;
}

echo json_encode($response);
