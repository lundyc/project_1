<?php

require_once __DIR__ . '/../../../lib/match_repository.php';
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

// --- Club context logic copied from matches/index.php ---
require_once __DIR__ . '/../../../lib/club_repository.php';
$user = isset($user) ? $user : (function_exists('current_user') ? current_user() : null);
$roles = $_SESSION['roles'] ?? [];
$isPlatformAdmin = function_exists('user_has_role') ? user_has_role('platform_admin') : false;
$selectedClubId = 0;
$selectedClub = null;
$availableClubs = [];
if ($isPlatformAdmin) {
    $availableClubs = function_exists('get_all_clubs') ? get_all_clubs() : [];
    $requestedClubId = isset($_GET['club_id']) ? (int)$_GET['club_id'] : 0;
    if ($requestedClubId > 0) {
        $club = function_exists('get_club_by_id') ? get_club_by_id($requestedClubId) : null;
        if ($club) {
            $_SESSION['stats_club_id'] = $requestedClubId;
            $selectedClubId = $requestedClubId;
            $selectedClub = $club;
        }
    }
    if (!$selectedClub) {
        $sessionClubId = isset($_SESSION['stats_club_id']) ? (int)$_SESSION['stats_club_id'] : 0;
        if ($sessionClubId > 0) {
            $club = function_exists('get_club_by_id') ? get_club_by_id($sessionClubId) : null;
            if ($club) {
                $selectedClubId = $sessionClubId;
                $selectedClub = $club;
            }
        }
    }
    if (!$selectedClub && !empty($availableClubs)) {
        $selectedClub = $availableClubs[0];
        $selectedClubId = (int)($selectedClub['id'] ?? 0);
        if ($selectedClubId > 0) {
            $_SESSION['stats_club_id'] = $selectedClubId;
        }
    }
} else {
    $selectedClubId = (int)($user['club_id'] ?? 0);
    if ($selectedClubId > 0) {
        $selectedClub = function_exists('get_club_by_id') ? get_club_by_id($selectedClubId) : null;
        $_SESSION['stats_club_id'] = $selectedClubId;
    }
}
$clubContextName = $selectedClub['name'] ?? 'Club';
$showClubSelector = $isPlatformAdmin && !empty($availableClubs);

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
<?php
$headerTitle = 'League Intelligence';
$headerDescription = 'Detailed league data and insights.';
$headerButtons = [];
include __DIR__ . '/../../partials/header.php';
?>
<link rel="stylesheet" href="/assets/css/stats-table.css">
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
                <div class="rounded-xl bg-slate-900/80 border border-white/10 p-3 mb-4">
                <nav class="flex flex-col gap-2 mb-3" role="tablist" aria-label="Sidebar actions">
             
                        <form method="post" action="<?= htmlspecialchars($base) ?>/league-intelligence/update-week">
                            <button type="submit" class="stats-tab w-full text-left px-4 py-2.5 text-sm font-medium rounded-lg border transition-all duration-200 bg-slate-800/40 border-white/10 text-slate-300 hover:bg-slate-700/50 hover:border-white/20" role="tab">Update This Week</button>
                        </form>
                        <?php if ($currentUserIsAdmin): ?>
                        <form action="<?= htmlspecialchars($base) ?>/league-intelligence/matches" method="get" class="w-full">
                            <button type="submit" class="stats-tab w-full text-left px-4 py-2.5 text-sm font-medium rounded-lg border transition-all duration-200 bg-slate-800/40 border-white/10 text-slate-300 hover:bg-slate-700/50 hover:border-white/20" role="tab">Matches Editor</button>
                        </form>
                        <?php endif; ?>
                </nav>
                <form method="get" class="flex flex-col gap-3" role="search">
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
               
                        </div>
            </aside>
            <!-- Main Content -->
            <main class="stats-col-main col-span-7 space-y-4 min-w-0">
                <div class="rounded-xl bg-slate-800 border border-white/10 p-3">
                     <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-2 mb-2">
                        <div>
                            <h1 class="text-2xl font-semibold text-white mb-1">League Table</h1>
                            <p class="text-xs text-slate-400">
                                <?= htmlspecialchars($selectedCompetition['name'] ?? 'All competitions') ?> · <?= htmlspecialchars($selectedSeason['name'] ?? 'All seasons') ?>
                            </p>
                        </div>
                        <div class="text-xs text-slate-400 self-end"><?= count($leagueTable) ?> teams</div>
                    </div>
                
                        <table class="min-w-full bg-bg-tertiary text-text-primary text-xs rounded-xl overflow-hidden" id="matches-table">
                                    <thead>
                                    <tr class="bg-bg-secondary text-text-muted uppercase font-semibold text-xs">
                                    <th class="px-4 py-3 w-10 text-center">#</th>
                                    <th class="px-4 py-2">Team</th>
                                    <th class="px-4 py-2 w-12 text-center">Pld</th>
                                    <th class="px-4 py-2 w-10 text-center">W</th>
                                    <th class="px-4 py-2 w-10 text-center">D</th>
                                    <th class="px-4 py-2 w-10 text-center">L</th>
                                    <th class="px-4 py-2 w-12 text-center">GF</th>
                                    <th class="px-4 py-2 w-12 text-center">GA</th>
                                    <th class="px-4 py-2 w-12 text-center">GD</th>
                                    <th class="px-4 py-2 text-center">Pts</th>
                                  
                                
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <?php foreach ($leagueTable as $row): ?>
                                    <tr class="align-top text-sm text-slate-200">
                                        <td class="px-3 py-3 w-10 font-semibold text-white text-center"><?= (int)$row['position'] ?></td>
                                        <td class="px-3 py-3">
                                            <a href="<?= htmlspecialchars($base . '/league-intelligence/team/' . $row['team_id'] . $filterQuery) ?>" class="text-indigo-300 hover:text-indigo-100">
                                                <?= htmlspecialchars($row['team_name']) ?>
                                            </a>
                                        </td>
                                        <td class="px-3 py-3 w-12 text-center"><?= (int)$row['played'] ?></td>
                                        <td class="px-3 py-3 w-10 text-center"><?= (int)$row['wins'] ?></td>
                                        <td class="px-3 py-3 w-10 text-center"><?= (int)$row['draws'] ?></td>
                                        <td class="px-3 py-3 w-10 text-center"><?= (int)$row['losses'] ?></td>
                                        <td class="px-3 py-3 w-12 text-center"><?= (int)$row['goals_for'] ?></td>
                                        <td class="px-3 py-3 w-12 text-center"><?= (int)$row['goals_against'] ?></td>
                                        <td class="px-3 py-3 w-12 text-center"><?= (int)$row['goal_difference'] ?></td>
                                        <td class="px-3 py-3 text-center">
                                            <?php
                                                $totalPoints = 3 * ((int)$row['wins']) + 1 * ((int)$row['draws']);
                                            ?>
                                            <div class="text-base font-bold text-white leading-tight">
                                                <?= $totalPoints ?>
                                                <span class="text-xs text-slate-400 font-normal"></span>
                                            </div>
                                            <div class="text-xs text-slate-400">(<?= number_format((float)$row['points_per_game'], 2) ?> PPG)</div>
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
                                   
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                  
                </div>
            </main>
            <!-- Right Sidebar -->
            <aside class="stats-col-right col-span-3 min-w-0">
                <div class="rounded-xl bg-slate-800 border border-white/10 p-4">
                    <h5 class="text-slate-200 font-semibold mb-1">Next Fixtures</h5>
                    <div class="text-slate-400 text-xs mb-4">Upcoming fixtures for the selected club</div>
                    <div class="space-y-3">
                        <?php
                        $fixtures = [];
                        if ($selectedClubId > 0) {
                            $fixtures = get_li_scheduled_fixtures_for_club($selectedClubId, 10);
                        }
                         if (!empty($fixtures)): ?>
                            <?php foreach ($fixtures as $match): ?>
                                <?php
                                $homeTeam = $match['home_team'] ?? $match['home_team_name'] ?? $match['home'] ?? '?';
                                $awayTeam = $match['away_team'] ?? $match['away_team_name'] ?? $match['away'] ?? '?';
                                $matchPreviewUrl = $base . '/league-intelligence/' . urlencode($match['match_id']) . '/match-preview';
                                ?>
                                <a href="<?= htmlspecialchars($matchPreviewUrl) ?>" class="block">
                                    <article class="rounded-lg border border-white/10 bg-slate-900/80 px-3 py-3 flex flex-col gap-1 hover:bg-slate-800/90 transition">
                                        <div class="flex justify-between items-center">
                                            <span class="font-semibold text-white text-sm"><?= htmlspecialchars($homeTeam) ?> vs <br><?= htmlspecialchars($awayTeam) ?></span>
                                            <span class="text-xs text-slate-400"><?= htmlspecialchars($formatMatchDate($match['kickoff_at'] ?? $match['date'] ?? '')) ?></span>
                                        </div>
                                    </article>
                                </a>
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
