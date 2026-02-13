<?php

require_once __DIR__ . '/db.php';

/**
 * Ensure expected match_videos metadata columns exist.
 */
function ensure_match_video_columns(): array
{
          static $columns;

          if ($columns !== null) {
                    return $columns;
          }

          try {
                    $pdo = db();
                    $stmt = $pdo->query('SHOW COLUMNS FROM match_videos');
                    $cols = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
                    $columns = array_map(fn($c) => $c['Field'], $cols);
          } catch (\Throwable $e) {
                    error_log('Unable to read match_videos columns: ' . $e->getMessage());
                    $columns = [];
          }

          return $columns;
}

function normalize_video_source_type(?string $type): string
{
          $type = strtolower(trim((string)$type));
          if ($type === 'veo') {
                    return 'veo';
          }

          if ($type === 'url') {
                    return 'url';
          }

          if ($type === 'none') {
                    return 'none';
          }

          return in_array($type, ['upload', 'file'], true) ? 'upload' : 'upload';
}

function get_match_video(int $matchId): ?array
{
          $pdo = db();
          ensure_match_video_columns();

          $stmt = $pdo->prepare('SELECT * FROM match_videos WHERE match_id = :match_id ORDER BY id DESC LIMIT 1');
          $stmt->execute(['match_id' => $matchId]);
          $row = $stmt->fetch();

          if (!$row) {
                    return null;
          }

          $row['source_type'] = normalize_video_source_type($row['source_type'] ?? 'upload');

          return $row;
}

function upsert_match_video(int $matchId, array $data): void
{
          $pdo = db();
          $columns = ensure_match_video_columns();

          $existingStmt = $pdo->prepare('SELECT id FROM match_videos WHERE match_id = :match_id LIMIT 1');
          $existingStmt->execute(['match_id' => $matchId]);
          $hasRow = (bool)$existingStmt->fetchColumn();

          $videoType = normalize_video_source_type($data['source_type'] ?? 'upload');
          if (empty($data['source_path'])) {
                    throw new \RuntimeException('source_path is required for match_videos');
          }
          $fields = [
                    'source_type' => $videoType,
                    'source_path' => $data['source_path'],
          ];

          if (in_array('source_url', $columns, true)) {
                    $fields['source_url'] = $data['source_url'] ?? null;
          }
          if (in_array('download_status', $columns, true)) {
                    $fields['download_status'] = $data['download_status'] ?? 'pending';
          }
          if (in_array('download_progress', $columns, true)) {
                    $fields['download_progress'] = isset($data['download_progress']) ? (int)$data['download_progress'] : 0;
          }
          if (in_array('error_message', $columns, true)) {
                    $fields['error_message'] = $data['error_message'] ?? null;
          }
          if (in_array('thumbnail_path', $columns, true)) {
                    $fields['thumbnail_path'] = $data['thumbnail_path'] ?? null;
          }

          $params = ['match_id' => $matchId];

          if ($hasRow) {
                    $assignments = [];
                    foreach ($fields as $key => $value) {
                              $assignments[] = $key . ' = :' . $key;
                              $params[$key] = $value;
                    }
                    $sql = 'UPDATE match_videos SET ' . implode(', ', $assignments) . ' WHERE match_id = :match_id';
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
          } else {
                    $columnsSql = implode(', ', array_merge(['match_id'], array_keys($fields)));
                    $placeholders = ':' . implode(', :', array_merge(['match_id'], array_keys($fields)));
                    $stmt = $pdo->prepare("INSERT INTO match_videos ({$columnsSql}) VALUES ({$placeholders})");
                    $stmt->execute(array_merge($params, $fields));
          }
}

