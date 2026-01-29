
<?php ob_start(); ?>
<div class="w-full mt-4 text-slate-200">
    <div class="max-w-full">
        <div class="grid grid-cols-12 gap-2 px-4 md:px-6 lg:px-8 w-full">
            <!-- Left Sidebar: Filters -->
            <aside class="col-span-2 space-y-4 min-w-0">
                <div class="space-y-2">
                    <h2 class="text-xs uppercase tracking-[0.3em] text-slate-400 font-semibold mb-2">Filters</h2>
                    <form class="flex flex-col gap-3" role="search" onsubmit="return false;">
                        <label class="block text-slate-400 text-xs mb-1">Team
                            <select id="filter-team" class="block w-full rounded-md bg-slate-900/60 border border-white/20 px-2 py-1 text-xs">
                                <option value="">All Teams</option>
                                <?php foreach ($teams as $team): ?>
                                    <option value="<?= htmlspecialchars($team['id']) ?>"><?= htmlspecialchars($team['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="block text-slate-400 text-xs mb-1">Competition
                            <select id="filter-competition" class="block w-full rounded-md bg-slate-900/60 border border-white/20 px-2 py-1 text-xs">
                                <option value="">All Competitions</option>
                                <!-- Options will be populated by JS -->
                            </select>
                        </label>
                        <label class="block text-slate-400 text-xs mb-1">Status
                            <select id="filter-status" class="block w-full rounded-md bg-slate-900/60 border border-white/20 px-2 py-1 text-xs">
                                <option value="">All Statuses</option>
                                <?php foreach ($statuses as $status): ?>
                                    <option value="<?= htmlspecialchars($status) ?>"><?= ucfirst($status) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </form>
                </div>
            </aside>
            <!-- Main Content -->
            <main class="col-span-7 space-y-5 min-w-0">
                <header class="flex items-center justify-between">
                    <h1 class="text-2xl font-semibold text-white">League Intelligence Matches</h1>
                    <a href="/league-intelligence/matches/add" class="rounded bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 text-sm">Add Match</a>
                </header>
                <div>
                    <table class="min-w-full bg-bg-tertiary text-text-primary text-xs rounded-xl overflow-hidden">
                        <thead>
                            <tr class="bg-bg-secondary text-text-muted uppercase font-semibold text-xs">
                                <th class="px-4 py-3">#</th>
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3">Competition</th>
                                <th class="px-4 py-3">Home</th>
                                <th class="px-4 py-3">Away</th>
                                <th class="px-4 py-3">HG</th>
                                <th class="px-4 py-3">AG</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Filtering logic
                            // We'll render all rows, but hide/show with JS for live filtering
                            foreach ($matches as $m):
                                // UK date format: DD/MM/YY @ HH:MM
                                $date = $m['kickoff_at'] ? date('d/m/y @ H:i', strtotime($m['kickoff_at'])) : '-';
                                $home = $m['home_team_name'] ?? $m['home_team_id'];
                                $away = $m['away_team_name'] ?? $m['away_team_id'];
                                $hg = ($m['home_goals'] === null || $m['home_goals'] === '') ? '-' : htmlspecialchars($m['home_goals']);
                                $ag = ($m['away_goals'] === null || $m['away_goals'] === '') ? '-' : htmlspecialchars($m['away_goals']);
                                $competition = $m['competition_name'] ?? '-';
                                $rowId = 'match-row-' . htmlspecialchars($m['match_id']);
                            ?>
                                <tr id="<?= $rowId ?>" data-match-id="<?= htmlspecialchars($m['match_id']) ?>" data-team="<?= htmlspecialchars($m['home_team_id']) ?>,<?= htmlspecialchars($m['away_team_id']) ?>" data-competition="<?= htmlspecialchars($m['competition_id']) ?>" data-status="<?= htmlspecialchars($m['status']) ?>" class="border-b border-border-soft hover:bg-bg-secondary/60 cursor-pointer transition-colors match-row">
                                    <td class="px-4 py-2 text-center"><?= htmlspecialchars($m['match_id']) ?></td>
                                    <td class="px-4 py-2"><?= $date ?></td>
                                    <td class="px-4 py-2"><?= htmlspecialchars($competition) ?></td>
                                    <td class="px-4 py-2"><?= htmlspecialchars($home) ?></td>
                                    <td class="px-4 py-2"><?= htmlspecialchars($away) ?></td>
                                    <td class="px-4 py-2 text-center"><?= $hg ?></td>
                                    <td class="px-4 py-2 text-center"><?= $ag ?></td>
                                    <td class="px-4 py-2 text-center"><?= htmlspecialchars($m['status']) ?></td>
                                    <td class="px-4 py-2">
                                        <a href="/league-intelligence/matches/edit/<?= urlencode($m['match_id']) ?>" class="text-indigo-400 hover:underline mr-2">Edit</a>
                                        <form action="/league-intelligence/matches/delete/<?= urlencode($m['match_id']) ?>" method="post" style="display:inline" onsubmit="return confirm('Delete this match?');">
                                            <input type="hidden" name="_redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
                                            <button type="submit" class="text-rose-400 hover:underline">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </main>
            <!-- Right Sidebar: Contextual Panels (empty for now) -->
            <aside class="col-span-3 min-w-0"></aside>
        </div>
    </div>
</div>
<style>
    .match-row.selected {
            background-color: #065f46 !important; /* emerald-800 */

    }
    .match-row:hover {
    background-color: #1f473b !important; /* emerald-600 */
    }
</style>
<script>
// Live filtering and tick persistence
document.addEventListener('DOMContentLoaded', function() {
    const storageKey = 'li_match_ticks';
    let ticks = {};
    try {
        ticks = JSON.parse(localStorage.getItem(storageKey)) || {};
    } catch (e) { ticks = {}; }

    // Restore ticked rows
    Object.keys(ticks).forEach(function(matchId) {
        if (ticks[matchId]) {
            const row = document.querySelector('tr[data-match-id="' + matchId + '"]');
            if (row) row.classList.add('selected');
        }
    });

    // Filtering logic
    const teamSelect = document.getElementById('filter-team');
    const compSelect = document.getElementById('filter-competition');
    const statusSelect = document.getElementById('filter-status');
    const allRows = Array.from(document.querySelectorAll('tr.match-row'));

    // Build a map: teamId => [competitionId => competitionName]
    const teamComps = {};
    allRows.forEach(row => {
        const homeAway = row.getAttribute('data-team').split(',');
        const compId = row.getAttribute('data-competition');
        const compName = row.querySelector('td:nth-child(3)').textContent.trim();
        homeAway.forEach(tid => {
            if (!teamComps[tid]) teamComps[tid] = {};
            teamComps[tid][compId] = compName;
        });
    });

    function updateCompetitionOptions() {
        const teamId = teamSelect.value;
        compSelect.innerHTML = '<option value="">All Competitions</option>';
        let comps = {};
        if (teamId && teamComps[teamId]) {
            comps = teamComps[teamId];
        } else {
            // All competitions
            allRows.forEach(row => {
                comps[row.getAttribute('data-competition')] = row.querySelector('td:nth-child(3)').textContent.trim();
            });
        }
        Object.entries(comps).forEach(([id, name]) => {
            if (id && name && compSelect.querySelector('option[value="'+id+'"]') === null) {
                const opt = document.createElement('option');
                opt.value = id;
                opt.textContent = name;
                compSelect.appendChild(opt);
            }
        });
    }

    function syncUrlFilters() {
        const params = new URLSearchParams();
        if (teamSelect.value) params.set('team_id', teamSelect.value);
        if (compSelect.value) params.set('competition_id', compSelect.value);
        if (statusSelect.value) params.set('status', statusSelect.value);
        const query = params.toString();
        const nextUrl = query ? `${window.location.pathname}?${query}` : window.location.pathname;
        window.history.replaceState({}, '', nextUrl);
    }

    function filterRows() {
        const teamId = teamSelect.value;
        const compId = compSelect.value;
        const status = statusSelect.value;
        allRows.forEach(row => {
            const [home, away] = row.getAttribute('data-team').split(',');
            const rowComp = row.getAttribute('data-competition');
            const rowStatus = row.getAttribute('data-status');
            let show = true;
            if (teamId && home !== teamId && away !== teamId) show = false;
            if (show && compId && rowComp !== compId) show = false;
            if (show && status && rowStatus !== status) show = false;
            row.style.display = show ? '' : 'none';
        });
        syncUrlFilters();
    }

    // When team changes, update competitions and filter
    teamSelect.addEventListener('change', function() {
        updateCompetitionOptions();
        filterRows();
    });
    // When competition or status changes, filter
    compSelect.addEventListener('change', filterRows);
    statusSelect.addEventListener('change', filterRows);

    // Initial population
    updateCompetitionOptions();
    // Restore filters from URL if present
    const params = new URLSearchParams(window.location.search);
    const teamParam = params.get('team_id');
    const compParam = params.get('competition_id');
    const statusParam = params.get('status');
    if (teamParam) {
        teamSelect.value = teamParam;
    }
    updateCompetitionOptions();
    if (compParam) {
        compSelect.value = compParam;
    }
    if (statusParam) {
        statusSelect.value = statusParam;
    }
    filterRows();

    // Ensure delete redirects keep current filters
    document.addEventListener('submit', function (event) {
        const form = event.target;
        if (!form || form.getAttribute('action')?.indexOf('/league-intelligence/matches/delete/') !== 0) return;
        const redirectInput = form.querySelector('input[name="_redirect"]');
        if (!redirectInput) return;
        redirectInput.value = window.location.pathname + window.location.search;
    }, true);

    // Tick system
    allRows.forEach(function(row) {
        row.addEventListener('click', function(e) {
            if (e.target.tagName === 'A' || e.target.tagName === 'BUTTON' || e.target.closest('form')) return;
            row.classList.toggle('selected');
            const matchId = row.getAttribute('data-match-id');
            if (row.classList.contains('selected')) {
                ticks[matchId] = true;
            } else {
                delete ticks[matchId];
            }
            localStorage.setItem(storageKey, JSON.stringify(ticks));
        });
    });
});
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../layout.php';
?>
