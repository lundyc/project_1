<?php

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/match_repository.php';
require_once __DIR__ . '/../../lib/match_permissions.php';
require_once __DIR__ . '/../../lib/event_repository.php';

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

if (!can_view_match($user, $roles, (int)$match['club_id']) && !can_manage_match_for_club($user, $roles, (int)$match['club_id'])) {
          http_response_code(403);
          echo json_encode(['ok' => false, 'error' => 'forbidden']);
          exit;
}

$events = list_events($matchId);

echo json_encode([
          'ok' => true,
          'events' => $events,
          'meta' => [
                    'events_version' => (int)($match['events_version'] ?? 0),
                    'match_id' => $matchId,
          ],
]);
