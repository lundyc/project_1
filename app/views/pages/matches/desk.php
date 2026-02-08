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
require_once __DIR__ . '/../../../lib/match_stats_service.php';
require_once __DIR__ . '/../../../lib/StatsService.php';
require_once __DIR__ . '/../../../lib/csrf.php';
require_once __DIR__ . '/../../../lib/phase3.php';
require_once __DIR__ . '/../../../lib/asset_helper.php';

$user = current_user();
$roles = $_SESSION['roles'] ?? [];

if (!isset($match)) {
    http_response_code(404);
    echo 'Match not found';
    exit;
}

$base = base_path();
$cspNonce = function_exists('get_csp_nonce') ? get_csp_nonce() : '';
$nonceAttr = $cspNonce ? ' nonce="' . htmlspecialchars($cspNonce) . '"' : '';
$jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
$canView = can_view_match($user, $roles, (int)$match['club_id']);
$canEditRoles = in_array('platform_admin', $roles, true) || in_array('club_admin', $roles, true) || in_array('analyst', $roles, true);
$canManage = $canEditRoles && can_manage_match_for_club($user, $roles, (int)$match['club_id']);

if (!$canView && !$canManage) {
    http_response_code(403);
    echo '403 Forbidden';
    exit;
}

$currentLock = findLock((int)$match['id']);


// --- DATASET CLASSIFICATION & STRATEGY ---
// Events: Required for first paint. STRATEGY A (server owns initial data, embed as JSON)
// Event Types: Required for first paint. STRATEGY A
// Periods: Required for first paint. STRATEGY A
// Players: Required for first paint. STRATEGY A
// Tags: Required for first paint. STRATEGY A
// Derived Stats: Only for summary panel, can be deferred. STRATEGY A (defer fetch)
// Playlists, Annotations, Lock/Session: Not required for first paint. STRATEGY B (client fetches as needed)

$periods = get_match_periods((int)$match['id']); // [Required for first paint]
$matchPlayers = get_match_players((int)$match['id']); // [Required for first paint]
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

ensure_default_event_types((int)$match['club_id']); // [Required for event type integrity]

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


// Tags: Required for first paint. STRATEGY A
$tagsStmt = db()->prepare('SELECT id, label FROM tags WHERE club_id IS NULL OR club_id = :club_id ORDER BY label ASC');
$tagsStmt->execute(['club_id' => (int)$match['club_id']]);
$tags = $tagsStmt->fetchAll();

$matchId = (int)$match['id'];

// Events: Required for first paint. STRATEGY A
$events = event_list_for_match($matchId);

// Derived Stats: Only for summary panel, can be deferred. STRATEGY A (defer fetch)
$eventsVersion = count($events); // crude versioning by event count
$derivedStats = get_or_compute_match_stats($matchId, $eventsVersion, $events, $eventTypes);
$playerPerformance = ['starting_xi' => [], 'substitutes' => []];
try {
    $statsService = new StatsService((int)$match['club_id']);
    $playerPerformance = $statsService->getPlayerPerformanceForMatch($matchId);
} catch (Throwable $e) {
    $playerPerformance = ['starting_xi' => [], 'substitutes' => []];
}

$title = 'Analysis Desk';
// Filemtime-based versions keep URLs stable until the asset changes.
$headExtras = '<link href="' . htmlspecialchars($base) . '/assets/css/desk.css' . asset_version('/assets/css/desk.css') . '" rel="stylesheet">';
$headExtras .= '<link href="' . htmlspecialchars($base) . '/assets/css/toast.css' . asset_version('/assets/css/toast.css') . '" rel="stylesheet">';
$headExtras .= '<script' . $nonceAttr . '>window.ANNOTATIONS_ENABLED = true;</script>';
$videoLabEnabled = phase3_is_enabled();
$headExtras .= '<script' . $nonceAttr . '>window.VIDEO_LAB_ENABLED = ' . ($videoLabEnabled ? 'true' : 'false') . ';</script>';
$projectRoot = realpath(__DIR__ . '/../../../../');
$isVeo = (($match['video_source_type'] ?? '') === 'veo');
$showVideoProgressPanel = $videoLabEnabled && !empty($matchVideoRow);

// Check if match has video from match_videos table
$matchVideoStmt = $db->prepare('SELECT source_path, download_status FROM match_videos WHERE match_id = :match_id LIMIT 1');
$matchVideoStmt->execute(['match_id' => $matchId]);
$matchVideoRow = $matchVideoStmt->fetch();
$dbSourcePath = $matchVideoRow ? ($matchVideoRow['source_path'] ?? '') : '';
$dbDownloadStatus = $matchVideoRow ? ($matchVideoRow['download_status'] ?? '') : '';
$isVideoDownloadComplete = in_array($dbDownloadStatus, ['completed', 'complete', 'ready'], true);

// Use database path if available, otherwise fall back to conventional path
$standardRelative = $dbSourcePath ? '/videos/matches/' . ltrim($dbSourcePath, '/') : '';
$standardAbsolute = $projectRoot && $dbSourcePath
    ? $projectRoot . DIRECTORY_SEPARATOR . 'videos' . DIRECTORY_SEPARATOR . 'matches' . DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $dbSourcePath), DIRECTORY_SEPARATOR)
    : '';
$standardReady = $standardAbsolute && is_file($standardAbsolute) && $isVideoDownloadComplete;

// Panoramic not supported in new structure, set to empty
$panoramicRelative = '';
$panoramicAbsolute = '';
$panoramicReady = false;
$videoReady = false;
$videoPath = '';
$videoSrc = '';
$videoFormats = [];
$defaultFormatId = null;
$placeholderMessage = 'Video will appear once the download completes.';

if ($videoLabEnabled) {
    $videoReady = (bool)$standardReady;
    $videoPath = $videoReady ? $standardRelative : ($match['video_source_path'] ?? '');
    $videoSrc = $videoReady ? $standardRelative : '';

    $defaultFormatId = 'standard';
    $placeholderMessage = '';
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

// Poster images improve perceived load without changing playback behavior.
$thumbnailPath = trim((string)($match['video_thumbnail_path'] ?? ''));
$posterRelative = $thumbnailPath !== ''
    ? '/videos/matches/' . ltrim($thumbnailPath, '/')
    : '/videos/matches/thumbnail_' . $matchId . '.jpg';
$posterAbsolute = $projectRoot
    ? $projectRoot . DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $posterRelative), DIRECTORY_SEPARATOR)
    : '';
if (!$posterAbsolute || !is_file($posterAbsolute)) {
    $posterRelative = '/assets/img/logo.png';
}
$posterUrl = ($base ?: '') . $posterRelative;

$csrfToken = get_csrf_token();


// --- Embed all required datasets for first paint ---
$deskConfig = [
    'basePath' => $base,
    'matchId' => $matchId,
    'clubId' => (int)$match['club_id'],
    'userId' => (int)$user['id'],
    'userName' => $user['display_name'],
    'canEditRole' => $canManage,
    'eventTypes' => $eventTypes, // [Required for first paint]
    'tags' => $tags, // [Required for first paint]
    'players' => array_merge($homePlayers, $awayPlayers), // [Required for first paint]
    'homeTeamName' => $match['home_team'] ?? 'Home',
    'awayTeamName' => $match['away_team'] ?? 'Away',
    'outcomeOptions' => $outcomeOptions,
    'outcomeOptionsByTypeId' => $outcomeOptionsByTypeId,
    'periods' => $periods, // [Required for first paint]
    'events' => $events, // [Required for first paint]
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
        // Only endpoints for deferred/interactive fetches
        'lockAcquire' => $base . '/api/matches/' . (int)$match['id'] . '/lock/acquire',
        'lockHeartbeat' => $base . '/api/matches/' . (int)$match['id'] . '/lock/heartbeat',
        'lockRelease' => $base . '/api/matches/' . (int)$match['id'] . '/lock/release',
        'events' => $base . '/api/matches/' . (int)$match['id'] . '/events', // For deferred/interactive fetches only
        'eventCreate' => $base . '/api/matches/' . (int)$match['id'] . '/events/create',
        'eventUpdate' => $base . '/api/matches/' . (int)$match['id'] . '/events/update',
        'eventDelete' => $base . '/api/matches/' . (int)$match['id'] . '/events/delete',
        'undoEvent' => $base . '/api/events/undo',
        'redoEvent' => $base . '/api/events/redo',
        'periodsStart' => $base . '/api/matches/' . (int)$match['id'] . '/periods/start',
        'periodsEnd' => $base . '/api/matches/' . (int)$match['id'] . '/periods/end',
        'periodsSet' => $base . '/api/match-periods/set',
        'periodsList' => $base . '/api/matches/' . (int)$match['id'] . '/periods', // For deferred/interactive fetches only
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
        'matchPlayersList' => $base . '/api/match-players/list',
        'matchPlayersAdd' => $base . '/api/match-players/add',
        'matchPlayersUpdate' => $base . '/api/match-players/update',
        'matchPlayersDelete' => $base . '/api/match-players/delete',
        'playersList' => $base . '/api/players/list',
        'matchSubstitutionsList' => $base . '/api/match-substitutions/list',
        'matchSubstitutionsCreate' => $base . '/api/match-substitutions/create',
        'matchSubstitutionsDelete' => $base . '/api/match-substitutions/delete',
        'annotationsList' => $base . '/api/matches/' . (int)$match['id'] . '/annotations',
        'annotationsCreate' => $base . '/api/matches/' . (int)$match['id'] . '/annotations/create',
        'annotationsUpdate' => $base . '/api/matches/' . (int)$match['id'] . '/annotations/update',
        'annotationsDelete' => $base . '/api/matches/' . (int)$match['id'] . '/annotations/delete',
    ],
    'videoLabEnabled' => $videoLabEnabled,
];

$sessionBootstrap = [
    'matchId' => $matchId,
    'role' => 'analyst',
    'videoElementId' => 'deskVideoPlayer',
    'userId' => (int)$user['id'],
    'userName' => (string)$user['display_name'],
    'durationSeconds' => isset($deskConfig['video']['duration_seconds']) ? (float)$deskConfig['video']['duration_seconds'] : null,
    'ui' => [
        'statusElementId' => 'deskSessionStatus',
        'ownerElementId' => 'deskControlOwner',
        'takeControlButtonId' => 'deskTakeControl',
    ],
];

$footerScripts = '<script' . $nonceAttr . '>window.DeskConfig = ' . json_encode($deskConfig, $jsonFlags) . ';</script>';
$footerScripts .= '<script' . $nonceAttr . '>window.DeskSessionBootstrap = ' . json_encode($sessionBootstrap, $jsonFlags) . ';</script>';
// WebSocket disabled: $footerScripts .= '<script src="' . htmlspecialchars($base) . '/assets/js/vendor/socket.io.min.js' . asset_version('/assets/js/vendor/socket.io.min.js') . '"></script>';
$footerScripts .= '<script src="' . htmlspecialchars($base) . '/assets/js/desk-session.js' . asset_version('/assets/js/desk-session.js') . '"></script>';
$footerScripts .= '<script src="' . htmlspecialchars($base) . '/assets/js/toast.js' . asset_version('/assets/js/toast.js') . '"></script>';
$footerScripts .= '<script src="' . htmlspecialchars($base) . '/assets/js/desk-events.js' . asset_version('/assets/js/desk-events.js') . '"></script>';
$footerScripts .= '<script src="' . htmlspecialchars($base) . '/assets/js/desk-annotations.js' . asset_version('/assets/js/desk-annotations.js') . '"></script>';
if ($videoLabEnabled) {
    if ($showVideoProgressPanel) {
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
        $footerScripts .= '<script' . $nonceAttr . '>window.MatchVideoDeskConfig = ' . json_encode($videoProgressConfig, $jsonFlags) . ';</script>';
    }
    $footerScripts .= '<script src="' . htmlspecialchars($base) . '/assets/js/desk-video-controls.js' . asset_version('/assets/js/desk-video-controls.js') . '"></script>';
    $footerScripts .= '<script src="' . htmlspecialchars($base) . '/assets/js/desk-video-interactive.js' . asset_version('/assets/js/desk-video-interactive.js') . '"></script>';
    $footerScripts .= '<script src="' . htmlspecialchars($base) . '/assets/js/timeline-markers.js' . asset_version('/assets/js/timeline-markers.js') . '"></script>';
    $footerScripts .= '<script src="' . htmlspecialchars($base) . '/assets/js/panoramic-player.js' . asset_version('/assets/js/panoramic-player.js') . '"></script>';
    if ($showVideoProgressPanel) {
        $footerScripts .= '<script src="' . htmlspecialchars($base) . '/assets/js/match-video-progress.js' . asset_version('/assets/js/match-video-progress.js') . '"></script>';
    }
}

ob_start();
?>
<div id="deskRoot" data-base-path="<?= htmlspecialchars($base) ?>" data-match-id="<?= (int)$match['id'] ?>"></div>
<div id="deskError" class="desk-toast desk-toast-error" style="display:none;"></div>

