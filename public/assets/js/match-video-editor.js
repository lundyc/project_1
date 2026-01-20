/**
 * Match Video Editor
 * Handles video source editing form interactions
 */

(() => {
          'use strict';

          const config = window.MatchWizardConfig || {};
          const hiddenClass = 'hidden';

          // DOM elements
          const videoModeVeo = document.getElementById('videoModeVeo');
          const videoModeUpload = document.getElementById('videoModeUpload');
          const videoUploadGroup = document.getElementById('videoUploadGroup');
          const videoVeoGroup = document.getElementById('videoVeoGroup');
          const videoFileSelect = document.getElementById('video_file_select');
          const videoUrlInput = document.getElementById('video_url_input');
          const veoDownloadPanel = document.getElementById('veoDownloadPanel');
          const saveVideoBtn = document.getElementById('saveVideoBtn');

          // Handle video mode toggle
          function updateVideoMode() {
                    const isVeo = videoModeVeo && videoModeVeo.checked;

                    if (videoUploadGroup) {
                              videoUploadGroup.classList.toggle(hiddenClass, isVeo);
                    }
                    if (videoVeoGroup) {
                              videoVeoGroup.classList.toggle(hiddenClass, !isVeo);
                    }

                    if (videoFileSelect) {
                              videoFileSelect.disabled = isVeo;
                    }
                    if (videoUrlInput) {
                              videoUrlInput.disabled = !isVeo;
                    }
          }

          // Initialize event listeners
          if (videoModeVeo) {
                    videoModeVeo.addEventListener('change', updateVideoMode);
          }
          if (videoModeUpload) {
                    videoModeUpload.addEventListener('change', updateVideoMode);
          }

          // Poll for download progress if VEO is selected and download is in progress
          let pollInterval = null;

          function startPolling() {
                    if (pollInterval) {
                              return;
                    }

                    const initialStatus = config.initialDownloadStatus;
                    if (!initialStatus || initialStatus === 'completed' || initialStatus === 'failed') {
                              return;
                    }

                    pollInterval = setInterval(async () => {
                              try {
                                        const response = await fetch(`${config.basePath}/api/matches/${config.matchId}/video-progress`);
                                        if (!response.ok) {
                                                  return;
                                        }

                                        const data = await response.json();
                                        updateDownloadProgress(data);

                                        if (data.status === 'completed' || data.status === 'failed') {
                                                  clearInterval(pollInterval);
                                                  pollInterval = null;
                                        }
                              } catch (error) {
                                        console.error('Poll error:', error);
                              }
                    }, config.pollInterval || 2000);
          }

          function updateDownloadProgress(data) {
                    const progressBar = document.getElementById('veoInlineProgressBar');
                    const progressText = document.getElementById('veoInlineProgressText');
                    const statusText = document.getElementById('veoInlineStatusText');
                    const statusBadge = document.getElementById('veoInlineStatusBadge');
                    const summary = document.getElementById('veoInlineSummary');
                    const errorDiv = document.getElementById('veoInlineError');

                    if (progressBar) {
                              progressBar.style.width = (data.progress || 0) + '%';
                    }
                    if (progressText) {
                              progressText.textContent = (data.progress || 0) + '%';
                    }
                    if (statusText) {
                              statusText.textContent = data.status || 'Unknown';
                    }
                    if (summary) {
                              summary.textContent = data.status === 'completed' ? 'Download complete' :
                                        data.status === 'failed' ? 'Download failed' :
                                                  'Downloading...';
                    }

                    if (statusBadge) {
                              statusBadge.className = 'inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold text-slate-300 bg-slate-800';
                              if (data.status === 'completed') {
                                        statusBadge.classList.add('bg-emerald-600', 'text-emerald-50');
                                        statusBadge.textContent = 'Completed';
                              } else if (data.status === 'failed') {
                                        statusBadge.classList.add('bg-rose-600', 'text-rose-50');
                                        statusBadge.textContent = 'Failed';
                              } else if (data.status === 'downloading') {
                                        statusBadge.classList.add('bg-blue-600', 'text-white');
                                        statusBadge.textContent = 'Downloading';
                              } else {
                                        statusBadge.classList.add('bg-slate-800', 'text-slate-300');
                                        statusBadge.textContent = 'Pending';
                              }
                    }

                    if (errorDiv) {
                              errorDiv.textContent = data.error || '';
                              errorDiv.classList.toggle(hiddenClass, !data.error);
                    }

                    if (veoDownloadPanel) {
                              const showPanel = data.status && data.status !== 'completed';
                              veoDownloadPanel.classList.toggle(hiddenClass, !showPanel);
                    }
          }

          // Start polling if needed
          if (config.initialDownloadStatus &&
                    config.initialDownloadStatus !== 'completed' &&
                    config.initialDownloadStatus !== 'failed') {
                    startPolling();
          }

          // Initialize
          updateVideoMode();
})();
