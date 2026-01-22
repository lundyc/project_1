<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../app/lib/auth.php';
require_once __DIR__ . '/../app/lib/router.php';
require_once __DIR__ . '/../app/lib/phase3.php';
require_once __DIR__ . '/../app/middleware/require_admin.php';

auth_boot();
require_once __DIR__ . '/../app/routes/admin_players.php';
require_once __DIR__ . '/../app/routes/admin_playlists.php';

route('/login', function () {
          require __DIR__ . '/../app/views/pages/login.php';
});

route('/api/login', function () {
          require __DIR__ . '/../app/api/login.php';
});

route('/logout', function () {
          require __DIR__ . '/../app/api/logout.php';
});

route('/matches', function () {
          require_auth();
          require __DIR__ . '/../app/views/pages/matches/index.php';
});

route('/matches/create', function () {
          require_auth();
          require_once __DIR__ . '/../app/lib/match_permissions.php';

          $user = current_user();
          $roles = $_SESSION['roles'] ?? [];

          if (!can_manage_matches($user, $roles)) {
                    http_response_code(403);
                    echo '403 Forbidden';
                    return;
          }

          $match = null;
          require __DIR__ . '/../app/views/pages/matches/form.php';
});

route('/api/matches/create', function () {
          require_auth();
          require __DIR__ . '/../app/api/matches/create.php';
});

route('/admin', function () {
          require_admin();
          require __DIR__ . '/../app/views/pages/admin/dashboard.php';
});

route('/admin/clubs', function () {
          require_role('platform_admin');
          require __DIR__ . '/../app/views/pages/admin/clubs.php';
});

route('/admin/users', function () {
          require_role('platform_admin');
          require __DIR__ . '/../app/views/pages/admin/users.php';
});

route('/admin/seasons', function () {
          require_admin();
          require __DIR__ . '/../app/views/pages/admin/seasons.php';
});

route('/admin/competitions', function () {
          require_admin();
          require __DIR__ . '/../app/views/pages/admin/competitions.php';
});

route('/api/admin/clubs/create', function () {
          require_role('platform_admin');
          require __DIR__ . '/../app/api/admin/create_club.php';
});

route('/api/admin/users/create', function () {
          require_role('platform_admin');
          require __DIR__ . '/../app/api/admin/create_user.php';
});

route('/api/teams/create', function () {
          require_auth();
          require __DIR__ . '/../app/api/teams/create.php';
});

route('/api/videos/browse', function () {
          require_auth();
          require __DIR__ . '/../app/api/videos/browse.php';
});

/* Video Lab APIs temporarily disabled
route('/api/video_status', function () {
          require __DIR__ . '/../app/api/matches/video_status.php';
});

route('/api/match-video/progress', function () {
          require_auth();
          require __DIR__ . '/../app/api/match-video/progress.php';
});

route('/api/match-video/retry', function () {
          require_auth();
          require __DIR__ . '/../app/api/match-video/retry.php';
});

route('/api/match-video/start', function () {
          require_auth();
          require __DIR__ . '/../app/api/matches/video_veo.php';
});
*/

route('/api/events/undo', function () {
          require_auth();
          require __DIR__ . '/../app/api/events/undo.php';
});

route('/api/events/redo', function () {
          require_auth();
          require __DIR__ . '/../app/api/events/redo.php';
});

route('/api/stats/overview', function () {
          require_auth();
          require __DIR__ . '/../app/api/stats/overview.php';
});

route('/api/stats/team-performance', function () {
          require_auth();
          require __DIR__ . '/../app/api/stats/team-performance.php';
});

route('/api/stats/player-performance', function () {
          require_auth();
          require __DIR__ . '/../app/api/stats/player-performance.php';
});

route('/api/stats/matches', function () {
          require_auth();
          require __DIR__ . '/../app/api/stats/matches.php';
});

route('/api/stats/match/overview', function () {
          require_auth();
          require __DIR__ . '/../app/api/stats/match/overview.php';
});

route('/api/stats/match/team-performance', function () {
          require_auth();
          require __DIR__ . '/../app/api/stats/match/team-performance.php';
});

