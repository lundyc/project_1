<?php
$base = base_path();
$matchLabel = trim(($match['home_team'] ?? '') . ' vs ' . ($match['away_team'] ?? ''));
$title = 'Video Lab · ' . ($matchLabel ?: 'Match');

function video_lab_format_duration(?int $seconds): string
{
          if ($seconds === null || $seconds <= 0) {
                    return 'Unknown';
          }

          $hours = intdiv($seconds, 3600);
          $minutes = intdiv($seconds % 3600, 60);
          $remain = $seconds % 60;

          if ($hours > 0) {
                    return sprintf('%d:%02d:%02d', $hours, $minutes, $remain);
          }

          return sprintf('%d:%02d', $minutes, $remain);
}

function video_lab_format_match_second(?int $seconds): string
{
          if ($seconds === null) {
                    return '—';
          }

          $minutes = intdiv($seconds, 60);
          $remain = $seconds % 60;

          return sprintf('%d:%02d', $minutes, $remain);
}

function video_lab_format_event_minute(?int $minute, ?int $minuteExtra): string
{
          if ($minute === null) {
                    return '—';
          }

          if ($minuteExtra !== null && $minuteExtra > 0) {
                    return sprintf('%d+%02d', $minute, $minuteExtra);
          }

          return (string)$minute;
}

function video_lab_format_team_side(?string $teamSide): string
{
          switch ($teamSide) {
                    case 'home':
                              return 'Home';
                    case 'away':
                              return 'Away';
                    default:
                              return 'Unknown';
          }
}

$matchId = (int)($match['id'] ?? 0);
$phase3Enabled = $phase3Enabled ?? false;
$clipReviewClips = $eventClips ?? [];
$eventClips = $clipReviewClips;
$durationLabel = video_lab_format_duration(isset($match['duration_seconds']) ? (int)$match['duration_seconds'] : null);
$kickoffTs = !empty($match['kickoff_at']) ? strtotime($match['kickoff_at']) : null;
$kickoffLabel = $kickoffTs ? date('M j, Y H:i', $kickoffTs) : 'TBD';
$clipCount = (int)($match['clip_count'] ?? 0);
$sourceType = strtoupper($match['source_type'] ?? 'unknown');
$statuses = $clipStatuses ?? ['pending' => 0, 'approved' => 0, 'rejected' => 0];
$statusMeta = [
          'pending' => ['label' => 'Pending review', 'hint' => 'Awaiting review'],
          'approved' => ['label' => 'Approved', 'hint' => 'Passed review'],
          'rejected' => ['label' => 'Rejected', 'hint' => 'Dismissed'],
];
ob_start();
?>
<div class="d-flex flex-wrap align-items-start justify-content-between mb-4 gap-3">
          <div>
                    <h1 class="mb-1">Video Lab · <?= htmlspecialchars($matchLabel ?: 'Match') ?></h1>
                    <p class="text-muted-alt text-sm mb-0">Read-only observation. No editing, playback, or exports.</p>
          </div>
          <div class="text-end">
                    <a href="<?= htmlspecialchars($base) ?>/video-lab" class="btn btn-sm btn-outline-light">Back to Video Lab</a>
                    <div class="mt-2">
                              <span class="badge bg-warning text-dark px-3 py-2 fw-semibold">Experimental</span>
                              <div class="text-xs text-muted-alt mt-1">Clip metadata only.</div>
                    </div>
          </div>
</div>

