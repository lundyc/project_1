<?php
$base = base_path();
require_once __DIR__ . '/../../../lib/csrf.php';
require_once __DIR__ . '/../../../lib/team_repository.php';
require_once __DIR__ . '/../../../lib/season_repository.php';
require_once __DIR__ . '/../../../lib/competition_repository.php';
require_once __DIR__ . '/../../../lib/club_repository.php';
require_once __DIR__ . '/../../../lib/player_repository.php';
require_once __DIR__ . '/../../../lib/asset_helper.php';

$user = current_user() ?? [];
$roles = $_SESSION['roles'] ?? [];
$isPlatformAdmin = in_array('platform_admin', $roles, true);

$availableClubs = $isPlatformAdmin ? get_all_clubs() : [];

$selectedClubId = 0;
if ($isPlatformAdmin) {
    $requestedClubId = isset($_GET['club_id']) && $_GET['club_id'] !== '' ? (int)$_GET['club_id'] : 0;
    $selectedClubId = $requestedClubId > 0 ? $requestedClubId : 0;
    if (!$selectedClubId && !empty($availableClubs)) {
        $selectedClubId = (int)($availableClubs[0]['id'] ?? 0);
    }
    if (!$selectedClubId) {
        $selectedClubId = (int)($user['club_id'] ?? 0);
    }
} else {
    $selectedClubId = (int)($user['club_id'] ?? 0);
}

if ($selectedClubId <= 0) {
    http_response_code(400);
    echo 'Club context required';
    exit;
}

$selectedClub = get_club_by_id($selectedClubId);
if (!$selectedClub) {
    http_response_code(404);
    echo 'Club not found';
    exit;
}

if (!$isPlatformAdmin) {
    $availableClubs = [$selectedClub];
}

$teams = get_teams_by_club($selectedClubId);
$seasons = get_seasons_by_club($selectedClubId);
$competitions = get_competitions_by_club($selectedClubId);
$clubPlayers = get_players_for_club($selectedClubId);
$homeTeamName = '';
$awayTeamName = '';
$videoType = 'none';
$videoPath = '';
$videoUrl = '';
$currentVideoLabel = 'None';
$substitutions = [];
$error = $_SESSION['match_form_error'] ?? null;
$success = $_SESSION['match_form_success'] ?? null;
unset($_SESSION['match_form_error']);
unset($_SESSION['match_form_success']);
// JS config for match-create.js (for create mode)
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
$footerScripts = '<script>window.MatchCreateConfig = ' . json_encode($setupConfig) . ';</script>';
// Filemtime-based versioning enables long-lived caching between updates.
$footerScripts .= '<script src="' . htmlspecialchars($base) . '/assets/js/match-create.js' . asset_version('/assets/js/match-create.js') . '"></script>';
$clubPlayersJson = json_encode(array_map(function($p) {
    return [
        'id' => (int)$p['id'],
        'name' => $p['first_name'] . ' ' . $p['last_name'],
        'is_active' => (int)$p['is_active'],
    ];
}, $clubPlayers));
$footerScripts .= "<script>window.clubPlayers = {$clubPlayersJson};</script>";
$headExtras = '';
// Extract the <style> block from edit.php for identical styling (shared styles)
$editPhp = file_get_contents(__DIR__ . '/edit.php'); // Still using edit.php for style extraction
$styleStart = strpos($editPhp, '<style>');
$styleEnd = strpos($editPhp, '</style>');
if ($styleStart !== false && $styleEnd !== false) {
    $headExtras .= substr($editPhp, $styleStart, $styleEnd - $styleStart + 8);
}
ob_start();


// Set header/title/description for Create Match
$matchTitle = 'Create Match';
$matchDescription = 'Create a new match.';
$clubContextName = $selectedClub['name'] ?? 'Saltcoats Victoria F.C.';
$showClubSelector = true;
include __DIR__ . '/../../partials/match_context_header.php';

?>
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
        <nav class="flex flex-col gap-2 mb-3" role="tablist" aria-label="Match create sections">
            <button type="button" class="create-nav-item active w-full text-left" data-section="details" data-section-num="1"
                    role="tab" aria-selected="true" aria-controls="section-details">
                Match Details
            </button>
            <button type="button" class="create-nav-item w-full text-left" data-section="video" data-section-num="2"
                    role="tab" aria-selected="false" aria-controls="section-video">
                Video Source
            </button>
            <button type="button" class="create-nav-item w-full text-left" data-section="lineups" data-section-num="3"
                    role="tab" aria-selected="false" aria-controls="section-lineups">
                Player Lineups
            </button>
            <button type="button" class="create-nav-item w-full text-left" data-section="events" data-section-num="4"
                    role="tab" aria-selected="false" aria-controls="section-events">
                Match Events
            </button>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 min-w-0 space-y-4 w-100">
        <!-- Section 1: Match Details -->
        <section id="section-details" class="create-section active">
