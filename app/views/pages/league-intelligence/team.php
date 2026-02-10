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
$clubContextId = (int)($_SESSION['stats_club_id'] ?? 0);
if ($clubContextId <= 0 && function_exists('current_user')) {
          $currentUser = current_user();
          $clubContextId = (int)($currentUser['club_id'] ?? 0);
}
require_once __DIR__ . '/../../../lib/team_repository.php';
$clubTeamId = 0;
if ($clubContextId > 0) {
          $clubTeams = get_teams_by_club($clubContextId);
          foreach ($clubTeams as $clubTeam) {
                    if (($clubTeam['team_type'] ?? '') === 'club') {
                              $clubTeamId = (int)$clubTeam['id'];
                              break;
                    }
          }
          if ($clubTeamId <= 0 && !empty($clubTeams)) {
                    $clubTeamId = (int)$clubTeams[0]['id'];
          }
}
$matchHistory = $teamInsights['match_history'] ?? [];
$completedMatches = array_values(array_filter($matchHistory, fn ($match) => ($match['status'] ?? '') === 'completed'));
usort($completedMatches, function ($a, $b) {
          return strtotime($b['date'] ?? '') <=> strtotime($a['date'] ?? '');
});
$upcomingMatches = array_values(array_filter($matchHistory, fn ($match) => ($match['status'] ?? '') !== 'completed'));
usort($upcomingMatches, function ($a, $b) {
          return strtotime($a['date'] ?? '') <=> strtotime($b['date'] ?? '');
});
$resultsPerPage = 10;
$fixturesPerPage = 10;
$resultsTotalPages = max(1, (int)ceil(count($completedMatches) / $resultsPerPage));
$fixturesTotalPages = max(1, (int)ceil(count($upcomingMatches) / $fixturesPerPage));
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
$currentTeamId = (int)($teamInsights['team_id'] ?? 0);
$h2hTeamId = isset($_GET['h2h_team_id']) ? (int)$_GET['h2h_team_id'] : 0;
$h2hTeams = array_values($teamNavigation);
$defaultH2hTeamId = $clubTeamId > 0 ? $clubTeamId : $currentTeamId;
if ($h2hTeamId <= 0 && !empty($h2hTeams)) {
          $h2hTeamId = $defaultH2hTeamId;
}
$h2hTeam = null;
foreach ($h2hTeams as $team) {
          if ((int)$team['team_id'] === $h2hTeamId) {
                    $h2hTeam = $team;
                    break;
          }
}
$h2hTeamId = $h2hTeam ? $h2hTeamId : (int)($h2hTeams[0]['team_id'] ?? 0);
if ($h2hTeamId > 0 && !$h2hTeam) {
          $h2hTeam = $h2hTeams[0] ?? null;
}
$h2hMatches = array_values(array_filter($matchHistory, function ($match) use ($h2hTeamId) {
          return (int)($match['opponent_id'] ?? 0) === $h2hTeamId && ($match['status'] ?? '') === 'completed';
}));
$h2hStats = [
          'played' => count($h2hMatches),
          'draws' => 0,
          'team' => ['wins' => 0, 'home_wins' => 0, 'away_wins' => 0],
          'opponent' => ['wins' => 0, 'home_wins' => 0, 'away_wins' => 0],
];
foreach ($h2hMatches as $match) {
          $result = $match['result'] ?? null;
          $venue = strtolower((string)($match['venue'] ?? ''));
          if ($result === 'W') {
                    $h2hStats['team']['wins']++;
                    if ($venue === 'home') {
                              $h2hStats['team']['home_wins']++;
                    } elseif ($venue === 'away') {
                              $h2hStats['team']['away_wins']++;
                    }
          } elseif ($result === 'L') {
                    $h2hStats['opponent']['wins']++;
                    if ($venue === 'home') {
                              $h2hStats['opponent']['away_wins']++;
                    } elseif ($venue === 'away') {
                              $h2hStats['opponent']['home_wins']++;
                    }
          } elseif ($result === 'D') {
                    $h2hStats['draws']++;
          }
}
$h2hPlayed = max(1, $h2hStats['played']);
$teamWinPct = (int)round(($h2hStats['team']['wins'] / $h2hPlayed) * 100);
$teamHomeWinPct = (int)round(($h2hStats['team']['home_wins'] / $h2hPlayed) * 100);
$teamAwayWinPct = (int)round(($h2hStats['team']['away_wins'] / $h2hPlayed) * 100);
$oppWinPct = (int)round(($h2hStats['opponent']['wins'] / $h2hPlayed) * 100);
$oppHomeWinPct = (int)round(($h2hStats['opponent']['home_wins'] / $h2hPlayed) * 100);
$oppAwayWinPct = (int)round(($h2hStats['opponent']['away_wins'] / $h2hPlayed) * 100);
?>
<?php ob_start(); ?>
<div class="w-full mt-4 text-slate-200">
    <div class="max-w-full">
        <div class="grid grid-cols-12 gap-2 px-4 md:px-6 lg:px-8 w-full">
            <!-- Left Sidebar: Navigation / Filters -->
            <aside class="col-span-2 space-y-4 min-w-0">
                <div class="space-y-2">
                    <a href="<?= htmlspecialchars($overviewLink) ?>" class="btn-flat text-xs uppercase tracking-[0.3em] w-full block mb-2">League overview</a>
                    <label class="block text-slate-400 text-xs mb-1">Team
                        <select class="block w-full rounded-md bg-slate-900/60 border border-white/20 px-2 py-1 text-xs" onchange="if (this.value) window.location.href = this.value;">
                            <?php foreach ($teamNavigation as $team): ?>
                                <option value="<?= htmlspecialchars($base . '/league-intelligence/team/' . $team['team_id'] . $filterQuery) ?>" <?= (isset($teamInsights['team_id']) && $teamInsights['team_id'] == $team['team_id']) ? 'selected' : '' ?>>
                                    <?= sprintf('#%d %s', (int)$team['position'], htmlspecialchars($team['team_name'])) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
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
                              <section class="rounded-xl border border-border-soft bg-bg-secondary p-5 shadow space-y-4">
                                        <p class="text-xs uppercase tracking-[0.3em] text-slate-500">Form &amp; momentum</p>
                                        <div class="space-y-4">
                                                  <div class="flex flex-wrap justify-center gap-2">
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

                              <section class="panel rounded-2xl p-5 space-y-4">
                                        <p class="text-xs uppercase tracking-[0.3em] text-slate-500">Home vs away</p>
                                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                                  <?php foreach (['home', 'away'] as $venue): ?>
                                                            <?php $venueData = $teamInsights[$venue] ?? []; ?>
                                                            <?php
                                                                      $matches = (int)($venueData['matches'] ?? 0);
                                                                      $wins = (int)($venueData['wins'] ?? 0);
                                                                      $draws = (int)($venueData['draws'] ?? 0);
                                                                      $losses = (int)($venueData['losses'] ?? 0);
                                                                      $total = max(1, $matches);
                                                                      $winPct = (int)round(($wins / $total) * 100);
                                                                      $drawPct = (int)round(($draws / $total) * 100);
                                                                      $lossPct = max(0, 100 - $winPct - $drawPct);
                                                            ?>
                                                            <article class="space-y-3 rounded-2xl border border-white/5 bg-white/5 p-4 text-sm text-slate-300">
                                                                      <div class="flex items-center justify-between">
                                                                                <p class="text-sm font-semibold text-white"><?= ucfirst($venue) ?></p>
                                                                                <span class="text-xs text-slate-400"><?= $matches ?> matches</span>
                                                                      </div>
                                                                      <div class="h-3 w-full overflow-hidden rounded-full bg-slate-800">
                                                                                <div class="flex h-full">
                                                                                          <div class="h-full bg-emerald-500" style="width:<?= $winPct ?>%"></div>
                                                                                          <div class="h-full bg-slate-500" style="width:<?= $drawPct ?>%"></div>
                                                                                          <div class="h-full bg-rose-500" style="width:<?= $lossPct ?>%"></div>
                                                                                </div>
                                                                      </div>
                                                                      <div class="grid grid-cols-3 text-center text-xs text-slate-400">
                                                                                <span>W <?= $wins ?> · <?= $winPct ?>%</span>
                                                                                <span>D <?= $draws ?> · <?= $drawPct ?>%</span>
                                                                                <span>L <?= $losses ?> · <?= $lossPct ?>%</span>
                                                                      </div>
                                                                      <div class="grid grid-cols-2 gap-2 text-xs text-slate-400">
                                                                                <span>Points <?= (int)($venueData['points'] ?? 0) ?></span>
                                                                                <span class="text-right">Goals <?= (int)($venueData['goals_for'] ?? 0) ?> - <?= (int)($venueData['goals_against'] ?? 0) ?></span>
                                                                      </div>
                                                            </article>
                                                  <?php endforeach; ?>
                                        </div>
                              </section>

                              <section class="panel rounded-2xl p-5 space-y-4">
                                        <div class="flex items-center justify-between">
                                                  <div>
                                                            <p class="text-xs uppercase tracking-[0.3em] text-slate-500">Head-to-head intelligence</p>
                                                            <span class="text-xs text-slate-400">Compare vs another team</span>
                                                  </div>
                                                  <form method="get" class="flex items-center gap-2 text-xs">
                                                            <?php foreach ($activeFilters as $key => $value): ?>
                                                                      <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars((string)$value) ?>">
                                                            <?php endforeach; ?>
                                                            <select name="h2h_team_id" class="rounded-md bg-slate-900/60 border border-white/20 px-2 py-1" onchange="this.form.submit()">
                                                                      <?php foreach ($h2hTeams as $team): ?>
                                                                                <option value="<?= (int)$team['team_id'] ?>" <?= $h2hTeamId === (int)$team['team_id'] ? 'selected' : '' ?>><?= htmlspecialchars($team['team_name']) ?></option>
                                                                      <?php endforeach; ?>
                                                            </select>
                                                  </form>
                                        </div>

                                        <?php if ($h2hTeam): ?>
                                                  <div class="flex flex-col gap-6 rounded-2xl border border-white/5 bg-white/5 p-4 md:flex-row md:items-center md:justify-between">
                                                            <div class="flex-1 space-y-4">
                                                                      <h3 class="text-lg font-semibold text-white"><?= htmlspecialchars($teamInsights['team_name'] ?? 'Team') ?></h3>
                                                                      <div class="space-y-3 text-sm text-slate-300">
                                                                                <div>
                                                                                          <div class="flex items-center justify-between">
                                                                                                    <span><?= (int)$h2hStats['team']['wins'] ?> Total wins</span>
                                                                                                    <span class="text-xs text-slate-500"><?= $teamWinPct ?>%</span>
                                                                                          </div>
                                                                                          <div class="mt-1 h-2 rounded-full bg-slate-800">
                                                                                                    <div class="h-2 rounded-full bg-emerald-500" style="width:<?= $teamWinPct ?>%"></div>
                                                                                          </div>
                                                                                </div>
                                                                                <div>
                                                                                          <div class="flex items-center justify-between">
                                                                                                    <span><?= (int)$h2hStats['team']['home_wins'] ?> Home wins</span>
                                                                                                    <span class="text-xs text-slate-500"><?= $teamHomeWinPct ?>%</span>
                                                                                          </div>
                                                                                          <div class="mt-1 h-2 rounded-full bg-slate-800">
                                                                                                    <div class="h-2 rounded-full bg-emerald-500" style="width:<?= $teamHomeWinPct ?>%"></div>
                                                                                          </div>
                                                                                </div>
                                                                                <div>
                                                                                          <div class="flex items-center justify-between">
                                                                                                    <span><?= (int)$h2hStats['team']['away_wins'] ?> Away wins</span>
                                                                                                    <span class="text-xs text-slate-500"><?= $teamAwayWinPct ?>%</span>
                                                                                          </div>
                                                                                          <div class="mt-1 h-2 rounded-full bg-slate-800">
                                                                                                    <div class="h-2 rounded-full bg-emerald-500" style="width:<?= $teamAwayWinPct ?>%"></div>
                                                                                          </div>
                                                                                </div>
                                                                      </div>
                                                            </div>
                                                            <div class="flex flex-col items-center justify-center gap-2 text-center">
                                                                      <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Played</p>
                                                                      <p class="text-4xl font-semibold text-emerald-200"><?= (int)$h2hStats['played'] ?></p>
                                                                      <p class="text-xs text-slate-400">Draws <strong><?= (int)$h2hStats['draws'] ?></strong></p>
                                                            </div>
                                                            <div class="flex-1 space-y-4">
                                                                      <h3 class="text-lg font-semibold text-white text-right"><?= htmlspecialchars($h2hTeam['team_name'] ?? 'Opponent') ?></h3>
                                                                      <div class="space-y-3 text-sm text-slate-300">
                                                                                <div>
                                                                                          <div class="flex items-center justify-between">
                                                                                                    <span>Total wins <?= (int)$h2hStats['opponent']['wins'] ?></span>
                                                                                                    <span class="text-xs text-slate-500"><?= $oppWinPct ?>%</span>
                                                                                          </div>
                                                                                          <div class="mt-1 h-2 rounded-full bg-slate-800">
                                                                                                    <div class="h-2 rounded-full bg-indigo-500" style="width:<?= $oppWinPct ?>%"></div>
                                                                                          </div>
                                                                                </div>
                                                                                <div>
                                                                                          <div class="flex items-center justify-between">
                                                                                                    <span>Home wins <?= (int)$h2hStats['opponent']['home_wins'] ?></span>
                                                                                                    <span class="text-xs text-slate-500"><?= $oppHomeWinPct ?>%</span>
                                                                                          </div>
                                                                                          <div class="mt-1 h-2 rounded-full bg-slate-800">
                                                                                                    <div class="h-2 rounded-full bg-indigo-500" style="width:<?= $oppHomeWinPct ?>%"></div>
                                                                                          </div>
                                                                                </div>
                                                                                <div>
                                                                                          <div class="flex items-center justify-between">
                                                                                                    <span>Away wins <?= (int)$h2hStats['opponent']['away_wins'] ?></span>
                                                                                                    <span class="text-xs text-slate-500"><?= $oppAwayWinPct ?>%</span>
                                                                                          </div>
                                                                                          <div class="mt-1 h-2 rounded-full bg-slate-800">
                                                                                                    <div class="h-2 rounded-full bg-indigo-500" style="width:<?= $oppAwayWinPct ?>%"></div>
                                                                                          </div>
                                                                                </div>
                                                                      </div>
                                                            </div>
                                                  </div>
                                        <?php else: ?>
                                                  <p class="text-xs text-slate-500">No head-to-head data yet.</p>
                                        <?php endif; ?>
                              </section>

                              <section class="panel rounded-2xl p-5 space-y-4">
                                        <div class="flex items-center justify-between">
                                                  <p class="text-xs uppercase tracking-[0.3em] text-slate-500">Results &amp; fixtures</p>
                                                  <span class="text-xs text-slate-400">Past results and upcoming matches</span>
                                        </div>
                                        <div class="grid gap-4">
                                                  <div>
                                                            <h3 class="text-sm font-semibold text-white">Recent results</h3>
                                                            <?php if (!empty($completedMatches)): ?>
                                                                      <div class="mt-3 overflow-hidden rounded-2xl border border-white/10">
                                                                                <table class="w-full text-xs text-slate-300">
                                                                                          <thead class="bg-white/5 text-slate-400">
                                                                                                    <tr>
                                                                                                              <th class="px-3 py-2 text-left">Date</th>
                                                                                                              <th class="px-3 py-2 text-left">Opponent</th>
                                                                                                              <th class="px-3 py-2 text-left">Venue</th>
                                                                                                              <th class="px-3 py-2 text-left">Result</th>
                                                                                                              <th class="px-3 py-2 text-left">Score</th>
                                                                                                    </tr>
                                                                                          </thead>
                                                                                          <tbody data-pager="results" data-per-page="<?= $resultsPerPage ?>">
                                                                                                    <?php foreach ($completedMatches as $match): ?>
                                                                                                              <tr class="border-t border-white/5">
                                                                                                                        <td class="px-3 py-2 text-slate-400"><?= htmlspecialchars($formatMatchDate($match['date'])) ?></td>
                                                                                                                        <td class="px-3 py-2 text-white"><?= htmlspecialchars($match['opponent_name'] ?? ($match['away_team_name'] ?? '?')) ?></td>
                                                                                                                        <td class="px-3 py-2 text-slate-400"><?= htmlspecialchars($match['venue'] ?? '') ?></td>
                                                                                                                        <td class="px-3 py-2 text-white"><?= htmlspecialchars($match['result'] ?? '—') ?></td>
                                                                                                                        <td class="px-3 py-2 text-white"><?= htmlspecialchars($match['score'] ?? '—') ?></td>
                                                                                                              </tr>
                                                                                                    <?php endforeach; ?>
                                                                                          </tbody>
                                                                                </table>
                                                                      </div>
                                                                      <?php if ($resultsTotalPages > 1): ?>
                                                                                <div class="mt-3 flex items-center justify-between text-xs text-slate-400" data-pager-controls="results">
                                                                                          <span data-pager-info="results"></span>
                                                                                          <div class="flex items-center gap-2">
                                                                                                    <button type="button" class="hover:text-white" data-pager-prev="results">Previous</button>
                                                                                                    <button type="button" class="hover:text-white" data-pager-next="results">Next</button>
                                                                                          </div>
                                                                                </div>
                                                                      <?php endif; ?>
                                                            <?php else: ?>
                                                                      <p class="mt-3 text-xs text-slate-500">No completed fixtures available yet.</p>
                                                            <?php endif; ?>
                                                  </div>
                                                  <div>
                                                            <h3 class="text-sm font-semibold text-white">Upcoming fixtures</h3>
                                                            <?php if (!empty($upcomingMatches)): ?>
                                                                      <div class="mt-3 overflow-hidden rounded-2xl border border-white/10">
                                                                                <table class="w-full text-xs text-slate-300">
                                                                                          <thead class="bg-white/5 text-slate-400">
                                                                                                    <tr>
                                                                                                              <th class="px-3 py-2 text-left">Date</th>
                                                                                                              <th class="px-3 py-2 text-left">Opponent</th>
                                                                                                              <th class="px-3 py-2 text-left">Venue</th>
                                                                                                              <th class="px-3 py-2 text-left">Status</th>
                                                                                                    </tr>
                                                                                          </thead>
                                                                                          <tbody data-pager="fixtures" data-per-page="<?= $fixturesPerPage ?>">
                                                                                                    <?php foreach ($upcomingMatches as $match): ?>
                                                                                                              <tr class="border-t border-white/5">
                                                                                                                        <td class="px-3 py-2 text-slate-400"><?= htmlspecialchars($formatMatchDate($match['date'])) ?></td>
                                                                                                                        <td class="px-3 py-2 text-white"><?= htmlspecialchars($match['opponent_name'] ?? ($match['away_team_name'] ?? '?')) ?></td>
                                                                                                                        <td class="px-3 py-2 text-slate-400"><?= htmlspecialchars($match['venue'] ?? '') ?></td>
                                                                                                                        <td class="px-3 py-2 text-slate-400"><?= htmlspecialchars($match['status'] ?? 'scheduled') ?></td>
                                                                                                              </tr>
                                                                                                    <?php endforeach; ?>
                                                                                          </tbody>
                                                                                </table>
                                                                      </div>
                                                                      <?php if ($fixturesTotalPages > 1): ?>
                                                                                <div class="mt-3 flex items-center justify-between text-xs text-slate-400" data-pager-controls="fixtures">
                                                                                          <span data-pager-info="fixtures"></span>
                                                                                          <div class="flex items-center gap-2">
                                                                                                    <button type="button" class="hover:text-white" data-pager-prev="fixtures">Previous</button>
                                                                                                    <button type="button" class="hover:text-white" data-pager-next="fixtures">Next</button>
                                                                                          </div>
                                                                                </div>
                                                                      <?php endif; ?>
                                                            <?php else: ?>
                                                                      <p class="mt-3 text-xs text-slate-500">No upcoming fixtures scheduled.</p>
                                                            <?php endif; ?>
                                                  </div>
                                        </div>
                              </section>

            </main>
            <!-- Right Sidebar: Team Snapshot -->
            <aside class="col-span-3 space-y-4 min-w-0">
                <section class="rounded-xl border border-border-soft bg-bg-secondary p-5 shadow space-y-5">
                    <header class="flex items-center justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-[0.3em] text-slate-500">Team snapshot</p>
                            <h2 class="text-xl font-semibold text-white">Current status</h2>
                        </div>
                    </header>
                    <div class="grid grid-cols-1 gap-3">
                        <div class="rounded-2xl border border-white/5 bg-white/5 p-4">
                            <div class="flex items-start justify-between">
                                <div>
                                    <p class="text-xs uppercase tracking-[0.25em] text-slate-500">Position</p>
                                    <p class="mt-2 text-4xl font-semibold text-white">#<?= (int)$teamInsights['position'] ?></p>
                                    <p class="text-xs text-slate-500">League rank</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs uppercase tracking-[0.25em] text-slate-500">Points</p>
                                    <p class="mt-2 text-4xl font-semibold text-white"><?= (int)$teamInsights['points'] ?></p>
                                    <p class="text-xs text-slate-500">Total points</p>
                                </div>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <article class="rounded-2xl border border-white/5 bg-white/5 p-4">
                                <p class="text-xs text-slate-400">Record</p>
                                <p class="mt-1 text-2xl font-semibold text-white"><?= htmlspecialchars($teamInsights['record'] ?? '—') ?></p>
                                <p class="text-xs text-slate-500">W-D-L</p>
                            </article>
                            <article class="rounded-2xl border border-white/5 bg-white/5 p-4">
                                <p class="text-xs text-slate-400">Goal difference</p>
                                <p class="mt-1 text-2xl font-semibold text-white"><?= (int)$teamInsights['goal_difference'] ?></p>
                                <p class="text-xs text-slate-500">Net goals</p>
                            </article>
                            <article class="rounded-2xl border border-white/5 bg-white/5 p-4">
                                <p class="text-xs text-slate-400">Current streak</p>
                                <p class="mt-1 text-2xl font-semibold text-white"><?= htmlspecialchars($teamInsights['streak'] ?? '—') ?></p>
                                <p class="text-xs text-slate-500">Recent run</p>
                            </article>
                            <article class="rounded-2xl border border-white/5 bg-white/5 p-4">
                                <p class="text-xs text-slate-400">Points per game</p>
                                <p class="mt-1 text-2xl font-semibold text-white"><?= number_format((float)($teamInsights['points_per_game'] ?? 0), 2) ?></p>
                                <p class="text-xs text-slate-500">Season average</p>
                            </article>
                        </div>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-[0.3em] text-slate-500">Performance profile</p>
                        <div class="mt-3 grid grid-cols-1 gap-3">
                            <article class="rounded-2xl border border-white/5 bg-white/5 p-4">
                                <p class="text-xs text-slate-400">Goals for</p>
                                <p class="text-2xl font-semibold text-white"><?= (int)($teamInsights['goals_for'] ?? 0) ?></p>
                            </article>
                            <article class="rounded-2xl border border-white/5 bg-white/5 p-4">
                                <p class="text-xs text-slate-400">Goals against</p>
                                <p class="text-2xl font-semibold text-white"><?= (int)($teamInsights['goals_against'] ?? 0) ?></p>
                            </article>
                            <article class="rounded-2xl border border-white/5 bg-white/5 p-4">
                                <p class="text-xs text-slate-400">Clean sheets</p>
                                <p class="text-2xl font-semibold text-white"><?= (int)($teamInsights['clean_sheets'] ?? 0) ?></p>
                            </article>
                            <article class="rounded-2xl border border-white/5 bg-white/5 p-4">
                                <p class="text-xs text-slate-400">Avg goals / match</p>
                                <p class="text-2xl font-semibold text-white"><?= number_format((float)($teamInsights['average_goals_per_match'] ?? 0), 2) ?></p>
                            </article>
                        </div>
                    </div>
                </section>
            </aside>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../layout.php';

