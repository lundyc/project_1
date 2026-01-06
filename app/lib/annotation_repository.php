<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/error_logger.php';

/**
 * Decode the stored drawing data JSON and normalize the annotation record.
 *
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function annotation_normalize_row(array $row): array
{
          $drawing = null;
          if (isset($row['drawing_data']) && $row['drawing_data'] !== null) {
                    $decoded = json_decode((string)$row['drawing_data'], true);
                    if (is_array($decoded)) {
                              $drawing = $decoded;
                    }
          }

          return [
                    'id' => isset($row['id']) ? (int)$row['id'] : 0,
                    'match_id' => isset($row['match_id']) ? (int)$row['match_id'] : 0,
                    'target_type' => $row['target_type'] ?? '',
                    'target_id' => isset($row['target_id']) ? (int)$row['target_id'] : 0,
                    'timestamp_second' => isset($row['timestamp_second']) ? (int)$row['timestamp_second'] : 0,
                   'notes' => $row['notes'] ?? null,
                   'tool_type' => $row['tool_type'] ?? null,
                   'drawing_data' => $drawing,
                   'created_at' => $row['created_at'] ?? null,
                   'updated_at' => $row['updated_at'] ?? null,
          ];
}

function annotation_match_video_belongs_to_match(int $matchId, int $videoId): bool
{
          if ($matchId <= 0 || $videoId <= 0) {
                    return false;
          }
          $stmt = db()->prepare('SELECT 1 FROM match_videos WHERE id = :id AND match_id = :match_id LIMIT 1');
          $stmt->execute(['id' => $videoId, 'match_id' => $matchId]);
          return (bool)$stmt->fetchColumn();
}

function annotation_clip_belongs_to_match(int $matchId, int $clipId): bool
{
          if ($matchId <= 0 || $clipId <= 0) {
                    return false;
          }
          $stmt = db()->prepare('SELECT 1 FROM clips WHERE id = :id AND match_id = :match_id LIMIT 1');
          $stmt->execute(['id' => $clipId, 'match_id' => $matchId]);
          return (bool)$stmt->fetchColumn();
}

function annotation_target_exists(int $matchId, string $targetType, int $targetId): bool
{
          if ($targetType === 'match_video') {
                    return annotation_match_video_belongs_to_match($matchId, $targetId);
          }
          if ($targetType === 'clip') {
                    return annotation_clip_belongs_to_match($matchId, $targetId);
          }
          return false;
}

function annotation_extract_tool_type(array $drawingData): string
{
          if (!empty($drawingData['tool']) && is_string($drawingData['tool'])) {
                    return $drawingData['tool'];
          }
          if (!empty($drawingData['tool_type']) && is_string($drawingData['tool_type'])) {
                    return $drawingData['tool_type'];
          }

          return 'line';
}

/**
 * Fetch all annotations for the provided target.
 *
 * @return array<int, array<string, mixed>>
 */
function annotation_list_for_target(int $matchId, string $targetType, int $targetId): array
{
          if ($matchId <= 0 || $targetId <= 0 || !$targetType) {
                    return [];
          }
          $stmt = db()->prepare(
                    'SELECT * FROM annotations
           WHERE match_id = :match_id
             AND target_type = :target_type
             AND target_id = :target_id
           ORDER BY timestamp_second ASC, id ASC'
          );
          $stmt->execute([
                    'match_id' => $matchId,
                    'target_type' => $targetType,
                    'target_id' => $targetId,
          ]);
          $rows = $stmt->fetchAll();
          return array_map('annotation_normalize_row', $rows ?: []);
}

function annotation_find(int $annotationId): ?array
{
          if ($annotationId <= 0) {
                    return null;
          }
          $stmt = db()->prepare('SELECT * FROM annotations WHERE id = :id LIMIT 1');
          $stmt->execute(['id' => $annotationId]);
          $row = $stmt->fetch();
          if (!$row) {
                    return null;
          }
          return annotation_normalize_row($row);
}

