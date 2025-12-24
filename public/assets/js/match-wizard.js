(() => {
          const cfg = window.MatchWizardConfig || {};
          const form = document.getElementById('matchWizardForm');
          if (!form) return;

          const stateKey = 'matchWizardState';
          const navSteps = Array.from(document.querySelectorAll('[data-step-nav]'));
          const stepPanels = Array.from(document.querySelectorAll('.wizard-step-panel'));
          const flash = document.getElementById('wizardFlash');
          const statusText = document.getElementById('downloadStatusText');
          const progressText = document.getElementById('downloadProgressText');
          const progressBar = document.getElementById('downloadProgressBar');
          const statusBadge = document.getElementById('downloadStatusBadge');
          const summaryText = document.getElementById('wizardSummaryText');
          const sizeText = document.getElementById('downloadSizeText');
          const retryBtn = document.getElementById('wizardRetryBtn');
          const cancelBtn = document.getElementById('wizardCancelBtn');
          const continueBtn = document.getElementById('wizardContinueBtn');
          const submitBtn = document.getElementById('wizardSubmitBtn');
          const step1Next = document.getElementById('step1Next');
          const step2Back = document.getElementById('step2Back');
          const veoInput = document.getElementById('video_url_input');
          const uploadSelect = document.getElementById('video_file_select');
          const videoModeRadios = document.querySelectorAll('input[name="video_mode"]');
          const uploadGroup = document.getElementById('videoUploadGroup');
          const veoGroup = document.getElementById('videoVeoGroup');
          const errorBox = document.getElementById('wizardError');
          const pollInterval = cfg.pollInterval || 2000;
          const lineupBackBtn = document.getElementById('lineupBackBtn');
          const veoInlinePanel = document.getElementById('veoDownloadPanel');
          const veoInlineBadge = document.getElementById('veoInlineStatusBadge');
          const veoInlineSummary = document.getElementById('veoInlineSummary');
          const veoInlineProgressBar = document.getElementById('veoInlineProgressBar');
          const veoInlineStatusText = document.getElementById('veoInlineStatusText');
          const veoInlineProgressText = document.getElementById('veoInlineProgressText');
          const veoInlineSizeText = document.getElementById('veoInlineSizeText');
          const veoInlineError = document.getElementById('veoInlineError');
          const veoInlineRetryBtn = document.getElementById('veoInlineRetryBtn');

          let currentStep = 1;
          let isSubmitting = false;
          let pollTimer = null;
          let matchId = cfg.matchId || null;

          function saveState(nextState) {
                    try {
                              const current = loadState() || {};
                              const merged = { ...current, ...nextState };
                              localStorage.setItem(stateKey, JSON.stringify(merged));
                    } catch (e) {
                              console.warn('Unable to persist wizard state', e);
                    }
          }

          function loadState() {
                    try {
                              const raw = localStorage.getItem(stateKey);
                              return raw ? JSON.parse(raw) : null;
                    } catch (e) {
                              console.warn('Unable to read wizard state', e);
                              return null;
                    }
          }

          function clearState() {
                    try {
                              localStorage.removeItem(stateKey);
                    } catch (e) {
                              console.warn('Unable to clear wizard state', e);
                    }
          }

          function setFlash(type, message) {
                    if (!flash) return;
                    flash.classList.remove('d-none', 'alert-danger', 'alert-success', 'alert-info');
                    flash.classList.add('alert', type === 'success' ? 'alert-success' : (type === 'info' ? 'alert-info' : 'alert-danger'));
                    flash.textContent = message;
          }

          function clearFlash() {
                    if (flash) {
                              flash.classList.add('d-none');
                              flash.textContent = '';
                    }
          }

          function setStatusBadge(status) {
                    const badges = [statusBadge, veoInlineBadge].filter(Boolean);
                    if (!badges.length) return;
                    badges.forEach((badge) => {
                              badge.className = 'wizard-status';
                              let text = status;
                              if (status === 'downloading' || status === 'starting') {
                                        badge.classList.add('wizard-status-active');
                                        text = status === 'starting' ? 'Starting' : 'Downloading';
                              } else if (status === 'completed') {
                                        badge.classList.add('wizard-status-success');
                                        text = 'Completed';
                              } else if (status === 'failed') {
                                        badge.classList.add('wizard-status-failed');
                                        text = 'Failed';
                              } else {
                                        badge.classList.add('wizard-status-pending');
                                        text = 'Pending';
                              }
                              badge.textContent = text;
                    });
          }

          function showStep(step) {
                    currentStep = step;
                    stepPanels.forEach((panel) => {
                              panel.classList.toggle('is-active', Number(panel.dataset.step) === step);
                    });
                    navSteps.forEach((nav) => {
                              nav.classList.toggle('is-active', Number(nav.dataset.stepNav) === step);
                    });
          }

          function goToLineupStep(targetMatchId) {
                    if (!targetMatchId) {
                              return;
                    }
                    matchId = targetMatchId;
                    cfg.matchId = targetMatchId;
                    saveState({ matchId: targetMatchId, lineupReady: true });
                    if (window.MatchWizardLineup && typeof window.MatchWizardLineup.setMatchId === 'function') {
                              window.MatchWizardLineup.setMatchId(targetMatchId);
                    }
                    disableContinue();
                    showStep(4);
          }

          function getVideoMode() {
                    const checked = Array.from(videoModeRadios).find((r) => r.checked);
                    return checked ? checked.value : 'upload';
          }

          function toggleVideoInputs() {
                    const mode = getVideoMode();
                    if (uploadSelect) {
                              uploadSelect.disabled = mode === 'veo' || uploadSelect.options.length === 0;
                    }
                    if (veoInput) {
                              veoInput.disabled = mode !== 'veo';
                    }
                    if (uploadGroup) {
                              uploadGroup.classList.toggle('d-none', mode === 'veo');
                    }
                    if (veoGroup) {
                              veoGroup.classList.toggle('d-none', mode !== 'veo');
                    }
                    if (veoInlinePanel) {
                              veoInlinePanel.classList.toggle('d-none', mode !== 'veo');
                    }
          }

          function formatGB(bytes) {
                    if (!bytes) return '0 GB';
                    const gb = bytes / (1024 ** 3);
                    const fixed = gb >= 10 ? gb.toFixed(1) : gb.toFixed(2);
                    return `${fixed} GB`;
          }

          function formatSizeLabel(downloaded, total) {
                    const downloadedLabel = formatGB(Number(downloaded) || 0);
                    if (!total) {
                              return `${downloadedLabel} downloaded`;
                    }
                    const totalLabel = formatGB(Number(total) || 0);
                    return `${downloadedLabel} / ${totalLabel}`;
          }

          function applyProgressBar(barElement, progress, indeterminate) {
                    if (!barElement) return;
                    const width = indeterminate ? 100 : progress;
                    const safeWidth = Math.max(0, Math.min(100, Number(width) || 0));
                    barElement.style.width = `${safeWidth}%`;
                    barElement.setAttribute('aria-valuenow', safeWidth.toString());
                    if (indeterminate) {
                              barElement.classList.add('progress-bar-striped', 'progress-bar-animated');
                    } else {
                              barElement.classList.remove('progress-bar-striped', 'progress-bar-animated');
                    }
          }

          function setProgress(status, progress, error, customSummary, statusMessage, downloadedBytes, totalBytes) {
                    const safeProgress = Math.max(0, Math.min(100, Number(progress) || 0));
                    const indeterminate = !totalBytes && status !== 'completed';

                    applyProgressBar(progressBar, safeProgress, indeterminate);
                    applyProgressBar(veoInlineProgressBar, safeProgress, indeterminate);

                    if (progressText) {
                              progressText.textContent = `${safeProgress}%`;
                    }
                    if (veoInlineProgressText) {
                              veoInlineProgressText.textContent = `${safeProgress}%`;
                    }

                    let statusLabel = statusMessage || '';
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
                                        statusLabel = totalBytes ? 'Downloading video...' : `Downloading (${formatGB(Number(downloadedBytes) || 0)} downloaded)`;
                              } else {
                                        statusLabel = 'Not started';
                              }
                    }
                    if (statusText) {
                              statusText.textContent = statusLabel;
                    }
                    if (veoInlineStatusText) {
                              veoInlineStatusText.textContent = statusLabel;
                    }

                    const sizeLabel = formatSizeLabel(downloadedBytes, totalBytes);
                    if (sizeText) {
                              sizeText.textContent = sizeLabel;
                    }
                    if (veoInlineSizeText) {
                              veoInlineSizeText.textContent = sizeLabel;
                    }

                    let summaryLabel = customSummary || statusMessage || '';
                    if (!summaryLabel) {
                              if (status === 'completed') {
                                        summaryLabel = 'Video saved to match folder.';
                              } else if (status === 'failed') {
                                        summaryLabel = 'Download failed';
                              } else if (status === 'downloading') {
                                        summaryLabel = totalBytes
                                                  ? 'Downloading from VEO...'
                                                  : `Downloading (${formatGB(Number(downloadedBytes) || 0)} downloaded)`;
                              } else if (status === 'starting') {
                                        summaryLabel = 'Starting download...';
                              } else {
                                        summaryLabel = 'Waiting to start';
                              }
                    }
                    if (summaryText) {
                              summaryText.textContent = summaryLabel;
                    }
                    if (veoInlineSummary) {
                              veoInlineSummary.textContent = summaryLabel;
                    }

                    setStatusBadge(status);

                    const showError = Boolean(error);
                    if (errorBox) {
                              errorBox.classList.toggle('d-none', !showError);
                              errorBox.textContent = error || '';
                    }
                    if (veoInlineError) {
                              veoInlineError.classList.toggle('d-none', !showError);
                              veoInlineError.textContent = error || '';
                    }

                    if (retryBtn) {
                              retryBtn.classList.toggle('d-none', status !== 'failed');
                    }
                    if (veoInlineRetryBtn) {
                              veoInlineRetryBtn.classList.toggle('d-none', status !== 'failed');
                    }

                    if (cancelBtn) {
                              cancelBtn.classList.toggle('d-none', !(status === 'downloading' || status === 'pending'));
                              cancelBtn.disabled = status === 'completed' || status === 'failed';
                    }
          }

          function setContinueReady() {
                    if (!continueBtn) return;
                    continueBtn.classList.remove('disabled');
                    continueBtn.setAttribute('aria-disabled', 'false');
                    const target = cfg.continuePath || `${cfg.basePath}/matches`;
                    continueBtn.href = target;
          }

          function disableContinue() {
                    if (!continueBtn) return;
                    continueBtn.classList.add('disabled');
                    continueBtn.setAttribute('aria-disabled', 'true');
                    continueBtn.href = '#';
          }

          function collectPayload(videoMode) {
                    const payload = {
                              club_id: form.elements['club_id'] ? form.elements['club_id'].value : '',
                              season_id: form.elements['season_id'] ? form.elements['season_id'].value : '',
                              competition_id: form.elements['competition_id'] ? form.elements['competition_id'].value : '',
                              home_team_id: form.elements['home_team_id'] ? form.elements['home_team_id'].value : '',
                              away_team_id: form.elements['away_team_id'] ? form.elements['away_team_id'].value : '',
                              kickoff_at: form.elements['kickoff_at'] ? form.elements['kickoff_at'].value : '',
                              venue: form.elements['venue'] ? form.elements['venue'].value : '',
                              referee: form.elements['referee'] ? form.elements['referee'].value : '',
                              attendance: form.elements['attendance'] ? form.elements['attendance'].value : '',
                              status: form.elements['status'] ? form.elements['status'].value : 'draft',
                              video_source_type: videoMode === 'veo' ? 'veo' : 'upload',
                              video_source_path: videoMode === 'upload' && uploadSelect ? uploadSelect.value : '',
                    };
                    if (matchId) {
                              payload.match_id = matchId;
                    }
                    return payload;
          }

          function validateStep1() {
                    const home = form.elements['home_team_id'] ? form.elements['home_team_id'].value : '';
                    const away = form.elements['away_team_id'] ? form.elements['away_team_id'].value : '';
                    const club = form.elements['club_id'] ? form.elements['club_id'].value : '';
                    if (!club || !home || !away) {
                              setFlash('danger', 'Club, home team, and away team are required.');
                              return false;
                    }
                    if (home === away) {
                              setFlash('danger', 'Home and away teams must be different.');
                              return false;
                    }
                    return true;
          }

          async function callJson(url, payload) {
                    const res = await fetch(url, {
                              method: 'POST',
                              headers: {
                                        'Content-Type': 'application/json',
                                        Accept: 'application/json',
                              },
                              body: JSON.stringify(payload),
                    });

                    const data = await res.json().catch(() => ({}));
                    if (!res.ok || !data.ok) {
                              const message = data.error || 'Request failed';
                              throw new Error(message);
                    }
                    return data;
          }

          async function saveMatch(videoMode) {
                    const payload = collectPayload(videoMode);
                    const endpoint = cfg.isEdit && cfg.updateEndpoint ? cfg.updateEndpoint : cfg.createEndpoint;
                    if (!endpoint) {
                              throw new Error('Missing endpoint');
                    }
                    const data = await callJson(endpoint, payload);
                    matchId = data.match_id || matchId || cfg.matchId;
                    cfg.matchId = matchId;
                    return data;
          }

          async function startDownload() {
                    if (!matchId) throw new Error('Missing match id');
                    const veoUrl = veoInput ? veoInput.value.trim() : '';
                    if (!veoUrl) {
                              throw new Error('VEO URL required');
                    }

                    const startUrl = `${cfg.basePath}/api/matches/${matchId}/video/veo/start`;
                    console.info('Starting VEO download', { matchId, startUrl, veoUrl });
                    await callJson(startUrl, { veo_url: veoUrl });
                    console.info('VEO download spawned, starting polling', { matchId });
                    saveState({ matchId, videoType: 'veo', veoUrl });
                    setProgress('downloading', 0, null, 'Starting download...', 'Starting download...', 0, 0);
                    showStep(3);
                    disableContinue();
                    startPolling();
          }

          async function retryDownload() {
                    if (!matchId) throw new Error('Missing match id');
                    const retryUrl = `${cfg.basePath}/api/match-video/retry`;
                    await callJson(retryUrl, { match_id: matchId });
                    setProgress('starting', 0, null, 'Retrying download...', 'Retrying download', 0, 0);
                    disableContinue();
                    startPolling();
          }

          async function cancelDownload() {
                    if (!matchId) throw new Error('Missing match id');
                    const cancelUrl = `${cfg.basePath}/api/matches/${matchId}/video/veo/cancel`;
                    await callJson(cancelUrl, {});
                    stopPolling();
                    setProgress('failed', 0, 'Cancelled by user', 'Download cancelled', 'Cancelled by user', 0, 0);
                    disableContinue();
          }

          async function pollProgress() {
                    if (!matchId) return;
                    try {
                              const pollUrl = `${cfg.basePath}/api/match-video/progress?match_id=${matchId}`;
                              const res = await fetch(pollUrl, {
                                        headers: { Accept: 'application/json' },
                              });
                              if (!res.ok) {
                                        throw new Error('Unable to read download progress');
                              }
                              const data = await res.json().catch(() => ({}));

                              const status = (data.status || 'pending').toLowerCase();
                              const downloadedBytes = Number(data.downloaded_bytes) || 0;
                              let totalBytes = Number(data.total_bytes) || 0;
                              const message = data.message || data.error || data.status || '';
                              const error = data.error || (status === 'failed' ? message : null);
                              let percent = Number(data.percent);
                              if (!Number.isFinite(percent) || percent === 0) {
                                        if (totalBytes > 0) {
                                                  percent = Math.round((downloadedBytes / totalBytes) * 100);
                                        } else if (data.formats) {
                                                  const formatPercents = Object.values(data.formats)
                                                            .map((fmt) => Number(fmt.percent) || 0)
                                                            .filter((val) => val > 0);
                                                  if (formatPercents.length) {
                                                            percent = Math.round(
                                                                      formatPercents.reduce((sum, val) => sum + val, 0) / formatPercents.length
                                                            );
                                                  }
                                        }
                              }
                              percent = Math.max(0, Math.min(100, Number(percent) || 0));

                              if (!totalBytes && data.formats) {
                                        totalBytes = Object.values(data.formats)
                                                  .map((fmt) => Number(fmt.total_bytes) || 0)
                                                  .reduce((sum, val) => sum + val, 0);
                              }

                              console.debug('VEO status poll', { matchId, status, percent, message });

                              const isCompleted = ['complete', 'completed', 'ready'].includes(status);
                              const finalPath = data.path ? `${data.path}/standard/match_${matchId}_standard.mp4` : null;

                              if (isCompleted) {
                                        setProgress(
                                                  'completed',
                                                  100,
                                                  null,
                                                  finalPath ? `Saved to ${finalPath}` : message || null,
                                                  message || 'Download complete',
                                                  downloadedBytes,
                                                  totalBytes
                                        );
                                        console.info('VEO download completed', { matchId, videoPath: finalPath, percent });
                                        setContinueReady();
                                        stopPolling();
                                        goToLineupStep(matchId);
                              } else if (status === 'failed' || status === 'error') {
                                        setProgress('failed', percent, error || 'Download failed', null, message || 'Download failed', downloadedBytes, totalBytes);
                                        console.warn('VEO download failed', { matchId, error: error || message, percent });
                                        disableContinue();
                                        stopPolling();
                              } else {
                                        let label = message;
                                        if (!label) {
                                                  if (status === 'starting' || status === 'pending') {
                                                            label = 'Starting download...';
                                                  } else if (totalBytes === 0) {
                                                            label = `Downloading (${formatGB(downloadedBytes)} downloaded)`;
                                                  } else {
                                                            label = 'Downloading video...';
                                                  }
                                        }
                                        const normalizedStatus = status === 'pending' ? 'starting' : 'downloading';
                                        setProgress(normalizedStatus, percent, null, null, label, downloadedBytes, totalBytes);
                              }
                    } catch (e) {
                              console.error('VEO status poll failed', e);
                              setProgress('failed', 0, e.message || 'Progress check failed', null, null, 0, 0);
                              stopPolling();
                    }
          }

          function startPolling() {
                    stopPolling();
                    pollProgress();
                    pollTimer = window.setInterval(pollProgress, pollInterval);
          }

          function stopPolling() {
                    if (pollTimer) {
                              clearInterval(pollTimer);
                              pollTimer = null;
                    }
          }

          async function handleSubmit() {
                    clearFlash();
                    if (isSubmitting) return;
                    if (!validateStep1()) {
                              showStep(1);
                              return;
                    }

                    const mode = getVideoMode();
                    if (mode === 'veo' && (!veoInput || !veoInput.value.trim())) {
                              setFlash('danger', 'VEO URL required to start the download.');
                              showStep(2);
                              return;
                    }

                    isSubmitting = true;
                    if (submitBtn) {
                              submitBtn.disabled = true;
                              submitBtn.textContent = 'Saving...';
                    }

                    try {
                              await saveMatch(mode);
                              if (mode === 'veo') {
                                        await startDownload();
                              } else {
                                        const hasVideo = uploadSelect && uploadSelect.value;
                                        const summary = hasVideo
                                                  ? 'Upload selection saved.'
                                                  : 'Match saved. Add video before analysis.';
                                        const statusKey = hasVideo ? 'completed' : 'pending';
                                        const percent = hasVideo ? 100 : 0;
                                        setProgress(statusKey, percent, null, summary, null, 0, 0);
                                        setContinueReady();
                                        goToLineupStep(matchId);
                              }
                    } catch (e) {
                              setFlash('danger', e.message || 'Unable to save match');
                    } finally {
                              isSubmitting = false;
                              if (submitBtn) {
                                        submitBtn.disabled = false;
                                        submitBtn.textContent = cfg.isEdit ? 'Save & start' : 'Create & start';
                              }
                    }
          }

          function resumeIfNeeded() {
                    let stored = loadState();

                    if (!cfg.isEdit) {
                              clearState();
                              stored = null;
                    }

                    if (stored && stored.matchId) {
                              matchId = stored.matchId;
                              cfg.matchId = stored.matchId;
                              if (stored.lineupReady) {
                                        goToLineupStep(stored.matchId);
                                        return;
                              }
                              if (stored.videoType === 'veo') {
                                        if (veoInput && stored.veoUrl) {
                                                  veoInput.value = stored.veoUrl;
                                        }
                                        const radio = Array.from(videoModeRadios).find((r) => r.value === 'veo');
                                        if (radio) radio.checked = true;
                                        toggleVideoInputs();
                                        showStep(3);
                                        startPolling();
                                        return;
                              }
                    }

                    if (cfg.isEdit && cfg.matchId && cfg.initialVideoType === 'veo') {
                              matchId = cfg.matchId;
                              if (veoInput && cfg.initialVeoUrl) {
                                        veoInput.value = cfg.initialVeoUrl;
                              }
                              const status = cfg.initialDownloadStatus || 'pending';
                              const progress = cfg.initialDownloadProgress || 0;
                              const radio = Array.from(videoModeRadios).find((r) => r.value === 'veo');
                              if (radio) radio.checked = true;
                              toggleVideoInputs();

                              if (status === 'completed') {
                                        setProgress('completed', 100, null, null, null, 0, 0);
                                        setContinueReady();
                                        showStep(3);
                              } else if (status === 'downloading' || status === 'pending') {
                                        setProgress(status === 'pending' ? 'pending' : 'downloading', progress, null, null, null, 0, 0);
                                        showStep(3);
                                        startPolling();
                              }
                    }
          }

          function init() {
                    toggleVideoInputs();
                    showStep(1);
                    resumeIfNeeded();

                    if (videoModeRadios.length) {
                              videoModeRadios.forEach((radio) => radio.addEventListener('change', toggleVideoInputs));
                    }
                    if (step1Next) {
                              step1Next.addEventListener('click', () => {
                                        clearFlash();
                                        if (validateStep1()) {
                                                  showStep(2);
                                        }
                              });
                    }
                    if (step2Back) {
                              step2Back.addEventListener('click', () => showStep(1));
                    }
                    if (submitBtn) {
                              submitBtn.addEventListener('click', handleSubmit);
                    }
                    if (retryBtn) {
                              retryBtn.addEventListener('click', () => retryDownload().catch((e) => setFlash('danger', e.message || 'Retry failed')));
                    }
                    if (veoInlineRetryBtn) {
                              veoInlineRetryBtn.addEventListener('click', () => retryDownload().catch((e) => setFlash('danger', e.message || 'Retry failed')));
                    }
                    if (cancelBtn) {
                              cancelBtn.addEventListener('click', () => cancelDownload().catch((e) => setFlash('danger', e.message || 'Cancel failed')));
                    }
                    if (lineupBackBtn) {
                              lineupBackBtn.addEventListener('click', () => showStep(3));
                    }
                    form.addEventListener('submit', (e) => {
                              e.preventDefault();
                              handleSubmit();
                    });
          }

          init();
})();