<div class="row g-3">
          <div class="col-12 col-lg-6">
                    <div class="panel p-4 border-soft">
                              <div class="mb-3">
                                        <h5 class="mb-1">Match metadata</h5>
                                        <p class="text-muted-alt text-sm mb-0">Viewed via the Video Lab sandbox.</p>
                              </div>
                              <div class="d-flex flex-column gap-2 text-sm">
                                        <div>
                                                  <span class="text-muted-alt text-xs">Match</span>
                                                  <div class="fw-semibold"><?= htmlspecialchars($matchLabel ?: 'Unknown match') ?></div>
                                        </div>
                                        <div>
                                                  <span class="text-muted-alt text-xs">Date</span>
                                                  <div><?= htmlspecialchars($kickoffLabel) ?></div>
                                        </div>
                                        <div>
                                                  <span class="text-muted-alt text-xs">Video source</span>
                                                  <div><?= htmlspecialchars($sourceType) ?></div>
                                        </div>
                                        <div>
                                                  <span class="text-muted-alt text-xs">Video duration</span>
                                                  <div><?= htmlspecialchars($durationLabel) ?></div>
                                        </div>
                              </div>
                    </div>
          </div>
          <div class="col-12 col-lg-6">
                    <div class="panel p-4 border-soft h-100">
                                                  <div class="mb-3">
                                                            <h5 class="mb-1">Clip overview</h5>
                                                            <p class="text-muted-alt text-sm mb-0">Programmatically generated clip totals and quick actions.</p>
                                                  </div>
                                                  <div class="row g-2 align-items-center">
                                                            <div class="col-12 col-md-4">
                                                                      <div class="panel p-3 d-flex align-items-center gap-3">
                                                                                <div class="fs-3 text-primary"><i class="fa-solid fa-film"></i></div>
                                                                                <div>
                                                                                          <div class="text-xs text-muted-alt">Total clips</div>
                                                                                          <div id="videoLabTotalClips" class="fs-3 fw-bold"><?= $clipCount ?></div>
                                                                                </div>
                                                                      </div>
                                                            </div>
                                                            <div class="col-12 col-md-4">
                                                                      <div class="panel p-3 d-flex align-items-center gap-3">
                                                                                <div class="fs-3 text-secondary"><i class="fa-solid fa-clock"></i></div>
                                                                                <div>
                                                                                          <div class="text-xs text-muted-alt">Video duration</div>
                                                                                          <div class="fw-semibold"><?= htmlspecialchars($durationLabel) ?></div>
                                                                                </div>
                                                                      </div>
                                                            </div>
                                                            <div class="col-12 col-md-4">
                                                                      <div class="panel p-3 d-flex align-items-center gap-3 justify-content-between">
                                                                                <div class="d-flex gap-3 align-items-center">
                                                                                          <div class="fs-3 text-success"><i class="fa-solid fa-video"></i></div>
                                                                                          <div>
                                                                                                    <div class="text-xs text-muted-alt">Source</div>
                                                                                                    <div class="fw-semibold"><?= htmlspecialchars($sourceType) ?></div>
                                                                                          </div>
                                                                                </div>
                                                                                <div>
                                                                                          <button type="button" class="btn btn-sm btn-outline-light" data-action="regenerate-match" data-endpoint="<?= htmlspecialchars($base) ?>/api/video-lab/match/<?= $matchId ?>/regenerate" <?= $phase3Enabled ? '' : 'disabled' ?> title="Regenerate all clips"><i class="fa-solid fa-sync"></i></button>
                                                                                </div>
                                                                      </div>
                                                            </div>
                                                  </div>

                                                  <div class="text-muted-alt text-xs mt-3 mb-2">Status breakdown</div>
                                                  <div class="d-flex flex-wrap gap-2">
                                                            <?php foreach ($statusMeta as $key => $meta): ?>
                                                                      <div class="panel p-2 rounded-md d-flex flex-column align-items-center" style="min-width: 140px;">
                                                                                <div class="text-xs text-muted-alt text-uppercase mb-1"><?= htmlspecialchars($meta['label']) ?></div>
                                                                                <div class="fs-4 fw-semibold" data-clip-status-count="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars((string)($statuses[$key] ?? 0)) ?></div>
                                                                                <div class="text-muted-alt text-xs mt-1"><?= htmlspecialchars($meta['hint']) ?></div>
                                                                                <button type="button" class="btn btn-xs btn-link mt-2" data-filter-status="<?= htmlspecialchars($key) ?>">Show</button>
                                                                      </div>
                                                            <?php endforeach; ?>
                                                  </div>
                    </div>
          </div>
</div>

