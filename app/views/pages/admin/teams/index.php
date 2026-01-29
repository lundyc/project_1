<?php
require_role('platform_admin');
require_once dirname(__DIR__, 4) . '/lib/team_repository.php';
require_once dirname(__DIR__, 4) . '/lib/club_repository.php';

$base = base_path();
$teams = get_all_teams_with_clubs();
$clubs = get_all_clubs();

$teamTypes = [];
foreach ($teams as $team) {
          $type = $team['team_type'] ?? '';
          if ($type !== '' && !in_array($type, $teamTypes, true)) {
                    $teamTypes[] = $type;
          }
}
if (empty($teamTypes)) {
          $teamTypes = ['club', 'opponent'];
}

$filters = [
          'search' => trim((string)($_GET['search'] ?? '')),
          'club_id' => trim((string)($_GET['club_id'] ?? '')),
          'team_type' => trim((string)($_GET['team_type'] ?? '')),
];

$filteredTeams = array_values(array_filter($teams, function (array $team) use ($filters): bool {
          if ($filters['club_id'] !== '') {
                    if ((string)(int)$team['club_id'] !== $filters['club_id']) {
                              return false;
                    }
          }

          if ($filters['team_type'] !== '') {
                    if (($team['team_type'] ?? '') !== $filters['team_type']) {
                              return false;
                    }
          }

          if ($filters['search'] !== '') {
                    $name = (string)($team['name'] ?? '');
                    if (stripos($name, $filters['search']) === false) {
                              return false;
                    }
          }

          return true;
}));

$teamCount = count($filteredTeams);
$totalTeams = count($teams);

$success = $_SESSION['team_form_success'] ?? null;
$error = $_SESSION['team_form_error'] ?? null;
unset($_SESSION['team_form_success'], $_SESSION['team_form_error']);

$typeCounts = [];
foreach ($teams as $team) {
          $type = $team['team_type'] ?? 'unknown';
          $typeCounts[$type] = ($typeCounts[$type] ?? 0) + 1;
}

$title = 'Teams';

