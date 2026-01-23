<?php
require_auth();
require_once __DIR__ . '/../../../lib/match_permissions.php';
require_once __DIR__ . '/../../../lib/match_period_repository.php';

$user = current_user();
$roles = $_SESSION['roles'] ?? [];
$canManage = can_manage_match_for_club($user, $roles, (int)$match['club_id']);
$base = base_path();

$periods = get_match_periods((int)$match['id']);
$success = $_SESSION['periods_success'] ?? null;
$error = $_SESSION['periods_error'] ?? null;
unset($_SESSION['periods_success'], $_SESSION['periods_error']);

$rows = max(count($periods), 4);
$periodConfig = [
          'matchId' => (int)$match['id'],
          'canManage' => $canManage,
          'periods' => $periods,
          'endpoints' => [
                    'list' => $base . '/api/matches/' . (int)$match['id'] . '/periods',
                    'start' => $base . '/api/matches/' . (int)$match['id'] . '/periods/start',
                    'end' => $base . '/api/matches/' . (int)$match['id'] . '/periods/end',
                    'custom' => $base . '/api/matches/' . (int)$match['id'] . '/periods/custom',
                    'preset' => $base . '/api/matches/' . (int)$match['id'] . '/periods/preset',
          ],
];

$cacheBuster = time();

ob_start();
?>
<div class="d-flex align-items-center justify-content-between mb-4">
          <div>
                    <p class="text-xs text-muted-alt mb-1">Match periods</p>
                    <h1 class="mb-1"><?= htmlspecialchars($match['home_team']) ?> vs <?= htmlspecialchars($match['away_team']) ?></h1>
                    <p class="text-muted-alt mb-0 text-sm"><?= htmlspecialchars($match['kickoff_at'] ?? '') ?></p>
          </div>
          <a href="<?= htmlspecialchars($base) ?>/matches" class="btn btn-secondary-soft btn-sm">Back to matches</a>
</div>

<?php if ($error): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php elseif ($success): ?>
          <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div id="periodsFlash" class="alert d-none"></div>

<div id="periodsApp">
          <div class="row g-3">
                    <div class="col-lg-7">
                              <div class="panel p-3 rounded-md border-soft h-100">
                                        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                                                  <div>
                                                            <h5 class="card-title mb-1">Current periods</h5>
                                                            <p class="text-muted-alt text-sm mb-0">Live status, start/end seconds, and progress</p>
                                                  </div>
                                                  <?php if ($canManage): ?>
                                                            <div class="d-flex align-items-center gap-2">
                                                                      <label for="currentSecondInput" class="form-label mb-0 text-xs">Current match second</label>
                                                                      <input type="number" id="currentSecondInput" class="form-control form-control-sm input-dark" min="0" step="1" value="0" style="max-width:140px">
                                                            </div>
                                                  <?php endif; ?>
                                        </div>
                                        <div id="periodList" class="period-list"></div>
                              </div>
                    </div>

                    <?php if ($canManage): ?>
                              <div class="col-lg-5">
                                        <div class="panel p-3 rounded-md border-soft h-100">
                                                  <div class="d-flex justify-content-between align-items-center mb-2">
                                                            <div>
                                                                      <h5 class="card-title mb-1">Plan &amp; presets</h5>
                                                                      <p class="text-muted-alt text-sm mb-0">Presets populate the form. Save to update instantly.</p>
                                                            </div>
                                                            <button type="submit" form="periodForm" class="btn btn-primary-soft btn-sm">Save</button>
                                                  </div>
                                                  <div class="d-flex flex-wrap gap-2 mb-3">
                                                            <button type="button" class="btn btn-secondary-soft btn-sm" data-period-preset="fh" data-label="First Half" data-start="0" data-end="45">First Half</button>
                                                            <button type="button" class="btn btn-secondary-soft btn-sm" data-period-preset="sh" data-label="Second Half" data-start="45" data-end="90">Second Half</button>
                                                            <button type="button" class="btn btn-secondary-soft btn-sm" data-period-preset="et1" data-label="Extra Time – First Half" data-start="90" data-end="105">ET 1</button>
                                                            <button type="button" class="btn btn-secondary-soft btn-sm" data-period-preset="et2" data-label="Extra Time – Second Half" data-start="105" data-end="120">ET 2</button>
                                                  </div>
                                                  <form id="periodForm">
                                                            <div class="period-form-rows" id="periodFormRows">
                                                                      <?php for ($i = 0; $i < $rows; $i++): ?>
                                                                                <?php
                                                                                $prefill = $periods[$i] ?? null;
                                                                                $label = $prefill['label'] ?? '';
                                                                                $startMinute = $prefill['start_minute'] ?? '';
                                                                                $endMinute = $prefill['end_minute'] ?? '';
                                                                                if ($startMinute === '' && $endMinute === '' && isset($prefill['minutes_planned']) && $prefill['minutes_planned'] !== null) {
                                                                                          $endMinute = (int)$prefill['minutes_planned'];
                                                                                }
                                                                                ?>
                                                                                <div class="period-form-row border-soft rounded-sm" data-row-index="<?= $i ?>">
                                                                                          <div>
                                                                                                    <label class="form-label text-xs mb-1">Label</label>
                                                                                                    <input type="text" name="label[]" class="form-control form-control-sm input-dark" value="<?= htmlspecialchars((string)$label) ?>" placeholder="e.g. First Half">
                                                                                          </div>
                                                                                          <div>
                                                                                                    <label class="form-label text-xs mb-1">Start (min)</label>
                                                                                                    <input type="number" name="start_minute[]" class="form-control form-control-sm input-dark" min="0" step="1" value="<?= $startMinute !== '' ? htmlspecialchars((string)$startMinute) : '' ?>">
                                                                                          </div>
                                                                                          <div>
                                                                                                    <label class="form-label text-xs mb-1">End (min)</label>
                                                                                                    <input type="number" name="end_minute[]" class="form-control form-control-sm input-dark" min="0" step="1" value="<?= $endMinute !== '' ? htmlspecialchars((string)$endMinute) : '' ?>">
                                                                                          </div>
                                                                                </div>
                                                                      <?php endfor; ?>
                                                            </div>
                                                            <div class="d-flex justify-content-end">
                                                                      <button type="submit" class="btn btn-primary-soft btn-sm mt-3">Save periods</button>
                                                            </div>
                                                  </form>
                                        </div>
                              </div>
                    <?php endif; ?>
          </div>
</div>
<?php
$content = ob_get_clean();
$footerScripts = '<script>window.MatchPeriodsConfig = ' . json_encode($periodConfig) . ';</script>'
          . '<script src="' . htmlspecialchars($base) . '/assets/js/match-periods.js?v=' . $cacheBuster . '"></script>';
require __DIR__ . '/../../layout.php';
