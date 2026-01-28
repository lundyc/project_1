<?php

require_once __DIR__ . '/db.php';

class LeagueIntelligenceService
{
          private $pdo;
          private $seasonId;
          private $competitionId;
          private $matchRows = [];
          private $teamNames = [];
          private $competitions = [];
          private $seasons = [];
          private $teamStats = [];
          private $positions = [];

          public function __construct(?int $seasonId = null, ?int $competitionId = null)
          {
                    $this->pdo = db();
                    $this->resolveFilters($seasonId, $competitionId);
                    $this->ensureMatchStore();
                    $this->syncMatches();
                    $this->teamNames = $this->loadTeamNames();
          }

          public function getSelectedSeason(): ?array
          {
                    return $this->seasons[$this->seasonId] ?? null;
          }

          public function getSelectedCompetition(): ?array
          {
                    return $this->competitions[$this->competitionId] ?? null;
          }

          public function getSeasonOptions(): array
          {
                    return array_values($this->seasons);
          }

          public function getCompetitionOptions(): array
          {
                    return array_values($this->competitions);
          }

          public function getLeagueTable(): array
          {
                    $this->ensureTeamStats();
                    return $this->teamStats;
          }

          private function ensureTeamStats(): void
          {
                    if ($this->teamStats !== []) {
                              return;
                    }

                    $matches = $this->fetchMatches();
                    $teams = [];
                    $totalGoals = 0;
                    $completedMatches = 0;

                    foreach ($matches as $match) {
                              $homeId = (int)$match['home_team_id'];
                              $awayId = (int)$match['away_team_id'];
                              $homeName = trim($match['home_team_name'] ?? '') ?: 'Home';
                              $awayName = trim($match['away_team_name'] ?? '') ?: 'Away';

                              if (!isset($teams[$homeId])) {
                                        $teams[$homeId] = $this->createTeamStat($homeId, $homeName);
                              }

                              if (!isset($teams[$awayId])) {
                                        $teams[$awayId] = $this->createTeamStat($awayId, $awayName);
                              }

                              $homeGoals = $match['home_goals'] !== null ? (int)$match['home_goals'] : null;
                              $awayGoals = $match['away_goals'] !== null ? (int)$match['away_goals'] : null;
                              $isCompleted = $match['status'] === 'completed' && $homeGoals !== null && $awayGoals !== null;
                              $matchDate = $match['kickoff_at'];
                              $matchInfo = [
                                        'match_id' => (int)$match['match_id'],
                                        'date' => $matchDate,
                                        'home_team_id' => $homeId,
                                        'away_team_id' => $awayId,
                                        'home_team_name' => $homeName,
                                        'away_team_name' => $awayName,
                                        'home_goals' => $homeGoals,
                                        'away_goals' => $awayGoals,
                                        'status' => $match['status'],
                                        'result' => null,
                                        'venue' => 'home',
                              ];

                              if ($isCompleted) {
                                        $completedMatches++;
                                        $totalGoals += ($homeGoals + $awayGoals);
                                        $homeResult = $this->deriveResult($homeGoals, $awayGoals);
                                        $awayResult = $this->deriveResult($awayGoals, $homeGoals);

                                        $this->applyMatchOutcome($teams[$homeId], 'home', $homeResult, $homeGoals, $awayGoals);
                                        $this->applyMatchOutcome($teams[$awayId], 'away', $awayResult, $awayGoals, $homeGoals);

                                        $teams[$homeId]['form'][] = $homeResult;
                                        $teams[$awayId]['form'][] = $awayResult;

                                        $teams[$homeId]['points_trend'][] = $teams[$homeId]['points'];
                                        $teams[$awayId]['points_trend'][] = $teams[$awayId]['points'];

                                        $teams[$homeId]['goal_difference_trend'][] = $teams[$homeId]['goals_for'] - $teams[$homeId]['goals_against'];
                                        $teams[$awayId]['goal_difference_trend'][] = $teams[$awayId]['goals_for'] - $teams[$awayId]['goals_against'];

                                        $this->trackHeadToHead($teams[$homeId]['head_to_head'], $awayId, $awayName, $homeResult, $matchDate);
                                        $this->trackHeadToHead($teams[$awayId]['head_to_head'], $homeId, $homeName, $awayResult, $matchDate);

                                        $matchInfo['result'] = $homeResult;
                              } else {
                                        $matchInfo['result'] = null;
                              }

                              $teams[$homeId]['match_history'][] = $this->buildMatchHistoryEntry($matchInfo, true, $homeGoals, $awayGoals);
                              $teams[$awayId]['match_history'][] = $this->buildMatchHistoryEntry($matchInfo, false, $awayGoals, $homeGoals);
                    }

                    $sorted = $this->finalizeTeamStats($teams);
                    $this->teamStats = $sorted;
                    $this->positions = array_column($sorted, 'position', 'team_id');

                    $this->assignStrengthOfSchedule($matches);
          }

