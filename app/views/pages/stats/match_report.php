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

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Match Report - <?= htmlspecialchars($homeTeam['name']) ?> vs <?= htmlspecialchars($awayTeam['name']) ?></title>
    <style>
        body { font-family: Arial, sans-serif; color: #222; background: #fff; margin: 0; padding: 0; }
        .report-container { max-width: 900px; margin: 0 auto; padding: 32px; }
        h1, h2, h3 { color: #1a237e; margin-bottom: 0.5em; }
        h1 { font-size: 2.2em; }
        h2 { font-size: 1.4em; border-bottom: 1px solid #eee; padding-bottom: 0.2em; margin-top: 2em; }
        h3 { font-size: 1.1em; margin-top: 1.5em; }
        .scoreline { font-size: 2.5em; font-weight: bold; margin: 0.5em 0; }
        .teams { display: flex; justify-content: space-between; align-items: center; }
        .team-name { font-size: 1.3em; font-weight: bold; }
        .section { margin-bottom: 2em; }
        table { width: 100%; border-collapse: collapse; margin: 1em 0; }
        th, td { border: 1px solid #bbb; padding: 8px 10px; text-align: center; }
        th { background: #f5f5f5; color: #222; }
        .event-list { margin: 0.5em 0 1em 0; }
        .event-list li { margin-bottom: 0.2em; }
        .subsection { margin-top: 1.2em; }
        .label { font-weight: bold; color: #333; }
        .note { color: #666; font-size: 0.95em; }
        .divider { border-top: 2px solid #eee; margin: 2em 0; }
    </style>
</head>
<body>
<div class="report-container">
    <h1>Match Report</h1>
    <div class="teams">
        <div class="team-name">Home: <?= htmlspecialchars($homeTeam['name']) ?></div>
        <div class="scoreline">
            <?= (int)($matchStats['home']['goals'] ?? 0) ?> : <?= (int)($matchStats['away']['goals'] ?? 0) ?>
        </div>
        <div class="team-name">Away: <?= htmlspecialchars($awayTeam['name']) ?></div>
    </div>
    <div class="label">Competition:</div> <?= htmlspecialchars($match['competition'] ?? '-') ?>
    <div class="label">Date:</div> <?= htmlspecialchars($match['date'] ?? '-') ?>
    <div class="divider"></div>

    <h2>Overview</h2>
    <table>
        <tr><th>Metric</th><th>Home</th><th>Away</th></tr>
        <?php foreach ([
            'Goals' => 'goals',
            'Shots' => 'shots',
            'Corners' => 'corners',
            'Free Kicks' => 'free_kicks',
            'Penalties' => 'penalties',
            'Fouls' => 'fouls',
            'Yellow Cards' => 'yellow_cards',
            'Red Cards' => 'red_cards',
            'Substitutions' => 'substitutions',
        ] as $label => $key): ?>
            <tr>
                <td><?= htmlspecialchars($label) ?></td>
                <td><?= (int)($matchStats['home'][$key] ?? 0) ?></td>
                <td><?= (int)($matchStats['away'][$key] ?? 0) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <div class="subsection">
        <div class="label">Events</div>
        <ul class="event-list">
            <?php foreach (($overview['events']['home_goals'] ?? []) as $goal): ?>
                <li>Home Goal: <?= htmlspecialchars($goal['player'] ?? 'Unknown') ?> <?= htmlspecialchars($goal['minute'] ?? '') ?>'</li>
            <?php endforeach; ?>
            <?php foreach (($overview['events']['away_goals'] ?? []) as $goal): ?>
                <li>Away Goal: <?= htmlspecialchars($goal['player'] ?? 'Unknown') ?> <?= htmlspecialchars($goal['minute'] ?? '') ?>'</li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="divider"></div>
    <h2>Team Performance</h2>
    <table>
        <tr><th>Metric</th><th>Home</th><th>Away</th></tr>
        <?php foreach ([
            'Goals' => 'goals_for',
            'Goals Against' => 'goals_against',
            'Wins' => 'wins',
            'Draws' => 'draws',
            'Losses' => 'losses',
            'Goal Difference' => 'goal_difference',
            'Matches Played' => 'matches',
        ] as $label => $key): ?>
            <tr>
                <td><?= htmlspecialchars($label) ?></td>
                <td><?= (int)($teamPerformance['home_away']['home'][$key] ?? 0) ?></td>
                <td><?= (int)($teamPerformance['home_away']['away'][$key] ?? 0) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    <div class="divider"></div>
    <h2>Player Performance</h2>
    <h3>Starting XI</h3>
    <table>
        <tr><th>#</th><th>Player</th><th>Position</th><th>Goals</th><th>Cards</th></tr>
        <?php foreach (($playerPerformance['starting_xi'] ?? []) as $player): ?>
            <tr>
                <td><?= htmlspecialchars($player['shirt_number'] ?? '-') ?></td>
                <td><?= htmlspecialchars($player['name'] ?? '-') ?></td>
                <td><?= htmlspecialchars($player['position'] ?? '-') ?></td>
                <td><?= ((int)($player['goals'] ?? 0) > 0 ? (int)$player['goals'] : '') ?></td>
                <td><?= ($player['yellow_cards'] ?? 0 ? '🟨 ' . (int)$player['yellow_cards'] : '') . ($player['red_cards'] ?? 0 ? ' 🟥 ' . (int)$player['red_cards'] : '') ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    <h3>Substitutes</h3>
    <table>
        <tr><th>Event</th><th>#</th><th>Player</th><th>Position</th><th>Minute</th></tr>
        <?php
        // Collect all player_on and player_off IDs from substitutions
        $usedSubIds = [];
        foreach ($substitutions as $sub) {
            if (!empty($sub['player_on_match_player_id'])) $usedSubIds[] = $sub['player_on_match_player_id'];
            if (!empty($sub['player_off_match_player_id'])) $usedSubIds[] = $sub['player_off_match_player_id'];
            ?>
            <tr>
                <td>ON</td>
                <td><?= htmlspecialchars($sub['player_on_shirt'] ?? '-') ?></td>
                <td><?= htmlspecialchars($sub['player_on_name'] ?? '-') ?></td>
                <td></td>
                <td><?= htmlspecialchars($sub['minute'] ?? '-') ?></td>
            </tr>
            <tr>
                <td>OFF</td>
                <td><?= htmlspecialchars($sub['player_off_shirt'] ?? '-') ?></td>
                <td><?= htmlspecialchars($sub['player_off_name'] ?? '-') ?></td>
                <td></td>
                <td><?= htmlspecialchars($sub['minute'] ?? '-') ?></td>
            </tr>
        <?php }
        // Show unused substitutes (not in any substitution event)
        $usedSubIds = array_unique($usedSubIds);
        foreach (($playerPerformance['substitutes'] ?? []) as $player) {
            if (!in_array($player['player_id'], $usedSubIds)) {
                ?>
                <tr>
                    <td>Unused</td>
                    <td><?= htmlspecialchars($player['shirt_number'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($player['name'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($player['position'] ?? '-') ?></td>
                    <td></td>
                </tr>
            <?php }
        }
        ?>
    </table>
</div>
</body>
</html>
