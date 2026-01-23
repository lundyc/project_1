<?php
require_auth();
require_once __DIR__ . '/../../../lib/time_helper.php';

$base = base_path();
$title = 'Match Stats';

$flashSuccess = $_SESSION['stats_flash_success'] ?? null;
unset($_SESSION['stats_flash_success']);

// Set default values for match display variables
$competition = $match['competition'] ?? ($match['competition_name'] ?? 'Competition');
$matchStatusLabel = $match['status'] ?? 'Scheduled';

// Build match score from available score fields
$matchScore = null;
$scorePairs = [
    ['home_score', 'away_score'],
    ['home_team_score', 'away_team_score'],
    ['home_goals', 'away_goals'],
];
foreach ($scorePairs as [$homeKey, $awayKey]) {
    if (isset($match[$homeKey], $match[$awayKey]) && $match[$homeKey] !== null && $match[$awayKey] !== null) {
        $matchScore = sprintf('%s - %s', $match[$homeKey], $match[$awayKey]);
        break;
    }
}

$byType = $derivedStats['by_type_team'] ?? [];
$totals = $derivedStats['totals'] ?? [];

$defaultCounts = ['home' => 0, 'away' => 0, 'unknown' => 0];
$safe = function (string $key) use ($byType, $defaultCounts) {
          return $byType[$key] ?? $defaultCounts;
};

function gauge_arc_path(float $share, float $offset): ?string
{
          if ($share <= 0) {
                    return null;
          }
          $startAngle = M_PI + M_PI * $offset;
          $endAngle = M_PI + M_PI * min(1, $offset + $share);
          $startX = 60 + 50 * cos($startAngle);
          $startY = 60 + 50 * sin($startAngle);
          $endX = 60 + 50 * cos($endAngle);
          $endY = 60 + 50 * sin($endAngle);
          $largeArcFlag = ($share >= 1 - 1e-6) ? 1 : 0;
          return sprintf('M %.2f %.2f A 50 50 0 %d 1 %.2f %.2f', $startX, $startY, $largeArcFlag, $endX, $endY);
}

function build_overview_gauge(int $home, int $away): array
{
          $total = max(0, $home + $away);
          if ($total === 0) {
                    return [
                              'homePercent' => 0,
                              'awayPercent' => 0,
                              'total' => 0,
                              'homePath' => null,
                              'awayPath' => null,
                    ];
          }
          $homeShare = $home / $total;
          $awayShare = $away / $total;
          $clampedHome = round(min(1, max(0, $homeShare)), 10);
          $clampedAway = round(min(1, max(0, $awayShare)), 10);
          return [
                    'homePercent' => (int)round($clampedHome * 100),
                    'awayPercent' => (int)round($clampedAway * 100),
                    'total' => $total,
                    'homePath' => gauge_arc_path($clampedHome, 0),
                    'awayPath' => gauge_arc_path($clampedAway, $clampedHome),
          ];
}

function render_graph_card(string $label, int $homeVal, int $awayVal, string $note = ''): string
{
          $gauge = build_overview_gauge($homeVal, $awayVal);
          $ariaLabel = htmlspecialchars(sprintf('%s home share %d%% away share %d%%', $label, $gauge['homePercent'], $gauge['awayPercent']));
          ob_start();
          ?>
          <div class="overview-graph-card">
                    <div class="phase2-card-label"><?= htmlspecialchars($label) ?></div>
                    <?php if ($note): ?>
                              <div class="phase2-highlight"><?= htmlspecialchars($note) ?></div>
                    <?php endif; ?>
                    <div class="overview-gauge-wrapper">
                              <div class="overview-gauge" role="img" aria-label="<?= $ariaLabel ?>">
                                        <svg viewBox="0 0 120 60" aria-hidden="true">
                                                  <path class="overview-gauge-base" d="M 10 60 A 50 50 0 0 1 110 60"></path>
                                                  <?php if ($gauge['homePath']): ?>
                                                            <path class="overview-gauge-fill overview-gauge-home" d="<?= $gauge['homePath'] ?>"></path>
                                                  <?php endif; ?>
                                                  <?php if ($gauge['awayPath']): ?>
                                                            <path class="overview-gauge-fill overview-gauge-away" d="<?= $gauge['awayPath'] ?>"></path>
                                                  <?php endif; ?>
                                        </svg>
                                        <div class="overview-gauge-labels">
                                                  <span class="overview-gauge-label-home"><?= $gauge['homePercent'] ?>%</span>
                                                  <span class="overview-gauge-label-away"><?= $gauge['awayPercent'] ?>%</span>
                                        </div>
                                        <div class="overview-gauge-center">
                                                  <div class="overview-gauge-percent"><?= $gauge['homePercent'] ?>%</div>
                                                  <div class="overview-gauge-caption">Home share</div>
                                        </div>
                              </div>
                              <div class="overview-stats">
                                        <div class="overview-stats-row overview-stats-labels">
                                                  <span class="overview-stats-label">Home</span>
                                                  <span class="overview-stats-label">Away</span>
                                        </div>
                                        <div class="overview-stats-row overview-stats-values">
                                                  <span><?= $homeVal ?></span>
                                                  <span><?= $awayVal ?></span>
                                        </div>
                                        <div class="overview-stats-row overview-stats-values">
                                                  <span><?= $gauge['homePercent'] ?>%</span>
                                                  <span><?= $gauge['awayPercent'] ?>%</span>
                                        </div>
                              </div>
                    </div>
          </div>
          <?php
          return ob_get_clean();
}

