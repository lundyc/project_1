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

$progressFile = $projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'video_progress' . DIRECTORY_SEPARATOR . $matchId . '.json';

if (!is_file($progressFile)) {
          echo json_encode(['status' => 'pending']);
          exit;
}

$contents = file_get_contents($progressFile);
if ($contents === false || trim($contents) === '') {
          echo json_encode(['status' => 'pending']);
          exit;
}

echo $contents;
