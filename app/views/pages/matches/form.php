<?php
require_auth();
require_once __DIR__ . '/../../../lib/match_permissions.php';
require_once __DIR__ . '/../../../lib/team_repository.php';
require_once __DIR__ . '/../../../lib/season_repository.php';
require_once __DIR__ . '/../../../lib/competition_repository.php';
require_once __DIR__ . '/../../../lib/club_repository.php';

$user = current_user();
$roles = $_SESSION['roles'] ?? [];
$isPlatformAdmin = in_array('platform_admin', $roles, true);
$isEdit = isset($match) && is_array($match);
$base = base_path();

$clubs = $isPlatformAdmin ? get_all_clubs() : [];

if ($isEdit) {
          $selectedClubId = (int)$match['club_id'];
} else {
          $selectedClubId = $isPlatformAdmin
                    ? (isset($_GET['club_id']) && $_GET['club_id'] !== '' ? (int)$_GET['club_id'] : (int)($clubs[0]['id'] ?? ($user['club_id'] ?? 0)))
                    : (int)($user['club_id'] ?? 0);
}

if (!$selectedClubId) {
          http_response_code(400);
          echo 'Club context required';
          exit;
}

$teams = get_teams_by_club($selectedClubId);
$seasons = get_seasons_by_club($selectedClubId);
$competitions = get_competitions_by_club($selectedClubId);

$error = $_SESSION['match_form_error'] ?? null;
$success = $_SESSION['match_form_success'] ?? null;
unset($_SESSION['match_form_error']);
unset($_SESSION['match_form_success']);

$title = $isEdit ? 'Edit Match' : 'Create Match';
$action = $isEdit ? ($base . '/api/matches/' . (int)$match['id'] . '/edit') : ($base . '/api/matches/create');
$kickoffValue = $isEdit && !empty($match['kickoff_at']) ? date('Y-m-d\TH:i', strtotime($match['kickoff_at'])) : '';
$videoType = $isEdit ? ($match['video_source_type'] ?? 'upload') : 'veo';
$videoPath = $isEdit ? ($match['video_source_path'] ?? '') : '';
$initialDownloadStatus = $isEdit ? ($match['video_download_status'] ?? '') : '';
$initialDownloadProgress = $isEdit ? (int)($match['video_download_progress'] ?? 0) : 0;
$initialVeoUrl = $isEdit ? ($match['video_source_url'] ?? '') : '';
$matchSeasonId = $isEdit ? $match['season_id'] : null;
$matchCompetitionId = $isEdit ? $match['competition_id'] : null;
$matchHomeId = $isEdit ? (int)$match['home_team_id'] : 0;
$matchAwayId = $isEdit ? (int)$match['away_team_id'] : 0;
$matchVenue = $isEdit ? (string)($match['venue'] ?? '') : '';
$matchReferee = $isEdit ? (string)($match['referee'] ?? '') : '';
$matchAttendance = $isEdit ? $match['attendance'] : null;
$matchStatus = $isEdit ? $match['status'] : 'draft';
$matchIdValue = $isEdit ? (int)$match['id'] : null;

$videoFiles = [];
$videoDir = realpath(dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'videos' . DIRECTORY_SEPARATOR . 'raw');
$allowedVideoExt = ['mp4', 'webm', 'mov'];

if ($videoDir && is_dir($videoDir)) {
          $items = scandir($videoDir);
          foreach ($items as $item) {
                    if ($item === '.' || $item === '..') {
                              continue;
                    }
                    $full = $videoDir . DIRECTORY_SEPARATOR . $item;
                    if (!is_file($full)) {
                              continue;
                    }
                    $real = realpath($full);
                    if (!$real || !str_starts_with($real, $videoDir)) {
                              continue;
                    }
                    $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
                    if (!in_array($ext, $allowedVideoExt, true)) {
                              continue;
                    }
                    $videoFiles[] = [
                              'filename' => $item,
                              'web_path' => '/videos/raw/' . $item,
                    ];
          }
}
$hasCurrentVideo = $videoPath && !empty(array_filter($videoFiles, fn($f) => $f['web_path'] === $videoPath));

