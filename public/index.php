<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../app/lib/auth.php';
require_once __DIR__ . '/../app/lib/router.php';

auth_boot();
require_once __DIR__ . '/../app/routes/admin_players.php';

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
          require_role('platform_admin');
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

route('/api/video_status', function () {
          require __DIR__ . '/../app/api/matches/video_status.php';
});

route('/api/events/undo', function () {
          require_auth();
          require __DIR__ . '/../app/api/events/undo.php';
});

route('/api/events/redo', function () {
          require_auth();
          require __DIR__ . '/../app/api/events/redo.php';
});

route('/api/teams/create-json', function () {
          require_auth();
          require __DIR__ . '/../app/api/teams/create-json.php';
});

route('/api/seasons/create', function () {
          require_auth();
          require __DIR__ . '/../app/api/seasons/create.php';
});

route('/api/competitions/create', function () {
          require_auth();
          require __DIR__ . '/../app/api/competitions/create.php';
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

route('/api/players/list', function () {
          require_auth();
          require __DIR__ . '/../app/api/players/list.php';
});

route('/api/players/create', function () {
          require_auth();
          require __DIR__ . '/../app/api/players/create.php';
});

route('/', function () {
          require_auth();
          require __DIR__ . '/../app/views/pages/dashboard.php';
});

function handle_dynamic_match_routes(string $path): bool
{
          if (preg_match('#^/matches/(\d+)/desk$#', $path, $m)) {
                    require_auth();
                    require_once __DIR__ . '/../app/lib/match_repository.php';
                    require_once __DIR__ . '/../app/lib/match_permissions.php';
                    require_once __DIR__ . '/../app/lib/match_stats_service.php';

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

                    $videoStatus = $match['video_download_status'] ?? null;
                    $videoProgress = (int)($match['video_download_progress'] ?? 0);
                    $videoReady = !empty($match['video_source_path']);

                    if (($match['video_source_type'] ?? '') === 'veo') {
                              $videoReady = $videoReady && ($videoStatus === 'completed');
                    }

                    if (!$videoReady) {
                              http_response_code(409);
                              $pendingStatus = $videoStatus ?: 'pending';
                              $pendingError = $match['video_error_message'] ?? null;
                              $veoUrl = $match['video_source_url'] ?? null;
                              $pendingProgress = $videoProgress;
                              require __DIR__ . '/../app/views/pages/matches/video_pending.php';
                              return true;
                    }

                    require __DIR__ . '/../app/views/pages/matches/desk.php';
                    return true;
          }

          if (preg_match('#^/matches/(\d+)/summary$#', $path, $m)) {
                    require_auth();
                    require_once __DIR__ . '/../app/lib/match_repository.php';
                    require_once __DIR__ . '/../app/lib/match_permissions.php';
                    require_once __DIR__ . '/../app/lib/event_repository.php';
                    require_once __DIR__ . '/../app/lib/match_stats_service.php';
                    require_once __DIR__ . '/../app/lib/match_period_repository.php';

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

                    $derivedStats = get_or_compute_match_stats((int)$match['id'], (int)$match['events_version'], $events, $eventTypes);
                    $matchPeriods = get_match_periods($matchId);

                    require __DIR__ . '/../app/views/pages/matches/summary.php';
                    return true;
          }

          if (preg_match('#^/matches/(\d+)/summary/recompute$#', $path, $m)) {
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
                    $_SESSION['summary_flash_success'] = 'Stats recomputed';
                    redirect('/matches/' . $matchId . '/summary');
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

                    require __DIR__ . '/../app/views/pages/matches/form.php';
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
