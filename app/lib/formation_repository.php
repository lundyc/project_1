<?php

require_once __DIR__ . '/db.php';

function get_formations_with_positions(array $filters = []): array
{
          $params = [];
          $where = [];

          if (isset($filters['format']) && $filters['format'] !== '') {
                    $where[] = 'f.format = :format';
                    $params['format'] = $filters['format'];
          }
          if (array_key_exists('is_fixed', $filters)) {
                    $where[] = 'f.is_fixed = :is_fixed';
                    $params['is_fixed'] = $filters['is_fixed'] ? 1 : 0;
          }

          $sql = 'SELECT f.id AS formation_id,
                         f.format,
                         f.formation_key,
                         f.label,
                         f.player_count,
                         f.is_fixed,
                         fp.slot_index,
                         fp.position_label,
                         fp.left_percent,
                         fp.bottom_percent,
                         fp.rotation_deg
                  FROM formations f
                  LEFT JOIN formation_positions fp ON fp.formation_id = f.id';
          if ($where) {
                    $sql .= ' WHERE ' . implode(' AND ', $where);
          }
          $sql .= ' ORDER BY f.format ASC, f.formation_key ASC, fp.slot_index ASC';

          $stmt = db()->prepare($sql);
          $stmt->execute($params);
          $rows = $stmt->fetchAll();

          $formations = [];
          foreach ($rows as $row) {
                    $formationId = (int)($row['formation_id'] ?? 0);
                    if ($formationId <= 0) {
                              continue;
                    }
                    if (!isset($formations[$formationId])) {
                              $formations[$formationId] = [
                                        'id' => $formationId,
                                        'format' => $row['format'],
                                        'formation_key' => $row['formation_key'],
                                        'label' => $row['label'],
                                        'player_count' => (int)($row['player_count'] ?? 0),
                                        'is_fixed' => (bool)($row['is_fixed'] ?? 0),
                                        'positions' => [],
                              ];
                    }

                    if ($row['slot_index'] !== null) {
                              $formations[$formationId]['positions'][] = [
                                        'slot_index' => (int)$row['slot_index'],
                                        'position_label' => $row['position_label'],
                                        'left_percent' => (float)$row['left_percent'],
                                        'bottom_percent' => (float)$row['bottom_percent'],
                                        'rotation_deg' => (int)($row['rotation_deg'] ?? 0),
                              ];
                    }
          }

          return array_values($formations);
}

function find_formation_by_id(int $formationId): ?array
{
          if ($formationId <= 0) {
                    return null;
          }
          $stmt = db()->prepare('SELECT id FROM formations WHERE id = :id LIMIT 1');
          $stmt->execute(['id' => $formationId]);
          $row = $stmt->fetch();
          return $row ?: null;
}

function record_match_formation_selection(int $matchId, string $teamSide, string $format, string $formationKey, array $options = []): int
{
          $matchPeriodId = $options['match_period_id'] ?? null;
          $matchSecond = isset($options['match_second']) ? (int)$options['match_second'] : 0;
          $minute = isset($options['minute']) ? (int)$options['minute'] : 0;
          $minuteExtra = isset($options['minute_extra']) ? (int)$options['minute_extra'] : 0;
          $layoutJson = $options['layout_json'] ?? null;
          $notes = $options['notes'] ?? null;
          $createdBy = $options['created_by'] ?? null;

          if (
                    $matchSecond === null ||
                    $minute === null ||
                    $minuteExtra === null
          ) {
                    throw new LogicException('Formation changes require explicit timing context');
          }

          $updateStmt = db()->prepare(
                    'UPDATE match_formations
                              SET format = :format,
                                  formation_key = :formation_key,
                                  layout_json = :layout_json,
                                  updated_at = CURRENT_TIMESTAMP()
                              WHERE match_id = :match_id
                                AND team_side = :team_side'
          );
          $updateStmt->execute([
                    ':format' => $format,
                    ':formation_key' => $formationKey,
                    ':layout_json' => $layoutJson,
                    ':match_id' => $matchId,
                    ':team_side' => $teamSide,
          ]);
          if ($updateStmt->rowCount() > 0) {
                    return 0;
          }

          $insertStmt = db()->prepare(
                    'INSERT INTO match_formations (
                              match_id,
                              team_side,
                              match_period_id,
                              match_second,
                              minute,
                              minute_extra,
                              format,
                              formation_key,
                              layout_json,
                              notes,
                              created_by
                    ) VALUES (
                              :match_id,
                              :team_side,
                              :match_period_id,
                              :match_second,
                              :minute,
                              :minute_extra,
                              :format,
                              :formation_key,
                              :layout_json,
                              :notes,
                              :created_by
                    )'
          );
          $insertStmt->execute([
                    ':match_id' => $matchId,
                    ':team_side' => $teamSide,
                    ':match_period_id' => $matchPeriodId,
                    ':match_second' => $matchSecond,
                    ':minute' => $minute,
                    ':minute_extra' => $minuteExtra,
                    ':format' => $format,
                    ':formation_key' => $formationKey,
                    ':layout_json' => $layoutJson,
                    ':notes' => $notes,
                    ':created_by' => $createdBy,
          ]);
          return (int)db()->lastInsertId();
}

function get_active_match_formation(int $matchId, string $teamSide): ?array
{
          if ($matchId <= 0 || !in_array($teamSide, ['home', 'away'], true)) {
                    return null;
          }
          $stmt = db()->prepare(
                    'SELECT
                              mf.*,
                              f.id AS formation_id,
                              f.label
                      FROM match_formations mf
                      JOIN formations f
                        ON f.format = mf.format
                       AND f.formation_key = mf.formation_key
                      WHERE mf.match_id = :match_id
                        AND mf.team_side = :team_side
                      ORDER BY mf.match_second DESC, mf.id DESC
                      LIMIT 1'
          );
          $stmt->execute([
                    ':match_id' => $matchId,
                    ':team_side' => $teamSide,
          ]);
          $row = $stmt->fetch(PDO::FETCH_ASSOC);
          return $row ?: null;
}
