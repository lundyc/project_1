<?php
$base = base_path();
$currentUserIsAdmin = user_has_role('platform_admin');
$selectedSeasonId = isset($selectedSeason['id']) ? (int)$selectedSeason['id'] : null;
$selectedCompetitionId = isset($selectedCompetition['id']) ? (int)$selectedCompetition['id'] : null;
$filterValues = [
          'season_id' => $selectedSeasonId ?? '',
          'competition_id' => $selectedCompetitionId ?? '',
];
$activeFilters = array_filter($filterValues, fn ($value) => $value !== '' && $value !== null);
$filterQuery = $activeFilters ? ('?' . http_build_query($activeFilters)) : '';

$resultBadgeStyles = [
          'W' => 'bg-emerald-500/30 text-emerald-200',
          'D' => 'bg-slate-500/30 text-slate-200',
          'L' => 'bg-rose-500/30 text-rose-200',
];
$getResultBadgeStyle = static function (?string $result) use ($resultBadgeStyles): string {
          return $result !== null && isset($resultBadgeStyles[$result])
                    ? $resultBadgeStyles[$result]
                    : 'bg-slate-800 text-slate-500';
};

$formatMatchDate = static function (?string $date): string {
          if (empty($date)) {
                    return 'TBD';
          }
          $ts = strtotime($date);
          if ($ts === false) {
                    return $date;
          }
          return date('D, M j', $ts);
};

$flashSuccess = $_SESSION['wosfl_import_success'] ?? null;
$flashError = $_SESSION['wosfl_import_error'] ?? null;
unset($_SESSION['wosfl_import_success'], $_SESSION['wosfl_import_error']);
?>

