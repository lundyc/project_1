(function () {
  const toggle = document.getElementById('deskInteractiveToggle');
  const transformLayer = document.querySelector('.video-transform-layer');
  if (!toggle || !transformLayer) {
    return;
  }

  let interactiveEnabled = false;
  let scale = 1;
  let translateX = 0;
  let translateY = 0;
  let dragging = false;
  let pointerId = null;
  let startX = 0;
  let startY = 0;
  let baseTranslateX = 0;
  let baseTranslateY = 0;

  const MIN_SCALE = 1;
  const MAX_SCALE = 3;

  function clampScale(value) {
    return Math.min(MAX_SCALE, Math.max(MIN_SCALE, value));
  }

  function applyTransform() {
    transformLayer.style.transform = `translate(${translateX}px, ${translateY}px) scale(${scale})`;
    transformLayer.style.cursor = interactiveEnabled ? (dragging ? 'grabbing' : 'grab') : 'unset';
    dispatchTransformChange();
  }

  function dispatchInteractiveChange(enabled) {
    window.dispatchEvent(
      new CustomEvent('DeskInteractiveModeChanged', {
        detail: { enabled: Boolean(enabled) },
      })
    );
  }

  function dispatchTransformChange() {
    window.dispatchEvent(new CustomEvent('DeskVideoTransformChanged'));
  }

  function setInteractiveState(enabled) {
    interactiveEnabled = Boolean(enabled);
    toggle.classList.toggle('is-active', interactiveEnabled);
    toggle.setAttribute('aria-pressed', interactiveEnabled ? 'true' : 'false');
    if (!interactiveEnabled) {
      dragging = false;
      pointerId = null;
      translateX = 0;
      translateY = 0;
      scale = 1;
    }
    applyTransform();
    dispatchInteractiveChange(interactiveEnabled);
  }

  function handleToggleClick(event) {
    setInteractiveState(!interactiveEnabled);
    event.preventDefault();
  }

  function handlePointerDown(event) {
    if (!interactiveEnabled || event.button !== 0) {
      return;
    }
    dragging = true;
    pointerId = event.pointerId;
    startX = event.clientX;
    startY = event.clientY;
    baseTranslateX = translateX;
    baseTranslateY = translateY;
    transformLayer.setPointerCapture?.(pointerId);
    applyTransform();
    event.preventDefault();
  }

  function handlePointerMove(event) {
    if (!interactiveEnabled || !dragging || event.pointerId !== pointerId) {
      return;
    }
    const dx = event.clientX - startX;
    const dy = event.clientY - startY;
    translateX = baseTranslateX + dx;
    translateY = baseTranslateY + dy;
    applyTransform();
  }

  function handlePointerEnd(event) {
    if (!interactiveEnabled || event.pointerId !== pointerId) {
      return;
    }
    dragging = false;
    transformLayer.releasePointerCapture?.(pointerId);
    pointerId = null;
    applyTransform();
  }

  function handleWheel(event) {
    if (!interactiveEnabled) {
      return;
    }
    event.preventDefault();
    const rect = transformLayer.getBoundingClientRect();
    if (!rect.width || !rect.height) {
      return;
    }
    const delta = -event.deltaY * 0.0025;
    const nextScale = clampScale(scale * (1 + delta));
    if (Math.abs(nextScale - scale) < 1e-5) {
      return;
    }
    const focusX = event.clientX - rect.left;
    const focusY = event.clientY - rect.top;
    const prevScale = scale;
    scale = nextScale;
    translateX += focusX / scale - focusX / prevScale;
    translateY += focusY / scale - focusY / prevScale;
    applyTransform();
  }

  function closeInteractiveOnEditing(event) {
    if (!event || !event.detail || !event.detail.editing) {
      return;
    }
    if (interactiveEnabled) {
      setInteractiveState(false);
    }
  }

  function handleEscape(event) {
    if (event.key === 'Escape' && interactiveEnabled) {
      setInteractiveState(false);
    }
  }

  toggle.addEventListener('click', handleToggleClick);
  transformLayer.addEventListener('pointerdown', handlePointerDown);
  transformLayer.addEventListener('pointermove', handlePointerMove);
  transformLayer.addEventListener('pointerup', handlePointerEnd);
  transformLayer.addEventListener('pointerleave', handlePointerEnd);
  transformLayer.addEventListener('pointercancel', handlePointerEnd);
  transformLayer.addEventListener('wheel', handleWheel, { passive: false });
  window.addEventListener('DeskDrawingModeChanged', closeInteractiveOnEditing);
  document.addEventListener('keydown', handleEscape);

  // rely on CSS to keep overlay clickable when zoomed
  setInteractiveState(false);
})();