function get_matches_for_user(array $user): array
{
          $roles = $_SESSION['roles'] ?? [];
          $isPlatformAdmin = in_array('platform_admin', $roles, true);

          $availableCols = ensure_match_video_columns();
          $videoColumns = [
                    'mv.id AS video_id',
                    'mv.source_type AS video_source_type',
                    'mv.source_path AS video_source_path',
          ];
          if (in_array('thumbnail_path', $availableCols, true)) {
                    $videoColumns[] = 'mv.thumbnail_path AS video_thumbnail_path';
          }
          if (in_array('duration_seconds', $availableCols, true)) {
                    $videoColumns[] = 'mv.duration_seconds AS video_duration_seconds';
          }
          $videoSelect = implode(",\n                         ", $videoColumns);

          $sql = 'SELECT m.id,
                         m.club_id,
                         m.home_team_id,
                         m.away_team_id,
                         m.kickoff_at,
                         m.status,
                         m.venue,
                         m.notes,
                         ht.name AS home_team,
                         at.name AS away_team,
                         c.name AS competition,
                         cl.name AS club_name,
                         ' . $videoSelect . ',
                         mv.id IS NOT NULL AS has_video
                  FROM matches m
                  JOIN teams ht ON ht.id = m.home_team_id
                  JOIN teams at ON at.id = m.away_team_id
                  LEFT JOIN competitions c ON c.id = m.competition_id
                  LEFT JOIN clubs cl ON cl.id = m.club_id
                  LEFT JOIN (
                            SELECT mv1.*
                            FROM match_videos mv1
                            INNER JOIN (
                                      SELECT match_id, MAX(id) AS max_id
                                      FROM match_videos
                                      GROUP BY match_id
                            ) latest ON latest.max_id = mv1.id
                  ) mv ON mv.match_id = m.id';

          $params = [];

          if (!$isPlatformAdmin) {
                    if (empty($user['club_id'])) {
                              return [];
                    }

                    $sql .= ' WHERE m.club_id = :club_id';
                    $params['club_id'] = $user['club_id'];
          }

          $sql .= ' ORDER BY m.kickoff_at DESC, m.id DESC';

          $stmt = db()->prepare($sql);
          $stmt->execute($params);

          return $stmt->fetchAll();
}

function get_user_club_id_by_id(int $userId): ?int
{
          if ($userId <= 0) {
                    return null;
          }

          $stmt = db()->prepare('SELECT club_id FROM users WHERE id = :id LIMIT 1');
          $stmt->execute(['id' => $userId]);
          $clubId = $stmt->fetchColumn();
          if ($clubId === false || $clubId === null) {
                    return null;
          }

          return (int)$clubId;
}

function get_match_status_counts_for_user(int $userId): array
{
          $roles = $_SESSION['roles'] ?? [];
          $isPlatformAdmin = in_array('platform_admin', $roles, true);
          $params = [];

          $where = [];
          if (!$isPlatformAdmin) {
                    $clubId = get_user_club_id_by_id($userId);
                    if (!$clubId) {
                              return [];
                    }
                    $where[] = 'm.club_id = :club_id';
                    $params['club_id'] = $clubId;
          }

          $whereSql = $where ? (' WHERE ' . implode(' AND ', $where)) : '';
          $sql = '
                    SELECT COALESCE(NULLIF(LOWER(TRIM(m.status)), ""), "draft") AS status_key,
                           COUNT(*) AS status_count
                    FROM matches m' . $whereSql . '
                    GROUP BY COALESCE(NULLIF(LOWER(TRIM(m.status)), ""), "draft")
          ';
          $stmt = db()->prepare($sql);
          foreach ($params as $key => $value) {
                    $stmt->bindValue(':' . $key, $value, \PDO::PARAM_INT);
          }
          $stmt->execute();
          $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

          $counts = [];
          foreach ($rows as $row) {
                    $key = (string)($row['status_key'] ?? '');
                    if ($key === '') {
                              $key = 'draft';
                    }
                    $counts[$key] = (int)($row['status_count'] ?? 0);
          }

          return $counts;
}

