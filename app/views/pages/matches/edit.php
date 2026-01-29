<?php
require_auth();
require_once __DIR__ . '/../../../lib/match_permissions.php';
require_once __DIR__ . '/../../../lib/team_repository.php';
require_once __DIR__ . '/../../../lib/season_repository.php';
require_once __DIR__ . '/../../../lib/competition_repository.php';
require_once __DIR__ . '/../../../lib/club_repository.php';
require_once __DIR__ . '/../../../lib/match_player_repository.php';
require_once __DIR__ . '/../../../lib/player_repository.php';
require_once __DIR__ . '/../../../lib/event_repository.php';
require_once __DIR__ . '/../../../lib/match_substitution_repository.php';
require_once __DIR__ . '/../../../lib/csrf.php';
require_once __DIR__ . '/../../../lib/asset_helper.php';

$user = current_user();
$roles = $_SESSION['roles'] ?? [];
$isPlatformAdmin = in_array('platform_admin', $roles, true);
$base = base_path();

if (!isset($match) || !is_array($match)) {
    http_response_code(404);
    echo '404 Not Found';
    exit;
}

$matchId = (int)$match['id'];
$nextMatchId = $matchId + 1;
$selectedClubId = (int)$match['club_id'];

$clubTeams = get_teams_by_club($selectedClubId);
$opponentTeams = get_teams_not_in_club($selectedClubId);
$seasons = get_seasons_by_club($selectedClubId);
$competitions = get_competitions_by_club($selectedClubId);
$clubPlayers = get_players_for_club($selectedClubId);

// Get match players for lineups
$matchPlayers = get_match_players($matchId);
$homeStarters = array_filter($matchPlayers, fn($p) => $p['team_side'] === 'home' && $p['is_starting']);
$homeSubs = array_filter($matchPlayers, fn($p) => $p['team_side'] === 'home' && !$p['is_starting']);
$awayStarters = array_filter($matchPlayers, fn($p) => $p['team_side'] === 'away' && $p['is_starting']);
$awaySubs = array_filter($matchPlayers, fn($p) => $p['team_side'] === 'away' && !$p['is_starting']);

// Get events for match
$allEvents = event_list_for_match($matchId);
$goals = array_filter($allEvents, fn($e) => ($e['event_type_key'] ?? '') === 'goal');
$yellowCards = array_filter($allEvents, fn($e) => ($e['event_type_key'] ?? '') === 'yellow_card');
$redCards = array_filter($allEvents, fn($e) => ($e['event_type_key'] ?? '') === 'red_card');
$cards = array_merge($yellowCards, $redCards);
usort($cards, fn($a, $b) => ($a['minute'] ?? 0) <=> ($b['minute'] ?? 0));

// Get substitutions
$substitutions = get_match_substitutions($matchId);

// Calculate score
$homeGoals = count(array_filter($goals, fn($g) => $g['team_side'] === 'home'));
$awayGoals = count(array_filter($goals, fn($g) => $g['team_side'] === 'away'));

$error = $_SESSION['match_form_error'] ?? null;
$success = $_SESSION['match_form_success'] ?? null;
unset($_SESSION['match_form_error']);
unset($_SESSION['match_form_success']);

$title = 'Edit Match';
$kickoffValue = !empty($match['kickoff_at']) ? date('Y-m-d\TH:i', strtotime($match['kickoff_at'])) : '';
$matchSeasonId = $match['season_id'] ?? null;
$matchCompetitionId = $match['competition_id'] ?? null;
$matchHomeId = (int)($match['home_team_id'] ?? 0);
$matchAwayId = (int)($match['away_team_id'] ?? 0);
$matchVenue = (string)($match['venue'] ?? '');
$matchReferee = (string)($match['referee'] ?? '');
$matchAttendance = $match['attendance'] ?? null;
$matchStatus = $match['status'] ?? 'draft';

$homeTeamRow = get_team_by_id($matchHomeId);
$awayTeamRow = get_team_by_id($matchAwayId);
$homeTeamName = $homeTeamRow['name'] ?? '';
$awayTeamName = $awayTeamRow['name'] ?? '';

$clubTeamIds = array_map(static function ($team) {
    return (int)$team['id'];
}, $clubTeams);

$clubTeamId = 0;
$opponentTeamId = 0;
$clubSide = 'home';
if (in_array($matchHomeId, $clubTeamIds, true)) {
    $clubTeamId = $matchHomeId;
    $opponentTeamId = $matchAwayId;
    $clubSide = 'home';
} elseif (in_array($matchAwayId, $clubTeamIds, true)) {
    $clubTeamId = $matchAwayId;
    $opponentTeamId = $matchHomeId;
    $clubSide = 'away';
} else {
    $clubTeamId = $clubTeamIds[0] ?? 0;
    $opponentTeamId = $matchHomeId ?: $matchAwayId;
    $clubSide = 'home';
}

// Video source info
$videoPath = $match['video_source_path'] ?? '';
$videoUrl = $match['video_source_url'] ?? '';
$downloadStatus = $match['video_download_status'] ?? '';
$downloadProgress = (int)($match['video_download_progress'] ?? 0);

// Default to 'none' if no video file exists, otherwise use the stored value
$videoType = $match['video_source_type'] ?? 'upload';
if (empty($videoPath) && $videoType === 'upload') {
    $videoType = 'none';
}

// Raw video files (reuse create-match behavior)
$videoFiles = [];
$videoDir = realpath(dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'videos' . DIRECTORY_SEPARATOR . 'raw');
$allowedVideoExt = ['mp4', 'webm', 'mov'];

if ($videoDir && is_dir($videoDir)) {
    $items = scandir($videoDir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $full = $videoDir . DIRECTORY_SEPARATOR . $item;
        if (!is_file($full)) {
            continue;
        }
        $real = realpath($full);
        if (!$real || !str_starts_with($real, $videoDir)) {
            continue;
        }
        $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedVideoExt, true)) {
            continue;
        }
        $videoFiles[] = [
            'filename' => $item,
            'web_path' => '/videos/raw/' . $item,
        ];
    }
}
$hasCurrentVideo = $videoPath && !empty(array_filter($videoFiles, fn($f) => $f['web_path'] === $videoPath));
$currentVideoLabel = $videoPath ? basename($videoPath) : ($videoUrl ?: 'None');

$setupConfig = [
    'basePath' => $base,
    'clubId' => $selectedClubId,
    'matchId' => $matchId,
    'homeTeamId' => $matchHomeId,
    'awayTeamId' => $matchAwayId,
    'csrfToken' => get_csrf_token(),
    'matchPlayers' => array_map(function($mp) {
        return [
            'id' => (int)$mp['id'],
            'player_id' => (int)($mp['player_id'] ?? 0),
            'player_name' => $mp['display_name'] ?? '',
            'full_name' => trim(($mp['first_name'] ?? '') . ' ' . ($mp['last_name'] ?? '')),
            'team_side' => $mp['team_side'],
            'is_starting' => (bool)$mp['is_starting'],
            'shirt_number' => $mp['shirt_number'] ?? '',
            'position_label' => $mp['position_label'] ?? '',
        ];
    }, $matchPlayers),
    'endpoints' => [
        'teamCreate' => $base . '/api/teams/create-json',
        'seasonCreate' => $base . '/api/seasons/create',
        'competitionCreate' => $base . '/api/competitions/create',
        'matchPlayersList' => $base . '/api/match-players?match_id=' . $matchId,
        'matchPlayersAdd' => $base . '/api/match-players/add',
        'matchPlayersUpdate' => $base . '/api/match-players/update',
        'matchPlayersDelete' => $base . '/api/match-players/delete',
        'playerSearch' => $base . '/api/players/search',
        'playersCreate' => $base . '/api/players/create',
    ],
];

$footerScripts = '<script>window.MatchEditConfig = ' . json_encode($setupConfig) . ';</script>';
// Filemtime-based versioning enables long-lived caching between updates.
$footerScripts .= '<script src="' . htmlspecialchars($base) . '/assets/js/match-edit.js' . asset_version('/assets/js/match-edit.js') . '"></script>';
$footerScripts .= '<script>
document.addEventListener("DOMContentLoaded", function () {
    const clubSideInput = document.getElementById("clubSide");
    const buttons = document.querySelectorAll(".club-side-btn");
    if (!clubSideInput || buttons.length === 0) return;

    const baseClasses = ["bg-slate-800", "border-slate-700", "text-slate-200"];
    const activeClasses = ["bg-blue-600", "border-blue-500", "text-white"];

    const setActive = (value) => {
        clubSideInput.value = value;
        buttons.forEach(btn => {
            const isActive = btn.getAttribute("data-club-side") === value;
            baseClasses.forEach(cls => btn.classList.toggle(cls, !isActive));
            activeClasses.forEach(cls => btn.classList.toggle(cls, isActive));
            btn.setAttribute("aria-pressed", isActive ? "true" : "false");
        });
    };

    buttons.forEach(btn => {
        btn.addEventListener("click", () => {
            const value = btn.getAttribute("data-club-side") || "home";
            setActive(value);
        });
    });

    setActive(clubSideInput.value || "home");
});
</script>';

// Remove padding and background from layout wrapper for full-width page
$headExtras = '<style>
    /* Alert Animations */
    @keyframes slideInDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes slideOutUp {
        from {
            opacity: 1;
            transform: translateY(0);
        }
        to {
            opacity: 0;
            transform: translateY(-20px);
        }
    }
    
    .animate-slide-in {
        animation: slideInDown 0.3s ease-out;
    }
    
    .animate-slide-out {
        animation: slideOutUp 0.3s ease-in forwards;
    }
    
    /* Form Input States */
    input:invalid,
    select:invalid,
    textarea:invalid {
        border-color: var(--border-danger) !important;
    }
    
    input:invalid:focus,
    select:invalid:focus,
    textarea:invalid:focus {
        border-color: var(--border-danger) !important;
        ring: 2px var(--border-danger) !important;
    }   
  
    /* Sticky Button Container */
    .sticky-button-container {
        box-shadow: 0 -2px 15px var(--shadow-strong);
    }
    
    /* Tab Indicator */
    .edit-nav-item::after {
        content: "";
        position: absolute;
        left: 0;
        bottom: 0;
        height: 3px;
        width: 100%;
        background-color: transparent;
        transition: background-color 0.2s;
    }
    
    .edit-nav-item.active::after {
     /*   background-color: var(--accent-info);*/
    }
    
    /* Focus Ring Enhancement */
    button:focus-visible,
    input:focus-visible,
    select:focus-visible,
    textarea:focus-visible {
        outline: 2px solid var(--accent-info);
        outline-offset: 2px;
    }
