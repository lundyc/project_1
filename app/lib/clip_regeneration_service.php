<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/clip_repository.php';
require_once __DIR__ . '/clip_generation_service.php';
require_once __DIR__ . '/event_repository.php';
require_once __DIR__ . '/match_repository.php';
require_once __DIR__ . '/match_version_service.php';
require_once __DIR__ . '/phase3.php';
require_once __DIR__ . '/video_lab_repository.php';

/**
 * Regenerate the clip for a single event.
 *
 * @return array<string, int|null>
 */
function clip_regeneration_service_regenerate_event(int $matchId, int $eventId, int $userId = 0): array
{
          $match = get_match($matchId);
          if (!$match) {
                    throw new InvalidArgumentException('match_not_found');
          }

          $clip = get_clip_for_event($eventId);
          $clipIdForLogging = $clip ? (int)$clip['id'] : null;

          if (!phase3_is_enabled()) {
                    if ($clipIdForLogging !== null) {
                              phase3_log_clip_action((int)($match['club_id'] ?? 0), $clipIdForLogging, $userId, 'phase3_disabled');
                    }
                    throw new RuntimeException('phase3_disabled');
          }

          $video = video_lab_get_latest_match_video($matchId);
          $durationSeconds = isset($video['duration_seconds']) ? (int)$video['duration_seconds'] : 0;
          if (!$video || $durationSeconds <= 0) {
                    throw new RuntimeException('video_unavailable');
          }

          $event = event_get_by_id($eventId);
          if (!$event || (int)$event['match_id'] !== $matchId) {
                    throw new InvalidArgumentException('event_not_found');
          }

          $eventTypeKey = strtolower((string)($event['event_type_key'] ?? ''));
          if (in_array($eventTypeKey, ['period_start', 'period_end'], true)) {
                    throw new InvalidArgumentException('event_not_supported');
          }

          $pdo = db();
          $pdo->beginTransaction();
          $auditClipId = null;

          try {
                    $result = clip_regeneration_service_regenerate_event_internal(
                              $pdo,
                              $match,
                              $event,
                              $durationSeconds,
                              $userId
                    );
                    $auditClipId = isset($result['clip_id']) ? (int)$result['clip_id'] : null;
                    $pdo->commit();

                    phase3_log_metrics([
                              'clips_regenerated' => 1,
                              'match_id' => $matchId,
                              'event_id' => $eventId,
                              'user_id' => $userId,
                              'context' => 'event-regeneration',
                    ]);

                    if ($auditClipId !== null) {
                              phase3_log_clip_action((int)($match['club_id'] ?? 0), $auditClipId, $userId, 'regenerated');
                    }

                    return $result;
          } catch (\Throwable $e) {
                    $pdo->rollBack();
                    phase3_log_metrics([
                              'failures' => 1,
                              'match_id' => $matchId,
                              'event_id' => $eventId,
                              'user_id' => $userId,
                              'context' => 'event-regeneration',
                    ]);
                    throw $e;
          }
}

/**
 * Regenerate all eligible clips for a match.
 */
