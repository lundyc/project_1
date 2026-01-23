<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/event_validation.php';
require_once __DIR__ . '/player_name_helper.php';

function ensure_default_event_types(int $clubId): void
{
          $pdo = db();

          $existing = $pdo->prepare('SELECT COUNT(*) FROM event_types WHERE club_id = :club_id');
          $existing->execute(['club_id' => $clubId]);
          if ((int)$existing->fetchColumn() > 0) {
                    return;
          }

          $defaults = [
                    ['type_key' => 'period_start', 'label' => 'Period Start', 'importance' => 3],
                    ['type_key' => 'period_end', 'label' => 'Period End', 'importance' => 3],
                    ['type_key' => 'shot', 'label' => 'Shot', 'importance' => 3],
                    ['type_key' => 'goal', 'label' => 'Goal', 'importance' => 5],
                    ['type_key' => 'foul', 'label' => 'Foul', 'importance' => 3],
                    ['type_key' => 'turnover', 'label' => 'Turnover', 'importance' => 2],
          ];

          $stmt = $pdo->prepare(
                    'INSERT INTO event_types (club_id, type_key, label, default_importance)
         VALUES (:club_id, :type_key, :label, :importance)'
          );

          foreach ($defaults as $def) {
                    $stmt->execute([
                              'club_id' => $clubId,
                              'type_key' => $def['type_key'],
                              'label' => $def['label'],
                              'importance' => $def['importance'],
                    ]);
          }
}

function get_event_type_id_by_key(int $clubId, string $typeKey): ?int
{
          $stmt = db()->prepare('SELECT id FROM event_types WHERE club_id = :club_id AND type_key = :type_key LIMIT 1');
          $stmt->execute([
                    'club_id' => $clubId,
                    'type_key' => $typeKey,
          ]);
          $row = $stmt->fetch();
          if (!$row) {
                    return null;
          }
          return (int)$row['id'];
}

function ensure_event_type_exists(int $clubId, string $typeKey, string $label, int $importance = 3): int
{
          $typeKey = trim($typeKey);
          if ($typeKey === '') {
                    throw new \InvalidArgumentException('type_key_required');
          }
          $label = trim($label) !== '' ? trim($label) : ucfirst($typeKey);

          $existing = get_event_type_id_by_key($clubId, $typeKey);
          if ($existing) {
                    return $existing;
          }

          try {
                    $stmt = db()->prepare(
                              'INSERT INTO event_types (club_id, type_key, label, default_importance)
               VALUES (:club_id, :type_key, :label, :importance)'
                    );
                    $stmt->execute([
                              'club_id' => $clubId,
                              'type_key' => $typeKey,
                              'label' => $label,
                              'importance' => max(1, min(5, $importance)),
                    ]);
                    return (int)db()->lastInsertId();
          } catch (\PDOException $e) {
                    if (($e->errorInfo[1] ?? null) === 1062) {
                              $existingDuplicate = get_event_type_id_by_key($clubId, $typeKey);
                              if ($existingDuplicate) {
                                        return $existingDuplicate;
                              }
                    }
                    throw $e;
          }
}