</style>';

ob_start();
?>

        <?php
        // Set header/title/description for Edit Match only
        $matchTitle = 'Edit Match';
        $homeTeamName = isset($homeTeamName) ? $homeTeamName : ($match['home_team'] ?? 'Home');
        $awayTeamName = isset($awayTeamName) ? $awayTeamName : ($match['away_team'] ?? 'Away');
        $matchDescription = 'Manual entry for ' . htmlspecialchars($homeTeamName) . ' vs ' . htmlspecialchars($awayTeamName) . '.';
        $clubContextName = $selectedClub['name'] ?? 'Saltcoats Victoria F.C.';
        $showClubSelector = true;
        include __DIR__ . '/../../partials/match_context_header.php';
        ?>
                    <div class="flex items-center justify-end gap-2 mb-0">
            <a href="<?= htmlspecialchars($base) ?>/matches" class="inline-flex items-center rounded-md bg-slate-700/60 px-2 py-1 text-xs text-slate-200 hover:bg-slate-700/80 transition" aria-label="Back to matches">
                ← Back to matches
            </a>
            <?php if (isset($nextMatchId) && $nextMatchId): ?>
            <a href="<?= htmlspecialchars($base) ?>/matches/<?= $nextMatchId ?>/edit" class="inline-flex items-center rounded-md bg-indigo-700/60 px-2 py-1 text-xs text-white hover:bg-indigo-700 transition" aria-label="Next Match">
                Next Match <span aria-hidden="true">→</span>
            </a>
            <?php endif; ?>
            <?php if (isset($matchStatus) && $matchStatus === 'ready'): ?>
                <a href="/matches/<?= urlencode($matchId) ?>/repair-lineups" class="inline-flex items-center rounded-md bg-green-700/80 px-2 py-1 text-xs text-white hover:bg-green-800 transition shadow" aria-label="Repair Starting Lineups">
                    <i class="fa-solid fa-wrench mr-1"></i> Repair Lineups
                </a>
            <?php elseif (isset($matchStatus)): ?>
                <span class="inline-flex items-center rounded-md bg-slate-700/60 px-2 py-1 text-xs text-slate-200" aria-label="Match Status">
                    Status: <?= htmlspecialchars(ucfirst($matchStatus)) ?>
                </span>
            <?php endif; ?>
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

            <div class="flex gap-6 px-4 md:px-6 w-full">
                <!-- Left Sidebar -->
                <aside class="w-48 flex-shrink-0 space-y-4">
                    <!-- Progress Indicator -->
                    <div class="mb-4 p-3 bg-slate-800/40 rounded-lg border border-slate-700">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-medium text-slate-400">Section Progress</span>
                            <span class="text-xs text-slate-400" id="section-progress-text">1 of 4</span>
                        </div>
                        <div class="w-full h-1.5 bg-slate-700 rounded-full overflow-hidden">
                            <div id="section-progress-bar" class="h-full w-1/4 bg-blue-500 transition-all duration-300"></div>
                        </div>
                        <p class="text-xs text-slate-400 mt-2" id="section-name">Match Details</p>
                        <?php if ($homeTeamName || $awayTeamName): ?>
                            <p class="text-xs text-slate-300 mt-2 font-medium"><?= htmlspecialchars($homeTeamName) ?> vs <?= htmlspecialchars($awayTeamName) ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <nav class="flex flex-col gap-2 mb-3" role="tablist" aria-label="Match edit sections">
                        <button type="button" class="edit-nav-item active w-full text-left" data-section="details" data-section-num="1"
                                role="tab" aria-selected="true" aria-controls="section-details">
                            Match Details
                        </button>
                        <button type="button" class="edit-nav-item w-full text-left" data-section="video" data-section-num="2"
                                role="tab" aria-selected="false" aria-controls="section-video">
                            Video Source
                        </button>
                        <button type="button" class="edit-nav-item w-full text-left" data-section="lineups" data-section-num="3"
                                role="tab" aria-selected="false" aria-controls="section-lineups">
                            Player Lineups
                        </button>
                        <button type="button" class="edit-nav-item w-full text-left" data-section="events" data-section-num="4"
                                role="tab" aria-selected="false" aria-controls="section-events">
                            Match Events
                        </button>
                    </nav>
                </aside>

                <!-- Main Content -->
                <main class="flex-1 min-w-0 space-y-4 w-100">
                <!-- Section 1: Match Details -->
                <section id="section-details" class="edit-section active">
                    <div class="rounded-xl bg-slate-900/50 border border-slate-800 overflow-hidden">
                        <div class="border-b border-slate-800 px-6 py-4">
                            <h2 class="text-lg font-semibold text-white">Match Details</h2>
                            <p class="text-sm text-slate-400 mt-1">Basic match information and competition details</p>
                        </div>
                        <div class="p-6">
                            <form id="match-details-form" method="post" action="<?= htmlspecialchars($base) ?>/api/matches/<?= $matchId ?>/update-details" class="space-y-6">
                                <input type="hidden" name="club_id" value="<?= htmlspecialchars($selectedClubId) ?>">
                                <input type="hidden" name="match_id" value="<?= $matchId ?>">
                                
                                <!-- Teams -->
                                <!-- Teams Section -->
                                <div class="space-y-4 border-l-4 border-blue-500 pl-4 py-3 rounded-r bg-blue-500/5">
                                    <h3 class="text-sm font-semibold text-blue-400 uppercase tracking-wider flex items-center gap-2">
                                        <i class="fa-solid fa-users"></i> Teams
                                    </h3>
                                    <div class="grid gap-4 grid-cols-1 md:grid-cols-2">
                                        <div>
                                            <label class="block text-sm font-medium text-slate-300 mb-2" for="clubTeam">
                                                Your Club <span class="text-rose-400">*</span>
                                            </label>
                                            <select id="clubTeam" name="club_team_id" required class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2.5 text-sm text-white focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/40">
                                                <option value="">Select your team</option>
                                                <?php foreach ($clubTeams as $team): ?>
                                                    <option value="<?= (int)$team['id'] ?>" <?= $clubTeamId == $team['id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($team['name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-slate-300 mb-2" for="opponentTeam">
                                                Opponents <span class="text-rose-400">*</span>
                                            </label>
                                            <select id="opponentTeam" name="opponent_team_id" required class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2.5 text-sm text-white focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/40">
                                                <option value="">Select opponent</option>
                                                <?php foreach ($opponentTeams as $team): ?>
                                                    <option value="<?= (int)$team['id'] ?>" <?= $opponentTeamId == $team['id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($team['name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mt-4">
                                        <label class="block text-sm font-medium text-slate-300 mb-2">Home/Away</label>
                                        <div class="flex flex-wrap gap-2">
                                            <button type="button" class="club-side-btn flex-1 rounded-lg border border-slate-700 bg-slate-800 px-4 py-2 text-sm font-medium text-slate-200 transition hover:bg-slate-700<?= $clubSide === 'home' ? ' bg-blue-600 border-blue-500 text-white' : '' ?>" data-club-side="home" aria-pressed="<?= $clubSide === 'home' ? 'true' : 'false' ?>">
                                                <i class="fa-solid fa-house mr-2"></i>Your Club at Home
                                            </button>
                                            <button type="button" class="club-side-btn flex-1 rounded-lg border border-slate-700 bg-slate-800 px-4 py-2 text-sm font-medium text-slate-200 transition hover:bg-slate-700<?= $clubSide === 'away' ? ' bg-blue-600 border-blue-500 text-white' : '' ?>" data-club-side="away" aria-pressed="<?= $clubSide === 'away' ? 'true' : 'false' ?>">
                                                <i class="fa-solid fa-arrow-right mr-2"></i>Your Club Away
                                            </button>
                                        </div>
                                        <input type="hidden" name="club_side" id="clubSide" value="<?= htmlspecialchars($clubSide) ?>">
                                    </div>
                                </div>

                                <!-- Competition & Season Section -->
                                <div class="space-y-4 border-l-4 border-emerald-500 pl-4 py-3 rounded-r bg-emerald-500/5">
                                    <h3 class="text-sm font-semibold text-emerald-400 uppercase tracking-wider flex items-center gap-2">
                                        <i class="fa-solid fa-trophy"></i> Competition
                                    </h3>
                                    <div class="grid gap-4 grid-cols-1 md:grid-cols-2">
                                        <div>
                                            <label class="block text-sm font-medium text-slate-300 mb-2" for="season">
                                                Season
                                            </label>
                                            <select id="season" name="season_id" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2.5 text-sm text-white focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/40">
                                                <option value="">Select season</option>
                                                <?php foreach ($seasons as $season): ?>
                                                    <option value="<?= (int)$season['id'] ?>" <?= $matchSeasonId == $season['id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($season['name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-slate-300 mb-2" for="competition">
                                                Competition
                                            </label>
                                            <select id="competition" name="competition_id" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2.5 text-sm text-white focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/40">
                                                <option value="">Select competition</option>
                                                <?php foreach ($competitions as $comp): ?>
                                                    <option value="<?= (int)$comp['id'] ?>" <?= $matchCompetitionId == $comp['id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($comp['name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Match Information Section -->
                                <div class="space-y-4 border-l-4 border-purple-500 pl-4 py-3 rounded-r bg-purple-500/5">
                                    <h3 class="text-sm font-semibold text-purple-400 uppercase tracking-wider flex items-center gap-2">
                                        <i class="fa-solid fa-calendar-days"></i> Match Information
                                    </h3>
                                    <div class="grid gap-4 grid-cols-1 md:grid-cols-2">
                                        <div>
                                            <label class="block text-sm font-medium text-slate-300 mb-2" for="kickoff">
                                                Kickoff Date & Time
                                            </label>
                                            <input type="datetime-local" id="kickoff" name="kickoff_at" value="<?= htmlspecialchars($kickoffValue) ?>" 
                                                   class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2.5 text-sm text-white focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/40">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-slate-300 mb-2" for="venue">
                                                Venue
                                            </label>
                                            <input type="text" id="venue" name="venue" value="<?= htmlspecialchars($matchVenue) ?>" 
                                                   placeholder="Stadium name" 
                                                   class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2.5 text-sm text-white placeholder-slate-500 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/40">
                                        </div>
                                    </div>
                                    <div class="grid gap-4 grid-cols-1 md:grid-cols-2">
                                        <div>
                                            <label class="block text-sm font-medium text-slate-300 mb-2" for="referee">
                                                Referee
                                            </label>
                                            <input type="text" id="referee" name="referee" value="<?= htmlspecialchars($matchReferee) ?>" 
                                                   placeholder="Referee name" 
                                                   class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2.5 text-sm text-white placeholder-slate-500 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/40">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-slate-300 mb-2" for="attendance">
                                                Attendance
                                            </label>
                                            <input type="number" id="attendance" name="attendance" value="<?= htmlspecialchars((string)$matchAttendance) ?>" 
                                                   placeholder="0" min="0" max="1000000"
                                                   aria-describedby="attendance-help"
                                                   class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2.5 text-sm text-white placeholder-slate-500 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/40">
                                            <p id="attendance-help" class="text-xs text-slate-500 mt-1">Optional. Enter number of spectators.</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Status -->
                                <div class="space-y-4">
                                    <h3 class="text-sm font-semibold text-slate-300 uppercase tracking-wider">Status</h3>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-300 mb-2" for="status">
                                            Match Status
                                        </label>
                                        <select id="status" name="status" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2.5 text-sm text-white focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/40">
                                            <option value="draft" <?= $matchStatus === 'draft' ? 'selected' : '' ?>>Draft</option>
                                            <option value="ready" <?= $matchStatus === 'ready' ? 'selected' : '' ?>>Ready</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="flex justify-end pt-4 border-t border-slate-800">
                                    <!-- Save button moved to sticky footer -->
                                </div>
                            </form>
                        </div>
                    </div>
                </section>

                <!-- Section 2: Video Source -->
                <section id="section-video" class="edit-section" style="display:none;">
                    <div class="rounded-xl bg-slate-900/50 border border-slate-800 overflow-hidden">
                        <div class="border-b border-slate-800 px-6 py-4">
                            <h2 class="text-lg font-semibold text-white">Video Source</h2>
                            <p class="text-sm text-slate-400 mt-1">Choose how this match video is sourced. Existing video is kept unless you pick a new file or URL.</p>
                        </div>
                        <div class="p-6 space-y-6">
                            <div class="rounded-lg border border-slate-800 bg-slate-900/60 p-4 flex items-start gap-3">
                                <div class="text-blue-400 text-lg">
                                    <i class="fa-solid fa-circle-info"></i>
                                </div>
                                <div class="text-sm text-slate-300 leading-relaxed">
                                    <div class="font-semibold text-white">Current video</div>
                                    <div class="text-slate-200"><?= htmlspecialchars($currentVideoLabel) ?></div>
                                    <div class="text-xs text-slate-400 mt-1">We will only change or download video if you select a new file or enter a new URL.</div>
                                </div>
                            </div>

                            <div class="grid md:grid-cols-3 gap-4">
                                <label class="flex items-start gap-3 border border-slate-800 rounded-lg p-4 cursor-pointer hover:border-slate-700 transition">
                                    <input type="radio" name="video_source_type" id="videoTypeNone" value="none" class="mt-1" form="match-details-form" <?= $videoType === 'none' ? 'checked' : '' ?>>
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <span class="text-white font-semibold">No Video</span>
                                            <?php if ($videoType === 'none'): ?>
                                                <span class="text-xs text-emerald-400">Currently selected</span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="text-sm text-slate-400">Historical match with no video file.</p>
                                    </div>
                                </label>

                                <label class="flex items-start gap-3 border border-slate-800 rounded-lg p-4 cursor-pointer hover:border-slate-700 transition">
                                    <input type="radio" name="video_source_type" id="videoTypeUpload" value="upload" class="mt-1" form="match-details-form" <?= $videoType === 'upload' ? 'checked' : '' ?>>
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <span class="text-white font-semibold">Upload file</span>
                                            <?php if ($videoType === 'upload' && $videoPath): ?>
                                                <span class="text-xs text-emerald-400">Currently selected</span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="text-sm text-slate-400">Select a raw video already on the server or upload a new one.</p>
                                        <div id="video-upload-dropzone" class="mt-3" style="display:<?= $videoType === 'upload' ? 'block' : 'none' ?>;">
                                            <form id="videoUploadForm" enctype="multipart/form-data" method="post" action="<?= htmlspecialchars($base) ?>/api/videos/upload" style="border: 2px dashed #3b82f6; padding: 20px; border-radius: 8px; background: #1e293b; text-align: center;">
                                                <input type="file" name="video_file" id="videoFileInput" accept="video/mp4,video/webm,video/mov" style="display:none;">
                                                <label for="videoFileInput" class="cursor-pointer text-blue-400 hover:text-blue-300">
                                                    <i class="fa-solid fa-cloud-arrow-up text-2xl"></i>
                                                    <div class="mt-2">Drag & drop or click to select video</div>
                                                </label>
                                                <div id="videoUploadPreview" class="mt-2 text-slate-400"></div>
                                                <button type="button" id="uploadNowBtn" class="mt-4 px-4 py-2 rounded bg-blue-600 text-white font-semibold hover:bg-blue-700 transition">Upload Now</button>
                                                <div id="uploadProgressBar" class="w-full h-2 bg-slate-700 rounded-full overflow-hidden mt-3" style="display:none;">
                                                    <div id="uploadProgress" class="h-full bg-blue-500 transition-all duration-300" style="width:0%"></div>
                                                </div>
                                                <div id="uploadStatus" class="mt-2 text-xs text-slate-400"></div>
                                            </form>
                                        </div>
                                    </div>
                                </label>

                                <label class="flex items-start gap-3 border border-slate-800 rounded-lg p-4 cursor-pointer hover:border-slate-700 transition">
                                    <input type="radio" name="video_source_type" id="videoTypeVeo" value="veo" class="mt-1" form="match-details-form" <?= $videoType === 'veo' ? 'checked' : '' ?>>
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <span class="text-white font-semibold">VEO URL</span>
                                            <?php if ($videoType === 'veo' && $videoUrl): ?>
                                                <span class="text-xs text-emerald-400">Currently linked</span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="text-sm text-slate-400">Download from https://app.veo.co/matches/</p>
                                    </div>
                                </label>
                            </div>

                            <div class="grid md:grid-cols-2 gap-6" id="videoInputsSection" <?= $videoType === 'none' ? 'style="display:none;"' : '' ?>>

                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-slate-200">VEO match URL</label>
                                    <input type="text" name="video_source_url" id="video_url_input" form="match-details-form" value="<?= htmlspecialchars($videoType === 'veo' ? $videoUrl : '') ?>" placeholder="https://app.veo.co/matches/..." class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2.5 text-sm text-white focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/40" <?= $videoType === 'veo' ? '' : 'disabled' ?>>
                                                                        <button type="button" id="veoDownloadBtn" class="mt-2 px-4 py-2 rounded bg-emerald-600 text-white font-semibold hover:bg-emerald-700 transition" <?= $videoType === 'veo' ? '' : 'disabled' ?>>Download now</button>
                                                                        <div id="veoDownloadStatus" class="mt-2 text-xs text-slate-400"></div>
                                    <p class="text-xs text-slate-500">Leave blank to keep existing VEO link. A new link will start a fresh download.</p>
                                </div>
                            </div>

                            <div class="rounded-lg border border-slate-800 bg-slate-900/60 p-4 text-sm text-slate-300 space-y-2">
                                <div class="flex items-center gap-2 text-slate-200 font-semibold"><i class="fa-solid fa-shield"></i> Rules</div>
                                <ul class="list-disc list-inside space-y-1 text-slate-400">
                                    <li>Select <strong>No Video</strong> for historical matches without footage.</li>
                                    <li>If you don’t change anything, the existing video stays attached.</li>
                                    <li>Switching to VEO with a URL will queue a download.</li>
                                    <li>Switching to upload requires selecting a raw file.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Section 3: Player Lineups -->
                <section id="section-lineups" class="edit-section" style="display:none;">
                    <div class="rounded-xl bg-slate-900/50 border border-slate-800 overflow-hidden">
                        <div class="border-b border-slate-800 px-6 py-4">
                            <h2 class="text-lg font-semibold text-white">Player Lineups</h2>
                            <p class="text-sm text-slate-400 mt-1">Add starting XI and substitutes for both teams</p>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-2 gap-6">
                                <!-- Home Team -->
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between">
                                        <h3 class="text-base font-semibold text-white">
                                            <i class="fa-solid fa-house-chimney text-blue-400 mr-2"></i>
                                            <?= htmlspecialchars($homeTeamName) ?>
                                        </h3>
                                    </div>

                                    <!-- Starting XI -->
                                    <div class="space-y-3">
                                        <div class="flex items-center justify-between">
                                            <h4 class="text-sm font-medium text-slate-300 uppercase tracking-wider">Starting XI</h4>
                                            <button type="button" class="text-xs font-semibold text-blue-400 hover:text-blue-300 uppercase tracking-wider" data-add-player="home" data-is-starting="1">
                                                <i class="fa-solid fa-plus mr-1"></i> Add
                                            </button>
                                        </div>
                                        <div id="home-starters" class="space-y-2 min-h-[100px]">
                                            <?php if (empty($homeStarters)): ?>
                                                <div class="text-center py-6 text-slate-500 text-sm border-2 border-dashed border-slate-700 rounded-lg">
                                                    No starting players added yet
                                                </div>
                                            <?php else: ?>
                                                <?php 
                                                    // Sort by shirt number (1-11 first), then by name
                                                    $sortedStarters = $homeStarters;
                                                    usort($sortedStarters, function($a, $b) {
                                                        $aShirt = (int)($a['shirt_number'] ?? 0);
                                                        $bShirt = (int)($b['shirt_number'] ?? 0);
                                                        
                                                        // Players with shirt numbers (1-11) come first, sorted by number
                                                        if ($aShirt > 0 && $bShirt > 0) {
                                                            return $aShirt <=> $bShirt;
                                                        }
                                                        // Players with shirt numbers come before those without
                                                        if ($aShirt > 0) return -1;
                                                        if ($bShirt > 0) return 1;
                                                        // Players without shirt numbers, sort by name
                                                        return strcmp($a['display_name'] ?? '', $b['display_name'] ?? '');
                                                    });
                                                ?>
                                                <?php foreach ($sortedStarters as $mp): ?>
                                                    <div class="lineup-player-card" data-match-player-id="<?= (int)$mp['id'] ?>" data-shirt-number="<?= (int)($mp['shirt_number'] ?? 0) ?>">
                                                        <div class="flex items-center gap-3">
                                                            <div class="flex items-center gap-2">
                                                                <span class="lineup-shirt-number"><?= htmlspecialchars($mp['shirt_number'] ?? '—') ?></span>
                                                                <span class="lineup-position"><?= htmlspecialchars($mp['position_label'] ?? '—') ?></span>
                                                            </div>
                                                            <div class="flex-1 min-w-0">
                                                                <div class="text-sm font-medium text-white truncate"><?= htmlspecialchars($mp['display_name'] ?? 'Unknown') ?></div>
                                                            </div>
                                                            <?php if ($mp['is_captain']): ?>
                                                                <span class="text-yellow-400 text-xs" title="Captain">⭐</span>
                                                            <?php endif; ?>
                                                            <button type="button" class="lineup-delete-btn" data-delete-player="<?= (int)$mp['id'] ?>">
                                                                <i class="fa-solid fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Substitutes -->
                                    <div class="space-y-3">
                                        <div class="flex items-center justify-between">
                                            <h4 class="text-sm font-medium text-slate-300 uppercase tracking-wider">Substitutes</h4>
                                            <button type="button" class="text-xs font-semibold text-blue-400 hover:text-blue-300 uppercase tracking-wider" data-add-player="home" data-is-starting="0">
                                                <i class="fa-solid fa-plus mr-1"></i> Add
                                            </button>
                                        </div>
                                        <div id="home-subs" class="space-y-2 min-h-[60px]">
                                            <?php if (empty($homeSubs)): ?>
                                                <div class="text-center py-4 text-slate-500 text-sm border-2 border-dashed border-slate-700 rounded-lg">
                                                    No substitutes added yet
                                                </div>
                                            <?php else: ?>
                                                <?php foreach ($homeSubs as $mp): ?>
                                                    <div class="lineup-player-card" data-match-player-id="<?= (int)$mp['id'] ?>">
                                                        <div class="flex items-center gap-3">
                                                            <div class="flex items-center gap-2">
                                                                <span class="lineup-shirt-number"><?= htmlspecialchars($mp['shirt_number'] ?? '—') ?></span>
                                                                <span class="lineup-position"><?= htmlspecialchars($mp['position_label'] ?? '—') ?></span>
                                                            </div>
                                                            <div class="flex-1 min-w-0">
                                                                <div class="text-sm font-medium text-white truncate"><?= htmlspecialchars($mp['display_name'] ?? 'Unknown') ?></div>
                                                            </div>
                                                            <button type="button" class="lineup-delete-btn" data-delete-player="<?= (int)$mp['id'] ?>">
                                                                <i class="fa-solid fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Away Team -->
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between">
                                        <h3 class="text-base font-semibold text-white">
                                            <i class="fa-solid fa-plane-departure text-slate-400 mr-2"></i>
                                            <?= htmlspecialchars($awayTeamName) ?>
                                        </h3>
                                    </div>

                                    <!-- Starting XI -->
                                    <div class="space-y-3">
                                        <div class="flex items-center justify-between">
                                            <h4 class="text-sm font-medium text-slate-300 uppercase tracking-wider">Starting XI</h4>
                                            <button type="button" class="text-xs font-semibold text-blue-400 hover:text-blue-300 uppercase tracking-wider" data-add-player="away" data-is-starting="1">
                                                <i class="fa-solid fa-plus mr-1"></i> Add
                                            </button>
                                        </div>
                                        <div id="away-starters" class="space-y-2 min-h-[100px]">
                                            <?php if (empty($awayStarters)): ?>
                                                <div class="text-center py-6 text-slate-500 text-sm border-2 border-dashed border-slate-700 rounded-lg">
                                                    No starting players added yet
                                                </div>
                                            <?php else: ?>
                                                <?php 
                                                    // Sort by shirt number (1-11 first), then by name
                                                    $sortedStarters = $awayStarters;
                                                    usort($sortedStarters, function($a, $b) {
                                                        $aShirt = (int)($a['shirt_number'] ?? 0);
                                                        $bShirt = (int)($b['shirt_number'] ?? 0);
                                                        
                                                        // Players with shirt numbers (1-11) come first, sorted by number
                                                        if ($aShirt > 0 && $bShirt > 0) {
                                                            return $aShirt <=> $bShirt;
                                                        }
                                                        // Players with shirt numbers come before those without
                                                        if ($aShirt > 0) return -1;
                                                        if ($bShirt > 0) return 1;
                                                        // Players without shirt numbers, sort by name
                                                        return strcmp($a['display_name'] ?? '', $b['display_name'] ?? '');
                                                    });
                                                ?>
                                                <?php foreach ($sortedStarters as $mp): ?>
                                                    <div class="lineup-player-card" data-match-player-id="<?= (int)$mp['id'] ?>" data-shirt-number="<?= (int)($mp['shirt_number'] ?? 0) ?>">
                                                        <div class="flex items-center gap-3">
                                                            <div class="flex items-center gap-2">
                                                                <span class="lineup-shirt-number"><?= htmlspecialchars($mp['shirt_number'] ?? '—') ?></span>
                                                                <span class="lineup-position"><?= htmlspecialchars($mp['position_label'] ?? '—') ?></span>
                                                            </div>
                                                            <div class="flex-1 min-w-0">
                                                                <div class="text-sm font-medium text-white truncate"><?= htmlspecialchars($mp['display_name'] ?? 'Unknown') ?></div>
                                                            </div>
                                                            <?php if ($mp['is_captain']): ?>
                                                                <span class="text-yellow-400 text-xs" title="Captain">⭐</span>
                                                            <?php endif; ?>
                                                            <button type="button" class="lineup-delete-btn" data-delete-player="<?= (int)$mp['id'] ?>">
                                                                <i class="fa-solid fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Substitutes -->
                                    <div class="space-y-3">
                                        <div class="flex items-center justify-between">
                                            <h4 class="text-sm font-medium text-slate-300 uppercase tracking-wider">Substitutes</h4>
                                            <button type="button" class="text-xs font-semibold text-blue-400 hover:text-blue-300 uppercase tracking-wider" data-add-player="away" data-is-starting="0">
                                                <i class="fa-solid fa-plus mr-1"></i> Add
                                            </button>
                                        </div>
                                        <div id="away-subs" class="space-y-2 min-h-[60px]">
                                            <?php if (empty($awaySubs)): ?>
                                                <div class="text-center py-4 text-slate-500 text-sm border-2 border-dashed border-slate-700 rounded-lg">
                                                    No substitutes added yet
                                                </div>
                                            <?php else: ?>
                                                <?php foreach ($awaySubs as $mp): ?>
                                                    <div class="lineup-player-card" data-match-player-id="<?= (int)$mp['id'] ?>">
                                                        <div class="flex items-center gap-3">
                                                            <div class="flex items-center gap-2">
                                                                <span class="lineup-shirt-number"><?= htmlspecialchars($mp['shirt_number'] ?? '—') ?></span>
                                                                <span class="lineup-position"><?= htmlspecialchars($mp['position_label'] ?? '—') ?></span>
                                                            </div>
                                                            <div class="flex-1 min-w-0">
                                                                <div class="text-sm font-medium text-white truncate"><?= htmlspecialchars($mp['display_name'] ?? 'Unknown') ?></div>
                                                            </div>
                                                            <button type="button" class="lineup-delete-btn" data-delete-player="<?= (int)$mp['id'] ?>">
                                                                <i class="fa-solid fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Section 4: Match Events -->
                <section id="section-events" class="edit-section" style="display:none;">
                    <div class="rounded-xl bg-slate-900/50 border border-slate-800 overflow-hidden">
                        <div class="border-b border-slate-800 px-6 py-4">
                            <h2 class="text-lg font-semibold text-white">Match Events</h2>
                            <p class="text-sm text-slate-400 mt-1">Record goals, cards, and substitutions</p>
                        </div>
                        
                        <!-- Event Tabs -->
                        <div class="border-b border-slate-800 bg-slate-900/30">
                            <div class="flex px-6">
                                <button type="button" class="event-tab active" data-tab="goals">
                                    <i class="fa-solid fa-futbol mr-2"></i>
                                    Goals
                                </button>
                                <button type="button" class="event-tab" data-tab="cards">
                                    <i class="fa-solid fa-square mr-2 text-yellow-500"></i>
                                    Cards
                                </button>
                                <button type="button" class="event-tab" data-tab="substitutions">
                                    <i class="fa-solid fa-repeat mr-2"></i>
                                    Substitutions
                                </button>
                            </div>
                        </div>

                        <!-- Event Content -->
                        <div class="p-6">
                            <!-- Goals Tab -->
                            <div id="tab-goals" class="event-tab-content active">
                                <div class="mb-4 flex items-center justify-between">
                                    <h3 class="text-lg font-semibold text-white">Goals</h3>
                                    <button type="button" class="btn-primary text-sm" data-add-goal>
                                        <i class="fa-solid fa-plus mr-2"></i>
                                        Add Goal
                                    </button>
                                </div>
                                
                                <!-- Two Column Layout -->
                                <div class="grid grid-cols-2 gap-4">
                                    <!-- Home Team Goals -->
                                    <div>
                                        <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-700">
                                            <h4 class="text-sm font-semibold text-blue-400">
                                                <?= htmlspecialchars($homeTeamName) ?>
                                            </h4>
                                            <span class="text-2xl font-bold text-blue-400"><?= $homeGoals ?></span>
                                        </div>
                                        <div class="space-y-2">
                                            <?php 
                                            $homeGoalsList = array_filter($goals, fn($g) => $g['team_side'] === 'home');
                                            // Sort by minute (chronological)
                                            usort($homeGoalsList, function($a, $b) {
                                                $minuteA = ($a['minute'] ?? 0) + (($a['minute_extra'] ?? 0) / 100);
                                                $minuteB = ($b['minute'] ?? 0) + (($b['minute_extra'] ?? 0) / 100);
                                                return $minuteA <=> $minuteB;
                                            });
                                            if (empty($homeGoalsList)): ?>
                                                <div class="text-center py-8 text-slate-500 text-sm">
                                                    <p>No goals</p>
                                                </div>
                                            <?php else: ?>
                                                <?php foreach ($homeGoalsList as $goal): 
                                                    // Prefer the match player name (contains shirt prefix) then fall back to display_name
                                                    $goalPlayerRaw = $goal['match_player_name'] 
                                                        ?? $goal['display_name'] 
                                                        ?? '';
                                                    $goalPlayer = trim($goalPlayerRaw) !== '' ? $goalPlayerRaw : 'Unknown';
                                                    $goalMinute = $goal['minute'] ?? 0;
                                                    $goalMinuteExtra = $goal['minute_extra'] ?? 0;
                                                    $goalMinuteDisplay = $goalMinute . ($goalMinuteExtra > 0 ? "+{$goalMinuteExtra}" : '');
                                                    $goalMatchPlayerId = (int)($goal['match_player_id'] ?? 0);
                                                    $goalEventTypeId = (int)($goal['event_type_id'] ?? 0);
                                                    $goalOutcome = $goal['outcome'] ?? '';
                                                    $isOwnGoal = $goalOutcome === 'own_goal';
                                                ?>
                                                    <div class="rounded-lg bg-slate-800/40 border border-slate-700 p-3">
                                                        <div class="flex items-center gap-3">
                                                            <div class="flex-1 min-w-0">
                                                                <div class="text-sm font-semibold text-white truncate">
                                                                    <?= htmlspecialchars($goalPlayer) ?>
                                                                    <?php if ($isOwnGoal): ?>
                                                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-500/10 border border-amber-500/40 text-amber-300">
                                                                            OG
                                                                        </span>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div class="text-xs text-slate-400">
                                                                    <?= htmlspecialchars($goalMinuteDisplay) ?>'<?= $isOwnGoal ? ' (Own goal)' : '' ?>
                                                                </div>
                                                            </div>
                                                            <div class="flex items-center gap-2 ml-auto">
                                                                <button type="button" class="text-xs font-semibold px-3 py-1 rounded border border-slate-600 text-slate-200 hover:border-slate-400" 
                                                                    data-edit-goal
                                                                    data-event-id="<?= (int)$goal['id'] ?>"
                                                                    data-event-type-id="<?= $goalEventTypeId ?>"
                                                                    data-team-side="<?= htmlspecialchars($goal['team_side'] ?? '') ?>"
                                                                    data-minute="<?= $goalMinute ?>"
                                                                    data-minute-extra="<?= $goalMinuteExtra ?>"
                                                                    data-match-player-id="<?= $goalMatchPlayerId ?>"
                                                                    data-outcome="<?= htmlspecialchars($goalOutcome) ?>">
                                                                    Edit
                                                                </button>
                                                                <button type="button" class="text-rose-400 hover:text-rose-300 text-sm" data-delete-event="<?= (int)$goal['id'] ?>">
                                                                    <i class="fa-solid fa-trash"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Away Team Goals -->
                                    <div>
                                        <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-700">
                                            <h4 class="text-sm font-semibold text-slate-400">
                                                <?= htmlspecialchars($awayTeamName) ?>
                                            </h4>
                                            <span class="text-2xl font-bold text-slate-400"><?= $awayGoals ?></span>
                                        </div>
                                        <div class="space-y-2">
                                            <?php 
                                            $awayGoalsList = array_filter($goals, fn($g) => $g['team_side'] === 'away');
                                            // Sort by minute (chronological)
                                            usort($awayGoalsList, function($a, $b) {
                                                $minuteA = ($a['minute'] ?? 0) + (($a['minute_extra'] ?? 0) / 100);
                                                $minuteB = ($b['minute'] ?? 0) + (($b['minute_extra'] ?? 0) / 100);
                                                return $minuteA <=> $minuteB;
                                            });
                                            if (empty($awayGoalsList)): ?>
                                                <div class="text-center py-8 text-slate-500 text-sm">
                                                    <p>No goals</p>
                                                </div>
                                            <?php else: ?>
                                                <?php foreach ($awayGoalsList as $goal): 
                                                    // Prefer the match player name (contains shirt prefix) then fall back to display_name
                                                    $goalPlayerRaw = $goal['match_player_name'] 
                                                        ?? $goal['display_name'] 
                                                        ?? '';
                                                    $goalPlayer = trim($goalPlayerRaw) !== '' ? $goalPlayerRaw : 'Unknown';
                                                    $goalMinute = $goal['minute'] ?? 0;
                                                    $goalMinuteExtra = $goal['minute_extra'] ?? 0;
                                                    $goalMinuteDisplay = $goalMinute . ($goalMinuteExtra > 0 ? "+{$goalMinuteExtra}" : '');
                                                    $goalMatchPlayerId = (int)($goal['match_player_id'] ?? 0);
                                                    $goalEventTypeId = (int)($goal['event_type_id'] ?? 0);
                                                    $goalOutcome = $goal['outcome'] ?? '';
                                                    $isOwnGoal = $goalOutcome === 'own_goal';
                                                ?>
                                                    <div class="rounded-lg bg-slate-800/40 border border-slate-700 p-3">
                                                        <div class="flex items-center gap-3">
                                                            <div class="flex-1 min-w-0">
                                                                <div class="text-sm font-semibold text-white truncate">
                                                                    <?= htmlspecialchars($goalPlayer) ?>
                                                                    <?php if ($isOwnGoal): ?>
                                                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-500/10 border border-amber-500/40 text-amber-300">
                                                                            OG
                                                                        </span>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div class="text-xs text-slate-400">
                                                                    <?= htmlspecialchars($goalMinuteDisplay) ?>'<?= $isOwnGoal ? ' (Own goal)' : '' ?>
                                                                </div>
                                                            </div>
                                                            <div class="flex items-center gap-2 ml-auto">
                                                                <button type="button" class="text-xs font-semibold px-3 py-1 rounded border border-slate-600 text-slate-200 hover:border-slate-400" 
                                                                    data-edit-goal
                                                                    data-event-id="<?= (int)$goal['id'] ?>"
                                                                    data-event-type-id="<?= $goalEventTypeId ?>"
                                                                    data-team-side="<?= htmlspecialchars($goal['team_side'] ?? '') ?>"
                                                                    data-minute="<?= $goalMinute ?>"
                                                                    data-minute-extra="<?= $goalMinuteExtra ?>"
                                                                    data-match-player-id="<?= $goalMatchPlayerId ?>"
                                                                    data-outcome="<?= htmlspecialchars($goalOutcome) ?>">
                                                                    Edit
                                                                </button>
                                                                <button type="button" class="text-rose-400 hover:text-rose-300 text-sm" data-delete-event="<?= (int)$goal['id'] ?>">
                                                                    <i class="fa-solid fa-trash"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Cards Tab -->
                            <div id="tab-cards" class="event-tab-content" style="display:none;">
                                <div class="mb-4 flex items-center justify-between">
                                    <h3 class="text-lg font-semibold text-white">Disciplinary Cards</h3>
                                    <div class="flex gap-2">
                                        <button type="button" class="btn-secondary text-sm" data-add-card="yellow">
                                            <i class="fa-solid fa-square text-yellow-500 mr-2"></i>
                                            Add Yellow
                                        </button>
                                        <button type="button" class="btn-secondary text-sm" data-add-card="red">
                                            <i class="fa-solid fa-square text-rose-500 mr-2"></i>
                                            Add Red
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Two Column Layout -->
                                <div class="grid grid-cols-2 gap-4">
                                    <!-- Home Team Cards -->
                                    <div>
                                        <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-700">
                                            <h4 class="text-sm font-semibold text-blue-400">
                                                <?= htmlspecialchars($homeTeamName) ?>
                                            </h4>
                                        </div>
                                        <div class="space-y-2" id="home-cards-list">
                                            <?php 
                                            $homeCardsList = array_filter($cards, fn($c) => $c['team_side'] === 'home');
                                            if (empty($homeCardsList)): ?>
                                                <div class="text-center py-8 text-slate-500 text-sm card-empty" data-team="home">
                                                    <p>No cards</p>
                                                </div>
                                            <?php else: ?>
                                                <?php foreach ($homeCardsList as $card): 
                                                    // Prefer match-specific name (includes shirt prefix) then fall back; ensure non-empty value
                                                    $cardPlayer = $card['match_player_name'] 
                                                        ?? $card['display_name'] 
                                                        ?? 'Unknown';
                                                    if (trim((string)$cardPlayer) === '') {
                                                        $cardPlayer = 'Unknown';
                                                    }
                                                    $cardMinute = $card['minute'] ?? 0;
                                                    $cardMinuteExtra = $card['minute_extra'] ?? 0;
                                                    $cardMinuteDisplay = $cardMinute . ($cardMinuteExtra > 0 ? "+{$cardMinuteExtra}" : '');
                                                    $isYellow = ($card['event_type_key'] ?? '') === 'yellow_card';
                                                    $cardMatchPlayerId = (int)($card['match_player_id'] ?? 0);
                                                    $cardEventTypeId = (int)($card['event_type_id'] ?? 0);
                                                ?>
                                                    <div class="rounded-lg bg-slate-800/40 border border-slate-700 p-3">
                                                        <div class="flex items-start gap-3">
                                                            <div class="flex-1 min-w-0">
                                                                <div class="text-sm font-semibold text-white truncate">
                                                                    <?= htmlspecialchars($cardPlayer) ?>
                                                                </div>
                                                                <div class="text-xs text-slate-400">
                                                                    <?= htmlspecialchars($cardMinuteDisplay) ?>'
                                                                </div>
                                                                <div class="mt-1 inline-flex items-center gap-2 text-xs font-semibold px-2 py-1 rounded <?= $isYellow ? 'bg-amber-500/15 text-amber-300' : 'bg-rose-500/15 text-rose-200' ?>">
                                                                    <?= $isYellow ? 'Yellow Card' : 'Red Card' ?>
                                                                </div>
                                                                <?php if (!empty($card['notes'])): ?>
                                                                    <div class="text-xs text-slate-500 mt-2"><?= htmlspecialchars($card['notes']) ?></div>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="flex items-center gap-2 ml-auto">
                                                                <button type="button" class="text-xs font-semibold px-3 py-1 rounded border border-slate-600 text-slate-200 hover:border-slate-400"
                                                                    data-edit-card
                                                                    data-event-id="<?= (int)$card['id'] ?>"
                                                                    data-event-type-id="<?= $cardEventTypeId ?>"
                                                                    data-card-type="<?= $isYellow ? 'yellow' : 'red' ?>"
                                                                    data-team-side="<?= htmlspecialchars($card['team_side'] ?? '') ?>"
                                                                    data-minute="<?= $cardMinute ?>"
                                                                    data-minute-extra="<?= $cardMinuteExtra ?>"
                                                                    data-match-player-id="<?= $cardMatchPlayerId ?>"
                                                                    data-notes="<?= htmlspecialchars($card['notes'] ?? '') ?>">
                                                                    Edit
                                                                </button>
                                                                <button type="button" class="text-rose-400 hover:text-rose-300 text-sm" data-delete-event="<?= (int)$card['id'] ?>">
                                                                    <i class="fa-solid fa-trash"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Away Team Cards -->
                                    <div>
                                        <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-700">
                                            <h4 class="text-sm font-semibold text-slate-400">
                                                <?= htmlspecialchars($awayTeamName) ?>
                                            </h4>
                                        </div>
                                        <div class="space-y-2" id="away-cards-list">
                                            <?php 
                                            $awayCardsList = array_filter($cards, fn($c) => $c['team_side'] === 'away');
                                            if (empty($awayCardsList)): ?>
                                                <div class="text-center py-8 text-slate-500 text-sm card-empty" data-team="away">
                                                    <p>No cards</p>
                                                </div>
                                            <?php else: ?>
                                                <?php foreach ($awayCardsList as $card): 
                                                    // Prefer match-specific name (includes shirt prefix) then fall back; ensure non-empty value
                                                    $cardPlayer = $card['match_player_name'] 
                                                        ?? $card['display_name'] 
                                                        ?? 'Unknown';
                                                    if (trim((string)$cardPlayer) === '') {
                                                        $cardPlayer = 'Unknown';
                                                    }
                                                    $cardMinute = $card['minute'] ?? 0;
                                                    $cardMinuteExtra = $card['minute_extra'] ?? 0;
                                                    $cardMinuteDisplay = $cardMinute . ($cardMinuteExtra > 0 ? "+{$cardMinuteExtra}" : '');
                                                    $isYellow = ($card['event_type_key'] ?? '') === 'yellow_card';
                                                    $cardMatchPlayerId = (int)($card['match_player_id'] ?? 0);
                                                    $cardEventTypeId = (int)($card['event_type_id'] ?? 0);
                                                ?>
                                                    <div class="rounded-lg bg-slate-800/40 border border-slate-700 p-3">
                                                        <div class="flex items-start gap-3">
                                                            <div class="flex-1 min-w-0">
                                                                <div class="text-sm font-semibold text-white truncate">
                                                                    <?= htmlspecialchars($cardPlayer) ?>
                                                                </div>
                                                                <div class="text-xs text-slate-400">
                                                                    <?= htmlspecialchars($cardMinuteDisplay) ?>'
                                                                </div>
                                                                <div class="mt-1 inline-flex items-center gap-2 text-xs font-semibold px-2 py-1 rounded <?= $isYellow ? 'bg-amber-500/15 text-amber-300' : 'bg-rose-500/15 text-rose-200' ?>">
                                                                    <?= $isYellow ? 'Yellow Card' : 'Red Card' ?>
                                                                </div>
                                                                <?php if (!empty($card['notes'])): ?>
                                                                    <div class="text-xs text-slate-500 mt-2"><?= htmlspecialchars($card['notes']) ?></div>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="flex items-center gap-2 ml-auto">
                                                                <button type="button" class="text-xs font-semibold px-3 py-1 rounded border border-slate-600 text-slate-200 hover:border-slate-400"
                                                                    data-edit-card
                                                                    data-event-id="<?= (int)$card['id'] ?>"
                                                                    data-event-type-id="<?= $cardEventTypeId ?>"
                                                                    data-card-type="<?= $isYellow ? 'yellow' : 'red' ?>"
                                                                    data-team-side="<?= htmlspecialchars($card['team_side'] ?? '') ?>"
                                                                    data-minute="<?= $cardMinute ?>"
                                                                    data-minute-extra="<?= $cardMinuteExtra ?>"
                                                                    data-match-player-id="<?= $cardMatchPlayerId ?>"
                                                                    data-notes="<?= htmlspecialchars($card['notes'] ?? '') ?>">
                                                                    Edit
                                                                </button>
                                                                <button type="button" class="text-rose-400 hover:text-rose-300 text-sm" data-delete-event="<?= (int)$card['id'] ?>">
                                                                    <i class="fa-solid fa-trash"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Substitutions Tab -->
                            <div id="tab-substitutions" class="event-tab-content" style="display:none;">
                                <div class="mb-4 flex items-center justify-between">
                                    <h3 class="text-lg font-semibold text-white">Substitutions</h3>
                                    <button type="button" class="btn-primary text-sm" data-add-substitution>
                                        <i class="fa-solid fa-repeat mr-2"></i>
                                        Add Substitution
                                    </button>
                                </div>
                                
                                <!-- Two Column Layout -->
                                <div class="grid grid-cols-2 gap-4">
                                    <!-- Home Team Substitutions -->
                                    <div>
                                        <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-700">
                                            <h4 class="text-sm font-semibold text-blue-400">
                                                <i class="fa-solid fa-house-chimney mr-2"></i>
                                                <?= htmlspecialchars($homeTeamName) ?>
                                            </h4>
                                        </div>
                                        <div class="space-y-2" id="home-subs-list">
                                            <?php 
                                            $homeSubsList = array_filter($substitutions, fn($s) => $s['team_side'] === 'home');
                                            if (empty($homeSubsList)): ?>
                                                <div class="text-center py-8 text-slate-500 text-sm">
                                                    <i class="fa-solid fa-repeat opacity-30 mb-2"></i>
                                                    <p>No substitutions</p>
                                                </div>
                                            <?php else: ?>
                                                <?php foreach ($homeSubsList as $sub): 
                                                    $subMinute = $sub['minute'] ?? 0;
                                                    $playerOff = $sub['player_off_name'] ?? 'Unknown';
                                                    $playerOn = $sub['player_on_name'] ?? 'Unknown';
                                                    $shirtOff = $sub['player_off_shirt'] ?? '?';
                                                    $shirtOn = $sub['player_on_shirt'] ?? '?';
                                                    $reason = $sub['reason'] ?? '';
                                                ?>
                                                    <div class="rounded-lg bg-slate-800/40 border border-slate-700 p-3">
                                                        <div class="flex items-center gap-2">
                                                            <span class="text-lg">🔄</span>
                                                            <div class="flex-1 min-w-0">
                                                                <div class="text-xs text-slate-400 mb-1">
                                                                    <?= $subMinute ?>'
                                                                </div>
                                                                <div class="text-xs space-y-0.5">
                                                                    <div class="text-slate-400">
                                                                        <i class="fa-solid fa-arrow-down mr-1"></i>
                                                                        OFF: #<?= htmlspecialchars($shirtOff) ?> <?= htmlspecialchars($playerOff) ?>
                                                                    </div>
                                                                    <div class="text-emerald-400">
                                                                        <i class="fa-solid fa-arrow-up mr-1"></i>
                                                                        ON: #<?= htmlspecialchars($shirtOn) ?> <?= htmlspecialchars($playerOn) ?>
                                                                    </div>
                                                                    <?php if ($reason): ?>
                                                                        <div class="text-xs text-slate-500 mt-1">
                                                                            <?= htmlspecialchars(ucfirst($reason)) ?>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                            <button type="button" class="text-rose-400 hover:text-rose-300 text-sm" data-delete-substitution="<?= (int)$sub['id'] ?>">
                                                                <i class="fa-solid fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Away Team Substitutions -->
                                    <div>
                                        <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-700">
                                            <h4 class="text-sm font-semibold text-slate-400">
                                                <i class="fa-solid fa-plane-departure mr-2"></i>
                                                <?= htmlspecialchars($awayTeamName) ?>
                                            </h4>
                                        </div>
                                        <div class="space-y-2" id="away-subs-list">
                                            <?php 
                                            $awaySubsList = array_filter($substitutions, fn($s) => $s['team_side'] === 'away');
                                            if (empty($awaySubsList)): ?>
                                                <div class="text-center py-8 text-slate-500 text-sm">
                                                    <i class="fa-solid fa-repeat opacity-30 mb-2"></i>
                                                    <p>No substitutions</p>
                                                </div>
                                            <?php else: ?>
                                                <?php foreach ($awaySubsList as $sub): 
                                                    $subMinute = $sub['minute'] ?? 0;
                                                    $playerOff = $sub['player_off_name'] ?? 'Unknown';
                                                    $playerOn = $sub['player_on_name'] ?? 'Unknown';
                                                    $shirtOff = $sub['player_off_shirt'] ?? '?';
                                                    $shirtOn = $sub['player_on_shirt'] ?? '?';
                                                    $reason = $sub['reason'] ?? '';
                                                ?>
                                                    <div class="rounded-lg bg-slate-800/40 border border-slate-700 p-3">
                                                        <div class="flex items-center gap-2">
                                                            <span class="text-lg">🔄</span>
                                                            <div class="flex-1 min-w-0">
                                                                <div class="text-xs text-slate-400 mb-1">
                                                                    <?= $subMinute ?>'
                                                                </div>
                                                                <div class="text-xs space-y-0.5">
                                                                    <div class="text-slate-400">
                                                                        <i class="fa-solid fa-arrow-down mr-1"></i>
                                                                        OFF: #<?= htmlspecialchars($shirtOff) ?> <?= htmlspecialchars($playerOff) ?>
                                                                    </div>
                                                                    <div class="text-emerald-400">
                                                                        <i class="fa-solid fa-arrow-up mr-1"></i>
                                                                        ON: #<?= htmlspecialchars($shirtOn) ?> <?= htmlspecialchars($playerOn) ?>
                                                                    </div>
                                                                    <?php if ($reason): ?>
                                                                        <div class="text-xs text-slate-500 mt-1">
                                                                            <?= htmlspecialchars(ucfirst($reason)) ?>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                            <button type="button" class="text-rose-400 hover:text-rose-300 text-sm" data-delete-substitution="<?= (int)$sub['id'] ?>">
                                                                <i class="fa-solid fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </main>
            
            <!-- Right Sidebar: Match Overview -->
            <aside class="hidden space-y-4 min-w-0">
                <div class="rounded-xl bg-slate-900/80 border border-white/10 p-4">
                    <h5 class="text-slate-200 font-semibold mb-1">Match Overview</h5>
                    <div class="text-slate-400 text-xs mb-4">Quick match statistics</div>
                    <div class="space-y-3">
                        <!-- Score Card -->
                        <article class="rounded-lg border border-white/10 bg-slate-800/40 px-3 py-3">
                            <div class="text-xs font-semibold text-slate-300 mb-2 text-center">Current Score</div>
                            <div class="text-center">
                                <div class="text-3xl font-bold text-slate-100 stat-value">
                                    <?= $homeGoals ?> - <?= $awayGoals ?>
                                </div>
                                <div class="text-xs text-slate-400 mt-1">
                                    <?= htmlspecialchars($homeTeamName) ?> vs <?= htmlspecialchars($awayTeamName) ?>
                                </div>
                            </div>
                        </article>
                        
                        <div class="border-t border-white/10"></div>
                        
                        <!-- Team Stats -->
                        <article class="rounded-lg border border-white/10 bg-slate-800/40 px-3 py-3">
                            <div class="text-xs font-semibold text-slate-300 mb-2 text-center">Team Statistics</div>
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="text-[10px] text-slate-400">Home Players</div>
                                        <div class="text-xs text-slate-300"><?= count($homeStarters) + count($homeSubs) ?> total</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm font-bold text-blue-400"><?= count($homeStarters) ?></div>
                                        <div class="text-[10px] text-slate-400">starters</div>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="text-[10px] text-slate-400">Away Players</div>
                                        <div class="text-xs text-slate-300"><?= count($awayStarters) + count($awaySubs) ?> total</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm font-bold text-blue-400"><?= count($awayStarters) ?></div>
                                        <div class="text-[10px] text-slate-400">starters</div>
                                    </div>
                                </div>
                            </div>
                        </article>
                        
                        <div class="border-t border-white/10"></div>
                        
                        <!-- Events Summary -->
                        <article class="rounded-lg border border-white/10 bg-slate-800/40 px-3 py-3">
                            <div class="text-xs font-semibold text-slate-300 mb-2 text-center">Match Events</div>
                            <div class="grid grid-cols-3 gap-3 text-center">
                                <div>
                                    <div class="text-xl font-bold text-emerald-400 stat-value"><?= count($goals) ?></div>
                                    <div class="text-[10px] text-slate-400">Goals</div>
                                </div>
                                <div>
                                    <div class="text-xl font-bold text-yellow-400 stat-value"><?= count($yellowCards) ?></div>
                                    <div class="text-[10px] text-slate-400">Yellow</div>
                                </div>
                                <div>
                                    <div class="text-xl font-bold text-red-400 stat-value"><?= count($redCards) ?></div>
                                    <div class="text-[10px] text-slate-400">Red</div>
                                </div>
                            </div>
                        </article>
                        
                        <div class="border-t border-white/10"></div>
                        
                        <!-- Substitutions -->
                        <article class="rounded-lg border border-white/10 bg-slate-800/40 px-3 py-3">
                            <div class="text-xs font-semibold text-slate-300 mb-2 text-center">Substitutions</div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-cyan-400 stat-value"><?= count($substitutions) ?></div>
                                <div class="text-[10px] text-slate-400 mt-1">Total substitutions made</div>
                            </div>
                        </article>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>

<!-- Sticky Save Footer -->
<div class="fixed bottom-0 left-0 right-0 bg-gradient-to-t from-slate-950 via-slate-950 to-slate-950/95 border-t border-slate-800 z-40 sticky-button-container">
    <div class="max-w-full px-4 md:px-6 lg:px-8 py-4">
        <div class="flex items-center justify-end gap-3 max-w-screen-xl">
            <span id="form-dirty-indicator" class="hidden text-xs font-medium text-amber-400 flex items-center gap-2">
                <span class="inline-block w-2 h-2 bg-amber-400 rounded-full animate-pulse"></span>
                Unsaved changes
            </span>
            <a href="<?= htmlspecialchars($base) ?>/matches" class="px-4 py-2.5 text-sm font-medium rounded-lg border border-slate-700 bg-slate-800/50 text-slate-300 hover:bg-slate-700 hover:border-slate-600 transition-all duration-200">
                Cancel
            </a>
            <button type="submit" form="match-details-form" class="match-details-submit px-6 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-900/50 disabled:cursor-not-allowed text-white text-sm font-medium rounded-lg transition-colors shadow-lg hover:shadow-blue-500/20 flex items-center gap-2"
                    aria-label="Save match details">
                <i class="fa-solid fa-save"></i>
                <span class="submit-text">Save Match Details</span>
                <span class="submit-loading hidden ml-2"><i class="fa-solid fa-spinner fa-spin"></i></span>
            </button>
        </div>
    </div>
</div>

<!-- Adjust main page bottom padding to account for sticky footer -->
<style>
    body { padding-bottom: 80px; }
</style>

<!-- Add Player Modal -->
<div id="addPlayerModal" class="modal" style="display:none;" role="dialog" aria-labelledby="add-player-modal-title" aria-modal="true">
    <div class="modal-backdrop" aria-hidden="true"></div>
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header flex-col items-start">
                <h3 id="add-player-modal-title" class="modal-title text-lg font-semibold">
                    Add Player - <span id="add-player-team-name" class="font-bold text-blue-300">
                        <?php
                            // Default to home team name as fallback, JS will update as needed
                            echo htmlspecialchars($homeTeamName ?: ($clubTeams[0]['name'] ?? ''));
                        ?>
                    </span>
                </h3>
                <div id="add-player-modal-subtitle" class="text-xs text-slate-400 mt-1 mb-1"></div>
                <button type="button" class="modal-close absolute top-4 right-4" data-close-modal aria-label="Close dialog (press ESC)" title="Close (ESC)">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <form id="addPlayerForm">
                <div class="modal-body space-y-4">
                    <div id="player-modal-tip" class="mb-3 p-3 bg-blue-900/30 border border-blue-700 rounded-lg text-sm text-blue-200 flex items-center gap-2">
                        <i class="fa-solid fa-keyboard mr-2"></i>
                        <span class="font-medium">Tip:</span> Press <kbd class="px-1.5 py-0.5 bg-slate-700 rounded text-xs">ESC</kbd> to close this dialog
                    </div>
                    
                    <input type="hidden" id="player-team-side" name="team_side">
                    <input type="hidden" id="player-is-starting" name="is_starting">
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">
                            Player <span class="text-rose-400">*</span>
                        </label>
                        <div id="player-select-wrapper" class="w-full">
                            <!-- Will be populated dynamically -->
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2" for="player-shirt-number">
                                Shirt #
                            </label>
                            <input type="number" id="player-shirt-number" name="shirt_number" min="1" max="99"
                                   class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white focus:border-blue-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2" for="player-position">
                                Position
                            </label>
                            <select id="player-position" name="position_label"
                                    class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white focus:border-blue-500 focus:outline-none">
                                <option value="">Select</option>
                                <option value="GK">GK</option>
                                <option value="LB">LB</option>
                                <option value="CB">CB</option>
                                <option value="RB">RB</option>
                                <option value="LWB">LWB</option>
                                <option value="RWB">RWB</option>
                                <option value="CDM">CDM</option>
                                <option value="CM">CM</option>
                                <option value="CAM">CAM</option>
                                <option value="LM">LM</option>
                                <option value="RM">RM</option>
                                <option value="LW">LW</option>
                                <option value="RW">RW</option>
                                <option value="ST">ST</option>
                                <option value="CF">CF</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="hidden" id="player-is-captain" name="is_captain" value="0">
                        <button type="button" id="captain-toggle-btn" class="flex items-center gap-2 px-3 py-2 rounded-lg border border-slate-700 bg-slate-800 hover:bg-slate-700 transition-colors text-sm text-slate-300">
                            <i class="fa-solid fa-star text-slate-600" id="captain-star-icon"></i>
                            <span>Captain</span>
                        </button>
                        <span class="text-xs text-slate-500">(Click star to set as captain)</span>
                    </div>

                    <div id="player-form-error" class="hidden text-sm text-rose-400 p-3 bg-rose-900/20 rounded-lg border border-rose-700/50"></div>
                </div>
                <div class="modal-footer">
                  
                    <!-- Single mode buttons -->
                    <div class="flex gap-2">
                             <button type="button" class="btn-secondary" data-close-modal>Cancel</button>
                        <button type="submit" class="btn-primary" id="add-player-btn">
                            <i class="fa-solid fa-plus mr-2"></i>
                            Add Player
                        </button>
                        <button type="button" class="btn-primary" id="add-another-btn">
                            <i class="fa-solid fa-redo mr-2"></i>
                            Save & Add Another
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add New Player Modal (for creating players not yet in database) -->
<div id="addNewPlayerModal" class="modal" style="display:none;" role="dialog" aria-labelledby="add-new-player-modal-title" aria-modal="true">
    <div class="modal-backdrop" aria-hidden="true"></div>
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="add-new-player-modal-title" class="modal-title">Add New Player</h3>
                <button type="button" class="modal-close" data-close-new-player-modal aria-label="Close dialog (press ESC)">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <form id="addNewPlayerForm">
                <div class="modal-body space-y-4">
                    <input type="hidden" id="new-player-club-id" name="club_id">
                    <input type="hidden" id="new-player-team-id" name="team_id">
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">First Name <span class="text-rose-400">*</span></label>
                            <input type="text" id="new-player-first-name" name="first_name" required
                                   class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white focus:border-blue-500 focus:outline-none"
                                   placeholder="e.g., Craig">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Last Name <span class="text-rose-400">*</span></label>
                            <input type="text" id="new-player-last-name" name="last_name" required
                                   class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white focus:border-blue-500 focus:outline-none"
                                   placeholder="e.g., Lamb">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Position</label>
                        <select id="new-player-position" name="primary_position"
                                class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white focus:border-blue-500 focus:outline-none">
                            <option value="">Unknown</option>
                            <option value="GK">GK</option>
                            <option value="LB">LB</option>
                            <option value="CB">CB</option>
                            <option value="RB">RB</option>
                            <option value="LWB">LWB</option>
                            <option value="RWB">RWB</option>
                            <option value="CDM">CDM</option>
                            <option value="CM">CM</option>
                            <option value="CAM">CAM</option>
                            <option value="LM">LM</option>
                            <option value="RM">RM</option>
                            <option value="LW">LW</option>
                            <option value="RW">RW</option>
                            <option value="ST">ST</option>
                            <option value="CF">CF</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-3 p-3 bg-slate-800/50 rounded-lg border border-slate-700">
                        <input type="checkbox" id="new-player-is-active" name="is_active" value="1" checked
                               class="w-4 h-4 rounded border-slate-700 bg-slate-800 text-blue-600 focus:ring-2 focus:ring-blue-500">
                        <div class="flex-1">
                            <label for="new-player-is-active" class="text-sm font-medium text-slate-300 block">Active Player</label>
                            <p class="text-xs text-slate-500 mt-0.5">Uncheck if this player is no longer at the club</p>
                        </div>
                    </div>

                    <div id="new-player-form-error" class="hidden p-3 bg-red-900/30 border border-red-700 rounded-lg text-sm text-red-200">
                        <i class="fa-solid fa-exclamation-circle mr-2"></i>
                        <span id="new-player-error-text"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" data-close-new-player-modal>Cancel</button>
                    <button type="submit" class="btn-primary">
                        <i class="fa-solid fa-user-plus mr-2"></i>
                        Create Player
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Goal Modal -->
<div id="addGoalModal" class="modal" style="display:none;" role="dialog" aria-labelledby="add-goal-modal-title" aria-modal="true">
    <div class="modal-backdrop" aria-hidden="true"></div>
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="add-goal-modal-title" class="modal-title">Add Goal</h3>
                <button type="button" class="modal-close" data-close-modal="goal" aria-label="Close dialog (press ESC)" title="Close (ESC)">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <div class="px-6 pt-3 pb-2">
                <div class="text-xs text-slate-400">
                    <i class="fa-solid fa-keyboard mr-1"></i>
                    <span class="font-medium">Tip:</span> Press <kbd class="px-1.5 py-0.5 bg-slate-700 rounded text-xs">ESC</kbd> to close this dialog
                </div>
            </div>
            <form id="addGoalForm">
                <input type="hidden" id="goal-event-id" name="event_id" value="">
                <input type="hidden" id="goal-event-type-id" name="event_type_id" value="16">
                <div class="modal-body space-y-4">

                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">
                            Team <span class="text-rose-400">*</span>
                        </label>
                        <div class="flex gap-2">
                            <button type="button" class="team-toggle-btn" data-team-value="home" name="team_side_btn">
                                <i class="fa-solid fa-house mr-2"></i>Home
                            </button>
                            <button type="button" class="team-toggle-btn" data-team-value="away" name="team_side_btn">
                                <i class="fa-solid fa-arrow-right mr-2"></i>Away
                            </button>
                            <input type="hidden" name="team_side" id="goal-team-side" value="home">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">
                            Scorer <span class="text-rose-400">*</span>
                        </label>
                        <div id="goal-player-select-wrapper">
                            <!-- Populated dynamically -->
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2" for="goal-minute">
                            Minute <span class="text-rose-400">*</span>
                        </label>
                        <input type="number" id="goal-minute" name="minute" required min="0" max="120"
                               class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white focus:border-blue-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">
                            Goal Type
                        </label>
                        <div class="flex gap-2">
                            <button type="button" class="team-toggle-btn" data-goal-type="own_goal" name="goal_type_btn">
                                <i class="fa-solid fa-warning mr-2"></i>Own Goal
                            </button>
                            <button type="button" class="team-toggle-btn" data-goal-type="penalty" name="goal_type_btn">
                                <i class="fa-solid fa-futbol mr-2"></i>Penalty
                            </button>
                            <input type="hidden" name="goal_type_own_goal" id="goal-own-goal-hidden" value="0">
                            <input type="hidden" name="goal_type_penalty" id="goal-is-penalty-hidden" value="0">
                        </div>
                    </div>

                    <div id="goal-form-error" class="hidden text-sm text-rose-400 p-3 bg-rose-900/20 rounded-lg border border-rose-700/50"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" data-close-modal="goal">Cancel</button>
                    <button type="submit" class="btn-primary">
                        <span class="goal-submit-label">Add Goal</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Card Modal -->
<div id="addCardModal" class="modal" style="display:none;">
    <div class="modal-backdrop"></div>
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Add Card</h3>
                <button type="button" class="modal-close" data-close-modal="card">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <form id="addCardForm">
                <input type="hidden" id="card-event-id" name="event_id" value="">
                <input type="hidden" id="card-event-type-id" name="event_type_id" value="8">
                <div class="modal-body space-y-4">
                    <input type="hidden" id="card-type" name="card_type">
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">
                            Team <span class="text-rose-400">*</span>
                        </label>
                        <div class="flex gap-2">
                            <button type="button" class="team-toggle-btn" data-team-value="home" name="team_side_btn">
                                <i class="fa-solid fa-house mr-2"></i>Home
                            </button>
                            <button type="button" class="team-toggle-btn" data-team-value="away" name="team_side_btn">
                                <i class="fa-solid fa-arrow-right mr-2"></i>Away
                            </button>
                            <input type="hidden" name="team_side" id="card-team-side" value="home">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">
                            Player <span class="text-rose-400">*</span>
                        </label>
                        <div id="card-player-select-wrapper">
                            <!-- Populated dynamically -->
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2" for="card-minute">
                            Minute <span class="text-rose-400">*</span>
                        </label>
                        <input type="number" id="card-minute" name="minute" required min="0" max="120"
                               class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white focus:border-blue-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2" for="card-notes">
                            Notes
                        </label>
                        <input type="text" id="card-notes" name="notes" placeholder="Reason or context"
                               class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white placeholder-slate-500 focus:border-blue-500 focus:outline-none">
                    </div>

                    <div id="card-form-error" class="hidden text-sm text-rose-400 p-3 bg-rose-900/20 rounded-lg border border-rose-700/50"></div>
                    <div id="card-form-success" class="hidden text-sm text-green-400 p-3 bg-green-900/20 rounded-lg border border-green-700/50"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" data-close-modal="card">Cancel</button>
                    <div class="flex gap-2">
                        <button type="button" class="btn-primary" id="card-add-another-btn">
                            <i class="fa-solid fa-redo mr-2"></i>
                            Save &amp; Add Another
                        </button>
                        <button type="submit" class="btn-primary">
                            <span class="card-submit-label">Add Card</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Substitution Modal -->
<div id="addSubstitutionModal" class="modal" style="display:none;">
    <div class="modal-backdrop"></div>
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Add Substitution</h3>
                <button type="button" class="modal-close" data-close-modal="substitution">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <form id="addSubstitutionForm">
                <div class="modal-body space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">
                            Team <span class="text-rose-400">*</span>
                        </label>
                        <div class="flex gap-2">
                            <button type="button" class="team-toggle-btn" data-team-value="home" name="sub_team_side_btn">
                                <i class="fa-solid fa-house mr-2"></i>Home
                            </button>
                            <button type="button" class="team-toggle-btn" data-team-value="away" name="sub_team_side_btn">
                                <i class="fa-solid fa-arrow-right mr-2"></i>Away
                            </button>
                            <input type="hidden" name="team_side" id="sub-team-side" value="home">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">
                                Player ON <span class="text-rose-400">*</span>
                            </label>
                            <div id="sub-player-on-select-wrapper">
                                <!-- Populated dynamically -->
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">
                                Player OFF <span class="text-rose-400">*</span>
                            </label>
                            <div id="sub-player-off-select-wrapper">
                                <!-- Populated dynamically -->
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2" for="sub-minute">
                            Minute <span class="text-rose-400">*</span>
                        </label>
                        <input type="number" id="sub-minute" name="minute" required min="0" max="120"
                               class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white focus:border-blue-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">
                            Reason (optional)
                        </label>
                        <input type="hidden" id="sub-reason" name="reason" value="">
                        <div class="grid grid-cols-2 gap-2" id="sub-reason-buttons">
                            <button type="button" class="reason-toggle-btn" data-reason="tactical">Tactical</button>
                            <button type="button" class="reason-toggle-btn" data-reason="injury">Injury</button>
                            <button type="button" class="reason-toggle-btn" data-reason="fitness">Fitness</button>
                            <button type="button" class="reason-toggle-btn" data-reason="disciplinary">Disciplinary</button>
                        </div>
                    </div>

                    <div id="sub-form-error" class="hidden text-sm text-rose-400 p-3 bg-rose-900/20 rounded-lg border border-rose-700/50"></div>
                    <div id="sub-form-success" class="hidden text-sm text-green-400 p-3 bg-green-900/20 rounded-lg border border-green-700/50"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" data-close-modal="substitution">Cancel</button>
                    <div class="flex gap-2">
                        <button type="button" class="btn-primary" id="sub-add-another-btn">
                            <i class="fa-solid fa-redo mr-2"></i>
                            Save &amp; Add Another
                        </button>
                        <button type="submit" class="btn-primary">
                            <i class="fa-solid fa-repeat mr-2"></i>
                            Add Substitution
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Encode club players for JavaScript
$clubPlayersJson = json_encode(array_map(function($p) {
    return [
        'id' => (int)$p['id'],
        'name' => $p['first_name'] . ' ' . $p['last_name'],
        'is_active' => (int)$p['is_active'],
    ];
}, $clubPlayers));

$footerScripts .= "<script>window.clubPlayers = {$clubPlayersJson};</script>";

require __DIR__ . '/../../layout.php';
