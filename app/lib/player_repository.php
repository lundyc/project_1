<?php

require_once __DIR__ . '/db.php';

function get_player_by_id(int $playerId, int $clubId): ?array
{
          $stmt = db()->prepare(
                    'SELECT p.*, t.name AS team_name
             FROM players p
             LEFT JOIN teams t ON t.id = p.team_id AND t.club_id = p.club_id
             WHERE p.id = :id AND p.club_id = :club_id
             LIMIT 1'
          );
          $stmt->execute([
                    'id' => $playerId,
                    'club_id' => $clubId,
          ]);
          $row = $stmt->fetch();

          return $row ?: null;
}

function get_players_for_club(int $clubId, array $filters = []): array
{
          $params = ['club_id' => $clubId];
          $where = ['p.club_id = :club_id'];
          $joins = [];

          if (!empty($filters['team_id'])) {
                    $where[] = 'p.team_id = :team_id';
                    $params['team_id'] = (int)$filters['team_id'];
          }

          if (isset($filters['active']) && $filters['active'] !== '') {
                    $where[] = 'p.is_active = :is_active';
                    $params['is_active'] = $filters['active'] === '1' ? 1 : 0;
          }

          if (!empty($filters['season_id'])) {
                    $joins[] = 'EXISTS (
                              SELECT 1
                              FROM player_team_season pts
                              WHERE pts.player_id = p.id
                                AND pts.club_id = :club_id
                                AND pts.season_id = :season_id
                    )';
                    $params['season_id'] = (int)$filters['season_id'];
          }

          $sql = 'SELECT p.*, t.name AS team_name
           FROM players p
           LEFT JOIN teams t ON t.id = p.team_id AND t.club_id = p.club_id';

          if ($joins) {
                    $sql .= ' WHERE ' . implode(' AND ', array_merge($where, $joins));
          } else {
                    $sql .= ' WHERE ' . implode(' AND ', $where);
          }

          $sql .= ' ORDER BY p.first_name ASC, p.last_name ASC';

          $stmt = db()->prepare($sql);
          $stmt->execute($params);

          return $stmt->fetchAll();
}

function create_player_for_club(int $clubId, array $data): int
{
          $stmt = db()->prepare(
                    'INSERT INTO players (club_id, first_name, last_name, dob, primary_position, team_id, is_active)
           VALUES (:club_id, :first_name, :last_name, :dob, :primary_position, :team_id, :is_active)'
          );

          $stmt->execute([
                    'club_id' => $clubId,
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'dob' => $data['dob'],
                    'primary_position' => $data['primary_position'],
                    'team_id' => $data['team_id'],
                    'is_active' => $data['is_active'],
          ]);

          return (int)db()->lastInsertId();
}

function update_player_for_club(int $playerId, int $clubId, array $data): bool
{
          $stmt = db()->prepare(
                    'UPDATE players
             SET first_name = :first_name,
                 last_name = :last_name,
                 dob = :dob,
                 primary_position = :primary_position,
                 team_id = :team_id,
                 is_active = :is_active
             WHERE id = :id AND club_id = :club_id'
          );

          return $stmt->execute([
                    'id' => $playerId,
                    'club_id' => $clubId,
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'dob' => $data['dob'],
                    'primary_position' => $data['primary_position'],
                    'team_id' => $data['team_id'],
                    'is_active' => $data['is_active'],
          ]);
}

function deactivate_player(int $playerId, int $clubId): bool
{
          $stmt = db()->prepare('UPDATE players SET is_active = 0 WHERE id = :id AND club_id = :club_id');
          return $stmt->execute([
                    'id' => $playerId,
                    'club_id' => $clubId,
          ]);
}

function get_player_appearances(int $playerId, int $clubId): array
{
          $stmt = db()->prepare(
                    'SELECT mp.id,
                            mp.match_id,
                            mp.team_side,
                            mp.shirt_number,
                            mp.is_starting,
                            m.kickoff_at,
                            m.season_id,
                            s.name AS season_name,
                            ht.name AS home_team,
                            at.name AS away_team
             FROM match_players mp
             JOIN matches m ON m.id = mp.match_id AND m.club_id = :club_id
             JOIN teams ht ON ht.id = m.home_team_id
             JOIN teams at ON at.id = m.away_team_id
             LEFT JOIN seasons s ON s.id = m.season_id
             WHERE mp.player_id = :player_id
             ORDER BY m.kickoff_at DESC, m.id DESC'
          );

          $stmt->execute([
                    'player_id' => $playerId,
                    'club_id' => $clubId,
          ]);

          return $stmt->fetchAll();
}