<style>
    .desk-loading-skeleton { display: none; }
    .animate-pulse { animation: pulse 1.5s cubic-bezier(0.4,0,0.6,1) infinite; }
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.4; }
    }
    .video-transform-layer { position: relative; }
    .desk-corner-time-display {
        position: absolute;
        top: 12px;
        right: 18px;
        z-index: 100;
        font-size: 1.1em;
        background: rgba(0,0,0,0.55);
        color: #fff;
        padding: 2px 10px;
        border-radius: 6px;
        pointer-events: none;
        user-select: none;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    }
</style>
<div id="deskLoadingSkeleton" class="desk-loading-skeleton">
    <div class="mx-auto w-full max-w-2xl rounded-md border border-blue-300 p-4 mb-6">
        <div class="flex animate-pulse space-x-4">
            <div class="size-24 rounded bg-gray-200"></div>
            <div class="flex-1 space-y-6 py-1">
                <div class="h-4 rounded bg-gray-200 w-1/2"></div>
                <div class="space-y-3">
                    <div class="grid grid-cols-3 gap-4">
                        <div class="col-span-2 h-4 rounded bg-gray-200"></div>
                        <div class="col-span-1 h-4 rounded bg-gray-200"></div>
                    </div>
                    <div class="h-4 rounded bg-gray-200"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="mx-auto w-full max-w-3xl rounded-md border border-blue-300 p-4 mb-6">
        <div class="flex animate-pulse space-x-4">
            <div class="flex-1 space-y-4 py-1">
                <div class="h-4 rounded bg-gray-200 w-1/3"></div>
                <div class="h-4 rounded bg-gray-200 w-2/3"></div>
                <div class="h-4 rounded bg-gray-200 w-full"></div>
            </div>
        </div>
    </div>
    <div class="mx-auto w-full max-w-4xl rounded-md border border-blue-300 p-4">
        <div class="flex animate-pulse space-x-4">
            <div class="flex-1 space-y-4 py-1">
                <div class="h-4 rounded bg-gray-200 w-1/2"></div>
                <div class="h-4 rounded bg-gray-200 w-1/4"></div>
                <div class="h-4 rounded bg-gray-200 w-3/4"></div>
                <div class="h-4 rounded bg-gray-200 w-full"></div>
            </div>
        </div>
    </div>
</div>
<script nonce="<?= htmlspecialchars($cspNonce) ?>">
    document.addEventListener('DOMContentLoaded', function() {
        var skeleton = document.getElementById('deskLoadingSkeleton');
        var deskShell = document.querySelector('.desk-shell');
        if (skeleton && deskShell) {
            skeleton.style.display = '';
            deskShell.style.display = 'none';
            // Wait for main desk JS to signal ready (or fallback after 2.5s)
            window.deskHideSkeleton = function() {
                skeleton.style.display = 'none';
                deskShell.style.display = '';
            };
            setTimeout(window.deskHideSkeleton, 2500); // fallback timeout
        }
    });
