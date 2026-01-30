<?php
require_auth();
require_once __DIR__ . '/../../../lib/match_repository.php';
require_once __DIR__ . '/../../../lib/match_permissions.php';
require_once __DIR__ . '/../../../lib/club_repository.php';

$user = current_user();
$roles = $_SESSION['roles'] ?? [];
$matches = get_matches_for_user($user);
$canManage = can_manage_matches($user, $roles);
$base = base_path();

$success = $_SESSION['match_form_success'] ?? null;
$error = $_SESSION['match_form_error'] ?? null;
unset($_SESSION['match_form_success'], $_SESSION['match_form_error']);

$title = 'Matches';
$isPlatformAdmin = function_exists('user_has_role') ? user_has_role('platform_admin') : false;
$selectedClubId = 0;
$selectedClub = null;
$availableClubs = [];

if ($isPlatformAdmin) {
    $availableClubs = get_all_clubs();
    $requestedClubId = isset($_GET['club_id']) ? (int)$_GET['club_id'] : 0;
    if ($requestedClubId > 0) {
        $club = get_club_by_id($requestedClubId);
        if ($club) {
            $_SESSION['stats_club_id'] = $requestedClubId;
            $selectedClubId = $requestedClubId;
            $selectedClub = $club;
        }
    }

    if (!$selectedClub) {
        $sessionClubId = isset($_SESSION['stats_club_id']) ? (int)$_SESSION['stats_club_id'] : 0;
        if ($sessionClubId > 0) {
            $club = get_club_by_id($sessionClubId);
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
        $selectedClub = get_club_by_id($selectedClubId);
        $_SESSION['stats_club_id'] = $selectedClubId;
    }
}
$clubContextName = $selectedClub['name'] ?? 'Saltcoats Victoria F.C.';
$showClubSelector = $isPlatformAdmin && !empty($availableClubs);

$liClubId = $isPlatformAdmin ? $selectedClubId : (int)($user['club_id'] ?? 0);
$liFixtures = $canManage ? get_li_scheduled_fixtures_for_club($liClubId, 12) : [];
$currentUri = $_SERVER['REQUEST_URI'] ?? '/matches';
$redirectPath = '/matches';
if ($currentUri !== '') {
    $parsedBase = $base ?: '';
    if ($parsedBase !== '' && str_starts_with($currentUri, $parsedBase)) {
        $candidate = substr($currentUri, strlen($parsedBase));
        $redirectPath = $candidate !== '' ? $candidate : '/matches';
    } elseif (str_starts_with($currentUri, '/matches')) {
        $redirectPath = $currentUri;
    }
}

$searchQuery = trim((string)($_GET['q'] ?? ''));
$statusFilter = strtolower(trim((string)($_GET['status'] ?? '')));
$opponentFilter = trim((string)($_GET['opponent'] ?? ''));
$competitionTypeFilter = trim((string)($_GET['competition_type'] ?? ''));
$dateFrom = trim((string)($_GET['date_from'] ?? ''));
$dateTo = trim((string)($_GET['date_to'] ?? ''));

$totalMatches = count($matches);
$statusCounts = [];
foreach ($matches as $match) {
    $matchStatus = strtolower(trim((string)($match['status'] ?? '')));
    if ($matchStatus === '') {
        $matchStatus = 'draft';
    }
    $statusCounts[$matchStatus] = ($statusCounts[$matchStatus] ?? 0) + 1;
}

$statusOrder = ['ready', 'draft'];
$orderedStatuses = [];
foreach ($statusOrder as $statusKey) {
    if (isset($statusCounts[$statusKey])) {
        $orderedStatuses[$statusKey] = $statusCounts[$statusKey];
    }
}
foreach ($statusCounts as $status => $count) {
    if (isset($orderedStatuses[$status])) {
        continue;
    }
    $orderedStatuses[$status] = $count;
}

$searchNormalized = strtolower($searchQuery);
$filteredMatches = array_values(
    array_filter($matches, function (array $match) use ($searchNormalized, $statusFilter, $opponentFilter, $competitionTypeFilter, $dateFrom, $dateTo) {
        // Status filter
        if ($statusFilter !== '') {
            $matchStatus = strtolower(trim((string)($match['status'] ?? '')));
            if ($matchStatus === '') {
                $matchStatus = 'draft';
            }
            if ($matchStatus !== $statusFilter) {
                return false;
            }
        }
        // Opponent filter
        if ($opponentFilter !== '') {
            $home = strtolower(trim((string)($match['home_team'] ?? '')));
            $away = strtolower(trim((string)($match['away_team'] ?? '')));
            if ($opponentFilter !== $home && $opponentFilter !== $away) {
                return false;
            }
        }
        // Competition type filter
        if ($competitionTypeFilter !== '') {
            $competition = strtolower(trim((string)($match['competition'] ?? '')));
            if ($competitionTypeFilter === 'league' && strpos($competition, 'league') === false) {
                return false;
            }
            if ($competitionTypeFilter === 'cups' && strpos($competition, 'cup') === false) {
                return false;
            }
        }
        // Date range filter
        if ($dateFrom !== '' || $dateTo !== '') {
            $kickoff = $match['kickoff_at'] ?? null;
            if ($kickoff) {
                $kickoffDate = date('Y-m-d', strtotime($kickoff));
                if ($dateFrom !== '' && $kickoffDate < $dateFrom) {
                    return false;
                }
                if ($dateTo !== '' && $kickoffDate > $dateTo) {
                    return false;
                }
            } else {
                return false;
            }
        }
        // Search query
        if ($searchNormalized === '') {
            return true;
        }
        $haystack = strtolower(implode(' ', [
            $match['home_team'] ?? '',
            $match['away_team'] ?? '',
            $match['competition'] ?? '',
            $match['venue'] ?? '',
            $match['notes'] ?? '',
        ]));
        return str_contains($haystack, $searchNormalized);
    })
);
$displayedMatches = count($filteredMatches);
$tabBasePath = ($base ?: '') . '/matches';

$formatStatusLabel = static function (string $status): string {
    if ($status === '') {
        return 'Unknown';
    }
    return ucwords(str_replace('_', ' ', $status));
};

$normalizeStatusClass = static function (string $status): string {
    $clean = preg_replace('/[^a-z0-9_-]+/i', '', strtolower($status));
    return $clean ?: 'status';
};

$buildTabUrl = static function (?string $status) use ($tabBasePath, $searchQuery) {
    $params = [];
    if ($searchQuery !== '') {
        $params['q'] = $searchQuery;
    }
    if ($status !== null && $status !== '') {
        $params['status'] = $status;
    }
    $query = http_build_query($params);
    return $tabBasePath . ($query ? ('?' . $query) : '');
};

ob_start();
?>
<?php
$headerTitle = 'Matches';
$headerDescription = 'Description here';
$headerButtons = [];
if ($canManage) {
    $headerButtons[] = '<a href="' . htmlspecialchars($base) . '/matches/create" class="stats-tab w-full justify-start text-left px-4 py-2.5 text-sm font-medium rounded-lg border transition-all duration-200 bg-indigo-600 border-indigo-500 text-white shadow-lg shadow-indigo-500/20 flex">Create Match</a>';
}
include __DIR__ . '/../../partials/header.php';
?>
<link rel="stylesheet" href="/assets/css/stats-table.css">
<div class="stats-page w-full mt-4 text-slate-200">
    <div class="max-w-full">
        <!-- Standard 3-Column Layout: grid-cols-12 -->
        <div class="grid grid-cols-12 gap-2 px-4 md:px-6 lg:px-8 w-full">
            <!-- Left Sidebar: Filters -->
            <aside class="col-span-2 space-y-4 min-w-0">
                <div class="rounded-xl bg-slate-900/80 border border-white/10 p-3">
                    <h6 class="text-slate-300 text-xs font-semibold mb-2">Filters</h6>
                    <form method="get" class="flex flex-col gap-3" role="search">
                        <div>
                            <label class="block text-slate-400 text-xs mb-1" for="status-filter">Status</label>
                            <select id="status-filter" name="status" class="block w-full rounded-md bg-slate-900/60 border border-white/20 px-2 py-1 text-xs">
                                <option value="">All</option>
                                <?php foreach ($orderedStatuses as $status => $count): ?>
                                    <?php $statusLabel = $formatStatusLabel($status); ?>
                                    <option value="<?= htmlspecialchars($status) ?>" <?= $statusFilter === $status ? 'selected' : '' ?>><?= htmlspecialchars($statusLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-slate-400 text-xs mb-1" for="opponent-filter">Opponent</label>
                            <select id="opponent-filter" name="opponent" class="block w-full rounded-md bg-slate-900/60 border border-white/20 px-2 py-1 text-xs">
                                <option value="">All Opponents</option>
                                <?php
                                $opponents = [];
                                foreach ($matches as $m) {
                                    $home = trim($m['home_team'] ?? '');
                                    $away = trim($m['away_team'] ?? '');
                                    if ($home !== '' && !in_array($home, $opponents, true)) $opponents[] = $home;
                                    if ($away !== '' && !in_array($away, $opponents, true)) $opponents[] = $away;
                                }
                                sort($opponents, SORT_NATURAL | SORT_FLAG_CASE);
                                foreach ($opponents as $opponent): ?>
                                    <option value="<?= htmlspecialchars($opponent) ?>" <?= $opponentFilter === $opponent ? 'selected' : '' ?>><?= htmlspecialchars($opponent) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-slate-400 text-xs mb-1" for="competition-type-filter">Competition</label>
                            <select id="competition-type-filter" name="competition_type" class="block w-full rounded-md bg-slate-900/60 border border-white/20 px-2 py-1 text-xs">
                                <option value="">All Competitions</option>
                                <option value="league" <?= $competitionTypeFilter === 'league' ? 'selected' : '' ?>>League</option>
                                <option value="cups" <?= $competitionTypeFilter === 'cups' ? 'selected' : '' ?>>Cups</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-slate-400 text-xs mb-1" for="date-from">From</label>
                            <input id="date-from" name="date_from" type="date" class="block w-full rounded-md bg-slate-900/60 border border-white/20 px-2 py-1 text-xs" value="<?= htmlspecialchars($dateFrom) ?>">
                        </div>
                        <div>
                            <label class="block text-slate-400 text-xs mb-1" for="date-to">To</label>
                            <input id="date-to" name="date_to" type="date" class="block w-full rounded-md bg-slate-900/60 border border-white/20 px-2 py-1 text-xs" value="<?= htmlspecialchars($dateTo) ?>">
                        </div>
                        <div>
                            <button type="submit" class="w-full rounded-md bg-indigo-600 text-white px-4 py-2 text-xs font-semibold hover:bg-indigo-700 transition">Apply Filters</button>
                        </div>
                    </form>
                </div>
            </aside>
            <!-- Main Content -->
            <main class="col-span-7 space-y-4 min-w-0">
                <div class="rounded-xl bg-slate-800 border border-white/10 p-3">
                    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-2 mb-2">
                        <div>
                            <h1 class="text-2xl font-semibold text-white mb-1">Matches</h1>
                            <p class="text-xs text-slate-400">
                                All matches for <?= htmlspecialchars($clubContextName) ?>.
                            </p>
                        </div>
                    </div>
                    <?php if ($error): ?>
                        <div class="rounded-lg bg-red-900/80 border border-red-700 text-red-200 px-4 py-3 mb-4 text-sm"><?= htmlspecialchars($error) ?></div>
                    <?php elseif ($success): ?>
                        <div class="rounded-lg bg-emerald-900/80 border border-emerald-700 text-emerald-200 px-4 py-3 mb-4 text-sm"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>
                    <?php if ($displayedMatches === 0): ?>
                        <div class="rounded-xl border border-white/10 bg-slate-800/40 p-4 text-slate-400 text-sm">No matches found for the current view.</div>
                    <?php else: ?>
                              <table class="min-w-full bg-bg-tertiary text-text-primary text-xs rounded-xl overflow-hidden" id="matches-table">
                                    <thead>
                                    <tr class="bg-bg-secondary text-text-muted uppercase font-semibold text-xs">
                                    <th class="px-4 py-3">Match</th>
                                    <th class="px-4 py-3">Date</th>
                                    <th class="px-4 py-3">Time</th>
                                    <th class="px-4 py-3">Competition</th>
                                    <th class="px-4 py-3"></th>
                                    <th class="px-4 py-3 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($filteredMatches as $match): ?>
                                    <?php
                                        $matchId = (int)$match['id'];
                                        $matchUrl = htmlspecialchars($base . '/matches/' . $matchId . '/desk');
                                        $homeTeam = trim($match['home_team'] ?? '');
                                        $awayTeam = trim($match['away_team'] ?? '');
                                        $title = ($homeTeam !== '' || $awayTeam !== '') ? $homeTeam . ' vs ' . $awayTeam : 'Untitled match';
                                        $kickoffTs = $match['kickoff_at'] ? strtotime($match['kickoff_at']) : null;
                                        $dateLabel = $kickoffTs ? date('d/m/Y', $kickoffTs) : 'TBD';
                                        $timeLabel = $kickoffTs ? date('H:i', $kickoffTs) : 'TBD';
                                        $status = strtolower(trim((string)($match['status'] ?? '')));
                                        if ($status === '') {
                                            $status = 'draft';
                                        }
                                        $statusLabel = $formatStatusLabel($status);
                                        $statusClass = $normalizeStatusClass($status);
                                        $competition = $match['competition'] ?? '';
                                        $displayCompetition = $competition;
                                        if ($competition !== '') {
                                            $displayCompetition = preg_replace('/\b(Planning|Financial)\b.*?(Cup)?$/i', '', $competition);
                                            $displayCompetition = trim($displayCompetition);
                                            if (stripos($competition, 'Cup') !== false) {
                                                $displayCompetition .= ' Cup';
                                            }
                                        }
                                        $venue = $match['venue'] ?? '';
                                    ?>
                                    <tr>
                                        <td class="px-3 py-2">
                                            <a href="<?= $matchUrl ?>" class="text-indigo-300 hover:text-indigo-100">
                                                <?= htmlspecialchars($title) ?>
                                                <?php if ($venue !== ''): ?>
                                                    <br><span class="text-xs text-slate-400">@ <?= htmlspecialchars($venue) ?></span>
                                                <?php endif; ?>
                                            </a>
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap">
                                          
                                                <?= htmlspecialchars($dateLabel) ?>
                                            
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap">
                                        
                                                <?= htmlspecialchars($timeLabel) ?>
                                          
                                        </td>
                                        <td class="px-3 py-2" title="<?= htmlspecialchars($displayCompetition) ?>">
                                            <?php
                                            if ($displayCompetition !== '') {
                                                $shortComp = $displayCompetition;
                                                if (stripos($shortComp, 'cup') !== false) {
                                                    // Always show 'Cup' at the end, trim before if needed
                                                    $parts = preg_split('/\s+/', $shortComp);
                                                    $beforeCup = [];
                                                    $foundCup = false;
                                                    foreach ($parts as $part) {
                                                        if (stripos($part, 'cup') !== false) {
                                                            $foundCup = true;
                                                            break;
                                                        }
                                                        $beforeCup[] = $part;
                                                    }
                                                    $main = implode(' ', $beforeCup);
                                                    if (mb_strlen($main) > 10) {
                                                        $main = mb_substr($main, 0, 10) . '…';
                                                    }
                                                    $shortComp = trim($main) . ' Cup';
                                                } elseif (stripos($shortComp, 'league') !== false) {
                                                    // For leagues, show full name, never trim
                                                    $shortComp = $displayCompetition;
                                                } elseif (stripos($shortComp, 'division') !== false) {
                                                    // For divisions, show full name, never trim
                                                    $shortComp = $displayCompetition;
                                                } else {
                                                    // For other competitions, trim to 14 chars
                                                    $shortComp = mb_substr($displayCompetition, 0, 14);
                                                    if (mb_strlen($displayCompetition) > 14) {
                                                        $shortComp = rtrim($shortComp) . '…';
                                                    }
                                                }
                                                echo htmlspecialchars($shortComp);
                                            }
                                            ?>
                                        </td>
                                        <td class="px-3 py-3 whitespace-nowrap text-center">
                                            <?php if ($status === 'ready'): ?>
<span class="rounded-full p-2.5 text-center text-sm text-green-400 shadow-sm">
    <i class="fa-solid fa-circle-check"></i>
</span>


                                            <?php elseif ($status === 'draft'): ?>
                                               <span class="rounded-full p-2.5 text-center text-sm text-amber-400 shadow-sm"><i class="fa-solid fa-pencil"></i></span>
                                            <?php else: ?>
                                                <span class="status-badge status-badge--<?= htmlspecialchars($statusClass) ?> text-xs px-2 py-1 rounded-full bg-slate-700/40 text-slate-300 border border-white/10">
                                                    <?= htmlspecialchars($statusLabel) ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-3 py-3 whitespace-nowrap text-center">
                                            <div class="flex justify-center">
                                                <a href="<?= htmlspecialchars($base . '/stats/match/' . $matchId) ?>" class="inline-flex items-center rounded-md bg-slate-700/60 px-2 py-1 text-xs text-slate-200 hover:bg-slate-700/80 transition" aria-label="View match">
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>
                                                <a href="<?= htmlspecialchars($base . '/matches/' . $matchId . '/edit') ?>" class="inline-flex items-center rounded-md bg-indigo-700/60 px-2 py-1 text-xs text-white hover:bg-indigo-700 transition" aria-label="Edit match">
                                                    <i class="fa-solid fa-pen"></i>
                                                </a>
                                                <form method="post" action="<?= htmlspecialchars($base . '/api/matches/' . $matchId . '/delete') ?>" class="inline" onsubmit="return confirm('Delete this match?');">
                                                    <button type="submit" class="inline-flex items-center rounded-md bg-red-700/60 px-2 py-1 text-xs text-white hover:bg-red-800 transition" aria-label="Delete match">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                    <input type="hidden" name="match_id" value="<?= htmlspecialchars((string)$matchId) ?>">
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </main>
            <!-- Right Sidebar -->
            <aside class="stats-col-right col-span-3 min-w-0">
                <?php if ($canManage): ?>
                    <div class="rounded-xl bg-slate-800 border border-white/10 p-3 mb-4">
                        <div class="flex items-center justify-between gap-2 mb-1">
                            <h5 class="text-slate-200 font-semibold">Upcoming Fixtures</h5>

                        </div>
                        <div class="text-slate-400 text-xs mb-3">Scheduled fixtures not yet in matches.</div>
                        <?php if ($liClubId <= 0): ?>
                            <div class="rounded-lg border border-white/10 bg-slate-900/70 p-3 text-xs text-slate-400">
                                Select a club to view fixtures.
                            </div>
                        <?php elseif (empty($liFixtures)): ?>
                            <div class="rounded-lg border border-white/10 bg-slate-900/70 p-3 text-xs text-slate-400">
                                No upcoming fixtures found.
                            </div>
                        <?php else: ?>
                            <div class="space-y-2">
                                <?php foreach ($liFixtures as $fixture): ?>
                                    <?php
                                    $fixtureId = (int)$fixture['match_id'];
                                    $homeName = trim((string)($fixture['home_team_name'] ?? ''));
                                    $awayName = trim((string)($fixture['away_team_name'] ?? ''));
                                    $kickoffTs = $fixture['kickoff_at'] ? strtotime($fixture['kickoff_at']) : null;
                                    $dateLabel = $kickoffTs ? date('d M', $kickoffTs) : 'TBD';
                                    $timeLabel = $kickoffTs ? date('H:i', $kickoffTs) : '';
                                    $competitionName = trim((string)($fixture['competition_name'] ?? ''));
                                    ?>
                                    <article class="rounded-lg border border-white/10 bg-slate-900/80 px-3 py-2">
                                        <div class="flex items-center justify-between gap-2">
                                            <div class="min-w-0">
                                                <div class="text-xs text-slate-400"><?= htmlspecialchars($dateLabel) ?><?= $timeLabel ? ' · ' . htmlspecialchars($timeLabel) : '' ?></div>
                                                <div class="text-sm text-slate-100 truncate">
                                                    <?= htmlspecialchars($homeName !== '' ? $homeName : 'Home') ?>
                                                    <span class="text-slate-400">vs</span><br>
                                                    <?= htmlspecialchars($awayName !== '' ? $awayName : 'Away') ?>
                                                </div>
                                                <?php if ($competitionName !== ''): ?>
                                                    <div class="text-[11px] text-slate-500 truncate"><?= htmlspecialchars($competitionName) ?></div>
                                                <?php endif; ?>
                                            </div>
                                            <form method="post" action="<?= htmlspecialchars($base . '/api/league-intelligence/fixtures/accept') ?>" class="shrink-0 self-center">
                                                <input type="hidden" name="li_match_id" value="<?= htmlspecialchars((string)$fixtureId) ?>">
                                                <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirectPath) ?>">
                                                <?php if ($isPlatformAdmin): ?>
                                                    <input type="hidden" name="club_id" value="<?= htmlspecialchars((string)$liClubId) ?>">
                                                <?php endif; ?>
                                                <button type="submit" class="inline-flex items-center rounded-md border border-slate-700/80 bg-slate-900/80 px-2 py-1 text-xs font-semibold text-slate-200 transition hover:bg-emerald-600 hover:border-emerald-300 hover:text-white">
                                                    Accept
                                                </button>
                                            </form>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <div class="rounded-xl bg-slate-800 border border-white/10 p-3">
                    <h5 class="text-slate-200 font-semibold mb-1">Match Stats</h5>
                    <div class="text-slate-400 text-xs mb-4">Overview of matches</div>
                    <div class="space-y-3">
                        <article class="rounded-lg border border-white/10 bg-slate-900/80 px-3 py-3">
                            <div class="text-xs font-semibold text-slate-300 mb-2 text-center">Total Matches</div>
                            <div class="text-2xl font-bold text-slate-100 text-center"><?= $totalMatches ?></div>
                        </article>
                        <div class="border-t border-white/10"></div>
                        <?php foreach ($orderedStatuses as $status => $count): ?>
                            <article class="rounded-lg border border-white/10 bg-slate-900/80 px-3 py-3">
                                <div class="text-xs font-semibold text-slate-300 mb-2 text-center"><?= htmlspecialchars($formatStatusLabel($status)) ?></div>
                                <div class="text-xl font-bold text-indigo-400 text-center"><?= $count ?></div>
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
require __DIR__ . '/../../layout.php';