route('/api/stats/match/player-performance', function () {
          require_auth();
          require __DIR__ . '/../app/api/stats/match/player-performance.php';
});

route('/api/stats/match/visuals', function () {
          require_auth();
          require __DIR__ . '/../app/api/stats/match/visuals.php';
});

route('/api/teams/create-json', function () {
          require_auth();
          require __DIR__ . '/../app/api/teams/create-json.php';
});

route('/api/seasons/create', function () {
          require_auth();
          require __DIR__ . '/../app/api/seasons/create.php';
});
route('/api/seasons/update', function () {
          require_auth();
          require __DIR__ . '/../app/api/seasons/update.php';
});
route('/api/seasons/delete', function () {
          require_auth();
          require __DIR__ . '/../app/api/seasons/delete.php';
});

route('/api/competitions/create', function () {
          require_auth();
          require __DIR__ . '/../app/api/competitions/create.php';
});
route('/api/competitions/update', function () {
          require_auth();
          require __DIR__ . '/../app/api/competitions/update.php';
});
route('/api/competitions/delete', function () {
          require_auth();
          require __DIR__ . '/../app/api/competitions/delete.php';
});
route('/api/competitions/add-team', function () {
          require_auth();
          require __DIR__ . '/../app/api/competitions/add_team.php';
});
route('/api/competitions/remove-team', function () {
          require_auth();
          require __DIR__ . '/../app/api/competitions/remove_team.php';
});
route('/api/match-players/list', function () {
          require_auth();
          require __DIR__ . '/../app/api/match-players/list.php';
});

route('/api/match-players/add', function () {
          require_auth();
          require __DIR__ . '/../app/api/match-players/add.php';
});

route('/api/match-players/update', function () {
          require_auth();
          require __DIR__ . '/../app/api/match-players/update.php';
});

route('/api/match-players/delete', function () {
          require_auth();
          require __DIR__ . '/../app/api/match-players/delete.php';
});
route('/api/matches/{id}/substitutions', function () {
          require_auth();
          require __DIR__ . '/../app/api/matches/substitutions.php';
});

route('/api/match-substitutions/list', function () {
          require_auth();
          require __DIR__ . '/../app/api/match-substitutions/list.php';
});

route('/api/match-substitutions/create', function () {
          require_auth();
          require __DIR__ . '/../app/api/match-substitutions/create.php';
});

route('/api/match-substitutions/delete', function () {
          require_auth();
          require __DIR__ . '/../app/api/match-substitutions/delete.php';
});

route('/api/formations/list', function () {
          require_auth();
          require __DIR__ . '/../app/api/formations/list.php';
});

route('/api/match-formations/update', function () {
          require_auth();
          require __DIR__ . '/../app/api/match-formations/update.php';
});

route('/api/players/list', function () {
          require_auth();
          require __DIR__ . '/../app/api/players/list.php';
});

route('/api/players/search', function () {
          require_auth();
          require __DIR__ . '/../app/api/players/search.php';
});

route('/api/players/create', function () {
          require_auth();
          require __DIR__ . '/../app/api/players/create.php';
});

route('/stats', function () {
          require_auth();
          require_once __DIR__ . '/../app/controllers/StatsController.php';
          StatsController::dashboard();
});

route('/stats/match/{id}', function () {
          require_auth();
          require_once __DIR__ . '/../app/controllers/StatsController.php';
          StatsController::match((int)($_GET['id'] ?? 0));
});

route('/', function () {
          require_auth();
          require __DIR__ . '/../app/views/pages/dashboard.php';
});

