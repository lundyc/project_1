<?php
declare(strict_types=1);

require_once __DIR__ . '/playlist_repository.php';
require_once __DIR__ . '/clip_repository.php';
require_once __DIR__ . '/clip_file_helper.php';
require_once __DIR__ . '/clip_mp4_service.php';

/**
 * Return all active playlists for a match.
 */
function playlist_service_list_for_match(int $matchId): array
{
          return playlist_list_for_match($matchId);
}

/**
 * Build a playlist for a match, trimming user input before persistence.
 */
function playlist_service_create_playlist(int $matchId, int $userId, string $title, ?string $notes = null): array
{
          $trimmedTitle = trim($title);
          if ($trimmedTitle === '') {
                    throw new InvalidArgumentException('title_required');
          }

          // Schema does not currently store the creator, but we keep the argument for future expansion.
          return playlist_create($matchId, $trimmedTitle, $notes);
}

/**
 * Ensure the playlist belongs to the requested match. Optionally include deleted rows.
 */
function playlist_service_require_playlist_for_match(int $playlistId, int $matchId, bool $allowDeleted = false): array
{
          $playlist = playlist_get_by_id($playlistId, $allowDeleted);
          if (!$playlist || (int)$playlist['match_id'] !== $matchId) {
                    throw new RuntimeException('playlist_not_found');
          }

          return $playlist;
}

/**
 * Update the editable playlist columns while ensuring match scoping.
 */
function playlist_service_update_playlist(int $playlistId, int $matchId, array $fields): array
{
          $playlist = playlist_service_require_playlist_for_match($playlistId, $matchId);
          $updates = [];

          if (array_key_exists('title', $fields)) {
                    $value = trim((string)$fields['title']);
                    if ($value === '') {
                              throw new InvalidArgumentException('title_required');
                    }
                    $updates['title'] = $value;
          }

          if (array_key_exists('notes', $fields)) {
                    $notes = $fields['notes'];
                    if ($notes !== null) {
                              $notes = trim((string)$notes);
                              if ($notes === '') {
                                        $notes = null;
                              }
                    }
                    $updates['notes'] = $notes;
          }

          if (empty($updates)) {
                    return $playlist;
          }

          return playlist_update_fields($playlistId, $updates);
}

/**
 * Soft delete the playlist row to keep history intact.
 */
function playlist_service_soft_delete_playlist(int $playlistId, int $matchId): array
{
          playlist_service_require_playlist_for_match($playlistId, $matchId);
          return playlist_soft_delete($playlistId);
}

/**
 * Fetch playlist metadata together with its ordered clips.
 */
function playlist_service_get_with_clips(int $playlistId, int $matchId): array
{
          $playlist = playlist_service_require_playlist_for_match($playlistId, $matchId);
          $clips = playlist_get_clips($playlistId);

          $clips = array_map(function ($clip) {
                    $clip['is_legacy_auto_clip'] = is_legacy_auto_clip($clip);
                    $clip['mp4_path'] = clip_mp4_service_get_clip_web_path($clip);
                    return $clip;
          }, $clips);

          return [
                    'playlist' => $playlist,
                    'clips' => $clips,
          ];
}

/**
 * Add a clip to a playlist after ensuring the clip belongs to the same match.
 * If the clip_id is actually an event_id (no explicit clip yet), create a clip record on-the-fly.
 */
