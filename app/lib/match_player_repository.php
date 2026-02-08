<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/player_name_helper.php';

function match_players_supports_captain_column(): bool
{
          static $supported;
          if ($supported !== null) {
                    return $supported;
          }
          try {
                    db()->query('SELECT is_captain FROM match_players LIMIT 1');
                    $supported = true;
          } catch (\Throwable $e) {
                    $supported = false;
          }
          return $supported;
}

function get_match_players(int $matchId): array
{
          $supportsCaptain = match_players_supports_captain_column();
          $selectColumns = 'mp.id, mp.match_id, mp.team_side, mp.player_id, mp.shirt_number, mp.position_label, mp.is_starting';
          $orderSuffix = '';
          if ($supportsCaptain) {
                    $selectColumns .= ', mp.is_captain';
                    $orderSuffix = ', mp.is_captain DESC';
          }
          $selectColumns .= ', COALESCE(p.first_name, \'\') AS first_name, COALESCE(p.last_name, \'\') AS last_name';
          $stmt = db()->prepare(
                    "SELECT {$selectColumns}
             FROM match_players mp
             LEFT JOIN players p ON p.id = mp.player_id
             WHERE mp.match_id = :match_id
             ORDER BY mp.team_side ASC, mp.is_starting DESC{$orderSuffix}, COALESCE(mp.shirt_number, mp.id) ASC"
          );

          $stmt->execute(['match_id' => $matchId]);
          $players = $stmt->fetchAll();

          // Build full names
          foreach ($players as &$player) {
                    $player['display_name'] = build_full_name($player['first_name'], $player['last_name']);
          }

          return $players;
}

function replace_match_players(int $matchId, array $players): void
{
          $pdo = db();
          $supportsCaptain = match_players_supports_captain_column();
          $pdo->beginTransaction();

          try {
                    $delete = $pdo->prepare('DELETE FROM match_players WHERE match_id = :match_id');
                    $delete->execute(['match_id' => $matchId]);

                    if ($players) {
                              $columns = 'match_id, team_side, player_id, shirt_number, position_label, is_starting';
                              $placeholders = ':match_id, :team_side, :player_id, :shirt_number, :position_label, :is_starting';
                              if ($supportsCaptain) {
                                        $columns .= ', is_captain';
                                        $placeholders .= ', :is_captain';
                              }
                              $insert = $pdo->prepare(
                                        "INSERT INTO match_players ({$columns})
                             VALUES ({$placeholders})"
                              );

                              foreach ($players as $player) {
                                        $params = [
                                                  'match_id' => $matchId,
                                                  'team_side' => $player['team_side'],
                                                  'player_id' => $player['player_id'] ?? null,
                                                  'shirt_number' => $player['shirt_number'],
                                                  'position_label' => $player['position_label'],
                                                  'is_starting' => $player['is_starting'],
                                        ];
                                        if ($supportsCaptain) {
                                                  $params['is_captain'] = isset($player['is_captain']) && $player['is_captain'] ? 1 : 0;
                                        }
                                        $insert->execute($params);
                              }
                    }

                    $pdo->commit();
          } catch (\Throwable $e) {
                    $pdo->rollBack();
                    throw $e;
          }
}

function get_match_player(int $id): ?array
{
          $supportsCaptain = match_players_supports_captain_column();
          $selectColumns = 'mp.id, mp.match_id, mp.team_side, mp.player_id, mp.shirt_number, mp.position_label, mp.is_starting';
          if ($supportsCaptain) {
                    $selectColumns .= ', mp.is_captain';
          }
          $selectColumns .= ', COALESCE(p.first_name, \'\') AS first_name, COALESCE(p.last_name, \'\') AS last_name';
          $stmt = db()->prepare(
                    "SELECT {$selectColumns}
             FROM match_players mp
             LEFT JOIN players p ON p.id = mp.player_id
             WHERE mp.id = :id
             LIMIT 1"
          );
          $stmt->execute(['id' => $id]);
          $row = $stmt->fetch();

          if ($row) {
                    $row['display_name'] = build_full_name($row['first_name'], $row['last_name']);
          }

          return $row ?: null;
}

