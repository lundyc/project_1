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

$title = 'Analysis Desk';
// Filemtime-based versions keep URLs stable until the asset changes.
$headExtras = '<link href="' . htmlspecialchars($base) . '/assets/css/desk.css' . asset_version('/assets/css/desk.css') . '" rel="stylesheet">';
$headExtras .= '<link href="' . htmlspecialchars($base) . '/assets/css/toast.css' . asset_version('/assets/css/toast.css') . '" rel="stylesheet">';
$headExtras .= '<script>window.ANNOTATIONS_ENABLED = true;</script>';
$videoLabEnabled = phase3_is_enabled();
$headExtras .= '<script>window.VIDEO_LAB_ENABLED = ' . ($videoLabEnabled ? 'true' : 'false') . ';</script>';
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
    $placeholderMessage = 'This match video is downloading; it will appear once ready.';
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
        'session' => $base . '/api/matches/' . (int)$match['id'] . '/session',
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
    'sessionEndpoint' => $base . '/api/matches/' . $matchId . '/session',
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

$footerScripts = '<script>window.DeskConfig = ' . json_encode($deskConfig) . ';</script>';
$footerScripts .= '<script>window.DeskSessionBootstrap = ' . json_encode($sessionBootstrap) . ';</script>';
$footerScripts .= '<script src="' . htmlspecialchars($base) . '/assets/js/vendor/socket.io.min.js' . asset_version('/assets/js/vendor/socket.io.min.js') . '"></script>';
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
        $footerScripts .= '<script>window.MatchVideoDeskConfig = ' . json_encode($videoProgressConfig) . ';</script>';
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
<script>
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
<div class="desk-shell<?= $videoLabEnabled ? '' : ' desk-shell--video-disabled' ?>" style="">
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
                <div class="desk-left">
                    <div class="panel-row video-header">
                        <div class="video-title">
                            <div class="text-xl fw-semibold"><?= htmlspecialchars($match['home_team']) ?> vs <?= htmlspecialchars($match['away_team']) ?></div>
                        </div>
                        <div class="video-actions">
                                <div class="video-actions-left">
                                    <a class="ghost-btn ghost-btn-sm stats-btn" href="<?= htmlspecialchars($base) ?>/stats/match/<?= $matchId ?>">Stats</a>
                                    <button type="button" class="ghost-btn ghost-btn-sm lineup-toggle-btn" data-desk-lineup-button data-match-id="<?= $matchId ?>" aria-pressed="false">Lineup</button>
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
                            <div class="video-panel">
                                <div class="video-content">
                                    <div class="video-frame">
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
                              <div class="desk-control-group" style="display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; width: 100%; gap: 12px;">
                                  <div style="justify-self: start;">
                                          <div class="desk-time-display" id="deskTimeDisplay">
                                              <span class="desk-time-current">00:00</span>
                                              <span class="desk-time-total-block"> / 00:00</span>
                                          </div>
                                  </div>
                                      <div style="justify-self: center; display: flex; gap: 12px;">
                                          <span class="control-btn-shell">
                                              <button id="deskPlayPause" class="control-btn" aria-label="Play or pause" aria-describedby="tooltip-deskPlayPause">
                                                  <i class="fa-solid fa-play" aria-hidden="true"></i>
                                              </button>
                                              <div id="tooltip-deskPlayPause" role="tooltip" class="video-control-tooltip">Play/Pause</div>
                                          </span>
                                          <span class="control-btn-shell">
                                              <button id="deskRewind" class="control-btn" aria-label="Back 5 seconds" aria-describedby="tooltip-deskRewind">
                                                  <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                                              </button>
                                              <div id="tooltip-deskRewind" role="tooltip" class="video-control-tooltip">Back 5s</div>
                                          </span>
                                          <span class="control-btn-shell">
                                              <button id="deskForward" class="control-btn" aria-label="Forward 5 seconds" aria-describedby="tooltip-deskForward">
                                                  <i class="fa-solid fa-rotate-right" aria-hidden="true"></i>
                                              </button>
                                              <div id="tooltip-deskForward" role="tooltip" class="video-control-tooltip">Forward 5s</div>
                                          </span>
                                      </div>
                                      <div style="justify-self: end; display: flex; gap: 12px;">
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
                        <div id="deskVideoPlaceholder" class="text-center text-muted mb-3<?= $videoReady ? ' d-none' : '' ?>">
                            <?= htmlspecialchars($placeholderMessage) ?>
                        </div>
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
            <aside class="desk-side is-mode-active">
                <div class="desk-side-shell">
                    <div class="desk-mode-bar" data-desk-side-modes data-default-mode="tag-live" role="tablist">
                        <button type="button" class="desk-mode-button" data-mode="summary" aria-pressed="false" role="tab">Summary</button>
                        <button type="button" class="desk-mode-button is-active" data-mode="tag-live" aria-pressed="true" role="tab">Tag Live</button>
                        <?php if ($ANNOTATIONS_ENABLED): ?>
                            <button type="button" class="desk-mode-button" data-mode="drawings" aria-pressed="false" role="tab">Drawings</button>
                        <?php endif; ?>
                    </div>
                    <div class="desk-side-scroll">
                        <div class="desk-live-tagging" data-desk-live-tagging aria-hidden="false">
                            <section class="desk-section desk-quick-tags-section" aria-label="Quick tags">
                                <div class="desk-quick-tags">
                                    <div class="panel-dark tagging-panel">
                                        <div class="desk-section-header">
                                            <div>
                                                <div class="text-sm text-subtle">Quick Tags</div>
                                                <div class="text-xs text-muted-alt">One click = one event</div>
                                            </div>
                                        </div>
                                        <div class="period-controls period-controls-collapsed">
                                            <button
                                                class="ghost-btn ghost-btn-sm desk-editable period-modal-toggle"
                                                type="button"
                                                aria-haspopup="dialog"
                                                aria-expanded="false"
                                                aria-controls="periodsModal"
                                                aria-label="Open period controls">
                                                Periods
                                            </button>
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
                                                <div id="goalShotInfo" class="goal-shot-info" style="margin:0.5em 0 1em 0;display:none;"></div>
                                                <div id="goalPlayerList" class="goal-player-modal-list"></div>
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
                                                            <svg id="shotOriginSvg" style="width:100%;height:180px;display:block;border-radius:0.2em;"></svg>
                                                            <div id="shotOriginClearWrap" style="text-align:center;margin-top:0.3em;display:none;">
                                                                <button type="button" class="ghost-btn ghost-btn-xs" id="shotOriginClearBtn">Clear origin</button>
                                                            </div>
                                                            <div class="text-xs text-muted-alt mt-3 mb-1" style="text-align:center;">Shot target</div>
                                                            <svg id="shotTargetSvg" style="width:100%;height:140px;display:block;border-radius:0.7em;"></svg>
                                                            <div id="shotTargetClearWrap" style="text-align:center;margin-top:0.3em;display:none;">
                                                                <button type="button" class="ghost-btn ghost-btn-xs" id="shotTargetClearBtn">Clear target</button>
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
                        <div class="desk-mode-panels" data-mode-panels>
                            <div class="desk-mode-panel" data-panel="summary">
                                <div class="panel-dark desk-summary-panel p-3">
                                    <div class="panel-row">
                                        <div>
                                            <div class="text-sm text-subtle">Match summary</div>
                                            <div class="text-xs text-muted-alt">Live analytics context</div>
                                        </div>
                                    </div>
                                    <div class="desk-summary-content">
                                        <?php require __DIR__ . '/../../partials/match-summary-stats.php'; ?>
                                    </div>
                                </div>
                            </div>

                            <?php if ($ANNOTATIONS_ENABLED): ?>
                                <div class="desk-mode-panel" data-panel="drawings">
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
                </div>
            </aside>
            </div>
                </div>
        </div>
        <!-- Drawing Toolbar Toggle JS -->
        <script src="<?= htmlspecialchars($base) ?>/assets/js/desk-toolbar-toggle.js<?= asset_version('/assets/js/desk-toolbar-toggle.js') ?>" defer></script>
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
                        <button type="button" class="editor-modal-close" data-editor-close aria-label="Close event editor">✕</button>
                    </div>
                </div>
                <input type="hidden" id="eventId">
                <!-- match_second is the canonical source; minute is derived from it, minute_extra stores stoppage metadata -->
                <input type="hidden" id="match_second" value="0">
                <input type="hidden" id="minute" value="0">
                <input type="hidden" id="minute_extra" value="0">
                <input type="hidden" class="desk-editable" id="team_side" value="home">
                <div class="editor-tabs-row">
                    <div class="editor-tabs" role="tablist">
                        <button id="editorTabDetails" class="editor-tab is-active" data-panel="details" role="tab" aria-controls="editorTabpanelDetails" aria-selected="true">Details</button>
                        <button id="editorTabNotes" class="editor-tab" data-panel="notes" role="tab" aria-controls="editorTabpanelNotes" aria-selected="false">Notes</button>
                        <button id="editorTabClip" class="editor-tab" data-panel="clip" role="tab" aria-controls="editorTabpanelClip" aria-selected="false">Clip</button>
                    </div>
                </div>
                <div class="editor-tab-panels">
                    <div id="editorTabpanelDetails" class="editor-tab-panel is-active" data-panel="details" role="tabpanel" aria-labelledby="editorTabDetails">
                        <div class="editor-modal-content">
                            <div class="editor-content-left">
                                <label class="field-label">Event type</label>
                                <select class="input-dark desk-editable" id="event_type_id">
                                    <?php foreach ($eventTypes as $type): ?>
                                        <option value="<?= (int)$type['id'] ?>"><?= htmlspecialchars($type['label']) ?></option>
                                    <?php endforeach; ?>
                                </select>

                                <div>
                                    <label class="field-label">Match time (MM:SS)</label>
                                    <div class="d-flex gap-sm align-items-center">
                                        <button class="ghost-btn ghost-btn-sm desk-editable time-stepper" id="eventTimeStepDown" type="button" aria-label="Decrease time">−</button>
                                        <input type="text" class="input-dark desk-editable text-center" id="event_time_display" value="00:00" aria-label="Match time" placeholder="MM:SS">
                                        <button class="ghost-btn ghost-btn-sm desk-editable time-stepper" id="eventTimeStepUp" type="button" aria-label="Increase time">+</button>
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

                            <div class="editor-content-right">
                                <label class="field-label">Player</label>
                                <div id="playerSelectorContainer" style="display:none;">
                                    <div class="player-selector-columns">
                                        <div class="player-selector-column">
                                            <div class="player-selector-column-label">Starting XI</div>
                                            <div id="playerSelectorStarting" class="player-selector-buttons"></div>
                                        </div>
                                        <div class="player-selector-column">
                                            <div class="player-selector-column-label">Subs</div>
                                            <div id="playerSelectorSubs" class="player-selector-buttons"></div>
                                        </div>
                                    </div>
                                    <input type="hidden" class="input-dark desk-editable" id="match_player_id" value="">
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
                        <button class="primary-btn desk-editable" id="eventSaveBtn" type="button">Save edits</button>
                    </div>
                    <button class="danger-btn desk-editable" id="eventDeleteBtn" type="button">Delete event</button>
                </div>
        </div>
    </div>


