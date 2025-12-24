(() => {
          const cfg = window.MatchWizardSetupConfig || {};
          console.debug('Match setup config', cfg);
          if (!cfg.basePath || !cfg.clubId) {
                    console.warn('Match setup config missing required context', cfg);
                    return;
          }

          document.addEventListener('DOMContentLoaded', () => {
                    const teamModal = document.getElementById('teamCreateModal');
                    const seasonModal = document.getElementById('seasonCreateModal');
                    const competitionModal = document.getElementById('competitionCreateModal');
                    const homeSelect = document.querySelector('select[name="home_team_id"]');
                    const awaySelect = document.querySelector('select[name="away_team_id"]');
                    const seasonSelect = document.querySelector('select[name="season_id"]');
                    const competitionSelect = document.querySelector('select[name="competition_id"]');
                    const teamForm = document.getElementById('teamCreateForm');
                    const seasonForm = document.getElementById('seasonCreateForm');
                    const competitionForm = document.getElementById('competitionCreateForm');
                    const teamError = document.getElementById('teamCreateError');
                    const seasonError = document.getElementById('seasonCreateError');
                    const competitionError = document.getElementById('competitionCreateError');

                    let activeTeamTarget = null;

                    function toggleModal(modal, show) {
                              if (!modal) return;
                              modal.style.display = show ? 'flex' : 'none';
                              modal.setAttribute('aria-hidden', show ? 'false' : 'true');
                    }

                    function resetError(node) {
                              if (!node) return;
                              node.classList.add('d-none');
                              node.textContent = '';
                    }

                    function showError(node, message) {
                              if (!node) return;
                              node.classList.remove('d-none');
                              node.textContent = message;
                    }

                    function appendOption(select, id, label) {
                              if (!select) return;
                              let exists = Array.from(select.options).find((opt) => opt.value === id.toString());
                              if (exists) {
                                        return exists;
                              }
                              const option = document.createElement('option');
                              option.value = id.toString();
                              option.textContent = label;
                              select.appendChild(option);
                              return option;
                    }

                    function handleOverlayClick(modal) {
                              if (!modal) return;
                              modal.addEventListener('click', (event) => {
                                        if (event.target === modal) {
                                                  toggleModal(modal, false);
                                        }
                              });
                    }

                    function addCloseHandlers(modal) {
                              if (!modal) return;
                              modal.querySelectorAll('[data-setup-close-modal]').forEach((btn) => {
                                        if (btn.dataset.setupCloseModal !== modal.id) {
                                                  return;
                                        }
                                        btn.addEventListener('click', () => {
                                                  toggleModal(modal, false);
                                        });
                              });
                    }

                    function callJson(url, payload) {
                              return fetch(url, {
                                        method: 'POST',
                                        headers: {
                                                  'Content-Type': 'application/json',
                                                  Accept: 'application/json',
                                        },
                                        body: JSON.stringify(payload),
                              }).then(async (res) => {
                                        const data = await res.json().catch(() => ({}));
                                        if (!res.ok || !data.ok) {
                                                  const message = data.error || 'Request failed';
                                                  throw new Error(message);
                                        }
                                        return data;
                              });
                    }

                    document.querySelectorAll('[data-add-team]').forEach((button) => {
                              button.addEventListener('click', () => {
                                        console.debug('Add team button clicked', button.dataset.addTeam);
                                       activeTeamTarget = button.dataset.addTeam;
                                       resetError(teamError);
                                       toggleModal(teamModal, true);
                                       requestAnimationFrame(() => {
                                                 teamForm?.querySelector('input[name="name"]')?.focus();
                                       });
                              });
                    });

                    const addSeasonTrigger = document.querySelector('[data-add-season]');
                    if (addSeasonTrigger) {
                              addSeasonTrigger.addEventListener('click', () => {
                                        console.debug('Add season triggered');
                                       resetError(seasonError);
                                       toggleModal(seasonModal, true);
                                        requestAnimationFrame(() => {
                                                  seasonForm?.querySelector('input[name="name"]')?.focus();
                                        });
                              });
                    }

                    const addCompetitionTrigger = document.querySelector('[data-add-competition]');
                    if (addCompetitionTrigger) {
                              addCompetitionTrigger.addEventListener('click', () => {
                                        console.debug('Add competition triggered');
                                       resetError(competitionError);
                                       toggleModal(competitionModal, true);
                                        requestAnimationFrame(() => {
                                                  competitionForm?.querySelector('input[name="name"]')?.focus();
                                        });
                              });
                    }

                    [teamModal, seasonModal, competitionModal].forEach((modal) => {
                              if (modal) {
                                        handleOverlayClick(modal);
                                        addCloseHandlers(modal);
                              }
                    });

                    if (teamForm) {
                              teamForm.addEventListener('submit', async (event) => {
                                        event.preventDefault();
                                        event.stopPropagation();
                                        console.debug('Team form submit', { target: activeTeamTarget });
                                        resetError(teamError);
                                        const name = teamForm.elements['name']?.value?.trim() || '';
                                        if (!name) {
                                                  showError(teamError, 'Team name required');
                                                  return;
                                        }
                                        try {
                                                  const url = cfg.endpoints?.teamCreate;
                                                  if (!url) {
                                                            throw new Error('Team endpoint missing');
                                                  }
                                                  const data = await callJson(url, {
                                                            club_id: cfg.clubId,
                                                            name,
                                                  });
                                                  const team = data.team;
                                                  if (team) {
                                                            appendOption(homeSelect, team.id, team.name);
                                                            appendOption(awaySelect, team.id, team.name);
                                                            if (activeTeamTarget === 'home' && homeSelect) {
                                                                      homeSelect.value = team.id;
                                                            }
                                                            if (activeTeamTarget === 'away' && awaySelect) {
                                                                      awaySelect.value = team.id;
                                                            }
                                                            toggleModal(teamModal, false);
                                                            teamForm.reset();
                                                  }
                                        } catch (error) {
                                                  showError(teamError, error.message || 'Unable to create team');
                                        }
                              });
                    }

                    if (seasonForm) {
                              seasonForm.addEventListener('submit', async (event) => {
                                        event.preventDefault();
                                        event.stopPropagation();
                                        console.debug('Season form submit');
                                        resetError(seasonError);
                                        const name = seasonForm.elements['name']?.value?.trim() || '';
                                        if (!name) {
                                                  showError(seasonError, 'Season name required');
                                                  return;
                                        }
                                        try {
                                                  const url = cfg.endpoints?.seasonCreate;
                                                  if (!url) {
                                                            throw new Error('Season endpoint missing');
                                                  }
                                                  const data = await callJson(url, {
                                                            club_id: cfg.clubId,
                                                            name,
                                                  });
                                                  const season = data.season;
                                                  if (season && seasonSelect) {
                                                            appendOption(seasonSelect, season.id, season.name);
                                                            seasonSelect.value = season.id;
                                                            toggleModal(seasonModal, false);
                                                            seasonForm.reset();
                                                  }
                                        } catch (error) {
                                                  showError(seasonError, error.message || 'Unable to create season');
                                        }
                              });
                    }

                    if (competitionForm) {
                              competitionForm.addEventListener('submit', async (event) => {
                                        event.preventDefault();
                                        event.stopPropagation();
                                        console.debug('Competition form submit');
                                        resetError(competitionError);
                                        const name = competitionForm.elements['name']?.value?.trim() || '';
                                        if (!name) {
                                                  showError(competitionError, 'Competition name required');
                                                  return;
                                        }
                                        try {
                                                  const url = cfg.endpoints?.competitionCreate;
                                                  if (!url) {
                                                            throw new Error('Competition endpoint missing');
                                                  }
                                                  const data = await callJson(url, {
                                                            club_id: cfg.clubId,
                                                            name,
                                                  });
                                                  const competition = data.competition;
                                                  if (competition && competitionSelect) {
                                                            appendOption(competitionSelect, competition.id, competition.name);
                                                            competitionSelect.value = competition.id;
                                                            toggleModal(competitionModal, false);
                                                            competitionForm.reset();
                                                  }
                                        } catch (error) {
                                                  showError(competitionError, error.message || 'Unable to create competition');
                                        }
                              });
                    }
          });
})();
