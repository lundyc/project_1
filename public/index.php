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

// League Intelligence Matches CRUD routes
route('/league-intelligence/matches', function () {
    require_role('platform_admin');
    require_once __DIR__ . '/../app/controllers/LeagueIntelligenceMatchController.php';
    $controller = new LeagueIntelligenceMatchController();
    $controller->index();
});

route('/league-intelligence/matches/add', function () {
    require_role('platform_admin');
    require_once __DIR__ . '/../app/controllers/LeagueIntelligenceMatchController.php';
    $controller = new LeagueIntelligenceMatchController();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller->store();
    } else {
        $controller->create();
    }
});

route('/league-intelligence/matches/edit/{id}', function () {
    require_role('platform_admin');
    require_once __DIR__ . '/../app/controllers/LeagueIntelligenceMatchController.php';
    $controller = new LeagueIntelligenceMatchController();
    $id = $_GET['id'] ?? null;
    if (!$id) {
        http_response_code(404);
        echo '404 Not Found';
        return;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller->update($id);
    } else {
        $controller->edit($id);
    }
});

route('/league-intelligence/matches/delete/{id}', function () {
    require_role('platform_admin');
    require_once __DIR__ . '/../app/controllers/LeagueIntelligenceMatchController.php';
    $controller = new LeagueIntelligenceMatchController();
    $id = $_GET['id'] ?? null;
    if (!$id) {
        http_response_code(404);
        echo '404 Not Found';
        return;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller->delete($id);
    } else {
        http_response_code(405);
        echo '405 Method Not Allowed';
    }
});

require_once __DIR__ . '/../app/lib/auth.php';
require_once __DIR__ . '/../app/lib/router.php';
require_once __DIR__ . '/../app/lib/phase3.php';
require_once __DIR__ . '/../app/middleware/require_admin.php';

auth_boot();
require_once __DIR__ . '/../app/routes/admin_players.php';
require_once __DIR__ . '/../app/routes/admin_playlists.php';

route('/', function () {
    require_auth();
    redirect('/stats');
});

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

    require __DIR__ . '/../app/views/pages/matches/create.php';
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

route('/admin/clubs/create', function () {
          require_role('platform_admin');
          require __DIR__ . '/../app/views/pages/admin/clubs/create.php';
});

