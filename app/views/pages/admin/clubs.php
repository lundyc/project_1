<?php
require_role('platform_admin');
require_once dirname(__DIR__, 3) . '/lib/club_repository.php';

$base = base_path();
$clubs = get_all_clubs();

$filters = [
          'search' => trim((string)($_GET['search'] ?? '')),
          'club_id' => trim((string)($_GET['club_id'] ?? '')),
];

$filteredClubs = array_values(array_filter($clubs, function (array $club) use ($filters): bool {
          if ($filters['club_id'] !== '') {
                    if ((string)(int)$club['id'] !== $filters['club_id']) {
                              return false;
                    }
          }

          if ($filters['search'] !== '') {
                    $name = (string)($club['name'] ?? '');
                    if (stripos($name, $filters['search']) === false) {
                              return false;
                    }
          }

          return true;
}));

$clubCount = count($filteredClubs);
$totalClubs = count($clubs);

$error = $_SESSION['club_form_error'] ?? null;
$success = $_SESSION['club_form_success'] ?? null;
unset($_SESSION['club_form_error'], $_SESSION['club_form_success']);

$title = 'Manage Clubs';

$latestUpdated = null;
foreach ($clubs as $club) {
          $candidate = $club['updated_at'] ?? $club['created_at'] ?? null;
          if ($candidate && (!$latestUpdated || strtotime($candidate) > strtotime($latestUpdated))) {
                    $latestUpdated = $candidate;
          }
}

