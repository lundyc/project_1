<?php
// Minimal PDF template for League Intelligence Team report
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($headerTitle ?? 'Team Intelligence Report') ?></title>
    <style>
        body { font-family: Arial, sans-serif; color: #222; background: #f8fafc; }
        h1 { font-size: 2.2em; margin-bottom: 0.2em; color: #2563eb; }
        h2 { font-size: 1.3em; margin-top: 1.5em; color: #0e7490; }
        .section { margin-bottom: 1.7em; padding: 1em; border-radius: 8px; background: #fff; box-shadow: 0 2px 8px #e0e7ef33; }
        .stats-table { width: 100%; border-collapse: collapse; margin-top: 1em; }
        .stats-table th, .stats-table td { border: 1px solid #cbd5e1; padding: 0.5em 0.8em; }
        .stats-table th { background: #dbeafe; color: #1e293b; }
        .highlight { color: #16a34a; font-weight: bold; }
        .profile-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.7em; margin-top: 0.7em; }
        .profile-item { background: #f1f5f9; border-radius: 6px; padding: 0.7em; text-align: center; }
        .graph-img { width: 100%; max-width: 350px; margin: 0.5em auto; display: block; border-radius: 8px; border: 2px solid #60a5fa; }
        .home-away-table th { background: #fef08a; color: #92400e; }
        .home-away-table td { background: #fef9c3; }
    </style>
</head>
<body>
    <h1><?= htmlspecialchars($headerTitle ?? 'Team Intelligence Report') ?></h1>
    <p><?= htmlspecialchars($headerDescription ?? '') ?></p>


    <div class="section">
        <h2>Team Snapshot</h2>
        <table class="stats-table" style="width: 60%; margin: 0 auto;">
            <tr>
                <td><strong>Position</strong></td>
                <td class="highlight"><?= htmlspecialchars($insights['position'] ?? '-') ?></td>
                <td><strong>Points</strong></td>
                <td class="highlight"><?= htmlspecialchars($insights['points'] ?? '-') ?></td>
            </tr>
            <tr>
                <td><strong>Goal Diff</strong></td>
                <td class="highlight"><?= htmlspecialchars($insights['goal_difference'] ?? '-') ?></td>
                <td><strong>Record</strong></td>
                <td><?= htmlspecialchars($insights['record'] ?? '-') ?></td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h2>Current Status</h2>
        <table class="stats-table" style="width: 60%; margin: 0 auto;">
            <tr><td><strong>Streak</strong></td><td class="highlight"><?= htmlspecialchars($insights['streak'] ?? '-') ?></td></tr>
            <tr><td><strong>Points per game</strong></td><td class="highlight"><?= htmlspecialchars($insights['points_per_game'] ?? '-') ?></td></tr>
            <tr><td><strong>Avg goals per match</strong></td><td class="highlight"><?= htmlspecialchars($insights['average_goals_per_match'] ?? '-') ?></td></tr>
            <tr><td><strong>Clean sheets</strong></td><td class="highlight"><?= htmlspecialchars($insights['clean_sheets'] ?? '-') ?></td></tr>
        </table>
    </div>

    <div class="section">
        <h2>Performance Profile</h2>
        <div class="profile-grid">
            <div class="profile-item">
                <div>Goals For</div>
                <div class="highlight"><?= htmlspecialchars($insights['goals_for'] ?? '-') ?></div>
            </div>
            <div class="profile-item">
                <div>Goals Against</div>
                <div class="highlight"><?= htmlspecialchars($insights['goals_against'] ?? '-') ?></div>
            </div>
            <div class="profile-item">
                <div>Home Record</div>
                <div><?= htmlspecialchars($insights['home']['record'] ?? '-') ?></div>
            </div>
            <div class="profile-item">
                <div>Away Record</div>
                <div><?= htmlspecialchars($insights['away']['record'] ?? '-') ?></div>
            </div>
        </div>
    </div>


    <div class="section">
        <h2>Form &amp; Momentum</h2>
        <p>Form: <span class="highlight"><?= !empty($insights['form']) ? htmlspecialchars(implode(' ', $insights['form'])) : '-' ?></span></p>
        <p>Momentum: <span class="highlight"><?= isset($insights['momentum']) && $insights['momentum'] !== '' ? htmlspecialchars($insights['momentum']) : '-' ?></span></p>
    </div>

    <div class="section">
        <h2>Trends &amp; Visual Analytics</h2>
        <p>Goal Trend:</p>
        <img src="<?= htmlspecialchars($insights['goal_trend_graph'] ?? 'https://via.placeholder.com/350x120?text=Goal+Trend+Graph') ?>" class="graph-img" alt="Goal Trend Graph">
        <p>Other Analytics:</p>
        <img src="<?= htmlspecialchars($insights['analytics_graph'] ?? 'https://via.placeholder.com/350x120?text=Analytics+Graph') ?>" class="graph-img" alt="Analytics Graph">
    </div>

    <div class="section">
        <h2>Home vs Away</h2>
        <table class="stats-table home-away-table">
            <tr><th></th><th>Home</th><th>Away</th></tr>
            <tr><td>Record</td><td><?= htmlspecialchars($insights['home']['record'] ?? '-') ?></td><td><?= htmlspecialchars($insights['away']['record'] ?? '-') ?></td></tr>
            <tr><td>Goals For</td><td><?= htmlspecialchars($insights['home']['goals_for'] ?? '-') ?></td><td><?= htmlspecialchars($insights['away']['goals_for'] ?? '-') ?></td></tr>
            <tr><td>Goals Against</td><td><?= htmlspecialchars($insights['home']['goals_against'] ?? '-') ?></td><td><?= htmlspecialchars($insights['away']['goals_against'] ?? '-') ?></td></tr>
            <tr><td>Clean Sheets</td><td><?= htmlspecialchars($insights['home']['clean_sheets'] ?? '-') ?></td><td><?= htmlspecialchars($insights['away']['clean_sheets'] ?? '-') ?></td></tr>
        </table>
    </div>


    <div class="section">
        <h2>Head-to-Head Intelligence</h2>
        <?php
        // Only show head-to-head for the team being viewed (e.g., Saltcoats)
        $myTeam = $insights['team_name'] ?? '';
        $opponents = $insights['head_to_head'] ?? [];
        if (!empty($opponents)):
            foreach ($opponents as $h2h):
                $opponentName = htmlspecialchars($h2h['opponent_name'] ?? '-');
                $totalWins = (int)($h2h['total_wins'] ?? 0);
                $homeWins = (int)($h2h['home_wins'] ?? 0);
                $awayWins = (int)($h2h['away_wins'] ?? 0);
                $draws = (int)($h2h['draws'] ?? 0);
                $played = (int)($h2h['played'] ?? 0);
                $myWins = (int)($h2h['my_wins'] ?? 0);
                $myHomeWins = (int)($h2h['my_home_wins'] ?? 0);
                $myAwayWins = (int)($h2h['my_away_wins'] ?? 0);
                $myDraws = (int)($h2h['my_draws'] ?? 0);
                $myPlayed = (int)($h2h['my_played'] ?? 0);
                $oppWinPct = $played > 0 ? round(100 * $totalWins / $played) : 0;
                $oppHomePct = $played > 0 ? round(100 * $homeWins / $played) : 0;
                $oppAwayPct = $played > 0 ? round(100 * $awayWins / $played) : 0;
                $myWinPct = $myPlayed > 0 ? round(100 * $myWins / $myPlayed) : 0;
                $myHomePct = $myPlayed > 0 ? round(100 * $myHomeWins / $myPlayed) : 0;
                $myAwayPct = $myPlayed > 0 ? round(100 * $myAwayWins / $myPlayed) : 0;
        ?>
        <table class="stats-table" style="margin-bottom: 1em;">
            <tr>
                <th><?= $opponentName ?></th>
                <th><?= htmlspecialchars($myTeam) ?></th>
            </tr>
            <tr>
                <td>
                    <div>Total wins: <?= $totalWins ?> (<?= $oppWinPct ?>%)</div>
                    <div>Home wins: <?= $homeWins ?> (<?= $oppHomePct ?>%)</div>
                    <div>Away wins: <?= $awayWins ?> (<?= $oppAwayPct ?>%)</div>
                </td>
                <td>
                    <div>Total wins: <?= $myWins ?> (<?= $myWinPct ?>%)</div>
                    <div>Home wins: <?= $myHomeWins ?> (<?= $myHomePct ?>%)</div>
                    <div>Away wins: <?= $myAwayWins ?> (<?= $myAwayPct ?>%)</div>
                </td>
            </tr>
            <tr>
                <td>Draws: <?= $draws ?></td>
                <td>Draws: <?= $myDraws ?></td>
            </tr>
            <tr>
                <td>Played: <?= $played ?></td>
                <td>Played: <?= $myPlayed ?></td>
            </tr>
        </table>
        <?php endforeach;
        else:
        ?>
            <p>No head-to-head data available.</p>
        <?php endif; ?>
    </div>

    <div class="section">
        <h2>Recent Results (Last 5)</h2>
        <table class="stats-table">
            <tr><th>Date</th><th>Opponent</th><th>Result</th></tr>
            <?php foreach (array_slice(($insights['match_history'] ?? []), 0, 5) as $match): ?>
                <tr>
                    <td><?= htmlspecialchars($match['date'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($match['opponent_name'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($match['result'] ?? '-') ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>
