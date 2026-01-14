(function () {
  // Configuration
  const DRAW_CONFIG = {
    // Pencil defaults used for initial toolbar state
    pencil: {
      thickness: 4,
      color: '#facc15',
    },
    spotlight: {
      // Vertical beam gradient stops (bottom → top)
      verticalGradientStops: [
        { offset: 0, color: 'rgba(255,255,255,0.58)' },
        { offset: 0.25, color: 'rgba(255,255,255,0.44)' },
        { offset: 0.45, color: 'rgba(255,255,255,0.34)' },
        { offset: 0.7, color: 'rgba(255,255,255,0.22)' },
        { offset: 1, color: 'rgba(255,255,255,0)' },
      ],
      // Horizontal softness mask stops (left → right)
      horizontalGradientStops: [
        { offset: 0, color: 'rgba(255,255,255,0)' },
        { offset: 0.25, color: 'rgba(255,255,255,0.25)' },
        { offset: 0.5, color: 'rgba(255,255,255,0.45)' },
        { offset: 0.75, color: 'rgba(255,255,255,0.25)' },
        { offset: 1, color: 'rgba(255,255,255,0)' },
      ],
      // Ellipse glow radial gradient stops
      ellipseGradientStops: [
        { offset: 0, color: 'rgba(255,255,255,0.45)' },
        { offset: 0.5, color: 'rgba(255,255,255,0.15)' },
        { offset: 1, color: 'rgba(255,255,255,0)' },
      ],
      // Ellipse fade mask stops (bottom → top)
      ellipseMaskStops: [
        { offset: 1, color: 'rgba(255,255,255,1)' },
        { offset: 0.5, color: 'rgba(255,255,255,0.5)' },
        { offset: 0, color: 'rgba(255,255,255,0)' },
      ],
      minBeamWidth: 30,
    },
  };

  const POLYGON_CLOSE_THRESHOLD = 10;
  const POLYGON_CLOSE_THRESHOLD_SQ = POLYGON_CLOSE_THRESHOLD * POLYGON_CLOSE_THRESHOLD;
  const TEXT_FONT_FAMILY = '"Inter", "Segoe UI", sans-serif';
  const TEXT_FONT_WEIGHT = 500;
  const TEXT_LINE_HEIGHT_RATIO = 1.35;
  const TEXT_PADDING = 8;
  const TEXT_COLOR = '#ffffff';
  const TEXT_BACKGROUND = 'rgba(0,0,0,0.6)';

  // DOM lookups
  const overlay = document.querySelector('[data-annotation-overlay]');
  const canvas = document.querySelector('[data-annotation-canvas]');
  const toolbar = document.querySelector('[data-annotation-toolbar]');
  const pencilButton = document.getElementById('deskPencilTool');
  const circleButton = document.getElementById('deskCircleTool');
  const spotlightButton = document.getElementById('deskSpotlightTool');

  console.log('Drawing system restored');
  console.log('Pencil exists:', !!pencilButton);
  console.log('Circle exists:', !!circleButton);
  console.log('Spotlight exists:', !!spotlightButton);

  if (!overlay || !canvas || !toolbar || !pencilButton || !circleButton || !spotlightButton) {
    return;
  }

  const thicknessButtons = Array.from(toolbar.querySelectorAll('[data-pencil-thickness]'));
  const colourButtons = Array.from(toolbar.querySelectorAll('[data-pencil-color]'));
  const circleModeButtons = Array.from(toolbar.querySelectorAll('[data-circle-mode]'));
  const arrowButtons = Array.from(toolbar.querySelectorAll('[data-arrow-type]'));
  const shapeButtons = Array.from(toolbar.querySelectorAll('[data-shape-type]'));
  const menuButtons = Array.from(toolbar.querySelectorAll('[data-toolbar-button][data-menu-key]'));
  const menuPanels = Array.from(toolbar.querySelectorAll('[data-toolbar-menu]'));
  const menuPanelMap = menuPanels.reduce((acc, panel) => {
    const key = panel.dataset.toolbarMenu;
    if (key) {
      acc[key] = panel;
    }
    return acc;
  }, {});
  const undoButton = toolbar.querySelector('[data-drawing-tool="undo"]');
  const redoButton = toolbar.querySelector('[data-drawing-tool="redo"]');
  const textButton = toolbar.querySelector('[data-drawing-tool="text"]');
  const textFontSizeInput = toolbar.querySelector('[data-text-font-size]');
  const textFontSizeOutput = toolbar.querySelector('[data-text-font-size-value]');
  const ctx = canvas.getContext('2d');

  if (!ctx) {
    return;
  }

  const textEditor = document.createElement('div');
  textEditor.className = 'annotation-text-editor';
  textEditor.setAttribute('contenteditable', 'true');
  textEditor.setAttribute('spellcheck', 'false');
  textEditor.dataset.visible = 'false';
  overlay.appendChild(textEditor);

  // State
  const state = {
    activeTool: null,
    pencilThickness: DRAW_CONFIG.pencil.thickness,
    pencilColor: DRAW_CONFIG.pencil.color,
    circleMode: 'solid',
    arrowType: 'pass',
    shapeType: 'ellipse',
    drawHistory: [],
    redoStack: [],
    drawing: null,
    currentAction: null,
    polygonPreviewPoint: null,
    textFontSize: 18,
    textEditingIndex: null,
  };

  window.DeskDrawingState = state;

  const isToolActive = (tool) => state.activeTool === tool;

  const pointerState = { pointerId: null };

  const menuKeys = Object.keys(menuPanelMap);
  const hoverCounts = menuKeys.reduce((acc, key) => {
    acc[key] = 0;
    return acc;
  }, {});
  let hoverTool = null;
  let pinnedTool = null;

  const getActiveMenuKey = () => hoverTool || pinnedTool;

  const handleMenuHoverEnter = (menuKey) => {
    if (!menuKey || !(menuKey in hoverCounts)) {
      return;
    }
    hoverCounts[menuKey] += 1;
    hoverTool = menuKey;
    updateMenuVisibility();
  };

  const handleMenuHoverLeave = (menuKey) => {
    if (!menuKey || !(menuKey in hoverCounts)) {
      return;
    }
    hoverCounts[menuKey] = Math.max(0, hoverCounts[menuKey] - 1);
    if (hoverCounts[menuKey] === 0 && hoverTool === menuKey) {
      hoverTool = null;
    }
    updateMenuVisibility();
  };

  const registerMenuHoverEvents = (target, menuKey) => {
    if (!target || !menuKey || !(menuKey in hoverCounts)) {
      return;
    }
    target.addEventListener('pointerenter', () => handleMenuHoverEnter(menuKey));
    target.addEventListener('pointerleave', () => handleMenuHoverLeave(menuKey));
  };

  menuButtons.forEach((button) => {
    const key = button.dataset.menuKey;
    registerMenuHoverEvents(button, key);
    const panel = menuPanelMap[key];
    if (panel) {
      registerMenuHoverEvents(panel, key);
    }
  });

  const updateMenuVisibility = () => {
    const activeMenu = getActiveMenuKey();
    menuPanels.forEach((panel) => {
      const key = panel.dataset.toolbarMenu;
      const isVisible = key && activeMenu === key;
      panel.classList.toggle('is-visible', isVisible);
      panel.setAttribute('aria-hidden', isVisible ? 'false' : 'true');
      if (isVisible) {
        panel.removeAttribute('inert');
      } else {
        panel.setAttribute('inert', '');
      }
    });
    menuButtons.forEach((button) => {
      const key = button.dataset.menuKey;
      const isPinned = key && pinnedTool === key;
      const isOpen = key && activeMenu === key;
      button.classList.toggle('is-active', isPinned);
      button.classList.toggle('is-open', isOpen);
      if (typeof button.getAttribute('aria-pressed') === 'string') {
        button.setAttribute('aria-pressed', isPinned ? 'true' : 'false');
      }
    });
    // Keep the toolbar open when a menu is active/pinned
    if (toolbar) {
      toolbar.classList.toggle('is-open', !!activeMenu);
    }
  };

  const togglePinnedTool = (menuKey) => {
    if (!menuKey || !(menuKey in hoverCounts)) {
      return;
    }
    const alreadyPinned = pinnedTool === menuKey;
    if (alreadyPinned) {
      pinnedTool = null;
      hoverCounts[menuKey] = 0;
      hoverTool = null;
    } else {
      pinnedTool = menuKey;
    }
    updateMenuVisibility();
  };

  const closeMenus = () => {
    hoverTool = null;
    pinnedTool = null;
    Object.keys(hoverCounts).forEach((key) => {
      hoverCounts[key] = 0;
    });
    updateMenuVisibility();
  };

  // Canvas helpers
  const updateCanvasSize = () => {
    const rect = overlay.getBoundingClientRect();
    if (!rect.width || !rect.height) {
      return;
    }
    const ratio = window.devicePixelRatio || 1;
    canvas.width = rect.width * ratio;
    canvas.height = rect.height * ratio;
    ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
    render();
  };

  const hexToRgb = (value) => {
    const cleaned = (value || '').replace('#', '').trim();
    const expanded = cleaned.length === 3
      ? cleaned.split('').map((char) => char + char).join('')
      : cleaned;
    if (!/^[0-9a-fA-F]{6}$/.test(expanded)) {
      return { r: 0, g: 0, b: 0 };
    }
    const numeric = parseInt(expanded, 16);
    return {
      r: (numeric >> 16) & 0xff,
      g: (numeric >> 8) & 0xff,
      b: numeric & 0xff,
    };
  };

  const rgba = (hex, alpha) => {
    const { r, g, b } = hexToRgb(hex);
    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
  };

  // Drawing routines
  const drawStroke = (action) => {
    if (!action.points || !action.points.length) {
      return;
    }
    ctx.save();
    ctx.lineWidth = action.thickness;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    ctx.strokeStyle = action.color;
    if (action.points.length === 1) {
      const point = action.points[0];
      ctx.beginPath();
      ctx.arc(point.x, point.y, action.thickness / 2, 0, Math.PI * 2);
      ctx.fillStyle = action.color;
      ctx.fill();
      ctx.restore();
      return;
    }
    ctx.beginPath();
    ctx.moveTo(action.points[0].x, action.points[0].y);
    for (let i = 1; i < action.points.length; i += 1) {
      ctx.lineTo(action.points[i].x, action.points[i].y);
    }
    ctx.stroke();
    ctx.restore();
  };

  const drawEllipse = (action) => {
    if (!action || action.width <= 0 || action.height <= 0) {
      return;
    }
    ctx.save();
    const centerX = action.x + action.width / 2;
    const centerY = action.y + action.height / 2;
    if (action.mode === 'solid') {
      ctx.beginPath();
      ctx.ellipse(centerX, centerY, action.width / 2, action.height / 2, 0, 0, Math.PI * 2);
      ctx.fillStyle = rgba(action.color, 0.6);
      ctx.fill();
    } else if (action.mode === 'hollow') {
      const beamWidth = Math.max(action.width, 1);
      const beamHeight = Math.max(action.height, 1);
      const beamLeft = action.x;
      const beamTop = 0;
      const beamBottom = beamTop + beamHeight;

      ctx.save();
      ctx.beginPath();
      ctx.rect(beamLeft, beamTop, beamWidth, beamHeight);
      ctx.clip();

      const beamGradient = ctx.createLinearGradient(0, beamBottom, 0, beamTop);
      DRAW_CONFIG.spotlight.verticalGradientStops.forEach(({ offset, color }) => {
        beamGradient.addColorStop(offset, color);
      });

      ctx.globalCompositeOperation = 'source-over';
      ctx.fillStyle = beamGradient;
      ctx.fillRect(beamLeft, beamTop, beamWidth, beamHeight);

      ctx.globalCompositeOperation = 'destination-in';
      const horizontalGradient = ctx.createLinearGradient(beamLeft, 0, beamLeft + beamWidth, 0);
      DRAW_CONFIG.spotlight.horizontalGradientStops.forEach(({ offset, color }) => {
        horizontalGradient.addColorStop(offset, color);
      });
      ctx.fillStyle = horizontalGradient;
      ctx.fillRect(beamLeft, beamTop, beamWidth, beamHeight);

      ctx.restore();

      // ✅ existing bottom ellipse (unchanged)
      const ellipseCenterX = beamLeft + beamWidth / 2;
      const ellipseCenterY = beamBottom;
      const ellipseRadiusX = Math.max(beamWidth * 0.9, 30);
      const ellipseRadiusY = Math.max(beamWidth * 0.25, 12);

      const ellipseGradient = ctx.createRadialGradient(
        ellipseCenterX,
        ellipseCenterY,
        0,
        ellipseCenterX,
        ellipseCenterY,
        ellipseRadiusX
      );

      DRAW_CONFIG.spotlight.ellipseGradientStops.forEach(({ offset, color }) => {
        ellipseGradient.addColorStop(offset, color);
      });

      ctx.beginPath();
      ctx.ellipse(
        ellipseCenterX,
        ellipseCenterY,
        ellipseRadiusX,
        ellipseRadiusY,
        0,
        0,
        Math.PI * 2
      );
      ctx.fillStyle = ellipseGradient;
      ctx.fill();
      const ellipseMaskGradient = ctx.createLinearGradient(
        ellipseCenterX,
        ellipseCenterY,
        ellipseCenterX,
        ellipseCenterY - ellipseRadiusY,
      );
      DRAW_CONFIG.spotlight.ellipseMaskStops.forEach(({ offset, color }) => {
        ellipseMaskGradient.addColorStop(offset, color);
      });
      ctx.save();
      ctx.globalCompositeOperation = 'destination-in';
      ctx.fillStyle = ellipseMaskGradient;
      ctx.fillRect(
        ellipseCenterX - ellipseRadiusX,
        ellipseCenterY - ellipseRadiusY,
        ellipseRadiusX * 2,
        ellipseRadiusY,
      );
      ctx.restore();


    } else {
      const radiusX = action.width / 2;
      const radiusY = action.height / 2;
      const top = centerY - radiusY;
      const bottom = centerY + radiusY;
      ctx.save();
      ctx.beginPath();
      ctx.ellipse(centerX, centerY, radiusX, radiusY, 0, 0, Math.PI * 2);
      ctx.clip();

      const radial = ctx.createRadialGradient(centerX, centerY, 0, centerX, centerY, Math.max(radiusX, radiusY));
      radial.addColorStop(0, 'rgba(255,255,255,0.32)');
      radial.addColorStop(0.6, 'rgba(255,255,255,0.12)');
      radial.addColorStop(1, 'rgba(255,255,255,0)');
      ctx.fillStyle = radial;
      ctx.fillRect(centerX - radiusX, top, radiusX * 2, radiusY * 2);

      const vertical = ctx.createLinearGradient(centerX, top, centerX, bottom);
      vertical.addColorStop(0, 'rgba(255,255,255,0)');
      vertical.addColorStop(0.35, 'rgba(255,255,255,0.18)');
      vertical.addColorStop(0.5, 'rgba(255,255,255,0.35)');
      vertical.addColorStop(0.65, 'rgba(255,255,255,0.18)');
      vertical.addColorStop(1, 'rgba(255,255,255,0)');
      ctx.globalCompositeOperation = 'destination-in';
      ctx.fillStyle = vertical;
      ctx.fillRect(centerX - radiusX, top, radiusX * 2, radiusY * 2);

      ctx.globalCompositeOperation = 'source-over';
      ctx.restore();

      ctx.beginPath();
      ctx.ellipse(centerX, centerY, radiusX, radiusY, 0, 0, Math.PI * 2);
      ctx.lineWidth = Math.max(1, (action.thickness || 3) * 0.4);
      ctx.strokeStyle = 'rgba(255,255,255,0.25)';
      ctx.stroke();
    }
    ctx.restore();
  };

  const drawLine = (action) => {
    if (!action) {
      return;
    }
    ctx.save();
    ctx.beginPath();
    ctx.moveTo(action.startX, action.startY);
    ctx.lineTo(action.endX, action.endY);
    ctx.strokeStyle = action.color;
    ctx.lineWidth = action.thickness;
    ctx.lineCap = 'round';
    ctx.stroke();
    ctx.restore();
  };

  const drawRectangle = (action) => {
    if (!action || typeof action.width !== 'number' || typeof action.height !== 'number') {
      return;
    }
    const width = action.width;
    const height = action.height;
    if (width === 0 && height === 0) {
      return;
    }
    const x = width >= 0 ? action.startX : action.startX + width;
    const y = height >= 0 ? action.startY : action.startY + height;
    ctx.save();
    ctx.strokeStyle = action.color;
    ctx.lineWidth = action.thickness;
    ctx.lineCap = 'round';
    ctx.strokeRect(x, y, Math.abs(width), Math.abs(height));
    ctx.restore();
  };

  const drawPolygon = (action, previewPoint = null) => {
    if (!action || !Array.isArray(action.points) || action.points.length === 0) {
      return;
    }
    const points = action.points;
    ctx.save();
    ctx.lineWidth = action.thickness;
    ctx.strokeStyle = action.color;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    ctx.beginPath();
    ctx.moveTo(points[0].x, points[0].y);
    for (let i = 1; i < points.length; i += 1) {
      ctx.lineTo(points[i].x, points[i].y);
    }
    const shouldPreviewLine = previewPoint && !action.isClosed;
    if (shouldPreviewLine) {
      ctx.lineTo(previewPoint.x, previewPoint.y);
    }
    if (action.isClosed) {
      ctx.closePath();
      ctx.fillStyle = rgba(action.color, 0.3);
      ctx.fill();
    }
    ctx.stroke();
    ctx.restore();

    ctx.save();
    ctx.fillStyle = action.color;
    points.forEach((pt) => {
      ctx.beginPath();
      ctx.arc(pt.x, pt.y, 3, 0, Math.PI * 2);
      ctx.fill();
    });
    if (shouldPreviewLine) {
      ctx.beginPath();
      ctx.arc(previewPoint.x, previewPoint.y, 3, 0, Math.PI * 2);
      ctx.fill();
    }
    ctx.restore();
  };

  const drawSpotlight = (action) => {
    if (!action || !action.width || typeof action.groundY !== 'number' || action.groundY <= 0) {
      return;
    }
    const beamWidth = Math.max(action.width, 1);
    const beamHeight = Math.max(1, Math.min(action.groundY, canvas.height));
    const beamLeft = action.centerX - beamWidth / 2;
    const beamTop = 0;
    const beamColor = action.color || '#ffffff';

    ctx.save();
    ctx.beginPath();
    ctx.rect(beamLeft, beamTop, beamWidth, beamHeight);
    ctx.clip();

    const verticalGradient = ctx.createLinearGradient(0, beamHeight, 0, beamTop);
    verticalGradient.addColorStop(0, rgba(beamColor, 0.5));
    verticalGradient.addColorStop(1, rgba(beamColor, 0));
    ctx.fillStyle = verticalGradient;
    ctx.fillRect(beamLeft, beamTop, beamWidth, beamHeight);

    ctx.globalCompositeOperation = 'destination-in';
    const horizontalGradient = ctx.createLinearGradient(beamLeft, 0, beamLeft + beamWidth, 0);
    horizontalGradient.addColorStop(0, 'transparent');
    horizontalGradient.addColorStop(0.5, rgba(beamColor, 0.9));
    horizontalGradient.addColorStop(1, 'transparent');
    ctx.fillStyle = horizontalGradient;
    ctx.fillRect(beamLeft, beamTop, beamWidth, beamHeight);
    ctx.restore();

    const ellipseWidth = beamWidth * 1.2;
    const ellipseHeight = beamWidth * 0.4;
    const ellipseCenterX = action.centerX;
    const ellipseCenterY = beamHeight;
    const radiusX = ellipseWidth / 2;
    const radiusY = ellipseHeight / 2;

    const ellipseGradient = ctx.createRadialGradient(
      ellipseCenterX,
      ellipseCenterY,
      0,
      ellipseCenterX,
      ellipseCenterY,
      Math.max(radiusX, radiusY)
    );
    ellipseGradient.addColorStop(0, rgba(beamColor, 0.6));
    ellipseGradient.addColorStop(1, rgba(beamColor, 0));

    ctx.save();
    ctx.fillStyle = ellipseGradient;
    ctx.beginPath();
    ctx.ellipse(ellipseCenterX, ellipseCenterY, radiusX, radiusY, 0, 0, Math.PI * 2);
    ctx.fill();
    ctx.restore();
  };

  const updateTextMetrics = (action) => {
    if (!action) {
      return action;
    }
    const rawText = typeof action.text === 'string' ? action.text : '';
    const lines = rawText.length === 0 ? [''] : rawText.split('\n');
    ctx.save();
    ctx.font = `${action.fontWeight || TEXT_FONT_WEIGHT} ${action.fontSize}px ${TEXT_FONT_FAMILY}`;
    let maxWidth = 0;
    lines.forEach((line) => {
      const measurement = ctx.measureText(line || ' ');
      maxWidth = Math.max(maxWidth, measurement.width);
    });
    ctx.restore();
    const lineHeight = action.fontSize * TEXT_LINE_HEIGHT_RATIO;
    action.lines = lines;
    action.lineHeight = lineHeight;
    action.width = maxWidth + TEXT_PADDING * 2;
    action.height = lineHeight * lines.length + TEXT_PADDING * 2;
    action.padding = TEXT_PADDING;
    action.color = action.color || TEXT_COLOR;
    action.background = action.background || TEXT_BACKGROUND;
    action.fontWeight = action.fontWeight || TEXT_FONT_WEIGHT;
    return action;
  };

  const drawText = (action) => {
    if (!action || typeof action.x !== 'number' || typeof action.y !== 'number') {
      return;
    }
    if (!action.lines || !action.lines.length) {
      return;
    }
    const padding = action.padding ?? TEXT_PADDING;
    const width = action.width || 0;
    const height = action.height || 0;
    if (!width || !height) {
      return;
    }
    ctx.save();
    ctx.font = `${action.fontWeight || TEXT_FONT_WEIGHT} ${action.fontSize}px ${TEXT_FONT_FAMILY}`;
    ctx.textBaseline = 'top';
    ctx.fillStyle = action.background || TEXT_BACKGROUND;
    ctx.fillRect(action.x - padding, action.y - padding, width, height);
    ctx.fillStyle = action.color || TEXT_COLOR;
    const lineHeight = action.lineHeight || action.fontSize * TEXT_LINE_HEIGHT_RATIO;
    action.lines.forEach((line, idx) => {
      ctx.fillText(line, action.x, action.y + idx * lineHeight);
    });
    ctx.restore();
  };

  // Rendering
  const render = () => {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    state.drawHistory.forEach((action, index) => {
      if (action.type === 'text' && state.textEditingIndex === index) {
        return;
      }
      if (action.type === 'stroke') {
        drawStroke(action);
      } else if (action.type === 'ellipse') {
        drawEllipse(action);
      } else if (action.type === 'spotlight') {
        drawSpotlight(action);
      } else if (action.type === 'line') {
        drawLine(action);
      } else if (action.type === 'rectangle') {
        drawRectangle(action);
      } else if (action.type === 'polygon') {
        drawPolygon(action);
      } else if (action.type === 'text') {
        drawText(action);
      }
    });
    if (state.currentAction) {
      if (state.currentAction.type === 'stroke') {
        drawStroke(state.currentAction);
      } else if (state.currentAction.type === 'ellipse') {
        drawEllipse(state.currentAction);
      } else if (state.currentAction.type === 'spotlight') {
        drawSpotlight(state.currentAction);
      } else if (state.currentAction.type === 'line') {
        drawLine(state.currentAction);
      } else if (state.currentAction.type === 'rectangle') {
        drawRectangle(state.currentAction);
      } else if (state.currentAction.type === 'polygon') {
        drawPolygon(state.currentAction, state.polygonPreviewPoint);
      } else if (state.currentAction.type === 'text') {
        drawText(state.currentAction);
      }
    }
  };

  const isTextEditorVisible = () => textEditor.dataset.visible === 'true';

  const focusTextEditorCaret = () => {
    textEditor.focus();
    const selection = window.getSelection();
    if (!selection) {
      return;
    }
    const range = document.createRange();
    range.selectNodeContents(textEditor);
    range.collapse(false);
    selection.removeAllRanges();
    selection.addRange(range);
  };

  const repositionTextEditor = () => {
    if (!state.currentAction || state.currentAction.type !== 'text') {
      return;
    }
    const action = state.currentAction;
    const left = Math.max(0, action.x - TEXT_PADDING);
    const top = Math.max(0, action.y - TEXT_PADDING);
    textEditor.style.left = `${left}px`;
    textEditor.style.top = `${top}px`;
    textEditor.style.width = `${Math.max(120, action.width)}px`;
    textEditor.style.height = `${Math.max(24, action.height)}px`;
    textEditor.style.fontSize = `${action.fontSize}px`;
    textEditor.style.lineHeight = `${(action.lineHeight / action.fontSize).toFixed(2)}`;
  };

  const hideTextEditor = () => {
    textEditor.dataset.visible = 'false';
    textEditor.style.display = 'none';
  };

  const showTextEditor = (action, editIndex = null) => {
    const prepared = updateTextMetrics({
      ...action,
      fontSize: action.fontSize || state.textFontSize,
      fontWeight: action.fontWeight || TEXT_FONT_WEIGHT,
      color: action.color || TEXT_COLOR,
      background: action.background || TEXT_BACKGROUND,
      text: action.text || '',
    });
    state.currentAction = prepared;
    state.drawing = 'text';
    state.textEditingIndex = typeof editIndex === 'number' ? editIndex : null;
    state.textFontSize = prepared.fontSize;
    updateTextSizeDisplay();
    textEditor.innerText = prepared.text;
    textEditor.dataset.visible = 'true';
    textEditor.style.display = 'block';
    repositionTextEditor();
    focusTextEditorCaret();
    render();
  };

  const updateTextSizeDisplay = () => {
    if (textFontSizeInput) {
      textFontSizeInput.value = state.textFontSize;
    }
    if (textFontSizeOutput) {
      textFontSizeOutput.textContent = `${state.textFontSize}px`;
    }
  };

  const commitPendingText = () => {
    if (!isTextEditorVisible()) {
      return false;
    }
    const committed = commitCurrentAction();
    hideTextEditor();
    return committed;
  };

  const cancelTextEditing = () => {
    hideTextEditor();
    state.currentAction = null;
    state.drawing = null;
    state.textEditingIndex = null;
    render();
  };

  const syncTextEditorContent = () => {
    if (!state.currentAction || state.currentAction.type !== 'text') {
      return;
    }
    state.currentAction.text = textEditor.innerText || '';
    updateTextMetrics(state.currentAction);
    repositionTextEditor();
    render();
  };

  const getTextActionAtPoint = (point) => {
    for (let i = state.drawHistory.length - 1; i >= 0; i -= 1) {
      const action = state.drawHistory[i];
      if (action.type !== 'text') {
        continue;
      }
      const padding = action.padding ?? TEXT_PADDING;
      const left = action.x - padding;
      const top = action.y - padding;
      const width = action.width || 0;
      const height = action.height || 0;
      if (width > 0 && height > 0
        && point.x >= left
        && point.x <= left + width
        && point.y >= top
        && point.y <= top + height) {
        return { action, index: i };
      }
    }
    return null;
  };

  textEditor.addEventListener('input', syncTextEditorContent);
  textEditor.addEventListener('keydown', (event) => {
    if (event.key === 'Enter' && !event.shiftKey) {
      event.preventDefault();
      commitPendingText();
      return;
    }
    if (event.key === 'Escape') {
      event.preventDefault();
      cancelTextEditing();
    }
  });
  textEditor.addEventListener('pointerdown', (event) => event.stopPropagation());
  textEditor.addEventListener('click', (event) => event.stopPropagation());

  if (textFontSizeInput) {
    textFontSizeInput.addEventListener('input', () => {
      const parsed = Math.round(Number(textFontSizeInput.value)) || state.textFontSize;
      state.textFontSize = Math.min(48, Math.max(12, parsed));
      updateTextSizeDisplay();
      if (state.currentAction && state.currentAction.type === 'text') {
        state.currentAction.fontSize = state.textFontSize;
        updateTextMetrics(state.currentAction);
        repositionTextEditor();
        render();
      }
    });
  }

  // Toolbar logic
  const updateToolbarVisuals = () => {
    if (pencilButton) {
      const pencilActive = isToolActive('pencil');
      pencilButton.classList.toggle('is-active', pencilActive);
      pencilButton.setAttribute('aria-pressed', pencilActive ? 'true' : 'false');
    }
    if (circleButton) {
      const circleActive = isToolActive('circle');
      circleButton.classList.toggle('is-active', circleActive);
      circleButton.setAttribute('aria-pressed', circleActive ? 'true' : 'false');
    }
    if (spotlightButton) {
      const spotlightActive = isToolActive('spotlight');
      spotlightButton.classList.toggle('is-active', spotlightActive);
      spotlightButton.setAttribute('aria-pressed', spotlightActive ? 'true' : 'false');
    }
    thicknessButtons.forEach((button) => {
      const thickness = Number(button.dataset.pencilThickness);
      button.classList.toggle('is-selected', thickness === state.pencilThickness);
    });
    colourButtons.forEach((button) => {
      button.classList.toggle('is-selected', button.dataset.pencilColor === state.pencilColor);
    });
    circleModeButtons.forEach((button) => {
      button.classList.toggle('is-selected', button.dataset.circleMode === state.circleMode);
    });
    arrowButtons.forEach((button) => {
      button.classList.toggle('is-selected', button.dataset.arrowType === state.arrowType);
    });
    shapeButtons.forEach((button) => {
      button.classList.toggle('is-selected', button.dataset.shapeType === state.shapeType);
    });
    if (textButton) {
      const textActive = isToolActive('text');
      textButton.classList.toggle('is-active', textActive);
      textButton.setAttribute('aria-pressed', textActive ? 'true' : 'false');
    }
    if (undoButton) {
      undoButton.disabled = state.drawHistory.length === 0;
    }
    if (redoButton) {
      redoButton.disabled = state.redoStack.length === 0;
    }
    updateTextSizeDisplay();
  };

  const commitCurrentAction = () => {
    if (!state.currentAction) {
      state.drawing = null;
      updateToolbarVisuals();
      return false;
    }
    if (state.currentAction.type === 'ellipse' && (state.currentAction.width < 4 || state.currentAction.height < 4)) {
      state.currentAction = null;
      state.drawing = null;
      updateToolbarVisuals();
      render();
      return false;
    }
    if (state.currentAction.type === 'polygon' && !state.currentAction.isClosed) {
      state.polygonPreviewPoint = null;
      state.drawing = null;
      updateToolbarVisuals();
      render();
      return false;
    }
    let actionToStore = state.currentAction;
    if (state.currentAction.type === 'ellipse') {
      const { startX, startY, ...persisted } = state.currentAction;
      actionToStore = persisted;
    } else if (state.currentAction.type === 'spotlight') {
      const { startX, startY, ...persisted } = state.currentAction;
      actionToStore = persisted;
    } else if (state.currentAction.type === 'line') {
      actionToStore = state.currentAction;
    } else if (state.currentAction.type === 'rectangle') {
      const width = Math.abs(state.currentAction.width || 0);
      const height = Math.abs(state.currentAction.height || 0);
      if (width === 0 && height === 0) {
        state.currentAction = null;
        state.drawing = null;
        updateToolbarVisuals();
        render();
        return false;
      }
      actionToStore = state.currentAction;
    } else if (state.currentAction.type === 'polygon') {
      actionToStore = state.currentAction;
    } else if (state.currentAction.type === 'text') {
      const trimmedText = (state.currentAction.text || '').trim();
      if (!trimmedText) {
        state.currentAction = null;
        state.drawing = null;
        state.textEditingIndex = null;
        updateToolbarVisuals();
        render();
        return false;
      }
      actionToStore = { ...state.currentAction };
      if (typeof state.textEditingIndex === 'number') {
        state.drawHistory[state.textEditingIndex] = actionToStore;
      } else {
        state.drawHistory.push(actionToStore);
      }
      state.textEditingIndex = null;
      state.currentAction = null;
      state.drawing = null;
      state.redoStack = [];
      updateToolbarVisuals();
      render();
      return true;
    }
    state.currentAction = null;
    state.polygonPreviewPoint = null;
    state.drawing = null;
    state.redoStack = [];
    state.drawHistory.push(actionToStore);
    updateToolbarVisuals();
    render();
    return true;
  };

  const handleUndo = () => {
    if (!state.drawHistory.length) {
      return;
    }
    const action = state.drawHistory.pop();
    state.redoStack.push(action);
    render();
    updateToolbarVisuals();
  };

  const handleRedo = () => {
    if (!state.redoStack.length) {
      return;
    }
    const action = state.redoStack.pop();
    state.drawHistory.push(action);
    render();
    updateToolbarVisuals();
  };

  const setActiveTool = (tool) => {
    const validTools = ['pencil', 'circle', 'spotlight', 'line', 'polygon', 'text', null];
    if (!validTools.includes(tool)) {
      return;
    }
    const wasTextActive = state.activeTool === 'text';
    const nextTool = tool === state.activeTool ? null : tool;
    state.activeTool = nextTool;
    if (wasTextActive && nextTool !== 'text') {
      commitPendingText();
    }
    if (nextTool !== 'text') {
      hideTextEditor();
      state.textEditingIndex = null;
    }
    state.drawing = null;
    state.currentAction = null;
    state.polygonPreviewPoint = null;
    console.log('Active tool:', state.activeTool);
  };

  const handleToolbarClick = (event) => {
    const insideTextPanel = !!event.target.closest('[data-toolbar-menu=\"text\"]');
    if (!insideTextPanel && !textEditor.contains(event.target)) {
      commitPendingText();
    }
    const menuToggleButton = event.target.closest('[data-toolbar-button][data-menu-key]');
    const menuKey = menuToggleButton?.dataset?.menuKey;
    if (menuToggleButton) {
      togglePinnedTool(menuKey);
    }
    const toolElement = event.target.closest('[data-drawing-tool]');
    if (toolElement) {
      const toolName = toolElement.dataset.drawingTool;
      if (toolName === 'undo') {
        handleUndo();
        return;
      }
      if (toolName === 'redo') {
        handleRedo();
        return;
      }
      if (toolName === 'pencil' || toolName === 'circle') {
        setActiveTool(toolName);
        updateToolbarVisuals();
        if (!menuKey) {
          closeMenus();
        }
        return;
      }
      if (toolName === 'spotlight') {
        setActiveTool('spotlight');
        updateToolbarVisuals();
        if (!menuKey) {
          closeMenus();
        }
        return;
      }
      if (toolName === 'text') {
        setActiveTool('text');
        updateToolbarVisuals();
        return;
      }
      if (toolName === 'delete') {
        closeMenus();
        return;
      }
      return;
    }
    const thicknessTarget = event.target.closest('[data-pencil-thickness]');
    if (thicknessTarget) {
      const thicknessValue = Number(thicknessTarget.dataset.pencilThickness) || state.pencilThickness;
      state.pencilThickness = thicknessValue;
      updateToolbarVisuals();
      return;
    }
    const colourTarget = event.target.closest('[data-pencil-color]');
    if (colourTarget) {
      state.pencilColor = colourTarget.dataset.pencilColor || state.pencilColor;
      updateToolbarVisuals();
      return;
    }
    const arrowTarget = event.target.closest('[data-arrow-type]');
    if (arrowTarget) {
      const arrowValue = arrowTarget.dataset.arrowType;
      if (arrowValue) {
        state.arrowType = arrowValue;
        updateToolbarVisuals();
      }
      return;
    }
    const shapeTarget = event.target.closest('[data-shape-type]');
    if (shapeTarget) {
      const shapeValue = shapeTarget.dataset.shapeType;
      if (shapeValue) {
        if (shapeValue === 'line') {
          setActiveTool('line');
          state.drawing = null;
          state.currentAction = null;
          closeMenus();
        } else if (shapeValue === 'polygon') {
          setActiveTool('polygon');
          state.drawing = null;
          state.currentAction = null;
          state.polygonPreviewPoint = null;
          closeMenus();
        } else if (shapeValue === 'ellipse') {
          setActiveTool('circle');
        }
        state.shapeType = shapeValue;
        updateToolbarVisuals();
      }
    }
    const circleModeTarget = event.target.closest('[data-circle-mode]');
    if (circleModeTarget) {
      const modeValue = circleModeTarget.dataset.circleMode;
      if (modeValue === 'solid' || modeValue === 'hollow') {
        state.circleMode = modeValue;
        updateToolbarVisuals();
      }
    }
  };

  const handleDocumentClick = (event) => {
    const clickedInTextPanel = !!event.target.closest('[data-toolbar-menu="text"]');
    const clickedInEditor = textEditor.contains(event.target);
    if (!clickedInEditor && !clickedInTextPanel) {
      commitPendingText();
    }
    if (event.target.closest('[data-annotation-toolbar]')) {
      return;
    }
    closeMenus();
  };

  // Pointer handling
  const getCanvasPoint = (event) => {
    const rect = canvas.getBoundingClientRect();
    return {
      x: event.clientX - rect.left,
      y: event.clientY - rect.top,
    };
  };

  const getDistanceSquared = (a, b) => {
    if (!a || !b) {
      return Infinity;
    }
    const dx = a.x - b.x;
    const dy = a.y - b.y;
    return dx * dx + dy * dy;
  };

  const handlePointerDown = (event) => {
    const activeTool = state.activeTool;
    if (!activeTool) {
      return;
    }
    if (activeTool === 'text' && isTextEditorVisible()) {
      commitPendingText();
    }
    if (event.button !== 0 || state.drawing) {
      return;
    }
    const point = getCanvasPoint(event);
    pointerState.pointerId = event.pointerId;
    canvas.setPointerCapture?.(event.pointerId);
    if (activeTool === 'text') {
      const existing = getTextActionAtPoint(point);
      if (existing) {
        showTextEditor(existing.action, existing.index);
      } else {
        showTextEditor({
          type: 'text',
          text: '',
          x: point.x,
          y: point.y,
          fontSize: state.textFontSize,
          fontWeight: TEXT_FONT_WEIGHT,
          color: TEXT_COLOR,
          background: TEXT_BACKGROUND,
        });
      }
      event.preventDefault();
      return;
    }
    if (activeTool === 'pencil') {
      state.drawing = 'pencil';
      state.currentAction = {
        type: 'stroke',
        color: state.pencilColor,
        thickness: state.pencilThickness,
        points: [point],
      };
    } else if (activeTool === 'circle') {
      state.drawing = 'circle';
      state.currentAction = {
        type: 'ellipse',
        color: state.pencilColor,
        mode: state.circleMode,
        thickness: state.pencilThickness,
        x: point.x,
        y: point.y,
        width: 0,
        height: 0,
        startX: point.x,
        startY: point.y,
      };
    } else if (activeTool === 'spotlight') {
      state.drawing = 'spotlight';
      state.currentAction = {
        type: 'spotlight',
        color: '#ffffff',
        centerX: point.x,
        startX: point.x,
        startY: point.y,
        width: DRAW_CONFIG.spotlight.minBeamWidth,
        groundY: point.y,
      };
    } else if (activeTool === 'line') {
      state.drawing = 'line';
      state.currentAction = {
        type: 'line',
        color: state.pencilColor,
        thickness: 4,
        startX: point.x,
        startY: point.y,
        endX: point.x,
        endY: point.y,
      };
    } else if (activeTool === 'polygon') {
      const polygonAction = state.currentAction;
      const shouldClose = polygonAction
        && polygonAction.type === 'polygon'
        && !polygonAction.isClosed
        && polygonAction.points.length >= 3
        && getDistanceSquared(point, polygonAction.points[0]) <= POLYGON_CLOSE_THRESHOLD_SQ;
      if (shouldClose) {
        polygonAction.isClosed = true;
        state.polygonPreviewPoint = null;
        state.drawing = 'polygon';
        commitCurrentAction();
        event.preventDefault();
        return;
      }
      state.polygonPreviewPoint = null;
      if (!polygonAction || polygonAction.type !== 'polygon' || polygonAction.isClosed) {
        state.currentAction = {
          type: 'polygon',
          color: state.pencilColor,
          thickness: state.pencilThickness,
          points: [point],
          isClosed: false,
        };
      } else {
        polygonAction.points.push(point);
      }
      state.drawing = 'polygon';
    }
    updateToolbarVisuals();
    render();
    event.preventDefault();
  };

  const handlePointerMove = (event) => {
    if (event.pointerId !== pointerState.pointerId || !state.currentAction) {
      return;
    }
    const point = getCanvasPoint(event);
    if (state.currentAction.type === 'stroke') {
      state.currentAction.points.push(point);
    } else if (state.currentAction.type === 'ellipse') {
      const startX = typeof state.currentAction.startX === 'number'
        ? state.currentAction.startX
        : state.currentAction.x;
      if (state.currentAction.mode === 'hollow') {
        const width = Math.max(1, Math.abs(point.x - startX));
        const vertical = Math.max(1, Math.max(0, point.y));
        state.currentAction.width = width;
        state.currentAction.height = vertical;
        state.currentAction.x = Math.min(point.x, startX);
        state.currentAction.y = 0;
      } else {
        const startY = typeof state.currentAction.startY === 'number'
          ? state.currentAction.startY
          : state.currentAction.y;
        const width = Math.abs(point.x - startX);
        const height = Math.abs(point.y - startY);
        state.currentAction.width = width;
        state.currentAction.height = height;
        state.currentAction.x = Math.min(point.x, startX);
        state.currentAction.y = Math.min(point.y, startY);
      }
    } else if (state.currentAction.type === 'spotlight') {
      const startX = typeof state.currentAction.startX === 'number'
        ? state.currentAction.startX
        : state.currentAction.centerX;
      const minWidth = DRAW_CONFIG.spotlight.minBeamWidth;
      const width = Math.max(minWidth, Math.abs(point.x - startX));
      const groundY = Math.max(1, Math.min(point.y, canvas.height));
      state.currentAction.width = width;
      state.currentAction.groundY = groundY;
      state.currentAction.centerX = startX;
    } else if (state.currentAction.type === 'line') {
      state.currentAction.endX = point.x;
      state.currentAction.endY = point.y;
    } else if (state.currentAction.type === 'polygon') {
      if (!state.currentAction.isClosed) {
        state.polygonPreviewPoint = point;
      }
    } else if (state.currentAction.type === 'rectangle') {
      state.currentAction.width = point.x - state.currentAction.startX;
      state.currentAction.height = point.y - state.currentAction.startY;
    }
    render();
  };

  const handlePointerUp = (event) => {
    if (event.pointerId !== pointerState.pointerId) {
      return;
    }
    pointerState.pointerId = null;
    canvas.releasePointerCapture?.(event.pointerId);
    commitCurrentAction();
    event.preventDefault();
  };

  // Init / listeners
  toolbar.addEventListener('click', handleToolbarClick);
  document.addEventListener('click', handleDocumentClick);
  canvas.addEventListener('pointerdown', handlePointerDown);
  canvas.addEventListener('pointermove', handlePointerMove);
  canvas.addEventListener('pointerup', handlePointerUp);
  canvas.addEventListener('pointercancel', handlePointerUp);

  updateToolbarVisuals();
  // Force-close all menus on initial load to avoid any visible panels on page load
  closeMenus();
  menuPanels.forEach((panel) => {
    panel.classList.remove('is-visible');
    panel.setAttribute('aria-hidden', 'true');
    panel.setAttribute('inert', '');
  });
  updateMenuVisibility();

  window.addEventListener('resize', updateCanvasSize);
  if (window.ResizeObserver) {
    const observer = new ResizeObserver(updateCanvasSize);
    observer.observe(overlay);
  }
  window.requestAnimationFrame(updateCanvasSize);
})();

/*
DEBUG INFO:
- pinned state variable: pinnedTool
- default hidden via: .drawing-tool-panel { opacity: 0; visibility: hidden; }
- outside click handler: document.addEventListener('click', handleDocumentClick)
- tool selector: [data-drawing-tool]
- visibility controlled by: JS + CSS classes
*/
