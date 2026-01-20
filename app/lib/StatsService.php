<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/match_repository.php';

/**
 * StatsService - Centralized service for all statistics data access
 * 
 * This service handles all aggregation and computation of statistics from the database.
 * It provides a clean API for controllers and endpoints to use without directly querying the database.
 * 
 * Data Flow:
 *   Database (matches, events, event_types)
 *     → StatsService methods (aggregation logic)
 *       → API endpoints (HTTP layer)
 *         → Frontend JavaScript (presentation layer)
 * 
 * Adding New Stats:
 *   1. Add a new public method to this service
 *   2. Create corresponding API endpoint that calls the service method
 *   3. Update the controller/view to display the data
 *   4. No need to modify existing methods or database schema
 * 
 * @package Analytics\Stats
 */
class StatsService
{
    private $pdo;
    private $clubId;
    private $primaryTeamId;

    /**
     * Initialize the stats service with a club context
     * 
     * @param int $clubId The club ID for which to fetch statistics
     */
    public function __construct(?int $clubId = null)
    {
        $this->pdo = db();
        
        if ($clubId === null) {
            $user = current_user();
            $clubId = (int)($user['club_id'] ?? 0);
        }
        
        if ($clubId <= 0) {
            throw new \Exception('Invalid or missing club ID');
        }
        
        $this->clubId = $clubId;
        $this->primaryTeamId = $this->determinePrimaryTeam();
    }

