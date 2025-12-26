<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/event_repository.php';
require_once __DIR__ . '/match_version_service.php';
require_once __DIR__ . '/audit_service.php';

function get_clip_for_event(int $eventId): ?array
{
          $stmt = db()->prepare('SELECT * FROM clips WHERE event_id = :event_id LIMIT 1');
          $stmt->execute(['event_id' => $eventId]);
          $clip = $stmt->fetch();

          return $clip ?: null;
}

function create_clip(int $matchId, int $clubId, int $userId, int $eventId, array $payload): array
{
          $event = event_get_by_id($eventId);

          if (!$event || (int)$event['match_id'] !== $matchId) {
                    throw new InvalidArgumentException('Event not found for this match');
          }

          $existingClip = get_clip_for_event($eventId);
          if ($existingClip) {
                    throw new InvalidArgumentException('Clip already exists for this event');
          }

          if ($payload['end_second'] <= $payload['start_second']) {
                    throw new InvalidArgumentException('Clip end must be greater than start');
          }

          $pdo = db();
          $pdo->beginTransaction();

          try {
                    $stmt = $pdo->prepare(
                              'INSERT INTO clips (match_id, event_id, clip_name, start_second, end_second, created_by)
                     VALUES (:match_id, :event_id, :clip_name, :start_second, :end_second, :created_by)'
                    );

                    $stmt->execute([
                              'match_id' => $matchId,
                              'event_id' => $eventId,
                              'clip_name' => $payload['clip_name'],
                              'start_second' => $payload['start_second'],
                              'end_second' => $payload['end_second'],
                              'created_by' => $userId,
                    ]);

                    $clipId = (int)$pdo->lastInsertId();
                    $version = bump_clips_version($matchId);

                    $clip = get_clip_for_event($eventId);
                    audit($clubId, $userId, 'clip', $clipId, 'create', null, json_encode($clip));

                    $pdo->commit();

                    return ['clip' => $clip, 'version' => $version];
          } catch (\Throwable $e) {
                    $pdo->rollBack();
                    throw $e;
          }
}

function delete_clip(int $matchId, int $clubId, int $userId, int $eventId): array
{
          $clip = get_clip_for_event($eventId);

          if (!$clip || (int)$clip['match_id'] !== $matchId) {
                    throw new InvalidArgumentException('Clip not found for this match');
          }

          $pdo = db();
          $pdo->beginTransaction();

          try {
                    $pdo->prepare('DELETE FROM clips WHERE event_id = :event_id')
                              ->execute(['event_id' => $eventId]);

                    $version = bump_clips_version($matchId);

                    audit($clubId, $userId, 'clip', (int)$clip['id'], 'delete', json_encode($clip), null);

                    $pdo->commit();

                    return ['version' => $version];
          } catch (\Throwable $e) {
                    $pdo->rollBack();
                    throw $e;
          }
}