function event_list_for_match(int $matchId): array
{
          $pdo = db();
          $stmt = $pdo->prepare(
                    'SELECT e.*,
                            et.label AS event_type_label,
                            et.type_key AS event_type_key,
                            CONCAT(COALESCE(pl.first_name, \'\'), \' \', COALESCE(pl.last_name, \'\')) AS match_player_name,
                            mp.shirt_number AS match_player_shirt,
                            mp.team_side AS match_player_team_side,
                            mp.position_label AS match_player_position,
                            mp.id AS match_player_id,
                            mpd.label AS period_label,
                            c.id AS clip_id,
                            c.start_second AS clip_start_second,
                            c.end_second AS clip_end_second,
                            pl.first_name AS player_first_name,
                            pl.last_name AS player_last_name
             FROM events e
             LEFT JOIN event_types et ON et.id = e.event_type_id
             LEFT JOIN match_players mp ON mp.id = e.match_player_id
             LEFT JOIN players pl ON pl.id = mp.player_id
             LEFT JOIN match_periods mpd ON mpd.id = e.period_id
             LEFT JOIN clips c ON c.event_id = e.id
             WHERE e.match_id = :match_id
             ORDER BY e.match_second ASC, e.id ASC'
          );
          $stmt->execute(['match_id' => $matchId]);
          $events = $stmt->fetchAll();

          if (!$events) {
                    return [];
          }

          // Build full names
          foreach ($events as &$event) {
                    $event['match_player_name'] = build_full_name($event['player_first_name'] ?? null, $event['player_last_name'] ?? null);
          }

          $ids = array_column($events, 'id');
          $tagStmt = $pdo->prepare(
                    'SELECT et.event_id,
                            t.id AS tag_id,
                            t.label AS tag_label,
                            t.tag_key
             FROM event_tags et
             JOIN tags t ON t.id = et.tag_id
             WHERE et.event_id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')'
          );
          $tagStmt->execute($ids);

          $tagMap = [];
          foreach ($tagStmt->fetchAll() as $row) {
                    $tagMap[(int)$row['event_id']][] = [
                              'id' => (int)$row['tag_id'],
                              'label' => $row['tag_label'],
                              'tag_key' => $row['tag_key'],
                    ];
          }

          foreach ($events as &$event) {
                    $id = (int)$event['id'];
                    $event['tags'] = $tagMap[$id] ?? [];
          }

          return $events;
}

function event_get_by_id(int $eventId): ?array
{
          $pdo = db();
          $stmt = $pdo->prepare(
                    'SELECT e.*,
                            et.label AS event_type_label,
                            et.type_key AS event_type_key,
                            CONCAT(COALESCE(pl.first_name, \'\'), \' \', COALESCE(pl.last_name, \'\')) AS match_player_name,
                            mp.shirt_number AS match_player_shirt,
                            mp.team_side AS match_player_team_side,
                            mp.position_label AS match_player_position,
                            mp.id AS match_player_id,
                            mpd.label AS period_label,
                            c.id AS clip_id,
                            c.start_second AS clip_start_second,
                            c.end_second AS clip_end_second,
                            pl.first_name AS player_first_name,
                            pl.last_name AS player_last_name
             FROM events e
             LEFT JOIN event_types et ON et.id = e.event_type_id
             LEFT JOIN match_players mp ON mp.id = e.match_player_id
             LEFT JOIN players pl ON pl.id = mp.player_id
             LEFT JOIN match_periods mpd ON mpd.id = e.period_id
             LEFT JOIN clips c ON c.event_id = e.id
             WHERE e.id = :id
             LIMIT 1'
          );
          $stmt->execute(['id' => $eventId]);
          $event = $stmt->fetch();

          if (!$event) {
                    return null;
          }

          // Build full name
          $event['match_player_name'] = build_full_name($event['player_first_name'] ?? null, $event['player_last_name'] ?? null);

          $tagStmt = $pdo->prepare(
                    'SELECT t.id AS tag_id, t.label AS tag_label, t.tag_key
             FROM event_tags et
             JOIN tags t ON t.id = et.tag_id
             WHERE et.event_id = :event_id'
          );
          $tagStmt->execute(['event_id' => $eventId]);
          $event['tags'] = array_map(function ($row) {
                    return [
                              'id' => (int)$row['tag_id'],
                              'label' => $row['tag_label'],
                              'tag_key' => $row['tag_key'],
                    ];
          }, $tagStmt->fetchAll());

          return $event;
}

function event_create(int $matchId, array $data, array $tagIds, int $userId, bool $withTransaction = true): int
{
          $pdo = db();
          $transactionOwned = $withTransaction && !$pdo->inTransaction();
          if ($transactionOwned) {
                    $pdo->beginTransaction();
          }

          try {
                    $normalized = normalize_event_payload($data);

                    $stmt = $pdo->prepare(
                              'INSERT INTO events
                     (match_id, period_id, match_second, minute, minute_extra, team_side, event_type_id, importance, phase, is_penalty, match_player_id, opponent_detail, outcome, zone, notes, created_by)
                     VALUES
                     (:match_id, :period_id, :match_second, :minute, :minute_extra, :team_side, :event_type_id, :importance, :phase, :is_penalty, :match_player_id, :opponent_detail, :outcome, :zone, :notes, :created_by)'
                    );

                    $stmt->execute([
                              'match_id' => $matchId,
                              'period_id' => $normalized['period_id'],
                              'match_second' => $normalized['match_second'],
                              'minute' => $normalized['minute'],
                              'minute_extra' => $normalized['minute_extra'],
                              'team_side' => $normalized['team_side'],
                              'event_type_id' => $normalized['event_type_id'],
                              'importance' => $normalized['importance'],
                              'phase' => $normalized['phase'],
                              'is_penalty' => $normalized['is_penalty'],
                              'match_player_id' => $normalized['match_player_id'],
                              'opponent_detail' => $normalized['opponent_detail'],
                              'outcome' => $normalized['outcome'],
                              'zone' => $normalized['zone'],
                              'notes' => $normalized['notes'],
                              'created_by' => $userId,
                    ]);

                    $eventId = (int)$pdo->lastInsertId();

                    if (!empty($tagIds)) {
                              $tagStmt = $pdo->prepare('INSERT INTO event_tags (event_id, tag_id) VALUES (:event_id, :tag_id)');
                              foreach ($tagIds as $tagId) {
                                        $tagStmt->execute(['event_id' => $eventId, 'tag_id' => $tagId]);
                              }
                    }

                    if ($transactionOwned) {
                              $pdo->commit();
                    }
                    return $eventId;
          } catch (\Throwable $e) {
                    if ($transactionOwned) {
                              $pdo->rollBack();
                    }
                    throw $e;
          }
}

function event_update(int $eventId, array $data, array $tagIds, int $userId): void
{
          $pdo = db();
          $pdo->beginTransaction();

          try {
                    $normalized = normalize_event_payload($data);

                    $stmt = $pdo->prepare(
                              'UPDATE events
                     SET period_id = :period_id,
                         match_second = :match_second,
                         minute = :minute,
                         minute_extra = :minute_extra,
                         team_side = :team_side,
                         event_type_id = :event_type_id,
                         importance = :importance,
                         phase = :phase,
                         is_penalty = :is_penalty,
                         match_player_id = :match_player_id,
                         opponent_detail = :opponent_detail,
                         outcome = :outcome,
                         zone = :zone,
                         notes = :notes
                     WHERE id = :id'
                    );

                    $stmt->execute([
                              'period_id' => $normalized['period_id'],
                              'match_second' => $normalized['match_second'],
                              'minute' => $normalized['minute'],
                              'minute_extra' => $normalized['minute_extra'],
                              'team_side' => $normalized['team_side'],
                              'event_type_id' => $normalized['event_type_id'],
                              'importance' => $normalized['importance'],
                              'phase' => $normalized['phase'],
                              'is_penalty' => $normalized['is_penalty'],
                              'match_player_id' => $normalized['match_player_id'],
                              'opponent_detail' => $normalized['opponent_detail'],
                              'outcome' => $normalized['outcome'],
                              'zone' => $normalized['zone'],
                              'notes' => $normalized['notes'],
                              'id' => $eventId,
                    ]);

                    $pdo->prepare('DELETE FROM event_tags WHERE event_id = :event_id')
                              ->execute(['event_id' => $eventId]);

                    if (!empty($tagIds)) {
                              $tagStmt = $pdo->prepare('INSERT INTO event_tags (event_id, tag_id) VALUES (:event_id, :tag_id)');
                              foreach ($tagIds as $tagId) {
                                        $tagStmt->execute(['event_id' => $eventId, 'tag_id' => $tagId]);
                              }
                    }

                    $pdo->commit();
          } catch (\Throwable $e) {
                    $pdo->rollBack();
                    throw $e;
          }
}

function event_delete(int $eventId, int $userId): void
{
          $pdo = db();
          $pdo->beginTransaction();

          try {
                    $pdo->prepare('DELETE FROM events WHERE id = :id')
                              ->execute(['id' => $eventId]);

                    $pdo->commit();
          } catch (\Throwable $e) {
                    $pdo->rollBack();
                    throw $e;
          }
}
