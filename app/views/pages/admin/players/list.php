<?php
require_once __DIR__ . '/../../../../lib/auth.php';
require_once __DIR__ . '/../../../../lib/player_repository.php';
require_once __DIR__ . '/../../../../lib/team_repository.php';
require_once __DIR__ . '/../../../../lib/season_repository.php';
require_once __DIR__ . '/../../../../lib/club_repository.php';
require_once __DIR__ . '/../../../../lib/player_name_helper.php';
require_once __DIR__ . '/../../../../lib/csrf.php';

$context = require_club_admin_access();
$user = $context['user'];
$clubId = $context['club_id'];
$isPlatformAdmin = in_array('platform_admin', $context['roles'], true);
$clubs = $isPlatformAdmin ? get_all_clubs() : [];

$filters = [
          'active' => $_GET['active'] ?? '',
          'team_id' => $_GET['team_id'] ?? '',
          'season_id' => $_GET['season_id'] ?? '',
          'club_id' => $isPlatformAdmin ? (isset($_GET['club_id']) ? (int)$_GET['club_id'] : $clubId) : $clubId,
];

$teams = get_teams_by_club($clubId);
$seasons = get_seasons_by_club($clubId);
$players = get_players_for_club($clubId, $filters);

$success = $_SESSION['player_flash_success'] ?? null;
$error = $_SESSION['player_flash_error'] ?? null;
unset($_SESSION['player_flash_success'], $_SESSION['player_flash_error']);

$csrfToken = get_csrf_token();

$title = 'Players';
$base = base_path();

ob_start();

$headerTitle = 'Players';
$headerDescription = 'Description here';
    $headerButtons[] = '<a href="' . htmlspecialchars($base) . '/admin/players/create" class="stats-tab w-full justify-start text-left px-4 py-2.5 text-sm font-medium rounded-lg border transition-all duration-200 bg-indigo-600 border-indigo-500 text-white shadow-lg shadow-indigo-500/20 flex">Create Player</a>';
include __DIR__ . '/../../../partials/header.php';
        ?>
