<?php
require_auth();

$base = base_path();
$title = 'Match Statistics';
global $clubId, $selectedClub;

$base = base_path();
$title = 'Match Statistics';
$match = $match ?? [];
$homeTeam = $match['home_team'] ?? ($match['home_team_name'] ?? 'Home');
$awayTeam = $match['away_team'] ?? ($match['away_team_name'] ?? 'Away');
$competition = $match['competition'] ?? ($match['competition_name'] ?? 'Competition');
$matchStatusLabel = $matchStatusLabel ?? ($match['status'] ?? 'Scheduled');
$matchDateLabel = $matchDateLabel ?? 'TBD';
$matchTimeLabel = $matchTimeLabel ?? 'TBD';
$matchId = (int)($match['id'] ?? 0);

$matchScore = null;
$pairs = [
    ['home_score', 'away_score'],
    ['home_team_score', 'away_team_score'],
    ['home_goals', 'away_goals'],
    ['home_team_goals', 'away_team_goals'],
];
foreach ($pairs as [$homeKey, $awayKey]) {
    if (isset($match[$homeKey], $match[$awayKey])) {
        $homeValue = $match[$homeKey];
        $awayValue = $match[$awayKey];
        if ($homeValue !== null && $awayValue !== null && $homeValue !== '' && $awayValue !== '') {
            $matchScore = sprintf('%s - %s', $homeValue, $awayValue);
            break;
        }
    }
}

// Prepare derived stats data
$derivedStats = $derivedStats ?? [];
$events = $events ?? [];
$periods = $periods ?? [];
$byType = $derivedStats['by_type_team'] ?? [];
$totals = $derivedStats['totals'] ?? [];
$phase2 = $derivedStats['phase_2'] ?? [];
$phase2ByPeriod = $phase2['by_period'] ?? [];
$phase2Buckets = $phase2['per_15_minute'] ?? [];

$defaultCounts = ['home' => 0, 'away' => 0, 'unknown' => 0];
$safe = function (string $key) use ($byType, $defaultCounts) {
    return $byType[$key] ?? $defaultCounts;
};

// Helper functions for rendering
function gauge_arc_path(float $share, float $offset): ?string
{
    if ($share <= 0) {
        return null;
    }
    $startAngle = M_PI + M_PI * $offset;
    $endAngle = M_PI + M_PI * min(1, $offset + $share);
    $startX = 60 + 50 * cos($startAngle);
    $startY = 60 + 50 * sin($startAngle);
    $endX = 60 + 50 * cos($endAngle);
    $endY = 60 + 50 * sin($endAngle);
    $largeArcFlag = ($share >= 1 - 1e-6) ? 1 : 0;
    return sprintf('M %.2f %.2f A 50 50 0 %d 1 %.2f %.2f', $startX, $startY, $largeArcFlag, $endX, $endY);
}

function build_overview_gauge(int $home, int $away): array
{
    $total = max(0, $home + $away);
    if ($total === 0) {
        return [
            'homePercent' => 0,
            'awayPercent' => 0,
            'total' => 0,
            'homePath' => null,
            'awayPath' => null,
        ];
    }
    $homeShare = $home / $total;
    $awayShare = $away / $total;
    $clampedHome = round(min(1, max(0, $homeShare)), 10);
    $clampedAway = round(min(1, max(0, $awayShare)), 10);
    return [
        'homePercent' => (int)round($clampedHome * 100),
        'awayPercent' => (int)round($clampedAway * 100),
        'total' => $total,
        'homePath' => gauge_arc_path($clampedHome, 0),
        'awayPath' => gauge_arc_path($clampedAway, $clampedHome),
    ];
}