function get_match_opponents_for_user(int $userId): array
{
          $roles = $_SESSION['roles'] ?? [];
          $isPlatformAdmin = in_array('platform_admin', $roles, true);
          $params = [];

          $where = [];
          if (!$isPlatformAdmin) {
                    $clubId = get_user_club_id_by_id($userId);
                    if (!$clubId) {
                              return [];
                    }
                    $where[] = 'm.club_id = :club_id';
                    $params['club_id'] = $clubId;
          }

          $whereSql = $where ? (' WHERE ' . implode(' AND ', $where)) : '';
          $sql = '
                    SELECT DISTINCT name FROM (
                              SELECT ht.name AS name
                              FROM matches m
                              JOIN teams ht ON ht.id = m.home_team_id' . $whereSql . '
                              UNION
                              SELECT at.name AS name
                              FROM matches m
                              JOIN teams at ON at.id = m.away_team_id' . $whereSql . '
                    ) t
                    WHERE name IS NOT NULL AND name <> ""
          ';
          $stmt = db()->prepare($sql);
          if (!empty($params)) {
                    $stmt->bindValue(':club_id', $params['club_id'], \PDO::PARAM_INT);
          }
          $stmt->execute();
          $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

          $opponents = [];
          foreach ($rows as $row) {
                    $name = trim((string)($row['name'] ?? ''));
                    if ($name !== '') {
                              $opponents[] = $name;
                    }
          }

          return $opponents;
}

function get_match_ids_for_user(int $userId): array
{
          $roles = $_SESSION['roles'] ?? [];
          $isPlatformAdmin = in_array('platform_admin', $roles, true);
          $params = [];

          $where = [];
          if (!$isPlatformAdmin) {
                    $clubId = get_user_club_id_by_id($userId);
                    if (!$clubId) {
                              return [];
                    }
                    $where[] = 'm.club_id = :club_id';
                    $params['club_id'] = $clubId;
          }

          $whereSql = $where ? (' WHERE ' . implode(' AND ', $where)) : '';
          $sql = 'SELECT m.id FROM matches m' . $whereSql;
          $stmt = db()->prepare($sql);
          if (!empty($params)) {
                    $stmt->bindValue(':club_id', $params['club_id'], \PDO::PARAM_INT);
          }
          $stmt->execute();
          $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

          return array_map(static fn($row) => (int)($row['id'] ?? 0), $rows);
}