ob_start();
?>
<div class="w-full mt-4 text-slate-200">
          <div class="max-w-full">
                    <?php
                    $pageTitle = 'Clubs';
                    $pageDescription = 'Create clubs and manage their team assignments.';
                    include __DIR__ . '/../../partials/club_context_header.php';
                    ?>
                    <div class="flex justify-end mb-4 px-4 md:px-6 lg:px-8">
                              <a href="<?= htmlspecialchars($base) ?>/admin/clubs/create" class="btn-primary-soft px-4 py-2 text-sm font-semibold rounded-md">+ Create Club</a>
                    </div>
                    <div class="grid grid-cols-12 gap-2 px-4 md:px-6 lg:px-8 w-full">
                              <!-- Left: Filters -->
                              <aside class="col-span-2 space-y-4 min-w-0">
                                        <form method="get" class="space-y-4">
                                                  <div>
                                                            <label class="block text-slate-400 text-xs mb-1">Search</label>
                                                            <input type="text" name="search" value="<?= htmlspecialchars($filters['search']) ?>" placeholder="Club name" class="block w-full rounded-md bg-slate-900/60 border border-white/20 px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-white/30">
                                                  </div>
                                                  <div>
                                                            <label class="block text-slate-400 text-xs mb-1">Club ID</label>
                                                            <input type="text" name="club_id" value="<?= htmlspecialchars($filters['club_id']) ?>" placeholder="Exact ID" class="block w-full rounded-md bg-slate-900/60 border border-white/20 px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-white/30">
                                                  </div>
                                                  <div class="flex gap-2">
                                                            <button type="submit" class="btn btn-primary-soft btn-sm flex-1">Apply</button>
                                                            <a href="<?= htmlspecialchars($base) ?>/admin/clubs" class="btn btn-secondary-soft btn-sm flex-1">Clear</a>
                                                  </div>
                                        </form>
                              </aside>

                              <!-- Center: Clubs List -->
                              <main class="col-span-7 space-y-4 min-w-0">
                                        <?php if ($error): ?>
                                                  <div class="rounded-lg bg-red-900/80 border border-red-700 text-red-200 px-4 py-3 mb-4 text-sm"><?= htmlspecialchars($error) ?></div>
                                        <?php elseif ($success): ?>
                                                  <div class="rounded-lg bg-emerald-900/80 border border-emerald-700 text-emerald-200 px-4 py-3 mb-4 text-sm"><?= htmlspecialchars($success) ?></div>
                                        <?php endif; ?>

                                        <?php if ($clubCount === 0): ?>
                                                  <div class="rounded-xl border border-white/10 bg-slate-800/40 p-4 text-slate-400 text-sm">No clubs found.</div>
                                        <?php else: ?>
                                                  <div class="overflow-x-auto rounded-xl border border-white/10 bg-slate-800/40 p-2">
                                                            <table class="w-full text-sm text-slate-200">
                                                                      <thead class="sticky top-0 bg-slate-900/95 border-b border-white/10">
                                                                                <tr>
                                                                                          <th class="px-6 py-3 text-left font-semibold uppercase tracking-wide text-slate-300">Club</th>
                                                                                          <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide text-slate-300">Created</th>
                                                                                          <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide text-slate-300">Updated</th>
                                                                                          <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide text-slate-300">ID</th>
                                                                                          <th class="px-4 py-3 text-right font-semibold uppercase tracking-wide text-slate-300">Actions</th>
                                                                                </tr>
                                                                      </thead>
                                                                      <tbody>
                                                                                <?php foreach ($filteredClubs as $club): ?>
                                                                                          <?php
                                                                                          $clubId = (int)$club['id'];
                                                                                          $clubName = $club['name'] ?? 'Club';
                                                                                          $createdAtRaw = $club['created_at'] ?? '';
                                                                                          $updatedAtRaw = $club['updated_at'] ?? '';
                                                                                          $createdAt = $createdAtRaw ? date('Y-m-d H:i', strtotime($createdAtRaw)) : 'N/A';
                                                                                          $updatedAt = $updatedAtRaw ? date('Y-m-d H:i', strtotime($updatedAtRaw)) : 'N/A';
                                                                                          ?>
                                                                                          <tr class="border-b border-white/10 hover:bg-slate-800/50 transition-colors">
                                                                                                    <td class="px-6 py-3 font-semibold text-slate-100"><?= htmlspecialchars($clubName) ?></td>
                                                                                                    <td class="px-4 py-3 text-slate-300" title="<?= htmlspecialchars($createdAtRaw ?: 'N/A') ?>"><?= htmlspecialchars($createdAt) ?></td>
                                                                                                    <td class="px-4 py-3 text-slate-300" title="<?= htmlspecialchars($updatedAtRaw ?: 'N/A') ?>"><?= htmlspecialchars($updatedAt) ?></td>
                                                                                                    <td class="px-4 py-3 text-slate-400">#<?= $clubId ?></td>
                                                                                                    <td class="px-4 py-3 text-right">
                                                                                                              <div class="flex justify-end gap-2">
                                                                                                                        <a href="<?= htmlspecialchars($base) ?>/admin/clubs/<?= $clubId ?>/edit" class="inline-flex items-center rounded-md bg-indigo-700/60 px-2 py-1 text-xs text-white hover:bg-indigo-700 transition" aria-label="Edit club">
                                                                                                                                  <i class="fa-solid fa-pen"></i>
                                                                                                                        </a>
                                                                                                                        <form method="post" action="<?= htmlspecialchars($base) ?>/api/admin/clubs/delete" class="inline" onsubmit="return confirm('Delete this club? This will fail if it has related data.');">
                                                                                                                                  <input type="hidden" name="id" value="<?= $clubId ?>">
                                                                                                                                  <button type="submit" class="inline-flex items-center rounded-md bg-red-700/60 px-2 py-1 text-xs text-white hover:bg-red-800 transition" aria-label="Delete club">
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
                                                  <h5 class="text-slate-200 font-semibold mb-1">Club Summary</h5>
                                                  <div class="text-slate-400 text-xs mb-4">Quick overview</div>
                                                  <div class="space-y-3">
                                                            <article class="rounded-lg border border-white/10 bg-slate-800/40 px-3 py-3">
                                                                      <div class="text-xs font-semibold text-slate-300 mb-2 text-center">Total Clubs</div>
                                                                      <div class="text-2xl font-bold text-slate-100 text-center"><?= $totalClubs ?></div>
                                                            </article>
                                                            <article class="rounded-lg border border-white/10 bg-slate-800/40 px-3 py-3">
                                                                      <div class="text-xs font-semibold text-slate-300 mb-2 text-center">Showing</div>
                                                                      <div class="text-2xl font-bold text-slate-100 text-center"><?= $clubCount ?></div>
                                                            </article>
                                                            <article class="rounded-lg border border-white/10 bg-slate-800/40 px-3 py-3">
                                                                      <div class="text-xs font-semibold text-slate-300 mb-2 text-center">Last Updated</div>
                                                                      <div class="text-sm font-semibold text-slate-100 text-center">
                                                                                <?= $latestUpdated ? htmlspecialchars(date('Y-m-d H:i', strtotime($latestUpdated))) : 'N/A' ?>
                                                                      </div>
                                                            </article>
                                                  </div>
                                        </div>
                              </aside>
                    </div>
          </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../layout.php';
