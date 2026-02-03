<?php
require_once __DIR__ . '/../../../lib/db.php';
require_once __DIR__ . '/../../../lib/phase3.php';
require_once __DIR__ . '/../../../lib/asset_helper.php';

$base = base_path();
$matchId = (int)$match['id'];
$db = db();

$videoLabEnabled = phase3_is_enabled();
$projectRoot = realpath(__DIR__ . '/../../../../');

$matchVideoStmt = $db->prepare('SELECT source_path, download_status FROM match_videos WHERE match_id = :match_id LIMIT 1');
$matchVideoStmt->execute(['match_id' => $matchId]);
$matchVideoRow = $matchVideoStmt->fetch(PDO::FETCH_ASSOC);
$dbSourcePath = $matchVideoRow['source_path'] ?? '';
$dbDownloadStatus = $matchVideoRow['download_status'] ?? '';
$isVideoDownloadComplete = in_array($dbDownloadStatus, ['completed', 'complete', 'ready'], true);

$standardRelative = $dbSourcePath ? '/videos/matches/' . ltrim($dbSourcePath, '/') : '';
$standardAbsolute = $projectRoot && $dbSourcePath
          ? $projectRoot . DIRECTORY_SEPARATOR . 'videos' . DIRECTORY_SEPARATOR . 'matches' . DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $dbSourcePath), DIRECTORY_SEPARATOR)
          : '';
$standardReady = $standardAbsolute && is_file($standardAbsolute) && $isVideoDownloadComplete;

$videoReady = $videoLabEnabled && (bool)$standardReady;
$videoSrc = $videoReady ? $standardRelative : '';
$placeholderMessage = $videoReady
          ? ''
          : 'Video is not ready yet. Start the WebSocket session once the video is available.';

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

$title = 'Match TV View';
$hideNav = true;
$appShellClasses = 'app-shell tv-shell';
$mainClasses = 'app-main tv-main';
$bodyAttributes = 'data-match-tv="true"';
// Filemtime-based versions keep URLs stable until the asset changes.
$headExtras = '<link href="' . htmlspecialchars($base) . '/assets/css/tv.css' . asset_version('/assets/css/tv.css') . '" rel="stylesheet">';
$headExtras .= '<link href="' . htmlspecialchars($base) . '/assets/css/desk.css' . asset_version('/assets/css/desk.css') . '" rel="stylesheet">';

$sessionBootstrap = [
          'matchId' => $matchId,
          'role' => 'viewer',
          'sessionEndpoint' => $base . '/api/matches/' . $matchId . '/session',
          'videoElementId' => 'deskVideoPlayer',
          'durationSeconds' => isset($match['video_duration_seconds']) ? (float)$match['video_duration_seconds'] : null,
          'ui' => [
                    'statusElementId' => 'deskSessionStatus',
                    'ownerElementId' => 'deskControlOwner',
          ],
];

$footerScripts = '<script>window.DeskSessionBootstrap = ' . json_encode($sessionBootstrap) . ';</script>';
// WebSocket disabled: $footerScripts .= '<script src="' . htmlspecialchars($base) . '/assets/js/vendor/socket.io.min.js' . asset_version('/assets/js/vendor/socket.io.min.js') . '"></script>';
$footerScripts .= '<script src="' . htmlspecialchars($base) . '/assets/js/desk-session.js' . asset_version('/assets/js/desk-session.js') . '"></script>';
$footerScripts .= '<script src="' . htmlspecialchars($base) . '/assets/js/desk-tv.js' . asset_version('/assets/js/desk-tv.js') . '"></script>';

ob_start();
?>
<div class="tv-root" data-tv-root>
          <div class="tv-video-frame" data-tv-video-frame>
                    <video
                              id="deskVideoPlayer"
                              class="tv-video-player<?= $videoReady ? '' : ' d-none' ?>"
                              poster="<?= htmlspecialchars($posterUrl) ?>"
                              preload="auto"
                              playsinline
                              muted
                              <?= $videoReady ? 'src="' . htmlspecialchars($videoSrc) . '"' : '' ?>></video>
                    <div class="tv-overlay" aria-hidden="true">
                              <div id="deskSessionStatus" class="tv-status">Connecting session…</div>
                              <div class="tv-feedback" data-tv-play-overlay aria-hidden="true">
                                        <div class="tv-feedback-icon">
                                                  <i class="fa-solid fa-play" data-tv-play-icon></i>
                                        </div>
                              </div>
                    </div>
          </div>
          <?php if (!$videoReady): ?>
                    <div class="tv-placeholder" role="status"><?= htmlspecialchars($placeholderMessage) ?></div>
          <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../layout.php';
?>
