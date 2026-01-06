<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/playlist_playback_service.php';

/**
 * Session-backed state for playlist player preferences.
 *
 * Stored per session to keep scope limited while the feature is under construction.
 */
function playlist_player_state_defaults(): array
{
          return [
                    'mode' => PLAYLIST_PLAYBACK_MODE_FULL_MATCH,
                    'current_clip_id' => null,
                    'current_time' => 0.0,
                    'autoplay_next' => true,
                    'loop_clip' => false,
          ];
}

function playlist_player_state_session_namespace(): string
{
          return 'playlist_player_state';
}

function playlist_player_state_read(int $playlistId): array
{
          auth_boot();

          $namespace = playlist_player_state_session_namespace();
          $states = $_SESSION[$namespace] ?? [];
          if (!is_array($states)) {
                    $states = [];
          }

          $stored = $states[$playlistId] ?? [];

          return array_merge(playlist_player_state_defaults(), is_array($stored) ? $stored : []);
}

/**
 * Merge new values into the stored state. Invalid keys are ignored.
 */
function playlist_player_state_write(int $playlistId, array $updates): array
{
          $state = playlist_player_state_read($playlistId);

          foreach ($updates as $key => $value) {
                    switch ($key) {
                              case 'mode':
                                        $state['mode'] = playlist_playback_normalize_mode((string)$value);
                                        break;
                              case 'current_clip_id':
                                        $state['current_clip_id'] = $value === null ? null : (int)$value;
                                        break;
                              case 'current_time':
                                        $state['current_time'] = max(0.0, (float)$value);
                                        break;
                              case 'autoplay_next':
                                        $state['autoplay_next'] = (bool)$value;
                                        break;
                              case 'loop_clip':
                                        $state['loop_clip'] = (bool)$value;
                                        break;
                    }
          }

          $namespace = playlist_player_state_session_namespace();
          if (!isset($_SESSION[$namespace]) || !is_array($_SESSION[$namespace])) {
                    $_SESSION[$namespace] = [];
          }
          $_SESSION[$namespace][$playlistId] = $state;

          return $state;
}
