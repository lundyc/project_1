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
                    'SELECT id, match_id, team_side, match_second, minute, minute_extra, player_off_match_player_id, player_on_match_player_id, reason, event_id, created_by, created_at
             FROM match_substitutions
             WHERE match_id = :match_id
             ORDER BY id ASC'
          );
          $stmt->execute(['match_id' => $matchId]);

          return $stmt->fetchAll();
}