</script>
<div class="desk-shell<?= $videoLabEnabled ? '' : ' desk-shell--video-disabled' ?>">
    <?php if (!$videoLabEnabled): ?>
        <style>
            .desk-shell--video-disabled .video-header,
            .desk-shell--video-disabled .desk-video,
            .desk-shell--video-disabled .panoramic-shell,
            .desk-shell--video-disabled .custom-video-controls,
            .desk-shell--video-disabled #deskVideoProgressPanel,
            .desk-shell--video-disabled .video-actions,
            .desk-shell--video-disabled .video-format-toggle,
            .desk-shell--video-disabled .playlist-panel-controls .playlist-control-buttons {
                display: none !important;
            }
            .desk-shell--video-disabled .desk-playlists-section {
                opacity: 0.6;
                pointer-events: none;
            }
        </style>
        <div class="alert alert-warning mb-3">
            <strong>Video Lab disabled</strong> – Video playback, clip review, and timeline scrubbing are hidden while the feature is offline. All match information and tagging tools remain available.
        </div>
    <?php endif; ?>

    <?php if ($videoLabEnabled && empty($match['video_source_path'])): ?>
        <div class="desk-alert">No video available for this match.</div>
    <?php endif; ?>

    <div class="desk-main">
        <div class="desk-layout">
            <div class="desk-top">
                <div class="panel-row video-header">
                    <div class="video-title">
                        <div class="text-xl fw-semibold"><?= htmlspecialchars($match['home_team']) ?> vs <?= htmlspecialchars($match['away_team']) ?></div>
                    </div>
                    <div class="video-actions">
                        <div class="video-actions-left">
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
                <div class="desk-left">
                    <section class="desk-video">
                        <div class="desk-video-shell">
                            <div class="video-panel">
                                <div class="video-content">
                                    <div class="video-frame">
                                        <div class="desk-scorebug" aria-label="Scoreboard">
                                            <div class="desk-scorebug-time" id="deskScorebugTime">0</div>
                                            <div class="desk-scorebug-row">
                                                <span class="desk-scorebug-team"><?= htmlspecialchars($match['home_team'] ?? 'Home') ?></span>
                                                <span class="desk-scorebug-score" id="deskScorebugHome"><?= (int)($match['home_score'] ?? 0) ?></span>
                                            </div>
                                            <div class="desk-scorebug-row">
                                                <span class="desk-scorebug-team"><?= htmlspecialchars($match['away_team'] ?? 'Away') ?></span>
                                                <span class="desk-scorebug-score" id="deskScorebugAway"><?= (int)($match['away_score'] ?? 0) ?></span>
                                            </div>
                                        </div>
                                        <div class="desk-drawing-toolbar toolbar-hidden" data-drawing-toolbar style="display:none;">
                                            <?php if ($ANNOTATIONS_ENABLED): ?>
                                                <div class="drawing-toolbar" data-annotation-toolbar>
                                                    <div class="drawing-toolbar-tools">
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
                                                                <button type="button" class="drawing-option-btn drawing-colour-swatch color-swatch" style="--swatch-color:var(--accent-yellow);background:var(--accent-yellow);" data-pencil-color="var(--accent-yellow)" aria-label="Yellow"></button>
                                                                <button type="button" class="drawing-option-btn drawing-colour-swatch color-swatch" style="--swatch-color:var(--accent-green);background:var(--accent-green);" data-pencil-color="var(--accent-green)" aria-label="Green"></button>
                                                                <button type="button" class="drawing-option-btn drawing-colour-swatch color-swatch" style="--swatch-color:var(--accent-sky);background:var(--accent-sky);" data-pencil-color="var(--accent-sky)" aria-label="Sky blue"></button>
                                                                <button type="button" class="drawing-option-btn drawing-colour-swatch color-swatch" style="--swatch-color:var(--accent-pink);background:var(--accent-pink);" data-pencil-color="var(--accent-pink)" aria-label="Pink"></button>
                                                                <button type="button" class="drawing-option-btn drawing-colour-swatch color-swatch" style="--swatch-color:var(--accent-purple);background:var(--accent-purple);" data-pencil-color="var(--accent-purple)" aria-label="Purple"></button>
                                                                <button type="button" class="drawing-option-btn drawing-colour-swatch color-swatch" style="--swatch-color:var(--accent-orange);background:var(--accent-orange);" data-pencil-color="var(--accent-orange)" aria-label="Orange"></button>
                                                                <button type="button" class="drawing-option-btn drawing-colour-swatch color-swatch" style="--swatch-color:var(--accent-danger);background:var(--accent-danger);" data-pencil-color="var(--accent-danger)" aria-label="Red"></button>
                                                                <button type="button" class="drawing-option-btn drawing-colour-swatch color-swatch" style="--swatch-color:var(--accent-blue);background:var(--accent-blue);" data-pencil-color="var(--accent-blue)" aria-label="Blue"></button>
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
                                      poster="<?= htmlspecialchars($posterUrl) ?>"
                                      preload="auto"
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
                                    <!-- Removed persistent time display in video corner -->
                              </div>
                              <div class="panoramic-shell" data-panoramic-shell>
                                  <video id="deskPanoramicVideo" playsinline muted poster="<?= htmlspecialchars($posterUrl) ?>" data-panoramic-video></video>
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
                              <!-- Move desk-time-display outside controls for persistent visibility -->
                              <div class="desk-time-display" id="deskTimeDisplay">
                                  <span class="desk-time-current">00:00</span>
                                  <span class="desk-time-total-block"> / 00:00</span>
                              </div>
                              <div class="custom-video-controls" id="deskControls">
                                  <div class="desk-timeline" id="deskTimeline" role="slider" tabindex="0" aria-label="Video timeline" data-video-timeline>
                                  <div class="desk-timeline-track" id="deskTimelineTrack" data-video-timeline-track>
                                      <div class="desk-timeline-markers" data-video-timeline-markers></div>
                                      <div class="desk-timeline-buffered" id="deskTimelineBuffered"></div>
                                      <div class="desk-timeline-progress" id="deskTimelineProgress"></div>
                                      <span class="desk-timeline-hover-ball" data-video-timeline-ball aria-hidden="true"></span>
                                      <div class="desk-timeline-playhead" data-video-timeline-playhead></div>
                                  </div>
                              </div>
                            <div class="desk-control-group desk-control-group--video">
                                <div class="desk-control-spacer"></div>

                                 <div class="desk-control-center">
                                        <span class="control-btn-shell">
                                            <button id="deskRewind" class="control-btn" aria-label="Back 5 seconds" aria-describedby="tooltip-deskRewind">
                                                <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                                            </button>
                                            <div id="tooltip-deskRewind" role="tooltip" class="video-control-tooltip">Back 5s</div>
                                        </span>
                                        <span class="control-btn-shell">
                                            <button id="deskPlayPause" class="control-btn" aria-label="Play or pause" aria-describedby="tooltip-deskPlayPause">
                                                <i class="fa-solid fa-play" aria-hidden="true"></i>
                                            </button>
                                            <div id="tooltip-deskPlayPause" role="tooltip" class="video-control-tooltip">Play/Pause</div>
                                        </span>
                                        <span class="control-btn-shell">
                                            <button id="deskForward" class="control-btn" aria-label="Forward 5 seconds" aria-describedby="tooltip-deskForward">
                                                <i class="fa-solid fa-rotate-right" aria-hidden="true"></i>
                                            </button>
                                            <div id="tooltip-deskForward" role="tooltip" class="video-control-tooltip">Forward 5s</div>
                                        </span>
                                    </div>
                                    <div class="desk-control-end">
                                        <span class="control-btn-shell" style="display:none;">
                                            <button id="drawingToolbarToggleBtn" class="control-btn" aria-label="Show/hide drawing toolbar" aria-pressed="false" type="button" aria-describedby="tooltip-drawingToolbarToggle">
                                                <i class="fa-solid fa-pen-ruler" aria-hidden="true"></i>
                                            </button>
                                            <div id="tooltip-drawingToolbarToggle" role="tooltip" class="video-control-tooltip">Show/hide drawing tools</div>
                                        </span>
                                        <div class="speed-selector">
                                            <button id="deskSpeedToggle" class="control-btn" aria-label="Playback speed">
                                                <i class="fa-solid fa-gauge-simple" aria-hidden="true"></i>
                                                <span class="speed-label">1×</span>
                                            </button>
                                            <ul id="deskSpeedOptions" class="speed-options" role="menu"></ul>
                                        </div>
                                        <span class="control-btn-shell">
                                            <button id="deskMuteToggle" class="control-btn" aria-label="Toggle mute" aria-describedby="tooltip-deskMuteToggle">
                                                <i class="fa-solid fa-volume-high" aria-hidden="true"></i>
                                            </button>
                                            <div id="tooltip-deskMuteToggle" role="tooltip" class="video-control-tooltip">Mute</div>
                                        </span>
                                        <span class="control-btn-shell">
                                            <button id="deskFullscreen" class="control-btn" aria-label="Toggle fullscreen" aria-describedby="tooltip-deskFullscreen">
                                                <i class="fa-solid fa-expand" aria-hidden="true"></i>
                                            </button>
                                            <div id="tooltip-deskFullscreen" role="tooltip" class="video-control-tooltip">Fullscreen</div>
                                        </span>
                                        <span class="control-btn-shell" style="display:none;">
                                            <button id="deskDetachVideo" class="control-btn" aria-label="Detach video" aria-describedby="tooltip-deskDetachVideo" type="button">
                                                <i class="fa-solid fa-up-right-from-square" aria-hidden="true"></i>
                                            </button>
                                            <div id="tooltip-deskDetachVideo" role="tooltip" class="video-control-tooltip">Detach video</div>
                                        </span>
                                    </div>
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
                        <div id="deskVideoPlaceholder" class="text-center text-muted mb-3<?= $videoReady ? ' d-none' : '' ?>">                        </div>
                        <?php if ($showVideoProgressPanel): ?>
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
                        <?php endif; ?>
                    </div>
                    <!-- timeline-panel moved below desk-video section -->
                </div>
                    </section>
                </div>
            <aside class="desk-side is-mode-active">
                <div class="desk-side-shell">
                    <div class="desk-live-tagging" data-desk-live-tagging aria-hidden="false">
                            <section class="desk-section desk-quick-tags-section" aria-label="Quick tags">
                                <div class="desk-quick-tags">
                                    <div class="panel-dark tagging-panel">

                                        <div class="period-controls period-controls-collapsed">
                                            <div style="display:flex;flex-direction:column;gap:6px; width:100%;">
                                                <button type="button"
                                                    class="ghost-btn ghost-btn-sm lineup-toggle-btn"
                                                    data-stats-modal-open
                                                    aria-haspopup="dialog"
                                                    aria-expanded="false"
                                                    style="width:100%;">Stats</button>
                                                <button type="button" 
                                                    class="ghost-btn ghost-btn-sm lineup-toggle-btn" 
                                                    data-lineup-modal-open
                                                    aria-haspopup="dialog"
                                                    aria-expanded="false"
                                                    aria-controls="lineupModal"
                                                    style="width:100%;">Lineup</button>
                                                <button
                                                    class="ghost-btn ghost-btn-sm lineup-toggle-btn desk-editable period-modal-toggle"
                                                    type="button"
                                                    aria-haspopup="dialog"
                                                    aria-expanded="false"
                                                    aria-controls="periodsModal"
                                                    aria-label="Open period controls" style="width:100%;">
                                                    Periods
                                                </button>
                                            </div>
                                        </div>

                                        <div class="desk-event-groups">
                                            <div class="desk-section-label">Event Groups</div>
                                            <div id="quickTagBoard" class="qt-board"></div>
                                        </div>

                                        <section class="desk-section desk-team-section" aria-label="Team selector" style="margin-top: 8px;">
                                            <div class="desk-section-label">Team</div>
                                            <div id="teamToggle" class="team-toggle desk-team-toggle">
                                                <button class="toggle-btn is-active" data-team="home">Home</button>
                                                <button class="toggle-btn" data-team="away">Away</button>
                                            </div>
                                        </section>

                                        <div id="periodsModal" class="periods-modal" role="dialog" aria-modal="true" aria-hidden="true" hidden>
                                            <div class="periods-modal-backdrop" data-period-modal-close></div>
                                            <div class="panel-dark periods-modal-card" role="document">
                                                <div class="periods-modal-header">
                                                    <div>
                                                        <div class="text-sm text-subtle">Period controls</div>
                                                        <div class="text-xs text-muted-alt">Manage match periods on demand</div>
                                                    </div>
                                                    <button type="button" class="editor-modal-close" data-period-modal-close aria-label="Close period controls">✕</button>
                                                </div>
                                                <div class="periods-modal-body">
                                                    <div id="currentPeriodStatus" class="period-status" aria-live="polite" role="status">
                                                        Current period: Not started
                                                    </div>
                                                    <div class="periods-modal-section">
                                                        <div class="periods-modal-section-header">First Half</div>
                                                        <div class="periods-modal-section-controls">
                                                            <button class="ghost-btn ghost-btn-sm desk-editable period-btn period-start" id="btnPeriodFirstStart" type="button" data-period-key="first_half" data-period-action="start" data-period-label="First Half" data-period-event="period_start">▶ Start First Half</button>
                                                            <button class="ghost-btn ghost-btn-sm desk-editable period-btn period-end" id="btnPeriodFirstEnd" type="button" data-period-key="first_half" data-period-action="end" data-period-label="First Half" data-period-event="period_end">■ End First Half</button>
                                                        </div>
                                                    </div>
                                                    <div class="periods-modal-section">
                                                        <div class="periods-modal-section-header">Second Half</div>
                                                        <div class="periods-modal-section-controls">
                                                            <button class="ghost-btn ghost-btn-sm desk-editable period-btn period-start" id="btnPeriodSecondStart" type="button" data-period-key="second_half" data-period-action="start" data-period-label="Second Half" data-period-event="period_start">▶ Start Second Half</button>
                                                            <button class="ghost-btn ghost-btn-sm desk-editable period-btn period-end" id="btnPeriodSecondEnd" type="button" data-period-key="second_half" data-period-action="end" data-period-label="Second Half" data-period-event="period_end">■ End Second Half</button>
                                                        </div>
                                                    </div>
                                                    <div class="periods-modal-section">
                                                        <div class="periods-modal-section-header">Extra Time</div>
                                                        <div class="periods-modal-period-grid">
                                                            <div class="periods-modal-period">
                                                                <div class="periods-modal-period-label">Extra Time 1</div>
                                                                <div class="periods-modal-section-controls">
                                                                    <button class="ghost-btn ghost-btn-sm desk-editable period-btn period-start" id="btnPeriodET1Start" type="button" data-period-key="extra_time_1" data-period-action="start" data-period-label="Extra Time 1" data-period-event="period_start">▶ Start Extra Time 1</button>
                                                                    <button class="ghost-btn ghost-btn-sm desk-editable period-btn period-end" id="btnPeriodET1End" type="button" data-period-key="extra_time_1" data-period-action="end" data-period-label="Extra Time 1" data-period-event="period_end">■ End Extra Time 1</button>
                                                                </div>
                                                            </div>
                                                            <div class="periods-modal-period">
                                                                <div class="periods-modal-period-label">Extra Time 2</div>
                                                                <div class="periods-modal-section-controls">
                                                                    <button class="ghost-btn ghost-btn-sm desk-editable period-btn period-start" id="btnPeriodET2Start" type="button" data-period-key="extra_time_2" data-period-action="start" data-period-label="Extra Time 2" data-period-event="period_start">▶ Start Extra Time 2</button>
                                                                    <button class="ghost-btn ghost-btn-sm desk-editable period-btn period-end" id="btnPeriodET2End" type="button" data-period-key="extra_time_2" data-period-action="end" data-period-label="Extra Time 2" data-period-event="period_end">■ End Extra Time 2</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="periods-modal-section">
                                                        <div class="periods-modal-section-header">Penalties</div>
                                                        <div class="periods-modal-section-controls">
                                                            <button class="ghost-btn ghost-btn-sm desk-editable period-btn period-start" id="btnPeriodPenaltiesStart" type="button" data-period-key="penalties" data-period-action="start" data-period-label="Penalties" data-period-event="period_start">▶ Start Penalties</button>
                                                            <button class="ghost-btn ghost-btn-sm desk-editable period-btn period-end" id="btnPeriodPenaltiesEnd" type="button" data-period-key="penalties" data-period-action="end" data-period-label="Penalties" data-period-event="period_end">■ End Penalties</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="statsModal" class="periods-modal stats-modal" role="dialog" aria-modal="true" aria-hidden="true" hidden>
                                            <div class="periods-modal-backdrop" data-stats-modal-close></div>
                                            <div class="panel-dark periods-modal-card stats-modal-card" role="document">
                                                <div class="periods-modal-header stats-modal-header">
                                                    <div class="stats-modal-header-left">
                                                        <div class="text-sm text-subtle">Match stats</div>
                                                        <div class="text-xs text-muted-alt">Live analytics context</div>
                                                    </div>
                                                    <div class="stats-modal-header-center">
                                                        <div class="stats-tabs stats-tabs--header" role="tablist" aria-label="Stats views">
                                                            <button type="button" class="editor-tab is-active" data-stats-tab="overview" aria-pressed="true">Overview</button>
                                                            <button type="button" class="editor-tab" data-stats-tab="comparison" aria-pressed="false">Comparisons</button>
                                                            <button type="button" class="editor-tab" data-stats-tab="players" aria-pressed="false">Players</button>
                                                        </div>
                                                    </div>
                                                    <div class="stats-modal-header-right">
                                                        <button type="button" class="editor-modal-close" data-stats-modal-close aria-label="Close match stats">✕</button>
                                                    </div>
                                                </div>
                                                <div class="periods-modal-body">
                                                    <div class="desk-summary-content">
                                                        <?php require __DIR__ . '/../../partials/match-summary-stats.php'; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php
                                        $homeStarters = array_values(array_filter($homePlayers ?? [], fn($p) => (int)($p['is_starting'] ?? 0) === 1));
                                        $homeBench = array_values(array_filter($homePlayers ?? [], fn($p) => (int)($p['is_starting'] ?? 0) !== 1));
                                        $awayStarters = array_values(array_filter($awayPlayers ?? [], fn($p) => (int)($p['is_starting'] ?? 0) === 1));
                                        $awayBench = array_values(array_filter($awayPlayers ?? [], fn($p) => (int)($p['is_starting'] ?? 0) !== 1));
                                        ?>
                                        <div id="lineupModal" class="periods-modal lineup-modal" role="dialog" aria-modal="true" aria-hidden="true" hidden>
                                            <div class="periods-modal-backdrop" data-lineup-modal-close></div>
                                            <div class="panel-dark periods-modal-card lineup-modal-card" role="document">
                                                <div class="periods-modal-header">
                                                    <div>
                                                        <div class="text-sm text-subtle">Lineups</div>
                                                        <div class="text-xs text-muted-alt">Starting XI and bench</div>
                                                    </div>
                                                    <button type="button" class="editor-modal-close" data-lineup-modal-close aria-label="Close lineups">✕</button>
                                                </div>
                                                <div class="periods-modal-body">
                                                    <div class="lineup-modal-error text-xs text-danger" data-lineup-error hidden></div>
                                                    <div class="grid grid-cols-2 gap-6">
                                                        <div class="space-y-4">
                                                            <div class="flex items-center justify-between">
                                                                <h3 class="text-base font-semibold text-white">
                                                                    <i class="fa-solid fa-house-chimney text-blue-400 mr-2"></i>
                                                                    <?= htmlspecialchars($match['home_team'] ?? 'Home') ?>
                                                                </h3>
                                                            </div>
                                                            <div class="space-y-3">
                                                                <div class="flex items-center justify-between">
                                                                    <h4 class="text-sm font-medium text-slate-300 uppercase tracking-wider">Starting XI</h4>
                                                                    <?php if ($canManage): ?>
                                                                    <button type="button" class="text-xs font-semibold text-blue-400 hover:text-blue-300 uppercase tracking-wider" data-lineup-add="home" data-lineup-starting="1">
                                                                        <i class="fa-solid fa-plus mr-1"></i> Add
                                                                    </button>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div class="lineup-table-wrapper">
                                                                    <table class="lineup-table">
                                                                        <colgroup>
                                                                            <col class="lineup-colgroup-number">
                                                                            <col class="lineup-colgroup-name">
                                                                            <col class="lineup-colgroup-actions">
                                                                        </colgroup>
                                                                        <thead>
                                                                            <tr>
                                                                                <th class="lineup-col-number">#</th>
                                                                                <th>Player</th>
                                                                                <th class="lineup-col-actions">Actions</th>
                                                                            </tr>
                                                                        </thead>
                                                                    </table>
                                                                    <div class="lineup-table-body lineup-table-body--starters">
                                                                        <table class="lineup-table">
                                                                            <colgroup>
                                                                                <col class="lineup-colgroup-number">
                                                                                <col class="lineup-colgroup-name">
                                                                                <col class="lineup-colgroup-actions">
                                                                            </colgroup>
                                                                            <tbody id="lineup-home-starters" data-lineup-list data-team="home" data-is-starting="1"></tbody>
                                                                        </table>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="space-y-3">
                                                                <div class="flex items-center justify-between">
                                                                    <h4 class="text-sm font-medium text-slate-300 uppercase tracking-wider">Substitutes</h4>
                                                                    <?php if ($canManage): ?>
                                                                    <button type="button" class="text-xs font-semibold text-blue-400 hover:text-blue-300 uppercase tracking-wider" data-lineup-add="home" data-lineup-starting="0">
                                                                        <i class="fa-solid fa-plus mr-1"></i> Add
                                                                    </button>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div class="lineup-table-wrapper">
                                                                    <table class="lineup-table">
                                                                        <colgroup>
                                                                            <col class="lineup-colgroup-number">
                                                                            <col class="lineup-colgroup-name">
                                                                            <col class="lineup-colgroup-actions">
                                                                        </colgroup>
                                                                        <thead>
                                                                            <tr>
                                                                                <th class="lineup-col-number">#</th>
                                                                                <th>Player</th>
                                                                                <th class="lineup-col-actions">Actions</th>
                                                                            </tr>
                                                                        </thead>
                                                                    </table>
                                                                    <div class="lineup-table-body lineup-table-body--subs">
                                                                        <table class="lineup-table">
                                                                            <colgroup>
                                                                                <col class="lineup-colgroup-number">
                                                                                <col class="lineup-colgroup-name">
                                                                                <col class="lineup-colgroup-actions">
                                                                            </colgroup>
                                                                            <tbody id="lineup-home-subs" data-lineup-list data-team="home" data-is-starting="0"></tbody>
                                                                        </table>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="space-y-4">
                                                            <div class="flex items-center justify-between">
                                                                <h3 class="text-base font-semibold text-white">
                                                                    <i class="fa-solid fa-plane-departure text-slate-400 mr-2"></i>
                                                                    <?= htmlspecialchars($match['away_team'] ?? 'Away') ?>
                                                                </h3>
                                                            </div>
                                                            <div class="space-y-3">
                                                                <div class="flex items-center justify-between">
                                                                    <h4 class="text-sm font-medium text-slate-300 uppercase tracking-wider">Starting XI</h4>
                                                                    <?php if ($canManage): ?>
                                                                    <button type="button" class="text-xs font-semibold text-blue-400 hover:text-blue-300 uppercase tracking-wider" data-lineup-add="away" data-lineup-starting="1">
                                                                        <i class="fa-solid fa-plus mr-1"></i> Add
                                                                    </button>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div class="lineup-table-wrapper">
                                                                    <table class="lineup-table">
                                                                        <colgroup>
                                                                            <col class="lineup-colgroup-number">
                                                                            <col class="lineup-colgroup-name">
                                                                            <col class="lineup-colgroup-actions">
                                                                        </colgroup>
                                                                        <thead>
                                                                            <tr>
                                                                                <th class="lineup-col-number">#</th>
                                                                                <th>Player</th>
                                                                                <th class="lineup-col-actions">Actions</th>
                                                                            </tr>
                                                                        </thead>
                                                                    </table>
                                                                    <div class="lineup-table-body lineup-table-body--starters">
                                                                        <table class="lineup-table">
                                                                            <colgroup>
                                                                                <col class="lineup-colgroup-number">
                                                                                <col class="lineup-colgroup-name">
                                                                                <col class="lineup-colgroup-actions">
                                                                            </colgroup>
                                                                            <tbody id="lineup-away-starters" data-lineup-list data-team="away" data-is-starting="1"></tbody>
                                                                        </table>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="space-y-3">
                                                                <div class="flex items-center justify-between">
                                                                    <h4 class="text-sm font-medium text-slate-300 uppercase tracking-wider">Substitutes</h4>
                                                                    <?php if ($canManage): ?>
                                                                    <button type="button" class="text-xs font-semibold text-blue-400 hover:text-blue-300 uppercase tracking-wider" data-lineup-add="away" data-lineup-starting="0">
                                                                        <i class="fa-solid fa-plus mr-1"></i> Add
                                                                    </button>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div class="lineup-table-wrapper">
                                                                    <table class="lineup-table">
                                                                        <colgroup>
                                                                            <col class="lineup-colgroup-number">
                                                                            <col class="lineup-colgroup-name">
                                                                            <col class="lineup-colgroup-actions">
                                                                        </colgroup>
                                                                        <thead>
                                                                            <tr>
                                                                                <th class="lineup-col-number">#</th>
                                                                                <th>Player</th>
                                                                                <th class="lineup-col-actions">Actions</th>
                                                                            </tr>
                                                                        </thead>
                                                                    </table>
                                                                    <div class="lineup-table-body lineup-table-body--subs">
                                                                        <table class="lineup-table">
                                                                            <colgroup>
                                                                                <col class="lineup-colgroup-number">
                                                                                <col class="lineup-colgroup-name">
                                                                                <col class="lineup-colgroup-actions">
                                                                            </colgroup>
                                                                            <tbody id="lineup-away-subs" data-lineup-list data-team="away" data-is-starting="0"></tbody>
                                                                        </table>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="mt-6 border-t border-slate-800 pt-4">
                                                        <div class="mb-3 flex items-center justify-between">
                                                            <h3 class="text-lg font-semibold text-white">Substitutions</h3>
                                                            <?php if ($canManage): ?>
                                                            <button type="button" class="btn-primary text-sm" data-lineup-add-substitution>
                                                                <i class="fa-solid fa-repeat mr-2"></i>
                                                                Add Substitution
                                                            </button>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="grid grid-cols-2 gap-4">
                                                            <div>
                                                                <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-700">
                                                                    <h4 class="text-sm font-semibold text-blue-400">
                                                                        <i class="fa-solid fa-house-chimney mr-2"></i>
                                                                        <?= htmlspecialchars($match['home_team'] ?? 'Home') ?>
                                                                    </h4>
                                                                </div>
                                                                <div class="space-y-2" id="lineup-home-substitutions"></div>
                                                            </div>
                                                            <div>
                                                                <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-700">
                                                                    <h4 class="text-sm font-semibold text-slate-400">
                                                                        <i class="fa-solid fa-plane-departure mr-2"></i>
                                                                        <?= htmlspecialchars($match['away_team'] ?? 'Away') ?>
                                                                    </h4>
                                                                </div>
                                                                <div class="space-y-2" id="lineup-away-substitutions"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="lineupAddPlayerModal" class="modal" style="display:none;">
                                            <div class="modal-backdrop"></div>
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <div>
                                                            <h3 class="modal-title">Add player</h3>
                                                            <div class="text-xs text-slate-400" id="lineup-add-subtitle"></div>
                                                        </div>
                                                        <button type="button" class="modal-close" data-lineup-add-close>
                                                            <i class="fa-solid fa-times"></i>
                                                        </button>
                                                    </div>
                                                    <form id="lineupAddPlayerForm">
                                                        <input type="hidden" name="team_side" id="lineup-add-team-side">
                                                        <input type="hidden" name="is_starting" id="lineup-add-is-starting">
                                                        <div class="modal-body space-y-4">
                                                            <div>
                                                                <label class="block text-sm font-medium text-slate-300 mb-2">Player <span class="text-rose-400">*</span></label>
                                                                <select id="lineup-add-player" name="player_id" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white focus:border-blue-500 focus:outline-none"></select>
                                                            </div>
                                                            <div class="grid grid-cols-2 gap-4">
                                                                <div>
                                                                    <label class="block text-sm font-medium text-slate-300 mb-2">Shirt #</label>
                                                                    <input type="number" id="lineup-add-shirt" name="shirt_number" min="0" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white focus:border-blue-500 focus:outline-none">
                                                                </div>
                                                                <div>
                                                                    <label class="block text-sm font-medium text-slate-300 mb-2">Position</label>
                                                                    <input type="text" id="lineup-add-position" name="position_label" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white focus:border-blue-500 focus:outline-none">
                                                                </div>
                                                            </div>
                                                            <div class="flex items-center gap-2">
                                                                <input type="checkbox" id="lineup-add-captain" name="is_captain" value="1">
                                                                <label for="lineup-add-captain" class="text-sm text-slate-300">Captain</label>
                                                            </div>
                                                            <div id="lineup-add-error" class="hidden text-sm text-rose-400 p-3 bg-rose-900/20 rounded-lg border border-rose-700/50"></div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn-secondary" data-lineup-add-close>Cancel</button>
                                                            <div class="flex gap-2">
                                                                <button type="button" class="btn-primary" id="lineup-add-another-btn">
                                                                    <i class="fa-solid fa-redo mr-2"></i>
                                                                    Save &amp; Add Another
                                                                </button>
                                                                <button type="submit" class="btn-primary">Add Player</button>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="lineupSubstitutionModal" class="modal" style="display:none;">
                                            <div class="modal-backdrop"></div>
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h3 class="modal-title">Add Substitution</h3>
                                                        <button type="button" class="modal-close" data-lineup-sub-close>
                                                            <i class="fa-solid fa-times"></i>
                                                        </button>
                                                    </div>
                                                    <form id="lineupSubstitutionForm">
                                                        <div class="modal-body space-y-4">
                                                            <div>
                                                                <label class="block text-sm font-medium text-slate-300 mb-2">Team <span class="text-rose-400">*</span></label>
                                                                <div class="flex gap-2">
                                                                    <button type="button" class="team-toggle-btn" data-lineup-sub-team="home">
                                                                        <i class="fa-solid fa-house mr-2"></i>Home
                                                                    </button>
                                                                    <button type="button" class="team-toggle-btn" data-lineup-sub-team="away">
                                                                        <i class="fa-solid fa-arrow-right mr-2"></i>Away
                                                                    </button>
                                                                    <input type="hidden" name="team_side" id="lineup-sub-team-side" value="home">
                                                                </div>
                                                            </div>
                                                            <div class="grid grid-cols-2 gap-4">
                                                                <div>
                                                                    <label class="block text-sm font-medium text-slate-300 mb-2">Player ON <span class="text-rose-400">*</span></label>
                                                                    <select id="lineup-sub-player-on" name="player_on_match_player_id" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white focus:border-blue-500 focus:outline-none"></select>
                                                                </div>
                                                                <div>
                                                                    <label class="block text-sm font-medium text-slate-300 mb-2">Player OFF <span class="text-rose-400">*</span></label>
                                                                    <select id="lineup-sub-player-off" name="player_off_match_player_id" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white focus:border-blue-500 focus:outline-none"></select>
                                                                </div>
                                                            </div>
                                                            <div>
                                                                <label class="block text-sm font-medium text-slate-300 mb-2" for="lineup-sub-minute">Minute <span class="text-rose-400">*</span></label>
                                                                <input type="number" id="lineup-sub-minute" name="minute" required min="0" max="120" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white focus:border-blue-500 focus:outline-none">
                                                            </div>
                                                            <div>
                                                                <label class="block text-sm font-medium text-slate-300 mb-2">Reason (optional)</label>
                                                                <input type="hidden" id="lineup-sub-reason" name="reason" value="">
                                                                <div class="grid grid-cols-2 gap-2" id="lineup-sub-reason-buttons">
                                                                    <button type="button" class="reason-toggle-btn" data-reason="tactical">Tactical</button>
                                                                    <button type="button" class="reason-toggle-btn" data-reason="injury">Injury</button>
                                                                    <button type="button" class="reason-toggle-btn" data-reason="fitness">Fitness</button>
                                                                    <button type="button" class="reason-toggle-btn" data-reason="disciplinary">Disciplinary</button>
                                                                </div>
                                                            </div>
                                                            <div id="lineup-sub-error" class="hidden text-sm text-rose-400 p-3 bg-rose-900/20 rounded-lg border border-rose-700/50"></div>
                                                            <div id="lineup-sub-success" class="hidden text-sm text-green-400 p-3 bg-green-900/20 rounded-lg border border-green-700/50"></div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn-secondary" data-lineup-sub-close>Cancel</button>
                                                            <div class="flex gap-2">
                                                                <button type="button" class="btn-primary" id="lineup-sub-add-another-btn">
                                                                    <i class="fa-solid fa-redo mr-2"></i>
                                                                    Save &amp; Add Another
                                                                </button>
                                                                <button type="submit" class="btn-primary">
                                                                    <i class="fa-solid fa-repeat mr-2"></i>
                                                                    Add Substitution
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="goalPlayerModal" class="goal-player-modal" role="dialog" aria-modal="true" aria-hidden="true" hidden>
                                            <div class="goal-player-modal-backdrop" data-goal-modal-close></div>
                                            <div class="panel-dark goal-player-modal-card shot-modal-advanced" style="max-width:90vw;width:90vw;min-width:600px;">
                                                <div class="shot-modal-columns" style="display:flex;gap:2.5rem;align-items:flex-start;">
                                                    <!-- Left: Goal scorer selection -->
                                                    <div class="shot-modal-left" style="flex:1 1 320px;min-width:260px;">
                                                        <div style="display: flex; justify-content: flex-end; align-items: flex-start;">
                                                            <button type="button" class="editor-modal-close" data-goal-modal-close aria-label="Close goal scorer modal" style="font-size:1.5em;line-height:1;position:absolute;top:18px;right:30px;z-index:10;background:none;border:none;">✕</button>
                                                        </div>
                                                        <div class="goal-player-modal-header" style="margin-top:2.5em;">
                                                            <div>
                                                                <div class="text-sm text-subtle">Goal recorder</div>
                                                                <div class="text-xs text-muted-alt">Pick a player to log the goal</div>
                                                            </div>
                                                            <div class="goal-player-modal-header-actions">
                                                                <button type="button" class="ghost-btn ghost-btn-sm" data-goal-unknown>Unknown player</button>
                                                            </div>
                                                        </div>
                                                        <div id="goalPlayerList" class="goal-player-modal-list"></div>
                                                    </div>
                                                    <!-- Right: Goal context panel -->
                                                    <div class="shot-modal-right" style="flex:1 1 400px;min-width:320px;max-width:520px;">
                                                        <div class="shot-pitch-area" style="padding:1.2em 1em 1.5em 1em;border-radius:1em;box-shadow:0 2px 12px #0002;">
                                                            <div class="text-xs text-muted-alt mb-1" style="text-align:center;">Shot taken from</div>
                                                            <svg id="goalOriginSvg" data-shot-svg="origin" style="width:100%;height:180px;display:block;border-radius:0.2em;"></svg>
                                                            <div id="goalOriginClearWrap" data-shot-clear-wrap="origin" style="text-align:center;margin-top:0.3em;display:none;">
                                                                <button type="button" class="ghost-btn ghost-btn-xs" data-shot-clear="origin" id="goalOriginClearBtn">Clear origin</button>
                                                            </div>
                                                            <div class="text-xs text-muted-alt mt-3 mb-1" style="text-align:center;">Shot target</div>
                                                            <svg id="goalTargetSvg" data-shot-svg="target" style="width:100%;height:140px;display:block;border-radius:0.7em;"></svg>
                                                            <div id="goalTargetClearWrap" data-shot-clear-wrap="target" style="text-align:center;margin-top:0.3em;display:none;">
                                                                <button type="button" class="ghost-btn ghost-btn-xs" data-shot-clear="target" id="goalTargetClearBtn">Clear target</button>
                                                            </div>
                                                            <div id="goalShotInfo" class="goal-shot-info" style="margin:0.5em 0 0 0;display:none;"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="shotPlayerModal" class="goal-player-modal shot-player-modal" role="dialog" aria-modal="true" aria-hidden="true" hidden>
                                            <div class="goal-player-modal-backdrop" data-shot-modal-close></div>
                                            <div class="panel-dark goal-player-modal-card shot-modal-advanced" style="max-width:90vw;width:90vw;min-width:600px;">
                                                <div class="shot-modal-columns" style="display:flex;gap:2.5rem;align-items:flex-start;">
                                                    <!-- Left: Existing controls -->
                                                    <div class="shot-modal-left" style="flex:1 1 320px;min-width:260px;">
                                                        <div style="display: flex; justify-content: flex-end; align-items: flex-start;">
                                                            <button type="button" class="editor-modal-close" data-shot-modal-close aria-label="Close shot modal" style="font-size:1.5em;line-height:1;position:absolute;top:18px;right:30px;z-index:10;background:none;border:none;">✕</button>
                                                        </div>
                                                        <div class="goal-player-modal-header" style="margin-top:2.5em;">
                                                            <div>
                                                                <div class="text-sm text-subtle">Shot recorder</div>
                                                                <div class="text-xs text-muted-alt">Select the shooter and outcome</div>
                                                            </div>
                                                            <div class="goal-player-modal-header-actions">
                                                                <button type="button" class="ghost-btn ghost-btn-sm" data-shot-unknown>Unknown player</button>
                                                            </div>
                                                        </div>
                                                        <div class="shot-outcome-controls">
                                                            <button type="button" class="ghost-btn shot-outcome-btn" data-shot-outcome="on_target">On Target</button>
                                                            <button type="button" class="ghost-btn shot-outcome-btn" data-shot-outcome="off_target">Off Target</button>
                                                        </div>
                                                        <div id="shotPlayerList" class="goal-player-modal-list shot-player-modal-list"></div>
                                                    </div>
                                                    <!-- Right: SVG pitch selector -->
                                                    <div class="shot-modal-right" style="flex:1 1 400px;min-width:320px;max-width:520px;">
                                                        <div class="shot-pitch-area" style="padding:1.2em 1em 1.5em 1em;border-radius:1em;box-shadow:0 2px 12px #0002;">
                                                            <div class="text-xs text-muted-alt mb-1" style="text-align:center;">Shot taken from</div>
                                                            <svg id="shotOriginSvg" data-shot-svg="origin" style="width:100%;height:180px;display:block;border-radius:0.2em;"></svg>
                                                            <div id="shotOriginClearWrap" data-shot-clear-wrap="origin" style="text-align:center;margin-top:0.3em;display:none;">
                                                                <button type="button" class="ghost-btn ghost-btn-xs" data-shot-clear="origin" id="shotOriginClearBtn">Clear origin</button>
                                                            </div>
                                                            <div class="text-xs text-muted-alt mt-3 mb-1" style="text-align:center;">Shot target</div>
                                                            <svg id="shotTargetSvg" data-shot-svg="target" style="width:100%;height:140px;display:block;border-radius:0.7em;"></svg>
                                                            <div id="shotTargetClearWrap" data-shot-clear-wrap="target" style="text-align:center;margin-top:0.3em;display:none;">
                                                                <button type="button" class="ghost-btn ghost-btn-xs" data-shot-clear="target" id="shotTargetClearBtn">Clear target</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
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
                                        <div id="offsidePlayerModal" class="goal-player-modal offside-player-modal" role="dialog" aria-modal="true" aria-hidden="true" hidden>
                                            <div class="goal-player-modal-backdrop" data-offside-modal-close></div>
                                            <div class="panel-dark goal-player-modal-card">
                                                <div class="goal-player-modal-header">
                                                    <div>
                                                        <div class="text-sm text-subtle">Offside player</div>
                                                        <div class="text-xs text-muted-alt">Pick the player who was offside</div>
                                                    </div>
                                                    <div class="goal-player-modal-header-actions">
                                                        <button type="button" class="ghost-btn ghost-btn-sm" data-offside-unknown>Unknown player</button>
                                                        <button type="button" class="editor-modal-close" data-offside-modal-close aria-label="Close offside modal">✕</button>
                                                    </div>
                                                </div>
                                                <div id="offsidePlayerList" class="goal-player-modal-list"></div>
                                            </div>
                                        </div>
                                        <div id="tagToast" class="desk-toast" style="display:none;"></div>
                                    </div>
                                </div>
                            </section>
                        </div>
                        <div class="desk-mode-panels"></div>
                </div>
            </aside>
        </div>
            <div class="desk-timeline-row">
                <div class="panel-dark timeline-panel timeline-panel-full p-2">
                    <div class="panel-row">
                        <div class="text-sm text-subtle">Timeline</div>
                        <div class="timeline-actions">
                            <span class="control-btn-shell">
                                <button id="timelineDeleteAll" class="ghost-btn ghost-btn-sm desk-editable" type="button" aria-label="Delete all events" aria-describedby="tooltip-timelineDeleteAll">
                                    <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                </button>
                                <div id="tooltip-timelineDeleteAll" role="tooltip" class="video-control-tooltip">Delete all events</div>
                            </span>
                            <div class="timeline-undo-redo">
                                <span class="control-btn-shell">
                                    <button class="ghost-btn ghost-btn-sm desk-editable" id="eventUndoBtn" type="button" disabled aria-label="Undo" aria-describedby="tooltip-eventUndoBtn">
                                        <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                                    </button>
                                    <div id="tooltip-eventUndoBtn" role="tooltip" class="video-control-tooltip">Undo</div>
                                </span>
                                <span class="control-btn-shell">
                                    <button class="ghost-btn ghost-btn-sm desk-editable" id="eventRedoBtn" type="button" disabled aria-label="Redo" aria-describedby="tooltip-eventRedoBtn">
                                        <i class="fa-solid fa-rotate-right" aria-hidden="true"></i>
                                    </button>
                                    <div id="tooltip-eventRedoBtn" role="tooltip" class="video-control-tooltip">Redo</div>
                                </span>
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
                        <div class="timeline-view" id="timelineList"></div>
                        <div class="timeline-view is-active" id="timelineMatrix"></div>
                    </div>
                </div>
            </div>
            <div class="desk-playlist-row">
                <section class="desk-section desk-playlists-section" aria-label="Playlists">
                    <div id="playlistsPanel" class="panel-dark playlists-panel p-3">
                        <div class="playlist-panel-header">
                            <div class="playlist-panel-heading">
                                <div>
                                    <div class="text-sm text-subtle">Playlists</div>
                                    <div class="text-xs text-muted-alt">Curate clips for replay</div>
                                </div>
                            </div>
                            <div class="playlist-panel-controls">
                                <div class="playlist-control-buttons">
                                    <button id="playlistFilterBtn" type="button" class="ghost-btn ghost-btn-sm playlist-filter-btn" aria-label="Filter playlists" aria-expanded="false" style="display: none">
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
                        <div id="playlistFilterPopover" class="playlist-filter-popover" hidden style="display: none">
                            <button type="button" class="playlist-filter-option" data-team="">All teams</button>
                            <button type="button" class="playlist-filter-option" data-team="home">Home - <?= htmlspecialchars($match['home_team'] ?? 'Home') ?></button>
                            <button type="button" class="playlist-filter-option" data-team="away">Away - <?= htmlspecialchars($match['away_team'] ?? 'Away') ?></button>
                        </div>
                        <div id="playlistList" class="playlist-list text-sm text-muted-alt">Loading playlists…</div>
                    </div>
                </section>
                <section class="desk-section desk-clips-section" aria-label="Clips">
                    <div id="clipsPanel" class="panel-dark playlists-panel p-3 clips-panel">
                        <div class="playlist-mode">
                            <div class="playlist-mode-header">
                                <div>
                                    <div class="text-sm text-subtle">Clips</div>
                                    <div id="playlistActiveTitle" class="text-xs text-muted-alt">click on playlist to show clips</div>
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
                </section>
            </div>
                </div>
        </div>
        <!-- Drawing Toolbar Toggle JS -->
        <script src="<?= htmlspecialchars($base) ?>/assets/js/desk-toolbar-toggle.js<?= asset_version('/assets/js/desk-toolbar-toggle.js') ?>" defer></script>
