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
          let markerDragState = null;
          let skipMarkerClickId = null;

          // Compute timeline duration, compressing half-time and hiding ET unless started
          function getTimelinePeriods() {
                    // Example: periods = [{start:0,end:45,type:'H1'},{start:45,end:60,type:'HT'},{start:60,end:90,type:'H2'},{start:90,end:105,type:'ET1'},{start:105,end:120,type:'ET2'}]
                    // You may need to fetch this from window.DeskConfig or PHP
                    const periods = window.DeskConfig?.periods || [
                              { start: 0, end: 45, type: 'H1' },
                              { start: 45, end: 60, type: 'HT' },
                              { start: 60, end: 90, type: 'H2' },
                              { start: 90, end: 105, type: 'ET1' },
                              { start: 105, end: 120, type: 'ET2' }
                    ];
                    // Hide ET1/ET2 unless started
                    const showET1 = window.DeskConfig?.et1Started;
                    const showET2 = window.DeskConfig?.et2Started;
                    return periods.filter(p => {
                              if (p.type === 'ET1' && !showET1) return false;
                              if (p.type === 'ET2' && !showET2) return false;
                              return true;
                    });
          }

          function getTimelineDuration() {
                    // Only count visible periods, compress half-time
                    const periods = getTimelinePeriods();
                    let duration = 0;
                    periods.forEach(p => {
                              if (p.type === 'HT') {
                                        duration += 2; // Compress half-time to 2 minutes width
                              } else {
                                        duration += (p.end - p.start);
                              }
                    });
                    return duration > 0 ? duration : 1;
          }

          function computeTimestampFromClientX(clientX) {
                    const rect = trackEl.getBoundingClientRect();
                    if (!rect.width) {
                              return 0;
                    }
                    const offset = Math.min(rect.width, Math.max(0, clientX - rect.left));
                    const duration = getTimelineDuration();
                    return Math.min(duration, Math.max(0, (offset / rect.width) * duration));
          }

          function renderMarkerPosition(marker, entry, timestamp) {
                    // Map real time to compressed timeline
                    const periods = getTimelinePeriods();
                    let timelineTime = 0;
                    let found = false;
                    for (const p of periods) {
                              if (timestamp >= p.start && timestamp < p.end) {
                                        if (p.type === 'HT') {
                                                  // Half-time: compress
                                                  timelineTime += 2 * (timestamp - p.start) / (p.end - p.start);
                                        } else {
                                                  timelineTime += (timestamp - p.start);
                                        }
                                        found = true;
                                        break;
                              } else {
                                        if (p.type === 'HT') {
                                                  timelineTime += 2;
                                        } else {
                                                  timelineTime += (p.end - p.start);
                                        }
                              }
                    }
                    const duration = getTimelineDuration();
                    const percent = duration > 0 ? Math.min(100, Math.max(0, (timelineTime / duration) * 100)) : 0;
                    marker.style.left = `${percent}%`;
                    marker.dataset.timestamp = String(timestamp);
                    marker.title = buildTooltip(entry);
          }

          function cleanupMarkerDrag() {
                    if (!markerDragState) {
                              return;
                    }
                    const { marker, pointerId } = markerDragState;
                    marker.classList.remove('is-dragging');
                    marker.releasePointerCapture?.(pointerId);
                    document.removeEventListener('pointermove', handleMarkerPointerMove);
                    document.removeEventListener('pointerup', finalizeMarkerDrag);
                    document.removeEventListener('pointercancel', cancelMarkerDrag);
                    markerDragState = null;
          }

          function handleMarkerPointerDown(entry, marker, event) {
                    if (event.button !== 0) {
                              return;
                    }
                    const rect = trackEl.getBoundingClientRect();
                    if (!rect.width || state.matchDuration <= 0) {
                              return;
                    }
                    event.preventDefault();
                    event.stopPropagation();
                    cleanupMarkerDrag();
                    markerDragState = {
                              entry,
                              marker,
                              pointerId: event.pointerId,
                              currentTime: entry.timestamp,
                    };
                    marker.classList.add('is-dragging');
                    marker.setPointerCapture?.(event.pointerId);
                    document.addEventListener('pointermove', handleMarkerPointerMove);
                    document.addEventListener('pointerup', finalizeMarkerDrag);
                    document.addEventListener('pointercancel', cancelMarkerDrag);
          }

          function handleMarkerPointerMove(event) {
                    if (!markerDragState || event.pointerId !== markerDragState.pointerId) {
                              return;
                    }
                    const timestamp = computeTimestampFromClientX(event.clientX);
                    markerDragState.currentTime = timestamp;
                    markerDragState.entry.timestamp = timestamp;
                    renderMarkerPosition(markerDragState.marker, markerDragState.entry, timestamp);
                    event.preventDefault();
          }

          function finalizeMarkerDrag(event) {
                    if (!markerDragState || event.pointerId !== markerDragState.pointerId) {
                              return;
                    }
                    const { entry } = markerDragState;
                    const timestamp = Number(markerDragState.currentTime ?? entry.timestamp) || 0;
                    cleanupMarkerDrag();
                    skipMarkerClickId = entry.id;
                    requestAnimationFrame(() => {
                              if (skipMarkerClickId === entry.id) {
                                        skipMarkerClickId = null;
                              }
                    });
                    window.dispatchEvent(
                              new CustomEvent('DeskDrawingTimestampUpdate', {
                                        detail: { drawingId: entry.id, timestamp },
                              })
                    );
                    if (window.DeskAnnotationTimelineBridge && typeof window.DeskAnnotationTimelineBridge.highlightAnnotation === 'function') {
                              window.DeskAnnotationTimelineBridge.highlightAnnotation(entry.id);
                    }
          }

          function cancelMarkerDrag(event) {
                    if (!markerDragState || event.pointerId !== markerDragState.pointerId) {
                              return;
                    }
                    const entryId = markerDragState.entry.id;
                    cleanupMarkerDrag();
                    skipMarkerClickId = entryId;
                    requestAnimationFrame(() => {
                              if (skipMarkerClickId === entryId) {
                                        skipMarkerClickId = null;
                              }
                    });
                    renderMarkers();
          }

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
                    // Map real time to compressed timeline
                    const periods = getTimelinePeriods();
                    let timelineTime = 0;
                    let found = false;
                    const currentTime = typeof videoEl.currentTime === 'number' ? videoEl.currentTime : 0;
                    for (const p of periods) {
                              if (currentTime >= p.start && currentTime < p.end) {
                                        if (p.type === 'HT') {
                                                  timelineTime += 2 * (currentTime - p.start) / (p.end - p.start);
                                        } else {
                                                  timelineTime += (currentTime - p.start);
                                        }
                                        found = true;
                                        break;
                              } else {
                                        if (p.type === 'HT') {
                                                  timelineTime += 2;
                                        } else {
                                                  timelineTime += (p.end - p.start);
                                        }
                              }
                    }
                    const duration = getTimelineDuration();
                    const position = duration > 0 ? Math.min(100, Math.max(0, (timelineTime / duration) * 100)) : 0;
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
                    if (!entry || skipMarkerClickId === entry.id) {
                              skipMarkerClickId = null;
                              return;
                    }
                    if (!Number.isFinite(entry.timestamp)) {
                              return;
                    }
                    state.selectedAnnotationId = entry.id;
                    const session = window.DeskSession;
                    if (session && typeof session.seek === 'function') {
                              session.seek(Math.max(0, entry.timestamp));
                    } else {
                              videoEl.currentTime = Math.max(0, entry.timestamp);
                    }
                    handleTimeUpdate();
                    if (window.DeskAnnotationTimelineBridge && typeof window.DeskAnnotationTimelineBridge.highlightAnnotation === 'function') {
                              window.DeskAnnotationTimelineBridge.highlightAnnotation(entry.id);
                    }
          }

          function renderMarkers() {
                    cleanupMarkerDrag();
                    const annotations = collectAnnotationsForContext();
                    state.currentMarkers = annotations;
                    markersEl.innerHTML = '';

                    // Add period markers (vertical line + dot)
                    const periods = getTimelinePeriods();
                    const duration = getTimelineDuration();
                    periods.forEach((p, idx) => {
                              // Only show ET1/ET2 if started (already filtered by getTimelinePeriods)
                              // Place marker at start of each period except first
                              if (idx === 0) return;
                              let timelineTime = 0;
                              for (let i = 0; i < idx; i++) {
                                        if (periods[i].type === 'HT') {
                                                  timelineTime += 2;
                                        } else {
                                                  timelineTime += (periods[i].end - periods[i].start);
                                        }
                              }
                              const percent = duration > 0 ? Math.min(100, Math.max(0, (timelineTime / duration) * 100)) : 0;
                              const markerLine = document.createElement('div');
                              markerLine.className = 'matrix-period-marker-line';
                              markerLine.style.left = `${percent}%`;
                              markerLine.tabIndex = 0;
                              markerLine.title = p.label || p.type;
                              const dot = document.createElement('div');
                              dot.className = 'matrix-period-marker-dot';
                              markerLine.appendChild(dot);
                              markersEl.appendChild(markerLine);
                    });

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
                              marker.dataset.annotationId = String(entry.id);
                              renderMarkerPosition(marker, entry, entry.timestamp);
                              marker.addEventListener('pointerdown', (event) => handleMarkerPointerDown(entry, marker, event));
                              marker.addEventListener('click', (event) => {
                                        event.preventDefault();
                                        event.stopPropagation();
                                        handleMarkerClick(entry);
                              });
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
