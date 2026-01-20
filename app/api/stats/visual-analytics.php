<?php
/**
 * Player Performance Statistics API Endpoint
 * 
 * GET /api/stats/player-performance
 * 
 * PLACEHOLDER - Future Implementation
 * 
 * This endpoint will return:
 *   - Player appearance counts
 *   - Minutes played per player
 *   - Goals and assists breakdown
 *   - Yellow and red cards per player
 *   - Support for filtering and sorting
 * 
 * Data Flow: StatsService::getPlayerPerformanceStats() → HTTP response as JSON
 */
require_auth();
require_once __DIR__ . '/../../lib/StatsService.php';

header('Content-Type: application/json');

try {
    $user = current_user();
    $clubId = (int)($user['club_id'] ?? 0);
    
    if ($clubId <= 0) {
        http_response_code(403);
        echo json_encode(['error' => 'User does not belong to a club']);
        exit;
    }

    // Delegate to service
    $service = new StatsService($clubId);
    $stats = $service->getPlayerPerformanceStats();
    
    echo json_encode([
        'success' => true,
        'data' => $stats,
    ]);
} catch (\Throwable $e) {
    error_log('Stats Player Performance API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Player performance stats not yet available',
    ]);
}
?>