</div>

        <div id="editorPanel" class="editor-modal is-hidden" role="dialog" aria-modal="true" aria-labelledby="editorTitle" inert>
            <div class="editor-modal-backdrop" data-editor-close></div>
            <div class="panel-dark editor-modal-card">
                <div class="editor-modal-header">
                    <div>
                        <div id="editorTitle" class="text-sm text-subtle">Event editor</div>
                        <div id="editorHint" class="text-xs text-muted-alt">Click a timeline item to edit details</div>
                    </div>
                                  <div class="editor-tabs" role="tablist">
                        <button id="editorTabDetails" class="editor-tab is-active" data-panel="details" role="tab" aria-controls="editorTabpanelDetails" aria-selected="true">Details</button>
                        <button id="editorTabNotes" class="editor-tab" data-panel="notes" role="tab" aria-controls="editorTabpanelNotes" aria-selected="false">Notes</button>
                        <button id="editorTabClip" class="editor-tab" data-panel="clip" role="tab" aria-controls="editorTabpanelClip" aria-selected="false">Clip</button>
                    </div>
                    <div class="editor-modal-header-actions">
                        <button type="button" class="editor-modal-close" data-editor-close aria-label="Close event editor">✕</button>
                    </div>
                </div>
                <input type="hidden" id="eventId">
                <!-- match_second is the canonical source of event timing in seconds -->
                <input type="hidden" id="match_second" value="0">
                <input type="hidden" class="desk-editable" id="team_side" value="home">

                <div class="editor-tab-panels">
                    <div id="editorTabpanelDetails" class="editor-tab-panel is-active" data-panel="details" role="tabpanel" aria-labelledby="editorTabDetails">
                            <div class="editor-modal-content">
                            <div class="editor-content-left">
                                <div class="editor-field-row">
                                    <div>
                                        <label class="field-label">Event type</label>
                                        <select class="input-dark desk-editable" id="event_type_id">
                                            <?php foreach ($eventTypes as $type): ?>
                                                <option value="<?= (int)$type['id'] ?>"><?= htmlspecialchars($type['label']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="field-label">Match time (MM:SS)</label>
                                        <div class="d-flex gap-sm align-items-center">
                                            <button class="ghost-btn ghost-btn-sm desk-editable time-stepper" id="eventTimeStepDown" type="button" aria-label="Decrease time">−</button>
                                            <input type="text" class="input-dark desk-editable text-center" id="event_time_display" value="00:00" aria-label="Match time" placeholder="MM:SS">
                                            <button class="ghost-btn ghost-btn-sm desk-editable time-stepper" id="eventTimeStepUp" type="button" aria-label="Increase time">+</button>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label class="field-label">Team</label>
                                    <div class="team-selector-buttons" id="teamSelectorButtons" data-field="team_side">
                                        <button type="button" class="team-selector-btn desk-editable" data-team="home" title="<?= htmlspecialchars($match['home_team'] ?? 'Home') ?>">
                                            <?= htmlspecialchars($match['home_team'] ?? 'Home') ?>
                                        </button>
                                        <button type="button" class="team-selector-btn desk-editable" data-team="away" title="<?= htmlspecialchars($match['away_team'] ?? 'Away') ?>">
                                            <?= htmlspecialchars($match['away_team'] ?? 'Away') ?>
                                        </button>
                                        <button type="button" class="team-selector-btn desk-editable" data-team="unknown" title="No team">
                                            No team
                                        </button>
                                    </div>
                                </div>

                                <div id="outcomeField" style="display:none;">
                                    <label class="field-label">Outcome</label>
                                    <div class="outcome-selector-buttons" id="outcomeButtonsContainer" data-field="outcome">
                                        <button type="button" class="outcome-selector-btn desk-editable" data-outcome="on_target" title="On Target">
                                            On Target
                                        </button>
                                        <button type="button" class="outcome-selector-btn desk-editable" data-outcome="off_target" title="Off Target">
                                            Off Target
                                        </button>
                                    </div>
                                    <input type="hidden" class="input-dark desk-editable" id="outcome" value="">
                                </div>

                                <div class="editor-field-row">
                                    <div>
                                        <label class="field-label">Zone</label>
                                        <input type="text" class="input-dark desk-editable" id="zone">
                                    </div>
                                    <div>
                                        <label class="field-label">Tags</label>
                                        <select multiple class="input-dark desk-editable" id="tag_ids">
                                            <?php foreach ($tags as $tag): ?>
                                                <option value="<?= (int)$tag['id'] ?>"><?= htmlspecialchars($tag['label']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="editor-content-middle" id="playerSelectorContainer" style="display:none;">
                                <label class="field-label">Starting XI</label>
                                <div id="playerSelectorStarting" class="player-selector-buttons"></div>
                                <div id="playerSelectorSubsContainer" style="display:none;">
                                    <label class="field-label">Subs</label>
                                    <div id="playerSelectorSubs" class="player-selector-buttons"></div>
                                    <input type="hidden" class="input-dark desk-editable" id="match_player_id" value="">
                                </div>
                            </div>

                            <div class="editor-content-right">
                                <div id="editorShotMap" class="editor-shotmap-card" hidden>
                                    <div class="editor-shotmap-grid">
                                        <div class="editor-shotmap-block">
                                            <div class="shot-pitch-area">
                                                <div class="editor-shotmap-label-row">
                                                    <div class="text-xs text-muted-alt">Shot taken from</div>
                                                    <div class="editor-shotmap-clear" data-shot-clear-wrap="origin" style="display:none;">
                                                        <button type="button" class="ghost-btn ghost-btn-xs" data-shot-clear="origin">Clear origin</button>
                                                    </div>
                                                </div>
                                                <svg id="editorShotOriginSvg" class="editor-shotmap-svg" data-shot-svg="origin"></svg>
                                            </div>
                                        </div>
                                        <div class="editor-shotmap-block">
                                            <div class="shot-pitch-area">
                                                <div class="editor-shotmap-label-row">
                                                    <div class="text-xs text-muted-alt">Shot target</div>
                                                    <div class="editor-shotmap-clear" data-shot-clear-wrap="target" style="display:none;">
                                                        <button type="button" class="ghost-btn ghost-btn-xs" data-shot-clear="target">Clear target</button>
                                                    </div>
                                                </div>
                                                <svg id="editorShotTargetSvg" class="editor-shotmap-svg" data-shot-svg="target"></svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Hidden fields for Importance and Phase - not user-editable but retained for API -->
                            <input type="hidden" class="input-dark desk-editable" id="importance" value="3">
                            <input type="hidden" class="input-dark desk-editable" id="phase" value="unknown">
                        </div>
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
                        <button class="danger-btn desk-editable" id="eventDeleteBtn" type="button">Delete</button>
                    </div>
                    <button class="primary-btn desk-editable" id="eventSaveBtn" type="button">Save</button>
                </div>
        </div>
    </div>


<script nonce="<?= htmlspecialchars($cspNonce) ?>">
    (function () {
        const openBtn = document.querySelector('[data-stats-modal-open]');
        const modal = document.getElementById('statsModal');
        if (!openBtn || !modal) {
            return;
        }
        const closeButtons = Array.from(modal.querySelectorAll('[data-stats-modal-close]'));
        const tabButtons = Array.from(modal.querySelectorAll('[data-stats-tab]'));
        const statSections = Array.from(modal.querySelectorAll('[data-stats-section]'));
        const setActiveTab = (tab) => {
            tabButtons.forEach((button) => {
                const isActive = button.dataset.statsTab === tab;
                button.classList.toggle('is-active', isActive);
                button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });
            statSections.forEach((section) => {
                const isActive = section.dataset.statsSection === tab;
                section.classList.toggle('is-active', isActive);
            });
        };
        const openModal = () => {
            modal.hidden = false;
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            openBtn.setAttribute('aria-expanded', 'true');
            if (tabButtons.length && !tabButtons.some((btn) => btn.classList.contains('is-active'))) {
                setActiveTab(tabButtons[0].dataset.statsTab);
            }
        };
        const closeModal = () => {
            modal.hidden = true;
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            openBtn.setAttribute('aria-expanded', 'false');
        };
        openBtn.addEventListener('click', openModal);
        tabButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const tab = button.dataset.statsTab;
                if (tab) {
                    setActiveTab(tab);
                }
            });
        });
        if (tabButtons.length) {
            setActiveTab(tabButtons[0].dataset.statsTab);
        }
        closeButtons.forEach((btn) => {
            btn.addEventListener('click', closeModal);
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !modal.hidden) {
                closeModal();
            }
        });
    })();
