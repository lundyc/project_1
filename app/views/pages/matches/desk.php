<?php
require_auth();
require_once __DIR__ . '/../../../lib/match_permissions.php';
require_once __DIR__ . '/../../../lib/match_repository.php';
require_once __DIR__ . '/../../../lib/match_player_repository.php';
require_once __DIR__ . '/../../../lib/event_repository.php';
require_once __DIR__ . '/../../../lib/match_lock_service.php';
require_once __DIR__ . '/../../../lib/event_outcome_rules.php';
require_once __DIR__ . '/../../../lib/event_action_stack.php';
require_once __DIR__ . '/../../../lib/csrf.php';

$user = current_user();
$roles = $_SESSION['roles'] ?? [];

if (!isset($match)) {
          http_response_code(404);
          echo 'Match not found';
          exit;
}

$base = base_path();
$canView = can_view_match($user, $roles, (int)$match['club_id']);
$canEditRoles = in_array('platform_admin', $roles, true) || in_array('club_admin', $roles, true) || in_array('analyst', $roles, true);
$canManage = $canEditRoles && can_manage_match_for_club($user, $roles, (int)$match['club_id']);

if (!$canView && !$canManage) {
          http_response_code(403);
          echo '403 Forbidden';
          exit;
}

$currentLock = findLock((int)$match['id']);

$matchPlayers = get_match_players((int)$match['id']);
$players = array_map(fn($p) => [
          'id' => (int)$p['id'],
          'team_side' => $p['team_side'],
          'display_name' => $p['display_name'],
          'shirt_number' => $p['shirt_number'],
          'is_starting' => (int)$p['is_starting'],
          'position_label' => $p['position_label'],
], $matchPlayers);
$homePlayers = array_values(array_filter($players, fn($p) => $p['team_side'] === 'home'));
$awayPlayers = array_values(array_filter($players, fn($p) => $p['team_side'] === 'away'));

ensure_default_event_types((int)$match['club_id']);

$db = db();
$eventTypes = $db->prepare('SELECT id, label, type_key, default_importance FROM event_types WHERE club_id = :club_id ORDER BY label ASC');
$eventTypes->execute(['club_id' => (int)$match['club_id']]);
$eventTypes = $eventTypes->fetchAll();
$rawOutcomeOptionsByTypeId = get_outcome_options_by_event_type_id($db);
$outcomeOptions = [];
$outcomeOptionsByTypeId = [];
foreach ($eventTypes as $eventType) {
          $typeId = isset($eventType['id']) ? (int)$eventType['id'] : 0;
          if ($typeId <= 0) {
                    continue;
          }
          $options = $rawOutcomeOptionsByTypeId[$typeId] ?? [];
          $sanitized = array_values(array_map('strval', (array)$options));
          $outcomeOptions[$typeId] = $sanitized;
          $outcomeOptionsByTypeId[$typeId] = $sanitized;
}

$tagsStmt = db()->prepare('SELECT id, label FROM tags WHERE club_id IS NULL OR club_id = :club_id ORDER BY label ASC');
$tagsStmt->execute(['club_id' => (int)$match['club_id']]);
$tags = $tagsStmt->fetchAll();

$title = 'Analysis Desk';
$headExtras = '<link href="' . htmlspecialchars($base) . '/assets/css/desk.css?v=' . time() . '" rel="stylesheet">';
$projectRoot = realpath(__DIR__ . '/../../../../');
$matchId = (int)$match['id'];
$isVeo = (($match['video_source_type'] ?? '') === 'veo');
$standardRelative = '/videos/matches/match_' . $matchId . '/source/veo/standard/match_' . $matchId . '_standard.mp4';
$standardAbsolute = $projectRoot
          ? $projectRoot . DIRECTORY_SEPARATOR . 'videos' . DIRECTORY_SEPARATOR . 'matches' . DIRECTORY_SEPARATOR . 'match_' . $matchId . DIRECTORY_SEPARATOR . 'source' . DIRECTORY_SEPARATOR . 'veo' . DIRECTORY_SEPARATOR . 'standard' . DIRECTORY_SEPARATOR . 'match_' . $matchId . '_standard.mp4'
          : '';
