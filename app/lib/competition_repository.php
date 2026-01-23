<?php

require_once __DIR__ . '/db.php';

function get_competitions_by_club(int $club_id): array
{
             $stmt = db()->prepare(
                          'SELECT id, name, type, season_id
              FROM competitions
              WHERE club_id = :club_id
              ORDER BY type DESC, name ASC, id DESC'
             );

             $stmt->execute(['club_id' => $club_id]);

             return $stmt->fetchAll();
}

function get_competition_by_id(int $id): ?array
{
             $stmt = db()->prepare('SELECT * FROM competitions WHERE id = :id LIMIT 1');
             $stmt->execute(['id' => $id]);

             $competition = $stmt->fetch();

             return $competition ?: null;
}

function create_competition_for_club(int $club_id, int $season_id, string $name, string $type): int
{
             $stmt = db()->prepare('INSERT INTO competitions (club_id, season_id, name, type) VALUES (:club_id, :season_id, :name, :type)');
             $stmt->execute([
                          'club_id' => $club_id,
                          'season_id' => $season_id,
                          'name' => $name,
                          'type' => $type,
             ]);

             return (int)db()->lastInsertId();
}

function update_competition_for_club(int $id, int $club_id, int $season_id, string $name, string $type): bool
{
             $stmt = db()->prepare('UPDATE competitions SET season_id = :season_id, name = :name, type = :type WHERE id = :id AND club_id = :club_id');

             return $stmt->execute([
                          'id' => $id,
                          'club_id' => $club_id,
                          'season_id' => $season_id,
                          'name' => $name,
                          'type' => $type,
             ]);
}

function delete_competition_for_club(int $id, int $club_id): bool
{
             if (competition_has_matches($id)) {
                          return false;
             }

             $stmt = db()->prepare('DELETE FROM competitions WHERE id = :id AND club_id = :club_id');

             return $stmt->execute([
                          'id' => $id,
                          'club_id' => $club_id,
             ]);
}

function competition_has_matches(int $competition_id): bool
{
             $stmt = db()->prepare('SELECT COUNT(*) FROM matches WHERE competition_id = :competition_id');
             $stmt->execute(['competition_id' => $competition_id]);

             return (int)$stmt->fetchColumn() > 0;
}

function list_competition_teams(int $competition_id): array
{
             $stmt = db()->prepare(
                          'SELECT ct.id, t.id AS team_id, t.name, t.team_type
                 FROM competition_teams ct
                 INNER JOIN teams t ON t.id = ct.team_id
                 WHERE ct.competition_id = :competition_id
                 ORDER BY t.name ASC, t.id ASC'
             );
             $stmt->execute(['competition_id' => $competition_id]);

             return $stmt->fetchAll();
}

function add_team_to_competition(int $competition_id, int $team_id): bool
{
             // Ensure competition and team belong to the same club
             $stmt = db()->prepare('SELECT c.club_id AS competition_club, t.club_id AS team_club FROM competitions c JOIN teams t ON t.id = :team_id WHERE c.id = :competition_id LIMIT 1');
             $stmt->execute([
                          'competition_id' => $competition_id,
                          'team_id' => $team_id,
             ]);
             $row = $stmt->fetch();

             if (!$row || (int)$row['competition_club'] !== (int)$row['team_club']) {
                          return false;
             }

             $insert = db()->prepare('INSERT IGNORE INTO competition_teams (competition_id, team_id) VALUES (:competition_id, :team_id)');

             return $insert->execute([
                          'competition_id' => $competition_id,
                          'team_id' => $team_id,
             ]);
}

function remove_team_from_competition(int $competition_id, int $team_id): bool
{
             $stmt = db()->prepare('DELETE FROM competition_teams WHERE competition_id = :competition_id AND team_id = :team_id');

             return $stmt->execute([
                          'competition_id' => $competition_id,
                          'team_id' => $team_id,
             ]);
}

function is_competition_in_club(int $competition_id, int $club_id): bool
{
             $stmt = db()->prepare('SELECT 1 FROM competitions WHERE id = :competition_id AND club_id = :club_id LIMIT 1');
             $stmt->execute([
                          'competition_id' => $competition_id,
                          'club_id' => $club_id,
             ]);

             return (bool)$stmt->fetchColumn();
}
