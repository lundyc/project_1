<?php
/**
 * Team Performance API Endpoint
 *
 * GET /api/stats/team-performance
 *
 * Returns derived performance data for the selected club (matches, record, form, home/away splits).
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

    $service = new StatsService($clubId);
    $stats = $service->getTeamPerformanceStats();

    echo json_encode([
        'success' => true,
        'data' => $stats,
    ]);
} catch (\Throwable $e) {
    error_log('Stats Team Performance API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch team performance',
    ]);
}
