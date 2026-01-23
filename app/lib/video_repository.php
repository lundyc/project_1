<?php

require_once __DIR__ . '/db.php';

/**
 * @return string|null
 */
function video_repository_project_root(): ?string
{
          static $root;
          if ($root !== null) {
                    return $root;
          }

          $candidate = realpath(__DIR__ . '/../../');
          if ($candidate === false) {
                    return null;
          }

          return $root = $candidate;
}

/**
 * Convert a platform path into an absolute filesystem path.
 */
function video_repository_absolute_path(string $path): ?string
{
          $root = video_repository_project_root();
          if ($root === null) {
                    return null;
          }

          $trimmed = ltrim($path, '/');
          return $root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $trimmed);
}

/**
 * Apply the optional club filter to queries that are joined with the `matches` table.
 *
 * @param int|null $clubId
 * @param array<string, mixed> &$params
 * @param array<int, string> &$where
 * @param string $alias
 */
function video_repository_apply_club_filter(?int $clubId, array &$params, array &$where, string $alias = 'm'): void
{
          if ($clubId !== null && $clubId > 0) {
                    $where[] = $alias . '.club_id = :club_id';
                    $params['club_id'] = $clubId;
          }
}

/**
 * @return string|null
 */
function video_repository_progress_dir(): ?string
{
          $root = video_repository_project_root();
          if ($root === null) {
                    return null;
          }

          $dir = $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'video_progress';
          if (!is_dir($dir)) {
                    @mkdir($dir, 0777, true);
          }

          return $dir;
}

/**
 * @return string|null
 */
function video_repository_progress_file_path(int $matchId): ?string
{
          $dir = video_repository_progress_dir();
          if ($dir === null) {
                    return null;
          }

          return $dir . DIRECTORY_SEPARATOR . $matchId . '.json';
}

/**
 * @return array<string, mixed>
 */
function video_repository_make_file_meta(
          int $matchId,
          string $format,
          array $candidates,
          ?string $projectRoot
): array {
          $meta = [
                    'label' => ucfirst($format),
                    'available' => false,
                    'absolute_path' => null,
                    'public_path' => null,
                    'size' => 0,
                    'last_modified' => null,
          ];

          foreach ($candidates as $candidate) {
                    if ($candidate === null || !is_string($candidate) || $projectRoot === null) {
                              continue;
                    }

                    $formatDir = $candidate . DIRECTORY_SEPARATOR . $format;
                    $filename = 'match_' . $matchId . '_' . $format . '.mp4';
                    $filePath = $formatDir . DIRECTORY_SEPARATOR . $filename;

                    if (!is_file($filePath)) {
                              continue;
                    }

                    $meta['available'] = true;
                    $meta['absolute_path'] = $filePath;
                    $meta['public_path'] = '/' . ltrim(str_replace($projectRoot, '', $filePath), '/\\');
                    $meta['size'] = file_exists($filePath) ? (int)@filesize($filePath) : 0;
                    $meta['last_modified'] = filemtime($filePath) ?: null;
                    break;
          }

          return $meta;
}

/**
 * @return array<string, array<string, mixed>>
 */
function get_video_files(int $matchId, ?string $sourcePath = null): array
{
          $projectRoot = video_repository_project_root();
          $paths = [];
          if ($sourcePath) {
                    $paths[] = video_repository_absolute_path($sourcePath);
          }

          $paths[] = $projectRoot ? $projectRoot . DIRECTORY_SEPARATOR . 'videos' . DIRECTORY_SEPARATOR . 'matches' . DIRECTORY_SEPARATOR . 'match_' . $matchId . DIRECTORY_SEPARATOR . 'source' . DIRECTORY_SEPARATOR . 'veo' : null;
          $paths[] = $projectRoot ? $projectRoot . DIRECTORY_SEPARATOR . 'videos' . DIRECTORY_SEPARATOR . 'matches' . DIRECTORY_SEPARATOR . 'match_' . $matchId . DIRECTORY_SEPARATOR . 'source' . DIRECTORY_SEPARATOR . 'upload' : null;

          $formatsToCheck = ['standard', 'panoramic'];
          $files = [];
          foreach ($formatsToCheck as $format) {
                    $files[$format] = video_repository_make_file_meta($matchId, $format, $paths, $projectRoot);
          }

          return $files;
}