<div class="panel p-4 border-soft mt-4" id="videoLabClipReviewPanel">
          <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
                    <div>
                              <h5 class="mb-1">Clips</h5>
                              <p class="text-muted-alt text-sm mb-0">Review, load and regenerate clips from one unified view.</p>
                    </div>
                    <div class="text-end">
                              <div class="mb-2">
                                        <button type="button"
                                                  class="btn btn-sm btn-primary"
                                                  data-action="regenerate-match"
                                                  data-endpoint="<?= htmlspecialchars($base) ?>/api/video-lab/match/<?= $matchId ?>/regenerate"
                                                  data-loading-text="Regenerating…"
                                                  <?= $phase3Enabled ? '' : 'disabled' ?>>
                                                  Regenerate all
                                        </button>
                              </div>
                              <?php if (!$phase3Enabled): ?>
                                        <div class="text-xs text-warning">Phase 3 clip operations are disabled.</div>
                              <?php endif; ?>
                    </div>
          </div>

          <?php if (empty($clipReviewClips)): ?>
                    <div class="text-muted-alt text-sm">Clip metadata is not available yet.</div>
          <?php else: ?>
                    <div class="mb-3 d-flex gap-2 flex-wrap align-items-center">
                              <div class="text-xs text-muted-alt">Filter:</div>
                              <select id="videoLabFilterStatus" class="form-select form-select-sm" style="width:160px;">
                                        <option value="">All statuses</option>
                                        <option value="pending">Pending</option>
                                        <option value="approved">Approved</option>
                                        <option value="rejected">Rejected</option>
                              </select>
                              <select id="videoLabFilterSource" class="form-select form-select-sm" style="width:160px;">
                                        <option value="">All sources</option>
                                        <option value="event_auto">Auto</option>
                                        <option value="manual">Manual</option>
                              </select>
                              <input id="videoLabFilterSearch" class="form-control form-control-sm" placeholder="Search player / event" style="width:260px;" />
                    </div>

                    <div class="row g-3">
                              <div class="col-12 col-lg-4">
                                        <div class="panel p-3 border-soft">
                                                  <div class="mb-2 text-muted-alt text-xs">Mini player</div>
                                                  <div>
                                                            <?php
                                                                      $videoWebPath = '/videos/matches/match_' . $matchId . '/source/veo/standard/match_' . $matchId . '_standard.mp4';
                                                            ?>
                                                            <video id="videoLabPlayer" class="w-100" controls preload="metadata">
                                                                      <source src="<?= htmlspecialchars($videoWebPath) ?>" type="video/mp4">
                                                                      Your browser does not support the video element.
                                                            </video>
                                                            <div id="videoLabPlayerInfo" class="text-xs text-muted-alt mt-2">Select a clip to load into the player.</div>
                                                  </div>
                                        </div>
                              </div>
                              <div class="col-12 col-lg-8">
                                        <div class="match-table-wrap relative overflow-x-auto">
                                                  <table class="match-table video-lab-clip-table" id="videoLabCombinedTable">
                                        <thead class="match-table-head">
                                                  <tr>
                                                            <th>Event123</th>
                                                            <th>Clip window</th>
                                                            <th>Team</th>
                                                            <th>Player</th>
                                                            <th>Source</th>
                                                            <th>Duration</th>
                                                            <th>Status</th>
                                                            <th class="text-end">Actions</th>
                                                  </tr>
                                        </thead>
                                        <tbody>
                                                  <?php foreach ($clipReviewClips as $clip): ?>
                                                            <?php
                                                                      $status = $clip['review_status'] ?? 'pending';
                                                                      $isPending = $status === 'pending' && $phase3Enabled;
                                                                      $statusLabel = ucfirst($status);
                                                                      $clipStart = $clip['start_second'] ?? null;
                                                                      $clipEnd = $clip['end_second'] ?? null;
                                                                      $clipWindow = ($clipStart !== null && $clipEnd !== null)
                                                                                ? sprintf('%s – %s', video_lab_format_match_second($clipStart), video_lab_format_match_second($clipEnd))
                                                                                : 'Unknown';
                                                            ?>
                                                            <tr data-clip-id="<?= isset($clip['clip_id']) ? (int)$clip['clip_id'] : 0 ?>"
                                                                        data-event-id="<?= isset($clip['event_id']) ? (int)$clip['event_id'] : 0 ?>"
                                                                        data-start-second="<?= isset($clip['start_second']) ? (int)$clip['start_second'] : '' ?>"
                                                                        data-end-second="<?= isset($clip['end_second']) ? (int)$clip['end_second'] : '' ?>"
                                                                        data-duration-seconds="<?= isset($clip['duration_seconds']) ? (int)$clip['duration_seconds'] : '' ?>"
                                                                        data-clip-name="<?= htmlspecialchars($clip['clip_name'] ?? ($clip['event_type_label'] ?? 'Clip')) ?>"
                                                                        data-generation-source="<?= htmlspecialchars($clip['generation_source'] ?? '') ?>"
                                                                        data-player-name="<?= htmlspecialchars($clip['player_name'] ?? '') ?>">
                                                                      <td>
                                                                                <div class="fw-semibold"><?= htmlspecialchars($clip['event_type_label'] ?? 'Clip') ?></div>
                                                                                <div class="text-muted-alt text-xs">Time: <?= htmlspecialchars(video_lab_format_match_second($clip['match_second'] ?? null)) ?></div>
                                                                      </td>
                                                                      <td>
                                                                                <div class="text-muted-alt text-xs">Window</div>
                                                                                <div class="fw-semibold"><?= htmlspecialchars($clipWindow) ?></div>
                                                                      </td>
                                                                      <td>
                                                                                <div class="text-muted-alt text-xs">Team</div>
                                                                                <div class="fw-semibold"><?= htmlspecialchars(video_lab_format_team_side($clip['team_side'] ?? null)) ?></div>
                                                                      </td>
                                                                      <td>
                                                                                <div class="text-muted-alt text-xs">Player</div>
                                                                                <div class="fw-semibold"><?= htmlspecialchars($clip['player_name'] ?? '—') ?></div>
                                                                      </td>
                                                                      <td>
                                                                                <div class="text-muted-alt text-xs">Source</div>
                                                                                <div class="fw-semibold"><?= htmlspecialchars($clip['generation_source'] ?? 'event_auto') ?></div>
                                                                      </td>
                                                                      <td>
                                                                                <div class="text-muted-alt text-xs">Duration</div>
                                                                                <div class="fw-semibold"><?= htmlspecialchars(video_lab_format_duration($clip['duration_seconds'] ?? null)) ?></div>
                                                                      </td>
                                                                      <td>
                                                                                <div class="text-muted-alt text-xs">Review</div>
                                                                                <span class="video-lab-status-badge video-lab-status-badge-<?= htmlspecialchars($status) ?>"><?= htmlspecialchars($statusLabel) ?></span>
                                                                      </td>
                                                                      <td class="text-end">
                                                                                <div class="d-flex flex-wrap justify-content-end gap-2 align-items-center">
                                                                                          <button type="button" class="btn btn-sm btn-outline-light" title="Approve" data-bs-toggle="tooltip" data-action="review-approve" data-clip-id="<?= isset($clip['clip_id']) ? (int)$clip['clip_id'] : 0 ?>" <?= $isPending ? '' : 'disabled' ?>><i class="fa-solid fa-check"></i></button>
                                                                                          <button type="button" class="btn btn-sm btn-outline-light" title="Reject" data-bs-toggle="tooltip" data-action="review-reject" data-clip-id="<?= isset($clip['clip_id']) ? (int)$clip['clip_id'] : 0 ?>" <?= $isPending ? '' : 'disabled' ?>><i class="fa-solid fa-xmark"></i></button>
                                                                                          <button type="button" class="btn btn-sm btn-outline-light" title="Load clip" data-bs-toggle="tooltip" data-action="clip-load" data-clip-id="<?= isset($clip['clip_id']) ? (int)$clip['clip_id'] : 0 ?>" <?= (isset($clip['start_second']) && isset($clip['end_second'])) ? '' : 'disabled' ?>><i class="fa-solid fa-play"></i></button>
                                                                                          <button type="button" class="btn btn-sm btn-outline-light" title="Details" data-bs-toggle="tooltip" data-action="clip-details" data-clip-id="<?= isset($clip['clip_id']) ? (int)$clip['clip_id'] : 0 ?>"><i class="fa-solid fa-info"></i></button>
                                                                                          <?php $isAuto = strtolower((string)($clip['generation_source'] ?? 'event_auto')) === 'event_auto'; $hasClip = $clip['clip_id'] !== null; $actionDisabled = !$phase3Enabled || !$hasClip || !$isAuto; ?>
                                                                                          <button type="button" class="btn btn-sm btn-outline-light" title="Regenerate event" data-bs-toggle="tooltip" data-action="regenerate-event" data-endpoint="<?= htmlspecialchars($base) ?>/api/video-lab/match/<?= $matchId ?>/event/<?= $clip['event_id'] ?? 0 ?>/regenerate" <?= $actionDisabled ? 'disabled' : '' ?>><i class="fa-solid fa-sync"></i></button>
                                                                                </div>
                                                                      </td>
                                                            </tr>
                                                  <?php endforeach; ?>
                                        </tbody>
                              </table>
                    </div>
                    </div>
          <?php endif; ?>
          <div id="videoLabClipReviewMessage" class="text-xs mt-2 text-muted-alt" role="status"></div>
