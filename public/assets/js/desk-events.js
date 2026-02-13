// Team side labels for player display
const teamSideLabels = Object.freeze({
      home: 'Home',
      away: 'Away',
      unknown: 'Unknown'
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
            regenerateAll: endpoints.clipRegenerateAll,
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
      const regenerateModalHtml = `
<div id="playlist-clip-regenerate-modal" aria-hidden="true" style="display:none;position:fixed;inset:0;z-index:1500;">
      <div data-regenerate-backdrop style="position:absolute;inset:0;background:rgba(15,23,42,0.55);backdrop-filter:blur(2px);"></div>
      <div role="dialog" aria-modal="true" style="position:relative;max-width:460px;margin:7vh auto;background:#fff;border-radius:0.85rem;padding:1.5rem 1.75rem 1.25rem;box-shadow:0 35px 65px rgba(15,23,42,0.35);">
            <h3 style="margin-top:0;margin-bottom:0.75rem;font-size:1.25rem;">Regenerate clip</h3>
            <p data-regenerate-message style="margin:0 0 1rem;font-size:0.95rem;color:#334155;">
                  This will permanently replace the clip using the latest naming and generation logic.
            </p>
            <div data-regenerate-error style="display:none;color:#b91c1c;font-size:0.92rem;margin-bottom:0.75rem;"></div>
            <div style="display:flex;justify-content:flex-end;gap:0.5rem;">
                  <button type="button" data-regenerate-cancel style="background:none;border:1px solid #e2e8f0;border-radius:0.35rem;padding:0.45rem 1.1rem;font-size:0.9rem;">Cancel</button>
                  <button type="button" data-regenerate-confirm style="background:#0f172a;color:#fff;border:none;border-radius:0.35rem;padding:0.45rem 1.2rem;font-size:0.9rem;">Regenerate clip</button>
            </div>
      </div>
</div>`;
      let $regenerateModal = null;
      let $regenerateModalMessage = null;
      let $regenerateModalError = null;
      let $regenerateModalConfirm = null;
      let $regenerateModalCancel = null;
      let $regenerateModalBackdrop = null;
      let regenerateModalClipId = null;
      let regenerateModalPending = false;
      let missingClipToastShown = false;
      let playlistFilterPopoverOpen = false;
      const clipPlaybackState = {
            mode: 'match',
            clipId: null,
            startSecond: null,
            endSecond: null,
      };
      window.DeskClipPlaybackState = clipPlaybackState;

      function ensureRegenerateModal() {
            if ($regenerateModal) return;
            $regenerateModal = $(regenerateModalHtml).appendTo('body');
            $regenerateModalMessage = $regenerateModal.find('[data-regenerate-message]');
            $regenerateModalError = $regenerateModal.find('[data-regenerate-error]');
            $regenerateModalConfirm = $regenerateModal.find('[data-regenerate-confirm]');
            $regenerateModalCancel = $regenerateModal.find('[data-regenerate-cancel]');
            $regenerateModalBackdrop = $regenerateModal.find('[data-regenerate-backdrop]');
            $regenerateModalConfirm.on('click', submitRegenerateClip);
            $regenerateModalCancel.on('click', function () {
                  closeRegenerateModal();
            });
            $regenerateModalBackdrop.on('click', function () {
                  closeRegenerateModal();
            });
      }

      const $video = $('#deskVideoPlayer');
      let videoDurationSeconds = null;
      if ($video.length) {
            $video.on('loadedmetadata durationchange', function () {
                  deskVideoReady = true;
                  const duration = $video[0] ? $video[0].duration : null;
                  videoDurationSeconds = Number.isFinite(duration) ? Math.max(0, Math.floor(duration)) : null;
                  tryHideDeskSkeleton();
                  if (timelineMode === 'matrix') {
                        renderTimeline();
                  }
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
      let $playlistRegenerateClipsBtn;
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
      const $timeDisplay = $('#event_time_display');
      const $timeStepDown = $('#eventTimeStepDown');
      const $timeStepUp = $('#eventTimeStepUp');
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
      const $editorShotMap = $('#editorShotMap');
      const $editorShotOriginSvg = $('#editorShotOriginSvg');
      const $editorShotTargetSvg = $('#editorShotTargetSvg');
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

      const EVENT_COLOURS = Object.freeze({
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
            off_side: '#795548',
            card: '#FFEB3B',
            yellow_card: '#FFEB3B',
            red_card: '#D62828',
            mistake: '#9E9E9E',
            turnover: '#9E9E9E',
            good_play: '#7CC378',
            highlight: '#3F51B5',
            other: '#B8C1EC',
      });
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
      // DATASET: events
      // Ownership: STRATEGY A (Server embeds all required events as JSON in window.DeskConfig; client reads from cfg.events)
      // Rationale: Avoids duplicate DB/API fetches on load, ensures single source of truth for initial render
      let events = [];
      let eventById = new Map();
      function setDeskEvents(nextEvents) {
            events = Array.isArray(nextEvents) ? nextEvents : [];
            eventById = new Map();
            for (let i = 0; i < events.length; i += 1) {
                  const ev = events[i];
                  const id = Number(ev && ev.id);
                  if (Number.isFinite(id)) {
                        eventById.set(id, ev);
                  }
            }
            window.DeskEvents = events;
            document.dispatchEvent(new CustomEvent('desk:events', { detail: events }));
      }
      let filteredCache = [];
      let selectedId = null;
      let clipState = { id: null, start: null, end: null };
      let draggingClipId = null;
      let draggingMatrixRow = null;
      let dropTargetElement = null;
      let playlistRowDropPending = false;
      let currentContext = 'all';
      let timelineMode = 'matrix';
      const storedTeam = window.localStorage ? window.localStorage.getItem('deskTeamSide') : null;
      let currentTeam = ['home', 'away'].includes(storedTeam) ? storedTeam : 'home';
      const eventTypeMap = {};
      const eventTypeAccents = {};
      const eventTypeKeyMap = {};
      const boardLabelByTypeId = {};
      const tagReplacements = Object.freeze({
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
      });
      let quickTagBoard = {
            title: 'Quick Tags',
            tiles: [],
      };
      const GOAL_EVENT_KEYS = new Set(['goal', 'goal_for', 'goal_against']);
      const GOAL_EVENT_TYPE_ID = 16;
      const SHOT_EVENT_KEYS = new Set(['shot', 'shot_on_target', 'shot_off_target']);
      const CARD_EVENT_KEYS = new Set(['card', 'yellow_card', 'red_card']);
      const OFFSIDE_EVENT_KEYS = new Set(['off_side', 'offside']);
      const shotOutcomeLabels = Object.freeze({
            on_target: 'On Target',
            off_target: 'Off Target',
      });
      // DATASET: players
      // Ownership: STRATEGY A (Server embeds all required players as JSON in window.DeskConfig; client reads from cfg.players)
      // Rationale: Avoids duplicate DB/API fetches on load, ensures single source of truth for initial render
      const players = Array.isArray(cfg.players) ? cfg.players : [];
      const playerByMatchPlayerId = new Map();
      for (let i = 0; i < players.length; i += 1) {
            const player = players[i];
            const id = Number(player && player.id);
            if (Number.isFinite(id)) {
                  playerByMatchPlayerId.set(id, player);
            }
      }

      // --- DEFERRED: Non-critical data and actions ---
      function deferNonCriticalWork() {
            // DEFERRED: derived stats, playlists, annotations, lock/session
            // Ownership: STRATEGY B (Client fetches or triggers these after first paint or user interaction)
            // Rationale: Non-critical for first paint, can be loaded lazily to reduce TTFB and initial load cost
            if (typeof window.DeskDeferredStats === 'function') {
                  window.DeskDeferredStats();
            }
            if (typeof fetchPlaylists === 'function') {
                  fetchPlaylists();
            }
            if (typeof window.DeskDeferredAnnotations === 'function') {
                  window.DeskDeferredAnnotations();
            }
            if (typeof window.DeskDeferredLockSession === 'function') {
                  window.DeskDeferredLockSession();
            }
      }

      // Run deferred work after first paint or user interaction
      function scheduleDeferredWork() {
            if (window.requestIdleCallback) {
                  window.requestIdleCallback(deferNonCriticalWork, { timeout: 2000 });
            } else {
                  setTimeout(deferNonCriticalWork, 1200);
            }
      };
      const $goalPlayerModal = $('#goalPlayerModal');
      const $goalPlayerList = $('#goalPlayerList');
      const $shotPlayerModal = $('#shotPlayerModal');
      const $shotPlayerList = $('#shotPlayerList');
      const $cardPlayerModal = $('#cardPlayerModal');
      const $cardPlayerList = $('#cardPlayerList');
      const $offsidePlayerModal = $('#offsidePlayerModal');
      const $offsidePlayerList = $('#offsidePlayerList');
      let goalModalState = { payload: null, label: '', wasPlaying: false };
      let shotModalState = { payload: null, label: '', baseLabel: '', wasPlaying: false, selectedPlayerId: null, selectedOutcome: null, selectedResult: null };
      let cardModalState = { payload: null, label: '', wasPlaying: false };
      let offsideModalState = { payload: null, label: '', wasPlaying: false };
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
      let timelineResizeObserver = null;
      let lastTimelineWidth = 0;
      let timelineRenderTimer = null;
      let initialTimelineRefreshScheduled = false;
      let editorDirty = false;
      let suppressDirtyTracking = false;
      let activeEditorTab = 'details';
      let editorOpen = false;
      let lastKnownMatchSecond = 0;
      const modifierState = { shift: false, ctrl: false, meta: false };
      let matrixRowClipMap = new Map();
      let playlistRowDropMessageTimer = null;

      function setPlaylistRowDropArmed(armed) {
            if ($playlistList && $playlistList.length) {
                  $playlistList.toggleClass('is-row-drop-armed', armed);
            }
            if ($playlistPanel && $playlistPanel.length) {
                  $playlistPanel.toggleClass('is-row-drop-armed', armed);
            }
      }

      function setPlaylistRowDropTarget(active) {
            if ($playlistList && $playlistList.length) {
                  $playlistList.toggleClass('is-row-drop-target', active);
            }
            if ($playlistPanel && $playlistPanel.length) {
                  $playlistPanel.toggleClass('is-row-drop-target', active);
            }
      }

      function setPlaylistRowDropPending(pending, message) {
            playlistRowDropPending = pending;
            if (!$playlistList || !$playlistList.length) return;
            $playlistList.toggleClass('is-row-drop-pending', pending);
            if (message) {
                  $playlistList.attr('data-row-drop-message', message);
            } else {
                  $playlistList.removeAttr('data-row-drop-message');
            }
      }

      function findPlaylistByTitle(title) {
            const needle = (title || '').trim().toLowerCase();
            if (!needle) return null;
            return (playlistState.playlists || []).find(
                  (pl) => (pl.title || '').toString().trim().toLowerCase() === needle
            ) || null;
      }

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
            const soft = `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, 0.35)`;
            const strong = `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, 0.65)`;
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
            if (lower.includes('off_side')) return 'off_side';
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
            // DATASET: eventTypes
            // Ownership: STRATEGY A (Server embeds all required event types as JSON in window.DeskConfig; client reads from cfg.eventTypes)
            // Rationale: Avoids duplicate DB/API fetches on load, ensures single source of truth for initial render
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

      function formatGameClockFromPeriods(totalSeconds) {
            if (!hasCanonicalPeriods) {
                  return '—';
            }
            const normalized = Math.max(0, Math.floor(Number(totalSeconds) || 0));
            const state = typeof periodState !== 'undefined' ? periodState : window.DeskPeriodState;
            const firstHalf = state && state.first_half ? state.first_half : null;
            const secondHalf = state && state.second_half ? state.second_half : null;
            const pad = (num) => String(num).padStart(2, '0');

            const inPeriod = (entry) => {
                  if (!entry || !Number.isFinite(entry.startMatchSecond)) return false;
                  const start = Number(entry.startMatchSecond);
                  const end = Number(entry.endMatchSecond);
                  return normalized >= start && (!Number.isFinite(end) || normalized <= end);
            };

            let period = null;
            if (secondHalf && inPeriod(secondHalf)) {
                  period = { baseMinute: 46, injuryBase: 90, entry: secondHalf };
            } else if (firstHalf && inPeriod(firstHalf)) {
                  period = { baseMinute: 0, injuryBase: 45, entry: firstHalf };
            }

            if (!period) {
                  if (firstHalf && Number.isFinite(firstHalf.startMatchSecond) && normalized < Number(firstHalf.startMatchSecond)) {
                        return '00:00';
                  }
                  if (secondHalf && Number.isFinite(secondHalf.startMatchSecond) && normalized < Number(secondHalf.startMatchSecond)) {
                        return '45:00';
                  }
                  return formatMatchSecond(normalized).text;
            }

            const startSecond = Number(period.entry.startMatchSecond);
            const endSecond = Number(period.entry.endMatchSecond);
            let effective = normalized;
            if (Number.isFinite(endSecond) && endSecond > 0) {
                  effective = Math.min(effective, endSecond);
            }
            const elapsedSeconds = Math.max(0, effective - startSecond);
            const total = elapsedSeconds + period.baseMinute * 60;
            const minute = Math.floor(total / 60);
            const second = Math.floor(total % 60);
            if (minute > period.injuryBase) {
                  return `${period.injuryBase}+${minute - period.injuryBase}`;
            }
            return `${pad(minute)}:${pad(second)}`;
      }

      function fmtTime(sec) {
            return formatMatchSecond(sec).text;
      }

      function buildMatrixTooltipHtml(ev) {
            if (!ev) return '';
            const matchSecond = Number(ev.match_second) || 0;
            const videoTime = formatMatchSecond(matchSecond).text;
            const gameTime = formatGameClockFromPeriods(matchSecond);
            const typeLabel = ev.event_type_label || eventTypeMap[resolveEventTypeId(ev)]?.label || ev.event_type_key || 'Event';
            const detailLabel = displayEventLabel(ev, typeLabel);
            const teamLabel = teamSideLabels[ev.team_side] || 'Unknown';
            const playerId = Number(ev.match_player_id);
            const player = Number.isFinite(playerId) ? playerByMatchPlayerId.get(playerId) : null;
            const playerName = player ? player.display_name || player.player_name || 'Unknown' : 'Unknown';
            const outcome = (ev.outcome || '').toString().trim();

            return `
                  <div class="matrix-tooltip-header">${h(typeLabel)}</div>
                  <div class="matrix-tooltip-sub">${h(detailLabel || '')}</div>
                  <div class="matrix-tooltip-row">
                        <span>Video time</span>
                        <strong>${h(videoTime)}</strong>
                  </div>
                  <div class="matrix-tooltip-row">
                        <span>Game time</span>
                        <strong>${h(gameTime)}</strong>
                  </div>
                  <div class="matrix-tooltip-row">
                        <span>Team</span>
                        <strong>${h(teamLabel)}</strong>
                  </div>
                  <div class="matrix-tooltip-row">
                        <span>Player</span>
                        <strong>${h(playerName)}</strong>
                  </div>
                  ${outcome ? `<div class="matrix-tooltip-row"><span>Outcome</span><strong>${h(outcome)}</strong></div>` : ''}
            `;
      }

      let matrixTooltip = null;
      function ensureMatrixTooltip() {
            if (matrixTooltip) return matrixTooltip;
            matrixTooltip = document.createElement('div');
            matrixTooltip.className = 'matrix-tooltip';
            document.body.appendChild(matrixTooltip);
            return matrixTooltip;
      }

      function positionMatrixTooltip(target, tooltip) {
            const rect = target.getBoundingClientRect();
            tooltip.style.left = '0px';
            tooltip.style.top = '0px';
            const tooltipRect = tooltip.getBoundingClientRect();
            let left = rect.left + rect.width / 2 - tooltipRect.width / 2;
            let top = rect.top - tooltipRect.height - 10;
            if (top < 8) {
                  top = rect.bottom + 10;
            }
            left = Math.max(8, Math.min(left, window.innerWidth - tooltipRect.width - 8));
            tooltip.style.left = `${left}px`;
            tooltip.style.top = `${top}px`;
      }

      let matrixTooltipBound = false;
      function bindMatrixTooltipHandlers() {
            if (matrixTooltipBound) return;
            matrixTooltipBound = true;
            $timelineMatrix.on('mouseenter', '.matrix-dot', function () {
                  const tooltip = ensureMatrixTooltip();
                  const eventId = Number(this.dataset.eventId);
                  const ev = Number.isFinite(eventId) ? eventById.get(eventId) : null;
                  tooltip.innerHTML = buildMatrixTooltipHtml(ev);
                  tooltip.classList.add('is-visible');
                  positionMatrixTooltip(this, tooltip);
            });
            $timelineMatrix.on('mouseleave', '.matrix-dot', function () {
                  // Removed matrix-period-marker-label span; tooltip only
                  if (matrixTooltip) {
                        matrixTooltip.classList.remove('is-visible');
                  }
            });
            $timelineMatrix.on('mousemove', '.matrix-dot', function () {
                  if (matrixTooltip && matrixTooltip.classList.contains('is-visible')) {
                        positionMatrixTooltip(this, matrixTooltip);
                  }
            });
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
            // match_second is the canonical source of event timing in seconds
            const normalized = Math.max(0, Math.floor(Number(value) || 0));
            lastKnownMatchSecond = normalized;
            $matchSecond.val(normalized);
            if ($timeDisplay.length) {
                  $timeDisplay.val(formatMatchSecond(normalized).text);
            }
      }

      function updateMinuteExtraFields(value) {
            // No longer used - minute_extra has been removed from the system
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

      function getDeskSession() {
            return window.DeskSession || null;
      }

      function getCurrentVideoSecond() {
            const session = getDeskSession();
            if (session && typeof session.getCurrentSecond === 'function') {
                  return session.getCurrentSecond();
            }
            if (!$video.length) {
                  return 0;
            }
            const rawSeconds = $video[0].currentTime;
            if (typeof rawSeconds !== 'number' || Number.isNaN(rawSeconds)) {
                  return 0;
            }
            return Math.max(0, Math.floor(rawSeconds));
      }

      function seekPlayback(seconds, reason = 'seek') {
            const normalized = Number(seconds);
            if (!Number.isFinite(normalized)) {
                  return;
            }
            const session = getDeskSession();
            if (session && typeof session.seek === 'function') {
                  session.seek(Math.max(0, normalized));
                  return;
            }
            if ($video.length) {
                  $video[0].currentTime = Math.max(0, normalized);
            }
      }

      function pausePlayback(reason = 'pause') {
            const session = getDeskSession();
            if (session && typeof session.pause === 'function') {
                  session.pause();
                  return;
            }
            if ($video.length) {
                  $video[0].pause();
            }
      }

      function resumePlaybackIfNeeded(wasPlaying, reason = 'play') {
            if (!wasPlaying) {
                  return;
            }
            const session = getDeskSession();
            if (session && typeof session.play === 'function') {
                  session.play();
                  return;
            }
            if ($video.length) {
                  const videoEl = $video[0];
                  if (videoEl.paused) {
                        videoEl.play().catch(() => { });
                  }
            }
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
                  'off_side',
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
            off_side: ['off_side'],
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

      function buildPlayerOptionsHtml(primaryClass, secondaryClass = '', teamFilter = null) {
            let filteredPlayers = getSortedPlayers();
            if (teamFilter && ['home', 'away'].includes(teamFilter)) {
                  filteredPlayers = filteredPlayers.filter((p) => (p.team_side || 'unknown') === teamFilter);
            }
            if (!filteredPlayers.length) {
                  // Only show Unknown Player button if no players for this team
                  return '<button type="button" class="goal-player-option" data-player-id="" data-goal-unknown data-shot-unknown data-card-unknown data-offside-unknown>Unknown Player</button>';
            }
            // For shot modal, sort by shirt number (numeric, ascending), then surname
            if (secondaryClass && secondaryClass.includes('shot-player-option')) {
                  filteredPlayers = filteredPlayers.slice().sort((a, b) => {
                        // Sort by shirt_number (numeric, missing last), then surname
                        const aNum = parseInt(a.shirt_number, 10);
                        const bNum = parseInt(b.shirt_number, 10);
                        if (!isNaN(aNum) && !isNaN(bNum)) {
                              if (aNum !== bNum) return aNum - bNum;
                        } else if (!isNaN(aNum)) {
                              return -1;
                        } else if (!isNaN(bNum)) {
                              return 1;
                        }
                        // If shirt numbers are equal or missing, sort by surname
                        const aSurname = (a.surname || '').toLowerCase();
                        const bSurname = (b.surname || '').toLowerCase();
                        if (aSurname < bSurname) return -1;
                        if (aSurname > bSurname) return 1;
                        return 0;
                  });
            }
            return filteredPlayers
                  .map((player) => {
                        const teamSide = player.team_side || 'unknown';
                        const playerId = String(player.id || '');
                        // Format: #{Number} Surname, Firstname
                        let formattedName = '';
                        if (player.shirt_number) {
                              formattedName += `#${player.shirt_number} `;
                        }
                        if (player.surname && player.firstname) {
                              formattedName += `${player.surname}, ${player.firstname}`;
                        } else {
                              formattedName += player.display_name || 'Player';
                        }
                        const baseClasses = [primaryClass];
                        if (secondaryClass) {
                              baseClasses.push(...secondaryClass.split(' ').filter(Boolean));
                        }
                        const baseClassList = baseClasses.join(' ');
                        const sideClassList = baseClasses.map((cls) => `${cls}--${teamSide}`).join(' ');
                        const optionClassList = `${baseClassList} ${sideClassList}`.trim();
                        // Remove Home/Away label for Shot modal only
                        if (secondaryClass && secondaryClass.includes('shot-player-option')) {
                              return `<button type="button" class="${optionClassList}" data-player-id="${h(playerId)}" data-team-side="${h(teamSide)}">
                                    <span class="goal-player-option-name">${h(formattedName)}</span>
                              </button>`;
                        } else {
                              const teamLabel = teamSideLabels[teamSide] || teamSideLabels.unknown;
                              return `<button type="button" class="${optionClassList}" data-player-id="${h(playerId)}" data-team-side="${h(teamSide)}">
                                    <span class="goal-player-option-name">${h(formattedName)}</span>
                                    <span class="goal-player-option-meta">${h(teamLabel)}</span>
                              </button>`;
                        }
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

            const formatPlayerLabel = (player) => {
                  const name = String(player.display_name || '').trim() || 'Player';
                  const parts = name.split(/\s+/).filter(Boolean);
                  const firstName = parts[0] || '';
                  const surname = parts.length > 1 ? parts.slice(1).join(' ') : '';
                  const shirtNumber = String(player.shirt_number || '').trim();
                  const numberPrefix = shirtNumber ? `#${shirtNumber}` : '#';
                  const fullName = surname ? `${firstName} ${surname}` : firstName;
                  return `${numberPrefix} ${fullName}`.trim();
            };

            const teamPlayers = Array.isArray(players)
                  ? players.filter((p) => (p.team_side || 'unknown') === teamSide)
                  : [];

            // Separate Starting XI and Substitutes (is_starting is 1 for starting, 0 for subs)
            const startingXI = teamPlayers.filter((p) => p.is_starting === 1 || p.is_starting === true);
            const substitutes = teamPlayers.filter((p) => p.is_starting !== 1 && p.is_starting !== true);

            // Sort Starting XI by shirt number
            startingXI.sort((a, b) => {
                  const shirtA = parseInt(a.shirt_number) || 999;
                  const shirtB = parseInt(b.shirt_number) || 999;
                  return shirtA - shirtB;
            });

            // Build Starting XI buttons
            let startingHtml = '';
            if (startingXI.length > 0) {
                  startingXI.forEach((player) => {
                        const playerId = String(player.id || '');
                        const label = formatPlayerLabel(player);
                        startingHtml += `<button type="button" class="player-selector-btn desk-editable" data-player-id="${h(playerId)}">${h(label)}</button>`;
                  });
            }

            // Build Substitutes buttons
            let subsHtml = '';
            if (substitutes.length > 0) {
                  substitutes.forEach((player) => {
                        const playerId = String(player.id || '');
                        const label = formatPlayerLabel(player);
                        subsHtml += `<button type="button" class="player-selector-btn desk-editable" data-player-id="${h(playerId)}">${h(label)}</button>`;
                  });
            }

            return { startingHtml, subsHtml };
      }

      /**
       * Update event editor player list when team is selected
       */
      function updateEventEditorPlayerList(teamSide) {
            const $playerContainer = $('#playerSelectorContainer');
            const $playerSubsContainer = $('#playerSelectorSubsContainer');
            const $playerStarting = $('#playerSelectorStarting');
            const $playerSubs = $('#playerSelectorSubs');
            const $playerInput = $('#match_player_id');

            if (!$playerContainer.length || !$playerSubsContainer.length || !$playerStarting.length || !$playerSubs.length) {
                  return;
            }

            if (!teamSide || !['home', 'away'].includes(teamSide)) {
                  $playerContainer.hide();
                  $playerSubsContainer.hide();
                  $playerStarting.html('');
                  $playerSubs.html('');
                  $playerInput.val('');
                  return;
            }

            // Show player containers
            $playerContainer.show();
            $playerSubsContainer.show();

            // Build and render player lists
            const playerLists = buildEventEditorPlayerListHtml(teamSide);
            if (!playerLists || (!playerLists.startingHtml && !playerLists.subsHtml)) {
                  $playerStarting.html('<div class="text-sm text-muted-alt">No players available</div>');
                  $playerSubs.html('');
                  return;
            }

            $playerStarting.html(playerLists.startingHtml || '<div class="text-sm text-muted-alt">None</div>');
            $playerSubs.html(playerLists.subsHtml || '<div class="text-sm text-muted-alt">None</div>');

            // Add event listeners to player buttons in both containers
            const $allButtons = $playerContainer.add($playerSubsContainer).find('.player-selector-btn');
            $allButtons.on('click', function (e) {
                  e.preventDefault();
                  const playerId = $(this).data('player-id');

                  // Remove selected class from all buttons in both containers
                  $allButtons.removeClass('selected');

                  // Add selected class to clicked button
                  $(this).addClass('selected');

                  // Update the hidden input
                  $playerInput.val(playerId);
                  editorDirty = true;
            });

            // Set selected state if player is already selected
            const currentPlayerId = $playerInput.val();
            if (currentPlayerId) {
                  $allButtons.filter(`[data-player-id="${currentPlayerId}"]`).addClass('selected');
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

      function shouldShowEditorShotMap(typeKey) {
            return isShotTypeKey(typeKey) || isGoalTypeKey(typeKey);
      }

      function setEditorShotMapVisibility(visible) {
            if (!$editorShotMap.length) return;
            $editorShotMap.prop('hidden', !visible);
            $editorShotMap.toggleClass('is-hidden', !visible);
      }

      function initEditorShotMap() {
            if (!$editorShotMap.length) return;
            const originSvg = $editorShotOriginSvg.length ? $editorShotOriginSvg[0] : null;
            const targetSvg = $editorShotTargetSvg.length ? $editorShotTargetSvg[0] : null;

            if (originSvg && originSvg.childNodes.length === 0 && typeof window.renderShotOriginSvg === 'function') {
                  window.renderShotOriginSvg(originSvg);
            }
            if (targetSvg && targetSvg.childNodes.length === 0 && typeof window.renderShotTargetSvg === 'function') {
                  window.renderShotTargetSvg(targetSvg);
            }
            if (originSvg && typeof window.attachSinglePointHandler === 'function') {
                  window.attachSinglePointHandler(originSvg, 'origin');
            }
            if (targetSvg && typeof window.attachSinglePointHandler === 'function') {
                  window.attachSinglePointHandler(targetSvg, 'target');
            }
      }

      function updateEditorShotMapForType(typeKey) {
            const show = shouldShowEditorShotMap(typeKey);
            setEditorShotMapVisibility(show);
            if (show) {
                  initEditorShotMap();
            }
      }

      function isCardTypeKey(key) {
            if (!key) return false;
            return CARD_EVENT_KEYS.has(String(key).toLowerCase());
      }

      function isOffsideTypeKey(key) {
            if (!key) return false;
            return OFFSIDE_EVENT_KEYS.has(String(key).toLowerCase());
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
            $goalPlayerList.html(buildPlayerOptionsHtml('goal-player-option', '', currentTeam));
      }

      function openGoalPlayerModal(payload, label) {
            if (!$goalPlayerModal.length) return;
            if (!players.length) {
                  showError('No players available', 'Add match players before logging goals');
                  return;
            }
            goalModalState.payload = { ...payload };
            goalModalState.label = label || 'Goal';
            const session = getDeskSession();
            goalModalState.wasPlaying = session ? session.isPlaying() : !!($video.length && !$video[0].paused);
            pausePlayback('goal_modal');
            renderGoalPlayerList(); // Always update player list to match current team
            setGoalPlayerOptionsEnabled(true);
            // Show shot info if available from last shot
            if (window.lastShotInfo && ($goalPlayerModal.find('#goalShotInfo').length)) {
                  const infoDiv = $goalPlayerModal.find('#goalShotInfo');
                  let html = '';
                  if (window.lastShotInfo.origin) {
                        html += `<div><strong>Shot taken from:</strong> ${window.lastShotInfo.origin}</div>`;
                  }
                  if (window.lastShotInfo.target) {
                        html += `<div><strong>Shot target:</strong> ${window.lastShotInfo.target}</div>`;
                  }
                  if (html) {
                        infoDiv.html(html).show();
                  } else {
                        infoDiv.hide();
                  }
            }
            $goalPlayerModal.removeAttr('hidden').attr('aria-hidden', 'false').addClass('is-active');

            // --- Goal SVG rendering integration (mirrors shot recorder) ---
            setTimeout(function () {
                  const goalOriginSvg = document.getElementById('goalOriginSvg');
                  const goalTargetSvg = document.getElementById('goalTargetSvg');

                  if (goalOriginSvg && goalOriginSvg.childNodes.length === 0 && typeof window.renderShotOriginSvg === 'function') {
                        window.renderShotOriginSvg(goalOriginSvg);
                  }
                  if (goalTargetSvg && goalTargetSvg.childNodes.length === 0 && typeof window.renderShotTargetSvg === 'function') {
                        window.renderShotTargetSvg(goalTargetSvg);
                  }

                  if (goalOriginSvg && typeof window.attachSinglePointHandler === 'function') {
                        window.attachSinglePointHandler(goalOriginSvg, 'origin');
                  }
                  if (goalTargetSvg && typeof window.attachSinglePointHandler === 'function') {
                        window.attachSinglePointHandler(goalTargetSvg, 'target');
                  }

                  const originPoint = (payload.shot_origin_x !== null && payload.shot_origin_x !== undefined
                        && payload.shot_origin_y !== null && payload.shot_origin_y !== undefined)
                        ? { x: Number(payload.shot_origin_x), y: Number(payload.shot_origin_y) }
                        : null;

                  const targetPoint = (payload.shot_target_x !== null && payload.shot_target_x !== undefined
                        && payload.shot_target_y !== null && payload.shot_target_y !== undefined)
                        ? { x: Number(payload.shot_target_x), y: Number(payload.shot_target_y) }
                        : null;

                  if (originPoint) {
                        if (typeof window.setShotPointState === 'function') {
                              window.setShotPointState('origin', originPoint);
                        } else {
                              window.shotOriginPoint = originPoint;
                              window.shotOriginCleared = false;
                        }
                        if (typeof window.renderShotPoint === 'function') {
                              window.renderShotPoint('origin', originPoint);
                        }
                  } else {
                        if (typeof window.clearShotPointState === 'function') {
                              window.clearShotPointState('origin');
                        } else {
                              window.shotOriginPoint = null;
                              window.shotOriginCleared = false;
                        }
                        if (typeof window.renderShotPoint === 'function') {
                              window.renderShotPoint('origin', null);
                        }
                  }

                  if (targetPoint) {
                        if (typeof window.setShotPointState === 'function') {
                              window.setShotPointState('target', targetPoint);
                        } else {
                              window.shotTargetPoint = targetPoint;
                              window.shotTargetCleared = false;
                        }
                        if (typeof window.renderShotPoint === 'function') {
                              window.renderShotPoint('target', targetPoint);
                        }
                  } else {
                        if (typeof window.clearShotPointState === 'function') {
                              window.clearShotPointState('target');
                        } else {
                              window.shotTargetPoint = null;
                              window.shotTargetCleared = false;
                        }
                        if (typeof window.renderShotPoint === 'function') {
                              window.renderShotPoint('target', null);
                        }
                  }
            }, 0);
      }

      function closeGoalPlayerModal() {
            if (!$goalPlayerModal.length) return;
            const wasPlaying = goalModalState.wasPlaying;
            goalModalState.payload = null;
            goalModalState.label = '';
            goalModalState.wasPlaying = false;
            setGoalPlayerOptionsEnabled(true);
            $goalPlayerModal.attr('aria-hidden', 'true').attr('hidden', 'hidden').removeClass('is-active');
            resumePlaybackIfNeeded(wasPlaying, 'goal_modal');
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
            const payloadToSend = { ...payload };
            Object.assign(payloadToSend, buildShotLocationPayload());
            $.post(url, payloadToSend)
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
                                    payloadToSend.match_second,
                                    payloadToSend.minute_extra || 0
                              )}`
                        );
                        setStatus('Tagged');
                        loadEvents(true); // Force reload from API so timeline updates immediately
                        refreshSummaryPanel(); // Refresh summary stats
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
            $cardPlayerList.html(buildPlayerOptionsHtml('goal-player-option', '', currentTeam));
      }

      function openCardPlayerModal(payload, label) {
            if (!$cardPlayerModal.length) return;
            if (!players.length) {
                  showError('No players available', 'Add match players before logging cards');
                  return;
            }
            cardModalState.payload = { ...payload };
            cardModalState.label = label || 'Card';
            const session = getDeskSession();
            cardModalState.wasPlaying = session ? session.isPlaying() : !!($video.length && !$video[0].paused);
            pausePlayback('card_modal');
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
            resumePlaybackIfNeeded(wasPlaying, 'card_modal');
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
                        loadEvents(true); // Force reload from API so timeline updates immediately
                        refreshSummaryPanel(); // Refresh summary stats
                        closeCardPlayerModal();
                  })
                  .fail((xhr, status, error) => {
                        showError('Save failed', xhr.responseText || error || status);
                        setCardPlayerOptionsEnabled(true);
                  });
      }

      function setOffsidePlayerOptionsEnabled(enabled = true) {
            if (!$offsidePlayerModal.length) return;
            if ($offsidePlayerList.length) {
                  $offsidePlayerList.find('.goal-player-option').prop('disabled', !enabled);
            }
            $offsidePlayerModal.find('[data-offside-unknown]').prop('disabled', !enabled);
      }

      function renderOffsidePlayerList() {
            if (!$offsidePlayerList.length) return;
            $offsidePlayerList.html(buildPlayerOptionsHtml('goal-player-option', '', currentTeam));
      }

      function openOffsidePlayerModal(payload, label) {
            if (!$offsidePlayerModal.length) return;
            if (!players.length) {
                  showError('No players available', 'Add match players before logging offsides');
                  return;
            }
            offsideModalState.payload = { ...payload };
            offsideModalState.label = label || 'Offside';
            const session = getDeskSession();
            offsideModalState.wasPlaying = session ? session.isPlaying() : !!($video.length && !$video[0].paused);
            pausePlayback('offside_modal');
            renderOffsidePlayerList();
            setOffsidePlayerOptionsEnabled(true);
            $offsidePlayerModal.removeAttr('hidden').attr('aria-hidden', 'false').addClass('is-active');
      }

      function closeOffsidePlayerModal() {
            if (!$offsidePlayerModal.length) return;
            const wasPlaying = offsideModalState.wasPlaying;
            offsideModalState.payload = null;
            offsideModalState.label = '';
            offsideModalState.wasPlaying = false;
            setOffsidePlayerOptionsEnabled(true);
            $offsidePlayerModal.attr('aria-hidden', 'true').attr('hidden', 'hidden').removeClass('is-active');
            resumePlaybackIfNeeded(wasPlaying, 'offside_modal');
      }

      function handleOffsidePlayerSelection(event) {
            const $btn = $(event.currentTarget);
            if (!offsideModalState.payload) return;
            const playerId = $btn.data('player-id');
            if (!playerId) return;
            setOffsidePlayerOptionsEnabled(false);
            const payload = {
                  ...offsideModalState.payload,
                  match_player_id: playerId,
            };
            sendOffsideEventRequest(payload, offsideModalState.label);
      }

      function handleOffsideUnknownClick(event) {
            event.preventDefault();
            if (!offsideModalState.payload) return;
            setOffsidePlayerOptionsEnabled(false);
            const payload = {
                  ...offsideModalState.payload,
                  match_player_id: null,
            };
            sendOffsideEventRequest(payload, offsideModalState.label);
      }

      function sendOffsideEventRequest(payload, label) {
            const url = endpoint('eventCreate');
            if (!url) {
                  showError('Save failed', 'Missing event endpoint');
                  setOffsidePlayerOptionsEnabled(true);
                  return;
            }
            $.post(url, payload)
                  .done((res) => {
                        if (!res.ok) {
                              showError('Save failed', res.error || 'Unknown');
                              setOffsidePlayerOptionsEnabled(true);
                              return;
                        }
                        hideError();
                        syncUndoRedoFromMeta(res.meta);
                        selectedId = null;
                        setEditorCollapsed(true, 'Click a timeline item to edit details', true);
                        showToast(
                              `${label || 'Offside'} tagged at ${formatMatchSecondWithExtra(
                                    payload.match_second,
                                    payload.minute_extra || 0
                              )}`
                        );
                        setStatus('Tagged');
                        loadEvents(true); // Force reload from API so timeline updates immediately
                        refreshSummaryPanel(); // Refresh summary stats
                        closeOffsidePlayerModal();
                  })
                  .fail((xhr, status, error) => {
                        showError('Save failed', xhr.responseText || error || status);
                        setOffsidePlayerOptionsEnabled(true);
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

      function setShotResultSelection(result) {
            const normalized = result ? String(result) : null;
            shotModalState.selectedResult = normalized;
            if (!$shotPlayerModal.length) return;
            $shotPlayerModal
                  .find('.shot-result-btn')
                  .each((_, btn) => {
                        const $btn = $(btn);
                        $btn.toggleClass('is-active', $btn.data('shot-result') === normalized);
                  });
            hideError();
      }

      function setEditorOutcomeSelection(outcome) {
            const normalized = outcome ? String(outcome) : '';
            if (!$editorPanel.length || !$outcome.length) return;
            $outcome.val(normalized);
            $editorPanel.find('.outcome-selector-btn').removeClass('selected');
            if (normalized) {
                  $editorPanel.find(`.outcome-selector-btn[data-outcome="${normalized}"]`).addClass('selected');
            }
      }

      function renderShotPlayerList() {
            if (!$shotPlayerList.length) return;
            $shotPlayerList.html(buildPlayerOptionsHtml('goal-player-option', 'shot-player-option', currentTeam));
            setShotPlayerSelection(null);
      }

      function setShotModalControlsEnabled(enabled = true) {
            if (!$shotPlayerModal.length) return;
            $shotPlayerModal.find('.shot-player-option').prop('disabled', !enabled);
            $shotPlayerModal.find('[data-shot-outcome]').prop('disabled', !enabled);
            $shotPlayerModal.find('[data-shot-result]').prop('disabled', !enabled);
            $shotPlayerModal.find('[data-shot-unknown]').prop('disabled', !enabled);
      }

      function openShotPlayerModal(payload, label) {
            if (!$shotPlayerModal.length) return;
            if (!players.length) {
                  showError('No players available', 'Add match players before logging shots');
                  return;
            }
            shotModalState.payload = { ...payload };
            shotModalState.baseLabel = label || 'Shot';
            shotModalState.label = shotModalState.baseLabel;
            const session = getDeskSession();
            shotModalState.wasPlaying = session ? session.isPlaying() : !!($video.length && !$video[0].paused);
            pausePlayback('shot_modal');
            renderShotPlayerList();
            setShotOutcomeSelection(null);
            setShotResultSelection(null);
            setShotModalControlsEnabled(true);
            $shotPlayerModal.removeAttr('hidden').attr('aria-hidden', 'false').addClass('is-active');
            // --- Shot SVG rendering integration ---
            setTimeout(function () {
                  var shotOriginSvg = document.getElementById('shotOriginSvg');
                  var shotTargetSvg = document.getElementById('shotTargetSvg');

                  if (shotOriginSvg && shotOriginSvg.childNodes.length === 0 && typeof window.renderShotOriginSvg === 'function') {
                        window.renderShotOriginSvg(shotOriginSvg);
                  }
                  if (shotTargetSvg && shotTargetSvg.childNodes.length === 0 && typeof window.renderShotTargetSvg === 'function') {
                        window.renderShotTargetSvg(shotTargetSvg);
                  }

                  if (shotOriginSvg && typeof window.attachSinglePointHandler === 'function') {
                        window.attachSinglePointHandler(shotOriginSvg, 'origin');
                  }
                  if (shotTargetSvg && typeof window.attachSinglePointHandler === 'function') {
                        window.attachSinglePointHandler(shotTargetSvg, 'target');
                  }

                  const originPoint = (payload.shot_origin_x !== null && payload.shot_origin_x !== undefined
                        && payload.shot_origin_y !== null && payload.shot_origin_y !== undefined)
                        ? { x: Number(payload.shot_origin_x), y: Number(payload.shot_origin_y) }
                        : null;

                  const targetPoint = (payload.shot_target_x !== null && payload.shot_target_x !== undefined
                        && payload.shot_target_y !== null && payload.shot_target_y !== undefined)
                        ? { x: Number(payload.shot_target_x), y: Number(payload.shot_target_y) }
                        : null;

                  if (originPoint) {
                        if (typeof window.setShotPointState === 'function') {
                              window.setShotPointState('origin', originPoint);
                        } else {
                              window.shotOriginPoint = originPoint;
                              window.shotOriginCleared = false;
                        }
                        if (typeof window.renderShotPoint === 'function') {
                              window.renderShotPoint('origin', originPoint);
                        }
                  } else {
                        window.shotOriginPoint = null;
                        window.shotOriginCleared = false;
                        if (typeof window.renderShotPoint === 'function') {
                              window.renderShotPoint('origin', null);
                        }
                  }

                  if (targetPoint) {
                        if (typeof window.setShotPointState === 'function') {
                              window.setShotPointState('target', targetPoint);
                        } else {
                              window.shotTargetPoint = targetPoint;
                              window.shotTargetCleared = false;
                        }
                        if (typeof window.renderShotPoint === 'function') {
                              window.renderShotPoint('target', targetPoint);
                        }
                  } else {
                        window.shotTargetPoint = null;
                        window.shotTargetCleared = false;
                        if (typeof window.renderShotPoint === 'function') {
                              window.renderShotPoint('target', null);
                        }
                  }
            }, 0);
      }

      function closeShotPlayerModal() {
            if (!$shotPlayerModal.length) return;
            const wasPlaying = shotModalState.wasPlaying;
            shotModalState.payload = null;
            shotModalState.label = '';
            shotModalState.baseLabel = '';
            shotModalState.selectedPlayerId = null;
            shotModalState.wasPlaying = false;
            setShotModalControlsEnabled(true);
            setShotPlayerSelection(null);
            setShotOutcomeSelection(null);
            setShotResultSelection(null);
            $shotPlayerModal.attr('aria-hidden', 'true').attr('hidden', 'hidden').removeClass('is-active');
            resumePlaybackIfNeeded(wasPlaying, 'shot_modal');
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
            const resolvedOutcome = shotModalState.selectedResult === 'goal' ? 'on_target' : outcome;
            const payload = {
                  ...shotModalState.payload,
                  outcome: resolvedOutcome,
            };
            if (shotModalState.selectedResult === 'goal') {
                  payload.event_type_id = GOAL_EVENT_TYPE_ID;
                  payload.event_type_key = 'goal';
            }
            if (playerId) {
                  payload.match_player_id = playerId;
            }
            Object.assign(payload, buildShotLocationPayload());
            // Store shot info for use in goal modal
            window.lastShotInfo = {
                  origin: shotModalState.payload.shotOriginLabel || '',
                  target: shotModalState.payload.shotTargetLabel || ''
            };
            sendShotEventRequest(payload, shotModalState.label, payload.outcome);
      }

      function buildShotLocationPayload() {
            const payload = {};
            const origin = window.shotOriginPoint;
            const target = window.shotTargetPoint;

            if (origin && typeof origin.x === 'number' && typeof origin.y === 'number') {
                  payload.shot_origin_x = origin.x;
                  payload.shot_origin_y = origin.y;
            } else if (window.shotOriginCleared) {
                  payload.shot_origin_x = null;
                  payload.shot_origin_y = null;
            }

            if (target && typeof target.x === 'number' && typeof target.y === 'number') {
                  payload.shot_target_x = target.x;
                  payload.shot_target_y = target.y;
            } else if (window.shotTargetCleared) {
                  payload.shot_target_x = null;
                  payload.shot_target_y = null;
            }

            return payload;
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

      function handleShotResultClick(event) {
            event.preventDefault();
            const result = $(event.currentTarget).data('shot-result');
            if (!shotModalState.payload || !result) return;
            setShotResultSelection(result);
            if (result === 'goal') {
                  setShotOutcomeSelection('on_target');
                  shotModalState.label = 'Goal';
            } else {
                  shotModalState.label = shotModalState.baseLabel || 'Shot';
            }
      }

      function autoSelectShotOutcomeFromTarget(point) {
            if (!point || typeof point.x !== 'number' || typeof point.y !== 'number') return;
            const onTarget = point.x >= (20 / 120)
                  && point.x <= (100 / 120)
                  && point.y >= (10 / 60)
                  && point.y <= (50 / 60);
            const outcome = onTarget ? 'on_target' : 'off_target';
            setShotOutcomeSelection(outcome);
            return outcome;
      }

      document.addEventListener('shot-point-updated', (event) => {
            const detail = event && event.detail ? event.detail : null;
            if (!detail || detail.type !== 'target' || detail.source !== 'click') return;
            const outcome = autoSelectShotOutcomeFromTarget(detail.point);
            if (!outcome) return;
            if ($shotPlayerModal.length && $shotPlayerModal.hasClass('is-active')) {
                  return;
            }
            if ($editorPanel.length && !$editorPanel.hasClass('is-hidden') && $editorShotMap.length && !$editorShotMap.prop('hidden')) {
                  setEditorOutcomeSelection(outcome);
            }
      });

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
            if (payload.shot_origin_x !== undefined || payload.shot_target_x !== undefined) {
                  console.debug('[ShotSVG] Shot payload', {
                        shot_origin_x: payload.shot_origin_x,
                        shot_origin_y: payload.shot_origin_y,
                        shot_target_x: payload.shot_target_x,
                        shot_target_y: payload.shot_target_y,
                  });
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
                        loadEvents(true); // Force reload from API so timeline updates immediately
                        refreshSummaryPanel(); // Refresh summary stats
                        closeShotPlayerModal();
                  })
                  .fail((xhr, status, error) => {
                        showError('Save failed', xhr.responseText || error || status);
                        setShotModalControlsEnabled(true);
                  });
      }

      // Accepts optional videoSecond argument for precise event timing
      function quickTag(typeKey, typeId, $btn, videoSecond) {
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
            // Use provided videoSecond if available, else fallback
            const currentSecond = (typeof videoSecond === 'number' && Number.isFinite(videoSecond)) ? videoSecond : getCurrentVideoSecond();
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
            if (isOffsideTypeKey(key)) {
                  openOffsidePlayerModal(payload, quickTagLabel);
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
                        // Force reload from API, not embedded config, so timeline updates immediately
                        loadEvents(true);
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
      function loadEvents(forceApi = false) {
            // If forceApi is true, always fetch from API. Otherwise, use embedded events for first paint only.
            if (!forceApi && Array.isArray(cfg.events)) {
                  setDeskEvents(applyEventLabelReplacements(cfg.events));
                  refreshPeriodStateFromEvents();
                  // meta and selectedId are not available from embedded, so skip those
                  renderTimeline();
                  if (selectedId) selectEvent(selectedId);
                  return;
            }
            // Always fetch from API if forceApi is true
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
                        setDeskEvents(applyEventLabelReplacements(res.events || []));
                        refreshPeriodStateFromEvents();
                        syncUndoRedoFromMeta(res.meta);
                        renderTimeline();
                        if (selectedId) selectEvent(selectedId);
                  })
                  .fail((xhr, status, error) => showError('Events load failed', xhr.responseText || error || status));
      }

      function refreshSummaryPanel() {
            // Reload the summary panel by fetching fresh data from the server
            const matchId = cfg.matchId;
            if (!matchId) {
                  console.warn('refreshSummaryPanel: No matchId available');
                  return;
            }

            const url = `${cfg.basePath}/api/stats/match/summary?match_id=${matchId}`;
            $.getJSON(url)
                  .done((res) => {
                        if (res && res.ok && res.html) {
                              const $summaryContent = $('.desk-summary-content');
                              if ($summaryContent.length) {
                                    $summaryContent.html(res.html);
                              } else {
                                    console.warn('refreshSummaryPanel: .desk-summary-content not found in DOM');
                              }
                        } else {
                              console.warn('Summary API response missing expected fields:', res);
                        }
                  })
                  .fail((xhr, status, error) => {
                        console.error('Summary refresh API call failed:', { status, error, responseText: xhr.responseText });
                  });
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

      function scheduleTimelineRender(delay = 80) {
            if (timelineRenderTimer) {
                  clearTimeout(timelineRenderTimer);
            }
            timelineRenderTimer = setTimeout(() => {
                  timelineRenderTimer = null;
                  renderTimeline();
            }, delay);
      }

      function scheduleInitialTimelineRefresh() {
            if (initialTimelineRefreshScheduled) {
                  return;
            }
            initialTimelineRefreshScheduled = true;
            setTimeout(() => renderTimeline(), 50);
            if (typeof window.requestAnimationFrame === 'function') {
                  window.requestAnimationFrame(() => {
                        $(window).trigger('resize');
                  });
            } else {
                  setTimeout(() => {
                        $(window).trigger('resize');
                  }, 0);
            }
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
            if (hasCanonicalPeriods && events && events.length) {
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
            scheduleInitialTimelineRefresh();
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
            const buckets = [];
            // Use fallback duration if videoDurationSeconds is not set
            const fallbackDuration = 3600; // 60 min default
            const videoDuration = Number.isFinite(videoDurationSeconds) && videoDurationSeconds > 0 ? videoDurationSeconds : fallbackDuration;
            const maxBucketMinute = Math.ceil(videoDuration / 60 / 5) * 5;
            for (let i = 0; i <= maxBucketMinute; i += 5) {
                  if (i === 50 || i === 55) continue; // Hide 50 and 55
                  let start = i * 60;
                  let end = (i + 5) * 60;
                  let label = String(i);
                  if (i === maxBucketMinute) {
                        end = null;
                  }
                  buckets.push({ label, start, end });
            }
            const axisPad = axisOffset();
            const baseDuration = 6000;
            const resolvePeriodKey = (second) => {
                  const state = typeof periodState !== 'undefined' ? periodState : window.DeskPeriodState;
                  if (!state) return null;
                  const order = ['first_half', 'second_half', 'extra_time_1', 'extra_time_2', 'penalties'];
                  for (const key of order) {
                        const entry = state[key];
                        if (!entry || !Number.isFinite(entry.startMatchSecond)) continue;
                        const start = Number(entry.startMatchSecond);
                        const end = Number(entry.endMatchSecond);
                        if (second >= start && (!Number.isFinite(end) || second <= end)) {
                              return key;
                        }
                  }
                  return null;
            };
            const resolveMatrixPeriodKey = (second) => {
                  const state = typeof periodState !== 'undefined' ? periodState : window.DeskPeriodState;
                  if (!state) return null;
                  const first = state.first_half;
                  const secondHalf = state.second_half;
                  const extra1 = state.extra_time_1;
                  const firstStart = Number(first && first.startMatchSecond);
                  const firstEnd = Number(first && first.endMatchSecond);
                  const secondStart = Number(secondHalf && secondHalf.startMatchSecond);
                  const secondEnd = Number(secondHalf && secondHalf.endMatchSecond);
                  const extraStart = Number(extra1 && extra1.startMatchSecond);
                  if (Number.isFinite(firstStart) && second < firstStart) {
                        return 'pre_first_half';
                  }
                  if (Number.isFinite(firstEnd) && Number.isFinite(secondStart) && second >= firstEnd && second < secondStart) {
                        return 'half_time';
                  }
                  if (Number.isFinite(secondEnd) && second >= secondEnd && (!Number.isFinite(extraStart) || second < extraStart)) {
                        return 'post_second_half';
                  }
                  return resolvePeriodKey(second);
            };

            if (!filtered.length) {
                  timelineMetrics.duration = baseDuration;
                  timelineMetrics.totalWidth = axisPad;
                  timelineMetrics.viewportWidth = $timelineMatrix.width() || 0;
                  timelineZoom.scale = Math.min(timelineZoom.max, 1);
                  $timelineMatrix.html('<div class="text-muted-alt text-sm">No events yet.</div>');
                  return;
            }

            const staticTypeOrder = [
                  'goal',
                  'shot',
                  'chance',
                  'penalty',
                  'corner',
                  'free_kick',
                  'offside',
                  'foul',
                  'yellow_card',
                  'red_card',
                  'mistake',
                  'turnover',
                  'good_play',
                  'highlight',
                  'other',
            ];
            const normalizeKey = (value) => (value || '').toLowerCase().replace(/[_\s]/g, '');
            const orderIndex = new Map(staticTypeOrder.map((key, idx) => [normalizeKey(key), idx]));

            const rowMap = new Map();
            (cfg.eventTypes || []).forEach((type) => {
                  if (!type || !type.id) return;
                  const label = type.label || type.type_key || 'Event';
                  rowMap.set(String(type.id), {
                        id: type.id,
                        label,
                        events: [],
                        typeKey: type.type_key || '',
                  });
            });

            (filtered || []).forEach((ev) => {
                  if (isPeriodEvent(ev)) {
                        return;
                  }
                  const typeId = ev.event_type_id ? String(ev.event_type_id) : '';
                  let row = typeId ? rowMap.get(typeId) : null;
                  if (!row) {
                        const fallbackKey = normalizeKey(ev.event_type_key || ev.event_type_label || '');
                        const mapped = eventTypeKeyMap[fallbackKey] || eventTypeKeyMap[normalizeKey(ev.event_type_key || '')] || null;
                        if (mapped && mapped.id) {
                              row = rowMap.get(String(mapped.id)) || null;
                        }
                  }
                  if (!row) {
                        // If we still can't resolve, skip to avoid introducing a new type row.
                        return;
                  }
                  row.events.push(ev);
            });

            const typeRows = Array.from(rowMap.values()).sort((a, b) => {
                  const aKey = normalizeKey(a.typeKey || a.label);
                  const bKey = normalizeKey(b.typeKey || b.label);
                  const aIdx = orderIndex.has(aKey) ? orderIndex.get(aKey) : Number.MAX_SAFE_INTEGER;
                  const bIdx = orderIndex.has(bKey) ? orderIndex.get(bKey) : Number.MAX_SAFE_INTEGER;
                  if (aIdx !== bIdx) return aIdx - bIdx;
                  return String(a.label).localeCompare(String(b.label));
            }).filter((row) => (row.events || []).length > 0);
            // Build periodMarkers from DeskConfig.periods (all period boundaries)
            let periodMarkers = [];
            if (Array.isArray(cfg.periods) && cfg.periods.length > 0) {
                  cfg.periods.forEach((period, idx) => {
                        // Add marker for start (except very first if undesired)
                        if (idx > 0) {
                              periodMarkers.push({
                                    id: `period_${period.id}_start`,
                                    label: `${period.label} Start`,
                                    second: Number(period.start_second) || 0,
                                    edge: 'start',
                              });
                        } else {
                              // Optionally include first period start marker
                              periodMarkers.push({
                                    id: `period_${period.id}_start`,
                                    label: `${period.label} Start`,
                                    second: Number(period.start_second) || 0,
                                    edge: 'start',
                              });
                        }
                        // Always add marker for end
                        periodMarkers.push({
                              id: `period_${period.id}_end`,
                              label: `${period.label} End`,
                              second: Number(period.end_second) || 0,
                              edge: 'end',
                        });
                  });
                  periodMarkers = periodMarkers.filter(m => Number.isFinite(m.second)).sort((a, b) => a.second - b.second);
            } else if (hasCanonicalPeriods) {
                  // fallback to old event-based logic if periods missing
                  periodMarkers = (filtered || [])
                        .filter(
                              (ev) =>
                                    (ev.event_type_key === 'period_start' || ev.event_type_key === 'period_end') &&
                                    ev.match_second !== null &&
                                    ev.match_second !== undefined
                        )
                        .map((ev) => ({
                              id: ev.id,
                              label: displayEventLabel(ev, ev.event_type_label || 'Period'),
                              second: Number(ev.match_second) || 0,
                              edge: ev.event_type_key === 'period_start' ? 'start' : 'end',
                        }))
                        .sort((a, b) => a.second - b.second);
            } else {
                  periodMarkers = [];
            }

            const markerPeriodKey = (label) => {
                  const normalized = normalizePeriodLabel(label)
                        .replace(/\b(start|end)\b/g, '')
                        .replace(/\s+/g, ' ')
                        .trim();
                  if (!normalized) return null;
                  return periodLabelToKey.get(normalized) || normalized.replace(/[^a-z0-9]+/g, '_');
            };

            let firstHalfEndSecond = null;
            if (typeof periodState !== 'undefined' && periodState && periodState.first_half) {
                  const endSecond = Number(periodState.first_half.endMatchSecond);
                  if (Number.isFinite(endSecond)) {
                        firstHalfEndSecond = endSecond;
                  }
            }

            if (!Number.isFinite(firstHalfEndSecond) && hasCanonicalPeriods) {
                  (filtered || []).forEach((ev) => {
                        const key = canonicalizePeriodKey(ev.period_key, ev.period_label);
                        if (key !== 'first_half') return;
                        const seconds = Number(ev.match_second);
                        if (!Number.isFinite(seconds)) return;
                        if (!Number.isFinite(firstHalfEndSecond) || seconds > firstHalfEndSecond) {
                              firstHalfEndSecond = seconds;
                        }
                  });
            }

            if (Number.isFinite(firstHalfEndSecond) && hasCanonicalPeriods) {
                  const halfLabel = (periodState && periodState.first_half && periodState.first_half.label)
                        ? periodState.first_half.label
                        : 'First Half';
                  const halfMarkerLabel = `${halfLabel} End`;
                  const existingIndex = periodMarkers.findIndex(
                        (marker) => marker.edge === 'end' && markerPeriodKey(marker.label) === 'first_half'
                  );
                  if (existingIndex >= 0) {
                        periodMarkers[existingIndex].second = firstHalfEndSecond;
                        periodMarkers[existingIndex].label = halfMarkerLabel;
                  } else {
                        periodMarkers.push({
                              id: 'half_time_divider',
                              label: halfMarkerLabel,
                              second: firstHalfEndSecond,
                              edge: 'end',
                        });
                  }
                  periodMarkers.sort((a, b) => a.second - b.second);
            }
            const breaks = [];
            const breakGapSeconds = 60;
            const addBreak = (start, end) => {
                  const safeStart = Number(start);
                  const safeEnd = Number(end);
                  if (!Number.isFinite(safeStart) || !Number.isFinite(safeEnd)) return;
                  if (safeEnd <= safeStart) return;
                  const gap = safeEnd - safeStart;
                  if (gap <= breakGapSeconds) return;
                  breaks.push({ start: safeStart, end: safeEnd, gap: breakGapSeconds });
            };
            if (hasCanonicalPeriods) {
                  for (let i = 0; i < periodMarkers.length - 1; i += 1) {
                        const current = periodMarkers[i];
                        const next = periodMarkers[i + 1];
                        if (current.edge === 'end' && next.edge === 'start') {
                              addBreak(current.second, next.second);
                        }
                  }
                  const state = typeof periodState !== 'undefined' ? periodState : window.DeskPeriodState;
                  if (state) {
                        for (let i = 0; i < PERIOD_SEQUENCE.length - 1; i += 1) {
                              const current = state[PERIOD_SEQUENCE[i]];
                              const next = state[PERIOD_SEQUENCE[i + 1]];
                              if (!current || !next) continue;
                              addBreak(current.endMatchSecond, next.startMatchSecond);
                        }
                  }
            }
            if (breaks.length > 1) {
                  const seen = new Set();
                  const deduped = [];
                  breaks.forEach((br) => {
                        const key = `${br.start}|${br.end}`;
                        if (seen.has(key)) return;
                        seen.add(key);
                        deduped.push(br);
                  });
                  breaks.length = 0;
                  deduped.sort((a, b) => a.start - b.start).forEach((br) => breaks.push(br));
            }
            const mapSecond = (seconds) => {
                  const normalized = Number(seconds) || 0;
                  let mapped = normalized;
                  breaks.forEach((br) => {
                        if (normalized <= br.start) {
                              return;
                        }
                        const windowEnd = Math.min(normalized, br.end);
                        const over = windowEnd - br.start;
                        if (over > br.gap) {
                              mapped -= over - br.gap;
                        }
                  });
                  return mapped;
            };
            timelineMetrics.mapSecond = mapSecond;
            const clipRanges = buildClipRanges(filtered);
            const maxClipSecond = clipRanges.reduce((max, clip) => Math.max(max, clip.end || 0), 0);
            const maxAnnotationSecond = timelineAnnotations.reduce((max, annotation) => {
                  const seconds = Number(annotation.timestamp_second);
                  return Number.isFinite(seconds) ? Math.max(max, seconds) : max;
            }, 0);
            const maxEventSecond = filtered.reduce((max, ev) => Math.max(max, ev.match_second || 0), 0);
            const maxMarkerSecond = Math.max(maxEventSecond, maxClipSecond, maxAnnotationSecond);
            const minDuration = videoDuration > 0 ? 0 : baseDuration;
            const rawDuration = Math.max(minDuration, maxMarkerSecond, videoDuration);
            // No need to add extra buckets beyond maxBucketMinute
            const mappedBase = mapSecond(baseDuration);
            timelineMetrics.duration = Math.max(mappedBase, mapSecond(rawDuration));
            const containerWidth = $timelineMatrix.closest('.timeline-scroll').width() || $timelineMatrix.width() || 0;
            const availableWidth = Math.max(0, containerWidth - axisPad);
            const baseWidth = timelineMetrics.duration * timelineZoom.pixelsPerSecond;
            const safeFit = 1; // keep true scale; allow horizontal scrolling instead of auto-fitting
            const minBucketWidth = 100;
            let requiredScale = 1;
            buckets.forEach((bucket) => {
                  const bucketEnd = bucket.end === null ? rawDuration : bucket.end;
                  const startDisplay = mapSecond(bucket.start);
                  const endDisplay = Math.min(mapSecond(bucketEnd), timelineMetrics.duration);
                  const displaySpan = Math.max(0, endDisplay - startDisplay);
                  if (!displaySpan) return;
                  const needed = minBucketWidth / (displaySpan * timelineZoom.pixelsPerSecond);
                  if (needed > requiredScale) {
                        requiredScale = needed;
                  }
            });
            const widthReductionFactor = 0.3; // reduce overall timeline width by ~70%
            const targetScale = Math.max(safeFit, requiredScale) * widthReductionFactor;
            timelineZoom.scale = Math.min(timelineZoom.max, targetScale);
            const timelineWidth = timelineMetrics.duration * timelineZoom.pixelsPerSecond * timelineZoom.scale;
            // Calculate bucket positions and widths using mapped seconds for perfect alignment
            // --- DEMO: Show 50 and 55 columns in viewport ---
            // To toggle, set desiredBucketCount to 50 or 55
            const desiredBucketCount = 50; // Change to 55 to show 55 columns
            const numBuckets = desiredBucketCount;
            const equalBucketWidth = timelineWidth / numBuckets;
            const bucketWidths = Array(numBuckets).fill(equalBucketWidth);
            const bucketColumns = bucketWidths.map((width) => `${width}px`).join(' ');
            const gridColumnsStyle = `grid-template-columns: ${MATRIX_TYPE_WIDTH}px ${timelineWidth}px;`;
            // For label positioning, also update bucketRects
            const bucketRects = bucketWidths.map((width, idx) => {
                  const left = idx * equalBucketWidth;
                  return { left, width };
            });

            const markerOffset = MATRIX_TYPE_WIDTH + MATRIX_GAP;
            let html = `<div class="matrix-viewport" data-scale="${timelineZoom.scale}">`;
            if (periodMarkers.length) {
                  html += `<div class="matrix-period-markers" style="left:${markerOffset}px; width:${timelineWidth}px">`;
                  periodMarkers.forEach((marker) => {
                        const displaySecond = timelineMetrics.mapSecond ? timelineMetrics.mapSecond(marker.second) : marker.second;
                        const position = Math.min(Math.max(0, displaySecond * timelineZoom.pixelsPerSecond * timelineZoom.scale), timelineWidth);
                        // Short label logic
                        let shortLabel = '';
                        if (marker.label.toLowerCase().includes('first')) {
                              shortLabel = marker.edge === 'start' ? 'First_half start' : 'First_half end';
                        } else if (marker.label.toLowerCase().includes('second')) {
                              shortLabel = marker.edge === 'start' ? 'Second_half start' : 'Second_half end';
                        } else {
                              shortLabel = marker.label;
                        }
                        // Find period info for tooltip
                        let tooltipLabel = '';
                        let periodInfo = null;
                        if (Array.isArray(cfg.periods)) {
                              periodInfo = cfg.periods.find(p => {
                                    // Match by start or end second and label
                                    return (
                                          (marker.edge === 'start' && Number(p.start_second) === marker.second) ||
                                          (marker.edge === 'end' && Number(p.end_second) === marker.second)
                                    );
                              });
                        }
                        if (periodInfo) {
                              const prettyLabel = periodInfo.label || '';
                              const startTime = formatMatchSecond(periodInfo.start_second).text;
                              const endTime = formatMatchSecond(periodInfo.end_second).text;
                              tooltipLabel = `${prettyLabel}\nStart: ${startTime}\nFinish: ${endTime}`;
                        } else {
                              tooltipLabel = shortLabel;
                        }
                        html += `<button type="button" class="matrix-period-marker" data-period-id="${marker.id}" data-period-edge="${marker.edge}" data-period-label="${h(shortLabel)}" data-second="${marker.second}" style="left:${position}px">`;
                        html += `<span class="matrix-period-marker-line"></span>`;
                        html += `<span class="matrix-period-marker-tooltip">${h(tooltipLabel)}</span>`;
                        html += '</button>';
                        // Tooltip show/hide for matrix-period-marker
                        $timelineMatrix.off('mouseenter mouseleave focus blur', '.matrix-period-marker');
                        $timelineMatrix.on('mouseenter focus', '.matrix-period-marker', function () {
                              $(this).addClass('show-tooltip');
                        });
                        $timelineMatrix.on('mouseleave blur', '.matrix-period-marker', function () {
                              $(this).removeClass('show-tooltip');
                        });
                  });
                  html += '</div>';
            }
            html += `<div class="matrix-bucket-row" style="${gridColumnsStyle};position:relative;">`;
            html += `<div class="matrix-type-spacer" style="width:${MATRIX_TYPE_WIDTH}px;position:relative;z-index:1;"></div>`;
            html += `<div class="matrix-bucket-labels" style="position:absolute;top:0;left:${axisPad}px;width:${timelineWidth}px;height:30px;">`;
            const bucketLabelMinWidth = 100; // px, adjust as needed for visual consistency
            // For 50/55 buckets, show time range for each bucket
            for (let idx = 0; idx < bucketRects.length; idx++) {
                  const rect = bucketRects[idx];
                  const labelWidth = Math.max(bucketLabelMinWidth, rect.width);
                  const labelLeft = rect.left + Math.max(0, (rect.width - labelWidth) / 2);
                  // Calculate time range for this bucket
                  const bucketStart = (idx / bucketRects.length) * timelineMetrics.duration;
                  const bucketEnd = ((idx + 1) / bucketRects.length) * timelineMetrics.duration;
                  const startLabel = formatMatchSecond(bucketStart).text;
                  const endLabel = formatMatchSecond(bucketEnd).text;
                  const label = `${startLabel} - ${endLabel}`;
                  html += `<div class="matrix-bucket-label" style="min-width:${bucketLabelMinWidth}px;text-align:center;width:${labelWidth}px;left:${labelLeft}px;position:absolute;">${h(label)}</div>`;
            }
            html += '</div>';
            html += '</div>';


            matrixRowClipMap = new Map();
            typeRows.forEach((row) => {
                  // DEBUG: Log row and events for matrixRowClipMap
                  console.log('[MATRIX] Row', row.id, row.label, row.events);
                  const accentColor = eventTypeAccents[String(row.id)] || EVENT_NEUTRAL;
                  const accentStyle = buildColorStyle(accentColor);
                  const rowTypeKey = (row.typeKey || '').toLowerCase();
                  const rowClipIds = Array.from(
                        new Set(
                              (row.events || [])
                                    .map((ev) => getEventClipId(ev))
                                    .filter((clipId) => Number.isFinite(clipId) && clipId > 0)
                                    .map((clipId) => String(clipId))
                        )
                  );
                  matrixRowClipMap.set(String(row.id), rowClipIds);
                  // DEBUG: Log rowClipIds for this row
                  console.log('[MATRIX] matrixRowClipMap.set', String(row.id), rowClipIds);
                  html += `<div class="matrix-grid" data-event-type-key="${h(rowTypeKey)}" style="${gridColumnsStyle}">`;
                  html += `<div class="matrix-type matrix-type--draggable" draggable="true" tabindex="0" data-row-type-id="${h(
                        row.id
                  )}" data-row-label="${h(row.label)}" style="${accentStyle};transform:translateZ(0);will-change:transform;">${h(row.label)}</div>`;
                  html += `<div class="matrix-track" style="width:${timelineWidth}px">`;
                  html += `<div class="matrix-row-buckets" style="position:absolute;top:0;left:0;width:${timelineWidth}px;height:100%;">`;
                  // Use bucketRects for both labels and buckets to ensure sync
                  bucketRects.forEach((rect, idx) => {
                        html += `<div class="matrix-cell" data-bucket="${idx}" style="position:absolute;left:${rect.left}px;width:${rect.width}px;height:100%;"></div>`;
                  });
                  html += '</div>';
                  html += `<div class="matrix-row-events" style="width:${timelineWidth}px">`;
                  const stackBuckets = new Map();
                  const stackIndexMap = new Map();
                  const sortedEvents = [...row.events].sort((a, b) => {
                        const aSecond = Number(a.match_second) || 0;
                        const bSecond = Number(b.match_second) || 0;
                        if (aSecond !== bSecond) return aSecond - bSecond;
                        return (Number(a.id) || 0) - (Number(b.id) || 0);
                  });
                  const getStackKey = (seconds) => {
                        const mapped = timelineMetrics.mapSecond ? timelineMetrics.mapSecond(seconds) : seconds;
                        return Math.round(mapped / 6);
                  };
                  sortedEvents.forEach((ev) => {
                        if (isPeriodEvent(ev)) {
                              return;
                        }
                        const seconds = Number.isFinite(Number(ev.match_second)) ? Number(ev.match_second) : 0;
                        const stackKey = getStackKey(seconds);
                        const count = stackBuckets.has(stackKey) ? stackBuckets.get(stackKey) : 0;
                        stackBuckets.set(stackKey, count + 1);
                  });
                  sortedEvents.forEach((ev) => {
                        if (isPeriodEvent(ev)) {
                              return;
                        }
                        const labelText = displayEventLabel(ev, row.label);
                        const matrixTimeLabel = h(formatMatchSecond(ev.match_second).text);
                        const seconds = Number.isFinite(Number(ev.match_second)) ? Number(ev.match_second) : 0;
                        const displaySecond = timelineMetrics.mapSecond ? timelineMetrics.mapSecond(seconds) : seconds;
                        const position = Math.min(Math.max(0, displaySecond * timelineZoom.pixelsPerSecond * timelineZoom.scale), timelineWidth);
                        const stackKey = getStackKey(seconds);
                        const stackIndex = stackIndexMap.has(stackKey) ? stackIndexMap.get(stackKey) : 0;
                        stackIndexMap.set(stackKey, stackIndex + 1);
                        const stackCount = stackBuckets.has(stackKey) ? stackBuckets.get(stackKey) : 1;
                        const spacingX = 10;
                        const stackX = stackCount > 1 ? (stackIndex - (stackCount - 1) / 2) * spacingX : 0;
                        const stackY = stackCount > 1 ? Math.floor(stackIndex / 3) * 5 : 0;
                        const stackZ = 5 + Math.min(stackIndex, 12);
                        const rawImportance = parseInt(ev.importance, 10);
                        const importance = Number.isNaN(rawImportance) ? 1 : rawImportance;
                        const baseAccent = eventTypeAccents[String(row.id)] || EVENT_NEUTRAL;
                        const teamColor =
                              ev.team_side === 'home' ? '#3b82f6' : ev.team_side === 'away' ? '#f97316' : baseAccent;
                        const emphasisHighlight =
                              importance >= 4 ? 'border: 1px solid var(--event-color-strong, rgba(148, 163, 184, 0.55));' : '';
                        const dotStyle = `${buildColorStyle(teamColor)}left:${position}px; --stack-x:${stackX}px; --stack-y:${stackY}px; z-index:${stackZ}; ${emphasisHighlight}`;
                        const clipNumeric = getEventOrClipId(ev);
                        const eventClipId = Number.isFinite(clipNumeric) && clipNumeric > 0 ? String(clipNumeric) : null;
                        const clipAttributes = ` draggable="true"${eventClipId ? ` data-clip-id="${eventClipId}"` : ''}`;
                        const evTypeKey = typeof ev.event_type_key === 'string' ? ev.event_type_key.toLowerCase() : '';
                        html += `<span class="matrix-dot"${clipAttributes} data-event-type-key="${h(evTypeKey)}" data-second="${ev.match_second}" data-event-id="${ev.id}" style="${dotStyle}"></span>`;
                  });
                  html += '</div></div></div>';
            });


            // Removed Clips and Drawings from timeline (matrix mode)

            html += '</div>';

            $timelineMatrix.html(html);
            bindMatrixTooltipHandlers();
            // --- FORCE PLAYLIST PANEL INIT (fix race/timing issues) ---
            // (Removed forced re-initialization of playlist panel/controls here)
            const debugCounts = {
                  dots: $timelineMatrix.find('.matrix-dot').length,
                  dotsWithClip: $timelineMatrix.find('.matrix-dot[data-clip-id]').length,
                  dotsWithoutClip: $timelineMatrix.find('.matrix-dot:not([data-clip-id])').length,
                  clips: $timelineMatrix.find('.matrix-clip').length,
                  drawings: $timelineMatrix.find('.matrix-drawing').length,
            };
            if (debugCounts.dots > 0 && debugCounts.dotsWithClip === 0 && !missingClipToastShown) {
                  missingClipToastShown = true;
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
            // Timeline drag-to-scroll disabled
            return;
      }

      function handleMatrixPointerMove(e) {
            // Timeline drag-to-scroll disabled
            return;
      }

      function handleMatrixPointerUp(e) {
            // Timeline drag-to-scroll disabled
            return;
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

      function setupTimelineResizeObserver() {
            if (timelineResizeObserver || typeof window.ResizeObserver !== 'function') {
                  return;
            }
            const target = $timelineMatrix.closest('.timeline-scroll')[0];
            if (!target) return;
            timelineResizeObserver = new window.ResizeObserver((entries) => {
                  const entry = entries && entries[0];
                  if (!entry) return;
                  const width = Math.round(entry.contentRect && entry.contentRect.width ? entry.contentRect.width : 0);
                  if (!width || width === lastTimelineWidth) return;
                  lastTimelineWidth = width;
                  if (timelineMode === 'matrix') {
                        renderTimeline();
                  }
            });
            timelineResizeObserver.observe(target);
      }

      function setEditorCollapsed(collapsed, hintText, hidePanel = false) {
            const shouldHide = !!collapsed || !!hidePanel;
            if (shouldHide) {
                  $editorPanel.addClass('is-hidden');
                  $editorPanel[0].setAttribute('inert', '');
            } else {
                  $editorPanel.removeClass('is-hidden');
                  $editorPanel[0].removeAttribute('inert');
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

      function attemptCloseEditor(forceClose = false) {
            if (editorDirty && !forceClose) return;
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
                        updateEditorShotMapForType('');
                        window.shotOriginPoint = null;
                        window.shotTargetPoint = null;
                        window.shotOriginCleared = false;
                        window.shotTargetCleared = false;
                        if (typeof window.renderShotPoint === 'function') {
                              window.renderShotPoint('origin', null);
                              window.renderShotPoint('target', null);
                        }
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

                  const typeKey = ((ev.event_type_key || '').toLowerCase() || resolveEventTypeKeyById(ev.event_type_id));
                  updateEditorShotMapForType(typeKey);
                  if (isShotTypeKey(typeKey) || isGoalTypeKey(typeKey)) {
                        const originPoint = (ev.shot_origin_x !== null && ev.shot_origin_x !== undefined
                              && ev.shot_origin_y !== null && ev.shot_origin_y !== undefined)
                              ? { x: Number(ev.shot_origin_x), y: Number(ev.shot_origin_y) }
                              : null;
                        const targetPoint = (ev.shot_target_x !== null && ev.shot_target_x !== undefined
                              && ev.shot_target_y !== null && ev.shot_target_y !== undefined)
                              ? { x: Number(ev.shot_target_x), y: Number(ev.shot_target_y) }
                              : null;

                        if (originPoint) {
                              window.shotOriginPoint = originPoint;
                              window.shotOriginCleared = false;
                              if (typeof window.renderShotPoint === 'function') {
                                    window.renderShotPoint('origin', originPoint);
                              }
                        } else {
                              window.shotOriginPoint = null;
                              window.shotOriginCleared = false;
                              if (typeof window.renderShotPoint === 'function') {
                                    window.renderShotPoint('origin', null);
                              }
                        }

                        if (targetPoint) {
                              window.shotTargetPoint = targetPoint;
                              window.shotTargetCleared = false;
                              if (typeof window.renderShotPoint === 'function') {
                                    window.renderShotPoint('target', targetPoint);
                              }
                        } else {
                              window.shotTargetPoint = null;
                              window.shotTargetCleared = false;
                              if (typeof window.renderShotPoint === 'function') {
                                    window.renderShotPoint('target', null);
                              }
                        }
                  }
            });
            editorDirty = false;
            const labelText = displayEventLabel(ev, ev.event_type_label || 'Event');
            const editorTimeLabel = h(formatMatchSecond(ev.match_second).text);
            setEditorCollapsed(false, `${h(labelText)} - ${editorTimeLabel}`, false);
      }

      function findEventById(id) {
            if (!id) return null;
            return events.find((e) => String(e.id) === String(id));
      }

      function goToVideoTime(seconds) {
            if (seconds === null || seconds === undefined) {
                  return;
            }
            const normalized = Number(seconds);
            if (Number.isNaN(normalized)) {
                  return;
            }
            seekPlayback(Math.max(0, normalized), 'event_jump');
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
            const displaySeconds = timelineMetrics.mapSecond ? timelineMetrics.mapSecond(normalized) : normalized;
            const offset = axisOffset();
            const center = viewportEl.clientWidth / 2;
            const target =
                  Math.max(0, displaySeconds * timelineZoom.pixelsPerSecond * timelineZoom.scale + offset - center);
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
                  matchSecond = getCurrentVideoSecond();
                  updateTimeFromSeconds(matchSecond);
            }
            matchSecond = Math.max(0, matchSecond);
            const teamSide = ($teamSide.val() || '').trim() || currentTeam || 'home';
            $teamSide.val(teamSide);
            return {
                  match_id: cfg.matchId,
                  event_id: $eventId.val(),
                  match_second: matchSecond,
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

      function resolveEventTypeKeyById(typeId) {
            if (!typeId) return '';
            const type = eventTypeMap[String(typeId)];
            return (type && type.type_key ? String(type.type_key) : '').toLowerCase();
      }

      function saveEvent() {
            if (!lockOwned || !cfg.canEditRole) return;
            const data = collectData();
            const typeKey = resolveEventTypeKeyById(data.event_type_id);
            if (isShotTypeKey(typeKey) || isGoalTypeKey(typeKey)) {
                  Object.assign(data, buildShotLocationPayload());
            }
            const endpointKey = data.event_id ? 'eventUpdate' : 'eventCreate';
            const url = endpoint(endpointKey);
            if (!url) {
                  Toast.error('Save failed: Missing event endpoint');
                  return;
            }
            if (data.shot_origin_x !== undefined || data.shot_target_x !== undefined) {
                  console.debug('[ShotSVG] Shot payload', {
                        shot_origin_x: data.shot_origin_x,
                        shot_origin_y: data.shot_origin_y,
                        shot_target_x: data.shot_target_x,
                        shot_target_y: data.shot_target_y,
                  });
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
                        loadEvents(true);
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
                        loadEvents(true);
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
                        loadEvents(true);
                        refreshSummaryPanel();
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
                        loadEvents(true);
                        refreshSummaryPanel();
                        setStatus('Deleted all visible');
                  })
                  .catch((e) => showError('Delete failed', e && e.message ? e.message : 'Unknown'));
      }

      function addPeriodMarker(typeKey, note) {
            const type = (cfg.eventTypes || []).find((t) => t.type_key === typeKey);
            if (!type) return;
            const current = getCurrentVideoSecond();
            $eventId.val('');
            $eventTypeId.val(type.id);
            $teamSide.val('unknown');
            $matchSecond.val(current);
            $notes.val(note || '');
            $importance.val('1');
            saveEvent();
      }

      function normalizePeriodLabel(label) {
            if (!label) return '';
            return String(label).trim().toLowerCase();
      }

      function canonicalizePeriodKey(rawKey, label) {
            const tryNormalize = (value) => {
                  if (!value) return null;
                  const normalizedLabel = normalizePeriodLabel(value);
                  if (!normalizedLabel) return null;
                  const mapped = periodLabelToKey.get(normalizedLabel);
                  if (mapped) {
                        return mapped;
                  }
                  const fallbackKey = normalizedLabel.replace(/[^a-z0-9]+/g, '_');
                  if (periodDefinitions[fallbackKey]) {
                        return fallbackKey;
                  }
                  return null;
            };

            if (rawKey && typeof rawKey === 'string') {
                  const normalizedKey = rawKey.trim().replace(/[^a-zA-Z0-9]+/g, '_').toLowerCase();
                  if (periodDefinitions[normalizedKey]) {
                        return normalizedKey;
                  }
                  const labelFromKey = tryNormalize(rawKey);
                  if (labelFromKey) {
                        return labelFromKey;
                  }
            }

            return tryNormalize(label);
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
            window.DeskPeriodState = periodState;
            document.dispatchEvent(new CustomEvent('desk:periodstate', { detail: periodState }));
            updatePeriodControlsUI();
      }

      function refreshPeriodStateFromEvents() {
            if (!hasCanonicalPeriods) {
                  return;
            }
            const hasExisting = periodState && Object.keys(periodState).length > 0;
            const nextState = hasExisting ? JSON.parse(JSON.stringify(periodState)) : createBasePeriodState();
            if (!hasExisting && Array.isArray(cfg.periods)) {
                  (cfg.periods || []).forEach((p) => {
                        const key = canonicalizePeriodKey(p.period_key, p.label);
                        if (!key || !nextState[key]) return;
                        const entry = nextState[key];
                        entry.started = !!p.start_second;
                        entry.ended = !!p.end_second;
                        entry.startMatchSecond = p.start_second !== null ? Number(p.start_second) : null;
                        entry.endMatchSecond = p.end_second !== null ? Number(p.end_second) : null;
                        entry.label = p.label || entry.label;
                  });
            }
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
            if (!hasCanonicalPeriods) {
                  currentPeriodStatusEl.textContent = '';
                  return;
            }
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
            if (!state || Object.values(state).every(p => !p || !p.started)) return 'not_started';
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


      // --- DATA OWNERSHIP: periods, events, eventTypes, players, tags ---
      // STRATEGY A: Use embedded data from window.DeskConfig for first paint, do not re-fetch on load
      // DATASET: tags
      // Ownership: STRATEGY A (Server embeds all required tags as JSON in window.DeskConfig; client reads from cfg.tags)
      // Rationale: Avoids duplicate DB/API fetches on load, ensures single source of truth for initial render
      const tags = Array.isArray(cfg.tags) ? cfg.tags : [];
      const hasCanonicalPeriods = Array.isArray(cfg.periods) && cfg.periods.length > 0;
      window.DeskPeriodsAvailable = hasCanonicalPeriods;
      const showMissingPeriodsWarning = () => {
            if (!$status || !$status.length) {
                  return;
            }
            $status.text(
                  'No period timing data available for this match. Please define periods in the match setup.'
            );
      };
      let periodsBootstrapped = false;
      function refreshPeriodStateFromApi() {
            if (!hasCanonicalPeriods) {
                  periodState = {};
                  window.DeskPeriodState = periodState;
                  updatePeriodControlsUI();
                  showMissingPeriodsWarning();
                  return;
            }
            // Use embedded periods for initial load only
            if (!periodsBootstrapped && Array.isArray(cfg.periods)) {
                  const nextState = createBasePeriodState();
                  (cfg.periods || []).forEach((p) => {
                        const key = canonicalizePeriodKey(p.period_key, p.label);
                        if (!key) return;
                        const entry = nextState[key];
                        if (!entry) return;
                        entry.started = !!p.start_second;
                        entry.ended = !!p.end_second;
                        entry.startMatchSecond = p.start_second !== null ? Number(p.start_second) : null;
                        entry.endMatchSecond = p.end_second !== null ? Number(p.end_second) : null;
                        entry.label = p.label || entry.label;
                  });
                  setPeriodState(nextState);
                  periodsBootstrapped = true;
                  return;
            }
            // Fallback: fetch if not present (should not happen)
            const url = cfg.endpoints && cfg.endpoints.periodsList ? cfg.endpoints.periodsList : '/app/api/matches/periods_list.php';
            if (!url) return;
            $.getJSON(url)
                  .done((res) => {
                        if (!res.ok || !Array.isArray(res.periods)) return;
                        const nextState = createBasePeriodState();
                        (res.periods || []).forEach((p) => {
                              const key = canonicalizePeriodKey(p.period_key, p.label);
                              if (!key) return;
                              const entry = nextState[key];
                              if (!entry) return;
                              entry.started = !!p.start_second;
                              entry.ended = !!p.end_second;
                              entry.startMatchSecond = p.start_second !== null ? Number(p.start_second) : null;
                              entry.endMatchSecond = p.end_second !== null ? Number(p.end_second) : null;
                              entry.label = p.label || entry.label;
                        });
                        setPeriodState(nextState);
                  });
      }

      // On page load, use embedded periods (no duplicate fetch)
      refreshPeriodStateFromApi();

      function recordPeriodBoundary(action, periodKey, label) {
            if (!lockOwned || !cfg.canEditRole || !periodKey || !action) {
                  return;
            }
            const url = endpoint('periodsSet');
            if (!url) {
                  return;
            }
            const current = getCurrentVideoSecond();
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
                  return;
            }
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
                  return;
            }
            if (!playlistState.playlists.length) {
                  $playlistList.text('No playlists yet.');
                  return;
            }
            const filteredPlaylists = getFilteredPlaylists();
            if (!filteredPlaylists.length) {
                  const hasFilters =
                        (playlistViewState.teamFilter || '') !== '' || (playlistViewState.searchQuery || '').trim() !== '';
                  $playlistList.text(hasFilters ? 'No playlists match the current filters.' : 'No playlists yet.');
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
                  $playlistClips.removeClass('is-table');
                  $playlistClips.html('<div class="text-muted-alt text-sm">No clips in this playlist yet.</div>');
                  refreshPlaylistAddButton();
                  updatePlaylistControls();
                  emitClipPlaybackState();
                  return;
            }
            $playlistClips.addClass('is-table');
            const html = playlistState.clips
                  .map((clip, idx) => {
                        const activeClass = idx === playlistState.activeIndex ? ' playlist-clip-active' : '';
                        const clipId = getPlaylistClipId(clip);
                        const clipIdAttr = clipId !== null ? String(clipId) : '';
                        const clipLabel = clipIdAttr || (clip.id ? String(clip.id) : '—');
                        const startLabel = formatMatchSecondWithExtra(clip.start_second, 0);
                        const endLabel = formatMatchSecondWithExtra(clip.end_second, 0);
                        const durationText = clip.duration_seconds ? `${clip.duration_seconds}s` : '—';
                        const clipName = clip.clip_name || `Clip #${clipLabel}`;
                        const regenerateButton = '';
                        let statusHtml = '';
                        if (!clipIdAttr) {
                              statusHtml = '<span class="playlist-clip-status creating">Creating...</span>';
                        } else {
                              let mp4Url = clip.mp4_path || '';
                              // Fix: If mp4Url contains absolute path, strip everything before /videos/clips/
                              const webPathIdx = mp4Url.indexOf('/videos/clips/');
                              if (webPathIdx > -1) {
                                    mp4Url = mp4Url.substring(webPathIdx);
                              }
                              // Optionally prepend base URL if needed
                              if (mp4Url && window.DeskConfig && window.DeskConfig.basePath) {
                                    mp4Url = window.DeskConfig.basePath.replace(/\/$/, '') + mp4Url;
                              }
                              const statusClass = mp4Url ? '' : ' pending';
                              const statusText = mp4Url ? 'Checking...' : 'Waiting…';
                              statusHtml = `<span class="playlist-clip-status${statusClass}" data-clip-id="${clipIdAttr}" data-mp4-url="${h(mp4Url)}">${statusText}</span>`;
                        }
                        return `<div class="playlist-clip${activeClass}" data-clip-id="${clipIdAttr}">
                                          <div class="playlist-clip-row">
                                                <div class="playlist-clip-col-title">
                                                      <strong>${h(clipName)}</strong>
                                                      <span>${h(startLabel)} · ${h(durationText)}</span>
                                                </div>
                                                <div class="playlist-clip-col-meta">${h(startLabel)}</div>
                                                <div class="playlist-clip-col-meta">${h(endLabel)}</div>
                                                <div class="playlist-clip-col-meta">#${h(clipLabel)}</div>
                                                <div class="playlist-clip-col-meta">${statusHtml}</div>
                                                <div class="playlist-clip-col-actions">
                                                      ${regenerateButton}
                                                      <button type="button" class="playlist-clip-download" data-clip-id="${clipIdAttr}" aria-label="Download clip">
                                                            <i class="fa-solid fa-download" aria-hidden="true"></i>
                                                      </button>
                                                      <button type="button" class="playlist-clip-delete" data-clip-id="${clipIdAttr}" aria-label="Remove clip">
                                                            <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                                                      </button>
                                                </div>
                                          </div>
                                    </div>`;
                  })
                  .join('');
            const header = `<div class="playlist-clip-head">
                        <div>Clip</div>
                        <div>Start</div>
                        <div>End</div>
                        <div>ID</div>
                        <div>Status</div>
                        <div>Actions</div>
                  </div>`;
            $playlistClips.html(header + html);
            setTimeout(() => {
                  $playlistClips.find('.playlist-clip-status[data-clip-id]').each(function () {
                        const $status = $(this);
                        const clipId = $status.data('clipId');
                        const mp4Url = $status.data('mp4Url');
                        if (!clipId || !mp4Url) {
                              $status.text('Waiting…');
                              $status.removeClass('creating ready error').addClass('pending');
                              return;
                        }
                        fetch(mp4Url, { method: 'HEAD' })
                              .then((resp) => {
                                    if (resp.ok) {
                                          $status.text('Ready');
                                          $status.removeClass('creating error').addClass('ready');
                                    } else {
                                          $status.text('Error');
                                          $status.removeClass('creating ready').addClass('error');
                                    }
                              })
                              .catch((err) => {
                                    console.error('[Clip Status Debug] HEAD request failed:', {
                                          clipId,
                                          mp4Url,
                                          error: err,
                                    });
                                    $status.text('Error');
                                    $status.removeClass('creating ready').addClass('error');
                              });
                  });
            }, 100);
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

      function handleRegenerateClip(event) {
            event.stopPropagation();
            event.preventDefault();
            const clipId = $(event.currentTarget).data('clipId');
            if (!clipId) {
                  return;
            }
            const clip = playlistState.clips.find((row) => String(row.id) === String(clipId));
            if (!clip) {
                  return;
            }
            openRegenerateModal(clip);
      }

      function openRegenerateModal(clip) {
            if (!clip || !clip.id) {
                  return;
            }
            ensureRegenerateModal();
            regenerateModalClipId = clip.id;
            const label = clip.clip_name ? clip.clip_name.toString() : 'clip';
            $regenerateModalMessage.text(`This will permanently replace "${label}" using the latest naming and generation logic.`);
            $regenerateModalError.hide().text('');
            $regenerateModal.attr('aria-hidden', 'false').css('display', 'block');
            $regenerateModalConfirm.prop('disabled', false).text('Regenerate clip');
      }

      function closeRegenerateModal(force = false) {
            if (regenerateModalPending && !force) {
                  return;
            }
            if (!$regenerateModal) {
                  return;
            }
            regenerateModalClipId = null;
            regenerateModalPending = false;
            $regenerateModal.attr('aria-hidden', 'true').css('display', 'none');
            $regenerateModalError.hide().text('');
            $regenerateModalConfirm.prop('disabled', false).text('Regenerate clip');
      }

      function showRegenerateModalError(message) {
            if (!message) {
                  message = 'Regeneration failed.';
            }
            if (!$regenerateModalError) {
                  return;
            }
            $regenerateModalError.text(message).show();
      }

      function submitRegenerateClip() {
            if (!regenerateModalClipId || regenerateModalPending) {
                  return;
            }
            if (!$regenerateModalConfirm || !$regenerateModalError) {
                  return;
            }
            regenerateModalPending = true;
            $regenerateModalError.hide().text('');
            $regenerateModalConfirm.prop('disabled', true).text('Regenerating…');
            const url = `/api/clips/${regenerateModalClipId}/regenerate`;
            postJson(url, {})
                  .done((res) => {
                        if (!res || res.ok === false) {
                              showRegenerateModalError(res && res.error ? res.error : 'Regeneration failed');
                              return;
                        }
                        const updatedClip = res.clip;
                        if (updatedClip) {
                              const idx = playlistState.clips.findIndex((row) => String(row.id) === String(updatedClip.id));
                              if (idx >= 0) {
                                    playlistState.clips[idx] = updatedClip;
                              }
                        }
                        closeRegenerateModal(true);
                        renderPlaylistClips();
                        showToast('Clip regenerated');
                  })
                  .fail((xhr) => {
                        const message =
                              xhr && xhr.responseJSON && xhr.responseJSON.error
                                    ? xhr.responseJSON.error
                                    : xhr && xhr.responseText
                                          ? xhr.responseText
                                          : 'Regeneration failed';
                        showRegenerateModalError(message);
                  })
                  .always(() => {
                        regenerateModalPending = false;
                        $regenerateModalConfirm.prop('disabled', false).text('Regenerate clip');
                  });
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

      function handlePlaylistSelection(event) {
            if (!playlistEnabled()) {
                  return;
            }
            if (event && $(event.target).closest('.playlist-item-action').length) {
                  return;
            }
            const playlistId = $(this).data('playlistId');
            if (!playlistId) {
                  return;
            }
            if (playlistState.activePlaylistId === playlistId) {
                  return;
            }
            loadPlaylist(playlistId);
      }

      function handlePlaylistItemAction(event) {
            event.preventDefault();
            event.stopPropagation();
            if (!playlistEnabled()) {
                  return;
            }
            const $button = $(event.currentTarget);
            const playlistId = $button.data('playlistId');
            const action = $button.data('playlistAction');
            if (!playlistId || !action) {
                  return;
            }
            if (action === 'download') {
                  const url = playlistConfig.download;
                  if (!url) {
                        showError('Unable to download playlist', 'Missing download endpoint');
                        return;
                  }
                  const downloadUrl = `${url}?playlist_id=${encodeURIComponent(playlistId)}`;
                  const link = document.createElement('a');
                  link.href = downloadUrl;
                  link.download = '';
                  document.body.appendChild(link);
                  link.click();
                  link.remove();
                  return;
            }
            if (action !== 'delete') {
                  return;
            }
            if (!cfg.canEditRole) {
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

      function handlePlaylistRegenerateClips() {
            if (!playlistEnabled() || !cfg.canEditRole) {
                  return;
            }
            const url = playlistConfig.regenerateAll;
            if (!url) {
                  showError('Unable to regenerate clips', 'Missing regenerate endpoint');
                  return;
            }
            if (!confirm('Regenerate clips for all events in this match?')) {
                  return;
            }
            if ($playlistRegenerateClipsBtn && $playlistRegenerateClipsBtn.length) {
                  $playlistRegenerateClipsBtn.prop('disabled', true).text('Regenerating…');
            }
            postJson(url, {})
                  .done((res) => {
                        if (!res || res.ok === false) {
                              showError('Unable to regenerate clips', res ? res.error : 'Unknown');
                              return;
                        }
                        const created = Number(res.created || 0);
                        const updated = Number(res.updated || 0);
                        const skipped = Number(res.skipped || 0);
                        showToast(`Clips regenerated. Created ${created}, updated ${updated}, skipped ${skipped}.`);
                        fetchPlaylists();
                        loadEvents(true);
                  })
                  .fail((xhr, status, error) => {
                        showError('Unable to regenerate clips', xhr.responseText || error || status);
                  })
                  .always(() => {
                        if ($playlistRegenerateClipsBtn && $playlistRegenerateClipsBtn.length) {
                              $playlistRegenerateClipsBtn.prop('disabled', false).text('Regenerate clips');
                        }
                  });
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

      function addClipsToPlaylistById(playlistId, clipIds, options = {}) {
            if (!playlistEnabled() || !playlistId) {
                  return;
            }
            const actionLabel = options.action === 'created' ? 'created' : 'updated';
            const unique = Array.from(new Set((clipIds || []).map((id) => String(id)).filter(Boolean)));
            if (!unique.length) {
                  showToast('No clips available to add', true);
                  return;
            }
            let added = 0;
            let skipped = 0;
            let failed = 0;
            let index = 0;
            const addNext = () => {
                  if (index >= unique.length) {
                        fetchPlaylists();
                        loadPlaylist(playlistId);
                        const total = unique.length;
                        const message = failed
                              ? `Playlist ${actionLabel} with ${added}/${total} clips (some failed)`
                              : skipped
                                    ? `Playlist ${actionLabel} with ${added}/${total} clips (some already existed)`
                                    : `Playlist ${actionLabel} with ${added} clip${added === 1 ? '' : 's'}`;
                        showToast(message, failed > 0);
                        if (playlistRowDropPending) {
                              setPlaylistRowDropPending(false);
                        }
                        return;
                  }
                  const clipId = unique[index];
                  postJson(playlistConfig.addClip, {
                        playlist_id: playlistId,
                        clip_id: clipId,
                  })
                        .done((res) => {
                              if (!res || res.ok === false) {
                                    failed += 1;
                                    return;
                              }
                              added += 1;
                        })
                        .fail((xhr) => {
                              if (xhr && xhr.status === 409) {
                                    skipped += 1;
                                    return;
                              }
                              failed += 1;
                        })
                        .always(() => {
                              index += 1;
                              addNext();
                        });
            };
            addNext();
      }

      function updatePlaylistWithRowClips(playlistId, clipIds) {
            if (!playlistEnabled() || !playlistId) {
                  return;
            }
            const unique = Array.from(new Set((clipIds || []).map((id) => String(id)).filter(Boolean)));
            if (!unique.length) {
                  showToast('No clips available to add', true);
                  return;
            }
            const url = playlistConfig.show(playlistId);
            if (!url) {
                  showError('Unable to update playlist', 'Missing playlist endpoint');
                  return;
            }
            setPlaylistRowDropPending(true, 'Updating playlist…');
            $.get(url)
                  .done((res) => {
                        if (!res || res.ok === false) {
                              showError('Unable to load playlist', res ? res.error : 'Unknown');
                              return;
                        }
                        const currentClips = Array.isArray(res.clips) ? res.clips : [];
                        const currentIds = new Set(
                              currentClips
                                    .map((clip) => getPlaylistClipId(clip))
                                    .filter((id) => Number.isFinite(id) && id > 0)
                                    .map((id) => String(id))
                        );
                        const targetIds = new Set(unique);
                        const toRemove = Array.from(currentIds).filter((id) => !targetIds.has(id));
                        const toAdd = Array.from(targetIds).filter((id) => !currentIds.has(id));

                        const removeNext = () => {
                              if (!toRemove.length) {
                                    addClipsToPlaylistById(playlistId, toAdd);
                                    return;
                              }
                              const clipId = toRemove.shift();
                              postJson(playlistConfig.removeClip, {
                                    playlist_id: playlistId,
                                    clip_id: clipId,
                              })
                                    .always(() => {
                                          removeNext();
                                    });
                        };
                        removeNext();
                  })
                  .fail((xhr, status, error) => {
                        showError('Unable to load playlist', xhr.responseText || error || status);
                  })
                  .always(() => {
                        setTimeout(() => setPlaylistRowDropPending(false), 400);
                  });
      }

      function createPlaylistWithRowClips(rowTypeId, rowLabel) {
            if (!playlistEnabled()) {
                  return;
            }
            const title = (rowLabel || '').trim();
            if (!title) {
                  showToast('Playlist title missing', true);
                  return;
            }
            const url = playlistConfig.create;
            if (!url) {
                  showError('Unable to create playlist', 'Missing create endpoint');
                  return;
            }
            const clipIds = matrixRowClipMap.get(String(rowTypeId)) || [];
            // DEBUG: Log clipIds for playlist creation
            console.log('[PLAYLIST] Attempt to create playlist for rowTypeId', rowTypeId, 'clipIds:', clipIds);
            if (!clipIds.length) {
                  showToast('No clips in this row yet', true);
                  return;
            }
            const existing = findPlaylistByTitle(title);
            if (existing && existing.id) {
                  const wantsUpdate = window.confirm(
                        `A playlist named "${title}" already exists. Update it with clips from this row?`
                  );
                  if (wantsUpdate) {
                        updatePlaylistWithRowClips(existing.id, clipIds);
                        return;
                  }
                  let nextTitle = window.prompt('Enter a new playlist name:', title);
                  if (nextTitle === null) {
                        return;
                  }
                  nextTitle = nextTitle.trim();
                  if (!nextTitle) {
                        showToast('Playlist name cannot be empty', true);
                        return;
                  }
                  if (findPlaylistByTitle(nextTitle)) {
                        showToast('Playlist name already exists', true);
                        return;
                  }
                  return createPlaylistWithRowClips(rowTypeId, nextTitle);
            }
            setPlaylistRowDropPending(true, 'Creating playlist…');
            $.post(url, { title })
                  .done((res) => {
                        if (!res || res.ok === false) {
                              showError('Unable to create playlist', res ? res.error : 'Unknown');
                              return;
                        }
                        const playlistId = res.playlist && res.playlist.id ? res.playlist.id : null;
                        if (!playlistId) {
                              showError('Unable to create playlist', 'Missing playlist id');
                              return;
                        }
                        playlistState.activePlaylistId = playlistId;
                        addClipsToPlaylistById(playlistId, clipIds, { action: 'created' });
                  })
                  .fail((xhr, status, error) => {
                        showError('Unable to create playlist', xhr.responseText || error || status);
                  })
                  .always(() => {
                        setTimeout(() => setPlaylistRowDropPending(false), 400);
                  });
      }

      function handleMatrixRowDragStart(event) {
            const $el = $(event.currentTarget);
            console.log('[DRAG] matrix-type--draggable dragstart', $el[0]);
            // Debug: Check playlist drop target DOM presence and bounding rects
            setTimeout(function () {
                  var $playlistList = $('#playlistList');
                  if ($playlistList.length) {
                        var rect = $playlistList[0].getBoundingClientRect();
                        console.log('[DEBUG] #playlistList present. Bounding rect:', rect);
                        var $firstItem = $playlistList.find('.playlist-item').first();
                        if ($firstItem.length) {
                              var itemRect = $firstItem[0].getBoundingClientRect();
                              console.log('[DEBUG] .playlist-item present. Bounding rect:', itemRect);
                        } else {
                              console.log('[DEBUG] No .playlist-item found in #playlistList');
                        }
                  } else {
                        console.log('[DEBUG] #playlistList NOT present in DOM');
                  }
            }, 100);
            // Force setData for Chrome/Edge drag-and-drop bug
            if (event.originalEvent && event.originalEvent.dataTransfer) {
                  event.originalEvent.dataTransfer.setData('text/plain', $el.data('rowLabel') || 'matrix-row');
            }
            const rowTypeId = $el.data('rowTypeId');
            const rowLabel = ($el.data('rowLabel') || '').toString();
            if (playlistRowDropPending) {
                  showToast('Playlist update in progress. Please wait…', true);
                  event.preventDefault();
                  return;
            }
            if (!rowTypeId) {
                  return;
            }
            const clipIds = matrixRowClipMap.get(String(rowTypeId)) || [];
            if (!clipIds.length) {
                  showToast('No clips in this row yet', true);
                  event.preventDefault();
                  return;
            }
            draggingMatrixRow = { typeId: String(rowTypeId), label: rowLabel };
            const transfer = event.originalEvent && event.originalEvent.dataTransfer;
            if (transfer) {
                  transfer.setData(
                        'application/x-desk-matrix-row',
                        JSON.stringify({ typeId: String(rowTypeId), label: rowLabel })
                  );
                  transfer.setData('text/plain', `row:${rowTypeId}`);
                  transfer.effectAllowed = 'copy';
            }
            setPlaylistRowDropArmed(true);
      }

      function handleMatrixRowDragEnd() {
            draggingMatrixRow = null;
            setPlaylistRowDropArmed(false);
            setPlaylistRowDropTarget(false);
      }

      function handleMatrixItemDragStart(event) {
            const $el = $(event.currentTarget);
            const dataClipId = $el.data('clipId');
            const attrClipId = $el.attr('data-clip-id');
            const clipId = dataClipId !== undefined && dataClipId !== null ? dataClipId : attrClipId;
            const hasClipId = clipId !== undefined && clipId !== null && `${clipId}` !== '';
            if (!hasClipId) {
                  showToast('This event has no clip yet. Set in/out and create a clip first.', true);
                  event.preventDefault();
                  return;
            }
            const clipIdString = String(clipId);
            const clipNumeric = Number(clipIdString);
            if (!Number.isFinite(clipNumeric) || clipNumeric <= 0) {
                  showToast('This event has no clip yet. Set in/out and create a clip first.', true);
                  event.preventDefault();
                  return;
            }
            if (event.originalEvent && event.originalEvent.dataTransfer) {
                  event.originalEvent.dataTransfer.setData('text/plain', clipIdString);
                  event.originalEvent.dataTransfer.effectAllowed = 'copy';
            }
            draggingClipId = clipIdString;
      }

      function handleMatrixItemDragEnd() {
            draggingClipId = null;
            clearPlaylistDropHighlight();
      }

      function handlePlaylistDragOver(event) {
            if (!draggingClipId) {
                  return;
            }
            console.log('[PLAYLIST] dragover playlist item', event);
            const $target = $(event.currentTarget);
            dropTargetElement = $target[0];
            $target.addClass('is-drop-target');
            event.preventDefault();
            if (event.originalEvent && event.originalEvent.dataTransfer) {
                  event.originalEvent.dataTransfer.dropEffect = 'copy';
            }
      }

      function handlePlaylistDragEnter(event) {
            if (!draggingClipId) {
                  return;
            }
            console.log('[PLAYLIST] dragenter playlist item', event);
            const $target = $(event.currentTarget);
            dropTargetElement = $target[0];
            $target.addClass('is-drop-target');
            event.preventDefault();
      }

      function handlePlaylistDragLeave(event) {
            if (!draggingClipId) {
                  return;
            }
            console.log('[PLAYLIST] dragleave playlist item', event);
            const $target = $(event.currentTarget);
            const related = event.originalEvent && event.originalEvent.relatedTarget;
            if (related && $target[0] && $target[0].contains(related)) {
                  return;
            }
            if (dropTargetElement === $target[0]) {
                  dropTargetElement = null;
            }
            $target.removeClass('is-drop-target');
      }

      function handlePlaylistDrop(event) {
            console.log('[PLAYLIST] drop playlist item', event);
            event.preventDefault();
            event.stopPropagation();
            const $target = $(event.currentTarget);
            $target.removeClass('is-drop-target');
            const playlistId = $target.data('playlistId');
            const transfer = event.originalEvent && event.originalEvent.dataTransfer;
            const transferredData = transfer ? transfer.getData('text/plain') : null;
            draggingClipId = null;
            clearPlaylistDropHighlight();
            if (!playlistId) {
                  return;
            }
            // If dropping a matrix row, add all clips for that row
            if (transferredData && /^row:(\d+)$/.test(transferredData)) {
                  const rowTypeId = transferredData.match(/^row:(\d+)$/)[1];
                  const clipIds = matrixRowClipMap.get(String(rowTypeId)) || [];
                  if (!clipIds.length) {
                        showToast('No clips available for this row', true);
                        return;
                  }
                  // Add all clips for this row to the playlist
                  clipIds.forEach(function (clipId) {
                        addClipToPlaylistById(playlistId, clipId, { setActivePlaylist: true });
                  });
                  return;
            }
            // Otherwise, treat as a single clip ID
            const rawClipId = transferredData || draggingClipId;
            const hasClipId = rawClipId !== undefined && rawClipId !== null && rawClipId !== '';
            const clipId = hasClipId ? String(rawClipId) : null;
            const clipNumeric = clipId !== null ? Number(clipId) : NaN;
            if (!clipId || !Number.isFinite(clipNumeric) || clipNumeric <= 0) {
                  showToast('No clip available to add', true);
                  return;
            }
            addClipToPlaylistById(playlistId, clipId, { setActivePlaylist: true });
      }

      function handlePlaylistListDragOver(event) {
            if (!draggingMatrixRow || draggingClipId || playlistRowDropPending) {
                  return;
            }
            console.log('[PLAYLIST] dragover playlist list', event);
            event.preventDefault();
            setPlaylistRowDropTarget(true);
            if (event.originalEvent && event.originalEvent.dataTransfer) {
                  event.originalEvent.dataTransfer.dropEffect = 'copy';
            }
      }

      function handlePlaylistListDragEnter(event) {
            if (!draggingMatrixRow || draggingClipId || playlistRowDropPending) {
                  return;
            }
            console.log('[PLAYLIST] dragenter playlist list', event);
            event.preventDefault();
            setPlaylistRowDropTarget(true);
      }

      function handlePlaylistListDragLeave(event) {
            if (!draggingMatrixRow || draggingClipId || playlistRowDropPending) {
                  return;
            }
            console.log('[PLAYLIST] dragleave playlist list', event);
            const related = event.originalEvent && event.originalEvent.relatedTarget;
            if (related && $playlistList[0] && $playlistList[0].contains(related)) {
                  return;
            }
            setPlaylistRowDropTarget(false);
      }

      function handlePlaylistListDrop(event) {
            if (!draggingMatrixRow || draggingClipId) {
                  return;
            }
            console.log('[PLAYLIST] drop playlist list', event);
            event.preventDefault();
            event.stopPropagation();
            if (playlistRowDropPending) {
                  showToast('Playlist update in progress. Please wait…', true);
                  return;
            }
            const row = draggingMatrixRow;
            draggingMatrixRow = null;
            setPlaylistRowDropTarget(false);
            setPlaylistRowDropArmed(false);
            if ($(event.target).closest('.playlist-item').length) {
                  return;
            }
            if (!row || !row.typeId) {
                  return;
            }
            createPlaylistWithRowClips(row.typeId, row.label);
      }

      function clearPlaylistDropHighlight() {
            dropTargetElement = null;
            if (!$playlistList.length) {
                  return;
            }
            $playlistList.find('.playlist-item.is-drop-target').removeClass('is-drop-target');
            setPlaylistRowDropTarget(false);
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
            const current = getCurrentVideoSecond();
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
                        loadEvents(true);
                  })
                  .fail((xhr, status, error) => showError('Clip delete failed', xhr.responseText || error || status));
      }

      function handleGlobalEscape(event) {
            if (event.key !== 'Escape') {
                  return;
            }
            if ($editorPanel.length && !$editorPanel.hasClass('is-hidden') && editorOpen) {
                  event.preventDefault();
                  attemptCloseEditor(true);
                  return;
            }
            if ($regenerateModal && $regenerateModal.is(':visible')) {
                  event.preventDefault();
                  closeRegenerateModal();
                  return;
            }
            if (playlistFilterPopoverOpen) {
                  event.preventDefault();
                  closePlaylistFilterPopover();
            }
      }

      function bindHandlers() {
            // Handle desk mode buttons (Summary, Tag Live, Drawings)
            $(document).on('click', '.desk-mode-button', function () {
                  const $btn = $(this);
                  const mode = $btn.data('mode');
                  if (!mode) {
                        return;
                  }

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
                  // Update toggle button color states and ghost-btn logic
                  $teamToggle.find('.toggle-btn').each(function () {
                        if ($(this).hasClass('is-active')) {
                              $(this).removeClass('ghost-btn');
                        } else {
                              $(this).addClass('ghost-btn');
                        }
                  });
            });

            // On page load, ensure correct ghost-btn state for team toggles
            $teamToggle.find('.toggle-btn').each(function () {
                  if ($(this).hasClass('is-active')) {
                        $(this).removeClass('ghost-btn');
                  } else {
                        $(this).addClass('ghost-btn');
                  }
            });

            $tagBoard.on('click', '.qt-tile', function () {
                  const typeId = $(this).data('type-id');
                  const typeKey = $(this).data('type-key');
                  // Capture video time at the exact moment of click
                  let videoSecond = 0;
                  if (window.DeskSession && typeof window.DeskSession.getCurrentSecond === 'function') {
                        videoSecond = window.DeskSession.getCurrentSecond();
                  } else {
                        const $video = $('#deskVideoPlayer');
                        if ($video.length) {
                              const rawSeconds = $video[0].currentTime;
                              videoSecond = (typeof rawSeconds === 'number' && !Number.isNaN(rawSeconds)) ? Math.max(0, Math.floor(rawSeconds)) : 0;
                        }
                  }
                  quickTag(typeKey, typeId, $(this), videoSecond);
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
            $(document).on('click', '#offsidePlayerList .goal-player-option', handleOffsidePlayerSelection);
            $(document).on('click', '[data-offside-unknown]', handleOffsideUnknownClick);
            $(document).on('click', '[data-offside-modal-close]', (event) => {
                  event.preventDefault();
                  closeOffsidePlayerModal();
            });
            $(document).on('click', '#shotPlayerList .shot-player-option', handleShotPlayerSelection);
            $(document).on('click', '#shotPlayerModal .shot-outcome-btn', handleShotOutcomeClick);
            $(document).on('click', '#shotPlayerModal .shot-result-btn', handleShotResultClick);
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
                  attemptCloseEditor(true);
            });
            $(document).on('keydown', (event) => {
                  if (event.key !== 'Escape') return;
                  if (!$editorPanel.length || $editorPanel.hasClass('is-hidden')) return;
                  event.preventDefault();
                  attemptCloseEditor(true);
            });
            $editorPanel.on('input change', '.desk-editable', () => markEditorDirty());
            $undoBtn.on('click', () => performActionStackRequest('undoEvent', 'Undo'));
            $redoBtn.on('click', () => performActionStackRequest('redoEvent', 'Redo'));
            $eventTypeId.on('change', () => {
                  refreshOutcomeField($eventTypeId.val(), $outcome.val());
                  updateEditorShotMapForType(resolveEventTypeKeyById($eventTypeId.val()));
            });
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
                        updatePlaylistFilterOptions();
                  }
                  if ($playlistSearchToggle.length) {
                        $playlistSearchToggle.attr('aria-pressed', 'false');
                        $playlistSearchToggle.on('click', togglePlaylistSearchRow);
                  }
                  if ($playlistSearchInput.length) {
                        $playlistSearchInput.on('input', handlePlaylistSearchInput);
                  }
                  if ($playlistRegenerateClipsBtn && $playlistRegenerateClipsBtn.length) {
                        $playlistRegenerateClipsBtn.on('click', handlePlaylistRegenerateClips);
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
                  $playlistClips.on('click', '.playlist-clip-regenerate', handleRegenerateClip);
                  $playlistPrevBtn.on('click', () => navigatePlaylist(-1));
                  $playlistNextBtn.on('click', () => navigatePlaylist(1));
            }

            // Regenerate modal is bound lazily in ensureRegenerateModal()

            // Bind drag-and-drop handlers unconditionally so DnD always works
            $timelineMatrix.on('dragstart', '.matrix-dot, .matrix-clip', handleMatrixItemDragStart);
            $timelineMatrix.on('dragend', '.matrix-dot, .matrix-clip', handleMatrixItemDragEnd);
            $timelineMatrix.on('dragstart', '.matrix-type--draggable', handleMatrixRowDragStart);
            $timelineMatrix.on('dragend', '.matrix-type--draggable', handleMatrixRowDragEnd);
            $playlistList.on('dragover', handlePlaylistListDragOver);
            $playlistList.on('dragenter', handlePlaylistListDragEnter);
            $playlistList.on('dragleave', handlePlaylistListDragLeave);
            $playlistList.on('drop', handlePlaylistListDrop);
            $playlistList.on('dragover', '.playlist-item', handlePlaylistDragOver);
            $playlistList.on('dragenter', '.playlist-item', handlePlaylistDragEnter);
            $playlistList.on('dragleave', '.playlist-item', handlePlaylistDragLeave);
            $playlistList.on('drop', '.playlist-item', handlePlaylistDrop);
            $(document).on('dragend', () => {
                  draggingClipId = null;
                  clearPlaylistDropHighlight();
            });

            $filterTeam.on('change', () => scheduleTimelineRender());
            $filterType.on('change', () => scheduleTimelineRender());
            $filterPlayer.on('change', () => scheduleTimelineRender());
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
            // Timeline drag-to-scroll and wheel scroll disabled
            if ($timelineMatrix.length && !matrixWheelListenerBound) {
                  const matrixEl = $timelineMatrix[0];
                  if (matrixEl) {
                        matrixWheelListenerBound = true;
                        matrixEl.removeEventListener && matrixEl.removeEventListener('wheel', matrixViewportWheelListener);
                  }
            }
            $timelineMatrix.off('pointerdown', '.matrix-viewport', handleMatrixPointerDown);
            $timelineMatrix.off('pointermove', '.matrix-viewport', handleMatrixPointerMove);
            $timelineMatrix.off('pointerup pointerleave pointercancel', '.matrix-viewport', handleMatrixPointerUp);
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
                  // Seek to 5 seconds before the event
                  const seekTime = Math.max(0, sec - 5);
                  goToVideoTime(seekTime);
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

            $(document).on('keydown', handleGlobalEscape);

            if ($video.length) {
                  const persistTime = () => {
                        if (!window.localStorage || !$video.length) return;
                        const session = getDeskSession();
                        const t =
                              session && typeof session.getCurrentSecond === 'function'
                                    ? session.getCurrentSecond()
                                    : Math.floor($video[0].currentTime || 0);
                        window.localStorage.setItem(VIDEO_TIME_KEY, Math.floor(t));
                  };
                  $video.on('timeupdate seeked pause', persistTime);
                  $(window).on('beforeunload', persistTime);
            }
      }

      function init() {
            if ($jsBadge.length) {
                  $jsBadge.text('JS');
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
            if ($playlistPanel.length) {
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
                  $playlistRegenerateClipsBtn = $('#playlistRegenerateClipsBtn');
                  $playlistCreateToggle = $('#playlistCreateToggle');
                  $playlistCreateRow = $('#playlistCreateRow');
                  if ($playlistFilterPopover.length) {
                        $playlistFilterPopover.attr('hidden', 'hidden');
                  }
                  if ($playlistFilterBtn.length) {
                        $playlistFilterBtn.attr('aria-expanded', 'false');
                  }
            }
            setupTimeStepper($timeStepDown, -1);
            setupTimeStepper($timeStepUp, 1);
            bindHandlers();
            setupTimelineResizeObserver();
            if (annotationsEnabled) {
                  ensureAnnotationBridge();
            }
            acquireLock();
            updateClipUi();
            loadEvents();
            scheduleDeferredWork();
            emitClipPlaybackState();
      }

      $(init);
})(jQuery);
