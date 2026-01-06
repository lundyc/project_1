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
          <title>Playlist navigation debug</title>
          <style>
                    body {
                              font-family: system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
                              padding: 1rem;
                              line-height: 1.4;
                              background: #fffaf0;
                    }
                    label {
                              display: block;
                              margin-bottom: 0.5rem;
                    }
                    input[type="number"] {
                              width: 100%;
                              padding: 0.35rem 0.5rem;
                              font-size: 1rem;
                    }
                    button {
                              margin-right: 0.5rem;
                              padding: 0.35rem 0.75rem;
                              font-size: 1rem;
                    }
                    pre {
                              margin-top: 1rem;
                              padding: 0.5rem;
                              background: #111;
                              color: #f8f8f2;
                              max-height: 360px;
                              overflow: auto;
                    }
          </style>
</head>
<body>
          <h1>Playlist navigation debug</h1>
          <p>Call the next/previous endpoints for <code>/admin/playlists/{playlist_id}/resolve-next</code> and <code>/resolve-prev</code>.</p>
          <label>
                    Playlist ID
                    <input type="number" id="playlistId" min="1" placeholder="e.g. 1">
          </label>
          <label>
                    Current clip ID (leave blank to fetch first clip for next or nil for prev)
                    <input type="number" id="currentClipId" min="0" placeholder="e.g. 12">
          </label>
          <div>
                    <button id="nextBtn">Resolve next</button>
                    <button id="prevBtn">Resolve previous</button>
          </div>
          <pre id="result">Navigation responses appear here.</pre>

          <script>
                    (function () {
                              const basePath = '<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>';
                              const root = window.location.origin + basePath;
                              const playlistInput = document.getElementById('playlistId');
                              const clipInput = document.getElementById('currentClipId');
                              const nextBtn = document.getElementById('nextBtn');
                              const prevBtn = document.getElementById('prevBtn');
                              const result = document.getElementById('result');

                              async function callEndpoint(direction) {
                                        const playlistId = playlistInput.value.trim();
                                        if (!playlistId) {
                                                  result.textContent = 'Please provide a playlist ID.';
                                                  return;
                                        }

                                        const clipId = clipInput.value.trim();
                                        const params = [];
                                        if (clipId) {
                                                  params.push(`current_clip_id=${encodeURIComponent(clipId)}`);
                                        }
                                        const query = params.length ? `?${params.join('&')}` : '';
                                        const url = `${root}/admin/playlists/${playlistId}/resolve-${direction}${query}`;

                                        result.textContent = `Calling ${url}…`;
                                        try {
                                                  const response = await fetch(url, { credentials: 'same-origin' });
                                                  const payload = await response.json();
                                                  result.textContent = JSON.stringify(payload, null, 2);
                                        } catch (error) {
                                                  result.textContent = 'Fetch failed: ' + (error.message || 'unknown error');
                                        }
                              }

                              nextBtn.addEventListener('click', function () {
                                        callEndpoint('next');
                              });
                              prevBtn.addEventListener('click', function () {
                                        callEndpoint('prev');
                              });
                    })();
          </script>
</body>
</html>
