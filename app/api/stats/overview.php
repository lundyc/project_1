<?php
/**
 * Overview Statistics API Endpoint
 * 
 * GET /api/stats/overview?season_id=X&type=league|cup
 * 
 * Returns aggregated club statistics (total matches, goals, wins/draws/losses, clean sheets, etc.)
 * Supports filtering by season and competition type.
 * 
 * Query Parameters:
 *   - season_id: Filter by season ID (optional)
 *   - type: Filter by competition type (league|cup, optional, defaults to both)
 */
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/StatsService.php';
require_once __DIR__ . '/../../lib/stats_context.php';

auth_boot();
require_auth();

header('Content-Type: application/json');

try {
    $context = resolve_club_context_for_stats();
    $clubId = $context['club_id'];
    
    // Get filter parameters
    $seasonId = !empty($_GET['season_id']) ? (int)$_GET['season_id'] : null;
    $type = !empty($_GET['type']) && in_array($_GET['type'], ['league', 'cup'], true) ? $_GET['type'] : null;

    // Delegate to service for data access
    $service = new StatsService($clubId);
    $stats = $service->getOverviewStats($seasonId, $type);
    
    echo json_encode([
        'success' => true,
        'data' => $stats,
    ]);
} catch (\Throwable $e) {
    error_log('Stats API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch statistics',
    ]);
}
?>