/**
 * @return array<int, array<string, mixed>>
 */
function get_all_videos(?int $clubId = null): array
{
          $pdo = db();
          $params = [];
          $where = [];

          video_repository_apply_club_filter($clubId, $params, $where);

          $sql = <<<SQL
SELECT mv.*,
       m.club_id,
       m.id AS match_id,
       m.kickoff_at,
       m.status AS match_status,
       m.created_at AS match_created_at,
       ht.name AS home_team,
       at.name AS away_team,
       c.name AS competition
FROM match_videos mv
JOIN matches m ON m.id = mv.match_id
JOIN teams ht ON ht.id = m.home_team_id
JOIN teams at ON at.id = m.away_team_id
LEFT JOIN competitions c ON c.id = m.competition_id
SQL;

          if (!empty($where)) {
                    $sql .= ' WHERE ' . implode(' AND ', $where);
          }

          $sql .= ' ORDER BY mv.created_at DESC';

          $stmt = $pdo->prepare($sql);
          $stmt->execute($params);

          return $stmt->fetchAll();
}

/**
 * @return array<string, mixed>|null
 */
function get_video(int $id): ?array
{
          $pdo = db();

          $sql = <<<SQL
SELECT mv.*,
       m.club_id,
       m.id AS match_id,
       m.kickoff_at,
       m.status AS match_status,
       m.created_at AS match_created_at,
       ht.name AS home_team,
       at.name AS away_team,
       c.name AS competition
FROM match_videos mv
JOIN matches m ON m.id = mv.match_id
JOIN teams ht ON ht.id = m.home_team_id
JOIN teams at ON at.id = m.away_team_id
LEFT JOIN competitions c ON c.id = m.competition_id
WHERE mv.id = :id
LIMIT 1
SQL;

          $stmt = $pdo->prepare($sql);
          $stmt->execute(['id' => $id]);

          return $stmt->fetch() ?: null;
}

/**
 * @return array<int, array<string, mixed>>
 */
function get_recent_video_ingestions(?int $clubId = null, int $limit = 5): array
{
          $pdo = db();
          $params = [];
          $where = [];

          video_repository_apply_club_filter($clubId, $params, $where);

          $sql = <<<SQL
SELECT mv.*,
       m.club_id,
       m.id AS match_id,
       m.kickoff_at,
       ht.name AS home_team,
       at.name AS away_team,
       c.name AS competition
FROM match_videos mv
JOIN matches m ON m.id = mv.match_id
JOIN teams ht ON ht.id = m.home_team_id
JOIN teams at ON at.id = m.away_team_id
LEFT JOIN competitions c ON c.id = m.competition_id
SQL;

          if (!empty($where)) {
                    $sql .= ' WHERE ' . implode(' AND ', $where);
          }

          $sql .= ' ORDER BY mv.created_at DESC LIMIT :limit';

          $stmt = $pdo->prepare($sql);
          foreach ($params as $key => $value) {
                    $stmt->bindValue($key, $value);
          }
          $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
          $stmt->execute();

          return $stmt->fetchAll();
}

/**
 * @return array<string, int>
 */
function count_video_statuses(?int $clubId = null): array
{
          $pdo = db();
          $params = [];
          $where = [];

          video_repository_apply_club_filter($clubId, $params, $where);

          $sql = <<<SQL
SELECT mv.download_status AS status, COUNT(*) AS total
FROM match_videos mv
JOIN matches m ON m.id = mv.match_id
SQL;

          if (!empty($where)) {
                    $sql .= ' WHERE ' . implode(' AND ', $where);
          }

          $sql .= ' GROUP BY mv.download_status';

          $stmt = $pdo->prepare($sql);
          $stmt->execute($params);

          $result = [];
          while ($row = $stmt->fetch()) {
                    $status = $row['status'] ?? 'unknown';
                    $result[$status] = (int)($row['total'] ?? 0);
          }

          return $result;
}

