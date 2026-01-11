<?php

require_once __DIR__ . '/db.php';

function playlist_list_for_match(int $matchId): array
{
          $stmt = db()->prepare(
                    'SELECT p.*,
                            (SELECT COUNT(*) FROM playlist_clips pc WHERE pc.playlist_id = p.id) AS clip_count,
                            (
                                      SELECT GROUP_CONCAT(DISTINCT ev.team_side ORDER BY FIELD(ev.team_side, \'home\', \'away\') SEPARATOR \',\')
                                      FROM playlist_clips pc
                                      JOIN clips c ON c.id = pc.clip_id
                                      JOIN events ev ON ev.id = c.event_id
                                      WHERE pc.playlist_id = p.id
                                        AND ev.team_side IN (\'home\', \'away\')
                            ) AS team_sides
             FROM playlists p
             WHERE p.match_id = :match_id
               AND p.deleted_at IS NULL
             ORDER BY p.created_at DESC, p.id DESC'
          );
          $stmt->execute(['match_id' => $matchId]);
          $rows = $stmt->fetchAll();

          return array_map('playlist_cast_row', $rows);
}

function playlist_get_by_id(int $playlistId, bool $allowDeleted = false): ?array
{
          $sql = 'SELECT * FROM playlists WHERE id = :id';
          if (!$allowDeleted) {
                    $sql .= ' AND deleted_at IS NULL';
          }

          $stmt = db()->prepare($sql);
          $stmt->execute(['id' => $playlistId]);
          $row = $stmt->fetch();

          return $row ? playlist_cast_row($row) : null;
}

function playlist_create(int $matchId, string $title, ?string $notes = null): array
{
          $stmt = db()->prepare('INSERT INTO playlists (match_id, title, notes) VALUES (:match_id, :title, :notes)');
          $stmt->execute([
                    'match_id' => $matchId,
                    'title' => $title,
                    'notes' => $notes,
          ]);

          return playlist_get_by_id((int)db()->lastInsertId());
}

function playlist_update_fields(int $playlistId, array $fields): array
{
          $allowed = ['title', 'notes'];
          $updates = [];
          $params = ['id' => $playlistId];

          foreach ($fields as $column => $value) {
                    if (!in_array($column, $allowed, true)) {
                              continue;
                    }
                    $updates[] = "`$column` = :$column";
                    $params[$column] = $value;
          }

          if (empty($updates)) {
                    return playlist_get_by_id($playlistId);
          }

          $stmt = db()->prepare('UPDATE playlists SET ' . implode(', ', $updates) . ' WHERE id = :id');
          $stmt->execute($params);

          return playlist_get_by_id($playlistId);
}

function playlist_soft_delete(int $playlistId): ?array
{
          $stmt = db()->prepare('UPDATE playlists SET deleted_at = current_timestamp() WHERE id = :id');
          $stmt->execute(['id' => $playlistId]);

          return playlist_get_by_id($playlistId, true);
}

function playlist_get_clips(int $playlistId): array
{
          $stmt = db()->prepare(
                    'SELECT c.*,
                            pc.sort_order
             FROM playlist_clips pc
             JOIN clips c ON c.id = pc.clip_id
             WHERE pc.playlist_id = :playlist_id
             ORDER BY pc.sort_order ASC, pc.clip_id ASC'
          );
          $stmt->execute(['playlist_id' => $playlistId]);

          $rows = $stmt->fetchAll();

          return array_map('playlist_cast_clip_row', $rows);
}

function playlist_get_clip_details(int $playlistId, int $clipId): ?array
{
          $stmt = db()->prepare(
                    'SELECT c.*,
                            pc.sort_order
             FROM playlist_clips pc
             JOIN clips c ON c.id = pc.clip_id
             WHERE pc.playlist_id = :playlist_id
               AND pc.clip_id = :clip_id
             LIMIT 1'
          );
          $stmt->execute(['playlist_id' => $playlistId, 'clip_id' => $clipId]);
          $row = $stmt->fetch();

          return $row ? playlist_cast_clip_row($row) : null;
}

function playlist_get_clip_ids(int $playlistId): array
{
          $stmt = db()->prepare('SELECT clip_id FROM playlist_clips WHERE playlist_id = :playlist_id ORDER BY sort_order ASC, clip_id ASC');
          $stmt->execute(['playlist_id' => $playlistId]);

          return array_map(fn($row) => (int)$row['clip_id'], $stmt->fetchAll());
}

function playlist_add_clip(int $playlistId, int $clipId, ?int $sortOrder = null): array
{
          $pdo = db();
          $ownsTransaction = !$pdo->inTransaction();
          if ($ownsTransaction) {
                    $pdo->beginTransaction();
          }

          try {
                    $check = $pdo->prepare('SELECT 1 FROM playlist_clips WHERE playlist_id = :playlist_id AND clip_id = :clip_id LIMIT 1');
                    $check->execute(['playlist_id' => $playlistId, 'clip_id' => $clipId]);
                    if ($check->fetch()) {
                              throw new RuntimeException('duplicate_clip');
                    }

                    if ($sortOrder === null) {
                              $orderStmt = $pdo->prepare('SELECT COALESCE(MAX(sort_order), -1) + 1 AS next_order FROM playlist_clips WHERE playlist_id = :playlist_id');
                              $orderStmt->execute(['playlist_id' => $playlistId]);
                              $sortOrder = (int)$orderStmt->fetchColumn();
                    }

                    $insert = $pdo->prepare('INSERT INTO playlist_clips (playlist_id, clip_id, sort_order) VALUES (:playlist_id, :clip_id, :sort_order)');
                    $insert->execute([
                              'playlist_id' => $playlistId,
                              'clip_id' => $clipId,
                              'sort_order' => $sortOrder,
                    ]);

                    if ($ownsTransaction) {
                              $pdo->commit();
                    }

                    $clip = playlist_get_clip_details($playlistId, $clipId);
                    if (!$clip) {
                              throw new RuntimeException('playlist_clip_missing');
                    }

                    return $clip;
          } catch (\Throwable $e) {
                    if ($ownsTransaction) {
                              $pdo->rollBack();
                    }
                    throw $e;
          }
}

function playlist_remove_clip(int $playlistId, int $clipId): bool
{
          $stmt = db()->prepare('DELETE FROM playlist_clips WHERE playlist_id = :playlist_id AND clip_id = :clip_id');
          $stmt->execute(['playlist_id' => $playlistId, 'clip_id' => $clipId]);

          return $stmt->rowCount() > 0;
}

function playlist_reorder_clips(int $playlistId, array $ordering): void
{
          if (empty($ordering)) {
                    return;
          }

          $pdo = db();
          $ownsTransaction = !$pdo->inTransaction();
          if ($ownsTransaction) {
                    $pdo->beginTransaction();
          }

          try {
                    $stmt = $pdo->prepare('UPDATE playlist_clips SET sort_order = :sort_order WHERE playlist_id = :playlist_id AND clip_id = :clip_id');
                    foreach ($ordering as $clipId => $sortOrder) {
                              $stmt->execute([
                                        'sort_order' => $sortOrder,
                                        'playlist_id' => $playlistId,
                                        'clip_id' => $clipId,
                              ]);
                              if ($stmt->rowCount() === 0) {
                                        throw new RuntimeException('playlist_clip_not_found');
                              }
                    }
                    if ($ownsTransaction) {
                              $pdo->commit();
                    }
          } catch (\Throwable $e) {
                    if ($ownsTransaction) {
                              $pdo->rollBack();
                    }
                    throw $e;
          }
}

function playlist_get_clip_for_match(int $clipId, int $matchId): ?array
{
          $stmt = db()->prepare('SELECT * FROM clips WHERE id = :clip_id AND match_id = :match_id LIMIT 1');
          $stmt->execute(['clip_id' => $clipId, 'match_id' => $matchId]);

          $row = $stmt->fetch();

          return $row ?: null;
}

function playlist_cast_row(array $row): array
{
          if (isset($row['id'])) {
                    $row['id'] = (int)$row['id'];
          }
          if (isset($row['match_id'])) {
                    $row['match_id'] = (int)$row['match_id'];
          }
          if (isset($row['clip_count'])) {
                    $row['clip_count'] = (int)$row['clip_count'];
          }
          if (isset($row['team_sides'])) {
                    $sides = array_filter(array_map('trim', explode(',', (string)$row['team_sides'])), fn($value) => $value !== '');
                    $row['team_sides'] = array_values($sides);
          } else {
                    $row['team_sides'] = [];
          }
          return $row;
}

function playlist_cast_clip_row(array $row): array
{
          if (isset($row['clip_id'])) {
                    $row['clip_id'] = (int)$row['clip_id'];
          }
          if (isset($row['id'])) {
                    $row['id'] = (int)$row['id'];
          }
          if (isset($row['match_id'])) {
                    $row['match_id'] = (int)$row['match_id'];
          }
          if (isset($row['sort_order'])) {
                    $row['sort_order'] = (int)$row['sort_order'];
          }
          if (isset($row['start_second'])) {
                    $row['start_second'] = (int)$row['start_second'];
          }
          if (isset($row['end_second'])) {
                    $row['end_second'] = (int)$row['end_second'];
          }
          if (isset($row['duration_seconds'])) {
                    $row['duration_seconds'] = (int)$row['duration_seconds'];
          }
          if (isset($row['created_by'])) {
                    $row['created_by'] = (int)$row['created_by'];
          }
          if (isset($row['updated_by'])) {
                    $row['updated_by'] = $row['updated_by'] !== null ? (int)$row['updated_by'] : null;
          }
          if (isset($row['generation_version'])) {
                    $row['generation_version'] = (int)$row['generation_version'];
          }
          if (isset($row['is_valid'])) {
                    $row['is_valid'] = (int)$row['is_valid'];
          }
          return $row;
}