<script>
    (function () {
        const modeRoot = document.querySelector('[data-desk-side-modes]');
        const panelRoot = document.querySelector('[data-mode-panels]');
        const deskSide = document.querySelector('.desk-side');
        const liveContainer = document.querySelector('[data-desk-live-tagging]');
        if (!modeRoot) {
            return;
        }
        const buttons = Array.from(modeRoot.querySelectorAll('[data-mode]'));
        const panels = panelRoot ? Array.from(panelRoot.querySelectorAll('[data-panel]')) : [];
        const updateLiveVisibility = (mode) => {
            const isLive = mode === 'tag-live';
            deskSide?.classList.toggle('is-mode-active', isLive);
            if (liveContainer) {
                liveContainer.setAttribute('aria-hidden', (!isLive).toString());
            }
        };
        const activateMode = (targetMode) => {
            buttons.forEach((button) => {
                const isActive = button.dataset.mode === targetMode;
                button.classList.toggle('is-active', isActive);
                button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });
            if (!panelRoot) {
                updateLiveVisibility(targetMode);
                return;
            }
            const targetPanel = panels.find((panel) => panel.dataset.panel === targetMode);
            panels.forEach((panel) => {
                const isVisible = Boolean(targetPanel && panel === targetPanel);
                panel.classList.toggle('is-visible', isVisible);
                panel.setAttribute('aria-hidden', isVisible ? 'false' : 'true');
            });
            updateLiveVisibility(targetMode);
        };
        activateMode(modeRoot.dataset.defaultMode || 'tag-live');
        buttons.forEach((button) => {
            button.addEventListener('click', () => {
                activateMode(button.dataset.mode);
            });
        });
    })();
