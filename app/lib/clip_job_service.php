<?php

require_once __DIR__ . '/db.php';

class ClipJobService
{
          private static $generationSourceColumnExists;

          public static function createFromEvent(array $event): void
          {
                    $eventId = isset($event['id']) ? (int)$event['id'] : 0;
                    $matchId = isset($event['match_id']) ? (int)$event['match_id'] : 0;
                    if ($eventId <= 0) {
                              self::logClipJobDecision($matchId, $eventId, 'missing_event_id');
                              return;
                    }

                    if ($matchId <= 0) {
                              self::logClipJobDecision($matchId, $eventId, 'missing_match_id');
                              return;
                    }

                    $eventTypeKey = $event['event_type_key'] ?? null;
                    if (in_array($eventTypeKey, ['period_start', 'period_end'], true)) {
                              self::logClipJobDecision($matchId, $eventId, 'filtered_event_type');
                              return;
                    }

                    $deletedAt = array_key_exists('deleted_at', $event) ? $event['deleted_at'] : null;
                    if ($deletedAt !== null) {
                              self::logClipJobDecision($matchId, $eventId, 'event_deleted');
                              return;
                    }

                    if (!array_key_exists('match_second', $event) || $event['match_second'] === null) {
                              self::logClipJobDecision($matchId, $eventId, 'missing_match_second');
                              return;
                    }

                    $matchSecond = (int)$event['match_second'];
                    if ($matchSecond <= 0) {
                              self::logClipJobDecision($matchId, $eventId, 'non_positive_match_second');
                              return;
                    }
                    $startSecond = max(0, $matchSecond - 30);
                    $durationSeconds = 60;

                    $pdo = db();
                    if (self::clipJobExists($pdo, $eventId)) {
                              self::logClipJobDecision($matchId, $eventId, 'duplicate_job');
                              return;
                    }

                    $payload = [
                              'match_id' => $matchId,
                              'event_id' => $eventId,
                              'start_second' => $startSecond,
                              'duration_seconds' => $durationSeconds,
                              'source_path' => sprintf(
                                        'videos/matches/match_%d/source/veo/standard/match_%d_standard.mp4',
                                        $matchId,
                                        $matchId
                              ),
                    ];

                    if (isset($event['clip_id']) && $event['clip_id'] !== null) {
                              $clipId = (int)$event['clip_id'];
                              if ($clipId > 0) {
                                        $payload['clip_id'] = $clipId;
                              }
                    }

                    $payloadJson = json_encode($payload, JSON_UNESCAPED_SLASHES);
                    if ($payloadJson === false) {
                              $payloadJson = '{}';
                    }

                    $params = [
                              'status' => 'pending',
                              'match_id' => $matchId,
                              'event_id' => $eventId,
                              'payload' => $payloadJson,
                    ];

                    $columns = ['status', 'match_id', 'event_id', 'payload'];

                    if (isset($payload['clip_id'])) {
                              $params['clip_id'] = $payload['clip_id'];
                              $columns[] = 'clip_id';
                    }

                    if (self::hasGenerationSourceColumn($pdo)) {
                              $params['generation_source'] = 'event_auto';
                              $columns[] = 'generation_source';
                    }

                    $columnList = implode(', ', $columns);
                    $placeholders = implode(
                              ', ',
                              array_map(static function ($column) {
                                        return ':' . $column;
                              }, $columns)
                    );

                    $stmt = $pdo->prepare(sprintf(
                              'INSERT INTO clip_jobs (%s) VALUES (%s)',
                              $columnList,
                              $placeholders
                    ));
                    $stmt->execute($params);
                    self::logClipJobDecision($matchId, $eventId, 'created');
          }

          private static function clipJobExists(PDO $pdo, int $eventId): bool
          {
                    $stmt = $pdo->prepare(
                              "SELECT 1 FROM clip_jobs WHERE event_id = :event_id AND status IN ('pending', 'processing', 'completed') LIMIT 1"
                    );
                    $stmt->execute(['event_id' => $eventId]);

                    return (bool)$stmt->fetchColumn();
          }

          private static function hasGenerationSourceColumn(PDO $pdo): bool
          {
                    if (self::$generationSourceColumnExists !== null) {
                              return self::$generationSourceColumnExists;
                    }

                    $stmt = $pdo->prepare("SHOW COLUMNS FROM clip_jobs LIKE 'generation_source'");
                    $stmt->execute();
                    self::$generationSourceColumnExists = (bool)$stmt->fetch();

                    return self::$generationSourceColumnExists;
          }

          private static function logClipJobDecision(int $matchId, int $eventId, string $reason): void
          {
                    error_log(sprintf(
                              '[clip-job] match=%d event=%d reason=%s',
                              $matchId,
                              $eventId,
                              $reason
                    ));
          }
}
