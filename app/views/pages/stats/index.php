<?php
require_auth();

$base = base_path();
$title = 'Statistics Dashboard';
$selectedClubId = $selectedClubId ?? 0;
$selectedClub = $selectedClub ?? null;
$availableClubs = $availableClubs ?? [];
$isPlatformAdmin = user_has_role('platform_admin');
$clubContextName = $selectedClub['name'] ?? 'Club';
$showClubSelector = $isPlatformAdmin && !empty($availableClubs);
$matches = $matches ?? [];

$overviewMetrics = [
    [
        'key' => 'total_matches',
        'label' => 'Matches',
        'description' => 'Matches played',
        'icon' => 'fa-calendar-check',
        'icon_color' => 'text-primary',
        'format' => 'integer',
    ],
    [
        'key' => 'wins',
        'label' => 'Wins',
        'description' => 'Matches won',
        'icon' => 'fa-trophy',
        'icon_color' => 'text-success',
        'format' => 'integer',
    ],
    [
        'key' => 'draws',
        'label' => 'Draws',
        'description' => 'Matches drawn',
        'icon' => 'fa-handshake-alt',
        'icon_color' => 'text-warning',
        'format' => 'integer',
    ],
    [
        'key' => 'losses',
        'label' => 'Losses',
        'description' => 'Matches lost',
        'icon' => 'fa-face-sad-tear',
        'icon_color' => 'text-danger',
        'format' => 'integer',
    ],
    [
        'key' => 'goals_for',
        'label' => 'Goals For',
        'description' => 'Goals scored',
        'icon' => 'fa-bullseye',
        'icon_color' => 'text-success',
        'format' => 'integer',
    ],
    [
        'key' => 'goals_against',
        'label' => 'Goals Against',
        'description' => 'Goals conceded',
        'icon' => 'fa-bullseye',
        'icon_color' => 'text-danger',
        'format' => 'integer',
    ],
    [
        'key' => 'goal_difference',
        'label' => 'Goal Difference',
        'description' => 'GF − GA',
        'icon' => 'fa-arrows-left-right',
        'icon_color' => 'text-info',
        'format' => 'integer',
        'direction' => 'signed',
    ],
    [
        'key' => 'clean_sheets',
        'label' => 'Clean Sheets',
        'description' => 'Shutouts',
        'icon' => 'fa-shield-halved',
        'icon_color' => 'text-secondary',
        'format' => 'integer',
    ],
    [
        'key' => 'average_goals_per_game',
        'label' => 'Avg. goals/game',
        'description' => 'Goals per match',
        'icon' => 'fa-chart-line',
        'icon_color' => 'text-primary',
        'format' => 'decimal',
    ],
];

$statDefinitions = [];
foreach ($overviewMetrics as $metric) {
    $statDefinitions[$metric['key']] = [
        'format' => $metric['format'] ?? 'integer',
        'direction' => $metric['direction'] ?? 'positive',
    ];
}

$formatMatchScore = function (array $match): ?string {
    $pairs = [
        ['home_score', 'away_score'],
        ['home_team_score', 'away_team_score'],
        ['home_goals', 'away_goals'],
        ['home_team_goals', 'away_team_goals'],
    ];

    foreach ($pairs as [$homeKey, $awayKey]) {
        if (array_key_exists($homeKey, $match) && array_key_exists($awayKey, $match)) {
            $homeValue = $match[$homeKey];
            $awayValue = $match[$awayKey];
            if ($homeValue !== null && $awayValue !== null && $homeValue !== '' && $awayValue !== '') {
                return sprintf('%s - %s', $homeValue, $awayValue);
            }
        }
    }

    return null;
};

$formatMatchDate = function (array $match): string {
    $kickoffAt = $match['kickoff_at'] ?? null;
    if ($kickoffAt) {
        try {
            $dt = new DateTime($kickoffAt);
            return $dt->format('j M Y');
        } catch (Exception $e) {
        }
    }
    return 'TBD';
};

$formatMatchTime = function (array $match): string {
    $kickoffAt = $match['kickoff_at'] ?? null;
    if ($kickoffAt) {
        try {
            $dt = new DateTime($kickoffAt);
            return $dt->format('H:i');
        } catch (Exception $e) {
        }
    }
    return 'TBD';
};

$normalizeStatusClass = static function (string $status): string {
    $clean = preg_replace('/[^a-z0-9_-]+/i', '', strtolower($status));
    return $clean ?: 'status';
};

$formatStatusLabel = static function (string $status): string {
    if ($status === '') {
        return 'Unknown';
    }
    return ucwords(str_replace('_', ' ', $status));
};

