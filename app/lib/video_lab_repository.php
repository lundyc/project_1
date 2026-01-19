<?php

require_once __DIR__ . '/db.php';

/**
 * Get the latest match video for a given match ID.
 * Used by clip generation and regeneration services.
 */
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
 * Get event clips for a match.
 * Used by clip regeneration service.
 */
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
