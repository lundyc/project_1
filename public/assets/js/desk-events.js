
/* global jQuery */
(function ($) {
          const cfg = window.DeskConfig;
          if (!cfg) return;
          console.log('Outcome options loaded', DeskConfig.outcomeOptionsByTypeId);
          const csrfToken = cfg.csrfToken || null;
          if (csrfToken && $.ajaxSetup) {
                    $.ajaxSetup({
                              headers: { 'X-CSRF-Token': csrfToken },
                    });
          }

          const endpoints = cfg.endpoints || {};

          const $video = $('#deskVideoPlayer');
          const $timelineList = $('#timelineList');
          const $timelineMatrix = $('#timelineMatrix');
          const $timelineScroll = $('.timeline-scroll');
          const $timeline = $('.timeline-panel');
          const $status = $('#deskStatus');

          const $lockStatus = $('#lockStatusText');
          const $btnAcquire = $('#lockRetryBtn');
          const $deskError = $('#deskError');
          const $jsBadge = $('#deskJsBadge');

          const $contextTabs = $('#contextTabs');
          const $teamToggle = $('#teamToggle');
          const $tagBoard = $('#quickTagBoard');
          const $tagToast = $('#tagToast');
          const $editorPanel = $('#editorPanel');
          const $editorHint = $('#editorHint');

          const $eventId = $('#eventId');
          const $matchSecond = $('#match_second');
          const $minute = $('#minute');
          const $minuteExtra = $('#minute_extra');
          const $teamSide = $('#team_side');
          const $periodId = $('#period_id');
          const $eventTypeId = $('#event_type_id');
          const $matchPlayerId = $('#match_player_id');
          const $importance = $('#importance');
          const $phase = $('#phase');
          const $outcome = $('#outcome');
          const $outcomeField = $('#outcomeField');
          const outcomeOptionsByTypeId = cfg.outcomeOptionsByTypeId || {};
          const $zone = $('#zone');
          const $notes = $('#notes');
          const $tagIds = $('#tag_ids');
          const $undoBtn = $('#eventUndoBtn');
          const $redoBtn = $('#eventRedoBtn');
          const undoRedoState = { canUndo: false, canRedo: false };

          const $filterTeam = $('#filterTeam');
          const $filterType = $('#filterType');
          const $filterPlayer = $('#filterPlayer');
          const $timelineModeBtns = $('.timeline-mode-btn');

          const $clipIn = $('#clipInText');
          const $clipOut = $('#clipOutText');
          const $clipDuration = $('#clipDurationText');
          const $clipInFmt = $('#clip_in_fmt');
          const $clipOutFmt = $('#clip_out_fmt');
          const $btnClipSetIn = $('#clipInBtn');
          const $btnClipSetOut = $('#clipOutBtn');
          const $btnClipCreate = $('#clipCreateBtn');
          const $btnClipDelete = $('#clipDeleteBtn');

          const EVENT_COLOURS = {
                    goal: '#22c55e',
                    goal_for: '#22c55e',
                    goal_against: '#22c55e',
                    shot: '#3b82f6',
                    chance: '#8b5cf6',
                    big_chance: '#8b5cf6',
                    corner: '#14b8a6',
                    corner_for: '#14b8a6',
                    corner_against: '#14b8a6',
                    freekick: '#06b6d4',
                    free_kick: '#06b6d4',
                    free_kick_for: '#06b6d4',
                    free_kick_against: '#06b6d4',
                    penalty: '#f59e0b',
                    foul: '#ef4444',
                    card: '#fb923c',
                    yellow_card: '#fb923c',
                    red_card: '#fb923c',
                    mistake: '#f472b6',
                    turnover: '#f472b6',
                    good_play: '#84cc16',
                    highlight: '#facc15',
                    other: '#94a3b8',
          };
          const EVENT_NEUTRAL = '#94a3b8';
          const VIDEO_TIME_KEY = cfg && cfg.matchId ? `deskVideoTime_${cfg.matchId}` : 'deskVideoTime';

          let heartbeatTimer = null;
          let lockOwned = false;
          let events = [];
          let filteredCache = [];
          let selectedId = null;
          let clipState = { id: null, start: null, end: null };
          let suppressEditorOpen = false;
          let currentContext = 'all';
          let timelineMode = 'matrix';
          const storedTeam = window.localStorage ? window.localStorage.getItem('deskTeamSide') : null;
          let currentTeam = ['home', 'away'].includes(storedTeam) ? storedTeam : 'home';
          const eventTypeMap = {};
          const eventTypeAccents = {};
          const eventTypeKeyMap = {};
          const boardLabelByTypeId = {};
          const tagReplacements = {
                    'TOR HEIM': { label: 'Goal (For)', key: 'goal_for' },
                    'TOR-G': { label: 'Goal (Against)', key: 'goal_against' },
                    'CHANCE': { label: 'Chance', key: 'chance' },
                    'G CHANCE': { label: 'Big Chance', key: 'big_chance' },
                    'LONGB': { label: 'Long Ball', key: 'long_ball' },
                    'FREISTOSS': { label: 'Free Kick (For)', key: 'free_kick_for' },
                    'FREISTOSS G': { label: 'Free Kick (Against)', key: 'free_kick_against' },
                    'ECKE': { label: 'Corner (For)', key: 'corner_for' },
                    'ECKE G': { label: 'Corner (Against)', key: 'corner_against' },
                    'UMST OFF': { label: 'Attacking Transition', key: 'attacking_transition' },
                    'UMS - DEF': { label: 'Defensive Transition', key: 'defensive_transition' },
                    'BB 1': { label: 'Build-Up Phase 1', key: 'build_up_phase_1' },
                    'BB 2': { label: 'Build-Up Phase 2', key: 'build_up_phase_2' },
                    'BB 3': { label: 'Build-Up Phase 3', key: 'build_up_phase_3' },
                    'PRESSING Z1': { label: 'Pressing Zone 1', key: 'pressing_zone_1' },
                    'PRESSING Z2': { label: 'Pressing Zone 2', key: 'pressing_zone_2' },
                    'PRESSING Z3': { label: 'Pressing Zone 3', key: 'pressing_zone_3' },
                    'PRESSING LB': { label: 'Low Block Press', key: 'low_block_press' },
                    'HIGHLIGHT': { label: 'Highlight', key: 'highlight' },
                    'SONSTIGES': { label: 'Other', key: 'other' },
          };
          let quickTagBoard = {
                    title: 'Quick Tags',
                    tiles: [],
          };
          const MATRIX_TYPE_WIDTH = 160;
          const MATRIX_GAP = 8;
          const timelineZoom = {
                    scale: 1,
                    min: 1,
                    max: 10,
                    pixelsPerSecond: 3,
          };
          const timelineMetrics = { duration: 0, totalWidth: 0, viewportWidth: 0 };
          let matrixInitialized = false;
          let matrixPan = { active: false, startX: 0, scrollLeft: 0 };
          let resizeTimer = null;

          function hexToRgb(hex) {
                    const value = (hex || '').replace('#', '').trim();
                    if (value.length === 3) {
                              const r = value[0];
                              const g = value[1];
                              const b = value[2];
                              return {
                                        r: parseInt(r + r, 16) || 0,
                                        g: parseInt(g + g, 16) || 0,
                                        b: parseInt(b + b, 16) || 0,
                              };
                    }
                    const r = parseInt(value.slice(0, 2), 16);
                    const g = parseInt(value.slice(2, 4), 16);
                    const b = parseInt(value.slice(4, 6), 16);
                    return {
                              r: Number.isNaN(r) ? 0 : r,
                              g: Number.isNaN(g) ? 0 : g,
                              b: Number.isNaN(b) ? 0 : b,
                    };
          }

          function buildColorStyle(color) {
                    const base = color || EVENT_NEUTRAL;
                    const rgb = hexToRgb(base);
                    const soft = `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, 0.18)`;
                    const strong = `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, 0.6)`;
                    return `--event-color:${base};--event-color-soft:${soft};--event-color-strong:${strong};`;
          }

          function colorForKey(key) {
                    const norm = (key || '').toLowerCase();
                    if (EVENT_COLOURS[norm]) return EVENT_COLOURS[norm];
                    const fallbacks = typeKeyFallbacks[norm] || [];
                    for (const alt of fallbacks) {
                              if (EVENT_COLOURS[alt]) return EVENT_COLOURS[alt];
                    }
                    return EVENT_NEUTRAL;
          }

          function guessKeyFromLabel(label) {
                    const lower = (label || '').toLowerCase();
                    if (lower.includes('goal')) return 'goal';
                    if (lower.includes('shot')) return 'shot';
                    if (lower.includes('chance')) return 'chance';
                    if (lower.includes('corner')) return 'corner';
                    if (lower.includes('free')) return 'free_kick';
                    if (lower.includes('penalty')) return 'penalty';
                    if (lower.includes('foul')) return 'foul';
                    if (lower.includes('card')) return 'card';
                    if (lower.includes('mistake') || lower.includes('error')) return 'mistake';
                    if (lower.includes('good') || lower.includes('play')) return 'good_play';
                    if (lower.includes('highlight')) return 'highlight';
                    return null;
          }

          function colorForType(type) {
                    if (!type) return EVENT_NEUTRAL;
                    const key = (type.type_key || '').toLowerCase() || guessKeyFromLabel(type.label);
                    return colorForKey(key);
          }

          function displayEventLabel(ev, fallback) {
                    const key = (ev && ev.event_type_key) || '';
                    if (key === 'period_start' || key === 'period_end') {
                              const base = (ev.notes || 'Period').trim() || 'Period';
                              const suffix = key === 'period_start' ? 'Start' : 'End';
                              return `${base} ${suffix}`;
                    }
                    return ev && ev.event_type_label ? ev.event_type_label : fallback || 'Event';
          }
          function translateEventType(type) {
                    const repl = tagReplacements[type.label];
                    if (repl) {
                              return { ...type, label: repl.label, type_key: repl.key };
                    }
                    return type;
          }

          function applyQuickTagReplacements() {
                    if (!cfg.eventTypes) return;
                    cfg.eventTypes = cfg.eventTypes.map(translateEventType);
          }

          function syncEventTypeOptions() {
                    if (!cfg.eventTypes) return;
                    const typeMap = {};
                    cfg.eventTypes.forEach((t) => {
                              typeMap[String(t.id)] = t;
                    });
                    [$filterType, $eventTypeId].forEach(($select) => {
                              if (!$select.length) return;
                              $select.find('option').each(function () {
                                        const val = $(this).attr('value');
                                        const type = typeMap[String(val)];
                                        if (type) {
                                                  $(this).text(type.label);
                                        }
                              });
                    });
          }

          function syncFilterOptionsFromBoard() {
                    const seen = new Set();
                    const options = ['<option value=\"\">All types</option>'];
                    (quickTagBoard.tiles || []).forEach((tile) => {
                              if (tile.spacer) return;
                              const type = resolveTileType(tile);
                              if (!type) return;
                              const id = String(type.id);
                              if (seen.has(id)) return;
                              seen.add(id);
                              options.push(`<option value=\"${id}\">${h(tile.label)}</option>`);
                    });
                    if ($filterType.length) {
                              $filterType.html(options.join(''));
                    }
          }

          function endpoint(key) {
                    const url = endpoints[key];
                    if (!url) {
                              console.error(`Missing endpoint: ${key}`);
                    }
                    return url;
          }

          function h(val) {
                    if (val === null || val === undefined) return '';
                    return String(val)
                              .replace(/&/g, '&amp;')
                              .replace(/</g, '&lt;')
                              .replace(/>/g, '&gt;')
                              .replace(/"/g, '&quot;')
                              .replace(/'/g, '&#39;');
          }

          function resolveOutcomeOptions(typeId) {
                    const key = typeId ? String(typeId) : '';
                    if (!key) return [];
                    const candidate = outcomeOptionsByTypeId[key];
                    if (!Array.isArray(candidate)) return [];
                    return candidate;
          }

          function refreshUndoRedoButtons() {
                    const canUndo = lockOwned && undoRedoState.canUndo;
                    const canRedo = lockOwned && undoRedoState.canRedo;
                    if ($undoBtn.length) {
                              $undoBtn.prop('disabled', !canUndo);
                    }
                    if ($redoBtn.length) {
                              $redoBtn.prop('disabled', !canRedo);
                    }
          }

          function setUndoRedoState(state = {}) {
                    if (state && typeof state === 'object') {
                              if (typeof state.canUndo === 'boolean') {
                                        undoRedoState.canUndo = state.canUndo;
                              } else if ('canUndo' in state) {
                                        undoRedoState.canUndo = Boolean(state.canUndo);
                              }
                              if (typeof state.canRedo === 'boolean') {
                                        undoRedoState.canRedo = state.canRedo;
                              } else if ('canRedo' in state) {
                                        undoRedoState.canRedo = Boolean(state.canRedo);
                              }
                    }
                    refreshUndoRedoButtons();
          }

          function syncUndoRedoFromMeta(meta) {
                    if (meta && meta.action_stack) {
                              setUndoRedoState(meta.action_stack);
                    }
          }

          setUndoRedoState(cfg.actionStack || {});

          function refreshOutcomeField(typeId, selectedOutcome = '') {
                    if (!$outcomeField.length || !$outcome.length) return;
                    const options = resolveOutcomeOptions(typeId);
                    console.log('Outcome options for type', typeId, options);
                    if (!options || !options.length) {
                              $outcome.html('<option value=\"\"></option>');
                              $outcome.val('');
                              $outcomeField.hide();
                              return;
                    }
                    const normalizedSelected = (selectedOutcome || '').toString();
                    const hasSelected =
                              normalizedSelected && options.some((opt) => String(opt || '').toLowerCase() === normalizedSelected.toLowerCase());
                    const html = ['<option value=""></option>'];
                    options.forEach((opt) => {
                              html.push(`<option value="${h(opt)}">${h(opt)}</option>`);
                    });
                    $outcome.html(html.join(''));
                    if (hasSelected) {
                              $outcome.val(normalizedSelected);
                    } else {
                              $outcome.val('');
                    }
                    $outcomeField.show();
          }

          function resolveEventTypeId(event) {
                    if (!event) return null;
                    if (event.event_type_id) return event.event_type_id;
                    if (event.event_type && event.event_type.id) return event.event_type.id;
                    const rawKey = (event.event_type_key || '').toLowerCase().trim();
                    if (!rawKey) return null;
                    if (eventTypeKeyMap[rawKey]) {
                              return eventTypeKeyMap[rawKey].id;
                    }
                    const normalized = rawKey.replace(/[_\s]/g, '');
                    if (eventTypeKeyMap[normalized]) {
                              return eventTypeKeyMap[normalized].id;
                    }
                    return null;
          }

          function refreshOutcomeFieldForEvent(event) {
                    const typeId = resolveEventTypeId(event);
                    const outcome = (event && event.outcome) || '';
                    refreshOutcomeField(typeId, outcome);
                    return typeId;
          }

          function fmtTime(sec) {
                    const s = Math.max(0, Math.floor(sec));
                    const m = Math.floor(s / 60);
                    const mm = m.toString().padStart(2, '0');
                    const ss = (s % 60).toString().padStart(2, '0');
                    return `${mm}:${ss}`;
          }

          function getCurrentVideoSecond() {
                    if (!$video.length) {
                              return 0;
                    }
                    const rawSeconds = $video[0].currentTime;
                    if (typeof rawSeconds !== 'number' || Number.isNaN(rawSeconds)) {
                              return 0;
                    }
                    return Math.max(0, Math.floor(rawSeconds));
          }

          function showError(msg, detail) {
                    console.error('Desk error', msg, detail || '');
                    if ($deskError.length) {
                              $deskError.text(detail ? `${msg}: ${detail}` : msg).show();
                    }
          }

          function hideError() {
                    if ($deskError.length) {
                              $deskError.hide().text('');
                    }
          }

          function setStatus(text) {
                    if ($status.length) {
                              $status.text(text);
                    }
          }

          function showToast(text, isError = false) {
                    if (!$tagToast.length) return;
                    $tagToast.toggleClass('desk-toast-error', !!isError);
                    $tagToast.text(text).fadeIn(120);
                    setTimeout(() => $tagToast.fadeOut(160), 1400);
          }

          function contextForType(type) {
                    const key = (type.type_key || '').toLowerCase();
                    if (key.includes('transition') || key.includes('turnover')) return 'transition';
                    if (key.includes('def') || key.includes('block')) return 'defence';
                    return 'attack';
          }

          function buildTypeMap() {
                    (cfg.eventTypes || []).forEach((t) => {
                              eventTypeMap[String(t.id)] = t;
                              const color = colorForType(t);
                              eventTypeAccents[String(t.id)] = color;
                              if (t.type_key) {
                                        const rawKey = String(t.type_key).toLowerCase();
                                        eventTypeKeyMap[rawKey] = t;
                                        const normalized = rawKey.replace(/[_\s]/g, '');
                                        if (!eventTypeKeyMap[normalized]) {
                                                  eventTypeKeyMap[normalized] = t;
                                        }
                              }
                    });
          }

          function rebuildQuickTagBoard() {
                    const allowedKeys = [
                              'goal',
                              'shot',
                              'chance',
                              'corner',
                              'free_kick',
                              'penalty',
                              'foul',
                              'yellow_card',
                              'red_card',
                              'mistake',
                              'good_play',
                              'highlight',
                    ];
                    const tiles = [];
                    allowedKeys.forEach((key) => {
                              const type = resolveTileType({ event_type_key: key });
                              if (!type) {
                                        console.warn('Quick tag skipped; missing event type', key);
                                        return;
                              }
                              tiles.push({
                                        label: type.label || key,
                                        event_type_key: type.type_key || key,
                                        importance: parseInt(type.default_importance, 10) || 3,
                              });
                    });
                    quickTagBoard = { title: 'Quick Tags', tiles };
          }

          const typeKeyFallbacks = {
                    goal: ['goal', 'goal_for', 'goal_against'],
                    shot: ['shot'],
                    chance: ['chance', 'big_chance', 'shot'],
                    corner: ['corner', 'corner_for', 'corner_against', 'set_piece'],
                    freekick: ['freekick', 'free_kick', 'free_kick_for', 'free_kick_against', 'set_piece'],
                    penalty: ['penalty', 'spot_kick'],
                    foul: ['foul'],
                    card: ['card', 'yellow_card', 'red_card', 'yellow', 'red'],
                    yellow_card: ['card', 'yellow_card', 'yellow'],
                    red_card: ['card', 'red_card', 'red'],
                    mistake: ['mistake', 'error', 'turnover', 'own_goal'],
                    good_play: ['good_play', 'positive', 'assist'],
                    highlight: ['highlight', 'other'],
                    other: ['other'],
          };

          function fallbackType() {
                    if (eventTypeKeyMap.other) return eventTypeKeyMap.other;
                    return (cfg.eventTypes || [])[0] || null;
          }

          function resolveTileType(tile) {
                    if (!tile || !tile.event_type_key) {
                              console.error('Quick tag missing event_type_key', tile);
                              return null;
                    }
                    const key = String(tile.event_type_key).toLowerCase();
                    const normKey = key.replace(/[_\s]/g, '');
                    const direct = eventTypeKeyMap[key];
                    if (direct) return direct;
                    const normMatch = eventTypeKeyMap[normKey];
                    if (normMatch) return normMatch;
                    const labelMatch = (cfg.eventTypes || []).find((t) => (t.label || '').toLowerCase() === (tile.label || '').toLowerCase());
                    if (labelMatch) return labelMatch;
                    console.error('Unknown event_type_key for quick tag', key);
                    return null;
          }

          function tileContext(tile, type) {
                    if (tile && tile.context) return tile.context;
                    return contextForType(type);
          }

          function applyEventLabelReplacements(list) {
                    return (list || []).map((ev) => {
                              const mappedType = eventTypeMap[String(ev.event_type_id)];
                              const boardLabel = boardLabelByTypeId[String(ev.event_type_id)];
                              const repl = tagReplacements[ev.event_type_label];
                              if (boardLabel && mappedType) {
                                        return { ...ev, event_type_label: boardLabel, event_type_key: mappedType.type_key };
                              }
                              if (mappedType) {
                                        return { ...ev, event_type_label: mappedType.label, event_type_key: mappedType.type_key };
                              }
                              if (repl) {
                                        return { ...ev, event_type_label: repl.label, event_type_key: repl.key };
                              }
                              return ev;
                    });
          }

          function renderTagGrid() {
                    const tiles = quickTagBoard.tiles || [];
                    let html = `<div class="qt-board-head">
                              <div class="qt-board-title">${h(quickTagBoard.title || 'Quick Tags')}</div>
                    </div>`;
                    html += '<div class="qt-grid">';

                    tiles.forEach((tile) => {
                              const type = resolveTileType(tile);
                              if (!type) {
                                        const styleAttr = `style="${buildColorStyle(EVENT_NEUTRAL)}"`;
                                        html += `<button class="qt-tile" data-context="all" data-type-key="${h(tile.event_type_key)}" data-label="${h(tile.label)}" data-phase="unknown" data-importance="${tile.importance || 3}" ${styleAttr} disabled>
                                                  <span class="qt-label">${h(tile.label)} (missing)</span>
                                        </button>`;
                                        return;
                              }
                              const context = 'all';
                              const imp = tile.importance ? parseInt(tile.importance, 10) : parseInt(type.default_importance, 10) || 3;
                              const phase = 'unknown';
                              boardLabelByTypeId[String(type.id)] = tile.label;
                              const accentColor = eventTypeAccents[String(type.id)] || colorForKey(type.type_key);
                              const styleAttr = `style="${buildColorStyle(accentColor)}"`;
                              html += `<button class="qt-tile" data-context="${h(context)}" data-type-id="${type.id}" data-type-key="${h(type.type_key)}" data-label="${h(tile.label)}" data-phase="${h(phase)}" data-importance="${imp}" ${styleAttr}>
                                        <span class="qt-label">${h(tile.label)}</span>
                              </button>`;
                    });

                    html += '</div>';
                    $tagBoard.html(html);
                    filterTagGrid();
                    syncFilterOptionsFromBoard();
          }

          function setContext(ctx) {
                    currentContext = ctx;
                    $contextTabs.find('.tab-btn').removeClass('is-active');
                    $contextTabs.find(`[data-context="${ctx}"]`).addClass('is-active');
                    filterTagGrid();
          }

          function setTeam(team) {
                    if (!['home', 'away'].includes(team)) return;
                    currentTeam = team;
                    if (window.localStorage) {
                              window.localStorage.setItem('deskTeamSide', team);
                    }
                    $teamToggle.find('.toggle-btn').removeClass('is-active');
                    $teamToggle.find(`[data-team="${team}"]`).addClass('is-active');
          }

          function filterTagGrid() {
                    const enabled = lockOwned && cfg.canEditRole;
                    $tagBoard.toggleClass('disabled', !enabled);
                    $tagBoard.find('.qt-tile').show();
          }

          function quickTag(typeKey, typeId, $btn) {
                    if (!typeKey) {
                              console.error('Missing event_type_key for quick tag');
                              return;
                    }
                    const key = String(typeKey).toLowerCase();
                    const type = resolveTileType({ event_type_key: key, label: ($btn && $btn.data('label')) || '' });
                    if (!type) return;
                    if (!lockOwned || !cfg.canEditRole) {
                              showError('Lock required to tag', 'Acquire lock to create events');
                              return;
                    }
                    const currentSecond = getCurrentVideoSecond();
                    const normalizedSecond = Number.isFinite(currentSecond) ? Math.floor(currentSecond) : 0;
                    console.log('Quick tag second', normalizedSecond);
                    const $btnNode = $btn && $btn.length ? $btn : $tagBoard.find(`.qt-tile[data-type-id="${type.id}"]`).first();
                    if (!$btnNode || !$btnNode.length) {
                              console.error('Quick tag button not found for type', key);
                              return;
                    }
                    const phaseOverride = ($btnNode.data('phase') || '').trim();
                    const importanceOverride = parseInt($btnNode.data('importance'), 10);
                    const labelOverride = ($btnNode.data('label') || '').trim();
                    const payload = {
                              match_id: cfg.matchId,
                              event_type_id: type.id,
                              event_type_key: key,
                              minute: Math.floor(normalizedSecond / 60),
                              team_side: currentTeam || 'home',
                              phase: phaseOverride || 'unknown',
                              minute_extra: 0,
                              importance: Number.isNaN(importanceOverride) ? parseInt(type.default_importance, 10) || 3 : importanceOverride,
                    };
                    payload.match_second = normalizedSecond;
                    const url = endpoint('eventCreate');
                    if (!url) {
                              showError('Save failed', 'Missing event endpoint');
                              return;
                    }
                    $.post(url, payload)
                              .done((res) => {
                                        console.log('Quick tag', res);
                                        if (!res.ok) {
                                                  showError('Save failed', res.error || 'Unknown');
                                                  return;
                                        }
                                        hideError();
                                        selectedId = null;
                                        setEditorCollapsed(true, 'Click a timeline item to edit details', true);
                                        showToast(`${labelOverride || type.label} tagged at ${fmtTime(normalizedSecond)}`);
                                        setStatus('Tagged');
                                        syncUndoRedoFromMeta(res.meta);
                                        loadEvents();
                              })
                              .fail((xhr, status, error) => showError('Save failed', xhr.responseText || error || status));
          }

          function stopHeartbeat() {
                    if (heartbeatTimer) {
                              clearInterval(heartbeatTimer);
                              heartbeatTimer = null;
                    }
          }

                    function applyMode(isEdit, info) {
                    if (!isEdit) stopHeartbeat();
                    lockOwned = isEdit && cfg.canEditRole;
                    const editable = $('.desk-editable');
                    editable.prop('disabled', !lockOwned);

                    const owner = info && info.locked_by ? (info.locked_by.display_name || `User ${info.locked_by.id}`) : null;
                    if (isEdit) {
                              $lockStatus.removeClass().addClass('text-success fw-semibold').text('Locked by you - Edit mode');
                    } else {
                              const label = owner ? `Read-only - locked by ${owner}` : 'Unlocked';
                              const className = owner ? 'text-warning fw-semibold' : 'text-muted';
                              $lockStatus.removeClass().addClass(className).text(label);
                    }

                    if ($btnAcquire.length) {
                              $btnAcquire.toggle(!isEdit && cfg.canEditRole);
                    }
                   filterTagGrid();
                   updateClipUi();
                    refreshUndoRedoButtons();
          }
function applyLockResponse(res) {
                    const isEdit = !!(res && res.ok && res.mode === 'edit');
                    applyMode(isEdit, res);
                    if (isEdit) {
                              startHeartbeat();
                    } else if (res && res.locked_by) {
                              const owner = res.locked_by.display_name || `User ${res.locked_by.id}`;
                              showError('Desk locked by another user', owner);
                    }
          }

          function startHeartbeat() {
                    if (heartbeatTimer || !lockOwned) return;
                    const url = endpoint('lockHeartbeat');
                    if (!url) {
                              showError('Heartbeat error', 'Missing lock heartbeat endpoint');
                              return;
                    }
                    heartbeatTimer = setInterval(() => {
                              $.post(url, { match_id: cfg.matchId })
                                        .done((res) => {
                                                  if (!res.ok || res.mode !== 'edit') {
                                                            showError('Lock lost', res.error || 'Lock expired');
                                                            applyMode(false, res);
                                                            loadEvents();
                                                  }
                                        })
                                        .fail((xhr, status, error) => {
                                                  showError('Heartbeat failed', xhr.responseText || error || status);
                                                  applyMode(false, null);
                                                  loadEvents();
                                        });
                    }, 10000);
          }

          function acquireLock() {
                    const url = endpoint('lockAcquire');
                    if (!url) {
                              showError('Lock failed', 'Missing lock acquire endpoint');
                              applyMode(false, null);
                              loadEvents();
                              return;
                    }
                    $.post(url, { match_id: cfg.matchId })
                              .done((res) => {
                                        hideError();
                                        applyLockResponse(res);
                                        loadEvents();
                              })
                              .fail((xhr, status, error) => {
                                        showError('Lock failed', xhr.responseText || error || status);
                                        applyMode(false, null);
                                        loadEvents();
                              });
          }
          function loadEvents() {
                    const url = endpoint('events');
                    if (!url) {
                              showError('Failed to load events', 'Missing events endpoint');
                              return;
                    }
                    $.getJSON(url, { match_id: cfg.matchId })
                  .done((res) => {
                            if (!res.ok) {
                                      showError('Failed to load events', res.error || 'Unknown');
                                      return;
                            }
                            hideError();
                            events = applyEventLabelReplacements(res.events || []);
                            syncUndoRedoFromMeta(res.meta);
                            renderTimeline();
                            if (selectedId) selectEvent(selectedId);
                  })
                              .fail((xhr, status, error) => showError('Events load failed', xhr.responseText || error || status));
          }

          function setTimelineMode(mode) {
                    timelineMode = mode === 'matrix' ? 'matrix' : 'list';
                    $timelineModeBtns.removeClass('is-active');
                    $timelineModeBtns.filter(`[data-mode="${timelineMode}"]`).addClass('is-active');
                    $('.timeline-view').removeClass('is-active');
                    if (timelineMode === 'matrix') {
                              matrixInitialized = false;
                              $timelineMatrix.addClass('is-active');
                    } else {
                              $timelineList.addClass('is-active');
                    }
                    renderTimeline();
          }

          function renderTimeline(options = {}) {
                    const teamF = $filterTeam.val();
                    const typeF = $filterType.val();
                    const playerF = $filterPlayer.val();
                    const prevViewport = $timelineMatrix.find('.matrix-viewport');
                    const previousScroll = prevViewport.length ? prevViewport[0].scrollLeft : 0;
                    const opts = { ...options, previousScroll };

                    let filtered = events;
                    if (teamF) filtered = filtered.filter((e) => e.team_side === teamF);
                    if (typeF) filtered = filtered.filter((e) => String(e.event_type_id) === String(typeF));
                    if (playerF) filtered = filtered.filter((e) => String(e.match_player_id) === String(playerF));

                    const groups = {};
                    filtered.forEach((ev) => {
                              const key = ev.period_label || 'No period';
                              if (!groups[key]) groups[key] = [];
                              groups[key].push(ev);
                    });

                    filteredCache = filtered;

                    if (timelineMode === 'matrix') {
                              renderMatrix(groups, filtered, opts);
                    } else {
                              renderListTimeline(groups, filtered);
                    }
          }

          function renderListTimeline(groups, filtered) {
                    let html = '';
                    Object.keys(groups).forEach((label) => {
                              html += `<div class="timeline-group">
                                        <div class="timeline-group-title">${h(label)}</div>`;
                              groups[label].forEach((ev) => {
                                        const labelText = displayEventLabel(ev, ev.event_type_label || 'Event');
                                        const accent = eventTypeAccents[String(ev.event_type_id)] || EVENT_NEUTRAL;
                                        const colorStyle = buildColorStyle(accent);
                                        const minute = ev.minute !== null ? ev.minute : Math.floor(ev.match_second / 60);
                                        const badgeClass = ev.team_side === 'home' ? 'badge-home' : ev.team_side === 'away' ? 'badge-away' : 'badge-unknown';
                                        const player = ev.match_player_name ? `<span>${h(ev.match_player_name)}</span>` : '<span class="text-muted-alt">No player</span>';
                                        html += `<div class="timeline-item" data-id="${ev.id}" data-second="${ev.match_second}" style="${colorStyle}">
                                                  <div class="timeline-top">
                                                            <div><span class="badge-pill ${badgeClass}">${h(ev.team_side || 'unk')}</span> <span class="event-label">${h(labelText)}</span></div>
                                                            <div class="timeline-actions">
                                                                      <span class="text-muted-alt text-xs">${minute}' (${fmtTime(ev.match_second)})</span>
                                                                      <button type="button" class="ghost-btn ghost-btn-sm desk-editable timeline-delete" data-id="${ev.id}">Delete</button>
                                                            </div>
                                                  </div>
                                                  <div class="timeline-meta">
                                                            ${player}
                                                            <span>${ev.tags && ev.tags.length ? `${ev.tags.length} tags` : ''}</span>
                                                  </div>
                                        </div>`;
                              });
                              html += '</div>';
                    });

                    if (!filtered.length) {
                              html = '<div class="text-muted-alt text-sm">No events yet.</div>';
                    }

                    $timelineList.html(html);
                    if (selectedId) {
                              $timelineList.find(`.timeline-item[data-id="${selectedId}"]`).addClass('active');
                    }
          }

                                                  function clampZoom(scale) {
                    return Math.min(timelineZoom.max, Math.max(timelineZoom.min, scale));
          }

          function axisOffset() {
                    return MATRIX_TYPE_WIDTH + MATRIX_GAP;
          }

          function clampScrollValue(value, viewportEl) {
                    const vpWidth = viewportEl ? viewportEl.clientWidth : timelineMetrics.viewportWidth;
                    const maxScroll = Math.max(0, timelineMetrics.totalWidth - vpWidth);
                    return Math.min(Math.max(0, value), maxScroll);
          }

          function renderMatrix(groups, filtered, options = {}) {
                    const buckets = [
                              { label: '0-15', start: 0, end: 900 },
                              { label: '15-30', start: 900, end: 1800 },
                              { label: '30-45', start: 1800, end: 2700 },
                              { label: '45-60', start: 2700, end: 3600 },
                              { label: '60-75', start: 3600, end: 4500 },
                              { label: '75-90', start: 4500, end: 5400 },
                              { label: '90+', start: 5400, end: 6000 },
                              { label: 'ET 1', start: 6000, end: 6900 },
                              { label: 'ET 2', start: 6900, end: 7800 },
                    ];
                    const axisPad = axisOffset();
                    const baseDuration = buckets[buckets.length - 1].end;

                    if (!filtered.length) {
                              timelineMetrics.duration = baseDuration;
                              timelineMetrics.totalWidth = axisPad;
                              timelineMetrics.viewportWidth = $timelineMatrix.width() || 0;
                              timelineZoom.min = 1;
                              timelineZoom.scale = clampZoom(timelineZoom.scale);
                              $timelineMatrix.html('<div class="text-muted-alt text-sm">No events yet.</div>');
                              return;
                    }

                    const rowMap = new Map();
          (filtered || []).forEach((ev) => {
                              const label = displayEventLabel(ev, ev.event_type_label || 'Event');
                              const key = `${ev.event_type_id || 'unknown'}::${label}`;
                              if (!rowMap.has(key)) {
                                        rowMap.set(key, { id: ev.event_type_id, label, events: [] });
                              }
                              rowMap.get(key).events.push(ev);
                    });
                    const typeRows = Array.from(rowMap.values());

                    const maxEventSecond = filtered.reduce((max, ev) => Math.max(max, ev.match_second || 0), 0);
                    timelineMetrics.duration = Math.max(baseDuration, maxEventSecond);
                    const containerWidth = $timelineMatrix.closest('.timeline-scroll').width() || $timelineMatrix.width() || 0;
                    const availableWidth = Math.max(0, containerWidth - axisPad);
                    const baseWidth = timelineMetrics.duration * timelineZoom.pixelsPerSecond;
                    const fitScale = baseWidth > 0 ? availableWidth / baseWidth : 1;
                    const safeFit = fitScale > 0 ? fitScale : 1;
                    timelineZoom.min = Math.min(timelineZoom.max, safeFit);
                    if (!matrixInitialized || options.forceReset) {
                              timelineZoom.scale = timelineZoom.min;
                              matrixInitialized = true;
                    }
                    timelineZoom.scale = clampZoom(timelineZoom.scale);
                    const timelineWidth = timelineMetrics.duration * timelineZoom.pixelsPerSecond * timelineZoom.scale;
                    const bucketWidths = buckets.map((bucket) => {
                              const bucketEnd = Math.min(bucket.end, timelineMetrics.duration);
                              const span = Math.max(0, bucketEnd - bucket.start);
                              return span * timelineZoom.pixelsPerSecond * timelineZoom.scale;
                    });
                    const bucketColumns = bucketWidths.map((w) => `${Math.max(24, w)}px`).join(' ');
                    const gridColumnsStyle = `grid-template-columns: ${MATRIX_TYPE_WIDTH}px ${timelineWidth}px;`;

                    let html = `<div class="matrix-viewport" data-scale="${timelineZoom.scale}">`;
                    html += `<div class="matrix-bucket-row" style="${gridColumnsStyle}">`;
                    html += `<div class="matrix-type-spacer" style="width:${MATRIX_TYPE_WIDTH}px"></div>`;
                    html += `<div class="matrix-bucket-labels" style="grid-template-columns:${bucketColumns}; width:${timelineWidth}px">`;
                    buckets.forEach((b) => {
                              html += `<div class="text-center">${h(b.label)}</div>`;
                    });
                    html += '</div></div>';

                    typeRows.forEach((row) => {
                              const accentColor = eventTypeAccents[String(row.id)] || EVENT_NEUTRAL;
                              const accentStyle = buildColorStyle(accentColor);
                              html += `<div class="matrix-grid" style="${gridColumnsStyle}">`;
                              html += `<div class="matrix-type" style="${accentStyle}">${h(row.label)}</div>`;
                              html += `<div class="matrix-track" style="width:${timelineWidth}px">`;
                              html += `<div class="matrix-row-buckets" style="grid-template-columns:${bucketColumns}; width:${timelineWidth}px">`;
                              buckets.forEach((bucket, idx) => {
                                        html += `<div class="matrix-cell" data-bucket="${idx}" style="width:${bucketWidths[idx]}px"></div>`;
                              });
                              html += '</div>';
                              html += `<div class="matrix-row-events" style="width:${timelineWidth}px">`;
                              row.events.forEach((ev) => {
                                        const imp = parseInt(ev.importance, 10) || 1;
                                        const height = 10 + imp * 2;
                                        const opacity = Math.min(1, 0.4 + imp * 0.1);
                                        const baseAccent = eventTypeAccents[String(row.id)] || EVENT_NEUTRAL;
                                        const teamColor = ev.team_side === 'home' ? '#3b82f6' : ev.team_side === 'away' ? '#f97316' : baseAccent;
                                        const posX = ev.match_second * timelineZoom.pixelsPerSecond * timelineZoom.scale;
                                        const dotStyle = `${buildColorStyle(teamColor)}left:${posX}px; height:${height}px; opacity:${opacity};`;
                                        const labelText = displayEventLabel(ev, row.label);
                                        html += `<span class="matrix-dot" data-second="${ev.match_second}" title="${fmtTime(ev.match_second)} - ${h(labelText)} (${h(ev.team_side || 'team')})" style="${dotStyle}"></span>`;
                              });
                              html += '</div></div></div>';
                    });

                    html += '</div>';

                    $timelineMatrix.html(html);
                    const $viewport = $timelineMatrix.find('.matrix-viewport');
                    timelineMetrics.viewportWidth = $viewport.length ? $viewport[0].clientWidth : availableWidth + axisPad;
                    timelineMetrics.totalWidth = timelineWidth + axisPad;
                    const targetScroll = typeof options.scrollLeft === 'number' ? options.scrollLeft : options.previousScroll || 0;
                    if ($viewport.length) {
                              $viewport[0].scrollLeft = clampScrollValue(targetScroll, $viewport[0]);
                    }
          }

          function getMatrixViewport() {
                    return $timelineMatrix.find('.matrix-viewport');
          }

          function applyTimelineZoom(targetScale, focalX) {
                    const $viewport = getMatrixViewport();
                    const viewportEl = $viewport.length ? $viewport[0] : null;
                    const offset = axisOffset();
                    const currentScale = timelineZoom.scale || 1;
                    const focal = typeof focalX === 'number' ? focalX : viewportEl ? viewportEl.clientWidth / 2 : 0;
                    let timeAtCursor = 0;
                    if (viewportEl) {
                              const timelineX = viewportEl.scrollLeft + focal - offset;
                              timeAtCursor = Math.max(0, timelineX / (timelineZoom.pixelsPerSecond * currentScale));
                    }
                    timelineZoom.scale = clampZoom(targetScale);
                    const targetScroll = viewportEl
                              ? Math.max(0, timeAtCursor * timelineZoom.pixelsPerSecond * timelineZoom.scale + offset - focal)
                              : 0;
                    renderTimeline({ scrollLeft: targetScroll });
          }

          function resetTimelineZoom() {
                    renderTimeline({ scrollLeft: 0, forceReset: true });
          }

          function handleMatrixWheel(e) {
                    const evt = e.originalEvent || e;
                    const viewport = e.currentTarget;
                    if (!viewport) return;
                    if (evt.ctrlKey) {
                              e.preventDefault();
                              const factor = (evt.deltaY || evt.wheelDelta || 0) < 0 ? 1.15 : 1 / 1.15;
                              const rect = viewport.getBoundingClientRect();
                              const focalX = evt.clientX - rect.left;
                              applyTimelineZoom(timelineZoom.scale * factor, focalX);
                              return;
                    }
                    const delta = Math.abs(evt.deltaX || 0) > Math.abs(evt.deltaY || 0) ? evt.deltaX : evt.deltaY;
                    if (!delta) return;
                    e.preventDefault();
                    viewport.scrollLeft = clampScrollValue(viewport.scrollLeft + delta, viewport);
          }

          function handleMatrixPointerDown(e) {
                    if (e.button !== 0) return;
                    if ($(e.target).closest('.matrix-dot').length) return;
                    const viewport = e.currentTarget;
                    matrixPan = { active: true, startX: e.clientX, scrollLeft: viewport.scrollLeft };
                    viewport.setPointerCapture && viewport.setPointerCapture(e.pointerId);
                    $(viewport).addClass('is-dragging');
                    e.preventDefault();
          }

          function handleMatrixPointerMove(e) {
                    if (!matrixPan.active) return;
                    const viewport = e.currentTarget;
                    const dx = matrixPan.startX - e.clientX;
                    viewport.scrollLeft = clampScrollValue(matrixPan.scrollLeft + dx, viewport);
          }

          function handleMatrixPointerUp(e) {
                    if (!matrixPan.active) return;
                    const viewport = e.currentTarget;
                    matrixPan = { active: false, startX: 0, scrollLeft: 0 };
                    viewport.releasePointerCapture && viewport.releasePointerCapture(e.pointerId);
                    $(viewport).removeClass('is-dragging');
          }

          function handleMatrixResize() {
                    if (timelineMode !== 'matrix') return;
                    clearTimeout(resizeTimer);
                    resizeTimer = setTimeout(() => renderTimeline(), 120);
          }

          function setEditorCollapsed(collapsed, hintText, hidePanel = false) {
                    $editorPanel.toggleClass('is-collapsed', collapsed);
                    $editorPanel.toggleClass('is-hidden', !!hidePanel);
                    if (hintText && $editorHint.length) {
                              $editorHint.text(hintText);
                    }
          }

          function fillForm(ev) {
                    if (!ev) {
                              selectedId = null;
                              $eventId.val('');
                              $matchSecond.val('');
                              $minute.val('');
                              $minuteExtra.val('');
                              $teamSide.val('home');
                              $periodId.val('');
                              $eventTypeId.val('');
                              $matchPlayerId.val('');
                              $importance.val('3');
                              $phase.val('');
                              $outcome.val('');
                              $zone.val('');
                              $notes.val('');
                              $tagIds.val([]);
                              clipState = { id: null, start: null, end: null };
                              updateClipUi();
                              refreshOutcomeFieldForEvent(null);
                              setEditorCollapsed(true, 'Click a timeline item to edit details', true);
                              return;
                    }

                    selectedId = ev.id;
                    $eventId.val(ev.id);
                    $matchSecond.val(ev.match_second);
                    $minute.val(ev.minute);
                    $minuteExtra.val(ev.minute_extra);
                    $teamSide.val(ev.team_side || 'unknown');
                    $periodId.val(ev.period_id);
                    $eventTypeId.val(ev.event_type_id);
                    $matchPlayerId.val(ev.match_player_id);
                    $importance.val(ev.importance || 3);
                    $phase.val(ev.phase || '');
                    $outcome.val(ev.outcome || '');
                    $zone.val(ev.zone || '');
                    $notes.val(ev.notes || '');
                    $tagIds.val(ev.tags ? ev.tags.map((t) => t.id) : []);
                    if (ev.clip_id) {
                              clipState = { id: ev.clip_id, start: ev.clip_start_second, end: ev.clip_end_second };
                    } else {
                              clipState = { id: null, start: null, end: null };
                    }
                    updateClipUi();
                    refreshOutcomeFieldForEvent(ev);
                    const labelText = displayEventLabel(ev, ev.event_type_label || 'Event');
                    setEditorCollapsed(false, `${h(labelText)} - ${fmtTime(ev.match_second)}`, false);
          }

          function selectEvent(id) {
                    const ev = events.find((e) => String(e.id) === String(id));
                    if (!ev) return;
                    selectedId = ev.id;
                    $timeline.find('.timeline-item').removeClass('active');
                    $timeline.find(`.timeline-item[data-id="${id}"]`).addClass('active');
                    fillForm(ev);
                    if ($video.length) $video[0].currentTime = ev.match_second;
          }

          function collectData() {
                    let matchSecond = parseInt($matchSecond.val(), 10);
                    if (Number.isNaN(matchSecond) && $video.length) {
                              matchSecond = Math.floor($video[0].currentTime || 0);
                              $matchSecond.val(matchSecond);
                    }
                    let minuteVal = $minute.val();
                    if (!minuteVal && !Number.isNaN(matchSecond)) {
                              minuteVal = Math.floor(matchSecond / 60);
                              $minute.val(minuteVal);
                    }
                    return {
                              match_id: cfg.matchId,
                              event_id: $eventId.val(),
                              match_second: matchSecond,
                              minute: $minute.val(),
                              minute_extra: $minuteExtra.val(),
                              team_side: $teamSide.val(),
                              period_id: $periodId.val(),
                              event_type_id: $eventTypeId.val(),
                              match_player_id: $matchPlayerId.val(),
                              importance: $importance.val(),
                              phase: $phase.val(),
                              outcome: $outcome.val(),
                              zone: $zone.val(),
                              notes: $notes.val(),
                              tag_ids: $tagIds.val() || [],
                    };
          }

          function saveEvent() {
                    if (!lockOwned || !cfg.canEditRole) return;
                    const data = collectData();
                    const endpointKey = data.event_id ? 'eventUpdate' : 'eventCreate';
                    const url = endpoint(endpointKey);
                    if (!url) {
                              showError('Save failed', 'Missing event endpoint');
                              return;
                    }
                    $.post(url, data)
                              .done((res) => {
                                        if (!res.ok) {
                                                  showError('Save failed', res.error || 'Unknown');
                                                  return;
                                        }
                                        hideError();
                                        syncUndoRedoFromMeta(res.meta);
                                        loadEvents();
                                        if (res.event) {
                                                  if (!suppressEditorOpen) {
                                                            selectedId = res.event.id;
                                                            fillForm(res.event);
                                                  } else {
                                                            selectedId = null;
                                                  }
                                        }
                                        setStatus('Saved');
                                        suppressEditorOpen = false;
                              })
                              .fail((xhr, status, error) => {
                                        showError('Save failed', xhr.responseText || error || status);
                                        suppressEditorOpen = false;
                              });
          }

          function performActionStackRequest(endpointKey, label) {
                    if (!lockOwned || !cfg.canEditRole) {
                              showError(`${label} failed`, 'Acquire lock to manage events');
                              return;
                    }
                    const url = endpoint(endpointKey);
                    if (!url) {
                              showError(`${label} failed`, 'Missing endpoint');
                              return;
                    }
                    $.post(url, { match_id: cfg.matchId })
                              .done((res) => {
                                        if (!res.ok) {
                                                  showError(`${label} failed`, res.error || 'Unknown');
                                                  return;
                                        }
                                        hideError();
                                        syncUndoRedoFromMeta(res.meta);
                                        if (res.event) {
                                                  selectedId = res.event.id;
                                                  fillForm(res.event);
                                        } else {
                                                  selectedId = null;
                                                  fillForm(null);
                                        }
                                        loadEvents();
                                        setStatus(`${label} applied`);
                              })
                              .fail((xhr, status, error) => showError(`${label} failed`, xhr.responseText || error || status));
          }

          function deleteEvent() {
                    if (!lockOwned || !cfg.canEditRole || !$eventId.val()) return;
                    deleteEventById($eventId.val(), false);
          }

          function deleteEventById(eventId, skipConfirm) {
                    if (!lockOwned || !cfg.canEditRole || !eventId) return;
                    if (!skipConfirm && !window.confirm('Delete this event?')) return;
                    const url = endpoint('eventDelete');
                    if (!url) {
                              showError('Delete failed', 'Missing event delete endpoint');
                              return;
                    }
                    $.post(url, { match_id: cfg.matchId, event_id: eventId })
                              .done((res) => {
                                       if (!res.ok) {
                                                 showError('Delete failed', res.error || 'Unknown');
                                                 return;
                                       }
                                       hideError();
                                       syncUndoRedoFromMeta(res.meta);
                                       if (String(selectedId) === String(eventId)) {
                                                 selectedId = null;
                                                 fillForm(null);
                                       }
                                       loadEvents();
                                       setStatus('Deleted');
                             })
                              .fail((xhr, status, error) => showError('Delete failed', xhr.responseText || error || status));
          }

          function deleteAllVisible() {
                    if (!lockOwned || !cfg.canEditRole) return;
                    const ids = (filteredCache || []).map((e) => e.id).filter(Boolean);
                    if (!ids.length) return;
                    if (!window.confirm(`Delete ${ids.length} events? This cannot be undone.`)) return;
                    const url = endpoint('eventDelete');
                    if (!url) {
                              showError('Delete failed', 'Missing event delete endpoint');
                              return;
                    }
                    Promise.all(
                              ids.map((id) =>
                                        $.post(url, { match_id: cfg.matchId, event_id: id }).promise().catch((err) => ({ ok: false, error: err })))
                    )
                              .then((results) => {
                                        const failed = results.some((r) => !r || r.ok === false);
                                        if (failed) {
                                                  showError('Some deletes failed', 'Check server logs');
                                        } else {
                                                  hideError();
                                        }
                                        selectedId = null;
                                        fillForm(null);
                                        loadEvents();
                                        setStatus('Deleted all visible');
                              })
                              .catch((e) => showError('Delete failed', e && e.message ? e.message : 'Unknown'));
          }

          function addPeriodMarker(typeKey, note) {
                    const type = (cfg.eventTypes || []).find((t) => t.type_key === typeKey);
                    if (!type) return;
                    const current = $video.length ? Math.floor($video[0].currentTime) : 0;
                    $eventId.val('');
                    $eventTypeId.val(type.id);
                    $teamSide.val('unknown');
                    $matchSecond.val(current);
                    $minute.val(Math.floor(current / 60));
                    $minuteExtra.val('');
                    $notes.val(note || '');
                    $importance.val('1');
                    saveEvent();
          }

          function recordPeriodBoundary(action, periodKey, label) {
                    if (!lockOwned || !cfg.canEditRole || !periodKey || !action) {
                              return;
                    }
                    const url = endpoint('periodsSet');
                    if (!url) {
                              return;
                    }
                    const current = $video.length ? Math.floor($video[0].currentTime) : 0;
                    $.post(url, {
                              match_id: cfg.matchId,
                              period: `${periodKey}_${action}`,
                              video_time: current,
                    })
                              .done((res) => {
                                        if (!res || res.ok === false) {
                                                  showToast(`Unable to ${action} ${label}`, true);
                                                  console.error('Period boundary error', res);
                                                  return;
                                        }
                                        showToast(`${label || 'Period'} ${action === 'end' ? 'ended' : 'started'}`);
                              })
                              .fail((xhr, status, error) => {
                                        showToast(`Unable to ${action} ${label}`, true);
                                        console.error('Period boundary request failed', xhr.responseText || error || status);
                              });
          }

          function handlePeriodAction(typeKey, label, periodKey, action) {
                    suppressEditorOpen = true;
                    recordPeriodBoundary(action, periodKey, label);
                    addPeriodMarker(typeKey, label);
          }

          function updateClipUi() {
                    if (clipState.start !== null) {
                              $clipIn.val(clipState.start);
                              $clipInFmt.text(fmtTime(clipState.start));
                    } else {
                              $clipIn.val('');
                              $clipInFmt.text('');
                    }
                    if (clipState.end !== null) {
                              $clipOut.val(clipState.end);
                              $clipOutFmt.text(fmtTime(clipState.end));
                    } else {
                              $clipOut.val('');
                              $clipOutFmt.text('');
                    }
                    if (clipState.start !== null && clipState.end !== null) {
                              const dur = Math.max(0, clipState.end - clipState.start);
                              $clipDuration.val(`${dur}s (${fmtTime(dur)})`);
                    } else {
                              $clipDuration.val('');
                    }
                    if (!clipState.id) {
                              $btnClipCreate.prop('disabled', !lockOwned || !cfg.canEditRole || !selectedId);
                              $btnClipDelete.prop('disabled', true);
                    } else {
                              $btnClipCreate.prop('disabled', true);
                              $btnClipDelete.prop('disabled', !lockOwned || !cfg.canEditRole);
                    }
          }

          function setClipPoint(type) {
                    if (!lockOwned || !cfg.canEditRole) return;
                    const current = $video.length ? Math.floor($video[0].currentTime) : 0;
                    if (type === 'in') {
                              clipState.start = current;
                    } else {
                              clipState.end = current;
                    }
                    updateClipUi();
          }

          function createClip() {
                    if (!lockOwned || !cfg.canEditRole || !selectedId) return;
                    if (clipState.start === null || clipState.end === null || clipState.end <= clipState.start) {
                              setStatus('Set valid IN/OUT first');
                              return;
                    }
                    const url = endpoint('clipCreate');
                    if (!url) {
                              showError('Clip save failed', 'Missing clip create endpoint');
                              return;
                    }
                    $.post(url, {
                              match_id: cfg.matchId,
                              event_id: selectedId,
                              start_second: clipState.start,
                              end_second: clipState.end,
                    }).done((res) => {
                              if (!res.ok) {
                                        showError('Clip save failed', res.error || 'Unknown');
                                        return;
                              }
                              hideError();
                              clipState.id = res.clip ? res.clip.id : null;
                              updateClipUi();
                              setStatus('Clip saved');
                              loadEvents();
                    }).fail((xhr, status, error) => showError('Clip save failed', xhr.responseText || error || status));
          }

          function deleteClip() {
                    if (!lockOwned || !cfg.canEditRole || !selectedId || !clipState.id) return;
                    const url = endpoint('clipDelete');
                    if (!url) {
                              showError('Clip delete failed', 'Missing clip delete endpoint');
                              return;
                    }
                    $.post(url, { match_id: cfg.matchId, event_id: selectedId })
                              .done((res) => {
                                        if (!res.ok) {
                                                  showError('Clip delete failed', res.error || 'Unknown');
                                                  return;
                                        }
                                        hideError();
                                        clipState = { id: null, start: null, end: null };
                                        updateClipUi();
                                        setStatus('Clip deleted');
                                        loadEvents();
                              })
                              .fail((xhr, status, error) => showError('Clip delete failed', xhr.responseText || error || status));
          }

          function bindHandlers() {
                    $(document).on('click', '#lockRetryBtn', (e) => {
                              e.preventDefault();
                              hideError();
                              acquireLock();
                    });

                    $contextTabs.on('click', '.tab-btn', function () {
                              const ctx = $(this).data('context');
                              setContext(ctx);
                    });

                    $teamToggle.on('click', '.toggle-btn', function () {
                              const team = $(this).data('team');
                              setTeam(team);
                    });

                    $tagBoard.on('click', '.qt-tile', function () {
                              const typeId = $(this).data('type-id');
                              const typeKey = $(this).data('type-key');
                              quickTag(typeKey, typeId, $(this));
                    });

                    $(document).on('click', '#eventUseTimeBtn', () => {
                              const current = $video.length ? Math.floor($video[0].currentTime) : 0;
                              $matchSecond.val(current);
                              $minute.val(Math.floor(current / 60));
                    });
                    $(document).on('click', '#eventSaveBtn', saveEvent);
                    $(document).on('click', '#eventNewBtn', () => {
                              selectedId = null;
                              fillForm(null);
                    });
                    $(document).on('click', '#eventDeleteBtn', deleteEvent);
                    $undoBtn.on('click', () => performActionStackRequest('undoEvent', 'Undo'));
                    $redoBtn.on('click', () => performActionStackRequest('redoEvent', 'Redo'));
                    $eventTypeId.on('change', () => refreshOutcomeField($eventTypeId.val(), $outcome.val()));
                    $(document).on('click', '.period-btn', function () {
                              const $btn = $(this);
                              const periodKey = $btn.data('period-key');
                              const action = $btn.data('period-action') || ($btn.hasClass('period-end') ? 'end' : 'start');
                              const typeKey = $btn.data('period-event') || (action === 'end' ? 'period_end' : 'period_start');
                              const label = $btn.data('period-label') || $btn.text().trim();
                              if (!periodKey || !action) {
                                        return;
                              }
                              handlePeriodAction(typeKey, label, periodKey, action);
                    });
                    $(document).on('click', '#clipInBtn', () => setClipPoint('in'));
                    $(document).on('click', '#clipOutBtn', () => setClipPoint('out'));
                    $(document).on('click', '#clipCreateBtn', createClip);
                    $(document).on('click', '#clipDeleteBtn', deleteClip);

                    $filterTeam.on('change', renderTimeline);
                    $filterType.on('change', renderTimeline);
                    $filterPlayer.on('change', renderTimeline);
                    $timelineList.on('click', '.timeline-item', function () {
                              const id = $(this).data('id');
                              selectEvent(id);
                    });
                    $timelineList.on('click', '.timeline-delete', function (e) {
                              e.stopPropagation();
                              const id = $(this).data('id');
                              deleteEventById(id, false);
                    });
                    $(document).on('click', '#timelineDeleteAll', deleteAllVisible);
                    $timelineModeBtns.on('click', function () {
                              const mode = $(this).data('mode');
                              setTimelineMode(mode);
                    });
                    $timelineMatrix.on('wheel', '.matrix-viewport', handleMatrixWheel);
                    $timelineMatrix.on('pointerdown', '.matrix-viewport', handleMatrixPointerDown);
                    $timelineMatrix.on('pointermove', '.matrix-viewport', handleMatrixPointerMove);
                    $timelineMatrix.on('pointerup pointerleave pointercancel', '.matrix-viewport', handleMatrixPointerUp);
                    $(document).on('click', '#timelineZoomIn', () => applyTimelineZoom(timelineZoom.scale * 1.15));
                    $(document).on('click', '#timelineZoomOut', () => applyTimelineZoom(timelineZoom.scale / 1.15));
                    $(document).on('click', '#timelineZoomReset', resetTimelineZoom);
                    $timelineScroll.on('wheel', function (e) {
                              const evt = e.originalEvent || e;
                              if (evt && evt.ctrlKey) {
                                        e.preventDefault();
                              }
                    });
                    $(window).on('wheel', function (e) {
                              const evt = e.originalEvent || e;
                              if (!evt || !evt.ctrlKey) return;
                              const $t = $(evt.target);
                              if ($t.closest('.timeline-scroll').length) {
                                        e.preventDefault();
                              }
                    });
                    $(window).on('resize', handleMatrixResize);
                    $timelineMatrix.on('click', '.matrix-dot', function () {
                              const sec = $(this).data('second');
                              if ($video.length && sec !== undefined) {
                                        $video[0].currentTime = sec;
                              }
                    });

                    if ($video.length) {
                              const savedTime = window.localStorage ? parseFloat(window.localStorage.getItem(VIDEO_TIME_KEY)) : 0;
                              if (!Number.isNaN(savedTime) && savedTime > 0) {
                                        $video[0].currentTime = savedTime;
                              }
                              const persistTime = () => {
                                        if (!window.localStorage || !$video.length) return;
                                        const t = $video[0].currentTime || 0;
                                        window.localStorage.setItem(VIDEO_TIME_KEY, Math.floor(t));
                              };
                              $video.on('timeupdate seeked pause', persistTime);
                              $(window).on('beforeunload', persistTime);
                    }
          }

          function init() {
                    if ($jsBadge.length) $jsBadge.text('JS');
                    applyQuickTagReplacements();
                    buildTypeMap();
                    rebuildQuickTagBoard();
                    syncEventTypeOptions();
                    renderTagGrid();
                    setContext(currentContext);
                    setTeam(currentTeam);
                    fillForm(null);
                    setTimelineMode(timelineMode);
                    applyMode(false, {});
                    bindHandlers();
                    acquireLock();
                    updateClipUi();
                    loadEvents();
          }

          $(init);
})(jQuery);
