// Phase C: Gate server-driven seeks and playback state on media readiness
// This prevents video preload cancellation and unnecessary seek/pause churn.
(function () {
  const bootstrap = window.DeskSessionBootstrap || {};
  const matchId = Number(bootstrap.matchId || window.DeskConfig?.matchId || 0);
  if (!matchId) {
    return;
  }

  const roleHint = (bootstrap.role || 'analyst').toLowerCase() === 'viewer' ? 'viewer' : 'analyst';
  const sessionEndpoint = bootstrap.sessionEndpoint || (window.DeskConfig?.endpoints?.session ?? null);
  const videoElementId = bootstrap.videoElementId || 'deskVideoPlayer';

  const video = document.getElementById(videoElementId);
  if (!video || !sessionEndpoint) {
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

  // Listen for video readiness
  video.addEventListener('loadedmetadata', () => {
    videoReady = true;
    maybeApplyBufferedState();
  });
  // Optionally, wait for canplay for extra safety
  video.addEventListener('canplay', () => {
    videoReady = true;
    maybeApplyBufferedState();
  });

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
    return (
      (myUserId > 0 && Number(controlOwner.userId || 0) === myUserId) ||
      (mySocketId && controlOwner.socketId && controlOwner.socketId === mySocketId)
    );
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
    if (roleHint !== 'analyst') {
      return Promise.resolve(false);
    }
    if (!socket || !connected) {
      return Promise.resolve(false);
    }
    if (isOwner()) {
      socket.emit('request_control', { matchId, reason });
      return Promise.resolve(true);
    }
    if (pendingControlRequest) {
      return pendingControlRequest.promise;
    }

    let resolveFn = null;
    const promise = new Promise((resolve) => {
      resolveFn = resolve;
    });
    const timeoutId = window.setTimeout(() => {
      if (pendingControlRequest) {
        pendingControlRequest = null;
      }
      resolveFn(false);
    }, 2500);

    pendingControlRequest = {
      promise,
      resolve: (value) => {
        window.clearTimeout(timeoutId);
        pendingControlRequest = null;
        resolveFn(Boolean(value));
      },
    };

    socket.emit('request_control', { matchId, reason });
    return promise;
  };

  const emitPlaybackCommand = async (type, payload = {}) => {
    if (roleHint !== 'analyst' || !socket || !connected) {
      return false;
    }
    if (type === 'play') {
      localOverrideUntilMs = Date.now() + 1500;
      recentUserGestureUntilMs = Date.now() + 2000;
      video.play().catch(() => {
        /* autoplay may still be blocked */
      });
    } else if (type === 'pause') {
      localOverrideUntilMs = Date.now() + 800;
      video.pause();
    }
    const gotControl = isOwner() ? true : await requestControl(type);
    if (!gotControl) {
      dispatch('desk:session-denied', { matchId, type, controlOwner: lastState?.controlOwner || null });
      return false;
    }
    socket.emit('playback_cmd', {
      matchId,
      type,
      time: payload.time,
      rate: payload.rate,
    });
    return true;
  };

  const play = () => emitPlaybackCommand('play');
  const pause = () => emitPlaybackCommand('pause');
  const seek = (timeSeconds) => emitPlaybackCommand('seek', { time: Number(timeSeconds) });
  const setRate = (rate) => emitPlaybackCommand('rate', { rate: Number(rate) });
  const skip = (deltaSeconds) => {
    const state = getCurrentState();
    const baseTime = state ? state.time : Number(video.currentTime || 0);
    const duration = getDurationSeconds();
    const target = duration ? clamp(baseTime + deltaSeconds, 0, duration) : Math.max(0, baseTime + deltaSeconds);
    return seek(target);
  };

  const releaseControl = () => {
    if (!socket || !connected || roleHint !== 'analyst') {
      return;
    }
    socket.emit('release_control', { matchId });
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

  const handleLocalPlayPause = () => {
    if (applyingServerState) {
      return;
    }
    if (Date.now() < localOverrideUntilMs) {
      return;
    }
    sessionApi.applyState(true);
  };

  video.addEventListener('play', handleLocalPlayPause);
  video.addEventListener('pause', handleLocalPlayPause);

  const connect = async () => {
    setStatus('Connecting session…');

    let sessionConfig = null;
    try {
      const url = new URL(sessionEndpoint, window.location.origin);
      url.searchParams.set('role', roleHint);
      const response = await fetch(url.toString(), {
        credentials: 'same-origin',
        headers: {
          Accept: 'application/json',
        },
      });
      sessionConfig = await response.json();
    } catch (err) {
      setStatus('Session endpoint unavailable');
      dispatch('desk:session-error', { code: 'session_endpoint_failed', error: err });
      return;
    }

    if (!sessionConfig || sessionConfig.ok === false) {
      setStatus('Session unavailable');
      dispatch('desk:session-error', { code: 'session_config_invalid', sessionConfig });
      return;
    }

    // If the session endpoint returns a bare origin (no path), prefer the
    // HTTPS reverse-proxy path we configured for Socket.IO.
    try {
      const parsedWsUrl = new URL(sessionConfig.websocketUrl, window.location.origin);
      const isSameHost = parsedWsUrl.host === window.location.host;
      const hasNoPath = !parsedWsUrl.pathname || parsedWsUrl.pathname === '/';
      if (isSameHost && hasNoPath) {
        sessionConfig.websocketUrl = `${window.location.origin}/match-session`;
      }
    } catch (err) {
      /* ignore URL normalization issues */
    }

    if (sessionConfig.snapshot) {
      // Buffer the initial snapshot if video is not ready
      const snapshot = sessionConfig.snapshot;
      const serverTime = Number(sessionConfig.serverTime || Date.now());
      const baseTime = Number(snapshot.baseTime || 0);
      const rate = Number(snapshot.rate || 1);
      const updatedAt = Number(snapshot.updatedAt || serverTime);
      const elapsedSeconds = snapshot.playing ? Math.max(0, serverTime - updatedAt) / 1000 * rate : 0;
      const initialState = {
        matchId,
        playing: Boolean(snapshot.playing),
        time: baseTime + elapsedSeconds,
        baseTime,
        rate,
        updatedAt,
        controlOwner: snapshot.controlOwner || null,
        serverTime,
        reason: 'snapshot',
      };
      if (!videoReady) {
        bufferedSessionState = initialState;
        bufferedSessionReason = 'snapshot';
      } else {
        lastState = initialState;
        sessionApi.applyState(true);
      }
    }

    if (!window.io) {
      setStatus('Socket client missing');
      dispatch('desk:session-error', { code: 'socket_client_missing' });
      return;
    }

    // Socket.IO expects the base origin separately from the path. If we pass
    // a URL that includes `/match-session`, it is treated as a namespace and
    // triggers "Invalid namespace". Use the origin + explicit path instead.
    const parsedWsUrl = new URL(sessionConfig.websocketUrl, window.location.origin);
    const socketBaseUrl = parsedWsUrl.origin;
    const socketPath = '/match-session/socket.io';

    socket = window.io(socketBaseUrl, {
      // Allow polling fallback (helps behind proxies/CDNs like Cloudflare).
      transports: ['websocket', 'polling'],
      withCredentials: true,
      reconnection: true,
      reconnectionDelay: 500,
      reconnectionDelayMax: 2000,
      path: socketPath,
    });

    socket.on('connect', () => {
      connected = true;
      setStatus(roleHint === 'viewer' ? 'Viewer mode' : 'Connected');
      socket.emit('session_join', {
        matchId,
        role: roleHint,
        userId: window.DeskConfig?.userId || bootstrap.userId || null,
        userName: window.DeskConfig?.userName || bootstrap.userName || null,
        token: sessionConfig.token,
        durationSeconds: getDurationSeconds(),
      });
      startStateRequests();
      if (roleHint === 'analyst') {
        autoControlPending = true;
        autoPausePending = true;
        requestControl('auto');
      }
      if (resolveReady) {
        resolveReady(sessionApi);
        resolveReady = null;
      }
      dispatch('desk:session-ready', sessionApi);
    });

    socket.on('disconnect', () => {
      connected = false;
      stopStateRequests();
      updateStatusFromState();
      dispatch('desk:session-disconnect', { matchId });
    });

    socket.on('connect_error', (err) => {
      setStatus('Session connection failed');
    });

    socket.on('reconnect_attempt', () => { });

    socket.on('session_state', (state) => {
      // Buffer state if video not ready
      handleIncomingState(state, state.reason);
    });

    socket.on('control_owner_changed', (payload) => {
      updateControlOwner(payload);
    });

    socket.on('control_granted', (payload) => {
      if (pendingControlRequest) {
        pendingControlRequest.resolve(true);
      }
      updateControlOwner(payload);
      if (autoControlPending) {
        autoControlPending = false;
      }
      if (autoPausePending) {
        autoPausePending = false;
        pause();
      }
      dispatch('desk:control-granted', payload);
    });

    socket.on('control_denied', (payload) => {
      if (pendingControlRequest) {
        pendingControlRequest.resolve(false);
      }
      updateControlOwner(payload);
      if (autoControlPending) {
        autoControlPending = false;
        if (window.Toast && typeof window.Toast.show === 'function') {
          const ownerName = payload?.controlOwner?.userName;
          const msg = ownerName ? `Control denied (owned by ${ownerName})` : 'Control denied';
          window.Toast.show(msg, 'warning');
        }
      }
      dispatch('desk:control-denied', payload);
    });

    socket.on('error', (payload) => {
      dispatch('desk:session-error', payload);
    });
  };

  connect();
})();
