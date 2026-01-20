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
<div class="stats-page container mt-4">
    <div class="library-layout">
        <header class="library-layout__header">
            <div>
                <h1 class="library-layout__title">Statistics Dashboard</h1>
                <p class="library-layout__description">View club-wide analytics and performance indicators.</p>
            </div>
            <div class="library-layout__header-actions">
                <p class="text-muted text-xs mb-0">
                    Viewing stats for <strong><?= htmlspecialchars($clubContextName) ?></strong>.
                </p>
                <?php if ($showClubSelector): ?>
                    <div class="ms-2">
                        <label for="stats-club-selector" class="form-label text-muted text-xs mb-1">Switch club context</label>
                        <select id="stats-club-selector" class="form-select form-select-sm">
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

        <div class="library-tabs-row mb-3">
            <nav class="library-tabs stats-tabs" role="tablist" aria-label="Statistics tabs">
                <?php $tabs = [
                    'overview' => 'Overview',
                    'team-performance' => 'Team Performance',
                    'player-performance' => 'Player Performance',
                ]; ?>
                <?php foreach ($tabs as $tabId => $tabLabel): ?>
                    <button
                        type="button"
                        class="library-tab btn btn-sm btn-outline-light stats-tab <?= $tabId === 'overview' ? 'is-active' : '' ?>"
                        role="tab"
                        aria-selected="<?= $tabId === 'overview' ? 'true' : 'false' ?>"
                        data-tab-id="<?= htmlspecialchars($tabId) ?>">
                        <?= htmlspecialchars($tabLabel) ?>
                    </button>
                <?php endforeach; ?>
            </nav>
            <div class="library-tabs-row__right">
                <span class="text-muted text-xs">Club-level stats only</span>
            </div>
        </div>

        <div class="stats-panels">
            <section id="overview-panel" role="tabpanel" aria-labelledby="overview-tab" data-panel-id="overview">
                <div class="panel panel-dark p-4">
                    <h3 class="mb-3">League Stats Overview</h3>
                    <p class="text-muted-alt text-sm mb-4">Showing statistics for Fourth Division matches only.</p>
                    <div class="stats-overview-grid">
                        <?php foreach ($overviewMetrics as $metric): ?>
                            <article
                                class="stats-overview-card"
                                data-stat-card="<?= htmlspecialchars($metric['key']) ?>"
                                data-stat-direction="<?= htmlspecialchars($metric['direction'] ?? 'positive') ?>">
                                <div class="stats-overview-card__left">
                                    <div class="stats-overview-card__desc"><?= htmlspecialchars($metric['description']) ?></div>
                                    <div class="stats-overview-card__value" data-stat-value="<?= htmlspecialchars($metric['key']) ?>">—</div>
                                    <div class="stats-overview-card__label"><?= htmlspecialchars($metric['label']) ?></div>
                                </div>
                                <div class="stats-overview-card__icon">
                                    <i class="fa-solid <?= htmlspecialchars($metric['icon']) ?> <?= htmlspecialchars($metric['icon_color']) ?>" aria-hidden="true"></i>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                    <div id="overview-error" class="text-danger text-xs mt-3" style="display:none;">Unable to load overview statistics.</div>

                    <!-- Matches list (Overview only) -->
                    <div class="mt-4">
                        <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-2 mb-2">
                            <div>
                                <h4 class="mb-0">Matches</h4>
                                <span class="text-muted text-xs"><?= htmlspecialchars($clubContextName) ?> matches in focus.</span>
                            </div>
                            <span class="text-muted text-xs">All data reflects ready matches only.</span>
                        </div>
                        <?php if (!empty($matches)): ?>
                            <div class="library-list table-responsive">
                                <table class="table table-sm table-bordered mb-0 text-nowrap">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Date</th>
                                            <th>Time</th>
                                            <th>Match</th>
                                            <th class="text-center">Score</th>
                                            <th>Competition</th>
                                            <th>Status</th>
                                            <th class="text-center">Action</th>
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
                                            <td><?= htmlspecialchars($dateLabel) ?></td>
                                            <td><?= htmlspecialchars($timeLabel) ?></td>
                                            <td>
                                                <a href="<?= $matchUrl ?>" class="link-light text-decoration-none">
                                                    <?= htmlspecialchars($title) ?>
                                                </a>
                                            </td>
                                            <td class="text-center">
                                                <span class="fw-semibold <?= $scoreLabel !== '—' ? 'text-success' : 'text-muted' ?>"><?= htmlspecialchars($scoreLabel) ?></span>
                                            </td>
                                            <td><?= htmlspecialchars($competition) ?></td>
                                            <td>
                                                <span class="status-badge status-badge--<?= htmlspecialchars($statusClass) ?>"><?= htmlspecialchars($statusLabel) ?></span>
                                            </td>
                                            <td class="text-center">
                                                <a class="btn btn-sm btn-outline-light" href="<?= $matchUrl ?>">View</a>
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
            <div class="panel panel-dark p-4 mt-4">
                    <div class="d-flex flex-column flex-md-row align-items-start justify-content-between gap-3 mb-3">
                        <div>
                            <h3 class="mb-1">Team Performance</h3>
                            <p class="text-muted-alt text-sm mb-0">Detailed club-level results, home/away splits, and recent form.</p>
                        </div>
                        <span class="text-muted text-xs">All numbers reflect your resolved club.</span>
                    </div>
                <div id="team-performance-content" style="display:none;">
                    <div class="tp-stats-grid" id="team-performance-cards">
                        <?php $performanceCards = [
                            ['label' => 'Matches', 'key' => 'matches_played'],
                            ['label' => 'Wins', 'key' => 'wins'],
                            ['label' => 'Draws', 'key' => 'draws'],
                            ['label' => 'Losses', 'key' => 'losses'],
                            ['label' => 'Goals For', 'key' => 'goals_for'],
                            ['label' => 'Goals Against', 'key' => 'goals_against'],
                            ['label' => 'Goal Difference', 'key' => 'goal_difference'],
                            ['label' => 'Clean Sheets', 'key' => 'clean_sheets'],
                        ]; ?>
                        <?php foreach ($performanceCards as $card): ?>
                            <div class="tp-card">
                                <div class="tp-card__label"><?= htmlspecialchars($card['label']) ?></div>
                                <div class="tp-card__value" data-team-stat="<?= htmlspecialchars($card['key']) ?>">—</div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                        <div class="row g-3 mt-3">
                            <div class="col-12">
                                <div class="panel panel-secondary p-3">
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <h5 class="mb-0 text-light">Home vs Away Record</h5>
                                        <span class="text-muted text-xs">Split by venue</span>
                                    </div>
                                    <div class="table-responsive d-flex justify-content-center">
                                        <table class="table table-sm table-bordered mb-0 text-nowrap tp-home-away-table">
                            <thead class="table-dark">
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
                                            <tbody id="team-performance-home-away">
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
                                <div class="panel panel-secondary p-3">
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <h5 class="mb-0 text-light">Recent Form (Last 5)</h5>
                                        <span class="text-muted text-xs">Latest matches</span>
                                    </div>
                                    <div id="team-performance-form" class="tp-form-grid">
                                        <div class="text-muted text-xs">Loading…</div>
                                    </div>
                                    <div id="team-performance-form-empty" class="text-muted text-xs mt-2" style="display:none;">Add matches to capture your form.</div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="panel panel-secondary p-3">
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <h5 class="mb-0 text-light">Goals &amp; Clean Sheets</h5>
                                        <span class="text-muted text-xs">By competition</span>
                                    </div>
                                    <div class="table-responsive d-flex justify-content-center">
                                        <table class="table table-sm table-bordered mb-0 text-nowrap tp-goals-table">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th>Competition</th>
                                                    <th class="text-center">For</th>
                                                    <th class="text-center">Against</th>
                                                    <th class="text-center">Diff</th>
                                                    <th class="text-center">Clean</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>League</td>
                                                    <td class="text-center" data-league-stat="goals_for">—</td>
                                                    <td class="text-center" data-league-stat="goals_against">—</td>
                                                    <td class="text-center" data-league-stat="goal_difference">—</td>
                                                    <td class="text-center" data-league-stat="clean_sheets">—</td>
                                                </tr>
                                                <tr>
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
                <div class="panel panel-dark p-4 mt-4">
                    <h3 class="mb-3">Player Performance</h3>
                    
                    <!-- Filters -->
                    <div class="d-flex flex-column flex-md-row align-items-start gap-3 mb-3">
                        <div class="flex-fill">
                            <label class="form-label text-muted text-xs mb-1">Filter by Position</label>
                            <select id="player-position-filter" class="form-select form-select-sm">
                                <option value="">All Positions</option>
                            </select>
                        </div>
                        <div class="flex-fill">
                            <label class="form-label text-muted text-xs mb-1">Filter by Type</label>
                            <select id="player-type-filter" class="form-select form-select-sm">
                                <option value="">All Players</option>
                                <option value="starters">Starters Only</option>
                                <option value="subs">Substitutes Only</option>
                            </select>
                        </div>
                    </div>

                    <!-- Table -->
                    <div id="player-performance-content">
                        <div id="player-performance-loading" class="text-muted text-xs">Loading player performance…</div>
                        <div id="player-performance-error" class="text-danger text-xs" style="display:none;">Unable to load player performance.</div>
                        <div id="player-performance-empty" class="text-muted text-xs" style="display:none;">No player data available.</div>
                        
                        <div id="player-performance-table-wrapper" style="display:none;">
                            <div class="table-responsive">
                                <table class="table table-sm table-dark table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th class="sortable" data-sort="name">Player <span class="sort-icon">⇅</span></th>
                                            <th class="sortable" data-sort="position">Position <span class="sort-icon">⇅</span></th>
                                            <th class="text-center sortable" data-sort="appearances">Apps <span class="sort-icon">⇅</span></th>
                                            <th class="text-center sortable" data-sort="starts">Starts <span class="sort-icon">⇅</span></th>
                                            <th class="text-center sortable" data-sort="goals">Goals <span class="sort-icon">⇅</span></th>
                                            <th class="text-center sortable" data-sort="assists">Assists <span class="sort-icon">⇅</span></th>
                                            <th class="text-center sortable" data-sort="cards">Cards <span class="sort-icon">⇅</span></th>
                                            <th class="text-center sortable" data-sort="minutes">Minutes <span class="sort-icon">⇅</span></th>
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
            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });

        panels.forEach((panel) => {
            const panelId = panel.getAttribute('data-panel-id');
            panel.style.display = panelId === tabId ? '' : 'none';
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
        const overviewUrl = apiBase + '/overview';
        let intervalId = null;

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

        function applyStats(data) {
            if (!data || typeof data !== 'object') {
                return;
            }
            Object.keys(statDefinitions).forEach((key) => {
                updateCard(key, data[key]);
            });
            document.getElementById('overview-error').style.display = 'none';
        }

        function fetchStats() {
            fetch(overviewUrl, {
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
                fetchStats();
                intervalId = window.setInterval(fetchStats, 30000);
            },
        };
    })();

    const teamPerformanceModule = (() => {
        const url = apiBase + '/team-performance';
        const loadingEl = document.getElementById('team-performance-loading');
        const errorEl = document.getElementById('team-performance-error');
        const emptyEl = document.getElementById('team-performance-empty');
        const contentEl = document.getElementById('team-performance-content');
        const formContainer = document.getElementById('team-performance-form');
        const formEmptyEl = document.getElementById('team-performance-form-empty');
        const recordBody = document.getElementById('team-performance-home-away');
        const statElements = document.querySelectorAll('[data-team-stat]');
        let initialized = false;

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

        function renderForm(entries = []) {
            if (!formContainer) {
                return;
            }
            formContainer.innerHTML = '';
            if (!Array.isArray(entries) || entries.length === 0) {
                if (formEmptyEl) {
                    formEmptyEl.style.display = '';
                }
                return;
            }
            if (formEmptyEl) {
                formEmptyEl.style.display = 'none';
            }

            entries.forEach((entry) => {
                const pill = document.createElement('div');
                const result = entry.result ?? '—';
                const colorClass = result === 'W' ? 'tp-form-result--W' : result === 'D' ? 'tp-form-result--D' : result === 'L' ? 'tp-form-result--L' : '';
                pill.className = `panel panel-secondary p-2 text-center flex-fill ${colorClass}`;
                const dateLabel = entry.date ? new Date(entry.date).toLocaleDateString() : 'TBD';
                const venueLabel = entry.venue ?? 'Home';
                const opponent = entry.opponent ?? 'Opponent';
                const score = entry.score ?? '—';
                const resultClass = result === 'W' ? 'text-success' : result === 'D' ? 'text-warning' : 'text-danger';
                pill.innerHTML = `
                    <div class="${resultClass} fs-5 fw-bold">${result}</div>
                    <div class="text-muted text-xs">${score}</div>
                    <div class="text-muted text-xs">${venueLabel} · ${dateLabel}</div>
                    <div class="text-muted text-xs">vs ${opponent}</div>
                `;
                formContainer.appendChild(pill);
            });
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
        }

        function fetchStats() {
            showLoadingState();
            fetch(url, {
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
                    showErrorState();
                });
        }

        return {
            init() {
                if (initialized) {
                    return;
                }
                initialized = true;
                fetchStats();
            },
        };
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

            fetch(`${apiBase}/player-performance`)
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
            const positionFilter = document.getElementById('player-position-filter');
            const typeFilter = document.getElementById('player-type-filter');

            positionFilter.addEventListener('change', applyFilters);
            typeFilter.addEventListener('change', applyFilters);
        }

        function applyFilters() {
            const positionFilter = document.getElementById('player-position-filter').value;
            const typeFilter = document.getElementById('player-type-filter').value;

            filteredPlayers = allPlayers.filter(player => {
                if (positionFilter && player.position !== positionFilter) return false;
                if (typeFilter === 'starters' && player.starts === 0) return false;
                if (typeFilter === 'subs' && player.sub_appearances === 0) return false;
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
                cell.colSpan = 8;
                cell.className = 'text-center text-muted';
                cell.textContent = 'No players match the current filters.';
                return;
            }

            filteredPlayers.forEach(player => {
                const row = tbody.insertRow();
                row.innerHTML = `
                    <td>${escapeHtml(player.name)}</td>
                    <td>${escapeHtml(player.position)}</td>
                    <td class="text-center">${player.appearances}</td>
                    <td class="text-center">${player.starts}</td>
                    <td class="text-center">${player.goals > 0 ? '⚽ ' + player.goals : '—'}</td>
                    <td class="text-center">${player.assists > 0 ? player.assists : '—'}</td>
                    <td class="text-center">${formatCards(player.yellow_cards, player.red_cards)}</td>
                    <td class="text-center">${player.minutes_played}</td>
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
        });
    });

    showTab('overview');
})();
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../layout.php';