          public function getTeamMatches(int $teamId): array
          {
                    $this->ensureTeamStats();
                    foreach ($this->teamStats as $team) {
                              if ($team['team_id'] === $teamId) {
                                        return array_reverse($team['match_history']);
                              }
                    }

                    return [];
          }

          public function getResultsAndFixtures(int $limit = 5): array
          {
                    $matches = $this->fetchMatches();
                    $results = [];
                    $fixtures = [];

                    $completed = array_reverse($matches);
                    foreach ($completed as $match) {
                              if (count($results) >= $limit) {
                                        break;
                              }

                              if ($match['status'] === 'completed' && $match['home_goals'] !== null && $match['away_goals'] !== null) {
                                        $results[] = $this->formatLeagueOverviewMatch($match);
                              }
                    }

                    foreach ($matches as $match) {
                              if (count($fixtures) >= $limit) {
                                        break;
                              }

                              if ($match['status'] === 'scheduled') {
                                        $fixtures[] = $this->formatLeagueOverviewMatch($match);
                              }
                    }

                    return [
                              'results' => $results,
                              'fixtures' => $fixtures,
                    ];
          }

          private function finalizeTeamStats(array $teams): array
          {
                    $rows = array_values($teams);

                    usort($rows, function ($a, $b) {
                              if ($b['points'] !== $a['points']) {
                                        return $b['points'] <=> $a['points'];
                              }

                              $aGoalDiff = $a['goals_for'] - $a['goals_against'];
                              $bGoalDiff = $b['goals_for'] - $b['goals_against'];
                              if ($bGoalDiff !== $aGoalDiff) {
                                        return $bGoalDiff <=> $aGoalDiff;
                              }

                              if ($b['goals_for'] !== $a['goals_for']) {
                                        return $b['goals_for'] <=> $a['goals_for'];
                              }

                              return $a['team_name'] <=> $b['team_name'];
                    });

                    $position = 0;
                    foreach ($rows as &$team) {
                              $position++;
                              $team['position'] = $position;
                              $team['goal_difference'] = $team['goals_for'] - $team['goals_against'];
                              $team['points_per_game'] = $team['played'] > 0 ? round($team['points'] / $team['played'], 2) : 0;
                              $team['average_goals_per_match'] = $team['played'] > 0 ? round($team['goals_for'] / $team['played'], 2) : 0;
                              $team['form_display'] = array_slice($team['form'], -5);
                              $team['streak_label'] = $this->formatStreak($team['form']);
                              $team['record_label'] = sprintf('%d-%d-%d', $team['wins'], $team['draws'], $team['losses']);
                              $team['head_to_head_list'] = $this->buildHeadToHeadList($team['head_to_head']);
                              $completed = array_filter($team['match_history'], fn($row) => $row['result'] !== null);
                              $team['recent_results'] = array_slice(array_reverse(array_values($completed)), 0, 5);
                    }

                    return $rows;
          }

          private function formatStreak(array $form): string
          {
                    $last = null;
                    $count = 0;
                    for ($i = count($form) - 1; $i >= 0; $i--) {
                              if ($last === null) {
                                        $last = $form[$i];
                                        $count = 1;
                                        continue;
                              }

                              if ($form[$i] === $last) {
                                        $count++;
                              } else {
                                        break;
                              }
                    }

                    if ($last === null) {
                              return '—';
                    }

                    return $last . $count;
          }

