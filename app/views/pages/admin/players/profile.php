
<?php
require_once __DIR__ . '/../../../../lib/auth.php';
require_once __DIR__ . '/../../../../lib/player_repository.php';

$context = require_club_admin_access();
$clubId = $context['club_id'];

$playerId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($playerId <= 0) {
    http_response_code(404);
    echo '404 Not Found';
    exit;
}

$player = get_player_by_id($playerId, $clubId);
if (!$player) {
    $stmt = db()->prepare('SELECT club_id FROM players WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $playerId]);
    $foundClub = $stmt->fetchColumn();

    if ($foundClub !== false) {
        http_response_code(403);
        echo '403 Forbidden';
        exit;
    }

    http_response_code(404);
    echo '404 Not Found';
    exit;
}

$appearances = get_player_appearances($playerId, $clubId);
$eventStats = get_player_event_stats($playerId, $clubId);
$teamHistory = get_player_team_history($playerId, $clubId);
$matchIds = array_values(array_unique(array_column($appearances, 'match_id')));
$derivedStats = get_derived_stats_for_match_ids($matchIds, $playerId);

$appearanceMap = [];
foreach ($appearances as $appearance) {
    $appearanceMap[(int)$appearance['match_id']] = $appearance;
}

$displayName = isset($player['display_name']) && $player['display_name'] !== null && $player['display_name'] !== ''
    ? $player['display_name']
    : trim(($player['first_name'] ?? '') . ' ' . ($player['last_name'] ?? ''));
$title = $displayName;
$base = base_path();

ob_start();
$headerTitle = 'Player Profile - '. htmlspecialchars((string)$displayName);
$headerDescription = 'Description here';
// Add Back, Edit, and Delete Player buttons to header
$headerButtons = [
    '<a href="'.htmlspecialchars($base).'/admin/players" class="stats-tab w-50 justify-start text-left px-4 py-2.5 text-sm font-medium rounded-lg border transition-all duration-200 bg-slate-600 border-slate-500 text-white shadow-lg shadow-slate-500/20 flex hover:bg-slate-700">Back to players</a>',
    '<a href="'.htmlspecialchars($base).'/admin/players/'.(int)$playerId.'/edit" class="stats-tab w-50 justify-start text-left px-4 py-2.5 text-sm font-medium rounded-lg border transition-all duration-200 bg-indigo-600 border-indigo-500 text-white shadow-lg shadow-indigo-500/20 flex hover:text-white hover:bg-indigo-800">Edit player</a>',
    '<form method="post" action="'.htmlspecialchars($base).'/admin/players/'.(int)$playerId.'/delete" class="inline" onsubmit="return confirm(\'Are you sure you want to delete this player? This will mark them as inactive.\');" style="display:inline-block;margin:0;">
        <button type="submit" class="stats-tab w-50 justify-start text-left px-4 py-2.5 text-sm font-medium rounded-lg border transition-all duration-200 bg-red-700 border-red-600 text-white shadow-lg shadow-red-500/20 flex hover:bg-red-800">
            Delete Player
        </button>
    </form>'
];
//$headerButtons[] = '<a href="' . htmlspecialchars($base) . '/admin/players/create" class="stats-tab w-full justify-start text-left px-4 py-2.5 text-sm font-medium rounded-lg border transition-all duration-200 bg-indigo-600 border-indigo-500 text-white shadow-lg shadow-indigo-500/20 flex">Create Player</a>';
include __DIR__ . '/../../../partials/header.php';
?>
<div class="stats-page w-full mt-4 text-slate-200">
    <div class="max-w-full">
        <div class="grid grid-cols-12 gap-2 px-4 md:px-6 lg:px-8 w-full">
            <!-- Left: Player Info & Actions -->
            <aside class="col-span-2 space-y-4 min-w-0">
                <div class="rounded-xl bg-slate-900/80 border border-white/10 p-3">
                    <div class="text-muted-alt text-xs mb-1">Player profile</div>
                    <h1 class="mb-1 text-lg font-semibold text-white"><?= htmlspecialchars((string)$displayName) ?></h1>
                    <p class="text-muted-alt text-xs mb-2"><?= htmlspecialchars($player['primary_position'] ?? 'Position not set') ?></p>
                    <div class="mt-4">
                        <div class="text-slate-400 text-xs mb-1">Status</div>
                        <?php if ($player['is_active']): ?>
                            <span class="inline-flex items-center rounded-full bg-emerald-700/20 px-2 py-1 text-xs font-semibold text-emerald-300">Active</span>
                        <?php else: ?>
                            <span class="inline-flex items-center rounded-full bg-slate-700/40 px-2 py-1 text-xs font-semibold text-slate-300">Inactive</span>
                        <?php endif; ?>
                    </div>
                    <div class="mt-2">
                        <div class="text-slate-400 text-xs mb-1">Date of birth</div>
                        <div><?= $player['dob'] ? htmlspecialchars(date('d/m/Y', strtotime($player['dob']))) : 'Unknown' ?></div>
                    </div>
                    <div class="mt-2">
                        <div class="text-slate-400 text-xs mb-1">Current team</div>
                        <div><?= htmlspecialchars($player['team_name'] ?? 'Unassigned') ?></div>
                    </div>
                </div>
            </aside>
            <!-- Center: Main Content -->
            <main class="col-span-7 space-y-4 min-w-0">
                <!-- Appearances -->
                <div class="panel p-3 mb-4">
                    <div class="flex items-center justify-between mb-3">
                        <h5 class="mb-0 text-white text-lg font-semibold">Match Appearances</h5>
                    </div>
       
     
                    <?php if (empty($appearances)): ?>
                        <div class="text-slate-400 text-xs">No appearances recorded.</div>
                    <?php else: ?>
                        <div class="overflow-x-auto rounded-xl border border-white/10 bg-slate-800/40 p-2">
                            <table class="min-w-full bg-bg-tertiary text-text-primary text-xs rounded-xl overflow-hidden">
                                <thead>
                                    <tr class="bg-bg-secondary text-text-muted uppercase font-semibold text-xs">
                                        <th class="px-4 py-3 text-center">Date</th>
                                        <th class="px-4 py-3 text-center">Match</th>
                                        <th class="px-4 py-3 text-center">Side</th>
                                        <th class="px-4 py-3 text-center">Shirt</th>
                                        <th class="px-4 py-3 text-center">Role</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($appearances as $appearance): ?>
                                        <tr>
                                            <td class="px-3 py-2 text-center"><?= $appearance['kickoff_at'] ? htmlspecialchars(date('d/m/Y', strtotime($appearance['kickoff_at']))) : 'TBD' ?></td>
                                            <td class="px-4 py-3 text-center"><?= htmlspecialchars($appearance['home_team']) ?> <span class="text-slate-400">vs</span> <?= htmlspecialchars($appearance['away_team']) ?></td>
                                            <td class="px-4 py-3 text-center"><?= htmlspecialchars(ucfirst($appearance['team_side'] ?? 'unknown')) ?></td>
                                            <td class="px-4 py-3 text-center"><?= $appearance['shirt_number'] ? htmlspecialchars((string)$appearance['shirt_number']) : '—' ?></td>
                                            <td class="px-4 py-3 text-center"><?= $appearance['is_starting'] ? 'Starter' : 'Substitute' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                <!-- Event Stats -->
                <div class="panel p-3 mb-4">
                    <div class="flex items-center justify-between mb-3">
                        <h5 class="mb-0 text-white text-lg font-semibold">Event Stats</h5>
                        <span class="text-slate-400 text-xs">Aggregated from live events</span>
                    </div>
                    <?php if (empty($eventStats['matches'])): ?>
                        <div class="text-slate-400 text-xs">No event stats recorded.</div>
                    <?php else: ?>
                        <div class="overflow-x-auto rounded-xl border border-white/10 bg-slate-800/40 p-2">
                            <table class="min-w-full bg-bg-tertiary text-text-primary text-xs rounded-xl overflow-hidden">
                                <thead>
                                    <tr class="bg-bg-secondary text-text-muted uppercase font-semibold text-xs">
                                        <th class="px-4 py-3 text-center">Date</th>
                                        <th class="px-4 py-3 text-center">Match</th>
                                        <th class="px-4 py-3 text-center">Total events</th>
                                        <th class="px-4 py-3 text-center">Goals</th>
                                        <th class="px-4 py-3 text-center">Assists</th>
                                        <th class="px-4 py-3 text-center">Shots</th>
                                        <th class="px-4 py-3 text-center">Tackles</th>
                                        <th class="px-4 py-3 text-center">Key passes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($eventStats['matches'] as $row): ?>
                                        <?php $matchLabel = htmlspecialchars($row['home_team'] . ' vs ' . $row['away_team']); ?>
                                        <tr>
                                            <td class="px-3 py-2 text-center"><?= $row['kickoff_at'] ? htmlspecialchars(date('d/m/Y', strtotime($row['kickoff_at']))) : 'TBD' ?></td>
                                            <td class="px-4 py-3 text-center"><?= $matchLabel ?></td>
                                            <td class="px-4 py-3 text-center"><?= (int)$row['total_events'] ?></td>
                                            <td class="px-4 py-3 text-center"><?= (int)$row['goals'] ?></td>
                                            <td class="px-4 py-3 text-center"><?= (int)$row['assists'] ?></td>
                                            <td class="px-4 py-3 text-center"><?= (int)$row['shots'] ?></td>
                                            <td class="px-4 py-3 text-center"><?= (int)$row['tackles'] ?></td>
                                            <td class="px-4 py-3 text-center"><?= (int)$row['key_passes'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if (!empty($eventStats['seasons'])): ?>
                            <div class="mt-3">
                                <h6 class="text-slate-400 text-xs mb-2">Season totals</h6>
                                <div class="overflow-x-auto rounded-xl border border-white/10 bg-slate-800/40 p-2">
                                    <table class="min-w-full bg-bg-tertiary text-text-primary text-xs rounded-xl overflow-hidden">
                                        <thead>
                                            <tr class="bg-bg-secondary text-text-muted uppercase font-semibold text-xs">
                                                <th class="px-4 py-3 text-center">Season</th>
                                                <th class="px-4 py-3 text-center">Total events</th>
                                                <th class="px-4 py-3 text-center">Goals</th>
                                                <th class="px-4 py-3 text-center">Assists</th>
                                                <th class="px-4 py-3 text-center">Shots</th>
                                                <th class="px-4 py-3 text-center">Tackles</th>
                                                <th class="px-4 py-3 text-center">Key passes</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($eventStats['seasons'] as $season): ?>
                                                <tr>
                                                    <td class="px-4 py-3 text-center"><?= htmlspecialchars($season['season_name']) ?></td>
                                                    <td class="px-4 py-3 text-center"><?= (int)$season['total_events'] ?></td>
                                                    <td class="px-4 py-3 text-center"><?= (int)$season['goals'] ?></td>
                                                    <td class="px-4 py-3 text-center"><?= (int)$season['assists'] ?></td>
                                                    <td class="px-4 py-3 text-center"><?= (int)$season['shots'] ?></td>
                                                    <td class="px-4 py-3 text-center"><?= (int)$season['tackles'] ?></td>
                                                    <td class="px-4 py-3 text-center"><?= (int)$season['key_passes'] ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <!-- Derived Stats -->
                <div class="panel p-3 mb-4">
                    <div class="flex items-center justify-between mb-3">
                        <h5 class="mb-0 text-white text-lg font-semibold">Derived stats</h5>
                        <span class="text-slate-400 text-xs">Read-only data from cached analytics</span>
                    </div>
                    <?php if (empty($derivedStats)): ?>
                        <div class="text-slate-400 text-xs">No derived stats available for this player.</div>
                    <?php else: ?>
                        <?php foreach ($derivedStats as $entry): ?>
                            <div class="panel p-3 mb-3 bg-surface border border-soft">
                                <?php
                                    $matchInfo = $appearanceMap[$entry['match_id']] ?? null;
                                    $matchLabel = $matchInfo
                                        ? htmlspecialchars($matchInfo['home_team'] . ' vs ' . $matchInfo['away_team'])
                                        : 'Match ' . (int)$entry['match_id'];
                                ?>
                                <div class="flex items-center justify-between mb-2">
                                    <div>
                                        <div class="text-slate-400 text-xs">Match</div>
                                        <div class="font-semibold text-white text-sm"><?= $matchLabel ?></div>
                                    </div>
                                    <div class="text-slate-400 text-xs">
                                        <?= $entry['computed_at'] ? htmlspecialchars(date('d/m/Y · H:i', strtotime($entry['computed_at']))) : 'Computed recently' ?>
                                    </div>
                                </div>
                                <?php if (empty($entry['metrics'])): ?>
                                    <div class="text-slate-400 text-xs">No player metrics found in derived stats.</div>
                                <?php else: ?>
                                    <div class="grid grid-cols-3 gap-2">
                                        <?php foreach ($entry['metrics'] as $metric): ?>
                                            <div class="rounded-lg bg-slate-900/60 border border-white/10 p-2">
                                                <div class="text-slate-400 text-xs"><?= htmlspecialchars($metric['label']) ?></div>
                                                <div class="font-semibold text-white text-base"><?= htmlspecialchars((string)$metric['value']) ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <!-- Team History -->
                <?php if (!empty($teamHistory)): ?>
                <div class="panel p-3 mb-4">
                    <div class="flex items-center justify-between mb-3">
                        <h5 class="mb-0 text-white text-lg font-semibold">Team & season history</h5>
                        <span class="text-slate-400 text-xs"><?= count($teamHistory) ?> rows</span>
                    </div>
                    <div class="overflow-x-auto rounded-xl border border-white/10 bg-slate-800/40 p-2">
                        <table class="min-w-full bg-bg-tertiary text-text-primary text-xs rounded-xl overflow-hidden">
                            <thead>
                                <tr class="bg-bg-secondary text-text-muted uppercase font-semibold text-xs">
                                    <th class="px-4 py-3 text-center">Season</th>
                                    <th class="px-4 py-3 text-center">Team</th>
                                    <th class="px-4 py-3 text-center">Recorded</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($teamHistory as $row): ?>
                                    <tr>
                                        <td class="px-4 py-3 text-center"><?= htmlspecialchars($row['season_name'] ?? 'Unassigned') ?></td>
                                        <td class="px-4 py-3 text-center"><?= htmlspecialchars($row['team_name']) ?></td>
                                        <td class="px-4 py-3 text-center"><?= htmlspecialchars(date('d/m/Y', strtotime($row['created_at']))) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </main>
            <!-- Right: Player Summary -->
            <aside class="col-span-3 min-w-0">
                <div class="rounded-xl bg-slate-900/80 border border-white/10 p-4">
                    <h5 class="text-slate-200 font-semibold mb-1">Player Summary</h5>
                    <div class="text-slate-400 text-xs mb-4">Quick stats and links</div>
                    <div class="space-y-3">
                        <article class="rounded-lg border border-white/10 bg-slate-800/40 px-3 py-3">
                            <div class="text-xs font-semibold text-slate-300 mb-2 text-center">Total Appearances</div>
                            <div class="text-2xl font-bold text-slate-100 text-center "><?= count($appearances) ?></div>
                            <div class="border-t border-white/10 my-2 mb-2 mt-2"></div>
                            <div class="flex flex-row items-center justify-center gap-6 text-xs text-slate-300 w-full">
                                <div class="flex-1 flex flex-col items-center justify-center min-w-0">
                                    <span class="font-semibold">Starter:</span>
                                    <span class="text-2xl font-bold text-slate-100"><?php
                                        $starterCount = 0;
                                        $subCount = 0;
                                        foreach ($appearances as $appearance) {
                                            if (isset($appearance['is_starting']) && $appearance['is_starting']) {
                                                $starterCount++;
                                            } elseif (isset($appearance['is_starting'])) {
                                                $subCount++;
                                            }
                                        }
                                        echo $starterCount;
                                    ?></span>
                                </div>
                                <div class="h-8 border-l border-white/10 mx-4"></div>
                                <div class="flex-1 flex flex-col items-center justify-center min-w-0">
                                    <span class="font-semibold">Substitute:</span>
                                    <span class="text-2xl font-bold text-slate-100"><?php echo $subCount; ?></span>
                                </div>
                            </div>
                        </article>
                        <div class="border-t border-white/10"></div>
                        <article class="rounded-lg border border-white/10 bg-slate-800/40 px-3 py-3">
                            <div class="text-xs font-semibold text-slate-300 mb-2 text-center">Goals & Assists</div>
                            <?php
                                // Goals
                                $goals = isset($eventStats['totals']['goals']) ? (int)$eventStats['totals']['goals'] : 0;
                                if (!$goals && !empty($eventStats['matches'])) {
                                    $goals = array_sum(array_map(fn($m) => isset($m['goals']) ? (int)$m['goals'] : 0, $eventStats['matches']));
                                }
                                // Assists
                                $assists = isset($eventStats['totals']['assists']) ? (int)$eventStats['totals']['assists'] : 0;
                                if (!$assists && !empty($eventStats['matches'])) {
                                    $assists = array_sum(array_map(fn($m) => isset($m['assists']) ? (int)$m['assists'] : 0, $eventStats['matches']));
                                }
                            ?>
                            <div class="flex flex-row items-center justify-center gap-6">

                                <div class="flex-1 flex flex-col items-center justify-center min-w-0">
                                    <span class="font-semibold">Goals</span>
                                    <span class="text-2xl font-bold text-slate-100">
<?= $goals ?></span>
                                </div>
                                <div class="h-8 border-l border-white/10 mx-4"></div>
                                <div class="flex-1 flex flex-col items-center justify-center min-w-0">
                                    <span class="font-semibold">Assists</span>
                                    <span class="text-2xl font-bold text-slate-100"><?= $assists ?></span>
                                </div>
                            </div>
                        </article>
                        <!-- Playing Time -->
                        <article class="rounded-lg border border-white/10 bg-slate-800/40 px-3 py-3">
                            <div class="text-xs font-semibold text-slate-300 mb-2 text-center">Playing Time</div>
                            <?php
                                $totalMinutes = 0;
                                if (!empty($appearances)) {
                                    foreach ($appearances as $appearance) {
                                        $totalMinutes += isset($appearance['minutes_played']) ? (int)$appearance['minutes_played'] : 0;
                                    }
                                }
                            ?>
                            <div class="text-xl font-bold text-cyan-400 text-center"><?= $totalMinutes ?></div>
                            <div class="text-xs text-slate-500 text-center mt-1">Total minutes played</div>
                        </article>
                        <!-- Discipline Overview -->
                        <article class="rounded-lg border border-white/10 bg-slate-800/40 px-3 py-3">
                            <div class="text-xs font-semibold text-slate-300 mb-2 text-center">Discipline Overview</div>
                            <?php
                                $yellow = isset($eventStats['totals']['yellow_cards']) ? (int)$eventStats['totals']['yellow_cards'] : 0;
                                $red = isset($eventStats['totals']['red_cards']) ? (int)$eventStats['totals']['red_cards'] : 0;
                                if ((!$yellow || !$red) && !empty($appearances)) {
                                    foreach ($appearances as $appearance) {
                                        $yellow += isset($appearance['yellow_cards']) ? (int)$appearance['yellow_cards'] : 0;
                                        $red += isset($appearance['red_cards']) ? (int)$appearance['red_cards'] : 0;
                                    }
                                }
                            ?>
                            <div class="flex flex-row items-center justify-center gap-6">
                                <div class="flex-1 flex flex-col items-center justify-center min-w-0">
                                    <span class="font-semibold">Yellow Cards</span>
                                    <span class="text-2xl font-bold text-yellow-400 text-center"><?= $yellow ?></span>
                                </div>
                                <div class="h-8 border-l border-white/10 mx-4"></div>
                                <div class="flex-1 flex flex-col items-center justify-center min-w-0">
                                    <span class="font-semibold">Red Cards</span>
                                    <span class="text-2xl font-bold text-red-400 text-center"><?= $red ?></span>
                                </div>
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
require __DIR__ . '/../../../layout.php';