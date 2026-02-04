-- Performance Optimization Indexes
-- Applied: 2026-02-04
-- Purpose: Fix query performance issues identified in security audit

-- Index for events query optimization (used in WHERE/ORDER BY clauses)
CREATE INDEX IF NOT EXISTS idx_events_match_second 
ON events(match_id, match_second);

-- Index for match_players JOIN optimization
CREATE INDEX IF NOT EXISTS idx_match_players_player 
ON match_players(player_id);

-- Index for clips foreign key (ON DELETE CASCADE performance)
CREATE INDEX IF NOT EXISTS idx_clips_event 
ON clips(event_id);

-- Composite index for event_tags lookups
CREATE INDEX IF NOT EXISTS idx_event_tags_event 
ON event_tags(event_id);

-- Index for match_sessions queries
CREATE INDEX IF NOT EXISTS idx_match_sessions_match 
ON match_sessions(match_id);

-- Index for rate_limit_attempts cleanup queries
CREATE INDEX IF NOT EXISTS idx_rate_limit_cleanup 
ON rate_limit_attempts(identifier, action, created_at);

-- Index for match_locks queries
CREATE INDEX IF NOT EXISTS idx_match_locks_match 
ON match_locks(match_id);

-- Index for competitions by club (stats queries)
CREATE INDEX IF NOT EXISTS idx_competitions_club_type 
ON competitions(club_id, type);

-- Index for events by team_side (stats aggregation)
CREATE INDEX IF NOT EXISTS idx_events_team_side 
ON events(match_id, team_side, event_type_id);

-- Verify index creation
SELECT 
    TABLE_NAME,
    INDEX_NAME,
    COLUMN_NAME,
    SEQ_IN_INDEX
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('events', 'match_players', 'clips', 'event_tags', 'match_sessions', 
                     'rate_limit_attempts', 'match_locks', 'competitions')
ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX;
