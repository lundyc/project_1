<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/audit_service.php';
require_once __DIR__ . '/phase3.php';

/**
 * Return a summary of clip review statuses for the given match.
 *
 * @return array<string, int>
 */
function clip_review_service_get_summary(int $matchId): array
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

          while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                    $status = $row['status'] ?? 'pending';
                    if (!isset($counts[$status])) {
                              continue;
                    }
                    $counts[$status] = (int)($row['total'] ?? 0);
          }

          return $counts;
}

/**
 * List clip review metadata for a match.
 *
 * @return array<int, array<string, mixed>>
 */
function clip_review_service_list_clips_for_match(int $matchId): array
{
          $pdo = db();
          $stmt = $pdo->prepare(
                    'SELECT c.id AS clip_id,
                            c.match_id,
                            c.event_id,
                            c.clip_name,
                            c.start_second,
                            c.end_second,
                            c.duration_seconds,
                            c.generation_source,
                            c.generation_version,
                            e.match_second,
                            e.minute,
                            e.minute_extra,
                            e.team_side,
                            et.label AS event_type_label,
                            et.type_key AS event_type_key,
                            pl.first_name,
                            pl.last_name,
                            cr.status AS clip_review_status,
                            cr.reviewed_at,
                            cr.reviewed_by,
                            u.display_name AS reviewer_name
             FROM clips c
             JOIN events e ON e.id = c.event_id
             LEFT JOIN event_types et ON et.id = e.event_type_id
             LEFT JOIN match_players mp ON mp.id = e.match_player_id
             LEFT JOIN players pl ON pl.id = mp.player_id
             LEFT JOIN clip_reviews cr ON cr.clip_id = c.id
             LEFT JOIN users u ON u.id = cr.reviewed_by
             WHERE c.match_id = :match_id AND c.deleted_at IS NULL
             ORDER BY e.match_second ASC, c.id ASC'
          );
          $stmt->execute(['match_id' => $matchId]);

          $clips = [];
          require_once __DIR__ . '/player_name_helper.php';
          while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                    $row['player_name'] = build_full_name($row['first_name'] ?? null, $row['last_name'] ?? null);
                    $clips[] = clip_review_service_normalize_row($row);
          }

          return $clips;
}

/**
 * Fetch clip details for the match, including snapshot and history.
 */
function clip_review_service_get_clip_details(int $matchId, int $clipId): ?array
{
          $pdo = db();
          $clip = clip_review_service_get_clip_for_match($pdo, $matchId, $clipId);
          if (!$clip) {
                    return null;
          }

          $snapshot = clip_review_service_fetch_event_snapshot((int)$clip['event_id']);
          $clip['event_snapshot'] = $snapshot['snapshot'] ?? null;
          $clip['snapshot_json'] = $snapshot['snapshot_json'] ?? null;
          $clip['history'] = clip_review_service_build_history($clip);

          return $clip;
}

/**
 * Apply a review action to a clip and log the operation.
 *
 * @return array{clip: array<string, mixed>, summary: array<string, int>}
 */
function clip_review_service_review_clip(int $matchId, int $clipId, int $userId, string $status, int $clubId): array
{
          if (!phase3_is_enabled()) {
                    phase3_log_clip_action($clubId, $clipId, $userId, 'phase3_disabled');
                    throw new RuntimeException('phase3_disabled');
          }

          $validActions = ['approved', 'rejected'];
          if (!in_array($status, $validActions, true)) {
                    throw new InvalidArgumentException('invalid_action');
          }

          $pdo = db();
          $pdo->beginTransaction();

          try {
                    clip_review_service_ensure_pending_entry($pdo, $clipId);

                    $clip = clip_review_service_get_clip_for_match($pdo, $matchId, $clipId);
                    if (!$clip) {
                              throw new InvalidArgumentException('clip_not_found');
                    }

                    if ($clip['review_status'] !== 'pending') {
                              throw new InvalidArgumentException('review_not_pending');
                    }

                    $stmt = $pdo->prepare(
                              'UPDATE clip_reviews
                    SET status = :status,
                        reviewed_by = :reviewed_by,
                        reviewed_at = NOW()
                    WHERE clip_id = :clip_id'
                    );
                    $stmt->execute([
                              'status' => $status,
                              'reviewed_by' => $userId,
                              'clip_id' => $clipId,
                    ]);

                    $before = [
                              'status' => $clip['review_status'],
                              'reviewed_by' => $clip['reviewed_by'],
                              'reviewed_at' => $clip['reviewed_at'],
                    ];
                    $after = [
                              'status' => $status,
                              'reviewed_by' => $userId,
                              'reviewed_at' => date('Y-m-d H:i:s'),
                    ];
                    audit(
                              $clubId,
                              $userId,
                              'clip_review',
                              $clipId,
                              'review_' . $status,
                              json_encode($before),
                              json_encode($after)
                    );

                    $pdo->commit();
          } catch (\Throwable $e) {
                    $pdo->rollBack();
                    throw $e;
          }

          $updatedClip = clip_review_service_get_clip_for_match(db(), $matchId, $clipId);
          if (!$updatedClip) {
                    throw new RuntimeException('clip_not_found');
          }

          return [
                    'clip' => $updatedClip,
                    'summary' => clip_review_service_get_summary($matchId),
          ];
}

/**
 * Normalize a raw query row into an explicit structure.
 *
 * @return array<string, mixed>
 */
