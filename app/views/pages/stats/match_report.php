<?php
// match_report.php - Standalone printable match report for PDF export
// Usage: /app/views/pages/stats/match_report.php?match_id=XX&club_id=YY

if (!isset($match_for_report)) {
    require_once __DIR__ . '/../../../config/config.php';
    require_once __DIR__ . '/../../../lib/db.php';
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
    require_once __DIR__ . '/../../../lib/db.php';
    require_once __DIR__ . '/../../../lib/StatsService.php';
    require_once __DIR__ . '/../../../lib/player_repository.php';
    require_once __DIR__ . '/../../../lib/team_repository.php';
}

$statsService = new StatsService($clubId);
$matchStats = $statsService->getMatchStats($matchId);
$derivedData = $statsService->getMatchDerivedData($matchId);
$teamPerformance = $statsService->getTeamPerformanceStats();
$playerPerformance = $statsService->getPlayerPerformanceForMatch($matchId);
$overview = [
    'events' => $statsService->getMatchEvents($matchId),
];
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

$derivedByType = $derivedData['derived']['by_type_team'] ?? [];
$homeShots = (int)($derivedByType['shot_on_target']['home'] ?? 0) + (int)($derivedByType['shot_off_target']['home'] ?? 0);
$awayShots = (int)($derivedByType['shot_on_target']['away'] ?? 0) + (int)($derivedByType['shot_off_target']['away'] ?? 0);

$homeShotsOnTarget = (int)($derivedByType['shot_on_target']['home'] ?? 0);
$awayShotsOnTarget = (int)($derivedByType['shot_on_target']['away'] ?? 0);

$homeShotsOffTarget = (int)($derivedByType['shot_off_target']['home'] ?? 0);
$awayShotsOffTarget = (int)($derivedByType['shot_off_target']['away'] ?? 0);

