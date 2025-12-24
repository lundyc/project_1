<?php

require_once __DIR__ . '/db.php';

const LOCK_FRESH_WINDOW = 20;

function lock_now(): string
{
          return date('Y-m-d H:i:s');
}

function get_lock_with_owner(int $matchId): ?array
{
          $stmt = db()->prepare(
                    'SELECT ml.match_id,
                            ml.locked_by,
                            ml.locked_at,
                            ml.last_heartbeat_at,
                            u.display_name AS locked_by_name
             FROM match_locks ml
             LEFT JOIN users u ON u.id = ml.locked_by
             WHERE ml.match_id = :match_id
             LIMIT 1'
          );

          $stmt->execute(['match_id' => $matchId]);
          $lock = $stmt->fetch();

          return $lock ?: null;
}

function is_lock_fresh(array $lock): bool
{
          $last = $lock['last_heartbeat_at'] ?? null;
          $ts = $last ?: ($lock['locked_at'] ?? null);

          if (!$ts) {
                    return false;
          }

          return (time() - strtotime($ts)) <= LOCK_FRESH_WINDOW;
}

function acquire_match_lock(int $matchId, int $userId): array
{
          $pdo = db();
          $pdo->beginTransaction();

          try {
                    $lock = get_lock_with_owner($matchId);

                    if (!$lock) {
                              $now = lock_now();
                              $insert = $pdo->prepare(
                                        'INSERT INTO match_locks (match_id, locked_by, locked_at, last_heartbeat_at)
                             VALUES (:match_id, :locked_by, :locked_at, :last_heartbeat_at)'
                              );
                              $insert->execute([
                                        'match_id' => $matchId,
                                        'locked_by' => $userId,
                                        'locked_at' => $now,
                                        'last_heartbeat_at' => $now,
                              ]);

                              $pdo->commit();

                              return ['ok' => true, 'owner' => 'me'];
                    }

                    if ((int)$lock['locked_by'] === $userId) {
                              $now = lock_now();
                              $update = $pdo->prepare(
                                        'UPDATE match_locks
                             SET last_heartbeat_at = :heartbeat_at
                             WHERE match_id = :match_id'
                              );
                              $update->execute([
                                        'heartbeat_at' => $now,
                                        'match_id' => $matchId,
                              ]);

                              $pdo->commit();

                              return ['ok' => true, 'owner' => 'me'];
                    }

                    if (is_lock_fresh($lock)) {
                              $pdo->commit();

                              return [
                                        'ok' => false,
                                        'locked_by' => $lock['locked_by'],
                                        'locked_by_name' => $lock['locked_by_name'],
                                        'locked_at' => $lock['locked_at'],
                                        'last_heartbeat_at' => $lock['last_heartbeat_at'],
                              ];
                    }

                    $pdo->prepare('DELETE FROM match_locks WHERE match_id = :match_id')
                              ->execute(['match_id' => $matchId]);

                    $now = lock_now();
                    $insert = $pdo->prepare(
                              'INSERT INTO match_locks (match_id, locked_by, locked_at, last_heartbeat_at)
                   VALUES (:match_id, :locked_by, :locked_at, :last_heartbeat_at)'
                    );
                    $insert->execute([
                              'match_id' => $matchId,
                              'locked_by' => $userId,
                              'locked_at' => $now,
                              'last_heartbeat_at' => $now,
                    ]);

                    $pdo->commit();

                    return ['ok' => true, 'owner' => 'me'];
          } catch (\Throwable $e) {
                    $pdo->rollBack();
                    throw $e;
          }
}

function heartbeat_match_lock(int $matchId, int $userId): array
{
          $lock = get_lock_with_owner($matchId);

          if (!$lock || (int)$lock['locked_by'] !== $userId) {
                    return [
                              'ok' => false,
                              'locked_by' => $lock['locked_by'] ?? null,
                              'locked_by_name' => $lock['locked_by_name'] ?? null,
                              'locked_at' => $lock['locked_at'] ?? null,
                              'last_heartbeat_at' => $lock['last_heartbeat_at'] ?? null,
                    ];
          }

          $now = lock_now();
          $stmt = db()->prepare(
                    'UPDATE match_locks
         SET last_heartbeat_at = :heartbeat_at
         WHERE match_id = :match_id'
          );
          $stmt->execute([
                    'heartbeat_at' => $now,
                    'match_id' => $matchId,
          ]);

          return ['ok' => true];
}

function release_match_lock(int $matchId, int $userId): array
{
          $lock = get_lock_with_owner($matchId);

          if (!$lock || (int)$lock['locked_by'] !== $userId) {
                    return [
                              'ok' => false,
                              'locked_by' => $lock['locked_by'] ?? null,
                              'locked_by_name' => $lock['locked_by_name'] ?? null,
                              'locked_at' => $lock['locked_at'] ?? null,
                              'last_heartbeat_at' => $lock['last_heartbeat_at'] ?? null,
                    ];
          }

          $stmt = db()->prepare('DELETE FROM match_locks WHERE match_id = :match_id');
          $stmt->execute(['match_id' => $matchId]);

          return ['ok' => true];
}