function render_shot_accuracy_card(
          string $label,
          string $teamName,
          string $onLabel,
          string $offLabel,
          int $onTarget,
          int $offTarget,
          int $onPct,
          int $offPct,
          int $total
): string {
          $gauge = build_overview_gauge($onTarget, $offTarget);
          ob_start();

          ?>
          <div class="overview-graph-card">
                    <div class="phase2-card-label"><?= htmlspecialchars($label) ?></div>
                    <div class="overview-gauge-wrapper">
                              <div class="overview-gauge" role="img" aria-label="<?= htmlspecialchars($teamName . ' on target share ' . $onPct . '%') ?>">
                                        <svg viewBox="0 0 120 60" aria-hidden="true">
                                                  <path class="overview-gauge-base" d="M 10 60 A 50 50 0 0 1 110 60"></path>
                                                  <?php if ($gauge['homePath']): ?>
                                                            <path class="overview-gauge-fill overview-gauge-home" d="<?= $gauge['homePath'] ?>"></path>
                                                  <?php endif; ?>
                                                  <?php if ($gauge['awayPath']): ?>
                                                            <path class="overview-gauge-fill overview-gauge-away" d="<?= $gauge['awayPath'] ?>"></path>
                                                  <?php endif; ?>
                                        </svg>
                                        <div class="overview-gauge-labels">
                                                  <span class="overview-gauge-label-home"><?= $onPct ?>%</span>
                                                  <span class="overview-gauge-label-away"><?= $offPct ?>%</span>
                                        </div>
                                        <div class="overview-gauge-center">
                                                  <div class="overview-gauge-percent"><?= $onPct ?>%</div>
                                                  <div class="overview-gauge-caption"><?= htmlspecialchars($teamName) ?> on target share</div>
                                        </div>
                              </div>
                              <div class="overview-stats">
                                        <div class="overview-stats-row overview-stats-labels">
                                                  <span class="overview-stats-label"><?= htmlspecialchars($onLabel) ?></span>
                                                  <span class="overview-stats-label"><?= htmlspecialchars($offLabel) ?></span>
                                        </div>
                                        <div class="overview-stats-row overview-stats-values">
                                                  <span><?= $onTarget ?></span>
                                                  <span><?= $offTarget ?></span>
                                        </div>
                                        <div class="overview-stats-row overview-stats-values">
                                                  <span><?= $onPct ?>%</span>
                                                  <span><?= $offPct ?>%</span>
                                        </div>
                              </div>
                              <div class="overview-gauge-note text-xs text-muted-alt">
                                        <?= $total ? htmlspecialchars(sprintf('%d of %d shots', $onTarget, $total)) : 'No shots recorded' ?>
                              </div>
                    </div>
          </div>
          <?php
          return ob_get_clean();
}

$phase2 = $derivedStats['phase_2'] ?? []; // Phase 2 summary data from derived_stats.payload_json
$phase2ByPeriod = $phase2['by_period'] ?? [];
$phase2Buckets = $phase2['per_15_minute'] ?? [];