function get_paginated_matches_for_user(
          int $userId,
          array $filters,
          int $limit,
          int $offset
): array {
          $roles = $_SESSION['roles'] ?? [];
          $isPlatformAdmin = in_array('platform_admin', $roles, true);
          $params = [];
          $paramTypes = [];
          $where = [];

          if (!$isPlatformAdmin) {
                    $clubId = get_user_club_id_by_id($userId);
                    if (!$clubId) {
                              return ['data' => [], 'total' => 0];
                    }
                    $where[] = 'm.club_id = :club_id';
                    $params['club_id'] = $clubId;
                    $paramTypes['club_id'] = \PDO::PARAM_INT;
          }

          $statusFilter = strtolower(trim((string)($filters['status'] ?? '')));
          if ($statusFilter !== '') {
                    if ($statusFilter === 'draft') {
                              $where[] = '(m.status IS NULL OR m.status = "" OR m.status = :status_draft)';
                              $params['status_draft'] = 'draft';
                              $paramTypes['status_draft'] = \PDO::PARAM_STR;
                    } else {
                              $where[] = 'm.status = :status';
                              $params['status'] = $statusFilter;
                              $paramTypes['status'] = \PDO::PARAM_STR;
                    }
          }

          $opponentFilter = trim((string)($filters['opponent'] ?? ''));
          if ($opponentFilter !== '') {
                    $where[] = '(ht.name = :opponent OR at.name = :opponent)';
                    $params['opponent'] = $opponentFilter;
                    $paramTypes['opponent'] = \PDO::PARAM_STR;
          }

          $competitionTypeFilter = strtolower(trim((string)($filters['competition_type'] ?? '')));
          if ($competitionTypeFilter === 'league') {
                    $where[] = 'c.name LIKE :competition_like';
                    $params['competition_like'] = '%league%';
                    $paramTypes['competition_like'] = \PDO::PARAM_STR;
          } elseif ($competitionTypeFilter === 'cups') {
                    $where[] = 'c.name LIKE :competition_like';
                    $params['competition_like'] = '%cup%';
                    $paramTypes['competition_like'] = \PDO::PARAM_STR;
          }

          $dateFrom = trim((string)($filters['date_from'] ?? ''));
          $dateTo = trim((string)($filters['date_to'] ?? ''));
          if ($dateFrom !== '') {
                    $where[] = 'm.kickoff_at IS NOT NULL AND m.kickoff_at >= :date_from';
                    $params['date_from'] = $dateFrom . ' 00:00:00';
                    $paramTypes['date_from'] = \PDO::PARAM_STR;
          }
          if ($dateTo !== '') {
                    $where[] = 'm.kickoff_at IS NOT NULL AND m.kickoff_at <= :date_to';
                    $params['date_to'] = $dateTo . ' 23:59:59';
                    $paramTypes['date_to'] = \PDO::PARAM_STR;
          }

          $searchQuery = strtolower(trim((string)($filters['search'] ?? '')));
          if ($searchQuery !== '') {
                    $where[] = 'CONCAT_WS(" ", ht.name, at.name, c.name, m.venue, m.notes) LIKE :search';
                    $params['search'] = '%' . $searchQuery . '%';
                    $paramTypes['search'] = \PDO::PARAM_STR;
          }

          $whereSql = $where ? (' WHERE ' . implode(' AND ', $where)) : '';

          $countSql = '
                    SELECT COUNT(*) AS total
                    FROM matches m
                    JOIN teams ht ON ht.id = m.home_team_id
                    JOIN teams at ON at.id = m.away_team_id
                    LEFT JOIN competitions c ON c.id = m.competition_id' . $whereSql;
          $countStmt = db()->prepare($countSql);
          foreach ($params as $key => $value) {
                    $type = $paramTypes[$key] ?? \PDO::PARAM_STR;
                    $countStmt->bindValue(':' . $key, $value, $type);
          }
          $countStmt->execute();
          $total = (int)$countStmt->fetchColumn();

          $availableCols = ensure_match_video_columns();
          $videoColumns = [
                    'mv.id AS video_id',
                    'mv.source_type AS video_source_type',
                    'mv.source_path AS video_source_path',
          ];
          if (in_array('thumbnail_path', $availableCols, true)) {
                    $videoColumns[] = 'mv.thumbnail_path AS video_thumbnail_path';
          }
          if (in_array('duration_seconds', $availableCols, true)) {
                    $videoColumns[] = 'mv.duration_seconds AS video_duration_seconds';
          }
          $videoSelect = implode(",\n                         ", $videoColumns);

          $dataSql = 'SELECT m.id,
                         m.club_id,
                         m.home_team_id,
                         m.away_team_id,
                         m.kickoff_at,
                         m.status,
                         m.venue,
                         m.notes,
                         ht.name AS home_team,
                         at.name AS away_team,
                         c.name AS competition,
                         cl.name AS club_name,
                         ' . $videoSelect . ',
                         mv.id IS NOT NULL AS has_video
                  FROM matches m
                  JOIN teams ht ON ht.id = m.home_team_id
                  JOIN teams at ON at.id = m.away_team_id
                  LEFT JOIN competitions c ON c.id = m.competition_id
                  LEFT JOIN clubs cl ON cl.id = m.club_id
                  LEFT JOIN (
                            SELECT mv1.*
                            FROM match_videos mv1
                            INNER JOIN (
                                      SELECT match_id, MAX(id) AS max_id
                                      FROM match_videos
                                      GROUP BY match_id
                            ) latest ON latest.max_id = mv1.id
                  ) mv ON mv.match_id = m.id' . $whereSql . '
                  ORDER BY m.kickoff_at DESC, m.id DESC
                  LIMIT :limit OFFSET :offset';

          $dataStmt = db()->prepare($dataSql);
          foreach ($params as $key => $value) {
                    $type = $paramTypes[$key] ?? \PDO::PARAM_STR;
                    $dataStmt->bindValue(':' . $key, $value, $type);
          }
          $dataStmt->bindValue(':limit', max(1, (int)$limit), \PDO::PARAM_INT);
          $dataStmt->bindValue(':offset', max(0, (int)$offset), \PDO::PARAM_INT);
          $dataStmt->execute();
          $data = $dataStmt->fetchAll();

          return ['data' => $data, 'total' => $total];
}