ob_start();
?>
<div class="w-full mt-4 text-slate-200">
    <div class="max-w-full">
        <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 px-4 md:px-6 mb-6">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold tracking-tight">Statistics Dashboard</h1>
                <p class="text-slate-400 text-sm">View club-wide analytics and performance indicators.</p>
            </div>
            <div class="flex items-center gap-3">
                <p class="text-slate-400 text-xs">
                    Viewing stats for <span class="font-semibold text-slate-200"><?= htmlspecialchars($clubContextName) ?></span>.
                </p>
                <?php if ($showClubSelector): ?>
                    <div>
                        <label for="stats-club-selector" class="block text-slate-400 text-xs mb-1">Switch club context</label>
                        <select id="stats-club-selector" class="block w-48 rounded-md bg-slate-900/60 border border-white/20 px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-white/30">
                            <?php foreach ($availableClubs as $club): ?>
                                <option value="<?= (int)$club['id'] ?>" <?= (int)$club['id'] === $selectedClubId ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($club['name'] ?? 'Club') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
            </div>
        </header>

        <div class="grid grid-cols-12 gap-2 px-4 md:px-6 lg:px-8 w-full">
            <aside class="col-span-2 space-y-4 min-w-0">
                <nav class="flex flex-col gap-2 mb-3" role="tablist" aria-label="Statistics tabs">
                    <?php $tabs = [
                        'overview' => 'Overview',
                        'team-performance' => 'Team Performance',
                        'player-performance' => 'Player Performance',
                    ]; ?>
                    <?php foreach ($tabs as $tabId => $tabLabel): ?>
                        <button
                            type="button"
                            class="stats-tab w-full text-left px-4 py-2.5 text-sm font-medium rounded-lg border transition-all duration-200 <?= $tabId === 'overview' ? 'bg-indigo-600 border-indigo-500 text-white shadow-lg shadow-indigo-500/20' : 'bg-slate-800/40 border-white/10 text-slate-300 hover:bg-slate-700/50 hover:border-white/20' ?>"
                            role="tab"
                            aria-selected="<?= $tabId === 'overview' ? 'true' : 'false' ?>"
                            data-tab-id="<?= htmlspecialchars($tabId) ?>">
                            <?= htmlspecialchars($tabLabel) ?>
                        </button>
                    <?php endforeach; ?>
                </nav>
                <div class="space-y-4">
                    <div class="rounded-xl bg-slate-900/80 border border-white/10 p-3" data-filter-group="overview">
                        <h6 class="text-slate-300 text-xs font-semibold mb-2">Filters</h6>
                        <div class="flex flex-col gap-3">
                            <div>
                                <label class="block text-slate-400 text-xs mb-1">Season</label>
                                <select id="overview-season-filter" class="block w-full rounded-md bg-slate-900/60 border border-white/20 px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-white/30">
                                    <option value="">All Seasons</option>
                                    <?php foreach ($seasons ?? [] as $season): ?>
                                        <option value="<?= htmlspecialchars((string)$season['id']) ?>">
                                            <?= htmlspecialchars($season['name'] ?? 'Season ' . $season['id']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-slate-400 text-xs mb-1">Competition Type</label>
                                <select id="overview-type-filter" class="block w-full rounded-md bg-slate-900/60 border border-white/20 px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-white/30">
                                    <option value="">Both League &amp; Cup</option>
                                    <option value="league">League Only</option>
                                    <option value="cup">Cup Only</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-xl bg-slate-900/80 border border-white/10 p-3" data-filter-group="team-performance" style="display:none;">
                        <h6 class="text-slate-300 text-xs font-semibold mb-2">Filters</h6>
                        <div class="flex flex-col gap-3">
                            <div>
                                <label class="block text-slate-400 text-xs mb-1">Season</label>
                                <select id="team-performance-season-filter" class="block w-full rounded-md bg-slate-900/60 border border-white/20 px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-white/30">
                                    <option value="">All Seasons</option>
                                    <?php foreach ($seasons ?? [] as $season): ?>
                                        <option value="<?= htmlspecialchars((string)$season['id']) ?>">
                                            <?= htmlspecialchars($season['name'] ?? 'Season ' . $season['id']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-slate-400 text-xs mb-1">Competition Type</label>
                                <select id="team-performance-type-filter" class="block w-full rounded-md bg-slate-900/60 border border-white/20 px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-white/30">
                                    <option value="">Both League &amp; Cup</option>
                                    <option value="league">League Only</option>
                                    <option value="cup">Cup Only</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-xl bg-slate-900/80 border border-white/10 p-3" data-filter-group="player-performance" style="display:none;">
                        <h6 class="text-slate-300 text-xs font-semibold mb-2">Filters</h6>
                        <div class="flex flex-col gap-3">
                            <div>
                                <label class="block text-slate-400 text-xs mb-1">Season</label>
                                <select id="player-season-filter" class="block w-full rounded-md bg-slate-900/60 border border-white/20 px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-white/30">
                                    <option value="">All Seasons</option>
                                    <?php foreach ($seasons ?? [] as $season): ?>
                                        <option value="<?= htmlspecialchars((string)$season['id']) ?>">
                                            <?= htmlspecialchars($season['name'] ?? 'Season ' . $season['id']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-slate-400 text-xs mb-1">Competition Type</label>
                                <select id="player-type-filter" class="block w-full rounded-md bg-slate-900/60 border border-white/20 px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-white/30">
                                    <option value="">Both League &amp; Cup</option>
                                    <option value="league">League Only</option>
                                    <option value="cup">Cup Only</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-slate-400 text-xs mb-1">Position</label>
                                <select id="player-position-filter" class="block w-full rounded-md bg-slate-900/60 border border-white/20 px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-white/30">
                                    <option value="">All Positions</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>
            <main class="col-span-7 space-y-4 min-w-0">
                <div class="stats-panels">
            <section id="overview-panel" role="tabpanel" aria-labelledby="overview-tab" data-panel-id="overview">
                <div class="rounded-xl bg-slate-900/80 border border-white/10 p-3">
                    <!-- Matches list (Overview only) -->
                    <div class="mt-1">
                        <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-2 mb-2">
                        
                                <h4 class="mb-0">Matches</h4>
                        
                         
                        </div>
                        <?php if (!empty($matches)): ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm text-slate-200">
                                    <thead class="bg-slate-900/90 text-slate-100 uppercase tracking-wider">
                                        <tr>
                                            <th class="px-3 py-2">Date</th>
                                            <th class="px-3 py-2">Time</th>
                                            <th class="px-3 py-2">Match</th>
                                            <th class="px-3 py-2 text-center">Score</th>
                                            <th class="px-3 py-2">Competition</th>
                                            <th class="px-3 py-2 text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($matches as $match): ?>
                                        <?php
                                            $matchId = (int)$match['id'];
                                            $matchUrl = htmlspecialchars($base . '/stats/match/' . $matchId);
                                            $title = trim(($match['home_team'] ?? '') . ' vs ' . ($match['away_team'] ?? ''));
                                            if ($title === '') {
                                                $title = 'Untitled match';
                                            }
                                            $dateLabel = $formatMatchDate($match);
                                            $timeLabel = $formatMatchTime($match);
                                            $scoreLabel = $formatMatchScore($match) ?? '—';
                                            $status = strtolower(trim((string)($match['status'] ?? '')));
                                            if ($status === '') {
                                                $status = 'draft';
                                            }
                                            $statusLabel = $formatStatusLabel($status);
                                            $statusClass = $normalizeStatusClass($status);
                                            $competition = $match['competition'] ?? '';
                                        ?>
                                        <tr data-match-id="<?= htmlspecialchars((string)$matchId) ?>">
                                            <td class="px-3 py-2 whitespace-nowrap"><?= htmlspecialchars($dateLabel) ?></td>
                                            <td class="px-3 py-2 whitespace-nowrap"><?= htmlspecialchars($timeLabel) ?></td>
                                            <td class="px-3 py-2">
                                                <a href="<?= $matchUrl ?>" class="text-indigo-300 hover:text-indigo-100">
                                                    <?= htmlspecialchars($title) ?>
                                                </a>
                                            </td>
                                            <td class="px-3 py-2 text-center">
                                                <span class="font-semibold <?= $scoreLabel !== '—' ? 'text-emerald-400' : 'text-slate-400' ?>"><?= htmlspecialchars($scoreLabel) ?></span>
                                            </td>
                                            <td class="px-3 py-2"><?= htmlspecialchars($competition) ?></td>
                                            <td class="px-3 py-2 text-center">
                                                <a class="inline-flex items-center px-2.5 py-1 text-xs rounded-md border border-white/20 hover:bg-white/10" href="<?= $matchUrl ?>">View</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-secondary mb-0">No ready matches available yet.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <section id="team-performance-panel" role="tabpanel" aria-labelledby="team-performance-tab" data-panel-id="team-performance" style="display:none;">
            <div class="rounded-xl bg-slate-900/80 border border-white/10 p-3">
                    <div class="d-flex flex-column flex-md-row align-items-start justify-content-between gap-3 mb-3">
                        <div>
                            <h3 class="mb-1">Team Performance</h3>
                        </div>
                    </div>
                    
                    <!-- Filters moved to left sidebar -->
                    
                <div id="team-performance-content" style="display:none;">
                        <div class="row g-3 mt-3">
                            <div class="col-12">
                                <div class="rounded-xl border border-white/10 bg-slate-800/60 p-3">
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <h5 class="mb-0 text-light">Home vs Away Record</h5>
                                    </div>
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-900/90 text-slate-100 uppercase tracking-wider">
                                                <tr>
                                                    <th>Venue</th>
                                                    <th class="text-center">MP</th>
                                                    <th class="text-center">W</th>
                                                    <th class="text-center">D</th>
                                                    <th class="text-center">L</th>
                                                    <th class="text-center">GF</th>
                                                    <th class="text-center">GA</th>
                                                    <th class="text-center">GD</th>
                                                </tr>
                                            </thead>
                                            <tbody id="team-performance-home-away" class="text-slate-200">
                                                <tr>
                                                    <td>Home</td>
                                                    <td class="text-center">—</td>
                                                    <td class="text-center">—</td>
                                                    <td class="text-center">—</td>
                                                    <td class="text-center">—</td>
                                                    <td class="text-center">—</td>
                                                    <td class="text-center">—</td>
                                                    <td class="text-center">—</td>
                                                </tr>
                                                <tr>
                                                    <td>Away</td>
                                                    <td class="text-center">—</td>
                                                    <td class="text-center">—</td>
                                                    <td class="text-center">—</td>
                                                    <td class="text-center">—</td>
                                                    <td class="text-center">—</td>
                                                    <td class="text-center">—</td>
                                                    <td class="text-center">—</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row g-3 mt-3">
                            <div class="col-12 col-md-6">
                                <div class="rounded-xl border border-white/10 bg-slate-800/60 p-3">
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <h5 class="mb-0 text-light">Recent Form</h5>
                                        <select id="team-performance-form-count" class="block rounded-md bg-slate-900/60 border border-white/20 px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-white/30">
                                            <option value="5" selected>Last 5</option>
                                            <option value="6">Last 6</option>
                                            <option value="7">Last 7</option>
                                            <option value="8">Last 8</option>
                                            <option value="9">Last 9</option>
                                            <option value="10">Last 10</option>
                                        </select>
                                    </div>
                                    <div id="team-performance-form" class="transition-opacity duration-300">
                                        <div class="text-muted text-xs">Loading…</div>
                                    </div>
                                    <div id="team-performance-form-empty" class="text-muted text-xs mt-2" style="display:none;">Add matches to capture your form.</div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="rounded-xl border border-white/10 bg-slate-800/60 p-3">
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <h5 class="mb-0 text-light">Goals &amp; Clean Sheets</h5>
                                    </div>
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full text-sm">
                                            <thead class="bg-slate-900/90 text-slate-100 uppercase tracking-wider">
                                                <tr>
                                                    <th>Competition</th>
                                                    <th class="text-center">For</th>
                                                    <th class="text-center">Against</th>
                                                    <th class="text-center">Diff</th>
                                                    <th class="text-center">Clean</th>
                                                </tr>
                                            </thead>
                                            <tbody class="text-slate-200">
                                                <tr data-competition-type="league">
                                                    <td>League</td>
                                                    <td class="text-center" data-league-stat="goals_for">—</td>
                                                    <td class="text-center" data-league-stat="goals_against">—</td>
                                                    <td class="text-center" data-league-stat="goal_difference">—</td>
                                                    <td class="text-center" data-league-stat="clean_sheets">—</td>
                                                </tr>
                                                <tr data-competition-type="cup">
                                                    <td>Cup</td>
                                                    <td class="text-center" data-cup-stat="goals_for">—</td>
                                                    <td class="text-center" data-cup-stat="goals_against">—</td>
                                                    <td class="text-center" data-cup-stat="goal_difference">—</td>
                                                    <td class="text-center" data-cup-stat="clean_sheets">—</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="team-performance-empty" class="text-muted text-xs mt-3" style="display:none;">No ready matches available yet.</div>
                    <div id="team-performance-loading" class="text-muted text-xs mt-3">Loading team performance…</div>
                    <div id="team-performance-error" class="text-danger text-xs mt-3" style="display:none;">Unable to load team performance.</div>
                </div>
            </section>

            <section id="player-performance-panel" role="tabpanel" aria-labelledby="player-performance-tab" data-panel-id="player-performance" style="display:none;">
                <div class="rounded-xl bg-slate-900/80 border border-white/10 p-4">
                    <h3 class="mb-3">Player Performance</h3>
                    <!-- Filters moved to left sidebar -->

                    <!-- Table -->
                    <div id="player-performance-content">
                        <div id="player-performance-loading" class="text-muted text-xs">Loading player performance…</div>
                        <div id="player-performance-error" class="text-danger text-xs" style="display:none;">Unable to load player performance.</div>
                        <div id="player-performance-empty" class="text-muted text-xs" style="display:none;">No player data available.</div>
                        
                        <div id="player-performance-table-wrapper" style="display:none;">
                            <div class="overflow-x-auto rounded-xl border border-white/10">
                                <table class="w-full text-sm text-slate-200">
                                    <thead class="sticky top-0 bg-slate-900/95 border-b border-white/10">
                                        <tr>
                                            <th class="sortable px-6 py-3 text-left font-semibold uppercase tracking-wide text-slate-300 cursor-pointer hover:text-slate-100" data-sort="name">Player <span class="ml-1 text-xs opacity-50">⇅</span></th>
                                            <th class="sortable px-4 py-3 text-center font-semibold uppercase tracking-wide text-slate-300 cursor-pointer hover:text-slate-100" data-sort="position">Pos <span class="ml-1 text-xs opacity-50">⇅</span></th>
                                            <th class="sortable px-4 py-3 text-center font-semibold uppercase tracking-wide text-slate-300 cursor-pointer hover:text-slate-100" data-sort="appearances">Apps <span class="ml-1 text-xs opacity-50">⇅</span></th>
                                            <th class="sortable px-4 py-3 text-center font-semibold uppercase tracking-wide text-slate-300 cursor-pointer hover:text-slate-100" data-sort="starts">Starts <span class="ml-1 text-xs opacity-50">⇅</span></th>
                                            <th class="sortable px-4 py-3 text-center font-semibold uppercase tracking-wide text-slate-300 cursor-pointer hover:text-slate-100" data-sort="goals">Goals <span class="ml-1 text-xs opacity-50">⇅</span></th>
                                            <th class="sortable px-4 py-3 text-center font-semibold uppercase tracking-wide text-slate-300 cursor-pointer hover:text-slate-100" data-sort="yellow_cards">
                                                <span class="inline-flex items-center justify-center gap-2">
                                                    <svg class="yellowCard-ico" style="width: 12px; height: 16px;"><use xlink:href="/assets/svg/incident.svg#card"></use></svg>
                                                    <span class="text-xs opacity-50">⇅</span>
                                                </span>
                                            </th>
                                            <th class="sortable px-4 py-3 text-center font-semibold uppercase tracking-wide text-slate-300 cursor-pointer hover:text-slate-100" data-sort="red_cards">
                                                <span class="inline-flex items-center justify-center gap-2">
                                                    <svg class="redCard-ico" style="width: 12px; height: 16px;"><use xlink:href="/assets/svg/incident.svg#card"></use></svg>
                                                    <span class="text-xs opacity-50">⇅</span>
                                                </span>
                                            </th>
                                            <th class="sortable px-4 py-3 text-center font-semibold uppercase tracking-wide text-slate-300 cursor-pointer hover:text-slate-100" data-sort="minutes">Mins <span class="ml-1 text-xs opacity-50">⇅</span></th>
                                        </tr>
                                    </thead>
                                    <tbody id="player-performance-tbody">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
                </div>
            </main>
            
            <!-- Right Sidebar: Overview Cards -->
            <aside id="overview-sidebar" class="col-span-3 min-w-0">
                <div class="rounded-xl bg-slate-900/80 border border-white/10 p-4">
                    <h5 class="text-slate-200 font-semibold mb-1" id="overview-title">Overview</h5>
                    <div class="text-slate-400 text-xs mb-4">Club-wide performance summary</div>
                    <div class="space-y-3">
                        <!-- Grouped: Matches/Clean Sheets/Avg Goals -->
                        <article class="rounded-lg border border-white/10 bg-slate-800/40 px-3 py-3">
                            <div class="grid grid-cols-3 gap-3 text-center">
                                <div data-stat-card="total_matches">
                                    <div class="text-[10px] text-slate-400 mb-1">Matches</div>
                                    <div class="text-xl font-bold text-slate-100" data-stat-value="total_matches">—</div>
                                </div>
                                <div data-stat-card="clean_sheets">
                                    <div class="text-[10px] text-slate-400 mb-1">Clean Sheets</div>
                                    <div class="text-xl font-bold text-slate-100" data-stat-value="clean_sheets">—</div>
                                </div>
                                <div data-stat-card="average_goals_per_game">
                                    <div class="text-[10px] text-slate-400 mb-1">Avg Goals</div>
                                    <div class="text-xl font-bold text-slate-100" data-stat-value="average_goals_per_game">—</div>
                                </div>
                            </div>
                        </article>
                        
                        <div class="border-t border-white/10"></div>
                        
                        <!-- Grouped: Match Results -->
                        <article class="rounded-lg border border-white/10 bg-slate-800/40 px-3 py-3">
                            <div class="text-xs font-semibold text-slate-300 mb-2 text-center">Match Results</div>
                            <div class="grid grid-cols-3 gap-3 text-center">
                                <div data-stat-card="wins">
                                    <div class="text-[10px] text-slate-400 mb-1">Wins</div>
                                    <div class="text-xl font-bold text-emerald-400" data-stat-value="wins">—</div>
                                </div>
                                <div data-stat-card="draws">
                                    <div class="text-[10px] text-slate-400 mb-1">Draws</div>
                                    <div class="text-xl font-bold text-amber-400" data-stat-value="draws">—</div>
                                </div>
                                <div data-stat-card="losses">
                                    <div class="text-[10px] text-slate-400 mb-1">Losses</div>
                                    <div class="text-xl font-bold text-red-400" data-stat-value="losses">—</div>
                                </div>
                            </div>
                        </article>
                        
                        <div class="border-t border-white/10"></div>
                        
                        <!-- Grouped: Goals -->
                        <article class="rounded-lg border border-white/10 bg-slate-800/40 px-3 py-3">
                            <div class="text-xs font-semibold text-slate-300 mb-2 text-center">Goals</div>
                            <div class="grid grid-cols-3 gap-3 text-center">
                                <div data-stat-card="goals_for">
                                    <div class="text-[10px] text-slate-400 mb-1">For</div>
                                    <div class="text-xl font-bold text-emerald-400" data-stat-value="goals_for">—</div>
                                </div>
                                <div data-stat-card="goals_against">
                                    <div class="text-[10px] text-slate-400 mb-1">Against</div>
                                    <div class="text-xl font-bold text-red-400" data-stat-value="goals_against">—</div>
                                </div>
                                <div data-stat-card="goal_difference" data-stat-direction="signed">
                                    <div class="text-[10px] text-slate-400 mb-1">Diff</div>
                                    <div class="text-xl font-bold text-cyan-400" data-stat-value="goal_difference">—</div>
                                </div>
                            </div>
                        </article>
                    </div>
                    <div id="overview-error" class="text-red-400 text-xs mt-3" style="display:none;">Unable to load overview statistics.</div>
                </div>
            </aside>
            
            <!-- Right Sidebar: Team Performance Cards -->
            <aside id="team-performance-sidebar" class="col-span-3 min-w-0" style="display:none;">
                <div class="rounded-xl bg-slate-900/80 border border-white/10 p-4">
                    <h5 class="text-slate-200 font-semibold mb-1">Team Statistics</h5>
                    <div class="text-slate-400 text-xs mb-4">Performance metrics</div>
                    <div class="space-y-3" id="team-performance-cards-sidebar"></div>
                </div>
            </aside>
            
            <!-- Right Sidebar: Player Performance Stats -->
            <aside id="player-performance-sidebar" class="col-span-3 min-w-0" style="display:none;">
                <div class="rounded-xl bg-slate-900/80 border border-white/10 p-4">
                    <div class="space-y-3">
                        <!-- Option 1: Top Player Statistics -->
                        <div>
                            <h5 class="text-slate-200 font-semibold mb-1">Top Performers</h5>
                            <div class="text-slate-400 text-xs mb-4">Squad standouts</div>
                            <div class="space-y-3" id="player-top-performers"></div>
                        </div>
                        
                        <div class="border-t border-white/10"></div>
                        
                        <!-- Option 2: Squad Overview -->
                        <div>
                            <h5 class="text-slate-200 font-semibold mb-1">Squad Summary</h5>
                            <div class="text-slate-400 text-xs mb-4">Aggregate metrics</div>
                            <div class="space-y-3" id="player-squad-summary"></div>
                        </div>
                    </div>
                </div>
            </aside>

        
    </div>
    
</div>

<style>
.stats-page {
    color: #e5e7eb;
}
.stats-page h1,
.stats-page h2,
.stats-page h3,
.stats-page h4,
.stats-page h5,
.stats-page h6,
.stats-page p,
.stats-page label,
.stats-page span,
.stats-page ul,
.stats-page li,
.stats-page th,
.stats-page td,
.stats-page .panel,
.stats-page .form-control,
.stats-page .form-select,
.stats-page .stats-card,
.stats-page .stats-tabs .btn,
.stats-page .table {
    color: inherit;
}
.stats-page .stats-tabs .btn.is-active {
    background-color: rgba(255, 255, 255, 0.12);
    border-color: rgba(255, 255, 255, 0.2);
}
.stats-page .text-muted-alt {
    color: rgba(255, 255, 255, 0.65);
}
.stats-page .text-muted-alt small {
    color: rgba(255, 255, 255, 0.4);
}
.stats-page .stats-card {
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 12px;
    background-color: rgba(255, 255, 255, 0.02);
}

/* Three-column layout */
.stats-three-col {
    display: grid;
    grid-template-columns: 280px 1fr 320px;
    gap: 16px;
}
.stats-col-left {
    display: flex;
    flex-direction: column;
}
.stats-col-main {
    min-width: 0; /* allow table/content to shrink */
}
.stats-col-right {
    min-width: 0;
}
@media (max-width: 992px) {
    .stats-three-col {
        grid-template-columns: 1fr;
    }
    .stats-col-right {
        order: 3;
    }
}

/* Overview redesign */
.stats-overview-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 12px;
}

.stats-overview-card {
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 12px;
    padding: 16px;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    min-height: 110px;
    transition: background-color 120ms ease, border-color 120ms ease, transform 120ms ease;
}

.stats-overview-card:hover {
    background: rgba(255, 255, 255, 0.06);
    border-color: rgba(255, 255, 255, 0.14);
}

.stats-overview-card__left {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.stats-overview-card__desc {
    font-size: 11px;
    letter-spacing: .02em;
    text-transform: uppercase;
    color: rgba(255, 255, 255, 0.6);
}

.stats-overview-card__value {
    font-size: 28px;
    font-weight: 800;
    line-height: 1;
}

.stats-overview-card__label {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.7);
}

.stats-overview-card__icon {
    margin-left: 12px;
    opacity: .9;
}

.stats-overview-card__icon .fa-solid {
    font-size: 20px;
}

/* Signed direction gets monospaced sign spacing, positive neutral */
[data-stat-direction="signed"] .stats-overview-card__value {
    font-variant-numeric: tabular-nums;
}

/* Team Performance redesign */
.tp-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 16px;
}

.tp-card {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.06) 0%, rgba(255, 255, 255, 0.02) 100%);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 12px;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 6px;
    min-height: 90px;
    transition: all 0.2s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.tp-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    border-color: rgba(255, 255, 255, 0.2);
}