    /**
     * Determine the primary team for statistics tracking
     * 
     * When multiple teams exist under the same club_id (e.g., Saltcoats, Rossvale),
     * we need to identify which team's stats should be tracked. This method finds
     * the team that has played the most matches in the competition.
     * 
     * @return int|null The team ID of the primary team, or null if no team found
     */
    private function determinePrimaryTeam(): ?int
    {
        // First, try to find a team with matches
        $stmt = $this->pdo->prepare('
            SELECT 
                t.id,
                t.name,
                COUNT(m.id) as match_count
            FROM teams t
            LEFT JOIN matches m ON (m.home_team_id = t.id OR m.away_team_id = t.id) 
                AND m.club_id = :club_id 
                AND m.status = "ready"
            WHERE t.club_id = :club_id 
            AND t.team_type = "club"
            GROUP BY t.id, t.name
            ORDER BY match_count DESC
            LIMIT 1
        ');
        $stmt->execute(['club_id' => $this->clubId]);
        $result = $stmt->fetch();
        
        if ($result && !empty($result['id'])) {
            return (int)$result['id'];
        }
        
        // If no team with matches found, try to find any team for this club
        $stmt = $this->pdo->prepare('
            SELECT id 
            FROM teams 
            WHERE club_id = :club_id 
            AND team_type = "club"
            ORDER BY id ASC
            LIMIT 1
        ');
        $stmt->execute(['club_id' => $this->clubId]);
        $result = $stmt->fetch();
        
        if ($result && !empty($result['id'])) {
            return (int)$result['id'];
        }
        
        // No team found at all - return null to indicate no data
        return null;
    }

    /**
     * Check if the service has valid team data to work with
     * 
     * @return bool True if a primary team exists, false otherwise
     */
    private function hasValidTeam(): bool
    {
        return $this->primaryTeamId !== null;
    }

    /**
     * Get overview statistics for the club
     * 
     * Aggregates match and goal statistics across all matches.
     * Used by the Overview tab to show high-level performance metrics.
     * 
     * @return array Associative array with keys: total_matches, wins, draws, losses, 
     *               goals_for, goals_against, goal_difference, clean_sheets, average_goals_per_game
     */
    public function getOverviewStats(?int $seasonId = null, ?string $type = null): array
    {
        // Return empty stats if no team is configured
        if (!$this->hasValidTeam()) {
            return $this->getEmptyOverviewStats();
        }

        // Build filtering query
        $where = ['m.club_id = :club_id', 'm.status = "ready"'];
        $params = ['club_id' => $this->clubId];
        
        if ($seasonId !== null) {
            $where[] = 'm.season_id = :season_id';
            $params['season_id'] = $seasonId;
        }
        
        if ($type !== null) {
            $where[] = 'c.type = :type';
            $params['type'] = $type;
        }
        
        $whereClause = implode(' AND ', $where);

        // Get total matches for the club
        $matchStmt = $this->pdo->prepare('
            SELECT COUNT(*) as total_matches
            FROM matches m
            LEFT JOIN competitions c ON c.id = m.competition_id
            WHERE ' . $whereClause
        );
        $matchStmt->execute($params);
        $matchResult = $matchStmt->fetch();
        $totalMatches = (int)($matchResult['total_matches'] ?? 0);

        if ($totalMatches === 0) {
            return $this->getEmptyOverviewStats();
        }

        // Count goals scored by our club's teams
        $goalsFor = $this->countGoalsForClub($seasonId, $type);

        // Count goals scored against our club (by opponent teams)
        $goalsAgainst = $this->countGoalsAgainstClub($seasonId, $type);

        // Goal difference
        $goalDifference = $goalsFor - $goalsAgainst;

        // Calculate wins, draws, losses
        $results = $this->computeMatchResults($seasonId, $type);
        $wins = (int)($results['wins'] ?? 0);
        $draws = (int)($results['draws'] ?? 0);
        $losses = $totalMatches - $wins - $draws;

        // Count clean sheets
        $cleanSheets = $this->countCleanSheets($seasonId, $type);

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

    /**
     * Get list of matches for the club
     * 
     * Returns formatted match list sorted by kickoff time (newest first).
     * Used to populate the match selector dropdown.
     * 
     * @param int $limit Maximum number of matches to return (default: 100)
     * @return array Array of match objects with: id, date, time, home_team, away_team, competition
     */
    public function getMatchList(int $limit = 100): array
    {
        $goalTypeId = $this->getGoalEventTypeId();
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
                      AND e.event_type_id = :goal_type_id
                ), 0) AS home_goals,
                COALESCE((
                    SELECT COUNT(*) FROM events e
                    WHERE e.match_id = m.id
                      AND e.team_side = "away"
                      AND e.event_type_id = :goal_type_id
                ), 0) AS away_goals
            FROM matches m
            LEFT JOIN teams ht ON ht.id = m.home_team_id
            LEFT JOIN teams at ON at.id = m.away_team_id
            LEFT JOIN competitions c ON c.id = m.competition_id
            WHERE m.club_id = :club_id AND m.status = "ready"
            ORDER BY m.kickoff_at DESC, m.id DESC
            LIMIT :limit
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':club_id', $this->clubId, \PDO::PARAM_INT);
        $stmt->bindValue(':goal_type_id', $goalTypeId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        $matches = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Format matches for display
        $formattedMatches = [];
        foreach ($matches as $match) {
            $kickoffTs = $match['kickoff_at'] ? strtotime($match['kickoff_at']) : null;
            $dateLabel = $kickoffTs ? date('M j, Y', $kickoffTs) : 'TBD';
            $timeLabel = $kickoffTs ? date('H:i', $kickoffTs) : 'TBD';

            $formattedMatches[] = [
                'id' => (int)$match['id'],
                'date' => $dateLabel,
                'time' => $timeLabel,
                'home_team' => trim($match['home_team'] ?? ''),
                'away_team' => trim($match['away_team'] ?? ''),
                'home_goals' => (int)($match['home_goals'] ?? 0),
                'away_goals' => (int)($match['away_goals'] ?? 0),
                'competition' => trim($match['competition'] ?? ''),
                'kickoff_at' => $match['kickoff_at'],
                'kickoff_timestamp' => $kickoffTs,
                'status' => $match['status'] ?? 'draft',
            ];
        }

        return $formattedMatches;
    }

    /**
     * Get detailed match statistics
     * 
     * Aggregates event statistics for a specific match, broken down by team side.
     * Used by the Match Stats tab to show event-level breakdown.
     * 
     * @param int $matchId The match ID to fetch statistics for
     * @return array Array with 'home' and 'away' keys, each containing event counts
     * @throws \Exception If match not found or doesn't belong to this club
     */
    public function getMatchStats(int $matchId): array
    {
        // Verify match belongs to this club
        $matchStmt = $this->pdo->prepare('
            SELECT 
                m.id, 
                COALESCE(ht.name, "Home") AS home_team, 
                COALESCE(at.name, "Away") AS away_team 
            FROM matches m
            LEFT JOIN teams ht ON ht.id = m.home_team_id
            LEFT JOIN teams at ON at.id = m.away_team_id
            WHERE m.id = :id AND m.club_id = :club_id
        ');
        $matchStmt->execute(['id' => $matchId, 'club_id' => $this->clubId]);
        $match = $matchStmt->fetch();
        
        if (!$match) {
            throw new \Exception('Match not found');
        }

        // Get event type mapping
        $typeKeyMap = $this->getEventTypeMapping();

        // Count events by type and team side
        $eventStmt = $this->pdo->prepare('
            SELECT 
                e.event_type_id,
                e.team_side,
                COUNT(*) as count
            FROM events e
            WHERE e.match_id = :match_id
            GROUP BY e.event_type_id, e.team_side
        ');
        $eventStmt->execute(['match_id' => $matchId]);
        $events = $eventStmt->fetchAll();

        // Initialize stats structure
        $stats = [
            'home' => $this->getEmptyMatchStats(trim($match['home_team'] ?? 'Home Team')),
            'away' => $this->getEmptyMatchStats(trim($match['away_team'] ?? 'Away Team')),
        ];

        // Populate stats from events
        foreach ($events as $event) {
            $typeId = $event['event_type_id'];
            $side = $event['team_side'];
            $count = (int)$event['count'];

            if (!in_array($side, ['home', 'away'])) {
                continue;
            }

            // Find event type key
            $typeKey = null;
            foreach ($typeKeyMap as $key => $id) {
                if ($id === $typeId) {
                    $typeKey = $key;
                    break;
                }
            }

            // Map to stat field
            $this->updateMatchStatByType($stats[$side], $typeKey, $count);
        }

        return $stats;
    }


    /**
     * PLACEHOLDER: Get player performance statistics
     * 
     * Future implementation will include:
     *   - Player appearance counts
     *   - Minutes played
     *   - Goals and assists
     *   - Yellow and red cards
     *   - Filtering and sorting options
     * 
     * @return array Player performance data structure
     */
    /**
     * Get player performance statistics for the club (all matches)
     * 
     * Aggregates player statistics across all matches for the club.
     * Returns appearance counts, goals, cards, and estimated minutes played.
     * 
     * @return array Array of player performance data
     */
    public function getPlayerPerformanceForClub(?int $seasonId = null, ?string $type = null): array
    {
        // Get event type IDs
        $goalTypeId = $this->getGoalEventTypeId();
        $yellowCardTypeId = $this->getEventTypeIdByKey('yellow_card');
        $redCardTypeId = $this->getEventTypeIdByKey('red_card');
        
        $where = ['m.club_id = :club_id', 'm.status = "ready"', 'p.club_id = :club_id'];
        $params = ['club_id' => $this->clubId];
        
        if ($seasonId !== null) {
            $where[] = 'm.season_id = :season_id';
            $params['season_id'] = $seasonId;
        }
        if ($type !== null) {
            $where[] = 'c.type = :type';
            $params['type'] = $type;
        }
        
        $whereClause = implode(' AND ', $where);

                // Get all match_players for the club's matches, grouped by player (minutes computed below)
                $sql = '
                        SELECT 
                                p.id as player_id,
                                p.display_name as name,
                                p.primary_position as position,
                                COUNT(DISTINCT mp.match_id) as appearances,
                                SUM(CASE WHEN mp.is_starting = 1 THEN 1 ELSE 0 END) as starts,
                                SUM(CASE WHEN mp.is_starting = 0 THEN 1 ELSE 0 END) as sub_appearances
                        FROM players p
                        INNER JOIN match_players mp ON mp.player_id = p.id
                        INNER JOIN matches m ON m.id = mp.match_id
                        LEFT JOIN competitions c ON c.id = m.competition_id
                        WHERE ' . $whereClause . '
                        GROUP BY p.id, p.display_name, p.primary_position
                ';
        
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
                $players = $stmt->fetchAll(\PDO::FETCH_ASSOC);

                // Compute minutes using substitutions (fallback: starters 90, unused subs 0, subs that came on get actual minutes)
                $minutesByPlayer = $this->computeMinutesPlayedForClub($seasonId, $type);

        // Now get event stats for each player
        // Note: Events can have player_id directly OR via match_player_id
        $eventSql = '
            SELECT 
                COALESCE(e.player_id, mp.player_id) as player_id,
                e.event_type_id,
                COUNT(*) as event_count
            FROM events e
            INNER JOIN matches m ON m.id = e.match_id
            LEFT JOIN match_players mp ON mp.id = e.match_player_id
            LEFT JOIN competitions c ON c.id = m.competition_id
            WHERE m.club_id = :club_id 
              AND m.status = "ready"
              AND (e.player_id IS NOT NULL OR mp.player_id IS NOT NULL)
              AND e.event_type_id IN (:goal_id, :yellow_id, :red_id)' .
              ($seasonId !== null ? ' AND m.season_id = :season_id' : '') .
              ($type !== null ? ' AND c.type = :type' : '') . '
            GROUP BY COALESCE(e.player_id, mp.player_id), e.event_type_id
        ';
        
        $eventParams = [
            'club_id' => $this->clubId,
            'goal_id' => $goalTypeId,
            'yellow_id' => $yellowCardTypeId,
            'red_id' => $redCardTypeId,
        ];
        if ($seasonId !== null) {
            $eventParams['season_id'] = $seasonId;
        }
        if ($type !== null) {
            $eventParams['type'] = $type;
        }
        
        $eventStmt = $this->pdo->prepare($eventSql);
        $eventStmt->execute($eventParams);
        $events = $eventStmt->fetchAll(\PDO::FETCH_ASSOC);

        // Build event map by player
        $eventsByPlayer = [];
        foreach ($events as $evt) {
            $playerId = (int)$evt['player_id'];
            $typeId = (int)$evt['event_type_id'];
            $count = (int)$evt['event_count'];
            
            if (!isset($eventsByPlayer[$playerId])) {
                $eventsByPlayer[$playerId] = [
                    'goals' => 0,
                    'yellow_cards' => 0,
                    'red_cards' => 0,
                ];
            }
            
            if ($typeId === $goalTypeId) {
                $eventsByPlayer[$playerId]['goals'] = $count;
            } elseif ($typeId === $yellowCardTypeId) {
                $eventsByPlayer[$playerId]['yellow_cards'] = $count;
            } elseif ($typeId === $redCardTypeId) {
                $eventsByPlayer[$playerId]['red_cards'] = $count;
            }
        }

        // Merge event data into player records
        $result = [];
        foreach ($players as $player) {
            $playerId = (int)$player['player_id'];
            $events = $eventsByPlayer[$playerId] ?? ['goals' => 0, 'yellow_cards' => 0, 'red_cards' => 0];
            
            $result[] = [
                'player_id' => $playerId,
                'name' => trim($player['name'] ?? ''),
                'position' => trim($player['position'] ?? 'N/A'),
                'appearances' => (int)($player['appearances'] ?? 0),
                'starts' => (int)($player['starts'] ?? 0),
                'sub_appearances' => (int)($player['sub_appearances'] ?? 0),
                'goals' => $events['goals'],
                'assists' => 0, // Placeholder: assists not yet tracked
                'yellow_cards' => $events['yellow_cards'],
                'red_cards' => $events['red_cards'],
                // Minutes: starters full length unless subbed off; subs get time after coming on; unused subs = 0
                'minutes_played' => (int)($minutesByPlayer[$playerId] ?? 0),
            ];
        }

        // Sort by appearances (descending), then by goals
        usort($result, function($a, $b) {
            if ($a['appearances'] !== $b['appearances']) {
                return $b['appearances'] - $a['appearances'];
            }
            return $b['goals'] - $a['goals'];
        });

        return $result;
    }

    /**
     * Compute minutes played for all players in club matches using substitutions
     *
     * Rules:
     *  - Starters: full match length unless subbed off
     *  - Subs: minutes from time they come on; unused subs = 0
     *  - Match length is assumed 90 minutes (no stoppage-time granularity tracked)
     *
     * @return array player_id => minutes_played
     */
    private function computeMinutesPlayedForClub(?int $seasonId = null, ?string $type = null): array
    {
        $matchLength = 90;
        
        $where = ['m.club_id = :club_id', 'm.status = "ready"'];
        $params = ['club_id' => $this->clubId];
        
        if ($seasonId !== null) {
            $where[] = 'm.season_id = :season_id';
            $params['season_id'] = $seasonId;
        }
        if ($type !== null) {
            $where[] = 'c.type = :type';
            $params['type'] = $type;
        }
        
        $whereClause = implode(' AND ', $where);

        // Collect ready match IDs for this club
        $matchStmt = $this->pdo->prepare('
            SELECT m.id FROM matches m
            LEFT JOIN competitions c ON c.id = m.competition_id
            WHERE ' . $whereClause
        );
        $matchStmt->execute($params);
        $matchIds = array_map('intval', $matchStmt->fetchAll(\PDO::FETCH_COLUMN));
        if (empty($matchIds)) {
            return [];
        }

        // Fetch match_players for those matches
        $mpStmt = $this->pdo->prepare('
            SELECT mp.id as match_player_id, mp.match_id, mp.player_id, mp.is_starting
            FROM match_players mp
            INNER JOIN matches m ON m.id = mp.match_id
            LEFT JOIN competitions c ON c.id = m.competition_id
            WHERE ' . $whereClause
        );
        $mpStmt->execute($params);
        $matchPlayers = $mpStmt->fetchAll(\PDO::FETCH_ASSOC);

        // Group match players by match and map match_player_id → player_id
        $playersByMatch = [];
        $playerIdByMatchPlayerId = [];
        foreach ($matchPlayers as $mp) {
            $matchId = (int)$mp['match_id'];
            $playersByMatch[$matchId][] = $mp;
            $playerIdByMatchPlayerId[(int)$mp['match_player_id']] = (int)$mp['player_id'];
        }

        // Fetch substitutions for those matches
        $placeholders = implode(',', array_fill(0, count($matchIds), '?'));
        $subsStmt = $this->pdo->prepare("\n            SELECT match_id, player_off_match_player_id, player_on_match_player_id, minute, minute_extra\n            FROM match_substitutions\n            WHERE match_id IN ($placeholders)\n        ");
        $subsStmt->execute($matchIds);
        $subs = $subsStmt->fetchAll(\PDO::FETCH_ASSOC);

        // Organize substitutions per match
        $subsByMatch = [];
        foreach ($subs as $sub) {
            $matchId = (int)$sub['match_id'];
            $subsByMatch[$matchId][] = $sub;
        }

        // Compute minutes per player per match, then aggregate
        $minutesByPlayer = [];

        foreach ($playersByMatch as $matchId => $matchPlayerRows) {
            $onMap = [];
            $offMap = [];

            // Build on/off maps in minutes
            foreach ($subsByMatch[$matchId] ?? [] as $sub) {
                $minute = (int)$sub['minute'] + (int)$sub['minute_extra'];
                if (!empty($sub['player_on_match_player_id'])) {
                    $onMap[(int)$sub['player_on_match_player_id']] = $minute;
                }
                if (!empty($sub['player_off_match_player_id'])) {
                    $offMap[(int)$sub['player_off_match_player_id']] = $minute;
                }
            }

            foreach ($matchPlayerRows as $mp) {
                $matchPlayerId = (int)$mp['match_player_id'];
                $playerId = (int)$mp['player_id'];
                $isStarting = (int)$mp['is_starting'] === 1;

                $minutes = 0;
                if ($isStarting) {
                    // Starter: full length unless subbed off
                    $minutes = $matchLength;
                    if (isset($offMap[$matchPlayerId])) {
                        $minutes = max(0, min($matchLength, $offMap[$matchPlayerId]));
                    }
                } else {
                    // Sub: only minutes after coming on; unused subs = 0
                    if (isset($onMap[$matchPlayerId])) {
                        $minutes = max(0, $matchLength - $onMap[$matchPlayerId]);
                    }
                }

                if (!isset($minutesByPlayer[$playerId])) {
                    $minutesByPlayer[$playerId] = 0;
                }
                $minutesByPlayer[$playerId] += $minutes;
            }
        }

        return $minutesByPlayer;
    }

    /**
     * Get player performance statistics for a single match
     * 
     * Returns player data grouped by starting XI and substitutes.
     * Includes goals and cards for the match.
     * 
     * @param int $matchId The match ID
     * @return array Array with 'starting_xi' and 'substitutes' keys
     */
    public function getPlayerPerformanceForMatch(int $matchId): array
    {
        // Verify match belongs to this club
        $matchStmt = $this->pdo->prepare('
            SELECT id FROM matches WHERE id = :id AND club_id = :club_id
        ');
        $matchStmt->execute(['id' => $matchId, 'club_id' => $this->clubId]);
        if (!$matchStmt->fetch()) {
            throw new \Exception('Match not found or access denied');
        }

        // Get event type IDs
        $goalTypeId = $this->getGoalEventTypeId();
        $yellowCardTypeId = $this->getEventTypeIdByKey('yellow_card');
        $redCardTypeId = $this->getEventTypeIdByKey('red_card');

        // Get all match_players for this match, with fallback to players table for names
        $sql = '
            SELECT 
                mp.player_id,
                COALESCE(NULLIF(mp.display_name, ""), p.display_name, "Unknown") as name,
                mp.shirt_number,
                COALESCE(mp.position_label, p.primary_position, "N/A") as position,
                mp.is_starting,
                mp.is_captain
            FROM match_players mp
            LEFT JOIN players p ON p.id = mp.player_id
            WHERE mp.match_id = :match_id
            ORDER BY mp.is_starting DESC, mp.shirt_number ASC
        ';
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['match_id' => $matchId]);
        $players = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Get event stats for these players in this match
        // Note: Events can have player_id directly OR via match_player_id
        $eventSql = '
            SELECT 
                COALESCE(e.player_id, mp.player_id) as player_id,
                e.event_type_id,
                COUNT(*) as event_count
            FROM events e
            LEFT JOIN match_players mp ON mp.id = e.match_player_id
            WHERE e.match_id = :match_id
              AND (e.player_id IS NOT NULL OR mp.player_id IS NOT NULL)
              AND e.event_type_id IN (:goal_id, :yellow_id, :red_id)
            GROUP BY COALESCE(e.player_id, mp.player_id), e.event_type_id
        ';
        
        $eventStmt = $this->pdo->prepare($eventSql);
        $eventStmt->execute([
            'match_id' => $matchId,
            'goal_id' => $goalTypeId,
            'yellow_id' => $yellowCardTypeId,
            'red_id' => $redCardTypeId,
        ]);
        $events = $eventStmt->fetchAll(\PDO::FETCH_ASSOC);

        // Build event map by player
        $eventsByPlayer = [];
        foreach ($events as $evt) {
            $playerId = (int)$evt['player_id'];
            $typeId = (int)$evt['event_type_id'];
            $count = (int)$evt['event_count'];
            
            if (!isset($eventsByPlayer[$playerId])) {
                $eventsByPlayer[$playerId] = [
                    'goals' => 0,
                    'yellow_cards' => 0,
                    'red_cards' => 0,
                ];
            }
            
            if ($typeId === $goalTypeId) {
                $eventsByPlayer[$playerId]['goals'] = $count;
            } elseif ($typeId === $yellowCardTypeId) {
                $eventsByPlayer[$playerId]['yellow_cards'] = $count;
            } elseif ($typeId === $redCardTypeId) {
                $eventsByPlayer[$playerId]['red_cards'] = $count;
            }
        }

        // Separate into starting XI and substitutes
        $startingXI = [];
        $substitutes = [];
        
        foreach ($players as $player) {
            $playerId = (int)($player['player_id'] ?? 0);
            $events = $eventsByPlayer[$playerId] ?? ['goals' => 0, 'yellow_cards' => 0, 'red_cards' => 0];
            
            $playerData = [
                'player_id' => $playerId,
                'name' => trim($player['name'] ?? ''),
                'shirt_number' => (int)($player['shirt_number'] ?? 0),
                'position' => trim($player['position'] ?? 'N/A'),
                'goals' => $events['goals'],
                'yellow_cards' => $events['yellow_cards'],
                'red_cards' => $events['red_cards'],
                'is_captain' => (bool)($player['is_captain'] ?? false),
            ];
            
            if ((int)($player['is_starting'] ?? 0) === 1) {
                $startingXI[] = $playerData;
            } else {
                $substitutes[] = $playerData;
            }
        }

        return [
            'starting_xi' => $startingXI,
            'substitutes' => $substitutes,
        ];
    }

    /**
     * DEPRECATED: Legacy method for backward compatibility
     * Use getPlayerPerformanceForClub() or getPlayerPerformanceForMatch() instead
     */
    public function getPlayerPerformanceStats(): array
    {
        return $this->getPlayerPerformanceForClub();
    }

    /**
     * PLACEHOLDER: Get visual analytics data
     * 
     * Future implementation will include:
     *   - Shot maps
     *   - Heat maps
     *   - Possession zones
     *   - Pass networks
     * 
     * @param int $matchId Optional match ID for match-specific visuals
     * @return array Visual analytics data structure
     */
    public function getVisualAnalyticsData(?int $matchId = null): array
    {
        // TODO: Implement visual analytics data aggregation
        return [
            'shot_map' => [],
            'heat_map' => [],
            'possession' => [],
            'pass_network' => [],
            'status' => 'placeholder',
        ];
    }

    // ==================== Private Helper Methods ====================

    /**
     * Count goals scored by our primary team (across all matches)
     * 
     * This correctly identifies goals scored by the primary team only.
     * When multiple club teams exist (e.g., Saltcoats, Rossvale), only goals
     * by the primary team (determined by match count) are counted.
     * 
     * @return int Total goals scored by our primary team
     */
    private function countGoalsForClub(?int $seasonId = null, ?string $type = null): int
    {
        if (!$this->hasValidTeam()) {
            return 0;
        }
        
        $where = ['m.club_id = :club_id', 'm.status = "ready"'];
        $params = ['club_id' => $this->clubId, 'team_id' => $this->primaryTeamId];
        
        if ($seasonId !== null) {
            $where[] = 'm.season_id = :season_id';
            $params['season_id'] = $seasonId;
        }
        if ($type !== null) {
            $where[] = 'c.type = :type';
            $params['type'] = $type;
        }
        
        $whereClause = implode(' AND ', $where);
        
        $stmt = $this->pdo->prepare('
            SELECT COUNT(*) as goals
            FROM events e
            JOIN matches m ON m.id = e.match_id
            LEFT JOIN competitions c ON c.id = m.competition_id
            JOIN teams scoring_team ON scoring_team.id = CASE 
                WHEN e.team_side = "home" THEN m.home_team_id
                WHEN e.team_side = "away" THEN m.away_team_id
            END
            WHERE ' . $whereClause . '
            AND scoring_team.id = :team_id
            AND e.event_type_id = (
                SELECT id FROM event_types 
                WHERE type_key = "goal" 
                LIMIT 1
            )
        ');
        $stmt->execute($params);
        $result = $stmt->fetch();
        return (int)($result['goals'] ?? 0);
    }

    /**
     * Count goals scored against our primary team (by all opponents)
     * 
     * This counts goals scored by ANY team that is not the primary team.
     * Includes opponent teams AND other club teams (e.g., Rossvale vs Saltcoats).
     * 
     * @return int Total goals conceded by our primary team
     */
    private function countGoalsAgainstClub(?int $seasonId = null, ?string $type = null): int
    {
        if (!$this->hasValidTeam()) {
            return 0;
        }
        
        $where = ['m.club_id = :club_id', "m.status = 'ready'"];
        $params = ['club_id' => $this->clubId, 'team_id' => $this->primaryTeamId];
        
        if ($seasonId !== null) {
            $where[] = 'm.season_id = :season_id';
            $params['season_id'] = $seasonId;
        }
        if ($type !== null) {
            $where[] = 'c.type = :type';
            $params['type'] = $type;
        }
        
        $whereClause = implode(' AND ', $where);
        
        $stmt = $this->pdo->prepare('
            SELECT COUNT(*) as goals
            FROM events e
            JOIN matches m ON m.id = e.match_id
            LEFT JOIN competitions c ON c.id = m.competition_id
            JOIN teams scoring_team ON scoring_team.id = CASE 
                WHEN e.team_side = "home" THEN m.home_team_id
                WHEN e.team_side = "away" THEN m.away_team_id
            END
            WHERE ' . $whereClause . '
            AND scoring_team.id != :team_id
            AND e.event_type_id = (
                SELECT id FROM event_types 
                WHERE type_key = "goal" 
                LIMIT 1
            )
        ');
        $stmt->execute($params);
        $result = $stmt->fetch();
        return (int)($result['goals'] ?? 0);
    }

    /**
     * Compute match results (wins, draws, losses)
     * 
     * Uses goal totals per match to determine outcome.
     * Correctly identifies the primary team by team_id.
     * 
     * @return array Array with 'wins' and 'draws' keys
     */
    private function computeMatchResults(?int $seasonId = null, ?string $type = null): array
    {
        if (!$this->hasValidTeam()) {
            return ['wins' => 0, 'draws' => 0];
        }
        
        $where = ['m.club_id = :club_id', "m.status = 'ready'"];
        $params = ['club_id' => $this->clubId];
        
        if ($seasonId !== null) {
            $where[] = 'm.season_id = :season_id';
            $params['season_id'] = $seasonId;
        }
        if ($type !== null) {
            $where[] = 'c.type = :type';
            $params['type'] = $type;
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Get all matches for this club with filters
        $stmt = $this->pdo->prepare('
            SELECT 
                m.id,
                m.home_team_id,
                m.away_team_id,
                (SELECT COALESCE(COUNT(*), 0)
                 FROM events e 
                 WHERE e.match_id = m.id 
                 AND e.team_side = "home"
                 AND e.event_type_id = (SELECT id FROM event_types WHERE type_key = "goal" LIMIT 1)
                ) as home_goals,
                (SELECT COALESCE(COUNT(*), 0)
                 FROM events e 
                 WHERE e.match_id = m.id 
                 AND e.team_side = "away"
                 AND e.event_type_id = (SELECT id FROM event_types WHERE type_key = "goal" LIMIT 1)
                ) as away_goals
            FROM matches m
            LEFT JOIN competitions c ON c.id = m.competition_id
            WHERE ' . $whereClause
        );
        $stmt->execute($params);
        $matches = $stmt->fetchAll();
        
        $wins = 0;
        $draws = 0;
        
        foreach ($matches as $match) {
            $homeGoals = (int)$match['home_goals'];
            $awayGoals = (int)$match['away_goals'];
            $homeTeamId = (int)$match['home_team_id'];
            $awayTeamId = (int)$match['away_team_id'];
            
            // Determine which team is our primary team
            $isHomeClub = ($homeTeamId === $this->primaryTeamId);
            $isAwayClub = ($awayTeamId === $this->primaryTeamId);
            
            if ($homeGoals === $awayGoals) {
                $draws++;
            } elseif ($isHomeClub && $homeGoals > $awayGoals) {
                // Our primary team is home and won
                $wins++;
            } elseif ($isAwayClub && $awayGoals > $homeGoals) {
                // Our primary team is away and won
                $wins++;
            }
        }
        
        return ['wins' => $wins, 'draws' => $draws];
    }

    /**
     * Count clean sheets (matches where primary team conceded 0 goals)
     * 
     * A clean sheet occurs when our primary team keeps the opponent from scoring,
     * regardless of how many goals we scored. This correctly handles:
     * - 0-0 draws (both teams get clean sheet)
     * - Wins like 4-0, 2-0, etc. (only winning team gets clean sheet)
     * 
     * @return int Clean sheet count for our primary team
     */
    private function countCleanSheets(?int $seasonId = null, ?string $type = null): int
    {
        if (!$this->hasValidTeam()) {
            return 0;
        }
        
        $where = ['m.club_id = :club_id', 'm.status = "ready"'];
        $params = ['club_id' => $this->clubId, 'team_id' => $this->primaryTeamId];
        
        if ($seasonId !== null) {
            $where[] = 'm.season_id = :season_id';
            $params['season_id'] = $seasonId;
        }
        
        if ($type !== null) {
            $where[] = 'c.type = :type';
            $params['type'] = $type;
        }
        
        $whereClause = implode(' AND ', $where);
        
        $stmt = $this->pdo->prepare('
            SELECT COUNT(*) as clean_sheets
            FROM matches m
            LEFT JOIN competitions c ON c.id = m.competition_id
            WHERE ' . $whereClause . '
            AND (
                (m.home_team_id = :team_id AND (
                    SELECT COUNT(*) 
                    FROM events e 
                    WHERE e.match_id = m.id 
                    AND e.team_side = "away"
                    AND e.event_type_id = (SELECT id FROM event_types WHERE type_key = "goal" LIMIT 1)
                ) = 0)
                OR
                (m.away_team_id = :team_id AND (
                    SELECT COUNT(*) 
                    FROM events e 
                    WHERE e.match_id = m.id 
                    AND e.team_side = "home"
                    AND e.event_type_id = (SELECT id FROM event_types WHERE type_key = "goal" LIMIT 1)
                ) = 0)
            )
        ');
        $stmt->execute($params);
        $result = $stmt->fetch();
        return (int)($result['clean_sheets'] ?? 0);
    }

    /**
     * Get event type ID mapping (type_key => id)
     * 
     * @return array Associative array of type_key => event_type_id
     */
    private function getEventTypeMapping(): array
    {
        $stmt = $this->pdo->prepare('
            SELECT id, type_key 
            FROM event_types 
            WHERE club_id = :club_id
        ');
        $stmt->execute(['club_id' => $this->clubId]);
        
        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $map[$row['type_key']] = $row['id'];
        }
        return $map;
    }

    /**
     * Update match stat based on event type key
     * 
     * @param array $stats The stats array to update (passed by reference)
     * @param string $typeKey The event type key
     * @param int $count The count to set
     */
    private function updateMatchStatByType(array &$stats, ?string $typeKey, int $count): void
    {
        $mapping = [
            'goal' => 'goals',
            'shot' => 'shots',
            'corner' => 'corners',
            'free_kick' => 'free_kicks',
            'penalty' => 'penalties',
            'foul' => 'fouls',
            'yellow_card' => 'yellow_cards',
            'red_card' => 'red_cards',
            'substitution' => 'substitutions',
        ];

        $statField = $mapping[$typeKey] ?? null;
        if ($statField && isset($stats[$statField])) {
            $stats[$statField] = $count;
        }
    }

    /**
     * Get empty overview stats array
     * 
     * @return array Default stats with all values set to 0
     */
    private function getEmptyOverviewStats(): array
    {
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

    /**
     * Get empty match stats structure for a team
     * 
     * @param string $teamName The team name to include
     * @return array Empty stats with team name
     */
    private function getEmptyMatchStats(string $teamName): array
    {
        return [
            'team' => $teamName,
            'goals' => 0,
            'shots' => 0,
            'corners' => 0,
            'free_kicks' => 0,
            'penalties' => 0,
            'fouls' => 0,
            'yellow_cards' => 0,
            'red_cards' => 0,
            'substitutions' => 0,
        ];
    }

    private function createRecordTemplate(): array
    {
        return [
            'matches' => 0,
            'wins' => 0,
            'draws' => 0,
            'losses' => 0,
            'goals_for' => 0,
            'goals_against' => 0,
            'goal_difference' => 0,
        ];
    }

    private function resolveClubVenueSide(array $match): ?string
    {
        if (!$this->hasValidTeam()) {
            return null;
        }
        
        $homeTeamId = isset($match['home_team_id']) ? (int)$match['home_team_id'] : 0;
        $awayTeamId = isset($match['away_team_id']) ? (int)$match['away_team_id'] : 0;
        
        // Check if primary team is home or away
        if ($homeTeamId === $this->primaryTeamId) {
            return 'home';
        }
        if ($awayTeamId === $this->primaryTeamId) {
            return 'away';
        }
        
        // Fallback: shouldn't happen if data is consistent
        return null;
    }

    private function resolveOpponentName(array $match, string $venue): string
    {
        if ($venue === 'home') {
            return trim((string)($match['away_team_name'] ?? 'Away Team'));
        }

        return trim((string)($match['home_team_name'] ?? 'Home Team'));
    }

    private function formatMatchResult(int $clubGoals, int $opponentGoals): string
    {
        if ($clubGoals > $opponentGoals) {
            return 'W';
        }

        if ($clubGoals === $opponentGoals) {
            return 'D';
        }

        return 'L';
    }

    private function getGoalEventTypeId(): int
    {
        $map = $this->getEventTypeMapping();
        return isset($map['goal']) ? (int)$map['goal'] : 0;
    }

    /**
     * Get event type ID by type key
     * 
     * @param string $typeKey Event type key (e.g., 'yellow_card', 'red_card')
     * @return int Event type ID
     */
    private function getEventTypeIdByKey(string $typeKey): int
    {
        $map = $this->getEventTypeMapping();
        return isset($map[$typeKey]) ? (int)$map[$typeKey] : 0;
    }

    public function getTeamPerformanceStats(?int $seasonId = null, ?string $type = null): array
    {
        $goalTypeId = $this->getGoalEventTypeId();
        
        $where = ['m.club_id = :club_id', 'm.status = "ready"'];
        $params = ['club_id' => $this->clubId, 'goal_type_id' => $goalTypeId];
        
        if ($seasonId !== null) {
            $where[] = 'm.season_id = :season_id';
            $params['season_id'] = $seasonId;
        }
        if ($type !== null) {
            $where[] = 'c.type = :type';
            $params['type'] = $type;
        }
        
        $whereClause = implode(' AND ', $where);

        $stmt = $this->pdo->prepare('
            SELECT
                m.id,
                m.kickoff_at,
                m.home_team_id,
                m.away_team_id,
                c.name AS competition_name,
                c.type AS competition_type,
                ht.name AS home_team_name,
                at.name AS away_team_name,
                COALESCE((
                    SELECT COUNT(*) FROM events e
                    WHERE e.match_id = m.id
                      AND e.team_side = "home"
                      AND e.event_type_id = :goal_type_id
                ), 0) AS home_goals,
                COALESCE((
                    SELECT COUNT(*) FROM events e
                    WHERE e.match_id = m.id
                      AND e.team_side = "away"
                      AND e.event_type_id = :goal_type_id
                ), 0) AS away_goals
            FROM matches m
            LEFT JOIN competitions c ON c.id = m.competition_id
            LEFT JOIN teams ht ON ht.id = m.home_team_id
            LEFT JOIN teams at ON at.id = m.away_team_id
            WHERE ' . $whereClause . '
            ORDER BY m.kickoff_at DESC, m.id DESC
        ');
        $stmt->execute($params);

        $matches = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $matchesPlayed = count($matches);

        $homeRecord = $this->createRecordTemplate();
        $awayRecord = $this->createRecordTemplate();
        $leagueStats = $this->createRecordTemplate();
        $cupStats = $this->createRecordTemplate();
        $form = [];
        $wins = 0;
        $draws = 0;
        $losses = 0;

        foreach ($matches as $match) {
            $venue = $this->resolveClubVenueSide($match);
            if ($venue === null) {
                continue;
            }

            $clubGoals = $venue === 'home' ? (int)($match['home_goals'] ?? 0) : (int)($match['away_goals'] ?? 0);
            $opponentGoals = $venue === 'home' ? (int)($match['away_goals'] ?? 0) : (int)($match['home_goals'] ?? 0);
            $result = $this->formatMatchResult($clubGoals, $opponentGoals);
            $competitionType = trim((string)($match['competition_type'] ?? ''));
            $isLeague = $competitionType === 'league';

            if ($result === 'W') {
                $wins++;
            } elseif ($result === 'D') {
                $draws++;
            } else {
                $losses++;
            }

            $targetRecord = &$homeRecord;
            if ($venue === 'away') {
                $targetRecord = &$awayRecord;
            }

            $targetRecord['matches'] += 1;
            $targetRecord['goals_for'] += $clubGoals;
            $targetRecord['goals_against'] += $opponentGoals;
            if ($result === 'W') {
                $targetRecord['wins'] += 1;
            } elseif ($result === 'D') {
                $targetRecord['draws'] += 1;
            } else {
                $targetRecord['losses'] += 1;
            }

            $targetRecord['goal_difference'] = $targetRecord['goals_for'] - $targetRecord['goals_against'];
            
            // Track league vs cup stats
            if ($isLeague) {
                $competitionRecord = &$leagueStats;
            } else {
                $competitionRecord = &$cupStats;
            }
            $competitionRecord['matches'] += 1;
            $competitionRecord['goals_for'] += $clubGoals;
            $competitionRecord['goals_against'] += $opponentGoals;
            if ($result === 'W') {
                $competitionRecord['wins'] += 1;
            } elseif ($result === 'D') {
                $competitionRecord['draws'] += 1;
            } else {
                $competitionRecord['losses'] += 1;
            }
            $competitionRecord['goal_difference'] = $competitionRecord['goals_for'] - $competitionRecord['goals_against'];

            if (count($form) < 5) {
                $form[] = [
                    'match_id' => (int)$match['id'],
                    'result' => $result,
                    'score' => "{$clubGoals}-{$opponentGoals}",
                    'opponent' => $this->resolveOpponentName($match, $venue),
                    'venue' => ucfirst($venue),
                    'date' => $match['kickoff_at'] ?? null,
                ];
            }

            unset($targetRecord, $competitionRecord);
        }

        // Calculate clean sheets per competition
        $leagueCleanSheets = $this->countCleanSheetsByCompetitionType('league');
        $cupCleanSheets = $this->countCleanSheets() - $leagueCleanSheets;
        
        $leagueStats['clean_sheets'] = $leagueCleanSheets;
        $cupStats['clean_sheets'] = $cupCleanSheets;

        return [
            'matches_played' => $matchesPlayed,
            'wins' => $wins,
            'draws' => $draws,
            'losses' => $losses,
            'goals_for' => $leagueStats['goals_for'] + $cupStats['goals_for'],
            'goals_against' => $leagueStats['goals_against'] + $cupStats['goals_against'],
            'goal_difference' => ($leagueStats['goals_for'] + $cupStats['goals_for']) - ($leagueStats['goals_against'] + $cupStats['goals_against']),
            'clean_sheets' => $leagueCleanSheets + $cupCleanSheets,
            'home_away' => [
                'home' => $homeRecord,
                'away' => $awayRecord,
            ],
            'league_cup' => [
                'league' => $leagueStats,
                'cup' => $cupStats,
            ],
            'form' => $form,
        ];
    }
    
    /**
     * Count clean sheets for competitions of a specific type (league|cup)
     * 
     * @param string $competitionType Competition type to filter by
     * @return int Clean sheet count
     */
    private function countCleanSheetsByCompetitionType(string $competitionType): int
    {
        if (!$this->hasValidTeam()) {
            return 0;
        }
        
        $stmt = $this->pdo->prepare('
            SELECT COUNT(*) as clean_sheets
            FROM matches m
            LEFT JOIN competitions c ON c.id = m.competition_id
            WHERE m.club_id = :club_id 
            AND m.status = "ready" 
            AND c.type = :competition_type
            AND (
                (m.home_team_id = :team_id AND (
                    SELECT COUNT(*) 
                    FROM events e 
                    WHERE e.match_id = m.id 
                    AND e.team_side = "away"
                    AND e.event_type_id = (SELECT id FROM event_types WHERE type_key = "goal" LIMIT 1)
                ) = 0)
                OR
                (m.away_team_id = :team_id AND (
                    SELECT COUNT(*) 
                    FROM events e 
                    WHERE e.match_id = m.id 
                    AND e.team_side = "home"
                    AND e.event_type_id = (SELECT id FROM event_types WHERE type_key = "goal" LIMIT 1)
                ) = 0)
            )
        ');
        $stmt->execute([
            'club_id' => $this->clubId,
            'team_id' => $this->primaryTeamId,
            'competition_type' => $competitionType
        ]);
        $result = $stmt->fetch();
        return (int)($result['clean_sheets'] ?? 0);
    }
}
?>
