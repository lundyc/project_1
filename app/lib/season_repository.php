<?php

require_once __DIR__ . '/db.php';

function get_seasons_by_club(int $club_id): array
{
          $stmt = db()->prepare(
                    'SELECT id, name, start_date, end_date
           FROM seasons
           WHERE club_id = :club_id
           ORDER BY start_date DESC, id DESC'
          );

          $stmt->execute(['club_id' => $club_id]);

          return $stmt->fetchAll();
}

function get_season_by_id(int $id): ?array
{
          $stmt = db()->prepare('SELECT * FROM seasons WHERE id = :id LIMIT 1');
          $stmt->execute(['id' => $id]);

          $season = $stmt->fetch();

          return $season ?: null;
}

function create_season_for_club(int $club_id, string $name, ?string $start_date = null, ?string $end_date = null): int
{
          $stmt = db()->prepare('INSERT INTO seasons (club_id, name, start_date, end_date) VALUES (:club_id, :name, :start_date, :end_date)');
          $stmt->execute([
                    'club_id' => $club_id,
                    'name' => $name,
                    'start_date' => $start_date,
                    'end_date' => $end_date,
          ]);

          return (int)db()->lastInsertId();
}

function update_season_for_club(int $id, int $club_id, string $name, ?string $start_date = null, ?string $end_date = null): bool
{
          $stmt = db()->prepare('UPDATE seasons SET name = :name, start_date = :start_date, end_date = :end_date WHERE id = :id AND club_id = :club_id');

          return $stmt->execute([
                    'id' => $id,
                    'club_id' => $club_id,
                    'name' => $name,
                    'start_date' => $start_date,
                    'end_date' => $end_date,
          ]);
}

function delete_season_for_club(int $id, int $club_id): bool
{
          if (season_has_matches($id) || season_has_competitions($id)) {
                    return false;
          }

          $stmt = db()->prepare('DELETE FROM seasons WHERE id = :id AND club_id = :club_id');

          return $stmt->execute([
                    'id' => $id,
                    'club_id' => $club_id,
          ]);
}

function season_has_matches(int $season_id): bool
{
          $stmt = db()->prepare('SELECT COUNT(*) FROM matches WHERE season_id = :season_id');
          $stmt->execute(['season_id' => $season_id]);

          return (int)$stmt->fetchColumn() > 0;
}

function season_has_competitions(int $season_id): bool
{
          $stmt = db()->prepare('SELECT COUNT(*) FROM competitions WHERE season_id = :season_id');
          $stmt->execute(['season_id' => $season_id]);

          return (int)$stmt->fetchColumn() > 0;
}

function is_season_in_club(int $season_id, int $club_id): bool
{
          $stmt = db()->prepare('SELECT 1 FROM seasons WHERE id = :id AND club_id = :club_id LIMIT 1');
          $stmt->execute([
                    'id' => $season_id,
                    'club_id' => $club_id,
          ]);

          return (bool)$stmt->fetchColumn();
}
