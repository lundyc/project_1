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
?>
<body>
<table class="report-container noborder" style="width:100%; max-width:900px; margin:0 auto; padding:32px; border:0;">
    <tr><td colspan="2" style="border:0; padding:0;"><h1>Match Report</h1></td></tr>
    <tr>
        <td colspan="2" style="border:0; padding:0;">
            <table style="width:100%; border:0;">
                <tr>
                    <td class="team-name" style="width:33%; text-align:left; border:0;">Home: <?= htmlspecialchars($homeTeam['name']) ?></td>
                    <td class="scoreline" style="width:34%; text-align:center; border:0; font-size:2.5em; font-weight:bold;">
                        <?= (int)($matchStats['home']['goals'] ?? 0) ?> : <?= (int)($matchStats['away']['goals'] ?? 0) ?>
                    </td>
                    <td class="team-name" style="width:33%; text-align:right; border:0;">Away: <?= htmlspecialchars($awayTeam['name']) ?></td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td style="vertical-align:top; width:50%; border:0; padding-right:1em; min-width:220px;">
            <table style="width:100%; border:0;">
                <tr><td style="border:0;"><span class="label">Match Info:</span> <?= htmlspecialchars($match['notes'] ?? '-') ?></td></tr>
                <tr><td style="border:0;"><span class="label">Date / Time:</span> <?php
                    $dateStr = $match['kickoff_at'] ?? '';
                    if ($dateStr && strtotime($dateStr) !== false) {
                        $dt = new DateTime($dateStr);
                        echo $dt->format('d/m/Y @ H:i');
                    } else {
                        echo '-';
                    }
                ?></td></tr>
                <tr><td style="border:0;"><span class="label">Venue:</span> <?= htmlspecialchars($match['venue'] ?? '-') ?></td></tr>
                <tr><td style="border:0;"><span class="label">Competition:</span> <?= htmlspecialchars($match['competition'] ?? '-') ?></td></tr>
                <tr><td style="border:0;"><span class="label">Season:</span> <?php
                    if (!empty($match['season'])) {
                        echo htmlspecialchars($match['season']);
                    } elseif (!empty($match['season_id'])) {
                        $pdo = db();
                        $stmt = $pdo->prepare('SELECT name FROM seasons WHERE id = ?');
                        $stmt->execute([$match['season_id']]);
                        $season = $stmt->fetchColumn();
                        echo $season ? htmlspecialchars($season) : '-';
                    } else {
                        echo '-';
                    }
                ?></td></tr>
            </table>
        </td>
        <td style="vertical-align:top; width:50%; border:0; min-width:180px;">
            <table style="width:100%; border:0;">
                <tr><td style="border:0;"><span class="label">&nbsp;</span></td></tr>
                <tr><td style="border:0;"><span class="label">Attendance:</span> <?= htmlspecialchars($match['attendance'] ?? '-') ?></td></tr>
                <tr><td style="border:0;"><span class="label">Referee:</span> <?= htmlspecialchars($match['referee'] ?? '-') ?></td></tr>
                <tr><td style="border:0;"><span class="label">Status:</span> <?= htmlspecialchars($match['status'] ?? '-') ?></td></tr>
            </table>
        </td>
    </tr>
    <tr><td colspan="2" style="border:0; padding:0;"><div class="divider"></div></td></tr>
                    echo $dt->format('d/m/Y @ H:i');
                } else {
                    echo '-';
                }
            ?></div>
            <div><span class="label">Venue:</span> <?= htmlspecialchars($match['venue'] ?? '-') ?></div>
            <div><span class="label">Competition:</span> <?= htmlspecialchars($match['competition'] ?? '-') ?></div>
            <div><span class="label">Season:</span> <?php
                if (!empty($match['season'])) {
                    echo htmlspecialchars($match['season']);
                } elseif (!empty($match['season_id'])) {
                    $pdo = db();
                    $stmt = $pdo->prepare('SELECT name FROM seasons WHERE id = ?');
                    $stmt->execute([$match['season_id']]);
                    $season = $stmt->fetchColumn();
                    echo $season ? htmlspecialchars($season) : '-';
                } else {
                    echo '-';
                }
            ?></div>
        </div>
        <div style="flex:1; min-width:180px;">
        <div><span class="label"> </span></div>    
        <div><span class="label">Attendance:</span> <?= htmlspecialchars($match['attendance'] ?? '-') ?></div>
            <div><span class="label">Referee:</span> <?= htmlspecialchars($match['referee'] ?? '-') ?></div>
            <div><span class="label">Status:</span> <?= htmlspecialchars($match['status'] ?? '-') ?></div>
        </div>
    </div>
    <div class="divider"></div>

    <h1>Overview</h1>
    <table>
        <tr><th>Metric</th><th><?= htmlspecialchars($homeTeam['name']) ?></th><th><?= htmlspecialchars($awayTeam['name']) ?></th></tr>
        <tr>
            <td>Goals</td>
            <td><?= (int)($matchStats['home']['goals'] ?? 0) ?></td>
            <td><?= (int)($matchStats['away']['goals'] ?? 0) ?></td>
        </tr>
        <tr>
            <td>Shots</td>
            <td><?= (int)($matchStats['home']['shots'] ?? 0) ?></td>
            <td><?= (int)($matchStats['away']['shots'] ?? 0) ?></td>
        </tr>
        <tr>
            <td>&nbsp;&nbsp;&nbsp;On Target</td>
            <td><?php
                $onTarget = $matchStats['home']['shots_on_target'] ?? null;
                if ($onTarget === null || $onTarget === 0) {
                    $onTarget = $derivedData['derived']['by_type_team']['shot_on_target']['home'] ?? 0;
                }
                echo (int)$onTarget;
            ?></td>
            <td><?php
                $onTarget = $matchStats['away']['shots_on_target'] ?? null;
                if ($onTarget === null || $onTarget === 0) {
                    $onTarget = $derivedData['derived']['by_type_team']['shot_on_target']['away'] ?? 0;
                }
                echo (int)$onTarget;
            ?></td>
        </tr>
        <tr>
            <td>&nbsp;&nbsp;&nbsp;Off Target</td>
            <td><?php
                $offTarget = $matchStats['home']['shots_off_target'] ?? null;
                if ($offTarget === null || $offTarget === 0) {
                    $offTarget = $derivedData['derived']['by_type_team']['shot_off_target']['home'] ?? 0;
                }
                echo (int)$offTarget;
            ?></td>
            <td><?php
                $offTarget = $matchStats['away']['shots_off_target'] ?? null;
                if ($offTarget === null || $offTarget === 0) {
                    $offTarget = $derivedData['derived']['by_type_team']['shot_off_target']['away'] ?? 0;
                }
                echo (int)$offTarget;
            ?></td>
        </tr>
        <tr>
            <td>Corners</td>
            <td><?= (int)($matchStats['home']['corners'] ?? 0) ?></td>
            <td><?= (int)($matchStats['away']['corners'] ?? 0) ?></td>
        </tr>
        <tr>
            <td>Free Kicks</td>
            <td><?= (int)($matchStats['home']['free_kicks'] ?? 0) ?></td>
            <td><?= (int)($matchStats['away']['free_kicks'] ?? 0) ?></td>
        </tr>
        <tr>
            <td>Penalties</td>
            <td><?= (int)($matchStats['home']['penalties'] ?? 0) ?></td>
            <td><?= (int)($matchStats['away']['penalties'] ?? 0) ?></td>
        </tr>
        <tr>
            <td>Yellow Cards</td>
            <td><?= (int)($matchStats['home']['yellow_cards'] ?? 0) ?></td>
            <td><?= (int)($matchStats['away']['yellow_cards'] ?? 0) ?></td>
        </tr>
        <tr>
            <td>Red Cards</td>
            <td><?= (int)($matchStats['home']['red_cards'] ?? 0) ?></td>
            <td><?= (int)($matchStats['away']['red_cards'] ?? 0) ?></td>
        </tr>
        <!-- Exploited Event Types (Team Level) -->
        <tr><th colspan="3" style="background:#e3e6f3;">Exploited Event Types</th></tr>
        <?php
        // Use derivedData['derived']['by_type_team'] for event types
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
        <tr>
            <td><?= $label ?></td>
            <td><?= (int)($derivedData['derived']['by_type_team'][$key]['home'] ?? 0) ?></td>
            <td><?= (int)($derivedData['derived']['by_type_team'][$key]['away'] ?? 0) ?></td>
        </tr>
        <?php endforeach; ?>
        <tr>
            <td>Set Pieces (Corners+FK+Pens)</td>
            <td><?= (int)($matchStats['home']['corners'] ?? 0) + (int)($matchStats['home']['free_kicks'] ?? 0) + (int)($matchStats['home']['penalties'] ?? 0) ?></td>
            <td><?= (int)($matchStats['away']['corners'] ?? 0) + (int)($matchStats['away']['free_kicks'] ?? 0) + (int)($matchStats['away']['penalties'] ?? 0) ?></td>
        </tr>

        <!-- Efficiency & Discipline Section -->
        <h2>Efficiency & Discipline</h2>
        <table>
            <tr><th>Metric</th><th><?= htmlspecialchars($homeTeam['name']) ?></th><th><?= htmlspecialchars($awayTeam['name']) ?></th></tr>
            <?php
            // Gather values
            $hShots = (int)($matchStats['home']['shots'] ?? 0);
            $aShots = (int)($matchStats['away']['shots'] ?? 0);
            $hOnTarget = (int)($matchStats['home']['shots_on_target'] ?? ($derivedData['derived']['by_type_team']['shot_on_target']['home'] ?? 0));
            $aOnTarget = (int)($matchStats['away']['shots_on_target'] ?? ($derivedData['derived']['by_type_team']['shot_on_target']['away'] ?? 0));
            $hGoals = (int)($matchStats['home']['goals'] ?? 0);
            $aGoals = (int)($matchStats['away']['goals'] ?? 0);
            $hCards = (int)($matchStats['home']['yellow_cards'] ?? 0) + (int)($matchStats['home']['red_cards'] ?? 0);
            $aCards = (int)($matchStats['away']['yellow_cards'] ?? 0) + (int)($matchStats['away']['red_cards'] ?? 0);
            $hFouls = (int)($derivedData['derived']['by_type_team']['foul']['home'] ?? 0);
            $aFouls = (int)($derivedData['derived']['by_type_team']['foul']['away'] ?? 0);
            $hSetPieces = (int)($matchStats['home']['corners'] ?? 0) + (int)($matchStats['home']['free_kicks'] ?? 0) + (int)($matchStats['home']['penalties'] ?? 0);
            $aSetPieces = (int)($matchStats['away']['corners'] ?? 0) + (int)($matchStats['away']['free_kicks'] ?? 0) + (int)($matchStats['away']['penalties'] ?? 0);
            // Shot Accuracy
            $hShotAcc = $hShots > 0 ? round($hOnTarget / $hShots * 100, 1) : '—';
            $aShotAcc = $aShots > 0 ? round($aOnTarget / $aShots * 100, 1) : '—';
            // Shot Conversion
            $hShotConv = $hShots > 0 ? round($hGoals / $hShots * 100, 1) : '—';
            $aShotConv = $aShots > 0 ? round($aGoals / $aShots * 100, 1) : '—';
            // Goals per Shot on Target
            $hGPSOT = $hOnTarget > 0 ? round($hGoals / $hOnTarget, 2) : '—';
            $aGPSOT = $aOnTarget > 0 ? round($aGoals / $aOnTarget, 2) : '—';
            // Fouls per Card
            $hFPC = $hCards > 0 ? round($hFouls / $hCards, 2) : '—';
            $aFPC = $aCards > 0 ? round($aFouls / $aCards, 2) : '—';
            // Set Pieces per Shot
            $hSPPS = $hShots > 0 ? round($hSetPieces / $hShots, 2) : '—';
            $aSPPS = $aShots > 0 ? round($aSetPieces / $aShots, 2) : '—';
            ?>
            <tr><td>Shot Accuracy (%)</td><td><?= $hShotAcc ?></td><td><?= $aShotAcc ?></td></tr>
            <tr><td>Shot Conversion (%)</td><td><?= $hShotConv ?></td><td><?= $aShotConv ?></td></tr>
            <tr><td>Goals per Shot on Target</td><td><?= $hGPSOT ?></td><td><?= $aGPSOT ?></td></tr>
            <tr><td>Fouls per Card</td><td><?= $hFPC ?></td><td><?= $aFPC ?></td></tr>
            <tr><td>Set Pieces per Shot</td><td><?= $hSPPS ?></td><td><?= $aSPPS ?></td></tr>
        </table>
    </table>

    <table class="subsection" style="width:100%; margin-top:1.2em; border:0;">
        <tr>
            <td style="border:0; padding:0;">
                <ul class="event-list" style="margin:0; padding:0;">
                    <?php foreach (($overview['events']['home_goals'] ?? []) as $goal): ?>
                        <li>Home Goal: <?= htmlspecialchars($goal['player'] ?? 'Unknown') ?> <?= htmlspecialchars($goal['minute'] ?? '') ?>'</li>
                    <?php endforeach; ?>
                    <?php foreach (($overview['events']['away_goals'] ?? []) as $goal): ?>
                        <li>Away Goal: <?= htmlspecialchars($goal['player'] ?? 'Unknown') ?> <?= htmlspecialchars($goal['minute'] ?? '') ?>'</li>
                    <?php endforeach; ?>
                </ul>
            </td>
        </tr>
    </table>

    <tr><td colspan="2" style="border:0; padding:0;"><div class="divider"></div></td></tr>
    <h2>Player Performance</h2>
    <h3>Starting XI</h3>
    <!-- Expanded Player Performance Table -->
    <?php
    // Build substitution lookup for minutes played
    $subOffByMatchPlayerId = [];
    foreach ($substitutions as $sub) {
        if (!empty($sub['player_off_match_player_id'])) {
            $subOffByMatchPlayerId[$sub['player_off_match_player_id']] = $sub;
        }
    }
    $matchDuration = (int)($match['duration_minutes'] ?? 90);
    // Determine if red cards present
    $hasRedCards = ((int)($matchStats['home']['red_cards'] ?? 0) + (int)($matchStats['away']['red_cards'] ?? 0)) > 0;
    ?>
    <table>
        <tr>
            <th>#</th><th>Player</th><th>Position</th><th>Goals</th><th>Yellow Cards</th><?php if ($hasRedCards): ?><th>Red Cards</th><?php endif; ?><th>Minutes Played</th>
        </tr>
        <?php foreach (($playerPerformance['starting_xi'] ?? []) as $player): ?>
            <?php
            $mpid = $player['match_player_id'] ?? null;
            // Minutes played: subbed off minute or full match
            $minutes = '—';
            if ($mpid && isset($subOffByMatchPlayerId[$mpid])) {
                $minutes = (int)($subOffByMatchPlayerId[$mpid]['minute'] ?? 0);
            } elseif ($mpid) {
                $minutes = $matchDuration;
            }
            $goals = (int)($player['goals'] ?? 0);
            ?>
            <tr>
                <td><?= htmlspecialchars($player['shirt_number'] ?? '-') ?></td>
                <td><?= htmlspecialchars($player['name'] ?? '-') ?></td>
                <td><?= htmlspecialchars($player['position'] ?? '-') ?></td>
                <td><?= $goals > 0 ? $goals : '' ?></td>
                <td><?= ($player['yellow_cards'] ?? 0 ? (int)$player['yellow_cards'] : '') ?></td>
                <?php if ($hasRedCards): ?><td><?= ($player['red_cards'] ?? 0 ? (int)$player['red_cards'] : '') ?></td><?php endif; ?>
                <td><?= $minutes !== '' ? $minutes : '—' ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

        <!-- Shot Map Placeholder Section -->
        <h2>Shot Map</h2>
        <table style="width:100%; border:2px dashed #bbb; height:180px; background:#f9f9f9; margin-bottom:2em; text-align:center; vertical-align:middle;">
            <tr style="height:180px;">
                <td style="text-align:center; vertical-align:middle; color:#888; font-size:1.2em;">Shot map visualization coming soon</td>
            </tr>
        </table>
    <h3>Substitutes</h3>
    <?php
    // Determine if any goals, yellow, or red cards for subs columns
    $subsGoals = 0; $subsYellows = 0; $subsReds = 0;
    foreach (($playerPerformance['substitutes'] ?? []) as $player) {
        $subsGoals += (int)($player['goals'] ?? 0);
        $subsYellows += (int)($player['yellow_cards'] ?? 0);
        $subsReds += (int)($player['red_cards'] ?? 0);
    }
    $showGoals = $subsGoals > 0;
    $showYellows = $subsYellows > 0;
    $showReds = $subsReds > 0;
    ?>
    <table>
        <tr>
            <th>#</th><th>Player</th><th>Position</th><?php if ($showGoals): ?><th>Goals</th><?php endif; ?><?php if ($showYellows): ?><th>Yellow Cards</th><?php endif; ?><?php if ($showReds): ?><th>Red Cards</th><?php endif; ?><th>Substitution</th>
        </tr>
        <?php
        // Build a map of match_player_id => substitution event for quick lookup
        $subEventByMatchPlayerId = [];
        foreach ($substitutions as $sub) {
            if (!empty($sub['player_on_match_player_id'])) {
                $subEventByMatchPlayerId[$sub['player_on_match_player_id']] = $sub;
            }
        }
        foreach (($playerPerformance['substitutes'] ?? []) as $player) {
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
                        On for <?= htmlspecialchars($sub['player_off_name'] ?? '-') ?> (<?= (int)($sub['minute'] ?? 0) ?>')
                    <?php else: ?>
                        <em class="reducetextsize3">Unused</em>
                    <?php endif; ?>
                </td>
            </tr>
        <?php }
        ?>
    </table>
</table>
</body>
</html>
