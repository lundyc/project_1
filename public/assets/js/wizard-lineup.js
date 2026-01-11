(() => {
          const cfg = window.MatchWizardLineupConfig || {};
          const root = document.querySelector('.wizard-step-panel[data-step="4"]');
          if (!root) {
                    return;
          }

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
          const formationSlots = Array.from(root.querySelectorAll('.lineup-formation-slot'));
          const form = document.getElementById('matchWizardForm');
          const teamSelects = {
                    home: form ? form.elements['home_team_id'] : null,
                    away: form ? form.elements['away_team_id'] : null,
          };
          const substituteAddButtons = Array.from(root.querySelectorAll('[data-lineup-sub-add]'));
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

          let matchId = cfg.matchId || null;
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
          let substituteTargetSide = null;
          const slotAssignments = new Map();
          const lineupMap = new Map();
          let activeSlotMenuSlot = null;

          function setFlash(type, message) {
                    if (!lineupFlash) return;
                    lineupFlash.classList.remove('d-none', 'alert-danger', 'alert-success', 'alert-info');
                    lineupFlash.classList.add('alert', type === 'success' ? 'alert-success' : (type === 'info' ? 'alert-info' : 'alert-danger'));
                    lineupFlash.textContent = message;
          }

          function clearFlash() {
                    if (!lineupFlash) return;
                    lineupFlash.classList.add('d-none');
                    lineupFlash.textContent = '';
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
                    if (homeLabel && teamSelects.home) {
                              const homeOption = teamSelects.home.selectedOptions?.[0]?.textContent?.trim() || 'Home lineup';
                              homeLabel.textContent = homeOption;
                    }
                    if (awayLabel && teamSelects.away) {
                              const awayOption = teamSelects.away.selectedOptions?.[0]?.textContent?.trim() || 'Away lineup';
                              awayLabel.textContent = awayOption;
                    }
          }

          function formatPath(template) {
                    return template?.replace('{match_id}', encodeURIComponent(matchId));
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
                    const disabled = !matchId || players.length === 0;
                    addButtons.forEach((btn) => {
                              btn.disabled = disabled;
                    });
                    substituteAddButtons.forEach((btn) => {
                              btn.disabled = disabled;
                    });
          }

          function buildPlayerOptions(side, targetSelect) {
                    if (!targetSelect) {
                              return;
                    }
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
                    updateSlotIndicator(slot, entry);
                    updateSlotPositionLabel(slot, entry);
                    updateSlotCaptainBadge(slot, entry);
          }

          function clearSlotPlayer(slot) {
                    if (!slot) {
                              return;
                    }
                    slot.classList.remove('has-player');
                    slot.removeAttribute('data-lineup-entry-id');
                    slot.removeAttribute('data-lineup-player-id');
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

          function updateSlotPositionLabel(slot, entry) {
                    const container = slot.closest('.formation-position');
                    if (!container) {
                              return;
                    }
                    const nameEl = container.querySelector('.position-label-name');
                    const roleEl = container.querySelector('.position-label-role');
                    const defaultText = roleEl?.dataset.defaultLabel || '';
                    if (entry && entry.display_name) {
                              const positionText = entry.position_label?.trim();
                              if (nameEl) {
                                        nameEl.textContent = entry.display_name;
                              }
                              if (roleEl) {
                                        roleEl.textContent = positionText || defaultText;
                              }
                    } else {
                              if (nameEl) {
                                        nameEl.textContent = '';
                              }
                              if (roleEl) {
                                        roleEl.textContent = defaultText;
                              }
                    }
          }

          function updateSlotCaptainBadge(slot, entry) {
                    const badge = slot.querySelector('.lineup-slot-captain-badge');
                    if (!badge) {
                              return;
                    }
                    if (entry && entry.is_captain) {
                              badge.textContent = 'C';
                              badge.classList.add('visible');
                    } else {
                              badge.textContent = '';
                              badge.classList.remove('visible');
                    }
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

          function createSlotMenu(slot) {
                    let menu = slot.querySelector('.lineup-slot-menu');
                    if (!menu) {
                              menu = document.createElement('div');
                              menu.className = 'lineup-slot-menu';
                              menu.innerHTML = `
                                        <button type="button" data-lineup-slot-action="edit">Edit</button>
                                        <button type="button" data-lineup-slot-action="delete">Delete</button>
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
                    if (window.confirm('Remove this player from the slot?')) {
                              deleteLineupEntry(entryId).then(() => {
                                        clearSlotForEntry(entryId);
                              }).catch((error) => {
                                        setFlash('danger', error.message || 'Unable to remove player');
                              });
                    }
          }

          function handleSlotClick(slot) {
                    closeSlotActionMenu();
                    const entryId = slot.dataset.lineupEntryId;
                    if (entryId) {
                              showSlotActionMenu(slot);
                    } else {
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
                    assignEntriesToSlots();
                    closeAddForm('home');
                    closeAddForm('away');
                    buildFormsFromState();
                    syncSlotsWithEntries();
                    renderSubstitutes();
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

function buildQuickAddPlayerOptions(side) {
                    if (!quickAddPlayerSelect) {
                              return;
                    }
                    quickAddPlayerSelect.innerHTML = '';
                    const placeholder = document.createElement('option');
                    placeholder.value = '';
                    placeholder.textContent = 'Select player';
                    quickAddPlayerSelect.appendChild(placeholder);
                    const teamIdValue = teamSelects[side]?.value;
                    const teamId = teamIdValue ? Number(teamIdValue) : null;
                    let entries = teamId
                              ? players.filter((player) => player.team_id && Number(player.team_id) === teamId)
                              : players.slice();
                    if (teamId && entries.length === 0) {
                              entries = players.slice();
                    }
                    const assignedIds = getAssignedPlayerIdsForSide(side);
                    entries = entries.filter((player) => !assignedIds.has(Number(player.id)));
                    if (entries.length === 0) {
                              quickAddPlayerSelect.disabled = true;
                              const option = document.createElement('option');
                              option.value = '';
                              option.textContent = 'No players available';
                              quickAddPlayerSelect.appendChild(option);
                              return;
                    }
                    const targetPosition = (quickAddPosition || '').trim().toLowerCase();
                    const samePosition = [];
                    const otherPlayers = [];
                    entries.forEach((player) => {
                              const position = (player.primary_position || '').trim();
                              const normalized = position.toLowerCase();
                              if (targetPosition && normalized === targetPosition) {
                                        samePosition.push(player);
                              } else {
                                        otherPlayers.push(player);
                              }
                    });
                    const sortByName = (list) => {
                              return list.sort((a, b) => {
                                        const nameA = (a.display_name || '').toLowerCase();
                                        const nameB = (b.display_name || '').toLowerCase();
                                        return nameA.localeCompare(nameB);
                              });
                    };
                    sortByName(samePosition);
                    const normalizePosition = (player) => {
                              const position = (player.primary_position || '').trim();
                              return position ? position.toLowerCase() : 'zzz';
                    };
                    otherPlayers.sort((a, b) => {
                              const posA = normalizePosition(a);
                              const posB = normalizePosition(b);
                              if (posA !== posB) {
                                        return posA.localeCompare(posB);
                              }
                              const nameA = (a.display_name || '').toLowerCase();
                              const nameB = (b.display_name || '').toLowerCase();
                              return nameA.localeCompare(nameB);
                    });
                    const renderOption = (player) => {
                              const option = document.createElement('option');
                              option.value = player.id;
                              const positionLabel = (player.primary_position || '').trim();
                              option.textContent = positionLabel
                                        ? `${positionLabel} · ${player.display_name}`
                                        : player.display_name;
                              quickAddPlayerSelect.appendChild(option);
                    };
                    samePosition.forEach(renderOption);
                    if (samePosition.length > 0 && otherPlayers.length > 0) {
                              const separator = document.createElement('option');
                              separator.disabled = true;
                              separator.setAttribute('role', 'separator');
                              quickAddPlayerSelect.appendChild(separator);
                    }
                    otherPlayers.forEach(renderOption);
                    quickAddPlayerSelect.disabled = false;
          }

          function refreshQuickAddPlayerOptions() {
                    ['home', 'away'].forEach((side) => buildQuickAddPlayerOptions(side));
          }

          function buildSubstitutePlayerOptions(side, includePlayerId = null) {
                    if (!substitutePlayerSelect) {
                              return;
                    }
                    substitutePlayerSelect.innerHTML = '';
                    const placeholder = document.createElement('option');
                    placeholder.value = '';
                    placeholder.textContent = 'Select player';
                    substitutePlayerSelect.appendChild(placeholder);
                    const teamIdValue = teamSelects[side]?.value;
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

          function renderSubstitutes() {
                    if (!substituteListHome && !substituteListAway) {
                              return;
                    }
                    ['home', 'away'].forEach((side) => {
                              const container = side === 'home' ? substituteListHome : substituteListAway;
                              if (!container) {
                                        return;
                              }
                              container.innerHTML = '';
                              const substitutes = lineupEntries
                                        .filter((entry) => {
                                                  const entrySide = (entry.team_side || '').toLowerCase();
                                                  const isStarter = Number(entry.is_starting) === 1;
                                                  return entrySide === side && !isStarter;
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
                              if (substitutes.length === 0) {
                                        const placeholder = document.createElement('p');
                                        placeholder.className = 'lineup-substitutes-placeholder mb-0';
                                        placeholder.textContent = 'No substitutes yet.';
                                        container.appendChild(placeholder);
                                        return;
                              }
                              substitutes.forEach((entry) => {
                                        const row = document.createElement('div');
                                        row.className = 'lineup-substitute-row';
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
                                        deleteButton.innerHTML = '<i class="fa-solid fa-trash"></i>';
                                        deleteButton.addEventListener('click', () => {
                                                  if (!window.confirm('Remove this substitute?')) {
                                                            return;
                                                  }
                                                  deleteLineupEntry(entry.id).catch((error) => {
                                                            setFlash('danger', error.message || 'Unable to remove substitute');
                                                  });
                                        });
                                        actions.appendChild(editButton);
                                        actions.appendChild(deleteButton);
                                        row.appendChild(details);
                                        row.appendChild(actions);
                                        container.appendChild(row);
                              });
                    });
          }

          function openSubstituteModal(side, entry = null) {
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
                    refreshQuickAddPlayerOptions();
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
                    loadLineup();
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
                    loadLineup();
          }

          async function deleteLineupEntry(id) {
                    if (!cfg.matchPlayers?.delete) {
                              throw new Error('Missing lineup endpoint');
                    }
                    await callJson(cfg.matchPlayers.delete, { id });
                    clearFlash();
                    setFlash('success', 'Player removed');
                    loadLineup();
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
                              refreshQuickAddPlayerOptions();
                    } catch (error) {
                              setFlash('danger', error.message || 'Unable to load players');
                    }
          }

          async function loadLineup() {
                    if (!cfg.matchPlayers?.list || !matchId) {
                              updateBadge('waiting', 'Complete previous steps');
                              return;
                    }
                    try {
                              updateBadge('loading', 'Loading lineup...');
                              const url = `${cfg.matchPlayers.list}?match_id=${encodeURIComponent(matchId)}`;
                              const data = await callJson(url, null, 'GET');
                              lineupEntries = Array.isArray(data.match_players) ? data.match_players : [];
                              renderLineup(lineupEntries);
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
                    matchId = id;
                    updateNavigationLinks();
                    updateAddButtons();
                    if (matchId) {
                              loadLineup();
                    }
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
          const quickAddCreateLink = quickAddModal?.querySelector('[data-lineup-quick-add-create]');
          quickAddCreateLink?.addEventListener('click', (event) => {
                    event.preventDefault();
                    quickAddReturnState = quickAddTargetSlot ? { slot: quickAddTargetSlot } : null;
                    const createTeamField = modalForm?.querySelector('select[name="team_id"]');
                    const defaultTeam = quickAddSide ? (teamSelects[quickAddSide]?.value || '') : '';
                    if (createTeamField) {
                              createTeamField.value = defaultTeam;
                    }
                    closeQuickAddModal();
                    showModal();
          });
          quickAddForm?.addEventListener('submit', handleQuickAddSubmit);

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

          formationSlots.forEach((slot) => {
                    slot.addEventListener('click', (event) => {
                              event.stopPropagation();
                              handleSlotClick(slot);
                    });
          });

          const handleTeamChange = () => {
                    updateTeamLabels();
                    refreshQuickAddPlayerOptions();
          };
          teamSelects.home?.addEventListener('change', handleTeamChange);
          teamSelects.away?.addEventListener('change', handleTeamChange);

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
          fetchPlayers();
          if (matchId) {
                    loadLineup();
          } else {
                    updateBadge('waiting', 'Complete previous steps');
          }

          window.MatchWizardLineup = {
                    setMatchId,
                    refresh: loadLineup,
          };
})();
