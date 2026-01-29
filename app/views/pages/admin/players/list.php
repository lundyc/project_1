<?php
require_once __DIR__ . '/../../../../lib/auth.php';
require_once __DIR__ . '/../../../../lib/player_repository.php';
require_once __DIR__ . '/../../../../lib/team_repository.php';
require_once __DIR__ . '/../../../../lib/season_repository.php';
require_once __DIR__ . '/../../../../lib/club_repository.php';
require_once __DIR__ . '/../../../../lib/player_name_helper.php';

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

$title = 'Players';
$base = base_path();

ob_start();
?>
<div class="w-full mt-4 text-slate-200">
    <div class="max-w-full">
        <?php
            $pageTitle = 'Players';
            $pageDescription = 'Manage club-wide players, positions, and assignments.';
            include __DIR__ . '/../../../partials/club_context_header.php';
        ?>
        <div class="flex justify-end mb-4 px-4 md:px-6 lg:px-8">
            <a href="<?= $base ?>/admin/players/create.php" class="inline-flex items-center gap-2 bg-accent-primary text-white px-4 py-2 rounded-md hover:bg-accent-primary/80 transition">+ Create Player</a>
        </div>
        <div class="grid grid-cols-12 gap-2 px-4 md:px-6 lg:px-8 w-full">
            <!-- Left: Filters -->
            <aside class="col-span-2 space-y-4 min-w-0">
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
                        <div class="mb-2 flex justify-end">
                            <input id="playerSearchInput" type="text" class="input-dark w-64" placeholder="Search players...">
                        </div>
                        <table class="w-full text-sm text-slate-200" id="playersTable">
                            <thead class="sticky top-0 bg-slate-900/95 border-b border-white/10">
                                <tr>
                                    <th class="px-6 py-3 text-left font-semibold uppercase tracking-wide text-slate-300">Name</th>
                                    <th class="px-4 py-3 text-center font-semibold uppercase tracking-wide text-slate-300">Position</th>
                                    <th class="px-4 py-3 text-center font-semibold uppercase tracking-wide text-slate-300">Team</th>
                                    <th class="px-4 py-3 text-center font-semibold uppercase tracking-wide text-slate-300">Active</th>
                                    <th class="px-4 py-3 text-right font-semibold uppercase tracking-wide text-slate-300">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($players as $player): ?>
                                    <tr class="border-b border-white/10 hover:bg-slate-800/50 transition-colors">
                                        <td class="px-6 py-3 text-left font-bold text-slate-100"><?= htmlspecialchars(build_full_name($player['first_name'], $player['last_name'])) ?></td>
                                        <td class="px-4 py-3 text-center text-slate-300"><?= htmlspecialchars($player['primary_position'] ?? '—') ?></td>
                                        <td class="px-4 py-3 text-center text-slate-300">
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
                                        <td class="px-4 py-3 text-right">
                                            <div class="flex justify-end gap-2">
                                                <a href="<?= htmlspecialchars($base) ?>/admin/players/<?= (int)$player['id'] ?>" class="inline-flex items-center rounded-md bg-slate-700/60 px-2 py-1 text-xs text-slate-200 hover:bg-slate-700/80 transition" aria-label="View player">
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>
                                                <a href="<?= htmlspecialchars($base) ?>/admin/players/<?= (int)$player['id'] ?>/edit" class="inline-flex items-center rounded-md bg-indigo-700/60 px-2 py-1 text-xs text-white hover:bg-indigo-700 transition" aria-label="Edit player">
                                                    <i class="fa-solid fa-pen"></i>
                                                </a>
                                                <form method="post" action="<?= htmlspecialchars($base) ?>/admin/players/<?= (int)$player['id'] ?>/delete" class="inline" onsubmit="return confirm('Mark this player as inactive?');">
                                                    <button type="submit" class="inline-flex items-center gap-2 bg-accent-danger text-white px-4 py-2 rounded-md hover:bg-accent-danger/80 transition text-xs" aria-label="Delete player">
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
            </aside>
        </div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../../layout.php';
