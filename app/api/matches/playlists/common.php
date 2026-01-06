<?php

function playlist_api_fetch_match(int $matchId): array
{
          $match = get_match($matchId);
          if (!$match) {
                    api_respond_with_json(404, ['ok' => false, 'error' => 'match_not_found']);
          }

          return $match;
}

function playlist_api_context(int $matchId, bool $requireManage = false): array
{
          $match = playlist_api_fetch_match($matchId);
          $user = current_user();
          $roles = $_SESSION['roles'] ?? [];

          $permitted = $requireManage
                    ? can_manage_match_for_club($user, $roles, (int)$match['club_id'])
                    : can_view_match($user, $roles, (int)$match['club_id']);

          if (!$permitted) {
                    api_respond_with_json(403, ['ok' => false, 'error' => 'forbidden']);
          }

          return [
                    'match' => $match,
                    'user' => $user,
                    'roles' => $roles,
          ];
}

function playlist_api_require_manage(int $matchId): array
{
          return playlist_api_context($matchId, true);
}

function playlist_api_require_view(int $matchId): array
{
          return playlist_api_context($matchId, false);
}

function playlist_api_require_playlist(int $playlistId, int $matchId): array
{
          $playlist = playlist_get_by_id($playlistId);
          if (!$playlist || (int)$playlist['match_id'] !== $matchId) {
                    api_respond_with_json(404, ['ok' => false, 'error' => 'playlist_not_found']);
          }

          return $playlist;
}
