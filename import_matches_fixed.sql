-- Import Historical Matches for Saltcoats Victoria F.C.

SET @season_id = (SELECT id FROM seasons WHERE club_id = 1 LIMIT 1);
SET @club_id = 1;
SET @saltcoats_team_id = 2;
SET @goal_type_id = (SELECT id FROM event_types WHERE club_id = 1 AND type_key = 'goal' LIMIT 1);

-- Create/Get competitions
INSERT IGNORE INTO competitions (club_id, name, created_at)
VALUES 
  (1, 'Fourth Division', NOW()),
  (1, 'Finest Carmats South Region Challenge Cup - Round 1', NOW()),
  (1, '3 Pillars Financial Planning Scottish Communities Cup - Round 2', NOW()),
  (1, 'Strathclyde Demolition West Of Scotland League Cup - Round 2', NOW()),
  (1, '3 Pillars Financial Planning Scottish Communities Cup - Round 3', NOW());

-- Create/Get opponent teams
INSERT IGNORE INTO teams (club_id, name, team_type, created_at)
VALUES
  (1, 'Campbeltown Pupils', 'opponent', NOW()),
  (1, 'Wishaw', 'opponent', NOW()),
  (1, 'Vale of Leven', 'opponent', NOW()),
  (1, 'East Kilbride YM', 'opponent', NOW()),
  (1, 'Easthouses Lily', 'opponent', NOW()),
  (1, 'Newmains United', 'opponent', NOW()),
  (1, 'Giffnock', 'opponent', NOW()),
  (1, 'West Park United', 'opponent', NOW()),
  (1, 'Coupar Angus', 'opponent', NOW()),
  (1, 'St. Peter\'s', 'opponent', NOW()),
  (1, 'Neilston', 'opponent', NOW()),
  (1, 'Eglinton', 'opponent', NOW()),
  (1, 'Dyce', 'opponent', NOW()),
  (1, 'Irvine Victoria', 'opponent', NOW()),
  (1, 'Carluke Rovers', 'opponent', NOW()),
  (1, 'Royal Albert', 'opponent', NOW()),
  (1, 'East Kilbride Thistle', 'opponent', NOW());

-- Match 1: Campbeltown Pupils 1-4 Saltcoats Victoria
INSERT IGNORE INTO matches (club_id, season_id, competition_id, home_team_id, away_team_id, kickoff_at, status, created_by, created_at) VALUES (1, @season_id, (SELECT id FROM competitions WHERE name = 'Fourth Division' LIMIT 1), (SELECT id FROM teams WHERE name = 'Campbeltown Pupils' LIMIT 1), 2, '2025-07-26 14:30:00', 'ready', 1, NOW());
SET @match_id = LAST_INSERT_ID();
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'home', 1, NOW());
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'away', 1, NOW());
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'away', 1, NOW());
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'away', 1, NOW());
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'away', 1, NOW());

-- Match 2: Saltcoats Victoria 5-1 Wishaw
INSERT IGNORE INTO matches (club_id, season_id, competition_id, home_team_id, away_team_id, kickoff_at, status, created_by, created_at) VALUES (1, @season_id, (SELECT id FROM competitions WHERE name = 'Fourth Division' LIMIT 1), 2, (SELECT id FROM teams WHERE name = 'Wishaw' LIMIT 1), '2025-07-30 19:30:00', 'ready', 1, NOW());
SET @match_id = LAST_INSERT_ID();
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'home', 1, NOW());
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'home', 1, NOW());
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'home', 1, NOW());
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'home', 1, NOW());
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'home', 1, NOW());
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'away', 1, NOW());

-- Match 3: Vale of Leven 4-0 Saltcoats Victoria
INSERT IGNORE INTO matches (club_id, season_id, competition_id, home_team_id, away_team_id, kickoff_at, status, created_by, created_at) VALUES (1, @season_id, (SELECT id FROM competitions WHERE name = 'Fourth Division' LIMIT 1), (SELECT id FROM teams WHERE name = 'Vale of Leven' LIMIT 1), 2, '2025-08-02 14:00:00', 'ready', 1, NOW());
SET @match_id = LAST_INSERT_ID();
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'home', 1, NOW());
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'home', 1, NOW());
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'home', 1, NOW());
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'home', 1, NOW());

