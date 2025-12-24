<?php

require_once __DIR__ . '/db.php';

function get_match_players(int $matchId): array
{
          $stmt = db()->prepare(
                    'SELECT id, match_id, team_side, player_id, display_name, shirt_number, position_label, is_starting
             FROM match_players
             WHERE match_id = :match_id
             ORDER BY team_side ASC, is_starting DESC, shirt_number ASC, id ASC'
          );

          $stmt->execute(['match_id' => $matchId]);

          return $stmt->fetchAll();
}

function replace_match_players(int $matchId, array $players): void
{
          $pdo = db();
          $pdo->beginTransaction();

          try {
                    $delete = $pdo->prepare('DELETE FROM match_players WHERE match_id = :match_id');
                    $delete->execute(['match_id' => $matchId]);

                    if ($players) {
                              $insert = $pdo->prepare(
                                        'INSERT INTO match_players (match_id, team_side, player_id, display_name, shirt_number, position_label, is_starting)
                             VALUES (:match_id, :team_side, :player_id, :display_name, :shirt_number, :position_label, :is_starting)'
                              );

                              foreach ($players as $player) {
                                        $insert->execute([
                                                  'match_id' => $matchId,
                                                  'team_side' => $player['team_side'],
                                                  'player_id' => $player['player_id'],
                                                  'display_name' => $player['display_name'],
                                                  'shirt_number' => $player['shirt_number'],
                                                  'position_label' => $player['position_label'],
                                                  'is_starting' => $player['is_starting'],
                                        ]);
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
          $stmt = db()->prepare('SELECT id, match_id, team_side, player_id, display_name, shirt_number, position_label, is_starting FROM match_players WHERE id = :id LIMIT 1');
          $stmt->execute(['id' => $id]);
          $row = $stmt->fetch();

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

function insert_match_player(array $data): int
{
          $stmt = db()->prepare(
                    'INSERT INTO match_players (match_id, team_side, player_id, display_name, shirt_number, position_label, is_starting)
                    VALUES (:match_id, :team_side, :player_id, :display_name, :shirt_number, :position_label, :is_starting)'
          );
          $stmt->execute([
                    'match_id' => $data['match_id'],
                    'team_side' => $data['team_side'],
                    'player_id' => $data['player_id'] ?? null,
                    'display_name' => $data['display_name'],
                    'shirt_number' => $data['shirt_number'] ?? null,
                    'position_label' => $data['position_label'] ?? null,
                    'is_starting' => isset($data['is_starting']) && $data['is_starting'] ? 1 : 0,
          ]);

          return (int)db()->lastInsertId();
}

function update_match_player(int $id, array $data): bool
{
          $stmt = db()->prepare(
                    'UPDATE match_players
                    SET shirt_number = :shirt_number, position_label = :position_label, is_starting = :is_starting
                    WHERE id = :id'
          );
          return $stmt->execute([
                    'id' => $id,
                    'shirt_number' => $data['shirt_number'] ?? null,
                    'position_label' => $data['position_label'] ?? null,
                    'is_starting' => isset($data['is_starting']) && $data['is_starting'] ? 1 : 0,
          ]);
}

function delete_match_player(int $id): bool
{
          $stmt = db()->prepare('DELETE FROM match_players WHERE id = :id');
          return $stmt->execute(['id' => $id]);
}

function get_club_players(int $clubId): array
{
          $stmt = db()->prepare(
                    'SELECT id, display_name, primary_position
             FROM players
             WHERE club_id = :club_id AND is_active = 1
             ORDER BY display_name ASC'
          );

          $stmt->execute(['club_id' => $clubId]);

          return $stmt->fetchAll();
}
