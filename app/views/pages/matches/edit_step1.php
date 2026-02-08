<?php
require_auth();
require_once __DIR__ . '/../../../lib/match_permissions.php';
require_once __DIR__ . '/../../../lib/team_repository.php';
require_once __DIR__ . '/../../../lib/season_repository.php';
require_once __DIR__ . '/../../../lib/competition_repository.php';
require_once __DIR__ . '/../../../lib/club_repository.php';
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
$selectedClubId = (int)$match['club_id'];

$teams = get_teams_by_club($selectedClubId);
$seasons = get_seasons_by_club($selectedClubId);
$competitions = get_competitions_by_club($selectedClubId);

$error = $_SESSION['match_form_error'] ?? null;
$success = $_SESSION['match_form_success'] ?? null;
unset($_SESSION['match_form_error']);
unset($_SESSION['match_form_success']);

$title = 'Edit Match - Details';
$kickoffValue = !empty($match['kickoff_at']) ? date('Y-m-d\TH:i', strtotime($match['kickoff_at'])) : '';
$matchSeasonId = $match['season_id'] ?? null;
$matchCompetitionId = $match['competition_id'] ?? null;
$matchHomeId = (int)($match['home_team_id'] ?? 0);
$matchAwayId = (int)($match['away_team_id'] ?? 0);
$matchVenue = (string)($match['venue'] ?? '');
$matchReferee = (string)($match['referee'] ?? '');
$matchAttendance = $match['attendance'] ?? null;
$matchStatus = $match['status'] ?? 'draft';

$setupConfig = [
    'basePath' => $base,
    'clubId' => $selectedClubId,
    'endpoints' => [
        'teamCreate' => $base . '/api/teams/create-json',
        'seasonCreate' => $base . '/api/seasons/create',
        'competitionCreate' => $base . '/api/competitions/create',
    ],
];

$footerScripts = '<script>window.MatchWizardSetupConfig = ' . json_encode($setupConfig) . ';</script>';
// Filemtime-based versioning enables long-lived caching between updates.
$footerScripts .= '<script src="' . htmlspecialchars($base) . '/assets/js/match-setup.js' . asset_version('/assets/js/match-setup.js') . '"></script>';

ob_start();
?>