.tp-card__label {
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.02em;
    color: rgba(255, 255, 255, 0.6);
}

.tp-card__value {
    font-size: 32px;
    font-weight: 700;
    line-height: 1;
    margin-top: auto;
}

.tp-form-grid {
    display: flex;
    justify-content: center;
    gap: 10px;
    flex-wrap: wrap;
}

.tp-form-grid .panel {
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.08);
    min-width: 140px;
}

/* Form result color coding */
.tp-form-result--W {
    background: rgba(34, 197, 94, 0.15) !important;
    border-color: rgba(34, 197, 94, 0.3) !important;
}

.tp-form-result--L {
    background: rgba(239, 68, 68, 0.15) !important;
    border-color: rgba(239, 68, 68, 0.3) !important;
}

.tp-form-result--D {
    background: rgba(156, 163, 175, 0.15) !important;
    border-color: rgba(156, 163, 175, 0.3) !important;
}

/* Home vs Away table centered and sized */
.tp-home-away-table {
    width: 75%;
    max-width: 800px;
    margin-left: auto;
    margin-right: auto;
}

/* Goals & Clean Sheets grid */
.tp-goals-table {
    width: 85%;
    max-width: 900px;
    margin-left: auto;
    margin-right: auto;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.08);
}

.tp-goals-table th,
.tp-goals-table td {
    vertical-align: middle;
}

