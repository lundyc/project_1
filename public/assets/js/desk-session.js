// Phase C: Gate server-driven seeks and playback state on media readiness
// This prevents video preload cancellation and unnecessary seek/pause churn.
(function () {
  const bootstrap = window.DeskSessionBootstrap || {};
  const matchId = Number(bootstrap.matchId || window.DeskConfig?.matchId || 0);
  if (!matchId) {
    return;
  }

  const roleHint = (bootstrap.role || 'analyst').toLowerCase() === 'viewer' ? 'viewer' : 'analyst';
  const videoElementId = bootstrap.videoElementId || 'deskVideoPlayer';

  const video = document.getElementById(videoElementId);
  if (!video) {
    return;
  }

  // --- Phase C: Improve perceived startup ---
  // Add poster, muted, playsinline if missing (for instant UI and mobile compatibility)
  if (!video.hasAttribute('poster')) {
    video.setAttribute('poster', '/assets/img/video-poster.png'); // Use a default poster if available
  }
  if (!video.hasAttribute('muted')) {
    video.setAttribute('muted', '');
    video.muted = true;
  }
  if (!video.hasAttribute('playsinline')) {
    video.setAttribute('playsinline', '');
  }

  // UI should render immediately, even if socket/session is not ready yet
  // (No code change needed here if UI is not blocked by JS)

  // --- Phase C: Prevent preload cancellation ---
  // Only call video.load() once, and never seek before loadedmetadata.
  let preloadStarted = false;
  function ensurePreload() {
    if (!preloadStarted) {
      // Start preload as soon as possible
      video.load();
      preloadStarted = true;
    }
  }
  ensurePreload();

  // Never seek before loadedmetadata; all seeks are now gated by videoReady above.
  // See handleIncomingState and buffering logic for details.

  // --- Phase C: Buffer server session state until video is ready ---
  let videoReady = false;
  let bufferedSessionState = null;
  let bufferedSessionReason = null;

  // Helper: apply buffered state if ready
  function maybeApplyBufferedState() {
    if (videoReady && bufferedSessionState) {
      // Only apply after loadedmetadata (and ideally canplay)
      handleIncomingState(bufferedSessionState, bufferedSessionReason);
      bufferedSessionState = null;
      bufferedSessionReason = null;
    }
  }

  // Forward declarations and definitions of functions that may be called during initialization

  // Will be populated after sessionApi is created
  let handleLocalPlayPause;
  let saveVideoTime;
  let restoreVideoTime;

  const statusElementId = bootstrap.ui?.statusElementId || 'deskSessionStatus';
  const ownerElementId = bootstrap.ui?.ownerElementId || 'deskControlOwner';
  const statusEl = document.getElementById(statusElementId);
  const ownerEl = document.getElementById(ownerElementId);

  const DRIFT_FORCE_SEEK_SECONDS = 0.35;
  const DRIFT_SOFT_SECONDS = 0.12;
  const STATE_REQUEST_INTERVAL_MS = 5000;

  let socket = null;
  let connected = false;
  let serverOffsetMs = 0;
  let lastState = null;
  let applyingServerState = false;
  let isScrubbing = false;
  let pendingControlRequest = null;
  let stateRequestIntervalId = null;
  let localOverrideUntilMs = 0;
  let autoControlPending = false;
  let recentUserGestureUntilMs = 0;
  let autoPausePending = false;

  const clamp = (value, min, max) => Math.min(max, Math.max(min, value));

  const formatTime = (value) => {
    if (!Number.isFinite(value) || value < 0) {
      return '00:00';
    }
    const seconds = Math.floor(value % 60);
    const minutes = Math.floor(value / 60);
    const pad = (num) => String(num).padStart(2, '0');
    return `${pad(minutes)}:${pad(seconds)}`;
  };

  const setStatus = (text) => {
    if (statusEl) {
      statusEl.textContent = text;
    }
  };

  const setOwnerText = (owner) => {
    if (!ownerEl) {
      return;
    }
    if (!owner || !owner.userName) {
      ownerEl.textContent = '';
      return;
    }
    ownerEl.textContent = `Controlled by ${owner.userName}`;
  };

  const dispatch = (name, detail) => {
    window.dispatchEvent(new CustomEvent(name, { detail }));
  };

  const getDurationSeconds = () => {
    if (Number.isFinite(video.duration) && video.duration > 0) {
      return video.duration;
    }
    const configured = Number(bootstrap.durationSeconds || window.DeskConfig?.video?.duration_seconds || NaN);
    return Number.isFinite(configured) && configured > 0 ? configured : null;
  };

  const getServerNow = () => Date.now() + serverOffsetMs;

  const projectStateTime = (state) => {
    if (!state) {
      return 0;
    }
    const serverNow = getServerNow();
    const serverDeltaSeconds = Math.max(0, serverNow - Number(state.serverTime || serverNow)) / 1000;
    const projected = state.playing ? Number(state.time || 0) + serverDeltaSeconds * Number(state.rate || 1) : Number(state.time || 0);
    const duration = getDurationSeconds();
    if (!duration) {
      return Math.max(0, projected);
    }
    return clamp(projected, 0, duration);
  };

  const getCurrentState = () => {
    if (!lastState) {
      return null;
    }
    const projectedTime = projectStateTime(lastState);
    return {
      ...lastState,
      time: projectedTime,
      projectedTime,
    };
  };

  const isOwner = () => {
    if (!lastState || !lastState.controlOwner) {
      return false;
    }
    const controlOwner = lastState.controlOwner;
    const myUserId = Number(window.DeskConfig?.userId || bootstrap.userId || 0);
    const mySocketId = socket?.id || null;
    const result = (
      (myUserId > 0 && Number(controlOwner.userId || 0) === myUserId) ||
      (mySocketId && controlOwner.socketId && controlOwner.socketId === mySocketId)
    );
    return result;
  };

  const updateStatusFromState = () => {
    const state = getCurrentState();
    if (!state) {
      return;
    }
    if (!connected) {
      setStatus('Reconnecting session…');
      return;
    }
    const playingLabel = state.playing ? 'Playing' : 'Paused';
    const timeLabel = formatTime(state.time);
    const ownerLabel = state.controlOwner ? ` · ${state.controlOwner.userName}` : '';
    setStatus(`${playingLabel} · ${timeLabel}${ownerLabel}`);
    setOwnerText(state.controlOwner || null);
  };

  const withServerApply = (fn) => {
    applyingServerState = true;
    try {
      fn();
    } finally {
      window.setTimeout(() => {
        applyingServerState = false;
      }, 0);
    }
  };

  const applyStateToVideo = (state, options = {}) => {
    if (!state) {
      return;
    }
    if (isScrubbing && !options.force) {
      return;
    }

    const projectedTime = projectStateTime(state);
    const duration = getDurationSeconds();
    const localTime = Number(video.currentTime || 0);
    const drift = Math.abs(localTime - projectedTime);

    withServerApply(() => {
      const targetRate = Number(state.rate || 1);
      if (Number.isFinite(targetRate) && targetRate > 0 && Math.abs(video.playbackRate - targetRate) > 0.001) {
        video.playbackRate = targetRate;
      }

      const shouldForceSeek = drift > DRIFT_FORCE_SEEK_SECONDS || options.forceSeek;
      if (shouldForceSeek) {
        video.currentTime = projectedTime;
      } else if (drift > DRIFT_SOFT_SECONDS && state.playing) {
        const correction = projectedTime - localTime;
        const nudged = localTime + correction * 0.6;
        video.currentTime = nudged;
      }

      if (state.playing) {
        if (video.paused) {
          const canAttemptPlay = Date.now() < recentUserGestureUntilMs || video.muted;
          if (canAttemptPlay) {
            video.play().catch(() => {
              /* autoplay may be blocked */
            });
          }
        }
      } else if (!video.paused) {
        video.pause();
      }
    });
  };

  // --- Phase C: Gate state application on readiness ---
  const handleIncomingState = (state, reason) => {
    // If video is not ready, buffer the latest state and return.
    if (!videoReady) {
      bufferedSessionState = state;
      bufferedSessionReason = reason;
      // Do not apply state yet; prevents early seek/pause/play
      return;
    }
    const incomingServerTime = Number(state.serverTime || Date.now());
    const measuredOffset = incomingServerTime - Date.now();
    serverOffsetMs = serverOffsetMs === 0 ? measuredOffset : serverOffsetMs * 0.85 + measuredOffset * 0.15;

    lastState = {
      ...state,
      reason: reason || state.reason || 'update',
    };

    applyStateToVideo(lastState);
    updateStatusFromState();
    dispatch('desk:session-state', getCurrentState());
  };

  const updateControlOwner = (payload) => {
    if (!payload) {
      return;
    }
    if (lastState) {
      lastState.controlOwner = payload.controlOwner || null;
    }
    setOwnerText(payload.controlOwner || null);
    dispatch('desk:control-owner', payload);
    dispatch('desk:session-state', getCurrentState());
  };

  const startStateRequests = () => {
    if (stateRequestIntervalId || !socket) {
      return;
    }
    stateRequestIntervalId = window.setInterval(() => {
      if (socket && connected) {
        socket.emit('session_state_request', { matchId });
      }
    }, STATE_REQUEST_INTERVAL_MS);
  };

  const stopStateRequests = () => {
    if (!stateRequestIntervalId) {
      return;
    }
    window.clearInterval(stateRequestIntervalId);
    stateRequestIntervalId = null;
  };

  const requestControl = (reason = 'interaction') => {
    // Local-only control (WebSockets disabled)
    return Promise.resolve(true);
  };

  const emitPlaybackCommand = async (type, payload = {}) => {
    // Local-only playback control (WebSockets disabled)
    if (type === 'play') {
      localOverrideUntilMs = Date.now() + 1500;
      recentUserGestureUntilMs = Date.now() + 2000;
      if (lastState) {
        lastState.playing = true;
        lastState.time = video.currentTime;
        lastState.baseTime = getServerNow();
      }
      video.play().catch((err) => {
        console.error('[DeskSession] Play error:', err);
      });
    } else if (type === 'pause') {
      localOverrideUntilMs = Date.now() + 800;
      if (lastState) {
        lastState.playing = false;
        lastState.time = video.currentTime;
        lastState.baseTime = getServerNow();
      }
      video.pause();
    } else if (type === 'seek') {
      const timeSeconds = payload.time;
      if (Number.isFinite(timeSeconds)) {
        localOverrideUntilMs = Date.now() + 500;
        video.currentTime = timeSeconds;
        if (lastState) {
          lastState.time = timeSeconds;
          lastState.baseTime = getServerNow();
        }
      }
    } else if (type === 'rate') {
      const rate = payload.rate;
      if (Number.isFinite(rate) && rate > 0) {
        video.playbackRate = rate;
        if (lastState) {
          lastState.rate = rate;
        }
      }
    }
    return true;
  };

  const play = () => emitPlaybackCommand('play');
  const pause = () => emitPlaybackCommand('pause');
  const seek = (timeSeconds) => emitPlaybackCommand('seek', { time: Number(timeSeconds) });
  const setRate = (rate) => emitPlaybackCommand('rate', { rate: Number(rate) });
  const skip = (deltaSeconds) => {
    const baseTime = Number(video.currentTime || 0);
    const duration = getDurationSeconds();
    const target = duration ? clamp(baseTime + deltaSeconds, 0, duration) : Math.max(0, baseTime + deltaSeconds);
    return seek(target);
  };

  const releaseControl = () => {
    // Local-only control (WebSockets disabled)
    return;
  };

  const getCurrentTime = () => {
    const state = getCurrentState();
    if (state) {
      return state.time;
    }
    return Number(video.currentTime || 0);
  };

  const getCurrentSecond = () => Math.max(0, Math.floor(getCurrentTime()));
  const isPlaying = () => Boolean(getCurrentState()?.playing);

  const sessionApi = {
    matchId,
    role: roleHint,
    get socket() {
      return socket;
    },
    get state() {
      return getCurrentState();
    },
    isOwner,
    isPlaying,
    getCurrentTime,
    getCurrentSecond,
    requestControl,
    releaseControl,
    play,
    pause,
    seek,
    setRate,
    skip,
    setScrubbing(value) {
      isScrubbing = Boolean(value);
    },
    applyState(force = false) {
      if (lastState) {
        applyStateToVideo(lastState, { force, forceSeek: force });
      }
    },
  };

  let resolveReady = null;
  const readyPromise = new Promise((resolve) => {
    resolveReady = resolve;
  });
  sessionApi.ready = readyPromise;
  window.DeskSession = sessionApi;

  // Now assign the functions to the forward-declared variables
  handleLocalPlayPause = () => {
    if (applyingServerState) {
      return;
    }
    if (Date.now() < localOverrideUntilMs) {
      return;
    }
    // Update session state to reflect actual video playback state
    if (lastState) {
      lastState.playing = !video.paused;
      lastState.time = video.currentTime;
      lastState.baseTime = getServerNow();
    }
    sessionApi.applyState(true);
  };

  // Save video time to localStorage periodically
  saveVideoTime = () => {
    try {
      const storageKey = `desk-video-time-${matchId}`;
      localStorage.setItem(storageKey, JSON.stringify({
        time: video.currentTime,
        duration: video.duration,
        timestamp: Date.now()
      }));
    } catch (e) {
      console.error('[DeskSession] Failed to save video time:', e);
    }
  };

  // Restore video time from localStorage
  restoreVideoTime = () => {
    try {
      const storageKey = `desk-video-time-${matchId}`;
      const saved = localStorage.getItem(storageKey);
      if (saved) {
        const data = JSON.parse(saved);
        video.currentTime = data.time;
      }
    } catch (e) {
      console.error('[DeskSession] Failed to restore video time:', e);
    }
  };

  // Set up event listeners for play/pause tracking and time persistence
  video.addEventListener('play', handleLocalPlayPause);
  video.addEventListener('pause', handleLocalPlayPause);

  // Save video time every 2 seconds
  setInterval(saveVideoTime, 2000);

  // Restore video time when metadata is loaded
  video.addEventListener('loadedmetadata', () => {
    videoReady = true;
    maybeApplyBufferedState();
    // Restore after applying buffered state so it doesn't get overwritten
    restoreVideoTime();
  });

  const connect = async () => {
    setStatus('Initializing video player…');

    // WebSockets disabled - initialize with local-only state
    // Skip the session endpoint fetch entirely

    // Get current user info
    const myUserId = Number(window.DeskConfig?.userId || bootstrap.userId || 0);
    const myUserName = window.DeskConfig?.userName || bootstrap.userName || 'Local User';

    // Initialize with a basic local state
    // Set current user as control owner so they can control playback
    const initialState = {
      matchId,
      playing: false,
      time: 0,
      baseTime: 0,
      rate: 1,
      updatedAt: Date.now(),
      controlOwner: myUserId > 0 ? { userId: myUserId, userName: myUserName, socketId: null } : null,
      serverTime: Date.now(),
      reason: 'local-init',
    };

    if (!videoReady) {
      bufferedSessionState = initialState;
      bufferedSessionReason = 'local-init';
    } else {
      lastState = initialState;
      sessionApi.applyState(true);
    }

    // Create a mock socket object for compatibility
    socket = {
      id: 'local-' + Math.random().toString(36).substr(2, 9),
      on: () => { },
      emit: () => { },
      off: () => { },
      disconnect: () => { },
    };

    // Mark as connected locally
    connected = true;
    setStatus(roleHint === 'viewer' ? 'Viewer mode (local)' : 'Connected (local)');

    // Dispatch ready immediately
    if (resolveReady) {
      resolveReady(sessionApi);
      resolveReady = null;
    }
    dispatch('desk:session-ready', sessionApi);
  };

  connect();
})();
