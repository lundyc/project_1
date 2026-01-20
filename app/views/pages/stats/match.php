<?php
require_auth();

$base = base_path();
$title = 'Match Statistics';
$match = $match ?? [];
$homeTeam = $match['home_team'] ?? ($match['home_team_name'] ?? 'Home');
$awayTeam = $match['away_team'] ?? ($match['away_team_name'] ?? 'Away');
$competition = $match['competition'] ?? ($match['competition_name'] ?? 'Competition');
$matchStatusLabel = $matchStatusLabel ?? ($match['status'] ?? 'Scheduled');
$matchDateLabel = $matchDateLabel ?? 'TBD';
$matchTimeLabel = $matchTimeLabel ?? 'TBD';
$matchId = (int)($match['id'] ?? 0);

$matchScore = null;
$pairs = [
    ['home_score', 'away_score'],
    ['home_team_score', 'away_team_score'],
    ['home_goals', 'away_goals'],
    ['home_team_goals', 'away_team_goals'],
];
foreach ($pairs as [$homeKey, $awayKey]) {
    if (isset($match[$homeKey], $match[$awayKey])) {
        $homeValue = $match[$homeKey];
        $awayValue = $match[$awayKey];
        if ($homeValue !== null && $awayValue !== null && $homeValue !== '' && $awayValue !== '') {
            $matchScore = sprintf('%s - %s', $homeValue, $awayValue);
            break;
        }
    }
}

