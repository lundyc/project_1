<?php

require_once __DIR__ . '/db.php';

function match_period_columns(): array
{
          static $columns;

          if ($columns !== null) {
                    return $columns;
          }

          try {
                    $stmt = db()->query('SHOW COLUMNS FROM match_periods');
                    $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
                    $columns = array_map(fn($c) => $c['Field'], $rows);
          } catch (\Throwable $e) {
                    error_log('Unable to read match_periods columns: ' . $e->getMessage());
                    $columns = [];
          }

          return $columns;
}

function hydrate_period(array $row): array
{
          $status = 'pending';
          $startSecond = array_key_exists('start_second', $row) ? $row['start_second'] : null;
          $endSecond = array_key_exists('end_second', $row) ? $row['end_second'] : null;

          if ($startSecond !== null) {
                    $status = 'active';
                    if ($endSecond !== null) {
                              $status = 'completed';
                    }
          }

          
          $row['status'] = $status;
          $row['start_minute'] = $startSecond !== null ? (int)floor($startSecond / 60) : null;
          $row['end_minute'] = $endSecond !== null ? (int)floor($endSecond / 60) : null;

          return $row;
}

function get_match_periods(int $matchId): array
{
          $sql = 'SELECT id, match_id, period_key, label, start_second, end_second, created_at, updated_at FROM match_periods WHERE match_id = :match_id ORDER BY period_key ASC, id ASC';
          $stmt = db()->prepare($sql);
          $stmt->execute(['match_id' => $matchId]);
          $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

          return array_map('hydrate_period', $rows);
}

function replace_match_periods(int $matchId, array $periods): void
{
          $pdo = db();
          $pdo->beginTransaction();

          try {
                    $pdo->prepare('DELETE FROM match_periods WHERE match_id = :match_id')->execute(['match_id' => $matchId]);

                    if (empty($periods)) {
                              $pdo->commit();
                              return;
                    }

                    $sql = 'INSERT INTO match_periods (match_id, period_key, label) VALUES (:match_id, :period_key, :label)';
                    $stmt = $pdo->prepare($sql);

                    foreach ($periods as $idx => $period) {
                              $key = trim((string)($period['period_key'] ?? $period['label'] ?? ''));
                              if ($key === '') {
                                        $key = sprintf('period_%02d', $idx + 1);
                              }
                              $label = trim((string)($period['label'] ?? ('Period ' . ($idx + 1))));

                              $stmt->execute([
                                        'match_id' => $matchId,
                                        'period_key' => $key,
                                        'label' => $label,
                              ]);
                    }

                    $pdo->commit();
          } catch (\Throwable $e) {
                    $pdo->rollBack();
                    throw $e;
          }
}

function upsert_match_period_time(
          int $matchId,
          ?int $periodId,
          string $label,
          ?int $startSecond,
          ?int $endSecond,
          ?string $periodKey = null,
          bool $clearEnd = false
): array
{
          $pdo = db();
          $pdo->beginTransaction();

          try {
                    $period = null;

                    if ($periodId !== null) {
                              $stmt = $pdo->prepare('SELECT * FROM match_periods WHERE id = :id AND match_id = :match_id LIMIT 1');
                              $stmt->execute(['id' => $periodId, 'match_id' => $matchId]);
                              $period = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
                    }

                    if ($period === null && $periodKey !== null) {
                              $stmt = $pdo->prepare('SELECT * FROM match_periods WHERE match_id = :match_id AND period_key = :period_key ORDER BY id ASC LIMIT 1');
                              $stmt->execute(['match_id' => $matchId, 'period_key' => $periodKey]);
                              $period = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
                    }

                    if ($period === null) {
                              $stmt = $pdo->prepare('SELECT * FROM match_periods WHERE match_id = :match_id AND label = :label ORDER BY id ASC LIMIT 1');
                              $stmt->execute(['match_id' => $matchId, 'label' => $label]);
                              $period = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
                    }

                    if ($period === null) {
                              $stmt = $pdo->prepare('INSERT INTO match_periods (match_id, period_key, label) VALUES (:match_id, :period_key, :label)');
                              $stmt->execute([
                                        'match_id' => $matchId,
                                        'period_key' => $periodKey ?? ($label !== '' ? $label : 'period_' . time()),
                                        'label' => $label,
                              ]);
                              $period = ['id' => (int)$pdo->lastInsertId()];
                    }

                    $updates = [];
                    $params = ['match_id' => $matchId, 'id' => $period['id']];

                    // Always update start_second if provided (for period start)
                    if ($startSecond !== null) {
                              $updates[] = 'start_second = :start_second';
                              $params['start_second'] = $startSecond;
                    } else if ($period !== null && !array_key_exists('start_second', $period)) {
                              // If period exists but has no start_second, set it to 0
                              $updates[] = 'start_second = :start_second';
                              $params['start_second'] = 0;
                    }
                    if ($endSecond !== null) {
                              $updates[] = 'end_second = :end_second';
                              $params['end_second'] = $endSecond;
                    }

                    if ($clearEnd) {
                              $updates[] = 'end_second = NULL';
                    }

                    if (!empty($updates)) {
                              $pdo->prepare('UPDATE match_periods SET ' . implode(', ', $updates) . ' WHERE id = :id AND match_id = :match_id')
                                        ->execute($params);
                    }

                    $pdo->commit();
                    return [
                              'period_id' => (int)$period['id'],
                              'periods' => get_match_periods($matchId),
                    ];
          } catch (\Throwable $e) {
                    $pdo->rollBack();
                    throw $e;
          }
}

function get_active_match_period(int $matchId): ?array
{
          $sql = 'SELECT id, match_id, period_key, label, start_second, end_second FROM match_periods WHERE match_id = :match_id AND start_second IS NOT NULL AND end_second IS NULL ORDER BY id ASC LIMIT 1';
          $stmt = db()->prepare($sql);
          $stmt->execute(['match_id' => $matchId]);
          $row = $stmt->fetch(PDO::FETCH_ASSOC);

          return $row ? hydrate_period($row) : null;
}
