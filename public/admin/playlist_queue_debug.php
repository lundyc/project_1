<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/middleware/require_admin.php';

require_admin();

$basePath = base_path();
if ($basePath === '/') {
          $basePath = '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
          <meta charset="UTF-8">
          <title>Playlist queue debug</title>
          <style>
                    body {
                              font-family: system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
                              padding: 1rem;
                              line-height: 1.4;
                              background: #f5f5f5;
                    }
                    label {
                              display: block;
                              margin-bottom: 0.5rem;
                    }
                    input[type="number"],
                    select {
                              width: 100%;
                              padding: 0.35rem 0.5rem;
                              font-size: 1rem;
                    }
                    button {
                              margin-top: 0.5rem;
                              padding: 0.35rem 0.75rem;
                              font-size: 1rem;
                    }
                    pre {
                              margin-top: 1rem;
                              padding: 0.5rem;
                              background: #1e1e1e;
                              color: #f8f8f2;
                              max-height: 400px;
                              overflow: auto;
                    }
          </style>
</head>
<body>
          <h1>Playlist queue debug</h1>
          <p>Provide a playlist ID and optionally toggle the playback mode. This page simply displays the raw JSON returned by <code>/admin/playlists/{playlist_id}/queue</code>.</p>
          <label>
                    Playlist ID
                    <input type="number" id="playlistId" min="1" placeholder="e.g. 1">
          </label>
          <label>
                    Mode
                    <select id="mode">
                              <option value="full_match">full_match</option>
                              <option value="clips">clips</option>
                    </select>
          </label>
          <button id="fetchQueue">Fetch queue</button>
          <p id="hint" style="margin-top:0.5rem; font-size:0.9rem;">Make sure you are logged in as an admin before using this page.</p>
          <pre id="result">Queue response will appear here.</pre>

          <script>
                    (function () {
                              const basePath = '<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>';
                              const root = window.location.origin + basePath;
                              const playlistInput = document.getElementById('playlistId');
                              const modeInput = document.getElementById('mode');
                              const button = document.getElementById('fetchQueue');
                              const result = document.getElementById('result');

                              async function fetchQueue() {
                                        const playlistId = playlistInput.value.trim();
                                        if (!playlistId) {
                                                  result.textContent = 'Please provide a playlist ID.';
                                                  return;
                                        }

                                        const mode = modeInput.value;
                                        const url = `${root}/admin/playlists/${playlistId}/queue?mode=${encodeURIComponent(mode)}`;
                                        result.textContent = 'Loading…';

                                        try {
                                                  const response = await fetch(url, { credentials: 'same-origin' });
                                                  const payload = await response.json();
                                                  result.textContent = JSON.stringify(payload, null, 2);
                                        } catch (error) {
                                                  result.textContent = 'Fetch failed: ' + (error.message || 'unknown error');
                                        }
                              }

                              button.addEventListener('click', fetchQueue);
                              playlistInput.addEventListener('keydown', function (event) {
                                        if (event.key === 'Enter') {
                                                  fetchQueue();
                                        }
                              });
                    })();
          </script>
</body>
</html>