ob_start();
?>
<div class="w-full mt-4 text-slate-200">
          <div class="max-w-full">
                    <?php
                    $pageTitle = 'Teams';
                    $pageDescription = 'Manage team records and assign them to clubs.';
                    include __DIR__ . '/../../../partials/club_context_header.php';
                    ?>
                    <div class="flex justify-end mb-4 px-4 md:px-6 lg:px-8">
                            <a href="<?= htmlspecialchars($base) ?>/admin/teams/create" class="inline-flex items-center gap-2 bg-accent-primary text-white px-4 py-2 rounded-md hover:bg-accent-primary/80 transition">+ Create Team</a>
                    </div>
                    <div class="grid grid-cols-12 gap-2 px-4 md:px-6 lg:px-8 w-full">
                              <!-- Left: Filters -->
                              <aside class="col-span-2 space-y-4 min-w-0">
                                        <form method="get" class="space-y-4">
                                                  <div>
                                                            <label class="block text-slate-400 text-xs mb-1">Search</label>
                                                            <input type="text" name="search" value="<?= htmlspecialchars($filters['search']) ?>" placeholder="Team name" class="block w-full rounded-md bg-slate-900/60 border border-white/20 px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-white/30">
                                                  </div>
                                                  <div>
                                                            <label class="block text-slate-400 text-xs mb-1">Club</label>
                                                            <select name="club_id" class="block w-full rounded-md bg-slate-900/60 border border-white/20 px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-white/30">
                                                                      <option value="">All clubs</option>
                                                                      <?php foreach ($clubs as $club): ?>
                                                                                <option value="<?= (int)$club['id'] ?>" <?= $filters['club_id'] === (string)$club['id'] ? 'selected' : '' ?>><?= htmlspecialchars($club['name']) ?></option>
                                                                      <?php endforeach; ?>
                                                            </select>
                                                  </div>
                                                  <div>
                                                            <label class="block text-slate-400 text-xs mb-1">Type</label>
                                                            <select name="team_type" class="block w-full rounded-md bg-slate-900/60 border border-white/20 px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-white/30">
                                                                      <option value="">All types</option>
                                                                      <?php foreach ($teamTypes as $type): ?>
                                                                                <option value="<?= htmlspecialchars($type) ?>" <?= $filters['team_type'] === $type ? 'selected' : '' ?>><?= htmlspecialchars($type) ?></option>
                                                                      <?php endforeach; ?>
                                                            </select>
                                                  </div>
                                                  <div class="flex gap-2">
                                                            <button type="submit" class="inline-flex items-center gap-2 bg-accent-primary text-white px-4 py-2 rounded-md hover:bg-accent-primary/80 transition flex-1">Apply</button>
                                                            <a href="<?= htmlspecialchars($base) ?>/admin/teams" class="inline-flex items-center gap-2 bg-bg-secondary text-text-primary border border-border-soft px-4 py-2 rounded-md hover:bg-bg-secondary/80 transition flex-1">Clear</a>
                                                  </div>
                                        </form>
                              </aside>

                              <!-- Center: Teams List -->
                              <main class="col-span-7 space-y-4 min-w-0">
                                        <?php if ($error): ?>
                                                  <div class="rounded-lg bg-red-900/80 border border-red-700 text-red-200 px-4 py-3 mb-4 text-sm"><?= htmlspecialchars($error) ?></div>
                                        <?php elseif ($success): ?>
                                                  <div class="rounded-lg bg-emerald-900/80 border border-emerald-700 text-emerald-200 px-4 py-3 mb-4 text-sm"><?= htmlspecialchars($success) ?></div>
                                        <?php endif; ?>

                                        <?php if ($teamCount === 0): ?>
                                                  <div class="rounded-xl border border-white/10 bg-slate-800/40 p-4 text-slate-400 text-sm">No teams found.</div>
                                        <?php else: ?>
                                                  <div class="overflow-x-auto rounded-xl border border-white/10 bg-slate-800/40 p-2">
                                                            <table class="w-full text-sm text-slate-200">
                                                                      <thead class="sticky top-0 bg-slate-900/95 border-b border-white/10">
                                                                                <tr>
                                                                                          <th class="px-6 py-3 text-left font-semibold uppercase tracking-wide text-slate-300">Team</th>
                                                                                          <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide text-slate-300">Type</th>
                                                                                          <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide text-slate-300">Club</th>
                                                                                          <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide text-slate-300">Created</th>
                                                                                          <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide text-slate-300">Updated</th>
                                                                                          <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide text-slate-300">ID</th>
                                                                                          <th class="px-4 py-3 text-right font-semibold uppercase tracking-wide text-slate-300">Actions</th>
                                                                                </tr>
                                                                      </thead>
                                                                      <tbody>
                                                                                <?php foreach ($filteredTeams as $team): ?>
                                                                                          <?php
                                                                                          $teamId = (int)$team['id'];
                                                                                          $createdAtRaw = $team['created_at'] ?? '';
                                                                                          $updatedAtRaw = $team['updated_at'] ?? '';
                                                                                          $createdAt = $createdAtRaw ? date('Y-m-d H:i', strtotime($createdAtRaw)) : 'N/A';
                                                                                          $updatedAt = $updatedAtRaw ? date('Y-m-d H:i', strtotime($updatedAtRaw)) : 'N/A';
                                                                                          ?>
                                                                                          <tr class="border-b border-white/10 hover:bg-slate-800/50 transition-colors">
                                                                                                    <td class="px-6 py-3 font-semibold text-slate-100"><?= htmlspecialchars($team['name'] ?? 'Team') ?></td>
                                                                                                    <td class="px-4 py-3 text-slate-300"><?= htmlspecialchars($team['team_type'] ?? 'club') ?></td>
                                                                                                    <td class="px-4 py-3 text-slate-300"><?= htmlspecialchars($team['club_name'] ?? 'Unknown') ?></td>
                                                                                                    <td class="px-4 py-3 text-slate-300" title="<?= htmlspecialchars($createdAtRaw ?: 'N/A') ?>"><?= htmlspecialchars($createdAt) ?></td>
                                                                                                    <td class="px-4 py-3 text-slate-300" title="<?= htmlspecialchars($updatedAtRaw ?: 'N/A') ?>"><?= htmlspecialchars($updatedAt) ?></td>
                                                                                                    <td class="px-4 py-3 text-slate-400">#<?= $teamId ?></td>
                                                                                                    <td class="px-4 py-3 text-right">
                                                                                                              <div class="flex justify-end gap-2">
                                                                                                                        <a href="<?= htmlspecialchars($base) ?>/admin/teams/<?= $teamId ?>/edit" class="inline-flex items-center rounded-md bg-indigo-700/60 px-2 py-1 text-xs text-white hover:bg-indigo-700 transition" aria-label="Edit team">
                                                                                                                                  <i class="fa-solid fa-pen"></i>
                                                                                                                        </a>
                                                                                                                        <form method="post" action="<?= htmlspecialchars($base) ?>/api/admin/teams/delete" class="inline" onsubmit="return confirm('Delete this team? This will fail if it has matches or related data.');">
                                                                                                                                  <input type="hidden" name="id" value="<?= $teamId ?>">
                                                                                                                                <button type="submit" class="inline-flex items-center gap-2 bg-accent-danger text-white px-4 py-2 rounded-md hover:bg-accent-danger/80 transition text-xs" aria-label="Delete team">
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

                              <!-- Right: Summary -->
                              <aside class="col-span-3 min-w-0">
                                        <div class="rounded-xl bg-slate-900/80 border border-white/10 p-4">
                                                  <h5 class="text-slate-200 font-semibold mb-1">Team Summary</h5>
                                                  <div class="text-slate-400 text-xs mb-4">Quick overview</div>
                                                  <div class="space-y-3">
                                                            <article class="rounded-lg border border-white/10 bg-slate-800/40 px-3 py-3">
                                                                      <div class="text-xs font-semibold text-slate-300 mb-2 text-center">Total Teams</div>
                                                                      <div class="text-2xl font-bold text-slate-100 text-center"><?= $totalTeams ?></div>
                                                            </article>
                                                            <article class="rounded-lg border border-white/10 bg-slate-800/40 px-3 py-3">
                                                                      <div class="text-xs font-semibold text-slate-300 mb-2 text-center">Showing</div>
                                                                      <div class="text-2xl font-bold text-slate-100 text-center"><?= $teamCount ?></div>
                                                            </article>
                                                            <?php foreach ($typeCounts as $type => $count): ?>
                                                                      <article class="rounded-lg border border-white/10 bg-slate-800/40 px-3 py-3">
                                                                                <div class="text-xs font-semibold text-slate-300 mb-2 text-center"><?= htmlspecialchars($type) ?> teams</div>
                                                                                <div class="text-2xl font-bold text-slate-100 text-center"><?= (int)$count ?></div>
                                                                      </article>
                                                            <?php endforeach; ?>
                                                  </div>
                                        </div>
                              </aside>
                    </div>
          </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../../layout.php';