/**
 * @return int
 */
function count_matches_with_videos(?int $clubId = null): int
{
          $pdo = db();
          $params = [];
          $where = [];

          video_repository_apply_club_filter($clubId, $params, $where);

          $sql = <<<SQL
SELECT COUNT(DISTINCT m.id) AS total
FROM match_videos mv
JOIN matches m ON m.id = mv.match_id
SQL;

          if (!empty($where)) {
                    $sql .= ' WHERE ' . implode(' AND ', $where);
          }

          $stmt = $pdo->prepare($sql);
          $stmt->execute($params);

          return (int)$stmt->fetchColumn();
}

/**
 * @return array<int>
 */
function get_video_match_ids(?int $clubId = null): array
{
          $pdo = db();
          $params = [];
          $where = [];

          video_repository_apply_club_filter($clubId, $params, $where);

          $sql = <<<SQL
SELECT DISTINCT m.id AS match_id
FROM match_videos mv
JOIN matches m ON m.id = mv.match_id
SQL;

          if (!empty($where)) {
                    $sql .= ' WHERE ' . implode(' AND ', $where);
          }

          $stmt = $pdo->prepare($sql);
          $stmt->execute($params);

          $ids = [];
          while ($row = $stmt->fetch()) {
                    if (!empty($row['match_id'])) {
                              $ids[] = (int)$row['match_id'];
                    }
          }

          return $ids;
}

/**
 * @param int[] $matchIds
 * @return int
 */
function sum_video_storage_for_matches(array $matchIds): int
{
          $total = 0;
          foreach ($matchIds as $matchId) {
                    $files = get_video_files($matchId);
                    foreach ($files as $meta) {
                              if (!empty($meta['available'])) {
                                        $total += (int)($meta['size'] ?? 0);
                              }
                    }
          }

          return $total;
}

/**
 * @return array<string, mixed>
 */
function read_video_progress(int $matchId): array
{
          $projectRoot = video_repository_project_root();
          $result = [
                    'exists' => false,
                    'path' => null,
                    'raw' => null,
                    'data' => null,
                    'error' => null,
                    'progress_issue' => null,
          ];

          $file = video_repository_progress_file_path($matchId);
          if ($file === null) {
                    $result['error'] = 'progress_directory_unavailable';
                    return $result;
          }

          $result['path'] = '/' . ltrim(str_replace($projectRoot, '', $file), '/\\');

          if (!is_file($file)) {
                    $result['error'] = 'missing';
                    return $result;
          }

          $contents = @file_get_contents($file);
          if ($contents === false) {
                    $result['error'] = 'unreadable';
                    return $result;
          }

          $decoded = json_decode($contents, true);
          if (!is_array($decoded)) {
                    $result['error'] = 'invalid_json';
                    return $result;
          }

          $result['exists'] = true;
          $result['raw'] = $contents;
          $result['data'] = $decoded;

          $lastSeen = $decoded['last_seen_at'] ?? $decoded['updated_at'] ?? null;
          if ($lastSeen) {
                    $lastTs = strtotime($lastSeen);
                    if ($lastTs !== false && (time() - $lastTs) > 30) {
                              $result['progress_issue'] = 'stale_progress';
                    }
          }

          return $result;
}

/**
 * Retry or restart the VEO downloader based on a video row.
 *
 * @return array<string, mixed>
 * @throws RuntimeException
 */