function find_match_player_by_player(int $matchId, string $teamSide, int $playerId): ?array
{
          $stmt = db()->prepare('SELECT id FROM match_players WHERE match_id = :match_id AND team_side = :team_side AND player_id = :player_id LIMIT 1');
          $stmt->execute([
                    'match_id' => $matchId,
                    'team_side' => $teamSide,
                    'player_id' => $playerId,
          ]);
          $row = $stmt->fetch();

          return $row ?: null;
}

function clear_team_captain(int $matchId, string $teamSide): void
{
          if (!match_players_supports_captain_column()) {
                    return;
          }
          $stmt = db()->prepare('UPDATE match_players SET is_captain = 0 WHERE match_id = :match_id AND team_side = :team_side');
          $stmt->execute([
                    'match_id' => $matchId,
                    'team_side' => $teamSide,
          ]);
}

function insert_match_player(array $data): int
{
          $supportsCaptain = match_players_supports_captain_column();
          $columns = 'match_id, team_side, player_id, shirt_number, position_label, is_starting';
          $placeholders = ':match_id, :team_side, :player_id, :shirt_number, :position_label, :is_starting';
          if ($supportsCaptain) {
                    $columns .= ', is_captain';
                    $placeholders .= ', :is_captain';
          }
          $stmt = db()->prepare(
                    "INSERT INTO match_players ({$columns})
                    VALUES ({$placeholders})"
          );
          $params = [
                    'match_id' => $data['match_id'],
                    'team_side' => $data['team_side'],
                    'player_id' => $data['player_id'] ?? null,
                    'shirt_number' => $data['shirt_number'] ?? null,
                    'position_label' => $data['position_label'] ?? null,
                    'is_starting' => isset($data['is_starting']) && $data['is_starting'] ? 1 : 0,
          ];
          if ($supportsCaptain) {
                    $params['is_captain'] = isset($data['is_captain']) && $data['is_captain'] ? 1 : 0;
          }
          $stmt->execute($params);

          return (int)db()->lastInsertId();
}

function update_match_player(int $id, array $data): bool
{
          $supportsCaptain = match_players_supports_captain_column();
          $setClauses = 'shirt_number = :shirt_number, position_label = :position_label, is_starting = :is_starting';
          if ($supportsCaptain) {
                    $setClauses .= ', is_captain = :is_captain';
          }
          $stmt = db()->prepare(
                    "UPDATE match_players
                    SET {$setClauses}
                    WHERE id = :id"
          );
          $params = [
                    'id' => $id,
                    'shirt_number' => $data['shirt_number'] ?? null,
                    'position_label' => $data['position_label'] ?? null,
                    'is_starting' => isset($data['is_starting']) && $data['is_starting'] ? 1 : 0,
          ];
          if ($supportsCaptain) {
                    $params['is_captain'] = isset($data['is_captain']) && $data['is_captain'] ? 1 : 0;
          }
          return $stmt->execute($params);
}

function delete_match_player(int $id): bool
{
          $countStmt = db()->prepare(
                    'SELECT COUNT(*) FROM match_substitutions
             WHERE player_off_match_player_id = :id_off OR player_on_match_player_id = :id_on'
          );
          $countStmt->execute(['id_off' => $id, 'id_on' => $id]);
          $count = (int)$countStmt->fetchColumn();
          if ($count > 0) {
                    throw new \RuntimeException('This player cannot be removed because they were involved in a substitution.');
          }

          $stmt = db()->prepare('DELETE FROM match_players WHERE id = :id');
          return $stmt->execute(['id' => $id]);
}

function get_club_players(int $clubId, bool $includeInactive = false): array
{
          $whereClause = 'WHERE club_id = :club_id';
          if (!$includeInactive) {
                    $whereClause .= ' AND is_active = 1';
          }

          $stmt = db()->prepare(
                    'SELECT id, first_name, last_name, primary_position, team_id, is_active
             FROM players
             ' . $whereClause . '
             ORDER BY is_active DESC, first_name ASC, last_name ASC'
          );

          $stmt->execute(['club_id' => $clubId]);

          $rows = $stmt->fetchAll();
          
          // Build display_name for each player
          foreach ($rows as &$row) {
                    $row['display_name'] = build_full_name($row['first_name'], $row['last_name']);
          }
          
          return $rows;
}