          private function buildHeadToHeadList(array $map): array
          {
                    $list = array_values($map);
                    usort($list, function ($a, $b) {
                              $matchesA = $a['wins'] + $a['draws'] + $a['losses'];
                              $matchesB = $b['wins'] + $b['draws'] + $b['losses'];
                              if ($matchesB !== $matchesA) {
                                        return $matchesB <=> $matchesA;
                              }

                              if ($a['opponent_name'] !== $b['opponent_name']) {
                                        return $a['opponent_name'] <=> $b['opponent_name'];
                              }

                              return 0;
                    });

                    return $list;
          }

          private function assignStrengthOfSchedule(array $matches): void
          {
                    if (empty($this->teamStats)) {
                              return;
                    }

                    $tracker = [];
                    foreach ($this->teamStats as $row) {
                              $tracker[$row['team_id']] = [
                                        'completed_sum' => 0,
                                        'completed_count' => 0,
                                        'upcoming_sum' => 0,
                                        'upcoming_count' => 0,
                              ];
                    }

                    foreach ($matches as $match) {
                              $homeId = (int)$match['home_team_id'];
                              $awayId = (int)$match['away_team_id'];
                              $isCompleted = $match['status'] === 'completed' && $match['home_goals'] !== null && $match['away_goals'] !== null;

                              $homeOpp = $this->positions[$awayId] ?? null;
                              $awayOpp = $this->positions[$homeId] ?? null;

                              if ($homeOpp !== null && isset($tracker[$homeId])) {
                                        if ($isCompleted) {
                                                  $tracker[$homeId]['completed_sum'] += $homeOpp;
                                                  $tracker[$homeId]['completed_count']++;
                                        } else {
                                                  $tracker[$homeId]['upcoming_sum'] += $homeOpp;
                                                  $tracker[$homeId]['upcoming_count']++;
                                        }
                              }

                              if ($awayOpp !== null && isset($tracker[$awayId])) {
                                        if ($isCompleted) {
                                                  $tracker[$awayId]['completed_sum'] += $awayOpp;
                                                  $tracker[$awayId]['completed_count']++;
                                        } else {
                                                  $tracker[$awayId]['upcoming_sum'] += $awayOpp;
                                                  $tracker[$awayId]['upcoming_count']++;
                                        }
                              }
                    }

                    foreach ($tracker as $teamId => $values) {
                              $index = $this->findTeamIndex($teamId);
                              if ($index === null) {
                                        continue;
                              }

                              if ($values['completed_count'] > 0) {
                                        $this->teamStats[$index]['strength_of_schedule']['completed'] = round($values['completed_sum'] / $values['completed_count'], 2);
                              }

                              if ($values['upcoming_count'] > 0) {
                                        $this->teamStats[$index]['strength_of_schedule']['upcoming'] = round($values['upcoming_sum'] / $values['upcoming_count'], 2);
                              }
                    }
          }

          private function findTeamIndex(int $teamId): ?int
          {
                    foreach ($this->teamStats as $index => $team) {
                              if ($team['team_id'] === $teamId) {
                                        return $index;
                              }
                    }

                    return null;
          }

