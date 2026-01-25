<?php
declare(strict_types=1);

require_once __DIR__ . '/playlist_repository.php';
require_once __DIR__ . '/clip_repository.php';

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
                              // Use a temporary ID for filename, will rename after DB insert
                              $mp4Created = false;
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
                              $mp4Path = ensure_clip_mp4_exists($tempClip);
                              // Rename file to match_{match_id}_{clip_id}.mp4 if needed
                              $documentRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', DIRECTORY_SEPARATOR);
                              $clipsDir = $documentRoot . "/videos/clips";
                              $finalPath = $clipsDir . "/match_{$matchId}_{$newClipId}.mp4";
                              if ($mp4Path !== $finalPath) {
                                   @rename($mp4Path, $finalPath);
                              }
                              $clip = [
                                   'id' => $newClipId,
                                   'clip_id' => $newClipId,
                                   'match_id' => $matchId,
                                   'clip_name' => $clipName,
                                   'start_second' => $startSecond,
                                   'end_second' => $endSecond,
                              ];
                              $clipId = $newClipId;
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
                         // --- Ensure mp4 is generated for this clip ---
                         require_once __DIR__ . '/clip_mp4_service.php';
                         try {
                              ensure_clip_mp4_exists($clipRow);
                         } catch (\Throwable $mp4ex) {
                              // Optionally log or handle mp4 generation errors
                              // error_log('Clip mp4 generation failed: ' . $mp4ex->getMessage());
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
 * Format: EventType_PlayerName_TeamName_Date
 * Example: Goal_John_Smith_Home_2026-01-18
 */
function generate_clip_name_from_event(array $event, int $matchId): string
{
          $parts = [];
          
          // Add event type label (Goal, Shot, Foul, etc.)
          if (!empty($event['event_type_label'])) {
                    $parts[] = str_replace(' ', '_', $event['event_type_label']);
          }
          
          // Add player name if available
          if (!empty($event['match_player_name'])) {
                    // Remove extra spaces and replace with underscore
                    $playerName = str_replace(' ', '_', trim($event['match_player_name']));
                    $parts[] = $playerName;
          }
          
          // Add team side (Home/Away)
          if (!empty($event['match_player_team_side'])) {
                    $parts[] = ucfirst($event['match_player_team_side']);
          }
          
          // Add match date if available
          if (!empty($event['match_id'])) {
                    $match = get_match((int)$event['match_id']);
                    if ($match && !empty($match['kickoff_at'])) {
                              $date = date('Y-m-d', strtotime($match['kickoff_at']));
                              $parts[] = $date;
                    }
          }
          
          // Join all parts with underscore; if empty, use generic name
          $clipName = implode('_', array_filter($parts));
          return !empty($clipName) ? $clipName : 'clip';
}
