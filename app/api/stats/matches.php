<?php
/**
 * Matches List API Endpoint for Stats Dashboard
 * 
 * GET /api/stats/matches?season_id=X&type=league|cup
 * 
 * Returns filtered list of matches for the statistics dashboard.
 * Supports filtering by season and competition type.
 * 
 * Query Parameters:
 *   - season_id: Filter by season ID (optional)
 *   - type: Filter by competition type (league|cup, optional)
 */
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/db.php';
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

    $pdo = db();
    
    // Build WHERE clause with filters
    $where = ['m.club_id = :club_id', 'm.status = "ready"'];
    $params = ['club_id' => $clubId];
    
    if ($seasonId !== null) {
        $where[] = 'm.season_id = :season_id';
        $params['season_id'] = $seasonId;
    }
    
    if ($type !== null) {
        $where[] = 'c.type = :type';
        $params['type'] = $type;
    }
    
    $whereClause = implode(' AND ', $where);
    
    // Get goal event type ID
    $goalStmt = $pdo->prepare('SELECT id FROM event_types WHERE type_key = "goal" LIMIT 1');
    $goalStmt->execute();
    $goalTypeId = (int)$goalStmt->fetchColumn();
    
    // Fetch matches with filters
    $sql = '
        SELECT 
            m.id,
            m.kickoff_at,
            m.status,
            COALESCE(ht.name, "Home") AS home_team,
            COALESCE(at.name, "Away") AS away_team,
            COALESCE(c.name, "") AS competition,
                        COALESCE((
                                SELECT COUNT(*) FROM events e
                                WHERE e.match_id = m.id
                                    AND e.team_side = "home"
                                    AND e.event_type_id = :goal_type_id_home
                        ), 0) AS home_goals,
                        COALESCE((
                                SELECT COUNT(*) FROM events e
                                WHERE e.match_id = m.id
                                    AND e.team_side = "away"
                                    AND e.event_type_id = :goal_type_id_away
                        ), 0) AS away_goals
        FROM matches m
        LEFT JOIN teams ht ON ht.id = m.home_team_id
        LEFT JOIN teams at ON at.id = m.away_team_id
        LEFT JOIN competitions c ON c.id = m.competition_id
        WHERE ' . $whereClause . '
        ORDER BY m.kickoff_at DESC, m.id DESC
        LIMIT 100
    ';
    
    $params['goal_type_id_home'] = $goalTypeId;
    $params['goal_type_id_away'] = $goalTypeId;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'matches' => $matches,
    ]);
} catch (\Throwable $e) {
    error_log('Stats Matches API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch matches',
    ]);
}
?>
