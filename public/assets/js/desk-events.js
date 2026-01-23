// Ensure playlist filter popover is hidden on page load
// --- Playlist Filter Popover Dropdown ---
$(function () {
      var $playlistFilterPopover = $('#playlistFilterPopover');
      if ($playlistFilterPopover.length) {
            $playlistFilterPopover.attr('hidden', '');
      }
});
// --- Skeleton Loader Synchronization ---
let deskVideoReady = false;
let deskTimelineReady = false;
function tryHideDeskSkeleton() {
      if (deskVideoReady && deskTimelineReady && window.deskHideSkeleton) {
            window.deskHideSkeleton();
      }
}
// --- Playlist Filter Popover Dropdown ---
$(function () {
      $playlistFilterBtn = $('#playlistFilterBtn');
      $playlistFilterPopover = $('#playlistFilterPopover');
      if ($playlistFilterBtn.length && $playlistFilterPopover.length) {
            function closePopover() {
                  $playlistFilterPopover.attr('hidden', '');
                  $playlistFilterBtn.attr('aria-expanded', 'false');
            }
            function openPopover() {
                  $playlistFilterPopover.removeAttr('hidden');
                  $playlistFilterBtn.attr('aria-expanded', 'true');
            }
            $playlistFilterBtn.on('click', function (e) {
                  e.stopPropagation();
                  if ($playlistFilterPopover.is(':visible')) {
                        closePopover();
                  } else {
                        openPopover();
                  }
            });
            // Hide popover when clicking outside
            $(document).on('mousedown', function (e) {
                  if (
                        !$playlistFilterPopover.is(e.target) &&
                        $playlistFilterPopover.has(e.target).length === 0 &&
                        !$playlistFilterBtn.is(e.target)
                  ) {
                        closePopover();
                  }
            });
            // Hide on ESC
            $(document).on('keydown', function (e) {
                  if (e.key === 'Escape') closePopover();
            });
      }
});