$debugMode = isset($_GET['debug']) && $_GET['debug'] === '1';
$wizardConfig = [
          'basePath' => $base,
          'isEdit' => $isEdit,
          'matchId' => $matchIdValue,
          'createEndpoint' => $base . '/api/matches/create',
          'updateEndpoint' => $isEdit ? $base . '/api/matches/' . (int)$matchIdValue . '/edit' : null,
          'continuePath' => $base . '/matches',
          'initialVideoType' => $videoType,
          'initialDownloadStatus' => $initialDownloadStatus ?: null,
          'initialDownloadProgress' => $initialDownloadProgress,
          'initialVeoUrl' => $initialVeoUrl,
          'pollInterval' => 2000,
          'debugConsole' => $debugMode,
];

$lineupConfig = [
          'basePath' => $base,
          'clubId' => $selectedClubId,
          'matchId' => $matchIdValue,
          'homeTeamId' => $matchHomeId,
          'awayTeamId' => $matchAwayId,
          'matchPlayers' => [
                    'list' => $base . '/api/match-players/list',
                    'add' => $base . '/api/match-players/add',
                    'update' => $base . '/api/match-players/update',
                    'delete' => $base . '/api/match-players/delete',
          ],
          'players' => [
                    'list' => $base . '/api/players/list',
                    'create' => $base . '/api/players/create',
          ],
          'overviewPathTemplate' => $base . '/matches/{match_id}',
          'analysisDeskPathTemplate' => $base . '/matches/{match_id}/desk',
          'stateKey' => 'matchWizardState',
];

$setupConfig = [
          'basePath' => $base,
          'clubId' => $selectedClubId,
          'endpoints' => [
                    'teamCreate' => $base . '/api/teams/create-json',
                    'seasonCreate' => $base . '/api/seasons/create',
                    'competitionCreate' => $base . '/api/competitions/create',
          ],
];

$footerScripts = '<script>window.MatchWizardConfig = ' . json_encode($wizardConfig) . ';</script>';
$footerScripts .= '<script>window.MatchWizardSetupConfig = ' . json_encode($setupConfig) . ';</script>';
$footerScripts .= '<script>window.MatchWizardLineupConfig = ' . json_encode($lineupConfig) . ';</script>';
$footerScripts .= '<script src="' . htmlspecialchars($base) . '/assets/js/match-setup.js?v=' . time() . '"></script>';
$footerScripts .= '<script src="' . htmlspecialchars($base) . '/assets/js/wizard-lineup.js?v=' . time() . '"></script>';
$footerScripts .= '<script src="' . htmlspecialchars($base) . '/assets/js/match-wizard.js?v=' . time() . '"></script>';

ob_start();
?>

<div class="d-flex align-items-center justify-content-between mb-4">
          <div>
                    <h1 class="mb-1"><?= $isEdit ? 'Edit Match' : 'Create Match' ?></h1>
                    <p class="text-secondary mb-0"><?= $isEdit ? 'Update match details or start a new VEO download.' : 'Three-step wizard to capture match details and video.' ?></p>
          </div>
          <?php if ($isEdit): ?>
                    <a href="<?= htmlspecialchars($base) ?>/matches/<?= (int)$matchIdValue ?>/desk" class="btn btn-secondary-soft btn-sm">Open Analysis Desk</a>
          <?php endif; ?>
</div>

<?php if ($isPlatformAdmin && !$isEdit): ?>
          <form method="get" action="<?= htmlspecialchars($base) ?>/matches/create" class="mb-3">
                    <label class="form-label text-light">Club context</label>
                    <div class="input-group">
                              <select name="club_id" class="form-select">
                                        <?php foreach ($clubs as $club): ?>
                                                  <option value="<?= (int)$club['id'] ?>" <?= (int)$club['id'] === $selectedClubId ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($club['name']) ?>
                                                  </option>
                                        <?php endforeach; ?>
                              </select>
                              <button class="btn btn-outline-light" type="submit">Switch</button>
                    </div>
          </form>
<?php endif; ?>

