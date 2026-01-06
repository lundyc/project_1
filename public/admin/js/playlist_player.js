(function () {
          const config = window._playlistPlayerConfig || {};
          const playlistId = Number(config.playlistId || 0);
          if (!playlistId) {
                    console.warn('playlist_player requires playlist_id');
                    return;
          }

          const rawBasePath = typeof config.basePath === 'string' ? config.basePath : '';
          const normalizedBasePath = rawBasePath && !rawBasePath.startsWith('/') ? `/${rawBasePath}` : rawBasePath;
          const root = window.location.origin + normalizedBasePath;
          const playlistBase = `${root}/admin/playlists/${playlistId}`;

          const statusEl = document.getElementById('statusMessage');
          const queueListEl = document.getElementById('queueList');
          const playlistTitleEl = document.getElementById('playlistTitle');
          const videoEl = document.getElementById('playerVideo');
          const currentTimeEl = document.getElementById('currentTime');
          const clipWindowEl = document.getElementById('clipWindow');
          const playPauseButton = document.getElementById('playPauseButton');
          const prevButton = document.getElementById('prevButton');
          const nextButton = document.getElementById('nextButton');
          const autoplayCheckbox = document.getElementById('toggleAutoplay');
          const loopCheckbox = document.getElementById('toggleLoop');
          const modeButtons = Array.from(document.querySelectorAll('.mode-toggle__button'));

          let queue = [];
          let fullMatchVideoUrl = '';
          let currentClip = null;
          let state = {
                    mode: 'full_match',
                    autoplayNext: true,
                    loopClip: false,
                    current_clip_id: null,
                    current_time: 0,
          };
          let windowBoundaryLocked = false;
          let stateSaveTimer = null;

          const formatTime = (value) => {
                    const seconds = Math.max(0, Number(value) || 0);
                    const minutes = Math.floor(seconds / 60);
                    const remainder = Math.floor(seconds % 60);
                    return `${String(minutes).padStart(2, '0')}:${String(remainder).padStart(2, '0')}`;
          };

          const updateStatus = (type, message) => {
                    if (!statusEl) {
                              return;
                    }
                    statusEl.dataset.type = type;
                    statusEl.textContent = message;
          };

          const fetchJson = async (url, options = {}) => {
                    const response = await fetch(url, { credentials: 'same-origin', ...options });
                    if (!response.ok) {
                              const text = await response.text();
                              throw new Error(text || `Request failed (${response.status})`);
                    }
                    return response.json();
          };

          const loadQueue = async (mode) => {
                    const targetUrl = `${playlistBase}/queue?mode=${encodeURIComponent(mode)}`;
                    const payload = await fetchJson(targetUrl);
                    queue = Array.isArray(payload.queue) ? payload.queue.slice() : [];
                    fullMatchVideoUrl = payload.full_match_video_url || '';
                    queue.sort((a, b) => {
                              const orderA = typeof a.sort_order === 'number' ? a.sort_order : 0;
                              const orderB = typeof b.sort_order === 'number' ? b.sort_order : 0;
                              return orderA - orderB;
                    });
                    state.mode = payload.mode || mode;
                    if (payload.playlist && typeof payload.playlist.title === 'string' && payload.playlist.title.trim() !== '') {
                              playlistTitleEl.textContent = payload.playlist.title.trim();
                    }
                    renderQueue();
          };

          const renderQueue = () => {
                    queueListEl.innerHTML = '';
                    if (!queue.length) {
                              queueListEl.innerHTML = '<li class="queue-item" aria-live="polite">Queue is empty.</li>';
                              currentClip = null;
                              state.current_clip_id = null;
                              state.current_time = 0;
                              updateClipWindowDisplay();
                              updateStatus('info', 'No clips were returned for this playlist.');
                              scheduleStateSave(true);
                              return;
                    }

                    queue.forEach((clip, index) => {
                              const item = document.createElement('li');
                              item.className = 'queue-item';
                              item.setAttribute('data-clip-id', String(clip.clip_id ?? index));
                              const label = document.createElement('div');
                              label.innerHTML = `<strong>${clip.sort_order ?? index + 1}. ${clip.clip_name || 'Untitled clip'}</strong>`;
                              const range = document.createElement('div');
                              range.textContent = `${formatTime(clip.start_second)} – ${formatTime(clip.end_second)}`;
                              item.append(label, range);
                              item.addEventListener('click', () => {
                                        selectClip(clip);
                              });
                              queueListEl.appendChild(item);
                    });
                    updateQueueHighlight();
                    updateStatus('info', `Queue loaded (${queue.length} clip${queue.length === 1 ? '' : 's'}).`);
          };

          const updateQueueHighlight = () => {
                    const items = Array.from(queueListEl.querySelectorAll('.queue-item'));
                    items.forEach((item) => {
                              const clipId = parseInt(item.dataset.clipId, 10);
                              if (currentClip && clipId === currentClip.clip_id) {
                                        item.classList.add('active');
                                        item.setAttribute('aria-current', 'true');
                              } else {
                                        item.classList.remove('active');
                                        item.removeAttribute('aria-current');
                              }
                    });
          };

          const isWindowPlayback = () => {
                    if (!currentClip) {
                              return false;
                    }
                    if (state.mode === 'full_match') {
                              return true;
                    }
                    return !currentClip.clip_video_url;
          };

          const updateClipWindowDisplay = () => {
                    if (currentClip) {
                              clipWindowEl.textContent = `${formatTime(currentClip.start_second)} – ${formatTime(currentClip.end_second)}`;
                    } else {
                              clipWindowEl.textContent = '—';
                    }
          };

          const persistState = async () => {
                    if (!playlistId) {
                              return;
                    }
                    state.current_time = Math.max(0, Number(videoEl.currentTime) || 0);
                    const payload = {
                              mode: state.mode,
                              current_clip_id: state.current_clip_id,
                              current_time: state.current_time,
                              autoplay_next: state.autoplayNext,
                              loop_clip: state.loopClip,
                    };
                    try {
                              await fetchJson(`${playlistBase}/player-state`, {
                                        method: 'POST',
                                        headers: {
                                                  'Content-Type': 'application/json',
                                        },
                                        body: JSON.stringify(payload),
                              });
                    } catch (error) {
                              console.error('Unable to persist player state', error);
                    }
          };

          const scheduleStateSave = (force = false) => {
                    if (force) {
                              if (stateSaveTimer) {
                                        clearTimeout(stateSaveTimer);
                                        stateSaveTimer = null;
                              }
                              persistState();
                              return;
                    }
                    if (stateSaveTimer) {
                              return;
                    }
                    stateSaveTimer = setTimeout(() => {
                              stateSaveTimer = null;
                              persistState();
                    }, 1400);
          };

          const setModeButtons = () => {
                    modeButtons.forEach((button) => {
                              if (button.dataset.mode === state.mode) {
                                        button.classList.add('active');
                              } else {
                                        button.classList.remove('active');
                              }
                    });
          };

          const selectClip = async (clip, options = {}) => {
                    if (!clip) {
                              return;
                    }
                    windowBoundaryLocked = false;
                    currentClip = clip;
                    state.current_clip_id = clip.clip_id;
                    const useClipAsset = state.mode === 'clips' && clip.clip_video_url;
                    const enforceWindow = !useClipAsset;
                    updateClipWindowDisplay();
                    const restoredTime = typeof options.restoreTime === 'number' ? options.restoreTime : null;
                    const desiredTime = restoredTime !== null
                              ? restoredTime
                              : enforceWindow
                                        ? clip.start_second
                                        : 0;
                    const source = useClipAsset ? clip.clip_video_url : fullMatchVideoUrl;
                    if (source && videoEl.src !== source) {
                              videoEl.src = source;
                              videoEl.load();
                    }
                    const setTime = () => {
                              try {
                                        videoEl.currentTime = Math.min(desiredTime, videoEl.duration || desiredTime);
                              } catch (error) {
                                        console.error('Unable to set player time', error);
                              }
                    };
                    if (videoEl.readyState >= 1) {
                              setTime();
                    } else {
                              const handler = () => {
                                        videoEl.removeEventListener('loadedmetadata', handler);
                                        setTime();
                              };
                              videoEl.addEventListener('loadedmetadata', handler);
                    }
                    updateQueueHighlight();
                    updateStatus('info', `Now playing: ${clip.clip_name || 'Clip #' + clip.clip_id}`);
                    if (!options.skipPlay) {
                              videoEl.play().catch(() => {});
                    }
                    state.current_time = Math.max(0, Number(desiredTime) || 0);
                    scheduleStateSave(true);
          };

          const handleClipBoundary = () => {
                    if (!currentClip || !isWindowPlayback()) {
                              return;
                    }
                    const clipEnd = Math.max(0, Number(currentClip.end_second) || 0);
                    if (clipEnd <= 0) {
                              return;
                    }
                    if (videoEl.currentTime < clipEnd - 0.15) {
                              return;
                    }
                    if (windowBoundaryLocked) {
                              return;
                    }
                    windowBoundaryLocked = true;
                    setTimeout(() => {
                              windowBoundaryLocked = false;
                    }, 400);
                    if (state.loopClip) {
                              videoEl.currentTime = Math.max(0, Number(currentClip.start_second) || 0);
                              return;
                    }
                    if (state.autoplayNext) {
                              goToNextClip();
                              return;
                    }
                    videoEl.pause();
                    videoEl.currentTime = clipEnd;
          };

          const goToNextClip = async () => {
                    const clipIdParam = currentClip && currentClip.clip_id ? `?current_clip_id=${encodeURIComponent(currentClip.clip_id)}` : '';
                    try {
                              const payload = await fetchJson(`${playlistBase}/resolve-next${clipIdParam}`);
                              if (payload.clip) {
                                        await selectClip(payload.clip);
                              } else {
                                        updateStatus('info', 'Reached the end of the queue.');
                              }
                    } catch (error) {
                              updateStatus('error', 'Next clip request failed.');
                              console.error(error);
                    }
          };

          const goToPrevClip = async () => {
                    const clipIdParam = currentClip && currentClip.clip_id ? `?current_clip_id=${encodeURIComponent(currentClip.clip_id)}` : '';
                    try {
                              const payload = await fetchJson(`${playlistBase}/resolve-prev${clipIdParam}`);
                              if (payload.clip) {
                                        await selectClip(payload.clip);
                              } else {
                                        updateStatus('info', 'At the beginning of the queue.');
                              }
                    } catch (error) {
                              updateStatus('error', 'Previous clip request failed.');
                              console.error(error);
                    }
          };

          const togglePlayPause = () => {
                    if (videoEl.paused) {
                              videoEl.play().catch(() => {});
                    } else {
                              videoEl.pause();
                    }
          };

          const loadPlayerState = async () => {
                    const payload = await fetchJson(`${playlistBase}/player-state`);
                    const saved = payload.state || {};
                    state.autoplayNext = saved.autoplay_next ?? state.autoplayNext;
                    state.loopClip = saved.loop_clip ?? state.loopClip;
                    state.mode = saved.mode || state.mode;
                    state.current_clip_id = saved.current_clip_id ?? state.current_clip_id;
                    state.current_time = saved.current_time ?? state.current_time;
                    autoplayCheckbox.checked = state.autoplayNext;
                    loopCheckbox.checked = state.loopClip;
                    setModeButtons();
          };

          const setMode = async (newMode) => {
                    if (!newMode || newMode === state.mode) {
                              return;
                    }
                    const previousMode = state.mode;
                    state.mode = newMode;
                    setModeButtons();
                    updateStatus('info', `Switched to ${newMode.replace('_', ' ')} mode.`);
                    try {
                              await loadQueue(newMode);
                              const preferredClip = queue.find((entry) => entry.clip_id === state.current_clip_id) || queue[0] || null;
                              if (preferredClip) {
                                        await selectClip(preferredClip, { restoreTime: state.current_time, skipPlay: true });
                                        videoEl.play().catch(() => {});
                              }
                              scheduleStateSave(true);
                    } catch (error) {
                              state.mode = previousMode;
                              setModeButtons();
                              updateStatus('error', 'Unable to switch playback mode.');
                              console.error(error);
                    }
          };

          const handleKeyDown = (event) => {
                    const activeTag = document.activeElement ? document.activeElement.tagName : '';
                    if (['INPUT', 'TEXTAREA'].includes(activeTag)) {
                              return;
                    }
                    if (document.activeElement && document.activeElement.isContentEditable) {
                              return;
                    }
                    const key = event.key.toLowerCase();
                    switch (key) {
                              case ' ':
                                        event.preventDefault();
                                        togglePlayPause();
                                        break;
                              case 'arrowleft':
                                        if (isWindowPlayback()) {
                                                  videoEl.currentTime = Math.max(0, videoEl.currentTime - 5);
                                        }
                                        break;
                              case 'arrowright':
                                        if (isWindowPlayback()) {
                                                  videoEl.currentTime = Math.max(0, videoEl.currentTime + 5);
                                        }
                                        break;
                              case 'n':
                                        event.preventDefault();
                                        goToNextClip();
                                        break;
                              case 'p':
                                        event.preventDefault();
                                        goToPrevClip();
                                        break;
                              case 'm':
                                        event.preventDefault();
                                        setMode(state.mode === 'clips' ? 'full_match' : 'clips');
                                        break;
                              case 'l':
                                        event.preventDefault();
                                        loopCheckbox.checked = !loopCheckbox.checked;
                                        loopCheckbox.dispatchEvent(new Event('change'));
                                        break;
                              case 'a':
                                        event.preventDefault();
                                        autoplayCheckbox.checked = !autoplayCheckbox.checked;
                                        autoplayCheckbox.dispatchEvent(new Event('change'));
                                        break;
                              case 'escape':
                                        if (document.activeElement) {
                                                  document.activeElement.blur();
                                        }
                                        break;
                    }
          };

          const initialize = async () => {
                    setModeButtons();
                    try {
                              await loadPlayerState();
                              await loadQueue(state.mode);
                              const initialClip = queue.find((entry) => entry.clip_id === state.current_clip_id) || queue[0] || null;
                              if (initialClip) {
                                        await selectClip(initialClip, { restoreTime: state.current_time });
                              } else {
                                        updateStatus('info', 'Waiting for a clip to be chosen.');
                              }
                    } catch (error) {
                              updateStatus('error', 'Failed to load playlist.');
                              console.error(error);
                    }
          };

          playPauseButton.addEventListener('click', togglePlayPause);
          prevButton.addEventListener('click', goToPrevClip);
          nextButton.addEventListener('click', goToNextClip);
          autoplayCheckbox.addEventListener('change', () => {
                    state.autoplayNext = autoplayCheckbox.checked;
                    scheduleStateSave(true);
          });
          loopCheckbox.addEventListener('change', () => {
                    state.loopClip = loopCheckbox.checked;
                    scheduleStateSave(true);
          });
          modeButtons.forEach((button) => {
                    button.addEventListener('click', () => {
                              setMode(button.dataset.mode);
                    });
          });
          videoEl.addEventListener('timeupdate', () => {
                    currentTimeEl.textContent = formatTime(videoEl.currentTime);
                    if (isWindowPlayback()) {
                              handleClipBoundary();
                    }
                    scheduleStateSave();
          });
          videoEl.addEventListener('play', () => {
                    playPauseButton.textContent = 'Pause';
          });
          videoEl.addEventListener('pause', () => {
                    playPauseButton.textContent = 'Play';
          });
          window.addEventListener('keydown', handleKeyDown);

          initialize();
}());