function annotation_create(int $matchId, string $targetType, int $targetId, int $timestampSecond, array $drawingData, ?string $notes = null): array
{
          $now = date('Y-m-d H:i:s');
          $toolType = annotation_extract_tool_type($drawingData);
          $pdo = db();
          $sql = 'INSERT INTO annotations
          (match_id, target_type, target_id, timestamp_second, tool_type, drawing_data, notes, created_at, updated_at)
          VALUES (:match_id, :target_type, :target_id, :timestamp_second, :tool_type, :drawing_data, :notes, :created_at, :updated_at)';
          $params = [
                    'match_id' => $matchId,
                    'target_type' => $targetType,
                    'target_id' => $targetId,
                    'timestamp_second' => $timestampSecond,
                    'tool_type' => $toolType,
                    'drawing_data' => json_encode($drawingData, JSON_UNESCAPED_UNICODE),
                    'notes' => $notes,
                    'created_at' => $now,
                    'updated_at' => $now,
          ];

          try {
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
          } catch (\PDOException $e) {
                    $logContext = [
                              'layer' => 'repository',
                              'fn' => 'annotation_create',
                              'message' => 'Failed to persist annotation',
                              'match_id' => $matchId,
                              'target_type' => $targetType,
                              'target_id' => $targetId,
                              'timestamp_second' => $timestampSecond,
                              'tool_type' => $toolType,
                              'sql' => $sql,
                              'params' => $params,
                              'level' => 'error',
                              'exception' => $e,
                    ];

                    $sqlState = $e->getCode();
                    $message = $e->getMessage();
                    if ($sqlState === '42S22' || stripos($message, 'unknown column') !== false) {
                              $column = 'tool_type';
                              if (preg_match("/Unknown column '([^']+)'/i", $message, $matches)) {
                                        $column = $matches[1];
                              }
                              $logContext['hint'] = 'schema_mismatch_or_wrong_connection';
                              $logContext['table'] = 'annotations';
                              $logContext['column'] = $column;
                    }

                    $dbName = null;
                    try {
                              $dbName = $pdo->query('SELECT DATABASE()')->fetchColumn();
                    } catch (\Throwable $ignore) {
                              // ignore
                    }
                    if ($dbName !== false && $dbName !== null) {
                              $logContext['db_name'] = (string)$dbName;
                    }

                    try {
                              $serverInfo = $pdo->getAttribute(\PDO::ATTR_SERVER_INFO);
                              if ($serverInfo !== null) {
                                        $logContext['server'] = $serverInfo;
                              }
                    } catch (\Throwable $ignore) {
                              // ignore
                    }

                    log_api_error($logContext);
                    throw $e;
          }

          return annotation_find((int)$pdo->lastInsertId()) ?: [];
}

function annotation_update(int $annotationId, int $matchId, int $timestampSecond, array $drawingData, ?string $notes = null): array
{
          if ($annotationId <= 0 || $matchId <= 0) {
                    return [];
          }
          $now = date('Y-m-d H:i:s');
          $toolType = annotation_extract_tool_type($drawingData);

          $stmt = db()->prepare(
                    'UPDATE annotations
           SET timestamp_second = :timestamp_second,
               drawing_data = :drawing_data,
               tool_type = :tool_type,
               notes = :notes,
               updated_at = :updated_at
           WHERE id = :id AND match_id = :match_id'
          );
          $stmt->execute([
                    'timestamp_second' => $timestampSecond,
                    'drawing_data' => json_encode($drawingData, JSON_UNESCAPED_UNICODE),
                    'tool_type' => $toolType,
                    'notes' => $notes,
                    'updated_at' => $now,
                    'id' => $annotationId,
                    'match_id' => $matchId,
          ]);

          return annotation_find($annotationId) ?: [];
}

function annotation_delete(int $annotationId): void
{
          if ($annotationId <= 0) {
                    return;
          }
          db()->prepare('DELETE FROM annotations WHERE id = :id')->execute(['id' => $annotationId]);
}