</script>
<script nonce="<?= htmlspecialchars($cspNonce) ?>">
    (function () {
        const openBtn = document.querySelector('[data-lineup-modal-open]');
        const modal = document.getElementById('lineupModal');
        if (!openBtn || !modal) {
            return;
        }
        const getConfig = () => window.DeskConfig || {};
        const getEndpoints = () => getConfig().endpoints || {};
        const canEdit = () => !!getConfig().canEditRole;
        const getMatchId = () => getConfig().matchId;
        const getClubId = () => getConfig().clubId;
        const getCsrfToken = () => getConfig().csrfToken || '';

        const closeButtons = Array.from(modal.querySelectorAll('[data-lineup-modal-close]'));
        const errorEl = modal.querySelector('[data-lineup-error]');
        const addButtons = Array.from(modal.querySelectorAll('[data-lineup-add]'));
        const startersHome = modal.querySelector('#lineup-home-starters');
        const subsHome = modal.querySelector('#lineup-home-subs');
        const startersAway = modal.querySelector('#lineup-away-starters');
        const subsAway = modal.querySelector('#lineup-away-subs');
        const subsHomeList = modal.querySelector('#lineup-home-substitutions');
        const subsAwayList = modal.querySelector('#lineup-away-substitutions');
        const addSubstitutionBtn = modal.querySelector('[data-lineup-add-substitution]');

        const addModal = document.getElementById('lineupAddPlayerModal');
        const addForm = document.getElementById('lineupAddPlayerForm');
        const addError = document.getElementById('lineup-add-error');
        const addAnotherBtn = document.getElementById('lineup-add-another-btn');
        const addTeamInput = document.getElementById('lineup-add-team-side');
        const addStartingInput = document.getElementById('lineup-add-is-starting');
        const addPlayerSelect = document.getElementById('lineup-add-player');
        const addShirtInput = document.getElementById('lineup-add-shirt');
        const addPositionInput = document.getElementById('lineup-add-position');
        const addCaptainInput = document.getElementById('lineup-add-captain');
        const addSubtitle = document.getElementById('lineup-add-subtitle');

        const subModal = document.getElementById('lineupSubstitutionModal');
        const subForm = document.getElementById('lineupSubstitutionForm');
        const subError = document.getElementById('lineup-sub-error');
        const subSuccess = document.getElementById('lineup-sub-success');
        const subAddAnotherBtn = document.getElementById('lineup-sub-add-another-btn');
        const subTeamInput = document.getElementById('lineup-sub-team-side');
        const subPlayerOn = document.getElementById('lineup-sub-player-on');
        const subPlayerOff = document.getElementById('lineup-sub-player-off');
        const subMinuteInput = document.getElementById('lineup-sub-minute');
        const subReasonInput = document.getElementById('lineup-sub-reason');
        const subReasonButtons = document.getElementById('lineup-sub-reason-buttons');

        let clubPlayers = [];
        let matchPlayers = [];
        let substitutions = [];
        let loading = false;
        let addAnotherMode = false;
        let subAddAnotherMode = false;

        const setError = (message) => {
            if (!errorEl) {
                return;
            }
            if (!message) {
                errorEl.hidden = true;
                errorEl.textContent = '';
                return;
            }
            errorEl.textContent = message;
            errorEl.hidden = false;
        };

        const setAddError = (message) => {
            if (!addError) {
                return;
            }
            if (!message) {
                addError.classList.add('hidden');
                addError.textContent = '';
                return;
            }
            addError.textContent = message;
            addError.classList.remove('hidden');
        };

        const setSubError = (message) => {
            if (!subError) {
                return;
            }
            if (!message) {
                subError.classList.add('hidden');
                subError.textContent = '';
                return;
            }
            subError.textContent = message;
            subError.classList.remove('hidden');
        };

        const setSubSuccess = (message) => {
            if (!subSuccess) {
                return;
            }
            if (!message) {
                subSuccess.classList.add('hidden');
                subSuccess.textContent = '';
                return;
            }
            subSuccess.textContent = message;
            subSuccess.classList.remove('hidden');
        };

        const fetchJson = (url, options = {}) => {
            const csrfToken = getCsrfToken();
            return fetch(url, {
                credentials: 'same-origin',
                ...options,
                headers: {
                    'Content-Type': 'application/json',
                    ...(csrfToken ? { 'X-CSRF-Token': csrfToken } : {}),
                    ...(options.headers || {}),
                },
            }).then(async (response) => {
                const data = await response.json().catch(() => ({}));
                return { ok: response.ok, data };
            });
        };

        const getClubPlayers = async () => {
            const endpoints = getEndpoints();
            const clubId = getClubId();
            if (!endpoints.playersList || !clubId) {
                return [];
            }
            const url = `${endpoints.playersList}?club_id=${encodeURIComponent(clubId)}`;
            const result = await fetchJson(url, { method: 'GET' });
            if (!result.ok || !result.data || result.data.ok === false) {
                throw new Error(result.data && result.data.error ? result.data.error : 'Unable to load players');
            }
            return Array.isArray(result.data.players) ? result.data.players : [];
        };

        const getMatchPlayers = async () => {
            const endpoints = getEndpoints();
            const matchId = getMatchId();
            if (!endpoints.matchPlayersList || !matchId) {
                return [];
            }
            const url = `${endpoints.matchPlayersList}?match_id=${encodeURIComponent(matchId)}`;
            const result = await fetchJson(url, { method: 'GET' });
            if (!result.ok || !result.data || result.data.ok === false) {
                throw new Error(result.data && result.data.error ? result.data.error : 'Unable to load lineup');
            }
            return Array.isArray(result.data.match_players) ? result.data.match_players : [];
        };

        const getSubstitutions = async () => {
            const endpoints = getEndpoints();
            const matchId = getMatchId();
            if (!endpoints.matchSubstitutionsList || !matchId) {
                return [];
            }
            const url = `${endpoints.matchSubstitutionsList}?match_id=${encodeURIComponent(matchId)}`;
            const result = await fetchJson(url, { method: 'GET' });
            if (!result.ok || !result.data || result.data.ok === false) {
                throw new Error(result.data && result.data.error ? result.data.error : 'Unable to load substitutions');
            }
            return Array.isArray(result.data.substitutions) ? result.data.substitutions : [];
        };

        const getPlayerLabel = (player) => {
            if (!player) return 'Unknown';
            const first = (player.first_name || '').toString().trim();
            const last = (player.last_name || '').toString().trim();
            const name = `${first} ${last}`.trim();
            return name || player.display_name || `Player ${player.id || ''}`.trim();
        };

        const buildLineupCard = (entry) => {
            const row = document.createElement('tr');
            row.className = 'lineup-table-row';
            row.dataset.matchPlayerId = entry.id;
            row.innerHTML = `
                <td class="lineup-col-number">${entry.shirt_number ?? '—'}</td>
                <td class="lineup-col-name">
                    <span class="lineup-name-wrap">
                        <span class="lineup-name-text">${entry.display_name || 'Unknown'}</span>
                        ${entry.is_captain ? '<span class="lineup-captain" title="Captain">⭐</span>' : ''}
                    </span>
                </td>
                <td class="lineup-col-actions">
                    <button type="button" class="lineup-delete-btn" data-delete-player="${entry.id}" aria-label="Remove player"><i class="fa-solid fa-trash"></i></button>
                </td>
            `;
            return row;
        };

        const sortLineupPlayers = (entries) => {
            return entries.slice().sort((a, b) => {
                const aShirt = Number(a.shirt_number || 0);
                const bShirt = Number(b.shirt_number || 0);
                if (aShirt > 0 && bShirt > 0) return aShirt - bShirt;
                if (aShirt > 0) return -1;
                if (bShirt > 0) return 1;
                return String(a.display_name || '').localeCompare(String(b.display_name || ''));
            });
        };

        const renderLineupList = (container, entries, emptyText) => {
            if (!container) {
                return;
            }
            container.innerHTML = '';
            if (!entries.length) {
                const empty = document.createElement('tr');
                empty.className = 'lineup-table-empty-row';
                empty.innerHTML = `<td colspan="3" class="lineup-table-empty">${emptyText}</td>`;
                container.appendChild(empty);
                return;
            }
            entries.forEach((entry) => container.appendChild(buildLineupCard(entry)));
        };

        const renderLineups = () => {
            const homeStarters = sortLineupPlayers(matchPlayers.filter((p) => p.team_side === 'home' && p.is_starting));
            const homeSubs = sortLineupPlayers(matchPlayers.filter((p) => p.team_side === 'home' && !p.is_starting));
            const awayStarters = sortLineupPlayers(matchPlayers.filter((p) => p.team_side === 'away' && p.is_starting));
            const awaySubs = sortLineupPlayers(matchPlayers.filter((p) => p.team_side === 'away' && !p.is_starting));

            renderLineupList(startersHome, homeStarters, 'No starting players added yet');
            renderLineupList(subsHome, homeSubs, 'No substitutes added yet');
            renderLineupList(startersAway, awayStarters, 'No starting players added yet');
            renderLineupList(subsAway, awaySubs, 'No substitutes added yet');
        };

        const renderSubstitutions = () => {
            const renderList = (container, list, isHome) => {
                if (!container) return;
                container.innerHTML = '';
                if (!list.length) {
                    const empty = document.createElement('div');
                    empty.className = 'text-center py-8 text-slate-500 text-sm';
                    empty.innerHTML = '<i class="fa-solid fa-repeat opacity-30 mb-2"></i><p>No substitutions</p>';
                    container.appendChild(empty);
                    return;
                }
                list.forEach((sub) => {
                    const minute = sub.minute ?? 0;
                    const offName = sub.player_off_name || 'Unknown';
                    const onName = sub.player_on_name || 'Unknown';
                    const offShirt = sub.player_off_shirt ?? '?';
                    const onShirt = sub.player_on_shirt ?? '?';
                    const reason = sub.reason || '';
                    const card = document.createElement('div');
                    card.className = 'rounded-lg bg-slate-800/40 border border-slate-700 p-3';
                    card.innerHTML = `
                        <div class="flex items-center gap-2">
                            <span class="text-lg">🔄</span>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs text-slate-400 mb-1">${minute}'</div>
                                <div class="text-xs space-y-0.5">
                                    <div class="text-slate-400"><i class="fa-solid fa-arrow-down mr-1"></i>OFF: #${offShirt} ${offName}</div>
                                    <div class="text-emerald-400"><i class="fa-solid fa-arrow-up mr-1"></i>ON: #${onShirt} ${onName}</div>
                                    ${reason ? `<div class="text-xs text-slate-500 mt-1">${reason.charAt(0).toUpperCase() + reason.slice(1)}</div>` : ''}
                                </div>
                            </div>
                            ${canEdit() ? `<button type="button" class="text-rose-400 hover:text-rose-300 text-sm" data-delete-substitution="${sub.id}"><i class="fa-solid fa-trash"></i></button>` : ''}
                        </div>
                    `;
                    container.appendChild(card);
                });
            };

            renderList(subsHomeList, substitutions.filter((s) => s.team_side === 'home'), true);
            renderList(subsAwayList, substitutions.filter((s) => s.team_side === 'away'), false);
        };

        const refreshAddPlayerSelect = (teamSide) => {
            if (!addPlayerSelect) {
                return;
            }
            addPlayerSelect.innerHTML = '';
            const taken = new Set(matchPlayers.map((p) => String(p.player_id)).filter(Boolean));
            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = 'Select player';
            addPlayerSelect.appendChild(placeholder);
            clubPlayers.forEach((player) => {
                const id = player && player.id ? String(player.id) : '';
                if (!id || taken.has(id)) {
                    return;
                }
                const option = document.createElement('option');
                option.value = id;
                option.textContent = getPlayerLabel(player);
                addPlayerSelect.appendChild(option);
            });
        };

        const suggestNextShirt = (teamSide) => {
            const entries = matchPlayers.filter((p) => p.team_side === teamSide);
            const max = entries.reduce((acc, p) => {
                const num = Number(p.shirt_number || 0);
                return Number.isFinite(num) && num > acc ? num : acc;
            }, 0);
            let next = max + 1;
            if (next === 13) next = 14;
            if (addShirtInput) {
                addShirtInput.value = next || '';
            }
        };

        const openAddPlayerModal = (teamSide, isStarting) => {
            if (!addModal || !addForm) {
                return;
            }
            addForm.reset();
            setAddError('');
            addTeamInput.value = teamSide;
            addStartingInput.value = isStarting ? '1' : '0';
            if (addSubtitle) {
                const teamLabel = teamSide === 'home' ? (getConfig().homeTeamName || 'Home') : (getConfig().awayTeamName || 'Away');
                addSubtitle.textContent = `${teamLabel} · ${isStarting ? 'Starting XI' : 'Substitute'}`;
            }
            refreshAddPlayerSelect(teamSide);
            suggestNextShirt(teamSide);
            if (addCaptainInput) addCaptainInput.checked = false;
            addModal.style.display = 'block';
        };

        const closeAddPlayerModal = () => {
            if (!addModal) return;
            addModal.style.display = 'none';
        };

        const openSubstitutionModal = () => {
            if (!subModal || !subForm) {
                return;
            }
            subForm.reset();
            setSubError('');
            setSubSuccess('');
            subTeamInput.value = 'home';
            subModal.querySelectorAll('[data-lineup-sub-team]').forEach((node) => node.classList.remove('active'));
            const homeBtn = subModal.querySelector('[data-lineup-sub-team="home"]');
            if (homeBtn) {
                homeBtn.classList.add('active');
            }
            if (subReasonButtons) {
                subReasonButtons.querySelectorAll('.reason-toggle-btn').forEach((node) => node.classList.remove('active'));
            }
            if (subReasonInput) {
                subReasonInput.value = '';
            }
            updateSubstitutionSelects('home');
            subModal.style.display = 'block';
        };

        const closeSubstitutionModal = () => {
            if (!subModal) return;
            subModal.style.display = 'none';
        };

        const updateSubstitutionSelects = (teamSide) => {
            if (!subPlayerOn || !subPlayerOff) {
                return;
            }
            const starters = matchPlayers.filter((p) => p.team_side === teamSide && p.is_starting);
            const bench = matchPlayers.filter((p) => p.team_side === teamSide && !p.is_starting);
            subPlayerOn.innerHTML = '<option value="">Select player</option>';
            subPlayerOff.innerHTML = '<option value="">Select player</option>';
            bench.forEach((p) => {
                const opt = document.createElement('option');
                opt.value = p.id;
                opt.textContent = `#${p.shirt_number || '—'} ${p.display_name || 'Unknown'}`;
                subPlayerOn.appendChild(opt);
            });
            starters.forEach((p) => {
                const opt = document.createElement('option');
                opt.value = p.id;
                opt.textContent = `#${p.shirt_number || '—'} ${p.display_name || 'Unknown'}`;
                subPlayerOff.appendChild(opt);
            });
        };

        const refreshAll = async () => {
            if (loading) return;
            loading = true;
            setError('');
            try {
                if (!clubPlayers.length) {
                    clubPlayers = await getClubPlayers();
                }
                matchPlayers = await getMatchPlayers();
                substitutions = await getSubstitutions();
                renderLineups();
                renderSubstitutions();
            } catch (error) {
                setError(error && error.message ? error.message : 'Unable to refresh lineup');
            } finally {
                loading = false;
            }
        };

        const openModal = () => {
            modal.hidden = false;
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            openBtn.setAttribute('aria-expanded', 'true');
            refreshAll();
        };

        const closeModal = () => {
            modal.hidden = true;
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            openBtn.setAttribute('aria-expanded', 'false');
        };

        const addMatchPlayer = async (payload) => {
            const endpoints = getEndpoints();
            if (!endpoints.matchPlayersAdd) {
                throw new Error('Missing lineup add endpoint');
            }
            const result = await fetchJson(endpoints.matchPlayersAdd, {
                method: 'POST',
                body: JSON.stringify(payload),
            });
            if (!result.ok || !result.data || result.data.ok === false) {
                throw new Error(result.data && result.data.error ? result.data.error : 'Unable to add player');
            }
        };

        const deleteMatchPlayer = async (matchPlayerId) => {
            const endpoints = getEndpoints();
            if (!endpoints.matchPlayersDelete) {
                throw new Error('Missing lineup delete endpoint');
            }
            const result = await fetchJson(endpoints.matchPlayersDelete, {
                method: 'POST',
                body: JSON.stringify({ match_player_id: matchPlayerId }),
            });
            if (!result.ok || !result.data || result.data.ok === false) {
                throw new Error(result.data && result.data.error ? result.data.error : 'Unable to remove player');
            }
        };

        const createSubstitution = async (payload) => {
            const endpoints = getEndpoints();
            if (!endpoints.matchSubstitutionsCreate) {
                throw new Error('Missing substitution create endpoint');
            }
            const result = await fetchJson(endpoints.matchSubstitutionsCreate, {
                method: 'POST',
                body: JSON.stringify(payload),
            });
            if (!result.ok || !result.data || result.data.ok === false) {
                throw new Error(result.data && result.data.error ? result.data.error : 'Unable to add substitution');
            }
            return result.data.substitution || null;
        };

        const deleteSubstitution = async (subId) => {
            const endpoints = getEndpoints();
            if (!endpoints.matchSubstitutionsDelete) {
                throw new Error('Missing substitution delete endpoint');
            }
            const result = await fetchJson(endpoints.matchSubstitutionsDelete, {
                method: 'POST',
                body: JSON.stringify({ match_id: getMatchId(), id: subId }),
            });
            if (!result.ok || !result.data || result.data.success === false) {
                throw new Error(result.data && result.data.error ? result.data.error : 'Unable to delete substitution');
            }
        };

        addButtons.forEach((btn) => {
            btn.addEventListener('click', () => {
                if (!canEdit()) return;
                const team = btn.dataset.lineupAdd || 'home';
                const isStarting = btn.dataset.lineupStarting === '1';
                openAddPlayerModal(team, isStarting);
            });
        });

        addModal?.querySelectorAll('[data-lineup-add-close]').forEach((btn) => {
            btn.addEventListener('click', closeAddPlayerModal);
        });
        addModal?.querySelector('.modal-backdrop')?.addEventListener('click', (event) => {
            if (event.target === addModal.querySelector('.modal-backdrop')) {
                closeAddPlayerModal();
            }
        });

        addAnotherBtn?.addEventListener('click', (event) => {
            event.preventDefault();
            addAnotherMode = true;
            addForm?.requestSubmit();
        });

        addForm?.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (loading) return;
            addAnotherMode = addAnotherMode || false;
            setAddError('');
            const playerId = addPlayerSelect ? Number(addPlayerSelect.value || 0) : 0;
            if (!playerId) {
                setAddError('Select a player to add.');
                addAnotherMode = false;
                return;
            }
            try {
                await addMatchPlayer({
                    match_id: getMatchId(),
                    team_side: addTeamInput.value || 'home',
                    player_id: playerId,
                    shirt_number: addShirtInput && addShirtInput.value ? addShirtInput.value.trim() : '',
                    position_label: addPositionInput && addPositionInput.value ? addPositionInput.value.trim() : '',
                    is_starting: addStartingInput.value === '1' ? 1 : 0,
                    is_captain: addCaptainInput && addCaptainInput.checked ? 1 : 0,
                });
                await refreshAll();
                if (addAnotherMode) {
                    addAnotherMode = false;
                    openAddPlayerModal(addTeamInput.value || 'home', addStartingInput.value === '1');
                    return;
                }
                closeAddPlayerModal();
            } catch (error) {
                setAddError(error && error.message ? error.message : 'Unable to add player');
            } finally {
                addAnotherMode = false;
            }
        });

        addSubstitutionBtn?.addEventListener('click', () => {
            if (!canEdit()) return;
            openSubstitutionModal();
        });

        subModal?.querySelectorAll('[data-lineup-sub-close]').forEach((btn) => {
            btn.addEventListener('click', closeSubstitutionModal);
        });
        subModal?.querySelector('.modal-backdrop')?.addEventListener('click', (event) => {
            if (event.target === subModal.querySelector('.modal-backdrop')) {
                closeSubstitutionModal();
            }
        });

        subModal?.querySelectorAll('[data-lineup-sub-team]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const team = btn.dataset.lineupSubTeam || 'home';
                subTeamInput.value = team;
                subModal.querySelectorAll('[data-lineup-sub-team]').forEach((node) => node.classList.remove('active'));
                btn.classList.add('active');
                updateSubstitutionSelects(team);
            });
        });

        subReasonButtons?.querySelectorAll('.reason-toggle-btn').forEach((btn) => {
            btn.addEventListener('click', () => {
                const reason = btn.dataset.reason || '';
                subReasonInput.value = reason;
                subReasonButtons.querySelectorAll('.reason-toggle-btn').forEach((node) => node.classList.remove('active'));
                btn.classList.add('active');
            });
        });

        subAddAnotherBtn?.addEventListener('click', (event) => {
            event.preventDefault();
            subAddAnotherMode = true;
            subForm?.requestSubmit();
        });

        subForm?.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (loading) return;
            setSubError('');
            setSubSuccess('');
            const payload = {
                match_id: getMatchId(),
                team_side: subTeamInput.value || 'home',
                minute: Number(subMinuteInput.value || 0),
                player_off_match_player_id: Number(subPlayerOff.value || 0),
                player_on_match_player_id: Number(subPlayerOn.value || 0),
                reason: subReasonInput.value || '',
            };
            if (!payload.player_off_match_player_id || !payload.player_on_match_player_id) {
                setSubError('Select players on and off.');
                subAddAnotherMode = false;
                return;
            }
            try {
                const created = await createSubstitution(payload);
                if (created) {
                    substitutions.push(created);
                } else {
                    substitutions = await getSubstitutions();
                }
                renderSubstitutions();
                if (subAddAnotherMode) {
                    setSubSuccess('Substitution saved. Ready for the next one.');
                    subForm.reset();
                    subTeamInput.value = payload.team_side;
                    updateSubstitutionSelects(payload.team_side);
                    subAddAnotherMode = false;
                    return;
                }
                closeSubstitutionModal();
            } catch (error) {
                setSubError(error && error.message ? error.message : 'Unable to add substitution');
            } finally {
                subAddAnotherMode = false;
            }
        });

        modal.addEventListener('click', async (event) => {
            const deletePlayerBtn = event.target.closest('[data-delete-player]');
            if (deletePlayerBtn && canEdit()) {
                if (!window.confirm('Remove this player from the lineup?')) {
                    return;
                }
                const id = Number(deletePlayerBtn.dataset.deletePlayer || 0);
                if (!id) return;
                try {
                    await deleteMatchPlayer(id);
                    await refreshAll();
                } catch (error) {
                    setError(error && error.message ? error.message : 'Unable to remove player');
                }
            }

            const deleteSubBtn = event.target.closest('[data-delete-substitution]');
            if (deleteSubBtn && canEdit()) {
                if (!window.confirm('Delete this substitution?')) {
                    return;
                }
                const subId = Number(deleteSubBtn.dataset.deleteSubstitution || 0);
                if (!subId) return;
                try {
                    await deleteSubstitution(subId);
                    substitutions = substitutions.filter((s) => Number(s.id) !== subId);
                    renderSubstitutions();
                } catch (error) {
                    setError(error && error.message ? error.message : 'Unable to delete substitution');
                }
            }
        });

        openBtn.addEventListener('click', openModal);
        closeButtons.forEach((btn) => btn.addEventListener('click', closeModal));
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !modal.hidden) {
                closeModal();
            }
        });
    })();