</div>
<div id="videoLabClipModal" class="video-lab-clip-modal" role="dialog" aria-modal="true" aria-hidden="true">
          <div class="video-lab-clip-modal-backdrop" data-video-lab-modal-close></div>
          <div class="video-lab-clip-modal-card">
                    <div class="video-lab-clip-modal-header">
                              <div>
                                        <div class="text-xs text-muted-alt">Clip details</div>
                                        <div id="videoLabClipModalTitle" class="fw-semibold">Clip overview</div>
                              </div>
                              <button type="button"
                                        class="btn btn-sm btn-outline-light"
                                        data-video-lab-modal-close
                                        aria-label="Close clip details">
                                        Close
                              </button>
                    </div>
                    <div class="video-lab-clip-modal-body">
                              <div class="video-lab-clip-modal-section">
                                        <div class="text-muted-alt text-xs mb-1">Clip timing</div>
                                        <div class="grid-2 gap-sm">
                                                  <div>
                                                            <div class="text-xs text-muted-alt">Start (s)</div>
                                                            <div id="videoLabClipModalStart" class="fw-semibold">—</div>
                                                  </div>
                                                  <div>
                                                            <div class="text-xs text-muted-alt">End (s)</div>
                                                            <div id="videoLabClipModalEnd" class="fw-semibold">—</div>
                                                  </div>
                                        </div>
                              </div>
                              <div class="video-lab-clip-modal-section">
                                        <div class="text-muted-alt text-xs mb-1">Generation metadata</div>
                                        <div class="grid-2 gap-sm">
                                                  <div>
                                                            <div class="text-xs text-muted-alt">Source</div>
                                                            <div id="videoLabClipModalSource" class="fw-semibold">—</div>
                                                  </div>
                                                  <div>
                                                            <div class="text-xs text-muted-alt">Version</div>
                                                            <div id="videoLabClipModalVersion" class="fw-semibold">—</div>
                                                  </div>
                                        </div>
                              </div>
                              <div class="video-lab-clip-modal-section">
                                        <div class="text-muted-alt text-xs mb-1">Event snapshot</div>
                                        <pre id="videoLabClipModalSnapshot" class="video-lab-clip-modal-json">No snapshot available.</pre>
                              </div>
                              <div class="video-lab-clip-modal-section">
                                        <div class="text-muted-alt text-xs mb-1">Review history</div>
                                        <ul id="videoLabClipModalHistory" class="video-lab-clip-modal-history"></ul>
                              </div>
                    </div>
          </div>
</div>

<?php
$clipReviewConfig = [
          'matchId' => $matchId,
          'phase3Enabled' => $phase3Enabled,
          'reviewEndpoint' => $base . '/api/video-lab/match/' . $matchId . '/clip/{clipId}/review',
          'detailsEndpoint' => $base . '/api/video-lab/match/' . $matchId . '/clip/{clipId}',
          'videoSource' => $videoWebPath,
];
?>
<script>
          window.VideoLabClipReviewConfig = <?= json_encode($clipReviewConfig, JSON_UNESCAPED_SLASHES) ?>;
</script>
<?php
$videoLabScriptPath = __DIR__ . '/../../../../public/assets/js/video_lab.js';
$videoLabScriptVersion = is_file($videoLabScriptPath) ? (int)filemtime($videoLabScriptPath) : time();
?>
<script src="<?= htmlspecialchars($base) ?>/assets/js/video_lab.js?v=<?= $videoLabScriptVersion ?>" defer></script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../layout.php';
