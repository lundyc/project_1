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
$selectedClubId = (int)$match['club_id'];

$teams = get_teams_by_club($selectedClubId);
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

$homeTeamName = '';
$awayTeamName = '';
foreach ($teams as $team) {
    if ($team['id'] == $matchHomeId) $homeTeamName = $team['name'];
    if ($team['id'] == $matchAwayId) $awayTeamName = $team['name'];
}

// Video source info
$videoType = $match['video_source_type'] ?? 'upload';
$videoPath = $match['video_source_path'] ?? '';
$videoUrl = $match['video_source_url'] ?? '';
$downloadStatus = $match['video_download_status'] ?? '';
$downloadProgress = (int)($match['video_download_progress'] ?? 0);

$setupConfig = [
    'basePath' => $base,
    'clubId' => $selectedClubId,
    'matchId' => $matchId,
    'matchPlayers' => array_map(function($mp) {
        return [
            'id' => (int)$mp['id'],
            'player_id' => (int)($mp['player_id'] ?? 0),
            'player_name' => $mp['player_name'] ?? '',
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
    ],
];

$footerScripts = '<script>window.MatchEditConfig = ' . json_encode($setupConfig) . ';</script>';
$footerScripts .= '<script src="' . htmlspecialchars($base) . '/assets/js/match-edit.js?v=' . time() . '"></script>';

// Remove padding and background from layout wrapper for full-width page
$headExtras = '<style>
    .app-main { 
        padding: 0 !important; 
        background: transparent !important; 
        max-width: none !important;
    }
    .app-shell {
        max-width: none !important;
    }
</style>';

ob_start();
?>

<div class="w-full mt-4 text-slate-200">
    <div class="max-w-full">
        <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 px-4 md:px-6 mb-6">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-white">Edit Match</h1>
                    <p class="text-slate-400 text-sm">Manual entry for <?= htmlspecialchars($homeTeamName) ?> vs <?= htmlspecialchars($awayTeamName) ?>.</p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="<?= htmlspecialchars($base) ?>/matches" class="px-4 py-2 text-sm font-medium rounded-lg border bg-slate-800/40 border-white/10 text-slate-300 hover:bg-slate-700/50 hover:border-white/20 transition-all duration-200">← Back to matches</a>
                    <span class="px-3 py-1 rounded-full text-xs font-medium <?= $matchStatus === 'ready' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-slate-700 text-slate-300' ?>">
                        <?= htmlspecialchars(ucfirst($matchStatus)) ?>
                    </span>
                </div>
            </header>

            <div class="px-4 md:px-6 lg:px-8">
                <?php if ($error): ?>
                    <div class="mb-6 rounded-lg border border-rose-700/60 bg-rose-900/40 px-4 py-3 text-sm text-rose-200">
                        <i class="fa-solid fa-circle-exclamation mr-2"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php elseif ($success): ?>
                    <div class="mb-6 rounded-lg border border-emerald-600/60 bg-emerald-900/40 px-4 py-3 text-sm text-emerald-200">
                        <i class="fa-solid fa-circle-check mr-2"></i>
                        <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="grid grid-cols-12 gap-2 px-4 md:px-6 lg:px-8 w-full">
                <!-- Left Sidebar -->
                <aside class="col-span-2 space-y-4 min-w-0">
                    <nav class="flex flex-col gap-2 mb-3" role="tablist" aria-label="Match edit sections">
                        <button type="button" class="edit-nav-item active w-full text-left" data-section="details">
                            Match Details
                        </button>
                        <button type="button" class="edit-nav-item w-full text-left" data-section="video">
                            Video Source
                        </button>
                        <button type="button" class="edit-nav-item w-full text-left" data-section="lineups">
                            Player Lineups
                        </button>
                        <button type="button" class="edit-nav-item w-full text-left" data-section="events">
                            Match Events
                        </button>
                    </nav>
                </aside>

                <!-- Main Content -->
                <main class="col-span-7 space-y-4 min-w-0">
                <!-- Section 1: Match Details -->
                <section id="section-details" class="edit-section active">
                    <div class="rounded-xl bg-slate-900/50 border border-slate-800 overflow-hidden">
                        <div class="border-b border-slate-800 px-6 py-4">
                            <h2 class="text-lg font-semibold text-white">Match Details</h2>
                            <p class="text-sm text-slate-400 mt-1">Basic match information and competition details</p>
                        </div>
                        <div class="p-6">
                            <form method="post" action="<?= htmlspecialchars($base) ?>/api/matches/<?= $matchId ?>/update-details" class="space-y-6">
                                <input type="hidden" name="match_id" value="<?= $matchId ?>">
                                
                                <!-- Teams -->
                                <div class="space-y-4">
                                    <h3 class="text-sm font-semibold text-slate-300 uppercase tracking-wider">Teams</h3>
                                    <div class="grid gap-4 md:grid-cols-2">
                                        <div>
                                            <label class="block text-sm font-medium text-slate-300 mb-2" for="homeTeam">
                                                Home Team <span class="text-rose-400">*</span>
                                            </label>
                                            <select id="homeTeam" name="home_team_id" required class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2.5 text-sm text-white focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/40">
                                                <?php foreach ($teams as $team): ?>
                                                    <option value="<?= (int)$team['id'] ?>" <?= $matchHomeId == $team['id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($team['name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-slate-300 mb-2" for="awayTeam">
                                                Away Team <span class="text-rose-400">*</span>
                                            </label>
                                            <select id="awayTeam" name="away_team_id" required class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2.5 text-sm text-white focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/40">
                                                <?php foreach ($teams as $team): ?>
                                                    <option value="<?= (int)$team['id'] ?>" <?= $matchAwayId == $team['id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($team['name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Competition & Season -->
                                <div class="space-y-4">
                                    <h3 class="text-sm font-semibold text-slate-300 uppercase tracking-wider">Competition</h3>
                                    <div class="grid gap-4 md:grid-cols-2">
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

                                <!-- Match Info -->
                                <div class="space-y-4">
                                    <h3 class="text-sm font-semibold text-slate-300 uppercase tracking-wider">Match Information</h3>
                                    <div class="grid gap-4 md:grid-cols-2">
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
                                    <div class="grid gap-4 md:grid-cols-2">
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
                                                   placeholder="0" min="0" 
                                                   class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2.5 text-sm text-white placeholder-slate-500 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/40">
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
                                    <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                                        <i class="fa-solid fa-save mr-2"></i>
                                        Save Match Details
                                    </button>
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
                            <p class="text-sm text-slate-400 mt-1">Configure video source for match analysis</p>
                        </div>
                        <div class="p-6">
                            <div class="text-center py-12 text-slate-400">
                                <i class="fa-solid fa-video text-4xl mb-4 opacity-50"></i>
                                <p class="text-sm">Video source configuration coming soon</p>
                                <p class="text-xs mt-2">This will include VEO download and file upload options</p>
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
                                                <?php foreach ($homeStarters as $mp): ?>
                                                    <div class="lineup-player-card" data-match-player-id="<?= (int)$mp['id'] ?>">
                                                        <div class="flex items-center gap-3">
                                                            <div class="flex items-center gap-2">
                                                                <span class="lineup-shirt-number"><?= htmlspecialchars($mp['shirt_number'] ?? '—') ?></span>
                                                                <span class="lineup-position"><?= htmlspecialchars($mp['position_label'] ?? '—') ?></span>
                                                            </div>
                                                            <div class="flex-1 min-w-0">
                                                                <div class="text-sm font-medium text-white truncate"><?= htmlspecialchars($mp['player_name'] ?? 'Unknown') ?></div>
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
                                                                <div class="text-sm font-medium text-white truncate"><?= htmlspecialchars($mp['player_name'] ?? 'Unknown') ?></div>
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
                                                <?php foreach ($awayStarters as $mp): ?>
                                                    <div class="lineup-player-card" data-match-player-id="<?= (int)$mp['id'] ?>">
                                                        <div class="flex items-center gap-3">
                                                            <div class="flex items-center gap-2">
                                                                <span class="lineup-shirt-number"><?= htmlspecialchars($mp['shirt_number'] ?? '—') ?></span>
                                                                <span class="lineup-position"><?= htmlspecialchars($mp['position_label'] ?? '—') ?></span>
                                                            </div>
                                                            <div class="flex-1 min-w-0">
                                                                <div class="text-sm font-medium text-white truncate"><?= htmlspecialchars($mp['player_name'] ?? 'Unknown') ?></div>
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
                                                                <div class="text-sm font-medium text-white truncate"><?= htmlspecialchars($mp['player_name'] ?? 'Unknown') ?></div>
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
                                            if (empty($homeGoalsList)): ?>
                                                <div class="text-center py-8 text-slate-500 text-sm">
                                                    <p>No goals</p>
                                                </div>
                                            <?php else: ?>
                                                <?php foreach ($homeGoalsList as $goal): 
                                                    $goalPlayer = $goal['player_name'] ?? 'Unknown';
                                                    $goalMinute = $goal['minute'] ?? 0;
                                                    $goalMinuteExtra = $goal['minute_extra'] ?? 0;
                                                    $goalMinuteDisplay = $goalMinute . ($goalMinuteExtra > 0 ? "+{$goalMinuteExtra}" : '');
                                                    $goalMatchPlayerId = (int)($goal['match_player_id'] ?? 0);
                                                    $goalEventTypeId = (int)($goal['event_type_id'] ?? 0);
                                                ?>
                                                    <div class="rounded-lg bg-slate-800/40 border border-slate-700 p-3">
                                                        <div class="flex items-center gap-3">
                                                            <div class="flex-1 min-w-0">
                                                                <div class="text-sm font-semibold text-white truncate">
                                                                    <?= htmlspecialchars($goalPlayer) ?>
                                                                </div>
                                                                <div class="text-xs text-slate-400">
                                                                    <?= htmlspecialchars($goalMinuteDisplay) ?>'
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
                                                                    data-match-player-id="<?= $goalMatchPlayerId ?>">
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
                                            if (empty($awayGoalsList)): ?>
                                                <div class="text-center py-8 text-slate-500 text-sm">
                                                    <p>No goals</p>
                                                </div>
                                            <?php else: ?>
                                                <?php foreach ($awayGoalsList as $goal): 
                                                    $goalPlayer = $goal['player_name'] ?? 'Unknown';
                                                    $goalMinute = $goal['minute'] ?? 0;
                                                    $goalMinuteExtra = $goal['minute_extra'] ?? 0;
                                                    $goalMinuteDisplay = $goalMinute . ($goalMinuteExtra > 0 ? "+{$goalMinuteExtra}" : '');
                                                    $goalMatchPlayerId = (int)($goal['match_player_id'] ?? 0);
                                                    $goalEventTypeId = (int)($goal['event_type_id'] ?? 0);
                                                ?>
                                                    <div class="rounded-lg bg-slate-800/40 border border-slate-700 p-3">
                                                        <div class="flex items-center gap-3">
                                                            <div class="flex-1 min-w-0">
                                                                <div class="text-sm font-semibold text-white truncate">
                                                                    <?= htmlspecialchars($goalPlayer) ?>
                                                                </div>
                                                                <div class="text-xs text-slate-400">
                                                                    <?= htmlspecialchars($goalMinuteDisplay) ?>'
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
                                                                    data-match-player-id="<?= $goalMatchPlayerId ?>">
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
                                        <div class="space-y-2">
                                            <?php 
                                            $homeCardsList = array_filter($cards, fn($c) => $c['team_side'] === 'home');
                                            if (empty($homeCardsList)): ?>
                                                <div class="text-center py-8 text-slate-500 text-sm">
                                                    <p>No cards</p>
                                                </div>
                                            <?php else: ?>
                                                <?php foreach ($homeCardsList as $card): 
                                                    $cardPlayer = $card['player_name'] ?? 'Unknown';
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
                                        <div class="space-y-2">
                                            <?php 
                                            $awayCardsList = array_filter($cards, fn($c) => $c['team_side'] === 'away');
                                            if (empty($awayCardsList)): ?>
                                                <div class="text-center py-8 text-slate-500 text-sm">
                                                    <p>No cards</p>
                                                </div>
                                            <?php else: ?>
                                                <?php foreach ($awayCardsList as $card): 
                                                    $cardPlayer = $card['player_name'] ?? 'Unknown';
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
                                        <div class="space-y-2">
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
                                        <div class="space-y-2">
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
            <aside class="col-span-3 space-y-4 min-w-0">
                <div class="rounded-xl bg-slate-900/80 border border-white/10 p-4">
                    <h5 class="text-slate-200 font-semibold mb-1">Match Overview</h5>
                    <div class="text-slate-400 text-xs mb-4">Quick match statistics</div>
                    <div class="space-y-3">
                        <!-- Score Card -->
                        <article class="rounded-lg border border-white/10 bg-slate-800/40 px-3 py-3">
                            <div class="text-xs font-semibold text-slate-300 mb-2 text-center">Current Score</div>
                            <div class="text-center">
                                <div class="text-3xl font-bold text-slate-100">
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
                                    <div class="text-xl font-bold text-emerald-400"><?= count($goals) ?></div>
                                    <div class="text-[10px] text-slate-400">Goals</div>
                                </div>
                                <div>
                                    <div class="text-xl font-bold text-yellow-400"><?= count($yellowCards) ?></div>
                                    <div class="text-[10px] text-slate-400">Yellow</div>
                                </div>
                                <div>
                                    <div class="text-xl font-bold text-red-400"><?= count($redCards) ?></div>
                                    <div class="text-[10px] text-slate-400">Red</div>
                                </div>
                            </div>
                        </article>
                        
                        <div class="border-t border-white/10"></div>
                        
                        <!-- Substitutions -->
                        <article class="rounded-lg border border-white/10 bg-slate-800/40 px-3 py-3">
                            <div class="text-xs font-semibold text-slate-300 mb-2 text-center">Substitutions</div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-cyan-400"><?= count($substitutions) ?></div>
                                <div class="text-[10px] text-slate-400 mt-1">Total substitutions made</div>
                            </div>
                        </article>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>

<!-- Add Player Modal -->
<div id="addPlayerModal" class="modal" style="display:none;">
    <div class="modal-backdrop"></div>
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Add Player</h3>
                <button type="button" class="modal-close" data-close-modal>
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <form id="addPlayerForm">
                <div class="modal-body space-y-4">
                    <input type="hidden" id="player-team-side" name="team_side">
                    <input type="hidden" id="player-is-starting" name="is_starting">
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">
                            Player <span class="text-rose-400">*</span>
                        </label>
                        <div id="player-select-wrapper">
                            <!-- Will be populated dynamically -->
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2" for="player-shirt-number">
                                Shirt # <span class="text-rose-400">*</span>
                            </label>
                            <input type="number" id="player-shirt-number" name="shirt_number" required min="1" max="99"
                                   class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white focus:border-blue-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2" for="player-position">
                                Position <span class="text-rose-400">*</span>
                            </label>
                            <select id="player-position" name="position_label" required
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
                        <input type="checkbox" id="player-is-captain" name="is_captain" value="1"
                               class="w-4 h-4 rounded border-slate-700 bg-slate-800 text-blue-600 focus:ring-2 focus:ring-blue-500">
                        <label for="player-is-captain" class="text-sm text-slate-300">
                            <i class="fa-solid fa-star text-yellow-400 mr-1"></i>
                            Captain
                        </label>
                    </div>

                    <div id="player-form-error" class="hidden text-sm text-rose-400 p-3 bg-rose-900/20 rounded-lg border border-rose-700/50"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" data-close-modal>Cancel</button>
                    <button type="submit" class="btn-primary">
                        <i class="fa-solid fa-plus mr-2"></i>
                        Add Player
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Goal Modal -->
<div id="addGoalModal" class="modal" style="display:none;">
    <div class="modal-backdrop"></div>
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Add Goal</h3>
                <button type="button" class="modal-close" data-close-modal="goal">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <form id="addGoalForm">
                <input type="hidden" id="goal-event-id" name="event_id" value="">
                <input type="hidden" id="goal-event-type-id" name="event_type_id" value="16">
                <div class="modal-body space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">
                            Team <span class="text-rose-400">*</span>
                        </label>
                        <div class="flex gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="team_side" value="home" required class="w-4 h-4 text-blue-600 focus:ring-2 focus:ring-blue-500">
                                <span class="text-sm text-slate-300">Home</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="team_side" value="away" class="w-4 h-4 text-blue-600 focus:ring-2 focus:ring-blue-500">
                                <span class="text-sm text-slate-300">Away</span>
                            </label>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2" for="goal-minute">
                                Minute <span class="text-rose-400">*</span>
                            </label>
                            <input type="number" id="goal-minute" name="minute" required min="0" max="120"
                                   class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white focus:border-blue-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2" for="goal-minute-extra">
                                Extra Time
                            </label>
                            <input type="number" id="goal-minute-extra" name="minute_extra" min="0" max="15" placeholder="0"
                                   class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white focus:border-blue-500 focus:outline-none">
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
                        <div class="flex gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="team_side" value="home" required class="w-4 h-4 text-blue-600 focus:ring-2 focus:ring-blue-500">
                                <span class="text-sm text-slate-300">Home</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="team_side" value="away" class="w-4 h-4 text-blue-600 focus:ring-2 focus:ring-blue-500">
                                <span class="text-sm text-slate-300">Away</span>
                            </label>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2" for="card-minute">
                                Minute <span class="text-rose-400">*</span>
                            </label>
                            <input type="number" id="card-minute" name="minute" required min="0" max="120"
                                   class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white focus:border-blue-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2" for="card-minute-extra">
                                Extra Time
                            </label>
                            <input type="number" id="card-minute-extra" name="minute_extra" min="0" max="15" placeholder="0"
                                   class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white focus:border-blue-500 focus:outline-none">
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
                        <label class="block text-sm font-medium text-slate-300 mb-2" for="card-notes">
                            Notes
                        </label>
                        <input type="text" id="card-notes" name="notes" placeholder="Reason or context"
                               class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white placeholder-slate-500 focus:border-blue-500 focus:outline-none">
                    </div>

                    <div id="card-form-error" class="hidden text-sm text-rose-400 p-3 bg-rose-900/20 rounded-lg border border-rose-700/50"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" data-close-modal="card">Cancel</button>
                    <button type="submit" class="btn-primary">
                        <span class="card-submit-label">Add Card</span>
                    </button>
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
                        <div class="flex gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="team_side" value="home" required class="w-4 h-4 text-blue-600 focus:ring-2 focus:ring-blue-500">
                                <span class="text-sm text-slate-300">Home</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="team_side" value="away" class="w-4 h-4 text-blue-600 focus:ring-2 focus:ring-blue-500">
                                <span class="text-sm text-slate-300">Away</span>
                            </label>
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
                            Player OFF <span class="text-rose-400">*</span>
                        </label>
                        <div id="sub-player-off-select-wrapper">
                            <!-- Populated dynamically -->
                        </div>
                    </div>

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
                            Reason
                        </label>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="reason" value="tactical" class="w-4 h-4 text-blue-600">
                                <span class="text-sm text-slate-300">Tactical</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="reason" value="injury" class="w-4 h-4 text-blue-600">
                                <span class="text-sm text-slate-300">Injury</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="reason" value="fitness" class="w-4 h-4 text-blue-600">
                                <span class="text-sm text-slate-300">Fitness</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="reason" value="disciplinary" class="w-4 h-4 text-blue-600">
                                <span class="text-sm text-slate-300">Disciplinary</span>
                            </label>
                        </div>
                    </div>

                    <div id="sub-form-error" class="hidden text-sm text-rose-400 p-3 bg-rose-900/20 rounded-lg border border-rose-700/50"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" data-close-modal="substitution">Cancel</button>
                    <button type="submit" class="btn-primary">
                        <i class="fa-solid fa-repeat mr-2"></i>
                        Add Substitution
                    </button>
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
    ];
}, $clubPlayers));

$footerScripts .= "<script>window.clubPlayers = {$clubPlayersJson};</script>";

require __DIR__ . '/../../layout.php';