.tp-goals-table tbody td[data-league-stat="goals_for"],
.tp-goals-table tbody td[data-cup-stat="goals_for"] {
    color: #22c55e;
    font-weight: 700;
}

.tp-goals-table tbody td[data-league-stat="goals_against"],
.tp-goals-table tbody td[data-cup-stat="goals_against"] {
    color: #ef4444;
    font-weight: 700;
}

.tp-goals-table tbody td[data-league-stat="goal_difference"],
.tp-goals-table tbody td[data-cup-stat="goal_difference"] {
    color: #3b82f6;
    font-weight: 700;
}

.tp-goals-table tbody td[data-league-stat="clean_sheets"],
.tp-goals-table tbody td[data-cup-stat="clean_sheets"] {
    color: #f59e0b;
    font-weight: 700;
}

.tp-goal-card__label {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.02em;
    color: rgba(255, 255, 255, 0.6);
    margin-bottom: 8px;
}

.tp-goal-card__value {
    font-size: 28px;
    font-weight: 800;
    line-height: 1;
}

.tp-goal-card--for .tp-goal-card__value {
    color: #22c55e;
}

.tp-goal-card--against .tp-goal-card__value {
    color: #ef4444;
}

.tp-goal-card--diff .tp-goal-card__value {
    color: #3b82f6;
}

.tp-goal-card--clean .tp-goal-card__value {
    color: #8b5cf6;
}

/* Club selector dark styling */
#stats-club-selector.form-select,
#stats-club-selector {
    background-color: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.18);
    color: #e5e7eb;
}

#stats-club-selector:focus {
    border-color: rgba(255, 255, 255, 0.35);
    box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.1) inset;
    outline: none;
}

#stats-club-selector option {
    background-color: #0b1220;
    color: #e5e7eb;
}

/* Table tweaks for dark theme */
.stats-page .table thead.table-dark th {
    background-color: rgba(255, 255, 255, 0.06);
    border-color: rgba(255, 255, 255, 0.12);
}
.stats-page .table td, .stats-page .table th {
    border-color: rgba(255, 255, 255, 0.12);
}
.stats-page .table td,
.stats-page .table th {
    vertical-align: middle;
}
.stats-page a {
    color: #a5b4fc;
}
.stats-page a:hover,
.stats-page a:focus {
    color: #dbeafe;
}
.stats-page .panel-secondary {
    border-color: rgba(255, 255, 255, 0.08) !important;
}
.panel-dark {
    background-color: rgba(15, 23, 42, 0.8) !important;
    border-color: rgba(255, 255, 255, 0.08) !important;
    border-radius: 12px !important;
}
.stats-page .table {
    background-color: rgba(15, 23, 42, 0.6) !important;
    border-radius: 12px !important;
    border: 1px solid rgba(255, 255, 255, 0.08) !important;
    overflow: hidden;
}
.stats-page .table thead {
    background-color: #0b1220 !important;
}
.stats-page .table thead th {
    color: #f8fafc !important;
    border-color: transparent;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    font-size: 0.75rem;
}
.stats-page .table tbody tr:nth-child(even) {
    background-color: rgba(18, 32, 76, 0.85) !important;
}
.stats-page .table tbody tr:nth-child(odd) {
    background-color: rgba(20, 34, 80, 0.65) !important;
}
.stats-page .table tbody tr td {
    border-color: transparent;
    color: #e2e8f0;
}
.stats-page .table-dark {
    background: rgba(12, 17, 34, 0.85) !important;
    color: #fff !important;
}
.library-list .library-row {
    border-radius: 10px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    padding: 0.75rem;
    margin-bottom: 0.75rem;
    background: rgba(255, 255, 255, 0.02);
}

/* Sortable table headers */
.stats-page th.sortable {
    cursor: pointer;
    user-select: none;
    transition: background-color 0.15s ease;
}
.stats-page th.sortable:hover {
    background-color: rgba(255, 255, 255, 0.1) !important;
}
.stats-page th.sortable .sort-icon {
    margin-left: 4px;
    opacity: 0.5;
    font-size: 0.85em;
}
</style>
<!-- Custom CSS removed; using Tailwind utility classes -->

