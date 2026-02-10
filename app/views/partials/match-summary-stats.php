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
$cardsTotals = $totals['cards'] ?? null;
$highlightsByTeam = $totals['highlights']['by_team'] ?? $defaultCounts;
$penaltyTotals = $safe('penalty');
$cornerTotals = $safe('corner');
$freeKickTotals = $safe('free_kick');
$shotOnTargetTotals = $safe('shot_on_target');
$shotOffTargetTotals = $safe('shot_off_target');
$cardsTotals = is_array($cardsTotals)
          ? $cardsTotals
          : [
                    'home' => ($safe('yellow_card')['home'] ?? 0) + ($safe('red_card')['home'] ?? 0),
                    'away' => ($safe('yellow_card')['away'] ?? 0) + ($safe('red_card')['away'] ?? 0),
                    'unknown' => ($safe('yellow_card')['unknown'] ?? 0) + ($safe('red_card')['unknown'] ?? 0),
          ];
$scoreCounts = $safe('goal');
$homeScore = (int)($scoreCounts['home'] ?? 0);
$awayScore = (int)($scoreCounts['away'] ?? 0);
$shotTotalCounts = [
          'home' => ($shotOnTargetTotals['home'] ?? 0) + ($shotOffTargetTotals['home'] ?? 0),
          'away' => ($shotOnTargetTotals['away'] ?? 0) + ($shotOffTargetTotals['away'] ?? 0),
          'unknown' => ($shotOnTargetTotals['unknown'] ?? 0) + ($shotOffTargetTotals['unknown'] ?? 0),
];
$competitionLabel = trim((string)($match['competition'] ?? ''));

