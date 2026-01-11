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

    const skip = (seconds) => {
      const maxTime = Number.isFinite(video.duration) ? video.duration : Number.POSITIVE_INFINITY;
      video.currentTime = Math.min(Math.max(0, video.currentTime + seconds), maxTime);
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
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDeskControls);
  } else {
    initDeskControls();
  }
})();