ob_start();
?>
<div class="stats-page container mt-4">
    <div class="library-layout">
        <header class="library-layout__header">
            <div>
                <a class="btn btn-link px-0 text-xs text-muted" href="<?= htmlspecialchars($base) ?>/stats">← Back to club dashboard</a>
                <h1 class="library-layout__title mb-0">Match Statistics</h1>
                <p class="library-layout__description mb-0">Match-level context for <?= htmlspecialchars($homeTeam) ?> vs <?= htmlspecialchars($awayTeam) ?>.</p>
            </div>
            <div class="library-layout__header-actions">
                <span class="text-muted text-xs">Match ID <?= $matchId ?: 'N/A' ?></span>
            </div>
        </header>

        <div class="panel panel-secondary p-4 match-context mb-4">
        <div class="row g-3">
            <div class="col-12 col-md-6">
                <div class="text-muted text-xs">Match</div>
                <div class="fs-4 fw-semibold"><?= htmlspecialchars($homeTeam) ?> vs <?= htmlspecialchars($awayTeam) ?></div>
                <div class="text-muted text-xs"><?= htmlspecialchars($competition) ?></div>
            </div>
            <div class="col-12 col-md-3">
                <div class="text-muted text-xs">Kickoff</div>
                <div class="fs-5 fw-semibold"><?= htmlspecialchars($matchDateLabel) ?> · <?= htmlspecialchars($matchTimeLabel) ?></div>
            </div>
            <div class="col-12 col-md-3 text-md-end">
                <div class="text-muted text-xs">Status</div>
                <div class="fs-5 fw-semibold text-uppercase" id="match-header-status"><?= htmlspecialchars($matchStatusLabel) ?></div>
                <?php if ($matchScore): ?>
                    <div class="text-muted text-xs">Score</div>
                    <div class="fs-5 fw-semibold" id="match-header-score"><?= htmlspecialchars($matchScore) ?></div>
                <?php else: ?>
                    <div class="text-muted text-xs">Score</div>
                    <div class="fs-5 fw-semibold" id="match-header-score">—</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="library-tabs-row mb-3">
        <nav class="library-tabs stats-tabs" role="tablist" aria-label="Match statistics tabs">
            <?php $tabs = [
                'match-overview' => 'Overview',
                'match-team-performance' => 'Team Performance',
                'match-player-performance' => 'Player Performance',
                'match-visual-analytics' => 'Visual Analytics',
            ]; ?>
            <?php foreach ($tabs as $tabId => $tabLabel): ?>
                <button
                    type="button"
                    class="library-tab btn btn-sm btn-outline-light stats-tab <?= $tabId === 'match-overview' ? 'is-active' : '' ?>"
                    role="tab"
                    aria-selected="<?= $tabId === 'match-overview' ? 'true' : 'false' ?>"
                    data-tab-id="<?= htmlspecialchars($tabId) ?>">
                    <?= htmlspecialchars($tabLabel) ?>
                </button>
            <?php endforeach; ?>
        </nav>
        <div class="library-tabs-row__right">
            <span class="text-muted text-xs">Match context locked to this route</span>
        </div>
    </div>

    <div class="stats-panels">
        <section id="match-overview-panel" data-panel-id="match-overview">
            <div class="panel panel-dark p-4">
                <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                    <div>
                        <h3 class="mb-1">Overview</h3>
                        <p class="text-muted-alt text-sm mb-0">Match-specific metrics powered by explicit match_id.</p>
                    </div>
                    <div class="text-end text-muted text-xs" id="match-overview-meta">Loading match details…</div>
                </div>

                <div class="match-overview-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
                    <div>
                        <div class="text-muted text-xs">Match</div>
                        <div class="fs-4 fw-semibold" id="match-overview-title"><?= htmlspecialchars($homeTeam) ?> vs <?= htmlspecialchars($awayTeam) ?></div>
                        <div class="text-muted text-xs" id="match-overview-kickoff"><?= htmlspecialchars($matchDateLabel) ?> · <?= htmlspecialchars($matchTimeLabel) ?></div>
                    </div>
                    <div class="text-md-end">
                        <div class="text-muted text-xs">Score</div>
                        <div class="fs-2 fw-bold" id="match-overview-score"><?= htmlspecialchars($matchScore ?? '—') ?></div>
                        <div class="text-muted text-xs" id="match-overview-status"><?= htmlspecialchars($matchStatusLabel) ?></div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                        <div class="panel panel-secondary p-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-muted text-xs">Goals</span>
                                <span class="fs-6 fw-semibold" id="match-overview-goals-label">—</span>
                            </div>
                            <div class="stats-bar" aria-hidden="true">
                                <div class="stats-bar-fill stats-bar-fill-home" data-bar="goals" data-side="home"></div>
                            </div>
                            <div class="stats-bar" aria-hidden="true">
                                <div class="stats-bar-fill stats-bar-fill-away" data-bar="goals" data-side="away"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="panel panel-secondary p-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-muted text-xs">Shots</span>
                                <span class="fs-6 fw-semibold" id="match-overview-shots-label">—</span>
                            </div>
                            <div class="stats-bar" aria-hidden="true">
                                <div class="stats-bar-fill stats-bar-fill-home" data-bar="shots" data-side="home"></div>
                            </div>
                            <div class="stats-bar" aria-hidden="true">
                                <div class="stats-bar-fill stats-bar-fill-away" data-bar="shots" data-side="away"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Metric</th>
                                <th class="text-center">Home</th>
                                <th class="text-center">Away</th>
                            </tr>
                        </thead>
                        <tbody id="match-overview-table-body"></tbody>
                    </table>
                </div>

                <div id="match-overview-loading" class="text-muted text-xs mt-3">Loading match statistics…</div>
                <div id="match-overview-error" class="text-danger text-xs mt-3" style="display:none;">Unable to load match overview.</div>
            </div>
        </section>

        <section id="match-team-performance-panel" data-panel-id="match-team-performance" style="display:none;">
            <div class="panel panel-dark p-4">
                <h3 class="mb-3">Team Performance</h3>
                <p class="text-muted-alt text-sm mb-4">Home vs away event counts for this match.</p>
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Metric</th>
                                <th class="text-center">Home</th>
                                <th class="text-center">Away</th>
                            </tr>
                        </thead>
                        <tbody id="match-team-performance-table"></tbody>
                    </table>
                </div>
                <div id="match-team-performance-loading" class="text-muted text-xs mt-3">Loading team performance…</div>
                <div id="match-team-performance-error" class="text-danger text-xs mt-3" style="display:none;">Unable to load team performance.</div>
            </div>
        </section>

        <section id="match-player-performance-panel" data-panel-id="match-player-performance" style="display:none;">
            <div class="panel panel-dark p-4">
                <h3 class="mb-3">Player Performance</h3>
                
                <div id="match-player-performance-loading" class="text-muted text-xs">Loading player performance…</div>
                <div id="match-player-performance-error" class="text-danger text-xs" style="display:none;">Unable to load player performance.</div>
                <div id="match-player-performance-empty" class="text-muted text-xs" style="display:none;">No player data available for this match.</div>
                
                <div id="match-player-performance-content" style="display:none;">
                    <!-- Starting XI -->
                    <div class="mb-4">
                        <h4 class="h6 mb-3">Starting XI</h4>
                        <div class="table-responsive">
                            <table class="table table-sm table-dark table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 60px;" class="text-center">#</th>
                                        <th>Player</th>
                                        <th style="width: 100px;">Position</th>
                                        <th class="text-center" style="width: 80px;">Goals</th>
                                        <th class="text-center" style="width: 80px;">Cards</th>
                                    </tr>
                                </thead>
                                <tbody id="match-starting-xi-tbody">
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Substitutes -->
                    <div id="match-substitutes-section" style="display:none;">
                        <h4 class="h6 mb-3">Substitutes</h4>
                        <div class="table-responsive">
                            <table class="table table-sm table-dark table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 60px;" class="text-center">#</th>
                                        <th>Player</th>
                                        <th style="width: 100px;">Position</th>
                                        <th class="text-center" style="width: 80px;">Goals</th>
                                        <th class="text-center" style="width: 80px;">Cards</th>
                                    </tr>
                                </thead>
                                <tbody id="match-substitutes-tbody">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="match-visual-analytics-panel" data-panel-id="match-visual-analytics" style="display:none;">
            <div class="panel panel-dark p-4">
                <h3 class="mb-3">Visual Analytics</h3>
                <p class="text-muted-alt text-sm mb-4">Shot maps, heat maps, possession zones and pass networks will appear here.</p>
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="panel panel-secondary p-4 text-center" style="min-height: 180px;">
                            <p class="text-muted small mb-2">Shot Map</p>
                            <span class="text-muted small">Placeholder</span>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="panel panel-secondary p-4 text-center" style="min-height: 180px;">
                            <p class="text-muted small mb-2">Heat Map</p>
                            <span class="text-muted small">Placeholder</span>
                        </div>
                    </div>
                </div>
                <div class="row g-3 mt-3">
                    <div class="col-12 col-md-6">
                        <div class="panel panel-secondary p-4 text-center" style="min-height: 140px;">
                            <p class="text-muted small mb-2">Possession Zones</p>
                            <span class="text-muted small">Placeholder</span>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="panel panel-secondary p-4 text-center" style="min-height: 140px;">
                            <p class="text-muted small mb-2">Pass Network</p>
                            <span class="text-muted small">Placeholder</span>
                        </div>
                    </div>
                </div>
                <div id="match-visual-analytics-status" class="text-muted text-xs mt-3">Loading visual analytics…</div>
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
.stats-page .panel-secondary {
    border-color: rgba(255, 255, 255, 0.08);
}
.match-context {
    border-radius: 16px;
    border: 1px solid rgba(255, 255, 255, 0.08);
}
.stats-bar {
    position: relative;
    height: 8px;
    border-radius: 999px;
    background-color: rgba(255, 255, 255, 0.08);
    margin-bottom: 6px;
}
.stats-bar-fill {
    position: absolute;
    inset: 0;
    border-radius: inherit;
    width: 0;
    transition: width 0.3s ease;
}
.stats-bar-fill-home {
    background-color: rgba(16, 185, 129, 0.8);
}
.stats-bar-fill-away {
    background-color: rgba(248, 113, 113, 0.8);
}
.panel-dark {
    background-color: rgba(15, 23, 42, 0.8);
    border-color: rgba(255, 255, 255, 0.08);
    border-radius: 12px;
}
</style>

