<?php

require_once __DIR__ . '/db.php';

function get_all_clubs(): array
{
          // Exclude "Opponents" club (id=3) as it's just a container for opponent teams
          $stmt = db()->query('SELECT id, name FROM clubs WHERE id != 3 ORDER BY id ASC');

          return $stmt->fetchAll();
}

function get_club_by_id(int $clubId): ?array
{
          $stmt = db()->prepare('SELECT id, name FROM clubs WHERE id = :id LIMIT 1');
          $stmt->execute(['id' => $clubId]);

          $club = $stmt->fetch();
          return $club ?: null;
}

function create_club(string $name): int
{
          $pdo = db();

          $stmt = $pdo->prepare(
                    'INSERT INTO clubs (name) VALUES (:name)'
          );

          $stmt->execute(['name' => $name]);

          return (int)$pdo->lastInsertId();
}
