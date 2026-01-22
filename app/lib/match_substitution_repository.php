<?php

require_once __DIR__ . '/db.php';

function insert_match_substitution(array $data): int
{
          $stmt = db()->prepare(
                    'INSERT INTO match_substitutions
             (match_id, team_side, match_second, minute, minute_extra, player_off_match_player_id, player_on_match_player_id, reason, event_id, created_by)
             VALUES
             (:match_id, :team_side, :match_second, :minute, :minute_extra, :player_off, :player_on, :reason, :event_id, :created_by)'
          );
          $stmt->execute([
                    'match_id' => $data['match_id'],
                    'team_side' => $data['team_side'],
                    'match_second' => $data['match_second'],
                    'minute' => $data['minute'],
                    'minute_extra' => $data['minute_extra'],
                    'player_off' => $data['player_off_match_player_id'],
                    'player_on' => $data['player_on_match_player_id'],
                    'reason' => $data['reason'],
                    'event_id' => $data['event_id'],
                    'created_by' => $data['created_by'],
          ]);

          return (int)db()->lastInsertId();
}

function get_match_substitutions(int $matchId): array
{
          $stmt = db()->prepare(
                    'SELECT ms.id, ms.match_id, ms.team_side, ms.match_second, ms.minute, ms.minute_extra, 
                            ms.player_off_match_player_id, ms.player_on_match_player_id, ms.reason, ms.event_id, ms.created_by, ms.created_at,
                            mp_off.shirt_number AS player_off_shirt,
                            COALESCE(pl_off.display_name, \'\') AS player_off_name,
                            mp_on.shirt_number AS player_on_shirt,
                            COALESCE(pl_on.display_name, \'\') AS player_on_name
             FROM match_substitutions ms
             LEFT JOIN match_players mp_off ON mp_off.id = ms.player_off_match_player_id
             LEFT JOIN players pl_off ON pl_off.id = mp_off.player_id
             LEFT JOIN match_players mp_on ON mp_on.id = ms.player_on_match_player_id
             LEFT JOIN players pl_on ON pl_on.id = mp_on.player_id
             WHERE ms.match_id = :match_id
             ORDER BY ms.id ASC'
          );
          $stmt->execute(['match_id' => $matchId]);

          return $stmt->fetchAll();
}
