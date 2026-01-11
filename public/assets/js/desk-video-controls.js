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
    const speedLabel = speedToggle ? speedToggle.querySelector('.speed-label') : null;
    const videoFrame = video ? video.closest('.video-frame') : null;
    const fullscreenTarget = videoFrame || video;
    const timelineTrack = document.getElementById('deskTimelineTrack');
    const timelineProgress = document.getElementById('deskTimelineProgress');
    const timelineWrapper = document.getElementById('deskTimeline');
    const timeDisplay = document.getElementById('deskTimeDisplay');

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

    video.playbackRate = 1;
    speedToggle.setAttribute('aria-expanded', 'false');
    speedOptions.setAttribute('aria-hidden', 'true');
    speedLabel.textContent = '1×';

    const updatePlayPauseIcon = () => {
      playPauseIcon.classList.remove('fa-play', 'fa-pause');
      playPauseIcon.classList.add(video.paused ? 'fa-play' : 'fa-pause');
      playPauseBtn.setAttribute('aria-label', video.paused ? 'Play video' : 'Pause video');
    };

    const updateMuteIcon = () => {
      muteIcon.classList.remove('fa-volume-high', 'fa-volume-xmark');
      muteIcon.classList.add(video.muted ? 'fa-volume-xmark' : 'fa-volume-high');
      muteBtn.setAttribute('aria-label', video.muted ? 'Unmute video' : 'Mute video');
    };

    const updateFullscreenIcon = () => {
      const isVideoFullscreen = document.fullscreenElement === fullscreenTarget;
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
      timeDisplay.textContent = `${current} / ${total}`;
    };

    const updateTimelineProgress = () => {
      if (!timelineProgress) {
        return;
      }
      if (!Number.isFinite(video.duration) || video.duration === 0) {
        timelineProgress.style.width = '0%';
        return;
      }
      const percent = Math.min(100, Math.max(0, (video.currentTime / video.duration) * 100));
      timelineProgress.style.width = `${percent}%`;
    };

    const syncTimeline = () => {
      updateTimeDisplay();
      updateTimelineProgress();
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
      if (video.paused) {
        video.play();
      } else {
        video.pause();
      }
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
      if (!fullscreenTarget) {
        return;
      }
      if (document.fullscreenElement === fullscreenTarget) {
        document.exitFullscreen();
      } else if (fullscreenTarget.requestFullscreen) {
        fullscreenTarget.requestFullscreen().catch(() => {
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