<link rel="stylesheet" href="/assets/css/stats-table.css">
<div class="stats-page w-full mt-4 text-slate-200">
    <div class="max-w-full">
        <!-- Standard 3-Column Layout: grid-cols-12 -->
        <div class="grid grid-cols-12 gap-2 px-4 md:px-6 lg:px-8 w-full">
            <!-- Left: Filters -->
            <aside class="col-span-2 space-y-4 min-w-0">
                     <div class="rounded-xl bg-slate-900/80 border border-white/10 p-3">
                <form method="get" class="space-y-4">
                    <?php if ($isPlatformAdmin): ?>
                        <div>
                            <label class="block text-slate-400 text-xs mb-1">Club</label>
                            <select name="club_id" class="block w-full rounded-md bg-slate-900/60 border border-white/20 px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-white/30">
                                <?php foreach ($clubs as $club): ?>
                                    <option value="<?= (int)$club['id'] ?>" <?= (int)$filters['club_id'] === (int)$club['id'] ? 'selected' : '' ?>><?= htmlspecialchars($club['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    <div>
                        <label class="block text-slate-400 text-xs mb-1">Status</label>
                        <select name="active" class="block w-full rounded-md bg-slate-900/60 border border-white/20 px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-white/30">
                            <option value="">All players</option>
                            <option value="1" <?= $filters['active'] === '1' ? 'selected' : '' ?>>Active</option>
                            <option value="0" <?= $filters['active'] === '0' ? 'selected' : '' ?>>Inactive</option>
                        </select>
          </div>
                    <div>
                        <label class="block text-slate-400 text-xs mb-1">Team</label>
                        <select name="team_id" class="block w-full rounded-md bg-slate-900/60 border border-white/20 px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-white/30">
                            <option value="">All teams</option>
                            <?php foreach ($teams as $team): ?>
                                <?php if ((isset($team['team_type']) && $team['team_type'] === 'club')): ?>
                                    <option value="<?= (int)$team['id'] ?>" <?= $filters['team_id'] === (string)$team['id'] ? 'selected' : '' ?>><?= htmlspecialchars($team['name']) ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-slate-400 text-xs mb-1">Season</label>
                        <select name="season_id" class="block w-full rounded-md bg-slate-900/60 border border-white/20 px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-white/30">
                            <option value="">All seasons</option>
                            <?php foreach ($seasons as $season): ?>
                                <option value="<?= (int)$season['id'] ?>" <?= $filters['season_id'] === (string)$season['id'] ? 'selected' : '' ?>><?= htmlspecialchars($season['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- Auto-submit on change, no button needed -->
                </form>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    var form = document.querySelector('aside form');
                    if (form) {
                        form.querySelectorAll('select').forEach(function(select) {
                            select.addEventListener('change', function() {
                                form.submit();
                            });
                        });
                    }
                });
                </script>

                            </div>
            </aside>
            <!-- Center: Players List -->
            <main class="col-span-7 space-y-4 min-w-0">
                <?php if ($error): ?>
                    <div class="rounded-lg bg-red-900/80 border border-red-700 text-red-200 px-4 py-3 mb-4 text-sm"><?= htmlspecialchars($error) ?></div>
                <?php elseif ($success): ?>
                    <div class="rounded-lg bg-emerald-900/80 border border-emerald-700 text-emerald-200 px-4 py-3 mb-4 text-sm"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
                <?php if (empty($players)): ?>
                    <div class="rounded-xl border border-white/10 bg-slate-800/40 p-4 text-slate-400 text-sm">No players added yet.</div>
                <?php else: ?>
                    <div class="overflow-x-auto rounded-xl border border-white/10 bg-slate-800/40 p-2">
                        <!-- Live search filter -->
                    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-2 mb-2">
                        <div>
                            <h1 class="text-2xl font-semibold text-white mb-1">Players List</h1>
                            <p class="text-xs text-slate-400">
                            short description in here 
                            </p>
                        </div>
                           <div class="mb-2 flex justify-end">
                            <input id="playerSearchInput" type="text" class="input-dark w-64" placeholder="Search players...">
                        </div>
                    </div>
                     
                      <table class="min-w-full bg-bg-tertiary text-text-primary text-xs rounded-xl overflow-hidden" id="playersTable">
      <thead>
                                    <tr class="bg-bg-secondary text-text-muted uppercase font-semibold text-xs">
                                    <th class="px-4 py-3 text-center">Name</th>
                                    <th class="px-4 py-3 text-center">Position</th>
                                    <th class="px-4 py-3 text-center">Team</th>
                                    <th class="px-4 py-3 text-center">Active</th>
                                    <th class="px-4 py-3 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($players as $player): ?>
                                <tr>
                                        <td class="px-3 py-2 text-left font-bold text-slate-100"><?= htmlspecialchars(build_full_name($player['first_name'], $player['last_name'])) ?></td>
                                        <td class="px-4 py-3 text-center"><?= htmlspecialchars($player['primary_position'] ?? '—') ?></td>
                                        <td class="px-4 py-3 text-center">
                                            <?php
                                            // Show team name if set, otherwise —
                                            if (!empty($player['team_name'])) {
                                                echo htmlspecialchars($player['team_name']);
                                            } else {
                                                echo '—';
                                            }
                                            ?>
                                        </td>
                                        <script>
                                        // Live search filter for players table
                                        document.addEventListener('DOMContentLoaded', function() {
                                            const input = document.getElementById('playerSearchInput');
                                            const table = document.getElementById('playersTable');
                                            if (!input || !table) return;
                                            input.addEventListener('input', function() {
                                                const filter = input.value.toLowerCase();
                                                const rows = table.querySelectorAll('tbody tr');
                                                rows.forEach(row => {
                                                    const text = row.textContent.toLowerCase();
                                                    row.style.display = text.includes(filter) ? '' : 'none';
                                                });
                                            });
                                        });
                                        </script>
                                        <td class="px-4 py-3 text-center">
                                            <?php if ($player['is_active']): ?>
                                                <span class="inline-flex items-center rounded-full bg-emerald-700/20 px-2 py-1 text-xs font-semibold text-emerald-300">Yes</span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center rounded-full bg-slate-700/40 px-2 py-1 text-xs font-semibold text-slate-300">No</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                        
                                                <div class="flex justify-center gap-2 rounded-lg text-lg" role="group">
                                                    <form method="get" action="<?= htmlspecialchars($base) ?>/admin/players/<?= (int)$player['id'] ?>/" class="inline">
                                                        <button type="submit" class="inline-flex items-center rounded-lg border border-slate-200 bg-transparent px-2 py-1 text-xs text-slate-200 hover:bg-slate-700 hover:text-sky-400 hover:border-sky-400 pt-2 pb-2 transition-colors" aria-label="View player">
                                                            <i class="fa-solid fa-eye"></i>
                                                        </button>
                                                    </form>
                                                    <form method="get" action="<?= htmlspecialchars($base) ?>/admin/players/<?= (int)$player['id'] ?>/edit" class="inline">
                                                        <button type="submit" class="inline-flex items-center rounded-lg border border-indigo-700 bg-transparent px-2 py-1 text-xs text-indigo-700 hover:bg-indigo-700 hover:text-white pt-2 pb-2 transition-colors" aria-label="Edit player">
                                                            <i class="fa-solid fa-pen"></i>
                                                        </button>
                                                    </form>
                                                    <form method="post" action="<?= htmlspecialchars($base) ?>/admin/players/<?= (int)$player['id'] ?>/delete" class="inline" data-confirm="Mark this player as inactive?">
                                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                                        <button type="submit" class="inline-flex items-center rounded-lg border border-red-700 bg-transparent px-2 py-1 text-xs text-red-700 hover:bg-red-700 hover:text-white pt-2 pb-2 transition-colors" aria-label="Delete player">
                                                            <i class="fa-solid fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                        
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </main>
            <!-- Right: Player Summary Cards -->
            <aside class="col-span-3 min-w-0">
                <div class="rounded-xl bg-slate-900/80 border border-white/10 p-4">
                    <h5 class="text-slate-200 font-semibold mb-1">Player Summary</h5>
                    <div class="text-slate-400 text-xs mb-4">Quick stats and links</div>
                    <div class="space-y-3">
                        <article class="rounded-lg border border-white/10 bg-slate-800/40 px-3 py-3">
                            <div class="text-xs font-semibold text-slate-300 mb-2 text-center">Total Players</div>
                            <div class="text-2xl font-bold text-slate-100 text-center"><?= count($players) ?></div>
                        </article>
                        <div class="border-t border-white/10"></div>
                        <article class="rounded-lg border border-white/10 bg-slate-800/40 px-3 py-3">
                            <div class="text-xs font-semibold text-slate-300 mb-2 text-center">Active</div>
                            <div class="text-xl font-bold text-emerald-400 text-center"><?= count(array_filter($players, fn($p) => $p['is_active'])) ?></div>
                        </article>
                        <article class="rounded-lg border border-white/10 bg-slate-800/40 px-3 py-3">
                            <div class="text-xs font-semibold text-slate-300 mb-2 text-center">Inactive</div>
                            <div class="text-xl font-bold text-red-400 text-center"><?= count(array_filter($players, fn($p) => !$p['is_active'])) ?></div>
                        </article>
                    </div>
                </div>
            </aside
                                            </div>
                                            </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('form[data-confirm]').forEach(function(form) {
        form.addEventListener('submit', function(event) {
            var message = form.getAttribute('data-confirm') || 'Are you sure?';
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });
});
</script>
    
<?php
$content = ob_get_clean();
require __DIR__ . '/../../../layout.php';
