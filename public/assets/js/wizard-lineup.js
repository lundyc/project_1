window.LINEUP_DEBUG = window.LINEUP_DEBUG ?? false;
const DEBUG_LINEUP_MODAL = false;
const DEBUG_SUBS = false;
const DEBUG_LINEUP_DUMP = false;
const SUBSTITUTION_DELETE_API_ERROR = 'Player cannot be removed because they were involved in a substitution.';
const SUBSTITUTION_DELETE_UI_MESSAGE = 'This player has substitution history and cannot be removed.';
const SUB_SVG = `<svg fill="currentColor" viewBox="0 0 20 20"
          data-testid="wcl-icon-incidents-substitution"
          class="lineup-sub-icon">
          <path fill-rule="evenodd"
                    d="m18.74 4.21-1.31 3.16a7.84 7.84 0 0 0-14.81-.18l1.28.46a6.47 6.47 0 0 1 12.28.3l-3.24-1.33-.52 1.26 5.36 2.22L20 4.74l-1.26-.53Z"
                    fill="#dc0000">
          </path>
          <path fill-rule="evenodd"
                    d="M10 16.3a6.47 6.47 0 0 1-6.18-4.58l3.24 1.34.53-1.26-5.37-2.22-.32.77-.2.49-1.7 4.1 1.26.52 1.31-3.15a7.84 7.84 0 0 0 14.81.17l-1.28-.46a6.49 6.49 0 0 1-6.1 4.29Z"
                    fill="#14dc4b">
          </path>
</svg>`;
const SUBSTITUTION_LOCK_TOOLTIP = 'Players with substitution history cannot be removed';
const LOCKED_CLASS = 'is-locked';

window.LINEUP_PAGE_MODE =
  document.body.dataset.lineupPage === 'true';
dbg(
  'Page mode:',
  window.LINEUP_PAGE_MODE ? 'LINEUP' : 'WIZARD'
);
if (!window.LINEUP_PAGE_MODE && window.LINEUP_DEBUG) {
  dbgWarn('[LINEUP] Page mode mismatch detected');
}

function dbg(...args) {
  if (window.LINEUP_DEBUG) {
    console.log('[LINEUP]', ...args);
  }
}

function dbgWarn(...args) {
  if (window.LINEUP_DEBUG) {
    console.warn('[LINEUP]', ...args);
  }
}

function dbgGroup(label, fn) {
  if (!window.LINEUP_DEBUG) {
    return;
  }
  console.group(label);
  try {
    fn();
  } finally {
    console.groupEnd();
  }
}

let domContentLogged = false;
let formationSelectorsInitializing = false;

function dumpLineupState(stage) {
  if (!DEBUG_LINEUP_DUMP) {
    return;
  }
  const pitch =
    document.querySelector('.formation-positions')
    || document.querySelector('.formation-pitch-content');
  const slots = Array.from(document.querySelectorAll('.lineup-formation-slot'));
  const hasPlayerSlots = slots.filter((slot) => slot.classList.contains('has-player'));
  const indicatorHasNumber = Array.from(document.querySelectorAll('.lineup-slot-indicator.has-number'));
  const nonEmptyNames = Array.from(document.querySelectorAll('.position-label-name')).filter(
    (el) => (el.textContent || '').trim().length > 0
  );
  const exampleSlot = hasPlayerSlots[0] || slots[0] || null;

  dbgGroup(`[LINEUP-DUMP] ${stage}`, () => {
    dbg('slots total:', slots.length);
    dbg('slots.has-player:', hasPlayerSlots.length);
    dbg('.lineup-slot-indicator.has-number:', indicatorHasNumber.length);
    dbg('non-empty .position-label-name:', nonEmptyNames.length);
    if (!pitch) {
      dbgWarn('pitch container not found (selectors may be wrong)');
    }
    if (exampleSlot) {
      dbg('example slot classes:', exampleSlot.className);
      dbg('example slot dataset:', { ...exampleSlot.dataset });
      const wrapper = exampleSlot.closest('.formation-position') || exampleSlot.parentElement;
      dbg('example slot wrapper:', wrapper);
      dbg('example slot outerHTML:', exampleSlot.outerHTML);
      if (wrapper) {
        dbg('example wrapper outerHTML:', wrapper.outerHTML);
      }
    } else {
      dbgWarn('no lineup slots found in DOM');
    }
  });

  setTimeout(() => {
    const slotsLater = Array.from(document.querySelectorAll('.lineup-formation-slot'));
    const hasPlayerLater = slotsLater.filter((slot) => slot.classList.contains('has-player')).length;
    const namesLater = Array.from(document.querySelectorAll('.position-label-name'))
      .filter((el) => (el.textContent || '').trim().length > 0).length;
    const numbersLater = Array.from(document.querySelectorAll('.lineup-slot-indicator.has-number')).length;
    dbg(`[LINEUP-DUMP] ${stage} (after 1s)`, {
      slots: slotsLater.length,
      hasPlayer: hasPlayerLater,
      names: namesLater,
      numbers: numbersLater,
    });
  }, 1000);
}

function handleLineupDomContentLoaded() {
  if (domContentLogged) {
    return;
  }
  domContentLogged = true;
  dbg('DOMContentLoaded fired');
  const initialPlayers = document.querySelectorAll('.player-dot[data-player-id]');
  dbg('Initial player dots found:', initialPlayers.length);
  initialPlayers.forEach((p) => {
    dbg('Initial player:', {
      playerId: p.dataset.playerId,
      slot: p.dataset.slotIndex,
      team: p.dataset.teamSide,
      el: p,
    });
  });
  const pitch =
    document.querySelector('.lineup-pitch')
    || document.querySelector('.lineup-formation .formation-pitch')
    || document.querySelector('.formation-pitch');
  if (!pitch) {
    dbg('MutationObserver not attached (pitch missing)');
    return;
  }
  const observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
      mutation.removedNodes.forEach((node) => {
        if (node.nodeType === 1 && node.classList.contains('player-dot')) {
          dbg('❌ Player node removed', {
            playerId: node.dataset.playerId,
            slot: node.dataset.slotIndex,
            team: node.dataset.teamSide,
            stack: new Error().stack,
          });
        }
      });
    });
  });
  observer.observe(pitch, { childList: true, subtree: false });
  dbg('MutationObserver attached to pitch');
  dumpLineupState('after DOMContentLoaded');
}

document.addEventListener('DOMContentLoaded', handleLineupDomContentLoaded);
if (document.readyState !== 'loading') {
  handleLineupDomContentLoaded();
}