$headExtras = <<<HTML
<style>
.match-hero {
    border: 1px solid var(--border-primary);
    border-radius: 12px;
    background: linear-gradient(135deg, var(--surface-2) 0%, var(--surface-1) 100%);
    box-shadow: 0 10px 30px var(--shadow-strong);
}
.match-hero-left {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.match-hero-eyebrow, .match-hero-separator, .match-hero-meta-label {
    color: var(--text-muted);
}
.match-hero-title, .match-hero-score {
    color: var(--text-primary);
}
.match-hero-subtitle, .match-hero-status, .stl-period-header, .stl-player {
    color: var(--text-secondary);
}
.stl-period {
    background: var(--surface-1);
    border: 1px solid var(--border-primary);
}
.stl-period-score {
    background: var(--surface-2);
    border: 1px solid var(--border-secondary);
    color: var(--text-tertiary);
}
.stl-separator, .stl-minute {
    color: var(--text-tertiary);
}
.stl-separator::before, .stl-separator::after {
    background: var(--surface-3);
}
.stl-event {
    background: var(--surface-4);
    border: 1px solid var(--border-secondary);
    color: var(--text-tertiary);
}
.stl-event:hover {
    border-color: var(--accent-info);
}
.stl-card {
    color: var(--accent-warning);
}
.stl-card.red {
    color: var(--accent-danger);
}
.stl-title {
    color: var(--text-tertiary);
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
    background: var(--surface-1);
    border: 1px solid var(--border-secondary);
    border-radius: 10px;
    padding: 10px 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-weight: 700;
    color: var(--text-tertiary);
}
.stl-fulltime small {
    color: var(--text-tertiary);
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
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}
.phase2-panel-column {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.phase2-panel {
    background: var(--surface-1);
    border: 1px solid var(--border-primary);
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
    color: var(--text-muted);
    letter-spacing: 0.04em;
    text-transform: uppercase;
}
.phase2-card {
    background: var(--surface-2);
    border: 1px solid var(--border-secondary);
    border-radius: 10px;
    padding: 10px;
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.phase2-card-label {
    font-size: 12px;
    color: var(--text-muted);
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
    color: var(--text-muted);
}
.half-cue {
    font-size: 12px;
    color: var(--text-muted);
}
.distribution-table {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.distribution-legend {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    font-size: 11px;
    letter-spacing: 0.06em;
    color: var(--text-muted);
}
.distribution-legend-pill {
    text-transform: uppercase;
    border-radius: 999px;
    padding: 4px 10px;
    font-weight: 600;
    font-size: 10px;
}
.distribution-legend-pill-home {
    background: rgba(59, 130, 246, 0.15);
    color: var(--text-primary);
}
.distribution-legend-pill-away {
    background: rgba(251, 146, 60, 0.15);
    color: var(--text-primary);
}
.distribution-legend-pill-unknown {
    background: rgba(148, 163, 184, 0.15);
    color: var(--text-muted);
}
.distribution-row {
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding: 14px;
    border: 1px solid var(--surface-3);
    border-radius: 10px;
    background: var(--surface-1);
}
.distribution-row-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
}
.distribution-label {
    font-size: 12px;
    font-weight: 600;
    color: var(--text-muted);
}
.distribution-total {
    font-size: 11px;
    color: var(--text-muted);
}
.distribution-info {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.distribution-chart {
    display: flex;
    height: 10px;
    border-radius: 999px;
    overflow: hidden;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.08);
}
.distribution-segment {
    display: block;
    height: 100%;
}
.distribution-segment-home {
    background: linear-gradient(90deg, #3b82f6, #0ea5e9);
}
.distribution-segment-away {
    background: linear-gradient(90deg, #fb923c, #f97316);
}
.distribution-segment-unknown {
    background: linear-gradient(90deg, #94a3b8, #64748b);
}
.distribution-pill-row {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.distribution-pill {
    font-size: 11px;
    font-weight: 600;
    padding: 6px 12px;
    border-radius: 999px;
    color: var(--text-primary);
    text-transform: none;
}
.distribution-pill-home {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.25), rgba(14, 165, 233, 0.45));
}
.distribution-pill-away {
    background: linear-gradient(135deg, rgba(251, 146, 60, 0.25), rgba(249, 115, 22, 0.45));
}
.distribution-pill-unknown {
    background: linear-gradient(135deg, rgba(148, 163, 184, 0.25), rgba(100, 116, 139, 0.45));
}
@media (max-width: 768px) {
    .phase2-grid {
        grid-template-columns: 1fr;
    }
    .distribution-row-heading {
        flex-direction: column;
        align-items: flex-start;
    }
    .distribution-pill-row {
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
    background: var(--surface-1);
    border: 1px solid var(--border-primary);
    border-radius: 10px;
    padding: 12px;
}
.period-section-note,
.phase-section-note {
    font-size: 11px;
    color: var(--text-muted);
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
    color: var(--text-muted);
    text-transform: uppercase;
}
.period-label,
.phase-label {
    font-weight: 600;
    color: var(--text-tertiary);
}
.observation-list {
    margin: 0;
    padding-left: 1.25rem;
    display: flex;
    flex-direction: column;
    gap: 4px;
    font-size: 13px;
    color: var(--text-muted);
}
.momentum-panel {
    background: var(--surface-1);
    border: 1px solid var(--border-primary);
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
    color: var(--text-muted);
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
    color: var(--text-tertiary);
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
.match-overview-graphs {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
    margin-top: 12px;
}
.overview-graph-card {
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding: 16px;
    background: var(--surface-1);
    border: 1px solid var(--border-primary);
    border-radius: 14px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.35);
}
.overview-graph-card .phase2-card-label {
    margin-bottom: 0;
    text-transform: uppercase;
    letter-spacing: 0.18em;
    font-size: 13px;
    color: var(--text-muted);
}
.overview-gauge-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 16px;
}
.overview-gauge {
    position: relative;
    width: 160px;
    min-height: 80px;
    margin: 0 auto;
}
.overview-gauge svg {
    width: 160px;
    height: 80px;
    display: block;
}
.overview-gauge path {
    fill: none;
    stroke-width: 14;
    stroke-linecap: butt;
}
.overview-gauge-base {
    stroke: rgba(255, 255, 255, 0.15);
}
.overview-gauge-fill {
    opacity: 0.95;
}
.overview-gauge-fill.overview-gauge-away {
    stroke: #f97316;
}
.overview-gauge-fill.overview-gauge-home {
    stroke: #3b82f6;
}
.overview-gauge-center {
    position: absolute;
    top: 36px;
    left: 50%;
    transform: translateX(-50%);
    text-align: center;
}
.overview-gauge-percent {
    font-size: 1.4rem;
    font-weight: 700;
    color: #F4F7FA;
}
.overview-gauge-caption {
    font-size: 10px;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: #94a3b8;
}
.overview-gauge-labels {
    position: absolute;
    top: 6px;
    left: 10px;
    right: 10px;
    display: flex;
    justify-content: space-between;
    font-size: 11px;
    text-transform: uppercase;
}
.overview-gauge-label-home {
    color: #3b82f6;
}
.overview-gauge-label-away {
    color: #f97316;
    text-align: right;
}
.overview-stats {
    width: 100%;
}
.overview-stats-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
}
.overview-stats-labels {
    text-transform: uppercase;
    justify-items: center;
    text-align: center;
                    border-bottom: 1px solid rgba(255, 255, 255, 0.12);
    padding-bottom: 2px;
}

.overview-stats-row span {
    text-align: center;
}
.overview-stats-label {
    font-size: 10px;
    letter-spacing: 0.12em;
    color: #8fa4c7;

}
.overview-stats-label:last-child {
    text-align: right;
}
.overview-stats-values {
    font-size: 14px;
    font-weight: 600;
    color: #fdfdfd;
}
.overview-stats-values span:last-child {
    text-align: center;
}
.overview-gauge-meta {
    display: flex;
    flex-direction: column;
    gap: 4px;
    font-size: 12px;
    color: #e2e8f0;
}
.overview-gauge-value {
    font-weight: 600;
}
.overview-gauge-total {
    font-size: 11px;
    color: #94a3b8;
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
<div class="match-hero mb-4">
          <div class="match-hero-left">
                    <div class="match-hero-eyebrow">Match Stats</div>
                    <div class="match-hero-title">
                              <?= htmlspecialchars($match['home_team']) ?> <span class="match-hero-separator">vs</span> <?= htmlspecialchars($match['away_team']) ?>
                    </div>
                    <div class="match-hero-subtitle">
                              <?= $match['kickoff_at'] ? htmlspecialchars(date('M j, Y · H:i', strtotime($match['kickoff_at']))) : 'Date TBD' ?> · <?= htmlspecialchars($competition) ?>
                    </div>
          </div>
          <div class="match-hero-right">
                    <div class="match-hero-meta">
                              <span class="match-hero-meta-label">Score</span>
                              <span class="match-hero-score"><?= htmlspecialchars($matchScore ?? '—') ?></span>
                              <span class="match-hero-status text-uppercase"><?= htmlspecialchars($matchStatusLabel) ?></span>
                    </div>
                    <div class="match-hero-actions">
                              <a href="<?= htmlspecialchars($base) ?>/matches" class="btn btn-secondary-soft btn-sm">Back to Matches</a>
                              <a href="<?= htmlspecialchars($deskUrlBase) ?>" class="btn btn-primary-soft btn-sm">Analyse</a>
                    </div>
          </div>
</div>

<?php if ($flashSuccess): ?>
          <div class="alert alert-success"><?= htmlspecialchars($flashSuccess) ?></div>
<?php endif; ?>

<div class="panel p-3 rounded-md mb-4">
          <?php require __DIR__ . '/../../partials/match-summary-stats.php'; ?>
</div>

<?php
// Use Phase 2 payload to power the new coach-friendly blocks below.
$overviewMetrics = [
          ['label' => 'Shots', 'key' => 'shot'],
          ['label' => 'Goals', 'key' => 'goal'],
          ['label' => 'Corners', 'key' => 'corner'],
];
$shotOnTargetCounts = $safe('shot_on_target');
$shotOffTargetCounts = $safe('shot_off_target');
$goalCounts = $safe('goal');
// Calculate totals: on_target shots + off_target shots (excluding goals from the total)
$homeShotTotal = (int)($shotOnTargetCounts['home'] ?? 0) + (int)($shotOffTargetCounts['home'] ?? 0);
$awayShotTotal = (int)($shotOnTargetCounts['away'] ?? 0) + (int)($shotOffTargetCounts['away'] ?? 0);
// On Target = on_target shots + goals scored
$homeOnTargetCount = (int)($shotOnTargetCounts['home'] ?? 0) + (int)($goalCounts['home'] ?? 0);
$awayOnTargetCount = (int)($shotOnTargetCounts['away'] ?? 0) + (int)($goalCounts['away'] ?? 0);
// Off Target = shots that were off target
$homeOffTargetCount = (int)($shotOffTargetCounts['home'] ?? 0);
$awayOffTargetCount = (int)($shotOffTargetCounts['away'] ?? 0);
// Total shots for display = on target + off target (which includes goals in on target)
$homeTotalForDisplay = $homeOnTargetCount + $homeOffTargetCount;
$awayTotalForDisplay = $awayOnTargetCount + $awayOffTargetCount;
$shotOnTargetCounts = $safe('shot_on_target');
$shotOffTargetCounts = $safe('shot_off_target');
$homeShotGauge = build_overview_gauge($homeOnTargetCount, $homeOffTargetCount);
$awayShotGauge = build_overview_gauge($awayOnTargetCount, $awayOffTargetCount);
$firstHalfCounts = $phase2ByPeriod['1H'] ?? $defaultCounts;
$secondHalfCounts = $phase2ByPeriod['2H'] ?? $defaultCounts;

$periodKeyIndex = [];
foreach ($matchPeriods as $period) {
          $periodKey = strtolower(trim((string)($period['period_key'] ?? '')));
          if ($periodKey === '') {
                    continue;
          }
          if (!isset($periodKeyIndex[$periodKey])) {
                    $periodKeyIndex[$periodKey] = $period;
          }
}

$computePeriodCountsFromEvents = function (array $events, array $period) use ($defaultCounts) {
          $counts = ['home' => 0, 'away' => 0, 'unknown' => 0];
          $start = isset($period['start_second']) ? (int)$period['start_second'] : 0;
          $end = array_key_exists('end_second', $period) && $period['end_second'] !== null ? (int)$period['end_second'] : null;
          foreach ($events as $event) {
                    $second = max(0, (int)($event['match_second'] ?? 0));
                    if ($second < $start) {
                              continue;
                    }
                    if ($end !== null && $second > $end) {
                              continue;
                    }
                    $teamSide = normalize_team_side_value($event['team_side'] ?? 'unknown');
                    $counts[$teamSide] = ($counts[$teamSide] ?? 0) + 1;
          }
          if ($counts === ['home' => 0, 'away' => 0, 'unknown' => 0]) {
                    return $defaultCounts;
          }
          return $counts;
};

if (isset($periodKeyIndex['first_half'])) {
          $firstHalfCounts = $computePeriodCountsFromEvents($events, $periodKeyIndex['first_half']);
}
if (isset($periodKeyIndex['second_half'])) {
          $secondHalfCounts = $computePeriodCountsFromEvents($events, $periodKeyIndex['second_half']);
}
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
                              <div class="match-overview-graphs">
                                        <?php foreach ($overviewMetrics as $metric):
                                                  $counts = $metric['key'] ? $safe($metric['key']) : ($metric['value'] ?? $defaultCounts);
                                                  $homeVal = (int)$counts['home'];
                                                  $awayVal = (int)$counts['away'];
                                                  echo render_graph_card($metric['label'], $homeVal, $awayVal, $metric['note'] ?? '');
                                        endforeach; ?>
                              </div>
                              <div class="match-overview-graphs">
                                        <?= render_shot_accuracy_card(
                                                  'Home shot accuracy',
                                                  $match['home_team'] ?? 'Home',
                                                  'On Target',
                                                  'Off Target',
                                                  $homeOnTargetCount,
                                                  $homeOffTargetCount,
                                                  $homeShotGauge['homePercent'],
                                                  $homeShotGauge['awayPercent'],
                                                  $homeTotalForDisplay
                                        ) ?>
                                        <?= render_shot_accuracy_card(
                                                  'Away shot accuracy',
                                                  $match['away_team'] ?? 'Away',
                                                  'On Target',
                                                  'Off Target',
                                                  $awayOnTargetCount,
                                                  $awayOffTargetCount,
                                                  $awayShotGauge['homePercent'],
                                                  $awayShotGauge['awayPercent'],
                                                  $awayTotalForDisplay
                                        ) ?>
                              </div>
                    </div>
                    <div class="phase2-panel-column">
                              <div class="phase2-panel">
                                        <div class="phase2-panel-header">
                                                  <div class="phase2-panel-title">First Half vs Second Half</div>
                                                  <div class="phase2-panel-note">Per-team comparison</div>
                                        </div>
                                        <div class="match-overview-graphs">
                                                  <?= render_graph_card('First Half', (int)$firstHalfCounts['home'], (int)$firstHalfCounts['away'], $homeHalfCue) ?>
                                                  <?= render_graph_card('Second Half', (int)$secondHalfCounts['home'], (int)$secondHalfCounts['away'], $awayHalfCue) ?>
                                        </div>
                              </div>
                              <div class="phase2-panel">
                                        <div class="phase2-panel-header">
                                                  <div class="phase2-panel-title">Set pieces &amp; discipline</div>
                                                  <div class="phase2-panel-note">Combined derived totals</div>
                                        </div>
                                        <div class="match-overview-graphs">
                                                  <?= render_graph_card('Set pieces (corners + free kicks + penalties)', (int)$setPieces['home'], (int)$setPieces['away'], 'Aggregated from derived stats totals') ?>
                                                  <?= render_shot_accuracy_card(
                                                            'Cards (yellow + red)',
                                                            $match['home_team'] ?? 'Home',
                                                            'On Target',
                                                            'Off Target',
                                                            $cards['home'],
                                                            $cards['away'],
                                                            $homeShotGauge['homePercent'],
                                                            $homeShotGauge['awayPercent'],
                                                            $homeTotalForDisplay
                                                  ) ?>
                                        </div>
                              </div>
                    </div>
          </div>
          <div class="phase2-grid">
                    <div class="phase2-panel">
                    <div class="phase2-panel-header">
                              <div class="phase2-panel-title">Event distribution</div>
                              <div class="phase2-panel-note">Phase 2 · 15 minute buckets</div>
                    </div>
                    <div class="distribution-legend">
                              <span class="legend-pill legend-home">H = Home</span>
                              <span class="legend-pill legend-away">A = Away</span>
                              <span class="legend-pill legend-unknown">U = Unassigned</span>
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
                                        $homePct = $bucketTotal > 0 ? round(($homeBucket / $bucketTotal) * 100, 1) : 0;
                                        $awayPct = $bucketTotal > 0 ? round(($awayBucket / $bucketTotal) * 100, 1) : 0;
                                        $unknownPct = $bucketTotal > 0 ? round(($unknownBucket / $bucketTotal) * 100, 1) : 0;
                                        $chartLabel = sprintf('%s bucket: Home %.1f%% · Away %.1f%% · Unknown %.1f%%', $label, $homePct, $awayPct, $unknownPct);
                              ?>
                                        <div class="distribution-row">
                                                  <div class="distribution-info">
                                                            <div class="distribution-label"><?= htmlspecialchars($label) ?></div>
                                                            <div class="distribution-unknown"><?= $bucketTotal ?> events · <?= $unknownBucket ?> unassigned</div>
                                                            <div class="distribution-chart" role="img" aria-label="<?= htmlspecialchars($chartLabel) ?>">
                                                                      <?php if ($homePct > 0): ?>
                                                                                <span class="distribution-segment distribution-segment-home" style="width: <?= $homePct ?>%;"></span>
                                                                      <?php endif; ?>
                                                                      <?php if ($awayPct > 0): ?>
                                                                                <span class="distribution-segment distribution-segment-away" style="width: <?= $awayPct ?>%;"></span>
                                                                      <?php endif; ?>
                                                                      <?php if ($unknownPct > 0): ?>
                                                                                <span class="distribution-segment distribution-segment-unknown" style="width: <?= $unknownPct ?>%;"></span>
                                                                      <?php endif; ?>
                                                            </div>
                                                  </div>
                                                  <div class="distribution-statistics">
                                                            <div class="distribution-chart-meta">
                                                                      <span class="distribution-pill distribution-pill-home">H <?= $homePct ?>%</span>
                                                                      <span class="distribution-pill distribution-pill-away">A <?= $awayPct ?>%</span>
                                                                      <span class="distribution-pill distribution-pill-unknown">U <?= $unknownPct ?>%</span>
                                                            </div>
                                                            <div class="distribution-counts">
                                                                      <span class="distribution-count distribution-count-home">H <?= $homeBucket ?></span>
                                                                      <span class="distribution-count distribution-count-away">A <?= $awayBucket ?></span>
                                                                      <span class="distribution-count distribution-count-unknown">U <?= $unknownBucket ?></span>
                                                            </div>
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
                              <?php
                              // Helper: get the running score at the start of a period
                              $getScoreAtPeriodStart = function($periodIdx, $orderedLabels, $periodEndScores) {
                                        if ($periodIdx === 0) {
                                                  return ['home' => 0, 'away' => 0];
                                        }
                                        $prevLabel = $orderedLabels[$periodIdx - 1] ?? null;
                                        return $prevLabel && isset($periodEndScores[$prevLabel]) ? $periodEndScores[$prevLabel] : ['home' => 0, 'away' => 0];
                              };
                              $periodIdx = 0;
                              foreach ($timeline as $periodLabel => $rows):
                                        $sorted = $rows;
                                        usort($sorted, function ($a, $b) {
                                                  return ((int)($a['match_second'] ?? 0)) <=> ((int)($b['match_second'] ?? 0));
                                        });
                                        $scoreEnd = $periodEndScores[$periodLabel] ?? ['home' => 0, 'away' => 0];
                                        $scoreStart = $getScoreAtPeriodStart($periodIdx, $orderedLabels, $periodEndScores);
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
                                        $scoreText = $hasPeriodEnd ? ((int)$scoreEnd['home'] . ' - ' . (int)$scoreEnd['away']) : '';
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
                                                                                                    <?php if ($kLower === 'period_start'): ?>
                                                                                                              <?= (int)$scoreStart['home'] ?> - <?= (int)$scoreStart['away'] ?>
                                                                                                    <?php else: ?>
                                                                                                              <?= (int)$scoreEnd['home'] ?> - <?= (int)$scoreEnd['away'] ?>
                                                                                                    <?php endif; ?>
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
                                        <?php $periodIdx++; ?>
                              <?php endforeach; ?>
                              <div class="stl-fulltime">
                                        <small>Full time</small>
                                        <span><?= (int)$runningScore['home'] ?> - <?= (int)$runningScore['away'] ?></span>
                              </div>
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
