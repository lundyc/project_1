(function () {
          'use strict';

          const autoActions = ['regenerate-match', 'regenerate-event'];
          const clipReviewActions = ['review-approve', 'review-reject', 'clip-details', 'clip-load'];

          const clipReviewConfig = window.VideoLabClipReviewConfig || {};
          const reviewEndpointTemplate = clipReviewConfig.reviewEndpoint || '';
          const detailsEndpointTemplate = clipReviewConfig.detailsEndpoint || '';
          const reviewMessageEl = document.querySelector('#videoLabClipReviewMessage');
          const clipModalEl = document.getElementById('videoLabClipModal');
          const clipModalTitleEl = document.getElementById('videoLabClipModalTitle');
          const clipModalStartEl = document.getElementById('videoLabClipModalStart');
          const clipModalEndEl = document.getElementById('videoLabClipModalEnd');
          const clipModalSourceEl = document.getElementById('videoLabClipModalSource');
          const clipModalVersionEl = document.getElementById('videoLabClipModalVersion');
          const clipModalSnapshotEl = document.getElementById('videoLabClipModalSnapshot');
          const clipModalHistoryEl = document.getElementById('videoLabClipModalHistory');
          const videoPlayerEl = document.getElementById('videoLabPlayer');
          const videoPlayerInfoEl = document.getElementById('videoLabPlayerInfo');
          let videoAutoStopListener = null;

          function logVideoLab(message) {
                    if (!message) {
                              return;
                    }
                    if (window.console && typeof window.console.log === 'function') {
                              window.console.log(`[VideoLab] ${message}`);
                    }
          }

          function logClipDiagnostics(clipUrl, matchId, eventId, clipId) {
                    if (!matchId || !eventId || !clipId) {
                              return;
                    }
                    const storagePath = `storage/clips/match_${matchId}/event_${eventId}/${clipId}.mp4`;
                    logVideoLab(`clip.php storage path=${storagePath}`);
                    if (!clipUrl) {
                              return;
                    }
                    logVideoLab(`clip.php HEAD ${clipUrl}`);
                    fetch(clipUrl, {
                              method: 'HEAD',
                              credentials: 'same-origin',
                    }).then((response) => {
                              const statusLabel = `${response.status} ${response.statusText || ''}`.trim();
                              const redirectNote = response.redirected ? ' redirected' : '';
                              logVideoLab(`clip.php HEAD status=${statusLabel}${redirectNote}`);
                    }).catch((error) => {
                              logVideoLab(`clip.php HEAD failed: ${error.message}`);
                    });
          }

          function clearSelectedRow() {
                    const prev = document.querySelector('.video-lab-clip-table tr.is-selected');
                    if (prev) prev.classList.remove('is-selected');
          }

          function loadClipIntoPlayer(row) {
                    if (!videoPlayerEl || !row) return;
                    const clipId = row.dataset.clipId || '';
                    logVideoLab(`loadClipIntoPlayer row clip=${clipId || 'unknown'}`);
                    const matchStart = row.dataset.startSecond ? Number(row.dataset.startSecond) : NaN;
                    const matchEnd = row.dataset.endSecond ? Number(row.dataset.endSecond) : NaN;
                    const clipDuration = row.dataset.durationSeconds ? Number(row.dataset.durationSeconds) : NaN;
                    const name = row.dataset.clipName || '';

                    // set video source to the generated clip file served by /clip.php
                    const matchId = clipReviewConfig.matchId || row.closest('table')?.dataset?.matchId || '';
                    const eventId = row.dataset.eventId || '';
                    const useClipSource = Boolean(matchId && eventId && clipId);
                    let clipUrl = '';
                    if (useClipSource) {
                              clipUrl = `${(window.location.origin || '')}/clip.php?match=${encodeURIComponent(matchId)}&event=${encodeURIComponent(eventId)}&clip=${encodeURIComponent(clipId)}`;
                              logClipDiagnostics(clipUrl, matchId, eventId, clipId);
                    }
                    try {
                              if (useClipSource) {
                                        const sourceEl = videoPlayerEl.querySelector('source');
                                        if (!sourceEl) {
                                                  const newSource = document.createElement('source');
                                                  newSource.src = clipUrl;
                                                  newSource.type = 'video/mp4';
                                                  videoPlayerEl.appendChild(newSource);
                                        } else {
                                                  sourceEl.setAttribute('src', clipUrl);
                                        }
                              }
                    } catch (e) {
                              console.warn('Failed to set clip source', e);
                    }

                    clearSelectedRow();
                    row.classList.add('is-selected');

                    if (videoPlayerInfoEl) {
                              videoPlayerInfoEl.textContent = name || 'Clip loaded';
                    }

                    // Clip files are trimmed, so start from zero and stop at their duration.
                    const playbackStart = useClipSource ? 0 : matchStart;
                    const playbackStop = useClipSource ? clipDuration : matchEnd;

                    function seekAndPlay() {
                              try {
                                        if (!isNaN(playbackStart)) {
                                                  videoPlayerEl.currentTime = Math.max(0, playbackStart);
                                        }
                                        videoPlayerEl.play().catch(() => { });
                              } catch (e) {
                                        console.error('Failed to load clip into player', e);
                              }

                              if (videoAutoStopListener) {
                                        videoPlayerEl.removeEventListener('timeupdate', videoAutoStopListener);
                                        videoAutoStopListener = null;
                              }

                              if (!isNaN(playbackStop)) {
                                        videoAutoStopListener = function () {
                                                  if (videoPlayerEl.currentTime >= playbackStop) {
                                                            videoPlayerEl.pause();
                                                            videoPlayerEl.removeEventListener('timeupdate', videoAutoStopListener);
                                                            videoAutoStopListener = null;
                                                  }
                                        };
                                        videoPlayerEl.addEventListener('timeupdate', videoAutoStopListener);
                              }
                    }

                    const onLoaded = function () {
                              videoPlayerEl.removeEventListener('loadedmetadata', onLoaded);
                              seekAndPlay();
                    };

                    if (useClipSource) {
                              videoPlayerEl.addEventListener('loadedmetadata', onLoaded);
                              try {
                                        videoPlayerEl.load();
                              } catch (e) {
                                        /* ignore */
                              }
                              return;
                    }

                    if (isNaN(playbackStart)) {
                              seekAndPlay();
                              return;
                    }
                    if (videoPlayerEl.readyState >= 1) {
                              seekAndPlay();
                              return;
                    }

                    videoPlayerEl.addEventListener('loadedmetadata', onLoaded);
                    try {
                              videoPlayerEl.load();
                    } catch (e) {
                              /* ignore */
                    }
          }

          function setFeedback(element, message, isError = false) {
                    if (!element) {
                              return;
                    }
                    element.textContent = message;
                    element.classList.toggle('text-danger', isError);
          }

          function setButtonLoading(button, loading) {
                    if (!button) {
                              return;
                    }

                    if (loading) {
                              button.dataset.originalText = button.textContent;
                              button.textContent = button.dataset.loadingText || 'Processing…';
                              button.disabled = true;
                              button.dataset.__processing = '1';
                              return;
                    }

                    if (button.dataset.originalText) {
                              button.textContent = button.dataset.originalText;
                    }
                    button.disabled = false;
                    delete button.dataset.__processing;
          }

          async function fetchJson(endpoint, { method = 'POST' } = {}) {
                    const response = await fetch(endpoint, {
                              method,
                              credentials: 'same-origin',
                    });
                    const payload = await response.json().catch(() => null);
                    if (!payload || payload.ok !== true) {
                              const error = new Error(payload?.error || 'unexpected_response');
                              error.status = response.status;
                              throw error;
                    }
                    return payload;
          }

          async function handleAction(button) {
                    const endpoint = button.dataset.endpoint;
                    const action = button.dataset.action;
                    if (!endpoint || !autoActions.includes(action)) {
                              return;
                    }
                    logVideoLab(`auto-${action} triggered endpoint=${endpoint}`);

                    const feedback = action === 'regenerate-match'
                              ? document.querySelector('#videoLabMatchFeedback')
                              : button.closest('tr')?.querySelector('[data-event-feedback]');

                    setButtonLoading(button, true);
                    try {
                              const payload = await fetchJson(endpoint);
                              if (action === 'regenerate-match') {
                                        const regenerated = payload.meta?.regenerated ?? 0;
                                        const message = `Regenerated ${regenerated} clip${regenerated === 1 ? '' : 's'}.`;
                                        setFeedback(feedback, message, false);
                              } else {
                                        const version = payload.clip?.generation_version;
                                        const message = version ? `Regenerated (v${version}).` : 'Clip regenerated.';
                                        setFeedback(feedback, message, false);
                              }
                    } catch (error) {
                              const message = error.message === 'phase3_disabled'
                                        ? 'Phase 3 is disabled.'
                                        : `Failed: ${error.message}`;
                              setFeedback(feedback, message, true);
                              console.error(error);
                    } finally {
                              setButtonLoading(button, false);
                    }
          }

          function buildClipEndpoint(template, clipId) {
                    if (!template || !clipId) {
                              return '';
                    }
                    return template.replace('{clipId}', clipId);
          }

          function formatStatusLabel(status) {
                    const normalized = String(status || '').toLowerCase();
                    if (!normalized) {
                              return 'Pending';
                    }
                    return normalized.charAt(0).toUpperCase() + normalized.slice(1);
          }

          function setClipReviewMessage(message, isError = false) {
                    if (!reviewMessageEl) {
                              return;
                    }
                    reviewMessageEl.textContent = message || '';
                    reviewMessageEl.classList.toggle('text-danger', Boolean(isError && message));
          }

          function updateSummaryCounts(summary = {}) {
                    Object.keys(summary).forEach((status) => {
                              const countEl = document.querySelector(`[data-clip-status-count="${status}"]`);
                              if (countEl) {
                                        countEl.textContent = String(summary[status] ?? 0);
                              }
                    });
          }

          function updateClipRowStatus(clipId, status) {
                    const row = document.querySelector(`[data-clip-id="${clipId}"]`);
                    if (!row) {
                              return;
                    }
                    row.dataset.reviewStatus = status;
                    const badge = row.querySelector('.video-lab-status-badge');
                    if (badge) {
                              badge.textContent = formatStatusLabel(status);
                              ['pending', 'approved', 'rejected'].forEach((name) => {
                                        badge.classList.toggle(`video-lab-status-badge-${name}`, name === status);
                              });
                    }
                    const buttons = row.querySelectorAll('[data-action="review-approve"], [data-action="review-reject"]');
                    buttons.forEach((button) => {
                              button.disabled = status !== 'pending';
                    });
          }

          async function handleClipReviewAction(button) {
                    const clipId = button.dataset.clipId;
                    if (!clipId) {
                              return;
                    }
                    const reviewAction = button.dataset.action === 'review-approve' ? 'approve' : 'reject';
                    const endpoint = buildClipEndpoint(reviewEndpointTemplate, clipId);
                    if (!endpoint) {
                              setClipReviewMessage('Clip endpoint is unavailable.', true);
                              return;
                    }
                    logVideoLab(`review-${reviewAction} clip=${clipId}`);
                    setClipReviewMessage('');
                    setButtonLoading(button, true);
                    try {
                              const payload = await fetchJson(`${endpoint}?action=${reviewAction}`);
                              updateClipRowStatus(clipId, payload.clip?.review_status ?? 'pending');
                              updateSummaryCounts(payload.summary);
                              const confirmation = reviewAction === 'approve' ? 'Clip approved.' : 'Clip rejected.';
                              setClipReviewMessage(confirmation, false);
                    } catch (error) {
                              const message = error.message === 'phase3_disabled'
                                        ? 'Phase 3 is disabled.'
                                        : `Failed: ${error.message}`;
                              setClipReviewMessage(message, true);
                              console.error(error);
                    } finally {
                              setButtonLoading(button, false);
                    }
          }

          async function handleClipDetails(button) {
                    const clipId = button.dataset.clipId;
                    const endpoint = buildClipEndpoint(detailsEndpointTemplate, clipId);
                    if (!endpoint) {
                              setClipReviewMessage('Clip details are unavailable.', true);
                              return;
                    }
                    logVideoLab(`clip-details clip=${clipId}`);
                    setClipReviewMessage('');
                    try {
                              const payload = await fetchJson(endpoint, { method: 'GET' });
                              populateClipModal(payload.clip);
                              openClipModal();
                    } catch (error) {
                              const message = `Failed to load clip details: ${error.message}`;
                              setClipReviewMessage(message, true);
                              console.error(error);
                    }
          }

          function handleClipLoad(button) {
                    const clipId = button.dataset.clipId;
                    if (!clipId) return;
                    const row = document.querySelector(`[data-clip-id="${clipId}"]`);
                    if (!row) {
                              setClipReviewMessage('Clip row not found.', true);
                              return;
                    }
                    logVideoLab(`clip-load clip=${clipId}`);
                    loadClipIntoPlayer(row);
          }

          function populateClipModal(clip) {
                    if (!clip || !clipModalEl) {
                              return;
                    }
                    if (clipModalTitleEl) {
                              clipModalTitleEl.textContent = clip.clip_name ?? clip.event_type_label ?? 'Clip details';
                    }
                    if (clipModalStartEl) {
                              clipModalStartEl.textContent = clip.start_second != null ? String(clip.start_second) : '—';
                    }
                    if (clipModalEndEl) {
                              clipModalEndEl.textContent = clip.end_second != null ? String(clip.end_second) : '—';
                    }
                    if (clipModalSourceEl) {
                              clipModalSourceEl.textContent = clip.generation_source ? clip.generation_source.replace(/_/g, ' ') : '—';
                    }
                    if (clipModalVersionEl) {
                              clipModalVersionEl.textContent = clip.generation_version != null ? String(clip.generation_version) : '—';
                    }
                    if (clipModalSnapshotEl) {
                              const snapshot = clip.event_snapshot;
                              const hasSnapshot = snapshot && typeof snapshot === 'object' && Object.keys(snapshot).length > 0;
                              clipModalSnapshotEl.textContent = hasSnapshot ? JSON.stringify(snapshot, null, 2) : 'No snapshot available.';
                    }
                    if (clipModalHistoryEl) {
                              clipModalHistoryEl.innerHTML = '';
                              (clip.history || []).forEach((entry) => {
                                        const label = entry.label || formatStatusLabel(entry.status);
                                        const reviewer = entry.reviewed_by ? ` by ${entry.reviewed_by}` : '';
                                        const time = entry.reviewed_at ? ` on ${entry.reviewed_at}` : '';
                                        const li = document.createElement('li');
                                        li.textContent = `${label}${reviewer}${time}`;
                                        clipModalHistoryEl.appendChild(li);
                              });
                    }
          }

          function openClipModal() {
                    if (!clipModalEl) {
                              return;
                    }
                    clipModalEl.classList.add('is-visible');
                    clipModalEl.setAttribute('aria-hidden', 'false');
          }

          function closeClipModal() {
                    if (!clipModalEl) {
                              return;
                    }
                    clipModalEl.classList.remove('is-visible');
                    clipModalEl.setAttribute('aria-hidden', 'true');
          }

          document.addEventListener('click', (event) => {
                    // handle row clicks to load clip into mini player
                    const row = event.target.closest('tr[data-clip-id]');
                    if (row && !event.target.closest('button') && !event.target.closest('a')) {
                              // prevent interfering with buttons and links
                              loadClipIntoPlayer(row);
                    }
                    if (event.target.closest('[data-video-lab-modal-close]')) {
                              event.preventDefault();
                              closeClipModal();
                              return;
                    }
                    const button = event.target.closest('button[data-action]');
                    if (!button) {
                              return;
                    }
                    const action = button.dataset.action;
                    // allow clip-load even if the button is marked disabled (server-side rendering may disable by default)
                    if (button.disabled && action !== 'clip-load') {
                              return;
                    }
                    if (clipReviewActions.includes(action)) {
                              event.preventDefault();
                              if (action === 'clip-details') {
                                        handleClipDetails(button);
                              } else if (action === 'clip-load') {
                                        handleClipLoad(button);
                              } else {
                                        handleClipReviewAction(button);
                              }
                              return;
                    }
                    if (!autoActions.includes(action)) {
                              return;
                    }
                    event.preventDefault();
                    handleAction(button);
          });

          document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') {
                              closeClipModal();
                    }
          });

          // Initialize tooltips (Bootstrap) and filters
          function initTooltips() {
                    try {
                              if (window.bootstrap && typeof window.bootstrap.Tooltip === 'function') {
                                        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
                                                  try { new window.bootstrap.Tooltip(el); } catch (e) { /* ignore */ }
                                        });
                              }
                    } catch (e) {
                              console.warn('Tooltip init failed', e);
                    }
          }

          function applyFilters() {
                    const status = (document.getElementById('videoLabFilterStatus')?.value || '').toLowerCase();
                    const source = (document.getElementById('videoLabFilterSource')?.value || '').toLowerCase();
                    const search = (document.getElementById('videoLabFilterSearch')?.value || '').toLowerCase().trim();
                    const rows = document.querySelectorAll('#videoLabCombinedTable tbody tr');
                    rows.forEach((row) => {
                              let show = true;
                              const rowStatus = (row.querySelector('.video-lab-status-badge')?.textContent || '').toLowerCase();
                              const rowSource = (row.dataset.generationSource || '').toLowerCase();
                              const player = (row.dataset.playerName || '').toLowerCase();
                              const name = (row.dataset.clipName || '').toLowerCase();

                              if (status) {
                                        if (!rowStatus.includes(status)) show = false;
                              }
                              if (source) {
                                        if (!rowSource.includes(source)) show = false;
                              }
                              if (search) {
                                        if (!(player.includes(search) || name.includes(search))) show = false;
                              }

                              row.style.display = show ? '' : 'none';
                    });
          }

          function initFilters() {
                    const statusEl = document.getElementById('videoLabFilterStatus');
                    const sourceEl = document.getElementById('videoLabFilterSource');
                    const searchEl = document.getElementById('videoLabFilterSearch');

                    const logFilterState = () => {
                              const status = statusEl?.value || 'all';
                              const source = sourceEl?.value || 'all';
                              const search = searchEl?.value || '';
                              logVideoLab(`filters applied status=${status} source=${source} search="${search}"`);
                    };

                    const attachSelect = (element) => {
                              if (!element) {
                                        return;
                              }
                              element.addEventListener('change', () => {
                                        applyFilters();
                                        logFilterState();
                              });
                    };

                    attachSelect(statusEl);
                    attachSelect(sourceEl);

                    if (searchEl) {
                              const onInput = () => {
                                        applyFilters();
                                        logFilterState();
                              };
                              searchEl.addEventListener('input', () => { setTimeout(onInput, 0); });
                    }

                    // Quick status buttons in overview (delegated)
                    document.addEventListener('click', (ev) => {
                              const btn = ev.target.closest('[data-filter-status]');
                              if (!btn) return;
                              ev.preventDefault();
                              const value = String(btn.getAttribute('data-filter-status') || '').toLowerCase();
                              if (statusEl) {
                                        statusEl.value = value;
                                        applyFilters();
                                        logFilterState();
                              }
                    });

                    applyFilters();
                    logFilterState();
          }

          function initPage() {
                    initTooltips();
                    initFilters();
          }

          if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', initPage);
          } else {
                    initPage();
          }
})();
