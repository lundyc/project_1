(() => {
  const VIEW_WIDTH_RATIO = 0.4;
  const overlay = document.querySelector('[data-panoramic-shell]');
  const baseVideo = document.getElementById('deskVideoPlayer');
  if (!overlay) {
    window.PanoramicDesk = {
      show: () => { },
      hide: () => { },
      isVisible: () => false,
    };
    return;
  }

  const canvas = overlay.querySelector('[data-panoramic-canvas]');
  const video = overlay.querySelector('[data-panoramic-video]');
  const playToggle = overlay.querySelector('[data-panoramic-play]');
  const fullscreenToggle = overlay.querySelector('[data-panoramic-fullscreen]');
  const closeBtn = overlay.querySelector('[data-panoramic-close]');
  const seekTrack = overlay.querySelector('[data-panoramic-seek-track]');
  const seekProgress = overlay.querySelector('[data-panoramic-seek-progress]');
  const timeDisplay = overlay.querySelector('[data-panoramic-time]');
  const messageEl = overlay.querySelector('[data-panoramic-message]');
  const ctx = canvas ? canvas.getContext('2d') : null;
  if (!canvas || !video || !ctx) {
    window.PanoramicDesk = {
      show: () => { },
      hide: () => { },
      isVisible: () => false,
    };
    return;
  }

  const state = {
    cropWidth: 0,
    viewX: 0,
    maxX: 0,
    hasCentered: false,
    isDragging: false,
    dragStartX: 0,
    dragStartViewX: 0,
  };

  const clamp = (value, min, max) => Math.min(Math.max(min, value), max);
  const formatTime = (value) => {
    if (!Number.isFinite(value) || value < 0) {
      return '00:00';
    }
    const seconds = Math.floor(value % 60);
    const minutes = Math.floor(value / 60);
    const pad = (num) => String(num).padStart(2, '0');
    return `${pad(minutes)}:${pad(seconds)}`;
  };

  let animationFrame = null;
  let wasBasePlaying = false;
  let requestedStartTime = 0;

  const setMessage = (text) => {
    if (!messageEl) {
      return;
    }
    if (text) {
      messageEl.textContent = text;
      messageEl.classList.add('is-visible');
    } else {
      messageEl.classList.remove('is-visible');
    }
  };

  const updateCropWindow = () => {
    if (!video.videoWidth || !video.videoHeight || !canvas.width || !canvas.height) {
      return;
    }
    const cropWidth = Math.max(1, Math.round(video.videoWidth * VIEW_WIDTH_RATIO));
    state.cropWidth = Math.min(cropWidth, video.videoWidth);
    state.maxX = Math.max(0, video.videoWidth - state.cropWidth);
    if (!state.hasCentered) {
      state.viewX = clamp((video.videoWidth - state.cropWidth) / 2, 0, state.maxX);
      state.hasCentered = true;
      // Hard-coded mid-crop for quick visual validation that we are not showing the full frame.
      state.viewX = clamp(Math.round((video.videoWidth - state.cropWidth) / 2), 0, state.maxX);
    }
    state.viewX = clamp(state.viewX, 0, state.maxX);
  };

  const updateCanvasWithVideo = () => {
    if (!canvas.width || !canvas.height) {
      return;
    }
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    if (video.readyState >= 2 && state.cropWidth > 0 && video.videoHeight) {
      ctx.drawImage(
        video,
        state.viewX,
        0,
        state.cropWidth,
        video.videoHeight,
        0,
        0,
        canvas.width,
        canvas.height
      );
    } else {
      ctx.fillStyle = '#101624';
      ctx.fillRect(0, 0, canvas.width, canvas.height);
    }
  };

  const renderLoop = () => {
    updateCanvasWithVideo();
    animationFrame = window.requestAnimationFrame(renderLoop);
  };

  const startRenderLoop = () => {
    if (!animationFrame) {
      animationFrame = window.requestAnimationFrame(renderLoop);
    }
  };

  const stopRenderLoop = () => {
    if (animationFrame) {
      window.cancelAnimationFrame(animationFrame);
      animationFrame = null;
    }
  };

  const resizeCanvas = () => {
    canvas.width = overlay.clientWidth;
    canvas.height = overlay.clientHeight;
    updateCropWindow();
  };

  const updateSeekUI = () => {
    if (!seekProgress || !timeDisplay) {
      return;
    }
    const current = Number.isFinite(video.currentTime) ? video.currentTime : 0;
    const duration = Number.isFinite(video.duration) && video.duration > 0 ? video.duration : 0;
    const percent = duration > 0 ? Math.min(100, Math.max(0, (current / duration) * 100)) : 0;
    seekProgress.style.width = `${percent}%`;
    timeDisplay.textContent = `${formatTime(current)} / ${formatTime(duration)}`;
  };

  const seekTo = (value) => {
    if (!Number.isFinite(value) || !video.duration) {
      return;
    }
    video.currentTime = Math.min(Math.max(0, value), video.duration);
  };

  const clampView = (deltaX) => {
    if (!canvas.width) {
      return;
    }
    const ratio = state.cropWidth / canvas.width;
    state.viewX = clamp(state.viewX + deltaX * ratio, 0, state.maxX);
  };

  const handleCanvasPointerDown = (event) => {
    state.isDragging = true;
    state.dragStartX = event.clientX;
    state.dragStartViewX = state.viewX;
    canvas.setPointerCapture?.(event.pointerId);
  };

  const handleCanvasPointerMove = (event) => {
    if (!state.isDragging) {
      return;
    }
    const delta = event.clientX - state.dragStartX;
    if (canvas.width) {
      const ratio = state.cropWidth / canvas.width;
      state.viewX = clamp(state.dragStartViewX + delta * ratio, 0, state.maxX);
    }
  };

  const handleCanvasPointerUp = (event) => {
    if (event.pointerId) {
      canvas.releasePointerCapture?.(event.pointerId);
    }
    state.isDragging = false;
  };

  const handleCanvasWheel = (event) => {
    if (!overlay.classList.contains('is-visible')) {
      return;
    }
    event.preventDefault();
    const delta = (event.deltaX || event.deltaY || 0) * 0.2;
    clampView(delta);
  };

  const handleSeekPointer = (clientX) => {
    if (!seekTrack || !video.duration || video.duration <= 0) {
      return;
    }
    const rect = seekTrack.getBoundingClientRect();
    if (!rect.width) {
      return;
    }
    const offset = clamp(clientX - rect.left, 0, rect.width);
    const percent = offset / rect.width;
    video.currentTime = percent * video.duration;
  };

  let isSeeking = false;
  const handleSeekPointerDown = (event) => {
    event.preventDefault();
    isSeeking = true;
    seekTrack?.setPointerCapture?.(event.pointerId);
    handleSeekPointer(event.clientX);
  };
  const handleSeekPointerMove = (event) => {
    if (!isSeeking) {
      return;
    }
    handleSeekPointer(event.clientX);
  };
  const handleSeekPointerUp = (event) => {
    if (!isSeeking) {
      return;
    }
    isSeeking = false;
    seekTrack?.releasePointerCapture?.(event.pointerId);
  };

  const togglePlay = () => {
    if (video.paused) {
      video.play();
    } else {
      video.pause();
    }
  };

  const updatePlayLabel = () => {
    if (!playToggle) {
      return;
    }
    playToggle.textContent = video.paused ? 'Play' : 'Pause';
  };

  const toggleFullscreen = () => {
    if (document.fullscreenElement) {
      document.exitFullscreen?.();
      return;
    }
    overlay.requestFullscreen?.();
  };

  const handleFullscreenChange = () => {
    if (!fullscreenToggle) {
      return;
    }
    const isFullscreen = Boolean(document.fullscreenElement);
    fullscreenToggle.textContent = isFullscreen ? 'Exit fullscreen' : 'Fullscreen';
  };

  const hideOverlay = (syncTime = true) => {
    overlay.classList.remove('is-visible');
    stopRenderLoop();
    video.pause();
    if (syncTime && baseVideo && Number.isFinite(video.currentTime)) {
      baseVideo.currentTime = video.currentTime;
    }
    if (syncTime && baseVideo && wasBasePlaying) {
      baseVideo.play();
    }
    setMessage('');
  };

  const showOverlay = (options = {}) => {
    const src = options.src;
    const time = typeof options.time === 'number' ? options.time : 0;
    if (!src) {
      setMessage('Panoramic video not ready yet.');
      return;
    }
    overlay.classList.add('is-visible');
    wasBasePlaying = Boolean(baseVideo && !baseVideo.paused);
    baseVideo?.pause();
    if (video.src !== src) {
      video.src = src;
    }
    requestedStartTime = time;
    if (time > 0) {
      if (video.readyState >= 1 && video.duration) {
        seekTo(time);
      } else {
        video.addEventListener('loadedmetadata', () => seekTo(time), { once: true });
      }
    }
    resizeCanvas();
    startRenderLoop();
    updatePlayLabel();
    setMessage('');
    video.play().catch(() => {
      // Autoplay blocked; leave control to the user.
    });
  };

  canvas.addEventListener('pointerdown', handleCanvasPointerDown);
  canvas.addEventListener('pointermove', handleCanvasPointerMove);
  canvas.addEventListener('pointerup', handleCanvasPointerUp);
  canvas.addEventListener('pointercancel', handleCanvasPointerUp);
  canvas.addEventListener('wheel', handleCanvasWheel, { passive: false });

  seekTrack?.addEventListener('pointerdown', handleSeekPointerDown);
  seekTrack?.addEventListener('pointermove', handleSeekPointerMove);
  seekTrack?.addEventListener('pointerup', handleSeekPointerUp);
  seekTrack?.addEventListener('pointercancel', handleSeekPointerUp);

  playToggle?.addEventListener('click', () => {
    togglePlay();
    updatePlayLabel();
  });

  fullscreenToggle?.addEventListener('click', toggleFullscreen);
  closeBtn?.addEventListener('click', () => {
    hideOverlay();
  });
  document.addEventListener('fullscreenchange', handleFullscreenChange);

  window.addEventListener('resize', resizeCanvas);

  video.addEventListener('timeupdate', updateSeekUI);
  video.addEventListener('durationchange', updateSeekUI);
  video.addEventListener('loadedmetadata', () => {
    updateCropWindow();
    updateSeekUI();
    if (requestedStartTime > 0) {
      seekTo(requestedStartTime);
      requestedStartTime = 0;
    }
  });
  video.addEventListener('error', () => {
    setMessage('Unable to load panoramic video.');
  });

  window.PanoramicDesk = {
    show: (options = {}) => showOverlay(options),
    hide: () => hideOverlay(),
    isVisible: () => overlay.classList.contains('is-visible'),
  };
})();
