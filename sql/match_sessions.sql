-- Server-authoritative match playback session persistence
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
