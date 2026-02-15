<?php
$clubName = $selectedClub['name'] ?? 'Club';
$today = date('j M Y');
$overview = $overviewStats ?? [];
$teamPerformance = $teamPerformance ?? [];
$playerPerformance = $playerPerformance ?? [];
$filterSummary = $filterSummary ?? [];
$positionFilter = $positionFilter ?? null;

$overviewMetrics = [
    [
        'key' => 'total_matches',
        'label' => 'Matches',
        'description' => 'Matches played',
        'format' => 'integer',
    ],
    [
        'key' => 'wins',
        'label' => 'Wins',
        'description' => 'Matches won',
        'format' => 'integer',
    ],
    [
        'key' => 'draws',
        'label' => 'Draws',
        'description' => 'Matches drawn',
        'format' => 'integer',
    ],
    [
        'key' => 'losses',
        'label' => 'Losses',
        'description' => 'Matches lost',
        'format' => 'integer',
    ],
    [
        'key' => 'goals_for',
        'label' => 'Goals For',
        'description' => 'Goals scored',
        'format' => 'integer',
    ],
    [
        'key' => 'goals_against',
        'label' => 'Goals Against',
        'description' => 'Goals conceded',
        'format' => 'integer',
    ],
    [
        'key' => 'goal_difference',
        'label' => 'Goal Difference',
        'description' => 'GF − GA',
        'format' => 'integer',
        'direction' => 'signed',
    ],
    [
        'key' => 'clean_sheets',
        'label' => 'Clean Sheets',
        'description' => 'Shutouts',
        'format' => 'integer',
    ],
    [
        'key' => 'average_goals_per_game',
        'label' => 'Avg. goals/game',
        'description' => 'Goals per match',
        'format' => 'decimal',
    ],
];

$statDefinitions = [];
foreach ($overviewMetrics as $metric) {
    $statDefinitions[$metric['key']] = [
        'format' => $metric['format'] ?? 'integer',
        'direction' => $metric['direction'] ?? 'positive',
    ];
}

$formatNumber = static function ($value, int $decimals = 0): string {
    if ($value === null || $value === '') {
        return '—';
    }
    if (!is_numeric($value)) {
        return (string)$value;
    }
    return number_format((float)$value, $decimals);
};

$formatStat = static function (string $key, $value) use ($statDefinitions, $formatNumber): string {
    if ($value === null || $value === '') {
        return '—';
    }
    $definition = $statDefinitions[$key] ?? ['format' => 'integer', 'direction' => 'positive'];
    $format = $definition['format'] ?? 'integer';
    $direction = $definition['direction'] ?? 'positive';

    if (!is_numeric($value)) {
        return (string)$value;
    }
    $numeric = (float)$value;

    if ($format === 'decimal') {
        $formatted = $formatNumber($numeric, 2);
    } else {
        $formatted = $formatNumber($numeric, 0);
    }

    if ($direction === 'signed' && $numeric > 0) {
        return '+' . $formatted;
    }

    return $formatted;
};

$formatDate = static function (?string $dateString): string {
    if (!$dateString) {
        return 'TBD';
    }
    try {
        $dt = new DateTime($dateString);
        return $dt->format('j M Y');
    } catch (Exception $e) {
        return 'TBD';
    }
};

$formatTime = static function (?string $dateString): string {
    if (!$dateString) {
        return 'TBD';
    }
    try {
        $dt = new DateTime($dateString);
        return $dt->format('H:i');
    } catch (Exception $e) {
        return 'TBD';
    }
};

$players = is_array($playerPerformance) ? $playerPerformance : [];
if ($positionFilter !== null) {
    $players = array_values(array_filter($players, static function ($player) use ($positionFilter) {
        return trim((string)($player['position'] ?? '')) === $positionFilter;
    }));
}
$activePlayers = array_values(array_filter($players, static function ($player) {
    return !empty($player['is_active']);
}));
$inactivePlayers = array_values(array_filter($players, static function ($player) {
    return empty($player['is_active']);
}));