function render_match_stats(int $matchId): bool
{
          require_auth();
          require_once __DIR__ . '/../app/lib/match_repository.php';
          require_once __DIR__ . '/../app/lib/match_permissions.php';
          require_once __DIR__ . '/../app/lib/event_repository.php';
          require_once __DIR__ . '/../app/lib/match_stats_service.php';
          require_once __DIR__ . '/../app/lib/match_period_repository.php';

          $match = get_match($matchId);

          if (!$match) {
                    http_response_code(404);
                    echo '404 Not Found';
                    return true;
          }

          $user = current_user();
          $roles = $_SESSION['roles'] ?? [];

          if (!can_view_match($user, $roles, (int)$match['club_id'])) {
                    http_response_code(403);
                    echo '403 Forbidden';
                    return true;
          }

          ensure_default_event_types((int)$match['club_id']);

          $events = event_list_for_match($matchId);

          $eventTypesStmt = db()->prepare('SELECT id, type_key, label FROM event_types WHERE club_id = :club_id ORDER BY label ASC');
          $eventTypesStmt->execute(['club_id' => (int)$match['club_id']]);
          $eventTypes = $eventTypesStmt->fetchAll();

          $derivedStats = get_or_compute_match_stats((int)$match['id'], (int)$match['events_version'], $events, $eventTypes);
          $matchPeriods = get_match_periods($matchId);

          require __DIR__ . '/../app/views/pages/matches/stats.php';
          return true;
}

/* Video Lab helpers temporarily disabled
function resolve_video_progress_file(int $matchId): ?string
{
          $storageDir = realpath(__DIR__ . '/../storage');
          if (!$storageDir) {
                    return null;
          }
          $progressFile = $storageDir . DIRECTORY_SEPARATOR . 'video_progress' . DIRECTORY_SEPARATOR . $matchId . '.json';
          return is_file($progressFile) ? $progressFile : null;
}

function is_video_progress_completed(int $matchId): bool
{
          $progressFile = resolve_video_progress_file($matchId);
          if (!$progressFile) {
                    return false;
          }
          $contents = @file_get_contents($progressFile);
          if ($contents === false) {
                    return false;
          }
          $decoded = json_decode($contents, true);
          if (!is_array($decoded)) {
                    return false;
          }
          $status = strtolower(trim((string)($decoded['status'] ?? '')));
          return $status === 'completed';
}
*/

