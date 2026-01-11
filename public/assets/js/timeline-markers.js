(() => {
          'use strict';

          if (!window.ANNOTATIONS_ENABLED) {
                    return;
          }

          const cfg = window.DeskConfig || {};
          const videoEl = document.getElementById('deskVideoPlayer');
          const timelineEl = document.querySelector('[data-video-timeline]');
          if (!videoEl || !timelineEl) {
                    return;
          }
          const trackEl = timelineEl.querySelector('[data-video-timeline-track]');
          const markersEl = timelineEl.querySelector('[data-video-timeline-markers]');
          const playheadEl = timelineEl.querySelector('[data-video-timeline-playhead]');
          if (!trackEl || !markersEl || !playheadEl) {
                    return;
          }

          const rawMatchVideoId =
                    cfg.annotations && typeof cfg.annotations.matchVideoId === 'number'
                              ? cfg.annotations.matchVideoId
                              : cfg.video && typeof cfg.video.match_video_id === 'number'
                                        ? cfg.video.match_video_id
                                        : cfg.video && Number.isFinite(Number(cfg.video.match_video_id))
                                                  ? Number(cfg.video.match_video_id)
                                                  : 0;
          const matchVideoId = Number.isFinite(Number(rawMatchVideoId)) && Number(rawMatchVideoId) > 0 ? Number(rawMatchVideoId) : null;
          const visibilityState = window.DeskAnnotationVisibilityState;
          const durationFromConfig =
                    cfg.video && Number.isFinite(Number(cfg.video.duration_seconds)) ? Number(cfg.video.duration_seconds) : 0;

          const state = {
                    annotationsByTarget: new Map(),
                    activeContext: {
                              mode: 'match',
                              clipId: null,
                              startSecond: null,
                              endSecond: null,
                    },
                    annotationsVisible: visibilityState && typeof visibilityState.visible === 'boolean'
                              ? visibilityState.visible
                              : true,
                    selectedAnnotationId: null,
                    currentMarkers: [],
                    matchDuration: Number.isFinite(durationFromConfig) && durationFromConfig > 0 ? durationFromConfig : 0,
          };

          const ANNOTATION_WINDOW_SECONDS = 3;
          let annotationBridgeAttached = false;

          function updateTimelineVisibility() {
                    timelineEl.classList.toggle('is-hidden', !state.annotationsVisible);
          }

          function handleVisibilityChange(detail) {
                    const nextVisible = detail && typeof detail.visible === 'boolean' ? detail.visible : true;
                    state.annotationsVisible = nextVisible;
                    updateTimelineVisibility();
          }

          function handleClipChange(detail) {
                    const isClip = detail && detail.mode === 'clip';
                    state.activeContext.mode = isClip ? 'clip' : 'match';
                    state.activeContext.clipId = isClip && typeof detail.clipId === 'number' ? detail.clipId : null;
                    state.activeContext.startSecond = isClip && typeof detail.startSecond === 'number' ? detail.startSecond : null;
                    state.activeContext.endSecond = isClip && typeof detail.endSecond === 'number' ? detail.endSecond : null;
                    renderMarkers();
          }

          function normalizeKey(key) {
                    return Number.isFinite(key) ? Number(key) : null;
          }

          function handleAnnotationPayload(payload) {
                    if (!payload || typeof payload.type !== 'string') {
                              return;
                    }
                    const id = normalizeKey(payload.id);
                    if (!Number.isFinite(id)) {
                              return;
                    }
                    const key = `${payload.type}:${id}`;
                    const annotations = Array.isArray(payload.annotations) ? payload.annotations.slice() : [];
                    state.annotationsByTarget.set(key, annotations);
                    renderMarkers();
          }

          function attachAnnotationBridge() {
                    if (annotationBridgeAttached) {
                              return;
                    }
                    const bridge = window.DeskAnnotationTimelineBridge;
                    if (!bridge || typeof bridge.subscribe !== 'function') {
                              return;
                    }
                    annotationBridgeAttached = true;
                    bridge.subscribe(handleAnnotationPayload);
          }

          function collectAnnotationsForContext() {
                    const matchEntries = [];
                    state.annotationsByTarget.forEach((list, key) => {
                              const parts = key.split(':');
                              if (parts[0] !== 'match_video') {
                                        return;
                              }
                              const targetId = normalizeKey(parts[1]);
                              if (matchVideoId && targetId && targetId !== matchVideoId) {
                                        return;
                              }
                              list.forEach((annotation) => {
                                        if (!annotation) {
                                                  return;
                                        }
                                        const stamp = Number(annotation.timestamp_second);
                                        const annId = Number(annotation.id);
                                        if (!Number.isFinite(stamp) || !Number.isFinite(annId)) {
                                                  return;
                                        }
                                        matchEntries.push({
                                                  id: annId,
                                                  timestamp: stamp,
                                                  toolType: annotation.tool_type || '',
                                                  notes: annotation.notes || '',
                                                  isClip: false,
                                        });
                              });
                    });

                    let filtered = matchEntries;
                    if (state.activeContext.mode === 'clip') {
                              const start = typeof state.activeContext.startSecond === 'number' ? Math.max(0, state.activeContext.startSecond) : 0;
                              const endRaw = typeof state.activeContext.endSecond === 'number'
                                        ? state.activeContext.endSecond
                                        : state.matchDuration || null;
                              const end = endRaw !== null ? Math.max(start, endRaw) : start;
                              filtered = matchEntries.filter((entry) => entry.timestamp >= start && entry.timestamp <= end);
                    }

                    const combined = filtered.slice();
                    if (state.activeContext.mode === 'clip' && state.activeContext.clipId) {
                              const clipList = state.annotationsByTarget.get(`clip:${state.activeContext.clipId}`) || [];
                              clipList.forEach((annotation) => {
                                        if (!annotation) {
                                                  return;
                                        }
                                        const stamp = Number(annotation.timestamp_second);
                                        const annId = Number(annotation.id);
                                        if (!Number.isFinite(stamp) || !Number.isFinite(annId)) {
                                                  return;
                                        }
                                        combined.push({
                                                  id: annId,
                                                  timestamp: stamp,
                                                  toolType: annotation.tool_type || '',
                                                  notes: annotation.notes || '',
                                                  isClip: true,
                                        });
                              });
                    }

                    combined.sort((a, b) => {
                              if (a.timestamp === b.timestamp) {
                                        return a.id - b.id;
                              }
                              return a.timestamp - b.timestamp;
                    });

                    return combined;
          }

          function formatTime(seconds) {
                    const total = Math.max(0, Math.floor(seconds));
                    const minutes = Math.floor(total / 60);
                    const secs = total % 60;
                    return `${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
          }

          function buildTooltip(entry) {
                    const segments = [];
                    if (entry.toolType) {
                              segments.push(entry.toolType);
                    }
                    segments.push(formatTime(entry.timestamp));
                    let text = segments.join(' · ');
                    if (entry.notes) {
                              text += `\n${entry.notes}`;
                    }
                    return text;
          }

          function updateMarkerStates() {
                    const currentTime = typeof videoEl.currentTime === 'number' ? videoEl.currentTime : 0;
                    const activeId = findActiveMarkerId(currentTime);
                    const markerNodes = markersEl.querySelectorAll('.video-timeline-marker');
                    markerNodes.forEach((marker, index) => {
                              const entry = state.currentMarkers[index];
                              const isActive = entry && entry.id === activeId;
                              const isSelected = entry && state.selectedAnnotationId === entry.id;
                              marker.classList.toggle('is-active', Boolean(isActive));
                              marker.classList.toggle('is-selected', Boolean(isSelected));
                    });
          }

          function findActiveMarkerId(currentTime) {
                    for (let i = 0; i < state.currentMarkers.length; i++) {
                              const entry = state.currentMarkers[i];
                              if (!entry) {
                                        continue;
                              }
                              if (Math.abs(entry.timestamp - currentTime) <= ANNOTATION_WINDOW_SECONDS) {
                                        return entry.id;
                              }
                    }
                    return null;
          }

          function updatePlayheadPosition() {
                    const duration = state.matchDuration > 0 ? state.matchDuration : 1;
                    const currentTime = Math.max(0, Math.min(typeof videoEl.currentTime === 'number' ? videoEl.currentTime : 0, duration));
                    const position = Math.min(100, Math.max(0, (currentTime / duration) * 100));
                    playheadEl.style.left = `${position}%`;
          }

          function setMatchDuration(value) {
                    const normalized = Number(value);
                    if (!Number.isFinite(normalized) || normalized <= 0) {
                              return;
                    }
                    state.matchDuration = normalized;
                    renderMarkers();
          }

          function handleTimeUpdate() {
                    updateMarkerStates();
                    updatePlayheadPosition();
          }

          function handleMarkerClick(entry) {
                    if (!entry || !Number.isFinite(entry.timestamp)) {
                              return;
                    }
                    state.selectedAnnotationId = entry.id;
                    videoEl.currentTime = Math.max(0, entry.timestamp);
                    handleTimeUpdate();
                    if (window.DeskAnnotationTimelineBridge && typeof window.DeskAnnotationTimelineBridge.highlightAnnotation === 'function') {
                              window.DeskAnnotationTimelineBridge.highlightAnnotation(entry.id);
                    }
          }

          function renderMarkers() {
                    const duration = state.matchDuration > 0 ? state.matchDuration : 1;
                    const annotations = collectAnnotationsForContext();
                    state.currentMarkers = annotations;
                    markersEl.innerHTML = '';
                    if (!annotations.length) {
                              updateMarkerStates();
                              updatePlayheadPosition();
                              return;
                    }
                    const fragment = document.createDocumentFragment();
                    annotations.forEach((entry) => {
                              const marker = document.createElement('button');
                              marker.type = 'button';
                              marker.className = 'video-timeline-marker';
                              if (entry.isClip) {
                                        marker.classList.add('video-timeline-marker--clip');
                              }
                              const percent = Math.min(100, Math.max(0, (entry.timestamp / duration) * 100));
                              marker.style.left = `${percent}%`;
                              marker.dataset.annotationId = String(entry.id);
                              marker.dataset.timestamp = String(entry.timestamp);
                              marker.title = buildTooltip(entry);
                              marker.addEventListener('click', () => handleMarkerClick(entry));
                              fragment.appendChild(marker);
                    });
                    markersEl.appendChild(fragment);
                    updateMarkerStates();
                    updatePlayheadPosition();
          }

          function handleClipEvent(detail) {
                    handleClipChange(detail || {});
          }

          function handleVisibilityEvent(detail) {
                    handleVisibilityChange(detail || {});
          }

          function ensureBridgeReady() {
                    attachAnnotationBridge();
                    window.addEventListener('DeskAnnotationTimelineReady', attachAnnotationBridge);
          }

          videoEl.addEventListener('loadedmetadata', () => setMatchDuration(videoEl.duration));
          videoEl.addEventListener('durationchange', () => setMatchDuration(videoEl.duration));
          videoEl.addEventListener('timeupdate', handleTimeUpdate);
          videoEl.addEventListener('seeked', handleTimeUpdate);

          window.addEventListener('DeskClipPlaybackChanged', handleClipEvent);
          window.addEventListener('DeskAnnotationVisibilityChanged', handleVisibilityEvent);

          ensureBridgeReady();
          handleClipChange(window.DeskClipPlaybackState || {});
          handleVisibilityChange(window.DeskAnnotationVisibilityState || {});
          renderMarkers();
})();