<script>
(function () {
    const baseMeta = document.querySelector('meta[name="base-path"]');
    const basePath = baseMeta ? baseMeta.content.trim().replace(/^\/+/, '').replace(/\/+$/, '') : '';
    const apiBase = (basePath ? '/' + basePath : '') + '/api/stats/match';
    const matchId = <?= json_encode($matchId) ?>;
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

    const eventMetrics = [
        { key: 'goals', label: 'Goals' },
        { key: 'shots', label: 'Shots' },
        { key: 'corners', label: 'Corners' },
        { key: 'free_kicks', label: 'Free kicks' },
        { key: 'penalties', label: 'Penalties' },
        { key: 'fouls', label: 'Fouls' },
        { key: 'yellow_cards', label: 'Yellow cards' },
        { key: 'red_cards', label: 'Red cards' },
        { key: 'substitutions', label: 'Substitutions' },
    ];

    const matchOverviewModule = (() => {
        const url = `${apiBase}/overview?match_id=${encodeURIComponent(matchId)}`;
        const loadingEl = document.getElementById('match-overview-loading');
        const errorEl = document.getElementById('match-overview-error');
        const titleEl = document.getElementById('match-overview-title');
        const kickoffEl = document.getElementById('match-overview-kickoff');
        const scoreEl = document.getElementById('match-overview-score');
        const statusEl = document.getElementById('match-overview-status');
        const tableBody = document.getElementById('match-overview-table-body');
        const goalsLabel = document.getElementById('match-overview-goals-label');
        const shotsLabel = document.getElementById('match-overview-shots-label');

        let initialized = false;

        function updateBar(metric, stats) {
            const homeBar = document.querySelector(`[data-bar="${metric}"][data-side="home"]`);
            const awayBar = document.querySelector(`[data-bar="${metric}"][data-side="away"]`);
            const homeValue = stats?.home?.[metric] ?? 0;
            const awayValue = stats?.away?.[metric] ?? 0;
            const maxValue = Math.max(homeValue, awayValue, 1);

            if (homeBar) {
                homeBar.style.width = `${Math.min(100, (homeValue / maxValue) * 100)}%`;
            }
            if (awayBar) {
                awayBar.style.width = `${Math.min(100, (awayValue / maxValue) * 100)}%`;
            }
        }

        function renderTable(stats) {
            if (!tableBody) {
                return;
            }
            const rows = eventMetrics.map((metric) => {
                const homeValue = stats?.home?.[metric.key] ?? 0;
                const awayValue = stats?.away?.[metric.key] ?? 0;
                return `
                    <tr>
                        <td>${metric.label}</td>
                        <td class="text-center">${Number(homeValue).toLocaleString()}</td>
                        <td class="text-center">${Number(awayValue).toLocaleString()}</td>
                    </tr>
                `;
            }).join('');
            tableBody.innerHTML = rows;
        }

        function applyStats(data) {
            const stats = data?.stats || {};
            const matchMeta = data?.match;
            if (matchMeta) {
                titleEl.textContent = `${matchMeta.home_team} vs ${matchMeta.away_team}`;
                const date = matchMeta.date || 'TBD';
                const time = matchMeta.time || 'TBD';
                kickoffEl.textContent = `${date} · ${time}`;
                statusEl.textContent = matchMeta.status || 'Scheduled';
            }

            const homeGoals = stats?.home?.goals ?? 0;
            const awayGoals = stats?.away?.goals ?? 0;
            const homeShots = stats?.home?.shots ?? 0;
            const awayShots = stats?.away?.shots ?? 0;

            goalsLabel.textContent = `${homeGoals} - ${awayGoals}`;
            shotsLabel.textContent = `${homeShots} - ${awayShots}`;

            updateBar('goals', stats);
            updateBar('shots', stats);

            renderTable(stats);
            loadingEl.style.display = 'none';
            errorEl.style.display = 'none';
        }

        function fetchStats() {
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
                    console.error('[Match Overview]', error);
                    loadingEl.style.display = 'none';
                    errorEl.style.display = '';
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

    const matchTeamPerformanceModule = (() => {
        const url = `${apiBase}/team-performance?match_id=${encodeURIComponent(matchId)}`;
        const tableBody = document.getElementById('match-team-performance-table');
        const loadingEl = document.getElementById('match-team-performance-loading');
        const errorEl = document.getElementById('match-team-performance-error');
        let initialized = false;

        function renderStats(stats) {
            if (!tableBody) {
                return;
            }
            const rows = eventMetrics.map((metric) => {
                const homeValue = stats?.home?.[metric.key] ?? 0;
                const awayValue = stats?.away?.[metric.key] ?? 0;
                return `
                    <tr>
                        <td>${metric.label}</td>
                        <td class="text-center">${Number(homeValue).toLocaleString()}</td>
                        <td class="text-center">${Number(awayValue).toLocaleString()}</td>
                    </tr>
                `;
            }).join('');
            tableBody.innerHTML = rows;
        }

        function fetchStats() {
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
                        renderStats(payload.data.stats);
                        loadingEl.style.display = 'none';
                        errorEl.style.display = 'none';
                        return;
                    }
                    throw new Error(payload.error || 'Invalid payload');
                })
                .catch((error) => {
                    console.error('[Match Team Performance]', error);
                    loadingEl.style.display = 'none';
                    errorEl.style.display = '';
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

    const matchPlayerPerformanceModule = (() => {
        const url = `${apiBase}/player-performance?match_id=${encodeURIComponent(matchId)}`;
        const loadingEl = document.getElementById('match-player-performance-loading');
        const errorEl = document.getElementById('match-player-performance-error');
        const emptyEl = document.getElementById('match-player-performance-empty');
        const contentEl = document.getElementById('match-player-performance-content');
        let initialized = false;

        function fetchStats() {
            loadingEl.style.display = 'block';
            errorEl.style.display = 'none';
            emptyEl.style.display = 'none';
            contentEl.style.display = 'none';

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
                    loadingEl.style.display = 'none';
                    
                    if (payload.success) {
                        const startingXI = payload.starting_xi || [];
                        const substitutes = payload.substitutes || [];
                        
                        if (startingXI.length === 0 && substitutes.length === 0) {
                            emptyEl.style.display = 'block';
                            return;
                        }
                        
                        renderStartingXI(startingXI);
                        renderSubstitutes(substitutes);
                        contentEl.style.display = 'block';
                    } else {
                        throw new Error(payload.error || 'Invalid payload');
                    }
                })
                .catch((error) => {
                    console.error('[Match Player Performance]', error);
                    loadingEl.style.display = 'none';
                    errorEl.style.display = 'block';
                });
        }

        function renderStartingXI(players) {
            const tbody = document.getElementById('match-starting-xi-tbody');
            tbody.innerHTML = '';
            
            if (players.length === 0) {
                const row = tbody.insertRow();
                const cell = row.insertCell();
                cell.colSpan = 5;
                cell.className = 'text-center text-muted';
                cell.textContent = 'No starting XI data available';
                return;
            }
            
            players.forEach(player => {
                const row = tbody.insertRow();
                row.innerHTML = `
                    <td class="text-center">${player.shirt_number || '—'}</td>
                    <td>${escapeHtml(player.name)}${player.is_captain ? ' <span class="badge bg-warning text-dark" style="font-size: 0.7rem;">C</span>' : ''}</td>
                    <td>${escapeHtml(player.position)}</td>
                    <td class="text-center">${player.goals > 0 ? '⚽ ' + player.goals : '—'}</td>
                    <td class="text-center">${formatCards(player.yellow_cards, player.red_cards)}</td>
                `;
            });
        }

        function renderSubstitutes(players) {
            const section = document.getElementById('match-substitutes-section');
            const tbody = document.getElementById('match-substitutes-tbody');
            tbody.innerHTML = '';
            
            if (players.length === 0) {
                section.style.display = 'none';
                return;
            }
            
            section.style.display = 'block';
            
            players.forEach(player => {
                const row = tbody.insertRow();
                row.innerHTML = `
                    <td class="text-center">${player.shirt_number || '—'}</td>
                    <td>${escapeHtml(player.name)}</td>
                    <td>${escapeHtml(player.position)}</td>
                    <td class="text-center">${player.goals > 0 ? '⚽ ' + player.goals : '—'}</td>
                    <td class="text-center">${formatCards(player.yellow_cards, player.red_cards)}</td>
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

    const matchVisualsModule = (() => {
        const url = `${apiBase}/visuals?match_id=${encodeURIComponent(matchId)}`;
        const statusEl = document.getElementById('match-visual-analytics-status');
        let initialized = false;

        function fetchStats() {
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
                    if (payload.success) {
                        statusEl.textContent = payload.data.visuals?.status ? `Status: ${payload.data.visuals.status}` : 'Visual analytics placeholder.';
                        return;
                    }
                    throw new Error(payload.error || 'Invalid payload');
                })
                .catch((error) => {
                    console.error('[Match Visuals]', error);
                    statusEl.textContent = 'Unable to load visual analytics.';
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

    const tabModules = {
        'match-overview': matchOverviewModule,
        'match-team-performance': matchTeamPerformanceModule,
        'match-player-performance': matchPlayerPerformanceModule,
        'match-visual-analytics': matchVisualsModule,
    };

    tabButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const tabId = button.getAttribute('data-tab-id');
            showTab(tabId);
        });
    });

    showTab('match-overview');
})();
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../layout.php';