/* global jQuery */
(function ($) {
      const cfg = window.DeskConfig;
      const annotationsEnabled = window.ANNOTATIONS_ENABLED !== false;
      if (!cfg) return;
      const csrfToken = cfg.csrfToken || null;
      if (csrfToken && $.ajaxSetup) {
            $.ajaxSetup({
                  headers: { 'X-CSRF-Token': csrfToken },
            });
      }

      const endpoints = cfg.endpoints || {};
      const playlistConfig = {
            list: endpoints.playlistsList,
            create: endpoints.playlistCreate,
            addClip: endpoints.playlistClipsAdd,
            removeClip: endpoints.playlistClipsRemove,
            reorder: endpoints.playlistClipsReorder,
            rename: endpoints.playlistRename,
            delete: endpoints.playlistDelete,
            show: (id) => (endpoints.playlistsList ? `${endpoints.playlistsList}/${id}` : null),
            download: endpoints.playlistDownload,
      };
      const playlistState = {
            playlists: [],
            activePlaylistId: null,
            clips: [],
            activeIndex: -1,
      };
      const playlistViewState = {
            teamFilter: '',
            searchQuery: '',
      };
      let missingClipToastShown = false;
      let playlistFilterPopoverOpen = false;
      const clipPlaybackState = {
            mode: 'match',
            clipId: null,
            startSecond: null,
            endSecond: null,
      };
      window.DeskClipPlaybackState = clipPlaybackState;

      const $video = $('#deskVideoPlayer');
      if ($video.length) {
            $video.on('loadedmetadata', function () {
                  deskVideoReady = true;
                  tryHideDeskSkeleton();
            });
      }
      const $timelineList = $('#timelineList');
      const $timelineMatrix = $('#timelineMatrix');
      const $timelineScroll = $('.timeline-scroll');
      const $timeline = $('.timeline-panel');
      const $status = $('#deskStatus');

      const $lockStatus = $('#lockStatusText');
      const $btnAcquire = $('#lockRetryBtn');
      const $deskError = $('#deskError');

      const $contextTabs = $('#contextTabs');
      const $teamToggle = $('#teamToggle');
      const $tagBoard = $('#quickTagBoard');
      const $tagToast = $('#tagToast');
      const $jsBadge = $('#jsBadge');
      let $playlistPanel;
      let $playlistList;
      let $playlistCreateForm;
      let $playlistTitleInput;
      let $playlistAddClipBtn;
      let $playlistClips;
      let $playlistActiveTitle;
      let $playlistPrevBtn;
      let $playlistNextBtn;
      let $playlistRefreshBtn;
      let $playlistFilterBtn;
      let $playlistFilterPopover;
      let $playlistSearchToggle;
      let $playlistSearchRow;
      let $playlistSearchInput;
      let $playlistCreateToggle;
      let $playlistCreateRow;
      let playlistTitleEditor = null;
      const $editorPanel = $('#editorPanel');
      const $editorHint = $('#editorHint');
      const $editorTabs = $editorPanel.find('.editor-tab');
      const $editorTabPanels = $editorPanel.find('.editor-tab-panel');
      const $editorTabOutcome = $('#editorTabOutcome');

      const $eventId = $('#eventId');
      const $matchSecond = $('#match_second');
      const $minute = $('#minute');
      const $minuteExtra = $('#minute_extra');
      const $timeDisplay = $('#event_time_display');
      const $timeStepDown = $('#eventTimeStepDown');
      const $timeStepUp = $('#eventTimeStepUp');
      const $minuteExtraDisplay = $('#minute_extra_display');
      const $teamSide = $('#team_side');
      const $periodId = $('#period_id');
      const $periodHelperText = $('#periodHelperText');
      const $periodsModal = $('#periodsModal');
      const $periodsModalToggle = $('.period-modal-toggle');
      const periodsModalFocusableSelector =
            'button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';
      let periodModalLastFocused = null;
      const currentPeriodStatusEl = document.getElementById('currentPeriodStatus');
      const periodButtonNodes = Array.from(document.querySelectorAll('.period-btn'));
      const PERIOD_SEQUENCE = ['first_half', 'second_half', 'extra_time_1', 'extra_time_2', 'penalties'];
      const periodDefinitions = {};
      periodButtonNodes.forEach((button) => {
            const key = button.dataset.periodKey;
            if (!key) return;
            if (!periodDefinitions[key]) {
                  periodDefinitions[key] = {
                        key,
                        label: button.dataset.periodLabel || '',
                        startButton: null,
                        endButton: null,
                  };
            }
            const target = periodDefinitions[key];
            if (button.dataset.periodAction === 'end' || button.classList.contains('period-end')) {
                  target.endButton = button;
            } else if (button.dataset.periodAction === 'start' || button.classList.contains('period-start')) {
                  target.startButton = button;
            }
      });
      PERIOD_SEQUENCE.forEach((key) => {
            if (!periodDefinitions[key]) {
                  periodDefinitions[key] = { key, label: '', startButton: null, endButton: null };
            }
      });
      const periodLabelToKey = new Map();
      Object.values(periodDefinitions).forEach((def) => {
            const normalized = normalizePeriodLabel(def.label);
            if (normalized) {
                  periodLabelToKey.set(normalized, def.key);
            }
      });
      let periodState = {};
      let periodTimer = null;
      let periodTimerKey = null;
      let periodTimerStart = null;
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
            goal: '#22C55E',
            goal_for: '#22C55E',
            goal_against: '#22C55E',
            shot: '#0EA5E9',
            chance: '#FFB340',
            big_chance: '#9C27B0',
            corner: '#FF9800',
            corner_for: '#FF9800',
            corner_against: '#FF9800',
            freekick: '#009688',
            free_kick: '#009688',
            free_kick_for: '#009688',
            free_kick_against: '#009688',
            penalty: '#E91E63',
            foul: '#795548',
            card: '#FFEB3B',
            yellow_card: '#FFEB3B',
            red_card: '#D62828',
            mistake: '#9E9E9E',
            turnover: '#9E9E9E',
            good_play: '#7CC378',
            highlight: '#3F51B5',
            other: '#B8C1EC',
      };
      const EVENT_NEUTRAL = '#B8C1EC';
      const VIDEO_TIME_KEY = cfg && cfg.matchId ? `deskVideoTime_${cfg.matchId}` : 'deskVideoTime';
      const DRAWING_WINDOW_FALLBACK_SECONDS = 5;
      const PERIOD_EVENT_KEYS = new Set(['period_start', 'period_end']);
      const isPeriodEvent = (ev) => !!ev && PERIOD_EVENT_KEYS.has(ev.event_type_key);
      const getEventClipId = (ev) => {
            if (!ev) return null;
            const candidates = [
                  ev.clip_id,
                  ev.clipId,
                  ev.clipid,
                  ev.clip && ev.clip.id,
                  ev.clip && ev.clip.clip_id,
            ];
            for (const c of candidates) {
                  const n = Number(c);
                  if (Number.isFinite(n) && n > 0) return n;
            }
            return null;
      };

      const getEventOrClipId = (ev) => {
            const clipId = getEventClipId(ev);
            if (clipId !== null) return clipId;
            // Fallback: use event ID as clip ID when no explicit clip exists
            const eventId = Number(ev && ev.id);
            return Number.isFinite(eventId) && eventId > 0 ? eventId : null;
      };

      let heartbeatTimer = null;
      let lockOwned = false;
      let events = [];
      let filteredCache = [];
      let selectedId = null;
      let clipState = { id: null, start: null, end: null };
      let draggingClipId = null;
      let dropTargetElement = null;
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
      const GOAL_EVENT_KEYS = new Set(['goal', 'goal_for', 'goal_against']);
      const SHOT_EVENT_KEYS = new Set(['shot', 'shot_on_target', 'shot_off_target']);
      const CARD_EVENT_KEYS = new Set(['card', 'yellow_card', 'red_card']);
      const shotOutcomeLabels = {
            on_target: 'On Target',
            off_target: 'Off Target',
      };
      const players = Array.isArray(cfg.players) ? cfg.players : [];
      const teamSideLabels = {
            home: cfg.homeTeamName || 'Home',
            away: cfg.awayTeamName || 'Away',
            unknown: 'Unknown',
      };
      const $goalPlayerModal = $('#goalPlayerModal');
      const $goalPlayerList = $('#goalPlayerList');
      const $shotPlayerModal = $('#shotPlayerModal');
      const $shotPlayerList = $('#shotPlayerList');
      const $cardPlayerModal = $('#cardPlayerModal');
      const $cardPlayerList = $('#cardPlayerList');
      let goalModalState = { payload: null, label: '', wasPlaying: false };
      let shotModalState = { payload: null, label: '', wasPlaying: false, selectedPlayerId: null, selectedOutcome: null };
      let cardModalState = { payload: null, label: '', wasPlaying: false };
      const MATRIX_TYPE_WIDTH = 160;
      const MATRIX_GAP = 8;
      const timelineZoom = {
            scale: 1,
            max: 10,
            pixelsPerSecond: 3,
      };
      const timelineMetrics = { duration: 0, totalWidth: 0, viewportWidth: 0 };
      const annotationTargetId = (() => {
            if (!annotationsEnabled) {
                  return null;
            }
            const raw =
                  cfg.annotations && cfg.annotations.matchVideoId
                        ? cfg.annotations.matchVideoId
                        : cfg.video && cfg.video.match_video_id
                              ? cfg.video.match_video_id
                              : null;
            const parsed = Number(raw);
            return Number.isFinite(parsed) && parsed > 0 ? parsed : null;
      })();
      let annotationBridge = null;
      let timelineAnnotations = [];
      let matrixPan = { active: false, startX: 0, scrollLeft: 0 };
      let drawingDragState = null;
      let matrixWheelListenerBound = false;
      let resizeTimer = null;
      let editorDirty = false;
      let suppressDirtyTracking = false;
      let activeEditorTab = 'details';
      let editorOpen = false;
      let lastKnownMatchSecond = 0;
      const modifierState = { shift: false, ctrl: false, meta: false };

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
            const glow = `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, 0.35)`;
            return `--event-color:${base};--event-color-soft:${soft};--event-color-strong:${strong};--event-color-glow:${glow};`;
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
      setActiveEditorTab('details');
      updateOutcomeTabVisibility(resolveOutcomeOptions($eventTypeId.val()));

      function refreshOutcomeField(typeId, selectedOutcome = '') {
            if (!$outcomeField.length || !$outcome.length) return;
            const options = resolveOutcomeOptions(typeId);
            updateOutcomeTabVisibility(options);
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

      function formatMatchSecond(totalSeconds) {
            const normalized = Math.max(0, Math.floor(Number(totalSeconds) || 0));
            const minutes = Math.floor(normalized / 60);
            const seconds = normalized % 60;
            const text = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            return { total: normalized, minutes, seconds, text };
      }

      function fmtTime(sec) {
            return formatMatchSecond(sec).text;
      }

      function parseMatchTimeInput(value) {
            if (typeof value !== 'string') return null;
            const trimmed = value.trim();
            if (trimmed === '') return null;
            const match = trimmed.match(/^(\d+):(\d{1,2})$/);
            if (!match) return null;
            const minutes = parseInt(match[1], 10);
            const seconds = parseInt(match[2], 10);
            if (Number.isNaN(minutes) || Number.isNaN(seconds)) return null;
            if (minutes < 0 || seconds < 0 || seconds > 59) return null;
            return minutes * 60 + seconds;
      }

      function updateTimeFromSeconds(value) {
            // match_second is the canonical timestamp; minute follows from it and minute_extra holds stoppage metadata.
            const normalized = Math.max(0, Math.floor(Number(value) || 0));
            lastKnownMatchSecond = normalized;
            const derivedMinute = Math.floor(normalized / 60);
            $matchSecond.val(normalized);
            $minute.val(derivedMinute);
            if ($timeDisplay.length) {
                  $timeDisplay.val(formatMatchSecond(normalized).text);
            }
      }

      function updateMinuteExtraFields(value) {
            const parsed = Math.max(0, parseInt(value, 10) || 0);
            $minuteExtra.val(parsed);
            if ($minuteExtraDisplay.length) {
                  $minuteExtraDisplay.val(parsed);
            }
      }

      function handleTimeDisplayInput() {
            const parsed = parseMatchTimeInput($timeDisplay.val());
            if (parsed === null) return;
            updateTimeFromSeconds(parsed);
      }

      function handleTimeDisplayBlur() {
            const parsed = parseMatchTimeInput($timeDisplay.val());
            if (parsed === null) {
                  updateTimeFromSeconds(lastKnownMatchSecond);
                  return;
            }
            updateTimeFromSeconds(parsed);
      }

      function refreshPeriodHelperText() {
            if (!$periodHelperText.length) return;
            const selectedValue = $periodId.val();
            if (!selectedValue) {
                  $periodHelperText.text('');
                  return;
            }
            const $option = $periodId.find('option:selected');
            const startAttr = $option.attr('data-start-second');
            const endAttr = $option.attr('data-end-second');
            const startValue = startAttr !== undefined && startAttr !== '' ? parseInt(startAttr, 10) : null;
            const endValue = endAttr !== undefined && endAttr !== '' ? parseInt(endAttr, 10) : null;
            const startLabel = startValue !== null && !Number.isNaN(startValue) ? `${startValue}s` : 'start not set';
            const endLabel = endValue !== null && !Number.isNaN(endValue) ? `${endValue}s` : 'end not set';
            $periodHelperText.text(`Period runs from ${startLabel} to ${endLabel}`);
      }

      function applyTimeStep(delta) {
            const current = Math.max(0, parseInt($matchSecond.val(), 10) || 0);
            updateTimeFromSeconds(current + delta);
      }

      function getStepperStep(event) {
            if (event && (event.ctrlKey || event.metaKey)) return 10;
            if (event && event.shiftKey) return 5;
            if (modifierState.ctrl || modifierState.meta) return 10;
            if (modifierState.shift) return 5;
            return 1;
      }

      function setupTimeStepper($button, direction) {
            let holdTimer = null;
            let repeatTimer = null;
            const stopRepeat = () => {
                  if (holdTimer) {
                        clearTimeout(holdTimer);
                        holdTimer = null;
                  }
                  if (repeatTimer) {
                        clearInterval(repeatTimer);
                        repeatTimer = null;
                  }
            };

            const stepOnce = (event) => {
                  const step = getStepperStep(event);
                  applyTimeStep(step * direction);
            };

            const startRepeat = () => {
                  stopRepeat();
                  holdTimer = setTimeout(() => {
                        repeatTimer = setInterval(() => {
                              stepOnce();
                        }, 120);
                  }, 400);
            };

            const handlePointerDown = (event) => {
                  event.preventDefault();
                  stepOnce(event);
                  startRepeat();
            };

            const handlePointerUp = () => stopRepeat();

            const buttonEl = $button && $button[0];
            if (!buttonEl) return;
            if (buttonEl.dataset.deskStepperBound) return;
            buttonEl.dataset.deskStepperBound = '1';
            buttonEl.addEventListener('mousedown', handlePointerDown, { passive: false }); // Non-passive so we can prevent default for immediate stepping.
            buttonEl.addEventListener('touchstart', handlePointerDown, { passive: false }); // Non-passive because we call preventDefault to stop scrolling while stepping.
            const passiveEndOptions = { passive: true };
            ['mouseup', 'mouseleave'].forEach((type) => {
                  buttonEl.addEventListener(type, handlePointerUp, passiveEndOptions); // Passive: release events just stop the repeat timer.
            });
            ['touchend', 'touchcancel'].forEach((type) => {
                  buttonEl.addEventListener(type, handlePointerUp, { passive: true }); // Passive: just stops the hold action without preventing default.
            });
            $button.on('keydown', (event) => {
                  if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        stepOnce(event);
                  }
            });
      }

      function updateModifierState(key, isActive) {
            if (key === 'Shift') {
                  modifierState.shift = isActive;
                  return;
            }
            if (key === 'Control') {
                  modifierState.ctrl = isActive;
                  return;
            }
            if (key === 'Meta') {
                  modifierState.meta = isActive;
                  return;
            }
      }

      function formatMatchSecondWithExtra(seconds, extraValue) {
            const base = formatMatchSecond(seconds);
            const parsedExtra = parseInt(extraValue, 10);
            const extra = Number.isNaN(parsedExtra) ? 0 : Math.max(0, parsedExtra);
            return extra > 0 ? `${base.text}+${extra}` : base.text;
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
            /*   
            let html = `<div class="qt-board-head">
                         <div class="qt-board-title">${h(quickTagBoard.title || 'Quick Tags')}</div>
               </div>`;
            */
            let html = '<div class="qt-grid">';

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

      function getSortedPlayers() {
            if (!Array.isArray(players) || players.length === 0) {
                  return [];
            }
            const order = { home: 0, away: 1, unknown: 2 };
            return [...players].sort((a, b) => {
                  const sideA = a.team_side || 'unknown';
                  const sideB = b.team_side || 'unknown';
                  const diff = (order[sideA] ?? 3) - (order[sideB] ?? 3);
                  if (diff !== 0) {
                        return diff;
                  }
                  return String(a.display_name || '').localeCompare(String(b.display_name || ''));
            });
      }

      function buildPlayerOptionsHtml(primaryClass, secondaryClass = '') {
            const sortedPlayers = getSortedPlayers();
            if (!sortedPlayers.length) {
                  return '<div class="text-sm text-muted-alt">No players available</div>';
            }
            return sortedPlayers
                  .map((player) => {
                        const teamSide = player.team_side || 'unknown';
                        const teamLabel = teamSideLabels[teamSide] || teamSideLabels.unknown;
                        const shirt = player.shirt_number ? ` #${player.shirt_number}` : '';
                        const playerId = String(player.id || '');
                        const label = player.display_name || 'Player';
                        const baseClasses = [primaryClass];
                        if (secondaryClass) {
                              baseClasses.push(...secondaryClass.split(' ').filter(Boolean));
                        }
                        const baseClassList = baseClasses.join(' ');
                        const sideClassList = baseClasses.map((cls) => `${cls}--${teamSide}`).join(' ');
                        const optionClassList = `${baseClassList} ${sideClassList}`.trim();
                        return `<button type="button" class="${optionClassList}" data-player-id="${h(playerId)}" data-team-side="${h(teamSide)}">
                                                  <span class="goal-player-option-name">${h(label)}${shirt ? h(shirt) : ''}</span>
                                                  <span class="goal-player-option-meta">${h(teamLabel)}</span>
                                        </button>`;
                  })
                  .join('');
      }

      /**
       * Build player selector HTML for event editor (team-first flow)
       * Groups players by Starting XI and Substitutes in two columns
       */
      function buildEventEditorPlayerListHtml(teamSide) {
            if (!teamSide || !['home', 'away'].includes(teamSide)) {
                  return;
            }

            const teamPlayers = Array.isArray(players)
                  ? players.filter((p) => (p.team_side || 'unknown') === teamSide)
                  : [];

            // Separate Starting XI and Substitutes (is_starting is 1 for starting, 0 for subs)
            const startingXI = teamPlayers.filter((p) => p.is_starting === 1 || p.is_starting === true);
            const substitutes = teamPlayers.filter((p) => p.is_starting !== 1 && p.is_starting !== true);

            // Build Starting XI buttons
            let startingHtml = '';
            if (startingXI.length > 0) {
                  startingXI.forEach((player) => {
                        const shirt = player.shirt_number ? ` #${player.shirt_number}` : '';
                        const playerId = String(player.id || '');
                        const label = player.display_name || 'Player';
                        startingHtml += `<button type="button" class="player-selector-btn desk-editable" data-player-id="${h(playerId)}">${h(label)}${shirt ? h(shirt) : ''}</button>`;
                  });
            }

            // Build Substitutes buttons
            let subsHtml = '';
            if (substitutes.length > 0) {
                  substitutes.forEach((player) => {
                        const shirt = player.shirt_number ? ` #${player.shirt_number}` : '';
                        const playerId = String(player.id || '');
                        const label = player.display_name || 'Player';
                        subsHtml += `<button type="button" class="player-selector-btn desk-editable" data-player-id="${h(playerId)}">${h(label)}${shirt ? h(shirt) : ''}</button>`;
                  });
            }

            return { startingHtml, subsHtml };
      }

      /**
       * Update event editor player list when team is selected
       */
      function updateEventEditorPlayerList(teamSide) {
            const $playerContainer = $('#playerSelectorContainer');
            const $playerStarting = $('#playerSelectorStarting');
            const $playerSubs = $('#playerSelectorSubs');
            const $playerInput = $('#match_player_id');

            if (!$playerContainer.length || !$playerStarting.length || !$playerSubs.length) {
                  return;
            }

            if (!teamSide || !['home', 'away'].includes(teamSide)) {
                  $playerContainer.hide();
                  $playerStarting.html('');
                  $playerSubs.html('');
                  $playerInput.val('');
                  return;
            }

            // Show player container
            $playerContainer.show();

            // Build and render player lists
            const playerLists = buildEventEditorPlayerListHtml(teamSide);
            if (!playerLists || (!playerLists.startingHtml && !playerLists.subsHtml)) {
                  $playerStarting.html('<div class="text-sm text-muted-alt">No players available</div>');
                  $playerSubs.html('');
                  return;
            }

            $playerStarting.html(playerLists.startingHtml || '<div class="text-sm text-muted-alt">None</div>');
            $playerSubs.html(playerLists.subsHtml || '<div class="text-sm text-muted-alt">None</div>');

            // Add event listeners to player buttons
            $playerContainer.find('.player-selector-btn').on('click', function (e) {
                  e.preventDefault();
                  const playerId = $(this).data('player-id');

                  // Remove selected class from all buttons
                  $playerContainer.find('.player-selector-btn').removeClass('selected');

                  // Add selected class to clicked button
                  $(this).addClass('selected');

                  // Update the hidden input
                  $playerInput.val(playerId);
                  editorDirty = true;
            });

            // Set selected state if player is already selected
            const currentPlayerId = $playerInput.val();
            if (currentPlayerId) {
                  $playerContainer.find(`.player-selector-btn[data-player-id="${currentPlayerId}"]`).addClass('selected');
            }
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

      function isGoalTypeKey(key) {
            if (!key) return false;
            return GOAL_EVENT_KEYS.has(String(key).toLowerCase());
      }

      function isShotTypeKey(key) {
            if (!key) return false;
            return SHOT_EVENT_KEYS.has(String(key).toLowerCase());
      }

      function isCardTypeKey(key) {
            if (!key) return false;
            return CARD_EVENT_KEYS.has(String(key).toLowerCase());
      }

      function setGoalPlayerOptionsEnabled(enabled = true) {
            if (!$goalPlayerModal.length) return;
            if ($goalPlayerList.length) {
                  $goalPlayerList.find('.goal-player-option').prop('disabled', !enabled);
            }
            $goalPlayerModal.find('[data-goal-unknown]').prop('disabled', !enabled);
      }

      function renderGoalPlayerList() {
            if (!$goalPlayerList.length) return;
            $goalPlayerList.html(buildPlayerOptionsHtml('goal-player-option'));
      }

      function openGoalPlayerModal(payload, label) {
            if (!$goalPlayerModal.length) return;
            if (!players.length) {
                  showError('No players available', 'Add match players before logging goals');
                  return;
            }
            goalModalState.payload = { ...payload };
            goalModalState.label = label || 'Goal';
            goalModalState.wasPlaying = !!($video.length && !$video[0].paused);
            if ($video.length) {
                  $video[0].pause();
            }
            setGoalPlayerOptionsEnabled(true);
            $goalPlayerModal.removeAttr('hidden').attr('aria-hidden', 'false').addClass('is-active');
      }

      function closeGoalPlayerModal() {
            if (!$goalPlayerModal.length) return;
            const wasPlaying = goalModalState.wasPlaying;
            goalModalState.payload = null;
            goalModalState.label = '';
            goalModalState.wasPlaying = false;
            setGoalPlayerOptionsEnabled(true);
            $goalPlayerModal.attr('aria-hidden', 'true').attr('hidden', 'hidden').removeClass('is-active');
            if (wasPlaying && $video.length) {
                  const videoEl = $video[0];
                  if (videoEl.paused) {
                        videoEl.play().catch(() => { });
                  }
            }
      }

      function handleGoalPlayerSelection(event) {
            const $btn = $(event.currentTarget);
            if (!goalModalState.payload) return;
            const playerId = $btn.data('player-id');
            if (!playerId) return;
            const payload = {
                  ...goalModalState.payload,
                  match_player_id: playerId,
            };
            setGoalPlayerOptionsEnabled(false);
            sendGoalEventRequest(payload, goalModalState.label);
      }

      function handleGoalUnknownClick(event) {
            event.preventDefault();
            if (!goalModalState.payload) return;
            setGoalPlayerOptionsEnabled(false);
            const payload = {
                  ...goalModalState.payload,
                  match_player_id: null,
            };
            sendGoalEventRequest(payload, goalModalState.label);
      }

      function sendGoalEventRequest(payload, label) {
            const url = endpoint('eventCreate');
            if (!url) {
                  showError('Save failed', 'Missing event endpoint');
                  setGoalPlayerOptionsEnabled(true);
                  return;
            }
            $.post(url, payload)
                  .done((res) => {
                        if (!res.ok) {
                              showError('Save failed', res.error || 'Unknown');
                              setGoalPlayerOptionsEnabled(true);
                              return;
                        }
                        hideError();
                        syncUndoRedoFromMeta(res.meta);
                        selectedId = null;
                        setEditorCollapsed(true, 'Click a timeline item to edit details', true);
                        showToast(
                              `${label || 'Goal'} tagged at ${formatMatchSecondWithExtra(
                                    payload.match_second,
                                    payload.minute_extra || 0
                              )}`
                        );
                        setStatus('Tagged');
                        loadEvents();
                        closeGoalPlayerModal();
                  })
                  .fail((xhr, status, error) => {
                        showError('Save failed', xhr.responseText || error || status);
                        setGoalPlayerOptionsEnabled(true);
                  });
      }

      function setCardPlayerOptionsEnabled(enabled = true) {
            if (!$cardPlayerModal.length) return;
            if ($cardPlayerList.length) {
                  $cardPlayerList.find('.goal-player-option').prop('disabled', !enabled);
            }
            $cardPlayerModal.find('[data-card-unknown]').prop('disabled', !enabled);
      }

      function renderCardPlayerList() {
            if (!$cardPlayerList.length) return;
            $cardPlayerList.html(buildPlayerOptionsHtml('goal-player-option'));
      }

      function openCardPlayerModal(payload, label) {
            if (!$cardPlayerModal.length) return;
            if (!players.length) {
                  showError('No players available', 'Add match players before logging cards');
                  return;
            }
            cardModalState.payload = { ...payload };
            cardModalState.label = label || 'Card';
            cardModalState.wasPlaying = !!($video.length && !$video[0].paused);
            if ($video.length) {
                  $video[0].pause();
            }
            renderCardPlayerList();
            setCardPlayerOptionsEnabled(true);
            $cardPlayerModal.removeAttr('hidden').attr('aria-hidden', 'false').addClass('is-active');
      }

      function closeCardPlayerModal() {
            if (!$cardPlayerModal.length) return;
            const wasPlaying = cardModalState.wasPlaying;
            cardModalState.payload = null;
            cardModalState.label = '';
            cardModalState.wasPlaying = false;
            setCardPlayerOptionsEnabled(true);
            $cardPlayerModal.attr('aria-hidden', 'true').attr('hidden', 'hidden').removeClass('is-active');
            if (wasPlaying && $video.length) {
                  const videoEl = $video[0];
                  if (videoEl.paused) {
                        videoEl.play().catch(() => { });
                  }
            }
      }

      function handleCardPlayerSelection(event) {
            const $btn = $(event.currentTarget);
            if (!cardModalState.payload) return;
            const playerId = $btn.data('player-id');
            if (!playerId) return;
            setCardPlayerOptionsEnabled(false);
            const payload = {
                  ...cardModalState.payload,
                  match_player_id: playerId,
            };
            sendCardEventRequest(payload, cardModalState.label);
      }

      function handleCardUnknownClick(event) {
            event.preventDefault();
            if (!cardModalState.payload) return;
            setCardPlayerOptionsEnabled(false);
            const payload = {
                  ...cardModalState.payload,
                  match_player_id: null,
            };
            sendCardEventRequest(payload, cardModalState.label);
      }

      function sendCardEventRequest(payload, label) {
            const url = endpoint('eventCreate');
            if (!url) {
                  showError('Save failed', 'Missing event endpoint');
                  setCardPlayerOptionsEnabled(true);
                  return;
            }
            $.post(url, payload)
                  .done((res) => {
                        if (!res.ok) {
                              showError('Save failed', res.error || 'Unknown');
                              setCardPlayerOptionsEnabled(true);
                              return;
                        }
                        hideError();
                        syncUndoRedoFromMeta(res.meta);
                        selectedId = null;
                        setEditorCollapsed(true, 'Click a timeline item to edit details', true);
                        showToast(
                              `${label || 'Card'} tagged at ${formatMatchSecondWithExtra(
                                    payload.match_second,
                                    payload.minute_extra || 0
                              )}`
                        );
                        setStatus('Tagged');
                        loadEvents();
                        closeCardPlayerModal();
                  })
                  .fail((xhr, status, error) => {
                        showError('Save failed', xhr.responseText || error || status);
                        setCardPlayerOptionsEnabled(true);
                  });
      }

      function setShotPlayerSelection(playerId) {
            shotModalState.selectedPlayerId = playerId || null;
            if (!$shotPlayerList.length) return;
            $shotPlayerList.find('.shot-player-option').removeClass('is-selected');
            if (playerId) {
                  $shotPlayerList
                        .find(`.shot-player-option[data-player-id="${playerId}"]`)
                        .addClass('is-selected');
            }
      }

      function setShotOutcomeSelection(outcome) {
            const normalized = outcome ? String(outcome) : null;
            shotModalState.selectedOutcome = normalized;
            if (!$shotPlayerModal.length) return;
            $shotPlayerModal
                  .find('.shot-outcome-btn')
                  .each((_, btn) => {
                        const $btn = $(btn);
                        $btn.toggleClass('is-active', $btn.data('shot-outcome') === normalized);
                  });
            hideError();
      }

      function renderShotPlayerList() {
            if (!$shotPlayerList.length) return;
            $shotPlayerList.html(buildPlayerOptionsHtml('goal-player-option', 'shot-player-option'));
            setShotPlayerSelection(null);
      }

      function setShotModalControlsEnabled(enabled = true) {
            if (!$shotPlayerModal.length) return;
            $shotPlayerModal.find('.shot-player-option').prop('disabled', !enabled);
            $shotPlayerModal.find('[data-shot-outcome]').prop('disabled', !enabled);
            $shotPlayerModal.find('[data-shot-unknown]').prop('disabled', !enabled);
      }

      function openShotPlayerModal(payload, label) {
            if (!$shotPlayerModal.length) return;
            if (!players.length) {
                  showError('No players available', 'Add match players before logging shots');
                  return;
            }
            shotModalState.payload = { ...payload };
            shotModalState.label = label || 'Shot';
            shotModalState.wasPlaying = !!($video.length && !$video[0].paused);
            if ($video.length) {
                  $video[0].pause();
            }
            renderShotPlayerList();
            setShotOutcomeSelection(null);
            setShotModalControlsEnabled(true);
            $shotPlayerModal.removeAttr('hidden').attr('aria-hidden', 'false').addClass('is-active');
      }

      function closeShotPlayerModal() {
            if (!$shotPlayerModal.length) return;
            const wasPlaying = shotModalState.wasPlaying;
            shotModalState.payload = null;
            shotModalState.label = '';
            shotModalState.selectedPlayerId = null;
            shotModalState.wasPlaying = false;
            setShotModalControlsEnabled(true);
            setShotPlayerSelection(null);
            setShotOutcomeSelection(null);
            $shotPlayerModal.attr('aria-hidden', 'true').attr('hidden', 'hidden').removeClass('is-active');
            if (wasPlaying && $video.length) {
                  const videoEl = $video[0];
                  if (videoEl.paused) {
                        videoEl.play().catch(() => { });
                  }
            }
      }

      function handleShotPlayerSelection(event) {
            const $btn = $(event.currentTarget);
            const playerId = $btn.data('player-id');
            if (!playerId || !shotModalState.payload) return;
            setShotPlayerSelection(playerId);
            if (!shotModalState.selectedOutcome) {
                  showError('Select an outcome', 'Choose On Target or Off Target before tagging a shooter');
                  return;
            }
            submitShotEvent({ playerId, outcome: shotModalState.selectedOutcome });
      }

      function submitShotEvent({ playerId = null, outcome }) {
            if (!shotModalState.payload || !outcome) {
                  showError('Select an outcome', 'Choose On Target or Off Target before tagging a shooter');
                  return;
            }
            setShotModalControlsEnabled(false);
            const payload = {
                  ...shotModalState.payload,
                  outcome,
            };
            if (playerId) {
                  payload.match_player_id = playerId;
            }
            sendShotEventRequest(payload, shotModalState.label, outcome);
      }

      function handleShotOutcomeClick(event) {
            event.preventDefault();
            const outcome = $(event.currentTarget).data('shot-outcome');
            if (!shotModalState.payload || !outcome) return;
            setShotOutcomeSelection(outcome);
            if (shotModalState.selectedPlayerId) {
                  submitShotEvent({ playerId: shotModalState.selectedPlayerId, outcome });
            }
      }

      function handleShotUnknownClick(event) {
            event.preventDefault();
            if (!shotModalState.payload) return;
            if (!shotModalState.selectedOutcome) {
                  showError('Select an outcome', 'Choose On Target or Off Target before tagging a shooter');
                  return;
            }
            submitShotEvent({ outcome: shotModalState.selectedOutcome });
      }

      function sendShotEventRequest(payload, label, outcome) {
            const url = endpoint('eventCreate');
            if (!url) {
                  showError('Save failed', 'Missing event endpoint');
                  setShotModalControlsEnabled(true);
                  return;
            }
            $.post(url, payload)
                  .done((res) => {
                        if (!res.ok) {
                              showError('Save failed', res.error || 'Unknown');
                              setShotModalControlsEnabled(true);
                              return;
                        }
                        hideError();
                        syncUndoRedoFromMeta(res.meta);
                        selectedId = null;
                        setEditorCollapsed(true, 'Click a timeline item to edit details', true);
                        const outcomeLabel = shotOutcomeLabels[outcome] || outcome || 'Shot';
                        showToast(
                              `${label || 'Shot'} (${outcomeLabel}) tagged at ${formatMatchSecondWithExtra(
                                    payload.match_second,
                                    payload.minute_extra || 0
                              )}`
                        );
                        setStatus('Tagged');
                        loadEvents();
                        closeShotPlayerModal();
                  })
                  .fail((xhr, status, error) => {
                        showError('Save failed', xhr.responseText || error || status);
                        setShotModalControlsEnabled(true);
                  });
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
            const $btnNode = $btn && $btn.length ? $btn : $tagBoard.find(`.qt-tile[data-type-id="${type.id}"]`).first();
            if (!$btnNode || !$btnNode.length) {
                  console.error('Quick tag button not found for type', key);
                  return;
            }
            const phaseOverride = ($btnNode.data('phase') || '').trim();
            const importanceOverride = parseInt($btnNode.data('importance'), 10);
            const labelOverride = ($btnNode.data('label') || '').trim();
            const quickTagLabel = labelOverride || type.label;
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
            if (isGoalTypeKey(key)) {
                  openGoalPlayerModal(payload, quickTagLabel);
                  return;
            }
            if (isShotTypeKey(key)) {
                  openShotPlayerModal(payload, quickTagLabel);
                  return;
            }
            if (isCardTypeKey(key)) {
                  openCardPlayerModal(payload, quickTagLabel);
                  return;
            }
            const url = endpoint('eventCreate');
            if (!url) {
                  showError('Save failed', 'Missing event endpoint');
                  return;
            }
            $.post(url, payload)
                  .done((res) => {
                        if (!res.ok) {
                              showError('Save failed', res.error || 'Unknown');
                              return;
                        }
                        hideError();
                        selectedId = null;
                        setEditorCollapsed(true, 'Click a timeline item to edit details', true);
                        showToast(`${quickTagLabel} tagged at ${formatMatchSecondWithExtra(normalizedSecond, 0)}`);
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
                        refreshPeriodStateFromEvents();
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

            let filtered = [...events];
            const filtersActive = Boolean(teamF || typeF || playerF);
            if (teamF) filtered = filtered.filter((e) => e.team_side === teamF);
            if (typeF) filtered = filtered.filter((e) => String(e.event_type_id) === String(typeF));
            if (playerF) filtered = filtered.filter((e) => String(e.match_player_id) === String(playerF));

            // Always include period events if present in the DB, even if filtered out
            if (events && events.length) {
                  const periodCandidates = events.filter(
                        (ev) => isPeriodEvent(ev) && ev.match_second !== null && ev.match_second !== undefined
                  );
                  if (periodCandidates.length) {
                        const existingIds = new Set(filtered.map((ev) => String(ev.id)));
                        periodCandidates.forEach((periodEvent) => {
                              const periodId = String(periodEvent.id);
                              if (existingIds.has(periodId)) {
                                    return;
                              }
                              filtered.push(periodEvent);
                              existingIds.add(periodId);
                        });
                  }
            }

            const groups = {};
            filtered.forEach((ev) => {
                  const key = ev.period_label || 'No period';
                  if (!groups[key]) groups[key] = [];
                  groups[key].push(ev);
            });

            filteredCache = filtered;

            // Only show 'No events yet' if there are truly no events in the DB
            const noEventsInDb = !events || !events.length;
            const noEventsAfterFilter = !filtered.length;

            if (timelineMode === 'matrix') {
                  if (noEventsInDb) {
                        $timelineMatrix.html('<div class="text-muted-alt text-sm">No events yet.</div>');
                  } else if (noEventsAfterFilter && filtersActive) {
                        $timelineMatrix.html('<div class="text-muted-alt text-sm">No events match your filters.</div>');
                  } else {
                        renderMatrix(groups, filtered, opts);
                  }
            } else {
                  if (noEventsInDb) {
                        $timelineList.html('<div class="text-muted-alt text-sm">No events yet.</div>');
                  } else if (noEventsAfterFilter && filtersActive) {
                        $timelineList.html('<div class="text-muted-alt text-sm">No events match your filters.</div>');
                  } else {
                        renderListTimeline(groups, filtered);
                  }
            }
            // Mark timeline as ready and try to hide skeleton
            deskTimelineReady = true;
            tryHideDeskSkeleton();
      }

      function getEventMinuteBucket(ev) {
            if (!ev) {
                  return 0;
            }
            const rawMinute = ev.minute;
            if (rawMinute !== null && rawMinute !== undefined && String(rawMinute).trim() !== '') {
                  const normalized = String(rawMinute).split('+')[0];
                  const parsed = parseInt(normalized, 10);
                  if (!Number.isNaN(parsed)) {
                        return parsed;
                  }
            }
            const seconds = typeof ev.match_second === 'number' ? ev.match_second : parseFloat(ev.match_second);
            if (!Number.isNaN(seconds)) {
                  return Math.floor(Math.max(0, seconds) / 60);
            }
            return 0;
      }

      function formatEventMinuteText(ev) {
            if (!ev) {
                  return '0';
            }
            const extra = ev.minute_extra ? `+${ev.minute_extra}` : '';
            if (ev.minute !== null && ev.minute !== undefined && String(ev.minute).trim() !== '') {
                  const minuteText = String(ev.minute);
                  if (minuteText.includes('+')) {
                        return minuteText;
                  }
                  return `${minuteText}${extra}`;
            }
            const bucket = getEventMinuteBucket(ev);
            return `${bucket}${extra}`;
      }

      function buildEventImportanceClass(rawImportance) {
            const parsed = parseInt(rawImportance, 10);
            const importance = Number.isNaN(parsed) ? 0 : parsed;
            if (importance <= 2) {
                  return ' timeline-item--low-importance';
            }
            if (importance >= 4) {
                  return ' timeline-item--high-importance';
            }
            return '';
      }

      function renderListTimeline(groups, filtered) {
            let html = '';
            Object.keys(groups).forEach((label) => {
                  const periodEvents = groups[label] || [];
                  if (!periodEvents.length) {
                        return;
                  }
                  html += `<div class="timeline-group">
                                        <div class="timeline-group-title">${h(label)}</div>`;
                  const minuteBuckets = {};
                  const minuteOrder = [];
                  periodEvents.forEach((ev) => {
                        const minuteKey = getEventMinuteBucket(ev);
                        if (!Object.prototype.hasOwnProperty.call(minuteBuckets, minuteKey)) {
                              minuteBuckets[minuteKey] = [];
                              minuteOrder.push(minuteKey);
                        }
                        minuteBuckets[minuteKey].push(ev);
                  });
                  // Group events by minute so actions from the same minute stack together while keeping the source order intact.
                  minuteOrder.forEach((minuteKey) => {
                        const minuteEvents = minuteBuckets[minuteKey] || [];
                        if (!minuteEvents.length) {
                              return;
                        }
                        const minuteAttr = h(minuteKey);
                        const minuteLabel = `${Math.max(0, minuteKey)}'`;
                        html += `<div class="timeline-minute-group" data-minute="${minuteAttr}">
                                                  <div class="timeline-minute-header">
                                                            <span class="timeline-minute-label">${h(minuteLabel)}</span>
                                                            <span class="timeline-minute-count text-xs text-muted-alt">${minuteEvents.length} event${minuteEvents.length === 1 ? '' : 's'}</span>
                                                  </div>
                                                  <div class="timeline-minute-events">`;
                        minuteEvents.forEach((ev) => {
                              const labelText = displayEventLabel(ev, ev.event_type_label || 'Event');
                              const accent = eventTypeAccents[String(ev.event_type_id)] || EVENT_NEUTRAL;
                              const colorStyle = buildColorStyle(accent);
                              const importanceClass = buildEventImportanceClass(ev.importance);
                              const badgeClass = ev.team_side === 'home' ? 'badge-home' : ev.team_side === 'away' ? 'badge-away' : 'badge-unknown';
                              const player = ev.match_player_name ? `<span>${h(ev.match_player_name)}</span>` : '<span class="text-muted-alt">No player</span>';
                              const minuteDisplay = h(formatEventMinuteText(ev));
                              const matchTimeLabel = h(formatMatchSecondWithExtra(ev.match_second, ev.minute_extra));
                              html += `<div class="timeline-item${importanceClass}" data-id="${ev.id}" data-second="${ev.match_second}" style="${colorStyle}">
                                                            <div class="timeline-top">
                                                                      <div><span class="badge-pill ${badgeClass}">${h(ev.team_side || 'unk')}</span> <span class="event-label">${h(labelText)}</span></div>
                                                                      <div class="timeline-actions">
                                                                                <span class="text-muted-alt text-xs">${minuteDisplay}' (${matchTimeLabel})</span>
                                                                               <button type="button" class="ghost-btn ghost-btn-sm desk-editable timeline-edit" data-id="${ev.id}">Edit</button>
                                                                               <button type="button" class="ghost-btn ghost-btn-sm desk-editable timeline-delete" data-id="${ev.id}">Delete</button>
                                                                      </div>
                                                            </div>
                                                            <div class="timeline-meta">
                                                                      ${player}
                                                                      <span>${ev.tags && ev.tags.length ? `${ev.tags.length} tags` : ''}</span>
                                                            </div>
                                                  </div>`;
                        });
                        html += '</div></div>';
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

      function buildClipRanges(events) {
            const clipMap = new Map();
            (events || []).forEach((ev) => {
                  const clipId = getEventClipId(ev);
                  if (!Number.isFinite(clipId) || clipId <= 0) {
                        return;
                  }
                  const start = Number(ev.clip_start_second ?? (ev.clip && ev.clip.start_second));
                  const end = Number(ev.clip_end_second ?? (ev.clip && ev.clip.end_second));
                  if (!Number.isFinite(start) || !Number.isFinite(end) || end <= start) {
                        return;
                  }
                  const key = String(clipId);
                  if (!clipMap.has(key)) {
                        clipMap.set(key, {
                              clipId,
                              start,
                              end,
                              label: displayEventLabel(ev, 'Clip'),
                        });
                        return;
                  }
                  const existing = clipMap.get(key);
                  if (existing) {
                        existing.start = Math.min(existing.start, start);
                        existing.end = Math.max(existing.end, end);
                  }
            });
            return Array.from(clipMap.values());
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
                  timelineZoom.scale = Math.min(timelineZoom.max, 1);
                  $timelineMatrix.html('<div class="text-muted-alt text-sm">No events yet.</div>');
                  return;
            }

            const rowMap = new Map();
            (filtered || []).forEach((ev) => {
                  if (isPeriodEvent(ev)) {
                        return;
                  }
                  const label = displayEventLabel(ev, ev.event_type_label || 'Event');
                  const key = `${ev.event_type_id || 'unknown'}::${label}`;
                  if (!rowMap.has(key)) {
                        rowMap.set(key, { id: ev.event_type_id, label, events: [] });
                  }
                  rowMap.get(key).events.push(ev);
            });
            const typeRows = Array.from(rowMap.values());
            const periodMarkers = (filtered || [])
                  .filter((ev) => (ev.event_type_key === 'period_start' || ev.event_type_key === 'period_end') && ev.match_second !== null && ev.match_second !== undefined)
                  .map((ev) => ({
                        id: ev.id,
                        label: displayEventLabel(ev, ev.event_type_label || 'Period'),
                        second: Number(ev.match_second) || 0,
                        edge: ev.event_type_key === 'period_start' ? 'start' : 'end',
                  }))
                  .sort((a, b) => a.second - b.second);
            const clipRanges = buildClipRanges(filtered);
            const maxClipSecond = clipRanges.reduce((max, clip) => Math.max(max, clip.end || 0), 0);
            const maxAnnotationSecond = timelineAnnotations.reduce((max, annotation) => {
                  const seconds = Number(annotation.timestamp_second);
                  return Number.isFinite(seconds) ? Math.max(max, seconds) : max;
            }, 0);
            const maxEventSecond = filtered.reduce((max, ev) => Math.max(max, ev.match_second || 0), 0);
            const maxMarkerSecond = Math.max(maxEventSecond, maxClipSecond, maxAnnotationSecond);
            timelineMetrics.duration = Math.max(baseDuration, maxMarkerSecond);
            const containerWidth = $timelineMatrix.closest('.timeline-scroll').width() || $timelineMatrix.width() || 0;
            const availableWidth = Math.max(0, containerWidth - axisPad);
            const baseWidth = timelineMetrics.duration * timelineZoom.pixelsPerSecond;
            const fitScale = baseWidth > 0 ? availableWidth / baseWidth : 1;
            const safeFit = fitScale > 0 ? fitScale : 1;
            timelineZoom.scale = Math.min(timelineZoom.max, safeFit);
            const timelineWidth = timelineMetrics.duration * timelineZoom.pixelsPerSecond * timelineZoom.scale;
            const bucketWidths = buckets.map((bucket) => {
                  const bucketEnd = Math.min(bucket.end, timelineMetrics.duration);
                  const span = Math.max(0, bucketEnd - bucket.start);
                  return span * timelineZoom.pixelsPerSecond * timelineZoom.scale;
            });
            const bucketColumns = bucketWidths.map((w) => `${Math.max(24, w)}px`).join(' ');
            const gridColumnsStyle = `grid-template-columns: ${MATRIX_TYPE_WIDTH}px ${timelineWidth}px;`;

            const markerOffset = MATRIX_TYPE_WIDTH + MATRIX_GAP;
            let html = `<div class="matrix-viewport" data-scale="${timelineZoom.scale}">`;
            if (periodMarkers.length) {
                  html += `<div class="matrix-period-markers" style="left:${markerOffset}px; width:${timelineWidth}px">`;
                  periodMarkers.forEach((marker) => {
                        const position = Math.min(Math.max(0, marker.second * timelineZoom.pixelsPerSecond * timelineZoom.scale), timelineWidth);
                        html += `<button type="button" class="matrix-period-marker" data-period-id="${marker.id}" data-period-edge="${marker.edge}" data-second="${marker.second}" data-tooltip="${h(
                              marker.label
                        )}" style="left:${position}px">`;
                        html += `<span class="matrix-period-marker-line"></span>`;
                        html += '</button>';
                  });
                  html += '</div>';
            }
            html += `<div class="matrix-bucket-row" style="${gridColumnsStyle}">`;
            html += `<div class="matrix-type-spacer" style="width:${MATRIX_TYPE_WIDTH}px"></div>`;
            html += `<div class="matrix-bucket-labels" style="grid-template-columns:${bucketColumns}; width:${timelineWidth}px">`;
            buckets.forEach((b) => {
                  html += `<div class="text-center">${h(b.label)}</div>`;
            });
            html += '</div>';
            html += '</div>';

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
                        if (isPeriodEvent(ev)) {
                              return;
                        }
                        const labelText = displayEventLabel(ev, row.label);
                        const matrixTimeLabel = h(formatMatchSecondWithExtra(ev.match_second, ev.minute_extra));
                        const seconds = Number.isFinite(Number(ev.match_second)) ? Number(ev.match_second) : 0;
                        const position = Math.min(Math.max(0, seconds * timelineZoom.pixelsPerSecond * timelineZoom.scale), timelineWidth);
                        const rawImportance = parseInt(ev.importance, 10);
                        const importance = Number.isNaN(rawImportance) ? 1 : rawImportance;
                        const baseAccent = eventTypeAccents[String(row.id)] || EVENT_NEUTRAL;
                        const teamColor =
                              ev.team_side === 'home' ? '#3b82f6' : ev.team_side === 'away' ? '#f97316' : baseAccent;
                        const emphasisHighlight =
                              importance >= 4 ? 'border: 1px solid var(--event-color-strong, rgba(148, 163, 184, 0.55));' : '';
                        const dotStyle = `${buildColorStyle(teamColor)}left:${position}px; ${emphasisHighlight}`;
                        const clipNumeric = getEventOrClipId(ev);
                        const eventClipId = Number.isFinite(clipNumeric) && clipNumeric > 0 ? String(clipNumeric) : null;
                        const clipAttributes = ` draggable="true"${eventClipId ? ` data-clip-id="${eventClipId}"` : ''}`;
                        html += `<span class="matrix-dot"${clipAttributes} data-second="${ev.match_second}" data-event-id="${ev.id}" title="${matrixTimeLabel} - ${h(
                              labelText
                        )} (${h(ev.team_side || 'team')})" style="${dotStyle}"></span>`;
                  });
                  html += '</div></div></div>';
            });


            // Removed Clips and Drawings from timeline (matrix mode)

            html += '</div>';

            $timelineMatrix.html(html);
            const debugCounts = {
                  dots: $timelineMatrix.find('.matrix-dot').length,
                  dotsWithClip: $timelineMatrix.find('.matrix-dot[data-clip-id]').length,
                  dotsWithoutClip: $timelineMatrix.find('.matrix-dot:not([data-clip-id])').length,
                  clips: $timelineMatrix.find('.matrix-clip').length,
                  drawings: $timelineMatrix.find('.matrix-drawing').length,
            };
            // console.log('[Desk DnD] renderMatrix complete', ...);
            if (debugCounts.dots > 0 && debugCounts.dotsWithClip === 0 && !missingClipToastShown) {
                  missingClipToastShown = true;
                  console.log('[Desk DnD] using event IDs as clip IDs (no explicit clips yet)');
            }
            const $viewport = $timelineMatrix.find('.matrix-viewport');
            timelineMetrics.viewportWidth = $viewport.length ? $viewport[0].clientWidth : availableWidth + axisPad;
            timelineMetrics.totalWidth = timelineWidth + axisPad;
            const targetScroll = typeof options.scrollLeft === 'number' ? options.scrollLeft : options.previousScroll || 0;
            if ($viewport.length) {
                  $viewport[0].scrollLeft = clampScrollValue(targetScroll, $viewport[0]);
            }
      }

      function handleAnnotationTimelinePayload(payload) {
            if (!annotationsEnabled) {
                  return;
            }
            if (!payload || payload.type !== 'match_video' || !annotationTargetId) {
                  return;
            }
            const targetId = Number(payload.id);
            if (!Number.isFinite(targetId) || targetId !== annotationTargetId) {
                  return;
            }
            timelineAnnotations = Array.isArray(payload.annotations) ? payload.annotations.slice() : [];
            renderTimeline();
      }

      function ensureAnnotationBridge() {
            if (!annotationsEnabled) {
                  return;
            }
            if (!annotationTargetId) {
                  return;
            }
            const attachBridge = () => {
                  if (annotationBridge) {
                        return;
                  }
                  const bridge = window.DeskAnnotationTimelineBridge;
                  if (!bridge || typeof bridge.subscribe !== 'function') {
                        return;
                  }
                  annotationBridge = bridge;
                  bridge.subscribe(handleAnnotationTimelinePayload);
            };
            attachBridge();
            if (!annotationBridge) {
                  window.addEventListener(
                        'DeskAnnotationTimelineReady',
                        () => {
                              attachBridge();
                        },
                        { once: true }
                  );
            }
      }

      function getMatrixViewport() {
            return $timelineMatrix.find('.matrix-viewport');
      }

      function handleMatrixWheel(e) {
            const evt = e.originalEvent || e;
            const viewport = e.currentTarget;
            if (!viewport) return;
            const delta = Math.abs(evt.deltaX || 0) > Math.abs(evt.deltaY || 0) ? evt.deltaX : evt.deltaY;
            if (!delta) return;
            e.preventDefault();
            viewport.scrollLeft = clampScrollValue(viewport.scrollLeft + delta, viewport);
      }

      function createMatrixViewportEvent(event, viewport) {
            const proxyEvent = Object.create(event);
            Object.defineProperty(proxyEvent, 'currentTarget', {
                  value: viewport,
                  enumerable: true,
                  configurable: true,
            });
            Object.defineProperty(proxyEvent, 'originalEvent', {
                  value: event,
                  enumerable: true,
                  configurable: true,
            });
            proxyEvent.preventDefault = event.preventDefault ? event.preventDefault.bind(event) : () => { };
            proxyEvent.stopPropagation = event.stopPropagation ? event.stopPropagation.bind(event) : () => { };
            return proxyEvent;
      }

      function matrixViewportWheelListener(event) {
            const target = event.target;
            const viewport = target && typeof target.closest === 'function' ? target.closest('.matrix-viewport') : null;
            if (!viewport) return;
            handleMatrixWheel(createMatrixViewportEvent(event, viewport));
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

      function updateDrawingDragElement() {
            if (!drawingDragState || !drawingDragState.element) {
                  return;
            }
            const timelineWidth = Math.max(
                  0,
                  timelineMetrics.duration * timelineZoom.pixelsPerSecond * timelineZoom.scale
            );
            const position = timelineWidth > 0
                  ? Math.min(
                        timelineWidth,
                        Math.max(0, drawingDragState.currentTime * timelineZoom.pixelsPerSecond * timelineZoom.scale)
                  )
                  : 0;
            drawingDragState.element.style.left = `${position}px`;
            drawingDragState.element.dataset.second = String(drawingDragState.currentTime);
      }

      function cleanupDrawingDragListeners() {
            document.removeEventListener('pointermove', handleMatrixDrawingPointerMove);
            document.removeEventListener('pointerup', finalizeMatrixDrawingDrag);
            document.removeEventListener('pointercancel', cancelMatrixDrawingDrag);
      }

      function handleMatrixDrawingPointerDown(event) {
            if (event.button !== 0) return;
            const target = event.currentTarget;
            const drawingId = Number(target.dataset.drawingId);
            if (!Number.isFinite(drawingId)) return;
            const baseTime = Number(target.dataset.second) || 0;
            drawingDragState = {
                  id: drawingId,
                  pointerId: event.pointerId,
                  startX: event.clientX,
                  baseTime,
                  currentTime: baseTime,
                  element: target,
            };
            drawingDragState.element.classList.add('is-dragging');
            document.addEventListener('pointermove', handleMatrixDrawingPointerMove);
            document.addEventListener('pointerup', finalizeMatrixDrawingDrag);
            document.addEventListener('pointercancel', cancelMatrixDrawingDrag);
      }

      function handleMatrixDrawingPointerMove(event) {
            if (!drawingDragState || event.pointerId !== drawingDragState.pointerId) {
                  return;
            }
            const scaleFactor = timelineZoom.pixelsPerSecond * timelineZoom.scale;
            if (!scaleFactor) {
                  return;
            }
            const deltaX = event.clientX - drawingDragState.startX;
            const deltaSeconds = deltaX / scaleFactor;
            const duration = Math.max(0, timelineMetrics.duration);
            drawingDragState.currentTime = Math.min(
                  duration,
                  Math.max(0, drawingDragState.baseTime + deltaSeconds)
            );
            updateDrawingDragElement();
            event.preventDefault();
      }

      function finalizeMatrixDrawingDrag(event) {
            if (!drawingDragState || event.pointerId !== drawingDragState.pointerId) {
                  return;
            }
            const drawingId = drawingDragState.id;
            const timestamp = Math.max(0, drawingDragState.currentTime);
            drawingDragState.element.classList.remove('is-dragging');
            cleanupDrawingDragListeners();
            drawingDragState = null;
            window.dispatchEvent(
                  new CustomEvent('DeskDrawingTimestampUpdate', {
                        detail: { drawingId, timestamp },
                  })
            );
            scrollMatrixToSecond(timestamp);
      }

      function cancelMatrixDrawingDrag(event) {
            if (!drawingDragState || event.pointerId !== drawingDragState.pointerId) {
                  return;
            }
            drawingDragState.element && drawingDragState.element.classList.remove('is-dragging');
            cleanupDrawingDragListeners();
            drawingDragState = null;
            renderTimeline();
      }

      function handleMatrixResize() {
            if (timelineMode !== 'matrix') return;
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => renderTimeline(), 120);
      }

      function setEditorCollapsed(collapsed, hintText, hidePanel = false) {
            const shouldHide = !!collapsed || !!hidePanel;
            if (shouldHide) {
                  $editorPanel.addClass('is-hidden');
            } else {
                  $editorPanel.removeClass('is-hidden');
            }
            $editorPanel.attr('aria-hidden', shouldHide ? 'true' : 'false');
            editorOpen = !shouldHide;
            document.body && document.body.classList.toggle('editor-modal-open', editorOpen);
            if (hintText && $editorHint.length) {
                  $editorHint.text(hintText);
            }
            if (shouldHide) {
                  editorDirty = false;
                  activeEditorTab = 'details';
                  setActiveEditorTab('details');
            }
      }

      function withEditorPopulation(fn) {
            if (typeof fn !== 'function') return;
            suppressDirtyTracking = true;
            try {
                  fn();
            } finally {
                  suppressDirtyTracking = false;
            }
      }

      function setActiveEditorTab(panelName) {
            let target = panelName || 'details';

            if (target === 'outcome' && $editorTabOutcome.length && $editorTabOutcome.hasClass('is-hidden')) {
                  target = 'details';
            }
            activeEditorTab = target;

            $editorTabs.each((idx, tab) => {
                  const $tab = $(tab);
                  const panel = $tab.data('panel');
                  const isActive = panel === activeEditorTab;
                  $tab.toggleClass('is-active', isActive);
                  $tab.attr('aria-selected', isActive ? 'true' : 'false');
                  $tab.attr('tabindex', isActive ? '0' : '-1');
            });

            $editorTabPanels.each((idx, panel) => {
                  const $panel = $(panel);
                  const panelData = $panel.data('panel');
                  const isActive = panelData === activeEditorTab;
                  $panel.toggleClass('is-active', isActive);
            });
      }

      function updateOutcomeTabVisibility(options = []) {
            const hasOptions = Array.isArray(options) && options.length > 0;
            if ($editorTabOutcome.length) {
                  $editorTabOutcome.toggleClass('is-hidden', !hasOptions);
                  $editorTabOutcome.attr('aria-hidden', hasOptions ? 'false' : 'true');
                  if (!hasOptions && activeEditorTab === 'outcome') {
                        setActiveEditorTab('details');
                  }
            }
      }

      function markEditorDirty() {
            if (suppressDirtyTracking) return;
            editorDirty = true;
      }

      function attemptCloseEditor() {
            if (editorDirty) return;
            setEditorCollapsed(true, 'Click a timeline item to edit details', true);
      }

      function fillForm(ev) {
            if (!ev) {
                  withEditorPopulation(() => {
                        selectedId = null;
                        $eventId.val('');
                        updateTimeFromSeconds(0);
                        const teamSide = currentTeam || 'home';
                        $teamSide.val(teamSide);
                        $editorPanel.find('.team-selector-btn').removeClass('selected');
                        $editorPanel.find(`.team-selector-btn[data-team="${teamSide}"]`).addClass('selected');
                        $eventTypeId.val('');
                        $matchPlayerId.val('');
                        $importance.val('3');
                        $phase.val('');
                        $outcome.val('');
                        $editorPanel.find('.outcome-selector-btn').removeClass('selected');
                        $zone.val('');
                        $notes.val('');
                        $tagIds.val([]);
                        clipState = { id: null, start: null, end: null };
                        updateClipUi();
                        refreshOutcomeFieldForEvent(null);
                        updateEventEditorPlayerList(teamSide);
                  });
                  editorDirty = false;
                  setEditorCollapsed(true, 'Click a timeline item to edit details', true);
                  return;
            }

            withEditorPopulation(() => {
                  selectedId = ev.id;
                  $eventId.val(ev.id);
                  const seconds = Number.isFinite(Number(ev.match_second)) ? Number(ev.match_second) : 0;
                  updateTimeFromSeconds(seconds);
                  const teamSide = ev.team_side || 'unknown';
                  $teamSide.val(teamSide);
                  $editorPanel.find('.team-selector-btn').removeClass('selected');
                  $editorPanel.find(`.team-selector-btn[data-team="${teamSide}"]`).addClass('selected');
                  $eventTypeId.val(ev.event_type_id);
                  $matchPlayerId.val(ev.match_player_id);
                  $importance.val(ev.importance || 3);
                  $phase.val(ev.phase || '');
                  const outcomeValue = ev.outcome || '';
                  $outcome.val(outcomeValue);
                  $editorPanel.find('.outcome-selector-btn').removeClass('selected');
                  if (outcomeValue) {
                        $editorPanel.find(`.outcome-selector-btn[data-outcome="${outcomeValue}"]`).addClass('selected');
                  }
                  $zone.val(ev.zone || '');
                  $notes.val(ev.notes || '');
                  $tagIds.val(ev.tags ? ev.tags.map((t) => t.id) : []);
                  const evClipId = getEventClipId(ev);
                  if (evClipId) {
                        clipState = {
                              id: evClipId,
                              start: ev.clip_start_second ?? (ev.clip && ev.clip.start_second) ?? null,
                              end: ev.clip_end_second ?? (ev.clip && ev.clip.end_second) ?? null,
                        };
                  } else {
                        clipState = { id: null, start: null, end: null };
                  }
                  updateClipUi();
                  refreshOutcomeFieldForEvent(ev);
                  updateEventEditorPlayerList(teamSide);
            });
            editorDirty = false;
            const labelText = displayEventLabel(ev, ev.event_type_label || 'Event');
            const editorTimeLabel = h(formatMatchSecondWithExtra(ev.match_second, ev.minute_extra));
            setEditorCollapsed(false, `${h(labelText)} - ${editorTimeLabel}`, false);
      }

      function findEventById(id) {
            if (!id) return null;
            return events.find((e) => String(e.id) === String(id));
      }

      function goToVideoTime(seconds) {
            if (!$video.length) {
                  return;
            }
            if (seconds === null || seconds === undefined) {
                  return;
            }
            const normalized = Number(seconds);
            if (Number.isNaN(normalized)) {
                  return;
            }
            $video[0].currentTime = Math.max(0, normalized);
      }

      function scrollMatrixToSecond(seconds) {
            const viewport = getMatrixViewport();
            const viewportEl = viewport && viewport.length ? viewport[0] : null;
            if (!viewportEl) {
                  return;
            }
            const normalized = Number(seconds);
            if (!Number.isFinite(normalized)) {
                  return;
            }
            const offset = axisOffset();
            const center = viewportEl.clientWidth / 2;
            const target =
                  Math.max(0, normalized * timelineZoom.pixelsPerSecond * timelineZoom.scale + offset - center);
            viewportEl.scrollLeft = clampScrollValue(target, viewportEl);
      }

      function selectEvent(id) {
            const ev = findEventById(id);
            if (!ev) return;
            selectedId = ev.id;
            $timeline.find('.timeline-item').removeClass('active');
            $timeline.find(`.timeline-item[data-id="${id}"]`).addClass('active');
            fillForm(ev);
            goToVideoTime(ev.match_second);
      }

      function collectData() {
            let matchSecond = parseInt($matchSecond.val(), 10);
            if (Number.isNaN(matchSecond)) {
                  matchSecond = 0;
                  if ($video.length) {
                        matchSecond = Math.floor($video[0].currentTime || 0);
                  }
                  updateTimeFromSeconds(matchSecond);
            }
            matchSecond = Math.max(0, matchSecond);
            const minuteValue = Math.floor(matchSecond / 60);
            $minute.val(minuteValue);
            const minuteExtraValue = Math.max(0, parseInt($minuteExtra.val(), 10) || 0);
            $minuteExtra.val(minuteExtraValue);
            const teamSide = ($teamSide.val() || '').trim() || currentTeam || 'home';
            $teamSide.val(teamSide);
            return {
                  match_id: cfg.matchId,
                  event_id: $eventId.val(),
                  match_second: matchSecond,
                  minute: minuteValue,
                  minute_extra: minuteExtraValue,
                  team_side: teamSide,
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
                  Toast.error('Save failed: Missing event endpoint');
                  return;
            }
            $.post(url, data)
                  .done((res) => {
                        if (!res.ok) {
                              Toast.error(res.error || 'Save failed');
                              return;
                        }
                        Toast.success('Event saved successfully');
                        hideError();
                        syncUndoRedoFromMeta(res.meta);
                        setStatus('Saved');
                        setEditorCollapsed(true, 'Click a timeline item to edit details', true);
                        selectedId = null;
                        loadEvents();
                  })
                  .fail((xhr, status, error) => {
                        const errorMsg = xhr.responseText || error || status || 'Unknown error';
                        Toast.error(errorMsg);
                        showError('Save failed', errorMsg);
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
                  Toast.error('Delete failed: Missing endpoint');
                  return;
            }
            $.post(url, { match_id: cfg.matchId, event_id: eventId })
                  .done((res) => {
                        if (!res.ok) {
                              Toast.error(res.error || 'Delete failed');
                              return;
                        }
                        Toast.success('Event deleted successfully');
                        hideError();
                        syncUndoRedoFromMeta(res.meta);
                        if (String(selectedId) === String(eventId)) {
                              selectedId = null;
                              fillForm(null);
                        }
                        loadEvents();
                        attemptCloseEditor();
                        setStatus('Deleted');
                  })
                  .fail((xhr, status, error) => {
                        const errorMsg = xhr.responseText || error || status || 'Unknown error';
                        Toast.error(errorMsg);
                  });
      }

      function deleteAllVisible() {
            if (!lockOwned || !cfg.canEditRole) return;
            const ids = (filteredCache || []).map((e) => e.id).filter(Boolean);
            if (!ids.length) return;
            if (!window.confirm(`Delete ${ids.length} events? This cannot be undone.`)) return;
            const url = endpoint('eventDelete');
            if (!url) {
                  Toast.error('Delete failed: Missing endpoint');
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

      function normalizePeriodLabel(label) {
            if (!label) return '';
            return String(label).trim().toLowerCase();
      }

      function createBasePeriodState() {
            const state = {};
            PERIOD_SEQUENCE.forEach((key) => {
                  const def = periodDefinitions[key];
                  state[key] = {
                        key,
                        label: def ? def.label : '',
                        started: false,
                        ended: false,
                        startTimestamp: null,
                        startMatchSecond: null,
                        endMatchSecond: null,
                  };
            });
            return state;
      }

      function setPeriodState(nextState) {
            periodState = nextState || createBasePeriodState();
            updatePeriodControlsUI();
      }

      function refreshPeriodStateFromEvents() {
            const nextState = createBasePeriodState();
            const periodCandidates = (events || []).filter((ev) => PERIOD_EVENT_KEYS.has(ev.event_type_key));
            const sorted = periodCandidates
                  .map((ev) => ({ ev, timestamp: parseEventTimestamp(ev) }))
                  .sort((a, b) => {
                        const aTime = a.timestamp || 0;
                        const bTime = b.timestamp || 0;
                        if (aTime !== bTime) return aTime - bTime;
                        const aId = Number(a.ev.id) || 0;
                        const bId = Number(b.ev.id) || 0;
                        return aId - bId;
                  });
            sorted.forEach(({ ev }) => {
                  const labelKey = normalizePeriodLabel(ev.period_label || ev.notes);
                  const key = labelKey ? periodLabelToKey.get(labelKey) : null;
                  if (!key || !nextState[key]) return;
                  const entry = nextState[key];
                  const isStart = ev.event_type_key === 'period_start';
                  const isEnd = ev.event_type_key === 'period_end';
                  const timestamp = parseEventTimestamp(ev);
                  if (isStart) {
                        entry.started = true;
                        entry.startTimestamp = timestamp || entry.startTimestamp || Date.now();
                        entry.startMatchSecond = parseMatchSecond(ev.match_second);
                  }
                  if (isEnd) {
                        entry.ended = true;
                        entry.endMatchSecond = parseMatchSecond(ev.match_second);
                  }
            });
            setPeriodState(nextState);
      }

      function updatePeriodControlsUI() {
            updatePeriodButtons();
            ensureActivePeriodTimer();
            updatePeriodStatusLabel();
      }

      function updatePeriodButtons() {
            const activeKey = getActivePeriodKey(periodState);
            const matchStateKey = getMatchStateKey(periodState);
            const terminalState = matchStateKey === 'match_complete';
            PERIOD_SEQUENCE.forEach((key) => {
                  const def = periodDefinitions[key];
                  const entry = periodState[key];
                  if (!def || !entry) return;
                  const startBtn = def.startButton;
                  const endBtn = def.endButton;
                  const startDisabled = terminalState || computeStartDisabled(key);
                  if (startBtn) {
                        startBtn.disabled = startDisabled;
                        startBtn.setAttribute('aria-disabled', startDisabled ? 'true' : 'false');
                        startBtn.setAttribute('aria-pressed', activeKey === key ? 'true' : 'false');
                        updateTerminalTooltip(startBtn, terminalState);
                  }
                  if (endBtn) {
                        const endDisabled = !entry.started || entry.ended;
                        endBtn.disabled = endDisabled;
                        endBtn.setAttribute('aria-disabled', endDisabled ? 'true' : 'false');
                        endBtn.setAttribute('aria-pressed', 'false');
                  }
            });
      }

      function computeStartDisabled(key) {
            const entry = periodState[key];
            if (!entry) return true;
            if (entry.started) return true;
            if (hasLaterPeriodStarted(key)) return true;
            if (!isPreviousPeriodComplete(key)) return true;
            return false;
      }

      function hasLaterPeriodStarted(key) {
            const idx = PERIOD_SEQUENCE.indexOf(key);
            if (idx < 0) return false;
            return PERIOD_SEQUENCE.slice(idx + 1).some((later) => {
                  const next = periodState[later];
                  return next && next.started;
            });
      }

      function isPreviousPeriodComplete(key) {
            switch (key) {
                  case 'first_half':
                        return true;
                  case 'second_half':
                        return periodState.first_half && periodState.first_half.ended;
                  case 'extra_time_1':
                        return periodState.second_half && periodState.second_half.ended;
                  case 'extra_time_2':
                        return periodState.extra_time_1 && periodState.extra_time_1.ended;
                  case 'penalties':
                        if (!(periodState.second_half && periodState.second_half.ended)) return false;
                        if (periodState.extra_time_1 && periodState.extra_time_1.started && !periodState.extra_time_1.ended) return false;
                        if (periodState.extra_time_2 && periodState.extra_time_2.started && !periodState.extra_time_2.ended) return false;
                        return true;
                  default:
                        return true;
            }
      }

      function getActivePeriod(state) {
            if (!state) return null;
            return PERIOD_SEQUENCE.map((key) => state[key]).find((period) => period && period.started && !period.ended) || null;
      }

      function getActivePeriodKey(state) {
            const active = getActivePeriod(state);
            return active ? active.key : null;
      }

      function ensureActivePeriodTimer() {
            const active = getActivePeriod(periodState);
            if (!active || !active.startTimestamp) {
                  stopPeriodTimer();
                  periodTimerKey = null;
                  periodTimerStart = null;
                  return;
            }
            if (periodTimer && periodTimerKey === active.key && periodTimerStart === active.startTimestamp) {
                  return;
            }
            stopPeriodTimer();
            periodTimerKey = active.key;
            periodTimerStart = active.startTimestamp;
            periodTimer = setInterval(() => updatePeriodStatusLabel(), 1000);
      }

      function stopPeriodTimer() {
            if (periodTimer) {
                  clearInterval(periodTimer);
                  periodTimer = null;
            }
      }

      function updatePeriodStatusLabel() {
            if (!currentPeriodStatusEl) return;
            const label = deriveStatusLabel(periodState);
            const active = getActivePeriod(periodState);
            let text = `Current period: ${label}`;
            if (active && active.startTimestamp) {
                  const elapsedSeconds = Math.max(0, Math.floor((Date.now() - active.startTimestamp) / 1000));
                  text += ` (${formatDuration(elapsedSeconds)})`;
            }
            currentPeriodStatusEl.textContent = text;
      }

      function deriveStatusLabel(state) {
            const key = getMatchStateKey(state);
            switch (key) {
                  case 'first_half':
                        return state.first_half?.label || 'First Half';
                  case 'half_time':
                        return 'Half Time';
                  case 'second_half':
                        return state.second_half?.label || 'Second Half';
                  case 'full_time':
                        return 'Full Time';
                  case 'extra_time_1':
                        return state.extra_time_1?.label || 'Extra Time 1';
                  case 'extra_time_break':
                        return 'Extra Time Break';
                  case 'extra_time_2':
                        return state.extra_time_2?.label || 'Extra Time 2';
                  case 'penalties':
                        return state.penalties?.label || 'Penalties';
                  case 'match_complete':
                        return 'Match Complete';
                  case 'not_started':
                        return 'Not started';
                  default:
                        return 'Match';
            }
      }

      function getMatchStateKey(state) {
            if (!state) return 'not_started';
            const first = state.first_half;
            const second = state.second_half;
            const et1 = state.extra_time_1;
            const et2 = state.extra_time_2;
            const penalties = state.penalties;
            if (first && first.started && !first.ended) return 'first_half';
            if (first && first.ended && !(second && second.started)) return 'half_time';
            if (second && second.started && !second.ended) return 'second_half';
            if (second && second.ended && !et1?.started && !et2?.started && !penalties?.started) return 'full_time';
            if (et1 && et1.started && !et1.ended) return 'extra_time_1';
            if (et1 && et1.ended && !(et2 && et2.started)) return 'extra_time_break';
            if (et2 && et2.started && !et2.ended) return 'extra_time_2';
            if (penalties && penalties.started && !penalties.ended) return 'penalties';
            if (penalties && penalties.ended) return 'match_complete';
            if (!first || !first.started) return 'not_started';
            return 'match';
      }

      function updateTerminalTooltip(button, terminal) {
            if (!button) return;
            if (terminal) {
                  button.setAttribute('title', 'Match has finished');
            } else {
                  button.removeAttribute('title');
            }
      }

      function formatDuration(seconds) {
            const mins = Math.floor(seconds / 60);
            const secs = seconds % 60;
            const minutes = String(mins).padStart(2, '0');
            const secondsStr = String(secs).padStart(2, '0');
            return `${minutes}:${secondsStr}`;
      }

      function parseEventTimestamp(ev) {
            if (!ev) return null;
            if (ev.created_at) {
                  const parsed = Date.parse(ev.created_at);
                  if (!Number.isNaN(parsed)) {
                        return parsed;
                  }
            }
            if (ev.updated_at) {
                  const parsed = Date.parse(ev.updated_at);
                  if (!Number.isNaN(parsed)) {
                        return parsed;
                  }
            }
            const matchSeconds = parseMatchSecond(ev.match_second);
            if (matchSeconds !== null) {
                  return matchSeconds * 1000;
            }
            const id = Number(ev.id);
            return Number.isFinite(id) ? id : null;
      }

      function parseMatchSecond(value) {
            if (value === null || value === undefined) return null;
            const numeric = Number(value);
            if (!Number.isFinite(numeric)) {
                  return null;
            }
            return Math.max(0, numeric);
      }


      // Fetch period state from DB and update UI
      function refreshPeriodStateFromApi() {
            const url = cfg.endpoints && cfg.endpoints.periodsList ? cfg.endpoints.periodsList : '/app/api/matches/periods_list.php';
            if (!url) return;
            $.getJSON(url)
                  .done((res) => {
                        if (!res.ok || !Array.isArray(res.periods)) return;
                        const nextState = createBasePeriodState();
                        (res.periods || []).forEach((p) => {
                              const key = p.period_key;
                              if (!nextState[key]) return;
                              const entry = nextState[key];
                              entry.started = !!p.start_second;
                              entry.ended = !!p.end_second;
                              entry.startMatchSecond = p.start_second !== null ? Number(p.start_second) : null;
                              entry.endMatchSecond = p.end_second !== null ? Number(p.end_second) : null;
                              entry.label = p.label || entry.label;
                        });
                        setPeriodState(nextState);
                  });
      }

      // On page load, use DB period state
      refreshPeriodStateFromApi();

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
                        // After period action, refresh period state from DB
                        refreshPeriodStateFromApi();
                  })
                  .fail((xhr, status, error) => {
                        showToast(`Unable to ${action} ${label}`, true);
                        console.error('Period boundary request failed', xhr.responseText || error || status);
                  });
      }

      function handlePeriodAction(typeKey, label, periodKey, action) {
            recordPeriodBoundary(action, periodKey, label);
            addPeriodMarker(typeKey, label);
      }

      function getPeriodsModalFocusable() {
            if (!$periodsModal.length) return [];
            const nodes = $periodsModal[0].querySelectorAll(periodsModalFocusableSelector);
            return Array.from(nodes).filter((el) => !el.disabled && el.offsetParent !== null);
      }

      function openPeriodsModal() {
            if (!$periodsModal.length || $periodsModal.attr('aria-hidden') === 'false') {
                  return;
            }
            periodModalLastFocused = document.activeElement;
            $periodsModal.attr('aria-hidden', 'false').prop('hidden', false).addClass('is-open');
            $periodsModalToggle.attr('aria-expanded', 'true');
            $(document).on('keydown.periodsModal', handlePeriodsModalKeydown);
            const focusable = getPeriodsModalFocusable();
            if (focusable.length) {
                  const preferred = focusable.find((el) => el.classList && el.classList.contains('period-btn'));
                  (preferred || focusable[0]).focus();
            } else if ($periodsModalToggle.length) {
                  $periodsModalToggle.focus();
            }
      }

      function closePeriodsModal(restoreFocus = true) {
            if (!$periodsModal.length || $periodsModal.attr('aria-hidden') === 'true') {
                  return;
            }
            $periodsModal.attr('aria-hidden', 'true').prop('hidden', true).removeClass('is-open');
            $periodsModalToggle.attr('aria-expanded', 'false');
            $(document).off('keydown.periodsModal', handlePeriodsModalKeydown);
            if (restoreFocus) {
                  const target =
                        periodModalLastFocused && typeof periodModalLastFocused.focus === 'function'
                              ? periodModalLastFocused
                              : $periodsModalToggle[0];
                  if (target && typeof target.focus === 'function') {
                        target.focus();
                  }
            }
            periodModalLastFocused = null;
      }

      function handlePeriodsModalKeydown(event) {
            if (!$periodsModal.length || $periodsModal.attr('aria-hidden') === 'true') {
                  return;
            }
            if (event.key === 'Escape') {
                  event.preventDefault();
                  event.stopPropagation();
                  closePeriodsModal();
                  return;
            }
            if (event.key !== 'Tab') {
                  return;
            }
            const focusable = getPeriodsModalFocusable();
            if (!focusable.length) {
                  event.preventDefault();
                  return;
            }
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (event.shiftKey) {
                  if (document.activeElement === first || !focusable.includes(document.activeElement)) {
                        event.preventDefault();
                        last.focus();
                  }
            } else if (document.activeElement === last || !focusable.includes(document.activeElement)) {
                  event.preventDefault();
                  first.focus();
            }
      }

      function updateClipUi() {
            if (clipState.start !== null) {
                  $clipIn.val(clipState.start);
                  $clipInFmt.text(formatMatchSecondWithExtra(clipState.start, 0));
            } else {
                  $clipIn.val('');
                  $clipInFmt.text('');
            }
            if (clipState.end !== null) {
                  $clipOut.val(clipState.end);
                  $clipOutFmt.text(formatMatchSecondWithExtra(clipState.end, 0));
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
            refreshPlaylistAddButton();
      }

      function postJson(url, payload) {
            if (!url) {
                  return $.Deferred().reject('missing_url').promise();
            }
            return $.ajax({
                  url,
                  method: 'POST',
                  contentType: 'application/json',
                  data: JSON.stringify(payload),
            });
      }

      function playlistEnabled() {
            return !!playlistConfig.list && $playlistPanel && $playlistPanel.length;
      }

      function getPlaylistClipId(clip) {
            if (!clip) {
                  return null;
            }
            const toNumber = (value) => {
                  const numeric = Number(value);
                  return Number.isFinite(numeric) ? numeric : null;
            };
            const primary = toNumber(clip.clip_id);
            if (primary && primary > 0) {
                  return primary;
            }
            const fallback = toNumber(clip.id);
            if (fallback && fallback > 0) {
                  return fallback;
            }
            return null;
      }

      function initPlaylists() {
            if (!playlistEnabled()) {
                  console.log('[Desk DnD] initPlaylists skipped: playlistEnabled=false', {
                        playlistConfigHasList: !!playlistConfig.list,
                        playlistPanel: !!($playlistPanel && $playlistPanel.length),
                  });
                  return;
            }
            // console.log('[Desk DnD] initPlaylists start');
            fetchPlaylists();
      }

      function refreshPlaylistAddButton() {
            if (!playlistEnabled()) {
                  return;
            }
            const hasClip = !!clipState.id;
            const hasPlaylist = !!playlistState.activePlaylistId;
            const alreadyAdded =
                  hasClip &&
                  playlistState.clips.some((clip) => {
                        const playlistClipId = getPlaylistClipId(clip);
                        return playlistClipId !== null && playlistClipId === clipState.id;
                  });
            $playlistAddClipBtn.prop('disabled', !hasPlaylist || !hasClip || alreadyAdded);
      }

      function adjustPlaylistClipCount(playlistId, delta) {
            if (!playlistId || !Number.isFinite(delta)) {
                  return;
            }
            const entry = playlistState.playlists.find((pl) => pl.id === playlistId);
            if (!entry) {
                  return;
            }
            const currentCount = Number.isFinite(Number(entry.clip_count)) ? Number(entry.clip_count) : 0;
            const updated = Math.max(0, currentCount + delta);
            entry.clip_count = updated;
      }

      function getFilteredPlaylists() {
            const teamFilter = playlistViewState.teamFilter || '';
            const searchTerm = (playlistViewState.searchQuery || '').trim().toLowerCase();
            const hasTeamFilter = teamFilter !== '';
            const hasSearch = searchTerm !== '';
            if (!hasTeamFilter && !hasSearch) {
                  return playlistState.playlists.slice();
            }
            return playlistState.playlists.filter((pl) => {
                  if (hasTeamFilter) {
                        const sides = Array.isArray(pl.team_sides) ? pl.team_sides : [];
                        if (!sides.includes(teamFilter)) {
                              return false;
                        }
                  }
                  if (hasSearch) {
                        const haystack = `${pl.title || ''} ${pl.notes || ''}`.toLowerCase();
                        if (!haystack.includes(searchTerm)) {
                              return false;
                        }
                  }
                  return true;
            });
      }

      function renderPlaylistList() {
            if (!playlistEnabled()) {
                  console.log('[Desk DnD] renderPlaylistList skipped: playlistEnabled=false');
                  return;
            }
            if (!playlistState.playlists.length) {
                  $playlistList.text('No playlists yet.');
                  console.log('[Desk DnD] renderPlaylistList: no playlists');
                  return;
            }
            const filteredPlaylists = getFilteredPlaylists();
            if (!filteredPlaylists.length) {
                  const hasFilters =
                        (playlistViewState.teamFilter || '') !== '' || (playlistViewState.searchQuery || '').trim() !== '';
                  $playlistList.text(hasFilters ? 'No playlists match the current filters.' : 'No playlists yet.');
                  console.log('[Desk DnD] renderPlaylistList: no playlists after filters', { hasFilters });
                  return;
            }
            const html = filteredPlaylists
                  .map((pl) => {
                        const activeClass = pl.id === playlistState.activePlaylistId ? ' is-active' : '';
                        const clipCount = Number.isFinite(Number(pl.clip_count)) ? Number(pl.clip_count) : 0;
                        const notesHtml = pl.notes ? `<span>${h(pl.notes)}</span>` : '';
                        const safeTitle = h(pl.title || '');
                        return `<div class="playlist-item${activeClass}" data-playlist-id="${pl.id}">
                                                  <div class="playlist-item-title" data-playlist-id="${pl.id}">
                                                            <span class="playlist-item-title-text">${safeTitle}</span>
                                                            <input
                                                                      type="text"
                                                                      class="playlist-item-title-input"
                                                                      maxlength="120"
                                                                      autocomplete="off"
                                                                      aria-label="Playlist title"
                                                                      value="${safeTitle}"
                                                            />
                                                  </div>
                                                  <div class="playlist-item-meta">
                                                            <span>${clipCount} clip${clipCount === 1 ? '' : 's'}</span>
                                                            ${notesHtml}
                                                  </div>
                                                  <div class="playlist-item-actions">
                                                            <span class="playlist-item-action-group" aria-hidden="true">
                                                                      <button
                                                                                type="button"
                                                                                class="playlist-item-action playlist-item-action--download"
                                                                                data-playlist-action="download"
                                                                                data-playlist-id="${pl.id}"
                                                                                aria-label="Download playlist"
                                                                      >
                                                                                <i class="fa-solid fa-download" aria-hidden="true"></i>
                                                                      </button>
                                                                      <button
                                                                                type="button"
                                                                                class="playlist-item-action playlist-item-action--delete"
                                                                                data-playlist-action="delete"
                                                                                data-playlist-id="${pl.id}"
                                                                                aria-label="Delete playlist"
                                                                      >
                                                                                <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                                                      </button>
                                                            </span>
                                                  </div>
                                            </div>`;
                  })
                  .join('');
            $playlistList.html(html);
            // console.log('[Desk DnD] renderPlaylistList: rendered playlists', ...);
      }

      function handlePlaylistTitleClick(event) {
            event.stopPropagation();
            if (!playlistEnabled()) {
                  return;
            }
            if (!cfg.canEditRole) {
                  return;
            }
            const $titleEl = $(event.currentTarget);
            startPlaylistTitleEdit($titleEl);
      }

      function startPlaylistTitleEdit($titleEl) {
            if (!$titleEl || !$titleEl.length) {
                  return;
            }
            if ($titleEl.hasClass('is-editing')) {
                  return;
            }
            if (playlistTitleEditor && playlistTitleEditor[0] !== $titleEl[0]) {
                  cancelPlaylistTitleEdit(playlistTitleEditor);
            }
            const $input = $titleEl.find('.playlist-item-title-input');
            if (!$input.length) {
                  return;
            }
            const currentValue = ($input.val() || '').trim();
            $input.data('originalTitle', currentValue);
            playlistTitleEditor = $titleEl;
            $titleEl.addClass('is-editing');
            window.requestAnimationFrame(() => {
                  $input.prop('disabled', false);
                  $input.focus();
                  $input.select();
            });
      }

      function cancelPlaylistTitleEdit($titleEl) {
            if (!$titleEl || !$titleEl.length) {
                  return;
            }
            const $input = $titleEl.find('.playlist-item-title-input');
            if ($input.length) {
                  const original = $input.data('originalTitle');
                  if (typeof original === 'string') {
                        $input.val(original);
                  }
                  $input.removeData('originalTitle');
            }
            $titleEl.removeClass('is-editing');
            if (playlistTitleEditor && playlistTitleEditor[0] === $titleEl[0]) {
                  playlistTitleEditor = null;
            }
      }

      function submitPlaylistTitleEdit($input) {
            if (!$input || !$input.length) {
                  return;
            }
            const $titleEl = $input.closest('.playlist-item-title');
            if (!$titleEl.length) {
                  return;
            }
            const playlistId = Number($titleEl.data('playlist-id'));
            if (!playlistId) {
                  cancelPlaylistTitleEdit($titleEl);
                  return;
            }
            const trimmed = ($input.val() || '').trim();
            const original = ($input.data('originalTitle') || '').trim();
            if (!trimmed) {
                  showError('Playlist name cannot be empty');
                  window.requestAnimationFrame(() => {
                        $input.val(original);
                        $input.focus();
                        $input.select();
                  });
                  return;
            }
            if (trimmed === original) {
                  cancelPlaylistTitleEdit($titleEl);
                  return;
            }
            const renameUrl = playlistConfig.rename;
            if (!renameUrl) {
                  cancelPlaylistTitleEdit($titleEl);
                  return;
            }
            $input.prop('disabled', true);
            postJson(renameUrl, { playlist_id: playlistId, title: trimmed })
                  .done((res) => {
                        if (!res || res.ok === false) {
                              showError('Unable to rename playlist', res ? res.error : 'Unknown');
                              return;
                        }
                        const updatedTitle = res.playlist && res.playlist.title ? res.playlist.title : trimmed;
                        const entry = playlistState.playlists.find((pl) => pl.id === playlistId);
                        if (entry) {
                              entry.title = updatedTitle;
                        }
                        playlistTitleEditor = null;
                        renderPlaylistList();
                        updatePlaylistHeader();
                        showToast('Playlist renamed');
                  })
                  .fail((xhr, status, error) => {
                        showError('Unable to rename playlist', xhr.responseText || error || status);
                        window.requestAnimationFrame(() => {
                              $input.focus();
                              $input.select();
                        });
                  })
                  .always(() => {
                        $input.prop('disabled', false);
                  });
      }

      function handlePlaylistTitleInputKeydown(event) {
            const key = event.key;
            if (key === 'Enter') {
                  event.preventDefault();
                  event.currentTarget.blur();
                  return;
            }
            if (key === 'Escape') {
                  event.preventDefault();
                  const $input = $(event.currentTarget);
                  $input.data('ignoreBlur', true);
                  cancelPlaylistTitleEdit($input.closest('.playlist-item-title'));
                  $input.blur();
            }
      }

      function handlePlaylistTitleInputBlur(event) {
            const $input = $(event.currentTarget);
            if ($input.data('ignoreBlur')) {
                  $input.removeData('ignoreBlur');
                  return;
            }
            submitPlaylistTitleEdit($input);
      }

      function updatePlaylistFilterOptions() {
            if (!$playlistFilterPopover.length) {
                  return;
            }
            const currentSelection = playlistViewState.teamFilter || '';
            $playlistFilterPopover
                  .find('.playlist-filter-option')
                  .each(function () {
                        const $option = $(this);
                        const optionValue = ($option.data('team') || '').toString();
                        $option.toggleClass('is-active', optionValue === currentSelection);
                  });
      }

      function closePlaylistFilterPopover() {
            if (!$playlistFilterPopover.length || !playlistFilterPopoverOpen) {
                  return;
            }
            playlistFilterPopoverOpen = false;
            $playlistFilterPopover.attr('hidden', 'hidden');
            $playlistFilterBtn.attr('aria-expanded', 'false');
      }

      function openPlaylistFilterPopover() {
            if (!$playlistFilterPopover.length || playlistFilterPopoverOpen) {
                  return;
            }
            playlistFilterPopoverOpen = true;
            $playlistFilterPopover.removeAttr('hidden');
            $playlistFilterBtn.attr('aria-expanded', 'true');
      }

      function togglePlaylistFilterPopover(event) {
            if (!playlistEnabled() || !$playlistFilterPopover.length) {
                  return;
            }
            event.preventDefault();
            event.stopPropagation();
            if (playlistFilterPopoverOpen) {
                  closePlaylistFilterPopover();
                  return;
            }
            openPlaylistFilterPopover();
      }

      function handlePlaylistFilterOptionClick(event) {
            event.preventDefault();
            const selectedTeam = ($(this).data('team') || '').toString();
            playlistViewState.teamFilter = selectedTeam;
            updatePlaylistFilterOptions();
            renderPlaylistList();
            closePlaylistFilterPopover();
      }

      function handleDocumentClickCloseFilter(event) {
            if (!playlistFilterPopoverOpen) {
                  return;
            }
            const $target = $(event.target);
            if ($target.closest($playlistFilterPopover).length || $target.closest($playlistFilterBtn).length) {
                  return;
            }
            closePlaylistFilterPopover();
      }

      function togglePlaylistSearchRow(event) {
            if (!$playlistSearchRow.length) {
                  return;
            }
            event.preventDefault();
            const nextVisible = !$playlistSearchRow.hasClass('is-visible');
            $playlistSearchRow.toggleClass('is-visible', nextVisible);
            $playlistSearchToggle.attr('aria-pressed', nextVisible ? 'true' : 'false');
            if (nextVisible) {
                  $playlistSearchInput.focus();
            }
      }

      function togglePlaylistCreateRow(event) {
            if (!$playlistCreateRow.length) {
                  return;
            }
            event.preventDefault();
            const nextVisible = !$playlistCreateRow.hasClass('is-visible');
            $playlistCreateRow.toggleClass('is-visible', nextVisible);
            $playlistCreateToggle.attr('aria-pressed', nextVisible ? 'true' : 'false');
            if (nextVisible) {
                  $playlistTitleInput.focus();
            }
      }

      function handlePlaylistSearchInput() {
            if (!$playlistSearchInput.length) {
                  return;
            }
            playlistViewState.searchQuery = ($playlistSearchInput.val() || '').toString();
            renderPlaylistList();
      }

      function updatePlaylistHeader() {
            if (!playlistEnabled()) {
                  return;
            }
            const active = playlistState.playlists.find((pl) => pl.id === playlistState.activePlaylistId);
            if (active) {
                  const clipCount = playlistState.clips.length;
                  $playlistActiveTitle.text(`${h(active.title)} (${clipCount} clip${clipCount === 1 ? '' : 's'})`);
            } else {
                  $playlistActiveTitle.text('Select a playlist to begin');
            }
      }

      function fetchPlaylists() {
            if (!playlistEnabled()) {
                  console.log('[Desk DnD] fetchPlaylists skipped: playlistEnabled=false', {
                        playlistConfigHasList: !!playlistConfig.list,
                        playlistPanel: !!($playlistPanel && $playlistPanel.length),
                  });
                  return;
            }
            $playlistList.text('Loading playlists…');
            $.get(playlistConfig.list)
                  .done((res) => {
                        if (!res || res.ok === false) {
                              showError('Unable to load playlists', res ? res.error : 'Unknown');
                              $playlistList.text('Unable to load playlists');
                              return;
                        }
                        playlistState.playlists = Array.isArray(res.playlists) ? res.playlists : [];
                        // console.log('[Desk DnD] fetchPlaylists success', ...);
                        renderPlaylistList();
                        if (playlistState.activePlaylistId) {
                              const stillExists = playlistState.playlists.some((pl) => pl.id === playlistState.activePlaylistId);
                              if (stillExists) {
                                    loadPlaylist(playlistState.activePlaylistId);
                              } else {
                                    playlistState.activePlaylistId = null;
                                    playlistState.clips = [];
                                    playlistState.activeIndex = -1;
                                    renderPlaylistClips();
                                    updatePlaylistHeader();
                                    refreshPlaylistAddButton();
                              }
                        }
                  })
                  .fail((xhr, status, error) => {
                        showError('Unable to load playlists', xhr.responseText || error || status);
                        $playlistList.text('Unable to load playlists');
                        console.log('[Desk DnD] fetchPlaylists failed', { status, error, response: xhr && xhr.responseText });
                  });
      }

      function loadPlaylist(playlistId) {
            if (!playlistEnabled() || !playlistId) {
                  return;
            }
            const url = playlistConfig.show(playlistId);
            if (!url) {
                  return;
            }
            $playlistClips.text('Loading clips…');
            $.get(url)
                  .done((res) => {
                        if (!res || res.ok === false) {
                              showError('Unable to load playlist', res ? res.error : 'Unknown');
                              return;
                        }
                        const clips = Array.isArray(res.clips) ? res.clips.slice() : [];
                        clips.sort((a, b) => {
                              const aOrder = Number.isFinite(Number(a && a.sort_order)) ? Number(a.sort_order) : 0;
                              const bOrder = Number.isFinite(Number(b && b.sort_order)) ? Number(b.sort_order) : 0;
                              if (aOrder !== bOrder) {
                                    return aOrder - bOrder;
                              }
                              const aId = Number.isFinite(Number(a && getEventClipId(a))) ? Number(getEventClipId(a)) : 0;
                              const bId = Number.isFinite(Number(b && getEventClipId(b))) ? Number(getEventClipId(b)) : 0;
                              return aId - bId;
                        });
                        playlistState.activePlaylistId = res.playlist ? res.playlist.id : playlistId;
                        playlistState.clips = clips;
                        playlistState.activeIndex = playlistState.clips.length ? 0 : -1;
                        renderPlaylistList();
                        renderPlaylistClips();
                        if (playlistState.activeIndex >= 0) {
                              const clip = playlistState.clips[playlistState.activeIndex];
                              if (clip) {
                                    goToVideoTime(clip.start_second);
                              }
                        }
                  })
                  .fail((xhr, status, error) => {
                        showError('Unable to load playlist', xhr.responseText || error || status);
                  });
      }

      function getClipAtIndex(index) {
            if (!playlistState.clips.length) {
                  return null;
            }
            const safeIndex = typeof index === 'number' ? index : playlistState.activeIndex;
            if (safeIndex < 0 || safeIndex >= playlistState.clips.length) {
                  return null;
            }
            return playlistState.clips[safeIndex];
      }

      function normalizeClipSecond(value) {
            const normalized = Number(value);
            return Number.isFinite(normalized) ? normalized : null;
      }

      function emitClipPlaybackState() {
            const clip = getClipAtIndex(playlistState.activeIndex);
            if (clip) {
                  clipPlaybackState.mode = 'clip';
                  const clipId = getPlaylistClipId(clip);
                  clipPlaybackState.clipId = clipId !== null ? clipId : null;
                  clipPlaybackState.startSecond = normalizeClipSecond(clip.start_second);
                  clipPlaybackState.endSecond = normalizeClipSecond(clip.end_second);
            } else {
                  clipPlaybackState.mode = 'match';
                  clipPlaybackState.clipId = null;
                  clipPlaybackState.startSecond = null;
                  clipPlaybackState.endSecond = null;
            }
            window.dispatchEvent(
                  new CustomEvent('DeskClipPlaybackChanged', {
                        detail: {
                              mode: clipPlaybackState.mode,
                              clipId: clipPlaybackState.clipId,
                              startSecond: clipPlaybackState.startSecond,
                              endSecond: clipPlaybackState.endSecond,
                        },
                  })
            );
      }

      function renderPlaylistClips() {
            if (!playlistEnabled()) {
                  return;
            }
            updatePlaylistHeader();
            if (!playlistState.clips.length) {
                  $playlistClips.html('<div class="text-muted-alt text-sm">No clips in this playlist yet.</div>');
                  refreshPlaylistAddButton();
                  updatePlaylistControls();
                  emitClipPlaybackState();
                  return;
            }
            const html = playlistState.clips
                  .map((clip, idx) => {
                        const activeClass = idx === playlistState.activeIndex ? ' playlist-clip-active' : '';
                        const clipId = getPlaylistClipId(clip);
                        const clipIdAttr = clipId !== null ? String(clipId) : '';
                        const clipLabel = clipIdAttr || (clip.id ? String(clip.id) : '—');
                        const startLabel = formatMatchSecondWithExtra(clip.start_second, 0);
                        const durationText = clip.duration_seconds ? `${clip.duration_seconds}s` : '—';
                        const clipName = clip.clip_name || `Clip #${clipLabel}`;
                        return `<div class="playlist-clip${activeClass}" data-clip-id="${clipIdAttr}">
                                                  <span class="playlist-clip-icon" aria-hidden="true">
                                                            <i class="fa-solid fa-play"></i>
                                                  </span>
                                                  <div class="playlist-clip-body">
                                                            <div class="playlist-clip-title">${h(clipName)}</div>
                                                            <div class="playlist-clip-subtext">${h(startLabel)} · ${h(durationText)}</div>
                                                            <div class="playlist-clip-meta">Clip #${h(clipLabel)}</div>
                                                  </div>
                                                  <button type="button" class="playlist-clip-download" data-clip-id="${clipIdAttr}" aria-label="Download clip">
                                                            <i class="fa-solid fa-download" aria-hidden="true"></i>
                                                  </button>
                                                  <button type="button" class="playlist-clip-delete" data-clip-id="${clipIdAttr}" aria-label="Remove clip">
                                                            <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                                                  </button>
                                            </div>`;
                  })
                  .join('');
            $playlistClips.html(html);
            refreshPlaylistAddButton();
            updatePlaylistControls();
            emitClipPlaybackState();
      }

      function updatePlaylistControls() {
            if (!playlistEnabled()) {
                  return;
            }
            const hasClips = playlistState.clips.length > 0;
            $playlistPrevBtn.prop('disabled', !hasClips || playlistState.activeIndex <= 0);
            $playlistNextBtn.prop('disabled', !hasClips || playlistState.activeIndex >= playlistState.clips.length - 1);
      }

      function setActiveClip(index) {
            if (!playlistEnabled()) {
                  return;
            }
            if (typeof index !== 'number' || !playlistState.clips.length) {
                  return;
            }
            const clamped = Math.max(0, Math.min(playlistState.clips.length - 1, index));
            playlistState.activeIndex = clamped;
            renderPlaylistClips();
            const clip = playlistState.clips[playlistState.activeIndex];
            if (clip) {
                  goToVideoTime(clip.start_second);
            }
      }

      function navigatePlaylist(step) {
            if (!playlistEnabled() || !playlistState.clips.length) {
                  return;
            }
            const nextIndex = playlistState.activeIndex + step;
            if (nextIndex < 0 || nextIndex >= playlistState.clips.length) {
                  return;
            }
            setActiveClip(nextIndex);
      }

      function handlePlaylistCreate(event) {
            if (!playlistEnabled()) {
                  return false;
            }
            event.preventDefault();
            const title = ($playlistTitleInput.val() || '').trim();
            if (!title) {
                  return false;
            }
            const url = playlistConfig.create;
            if (!url) {
                  return false;
            }
            $playlistTitleInput.prop('disabled', true);
            $.post(url, { title })
                  .done((res) => {
                        if (!res || res.ok === false) {
                              showError('Unable to create playlist', res ? res.error : 'Unknown');
                              return;
                        }
                        $playlistTitleInput.val('');
                        fetchPlaylists();
                        if (res.playlist && res.playlist.id) {
                              loadPlaylist(res.playlist.id);
                        }
                  })
                  .fail((xhr, status, error) => {
                        showError('Unable to create playlist', xhr.responseText || error || status);
                  })
                  .always(() => $playlistTitleInput.prop('disabled', false));
            return false;
      }

      function handlePlaylistSelection() {
            const id = $(this).data('playlistId');
            if (id) {
                  loadPlaylist(id);
            }
      }

      function handlePlaylistItemAction(event) {
            event.stopPropagation();
            const $button = $(event.currentTarget);
            const playlistId = Number($button.data('playlistId'));
            if (!playlistId) {
                  return;
            }
            const action = ($button.data('playlistAction') || '').toString();
            if (action === 'download') {
                  const downloadUrl = playlistConfig.download;
                  if (!downloadUrl) {
                        showError('Unable to download playlist', 'Download endpoint unavailable');
                        return;
                  }
                  const separator = downloadUrl.includes('?') ? '&' : '?';
                  const url = `${downloadUrl}${separator}playlist_id=${encodeURIComponent(playlistId)}`;
                  window.location.href = url;
                  return;
            }
            if (action !== 'delete') {
                  return;
            }
            const entry = playlistState.playlists.find((pl) => pl.id === playlistId);
            const titleValue = entry && entry.title ? entry.title.toString() : '';
            const escapedTitle = titleValue ? titleValue.replace(/"/g, '\\"') : '';
            const label = escapedTitle ? `"${escapedTitle}"` : 'this playlist';
            if (!confirm(`Delete playlist ${label}?`)) {
                  return;
            }
            deletePlaylistById(playlistId, $button);
      }

      function deletePlaylistById(playlistId, $trigger) {
            if (!playlistId) {
                  return;
            }
            const url = playlistConfig.delete;
            if (!url) {
                  showError('Unable to delete playlist', 'Missing delete endpoint');
                  return;
            }
            if ($trigger && $trigger.length) {
                  $trigger.prop('disabled', true);
            }
            postJson(url, { playlist_id: playlistId })
                  .done((res) => {
                        if (!res || res.ok === false) {
                              showError('Unable to delete playlist', res ? res.error : 'Unknown');
                              return;
                        }
                        showToast('Playlist deleted');
                        playlistState.playlists = playlistState.playlists.filter((pl) => pl.id !== playlistId);
                        if (playlistState.activePlaylistId === playlistId) {
                              playlistState.activePlaylistId = null;
                              playlistState.clips = [];
                              playlistState.activeIndex = -1;
                              renderPlaylistClips();
                              updatePlaylistHeader();
                              refreshPlaylistAddButton();
                        }
                        renderPlaylistList();
                  })
                  .fail((xhr, status, error) => {
                        showError('Unable to delete playlist', xhr && xhr.responseText ? xhr.responseText : error || status || 'Unknown');
                  })
                  .always(() => {
                        if ($trigger && $trigger.length) {
                              $trigger.prop('disabled', false);
                        }
                  });
      }

      function handlePlaylistRefresh() {
            fetchPlaylists();
      }

      function handleAddClipToPlaylist() {
            if (!playlistEnabled() || !playlistState.activePlaylistId || !clipState.id) {
                  return;
            }
            addClipToPlaylistById(playlistState.activePlaylistId, clipState.id, { setActivePlaylist: true });
      }

      function addClipToPlaylistById(playlistId, clipId, options = {}) {
            if (!playlistEnabled() || !playlistId || !clipId) {
                  return;
            }
            const { setActivePlaylist = false } = options;
            postJson(playlistConfig.addClip, {
                  playlist_id: playlistId,
                  clip_id: clipId,
            })
                  .done((res) => {
                        if (!res || res.ok === false) {
                              showError('Unable to add clip', res ? res.error : 'Unknown');
                              return;
                        }
                        if (setActivePlaylist) {
                              playlistState.activePlaylistId = playlistId;
                        }
                        showToast('Clip added to playlist');
                        fetchPlaylists();
                        loadPlaylist(playlistId);
                  })
                  .fail((xhr) => {
                        if (xhr && xhr.status === 409) {
                              showToast('Clip already in playlist', true);
                              return;
                        }
                        showError('Unable to add clip', xhr.responseText || 'Unknown');
                  });
      }

      function handleMatrixItemDragStart(event) {
            const $el = $(event.currentTarget);
            const dataClipId = $el.data('clipId');
            const attrClipId = $el.attr('data-clip-id');
            const clipId = dataClipId !== undefined && dataClipId !== null ? dataClipId : attrClipId;
            const hasClipId = clipId !== undefined && clipId !== null && `${clipId}` !== '';
            if (!hasClipId) {
                  console.log('[Desk DnD] dragstart ignored: no clipId on element', {
                        hasDataTransfer: !!(event.originalEvent && event.originalEvent.dataTransfer),
                        dataClipId,
                        attrClipId,
                        target: event.currentTarget && event.currentTarget.outerHTML ? event.currentTarget.outerHTML.slice(0, 200) : null,
                  });
                  showToast('This event has no clip yet. Set in/out and create a clip first.', true);
                  event.preventDefault();
                  return;
            }
            const clipIdString = String(clipId);
            const clipNumeric = Number(clipIdString);
            if (!Number.isFinite(clipNumeric) || clipNumeric <= 0) {
                  console.log('[Desk DnD] dragstart ignored: non-positive clipId', { clipIdString, clipNumeric });
                  showToast('This event has no clip yet. Set in/out and create a clip first.', true);
                  event.preventDefault();
                  return;
            }
            console.log('[Desk DnD] dragstart', { clipId: clipIdString, hasDataTransfer: !!(event.originalEvent && event.originalEvent.dataTransfer) });
            if (event.originalEvent && event.originalEvent.dataTransfer) {
                  event.originalEvent.dataTransfer.setData('text/plain', clipIdString);
                  event.originalEvent.dataTransfer.effectAllowed = 'copy';
            }
            draggingClipId = clipIdString;
      }

      function handleMatrixItemDragEnd() {
            console.log('[Desk DnD] dragend', { draggingClipId });
            draggingClipId = null;
            clearPlaylistDropHighlight();
      }

      function handlePlaylistDragOver(event) {
            if (!draggingClipId) {
                  console.log('[Desk DnD] dragover ignored: no draggingClipId');
                  return;
            }
            const $target = $(event.currentTarget);
            dropTargetElement = $target[0];
            $target.addClass('is-drop-target');
            event.preventDefault();
            console.log('[Desk DnD] dragover', { playlistId: $target.data('playlistId'), draggingClipId });
            if (event.originalEvent && event.originalEvent.dataTransfer) {
                  event.originalEvent.dataTransfer.dropEffect = 'copy';
            }
      }

      function handlePlaylistDragEnter(event) {
            if (!draggingClipId) {
                  console.log('[Desk DnD] dragenter ignored: no draggingClipId');
                  return;
            }
            const $target = $(event.currentTarget);
            dropTargetElement = $target[0];
            $target.addClass('is-drop-target');
            event.preventDefault();
            console.log('[Desk DnD] dragenter', { playlistId: $target.data('playlistId'), draggingClipId });
      }

      function handlePlaylistDragLeave(event) {
            if (!draggingClipId) {
                  console.log('[Desk DnD] dragleave ignored: no draggingClipId');
                  return;
            }
            const $target = $(event.currentTarget);
            const related = event.originalEvent && event.originalEvent.relatedTarget;
            if (related && $target[0] && $target[0].contains(related)) {
                  console.log('[Desk DnD] dragleave ignored: moving inside target', { playlistId: $target.data('playlistId') });
                  return;
            }
            if (dropTargetElement === $target[0]) {
                  dropTargetElement = null;
            }
            $target.removeClass('is-drop-target');
            console.log('[Desk DnD] dragleave', { playlistId: $target.data('playlistId'), draggingClipId });
      }

      function handlePlaylistDrop(event) {
            event.preventDefault();
            event.stopPropagation();
            const $target = $(event.currentTarget);
            $target.removeClass('is-drop-target');
            const playlistId = $target.data('playlistId');
            const transfer = event.originalEvent && event.originalEvent.dataTransfer;
            const transferredClipId = transfer ? transfer.getData('text/plain') : null;
            const rawClipId = transferredClipId || draggingClipId;
            const hasClipId = rawClipId !== undefined && rawClipId !== null && rawClipId !== '';
            const clipId = hasClipId ? String(rawClipId) : null;
            const clipNumeric = clipId !== null ? Number(clipId) : NaN;
            console.log('[Desk DnD] drop', { playlistId, transferredClipId, draggingClipId, resolvedClipId: clipId });
            draggingClipId = null;
            clearPlaylistDropHighlight();
            if (!playlistId) {
                  console.log('[Desk DnD] drop aborted: missing playlistId');
                  return;
            }
            if (!clipId || !Number.isFinite(clipNumeric) || clipNumeric <= 0) {
                  console.log('[Desk DnD] drop aborted: missing clipId');
                  showToast('No clip available to add', true);
                  return;
            }
            addClipToPlaylistById(playlistId, clipId, { setActivePlaylist: true });
      }

      function clearPlaylistDropHighlight() {
            dropTargetElement = null;
            if (!$playlistList.length) {
                  return;
            }
            $playlistList.find('.playlist-item.is-drop-target').removeClass('is-drop-target');
      }

      function handleDownloadClip(event) {
            event.preventDefault();
            event.stopPropagation();
            if (!playlistEnabled() || !playlistState.activePlaylistId) {
                  return;
            }
            const clipId = $(this).data('clipId');
            if (!clipId) {
                  return;
            }
            downloadClipAsMP4(clipId);
      }

      function downloadClipAsMP4(clipId) {
            if (!clipId) {
                  return;
            }
            const matchId = parseInt(window.location.pathname.match(/\/matches\/(\d+)/)?.[1] || '0', 10);
            if (!matchId) {
                  showError('Unable to download clip', 'Match ID not found');
                  return;
            }
            const downloadUrl = `/api/matches/${matchId}/clips/${clipId}/download`;
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.download = ''; // Browser will use filename from Content-Disposition header
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
      }

      function handleRemoveClip(event) {
            event.preventDefault();
            event.stopPropagation();
            if (!playlistEnabled() || !playlistState.activePlaylistId) {
                  return;
            }
            const clipId = $(this).data('clipId');
            if (!clipId) {
                  return;
            }
            removeClipFromPlaylistById(playlistState.activePlaylistId, clipId);
      }

      function removeClipFromPlaylistById(playlistId, clipId) {
            if (!playlistEnabled() || !playlistId || !clipId) {
                  return;
            }
            postJson(playlistConfig.removeClip, {
                  playlist_id: playlistId,
                  clip_id: clipId,
            })
                  .done((res) => {
                        if (!res || res.ok === false) {
                              console.error('Unable to remove clip', res ? res.error : 'Unknown');
                              showError('Unable to remove clip', res ? res.error : 'Unknown');
                              return;
                        }
                        const normalizedClipId = String(clipId);
                        const clipSelector = `.playlist-clip[data-clip-id="${normalizedClipId}"]`;
                        const $clipElement = $playlistClips.find(clipSelector);
                        const finalizeRemoval = () => {
                              playlistState.clips = playlistState.clips.filter((clip) => {
                                    const playlistClipId = getPlaylistClipId(clip);
                                    return playlistClipId === null ? true : String(playlistClipId) !== normalizedClipId;
                              });
                              adjustPlaylistClipCount(playlistId, -1);
                              renderPlaylistList();
                              if (playlistState.activeIndex >= playlistState.clips.length) {
                                    playlistState.activeIndex = playlistState.clips.length ? playlistState.clips.length - 1 : -1;
                              }
                              renderPlaylistClips();
                              showToast('Clip removed from playlist', false);
                              loadPlaylist(playlistId);
                              // Removal already reflected in playlist state.
                        };
                        if ($clipElement.length) {
                              $clipElement.fadeOut(180, function () {
                                    finalizeRemoval();
                              });
                        } else {
                              finalizeRemoval();
                        }
                  })
                  .fail((xhr, status, error) => {
                        console.error('Playlist clip removal failed', xhr.responseText || error || status);
                        showError('Unable to remove clip', xhr.responseText || error || status);
                  });
      }

      function handleClipClick() {
            if (!playlistEnabled()) {
                  return;
            }
            const clipId = $(this).attr('data-clip-id');
            if (!clipId) return;
            const idx = playlistState.clips.findIndex((clip) => {
                  const playlistClipId = getPlaylistClipId(clip);
                  return playlistClipId !== null && String(playlistClipId) === clipId;
            });
            if (idx >= 0) {
                  setActiveClip(idx);
            }
      }

      function reorderPlaylistFromDom() {
            if (!playlistEnabled() || !playlistState.activePlaylistId) {
                  return;
            }
            const order = [];
            $playlistClips.find('.playlist-clip').each((idx, el) => {
                  const clipId = $(el).data('clipId');
                  if (!clipId) {
                        return;
                  }
                  order.push({
                        clip_id: clipId,
                        sort_order: idx,
                  });
            });
            persistPlaylistOrder(order);
      }

      function persistPlaylistOrder(ordering) {
            if (!playlistEnabled() || !playlistState.activePlaylistId) {
                  return;
            }
            postJson(playlistConfig.reorder, {
                  playlist_id: playlistState.activePlaylistId,
                  order: ordering,
            })
                  .done((res) => {
                        if (!res || res.ok === false) {
                              showError('Unable to reorder playlist', res ? res.error : 'Unknown');
                              return;
                        }
                        applyPlaylistOrdering(ordering);
                        showToast('Playlist order updated');
                  })
                  .fail((xhr, status, error) => showError('Unable to reorder playlist', xhr.responseText || error || status));
      }

      function applyPlaylistOrdering(ordering) {
            const clipMap = new Map();
            playlistState.clips.forEach((clip) => {
                  const playlistClipId = getPlaylistClipId(clip);
                  if (playlistClipId !== null) {
                        clipMap.set(playlistClipId, clip);
                  }
            });
            const currentClip =
                  (playlistState.activeIndex >= 0 && playlistState.activeIndex < playlistState.clips.length)
                        ? playlistState.clips[playlistState.activeIndex]
                        : null;
            const currentClipId = getPlaylistClipId(currentClip);
            const updated = [];
            ordering.forEach((entry) => {
                  const clip = clipMap.get(entry.clip_id);
                  if (clip) {
                        clip.sort_order = entry.sort_order;
                        updated.push(clip);
                  }
            });
            playlistState.clips = updated;
            const newIndex =
                  currentClipId !== null
                        ? playlistState.clips.findIndex((clip) => {
                              const playlistClipId = getPlaylistClipId(clip);
                              return playlistClipId !== null && playlistClipId === currentClipId;
                        })
                        : -1;
            playlistState.activeIndex = newIndex >= 0 ? newIndex : (playlistState.clips.length ? 0 : -1);
            renderPlaylistClips();
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
            markEditorDirty();
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
            // Handle desk mode buttons (Summary, Tag Live, Drawings)
            $(document).on('click', '.desk-mode-button', function () {
                  const $btn = $(this);
                  const mode = $btn.data('mode');

                  // Update button states
                  $btn.closest('.desk-mode-bar').find('.desk-mode-button').each(function () {
                        const isActive = $(this).data('mode') === mode;
                        $(this).toggleClass('is-active', isActive);
                        $(this).attr('aria-pressed', isActive ? 'true' : 'false');
                  });

                  // Show/hide content
                  const $modeContainer = $btn.closest('.desk-side-shell');
                  const $liveTagging = $modeContainer.find('[data-desk-live-tagging]');
                  const $modePanels = $modeContainer.find('[data-mode-panels]');

                  if (mode === 'tag-live') {
                        $liveTagging.attr('aria-hidden', 'false');
                        $modePanels.find('.desk-mode-panel').attr('aria-hidden', 'true');
                  } else {
                        $liveTagging.attr('aria-hidden', 'true');
                        $modePanels.find('.desk-mode-panel').each(function () {
                              const isPanelActive = $(this).data('panel') === mode;
                              $(this).attr('aria-hidden', !isPanelActive);
                        });
                  }
            });

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

            $(document).on('click', '#goalPlayerList .goal-player-option', handleGoalPlayerSelection);
            $(document).on('click', '[data-goal-unknown]', handleGoalUnknownClick);
            $(document).on('click', '[data-goal-modal-close]', (event) => {
                  event.preventDefault();
                  closeGoalPlayerModal();
            });
            $(document).on('click', '#cardPlayerList .goal-player-option', handleCardPlayerSelection);
            $(document).on('click', '[data-card-unknown]', handleCardUnknownClick);
            $(document).on('click', '[data-card-modal-close]', (event) => {
                  event.preventDefault();
                  closeCardPlayerModal();
            });
            $(document).on('click', '#shotPlayerList .shot-player-option', handleShotPlayerSelection);
            $(document).on('click', '#shotPlayerModal .shot-outcome-btn', handleShotOutcomeClick);
            $(document).on('click', '[data-shot-unknown]', handleShotUnknownClick);
            $(document).on('click', '[data-shot-modal-close]', (event) => {
                  event.preventDefault();
                  closeShotPlayerModal();
            });
            $(document).on('click', '.period-modal-toggle', function (event) {
                  event.preventDefault();
                  if ($periodsModal.attr('aria-hidden') === 'false') {
                        closePeriodsModal();
                        return;
                  }
                  openPeriodsModal();
            });
            $(document).on('click', '[data-period-modal-close]', (event) => {
                  event.preventDefault();
                  closePeriodsModal();
            });

            $(document).on('click', '#eventSaveBtn', saveEvent);
            $(document).on('click', '#eventNewBtn', () => {
                  selectedId = null;
                  fillForm(null);
            });
            $timeDisplay.on('input', handleTimeDisplayInput);
            $timeDisplay.on('blur change', handleTimeDisplayBlur);
            $minuteExtraDisplay.on('input change', () => updateMinuteExtraFields($minuteExtraDisplay.val()));
            $periodId.on('change', refreshPeriodHelperText);
            $(document).on('keydown', (event) => updateModifierState(event.key, true));
            $(document).on('keyup', (event) => updateModifierState(event.key, false));
            $(window).on('blur', () => {
                  modifierState.shift = modifierState.ctrl = modifierState.meta = false;
            });
            refreshPeriodHelperText();
            $(document).on('click', '#eventDeleteBtn', deleteEvent);

            // Bind tab click handler - use document delegation for better reliability
            $(document).on('click', '.editor-tab', function (e) {
                  e.preventDefault();
                  e.stopPropagation();

                  const $tab = $(this);
                  const panelValue = $tab.data('panel');
                  const isHidden = $tab.hasClass('is-hidden');

                  if (isHidden) {
                        return;
                  }

                  if (!panelValue) {
                        return;
                  }

                  setActiveEditorTab(panelValue);
            });
            $(document).on('keydown', '.editor-tab', function (e) {
                  const key = e.key;
                  if (!key) return;
                  const visibleTabs = $editorTabs.filter(':not(.is-hidden)');
                  const idx = visibleTabs.index(this);
                  if (key === 'ArrowRight' || key === 'ArrowLeft') {
                        if (visibleTabs.length <= 1) return;
                        e.preventDefault();
                        const step = key === 'ArrowRight' ? 1 : -1;
                        const nextIdx = (idx + step + visibleTabs.length) % visibleTabs.length;
                        const $next = $(visibleTabs[nextIdx]);
                        $next.focus();
                        setActiveEditorTab($next.data('panel'));
                        return;
                  }
                  if (key === 'Enter' || key === ' ') {
                        e.preventDefault();
                        const panel = $(this).data('panel');
                        if (panel) setActiveEditorTab(panel);
                  }
            });
            $editorPanel.on('click', '[data-editor-close]', function (e) {
                  e.preventDefault();
                  attemptCloseEditor();
            });
            $editorPanel.on('input change', '.desk-editable', () => markEditorDirty());
            $undoBtn.on('click', () => performActionStackRequest('undoEvent', 'Undo'));
            $redoBtn.on('click', () => performActionStackRequest('redoEvent', 'Redo'));
            $eventTypeId.on('change', () => refreshOutcomeField($eventTypeId.val(), $outcome.val()));
            // Handle team selector button clicks for contextual player list
            $editorPanel.on('click', '.team-selector-btn', function (e) {
                  e.preventDefault();
                  const selectedTeam = $(this).data('team');

                  // Update all buttons
                  $editorPanel.find('.team-selector-btn').removeClass('selected');
                  $(this).addClass('selected');

                  // Update the hidden field
                  $teamSide.val(selectedTeam);
                  updateEventEditorPlayerList(selectedTeam);
                  markEditorDirty();
            });

            // Outcome selector buttons
            $editorPanel.on('click', '.outcome-selector-btn', function (e) {
                  e.preventDefault();
                  const selectedOutcome = $(this).data('outcome');

                  // Update all buttons
                  $editorPanel.find('.outcome-selector-btn').removeClass('selected');
                  $(this).addClass('selected');

                  // Update the hidden field
                  $('#outcome').val(selectedOutcome);
                  markEditorDirty();
            });

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
                  closePeriodsModal();
            });
            $(document).on('click', '#clipInBtn', () => setClipPoint('in'));
            $(document).on('click', '#clipOutBtn', () => setClipPoint('out'));
            $(document).on('click', '#clipCreateBtn', createClip);
            $(document).on('click', '#clipDeleteBtn', deleteClip);
            if (playlistEnabled()) {
                  $playlistList.on('click', '.playlist-item-title', handlePlaylistTitleClick);
                  $playlistList.on('click', '.playlist-item-action', handlePlaylistItemAction);
                  $playlistList.on('keydown', '.playlist-item-title-input', handlePlaylistTitleInputKeydown);
                  $playlistList.on('blur', '.playlist-item-title-input', handlePlaylistTitleInputBlur);
                  if ($playlistFilterBtn.length && $playlistFilterPopover.length) {
                        $playlistFilterBtn.on('click', togglePlaylistFilterPopover);
                        $playlistFilterPopover.on('click', '.playlist-filter-option', handlePlaylistFilterOptionClick);
                        $(document).on('click', handleDocumentClickCloseFilter);
                        $(document).on('keydown', (event) => {
                              if (event.key === 'Escape') {
                                    closePlaylistFilterPopover();
                              }
                        });
                        updatePlaylistFilterOptions();
                  }
                  if ($playlistSearchToggle.length) {
                        $playlistSearchToggle.attr('aria-pressed', 'false');
                        $playlistSearchToggle.on('click', togglePlaylistSearchRow);
                  }
                  if ($playlistSearchInput.length) {
                        $playlistSearchInput.on('input', handlePlaylistSearchInput);
                  }
                  if ($playlistCreateToggle.length) {
                        $playlistCreateToggle.attr('aria-pressed', 'false');
                        $playlistCreateToggle.on('click', togglePlaylistCreateRow);
                  }
                  $playlistCreateForm.on('submit', handlePlaylistCreate);
                  $playlistList.on('click', '.playlist-item', handlePlaylistSelection);
                  if ($playlistRefreshBtn.length) {
                        $playlistRefreshBtn.on('click', handlePlaylistRefresh);
                  }
                  $playlistAddClipBtn.on('click', handleAddClipToPlaylist);
                  $playlistClips.on('click', '.playlist-clip', handleClipClick);
                  $playlistClips.on('click', '.playlist-clip-download', handleDownloadClip);
                  $playlistClips.on('click', '.playlist-clip-delete', handleRemoveClip);
                  $playlistPrevBtn.on('click', () => navigatePlaylist(-1));
                  $playlistNextBtn.on('click', () => navigatePlaylist(1));
            }

            // Bind drag-and-drop handlers unconditionally so DnD always works
            $timelineMatrix.on('dragstart', '.matrix-dot, .matrix-clip', handleMatrixItemDragStart);
            $timelineMatrix.on('dragend', '.matrix-dot, .matrix-clip', handleMatrixItemDragEnd);
            $playlistList.on('dragover', '.playlist-item', handlePlaylistDragOver);
            $playlistList.on('dragenter', '.playlist-item', handlePlaylistDragEnter);
            $playlistList.on('dragleave', '.playlist-item', handlePlaylistDragLeave);
            $playlistList.on('drop', '.playlist-item', handlePlaylistDrop);
            $(document).on('dragend', () => {
                  draggingClipId = null;
                  clearPlaylistDropHighlight();
            });
            // Debug: capture all dragstart on matrix dots and clips (even without data-clip-id)
            $(document).on('dragstart', '.matrix-dot', function (e) {
                  const attrClipId = $(this).attr('data-clip-id');
                  const dataClipId = $(this).data('clipId');
                  console.log('[Desk DnD] debug dragstart (raw matrix-dot)', {
                        attrClipId,
                        dataClipId,
                        hasDataTransfer: !!(e.originalEvent && e.originalEvent.dataTransfer),
                        outer: this.outerHTML ? this.outerHTML.slice(0, 200) : null,
                  });
            });
            // console.log('[Desk DnD] handlers bound', ...);

            $filterTeam.on('change', renderTimeline);
            $filterType.on('change', renderTimeline);
            $filterPlayer.on('change', renderTimeline);
            $timelineList.on('click', '.timeline-item', function () {
                  const seconds = $(this).data('second');
                  goToVideoTime(seconds);
            });
            $timelineList.on('click', '.timeline-edit', function (e) {
                  e.stopPropagation();
                  const id = $(this).data('id');
                  if (id) {
                        selectEvent(id);
                  }
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
            if ($timelineMatrix.length && !matrixWheelListenerBound) {
                  const matrixEl = $timelineMatrix[0];
                  if (matrixEl) {
                        matrixWheelListenerBound = true;
                        matrixEl.addEventListener('wheel', matrixViewportWheelListener, { passive: false }); // Non-passive because the handler calls preventDefault for horizontal scroll.
                  }
            }
            $timelineMatrix.on('pointerdown', '.matrix-viewport', handleMatrixPointerDown);
            $timelineMatrix.on('pointermove', '.matrix-viewport', handleMatrixPointerMove);
            $timelineMatrix.on('pointerup pointerleave pointercancel', '.matrix-viewport', handleMatrixPointerUp);
            $(window).on('resize', handleMatrixResize);
            $timelineMatrix.on('click', '.matrix-period-marker', function (e) {
                  e.preventDefault();
                  const seconds = Number($(this).data('second'));
                  if (Number.isFinite(seconds)) {
                        scrollMatrixToSecond(seconds);
                        goToVideoTime(seconds);
                  }
            });
            $timelineMatrix.on('click', '.matrix-dot', function () {
                  const $dot = $(this);
                  const sec = $dot.data('second');
                  goToVideoTime(sec);
            });
            $timelineMatrix.on('click', '.matrix-clip', function () {
                  const start = Number($(this).data('startSecond'));
                  if (Number.isFinite(start)) {
                        goToVideoTime(start);
                  }
            });
            if (annotationsEnabled) {
                  $timelineMatrix.on('pointerdown', '.matrix-drawing', handleMatrixDrawingPointerDown);
                  $timelineMatrix.on('click', '.matrix-drawing', function () {
                        const annotationId = Number($(this).data('annotationId'));
                        const sec = Number($(this).data('second'));
                        if (Number.isFinite(sec)) {
                              goToVideoTime(sec);
                        }
                        if (annotationBridge && typeof annotationBridge.highlightAnnotation === 'function') {
                              annotationBridge.highlightAnnotation(annotationId);
                        }
                  });
                  $timelineMatrix.on('contextmenu', '.matrix-drawing', function (event) {
                        event.preventDefault();
                        const annotationId = Number($(this).data('annotationId'));
                        if (Number.isFinite(annotationId) && annotationId > 0) {
                              window.dispatchEvent(
                                    new CustomEvent('DeskDrawingEditRequested', {
                                          detail: { annotationId },
                                    })
                              );
                        }
                        return false;
                  });
            }
            $timelineMatrix.on('contextmenu', '.matrix-dot', function (e) {
                  e.preventDefault();
                  const id = $(this).data('event-id');
                  if (id) {
                        selectEvent(id);
                  }
                  return false;
            });

            $(document).on('keydown', (e) => {
                  if (e.key === 'Escape' && editorOpen) {
                        attemptCloseEditor();
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
            if ($jsBadge.length) {
                  $jsBadge.text('JS');
            } else {
                  console.warn('[desk-events] jsBadge not found — skipping badge logic');
            }
            applyQuickTagReplacements();
            buildTypeMap();
            rebuildQuickTagBoard();
            syncEventTypeOptions();
            renderTagGrid();
            renderGoalPlayerList();
            renderShotPlayerList();
            setContext(currentContext);
            setTeam(currentTeam);
            fillForm(null);
            setTimelineMode(timelineMode);
            applyMode(false, {});
            // Cache playlist controls now that the DOM has rendered
            $playlistPanel = $('#playlistsPanel');
            $playlistList = $('#playlistList');
            $playlistCreateForm = $('#playlistCreateForm');
            $playlistTitleInput = $('#playlistTitleInput');
            $playlistAddClipBtn = $('#playlistAddClipBtn');
            $playlistClips = $('#playlistClips');
            $playlistActiveTitle = $('#playlistActiveTitle');
            $playlistPrevBtn = $('#playlistPrevBtn');
            $playlistNextBtn = $('#playlistNextBtn');
            $playlistRefreshBtn = $('#playlistRefreshBtn');
            $playlistFilterBtn = $('#playlistFilterBtn');
            $playlistFilterPopover = $('#playlistFilterPopover');
            $playlistSearchToggle = $('#playlistSearchToggle');
            $playlistSearchRow = $('#playlistSearchRow');
            $playlistSearchInput = $('#playlistSearchInput');
            $playlistCreateToggle = $('#playlistCreateToggle');
            $playlistCreateRow = $('#playlistCreateRow');
            setupTimeStepper($timeStepDown, -1);
            setupTimeStepper($timeStepUp, 1);
            bindHandlers();
            if (annotationsEnabled) {
                  ensureAnnotationBridge();
            }
            acquireLock();
            updateClipUi();
            loadEvents();
            initPlaylists();
            emitClipPlaybackState();
      }

      $(init);
})(jQuery);
