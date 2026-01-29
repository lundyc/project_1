<?php
$base = base_path();
$selectedSeasonId = isset($selectedSeason['id']) ? (int)$selectedSeason['id'] : null;
$selectedCompetitionId = isset($selectedCompetition['id']) ? (int)$selectedCompetition['id'] : null;
$filterValues = [
          'season_id' => $selectedSeasonId ?? '',
          'competition_id' => $selectedCompetitionId ?? '',
];
$activeFilters = array_filter($filterValues, fn ($value) => $value !== '' && $value !== null);
$filterQuery = $activeFilters ? ('?' . http_build_query($activeFilters)) : '';
$overviewLink = $base . '/league-intelligence' . $filterQuery;
$matchHistory = $teamInsights['match_history'] ?? [];
$completedMatches = array_values(array_filter($matchHistory, fn ($match) => ($match['status'] ?? '') === 'completed'));
usort($completedMatches, function ($a, $b) {
          return strtotime($b['date'] ?? '') <=> strtotime($a['date'] ?? '');
});
$upcomingMatches = array_values(array_filter($matchHistory, fn ($match) => ($match['status'] ?? '') !== 'completed'));
usort($upcomingMatches, function ($a, $b) {
          return strtotime($a['date'] ?? '') <=> strtotime($b['date'] ?? '');
});
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
          return date('D, j M', $ts);
};
$buildTrendHeights = static function (array $values): array {
          if ($values === []) {
                    return [];
          }
          $min = min($values);
          $max = max($values);
          $range = $max - $min;
          if ($range === 0) {
                    $range = 1;
          }
          $heights = [];
          foreach ($values as $value) {
                    $normalized = ($value - $min) / $range;
                    $heights[] = max(10, min(100, (int)round(10 + $normalized * 80)));
          }
          return $heights;
};
$pointsTrend = $teamInsights['points_trend'] ?? [];
$goalTrend = $teamInsights['goal_difference_trend'] ?? [];
$pointsTrendHeights = $buildTrendHeights($pointsTrend);
$goalTrendHeights = $buildTrendHeights($goalTrend);
$headToHead = $teamInsights['head_to_head'] ?? [];
$strengthSchedule = $teamInsights['strength_of_schedule'] ?? [];
?>
<?php ob_start(); ?>
<div class="w-full mt-4 text-slate-200">
    <div class="max-w-full">
        <div class="grid grid-cols-12 gap-2 px-4 md:px-6 lg:px-8 w-full">
            <!-- Left Sidebar: Navigation / Filters -->
            <aside class="col-span-2 space-y-4 min-w-0">
                <div class="space-y-2">
                    <a href="<?= htmlspecialchars($overviewLink) ?>" class="btn-flat text-xs uppercase tracking-[0.3em] w-full block mb-2">League overview</a>
                    <form method="get" class="flex flex-col gap-3" role="search">
                        <label class="block text-slate-400 text-xs mb-1">Season
                            <select name="season_id" class="block w-full rounded-md bg-slate-900/60 border border-white/20 px-2 py-1 text-xs">
                                <option value="">All seasons</option>
                                <?php foreach ($seasonOptions as $season): ?>
                                    <option value="<?= (int)$season['id'] ?>" <?= $selectedSeasonId === (int)$season['id'] ? 'selected' : '' ?>><?= htmlspecialchars($season['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="block text-slate-400 text-xs mb-1">Competition
                            <select name="competition_id" class="block w-full rounded-md bg-slate-900/60 border border-white/20 px-2 py-1 text-xs">
                                <option value="">All league competitions</option>
                                <?php foreach ($competitionOptions as $competition): ?>
                                    <option value="<?= (int)$competition['id'] ?>" <?= $selectedCompetitionId === (int)$competition['id'] ? 'selected' : '' ?>><?= htmlspecialchars($competition['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <button type="submit" class="btn-primary-soft text-xs px-4 py-2 mt-2">Refresh</button>
                    </form>
                </div>
                <div class="mt-4">
                    <h6 class="text-slate-300 text-xs font-semibold mb-2">Team Navigation</h6>
                    <nav class="flex flex-col gap-1">
                        <?php foreach ($teamNavigation as $team): ?>
                            <a href="<?= htmlspecialchars($base . '/league-intelligence/team/' . $team['team_id'] . $filterQuery) ?>"
                                 class="flex items-center justify-between rounded-xl border border-white/5 bg-slate-900/40 px-4 py-2 text-sm font-semibold text-white hover:border-emerald-400<?= (isset($teamInsights['team_id']) && $teamInsights['team_id'] == $team['team_id']) ? ' border-emerald-400' : '' ?>">
                                <span><?= sprintf('#%d %s', (int)$team['position'], htmlspecialchars($team['team_name'])) ?></span>
                                <span class="text-xs text-slate-400">View</span>
                            </a>
                        <?php endforeach; ?>
                    </nav>
                </div>
            </aside>
            <!-- Main Content -->
            <main class="col-span-7 space-y-5 min-w-0">
                <header class="flex flex-col gap-3">
                    <p class="text-xs font-semibold tracking-[0.3em] uppercase text-slate-500">Team profile</p>
                    <h1 class="text-3xl font-semibold text-white"><?= htmlspecialchars($teamInsights['team_name'] ?? 'Team') ?></h1>
                    <p class="text-sm text-slate-400">
                        <?= htmlspecialchars($selectedCompetition['name'] ?? 'League view') ?> · <?= htmlspecialchars($selectedSeason['name'] ?? 'Season overview') ?>
                    </p>
                </header>
                <div class="space-y-5">
                    <?php // ...existing code for main content panels... ?>
                </div>
            </main>
            <!-- Right Sidebar: Contextual Panels -->
            <aside class="col-span-3 space-y-4 min-w-0">
                <?php // ...existing code for contextual panels (analytics, trends, head-to-head, etc.)... ?>
            </aside>
        </div>
    </div>
</div>
                              <section class="rounded-xl border border-border-soft bg-bg-secondary p-5 shadow space-y-4">
                                        <header class="flex items-center justify-between">
                                                  <div>
                                                            <p class="text-xs uppercase tracking-[0.3em] text-slate-500">Team snapshot</p>
                                                            <h2 class="text-xl font-semibold text-white">Current status</h2>
                                                  </div>
                                        </header>
                                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
                                                  <article class="rounded-xl border border-border-soft bg-bg-secondary p-5 text-center">
                                                            <p class="text-xs text-slate-400">Position</p>
                                                            <p class="text-2xl font-semibold text-white">#<?= (int)$teamInsights['position'] ?></p>
                                                  </article>
                                                  <article class="rounded-2xl border border-white/5 bg-white/5 p-3 text-center">
                                                            <p class="text-xs text-slate-400">Points</p>
                                                            <p class="text-2xl font-semibold text-white"><?= (int)$teamInsights['points'] ?></p>
                                                  </article>
                                                  <article class="rounded-2xl border border-white/5 bg-white/5 p-3 text-center">
                                                            <p class="text-xs text-slate-400">Record</p>
                                                            <p class="text-2xl font-semibold text-white"><?= htmlspecialchars($teamInsights['record'] ?? '—') ?></p>
                                                  </article>
                                                  <article class="rounded-2xl border border-white/5 bg-white/5 p-3 text-center">
                                                            <p class="text-xs text-slate-400">Goal difference</p>
                                                            <p class="text-2xl font-semibold text-white"><?= (int)$teamInsights['goal_difference'] ?></p>
                                                  </article>
                                                  <article class="rounded-2xl border border-white/5 bg-white/5 p-3 text-center">
                                                            <p class="text-xs text-slate-400">Current streak</p>
                                                            <p class="text-2xl font-semibold text-white"><?= htmlspecialchars($teamInsights['streak'] ?? '—') ?></p>
                                                  </article>
                                        </div>
                              </section>

                              <section class="rounded-xl border border-border-soft bg-bg-secondary p-5 shadow space-y-4">
                                        <p class="text-xs uppercase tracking-[0.3em] text-slate-500">Form &amp; momentum</p>
                                        <div class="space-y-4">
                                                  <div class="flex flex-wrap gap-2">
                                                            <?php if (!empty($teamInsights['form'])): ?>
                                                                      <?php foreach ($teamInsights['form'] as $result): ?>
                                                                                <span class="rounded-full px-3 py-1 text-sm font-semibold <?= $getResultBadgeStyle($result) ?>">
                                                                                          <?= htmlspecialchars($result) ?>
                                                                                </span>
                                                                      <?php endforeach; ?>
                                                            <?php else: ?>
                                                                      <span class="text-xs text-slate-500">No results yet.</span>
                                                            <?php endif; ?>
                                                  </div>
                                                  <div class="grid gap-4 sm:grid-cols-2">
                                                            <div class="rounded-xl border border-border-soft bg-bg-secondary p-5">
                                                                      <p class="text-xs text-slate-400">Points per game</p>
                                                                      <p class="text-2xl font-semibold text-white"><?= number_format((float)($teamInsights['points_per_game'] ?? 0), 2) ?></p>
                                                            </div>
                                                            <div class="rounded-2xl border border-white/5 bg-white/5 p-3">
                                                                      <p class="text-xs text-slate-400">Avg goals / match</p>
                                                                      <p class="text-2xl font-semibold text-white"><?= number_format((float)($teamInsights['average_goals_per_match'] ?? 0), 2) ?></p>
                                                            </div>
                                                  </div>
                                        </div>
                              </section>

                              <section class="panel rounded-2xl p-5 space-y-4">
                                        <div class="flex items-center justify-between">
                                                  <p class="text-xs uppercase tracking-[0.3em] text-slate-500">Results &amp; fixtures</p>
                                                  <span class="text-xs text-slate-400">Past results and upcoming matches</span>
                                        </div>
                                        <div class="grid gap-4 md:grid-cols-2">
                                                  <div>
                                                            <h3 class="text-sm font-semibold text-white">Recent results</h3>
                                                            <?php if (!empty($completedMatches)): ?>
                                                                      <div class="mt-3 space-y-3 max-h-72 overflow-y-auto pr-1 text-sm text-slate-300">
                                                                                <?php foreach ($completedMatches as $match): ?>
                                                                                          <article class="rounded-2xl border border-white/5 bg-white/5 p-3">
                                                                                                    <div class="flex items-center justify-between">
                                                                                                              <p class="font-semibold text-white"><?= htmlspecialchars($match['opponent_name'] ?? ($match['away_team_name'] ?? '?')) ?></p>
                                                                                                              <span class="text-xs text-slate-400"><?= htmlspecialchars($match['venue'] ?? '') ?></span>
                                                                                                    </div>
                                                                                                    <p class="text-xs text-slate-400"><?= htmlspecialchars($formatMatchDate($match['date'])) ?></p>
                                                                                                    <p class="mt-2 text-sm text-white">
                                                                                                              <?= htmlspecialchars($match['result'] ?? '—') ?> · <?= htmlspecialchars($match['score'] ?? '—') ?>
                                                                                                    </p>
                                                                                          </article>
                                                                                <?php endforeach; ?>
                                                                      </div>
                                                            <?php else: ?>
                                                                      <p class="mt-3 text-xs text-slate-500">No completed fixtures available yet.</p>
                                                            <?php endif; ?>
                                                  </div>
                                                  <div>
                                                            <h3 class="text-sm font-semibold text-white">Upcoming fixtures</h3>
                                                            <?php if (!empty($upcomingMatches)): ?>
                                                                      <div class="mt-3 space-y-3 text-sm text-slate-300">
                                                                                <?php foreach ($upcomingMatches as $match): ?>
                                                                                          <article class="rounded-2xl border border-white/5 bg-white/5 p-3">
                                                                                                    <div class="flex items-center justify-between">
                                                                                                              <p class="font-semibold text-white"><?= htmlspecialchars($match['opponent_name'] ?? ($match['away_team_name'] ?? '?')) ?></p>
                                                                                                              <span class="text-xs text-slate-400"><?= htmlspecialchars($match['venue'] ?? '') ?></span>
                                                                                                    </div>
                                                                                                    <p class="text-xs text-slate-400"><?= htmlspecialchars($formatMatchDate($match['date'])) ?></p>
                                                                                                    <p class="mt-2 text-xs text-slate-500">Scheduled · <?= htmlspecialchars($match['status'] ?? 'scheduled') ?></p>
                                                                                          </article>
                                                                                <?php endforeach; ?>
                                                                      </div>
                                                            <?php else: ?>
                                                                      <p class="mt-3 text-xs text-slate-500">No upcoming fixtures scheduled.</p>
                                                            <?php endif; ?>
                                                  </div>
                                        </div>
                              </section>

                              <section class="panel rounded-2xl p-5 space-y-4">
                                        <p class="text-xs uppercase tracking-[0.3em] text-slate-500">Performance profile</p>
                                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                                  <article class="rounded-2xl border border-white/5 bg-white/5 p-3">
                                                            <p class="text-xs text-slate-400">Goals for</p>
                                                            <p class="text-2xl font-semibold text-white"><?= (int)($teamInsights['goals_for'] ?? 0) ?></p>
                                                  </article>
                                                  <article class="rounded-2xl border border-white/5 bg-white/5 p-3">
                                                            <p class="text-xs text-slate-400">Goals against</p>
                                                            <p class="text-2xl font-semibold text-white"><?= (int)($teamInsights['goals_against'] ?? 0) ?></p>
                                                  </article>
                                                  <article class="rounded-2xl border border-white/5 bg-white/5 p-3">
                                                            <p class="text-xs text-slate-400">Clean sheets</p>
                                                            <p class="text-2xl font-semibold text-white"><?= (int)($teamInsights['clean_sheets'] ?? 0) ?></p>
                                                  </article>
                                                  <article class="rounded-2xl border border-white/5 bg-white/5 p-3">
                                                            <p class="text-xs text-slate-400">Avg goals / match</p>
                                                            <p class="text-2xl font-semibold text-white"><?= number_format((float)($teamInsights['average_goals_per_match'] ?? 0), 2) ?></p>
                                                  </article>
                                        </div>
                              </section>

                              <section class="panel rounded-2xl p-5 space-y-4">
                                        <p class="text-xs uppercase tracking-[0.3em] text-slate-500">Home vs away</p>
                                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                                  <?php foreach (['home', 'away'] as $venue): ?>
                                                            <?php $venueData = $teamInsights[$venue] ?? []; ?>
                                                            <article class="space-y-2 rounded-2xl border border-white/5 bg-white/5 p-3 text-sm text-slate-300">
                                                                      <p class="text-sm font-semibold text-white"><?= ucfirst($venue) ?></p>
                                                                      <p class="text-xs text-slate-400">Matches: <?= (int)($venueData['matches'] ?? 0) ?></p>
                                                                      <div class="grid grid-cols-3 text-center text-xs">
                                                                                <span>W <?= (int)($venueData['wins'] ?? 0) ?></span>
                                                                                <span>D <?= (int)($venueData['draws'] ?? 0) ?></span>
                                                                                <span>L <?= (int)($venueData['losses'] ?? 0) ?></span>
                                                                      </div>
                                                                      <p class="text-xs text-slate-400">Points <?= (int)($venueData['points'] ?? 0) ?></p>
                                                                      <p class="text-xs text-slate-400">Goals <?= (int)($venueData['goals_for'] ?? 0) ?> - <?= (int)($venueData['goals_against'] ?? 0) ?></p>
                                                            </article>
                                                  <?php endforeach; ?>
                                        </div>
                              </section>

                              <section class="panel rounded-2xl p-5 space-y-4">
                                        <div class="flex items-center justify-between">
                                                  <p class="text-xs uppercase tracking-[0.3em] text-slate-500">Head-to-head intelligence</p>
                                                  <span class="text-xs text-slate-400">Opponent records</span>
                                        </div>
                                        <?php if (!empty($headToHead)): ?>
                                                  <div class="space-y-3 text-sm text-slate-300">
                                                            <?php foreach ($headToHead as $opponent): ?>
                                                                      <article class="rounded-2xl border border-white/5 bg-white/5 p-3">
                                                                                <div class="flex items-center justify-between">
                                                                                          <p class="font-semibold text-white"><?= htmlspecialchars($opponent['opponent_name'] ?? 'Opponent') ?></p>
                                                                                          <span class="text-xs text-slate-400">Matches <?= (int)(($opponent['wins'] ?? 0) + ($opponent['draws'] ?? 0) + ($opponent['losses'] ?? 0)) ?></span>
                                                                                </div>
                                                                                <p class="text-xs text-slate-400">Record <?= sprintf('%d-%d-%d', (int)($opponent['wins'] ?? 0), (int)($opponent['draws'] ?? 0), (int)($opponent['losses'] ?? 0)) ?></p>
                                                                                <?php if (!empty($opponent['last_result'])): ?>
                                                                                          <p class="text-xs text-slate-400">Last meeting <?= htmlspecialchars($opponent['last_result']) ?> · <?= htmlspecialchars($formatMatchDate($opponent['last_date'] ?? '')) ?></p>
                                                                                <?php endif; ?>
                                                                      </article>
                                                            <?php endforeach; ?>
                                                  </div>
                                        <?php else: ?>
                                                  <p class="text-xs text-slate-500">No head-to-head data yet.</p>
                                        <?php endif; ?>
                              </section>

                              <section class="panel rounded-2xl p-5 space-y-4">
                                        <p class="text-xs uppercase tracking-[0.3em] text-slate-500">League context &amp; difficulty</p>
                                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 text-sm text-slate-300">
                                                  <article class="rounded-2xl border border-white/5 bg-white/5 p-3">
                                                            <p class="text-xs text-slate-400">Strength of schedule (completed)</p>
                                                            <p class="text-2xl font-semibold text-white"><?= $strengthSchedule['completed'] !== null ? number_format($strengthSchedule['completed'], 2) : '—' ?></p>
                                                  </article>
                                                  <article class="rounded-2xl border border-white/5 bg-white/5 p-3">
                                                            <p class="text-xs text-slate-400">Strength of schedule (upcoming)</p>
                                                            <p class="text-2xl font-semibold text-white"><?= $strengthSchedule['upcoming'] !== null ? number_format($strengthSchedule['upcoming'], 2) : '—' ?></p>
                                                  </article>
                                        </div>
                              </section>

                              <section class="panel rounded-2xl p-5 space-y-4">
                                        <p class="text-xs uppercase tracking-[0.3em] text-slate-500">Trends &amp; visual analytics</p>
                                        <div class="grid gap-5 md:grid-cols-2">
                                                  <article class="space-y-2 rounded-2xl border border-white/5 bg-white/5 p-3 text-sm text-slate-300">
                                                            <div class="flex items-center justify-between">
                                                                      <p class="text-white">Points trend</p>
                                                                      <span class="text-xs text-slate-400"><?= count($pointsTrend) ?> matches</span>
                                                            </div>
                                                            <?php if (!empty($pointsTrend)): ?>
                                                                      <div class="flex items-end gap-1 h-16">
                                                                                <?php foreach ($pointsTrendHeights as $idx => $height): ?>
                                                                                          <span class="block h-full w-1.5 rounded-full bg-emerald-500" style="height:<?= $height ?>%;"></span>
                                                                                <?php endforeach; ?>
                                                                      </div>
                                                                      <p class="text-xs text-slate-400">Latest: <?= implode(' · ', array_map(fn ($value) => (float)$value, $pointsTrend)) ?></p>
                                                            <?php else: ?>
                                                                      <p class="text-xs text-slate-500">No points movement yet.</p>
                                                            <?php endif; ?>
                                                  </article>
                                                  <article class="space-y-2 rounded-2xl border border-white/5 bg-white/5 p-3 text-sm text-slate-300">
                                                            <div class="flex items-center justify-between">
                                                                      <p class="text-white">Goal difference trend</p>
                                                                      <span class="text-xs text-slate-400"><?= count($goalTrend) ?> matches</span>
                                                            </div>
                                                            <?php if (!empty($goalTrend)): ?>
                                                                      <div class="flex items-end gap-1 h-16">
                                                                                <?php foreach ($goalTrendHeights as $height): ?>
                                                                                          <span class="block h-full w-1.5 rounded-full bg-cyan-400" style="height:<?= $height ?>%;"></span>
                                                                                <?php endforeach; ?>
                                                                      </div>
                                                                      <p class="text-xs text-slate-400">Latest: <?= implode(' · ', array_map(fn ($value) => (int)$value, $goalTrend)) ?></p>
                                                            <?php else: ?>
                                                                      <p class="text-xs text-slate-500">No goal difference data yet.</p>
                                                            <?php endif; ?>
                                                  </article>
                                        </div>
                              </section>
                    </main>
                    <aside class="space-y-4 lg:col-span-4">
                              <section class="panel rounded-2xl p-5 space-y-3">
                                        <p class="text-xs uppercase tracking-[0.3em] text-slate-500">Team navigation</p>
                                        <div class="space-y-2">
                                                  <?php foreach ($teamNavigation as $team): ?>
                                                            <a href="<?= htmlspecialchars($base . '/league-intelligence/team/' . $team['team_id'] . $filterQuery) ?>" class="flex items-center justify-between rounded-xl border border-white/5 bg-slate-900/40 px-4 py-2 text-sm font-semibold text-white hover:border-emerald-400">
                                                                      <span><?= sprintf('#%d %s', (int)$team['position'], htmlspecialchars($team['team_name'])) ?></span>
                                                                      <span class="text-[11px] text-slate-400">View</span>
                                                            </a>
                                                  <?php endforeach; ?>
                                        </div>
                              </section>
                    </aside>
          </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../layout.php';