function handle_dynamic_match_routes(string $path): bool
{
          if (preg_match('#^/matches/(\d+)/desk$#', $path, $m)) {
                    require_auth();
                    require_once __DIR__ . '/../app/lib/match_repository.php';
                    require_once __DIR__ . '/../app/lib/match_permissions.php';
                    // require_once __DIR__ . '/../app/lib/match_stats_service.php'; // Video Lab disabled

                    $matchId = (int)$m[1];
                    $match = get_match($matchId);

                    if (!$match) {
                              http_response_code(404);
                              echo '404 Not Found';
                              return true;
                    }

                    $user = current_user();
                    $roles = $_SESSION['roles'] ?? [];

                    if (!can_view_match($user, $roles, (int)$match['club_id'])) {
                              http_response_code(403);
                              echo '403 Forbidden';
                              return true;
                    }

                    /*
                    $videoStatus = $match['video_download_status'] ?? null;
                    $videoProgress = (int)($match['video_download_progress'] ?? 0);
                    $progressCompleted = is_video_progress_completed($matchId);
                    $videoReady = !empty($match['video_source_path']);
                    $projectRoot = realpath(__DIR__ . '/..');
                    $standardRelative = '/videos/matches/match_' . $matchId . '/source/veo/standard/match_' . $matchId . '_standard.mp4';
                    $standardAbsolute = $projectRoot
                              ? $projectRoot . DIRECTORY_SEPARATOR . 'videos' . DIRECTORY_SEPARATOR . 'matches' . DIRECTORY_SEPARATOR . 'match_' . $matchId . DIRECTORY_SEPARATOR . 'source' . DIRECTORY_SEPARATOR . 'veo' . DIRECTORY_SEPARATOR . 'standard' . DIRECTORY_SEPARATOR . 'match_' . $matchId . '_standard.mp4'
                              : '';
                    $standardReady = $standardAbsolute && is_file($standardAbsolute);

                    if (($match['video_source_type'] ?? '') === 'veo') {
                              if ($videoStatus !== null) {
                                        $videoReady = $videoReady && ($videoStatus === 'completed');
                              } else {
                                        $videoReady = $videoReady && $progressCompleted;
                              }
                    }

                    if (!$videoReady && $standardReady && ($videoStatus === 'completed' || $progressCompleted)) {
                              $videoReady = true;
                              $match['video_source_path'] = $standardRelative;
                              if (empty($match['video_source_type'])) {
                                        $match['video_source_type'] = 'veo';
                              }
                    }
                    */

                    require __DIR__ . '/../app/views/pages/matches/desk.php';
                    return true;
          }

          if (preg_match('#^/matches/(\d+)/lineup$#', $path, $m)) {
                    require_auth();
                    require_once __DIR__ . '/../app/lib/match_repository.php';
                    require_once __DIR__ . '/../app/lib/match_permissions.php';
                    require_once __DIR__ . '/../app/lib/team_repository.php';
                    require_once __DIR__ . '/../app/lib/formation_repository.php';

                    $matchId = (int)$m[1];
                    $match = get_match($matchId);

                    if (!$match) {
                              http_response_code(404);
                              echo '404 Not Found';
                              return true;
                    }

                    $user = current_user();
                    $roles = $_SESSION['roles'] ?? [];

                    if (!can_view_match($user, $roles, (int)$match['club_id'])) {
                              http_response_code(403);
                              echo '403 Forbidden';
                              return true;
                   }

                    $homeFormation = get_active_match_formation($matchId, 'home');
                    $awayFormation = get_active_match_formation($matchId, 'away');
                    require __DIR__ . '/../app/views/pages/matches/lineup.php';
                    return true;
          }

          if (preg_match('#^/matches/(\d+)$#', $path, $m)) {
                    return render_match_stats((int)$m[1]);
          }

          if (preg_match('#^/matches/(\d+)/stats$#', $path, $m)) {
                    return render_match_stats((int)$m[1]);
          }

          if (preg_match('#^/matches/(\d+)/summary$#', $path, $m)) {
                    redirect('/matches/' . $m[1] . '/stats');
                    return true;
          }

          if (preg_match('#^/matches/(\d+)/(?:summary|stats)/recompute$#', $path, $m)) {
                   require_auth();
                   require_once __DIR__ . '/../app/lib/match_repository.php';
                   require_once __DIR__ . '/../app/lib/match_permissions.php';
                   require_once __DIR__ . '/../app/lib/event_repository.php';
                   require_once __DIR__ . '/../app/lib/match_stats_service.php';

                    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
                              http_response_code(405);
                              echo '405 Method Not Allowed';
                              return true;
                    }

                    $matchId = (int)$m[1];
                    $match = get_match($matchId);

                    if (!$match) {
                              http_response_code(404);
                              echo '404 Not Found';
                              return true;
                    }

                    $user = current_user();
                    $roles = $_SESSION['roles'] ?? [];

                    if (!can_view_match($user, $roles, (int)$match['club_id'])) {
                              http_response_code(403);
                              echo '403 Forbidden';
                              return true;
                    }

                    ensure_default_event_types((int)$match['club_id']);

                    $events = event_list_for_match($matchId);
                    $eventTypesStmt = db()->prepare('SELECT id, type_key, label FROM event_types WHERE club_id = :club_id ORDER BY label ASC');
                    $eventTypesStmt->execute(['club_id' => (int)$match['club_id']]);
                    $eventTypes = $eventTypesStmt->fetchAll();

                    get_or_compute_match_stats((int)$match['id'], (int)$match['events_version'], $events, $eventTypes, true);
                    $_SESSION['stats_flash_success'] = 'Stats recomputed';
                    redirect('/matches/' . $matchId . '/stats');
                    return true;
          }

          if (preg_match('#^/matches/(\d+)/periods$#', $path, $m)) {
                    require_auth();
                    require_once __DIR__ . '/../app/lib/match_repository.php';
                    require_once __DIR__ . '/../app/lib/match_permissions.php';
                    require_once __DIR__ . '/../app/lib/match_period_repository.php';

                    $matchId = (int)$m[1];
                    $match = get_match($matchId);

                    if (!$match) {
                              http_response_code(404);
                              echo '404 Not Found';
                              return true;
                    }

                    require __DIR__ . '/../app/views/pages/matches/periods.php';
                    return true;
          }

          if (preg_match('#^/api/matches/(\d+)/periods/preset$#', $path, $m)) {
                    $matchId = (int)$m[1];
                    require __DIR__ . '/../app/api/matches/periods_preset.php';
                    return true;
          }

          if (preg_match('#^/api/matches/(\d+)/periods/custom$#', $path, $m)) {
                    $matchId = (int)$m[1];
                    require __DIR__ . '/../app/api/matches/periods_custom.php';
                    return true;
          }

          if (preg_match('#^/matches/(\d+)/roster$#', $path, $m)) {
                    require_auth();
                    require_once __DIR__ . '/../app/lib/match_repository.php';
                    require_once __DIR__ . '/../app/lib/match_permissions.php';
                    require_once __DIR__ . '/../app/lib/match_player_repository.php';

                    $matchId = (int)$m[1];
                    $match = get_match($matchId);

                    if (!$match) {
                              http_response_code(404);
                              echo '404 Not Found';
                              return true;
                    }

                    require __DIR__ . '/../app/views/pages/matches/roster.php';
                    return true;
          }

          if (preg_match('#^/api/matches/(\d+)/roster/save$#', $path, $m)) {
                    $matchId = (int)$m[1];
                    require __DIR__ . '/../app/api/matches/roster_save.php';
                    return true;
          }

          if (preg_match('#^/matches/(\d+)/edit$#', $path, $m)) {
                    require_auth();
                    require_once __DIR__ . '/../app/lib/match_repository.php';
                    require_once __DIR__ . '/../app/lib/match_permissions.php';

                    $matchId = (int)$m[1];
                    $match = get_match($matchId);

                    if (!$match) {
                              http_response_code(404);
                              echo '404 Not Found';
                              return true;
                    }

                    $user = current_user();
                    $roles = $_SESSION['roles'] ?? [];

                    if (!can_manage_match_for_club($user, $roles, (int)$match['club_id'])) {
                              http_response_code(403);
                              echo '403 Forbidden';
                              return true;
                    }

                    require __DIR__ . '/../app/views/pages/matches/edit.php';
                    return true;
              }

              if (preg_match('#^/matches/(\d+)/video$#', $path, $m)) {
                        require_auth();
                        require_once __DIR__ . '/../app/lib/match_repository.php';
                        require_once __DIR__ . '/../app/lib/match_permissions.php';

                        $matchId = (int)$m[1];
                        $match = get_match($matchId);

                        if (!$match) {
                                  http_response_code(404);
                                  echo '404 Not Found';
                                  return true;
                        }

                        $user = current_user();
                        $roles = $_SESSION['roles'] ?? [];

                        if (!can_manage_match_for_club($user, $roles, (int)$match['club_id'])) {
                                  http_response_code(403);
                                  echo '403 Forbidden';
                                  return true;
                        }

                        require __DIR__ . '/../app/views/pages/matches/edit_step2.php';
                        return true;
          }

              if (preg_match('#^/api/matches/(\d+)/update-details$#', $path, $m)) {
                        $matchId = (int)$m[1];
                        require __DIR__ . '/../app/api/matches/update-details.php';
                        return true;
              }

              if (preg_match('#^/api/matches/(\d+)/update-video$#', $path, $m)) {
                        $matchId = (int)$m[1];
                        require __DIR__ . '/../app/api/matches/update-video.php';
                        return true;
              }

          if (preg_match('#^/api/matches/(\d+)/lock/acquire$#', $path, $m)) {
                    $matchId = (int)$m[1];
                    require __DIR__ . '/../app/api/matches/lock_acquire.php';
                    return true;
          }

          if (preg_match('#^/api/matches/(\d+)/lock/heartbeat$#', $path, $m)) {
                    $matchId = (int)$m[1];
                    require __DIR__ . '/../app/api/matches/lock_heartbeat.php';
                    return true;
          }

          if (preg_match('#^/api/matches/(\d+)/lock/release$#', $path, $m)) {
                    $matchId = (int)$m[1];
                    require __DIR__ . '/../app/api/matches/lock_release.php';
                    return true;
          }

          if (preg_match('#^/api/matches/(\d+)/events$#', $path, $m)) {
                    $matchId = (int)$m[1];
                    require __DIR__ . '/../app/api/events/list.php';
                    return true;
          }

          if (preg_match('#^/api/matches/(\d+)/events/create$#', $path, $m)) {
                    $matchId = (int)$m[1];
                    require __DIR__ . '/../app/api/events/create.php';
                    return true;
          }

          if (preg_match('#^/api/matches/(\d+)/events/update$#', $path, $m)) {
                    $matchId = (int)$m[1];
                    require __DIR__ . '/../app/api/events/update.php';
                    return true;
          }

          if (preg_match('#^/api/matches/(\d+)/events/delete$#', $path, $m)) {
                    $matchId = (int)$m[1];
                    require __DIR__ . '/../app/api/events/delete.php';
                    return true;
          }

          if (preg_match('#^/api/matches/(\d+)/annotations/create$#', $path, $m)) {
                    $matchId = (int)$m[1];
                    require __DIR__ . '/../app/api/matches/annotations/create.php';
                    return true;
          }

          if (preg_match('#^/api/matches/(\d+)/annotations/update$#', $path, $m)) {
                    $matchId = (int)$m[1];
                    require __DIR__ . '/../app/api/matches/annotations/update.php';
                    return true;
          }

          if (preg_match('#^/api/matches/(\d+)/annotations/delete$#', $path, $m)) {
                    $matchId = (int)$m[1];
                    require __DIR__ . '/../app/api/matches/annotations/delete.php';
                    return true;
          }

          if (preg_match('#^/api/matches/(\d+)/annotations$#', $path, $m)) {
                    $matchId = (int)$m[1];
                    require __DIR__ . '/../app/api/matches/annotations/list.php';
                    return true;
          }

          if (preg_match('#^/api/matches/(\d+)/periods/start$#', $path, $m)) {
                    $matchId = (int)$m[1];
                    require __DIR__ . '/../app/api/matches/periods_start.php';
                    return true;
          }

          if (preg_match('#^/api/matches/(\d+)/periods/end$#', $path, $m)) {
                    $matchId = (int)$m[1];
                    require __DIR__ . '/../app/api/matches/periods_end.php';
                    return true;
          }

          if (preg_match('#^/api/matches/(\d+)/periods$#', $path, $m)) {
                    $matchId = (int)$m[1];
                    require __DIR__ . '/../app/api/matches/periods_list.php';
                    return true;
          }

          if (preg_match('#^/api/matches/(\d+)/share$#', $path, $m)) {
                    $matchId = (int)$m[1];
                    require __DIR__ . '/../app/api/matches/share.php';
                    return true;
          }

          if ($path === '/api/match-periods/set') {
                    require __DIR__ . '/../app/api/match-periods/set.php';
                    return true;
          }

          if (preg_match('#^/api/matches/(\d+)/video/veo/start$#', $path, $m)) {
                    $matchId = (int)$m[1];
                    require __DIR__ . '/../app/api/matches/video_veo.php';
                    return true;
          }

          if (preg_match('#^/api/matches/(\d+)/video/veo/cancel$#', $path, $m)) {
                    $matchId = (int)$m[1];
                    require __DIR__ . '/../app/api/matches/video_veo_cancel.php';
                    return true;
          }

          if (preg_match('#^/api/matches/(\d+)/video/veo$#', $path, $m)) {
                    $matchId = (int)$m[1];
                    require __DIR__ . '/../app/api/matches/video_veo.php';
                    return true;
          }

          if (preg_match('#^/api/matches/(\d+)/video_status$#', $path, $m)) {
                    $matchId = (int)$m[1];
                    require __DIR__ . '/../app/api/matches/video_status.php';
                    return true;
          }

          if (preg_match('#^/api/matches/(\d+)/video/status$#', $path, $m)) {
                    $matchId = (int)$m[1];
                    require __DIR__ . '/../app/api/matches/video_status.php';
                    return true;
          }

          if (preg_match('#^/api/matches/(\d+)/video/progress$#', $path, $m)) {
                    $matchId = (int)$m[1];
                    require __DIR__ . '/../app/api/matches/video_status.php';
                    return true;
          }

          if (preg_match('#^/api/matches/(\d+)/video-status$#', $path, $m)) {
                    $matchId = (int)$m[1];
                    require __DIR__ . '/../app/api/matches/video_status.php';
                    return true;
          }

          if (preg_match('#^/api/matches/(\d+)/clips/create$#', $path, $m)) {
                    $matchId = (int)$m[1];
                    require __DIR__ . '/../app/api/matches/clips_create.php';
                    return true;
          }

          if (preg_match('#^/api/matches/(\d+)/clips/delete$#', $path, $m)) {
                    $matchId = (int)$m[1];
                    require __DIR__ . '/../app/api/matches/clips_delete.php';
                    return true;
          }

          if (preg_match('#^/api/matches/(\d+)/playlists/(\d+)$#', $path, $m)) {
                    $matchId = (int)$m[1];
                    $playlistId = (int)$m[2];
                    require __DIR__ . '/../app/api/matches/playlists/show.php';
                    return true;
          }

          if (preg_match('#^/api/matches/(\d+)/playlists$#', $path, $m)) {
                    $matchId = (int)$m[1];
                    require __DIR__ . '/../app/api/matches/playlists/list.php';
                    return true;
          }

          if (preg_match('#^/api/matches/(\d+)/playlists/download$#', $path, $m)) {
                    $matchId = (int)$m[1];
                    require __DIR__ . '/../app/api/matches/playlists/download.php';
                    return true;
          }

          if (preg_match('#^/api/matches/(\d+)/playlists/create$#', $path, $m)) {
                    $matchId = (int)$m[1];
                    require __DIR__ . '/../app/api/matches/playlists/create.php';
                    return true;
          }

          if (preg_match('#^/api/matches/(\d+)/playlists/rename$#', $path, $m)) {
                    $matchId = (int)$m[1];
                    require __DIR__ . '/../app/api/matches/playlists/rename.php';
                    return true;
          }

          if (preg_match('#^/api/matches/(\d+)/playlists/notes$#', $path, $m)) {
                    $matchId = (int)$m[1];
                    require __DIR__ . '/../app/api/matches/playlists/notes.php';
                    return true;
          }

          if (preg_match('#^/api/matches/(\d+)/playlists/delete$#', $path, $m)) {
                    $matchId = (int)$m[1];
                    require __DIR__ . '/../app/api/matches/playlists/delete.php';
                    return true;
          }

          if (preg_match('#^/api/matches/(\d+)/playlists/clips/add$#', $path, $m)) {
                    $matchId = (int)$m[1];
                    require __DIR__ . '/../app/api/matches/playlists/clips_add.php';
                    return true;
          }

          if (preg_match('#^/api/matches/(\d+)/playlists/clips/remove$#', $path, $m)) {
                    $matchId = (int)$m[1];
                    require __DIR__ . '/../app/api/matches/playlists/clips_remove.php';
                    return true;
          }

          if (preg_match('#^/api/matches/(\d+)/playlists/clips/reorder$#', $path, $m)) {
                    $matchId = (int)$m[1];
                    require __DIR__ . '/../app/api/matches/playlists/clips_reorder.php';
                    return true;
          }

          if (preg_match('#^/api/matches/(\d+)/edit$#', $path, $m)) {
                    $matchId = (int)$m[1];
                    require __DIR__ . '/../app/api/matches/update.php';
                    return true;
          }

          if (preg_match('#^/api/matches/(\d+)/delete$#', $path, $m)) {
                    $matchId = (int)$m[1];
                    require __DIR__ . '/../app/api/matches/delete.php';
                    return true;
          }

          return false;
}

$__uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$__basePath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$__basePath = rtrim($__basePath, '/');
if ($__basePath === '/') {
          $__basePath = '';
}
if ($__basePath && str_starts_with($__uri, $__basePath)) {
          $__uri = substr($__uri, strlen($__basePath));
}
$__requestPath = normalize_path($__uri);

if (!handle_dynamic_match_routes($__requestPath)) {
          dispatch();
}
