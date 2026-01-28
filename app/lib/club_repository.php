<?php

require_once __DIR__ . '/db.php';

function get_all_clubs(): array
{
          // Exclude "Opponents" club (id=3) as it's just a container for opponent teams
          $stmt = db()->query('SELECT id, name, created_at, updated_at FROM clubs WHERE id != 3 ORDER BY id ASC');

          return $stmt->fetchAll();
}

function get_club_by_id(int $clubId): ?array
{
          $stmt = db()->prepare('SELECT id, name, created_at, updated_at FROM clubs WHERE id = :id LIMIT 1');
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

function update_club(int $clubId, string $name): bool
{
          $stmt = db()->prepare('UPDATE clubs SET name = :name WHERE id = :id');

          return $stmt->execute([
                    'id' => $clubId,
                    'name' => $name,
          ]);
}

function delete_club(int $clubId): bool
{
          $stmt = db()->prepare('DELETE FROM clubs WHERE id = :id');
          $stmt->execute(['id' => $clubId]);

          return $stmt->rowCount() > 0;
}
