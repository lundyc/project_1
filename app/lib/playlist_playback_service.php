<?php
declare(strict_types=1);

require_once __DIR__ . '/playlist_repository.php';
require_once __DIR__ . '/match_repository.php';

const PLAYLIST_PLAYBACK_MODE_CLIPS = 'clips';
const PLAYLIST_PLAYBACK_MODE_FULL_MATCH = 'full_match';

/**
 * Build the playlist queue and attach match video metadata.
 *
 * @return array{playlist: array, match: array, full_match_video_url: string, mode: string, queue: array<int, array>}
 */
function playlist_playback_build_queue(int $playlistId, ?string $mode = null): array
{
          $playlist = playlist_get_by_id($playlistId);
          if (!$playlist) {
                    throw new RuntimeException('playlist_not_found');
          }

          $matchId = isset($playlist['match_id']) ? (int)$playlist['match_id'] : 0;
          if ($matchId <= 0) {
                    throw new RuntimeException('playlist_invalid_match');
          }

          $match = get_match($matchId);
          if (!$match) {
                    throw new RuntimeException('match_not_found');
          }

          $fullMatchUrl = trim((string)($match['video_source_path'] ?? ''));
          if ($fullMatchUrl === '') {
                    throw new RuntimeException('match_video_missing');
          }

          $clips = playlist_get_clips($playlistId);
          $queue = [];
          foreach ($clips as $clip) {
                    if (!playlist_playback_clip_is_playable($clip)) {
                              continue;
                    }
                    if (isset($clip['match_id']) && (int)$clip['match_id'] !== $matchId) {
                              continue;
                    }
                    $queue[] = playlist_playback_format_clip($clip, $fullMatchUrl);
          }

          if (empty($queue)) {
                    $queue = [];
          }

          return [
                    'playlist' => $playlist,
                    'match' => $match,
                    'full_match_video_url' => $fullMatchUrl,
                    'mode' => playlist_playback_normalize_mode($mode),
                    'queue' => $queue,
          ];
}

function playlist_playback_normalize_mode(?string $mode): string
{
          $requested = $mode ? strtolower(trim($mode)) : '';
          if ($requested === PLAYLIST_PLAYBACK_MODE_CLIPS) {
                    return PLAYLIST_PLAYBACK_MODE_CLIPS;
          }
          return PLAYLIST_PLAYBACK_MODE_FULL_MATCH;
}

/**
 * Determine whether the clip row should participate in playback.
 */
function playlist_playback_clip_is_playable(array $clip): bool
{
          if (isset($clip['deleted_at']) && $clip['deleted_at'] !== null) {
                    return false;
          }
          if (isset($clip['is_valid']) && (int)$clip['is_valid'] === 0) {
                    return false;
          }
          return true;
}

/**
 * Normalize the clip row for queue responses.
 */
function playlist_playback_format_clip(array $clip, string $fullMatchUrl): array
{
          $clipId = isset($clip['clip_id']) && (int)$clip['clip_id'] > 0 ? (int)$clip['clip_id'] : (int)($clip['id'] ?? 0);
          return [
                    'clip_id' => $clipId,
                    'clip_name' => $clip['clip_name'] ?? 'Untitled clip',
                    'start_second' => isset($clip['start_second']) ? (int)$clip['start_second'] : 0,
                    'end_second' => isset($clip['end_second']) ? (int)$clip['end_second'] : 0,
                    'duration_seconds' => isset($clip['duration_seconds']) ? (int)$clip['duration_seconds'] : 0,
                    'match_id' => isset($clip['match_id']) ? (int)$clip['match_id'] : 0,
                    'event_id' => isset($clip['event_id']) ? (int)$clip['event_id'] : null,
                    'sort_order' => isset($clip['sort_order']) ? (int)$clip['sort_order'] : 0,
                    'clip_video_url' => null,
                    'full_match_video_url' => $fullMatchUrl,
          ];
}

/**
 * @param array<int, array> $queue
 */
function playlist_playback_find_first_clip(array $queue): ?array
{
          return $queue[0] ?? null;
}

/**
 * Find a clip entry by ID and return its array index.
 */
function playlist_playback_find_clip_index(array $queue, int $clipId): int
{
          foreach ($queue as $index => $entry) {
                    if (isset($entry['clip_id']) && (int)$entry['clip_id'] === $clipId) {
                              return $index;
                    }
          }
          return -1;
}

/**
 * @param array<int, array> $queue
 */
function playlist_playback_next_clip(array $queue, ?int $currentClipId): ?array
{
          if (empty($queue)) {
                    return null;
          }

          if ($currentClipId === null || $currentClipId <= 0) {
                    return playlist_playback_find_first_clip($queue);
          }

          $index = playlist_playback_find_clip_index($queue, $currentClipId);
          if ($index < 0) {
                    return playlist_playback_find_first_clip($queue);
          }

          $nextIndex = $index + 1;
          return $queue[$nextIndex] ?? null;
}

/**
 * @param array<int, array> $queue
 */
function playlist_playback_previous_clip(array $queue, ?int $currentClipId): ?array
{
          if (empty($queue)) {
                    return null;
          }

          if ($currentClipId === null || $currentClipId <= 0) {
                    return null;
          }

          $index = playlist_playback_find_clip_index($queue, $currentClipId);
          if ($index <= 0) {
                    return null;
          }

          $prevIndex = $index - 1;
          return $queue[$prevIndex] ?? null;
}
