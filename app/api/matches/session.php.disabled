<?php

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/match_repository.php';
require_once __DIR__ . '/../../lib/match_permissions.php';
require_once __DIR__ . '/../../lib/match_session_repository.php';
require_once __DIR__ . '/../../lib/match_session_token.php';

/**
 * Minimal .env loader so PHP can see the same MATCH_SESSION_* variables
 * that the Node service reads via dotenv.
 */
function match_session_load_env_file(): void
{
          static $loaded = false;
          if ($loaded) {
                    return;
          }
          $loaded = true;
          $envPath = realpath(__DIR__ . '/../../../.env');
          if (!$envPath || !is_file($envPath)) {
                    return;
          }
          $lines = @file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
          if (!$lines) {
                    return;
          }
          foreach ($lines as $line) {
                    $trimmed = trim($line);
                    if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                              continue;
                    }
                    $parts = explode('=', $trimmed, 2);
                    if (count($parts) !== 2) {
                              continue;
                    }
                    $key = trim($parts[0]);
                    $value = trim($parts[1]);
                    if ($key === '') {
                              continue;
                    }
                    // Strip simple surrounding quotes.
                    if (
                              (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                              (str_starts_with($value, "'") && str_ends_with($value, "'"))
                    ) {
                              $value = substr($value, 1, -1);
                    }
                    putenv($key . '=' . $value);
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
          }
}

match_session_load_env_file();

auth_boot();
require_auth();

header('Content-Type: application/json');

$matchId = isset($matchId) ? (int)$matchId : (int)($_GET['match_id'] ?? 0);
if ($matchId <= 0) {
          http_response_code(400);
          echo json_encode(['ok' => false, 'error' => 'invalid_match']);
          exit;
}

$match = get_match($matchId);
if (!$match) {
          http_response_code(404);
          echo json_encode(['ok' => false, 'error' => 'not_found']);
          exit;
}

$user = current_user();
$roles = $_SESSION['roles'] ?? [];
if (!can_view_match($user, $roles, (int)$match['club_id'])) {
          http_response_code(403);
          echo json_encode(['ok' => false, 'error' => 'forbidden']);
          exit;
}

$roleParam = isset($_GET['role']) ? strtolower(trim((string)$_GET['role'])) : 'analyst';
$role = $roleParam === 'viewer' ? 'viewer' : 'analyst';

$nowMs = (int)round(microtime(true) * 1000);
$tokenTtlMs = (int)(getenv('MATCH_SESSION_TOKEN_TTL_MS') ?: (6 * 60 * 60 * 1000));
$tokenPayload = [
          'matchId' => $matchId,
          'userId' => (int)$user['id'],
          'userName' => (string)($user['display_name'] ?? 'Analyst'),
          'role' => $role,
          'clubId' => (int)$match['club_id'],
          'iat' => $nowMs,
          'exp' => $nowMs + max(60000, $tokenTtlMs),
];

try {
          $token = match_session_create_token($tokenPayload);
} catch (\Throwable $e) {
          error_log('[match-session] token create failed: ' . $e->getMessage());
          http_response_code(500);
          echo json_encode(['ok' => false, 'error' => 'token_failed']);
          exit;
}

$wsUrl = getenv('MATCH_SESSION_WS_URL');
if (!is_string($wsUrl) || $wsUrl === '') {
          $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
          $rawHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
          $host = explode(':', $rawHost)[0] ?: 'localhost';
          $port = (int)(getenv('MATCH_SESSION_PORT') ?: 4001);
          $wsUrl = $scheme . '://' . $host . ':' . $port;
}

$snapshot = match_session_snapshot($matchId);

echo json_encode([
          'ok' => true,
          'matchId' => $matchId,
          'role' => $role,
          'websocketUrl' => $wsUrl,
          'token' => $token,
          'snapshot' => $snapshot,
          'serverTime' => $nowMs,
]);