</script>

<script nonce="<?= htmlspecialchars($cspNonce) ?>">
// --- Shot Recorder SVG rendering (debug version, forced) ---
const SVG_NS = "http://www.w3.org/2000/svg";

window.debugShotSVG = function() {
    console.debug("[ShotSVG] debugShotSVG CALLED");
    const shotOriginSvg = document.getElementById("shotOriginSvg");
    const shotTargetSvg = document.getElementById("shotTargetSvg");
    const shotModal = document.getElementById("shotPlayerModal");
    
    if (shotOriginSvg) {
        shotOriginSvg.setAttribute("width", "400");
        shotOriginSvg.setAttribute("height", "180");
        renderShotOriginSvg(shotOriginSvg);
        attachSinglePointHandler(shotOriginSvg, "origin");
    }
    if (shotTargetSvg) {
        shotTargetSvg.setAttribute("width", "400");
        shotTargetSvg.setAttribute("height", "140");
        renderShotTargetSvg(shotTargetSvg);
        attachSinglePointHandler(shotTargetSvg, "target");
    }
};

// State variables for shot points
window.shotOriginPoint = null;
window.shotTargetPoint = null;
window.shotOriginCleared = false;
window.shotTargetCleared = false;

function getShotSvgs(type) {
    return Array.from(document.querySelectorAll(`[data-shot-svg="${type}"]`));
}