$topScorer = [];
$mostUsed = [];
$minuteLeader = [];
if (!empty($players)) {
    $topScorer = $players[0];
    foreach ($players as $player) {
        if (($player['goals'] ?? 0) > ($topScorer['goals'] ?? 0)) {
            $topScorer = $player;
        }
    }

    $mostUsed = $players[0];
    foreach ($players as $player) {
        if (($player['appearances'] ?? 0) > ($mostUsed['appearances'] ?? 0)) {
            $mostUsed = $player;
        }
    }

    $minuteLeader = $players[0];
    foreach ($players as $player) {
        if (($player['minutes_played'] ?? 0) > ($minuteLeader['minutes_played'] ?? 0)) {
            $minuteLeader = $player;
        }
    }
}

$positions = [];
foreach ($activePlayers as $player) {
    $pos = trim((string)($player['position'] ?? 'N/A'));
    if ($pos === '') {
        $pos = 'N/A';
    }
    if (!isset($positions[$pos])) {
        $positions[$pos] = 0;
    }
    $positions[$pos] += 1;
}
$positionOrder = ['CB', 'CM', 'GK', 'LB', 'RB', 'RM', 'ST'];
$positionCounts = [];
foreach ($positionOrder as $pos) {
    $positionCounts[$pos] = (int)($positions[$pos] ?? 0);
}

$totalGoalsActive = 0;
$totalYellowActive = 0;
$totalRedActive = 0;
$totalMinutesActive = 0;
foreach ($activePlayers as $player) {
    $totalGoalsActive += (int)($player['goals'] ?? 0);
    $totalYellowActive += (int)($player['yellow_cards'] ?? 0);
    $totalRedActive += (int)($player['red_cards'] ?? 0);
    $totalMinutesActive += (int)($player['minutes_played'] ?? 0);
}

$totalGoalsInactive = 0;
$totalYellowInactive = 0;
$totalRedInactive = 0;
foreach ($inactivePlayers as $player) {
    $totalGoalsInactive += (int)($player['goals'] ?? 0);
    $totalYellowInactive += (int)($player['yellow_cards'] ?? 0);
    $totalRedInactive += (int)($player['red_cards'] ?? 0);
}

$avgGoalsPerPlayer = $activePlayers ? ($totalGoalsActive / count($activePlayers)) : 0;
$avgMinutesPerPlayer = $activePlayers ? ($totalMinutesActive / count($activePlayers)) : 0;

$mostCarded = null;
$mostCardedTotal = 0;
foreach ($players as $player) {
    $cards = (int)($player['yellow_cards'] ?? 0) + (int)($player['red_cards'] ?? 0);
    if ($cards > $mostCardedTotal) {
        $mostCardedTotal = $cards;
        $mostCarded = $player;
    }
}
$mostCardedLabel = '—';
if ($mostCarded && $mostCardedTotal > 0) {
    $mostCardedLabel = trim((string)($mostCarded['name'] ?? '')) . ' (' . $mostCardedTotal . ' cards)';
}