$shotOriginMarkers = ['home' => [], 'away' => []];
$shotTargetMarkers = ['home' => [], 'away' => []];
if ($matchId > 0) {
    $pdo = db();
    $shotStmt = $pdo->prepare('
        SELECT
            e.team_side,
            e.shot_origin_x,
            e.shot_origin_y,
            e.shot_target_x,
            e.shot_target_y,
            et.type_key,
            et.label
        FROM events e
        JOIN event_types et ON et.id = e.event_type_id
        WHERE e.match_id = ?
          AND et.type_key IN ("shot", "goal")
          AND (e.shot_origin_x IS NOT NULL OR e.shot_target_x IS NOT NULL)
        ORDER BY e.match_second ASC
    ');
    $shotStmt->execute([$matchId]);
    $shotRows = $shotStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($shotRows as $row) {
        $teamSide = ($row['team_side'] ?? '') === 'away' ? 'away' : 'home';
        $typeKey = strtolower(trim((string)($row['type_key'] ?? '')));
        $typeLabel = strtolower(trim((string)($row['label'] ?? '')));
        $isGoal = (strpos($typeKey, 'goal') !== false || strpos($typeLabel, 'goal') !== false);
        if ($row['shot_origin_x'] !== null && $row['shot_origin_y'] !== null) {
            $shotOriginMarkers[$teamSide][] = [
                'x' => (float)$row['shot_origin_x'],
                'y' => (float)$row['shot_origin_y'],
                'is_goal' => $isGoal,
            ];
        }
        if ($row['shot_target_x'] !== null && $row['shot_target_y'] !== null) {
            $shotTargetMarkers[$teamSide][] = [
                'x' => (float)$row['shot_target_x'],
                'y' => (float)$row['shot_target_y'],
                'is_goal' => $isGoal,
            ];
        }
    }
}

function build_shot_origin_svg(array $markers, string $teamColor): string
{
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid meet">'
        . '<rect x="5" y="5" width="90" height="70" stroke="#18191a" stroke-width="1" fill="none"/>'
        . '<rect x="20" y="5" width="60" height="30" stroke="#18191a" stroke-width="1" fill="none"/>'
        . '<rect x="35" y="5" width="30" height="15" stroke="#18191a" stroke-width="1" fill="none"/>'
        . '<circle cx="50" cy="28" r="1.5" fill="#18191a"/>'
        . '<path d="M35 35 A15 15 0 0 0 65 35" stroke="#18191a" fill="none"/>';
    foreach ($markers as $marker) {
        $x = max(0, min(1, (float)($marker['x'] ?? 0))) * 100;
        $y = max(0, min(1, (float)($marker['y'] ?? 0))) * 100;
        $fill = !empty($marker['is_goal']) ? '#22c55e' : $teamColor;
        $svg .= sprintf('<circle cx="%.2f" cy="%.2f" r="2.4" fill="%s" opacity="0.85" />', $x, $y, $fill);
    }
    $svg .= '</svg>';
    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

function build_shot_target_svg(array $markers, string $teamColor): string
{
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 60">'
        . '<line x1="18" y1="8" x2="18" y2="54" stroke="#18191a" stroke-width="3"/>'
        . '<line x1="102" y1="8" x2="102" y2="54" stroke="#18191a" stroke-width="3"/>'
        . '<line x1="18" y1="8" x2="102" y2="8" stroke="#18191a" stroke-width="3"/>';
    for ($x = 22; $x <= 94; $x += 6) {
        $svg .= sprintf('<line x1="%d" y1="12" x2="%d" y2="54" stroke="#72777f" stroke-width="0.2"/>', $x, $x);
    }
    for ($y = 12; $y <= 54; $y += 6) {
        $svg .= sprintf('<line x1="22" y1="%d" x2="98" y2="%d" stroke="#72777f" stroke-width="0.2"/>', $y, $y);
    }
    foreach ($markers as $marker) {
        $x = max(0, min(1, (float)($marker['x'] ?? 0))) * 120;
        $y = max(0, min(1, (float)($marker['y'] ?? 0))) * 60;
        if (!empty($marker['is_goal'])) {
            $svg .= sprintf('<circle cx="%.2f" cy="%.2f" r="2.8" fill="#22c55e" stroke="#18191a" stroke-width="0.4" />', $x, $y);
        } else {
            $svg .= sprintf('<circle cx="%.2f" cy="%.2f" r="2.8" fill="%s" opacity="0.85" stroke="#18191a" stroke-width="0.4" />', $x, $y, $teamColor);
        }
    }
    $svg .= '</svg>';
    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

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
        td { padding: 4px; vertical-align: middle; }
        body { font-size: 13px;font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
        .marker-font { font-family: "DejaVu Sans", "Arial Unicode MS", Arial, sans-serif; font-size: 11px; }
        h1, h2, h3 { margin: 1em 0 0.5em; font-family: inherit; }
        .divider { height: 1px; margin: 1em 0; background: #ccc; }
        .section-table td { border-bottom: 1px solid #e3e6f3; }
        .match-score td { border: none; }
        .info-table td { border: none; }
        .event-list { margin: 0; padding-left: 1.1em; }
        .center-cols th:nth-child(n+2),
        .center-cols td:nth-child(n+2) { text-align: center !important; }
        .starting-xi-table th:nth-child(3),
        .starting-xi-table th:nth-child(4),
        .starting-xi-table th:nth-child(5),
        .starting-xi-table th:nth-child(6),
        .starting-xi-table td:nth-child(3),
        .starting-xi-table td:nth-child(4),
        .starting-xi-table td:nth-child(5),
        .starting-xi-table td:nth-child(6) { text-align: center; }
        .svg-block { display: block; }

        /* Tailwind-style utility classes used in this template */
        .text-xl { font-size: 1.25rem; }
        .text-lg { font-size: 1.125rem; }
        .text-base { font-size: 1rem; }
        .text-sm { font-size: 0.875rem; }
        .text-xs { font-size: 0.75rem; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-text-primary { color: #111827; }
        .text-text-muted { color: #6b7280; }
        .text-gray-700 { color: #374151; }
        .font-semibold { font-weight: 600; }
        .font-medium { font-weight: 500; }
        .uppercase { text-transform: uppercase; }

        .w-full { width: 100%; }
        .min-w-full { min-width: 100%; }
        .w-1\/3 { width: 33.3333%; }

        .my-6 { margin-top: 1.5rem; margin-bottom: 1.5rem; }
        .my-8 { margin-top: 2rem; margin-bottom: 2rem; }
        .mb-2 { margin-bottom: 0.5rem; }
        .mb-4 { margin-bottom: 1rem; }
        .px-4 { padding-left: 1rem; padding-right: 1rem; }
        .px-6 { padding-left: 1.5rem; padding-right: 1.5rem; }
        .py-2 { padding-top: 0.5rem; padding-bottom: 0.5rem; }
        .py-3 { padding-top: 0.75rem; padding-bottom: 0.75rem; }
        .pl-6 { padding-left: 1.5rem; }
        .pr-2 { padding-right: 0.5rem; }

        .list-disc { list-style-type: disc; }
        .rounded-xl { border-radius: 0.75rem; }
        .overflow-hidden { overflow: hidden; }

        .bg-bg-tertiary { background: #f9fafb; }
        .bg-bg-secondary { background: #eef1f7; }
        .border-b { border-bottom: 1px solid #e3e6f3; }
        .border-border-soft { border-color: #e3e6f3; }
        .transition-colors { transition: color 0.2s ease, background-color 0.2s ease; }
        .hover\:bg-bg-secondary\/60:hover { background: #f3f4f6; }
    </style>
</head>
<body>

<!-- Prominent Scoreline with improved hierarchy -->
<h1 style="font-size:2.5em; text-align:center; margin-bottom:0.2em; letter-spacing:1px;">Match Report</h1>
<table class="match-score" style="margin-bottom: 1.2em; width:100%;">
    <tr>
        <td style="text-align:left; font-weight:700; font-size:1.2em; min-width:160px;">
            <?= htmlspecialchars($homeTeam['name']) ?>
        </td>
        <td style="text-align:center; font-size:3.2em; font-weight:900; letter-spacing:2px; background:#f3f4f6; border-radius:12px; border:2px solid #e3e6f3; padding:0.2em 0.7em;">
            <?= (int)($matchStats['home']['goals'] ?? 0) ?> <span style="color:#888; font-size:0.7em; font-weight:400;">:</span> <?= (int)($matchStats['away']['goals'] ?? 0) ?>
        </td>
        <td style="text-align:right; font-weight:700; font-size:1.2em; min-width:160px;">
            <?= htmlspecialchars($awayTeam['name']) ?>
        </td>
    </tr>
</table>

<!-- Match Summary / Key Takeaways (auto-generated, coach-friendly) -->
<!-- This section gives coaches a quick, factual summary of the match using existing stats. -->
<div class="match-summary my-6 px-6" style="margin-bottom:2.2em;">
    <h2 class="text-xl font-semibold mb-2" style="font-size:1.25em; margin-bottom:0.5em;">Match Summary / Key Takeaways</h2>
    <ul class="list-disc pl-6 text-base" style="font-size:1.05em;">
        <?php
        // --- Generate summary points using existing data ---
        $homeShots = $matchStats['home']['shots'] ?? $matchStats['home']['shots_total'] ?? 0;
        $awayShots = $matchStats['away']['shots'] ?? $matchStats['away']['shots_total'] ?? 0;
        $homeOnTarget = $matchStats['home']['shots_on_target'] ?? 0;
        $awayOnTarget = $matchStats['away']['shots_on_target'] ?? 0;
        $homeGoals = $matchStats['home']['goals'] ?? $match['home_score'] ?? 0;
        $awayGoals = $matchStats['away']['goals'] ?? $match['away_score'] ?? 0;
        $homeSetPieceGoals = $derivedData['home']['set_piece_goals'] ?? 0;
        $awaySetPieceGoals = $derivedData['away']['set_piece_goals'] ?? 0;
        $homeFouls = $matchStats['home']['fouls'] ?? 0;
        $awayFouls = $matchStats['away']['fouls'] ?? 0;
        $homeYellows = $matchStats['home']['yellow_cards'] ?? 0;
        $awayYellows = $matchStats['away']['yellow_cards'] ?? 0;
        $homeReds = $matchStats['home']['red_cards'] ?? 0;
        $awayReds = $matchStats['away']['red_cards'] ?? 0;
        $homeConversion = $homeShots ? round(100 * $homeGoals / $homeShots, 1) : 0;
        $awayConversion = $awayShots ? round(100 * $awayGoals / $awayShots, 1) : 0;
        $setPieceReliance = ($homeSetPieceGoals + $awaySetPieceGoals) > 0;
        $disciplineMention = ($homeYellows + $awayYellows + $homeReds + $awayReds) > 0;
        // 1. Shot dominance/efficiency
        if ($homeShots > $awayShots + 3) {
            echo '<li>' . htmlspecialchars($homeTeam['name']) . ' created more chances but conversion rate was ' . ($homeConversion > $awayConversion ? 'higher' : 'lower') . ' than ' . htmlspecialchars($awayTeam['name']) . '.</li>';
        } elseif ($awayShots > $homeShots + 3) {
            echo '<li>' . htmlspecialchars($awayTeam['name']) . ' created more chances but conversion rate was ' . ($awayConversion > $homeConversion ? 'higher' : 'lower') . ' than ' . htmlspecialchars($homeTeam['name']) . '.</li>';
        } else {
            echo '<li>Both teams had a similar number of shots. Finishing was ' . ($homeConversion == $awayConversion ? 'even' : (($homeConversion > $awayConversion) ? 'more clinical for ' . htmlspecialchars($homeTeam['name']) : 'more clinical for ' . htmlspecialchars($awayTeam['name']))) . '.</li>';
        }
        // 2. Set-piece reliance
        if ($setPieceReliance) {
            $setPieceSummary = [];
            if ($homeSetPieceGoals > 0) $setPieceSummary[] = htmlspecialchars($homeTeam['name']) . ' scored ' . $homeSetPieceGoals . ' from set pieces';
            if ($awaySetPieceGoals > 0) $setPieceSummary[] = htmlspecialchars($awayTeam['name']) . ' scored ' . $awaySetPieceGoals . ' from set pieces';
            echo '<li>' . implode('; ', $setPieceSummary) . '.</li>';
        } else {
            echo '<li>No goals from set pieces for either team.</li>';
        }
        // 3. Discipline
        if ($disciplineMention) {
            $cardSummary = [];
            if ($homeYellows > 0) $cardSummary[] = htmlspecialchars($homeTeam['name']) . ' received ' . $homeYellows . ' yellow' . ($homeYellows > 1 ? 's' : '');
            if ($awayYellows > 0) $cardSummary[] = htmlspecialchars($awayTeam['name']) . ' received ' . $awayYellows . ' yellow' . ($awayYellows > 1 ? 's' : '');
            if ($homeReds > 0) $cardSummary[] = htmlspecialchars($homeTeam['name']) . ' had ' . $homeReds . ' red card' . ($homeReds > 1 ? 's' : '');
            if ($awayReds > 0) $cardSummary[] = htmlspecialchars($awayTeam['name']) . ' had ' . $awayReds . ' red card' . ($awayReds > 1 ? 's' : '');
            echo '<li>Discipline: ' . implode('; ', $cardSummary) . '.</li>';
        } else {
            echo '<li>No cards shown in the match.</li>';
        }
        // 4. Clinical finishing
        if ($homeConversion >= 30) {
            echo '<li>' . htmlspecialchars($homeTeam['name']) . ' were clinical in front of goal (' . $homeConversion . '% conversion).</li>';
        } elseif ($awayConversion >= 30) {
            echo '<li>' . htmlspecialchars($awayTeam['name']) . ' were clinical in front of goal (' . $awayConversion . '% conversion).</li>';
        } else {
            echo '<li>Both teams struggled to convert chances into goals.</li>';
        }
        // 5. Fouls
        if ($homeFouls > $awayFouls + 3) {
            echo '<li>' . htmlspecialchars($homeTeam['name']) . ' committed more fouls than ' . htmlspecialchars($awayTeam['name']) . '.</li>';
        } elseif ($awayFouls > $homeFouls + 3) {
            echo '<li>' . htmlspecialchars($awayTeam['name']) . ' committed more fouls than ' . htmlspecialchars($homeTeam['name']) . '.</li>';
        }
        ?>
    </ul>
</div>
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


<!-- Combined Stat Table: Attacking, Set Pieces, Discipline & Control -->
<div class="combined-section my-8" style="margin-bottom:2em;">
    <h2 class="text-lg font-semibold mb-2" style="font-size:1.1em; margin-bottom:0.4em;">Match Statistics</h2>
   <table class="min-w-full bg-bg-tertiary text-text-primary text-xs rounded-xl overflow-hidden center-cols">
        <thead>
            <tr class="bg-bg-secondary text-text-muted uppercase font-semibold text-xs">
                <th class="w-1/3 px-4 py-2 text-left">Category</th>
                <th class="w-1/3 px-4 py-2 text-center"><?= htmlspecialchars($homeTeam['name']) ?></th>
                <th class="w-1/3 px-4 py-2 text-center"><?= htmlspecialchars($awayTeam['name']) ?></th>
            </tr>
        </thead>
        <tbody>
        <?php
        // Define all rows in order: Attacking, Set Pieces, Discipline
        $rows = [
            // Attacking
            ['section' => 'Attacking', 'label' => 'Goals', 'home' => (int)($matchStats['home']['goals'] ?? 0), 'away' => (int)($matchStats['away']['goals'] ?? 0)],
            ['section' => 'Attacking', 'label' => 'Shots', 'home' => $homeShots, 'away' => $awayShots],
            ['section' => 'Attacking', 'label' => 'On Target', 'home' => $homeShotsOnTarget, 'away' => $awayShotsOnTarget],
            ['section' => 'Attacking', 'label' => 'Off Target', 'home' => $homeShotsOffTarget, 'away' => $awayShotsOffTarget],
            // Set Pieces
            ['section' => 'Set Pieces', 'label' => 'Corners', 'home' => (int)($matchStats['home']['corners'] ?? 0), 'away' => (int)($matchStats['away']['corners'] ?? 0)],
            ['section' => 'Set Pieces', 'label' => 'Free Kicks', 'home' => (int)($matchStats['home']['free_kicks'] ?? 0), 'away' => (int)($matchStats['away']['free_kicks'] ?? 0)],
            ['section' => 'Set Pieces', 'label' => 'Penalties', 'home' => (int)($matchStats['home']['penalties'] ?? 0), 'away' => (int)($matchStats['away']['penalties'] ?? 0)],
            // Discipline & Control
            ['section' => 'Discipline & Control', 'label' => 'Yellow Cards', 'home' => (int)($matchStats['home']['yellow_cards'] ?? 0), 'away' => (int)($matchStats['away']['yellow_cards'] ?? 0)],
            ['section' => 'Discipline & Control', 'label' => 'Red Cards', 'home' => (int)($matchStats['home']['red_cards'] ?? 0), 'away' => (int)($matchStats['away']['red_cards'] ?? 0)],
            ['section' => 'Discipline & Control', 'label' => 'Fouls', 'home' => (int)($matchStats['home']['fouls'] ?? 0), 'away' => (int)($matchStats['away']['fouls'] ?? 0)],
        ];
        $lastSection = '';
        foreach ($rows as $row) {
            if ($row['section'] !== $lastSection) {
                echo '<tr style="background:#eef1f7;"><td colspan="3" class="font-semibold text-left px-4 py-2 text-center" style="font-size:1em;">' . htmlspecialchars($row['section']) . '</td></tr>';
                $lastSection = $row['section'];
            }
            echo '<tr>';
            echo '<td class="px-4 py-2">' . htmlspecialchars($row['label']) . '</td>';
            echo '<td class="px-4 py-2">' . $row['home'] . '</td>';
            echo '<td class="px-4 py-2">' . $row['away'] . '</td>';
            echo '</tr>';
        }
        ?>
        </tbody>
    </table>
</div>
<div style="page-break-before: always;"></div>
<h2>Exploited Event Types</h2>
<table class="min-w-full bg-bg-tertiary text-text-primary text-xs rounded-xl overflow-hidden center-cols">
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
<table class="min-w-full bg-bg-tertiary text-text-primary text-xs rounded-xl overflow-hidden center-cols">
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

<div style="page-break-before: always;"></div>
<!-- Shot Maps with captions and legend for clarity -->
<h2>Shot Maps</h2>
<table style="width:100%; margin-bottom: 1em;">
    <tr>
        <td style="width:50%; vertical-align: top; padding-right: 8px;">
            <div style="font-weight:600; margin-bottom:6px;"><?= htmlspecialchars($homeTeam['name']) ?> Shot Map</div>
            <div style="margin-bottom:6px; font-size:11px; color:#666;">Shot origins</div>
            <img class="svg-block" style="width:100%; height:auto;" src="<?= htmlspecialchars(build_shot_origin_svg($shotOriginMarkers['home'], '#ef4444')) ?>" alt="Home shot origins" />
            <div style="margin:6px 0 6px; font-size:11px; color:#666;">Shot targets</div>
            <img class="svg-block" style="width:100%;" src="<?= htmlspecialchars(build_shot_target_svg($shotTargetMarkers['home'], '#ef4444')) ?>" alt="Home shot targets" />
            <div style="margin-top:6px; font-size:11px; color:#444; text-align:center;">All shots taken by <?= htmlspecialchars($homeTeam['name']) ?>. Circles = shots, filled = goals.</div>
        </td>
        <td style="width:50%; vertical-align: top; padding-left: 8px;">
            <div style="font-weight:600; margin-bottom:6px;"><?= htmlspecialchars($awayTeam['name']) ?> Shot Map</div>
            <div style="margin-bottom:6px; font-size:11px; color:#666;">Shot origins</div>
            <img class="svg-block" style="width:100%; height:auto;" src="<?= htmlspecialchars(build_shot_origin_svg($shotOriginMarkers['away'], '#ef4444')) ?>" alt="Away shot origins" />
            <div style="margin:6px 0 6px; font-size:11px; color:#666;">Shot targets</div>
            <img class="svg-block" style="width:100%;" src="<?= htmlspecialchars(build_shot_target_svg($shotTargetMarkers['away'], '#ef4444')) ?>" alt="Away shot targets" />
            <div style="margin-top:6px; font-size:11px; color:#444; text-align:center;">All shots taken by <?= htmlspecialchars($awayTeam['name']) ?>. Circles = shots, filled = goals.</div>
        </td>
    </tr>
</table>
<!-- Simple legend for shot map -->
<div style="display:flex; justify-content:center; align-items:center; gap:18px; margin-bottom:1.5em;">
    <span style="display:inline-block;width:16px;height:16px;border-radius:50%;border:1px solid #333;background:#ef4444;margin-right:4px;"></span> Shot
    <span style="display:inline-block;width:16px;height:16px;border-radius:50%;border:1px solid #333;background:#22c55e;margin-left:16px;margin-right:4px;"></span> Goal
</div>

<div style="page-break-before: always;"></div>
<h2>Match Events</h2>
<table class="section-table w-full text-center mb-4" style="margin-bottom:1em;">
    <thead>
        <tr style="background:#eef1f7;">
            <th class="px-4 py-2 text-left">Team</th>
            <th class="px-4 py-2 text-left">Player</th>
            <th class="px-4 py-2 text-left">Event</th>
            <th class="px-4 py-2 text-center">Minute</th>
        </tr>
    </thead>
    <tbody>
        <?php
        // Helper to render a row
        function render_event_row($team, $teamColor, $player, $event, $minute) {
            echo '<tr>';
            echo '<td class="px-4 py-2 text-left" style="font-weight:600;color:' . $teamColor . ';">' . htmlspecialchars($team) . '</td>';
            echo '<td class="px-4 py-2 text-left">' . htmlspecialchars($player ?? 'Unknown') . '</td>';
            echo '<td class="px-4 py-2 text-left">' . htmlspecialchars($event) . '</td>';
            echo '<td class="px-4 py-2 text-center">' . htmlspecialchars($minute) . "'</td>";
            echo '</tr>';
        }
        // Goals
        foreach (($overview['events']['home_goals'] ?? []) as $goal) {
            $minute = isset($goal['match_second']) ? (int)floor((int)($goal['match_second'] ?? 0) / 60) : htmlspecialchars($goal['minute'] ?? '');
            render_event_row($homeTeam['name'], '#ef4444', $goal['player'], 'Goal', $minute);
        }
        foreach (($overview['events']['away_goals'] ?? []) as $goal) {
            $minute = isset($goal['match_second']) ? (int)floor((int)($goal['match_second'] ?? 0) / 60) : htmlspecialchars($goal['minute'] ?? '');
            render_event_row($awayTeam['name'], '#3b82f6', $goal['player'], 'Goal', $minute);
        }
        // Yellow Cards
        foreach (($overview['events']['home_yellow_cards'] ?? []) as $ev) {
            $minute = isset($ev['match_second']) ? (int)floor((int)($ev['match_second'] ?? 0) / 60) : htmlspecialchars($ev['minute'] ?? '');
            render_event_row($homeTeam['name'], '#ef4444', $ev['player'], 'Yellow Card', $minute);
        }
        foreach (($overview['events']['away_yellow_cards'] ?? []) as $ev) {
            $minute = isset($ev['match_second']) ? (int)floor((int)($ev['match_second'] ?? 0) / 60) : htmlspecialchars($ev['minute'] ?? '');
            render_event_row($awayTeam['name'], '#3b82f6', $ev['player'], 'Yellow Card', $minute);
        }
        // Red Cards
        foreach (($overview['events']['home_red_cards'] ?? []) as $ev) {
            $minute = isset($ev['match_second']) ? (int)floor((int)($ev['match_second'] ?? 0) / 60) : htmlspecialchars($ev['minute'] ?? '');
            render_event_row($homeTeam['name'], '#ef4444', $ev['player'], 'Red Card', $minute);
        }
        foreach (($overview['events']['away_red_cards'] ?? []) as $ev) {
            $minute = isset($ev['match_second']) ? (int)floor((int)($ev['match_second'] ?? 0) / 60) : htmlspecialchars($ev['minute'] ?? '');
            render_event_row($awayTeam['name'], '#3b82f6', $ev['player'], 'Red Card', $minute);
        }
        // Substitutions
        foreach (($overview['events']['home_substitutions'] ?? []) as $ev) {
            $minute = isset($ev['match_second']) ? (int)floor((int)($ev['match_second'] ?? 0) / 60) : htmlspecialchars($ev['minute'] ?? '');
            $player = $ev['player_on'] ?? $ev['player'] ?? 'Unknown';
            render_event_row($homeTeam['name'], '#ef4444', $player, 'Substitution', $minute);
        }
        foreach (($overview['events']['away_substitutions'] ?? []) as $ev) {
            $minute = isset($ev['match_second']) ? (int)floor((int)($ev['match_second'] ?? 0) / 60) : htmlspecialchars($ev['minute'] ?? '');
            $player = $ev['player_on'] ?? $ev['player'] ?? 'Unknown';
            render_event_row($awayTeam['name'], '#3b82f6', $player, 'Substitution', $minute);
        }
        // Other event types (corners, penalties, fouls, etc.)
        $otherTypes = [
            'home_corners' => ['label' => 'Corner', 'team' => 'home', 'color' => '#ef4444'],
            'away_corners' => ['label' => 'Corner', 'team' => 'away', 'color' => '#3b82f6'],
            'home_penalties' => ['label' => 'Penalty', 'team' => 'home', 'color' => '#ef4444'],
            'away_penalties' => ['label' => 'Penalty', 'team' => 'away', 'color' => '#3b82f6'],
            'home_fouls' => ['label' => 'Foul', 'team' => 'home', 'color' => '#ef4444'],
            'away_fouls' => ['label' => 'Foul', 'team' => 'away', 'color' => '#3b82f6'],
        ];
        foreach ($otherTypes as $key => $meta) {
            foreach (($overview['events'][$key] ?? []) as $ev) {
                $minute = isset($ev['match_second']) ? (int)floor((int)($ev['match_second'] ?? 0) / 60) : htmlspecialchars($ev['minute'] ?? '');
                render_event_row(
                    $meta['team'] === 'home' ? $homeTeam['name'] : $awayTeam['name'],
                    $meta['color'],
                    $ev['player'] ?? 'Unknown',
                    $meta['label'],
                    $minute
                );
            }
        }
        ?>
    </tbody>
</table>

<!-- Player Performance section with summary label and goal highlights -->
<h2>Player Performance</h2>
<div class="text-sm text-gray-700 text-center mb-2" style="margin-bottom:0.7em;">Minutes, goals and discipline summary</div>
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
<table class="min-w-full bg-bg-tertiary text-text-primary text-xs rounded-xl overflow-hidden starting-xi-table">
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
            $subSecond = (int)($subData['match_second'] ?? 0);
            $minutes = $subSecond > 0 ? (int)floor($subSecond / 60) : (int)($subData['minute'] ?? 0);
        } elseif ($mpid) {
            $minutes = $matchDuration;
        }
        $goals = (int)($player['goals'] ?? 0);
        ?>
        <tr class="border-b border-border-soft hover:bg-bg-secondary/60 transition-colors"<?= ($goals > 0 ? ' style="background:#e6ffe6;font-weight:bold;"' : '') ?>>
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
                    <span class="marker-font" style="color:green;font-weight:bold;">&#8594;</span>
                    On for <?= htmlspecialchars($sub['player_off_name'] ?? '-') ?> (<?php
                        $subSecond = (int)($sub['match_second'] ?? 0);
                        echo $subSecond > 0 ? (int)floor($subSecond / 60) : (int)($sub['minute'] ?? 0);
                    ?>')
                <?php else: ?>
                    <em>Unused</em>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
</body>
</html>