function clip_review_service_normalize_row(array $row): array
{
          return [
                    'clip_id' => isset($row['clip_id']) ? (int)$row['clip_id'] : null,
                    'match_id' => isset($row['match_id']) ? (int)$row['match_id'] : null,
                    'event_id' => isset($row['event_id']) ? (int)$row['event_id'] : null,
                    'clip_name' => $row['clip_name'] ?? null,
                    'start_second' => isset($row['start_second']) ? (int)$row['start_second'] : null,
                    'end_second' => isset($row['end_second']) ? (int)$row['end_second'] : null,
                    'duration_seconds' => isset($row['duration_seconds']) ? (int)$row['duration_seconds'] : null,
                    'generation_source' => $row['generation_source'] ?? null,
                    'generation_version' => isset($row['generation_version']) ? (int)$row['generation_version'] : null,
                    'match_second' => isset($row['match_second']) ? (int)$row['match_second'] : null,
                    'minute' => isset($row['minute']) ? (int)$row['minute'] : null,
                    'minute_extra' => isset($row['minute_extra']) ? (int)$row['minute_extra'] : null,
                    'team_side' => $row['team_side'] ?? 'unknown',
                    'event_type_label' => $row['event_type_label'] ?? 'Event',
                    'event_type_key' => $row['event_type_key'] ?? null,
                    'player_name' => $row['player_name'] ?? null,
                    'review_status' => $row['clip_review_status'] ?? 'pending',
                    'reviewed_at' => $row['reviewed_at'] ?? null,
                    'reviewed_by' => isset($row['reviewed_by']) ? (int)$row['reviewed_by'] : null,
                    'reviewed_by_name' => $row['reviewer_name'] ?? null,
          ];
}

/**
 * Ensure a clip_review row exists for the clip so updates can run.
 */
function clip_review_service_ensure_pending_entry(\PDO $pdo, int $clipId): void
{
          $stmt = $pdo->prepare(
                    'INSERT INTO clip_reviews (clip_id, status)
             VALUES (:clip_id, \'pending\')
             ON DUPLICATE KEY UPDATE clip_id = clip_id'
          );
          $stmt->execute(['clip_id' => $clipId]);
}

/**
 * Retrieve a single clip for the match.
 */
function clip_review_service_get_clip_for_match(\PDO $pdo, int $matchId, int $clipId): ?array
{
          $stmt = $pdo->prepare(
                    'SELECT c.id AS clip_id,
                            c.match_id,
                            c.event_id,
                            c.clip_name,
                            c.start_second,
                            c.end_second,
                            c.duration_seconds,
                            c.generation_source,
                            c.generation_version,
                            e.match_second,
                            e.minute,
                            e.minute_extra,
                            e.team_side,
                            et.label AS event_type_label,
                            et.type_key AS event_type_key,
                            pl.first_name,
                            pl.last_name,
                            cr.status AS clip_review_status,
                            cr.reviewed_at,
                            cr.reviewed_by,
                            u.display_name AS reviewer_name
             FROM clips c
             JOIN events e ON e.id = c.event_id
             LEFT JOIN event_types et ON et.id = e.event_type_id
             LEFT JOIN match_players mp ON mp.id = e.match_player_id
             LEFT JOIN players pl ON pl.id = mp.player_id
             LEFT JOIN clip_reviews cr ON cr.clip_id = c.id
             LEFT JOIN users u ON u.id = cr.reviewed_by
             WHERE c.match_id = :match_id AND c.id = :clip_id AND c.deleted_at IS NULL
             LIMIT 1'
          );
          $stmt->execute([
                    'match_id' => $matchId,
                    'clip_id' => $clipId,
          ]);
          
          $row = $stmt->fetch(\PDO::FETCH_ASSOC);
          if ($row) {
                    require_once __DIR__ . '/player_name_helper.php';
                    $row['player_name'] = build_full_name($row['first_name'] ?? null, $row['last_name'] ?? null);
          }
          
          return $row ? clip_review_service_normalize_row($row) : null;

          $row = $stmt->fetch(\PDO::FETCH_ASSOC);
          if (!$row) {
                    return null;
          }

          return clip_review_service_normalize_row($row);
}

/**
 * Read the latest event snapshot for the provided event.
 *
 * @return array{snapshot: array<string, mixed>|null, snapshot_json: string|null}
 */
function clip_review_service_fetch_event_snapshot(int $eventId): array
{
          $pdo = db();
          $stmt = $pdo->prepare('SELECT snapshot_json FROM event_snapshots WHERE event_id = :event_id LIMIT 1');
          $stmt->execute(['event_id' => $eventId]);
          $snapshotJson = $stmt->fetchColumn();

          if ($snapshotJson === false || $snapshotJson === null) {
                    return ['snapshot' => null, 'snapshot_json' => null];
          }

          $decoded = json_decode($snapshotJson, true);
          if (!is_array($decoded)) {
                    $decoded = [];
          }

          return [
                    'snapshot' => $decoded,
                    'snapshot_json' => $snapshotJson,
          ];
}

/**
 * Build a simple review history array from the clip state.
 *
 * @return array<int, array<string, mixed>>
 */
function clip_review_service_build_history(array $clip): array
{
          $history = [
                    [
                              'status' => 'pending',
                              'label' => 'Pending review',
                              'reviewed_by' => null,
                              'reviewed_at' => null,
                    ],
          ];

          if (($clip['review_status'] ?? '') !== 'pending') {
                    $history[] = [
                              'status' => $clip['review_status'],
                              'label' => ucfirst($clip['review_status']),
                              'reviewed_by' => $clip['reviewed_by_name'] ?? null,
                              'reviewed_at' => $clip['reviewed_at'] ?? null,
                    ];
          }

          return $history;
}
