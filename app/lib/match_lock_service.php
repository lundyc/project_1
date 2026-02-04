<?php

require_once __DIR__ . '/db.php';

// Lock timeout constants
const LOCK_HEARTBEAT_THRESHOLD = 60;  // 1 minute - lock expires after this
const LOCK_WARNING_THRESHOLD = 45;     // 45 seconds - UI should warn user

function isLockFresh(string $heartbeatAt, int $threshold = LOCK_HEARTBEAT_THRESHOLD): bool
{
          return (time() - strtotime($heartbeatAt)) <= $threshold;
}

function findLock(int $matchId): ?array
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
          $row = $stmt->fetch();

          return $row ?: null;
}

function acquireLock(int $matchId, int $userId): array
{
          $pdo = db();
          $pdo->beginTransaction();

          try {
                    $existing = findLock($matchId);
                    $now = date('Y-m-d H:i:s');

                    if (!$existing) {
                              $stmt = $pdo->prepare(
                                        'INSERT INTO match_locks (match_id, locked_by, locked_at, last_heartbeat_at)
                             VALUES (:match_id, :locked_by, :locked_at, :heartbeat)'
                              );
                              $stmt->execute([
                                        'match_id' => $matchId,
                                        'locked_by' => $userId,
                                        'locked_at' => $now,
                                        'heartbeat' => $now,
                              ]);
                              $pdo->commit();
                              return [
                                        'ok' => true,
                                        'locked_by' => ['id' => $userId, 'display_name' => null],
                                        'locked_at' => $now,
                                        'last_heartbeat_at' => $now,
                                        'mode' => 'edit',
                              ];
                    }

                    if ((int)$existing['locked_by'] === $userId) {
                              $stmt = $pdo->prepare(
                                        'UPDATE match_locks
                     SET last_heartbeat_at = :heartbeat
                     WHERE match_id = :match_id'
                              );
                              $stmt->execute([
                                        'heartbeat' => $now,
                                        'match_id' => $matchId,
                              ]);
                              $pdo->commit();
                              return [
                                        'ok' => true,
                                        'locked_by' => ['id' => $userId, 'display_name' => $existing['locked_by_name']],
                                        'locked_at' => $existing['locked_at'],
                                        'last_heartbeat_at' => $now,
                                        'mode' => 'edit',
                              ];
                    }

                    if (isLockFresh($existing['last_heartbeat_at'])) {
                              $pdo->commit();
                              return [
                                        'ok' => false,
                                        'locked_by' => [
                                                  'id' => (int)$existing['locked_by'],
                                                  'display_name' => $existing['locked_by_name'],
                                        ],
                                        'locked_at' => $existing['locked_at'],
                                        'last_heartbeat_at' => $existing['last_heartbeat_at'],
                                        'mode' => 'readonly',
                              ];
                    }

                    $pdo->prepare('DELETE FROM match_locks WHERE match_id = :match_id')
                              ->execute(['match_id' => $matchId]);

                    $stmt = $pdo->prepare(
                              'INSERT INTO match_locks (match_id, locked_by, locked_at, last_heartbeat_at)
                   VALUES (:match_id, :locked_by, :locked_at, :heartbeat)'
                    );
                    $stmt->execute([
                              'match_id' => $matchId,
                              'locked_by' => $userId,
                              'locked_at' => $now,
                              'heartbeat' => $now,
                    ]);

                    $pdo->commit();

                    return [
                              'ok' => true,
                              'locked_by' => ['id' => $userId, 'display_name' => null],
                              'locked_at' => $now,
                              'last_heartbeat_at' => $now,
                              'mode' => 'edit',
                    ];
          } catch (\Throwable $e) {
                    $pdo->rollBack();
                    throw $e;
          }
}

function refreshHeartbeat(int $matchId, int $userId): array
{
          $lock = findLock($matchId);
          if (!$lock || (int)$lock['locked_by'] !== $userId) {
                    return [
                              'ok' => false,
                              'locked_by' => $lock ? ['id' => (int)$lock['locked_by'], 'display_name' => $lock['locked_by_name']] : null,
                              'locked_at' => $lock['locked_at'] ?? null,
                              'last_heartbeat_at' => $lock['last_heartbeat_at'] ?? null,
                              'mode' => 'readonly',
                    ];
          }

          $now = date('Y-m-d H:i:s');
          $stmt = db()->prepare(
                    'UPDATE match_locks
         SET last_heartbeat_at = :heartbeat
         WHERE match_id = :match_id AND locked_by = :user_id'
          );
          $stmt->execute([
                    'heartbeat' => $now,
                    'match_id' => $matchId,
                    'user_id' => $userId,
          ]);

          return [
                    'ok' => true,
                    'locked_by' => ['id' => $userId, 'display_name' => $lock['locked_by_name']],
                    'locked_at' => $lock['locked_at'],
                    'last_heartbeat_at' => $now,
                    'mode' => 'edit',
          ];
}

function releaseLock(int $matchId, int $userId): array
{
          $lock = findLock($matchId);
          if (!$lock || (int)$lock['locked_by'] !== $userId) {
                    return [
                              'ok' => false,
                              'locked_by' => $lock ? ['id' => (int)$lock['locked_by'], 'display_name' => $lock['locked_by_name']] : null,
                              'locked_at' => $lock['locked_at'] ?? null,
                              'last_heartbeat_at' => $lock['last_heartbeat_at'] ?? null,
                              'mode' => 'readonly',
                    ];
          }

          db()->prepare('DELETE FROM match_locks WHERE match_id = :match_id')->execute(['match_id' => $matchId]);

          return [
                    'ok' => true,
                    'locked_by' => null,
                    'locked_at' => null,
                    'last_heartbeat_at' => null,
                    'mode' => 'readonly',
          ];
}
