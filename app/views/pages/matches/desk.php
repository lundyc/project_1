<?php
$ANNOTATIONS_ENABLED = true;

require_auth();
require_once __DIR__ . '/../../../lib/match_permissions.php';
require_once __DIR__ . '/../../../lib/match_repository.php';
require_once __DIR__ . '/../../../lib/match_player_repository.php';
require_once __DIR__ . '/../../../lib/event_repository.php';
require_once __DIR__ . '/../../../lib/match_lock_service.php';
require_once __DIR__ . '/../../../lib/event_outcome_rules.php';
require_once __DIR__ . '/../../../lib/event_action_stack.php';
require_once __DIR__ . '/../../../lib/match_period_repository.php';
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

$periods = get_match_periods((int)$match['id']);

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
$headExtras .= '<script>window.ANNOTATIONS_ENABLED = true;</script>';
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
    'homeTeamName' => $match['home_team'] ?? 'Home',
    'awayTeamName' => $match['away_team'] ?? 'Away',
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
        'duration_seconds' => isset($match['video_duration_seconds']) ? (int)$match['video_duration_seconds'] : null,
        'match_video_id' => isset($match['video_id']) ? (int)$match['video_id'] : null,
    ],
    'annotations' => [
        'matchVideoId' => isset($match['video_id']) ? (int)$match['video_id'] : null,
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
            'playlistsList' => $base . '/api/matches/' . (int)$match['id'] . '/playlists',
            'playlistCreate' => $base . '/api/matches/' . (int)$match['id'] . '/playlists/create',
            'playlistClipsAdd' => $base . '/api/matches/' . (int)$match['id'] . '/playlists/clips/add',
            'playlistClipsRemove' => $base . '/api/matches/' . (int)$match['id'] . '/playlists/clips/remove',
            'playlistClipsReorder' => $base . '/api/matches/' . (int)$match['id'] . '/playlists/clips/reorder',
            'playlistRename' => $base . '/api/matches/' . (int)$match['id'] . '/playlists/rename',
            'playlistDelete' => $base . '/api/matches/' . (int)$match['id'] . '/playlists/delete',
            'playlistDownload' => $base . '/api/matches/' . (int)$match['id'] . '/playlists/download',
            'annotationsList' => $base . '/api/matches/' . (int)$match['id'] . '/annotations',
            'annotationsCreate' => $base . '/api/matches/' . (int)$match['id'] . '/annotations/create',
            'annotationsUpdate' => $base . '/api/matches/' . (int)$match['id'] . '/annotations/update',
            'annotationsDelete' => $base . '/api/matches/' . (int)$match['id'] . '/annotations/delete',
    ],
];

