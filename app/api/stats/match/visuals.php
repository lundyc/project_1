<?php
/**
 * Match Visual Analytics API
 *
 * GET /api/stats/match/visuals?match_id={match_id}
 *
 * Returns visual analytics datasets for a single match (placeholder).
 */
require_auth();
require_once __DIR__ . '/../../../lib/StatsService.php';
require_once __DIR__ . '/../../../lib/stats_context.php';
require_once __DIR__ . '/../../../lib/match_repository.php';
require_once __DIR__ . '/../../../lib/match_permissions.php';

header('Content-Type: application/json');

try {
    $context = resolve_club_context_for_stats();
    $clubId = $context['club_id'];

    $matchId = (int)($_GET['match_id'] ?? 0);
    if ($matchId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing or invalid match ID']);
        exit;
    }

    $match = get_match($matchId);
    if (!$match) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Match not found']);
        exit;
    }

    $user = current_user();
    $roles = $_SESSION['roles'] ?? [];
    if (!can_view_match($user, $roles, (int)($match['club_id'] ?? 0)) || (int)($match['club_id'] ?? 0) !== $clubId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        exit;
    }

    $service = new StatsService($clubId);
    $visuals = $service->getVisualAnalyticsData($matchId);

    echo json_encode([
        'success' => true,
        'data' => [
            'match_id' => $matchId,
            'visuals' => $visuals,
        ],
    ]);
} catch (\Throwable $e) {
    error_log('Stats match visuals API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Unable to load match visual analytics']);
}
