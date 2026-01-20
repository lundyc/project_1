<?php
/**
 * Player Performance Statistics API Endpoint
 * 
 * GET /api/stats/player-performance?season_id=X&type=league|cup
 * 
 * Returns player performance statistics aggregated across all matches for the club.
 * Includes appearances, goals, cards, and estimated minutes played.
 * Supports filtering by season and competition type.
 * 
 * Query Parameters:
 *   - season_id: Filter by season ID (optional)
 *   - type: Filter by competition type (league|cup, optional, defaults to both)
 */
require_auth();
require_once __DIR__ . '/../../lib/StatsService.php';
require_once __DIR__ . '/../../lib/stats_context.php';

header('Content-Type: application/json');

try {
    $context = resolve_club_context_for_stats();
    $clubId = $context['club_id'];
    
    // Get filter parameters
    $seasonId = !empty($_GET['season_id']) ? (int)$_GET['season_id'] : null;
    $type = !empty($_GET['type']) && in_array($_GET['type'], ['league', 'cup'], true) ? $_GET['type'] : null;

    // Delegate to service
    $service = new StatsService($clubId);
    $players = $service->getPlayerPerformanceForClub($seasonId, $type);
    
    echo json_encode([
        'success' => true,
        'players' => $players,
    ]);
} catch (\Throwable $e) {
    error_log('Stats Player Performance API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Unable to load player performance statistics',
    ]);
}
?>
