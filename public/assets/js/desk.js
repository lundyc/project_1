// --- SHOT RECORDER SVG RENDERING GUARDS ---
function renderShotOriginSvg(svg) {
  if (svg.childNodes.length > 0) return;
  svg.setAttribute('viewBox', '0 0 100 100');
  const NS = 'http://www.w3.org/2000/svg';
  // Outer background
  let bg = document.createElementNS(NS, 'rect');
  bg.setAttribute('x', '0');
  bg.setAttribute('y', '0');
  bg.setAttribute('width', '100');
  bg.setAttribute('height', '100');
  bg.setAttribute('fill', '#f7f7f7');
  svg.appendChild(bg);
  // Outer rectangle
  let outer = document.createElementNS(NS, 'rect');
  outer.setAttribute('x', '0');
  outer.setAttribute('y', '0');
  outer.setAttribute('width', '100');
  outer.setAttribute('height', '100');
  outer.setAttribute('fill', 'none');
  outer.setAttribute('stroke', '#b5b5b5');
  outer.setAttribute('stroke-width', '0.4');
  svg.appendChild(outer);
  // Penalty box
  let penalty = document.createElementNS(NS, 'rect');
  penalty.setAttribute('x', '21.1');
  penalty.setAttribute('y', '0');
  penalty.setAttribute('width', '57.8');
  penalty.setAttribute('height', '32');
  penalty.setAttribute('fill', 'none');
  penalty.setAttribute('stroke', '#b5b5b5');
  penalty.setAttribute('stroke-width', '0.4');
  svg.appendChild(penalty);
  // Six-yard box
  let six = document.createElementNS(NS, 'rect');
  six.setAttribute('x', '36.6');
  six.setAttribute('y', '0');
  six.setAttribute('width', '26.8');
  six.setAttribute('height', '10.5');
  six.setAttribute('fill', 'none');
  six.setAttribute('stroke', '#b5b5b5');
  six.setAttribute('stroke-width', '0.4');
  svg.appendChild(six);
  // Penalty spot
  let spot = document.createElementNS(NS, 'circle');
  spot.setAttribute('cx', '50');
  spot.setAttribute('cy', '12');
  spot.setAttribute('r', '1.1');
  spot.setAttribute('fill', '#b5b5b5');
  svg.appendChild(spot);
  // Penalty arc
  let arc = document.createElementNS(NS, 'path');
  arc.setAttribute('d', 'M 39 18 A 11 11 0 0 1 61 18');
  arc.setAttribute('fill', 'none');
  arc.setAttribute('stroke', '#b5b5b5');
  arc.setAttribute('stroke-width', '0.4');
  svg.appendChild(arc);
  // Goal line (top)
  let goalLine = document.createElementNS(NS, 'rect');
  goalLine.setAttribute('x', '44.5');
  goalLine.setAttribute('y', '-3.5');
  goalLine.setAttribute('width', '11');
  goalLine.setAttribute('height', '3.5');
  goalLine.setAttribute('fill', 'none');
  goalLine.setAttribute('stroke', '#e0e0e0');
  goalLine.setAttribute('stroke-width', '0.3');
  svg.appendChild(goalLine);
}