<?php if ($error): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php elseif ($success): ?>
          <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="wizard-nav mb-3">
          <div class="wizard-step-card is-active" data-step-nav="1">
                    <div class="wizard-step-index">1</div>
                    <div>
                              <div class="text-xs text-muted-alt">Step 1</div>
                              <div class="wizard-step-title">Match details</div>
                    </div>
          </div>
          <div class="wizard-step-card" data-step-nav="2">
                    <div class="wizard-step-index">2</div>
                    <div>
                              <div class="text-xs text-muted-alt">Step 2</div>
                              <div class="wizard-step-title">Video source</div>
                    </div>
          </div>
          <div class="wizard-step-card" data-step-nav="3">
                    <div class="wizard-step-index">3</div>
                    <div>
                              <div class="text-xs text-muted-alt">Step 3</div>
                              <div class="wizard-step-title">Download progress</div>
                    </div>
          </div>
          <div class="wizard-step-card" data-step-nav="4">
                    <div class="wizard-step-index">4</div>
                    <div>
                              <div class="text-xs text-muted-alt">Step 4</div>
                              <div class="wizard-step-title">Player lineup</div>
                    </div>
          </div>
</div>

<div id="wizardFlash" class="alert d-none" role="alert"></div>

<form id="matchWizardForm"
      method="post"
      action="<?= htmlspecialchars($action) ?>"
      class="wizard-form"
      data-is-edit="<?= $isEdit ? '1' : '0' ?>"
      data-match-id="<?= $matchIdValue ?? '' ?>"
      data-base-path="<?= htmlspecialchars($base) ?>"
      data-create-endpoint="<?= htmlspecialchars($base) ?>/api/matches/create"
      data-update-endpoint="<?= $isEdit ? htmlspecialchars($base) . '/api/matches/' . (int)$matchIdValue . '/edit' : '' ?>"
      data-initial-video-type="<?= htmlspecialchars($videoType) ?>"
      data-initial-download-status="<?= htmlspecialchars($initialDownloadStatus) ?>"
      data-initial-download-progress="<?= (int)$initialDownloadProgress ?>"
      data-initial-veo-url="<?= htmlspecialchars($initialVeoUrl) ?>">

          <input type="hidden" id="matchIdInput" name="match_id" value="<?= $matchIdValue ? (int)$matchIdValue : '' ?>">

          <div class="wizard-step-panel is-active" data-step="1">
                    <div class="panel p-3 rounded-md">
                              <div class="panel-body">
                                        <?php if (empty($teams)): ?>
                                                  <div class="alert alert-info mb-3 text-light">No teams yet -- create them below to continue.</div>
                                        <?php endif; ?>
                                        <div class="row g-3">
                                                  <?php if ($isPlatformAdmin): ?>
                                                            <div class="col-md-6">
                                                                      <label class="form-label text-light">Club</label>
                                                                      <select name="club_id" class="form-select select-dark" <?= $isEdit ? 'disabled' : '' ?>>
                                                                                <?php foreach ($clubs as $club): ?>
                                                                                          <option value="<?= (int)$club['id'] ?>" <?= (int)$club['id'] === $selectedClubId ? 'selected' : '' ?>>
                                                                                                    <?= htmlspecialchars($club['name']) ?>
                                                                                          </option>
                                                                                <?php endforeach; ?>
                                                                      </select>
                                                                      <?php if ($isEdit): ?>
                                                                                <input type="hidden" name="club_id" value="<?= $selectedClubId ?>">
                                                                      <?php endif; ?>
                                                            </div>
                                                  <?php else: ?>
                                                            <input type="hidden" name="club_id" value="<?= $selectedClubId ?>">
                                                  <?php endif; ?>

                                                  <div class="col-12">
                                                            <div class="d-flex flex-wrap gap-3 align-items-end">
                                                                      <div class="flex-fill">
                                                                                <div class="d-flex align-items-center justify-content-between mb-1">
                                                                                          <label class="form-label text-light mb-0">Home team</label>
                                                                                          <button type="button" class="btn btn-link btn-sm text-decoration-none text-light" data-add-team="home">
                                                                                                    + Add team
                                                                                          </button>
                                                                                </div>
                                                                                <select name="home_team_id" class="form-select select-dark" required <?= empty($teams) ? 'disabled' : '' ?>>
                                                                                          <?php foreach ($teams as $team): ?>
                                                                                                    <option value="<?= (int)$team['id'] ?>" <?= $matchHomeId == $team['id'] ? 'selected' : '' ?>>
                                                                                                              <?= htmlspecialchars($team['name']) ?>
                                                                                                    </option>
                                                                                          <?php endforeach; ?>
                                                                                </select>
                                                                      </div>
                                                                      <div class="text-light opacity-75 fw-semibold">vs</div>
                                                                      <div class="flex-fill">
                                                                                <div class="d-flex align-items-center justify-content-between mb-1">
                                                                                          <label class="form-label text-light mb-0">Away team</label>
                                                                                          <button type="button" class="btn btn-link btn-sm text-decoration-none text-light" data-add-team="away">
                                                                                                    + Add team
                                                                                          </button>
                                                                                </div>
                                                                                <select name="away_team_id" class="form-select select-dark" required <?= empty($teams) ? 'disabled' : '' ?>>
                                                                                          <?php foreach ($teams as $team): ?>
                                                                                                    <option value="<?= (int)$team['id'] ?>" <?= $matchAwayId == $team['id'] ? 'selected' : '' ?>>
                                                                                                              <?= htmlspecialchars($team['name']) ?>
                                                                                                    </option>
                                                                                          <?php endforeach; ?>
                                                                                </select>
                                                                      </div>
                                                            </div>
                                                  </div>

                                                  <div class="col-md-6">
                                                            <label class="form-label text-light">Kickoff</label>
                                                            <input type="datetime-local" name="kickoff_at" class="form-control input-dark" value="<?= htmlspecialchars($kickoffValue) ?>">
                                                  </div>

                                                  <div class="col-md-6">
                                                            <label class="form-label text-light">Status</label>
                                                            <select name="status" class="form-select select-dark">
                                                                      <?php $status = $matchStatus; ?>
                                                                      <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
                                                                      <option value="ready" <?= $status === 'ready' ? 'selected' : '' ?>>Ready</option>
                                                            </select>
                                                  </div>

                                                  <div class="col-12">
                                                            <button class="btn btn-secondary-soft btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#optionalFields" aria-expanded="false" aria-controls="optionalFields">
                                                                      Optional fields
                                                            </button>
                                                  </div>

                                                  <div class="collapse" id="optionalFields">
                                                            <div class="row g-3 mt-1">
                                                                      <div class="col-md-6">
                                                                                <div class="d-flex align-items-center justify-content-between mb-1">
                                                                                          <label class="form-label text-light mb-0">Season</label>
                                                                                          <button type="button" class="btn btn-link btn-sm text-decoration-none text-light" data-add-season>+ Add season</button>
                                                                                </div>
                                                                                <select name="season_id" class="form-select select-dark">
                                                                                          <option value="">None</option>
                                                                                          <?php foreach ($seasons as $season): ?>
                                                                                                    <option value="<?= (int)$season['id'] ?>" <?= $matchSeasonId == $season['id'] ? 'selected' : '' ?>>
                                                                                                              <?= htmlspecialchars($season['name']) ?>
                                                                                                    </option>
                                                                                          <?php endforeach; ?>
                                                                                </select>
                                                                                <div class="form-text text-secondary">Optional context for competitions.</div>
                                                                      </div>

                                                                      <div class="col-md-6">
                                                                                <div class="d-flex align-items-center justify-content-between mb-1">
                                                                                          <label class="form-label text-light mb-0">Competition</label>
                                                                                          <button type="button" class="btn btn-link btn-sm text-decoration-none text-light" data-add-competition>+ Add competition</button>
                                                                                </div>
                                                                                <select name="competition_id" class="form-select select-dark">
                                                                                          <option value="">None</option>
                                                                                          <?php foreach ($competitions as $competition): ?>
                                                                                                    <option value="<?= (int)$competition['id'] ?>" <?= $matchCompetitionId == $competition['id'] ? 'selected' : '' ?>>
                                                                                                              <?= htmlspecialchars($competition['name']) ?>
                                                                                                    </option>
                                                                                          <?php endforeach; ?>
                                                                                </select>
                                                                                <div class="form-text text-secondary">Optional league or tournament.</div>
                                                                      </div>

                                                                      <div class="col-md-6">
                                                                                <label class="form-label text-light">Venue</label>
                                                                                <input type="text" name="venue" class="form-control input-dark" value="<?= htmlspecialchars($matchVenue) ?>">
                                                                      </div>

                                                                      <div class="col-md-6">
                                                                                <label class="form-label text-light">Referee</label>
                                                                                <input type="text" name="referee" class="form-control input-dark" value="<?= htmlspecialchars($matchReferee) ?>">
                                                                      </div>

                                                                      <div class="col-md-6">
                                                                                <label class="form-label text-light">Attendance</label>
                                                                                <input type="number" name="attendance" class="form-control input-dark" min="0" step="1" value="<?= $matchAttendance !== null ? htmlspecialchars((string)$matchAttendance) : '' ?>">
                                                                      </div>
                                                            </div>
                                                  </div>
                                        </div>
                                        <div class="d-flex justify-content-end mt-3">
                                                  <button type="button" class="btn btn-primary-soft" id="step1Next" <?= empty($teams) ? 'disabled' : '' ?>>Continue to video</button>
                                        </div>
                              </div>
                    </div>
          </div>

          <div class="wizard-step-panel" data-step="2">
                    <div class="panel p-3 rounded-md">
                              <div class="panel-body">
                                        <div class="mb-3">
                                                  <label class="form-label text-light">Video source</label>
                                        <div class="wizard-source-options d-flex flex-wrap gap-3">
                                                  <label class="wizard-source-option">
                                                            <input type="radio" class="form-check-input" name="video_mode" id="videoModeVeo" value="veo" <?= $videoType === 'veo' ? 'checked' : '' ?>>
                                                            <div class="wizard-source-copy">
                                                                      <div class="fw-semibold">VEO URL</div>
                                                                      <div class="text-muted-alt text-sm">Download from https://app.veo.co/matches/</div>
                                                            </div>
                                                  </label>
                                                  <label class="wizard-source-option">
                                                            <input type="radio" class="form-check-input" name="video_mode" id="videoModeUpload" value="upload" <?= $videoType !== 'veo' ? 'checked' : '' ?>>
                                                            <div class="wizard-source-copy">
                                                                      <div class="fw-semibold">Upload file</div>
                                                                      <div class="text-muted-alt text-sm">Import a raw video already on the server.</div>
                                                            </div>
                                                  </label>
                                        </div>
                                        </div>

                                        <div class="mb-3" id="videoUploadGroup">
                                                  <label class="form-label text-light">Raw video</label>
                                                  <select name="video_source_path" id="video_file_select" class="form-select select-dark" <?= $videoType === 'veo' ? 'disabled' : '' ?> <?= empty($videoFiles) ? 'disabled' : '' ?>>
                                                            <option value="">Select raw video</option>
                                                            <?php foreach ($videoFiles as $file): ?>
                                                                      <option value="<?= htmlspecialchars($file['web_path']) ?>" <?= $videoPath === $file['web_path'] ? 'selected' : '' ?>><?= htmlspecialchars($file['filename']) ?></option>
                                                            <?php endforeach; ?>
                                                            <?php if ($videoType !== 'veo' && $videoPath && !$hasCurrentVideo): ?>
                                                                      <option value="<?= htmlspecialchars($videoPath) ?>" selected>Current: <?= htmlspecialchars(basename($videoPath)) ?></option>
                                                            <?php endif; ?>
                                                  </select>
                                                  <div class="form-text text-secondary"><?= empty($videoFiles) ? 'No raw videos found in /videos/raw.' : 'Choose from the raw videos directory.' ?></div>
                                        </div>

                                        <div class="mb-3" id="videoVeoGroup">
                                                  <label class="form-label text-light">VEO match URL</label>
                                                  <input type="text" id="video_url_input" name="veo_url" class="form-control input-dark" placeholder="https://app.veo.co/matches/..." value="<?= htmlspecialchars($videoType === 'veo' ? $initialVeoUrl : '') ?>" <?= $videoType === 'veo' ? '' : 'disabled' ?>>
                                                  <div class="form-text text-secondary">Server-side download using yt-dlp and ffmpeg.</div>
                                        </div>

                                        <div class="panel p-3 rounded-md panel-dark veo-download-panel d-none" id="veoDownloadPanel">
                                                  <div class="d-flex justify-content-between align-items-center mb-2">
                                                            <div>
                                                                      <div class="text-xs text-muted-alt">VEO download progress</div>
                                                                      <div id="veoInlineSummary" class="fw-semibold">Waiting to start</div>
                                                            </div>
                                                            <span id="veoInlineStatusBadge" class="wizard-status wizard-status-pending">Pending</span>
                                                  </div>
                                                  <div class="progress bg-surface border border-soft mb-2" style="height: 12px;">
                                                            <div id="veoInlineProgressBar" class="progress-bar bg-primary" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width: 0%;"></div>
                                                  </div>
                                                  <div class="d-flex justify-content-between text-muted-alt text-sm mb-1">
                                                            <span id="veoInlineStatusText">Not started</span>
                                                            <span id="veoInlineProgressText">0%</span>
                                                  </div>
                                                  <div class="text-muted-alt text-sm mb-2" id="veoInlineSizeText"></div>
                                                  <div class="alert alert-danger d-none" id="veoInlineError"></div>
                                                  <div class="d-flex align-items-center justify-content-between gap-2">
                                                            <button type="button" class="btn btn-outline-danger btn-sm d-none" id="veoInlineRetryBtn">Retry download</button>
                                                            <span class="text-muted-alt text-xs mb-0">Downloading continues in the background; navigation stays enabled.</span>
                                                  </div>
                                        </div>

                                        <div class="d-flex justify-content-between mt-3">
                                                  <button type="button" class="btn btn-secondary-soft" id="step2Back">Back</button>
                                                  <button type="button" class="btn btn-primary-soft" id="wizardSubmitBtn"><?= $isEdit ? 'Save & start' : 'Create & start' ?></button>
                                        </div>
                              </div>
                    </div>
          </div>

          <div class="wizard-step-panel" data-step="3">
                    <div class="panel p-3 rounded-md">
                              <div class="panel-body">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                                  <div>
                                                            <div class="text-muted-alt text-sm">Download status</div>
                                                            <div id="wizardSummaryText" class="fw-semibold">Waiting to start</div>
                                                  </div>
                                                  <span id="downloadStatusBadge" class="wizard-status wizard-status-pending">Pending</span>
                                        </div>
                                        <div class="progress bg-surface border border-soft mb-2" style="height: 12px;">
                                                  <div id="downloadProgressBar" class="progress-bar bg-primary" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width: 0%;"></div>
                                        </div>
                                        <div class="d-flex justify-content-between text-muted-alt text-sm mb-2">
                                                  <span id="downloadStatusText">Not started</span>
                                                  <span id="downloadProgressText">0%</span>
                                        </div>
                                        <div class="text-muted-alt text-sm mb-2" id="downloadSizeText"></div>
                                        <div id="wizardError" class="alert alert-danger d-none"></div>
                                        <div class="d-flex gap-2">
                                                  <button type="button" class="btn btn-secondary-soft d-none" id="wizardRetryBtn">Retry download</button>
                                                  <button type="button" class="btn btn-outline-danger d-none" id="wizardCancelBtn">Cancel download</button>
                                                  <a id="wizardContinueBtn" class="btn btn-primary-soft disabled" href="#" aria-disabled="true">Back to matches</a>
                                        </div>
                                       <p class="text-muted-alt text-sm mt-2 mb-0">We poll every 2 seconds. Leaving the page will not stop the download.</p>
                              </div>
                    </div>
          </div>
          <?php if ($debugMode): ?>
                    <div id="matchDebugConsole" class="panel p-3 rounded-md panel-dark mb-3">
                              <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div>
                                                  <div class="text-xs text-muted-alt">Console mode</div>
                                                  <div class="fw-semibold text-light">Download diagnostics</div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-secondary-soft" id="matchDebugClearBtn">Clear</button>
                              </div>
                              <div id="matchDebugMessages" class="debug-console-entries text-xs text-light" style="min-height:120px; max-height:200px; overflow:auto; font-family:monospace; white-space:pre-wrap;"></div>
                              <div class="text-muted-alt text-xs mt-2 mb-1">Latest progress JSON</div>
                              <pre id="matchDebugJson" class="bg-surface border border-soft rounded-md p-2" style="max-height:180px; overflow:auto; font-size:0.75rem;"></pre>
                    </div>
          <?php endif; ?>
          <div class="wizard-step-panel" data-step="4">
                    <?php require __DIR__ . '/wizard-step-lineup.php'; ?>
          </div>