<script>
(function () {
    const statDefinitions = <?= json_encode($statDefinitions) ?>;
    const baseMeta = document.querySelector('meta[name="base-path"]');
    const basePath = baseMeta ? baseMeta.content.trim().replace(/^\/+/, '').replace(/\/+$/, '') : '';
    const apiBase = (basePath ? '/' + basePath : '') + '/api/stats';
    const clubSelector = document.getElementById('stats-club-selector');

    if (clubSelector) {
        clubSelector.addEventListener('change', () => {
            const params = new URLSearchParams(window.location.search);
            const selectedClub = clubSelector.value;
            if (selectedClub) {
                params.set('club_id', selectedClub);
            } else {
                params.delete('club_id');
            }
            const queryString = params.toString();
            window.location.href = window.location.pathname + (queryString ? `?${queryString}` : '');
        });
    }

    const tabButtons = document.querySelectorAll('[data-tab-id]');
    const panels = document.querySelectorAll('[data-panel-id]');

    function showTab(tabId) {
        tabButtons.forEach((button) => {
            const isActive = button.getAttribute('data-tab-id') === tabId;
            button.setAttribute('aria-selected', isActive ? 'true' : 'false');
            
            if (isActive) {
                button.classList.remove('bg-slate-800/40', 'border-white/10', 'text-slate-300', 'hover:bg-slate-700/50', 'hover:border-white/20');
                button.classList.add('bg-indigo-600', 'border-indigo-500', 'text-white', 'shadow-lg', 'shadow-indigo-500/20');
            } else {
                button.classList.remove('bg-indigo-600', 'border-indigo-500', 'text-white', 'shadow-lg', 'shadow-indigo-500/20');
                button.classList.add('bg-slate-800/40', 'border-white/10', 'text-slate-300', 'hover:bg-slate-700/50', 'hover:border-white/20');
            }
        });

        panels.forEach((panel) => {
            const panelId = panel.getAttribute('data-panel-id');
            panel.style.display = panelId === tabId ? '' : 'none';
        });

        // Toggle right sidebars based on active tab
        const overviewSidebar = document.getElementById('overview-sidebar');
        const teamPerformanceSidebar = document.getElementById('team-performance-sidebar');
        const playerPerformanceSidebar = document.getElementById('player-performance-sidebar');
        if (overviewSidebar) overviewSidebar.style.display = tabId === 'overview' ? '' : 'none';
        if (teamPerformanceSidebar) teamPerformanceSidebar.style.display = tabId === 'team-performance' ? '' : 'none';
        if (playerPerformanceSidebar) playerPerformanceSidebar.style.display = tabId === 'player-performance' ? '' : 'none';

        // Toggle filter groups in sidebar
        const filterGroups = document.querySelectorAll('[data-filter-group]');
        filterGroups.forEach((group) => {
            const groupId = group.getAttribute('data-filter-group');
            group.style.display = groupId === tabId ? '' : 'none';
        });

        const module = tabModules[tabId];
        if (module && typeof module.init === 'function') {
            module.init();
        }
    }

    function formatValue(key, raw) {
        if (raw === null || raw === undefined || raw === '') {
            return '—';
        }
        const def = statDefinitions[key] || { format: 'integer' };
        const numeric = Number(raw);
        if (!Number.isFinite(numeric)) {
            return String(raw);
        }
        if (def.format === 'decimal') {
            return numeric.toFixed(2);
        }
        return numeric.toLocaleString();
    }

    const overviewModule = (() => {
        const baseUrl = apiBase + '/overview';
        const matchesUrl = apiBase + '/matches';
        let intervalId = null;

        function getFilterParams() {
            const seasonSelect = document.getElementById('overview-season-filter');
            const typeSelect = document.getElementById('overview-type-filter');
            const params = new URLSearchParams();
            if (seasonSelect && seasonSelect.value) {
                params.append('season_id', seasonSelect.value);
            }
            if (typeSelect && typeSelect.value) {
                params.append('type', typeSelect.value);
            }
            return params.toString();
        }
        
        function getUrl() {
            const filters = getFilterParams();
            return filters ? baseUrl + '?' + filters : baseUrl;
        }
        
        function getMatchesUrl() {
            const filters = getFilterParams();
            return filters ? matchesUrl + '?' + filters : matchesUrl;
        }
        
        function formatMatchDate(kickoffAt) {
            if (!kickoffAt) return 'TBD';
            try {
                const date = new Date(kickoffAt);
                return date.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
            } catch (e) {
                return 'TBD';
            }
        }
        
        function formatMatchTime(kickoffAt) {
            if (!kickoffAt) return 'TBD';
            try {
                const date = new Date(kickoffAt);
                return date.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
            } catch (e) {
                return 'TBD';
            }
        }
        
        function formatStatus(status) {
            if (!status) return 'Unknown';
            return status.charAt(0).toUpperCase() + status.slice(1).replace(/_/g, ' ');
        }
        
        function updateMatchesTable(matches) {
            const tbody = document.querySelector('#overview-panel tbody');
            if (!tbody) return;
            
            tbody.innerHTML = '';
            
            if (!matches || matches.length === 0) {
                const row = tbody.insertRow();
                const cell = row.insertCell();
                cell.colSpan = 6;
                cell.className = 'px-3 py-4 text-center text-slate-400';
                cell.textContent = 'No matches found for the selected filters.';
                return;
            }
            
            matches.forEach(match => {
                const row = tbody.insertRow();
                const matchId = match.id;
                const matchUrl = (basePath ? '/' + basePath : '') + '/stats/match/' + matchId;
                const title = (match.home_team || 'Home') + ' vs ' + (match.away_team || 'Away');
                const dateLabel = formatMatchDate(match.kickoff_at);
                const timeLabel = formatMatchTime(match.kickoff_at);
                const homeGoals = match.home_goals ?? 0;
                const awayGoals = match.away_goals ?? 0;
                const scoreLabel = homeGoals + ' - ' + awayGoals;
                const hasScore = homeGoals > 0 || awayGoals > 0;
                const status = match.status || 'draft';
                const statusLabel = formatStatus(status);
                const competition = match.competition || '';
                
                row.innerHTML = `
                    <td class="px-3 py-2 whitespace-nowrap">${dateLabel}</td>
                    <td class="px-3 py-2 whitespace-nowrap">${timeLabel}</td>
                    <td class="px-3 py-2">
                        <a href="${matchUrl}" class="text-indigo-300 hover:text-indigo-100">${title}</a>
                    </td>
                    <td class="px-3 py-2 text-center">
                        <span class="font-semibold ${hasScore ? 'text-emerald-400' : 'text-slate-400'}">${scoreLabel}</span>
                    </td>
                    <td class="px-3 py-2">${competition}</td>
                    <td class="px-3 py-2 text-center">
                        <a class="inline-flex items-center px-2.5 py-1 text-xs rounded-md border border-white/20 hover:bg-white/10" href="${matchUrl}">View</a>
                    </td>
                `;
            });
        }
        
        function fetchMatches() {
            fetch(getMatchesUrl(), {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
                cache: 'no-cache',
            })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then((payload) => {
                    if (payload.success && payload.matches) {
                        updateMatchesTable(payload.matches);
                    } else {
                        throw new Error(payload.error || 'Invalid payload');
                    }
                })
                .catch((error) => {
                    console.error('[Stats Matches]', error);
                });
        }

        function updateCard(key, value) {
            const valueEl = document.querySelector(`[data-stat-value="${key}"]`);
            if (!valueEl) {
                return;
            }
            valueEl.textContent = formatValue(key, value);

            const card = valueEl.closest('[data-stat-card]');
            const direction = card?.getAttribute('data-stat-direction') || 'positive';
            if (direction === 'signed') {
                valueEl.classList.remove('text-success', 'text-danger', 'text-muted');
                const numeric = Number(value);
                if (!Number.isFinite(numeric)) {
                    valueEl.classList.add('text-muted');
                } else if (numeric > 0) {
                    valueEl.classList.add('text-success');
                } else if (numeric < 0) {
                    valueEl.classList.add('text-danger');
                } else {
                    valueEl.classList.add('text-muted');
                }
            }
        }

        function updateOverviewTitle() {
            const seasonSelect = document.getElementById('overview-season-filter');
            const typeSelect = document.getElementById('overview-type-filter');
            const titleEl = document.getElementById('overview-title');
            
            const parts = [];
            if (typeSelect && typeSelect.value) {
                const typeText = typeSelect.options[typeSelect.selectedIndex].text;
                parts.push(typeText);
            }
            if (seasonSelect && seasonSelect.value) {
                const seasonText = seasonSelect.options[seasonSelect.selectedIndex].text;
                parts.push(seasonText);
            }
            
            if (parts.length > 0) {
                titleEl.textContent = 'Overview (' + parts.join(' - ') + ')';
            } else {
                titleEl.textContent = 'Overview';
            }
        }

        function applyStats(data) {
            if (!data || typeof data !== 'object') {
                return;
            }
            Object.keys(statDefinitions).forEach((key) => {
                updateCard(key, data[key]);
            });
            document.getElementById('overview-error').style.display = 'none';
            updateOverviewTitle();
        }

        function fetchStats() {
            fetch(getUrl(), {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
                cache: 'no-cache',
            })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then((payload) => {
                    if (payload.success && payload.data) {
                        applyStats(payload.data);
                    } else {
                        throw new Error(payload.error || 'Invalid payload');
                    }
                })
                .catch((error) => {
                    console.error('[Stats Overview]', error);
                    document.getElementById('overview-error').style.display = 'block';
                });
        }

        return {
            init() {
                if (intervalId !== null) {
                    return;
                }
                
                // Restore saved filter values from localStorage
                restoreOverviewFilterValues();
                
                fetchStats();
                fetchMatches();
                intervalId = window.setInterval(fetchStats, 30000);
                
                // Add filter listeners
                const seasonSelect = document.getElementById('overview-season-filter');
                const typeSelect = document.getElementById('overview-type-filter');
                if (seasonSelect) {
                    seasonSelect.addEventListener('change', () => {
                        saveOverviewFilterValues();
                        fetchStats();
                        fetchMatches();
                        updateOverviewTitle();
                    });
                }
                if (typeSelect) {
                    typeSelect.addEventListener('change', () => {
                        saveOverviewFilterValues();
                        fetchStats();
                        fetchMatches();
                        updateOverviewTitle();
                    });
                }
            },
        };
        
        function saveOverviewFilterValues() {
            const seasonSelect = document.getElementById('overview-season-filter');
            const typeSelect = document.getElementById('overview-type-filter');
            const filters = {};
            if (seasonSelect) filters.season = seasonSelect.value;
            if (typeSelect) filters.type = typeSelect.value;
            localStorage.setItem('overviewFilters', JSON.stringify(filters));
        }
        
        function restoreOverviewFilterValues() {
            const saved = localStorage.getItem('overviewFilters');
            if (!saved) return;
            
            try {
                const filters = JSON.parse(saved);
                const seasonSelect = document.getElementById('overview-season-filter');
                const typeSelect = document.getElementById('overview-type-filter');
                if (seasonSelect && filters.season) {
                    seasonSelect.value = filters.season;
                }
                if (typeSelect && filters.type) {
                    typeSelect.value = filters.type;
                }
            } catch (e) {
                console.error('Failed to restore overview filters', e);
            }
        }
    })();

    const teamPerformanceModule = (() => {
        const url = apiBase + '/team-performance';
        const loadingEl = document.getElementById('team-performance-loading');
        const errorEl = document.getElementById('team-performance-error');
        const emptyEl = document.getElementById('team-performance-empty');
        const contentEl = document.getElementById('team-performance-content');
        const formContainer = document.getElementById('team-performance-form');
        const formEmptyEl = document.getElementById('team-performance-form-empty');
        const formCountSelect = document.getElementById('team-performance-form-count');
        const recordBody = document.getElementById('team-performance-home-away');
        const statElements = document.querySelectorAll('[data-team-stat]');
        let initialized = false;
        let formEntries = [];

        function formatNumber(value) {
            if (value === null || value === undefined || value === '') {
                return '—';
            }
            const numeric = Number(value);
            if (!Number.isFinite(numeric)) {
                return String(value);
            }
            return numeric.toLocaleString();
        }

        function showLoadingState() {
            if (loadingEl) {
                loadingEl.style.display = '';
            }
            if (contentEl) {
                contentEl.style.display = 'none';
            }
            if (emptyEl) {
                emptyEl.style.display = 'none';
            }
            if (errorEl) {
                errorEl.style.display = 'none';
            }
        }

        function showContentState() {
            if (loadingEl) {
                loadingEl.style.display = 'none';
            }
            if (contentEl) {
                contentEl.style.display = '';
            }
            if (emptyEl) {
                emptyEl.style.display = 'none';
            }
            if (errorEl) {
                errorEl.style.display = 'none';
            }
        }

        function showEmptyState() {
            if (loadingEl) {
                loadingEl.style.display = 'none';
            }
            if (contentEl) {
                contentEl.style.display = 'none';
            }
            if (emptyEl) {
                emptyEl.style.display = '';
            }
            if (errorEl) {
                errorEl.style.display = 'none';
            }
        }

        function showErrorState() {
            if (loadingEl) {
                loadingEl.style.display = 'none';
            }
            if (contentEl) {
                contentEl.style.display = 'none';
            }
            if (emptyEl) {
                emptyEl.style.display = 'none';
            }
            if (errorEl) {
                errorEl.style.display = '';
            }
        }

        function updateStatElements(payload) {
            statElements.forEach((el) => {
                const key = el.getAttribute('data-team-stat');
                if (!key) {
                    return;
                }
                el.textContent = formatNumber(payload?.[key]);
            });
            
            // Also update sidebar cards
            updateSidebarCards(payload);
        }
        
        function updateSidebarCards(payload) {
            const cardsContainer = document.getElementById('team-performance-cards-sidebar');
            if (!cardsContainer) return;
            
            const html = `
                <!-- Group 1: Goals & Clean Sheets -->
                <article class="rounded-lg border border-white/10 bg-slate-800/40 px-3 py-3">
                    <div class="grid grid-cols-2 gap-3 text-center">
                        <div>
                            <div class="text-[10px] text-slate-400 mb-1">Goals For</div>
                            <div class="text-xl font-bold text-emerald-400">${formatNumber(payload?.goals_for)}</div>
                        </div>
                        <div>
                            <div class="text-[10px] text-slate-400 mb-1">Clean Sheets</div>
                            <div class="text-xl font-bold text-slate-100">${formatNumber(payload?.clean_sheets)}</div>
                        </div>
                    </div>
                </article>
                
                <div class="border-t border-white/10"></div>
                
                <!-- Group 2: Match Results -->
                <article class="rounded-lg border border-white/10 bg-slate-800/40 px-3 py-3">
                    <div class="text-xs font-semibold text-slate-300 mb-2 text-center">Match Results</div>
                    <div class="grid grid-cols-3 gap-3 text-center">
                        <div>
                            <div class="text-[10px] text-slate-400 mb-1">Wins</div>
                            <div class="text-xl font-bold text-emerald-400">${formatNumber(payload?.wins)}</div>
                        </div>
                        <div>
                            <div class="text-[10px] text-slate-400 mb-1">Losses</div>
                            <div class="text-xl font-bold text-red-400">${formatNumber(payload?.losses)}</div>
                        </div>
                        <div>
                            <div class="text-[10px] text-slate-400 mb-1">Draws</div>
                            <div class="text-xl font-bold text-amber-400">${formatNumber(payload?.draws)}</div>
                        </div>
                    </div>
                </article>
                
                <div class="border-t border-white/10"></div>
                
                <!-- Group 3: Goals Stats -->
                <article class="rounded-lg border border-white/10 bg-slate-800/40 px-3 py-3">
                    <div class="text-xs font-semibold text-slate-300 mb-2 text-center">Goals</div>
                    <div class="grid grid-cols-3 gap-3 text-center">
                        <div>
                            <div class="text-[10px] text-slate-400 mb-1">For</div>
                            <div class="text-xl font-bold text-emerald-400">${formatNumber(payload?.goals_for)}</div>
                        </div>
                        <div>
                            <div class="text-[10px] text-slate-400 mb-1">Against</div>
                            <div class="text-xl font-bold text-red-400">${formatNumber(payload?.goals_against)}</div>
                        </div>
                        <div>
                            <div class="text-[10px] text-slate-400 mb-1">Diff</div>
                            <div class="text-xl font-bold text-cyan-400">${formatNumber(payload?.goal_difference)}</div>
                        </div>
                    </div>
                </article>
            `;
            
            cardsContainer.innerHTML = html;
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function renderHomeAway(record = {}) {
            if (!recordBody) {
                return;
            }
            const venues = ['home', 'away'];
            const rows = venues.map((venue) => {
                const data = record[venue] ?? {};
                const matches = data.matches ?? 0;
                const wins = data.wins ?? 0;
                const draws = data.draws ?? 0;
                const losses = data.losses ?? 0;
                const gf = data.goals_for ?? 0;
                const ga = data.goals_against ?? 0;
                const gd = data.goal_difference ?? gf - ga;
                const label = venue.charAt(0).toUpperCase() + venue.slice(1);
                return `
                    <tr>
                        <td>${label}</td>
                        <td class="text-center">${formatNumber(matches)}</td>
                        <td class="text-center">${formatNumber(wins)}</td>
                        <td class="text-center">${formatNumber(draws)}</td>
                        <td class="text-center">${formatNumber(losses)}</td>
                        <td class="text-center">${formatNumber(gf)}</td>
                        <td class="text-center">${formatNumber(ga)}</td>
                        <td class="text-center">${formatNumber(gd)}</td>
                    </tr>
                `;
            });
            recordBody.innerHTML = rows.join('');
        }

        function getFormLimit() {
            const val = formCountSelect ? parseInt(formCountSelect.value, 10) : 5;
            return Number.isFinite(val) ? val : 5;
        }

        function applyFormLayout(limit) {
            if (!formContainer) return;
            const base = 'transition-opacity duration-300';
            if (limit > 5) {
                formContainer.className = `${base} grid gap-3 px-2`;
                formContainer.style.display = 'grid';
                formContainer.style.gridTemplateColumns = 'repeat(5, minmax(0, 1fr))';
                formContainer.style.overflowX = 'visible';
            } else {
                formContainer.className = `${base} flex flex-nowrap gap-3 px-2 overflow-x-auto`;
                formContainer.style.display = 'flex';
                formContainer.style.gridTemplateColumns = '';
                formContainer.style.overflowX = 'auto';
            }
        }

        function renderForm(entries = []) {
            formEntries = Array.isArray(entries) ? entries : [];
            renderFormWithLimit();
        }

        function renderFormWithLimit() {
            if (!formContainer) {
                return;
            }

            const limit = getFormLimit();
            const subset = formEntries.slice(0, limit);

            if (!subset.length) {
                formContainer.innerHTML = '';
                if (formEmptyEl) {
                    formEmptyEl.style.display = '';
                }
                return;
            }
            if (formEmptyEl) {
                formEmptyEl.style.display = 'none';
            }

            applyFormLayout(limit);

            // simple fade
            formContainer.style.opacity = '0';
            setTimeout(() => {
                formContainer.innerHTML = '';
                subset.forEach((entry) => {
                    const pill = document.createElement('div');
                    const result = entry.result ?? '—';
                    const colorClass = result === 'W' ? 'tp-form-result--W' : result === 'D' ? 'tp-form-result--D' : result === 'L' ? 'tp-form-result--L' : '';
                    pill.className = `rounded-lg border border-white/10 bg-slate-800/40 p-3 w-full min-w-[7.5rem] text-center shadow-sm transition-transform duration-200 hover:-translate-y-0.5 ${colorClass}`;
                    const dateLabel = entry.date ? new Date(entry.date).toLocaleDateString() : 'TBD';
                    const venueLabel = entry.venue ?? 'Home';
                    const opponent = entry.opponent ?? 'Opponent';
                    const score = entry.score ?? '—';
                    const resultClass = result === 'W' ? 'text-emerald-400' : result === 'D' ? 'text-amber-400' : 'text-red-400';
                    pill.innerHTML = `
                        <div class="${resultClass} text-3xl font-bold mb-2">${result}</div>
                        <div class="text-sm font-semibold text-slate-100 mb-1">${score}</div>
                        <div class="text-xs text-slate-400 mb-1">${venueLabel}</div>
                        <div class="text-xs text-slate-400">vs ${opponent}</div>
                        <div class="text-xs text-slate-500 mt-1">${dateLabel}
                        </div>
                    `;
                    formContainer.appendChild(pill);
                });
                requestAnimationFrame(() => {
                    formContainer.style.opacity = '1';
                });
            }, 30);
        }

        function applyStats(data) {
            updateStatElements(data);
            renderHomeAway(data?.home_away);
            renderLeagueCup(data?.league_cup);
            renderForm(data?.form);
            if ((data?.matches_played ?? 0) > 0) {
                showContentState();
            } else {
                showEmptyState();
            }
        }
        
        function renderLeagueCup(leagueCup) {
            if (!leagueCup) return;
            
            const league = leagueCup.league || {};
            const cup = leagueCup.cup || {};
            
            // Update league stats
            document.querySelectorAll('[data-league-stat]').forEach((el) => {
                const key = el.getAttribute('data-league-stat');
                const value = league[key] ?? 0;
                el.textContent = formatNumber(value);
            });
            
            // Update cup stats
            document.querySelectorAll('[data-cup-stat]').forEach((el) => {
                const key = el.getAttribute('data-cup-stat');
                const value = cup[key] ?? 0;
                el.textContent = formatNumber(value);
            });
            
            // Show/hide rows based on filter selection
            updateCompetitionTypeRows();
        }
        
        function updateCompetitionTypeRows() {
            const typeSelect = document.getElementById('team-performance-type-filter');
            if (!typeSelect) return;
            
            const selectedType = typeSelect.value;
            const leagueRow = document.querySelector('tr[data-competition-type="league"]');
            const cupRow = document.querySelector('tr[data-competition-type="cup"]');
            
            if (leagueRow) {
                leagueRow.style.display = !selectedType || selectedType === 'league' ? '' : 'none';
            }
            if (cupRow) {
                cupRow.style.display = !selectedType || selectedType === 'cup' ? '' : 'none';
            }
        }

        function getFilterParams() {
            const seasonSelect = document.getElementById('team-performance-season-filter');
            const typeSelect = document.getElementById('team-performance-type-filter');
            const formSelect = document.getElementById('team-performance-form-count');
            const params = new URLSearchParams();
            if (seasonSelect && seasonSelect.value) {
                params.append('season_id', seasonSelect.value);
            }
            if (typeSelect && typeSelect.value) {
                params.append('type', typeSelect.value);
            }
            if (formSelect && formSelect.value) {
                params.append('form_limit', formSelect.value);
            }
            return params.toString();
        }
        
        function getUrl() {
            const filters = getFilterParams();
            return filters ? url + '?' + filters : url;
        }

        function fetchStats(options = {}) {
            const silent = options.silent === true;
            if (!silent) {
                showLoadingState();
            }
            fetch(getUrl(), {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
                cache: 'no-cache',
            })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then((payload) => {
                    if (payload.success && payload.data) {
                        applyStats(payload.data);
                        return;
                    }
                    throw new Error(payload.error || 'Invalid payload');
                })
                .catch((error) => {
                    console.error('[Team Performance]', error);
                    if (!silent) {
                        showErrorState();
                    }
                });
        }

        return {
            init() {
                if (initialized) {
                    return;
                }
                initialized = true;
                
                // Restore saved filter values from localStorage
                restoreFilterValues();
                
                fetchStats();
                
                // Add filter listeners
                const seasonSelect = document.getElementById('team-performance-season-filter');
                const typeSelect = document.getElementById('team-performance-type-filter');
                if (seasonSelect) {
                    seasonSelect.addEventListener('change', () => {
                        saveFilterValues();
                        fetchStats();
                    });
                }
                if (typeSelect) {
                    typeSelect.addEventListener('change', () => {
                        saveFilterValues();
                        fetchStats();
                        updateCompetitionTypeRows();
                    });
                }
                if (formCountSelect) {
                    formCountSelect.addEventListener('change', () => {
                        saveFilterValues();
                        fetchStats({ silent: true });
                    });
                }
            },
        };
        
        function saveFilterValues() {
            const seasonSelect = document.getElementById('team-performance-season-filter');
            const typeSelect = document.getElementById('team-performance-type-filter');
            const formSelect = document.getElementById('team-performance-form-count');
            const filters = {};
            if (seasonSelect) filters.season = seasonSelect.value;
            if (typeSelect) filters.type = typeSelect.value;
            if (formSelect) filters.form_count = formSelect.value;
            localStorage.setItem('teamPerformanceFilters', JSON.stringify(filters));
        }
        
        function restoreFilterValues() {
            const saved = localStorage.getItem('teamPerformanceFilters');
            if (!saved) return;
            
            try {
                const filters = JSON.parse(saved);
                const seasonSelect = document.getElementById('team-performance-season-filter');
                const typeSelect = document.getElementById('team-performance-type-filter');
                const formSelect = document.getElementById('team-performance-form-count');
                if (seasonSelect && filters.season) {
                    seasonSelect.value = filters.season;
                }
                if (typeSelect && filters.type) {
                    typeSelect.value = filters.type;
                }
                if (formSelect && filters.form_count) {
                    formSelect.value = filters.form_count;
                }
            } catch (e) {
                console.error('Failed to restore team performance filters', e);
            }
        }
    })();

    function createPlaceholderModule() {
        return {
            init() {
                /* Placeholder module intentionally empty. */
            },
        };
    }

    // Player Performance Module
    const playerPerformanceModule = (function() {
        let allPlayers = [];
        let filteredPlayers = [];
        let currentSort = { field: 'appearances', direction: 'desc' };

        function init() {
            restorePlayerFilterValues();
            loadPlayerPerformance();
            setupFilters();
            setupSorting();
        }

        function loadPlayerPerformance() {
            const loadingEl = document.getElementById('player-performance-loading');
            const errorEl = document.getElementById('player-performance-error');
            const emptyEl = document.getElementById('player-performance-empty');
            const tableWrapper = document.getElementById('player-performance-table-wrapper');

            loadingEl.style.display = 'block';
            errorEl.style.display = 'none';
            emptyEl.style.display = 'none';
            tableWrapper.style.display = 'none';

            // Build filter query
            const seasonSelect = document.getElementById('player-season-filter');
            const typeSelect = document.getElementById('player-type-filter');
            const params = new URLSearchParams();
            if (seasonSelect && seasonSelect.value) {
                params.append('season_id', seasonSelect.value);
            }
            if (typeSelect && typeSelect.value) {
                params.append('type', typeSelect.value);
            }
            
            const url = params.toString() 
                ? `${apiBase}/player-performance?${params.toString()}` 
                : `${apiBase}/player-performance`;

            fetch(url)
                .then(resp => {
                    if (!resp.ok) throw new Error('Network response was not ok');
                    return resp.json();
                })
                .then(payload => {
                    loadingEl.style.display = 'none';
                    
                    if (payload.success && payload.players && payload.players.length > 0) {
                        allPlayers = payload.players;
                        filteredPlayers = [...allPlayers];
                        populatePositionFilter();
                        renderTable();
                        updatePlayerPerformanceSidebar();
                        tableWrapper.style.display = 'block';
                    } else {
                        emptyEl.style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('[Player Performance]', error);
                    loadingEl.style.display = 'none';
                    errorEl.style.display = 'block';
                });
        }

        function populatePositionFilter() {
            const filterEl = document.getElementById('player-position-filter');
            const positions = [...new Set(allPlayers.map(p => p.position))].filter(p => p && p !== 'N/A').sort();
            
            filterEl.innerHTML = '<option value="">All Positions</option>';
            positions.forEach(pos => {
                const option = document.createElement('option');
                option.value = pos;
                option.textContent = pos;
                filterEl.appendChild(option);
            });
        }

        function setupFilters() {
            const seasonFilter = document.getElementById('player-season-filter');
            const typeFilter = document.getElementById('player-type-filter');
            const positionFilter = document.getElementById('player-position-filter');

            // Season and type filters reload data
            if (seasonFilter) {
                seasonFilter.addEventListener('change', () => {
                    savePlayerFilterValues();
                    loadPlayerPerformance();
                });
            }
            // Type filter (league/cup) also reloads
            if (typeFilter) {
                typeFilter.addEventListener('change', () => {
                    savePlayerFilterValues();
                    loadPlayerPerformance();
                });
            }
            // Position filter applies to current data
            if (positionFilter) {
                positionFilter.addEventListener('change', () => {
                    savePlayerFilterValues();
                    applyFilters();
                });
            }
        }
        
        function savePlayerFilterValues() {
            const seasonSelect = document.getElementById('player-season-filter');
            const typeSelect = document.getElementById('player-type-filter');
            const positionSelect = document.getElementById('player-position-filter');
            const filters = {};
            if (seasonSelect) filters.season = seasonSelect.value;
            if (typeSelect) filters.type = typeSelect.value;
            if (positionSelect) filters.position = positionSelect.value;
            localStorage.setItem('playerPerformanceFilters', JSON.stringify(filters));
        }
        
        function restorePlayerFilterValues() {
            const saved = localStorage.getItem('playerPerformanceFilters');
            if (!saved) return;
            
            try {
                const filters = JSON.parse(saved);
                const seasonSelect = document.getElementById('player-season-filter');
                const typeSelect = document.getElementById('player-type-filter');
                const positionSelect = document.getElementById('player-position-filter');
                if (seasonSelect && filters.season) {
                    seasonSelect.value = filters.season;
                }
                if (typeSelect && filters.type) {
                    typeSelect.value = filters.type;
                }
                if (positionSelect && filters.position) {
                    positionSelect.value = filters.position;
                }
            } catch (e) {
                console.error('Failed to restore player performance filters', e);
            }
        }

        function applyFilters() {
            const positionFilter = document.getElementById('player-position-filter').value;

            filteredPlayers = allPlayers.filter(player => {
                if (positionFilter && player.position !== positionFilter) return false;
                return true;
            });

            renderTable();
        }

        function setupSorting() {
            const sortHeaders = document.querySelectorAll('#player-performance-table-wrapper th.sortable');
            sortHeaders.forEach(header => {
                header.addEventListener('click', () => {
                    const field = header.getAttribute('data-sort');
                    if (currentSort.field === field) {
                        currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
                    } else {
                        currentSort.field = field;
                        currentSort.direction = 'desc';
                    }
                    sortPlayers();
                    renderTable();
                    updateSortIcons();
                });
            });
        }

        function sortPlayers() {
            filteredPlayers.sort((a, b) => {
                let aVal = a[currentSort.field];
                let bVal = b[currentSort.field];

                // String comparison for name and position
                if (currentSort.field === 'name' || currentSort.field === 'position') {
                    aVal = String(aVal).toLowerCase();
                    bVal = String(bVal).toLowerCase();
                    return currentSort.direction === 'asc' 
                        ? aVal.localeCompare(bVal)
                        : bVal.localeCompare(aVal);
                }

                // Numeric comparison
                aVal = Number(aVal) || 0;
                bVal = Number(bVal) || 0;
                return currentSort.direction === 'asc' ? aVal - bVal : bVal - aVal;
            });
        }

        function updateSortIcons() {
            document.querySelectorAll('#player-performance-table-wrapper th.sortable .sort-icon').forEach(icon => {
                icon.textContent = '⇅';
            });
            
            const activeHeader = document.querySelector(`#player-performance-table-wrapper th[data-sort="${currentSort.field}"] .sort-icon`);
            if (activeHeader) {
                activeHeader.textContent = currentSort.direction === 'asc' ? '↑' : '↓';
            }
        }

        function renderTable() {
            const tbody = document.getElementById('player-performance-tbody');
            tbody.innerHTML = '';

            if (filteredPlayers.length === 0) {
                const row = tbody.insertRow();
                const cell = row.insertCell();
                cell.colSpan = 9;
                cell.className = 'px-4 py-4 text-center text-slate-400';
                cell.textContent = 'No players match the current filters.';
                return;
            }

            filteredPlayers.forEach((player, index) => {
                const row = tbody.insertRow();
                const isEven = index % 2 === 0;
                row.className = `border-b border-white/10 ${isEven ? 'bg-slate-800/30' : 'bg-slate-900/20'} hover:bg-slate-800/50 transition-colors`;
                row.innerHTML = `
                    <td class="px-6 py-3 text-left"><span class="font-bold text-slate-100">${escapeHtml(player.name)}</span></td>
                    <td class="px-4 py-3 text-center text-slate-300">${escapeHtml(player.position)}</td>
                    <td class="px-4 py-3 text-center text-slate-300">${player.appearances}</td>
                    <td class="px-4 py-3 text-center text-slate-300">${player.starts}</td>
                    <td class="px-4 py-3 text-center text-slate-300 font-semibold">${player.goals > 0 ? player.goals : '—'}</td>
                    <td class="px-4 py-3 text-center text-slate-300">${player.yellow_cards > 0 ? `<span class="inline-flex items-center gap-1">${player.yellow_cards}</span>` : '—'}</td>
                    <td class="px-4 py-3 text-center text-slate-300">${player.red_cards > 0 ? `<span class="inline-flex items-center gap-1">${player.red_cards}</span>` : '—'}</td>
                    <td class="px-4 py-3 text-center text-slate-300">${player.minutes_played}</td>
                `;
            });
        }

        function formatCards(yellow, red) {
            const parts = [];
            if (yellow > 0) parts.push(`🟨 ${yellow}`);
            if (red > 0) parts.push(`🟥 ${red}`);
            return parts.length > 0 ? parts.join(' ') : '—';
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function updatePlayerPerformanceSidebar() {
            if (!allPlayers || allPlayers.length === 0) return;
            
            updateTopPerformers();
            updateSquadSummary();
        }
        
        function updateTopPerformers() {
            const container = document.getElementById('player-top-performers');
            if (!container) return;
            
            // Find top goalscorer
            const topScorer = [...allPlayers].sort((a, b) => b.goals - a.goals)[0] || {};
            const totalGoals = allPlayers.reduce((sum, p) => sum + p.goals, 0);
            const goalsPerMatch = allPlayers.length > 0 ? (totalGoals / allPlayers.length).toFixed(2) : 0;
            
            // Find player with most appearances
            const mostUsed = [...allPlayers].sort((a, b) => b.appearances - a.appearances)[0] || {};
            
            // Discipline stats
            const totalYellow = allPlayers.reduce((sum, p) => sum + p.yellow_cards, 0);
            const totalRed = allPlayers.reduce((sum, p) => sum + p.red_cards, 0);
            
            // Top minute player
            const minuteLeader = [...allPlayers].sort((a, b) => b.minutes_played - a.minutes_played)[0] || {};
            
            container.innerHTML = `
                <!-- Core Leaders grouped -->
                <article class="rounded-lg border border-white/10 bg-slate-800/40 px-3 py-3">
                    <div class="text-xs font-semibold text-slate-300 mb-3">Top Leaders</div>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between gap-2">
                            <div>
                                <div class="text-[10px] uppercase tracking-wide text-slate-400">Top Goalscorer</div>
                                <div class="text-sm text-slate-300">${topScorer.name ? escapeHtml(topScorer.name) : '—'}</div>
                            </div>
                            <div class="text-2xl font-bold text-emerald-400">${topScorer.goals || 0}</div>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <div>
                                <div class="text-[10px] uppercase tracking-wide text-slate-400">Most Minutes</div>
                                <div class="text-sm text-slate-300">${minuteLeader.name ? escapeHtml(minuteLeader.name) : '—'}</div>
                            </div>
                            <div class="text-2xl font-bold text-cyan-400">${minuteLeader.minutes_played || 0}</div>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <div>
                                <div class="text-[10px] uppercase tracking-wide text-slate-400">Most Used</div>
                                <div class="text-sm text-slate-300">${mostUsed.name ? escapeHtml(mostUsed.name) : '—'}</div>
                            </div>
                            <div class="text-2xl font-bold text-blue-400">${mostUsed.appearances || 0}</div>
                        </div>
                    </div>
                </article>

                <!-- Discipline -->
                <article class="rounded-lg border border-white/10 bg-slate-800/40 px-3 py-3">
                    <div class="text-xs font-semibold text-slate-300 mb-2">Discipline</div>
                    <div class="grid grid-cols-2 gap-3 text-center">
                        <div>
                            <div class="text-xl font-bold text-yellow-500">${totalYellow}</div>
                            <div class="text-xs text-slate-400">Yellow Cards</div>
                        </div>
                        <div>
                            <div class="text-xl font-bold text-red-500">${totalRed}</div>
                            <div class="text-xs text-slate-400">Red Cards</div>
                        </div>
                    </div>
                </article>
            `;
        }
        
        function updateSquadSummary() {
            const container = document.getElementById('player-squad-summary');
            if (!container) return;
            
            // Squad size by position
            const positions = {};
            allPlayers.forEach(p => {
                const pos = p.position || 'N/A';
                positions[pos] = (positions[pos] || 0) + 1;
            });
            
            // Total stats
            const totalGoals = allPlayers.reduce((sum, p) => sum + p.goals, 0);
            const totalYellow = allPlayers.reduce((sum, p) => sum + p.yellow_cards, 0);
            const totalRed = allPlayers.reduce((sum, p) => sum + p.red_cards, 0);
            const totalMinutes = allPlayers.reduce((sum, p) => sum + p.minutes_played, 0);
            const goalsPerPlayer = allPlayers.length > 0 ? (totalGoals / allPlayers.length).toFixed(2) : '0.00';
            const avgMinutesPerPlayer = allPlayers.length > 0 ? (totalMinutes / allPlayers.length).toFixed(0) : '0';
            
            const positionList = Object.entries(positions)
                .map(([pos, count]) => `${count} ${pos}`)
                .join(', ');
            
            container.innerHTML = `
                <!-- Squad core stats grouped -->
                <article class="rounded-lg border border-white/10 bg-slate-800/40 px-3 py-3">
                    <div class="text-xs font-semibold text-slate-300 mb-3">Squad Overview</div>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between gap-2">
                            <div>
                                <div class="text-[10px] uppercase tracking-wide text-slate-400">Squad Size</div>
                                <div class="text-xs text-slate-500 mt-1">${escapeHtml(positionList)}</div>
                            </div>
                            <div class="text-2xl font-bold text-slate-100">${allPlayers.length}</div>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <div>
                                <div class="text-[10px] uppercase tracking-wide text-slate-400">Goals Scored</div>
                                <div class="text-xs text-slate-500 mt-1">${goalsPerPlayer} per player</div>
                            </div>
                            <div class="text-2xl font-bold text-emerald-400">${totalGoals}</div>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <div>
                                <div class="text-[10px] uppercase tracking-wide text-slate-400">Playing Time</div>
                                <div class="text-xs text-slate-500 mt-1">${avgMinutesPerPlayer} avg per player</div>
                            </div>
                            <div class="text-2xl font-bold text-cyan-400">${totalMinutes}</div>
                        </div>
                    </div>
                </article>

                <!-- Discipline Summary -->
                <article class="rounded-lg border border-white/10 bg-slate-800/40 px-3 py-3">
                    <div class="text-xs font-semibold text-slate-300 mb-2">Discipline</div>
                    <div class="grid grid-cols-2 gap-3 text-center">
                        <div>
                            <div class="text-xl font-bold text-yellow-500">${totalYellow}</div>
                            <div class="text-xs text-slate-400">Yellow</div>
                        </div>
                        <div>
                            <div class="text-xl font-bold text-red-500">${totalRed}</div>
                            <div class="text-xs text-slate-400">Red</div>
                        </div>
                    </div>
                </article>
            `;
        }

        return { init };
    })();

    const tabModules = {
        overview: overviewModule,
        'team-performance': teamPerformanceModule,
        'player-performance': playerPerformanceModule,
    };

    tabButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const tabId = button.getAttribute('data-tab-id');
            showTab(tabId);
            // Save the active tab to localStorage
            localStorage.setItem('statsPageActiveTab', tabId);
        });
    });

    // Restore the active tab from localStorage, or default to 'overview'
    const savedTab = localStorage.getItem('statsPageActiveTab') || 'overview';
    showTab(savedTab);
})();
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../layout.php';
