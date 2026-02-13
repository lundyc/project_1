(() => {
  const playbackRates = [0.5, 1, 2, 4, 8];

  function initDeskControls() {
    const video = document.getElementById('deskVideoPlayer');
    const playPauseBtn = document.getElementById('deskPlayPause');
    const rewindBtn = document.getElementById('deskRewind');
    const forwardBtn = document.getElementById('deskForward');
    const volumeBtn = document.getElementById('deskVolumeButton');
    const volumeSlider = document.getElementById('deskVolumeSlider');
    const muteBtn = document.getElementById('deskMute');
    const muteIcon = muteBtn ? muteBtn.querySelector('i') : null;
    const fullscreenBtn = document.getElementById('deskFullscreen');
    const speedToggle = document.getElementById('deskSpeedToggle');
    const speedOptions = document.getElementById('deskSpeedOptions');
    const speedSelector = speedToggle ? speedToggle.closest('.speed-selector') : null;
    const playPauseIcon = playPauseBtn ? playPauseBtn.querySelector('i') : null;
    const fullscreenIcon = fullscreenBtn ? fullscreenBtn.querySelector('i') : null;
    const detachBtn = document.getElementById('deskDetachVideo');
    const timelineTrack = document.getElementById('deskTimelineTrack');
    const timelineBuffered = document.getElementById('deskTimelineBuffered');
    const timelineProgress = document.getElementById('deskTimelineProgress');
    const timelineWrapper = document.getElementById('deskTimeline');
    const timelineHoverBall = document.querySelector('[data-video-timeline-ball]');
    const timelineTooltip = (() => {
      if (!timelineTrack) {
        return null;
      }
      const existing = timelineTrack.querySelector('.desk-timeline-tooltip');
      if (existing) {
        return existing;
      }
      const tooltip = document.createElement('div');
      tooltip.className = 'desk-timeline-tooltip';
      tooltip.setAttribute('aria-hidden', 'true');
      timelineTrack.appendChild(tooltip);
      return tooltip;
    })();
    const timeDisplay = document.getElementById('deskTimeDisplay');
    const scorebugTime = document.getElementById('deskScorebugTime');
    const scorebugHome = document.getElementById('deskScorebugHome');
    const scorebugAway = document.getElementById('deskScorebugAway');
    // Removed deskCornerTimeDisplay reference
    const controls = document.getElementById('deskControls');
    const drawingToolbarShell = document.querySelector('[data-drawing-toolbar]');
    const videoTransformLayer = video ? video.closest('.video-transform-layer') : null;
    const playPauseFeedback = document.querySelector('[data-video-play-overlay]');
    const playPauseFeedbackIcon = playPauseFeedback ? playPauseFeedback.querySelector('i') : null;
    const seekFeedback = document.querySelector('[data-video-seek-overlay]');
    const sessionStatusEl = document.getElementById(window.DeskSessionBootstrap?.ui?.statusElementId || 'deskSessionStatus');
    const sessionOwnerEl = document.getElementById(window.DeskSessionBootstrap?.ui?.ownerElementId || 'deskControlOwner');
    const takeControlBtn = document.getElementById(window.DeskSessionBootstrap?.ui?.takeControlButtonId || 'deskTakeControl');
    const getActiveTool = () => window.DeskDrawingState?.activeTool ?? null;

    if (
      !video ||
      !playPauseBtn ||
      !rewindBtn ||
      !forwardBtn ||
      !fullscreenBtn ||
      !speedToggle ||
      !speedOptions ||
      !playPauseIcon ||
      !fullscreenIcon ||
      !volumeBtn ||
      !volumeSlider
    ) {
      return;
    }
    // Volume button/slider logic
    volumeBtn.addEventListener('click', () => {
      volumeSlider.style.display = volumeSlider.style.display === 'none' ? 'inline-block' : 'none';
    });
    volumeSlider.addEventListener('input', (e) => {
      video.volume = parseFloat(e.target.value);
      updateVolumeIcon();
    });
    function updateVolumeIcon() {
      const icon = volumeBtn.querySelector('i');
      if (!icon) return;
      if (video.volume === 0) {
        icon.className = 'fa-solid fa-volume-off';
      } else if (video.volume < 0.5) {
        icon.className = 'fa-solid fa-volume-low';
      } else {
        icon.className = 'fa-solid fa-volume-high';
      }
    }
    video.addEventListener('volumechange', updateVolumeIcon);
    updateVolumeIcon();

    const IDLE_HIDE_DELAY = 2000;
    let hideTimeoutId = null;
    let allowIdleHide = true;

    const FEEDBACK_DURATION = 420;
    let playPauseFeedbackTimer = null;
    let seekFeedbackTimer = null;

    const DETACH_WINDOW_NAME = 'matchDeskDetachedVideo';
    let detachedWindow = null;
    let detachWindowUnloadHandler = null;
    let detachWindowFullscreenHandler = null;
    let detachMonitorId = null;
    let isVideoDetached = false;

    let session = window.DeskSession || null;
    let sessionReady = false;
    let controlsDisabled = true;
    let lastKnownOwnerId = null;

    let isScrubbing = false;
    let scrubTime = null;
    let scrubAllowed = false;

    function setAllowIdleHide(val) {
      allowIdleHide = val;
      if (!allowIdleHide) {
        clearHideTimeout();
      }
    }

    function isIdleHideAllowed() {
      return allowIdleHide;
    }

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
      const videoFrame = video.closest('.video-frame');
      if (videoFrame) {
        videoFrame.classList.remove('cursor-hidden');
      }
      // desk-time-display should always be visible, so do not hide it
    };

    const hideControls = () => {
      if (!controls || video.paused || !isIdleHideAllowed() || isVideoDetached) {
        return;
      }
      controls.classList.add('is-hidden');
      setDrawingToolbarVisibility(false);
      const videoFrame = video.closest('.video-frame');
      if (videoFrame) {
        videoFrame.classList.add('cursor-hidden');
      }
      // desk-time-display should always be visible, so do not show/hide it
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
      if (!isIdleHideAllowed() || video.paused || isVideoDetached) {
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
      const videoFrame = video.closest('.video-frame');
      if (!videoFrame) {
        return;
      }
      ['mousemove', 'mousedown', 'keydown', 'touchstart'].forEach((eventName) => {
        videoFrame.addEventListener(eventName, handleInteraction, { passive: true });
      });
    };

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

    const formatTime = (value) => {
      if (!Number.isFinite(value) || value < 0) {
        return '00:00';
      }
      const seconds = Math.floor(value % 60);
      const minutes = Math.floor(value / 60);
      const pad = (num) => String(num).padStart(2, '0');
      return `${pad(minutes)}:${pad(seconds)}`;
    };

    const getSessionState = () => {
      const state = session?.state ?? null;
      return state;
    };

    const updatePlayPauseIcon = () => {
      const state = getSessionState();
      const playing = state ? Boolean(state.playing) : !video.paused;
      playPauseIcon.classList.remove('fa-play', 'fa-pause');
      playPauseIcon.classList.add(playing ? 'fa-pause' : 'fa-play');
      playPauseBtn.setAttribute('aria-label', playing ? 'Pause video' : 'Play video');
      playPauseBtn.setAttribute('aria-pressed', playing ? 'true' : 'false');
    };

    const updateMuteIcon = () => {
      if (!muteBtn || !muteIcon) {
        return;
      }
      muteIcon.classList.remove('fa-volume-high', 'fa-volume-xmark');
      muteIcon.classList.add(video.muted ? 'fa-volume-xmark' : 'fa-volume-high');
      muteBtn.setAttribute('aria-label', video.muted ? 'Unmute video' : 'Mute video');
    };

    const getFullscreenTarget = () => {
      return isVideoDetached ? video : video.closest('.video-frame') || video;
    };

    const updateFullscreenIcon = () => {
      const target = getFullscreenTarget();
      const targetDocument = target?.ownerDocument ?? document;
      const isVideoFullscreen = targetDocument.fullscreenElement === target;
      fullscreenIcon.classList.remove('fa-expand', 'fa-compress');
      fullscreenIcon.classList.add(isVideoFullscreen ? 'fa-compress' : 'fa-expand');
      fullscreenBtn.setAttribute('aria-label', isVideoFullscreen ? 'Exit fullscreen' : 'Enter fullscreen');
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
      ['width', 'height', 'maxWidth', 'maxHeight', 'objectFit'].forEach((prop) => {
        video.style[prop] = '';
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
      if (!isVideoDetached) return;
      cleanupDetachedWindow();
      isVideoDetached = false;
      setAllowIdleHide(true);
      updateDetachButtonState(false);
      updateFullscreenIcon();
      restoreVideoStyles();
      showControls();
      scheduleHide();
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

    const syncDetachedWindow = (state) => {
      if (!detachedWindow || detachedWindow.closed) {
        return;
      }
      try {
        detachedWindow.postMessage({ action: 'setPlaybackRate', value: video.playbackRate }, '*');
        if (state && Number.isFinite(state.time)) {
          detachedWindow.postMessage({ action: 'setCurrentTime', value: state.time }, '*');
        }
        detachedWindow.postMessage({ action: state?.playing ? 'play' : 'pause' }, '*');
        detachedWindow.postMessage({ action: 'setMuted', value: video.muted }, '*');
      } catch (err) {
        /* ignore detached sync failures */
      }
    };

    const detachVideo = () => {
      if (!detachBtn) {
        return;
      }
      if (detachedWindow && !detachedWindow.closed) {
        detachedWindow.focus();
        return;
      }
      const features = detachPopupFeatures();
      detachedWindow = window.open('', DETACH_WINDOW_NAME, features);
      if (!detachedWindow) {
        /* popup may be blocked by the browser */
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
      const popupVideo = video.cloneNode(true);
      popupVideo.id = 'deskVideoPlayerPopup';
      popupVideo.currentTime = video.currentTime;
      popupVideo.muted = video.muted;
      popupVideo.playbackRate = video.playbackRate;
      if (!video.paused) popupVideo.play().catch(() => { });
      container.appendChild(popupVideo);
      doc.body.appendChild(container);
      const bridgeScript = doc.createElement('script');
      bridgeScript.src = '/assets/js/desk-video-popup-bridge.js';
      doc.head.appendChild(bridgeScript);
      applyDetachedStyles();
      isVideoDetached = true;
      updateDetachButtonState(true);
      updateFullscreenIcon();
      setAllowIdleHide(false);
      showControls();
      const handleDetachedWindowBeforeUnload = () => {
        reattachVideo();
      };
      detachWindowUnloadHandler = handleDetachedWindowBeforeUnload;
      detachedWindow.addEventListener('beforeunload', handleDetachedWindowBeforeUnload);
      startDetachedWindowMonitor();
      detachedWindow.focus();
      syncDetachedWindow(getSessionState());
    };

    if (detachBtn) {
      updateDetachButtonState(false);
      detachBtn.addEventListener('click', detachVideo);
    }

    const GOAL_EVENT_KEYS = new Set(['goal', 'goal_for', 'goal_against']);
    const normalizeTeamSide = (side) => (side === 'home' ? 'home' : side === 'away' ? 'away' : null);
    const resolveGoalSide = (ev) => {
      const side = normalizeTeamSide(ev?.team_side);
      if (!side) {
        return null;
      }
      if (ev.event_type_key === 'goal_against') {
        return side === 'home' ? 'away' : 'home';
      }
      return side;
    };

    let scoreCache = {
      eventsRef: null,
      length: 0,
      sorted: [],
      lastIndex: 0,
      lastTime: -1,
      home: 0,
      away: 0,
    };

    const buildScoreCache = () => {
      const source = Array.isArray(window.DeskEvents) ? window.DeskEvents : [];
      scoreCache.eventsRef = source;
      scoreCache.length = source.length;
      scoreCache.sorted = source
        .map((ev) => {
          const second = Number(ev.match_second);
          if (!Number.isFinite(second)) return null;
          const key = ev.event_type_key;
          if (!GOAL_EVENT_KEYS.has(key)) return null;
          const side = resolveGoalSide(ev);
          if (!side) return null;
          return { second, side, id: Number(ev.id) || 0 };
        })
        .filter(Boolean)
        .sort((a, b) => (a.second === b.second ? a.id - b.id : a.second - b.second));
      scoreCache.lastIndex = 0;
      scoreCache.lastTime = -1;
      scoreCache.home = 0;
      scoreCache.away = 0;
    };

    const ensureScoreCache = () => {
      const source = Array.isArray(window.DeskEvents) ? window.DeskEvents : [];
      if (scoreCache.eventsRef !== source || scoreCache.length !== source.length) {
        buildScoreCache();
      }
    };

    const getScoreAtTime = (currentTime) => {
      ensureScoreCache();
      const time = Number.isFinite(currentTime) ? currentTime : 0;
      if (time < scoreCache.lastTime) {
        buildScoreCache();
      }
      const sorted = scoreCache.sorted;
      let i = scoreCache.lastIndex;
      while (i < sorted.length && sorted[i].second <= time) {
        if (sorted[i].side === 'home') {
          scoreCache.home += 1;
        } else if (sorted[i].side === 'away') {
          scoreCache.away += 1;
        }
        i += 1;
      }
      scoreCache.lastIndex = i;
      scoreCache.lastTime = time;
      return { home: scoreCache.home, away: scoreCache.away };
    };

    const getScorebugMinuteText = (currentTime) => {
      const hasCanonicalPeriods = Array.isArray(window.DeskConfig?.periods) && window.DeskConfig.periods.length > 0;
      if (window.DeskPeriodsAvailable === false || !hasCanonicalPeriods) {
        return '—';
      }
      const state = window.DeskPeriodState || null;
      const current = Number.isFinite(currentTime) ? currentTime : 0;
      const pad = (num) => String(num).padStart(2, '0');
      const formatClock = (minute, second) => `${pad(minute)}:${pad(second)}`;

      const resolvePeriod = (key) => {
        const entry = state && state[key] ? state[key] : null;
        const periods = Array.isArray(window.DeskConfig?.periods) ? window.DeskConfig.periods : [];
        const fallback = periods.find((p) => p && p.period_key === key) || null;
        const startSecond = Number(entry?.startMatchSecond ?? fallback?.start_second);
        const endSecond = Number(entry?.endMatchSecond ?? fallback?.end_second);
        const started = Boolean(entry?.started) || Number.isFinite(startSecond);
        const ended = Boolean(entry?.ended) || Number.isFinite(endSecond);
        return {
          entry,
          startSecond: Number.isFinite(startSecond) ? startSecond : null,
          endSecond: Number.isFinite(endSecond) ? endSecond : null,
          started,
          ended,
        };
      };

      const first = resolvePeriod('first_half');
      const second = resolvePeriod('second_half');
      const firstStart = first.startSecond;
      const firstEnd = first.endSecond;
      const secondStart = second.startSecond;
      const secondEnd = second.endSecond;

      if (Number.isFinite(firstStart) && current < firstStart) {
        return formatClock(0, 0);
      }

      const computeClock = (periodData, baseMinute, injuryBase) => {
        const startSecond = Number(periodData.startSecond);
        if (!Number.isFinite(startSecond)) {
          return formatClock(baseMinute, 0);
        }
        const endSecond = Number(periodData.endSecond);
        let effectiveTime = current;
        if (Number.isFinite(endSecond) && current >= endSecond) {
          effectiveTime = endSecond;
        }
        const elapsedSeconds = Math.max(0, effectiveTime - startSecond);
        const totalSeconds = elapsedSeconds + baseMinute * 60;
        const minute = Math.floor(totalSeconds / 60);
        const second = Math.floor(totalSeconds % 60);
        if (totalSeconds > injuryBase * 60) {
          const injurySeconds = Math.max(0, totalSeconds - injuryBase * 60);
          const injuryMinute = Math.max(1, Math.ceil(injurySeconds / 60));
          return `${injuryBase}:00+${injuryMinute}`;
        }
        return formatClock(minute, second);
      };

      if (Number.isFinite(secondStart) && current >= secondStart) {
        if (Number.isFinite(secondEnd) && current >= secondEnd) {
          return 'FULL TIME';
        }
        return computeClock(second, 46, 90);
      }

      if (Number.isFinite(firstStart) && current >= firstStart) {
        if (Number.isFinite(firstEnd) && current >= firstEnd) {
          return 'HALF TIME';
        }
        return computeClock(first, 0, 45);
      }

      return formatClock(0, 0);
    };

    const updateTimeDisplay = (overrideTime) => {
      const currentTime = Number.isFinite(overrideTime) ? overrideTime : video.currentTime;
      const current = formatTime(currentTime);
      const total = Number.isFinite(video.duration) && video.duration > 0 ? formatTime(video.duration) : '00:00';
      if (timeDisplay) {
        // Clear and rebuild safely using textContent
        timeDisplay.innerHTML = '';
        const currentSpan = document.createElement('span');
        currentSpan.textContent = current;
        timeDisplay.appendChild(currentSpan);

        const separatorText = document.createTextNode(' ');
        timeDisplay.appendChild(separatorText);

        const dividerSpan = document.createElement('span');
        dividerSpan.className = 'desk-time-total-block';
        dividerSpan.textContent = `/ ${total}`;
        timeDisplay.appendChild(dividerSpan);
      }
      if (scorebugTime) {
        scorebugTime.textContent = getScorebugMinuteText(currentTime);
      }
      if (scorebugHome && scorebugAway) {
        const score = getScoreAtTime(currentTime);
        scorebugHome.textContent = `${score.home}`;
        scorebugAway.textContent = `${score.away}`;
      }
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

    const showTimelineTooltip = (timeSeconds, percent) => {
      if (!timelineTooltip) {
        return;
      }
      const safeTime = Number.isFinite(timeSeconds) ? timeSeconds : 0;
      const label = formatTime(safeTime);
      timelineTooltip.textContent = label;
      // Debug: add border and log
      timelineTooltip.style.border = '2px solid #f00';
      console.log('[desk] Tooltip show', { timeSeconds, percent, label });
      // Clamp percent to [0, 100] to avoid overflow
      let clampedPercent = percent;
      if (typeof clampedPercent === 'number') {
        clampedPercent = Math.max(0, Math.min(100, clampedPercent));
        timelineTooltip.style.left = `${clampedPercent}%`;
      }
      timelineTooltip.classList.add('is-visible');
      timelineTooltip.setAttribute('aria-hidden', 'false');
    };

    const hideTimelineTooltip = () => {
      if (!timelineTooltip) {
        return;
      }
      timelineTooltip.classList.remove('is-visible');
      timelineTooltip.setAttribute('aria-hidden', 'true');
    };

    const getBufferedEndSeconds = () => {
      if (!video || !video.buffered || video.buffered.length === 0) {
        return 0;
      }
      const currentTime = Number.isFinite(video.currentTime) ? video.currentTime : 0;
      for (let i = 0; i < video.buffered.length; i += 1) {
        const start = video.buffered.start(i);
        const end = video.buffered.end(i);
        if (currentTime >= start && currentTime <= end) {
          return end;
        }
      }
      return video.buffered.end(video.buffered.length - 1);
    };

    const updateTimelineBuffered = () => {
      if (!timelineBuffered) {
        return;
      }
      const duration = Number.isFinite(video.duration) && video.duration > 0 ? video.duration : NaN;
      if (!Number.isFinite(duration) || duration <= 0) {
        timelineBuffered.style.width = '0%';
        return;
      }
      const bufferedEnd = clampToDuration(getBufferedEndSeconds());
      const percent = Math.min(100, Math.max(0, (bufferedEnd / duration) * 100));
      timelineBuffered.style.width = `${percent}%`;
    };

    const getTimelinePercent = (overrideTime) => {
      if (!Number.isFinite(video.duration) || video.duration === 0) {
        return 0;
      }
      const time = Number.isFinite(overrideTime) ? overrideTime : video.currentTime;
      return Math.min(100, Math.max(0, (time / video.duration) * 100));
    };

    const syncTimeline = () => {
      if (isScrubbing) {
        return;
      }
      updateTimeDisplay();
      const percent = getTimelinePercent();
      updateTimelineProgress(percent);
      updateTimelineHoverBall(percent);
      updateTimelineBuffered();
    };

    const previewTimelineAt = (timeSeconds) => {
      const percent = getTimelinePercent(timeSeconds);
      updateTimelineProgress(percent);
      updateTimelineHoverBall(percent);
      updateTimeDisplay(timeSeconds);
    };

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

    const clampToDuration = (timeSeconds) => {
      if (!Number.isFinite(timeSeconds)) {
        return 0;
      }
      if (!Number.isFinite(video.duration) || video.duration <= 0) {
        return Math.max(0, timeSeconds);
      }
      return Math.min(video.duration, Math.max(0, timeSeconds));
    };

    const ensureControl = (reason) => {
      if (!session || session.role !== 'analyst') {
        return Promise.resolve(false);
      }
      return session.requestControl(reason);
    };

    const togglePlayback = () => {
      if (!session || session.role !== 'analyst') {
        return null;
      }
      const shouldPlay = !(session.state?.playing ?? false);
      if (shouldPlay) {
        session.play();
      } else {
        session.pause();
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

    const computeTimelineTimeFromClientX = (clientX) => {
      if (!timelineTrack || !Number.isFinite(video.duration) || video.duration <= 0) {
        return null;
      }
      const rect = timelineTrack.getBoundingClientRect();
      if (!rect.width) {
        return null;
      }
      const offset = Math.min(Math.max(0, clientX - rect.left), rect.width);
      const percent = offset / rect.width;
      return clampToDuration(percent * video.duration);
    };

    const beginScrubPreview = (timeSeconds) => {
      if (!Number.isFinite(timeSeconds)) {
        return;
      }
      scrubTime = timeSeconds;
      previewTimelineAt(timeSeconds);
    };

    const handleTimelinePointerDown = (event) => {
      if (!timelineTrack || controlsDisabled) {
        return;
      }
      event.preventDefault();
      isScrubbing = true;
      scrubAllowed = session?.isOwner?.() ?? false;
      session?.setScrubbing(true);
      ensureControl('scrub').then((ok) => {
        scrubAllowed = ok || session?.isOwner?.() || false;
      });
      const timeSeconds = computeTimelineTimeFromClientX(event.clientX);
      if (Number.isFinite(timeSeconds)) {
        beginScrubPreview(timeSeconds);
      }
      timelineTrack.setPointerCapture?.(event.pointerId);
    };

    const handleTimelinePointerMove = (event) => {
      const timeSeconds = computeTimelineTimeFromClientX(event.clientX);
      if (Number.isFinite(timeSeconds)) {
        const percent = getTimelinePercent(timeSeconds);
        showTimelineTooltip(timeSeconds, percent);
      }
      if (!isScrubbing) {
        return;
      }
      if (Number.isFinite(timeSeconds)) {
        beginScrubPreview(timeSeconds);
      }
    };

    const finishScrub = () => {
      session?.setScrubbing(false);
      if (Number.isFinite(scrubTime)) {
        if (scrubAllowed) {
          session.seek(scrubTime);
        } else if (session?.role === 'analyst') {
          // First click can finish before requestControl resolves; in that case
          // immediately try to acquire control and then apply the seek once.
          session.requestControl('scrub').then((ok) => {
            if (ok || session?.isOwner?.()) {
              session.seek(scrubTime);
            } else {
              session?.applyState(true);
            }
          });
        } else {
          session?.applyState(true);
        }
      } else {
        session?.applyState(true);
      }
      scrubTime = null;
      scrubAllowed = false;
      syncTimeline();
    };

    const handleTimelinePointerUp = (event) => {
      if (!isScrubbing) {
        return;
      }
      isScrubbing = false;
      timelineTrack.releasePointerCapture?.(event.pointerId);
      finishScrub();
    };

    const handleTimelinePointerCancel = () => {
      if (!isScrubbing) {
        return;
      }
      isScrubbing = false;
      finishScrub();
    };

    const handleTimelineMouseMove = (event) => {
      const timeSeconds = computeTimelineTimeFromClientX(event.clientX);
      if (Number.isFinite(timeSeconds)) {
        const percent = getTimelinePercent(timeSeconds);
        showTimelineTooltip(timeSeconds, percent);
      }
    };

    const handleTimelineMouseLeave = () => {
      hideTimelineTooltip();
    };

    const handleKeyboardSeek = (event) => {
      if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') {
        return;
      }
      if (isTypingTarget(event.target)) {
        return;
      }
      if (controlsDisabled) {
        return;
      }
      event.preventDefault();
      event.stopPropagation();
      if (event.key === 'ArrowLeft') {
        session?.skip(-5);
        showSeekFeedback('rewind');
      } else {
        session?.skip(5);
        showSeekFeedback('forward');
      }
    };

    const toggleSpeedOptions = (event) => {
      if (controlsDisabled) {
        return;
      }
      event.stopPropagation();
      const isOpen = speedOptions.classList.toggle('is-open');
      speedToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      speedOptions.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
    };

    const handleSpeedSelect = (rate) => {
      if (controlsDisabled) {
        return;
      }
      session?.setRate(rate);
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

    const setButtonDisabled = (button, disabled) => {
      if (!button) {
        return;
      }
      button.disabled = disabled;
      button.setAttribute('aria-disabled', disabled ? 'true' : 'false');
    };

    const updateControlAvailability = (state) => {
      if (!sessionReady || !session || session.role !== 'analyst') {
        controlsDisabled = true;
      } else {
        controlsDisabled = !session.isOwner();
      }

      document.body.classList.toggle('session-control-disabled', controlsDisabled);
      setButtonDisabled(playPauseBtn, controlsDisabled);
      setButtonDisabled(rewindBtn, controlsDisabled);
      setButtonDisabled(forwardBtn, controlsDisabled);
      setButtonDisabled(speedToggle, controlsDisabled);
      if (timelineWrapper) {
        timelineWrapper.setAttribute('aria-disabled', controlsDisabled ? 'true' : 'false');
      }
      if (timelineTrack) {
        timelineTrack.dataset.sessionDisabled = controlsDisabled ? 'true' : 'false';
      }

      if (takeControlBtn) {
        takeControlBtn.disabled = !sessionReady;
      }

      if (!state) {
        return;
      }

      const ownerId = state.controlOwner?.userId ?? null;
      lastKnownOwnerId = ownerId;

      if (!sessionStatusEl) {
        return;
      }

      if (!sessionReady) {
        sessionStatusEl.textContent = 'Connecting session…';
      } else if (session.isOwner()) {
        sessionStatusEl.textContent = state.playing ? 'You control playback · Playing' : 'You control playback · Paused';
        if (takeControlBtn) {
          takeControlBtn.textContent = 'Release control';
        }
      } else if (state.controlOwner && state.controlOwner.userName) {
        sessionStatusEl.textContent = `Controlled by ${state.controlOwner.userName}`;
        if (takeControlBtn) {
          takeControlBtn.textContent = 'Take control';
        }
      } else {
        sessionStatusEl.textContent = state.playing ? 'Control available · Playing' : 'Control available · Paused';
        if (takeControlBtn) {
          takeControlBtn.textContent = 'Take control';
        }
      }

      if (sessionOwnerEl) {
        const ownerText = state.controlOwner?.userName ? `Controlled by ${state.controlOwner.userName}` : '';
        sessionOwnerEl.textContent = session.isOwner() ? '' : ownerText;
      }
    };

    if (takeControlBtn) {
      takeControlBtn.addEventListener('click', () => {
        if (!session || session.role !== 'analyst') {
          return;
        }
        if (session.isOwner()) {
          session.releaseControl();
          return;
        }
        session.requestControl('button');
      });
    }

    renderSpeedOptions();

    playPauseBtn.addEventListener('click', () => {
      if (controlsDisabled) {
        return;
      }
      togglePlayback();
    });

    rewindBtn.addEventListener('click', () => {
      if (controlsDisabled) {
        return;
      }
      session?.skip(-5);
      showSeekFeedback('rewind');
    });

    forwardBtn.addEventListener('click', () => {
      if (controlsDisabled) {
        return;
      }
      session?.skip(5);
      showSeekFeedback('forward');
    });

    if (muteBtn) {
      muteBtn.addEventListener('click', () => {
        video.muted = !video.muted;
        updateMuteIcon();
        syncDetachedWindow(getSessionState());
      });
    }

    fullscreenBtn.addEventListener('click', () => {
      const target = getFullscreenTarget();
      if (!target) {
        return;
      }
      const targetDocument = target.ownerDocument || document;
      if (targetDocument.fullscreenElement === target) {
        targetDocument.exitFullscreen?.();
      } else if (target.requestFullscreen) {
        target.requestFullscreen().catch(() => {
          /* ignore fullscreen failures */
        });
      }
    });

    document.addEventListener('fullscreenchange', updateFullscreenIcon);
    updateFullscreenIcon();

    video.addEventListener('timeupdate', syncTimeline);
    video.addEventListener('durationchange', syncTimeline);
    video.addEventListener('loadedmetadata', syncTimeline);
    // Also update the persistent time display
    video.addEventListener('timeupdate', () => updateTimeDisplay());
    video.addEventListener('durationchange', () => updateTimeDisplay());
    video.addEventListener('loadedmetadata', () => updateTimeDisplay());
    document.addEventListener('desk:periodstate', () => updateTimeDisplay());
    document.addEventListener('desk:events', () => updateTimeDisplay());
    video.addEventListener('progress', updateTimelineBuffered);
    video.addEventListener('canplay', updateTimelineBuffered);
    video.addEventListener('play', () => {
      updatePlayPauseIcon();
      showControls();
      scheduleHide();
    });
    video.addEventListener('pause', () => {
      updatePlayPauseIcon();
      showControls();
      clearHideTimeout();
    });
    video.addEventListener('ended', () => {
      updatePlayPauseIcon();
      showControls();
      clearHideTimeout();
    });

    updateMuteIcon();
    updatePlayPauseIcon();
    syncTimeline();

    speedToggle.setAttribute('aria-expanded', 'false');
    speedOptions.setAttribute('aria-hidden', 'true');
    speedToggle.addEventListener('click', toggleSpeedOptions);

    speedOptions.addEventListener('click', (event) => {
      event.stopPropagation();
    });

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
      if ((event.code === 'Space' || event.key === ' ') && !isTypingTarget(event.target)) {
        if (controlsDisabled) {
          return;
        }
        event.preventDefault();
        const feedbackState = togglePlayback();
        if (feedbackState) {
          showPlayPauseFeedback(feedbackState);
        }
      }
    });

    document.addEventListener('keydown', handleKeyboardSeek, true);

    if (timelineTrack) {
      timelineTrack.addEventListener('pointerdown', handleTimelinePointerDown);
      timelineTrack.addEventListener('pointermove', handleTimelinePointerMove);
      timelineTrack.addEventListener('pointerup', handleTimelinePointerUp);
      timelineTrack.addEventListener('pointerleave', handleTimelinePointerUp);
      timelineTrack.addEventListener('pointerleave', hideTimelineTooltip);
      timelineTrack.addEventListener('pointercancel', handleTimelinePointerCancel);
      timelineTrack.addEventListener('pointercancel', hideTimelineTooltip);
      timelineTrack.addEventListener('mouseenter', handleTimelineMouseMove);
      timelineTrack.addEventListener('mousemove', handleTimelineMouseMove);
      timelineTrack.addEventListener('mouseleave', handleTimelineMouseLeave);
    }

    if (timelineWrapper) {
      timelineWrapper.addEventListener('keydown', (event) => {
        if (!Number.isFinite(video.duration) || video.duration === 0 || controlsDisabled) {
          return;
        }
        const step = Math.max(1, video.duration / 20);
        const current = session?.getCurrentTime?.() ?? video.currentTime;
        if (event.key === 'ArrowRight' || event.key === 'ArrowUp') {
          event.preventDefault();
          session?.seek(clampToDuration(current + step));
        } else if (event.key === 'ArrowLeft' || event.key === 'ArrowDown') {
          event.preventDefault();
          session?.seek(clampToDuration(current - step));
        } else if (event.key === 'Home') {
          event.preventDefault();
          session?.seek(0);
        } else if (event.key === 'End') {
          event.preventDefault();
          session?.seek(video.duration);
        }
      });
    }

    registerInteractionListeners();
    if (videoTransformLayer) {
      videoTransformLayer.addEventListener('pointerdown', handleVideoSurfaceToggle, { passive: false });
    }

    const handleSessionReady = (event) => {
      session = event.detail || window.DeskSession;
      sessionReady = Boolean(session);
      updateControlAvailability(session?.state || null);
      updatePlayPauseIcon();
    };

    const handleSessionState = (event) => {
      if (!session) {
        session = window.DeskSession;
      }
      const detail = event.detail || null;
      let state = session?.state || null;
      if (detail) {
        state = state ? { ...state, ...detail } : detail;
      }
      if (state?.rate && speedToggle) {
        const speedLabel = speedToggle.querySelector('.speed-label');
        if (speedLabel) {
          speedLabel.textContent = `${state.rate}×`;
        }
      }
      updateControlAvailability(state);
      updatePlayPauseIcon();
      syncDetachedWindow(state);
    };

    window.addEventListener('desk:session-ready', handleSessionReady);
    window.addEventListener('desk:session-state', handleSessionState);
    window.addEventListener('desk:control-denied', handleSessionState);
    window.addEventListener('desk:control-owner', handleSessionState);

    // If the session was already initialized before this script ran, sync immediately.
    if (window.DeskSession?.ready) {
      window.DeskSession.ready.then((readySession) => {
        handleSessionReady({ detail: readySession });
        handleSessionState({ detail: readySession.state });
      });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDeskControls);
  } else {
    initDeskControls();
  }
})();