<?php require __DIR__ . '/wizard-step-lineup-modal.php'; ?>
</form>

<div id="setupModals">
          <div id="teamCreateModal" class="setup-modal" role="dialog" aria-hidden="true" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:2100; align-items:center; justify-content:center;">
                    <div class="panel p-3 rounded-md" style="max-width:420px; width:100%; margin:0 16px;">
                              <div class="d-flex align-items-center justify-content-between mb-3">
                                        <div>
                                                  <div class="text-xs text-muted-alt">Create team</div>
                                                  <div class="fw-semibold text-light">Add to club roster</div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-secondary-soft" data-setup-close-modal="teamCreateModal" aria-label="Close modal">×</button>
                              </div>
                              <form id="teamCreateForm" class="row g-3">
                                        <input type="hidden" name="club_id" value="<?= (int)$selectedClubId ?>">
                                        <div class="col-12">
                                                  <label class="form-label text-light" for="teamNameInput">Team name</label>
                                                  <input type="text" id="teamNameInput" name="name" class="form-control input-dark" required>
                                        </div>
                                        <div class="col-12">
                                                  <div id="teamCreateError" class="text-danger small d-none"></div>
                                        </div>
                                        <div class="col-12 d-flex justify-content-end gap-2">
                                                  <button type="button" class="btn btn-secondary-soft" data-setup-close-modal="teamCreateModal">Cancel</button>
                                                  <button type="submit" class="btn btn-primary-soft">Create team</button>
                                        </div>
                              </form>
                    </div>
          </div>

          <div id="seasonCreateModal" class="setup-modal" role="dialog" aria-hidden="true" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:2100; align-items:center; justify-content:center;">
                    <div class="panel p-3 rounded-md" style="max-width:420px; width:100%; margin:0 16px;">
                              <div class="d-flex align-items-center justify-content-between mb-3">
                                        <div>
                                                  <div class="text-xs text-muted-alt">Create season</div>
                                                  <div class="fw-semibold text-light">Add season context</div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-secondary-soft" data-setup-close-modal="seasonCreateModal" aria-label="Close modal">×</button>
                              </div>
                              <form id="seasonCreateForm" class="row g-3">
                                        <input type="hidden" name="club_id" value="<?= (int)$selectedClubId ?>">
                                        <div class="col-12">
                                                  <label class="form-label text-light" for="seasonNameInput">Season name</label>
                                                  <input type="text" id="seasonNameInput" name="name" class="form-control input-dark" required>
                                        </div>
                                        <div class="col-12">
                                                  <div id="seasonCreateError" class="text-danger small d-none"></div>
                                        </div>
                                        <div class="col-12 d-flex justify-content-end gap-2">
                                                  <button type="button" class="btn btn-secondary-soft" data-setup-close-modal="seasonCreateModal">Cancel</button>
                                                  <button type="submit" class="btn btn-primary-soft">Create season</button>
                                        </div>
                              </form>
                    </div>
          </div>

          <div id="competitionCreateModal" class="setup-modal" role="dialog" aria-hidden="true" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:2100; align-items:center; justify-content:center;">
                    <div class="panel p-3 rounded-md" style="max-width:420px; width:100%; margin:0 16px;">
                              <div class="d-flex align-items-center justify-content-between mb-3">
                                        <div>
                                                  <div class="text-xs text-muted-alt">Create competition</div>
                                                  <div class="fw-semibold text-light">Add a competition</div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-secondary-soft" data-setup-close-modal="competitionCreateModal" aria-label="Close modal">×</button>
                              </div>
                              <form id="competitionCreateForm" class="row g-3">
                                        <input type="hidden" name="club_id" value="<?= (int)$selectedClubId ?>">
                                        <div class="col-12">
                                                  <label class="form-label text-light" for="competitionNameInput">Competition name</label>
                                                  <input type="text" id="competitionNameInput" name="name" class="form-control input-dark" required>
                                        </div>
                                        <div class="col-12">
                                                  <div id="competitionCreateError" class="text-danger small d-none"></div>
                                        </div>
                                        <div class="col-12 d-flex justify-content-end gap-2">
                                                  <button type="button" class="btn btn-secondary-soft" data-setup-close-modal="competitionCreateModal">Cancel</button>
                                                  <button type="submit" class="btn btn-primary-soft">Create competition</button>
                                        </div>
                              </form>
                    </div>
          </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../layout.php';
