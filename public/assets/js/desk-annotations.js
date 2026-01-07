(function () {

          if (window.ANNOTATIONS_ENABLED === false) {
                    console.info('[annotations] disabled');
                    return;
          }

          const cfg = window.DeskConfig;
          if (!cfg || !cfg.matchId) {
                    return;
          }

          const endpoints = cfg.endpoints || {};
          const annotationsListUrl = endpoints.annotationsList;
          const drawingsCreateUrl = endpoints.drawingsCreate || endpoints.annotationsCreate;
          const drawingsUpdateUrl = endpoints.drawingsUpdate || endpoints.annotationsUpdate;
          const drawingsDeleteUrl = endpoints.drawingsDelete || endpoints.annotationsDelete;

          if (!annotationsListUrl || !drawingsCreateUrl || !drawingsUpdateUrl || !drawingsDeleteUrl) {
                    return;
          }

          const videoEl = document.getElementById('deskVideoPlayer');
          const canvasEl = document.querySelector('[data-annotation-canvas]');
          const overlayEl = document.querySelector('[data-annotation-overlay]');
          const toolbarEl = document.querySelector('[data-annotation-toolbar]');

          if (!videoEl || !canvasEl || !overlayEl || !toolbarEl) {
                    return;
          }

          const modeToggle = toolbarEl.querySelector('[data-annotation-mode-toggle]');
          const statusEl = toolbarEl.querySelector('[data-annotation-status]');
          const targetSelect = toolbarEl.querySelector('[data-annotation-target]');
          const clipGroup = toolbarEl.querySelector('[data-annotation-clip-group]');
          const toolButtons = Array.from(toolbarEl.querySelectorAll('[data-annotation-tool]'));
          const colorInput = toolbarEl.querySelector('[data-annotation-color]');
          const strokeInput = toolbarEl.querySelector('[data-annotation-stroke]');
          const deleteButton = toolbarEl.querySelector('[data-annotation-delete]');
          const editButton = toolbarEl.querySelector('[data-annotation-edit]');
          const visibilityToggle = toolbarEl.querySelector('[data-annotation-visibility-toggle]');
          const textInputWrapper = overlayEl.querySelector('[data-annotation-text-input]');
          const textInputField = overlayEl.querySelector('[data-annotation-text-field]');
          const textSaveBtn = overlayEl.querySelector('[data-annotation-text-save]');
          const textCancelBtn = overlayEl.querySelector('[data-annotation-text-cancel]');

          const drawingEditModal = document.querySelector('[data-drawing-edit-modal]');
          const drawingEditBeforeValue = drawingEditModal?.querySelector('[data-drawing-edit-before-value]');
          const drawingEditAfterValue = drawingEditModal?.querySelector('[data-drawing-edit-after-value]');
          const drawingEditToolLabel = drawingEditModal?.querySelector('[data-drawing-edit-tool]');
          const drawingEditTimestampLabel = drawingEditModal?.querySelector('[data-drawing-edit-timestamp]');
          const drawingEditNotes = drawingEditModal?.querySelector('[data-drawing-edit-notes]');
          const drawingEditSaveBtn = drawingEditModal?.querySelector('[data-drawing-edit-save]');
          const drawingEditDeleteBtn = drawingEditModal?.querySelector('[data-drawing-edit-delete]');
          const drawingEditCancelBtn = drawingEditModal?.querySelector('[data-drawing-edit-cancel]');
          const drawingEditCloseBtn = drawingEditModal?.querySelector('[data-drawing-edit-close]');
          const drawingEditDurationButtons = drawingEditModal
                    ? Array.from(drawingEditModal.querySelectorAll('[data-drawing-edit-duration]'))
                    : [];

          if (!modeToggle || !visibilityToggle || !targetSelect || !colorInput || !strokeInput || !deleteButton) {
                    return;
          }

          const matchId = cfg.matchId;
          const matchVideoId = cfg.annotations && cfg.annotations.matchVideoId ? cfg.annotations.matchVideoId : (cfg.video && cfg.video.match_video_id ? cfg.video.match_video_id : null);

          const state = {
                    editing: false,
                    targetType: matchVideoId ? 'match_video' : null,
                    targetId: matchVideoId || null,
                    tool: 'pen',
                    annotationsVisible: true,
                    color: colorInput.value || '#facc15',
                    strokeWidth: Number(strokeInput.value) || 4,
                    annotations: new Map(),
                    pendingFetches: new Map(),
                    visibleAnnotations: [],
                    selectedId: null,
                    isDrawing: false,
                    draft: null,
                    pointerId: null,
                    dragging: null,
                    textAnchor: null,
                    isDeleting: false,
          };
          const drawingEditState = {
                    annotationId: null,
                    beforeSeconds: DEFAULT_VISIBILITY_WINDOW_SECONDS,
                    afterSeconds: DEFAULT_VISIBILITY_WINDOW_SECONDS,
                    toolLabel: null,
                    timestamp: null,
          };
          const annotationVisibilityState = { visible: state.annotationsVisible };
          window.DeskAnnotationVisibilityState = annotationVisibilityState;

          const DEFAULT_VISIBILITY_WINDOW_SECONDS = 5;
          const RESIZE_HANDLE_THRESHOLD = 0.04;

          function parseTargetKey(key) {
                    if (!key || typeof key !== 'string') {
                              return null;
                    }
                    const parts = key.split(':');
                    if (parts.length < 2) {
                              return null;
                    }
                    const id = Number(parts[1]);
                    if (!Number.isFinite(id)) {
                              return null;
                    }
                    return { type: parts[0], id };
          }

          function findAnnotationById(annotationId) {
                    const normalizedId = Number(annotationId);
                    if (!Number.isFinite(normalizedId) || normalizedId <= 0) {
                              return null;
                    }
                    for (const entry of state.annotations.values()) {
                              if (!entry || !Array.isArray(entry.annotations)) {
                                        continue;
                              }
                              const match = entry.annotations.find((annotation) => Number(annotation.id) === normalizedId);
                              if (match) {
                                        return match;
                              }
                    }
                    return null;
          }

          const timelineBridge = (() => {
                   const subscribers = new Set();

                    function cloneAnnotations(annotations) {
                              return Array.isArray(annotations) ? annotations.slice() : [];
                    }

                    function emit(type, id, annotations) {
                              if (!type || !Number.isFinite(Number(id))) {
                                        return;
                              }
                              const payload = {
                                        type,
                                        id: Number(id),
                                        annotations: cloneAnnotations(annotations),
                              };
                              subscribers.forEach((cb) => {
                                        try {
                                                  cb(payload);
                                        } catch (error) {
                                                  console.error('DeskAnnotationTimelineBridge subscriber failure', error);
                                        }
                              });
                    }

                    return {
                              subscribe(cb) {
                                        if (typeof cb !== 'function') {
                                                  return () => {};
                                        }
                                        subscribers.add(cb);
                                        state.annotations.forEach((entry, key) => {
                                                  const parsed = parseTargetKey(key);
                                                  if (!parsed) {
                                                            return;
                                                  }
                                                  emit(parsed.type, parsed.id, entry.annotations);
                                        });
                                        return () => subscribers.delete(cb);
                              },
                              notify(type, id, annotations) {
                                        emit(type, id, annotations);
                              },
                             highlightAnnotation(annotationId) {
                                        const normalizedId = Number(annotationId);
                                        if (!Number.isFinite(normalizedId) || normalizedId <= 0) {
                                                  return false;
                                        }
                                        const annotation = findAnnotationById(normalizedId);
                                        if (annotation) {
                                                  handleSelection(annotation);
                                        } else {
                                                  handleSelection({ id: normalizedId });
                                        }
                                        return true;
                              },
                   };
         })();

          function updateCanvasSize() {
                    const rect = videoEl.getBoundingClientRect();
                    const width = Math.max(2, Math.round(rect.width));
                    const height = Math.max(2, Math.round(rect.height));
                    canvasEl.width = width;
                    canvasEl.height = height;
                    render();
          }

          function clamp(value, min, max) {
                    return Math.max(min, Math.min(max, value));
          }

          /**
           * Normalizes a pointer coordinate into [0,1] space relative to the overlay so geometry is stored independently of the actual canvas size.
           */
          function normalizePoint(clientX, clientY) {
                    const rect = overlayEl.getBoundingClientRect();
                    if (!rect.width || !rect.height) {
                              return { x: 0.5, y: 0.5 };
                    }
                    const x = clamp((clientX - rect.left) / rect.width, 0, 1);
                    const y = clamp((clientY - rect.top) / rect.height, 0, 1);
                    return { x, y };
          }

          function clampNormalizedPoint(point) {
                    if (!point || typeof point !== 'object') {
                              return { x: 0, y: 0 };
                    }
                    return {
                              x: clamp(typeof point.x === 'number' ? point.x : 0, 0, 1),
                              y: clamp(typeof point.y === 'number' ? point.y : 0, 0, 1),
                    };
          }

          function resolveAnnotationWindow(annotation) {
                    const timestamp = Number(annotation?.timestamp_second) || 0;
                    let beforeSeconds = Number(annotation?.show_before_seconds);
                    let afterSeconds = Number(annotation?.show_after_seconds);
                    if (!Number.isFinite(beforeSeconds) || beforeSeconds < 0) {
                              const derivedShowFrom = Number(annotation?.show_from_second);
                              if (Number.isFinite(derivedShowFrom)) {
                                        beforeSeconds = Math.max(0, timestamp - derivedShowFrom);
                              } else {
                                        beforeSeconds = null;
                              }
                    }
                    if (!Number.isFinite(afterSeconds) || afterSeconds < 0) {
                              const derivedShowTo = Number(annotation?.show_to_second);
                              if (Number.isFinite(derivedShowTo)) {
                                        afterSeconds = Math.max(0, derivedShowTo - timestamp);
                              } else {
                                        afterSeconds = null;
                              }
                    }
                    const safeBefore = Number.isFinite(beforeSeconds) ? Math.max(0, beforeSeconds) : DEFAULT_VISIBILITY_WINDOW_SECONDS;
                    const safeAfter = Number.isFinite(afterSeconds) ? Math.max(0, afterSeconds) : DEFAULT_VISIBILITY_WINDOW_SECONDS;
                    return { start: Math.max(0, timestamp - safeBefore), end: timestamp + safeAfter };
          }

          function formatTimestampLabel(seconds) {
                    const safeSeconds = Math.max(0, Math.round(seconds || 0));
                    const minutes = Math.floor(safeSeconds / 60);
                    const remainder = safeSeconds % 60;
                    return `${minutes}:${String(remainder).padStart(2, '0')}`;
          }

          function buildTargetKey(type, id) {
                    return `${type}:${id}`;
          }

          function describeTarget(type, id) {
                    if (type === 'match_video') {
                              return 'match video';
                    }
                    if (type === 'clip') {
                              return `clip #${id || 'unknown'}`;
                    }
                    return 'target';
          }

          function updateStatus(message) {
                    if (statusEl) {
                              statusEl.textContent = message;
                    }
          }

          function updateVisibilityState(visible) {
                    state.annotationsVisible = Boolean(visible);
                    visibilityToggle.classList.toggle('is-active', state.annotationsVisible);
                    visibilityToggle.textContent = state.annotationsVisible ? 'Hide annotations' : 'Show annotations';
                    overlayEl.style.opacity = state.annotationsVisible ? '1' : '0';
                    overlayEl.style.visibility = state.annotationsVisible ? 'visible' : 'hidden';
                    if (!state.annotationsVisible) {
                              overlayEl.style.pointerEvents = 'none';
                              canvasEl.style.pointerEvents = 'none';
                              state.draft = null;
                              hideTextInput();
                    }
                    annotationVisibilityState.visible = state.annotationsVisible;
                    window.dispatchEvent(
                              new CustomEvent('DeskAnnotationVisibilityChanged', {
                                        detail: { visible: state.annotationsVisible },
                              })
                    );
                    updateModeState(state.editing);
                    render();
          }

         function updateModeState(enabled) {
                   state.editing = Boolean(enabled);
                   modeToggle.classList.toggle('is-active', state.editing);
                   modeToggle.textContent = state.editing ? 'Disable editing' : 'Enable editing';
                   if (!state.annotationsVisible) {
                             overlayEl.style.pointerEvents = 'none';
                             canvasEl.style.pointerEvents = 'none';
                              updateDeleteState();
                             return;
                   }
                   const pointerState = state.editing ? 'auto' : 'none';
                   overlayEl.style.pointerEvents = pointerState;
                   canvasEl.style.pointerEvents = pointerState;
                    updateDeleteState();
         }

          function selectTool(toolName) {
                    state.tool = toolName;
                    toolButtons.forEach((btn) => {
                              btn.classList.toggle('is-active', btn.dataset.annotationTool === toolName);
                    });
          }

          function targetHasId() {
                    return state.targetType && state.targetId && state.targetId > 0;
          }

          function updateDeleteState() {
                    const canDelete = state.editing && state.selectedId && !state.isDeleting;
                    deleteButton.disabled = !canDelete;
                    updateEditButtonState();
          }

          function updateEditButtonState() {
                    if (!editButton) {
                              return;
                    }
                    const canEdit = Boolean(state.editing && state.selectedId);
                    editButton.disabled = !canEdit;
          }

          function removeAnnotationFromUnsaved(annotationId) {
                    if (!annotationId) {
                              return;
                    }
                    const normalizedId = Number(annotationId);
                    if (!Number.isFinite(normalizedId) || normalizedId <= 0) {
                              return;
                    }
                    const unsaved = state.unsaved;
                    if (!unsaved) {
                              return;
                    }
                    if (typeof unsaved.delete === 'function') {
                              unsaved.delete(normalizedId);
                              return;
                    }
                    if (Array.isArray(unsaved)) {
                              state.unsaved = unsaved.filter((entry) => Number(entry?.id) !== normalizedId);
                              return;
                    }
                    if (Object.prototype.hasOwnProperty.call(unsaved, normalizedId)) {
                              delete unsaved[normalizedId];
                    }
                    if (Object.prototype.hasOwnProperty.call(unsaved, String(normalizedId))) {
                              delete unsaved[String(normalizedId)];
                    }
          }

          function registerTargetSelection() {
                    if (targetSelect) {
                              targetSelect.value = state.targetType && state.targetId ? `${state.targetType}:${state.targetId}` : `match_video:${matchVideoId || 0}`;
                    }
          }

          function getAnnotationCache(key) {
                    return state.annotations.get(key) || null;
          }

          function setAnnotationCache(key, annotations) {
                    state.annotations.set(key, { annotations, timestamp: Date.now() });
                    const parsed = parseTargetKey(key);
                    if (parsed) {
                              timelineBridge.notify(parsed.type, parsed.id, annotations);
                    }
          }

          async function fetchAnnotations(type, id) {
                    const key = buildTargetKey(type, id);
                    if (state.pendingFetches.has(key)) {
                              return state.pendingFetches.get(key);
                    }
                    const url = new URL(annotationsListUrl, window.location.origin);
                    url.searchParams.set('match_id', matchId);
                    url.searchParams.set('target_type', type);
                    url.searchParams.set('target_id', String(id));
                    const request = fetch(url.toString(), {
                              credentials: 'same-origin',
                    })
                              .then((response) => response.json())
                              .then((payload) => {
                                        state.pendingFetches.delete(key);
                                        if (!payload || payload.ok !== true) {
                                                  throw new Error(payload?.error || 'annotations_fetch_failed');
                                        }
                                        return Array.isArray(payload.annotations) ? payload.annotations : [];
                              })
                              .catch((error) => {
                                        state.pendingFetches.delete(key);
                                        throw error;
                              });
                    state.pendingFetches.set(key, request);
                    return request;
          }

          async function ensureAnnotations(type, id) {
                    if (!type || !id) {
                              return;
                    }
                    const key = buildTargetKey(type, id);
                    const cached = getAnnotationCache(key);
                    if (cached) {
                              state.visibleAnnotations = cached.annotations;
                              render();
                              return;
                    }
                    try {
                              updateStatus('Loading annotations…');
                              const annotations = await fetchAnnotations(type, id);
                              setAnnotationCache(key, annotations);
                              state.visibleAnnotations = annotations;
                              updateStatus(`Annotations ready (${annotations.length})`);
                              render();
                    } catch (error) {
                              console.error('Failed to load annotations', error);
                              updateStatus('Unable to load annotations');
                    }
          }

          function toAbsolute(point) {
                    return {
                              x: (point.x || 0) * canvasEl.width,
                              y: (point.y || 0) * canvasEl.height,
                    };
          }

          function actualStrokeWidth(normalized) {
                    const sizeRef = Math.max(canvasEl.width, canvasEl.height, 1);
                    const value = normalized && normalized > 0 ? normalized : 0.01;
                    return Math.max(1, value * sizeRef);
          }

          function drawArrowHead(ctx, from, to, width) {
                    const angle = Math.atan2(to.y - from.y, to.x - from.x);
                    const headLength = Math.max(16, width * 3);
                    const br = Math.PI / 6;
                    ctx.beginPath();
                    ctx.moveTo(to.x, to.y);
                    ctx.lineTo(to.x - headLength * Math.cos(angle - br), to.y - headLength * Math.sin(angle - br));
                    ctx.lineTo(to.x - headLength * Math.cos(angle + br), to.y - headLength * Math.sin(angle + br));
                    ctx.closePath();
                    ctx.fill();
          }

          function renderAnnotation(ctx, annotation, options = {}) {
                    if (!annotation || !annotation.drawing_data) {
                              return;
                    }
                    const data = annotation.drawing_data;
                    const color = data.color || '#facc15';
                    const strokeWidth = actualStrokeWidth(data.strokeWidth);
                    ctx.save();
                    ctx.strokeStyle = color;
                    ctx.fillStyle = color;
                    ctx.lineWidth = strokeWidth;
                    ctx.lineCap = 'round';
                    ctx.lineJoin = 'round';

                    const tool = data.tool || annotation.tool || 'line';
                    const points = Array.isArray(data.points) ? data.points : [];
                    const absolutePoints = points
                              .map((point) => (point && typeof point === 'object' ? toAbsolute(point) : null))
                              .filter(Boolean);
                    const start = absolutePoints[0] || null;
                    const end = absolutePoints[1] || null;

                    const drawLineFn = () => {
                              if (start && end) {
                                        ctx.beginPath();
                                        ctx.moveTo(start.x, start.y);
                                        ctx.lineTo(end.x, end.y);
                                        ctx.stroke();
                              }
                    };

                    const drawPenPath = (opts = {}) => {
                              if (absolutePoints.length >= 2) {
                                        ctx.beginPath();
                                        ctx.moveTo(absolutePoints[0].x, absolutePoints[0].y);
                                        for (let i = 1; i < absolutePoints.length; i += 1) {
                                                  ctx.lineTo(absolutePoints[i].x, absolutePoints[i].y);
                                        }
                                        ctx.stroke();
                              } else if (absolutePoints[0]) {
                                        ctx.beginPath();
                                        ctx.arc(absolutePoints[0].x, absolutePoints[0].y, strokeWidth, 0, Math.PI * 2);
                                        if (opts.fillDot) {
                                                  ctx.fill();
                                        } else {
                                                  ctx.stroke();
                                        }
                              }
                    };

                    const drawRectangle = () => {
                              if (start && end) {
                                        const minX = Math.min(start.x, end.x);
                                        const minY = Math.min(start.y, end.y);
                                        const width = Math.abs(start.x - end.x);
                                        const height = Math.abs(start.y - end.y);
                                        ctx.strokeRect(minX, minY, width, height);
                              }
                    };

                    const drawEllipse = () => {
                              if (start && end) {
                                        const centerX = (start.x + end.x) / 2;
                                        const centerY = (start.y + end.y) / 2;
                                        const radiusX = Math.abs(start.x - end.x) / 2;
                                        const radiusY = Math.abs(start.y - end.y) / 2;
                                        if (!radiusX && !radiusY) {
                                                  return;
                                        }
                                        ctx.beginPath();
                                        if (typeof ctx.ellipse === 'function') {
                                                  ctx.ellipse(centerX, centerY, Math.max(radiusX, 0.5), Math.max(radiusY, 0.5), 0, 0, Math.PI * 2);
                                                  ctx.stroke();
                                                  return;
                                        }
                                        ctx.save();
                                        ctx.translate(centerX, centerY);
                                        const scaledRadiusY = Math.max(radiusY, 0.5);
                                        const scaleX = radiusX === 0 ? 1 : radiusX / scaledRadiusY;
                                        ctx.scale(scaleX, 1);
                                        ctx.arc(0, 0, scaledRadiusY, 0, Math.PI * 2);
                                        ctx.restore();
                                        ctx.stroke();
                              }
                    };

                    const drawArrow = () => {
                              drawLineFn();
                              if (start && end) {
                                        ctx.fillStyle = color;
                                        drawArrowHead(ctx, start, end, strokeWidth);
                              }
                    };

                    const drawText = (opts = {}) => {
                              const pos = data.position || points[0] || { x: 0.5, y: 0.5 };
                              const absolute = toAbsolute(pos);
                              const fontSize = Math.max(12, ((data.fontSize || 0.045) * canvasEl.height));
                              ctx.font = `${fontSize}px system-ui, sans-serif`;
                              ctx.textBaseline = 'middle';
                              if (opts.highlight) {
                                        ctx.strokeText(String(data.text || 'Annotation'), absolute.x, absolute.y);
                              } else {
                                        ctx.fillText(String(data.text || 'Annotation'), absolute.x, absolute.y);
                              }
                    };

                    const drawTool = (useHighlight = false) => {
                              switch (tool) {
                              case 'rectangle':
                                        drawRectangle();
                                        break;
                              case 'ellipse':
                              case 'circle':
                                        drawEllipse();
                                        break;
                                        case 'arrow':
                                                  drawArrow();
                                                  break;
                                        case 'line':
                                                  drawLineFn();
                                                  break;
                                        case 'text':
                                                  drawText({ highlight: useHighlight });
                                                  break;
                                        case 'pen':
                                                  drawPenPath({ fillDot: !useHighlight });
                                                  break;
                                        default:
                                                  drawLineFn();
                              }
                    };

                    drawTool(false);

                    if (options.highlight) {
                              ctx.save();
                              ctx.strokeStyle = 'rgba(255, 255, 255, 0.8)';
                              ctx.fillStyle = 'rgba(255, 255, 255, 0.8)';
                              ctx.lineWidth = strokeWidth + 2;
                              ctx.setLineDash([6, 4]);
                              drawTool(true);
                              ctx.setLineDash([]);
                              ctx.restore();
                    }

                    ctx.restore();
          }

          function render() {
                    const ctx = canvasEl.getContext('2d');
                    if (!ctx) {
                              return;
                    }
                    ctx.clearRect(0, 0, canvasEl.width, canvasEl.height);
                    if (!state.annotationsVisible) {
                              return;
                    }
                    const current = Math.round(videoEl.currentTime || 0);
                    const key = state.targetType && state.targetId ? buildTargetKey(state.targetType, state.targetId) : null;
                    const cached = key ? getAnnotationCache(key) : null;
                    const candidates = cached ? cached.annotations : [];
                    state.visibleAnnotations = candidates.filter((annotation) => {
                              if (!annotation) {
                                        return false;
                              }
                              if (!Number.isFinite(Number(annotation.timestamp_second))) {
                                        return false;
                              }
                              const window = resolveAnnotationWindow(annotation);
                              return current >= window.start && current <= window.end;
                    });
                    state.visibleAnnotations.forEach((annotation) => {
                              renderAnnotation(ctx, annotation, { highlight: annotation.id === state.selectedId });
                    });
                    if (state.draft) {
                              renderAnnotation(ctx, state.draft, { highlight: false });
                    }
          }

          function cloneDrawingData(original) {
                    if (!original || typeof original !== 'object') {
                              return null;
                    }
                    try {
                              return JSON.parse(JSON.stringify(original));
                    } catch (error) {
                              console.error('Unable to clone annotation drawing data', error);
                              return null;
                    }
          }

          function prepareDrawingForServer(drawingData) {
                    if (!drawingData || typeof drawingData !== 'object') {
                              return drawingData;
                    }
                    const normalized = cloneDrawingData(drawingData);
                    if (!normalized) {
                              return drawingData;
                    }
                    const toolName = (normalized.tool || normalized.tool_type || '').toLowerCase();
                    if (toolName === 'ellipse' || toolName === 'circle') {
                              const points = Array.isArray(normalized.points) ? normalized.points : [];
                              const start = points[0];
                              const end = points[1];
                              const width = start && end ? Math.abs(end.x - start.x) : normalized.width ?? 0;
                              const height = start && end ? Math.abs(end.y - start.y) : normalized.height ?? 0;
                              normalized.width = width;
                              normalized.height = height;
                    }
                    return normalized;
          }

          function updateDrawingHandle(baseDrawing, handleIndex, normalizedPoint) {
                    if (!baseDrawing || typeof handleIndex !== 'number') {
                              return null;
                    }
                    const updated = cloneDrawingData(baseDrawing);
                    if (!updated || !Array.isArray(updated.points)) {
                              return updated;
                    }
                    const points = updated.points.slice();
                    points[handleIndex] = clampNormalizedPoint(normalizedPoint);
                    updated.points = points;
                    return updated;
          }

          function detectResizeHandle(annotation, normalizedPoint) {
                    if (!annotation || !annotation.drawing_data) {
                              return null;
                    }
                    const data = annotation.drawing_data;
                    const tool = ((data.tool || annotation.tool_type) || '').toLowerCase();
                    const resizableTools = ['rectangle', 'line', 'ellipse', 'circle', 'arrow'];
                    if (!resizableTools.includes(tool)) {
                              return null;
                    }
                    const points = Array.isArray(data.points) ? data.points : [];
                    for (let index = 0; index < 2; index += 1) {
                              const point = points[index];
                              if (!point || typeof point.x !== 'number' || typeof point.y !== 'number') {
                                        continue;
                              }
                              const dx = normalizedPoint.x - point.x;
                              const dy = normalizedPoint.y - point.y;
                              if (Math.hypot(dx, dy) <= RESIZE_HANDLE_THRESHOLD) {
                                        return index;
                              }
                    }
                    return null;
          }

          function translateDrawingData(drawingData, delta) {
                    const base = cloneDrawingData(drawingData);
                    if (!base) {
                              return drawingData;
                    }
                    const movePoint = (point) => {
                              if (!point || typeof point.x !== 'number' || typeof point.y !== 'number') {
                                        return point;
                              }
                              return clampNormalizedPoint({ x: point.x + delta.x, y: point.y + delta.y });
                    };
                    if (Array.isArray(base.points)) {
                              base.points = base.points.map((point) => movePoint(point));
                    }
                    if (base.position) {
                              base.position = movePoint(base.position);
                    }
                    return base;
          }

          function hitTestPoint(x, y, annotation) {
                    if (!annotation || !annotation.drawing_data) {
                              return false;
                    }
                    const px = x;
                    const py = y;
                    const data = annotation.drawing_data;
                    const tolerance = actualStrokeWidth(data.strokeWidth || 0.01) + 4;
                    const points = Array.isArray(data.points) ? data.points : [];
                    const absolutePoints = points
                              .map((point) => (point && typeof point === 'object' ? toAbsolute(point) : null))
                              .filter(Boolean);
                    const absStart = absolutePoints[0] || null;
                    const absEnd = absolutePoints[1] || null;
                    const tool = data.tool || annotation.tool || 'line';

                    const distanceToSegment = (px, py, ax, ay, bx, by) => {
                              const dx = bx - ax;
                              const dy = by - ay;
                              if (!dx && !dy) {
                                        return Math.hypot(px - ax, py - ay);
                              }
                              const t = ((px - ax) * dx + (py - ay) * dy) / (dx * dx + dy * dy);
                              if (t < 0) {
                                        return Math.hypot(px - ax, py - ay);
                              }
                              if (t > 1) {
                                        return Math.hypot(px - bx, py - by);
                              }
                              const projX = ax + dx * t;
                              const projY = ay + dy * t;
                              return Math.hypot(px - projX, py - projY);
                    };

                    switch (tool) {
                              case 'rectangle':
                                        if (absStart && absEnd) {
                                                  const minX = Math.min(absStart.x, absEnd.x);
                                                  const minY = Math.min(absStart.y, absEnd.y);
                                                  const maxX = Math.max(absStart.x, absEnd.x);
                                                  const maxY = Math.max(absStart.y, absEnd.y);
                                                  return px >= minX - tolerance && px <= maxX + tolerance && py >= minY - tolerance && py <= maxY + tolerance;
                                        }
                                        break;
                              case 'ellipse':
                              case 'circle':
                                        if (absStart && absEnd) {
                                                  const centerX = (absStart.x + absEnd.x) / 2;
                                                  const centerY = (absStart.y + absEnd.y) / 2;
                                                  const radiusX = Math.abs(absStart.x - absEnd.x) / 2;
                                                  const radiusY = Math.abs(absStart.y - absEnd.y) / 2;
                                                  const safeRadiusX = Math.max(radiusX, 0.0001);
                                                  const safeRadiusY = Math.max(radiusY, 0.0001);
                                                  const normalizedX = (px - centerX) / safeRadiusX;
                                                  const normalizedY = (py - centerY) / safeRadiusY;
                                                  const distance = Math.sqrt(normalizedX * normalizedX + normalizedY * normalizedY);
                                                  const toleranceScale = Math.max(0.08, tolerance / Math.max(safeRadiusX, safeRadiusY));
                                                  return Math.abs(distance - 1) <= toleranceScale;
                                        }
                                        break;
                              case 'text':
                                        {
                                                  const anchor = data.position ? toAbsolute(data.position) : absStart;
                                                  if (anchor) {
                                                            const width = 150;
                                                            const height = 24;
                                                            return px >= anchor.x - tolerance && px <= anchor.x + width && py >= anchor.y - height && py <= anchor.y + height;
                                                  }
                                        }
                                        break;
                              case 'pen':
                                        if (absolutePoints.length >= 2) {
                                                  for (let i = 0; i < absolutePoints.length - 1; i += 1) {
                                                            const origin = absolutePoints[i];
                                                            const dest = absolutePoints[i + 1];
                                                            if (!origin || !dest) {
                                                                      continue;
                                                            }
                                                            const dist = distanceToSegment(px, py, origin.x, origin.y, dest.x, dest.y);
                                                            if (dist <= tolerance) {
                                                                      return true;
                                                            }
                                                  }
                                        } else if (absolutePoints[0]) {
                                                  const point = absolutePoints[0];
                                                  return Math.hypot(px - point.x, py - point.y) <= tolerance;
                                        }
                                        break;
                              default:
                                        if (absStart && absEnd) {
                                                  const dist = distanceToSegment(px, py, absStart.x, absStart.y, absEnd.x, absEnd.y);
                                                  return dist <= tolerance;
                                        }
                    }
                    return false;
          }

          function findAnnotationAt(x, y) {
                    return state.visibleAnnotations.find((annotation) => hitTestPoint(x, y, annotation));
          }

          function handleSelection(annotation) {
                    state.selectedId = annotation ? annotation.id : null;
                    updateDeleteState();
                    render();
          }

          function handleEditButtonClick() {
                    if (!state.selectedId) {
                              return;
                    }
                    openDrawingEditModal(state.selectedId);
          }

          function createDraftDrawing(normalized) {
                    // drawing_data stores normalized points and styling so annotations stay resolution-agnostic.
                    const normalizedStroke = state.strokeWidth / Math.max(canvasEl.width, canvasEl.height, 1);
                    const payload = {
                              tool: state.tool,
                              color: state.color,
                              strokeWidth: normalizedStroke,
                    };
                    if (state.tool === 'pen') {
                              return {
                                        drawing_data: {
                                                  ...payload,
                                                  points: [normalized],
                                        },
                              };
                    }
                    return {
                              drawing_data: {
                                        ...payload,
                                        points: [normalized, normalized],
                              },
                    };
          }

function startDrag(annotation, normalized, pointerId, options = {}) {
          if (!annotation || !annotation.drawing_data) {
                    return;
          }
          const baseDrawing = cloneDrawingData(annotation.drawing_data);
          if (!baseDrawing) {
                    return;
          }
          state.dragging = {
                    pointerId,
                    annotation,
                    start: normalized,
                    delta: { x: 0, y: 0 },
                    baseDrawing,
                    mode: options.mode === 'resize' ? 'resize' : 'move',
                    handleIndex: typeof options.handleIndex === 'number' ? options.handleIndex : null,
                    lastNormalized: normalized,
          };
          canvasEl.setPointerCapture(pointerId);
}

          function updateDragPoint(normalized) {
                    if (!state.dragging) {
                              return;
                    }
                    state.dragging.lastNormalized = normalized;
                    if (state.dragging.mode === 'resize' && typeof state.dragging.handleIndex === 'number') {
                              const updated = updateDrawingHandle(state.dragging.baseDrawing, state.dragging.handleIndex, normalized);
                              state.draft = updated ? { drawing_data: updated } : null;
                              render();
                              return;
                    }
                    const deltaX = normalized.x - state.dragging.start.x;
                    const deltaY = normalized.y - state.dragging.start.y;
                    state.dragging.delta = { x: deltaX, y: deltaY };
                    const translated = translateDrawingData(state.dragging.baseDrawing, state.dragging.delta);
                    state.draft = translated ? { drawing_data: translated } : null;
                    render();
          }

          function finalizeDrag(event) {
                    if (!state.dragging || event.pointerId !== state.dragging.pointerId) {
                              return;
                    }
                    canvasEl.releasePointerCapture(event.pointerId);
                    const drag = state.dragging;
                    state.dragging = null;
                    state.draft = null;
                    if (!drag) {
                              return;
                    }
                    let updatedData = null;
                    if (drag.mode === 'resize' && typeof drag.handleIndex === 'number' && drag.lastNormalized) {
                              updatedData =
                                        updateDrawingHandle(drag.baseDrawing, drag.handleIndex, drag.lastNormalized) ||
                                        drag.baseDrawing;
                    } else {
                              updatedData = translateDrawingData(drag.baseDrawing, drag.delta);
                    }
                    const timestamp = Math.max(0, Number(drag.annotation.timestamp_second || 0));
                    submitAnnotationUpdate(Number(drag.annotation.id), updatedData, timestamp);
          }

          function cancelDrag(event) {
                    if (!state.dragging || event.pointerId !== state.dragging.pointerId) {
                              return;
                    }
                    canvasEl.releasePointerCapture(event.pointerId);
                    state.dragging = null;
                    state.draft = null;
                    render();
          }

          function handleCanvasPointerDown(event) {
                    if (event.button !== 0 || !state.annotationsVisible) {
                              return;
                    }
                    if (!targetHasId()) {
                              updateStatus('Select a target before drawing');
                              return;
                    }
                    const { x, y } = normalizePoint(event.clientX, event.clientY);
                    const absX = x * canvasEl.width;
                    const absY = y * canvasEl.height;

          if (state.tool === 'select') {
                    const hit = findAnnotationAt(absX, absY);
                    handleSelection(hit);
                    if (state.editing && hit) {
                              const handleIndex = detectResizeHandle(hit, { x, y });
                              if (typeof handleIndex === 'number') {
                                        startDrag(hit, { x, y }, event.pointerId, { mode: 'resize', handleIndex });
                              } else {
                                        startDrag(hit, { x, y }, event.pointerId);
                              }
                    }
                    event.preventDefault();
                    return;
          }

                    if (state.tool === 'eraser') {
                              const hit = findAnnotationAt(absX, absY);
                              handleSelection(hit);
                              if (hit && state.editing) {
                                        handleDelete();
                              } else if (hit) {
                                        updateStatus('Enable editing to remove drawings');
                              }
                              event.preventDefault();
                              return;
                    }

                    if (!state.editing) {
                              return;
                    }

                    if (state.tool === 'text') {
                              openTextInput(event, { x, y });
                              event.preventDefault();
                              return;
                    }

                    if (!['pen', 'arrow', 'line', 'rectangle', 'circle', 'ellipse'].includes(state.tool)) {
                              return;
                    }

                    state.isDrawing = true;
                    state.pointerId = event.pointerId;
                    canvasEl.setPointerCapture(event.pointerId);
                    state.draft = createDraftDrawing({ x, y });
                    event.preventDefault();
                    render();
          }

          function handleCanvasPointerMove(event) {
                    if (state.dragging && event.pointerId === state.dragging.pointerId) {
                              const normalized = normalizePoint(event.clientX, event.clientY);
                              updateDragPoint(normalized);
                              event.preventDefault();
                              return;
                    }
                    if (!state.isDrawing || event.pointerId !== state.pointerId || !state.draft) {
                              return;
                    }
                    const { x, y } = normalizePoint(event.clientX, event.clientY);
                    const points = state.draft.drawing_data.points;
                    if (!Array.isArray(points)) {
                              return;
                    }
                    if (state.tool === 'pen') {
                              const last = points[points.length - 1];
                              const distance = last ? Math.hypot(last.x - x, last.y - y) : 0;
                              if (!last || distance >= 0.002) {
                                        points.push({ x, y });
                              }
                    } else if (points.length >= 2) {
                              points[1] = { x, y };
                    }
                    render();
          }

          function finalizeDrawing(event) {
                    if (!state.isDrawing || event.pointerId !== state.pointerId) {
                              return;
                    }
                    canvasEl.releasePointerCapture(event.pointerId);
                    state.isDrawing = false;
                    const draft = state.draft;
                    state.draft = null;
                    if (draft && draft.drawing_data) {
                              createAnnotation(draft.drawing_data);
                    }
          }

          function cancelDrawing(event) {
                    if (!state.isDrawing || event.pointerId !== state.pointerId) {
                              return;
                    }
                    canvasEl.releasePointerCapture(event.pointerId);
                    state.isDrawing = false;
                    state.draft = null;
                    render();
          }

          function finalizePointerInteraction(event) {
                    if (state.dragging) {
                              finalizeDrag(event);
                              return;
                    }
                    finalizeDrawing(event);
          }

          function cancelPointerInteraction(event) {
                    if (state.dragging) {
                              cancelDrag(event);
                              return;
                    }
                    cancelDrawing(event);
          }

          function openTextInput(event, anchor) {
                    if (!textInputWrapper || !textInputField) {
                              return;
                    }
                    const normalized = anchor || normalizePoint(event.clientX, event.clientY);
                    state.textAnchor = normalized;
                    const rect = overlayEl.getBoundingClientRect();
                    const left = clamp((event.clientX - rect.left) / rect.width, 0, 1) * rect.width;
                    const top = clamp((event.clientY - rect.top) / rect.height, 0, 1) * rect.height;
                    textInputWrapper.style.left = `${left}px`;
                    textInputWrapper.style.top = `${top}px`;
                    textInputWrapper.classList.add('is-active');
                    textInputField.value = '';
                    textInputField.focus();
          }

          function hideTextInput() {
                    if (!textInputWrapper) return;
                    textInputWrapper.classList.remove('is-active');
                    state.textAnchor = null;
          }

          function createAnnotation(drawingData) {
                    if (!targetHasId()) {
                              return;
                    }
                    const preparedDrawing = prepareDrawingForServer(drawingData);
                    const payload = {
                              match_id: matchId,
                              target_type: state.targetType,
                              target_id: state.targetId,
                              timestamp_second: Math.max(0, Math.round(videoEl.currentTime || 0)),
                              drawing_data: preparedDrawing,
                              before_seconds: DEFAULT_VISIBILITY_WINDOW_SECONDS,
                              after_seconds: DEFAULT_VISIBILITY_WINDOW_SECONDS,
                              show_before_seconds: DEFAULT_VISIBILITY_WINDOW_SECONDS,
                              show_after_seconds: DEFAULT_VISIBILITY_WINDOW_SECONDS,
                    };
                    const csrfToken = cfg.csrfToken;
                    updateStatus('Saving annotation…');
                    fetch(drawingsCreateUrl, {
                              method: 'POST',
                              credentials: 'same-origin',
                              headers: {
                                        'Content-Type': 'application/json',
                                        ...(csrfToken ? { 'X-CSRF-Token': csrfToken } : {}),
                              },
                              body: JSON.stringify(payload),
                    })
                              .then((res) => res.json())
                              .then((res) => {
                                        if (!res || res.ok !== true || !res.annotation) {
                                                  throw new Error(res?.error || 'annotation_create_failed');
                                        }
                                        const key = buildTargetKey(state.targetType, state.targetId);
                                        const cached = getAnnotationCache(key);
                                        const updated = cached ? [...cached.annotations, res.annotation] : [res.annotation];
                                        const sorted = updated.slice().sort((a, b) => {
                                                  const aTime = Number(a.timestamp_second) || 0;
                                                  const bTime = Number(b.timestamp_second) || 0;
                                                  if (aTime !== bTime) {
                                                            return aTime - bTime;
                                                  }
                                                  return Number(a.id) - Number(b.id);
                                        });
                                        setAnnotationCache(key, sorted);
                                        state.visibleAnnotations = sorted;
                                        updateStatus(`Annotation saved`);
                                        handleSelection(res.annotation);
                                        render();
                              })
                              .catch((error) => {
                                        console.error('Annotation save failed', error);
                                        updateStatus('Failed to save annotation');
                              });
          }

          function submitAnnotationUpdate(annotationId, drawingData, timestamp, options = {}) {
                    if (!targetHasId() || !drawingsUpdateUrl) {
                              updateStatus('Unable to update annotation');
                              return;
                   }
                   const payload = {
                             match_id: matchId,
                             target_type: state.targetType,
                             target_id: state.targetId,
                             annotation_id: annotationId,
                             timestamp_second: Math.max(0, Math.round(timestamp || 0)),
                              drawing_data: prepareDrawingForServer(drawingData),
                   };
                    if (options && typeof options.notes === 'string') {
                              payload.notes = options.notes.trim();
                    } else if (options && options.notes === null) {
                              payload.notes = null;
                    }
                    if (options && typeof options.beforeSeconds === 'number') {
                              const value = Math.max(0, Math.round(options.beforeSeconds));
                              payload.before_seconds = value;
                              payload.show_before_seconds = value;
                    }
                    if (options && typeof options.afterSeconds === 'number') {
                              const value = Math.max(0, Math.round(options.afterSeconds));
                              payload.after_seconds = value;
                              payload.show_after_seconds = value;
                    }
                   const csrfToken = cfg.csrfToken;
                   updateStatus('Saving annotation…');
                    fetch(drawingsUpdateUrl, {
                              method: 'POST',
                              credentials: 'same-origin',
                              headers: {
                                        'Content-Type': 'application/json',
                                        ...(csrfToken ? { 'X-CSRF-Token': csrfToken } : {}),
                              },
                              body: JSON.stringify(payload),
                    })
                              .then((res) => res.json())
                              .then((res) => {
                                        if (!res || res.ok !== true || !res.annotation) {
                                                  throw new Error(res?.error || 'annotation_update_failed');
                                        }
                                        const key = buildTargetKey(state.targetType, state.targetId);
                                        const cached = getAnnotationCache(key);
                                        const updatedList = cached
                                                  ? cached.annotations.map((annotation) => (annotation.id === res.annotation.id ? res.annotation : annotation))
                                                  : [res.annotation];
                                        const sorted = updatedList.slice().sort((a, b) => {
                                                  const aTime = Number(a.timestamp_second) || 0;
                                                  const bTime = Number(b.timestamp_second) || 0;
                                                  if (aTime !== bTime) {
                                                            return aTime - bTime;
                                                  }
                                                  return Number(a.id) - Number(b.id);
                                        });
                                        setAnnotationCache(key, sorted);
                                        state.visibleAnnotations = sorted;
                                        updateStatus('Annotation updated');
                                        handleSelection(res.annotation);
                                        render();
                              })
                              .catch((error) => {
                                        console.error('Annotation update failed', error);
                                        updateStatus('Failed to update annotation');
                              });
          }

          function handleTextSave() {
                    if (!state.textAnchor) {
                              hideTextInput();
                              return;
                    }
                    const textValue = (textInputField.value || '').trim();
                    hideTextInput();
                    if (!textValue) {
                              return;
                    }
                    const drawingData = {
                              tool: 'text',
                              color: state.color,
                              strokeWidth: state.strokeWidth / Math.max(canvasEl.width, canvasEl.height, 1),
                              fontSize: Math.max(0.02, state.strokeWidth / Math.max(canvasEl.height, 1)),
                              position: state.textAnchor,
                              text: textValue,
                    };
                    createAnnotation(drawingData);
          }

          function handleTextCancel() {
                    hideTextInput();
          }

          function updateDrawingModalValues() {
                    if (!drawingEditModal) {
                              return;
                    }
                    drawingEditBeforeValue && (drawingEditBeforeValue.textContent = String(drawingEditState.beforeSeconds));
                    drawingEditAfterValue && (drawingEditAfterValue.textContent = String(drawingEditState.afterSeconds));
                    if (drawingEditToolLabel) {
                              const toolText = drawingEditState.toolLabel ? `Tool: ${drawingEditState.toolLabel}` : 'Tool: Drawing';
                              drawingEditToolLabel.textContent = toolText;
                    }
                    if (drawingEditTimestampLabel) {
                              const timeText =
                                        drawingEditState.timestamp !== null
                                                  ? `Time: ${formatTimestampLabel(drawingEditState.timestamp)}`
                                                  : 'Time: 0:00';
                              drawingEditTimestampLabel.textContent = timeText;
                    }
          }

          function closeDrawingEditModal() {
                    if (!drawingEditModal) {
                              return;
                    }
                    drawingEditModal.classList.remove('is-active');
                    drawingEditModal.setAttribute('aria-hidden', 'true');
                    drawingEditState.annotationId = null;
                    drawingEditState.beforeSeconds = DEFAULT_VISIBILITY_WINDOW_SECONDS;
                    drawingEditState.afterSeconds = DEFAULT_VISIBILITY_WINDOW_SECONDS;
                    drawingEditState.toolLabel = null;
                    drawingEditState.timestamp = null;
                    updateDrawingModalValues();
          }

          function openDrawingEditModal(annotationId) {
                    if (!drawingEditModal) {
                              return;
                    }
                    const annotation = findAnnotationById(annotationId);
                    if (!annotation) {
                              return;
                    }
                    const windowRange = resolveAnnotationWindow(annotation);
                    const timestamp = Number(annotation.timestamp_second) || 0;
                    drawingEditState.annotationId = Number(annotation.id) || null;
                    drawingEditState.beforeSeconds = Math.max(0, timestamp - windowRange.start);
                    drawingEditState.afterSeconds = Math.max(0, windowRange.end - timestamp);
                    const rawTool = annotation.tool_type || annotation.drawing_data?.tool || 'drawing';
                    const normalizedTool = String(rawTool || 'drawing').replace(/_/g, ' ').trim();
                    const labelText = normalizedTool || 'drawing';
                    drawingEditState.toolLabel = labelText.charAt(0).toUpperCase() + labelText.slice(1);
                    drawingEditState.timestamp = timestamp;
                    drawingEditModal.classList.add('is-active');
                    drawingEditModal.setAttribute('aria-hidden', 'false');
                    updateDrawingModalValues();
                    if (drawingEditNotes) {
                              drawingEditNotes.value = annotation.notes || '';
                    }
                    if (!state.editing) {
                              updateModeState(true);
                    }
                    handleSelection(annotation);
          }

          function handleDrawingEditDurationClick(event) {
                    const button = event.currentTarget;
                    const mode = button?.dataset?.mode;
                    const step = Number(button?.dataset?.step) || 0;
                    if (!mode || !Number.isFinite(step)) {
                              return;
                    }
                    const key = mode === 'before' ? 'beforeSeconds' : mode === 'after' ? 'afterSeconds' : null;
                    if (!key) {
                              return;
                    }
                    drawingEditState[key] = Math.max(0, drawingEditState[key] + step);
                    updateDrawingModalValues();
          }

          function handleDrawingEditSave() {
                    const annotationId = drawingEditState.annotationId;
                    if (!annotationId) {
                              return;
                    }
                    const annotation = findAnnotationById(annotationId);
                    if (!annotation) {
                              return;
                    }
                    const timestamp = Number(annotation.timestamp_second) || 0;
                    const drawingData = annotation.drawing_data || null;
                    const notesValue = drawingEditNotes ? (drawingEditNotes.value || '').trim() : null;
                    closeDrawingEditModal();
                    submitAnnotationUpdate(annotationId, drawingData, timestamp, {
                              notes: notesValue,
                              beforeSeconds: drawingEditState.beforeSeconds,
                              afterSeconds: drawingEditState.afterSeconds,
                    });
          }

          function handleDrawingEditDelete() {
                    const annotationId = drawingEditState.annotationId;
                    if (!annotationId) {
                              return;
                    }
                    const annotation = findAnnotationById(annotationId);
                    if (annotation) {
                              handleSelection(annotation);
                    }
                    closeDrawingEditModal();
                    handleDelete();
          }

          function handleDrawingEditCancel() {
                    closeDrawingEditModal();
          }

          if (drawingEditModal) {
                    drawingEditDurationButtons.forEach((btn) => {
                              btn.addEventListener('click', handleDrawingEditDurationClick);
                    });
                    drawingEditSaveBtn && drawingEditSaveBtn.addEventListener('click', handleDrawingEditSave);
                    drawingEditDeleteBtn && drawingEditDeleteBtn.addEventListener('click', handleDrawingEditDelete);
                    drawingEditCancelBtn && drawingEditCancelBtn.addEventListener('click', handleDrawingEditCancel);
                    drawingEditCloseBtn && drawingEditCloseBtn.addEventListener('click', handleDrawingEditCancel);
                    drawingEditModal.addEventListener('click', (event) => {
                              if (event.target === drawingEditModal) {
                                        closeDrawingEditModal();
                              }
                    });
                    window.addEventListener('keydown', (event) => {
                              if (event.key === 'Escape') {
                                        closeDrawingEditModal();
                              }
                    });
          }

          function handleDelete() {
                    if (!state.selectedId || !targetHasId() || state.isDeleting) {
                              return;
                    }
                    const selectedId = state.selectedId;
                    const payload = {
                              match_id: matchId,
                              annotation_id: selectedId,
                    };
                    const csrfToken = cfg.csrfToken;
                    updateStatus('Removing drawing…');
                    state.isDeleting = true;
                    updateDeleteState();
                    fetch(drawingsDeleteUrl, {
                              method: 'POST',
                              credentials: 'same-origin',
                              headers: {
                                        'Content-Type': 'application/json',
                                        ...(csrfToken ? { 'X-CSRF-Token': csrfToken } : {}),
                              },
                              body: JSON.stringify(payload),
                    })
                              .then((response) => response.json().then((payload) => ({ response, payload })))
                              .then(({ response, payload }) => {
                                        if (!payload || payload.ok !== true) {
                                                  const failure = new Error(payload?.error || 'drawing_delete_failed');
                                                  failure.payload = payload;
                                                  failure.status = response?.status;
                                                  throw failure;
                                        }
                                        const key = buildTargetKey(state.targetType, state.targetId);
                                        const cached = getAnnotationCache(key);
                                        const remaining = cached
                                                  ? cached.annotations.filter((annotation) => Number(annotation.id) !== Number(selectedId))
                                                  : [];
                                        setAnnotationCache(key, remaining);
                                        state.visibleAnnotations = remaining;
                                        removeAnnotationFromUnsaved(selectedId);
                                        const parsedTarget = parseTargetKey(key);
                                        if (parsedTarget && typeof timelineBridge.notify === 'function') {
                                                  timelineBridge.notify(parsedTarget.type, parsedTarget.id, remaining);
                                        }
                                        state.isDeleting = false;
                                        handleSelection(null);
                                        hideTextInput();
                                        updateStatus('Drawing deleted');
                              })
                              .catch((error) => {
                                        const errorPayload = error?.payload ?? null;
                                        const reasonLabel = errorPayload?.error ? ` (${errorPayload.error})` : '';
                                        console.error('Drawing delete failed', {
                                                  status: error?.status ?? null,
                                                  payload: errorPayload,
                                                  message: error?.message ?? 'annotation_delete_failed',
                                        });
                                        state.isDeleting = false;
                                        updateDeleteState();
                                        updateStatus(`Failed to delete annotation${reasonLabel}`);
                              });
          }

          function handleTargetChange() {
                    const value = targetSelect.value;
                    if (!value) {
                              return;
                    }
                    const [type, id] = value.split(':');
                    const targetId = Number(id) || 0;
                    state.targetType = type || null;
                    state.targetId = targetId > 0 ? targetId : null;
                    state.selectedId = null;
                    updateDeleteState();
                    if (targetHasId()) {
                              ensureAnnotations(state.targetType, state.targetId);
                              updateStatus(`Target: ${describeTarget(state.targetType, state.targetId)}`);
                    } else {
                              updateStatus('Select a valid annotation target');
                    }
          }

          function populateClipOptions() {
                    if (!endpoints.events) {
                              return;
                    }
                    const url = new URL(endpoints.events, window.location.origin);
                    url.searchParams.set('match_id', matchId);
                    fetch(url.toString(), { credentials: 'same-origin' })
                              .then((res) => res.json())
                              .then((payload) => {
                                        if (!payload || payload.ok !== true || !Array.isArray(payload.events)) {
                                                  return;
                                        }
                                        const clips = payload.events.filter((event) => event.clip_id);
                                        if (!clipGroup) {
                                                  return;
                                        }
                                        clipGroup.innerHTML = '';
                                        clips.forEach((clip) => {
                                                  const option = document.createElement('option');
                                                  option.value = `clip:${clip.clip_id}`;
                                                  const label = clip.clip_name || clip.event_type_label || 'Clip';
                                                  const timeLabel = clip.clip_start_second ? ` @ ${Math.floor(clip.clip_start_second / 60)}:${String(clip.clip_start_second % 60).padStart(2, '0')}` : '';
                                                  option.textContent = `Clip #${clip.clip_id} – ${label}${timeLabel}`;
                                                  clipGroup.appendChild(option);
                                        });
                              })
                              .catch(() => { /* ignore */ });
          }

          modeToggle.addEventListener('click', () => {
                    if (!targetHasId()) {
                              updateStatus('Pick a valid target first');
                              return;
                    }
                    if (!state.annotationsVisible) {
                              updateVisibilityState(true);
                    }
                    const nextEditing = !state.editing;
                    updateModeState(nextEditing);
                    updateStatus(nextEditing ? 'Annotation mode active' : 'Annotation mode disabled');
          });

          visibilityToggle.addEventListener('click', () => {
                    const nextVisibility = !state.annotationsVisible;
                    updateVisibilityState(nextVisibility);
                    updateStatus(nextVisibility ? 'Annotations visible' : 'Annotations hidden');
          });

          toolButtons.forEach((btn) => {
                    btn.addEventListener('click', () => {
                              selectTool(btn.dataset.annotationTool);
                    });
          });

          colorInput.addEventListener('change', () => {
                    state.color = colorInput.value;
          });

          strokeInput.addEventListener('input', () => {
                    state.strokeWidth = Number(strokeInput.value) || 4;
          });

          deleteButton.addEventListener('click', handleDelete);
          editButton && editButton.addEventListener('click', handleEditButtonClick);
          targetSelect.addEventListener('change', handleTargetChange);
          canvasEl.addEventListener('pointerdown', handleCanvasPointerDown);
          canvasEl.addEventListener('pointermove', handleCanvasPointerMove);
          canvasEl.addEventListener('pointerup', finalizePointerInteraction);
          canvasEl.addEventListener('pointerleave', cancelPointerInteraction);
          canvasEl.addEventListener('pointercancel', cancelPointerInteraction);
          textSaveBtn && textSaveBtn.addEventListener('click', handleTextSave);
          textCancelBtn && textCancelBtn.addEventListener('click', handleTextCancel);

          videoEl.addEventListener('pointerdown', (event) => {
                    if (state.editing || !state.annotationsVisible) {
                              return;
                    }
                    const { x, y } = normalizePoint(event.clientX, event.clientY);
                    const absX = x * canvasEl.width;
                    const absY = y * canvasEl.height;
                    const hit = findAnnotationAt(absX, absY);
                    handleSelection(hit);
          });

          videoEl.addEventListener('loadedmetadata', updateCanvasSize);
          videoEl.addEventListener('resize', updateCanvasSize);
          videoEl.addEventListener('timeupdate', render);
          videoEl.addEventListener('seeked', render);
          window.addEventListener('resize', () => {
                    updateCanvasSize();
          });

          updateCanvasSize();
          registerTargetSelection();
          populateClipOptions();
          if (targetHasId()) {
                    ensureAnnotations(state.targetType, state.targetId);
          } else {
                    updateStatus('Annotations unavailable without a video target');
          }
          updateDeleteState();
          updateVisibilityState(true);
          updateModeState(false);
          window.DeskAnnotationTimelineBridge = timelineBridge;
          window.addEventListener('DeskDrawingEditRequested', (event) => {
                    const annotationId = Number(event?.detail?.annotationId);
                    if (!Number.isFinite(annotationId) || annotationId <= 0) {
                              return;
                    }
                    openDrawingEditModal(annotationId);
          });
          window.dispatchEvent(new CustomEvent('DeskAnnotationTimelineReady'));
})();
