<?php

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/match_repository.php';
require_once __DIR__ . '/../../lib/match_permissions.php';

auth_boot();
require_auth();

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
          http_response_code(405);
          echo json_encode(['error' => 'Method not allowed']);
          exit;
}

$matchId = isset($matchId) ? (int)$matchId : 0;
if ($matchId <= 0) {
          http_response_code(400);
          echo json_encode(['error' => 'Match id required']);
          exit;
}

$match = get_match($matchId);
if (!$match) {
          http_response_code(404);
          echo json_encode(['error' => 'Match not found']);
          exit;
}

$user = current_user();
$roles = $_SESSION['roles'] ?? [];
if (!can_view_match($user, $roles, (int)$match['club_id'])) {
          http_response_code(403);
          echo json_encode(['error' => 'Forbidden']);
          exit;
}

$basePath = base_path();
$sharePath = ($basePath ? $basePath : '') . '/matches/' . $matchId;
$proto = $_SERVER['HTTPS'] ?? '';
if ($proto === '' && !empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
          $proto = $_SERVER['HTTP_X_FORWARDED_PROTO'];
}
$scheme = in_array(strtolower($proto), ['https', 'on'], true) ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
if ($host !== '') {
          $origin = $scheme . '://' . $host;
          $shareUrl = $origin . $sharePath;
} else {
          $shareUrl = $sharePath;
}

$homeTeam = trim((string)($match['home_team'] ?? ''));
$awayTeam = trim((string)($match['away_team'] ?? ''));
$titleParts = array_filter([$homeTeam, $awayTeam], static fn(string $value) => $value !== '');
$title = $titleParts !== [] ? implode(' vs ', $titleParts) : 'Untitled match';

echo json_encode([
          'match_id' => $matchId,
          'title' => $title,
          'share_url' => $shareUrl,
], JSON_UNESCAPED_SLASHES);
