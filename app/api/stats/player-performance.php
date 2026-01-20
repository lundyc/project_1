<?php
/**
 * Player Performance Statistics API Endpoint
 * 
 * GET /api/stats/player-performance
 * 
 * Returns player performance statistics aggregated across all matches for the club.
 * Includes appearances, goals, cards, and estimated minutes played.
 * 
 * Data Flow: StatsService::getPlayerPerformanceForClub() → HTTP response as JSON
 */
require_auth();
require_once __DIR__ . '/../../lib/StatsService.php';
require_once __DIR__ . '/../../lib/stats_context.php';

header('Content-Type: application/json');

try {
    $context = resolve_club_context_for_stats();
    $clubId = $context['club_id'];

    // Delegate to service
    $service = new StatsService($clubId);
    $players = $service->getPlayerPerformanceForClub();
    
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