(() => {
  const cfg = window.MatchWizardLineupConfig || {};
  const MATCH_ID = resolveMatchId();
  let matchId = MATCH_ID;
  if (matchId === null && cfg.matchId !== undefined) {
    const fallbackConfigId = parseNumberValue(cfg.matchId);
    if (fallbackConfigId !== null) {
      matchId = fallbackConfigId;
    }
  }
  let formationTimingContext = {
    match_second: cfg.formations?.timing?.match_second ?? 0,
    minute: cfg.formations?.timing?.minute ?? 0,
    minute_extra: cfg.formations?.timing?.minute_extra ?? 0,
    match_period_id: cfg.formations?.timing?.match_period_id ?? null,
  };
  const root =
    document.querySelector('.wizard-step-panel[data-step="4"]')
    || document.querySelector('[data-lineup-root]');
  if (!root) {
    return;
  }

  const rootDataset = root.dataset || {};
  const canEdit = cfg.canEdit !== false;
  root.classList.toggle('lineup-read-only', !canEdit);

  const lineupFlash = document.getElementById('lineupFlash');
  const homeLabel = document.getElementById('lineupHomeLabel');
  const awayLabel = document.getElementById('lineupAwayLabel');
  const statusBadge = document.getElementById('lineupStatusBadge');
  const overviewBtn = document.getElementById('lineupOverviewBtn');
  const deskBtn = document.getElementById('lineupDeskBtn');
  const createPlayerBtn = document.getElementById('lineupCreatePlayerBtn');
  const modal = document.getElementById('lineupCreatePlayerModal');
  const modalForm = document.getElementById('lineupCreatePlayerForm');
  const modalError = document.getElementById('lineupCreatePlayerError');
  const addButtons = Array.from(root.querySelectorAll('[data-lineup-add]'));
  const homeContainer = root.querySelector('[data-lineup-side="home"] .lineup-forms');
  const awayContainer = root.querySelector('[data-lineup-side="away"] .lineup-forms');
  const quickAddModal = document.getElementById('lineupQuickAddModal');
  const quickAddForm = document.getElementById('lineupQuickAddForm');
  const quickAddPlayerSelect = quickAddForm?.querySelector('select[name="player_id"]');
  const quickAddNumberInput = quickAddForm?.querySelector('input[name="shirt_number"]');
  const quickAddPositionInput = quickAddForm?.querySelector('input[name="position_label"]');
  const quickAddCaptainInput = quickAddForm?.querySelector('input[name="is_captain"]');
  const quickAddEntryInput = quickAddForm?.querySelector('input[name="lineup_entry_id"]');
  const quickAddTitle = document.getElementById('lineupQuickAddTitle');
  const quickAddSubmitButton = quickAddForm?.querySelector('[data-lineup-quick-add-submit]');
  const formationSelects = {
    home: root.querySelector('[data-lineup-formation-select][data-lineup-side="home"]'),
    away: root.querySelector('[data-lineup-formation-select][data-lineup-side="away"]'),
  };
  const formationPositionsContainers = {
    home: root.querySelector('[data-lineup-formation-positions][data-lineup-side="home"]'),
    away: root.querySelector('[data-lineup-formation-positions][data-lineup-side="away"]'),
  };
  const formationCaptionElements = {
    home: root.querySelector('[data-lineup-side="home"] [data-lineup-formation-name]'),
    away: root.querySelector('[data-lineup-side="away"] [data-lineup-formation-name]'),
  };
  const rawFormations = Array.isArray(cfg.formations?.list) ? cfg.formations.list : [];
  const formationDefinitions = rawFormations.reduce((collection, formation) => {
    const formationId = parseNumberValue(formation.id);
    if (!formationId) {
      return collection;
    }
    const positions = (Array.isArray(formation.positions) ? formation.positions : [])
      .map((position) => ({
        slot_index: parseNumberValue(position.slot_index) ?? 0,
        position_label: (position.position_label || '').trim(),
        left_percent: parseNumberValue(position.left_percent) ?? 0,
        bottom_percent: parseNumberValue(position.bottom_percent) ?? 0,
        rotation_deg: parseNumberValue(position.rotation_deg) ?? 0,
      }))
      .sort((a, b) => a.slot_index - b.slot_index);
    collection.push({
      id: formationId,
      format: formation.format || '',
      formation_key: formation.formation_key || '',
      label: formation.label || formation.formation_key || `Formation ${formationId}`,
      player_count: parseNumberValue(formation.player_count) ?? positions.length,
      is_fixed: Boolean(formation.is_fixed),
      positions,
    });
    return collection;
  }, []);
  formationDefinitions.sort((a, b) => (a.label || '').localeCompare(b.label || ''));
  const formationMap = new Map(formationDefinitions.map((formation) => [formation.id, formation]));
  const formationSelections = {
    home: parseNumberValue(cfg.formations?.matchFormations?.home),
    away: parseNumberValue(cfg.formations?.matchFormations?.away),
  };
  const formationUpdateUrl = cfg.formations?.selectUrl || null;
  const formationHasOptions = formationDefinitions.length > 0;
  let formationSlots = [];
  const form = document.getElementById('matchWizardForm');
  const teamSelects = {
    home: form ? form.elements['home_team_id'] : null,
    away: form ? form.elements['away_team_id'] : null,
  };
  const defaultTeamNames = { home: 'Home lineup', away: 'Away lineup' };
  const lineupState = window.LINEUP_STATE || {};

  function findDefinitionByFormatKey(format, formationKey) {
    if (!format || !formationKey) {
      return null;
    }
    return formationDefinitions.find((formation) => {
      return formation.format === format && formation.formation_key === formationKey;
    }) || null;
  }

  function hydrateFormationSelectionsFromState() {
    ['home', 'away'].forEach((side) => {
      const state = lineupState[side];
      if (!state || !state.formation_key) {
        return;
      }
      const definition = findDefinitionByFormatKey(state.format || '', state.formation_key);
      if (!definition) {
        return;
      }
      formationSelections[side] = definition.id;
      const select = formationSelects[side];
      if (select) {
        select.value = definition.id;
        const option = select.querySelector(`[value="${definition.id}"]`);
        if (option) {
          option.setAttribute('aria-selected', 'true');
          option.classList.add('is-selected');
        }
      }
    });
  }

  function parseNumberValue(value) {
    const parsed = Number(value);
    return Number.isFinite(parsed) ? parsed : null;
  }

  function resolveMatchId() {
    const fromWindow = parseNumberValue(window.MATCH_ID);
    if (fromWindow !== null) {
      return fromWindow;
    }
    const bodyDataset = document.body?.dataset;
    const fromBody = bodyDataset ? parseNumberValue(bodyDataset.matchId) : null;
    if (fromBody !== null) {
      return fromBody;
    }
    const root = document.getElementById('lineupRoot');
    const rootDataset = root?.dataset;
    const fromRoot = rootDataset ? parseNumberValue(rootDataset.matchId) : null;
    if (fromRoot !== null) {
      return fromRoot;
    }
    const fromPath = parseMatchIdFromPath();
    if (fromPath !== null) {
      return fromPath;
    }
    console.warn('[LINEUP] Match id is required but missing from the page.');
    return null;
  }

  function parseMatchIdFromPath() {
    const pathname = window.location.pathname || '';
    const segments = pathname.split('/').filter((segment) => segment.length > 0);
    for (let i = segments.length - 1; i >= 0; i -= 1) {
      const candidate = parseNumberValue(segments[i]);
      if (candidate !== null) {
        return candidate;
      }
    }
    return null;
  }

  const ROLE_PRIORITY_MAP = {
    GK: ['GK'],
    CB: ['CB'],
    LB: ['LB', 'LWB'],
    RB: ['RB', 'RWB'],
    CM: ['CM', 'DM', 'AM'],
    DM: ['DM', 'CM'],
    AM: ['AM', 'CM'],
    LM: ['LM', 'LW'],
    RM: ['RM', 'RW'],
    LW: ['LW', 'LM'],
    RW: ['RW', 'RM'],
    ST: ['ST', 'CF'],
  };

  function normalizeRoleLabel(value) {
    if (!value) {
      return '';
    }
    return String(value).trim().toUpperCase();
  }

  function getRoleCandidates(role) {
    const normalized = normalizeRoleLabel(role);
    if (!normalized) {
      return [];
    }
    const candidates = ROLE_PRIORITY_MAP[normalized];
    if (Array.isArray(candidates) && candidates.length > 0) {
      return Array.from(new Set(candidates.map(normalizeRoleLabel)));
    }
    return [normalized];
  }

  function parseStylePercent(value) {
    if (value === null || value === undefined) {
      return null;
    }
    const stringValue = String(value).trim();
    const stripped = stringValue.endsWith('%') ? stringValue.slice(0, -1) : stringValue;
    return parseNumberValue(stripped);
  }

  function getSlotVerticalPercent(slot) {
    if (!slot) {
      return 0;
    }
    const datasetValue = parseNumberValue(slot.dataset.bottomPercent);
    if (datasetValue !== null) {
      return datasetValue;
    }
    const styleValue = parseStylePercent(slot.style.bottom);
    if (styleValue !== null) {
      return styleValue;
    }
    return 0;
  }

  function createFormationSlotElement() {
    const slot = document.createElement('div');
    slot.className = 'formation-position on-pitch';
    slot.classList.add('player-dot');
    slot.innerHTML = `
                              <button class="formation-position-button player-select-button lineup-formation-slot" type="button" data-position-label="">
                                        <span class="lineup-slot-indicator" aria-hidden="true"><i class="fa-solid fa-plus"></i></span>
                                        <span class="lineup-slot-captain-badge" aria-hidden="true"></span>
                              </button>
                              <span class="position-label">
                                        <span class="position-label-name" aria-hidden="true"></span>
                                        <span class="position-label-role" data-default-label=""></span>
                              </span>`;
    return slot;
  }

  function ensureSlotElements(container, count) {
    if (!container) {
      return [];
    }
    const slots = Array.from(container.querySelectorAll('.formation-position'));
    while (slots.length < count) {
      const slot = createFormationSlotElement();
      container.appendChild(slot);
      slots.push(slot);
    }
    return slots;
  }

  function hideFormationSlots(container) {
    if (!container) {
      return;
    }
    const slots = Array.from(container.querySelectorAll('.formation-position'));
    slots.forEach((slot) => {
      slot.style.display = 'none';
      slot.dataset.positionLabel = '';
      slot.dataset.slotIndex = '';
    });
  }

  function movePlayerToSlot(el, slot) {
    if (!el || !slot) {
      return;
    }
    const left = parseNumberValue(slot.left_percent) ?? 0;
    const bottom = parseNumberValue(slot.bottom_percent) ?? 0;
    el.style.left = `${left}%`;
    el.style.bottom = `${bottom}%`;
    el.dataset.leftPercent = String(left);
    el.dataset.bottomPercent = String(bottom);
    const rotation = parseNumberValue(slot.rotation_deg) ?? 0;
    el.style.transform = `translate(-50%, 50%) rotate(${rotation}deg)`;
    el.dataset.rotationDeg = String(rotation);
    if (slot.slot_index !== undefined && slot.slot_index !== null) {
      el.dataset.slotIndex = String(slot.slot_index);
    } else {
      el.dataset.slotIndex = '';
    }
    const positionLabel = slot.position_label || '';
    el.dataset.positionLabel = positionLabel;
    const roleEl = el.querySelector('.position-label-role');
    if (roleEl) {
      const label = positionLabel || roleEl.dataset.defaultLabel || '';
      roleEl.textContent = label;
      if (positionLabel) {
        roleEl.dataset.defaultLabel = positionLabel;
      }
    }
    const button = el.querySelector('.lineup-formation-slot');
    if (button) {
      button.dataset.positionLabel = positionLabel;
    }
  }

  async function fetchFormationPositions(definition) {
    const fallback = Array.isArray(definition?.positions) ? definition.positions : [];
    if (!definition || !cfg.formations?.listUrl) {
      return fallback;
    }
    try {
      const baseUrl = cfg.formations.listUrl;
      const requestUrl = new URL(baseUrl, window.location.origin);
      if (definition.format) {
        requestUrl.searchParams.set('format', definition.format);
      }
      if (typeof definition.is_fixed === 'boolean') {
        requestUrl.searchParams.set('is_fixed', definition.is_fixed ? '1' : '0');
      }
      const data = await callJson(requestUrl.toString(), null, 'GET');
      const formations = Array.isArray(data.formations) ? data.formations : [];
      const match = formations.find((item) => Number(item.formation_id) === Number(definition.id));
      if (!match) {
        return fallback;
      }
      return Array.isArray(match.positions) ? match.positions : fallback;
    } catch (error) {
      console.error('Unable to fetch formation positions', error);
      return fallback;
    }
  }

  function matchPlayersToSlots(players, slots) {
    const availableSlots = slots.map((slot) => ({
      ...slot,
      normalizedRole: normalizeRoleLabel(slot.position_label),
      assigned: false,
    }));
    const assignments = [];
    players.forEach((player) => {
      const slot = findBestSlotForPlayer(player, availableSlots);
      if (slot) {
        slot.assigned = true;
        assignments.push({ player, slot });
      }
    });
    const unusedSlots = availableSlots.filter((slot) => !slot.assigned);
    return { assignments, unusedSlots };
  }

  function findBestSlotForPlayer(player, slots) {
    const candidateRoles = getRoleCandidates(player.role);
    for (const candidateRole of candidateRoles) {
      const match = slots.find(
        (slot) => !slot.assigned && slot.normalizedRole === candidateRole
      );
      if (match) {
        return match;
      }
    }
    return findNearestSlotFallback(player, slots);
  }

  function findNearestSlotFallback(player, slots) {
    let best = null;
    let bestDistance = Infinity;
    const playerRole = player.role;
    const playerBottom = Number.isFinite(player.bottom) ? player.bottom : 0;
    slots.forEach((slot) => {
      if (slot.assigned) {
        return;
      }
      if (playerRole === 'GK' && slot.normalizedRole !== 'GK') {
        return;
      }
      if (playerRole !== 'GK' && slot.normalizedRole === 'GK') {
        return;
      }
      const slotBottom = Number.isFinite(slot.bottom_percent)
        ? slot.bottom_percent
        : 0;
      const distance = Math.abs(playerBottom - slotBottom);
      if (distance < bestDistance) {
        bestDistance = distance;
        best = slot;
      }
    });
    return best;
  }

  async function applyFormationLayout(side, definition) {
    const beforeStage = `before applyFormationLayout(${side})`;
    dumpLineupState(beforeStage);
    const container = formationPositionsContainers[side];
    const afterStage = `after applyFormationLayout(${side})`;
    const playerButtons = new Map();
    document.querySelectorAll('.lineup-formation-slot.has-player').forEach((btn) => {
      const wrapper = btn.closest('.formation-position');
      if (!wrapper) {
        return;
      }
      const slotIndex = wrapper.dataset.slotIndex;
      playerButtons.set(slotIndex, {
        button: btn,
        wrapper,
        indicatorHTML: btn.querySelector('.lineup-slot-indicator')?.innerHTML,
        nameHTML: wrapper.querySelector('.position-label-name')?.innerHTML,
        captainVisible: btn
          .querySelector('.lineup-slot-captain-badge')
          ?.classList.contains('visible'),
      });
    });
    if (!container) {
      dumpLineupState(afterStage);
      return { layoutSnapshot: {} };
    }
    const positionsRaw = await fetchFormationPositions(definition);
    const normalizedPositions = (Array.isArray(positionsRaw) ? positionsRaw : [])
      .map((position, index) => ({
        slot_index: parseNumberValue(position.slot_index) ?? index,
        position_label: (position.position_label || '').trim(),
        left_percent: parseNumberValue(position.left_percent) ?? 0,
        bottom_percent: parseNumberValue(position.bottom_percent) ?? 0,
        rotation_deg: parseNumberValue(position.rotation_deg) ?? 0,
      }))
      .sort((a, b) => (a.slot_index ?? 0) - (b.slot_index ?? 0));
    if (!normalizedPositions.length) {
      hideFormationSlots(container);
      dumpLineupState(afterStage);
      return { layoutSnapshot: {} };
    }
    hydrateSlotCacheFromDOM(side);
    const cachedPlayerCount = slotElementCache[side].size;
    const requiredCount = Math.max(normalizedPositions.length, cachedPlayerCount);
    const slotElements = ensureSlotElements(container, requiredCount);
    slotElements.forEach((slot) => {
      slot.style.display = '';
    });
    const cache = slotElementCache[side];
    const playerSlots = Array.from(cache.values());
    cache.clear();
    const placeholderNodes = slotElements.filter((slot) => !slot.dataset.matchPlayerId);
    const playerDescriptors = playerSlots.map((slot) => {
      const entryId = parseNumberValue(slot.dataset.lineupEntryId);
      const matchPlayerId = parseNumberValue(slot.dataset.matchPlayerId);
      const entry = entryId ? lineupMap.get(entryId) : null;
      const preferenceRole = entry?.position_label || slot.dataset.positionLabel || '';
      return {
        element: slot,
        matchPlayerId,
        role: normalizeRoleLabel(preferenceRole),
        bottom: getSlotVerticalPercent(slot),
      };
    });
    const { assignments, unusedSlots } = matchPlayersToSlots(playerDescriptors, normalizedPositions);
    const layoutSnapshot = {};
    assignments.forEach(({ player, slot }) => {
      movePlayerToSlot(player.element, slot);
      if (
        player.matchPlayerId !== null &&
        slot.slot_index !== undefined &&
        slot.slot_index !== null
      ) {
        layoutSnapshot[String(slot.slot_index)] = player.matchPlayerId;
        cache.set(slot.slot_index, player.element);
      }
    });
    unusedSlots.forEach((slot, index) => {
      const placeholder = placeholderNodes[index];
      if (!placeholder) {
        return;
      }
      clearSlotPlayer(placeholder);
      movePlayerToSlot(placeholder, slot);
      placeholder.style.display = '';
    });
    placeholderNodes.slice(unusedSlots.length).forEach((slot) => {
      if (!slot.dataset.matchPlayerId) {
        slot.style.display = 'none';
        slot.dataset.positionLabel = '';
        slot.dataset.slotIndex = '';
      }
    });
    slotElements
      .slice(Math.max(normalizedPositions.length, playerSlots.length))
      .forEach((slot) => {
        if (!slot.dataset.matchPlayerId) {
          slot.style.display = 'none';
          slot.dataset.positionLabel = '';
          slot.dataset.slotIndex = '';
        }
      });
    // restore player UI that may have been reset while reflowing slots
    playerButtons.forEach((data) => {
      const { button, wrapper, indicatorHTML, nameHTML, captainVisible } = data;
      if (!button || !wrapper) {
        return;
      }
      const indicator = button.querySelector('.lineup-slot-indicator');
      if (indicator && indicatorHTML) {
        indicator.innerHTML = indicatorHTML;
        indicator.classList.add('has-number');
      }
      const nameEl = wrapper.querySelector('.position-label-name');
      if (nameEl && nameHTML) {
        nameEl.innerHTML = nameHTML;
      }
      const badge = button.querySelector('.lineup-slot-captain-badge');
      if (badge && captainVisible) {
        badge.classList.add('visible');
      }
      button.classList.add('has-player');
    });
    const missingDetails = Array.from(document.querySelectorAll('.lineup-formation-slot.has-player'))
      .filter((btn) => {
        const wrap = btn.closest('.formation-position');
        if (!wrap) {
          return true;
        }
        const name = wrap.querySelector('.position-label-name');
        const num = wrap.querySelector('.lineup-slot-indicator');
        return !name || !(name.textContent || '').trim() || !num;
      });
    if (missingDetails.length) {
      dbgWarn(
        'has-player slots missing details:',
        missingDetails.length,
        missingDetails.slice(0, 2)
      );
    }
    dumpLineupState(afterStage);
    document.querySelectorAll('.lineup-formation-slot.has-player').forEach((btn) => {
      const indicator = btn.querySelector('.lineup-slot-indicator');
      if (indicator && indicator.querySelector('.fa-plus')) {
        console.error('[LINEUP] Player slot incorrectly reset to +', btn);
      }
    });
    return { layoutSnapshot };
  }

  const originalApplyFormationLayout = applyFormationLayout;
  applyFormationLayout = async function (...args) {
    dbg('➡️ applyFormationLayout called', args);
    dbg('Players before apply:', document.querySelectorAll('.player-dot').length);
    try {
      const result = await originalApplyFormationLayout.apply(this, args);
      dbg('Players after apply:', document.querySelectorAll('.player-dot').length);
      return result;
    } catch (err) {
      dbg('🔥 JS ERROR (applyFormationLayout)', err);
      throw err;
    } finally {
      tryRenderSubsAfterLayout();
    }
  };

  const normalizeSide = (side) => (side === 'away' ? 'away' : 'home');
  const capitalizeSide = (value) => value.charAt(0).toUpperCase() + value.slice(1);

  const serverRenderedPlayers = { home: false, away: false };
  const slotElementCache = { home: new Map(), away: new Map() };
  const SERVER_PLAYER_SELECTOR = '.player-dot[data-player-id], .formation-position[data-lineup-player-id]';

  function hydrateSlotCacheFromDOM(side) {
    const container = formationPositionsContainers[side];
    if (!container) {
      return;
    }
    const map = slotElementCache[side];
    map.clear();
    const slots = container.querySelectorAll('.formation-position');
    slots.forEach((slot) => {
      const slotIndex = parseNumberValue(slot.dataset.slotIndex);
      const matchPlayerId = parseNumberValue(slot.dataset.matchPlayerId || slot.dataset.playerId);
      if (slotIndex !== null && matchPlayerId !== null) {
        map.set(slotIndex, slot);
      }
    });
  }

  function detectServerRenderedPlayers() {
    ['home', 'away'].forEach((side) => {
      const normalized = normalizeSide(side);
      const container = formationPositionsContainers[normalized];
      if (!container) {
        return;
      }
      const existingNode = container.querySelector(SERVER_PLAYER_SELECTOR);
      if (!existingNode) {
        return;
      }
      serverRenderedPlayers[normalized] = true;
      const slots = Array.from(container.querySelectorAll('.formation-position'));
      slots.forEach((slot, index) => {
        if (!slot.dataset.slotIndex) {
          slot.dataset.slotIndex = String(index);
        }
        if (!slot.dataset.teamSide) {
          slot.dataset.teamSide = normalized;
        }
        if (!slot.classList.contains('player-dot')) {
          slot.classList.add('player-dot');
        }
        const resolvedPlayerId =
          slot.dataset.lineupPlayerId
          || slot.dataset.playerId
          || slot.dataset.matchPlayerId
          || '';
        if (resolvedPlayerId) {
          slot.dataset.playerId = resolvedPlayerId;
          slot.dataset.matchPlayerId = resolvedPlayerId;
        }
      });
      hydrateSlotCacheFromDOM(normalized);
    });
  }

  function getDatasetKey(side, suffix) {
    const normalized = normalizeSide(side);
    return `lineup${capitalizeSide(normalized)}${suffix}`;
  }

  function getTeamId(side) {
    const select = teamSelects[side];
    if (select && select.value) {
      return select.value;
    }
    const datasetValue = rootDataset[getDatasetKey(side, 'TeamId')];
    return datasetValue || '';
  }

  function getTeamName(side) {
    const select = teamSelects[side];
    const label = select?.selectedOptions?.[0]?.textContent?.trim();
    if (label) {
      return label;
    }
    const datasetValue = rootDataset[getDatasetKey(side, 'TeamName')];
    return datasetValue || defaultTeamNames[side] || 'Lineup';
  }

  function escapeHtml(value) {
    if (value === null || value === undefined) {
      return '';
    }
    return String(value).replace(/[&<>"']/g, (char) => {
      const replacements = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;',
      };
      return replacements[char] || char;
    });
  }

  function getActiveFormationId(side) {
    const normalized = normalizeSide(side);
    const selected = formationSelections[normalized];
    if (selected && formationMap.has(selected)) {
      return selected;
    }
    if (formationDefinitions.length === 0) {
      return null;
    }
    return formationDefinitions[0].id;
  }

  function getFormationDefinitionForSide(side) {
    const id = getActiveFormationId(side);
    if (!id) {
      return null;
    }
    return formationMap.get(id) || null;
  }

  function updateFormationCaption(side) {
    const caption = formationCaptionElements[side];
    if (!caption) {
      return;
    }
    const definition = getFormationDefinitionForSide(side);
    caption.textContent = definition
      ? definition.label || definition.formation_key || 'Formation'
      : '—';
  }

  function refreshFormationSlots() {
    formationSlots = Array.from(root.querySelectorAll('.lineup-formation-slot'));
  }

  function refreshSlotAssignmentsAfterFormationChange() {
    lineupMap.clear();
    lineupEntries.forEach((entry) => {
      if (entry?.id) {
        lineupMap.set(entry.id, entry);
      }
    });
    slotAssignments.clear();
    formationSlots.forEach((slot) => clearSlotPlayer(slot));
    assignEntriesToSlots();
    syncSlotsWithEntries();
  }

  async function renderFormationForSide(side, options = {}) {
    const container = formationPositionsContainers[side];
    if (!container) {
      return {};
    }
    const definition = getFormationDefinitionForSide(side);
    if (!definition || !Array.isArray(definition.positions) || definition.positions.length === 0) {
      hideFormationSlots(container);
      refreshFormationSlots();
      updateFormationCaption(side);
      return {};
    }
    const pitchElement = container;
    if (options.initialLoad && serverRenderedPlayers[side]) {
      pitchElement.dataset.initialized = 'true';
      refreshFormationSlots();
      updateFormationCaption(side);
      return {};
    }
    if (options.initialLoad && pitchElement.dataset.initialized === 'true') {
      refreshFormationSlots();
      updateFormationCaption(side);
      return {};
    }
    try {
      dbg('before applyFormationLayout lineupMap.size', lineupMap.size);
      dbg(
        'before applyFormationLayout has-player slots',
        document.querySelectorAll('.lineup-formation-slot.has-player').length
      );
      const result = await applyFormationLayout(side, definition);
      pitchElement.dataset.initialized = 'true';
      refreshFormationSlots();
      updateFormationCaption(side);
      return result.layoutSnapshot || {};
    } catch (error) {
      console.error('Unable to render formation layout', error);
      refreshFormationSlots();
      updateFormationCaption(side);
      return {};
    }
  }

  function buildFormationSelectors() {
    formationSelectorsInitializing = true;
    try {
      ['home', 'away'].forEach((side) => {
        const select = formationSelects[side];
        if (!select) {
          return;
        }
        dbg('⚠️ Clearing element innerHTML (buildFormationSelectors)', {
          caller: new Error().stack,
          target: select,
        });
        select.innerHTML = '';
        if (!formationHasOptions) {
          const option = document.createElement('option');
          option.disabled = true;
          option.selected = true;
          option.textContent = 'No formations available';
          select.appendChild(option);
          select.disabled = true;
          return;
        }
        const activeId = getActiveFormationId(side);
        formationDefinitions.forEach((formation) => {
          const option = document.createElement('option');
          option.value = formation.id;
          option.textContent = formation.label;
          option.dataset.format = formation.format || '';
          option.dataset.formationKey = formation.formation_key || '';
          if (activeId !== null && formation.id === activeId) {
            option.selected = true;
          }
          select.appendChild(option);
        });
        select.value = activeId !== null ? activeId : '';
        select.disabled = false;
        select.addEventListener('change', () => handleFormationSelectChange(side, select.value));
      });
    } finally {
      formationSelectorsInitializing = false;
      dumpLineupState('after buildFormationSelectors');
    }
  }

  function handleFormationSelectChange(side, value, options = {}) {
    if (formationSelectorsInitializing) {
      dbgWarn('Ignoring formation change during selector init');
      return Promise.resolve({});
    }
    dbg('Formation change event fired', {
      value,
      time: performance.now(),
    });
    const normalized = normalizeSide(side);
    const selectedId = parseNumberValue(value);
    const definition = selectedId ? formationMap.get(selectedId) : null;
    if (!definition) {
      setFlash('danger', 'Unable to resolve formation');
      return Promise.resolve();
    }
    const previous = formationSelections[normalized];
    formationSelections[normalized] = selectedId;
    const payload = {
      teamSide: normalized,
      formationId: selectedId,
      format: definition.format,
      formationKey: definition.formation_key,
      initialLoad: options.initialLoad === true,
    };
    const action = onFormationSelected(payload);
    if (payload.initialLoad) {
      return action.catch((error) => {
        console.debug('Formation initialization error', error);
      });
    }
    return action.catch((error) => {
      if (error?.message) {
        setFlash('danger', error.message);
      } else {
        setFlash('danger', 'Unable to save formation');
      }
      formationSelections[normalized] = previous;
      const select = formationSelects[normalized];
      if (select) {
        select.value = previous ?? '';
      }
      renderFormationForSide(normalized);
    });
  }

  function persistFormationSelection(side, formationId, definition, layoutSnapshot = {}) {
    if (!formationUpdateUrl || !matchId) {
      return Promise.resolve();
    }
    if (!definition) {
      return Promise.resolve();
    }
    const payload = {
      match_id: matchId,
      team_side: side,
      match_period_id: formationTimingContext.match_period_id,
      match_second: formationTimingContext.match_second,
      minute: formationTimingContext.minute,
      minute_extra: formationTimingContext.minute_extra,
      formation_id: formationId,
      format: definition.format,
      formation_key: definition.formation_key,
      layout_json:
        layoutSnapshot && Object.keys(layoutSnapshot).length > 0
          ? JSON.stringify(layoutSnapshot)
          : null,
    };
    return callJson(formationUpdateUrl, payload).then((data) => {
      const returned = data.match_formations || {};
      const sidesToRender = new Set([side]);
      ['home', 'away'].forEach((target) => {
        if (!Object.prototype.hasOwnProperty.call(returned, target)) {
          return;
        }
        const rawValue = returned[target];
        const normalizedValue = parseNumberValue(rawValue);
        formationSelections[target] = normalizedValue;
        sidesToRender.add(target);
      });
      sidesToRender.forEach((target) => {
        const select = formationSelects[target];
        if (select) {
          select.value = formationSelections[target] ?? '';
        }
        renderFormationForSide(target);
      });
    });
  }

  async function renderPitch(payload) {
    dumpLineupState('before renderPitch');
    if (!payload || !payload.teamSide) {
      return {};
    }
    const normalized = normalizeSide(payload.teamSide);
    const result = await renderFormationForSide(normalized, payload);
    dumpLineupState('after renderPitch');
    return result;
  }

  const originalRenderPitch = renderPitch;
  renderPitch = async function (...args) {
    dbg('➡️ renderPitch called', args);
    dbg('Players before pitch render:', document.querySelectorAll('.player-dot').length);
    try {
      const result = await originalRenderPitch.apply(this, args);
      dbg('Players after pitch render:', document.querySelectorAll('.player-dot').length);
      return result;
    } catch (err) {
      dbg('🔥 JS ERROR (renderPitch)', err);
      throw err;
    }
  };

  function saveFormationChange(payload, layoutSnapshot = {}) {
    const definition = formationMap.get(payload.formationId) || null;
    return persistFormationSelection(payload.teamSide, payload.formationId, definition, layoutSnapshot);
  }

  function setFormationTimingContext(context = {}) {
    if (!context || typeof context !== 'object') {
      return;
    }
    const normalizedMatchSecond = parseNumberValue(context.match_second);
    if (normalizedMatchSecond !== null) {
      formationTimingContext.match_second = normalizedMatchSecond;
    }
    const normalizedMinute = parseNumberValue(context.minute);
    if (normalizedMinute !== null) {
      formationTimingContext.minute = normalizedMinute;
    }
    if (Object.prototype.hasOwnProperty.call(context, 'minute_extra')) {
      formationTimingContext.minute_extra = context.minute_extra;
    }
    if (Object.prototype.hasOwnProperty.call(context, 'match_period_id')) {
      formationTimingContext.match_period_id = context.match_period_id;
    }
  }

  async function onFormationSelected(payload) {
    const layoutSnapshot = await renderPitch(payload);
    return saveFormationChange(payload, layoutSnapshot);
  }

  function resolvePayloadDefinition(payload, normalized) {
    if (!payload) {
      return null;
    }
    const candidateId = parseNumberValue(payload.formationId);
    if (candidateId && formationMap.has(candidateId)) {
      return formationMap.get(candidateId);
    }
    if (payload.format && payload.formationKey) {
      const definition = findDefinitionByFormatKey(payload.format, payload.formationKey);
      if (definition) {
        return definition;
      }
    }
    const fallbackId = getActiveFormationId(normalized);
    if (fallbackId && formationMap.has(fallbackId)) {
      return formationMap.get(fallbackId);
    }
    return null;
  }

  function renderFormation(payload) {
    if (!payload || !payload.teamSide) {
      return Promise.resolve();
    }
    const normalized = normalizeSide(payload.teamSide);
    const definition = resolvePayloadDefinition(payload, normalized);
    if (!definition) {
      return Promise.resolve();
    }
    const select = formationSelects[normalized];
    if (select) {
      select.value = definition.id;
    }
    formationSelections[normalized] = definition.id;
    return handleFormationSelectChange(normalized, definition.id, { initialLoad: Boolean(payload.initialLoad) });
  }

  const originalRenderFormation = renderFormation;
  renderFormation = function (...args) {
    dbg('➡️ renderFormation called', args);
    dbg('Players before renderFormation:', document.querySelectorAll('.player-dot').length);
    let result;
    try {
      result = originalRenderFormation.apply(this, args);
    } catch (err) {
      dbg('🔥 JS ERROR (renderFormation)', err);
      throw err;
    }
    dbg('Players after renderFormation:', document.querySelectorAll('.player-dot').length);
    if (result && typeof result.catch === 'function') {
      return result.catch((err) => {
        dbg('🔥 JS ERROR (renderFormation)', err);
        throw err;
      });
    }
    return result;
  };

  function buildInitialFormationPayload(side) {
    const normalized = normalizeSide(side);
    const state = lineupState[normalized];
    const payload = {
      teamSide: normalized,
      initialLoad: true,
    };
    if (state?.format) {
      payload.format = state.format;
    }
    if (state?.formation_key) {
      payload.formationKey = state.formation_key;
    }
    const selection = formationSelections[normalized];
    if (selection) {
      payload.formationId = selection;
    }
    return payload;
  }

  function setupFormations() {
    if (window.__lineupFormationsSetupDone) {
      return;
    }
    window.__lineupFormationsSetupDone = true;
    detectServerRenderedPlayers();
    buildFormationSelectors();
    hydrateFormationSelectionsFromState();
    ['home', 'away'].forEach((side) => {
      const payload = buildInitialFormationPayload(side);
      renderFormation(payload).catch((error) => {
        console.debug('Initial formation render failed', error);
      });
    });
  }

  const originalSetupFormations = setupFormations;
  setupFormations = function (...args) {
    dbg('➡️ setupFormations called', args);
    try {
      return originalSetupFormations.apply(this, args);
    } catch (err) {
      dbg('🔥 JS ERROR (setupFormations)', err);
      throw err;
    }
  };

  const originalBuildFormationSelectors = buildFormationSelectors;
  buildFormationSelectors = function (...args) {
    dbg('➡️ buildFormationSelectors called', args);
    try {
      const result = originalBuildFormationSelectors.apply(this, args);
      dbg('Players after build:', document.querySelectorAll('.player-dot').length);
      return result;
    } catch (err) {
      dbg('🔥 JS ERROR (buildFormationSelectors)', err);
      throw err;
    }
  };
  const substituteAddButtons = Array.from(root.querySelectorAll('[data-lineup-sub-add]'));
  const makeSubButtons = Array.from(root.querySelectorAll('[data-lineup-make-sub]'));
  const substituteModal = document.getElementById('lineupSubstituteModal');
  const substituteForm = document.getElementById('lineupSubstituteForm');
  const substitutePlayerSelect = substituteForm?.querySelector('select[name="player_id"]');
  const substituteNumberInput = substituteForm?.querySelector('input[name="shirt_number"]');
  const substituteEntryInput = substituteForm?.querySelector('input[name="lineup_entry_id"]');
  const substituteSideInput = substituteForm?.querySelector('input[name="team_side"]');
  const substituteTitle = document.getElementById('lineupSubstituteTitle');
  const substituteSubtitle = document.getElementById('lineupSubstituteSubtitle');
  const substituteListHome = document.getElementById('lineupSubstitutesHome');
  const substituteListAway = document.getElementById('lineupSubstitutesAway');
  const substitutionModal = document.getElementById('lineupSubstitutionModal');
  if (substitutionModal && typeof substitutionModal.inert !== 'undefined') {
    substitutionModal.inert = true;
  }
  const substitutionForm = document.getElementById('lineupSubstitutionForm');
  const substitutionOffList = substitutionModal?.querySelector('[data-sub-list="off"]');
  const substitutionOnList = substitutionModal?.querySelector('[data-sub-list="on"]');
  const substitutionMinuteInput = substitutionModal?.querySelector('input[name="minute"]');
  const substitutionMinuteExtraInput = substitutionModal?.querySelector('input[name="minute_extra"]');
  const substitutionReasonSelect = substitutionModal?.querySelector('select[name="reason"]');
  const substitutionSubmitButton = substitutionModal?.querySelector('[data-lineup-substitution-submit]');
  const substitutionTeamInput = substitutionForm?.querySelector('input[name="team_side"]');
  const quickAddCreateLink = quickAddModal?.querySelector('[data-lineup-quick-add-create]');

  let players = [];
  let lineupEntries = [];
  const addForms = {
    home: null,
    away: null,
  };
  let quickAddSide = null;
  let quickAddPosition = '';
  let quickAddTargetSlot = null;
  let quickAddReturnState = null;
  let quickAddReturnNewPlayerId = null;
  let quickAddEditMode = false;
  let substituteTargetSide = null;
  const slotAssignments = new Map();
  const lineupMap = new Map();
  let lineupMapReady = false;
  let subsRendered = false;
  let activeSlotMenuSlot = null;
  let lastSubstitutionModalOpener = null;
  let substitutions = [];
  let subIncidentByMatchPlayerId = new Map();
  let subbedPlayerIds = new Set();

  function buildSubTooltip(minuteLabel, otherName) {
    return `${minuteLabel} Player subbed with ${otherName}`;
  }

  function renderSubIcon(tooltipText) {
    const template = document.createElement('template');
    template.innerHTML = SUB_SVG;
    const svg = template.content.firstElementChild;
    if (!svg) {
      return null;
    }

    // Extract minute from tooltip (e.g., "45'" from "45' Player subbed with ...")
    const minuteMatch = tooltipText.match(/^(\d+\+?\d*')/);
    const minute = minuteMatch ? minuteMatch[1] : '';

    // Create container for icon and time
    const container = document.createElement('div');
    container.classList.add('lineup-sub-icon-container');
    container.setAttribute('title', tooltipText);
    container.style.display = 'flex';
    container.style.flexDirection = 'column';
    container.style.alignItems = 'center';
    container.style.gap = '2px';

    // Clone and add the SVG
    const icon = svg.cloneNode(true);
    icon.classList.add('lineup-sub-icon');
    icon.style.width = '20px';
    icon.style.height = '20px';
    container.appendChild(icon);

    // Add minute label below the icon
    if (minute) {
      const minuteLabel = document.createElement('span');
      minuteLabel.classList.add('lineup-sub-minute');
      minuteLabel.textContent = minute;
      minuteLabel.style.fontSize = '13px';
      minuteLabel.style.fontWeight = '600';
      minuteLabel.style.lineHeight = '1';
      container.appendChild(minuteLabel);
    }

    return container;
  }

  function formatMinuteLabel(minuteValue, minuteExtraValue) {
    const base = Number.isFinite(Number(minuteValue)) ? String(Number(minuteValue)) : '0';
    const extra = Number.isFinite(Number(minuteExtraValue)) ? Number(minuteExtraValue) : 0;
    return `${base}${extra > 0 ? `+${extra}` : ''}'`;
  }

  function getIncidentForMatchPlayer(matchPlayerId) {
    const id = Number(matchPlayerId);
    if (!Number.isFinite(id)) {
      return null;
    }
    return subIncidentByMatchPlayerId.get(id) || null;
  }

  function isPlayerSubbed(matchPlayerId) {
    const id = Number(matchPlayerId);
    if (!Number.isFinite(id)) {
      return false;
    }
    return subbedPlayerIds.has(id);
  }

  function buildSubbedPlayerIds() {
    const set = new Set();
    substitutions.forEach((record) => {
      const offId = Number(record.player_off_match_player_id);
      const onId = Number(record.player_on_match_player_id);
      if (Number.isFinite(offId)) {
        set.add(offId);
      }
      if (Number.isFinite(onId)) {
        set.add(onId);
      }
    });
    subbedPlayerIds = set;
  }

  async function loadSubstitutions(targetMatchId = matchId) {
    substitutions = [];
    subIncidentByMatchPlayerId = new Map();
    subbedPlayerIds = new Set();
    const resolvedMatchId = parseNumberValue(targetMatchId);
    if (!cfg.matchSubstitutions?.list || resolvedMatchId === null) {
      return;
    }
    const url = `${cfg.matchSubstitutions.list}?match_id=${encodeURIComponent(resolvedMatchId)}`;
    if (!url) {
      return;
    }
    try {
      const data = await callJson(url, null, 'GET');
      substitutions = Array.isArray(data.substitutions) ? data.substitutions : [];
    } catch (error) {
      console.error('[SUB]', error);
      substitutions = [];
    }
  }

  function buildSubstitutionIncidents() {
    const entriesMap = new Map(
      lineupEntries
        .filter((entry) => entry && entry.id)
        .map((entry) => [Number(entry.id), entry])
    );
    const map = new Map();
    substitutions.forEach((record) => {
      const offId = Number(record.player_off_match_player_id);
      const onId = Number(record.player_on_match_player_id);
      if (!Number.isFinite(offId) || !Number.isFinite(onId)) {
        return;
      }
      const minuteLabel = formatMinuteLabel(record.minute, record.minute_extra);
      const offEntry = entriesMap.get(offId) || null;
      const onEntry = entriesMap.get(onId) || null;
      const offName = offEntry?.display_name || offEntry?.name || 'Unknown';
      const onName = onEntry?.display_name || onEntry?.name || 'Unknown';
      map.set(offId, {
        minuteLabel,
        otherName: onName,
        direction: 'off',
      });
      map.set(onId, {
        minuteLabel,
        otherName: offName,
        direction: 'on',
      });
    });
    subIncidentByMatchPlayerId = map;
    buildSubbedPlayerIds();
  }

  function renderSlotSubIcon(slot, incident) {
    if (!slot) {
      return;
    }
    const nameEl = slot.querySelector('.position-label-name');
    if (!nameEl) {
      return;
    }
    const existingIcon = slot.querySelector('.lineup-sub-icon');
    if (existingIcon) {
      existingIcon.remove();
    }
    if (incident) {
      const tooltip = buildSubTooltip(incident.minuteLabel, incident.otherName);
      const icon = renderSubIcon(tooltip);
      nameEl.insertAdjacentElement('afterend', icon);
    }
    const hasIncident = Boolean(incident);
    slot.classList.toggle(LOCKED_CLASS, hasIncident);
    if (hasIncident) {
      slot.dataset.subLocked = '1';
    } else {
      slot.removeAttribute('data-sub-locked');
    }
    syncSlotDeleteControl(slot, hasIncident);
  }

  function renderSubRowIcon(row, incident) {
    if (!row) {
      return;
    }
    const nameEl = row.querySelector('.lineup-substitute-name');
    if (!nameEl) {
      return;
    }
    const existingWrapper = row.querySelector('.lineup-sub-icon');
    if (existingWrapper) {
      existingWrapper.remove();
    }
    const existingSvg = nameEl.querySelector('[data-testid="wcl-icon-incidents-substitution"]');
    if (existingSvg) {
      existingSvg.remove();
    }
    if (incident) {
      const tooltip = `${incident.minuteLabel} Subbed with ${incident.otherName}`;
      const icon = renderSubIcon(tooltip);
      if (icon) {
        nameEl.appendChild(icon);
      }
    }
    const hasIncident = Boolean(incident);
    row.classList.toggle(LOCKED_CLASS, hasIncident);
    if (hasIncident) {
      row.dataset.subLocked = '1';
    } else {
      row.removeAttribute('data-sub-locked');
    }
    syncSubRowDeleteControl(row, hasIncident);
  }

  function renderSubstitutionIncidents() {
    document.querySelectorAll('.formation-position').forEach((slot) => {
      const entryId = parseNumberValue(slot.dataset.lineupEntryId);
      const incident = entryId !== null ? getIncidentForMatchPlayer(entryId) : null;
      renderSlotSubIcon(slot, incident);
    });
    document.querySelectorAll('.lineup-substitute-row').forEach((row) => {
      const matchPlayerId = parseNumberValue(row.dataset.matchPlayerId);
      const incident = matchPlayerId !== null
        ? getIncidentForMatchPlayer(matchPlayerId)
        : null;
      renderSubRowIcon(row, incident);
    });
  }

  let flashTimeoutId = null;

  function setFlash(type, message, autoHideMs = 0) {
    if (!lineupFlash) return;
    lineupFlash.classList.remove('d-none', 'alert-danger', 'alert-success', 'alert-info', 'alert-warning');
    const classMap = {
      success: 'alert-success',
      info: 'alert-info',
      warning: 'alert-warning',
      danger: 'alert-danger',
    };
    const alertClass = classMap[type] || classMap.danger;
    lineupFlash.classList.add('alert', alertClass);
    lineupFlash.textContent = message;
    if (flashTimeoutId) {
      clearTimeout(flashTimeoutId);
      flashTimeoutId = null;
    }
    if (autoHideMs > 0) {
      flashTimeoutId = window.setTimeout(() => {
        clearFlash();
        flashTimeoutId = null;
      }, autoHideMs);
    }
  }

  function clearFlash() {
    if (!lineupFlash) return;
    lineupFlash.classList.add('d-none');
    dbg('⚠️ Clearing textContent (clearFlash)', {
      caller: new Error().stack,
      target: lineupFlash,
    });
    lineupFlash.textContent = '';
    if (flashTimeoutId) {
      clearTimeout(flashTimeoutId);
      flashTimeoutId = null;
    }
  }

  function isSubstitutionDeleteError(error) {
    return error && typeof error.message === 'string' && error.message === SUBSTITUTION_DELETE_API_ERROR;
  }

  function showWarningFlash(message) {
    setFlash('warning', message, 4000);
  }

  function updateBadge(state, message) {
    if (!statusBadge) return;
    statusBadge.className = 'wizard-status';
    if (state === 'ready') {
      statusBadge.classList.add('wizard-status-success');
      statusBadge.textContent = message || 'Lineup ready';
    } else if (state === 'error') {
      statusBadge.classList.add('wizard-status-failed');
      statusBadge.textContent = message || 'Error';
    } else if (state === 'waiting') {
      statusBadge.classList.add('wizard-status-pending');
      statusBadge.textContent = message || 'Waiting';
    } else {
      statusBadge.classList.add('wizard-status-active');
      statusBadge.textContent = message || 'Loading';
    }
  }

  async function callJson(url, payload = null, method = 'POST') {
    const options = {
      method,
      headers: {
        Accept: 'application/json',
      },
    };
    if (payload !== null) {
      options.headers['Content-Type'] = 'application/json';
      options.body = JSON.stringify(payload);
    }
    const res = await fetch(url, options);
    const data = await res.json().catch(() => ({}));
    if (!res.ok || !data.ok) {
      const error = data.error || 'Request failed';
      throw new Error(error);
    }
    return data;
  }

  function updateTeamLabels() {
    if (homeLabel) {
      homeLabel.textContent = getTeamName('home');
    }
    if (awayLabel) {
      awayLabel.textContent = getTeamName('away');
    }
  }

  function formatPath(template, overrideMatchId) {
    if (!template) {
      return null;
    }
    const resolvedId = parseNumberValue(overrideMatchId ?? matchId);
    if (resolvedId === null) {
      return template;
    }
    return template.replace('{match_id}', encodeURIComponent(resolvedId));
  }

  function updateNavigationLinks() {
    if (overviewBtn) {
      const overviewPath = matchId && cfg.overviewPathTemplate ? formatPath(cfg.overviewPathTemplate) : null;
      if (overviewPath) {
        overviewBtn.classList.remove('disabled');
        overviewBtn.setAttribute('aria-disabled', 'false');
        overviewBtn.setAttribute('href', overviewPath);
      } else {
        overviewBtn.classList.add('disabled');
        overviewBtn.setAttribute('aria-disabled', 'true');
        overviewBtn.setAttribute('href', '#');
      }
    }
    if (deskBtn) {
      const deskPath = matchId && cfg.analysisDeskPathTemplate ? formatPath(cfg.analysisDeskPathTemplate) : null;
      if (deskPath) {
        deskBtn.classList.remove('disabled');
        deskBtn.setAttribute('aria-disabled', 'false');
        deskBtn.setAttribute('href', deskPath);
      } else {
        deskBtn.classList.add('disabled');
        deskBtn.setAttribute('aria-disabled', 'true');
        deskBtn.setAttribute('href', '#');
      }
    }
  }

  function updateAddButtons() {
    const disabled = !canEdit || !matchId || players.length === 0;
    addButtons.forEach((btn) => {
      btn.disabled = disabled;
    });
    substituteAddButtons.forEach((btn) => {
      btn.disabled = disabled;
    });
    makeSubButtons.forEach((btn) => {
      btn.disabled = disabled;
    });
  }

  function buildPlayerOptions(side, targetSelect) {
    if (!targetSelect) {
      return;
    }
    dbg('⚠️ Clearing element innerHTML (buildPlayerOptions)', {
      caller: new Error().stack,
      target: targetSelect,
    });
    targetSelect.innerHTML = '';
    const defaultOption = document.createElement('option');
    defaultOption.value = '';
    defaultOption.textContent = 'Select player';
    targetSelect.appendChild(defaultOption);
    const takenIds = new Set(lineupEntries.map((entry) => entry.player_id).filter(Boolean));
    players.forEach((player) => {
      if (takenIds.has(player.id)) {
        return;
      }
      const option = document.createElement('option');
      option.value = player.id;
      option.textContent = player.display_name;
      targetSelect.appendChild(option);
    });
  }

  function renderSlotPlayer(slot, entry) {
    if (!slot || !entry) {
      return;
    }
    slot.classList.add('has-player');
    slot.dataset.lineupEntryId = entry.id;
    slot.dataset.lineupPlayerId = entry.player_id ?? '';
    slot.dataset.matchPlayerId = entry.player_id ?? '';
    updateSlotIndicator(slot, entry);
    updateSlotPositionLabel(slot, entry);
    updateSlotCaptainBadge(slot, entry);
    const matchPlayerId = parseNumberValue(entry.id);
    const incident = matchPlayerId !== null ? getIncidentForMatchPlayer(matchPlayerId) : null;
    renderSlotSubIcon(slot, incident);
  }

  function clearSlotPlayer(slot) {
    if (!slot) {
      return;
    }
    slot.classList.remove('has-player');
    slot.removeAttribute('data-lineup-entry-id');
    slot.removeAttribute('data-lineup-player-id');
    slot.removeAttribute('data-match-player-id');
    updateSlotIndicator(slot, null);
    updateSlotPositionLabel(slot, null);
    updateSlotCaptainBadge(slot, null);
    const menu = slot.querySelector('.lineup-slot-menu');
    if (menu) {
      menu.classList.remove('active');
    }
  }

  function updateSlotIndicator(slot, entry) {
    const indicator = slot.querySelector('.lineup-slot-indicator');
    if (!indicator) {
      return;
    }
    if (entry) {
      const number = entry.shirt_number ?? '—';
      indicator.textContent = number;
      indicator.classList.add('has-number');
    } else {
      indicator.innerHTML = '<i class="fa-solid fa-plus"></i>';
      indicator.classList.remove('has-number');
    }
  }

  function updateSlotPositionLabel(slotEl, player) {
    if (window.LINEUP_PAGE_MODE && !player) {
      return;
    }
    const container = slotEl.closest('.formation-position');
    if (!container) {
      return;
    }
    const nameEl = container.querySelector('.position-label-name');
    const roleEl = container.querySelector('.position-label-role');
    const defaultText = roleEl?.dataset.defaultLabel || '';
    if (!player) {
      if (nameEl) {
        nameEl.textContent = '';
      }
      if (roleEl) {
        roleEl.textContent = defaultText;
      }
      return;
    }
    const positionText = (player.position_label || '').trim();
    const displayName = player.display_name || player.name || '';
    if (nameEl) {
      nameEl.textContent = displayName;
    }
    if (roleEl) {
      const labelText = positionText || defaultText;
      roleEl.textContent = labelText;
      if (positionText) {
        roleEl.dataset.defaultLabel = positionText;
      }
    }
  }

  function updateSlotCaptainBadge(slotEl, player) {
    const badge = slotEl.querySelector('.lineup-slot-captain-badge');
    if (!badge) {
      return;
    }
    if (window.LINEUP_PAGE_MODE && !player) {
      return;
    }
    if (!player || !player.is_captain) {
      badge.textContent = '';
      badge.classList.remove('visible');
      return;
    }
    badge.textContent = 'C';
    badge.classList.add('visible');
  }

  function assignEntriesToSlots() {
    lineupEntries.forEach((entry) => {
      const slot = findSlotForEntry(entry);
      if (!slot || slot.dataset.lineupEntryId) {
        return;
      }
      assignSlotToEntry(slot, entry);
    });
  }

  function findSlotForEntry(entry) {
    if (!entry.position_label) {
      return null;
    }
    const targetLabel = entry.position_label.trim().toLowerCase();
    const entrySide = (entry.team_side || 'home').toLowerCase();
    return formationSlots.find((slot) => {
      if (slot.dataset.lineupEntryId) {
        return false;
      }
      const cardSide = slot.closest('.lineup-card')?.dataset.lineupSide || '';
      if (cardSide.toLowerCase() !== entrySide) {
        return false;
      }
      const slotLabel = (slot.dataset.positionLabel || '').trim().toLowerCase();
      return slotLabel === targetLabel;
    }) || null;
  }

  function assignSlotToEntry(slot, entry) {
    if (!slot || !entry) {
      return;
    }
    for (const [id, existingSlot] of slotAssignments.entries()) {
      if (id === entry.id && existingSlot !== slot) {
        clearSlotPlayer(existingSlot);
        slotAssignments.delete(id);
      }
    }
    slotAssignments.set(entry.id, slot);
    lineupMap.set(entry.id, entry);
    renderSlotPlayer(slot, entry);
  }

  function clearSlotForEntry(entryId) {
    const slot = slotAssignments.get(entryId);
    if (slot) {
      clearSlotPlayer(slot);
      slotAssignments.delete(entryId);
    }
  }

  function syncSlotsWithEntries() {
    const toRemove = [];
    for (const [entryId, slot] of slotAssignments.entries()) {
      const entry = lineupMap.get(entryId);
      if (entry) {
        renderSlotPlayer(slot, entry);
      } else {
        clearSlotPlayer(slot);
        toRemove.push(entryId);
      }
    }
    toRemove.forEach((id) => slotAssignments.delete(id));
  }

  function syncSlotDeleteControl(slot, locked) {
    const deleteButton = slot.querySelector('[data-lineup-slot-delete]');
    if (!deleteButton) {
      return;
    }
    deleteButton.disabled = locked;
    deleteButton.title = locked ? SUBSTITUTION_LOCK_TOOLTIP : '';
  }

  function createSlotMenu(slot) {
    let menu = slot.querySelector('.lineup-slot-menu');
    if (!menu) {
      menu = document.createElement('div');
      menu.className = 'lineup-slot-menu';
      menu.innerHTML = `
                              <button type="button" data-lineup-slot-action="edit">Edit</button>
                              <button type="button" data-lineup-slot-action="delete" data-lineup-slot-delete>Delete</button>
                    `;
      slot.appendChild(menu);
      menu.querySelector('[data-lineup-slot-action="edit"]').addEventListener('click', (event) => {
        event.stopPropagation();
        handleSlotEdit(slot);
        closeSlotActionMenu();
      });
      menu.querySelector('[data-lineup-slot-action="delete"]').addEventListener('click', (event) => {
        event.stopPropagation();
        handleSlotDelete(slot);
        closeSlotActionMenu();
      });
      syncSlotDeleteControl(slot, slot.classList.contains(LOCKED_CLASS));
    }
    return menu;
  }

  function showSlotActionMenu(slot) {
    const entryId = slot.dataset.lineupEntryId;
    if (!entryId) {
      return;
    }
    const menu = createSlotMenu(slot);
    closeSlotActionMenu();
    menu.classList.add('active');
    activeSlotMenuSlot = slot;
  }

  function closeSlotActionMenu() {
    if (!activeSlotMenuSlot) {
      return;
    }
    const menu = activeSlotMenuSlot.querySelector('.lineup-slot-menu');
    if (menu) {
      menu.classList.remove('active');
    }
    activeSlotMenuSlot = null;
  }

  function handleSlotEdit(slot) {
    const entryId = slot.dataset.lineupEntryId ? Number(slot.dataset.lineupEntryId) : null;
    if (!entryId) {
      return;
    }
    const entry = lineupMap.get(entryId);
    if (!entry) {
      return;
    }
    openQuickAddModal(slot, entry);
  }

  function handleSlotDelete(slot) {
    const entryId = slot.dataset.lineupEntryId ? Number(slot.dataset.lineupEntryId) : null;
    if (!entryId) {
      return;
    }
    if (slot.classList.contains(LOCKED_CLASS)) {
      showWarningFlash(SUBSTITUTION_DELETE_UI_MESSAGE);
      return;
    }
    if (window.confirm('Remove this player from the slot?')) {
      deleteLineupEntry(entryId).then(() => {
        clearSlotForEntry(entryId);
      }).catch((error) => {
        const errMessage = isSubstitutionDeleteError(error)
          ? SUBSTITUTION_DELETE_UI_MESSAGE
          : (error.message || 'Unable to remove player');
        if (isSubstitutionDeleteError(error)) {
          showWarningFlash(errMessage);
        } else {
          setFlash('danger', errMessage);
        }
      });
    }
  }

  function handleSlotClick(slot) {
    if (!canEdit) {
      return;
    }
    closeSlotActionMenu();
    const entryId = slot.dataset.lineupEntryId;
    if (entryId) {
      showSlotActionMenu(slot);
    } else {
      const card = slot.closest('.lineup-card');
      const side = card?.dataset.lineupSide || '';
      const slotIndex = slot.dataset.slotIndex || '';
      const positionLabel = slot.dataset.positionLabel || '';
      dbg('[MODAL] open', {
        side,
        slotIndex,
        positionLabel,
      });
      openQuickAddModal(slot);
    }
  }

  function closeAddForm(side) {
    if (addForms[side]) {
      addForms[side].remove();
      addForms[side] = null;
    }
  }

  function renderLineup(entries) {
    lineupEntries = Array.isArray(entries) ? entries : [];
    lineupEntries.sort((a, b) => ((a.id ?? 0) - (b.id ?? 0)));
    lineupMap.clear();
    slotAssignments.clear();
    formationSlots.forEach((slot) => clearSlotPlayer(slot));
    lineupEntries.forEach((entry) => {
      lineupMap.set(entry.id, entry);
    });
    lineupMapReady = true;
    subsRendered = false;
    const previewEntries = lineupEntries.slice(0, 3).map((entry) => ({
      id: entry.id,
      player_id: entry.player_id,
      name: entry.name || entry.display_name || '',
      shirt_number: entry.shirt_number,
      is_captain: entry.is_captain,
    }));
    dbg('lineupMap populated size', lineupMap.size);
    dbg('lineupMap first entries', previewEntries);
    assignEntriesToSlots();
    closeAddForm('home');
    closeAddForm('away');
    buildFormsFromState();
    syncSlotsWithEntries();
    ['home', 'away'].forEach((side) =>
      renderPitch({ teamSide: side }).catch((error) =>
        dbg('Unable to refresh formation after lineup update', error)
      )
    );
  }

  function buildFormsFromState() {
    ['home', 'away'].forEach((side) => {
      if (addForms[side]) {
        const select = addForms[side].querySelector('select[name="player_id"]');
        buildPlayerOptions(side, select);
      }
    });
  }

  function getAssignedPlayerIdsForSide(side) {
    if (!side) {
      return new Set();
    }
    const normalized = side.toLowerCase();
    return new Set(
      lineupEntries
        .filter((entry) => ((entry.team_side || '').toLowerCase() === normalized) && entry.player_id)
        .map((entry) => Number(entry.player_id))
    );
  }

  function normalizePositionKey(value) {
    const normalized = (value || '').trim().toLowerCase();
    return normalized || 'zzz';
  }

  function getStartingPlayerIdsForSide(side, includePlayerId = null) {
    const normalized = (side || '').toLowerCase();
    const ids = new Set(
      (Array.isArray(lineupEntries) ? lineupEntries : [])
        .filter((entry) => {
          const entrySide = (entry.team_side || '').toLowerCase();
          return (
            entrySide === normalized &&
            Number(entry.is_starting) === 1 &&
            entry.player_id
          );
        })
        .map((entry) => Number(entry.player_id))
    );
    if (includePlayerId) {
      ids.delete(Number(includePlayerId));
    }
    return ids;
  }

  function populateQuickAddPlayerSelect(side, includePlayerId = null) {
    if (!quickAddPlayerSelect) {
      return;
    }
    quickAddPlayerSelect.innerHTML = '';
    const placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = 'Select player';
    quickAddPlayerSelect.appendChild(placeholder);

    if (!Array.isArray(players) || players.length === 0) {
      quickAddPlayerSelect.disabled = true;
      const option = document.createElement('option');
      option.value = '';
      option.textContent = 'Loading players…';
      quickAddPlayerSelect.appendChild(option);
      return;
    }

    if (DEBUG_LINEUP_MODAL) {
      dbg('[MODAL] available players:', players.length);
    }

    const teamIdValue = getTeamId(side);
    const teamId = teamIdValue ? Number(teamIdValue) : null;
    let candidates = teamId
      ? players.filter((player) => player.team_id && Number(player.team_id) === teamId)
      : players.slice();
    if (teamId && candidates.length === 0) {
      candidates = players.slice();
    }
    const excludeIds = getStartingPlayerIdsForSide(side, includePlayerId);
    const filtered = candidates.filter((player) => !excludeIds.has(Number(player.id)));
    filtered.sort((a, b) => {
      const posA = normalizePositionKey(a.primary_position);
      const posB = normalizePositionKey(b.primary_position);
      if (posA !== posB) {
        return posA.localeCompare(posB);
      }
      const nameA = (a.display_name || '').toLowerCase();
      const nameB = (b.display_name || '').toLowerCase();
      return nameA.localeCompare(nameB);
    });

    if (filtered.length === 0) {
      quickAddPlayerSelect.disabled = true;
      const option = document.createElement('option');
      option.value = '';
      option.textContent = 'No players available';
      quickAddPlayerSelect.appendChild(option);
      if (DEBUG_LINEUP_MODAL) {
        dbg('[MODAL] dropdown options appended:', 0);
      }
      return;
    }

    filtered.forEach((player) => {
      const option = document.createElement('option');
      option.value = player.id;
      const positionLabel = (player.primary_position || '').trim();
      option.textContent = positionLabel
        ? `${positionLabel} · ${player.display_name}`
        : player.display_name;
      option.dataset.displayName = player.display_name || '';
      option.dataset.position = positionLabel;
      quickAddPlayerSelect.appendChild(option);
    });
    quickAddPlayerSelect.disabled = false;
    if (DEBUG_LINEUP_MODAL) {
      dbg('[MODAL] dropdown options appended:', filtered.length);
    }
  }

  function buildSubstitutePlayerOptions(side, includePlayerId = null) {
    if (!substitutePlayerSelect) {
      return;
    }
    dbg('⚠️ Clearing element innerHTML (buildSubstitutePlayerOptions)', {
      caller: new Error().stack,
      target: substitutePlayerSelect,
    });
    substitutePlayerSelect.innerHTML = '';
    const placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = 'Select player';
    substitutePlayerSelect.appendChild(placeholder);
    const teamIdValue = getTeamId(side);
    const teamId = teamIdValue ? Number(teamIdValue) : null;
    let entries = teamId
      ? players.filter((player) => player.team_id && Number(player.team_id) === teamId)
      : players.slice();
    if (teamId && entries.length === 0) {
      entries = players.slice();
    }
    const assignedIds = getAssignedPlayerIdsForSide(side);
    if (includePlayerId) {
      assignedIds.delete(Number(includePlayerId));
    }
    entries = entries.filter((player) => !assignedIds.has(Number(player.id)));
    if (entries.length === 0) {
      substitutePlayerSelect.disabled = true;
      const option = document.createElement('option');
      option.value = '';
      option.textContent = 'No players available';
      substitutePlayerSelect.appendChild(option);
      return;
    }
    entries.sort((a, b) => {
      const posA = normalizePositionKey(a.primary_position);
      const posB = normalizePositionKey(b.primary_position);
      if (posA !== posB) {
        return posA.localeCompare(posB);
      }
      const nameA = (a.display_name || '').toLowerCase();
      const nameB = (b.display_name || '').toLowerCase();
      return nameA.localeCompare(nameB);
    });
    entries.forEach((player) => {
      const option = document.createElement('option');
      option.value = player.id;
      const positionLabel = (player.primary_position || '').trim();
      option.textContent = positionLabel
        ? `${positionLabel} · ${player.display_name}`
        : player.display_name;
      option.dataset.displayName = player.display_name || '';
      option.dataset.position = positionLabel;
      substitutePlayerSelect.appendChild(option);
    });
    substitutePlayerSelect.disabled = false;
  }

  function getSubstituteEntriesForSide(side) {
    const normalized = (side || '').toLowerCase();
    return lineupEntries
      .filter((entry) => {
        const entrySide = (entry.team_side || '').toLowerCase();
        const isStarter = Number(entry.is_starting) === 1;
        return entrySide === normalized && !isStarter;
      })
      .sort((a, b) => {
        const posA = normalizePositionKey(a.position_label);
        const posB = normalizePositionKey(b.position_label);
        if (posA !== posB) {
          return posA.localeCompare(posB);
        }
        const nameA = (a.display_name || '').toLowerCase();
        const nameB = (b.display_name || '').toLowerCase();
        return nameA.localeCompare(nameB);
      });
  }

  function syncSubRowDeleteControl(row, locked) {
    const deleteButton = row.querySelector('[data-lineup-sub-delete]');
    if (!deleteButton) {
      return;
    }
    deleteButton.disabled = locked;
    deleteButton.title = locked ? SUBSTITUTION_LOCK_TOOLTIP : '';
  }

  function renderSubs(substitutes, side) {
    const container = side === 'home' ? substituteListHome : substituteListAway;
    if (!container) {
      if (DEBUG_SUBS) {
        dbgWarn('[SUBS] Container not found, skipping render', { side });
      }
      return 0;
    }
    while (container.firstChild) {
      container.removeChild(container.firstChild);
    }
    if (substitutes.length === 0) {
      const placeholder = document.createElement('p');
      placeholder.className = 'lineup-substitutes-placeholder mb-0';
      placeholder.textContent = 'No substitutes yet.';
      container.appendChild(placeholder);
      return 0;
    }
    substitutes.forEach((entry) => {
      const row = document.createElement('div');
      row.className = 'lineup-substitute-row';
      row.dataset.lineupEntryId = entry.id;
      row.dataset.matchPlayerId = entry.id;
      const details = document.createElement('div');
      details.className = 'd-flex align-items-center gap-2';
      const numberEl = document.createElement('span');
      numberEl.className = 'lineup-substitute-number';
      numberEl.textContent = entry.shirt_number ? `#${entry.shirt_number}` : '—';
      const nameEl = document.createElement('span');
      nameEl.className = 'lineup-substitute-name';
      nameEl.textContent = entry.display_name || 'Unknown';
      details.appendChild(numberEl);
      details.appendChild(nameEl);
      const actions = document.createElement('div');
      actions.className = 'lineup-substitute-actions';
      const editButton = document.createElement('button');
      editButton.type = 'button';
      editButton.innerHTML = '<i class="fa-solid fa-pen"></i>';
      editButton.addEventListener('click', () => {
        openSubstituteModal(side, entry);
      });
      const deleteButton = document.createElement('button');
      deleteButton.type = 'button';
      deleteButton.dataset.lineupSubDelete = '1';
      deleteButton.innerHTML = '<i class="fa-solid fa-trash"></i>';
      deleteButton.addEventListener('click', () => {
        if (row.classList.contains(LOCKED_CLASS)) {
          showWarningFlash(SUBSTITUTION_DELETE_UI_MESSAGE);
          return;
        }
        if (!window.confirm('Remove this substitute?')) {
          return;
        }
        deleteLineupEntry(entry.id).catch((error) => {
          const isSubError = isSubstitutionDeleteError(error);
          const errMessage = isSubError
            ? SUBSTITUTION_DELETE_UI_MESSAGE
            : (error.message || 'Unable to remove substitute');
          const flashFn = isSubError ? showWarningFlash : (msg) => setFlash('danger', msg);
          flashFn(errMessage);
        });
      });
      actions.appendChild(editButton);
      actions.appendChild(deleteButton);
      row.appendChild(details);
      row.appendChild(actions);
      const matchPlayerId = parseNumberValue(row.dataset.matchPlayerId);
      const incident = matchPlayerId !== null
        ? getIncidentForMatchPlayer(matchPlayerId)
        : null;
      renderSubRowIcon(row, incident);
      container.appendChild(row);
    });
    return substitutes.length;
  }

  function renderSubsBatch() {
    const homeSubs = getSubstituteEntriesForSide('home');
    const awaySubs = getSubstituteEntriesForSide('away');
    if (DEBUG_SUBS) {
      dbg('[SUBS] renderSubs called', {
        home: homeSubs.length,
        away: awaySubs.length,
      });
    }
    const homeRendered = renderSubs(homeSubs, 'home');
    const awayRendered = renderSubs(awaySubs, 'away');
    if (DEBUG_SUBS) {
      dbg('[SUBS] appended to DOM', {
        home: homeRendered,
        away: awayRendered,
      });
    }
    subsRendered = true;
  }

  function tryRenderSubsAfterLayout() {
    if (subsRendered) {
      return;
    }
    if (lineupEntries.length === 0) {
      return;
    }
    renderSubsBatch();
  }

  function openSubstituteModal(side, entry = null) {
    if (!canEdit) {
      return;
    }
    const normalizedSide = (side || substituteSideInput?.value || substituteTargetSide || '').toLowerCase();
    if (!normalizedSide) {
      return;
    }
    substituteTargetSide = normalizedSide;
    if (substituteSideInput) {
      substituteSideInput.value = normalizedSide;
    }
    if (substituteEntryInput) {
      substituteEntryInput.value = entry ? entry.id : '';
    }
    buildSubstitutePlayerOptions(normalizedSide, entry?.player_id ?? null);
    if (substitutePlayerSelect) {
      substitutePlayerSelect.disabled = Boolean(entry);
      substitutePlayerSelect.value = entry ? (entry.player_id ?? '') : '';
    }
    if (substituteTitle) {
      substituteTitle.textContent = entry ? 'Edit substitute' : 'Add substitute';
    }
    if (substituteSubtitle) {
      substituteSubtitle.textContent = entry ? 'Update bench player details' : 'Select a player to add to the bench';
    }
    if (substituteNumberInput) {
      substituteNumberInput.value = entry ? (entry.shirt_number ?? '') : '';
    }
    if (substituteModal) {
      substituteModal.style.display = 'flex';
      substituteModal.setAttribute('aria-hidden', 'false');
    }
  }

  function closeSubstituteModal() {
    substituteTargetSide = null;
    if (substituteEntryInput) {
      substituteEntryInput.value = '';
    }
    if (substitutePlayerSelect) {
      substitutePlayerSelect.disabled = false;
      substitutePlayerSelect.value = '';
    }
    if (substituteNumberInput) {
      substituteNumberInput.value = '';
    }
    if (substituteSideInput) {
      substituteSideInput.value = '';
    }
    if (substituteModal) {
      substituteModal.style.display = 'none';
      substituteModal.setAttribute('aria-hidden', 'true');
    }
    substituteForm?.reset();
  }

  const substitutionState = {
    teamSide: null,
    playerOffMatchPlayerId: null,
    playerOnMatchPlayerId: null,
  };

  function sortPlayersByPositionAndName(a, b) {
    const posA = normalizePositionKey(a.position_label);
    const posB = normalizePositionKey(b.position_label);
    if (posA !== posB) {
      return posA.localeCompare(posB);
    }
    const nameA = (a.display_name || '').toLowerCase();
    const nameB = (b.display_name || '').toLowerCase();
    return nameA.localeCompare(nameB);
  }

  function formatSubstitutionRowLabel(entry) {
    const shirtNumber = entry.shirt_number ? `#${entry.shirt_number}` : '#—';
    const position = (entry.position_label || '').trim() || '—';
    const displayName = entry.display_name || 'Unknown player';
    return `${shirtNumber} ${position} - ${displayName}`;
  }

  function buildSubstitutionList(container, entries, type) {
    if (!container) {
      return;
    }
    container.innerHTML = '';
    if (!entries.length) {
      const placeholder = document.createElement('p');
      placeholder.className = 'text-xs text-muted-alt mb-0';
      placeholder.textContent =
        type === 'off' ? 'No players currently on pitch.' : 'No substitutes available.';
      container.appendChild(placeholder);
      return;
    }
    entries.forEach((entry) => {
      const matchPlayerId = entry.id ?? '';
      const row = document.createElement('button');
      row.type = 'button';
      row.className = 'sub-player-row';
      row.dataset.subRow = type;
      row.dataset.matchPlayerId = matchPlayerId;
      row.dataset.lineupEntryId = matchPlayerId;
      row.setAttribute('aria-pressed', 'false');
      row.textContent = formatSubstitutionRowLabel(entry);
      const alreadySubbed = matchPlayerId && isPlayerSubbed(entry.id);
      if (alreadySubbed) {
        row.classList.add('is-disabled');
        row.setAttribute('aria-disabled', 'true');
        row.disabled = true;
        row.title = SUBSTITUTION_LOCK_TOOLTIP;
      }
      container.appendChild(row);
    });
  }

  function handleSubstitutionRowClick(event) {
    const row = event.target.closest('[data-sub-row]');
    if (!row) {
      return;
    }
    if (row.classList.contains('is-disabled')) {
      return;
    }
    event.preventDefault();
    const matchPlayerId = parseNumberValue(row.dataset.matchPlayerId);
    if (matchPlayerId === null) {
      return;
    }
    const type = row.dataset.subRow;
    const column = row.closest('.sub-player-list');
    column?.querySelectorAll('[data-sub-row]').forEach((sibling) => {
      sibling.classList.remove('is-selected');
      sibling.setAttribute('aria-pressed', 'false');
    });
    row.classList.add('is-selected');
    row.setAttribute('aria-pressed', 'true');
    if (type === 'off') {
      substitutionState.playerOffMatchPlayerId = matchPlayerId;
    } else if (type === 'on') {
      substitutionState.playerOnMatchPlayerId = matchPlayerId;
    }
    updateSubstitutionSubmitState();
  }

  function updateSubstitutionSubmitState() {
    const hasOff = Boolean(substitutionState.playerOffMatchPlayerId);
    const hasOn = Boolean(substitutionState.playerOnMatchPlayerId);
    const minuteValue = substitutionMinuteInput?.value?.trim() ?? '';
    const canSubmit = hasOff && hasOn && minuteValue !== '';
    if (substitutionSubmitButton) {
      substitutionSubmitButton.disabled = !canSubmit;
    }
  }

  function findFirstFocusableElement(container) {
    if (!container) {
      return null;
    }
    const focusableSelectors = [
      'a[href]:not([tabindex="-1"])',
      'button:not([disabled]):not([tabindex="-1"])',
      'input:not([disabled]):not([type="hidden"]):not([tabindex="-1"])',
      'select:not([disabled]):not([tabindex="-1"])',
      'textarea:not([disabled]):not([tabindex="-1"])',
      '[tabindex]:not([tabindex="-1"])',
    ].join(',');
    return container.querySelector(focusableSelectors);
  }

  function openSubstitutionModal(side, opener = null) {
    if (!canEdit) {
      return;
    }
    const normalizedSide = normalizeSide(side);
    if (!normalizedSide) {
      return;
    }
    substitutionState.teamSide = normalizedSide;
    substitutionState.playerOffMatchPlayerId = null;
    substitutionState.playerOnMatchPlayerId = null;
    if (substitutionTeamInput) {
      substitutionTeamInput.value = normalizedSide;
    }
    substitutionForm?.reset();
    const onPitch = [];
    const bench = [];
    lineupEntries.forEach((entry) => {
      const entrySide = (entry.team_side || '').toLowerCase();
      if (entrySide !== normalizedSide) {
        return;
      }
      const entryId = Number(entry.id);
      const isOnPitch = Number(entry.is_starting) === 1 || slotAssignments.has(entryId);
      if (isOnPitch) {
        onPitch.push(entry);
      } else {
        bench.push(entry);
      }
    });
    onPitch.sort(sortPlayersByPositionAndName);
    bench.sort(sortPlayersByPositionAndName);
    buildSubstitutionList(substitutionOffList, onPitch, 'off');
    buildSubstitutionList(substitutionOnList, bench, 'on');
    if (substitutionMinuteInput) {
      const minuteValue = formationTimingContext.minute;
      substitutionMinuteInput.value =
        minuteValue !== null && minuteValue !== undefined ? String(minuteValue) : '';
    }
    if (substitutionMinuteExtraInput) {
      const extraValue = formationTimingContext.minute_extra;
      substitutionMinuteExtraInput.value =
        extraValue !== null && extraValue !== undefined ? String(extraValue) : '';
    }
    if (substitutionReasonSelect) {
      substitutionReasonSelect.value = 'tactical';
    }
    lastSubstitutionModalOpener = opener ?? null;
    if (substitutionModal) {
      substitutionModal.style.display = 'flex';
      substitutionModal.removeAttribute('aria-hidden');
      if (typeof substitutionModal.inert !== 'undefined') {
        substitutionModal.inert = false;
      }
      const focusTarget = findFirstFocusableElement(substitutionModal);
      focusTarget?.focus();
    }
    updateSubstitutionSubmitState();
  }

  function closeSubstitutionModal() {
    substitutionState.teamSide = null;
    substitutionState.playerOffMatchPlayerId = null;
    substitutionState.playerOnMatchPlayerId = null;
    if (substitutionTeamInput) {
      substitutionTeamInput.value = '';
    }
    if (substitutionModal) {
      substitutionModal.style.display = 'none';
    }
    if (lastSubstitutionModalOpener) {
      lastSubstitutionModalOpener.focus();
    }
    if (substitutionModal) {
      substitutionModal.setAttribute('aria-hidden', 'true');
      if (typeof substitutionModal.inert !== 'undefined') {
        substitutionModal.inert = true;
      }
    }
    lastSubstitutionModalOpener = null;
    substitutionForm?.reset();
    updateSubstitutionSubmitState();
  }

  async function handleSubstitutionSubmit(event, targetMatchId = matchId) {
    event.preventDefault();
    if (!substitutionState.teamSide) {
      setFlash('danger', 'Unable to resolve team');
      return;
    }
    const playerOffId = substitutionState.playerOffMatchPlayerId;
    const playerOnId = substitutionState.playerOnMatchPlayerId;
    if (!playerOffId || !playerOnId) {
      setFlash('danger', 'Select players for substitution');
      return;
    }
    const minuteValue = substitutionMinuteInput?.value?.trim() ?? '';
    if (minuteValue === '') {
      setFlash('danger', 'Minute is required');
      return;
    }
    const minuteNumber = Number(minuteValue);
    const extraRaw = substitutionMinuteExtraInput?.value?.trim() ?? '';
    const minuteExtraNumber = extraRaw !== '' ? Number(extraRaw) : null;
    const reasonValue = substitutionReasonSelect?.value || 'tactical';
    const resolvedMatchId = parseNumberValue(targetMatchId);
    if (resolvedMatchId === null) {
      setFlash('danger', 'Match id is required');
      return;
    }
    const urlTemplate = cfg.matchSubstitutions?.create || '';
    const url = formatPath(urlTemplate, resolvedMatchId);
    if (!url) {
      setFlash('danger', 'Substitution endpoint missing');
      return;
    }
    substitutionSubmitButton?.setAttribute('disabled', 'disabled');
    try {
      await callJson(url, {
        match_id: resolvedMatchId,
        team_side: substitutionState.teamSide,
        match_second: formationTimingContext.match_second ?? 0,
        minute: minuteNumber,
        minute_extra: minuteExtraNumber,
        player_off_match_player_id: playerOffId,
        player_on_match_player_id: playerOnId,
        reason: reasonValue,
      });
      setFlash('success', 'Substitution saved');
      await loadSubstitutions(resolvedMatchId);
      buildSubstitutionIncidents();
      renderSubstitutionIncidents();
      closeSubstitutionModal();
    } catch (error) {
      console.error('[SUB]', error);
      setFlash('danger', error.message || 'Unable to save substitution');
    } finally {
      updateSubstitutionSubmitState();
    }
  }

  async function handleSubstituteSubmit(event) {
    event.preventDefault();
    const sideValue = (substituteSideInput?.value || substituteTargetSide || '').toLowerCase();
    if (!sideValue) {
      setFlash('danger', 'Unable to resolve team');
      return;
    }
    const selectedOption = substitutePlayerSelect?.selectedOptions?.[0];
    const playerId = substitutePlayerSelect?.value ? Number(substitutePlayerSelect.value) : null;
    const entryId = substituteEntryInput?.value ? Number(substituteEntryInput.value) : null;
    const jerseyNumber = (substituteNumberInput?.value || '').trim();
    const positionValue = '';
    if (!entryId && !playerId) {
      setFlash('danger', 'Select a player first.');
      return;
    }
    try {
      if (entryId) {
        await updateLineupEntry(entryId, {
          shirt_number: jerseyNumber,
          position_label: positionValue,
          is_starting: false,
        });
      } else {
        await addLineupEntry(sideValue, {
          player_id: playerId,
          display_name: selectedOption ? (selectedOption.dataset.displayName || selectedOption.textContent?.trim() || '') : '',
          shirt_number: jerseyNumber,
          position_label: positionValue,
          is_starting: false,
        });
      }
      closeSubstituteModal();
    } catch (error) {
      setFlash('danger', error.message || 'Unable to save substitute');
    }
  }

  function openQuickAddModal(slot, entry = null) {
    if (!canEdit) {
      return;
    }
    if (!Array.isArray(players) || players.length === 0) {
      dbgWarn('[MODAL] players not loaded yet');
    }
    const card = slot.closest('.lineup-card');
    if (!card) {
      return;
    }
    const side = card.dataset.lineupSide;
    if (!side) {
      return;
    }
    quickAddSide = side;
    const positionLabelElement = slot.closest('.formation-position')?.querySelector('.position-label');
    const defaultPosition = slot.dataset.positionLabel || positionLabelElement?.dataset.defaultLabel || '';
    quickAddPosition = entry?.position_label?.trim() || defaultPosition;
    if (quickAddTitle) {
      quickAddTitle.textContent = entry ? 'Edit player' : (quickAddPosition ? `Add ${quickAddPosition}` : 'Add player');
    }
    if (DEBUG_LINEUP_MODAL) {
      dbg('[MODAL] opened', { side });
    }
    populateQuickAddPlayerSelect(side, entry?.player_id ?? null);
    quickAddForm?.reset();
    if (quickAddEntryInput) {
      quickAddEntryInput.value = entry ? entry.id : '';
    }
    if (quickAddPlayerSelect) {
      quickAddPlayerSelect.value = entry ? entry.player_id ?? '' : '';
      quickAddPlayerSelect.disabled = Boolean(entry);
    }
    if (quickAddPositionInput) {
      quickAddPositionInput.value = entry ? (entry.position_label || quickAddPosition) : quickAddPosition;
    }
    if (quickAddNumberInput) {
      quickAddNumberInput.value = entry ? (entry.shirt_number ?? '') : '';
    }
    if (quickAddCaptainInput) {
      quickAddCaptainInput.checked = Boolean(entry?.is_captain);
    }
    if (quickAddSubmitButton) {
      quickAddSubmitButton.textContent = entry ? 'Update player' : 'Add player';
    }
    quickAddEditMode = Boolean(entry);
    if (quickAddCreateLink) {
      quickAddCreateLink.classList.toggle('d-none', Boolean(entry));
      quickAddCreateLink.setAttribute('aria-hidden', entry ? 'true' : 'false');
    }
    quickAddTargetSlot = slot;
    if (quickAddModal) {
      quickAddModal.style.display = 'flex';
      quickAddModal.setAttribute('aria-hidden', 'false');
    }
  }

  function closeQuickAddModal() {
    quickAddSide = null;
    quickAddPosition = '';
    quickAddTargetSlot = null;
    quickAddEditMode = false;
    if (quickAddEntryInput) {
      quickAddEntryInput.value = '';
    }
    if (quickAddPlayerSelect) {
      quickAddPlayerSelect.disabled = false;
    }
    if (quickAddModal) {
      quickAddModal.style.display = 'none';
      quickAddModal.setAttribute('aria-hidden', 'true');
    }
    if (quickAddCaptainInput) {
      quickAddCaptainInput.checked = false;
    }
    if (quickAddCreateLink) {
      quickAddCreateLink.classList.remove('d-none');
      quickAddCreateLink.setAttribute('aria-hidden', 'false');
    }
  }

  function applyQuickAddReturnSelection() {
    if (!quickAddReturnNewPlayerId || !quickAddPlayerSelect) {
      quickAddReturnNewPlayerId = null;
      return;
    }
    quickAddPlayerSelect.value = quickAddReturnNewPlayerId.toString();
    quickAddReturnNewPlayerId = null;
  }

  function maybeReturnToQuickAdd() {
    if (!quickAddReturnState || !quickAddReturnState.slot) {
      quickAddReturnState = null;
      return false;
    }
    const { slot } = quickAddReturnState;
    quickAddReturnState = null;
    openQuickAddModal(slot);
    applyQuickAddReturnSelection();
    return true;
  }

  async function handleQuickAddSubmit(event) {
    event.preventDefault();
    if (!quickAddSide || !quickAddPlayerSelect) {
      return;
    }
    const playerId = quickAddPlayerSelect.value ? Number(quickAddPlayerSelect.value) : null;
    if (!playerId) {
      setFlash('danger', 'Select a player first.');
      return;
    }
    const shirtNumber = quickAddNumberInput?.value || '';
    const selectedOption = quickAddPlayerSelect.selectedOptions?.[0];
    const displayName = selectedOption ? selectedOption.textContent?.trim() || '' : '';
    const positionValue = (quickAddPositionInput?.value?.trim()) || quickAddPosition;
    const isCaptain = quickAddCaptainInput?.checked ? 1 : 0;
    const entryIdValue = quickAddEntryInput?.value ? Number(quickAddEntryInput.value) : null;
    try {
      if (entryIdValue) {
        await updateLineupEntry(entryIdValue, {
          shirt_number: shirtNumber,
          position_label: positionValue,
          is_starting: true,
          is_captain: Boolean(isCaptain),
        });
        closeQuickAddModal();
      } else {
        const newEntry = await addLineupEntry(quickAddSide, {
          player_id: playerId,
          display_name: displayName,
          shirt_number: shirtNumber,
          position_label: positionValue,
          is_starting: true,
          is_captain: Boolean(isCaptain),
        });
        if (quickAddTargetSlot) {
          assignSlotToEntry(quickAddTargetSlot, newEntry);
        }
        closeQuickAddModal();
      }
    } catch (error) {
      setFlash('danger', error.message || 'Unable to add player');
    }
  }

  function addLineupForm(side) {
    if (!canEdit) {
      setFlash('info', 'You do not have permission to edit this lineup.');
      return;
    }
    if (!matchId) {
      setFlash('info', 'Complete the previous steps before managing the lineup.');
      return;
    }
    closeAddForm(side);
    const formWrapper = document.createElement('div');
    formWrapper.className = 'rounded-md border border-dashed border-soft p-3 mb-2 bg-black';

    const header = document.createElement('div');
    header.className = 'd-flex align-items-center justify-content-between mb-3';
    const title = document.createElement('div');
    title.className = 'text-xs text-muted-alt';
    title.textContent = side === 'home' ? 'Add home player' : 'Add away player';
    header.appendChild(title);
    const cancelBtn = document.createElement('button');
    cancelBtn.type = 'button';
    cancelBtn.className = 'btn btn-sm btn-outline-light';
    cancelBtn.textContent = 'Cancel';
    cancelBtn.addEventListener('click', () => closeAddForm(side));
    header.appendChild(cancelBtn);
    formWrapper.appendChild(header);

    const form = document.createElement('form');
    form.className = 'row g-2 align-items-end';

    const playerCol = document.createElement('div');
    playerCol.className = 'col-6';
    const playerLabel = document.createElement('label');
    playerLabel.className = 'form-label text-light text-xs';
    playerLabel.textContent = 'Player';
    const playerSelect = document.createElement('select');
    playerSelect.name = 'player_id';
    playerSelect.className = 'form-select form-select-sm select-dark';
    buildPlayerOptions(side, playerSelect);
    playerCol.appendChild(playerLabel);
    playerCol.appendChild(playerSelect);
    form.appendChild(playerCol);

    const numberCol = document.createElement('div');
    numberCol.className = 'col-3';
    const numberLabel = document.createElement('label');
    numberLabel.className = 'form-label text-light text-xs';
    numberLabel.textContent = '#';
    const numberInput = document.createElement('input');
    numberInput.type = 'number';
    numberInput.min = '0';
    numberInput.step = '1';
    numberInput.name = 'shirt_number';
    numberInput.className = 'form-control form-control-sm input-dark';
    numberCol.appendChild(numberLabel);
    numberCol.appendChild(numberInput);
    form.appendChild(numberCol);

    const positionCol = document.createElement('div');
    positionCol.className = 'col-3';
    const positionLabel = document.createElement('label');
    positionLabel.className = 'form-label text-light text-xs';
    positionLabel.textContent = 'Pos';
    const positionInput = document.createElement('input');
    positionInput.type = 'text';
    positionInput.name = 'position_label';
    positionInput.className = 'form-control form-control-sm input-dark';
    positionCol.appendChild(positionLabel);
    positionCol.appendChild(positionInput);
    form.appendChild(positionCol);

    const starterCol = document.createElement('div');
    starterCol.className = 'col-6';
    const starterWrapper = document.createElement('div');
    starterWrapper.className = 'form-check form-switch text-light';
    const starterInput = document.createElement('input');
    starterInput.type = 'checkbox';
    starterInput.className = 'form-check-input';
    starterInput.name = 'is_starting';
    const starterLabel = document.createElement('label');
    starterLabel.className = 'form-check-label text-xs';
    starterLabel.textContent = 'Starting';
    starterWrapper.appendChild(starterInput);
    starterWrapper.appendChild(starterLabel);
    starterCol.appendChild(starterWrapper);
    form.appendChild(starterCol);

    const captainCol = document.createElement('div');
    captainCol.className = 'col-6';
    const captainWrapper = document.createElement('div');
    captainWrapper.className = 'form-check form-switch text-light';
    const captainInput = document.createElement('input');
    captainInput.type = 'checkbox';
    captainInput.className = 'form-check-input';
    captainInput.name = 'is_captain';
    const captainLabel = document.createElement('label');
    captainLabel.className = 'form-check-label text-xs';
    captainLabel.textContent = 'Captain';
    captainWrapper.appendChild(captainInput);
    captainWrapper.appendChild(captainLabel);
    captainCol.appendChild(captainWrapper);
    form.appendChild(captainCol);

    const actionCol = document.createElement('div');
    actionCol.className = 'col-6 d-flex gap-2';
    const submitButton = document.createElement('button');
    submitButton.type = 'submit';
    submitButton.className = 'btn btn-sm btn-primary-soft flex-grow-1';
    submitButton.textContent = 'Add player';
    const createButton = document.createElement('button');
    createButton.type = 'button';
    createButton.className = 'btn btn-sm btn-outline-light flex-grow-1';
    createButton.textContent = 'New player';
    createButton.addEventListener('click', () => {
      showModal();
    });
    actionCol.appendChild(submitButton);
    actionCol.appendChild(createButton);
    form.appendChild(actionCol);

    form.addEventListener('submit', (event) => {
      event.preventDefault();
      submitButton.disabled = true;
      const payload = {
        player_id: Number(playerSelect.value) || null,
        shirt_number: numberInput.value,
        position_label: positionInput.value,
        is_starting: starterInput.checked,
        is_captain: captainInput.checked,
      };
      if (!payload.player_id) {
        setFlash('danger', 'Select a player first.');
        submitButton.disabled = false;
        return;
      }
      addLineupEntry(side, payload)
        .catch((error) => {
          setFlash('danger', error.message || 'Unable to add player');
        })
        .finally(() => {
          submitButton.disabled = false;
        });
    });

    formWrapper.appendChild(form);

    const container = side === 'home' ? homeContainer : awayContainer;
    if (container) {
      container.prepend(formWrapper);
      addForms[side] = formWrapper;
    }
  }

  async function addLineupEntry(side, payload) {
    if (!matchId) {
      throw new Error('Match not initialized');
    }
    const playerId = payload.player_id ? Number(payload.player_id) : null;
    const player = playerId ? players.find((p) => p.id === playerId) : null;
    const resolvedName = (payload.display_name?.toString().trim()) || (player?.display_name || '');
    if (!playerId && !resolvedName) {
      throw new Error('Player name is required');
    }
    const body = {
      match_id: matchId,
      team_side: side,
      player_id: playerId,
      display_name: resolvedName,
      shirt_number: payload.shirt_number,
      position_label: payload.position_label,
      is_starting: payload.is_starting ? 1 : 0,
      is_captain: payload.is_captain ? 1 : 0,
    };
    if (!cfg.matchPlayers?.add) {
      throw new Error('Missing lineup endpoint');
    }
    const response = await callJson(cfg.matchPlayers.add, body);
    closeAddForm(side);
    clearFlash();
    setFlash('success', `${resolvedName} added to lineup`);
    loadLineup(matchId);
    return response.match_player;
  }

  async function updateLineupEntry(id, payload) {
    if (!cfg.matchPlayers?.update) {
      throw new Error('Missing lineup endpoint');
    }
    await callJson(cfg.matchPlayers.update, {
      id,
      shirt_number: payload.shirt_number,
      position_label: payload.position_label,
      is_starting: payload.is_starting ? 1 : 0,
      is_captain: payload.is_captain ? 1 : 0,
    });
    clearFlash();
    setFlash('success', 'Lineup updated');
    loadLineup(matchId);
  }

  async function deleteLineupEntry(id) {
    if (!cfg.matchPlayers?.delete) {
      throw new Error('Missing lineup endpoint');
    }
    await callJson(cfg.matchPlayers.delete, { id });
    clearFlash();
    setFlash('success', 'Player removed');
    loadLineup(matchId);
  }

  function showModal() {
    if (!modal) return;
    modal.style.display = 'flex';
    modal.setAttribute('aria-hidden', 'false');
  }

  function hideModal() {
    if (!modal) return;
    modal.style.display = 'none';
    modal.setAttribute('aria-hidden', 'true');
    modalForm?.reset();
    if (modalError) {
      modalError.classList.add('d-none');
      dbg('⚠️ Clearing textContent (hideModal)', {
        caller: new Error().stack,
        target: modalError,
      });
      modalError.textContent = '';
    }
    maybeReturnToQuickAdd();
  }

  async function handleCreatePlayerSubmit(event) {
    event.preventDefault();
    event.stopPropagation();
    if (!modalForm) return;
    if (!cfg.clubId) {
      setFlash('danger', 'Club context missing');
      return;
    }
    const formData = new FormData(modalForm);
    const payload = {
      display_name: formData.get('display_name')?.toString().trim() || '',
      first_name: formData.get('first_name')?.toString().trim() || '',
      last_name: formData.get('last_name')?.toString().trim() || '',
      primary_position: formData.get('primary_position')?.toString().trim() || '',
      team_id: formData.get('team_id') ? Number(formData.get('team_id')) : null,
      club_id: cfg.clubId,
    };
    if (!payload.display_name) {
      if (modalError) {
        modalError.classList.remove('d-none');
        modalError.textContent = 'Display name is required';
      }
      return;
    }
    try {
      if (!cfg.players?.create) {
        throw new Error('Missing player endpoint');
      }
      const result = await callJson(cfg.players.create, payload);
      await fetchPlayers();
      if (quickAddReturnState) {
        const createdId = result?.player?.id ?? null;
        quickAddReturnNewPlayerId = createdId ? Number(createdId) : null;
      } else {
        quickAddReturnNewPlayerId = null;
      }
      setFlash('success', 'Player saved');
      hideModal();
    } catch (error) {
      if (modalError) {
        modalError.classList.remove('d-none');
        modalError.textContent = error.message || 'Unable to create player';
      }
    }
  }

  async function fetchPlayers() {
    if (!cfg.players?.list || !cfg.clubId) {
      return;
    }
    try {
      const url = `${cfg.players.list}?club_id=${encodeURIComponent(cfg.clubId)}`;
      const data = await callJson(url, null, 'GET');
      players = Array.isArray(data.players) ? data.players : [];
      updateAddButtons();
      buildFormsFromState();
    } catch (error) {
      setFlash('danger', error.message || 'Unable to load players');
    }
  }

  async function loadLineup(targetMatchId = matchId) {
    const resolvedMatchId = parseNumberValue(targetMatchId);
    if (!cfg.matchPlayers?.list || resolvedMatchId === null) {
      updateBadge('waiting', 'Complete previous steps');
      return;
    }
    matchId = resolvedMatchId;
    try {
      updateBadge('loading', 'Loading lineup...');
      const url = `${cfg.matchPlayers.list}?match_id=${encodeURIComponent(resolvedMatchId)}`;
      const data = await callJson(url, null, 'GET');
      dbg('loadLineup payload', data);
      const subsPayload = Array.isArray(data.match_players) ? data.match_players : [];
      if (DEBUG_SUBS) {
        dbg('[SUBS] Payload received', { count: subsPayload.length });
      }
      lineupEntries = subsPayload;
      await loadSubstitutions(resolvedMatchId);
      buildSubstitutionIncidents();
      dbg('loadLineup player count', lineupEntries.length);
      renderLineup(lineupEntries);
      renderSubstitutionIncidents();
      dbg('UI ready', {
        lineupMap: lineupMap.size,
        subs: subsPayload.length,
      });
      if (lineupEntries.length) {
        updateBadge('ready', 'Lineup ready');
      } else {
        updateBadge('waiting', 'No players yet');
      }
    } catch (error) {
      updateBadge('error', 'Unable to load lineup');
      setFlash('danger', error.message || 'Unable to load lineup');
    }
  }

  function setMatchId(id) {
    const parsed = parseNumberValue(id);
    if (parsed === null) {
      return;
    }
    matchId = parsed;
    updateNavigationLinks();
    updateAddButtons();
    loadLineup(parsed);
  }

  if (modal) {
    modal.addEventListener('click', (event) => {
      if (event.target === modal) {
        hideModal();
      }
    });
  }
  modal?.querySelectorAll('[data-lineup-modal-close]').forEach((btn) => {
    btn.addEventListener('click', hideModal);
  });
  modalForm?.addEventListener('submit', handleCreatePlayerSubmit);
  createPlayerBtn?.addEventListener('click', showModal);

  if (quickAddModal) {
    quickAddModal.addEventListener('click', (event) => {
      if (event.target === quickAddModal) {
        closeQuickAddModal();
      }
    });
  }
  quickAddModal?.querySelectorAll('[data-lineup-quick-add-close]').forEach((btn) => {
    btn.addEventListener('click', closeQuickAddModal);
  });
  const handleQuickAddCreateClick = (event) => {
    event.preventDefault();
    if (quickAddEditMode) {
      return;
    }
    quickAddReturnState = quickAddTargetSlot ? { slot: quickAddTargetSlot } : null;
    const createTeamField = modalForm?.querySelector('select[name="team_id"]');
    const defaultTeam = quickAddSide ? getTeamId(quickAddSide) : '';
    if (createTeamField) {
      createTeamField.value = defaultTeam;
    }
    closeQuickAddModal();
    showModal();
  };
  quickAddCreateLink?.addEventListener('click', handleQuickAddCreateClick);
  quickAddForm?.addEventListener('submit', handleQuickAddSubmit);

  if (canEdit) {
    addButtons.forEach((btn) => {
      const side = btn.dataset.lineupAdd;
      btn.addEventListener('click', () => addLineupForm(side));
    });

    if (substituteModal) {
      substituteModal.addEventListener('click', (event) => {
        if (event.target === substituteModal) {
          closeSubstituteModal();
        }
      });
    }
    substituteModal?.querySelectorAll('[data-lineup-sub-close]').forEach((btn) => {
      btn.addEventListener('click', closeSubstituteModal);
    });
    substituteForm?.addEventListener('submit', handleSubstituteSubmit);
    substituteAddButtons.forEach((btn) => {
      const side = btn.dataset.lineupSubAdd;
      btn.addEventListener('click', () => openSubstituteModal(side));
    });

    if (substitutionModal) {
      substitutionModal.addEventListener('click', (event) => {
        if (event.target === substitutionModal) {
          closeSubstitutionModal();
        }
      });
    }
    substitutionModal?.querySelectorAll('[data-lineup-substitution-close]').forEach((btn) => {
      btn.addEventListener('click', closeSubstitutionModal);
    });
    substitutionForm?.addEventListener('submit', (event) => handleSubstitutionSubmit(event, matchId));
    substitutionOffList?.addEventListener('click', handleSubstitutionRowClick);
    substitutionOnList?.addEventListener('click', handleSubstitutionRowClick);
    substitutionMinuteInput?.addEventListener('input', updateSubstitutionSubmitState);

    root.addEventListener('click', handleFormationSlotClick);
  }

  function handleFormationSlotClick(event) {
    const slot = event.target.closest('.lineup-formation-slot');
    if (!slot || !root.contains(slot)) {
      return;
    }
    event.stopPropagation();
    handleSlotClick(slot);
  }

  const handleTeamChange = () => {
    updateTeamLabels();
  };
  teamSelects.home?.addEventListener('change', handleTeamChange);
  teamSelects.away?.addEventListener('change', handleTeamChange);

  document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-lineup-make-sub]');
    if (!button) {
      return;
    }
    event.preventDefault();
    const side = button.dataset.lineupMakeSub;
    openSubstitutionModal(side, button);
  });

  document.addEventListener('click', (event) => {
    if (!activeSlotMenuSlot) {
      return;
    }
    if (activeSlotMenuSlot.contains(event.target)) {
      return;
    }
    closeSlotActionMenu();
  });

  updateTeamLabels();
  updateNavigationLinks();
  updateAddButtons();
  setupFormations();
  fetchPlayers();
  if (matchId) {
    loadLineup(matchId);
  } else {
    updateBadge('waiting', 'Complete previous steps');
  }

  window.MatchWizardLineup = {
    setMatchId,
    refresh: loadLineup,
    renderFormation,
    setFormationTimingContext,
  };
})();