$comparisonRows = [
          ['label' => 'Goals', 'key' => 'goal'],
          ['label' => 'Shots', 'value' => $shotTotalCounts],
          ['label' => 'Shots on Target', 'value' => $shotOnTargetTotals],
          ['label' => 'Shots off Target', 'value' => $shotOffTargetTotals],
          ['label' => 'Chances', 'key' => 'chance'],
          ['label' => 'Corners', 'value' => $cornerTotals],
          ['label' => 'Free Kicks', 'value' => $freeKickTotals],
          ['label' => 'Penalties', 'value' => $penaltyTotals],
          ['label' => 'Set Pieces', 'value' => $setPieces],
          ['label' => 'Fouls', 'key' => 'foul'],
          ['label' => 'Cards (Total)', 'value' => $cardsTotals],
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
$homeShotsTotal = (int)(($shotOnTarget['home'] ?? 0) + ($shotOffTarget['home'] ?? 0));
$awayShotsTotal = (int)(($shotOnTarget['away'] ?? 0) + ($shotOffTarget['away'] ?? 0));
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
$firstHalfStartSecond = 0;
$firstHalfEndSecond = 45 * 60;
$secondHalfStartSecond = $firstHalfEndSecond;
$secondHalfEndSecond = 90 * 60;
if (isset($periods) && is_array($periods)) {
          foreach ($periods as $period) {
                    $key = $period['period_key'] ?? $period['key'] ?? null;
                    $start = isset($period['start_second']) ? (int)$period['start_second'] : null;
                    $end = isset($period['end_second']) ? (int)$period['end_second'] : null;
                    if ($key === 'first_half' && $start !== null) {
                              $firstHalfStartSecond = $start;
                    }
                    if ($key === 'first_half' && $end) {
                              $firstHalfEndSecond = $end;
                    }
                    if ($key === 'second_half' && $start !== null) {
                              $secondHalfStartSecond = $start;
                    }
                    if ($key === 'second_half' && $end) {
                              $secondHalfEndSecond = $end;
                    }
          }
}

$goalEvents = [];
$yellowCardEvents = [];
$redCardEvents = [];
if (isset($events) && is_array($events)) {
          foreach ($events as $event) {
                    $eventTypeId = (int)($event['event_type_id'] ?? 0);
                    if ($eventTypeId === 16) {
                              $goalEvents[] = $event;
                    } elseif ($eventTypeId === 8) {
                              $yellowCardEvents[] = $event;
                    } elseif ($eventTypeId === 9) {
                              $redCardEvents[] = $event;
                    }
          }
}

// Function to get player name from match_player_id
function getPlayerName($matchPlayerId, $matchPlayers) {
          if (!$matchPlayerId || !is_array($matchPlayers)) {
                    return 'Unknown';
          }
          foreach ($matchPlayers as $mp) {
                    if ((int)($mp['id'] ?? 0) === (int)$matchPlayerId) {
                              return trim($mp['player_name'] ?? 'Unknown');
                    }
          }
          return 'Unknown';
}

$halfStarts = [
          'first_half' => $firstHalfStartSecond,
          'second_half' => $secondHalfStartSecond,
];
$formatMinute = function ($matchSecond, $halfKey) use ($halfStarts, $firstHalfStartSecond, $firstHalfEndSecond, $secondHalfStartSecond, $secondHalfEndSecond) {
          $matchSecond = (int)$matchSecond;
          if ($halfKey === 'first_half') {
                    if ($matchSecond < $firstHalfStartSecond || $matchSecond > $firstHalfEndSecond) {
                              return (string)((int)floor($matchSecond / 60));
                    }
                    $elapsed = $matchSecond - $firstHalfStartSecond;
                    $minute = (int)floor($elapsed / 60);
                    if ($minute > 45) {
                              return '45+' . ($minute - 45);
                    }
                    return (string)$minute;
          }
          if ($halfKey === 'second_half') {
                    if ($matchSecond < $secondHalfStartSecond || $matchSecond > $secondHalfEndSecond) {
                              return (string)((int)floor($matchSecond / 60));
                    }
                    $elapsed = $matchSecond - $secondHalfStartSecond;
                    $minute = 45 + (int)floor($elapsed / 60);
                    if ($minute > 90) {
                              return '90+' . ($minute - 90);
                    }
                    return (string)$minute;
          }
          return (string)((int)floor($matchSecond / 60));
};
$groupHalfKey = function ($matchSecond) use ($firstHalfStartSecond, $firstHalfEndSecond, $secondHalfStartSecond) {
          $matchSecond = (int)$matchSecond;
          if ($matchSecond >= $secondHalfStartSecond) {
                    return 'second_half';
          }
          if ($matchSecond >= $firstHalfStartSecond && $matchSecond <= $firstHalfEndSecond) {
                    return 'first_half';
          }
          if ($matchSecond < $firstHalfStartSecond) {
                    return 'first_half';
          }
          return 'second_half';
};
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

require_once __DIR__ . '/../../lib/match_substitution_repository.php';
$substitutions = isset($match['id']) ? get_match_substitutions((int)$match['id']) : [];

$groupHalfKey = function ($matchSecond) use ($firstHalfEndSecond) {
          return ((int)$matchSecond <= $firstHalfEndSecond) ? 'first_half' : 'second_half';
};

$buildItem = function ($type, $event, $matchPlayers, $formatMinute, $halfKey, $extra = []) {
          $matchSecond = (int)($event['match_second'] ?? 0);
          $playerName = getPlayerName($event['match_player_id'] ?? null, $matchPlayers);
          $label = $playerName;
          if (!empty($event['notes'])) {
                    $label .= ' (' . trim((string)$event['notes']) . ')';
          }
          return array_merge([
                    'type' => $type,
                    'team_side' => $event['team_side'] ?? 'unknown',
                    'match_second' => $matchSecond,
                    'minute' => $formatMinute($matchSecond, $halfKey),
                    'label' => $label,
          ], $extra);
};

$halfBuckets = [
          'first_half' => ['home' => [], 'away' => []],
          'second_half' => ['home' => [], 'away' => []],
];

$eventsById = [];
if (isset($events) && is_array($events)) {
          foreach ($events as $event) {
                    if (!empty($event['id'])) {
                              $eventsById[(int)$event['id']] = $event;
                    }
          }
}

$goalScore = ['home' => 0, 'away' => 0];
$goalTimeline = [];
foreach ($goalEvents as $goal) {
          $side = ($goal['team_side'] ?? '') === 'away' ? 'away' : 'home';
          $goalScore[$side] += 1;
          $goalTimeline[] = [
                    'event' => $goal,
                    'scoreline' => $goalScore['home'] . ' - ' . $goalScore['away'],
          ];
}

foreach ($goalTimeline as $goalEntry) {
          $event = $goalEntry['event'];
          $halfKey = $groupHalfKey($event['match_second'] ?? 0);
          $item = $buildItem('goal', $event, $matchPlayers, $formatMinute, $halfKey, [
                    'scoreline' => $goalEntry['scoreline'],
          ]);
          $side = ($item['team_side'] ?? '') === 'away' ? 'away' : 'home';
          $halfBuckets[$halfKey][$side][] = $item;
}

foreach ($yellowCardEvents as $card) {
          $halfKey = $groupHalfKey($card['match_second'] ?? 0);
          $item = $buildItem('yellow', $card, $matchPlayers, $formatMinute, $halfKey);
          $side = ($item['team_side'] ?? '') === 'away' ? 'away' : 'home';
          $halfBuckets[$halfKey][$side][] = $item;
}

foreach ($redCardEvents as $card) {
          $halfKey = $groupHalfKey($card['match_second'] ?? 0);
          $item = $buildItem('red', $card, $matchPlayers, $formatMinute, $halfKey);
          $side = ($item['team_side'] ?? '') === 'away' ? 'away' : 'home';
          $halfBuckets[$halfKey][$side][] = $item;
}

foreach ($substitutions as $sub) {
          $matchSecond = (int)($sub['match_second'] ?? 0);
          $subMinute = (int)($sub['minute'] ?? 0);
          $subMinuteExtra = (int)($sub['minute_extra'] ?? 0);
          $subMinuteText = null;
          if ($subMinute > 0) {
                    $subMinuteText = (string)$subMinute;
                    if ($subMinuteExtra > 0) {
                              $subMinuteText .= '+' . $subMinuteExtra;
                    }
                    if ($matchSecond <= 0) {
                              if ($subMinute > 45 && $secondHalfStartSecond > 0) {
                                        $matchSecond = $secondHalfStartSecond + (($subMinute - 45) * 60) + ($subMinuteExtra * 60);
                              } else {
                                        $matchSecond = $firstHalfStartSecond + ($subMinute * 60) + ($subMinuteExtra * 60);
                              }
                    }
          }
          if ($matchSecond <= 0) {
                    $totalMinute = $subMinute + $subMinuteExtra;
                    if ($totalMinute > 0) {
                              $matchSecond = $totalMinute * 60;
                    }
          }
          if ($matchSecond <= 0 && !empty($sub['event_id']) && isset($eventsById[(int)$sub['event_id']])) {
                    $matchSecond = (int)($eventsById[(int)$sub['event_id']]['match_second'] ?? 0);
          }
          $halfKey = $groupHalfKey($matchSecond);
          $label = trim(($sub['player_off_name'] ?? 'Unknown') . ' → ' . ($sub['player_on_name'] ?? 'Unknown'));
          $item = [
                    'type' => 'sub',
                    'team_side' => $sub['team_side'] ?? 'unknown',
                    'match_second' => $matchSecond,
                    'minute' => $subMinuteText ?? $formatMinute($matchSecond, $halfKey),
                    'label' => $label,
          ];
          $side = ($item['team_side'] ?? '') === 'away' ? 'away' : 'home';
          $halfBuckets[$halfKey][$side][] = $item;
}

foreach ($halfBuckets as $halfKey => $sides) {
          foreach ($sides as $side => $items) {
                    usort($halfBuckets[$halfKey][$side], function ($a, $b) {
                              return ($a['match_second'] ?? 0) <=> ($b['match_second'] ?? 0);
                    });
          }
}

$halfScores = [
          'first_half' => ['home' => 0, 'away' => 0],
          'second_half' => ['home' => 0, 'away' => 0],
];
foreach ($goalEvents as $goal) {
          $halfKey = $groupHalfKey($goal['match_second'] ?? 0);
          $side = ($goal['team_side'] ?? '') === 'away' ? 'away' : 'home';
          $halfScores[$halfKey][$side] += 1;
}
?>

<?php if (!empty($goalEvents) || !empty($yellowCardEvents) || !empty($redCardEvents) || !empty($substitutions)): ?>
<div class="match-events-summary mb-3">
          <div class="event-summary-section event-summary-timeline">
                    <?php
                    $halfLabels = [
                              'first_half' => '1ST HALF',
                              'second_half' => '2ND HALF',
                    ];
                    foreach ($halfLabels as $halfKey => $halfLabel):
                              $homeItems = $halfBuckets[$halfKey]['home'] ?? [];
                              $awayItems = $halfBuckets[$halfKey]['away'] ?? [];
                              if (empty($homeItems) && empty($awayItems)) {
                                        continue;
                              }
                              $halfScore = ($halfScores[$halfKey]['home'] ?? 0) . ' - ' . ($halfScores[$halfKey]['away'] ?? 0);
                    ?>
                    <div class="event-summary-half">
                              <div class="event-summary-half-header">
                                        <span class="event-summary-half-label"><?= $halfLabel ?></span>
                                        <span class="event-summary-half-score"><?= $halfScore ?></span>
                              </div>
                              <div class="event-summary-half-grid">
                                        <div class="event-summary-column event-summary-column--home">
                                                  <?php foreach ($homeItems as $item): ?>
                                                            <div class="event-summary-item event-summary-item--home">
                                                                      <span class="event-summary-time"><?= htmlspecialchars($item['minute']) ?>'</span>
                                                                      <?php if (($item['type'] ?? '') === 'goal'): ?>
                                                                                <span class="event-summary-icon event-summary-icon--goal"><i class="fa-solid fa-futbol"></i></span>
                                                                                <span class="event-summary-scoreline"><?= htmlspecialchars($item['scoreline'] ?? '') ?></span>
                                                                      <?php elseif (($item['type'] ?? '') === 'sub'): ?>
                                                                                <span class="event-summary-icon event-summary-icon--sub"><i class="fa-solid fa-right-left"></i></span>
                                                                      <?php elseif (($item['type'] ?? '') === 'yellow'): ?>
                                                                                <span class="event-summary-icon event-summary-icon--card event-summary-icon--yellow"></span>
                                                                      <?php elseif (($item['type'] ?? '') === 'red'): ?>
                                                                                <span class="event-summary-icon event-summary-icon--card event-summary-icon--red"></span>
                                                                      <?php endif; ?>
                                                                      <span class="event-summary-label"><?= htmlspecialchars($item['label'] ?? '') ?></span>
                                                            </div>
                                                  <?php endforeach; ?>
                                        </div>
                                        <div class="event-summary-column event-summary-column--away">
                                                  <?php foreach ($awayItems as $item): ?>
                                                            <div class="event-summary-item event-summary-item--away">
                                                                      <span class="event-summary-label"><?= htmlspecialchars($item['label'] ?? '') ?></span>
                                                                      <?php if (($item['type'] ?? '') === 'goal'): ?>
                                                                                <span class="event-summary-scoreline"><?= htmlspecialchars($item['scoreline'] ?? '') ?></span>
                                                                                <span class="event-summary-icon event-summary-icon--goal"><i class="fa-solid fa-futbol"></i></span>
                                                                      <?php elseif (($item['type'] ?? '') === 'sub'): ?>
                                                                                <span class="event-summary-icon event-summary-icon--sub"><i class="fa-solid fa-right-left"></i></span>
                                                                      <?php elseif (($item['type'] ?? '') === 'yellow'): ?>
                                                                                <span class="event-summary-icon event-summary-icon--card event-summary-icon--yellow"></span>
                                                                      <?php elseif (($item['type'] ?? '') === 'red'): ?>
                                                                                <span class="event-summary-icon event-summary-icon--card event-summary-icon--red"></span>
                                                                      <?php endif; ?>
                                                                      <span class="event-summary-time"><?= htmlspecialchars($item['minute']) ?>'</span>
                                                            </div>
                                                  <?php endforeach; ?>
                                        </div>
                              </div>
                    </div>
                    <?php endforeach; ?>
          </div>
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

<div class="stats-section" data-stats-section="players">
<div class="player-stats-panel">
          <?php
          $startingXI = $playerPerformance['starting_xi'] ?? [];
          $substitutes = $playerPerformance['substitutes'] ?? [];
          $renderRows = function (array $players) {
                    foreach ($players as $player) {
                              $name = trim((string)($player['name'] ?? 'Unknown'));
                              $shirt = (int)($player['shirt_number'] ?? 0);
                              $pos = trim((string)($player['position'] ?? ''));
                              $goals = (int)($player['goals'] ?? 0);
                              $yellows = (int)($player['yellow_cards'] ?? 0);
                              $reds = (int)($player['red_cards'] ?? 0);
                              $formatStat = static function (int $value): string {
                                        return $value > 0 ? (string)$value : '-';
                              };
                              $isCaptain = !empty($player['is_captain']);
                              echo '<tr>';
                              echo '<td class="player-stats-col player-stats-col--number">' . ($shirt > 0 ? $shirt : '—') . '</td>';
                              echo '<td class="player-stats-col player-stats-col--name">' . htmlspecialchars($name) . ($isCaptain ? ' <span class="player-stats-captain">(C)</span>' : '') . '</td>';
                              echo '<td class="player-stats-col player-stats-col--position">' . htmlspecialchars($pos !== '' ? $pos : 'N/A') . '</td>';
                              echo '<td class="player-stats-col player-stats-col--metric">' . $formatStat($goals) . '</td>';
                              echo '<td class="player-stats-col player-stats-col--metric player-stats-col--yellow">' . $formatStat($yellows) . '</td>';
                              echo '<td class="player-stats-col player-stats-col--metric player-stats-col--red">' . $formatStat($reds) . '</td>';
                              echo '</tr>';
                    }
          };
          ?>
          <div class="player-stats-section">
                    <div class="player-stats-section-title">Starting XI</div>
                    <div class="player-stats-table-wrap">
                              <table class="player-stats-table">
                                        <thead>
                                                  <tr>
                                                            <th class="text-center">#</th>
                                                            <th>Player</th>
                                                            <th class="text-center">Pos</th>
                                                            <th class="text-center">G</th>
                                                            <th class="text-center">Y</th>
                                                            <th class="text-center">R</th>
                                                  </tr>
                                        </thead>
                                        <tbody>
                                                  <?php if (!empty($startingXI)): ?>
                                                            <?php $renderRows($startingXI); ?>
                                                  <?php else: ?>
                                                            <tr class="player-stats-empty"><td colspan="6">No starting XI data available.</td></tr>
                                                  <?php endif; ?>
                                        </tbody>
                              </table>
                    </div>
          </div>
          <div class="player-stats-section">
                    <div class="player-stats-section-title">Substitutes</div>
                    <div class="player-stats-table-wrap player-stats-table-wrap--auto">
                              <table class="player-stats-table">
                                        <thead>
                                                  <tr>
                                                            <th>#</th>
                                                            <th>Player</th>
                                                            <th class="text-center">Pos</th>
                                                            <th class="text-center">G</th>
                                                            <th class="text-center">Y</th>
                                                            <th class="text-center">R</th>
                                                  </tr>
                                        </thead>
                                        <tbody>
                                                  <?php if (!empty($substitutes)): ?>
                                                            <?php $renderRows($substitutes); ?>
                                                  <?php else: ?>
                                                            <tr class="player-stats-empty"><td colspan="6">No substitute data available.</td></tr>
                                                  <?php endif; ?>
                                        </tbody>
                              </table>
                    </div>
          </div>
</div>
</div>
