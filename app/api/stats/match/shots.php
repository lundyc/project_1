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
    $typeStmt = $pdo->query("SELECT id, type_key, label FROM event_types WHERE type_key IN ('shot', 'goal')");
    $typeRows = $typeStmt->fetchAll(PDO::FETCH_ASSOC);
    $typeIds = array_values(array_filter(array_map(static function ($row) {
        return isset($row['id']) ? (int)$row['id'] : null;
    }, $typeRows)));
    if (!$typeIds) {
        echo json_encode([
            'success' => true,
            'data' => [
                'shots' => [],
            ],
        ]);
        exit;
    }
    $placeholders = implode(',', array_fill(0, count($typeIds), '?'));

    $stmt = $pdo->prepare(
        "SELECT 
            e.id,
            e.team_side,
            e.match_second,
            e.shot_origin_x,
            e.shot_origin_y,
            e.shot_target_x,
            e.shot_target_y,
            et.type_key AS event_type_key,
            et.label AS event_type_label
        FROM events e
        JOIN event_types et ON et.id = e.event_type_id
        WHERE e.match_id = ?
            AND e.event_type_id IN ($placeholders)
            AND (e.shot_origin_x IS NOT NULL OR e.shot_target_x IS NOT NULL)
        ORDER BY e.match_second ASC"
    );
    $stmt->execute(array_merge([$matchId], $typeIds));
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