function clip_regeneration_service_regenerate_match(int $matchId, int $userId = 0): array
{
          $match = get_match($matchId);
          if (!$match) {
                    throw new InvalidArgumentException('match_not_found');
          }

          $video = video_lab_get_latest_match_video($matchId);
          $durationSeconds = isset($video['duration_seconds']) ? (int)$video['duration_seconds'] : 0;
          if (!$video || $durationSeconds <= 0) {
                    throw new RuntimeException('video_unavailable');
          }

          $events = video_lab_get_event_clips($matchId);
          $firstClipId = null;
          foreach ($events as $event) {
                    if (!empty($event['clip_id'])) {
                              $firstClipId = (int)$event['clip_id'];
                              break;
                    }
          }

          if (!phase3_is_enabled()) {
                    if ($firstClipId !== null) {
                              phase3_log_clip_action((int)($match['club_id'] ?? 0), $firstClipId, $userId, 'phase3_disabled');
                    }
                    throw new RuntimeException('phase3_disabled');
          }

          $pdo = db();
          $pdo->beginTransaction();
          $regenerated = 0;
          $auditClipIds = [];

          try {
                    foreach ($events as $event) {
                              $clipId = $event['clip_id'] ?? null;
                              $matchSecond = $event['match_second'] ?? null;
                              $source = strtolower((string)($event['generation_source'] ?? ''));

                              if ($clipId === null || $source !== 'event_auto') {
                                        continue;
                              }

                              if ($matchSecond === null || $matchSecond < 0) {
                                        continue;
                              }

                              $eventTypeKey = strtolower((string)($event['event_type_key'] ?? ''));
                              if (in_array($eventTypeKey, ['period_start', 'period_end'], true)) {
                                        continue;
                              }

                              $result = clip_regeneration_service_regenerate_event_internal(
                                        $pdo,
                                        $match,
                                        $event,
                                        $durationSeconds,
                                        $userId
                              );
                              if (!empty($result['clip_id'])) {
                                        $auditClipIds[] = (int)$result['clip_id'];
                              }
                              $regenerated++;
                    }

                    $pdo->commit();

                    $clipsVersion = get_clips_version($matchId);
                    phase3_log_metrics([
                              'clips_regenerated' => $regenerated,
                              'match_id' => $matchId,
                              'user_id' => $userId,
                              'context' => 'match-regeneration',
                    ]);

                    foreach (array_unique($auditClipIds) as $clipId) {
                              phase3_log_clip_action((int)($match['club_id'] ?? 0), $clipId, $userId, 'regenerated');
                    }

                    return [
                              'regenerated' => $regenerated,
                              'clips_version' => $clipsVersion,
                    ];
          } catch (\Throwable $e) {
                    $pdo->rollBack();
                    phase3_log_metrics([
                              'failures' => 1,
                              'match_id' => $matchId,
                              'user_id' => $userId,
                              'context' => 'match-regeneration',
                    ]);
                    throw $e;
          }
}

/**
 * Perform the core regeneration workflow for an event without managing a transaction.
 *
 * @return array<string, int|null>
 */
function clip_regeneration_service_regenerate_event_internal(
          PDO $pdo,
          array $match,
          array $event,
          int $durationSeconds,
          int $userId
): array {
          $eventId = (int)$event['event_id'];
          $clip = get_clip_for_event($eventId);
          if (!$clip || strtolower((string)($clip['generation_source'] ?? 'event_auto')) !== 'event_auto') {
                    throw new InvalidArgumentException('clip_not_auto');
          }

          $matchSecond = $event['match_second'] ?? null;
          if ($matchSecond === null || $matchSecond < 0) {
                    throw new InvalidArgumentException('event_seconds_invalid');
          }

          $bounds = clip_generation_service_compute_bounds((int)$matchSecond, $durationSeconds);
          if ($bounds === null) {
                    throw new RuntimeException('invalid_bounds');
          }

          $clipName = clip_generation_service_build_clip_name($event);

          $pdo->prepare('DELETE FROM clip_reviews WHERE clip_id = :clip_id')
                    ->execute(['clip_id' => $clip['id']]);
          $pdo->prepare('DELETE FROM clips WHERE id = :id')
                    ->execute(['id' => $clip['id']]);
          $pdo->prepare('DELETE FROM event_snapshots WHERE event_id = :event_id')
                    ->execute(['event_id' => $eventId]);

          $snapshotEvent = $event;
          $snapshotEvent['id'] = $eventId;
          $snapshotEvent['match_id'] = (int)$match['id'];
          clip_generation_service_record_snapshot($pdo, (int)$match['id'], $snapshotEvent);

          $nextGenerationVersion = ((int)($clip['generation_version'] ?? 0)) + 1;
          $result = create_clip(
                    (int)$match['id'],
                    (int)$match['club_id'],
                    $userId,
                    $eventId,
                    [
                              'clip_name' => $clipName,
                              'start_second' => $bounds['start'],
                              'end_second' => $bounds['end'],
                              'generation_version' => $nextGenerationVersion,
                    ],
                    'event_auto'
          );

          return [
                    'clip_id' => isset($result['clip']['id']) ? (int)$result['clip']['id'] : null,
                    'generation_version' => $nextGenerationVersion,
                    'clips_version' => $result['version'],
          ];
}