          public function getLeagueTrends(): array
          {
                    $this->ensureTeamStats();
                    $matches = $this->fetchMatches();
                    $completedMatches = array_filter($matches, function ($match) {
                              return $match['status'] === 'completed' && $match['home_goals'] !== null && $match['away_goals'] !== null;
                    });

                    $totalGoals = array_reduce($completedMatches, function ($carry, $match) {
                              return $carry + ((int)$match['home_goals'] + (int)$match['away_goals']);
                    }, 0);

                    $avgGoalsPerMatch = count($completedMatches) > 0 ? round($totalGoals / count($completedMatches), 2) : 0;

                    $longest = $this->findLongestUnbeatenRun();
                    $cleanSheets = array_sum(array_column($this->teamStats, 'clean_sheets'));
                    $avgCleanSheets = !empty($this->teamStats) ? round($cleanSheets / count($this->teamStats), 2) : 0;

                    return [
                              [
                                        'label' => 'Avg goals per match',
                                        'value' => number_format($avgGoalsPerMatch, 2),
                                        'detail' => count($completedMatches) . ' completed',
                              ],
                              [
                                        'label' => 'Longest unbeaten run',
                                        'value' => $longest['label'],
                                        'detail' => $longest['description'],
                              ],
                              [
                                        'label' => 'Avg clean sheets / team',
                                        'value' => number_format($avgCleanSheets, 2),
                                        'detail' => 'league mean',
                              ],
                    ];
          }

          public function getTeamNavigation(): array
          {
                    $this->ensureTeamStats();
                    return array_map(fn($row) => [
                              'team_id' => $row['team_id'],
                              'team_name' => $row['team_name'],
                              'position' => $row['position'],
                    ], $this->teamStats);
          }

          public function getTeamById(int $teamId): ?array
          {
                    $this->ensureTeamStats();
                    foreach ($this->teamStats as $team) {
                              if ($team['team_id'] === $teamId) {
                                        return $team;
                              }
                    }

                    return null;
          }

          public function getTeamInsights(int $teamId): ?array
          {
                    $team = $this->getTeamById($teamId);
                    if (!$team) {
                              return null;
                    }

                    return [
                              'team_id' => $team['team_id'],
                              'team_name' => $team['team_name'],
                              'position' => $team['position'],
                              'points' => $team['points'],
                              'record' => $team['record_label'],
                              'goal_difference' => $team['goal_difference'],
                              'streak' => $team['streak_label'],
                              'form' => $team['form_display'],
                              'points_per_game' => $team['points_per_game'],
                              'average_goals_per_match' => $team['average_goals_per_match'],
                              'goals_for' => $team['goals_for'],
                              'goals_against' => $team['goals_against'],
                              'clean_sheets' => $team['clean_sheets'],
                              'home' => $team['home'],
                              'away' => $team['away'],
                              'head_to_head' => $team['head_to_head_list'],
                              'strength_of_schedule' => $team['strength_of_schedule'],
                              'points_trend' => $team['points_trend'],
                              'goal_difference_trend' => $team['goal_difference_trend'],
                              'recent_matches' => $team['recent_results'],
                              'match_history' => $team['match_history'],
                    ];
          }

          private function formatLeagueOverviewMatch(array $match): array
          {
                    $score = ($match['home_goals'] !== null && $match['away_goals'] !== null)
                              ? ((int)$match['home_goals'] . ' - ' . (int)$match['away_goals'])
                              : 'vs';

                    return [
                              'match_id' => (int)$match['match_id'],
                              'date' => $match['kickoff_at'],
                              'home_team' => $match['home_team_name'],
                              'away_team' => $match['away_team_name'],
                              'score' => $score,
                              'status' => $match['status'],
                    ];
          }

          private function findLongestUnbeatenRun(): array
          {
                    $best = [
                              'matches' => 0,
                              'label' => '—',
                              'description' => 'No matches recorded',
                    ];

                    foreach ($this->teamStats as $team) {
                              $current = 0;
                              $longest = 0;
                              foreach ($team['form'] as $result) {
                                        if ($result === 'L') {
                                                  $current = 0;
                                                  continue;
                                        }

                                        $current++;
                                        if ($current > $longest) {
                                                  $longest = $current;
                                        }
                              }

                              if ($longest > $best['matches']) {
                                        $best['matches'] = $longest;
                                        $best['label'] = $team['team_name'];
                                        $best['description'] = $longest > 0 ? "Unbeaten for {$longest} matches" : 'No streak yet';
                              }
                    }

                    return $best;
          }

