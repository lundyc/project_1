<?php
/**
 * Match Shots API
 *
 * GET /api/stats/match/shots?match_id={match_id}
 *
 * Returns all shots with coordinate data for a match.
 */
require_once __DIR__ . '/../../../lib/auth.php';
require_once __DIR__ . '/../../../lib/db.php';
require_once __DIR__ . '/../../../lib/match_repository.php';
require_once __DIR__ . '/../../../lib/match_permissions.php';
require_once __DIR__ . '/../../../lib/stats_context.php';

require_auth();

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

    $pdo = db();
    $typeStmt = $pdo->query("SELECT id FROM event_types WHERE type_key = 'shot' LIMIT 1");
    $shotTypeId = (int)$typeStmt->fetchColumn();

    $stmt = $pdo->prepare('
        SELECT 
            id,
            team_side,
            match_second,
            shot_origin_x,
            shot_origin_y,
            shot_target_x,
            shot_target_y
        FROM events
        WHERE match_id = :match_id
            AND event_type_id = :shot_type_id
            AND (shot_origin_x IS NOT NULL OR shot_target_x IS NOT NULL)
        ORDER BY match_second ASC
    ');
    $stmt->execute(['match_id' => $matchId, 'shot_type_id' => $shotTypeId]);
    $shots = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => [
            'shots' => $shots,
        ],
    ]);
} catch (\Throwable $e) {
    error_log('Stats match shots API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Unable to load match shots']);
}
?>
