(function () {
  const bootstrap = window.DeskSessionBootstrap || {};
  if (!bootstrap.matchId) {
    return;
  }

  const statusEl = document.getElementById(bootstrap.ui?.statusElementId || 'deskSessionStatus');
  const playOverlayEl = document.querySelector('[data-tv-play-overlay]');
  const playOverlayIcon = playOverlayEl ? playOverlayEl.querySelector('[data-tv-play-icon]') : null;
  let lastPlayingState = null;
  let overlayTimerId = null;

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

  const showPlayPauseFlash = (playing) => {
    if (!playOverlayEl || !playOverlayIcon) {
      return;
    }
    playOverlayIcon.classList.remove('fa-play', 'fa-pause');
    playOverlayIcon.classList.add(playing ? 'fa-play' : 'fa-pause');
    playOverlayEl.classList.add('is-visible');
    playOverlayEl.setAttribute('aria-hidden', 'false');
    window.clearTimeout(overlayTimerId);
    overlayTimerId = window.setTimeout(() => {
      playOverlayEl.classList.remove('is-visible');
      playOverlayEl.setAttribute('aria-hidden', 'true');
    }, 420);
  };

  setStatus('Connecting session…');

  window.addEventListener('desk:session-ready', () => {
    // Status will switch to the session time as soon as state arrives.
    setStatus('00:00');
  });

  window.addEventListener('desk:session-state', (event) => {
    const state = event.detail || {};
    const timeLabel = formatTime(state.time || 0);
    setStatus(timeLabel);
    if (typeof state.playing === 'boolean' && lastPlayingState !== null && state.playing !== lastPlayingState) {
      showPlayPauseFlash(state.playing);
    }
    if (typeof state.playing === 'boolean') {
      lastPlayingState = state.playing;
    }
  });
})();