<div class="rounded-xl bg-slate-900/50 border border-slate-800 overflow-hidden">
    <div class="border-b border-slate-800 px-6 py-4">
        <h2 class="text-lg font-semibold text-white">Match Details</h2>
        <p class="text-sm text-slate-400 mt-1">Basic match information and competition details</p>
    </div>
    <div class="p-6">
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

<form id="match-details-form" method="post" action="<?= htmlspecialchars($base) ?>/api/matches/create" class="space-y-6">
    <input type="hidden" name="club_id" value="<?= htmlspecialchars($selectedClubId) ?>">
    <div class="space-y-4 border-l-4 border-blue-500 pl-4 py-3 rounded-r bg-blue-500/5">
        <h3 class="text-sm font-semibold text-blue-400 uppercase tracking-wider flex items-center gap-2">
            <i class="fa-solid fa-users"></i> Teams
        </h3>
        <div class="grid gap-4 grid-cols-1 md:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2" for="homeTeam">
                    Home Team <span class="text-rose-400">*</span>
                </label>
                <select id="homeTeam" name="home_team_id" required class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2.5 text-sm text-white focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/40">
                    <option value="">Select home team</option>
                    <?php foreach ($teams as $team): ?>
                        <option value="<?= (int)$team['id'] ?>"><?= htmlspecialchars($team['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2" for="awayTeam">
                    Away Team <span class="text-rose-400">*</span>
                </label>
                <select id="awayTeam" name="away_team_id" required class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2.5 text-sm text-white focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/40">
                    <option value="">Select away team</option>
                    <?php foreach ($teams as $team): ?>
                        <option value="<?= (int)$team['id'] ?>"><?= htmlspecialchars($team['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
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
                        <option value="<?= (int)$season['id'] ?>"><?= htmlspecialchars($season['name']) ?></option>
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
                        <option value="<?= (int)$comp['id'] ?>"><?= htmlspecialchars($comp['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
    <div class="space-y-4 border-l-4 border-purple-500 pl-4 py-3 rounded-r bg-purple-500/5">
        <h3 class="text-sm font-semibold text-purple-400 uppercase tracking-wider flex items-center gap-2">
            <i class="fa-solid fa-calendar-days"></i> Match Information
        </h3>
        <div class="grid gap-4 grid-cols-1 md:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2" for="kickoff">
                    Kickoff Date & Time
                </label>
                <input type="datetime-local" id="kickoff" name="kickoff_at" value="" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2.5 text-sm text-white focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/40">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2" for="venue">
                    Venue
                </label>
                <input type="text" id="venue" name="venue" value="" placeholder="Stadium name" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2.5 text-sm text-white placeholder-slate-500 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/40">
            </div>
        </div>
        <div class="grid gap-4 grid-cols-1 md:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2" for="referee">
                    Referee
                </label>
                <input type="text" id="referee" name="referee" value="" placeholder="Referee name" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2.5 text-sm text-white placeholder-slate-500 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/40">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2" for="attendance">
                    Attendance
                </label>
                <input type="number" id="attendance" name="attendance" value="" placeholder="0" min="0" max="1000000" aria-describedby="attendance-help" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2.5 text-sm text-white placeholder-slate-500 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/40">
                <p id="attendance-help" class="text-xs text-slate-500 mt-1">Optional. Enter number of spectators.</p>
            </div>
        </div>
    </div>
    <div class="space-y-4">
        <h3 class="text-sm font-semibold text-slate-300 uppercase tracking-wider">Status</h3>
        <div>
            <label class="block text-sm font-medium text-slate-300 mb-2" for="status">
                Match Status
            </label>
            <select id="status" name="status" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2.5 text-sm text-white focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/40">
                <option value="draft" selected>Draft</option>
                <option value="ready">Ready</option>
            </select>
        </div>
    </div>
    <div class="flex justify-end pt-4 border-t border-slate-800">
        <button type="submit" class="match-details-submit px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors shadow-lg hover:shadow-blue-500/20 flex items-center gap-2">
            <i class="fa-solid fa-plus mr-2"></i> Create Match
        </button>
    </div>

</form>
    </div>
</div>
        </section>
        <!-- Section 2: Video Source -->
        <section id="section-video" class="create-section" style="display:none;">
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

                    <div class="grid md:grid-cols-2 gap-6" id="videoInputsSection" <?= $videoType === 'none' ? 'style=\"display:none;\"' : '' ?> >
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
        <section id="section-lineups" class="create-section" style="display:none;">
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
                                    <button type="button" class="text-xs font-semibold text-blue-400 hover:text-blue-300 uppercase tracking-wider" disabled>
                                        <i class="fa-solid fa-plus mr-1"></i> Add
                                    </button>
                                </div>
                                <div id="home-starters" class="space-y-2 min-h-[100px]">
                                    <div class="text-center py-6 text-slate-500 text-sm border-2 border-dashed border-slate-700 rounded-lg">
                                        No starting players added yet
                                    </div>
                                </div>
                            </div>
                            <!-- Substitutes -->
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-sm font-medium text-slate-300 uppercase tracking-wider">Substitutes</h4>
                                    <button type="button" class="text-xs font-semibold text-blue-400 hover:text-blue-300 uppercase tracking-wider" disabled>
                                        <i class="fa-solid fa-plus mr-1"></i> Add
                                    </button>
                                </div>
                                <div id="home-subs" class="space-y-2 min-h-[60px]">
                                    <div class="text-center py-4 text-slate-500 text-sm border-2 border-dashed border-slate-700 rounded-lg">
                                        No substitutes added yet
                                    </div>
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
                                    <button type="button" class="text-xs font-semibold text-blue-400 hover:text-blue-300 uppercase tracking-wider" disabled>
                                        <i class="fa-solid fa-plus mr-1"></i> Add
                                    </button>
                                </div>
                                <div id="away-starters" class="space-y-2 min-h-[100px]">
                                    <div class="text-center py-6 text-slate-500 text-sm border-2 border-dashed border-slate-700 rounded-lg">
                                        No starting players added yet
                                    </div>
                                </div>
                            </div>
                            <!-- Substitutes -->
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-sm font-medium text-slate-300 uppercase tracking-wider">Substitutes</h4>
                                    <button type="button" class="text-xs font-semibold text-blue-400 hover:text-blue-300 uppercase tracking-wider" disabled>
                                        <i class="fa-solid fa-plus mr-1"></i> Add
                                    </button>
                                </div>
                                <div id="away-subs" class="space-y-2 min-h-[60px]">
                                    <div class="text-center py-4 text-slate-500 text-sm border-2 border-dashed border-slate-700 rounded-lg">
                                        No substitutes added yet
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- Section 4: Match Events -->
        <section id="section-events" class="create-section" style="display:none;">
            <div class="rounded-xl bg-slate-900/50 border border-slate-800 overflow-hidden">
                <div class="border-b border-slate-800 px-6 py-4">
                    <h2 class="text-lg font-semibold text-white">Match Events</h2>
                    <p class="text-sm text-slate-400 mt-1">Record goals, cards, and substitutions</p>
                </div>
                <!-- Event Tabs -->
                <div class="border-b border-slate-800 bg-slate-900/30">
                    <div class="flex px-6">
                        <button type="button" class="event-tab active" data-tab="goals" disabled>
                            <i class="fa-solid fa-futbol mr-2"></i>
                            Goals
                        </button>
                        <button type="button" class="event-tab" data-tab="cards" disabled>
                            <i class="fa-solid fa-square mr-2 text-yellow-500"></i>
                            Cards
                        </button>
                        <button type="button" class="event-tab" data-tab="substitutions" disabled>
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
                            <button type="button" class="btn-primary text-sm" data-add-goal disabled>
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
                                    <span class="text-2xl font-bold text-blue-400">0</span>
                                </div>
                                <div class="space-y-2">
                                    <div class="text-center py-8 text-slate-500 text-sm">
                                        <p>No goals</p>
                                    </div>
                                </div>
                            </div>
                            <!-- Away Team Goals -->
                            <div>
                                <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-700">
                                    <h4 class="text-sm font-semibold text-slate-400">
                                        <?= htmlspecialchars($awayTeamName) ?>
                                    </h4>
                                    <span class="text-2xl font-bold text-slate-400">0</span>
                                </div>
                                <div class="space-y-2">
                                    <div class="text-center py-8 text-slate-500 text-sm">
                                        <p>No goals</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Cards Tab -->
                    <div id="tab-cards" class="event-tab-content" style="display:none;">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-white">Disciplinary Cards</h3>
                            <div class="flex gap-2">
                                <button type="button" class="btn-secondary text-sm" data-add-card="yellow" disabled>
                                    <i class="fa-solid fa-square text-yellow-500 mr-2"></i>
                                    Add Yellow
                                </button>
                                <button type="button" class="btn-secondary text-sm" data-add-card="red" disabled>
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
                                    <div class="text-center py-8 text-slate-500 text-sm card-empty" data-team="home">
                                        <p>No cards</p>
                                    </div>
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
                                    <div class="text-center py-8 text-slate-500 text-sm card-empty" data-team="away">
                                        <p>No cards</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Substitutions Tab -->
                    <div id="tab-substitutions" class="event-tab-content" style="display:none;">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-white">Substitutions</h3>
                            <button type="button" class="btn-primary text-sm" data-add-substitution disabled>
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
                                    <div class="text-center py-8 text-slate-500 text-sm">
                                        <i class="fa-solid fa-repeat opacity-30 mb-2"></i>
                                        <p>No substitutions</p>
                                    </div>
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
                                    <div class="text-center py-8 text-slate-500 text-sm">
                                        <i class="fa-solid fa-repeat opacity-30 mb-2"></i>
                                        <p>No substitutions</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
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
                    aria-label="Create match">
                <i class="fa-solid fa-plus"></i>
                <span class="submit-text">Create Match</span>
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
            <div class="modal-header">
                <h3 id="add-player-modal-title" class="modal-title">Add Player</h3>
                <button type="button" class="modal-close" data-close-modal aria-label="Close dialog (press ESC)" title="Close (ESC)">
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
                        <label class="block text-sm font-medium text-slate-300 mb-2">Player</label>
                        <div id="player-select-wrapper" class="w-full"></div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div></div>
                        <div></div>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="hidden" id="player-is-captain" name="is_captain" value="0">
                        <button type="button" id="captain-toggle-btn" class="flex items-center gap-2 px-3 py-2 rounded-lg border border-slate-700 bg-slate-800 hover:bg-slate-700 transition-colors text-sm text-slate-300">
                            <i class="fa-solid fa-star"></i> Captain
                        </button>
                        <span class="text-xs text-slate-500">(Click star to set as captain)</span>
                    </div>
                    <div id="player-form-error" class="hidden text-sm text-rose-400 p-3 bg-rose-900/20 rounded-lg border border-rose-700/50"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" data-close-modal>Cancel</button>
                    <div class="flex gap-2">
                        <button type="submit" class="btn-primary" id="add-player-btn">Add Player</button>
                        <button type="button" class="btn-primary" id="add-another-btn" style="background-color: var(--accent-info);">Add & Another</button>
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
                        <div></div>
                        <div></div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Position</label>
                        <select id="new-player-position" name="primary_position">
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
                        <label class="block text-sm font-medium text-slate-300 mb-2">Team <span class="text-rose-400">*</span></label>
                        <div class="flex gap-2">
                            <button type="button" class="team-toggle-btn" data-team-value="home" name="team_side_btn"></button>
                            <button type="button" class="team-toggle-btn" data-team-value="away" name="team_side_btn"></button>
                            <input type="hidden" name="team_side" id="goal-team-side" value="home">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Scorer <span class="text-rose-400">*</span></label>
                        <div id="goal-player-select-wrapper"></div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2" for="goal-minute">Minute <span class="text-rose-400">*</span></label>
                        <input type="number" id="goal-minute" name="minute" required min="0" max="120"
                               class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white focus:border-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Goal Type</label>
                        <div class="flex gap-2">
                            <button type="button" class="team-toggle-btn" data-goal-type="own_goal" name="goal_type_btn"></button>
                            <button type="button" class="team-toggle-btn" data-goal-type="penalty" name="goal_type_btn"></button>
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
                        <label class="block text-sm font-medium text-slate-300 mb-2">Team <span class="text-rose-400">*</span></label>
                        <div class="flex gap-2">
                            <button type="button" class="team-toggle-btn" data-team-value="home" name="team_side_btn"></button>
                            <button type="button" class="team-toggle-btn" data-team-value="away" name="team_side_btn"></button>
                            <input type="hidden" name="team_side" id="card-team-side" value="home">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Player <span class="text-rose-400">*</span></label>
                        <div id="card-player-select-wrapper"></div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2" for="card-minute">Minute <span class="text-rose-400">*</span></label>
                        <input type="number" id="card-minute" name="minute" required min="0" max="120"
                               class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white focus:border-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2" for="card-notes">Notes</label>
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
                        <label class="block text-sm font-medium text-slate-300 mb-2">Team <span class="text-rose-400">*</span></label>
                        <div class="flex gap-2">
                            <button type="button" class="team-toggle-btn" data-team-value="home" name="sub_team_side_btn"></button>
                            <button type="button" class="team-toggle-btn" data-team-value="away" name="sub_team_side_btn"></button>
                            <input type="hidden" name="team_side" id="sub-team-side" value="home">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Player On</label>
                            <div id="sub-player-on-select-wrapper"></div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Player Off</label>
                            <div id="sub-player-off-select-wrapper"></div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2" for="sub-minute">Minute <span class="text-rose-400">*</span></label>
                        <input type="number" id="sub-minute" name="minute" required min="0" max="120"
                               class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white focus:border-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Reason (optional)</label>
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
                        <button type="button" class="btn-primary" id="sub-add-another-btn">Add & Another</button>
                        <button type="submit" class="btn-primary">Add Substitution</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layout.php';
