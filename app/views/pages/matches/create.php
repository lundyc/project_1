
<?php
require_auth();
require_once __DIR__ . '/../../../lib/match_permissions.php';
require_once __DIR__ . '/../../../lib/team_repository.php';
require_once __DIR__ . '/../../../lib/season_repository.php';
require_once __DIR__ . '/../../../lib/competition_repository.php';
require_once __DIR__ . '/../../../lib/club_repository.php';
require_once __DIR__ . '/../../../lib/player_repository.php';
require_once __DIR__ . '/../../../lib/csrf.php';

$user = current_user();
$roles = $_SESSION['roles'] ?? [];
$isPlatformAdmin = in_array('platform_admin', $roles, true);
$base = base_path();

$clubs = $isPlatformAdmin ? get_all_clubs() : [];
$selectedClubId = $isPlatformAdmin
    ? (isset($_GET['club_id']) && $_GET['club_id'] !== '' ? (int)$_GET['club_id'] : (int)($clubs[0]['id'] ?? ($user['club_id'] ?? 0)))
    : (int)($user['club_id'] ?? 0);

if (!$selectedClubId) {
    http_response_code(400);
    echo 'Club context required';
    exit;
}

$teams = get_teams_by_club($selectedClubId);
$seasons = get_seasons_by_club($selectedClubId);
$competitions = get_competitions_by_club($selectedClubId);
$clubPlayers = get_players_for_club($selectedClubId);

// Defaults for create
$matchId = null;
$nextMatchId = null;
$match = [];
$homeTeamName = '';
$awayTeamName = '';
$matchSeasonId = null;
$matchCompetitionId = null;
$matchHomeId = 0;
$matchAwayId = 0;
$matchVenue = '';
$matchReferee = '';
$matchAttendance = null;
$matchStatus = 'draft';
$kickoffValue = '';
$videoPath = '';
$videoUrl = '';
$downloadStatus = '';
$downloadProgress = 0;
$videoType = 'veo';
$videoFiles = [];
$videoDir = realpath(dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'videos' . DIRECTORY_SEPARATOR . 'raw');
$allowedVideoExt = ['mp4', 'webm', 'mov'];
if ($videoDir && is_dir($videoDir)) {
    $items = scandir($videoDir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $full = $videoDir . DIRECTORY_SEPARATOR . $item;
        if (!is_file($full)) continue;
        $real = realpath($full);
        if (!$real || !str_starts_with($real, $videoDir)) continue;
        $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedVideoExt, true)) continue;
        $videoFiles[] = [
            'filename' => $item,
            'web_path' => '/videos/raw/' . $item,
        ];
    }
}
$hasCurrentVideo = false;
$currentVideoLabel = 'None';
$matchPlayers = [];
$allEvents = [];
$goals = [];
$yellowCards = [];
$redCards = [];
$cards = [];
$substitutions = [];
$error = $_SESSION['match_form_error'] ?? null;
$success = $_SESSION['match_form_success'] ?? null;
unset($_SESSION['match_form_error']);
unset($_SESSION['match_form_success']);
$setupConfig = [
    'basePath' => $base,
    'clubId' => $selectedClubId,
    'csrfToken' => get_csrf_token(),
    'matchPlayers' => [],
    'endpoints' => [
        'teamCreate' => $base . '/api/teams/create-json',
        'seasonCreate' => $base . '/api/seasons/create',
        'competitionCreate' => $base . '/api/competitions/create',
        'playerSearch' => $base . '/api/players/search',
        'playersCreate' => $base . '/api/players/create',
    ],
];
$footerScripts = '<script>window.MatchEditConfig = ' . json_encode($setupConfig) . ';</script>';
$footerScripts .= '<script src="' . htmlspecialchars($base) . '/assets/js/match-edit.js?v=' . time() . '"></script>';
$headExtras = '<style>';
$headExtras .= file_get_contents(__DIR__ . '/edit.php', false, null, strpos(file_get_contents(__DIR__ . '/edit.php'), '<style>'), strpos(file_get_contents(__DIR__ . '/edit.php'), '</style>') - strpos(file_get_contents(__DIR__ . '/edit.php'), '<style>') + 8);
$headExtras .= '</style>';
ob_start();
?>
<?php
    $matchTitle = 'Create Match';
    $matchDescription = 'Create a new match.';
    $clubContextName = $selectedClub['name'] ?? 'Saltcoats Victoria F.C.';
    $showClubSelector = true;
    include __DIR__ . '/../../partials/match_context_header.php';
?>
<div class="flex items-center justify-end gap-2 mb-6">
    <a href="<?= htmlspecialchars($base) ?>/matches" class="inline-flex items-center rounded-md bg-slate-700/60 px-2 py-1 text-xs text-slate-200 hover:bg-slate-700/80 transition" aria-label="Back to matches">
        ← Back to matches
    </a>
</div>
<div class="px-4 md:px-6 lg:px-8">
    <?php if ($error): ?>
        <div id="alert-error" class="fixed top-6 right-6 max-w-md z-50 rounded-lg border border-rose-500 bg-rose-950/95 px-4 py-3 text-sm text-rose-100 shadow-lg backdrop-blur-sm animate-slide-in">
            <div class="flex items-start gap-3">
                <i class="fa-solid fa-circle-exclamation text-rose-400 mt-0.5 flex-shrink-0"></i>
                <div class="flex-1">
                    <p class="font-semibold text-rose-200">Error</p>
                    <p class="text-rose-100 mt-1"><?= htmlspecialchars($error) ?></p>
                </div>
                <button type="button" class="text-rose-400 hover:text-rose-300 flex-shrink-0" onclick="this.parentElement.parentElement.remove();">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>
    <?php elseif ($success): ?>
        <div id="alert-success" class="fixed top-6 right-6 max-w-md z-50 rounded-lg border border-emerald-500 bg-emerald-950/95 px-4 py-3 text-sm text-emerald-100 shadow-lg backdrop-blur-sm animate-slide-in">
            <div class="flex items-start gap-3">
                <i class="fa-solid fa-circle-check text-emerald-400 mt-0.5 flex-shrink-0"></i>
                <div class="flex-1">
                    <p class="font-semibold text-emerald-200">Success</p>
                    <p class="text-emerald-100 mt-1"><?= htmlspecialchars($success) ?></p>
                </div>
                <button type="button" class="text-emerald-400 hover:text-emerald-300 flex-shrink-0" onclick="this.parentElement.parentElement.remove();">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php
// Copy the main content from edit.php, but adapt all $match and edit-specific logic to blank/defaults for create
// The rest of the HTML structure, sections, and forms should be copied from edit.php, replacing edit-specific logic with create logic as needed.
// For brevity, you may want to extract shared sections into partials for maintainability.
include __DIR__ . '/edit.php';
$content = ob_get_clean();
include __DIR__ . '/../../layout.php';
