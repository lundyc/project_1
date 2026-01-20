<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/club_repository.php';

/**
 * Resolve the active club context when serving statistics requests.
 *
 * Platform admins can switch clubs via session state, query params, or a default fallback.
 * Regular club users are bound to their own club_id and cannot override it.
 *
 * @return array{club_id:int, club:array|null}
 */
function resolve_club_context_for_stats(): array
{
          auth_boot();
          require_auth();

          $user = current_user();
          $roles = $_SESSION['roles'] ?? [];

          $clubId = (int)($user['club_id'] ?? 0);

          if ($clubId > 0) {
                    $club = get_club_by_id($clubId);
                    if ($club) {
                              $_SESSION['stats_club_id'] = $clubId;
                              return ['club_id' => $clubId, 'club' => $club];
                    }
          }

          if (!in_array('platform_admin', $roles, true)) {
                    http_response_code(403);
                    echo json_encode(['error' => 'User does not belong to a club']);
                    exit;
          }

          $clubId = 0;

          if (!empty($_SESSION['stats_club_id'])) {
                    $candidate = (int)$_SESSION['stats_club_id'];
                    if ($candidate > 0) {
                              $club = get_club_by_id($candidate);
                              if ($club) {
                                        $_SESSION['stats_club_id'] = $candidate;
                                        return ['club_id' => $candidate, 'club' => $club];
                              }
                    }
          }

          $requestedClubId = isset($_GET['club_id']) ? (int)$_GET['club_id'] : 0;
          if ($requestedClubId > 0) {
                    $club = get_club_by_id($requestedClubId);
                    if (!$club) {
                              http_response_code(403);
                              echo json_encode(['error' => 'Invalid club selected']);
                              exit;
                    }

                    $_SESSION['stats_club_id'] = $requestedClubId;
                    return ['club_id' => $requestedClubId, 'club' => $club];
          }

          $clubs = get_all_clubs();
          if (!empty($clubs)) {
                    $club = $clubs[0];
                    $fallbackClubId = (int)($club['id'] ?? 0);
                    if ($fallbackClubId > 0) {
                              $_SESSION['stats_club_id'] = $fallbackClubId;
                              return ['club_id' => $fallbackClubId, 'club' => $club];
                    }
          }

          http_response_code(403);
          echo json_encode(['error' => 'Unable to resolve club for statistics']);
          exit;
}
