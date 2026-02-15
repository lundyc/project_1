<?php
// Generate a PNG line chart for goal trend (goals per match) using GD

function build_goal_trend_png(array $goals, string $filePath) {
    $count = count($goals);
    if ($count < 2) return false;
    $w = 500; $h = 180; $pad = 32;
    $max = max($goals);
    $min = min($goals);
    $stepX = ($w - 2 * $pad) / ($count - 1);
    $im = imagecreatetruecolor($w, $h);
    $white = imagecolorallocate($im, 255,255,255);
    $gray = imagecolorallocate($im, 220, 224, 235);
    $border = imagecolorallocate($im, 163, 163, 163);
    $line = imagecolorallocate($im, 24, 25, 26);
    $fill = imagecolorallocate($im, 227, 230, 243);
    $dot = imagecolorallocate($im, 37, 99, 235);
    $axis = imagecolorallocate($im, 187, 187, 187);
    imagefilledrectangle($im, 0, 0, $w-1, $h-1, $white);
    imagerectangle($im, 0, 0, $w-1, $h-1, $border);
    // Draw axes
    imageline($im, $pad, $pad, $pad, $h-$pad, $axis);
    imageline($im, $pad, $h-$pad, $w-$pad, $h-$pad, $axis);
    // Points
    $points = [];
            foreach ($goals as $i => $g) {
        $x = $pad + $i * $stepX;
        $y = $pad + ($h - 2 * $pad) * (1 - ($g - $min) / ($max - $min));
        $points[] = [$x, $y];
    }

    // Area fill under line
    $polyFill = [];
    $polyFill[] = $pad; $polyFill[] = $h-$pad;
    foreach ($points as [$x, $y]) { $polyFill[] = $x; $polyFill[] = $y; }
    $polyFill[] = $w-$pad; $polyFill[] = $h-$pad;
    imagefilledpolygon($im, $polyFill, count($polyFill)/2, $fill);
    // Draw line

    for ($i=1; $i<$count; $i++) {
        imageline($im, $points[$i-1][0], $points[$i-1][1], $points[$i][0], $points[$i][1], $line);
    }

    // Draw dots
    foreach ($points as [$x, $y]) {
        imagefilledellipse($im, $x, $y, 8, 8, $dot);
        imageellipse($im, $x, $y, 8, 8, $line);
    }
    // Y axis labels
    $font = __DIR__ . '/../../../../vendor/dompdf/dompdf/lib/fonts/DejaVuSans.ttf';

    if (file_exists($font)) {
        imagettftext($im, 12, 0, 8, $h-$pad+16, $line, $font, $min);
        imagettftext($im, 12, 0, 8, $pad+12, $line, $font, $max);
    } else {
        imagestring($im, 3, 8, $h-$pad+4, $min, $line);
        imagestring($im, 3, 8, $pad-8, $max, $line);
    }

    imagepng($im, $filePath);
    imagedestroy($im);
    return true;
}

