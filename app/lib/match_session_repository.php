<?php

require_once __DIR__ . '/db.php';

function match_session_ensure_table(): void
{
          static $ensured = false;
          if ($ensured) {
                    return;
          }
          $ensured = true;
          $sql = <<<'SQL'
CREATE TABLE IF NOT EXISTS match_sessions (
  match_id INT NOT NULL PRIMARY KEY,
  playing TINYINT(1) NOT NULL DEFAULT 0,
  base_time_seconds DOUBLE NOT NULL DEFAULT 0,
  playback_rate DOUBLE NOT NULL DEFAULT 1,
  updated_at_ms BIGINT NOT NULL,
  control_owner_user_id INT NULL,
  control_owner_name VARCHAR(255) NULL,
  control_owner_socket_id VARCHAR(128) NULL,
  control_expires_at_ms BIGINT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;

          try {
                    db()->exec($sql);
          } catch (\Throwable $e) {
                    error_log('[match-session] ensure table failed: ' . $e->getMessage());
          }
}

function match_session_snapshot(int $matchId): ?array
{
          if ($matchId <= 0) {
                    return null;
          }
          match_session_ensure_table();
          $stmt = db()->prepare(
                    'SELECT match_id, playing, base_time_seconds, playback_rate, updated_at_ms,
                            control_owner_user_id, control_owner_name, control_owner_socket_id, control_expires_at_ms
                     FROM match_sessions
                     WHERE match_id = :match_id
                     LIMIT 1'
          );
          $stmt->execute(['match_id' => $matchId]);
          $row = $stmt->fetch(PDO::FETCH_ASSOC);
          if (!$row) {
                    return null;
          }
          $controlOwnerUserId = isset($row['control_owner_user_id']) ? (int)$row['control_owner_user_id'] : 0;
          $controlExpiresAt = isset($row['control_expires_at_ms']) ? (int)$row['control_expires_at_ms'] : 0;
          $controlOwner = null;
          if ($controlOwnerUserId > 0 && $controlExpiresAt > (int)round(microtime(true) * 1000)) {
                    $controlOwner = [
                              'userId' => $controlOwnerUserId,
                              'userName' => $row['control_owner_name'] ?: 'Analyst',
                              'socketId' => $row['control_owner_socket_id'] ?: null,
                              'expiresAt' => $controlExpiresAt,
                    ];
          }

          return [
                    'matchId' => (int)$row['match_id'],
                    'playing' => (bool)$row['playing'],
                    'baseTime' => isset($row['base_time_seconds']) ? (float)$row['base_time_seconds'] : 0.0,
                    'rate' => isset($row['playback_rate']) ? (float)$row['playback_rate'] : 1.0,
                    'updatedAt' => isset($row['updated_at_ms']) ? (int)$row['updated_at_ms'] : 0,
                    'controlOwner' => $controlOwner,
          ];
}