function render_graph_card(string $label, int $homeVal, int $awayVal, string $note = ''): string
{
    global $match, $selectedClub, $primaryTeamId;
    $gauge = build_overview_gauge($homeVal, $awayVal);
    $clubName = $selectedClub['name'] ?? ($match['club_name'] ?? 'Club');
    $isHome = isset($match['home_team_id'], $primaryTeamId) && ((int)$primaryTeamId === (int)$match['home_team_id']);
    $isAway = isset($match['away_team_id'], $primaryTeamId) && ((int)$primaryTeamId === (int)$match['away_team_id']);
    // Always show the selected club's share and value
    $clubValue = $isHome ? $homeVal : ($isAway ? $awayVal : 0);
    $clubPercent = $isHome ? $gauge['homePercent'] : ($isAway ? $gauge['awayPercent'] : 0);
    $oppValue = $isHome ? $awayVal : ($isAway ? $homeVal : 0);
    $oppPercent = $isHome ? $gauge['awayPercent'] : ($isAway ? $gauge['homePercent'] : 0);
    $shareLabel = $isHome ? 'Home Share' : ($isAway ? 'Away Share' : ($clubName . ' Share'));
    $ariaLabel = htmlspecialchars(sprintf('%s %s %d%% Opponent %d%%', $label, $shareLabel, $clubPercent, $oppPercent));
    ob_start();
    ?>
    <div class="overview-graph-card">
        <div class="phase2-card-label"><?= htmlspecialchars($label) ?></div>
        <?php if ($note): ?>
            <div class="phase2-highlight"><?= htmlspecialchars($note) ?></div>
        <?php endif; ?>
        <div class="overview-gauge-wrapper">
            <div class="overview-gauge" role="img" aria-label="<?= $ariaLabel ?>">
                <svg viewBox="0 0 120 60" aria-hidden="true">
                    <path class="overview-gauge-base" d="M 10 60 A 50 50 0 0 1 110 60"></path>
                    <?php if ($gauge['homePath']): ?>
                        <path class="overview-gauge-fill overview-gauge-home" d="<?= $gauge['homePath'] ?>"></path>
                    <?php endif; ?>
                    <?php if ($gauge['awayPath']): ?>
                        <path class="overview-gauge-fill overview-gauge-away" d="<?= $gauge['awayPath'] ?>"></path>
                    <?php endif; ?>
                </svg>
                <div class="overview-gauge-labels">
                    <span class="overview-gauge-label-home"><?= $isHome ? $gauge['homePercent'] : ($isAway ? $gauge['awayPercent'] : 0) ?>%</span>
                    <span class="overview-gauge-label-away"><?= $isHome ? $gauge['awayPercent'] : ($isAway ? $gauge['homePercent'] : 0) ?>%</span>
                </div>
                <div class="overview-gauge-center">
                    <div class="overview-gauge-percent"><?= $clubPercent ?>%</div>
                    <div class="overview-gauge-caption"><?= $shareLabel ?></div>
                </div>
            </div>
            <div class="overview-stats">
                <div class="overview-stats-row overview-stats-values">
                    <span><?= $clubValue ?></span>
                    <span><?= $oppValue ?></span>
                </div>
            </div>
            <?php if ($note): ?>
                <div class="overview-gauge-note"><?= htmlspecialchars($note) ?></div>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function render_shot_accuracy_card(
    string $label,
    string $teamName,
    string $onLabel,
    string $offLabel,
    int $onTarget,
    int $offTarget,
    int $onPct,
    int $offPct,
    int $total
): string {
    $gauge = build_overview_gauge($onTarget, $offTarget);
    ob_start();
    ?>
    <div class="overview-graph-card">
        <div class="phase2-card-label"><?= htmlspecialchars($label) ?></div>
        <div class="overview-gauge-wrapper">
            <div class="overview-gauge" role="img" aria-label="<?= htmlspecialchars($teamName . ' on target share ' . $onPct . '%') ?>">
                <svg viewBox="0 0 120 60" aria-hidden="true">
                    <path class="overview-gauge-base" d="M 10 60 A 50 50 0 0 1 110 60"></path>
                    <?php if ($gauge['homePath']): ?>
                        <path class="overview-gauge-fill overview-gauge-home" d="<?= $gauge['homePath'] ?>"></path>
                    <?php endif; ?>
                    <?php if ($gauge['awayPath']): ?>
                        <path class="overview-gauge-fill overview-gauge-away" d="<?= $gauge['awayPath'] ?>"></path>
                    <?php endif; ?>
                </svg>
                <div class="overview-gauge-labels">
                    <span class="overview-gauge-label-home"><?= $onPct ?>%</span>
                    <span class="overview-gauge-label-away"><?= $offPct ?>%</span>
                </div>
                <div class="overview-gauge-center">
                    <div class="overview-gauge-percent"><?= $onPct ?>%</div>
                    <div class="overview-gauge-caption"><?= htmlspecialchars($teamName) ?> on target share</div>
                </div>
            </div>
            <div class="overview-stats">
                <div class="overview-stats-row overview-stats-labels">
                    <span class="overview-stats-label"><?= htmlspecialchars($onLabel) ?></span>
                    <span class="overview-stats-label"><?= htmlspecialchars($offLabel) ?></span>
                </div>
                <div class="overview-stats-row overview-stats-values">
                    <span><?= $onTarget ?></span>
                    <span><?= $offTarget ?></span>
                </div>
                <div class="overview-stats-row overview-stats-values">
                    <span><?= $onPct ?>%</span>
                    <span><?= $offPct ?>%</span>
                </div>
            </div>
            <div class="overview-gauge-note text-xs text-muted-alt">
                <?= $total ? htmlspecialchars(sprintf('%d of %d shots', $onTarget, $total)) : 'No shots recorded' ?>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// Calculate shot accuracy data
$shotOnTargetCounts = $safe('shot_on_target');
$shotOffTargetCounts = $safe('shot_off_target');
$goalCounts = $safe('goal');

$homeOnTargetCount = (int)$shotOnTargetCounts['home'] + (int)$goalCounts['home'];
$awayOnTargetCount = (int)$shotOnTargetCounts['away'] + (int)$goalCounts['away'];
$homeOffTargetCount = (int)$shotOffTargetCounts['home'];
$awayOffTargetCount = (int)$shotOffTargetCounts['away'];

$homeTotalForDisplay = $homeOnTargetCount + $homeOffTargetCount;
$awayTotalForDisplay = $awayOnTargetCount + $awayOffTargetCount;

$homeShotGauge = build_overview_gauge($homeOnTargetCount, $homeOffTargetCount);
$awayShotGauge = build_overview_gauge($awayOnTargetCount, $awayOffTargetCount);

// Calculate half comparison data
$firstHalfCounts = $phase2ByPeriod['1H'] ?? $defaultCounts;
$secondHalfCounts = $phase2ByPeriod['2H'] ?? $defaultCounts;

// Calculate set pieces and discipline
$cornerCounts = $safe('corner');
$freeKickCounts = $safe('free_kick');
$penaltyCounts = $safe('penalty');
$yellowCardCounts = $safe('yellow_card');
$redCardCounts = $safe('red_card');

$setPieces = [
    'home' => (int)$cornerCounts['home'] + (int)$freeKickCounts['home'] + (int)$penaltyCounts['home'],
    'away' => (int)$cornerCounts['away'] + (int)$freeKickCounts['away'] + (int)$penaltyCounts['away'],
];

$cards = [
    'home' => (int)$yellowCardCounts['home'] + (int)$redCardCounts['home'],
    'away' => (int)$yellowCardCounts['away'] + (int)$redCardCounts['away'],
];

// Prepare period data
$periodRows = [
    ['label' => 'First Half', 'home' => (int)$firstHalfCounts['home'], 'away' => (int)$firstHalfCounts['away']],
    ['label' => 'Second Half', 'home' => (int)$secondHalfCounts['home'], 'away' => (int)$secondHalfCounts['away']],
    ['label' => 'Extra Time', 'home' => (int)($phase2ByPeriod['ET']['home'] ?? 0), 'away' => (int)($phase2ByPeriod['ET']['away'] ?? 0)],
];

// Prepare phase data
$phaseBuckets = ['build_up', 'transition', 'defensive_block', 'set_piece', 'unknown'];
$phaseTeamCounts = ['home' => [], 'away' => [], 'unknown' => []];
foreach ($phaseBuckets as $bucket) {
    $phaseTeamCounts['home'][$bucket] = 0;
    $phaseTeamCounts['away'][$bucket] = 0;
    $phaseTeamCounts['unknown'][$bucket] = 0;
}
foreach ($events as $event) {
    $phaseKey = trim((string)($event['phase'] ?? ''));
    if (!in_array($phaseKey, $phaseBuckets, true)) {
        $phaseKey = 'unknown';
    }
    $teamSide = ($event['team_side'] ?? '') === 'away' ? 'away' : (($event['team_side'] ?? '') === 'home' ? 'home' : 'unknown');
    $phaseTeamCounts[$teamSide][$phaseKey]++;
}

// Calculate momentum windows
$momentumWindowSize = 300;
$sortedMomentumEvents = $events;
usort($sortedMomentumEvents, fn($a, $b) => ((int)($a['match_second'] ?? 0) <=> (int)($b['match_second'] ?? 0)));
$momentumTypeWeights = [
    'goal' => 5,
    'shot' => 3,
    'big_chance' => 3,
    'chance' => 2,
    'corner' => 2,
    'free_kick' => 2,
    'penalty' => 4,
    'foul' => 1,
    'yellow_card' => 1,
    'red_card' => 2,
    'mistake' => 1,
    'turnover' => 1,
    'good_play' => 2,
    'highlight' => 1,
    'other' => 1,
];

$lastEventSecond = 0;
foreach ($events as $ev) {
    $sec = (int)($ev['match_second'] ?? 0);
    if ($sec > $lastEventSecond) {
        $lastEventSecond = $sec;
    }
}

$windowCount = max(1, (int)ceil(($lastEventSecond + 1) / $momentumWindowSize));
$momentumWindows = [];
$momentumMaxValue = 0;
for ($windowIndex = 0; $windowIndex < $windowCount; $windowIndex++) {
    $startSecond = $windowIndex * $momentumWindowSize;
    $endSecond = $startSecond + $momentumWindowSize;
    $homeScore = 0;
    $awayScore = 0;
    foreach ($sortedMomentumEvents as $event) {
        $matchSecond = (int)($event['match_second'] ?? 0);
        if ($matchSecond < $startSecond) {
            continue;
        }
        if ($matchSecond >= $endSecond) {
            break;
        }
        $teamSide = ($event['team_side'] ?? '') === 'away' ? 'away' : (($event['team_side'] ?? '') === 'home' ? 'home' : 'unknown');
        $typeKey = strtolower(trim((string)($event['event_type_key'] ?? '')));
        if ($typeKey === '') {
            $typeKey = guess_type_key_from_label($event['event_type_label'] ?? '') ?? 'other';
        }
        if ($typeKey === '') {
            $typeKey = 'other';
        }
        $baseWeight = $momentumTypeWeights[$typeKey] ?? 1;
        $importance = max(1, min(5, (int)($event['importance'] ?? 3)));
        $score = $baseWeight * $importance;
        if ($teamSide === 'home') {
            $homeScore += $score;
        } elseif ($teamSide === 'away') {
            $awayScore += $score;
        }
    }
    $momentumMaxValue = max($momentumMaxValue, $homeScore, $awayScore);
    $momentumWindows[] = [
        'label' => sprintf('%d-%d', (int)($startSecond / 60), (int)($endSecond / 60)),
        'home' => round($homeScore, 1),
        'away' => round($awayScore, 1),
    ];
}
if ($momentumMaxValue <= 0) {
    $momentumMaxValue = 1;
}

// Prepare timeline data
$periodWindows = [];
foreach ($events as $event) {
    $typeKey = $event['event_type_key'] ?? '';
    if ($typeKey !== 'period_start' && $typeKey !== 'period_end') {
        continue;
    }
    $label = trim((string)($event['notes'] ?? '')) ?: 'Period';
    if (!isset($periodWindows[$label])) {
        $periodWindows[$label] = ['label' => $label, 'start' => null, 'end' => null];
    }
    if ($typeKey === 'period_start' && $periodWindows[$label]['start'] === null) {
        $periodWindows[$label]['start'] = (int)$event['match_second'];
    }
    if ($typeKey === 'period_end' && $periodWindows[$label]['end'] === null) {
        $periodWindows[$label]['end'] = (int)$event['match_second'];
    }
}

foreach ($periodWindows as &$win) {
    if ($win['start'] !== null && ($win['end'] === null || $win['end'] < $win['start'])) {
        $win['end'] = $lastEventSecond;
    }
}
unset($win);

$preferred = ['First Half', 'Second Half', 'Extra Time 1', 'Extra Time 2'];
$orderedLabels = [];
foreach ($preferred as $label) {
    if (isset($periodWindows[$label]) && $periodWindows[$label]['start'] !== null) {
        $orderedLabels[] = $label;
    }
}
$remaining = array_diff(array_keys($periodWindows), $orderedLabels);
usort($remaining, function ($a, $b) use ($periodWindows) {
    $aStart = $periodWindows[$a]['start'] ?? PHP_INT_MAX;
    $bStart = $periodWindows[$b]['start'] ?? PHP_INT_MAX;
    return $aStart <=> $bStart;
});
$orderedLabels = array_merge($orderedLabels, $remaining);

$timeline = [];
foreach ($orderedLabels as $label) {
    $timeline[$label] = [];
}

foreach ($events as $event) {
    $placed = false;
    foreach ($orderedLabels as $label) {
        $win = $periodWindows[$label];
        if ($win['start'] === null) {
            continue;
        }
        $end = $win['end'] ?? $lastEventSecond;
        $sec = (int)$event['match_second'];
        if ($sec >= (int)$win['start'] && $sec <= (int)$end) {
            $timeline[$label][] = $event;
            $placed = true;
            break;
        }
    }
    if (!$placed) {
        $timeline['Ungrouped'][] = $event;
    }
}

if (empty($timeline['Ungrouped'])) {
    unset($timeline['Ungrouped']);
}

// Calculate running score for timeline
$runningScore = ['home' => 0, 'away' => 0];
$periodEndScores = [];
foreach ($orderedLabels as $label) {
    $rows = $timeline[$label] ?? [];
    foreach ($rows as $event) {
        $typeKey = strtolower(trim((string)($event['event_type_key'] ?? '')));
        $typeLabel = strtolower(trim((string)($event['event_type_label'] ?? '')));
        $isGoal = (strpos($typeKey, 'goal') !== false || strpos($typeLabel, 'goal') !== false);
        if ($isGoal) {
            $teamSide = ($event['team_side'] ?? '') === 'away' ? 'away' : (($event['team_side'] ?? '') === 'home' ? 'home' : 'home');
            $runningScore[$teamSide]++;
        }
    }
    $periodEndScores[$label] = ['home' => $runningScore['home'], 'away' => $runningScore['away']];
}

function display_event_label(array $event): string {
    return $event['event_type_label'] ?? $event['label'] ?? $event['type'] ?? $event['event_type_key'] ?? 'Event';
}


ob_start();
// --- Club context switcher for platform admins ---
$isPlatformAdmin = user_has_role('platform_admin');
$availableClubs = $availableClubs ?? (function_exists('get_all_clubs') ? get_all_clubs() : []);
$selectedClubId = $clubId ?? 0;
$selectedClub = $selectedClub ?? null;
$clubContextName = $selectedClub['name'] ?? ($match['club_name'] ?? 'Club');
$showClubSelector = $isPlatformAdmin && !empty($availableClubs);
$pageTitle = 'Match Statistics';
$pageDescription = 'Match-level context for ' . htmlspecialchars($homeTeam) . ' vs ' . htmlspecialchars($awayTeam) . '.';

?>
<?php
$headerTitle = 'Match Statistics';
$headerDescription = 'Match-level context for ' . htmlspecialchars($homeTeam) . ' vs ' . htmlspecialchars($awayTeam) . '.';
$headerButtons = [];
    $headerButtons[] = '<a href="/api/stats/match/report_pdf?match_id=' . urlencode($matchId) . '&club_id=' . urlencode($clubId ?? '') . '" class="justify-start text-left px-4 py-2.5 text-sm font-medium rounded-lg border transition-all duration-200 bg-indigo-600 border-indigo-500 text-white shadow-lg shadow-indigo-500/20 flex">Export PDF</a>';
include __DIR__ . '/../../partials/header.php';
?>

<div class="w-full mt-4 text-slate-200">
    <div class="max-w-full">

        <div class="grid grid-cols-12 gap-2 px-4 md:px-6 lg:px-8 w-full">
            <aside class="col-span-2 space-y-4 min-w-0">
                <nav class="flex flex-col gap-2 mb-3" role="tablist" aria-label="Match statistics tabs">
                    <?php $tabs = [
                        'match-overview' => 'Overview',
                        'match-team-performance' => 'Team Performance',
                        'match-player-performance' => 'Player Performance',
                        'match-timeline' => 'Timeline',
                        'match-visual-analytics' => 'Visual Analytics',
                    ]; ?>
                    <?php foreach ($tabs as $tabId => $tabLabel): ?>
                        <button
                            type="button"
                            class="stats-tab w-full text-left px-4 py-2.5 text-sm font-medium rounded-lg border transition-all duration-200 <?= $tabId === 'match-overview' ? 'bg-indigo-600 border-indigo-500 text-white shadow-lg shadow-indigo-500/20' : 'bg-slate-800/40 border-white/10 text-slate-300 hover:bg-slate-700/50 hover:border-white/20' ?>"
                            role="tab"
                            aria-selected="<?= $tabId === 'match-overview' ? 'true' : 'false' ?>"
                            data-tab-id="<?= htmlspecialchars($tabId) ?>">
                            <?= htmlspecialchars($tabLabel) ?>
                        </button>
                    <?php endforeach; ?>
                </nav>
            </aside>
            <main class="col-span-7 space-y-4 min-w-0">
                <div class="stats-panels">
        <section id="match-overview-panel" data-panel-id="match-overview">
            <div class="rounded-xl bg-slate-900/80 border border-white/10 p-3">
                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3 mb-3">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-100 mb-1">Overview</h3>
                        <p class="text-sm text-slate-400 mb-0">Match-specific metrics powered by explicit match_id.</p>
                    </div>
                </div>

                <div class="summary-score summary-score-card mb-3">
                    <div class="summary-team summary-team-home">
                        <div class="summary-team-label">Home</div>
                        <div class="summary-team-name" id="summary-home-name">—</div>
                    </div>
                    <div class="summary-scoreline">
                        <div class="summary-score-digits">
                            <span class="score-number" id="summary-home-score">—</span>
                            <span class="score-separator">:</span>
                            <span class="score-number" id="summary-away-score">—</span>
                        </div>
                        <div class="text-muted-alt text-xs" id="summary-competition">—</div>
                    </div>
                    <div class="summary-team summary-team-away text-end">
                        <div class="summary-team-label text-end">Away</div>
                        <div class="summary-team-name" id="summary-away-name">—</div>
                    </div>
                </div>

                <div class="match-events-summary mb-3" id="match-events-summary">
                    <div class="text-xs text-slate-500">Loading events…</div>
                </div>

                <div id="match-overview-comparison" class="comparison-list"></div>

                <div class="match-overview-graphs" id="match-overview-gauges">
                    <!-- JS will render gauge cards here -->
                </div>

                <div class="mb-3">
                    <h5 class="text-sm font-semibold text-slate-100 mb-2">Shot Accuracy</h5>
                    <div class="match-overview-graphs" id="match-overview-accuracy">
                        <!-- JS will render shot accuracy cards here -->
                    </div>
                    <!-- Debug output removed -->
                </div>

                <div class="mb-3">
                    <h5 class="text-sm font-semibold text-slate-100 mb-2">First Half vs Second Half</h5>
                    <div class="match-overview-graphs" id="match-overview-halves">
                        <!-- JS will render half comparison cards here -->
                    </div>
                </div>

                <div class="mb-3">
                    <h5 class="text-sm font-semibold text-slate-100 mb-2">Set Pieces & Discipline</h5>
                    <div class="match-overview-graphs" id="match-overview-set-pieces">
                        <!-- JS will render set pieces and discipline cards here -->
                    </div>
                </div>


                <div id="match-overview-loading" class="text-xs text-slate-500 mt-3">Loading match statistics…</div>
                <div id="match-overview-error" class="text-xs text-red-400 mt-3" style="display:none;">Unable to load match overview.</div>
            </div>
        </section>

        <section id="match-team-performance-panel" data-panel-id="match-team-performance" style="display:none;">
            <div class="rounded-xl bg-slate-900/80 border border-white/10 p-3">
                <h3 class="text-lg font-semibold text-slate-100 mb-2">Team Performance</h3>
                <p class="text-sm text-slate-400 mb-4">Home vs away event counts for this match.</p>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-slate-100">
                        <thead class="bg-slate-800/80 text-slate-200 uppercase tracking-wide">
                            <tr>
                                <th class="px-3 py-2 text-left"></th>
                                <th class="px-3 py-2 text-center"><?= htmlspecialchars($homeTeam) ?></th>
                                <th class="px-3 py-2 text-center"><?= htmlspecialchars($awayTeam) ?></th>
                            </tr>
                        </thead>
                        <tbody id="match-team-performance-table" class="divide-y divide-white/5"></tbody>
                    </table>
                </div>
                <div id="match-team-performance-loading" class="text-xs text-slate-500 mt-3">Loading team performance…</div>
                <div id="match-team-performance-error" class="text-xs text-red-400 mt-3" style="display:none;">Unable to load team performance.</div>

                <div class="mt-4">
                    <h5 class="text-sm font-semibold text-slate-100 mb-2">Period & Phase Snapshots</h5>
                    <p class="text-xs text-slate-400 mb-3">Period totals map to derived_stats.phase_2.by_period; phases come from event.phase</p>
                    <div class="comparison-extension">
                        <div class="period-comparison">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="text-light fw-semibold">Events per period</div>
                            </div>
                            <div class="period-table">
                                <div class="period-row period-row-header">
                                    <span>Period</span>
                                    <span><?= htmlspecialchars($homeTeam) ?></span>
                                    <span><?= htmlspecialchars($awayTeam) ?></span>
                                </div>
                                <?php foreach ($periodRows as $row): ?>
                                    <div class="period-row">
                                        <span class="period-label"><?= htmlspecialchars($row['label']) ?></span>
                                        <span>H <?= htmlspecialchars((string)$row['home']) ?></span>
                                        <span>A <?= htmlspecialchars((string)$row['away']) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="period-section-note">Source: derived_stats.phase_2.by_period</div>
                        </div>
                        <div class="phase-comparison">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="text-light fw-semibold">Phase distribution</div>
                            </div>
                            <div class="phase-table">
                                <div class="phase-row phase-row-header">
                                    <span>Phase</span>
                                    <span>Home</span>
                                    <span>Away</span>
                                </div>
                                <?php foreach ($phaseBuckets as $bucket): ?>
                                    <div class="phase-row">
                                        <span class="phase-label"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $bucket))) ?></span>
                                        <span>H <?= htmlspecialchars((string)($phaseTeamCounts['home'][$bucket] ?? 0)) ?></span>
                                        <span>A <?= htmlspecialchars((string)($phaseTeamCounts['away'][$bucket] ?? 0)) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="phase-section-note">Phase tags pulled directly from event.phase (unknown shown when missing)</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="match-player-performance-panel" data-panel-id="match-player-performance" style="display:none;">
            <div class="rounded-xl bg-slate-900/80 border border-white/10 p-3">
                <h3 class="text-lg font-semibold text-slate-100 mb-3">Player Performance</h3>
                
                <div id="match-player-performance-loading" class="text-xs text-slate-500">Loading player performance…</div>
                <div id="match-player-performance-error" class="text-xs text-red-400" style="display:none;">Unable to load player performance.</div>
                <div id="match-player-performance-empty" class="text-xs text-slate-500" style="display:none;">No player data available for this match.</div>
                
                <div id="match-player-performance-content" style="display:none;">
                    <!-- Starting XI -->
                    <div class="mb-4">
                        <h4 class="text-sm font-semibold text-slate-100 mb-3">Starting XI</h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm text-slate-100">
                                <thead class="bg-slate-800/80 text-slate-200 uppercase tracking-wide sticky-player-header">
                                    <tr>
                                        <th class="px-3 py-2 text-center" style="width: 60px;">#</th>
                                        <th class="px-3 py-2 text-left">Player</th>
                                        <th class="px-3 py-2 text-left" style="width: 100px;">Position</th>
                                        <th class="px-3 py-2 text-center" style="width: 80px;">Goals</th>
                                        <th class="px-3 py-2 text-center" style="width: 80px;">Cards</th>
                                    </tr>
                                </thead>
                                <tbody id="match-starting-xi-tbody" class="divide-y divide-white/5"></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Substitutes -->
                    <div id="match-substitutes-section" style="display:none;">
                        <h4 class="text-sm font-semibold text-slate-100 mb-3">Substitutes</h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm text-slate-100">
                                <thead class="bg-slate-800/80 text-slate-200 uppercase tracking-wide sticky-player-header">
                                    <tr>
                                        <th class="px-3 py-2 text-center" style="width: 60px;">#</th>
                                        <th class="px-3 py-2 text-left">Player</th>
                                        <th class="px-3 py-2 text-left" style="width: 100px;">Position</th>
                                        <th class="px-3 py-2 text-center" style="width: 80px;">Goals</th>
                                        <th class="px-3 py-2 text-center" style="width: 80px;">Cards</th>
                                    </tr>
                                </thead>
                                <tbody id="match-substitutes-tbody" class="divide-y divide-white/5"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="match-timeline-panel" data-panel-id="match-timeline" style="display:none;">
            <div class="rounded-xl bg-slate-900/80 border border-white/10 p-3">
                <h3 class="text-lg font-semibold text-slate-100 mb-2">Timeline</h3>
                <p class="text-sm text-slate-400 mb-4">Match events by period</p>

                <?php if (empty($events)): ?>
                    <div class="text-muted-alt text-sm">No events yet.</div>
                <?php else: ?>
                    <div class="summary-timeline">
                        <?php foreach ($timeline as $periodLabel => $rows): ?>
                            <?php
                            $sorted = $rows;
                            usort($sorted, function ($a, $b) {
                                return ((int)($a['match_second'] ?? 0)) <=> ((int)($b['match_second'] ?? 0));
                            });
                            $score = $periodEndScores[$periodLabel] ?? ['home' => 0, 'away' => 0];
                            $hasPeriodEnd = false;
                            foreach ($sorted as $checkEv) {
                                $k = strtolower((string)($checkEv['event_type_key'] ?? ''));
                                if ($k === 'period_end') {
                                    $hasPeriodEnd = true;
                                    break;
                                }
                            }
                            $scoreText = $hasPeriodEnd ? ((int)$score['home'] . ' - ' . (int)$score['away']) : '';
                            ?>
                            <div class="stl-period">
                                <div class="stl-period-header">
                                    <div class="stl-period-name"><?= htmlspecialchars($periodLabel) ?></div>
                                    <div class="stl-period-score"><?= htmlspecialchars($scoreText) ?></div>
                                </div>
                                <div class="stl-grid">
                                    <?php foreach ($sorted as $ev): ?>
                                        <?php
                                        $side = $ev['team_side'] === 'away' ? 'away' : ($ev['team_side'] === 'home' ? 'home' : 'home');
                                        $eventLabel = display_event_label($ev);
                                        $minuteVal = isset($ev['match_second']) && $ev['match_second'] !== '' ? (int)floor((int)($ev['match_second'] ?? 0) / 60) : 0;
                                        $playerName = $ev['match_player_name'] ?? '';
                                        $kLower = strtolower((string)($ev['event_type_key'] ?? ''));
                                        $lblLower = strtolower((string)($ev['event_type_label'] ?? ''));
                                        $cardClass = '';
                                        if (strpos($kLower, 'yellow') !== false || strpos($lblLower, 'yellow') !== false) {
                                            $cardClass = 'yellow';
                                        } elseif (strpos($kLower, 'red') !== false || strpos($lblLower, 'red') !== false) {
                                            $cardClass = 'red';
                                        }
                                        $isGoal = (strpos($kLower, 'goal') !== false || strpos($lblLower, 'goal') !== false);
                                        $isPeriodMarker = ($kLower === 'period_start' || $kLower === 'period_end');
                                        ?>
                                        <?php if ($isPeriodMarker): ?>
                                            <div class="stl-divider">
                                                <div class="stl-separator">
                                                    <?= $minuteVal ?>' ·
                                                    <?= htmlspecialchars($eventLabel) ?>
                                                    <?= (int)$score['home'] ?> - <?= (int)$score['away'] ?>
                                                </div>
                                            </div>
                                            <?php continue; ?>
                                        <?php endif; ?>
                                        <div class="stl-row">
                                            <div class="stl-cell stl-home">
                                                <?php if ($side === 'home'): ?>
                                                    <div class="stl-event">
                                                        <span class="stl-minute"><?= $minuteVal ?>'</span>
                                                        <?php if ($cardClass): ?>
                                                            <span class="stl-card<?= $cardClass === 'red' ? ' red' : '' ?>"><i class="fa-solid fa-square"></i></span>
                                                        <?php endif; ?>
                                                        <div class="stl-text">
                                                            <div class="stl-title"><?= htmlspecialchars($eventLabel) ?><?= $isGoal ? ' <i class="fa-solid fa-futbol"></i>' : '' ?></div>
                                                            <?php if (!empty($playerName)): ?>
                                                                <div class="stl-player"><?= htmlspecialchars($playerName) ?></div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="stl-cell stl-away">
                                                <?php if ($side === 'away'): ?>
                                                    <div class="stl-event">
                                                        <span class="stl-minute"><?= $minuteVal ?>'</span>
                                                        <?php if ($cardClass): ?>
                                                            <span class="stl-card<?= $cardClass === 'red' ? ' red' : '' ?>"><i class="fa-solid fa-square"></i></span>
                                                        <?php endif; ?>
                                                        <div class="stl-text">
                                                            <div class="stl-title"><?= htmlspecialchars($eventLabel) ?><?= $isGoal ? ' <i class="fa-solid fa-futbol"></i>' : '' ?></div>
                                                            <?php if (!empty($playerName)): ?>
                                                                <div class="stl-player"><?= htmlspecialchars($playerName) ?></div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <div class="stl-fulltime">
                            <small>Full time</small>
                            <span><?= (int)$runningScore['home'] ?> - <?= (int)$runningScore['away'] ?></span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section id="match-visual-analytics-panel" data-panel-id="match-visual-analytics" style="display:none;">
            <div class="rounded-xl bg-slate-900/80 border border-white/10 p-3">
                <h3 class="text-lg font-semibold text-slate-100 mb-2">Visual Analytics</h3>
                <p class="text-sm text-slate-400 mb-4">Event distribution, momentum analysis and match timeline.</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
                    <div class="rounded-lg border border-white/10 bg-slate-800/40 p-4 min-h-[400px] flex flex-col">
                        <p class="text-sm text-slate-400 mb-3 text-center">Home Team Shot Map</p>
                        <div class="flex-1 flex flex-col gap-3">
                            <div class="flex-1">
                                <p class="text-xs text-slate-500 mb-1 text-center">Shot origins</p>
                                <svg id="home-shot-origins" viewBox="0 0 100 100" class="w-full h-full" style="max-height: 180px;">
                                    <!-- Half pitch with penalty box -->
                                    <rect x="5" y="5" width="90" height="70" stroke="#e5e7eb" stroke-width="1" fill="none"/>
                                    <rect x="20" y="5" width="60" height="30" stroke="#e5e7eb" stroke-width="1" fill="none"/>
                                    <rect x="35" y="5" width="30" height="15" stroke="#e5e7eb" stroke-width="1" fill="none"/>
                                    <circle cx="50" cy="28" r="1.5" fill="#e5e7eb"/>
                                    <path d="M35 35 A15 15 0 0 0 65 35" stroke="#e5e7eb" fill="none"/>
                                    <g id="home-origin-markers"></g>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-xs text-slate-500 mb-1 text-center">Shot targets</p>
                                <svg id="home-shot-targets" viewBox="0 0 120 60" class="w-full h-full" style="max-height: 200px;">
                                    <!-- Goal face with grid -->
                                    <line x1="20" y1="10" x2="20" y2="50" stroke="#e5e7eb" stroke-width="3"/>
                                    <line x1="100" y1="10" x2="100" y2="50" stroke="#e5e7eb" stroke-width="3"/>
                                    <line x1="20" y1="10" x2="100" y2="10" stroke="#e5e7eb" stroke-width="3"/>
                                    <line x1="30" y1="20" x2="30" y2="50" stroke="#9ca3af" stroke-width="1"/>
                                    <line x1="40" y1="20" x2="40" y2="50" stroke="#9ca3af" stroke-width="1"/>
                                    <line x1="50" y1="20" x2="50" y2="50" stroke="#9ca3af" stroke-width="1"/>
                                    <line x1="60" y1="20" x2="60" y2="50" stroke="#9ca3af" stroke-width="1"/>
                                    <line x1="70" y1="20" x2="70" y2="50" stroke="#9ca3af" stroke-width="1"/>
                                    <line x1="80" y1="20" x2="80" y2="50" stroke="#9ca3af" stroke-width="1"/>
                                    <line x1="90" y1="20" x2="90" y2="50" stroke="#9ca3af" stroke-width="1"/>
                                    <line x1="30" y1="20" x2="90" y2="20" stroke="#9ca3af" stroke-width="1"/>
                                    <line x1="30" y1="30" x2="90" y2="30" stroke="#9ca3af" stroke-width="1"/>
                                    <line x1="30" y1="40" x2="90" y2="40" stroke="#9ca3af" stroke-width="1"/>
                                    <line x1="30" y1="50" x2="90" y2="50" stroke="#9ca3af" stroke-width="1"/>
                                    <g id="home-target-markers"></g>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-lg border border-white/10 bg-slate-800/40 p-4 min-h-[400px] flex flex-col">
                        <p class="text-sm text-slate-400 mb-3 text-center">Away Team Shot Map</p>
                        <div class="flex-1 flex flex-col gap-3">
                            <div class="flex-1">
                                <p class="text-xs text-slate-500 mb-1 text-center">Shot origins</p>
                                <svg id="away-shot-origins" viewBox="0 0 100 100" class="w-full h-full" style="max-height: 180px;">
                                    <!-- Half pitch with penalty box -->
                                    <rect x="5" y="5" width="90" height="70" stroke="#e5e7eb" stroke-width="1" fill="none"/>
                                    <rect x="20" y="5" width="60" height="30" stroke="#e5e7eb" stroke-width="1" fill="none"/>
                                    <rect x="35" y="5" width="30" height="15" stroke="#e5e7eb" stroke-width="1" fill="none"/>
                                    <circle cx="50" cy="28" r="1.5" fill="#e5e7eb"/>
                                    <path d="M35 35 A15 15 0 0 0 65 35" stroke="#e5e7eb" fill="none"/>
                                    <g id="away-origin-markers"></g>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-xs text-slate-500 mb-1 text-center">Shot targets</p>
                                <svg id="away-shot-targets" viewBox="0 0 120 60" class="w-full h-full" style="max-height: 200px;">
                                    <!-- Goal face with grid -->
                                    <line x1="20" y1="10" x2="20" y2="50" stroke="#e5e7eb" stroke-width="3"/>
                                    <line x1="100" y1="10" x2="100" y2="50" stroke="#e5e7eb" stroke-width="3"/>
                                    <line x1="20" y1="10" x2="100" y2="10" stroke="#e5e7eb" stroke-width="3"/>
                                    <line x1="30" y1="20" x2="30" y2="50" stroke="#9ca3af" stroke-width="1"/>
                                    <line x1="40" y1="20" x2="40" y2="50" stroke="#9ca3af" stroke-width="1"/>
                                    <line x1="50" y1="20" x2="50" y2="50" stroke="#9ca3af" stroke-width="1"/>
                                    <line x1="60" y1="20" x2="60" y2="50" stroke="#9ca3af" stroke-width="1"/>
                                    <line x1="70" y1="20" x2="70" y2="50" stroke="#9ca3af" stroke-width="1"/>
                                    <line x1="80" y1="20" x2="80" y2="50" stroke="#9ca3af" stroke-width="1"/>
                                    <line x1="90" y1="20" x2="90" y2="50" stroke="#9ca3af" stroke-width="1"/>
                                    <line x1="30" y1="20" x2="90" y2="20" stroke="#9ca3af" stroke-width="1"/>
                                    <line x1="30" y1="30" x2="90" y2="30" stroke="#9ca3af" stroke-width="1"/>
                                    <line x1="30" y1="40" x2="90" y2="40" stroke="#9ca3af" stroke-width="1"/>
                                    <line x1="30" y1="50" x2="90" y2="50" stroke="#9ca3af" stroke-width="1"/>
                                    <g id="away-target-markers"></g>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
                    <div class="rounded-lg border border-white/10 bg-slate-800/40 p-4 text-center min-h-[140px]">
                        <p class="text-sm text-slate-400 mb-2">Possession Zones</p>
                        <span class="text-xs text-slate-500">Placeholder</span>
                    </div>
                    <div class="rounded-lg border border-white/10 bg-slate-800/40 p-4 text-center min-h-[140px]">
                        <p class="text-sm text-slate-400 mb-2">Pass Network</p>
                        <span class="text-xs text-slate-500">Placeholder</span>
                    </div>
                </div>

                <div class="mb-4">
                    <h5 class="text-sm font-semibold text-slate-100 mb-2">Event Distribution</h5>
                    <p class="text-xs text-slate-400 mb-2">Phase 2 · 15 minute buckets</p>
                    <div class="distribution-legend">
                        <span class="distribution-legend-pill distribution-legend-pill-home">H = Home</span>
                        <span class="distribution-legend-pill distribution-legend-pill-away">A = Away</span>
                        <span class="distribution-legend-pill distribution-legend-pill-unknown">U = Unassigned</span>
                    </div>
                    <div class="phase2-card">
                        <div class="distribution-table">
                            <?php if (empty($phase2Buckets)): ?>
                                <div class="distribution-row">
                                    <div class="distribution-label">No distribution data yet.</div>
                                </div>
                            <?php else: ?>
                                <?php foreach ($phase2Buckets as $bucket):
                                    $label = $bucket['label'] ?? 'Bucket';
                                    $homeBucket = (int)$bucket['home'];
                                    $awayBucket = (int)$bucket['away'];
                                    $unknownBucket = (int)$bucket['unknown'];
                                    $bucketTotal = $homeBucket + $awayBucket + $unknownBucket;
                                    $homePct = $bucketTotal > 0 ? round(($homeBucket / $bucketTotal) * 100, 1) : 0;
                                    $awayPct = $bucketTotal > 0 ? round(($awayBucket / $bucketTotal) * 100, 1) : 0;
                                    $unknownPct = $bucketTotal > 0 ? round(($unknownBucket / $bucketTotal) * 100, 1) : 0;
                                    $chartLabel = sprintf('%s bucket: Home %.1f%% · Away %.1f%% · Unknown %.1f%%', $label, $homePct, $awayPct, $unknownPct);
                                ?>
                                    <div class="distribution-row">
                                        <div class="distribution-info">
                                            <div class="distribution-label"><?= htmlspecialchars($label) ?></div>
                                            <div class="distribution-unknown"><?= $bucketTotal ?> events · <?= $unknownBucket ?> unassigned</div>
                                            <div class="distribution-chart" role="img" aria-label="<?= htmlspecialchars($chartLabel) ?>">
                                                <?php if ($homePct > 0): ?>
                                                    <span class="distribution-segment distribution-segment-home" style="width: <?= $homePct ?>%;"></span>
                                                <?php endif; ?>
                                                <?php if ($awayPct > 0): ?>
                                                    <span class="distribution-segment distribution-segment-away" style="width: <?= $awayPct ?>%;"></span>
                                                <?php endif; ?>
                                                <?php if ($unknownPct > 0): ?>
                                                    <span class="distribution-segment distribution-segment-unknown" style="width: <?= $unknownPct ?>%;"></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="distribution-statistics">
                                            <div class="distribution-chart-meta">
                                                <span class="distribution-pill distribution-pill-home">H <?= $homePct ?>%</span>
                                                <span class="distribution-pill distribution-pill-away">A <?= $awayPct ?>%</span>
                                                <span class="distribution-pill distribution-pill-unknown">U <?= $unknownPct ?>%</span>
                                            </div>
                                            <div class="distribution-counts">
                                                <span class="distribution-count distribution-count-home">H <?= $homeBucket ?></span>
                                                <span class="distribution-count distribution-count-away">A <?= $awayBucket ?></span>
                                                <span class="distribution-count distribution-count-unknown">U <?= $unknownBucket ?></span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <h5 class="text-sm font-semibold text-slate-100 mb-2">Descriptive Momentum (not predictive)</h5>
                    <p class="text-xs text-slate-400 mb-2">Rolling 5-minute windows · weighted by event type and importance</p>
                    <div class="momentum-panel">
                        <div class="momentum-note">Home vs Away momentum per 5-minute bucket (unknown phases count like any other event).</div>
                        <div class="momentum-chart">
                            <?php foreach ($momentumWindows as $window):
                                $homePct = $momentumMaxValue > 0 ? min(100, max(0, round($window['home'] / $momentumMaxValue * 100, 1))) : 0;
                                $awayPct = $momentumMaxValue > 0 ? min(100, max(0, round($window['away'] / $momentumMaxValue * 100, 1))) : 0;
                            ?>
                                <div class="momentum-row">
                                    <div class="momentum-row-header">
                                        <span class="momentum-row-label"><?= htmlspecialchars($window['label']) ?>'</span>
                                        <div class="momentum-row-values">
                                            <span class="text-home">H <?= htmlspecialchars((string)$window['home']) ?></span>
                                            <span class="text-away">A <?= htmlspecialchars((string)$window['away']) ?></span>
                                        </div>
                                    </div>
                                    <div class="momentum-row-bars">
                                        <div class="momentum-bar momentum-bar-home" style="width: <?= $homePct ?>%;"></div>
                                        <div class="momentum-bar momentum-bar-away" style="width: <?= $awayPct ?>%;"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
                </div>
            </main>
            
            <!-- Right Column: Match Context -->
            <aside class="col-span-3 min-w-0">
                <div class="rounded-xl bg-slate-900/80 border border-white/10 p-4">
                    <h5 class="text-slate-200 font-semibold mb-1">Match Context</h5>
                    <div class="text-slate-400 text-xs mb-4">Match details and status</div>
                    <div class="space-y-3">
                        <div>
                            <div class="text-[11px] uppercase tracking-wide text-slate-500">Match</div>
                            <div class="text-base font-semibold text-slate-100"><?= htmlspecialchars($homeTeam) ?> vs <?= htmlspecialchars($awayTeam) ?></div>
                            <div class="text-xs text-slate-400"><?= htmlspecialchars($competition) ?></div>
                        </div>
                        
                        <div class="border-t border-white/10"></div>
                        
                        <div>
                            <div class="text-[11px] uppercase tracking-wide text-slate-500">Kickoff</div>
                            <div class="text-sm text-slate-100"><?= htmlspecialchars($matchDateLabel) ?></div>
                            <div class="text-sm text-slate-100"><?= htmlspecialchars($matchTimeLabel) ?></div>
                        </div>
                        
                        <div class="border-t border-white/10"></div>
                        
                        <div>
                            <div class="text-[11px] uppercase tracking-wide text-slate-500">Status</div>
                            <div class="text-sm font-semibold text-slate-100 uppercase" id="match-header-status"><?= htmlspecialchars($matchStatusLabel) ?></div>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>

