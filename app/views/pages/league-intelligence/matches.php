
<?php ob_start(); ?>
<div class="space-y-6">
    <header class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-white">League Intelligence Matches</h1>
        <a href="/league-intelligence/matches/add" class="rounded bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 text-sm">Add Match</a>
    </header>

    <!-- Filters -->
    <div class="flex flex-wrap gap-2 mb-4">
        <select id="filter-team" class="rounded border border-slate-600 bg-slate-800 text-slate-100 px-2 py-1 text-xs">
            <option value="">All Teams</option>
            <?php foreach ($teams as $team): ?>
                <option value="<?= htmlspecialchars($team['id']) ?>"><?= htmlspecialchars($team['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select id="filter-competition" class="rounded border border-slate-600 bg-slate-800 text-slate-100 px-2 py-1 text-xs">
            <option value="">All Competitions</option>
            <!-- Options will be populated by JS -->
        </select>
        <select id="filter-status" class="rounded border border-slate-600 bg-slate-800 text-slate-100 px-2 py-1 text-xs">
            <option value="">All Statuses</option>
            <?php foreach ($statuses as $status): ?>
                <option value="<?= htmlspecialchars($status) ?>"><?= ucfirst($status) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <table class="min-w-full bg-slate-900 text-slate-100 text-xs rounded-lg overflow-hidden">
        <thead>
            <tr class="bg-slate-800 text-slate-300">
                <th class="px-2 py-2">#</th>
                <th class="px-2 py-2">Date</th>
                <th class="px-2 py-2">Competition</th>
                <th class="px-2 py-2">Home</th>
                <th class="px-2 py-2">Away</th>
                <th class="px-2 py-2">HG</th>
                <th class="px-2 py-2">AG</th>
                <th class="px-2 py-2">Status</th>
                <th class="px-2 py-2">Actions</th>
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
                <tr id="<?= $rowId ?>" data-match-id="<?= htmlspecialchars($m['match_id']) ?>" data-team="<?= htmlspecialchars($m['home_team_id']) ?>,<?= htmlspecialchars($m['away_team_id']) ?>" data-competition="<?= htmlspecialchars($m['competition_id']) ?>" data-status="<?= htmlspecialchars($m['status']) ?>" class="border-b border-slate-700 cursor-pointer transition-colors match-row">
                    <td class="px-2 py-1 text-center"><?= htmlspecialchars($m['match_id']) ?></td>
                    <td class="px-2 py-1"><?= $date ?></td>
                    <td class="px-2 py-1"><?= htmlspecialchars($competition) ?></td>
                    <td class="px-2 py-1"><?= htmlspecialchars($home) ?></td>
                    <td class="px-2 py-1"><?= htmlspecialchars($away) ?></td>
                    <td class="px-2 py-1 text-center"><?= $hg ?></td>
                    <td class="px-2 py-1 text-center"><?= $ag ?></td>
                    <td class="px-2 py-1 text-center"><?= htmlspecialchars($m['status']) ?></td>
                    <td class="px-2 py-1">
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
    filterRows();

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
