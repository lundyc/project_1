<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/clip_repository.php';
require_once __DIR__ . '/phase3.php';
require_once __DIR__ . '/video_lab_repository.php';
require_once __DIR__ . '/match_version_service.php';

function clip_generation_service_handle_event_save(array $match, array $event): void
{
          if (!phase3_is_enabled()) {
                    return;
          }

          $eventTypeKey = strtolower((string)($event['event_type_key'] ?? ''));
          if (in_array($eventTypeKey, ['period_start', 'period_end'], true)) {
                    return;
          }

          $matchSecond = isset($event['match_second']) ? (int)$event['match_second'] : null;
          if ($matchSecond === null || $matchSecond < 0) {
                    return;
          }

          $video = video_lab_get_latest_match_video((int)$match['id']);
          if (!$video) {
                    return;
          }

          $durationSeconds = isset($video['duration_seconds']) ? (int)$video['duration_seconds'] : 0;
          if ($durationSeconds <= 0) {
                    return;
          }

          $bounds = clip_generation_service_compute_bounds($matchSecond, $durationSeconds);
          if ($bounds === null) {
                    return;
          }

          $clipName = clip_generation_service_build_clip_name($event);

          $pdo = db();
          $matchClubId = isset($match['club_id']) ? (int)$match['club_id'] : 0;
          $creatorId = isset($event['created_by']) ? (int)$event['created_by'] : 0;
          $auditClipId = null;

          try {
                    $pdo->beginTransaction();
                    clip_generation_service_record_snapshot($pdo, (int)$match['id'], $event);
                    $clipResult = clip_generation_service_insert_or_update_clip(
                              $pdo,
                              $match,
                              $event,
                              $bounds['start'],
                              $bounds['end'],
                              $clipName
                    );
                    $clipId = $clipResult['clip_id'] ?? null;
                    $wasInserted = $clipResult['was_inserted'] ?? false;
                    if ($clipId !== null) {
                              clip_generation_service_ensure_review($pdo, $clipId);
                    }
                    $pdo->commit();

                    if ($wasInserted && $clipId !== null) {
                              $auditClipId = $clipId;
                    }
          } catch (\Throwable $e) {
                    $pdo->rollBack();
                    error_log(sprintf('[clip-generation] match=%d event=%d error=%s', (int)$match['id'], (int)$event['id'], $e->getMessage()));
                    phase3_log_metrics([
                              'failures' => 1,
                              'match_id' => (int)$match['id'],
                              'event_id' => (int)$event['id'],
                              'context' => 'clip-generation',
                    ]);
          }

          if ($auditClipId !== null) {
                    phase3_log_clip_action($matchClubId, $auditClipId, $creatorId, 'generated');
          }
}

function clip_generation_service_compute_bounds(int $matchSecond, int $durationSeconds): ?array
{
          $start = max(0, $matchSecond - 30);
          $end = min($durationSeconds, $matchSecond + 30);

          if ($end <= $start) {
                    return null;
          }

          return ['start' => $start, 'end' => $end];
}

function clip_generation_service_build_clip_name(array $event): string
{
          $label = $event['event_type_label'] ?? ($event['event_type_key'] ?? 'Event');
          $matchSecond = isset($event['match_second']) ? (int)$event['match_second'] : 0;
          $name = trim($label);

          if ($matchSecond > 0) {
                    $name .= ' @ ' . $matchSecond . 's';
          }

          $name = $name !== '' ? $name : 'Auto clip';
          $maxLength = 110;
          if (mb_strlen($name) > $maxLength) {
                    $name = mb_substr($name, 0, $maxLength);
          }

          return 'Auto clip – ' . $name;
}

function clip_generation_service_record_snapshot(PDO $pdo, int $matchId, array $event): void
{
          $snapshot = json_encode($event);
          if ($snapshot === false) {
                    $snapshot = '{}';
          }

          $stmt = $pdo->prepare(
                    'INSERT INTO event_snapshots (event_id, match_id, snapshot_json)
             VALUES (:event_id, :match_id, :snapshot_json)
             ON DUPLICATE KEY UPDATE snapshot_json = VALUES(snapshot_json), created_at = NOW()'
          );
          $stmt->execute([
                    'event_id' => (int)$event['id'],
                    'match_id' => $matchId,
                    'snapshot_json' => $snapshot,
          ]);
}

/**
 * Insert a new auto clip or update the existing auto clip for the event.
 *
 * @return array{clip_id: int|null, was_inserted: bool}
 */
function clip_generation_service_insert_or_update_clip(
          PDO $pdo,
          array $match,
          array $event,
          int $startSecond,
          int $endSecond,
          string $clipName
): array {
           $eventId = (int)$event['id'];
           $existing = get_clip_for_event($eventId);
           $createdBy = isset($event['created_by']) ? (int)$event['created_by'] : 0;

            if ($existing) {
                      if (($existing['generation_source'] ?? 'event_auto') !== 'event_auto') {
                                return ['clip_id' => null, 'was_inserted' => false];
                      }

                      clip_generation_service_update_clip($pdo, (int)$existing['id'], $startSecond, $endSecond, $createdBy);
                      bump_clips_version((int)$match['id']);
                      return ['clip_id' => (int)$existing['id'], 'was_inserted' => false];
            }

            $result = create_clip(
                    (int)$match['id'],
                    (int)$match['club_id'],
                    $createdBy,
                    $eventId,
                    [
                              'clip_name' => $clipName,
                              'start_second' => $startSecond,
                              'end_second' => $endSecond,
                    ],
                    'event_auto'
          );

          if (isset($result['clip']['id'])) {
                    phase3_log_metrics([
                              'clips_generated' => 1,
                              'match_id' => (int)$match['id'],
                              'event_id' => $eventId,
                              'context' => 'auto-generation',
                    ]);
          }

                      return [
                                'clip_id' => isset($result['clip']['id']) ? (int)$result['clip']['id'] : null,
                                'was_inserted' => isset($result['clip']['id']),
                      ];
}

function clip_generation_service_update_clip(PDO $pdo, int $clipId, int $startSecond, int $endSecond, int $userId): void
{
          $durationSeconds = $endSecond - $startSecond;
          $stmt = $pdo->prepare(
                    'UPDATE clips
             SET start_second = :start_second,
                 end_second = :end_second,
                 duration_seconds = :duration_seconds,
                 updated_by = :updated_by,
                 generation_version = generation_version + 1
             WHERE id = :id'
          );
          $stmt->execute([
                    'start_second' => $startSecond,
                    'end_second' => $endSecond,
                    'duration_seconds' => $durationSeconds,
                    'updated_by' => $userId,
                    'id' => $clipId,
          ]);
}

function clip_generation_service_ensure_review(PDO $pdo, int $clipId): void
{
          $stmt = $pdo->prepare(
                    'INSERT INTO clip_reviews (clip_id, status)
             VALUES (:clip_id, \'pending\')
             ON DUPLICATE KEY UPDATE clip_id = clip_id'
          );
          $stmt->execute(['clip_id' => $clipId]);
}
