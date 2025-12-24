<?php

require_once __DIR__ . '/db.php';

function get_teams_by_club(int $club_id): array
{
          $stmt = db()->prepare(
                    'SELECT id, name, team_type
           FROM teams
           WHERE club_id = :club_id
           ORDER BY name ASC'
          );

          $stmt->execute(['club_id' => $club_id]);

          return $stmt->fetchAll();
}

function create_team_for_club(int $club_id, string $name, string $team_type = 'club'): int
{
          $stmt = db()->prepare(
                    'INSERT INTO teams (club_id, name, team_type)
         VALUES (:club_id, :name, :team_type)'
          );

          $stmt->execute([
                    'club_id' => $club_id,
                    'name' => $name,
                    'team_type' => $team_type,
          ]);

          return (int)db()->lastInsertId();
}

function is_team_in_club(int $team_id, int $club_id): bool
{
          $stmt = db()->prepare('SELECT 1 FROM teams WHERE id = :id AND club_id = :club_id LIMIT 1');
          $stmt->execute([
                    'id' => $team_id,
                    'club_id' => $club_id,
          ]);

          return (bool)$stmt->fetchColumn();
}
