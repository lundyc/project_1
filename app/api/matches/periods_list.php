<?php

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/match_repository.php';
require_once __DIR__ . '/../../lib/match_permissions.php';
require_once __DIR__ . '/../../lib/match_period_repository.php';

auth_boot();
require_auth();

header('Content-Type: application/json');

$matchId = isset($matchId) ? (int)$matchId : (int)($_GET['match_id'] ?? 0);
if ($matchId <= 0) {
          http_response_code(400);
          echo json_encode(['ok' => false, 'error' => 'match_id_required']);
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

$periods = get_match_periods($matchId);
$active = get_active_match_period($matchId);

echo json_encode([
          'ok' => true,
          'periods' => $periods,
          'active_period_id' => $active['id'] ?? null,
]);