          private function resolveFilters(?int $seasonId, ?int $competitionId): void
          {
                    $this->competitions = $this->loadLeagueCompetitions();
                    $this->seasons = $this->loadSeasons();

                    if ($competitionId !== null && isset($this->competitions[$competitionId])) {
                              $this->competitionId = $competitionId;
                    } elseif (!empty($this->competitions)) {
                              $keys = array_keys($this->competitions);
                              $this->competitionId = $keys[0];
                    } else {
                              $this->competitionId = null;
                    }

                    if ($seasonId !== null && isset($this->seasons[$seasonId])) {
                              $this->seasonId = $seasonId;
                    } elseif ($this->competitionId !== null) {
                              $comp = $this->competitions[$this->competitionId];
                              if (!empty($comp['season_id']) && isset($this->seasons[$comp['season_id']])) {
                                        $this->seasonId = $comp['season_id'];
                              } else {
                                        $this->seasonId = $this->getDefaultSeasonId();
                              }
                    } else {
                              $this->seasonId = $this->getDefaultSeasonId();
                    }
          }

          private function getDefaultSeasonId(): ?int
          {
                    $seasonIds = array_keys($this->seasons);
                    return $seasonIds[0] ?? null;
          }

          private function loadLeagueCompetitions(): array
          {
                    $stmt = $this->pdo->prepare('SELECT id, name, season_id, type FROM competitions WHERE type = :type ORDER BY name ASC');
                    $stmt->execute(['type' => 'league']);
                    $results = [];
                    while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                              $results[(int)$row['id']] = $row;
                    }
                    return $results;
          }

          private function loadSeasons(): array
          {
                    $stmt = $this->pdo->query('SELECT id, name, start_date, end_date FROM seasons ORDER BY start_date DESC, id DESC');
                    $results = [];
                    while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                              $results[(int)$row['id']] = $row;
                    }
                    return $results;
          }

          private function loadTeamNames(): array
          {
                    $stmt = $this->pdo->query('SELECT id, name FROM teams');
                    $results = [];
                    while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                              $results[(int)$row['id']] = $row['name'];
                    }
                    return $results;
          }

          private function ensureMatchStore(): void
          {
                    $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `league_intelligence_matches` (
  `match_id` BIGINT UNSIGNED NOT NULL,
  `competition_id` BIGINT UNSIGNED,
  `season_id` BIGINT UNSIGNED,
  `home_team_id` BIGINT UNSIGNED,
  `away_team_id` BIGINT UNSIGNED,
  `kickoff_at` DATETIME,
  `home_goals` SMALLINT UNSIGNED,
  `away_goals` SMALLINT UNSIGNED,
  `status` VARCHAR(32) NOT NULL DEFAULT 'scheduled',
  `neutral_location` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT current_timestamp(),
  `updated_at` DATETIME DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`match_id`),
  KEY `idx_li_competition` (`competition_id`),
  KEY `idx_li_season` (`season_id`),
  KEY `idx_li_home_team` (`home_team_id`),
  KEY `idx_li_away_team` (`away_team_id`),
  KEY `idx_li_kickoff` (`kickoff_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

                    $this->pdo->exec($sql);
          }

          public function syncMatches(): void
          {
                    $filters = [
                              'comp.type = :league_type',
                              'm.home_team_id IS NOT NULL',
                              'm.away_team_id IS NOT NULL',
                    ];
                    $params = ['league_type' => 'league'];

                    if ($this->competitionId !== null) {
                              $filters[] = 'comp.id = :competition_id';
                              $params['competition_id'] = $this->competitionId;
                    }

                    if ($this->seasonId !== null) {
                              $filters[] = 'COALESCE(m.season_id, comp.season_id) = :season_id';
                              $params['season_id'] = $this->seasonId;
                    }

                    $sql = '
INSERT INTO `league_intelligence_matches` (
  `match_id`,
  `competition_id`,
  `season_id`,
  `home_team_id`,
  `away_team_id`,
  `kickoff_at`,
  `home_goals`,
  `away_goals`,
  `status`,
  `neutral_location`,
  `created_at`,
  `updated_at`
) SELECT
  m.id,
  m.competition_id,
  COALESCE(m.season_id, comp.season_id) AS season_id,
  m.home_team_id,
  m.away_team_id,
  m.kickoff_at,
  CASE WHEN m.status IN (\'ready\',\'completed\',\'played\') THEN COALESCE(home_goals.goals, 0) ELSE NULL END,
  CASE WHEN m.status IN (\'ready\',\'completed\',\'played\') THEN COALESCE(away_goals.goals, 0) ELSE NULL END,
  CASE
    WHEN m.status IN (\'ready\',\'completed\',\'played\') THEN \'completed\'
    WHEN m.status IN (\'cancelled\',\'canceled\',\'void\',\'abandoned\') THEN \'cancelled\'
    ELSE \'scheduled\'
  END,
  0,
  m.created_at,
  m.updated_at
FROM `matches` m
INNER JOIN `competitions` comp ON comp.id = m.competition_id
LEFT JOIN (
  SELECT e.match_id, COUNT(*) AS goals
  FROM `events` e
  JOIN `event_types` t ON t.id = e.event_type_id AND t.type_key = \'goal\'
  WHERE e.team_side = \'home\'
  GROUP BY e.match_id
) home_goals ON home_goals.match_id = m.id
LEFT JOIN (
  SELECT e.match_id, COUNT(*) AS goals
  FROM `events` e
  JOIN `event_types` t ON t.id = e.event_type_id AND t.type_key = \'goal\'
  WHERE e.team_side = \'away\'
  GROUP BY e.match_id
) away_goals ON away_goals.match_id = m.id
WHERE ' . implode(' AND ', $filters) . '
ON DUPLICATE KEY UPDATE
  `competition_id` = VALUES(`competition_id`),
  `season_id` = VALUES(`season_id`),
  `home_team_id` = VALUES(`home_team_id`),
  `away_team_id` = VALUES(`away_team_id`),
  `kickoff_at` = VALUES(`kickoff_at`),
  `home_goals` = VALUES(`home_goals`),
  `away_goals` = VALUES(`away_goals`),
  `status` = VALUES(`status`),
  `updated_at` = VALUES(`updated_at`)
';

                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute($params);
          }

          private function fetchMatches(): array
          {
                    if ($this->matchRows !== []) {
                              return $this->matchRows;
                    }

                    $where = ['1 = 1'];
                    $params = [];

                    if ($this->competitionId !== null) {
                              $where[] = 'lim.competition_id = :competition_id';
                              $params['competition_id'] = $this->competitionId;
                    }

                    if ($this->seasonId !== null) {
                              $where[] = 'COALESCE(lim.season_id, comp.season_id) = :season_id';
                              $params['season_id'] = $this->seasonId;
                    }

                    $sql = '
SELECT
  lim.*,
  COALESCE(ht.name, "Home") AS home_team_name,
  COALESCE(at.name, "Away") AS away_team_name,
  comp.name AS competition_name,
  comp.type AS competition_type,
  s.name AS season_name
FROM `league_intelligence_matches` lim
LEFT JOIN `teams` ht ON ht.id = lim.home_team_id
LEFT JOIN `teams` at ON at.id = lim.away_team_id
LEFT JOIN `competitions` comp ON comp.id = lim.competition_id
LEFT JOIN `seasons` s ON s.id = lim.season_id
WHERE ' . implode(' AND ', $where) . '
ORDER BY COALESCE(lim.kickoff_at, "9999-12-31 23:59:59") ASC, lim.match_id ASC
';

                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute($params);

                    $this->matchRows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                    return $this->matchRows;
          }

          private function createTeamStat(int $teamId, string $name): array
          {
                    return [
                              'team_id' => $teamId,
                              'team_name' => $name,
                              'played' => 0,
                              'wins' => 0,
                              'draws' => 0,
                              'losses' => 0,
                              'points' => 0,
                              'goals_for' => 0,
                              'goals_against' => 0,
                              'goal_difference' => 0,
                              'clean_sheets' => 0,
                              'form' => [],
                              'last_matches' => [],
                              'home' => [
                                        'matches' => 0,
                                        'wins' => 0,
                                        'draws' => 0,
                                        'losses' => 0,
                                        'points' => 0,
                                        'goals_for' => 0,
                                        'goals_against' => 0,
                              ],
                              'away' => [
                                        'matches' => 0,
                                        'wins' => 0,
                                        'draws' => 0,
                                        'losses' => 0,
                                        'points' => 0,
                                        'goals_for' => 0,
                                        'goals_against' => 0,
                              ],
                              'match_history' => [],
                              'head_to_head' => [],
                              'points_trend' => [],
                              'goal_difference_trend' => [],
                              'strength_of_schedule' => [
                                        'completed' => null,
                                        'upcoming' => null,
                              ],
                    ];
          }

          private function deriveResult(?int $goalsFor, ?int $goalsAgainst): ?string
          {
                    if ($goalsFor === null || $goalsAgainst === null) {
                              return null;
                    }

                    if ($goalsFor > $goalsAgainst) {
                              return 'W';
                    }

                    if ($goalsFor === $goalsAgainst) {
                              return 'D';
                    }

                    return 'L';
          }

          private function applyMatchOutcome(array &$team, string $venue, string $result, int $goalsFor, int $goalsAgainst): void
          {
                    $team['played']++;
                    $team['goals_for'] += $goalsFor;
                    $team['goals_against'] += $goalsAgainst;

                    if ($result === 'W') {
                              $team['wins']++;
                              $team['points'] += 3;
                    } elseif ($result === 'D') {
                              $team['draws']++;
                              $team['points'] += 1;
                    } else {
                              $team['losses']++;
                    }

                    if ($goalsAgainst === 0) {
                              $team['clean_sheets']++;
                    }

                    $venueStats = &$team[$venue];
                    $venueStats['matches']++;
                    $venueStats['goals_for'] += $goalsFor;
                    $venueStats['goals_against'] += $goalsAgainst;

                    if ($result === 'W') {
                              $venueStats['wins']++;
                              $venueStats['points'] += 3;
                    } elseif ($result === 'D') {
                             $venueStats['draws']++;
                             $venueStats['points'] += 1;
                    } else {
                             $venueStats['losses']++;
                    }
          }

          private function trackHeadToHead(array &$map, int $opponentId, string $opponentName, ?string $result, ?string $date): void
          {
                    if (!isset($map[$opponentId])) {
                              $map[$opponentId] = [
                                        'opponent_id' => $opponentId,
                                        'opponent_name' => $opponentName,
                                        'wins' => 0,
                                        'draws' => 0,
                                        'losses' => 0,
                                        'last_result' => null,
                                        'last_date' => null,
                              ];
                    }

                    if ($result === 'W') {
                              $map[$opponentId]['wins']++;
                    } elseif ($result === 'D') {
                              $map[$opponentId]['draws']++;
                    } elseif ($result === 'L') {
                              $map[$opponentId]['losses']++;
                    }

                    if ($date !== null) {
                              $map[$opponentId]['last_result'] = $result;
                              $map[$opponentId]['last_date'] = $date;
                    }
          }

          private function buildMatchHistoryEntry(array $match, bool $isHome, ?int $goalsFor, ?int $goalsAgainst): array
          {
                    $opponentId = $isHome ? (int)$match['away_team_id'] : (int)$match['home_team_id'];
                    $opponentName = $isHome ? $match['away_team_name'] : $match['home_team_name'];
                    $result = $isHome ? $match['result'] : $this->invertResult($match['result']);
                    $score = ($goalsFor !== null && $goalsAgainst !== null) ? "{$goalsFor}-{$goalsAgainst}" : null;

                    return [
                              'match_id' => (int)$match['match_id'],
                              'date' => $match['date'],
                              'opponent_id' => $opponentId,
                              'opponent_name' => $opponentName,
                              'venue' => $isHome ? 'Home' : 'Away',
                              'score' => $score,
                              'result' => $result,
                              'status' => $match['status'],
                    ];
          }

          private function invertResult(?string $result): ?string
          {
                    if ($result === 'W') {
                              return 'L';
                    }

                    if ($result === 'L') {
                              return 'W';
                    }

                    return $result;
          }
}
