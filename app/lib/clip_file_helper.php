<?php

function clip_file_helper_get_clips_dir(): string
{
          $documentRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', DIRECTORY_SEPARATOR);
          return $documentRoot . '/videos/clips';
}

function clip_file_helper_get_canonical_path(int $matchId, int $clipId): string
{
          return clip_file_helper_get_clips_dir() . DIRECTORY_SEPARATOR . "match_{$matchId}_{$clipId}.mp4";
}

function clip_file_helper_build_clip_basename(string $clipName): string
{
          $text = trim($clipName);
          if ($text === '') {
                    return 'clip';
          }

          $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT', $text);
          if ($transliterated !== false) {
                    $text = $transliterated;
          }

          $text = preg_replace('/\s+/', '_', $text);
          $text = preg_replace('/[^A-Za-z0-9_\(\)\+]+/', '_', $text);
          $text = preg_replace('/_+/', '_', $text);
          $text = trim($text, '_');

          return $text !== '' ? $text : 'clip';
}

function clip_file_helper_remove_clip_files(int $matchId, int $clipId, ?string $clipName = null): array
{
          $removed = [];
          $clipsDir = clip_file_helper_get_clips_dir();
          $canonical = clip_file_helper_get_canonical_path($matchId, $clipId);
          if (is_file($canonical)) {
                    @unlink($canonical);
                    $removed[] = $canonical;
          }

          $baseName = clip_file_helper_build_clip_basename($clipName ?? 'clip');
          $pattern = $clipsDir . DIRECTORY_SEPARATOR . $baseName . '*.mp4';
          foreach (glob($pattern) as $candidate) {
                    if (!is_file($candidate)) {
                              continue;
                    }
                    if (realpath($candidate) === realpath($canonical)) {
                              continue;
                    }
                    @unlink($candidate);
                    $removed[] = $candidate;
          }

          clip_file_helper_forget_clip_path($clipId);

          return $removed;
}

function clip_file_helper_get_project_root(): string
{
          $documentRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', DIRECTORY_SEPARATOR);
          if ($documentRoot !== '') {
                    $projectRoot = dirname($documentRoot);
          } else {
                    $projectRoot = dirname(__DIR__, 2);
          }
          return rtrim($projectRoot, DIRECTORY_SEPARATOR);
}

function clip_file_helper_get_storage_dir(): string
{
          return clip_file_helper_get_project_root() . DIRECTORY_SEPARATOR . 'storage';
}

function clip_file_helper_get_clip_path_map_file(): string
{
          return clip_file_helper_get_storage_dir() . DIRECTORY_SEPARATOR . 'clip_path_map.json';
}

function clip_file_helper_load_clip_path_map(): array
{
          $mapFile = clip_file_helper_get_clip_path_map_file();
          if (!is_file($mapFile)) {
                    return [];
          }
          $contents = @file_get_contents($mapFile);
          if (!is_string($contents) || $contents === '') {
                    return [];
          }
          $decoded = json_decode($contents, true);
          if (!is_array($decoded)) {
                    return [];
          }
          return $decoded;
}

function clip_file_helper_save_clip_path_map(array $map): void
{
          $mapFile = clip_file_helper_get_clip_path_map_file();
          $mapDir = dirname($mapFile);
          if (!is_dir($mapDir)) {
                    @mkdir($mapDir, 0775, true);
          }
          @file_put_contents($mapFile, json_encode($map, JSON_UNESCAPED_SLASHES), LOCK_EX);
}

function clip_file_helper_register_clip_path(int $clipId, string $path): void
{
          if ($clipId <= 0 || $path === '') {
                    return;
          }
          $real = realpath($path) ?: $path;
          if ($real === '') {
                    return;
          }
          $map = clip_file_helper_load_clip_path_map();
          $map[(string)$clipId] = $real;
          clip_file_helper_save_clip_path_map($map);
}

function clip_file_helper_get_registered_clip_path(?int $clipId): ?string
{
          if (!$clipId) {
                    return null;
          }
          $map = clip_file_helper_load_clip_path_map();
          $key = (string)$clipId;
          if (!isset($map[$key])) {
                    return null;
          }
          $path = $map[$key];
          if (!is_file($path)) {
                    return null;
          }
          return realpath($path) ?: $path;
}

function clip_file_helper_forget_clip_path(?int $clipId): void
{
          if (!$clipId) {
                    return;
          }
          $map = clip_file_helper_load_clip_path_map();
          $key = (string)$clipId;
          if (!isset($map[$key])) {
                    return;
          }
          unset($map[$key]);
          clip_file_helper_save_clip_path_map($map);
}

function clip_file_helper_absolute_to_public_path(string $path): string
{
          $documentRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', DIRECTORY_SEPARATOR);
          if ($documentRoot !== '' && strpos($path, $documentRoot) === 0) {
                    $relative = substr($path, strlen($documentRoot));
                    $relative = str_replace(DIRECTORY_SEPARATOR, '/', $relative);
                    return '/' . ltrim($relative, '/');
          }
          return $path;
}
