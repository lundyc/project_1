-- Audit-driven index additions (2026-02-12)
-- Targets: league intelligence match preview + match list + roster lookups

-- Speed up latest match video lookup (match_videos subselects by match_id + id desc)
CREATE INDEX IF NOT EXISTS idx_match_videos_match_id_id
ON match_videos(match_id, id);

-- Speed up match list pagination by club + kickoff ordering
CREATE INDEX IF NOT EXISTS idx_matches_club_kickoff_id
ON matches(club_id, kickoff_at DESC, id DESC);

-- Speed up league intelligence recent matches by team/season/kickoff
CREATE INDEX IF NOT EXISTS idx_li_matches_home_season_kickoff
ON league_intelligence_matches(home_team_id, season_id, kickoff_at);

CREATE INDEX IF NOT EXISTS idx_li_matches_away_season_kickoff
ON league_intelligence_matches(away_team_id, season_id, kickoff_at);

-- Speed up formation aggregation by match/team
CREATE INDEX IF NOT EXISTS idx_match_formations_match_team
ON match_formations(match_id, team_side, formation_key, format);

-- Speed up recent starting XI aggregation by match/team
CREATE INDEX IF NOT EXISTS idx_match_players_match_team_starting
ON match_players(match_id, team_side, is_starting);
