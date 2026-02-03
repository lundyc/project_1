<?php
// match_report.php - Standalone printable match report for PDF export
// Usage: /app/views/pages/stats/match_report.php?match_id=XX&club_id=YY

if (!isset($match_for_report)) {
    require_once __DIR__ . '/../../../config/config.php';
    require_once __DIR__ . '/../../../lib/match_repository.php';
    require_once __DIR__ . '/../../../lib/StatsService.php';
    require_once __DIR__ . '/../../../lib/player_repository.php';
    require_once __DIR__ . '/../../../lib/team_repository.php';

    $matchId = isset($_GET['match_id']) ? (int)$_GET['match_id'] : 0;
    $clubId = isset($_GET['club_id']) ? (int)$_GET['club_id'] : null;
    $match = get_match($matchId);
} else {
    $matchId = $matchId_for_report;
    $clubId = $clubId_for_report;
    $match = $match_for_report;
    require_once __DIR__ . '/../../../lib/StatsService.php';
    require_once __DIR__ . '/../../../lib/player_repository.php';
    require_once __DIR__ . '/../../../lib/team_repository.php';
}

$statsService = new StatsService($clubId);
$matchStats = $statsService->getMatchStats($matchId);
$derivedData = $statsService->getMatchDerivedData($matchId);
$teamPerformance = $statsService->getTeamPerformanceStats();
$playerPerformance = $statsService->getPlayerPerformanceForMatch($matchId);
require_once __DIR__ . '/../../../lib/match_substitution_repository.php';
$substitutions = get_match_substitutions($matchId);
$homeTeam = get_team_by_id($match['home_team_id']);
$awayTeam = get_team_by_id($match['away_team_id']);

$matchNotes = htmlspecialchars($match['notes'] ?? '-');
$attendanceDisplay = htmlspecialchars($match['attendance'] ?? '-');
$refereeDisplay = htmlspecialchars($match['referee'] ?? '-');
$statusDisplay = htmlspecialchars($match['status'] ?? '-');

$kickoffDisplay = '-';
if (!empty($match['kickoff_at']) && strtotime($match['kickoff_at']) !== false) {
    $dt = new DateTime($match['kickoff_at']);
    $kickoffDisplay = $dt->format('d/m/Y @ H:i');
}

$seasonDisplay = '-';
if (!empty($match['season'])) {
    $seasonDisplay = htmlspecialchars($match['season']);
} elseif (!empty($match['season_id'])) {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT name FROM seasons WHERE id = ?');
    $stmt->execute([$match['season_id']]);
    $seasonName = $stmt->fetchColumn();
    $seasonDisplay = $seasonName ? htmlspecialchars($seasonName) : '-';
}

$homeShots = (int)($matchStats['home']['shots'] ?? 0);
$awayShots = (int)($matchStats['away']['shots'] ?? 0);

$homeShotsOnTarget = $matchStats['home']['shots_on_target'] ?? null;
if ($homeShotsOnTarget === null || $homeShotsOnTarget === 0) {
    $homeShotsOnTarget = $derivedData['derived']['by_type_team']['shot_on_target']['home'] ?? 0;
}
$homeShotsOnTarget = (int)$homeShotsOnTarget;

$awayShotsOnTarget = $matchStats['away']['shots_on_target'] ?? null;
if ($awayShotsOnTarget === null || $awayShotsOnTarget === 0) {
    $awayShotsOnTarget = $derivedData['derived']['by_type_team']['shot_on_target']['away'] ?? 0;
}
$awayShotsOnTarget = (int)$awayShotsOnTarget;

$homeShotsOffTarget = $matchStats['home']['shots_off_target'] ?? null;
if ($homeShotsOffTarget === null || $homeShotsOffTarget === 0) {
    $homeShotsOffTarget = $derivedData['derived']['by_type_team']['shot_off_target']['home'] ?? 0;
}
$homeShotsOffTarget = (int)$homeShotsOffTarget;