$teamHomeAway = $teamPerformance['home_away'] ?? ['home' => [], 'away' => []];
$teamLeagueCup = $teamPerformance['league_cup'] ?? ['league' => [], 'cup' => []];
$teamForm = $teamPerformance['form'] ?? [];
$cleanSheetMatches = $teamPerformance['clean_sheets_matches'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Stats Dashboard Report - <?= htmlspecialchars($clubName) ?></title>
    <?php require __DIR__ . '/../../partials/pdf_styles.php'; ?>
</head>
<body>

<div class="section">
    <div class="page-title">Statistics Dashboard Report</div>
    <div class="page-subtitle">Club: <strong><?= htmlspecialchars($clubName) ?></strong> &nbsp; | &nbsp; Date: <?= htmlspecialchars($today) ?><?php if (!empty($filterSummary)): ?> &nbsp; | &nbsp; Filters: <?= htmlspecialchars(implode(' · ', $filterSummary)) ?><?php endif; ?></div>
    <div class="divider"></div>

    <div class="section-title">Overview</div>
    <div class="section-subtitle">Matches and club-wide summary metrics.</div>
        <div class="card">
            <div class="card-title">Summary</div>
            <table class="stat-grid">
                <tr>
                    <td>
                        <div class="stat-label">Matches</div>
                        <div class="stat-value gray"><?= htmlspecialchars($overview['total_matches'] ?? '—') ?></div>
                    </td>
                    <td>
                        <div class="stat-label">Clean Sheets</div>
                        <div class="stat-value gray"><?= htmlspecialchars($overview['clean_sheets'] ?? '—') ?></div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="stat-label">Avg. Goals/Game</div>
                        <div class="stat-value gray"><?= htmlspecialchars($overview['average_goals_per_game'] ?? '—') ?></div>
                    </td>
                    <td>
                        <div class="stat-label">Match Results (W-D-L)</div>
                        <div class="stat-value gray"><?= htmlspecialchars(($overview['wins'] ?? '—') . ' - ' . ($overview['draws'] ?? '—') . ' - ' . ($overview['losses'] ?? '—')) ?></div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="stat-label">Goals (For / Against)</div>
                        <div class="stat-value gray"><?= htmlspecialchars(($overview['goals_for'] ?? '—') . ' / ' . ($overview['goals_against'] ?? '—')) ?></div>
                    </td>
                    <td>
                        <div class="stat-label">Goal Difference</div>
                        <div class="stat-value gray"><?= htmlspecialchars($overview['goal_difference'] ?? '—') ?></div>
                    </td>
                </tr>
            </table>
        </div>

    <div style="height:6px;"></div>
</div>

<div class="section">
    <div class="section-title">Team Performance</div>
    <div class="section-subtitle">Home vs away record, clean sheets, recent form, and league/cup breakdown.</div>


    <div style="height:6px;"></div>

    <h3 style="margin-bottom:8px;">Home vs Away Record</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Venue</th>
                            <th>MP</th>
                            <th>W</th>
                            <th>D</th>
                            <th>L</th>
                            <th>GF</th>
                            <th>GA</th>
                            <th>GD</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (['home' => 'Home', 'away' => 'Away'] as $venueKey => $venueLabel): ?>
                            <?php $row = $teamHomeAway[$venueKey] ?? []; ?>
                            <tr>
                                <td><?= htmlspecialchars($venueLabel) ?></td>
                                <td style="text-align:center;"><?= htmlspecialchars($formatNumber($row['matches'] ?? 0)) ?></td>
                                <td style="text-align:center;"><?= htmlspecialchars($formatNumber($row['wins'] ?? 0)) ?></td>
                                <td style="text-align:center;"><?= htmlspecialchars($formatNumber($row['draws'] ?? 0)) ?></td>
                                <td style="text-align:center;"><?= htmlspecialchars($formatNumber($row['losses'] ?? 0)) ?></td>
                                <td style="text-align:center;"><?= htmlspecialchars($formatNumber($row['goals_for'] ?? 0)) ?></td>
                                <td style="text-align:center;"><?= htmlspecialchars($formatNumber($row['goals_against'] ?? 0)) ?></td>
                                <td style="text-align:center;"><?= htmlspecialchars($formatNumber($row['goal_difference'] ?? (($row['goals_for'] ?? 0) - ($row['goals_against'] ?? 0)))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <h3 style="margin:14px 0 8px 0;">Clean Sheets</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Venue</th>
                            <th>Opponent</th>
                            <th>GK</th>
                            <th>Score</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($cleanSheetMatches)): ?>
                            <?php foreach ($cleanSheetMatches as $item): ?>
                                <?php
                                    $dateLabel = $formatDate($item['date'] ?? null);
                                    $venueLabel = $item['venue'] ?? 'Home';
                                    $opp = $item['opponent'] ?? 'Opponent';
                                    $gk = $item['gk'] ?? '—';
                                    $score = $item['score'] ?? '—';
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($dateLabel) ?></td>
                                    <td><?= htmlspecialchars($venueLabel) ?></td>
                                    <td><?= htmlspecialchars($opp) ?></td>
                                    <td><?= htmlspecialchars($gk) ?></td>
                                    <td style="text-align:center; font-weight:700;"><?= htmlspecialchars($score) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align:center;">No clean sheets yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <h3 style="margin:14px 0 8px 0;">Recent Form</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Result</th>
                            <th>Score</th>
                            <th>Opponent</th>
                            <th>Venue</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($teamForm)): ?>
                            <?php foreach ($teamForm as $entry): ?>
                                <?php
                                    $result = $entry['result'] ?? '—';
                                    $score = $entry['score'] ?? '—';
                                    $opponent = $entry['opponent'] ?? 'Opponent';
                                    $venue = $entry['venue'] ?? 'Home';
                                    $dateLabel = $formatDate($entry['date'] ?? null);
                                ?>
                                <tr>
                                    <td style="text-align:center;"><span class="pill <?= htmlspecialchars($result) ?>"><?= htmlspecialchars($result) ?></span></td>
                                    <td style="text-align:center; font-weight:700;"><?= htmlspecialchars($score) ?></td>
                                    <td><?= htmlspecialchars($opponent) ?></td>
                                    <td><?= htmlspecialchars($venue) ?></td>
                                    <td><?= htmlspecialchars($dateLabel) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align:center;">No recent form data available.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <div style="page-break-before: always;"></div>
                <h3 style="margin:14px 0 8px 0;">League &amp; Cup Summary</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Competition</th>
                            <th>Matches</th>
                            <th>Wins</th>
                            <th>Draws</th>
                            <th>Losses</th>
                            <th>GF</th>
                            <th>GA</th>
                            <th>GD</th>
                            <th>Clean Sheets</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (['league' => 'League', 'cup' => 'Cup'] as $compKey => $compLabel): ?>
                            <?php $row = $teamLeagueCup[$compKey] ?? []; ?>
                            <tr>
                                <td><?= htmlspecialchars($compLabel) ?></td>
                                <td style="text-align:center;"><?= htmlspecialchars($formatNumber($row['matches'] ?? 0)) ?></td>
                                <td style="text-align:center;"><?= htmlspecialchars($formatNumber($row['wins'] ?? 0)) ?></td>
                                <td style="text-align:center;"><?= htmlspecialchars($formatNumber($row['draws'] ?? 0)) ?></td>
                                <td style="text-align:center;"><?= htmlspecialchars($formatNumber($row['losses'] ?? 0)) ?></td>
                                <td style="text-align:center;"><?= htmlspecialchars($formatNumber($row['goals_for'] ?? 0)) ?></td>
                                <td style="text-align:center;"><?= htmlspecialchars($formatNumber($row['goals_against'] ?? 0)) ?></td>
                                <td style="text-align:center;"><?= htmlspecialchars($formatNumber($row['goal_difference'] ?? (($row['goals_for'] ?? 0) - ($row['goals_against'] ?? 0)))) ?></td>
                                <td style="text-align:center;"><?= htmlspecialchars($formatNumber($row['clean_sheets'] ?? 0)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
    <div style="height:6px;"></div>
</div>

<div class="section">
    <div class="section-title">Player Performance</div>
    <div class="section-subtitle">Individual player contributions, top performers, and squad summary.</div>
    <div class="card">
        <div class="card-title">Top Performers</div>
        <table class="stat-grid">
            <tr>
                <td>
                    <div class="stat-label">Top Goalscorer</div>
                    <div style="font-weight:700; font-size:12px;"><?= htmlspecialchars($topScorer['name'] ?? '—') ?></div>
                    <div class="stat-value green" style="font-size:16px;"><?= htmlspecialchars($formatNumber($topScorer['goals'] ?? 0)) ?></div>
                </td>
                <td>
                    <div class="stat-label">Most Minutes</div>
                    <div style="font-weight:700; font-size:12px;"><?= htmlspecialchars($minuteLeader['name'] ?? '—') ?></div>
                    <div class="stat-value cyan" style="font-size:16px;"><?= htmlspecialchars($formatNumber($minuteLeader['minutes_played'] ?? 0)) ?></div>
                </td>
                <td>
                    <div class="stat-label">Most Used</div>
                    <div style="font-weight:700; font-size:12px;"><?= htmlspecialchars($mostUsed['name'] ?? '—') ?></div>
                    <div class="stat-value blue" style="font-size:16px;"><?= htmlspecialchars($formatNumber($mostUsed['appearances'] ?? 0)) ?></div>
                </td>
            </tr>
        </table>
    </div>

    <table class="layout-table">
        <tr>
            <td style="padding-right:8px;">
                <div class="card">
                    <div class="card-title">Squad Summary (Active)</div>
                    <div class="stat-value gray" style="font-size:18px;"><?= htmlspecialchars($formatNumber(count($activePlayers))) ?></div>
                    <div class="small" style="margin-top:6px;">Squad Size</div>
                    <table class="data-table" style="margin-top:6px; margin-bottom:0;">
                        <thead>
                            <tr>
                                <?php foreach ($positionOrder as $pos): ?>
                                    <th style="text-align:center;"><?= htmlspecialchars($pos) ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <?php foreach ($positionOrder as $pos): ?>
                                    <td style="text-align:center; font-weight:700;"><?= htmlspecialchars($formatNumber($positionCounts[$pos] ?? 0)) ?></td>
                                <?php endforeach; ?>
                            </tr>
                        </tbody>
                    </table>
                    <div class="divider" style="margin:8px 0;"></div>
                    <div class="small">Goals Scored</div>
                    <div class="small"><?= htmlspecialchars(number_format($avgGoalsPerPlayer, 2)) ?> per player</div>
                    <div class="stat-value green" style="font-size:18px;"><?= htmlspecialchars($formatNumber($totalGoalsActive)) ?></div>
                    <div class="divider" style="margin:8px 0;"></div>
                    <div class="small">Playing Time</div>
                    <div class="small"><?= htmlspecialchars($formatNumber(round($avgMinutesPerPlayer))) ?> mins avg per player</div>
                    <div class="stat-value cyan" style="font-size:18px;"><?= htmlspecialchars($formatNumber($totalMinutesActive)) ?></div>
                    <div class="divider" style="margin:8px 0;"></div>
                    <div class="small">Inactive Squad</div>
                    <div class="small"><?= htmlspecialchars($formatNumber(count($inactivePlayers))) ?> players · <?= htmlspecialchars($formatNumber($totalGoalsInactive)) ?> goals</div>
                </div>
            </td>
            <td style="padding-left:8px;">
                <div class="card">
                    <div class="card-title">Discipline Overview</div>
                    <div class="small">Total Yellow Cards</div>
                    <div class="small">Active: <?= htmlspecialchars($formatNumber($totalYellowActive)) ?> | Inactive: <?= htmlspecialchars($formatNumber($totalYellowInactive)) ?></div>
                    <div class="stat-value amber" style="font-size:18px;"><?= htmlspecialchars($formatNumber($totalYellowActive + $totalYellowInactive)) ?></div>
                    <div class="divider" style="margin:8px 0;"></div>
                    <div class="small">Total Red Cards</div>
                    <div class="small">Active: <?= htmlspecialchars($formatNumber($totalRedActive)) ?> | Inactive: <?= htmlspecialchars($formatNumber($totalRedInactive)) ?></div>
                    <div class="stat-value red" style="font-size:18px;"><?= htmlspecialchars($formatNumber($totalRedActive + $totalRedInactive)) ?></div>
                    <div class="divider" style="margin:8px 0;"></div>
                    <div class="small">Avg. Cards per Player</div>
                    <div class="small">Yellow: <?= htmlspecialchars(number_format($activePlayers ? ($totalYellowActive / count($activePlayers)) : 0, 2)) ?> | Red: <?= htmlspecialchars(number_format($activePlayers ? ($totalRedActive / count($activePlayers)) : 0, 2)) ?></div>
                    <div class="divider" style="margin:8px 0;"></div>
                    <div class="small">Most Carded</div>
                    <div style="font-weight:700;"><?= htmlspecialchars($mostCardedLabel) ?></div>
                </div>
            </td>
        </tr>
    </table>

    <div style="height:6px;"></div>

    <div style="page-break-before: always;"></div>
    <h3 style="margin-bottom:8px;">Player Performance Table</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Player</th>
                            <th>Pos</th>
                            <th>Apps</th>
                            <th>Starts</th>
                            <th>Goals</th>
                            <th>Yellow</th>
                            <th>Red</th>
                            <th>Minutes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($activePlayers)): ?>
                            <?php foreach ($activePlayers as $player): ?>
                                <tr>
                                    <td><?= htmlspecialchars($player['name'] ?? '—') ?></td>
                                    <td style="text-align:center;"><?= htmlspecialchars($player['position'] ?? '—') ?></td>
                                    <td style="text-align:center;"><?= htmlspecialchars($formatNumber($player['appearances'] ?? 0)) ?></td>
                                    <td style="text-align:center;"><?= htmlspecialchars($formatNumber($player['starts'] ?? 0)) ?></td>
                                    <td style="text-align:center; font-weight:700;"><?= htmlspecialchars($formatNumber($player['goals'] ?? 0)) ?></td>
                                    <td style="text-align:center;"><?= htmlspecialchars($formatNumber($player['yellow_cards'] ?? 0)) ?></td>
                                    <td style="text-align:center;"><?= htmlspecialchars($formatNumber($player['red_cards'] ?? 0)) ?></td>
                                    <td style="text-align:center;"><?= htmlspecialchars($formatNumber($player['minutes_played'] ?? 0)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <?php if (!empty($inactivePlayers)): ?>
                            <tr class="table-section">
                                <td colspan="8" style="text-align:center;">Inactive Players</td>
                            </tr>
                            <?php foreach ($inactivePlayers as $player): ?>
                                <tr>
                                    <td><?= htmlspecialchars($player['name'] ?? '—') ?></td>
                                    <td style="text-align:center;"><?= htmlspecialchars($player['position'] ?? '—') ?></td>
                                    <td style="text-align:center;"><?= htmlspecialchars($formatNumber($player['appearances'] ?? 0)) ?></td>
                                    <td style="text-align:center;"><?= htmlspecialchars($formatNumber($player['starts'] ?? 0)) ?></td>
                                    <td style="text-align:center; font-weight:700;"><?= htmlspecialchars($formatNumber($player['goals'] ?? 0)) ?></td>
                                    <td style="text-align:center;"><?= htmlspecialchars($formatNumber($player['yellow_cards'] ?? 0)) ?></td>
                                    <td style="text-align:center;"><?= htmlspecialchars($formatNumber($player['red_cards'] ?? 0)) ?></td>
                                    <td style="text-align:center;"><?= htmlspecialchars($formatNumber($player['minutes_played'] ?? 0)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <?php if (empty($players)): ?>
                            <tr>
                                <td colspan="8" style="text-align:center;">No player data available.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
    <div style="height:6px;"></div>
</div>

</body>
</html>