function playlist_service_add_clip(int $playlistId, int $matchId, int $clipId, ?int $sortOrder = null): array
{
          require_once __DIR__ . '/event_repository.php';
          require_once __DIR__ . '/match_repository.php';
          playlist_service_require_playlist_for_match($playlistId, $matchId);

          $clip = playlist_get_clip_for_match($clipId, $matchId);
          $createdNewClip = false;
          $generatedClipPath = null;

          // If no clip exists, try to create one from the event (event_id == clip_id pattern)
          if (!$clip) {
                    $event = event_get_by_id($clipId);
                    if (!$event || (int)$event['match_id'] !== $matchId) {
                              throw new RuntimeException('clip_not_found');
                    }
                    // Build clip name from event details
                    $clipName = generate_clip_name_from_event($event, $matchId);
                    $matchSecond = (int)($event['match_second'] ?? 0);
                    $startSecond = max(0, $matchSecond - 30);
                    $endSecond = $matchSecond + 30;
                    $currentUser = current_user();
                    $userId = (int)($currentUser['id'] ?? 0);

                    // Generate mp4 first
                    require_once __DIR__ . '/clip_mp4_service.php';
                    $tempClip = [
                              'id' => null,
                              'match_id' => $matchId,
                              'clip_name' => $clipName,
                              'start_second' => $startSecond,
                              'duration_seconds' => $endSecond - $startSecond,
                    ];
                    // Insert into DB only after mp4 is created
                    try {
                              // Generate using descriptive base name (no clip_id yet)
                              $generatedClipPath = ensure_clip_mp4_exists($tempClip);
                              // Insert clip row
                              $pdo = db();
                              $stmt = $pdo->prepare(
                                        'INSERT INTO clips (match_id, event_id, clip_name, start_second, end_second, created_by) 
                                         VALUES (:match_id, :event_id, :clip_name, :start_second, :end_second, :created_by)'
                              );
                              $stmt->execute([
                                        'match_id' => $matchId,
                                        'event_id' => $clipId,
                                        'clip_name' => $clipName,
                                        'start_second' => $startSecond,
                                        'end_second' => $endSecond,
                                        'created_by' => $userId,
                              ]);
                              $newClipId = (int)$pdo->lastInsertId();
                              $tempClip['id'] = $newClipId;
                              $tempClip['mp4_file_path'] = $generatedClipPath;
                              $clip = [
                                        'id' => $newClipId,
                                        'clip_id' => $newClipId,
                                        'match_id' => $matchId,
                                        'clip_name' => $clipName,
                                        'start_second' => $startSecond,
                                        'end_second' => $endSecond,
                              ];
                              $clipId = $newClipId;
                              $createdNewClip = true;
                              clip_file_helper_register_clip_path($newClipId, $generatedClipPath);
                    } catch (\Exception $e) {
                              // If mp4 generation fails, remove DB row
                              if (isset($newClipId) && $newClipId) {
                                        $pdo->prepare('DELETE FROM clips WHERE id = :id')->execute(['id' => $newClipId]);
                              }
                              throw $e;
                    }
          }
          try {
                    $clipRow = playlist_add_clip($playlistId, $clipId, $sortOrder);
                    if (!$createdNewClip) {
                              require_once __DIR__ . '/clip_mp4_service.php';
                              try {
                                        ensure_clip_mp4_exists($clipRow);
                              } catch (\Throwable $mp4ex) {
                                        // Optionally log or handle mp4 generation errors
                                        // error_log('Clip mp4 generation failed: ' . $mp4ex->getMessage());
                              }
                    }
                    return $clipRow;
          } catch (RuntimeException $e) {
                    if ($e->getMessage() === 'duplicate_clip') {
                              throw new RuntimeException('duplicate_clip');
                    }
                    throw $e;
          }
}

/**
 * Remove a clip from the playlist and return the removed row for auditing.
 */
function playlist_service_remove_clip(int $playlistId, int $matchId, int $clipId): array
{
          playlist_service_require_playlist_for_match($playlistId, $matchId);
          $entry = playlist_get_clip_details($playlistId, $clipId);
          if (!$entry) {
                    throw new RuntimeException('clip_not_in_playlist');
          }

          playlist_remove_clip($playlistId, $clipId);
          return $entry;
}

/**
 * Apply a new clip ordering; invalid input raises detailed exceptions for the controller to translate.
 */
function playlist_service_reorder_clips(int $playlistId, int $matchId, array $orderInput): array
{
          playlist_service_require_playlist_for_match($playlistId, $matchId);

          if (!is_array($orderInput) || $orderInput === []) {
                    throw new InvalidArgumentException('playlist_and_order_required');
          }

          $existingClips = playlist_get_clips($playlistId);
          $existingIds = array_map(fn($row) => (int)$row['clip_id'], $existingClips);
          $ordering = playlist_service_parse_order_input($orderInput);

          $providedIds = array_keys($ordering);
          sort($providedIds);
          sort($existingIds);
          // Guard against payloads that omit or invent clip references.
          if ($providedIds !== $existingIds) {
                    throw new InvalidArgumentException('order_lineup_mismatch');
          }

          $before = array_map(fn($row) => [
                    'clip_id' => (int)$row['clip_id'],
                    'sort_order' => (int)$row['sort_order'],
          ], $existingClips);

          $after = array_map(fn($clipId, $sortOrder) => [
                    'clip_id' => (int)$clipId,
                    'sort_order' => (int)$sortOrder,
          ], array_keys($ordering), array_values($ordering));

          // Repository method wraps updates in a transaction so reordering stays atomic.
          playlist_reorder_clips($playlistId, $ordering);

          return [
                    'before' => $before,
                    'after' => $after,
          ];
}

/**
 * Normalize the controller's reorder payload into a strict clip_id => sort_order map.
 */
function playlist_service_parse_order_input(array $orderInput): array
{
          $ordering = [];
          $position = 0;

          foreach ($orderInput as $entry) {
                    $clipId = 0;
                    $sortOrder = null;

                    if (is_array($entry)) {
                              $clipId = isset($entry['clip_id']) ? (int)$entry['clip_id'] : 0;
                              if (array_key_exists('sort_order', $entry)) {
                                        $sortOrder = $entry['sort_order'] !== null ? (int)$entry['sort_order'] : null;
                              }
                    } elseif (is_numeric($entry)) {
                              $clipId = (int)$entry;
                    } else {
                              throw new InvalidArgumentException('invalid_clip_order');
                    }

                    if ($clipId <= 0) {
                              throw new InvalidArgumentException('invalid_clip_order');
                    }

                    if (isset($ordering[$clipId])) {
                              throw new InvalidArgumentException('duplicate_clip_order');
                    }

                    $ordering[$clipId] = $sortOrder !== null ? $sortOrder : $position;
                    $position++;
          }

          return $ordering;
}

