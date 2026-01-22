<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/match_repository.php';

/**
 * Get overview statistics for the authenticated user's club
 * Returns aggregated match and goal statistics
 */
function stats_service_get_overview(?int $clubId = null): array
{
    $pdo = db();
    
    // Use the current user's club if not specified
    if ($clubId === null) {
        $user = current_user();
        $clubId = (int)($user['club_id'] ?? 0);
        if ($clubId <= 0) {
            return [
                'total_matches' => 0,
                'wins' => 0,
                'draws' => 0,
                'losses' => 0,
                'goals_for' => 0,
                'goals_against' => 0,
                'goal_difference' => 0,
                'clean_sheets' => 0,
                'average_goals_per_game' => 0,
            ];
        }
    }

    // Get total matches for the club
    $matchStmt = $pdo->prepare('
        SELECT COUNT(*) as total_matches
        FROM matches
        WHERE club_id = :club_id AND status = "ready"
    ');
    $matchStmt->execute(['club_id' => $clubId]);
    $matchResult = $matchStmt->fetch();
    $totalMatches = (int)($matchResult['total_matches'] ?? 0);

    if ($totalMatches === 0) {
        return [
            'total_matches' => 0,
            'wins' => 0,
            'draws' => 0,
            'losses' => 0,
            'goals_for' => 0,
            'goals_against' => 0,
            'goal_difference' => 0,
            'clean_sheets' => 0,
            'average_goals_per_game' => 0,
        ];
    }

    // Count goals scored by home team (our goals when playing at home)
    $homeGoalsStmt = $pdo->prepare('
        SELECT COUNT(*) as goals
        FROM events e
        JOIN matches m ON m.id = e.match_id
        WHERE m.club_id = :club_id 
        AND m.status = "ready"
        AND e.team_side = "home"
        AND e.event_type_id = (SELECT id FROM event_types WHERE type_key = "goal" LIMIT 1)
    ');
    $homeGoalsStmt->execute(['club_id' => $clubId]);
    $homeGoalsResult = $homeGoalsStmt->fetch();
    $homeGoals = (int)($homeGoalsResult['goals'] ?? 0);

    // Count goals scored by away team (our goals when playing away)
    $awayGoalsStmt = $pdo->prepare('
        SELECT COUNT(*) as goals
        FROM events e
        JOIN matches m ON m.id = e.match_id
        WHERE m.club_id = :club_id 
        AND m.status = "ready"
        AND e.team_side = "away"
        AND e.event_type_id = (SELECT id FROM event_types WHERE type_key = "goal" LIMIT 1)
    ');
    $awayGoalsStmt->execute(['club_id' => $clubId]);
    $awayGoalsResult = $awayGoalsStmt->fetch();
    $awayGoals = (int)($awayGoalsResult['goals'] ?? 0);

    // Total goals for (our team's goals)
    $goalsFor = $homeGoals + $awayGoals;

    // Count opponent goals (goals against us)
    // Home opponent goals (when we're home team, they're away team)
    $opponentHomeGoalsStmt = $pdo->prepare('
        SELECT COUNT(*) as goals
        FROM events e
        JOIN matches m ON m.id = e.match_id
        WHERE m.club_id = :club_id 
        AND m.status = "ready"
        AND e.team_side = "away"
        AND e.event_type_id = (SELECT id FROM event_types WHERE type_key = "goal" LIMIT 1)
    ');
    $opponentHomeGoalsStmt->execute(['club_id' => $clubId]);
    $opponentHomeGoalsResult = $opponentHomeGoalsStmt->fetch();
    $opponentHomeGoals = (int)($opponentHomeGoalsResult['goals'] ?? 0);

    // Away opponent goals (when we're away team, they're home team)
    $opponentAwayGoalsStmt = $pdo->prepare('
        SELECT COUNT(*) as goals
        FROM events e
        JOIN matches m ON m.id = e.match_id
        WHERE m.club_id = :club_id 
        AND m.status = "ready"
        AND e.team_side = "home"
        AND e.event_type_id = (SELECT id FROM event_types WHERE type_key = "goal" LIMIT 1)
    ');
    $opponentAwayGoalsStmt->execute(['club_id' => $clubId]);
    $opponentAwayGoalsResult = $opponentAwayGoalsStmt->fetch();
    $opponentAwayGoals = (int)($opponentAwayGoalsResult['goals'] ?? 0);

    // Total goals against
    $goalsAgainst = $opponentHomeGoals + $opponentAwayGoals;

    // Goal difference
    $goalDifference = $goalsFor - $goalsAgainst;

    // Calculate wins, draws, losses
    // For simplicity, we'll count matches with more goals for us as wins
    // This requires counting goal totals per match
    $resultStmt = $pdo->prepare('
        SELECT
            (SELECT COUNT(DISTINCT m.id)
             FROM matches m
             WHERE m.club_id = :club_id AND m.status = "ready"
             AND (SELECT SUM(CASE WHEN e.team_side = "home" THEN 1 ELSE 0 END) FROM events e 
                  WHERE e.match_id = m.id AND e.event_type_id = (SELECT id FROM event_types WHERE type_key = "goal" LIMIT 1))
                 > 
                 (SELECT SUM(CASE WHEN e.team_side = "away" THEN 1 ELSE 0 END) FROM events e 
                  WHERE e.match_id = m.id AND e.event_type_id = (SELECT id FROM event_types WHERE type_key = "goal" LIMIT 1))
            ) as wins,
            (SELECT COUNT(DISTINCT m.id)
             FROM matches m
             WHERE m.club_id = :club_id AND m.status = "ready"
             AND (SELECT COALESCE(SUM(CASE WHEN e.team_side = "home" THEN 1 ELSE 0 END), 0) FROM events e 
                  WHERE e.match_id = m.id AND e.event_type_id = (SELECT id FROM event_types WHERE type_key = "goal" LIMIT 1))
                 = 
                 (SELECT COALESCE(SUM(CASE WHEN e.team_side = "away" THEN 1 ELSE 0 END), 0) FROM events e 
                  WHERE e.match_id = m.id AND e.event_type_id = (SELECT id FROM event_types WHERE type_key = "goal" LIMIT 1))
            ) as draws
    ');
    $resultStmt->execute(['club_id' => $clubId]);
    $results = $resultStmt->fetch();
    
    $wins = (int)($results['wins'] ?? 0);
    $draws = (int)($results['draws'] ?? 0);
    $losses = $totalMatches - $wins - $draws;

    // Count clean sheets (matches where the club conceded 0 goals)
    // If the club's team is home, opponent goals are 'away' goals; if away, opponent goals are 'home' goals.
    $cleanSheetsStmt = $pdo->prepare('
        SELECT COUNT(DISTINCT m.id) as clean_sheets
        FROM matches m
        JOIN teams th ON th.id = m.home_team_id
        JOIN teams ta ON ta.id = m.away_team_id
        WHERE m.club_id = :club_id AND m.status = "ready"
        AND (
            (
                th.club_id = m.club_id AND
                (
                    SELECT COALESCE(SUM(CASE WHEN e.team_side = "away" THEN 1 ELSE 0 END), 0)
                    FROM events e
                    WHERE e.match_id = m.id
                      AND e.event_type_id = (
                          SELECT id FROM event_types et
                          WHERE et.type_key = "goal" AND et.club_id = m.club_id
                          LIMIT 1
                      )
                ) = 0
            )
            OR
            (
                ta.club_id = m.club_id AND
                (
                    SELECT COALESCE(SUM(CASE WHEN e.team_side = "home" THEN 1 ELSE 0 END), 0)
                    FROM events e
                    WHERE e.match_id = m.id
                      AND e.event_type_id = (
                          SELECT id FROM event_types et
                          WHERE et.type_key = "goal" AND et.club_id = m.club_id
                          LIMIT 1
                      )
                ) = 0
            )
        )
    ');
    $cleanSheetsStmt->execute(['club_id' => $clubId]);
    $cleanSheetsResult = $cleanSheetsStmt->fetch();
    $cleanSheets = (int)($cleanSheetsResult['clean_sheets'] ?? 0);

    // Average goals per game
    $avgGoalsPerGame = $totalMatches > 0 ? round($goalsFor / $totalMatches, 2) : 0;

    return [
        'total_matches' => $totalMatches,
        'wins' => $wins,
        'draws' => $draws,
        'losses' => $losses,
        'goals_for' => $goalsFor,
        'goals_against' => $goalsAgainst,
        'goal_difference' => $goalDifference,
        'clean_sheets' => $cleanSheets,
        'average_goals_per_game' => $avgGoalsPerGame,
    ];
}