</script>
<script>
    (function () {
        const toggleBtn = document.querySelector('[data-desk-lineup-button]');
        const deskRoot = document.getElementById('deskRoot');
        if (!toggleBtn) {
            return;
        }

        const normalizedBase = (deskRoot?.dataset.basePath || '').replace(/\/$/, '');
        const contextMatchId = toggleBtn.dataset.matchId || deskRoot?.dataset.matchId;
        if (!contextMatchId) {
            return;
        }

        toggleBtn.addEventListener('click', () => {
            const target = `${normalizedBase || ''}/matches/${encodeURIComponent(contextMatchId)}/lineup`;
            window.location.href = target;
        });
    })();
</script>

<script>
// --- Shot Recorder SVG rendering (debug version, forced) ---
const SVG_NS = "http://www.w3.org/2000/svg";

document.addEventListener("DOMContentLoaded", function() {
    console.debug("[ShotSVG] DOMContentLoaded");
});

window.debugShotSVG = function() {
    const shotOriginSvg = document.getElementById("shotOriginSvg");
    const shotTargetSvg = document.getElementById("shotTargetSvg");
    const shotModal = document.getElementById("shotPlayerModal");
    console.debug("[ShotSVG] debugShotSVG called", {
        shotOriginSvg,
        shotTargetSvg,
        shotModal,
        shotOriginSvg_display: shotOriginSvg && shotOriginSvg.style.display,
        shotTargetSvg_display: shotTargetSvg && shotTargetSvg.style.display,
        shotOriginSvg_offset: shotOriginSvg && shotOriginSvg.offsetWidth,
        shotTargetSvg_offset: shotTargetSvg && shotTargetSvg.offsetWidth,
        shotModal_hidden: shotModal && shotModal.hidden,
        shotModal_style: shotModal && shotModal.style.display
    });
    if (shotOriginSvg) {
        shotOriginSvg.setAttribute("width", "400");
        shotOriginSvg.setAttribute("height", "180");
        renderShotOriginSvg(shotOriginSvg);
    }
    if (shotTargetSvg) {
        shotTargetSvg.setAttribute("width", "400");
        shotTargetSvg.setAttribute("height", "140");
        renderShotTargetSvg(shotTargetSvg);
    }
};

