<?php
/**
 * Overview Statistics API Endpoint
 * 
 * GET /api/stats/overview
 * 
 * Returns aggregated club statistics (total matches, goals, wins/draws/losses, clean sheets, etc.)
 * This endpoint delegates to StatsService for data access and aggregation.
 * 
 * Data Flow: StatsService::getOverviewStats() → HTTP response as JSON
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

    // Delegate to service for data access
    $service = new StatsService($clubId);
    $stats = $service->getOverviewStats();
    
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
