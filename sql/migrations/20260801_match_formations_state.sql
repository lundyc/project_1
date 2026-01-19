-- Deduplicate and lock match formation rows to one row per team.

DELETE mf1
FROM match_formations mf1
JOIN match_formations mf2
  ON mf1.match_id = mf2.match_id
 AND mf1.team_side = mf2.team_side
 AND mf1.id < mf2.id;

ALTER TABLE match_formations
  ADD UNIQUE KEY uq_match_team (match_id, team_side);