function renderShotTargetSvg(svg) {
  if (svg.childNodes.length > 0) return;
  svg.setAttribute('viewBox', '0 0 120 60');
  const NS = 'http://www.w3.org/2000/svg';
  // Background
  let bg = document.createElementNS(NS, 'rect');
  bg.setAttribute('x', '0');
  bg.setAttribute('y', '0');
  bg.setAttribute('width', '120');
  bg.setAttribute('height', '60');
  bg.setAttribute('fill', '#f7f7f7');
  svg.appendChild(bg);
  // Goal mouth rectangle
  let goal = document.createElementNS(NS, 'rect');
  goal.setAttribute('x', '30');
  goal.setAttribute('y', '0');
  goal.setAttribute('width', '60');
  goal.setAttribute('height', '8');
  goal.setAttribute('fill', 'none');
  goal.setAttribute('stroke', '#b5b5b5');
  goal.setAttribute('stroke-width', '1');
  svg.appendChild(goal);
  // Left post
  let leftPost = document.createElementNS(NS, 'line');
  leftPost.setAttribute('x1', '30');
  leftPost.setAttribute('y1', '0');
  leftPost.setAttribute('x2', '30');
  leftPost.setAttribute('y2', '60');
  leftPost.setAttribute('stroke', '#b5b5b5');
  leftPost.setAttribute('stroke-width', '1');
  svg.appendChild(leftPost);
  // Right post
  let rightPost = document.createElementNS(NS, 'line');
  rightPost.setAttribute('x1', '90');
  rightPost.setAttribute('y1', '0');
  rightPost.setAttribute('x2', '90');
  rightPost.setAttribute('y2', '60');
  rightPost.setAttribute('stroke', '#b5b5b5');
  rightPost.setAttribute('stroke-width', '1');
  svg.appendChild(rightPost);
  // Crossbar
  let crossbar = document.createElementNS(NS, 'line');
  crossbar.setAttribute('x1', '30');
  crossbar.setAttribute('y1', '0');
  crossbar.setAttribute('x2', '90');
  crossbar.setAttribute('y2', '0');
  crossbar.setAttribute('stroke', '#b5b5b5');
  crossbar.setAttribute('stroke-width', '1');
  svg.appendChild(crossbar);
  // Bottom line
  let bottom = document.createElementNS(NS, 'line');
  bottom.setAttribute('x1', '30');
  bottom.setAttribute('y1', '60');
  bottom.setAttribute('x2', '90');
  bottom.setAttribute('y2', '60');
  bottom.setAttribute('stroke', '#b5b5b5');
  bottom.setAttribute('stroke-width', '0.5');
  svg.appendChild(bottom);
  // Net grid (vertical lines)
  for (let i = 0; i < 6; i++) {
    let x = 40 + i * 10;
    let vline = document.createElementNS(NS, 'line');
    vline.setAttribute('x1', x);
    vline.setAttribute('y1', '8');
    vline.setAttribute('x2', x);
    vline.setAttribute('y2', '60');
    vline.setAttribute('stroke', '#e0e0e0');
    vline.setAttribute('stroke-width', '0.4');
    svg.appendChild(vline);
  }
  // Net grid (horizontal lines)
  for (let j = 0; j < 5; j++) {
    let y = 8 + (j + 1) * 8;
    let hline = document.createElementNS(NS, 'line');
    hline.setAttribute('x1', '30');
    hline.setAttribute('y1', y);
    hline.setAttribute('x2', '90');
    hline.setAttribute('y2', y);
    hline.setAttribute('stroke', '#e0e0e0');
    hline.setAttribute('stroke-width', '0.4');
    svg.appendChild(hline);
  }
}
/* global jQuery */
(function ($) {
  const cfg = window.DeskConfig;
  if (!cfg) return;

  const $lockStatus = $('#lockStatus');
  const $status = $('#deskStatus');
  const $btnAcquire = $('#btnAcquireLock');
  const $editable = $('.desk-editable');

  let heartbeatTimer = null;
  let lockOwned = cfg.lock && cfg.lock.is_owner && cfg.lock.fresh && cfg.canEditRole;

  function setMode(mode, info) {
    if (mode === 'edit') {
      lockOwned = true;
      $editable.prop('disabled', false);
      $lockStatus.removeClass().addClass('text-success fw-semibold').text('Locked by you');
      $status.removeClass().addClass('text-success').text('Edit mode');
    } else {
      lockOwned = false;
      $editable.prop('disabled', true);
      const owner = info && info.locked_by ? (info.locked_by.display_name || `User ${info.locked_by.id}`) : 'Unlocked';
      const label = info && info.locked_by ? `Read-only - locked by ${owner}` : 'Unlocked';
      $lockStatus.removeClass().addClass(info && info.locked_by ? 'text-warning fw-semibold' : 'text-muted').text(label);
      $status.removeClass().addClass('text-muted').text('Read-only mode');
    }
  }

  function startHeartbeat() {
    if (heartbeatTimer) clearInterval(heartbeatTimer);
    if (!lockOwned) return;
    heartbeatTimer = setInterval(() => {
      $.post(cfg.endpoints.lockHeartbeat, { match_id: cfg.matchId })
        .done((res) => {
          if (!res.ok) {
            clearInterval(heartbeatTimer);
            heartbeatTimer = null;
            setMode('readonly', res);
          }
        })
        .fail(() => {
          clearInterval(heartbeatTimer);
          heartbeatTimer = null;
          setMode('readonly', null);
        });
    }, 10000);
  }

  function acquireLock() {
    $.post(cfg.endpoints.lockAcquire, { match_id: cfg.matchId })
      .done((res) => {
        if (res.ok && res.mode === 'edit') {
          setMode('edit', res);
          startHeartbeat();
        } else {
          setMode('readonly', res);
        }
      })
      .fail(() => setMode('readonly', null));
  }


  // --- SHOT MODAL ADVANCED LOCATION LOGIC (SPLIT SELECTORS) ---
  let shotOriginSelector = null;
  let shotTargetSelector = null;
  let shotState = {
    outcome: null,
    body_part: null,
    is_big_chance: false,
    is_one_on_one: false,
    from_set_piece: false,
    location: null
  };
  const $shotModal = $('#shotPlayerModal');
  const $shotOriginSvg = $('#shotOriginSvg');
  const $shotTargetSvg = $('#shotTargetSvg');
  const $shotOriginClearBtn = $('#shotOriginClearBtn');
  const $shotTargetClearBtn = $('#shotTargetClearBtn');
  const $shotOriginClearWrap = $('#shotOriginClearWrap');
  const $shotTargetClearWrap = $('#shotTargetClearWrap');

  function updateShotClearButtons() {
    if (shotState.location && shotState.location.start) {
      $shotOriginClearWrap.show();
    } else {
      $shotOriginClearWrap.hide();
    }
    if (shotState.location && shotState.location.end) {
      $shotTargetClearWrap.show();
    } else {
      $shotTargetClearWrap.hide();
    }
  }

  function mountShotSelectors(initialLocation) {
    // Pitch origin selector
    if (shotOriginSelector) shotOriginSelector.clearLocation();
    shotOriginSelector = new window.PitchOriginSelector($shotOriginSvg[0], {
      onChange: (loc) => {
        if (!shotState.location) shotState.location = {};
        if (loc && loc.start) {
          shotState.location.start = { x: loc.start.x, y: loc.start.y };
        } else {
          if (shotState.location) delete shotState.location.start;
        }
        // Remove location if both are missing
        if (!shotState.location.start && !shotState.location.end) shotState.location = null;
        updateShotClearButtons();
      },
      initialLocation: initialLocation && initialLocation.start ? initialLocation.start : undefined
    });
    // Goal/net target selector
    if (shotTargetSelector) shotTargetSelector.clearLocation();
    shotTargetSelector = new window.GoalTargetSelector($shotTargetSvg[0], {
      onChange: (loc) => {
        if (!shotState.location) shotState.location = {};
        if (loc && loc.end) {
          shotState.location.end = { x: loc.end.x, y: loc.end.y };
        } else {
          if (shotState.location) delete shotState.location.end;
        }
        if (!shotState.location.start && !shotState.location.end) shotState.location = null;
        updateShotClearButtons();
      },
      initialLocation: initialLocation && initialLocation.end ? initialLocation.end : undefined
    });
    // Preload if editing
    if (initialLocation && (initialLocation.start || initialLocation.end)) {
      shotState.location = {};
      if (initialLocation.start) shotState.location.start = { x: initialLocation.start.x, y: initialLocation.start.y };
      if (initialLocation.end) shotState.location.end = { x: initialLocation.end.x, y: initialLocation.end.y };
    } else {
      shotState.location = null;
    }
    updateShotClearButtons();
  }

  $shotOriginClearBtn && $shotOriginClearBtn.on('click', function () {
    if (shotOriginSelector) shotOriginSelector.clearLocation();
    if (shotState.location) delete shotState.location.start;
    if (shotState.location && !shotState.location.end) shotState.location = null;
    updateShotClearButtons();
  });
  $shotTargetClearBtn && $shotTargetClearBtn.on('click', function () {
    if (shotTargetSelector) shotTargetSelector.clearLocation();
    if (shotState.location) delete shotState.location.end;
    if (shotState.location && !shotState.location.start) shotState.location = null;
    updateShotClearButtons();
  });

  // Example: open modal for create/edit
  window.openShotModal = function (existingShot) {
    // Reset state
    shotState = {
      outcome: existingShot && existingShot.outcome || null,
      body_part: existingShot && existingShot.body_part || null,
      is_big_chance: existingShot && existingShot.is_big_chance || false,
      is_one_on_one: existingShot && existingShot.is_one_on_one || false,
      from_set_piece: existingShot && existingShot.from_set_piece || false,
      location: existingShot && existingShot.location && (existingShot.location.start || existingShot.location.end) ? {
        start: existingShot.location.start ? { x: existingShot.location.start.x, y: existingShot.location.start.y } : undefined,
        end: existingShot.location.end ? { x: existingShot.location.end.x, y: existingShot.location.end.y } : undefined
      } : null
    };
    // Ensure SVGs are rendered (guarded)
    renderShotOriginSvg($shotOriginSvg[0]);
    renderShotTargetSvg($shotTargetSvg[0]);
    mountShotSelectors(shotState.location);
    $shotModal.removeAttr('hidden').attr('aria-hidden', 'false');
  };

  window.closeShotModal = function () {
    $shotModal.attr('hidden', 'true').attr('aria-hidden', 'true');
    if (shotOriginSelector) shotOriginSelector.clearLocation();
    if (shotTargetSelector) shotTargetSelector.clearLocation();
    shotState.location = null;
    updateShotClearButtons();
  };

  // Example: save handler
  window.saveShotModal = function () {
    // Defensive validation: location must be null or fully valid
    let validLoc = null;
    if (shotState.location && shotState.location.start && shotState.location.end) {
      // Clamp and check normalized
      const sx = Math.max(0, Math.min(1, Number(shotState.location.start.x)));
      const sy = Math.max(0, Math.min(1, Number(shotState.location.start.y)));
      const ex = Math.max(0, Math.min(1, Number(shotState.location.end.x)));
      const ey = Math.max(0, Math.min(1, Number(shotState.location.end.y)));
      if ([sx, sy, ex, ey].every(v => typeof v === 'number' && !isNaN(v))) {
        validLoc = { start: { x: sx, y: sy }, end: { x: ex, y: ey } };
      }
    } else if (shotState.location && (shotState.location.start || shotState.location.end)) {
      // Allow partials (either start or end or both)
      validLoc = {};
      if (shotState.location.start) {
        const sx = Math.max(0, Math.min(1, Number(shotState.location.start.x)));
        const sy = Math.max(0, Math.min(1, Number(shotState.location.start.y)));
        if (typeof sx === 'number' && typeof sy === 'number' && !isNaN(sx) && !isNaN(sy)) {
          validLoc.start = { x: sx, y: sy };
        }
      }
      if (shotState.location.end) {
        const ex = Math.max(0, Math.min(1, Number(shotState.location.end.x)));
        const ey = Math.max(0, Math.min(1, Number(shotState.location.end.y)));
        if (typeof ex === 'number' && typeof ey === 'number' && !isNaN(ex) && !isNaN(ey)) {
          validLoc.end = { x: ex, y: ey };
        }
      }
      if (!validLoc.start && !validLoc.end) validLoc = null;
    }
    const payload = {
      outcome: shotState.outcome,
      body_part: shotState.body_part,
      is_big_chance: shotState.is_big_chance,
      is_one_on_one: shotState.is_one_on_one,
      from_set_piece: shotState.from_set_piece,
      location: validLoc
    };
    // TODO: send payload to backend or update UI
    window.closeShotModal();
    return payload;
  };

  function init() {
    setMode(lockOwned ? 'edit' : 'readonly', { locked_by: cfg.lock ? cfg.lock.locked_by : null });
    if (lockOwned) startHeartbeat();
    if ($btnAcquire.length) {
      $btnAcquire.on('click', function (e) {
        e.preventDefault();
        acquireLock();
      });
    }
  }

  $(init);
})(jQuery);
