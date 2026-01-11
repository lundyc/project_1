<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/middleware/require_admin.php';

require_admin();

$playlistId = isset($_GET['playlist_id']) ? (int)$_GET['playlist_id'] : 0;
if ($playlistId <= 0) {
          http_response_code(400);
          echo 'Playlist ID is required';
          exit;
}

$basePath = base_path();
if ($basePath === '/') {
          $basePath = '';
}

$scriptPath = ($basePath === '' ? '' : $basePath) . '/admin/js/playlist_player.js';
if ($scriptPath === '') {
          $scriptPath = '/admin/js/playlist_player.js';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
          <meta charset="UTF-8">
          <meta name="viewport" content="width=device-width, initial-scale=1">
          <title>Playlist Player</title>
          <style>
                    :root {
                              font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
                              color: #0c1633;
                              background: #f9fafb;
                    }
                    body {
                              margin: 0;
                              padding: 1rem;
                    }
                    h1 {
                              margin: 0;
                              font-size: 1.5rem;
                    }
                    .page {
                              max-width: 1100px;
                              margin: 0 auto;
                    }
                    .header {
                              display: flex;
                              justify-content: space-between;
                              align-items: flex-start;
                              gap: 1rem;
                              flex-wrap: wrap;
                              margin-bottom: 1rem;
                    }
                    .header__title-group {
                              flex: 1;
                              min-width: 220px;
                    }
                    .mode-toggle {
                              margin-top: 0.5rem;
                              display: inline-flex;
                              border: 1px solid #cbd5f5;
                              border-radius: 999px;
                              overflow: hidden;
                              background: #fff;
                    }
                    .mode-toggle button {
                              border: none;
                              background: transparent;
                              padding: 0.35rem 0.9rem;
                              font-size: 0.95rem;
                              color: #334155;
                              cursor: pointer;
                              font-weight: 600;
                    }
                    .mode-toggle button.active {
                              background: #0f62fe;
                              color: #fff;
                    }
                    .toggles {
                              display: flex;
                              gap: 1rem;
                              flex-wrap: wrap;
                              font-size: 0.95rem;
                    }
                    .toggles label {
                              display: inline-flex;
                              align-items: center;
                              gap: 0.35rem;
                              cursor: pointer;
                    }
                    .panels {
                              display: grid;
                              grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
                              gap: 1rem;
                    }
                    .panel {
                              background: #fff;
                              border: 1px solid #e2e8f0;
                              border-radius: 0.5rem;
                              padding: 1rem;
                    }
                    .panel h2 {
                              margin-top: 0;
                              font-size: 1rem;
                              text-transform: uppercase;
                              letter-spacing: 0.04em;
                              color: #475569;
                    }
                    .queue-list {
                              list-style: none;
                              margin: 0;
                              padding: 0;
                              max-height: 520px;
                              overflow: auto;
                    }
                    .queue-item {
                              padding: 0.65rem;
                              border-bottom: 1px solid #edf2f7;
                              cursor: pointer;
                              display: flex;
                              justify-content: space-between;
                              gap: 0.5rem;
                              font-size: 0.95rem;
                    }
                    .queue-item strong {
                              font-weight: 600;
                              display: block;
                    }
                    .queue-item:last-child {
                              border-bottom: none;
                    }
                    .queue-item.active {
                              background: #0f62fe;
                              color: #fff;
                    }
                    .player-video {
                              width: 100%;
                              height: 280px;
                              background: #000;
                              border-radius: 0.4rem;
                              overflow: hidden;
                    }
                    video {
                              width: 100%;
                              height: 100%;
                              object-fit: contain;
                              background: #000;
                    }
                    .controls {
                              margin-top: 0.65rem;
                              display: flex;
                              gap: 0.65rem;
                    }
                    .controls button {
                              flex: 1;
                              padding: 0.5rem 0.75rem;
                              border: 1px solid #cbd5f5;
                              background: #fff;
                              border-radius: 0.35rem;
                              font-weight: 600;
                              cursor: pointer;
                    }
                    .controls button:active {
                              background: #eef2ff;
                    }
                    .player-info {
                              margin-top: 0.5rem;
                              display: flex;
                              justify-content: space-between;
                              font-size: 0.9rem;
                              color: #475569;
                              flex-wrap: wrap;
                              gap: 0.5rem;
                    }
                    .status {
                              margin-top: 0.35rem;
                              font-size: 0.9rem;
                              color: #334155;
                    }
                    .status[data-type="error"] {
                              color: #b91c1c;
                    }
                    .status[data-type="info"] {
                              color: #0f172a;
                    }
                    @media (max-width: 640px) {
                              .controls button {
                                        font-size: 0.85rem;
                              }
                    }
          </style>
</head>
<body>
          <div class="page">
                    <header class="header">
                              <div class="header__title-group">
                                        <h1>Playlist Player</h1>
                                        <div id="playlistTitle" style="font-size:1.1rem; font-weight:600; color:#0f172a; margin-top:0.35rem;">Playlist #<?= $playlistId ?></div>
                                        <div class="mode-toggle" aria-label="Playback mode toggle">
                                                  <button type="button" class="mode-toggle__button" data-mode="clips">Clips</button>
                                                  <button type="button" class="mode-toggle__button" data-mode="full_match">Full Match</button>
                                        </div>
                              </div>
                              <div class="toggles">
                                        <label>
                                                  <input type="checkbox" id="toggleAutoplay" checked>
                                                  Autoplay Next
                                        </label>
                                        <label>
                                                  <input type="checkbox" id="toggleLoop">
                                                  Loop Clip Window
                                        </label>
                              </div>
                    </header>

                    <div class="panels">
                              <section class="panel">
                                        <h2>Player</h2>
                                        <div class="player-video">
                                                  <video id="playerVideo" preload="metadata"></video>
                                        </div>
                                        <div class="player-info">
                                                  <div>Current time: <span id="currentTime">00:00</span></div>
                                                  <div>Clip window: <span id="clipWindow">—</span></div>
                                        </div>
                                        <div class="controls">
                                                  <button type="button" id="prevButton">Prev</button>
                                                  <button type="button" id="playPauseButton">Play</button>
                                                  <button type="button" id="nextButton">Next</button>
                                        </div>
                                        <div class="status" id="statusMessage" data-type="info">Loading playlist…</div>
                              </section>
                              <section class="panel">
                                        <h2>Queue</h2>
                                        <ol class="queue-list" id="queueList">
                                                  <li class="queue-item">Loading…</li>
                                        </ol>
                              </section>
                    </div>
          </div>

          <script>
                    window._playlistPlayerConfig = {
                              playlistId: <?= json_encode($playlistId) ?>,
                              basePath: <?= json_encode($basePath) ?>
                    };
          </script>
          <script defer src="<?= htmlspecialchars($scriptPath, ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
