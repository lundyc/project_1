<?php
if (!isset($match) || !isset($derivedStats)) {
          return;
}

$defaultCounts = ['home' => 0, 'away' => 0, 'unknown' => 0];
$byType = $derivedStats['by_type_team'] ?? [];
$totals = $derivedStats['totals'] ?? [];
$safe = function (string $key) use ($byType, $defaultCounts) {
          return $byType[$key] ?? $defaultCounts;
};

$setPieces = $totals['set_pieces'] ?? $defaultCounts;
$highlightsByTeam = $totals['highlights']['by_team'] ?? $defaultCounts;
$scoreCounts = $safe('goal');
$homeScore = (int)($scoreCounts['home'] ?? 0);
$awayScore = (int)($scoreCounts['away'] ?? 0);
$competitionLabel = trim((string)($match['competition'] ?? ''));

$comparisonRows = [
          ['label' => 'Goals', 'key' => 'goal'],
          ['label' => 'Shots', 'key' => 'shot'],
          ['label' => 'Chances', 'key' => 'chance'],
          ['label' => 'Set Pieces', 'value' => $setPieces],
          ['label' => 'Fouls', 'key' => 'foul'],
          ['label' => 'Yellow Cards', 'key' => 'yellow_card'],
          ['label' => 'Red Cards', 'key' => 'red_card'],
          ['label' => 'Mistakes', 'key' => 'mistake'],
          ['label' => 'Good Play', 'key' => 'good_play'],
          [
                    'label' => 'Highlights',
                    'value' => [
                              'home' => $highlightsByTeam['home'] ?? 0,
                              'away' => $highlightsByTeam['away'] ?? 0,
                              'unknown' => $highlightsByTeam['unknown'] ?? 0,
                    ],
          ],
];
?>

<?php
$shotTotals = $safe('shot');
$shotOnTarget = $safe('shot_on_target');
$shotOffTarget = $safe('shot_off_target');
$homeShotOn = (int)($shotOnTarget['home'] ?? 0);
$awayShotOn = (int)($shotOnTarget['away'] ?? 0);
$homeShotOff = (int)($shotOffTarget['home'] ?? 0);
$awayShotOff = (int)($shotOffTarget['away'] ?? 0);
$homeShotBreakdownTotal = $homeShotOn + $homeShotOff;
$awayShotBreakdownTotal = $awayShotOn + $awayShotOff;
$homeShotOnPct = $homeShotBreakdownTotal > 0 ? round(($homeShotOn / $homeShotBreakdownTotal) * 100) : 0;
$awayShotOnPct = $awayShotBreakdownTotal > 0 ? round(($awayShotOn / $awayShotBreakdownTotal) * 100) : 0;
$homeShotOffPct = $homeShotBreakdownTotal > 0 ? 100 - $homeShotOnPct : 0;
$awayShotOffPct = $awayShotBreakdownTotal > 0 ? 100 - $awayShotOnPct : 0;
$homeShotsTotal = (int)($shotTotals['home'] ?? 0);
$awayShotsTotal = (int)($shotTotals['away'] ?? 0);
?>

<div class="stats-section" data-stats-section="overview">
<div class="summary-score summary-score-card mb-3">
          <div class="summary-team summary-team-home">
                    <div class="summary-team-label">Home</div>
                    <div class="summary-team-name"><?= htmlspecialchars($match['home_team'] ?? 'Home') ?></div>
          </div>
          <div class="summary-scoreline">
                    <div class="summary-score-digits">
                              <span class="score-number"><?= $homeScore ?></span>
                              <span class="score-separator">:</span>
                              <span class="score-number"><?= $awayScore ?></span>
                    </div>
                    <?php if ($competitionLabel !== ''): ?>
                              <div class="text-muted-alt text-xs"><?= htmlspecialchars($competitionLabel) ?></div>
                    <?php endif; ?>
          </div>
          <div class="summary-team summary-team-away text-end">
                    <div class="summary-team-label text-end">Away</div>
                    <div class="summary-team-name"><?= htmlspecialchars($match['away_team'] ?? 'Away') ?></div>
          </div>
</div>

<?php
// Get goal scorers, yellow cards, and red cards with player names
$goalEvents = [];
$yellowCardEvents = [];
$redCardEvents = [];

