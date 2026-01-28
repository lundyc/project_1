<?php

require_once __DIR__ . '/db.php';

/**
 * Fetch a team by its ID
 * @param int $team_id
 * @return array|null
 */
function get_team_by_id(int $team_id): ?array
{
    $stmt = db()->prepare('SELECT * FROM teams WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $team_id]);
    $team = $stmt->fetch(PDO::FETCH_ASSOC);
    return $team ?: null;
}

function get_teams_by_club(int $club_id): array
{
    $stmt = db()->prepare(
        'SELECT id, name, team_type, created_at, updated_at
         FROM teams
         WHERE club_id = :club_id
         ORDER BY name ASC'
    );

    $stmt->execute(['club_id' => $club_id]);

    return $stmt->fetchAll();
}

function get_teams_not_in_club(int $club_id): array
{
    $stmt = db()->prepare(
        'SELECT t.id, t.name, t.team_type, t.club_id, c.name AS club_name
         FROM teams t
         INNER JOIN clubs c ON c.id = t.club_id
         WHERE t.club_id != :club_id
         ORDER BY c.name ASC, t.name ASC'
    );

    $stmt->execute(['club_id' => $club_id]);

    return $stmt->fetchAll();
}

function get_all_teams_with_clubs(): array
{
    $stmt = db()->query(
        'SELECT t.id, t.name, t.team_type, t.club_id, t.created_at, t.updated_at, c.name AS club_name
         FROM teams t
         INNER JOIN clubs c ON c.id = t.club_id
         ORDER BY c.name ASC, t.name ASC'
    );

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

function update_team(int $team_id, int $club_id, string $name, string $team_type): bool
{
    $stmt = db()->prepare(
        'UPDATE teams
         SET club_id = :club_id, name = :name, team_type = :team_type
         WHERE id = :id'
    );

    return $stmt->execute([
        'id' => $team_id,
        'club_id' => $club_id,
        'name' => $name,
        'team_type' => $team_type,
    ]);
}

function update_team_club(int $team_id, int $club_id): bool
{
    $stmt = db()->prepare('UPDATE teams SET club_id = :club_id WHERE id = :id');

    return $stmt->execute([
        'id' => $team_id,
        'club_id' => $club_id,
    ]);
}

function delete_team(int $team_id): bool
{
    $stmt = db()->prepare('DELETE FROM teams WHERE id = :id');
    $stmt->execute(['id' => $team_id]);

    return $stmt->rowCount() > 0;
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