route('/admin/clubs/{id}/edit', function () {
          require_role('platform_admin');
          require __DIR__ . '/../app/views/pages/admin/clubs/edit.php';
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

route('/api/admin/clubs/update', function () {
          require_role('platform_admin');
          require __DIR__ . '/../app/api/admin/update_club.php';
});

route('/api/admin/clubs/delete', function () {
          require_role('platform_admin');
          require __DIR__ . '/../app/api/admin/delete_club.php';
});

route('/api/admin/clubs/assign-team', function () {
          require_role('platform_admin');
          require __DIR__ . '/../app/api/admin/assign_club_team.php';
});

route('/api/admin/clubs/remove-team', function () {
          require_role('platform_admin');
          require __DIR__ . '/../app/api/admin/remove_club_team.php';
});

route('/admin/teams', function () {
          require_role('platform_admin');
          require __DIR__ . '/../app/views/pages/admin/teams/index.php';
});

route('/admin/teams/create', function () {
          require_role('platform_admin');
          require __DIR__ . '/../app/views/pages/admin/teams/create.php';
});

route('/admin/teams/{id}/edit', function () {
          require_role('platform_admin');
          require __DIR__ . '/../app/views/pages/admin/teams/edit.php';
});

route('/api/admin/teams/create', function () {
          require_role('platform_admin');
          require __DIR__ . '/../app/api/admin/teams/create.php';
});

route('/api/admin/teams/update', function () {
          require_role('platform_admin');
          require __DIR__ . '/../app/api/admin/teams/update.php';
});

route('/api/admin/teams/delete', function () {
          require_role('platform_admin');
          require __DIR__ . '/../app/api/admin/teams/delete.php';
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

route('/api/stats/match/derived', function () {
    require_auth();
    require __DIR__ . '/../app/api/stats/match/derived.php';
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

// league intelligence import routes
route('/league-intelligence/import', function () {
          require_role('platform_admin');
          require_once __DIR__ . '/../app/controllers/LeagueIntelligenceImportController.php';
          $controller = new LeagueIntelligenceImportController(new WosflImportService());
          $controller->showImportForm();
});

route('/league-intelligence/import/run', function () {
          require_role('platform_admin');
          require_once __DIR__ . '/../app/controllers/LeagueIntelligenceImportController.php';
          $controller = new LeagueIntelligenceImportController(new WosflImportService());
          $controller->runImport();
});

route('/league-intelligence/import/save', function () {
          require_role('platform_admin');
          require_once __DIR__ . '/../app/controllers/LeagueIntelligenceImportController.php';
          $controller = new LeagueIntelligenceImportController(new WosflImportService());
          $controller->saveImport();
});

route('/league-intelligence/update-week', function () {
          require_role('platform_admin');
          require_once __DIR__ . '/../app/controllers/LeagueIntelligenceImportController.php';
          $controller = new LeagueIntelligenceImportController(new WosflImportService());
          $controller->updateWeek();
});

route('/league-intelligence', function () {
          require_role('platform_admin');
          $seasonId = !empty($_GET['season_id']) ? (int)$_GET['season_id'] : null;
          $competitionId = !empty($_GET['competition_id']) ? (int)$_GET['competition_id'] : null;

          require_once __DIR__ . '/../app/lib/league_intelligence_service.php';
          $service = new LeagueIntelligenceService($seasonId, $competitionId);

          $leagueTable = $service->getLeagueTable();
          $resultsFixtures = $service->getResultsAndFixtures(5);
          $leagueTrends = $service->getLeagueTrends();
          $teamNavigation = $service->getTeamNavigation();
          $seasonOptions = $service->getSeasonOptions();
          $competitionOptions = $service->getCompetitionOptions();
          $selectedSeason = $service->getSelectedSeason();
          $selectedCompetition = $service->getSelectedCompetition();

          $title = 'League Intelligence';
          require __DIR__ . '/../app/views/pages/league-intelligence/index.php';
});

route('/league-intelligence/team/{id}', function () {
          require_role('platform_admin');
          $teamId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
          if ($teamId <= 0) {
                    http_response_code(404);
                    echo '404 Not Found';
                    return;
          }

          $seasonId = !empty($_GET['season_id']) ? (int)$_GET['season_id'] : null;
          $competitionId = !empty($_GET['competition_id']) ? (int)$_GET['competition_id'] : null;

          require_once __DIR__ . '/../app/lib/league_intelligence_service.php';
          $service = new LeagueIntelligenceService($seasonId, $competitionId);

          $teamInsights = $service->getTeamInsights($teamId);
          if (!$teamInsights) {
                    http_response_code(404);
                    echo '404 Not Found';
                    return;
          }

          $leagueTable = $service->getLeagueTable();
          $teamNavigation = $service->getTeamNavigation();
          $seasonOptions = $service->getSeasonOptions();
          $competitionOptions = $service->getCompetitionOptions();
          $selectedSeason = $service->getSelectedSeason();
          $selectedCompetition = $service->getSelectedCompetition();
          $resultsFixtures = $service->getResultsAndFixtures(5);

          $title = 'Team Profile – ' . $teamInsights['team_name'];
          require __DIR__ . '/../app/views/pages/league-intelligence/team.php';
});

route('/matches/(\d+)/desk', function ($matchId) {
          require_auth();
          require_once __DIR__ . '/../app/lib/match_repository.php';
          require_once __DIR__ . '/../app/lib/match_permissions.php';
          require_once __DIR__ . '/../app/lib/match_stats_service.php';
          require_once __DIR__ . '/../app/lib/match_period_repository.php';
          require_once __DIR__ . '/../app/lib/event_repository.php';

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

          require __DIR__ . '/../app/views/pages/matches/desk.php';
          return true;
});

route('/matches/(\d+)/tv', function ($matchId) {
          require_auth();
          require_once __DIR__ . '/../app/lib/match_repository.php';
          require_once __DIR__ . '/../app/lib/match_permissions.php';
          require_once __DIR__ . '/../app/lib/phase3.php';

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

          require __DIR__ . '/../app/views/pages/matches/tv.php';
          return true;
});

route('/matches/(\d+)/lineup', function ($matchId) {
          require_auth();
          require_once __DIR__ . '/../app/lib/match_repository.php';
          require_once __DIR__ . '/../app/lib/match_permissions.php';
          require_once __DIR__ . '/../app/lib/team_repository.php';
          require_once __DIR__ . '/../app/lib/formation_repository.php';

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
});

route('/matches/(\d+)/stats', function ($matchId) {
          return render_match_stats((int)$matchId);
});

route('/matches/(\d+)/summary', function ($matchId) {
          redirect('/matches/' . $matchId . '/stats');
          return true;
});

route('/matches/(\d+)/periods', function ($matchId) {
          require_auth();
          require_once __DIR__ . '/../app/lib/match_repository.php';
          require_once __DIR__ . '/../app/lib/match_permissions.php';
          require_once __DIR__ . '/../app/lib/match_period_repository.php';

          $match = get_match($matchId);

          if (!$match) {
                    http_response_code(404);
                    echo '404 Not Found';
                    return true;
          }

          require __DIR__ . '/../app/views/pages/matches/periods.php';
          return true;
});

route('/api/matches/(\d+)/periods/preset', function ($matchId) {
          require __DIR__ . '/../app/api/matches/periods_preset.php';
          return true;
});

route('/api/matches/(\d+)/periods/custom', function ($matchId) {
          require __DIR__ . '/../app/api/matches/periods_custom.php';
          return true;
});

route('/matches/(\d+)/roster', function ($matchId) {
          require_auth();
          require_once __DIR__ . '/../app/lib/match_repository.php';
          require_once __DIR__ . '/../app/lib/match_permissions.php';
          require_once __DIR__ . '/../app/lib/match_player_repository.php';

          $match = get_match($matchId);

          if (!$match) {
                    http_response_code(404);
                    echo '404 Not Found';
                    return true;
          }

          require __DIR__ . '/../app/views/pages/matches/roster.php';
          return true;
});

route('/api/matches/(\d+)/roster/save', function ($matchId) {
          require __DIR__ . '/../app/api/matches/roster_save.php';
          return true;
});

route('/matches/(\d+)/edit', function ($matchId) {
          require_auth();
          require_once __DIR__ . '/../app/lib/match_repository.php';
          require_once __DIR__ . '/../app/lib/match_permissions.php';

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
});

route('/matches/(\d+)/video', function ($matchId) {
          require_auth();
          require_once __DIR__ . '/../app/lib/match_repository.php';
          require_once __DIR__ . '/../app/lib/match_permissions.php';

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
});

route('/api/matches/(\d+)/update-details', function ($matchId) {
          require __DIR__ . '/../app/api/matches/update-details.php';
          return true;
});

route('/api/matches/(\d+)/update-video', function ($matchId) {
          require __DIR__ . '/../app/api/matches/update-video.php';
          return true;
});

route('/api/matches/(\d+)/lock/acquire', function ($matchId) {
          require __DIR__ . '/../app/api/matches/lock_acquire.php';
          return true;
});

route('/api/matches/(\d+)/lock/heartbeat', function ($matchId) {
          require __DIR__ . '/../app/api/matches/lock_heartbeat.php';
          return true;
});

route('/api/matches/(\d+)/lock/release', function ($matchId) {
          require __DIR__ . '/../app/api/matches/lock_release.php';
          return true;
});

route('/api/matches/(\d+)/session', function ($matchId) {
          require __DIR__ . '/../app/api/matches/session.php';
          return true;
});

route('/api/matches/(\d+)/events', function ($matchId) {
          require __DIR__ . '/../app/api/events/list.php';
          return true;
});

route('/api/matches/(\d+)/events/create', function ($matchId) {
          require __DIR__ . '/../app/api/events/create.php';
          return true;
});

route('/api/matches/(\d+)/events/update', function ($matchId) {
          require __DIR__ . '/../app/api/events/update.php';
          return true;
});

route('/api/matches/(\d+)/events/delete', function ($matchId) {
          require __DIR__ . '/../app/api/events/delete.php';
          return true;
});

route('/api/matches/(\d+)/annotations/create', function ($matchId) {
          require __DIR__ . '/../app/api/matches/annotations/create.php';
          return true;
});

route('/api/matches/(\d+)/annotations/update', function ($matchId) {
          require __DIR__ . '/../app/api/matches/annotations/update.php';
          return true;
});

route('/api/matches/(\d+)/annotations/delete', function ($matchId) {
          require __DIR__ . '/../app/api/matches/annotations/delete.php';
          return true;
});

route('/api/matches/(\d+)/annotations', function ($matchId) {
          require __DIR__ . '/../app/api/matches/annotations/list.php';
          return true;
});

route('/api/matches/(\d+)/periods/start', function ($matchId) {
          require __DIR__ . '/../app/api/matches/periods_start.php';
          return true;
});

route('/api/matches/(\d+)/periods/end', function ($matchId) {
          require __DIR__ . '/../app/api/matches/periods_end.php';
          return true;
});

route('/api/matches/(\d+)/periods', function ($matchId) {
          require __DIR__ . '/../app/api/matches/periods_list.php';
          return true;
});

route('/api/matches/(\d+)/share', function ($matchId) {
          require __DIR__ . '/../app/api/matches/share.php';
          return true;
});

route('/api/match-periods/set', function () {
          require __DIR__ . '/../app/api/match-periods/set.php';
          return true;
});

route('/api/matches/(\d+)/video/veo/start', function ($matchId) {
          require __DIR__ . '/../app/api/matches/video_veo.php';
          return true;
});

route('/api/matches/(\d+)/video/veo/cancel', function ($matchId) {
          require __DIR__ . '/../app/api/matches/video_veo_cancel.php';
          return true;
});

route('/api/matches/(\d+)/video/veo', function ($matchId) {
          require __DIR__ . '/../app/api/matches/video_veo.php';
          return true;
});

route('/api/matches/(\d+)/video_status', function ($matchId) {
          require __DIR__ . '/../app/api/matches/video_status.php';
          return true;
});

route('/api/matches/(\d+)/video/status', function ($matchId) {
          require __DIR__ . '/../app/api/matches/video_status.php';
          return true;
});

route('/api/matches/(\d+)/video/progress', function ($matchId) {
          require __DIR__ . '/../app/api/matches/video_status.php';
          return true;
});

route('/api/matches/(\d+)/video-status', function ($matchId) {
          require __DIR__ . '/../app/api/matches/video_status.php';
          return true;
});

route('/api/matches/(\d+)/clips/create', function ($matchId) {
          require __DIR__ . '/../app/api/matches/clips_create.php';
          return true;
});

route('/api/matches/(\d+)/clips/delete', function ($matchId) {
          require __DIR__ . '/../app/api/matches/clips_delete.php';
          return true;
});

route('/api/clips/(\d+)/regenerate', function ($clipId) {
          require __DIR__ . '/../app/api/clips/regenerate.php';
          return true;
});

route('/api/matches/(\d+)/playlists/(\d+)', function ($matchId, $playlistId) {
          require __DIR__ . '/../app/api/matches/playlists/show.php';
          return true;
});

route('/api/matches/(\d+)/playlists', function ($matchId) {
          require __DIR__ . '/../app/api/matches/playlists/list.php';
          return true;
});

route('/api/matches/(\d+)/playlists/download', function ($matchId) {
          require __DIR__ . '/../app/api/matches/playlists/download.php';
          return true;
});

route('/api/matches/(\d+)/playlists/create', function ($matchId) {
          require __DIR__ . '/../app/api/matches/playlists/create.php';
          return true;
});

route('/api/matches/(\d+)/playlists/rename', function ($matchId) {
          require __DIR__ . '/../app/api/matches/playlists/rename.php';
          return true;
});

route('/api/matches/(\d+)/playlists/notes', function ($matchId) {
          require __DIR__ . '/../app/api/matches/playlists/notes.php';
          return true;
});

route('/api/stats/match/report_pdf', function () {
    require __DIR__ . '/../app/api/stats/match/report_pdf.php';
    return true;
});
route('/api/matches/(\d+)/playlists/delete', function ($matchId) {
          require __DIR__ . '/../app/api/matches/playlists/delete.php';
          return true;
});

route('/api/matches/(\d+)/playlists/clips/add', function ($matchId) {
          require __DIR__ . '/../app/api/matches/playlists/clips_add.php';
          return true;
});

route('/api/matches/(\d+)/playlists/clips/remove', function ($matchId) {
          require __DIR__ . '/../app/api/matches/playlists/clips_remove.php';
          return true;
});

route('/api/matches/(\d+)/playlists/clips/reorder', function ($matchId) {
          require __DIR__ . '/../app/api/matches/playlists/clips_reorder.php';
          return true;
});

route('/api/matches/(\d+)/edit', function ($matchId) {
          require __DIR__ . '/../app/api/matches/update.php';
          return true;
});

route('/api/matches/(\d+)/delete', function ($matchId) {
          require __DIR__ . '/../app/api/matches/delete.php';
          return true;
});

route('/matches/(\d+)/repair-lineups', function ($matchId) {
    require_auth();
    require_once __DIR__ . '/../app/lib/match_repository.php';
    require_once __DIR__ . '/../app/lib/match_permissions.php';

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
    // Pass match_id as GET param for the view
    $_GET['match_id'] = $matchId;
    require __DIR__ . '/../app/views/pages/matches/repair-lineups.php';
    return true;
});

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

dispatch();