$standardReady = $standardAbsolute && is_file($standardAbsolute);
$panoramicRelative = '/videos/matches/match_' . $matchId . '/source/veo/panoramic/match_' . $matchId . '_panoramic.mp4';
$panoramicAbsolute = $projectRoot
          ? $projectRoot . DIRECTORY_SEPARATOR . 'videos' . DIRECTORY_SEPARATOR . 'matches' . DIRECTORY_SEPARATOR . 'match_' . $matchId . DIRECTORY_SEPARATOR . 'source' . DIRECTORY_SEPARATOR . 'veo' . DIRECTORY_SEPARATOR . 'panoramic' . DIRECTORY_SEPARATOR . 'match_' . $matchId . '_panoramic.mp4'
          : '';
$panoramicReady = $panoramicAbsolute && is_file($panoramicAbsolute);
$videoReady = $isVeo ? (bool)$standardReady : $standardReady;
$videoPath = $videoReady ? $standardRelative : ($match['video_source_path'] ?? '');
$videoSrc = $isVeo ? $standardRelative : ($videoReady ? $standardRelative : '');

$videoFormats = [];
$defaultFormatId = null;
$placeholderMessage = 'Video will appear once the download completes.';
if ($isVeo) {
          $defaultFormatId = 'standard';
          $placeholderMessage = 'This VEO standard video is downloading; it will appear once ready.';
          $videoFormats = [
                    [
                              'id' => 'standard',
                              'label' => 'Standard',
                              'relative_path' => $standardRelative,
                              'ready' => (bool)$standardReady,
                              'placeholder' => $placeholderMessage,
                    ],
                    [
                              'id' => 'panoramic',
                              'label' => 'Panoramic',
                              'relative_path' => $panoramicRelative,
                              'ready' => (bool)$panoramicReady,
                              'placeholder' => 'This VEO panoramic video is downloading; it will appear once ready.',
                    ],
          ];
}

$csrfToken = get_csrf_token();

$deskConfig = [
          'basePath' => $base,
          'matchId' => $matchId,
          'userId' => (int)$user['id'],
          'userName' => $user['display_name'],
          'canEditRole' => $canManage,
          'eventTypes' => $eventTypes,
          'tags' => $tags,
          'players' => array_merge($homePlayers, $awayPlayers),
          'outcomeOptions' => $outcomeOptions,
          'outcomeOptionsByTypeId' => $outcomeOptionsByTypeId,
          'actionStack' => get_event_action_stack_status($matchId, (int)$user['id']),
          'lock' => $currentLock ? [
                    'locked_by' => ['id' => (int)$currentLock['locked_by'], 'display_name' => $currentLock['locked_by_name']],
                    'locked_at' => $currentLock['locked_at'],
                    'last_heartbeat_at' => $currentLock['last_heartbeat_at'],
          ] : null,
          'csrfToken' => $csrfToken,
          'video' => [
                    'source_path' => $videoPath,
                    'full_path' => $videoSrc,
                    'source_type' => $match['video_source_type'] ?? 'file',
          ],
          'endpoints' => [
                  'lockAcquire' => $base . '/api/matches/' . (int)$match['id'] . '/lock/acquire',
                  'lockHeartbeat' => $base . '/api/matches/' . (int)$match['id'] . '/lock/heartbeat',
                    'lockRelease' => $base . '/api/matches/' . (int)$match['id'] . '/lock/release',
                    'events' => $base . '/api/matches/' . (int)$match['id'] . '/events',
                    'eventCreate' => $base . '/api/matches/' . (int)$match['id'] . '/events/create',
                    'eventUpdate' => $base . '/api/matches/' . (int)$match['id'] . '/events/update',
                    'eventDelete' => $base . '/api/matches/' . (int)$match['id'] . '/events/delete',
                    'undoEvent' => $base . '/api/events/undo',
                    'redoEvent' => $base . '/api/events/redo',
                    'periodsStart' => $base . '/api/matches/' . (int)$match['id'] . '/periods/start',
                    'periodsEnd' => $base . '/api/matches/' . (int)$match['id'] . '/periods/end',
                  'periodsSet' => $base . '/api/match-periods/set',
                  'clipCreate' => $base . '/api/matches/' . (int)$match['id'] . '/clips/create',
                  'clipDelete' => $base . '/api/matches/' . (int)$match['id'] . '/clips/delete',
          ],
];