?>
<script>
(function() {
    function initPager(key) {
        const body = document.querySelector(`[data-pager="${key}"]`);
        const controls = document.querySelector(`[data-pager-controls="${key}"]`);
        if (!body || !controls) {
            return;
        }

        const perPage = parseInt(body.getAttribute('data-per-page'), 10) || 6;
        const rows = Array.from(body.querySelectorAll('tr'));
        const totalPages = Math.max(1, Math.ceil(rows.length / perPage));
        let currentPage = 1;

        const info = controls.querySelector(`[data-pager-info="${key}"]`);
        const prev = controls.querySelector(`[data-pager-prev="${key}"]`);
        const next = controls.querySelector(`[data-pager-next="${key}"]`);

        function render() {
            const start = (currentPage - 1) * perPage;
            const end = start + perPage;
            rows.forEach((row, index) => {
                row.classList.toggle('hidden', index < start || index >= end);
            });
            if (info) {
                info.textContent = `Page ${currentPage} of ${totalPages}`;
            }
            if (prev) {
                prev.disabled = currentPage === 1;
                prev.classList.toggle('opacity-50', currentPage === 1);
                prev.classList.toggle('pointer-events-none', currentPage === 1);
            }
            if (next) {
                next.disabled = currentPage === totalPages;
                next.classList.toggle('opacity-50', currentPage === totalPages);
                next.classList.toggle('pointer-events-none', currentPage === totalPages);
            }
        }

        if (prev) {
            prev.addEventListener('click', () => {
                if (currentPage > 1) {
                    currentPage--;
                    render();
                }
            });
        }
        if (next) {
            next.addEventListener('click', () => {
                if (currentPage < totalPages) {
                    currentPage++;
                    render();
                }
            });
        }

        render();
    }

    initPager('results');
    initPager('fixtures');
})();
</script>