<div class="bg-slate-950 min-h-screen py-10">
    <div class="max-w-5xl mx-auto space-y-6 px-4">
        <div class="flex items-center justify-between">
            <a href="<?= htmlspecialchars($base) ?>/matches" class="text-sm font-medium text-slate-400 hover:text-slate-200 flex items-center gap-2">
                <span aria-hidden="true">←</span>
                Back to matches
            </a>
        </div>

        <header class="space-y-2">
            <h1 class="text-3xl font-semibold text-white">Edit Match</h1>
            <p class="text-sm text-slate-400">Update match details and competition information</p>
        </header>

        <?php if ($error): ?>
            <div class="rounded-2xl border border-rose-700/60 bg-rose-900/60 px-4 py-3 text-sm text-rose-100">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php elseif ($success): ?>
            <div class="rounded-2xl border border-emerald-600/60 bg-emerald-900/60 px-4 py-3 text-sm text-emerald-100">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= htmlspecialchars($base) ?>/api/matches/<?= $matchId ?>/update-details">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(get_csrf_token()) ?>">
            <input type="hidden" name="match_id" value="<?= $matchId ?>">
            <div class="space-y-6 pb-32">
                <section class="bg-slate-900 border border-slate-800 rounded-xl p-6 space-y-6">
                    <div class="flex items-center justify-between">
                        <p class="text-xs uppercase tracking-[0.4em] text-slate-500">Teams</p>
                    </div>
                    <div class="grid gap-6 lg:grid-cols-2">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <label class="text-sm font-semibold text-slate-200" for="homeTeam">Home Team</label>
                                <button type="button" data-add-team="home" class="text-xs font-semibold uppercase tracking-[0.3em] text-blue-400 hover:text-blue-300 transition-colors">
                                    + Add new
                                </button>
                            </div>
                            <select id="homeTeam" name="home_team_id" required class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-3 text-sm text-white focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/40">
                                <?php foreach ($teams as $team): ?>
                                    <option value="<?= (int)$team['id'] ?>" <?= $matchHomeId == $team['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($team['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <label class="text-sm font-semibold text-slate-200" for="awayTeam">Away Team</label>
                                <button type="button" data-add-team="away" class="text-xs font-semibold uppercase tracking-[0.3em] text-blue-400 hover:text-blue-300 transition-colors">
                                    + Add new
                                </button>
                            </div>
                            <select id="awayTeam" name="away_team_id" required class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-3 text-sm text-white focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/40">
                                <?php foreach ($teams as $team): ?>
                                    <option value="<?= (int)$team['id'] ?>" <?= $matchAwayId == $team['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($team['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </section>

                <section class="bg-slate-900 border border-slate-800 rounded-xl p-6 space-y-6">
                    <div class="flex items-center justify-between">
                        <p class="text-xs uppercase tracking-[0.4em] text-slate-500">Match Details</p>
                    </div>
                    <div class="grid gap-6 lg:grid-cols-3">
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-slate-200" for="kickoff">Kickoff</label>
                            <input id="kickoff" type="datetime-local" name="kickoff_at" value="<?= htmlspecialchars($kickoffValue) ?>" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-3 text-sm text-white focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/40">
                            <p class="text-xs text-slate-500">Local time</p>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-slate-200" for="status">Status</label>
                            <select id="status" name="status" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-3 text-sm text-white focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/40">
                                <option value="draft" <?= $matchStatus === 'draft' ? 'selected' : '' ?>>Draft</option>
                                <option value="ready" <?= $matchStatus === 'ready' ? 'selected' : '' ?>>Ready</option>
                            </select>
                            <p class="text-xs text-slate-500">Mark as ready when complete</p>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-slate-200" for="attendance">Attendance</label>
                            <input id="attendance" type="number" name="attendance" min="0" step="1" value="<?= $matchAttendance !== null ? htmlspecialchars((string)$matchAttendance) : '' ?>" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-3 text-sm text-white focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/40" placeholder="0">
                        </div>
                        <div class="lg:col-span-3 space-y-2">
                            <label class="text-sm font-semibold text-slate-200" for="referee">Referee</label>
                            <input id="referee" type="text" name="referee" placeholder="Referee name" value="<?= htmlspecialchars($matchReferee) ?>" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-3 text-sm text-white focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/40">
                        </div>
                    </div>
                </section>

                <section class="bg-slate-900 border border-slate-800 rounded-xl p-6 space-y-6">
                    <div class="flex items-center justify-between">
                        <p class="text-xs uppercase tracking-[0.4em] text-slate-500">Competition</p>
                        <span class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Optional</span>
                    </div>
                    <div class="grid gap-6 lg:grid-cols-3">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <label class="text-sm font-semibold text-slate-200" for="season">Season</label>
                                <button type="button" data-add-season class="text-xs font-semibold uppercase tracking-[0.3em] text-blue-400 hover:text-blue-300 transition-colors">
                                    + Add
                                </button>
                            </div>
                            <select id="season" name="season_id" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-3 text-sm text-white focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/40">
                                <option value="">None</option>
                                <?php foreach ($seasons as $season): ?>
                                    <option value="<?= (int)$season['id'] ?>" <?= $matchSeasonId == $season['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($season['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <label class="text-sm font-semibold text-slate-200" for="competition">Competition</label>
                                <button type="button" data-add-competition class="text-xs font-semibold uppercase tracking-[0.3em] text-blue-400 hover:text-blue-300 transition-colors">
                                    + Add
                                </button>
                            </div>
                            <select id="competition" name="competition_id" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-3 text-sm text-white focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/40">
                                <option value="">None</option>
                                <?php foreach ($competitions as $competition): ?>
                                    <option value="<?= (int)$competition['id'] ?>" <?= $matchCompetitionId == $competition['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($competition['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="space-y-2 lg:col-span-3">
                            <label class="text-sm font-semibold text-slate-200" for="venue">Venue</label>
                            <input id="venue" type="text" name="venue" placeholder="e.g., Home Park" value="<?= htmlspecialchars($matchVenue) ?>" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-3 text-sm text-white focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/40">
                        </div>
                    </div>
                </section>
            </div>

            <div class="sticky bottom-0 left-0 right-0 border-t border-slate-800 bg-slate-950/90 backdrop-blur px-4 py-4">
                <div class="max-w-5xl mx-auto flex items-center justify-between gap-4">
                    <a href="<?= htmlspecialchars($base) ?>/matches/<?= $matchId ?>/video" class="text-sm font-semibold text-blue-400 hover:text-blue-300">
                        Edit video source →
                    </a>
                    <div class="flex items-center gap-3">
                        <a href="<?= htmlspecialchars($base) ?>/matches" class="rounded-full border border-slate-800 px-4 py-2 text-sm font-semibold text-slate-300 hover:border-slate-600 hover:text-white transition-colors">
                            Cancel
                        </a>
                        <button type="submit" name="action" value="save" class="rounded-full bg-blue-600 px-6 py-2 text-sm font-semibold text-white transition hover:bg-blue-500">
                            Save changes
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Setup Modals -->
<div id="setupModals">
    <div id="teamCreateModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" role="dialog" aria-hidden="true">
        <div class="w-full max-w-md rounded-2xl border border-slate-800 bg-slate-900 p-6">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs uppercase tracking-[0.4em] text-slate-500">Create team</p>
                    <p class="text-base font-semibold text-white">Add to club roster</p>
                </div>
                <button type="button" class="text-slate-400 hover:text-white" data-setup-close-modal="teamCreateModal" aria-label="Close modal">×</button>
            </div>
            <form id="teamCreateForm" class="mt-4 space-y-4">
                <input type="hidden" name="club_id" value="<?= (int)$selectedClubId ?>">
                <div>
                    <label class="text-sm font-medium text-slate-200" for="teamNameInput">Team name</label>
                    <input type="text" id="teamNameInput" name="name" class="mt-1 w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-3 text-sm text-white focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/40" required>
                </div>
                <div>
                    <p id="teamCreateError" class="hidden text-sm text-rose-400"></p>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" class="rounded-full border border-slate-800 px-4 py-2 text-sm font-semibold text-slate-300 hover:border-slate-600 hover:text-white" data-setup-close-modal="teamCreateModal">Cancel</button>
                    <button type="submit" class="rounded-full bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500">Create team</button>
                </div>
            </form>
        </div>
    </div>

    <div id="seasonCreateModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" role="dialog" aria-hidden="true">
        <div class="w-full max-w-md rounded-2xl border border-slate-800 bg-slate-900 p-6">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs uppercase tracking-[0.4em] text-slate-500">Create season</p>
                    <p class="text-base font-semibold text-white">Add season context</p>
                </div>
                <button type="button" class="text-slate-400 hover:text-white" data-setup-close-modal="seasonCreateModal" aria-label="Close modal">×</button>
            </div>
            <form id="seasonCreateForm" class="mt-4 space-y-4">
                <input type="hidden" name="club_id" value="<?= (int)$selectedClubId ?>">
                <div>
                    <label class="text-sm font-medium text-slate-200" for="seasonNameInput">Season name</label>
                    <input type="text" id="seasonNameInput" name="name" class="mt-1 w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-3 text-sm text-white focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/40" required>
                </div>
                <div>
                    <p id="seasonCreateError" class="hidden text-sm text-rose-400"></p>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" class="rounded-full border border-slate-800 px-4 py-2 text-sm font-semibold text-slate-300 hover:border-slate-600 hover:text-white" data-setup-close-modal="seasonCreateModal">Cancel</button>
                    <button type="submit" class="rounded-full bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500">Create season</button>
                </div>
            </form>
        </div>
    </div>

    <div id="competitionCreateModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" role="dialog" aria-hidden="true">
        <div class="w-full max-w-md rounded-2xl border border-slate-800 bg-slate-900 p-6">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs uppercase tracking-[0.4em] text-slate-500">Create competition</p>
                    <p class="text-base font-semibold text-white">Add a competition</p>
                </div>
                <button type="button" class="text-slate-400 hover:text-white" data-setup-close-modal="competitionCreateModal" aria-label="Close modal">×</button>
            </div>
            <form id="competitionCreateForm" class="mt-4 space-y-4">
                <input type="hidden" name="club_id" value="<?= (int)$selectedClubId ?>">
                <div>
                    <label class="text-sm font-medium text-slate-200" for="competitionNameInput">Competition name</label>
                    <input type="text" id="competitionNameInput" name="name" class="mt-1 w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-3 text-sm text-white focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/40" required>
                </div>
                <div>
                    <p id="competitionCreateError" class="hidden text-sm text-rose-400"></p>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" class="rounded-full border border-slate-800 px-4 py-2 text-sm font-semibold text-slate-300 hover:border-slate-600 hover:text-white" data-setup-close-modal="competitionCreateModal">Cancel</button>
                    <button type="submit" class="rounded-full bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500">Create competition</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../layout.php';