function getShotClearWraps(type) {
    return Array.from(document.querySelectorAll(`[data-shot-clear-wrap="${type}"]`));
}

function setShotClearVisible(type, visible) {
    const wraps = getShotClearWraps(type);
    if (!wraps.length) return;
    wraps.forEach((wrap) => {
        wrap.style.display = visible ? 'block' : 'none';
    });
}

function setShotPointState(type, point) {
    if (type === 'origin') {
        window.shotOriginPoint = point;
        window.shotOriginCleared = false;
    } else {
        window.shotTargetPoint = point;
        window.shotTargetCleared = false;
    }
}

function clearShotPointState(type) {
    if (type === 'origin') {
        window.shotOriginPoint = null;
        window.shotOriginCleared = true;
    } else {
        window.shotTargetPoint = null;
        window.shotTargetCleared = true;
    }
}

function renderShotPoint(type, point) {
    const svgs = getShotSvgs(type);
    if (!svgs.length) return;

    svgs.forEach((svg) => {
        const existing = svg.querySelector(`[data-shot-point="${type}"]`);
        if (!point) {
            if (existing) {
                existing.remove();
            }
            return;
        }

        const viewBox = svg.getAttribute('viewBox');
        if (!viewBox) return;
        const [vbX, vbY, vbWidth, vbHeight] = viewBox.split(' ').map(Number);
        const svgX = vbX + (point.x * vbWidth);
        const svgY = vbY + (point.y * vbHeight);

        let pointCircle = existing;
        if (!pointCircle) {
            pointCircle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            pointCircle.setAttribute('data-shot-point', type);
            pointCircle.setAttribute('r', '2.5');
            pointCircle.setAttribute('fill', '#ef4444');
            pointCircle.setAttribute('stroke', '#ffffff');
            pointCircle.setAttribute('stroke-width', '1');
            svg.appendChild(pointCircle);
        }

        pointCircle.setAttribute('cx', svgX);
        pointCircle.setAttribute('cy', svgY);
    });

    setShotClearVisible(type, Boolean(point));
}