$footerScripts = '<script>window.DeskConfig = ' . json_encode($deskConfig) . ';</script>';
$footerScripts .= '<script>console.log(\'DeskConfig.outcomeOptionsByTypeId\', DeskConfig.outcomeOptionsByTypeId);</script>';
$footerScripts .= '<script src="' . htmlspecialchars($base) . '/assets/js/desk-events.js?v=' . time() . '"></script>';
$videoProgressConfig = [
          'matchId' => $matchId,
          'progressUrl' => $base . '/api/match-video/progress?match_id=' . $matchId,
          'retryUrl' => $base . '/api/match-video/retry',
          'standardPath' => $standardRelative,
          'videoReady' => $videoReady,
          'defaultFormatId' => $defaultFormatId,
          'videoFormats' => $videoFormats,
          'csrfToken' => $csrfToken,
];
$footerScripts .= '<script>window.MatchVideoDeskConfig = ' . json_encode($videoProgressConfig) . ';</script>';
$footerScripts .= '<script src="' . htmlspecialchars($base) . '/assets/js/match-video-progress.js?v=' . time() . '"></script>';

ob_start();
?>
<div id="deskRoot" data-base-path="<?= htmlspecialchars($base) ?>" data-match-id="<?= (int)$match['id'] ?>"></div>
<div id="deskError" class="desk-toast desk-toast-error" style="display:none;"></div>

