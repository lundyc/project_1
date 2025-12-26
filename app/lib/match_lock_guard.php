<?php

require_once __DIR__ . '/match_lock_service.php';

class MatchLockException extends \RuntimeException
{
}

/**
 * Ensure the match is currently locked by the user and that the lock is still fresh.
 *
 * @param int $matchId
 * @param int $userId
 * @throws MatchLockException
 */
function require_match_lock(int $matchId, int $userId): void
{
          $lock = findLock($matchId);
          if (!$lock || (int)$lock['locked_by'] !== $userId || !isLockFresh($lock['last_heartbeat_at'])) {
                    throw new MatchLockException('lock_required');
          }
}