window.renderShotPoint = renderShotPoint;
window.setShotPointState = setShotPointState;
window.clearShotPointState = clearShotPointState;

document.querySelectorAll('[data-shot-clear]').forEach((btn) => {
    btn.addEventListener('click', () => {
        const type = btn.getAttribute('data-shot-clear');
        if (type !== 'origin' && type !== 'target') {
            return;
        }
        clearShotPointState(type);
        renderShotPoint(type, null);
    });
});

/**
 * Attaches a single-point click handler to an SVG element
 * Normalises coordinates to 0-1 range and stores/updates state
 */
function attachSinglePointHandler(svg, type) {
    // Skip if already attached
    if (svg.dataset.shotPointHandlerAttached) {
        return;
    }
    svg.dataset.shotPointHandlerAttached = 'true';

    svg.addEventListener('click', (e) => {
        const viewBox = svg.getAttribute('viewBox');
        const [vbX, vbY, vbWidth, vbHeight] = viewBox.split(' ').map(Number);

        let svgX = 0;
        let svgY = 0;

        if (typeof svg.createSVGPoint === 'function' && svg.getScreenCTM()) {
            const point = svg.createSVGPoint();
            point.x = e.clientX;
            point.y = e.clientY;
            const transformed = point.matrixTransform(svg.getScreenCTM().inverse());
            svgX = transformed.x;
            svgY = transformed.y;
        } else {
            const rect = svg.getBoundingClientRect();
            const clickX = e.clientX;
            const clickY = e.clientY;
            const xNormFallback = Math.max(0, Math.min(1, (clickX - rect.left) / rect.width));
            const yNormFallback = Math.max(0, Math.min(1, (clickY - rect.top) / rect.height));
            svgX = vbX + (xNormFallback * vbWidth);
            svgY = vbY + (yNormFallback * vbHeight);
        }

        const xNorm = Math.max(0, Math.min(1, (svgX - vbX) / vbWidth));
        const yNorm = Math.max(0, Math.min(1, (svgY - vbY) / vbHeight));

        const point = { x: xNorm, y: yNorm };
        setShotPointState(type, point);
        renderShotPoint(type, point);

        if (type === 'origin') {
            console.log(`Shot origin updated:\nx: ${point.x.toFixed(2)}\ny: ${point.y.toFixed(2)}`);
        } else if (type === 'target') {
            console.log(`Shot target updated:\nx: ${point.x.toFixed(2)}\ny: ${point.y.toFixed(2)}`);
        }

        document.dispatchEvent(new CustomEvent('shot-point-updated', {
            detail: {
                type,
                point,
                source: 'click',
            },
        }));
    });
}

function renderShotOriginSvg(svg) {
    if (!svg) {
        return;
    }
    svg.innerHTML = "";
    svg.setAttribute("viewBox", "0 0 100 100");
    svg.setAttribute("preserveAspectRatio", "xMidYMid meet");

    // Outer pitch
    const pitch = document.createElementNS(SVG_NS, "rect");
    pitch.setAttribute("x", "5");
    pitch.setAttribute("y", "5");
    pitch.setAttribute("width", "90");
    pitch.setAttribute("height", "70");
    pitch.setAttribute("stroke", "#e5e7eb");
    pitch.setAttribute("stroke-width", "1");
    pitch.setAttribute("fill", "none");
    svg.appendChild(pitch);

    // Penalty box
    const penaltyBox = document.createElementNS(SVG_NS, "rect");
    penaltyBox.setAttribute("x", "20");
    penaltyBox.setAttribute("y", "5");
    penaltyBox.setAttribute("width", "60");
    penaltyBox.setAttribute("height", "30");
    penaltyBox.setAttribute("stroke", "#e5e7eb");
    penaltyBox.setAttribute("stroke-width", "1");
    penaltyBox.setAttribute("fill", "none");
    svg.appendChild(penaltyBox);

    // Six yard box
    const sixYardBox = document.createElementNS(SVG_NS, "rect");
    sixYardBox.setAttribute("x", "35");
    sixYardBox.setAttribute("y", "5");
    sixYardBox.setAttribute("width", "30");
    sixYardBox.setAttribute("height", "15");
    sixYardBox.setAttribute("stroke", "#e5e7eb");
    sixYardBox.setAttribute("stroke-width", "1");
    sixYardBox.setAttribute("fill", "none");
    svg.appendChild(sixYardBox);

    // Penalty spot
    const penaltySpot = document.createElementNS(SVG_NS, "circle");
    penaltySpot.setAttribute("cx", "50");
    penaltySpot.setAttribute("cy", "28");
    penaltySpot.setAttribute("r", "1.5");
    penaltySpot.setAttribute("fill", "#e5e7eb");
    svg.appendChild(penaltySpot);

    // Penalty arc
    const arc = document.createElementNS(SVG_NS, "path");
    arc.setAttribute("d", "M35 35 A15 15 0 0 0 65 35");
    arc.setAttribute("stroke", "#e5e7eb");
    arc.setAttribute("fill", "none");
    svg.appendChild(arc);
}

function renderShotTargetSvg(svg) {
    if (!svg) {
        return;
    }
    svg.innerHTML = "";
    svg.setAttribute("viewBox", "0 0 120 60");
    svg.setAttribute("preserveAspectRatio", "xMidYMid meet");

    // Left post
    const leftPost = document.createElementNS(SVG_NS, "line");
    leftPost.setAttribute("x1", "20");
    leftPost.setAttribute("y1", "10");
    leftPost.setAttribute("x2", "20");
    leftPost.setAttribute("y2", "50");
    leftPost.setAttribute("stroke", "#e5e7eb");
    leftPost.setAttribute("stroke-width", "3");
    svg.appendChild(leftPost);

    // Right post
    const rightPost = document.createElementNS(SVG_NS, "line");
    rightPost.setAttribute("x1", "100");
    rightPost.setAttribute("y1", "10");
    rightPost.setAttribute("x2", "100");
    rightPost.setAttribute("y2", "50");
    rightPost.setAttribute("stroke", "#e5e7eb");
    rightPost.setAttribute("stroke-width", "3");
    svg.appendChild(rightPost);

    // Crossbar
    const crossbar = document.createElementNS(SVG_NS, "line");
    crossbar.setAttribute("x1", "20");
    crossbar.setAttribute("y1", "10");
    crossbar.setAttribute("x2", "100");
    crossbar.setAttribute("y2", "10");
    crossbar.setAttribute("stroke", "#e5e7eb");
    crossbar.setAttribute("stroke-width", "3");
    svg.appendChild(crossbar);

    // Net grid - vertical lines
    for (let x = 30; x <= 90; x += 10) {
        const vLine = document.createElementNS(SVG_NS, "line");
        vLine.setAttribute("x1", x);
        vLine.setAttribute("y1", "20");
        vLine.setAttribute("x2", x);
        vLine.setAttribute("y2", "50");
        vLine.setAttribute("stroke", "#9ca3af");
        vLine.setAttribute("stroke-width", "1");
        svg.appendChild(vLine);
    }
    // Net grid - horizontal lines
    for (let y = 20; y <= 50; y += 10) {
        const hLine = document.createElementNS(SVG_NS, "line");
        hLine.setAttribute("x1", "30");
        hLine.setAttribute("y1", y);
        hLine.setAttribute("x2", "90");
        hLine.setAttribute("y2", y);
        hLine.setAttribute("stroke", "#9ca3af");
        hLine.setAttribute("stroke-width", "1");
        svg.appendChild(hLine);
    }
}

// Modal integration: always render SVGs after modal is visible
window.openShotModal = function(...args) {
    const modal = document.getElementById("shotPlayerModal");
    if (!modal) {
        return;
    }
    // If already visible, render immediately
    if (!modal.hidden && getComputedStyle(modal).display !== 'none') {
        window.debugShotSVG();
        return;
    }
    // Otherwise, observe for it becoming visible
    const observer = new MutationObserver(() => {
        if (!modal.hidden && getComputedStyle(modal).display !== 'none') {
            observer.disconnect();
            window.debugShotSVG();
        }
    });
    observer.observe(modal, { attributes: true, attributeFilter: ['hidden', 'style', 'class'] });
    // Fallback: also try after a short delay in case of animation
    setTimeout(() => {
        if (!modal.hidden && getComputedStyle(modal).display !== 'none') {
            observer.disconnect();
            window.debugShotSVG();
        }
    }, 350);
};
</script>
<div class="mt-3 text-muted-alt text-sm" id="deskStatus"></div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../layout.php';
?>
