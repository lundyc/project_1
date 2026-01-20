<?php
/**
 * Match Overview API
 *
 * GET /api/stats/match/overview?match_id={match_id}
 *
 * Returns aggregated event metrics for a single match with contextual metadata.
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
    $stats = $service->getMatchStats($matchId);

    $kickoffAt = null;
    if (!empty($match['kickoff_at'])) {
        try {
            $kickoffAt = new DateTime($match['kickoff_at']);
        } catch (Exception $e) {
            $kickoffAt = null;
        }
    }

    $matchMeta = [
        'id' => $matchId,
        'home_team' => $match['home_team'] ?? ($match['home_team_name'] ?? 'Home'),
        'away_team' => $match['away_team'] ?? ($match['away_team_name'] ?? 'Away'),
        'competition' => $match['competition'] ?? $match['competition_name'] ?? 'Competition',
        'status' => $match['status'] ?? 'Scheduled',
        'date' => $kickoffAt ? $kickoffAt->format('j M Y') : null,
        'time' => $kickoffAt ? $kickoffAt->format('H:i') : null,
    ];

    echo json_encode([
        'success' => true,
        'data' => [
            'match' => $matchMeta,
            'stats' => $stats,
        ],
    ]);
} catch (\Throwable $e) {
    error_log('Stats match overview API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Unable to load match overview statistics']);
}
