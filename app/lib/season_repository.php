<?php

require_once __DIR__ . '/db.php';

function get_seasons_by_club(int $club_id): array
{
          $stmt = db()->prepare(
                    'SELECT id, name
           FROM seasons
           WHERE club_id = :club_id
           ORDER BY start_date DESC, id DESC'
          );

          $stmt->execute(['club_id' => $club_id]);

          return $stmt->fetchAll();
}

function create_season_for_club(int $club_id, string $name): int
{
          $stmt = db()->prepare('INSERT INTO seasons (club_id, name) VALUES (:club_id, :name)');
          $stmt->execute([
                    'club_id' => $club_id,
                    'name' => $name,
          ]);

          return (int)db()->lastInsertId();
}