function renderShotOriginSvg(svg) {
    console.debug("[ShotSVG] renderShotOriginSvg called", svg);
    if (!svg) {
        console.debug("[ShotSVG] shotOriginSvg not found");
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
    console.debug("[ShotSVG] pitch rect appended");

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
    console.debug("[ShotSVG] penalty box appended");

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
    console.debug("[ShotSVG] six yard box appended");

    // Penalty spot
    const penaltySpot = document.createElementNS(SVG_NS, "circle");
    penaltySpot.setAttribute("cx", "50");
    penaltySpot.setAttribute("cy", "28");
    penaltySpot.setAttribute("r", "1.5");
    penaltySpot.setAttribute("fill", "#e5e7eb");
    svg.appendChild(penaltySpot);
    console.debug("[ShotSVG] penalty spot appended");

    // Penalty arc
    const arc = document.createElementNS(SVG_NS, "path");
    arc.setAttribute("d", "M35 35 A15 15 0 0 0 65 35");
    arc.setAttribute("stroke", "#e5e7eb");
    arc.setAttribute("fill", "none");
    svg.appendChild(arc);
    console.debug("[ShotSVG] penalty arc appended");
}

function renderShotTargetSvg(svg) {
    console.debug("[ShotSVG] renderShotTargetSvg called", svg);
    if (!svg) {
        console.debug("[ShotSVG] shotTargetSvg not found");
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
    console.debug("[ShotSVG] left post appended");

    // Right post
    const rightPost = document.createElementNS(SVG_NS, "line");
    rightPost.setAttribute("x1", "100");
    rightPost.setAttribute("y1", "10");
    rightPost.setAttribute("x2", "100");
    rightPost.setAttribute("y2", "50");
    rightPost.setAttribute("stroke", "#e5e7eb");
    rightPost.setAttribute("stroke-width", "3");
    svg.appendChild(rightPost);
    console.debug("[ShotSVG] right post appended");

    // Crossbar
    const crossbar = document.createElementNS(SVG_NS, "line");
    crossbar.setAttribute("x1", "20");
    crossbar.setAttribute("y1", "10");
    crossbar.setAttribute("x2", "100");
    crossbar.setAttribute("y2", "10");
    crossbar.setAttribute("stroke", "#e5e7eb");
    crossbar.setAttribute("stroke-width", "3");
    svg.appendChild(crossbar);
    console.debug("[ShotSVG] crossbar appended");

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
    console.debug("[ShotSVG] net grid verticals appended");
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
    console.debug("[ShotSVG] net grid horizontals appended");
}

// Modal integration: always render SVGs after modal is visible
window.openShotModal = function(...args) {
    console.debug("[ShotSVG] openShotModal called", args);
    const modal = document.getElementById("shotPlayerModal");
    if (!modal) {
        console.debug("[ShotSVG] shotPlayerModal not found");
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