function get_li_scheduled_fixtures_for_club(int $clubId, int $limit = 10): array
{
          if ($clubId <= 0) {
                    return [];
          }

          $limit = max(1, min($limit, 50));
          $now = date('Y-m-d H:i:s');
          $maxDate = '9999-12-31 23:59:59';

          $sql = "
          SELECT
                    lim.match_id,
                    lim.kickoff_at,
                    lim.home_team_id,
                    lim.away_team_id,
                    lim.competition_id,
                    lim.season_id,
                    lim.status,
                    ht.name AS home_team_name,
                    at.name AS away_team_name,
                    comp.name AS competition_name,
                    ht.club_id AS home_club_id,
                    at.club_id AS away_club_id
          FROM league_intelligence_matches lim
          JOIN teams ht ON ht.id = lim.home_team_id
          JOIN teams at ON at.id = lim.away_team_id
          LEFT JOIN competitions comp ON comp.id = lim.competition_id
          LEFT JOIN matches m ON m.id = lim.match_id
          WHERE lim.status = :status
            AND (ht.club_id = :club_id OR at.club_id = :club_id2)
            AND (lim.kickoff_at IS NULL OR lim.kickoff_at >= :now)
          ORDER BY COALESCE(lim.kickoff_at, :max_date) ASC, lim.match_id ASC
          LIMIT " . (int)$limit;

          $stmt = db()->prepare($sql);
          $stmt->execute([
                    'status' => 'scheduled',
                    'club_id' => $clubId,
                    'club_id2' => $clubId,
                    'now' => $now,
                    'max_date' => $maxDate,
          ]);

          return $stmt->fetchAll();
}

function get_match(int $id): ?array
{
          $availableCols = ensure_match_video_columns();
          $videoColumns = [
                    'mv.source_type AS video_source_type',
                    'mv.source_path AS video_source_path',
          ];
          if (in_array('thumbnail_path', $availableCols, true)) {
                    $videoColumns[] = 'mv.thumbnail_path AS video_thumbnail_path';
          }
          if (in_array('source_url', $availableCols, true)) {
                    $videoColumns[] = 'mv.source_url AS video_source_url';
          }
          if (in_array('download_status', $availableCols, true)) {
                    $videoColumns[] = 'mv.download_status AS video_download_status';
          }
          if (in_array('download_progress', $availableCols, true)) {
                    $videoColumns[] = 'mv.download_progress AS video_download_progress';
          }
          if (in_array('error_message', $availableCols, true)) {
                    $videoColumns[] = 'mv.error_message AS video_error_message';
          }
          if (in_array('duration_seconds', $availableCols, true)) {
                    $videoColumns[] = 'mv.duration_seconds AS video_duration_seconds';
          }

          $videoSelect = implode(",\n                            ", $videoColumns);

          $stmt = db()->prepare(
                    'SELECT m.*,
                            ht.name AS home_team,
                            at.name AS away_team,
                            c.name AS competition,
                            mv.id AS video_id,
                            ' . $videoSelect . '
             FROM matches m
             JOIN teams ht ON ht.id = m.home_team_id
             JOIN teams at ON at.id = m.away_team_id
             LEFT JOIN competitions c ON c.id = m.competition_id
             LEFT JOIN match_videos mv ON mv.match_id = m.id
             WHERE m.id = :id
             ORDER BY mv.id DESC
             LIMIT 1'
          );

          $stmt->execute(['id' => $id]);

          $match = $stmt->fetch();

          if (!$match) {
                    return null;
          }

          $match['video_source_type'] = normalize_video_source_type($match['video_source_type'] ?? 'upload');

          return $match;
}

function create_match(array $data): int
{
          $pdo = db();
          $pdo->beginTransaction();

          try {
                    $stmt = $pdo->prepare(
                              'INSERT INTO matches
                     (club_id, season_id, competition_id, home_team_id, away_team_id, kickoff_at, venue, referee, attendance, status, created_by)
                     VALUES
                     (:club_id, :season_id, :competition_id, :home_team_id, :away_team_id, :kickoff_at, :venue, :referee, :attendance, :status, :created_by)'
                    );

                    $stmt->execute([
                              'club_id' => $data['club_id'],
                              'season_id' => $data['season_id'],
                              'competition_id' => $data['competition_id'],
                              'home_team_id' => $data['home_team_id'],
                              'away_team_id' => $data['away_team_id'],
                              'kickoff_at' => $data['kickoff_at'],
                              'venue' => $data['venue'],
                              'referee' => $data['referee'],
                              'attendance' => $data['attendance'],
                              'status' => $data['status'],
                              'created_by' => $data['created_by'],
                    ]);

                    $matchId = (int)$pdo->lastInsertId();

                    if (!empty($data['video_source_path'])) {
                              upsert_match_video($matchId, [
                                        'source_type' => normalize_video_source_type($data['video_source_type'] ?? 'upload'),
                                        'source_path' => $data['video_source_path'],
                                        'download_status' => 'completed',
                                        'download_progress' => 100,
                                        'error_message' => null,
                              ]);
                    }

                    $pdo->commit();

                    return $matchId;
          } catch (\Throwable $e) {
                    $pdo->rollBack();
                    throw $e;
          }
}