<div class="desk-shell">
          <?php if (empty($match['video_source_path'])): ?>
                    <div class="desk-alert">No video available for this match.</div>
          <?php endif; ?>

          <div class="desk-main">
                    <div class="desk-body">
                              <div class="desk-left">
                                        <div class="panel-dark video-panel">
                                                  <div class="panel-row video-header">
                                                            <div class="video-title">
                                                                      <div class="text-subtle text-xs">Analysis Desk</div>
                                                                      <div class="text-xl fw-semibold"><?= htmlspecialchars($match['home_team']) ?> vs <?= htmlspecialchars($match['away_team']) ?></div>
                                                            </div>
                                                            <div class="video-actions">
                                                                      <div class="video-actions-left">
                                                                                <a class="toggle-btn is-active summary-btn" href="<?= htmlspecialchars($base) ?>/matches/<?= $matchId ?>/summary">Summary</a>
                                                                                <?php if (!empty($videoFormats) && count($videoFormats) > 1): ?>
                                                                                          <div class="video-format-toggle" data-video-format-toggle>
                                                                                                    <?php foreach ($videoFormats as $format): ?>
                                                                                                              <button
                                                                                                                        type="button"
                                                                                                                        class="toggle-btn video-format-btn<?= $format['id'] === $defaultFormatId ? ' is-active' : '' ?>"
                                                                                                                        data-video-format-id="<?= htmlspecialchars($format['id']) ?>"
                                                                                                                        aria-pressed="<?= $format['id'] === $defaultFormatId ? 'true' : 'false' ?>">
                                                                                                                        <?= htmlspecialchars($format['label']) ?>
                                                                                                              </button>
                                                                                                    <?php endforeach; ?>
                                                                                          </div>
                                                                                <?php endif; ?>
                                                                      </div>
                                                                      <div class="desk-lock lock-pill">
                                                                                                                                               <div id="lockStatusText" class="text-sm"></div>
                                                                                <?php if ($canManage): ?>
                                                                                          <button id="lockRetryBtn" class="ghost-btn ghost-btn-sm">Retry lock</button>
                                                                                <?php else: ?>
                                                                                          <span class="chip chip-muted">Read-only</span>
                                                                                <?php endif; ?>
                                                                      </div>
                                                            </div>
                                                  </div>
                                                  <div class="video-content">
                                                            <video
                                                                      id="deskVideoPlayer"
                                                                      class="video-player<?= $videoReady ? '' : ' d-none' ?>"
                                                                      preload="metadata"
                                                                      controls
                                                                      <?= $videoReady ? 'src="' . htmlspecialchars($videoSrc) . '"' : '' ?>>
                                                            </video>
                                                            <div id="deskVideoPlaceholder" class="text-center text-muted mb-3<?= $videoReady ? ' d-none' : '' ?>">
                                                                      <?= htmlspecialchars($placeholderMessage) ?>
                                                            </div>
                                                            <div class="panel p-3 rounded-md panel-dark mt-3<?= $videoReady ? ' d-none' : '' ?>" id="deskVideoProgressPanel">
                                                                      <div class="d-flex justify-content-between align-items-center mb-2">
                                                                                <div>
                                                                                          <div class="text-xs text-muted-alt">VEO download</div>
                                                                                          <div id="deskInlineSummary" class="fw-semibold">Waiting to start</div>
                                                                                </div>
                                                                                <span id="deskInlineStatusBadge" class="wizard-status wizard-status-pending">Pending</span>
                                                                      </div>
                                                                      <div class="progress bg-surface border border-soft mb-2" style="height: 12px;">
                                                                                <div
                                                                                          id="deskInlineProgressBar"
                                                                                          class="progress-bar bg-primary"
                                                                                          role="progressbar"
                                                                                          aria-valuemin="0"
                                                                                          aria-valuemax="100"
                                                                                          style="width: 0%;">
                                                                                </div>
                                                                      </div>
                                                                      <div class="d-flex justify-content-between text-muted-alt text-sm mb-1">
                                                                                <span id="deskInlineStatusText">Not started</span>
                                                                                <span id="deskInlineProgressText">0%</span>
                                                                      </div>
                                                                      <div class="text-muted-alt text-sm mb-2" id="deskInlineSizeText"></div>
                                                                      <div class="alert alert-danger d-none" id="deskInlineError"></div>
                                                                      <div class="d-flex align-items-center justify-content-between gap-2">
                                                                                <button type="button" class="btn btn-outline-danger btn-sm d-none" id="deskInlineRetryBtn">Retry download</button>
                                                                                <span class="text-muted-alt text-xs">We poll every 2 seconds; downloads continue in the background.</span>
                                                                      </div>
                                                            </div>
                                                  </div>
                                        </div>
                              </div>

                              <div class="desk-right">
                                        <div class="panel-dark tagging-panel">
                                                  <div class="panel-row">
                                                            <div>
                                                                      <div class="text-sm text-subtle">Quick Tag</div>
                                                                      <div class="text-xs text-muted-alt">One click = one event</div>
                                                            </div>
                                                            <span id="deskJsBadge" class="chip chip-muted">...</span>
                                                  </div>

                                                  <div class="period-controls period-btn-row">
                                                            <button class="ghost-btn ghost-btn-sm desk-editable period-btn period-start" id="btnPeriodFirstStart" type="button" data-period-key="first_half" data-period-action="start" data-period-label="First Half" data-period-event="period_start">▶ 1H</button>
                                                            <button class="ghost-btn ghost-btn-sm desk-editable period-btn period-end" id="btnPeriodFirstEnd" type="button" data-period-key="first_half" data-period-action="end" data-period-label="First Half" data-period-event="period_end">■ 1H</button>
                                                            <button class="ghost-btn ghost-btn-sm desk-editable period-btn period-start" id="btnPeriodSecondStart" type="button" data-period-key="second_half" data-period-action="start" data-period-label="Second Half" data-period-event="period_start">▶ 2H</button>
                                                            <button class="ghost-btn ghost-btn-sm desk-editable period-btn period-end" id="btnPeriodSecondEnd" type="button" data-period-key="second_half" data-period-action="end" data-period-label="Second Half" data-period-event="period_end">■ 2H</button>
                                                            <button class="ghost-btn ghost-btn-sm desk-editable period-btn period-start" id="btnPeriodET1Start" type="button" data-period-key="extra_time_1" data-period-action="start" data-period-label="Extra Time 1" data-period-event="period_start">▶ ET1</button>
                                                            <button class="ghost-btn ghost-btn-sm desk-editable period-btn period-end" id="btnPeriodET1End" type="button" data-period-key="extra_time_1" data-period-action="end" data-period-label="Extra Time 1" data-period-event="period_end">■ ET1</button>
                                                            <button class="ghost-btn ghost-btn-sm desk-editable period-btn period-start" id="btnPeriodET2Start" type="button" data-period-key="extra_time_2" data-period-action="start" data-period-label="Extra Time 2" data-period-event="period_start">▶ ET2</button>
                                                            <button class="ghost-btn ghost-btn-sm desk-editable period-btn period-end" id="btnPeriodET2End" type="button" data-period-key="extra_time_2" data-period-action="end" data-period-label="Extra Time 2" data-period-event="period_end">■ ET2</button>
                                                            <button class="ghost-btn ghost-btn-sm desk-editable period-btn period-start" id="btnPeriodPenaltiesStart" type="button" data-period-key="penalties" data-period-action="start" data-period-label="Penalties" data-period-event="period_start">▶ P</button>
                                                            <button class="ghost-btn ghost-btn-sm desk-editable period-btn period-end" id="btnPeriodPenaltiesEnd" type="button" data-period-key="penalties" data-period-action="end" data-period-label="Penalties" data-period-event="period_end">■ P</button>
                                                  </div>

                                                  <div id="teamToggle" class="team-toggle">
                                                            <button class="toggle-btn is-active" data-team="home">Home</button>
                                                            <button class="toggle-btn" data-team="away">Away</button>
                                                  </div>

                                                  <div id="quickTagBoard" class="qt-board"></div>
                                                  <div id="tagToast" class="desk-toast" style="display:none;"></div>
                                        </div>

                              </div>
                    </div>

                    <div class="panel-dark timeline-panel timeline-panel-full">
                              <div class="panel-row">
                                        <div class="text-sm text-subtle">Timeline</div>
                                        <div class="timeline-actions">
                                                  <button id="timelineDeleteAll" class="ghost-btn ghost-btn-sm desk-editable" type="button">Delete all</button>
                                                  <div class="timeline-undo-redo">
                                                            <button class="ghost-btn ghost-btn-sm desk-editable" id="eventUndoBtn" type="button" disabled>Undo</button>
                                                            <button class="ghost-btn ghost-btn-sm desk-editable" id="eventRedoBtn" type="button" disabled>Redo</button>
                                                  </div>
                                                  <div class="timeline-mode">
                                                            <button type="button" class="ghost-btn ghost-btn-sm timeline-mode-btn is-active" data-mode="list">List</button>
                                                            <button type="button" class="ghost-btn ghost-btn-sm timeline-mode-btn" data-mode="matrix">Matrix</button>
                                                  </div>
                                                  <div class="timeline-filters">
                                                            <select id="filterTeam" class="input-pill input-pill-sm">
                                                                      <option value="">All teams</option>
                                                                      <option value="home">Home</option>
                                                                      <option value="away">Away</option>
                                                                      <option value="unknown">Unknown</option>
                                                            </select>
                                                            <select id="filterType" class="input-pill input-pill-sm">
                                                                      <option value="">All types</option>
                                                                      <?php foreach ($eventTypes as $type): ?>
                                                                                <option value="<?= (int)$type['id'] ?>"><?= htmlspecialchars($type['label']) ?></option>
                                                                      <?php endforeach; ?>
                                                            </select>
                                                            <select id="filterPlayer" class="input-pill input-pill-sm">
                                                                      <option value="">All players</option>
                                                                      <?php foreach (array_merge($homePlayers, $awayPlayers) as $p): ?>
                                                                                <option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['display_name']) ?></option>
                                                                      <?php endforeach; ?>
                                                            </select>
                                                  </div>
                                                  <div class="timeline-zoom-controls">
                                                            <button type="button" class="ghost-btn ghost-btn-sm" id="timelineZoomOut">
                                                                      <svg viewBox="0 0 16 16" aria-hidden="true">
                                                                                <circle cx="6.5" cy="6.5" r="4.5" stroke-width="1.6" stroke="currentColor" fill="none" />
                                                                                <line x1="4" y1="6.5" x2="9" y2="6.5" stroke-width="1.6" stroke="currentColor" stroke-linecap="round" />
                                                                                <line x1="10.5" y1="10.5" x2="14.5" y2="14.5" stroke-width="1.6" stroke="currentColor" stroke-linecap="round" />
                                                                      </svg>
                                                                      <span>Zoom out</span>
                                                            </button>
                                                            <button type="button" class="ghost-btn ghost-btn-sm" id="timelineZoomIn">
                                                                      <svg viewBox="0 0 16 16" aria-hidden="true">
                                                                                <circle cx="6.5" cy="6.5" r="4.5" stroke-width="1.6" stroke="currentColor" fill="none" />
                                                                                <line x1="6.5" y1="4" x2="6.5" y2="9" stroke-width="1.6" stroke="currentColor" stroke-linecap="round" />
                                                                                <line x1="4" y1="6.5" x2="9" y2="6.5" stroke-width="1.6" stroke="currentColor" stroke-linecap="round" />
                                                                                <line x1="10.5" y1="10.5" x2="14.5" y2="14.5" stroke-width="1.6" stroke="currentColor" stroke-linecap="round" />
                                                                      </svg>
                                                                      <span>Zoom in</span>
                                                            </button>
                                                            <button type="button" class="ghost-btn ghost-btn-sm" id="timelineZoomReset">Reset</button>
                                                  </div>
                                        </div>
                              </div>
                              <div class="timeline-scroll">
                                        <div class="timeline-view is-active" id="timelineList"></div>
                                        <div class="timeline-view" id="timelineMatrix"></div>
                              </div>
                    </div>

                    <div id="editorPanel" class="editor-modal is-hidden" role="dialog" aria-modal="true" aria-labelledby="editorTitle">
                              <div class="editor-modal-backdrop" data-editor-close></div>
                              <div class="panel-dark editor-modal-card">
                                        <div class="editor-modal-header">
                                                  <div>
                                                            <div id="editorTitle" class="text-sm text-subtle">Event editor</div>
                                                            <div id="editorHint" class="text-xs text-muted-alt">Click a timeline item to edit details</div>
                                                  </div>
                                                  <div class="editor-modal-header-actions">
                                                            <div class="editor-actions">
                                                                      <button class="ghost-btn ghost-btn-sm desk-editable" id="eventUseTimeBtn" type="button">Use current time</button>
                                                                      <button class="ghost-btn ghost-btn-sm desk-editable" id="eventNewBtn" type="button">Clear</button>
                                                            </div>
                                                            <button type="button" class="editor-modal-close" data-editor-close aria-label="Close event editor">✕</button>
                                                  </div>
                                        </div>
                                        <div class="btn-row mb-2 period-controls">
                                                  <button class="ghost-btn ghost-btn-sm desk-editable" id="btnPeriodStart" type="button">Period start</button>
                                                  <button class="ghost-btn ghost-btn-sm desk-editable" id="btnPeriodEnd" type="button">Period end</button>
                                        </div>
                                        <input type="hidden" id="eventId">
                                        <div class="editor-tabs-row">
                                                  <div class="editor-tabs" role="tablist">
                                                            <button id="editorTabDetails" class="editor-tab is-active" data-panel="details" role="tab" aria-controls="editorTabpanelDetails" aria-selected="true">Details</button>
                                                            <button id="editorTabOutcome" class="editor-tab" data-panel="outcome" role="tab" aria-controls="editorTabpanelOutcome" aria-selected="false">Outcome</button>
                                                            <button id="editorTabNotes" class="editor-tab" data-panel="notes" role="tab" aria-controls="editorTabpanelNotes" aria-selected="false">Notes</button>
                                                            <button id="editorTabClip" class="editor-tab" data-panel="clip" role="tab" aria-controls="editorTabpanelClip" aria-selected="false">Clip</button>
                                                  </div>
                                        </div>
                                        <div class="editor-tab-panels">
                                                  <div id="editorTabpanelDetails" class="editor-tab-panel is-active" data-panel="details" role="tabpanel" aria-labelledby="editorTabDetails">
                                                            <label class="field-label">Event type</label>
                                                            <select class="input-dark desk-editable" id="event_type_id">
                                                                      <?php foreach ($eventTypes as $type): ?>
                                                                                <option value="<?= (int)$type['id'] ?>"><?= htmlspecialchars($type['label']) ?></option>
                                                                      <?php endforeach; ?>
                                                            </select>
                                                            <div class="grid-2 gap-sm">
                                                                      <div>
                                                                                <label class="field-label">Match second</label>
                                                                                <input type="number" min="0" class="input-dark desk-editable" id="match_second">
                                                                      </div>
                                                                      <div>
                                                                                <label class="field-label">Minute / +Extra</label>
                                                                                <div class="grid-2 gap-xs">
                                                                                          <input type="number" min="0" class="input-dark desk-editable" id="minute">
                                                                                          <input type="number" min="0" class="input-dark desk-editable" id="minute_extra" placeholder="+">
                                                                                </div>
                                                                      </div>
                                                            </div>
                                                            <div class="grid-2 gap-sm">
                                                                      <div>
                                                                                <label class="field-label">Team</label>
                                                                                <select class="input-dark desk-editable" id="team_side">
                                                                                          <option value="home">Home</option>
                                                                                          <option value="away">Away</option>
                                                                                          <option value="unknown">Unknown</option>
                                                                                </select>
                                                                      </div>
                                                                      <div>
                                                                                <label class="field-label">Period</label>
                                                                                <select class="input-dark desk-editable" id="period_id">
                                                                                          <option value="">None</option>
                                                                                </select>
                                                                      </div>
                                                            </div>
                                                            <div class="grid-2 gap-sm">
                                                                      <div>
                                                                                <label class="field-label">Player</label>
                                                                                <select class="input-dark desk-editable" id="match_player_id">
                                                                                          <option value="">None</option>
                                                                                          <?php if (!empty($homePlayers)): ?>
                                                                                                    <optgroup label="Home - <?= htmlspecialchars($match['home_team']) ?>">
                                                                                                              <?php foreach ($homePlayers as $p): ?>
                                                                                                                        <option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['display_name']) ?></option>
                                                                                                              <?php endforeach; ?>
                                                                                                    </optgroup>
                                                                                          <?php endif; ?>
                                                                                          <?php if (!empty($awayPlayers)): ?>
                                                                                                    <optgroup label="Away - <?= htmlspecialchars($match['away_team']) ?>">
                                                                                                              <?php foreach ($awayPlayers as $p): ?>
                                                                                                                        <option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['display_name']) ?></option>
                                                                                                              <?php endforeach; ?>
                                                                                                    </optgroup>
                                                                                          <?php endif; ?>
                                                                                </select>
                                                                      </div>
                                                                      <div>
                                                                                <label class="field-label">Importance</label>
                                                                                <select class="input-dark desk-editable" id="importance">
                                                                                          <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                                                    <option value="<?= $i ?>"><?= $i ?></option>
                                                                                          <?php endfor; ?>
                                                                                </select>
                                                                      </div>
                                                            </div>
                                                            <label class="field-label">Phase</label>
                                                            <input type="text" class="input-dark desk-editable" id="phase" placeholder="unknown">
                                                  </div>
                                                  <div id="editorTabpanelOutcome" class="editor-tab-panel" data-panel="outcome" role="tabpanel" aria-labelledby="editorTabOutcome">
                                                            <div id="outcomeField" style="display:none;">
                                                                      <label class="field-label">Outcome</label>
                                                                      <select class="input-dark desk-editable" id="outcome"></select>
                                                            </div>
                                                            <div>
                                                                      <label class="field-label">Zone</label>
                                                                      <input type="text" class="input-dark desk-editable" id="zone">
                                                            </div>
                                                            <label class="field-label">Tags</label>
                                                            <select multiple class="input-dark desk-editable" id="tag_ids">
                                                                      <?php foreach ($tags as $tag): ?>
                                                                                <option value="<?= (int)$tag['id'] ?>"><?= htmlspecialchars($tag['label']) ?></option>
                                                                      <?php endforeach; ?>
                                                            </select>
                                                  </div>
                                                  <div id="editorTabpanelNotes" class="editor-tab-panel" data-panel="notes" role="tabpanel" aria-labelledby="editorTabNotes">
                                                            <label class="field-label">Notes</label>
                                                            <textarea class="input-dark desk-editable" rows="2" id="notes"></textarea>
                                                  </div>
                                                  <div id="editorTabpanelClip" class="editor-tab-panel" data-panel="clip" role="tabpanel" aria-labelledby="editorTabClip">
                                                            <div class="panel-row">
                                                                      <div class="text-sm text-subtle">Clip</div>
                                                                      <div class="text-xs text-muted-alt">Set IN / OUT from video</div>
                                                            </div>
                                                            <div class="grid-3 gap-sm">
                                                                      <div>
                                                                                <label class="field-label">IN (s)</label>
                                                                                <input type="number" id="clipInText" class="input-dark desk-editable clip-field" readonly>
                                                                                <div class="text-xs text-muted-alt" id="clip_in_fmt"></div>
                                                                      </div>
                                                                      <div>
                                                                                <label class="field-label">OUT (s)</label>
                                                                                <input type="number" id="clipOutText" class="input-dark desk-editable clip-field" readonly>
                                                                                <div class="text-xs text-muted-alt" id="clip_out_fmt"></div>
                                                                      </div>
                                                                      <div>
                                                                                <label class="field-label">Duration</label>
                                                                                <input type="text" id="clipDurationText" class="input-dark" readonly>
                                                                      </div>
                                                            </div>
                                                            <div class="panel-row">
                                                                      <div class="btn-row">
                                                                                <button class="ghost-btn ghost-btn-sm desk-editable" id="clipInBtn" type="button">Set IN</button>
                                                                                <button class="ghost-btn ghost-btn-sm desk-editable" id="clipOutBtn" type="button">Set OUT</button>
                                                                                <button class="ghost-btn ghost-btn-sm desk-editable" id="clipCreateBtn" type="button">Create clip</button>
                                                                                <button class="ghost-btn ghost-btn-sm desk-editable" id="clipDeleteBtn" type="button">Delete clip</button>
                                                                      </div>
                                                            </div>
                                                  </div>
                                        </div>
                                        <div class="panel-row editor-modal-footer">
                                                  <div class="btn-row">
                                                            <button class="primary-btn desk-editable" id="eventSaveBtn" type="button">Save edits</button>
                                                            <button class="ghost-btn desk-editable" id="eventDeleteBtn" type="button">Delete</button>
                                                  </div>
                                        </div>
                              </div>
                    </div>

<div class="mt-3 text-muted-alt text-sm" id="deskStatus"></div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../layout.php';
?>
