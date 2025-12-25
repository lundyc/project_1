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

$response = [
          'ok' => true,
          'match_id' => $matchId,
          'status' => 'pending',
          'percent' => 0,
          'downloaded_bytes' => 0,
          'total_bytes' => 0,
          'message' => 'Waiting to start download',
          'error' => null,
          'path' => null,
          'source_url' => null,
];

if (is_file($progressFile)) {
          $contents = file_get_contents($progressFile);
          if ($contents !== false && trim($contents) !== '') {
                    $decoded = json_decode($contents, true);
                    if (is_array($decoded)) {
                              $response = array_merge($response, $decoded);
                              $response['match_id'] = $matchId;
                              echo json_encode($response);
                              exit;
                    }
          }
}

$status = strtolower((string)($match['video_download_status'] ?? 'pending'));
$response['status'] = $status === '' ? 'pending' : $status;
$response['percent'] = (int)($match['video_download_progress'] ?? 0);
$response['message'] = $match['video_error_message'] ?? ($response['status'] === 'pending' ? 'Waiting to start download' : 'Download pending');
$response['error'] = $match['video_error_message'] ?? null;
$response['path'] = $match['video_source_path'] ?? null;
$response['source_url'] = $match['video_source_url'] ?? null;

echo json_encode($response);