function update_match(int $id, array $data): void
{
          $pdo = db();
          $pdo->beginTransaction();

          try {
                    $stmt = $pdo->prepare(
                              'UPDATE matches
                     SET club_id = :club_id,
                         season_id = :season_id,
                         competition_id = :competition_id,
                         home_team_id = :home_team_id,
                         away_team_id = :away_team_id,
                         kickoff_at = :kickoff_at,
                         venue = :venue,
                         referee = :referee,
                         attendance = :attendance,
                         status = :status,
                         updated_at = NOW()
                     WHERE id = :id'
                    );

                    $stmt->execute([
                              'club_id' => $data['club_id'],
                              'season_id' => $data['season_id'],
                              'competition_id' => $data['competition_id'],
                              'home_team_id' => $data['home_team_id'],
                              'away_team_id' => $data['away_team_id'],
                              'kickoff_at' => $data['kickoff_at'],
                              'venue' => $data['venue'],
                              'referee' => $data['referee'],
                              'attendance' => $data['attendance'],
                              'status' => $data['status'],
                              'id' => $id,
                    ]);

                    if ($data['video_source_type'] === 'none') {
                              // Delete video record if switching to "No Video"
                              $deleteStmt = $pdo->prepare('DELETE FROM match_videos WHERE match_id = :match_id');
                              $deleteStmt->execute(['match_id' => $id]);
                    } elseif (($data['video_source_type'] ?? null) !== 'none') {
                              // Only upsert if a non-empty source_path is present
                              $sourcePath = $data['video_source_path'] ?? null;
                              if (!empty($sourcePath)) {
                                        upsert_match_video($id, [
                                                  'source_type' => normalize_video_source_type($data['video_source_type'] ?? 'upload'),
                                                  'source_path' => $sourcePath,
                                                  'download_status' => 'completed',
                                                  'download_progress' => 100,
                                                  'error_message' => null,
                                        ]);
                              }
                    }

                    $pdo->commit();
          } catch (\Throwable $e) {
                    $pdo->rollBack();
                    error_log('Error updating match ' . $id . ': ' . $e->getMessage());
                    throw $e;
          }
}

function delete_match(int $id): void
{
          $pdo = db();
          $pdo->beginTransaction();

          try {
                    // Remove dependent records to satisfy foreign keys.
                    $pdo->prepare(
                              'DELETE et FROM event_tags et
                   JOIN events e ON e.id = et.event_id
                   WHERE e.match_id = :match_id'
                    )->execute(['match_id' => $id]);

                    $pdo->prepare('DELETE FROM clips WHERE match_id = :match_id')
                              ->execute(['match_id' => $id]);

                    $pdo->prepare('DELETE FROM events WHERE match_id = :match_id')
                              ->execute(['match_id' => $id]);

                    $pdo->prepare('DELETE FROM match_players WHERE match_id = :match_id')
                              ->execute(['match_id' => $id]);

                    $pdo->prepare('DELETE FROM match_periods WHERE match_id = :match_id')
                              ->execute(['match_id' => $id]);

                    $pdo->prepare('DELETE FROM match_videos WHERE match_id = :match_id')
                              ->execute(['match_id' => $id]);

                    $pdo->prepare('DELETE FROM match_locks WHERE match_id = :match_id')
                              ->execute(['match_id' => $id]);

                    $pdo->prepare('DELETE FROM matches WHERE id = :id')
                              ->execute(['id' => $id]);

                    $pdo->commit();
          } catch (\Throwable $e) {
                    $pdo->rollBack();
                    throw $e;
          }
}