-- Match 4: Saltcoats Victoria 0-2 East Kilbride YM
INSERT IGNORE INTO matches (club_id, season_id, competition_id, home_team_id, away_team_id, kickoff_at, status, created_by, created_at) VALUES (1, @season_id, (SELECT id FROM competitions WHERE name = 'Fourth Division' LIMIT 1), 2, (SELECT id FROM teams WHERE name = 'East Kilbride YM' LIMIT 1), '2025-08-09 14:00:00', 'ready', 1, NOW());
SET @match_id = LAST_INSERT_ID();
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'away', 1, NOW());
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'away', 1, NOW());

-- Match 5: Easthouses Lily 5-1 Saltcoats Victoria
INSERT IGNORE INTO matches (club_id, season_id, competition_id, home_team_id, away_team_id, kickoff_at, status, created_by, created_at) VALUES (1, @season_id, (SELECT id FROM competitions WHERE name = 'Finest Carmats South Region Challenge Cup - Round 1' LIMIT 1), (SELECT id FROM teams WHERE name = 'Easthouses Lily' LIMIT 1), 2, '2025-08-16 14:30:00', 'ready', 1, NOW());
SET @match_id = LAST_INSERT_ID();
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'home', 1, NOW());
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'home', 1, NOW());
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'home', 1, NOW());
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'home', 1, NOW());
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'home', 1, NOW());
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'away', 1, NOW());

-- Match 6: Saltcoats Victoria 0-1 Newmains United
INSERT IGNORE INTO matches (club_id, season_id, competition_id, home_team_id, away_team_id, kickoff_at, status, created_by, created_at) VALUES (1, @season_id, (SELECT id FROM competitions WHERE name = 'Fourth Division' LIMIT 1), 2, (SELECT id FROM teams WHERE name = 'Newmains United' LIMIT 1), '2025-08-23 14:00:00', 'ready', 1, NOW());
SET @match_id = LAST_INSERT_ID();
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'away', 1, NOW());

-- Match 7: Giffnock 2-1 Saltcoats Victoria
INSERT IGNORE INTO matches (club_id, season_id, competition_id, home_team_id, away_team_id, kickoff_at, status, created_by, created_at) VALUES (1, @season_id, (SELECT id FROM competitions WHERE name = 'Fourth Division' LIMIT 1), (SELECT id FROM teams WHERE name = 'Giffnock' LIMIT 1), 2, '2025-08-30 14:00:00', 'ready', 1, NOW());
SET @match_id = LAST_INSERT_ID();
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'home', 1, NOW());
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'home', 1, NOW());
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'away', 1, NOW());

-- Match 8: West Park United 2-0 Saltcoats Victoria
INSERT IGNORE INTO matches (club_id, season_id, competition_id, home_team_id, away_team_id, kickoff_at, status, created_by, created_at) VALUES (1, @season_id, (SELECT id FROM competitions WHERE name = 'Fourth Division' LIMIT 1), (SELECT id FROM teams WHERE name = 'West Park United' LIMIT 1), 2, '2025-09-06 14:00:00', 'ready', 1, NOW());
SET @match_id = LAST_INSERT_ID();
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'home', 1, NOW());
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'home', 1, NOW());

-- Match 9: Coupar Angus 1-2 Saltcoats Victoria
INSERT IGNORE INTO matches (club_id, season_id, competition_id, home_team_id, away_team_id, kickoff_at, status, created_by, created_at) VALUES (1, @season_id, (SELECT id FROM competitions WHERE name = '3 Pillars Financial Planning Scottish Communities Cup - Round 2' LIMIT 1), (SELECT id FROM teams WHERE name = 'Coupar Angus' LIMIT 1), 2, '2025-09-20 14:30:00', 'ready', 1, NOW());
SET @match_id = LAST_INSERT_ID();
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'home', 1, NOW());
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'away', 1, NOW());
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'away', 1, NOW());

-- Match 10: Saltcoats Victoria 1-3 St. Peter's
INSERT IGNORE INTO matches (club_id, season_id, competition_id, home_team_id, away_team_id, kickoff_at, status, created_by, created_at) VALUES (1, @season_id, (SELECT id FROM competitions WHERE name = 'Fourth Division' LIMIT 1), 2, (SELECT id FROM teams WHERE name = 'St. Peter\'s' LIMIT 1), '2025-09-27 14:00:00', 'ready', 1, NOW());
SET @match_id = LAST_INSERT_ID();
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'home', 1, NOW());
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'away', 1, NOW());
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'away', 1, NOW());
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'away', 1, NOW());

