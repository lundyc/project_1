(function () {
  const playbackRates = [0.5, 1, 2, 4, 8];

  function initDeskControls() {
    const video = document.getElementById('deskVideoPlayer');
    const playPauseBtn = document.getElementById('deskPlayPause');
    const rewindBtn = document.getElementById('deskRewind');
    const forwardBtn = document.getElementById('deskForward');
    const muteBtn = document.getElementById('deskMuteToggle');
    const fullscreenBtn = document.getElementById('deskFullscreen');
    const speedToggle = document.getElementById('deskSpeedToggle');
    const speedOptions = document.getElementById('deskSpeedOptions');
    const speedSelector = speedToggle ? speedToggle.closest('.speed-selector') : null;
    const playPauseIcon = playPauseBtn ? playPauseBtn.querySelector('i') : null;
    const muteIcon = muteBtn ? muteBtn.querySelector('i') : null;
    const fullscreenIcon = fullscreenBtn ? fullscreenBtn.querySelector('i') : null;
    const detachBtn = document.getElementById('deskDetachVideo');
    const speedLabel = speedToggle ? speedToggle.querySelector('.speed-label') : null;
    const videoFrame = video ? video.closest('.video-frame') : null;
    const timelineTrack = document.getElementById('deskTimelineTrack');
    const timelineProgress = document.getElementById('deskTimelineProgress');
    const timelineWrapper = document.getElementById('deskTimeline');
    const timelineHoverBall = document.querySelector('[data-video-timeline-ball]');
    const timeDisplay = document.getElementById('deskTimeDisplay');
    const controls = document.getElementById('deskControls');
    const drawingToolbarShell = document.querySelector('[data-drawing-toolbar]');
    const videoTransformLayer = video ? video.closest('.video-transform-layer') : null;
    const playPauseFeedback = document.querySelector('[data-video-play-overlay]');
    const playPauseFeedbackIcon = playPauseFeedback ? playPauseFeedback.querySelector('i') : null;
    const seekFeedback = document.querySelector('[data-video-seek-overlay]');
    const getActiveTool = () => window.DeskDrawingState?.activeTool ?? null;

    const IDLE_HIDE_DELAY = 2000;
    let hideTimeoutId = null;

    const setDrawingToolbarVisibility = (visible) => {
      if (!drawingToolbarShell) {
        return;
      }
      drawingToolbarShell.classList.toggle('is-hidden', !visible);
    };

    const showControls = () => {
      if (!controls) {
        return;
      }
      controls.classList.remove('is-hidden');
      setDrawingToolbarVisibility(true);
      if (videoFrame) {
        videoFrame.classList.remove('cursor-hidden');
      }
    };

    const hideControls = () => {
      if (!controls || video.paused) {
        return;
      }
      controls.classList.add('is-hidden');
      setDrawingToolbarVisibility(false);
      if (videoFrame) {
        videoFrame.classList.add('cursor-hidden');
      }
    };

    const clearHideTimeout = () => {
      if (!hideTimeoutId) {
        return;
      }
      clearTimeout(hideTimeoutId);
      hideTimeoutId = null;
    };

    const scheduleHide = () => {
      clearHideTimeout();
      if (video.paused) {
        return;
      }
      hideTimeoutId = setTimeout(() => {
        hideControls();
        hideTimeoutId = null;
      }, IDLE_HIDE_DELAY);
    };

    const handleInteraction = () => {
      showControls();
      scheduleHide();
    };

    const registerInteractionListeners = () => {
      if (!videoFrame) {
        return;
      }
      ['mousemove', 'mousedown', 'keydown', 'touchstart'].forEach((eventName) => {
        videoFrame.addEventListener(eventName, handleInteraction, { passive: true });
      });
    };

    const FEEDBACK_DURATION = 420;
    let playPauseFeedbackTimer = null;
    let seekFeedbackTimer = null;

    const showPlayPauseFeedback = (state) => {
      if (!playPauseFeedback || !playPauseFeedbackIcon) {
        return;
      }
      playPauseFeedbackIcon.classList.remove('fa-play', 'fa-pause');
      playPauseFeedbackIcon.classList.add(state === 'pause' ? 'fa-pause' : 'fa-play');
      playPauseFeedback.classList.add('is-visible');
      playPauseFeedback.setAttribute('aria-hidden', 'false');
      clearTimeout(playPauseFeedbackTimer);
      playPauseFeedbackTimer = setTimeout(() => {
        playPauseFeedback.classList.remove('is-visible');
        playPauseFeedback.setAttribute('aria-hidden', 'true');
      }, FEEDBACK_DURATION);
    };

    const showSeekFeedback = (direction) => {
      if (!seekFeedback) {
        return;
      }
      seekFeedback.classList.remove('is-rewind', 'is-forward');
      const directionClass = direction === 'forward' ? 'is-forward' : 'is-rewind';
      seekFeedback.classList.add(directionClass, 'is-visible');
      seekFeedback.setAttribute('aria-hidden', 'false');
      clearTimeout(seekFeedbackTimer);
      seekFeedbackTimer = setTimeout(() => {
        seekFeedback.classList.remove('is-visible', 'is-rewind', 'is-forward');
        seekFeedback.setAttribute('aria-hidden', 'true');
      }, FEEDBACK_DURATION);
    };

    const handleVideoPlay = () => {
      showControls();
      scheduleHide();
    };

    const handleVideoPause = () => {
      showControls();
      clearHideTimeout();
    };

    const togglePlayback = () => {
      if (!video) {
        return null;
      }
      const shouldPlay = video.paused;
      if (shouldPlay) {
        video.play();
      } else {
        video.pause();
      }
      return shouldPlay ? 'pause' : 'play';
    };

    const handleVideoSurfaceToggle = (event) => {
      if (!video || getActiveTool()) {
        return;
      }
      const feedbackState = togglePlayback();
      if (feedbackState) {
        showPlayPauseFeedback(feedbackState);
      }
      event.preventDefault();
    };

    if (
      !video ||
      !playPauseBtn ||
      !rewindBtn ||
      !forwardBtn ||
      !muteBtn ||
      !fullscreenBtn ||
      !speedToggle ||
      !speedOptions ||
      !playPauseIcon ||
      !muteIcon ||
      !fullscreenIcon ||
      !speedLabel
    ) {
      return;
    }

    const originalVideoParent = video.parentNode;
    const originalVideoNextSibling = video.nextSibling;
    const styleBackup = {
      width: video.style.width,
      height: video.style.height,
      maxWidth: video.style.maxWidth,
      maxHeight: video.style.maxHeight,
      objectFit: video.style.objectFit,
    };
    const DETACH_WINDOW_NAME = 'matchDeskDetachedVideo';
    let detachedWindow = null;
    let detachWindowUnloadHandler = null;
    let detachWindowFullscreenHandler = null;
    let detachMonitorId = null;
    let isVideoDetached = false;

    video.playbackRate = 1;
    speedToggle.setAttribute('aria-expanded', 'false');
    speedOptions.setAttribute('aria-hidden', 'true');
    speedLabel.textContent = '1×';

    const updatePlayPauseIcon = () => {
      playPauseIcon.classList.remove('fa-play', 'fa-pause');
      playPauseIcon.classList.add(video.paused ? 'fa-play' : 'fa-pause');
      playPauseBtn.setAttribute('aria-label', video.paused ? 'Play video' : 'Pause video');
      playPauseBtn.setAttribute('aria-pressed', video.paused ? 'false' : 'true');
    };

    const updateMuteIcon = () => {
      muteIcon.classList.remove('fa-volume-high', 'fa-volume-xmark');
      muteIcon.classList.add(video.muted ? 'fa-volume-xmark' : 'fa-volume-high');
      muteBtn.setAttribute('aria-label', video.muted ? 'Unmute video' : 'Mute video');
    };

    const getFullscreenTarget = () => {
      return isVideoDetached ? video : videoFrame || video;
    };

    const updateDetachButtonState = (detached) => {
      if (!detachBtn) {
        return;
      }
      detachBtn.setAttribute('aria-pressed', detached ? 'true' : 'false');
      detachBtn.classList.toggle('is-active', detached);
    };

    const disableDetachButton = () => {
      if (!detachBtn) {
        return;
      }
      detachBtn.disabled = true;
      detachBtn.setAttribute('aria-disabled', 'true');
      detachBtn.title = 'Detach video (popup blocked)';
    };

    const applyDetachedStyles = () => {
      video.setAttribute('data-video-detached', 'true');
      video.style.width = '100%';
      video.style.height = '100%';
      video.style.maxWidth = '100%';
      video.style.maxHeight = '100%';
      video.style.objectFit = 'contain';
    };

    const restoreVideoStyles = () => {
      Object.entries(styleBackup).forEach(([prop, value]) => {
        video.style[prop] = value || '';
      });
      video.removeAttribute('data-video-detached');
    };

    const cleanupDetachedWindow = () => {
      if (detachedWindow && detachWindowUnloadHandler) {
        detachedWindow.removeEventListener('beforeunload', detachWindowUnloadHandler);
        detachWindowUnloadHandler = null;
      }
      if (detachedWindow && detachWindowFullscreenHandler) {
        detachedWindow.document.removeEventListener('fullscreenchange', detachWindowFullscreenHandler);
        detachWindowFullscreenHandler = null;
      }
      if (detachMonitorId) {
        clearInterval(detachMonitorId);
        detachMonitorId = null;
      }
      detachedWindow = null;
    };

    const reattachVideo = () => {
      if (!isVideoDetached || !originalVideoParent) {
        return;
      }
      // Reattach before the popup finishes unloading so the desk layout stays intact.
      cleanupDetachedWindow();
      if (video.parentNode !== originalVideoParent) {
        originalVideoParent.insertBefore(video, originalVideoNextSibling);
      }
      restoreVideoStyles();
      isVideoDetached = false;
      updateDetachButtonState(false);
      updateFullscreenIcon();
    };

    const startDetachedWindowMonitor = () => {
      if (!detachedWindow || detachMonitorId) {
        return;
      }
      detachMonitorId = setInterval(() => {
        if (!detachedWindow || !isVideoDetached) {
          return;
        }
        if (detachedWindow.closed) {
          reattachVideo();
        }
      }, 400);
    };

    const detachPopupFeatures = () => {
      const width = 1360;
      const height = 768;
      const left = Math.max(0, window.screenX + Math.floor((window.outerWidth - width) / 2));
      const top = Math.max(0, window.screenY + Math.floor((window.outerHeight - height) / 2));
      return `width=${width},height=${height},left=${left},top=${top},menubar=0,toolbar=0,location=0,status=0,resizable=1,scrollbars=0`;
    };

    const detachVideo = () => {
      if (!detachBtn) {
        return;
      }
      if (detachedWindow && !detachedWindow.closed) {
        detachedWindow.focus();
        return;
      }
      // Move the single video node into a popup so playback state stays intact while controls remain docked.
      const features = detachPopupFeatures();
      detachedWindow = window.open('', DETACH_WINDOW_NAME, features);
      if (!detachedWindow) {
        console.warn('Match Desk video detach popup was blocked by the browser.');
        disableDetachButton();
        return;
      }
      const doc = detachedWindow.document;
      detachWindowFullscreenHandler = () => {
        updateFullscreenIcon();
      };
      doc.addEventListener('fullscreenchange', detachWindowFullscreenHandler);
      doc.title = 'Match Desk Video';
      doc.documentElement.style.height = '100%';
      doc.documentElement.style.margin = '0';
      doc.documentElement.style.backgroundColor = '#000';
      doc.body.style.margin = '0';
      doc.body.style.height = '100vh';
      doc.body.style.display = 'flex';
      doc.body.style.alignItems = 'center';
      doc.body.style.justifyContent = 'center';
      doc.body.style.backgroundColor = '#000';
      const container = doc.createElement('div');
      container.style.width = '100%';
      container.style.height = '100%';
      container.style.display = 'flex';
      container.style.alignItems = 'center';
      container.style.justifyContent = 'center';
      container.appendChild(video);
      doc.body.appendChild(container);
      applyDetachedStyles();
      isVideoDetached = true;
      updateDetachButtonState(true);
      updateFullscreenIcon();
      const handleDetachedWindowBeforeUnload = () => {
        reattachVideo();
      };
      detachWindowUnloadHandler = handleDetachedWindowBeforeUnload;
      detachedWindow.addEventListener('beforeunload', handleDetachedWindowBeforeUnload);
      startDetachedWindowMonitor();
      detachedWindow.focus();
    };

    if (detachBtn) {
      updateDetachButtonState(false);
      detachBtn.addEventListener('click', detachVideo);
    }

    const updateFullscreenIcon = () => {
      const target = getFullscreenTarget();
      const targetDocument = target?.ownerDocument ?? document;
      const isVideoFullscreen = targetDocument.fullscreenElement === target;
      fullscreenIcon.classList.remove('fa-expand', 'fa-compress');
      fullscreenIcon.classList.add(isVideoFullscreen ? 'fa-compress' : 'fa-expand');
      fullscreenBtn.setAttribute('aria-label', isVideoFullscreen ? 'Exit fullscreen' : 'Enter fullscreen');
    };

    const formatTime = (value) => {
      if (!Number.isFinite(value) || value < 0) {
        return '00:00';
      }
      const seconds = Math.floor(value % 60);
      const minutes = Math.floor(value / 60);
      const pad = (num) => String(num).padStart(2, '0');
      return `${pad(minutes)}:${pad(seconds)}`;
    };

    const updateTimeDisplay = () => {
      if (!timeDisplay) {
        return;
      }
      const current = formatTime(video.currentTime);
      const total = Number.isFinite(video.duration) && video.duration > 0 ? formatTime(video.duration) : '00:00';
      timeDisplay.innerHTML = `${current} <span class="desk-time-total-block">/ ${total}</span>`;
    };

    const getTimelinePercent = () => {
      if (!Number.isFinite(video.duration) || video.duration === 0) {
        return 0;
      }
      return Math.min(100, Math.max(0, (video.currentTime / video.duration) * 100));
    };

    const updateTimelineProgress = (percent) => {
      if (!timelineProgress) {
        return;
      }
      timelineProgress.style.width = `${percent}%`;
    };

    const updateTimelineHoverBall = (percent) => {
      if (!timelineHoverBall) {
        return;
      }
      timelineHoverBall.style.left = `${percent}%`;
    };

    const syncTimeline = () => {
      updateTimeDisplay();
      const percent = getTimelinePercent();
      updateTimelineProgress(percent);
      updateTimelineHoverBall(percent);
    };

    const skip = (seconds) => {
      const maxTime = Number.isFinite(video.duration) ? video.duration : Number.POSITIVE_INFINITY;
      video.currentTime = Math.min(Math.max(0, video.currentTime + seconds), maxTime);
    };

    const seekFromPosition = (clientX) => {
      if (!timelineTrack || !Number.isFinite(video.duration) || video.duration === 0) {
        return;
      }
      const rect = timelineTrack.getBoundingClientRect();
      if (!rect.width) {
        return;
      }
      const offset = Math.min(Math.max(0, clientX - rect.left), rect.width);
      const percent = offset / rect.width;
      video.currentTime = percent * video.duration;
      syncTimeline();
    };

    let isScrubbing = false;
    const handleTimelinePointerDown = (event) => {
      if (!timelineTrack) {
        return;
      }
      event.preventDefault();
      isScrubbing = true;
      seekFromPosition(event.clientX);
      timelineTrack.setPointerCapture?.(event.pointerId);
    };

    const handleTimelinePointerMove = (event) => {
      if (!isScrubbing) {
        return;
      }
      seekFromPosition(event.clientX);
    };

    const handleTimelinePointerUp = (event) => {
      if (!isScrubbing) {
        return;
      }
      isScrubbing = false;
      timelineTrack.releasePointerCapture?.(event.pointerId);
    };

    const handleTimelinePointerCancel = () => {
      isScrubbing = false;
    };

    const handleSpeedSelect = (rate) => {
      video.playbackRate = rate;
      speedLabel.textContent = `${rate}×`;
      speedOptions.classList.remove('is-open');
      speedToggle.setAttribute('aria-expanded', 'false');
      speedOptions.setAttribute('aria-hidden', 'true');
      speedToggle.focus();
    };

    const renderSpeedOptions = () => {
      playbackRates.forEach((rate) => {
        const item = document.createElement('li');
        item.className = 'speed-option';
        item.setAttribute('role', 'menuitem');
        item.tabIndex = 0;
        item.dataset.rate = rate;
        item.textContent = `${rate}×`;
        item.addEventListener('click', () => handleSpeedSelect(rate));
        item.addEventListener('keydown', (event) => {
          if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            handleSpeedSelect(rate);
          }
        });
        speedOptions.appendChild(item);
      });
    };

    renderSpeedOptions();

    playPauseBtn.addEventListener('click', () => {
      togglePlayback();
    });

    video.addEventListener('play', updatePlayPauseIcon);
    video.addEventListener('pause', updatePlayPauseIcon);
    updatePlayPauseIcon();
    video.addEventListener('timeupdate', syncTimeline);
    video.addEventListener('durationchange', syncTimeline);
    video.addEventListener('loadedmetadata', syncTimeline);

    rewindBtn.addEventListener('click', () => skip(-5));
    forwardBtn.addEventListener('click', () => skip(5));

    muteBtn.addEventListener('click', () => {
      video.muted = !video.muted;
      updateMuteIcon();
    });
    updateMuteIcon();

    fullscreenBtn.addEventListener('click', () => {
      const target = getFullscreenTarget();
      if (!target) {
        return;
      }
      const targetDocument = target.ownerDocument || document;
      if (targetDocument.fullscreenElement === target) {
        targetDocument.exitFullscreen();
      } else if (target.requestFullscreen) {
        target.requestFullscreen().catch(() => {
          /* ignore fullscreen failures */
        });
      }
    });

    document.addEventListener('fullscreenchange', updateFullscreenIcon);
    updateFullscreenIcon();
    syncTimeline();

    const toggleSpeedOptions = (event) => {
      event.stopPropagation();
      const isOpen = speedOptions.classList.toggle('is-open');
      speedToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      speedOptions.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
    };

    speedToggle.addEventListener('click', toggleSpeedOptions);

    speedOptions.addEventListener('click', (event) => {
      event.stopPropagation();
    });

    registerInteractionListeners();
    if (videoTransformLayer) {
      videoTransformLayer.addEventListener('pointerdown', handleVideoSurfaceToggle, { passive: false });
    }
    video.addEventListener('play', handleVideoPlay);
    video.addEventListener('pause', handleVideoPause);
    video.addEventListener('ended', handleVideoPause);

    document.addEventListener('click', (event) => {
      if (!speedSelector || speedSelector.contains(event.target)) {
        return;
      }
      speedOptions.classList.remove('is-open');
      speedToggle.setAttribute('aria-expanded', 'false');
      speedOptions.setAttribute('aria-hidden', 'true');
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        speedOptions.classList.remove('is-open');
        speedToggle.setAttribute('aria-expanded', 'false');
        speedOptions.setAttribute('aria-hidden', 'true');
      }
    });

    const isTypingTarget = (target) => {
      if (!target || !(target instanceof Element)) {
        return false;
      }
      if (target.isContentEditable) {
        return true;
      }
      const tag = target.tagName?.toLowerCase();
      if (tag && ['input', 'textarea', 'select'].includes(tag)) {
        return true;
      }
      return !!target.closest('[contenteditable="true"], [contenteditable=""]');
    };

    const handleKeyboardSeek = (event) => {
      if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') {
        return;
      }
      if (isTypingTarget(event.target)) {
        return;
      }
      event.preventDefault();
      event.stopPropagation(); // Keep timeline listeners from reacting to the same arrow press.
      if (event.key === 'ArrowLeft') {
        skip(-5);
        showSeekFeedback('rewind');
      } else {
        skip(5);
        showSeekFeedback('forward');
      }
    };

    document.addEventListener('keydown', handleKeyboardSeek, true);

    if (timelineTrack) {
      timelineTrack.addEventListener('pointerdown', handleTimelinePointerDown);
      timelineTrack.addEventListener('pointermove', handleTimelinePointerMove);
      timelineTrack.addEventListener('pointerup', handleTimelinePointerUp);
      timelineTrack.addEventListener('pointerleave', handleTimelinePointerUp);
      timelineTrack.addEventListener('pointercancel', handleTimelinePointerCancel);
    }

    if (timelineWrapper) {
      timelineWrapper.addEventListener('keydown', (event) => {
        if (!Number.isFinite(video.duration) || video.duration === 0) {
          return;
        }
        const step = Math.max(1, video.duration / 20);
        if (event.key === 'ArrowRight' || event.key === 'ArrowUp') {
          event.preventDefault();
          video.currentTime = Math.min(video.duration, video.currentTime + step);
        } else if (event.key === 'ArrowLeft' || event.key === 'ArrowDown') {
          event.preventDefault();
          video.currentTime = Math.max(0, video.currentTime - step);
        } else if (event.key === 'Home') {
          event.preventDefault();
          video.currentTime = 0;
        } else if (event.key === 'End') {
          event.preventDefault();
          video.currentTime = video.duration;
        }
      });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDeskControls);
  } else {
    initDeskControls();
  }
})();