if (isset($events) && is_array($events)) {
          foreach ($events as $event) {
                    $eventTypeId = (int)($event['event_type_id'] ?? 0);
                    if ($eventTypeId === 16) { // Goal
                              $goalEvents[] = $event;
                    } elseif ($eventTypeId === 8) { // Yellow Card
                              $yellowCardEvents[] = $event;
                    } elseif ($eventTypeId === 9) { // Red Card
                              $redCardEvents[] = $event;
                    }
          }
}

// Function to get player name from match_player_id
function getPlayerName($matchPlayerId, $matchPlayers) {
          if (!$matchPlayerId || !is_array($matchPlayers)) return 'Unknown';
          foreach ($matchPlayers as $mp) {
                    if ((int)($mp['id'] ?? 0) === (int)$matchPlayerId) {
                              return trim($mp['player_name'] ?? 'Unknown');
                    }
          }
          return 'Unknown';
}

// Get match players if available
$matchPlayers = [];
if (isset($match['id'])) {
          $stmt = db()->prepare('
                    SELECT mp.id, CONCAT(p.first_name, " ", p.last_name) as player_name, mp.team_side
                    FROM match_players mp
                    JOIN players p ON p.id = mp.player_id
                    WHERE mp.match_id = :match_id
          ');
          $stmt->execute(['match_id' => (int)$match['id']]);
          $matchPlayers = $stmt->fetchAll();
}
?>

<?php if (!empty($goalEvents) || !empty($yellowCardEvents) || !empty($redCardEvents)): ?>
<div class="match-events-summary mb-3">
          <div class="event-summary-section">
                    <?php
                    $homeGoals = array_filter($goalEvents, fn($e) => ($e['team_side'] ?? '') === 'home');
                    $awayGoals = array_filter($goalEvents, fn($e) => ($e['team_side'] ?? '') === 'away');
                    
                    // Sort by match_second (which represents time in seconds)
                    usort($homeGoals, function($a, $b) {
                              return ((int)($a['match_second'] ?? 0)) - ((int)($b['match_second'] ?? 0));
                    });
                    usort($awayGoals, function($a, $b) {
                              return ((int)($a['match_second'] ?? 0)) - ((int)($b['match_second'] ?? 0));
                    });
                    
                    // Get max count to display equal rows
                    $maxGoals = max(count($homeGoals), count($awayGoals));
                    ?>
                    <div class="events-column-wrapper">
                              <div class="events-column">
                                        <?php foreach ($homeGoals as $goal): ?>
                                                  <div class="text-sm flex items-center gap-1">
                                                            <svg fill="currentColor" viewBox="0 0 20 20" style="width: 16px; height: 16px;" data-testid="wcl-icon-incidents-goal-soccer" class="wcl-icon_WGKvC"><title>Goal</title><path fill-rule="evenodd" class="incidents-goal-soccer" d="M17 2.93a9.96 9.96 0 1 0-14.08 14.1A9.96 9.96 0 0 0 17 2.92Zm.41 2.77a8.5 8.5 0 0 1 1.1 3.43L16.66 8.1l.75-2.4Zm-1.37-1.8.37.4-1.11 3.57-1.33.4-3.32-2.41V4.5l3.16-2.2a8.6 8.6 0 0 1 2.22 1.6ZM9.96 1.4c.78-.01 1.55.1 2.3.3l-2.3 1.6-2.3-1.6c.75-.2 1.52-.31 2.3-.3ZM3.9 3.9a8.6 8.6 0 0 1 2.22-1.6l3.16 2.2v1.36l-3.32 2.4-1.32-.4L3.52 4.3l.37-.4ZM2.52 5.7l.75 2.4-1.85 1.03a8.5 8.5 0 0 1 1.1-3.43Zm1.37 10.35-.22-.23H5.7l.65 1.95a8.6 8.6 0 0 1-2.45-1.72Zm2.01-1.6H2.63A8.5 8.5 0 0 1 1.4 10.7l2.75-1.55 1.41.43 1.28 3.91-.95.95Zm6.05 3.89c-1.3.3-2.66.3-3.97 0l-1.01-3.02 1.1-1.1h3.79l1.1 1.1-1.01 3.02Zm-.07-5.44H8.05L6.86 9.25 9.96 7l3.1 2.25-1.18 3.65Zm4.15 3.15a8.6 8.6 0 0 1-2.45 1.72l.66-1.94h2.01l-.22.22Zm-2-1.6-.95-.95 1.27-3.91 1.41-.43 2.76 1.55a8.5 8.5 0 0 1-1.22 3.74h-3.27Z"></path></svg>
                                                            <span><?= htmlspecialchars(getPlayerName($goal['match_player_id'] ?? null, $matchPlayers)) ?> <?= (int)floor((int)($goal['match_second'] ?? 0) / 60) ?>'</span>
                                        </div>
                              <?php endforeach; ?>
                              </div>
                              <div class="events-column">
                                        <?php foreach ($awayGoals as $goal): ?>
                                                  <div class="text-sm flex items-center gap-1 justify-end">
                                                            <span><?= htmlspecialchars(getPlayerName($goal['match_player_id'] ?? null, $matchPlayers)) ?> <?= (int)floor((int)($goal['match_second'] ?? 0) / 60) ?>'</span>
                                                            <svg fill="currentColor" viewBox="0 0 20 20" style="width: 16px; height: 16px;" data-testid="wcl-icon-incidents-goal-soccer" class="wcl-icon_WGKvC"><title>Goal</title><path fill-rule="evenodd" class="incidents-goal-soccer" d="M17 2.93a9.96 9.96 0 1 0-14.08 14.1A9.96 9.96 0 0 0 17 2.92Zm.41 2.77a8.5 8.5 0 0 1 1.1 3.43L16.66 8.1l.75-2.4Zm-1.37-1.8.37.4-1.11 3.57-1.33.4-3.32-2.41V4.5l3.16-2.2a8.6 8.6 0 0 1 2.22 1.6ZM9.96 1.4c.78-.01 1.55.1 2.3.3l-2.3 1.6-2.3-1.6c.75-.2 1.52-.31 2.3-.3ZM3.9 3.9a8.6 8.6 0 0 1 2.22-1.6l3.16 2.2v1.36l-3.32 2.4-1.32-.4L3.52 4.3l.37-.4ZM2.52 5.7l.75 2.4-1.85 1.03a8.5 8.5 0 0 1 1.1-3.43Zm1.37 10.35-.22-.23H5.7l.65 1.95a8.6 8.6 0 0 1-2.45-1.72Zm2.01-1.6H2.63A8.5 8.5 0 0 1 1.4 10.7l2.75-1.55 1.41.43 1.28 3.91-.95.95Zm6.05 3.89c-1.3.3-2.66.3-3.97 0l-1.01-3.02 1.1-1.1h3.79l1.1 1.1-1.01 3.02Zm-.07-5.44H8.05L6.86 9.25 9.96 7l3.1 2.25-1.18 3.65Zm4.15 3.15a8.6 8.6 0 0 1-2.45 1.72l.66-1.94h2.01l-.22.22Zm-2-1.6-.95-.95 1.27-3.91 1.41-.43 2.76 1.55a8.5 8.5 0 0 1-1.22 3.74h-3.27Z"></path></svg>
                                        </div>
                              <?php endforeach; ?>
                              </div>
                    </div>
          </div>

          <?php if (!empty($yellowCardEvents) || !empty($redCardEvents)): ?>
                    <div class="event-summary-section mt-3">
                              <?php
                              $homeYellow = array_filter($yellowCardEvents, fn($e) => ($e['team_side'] ?? '') === 'home');
                              $homeRed = array_filter($redCardEvents, fn($e) => ($e['team_side'] ?? '') === 'home');
                              $awayYellow = array_filter($yellowCardEvents, fn($e) => ($e['team_side'] ?? '') === 'away');
                              $awayRed = array_filter($redCardEvents, fn($e) => ($e['team_side'] ?? '') === 'away');
                              
                              $maxCards = max(count($homeYellow) + count($homeRed), count($awayYellow) + count($awayRed));
                              ?>
                              <div class="events-column-wrapper">
                                        <div class="events-column">
                                                  <?php
                                                  foreach ($homeYellow as $card) {
                                                            echo '<div class="text-sm d-flex align-items-center gap-1">';
                                                            echo '<span>';
                                                            echo htmlspecialchars(getPlayerName($card['match_player_id'] ?? null, $matchPlayers)) . ' ' . (int)floor((int)($card['match_second'] ?? 0) / 60). "'";
                                                            echo '</span>';
                                                            echo '<svg class="card-ico yellowCard-ico" style="width: 12px; height: 16px;"><title>Yellow Card</title><use xlink:href="/assets/svg/incident.svg#card"></use></svg>';
                                                            echo '</div>';
                                                  }
                                                  foreach ($homeRed as $card) {
                                                            echo '<div class="text-sm d-flex align-items-center gap-1">';
                                                            echo '<span>';
                                                            echo htmlspecialchars(getPlayerName($card['match_player_id'] ?? null, $matchPlayers)) . ' ' . (int)floor((int)($card['match_second'] ?? 0) / 60) . "'";
                                                            echo '</span>';
                                                            echo '<svg class="card-ico redCard-ico" style="width: 12px; height: 16px;"><title>Red Card</title><use xlink:href="/assets/svg/incident.svg#card"></use></svg>';
                                                           echo '</div>';
                                                  }
                                                  ?>
                                        </div>
                                        <div class="events-column">
                                                  <?php
                                                  foreach ($awayYellow as $card) {
                                                            echo '<div class="text-sm d-flex align-items-right gap-1 justify-content-end">';
                                                            echo '<svg class="card-ico yellowCard-ico" style="width: 12px; height: 16px;"><title>Yellow Card</title><use xlink:href="/assets/svg/incident.svg#card"></use></svg>';
                                                            echo '<span>';
                                                            echo htmlspecialchars(getPlayerName($card['match_player_id'] ?? null, $matchPlayers)) . ' ' . (int)floor((int)($card['match_second'] ?? 0) / 60) . "'";
                                                            echo '</span>';
                                                            echo '</div>';
                                                  }
                                                  foreach ($awayRed as $card) {
                                                            echo '<div class="text-sm d-flex align-items-right gap-1 justify-content-end">';
                                                            echo '<svg class="card-ico redCard-ico" style="width: 12px; height: 16px;"><title>Red Card</title><use xlink:href="/assets/svg/incident.svg#card"></use></svg>';
                                                            echo '<span>';
                                                            echo htmlspecialchars(getPlayerName($card['match_player_id'] ?? null, $matchPlayers)) . ' ' . (int)floor((int)($card['match_second'] ?? 0) / 60) . "'";
                                                            echo '</span>';
                                                            echo '</div>';
                                                  }
                                                  ?>
                                        </div>
                              </div>
                    </div>
          <?php endif; ?>
    </div>
<?php endif; ?>

</div>

<div class="stats-section" data-stats-section="comparison">
<div class="comparison-list">
          <?php foreach ($comparisonRows as $row): ?>
                    <?php
                    $counts = isset($row['key']) && $row['key'] ? $safe($row['key']) : ($row['value'] ?? $defaultCounts);
                    $homeVal = (int)($counts['home'] ?? 0);
                    $awayVal = (int)($counts['away'] ?? 0);
                    if ($homeVal === 0 && $awayVal === 0) {
                              continue;
                    }
                    $maxVal = max(1, $homeVal, $awayVal);
                    $homePct = ($homeVal / $maxVal) * 100;
                    $awayPct = ($awayVal / $maxVal) * 100;
                    ?>
                    <div class="comparison-row">
                              <div class="side value-home" aria-label="Home value <?= $homeVal ?> for <?= htmlspecialchars($row['label']) ?>">
                                        <div class="value-number text-home"><?= $homeVal ?></div>
                                        <div class="bar-wrap" title="<?= $homeVal ?>">
                                                  <div class="bar bar-home" style="width: <?= $homePct ?>%"></div>
                                        </div>
                              </div>
                              <div class="metric-label"><?= htmlspecialchars($row['label']) ?></div>
                              <div class="side value-away" aria-label="Away value <?= $awayVal ?> for <?= htmlspecialchars($row['label']) ?>">
                                        <div class="bar-wrap" title="<?= $awayVal ?>">
                                                  <div class="bar bar-away" style="width: <?= $awayPct ?>%"></div>
                                        </div>
                                        <div class="value-number text-away"><?= $awayVal ?></div>
                              </div>
                    </div>
          <?php endforeach; ?>
</div>
</div>
