<?php

require_once __DIR__ . '/db.php';

function get_competitions_by_club(int $club_id): array
{
          $stmt = db()->prepare(
                    'SELECT id, name
           FROM competitions
           WHERE club_id = :club_id
           ORDER BY name ASC, id DESC'
          );

          $stmt->execute(['club_id' => $club_id]);

          return $stmt->fetchAll();
}

function create_competition_for_club(int $club_id, string $name): int
{
          $stmt = db()->prepare('INSERT INTO competitions (club_id, name) VALUES (:club_id, :name)');
          $stmt->execute([
                    'club_id' => $club_id,
                    'name' => $name,
          ]);

          return (int)db()->lastInsertId();
}