function retry_video_download(int $videoId): array
{
          $pdo = db();
          $stmt = $pdo->prepare(
                    'SELECT mv.*, m.club_id FROM match_videos mv JOIN matches m ON m.id = mv.match_id WHERE mv.id = :id LIMIT 1'
          );
          $stmt->execute(['id' => $videoId]);
          $video = $stmt->fetch();

          if (!$video) {
                    throw new RuntimeException('Video record not found');
          }

          $matchId = (int)$video['match_id'];
          $sourceType = strtolower(trim((string)($video['source_type'] ?? 'upload')));
          $veoUrl = trim((string)($video['source_url'] ?? ''));

          if ($sourceType !== 'veo') {
                    throw new RuntimeException('Only VEO downloads can be retried');
          }

          if ($veoUrl === '') {
                    throw new RuntimeException('VEO URL missing');
          }

          $projectRoot = video_repository_project_root();
          if ($projectRoot === null) {
                    throw new RuntimeException('Project root unavailable');
          }

          $logDir = $projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs';
          @mkdir($logDir, 0777, true);
          $logFile = $logDir . DIRECTORY_SEPARATOR . 'veo_download.log';
          $runtimeLog = $logDir . DIRECTORY_SEPARATOR . 'veo_downloader_runtime.log';

          $progressDir = $projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'video_progress';
          @mkdir($progressDir, 0777, true);
          $progressFile = $progressDir . DIRECTORY_SEPARATOR . $matchId . '.json';
          $cancelFile = $progressFile . '.cancel';
          if (is_file($progressFile)) {
                    @unlink($progressFile);
          }
          if (is_file($cancelFile)) {
                    @unlink($cancelFile);
          }

          $sourcePath = 'video_' . $matchId . '.mp4';

          $pdo->prepare(
                    'UPDATE match_videos SET source_path = :path, download_status = :status, download_progress = 0, error_message = NULL WHERE id = :id'
          )->execute([
                    'path' => $sourcePath,
                    'status' => 'pending',
                    'id' => $videoId,
          ]);

          $pyDir = $projectRoot . DIRECTORY_SEPARATOR . 'py';
          $script = $pyDir . DIRECTORY_SEPARATOR . 'veo_downloader.py';
          if (!is_file($script)) {
                    throw new RuntimeException('Downloader script missing');
          }

          $python = $pyDir . DIRECTORY_SEPARATOR . '.venv' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'python';
          if (!is_file($python)) {
                    $python = '/usr/bin/python3';
          }
          if (!is_file($python)) {
                    $python = 'python3';
          }

          $cmd = sprintf(
                    'cd %s && %s %s %d %s >> %s 2>&1 & echo $!',
                    escapeshellarg($projectRoot),
                    escapeshellcmd($python),
                    escapeshellarg($script),
                    $matchId,
                    escapeshellarg($veoUrl),
                    escapeshellarg($runtimeLog)
          );

          exec($cmd, $output, $status);
          $pid = null;
          if ($status === 0 && !empty($output)) {
                    $pid = (int)trim((string)$output[0]);
          }

          $timestamp = date('c');
          $prefix = '[VEO RETRY][match:' . $matchId . '] ';
          $message = $prefix . 'Downloader restarted (pid=' . ($pid ?: 'unknown') . ')';
          $line = $timestamp . ' ' . $message;
          file_put_contents($logFile, $line . PHP_EOL, FILE_APPEND | LOCK_EX);

          return [
                    'match_id' => $matchId,
                    'video_id' => $videoId,
                    'status' => 'pending',
                    'pid' => $pid,
          ];
}

/**
 * @return array<string, mixed>
 * @throws RuntimeException
 */
function archive_video(int $videoId): array
{
          $pdo = db();
          $stmt = $pdo->prepare('SELECT mv.* FROM match_videos mv WHERE mv.id = :id LIMIT 1');
          $stmt->execute(['id' => $videoId]);
          $video = $stmt->fetch();

          if (!$video) {
                    throw new RuntimeException('Video record not found');
          }

          $stmt = $pdo->prepare(
                    'UPDATE match_videos SET download_status = :status, download_progress = 0, error_message = :error WHERE id = :id'
          );
          $stmt->execute([
                    'status' => 'failed',
                    'error' => 'Archived by admin',
                    'id' => $videoId,
          ]);

          return [
                    'video_id' => $videoId,
                    'match_id' => (int)$video['match_id'],
                    'status' => 'failed',
          ];
}