-- Match 11: Saltcoats Victoria 1-3 Neilston
INSERT IGNORE INTO matches (club_id, season_id, competition_id, home_team_id, away_team_id, kickoff_at, status, created_by, created_at) VALUES (1, @season_id, (SELECT id FROM competitions WHERE name = 'Strathclyde Demolition West Of Scotland League Cup - Round 2' LIMIT 1), 2, (SELECT id FROM teams WHERE name = 'Neilston' LIMIT 1), '2025-10-04 14:00:00', 'ready', 1, NOW());
SET @match_id = LAST_INSERT_ID();
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'home', 1, NOW());
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'away', 1, NOW());
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'away', 1, NOW());
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'away', 1, NOW());

-- Match 12: Eglinton 0-0 Saltcoats Victoria
INSERT IGNORE INTO matches (club_id, season_id, competition_id, home_team_id, away_team_id, kickoff_at, status, created_by, created_at) VALUES (1, @season_id, (SELECT id FROM competitions WHERE name = 'Fourth Division' LIMIT 1), (SELECT id FROM teams WHERE name = 'Eglinton' LIMIT 1), 2, '2025-10-11 14:00:00', 'ready', 1, NOW());

-- Match 13: Dyce 5-1 Saltcoats Victoria
INSERT IGNORE INTO matches (club_id, season_id, competition_id, home_team_id, away_team_id, kickoff_at, status, created_by, created_at) VALUES (1, @season_id, (SELECT id FROM competitions WHERE name = '3 Pillars Financial Planning Scottish Communities Cup - Round 3' LIMIT 1), (SELECT id FROM teams WHERE name = 'Dyce' LIMIT 1), 2, '2025-10-18 14:30:00', 'ready', 1, NOW());
SET @match_id = LAST_INSERT_ID();
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'home', 1, NOW());
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'home', 1, NOW());
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'home', 1, NOW());
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'home', 1, NOW());
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'home', 1, NOW());
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'away', 1, NOW());

-- Match 14: Irvine Victoria 1-2 Saltcoats Victoria
INSERT IGNORE INTO matches (club_id, season_id, competition_id, home_team_id, away_team_id, kickoff_at, status, created_by, created_at) VALUES (1, @season_id, (SELECT id FROM competitions WHERE name = 'Fourth Division' LIMIT 1), (SELECT id FROM teams WHERE name = 'Irvine Victoria' LIMIT 1), 2, '2025-10-25 14:00:00', 'ready', 1, NOW());
SET @match_id = LAST_INSERT_ID();
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'home', 1, NOW());
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'away', 1, NOW());
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'away', 1, NOW());

-- Match 15: Saltcoats Victoria 2-1 Carluke Rovers
INSERT IGNORE INTO matches (club_id, season_id, competition_id, home_team_id, away_team_id, kickoff_at, status, created_by, created_at) VALUES (1, @season_id, (SELECT id FROM competitions WHERE name = 'Fourth Division' LIMIT 1), 2, (SELECT id FROM teams WHERE name = 'Carluke Rovers' LIMIT 1), '2025-11-08 14:00:00', 'ready', 1, NOW());
SET @match_id = LAST_INSERT_ID();
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'home', 1, NOW());
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'home', 1, NOW());
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'away', 1, NOW());

-- Match 16: Saltcoats Victoria 0-1 Royal Albert
INSERT IGNORE INTO matches (club_id, season_id, competition_id, home_team_id, away_team_id, kickoff_at, status, created_by, created_at) VALUES (1, @season_id, (SELECT id FROM competitions WHERE name = 'Fourth Division' LIMIT 1), 2, (SELECT id FROM teams WHERE name = 'Royal Albert' LIMIT 1), '2025-11-22 13:30:00', 'ready', 1, NOW());
SET @match_id = LAST_INSERT_ID();
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'away', 1, NOW());

-- Match 17: East Kilbride Thistle 3-4 Saltcoats Victoria
INSERT IGNORE INTO matches (club_id, season_id, competition_id, home_team_id, away_team_id, kickoff_at, status, created_by, created_at) VALUES (1, @season_id, (SELECT id FROM competitions WHERE name = 'Fourth Division' LIMIT 1), (SELECT id FROM teams WHERE name = 'East Kilbride Thistle' LIMIT 1), 2, '2025-11-29 13:30:00', 'ready', 1, NOW());
SET @match_id = LAST_INSERT_ID();
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'home', 1, NOW());
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'home', 1, NOW());
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'home', 1, NOW());
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'away', 1, NOW());
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'away', 1, NOW());
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'away', 1, NOW());
INSERT INTO events (match_id, event_type_id, team_side, created_by, created_at) VALUES (@match_id, @goal_type_id, 'away', 1, NOW());

SELECT 'Historical matches import complete!' AS status;