$footerScripts = '<script>window.DeskConfig = ' . json_encode($deskConfig) . ';</script>';
$footerScripts .= '<script src="' . htmlspecialchars($base) . '/assets/js/desk-events.js?v=' . time() . '"></script>';
$footerScripts .= '<script src="' . htmlspecialchars($base) . '/assets/js/desk-annotations.js?v=' . time() . '"></script>';
$footerScripts .= '<script src="' . htmlspecialchars($base) . '/assets/js/desk-video-controls.js?v=' . time() . '"></script>';
$footerScripts .= '<script src="' . htmlspecialchars($base) . '/assets/js/desk-video-interactive.js?v=' . time() . '"></script>';
$footerScripts .= '<script src="' . htmlspecialchars($base) . '/assets/js/timeline-markers.js?v=' . time() . '"></script>';
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
$footerScripts .= '<script src="' . htmlspecialchars($base) . '/assets/js/panoramic-player.js?v=' . time() . '"></script>';
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
        <div class="desk-layout">
            <div class="desk-top">
                <div class="desk-left">
                    <div class="panel-row video-header">
                        <div class="video-title">
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
                          <?= !empty($format['relative_path']) ? 'data-video-format-src="' . htmlspecialchars($format['relative_path']) . '"' : '' ?>
                          aria-pressed="<?= $format['id'] === $defaultFormatId ? 'true' : 'false' ?>">
                          <?= htmlspecialchars($format['label']) ?>
                      </button>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <section class="desk-video">
                        <div class="desk-video-shell">
                            <div class="panel-dark video-panel">
                                <div class="video-content">
                                    <div class="video-frame">
                                        <div class="desk-drawing-toolbar" data-drawing-toolbar>
                                            <?php if ($ANNOTATIONS_ENABLED): ?>
                                                <div class="drawing-toolbar" data-annotation-toolbar>
                                                    <div class="drawing-toolbar-tools">
                                                <div class="drawing-tool drawing-tool--menu drawing-tool--colors">
                                                    <button
                                                        type="button"
                                                                class="drawing-tool-btn drawing-tool-btn--primary"
                                                                data-toolbar-button
                                                                data-menu-key="colours"
                                                                aria-pressed="false"
                                                                aria-label="Colours"
                                                                
                                                                data-tooltip="Colours">
                                                                <i class="fa-solid fa-palette" aria-hidden="true"></i>
                                                            </button>
                                                            <div class="drawing-tool-panel drawing-submenu colors" data-toolbar-menu="colours" aria-hidden="true" inert>
                                                                <div class="drawing-submenu-heading">Colours</div>
                                                                <button type="button" class="drawing-option-btn drawing-colour-swatch color-swatch" style="--swatch-color:#facc15;background:#facc15;" data-pencil-color="#facc15" aria-label="Yellow"></button>
                                                                <button type="button" class="drawing-option-btn drawing-colour-swatch color-swatch" style="--swatch-color:#10b981;background:#10b981;" data-pencil-color="#10b981" aria-label="Green"></button>
                                                                <button type="button" class="drawing-option-btn drawing-colour-swatch color-swatch" style="--swatch-color:#38bdf8;background:#38bdf8;" data-pencil-color="#38bdf8" aria-label="Sky blue"></button>
                                                                <button type="button" class="drawing-option-btn drawing-colour-swatch color-swatch" style="--swatch-color:#f472b6;background:#f472b6;" data-pencil-color="#f472b6" aria-label="Pink"></button>
                                                                <button type="button" class="drawing-option-btn drawing-colour-swatch color-swatch" style="--swatch-color:#a855f7;background:#a855f7;" data-pencil-color="#a855f7" aria-label="Purple"></button>
                                                                <button type="button" class="drawing-option-btn drawing-colour-swatch color-swatch" style="--swatch-color:#fb923c;background:#fb923c;" data-pencil-color="#fb923c" aria-label="Orange"></button>
                                                                <button type="button" class="drawing-option-btn drawing-colour-swatch color-swatch" style="--swatch-color:#ef4444;background:#ef4444;" data-pencil-color="#ef4444" aria-label="Red"></button>
                                                                <button type="button" class="drawing-option-btn drawing-colour-swatch color-swatch" style="--swatch-color:#2563eb;background:#2563eb;" data-pencil-color="#2563eb" aria-label="Blue"></button>
                                                            </div>
                                                        </div>
                                                        <div class="drawing-tool drawing-tool--menu drawing-tool--arrows">
                                                            <button
                                                                type="button"
                                                                class="drawing-tool-btn drawing-tool-btn--primary"
                                                                data-toolbar-button
                                                                data-menu-key="arrows"
                                                                aria-pressed="false"
                                                                aria-label="Arrows"
                                                               
                                                                data-tooltip="Arrows">
                                                                <i class="fa-solid fa-arrow-trend-up" aria-hidden="true"></i>
                                                            </button>
                                                            <div class="drawing-tool-panel drawing-submenu" data-toolbar-menu="arrows" aria-hidden="true" inert>
                                                                <div class="drawing-submenu-heading">Arrows</div>
                                                                <div class="drawing-option-controls drawing-arrow-grid">
                                                                    <button type="button" class="drawing-option-btn" data-arrow-type="pass">Pass</button>
                                                                    <button type="button" class="drawing-option-btn" data-arrow-type="run">Run</button>
                                                                    <button type="button" class="drawing-option-btn" data-arrow-type="dribble">Dribble</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="drawing-tool drawing-tool--menu drawing-tool--shapes">
                                                            <button
                                                                id="deskCircleTool"
                                                                type="button"
                                                                class="drawing-tool-btn drawing-tool-btn--primary"
                                                                data-toolbar-button
                                                                data-menu-key="shapes"
                                                                data-drawing-tool="circle"
                                                                aria-pressed="false"
                                                                aria-label="Shapes"

                                                                data-tooltip="Shapes">
                                                                <i class="fa-regular fa-circle" aria-hidden="true"></i>
                                                            </button>
                                                            <div class="drawing-tool-panel drawing-submenu" data-toolbar-menu="shapes" aria-hidden="true" inert>
                                                                <div class="drawing-submenu-heading">Shapes</div>
                                                                <div class="drawing-option-block">
                                                                    <div class="drawing-option-controls drawing-shape-grid">
                                                                        <button
                                                                            type="button"
                                                                            class="drawing-option-btn shape-line"
                                                                            data-shape-type="line"
                                                                            aria-label="Line">
                                                                        </button>
                                                                        <button
                                                                            type="button"
                                                                            class="drawing-option-btn shape-ellipse"
                                                                            data-shape-type="ellipse"
                                                                            aria-label="Solid ellipse">
                                                                        </button>
                                                                        <button
                                                                            type="button"
                                                                            class="drawing-option-btn shape-rectangle shape-polygon"
                                                                            data-shape-type="polygon"
                                                                            aria-label="Rectangle">
                                                                            <svg
                                                                                class="MuiSvgIcon-root MuiSvgIcon-fontSizeMedium h-4 w-4 css-vubbuv"
                                                                                focusable="false"
                                                                                aria-hidden="true"
                                                                                viewBox="0 0 105 52.5">
                                                                                <g id="Ebene_2" data-name="Ebene 2">
                                                                                    <g id="Ebene_1-2" data-name="Ebene 1">
                                                                                        <polygon
                                                                                            points="12.23 59.36 60.03 5.5 103.81 5.5 86.81 59.36 12.23 59.36"
                                                                                            style="fill: none; stroke: rgb(255, 255, 255); stroke-miterlimit: 10; stroke-width: 11px;">
                                                                                        </polygon>
                                                                                    </g>
                                                                                </g>
                                                                            </svg>
                                                                        </button>
                                                                    </div>
                                                                </div>

                                                            </div>
                                                        </div>
                                                        <div class="drawing-tool drawing-tool--spotlight">
                                                            <button
                                                                id="deskSpotlightTool"
                                                                type="button"
                                                                class="drawing-tool-btn drawing-tool-btn--primary"
                                                                data-drawing-tool="spotlight"
                                                                aria-pressed="false"
                                                                aria-label="Spotlight tool"
                                                               
                                                                data-tooltip="Spotlight">
                                                                <i class="fa-solid fa-lightbulb" aria-hidden="true"></i>
                                                            </button>
                                                        </div>
                                                        <div class="drawing-tool drawing-tool--free-draw">
                                                        <button
                                                            id="deskPencilTool"
                                                            type="button"
                                                            class="drawing-tool-btn drawing-tool-btn--primary"
                                                            data-drawing-tool="pencil"
                                                            aria-pressed="false"
                                                            aria-label="Free draw"
                                                          
                                                            data-tooltip="Free draw">
                                                            <i class="fa-solid fa-pencil" aria-hidden="true"></i>
                                                        </button>
                                                        </div>
                                                        <div class="drawing-tool drawing-tool--menu drawing-tool--text">
                                                            <button
                                                                type="button"
                                                                class="drawing-tool-btn drawing-tool-btn--primary"
                                                                data-toolbar-button
                                                                data-menu-key="text"
                                                                data-drawing-tool="text"
                                                                aria-pressed="false"
                                                                aria-label="Text tool"
                                                            
                                                                data-tooltip="Text">
                                                                <i class="fa-solid fa-font" aria-hidden="true"></i>
                                                            </button>
                                                            <div class="drawing-tool-panel drawing-submenu text" data-toolbar-menu="text" aria-hidden="true" inert>
                                                                <div class="drawing-submenu-heading">Text</div>
                                                                <label class="drawing-option-label">
                                                                    <span>Size</span>
                                                                    <div class="drawing-option-range">
                                                                        <input
                                                                            type="range"
                                                                            min="12"
                                                                            max="48"
                                                                            step="1"
                                                                            value="18"
                                                                            data-text-font-size
                                                                            aria-label="Text font size">
                                                                        <output data-text-font-size-value>18px</output>
                                                                    </div>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="drawing-toolbar-divider" aria-hidden="true"></div>
                                                    <div class="drawing-toolbar-actions">
                                                        <div class="drawing-tool drawing-tool--action drawing-tool--undo">
                                                            <button
                                                                type="button"
                                                                class="drawing-tool-btn drawing-tool-btn--ghost"
                                                                data-drawing-tool="undo"
                                                                aria-label="Undo"
                                                                
                                                                data-tooltip="Undo"
                                                                disabled>
                                                                <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                                                            </button>
                                                        </div>
                                                        <div class="drawing-tool drawing-tool--action drawing-tool--redo">
                                                            <button
                                                                type="button"
                                                                class="drawing-tool-btn drawing-tool-btn--ghost"
                                                                data-drawing-tool="redo"
                                                                aria-label="Redo"
                                                              
                                                                data-tooltip="Redo"
                                                                disabled>
                                                                <i class="fa-solid fa-rotate-right" aria-hidden="true"></i>
                                                            </button>
                                                        </div>
                                                        <div class="drawing-tool drawing-tool--action drawing-tool--delete">
                                                            <button
                                                                type="button"
                                                                class="drawing-tool-btn drawing-tool-btn--ghost"
                                                                data-drawing-tool="delete"
                                                                aria-label="Delete"
                                                               
                                                                data-tooltip="Delete">
                                                                <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="video-transform-layer">
                                  <video
                                      id="deskVideoPlayer"
                                      class="video-player<?= $videoReady ? '' : ' d-none' ?>"
                                      preload="metadata"
                                      <?= $videoReady ? 'src="' . htmlspecialchars($videoSrc) . '"' : '' ?>>
                                  </video>
                                 <?php if ($ANNOTATIONS_ENABLED): ?>
                                     <div class="annotation-overlay" data-annotation-overlay>
                                         <canvas id="deskAnnotationCanvas" data-annotation-canvas></canvas>
                                     </div>
                                 <?php endif; ?>
                                    <div class="desk-video-feedback" data-video-play-overlay aria-hidden="true">
                                        <div class="desk-video-feedback-icon">
                                            <i class="fa-solid fa-play" aria-hidden="true"></i>
                                        </div>
                                    </div>
                                    <div class="desk-seek-feedback" data-video-seek-overlay aria-hidden="true">
                                        <span class="seek-feedback-icon seek-feedback-icon--rewind" aria-hidden="true">
                                            <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                                        </span>
                                        <span class="seek-feedback-icon seek-feedback-icon--forward" aria-hidden="true">
                                            <i class="fa-solid fa-rotate-right" aria-hidden="true"></i>
                                        </span>
                                    </div>
                              </div>
                              <div class="panoramic-shell" data-panoramic-shell>
                                  <video id="deskPanoramicVideo" playsinline muted data-panoramic-video></video>
                                  <canvas id="deskPanoramicCanvas" data-panoramic-canvas aria-label="Panoramic video viewport"></canvas>
                                  <div class="panoramic-message" data-panoramic-message>Preparing panoramic view…</div>
                                  <div class="panoramic-controls">
                                      <div class="panoramic-control-row">
                                          <div class="panoramic-control-group">
                                              <button type="button" class="control-btn" data-panoramic-play aria-label="Play/pause panoramic video">Play</button>
                                              <button type="button" class="control-btn" data-panoramic-fullscreen aria-label="Toggle fullscreen">Fullscreen</button>
                                          </div>
                                          <button type="button" class="control-btn control-btn--ghost" data-panoramic-close aria-label="Return to standard view">Return</button>
                                      </div>
                                      <div class="panoramic-control-row">
                                          <div class="panoramic-seek-track" data-panoramic-seek-track aria-label="Seek">
                                              <div class="panoramic-seek-progress" data-panoramic-seek-progress></div>
                                          </div>
                                          <div class="panoramic-time-label" data-panoramic-time>00:00 / 00:00</div>
                                      </div>
                                  </div>
                              </div>
                              <div class="custom-video-controls" id="deskControls">
                                  <div class="desk-timeline" id="deskTimeline" role="slider" tabindex="0" aria-label="Video timeline" data-video-timeline>
                                      <div class="desk-timeline-track" id="deskTimelineTrack" data-video-timeline-track>
                                          <div class="desk-timeline-markers" data-video-timeline-markers></div>
                                          <div class="desk-timeline-progress" id="deskTimelineProgress"></div>
                                          <div class="desk-timeline-playhead" data-video-timeline-playhead></div>
                                      </div>
                                  </div>
                                  <div class="desk-control-group">
                                      <div class="desk-time-display" id="deskTimeDisplay">00:00 / 00:00</div>
                                      <div class="desk-control-primary">
                                          <button id="deskPlayPause" class="control-btn" data-tooltip="Play/Pause" aria-label="Play or pause">
                                              <i class="fa-solid fa-play" aria-hidden="true"></i>
                                          </button>
                                          <button id="deskRewind" class="control-btn" data-tooltip="Back 5s" aria-label="Back 5 seconds">
                                              <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                                          </button>
                                          <button id="deskForward" class="control-btn" data-tooltip="Forward 5s" aria-label="Forward 5 seconds">
                                              <i class="fa-solid fa-rotate-right" aria-hidden="true"></i>
                                          </button>
                                      </div>
                                      <div class="desk-control-secondary">
                                          <div class="speed-selector">
                                              <button id="deskSpeedToggle" class="control-btn" aria-label="Playback speed">
                                                  <i class="fa-solid fa-gauge-simple" aria-hidden="true"></i>
                                                  <span class="speed-label">1×</span>
                                              </button>
                                              <ul id="deskSpeedOptions" class="speed-options" role="menu"></ul>
                                          </div>
                                          <button id="deskMuteToggle" class="control-btn" data-tooltip="Mute" aria-label="Toggle mute">
                                              <i class="fa-solid fa-volume-high" aria-hidden="true"></i>
                                          </button>
                                          <button id="deskFullscreen" class="control-btn" data-tooltip="Fullscreen" aria-label="Toggle fullscreen">
                                              <i class="fa-solid fa-expand" aria-hidden="true"></i>
                                          </button>
                            <?php
                            /*
                                          <button id="deskInteractiveToggle" class="control-btn" data-tooltip="Interactive mode" aria-label="Toggle interactive mode" aria-pressed="false">
                                              🎯
                                          </button>
                                          */
                                          ?>
                                      </div>
                                  </div>
                              </div>
                            </div>
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
                    <div class="panel-dark timeline-panel timeline-panel-full">
                        <div class="panel-row">
                            <div class="text-sm text-subtle">Timeline</div>
                            <div class="timeline-actions">
                                <button id="timelineDeleteAll" class="ghost-btn ghost-btn-sm desk-editable" type="button" data-tooltip="Delete all events" aria-label="Delete all events">
                                    <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                </button>
                                <div class="timeline-undo-redo">
                                    <button class="ghost-btn ghost-btn-sm desk-editable" id="eventUndoBtn" type="button" data-tooltip="Undo" disabled aria-label="Undo">
                                        <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                                    </button>
                                    <button class="ghost-btn ghost-btn-sm desk-editable" id="eventRedoBtn" type="button" data-tooltip="Redo" disabled aria-label="Redo">
                                        <i class="fa-solid fa-rotate-right" aria-hidden="true"></i>
                                    </button>
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
                            </div>
                        </div>
                        <div class="timeline-scroll">
                            <div class="timeline-view is-active" id="timelineList"></div>
                            <div class="timeline-view" id="timelineMatrix"></div>
                        </div>
                    </div>
                </div>
                    </section>
                </div>
            <aside class="desk-side">
                    <div class="desk-side-tabs" data-desk-side-tabs data-default-tab="quick-tags">
                    <div class="desk-side-tabs-bar" role="tablist">
                        <button type="button" class="desk-side-tab is-active" data-tab-button="quick-tags" role="tab" aria-selected="true">Quick Tags</button>
                        <button type="button" class="desk-side-tab" data-tab-button="playlists" role="tab" aria-selected="false">Playlists / Clips</button>
                        <?php if ($ANNOTATIONS_ENABLED): ?>
                            <button type="button" class="desk-side-tab" data-tab-button="drawings" role="tab" aria-selected="false">Drawings</button>
                        <?php endif; ?>
                    </div>
                    <div class="desk-side-tabs-content">
                        <div class="desk-side-tab-panel is-active" data-tab-panel="quick-tags" role="tabpanel">
                            <div class="desk-quick-tags">
                                <div class="panel-dark tagging-panel">
                                    <div class="panel-row">
                                        <div>
                                            <div class="text-sm text-subtle">Quick Tag</div>
                                            <div class="text-xs text-muted-alt">One click = one event</div>
                                        </div>
                                    </div>

                                    <div class="period-controls period-btn-row">
                                        <button class="ghost-btn ghost-btn-sm desk-editable period-btn period-start" id="btnPeriodFirstStart" type="button" data-period-key="first_half" data-period-action="start" data-period-label="First Half" data-period-event="period_start">▶ 1H</button>
                                        <button class="ghost-btn ghost-btn-sm desk-editable period-btn period-start" id="btnPeriodSecondStart" type="button" data-period-key="second_half" data-period-action="start" data-period-label="Second Half" data-period-event="period_start">▶ 2H</button>
                                        <button class="ghost-btn ghost-btn-sm desk-editable period-btn period-start" id="btnPeriodET1Start" type="button" data-period-key="extra_time_1" data-period-action="start" data-period-label="Extra Time 1" data-period-event="period_start">▶ ET1</button>

                                        <button class="ghost-btn ghost-btn-sm desk-editable period-btn period-end" id="btnPeriodFirstEnd" type="button" data-period-key="first_half" data-period-action="end" data-period-label="First Half" data-period-event="period_end">■ 1H</button>
                                        <button class="ghost-btn ghost-btn-sm desk-editable period-btn period-end" id="btnPeriodSecondEnd" type="button" data-period-key="second_half" data-period-action="end" data-period-label="Second Half" data-period-event="period_end">■ 2H</button>
                                        <button class="ghost-btn ghost-btn-sm desk-editable period-btn period-end" id="btnPeriodET1End" type="button" data-period-key="extra_time_1" data-period-action="end" data-period-label="Extra Time 1" data-period-event="period_end">■ ET1</button>

                                        <button class="ghost-btn ghost-btn-sm desk-editable period-btn period-start" id="btnPeriodET2Start" type="button" data-period-key="extra_time_2" data-period-action="start" data-period-label="Extra Time 2" data-period-event="period_start">▶ ET2</button>
                                        <button class="ghost-btn ghost-btn-sm desk-editable period-btn period-start" id="btnPeriodPenaltiesStart" type="button" data-period-key="penalties" data-period-action="start" data-period-label="Penalties" data-period-event="period_start">▶ P</button>

                                        <button class="ghost-btn ghost-btn-sm desk-editable period-btn" id="blank_space_button" type="button" disabled></button>

                                        <button class="ghost-btn ghost-btn-sm desk-editable period-btn period-end" id="btnPeriodET2End" type="button" data-period-key="extra_time_2" data-period-action="end" data-period-label="Extra Time 2" data-period-event="period_end">■ ET2</button>
                                        <button class="ghost-btn ghost-btn-sm desk-editable period-btn period-end" id="btnPeriodPenaltiesEnd" type="button" data-period-key="penalties" data-period-action="end" data-period-label="Penalties" data-period-event="period_end">■ P</button>
                                    </div>

                                    <div id="teamToggle" class="team-toggle">
                                        <button class="toggle-btn is-active" data-team="home">Home</button>
                                        <button class="toggle-btn" data-team="away">Away</button>
                                    </div>

                                    <div id="quickTagBoard" class="qt-board"></div>
                                    <div id="goalPlayerModal" class="goal-player-modal" role="dialog" aria-modal="true" aria-hidden="true" hidden>
                                        <div class="goal-player-modal-backdrop" data-goal-modal-close></div>
                                        <div class="panel-dark goal-player-modal-card">
                                            <div class="goal-player-modal-header">
                                                <div>
                                                    <div class="text-sm text-subtle">Goal scorer</div>
                                                    <div class="text-xs text-muted-alt">Pick a player to log the goal</div>
                                                </div>
                                                <div class="goal-player-modal-header-actions">
                                                    <button type="button" class="ghost-btn ghost-btn-sm" data-goal-unknown>Unknown player</button>
                                                    <button type="button" class="editor-modal-close" data-goal-modal-close aria-label="Close goal scorer modal">✕</button>
                                                </div>
                                            </div>
                                            <div id="goalPlayerList" class="goal-player-modal-list"></div>
                                        </div>
                                    </div>
                                    <div id="shotPlayerModal" class="goal-player-modal shot-player-modal" role="dialog" aria-modal="true" aria-hidden="true" hidden>
                                        <div class="goal-player-modal-backdrop" data-shot-modal-close></div>
                                        <div class="panel-dark goal-player-modal-card">
                                            <div class="goal-player-modal-header">
                                                <div>
                                                    <div class="text-sm text-subtle">Shot recorder</div>
                                                    <div class="text-xs text-muted-alt">Select the shooter and outcome</div>
                                                </div>
                                                <div class="goal-player-modal-header-actions">
                                                    <button type="button" class="ghost-btn ghost-btn-sm" data-shot-unknown>Unknown player</button>
                                                    <button type="button" class="editor-modal-close" data-shot-modal-close aria-label="Close shot modal">✕</button>
                                                </div>
                                            </div>
                                            <div class="shot-outcome-controls">
                                                <button type="button" class="ghost-btn shot-outcome-btn" data-shot-outcome="on_target">On Target</button>
                                                <button type="button" class="ghost-btn shot-outcome-btn" data-shot-outcome="off_target">Off Target</button>
                                            </div>
                                            <div id="shotPlayerList" class="goal-player-modal-list shot-player-modal-list"></div>
                                        </div>
                                    </div>
                                    <div id="cardPlayerModal" class="goal-player-modal card-player-modal" role="dialog" aria-modal="true" aria-hidden="true" hidden>
                                        <div class="goal-player-modal-backdrop" data-card-modal-close></div>
                                        <div class="panel-dark goal-player-modal-card">
                                            <div class="goal-player-modal-header">
                                                <div>
                                                    <div class="text-sm text-subtle">Card recipient</div>
                                                    <div class="text-xs text-muted-alt">Pick a player to log the card</div>
                                                </div>
                                                <div class="goal-player-modal-header-actions">
                                                    <button type="button" class="ghost-btn ghost-btn-sm" data-card-unknown>Unknown player</button>
                                                    <button type="button" class="editor-modal-close" data-card-modal-close aria-label="Close card modal">✕</button>
                                                </div>
                                            </div>
                                            <div id="cardPlayerList" class="goal-player-modal-list"></div>
                                        </div>
                                    </div>
                                    <div id="tagToast" class="desk-toast" style="display:none;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="desk-side-tab-panel" data-tab-panel="playlists" role="tabpanel">
                            <div class="desk-playlist">
                                <div id="playlistsPanel" class="panel-dark playlists-panel">
                                    <div class="playlist-panel-header">
                                        <div class="playlist-panel-heading">
                                            <div>
                                                <div class="text-sm text-subtle">Playlists</div>
                                                <div class="text-xs text-muted-alt">Curate clips for replay</div>
                                            </div>
                                        </div>
                                        <div class="playlist-panel-controls">
                                            <div class="playlist-control-buttons">
                                                <button id="playlistFilterBtn" type="button" class="ghost-btn ghost-btn-sm playlist-filter-btn" aria-label="Filter playlists" aria-expanded="false">
                                                    <i class="fa-solid fa-filter" aria-hidden="true"></i>
                                                </button>
                                                <button id="playlistSearchToggle" type="button" class="ghost-btn ghost-btn-sm playlist-toggle-btn" aria-label="Search playlists">
                                                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                                                </button>
                                                <button id="playlistCreateToggle" type="button" class="ghost-btn ghost-btn-sm playlist-toggle-btn" aria-label="Create playlist">
                                                    <i class="fa-solid fa-plus" aria-hidden="true"></i>
                                                </button>
                                            </div>
                                            <div id="playlistSearchRow" class="playlist-input-row">
                                                <div class="playlist-search-wrapper">
                                                    <input id="playlistSearchInput" class="input-dark playlist-search-input" type="text" placeholder="Search playlists…" autocomplete="off">
                                                    <span class="playlist-search-icon" aria-hidden="true"><i class="fa-solid fa-magnifying-glass"></i></span>
                                                </div>
                                            </div>
                                            <div id="playlistCreateRow" class="playlist-input-row">
                                                <form id="playlistCreateForm" class="playlist-create-form" autocomplete="off">
                                                    <input id="playlistTitleInput" class="input-dark playlist-title-input" type="text" name="title" placeholder="New playlist title" autocomplete="off" aria-label="New playlist title">
                                                    <button type="submit" class="ghost-btn ghost-btn-sm playlist-create-btn" aria-label="Create playlist">
                                                        <i class="fa-solid fa-plus" aria-hidden="true"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="playlistFilterPopover" class="playlist-filter-popover" hidden>
                                        <button type="button" class="playlist-filter-option" data-team="">All teams</button>
                                        <button type="button" class="playlist-filter-option" data-team="home">Home - <?= htmlspecialchars($match['home_team'] ?? 'Home') ?></button>
                                        <button type="button" class="playlist-filter-option" data-team="away">Away - <?= htmlspecialchars($match['away_team'] ?? 'Away') ?></button>
                                    </div>
                                    <div id="playlistList" class="playlist-list text-sm text-muted-alt">Loading playlists…</div>
                                    <div class="playlist-mode">
                                        <div class="playlist-mode-header">
                                            <div>
                                                <div class="text-xs text-muted-alt">Clips</div>
                                                <div id="playlistActiveTitle" class="text-sm text-subtle">click on playlist to show clips</div>
                                            </div>
                                            <button id="playlistAddClipBtn" type="button" class="ghost-btn ghost-btn-sm" disabled aria-label="Add clip">
                                                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                        <div class="playlist-controls">
                                            <button id="playlistPrevBtn" type="button" class="ghost-btn ghost-btn-sm" disabled>Previous clip</button>
                                            <button id="playlistNextBtn" type="button" class="ghost-btn ghost-btn-sm" disabled>Next clip</button>
                                        </div>
                                        <div id="playlistClips" class="playlist-clips text-sm text-muted-alt">No playlist selected.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php if ($ANNOTATIONS_ENABLED): ?>
                            <div class="desk-side-tab-panel" data-tab-panel="drawings" role="tabpanel">
                                <div class="drawings-playlist">
                                    <div class="drawings-playlist-header">
                                        <div>
                                            <div class="text-sm text-subtle">Drawings</div>
                                            <div class="text-xs text-muted-alt">Auto-collected sketches</div>
                                        </div>
                                        <span class="chip chip-muted">Auto</span>
                                    </div>
                                    <div id="drawingsPlaylistList" class="drawings-playlist-list text-sm text-muted-alt">No drawings yet.</div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </aside>
            </div>
        </div>
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
                            <button class="ghost-btn ghost-btn-sm desk-editable" id="eventNewBtn" type="button">Clear</button>
                        </div>
                        <button type="button" class="editor-modal-close" data-editor-close aria-label="Close event editor">✕</button>
                    </div>
                </div>
                <input type="hidden" id="eventId">
                <!-- match_second is the canonical source; minute is derived from it, minute_extra stores stoppage metadata -->
                <input type="hidden" id="match_second" value="0">
                <input type="hidden" id="minute" value="0">
                <input type="hidden" id="minute_extra" value="0">
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
                                <label class="field-label">Match time (MM:SS)</label>
                                <div class="d-flex gap-sm align-items-center">
                                    <button class="ghost-btn ghost-btn-sm desk-editable time-stepper" id="eventTimeStepDown" type="button" aria-label="Decrease time">−</button>
                                    <input type="text" class="input-dark desk-editable text-center" id="event_time_display" value="00:00" aria-label="Match time" placeholder="MM:SS">
                                    <button class="ghost-btn ghost-btn-sm desk-editable time-stepper" id="eventTimeStepUp" type="button" aria-label="Increase time">+</button>
                                </div>
                                <div class="text-xs text-muted-alt">Seconds must be 0–59; match_second stays canonical.</div>
                            </div>
                            <div>
                                <label class="field-label">+Extra minutes</label>
                                <input type="number" min="0" class="input-dark desk-editable" id="minute_extra_display" value="0" placeholder="0">
                                <div class="text-xs text-muted-alt">Additional stoppage minutes metadata.</div>
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
                                    <?php foreach ($periods ?? [] as $period): ?>
                                        <option
                                            value="<?= (int)$period['id'] ?>"
                                            data-start-second="<?= isset($period['start_second']) ? (int)$period['start_second'] : '' ?>"
                                            data-end-second="<?= isset($period['end_second']) ? (int)$period['end_second'] : '' ?>">
                                            <?= htmlspecialchars($period['label'] ?: ucfirst(str_replace('_', ' ', $period['period_key'] ?? 'Period'))) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="text-xs text-muted-alt" id="periodHelperText" aria-live="polite"></div>
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

<script>
    (function () {
        const tabsRoot = document.querySelector('[data-desk-side-tabs]');
        if (!tabsRoot) {
            return;
        }
        const buttons = Array.from(tabsRoot.querySelectorAll('[data-tab-button]'));
        const panels = Array.from(tabsRoot.querySelectorAll('[data-tab-panel]'));
        const activateTab = (key) => {
            buttons.forEach((button) => {
                const isActive = button.dataset.tabButton === key;
                button.classList.toggle('is-active', isActive);
                button.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });
            panels.forEach((panel) => {
                panel.classList.toggle('is-active', panel.dataset.tabPanel === key);
            });
        };
        const defaultTab = tabsRoot.dataset.defaultTab || 'playlists';
        activateTab(defaultTab);
        buttons.forEach((button) => {
            button.addEventListener('click', () => {
                activateTab(button.dataset.tabButton);
            });
        });
    })();
</script>
<div class="mt-3 text-muted-alt text-sm" id="deskStatus"></div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../layout.php';
?>