function get_player_event_stats(int $playerId, int $clubId): array
{
          $eventExpr = 'LOWER(COALESCE(et.type_key, et.label, ""))';

          $sql = "
SELECT m.id AS match_id,
       m.season_id,
       s.name AS season_name,
       m.kickoff_at,
       ht.name AS home_team,
       at.name AS away_team,
       COUNT(*) AS total_events,
       SUM(CASE
                    WHEN ($eventExpr LIKE '%goal%') THEN 1
                    ELSE 0
               END) AS goals,
       SUM(CASE
                    WHEN ($eventExpr LIKE '%assist%') THEN 1
                    ELSE 0
               END) AS assists,
       SUM(CASE
                    WHEN (($eventExpr LIKE '%shot%') OR ($eventExpr LIKE '%strike%')) AND NOT ($eventExpr LIKE '%goal%') THEN 1
                    ELSE 0
               END) AS shots,
       SUM(CASE
                    WHEN $eventExpr LIKE '%tackle%' THEN 1
                    ELSE 0
               END) AS tackles,
       SUM(CASE
                    WHEN ($eventExpr LIKE '%key%pass%') OR ($eventExpr LIKE '%key_pass%') THEN 1
                    ELSE 0
               END) AS key_passes
FROM events e
LEFT JOIN match_players mp ON mp.id = e.match_player_id
JOIN matches m ON m.id = e.match_id AND m.club_id = :club_id
LEFT JOIN teams ht ON ht.id = m.home_team_id
LEFT JOIN teams at ON at.id = m.away_team_id
LEFT JOIN seasons s ON s.id = m.season_id
LEFT JOIN event_types et ON et.id = e.event_type_id
WHERE (e.player_id = :player_id OR mp.player_id = :player_id)
GROUP BY m.id
ORDER BY m.kickoff_at DESC, m.id DESC
";

          $stmt = db()->prepare($sql);
          $stmt->execute([
                    'player_id' => $playerId,
                    'club_id' => $clubId,
          ]);

          $rows = $stmt->fetchAll();
          $seasonTotals = [];

          foreach ($rows as $row) {
                    $seasonKey = $row['season_id'] !== null ? 'season_' . $row['season_id'] : 'season_0';
                    if (!isset($seasonTotals[$seasonKey])) {
                              $seasonTotals[$seasonKey] = [
                                        'season_id' => $row['season_id'],
                                        'season_name' => $row['season_name'] ?: 'Unassigned Season',
                                        'total_events' => 0,
                                        'goals' => 0,
                                        'assists' => 0,
                                        'shots' => 0,
                                        'tackles' => 0,
                                        'key_passes' => 0,
                              ];
                    }

                    $seasonTotals[$seasonKey]['total_events'] += (int)$row['total_events'];
                    $seasonTotals[$seasonKey]['goals'] += (int)$row['goals'];
                    $seasonTotals[$seasonKey]['assists'] += (int)$row['assists'];
                    $seasonTotals[$seasonKey]['shots'] += (int)$row['shots'];
                    $seasonTotals[$seasonKey]['tackles'] += (int)$row['tackles'];
                    $seasonTotals[$seasonKey]['key_passes'] += (int)$row['key_passes'];
          }

          return [
                    'matches' => $rows,
                    'seasons' => array_values($seasonTotals),
          ];
}

function get_player_team_history(int $playerId, int $clubId): array
{
          $stmt = db()->prepare(
                    'SELECT pts.*, t.name AS team_name, s.name AS season_name
             FROM player_team_season pts
             JOIN teams t ON t.id = pts.team_id AND t.club_id = pts.club_id
             LEFT JOIN seasons s ON s.id = pts.season_id
             WHERE pts.player_id = :player_id AND pts.club_id = :club_id
             ORDER BY pts.created_at DESC'
          );

          $stmt->execute([
                    'player_id' => $playerId,
                    'club_id' => $clubId,
          ]);

          return $stmt->fetchAll();
}

function get_derived_stats_for_match_ids(array $matchIds, int $playerId): array
{
          if (!$matchIds) {
                    return [];
          }

          $placeholders = implode(',', array_fill(0, count($matchIds), '?'));
          $stmt = db()->prepare(
                    'SELECT ds.match_id, ds.payload_json, ds.computed_at
             FROM derived_stats ds
             WHERE ds.match_id IN (' . $placeholders . ')
             ORDER BY ds.match_id ASC, ds.computed_at DESC'
          );

          $stmt->execute($matchIds);
          $result = [];

          foreach ($stmt->fetchAll() as $row) {
                    $matchId = (int)$row['match_id'];
                    if (isset($result[$matchId])) {
                              continue;
                    }

                    $payload = json_decode($row['payload_json'], true);
                    $metrics = extract_player_metrics_from_payload($payload, $playerId);

                    $result[$matchId] = [
                              'match_id' => $matchId,
                              'computed_at' => $row['computed_at'],
                              'metrics' => $metrics,
                              'payload' => $payload,
                    ];
          }

          return $result;
}

function extract_player_metrics_from_payload(?array $payload, int $playerId): array
{
          if (!is_array($payload)) {
                    return [];
          }

          $nodes = [];
          $stack = [$payload];

          while ($stack) {
                    $current = array_pop($stack);
                    if (!is_array($current)) {
                              continue;
                    }

                    if (isset($current['player_id']) && (int)$current['player_id'] === $playerId) {
                              $nodes[] = $current;
                    }

                    foreach ($current as $value) {
                              if (is_array($value)) {
                                        $stack[] = $value;
                              }
                    }
          }

          foreach ($nodes as $node) {
                    $metrics = [];

                    if (isset($node['metrics']) && is_array($node['metrics'])) {
                              foreach ($node['metrics'] as $metric) {
                                        $label = $metric['label'] ?? $metric['name'] ?? $metric['key'] ?? null;
                                        $value = $metric['value'] ?? $metric['count'] ?? $metric['total'] ?? null;
                                        if ($label && $value !== null) {
                                                  $metrics[] = [
                                                            'label' => $label,
                                                            'value' => $value,
                                                  ];
                                        }
                              }
                    }

                    if (!$metrics && isset($node['stats']) && is_array($node['stats'])) {
                              foreach ($node['stats'] as $key => $value) {
                                        $metrics[] = [
                                                  'label' => (string)$key,
                                                  'value' => $value,
                                        ];
                              }
                    }

                    if (!$metrics && isset($node['values']) && is_array($node['values'])) {
                              foreach ($node['values'] as $key => $value) {
                                        $metrics[] = [
                                                  'label' => (string)$key,
                                                  'value' => $value,
                                        ];
                              }
                    }

                    if ($metrics) {
                              return $metrics;
                    }
          }

          return [];
}
