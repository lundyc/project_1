<?php
require_auth();
require_once __DIR__ . '/../../../lib/match_repository.php';
require_once __DIR__ . '/../../../lib/match_permissions.php';

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
$selectedClubId = isset($_GET['club_id']) ? (int)$_GET['club_id'] : ($selectedClubId ?? 0);
$availableClubs = $availableClubs ?? [];
$selectedClub = null;
foreach ($availableClubs as $club) {
    if ((int)$club['id'] === (int)$selectedClubId) {
        $selectedClub = $club;
        break;
    }
}
$clubContextName = $selectedClub['name'] ?? 'Saltcoats Victoria F.C.';
$showClubSelector = true;


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
<div class="stats-page w-full mt-4 text-slate-200">
    <div class="max-w-full">

        <div class="stats-three-col grid grid-cols-12 gap-2 px-4 md:px-6 lg:px-8 w-full">
            <!-- Left Sidebar -->
            <aside class="stats-col-left col-span-2 space-y-4 min-w-0">
           
                    <?php if ($canManage): ?>
                        <a href="<?= htmlspecialchars($base) ?>/matches/create" class="stats-tab w-full justify-start text-left px-4 py-2.5 text-sm font-medium rounded-lg border transition-all duration-200 bg-indigo-600 border-indigo-500 text-white shadow-lg shadow-indigo-500/20 mb-4 flex">Create Match</a>
                    <?php endif; ?>
                    <div class="rounded-xl bg-slate-900/80 border border-white/10 p-3 mt-4">
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
                        <!-- Status buttons replaced by dropdown above. Showing X of X matches removed. -->
                    </div>
                
            </aside>
            <!-- Main Content -->
            <main class="stats-col-main col-span-7 space-y-4 min-w-0">
                <div class="rounded-xl bg-slate-900/80 border border-white/10 p-3">
                    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-2 mb-2">
                        <h4 class="mb-0">Matches</h4>
                    </div>
                    <?php if ($error): ?>
                        <div class="rounded-lg bg-red-900/80 border border-red-700 text-red-200 px-4 py-3 mb-4 text-sm"><?= htmlspecialchars($error) ?></div>
                    <?php elseif ($success): ?>
                        <div class="rounded-lg bg-emerald-900/80 border border-emerald-700 text-emerald-200 px-4 py-3 mb-4 text-sm"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>
                    <?php if ($displayedMatches === 0): ?>
                        <div class="rounded-xl border border-white/10 bg-slate-800/40 p-4 text-slate-400 text-sm">No matches found for the current view.</div>
                    <?php else: ?>
                                                       <table class="min-w-full text-sm text-slate-200">
                                    <thead class="bg-slate-900/90 text-slate-100 uppercase tracking-wider">
                                        <tr>
                                             <th class="px-3 py-2">Match</th>
                                            <th class="px-3 py-2">Date</th>
                                            <th class="px-3 py-2">Time</th>
                                           
                                        
                                            <th class="px-3 py-2">Competition</th>
                                             <th class="px-3 py-2"></th>
                                            <th class="px-3 py-2 text-center">Action</th>
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
                                        <td class="px-1 py-2 max-w-[8rem] truncate align-middle" title="<?= htmlspecialchars($displayCompetition) ?>">
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
                <div class="rounded-xl bg-slate-900/80 border border-white/10 p-4">
                    <h5 class="text-slate-200 font-semibold mb-1">Match Stats</h5>
                    <div class="text-slate-400 text-xs mb-4">Overview of matches</div>
                    <div class="space-y-3">
                        <article class="rounded-lg border border-white/10 bg-slate-800/40 px-3 py-3">
                            <div class="text-xs font-semibold text-slate-300 mb-2 text-center">Total Matches</div>
                            <div class="text-2xl font-bold text-slate-100 text-center"><?= $totalMatches ?></div>
                        </article>
                        <div class="border-t border-white/10"></div>
                        <?php foreach ($orderedStatuses as $status => $count): ?>
                            <article class="rounded-lg border border-white/10 bg-slate-800/40 px-3 py-3">
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
