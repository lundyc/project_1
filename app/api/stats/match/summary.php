<?php
/**
 * Match Summary Panel API
 *
 * GET /api/stats/match/summary?match_id={match_id}
 *
 * Returns rendered HTML for the match summary panel (used for dynamic updates).
 */
require_once __DIR__ . '/../../../lib/match_repository.php';
require_once __DIR__ . '/../../../lib/match_permissions.php';
require_once __DIR__ . '/../../../lib/event_repository.php';
require_once __DIR__ . '/../../../lib/match_stats_service.php';
require_once __DIR__ . '/../../../lib/match_player_repository.php';

require_auth();
header('Content-Type: application/json');

try {
    $matchId = (int)($_GET['match_id'] ?? 0);
    if ($matchId <= 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Missing or invalid match ID']);
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
    if (!can_view_match($user, $roles, (int)($match['club_id'] ?? 0))) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Access denied']);
        exit;
    }

    // Load required data
    $events = event_list_for_match($matchId);
    $matchPlayers = get_match_players($matchId);
    
    // Ensure default event types and load them
    ensure_default_event_types((int)$match['club_id']);
    $db = db();
    $eventTypesStmt = $db->prepare('SELECT id, label, type_key, default_importance FROM event_types WHERE club_id = :club_id ORDER BY label ASC');
    $eventTypesStmt->execute(['club_id' => (int)$match['club_id']]);
    $eventTypes = $eventTypesStmt->fetchAll();
    
    // Compute derived stats (force=true to bypass cache and get fresh calculations)
    $eventsVersion = count($events);
    $derivedStats = get_or_compute_match_stats($matchId, $eventsVersion, $events, $eventTypes, true);

    // Render the summary partial
    ob_start();
    require __DIR__ . '/../../../views/partials/match-summary-stats.php';
    $html = ob_get_clean();

    echo json_encode([
        'ok' => true,
        'html' => $html,
    ]);
} catch (\Throwable $e) {
    error_log("Match summary API error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'ok' => false, 
        'error' => 'Server error',
        'debug' => $e->getMessage() . " (" . get_class($e) . ")"
    ]);
}