// Generate a simple SVG line chart for goal trend (goals per match)
function build_goal_trend_svg(array $goals, string $lineColor = '#18191a', string $fillColor = '#e3e6f3') {
    $count = count($goals);
    if ($count < 2) return '';
    $w = 320; $h = 80; $pad = 24;
    $max = max($goals);
    $min = min($goals);
    if ($max == $min) $max = $min + 1; // avoid div by zero
    $stepX = ($w - 2 * $pad) / ($count - 1);
    $points = [];
    foreach ($goals as $i => $g) {
        $x = $pad + $i * $stepX;
        $y = $pad + ($h - 2 * $pad) * (1 - ($g - $min) / ($max - $min));
        $points[] = [$x, $y];
    }
    // Polyline for the trend
    $polyline = 'M ' . implode(' L ', array_map(fn($p) => sprintf('%.1f %.1f', $p[0], $p[1]), $points));
    // Area fill under the line
    $area = 'M ' . sprintf('%.1f %.1f', $points[0][0], $h - $pad)
        . ' L ' . implode(' L ', array_map(fn($p) => sprintf('%.1f %.1f', $p[0], $p[1]), $points))
        . ' L ' . sprintf('%.1f %.1f', $points[$count-1][0], $h - $pad) . ' Z';
    // SVG
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $w . '" height="' . $h . '" viewBox="0 0 ' . $w . ' ' . $h . '">';
    $svg .= '<rect x="0" y="0" width="' . $w . '" height="' . $h . '" fill="#fff"/>';
    $svg .= '<polyline fill="none" stroke="#bbb" stroke-width="1" points="' . $pad . ',' . $pad . ' ' . $pad . ',' . ($h-$pad) . ' ' . ($w-$pad) . ',' . ($h-$pad) . ' ' . ($w-$pad) . ',' . $pad . '"/>';
    $svg .= '<path d="' . $area . '" fill="' . $fillColor . '" opacity="0.5"/>';
    $svg .= '<path d="' . $polyline . '" fill="none" stroke="' . $lineColor . '" stroke-width="2.2"/>';
    // Dots for each point
    foreach ($points as $i => [$x, $y]) {
        $svg .= '<circle cx="' . $x . '" cy="' . $y . '" r="3.2" fill="#2563eb" stroke="#18191a" stroke-width="0.7"/>';
    }
    // Y axis labels (min/max)
    $svg .= '<text x="4" y="' . ($h-$pad+6) . '" font-size="11" fill="#18191a">' . $min . '</text>';
    $svg .= '<text x="4" y="' . ($pad+4) . '" font-size="11" fill="#18191a">' . $max . '</text>';
    $svg .= '</svg>';
    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

function build_line_chart_svg(array $series, array $xLabels, string $title, string $yLabel, string $lineColor = '#111827', string $fillColor = '#e5e7eb') {
    $count = count($series);
    if ($count < 2) return '';
    $w = 520; $h = 220; $pad = 42;
    $plotW = $w - 2 * $pad;
    $plotH = $h - 2 * $pad;

    $max = max($series);
    $min = min($series);
    if ($max == $min) $max = $min + 1; // avoid div by zero

    $stepX = $plotW / ($count - 1);
    $points = [];
    foreach ($series as $i => $value) {
        $x = $pad + $i * $stepX;
        $y = $pad + $plotH * (1 - ($value - $min) / ($max - $min));
        $points[] = [$x, $y];
    }

    $polyline = 'M ' . implode(' L ', array_map(fn($p) => sprintf('%.1f %.1f', $p[0], $p[1]), $points));
    $area = 'M ' . sprintf('%.1f %.1f', $points[0][0], $h - $pad)
        . ' L ' . implode(' L ', array_map(fn($p) => sprintf('%.1f %.1f', $p[0], $p[1]), $points))
        . ' L ' . sprintf('%.1f %.1f', $points[$count-1][0], $h - $pad) . ' Z';

    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $w . '" height="' . $h . '" viewBox="0 0 ' . $w . ' ' . $h . '">';
    $svg .= '<rect x="0" y="0" width="' . $w . '" height="' . $h . '" fill="#fff"/>';

    $svg .= '<line x1="' . $pad . '" y1="' . $pad . '" x2="' . $pad . '" y2="' . ($h-$pad) . '" stroke="#cbd5e1" stroke-width="1"/>';
    $svg .= '<line x1="' . $pad . '" y1="' . ($h-$pad) . '" x2="' . ($w-$pad) . '" y2="' . ($h-$pad) . '" stroke="#cbd5e1" stroke-width="1"/>';

    $ticks = [$min, round(($min + $max) / 2, 1), $max];
    foreach ($ticks as $tVal) {
        $y = $pad + $plotH * (1 - ($tVal - $min) / ($max - $min));
        $svg .= '<line x1="' . $pad . '" y1="' . sprintf('%.1f', $y) . '" x2="' . ($w-$pad) . '" y2="' . sprintf('%.1f', $y) . '" stroke="#e5e7eb" stroke-width="1"/>';
        $svg .= '<text x="8" y="' . sprintf('%.1f', $y + 4) . '" font-size="11" fill="#111827">' . $tVal . '</text>';
    }

    $xTickIndexes = [0, (int)floor(($count - 1) / 2), $count - 1];
    foreach ($xTickIndexes as $idx) {
        $label = $xLabels[$idx] ?? (string)($idx + 1);
        $x = $pad + $idx * $stepX;
        $svg .= '<line x1="' . sprintf('%.1f', $x) . '" y1="' . ($h-$pad) . '" x2="' . sprintf('%.1f', $x) . '" y2="' . ($h-$pad+4) . '" stroke="#94a3b8" stroke-width="1"/>';
        $svg .= '<text x="' . sprintf('%.1f', $x - 10) . '" y="' . ($h-$pad+18) . '" font-size="11" fill="#111827">' . htmlspecialchars($label) . '</text>';
    }

    $svg .= '<text x="' . ($pad) . '" y="20" font-size="13" font-weight="600" fill="#0f172a">' . htmlspecialchars($title) . '</text>';
    $svg .= '<text x="8" y="' . ($pad - 10) . '" font-size="11" fill="#64748b">' . htmlspecialchars($yLabel) . '</text>';

    $svg .= '<path d="' . $area . '" fill="' . $fillColor . '" opacity="0.55"/>';
    $svg .= '<path d="' . $polyline . '" fill="none" stroke="' . $lineColor . '" stroke-width="2.4"/>';

    foreach ($points as [$x, $y]) {
        $svg .= '<circle cx="' . sprintf('%.1f', $x) . '" cy="' . sprintf('%.1f', $y) . '" r="3.4" fill="#2563eb" stroke="#0f172a" stroke-width="0.7"/>';
    }

    $svg .= '</svg>';
    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

// Minimal PDF template for League Intelligence Team report
?>
<!DOCTYPE html><html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($headerTitle ?? 'Team Intelligence Report') ?></title>
    <?php require __DIR__ . '/../../partials/pdf_styles.php'; ?>
</head>
<body>
    <h1><?= htmlspecialchars($headerTitle ?? 'Team Intelligence Report') ?></h1>
    <p><?= htmlspecialchars($Description ?? '') ?></p>


    <div class="section">
        <h2>Team Snapshot</h2>
        <table class="stats-table" style="width: 100%; margin: 0 auto; text-align: center;">
            <tr>
                <td style="background:#e5e7eb; width: 50%;"><strong>Position</strong></td>
                <td style="background:#e5e7eb; width: 50%;"><strong>Points</strong></td>
            </tr>
<tr>
                <td><?= htmlspecialchars($insights['position'] ?? '-') ?></td>
                <td><?= htmlspecialchars($insights['points'] ?? '-') ?></td>
            </tr>
            <tr>
                <td style="background:#e5e7eb; width: 50%;"><strong>Goal Diff</strong></td>
                                <td style="background:#e5e7eb; width: 50%;"><strong>Record</strong></td>
</tr>
<tr>
                <td><?= htmlspecialchars($insights['goal_difference'] ?? '-') ?></td>
                <td><?= htmlspecialchars($insights['record'] ?? '-') ?></td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h2>Current Status</h2>
        <table class="stats-table" style="width: 100%; margin: 0 auto; text-align: center;">
            <tr>
                <td style="background:#e5e7eb; width: 25%;"><strong>Streak</strong></td>
         <td style="background:#e5e7eb; width: 25%;"><strong>Points per game</strong></td>
                  <td style="background:#e5e7eb; width: 25%;"><strong>Avg goals per match</strong></td>
       <td style="background:#e5e7eb; width: 25%;"><strong>Clean sheets</strong></td>
                </tr>
            <tr>
            <td style="text-align: center"><?= htmlspecialchars($insights['streak'] ?? '-') ?></td>    
            
           <td style="text-align: center"><?= htmlspecialchars($insights['points_per_game'] ?? '-') ?></td>
<td style="text-align: center"><?= htmlspecialchars($insights['average_goals_per_match'] ?? '-') ?></td>
            <td style="text-align: center"><?= htmlspecialchars($insights['clean_sheets'] ?? '-') ?></td>
        </tr>
        </table>
    </div>

    <div class="section">
        <h2>Performance Profile</h2>
        <table class="stats-table" style="width:100%;text-align:center;">
            <tr>
                <th>Goals For</th>
                <th>Goals Against</th>
                <th>Home Record</th>
                <th>Away Record</th>
            </tr>
            <tr>
                <td><?= htmlspecialchars($insights['goals_for'] ?? '-') ?></td>
                <td><?= htmlspecialchars($insights['goals_against'] ?? '-') ?></td>
                <td>
                    <?php
                    $h = $insights['home'] ?? [];
                    $homeRecord = isset($h['wins'], $h['draws'], $h['losses']) ? ($h['wins'] . '-' . $h['draws'] . '-' . $h['losses']) : '-';
                    echo htmlspecialchars($homeRecord);
                    ?>
                </td>
                <td>
                   
                    <?php
                    $awayRecord = isset($a['wins'], $a['draws'], $a['losses']) ? ($a['wins'] . '-' . $a['draws'] . '-' . $a['losses']) : '-';
                    echo htmlspecialchars($awayRecord);
                    ?>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h2>Home vs Away</h2>
        <table class="stats-table home-away-table">
            <tr><th></th><th>Home</th><th>Away</th></tr>
            <?php
            $home = $insights['home'] ?? [];
            $away = $insights['away'] ?? [];
            $homeRecord = isset($home['wins'], $home['draws'], $home['losses']) ? ($home['wins'] . '-' . $home['draws'] . '-' . $home['losses']) : '-';
            $awayRecord = isset($away['wins'], $away['draws'], $away['losses']) ? ($away['wins'] . '-' . $away['draws'] . '-' . $away['losses']) : '-';
            ?>
            <tr><td>Record</td><td style="text-align: center"><?= htmlspecialchars($homeRecord) ?></td><td style="text-align: center"><?= htmlspecialchars($awayRecord) ?></td></tr>
            <tr><td>Goals For</td><td style="text-align: center"><?= htmlspecialchars($insights['home']['goals_for'] ?? '-') ?></td><td style="text-align: center"><?= htmlspecialchars($insights['away']['goals_for'] ?? '-') ?></td></tr>
            <tr><td>Goals Against</td><td style="text-align: center"><?= htmlspecialchars($insights['home']['goals_against'] ?? '-') ?></td><td style="text-align: center"><?= htmlspecialchars($insights['away']['goals_against'] ?? '-') ?></td></tr>
            <tr><td>Clean Sheets</td><td style="text-align: center"><?= htmlspecialchars($insights['home']['clean_sheets'] ?? '-') ?></td><td style="text-align: center"><?= htmlspecialchars($insights['away']['clean_sheets'] ?? '-') ?></td></tr>
        </table>
    </div>

    <div style="page-break-before: always;"></div>
<?php
/* not using this atm
       <div class="section">
        <h2>Recent Results (Last 5)</h2>
        <table class="stats-table">
            <tr>
                <th>Date</th>
                <th>Opponent</th>
                <th>Venue</th>
                <th>Result</th>
                <th>Score</th>
            </tr>
            <?php
            $recentMatches = array_filter(($insights['match_history'] ?? []), fn ($match) => ($match['result'] ?? null) !== null);
            usort($recentMatches, function ($a, $b) {
                return strtotime($b['date'] ?? '') <=> strtotime($a['date'] ?? '');
            });
            $recentMatches = array_slice($recentMatches, 0, 5);
            $formatPdfDate = static function (?string $date): string {
                if (empty($date)) {
                    return '-';
                }
                $ts = strtotime($date);
                if ($ts === false) {
                    return $date;
                }
                return date('D, j M', $ts);
            };
            $formatResult = static function (?string $result): string {
                return $result ?? '-';
            };
            foreach ($recentMatches as $match):
            ?>
                <tr>
                    <td style="text-align: center"><?= htmlspecialchars($formatPdfDate($match['date'] ?? null)) ?></td>
                    <td><?= htmlspecialchars($match['opponent_name'] ?? '-') ?></td>
                    <td style="text-align: center"><?= htmlspecialchars($match['venue'] ?? '-') ?></td>
                    <td style="text-align: center"><?= htmlspecialchars($formatResult($match['result'] ?? null)) ?></td>
                    <td style="text-align: center"><?= htmlspecialchars($match['score'] ?? '-') ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
    */
    ?>
   
    <div class="section">
        <h2>Goal Trend & Points Trend</h2>
        <div style="text-align:center;">
            <p style="font-weight:bold;">Goal Trend</p>
            <?php
            $goalTrend = $insights['goal_trend'] ?? [];
            $matchHistory = $insights['match_history'] ?? [];
            $goalLabels = [];
            if (!empty($matchHistory)) {
                foreach ($matchHistory as $i => $match) {
                    if (!isset($match['goals_for']) || $match['goals_for'] === null) {
                        continue;
                    }
                    $rawDate = $match['date'] ?? '';
                    $label = '';
                    if (!empty($rawDate)) {
                        $ts = strtotime($rawDate);
                        if ($ts !== false) {
                            $label = date('M j', $ts);
                        }
                    }
                    if ($label === '') {
                        $label = (string)($i + 1);
                    }
                    $goalLabels[] = $label;
                }
            }
            if (empty($goalLabels)) {
                $goalLabels = array_map(fn($i) => (string)($i + 1), range(0, max(0, count($goalTrend) - 1)));
            }
            if (!empty($goalTrend) && count($goalTrend) > 1) {
                $insights['goal_trend_graph'] = build_line_chart_svg($goalTrend, $goalLabels, 'Goals per Match', 'Goals');
            }
            ?>
            <img src="<?= htmlspecialchars($insights['goal_trend_graph'] ?? 'https://via.placeholder.com/500x180?text=Goal+Trend+Graph') ?>" style="width:500px;max-width:90vw;height:180px;object-fit:contain;border-radius:8px;border:2px solid #a3a3a3;display:block;margin:0 auto 18px auto;" alt="Goal Trend Graph">
        </div>
        <div style="text-align:center;">
            <p style="font-weight:bold;">Points Trend</p>
            <?php
            $pointsTrend = $insights['points_trend'] ?? [];
            $pointsLabels = [];
            if (!empty($matchHistory)) {
                foreach ($matchHistory as $i => $match) {
                    if (!in_array($match['result'] ?? null, ['W', 'D', 'L'], true)) {
                        continue;
                    }
                    $rawDate = $match['date'] ?? '';
                    $label = '';
                    if (!empty($rawDate)) {
                        $ts = strtotime($rawDate);
                        if ($ts !== false) {
                            $label = date('M j', $ts);
                        }
                    }
                    if ($label === '') {
                        $label = (string)($i + 1);
                    }
                    $pointsLabels[] = $label;
                }
            }
            if (empty($pointsLabels)) {
                $pointsLabels = array_map(fn($i) => (string)($i + 1), range(0, max(0, count($pointsTrend) - 1)));
            }
            if (!empty($pointsTrend) && count($pointsTrend) > 1) {
                $insights['analytics_graph'] = build_line_chart_svg($pointsTrend, $pointsLabels, 'Points Trend', 'Points');
            }
            ?>
            <img src="<?= htmlspecialchars($insights['analytics_graph'] ?? 'https://via.placeholder.com/500x180?text=Analytics+Graph') ?>" style="width:500px;max-width:90vw;height:180px;object-fit:contain;border-radius:8px;border:2px solid #a3a3a3;display:block;margin:0 auto;" alt="Analytics Graph">
        </div>
    </div>

    <div style="page-break-before: always;"></div>




    <div class="section">
        <h2>Head-to-Head</h2>

        <?php
        $myTeam = $insights['team_name'] ?? '';
        $clubContextId = isset($_GET['club_id']) ? (int)$_GET['club_id'] : 0;
        $clubTeamId = 0;
        $clubTeamName = '';
        if ($clubContextId > 0) {
            $clubTeams = get_teams_by_club($clubContextId);
            foreach ($clubTeams as $clubTeam) {
                if (($clubTeam['team_type'] ?? '') === 'club') {
                    $clubTeamId = (int)$clubTeam['id'];
                    $clubTeamName = (string)($clubTeam['name'] ?? '');
                    break;
                }
            }
            if ($clubTeamId <= 0 && !empty($clubTeams)) {
                $clubTeamId = (int)$clubTeams[0]['id'];
                $clubTeamName = (string)($clubTeams[0]['name'] ?? '');
            }
        }

        $h2hTeamId = isset($_GET['h2h_team_id']) ? (int)$_GET['h2h_team_id'] : 0;
        if ($h2hTeamId <= 0 && $clubTeamId > 0) {
            $h2hTeamId = $clubTeamId;
        }

        $matchHistory = $insights['match_history'] ?? [];
        $h2hMatches = array_values(array_filter($matchHistory, function ($match) use ($h2hTeamId) {
            return (int)($match['opponent_id'] ?? 0) === $h2hTeamId && ($match['status'] ?? '') === 'completed';
        }));

        $h2hStats = [
            'played' => count($h2hMatches),
            'draws' => 0,
            'team' => ['wins' => 0, 'home_wins' => 0, 'away_wins' => 0],
            'opponent' => ['wins' => 0, 'home_wins' => 0, 'away_wins' => 0],
        ];

        foreach ($h2hMatches as $match) {
            $result = $match['result'] ?? null;
            $venue = strtolower((string)($match['venue'] ?? ''));
            if ($result === 'W') {
                $h2hStats['team']['wins']++;
                if ($venue === 'home') {
                    $h2hStats['team']['home_wins']++;
                } elseif ($venue === 'away') {
                    $h2hStats['team']['away_wins']++;
                }
            } elseif ($result === 'L') {
                $h2hStats['opponent']['wins']++;
                if ($venue === 'home') {
                    $h2hStats['opponent']['away_wins']++;
                } elseif ($venue === 'away') {
                    $h2hStats['opponent']['home_wins']++;
                }
            } elseif ($result === 'D') {
                $h2hStats['draws']++;
            }
        }

        $played = (int)$h2hStats['played'];
        $teamWinPct = $played > 0 ? (int)round(($h2hStats['team']['wins'] / $played) * 100) : 0;
        $teamHomeWinPct = $played > 0 ? (int)round(($h2hStats['team']['home_wins'] / $played) * 100) : 0;
        $teamAwayWinPct = $played > 0 ? (int)round(($h2hStats['team']['away_wins'] / $played) * 100) : 0;
        $oppWinPct = $played > 0 ? (int)round(($h2hStats['opponent']['wins'] / $played) * 100) : 0;
        $oppHomeWinPct = $played > 0 ? (int)round(($h2hStats['opponent']['home_wins'] / $played) * 100) : 0;
        $oppAwayWinPct = $played > 0 ? (int)round(($h2hStats['opponent']['away_wins'] / $played) * 100) : 0;

        $opponentName = '';
        if ($clubTeamName !== '') {
            $opponentName = $clubTeamName;
        } else {
            foreach ($h2hMatches as $match) {
                if (!empty($match['opponent_name'])) {
                    $opponentName = (string)$match['opponent_name'];
                    break;
                }
            }
        }
        if ($opponentName === '') {
            $opponentName = 'Opponent';
        }

        if ($h2hTeamId > 0):
        ?>
        <table style="width:100%;border-collapse:collapse;">
            <tr>
                <td style="width:40%;vertical-align:top;padding-right:12px;">
                    <h3 style="font-size:1.2em;font-weight:bold;color:#000;margin-bottom:10px;"><?= htmlspecialchars($myTeam) ?></h3>
                    <div style="margin-bottom:10px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span><?= (int)$h2hStats['team']['wins'] ?> Total wins</span>
                            <span style="font-size:0.9em;color:#6b7280;"><?= $teamWinPct ?>%</span>
                        </div>
                        <div style="margin-top:4px;height:8px;border-radius:4px;background:#e5e7eb;width:100%;overflow:hidden;">
                            <div style="height:8px;border-radius:4px;background:#10b981;width:<?= $teamWinPct ?>%"></div>
                        </div>
                    </div>
                    <div style="margin-bottom:10px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span><?= (int)$h2hStats['team']['home_wins'] ?> Home wins</span>
                            <span style="font-size:0.9em;color:#6b7280;"><?= $teamHomeWinPct ?>%</span>
                        </div>
                        <div style="margin-top:4px;height:8px;border-radius:4px;background:#e5e7eb;width:100%;overflow:hidden;">
                            <div style="height:8px;border-radius:4px;background:#10b981;width:<?= $teamHomeWinPct ?>%"></div>
                        </div>
                    </div>
                    <div>
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span><?= (int)$h2hStats['team']['away_wins'] ?> Away wins</span>
                            <span style="font-size:0.9em;color:#6b7280;"><?= $teamAwayWinPct ?>%</span>
                        </div>
                        <div style="margin-top:4px;height:8px;border-radius:4px;background:#e5e7eb;width:100%;overflow:hidden;">
                            <div style="height:8px;border-radius:4px;background:#10b981;width:<?= $teamAwayWinPct ?>%"></div>
                        </div>
                    </div>
                </td>
                <td style="width:20%;vertical-align:middle;text-align:center;border-left:1px solid #e5e7eb;border-right:1px solid #e5e7eb;">
                    <p style="font-size:0.8em;text-transform:uppercase;letter-spacing:0.15em;color:#6b7280;margin-bottom:6px;">Played</p>
                    <p style="font-size:2.2em;font-weight:bold;color:#111827;line-height:1;"><?= $played ?></p>
                    <p style="font-size:0.9em;color:#6b7280;margin-top:6px;">Draws <strong><?= (int)$h2hStats['draws'] ?></strong></p>
                </td>
                <td style="width:40%;vertical-align:top;padding-left:12px;">
                    <h3 style="font-size:1.2em;font-weight:bold;color:#000;margin-bottom:10px;text-align:right;"><?= htmlspecialchars($opponentName) ?></h3>
                    <div style="margin-bottom:10px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span><?= (int)$h2hStats['opponent']['wins'] ?> Total wins</span>
                            <span style="font-size:0.9em;color:#6b7280;"><?= $oppWinPct ?>%</span>
                        </div>
                        <div style="margin-top:4px;height:8px;border-radius:4px;background:#e5e7eb;width:100%;overflow:hidden;">
                            <div style="height:8px;border-radius:4px;background:#6366f1;width:<?= $oppWinPct ?>%"></div>
                        </div>
                    </div>
                    <div style="margin-bottom:10px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span><?= (int)$h2hStats['opponent']['home_wins'] ?> Home wins</span>
                            <span style="font-size:0.9em;color:#6b7280;"><?= $oppHomeWinPct ?>%</span>
                        </div>
                        <div style="margin-top:4px;height:8px;border-radius:4px;background:#e5e7eb;width:100%;overflow:hidden;">
                            <div style="height:8px;border-radius:4px;background:#6366f1;width:<?= $oppHomeWinPct ?>%"></div>
                        </div>
                    </div>
                    <div>
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span><?= (int)$h2hStats['opponent']['away_wins'] ?> Away wins</span>
                            <span style="font-size:0.9em;color:#6b7280;"><?= $oppAwayWinPct ?>%</span>
                        </div>
                        <div style="margin-top:4px;height:8px;border-radius:4px;background:#e5e7eb;width:100%;overflow:hidden;">
                            <div style="height:8px;border-radius:4px;background:#6366f1;width:<?= $oppAwayWinPct ?>%"></div>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
        <?php else: ?>
            <p>No head-to-head data available.</p>
        <?php endif; ?>
    </div>

    <!-- All Previous Meetings Section -->
    <div class="section">
        <h2>All Previous Meetings</h2>
        <?php
        // Find all matches between this club and the selected opponent team (all seasons/competitions).
        $myTeamId = (int)($insights['team_id'] ?? 0);
        $clubId = isset($_GET['club_id']) ? (int)$_GET['club_id'] : (int)($insights['club_id'] ?? 0);
        $allMeetings = [];
        if ($myTeamId > 0 && $clubId > 0) {
            $sql = '
SELECT
  m.id AS match_id,
  m.home_team_id,
  m.away_team_id,
  m.kickoff_at,
  m.status,
  CASE WHEN m.status IN ("ready","completed","played") THEN COALESCE(home_goals.goals, 0) ELSE NULL END AS home_goals,
  CASE WHEN m.status IN ("ready","completed","played") THEN COALESCE(away_goals.goals, 0) ELSE NULL END AS away_goals,
  ht.name AS home_team_name,
  at.name AS away_team_name,
  comp.name AS competition_name
FROM matches m
LEFT JOIN teams ht ON ht.id = m.home_team_id
LEFT JOIN teams at ON at.id = m.away_team_id
LEFT JOIN competitions comp ON comp.id = m.competition_id
LEFT JOIN (
  SELECT e.match_id, COUNT(*) AS goals
  FROM events e
  JOIN event_types t ON t.id = e.event_type_id AND t.type_key = "goal"
  WHERE e.team_side = "home"
  GROUP BY e.match_id
) home_goals ON home_goals.match_id = m.id
LEFT JOIN (
  SELECT e.match_id, COUNT(*) AS goals
  FROM events e
  JOIN event_types t ON t.id = e.event_type_id AND t.type_key = "goal"
  WHERE e.team_side = "away"
  GROUP BY e.match_id
) away_goals ON away_goals.match_id = m.id
WHERE m.club_id = ?
  AND (m.home_team_id = ? OR m.away_team_id = ?)
  AND m.status IN ("ready","completed","played")
ORDER BY m.kickoff_at DESC, m.id DESC
';
            $stmt = db()->prepare($sql);
            $stmt->execute([$clubId, $myTeamId, $myTeamId]);
            $rows = $stmt->fetchAll();

            foreach ($rows as $row) {
                $homeGoals = $row['home_goals'];
                $awayGoals = $row['away_goals'];
                $score = ($homeGoals !== null && $awayGoals !== null) ? ($homeGoals . ' - ' . $awayGoals) : 'vs';
                $allMeetings[] = [
                    'match_id' => (int)($row['match_id'] ?? 0),
                    'date' => $row['kickoff_at'] ?? null,
                    'home_team_name' => $row['home_team_name'] ?? '-',
                    'away_team_name' => $row['away_team_name'] ?? '-',
                    'competition_name' => $row['competition_name'] ?? '',
                    'score' => $score,
                ];
            }
        }
        ?>
        <?php if (!empty($allMeetings)): ?>
        <div class="meeting-wrap">
            <?php foreach ($allMeetings as $match):
                $date = '-';
                if (!empty($match['date'])) {
                    $ts = strtotime($match['date']);
                    $date = $ts !== false ? date('d/m/y | H:i', $ts) : $match['date'];
                }
            ?>
            <div class="meeting-card">
                <div class="meeting-title"><?= htmlspecialchars($match['competition_name'] ?: 'Previous meeting') ?></div>
                <div class="meeting-meta"><?= htmlspecialchars($date) ?></div>
                <div class="meeting-row">
                    <div class="meeting-cell meeting-team"><?= htmlspecialchars($match['home_team_name'] ?? '-') ?></div>
                    <div class="meeting-cell meeting-score"><?= htmlspecialchars($match['score'] ?? 'vs') ?></div>
                    <div class="meeting-cell meeting-team-right"><?= htmlspecialchars($match['away_team_name'] ?? '-') ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
            <p>No previous meetings found between these teams.</p>
        <?php endif; ?>
    </div>

    <div class="section" style="display:flex;flex-direction:column;align-items:center;justify-content:center;">
        <h2>Recent Form</h2>
        <table style="margin-bottom:10px; margin-left:auto; margin-right:auto; border-collapse:separate; border-spacing:0 4px; width: 80%">
            <tr>
                <th style="text-align:center; padding: 4px 8px;">Score</th>
                <th style="text-align:center; padding: 4px 8px;">Opponent</th>
                 <th style="text-align:center; padding: 4px 8px;">Venue</th>
                <th style="text-align:center; padding: 4px 8px;">Result</th>
            </tr>
            <?php
            $recentMatches = array_filter(($insights['match_history'] ?? []), fn ($match) => ($match['result'] ?? null) !== null);
            usort($recentMatches, function ($a, $b) {
                return strtotime($b['date'] ?? '') <=> strtotime($a['date'] ?? '');
            });
            $recentMatches = array_slice($recentMatches, 0, 5);
            // Most recent at top, oldest at bottom (no reverse)
            $formatResult = static function (?string $result): string {
                return $result ? strtoupper($result) : '-';
            };
            foreach ($recentMatches as $match):
                $result = strtoupper($match['result'] ?? '-');
                $color = '#e5e7eb';
                if ($result === 'W') $color = '#22c55e';
                elseif ($result === 'D') $color = '#f59e42';
                elseif ($result === 'L') $color = '#ef4444';
                    $venue = ucfirst(strtolower($match['venue'] ?? '-'));
            ?>
            <tr>
                <td style="padding: 4px 8px; font-weight:bold; text-align:center;"> <?= htmlspecialchars($match['score'] ?? '-') ?> </td>
                <td style="padding: 4px 8px; text-align:center;"> <?= htmlspecialchars($match['opponent_name'] ?? '-') ?> </td>
                    <td style="padding: 4px 8px; text-align:center;"> <?= htmlspecialchars($venue) ?> </td>
                <td style="padding: 4px 8px; font-weight:bold; color:#fff; background:<?= $color ?>; border-radius:6px; text-align:center;"> <?= $formatResult($result) ?> </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php if (isset($insights['momentum']) && $insights['momentum'] !== '' && $insights['momentum'] !== '-'): ?>
        <div>
            <span style="font-weight:bold;">Momentum:</span>
            <span style="font-size:1.1em;">
                <?= htmlspecialchars($insights['momentum']) ?>
            </span>
        </div>
        <?php endif; ?>
    </div>


</body>
</html>