$awayShotsOffTarget = $matchStats['away']['shots_off_target'] ?? null;
if ($awayShotsOffTarget === null || $awayShotsOffTarget === 0) {
    $awayShotsOffTarget = $derivedData['derived']['by_type_team']['shot_off_target']['away'] ?? 0;
}
$awayShotsOffTarget = (int)$awayShotsOffTarget;

$derivedByType = $derivedData['derived']['by_type_team'] ?? [];

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Match Report</title>
    <style>
        table { border-collapse: collapse; width: 100%; font-size: 13px; }
        th { background: #e3e6f3; text-align: left; padding: 4px; }
        td { padding: 4px; vertical-align: top; }
        h1, h2, h3 { margin: 1em 0 0.5em; font-family: sans-serif; }
        .divider { height: 1px; margin: 1em 0; background: #ccc; }
        .section-table td { border-bottom: 1px solid #e3e6f3; }
        .match-score td { border: none; }
        .info-table td { border: none; }
        .event-list { margin: 0; padding-left: 1.1em; }
    </style>
</head>
<body>
<h1>Match Report</h1>
<table class="match-score" style="margin-bottom: 1em;">
    <tr>
        <td style="text-align:left; font-weight:600;">
            <span style="font-size:0.95em; color:#444;">Home</span><br>
            <span style="font-size:1.5em; font-weight:bold; color:#222; line-height:1.1; display:inline-block; margin-top:2px;">
                <?= htmlspecialchars($homeTeam['name']) ?>
            </span>
        </td>
        <td style="text-align:center; font-size:2.5em; font-weight:bold;"><?= (int)($matchStats['home']['goals'] ?? 0) ?> : <?= (int)($matchStats['away']['goals'] ?? 0) ?></td>
        <td style="text-align:right; font-weight:600;">
            <span style="font-size:0.95em; color:#444;">Away</span><br>
            <span style="font-size:1.5em; font-weight:bold; color:#222; line-height:1.1; display:inline-block; margin-top:2px;">
                <?= htmlspecialchars($awayTeam['name']) ?>
            </span>
        </td>
    </tr>
</table>
<div class="divider"></div>
<h2>Match Info</h2>
<table class="info-table" style="margin-bottom: 1em;">
    <tr>
        <td style="width:55%;">
            <table style="width:100%;">
                <tr><td><strong>Date / Time:</strong> <?= htmlspecialchars($kickoffDisplay) ?></td></tr>
                <tr><td><strong>Venue:</strong> <?= htmlspecialchars($match['venue'] ?? '-') ?></td></tr>
                <tr><td><strong>Competition:</strong> <?= htmlspecialchars($match['competition'] ?? '-') ?></td></tr>
                <tr><td><strong>Season:</strong> <?= $seasonDisplay ?></td></tr>
            </table>
        </td>
        <td style="width:45%;">
            <table style="width:100%;">
                <tr><td><strong>Attendance:</strong> <?= $attendanceDisplay ?></td></tr>
                <tr><td><strong>Referee:</strong> <?= $refereeDisplay ?></td></tr>
                <tr><td><strong>Status:</strong> <?= $statusDisplay ?></td></tr>
            </table>
        </td>
    </tr>
</table>
<div class="divider"></div>

<h2>Overview</h2>
<table class="min-w-full bg-bg-tertiary text-text-primary text-xs rounded-xl overflow-hidden">
    <thead>
        <tr class="bg-bg-secondary text-text-muted uppercase font-semibold text-xs">
            <th class="px-4 py-3">Metric</th>
            <th class="px-4 py-3"><?= htmlspecialchars($homeTeam['name']) ?></th>
            <th class="px-4 py-3"><?= htmlspecialchars($awayTeam['name']) ?></th>
        </tr>
    </thead>
    <tbody>
        <tr class="border-b border-border-soft hover:bg-bg-secondary/60 transition-colors"><td class="px-4 py-2">Goals</td><td class="px-4 py-2"><?= (int)($matchStats['home']['goals'] ?? 0) ?></td><td class="px-4 py-2"><?= (int)($matchStats['away']['goals'] ?? 0) ?></td></tr>
        <tr class="border-b border-border-soft hover:bg-bg-secondary/60 transition-colors"><td class="px-4 py-2">Shots</td><td class="px-4 py-2"><?= $homeShots ?></td><td class="px-4 py-2"><?= $awayShots ?></td></tr>
        <tr class="border-b border-border-soft hover:bg-bg-secondary/60 transition-colors"><td class="px-4 py-2">&nbsp;&nbsp;&nbsp;On Target</td><td class="px-4 py-2"><?= $homeShotsOnTarget ?></td><td class="px-4 py-2"><?= $awayShotsOnTarget ?></td></tr>
        <tr class="border-b border-border-soft hover:bg-bg-secondary/60 transition-colors"><td class="px-4 py-2">&nbsp;&nbsp;&nbsp;Off Target</td><td class="px-4 py-2"><?= $homeShotsOffTarget ?></td><td class="px-4 py-2"><?= $awayShotsOffTarget ?></td></tr>
        <tr class="border-b border-border-soft hover:bg-bg-secondary/60 transition-colors"><td class="px-4 py-2">Corners</td><td class="px-4 py-2"><?= (int)($matchStats['home']['corners'] ?? 0) ?></td><td class="px-4 py-2"><?= (int)($matchStats['away']['corners'] ?? 0) ?></td></tr>
        <tr class="border-b border-border-soft hover:bg-bg-secondary/60 transition-colors"><td class="px-4 py-2">Free Kicks</td><td class="px-4 py-2"><?= (int)($matchStats['home']['free_kicks'] ?? 0) ?></td><td class="px-4 py-2"><?= (int)($matchStats['away']['free_kicks'] ?? 0) ?></td></tr>
        <tr class="border-b border-border-soft hover:bg-bg-secondary/60 transition-colors"><td class="px-4 py-2">Penalties</td><td class="px-4 py-2"><?= (int)($matchStats['home']['penalties'] ?? 0) ?></td><td class="px-4 py-2"><?= (int)($matchStats['away']['penalties'] ?? 0) ?></td></tr>
        <tr class="border-b border-border-soft hover:bg-bg-secondary/60 transition-colors"><td class="px-4 py-2">Yellow Cards</td><td class="px-4 py-2"><?= (int)($matchStats['home']['yellow_cards'] ?? 0) ?></td><td class="px-4 py-2"><?= (int)($matchStats['away']['yellow_cards'] ?? 0) ?></td></tr>
        <tr><td class="px-4 py-2">Red Cards</td><td class="px-4 py-2"><?= (int)($matchStats['home']['red_cards'] ?? 0) ?></td><td class="px-4 py-2"><?= (int)($matchStats['away']['red_cards'] ?? 0) ?></td></tr>
    </tbody>
</table>

<h2>Exploited Event Types</h2>
<table class="min-w-full bg-bg-tertiary text-text-primary text-xs rounded-xl overflow-hidden">
    <thead>
        <tr class="bg-bg-secondary text-text-muted uppercase font-semibold text-xs">
            <th class="px-4 py-3">Event Type</th>
            <th class="px-4 py-3"><?= htmlspecialchars($homeTeam['name']) ?></th>
            <th class="px-4 py-3"><?= htmlspecialchars($awayTeam['name']) ?></th>
        </tr>
    </thead>
    <tbody>
    <?php
    $eventTypes = [
        'offside' => 'Offsides',
        'chance' => 'Chances Created',
        'mistake' => 'Mistakes',
        'turnover' => 'Turnovers',
        'good_play' => 'Good Plays',
        'highlight' => 'Highlights',
    ];
    foreach ($eventTypes as $key => $label):
    ?>
        <tr class="border-b border-border-soft hover:bg-bg-secondary/60 transition-colors">
            <td class="px-4 py-2"><?= $label ?></td>
            <td class="px-4 py-2"><?= (int)($derivedByType[$key]['home'] ?? 0) ?></td>
            <td class="px-4 py-2"><?= (int)($derivedByType[$key]['away'] ?? 0) ?></td>
        </tr>
    <?php endforeach; ?>
        <tr>
            <td class="px-4 py-2">Set Pieces (Corners+FK+Pens)</td>
            <td class="px-4 py-2"><?= (int)($matchStats['home']['corners'] ?? 0) + (int)($matchStats['home']['free_kicks'] ?? 0) + (int)($matchStats['home']['penalties'] ?? 0) ?></td>
            <td class="px-4 py-2"><?= (int)($matchStats['away']['corners'] ?? 0) + (int)($matchStats['away']['free_kicks'] ?? 0) + (int)($matchStats['away']['penalties'] ?? 0) ?></td>
        </tr>
    </tbody>
</table>

<h2>Efficiency & Discipline</h2>
<table class="min-w-full bg-bg-tertiary text-text-primary text-xs rounded-xl overflow-hidden">
    <thead>
        <tr class="bg-bg-secondary text-text-muted uppercase font-semibold text-xs">
            <th class="px-4 py-3">Metric</th>
            <th class="px-4 py-3"><?= htmlspecialchars($homeTeam['name']) ?></th>
            <th class="px-4 py-3"><?= htmlspecialchars($awayTeam['name']) ?></th>
        </tr>
    </thead>
    <tbody>
    <?php
    $hShots = $homeShots;
    $aShots = $awayShots;
    $hOnTarget = $homeShotsOnTarget;
    $aOnTarget = $awayShotsOnTarget;
    $hGoals = (int)($matchStats['home']['goals'] ?? 0);
    $aGoals = (int)($matchStats['away']['goals'] ?? 0);
    $hCards = (int)($matchStats['home']['yellow_cards'] ?? 0) + (int)($matchStats['home']['red_cards'] ?? 0);
    $aCards = (int)($matchStats['away']['yellow_cards'] ?? 0) + (int)($matchStats['away']['red_cards'] ?? 0);
    $hFouls = (int)($derivedByType['foul']['home'] ?? 0);
    $aFouls = (int)($derivedByType['foul']['away'] ?? 0);
    $hSetPieces = (int)($matchStats['home']['corners'] ?? 0) + (int)($matchStats['home']['free_kicks'] ?? 0) + (int)($matchStats['home']['penalties'] ?? 0);
    $aSetPieces = (int)($matchStats['away']['corners'] ?? 0) + (int)($matchStats['away']['free_kicks'] ?? 0) + (int)($matchStats['away']['penalties'] ?? 0);
    $hShotAcc = $hShots > 0 ? round($hOnTarget / $hShots * 100, 1) : '—';
    $aShotAcc = $aShots > 0 ? round($aOnTarget / $aShots * 100, 1) : '—';
    $hShotConv = $hShots > 0 ? round($hGoals / $hShots * 100, 1) : '—';
    $aShotConv = $aShots > 0 ? round($aGoals / $aShots * 100, 1) : '—';
    $hGPSOT = $hOnTarget > 0 ? round($hGoals / $hOnTarget, 2) : '—';
    $aGPSOT = $aOnTarget > 0 ? round($aGoals / $aOnTarget, 2) : '—';
    $hFPC = $hCards > 0 ? round($hFouls / $hCards, 2) : '—';
    $aFPC = $aCards > 0 ? round($aFouls / $aCards, 2) : '—';
    $hSPPS = $hShots > 0 ? round($hSetPieces / $hShots, 2) : '—';
    $aSPPS = $aShots > 0 ? round($aSetPieces / $aShots, 2) : '—';
    ?>
    <tr class="border-b border-border-soft hover:bg-bg-secondary/60 transition-colors"><td class="px-4 py-2">Shot Accuracy (%)</td><td class="px-4 py-2"><?= $hShotAcc ?></td><td class="px-4 py-2"><?= $aShotAcc ?></td></tr>
    <tr class="border-b border-border-soft hover:bg-bg-secondary/60 transition-colors"><td class="px-4 py-2">Shot Conversion (%)</td><td class="px-4 py-2"><?= $hShotConv ?></td><td class="px-4 py-2"><?= $aShotConv ?></td></tr>
    <tr class="border-b border-border-soft hover:bg-bg-secondary/60 transition-colors"><td class="px-4 py-2">Goals per Shot on Target</td><td class="px-4 py-2"><?= $hGPSOT ?></td><td class="px-4 py-2"><?= $aGPSOT ?></td></tr>
    <tr class="border-b border-border-soft hover:bg-bg-secondary/60 transition-colors"><td class="px-4 py-2">Fouls per Card</td><td class="px-4 py-2"><?= $hFPC ?></td><td class="px-4 py-2"><?= $aFPC ?></td></tr>
    <tr><td class="px-4 py-2">Set Pieces per Shot</td><td class="px-4 py-2"><?= $hSPPS ?></td><td class="px-4 py-2"><?= $aSPPS ?></td></tr>
    </tbody>
</table>

<h2>Match Events</h2>
<table class="section-table">
    <tr><td>
        <ul class="event-list">
            <?php foreach (($overview['events']['home_goals'] ?? []) as $goal): ?>
                <li>Home Goal: <?= htmlspecialchars($goal['player'] ?? 'Unknown') ?> <?= isset($goal['match_second']) ? (int)floor((int)($goal['match_second'] ?? 0) / 60) : htmlspecialchars($goal['minute'] ?? '') ?>'</li>
            <?php endforeach; ?>
            <?php foreach (($overview['events']['away_goals'] ?? []) as $goal): ?>
                <li>Away Goal: <?= htmlspecialchars($goal['player'] ?? 'Unknown') ?> <?= isset($goal['match_second']) ? (int)floor((int)($goal['match_second'] ?? 0) / 60) : htmlspecialchars($goal['minute'] ?? '') ?>'</li>
            <?php endforeach; ?>
        </ul>
    </td></tr>
</table>

<h2>Player Performance</h2>
<h3>Starting XI</h3>
<?php
$subOffByMatchPlayerId = [];
foreach ($substitutions as $sub) {
    if (!empty($sub['player_off_match_player_id'])) {
        $subOffByMatchPlayerId[$sub['player_off_match_player_id']] = $sub;
    }
}
$matchDuration = (int)($match['duration_minutes'] ?? 90);
$hasRedCards = ((int)($matchStats['home']['red_cards'] ?? 0) + (int)($matchStats['away']['red_cards'] ?? 0)) > 0;
?>
<table class="min-w-full bg-bg-tertiary text-text-primary text-xs rounded-xl overflow-hidden">
    <thead>
        <tr class="bg-bg-secondary text-text-muted uppercase font-semibold text-xs">
            <th class="px-4 py-3">#</th>
            <th class="px-4 py-3">Player</th>
            <th class="px-4 py-3">Position</th>
            <th class="px-4 py-3">Goals</th>
            <th class="px-4 py-3">Yellow Cards</th>
            <?php if ($hasRedCards): ?><th class="px-4 py-3">Red Cards</th><?php endif; ?>
            <th class="px-4 py-3">Minutes Played</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach (($playerPerformance['starting_xi'] ?? []) as $player): ?>
        <?php
        $mpid = $player['match_player_id'] ?? null;
        $minutes = '—';
        if ($mpid && isset($subOffByMatchPlayerId[$mpid])) {
            $subData = $subOffByMatchPlayerId[$mpid];
            $minutes = isset($subData['match_second']) ? (int)floor((int)($subData['match_second'] ?? 0) / 60) : (int)($subData['minute'] ?? 0);
        } elseif ($mpid) {
            $minutes = $matchDuration;
        }
        $goals = (int)($player['goals'] ?? 0);
        ?>
        <tr class="border-b border-border-soft hover:bg-bg-secondary/60 transition-colors">
            <td class="px-4 py-2"><?= htmlspecialchars($player['shirt_number'] ?? '-') ?></td>
            <td class="px-4 py-2"><?= htmlspecialchars($player['name'] ?? '-') ?></td>
            <td class="px-4 py-2"><?= htmlspecialchars($player['position'] ?? '-') ?></td>
            <td class="px-4 py-2"><?= $goals > 0 ? $goals : '' ?></td>
            <td class="px-4 py-2"><?= ($player['yellow_cards'] ?? 0 ? (int)$player['yellow_cards'] : '') ?></td>
            <?php if ($hasRedCards): ?><td class="px-4 py-2"><?= ($player['red_cards'] ?? 0 ? (int)$player['red_cards'] : '') ?></td><?php endif; ?>
            <td class="px-4 py-2"><?= $minutes !== '' ? $minutes : '—' ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php
/*
<h2>Shot Map</h2>
<table style="border:2px dashed #bbb; height:180px; background:#f9f9f9; margin-bottom:2em; text-align:center; vertical-align:middle;">
    <tr style="height:180px;">
        <td style="color:#888; font-size:1.2em;">Shot map visualization coming soon</td>
    </tr>
</table>
*/
?>
<h3>Substitutes</h3>
<?php
$subsGoals = 0;
$subsYellows = 0;
$subsReds = 0;
foreach (($playerPerformance['substitutes'] ?? []) as $player) {
    $subsGoals += (int)($player['goals'] ?? 0);
    $subsYellows += (int)($player['yellow_cards'] ?? 0);
    $subsReds += (int)($player['red_cards'] ?? 0);
}
$showGoals = $subsGoals > 0;
$showYellows = $subsYellows > 0;
$showReds = $subsReds > 0;
$subEventByMatchPlayerId = [];
foreach ($substitutions as $sub) {
    if (!empty($sub['player_on_match_player_id'])) {
        $subEventByMatchPlayerId[$sub['player_on_match_player_id']] = $sub;
    }
}
?>
<table class="section-table">
    <tr>
        <th>#</th><th>Player</th><th>Position</th><?php if ($showGoals): ?><th>Goals</th><?php endif; ?><?php if ($showYellows): ?><th>Yellow Cards</th><?php endif; ?><?php if ($showReds): ?><th>Red Cards</th><?php endif; ?><th>Substitution</th>
    </tr>
    <?php foreach (($playerPerformance['substitutes'] ?? []) as $player): ?>
        <?php
        $matchPlayerId = $player['match_player_id'] ?? null;
        $sub = $matchPlayerId ? ($subEventByMatchPlayerId[$matchPlayerId] ?? null) : null;
        ?>
        <tr>
            <td><?= htmlspecialchars($player['shirt_number'] ?? '-') ?></td>
            <td><?= htmlspecialchars($player['name'] ?? '-') ?></td>
            <td><?= htmlspecialchars($player['position'] ?? '-') ?></td>
            <?php if ($showGoals): ?><td><?= ((int)($player['goals'] ?? 0) > 0 ? (int)$player['goals'] : '') ?></td><?php endif; ?>
            <?php if ($showYellows): ?><td><?= ($player['yellow_cards'] ?? 0 ? (int)$player['yellow_cards'] : '') ?></td><?php endif; ?>
            <?php if ($showReds): ?><td><?= ($player['red_cards'] ?? 0 ? (int)$player['red_cards'] : '') ?></td><?php endif; ?>
            <td>
                <?php if ($sub): ?>
                    <span style="color:green;font-weight:bold;">&#8594;</span>
                    On for <?= htmlspecialchars($sub['player_off_name'] ?? '-') ?> (<?= isset($sub['match_second']) ? (int)floor((int)($sub['match_second'] ?? 0) / 60) : (int)($sub['minute'] ?? 0) ?>')
                <?php else: ?>
                    <em>Unused</em>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
</body>
</html>
