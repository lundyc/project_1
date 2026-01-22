/**
 * Match Edit - Accordion Navigation & Event Tabs
 */

(function () {
          'use strict';

          // Section Navigation
          const navItems = document.querySelectorAll('.edit-nav-item');
          const sections = document.querySelectorAll('.edit-section');

          navItems.forEach(item => {
                    item.addEventListener('click', function () {
                              const sectionId = this.getAttribute('data-section');

                              // Update nav active state
                              navItems.forEach(nav => nav.classList.remove('active'));
                              this.classList.add('active');

                              // Show selected section, hide others
                              sections.forEach(section => {
                                        if (section.id === `section-${sectionId}`) {
                                                  section.style.display = 'block';
                                                  section.classList.add('active');
                                                  // Smooth scroll to section
                                                  section.scrollIntoView({ behavior: 'smooth', block: 'start' });
                                        } else {
                                                  section.style.display = 'none';
                                                  section.classList.remove('active');
                                        }
                              });

                              // Save active section to localStorage
                              localStorage.setItem('matchEditActiveSection', sectionId);
                    });
          });

          // Event Tabs
          const eventTabs = document.querySelectorAll('.event-tab');
          const eventTabContents = document.querySelectorAll('.event-tab-content');

          eventTabs.forEach(tab => {
                    tab.addEventListener('click', function () {
                              const tabId = this.getAttribute('data-tab');

                              // Update tab active state
                              eventTabs.forEach(t => t.classList.remove('active'));
                              this.classList.add('active');

                              // Show selected tab content
                              eventTabContents.forEach(content => {
                                        if (content.id === `tab-${tabId}`) {
                                                  content.style.display = 'block';
                                                  content.classList.add('active');
                                        } else {
                                                  content.style.display = 'none';
                                                  content.classList.remove('active');
                                        }
                              });

                              // Save active tab to localStorage
                              localStorage.setItem('matchEditActiveTab', tabId);
                    });
          });

          // Restore last active section and tab from localStorage
          const savedSection = localStorage.getItem('matchEditActiveSection');
          const savedTab = localStorage.getItem('matchEditActiveTab');

          if (savedSection) {
                    const targetNav = document.querySelector(`.edit-nav-item[data-section="${savedSection}"]`);
                    if (targetNav) {
                              targetNav.click();
                    }
          }

          if (savedTab) {
                    const targetTab = document.querySelector(`.event-tab[data-tab="${savedTab}"]`);
                    if (targetTab) {
                              targetTab.click();
                    }
          }

          console.log('[match-edit] Navigation initialized');

          // Player Lineup Management
          const config = window.MatchEditConfig || {};
          const clubPlayers = window.clubPlayers || [];
          const modal = document.getElementById('addPlayerModal');
          const form = document.getElementById('addPlayerForm');
          const errorDiv = document.getElementById('player-form-error');

          let currentTeamSide = null;
          let currentIsStarting = null;

          // Open modal
          document.querySelectorAll('[data-add-player]').forEach(btn => {
                    btn.addEventListener('click', function () {
                              currentTeamSide = this.getAttribute('data-add-player');
                              currentIsStarting = this.getAttribute('data-is-starting');

                              document.getElementById('player-team-side').value = currentTeamSide;
                              document.getElementById('player-is-starting').value = currentIsStarting;

                              populatePlayerSelect(currentTeamSide);
                              form.reset();
                              errorDiv.classList.add('hidden');
                              modal.style.display = 'block';
                    });
          });

          // Close modal
          document.querySelectorAll('[data-close-modal]').forEach(btn => {
                    btn.addEventListener('click', () => {
                              modal.style.display = 'none';
                    });
          });

          modal.querySelector('.modal-backdrop')?.addEventListener('click', () => {
                    modal.style.display = 'none';
          });

          // Populate player select
          function populatePlayerSelect(teamSide) {
                    const wrapper = document.getElementById('player-select-wrapper');

                    if (teamSide === 'home') {
                              // Home team: dropdown from club players
                              wrapper.innerHTML = `
                                        <select id="player-id" name="player_id" required class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white focus:border-blue-500 focus:outline-none">
                                                  <option value="">Select player</option>
                                                  ${clubPlayers.map(p => `<option value="${p.id}">${p.name}</option>`).join('')}
                                        </select>
                              `;
                    } else {
                              // Away team: text input
                              wrapper.innerHTML = `
                                        <input type="text" id="player-name" name="player_name" required placeholder="Player name" 
                                                 class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white placeholder-slate-500 focus:border-blue-500 focus:outline-none">
                              `;
                    }
          }

          // Add player form submit
          form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    errorDiv.classList.add('hidden');

                    const formData = new FormData(form);
                    const data = {
                              match_id: config.matchId,
                              team_side: formData.get('team_side'),
                              is_starting: parseInt(formData.get('is_starting')),
                              shirt_number: formData.get('shirt_number'),
                              position_label: formData.get('position_label'),
                              is_captain: formData.get('is_captain') ? 1 : 0,
                    };

                    if (data.team_side === 'home') {
                              data.player_id = formData.get('player_id');
                    } else {
                              data.player_name = formData.get('player_name');
                    }

                    try {
                              const response = await fetch(config.endpoints.matchPlayersAdd, {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/json' },
                                        body: JSON.stringify(data),
                              });

                              const result = await response.json();

                              if (!response.ok || !result.success) {
                                        throw new Error(result.error || 'Failed to add player');
                              }

                              // Reload page to show new player
                              window.location.reload();
                    } catch (error) {
                              errorDiv.textContent = error.message;
                              errorDiv.classList.remove('hidden');
                    }
          });

          // Delete player
          document.querySelectorAll('[data-delete-player]').forEach(btn => {
                    btn.addEventListener('click', async function () {
                              if (!confirm('Remove this player from the lineup?')) return;

                              const matchPlayerId = this.getAttribute('data-delete-player');

                              try {
                                        const response = await fetch(config.endpoints.matchPlayersDelete, {
                                                  method: 'POST',
                                                  headers: { 'Content-Type': 'application/json' },
                                                  body: JSON.stringify({
                                                            match_id: config.matchId,
                                                            id: parseInt(matchPlayerId)
                                                  }),
                                        });

                                        const result = await response.json();

                                        if (!response.ok || !result.success) {
                                                  throw new Error(result.error || 'Failed to delete player');
                                        }

                                        // Reload page
                                        window.location.reload();
                              } catch (error) {
                                        alert('Error: ' + error.message);
                              }
                    });
          });

          console.log('[match-edit] Player lineup management initialized');

          // ====================================
          // Match Events Management
          // ====================================

          const goalModal = document.getElementById('addGoalModal');
          const cardModal = document.getElementById('addCardModal');
          const subModal = document.getElementById('addSubstitutionModal');
          const goalForm = document.getElementById('addGoalForm');
          const cardForm = document.getElementById('addCardForm');
          const subForm = document.getElementById('addSubstitutionForm');
          const goalEventIdInput = document.getElementById('goal-event-id');
          const goalEventTypeIdInput = document.getElementById('goal-event-type-id');
          const goalSubmitLabel = goalForm?.querySelector('.goal-submit-label');
          const cardEventIdInput = document.getElementById('card-event-id');
          const cardEventTypeIdInput = document.getElementById('card-event-type-id');
          const cardSubmitLabel = cardForm?.querySelector('.card-submit-label');
          const goalMinuteInput = document.getElementById('goal-minute');
          const goalMinuteExtraInput = document.getElementById('goal-minute-extra');
          const cardMinuteInput = document.getElementById('card-minute');
          const cardMinuteExtraInput = document.getElementById('card-minute-extra');
          const cardNotesInput = document.getElementById('card-notes');

          const EVENT_TYPE_IDS = {
                    goal: 16,
                    yellow: 8,
                    red: 9,
          };

          // Helper: Get match players by team and type
          function getMatchPlayersByTeam(teamSide, isStarting = null) {
                    const allPlayers = config.matchPlayers || [];
                    return allPlayers.filter(p => {
                              if (p.team_side !== teamSide) return false;
                              if (isStarting !== null && Boolean(p.is_starting) !== Boolean(isStarting)) return false;
                              return true;
                    });
          }

          // Helper: Create player select HTML
          function createPlayerSelect(players, includeUnknown = true) {
                    let html = '<select required class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white focus:border-blue-500 focus:outline-none">';
                    html += '<option value="">Select player</option>';
                    if (includeUnknown) {
                              html += '<option value="unknown">Unknown</option>';
                    }
                    players.forEach(p => {
                              const name = p.full_name || p.player_name || 'Unknown';
                              const label = `#${p.shirt_number || '?'} ${name}`;
                              html += `<option value="${p.id}">${label}</option>`;
                    });
                    html += '</select>';
                    return html;
          }

          // Close modal handlers
          document.querySelectorAll('[data-close-modal]').forEach(btn => {
                    btn.addEventListener('click', function () {
                              const modalType = this.getAttribute('data-close-modal');
                              if (modalType === 'goal') goalModal.style.display = 'none';
                              if (modalType === 'card') cardModal.style.display = 'none';
                              if (modalType === 'substitution') subModal.style.display = 'none';
                    });
          });

          // Close on backdrop click
          [goalModal, cardModal, subModal].forEach(modal => {
                    if (modal) {
                              modal.querySelector('.modal-backdrop')?.addEventListener('click', () => {
                                        modal.style.display = 'none';
                              });
                    }
          });

          // Update player select when team changes
          function setupTeamChangeHandler(formElement, selectWrapperElement, includeUnknown = true) {
                    const radios = formElement.querySelectorAll('input[name="team_side"]');
                    radios.forEach(radio => {
                              radio.addEventListener('change', function () {
                                        const teamSide = this.value;
                                        const players = getMatchPlayersByTeam(teamSide);
                                        selectWrapperElement.innerHTML = createPlayerSelect(players, includeUnknown);
                              });
                    });
          }

          // ====================================
          // Add Goal
          // ====================================
          document.querySelectorAll('[data-add-goal]').forEach(btn => {
                    btn.addEventListener('click', function () {
                              goalForm.reset();
                              if (goalEventIdInput) goalEventIdInput.value = '';
                              if (goalEventTypeIdInput) goalEventTypeIdInput.value = EVENT_TYPE_IDS.goal;
                              document.getElementById('goal-form-error').classList.add('hidden');
                              document.getElementById('goal-player-select-wrapper').innerHTML = '<p class="text-sm text-slate-500">Select a team first</p>';
                              if (goalSubmitLabel) goalSubmitLabel.textContent = 'Add Goal';
                              const titleEl = goalModal.querySelector('.modal-title');
                              if (titleEl) titleEl.textContent = 'Add Goal';
                              goalModal.style.display = 'block';
                    });
          });

          setupTeamChangeHandler(goalForm, document.getElementById('goal-player-select-wrapper'));

          // Edit Goal
          document.querySelectorAll('[data-edit-goal]').forEach(btn => {
                    btn.addEventListener('click', () => {
                              goalForm.reset();
                              const dataset = btn.dataset;
                              const teamSide = dataset.teamSide || 'home';
                              const minute = dataset.minute || '0';
                              const minuteExtra = dataset.minuteExtra || '0';
                              const matchPlayerId = dataset.matchPlayerId || '';
                              const eventId = dataset.eventId || '';
                              const eventTypeId = dataset.eventTypeId || EVENT_TYPE_IDS.goal;

                              if (goalEventIdInput) goalEventIdInput.value = eventId;
                              if (goalEventTypeIdInput) goalEventTypeIdInput.value = eventTypeId;
                              if (goalSubmitLabel) goalSubmitLabel.textContent = 'Update Goal';
                              const titleEl = goalModal.querySelector('.modal-title');
                              if (titleEl) titleEl.textContent = 'Edit Goal';

                              const teamRadio = goalForm.querySelector(`input[name="team_side"][value="${teamSide}"]`);
                              if (teamRadio) teamRadio.checked = true;

                              if (goalMinuteInput) goalMinuteInput.value = minute;
                              if (goalMinuteExtraInput) goalMinuteExtraInput.value = minuteExtra;

                              const players = getMatchPlayersByTeam(teamSide);
                              document.getElementById('goal-player-select-wrapper').innerHTML = createPlayerSelect(players, true);
                              const playerSelect = document.querySelector('#goal-player-select-wrapper select');
                              if (playerSelect) {
                                        playerSelect.value = matchPlayerId && matchPlayerId !== '0' ? String(matchPlayerId) : 'unknown';
                              }

                              goalModal.style.display = 'block';
                    });
          });

          goalForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const errorDiv = document.getElementById('goal-form-error');
                    errorDiv.classList.add('hidden');

                    const formData = new FormData(goalForm);
                    const playerSelect = goalForm.querySelector('select');
                    const playerValue = playerSelect?.value;

                    const isEdit = Boolean(goalEventIdInput?.value);
                    const data = {
                              match_id: config.matchId,
                              event_type_id: parseInt(goalEventTypeIdInput?.value || EVENT_TYPE_IDS.goal),
                              team_side: formData.get('team_side'),
                              minute: parseInt(formData.get('minute')),
                              minute_extra: parseInt(formData.get('minute_extra')) || 0,
                    };

                    if (playerValue && playerValue !== 'unknown') {
                              data.match_player_id = parseInt(playerValue);
                    }

                    if (isEdit) {
                              data.event_id = parseInt(goalEventIdInput.value);
                    }

                    try {
                              const response = await fetch(config.basePath + (isEdit ? `/api/matches/${config.matchId}/events/update` : '/api/events/create'), {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/json' },
                                        body: JSON.stringify(data),
                              });

                              const result = await response.json();
                              if (!response.ok || !result.success) {
                                        throw new Error(result.error || (isEdit ? 'Failed to update goal' : 'Failed to add goal'));
                              }

                              window.location.reload();
                    } catch (error) {
                              errorDiv.textContent = error.message;
                              errorDiv.classList.remove('hidden');
                    }
          });

          // ====================================
          // Add Card
          // ====================================
          let currentCardType = 'yellow';

          document.querySelectorAll('[data-add-card]').forEach(btn => {
                    btn.addEventListener('click', function () {
                              currentCardType = this.getAttribute('data-add-card');
                              document.getElementById('card-type').value = currentCardType;
                              cardForm.reset();
                              if (cardEventIdInput) cardEventIdInput.value = '';
                              if (cardEventTypeIdInput) cardEventTypeIdInput.value = currentCardType === 'yellow' ? EVENT_TYPE_IDS.yellow : EVENT_TYPE_IDS.red;
                              document.getElementById('card-form-error').classList.add('hidden');
                              document.getElementById('card-player-select-wrapper').innerHTML = '<p class="text-sm text-slate-500">Select a team first</p>';

                              const title = currentCardType === 'yellow' ? 'Add Yellow Card' : 'Add Red Card';
                              cardModal.querySelector('.modal-title').textContent = title;
                              if (cardSubmitLabel) cardSubmitLabel.textContent = 'Add Card';
                              cardModal.style.display = 'block';
                    });
          });

          setupTeamChangeHandler(cardForm, document.getElementById('card-player-select-wrapper'));

          // Edit Card
          document.querySelectorAll('[data-edit-card]').forEach(btn => {
                    btn.addEventListener('click', () => {
                              cardForm.reset();
                              const dataset = btn.dataset;
                              const teamSide = dataset.teamSide || 'home';
                              const minute = dataset.minute || '0';
                              const minuteExtra = dataset.minuteExtra || '0';
                              const matchPlayerId = dataset.matchPlayerId || '';
                              const notes = dataset.notes || '';
                              const eventId = dataset.eventId || '';
                              const cardType = dataset.cardType || 'yellow';
                              const eventTypeId = dataset.eventTypeId || (cardType === 'yellow' ? EVENT_TYPE_IDS.yellow : EVENT_TYPE_IDS.red);

                              currentCardType = cardType;
                              document.getElementById('card-type').value = currentCardType;
                              if (cardEventIdInput) cardEventIdInput.value = eventId;
                              if (cardEventTypeIdInput) cardEventTypeIdInput.value = eventTypeId;
                              if (cardSubmitLabel) cardSubmitLabel.textContent = 'Update Card';
                              const titleEl = cardModal.querySelector('.modal-title');
                              if (titleEl) titleEl.textContent = currentCardType === 'yellow' ? 'Edit Yellow Card' : 'Edit Red Card';

                              const teamRadio = cardForm.querySelector(`input[name="team_side"][value="${teamSide}"]`);
                              if (teamRadio) teamRadio.checked = true;
                              if (cardMinuteInput) cardMinuteInput.value = minute;
                              if (cardMinuteExtraInput) cardMinuteExtraInput.value = minuteExtra;
                              if (cardNotesInput) cardNotesInput.value = notes;

                              const players = getMatchPlayersByTeam(teamSide);
                              document.getElementById('card-player-select-wrapper').innerHTML = createPlayerSelect(players, true);
                              const playerSelect = document.querySelector('#card-player-select-wrapper select');
                              if (playerSelect) {
                                        playerSelect.value = matchPlayerId && matchPlayerId !== '0' ? String(matchPlayerId) : 'unknown';
                              }

                              cardModal.style.display = 'block';
                    });
          });

          cardForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const errorDiv = document.getElementById('card-form-error');
                    errorDiv.classList.add('hidden');

                    const formData = new FormData(cardForm);
                    const playerSelect = cardForm.querySelector('select');
                    const playerValue = playerSelect?.value;

                    const isEdit = Boolean(cardEventIdInput?.value);
                    const data = {
                              match_id: config.matchId,
                              event_type_id: parseInt(cardEventTypeIdInput?.value || (currentCardType === 'yellow' ? EVENT_TYPE_IDS.yellow : EVENT_TYPE_IDS.red)),
                              team_side: formData.get('team_side'),
                              minute: parseInt(formData.get('minute')),
                              minute_extra: parseInt(formData.get('minute_extra')) || 0,
                              notes: formData.get('notes') || null,
                    };

                    if (playerValue && playerValue !== 'unknown') {
                              data.match_player_id = parseInt(playerValue);
                    }

                    if (isEdit) {
                              data.event_id = parseInt(cardEventIdInput.value);
                    }

                    try {
                              const response = await fetch(config.basePath + (isEdit ? `/api/matches/${config.matchId}/events/update` : '/api/events/create'), {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/json' },
                                        body: JSON.stringify(data),
                              });

                              const result = await response.json();
                              if (!response.ok || !result.success) {
                                        throw new Error(result.error || (isEdit ? 'Failed to update card' : 'Failed to add card'));
                              }

                              window.location.reload();
                    } catch (error) {
                              errorDiv.textContent = error.message;
                              errorDiv.classList.remove('hidden');
                    }
          });

          // ====================================
          // Add Substitution
          // ====================================
          document.querySelectorAll('[data-add-substitution]').forEach(btn => {
                    btn.addEventListener('click', function () {
                              subForm.reset();
                              document.getElementById('sub-form-error').classList.add('hidden');
                              document.getElementById('sub-player-off-select-wrapper').innerHTML = '<p class="text-sm text-slate-500">Select a team first</p>';
                              document.getElementById('sub-player-on-select-wrapper').innerHTML = '<p class="text-sm text-slate-500">Select a team first</p>';
                              subModal.style.display = 'block';
                    });
          });

          // Update sub player dropdowns when team changes
          const subTeamRadios = subForm.querySelectorAll('input[name="team_side"]');
          subTeamRadios.forEach(radio => {
                    radio.addEventListener('change', function () {
                              const teamSide = this.value;
                              const starters = getMatchPlayersByTeam(teamSide, true);
                              const subs = getMatchPlayersByTeam(teamSide, false);

                              document.getElementById('sub-player-off-select-wrapper').innerHTML = createPlayerSelect(starters, false);
                              document.getElementById('sub-player-on-select-wrapper').innerHTML = createPlayerSelect(subs, false);
                    });
          });

          subForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const errorDiv = document.getElementById('sub-form-error');
                    errorDiv.classList.add('hidden');

                    const formData = new FormData(subForm);
                    const playerOffSelect = document.querySelector('#sub-player-off-select-wrapper select');
                    const playerOnSelect = document.querySelector('#sub-player-on-select-wrapper select');

                    const data = {
                              match_id: config.matchId,
                              team_side: formData.get('team_side'),
                              minute: parseInt(formData.get('minute')),
                              player_off_match_player_id: parseInt(playerOffSelect?.value),
                              player_on_match_player_id: parseInt(playerOnSelect?.value),
                              reason: formData.get('reason') || null,
                    };

                    try {
                              const response = await fetch(config.basePath + '/api/match-substitutions/create', {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/json' },
                                        body: JSON.stringify(data),
                              });

                              const result = await response.json();
                              if (!response.ok || !result.success) {
                                        throw new Error(result.error || 'Failed to add substitution');
                              }

                              window.location.reload();
                    } catch (error) {
                              errorDiv.textContent = error.message;
                              errorDiv.classList.remove('hidden');
                    }
          });

          // ====================================
          // Delete Event
          // ====================================
          document.querySelectorAll('[data-delete-event]').forEach(btn => {
                    btn.addEventListener('click', async function () {
                              if (!confirm('Delete this event?')) return;

                              const eventId = parseInt(this.getAttribute('data-delete-event'));

                              try {
                                        const response = await fetch(config.basePath + '/api/events/delete', {
                                                  method: 'POST',
                                                  headers: { 'Content-Type': 'application/json' },
                                                  body: JSON.stringify({ event_id: eventId }),
                                        });

                                        const result = await response.json();
                                        if (!response.ok || !result.success) {
                                                  throw new Error(result.error || 'Failed to delete event');
                                        }

                                        window.location.reload();
                              } catch (error) {
                                        alert('Error: ' + error.message);
                              }
                    });
          });

          // ====================================
          // Delete Substitution
          // ====================================
          document.querySelectorAll('[data-delete-substitution]').forEach(btn => {
                    btn.addEventListener('click', async function () {
                              if (!confirm('Delete this substitution?')) return;

                              const subId = parseInt(this.getAttribute('data-delete-substitution'));

                              try {
                                        const response = await fetch(config.basePath + '/api/match-substitutions/delete', {
                                                  method: 'POST',
                                                  headers: { 'Content-Type': 'application/json' },
                                                  body: JSON.stringify({
                                                            match_id: config.matchId,
                                                            id: subId
                                                  }),
                                        });

                                        const result = await response.json();
                                        if (!response.ok || !result.success) {
                                                  throw new Error(result.error || 'Failed to delete substitution');
                                        }

                                        window.location.reload();
                              } catch (error) {
                                        alert('Error: ' + error.message);
                              }
                    });
          });

          console.log('[match-edit] Match events management initialized');
})();
