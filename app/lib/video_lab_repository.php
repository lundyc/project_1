<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/video_repository.php';

function video_lab_get_latest_match_video(int $matchId): ?array
{
          $pdo = db();
          $stmt = $pdo->prepare('SELECT * FROM match_videos WHERE match_id = :match_id ORDER BY id DESC LIMIT 1');
          $stmt->execute(['match_id' => $matchId]);

          $video = $stmt->fetch();
          if (!$video) {
                    return null;
          }

          return $video;
}

/**
 * @return array<int, array<string, mixed>>
 */
function video_lab_list_matches_with_videos(?int $clubId = null): array
{
          $pdo = db();
          $params = [];
          $where = [];

          video_repository_apply_club_filter($clubId, $params, $where, 'm');

          $latestVideoSubquery = <<<'SQL'
(SELECT mv1.match_id, mv1.source_type, mv1.duration_seconds
 FROM match_videos mv1
 JOIN (
           SELECT match_id, MAX(id) AS max_id
           FROM match_videos
           GROUP BY match_id
 ) latest_mv ON latest_mv.match_id = mv1.match_id AND latest_mv.max_id = mv1.id) mv_latest
SQL;

          $sql = <<<SQL
SELECT m.id,
       m.club_id,
       m.kickoff_at,
       m.status,
       ht.name AS home_team,
       at.name AS away_team,
       comp.name AS competition,
       mv_latest.source_type,
       mv_latest.duration_seconds,
       COUNT(DISTINCT cl.id) AS clip_count
FROM matches m
JOIN teams ht ON ht.id = m.home_team_id
JOIN teams at ON at.id = m.away_team_id
LEFT JOIN competitions comp ON comp.id = m.competition_id
JOIN $latestVideoSubquery ON mv_latest.match_id = m.id
LEFT JOIN clips cl ON cl.match_id = m.id AND cl.deleted_at IS NULL
SQL;

          if (!empty($where)) {
                    $sql .= ' WHERE ' . implode(' AND ', $where);
          }

          $sql .= ' GROUP BY m.id, m.club_id, m.kickoff_at, m.status, ht.name, at.name, comp.name, mv_latest.source_type, mv_latest.duration_seconds';
          $sql .= ' ORDER BY m.kickoff_at DESC, m.id DESC';

          $stmt = $pdo->prepare($sql);
          $stmt->execute($params);

          $matches = $stmt->fetchAll();

          foreach ($matches as &$match) {
                    $match['clip_count'] = isset($match['clip_count']) ? (int)$match['clip_count'] : 0;
          }

          return $matches;
}

function video_lab_get_match_with_video(int $matchId): ?array
{
          $pdo = db();

          $latestVideoSubquery = <<<'SQL'
(SELECT mv1.match_id, mv1.source_type, mv1.duration_seconds
 FROM match_videos mv1
 JOIN (
           SELECT match_id, MAX(id) AS max_id
           FROM match_videos
           GROUP BY match_id
 ) latest_mv ON latest_mv.match_id = mv1.match_id AND latest_mv.max_id = mv1.id) mv_latest
SQL;

          $sql = <<<SQL
SELECT m.id,
       m.club_id,
       m.kickoff_at,
       m.status,
       ht.name AS home_team,
       at.name AS away_team,
       comp.name AS competition,
       mv_latest.source_type,
       mv_latest.duration_seconds,
       COUNT(DISTINCT cl.id) AS clip_count
FROM matches m
JOIN teams ht ON ht.id = m.home_team_id
JOIN teams at ON at.id = m.away_team_id
LEFT JOIN competitions comp ON comp.id = m.competition_id
JOIN $latestVideoSubquery ON mv_latest.match_id = m.id
LEFT JOIN clips cl ON cl.match_id = m.id AND cl.deleted_at IS NULL
WHERE m.id = :match_id
GROUP BY m.id, m.club_id, m.kickoff_at, m.status, ht.name, at.name, comp.name, mv_latest.source_type, mv_latest.duration_seconds
LIMIT 1
SQL;

          $stmt = $pdo->prepare($sql);
          $stmt->execute(['match_id' => $matchId]);

          $match = $stmt->fetch();
          if (!$match) {
                    return null;
          }

          $match['clip_count'] = isset($match['clip_count']) ? (int)$match['clip_count'] : 0;

          return $match;
}

function video_lab_clip_review_summary(int $matchId): array
{
          $pdo = db();
          $stmt = $pdo->prepare(
                    'SELECT COALESCE(cr.status, \'pending\') AS status, COUNT(*) AS total
             FROM clips c
             LEFT JOIN clip_reviews cr ON cr.clip_id = c.id
             WHERE c.match_id = :match_id AND c.deleted_at IS NULL
             GROUP BY COALESCE(cr.status, \'pending\')'
          );
          $stmt->execute(['match_id' => $matchId]);

          $counts = [
                    'pending' => 0,
                    'approved' => 0,
                    'rejected' => 0,
          ];

          while ($row = $stmt->fetch()) {
                    $status = $row['status'] ?? 'pending';
                    if (!isset($counts[$status])) {
                              continue;
                    }
                    $counts[$status] = (int)($row['total'] ?? 0);
          }

          return $counts;
}

function video_lab_get_event_clips(int $matchId): array
{
          $pdo = db();
          $stmt = $pdo->prepare(
                    'SELECT e.id AS event_id,
                            e.match_second,
                            et.label AS event_type_label,
                            et.type_key AS event_type_key,
                            c.id AS clip_id,
                            c.clip_name,
                            COALESCE(c.generation_source, \'event_auto\') AS generation_source,
                            c.generation_version,
                            c.start_second AS clip_start_second,
                            c.end_second AS clip_end_second,
                            COALESCE(cr.status, \'pending\') AS clip_review_status
             FROM events e
             LEFT JOIN event_types et ON et.id = e.event_type_id
             LEFT JOIN clips c ON c.event_id = e.id AND c.deleted_at IS NULL
             LEFT JOIN clip_reviews cr ON cr.clip_id = c.id
             WHERE e.match_id = :match_id
             ORDER BY e.match_second ASC, e.id ASC'
          );
          $stmt->execute(['match_id' => $matchId]);

          $events = [];
          while ($row = $stmt->fetch()) {
                    $events[] = [
                              'event_id' => (int)$row['event_id'],
                              'id' => (int)$row['event_id'],
                              'match_id' => $matchId,
                              'match_second' => $row['match_second'] !== null ? (int)$row['match_second'] : null,
                              'event_type_label' => $row['event_type_label'] ?? null,
                              'event_type_key' => $row['event_type_key'] ?? null,
                              'clip_id' => $row['clip_id'] !== null ? (int)$row['clip_id'] : null,
                              'clip_name' => $row['clip_name'] ?? null,
                              'generation_source' => $row['generation_source'] ?? 'event_auto',
                              'generation_version' => $row['generation_version'] !== null ? (int)$row['generation_version'] : null,
                              'clip_start_second' => $row['clip_start_second'] !== null ? (int)$row['clip_start_second'] : null,
                              'clip_end_second' => $row['clip_end_second'] !== null ? (int)$row['clip_end_second'] : null,
                              'clip_review_status' => $row['clip_review_status'] ?? 'pending',
                    ];
          }

          return $events;
}
