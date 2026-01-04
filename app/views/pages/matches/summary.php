<?php
require_auth();
require_once __DIR__ . '/../../../lib/time_helper.php';

$base = base_path();
$title = 'Match Summary';

$flashSuccess = $_SESSION['summary_flash_success'] ?? null;
unset($_SESSION['summary_flash_success']);

$byType = $derivedStats['by_type_team'] ?? [];
$totals = $derivedStats['totals'] ?? [];

$defaultCounts = ['home' => 0, 'away' => 0, 'unknown' => 0];
$safe = function (string $key) use ($byType, $defaultCounts) {
          return $byType[$key] ?? $defaultCounts;
};

$phase2 = $derivedStats['phase_2'] ?? []; // Phase 2 summary data from derived_stats.payload_json
$phase2ByPeriod = $phase2['by_period'] ?? [];
$phase2Buckets = $phase2['per_15_minute'] ?? [];


$headExtras = <<<HTML
<style>
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
.stl-separator {
          display: flex;
          align-items: center;
          justify-content: center;
          gap: 8px;
          color: #94a3b8;
          font-size: 12px;
          padding: 6px 0;
}
.stl-separator::before,
.stl-separator::after {
          content: '';
          flex: 1;
          height: 1px;
          background: #1f2f4a;
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
.stl-cell {
          min-height: 20px;
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
          transition: border-color 120ms ease, transform 120ms ease;
}
.stl-event:hover {
          border-color: #38bdf8;
          transform: translateY(-1px);
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
.phase2-sections {
          display: flex;
          flex-direction: column;
          gap: 1rem;
          margin-bottom: 1.5rem;
}
.phase2-grid {
          display: grid;
          grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
          gap: 12px;
}
.phase2-panel {
          background: #0b172b;
          border: 1px solid #1e2b40;
          border-radius: 10px;
          padding: 12px;
}
.phase2-panel-header {
          display: flex;
          align-items: center;
          justify-content: space-between;
          margin-bottom: 10px;
}
.phase2-panel-title {
          font-size: 14px;
          font-weight: 600;
}
.phase2-panel-note {
          font-size: 11px;
          color: #94a3b8;
          letter-spacing: 0.04em;
          text-transform: uppercase;
}
.phase2-card {
          background: #0f1c33;
          border: 1px solid #1f2f4a;
          border-radius: 10px;
          padding: 10px;
          display: flex;
          flex-direction: column;
          gap: 4px;
}
.phase2-card-label {
          font-size: 12px;
          color: #94a3b8;
          text-transform: uppercase;
          letter-spacing: 0.06em;
}
.phase2-card-values {
          display: flex;
          width: 100%;
          justify-content: space-between;
          font-size: 1.65rem;
          font-weight: 700;
}
.phase2-card-values span {
          flex: 1;
}
.phase2-highlight {
          font-size: 12px;
          color: #cbd5e1;
}
.half-cue {
          font-size: 12px;
          color: #94a3b8;
}
.distribution-table {
          display: flex;
          flex-direction: column;
          gap: 8px;
}
.distribution-row {
          display: flex;
          justify-content: space-between;
          align-items: center;
          padding: 10px 12px;
          border: 1px solid #1f2f4a;
          border-radius: 8px;
          background: #0f1c33;
}
.distribution-label {
          font-size: 12px;
          font-weight: 600;
          color: #cbd5e1;
}
.distribution-values {
          display: flex;
          gap: 12px;
          font-weight: 600;
}
.distribution-unknown {
          font-size: 11px;
          color: #94a3b8;
}
@media (max-width: 768px) {
          .phase2-grid {
                    grid-template-columns: 1fr;
          }
          .distribution-row {
                    flex-direction: column;
                    align-items: flex-start;
          }
}
.comparison-extension {
          display: flex;
          flex-direction: column;
          gap: 1rem;
          margin-top: 1rem;
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
.momentum-panel {
          background: #0b172b;
          border: 1px solid #1e2b40;
          border-radius: 10px;
          padding: 14px;
          margin-top: 1rem;
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
</style>
HTML;

$setPieces = $totals['set_pieces'] ?? $defaultCounts;
$cards = $totals['cards'] ?? $defaultCounts;
$highlights = $totals['highlights']['total'] ?? 0;

function display_event_label(array $ev): string
{
          $key = $ev['event_type_key'] ?? '';
          $notes = trim((string)($ev['notes'] ?? ''));
          if ($key === 'period_start' || $key === 'period_end') {
                    $base = $notes !== '' ? $notes : 'Period';
                    $suffix = $key === 'period_start' ? 'Start' : 'End';
                    return $base . ' ' . $suffix;
          }
          return $ev['event_type_label'] ?? 'Event';
}

$lastEventSecond = 0;
foreach ($events as $event) {
          $lastEventSecond = max($lastEventSecond, (int)($event['match_second'] ?? 0));
}

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
                              $teamSide = normalize_team_side_value($event['team_side'] ?? 'unknown');
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

// Precompute cumulative scores for headers and full time.
$runningScore = ['home' => 0, 'away' => 0];
$periodEndScores = [];
foreach ($orderedLabels as $label) {
          $rows = $timeline[$label] ?? [];
          usort($rows, function ($a, $b) {
                    return ((int)($a['match_second'] ?? 0)) <=> ((int)($b['match_second'] ?? 0));
          });
          foreach ($rows as $ev) {
                    $k = strtolower((string)($ev['event_type_key'] ?? ''));
                    $lbl = strtolower((string)($ev['event_type_label'] ?? ''));
                    if ((strpos($k, 'goal') !== false) || (strpos($lbl, 'goal') !== false)) {
                              if ($ev['team_side'] === 'home') {
                                        $runningScore['home']++;
                              } elseif ($ev['team_side'] === 'away') {
                                        $runningScore['away']++;
                              }
                    }
          }
          $periodEndScores[$label] = $runningScore;
}

$clips = array_filter($events, fn($ev) => !empty($ev['clip_id']));

$deskUrlBase = $base . '/matches/' . (int)$match['id'] . '/desk';

ob_start();
?>
<div class="d-flex align-items-center justify-content-between mb-4">
          <div>
                    <div class="text-muted-alt text-sm">Match Summary</div>
                    <h1 class="mb-1"><?= htmlspecialchars($match['home_team']) ?> <span class="text-muted-alt">vs</span> <?= htmlspecialchars($match['away_team']) ?></h1>
                    <div class="text-muted-alt text-sm"><?= $match['kickoff_at'] ? htmlspecialchars(date('M j, Y · H:i', strtotime($match['kickoff_at']))) : 'Date TBD' ?></div>
          </div>
          <div class="d-flex align-items-center gap-2">
                    <a href="<?= htmlspecialchars($base) ?>/matches" class="btn btn-secondary-soft btn-sm">Back to Matches</a>
                    <a href="<?= htmlspecialchars($deskUrlBase) ?>" class="btn btn-primary-soft btn-sm">Analyse</a>
          </div>
</div>

<?php if ($flashSuccess): ?>
          <div class="alert alert-success"><?= htmlspecialchars($flashSuccess) ?></div>
<?php endif; ?>

<div class="panel p-3 rounded-md mb-4">
          <div class="summary-score d-flex align-items-center justify-content-center mb-3">
                    <div class="summary-team text-end me-3">
                              <div class="text-muted-alt text-sm">Home</div>
                              <div class="fw-semibold"><?= htmlspecialchars($match['home_team']) ?></div>
                    </div>
                    <div class="summary-scoreline text-center">
                              <div class="score-number"><?= (int)($safe('goal')['home'] ?? 0) ?> : <?= (int)($safe('goal')['away'] ?? 0) ?></div>
                              <div class="text-muted-alt text-xs"><?= htmlspecialchars($match['competition'] ?? '') ?></div>
                    </div>
                    <div class="summary-team text-start ms-3">
                              <div class="text-muted-alt text-sm">Away</div>
                              <div class="fw-semibold"><?= htmlspecialchars($match['away_team']) ?></div>
                    </div>
          </div>

          <div class="comparison-list">
                    <?php
                    $rows = [
                              ['label' => 'Goals', 'key' => 'goal'],
                              ['label' => 'Shots', 'key' => 'shot'],
                              ['label' => 'Chances', 'key' => 'chance'],
                              ['label' => 'Set Pieces', 'key' => null, 'value' => $setPieces],
                              ['label' => 'Fouls', 'key' => 'foul'],
                              ['label' => 'Yellow Cards', 'key' => 'yellow_card'],
                              ['label' => 'Red Cards', 'key' => 'red_card'],
                              ['label' => 'Mistakes', 'key' => 'mistake'],
                              ['label' => 'Good Play', 'key' => 'good_play'],
                              ['label' => 'Highlights', 'key' => null, 'value' => ['home' => $byType['highlight']['home'] ?? 0, 'away' => $byType['highlight']['away'] ?? 0, 'unknown' => $byType['highlight']['unknown'] ?? 0], 'total' => $highlights],
                    ];
                    foreach ($rows as $row):
                              $counts = $row['key'] ? $safe($row['key']) : ($row['value'] ?? $defaultCounts);
                              $homeVal = (int)$counts['home'];
                              $awayVal = (int)$counts['away'];
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

<?php
// Use Phase 2 payload to power the new coach-friendly blocks below.
$overviewMetrics = [
          ['label' => 'Shots', 'key' => 'shot', 'note' => 'Phase 2 shot total'],
          ['label' => 'Goals', 'key' => 'goal', 'note' => 'Phase 2 goal total'],
          ['label' => 'Corners', 'key' => 'corner', 'note' => 'Phase 2 corner total'],
          ['label' => 'Fouls', 'key' => 'foul', 'note' => 'Phase 2 foul total'],
];
$firstHalfCounts = $phase2ByPeriod['1H'] ?? $defaultCounts;
$secondHalfCounts = $phase2ByPeriod['2H'] ?? $defaultCounts;
$homeHalfShift = (int)$secondHalfCounts['home'] - (int)$firstHalfCounts['home'];
$awayHalfShift = (int)$secondHalfCounts['away'] - (int)$firstHalfCounts['away'];
          $homeHalfCue = $homeHalfShift > 0 ? 'Home activity rose after HT' : ($homeHalfShift < 0 ? 'Home settled after HT' : 'Home activity steady');
          $awayHalfCue = $awayHalfShift > 0 ? 'Away pressed harder after HT' : ($awayHalfShift < 0 ? 'Away activity cooled after HT' : 'Away activity steady');
          // Build period rows from derived stats while preserving official period labels where available.
          $periodCategories = [
                    '1H' => 'First Half',
                    '2H' => 'Second Half',
                    'ET' => 'Extra Time',
          ];
          $periodLabelOverrides = [];
          foreach ($matchPeriods as $period) {
                    $periodCategory = resolve_period_category($period);
                    if (isset($periodCategories[$periodCategory]) && !isset($periodLabelOverrides[$periodCategory])) {
                              $periodLabelOverrides[$periodCategory] = $period['label'];
                    }
          }
          $periodRows = [];
          foreach ($periodCategories as $category => $defaultLabel) {
                    $counts = $phase2ByPeriod[$category] ?? $defaultCounts;
                    $periodRows[] = [
                              'label' => $periodLabelOverrides[$category] ?? $defaultLabel,
                              'home' => (int)$counts['home'],
                              'away' => (int)$counts['away'],
                    ];
          }
          // Count phases directly using event.phase values, forcing unknown when absent.
          $phaseBuckets = ['build_up', 'transition', 'defensive_block', 'set_piece', 'unknown'];
          $phaseTeamCounts = [];
          foreach (['home', 'away', 'unknown'] as $side) {
                    $phaseTeamCounts[$side] = array_fill_keys($phaseBuckets, 0);
          }
          foreach ($events as $event) {
                    $phaseKey = trim((string)($event['phase'] ?? ''));
                    if (!in_array($phaseKey, $phaseBuckets, true)) {
                              $phaseKey = 'unknown';
                    }
                    $teamSide = normalize_team_side_value($event['team_side'] ?? 'unknown');
                    $phaseTeamCounts[$teamSide][$phaseKey]++;
          }
          $periodLabel1 = $periodRows[0]['label'] ?? 'First Half';
          $periodLabel2 = $periodRows[1]['label'] ?? 'Second Half';
          $periodInsights = [
                    sprintf('Derived stats record %d home events in %s and %d in %s.', (int)$firstHalfCounts['home'], $periodLabel1, (int)$secondHalfCounts['home'], $periodLabel2),
                    sprintf('Derived stats record %d away events in %s and %d in %s.', (int)$firstHalfCounts['away'], $periodLabel1, (int)$secondHalfCounts['away'], $periodLabel2),
          ];
          $phaseInsights = [
                    sprintf('Phase tags show Away logged %d set_piece actions compared to %d for Home.', $phaseTeamCounts['away']['set_piece'], $phaseTeamCounts['home']['set_piece']),
                    sprintf('Unknown team-side events cover %d phase-tagged entries, keeping the unknown bucket explicit.', array_sum($phaseTeamCounts['unknown'])),
          ];
          $observationLines = array_merge($periodInsights, $phaseInsights);
?>
<div class="phase2-sections">
          <div class="phase2-grid">
                    <div class="phase2-panel">
                              <div class="phase2-panel-header">
                                        <div class="phase2-panel-title">Match overview</div>
                                        <div class="phase2-panel-note">Totals by team</div>
                              </div>
                              <!-- Phase 2 by_type_team totals surface here to reinforce the summary -->
                              <div class="phase2-grid">
                                        <?php foreach ($overviewMetrics as $metric):
                                                  $counts = $safe($metric['key'] ?? '');
                                                  $homeVal = (int)$counts['home'];
                                                  $awayVal = (int)$counts['away'];
                                        ?>
                                                  <div class="phase2-card">
                                                            <div class="phase2-card-label"><?= htmlspecialchars($metric['label']) ?></div>
                                                            <div class="phase2-card-values">
                                                                      <span class="text-start"><?= $homeVal ?> <small>H</small></span>
                                                                      <span class="text-end"><?= $awayVal ?> <small>A</small></span>
                                                            </div>
                                                            <div class="phase2-highlight"><?= htmlspecialchars($metric['note']) ?></div>
                                                  </div>
                                        <?php endforeach; ?>
                              </div>
                    </div>
                    <div class="phase2-panel">
                              <div class="phase2-panel-header">
                                        <div class="phase2-panel-title">First Half vs Second Half</div>
                                        <div class="phase2-panel-note">Per-team comparison</div>
                              </div>
                              <!-- Phase 2 by_period counts highlight how activity shifted between 1H and 2H -->
                              <div class="phase2-grid">
                                        <div class="phase2-card">
                                                  <div class="phase2-card-label">First Half</div>
                                                  <div class="phase2-card-values">
                                                            <span><?= (int)$firstHalfCounts['home'] ?> <small>H</small></span>
                                                            <span><?= (int)$firstHalfCounts['away'] ?> <small>A</small></span>
                                                  </div>
                                                  <div class="half-cue"><?= $homeHalfCue ?> · <?= $awayHalfCue ?></div>
                                        </div>
                                        <div class="phase2-card">
                                                  <div class="phase2-card-label">Second Half</div>
                                                  <div class="phase2-card-values">
                                                            <span><?= (int)$secondHalfCounts['home'] ?> <small>H</small></span>
                                                            <span><?= (int)$secondHalfCounts['away'] ?> <small>A</small></span>
                                                  </div>
                                                  <div class="half-cue"><?= $homeHalfCue ?> · <?= $awayHalfCue ?></div>
                                        </div>
                              </div>
                    </div>
          </div>
          <div class="phase2-grid">
                    <div class="phase2-panel">
                              <div class="phase2-panel-header">
                                        <div class="phase2-panel-title">Set pieces &amp; discipline</div>
                                        <div class="phase2-panel-note">Combined derived totals</div>
                              </div>
                              <!-- Derived stats already combine corners/free kicks/penalties and card counts -->
                              <div class="phase2-card">
                                        <div class="phase2-card-label">Set pieces (corners + free kicks + penalties)</div>
                                        <div class="phase2-card-values">
                                                  <span><?= (int)$setPieces['home'] ?> <small>H</small></span>
                                                  <span><?= (int)$setPieces['away'] ?> <small>A</small></span>
                                        </div>
                                        <div class="phase2-highlight">Aggregated from derived stats totals</div>
                              </div>
                              <div class="phase2-card">
                                        <div class="phase2-card-label">Cards (yellow + red)</div>
                                        <div class="phase2-card-values">
                                                  <span><?= (int)$cards['home'] ?> <small>H</small></span>
                                                  <span><?= (int)$cards['away'] ?> <small>A</small></span>
                                        </div>
                                        <div class="phase2-highlight">Discipline totals from derived stats</div>
                              </div>
                    </div>
                    <div class="phase2-panel">
                              <div class="phase2-panel-header">
                                        <div class="phase2-panel-title">Event distribution</div>
                                        <div class="phase2-panel-note">Phase 2 · 15 minute buckets</div>
                              </div>
                              <!-- phase_2.per_15_minute buckets drive the per-interval counts below -->
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
                                                            ?>
                                                                      <div class="distribution-row">
                                                                                <div>
                                                                                          <div class="distribution-label"><?= htmlspecialchars($label) ?></div>
                                                                                          <div class="distribution-unknown"><?= $bucketTotal ?> events · <?= $unknownBucket ?> unassigned</div>
                                                                                </div>
                                                                                <div class="distribution-values">
                                                                                          <span>H <?= $homeBucket ?></span>
                                                                                          <span>A <?= $awayBucket ?></span>
                                                                                </div>
                                                                      </div>
                                                            <?php endforeach; ?>
                                                  <?php endif; ?>
                                        </div>
                              </div>
                    </div>
          </div>
</div>

<div class="panel p-3 rounded-md mb-4">
          <div class="panel-header d-flex align-items-center justify-content-between mb-2">
                    <h5 class="mb-0 text-light">Period &amp; Phase snapshots</h5>
                    <div class="text-muted-alt text-sm">Period totals map to derived_stats.phase_2.by_period; phases come from event.phase</div>
          </div>
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
                    <?php if (!empty($observationLines)): ?>
                              <ul class="observation-list">
                                        <?php foreach ($observationLines as $line): ?>
                                                  <li><?= htmlspecialchars($line) ?></li>
                                        <?php endforeach; ?>
                              </ul>
                    <?php endif; ?>
</div>
</div>

<div class="panel p-3 rounded-md mb-3">
          <div class="panel-header d-flex align-items-center justify-content-between mb-2">
                    <h5 class="mb-0 text-light">Descriptive momentum (not predictive)</h5>
                    <div class="text-muted-alt text-sm">Rolling 5-minute windows · weighted by event type and importance</div>
          </div>
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

<div class="panel p-3 rounded-md mb-3">
          <div class="panel-header d-flex align-items-center justify-content-between mb-2">
                    <h5 class="mb-0 text-light">Timeline</h5>
                    <div class="text-muted-alt text-sm">Jump to desk time</div>
          </div>
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
                                        $startMinute = isset($periodWindows[$periodLabel]['start']) ? (int)floor((int)$periodWindows[$periodLabel]['start'] / 60) : 0;
                                        $endMinute = isset($periodWindows[$periodLabel]['end']) ? (int)floor((int)$periodWindows[$periodLabel]['end'] / 60) : null;
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
                                                                      $jumpUrl = $deskUrlBase . '?t=' . max(0, (int)$ev['match_second']);
                                                                      $eventLabel = display_event_label($ev);
                                                                      $minuteVal = isset($ev['minute']) && $ev['minute'] !== '' ? (int)$ev['minute'] : floor((int)($ev['match_second'] ?? 0) / 60);
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
                                                                                                    <a class="stl-event" href="<?= htmlspecialchars($jumpUrl) ?>">
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
                                                                                                    </a>
                                                                                          <?php endif; ?>
                                                                                </div>
                                                                                <div class="stl-cell stl-away">
                                                                                          <?php if ($side === 'away'): ?>
                                                                                                    <a class="stl-event" href="<?= htmlspecialchars($jumpUrl) ?>">
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
                                                                                                    </a>
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

<div class="panel p-3 rounded-md">
          <div class="panel-header d-flex align-items-center justify-content-between mb-2">
                    <h5 class="mb-0 text-light">Clips</h5>
                    <div class="text-muted-alt text-sm">Events with saved clips</div>
          </div>
          <?php if (empty($clips)): ?>
                    <div class="text-muted-alt text-sm">No clips yet.</div>
          <?php else: ?>
                    <div class="table-responsive">
                              <table class="table table-dark table-sm align-middle mb-0">
                                        <thead>
                                                  <tr>
                                                            <th scope="col">Event</th>
                                                            <th scope="col">Team</th>
                                                            <th scope="col">Time</th>
                                                            <th scope="col">Clip</th>
                                                            <th scope="col" class="text-end">Actions</th>
                                                  </tr>
                                        </thead>
                                        <?php foreach ($clips as $clipEv): ?>
                                                  <?php
                                                  $start = (int)$clipEv['clip_start_second'];
                                                  $end = (int)$clipEv['clip_end_second'];
                                                  $duration = max(0, $end - $start);
                                                  $jumpUrl = $deskUrlBase . '?t=' . $start;
                                                  $clipLabel = display_event_label($clipEv);
                                                  $clipTimeLabel = formatMatchSecondText((int)$clipEv['match_second']);
                                                  if (!empty($clipEv['minute_extra'])) {
                                                            $clipTimeLabel .= '+' . (int)$clipEv['minute_extra'];
                                                  }
                                                  ?>
                                                  <tr>
                                                            <td>
                                                                      <div class="fw-semibold"><?= htmlspecialchars($clipLabel) ?></div>
                                                                      <div class="text-muted-alt text-xs"><?= (int)$clipEv['minute'] ?>' - <?= htmlspecialchars($clipTimeLabel) ?></div>
                                                            </td>
                                                            <td><span class="team-badge <?= $clipEv['team_side'] === 'home' ? 'badge-home' : ($clipEv['team_side'] === 'away' ? 'badge-away' : 'badge-unknown') ?>"><?= htmlspecialchars(ucfirst($clipEv['team_side'] ?? 'unknown')) ?></span></td>
                                                            <td><?= htmlspecialchars($clipTimeLabel) ?></td>
                                                            <td><?= htmlspecialchars(formatMatchSecondText($start)) ?> - <?= htmlspecialchars(formatMatchSecondText($end)) ?> (<?= (int)$duration ?>s)</td>
                                                            <td class="text-end">
                                                                      <a href="<?= htmlspecialchars($jumpUrl) ?>" class="btn-icon btn-icon-primary" data-bs-toggle="tooltip" data-bs-title="Open in desk">
                                                                                <i class="fa-solid fa-play"></i>
                                                                      </a>
                                                            </td>
                                                  </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                              </table>
                    </div>
          <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
$footerScripts = <<<HTML
<script>
document.addEventListener('DOMContentLoaded', function () {
          var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
          tooltipTriggerList.forEach(function (tooltipTriggerEl) {
                    new bootstrap.Tooltip(tooltipTriggerEl);
          });
});
</script>
HTML;
require __DIR__ . '/../../layout.php';