<?php ob_start(); ?>
<div class="w-full mt-4 text-slate-200">
    <div class="max-w-full">
        <?php if ($flashSuccess): ?>
            <div class="mb-4 rounded-xl border border-emerald-500/40 bg-emerald-500/30 p-4 text-sm text-emerald-200">
                <?= htmlspecialchars($flashSuccess) ?>
            </div>
        <?php endif; ?>
        <?php if ($flashError): ?>
            <div class="mb-4 rounded-xl border border-rose-700/50 bg-rose-900/20 p-4 text-sm text-rose-400">
                <?= htmlspecialchars($flashError) ?>
            </div>
        <?php endif; ?>
        <div class="stats-three-col grid grid-cols-12 gap-2 px-4 md:px-6 lg:px-8 w-full">
            <!-- Left Sidebar -->
            <aside class="stats-col-left col-span-2 space-y-4 min-w-0">
                <?php if ($currentUserIsAdmin): ?>
                    <div class="space-y-2">
                        <form method="post" action="<?= htmlspecialchars($base) ?>/league-intelligence/import/run">
                            <button type="submit" class="stats-tab w-full justify-start text-left px-4 py-2.5 text-sm font-medium rounded-lg border transition-all duration-200 bg-emerald-600 border-emerald-500 text-white shadow-lg shadow-emerald-500/20 flex">Import All Fixtures &amp; Results</button>
                        </form>
                        <form method="post" action="<?= htmlspecialchars($base) ?>/league-intelligence/update-week">
                            <button type="submit" class="stats-tab w-full justify-start text-left px-4 py-2.5 text-sm font-medium rounded-lg border transition-all duration-200 bg-slate-800/40 border-white/10 text-slate-300 hover:bg-slate-700/50 hover:border-white/20 flex">Update This Week</button>
                        </form>
                    </div>
                <?php endif; ?>
                <form method="get" class="flex flex-col gap-3" role="search">
                    <button type="submit" class="stats-tab w-full justify-start text-left px-4 py-2.5 text-sm font-medium rounded-lg border transition-all duration-200 bg-indigo-600 border-indigo-500 text-white shadow-lg shadow-indigo-500/20 mb-2 flex">Update View</button>
                    <div>
                        <label class="block text-slate-400 text-xs mb-1" for="season-select">Season</label>
                        <select id="season-select" name="season_id" class="block w-full rounded-md bg-slate-900/60 border border-white/20 px-2 py-1 text-xs">
                            <option value="">All seasons</option>
                            <?php foreach ($seasonOptions as $season): ?>
                                <option value="<?= (int)$season['id'] ?>" <?= $selectedSeasonId === (int)$season['id'] ? 'selected' : '' ?>><?= htmlspecialchars($season['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-slate-400 text-xs mb-1" for="competition-select">Competition</label>
                        <select id="competition-select" name="competition_id" class="block w-full rounded-md bg-slate-900/60 border border-white/20 px-2 py-1 text-xs">
                            <option value="">All league competitions</option>
                            <?php foreach ($competitionOptions as $competition): ?>
                                <option value="<?= (int)$competition['id'] ?>" <?= $selectedCompetitionId === (int)$competition['id'] ? 'selected' : '' ?>><?= htmlspecialchars($competition['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
                <div class="mt-4">
                    <h6 class="text-slate-300 text-xs font-semibold mb-2">Teams</h6>
                    <nav class="flex flex-col gap-1">
                        <?php foreach ($teamNavigation as $team): ?>
                            <a href="<?= htmlspecialchars($base . '/league-intelligence/team/' . $team['team_id'] . $filterQuery) ?>"
                               class="flex items-center justify-between rounded-xl border border-white/5 bg-slate-900/40 px-4 py-2 text-sm font-semibold text-white hover:border-emerald-400<?= (isset($selectedTeamId) && $selectedTeamId == $team['team_id']) ? ' border-emerald-400' : '' ?>">
                                <span><?= sprintf('#%d %s', (int)$team['position'], htmlspecialchars($team['team_name'])) ?></span>
                                <span class="text-xs text-slate-400">View</span>
                            </a>
                        <?php endforeach; ?>
                    </nav>
                </div>
            </aside>
            <!-- Main Content -->
            <main class="stats-col-main col-span-7 space-y-4 min-w-0">
                <div class="rounded-xl bg-slate-900/80 border border-white/10 p-5">
                    <div class="flex flex-col md:flex-row md:items-end md:justify-between mb-2 gap-2">
                        <div>
                            <h1 class="text-2xl font-semibold text-white mb-1">League Table</h1>
                            <p class="text-xs text-slate-400">
                                <?= htmlspecialchars($selectedCompetition['name'] ?? 'All competitions') ?> · <?= htmlspecialchars($selectedSeason['name'] ?? 'All seasons') ?>
                            </p>
                        </div>
                        <div class="text-xs text-slate-400 self-end"><?= count($leagueTable) ?> teams</div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[700px] text-left text-sm text-slate-300">
                            <thead>
                                <tr class="text-[11px] uppercase tracking-[0.2em] text-slate-500">
                                    <th class="px-3 py-2">Pos</th>
                                    <th class="px-3 py-2">Team</th>
                                    <th class="px-3 py-2">Pld</th>
                                    <th class="px-3 py-2">W</th>
                                    <th class="px-3 py-2">D</th>
                                    <th class="px-3 py-2">L</th>
                                    <th class="px-3 py-2">GF</th>
                                    <th class="px-3 py-2">GA</th>
                                    <th class="px-3 py-2">GD</th>
                                    <th class="px-3 py-2">Pts</th>
                                    <th class="px-3 py-2">Form</th>
                                    <th class="px-3 py-2">Streak</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <?php foreach ($leagueTable as $row): ?>
                                    <tr class="align-top text-sm text-slate-200">
                                        <td class="px-3 py-3 font-semibold text-white"><?= (int)$row['position'] ?></td>
                                        <td class="px-3 py-3">
                                            <a href="<?= htmlspecialchars($base . '/league-intelligence/team/' . $row['team_id'] . $filterQuery) ?>" class="inline-flex items-baseline gap-2 text-sm font-semibold text-white hover:text-emerald-300">
                                                <?= htmlspecialchars($row['team_name']) ?>
                                                <span class="text-[11px] font-normal text-slate-500">P<?= (int)$row['played'] ?></span>
                                            </a>
                                        </td>
                                        <td class="px-3 py-3"><?= (int)$row['played'] ?></td>
                                        <td class="px-3 py-3"><?= (int)$row['wins'] ?></td>
                                        <td class="px-3 py-3"><?= (int)$row['draws'] ?></td>
                                        <td class="px-3 py-3"><?= (int)$row['losses'] ?></td>
                                        <td class="px-3 py-3"><?= (int)$row['goals_for'] ?></td>
                                        <td class="px-3 py-3"><?= (int)$row['goals_against'] ?></td>
                                        <td class="px-3 py-3"><?= (int)$row['goal_difference'] ?></td>
                                        <td class="px-3 py-3">
                                            <div class="text-xs text-slate-400"><?= number_format((float)$row['points_per_game'], 2) ?> PPG</div>
                                            <div class="flex gap-1 mt-2">
                                                <?php if (!empty($row['form_display'])): ?>
                                                    <?php foreach ($row['form_display'] as $formResult): ?>
                                                        <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold <?= $getResultBadgeStyle($formResult) ?>"><?= htmlspecialchars($formResult) ?></span>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <span class="text-xs text-slate-500">—</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-3 py-3 text-sm text-slate-200">
                                            <?= htmlspecialchars($row['streak_label'] ?: '—') ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
            <!-- Right Sidebar -->
            <aside class="stats-col-right col-span-3 min-w-0">
                <div class="rounded-xl bg-slate-900/80 border border-white/10 p-4">
                    <h5 class="text-slate-200 font-semibold mb-1">Next Fixtures</h5>
                    <div class="text-slate-400 text-xs mb-4">Upcoming fixtures for the selected competition</div>
                    <div class="space-y-3">
                        <?php $fixtures = array_slice($resultsFixtures['fixtures'] ?? [], 0, 10); ?>
                        <?php if (!empty($fixtures)): ?>
                            <?php foreach ($fixtures as $match): ?>
                                <article class="rounded-lg border border-white/10 bg-slate-800/40 px-3 py-3 flex flex-col gap-1">
                                    <div class="flex justify-between items-center">
                                        <span class="font-semibold text-white text-sm"><?= htmlspecialchars($match['home_team'] ?? '?') ?> vs <?= htmlspecialchars($match['away_team'] ?? '?') ?></span>
                                        <span class="text-xs text-slate-400"><?= htmlspecialchars($formatMatchDate($match['date'])) ?></span>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-xs text-slate-500">No upcoming fixtures.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../layout.php';
