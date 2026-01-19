<?php
require_auth();

$base = base_path();
$title = 'Video Preparing';

$pendingStatus = $pendingStatus ?? ($match['video_download_status'] ?? 'pending');
$pendingProgress = isset($pendingProgress) ? (int)$pendingProgress : (int)($match['video_download_progress'] ?? 0);
$pendingError = $pendingError ?? ($match['video_error_message'] ?? null);
$veoUrl = $veoUrl ?? ($match['video_source_url'] ?? null);

ob_start();
?>
<div class="d-flex align-items-center justify-content-between mb-3">
          <div>
                    <h1 class="mb-1">Video not ready</h1>
                    <p class="text-muted-alt mb-0">We're still preparing the video for this match.</p>
          </div>
          <span class="status-pill status-pill-muted text-uppercase text-xs"><?= htmlspecialchars($pendingStatus) ?></span>
</div>

<div class="panel p-3 rounded-md">
          <div class="panel-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                              <div>
                                        <div class="text-muted-alt text-sm">Match</div>
                                        <div class="fw-semibold"><?= htmlspecialchars(($match['home_team'] ?? 'Home') . ' vs ' . ($match['away_team'] ?? 'Away')) ?></div>
                              </div>
                              <?php if ($veoUrl): ?>
                                        <div class="text-end">
                                                  <div class="text-muted-alt text-sm">Source</div>
                                                  <div class="text-sm text-break"><?= htmlspecialchars($veoUrl) ?></div>
                                        </div>
                              <?php endif; ?>
                    </div>

                    <div class="mb-2">
                              <div class="progress bg-surface border border-soft" style="height: 12px;">
                                        <div id="pendingProgressBar" class="progress-bar bg-primary" role="progressbar" style="width: <?= $pendingProgress ?>%;" aria-valuenow="<?= $pendingProgress ?>" aria-valuemin="0" aria-valuemax="100"></div>
                              </div>
                              <div class="d-flex justify-content-between text-muted-alt text-sm mt-2">
                                        <span id="pendingStatusText">Status: <?= htmlspecialchars($pendingStatus) ?></span>
                                        <span id="pendingPercentText"><?= $pendingProgress ?>%</span>
                              </div>
                              <div class="text-muted-alt text-sm" id="pendingSizeText"></div>
                    </div>

                    <?php if ($pendingError): ?>
                              <div class="alert alert-danger text-sm">
                                        <strong>Download failed:</strong> <?= htmlspecialchars($pendingError) ?>
                              </div>
                    <?php else: ?>
                              <div class="alert alert-info text-sm mb-3">
                                        Download will continue in the background. You can navigate away and return later.
                              </div>
                    <?php endif; ?>

                    <div class="d-flex gap-2">
                              <a class="btn btn-secondary-soft" href="<?= htmlspecialchars($base) ?>/matches">Back to matches</a>
                              <a class="btn btn-primary-soft" href="<?= htmlspecialchars($base) ?>/matches/<?= (int)($match['id'] ?? 0) ?>/stats">Match stats</a>
                    </div>
          </div>
</div>
<?php
$pendingConfig = [
          'basePath' => $base,
          'matchId' => (int)($match['id'] ?? 0),
];
$footerScripts = '<script>window.PendingVideoConfig = ' . json_encode($pendingConfig) . ";</script>\n";
$footerScripts .= <<<HTML
<script>
(() => {
          const cfg = window.PendingVideoConfig || {};
          if (!cfg.matchId) return;
          const statusEl = document.getElementById('pendingStatusText');
          const percentEl = document.getElementById('pendingPercentText');
          const barEl = document.getElementById('pendingProgressBar');
          const sizeEl = document.getElementById('pendingSizeText');
          let poller = null;

          const clamp = (value) => Math.max(0, Math.min(100, Math.round(Number(value) || 0)));
          const formatGB = (bytes) => {
                    if (!bytes) return '0 GB';
                    const gb = bytes / (1024 ** 3);
                    return gb >= 10 ? gb.toFixed(1) + ' GB' : gb.toFixed(2) + ' GB';
          };

          async function poll() {
                    try {
                              const url =
                                        (cfg.basePath || '') + '/api/video_status?match_id=' + encodeURIComponent(String(cfg.matchId));
                              const res = await fetch(url, { headers: { Accept: 'application/json' } });
                              const data = await res.json().catch(() => ({}));
                              if (!res.ok || !data.ok) throw new Error(data.error || 'Unable to read progress');

                              const status = (data.status || 'pending').toLowerCase();
                              const percent = clamp(data.percent);
                              const message = data.message || data.status || 'Pending';
                              const downloadedBytes = Number(data.downloaded_bytes) || 0;
                              const totalBytes = Number(data.total_bytes) || 0;

                              if (barEl) {
                                        barEl.style.width = percent + '%';
                                        barEl.setAttribute('aria-valuenow', percent.toString());
                              }
                              if (percentEl) {
                                        percentEl.textContent = percent + '%';
                              }
                              if (statusEl) {
                                        statusEl.textContent = 'Status: ' + message;
                              }
                              if (sizeEl) {
                                        const downloadedLabel = formatGB(downloadedBytes);
                                        const totalLabel = formatGB(totalBytes);
                                        sizeEl.textContent = downloadedLabel + ' / ' + totalLabel;
                              }

                              if (status === 'completed' || status === 'failed') {
                                        if (poller) {
                                                  clearInterval(poller);
                                                  poller = null;
                                        }
                              }
                    } catch (err) {
                              console.error('Pending video poll failed', err);
                              if (poller) {
                                        clearInterval(poller);
                                        poller = null;
                              }
                    }
          }

          poll();
          poller = window.setInterval(poll, 2000);
})();
</script>
HTML;
$content = ob_get_clean();
require __DIR__ . '/../../layout.php';
