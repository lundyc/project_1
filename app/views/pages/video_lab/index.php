<?php
$base = base_path();
$title = 'Video Lab';
ob_start();
?>
<div class="d-flex align-items-center justify-content-between mb-4">
          <div>
                    <h1 class="mb-1">Video Lab</h1>
                    <p class="text-muted-alt text-sm mb-0">Experimental read-only workspace for video artifacts. No editing, playback, or exports.</p>
          </div>
          <div class="text-end">
                    <span class="badge bg-warning text-dark px-3 py-2 fw-semibold">Experimental</span>
                    <p class="text-xs text-muted-alt mb-0">Safe observation only.</p>
          </div>
</div>

<?php if (empty($matches)): ?>
          <div class="panel p-3 text-muted-alt text-sm">No matches with video records are available yet.</div>
<?php else: ?>
          <div class="match-table-wrap relative overflow-x-auto">
                    <table class="match-table">
                              <thead class="match-table-head">
                                        <tr>
                                                  <th>Match</th>
                                                  <th>Date</th>
                                                  <th>Video source</th>
                                                  <th class="text-end">Clip count</th>
                                        </tr>
                              </thead>
                              <tbody>
                                        <?php foreach ($matches as $match): ?>
                                                  <?php
                                                            $kickoffTs = !empty($match['kickoff_at']) ? strtotime($match['kickoff_at']) : null;
                                                            $kickoffLabel = $kickoffTs ? date('M j, Y H:i', $kickoffTs) : 'TBD';
                                                            $matchLabel = trim(($match['home_team'] ?? '') . ' vs ' . ($match['away_team'] ?? ''));
                                                            $clipCount = (int)($match['clip_count'] ?? 0);
                                                            $sourceType = $match['source_type'] ?? 'unknown';
                                                  ?>
                                                  <tr>
                                                            <td>
                                                                      <div class="fw-semibold">
                                                                                <a href="<?= htmlspecialchars($base) ?>/video-lab/match/<?= (int)$match['id'] ?>"><?= htmlspecialchars($matchLabel) ?></a>
                                                                      </div>
                                                                      <div class="text-muted-alt text-xs">Status: <?= htmlspecialchars($match['status'] ?? 'N/A') ?></div>
                                                            </td>
                                                            <td><?= htmlspecialchars($kickoffLabel) ?></td>
                                                            <td><?= htmlspecialchars(strtoupper($sourceType)) ?></td>
                                                            <td class="text-end"><?= $clipCount ?></td>
                                                  </tr>
                                        <?php endforeach; ?>
                              </tbody>
                    </table>
          </div>
<?php endif; ?>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../layout.php';
