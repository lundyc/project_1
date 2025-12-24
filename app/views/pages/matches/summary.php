<?php
require_auth();

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
</style>
HTML;

$setPieces = $totals['set_pieces'] ?? $defaultCounts;
$cards = $totals['cards'] ?? $defaultCounts;
$highlights = $totals['highlights']['total'] ?? 0;

function format_mmss(int $seconds): string
{
          $seconds = max(0, $seconds);
          $m = floor($seconds / 60);
          $s = $seconds % 60;
          return sprintf('%02d:%02d', $m, $s);
}

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
                                                  ?>
                                                  <tr>
                                                            <td>
                                                                      <div class="fw-semibold"><?= htmlspecialchars($clipLabel) ?></div>
                                                                      <div class="text-muted-alt text-xs"><?= (int)$clipEv['minute'] ?>' - <?= htmlspecialchars(format_mmss((int)$clipEv['match_second'])) ?></div>
                                                            </td>
                                                            <td><span class="team-badge <?= $clipEv['team_side'] === 'home' ? 'badge-home' : ($clipEv['team_side'] === 'away' ? 'badge-away' : 'badge-unknown') ?>"><?= htmlspecialchars(ucfirst($clipEv['team_side'] ?? 'unknown')) ?></span></td>
                                                            <td><?= htmlspecialchars(format_mmss((int)$clipEv['match_second'])) ?></td>
                                                            <td><?= htmlspecialchars(format_mmss($start)) ?> - <?= htmlspecialchars(format_mmss($end)) ?> (<?= (int)$duration ?>s)</td>
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
