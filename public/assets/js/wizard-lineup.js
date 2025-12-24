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
          const homeContainer = root.querySelector('[data-lineup-side="home"] .lineup-list');
          const awayContainer = root.querySelector('[data-lineup-side="away"] .lineup-list');
          const form = document.getElementById('matchWizardForm');
          const teamSelects = {
                    home: form ? form.elements['home_team_id'] : null,
                    away: form ? form.elements['away_team_id'] : null,
          };

          let matchId = cfg.matchId || null;
          let players = [];
          let lineupEntries = [];
          const addForms = {
                    home: null,
                    away: null,
          };

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
                    const taken = lineupEntries.filter((entry) => entry.team_side === side).map((entry) => entry.player_id).filter(Boolean);
                    players.forEach((player) => {
                              const option = document.createElement('option');
                              option.value = player.id;
                              option.textContent = player.display_name;
                              if (taken.includes(player.id)) {
                                        option.disabled = true;
                              }
                              targetSelect.appendChild(option);
                    });
          }

          function closeAddForm(side) {
                    if (addForms[side]) {
                              addForms[side].remove();
                              addForms[side] = null;
                    }
          }

          function createEmptyMessage(sideLabel) {
                    const empty = document.createElement('div');
                    empty.className = 'text-muted text-sm px-3 py-2';
                    empty.textContent = `No ${sideLabel.toLowerCase()} players yet.`;
                    return empty;
          }

          function createRow(entry) {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'rounded-md border border-soft p-3 mb-2 bg-surface';
                    wrapper.dataset.lineupId = entry.id;

                    const header = document.createElement('div');
                    header.className = 'd-flex align-items-center justify-content-between mb-2';
                    const name = document.createElement('div');
                    name.className = 'fw-semibold';
                    name.textContent = entry.display_name;
                    header.appendChild(name);
                    wrapper.appendChild(header);

                    const grid = document.createElement('div');
                    grid.className = 'row g-2 align-items-end';

                    const shirtCol = document.createElement('div');
                    shirtCol.className = 'col-3';
                    const shirtLabel = document.createElement('label');
                    shirtLabel.className = 'form-label text-light text-xs';
                    shirtLabel.textContent = '#';
                    const shirtInput = document.createElement('input');
                    shirtInput.type = 'number';
                    shirtInput.min = '0';
                    shirtInput.step = '1';
                    shirtInput.className = 'form-control form-control-sm input-dark';
                    shirtInput.value = entry.shirt_number ?? '';
                    shirtCol.appendChild(shirtLabel);
                    shirtCol.appendChild(shirtInput);
                    grid.appendChild(shirtCol);

                    const positionCol = document.createElement('div');
                    positionCol.className = 'col-5';
                    const positionLabel = document.createElement('label');
                    positionLabel.className = 'form-label text-light text-xs';
                    positionLabel.textContent = 'Position';
                    const positionInput = document.createElement('input');
                    positionInput.type = 'text';
                    positionInput.className = 'form-control form-control-sm input-dark';
                    positionInput.value = entry.position_label ?? '';
                    positionCol.appendChild(positionLabel);
                    positionCol.appendChild(positionInput);
                    grid.appendChild(positionCol);

                    const starterCol = document.createElement('div');
                    starterCol.className = 'col-4';
                    const starterLabel = document.createElement('label');
                    starterLabel.className = 'form-check form-check-inline text-light';
                    const starterInput = document.createElement('input');
                    starterInput.type = 'checkbox';
                    starterInput.className = 'form-check-input';
                    starterInput.checked = !!entry.is_starting;
                    starterInput.style.marginLeft = '0';
                    starterLabel.appendChild(starterInput);
                    const starterSpan = document.createElement('span');
                    starterSpan.className = 'form-check-label text-xs';
                    starterSpan.textContent = 'Starting';
                    starterLabel.appendChild(starterSpan);
                    starterCol.appendChild(starterLabel);
                    grid.appendChild(starterCol);

                    wrapper.appendChild(grid);

                    const footer = document.createElement('div');
                    footer.className = 'd-flex justify-content-end gap-2 mt-2 flex-wrap';

                    const saveButton = document.createElement('button');
                    saveButton.type = 'button';
                    saveButton.className = 'btn btn-sm btn-primary-soft';
                    saveButton.dataset.lineupUpdate = '';
                    saveButton.textContent = 'Save';
                    saveButton.addEventListener('click', () => {
                              updateLineupEntry(entry.id, {
                                        shirt_number: shirtInput.value,
                                        position_label: positionInput.value,
                                        is_starting: starterInput.checked,
                              });
                    });

                    const removeButton = document.createElement('button');
                    removeButton.type = 'button';
                    removeButton.className = 'btn btn-sm btn-outline-danger';
                    removeButton.dataset.lineupRemove = '';
                    removeButton.textContent = 'Remove';
                    removeButton.addEventListener('click', () => {
                              deleteLineupEntry(entry.id);
                    });

                    footer.appendChild(saveButton);
                    footer.appendChild(removeButton);
                    wrapper.appendChild(footer);

                    return wrapper;
          }

          function renderSide(side, container) {
                    if (!container) return;
                    addForms[side] = null;
                    container.innerHTML = '';
                    const entries = lineupEntries.filter((entry) => entry.team_side === side);
                    if (entries.length === 0) {
                              container.appendChild(createEmptyMessage(side === 'home' ? 'Home' : 'Away'));
                              return;
                    }
                    entries.forEach((entry) => container.appendChild(createRow(entry)));
          }

          function renderLineup(entries) {
                    lineupEntries = Array.isArray(entries) ? entries : [];
                    renderSide('home', homeContainer);
                    renderSide('away', awayContainer);
                    buildFormsFromState();
          }

          function buildFormsFromState() {
                    ['home', 'away'].forEach((side) => {
                              if (addForms[side]) {
                                        const select = addForms[side].querySelector('select[name="player_id"]');
                                        buildPlayerOptions(side, select);
                              }
                    });
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
                    const player = players.find((p) => p.id === payload.player_id);
                    const displayName = player ? player.display_name : '';
                    const body = {
                              match_id: matchId,
                              team_side: side,
                              player_id: payload.player_id,
                              display_name: displayName,
                              shirt_number: payload.shirt_number,
                              position_label: payload.position_label,
                              is_starting: payload.is_starting ? 1 : 0,
                    };
                    if (!cfg.matchPlayers?.add) {
                              throw new Error('Missing lineup endpoint');
                    }
                    await callJson(cfg.matchPlayers.add, body);
                    closeAddForm(side);
                    clearFlash();
                    setFlash('success', `${displayName} added to lineup`);
                    loadLineup();
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
                              await callJson(cfg.players.create, payload);
                              await fetchPlayers();
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

          addButtons.forEach((btn) => {
                    const side = btn.dataset.lineupAdd;
                    btn.addEventListener('click', () => addLineupForm(side));
          });

          teamSelects.home?.addEventListener('change', updateTeamLabels);
          teamSelects.away?.addEventListener('change', updateTeamLabels);

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