<style>
.stats-page {
    color: #e5e7eb;
}
.stats-page h1,
.stats-page h2,
.stats-page h3,
.stats-page h4,
.stats-page h5,
.stats-page h6,
.stats-page p,
.stats-page label,
.stats-page span,
.stats-page ul,
.stats-page li,
.stats-page th,
.stats-page td,
.stats-page .panel,
.stats-page .form-control,
.stats-page .form-select,
.stats-page .stats-card,
.stats-page .stats-tabs .btn,
.stats-page .table {
    color: inherit;
}
.stats-page .stats-tab.is-active {
    background-color: rgba(255, 255, 255, 0.12);
    border-color: rgba(255, 255, 255, 0.25);
    color: #fff;
}
.stats-page .text-muted-alt {
    color: rgba(255, 255, 255, 0.65);
}
.stats-page .panel-secondary {
    border-color: rgba(255, 255, 255, 0.08);
}
.match-context {
    border-radius: 16px;
    border: 1px solid rgba(255, 255, 255, 0.08);
}
.stats-bar {
    position: relative;
    height: 8px;
    border-radius: 999px;
    background-color: rgba(255, 255, 255, 0.08);
    margin-bottom: 6px;
}
.stats-bar-fill {
    position: absolute;
    inset: 0;
    border-radius: inherit;
    width: 0;
    transition: width 0.3s ease;
}
.stats-bar-fill-home {
    background-color: rgba(16, 185, 129, 0.8);
}
.stats-bar-fill-away {
    background-color: rgba(248, 113, 113, 0.8);
}
.panel-dark {
    background-color: rgba(15, 23, 42, 0.8);
    border-color: rgba(255, 255, 255, 0.08);
    border-radius: 12px;
}
.match-overview-graphs {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 12px;
    margin-top: 12px;
}
.overview-graph-card {
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding: 14px;
    background: #0d1526;
    border: 1px solid #1d2740;
    border-radius: 14px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.35);
}
.phase2-card-label {
    font-size: 12px;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    margin-bottom: 2px;
}
.phase2-highlight {
    font-size: 12px;
    color: #cbd5e1;
}
.overview-gauge-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
}
.overview-gauge {
    position: relative;
    width: 160px;
    min-height: 80px;
    margin: 0 auto;
}
.overview-gauge svg {
    width: 160px;
    height: 80px;
    display: block;
}
.overview-gauge path {
    fill: none;
    stroke-width: 14;
    stroke-linecap: butt;
}
.overview-gauge-base {
    stroke: rgba(255, 255, 255, 0.15);
}
.overview-gauge-fill {
    opacity: 0.95;
}
.overview-gauge-fill.overview-gauge-away {
    stroke: #f97316;
}
.overview-gauge-fill.overview-gauge-home {
    stroke: #3b82f6;
}
.overview-gauge-center {
    position: absolute;
    top: 36px;
    left: 50%;
    transform: translateX(-50%);
    text-align: center;
}
.overview-gauge-percent {
    font-size: 1.4rem;
    font-weight: 700;
    color: #F4F7FA;
}
.overview-gauge-caption {
    font-size: 10px;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: #94a3b8;
}
.overview-gauge-labels {
    position: absolute;
    top: 6px;
    left: 10px;
    right: 10px;
    display: flex;
    justify-content: space-between;
    font-size: 11px;
    text-transform: uppercase;
}
.overview-gauge-label-home {
    color: #3b82f6;
}
.overview-gauge-label-away {
    color: #f97316;
    text-align: right;
}
.overview-stats {
    width: 100%;
}
.overview-stats-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
}
.overview-stats-labels {
    text-transform: uppercase;
    justify-items: center;
    text-align: center;
    border-bottom: 1px solid rgba(255, 255, 255, 0.12);
    padding-bottom: 2px;
}
.overview-stats-row span {
    text-align: center;
}
.overview-stats-label {
    font-size: 10px;
    letter-spacing: 0.12em;
    color: #8fa4c7;
}
.overview-stats-values {
    font-size: 14px;
    font-weight: 600;
    color: #fdfdfd;
}
.overview-gauge-note {
    font-size: 11px;
    color: #94a3b8;
}

