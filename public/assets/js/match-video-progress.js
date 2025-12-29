(() => {
          const cfg = window.MatchVideoDeskConfig || {};
          if (!cfg.matchId || !cfg.progressUrl) {
                    return;
          }
          const csrfToken = cfg.csrfToken || (window.DeskConfig && window.DeskConfig.csrfToken) || null;
          const fetchHeaders = {
                    Accept: 'application/json',
          };
          if (csrfToken) {
                    fetchHeaders['X-CSRF-Token'] = csrfToken;
          }

          const statusBadge = document.getElementById('deskInlineStatusBadge');
          const summaryText = document.getElementById('deskInlineSummary');
          const progressBar = document.getElementById('deskInlineProgressBar');
          const statusText = document.getElementById('deskInlineStatusText');
          const progressText = document.getElementById('deskInlineProgressText');
          const sizeText = document.getElementById('deskInlineSizeText');
          const errorBox = document.getElementById('deskInlineError');
          const retryBtn = document.getElementById('deskInlineRetryBtn');
          const videoPlayer = document.getElementById('deskVideoPlayer');
          const videoPanel = document.getElementById('deskVideoProgressPanel');
          const placeholder = document.getElementById('deskVideoPlaceholder');
          const videoFormats = Array.isArray(cfg.videoFormats) ? cfg.videoFormats : [];
          const hasVideoFormats = videoFormats.length > 0;
          const formatMap = videoFormats.reduce((acc, format) => {
                    if (format && format.id) {
                              acc[format.id] = format;
                    }
                    return acc;
          }, {});
          const firstFormatId = videoFormats.length ? videoFormats[0].id : null;
          const configuredDefaultFormatId = cfg.defaultFormatId && formatMap[cfg.defaultFormatId] ? cfg.defaultFormatId : firstFormatId;
          let currentFormatId = configuredDefaultFormatId;
          if (!currentFormatId && firstFormatId) {
                    currentFormatId = firstFormatId;
          }
          const formatButtons = Array.from(document.querySelectorAll('[data-video-format-id]'));
          const fallbackPlaceholderText = placeholder ? placeholder.textContent.trim() : '';
          const pollInterval = 2000;
          let pollTimer = null;
          const getFormat = (id) => (id && formatMap[id]) ? formatMap[id] : null;
          const getActiveFormat = () => {
                    if (!hasVideoFormats) {
                              return null;
                    }
                    const active = getFormat(currentFormatId);
                    if (active) {
                              return active;
                    }
                    if (videoFormats.length) {
                              currentFormatId = videoFormats[0].id;
                              return videoFormats[0];
                    }
                    return null;
          };
          const updatePlaceholderText = () => {
                    if (!placeholder) {
                              return;
                    }
                    if (!hasVideoFormats) {
                              placeholder.textContent = fallbackPlaceholderText;
                              return;
                    }
                    const activeFormat = getActiveFormat();
                    placeholder.textContent = activeFormat && activeFormat.placeholder
                              ? activeFormat.placeholder
                              : fallbackPlaceholderText;
          };
          const updateToggleButtons = () => {
                    if (!formatButtons.length) {
                              return;
                    }
                    formatButtons.forEach((btn) => {
                              const isActive = hasVideoFormats && btn.dataset.videoFormatId === currentFormatId;
                              btn.classList.toggle('is-active', isActive);
                              btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                    });
          };
          const showVideoForFormat = (format) => {
                    if (!format || !videoPlayer) {
                              return;
                    }
                    if (videoPanel) {
                              videoPanel.classList.add('d-none');
                    }
                    if (placeholder) {
                              placeholder.classList.add('d-none');
                    }
                    if (format.relative_path) {
                              videoPlayer.src = format.relative_path;
                    }
                    videoPlayer.classList.remove('d-none');
          };
          const hideVideoForFormat = () => {
                    if (videoPlayer) {
                              videoPlayer.pause();
                              videoPlayer.removeAttribute('src');
                              if (typeof videoPlayer.load === 'function') {
                                        videoPlayer.load();
                              }
                              videoPlayer.classList.add('d-none');
                    }
                    if (placeholder) {
                              placeholder.classList.remove('d-none');
                    }
                    if (videoPanel) {
                              videoPanel.classList.remove('d-none');
                    }
          };
          const updateVideoVisibility = () => {
                    if (!hasVideoFormats) {
                              return;
                    }
                    const activeFormat = getActiveFormat();
                    if (activeFormat && activeFormat.ready) {
                              showVideoForFormat(activeFormat);
                    } else {
                              hideVideoForFormat();
                    }
          };
          const handleFormatToggle = (formatId) => {
                    if (!formatId || !formatMap[formatId]) {
                              return;
                    }
                    if (formatId === currentFormatId) {
                              return;
                    }
                    currentFormatId = formatId;
                    updateToggleButtons();
                    updatePlaceholderText();
                    updateVideoVisibility();
          };
          const markFormatReady = (formatId) => {
                    const format = getFormat(formatId);
                    if (format) {
                              format.ready = true;
                    }
          };
          formatButtons.forEach((btn) => {
                    btn.addEventListener('click', () => handleFormatToggle(btn.dataset.videoFormatId));
          });

          const formatGB = (bytes) => {
                    if (!bytes) return '0 GB';
                    const gb = bytes / (1024 ** 3);
                    const fixed = gb >= 10 ? gb.toFixed(1) : gb.toFixed(2);
                    return `${fixed} GB`;
          };

          const formatSizeLabel = (downloaded, total) => {
                    const downloadedLabel = formatGB(Number(downloaded) || 0);
                    if (!total) {
                              return `${downloadedLabel} downloaded`;
                    }
                    const totalLabel = formatGB(Number(total) || 0);
                    return `${downloadedLabel} / ${totalLabel}`;
          };

          const applyProgressBar = (bar, progress, indeterminate) => {
                    if (!bar) return;
                    const width = indeterminate ? 100 : progress;
                    const safeWidth = Math.max(0, Math.min(100, Number(width) || 0));
                    bar.style.width = `${safeWidth}%`;
                    bar.setAttribute('aria-valuenow', safeWidth.toString());
                    if (indeterminate) {
                              bar.classList.add('progress-bar-striped', 'progress-bar-animated');
                    } else {
                              bar.classList.remove('progress-bar-striped', 'progress-bar-animated');
                    }
          };

          const setBadge = (status) => {
                    if (!statusBadge) return;
                    statusBadge.className = 'wizard-status';
                    let text = status;
                    if (status === 'downloading' || status === 'starting') {
                              statusBadge.classList.add('wizard-status-active');
                              text = status === 'starting' ? 'Starting' : 'Downloading';
                    } else if (status === 'completed') {
                              statusBadge.classList.add('wizard-status-success');
                              text = 'Completed';
                    } else if (status === 'failed') {
                              statusBadge.classList.add('wizard-status-failed');
                              text = 'Failed';
                    } else {
                              statusBadge.classList.add('wizard-status-pending');
                              text = 'Pending';
                    }
                    statusBadge.textContent = text;
          };

          const setStatusDisplay = (status, progress, message, downloadedBytes, totalBytes, error) => {
                    const safeProgress = Math.max(0, Math.min(100, Number(progress) || 0));
                    const indeterminate = !totalBytes && status !== 'completed';

                    applyProgressBar(progressBar, safeProgress, indeterminate);
                    if (progressText) {
                              progressText.textContent = `${safeProgress}%`;
                    }

                    let statusLabel = message || '';
                    if (!statusLabel) {
                              if (status === 'completed') {
                                        statusLabel = 'Download finished';
                              } else if (status === 'failed') {
                                        statusLabel = 'Download failed';
                              } else if (status === 'starting' || status === 'pending') {
                                        statusLabel = 'Starting download...';
                              } else if (status === 'downloading' && safeProgress >= 95) {
                                        statusLabel = 'Finalising...';
                              } else if (status === 'downloading') {
                                        statusLabel = totalBytes ? 'Downloading video...' : `Downloading (${formatGB(downloadedBytes)} downloaded)`;
                              } else {
                                        statusLabel = 'Waiting to start';
                              }
                    }
                    if (statusText) {
                              statusText.textContent = statusLabel;
                    }

                    if (summaryText) {
                              summaryText.textContent = status === 'completed'
                                        ? 'Video is ready for playback.'
                                        : message || (status === 'failed' ? 'Download error' : 'Downloading in background');
                    }

                    if (sizeText) {
                              sizeText.textContent = formatSizeLabel(downloadedBytes, totalBytes);
                    }

                    setBadge(status);

                    const showError = Boolean(error);
                    if (errorBox) {
                              errorBox.classList.toggle('d-none', !showError);
                              errorBox.textContent = error || '';
                    }
                    if (retryBtn) {
                              retryBtn.classList.toggle('d-none', status !== 'failed');
                    }
          };

          const showVideo = () => {
                    if (hasVideoFormats) {
                              updateVideoVisibility();
                              return;
                    }
                    if (videoPanel) {
                              videoPanel.classList.add('d-none');
                    }
                    if (placeholder) {
                              placeholder.classList.add('d-none');
                    }
                    if (videoPlayer && cfg.standardPath) {
                              videoPlayer.src = cfg.standardPath;
                              videoPlayer.classList.remove('d-none');
                    }
          };

          const stopPolling = () => {
                    if (pollTimer) {
                              clearInterval(pollTimer);
                              pollTimer = null;
                    }
          };

          const startPolling = () => {
                    stopPolling();
                    pollProgress();
                    pollTimer = window.setInterval(pollProgress, pollInterval);
          };

          const handleFailure = (message) => {
                    setStatusDisplay('failed', 0, message, 0, 0, message);
                    stopPolling();
          };

          const performRetry = async () => {
                    if (!cfg.retryUrl) {
                              throw new Error('Retry URL unavailable');
                    }
                    const res = await fetch(cfg.retryUrl, {
                              method: 'POST',
                              headers: {
                                        'Content-Type': 'application/json',
                                        ...fetchHeaders,
                              },
                              body: JSON.stringify({ match_id: cfg.matchId }),
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok || (typeof data.ok !== 'undefined' && !data.ok)) {
                              throw new Error(data.error || 'Retry failed');
                    }
                    setStatusDisplay('starting', 0, 'Retrying download...', 0, 0, null);
                    startPolling();
          };

          const pollProgress = async () => {
                    try {
                              const res = await fetch(`${cfg.progressUrl}`, {
                                        headers: fetchHeaders,
                              });
                              if (!res.ok) {
                                        throw new Error('Unable to read download progress');
                              }
                              const data = await res.json().catch(() => ({}));
                              const status = (data.status || 'pending').toLowerCase();
                              const downloadedBytes = Number(data.downloaded ?? data.downloaded_bytes) || 0;
                              const totalBytes = Number(data.total ?? data.total_bytes) || 0;
                              let percent = Number(data.percent);
                              if (!Number.isFinite(percent)) {
                                        percent = 0;
                              }
                              percent = Math.max(0, Math.min(100, percent));

                              const finalPath = data.path || null;
                              const message = data.message || data.error || data.status || '';
                              const error = data.error || (status === 'failed' ? message : null);
                              const isComplete = ['complete', 'completed', 'ready'].includes(status);

                              if (isComplete) {
                                        if (hasVideoFormats) {
                                                  const readyFormatId = configuredDefaultFormatId || currentFormatId;
                                                  if (readyFormatId) {
                                                            markFormatReady(readyFormatId);
                                                  }
                                        }
                                        setStatusDisplay(
                                                  'completed',
                                                  100,
                                                  finalPath ? `Saved to ${finalPath}` : message || 'Download complete',
                                                  downloadedBytes,
                                                  totalBytes,
                                                  null
                                        );
                                        showVideo();
                                        stopPolling();
                              } else if (status === 'failed' || status === 'error') {
                                        handleFailure(error || message || 'Download failed');
                              } else {
                                        let label = message;
                                        if (!label) {
                                                  if (status === 'pending' || status === 'starting') {
                                                            label = 'Starting download...';
                                                  } else if (totalBytes === 0) {
                                                            label = `Downloading (${formatGB(downloadedBytes)} downloaded)`;
                                                  } else {
                                                            label = 'Downloading video...';
                                                  }
                                        }
                                        const normalizedStatus = status === 'pending' ? 'starting' : 'downloading';
                                        setStatusDisplay(normalizedStatus, percent, label, downloadedBytes, totalBytes, null);
                              }
                    } catch (err) {
                              console.error('Match video progress failed', err);
                              handleFailure(err.message || 'Progress check failed');
                    }
          };

          const init = () => {
                    updateToggleButtons();
                    updatePlaceholderText();
                    if (hasVideoFormats) {
                              updateVideoVisibility();
                              const activeFormat = getActiveFormat();
                              if (activeFormat && activeFormat.ready) {
                                        return;
                              }
                    } else if (cfg.videoReady) {
                              showVideo();
                              return;
                    }
                    if (retryBtn) {
                              retryBtn.addEventListener('click', () => performRetry().catch((err) => handleFailure(err.message || 'Retry failed')));
                    }
                    startPolling();
          };

          init();
})();