/**
 * Generate a meaningful clip name from event details.
 * Format: EventType_PlayerName_(TimeInMinutes)
 * Example: Goal_John_Smith_(12)
 */
function generate_clip_name_from_event(array $event, int $matchId): string
{
           $eventLabel = playlist_service_slugify_clip_component($event['event_type_label'] ?? $event['event_type_key'] ?? '', 'Event');
           $playerLabel = playlist_service_slugify_clip_component($event['match_player_name'] ?? '', 'Unknown_Player');
           $timeLabel = playlist_service_format_event_time_label($event);

          $prefixParts = array_filter([$eventLabel, $playerLabel]);
          $prefix = $prefixParts !== [] ? implode('_', $prefixParts) . '_' : 'clip_';

          return $prefix . '(' . $timeLabel . ')';
}

function playlist_service_slugify_clip_component(string $value, string $fallback): string
{
          return playlist_service_slugify_filename($value, $fallback, ['_']);
}

function playlist_service_format_event_time_label(array $event): string
{
          $minute = isset($event['minute']) ? (int)$event['minute'] : null;
          $extra = isset($event['minute_extra']) ? (int)$event['minute_extra'] : 0;

          if ($minute !== null && $minute >= 0) {
                    $label = (string)$minute;
                    if ($extra > 0) {
                              $label .= '+' . $extra;
                    }
                    return $label;
          }

          $matchSecond = isset($event['match_second']) ? (int)$event['match_second'] : null;
          if ($matchSecond !== null && $matchSecond >= 0) {
                    return (string)floor($matchSecond / 60);
          }

          return '0';
}

function playlist_service_slugify_filename(string $text, string $fallback = 'clip', array $allowed = ['_', '-', '(', ')', '+']): string
{
          $value = trim($text);
          if ($value === '') {
                    return $fallback;
          }

          $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT', $value);
          if ($transliterated !== false) {
                    $value = $transliterated;
          }

          $value = preg_replace('/\s+/', '_', $value);
          $allowedPattern = $allowed ? preg_quote(implode('', $allowed), '/') : '';
          $value = preg_replace('/[^A-Za-z0-9' . $allowedPattern . ']+/', '_', $value);
          $value = preg_replace('/_+/', '_', $value);
          $value = trim($value, '_');

          return $value !== '' ? $value : $fallback;
}

function playlist_service_build_playlist_zip_filename(array $playlist, ?array $match): string
{
          $segments = [];
          $segments[] = playlist_service_slugify_filename($playlist['title'] ?? 'playlist', 'playlist', ['_']);

          $homeTeam = $match['home_team'] ?? '';
          $awayTeam = $match['away_team'] ?? '';
          if ($homeTeam !== '' || $awayTeam !== '') {
                    $homeSlug = playlist_service_slugify_filename($homeTeam, 'home', ['_']);
                    $awaySlug = playlist_service_slugify_filename($awayTeam, 'away', ['_']);
                    $segments[] = $homeSlug . '-vs-' . $awaySlug;
          }

          $dateLabel = playlist_service_format_kickoff_date($match['kickoff_at'] ?? null);
          if ($dateLabel !== '') {
                    $segments[] = $dateLabel;
          }

          $raw = implode('_', array_filter($segments));
          return playlist_service_slugify_filename($raw, 'playlist', ['_', '-', '+']);
}

function playlist_service_format_kickoff_date(?string $kickoffAt): string
{
          if (empty($kickoffAt)) {
                    return '';
          }

          $timestamp = strtotime($kickoffAt);
          if ($timestamp === false) {
                    return '';
          }

          return date('Y-m-d', $timestamp);
}

function playlist_service_make_unique_clip_name(string $base, array &$seen): string
{
          $candidate = $base;
          $suffix = 1;

          while (isset($seen[$candidate])) {
                    $suffix++;
                    $candidate = $base . '_' . $suffix;
          }

          $seen[$candidate] = true;
          return $candidate;
}

function is_legacy_auto_clip(array $clip): bool
{
          $source = strtolower(trim((string)($clip['generation_source'] ?? '')));
          if ($source === 'event_auto') {
                    return true;
          }

          $clipName = trim((string)($clip['clip_name'] ?? ''));
          if ($clipName === '') {
                    return true;
          }

          if (stripos($clipName, 'Auto clip') === 0 || stripos($clipName, 'Auto_clip') === 0) {
                    return true;
          }

          if (preg_match('/@[^@]*s$/i', $clipName)) {
                    return true;
          }

          $version = isset($clip['generation_version']) ? (int)$clip['generation_version'] : 0;
          if ($version > 0 && $version < 4) {
                    return true;
          }

          return false;
}