.period-comparison,
.phase-comparison {
    background: #0b172b;
    border: 1px solid #1e2b40;
    border-radius: 10px;
    padding: 12px;
}
.period-section-note,
.phase-section-note {
    font-size: 11px;
    color: #94a3b8;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    margin-top: 6px;
}
.period-table,
.phase-table {
    margin-top: 10px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    overflow: hidden;
}
.period-row,
.phase-row {
    display: grid;
    grid-template-columns: 1fr max-content max-content;
    gap: 10px;
    padding: 10px 12px;
    align-items: center;
}
.period-row:not(.period-row-header),
.phase-row:not(.phase-row-header) {
    border-top: 1px solid rgba(255, 255, 255, 0.04);
}
.period-row-header,
.phase-row-header {
    font-size: 11px;
    letter-spacing: 0.06em;
    color: #94a3b8;
    text-transform: uppercase;
}
.period-label,
.phase-label {
    font-weight: 600;
    color: #e2e8f0;
}
.observation-list {
    margin: 0;
    padding-left: 1.25rem;
    display: flex;
    flex-direction: column;
    gap: 4px;
    font-size: 13px;
    color: #cbd5e1;
}
.distribution-legend {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    font-size: 11px;
    letter-spacing: 0.06em;
    color: #94a3b8;
    margin-bottom: 8px;
}
.distribution-legend-pill {
    text-transform: uppercase;
    border-radius: 999px;
    padding: 4px 10px;
    font-weight: 600;
    font-size: 10px;
}
.distribution-legend-pill-home {
    background: rgba(59, 130, 246, 0.15);
    color: #3b82f6;
}
.distribution-legend-pill-away {
    background: rgba(251, 146, 60, 0.15);
    color: #fb923c;
}
.distribution-legend-pill-unknown {
    background: rgba(148, 163, 184, 0.15);
    color: #94a3b8;
}
.distribution-row {
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding: 14px;
    border: 1px solid #1f2f4a;
    border-radius: 10px;
    background: #0f1c33;
}
.distribution-label {
    font-size: 12px;
    font-weight: 600;
    color: #cbd5e1;
}
.distribution-unknown {
    font-size: 11px;
    color: #94a3b8;
}
.distribution-chart {
    display: flex;
    height: 10px;
    border-radius: 999px;
    overflow: hidden;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.08);
}
.distribution-segment {
    display: block;
    height: 100%;
}
.distribution-segment-home {
    background: linear-gradient(90deg, #3b82f6, #0ea5e9);
}
.distribution-segment-away {
    background: linear-gradient(90deg, #fb923c, #f97316);
}
.distribution-segment-unknown {
    background: linear-gradient(90deg, #94a3b8, #64748b);
}
.distribution-pill-row {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.distribution-pill {
    font-size: 11px;
    font-weight: 600;
    padding: 6px 12px;
    border-radius: 999px;
    color: #f8fafc;
    text-transform: none;
}
.distribution-pill-home {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.25), rgba(14, 165, 233, 0.45));
}
.distribution-pill-away {
    background: linear-gradient(135deg, rgba(251, 146, 60, 0.25), rgba(249, 115, 22, 0.45));
}
.distribution-pill-unknown {
    background: linear-gradient(135deg, rgba(148, 163, 184, 0.25), rgba(100, 116, 139, 0.45));
}
.momentum-panel {
    background: #0b172b;
    border: 1px solid #1e2b40;
    border-radius: 10px;
    padding: 14px;
}
.momentum-panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
}
.momentum-note {
    font-size: 11px;
    color: #94a3b8;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    margin-bottom: 10px;
}
.momentum-chart {
    display: flex;
    flex-direction: column;
    gap: 6px;
    max-height: 250px;
    overflow-y: auto;
}
.momentum-row {
    display: flex;
    flex-direction: column;
    gap: 6px;
    padding: 6px 0;
    border-top: 1px solid rgba(255, 255, 255, 0.04);
}
.momentum-row:first-child {
    border-top: none;
}
.momentum-row-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 13px;
    color: #e2e8f0;
}
.momentum-row-bars {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 6px;
}
.momentum-bar {
    height: 6px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.08);
}
.momentum-bar-home {
    background: linear-gradient(90deg, #38bdf8, #0ea5e9);
}
.momentum-bar-away {
    background: linear-gradient(90deg, #fb923c, #f97316);
}
.momentum-row-values {
    display: flex;
    gap: 12px;
    font-size: 12px;
    color: #cbd5e1;
}
.momentum-row-values .text-home {
    color: #38bdf8;
}
.momentum-row-values .text-away {
    color: #fb923c;
}
.momentum-row-label {
    font-weight: 600;
}
.summary-timeline {
    display: flex;
    flex-direction: column;
    gap: 14px;
}
.stl-period {
    background: #0b172b;
    border: 1px solid #1e2b40;
    border-radius: 10px;
    padding: 12px;
}
.stl-period-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    text-transform: uppercase;
    font-size: 12px;
    letter-spacing: 0.04em;
    color: #cbd5e1;
    margin-bottom: 8px;
}
.stl-period-name {
    font-weight: 700;
}
.stl-period-score {
    background: #0f1c33;
    border: 1px solid #233656;
    border-radius: 999px;
    padding: 6px 12px;
    font-weight: 700;
    color: #e2e8f0;
    min-width: 68px;
    text-align: center;
}
.stl-grid {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.stl-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    align-items: stretch;
}
.stl-divider {
    grid-column: 1 / -1;
    padding: 6px 0;
}
.stl-event {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #0f1f37;
    border: 1px solid #1f2f4a;
    border-radius: 12px;
    padding: 8px 10px;
    color: #e2e8f0;
    text-decoration: none;
}
.stl-minute {
    font-weight: 700;
    color: #94a3b8;
    min-width: 34px;
    text-align: right;
}
.stl-text {
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.stl-card {
    color: #eab308;
    display: inline-flex;
    align-items: center;
    font-size: 12px;
}
.stl-card.red {
    color: #ef4444;
}
.stl-title {
    font-weight: 700;
    color: #e2e8f0;
}
.stl-player {
    font-size: 12px;
    color: #cbd5e1;
}
.stl-away .stl-event {
    flex-direction: row-reverse;
    text-align: right;
}
.stl-away .stl-minute {
    text-align: left;
}
.stl-away .stl-text {
    align-items: flex-end;
}
.stl-fulltime {
    margin-top: 6px;
    background: #0b172b;
    border: 1px solid #233656;
    border-radius: 10px;
    padding: 10px 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-weight: 700;
    color: #e2e8f0;
}
.stl-fulltime small {
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
@media (max-width: 768px) {
    .period-row,
    .phase-row,
    .stl-row {
        grid-template-columns: 1fr;
    }
    .stl-away .stl-event {
        flex-direction: row;
        text-align: left;
    }
    .stl-away .stl-text {
        align-items: flex-start;
    }
}
</style>


<script>

(function () {
    const baseMeta = document.querySelector('meta[name="base-path"]');
    const basePath = baseMeta ? baseMeta.content.trim().replace(/^\/+/,'').replace(/\/+$/,'') : '';
    const apiBase = (basePath ? '/' + basePath : '') + '/api/stats/match';
    const matchId = <?= json_encode($matchId) ?>;
    const selectedClubId = <?= json_encode($clubId) ?>;
    const primaryTeamId = <?= json_encode($primaryTeamId ?? null) ?>;
    const matchHomeTeamId = <?= json_encode($match['home_team_id'] ?? null) ?>;
    const matchAwayTeamId = <?= json_encode($match['away_team_id'] ?? null) ?>;
    const selectedClubName = <?= json_encode($selectedClub['name'] ?? ($selectedClub ?? null)) ?>;
    const tabButtons = document.querySelectorAll('[data-tab-id]');
    const panels = document.querySelectorAll('[data-panel-id]');
    const derivedUrl = `${apiBase}/derived?match_id=${encodeURIComponent(matchId)}`;
    let derivedDataPromise = null;

    function loadDerivedData() {
        if (derivedDataPromise) {
            return derivedDataPromise;
        }
        derivedDataPromise = fetch(derivedUrl, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
            cache: 'no-cache',
        })
            .then((response) => {
                // Debug: show API URL and status
                const debugPre = document.getElementById('derived-stats-debug-pre');
                if (debugPre) {
                    debugPre.textContent = `Request: ${derivedUrl}\nStatus: ${response.status} ${response.statusText}`;
                }
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status + ' ' + response.statusText);
                }
                return response.json();
            })
            .then((payload) => {
                if (payload.success && payload.data) {
                    return payload.data;
                }
                throw new Error(payload.error || 'Invalid payload');
            })
            .catch((error) => {
                console.error('[Match Derived]', error);
                derivedDataPromise = null;
                // Debug: show error
                const debugPre = document.getElementById('derived-stats-debug-pre');
                if (debugPre) {
                    debugPre.textContent += `\nError: ${error}`;
                }
                throw error;
            });

        return derivedDataPromise;
    }

    function showTab(tabId) {
        tabButtons.forEach((button) => {
            const isActive = button.getAttribute('data-tab-id') === tabId;
            button.setAttribute('aria-selected', isActive ? 'true' : 'false');
            
            if (isActive) {
                button.classList.remove('bg-slate-800/40', 'border-white/10', 'text-slate-300', 'hover:bg-slate-700/50', 'hover:border-white/20');
                button.classList.add('bg-indigo-600', 'border-indigo-500', 'text-white', 'shadow-lg', 'shadow-indigo-500/20');
            } else {
                button.classList.remove('bg-indigo-600', 'border-indigo-500', 'text-white', 'shadow-lg', 'shadow-indigo-500/20');
                button.classList.add('bg-slate-800/40', 'border-white/10', 'text-slate-300', 'hover:bg-slate-700/50', 'hover:border-white/20');
            }
        });

        panels.forEach((panel) => {
            const panelId = panel.getAttribute('data-panel-id');
            panel.style.display = panelId === tabId ? '' : 'none';
        });

        const module = tabModules[tabId];
        if (module && typeof module.init === 'function') {
            module.init();
        }
    }

    const eventMetrics = [
        { key: 'goals', label: 'Goals' },
        { key: 'shots', label: 'Shots' },
        { key: 'shot_on_target', label: 'On Target' },
        { key: 'shot_off_target', label: 'Off Target' },
        { key: 'corners', label: 'Corners' },
        { key: 'free_kicks', label: 'Free kicks' },
        { key: 'penalties', label: 'Penalties' },
        { key: 'fouls', label: 'Fouls' },
        { key: 'yellow_cards', label: 'Yellow cards' },
        { key: 'red_cards', label: 'Red cards' },
        { key: 'substitutions', label: 'Substitutions' },
    ];

    const matchOverviewModule = (() => {
        const url = `${apiBase}/overview?match_id=${encodeURIComponent(matchId)}`;
        const loadingEl = document.getElementById('match-overview-loading');
        const errorEl = document.getElementById('match-overview-error');
        const comparisonList = document.getElementById('match-overview-comparison');
        const goalsLabel = document.getElementById('match-overview-goals-label');
        const shotsLabel = document.getElementById('match-overview-shots-label');
        const sidebarScoreEl = document.getElementById('match-header-score');
        const sidebarStatusEl = document.getElementById('match-header-status');
        const summaryHomeScore = document.getElementById('summary-home-score');
        const summaryAwayScore = document.getElementById('summary-away-score');
        const summaryHomeName = document.getElementById('summary-home-name');
        const summaryAwayName = document.getElementById('summary-away-name');
        const summaryCompetition = document.getElementById('summary-competition');
        const eventsSummary = document.getElementById('match-events-summary');
        const gaugeContainer = document.getElementById('match-overview-gauges');
        const accuracyContainer = document.getElementById('match-overview-accuracy');
        const halvesContainer = document.getElementById('match-overview-halves');
        const setPiecesContainer = document.getElementById('match-overview-set-pieces');
        const emptyCounts = { home: 0, away: 0, unknown: 0 };

        let initialized = false;

        function gaugeArcPath(share, offset) {
            if (share <= 0) return null;
            const clampedShare = Math.min(1, Math.max(0, share));
            const startAngle = Math.PI + Math.PI * offset;
            const endAngle = Math.PI + Math.PI * Math.min(1, offset + clampedShare);
            const startX = 60 + 50 * Math.cos(startAngle);
            const startY = 60 + 50 * Math.sin(startAngle);
            const endX = 60 + 50 * Math.cos(endAngle);
            const endY = 60 + 50 * Math.sin(endAngle);
            const largeArcFlag = clampedShare >= 1 - 1e-6 ? 1 : 0;
            return `M ${startX.toFixed(2)} ${startY.toFixed(2)} A 50 50 0 ${largeArcFlag} 1 ${endX.toFixed(2)} ${endY.toFixed(2)}`;
        }

        function buildGauge(home, away) {
            const total = Math.max(0, Number(home) + Number(away));
            if (!total) {
                return {
                    homePercent: 0,
                    awayPercent: 0,
                    total: 0,
                    homePath: null,
                    awayPath: null,
                };
            }
            const homeShare = Number(home) / total;
            const awayShare = Number(away) / total;
            const clampedHome = Math.min(1, Math.max(0, homeShare));
            const clampedAway = Math.min(1, Math.max(0, awayShare));
            return {
                homePercent: Math.round(clampedHome * 100),
                awayPercent: Math.round(clampedAway * 100),
                total,
                homePath: gaugeArcPath(clampedHome, 0),
                awayPath: gaugeArcPath(clampedAway, clampedHome),
            };
        }

        function renderGaugeCard(label, homeVal, awayVal, note = '') {
            const gauge = buildGauge(homeVal, awayVal);
            // Determine if selected club is home or away
            const isHome = primaryTeamId && matchHomeTeamId && Number(primaryTeamId) === Number(matchHomeTeamId);
            const isAway = primaryTeamId && matchAwayTeamId && Number(primaryTeamId) === Number(matchAwayTeamId);
            const clubPercent = isHome ? gauge.homePercent : (isAway ? gauge.awayPercent : 0);
            const oppPercent = isHome ? gauge.awayPercent : (isAway ? gauge.homePercent : 0);
            const clubValue = isHome ? homeVal : (isAway ? awayVal : 0);
            const oppValue = isHome ? awayVal : (isAway ? homeVal : 0);
            const shareLabel = isHome ? 'Home Share' : (isAway ? 'Away Share' : (selectedClubName ? selectedClubName + ' Share' : 'Share'));
            const aria = `${label} ${shareLabel} ${clubPercent}% Opponent ${oppPercent}%`;
            // Always show the selected club's share on the right if away, left if home
            const leftPercent = isHome ? clubPercent : oppPercent;
            const rightPercent = isHome ? oppPercent : clubPercent;
            // For values: left is always home, right is always away
            const leftValue = homeVal;
            const rightValue = awayVal;
            return `
                <div class="overview-graph-card">
                    <div class="phase2-card-label">${label}</div>
                    ${note ? `<div class="phase2-highlight">${note}</div>` : ''}
                    <div class="overview-gauge-wrapper">
                        <div class="overview-gauge" role="img" aria-label="${aria}">
                            <svg viewBox="0 0 120 60" aria-hidden="true">
                                <path class="overview-gauge-base" d="M 10 60 A 50 50 0 0 1 110 60"></path>
                                ${gauge.homePath ? `<path class="overview-gauge-fill overview-gauge-home" d="${gauge.homePath}"></path>` : ''}
                                ${gauge.awayPath ? `<path class="overview-gauge-fill overview-gauge-away" d="${gauge.awayPath}"></path>` : ''}
                            </svg>
                            <div class="overview-gauge-labels">
                                <span class="overview-gauge-label-home">${leftPercent}%</span>
                                <span class="overview-gauge-label-away">${rightPercent}%</span>
                            </div>
                            <div class="overview-gauge-center">
                                <div class="overview-gauge-percent">${clubPercent}%</div>
                                <div class="overview-gauge-caption">${shareLabel}</div>
                            </div>
                        </div>
                        <div class="overview-stats">
                            <div class="overview-stats-row overview-stats-values">
                                <span>${leftValue}</span>
                                <span>${rightValue}</span>
                            </div>
                        </div>
                        ${note ? `<div class="overview-gauge-note">${note}</div>` : ''}
                    </div>
                </div>
            `;
        }

        function renderShotAccuracyCard(label, teamName, onTarget, offTarget, onPct, offPct) {
            const gauge = buildGauge(onTarget, offTarget);
            const aria = `${teamName} on target ${onPct}% off target ${offPct}%`;
            return `
                <div class="overview-graph-card">
                    <div class="phase2-card-label">${label}</div>
                    <div class="overview-gauge-wrapper">
                        <div class="overview-gauge" role="img" aria-label="${aria}">
                            <svg viewBox="0 0 120 60" aria-hidden="true">
                                <path class="overview-gauge-base" d="M 10 60 A 50 50 0 0 1 110 60"></path>
                                ${gauge.homePath ? `<path class="overview-gauge-fill overview-gauge-home" d="${gauge.homePath}"></path>` : ''}
                                ${gauge.awayPath ? `<path class="overview-gauge-fill overview-gauge-away" d="${gauge.awayPath}"></path>` : ''}
                            </svg>
                            <div class="overview-gauge-labels">
                                <span class="overview-gauge-label-home">${onPct}%</span>
                                <span class="overview-gauge-label-away">${offPct}%</span>
                            </div>
                            <div class="overview-gauge-center">
                                <div class="overview-gauge-percent">${onPct}%</div>
                                <div class="overview-gauge-caption">${teamName} on target share</div>
                            </div>
                        </div>
                        <div class="overview-stats">
                            <div class="overview-stats-row overview-stats-labels">
                                <span class="overview-stats-label">On Target</span>
                                <span class="overview-stats-label">Off Target</span>
                            </div>
                            <div class="overview-stats-row overview-stats-values">
                                <span>${onTarget}</span>
                                <span>${offTarget}</span>
                            </div>
                            <div class="overview-stats-row overview-stats-values">
                                <span>${onPct}%</span>
                                <span>${offPct}%</span>
                            </div>
                        </div>
                        <div class="overview-gauge-note">${onTarget + offTarget ? `${onTarget} of ${onTarget + offTarget} shots` : 'No shots recorded'}</div>
                    </div>
                </div>
            `;
        }

        function renderShotAccuracy(payload) {
            if (!accuracyContainer) return;
            const byType = payload?.derived?.by_type_team || {};
            const onTarget = byType['shot_on_target'] || emptyCounts;
            const offTarget = byType['shot_off_target'] || emptyCounts;
            const goals = byType['goal'] || emptyCounts;
            const homeOn = Number(onTarget.home || 0) + Number(goals.home || 0);
            const awayOn = Number(onTarget.away || 0) + Number(goals.away || 0);
            const homeOff = Number(offTarget.home || 0);
            const awayOff = Number(offTarget.away || 0);
            const homeTotal = homeOn + homeOff;
            const awayTotal = awayOn + awayOff;
            const homeOnPct = homeTotal > 0 ? Math.round((homeOn / homeTotal) * 100) : 0;
            const awayOnPct = awayTotal > 0 ? Math.round((awayOn / awayTotal) * 100) : 0;
            const homeOffPct = homeTotal > 0 ? 100 - homeOnPct : 0;
            const awayOffPct = awayTotal > 0 ? 100 - awayOnPct : 0;

            const cards = [
                renderShotAccuracyCard('Home shot accuracy', payload?.match?.home_team || 'Home', homeOn, homeOff, homeOnPct, homeOffPct),
                renderShotAccuracyCard('Away shot accuracy', payload?.match?.away_team || 'Away', awayOn, awayOff, awayOnPct, awayOffPct),
            ].join('');

            accuracyContainer.innerHTML = cards || '<div class="text-xs text-slate-500">No shot accuracy data.</div>';
        }

        function renderHalfComparison(payload) {
            if (!halvesContainer) return;
            const byPeriod = payload?.derived?.phase_2?.by_period || {};
            const firstHalf = byPeriod['1H'] || emptyCounts;
            const secondHalf = byPeriod['2H'] || emptyCounts;

            const cards = [
                renderGaugeCard('First Half', Number(firstHalf.home || 0), Number(firstHalf.away || 0), 'Events tagged to first half'),
                renderGaugeCard('Second Half', Number(secondHalf.home || 0), Number(secondHalf.away || 0), 'Events tagged to second half'),
            ].join('');

            halvesContainer.innerHTML = cards || '<div class="text-xs text-slate-500">No period data available.</div>';
        }

        function renderSetPieces(payload) {
            if (!setPiecesContainer) return;
            const byType = payload?.derived?.by_type_team || {};
            const corners = byType['corner'] || emptyCounts;
            const freeKicks = byType['free_kick'] || emptyCounts;
            const penalties = byType['penalty'] || emptyCounts;
            const yellowCards = byType['yellow_card'] || emptyCounts;
            const redCards = byType['red_card'] || emptyCounts;

            const setPiecesHome = Number(corners.home || 0) + Number(freeKicks.home || 0) + Number(penalties.home || 0);
            const setPiecesAway = Number(corners.away || 0) + Number(freeKicks.away || 0) + Number(penalties.away || 0);
            const cardsHome = Number(yellowCards.home || 0) + Number(redCards.home || 0);
            const cardsAway = Number(yellowCards.away || 0) + Number(redCards.away || 0);

            const cards = [
                renderGaugeCard('Set pieces (corners + free kicks + penalties)', setPiecesHome, setPiecesAway, 'Aggregated from derived stats totals'),
                renderGaugeCard('Cards (yellow + red)', cardsHome, cardsAway, 'Discipline totals from derived stats'),
            ].join('');

            setPiecesContainer.innerHTML = cards || '<div class="text-xs text-slate-500">No set piece or discipline data.</div>';
        }

        function renderGaugeGrid(stats) {
            if (!gaugeContainer) return;
            const home = stats?.home || {};
            const away = stats?.away || {};
            const metrics = [
                {
                    label: 'Goals',
                    home: Number(home.goals || 0),
                    away: Number(away.goals || 0),
                },
                {
                    label: 'Shots',
                    home: Number(home.shots || 0),
                    away: Number(away.shots || 0),
                },
                {
                    label: 'Corners',
                    home: Number(home.corners || 0),
                    away: Number(away.corners || 0),
                },
                {
                    label: 'Set pieces (corners + free kicks + penalties)',
                    home: Number(home.corners || 0) + Number(home.free_kicks || 0) + Number(home.penalties || 0),
                    away: Number(away.corners || 0) + Number(away.free_kicks || 0) + Number(away.penalties || 0),
                    note: 'Aggregated from match overview totals',
                },
                {
                    label: 'Cards (yellow + red)',
                    home: Number(home.yellow_cards || 0) + Number(home.red_cards || 0),
                    away: Number(away.yellow_cards || 0) + Number(away.red_cards || 0),
                    note: 'Discipline totals',
                },
            ];

            const content = metrics
                .map((metric) => renderGaugeCard(metric.label, metric.home, metric.away, metric.note || ''))
                .join('');

            gaugeContainer.innerHTML = content || '<div class="text-xs text-slate-500">No gauge data available.</div>';
        }

        function updateBar(metric, stats) {
            const homeBar = document.querySelector(`[data-bar="${metric}"][data-side="home"]`);
            const awayBar = document.querySelector(`[data-bar="${metric}"][data-side="away"]`);
            const homeValue = stats?.home?.[metric] ?? 0;
            const awayValue = stats?.away?.[metric] ?? 0;
            const maxValue = Math.max(homeValue, awayValue, 1);

            if (homeBar) {
                homeBar.style.width = `${Math.min(100, (homeValue / maxValue) * 100)}%`;
            }
            if (awayBar) {
                awayBar.style.width = `${Math.min(100, (awayValue / maxValue) * 100)}%`;
            }
        }

        function renderComparison(stats) {
            if (!comparisonList) {
                return;
            }

            const rows = eventMetrics.map((metric) => {
                const homeValue = Number(stats?.home?.[metric.key] ?? 0);
                const awayValue = Number(stats?.away?.[metric.key] ?? 0);
                if (homeValue === 0 && awayValue === 0) {
                    return '';
                }
                const maxVal = Math.max(1, homeValue, awayValue);
                const homePct = (homeValue / maxVal) * 100;
                const awayPct = (awayValue / maxVal) * 100;

                return `
                    <div class="comparison-row">
                        <div class="side value-home" aria-label="Home value ${homeValue} for ${metric.label}">
                            <div class="value-number text-home">${homeValue.toLocaleString()}</div>
                            <div class="bar-wrap" title="${homeValue}">
                                <div class="bar bar-home" style="width: ${homePct}%"></div>
                            </div>
                        </div>
                        <div class="metric-label">${metric.label}</div>
                        <div class="side value-away" aria-label="Away value ${awayValue} for ${metric.label}">
                            <div class="bar-wrap" title="${awayValue}">
                                <div class="bar bar-away" style="width: ${awayPct}%"></div>
                            </div>
                            <div class="value-number text-away">${awayValue.toLocaleString()}</div>
                        </div>
                    </div>
                `;
            }).filter(Boolean).join('');

            comparisonList.innerHTML = rows || '<div class="text-xs text-slate-500">No match data available.</div>';
        }

        function renderEvents(data) {
            if (!eventsSummary) return;

            const goalsHome = data?.events?.home_goals ?? [];
            const goalsAway = data?.events?.away_goals ?? [];
            const yellowsHome = data?.events?.home_yellow ?? [];
            const yellowsAway = data?.events?.away_yellow ?? [];
            const redsHome = data?.events?.home_red ?? [];
            const redsAway = data?.events?.away_red ?? [];

            const renderGoal = (goal, alignEnd = false) => `
                <div class="text-sm flex items-center gap-1 ${alignEnd ? 'justify-end' : ''}">
                    <svg fill="currentColor" viewBox="0 0 20 20" style="width: 16px; height: 16px;" data-testid="wcl-icon-incidents-goal-soccer" class="wcl-icon_WGKvC">
                        <title>Goal</title>
                        <path fill-rule="evenodd" class="incidents-goal-soccer" d="M17 2.93a9.96 9.96 0 1 0-14.08 14.1A9.96 9.96 0 0 0 17 2.92Zm.41 2.77a8.5 8.5 0 0 1 1.1 3.43L16.66 8.1l.75-2.4Zm-1.37-1.8.37.4-1.11 3.57-1.33.4-3.32-2.41V4.5l3.16-2.2a8.6 8.6 0 0 1 2.22 1.6ZM9.96 1.4c.78-.01 1.55.1 2.3.3l-2.3 1.6-2.3-1.6c.75-.2 1.52-.31 2.3-.3ZM3.9 3.9a8.6 8.6 0 0 1 2.22-1.6l3.16 2.2v1.36l-3.32 2.4-1.32-.4L3.52 4.3l.37-.4ZM2.52 5.7l.75 2.4-1.85 1.03a8.5 8.5 0 0 1 1.1-3.43Zm1.37 10.35-.22-.23H5.7l.65 1.95a8.6 8.6 0 0 1-2.45-1.72Zm2.01-1.6H2.63A8.5 8.5 0 0 1 1.4 10.7l2.75-1.55 1.41.43 1.28 3.91-.95.95Zm6.05 3.89c-1.3.3-2.66.3-3.97 0l-1.01-3.02 1.1-1.1h3.79l1.1 1.1-1.01 3.02Zm-.07-5.44H8.05L6.86 9.25 9.96 7l3.1 2.25-1.18 3.65Zm4.15 3.15a8.6 8.6 0 0 1-2.45 1.72l.66-1.94h2.01l-.22.22Zm-2-1.6-.95-.95 1.27-3.91 1.41-.43 2.76 1.55a8.5 8.5 0 0 1-1.22 3.74h-3.27Z"></path>
                    </svg>
                    <span>${goal.player ?? 'Unknown'} ${goal.display_minute ? `${goal.display_minute}'` : (goal.match_second ? `${Math.floor(goal.match_second / 60)}'` : '')}</span>
                </div>`;

            const renderCard = (card, color) => `
                <div class="text-sm d-flex align-items-center gap-1 justify-content-center">
                    <svg class="card-ico ${color === 'red' ? 'redCard-ico' : 'yellowCard-ico'}" style="width: 12px; height: 16px;"><title>${color === 'red' ? 'Red Card' : 'Yellow Card'}</title><use xlink:href="/assets/svg/incident.svg#card"></use></svg>
                    <span>${card.player ?? 'Unknown'} ${card.match_second ? `${Math.floor(card.match_second / 60)}'` : ''}</span>
                </div>`;

            const homeGoalsHtml = goalsHome.map((g) => renderGoal(g, false)).join('');
            const awayGoalsHtml = goalsAway.map((g) => renderGoal(g, true)).join('');
            const homeCardsHtml = [...yellowsHome.map((c) => renderCard(c, 'yellow')), ...redsHome.map((c) => renderCard(c, 'red'))].join('');
            const awayCardsHtml = [...yellowsAway.map((c) => renderCard(c, 'yellow')), ...redsAway.map((c) => renderCard(c, 'red'))].join('');

            const goalsSection = (homeGoalsHtml || awayGoalsHtml)
                ? `
                    <div class="event-summary-section mb-3">
                        <div class="events-column-wrapper">
                            <div class="events-column">${homeGoalsHtml}</div>
                            <div class="events-column">${awayGoalsHtml}</div>
                        </div>
                    </div>
                `
                : '';

            const cardsSection = (homeCardsHtml || awayCardsHtml)
                ? `
                    <div class="event-summary-section mt-3">
                        <div class="events-column-wrapper">
                            <div class="events-column">${homeCardsHtml}</div>
                            <div class="events-column">${awayCardsHtml}</div>
                        </div>
                    </div>
                `
                : '';

            eventsSummary.innerHTML = goalsSection || cardsSection
                ? `${goalsSection}${cardsSection}`
                : '<div class="text-xs text-slate-500">No events recorded.</div>';
        }

        function applyStats(data) {
            const stats = data?.stats || {};
            const matchMeta = data?.match;
            
            // Update sidebar score and status
            if (matchMeta) {
                if (sidebarStatusEl) {
                    sidebarStatusEl.textContent = matchMeta.status || 'Scheduled';
                }
            }

            const homeGoals = stats?.home?.goals ?? 0;
            const awayGoals = stats?.away?.goals ?? 0;

            // Update sidebar score
            if (sidebarScoreEl) {
                sidebarScoreEl.textContent = `${homeGoals} - ${awayGoals}`;
            }

            // Update summary score card
            if (summaryHomeScore) summaryHomeScore.textContent = homeGoals;
            if (summaryAwayScore) summaryAwayScore.textContent = awayGoals;
            if (summaryHomeName && matchMeta?.home_team) summaryHomeName.textContent = matchMeta.home_team;
            if (summaryAwayName && matchMeta?.away_team) summaryAwayName.textContent = matchMeta.away_team;
            if (summaryCompetition && matchMeta?.competition) summaryCompetition.textContent = matchMeta.competition;

            if (goalsLabel) goalsLabel.textContent = `${homeGoals} - ${awayGoals}`;
            if (shotsLabel) {
                const homeShots = stats?.home?.shots ?? 0;
                const awayShots = stats?.away?.shots ?? 0;
                shotsLabel.textContent = `${homeShots} - ${awayShots}`;
            }

            updateBar('goals', stats);
            updateBar('shots', stats);

            renderComparison(stats);
            renderEvents(data);
            renderGaugeGrid(stats);

            loadDerivedData()
                .then((derived) => {
                    renderShotAccuracy(derived);
                    renderHalfComparison(derived);
                    renderSetPieces(derived);
                })
                .catch((err) => {
                    console.warn('[Match Overview derived]', err);
                });

            loadingEl.style.display = 'none';
            errorEl.style.display = 'none';
        }

        function fetchStats() {
            fetch(url, {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
                cache: 'no-cache',
            })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then((payload) => {
                    if (payload.success && payload.data) {
                        applyStats(payload.data);
                        return;
                    }
                    throw new Error(payload.error || 'Invalid payload');
                })
                .catch((error) => {
                    console.error('[Match Overview]', error);
                    loadingEl.style.display = 'none';
                    errorEl.style.display = '';
                });
        }

        return {
            init() {
                if (initialized) {
                    return;
                }
                initialized = true;
                fetchStats();
            },
        };
    })();

    const matchTeamPerformanceModule = (() => {
                        function renderPeriodPhase(derived) {
                            if (!periodPhaseContainer) return;
                            const byPeriod = derived?.derived?.phase_2?.by_period || {};
                            const phaseTeamCounts = derived?.derived?.phase_2?.phase_team_counts || {};
                            const periodRows = Object.entries(byPeriod).map(([label, counts]) => ({ label, home: counts.home || 0, away: counts.away || 0 }));
                            const phaseBuckets = Object.keys(phaseTeamCounts.home || {});
                            periodPhaseContainer.innerHTML = `
                                <div class="comparison-extension">
                                    <div class="period-comparison">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="text-light fw-semibold">Events per period</div>
                                        </div>
                                        <div class="period-table">
                                            <div class="period-row period-row-header">
                                                <span>Period</span>
                                                <span>Home</span>
                                                <span>Away</span>
                                            </div>
                                            ${periodRows.map(row => `
                                                <div class="period-row">
                                                    <span class="period-label">${row.label}</span>
                                                    <span>H ${row.home}</span>
                                                    <span>A ${row.away}</span>
                                                </div>
                                            `).join('')}
                                        </div>
                                        <div class="period-section-note">Source: derived_stats.phase_2.by_period</div>
                                    </div>
                                    <div class="phase-comparison">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="text-light fw-semibold">Phase distribution</div>
                                        </div>
                                        <div class="phase-table">
                                            <div class="phase-row phase-row-header">
                                                <span>Phase</span>
                                                <span>Home</span>
                                                <span>Away</span>
                                            </div>
                                            ${phaseBuckets.map(bucket => `
                                                <div class="phase-row">
                                                    <span class="phase-label">${bucket.replace(/_/g, ' ')}</span>
                                                    <span>H ${phaseTeamCounts.home[bucket] || 0}</span>
                                                    <span>A ${phaseTeamCounts.away[bucket] || 0}</span>
                                                </div>
                                            `).join('')}
                                        </div>
                                        <div class="phase-section-note">Phase tags pulled directly from event.phase (unknown shown when missing)</div>
                                    </div>
                                </div>
                            `;
                        }
                function renderStats(stats) {
                    if (!tableBody) return;
                    const metrics = [
                        { key: 'goals', label: 'Goals' },
                        { key: 'shots', label: 'Shots' },
                        { key: 'corners', label: 'Corners' },
                        { key: 'free_kicks', label: 'Free kicks' },
                        { key: 'penalties', label: 'Penalties' },
                        { key: 'fouls', label: 'Fouls' },
                        { key: 'yellow_cards', label: 'Yellow cards' },
                        { key: 'red_cards', label: 'Red cards' },
                        { key: 'substitutions', label: 'Substitutions' },
                    ];
                    tableBody.innerHTML = metrics.map(metric => {
                        const homeVal = Number(stats?.home?.[metric.key] ?? 0);
                        const awayVal = Number(stats?.away?.[metric.key] ?? 0);
                        return `<tr>
                            <td class="px-3 py-2 text-left">${metric.label}</td>
                            <td class="px-3 py-2 text-center">${homeVal}</td>
                            <td class="px-3 py-2 text-center">${awayVal}</td>
                        </tr>`;
                    }).join('');
                }
        const url = `${apiBase}/team-performance?match_id=${encodeURIComponent(matchId)}`;
        const tableBody = document.getElementById('match-team-performance-table');
        const loadingEl = document.getElementById('match-team-performance-loading');
        const errorEl = document.getElementById('match-team-performance-error');
        const periodPhaseContainer = document.getElementById('match-period-phase');
        const zeroCounts = { home: 0, away: 0, unknown: 0 };
        let initialized = false;

        function loadDerivedData() {
            if (derivedDataPromise) {
                return derivedDataPromise;
            }
            derivedDataPromise = fetch(derivedUrl, {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
                cache: 'no-cache',
            })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.status + ' ' + response.statusText);
                    }
                    return response.json();
                })
                .then((payload) => {
                    if (payload.success && payload.data) {
                        return payload.data;
                    }
                    throw new Error(payload.error || 'Invalid payload');
                })
                .catch((error) => {
                    console.error('[Match Derived]', error);
                    derivedDataPromise = null;
                    throw error;
                });

            return derivedDataPromise;
        }

        function fetchStats() {
            fetch(url, {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
                cache: 'no-cache',
            })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then((payload) => {
                    if (payload.success && payload.data) {
                        renderStats(payload.data.stats);
                        loadDerivedData()
                            .then(renderPeriodPhase)
                            .catch((err) => console.warn('[Match Period/Phase]', err));
                        loadingEl.style.display = 'none';
                        errorEl.style.display = 'none';
                        return;
                    }
                    throw new Error(payload.error || 'Invalid payload');
                })
                .catch((error) => {
                    console.error('[Match Team Performance]', error);
                    loadingEl.style.display = 'none';
                    errorEl.style.display = '';
                });
        }

        return {
            init() {
                if (initialized) {
                    return;
                }
                initialized = true;
                fetchStats();
            },
        };
    })();

    const matchPlayerPerformanceModule = (() => {
        const url = `${apiBase}/player-performance?match_id=${encodeURIComponent(matchId)}`;
        const loadingEl = document.getElementById('match-player-performance-loading');
        const errorEl = document.getElementById('match-player-performance-error');
        const emptyEl = document.getElementById('match-player-performance-empty');
        const contentEl = document.getElementById('match-player-performance-content');
        let initialized = false;

        function fetchStats() {
            loadingEl.style.display = 'block';
            errorEl.style.display = 'none';
            emptyEl.style.display = 'none';
            contentEl.style.display = 'none';

            fetch(url, {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
                cache: 'no-cache',
            })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then((payload) => {
                    loadingEl.style.display = 'none';
                    
                    if (payload.success) {
                        const startingXI = payload.starting_xi || [];
                        const substitutes = payload.substitutes || [];
                        
                        if (startingXI.length === 0 && substitutes.length === 0) {
                            emptyEl.style.display = 'block';
                            return;
                        }
                        
                        renderStartingXI(startingXI);
                        renderSubstitutes(substitutes);
                        contentEl.style.display = 'block';
                    } else {
                        throw new Error(payload.error || 'Invalid payload');
                    }
                })
                .catch((error) => {
                    console.error('[Match Player Performance]', error);
                    loadingEl.style.display = 'none';
                    errorEl.style.display = 'block';
                });
        }

        function renderStartingXI(players) {
            const tbody = document.getElementById('match-starting-xi-tbody');
            const table = tbody.closest('table');
            tbody.innerHTML = '';
            // Remove any existing tfoot
            const oldTfoot = table.querySelector('tfoot');
            if (oldTfoot) oldTfoot.remove();

            if (players.length === 0) {
                const row = tbody.insertRow();
                const cell = row.insertCell();
                cell.colSpan = 5;
                cell.className = 'text-center text-muted';
                cell.textContent = 'No starting XI data available';
                return;
            }

            let totalGoals = 0;
            let totalYellow = 0;
            let totalRed = 0;
            players.forEach(player => {
                const row = tbody.insertRow();
                row.innerHTML = `
                    <td class="text-center">${player.shirt_number || '—'}</td>
                    <td>${escapeHtml(player.name)}${player.is_captain ? ' <span class="badge bg-warning text-dark" style="font-size: 0.7rem;">C</span>' : ''}</td>
                    <td>${escapeHtml(player.position)}</td>
                    <td class="text-center">${player.goals > 0 ? '⚽ ' + player.goals : '—'}</td>
                    <td class="text-center">${formatCards(player.yellow_cards, player.red_cards)}</td>
                `;
                totalGoals += player.goals || 0;
                totalYellow += player.yellow_cards || 0;
                totalRed += player.red_cards || 0;
            });
            // Add totals row
            const tfoot = document.createElement('tfoot');
            tfoot.innerHTML = `<tr class="font-semibold bg-slate-800/60">
                <td colspan="3" class="text-right pr-3">Totals</td>
                <td class="text-center">${totalGoals > 0 ? '⚽ ' + totalGoals : '—'}</td>
                <td class="text-center">${formatCards(totalYellow, totalRed)}</td>
            </tr>`;
            table.appendChild(tfoot);
        }

        function renderSubstitutes(players) {
            const section = document.getElementById('match-substitutes-section');
            const tbody = document.getElementById('match-substitutes-tbody');
            const table = tbody.closest('table');
            tbody.innerHTML = '';
            // Remove any existing tfoot
            const oldTfoot = table.querySelector('tfoot');
            if (oldTfoot) oldTfoot.remove();

            if (players.length === 0) {
                section.style.display = 'none';
                return;
            }

            section.style.display = 'block';

            let totalGoals = 0;
            let totalYellow = 0;
            let totalRed = 0;
            players.forEach(player => {
                const row = tbody.insertRow();
                row.innerHTML = `
                    <td class="text-center">${player.shirt_number || '—'}</td>
                    <td>${escapeHtml(player.name)}</td>
                    <td>${escapeHtml(player.position)}</td>
                    <td class="text-center">${player.goals > 0 ? '⚽ ' + player.goals : '—'}</td>
                    <td class="text-center">${formatCards(player.yellow_cards, player.red_cards)}</td>
                `;
                totalGoals += player.goals || 0;
                totalYellow += player.yellow_cards || 0;
                totalRed += player.red_cards || 0;
            });
            // Add totals row
            const tfoot = document.createElement('tfoot');
            tfoot.innerHTML = `<tr class="font-semibold bg-slate-800/60">
                <td colspan="3" class="text-right pr-3">Totals</td>
                <td class="text-center">${totalGoals > 0 ? '⚽ ' + totalGoals : '—'}</td>
                <td class="text-center">${formatCards(totalYellow, totalRed)}</td>
            </tr>`;
            table.appendChild(tfoot);
        }

        function formatCards(yellow, red) {
            const parts = [];
            if (yellow > 0) parts.push(`🟨 ${yellow}`);
            if (red > 0) parts.push(`🟥 ${red}`);
            return parts.length > 0 ? parts.join(' ') : '—';
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        return {
            init() {
                if (initialized) {
                    return;
                }
                initialized = true;
                fetchStats();
            },
        };
    })();

    const matchVisualsModule = (() => {
        const statusEl = document.getElementById('match-visual-analytics-status');
        const distributionEl = document.getElementById('match-visual-distribution');
        const momentumEl = document.getElementById('match-visual-momentum');
        const timelineEl = document.getElementById('match-visual-timeline');
        const homeOriginMarkersEl = document.getElementById('home-origin-markers');
        const homeTargetMarkersEl = document.getElementById('home-target-markers');
        const awayOriginMarkersEl = document.getElementById('away-origin-markers');
        const awayTargetMarkersEl = document.getElementById('away-target-markers');
        const momentumTypeWeights = {
            goal: 5,
            shot: 3,
            big_chance: 3,
            chance: 2,
            corner: 2,
            free_kick: 2,
            penalty: 4,
            foul: 1,
            yellow_card: 1,
            red_card: 2,
            mistake: 1,
            turnover: 1,
            good_play: 2,
            highlight: 1,
            other: 1,
        };
        let initialized = false;

        function safeText(value) {
            const div = document.createElement('div');
            div.textContent = value == null ? '' : String(value);
            return div.innerHTML;
        }

        function formatMinute(seconds) {
            const sec = Number.isFinite(seconds) ? seconds : 0;
            const minute = Math.max(0, Math.floor(sec / 60));
            return `${minute}'`;
        }

        function guessTypeKeyFromLabel(label) {
            if (!label) return '';
            const lower = String(label).toLowerCase();
            if (lower.includes('goal')) return 'goal';
            if (lower.includes('shot')) return 'shot';
            if (lower.includes('chance')) return 'chance';
            if (lower.includes('corner')) return 'corner';
            if (lower.includes('free kick')) return 'free_kick';
            if (lower.includes('penalty')) return 'penalty';
            if (lower.includes('foul')) return 'foul';
            if (lower.includes('yellow')) return 'yellow_card';
            if (lower.includes('red')) return 'red_card';
            if (lower.includes('mistake')) return 'mistake';
            if (lower.includes('turnover')) return 'turnover';
            if (lower.includes('good')) return 'good_play';
            return 'other';
        }

        function renderShotMap(data) {
            const shots = Array.isArray(data?.shots) ? data.shots : [];
            
            // Clear existing markers
            if (homeOriginMarkersEl) homeOriginMarkersEl.innerHTML = '';
            if (homeTargetMarkersEl) homeTargetMarkersEl.innerHTML = '';
            if (awayOriginMarkersEl) awayOriginMarkersEl.innerHTML = '';
            if (awayTargetMarkersEl) awayTargetMarkersEl.innerHTML = '';

            // Ensure marker layers are on top of SVG elements
            [homeOriginMarkersEl, homeTargetMarkersEl, awayOriginMarkersEl, awayTargetMarkersEl].forEach((markersEl) => {
                if (!markersEl || !markersEl.parentNode) return;
                markersEl.parentNode.appendChild(markersEl);
            });

            if (shots.length === 0) {
                return;
            }

            shots.forEach((shot) => {
                const hasOrigin = shot.shot_origin_x !== null && shot.shot_origin_x !== undefined
                    && shot.shot_origin_y !== null && shot.shot_origin_y !== undefined;
                const hasTarget = shot.shot_target_x !== null && shot.shot_target_x !== undefined
                    && shot.shot_target_y !== null && shot.shot_target_y !== undefined;
                const originX = Number(shot.shot_origin_x);
                const originY = Number(shot.shot_origin_y);
                const targetX = Number(shot.shot_target_x);
                const targetY = Number(shot.shot_target_y);
                const teamSide = shot.team_side === 'away' ? 'away' : 'home';

                // Select appropriate marker containers
                const originMarkersEl = teamSide === 'home' ? homeOriginMarkersEl : awayOriginMarkersEl;
                const targetMarkersEl = teamSide === 'home' ? homeTargetMarkersEl : awayTargetMarkersEl;

                // Color based on team
                const color = teamSide === 'home' ? '#3b82f6' : '#ef4444';

                // Render origin marker if coordinates exist (pitch view: 100x100)
                if (hasOrigin && Number.isFinite(originX) && Number.isFinite(originY)) {
                    const svgOriginX = originX * 100;
                    const svgOriginY = originY * 100;
                    
                    const originCircle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                    originCircle.setAttribute('cx', svgOriginX);
                    originCircle.setAttribute('cy', svgOriginY);
                    originCircle.setAttribute('r', '2');
                    originCircle.setAttribute('fill', color);
                    originCircle.setAttribute('opacity', '0.7');
                    
                    const originTitle = document.createElementNS('http://www.w3.org/2000/svg', 'title');
                    originTitle.textContent = `${teamSide === 'home' ? 'Home' : 'Away'} shot origin`;
                    originCircle.appendChild(originTitle);
                    
                    if (originMarkersEl) originMarkersEl.appendChild(originCircle);
                }

                // Render target marker if coordinates exist (goal view: 120x60)
                if (hasTarget && Number.isFinite(targetX) && Number.isFinite(targetY)) {
                    const svgTargetX = targetX * 120;
                    const svgTargetY = targetY * 60;
                    
                    const targetCircle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                    targetCircle.setAttribute('cx', svgTargetX);
                    targetCircle.setAttribute('cy', svgTargetY);
                    targetCircle.setAttribute('r', '3');
                    targetCircle.setAttribute('fill', color);
                    targetCircle.setAttribute('opacity', '0.8');
                    targetCircle.setAttribute('stroke', 'white');
                    targetCircle.setAttribute('stroke-width', '0.5');
                    
                    const targetTitle = document.createElementNS('http://www.w3.org/2000/svg', 'title');
                    targetTitle.textContent = `${teamSide === 'home' ? 'Home' : 'Away'} shot target`;
                    targetCircle.appendChild(targetTitle);
                    
                    if (targetMarkersEl) targetMarkersEl.appendChild(targetCircle);
                }
            });
        }

        function renderDistribution(data) {
            if (!distributionEl) return;
            const buckets = Array.isArray(data?.derived?.phase_2?.per_15_minute) ? data.derived.phase_2.per_15_minute : [];
            if (buckets.length === 0) {
                distributionEl.innerHTML = '<div class="text-muted-alt text-sm">No distribution data yet.</div>';
                return;
            }

            const rows = buckets
                .map((bucket) => {
                    const label = bucket?.label || 'Bucket';
                    const home = Number(bucket?.home || 0);
                    const away = Number(bucket?.away || 0);
                    const unknown = Number(bucket?.unknown || 0);
                    const total = home + away + unknown;
                    const homePct = total > 0 ? (home / total) * 100 : 0;
                    const awayPct = total > 0 ? (away / total) * 100 : 0;
                    const unknownPct = total > 0 ? (unknown / total) * 100 : 0;
                    const chartLabel = `${label} bucket: Home ${homePct.toFixed(1)}% · Away ${awayPct.toFixed(1)}% · Unknown ${unknownPct.toFixed(1)}%`;
                    const segments = `
                        ${homePct > 0 ? `<span class="distribution-segment distribution-segment-home" style="width: ${homePct}%;"></span>` : ''}
                        ${awayPct > 0 ? `<span class="distribution-segment distribution-segment-away" style="width: ${awayPct}%;"></span>` : ''}
                        ${unknownPct > 0 ? `<span class="distribution-segment distribution-segment-unknown" style="width: ${unknownPct}%;"></span>` : ''}
                    `;
                    return `
                        <div class="distribution-row">
                            <div class="distribution-info">
                                <div class="distribution-label">${safeText(label)}</div>
                                <div class="distribution-unknown">${total} events · ${unknown} unassigned</div>
                                <div class="distribution-chart" role="img" aria-label="${safeText(chartLabel)}">
                                    ${segments}
                                </div>
                            </div>
                            <div class="distribution-statistics">
                                <div class="distribution-chart-meta">
                                    <span class="distribution-pill distribution-pill-home">H ${homePct.toFixed(1)}%</span>
                                    <span class="distribution-pill distribution-pill-away">A ${awayPct.toFixed(1)}%</span>
                                    <span class="distribution-pill distribution-pill-unknown">U ${unknownPct.toFixed(1)}%</span>
                                </div>
                                <div class="distribution-counts">
                                    <span class="distribution-count distribution-count-home">H ${home}</span>
                                    <span class="distribution-count distribution-count-away">A ${away}</span>
                                    <span class="distribution-count distribution-count-unknown">U ${unknown}</span>
                                </div>
                            </div>
                        </div>
                    `;
                })
                .join('');

            distributionEl.innerHTML = `
                <div class="distribution-legend">
                    <span class="distribution-legend-pill distribution-legend-pill-home">H = Home</span>
                    <span class="distribution-legend-pill distribution-legend-pill-away">A = Away</span>
                    <span class="distribution-legend-pill distribution-legend-pill-unknown">U = Unassigned</span>
                </div>
                <div class="distribution-table">
                    ${rows}
                </div>
            `;
        }

        function renderMomentum(data) {
            if (!momentumEl) return;
            const events = Array.isArray(data?.events) ? data.events : [];
            if (events.length === 0) {
                momentumEl.innerHTML = '<div class="text-muted-alt text-sm">No momentum data yet.</div>';
                return;
            }

            const windowSize = 300;
            let maxSecond = 0;
            events.forEach((ev) => {
                const sec = Number(ev?.match_second || 0);
                if (sec > maxSecond) maxSecond = sec;
            });
            const windowCount = Math.floor(maxSecond / windowSize) + 1;
            const windows = Array.from({ length: windowCount }, (_, idx) => ({
                start: idx * windowSize,
                end: (idx + 1) * windowSize,
                home: 0,
                away: 0,
            }));

            events.forEach((ev) => {
                const sec = Number(ev?.match_second || 0);
                const idx = Math.floor(sec / windowSize);
                const window = windows[idx];
                if (!window) return;
                const typeKey = (ev?.event_type_key || '').toLowerCase() || guessTypeKeyFromLabel(ev?.event_type_label || ev?.label || ev?.type || '');
                const weight = momentumTypeWeights[typeKey] ?? 1;
                const side = ev?.team_side === 'away' ? 'away' : 'home';
                window[side] += weight;
            });

            let momentumMaxValue = 0;
            windows.forEach((w) => {
                momentumMaxValue = Math.max(momentumMaxValue, w.home, w.away);
            });
            const rows = windows
                .map((w) => {
                    const homePct = momentumMaxValue > 0 ? Math.min(100, Math.max(0, (w.home / momentumMaxValue) * 100)) : 0;
                    const awayPct = momentumMaxValue > 0 ? Math.min(100, Math.max(0, (w.away / momentumMaxValue) * 100)) : 0;
                    const label = `${Math.floor(w.start / 60)}-${Math.floor(w.end / 60)}`;
                    return `
                        <div class="momentum-row">
                            <div class="momentum-row-header">
                                <span class="momentum-row-label">${label}'</span>
                                <div class="momentum-row-values">
                                    <span class="text-home">H ${w.home}</span>
                                    <span class="text-away">A ${w.away}</span>
                                </div>
                            </div>
                            <div class="momentum-row-bars">
                                <div class="momentum-bar momentum-bar-home" style="width: ${homePct}%;"></div>
                                <div class="momentum-bar momentum-bar-away" style="width: ${awayPct}%;"></div>
                            </div>
                        </div>
                    `;
                })
                .join('');

            momentumEl.innerHTML = `
                <div class="momentum-note">Home vs Away momentum per 5-minute bucket (derived events weighted by type).</div>
                <div class="momentum-chart">${rows}</div>
            `;
        }

        function isGoalEvent(ev) {
            const key = (ev?.event_type_key || '').toLowerCase();
            const label = (ev?.event_type_label || ev?.label || '').toLowerCase();
            return key.includes('goal') || label.includes('goal');
        }

        function renderTimeline(data) {
            if (!timelineEl) return;
            const events = Array.isArray(data?.events) ? data.events : [];
            if (events.length === 0) {
                timelineEl.innerHTML = '<div class="text-muted-alt text-sm">No events yet.</div>';
                return;
            }

            const sorted = events.slice().sort((a, b) => (Number(a?.match_second || 0) - Number(b?.match_second || 0)));
            const runningScore = { home: 0, away: 0 };
            const periodScores = {};
            sorted.forEach((ev) => {
                if (isGoalEvent(ev)) {
                    const side = ev?.team_side === 'away' ? 'away' : 'home';
                    runningScore[side] += 1;
                }
                const periodLabel = ev?.period_label || ev?.period || 'Match';
                periodScores[periodLabel] = { ...runningScore };
            });

            const grouped = sorted.reduce((acc, ev) => {
                const label = ev?.period_label || ev?.period || 'Match';
                acc[label] = acc[label] || [];
                acc[label].push(ev);
                return acc;
            }, {});

            const periodBlocks = Object.entries(grouped)
                .map(([periodLabel, list]) => {
                    const periodScore = periodScores[periodLabel];
                    const scoreText = periodScore ? `${periodScore.home} - ${periodScore.away}` : '';
                    const rows = list
                        .map((ev) => {
                            const side = ev?.team_side === 'away' ? 'away' : ev?.team_side === 'home' ? 'home' : 'home';
                            const minuteVal = Math.floor(Number(ev?.match_second || 0) / 60);
                            const minuteLabel = `${minuteVal}'`;
                            const eventLabel = ev?.event_type_label || ev?.label || ev?.event_type_key || 'Event';
                            const playerName = ev?.match_player_name || '';
                            const keyLower = (ev?.event_type_key || '').toLowerCase();
                            const labelLower = (ev?.event_type_label || '').toLowerCase();
                            const isCard = keyLower.includes('card') || labelLower.includes('yellow') || labelLower.includes('red');
                            const cardClass = labelLower.includes('red') || keyLower.includes('red') ? ' red' : '';
                            const isPeriodMarker = keyLower === 'period_start' || keyLower === 'period_end';
                            if (isPeriodMarker) {
                                return `
                                    <div class="stl-divider">
                                        <div class="stl-separator">${minuteLabel} · ${safeText(eventLabel)} ${scoreText}</div>
                                    </div>
                                `;
                            }
                            const goalIcon = isGoalEvent(ev) ? ' <i class="fa-solid fa-futbol"></i>' : '';
                            return `
                                <div class="stl-row">
                                    <div class="stl-cell stl-home">
                                        ${side === 'home'
                                            ? `<div class="stl-event">
                                                    <span class="stl-minute">${minuteLabel}</span>
                                                    ${isCard ? `<span class="stl-card${cardClass}"><i class="fa-solid fa-square"></i></span>` : ''}
                                                    <div class="stl-text">
                                                        <div class="stl-title">${safeText(eventLabel)}${goalIcon}</div>
                                                        ${playerName ? `<div class="stl-player">${safeText(playerName)}</div>` : ''}
                                                    </div>
                                                </div>`
                                            : ''}
                                    </div>
                                    <div class="stl-cell stl-away">
                                        ${side === 'away'
                                            ? `<div class="stl-event">
                                                    <span class="stl-minute">${minuteLabel}</span>
                                                    ${isCard ? `<span class="stl-card${cardClass}"><i class="fa-solid fa-square"></i></span>` : ''}
                                                    <div class="stl-text">
                                                        <div class="stl-title">${safeText(eventLabel)}${goalIcon}</div>
                                                        ${playerName ? `<div class="stl-player">${safeText(playerName)}</div>` : ''}
                                                    </div>
                                                </div>`
                                            : ''}
                                    </div>
                                </div>
                            `;
                        })
                        .join('');
                    return `
                        <div class="stl-period">
                            <div class="stl-period-header">
                                <div class="stl-period-name">${safeText(periodLabel)}</div>
                                <div class="stl-period-score">${safeText(scoreText)}</div>
                            </div>
                            <div class="stl-grid">${rows}</div>
                        </div>
                    `;
                })
                .join('');

            timelineEl.innerHTML = `
                <div class="summary-timeline">
                    ${periodBlocks}
                    <div class="stl-fulltime">
                        <small>Full time</small>
                        <span>${runningScore.home} - ${runningScore.away}</span>
                    </div>
                </div>
            `;
        }

        function renderVisuals(payload) {
            renderDistribution(payload);
            renderMomentum(payload);
            renderTimeline(payload);
            renderShotMap(payload);
            if (statusEl) {
                statusEl.textContent = 'Visual analytics populated from derived data.';
            }
        }

        function handleError(error) {
            console.error('[Match Visuals]', error);
            if (statusEl) {
                statusEl.textContent = 'Unable to load visual analytics.';
            }
        }

        return {
            init() {
                if (initialized) {
                    return;
                }
                initialized = true;
                // Load derived data and shots data in parallel
                Promise.all([
                    loadDerivedData(),
                    fetch(`${apiBase}/shots?match_id=${encodeURIComponent(matchId)}`).then(r => r.json())
                ])
                    .then(([derived, shotsData]) => {
                        const combined = {
                            ...derived,
                            shots: Array.isArray(shotsData?.data?.shots) ? shotsData.data.shots : []
                        };
                        renderVisuals(combined);
                    })
                    .catch(handleError);
            },
        };
    })();

    const tabModules = {
        'match-overview': matchOverviewModule,
        'match-team-performance': matchTeamPerformanceModule,
        'match-player-performance': matchPlayerPerformanceModule,
        'match-visual-analytics': matchVisualsModule,
    };

    tabButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const tabId = button.getAttribute('data-tab-id');
            showTab(tabId);
            // Save the active tab to localStorage
            localStorage.setItem('matchStatsActiveTab', tabId);
        });
    });

    // Restore the active tab from localStorage, or default to 'match-overview'
    const savedTab = localStorage.getItem('matchStatsActiveTab') || 'match-overview';
    showTab(savedTab);
})();
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../layout.php';
?>

