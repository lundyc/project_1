// This script is injected into the detached popup to expose the video element for remote control
window.addEventListener('DOMContentLoaded', function () {
          // Expose the video element for the opener
          window.videoPlayer = document.querySelector('video');
          // Listen for commands from the opener
          window.addEventListener('message', function (event) {
                    if (!window.videoPlayer) return;
                    if (!event.data || typeof event.data !== 'object') return;
                    const { action, value } = event.data;
                    switch (action) {
                              case 'play':
                                        window.videoPlayer.play();
                                        break;
                              case 'pause':
                                        window.videoPlayer.pause();
                                        break;
                              case 'setCurrentTime':
                                        window.videoPlayer.currentTime = value;
                                        break;
                              case 'setVolume':
                                        window.videoPlayer.volume = value;
                                        break;
                              case 'setMuted':
                                        window.videoPlayer.muted = !!value;
                                        break;
                              case 'setPlaybackRate':
                                        window.videoPlayer.playbackRate = value;
                                        break;
                              default:
                                        break;
                    }
          });
});
