<div id="lineupCreatePlayerModal" aria-hidden="true" role="dialog" style="display:none; position:fixed; inset:0; z-index:2100; background:var(--modal-backdrop); align-items:center; justify-content:center;">
          <div class="panel p-3 rounded-md" style="max-width:480px; width:100%; margin:0 16px;">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                              <div>
                                        <div class="text-xs text-muted-alt">Create player</div>
                                        <div class="fw-semibold text-light">Add to club roster</div>
                              </div>
                              <button type="button" class="btn btn-sm btn-secondary-soft" data-lineup-modal-close aria-label="Close modal">×</button>
                    </div>
                    <form id="lineupCreatePlayerForm" class="row g-3">
                              <div class="col-12">
                                        <label class="form-label text-light" for="lineupPlayerDisplayName">Display name</label>
                                        <input type="text" name="display_name" id="lineupPlayerDisplayName" class="form-control input-dark" required>
                              </div>
                              <div class="col-6">
                                        <label class="form-label text-light" for="lineupPlayerFirstName">First name</label>
                                        <input type="text" name="first_name" id="lineupPlayerFirstName" class="form-control input-dark">
                              </div>
                              <div class="col-6">
                                        <label class="form-label text-light" for="lineupPlayerLastName">Last name</label>
                                        <input type="text" name="last_name" id="lineupPlayerLastName" class="form-control input-dark">
                              </div>
                              <div class="col-6">
                                        <label class="form-label text-light" for="lineupPlayerPosition">Primary position</label>
                                        <select name="primary_position" id="lineupPlayerPosition" class="form-select select-dark">
                                                  <option value="">Select position</option>
                                                  <?php foreach ($positionOptions as $position): ?>
                                                            <option value="<?= htmlspecialchars($position) ?>"><?= htmlspecialchars($position) ?></option>
                                                  <?php endforeach; ?>
                                        </select>
                              </div>
                              <div class="col-6">
                                        <label class="form-label text-light" for="lineupPlayerTeam">Team</label>
                                        <select name="team_id" id="lineupPlayerTeam" class="form-select select-dark">
                                                  <option value="">Unassigned</option>
                                                  <?php foreach ($teams as $team): ?>
                                                            <option value="<?= (int)$team['id'] ?>"><?= htmlspecialchars($team['name']) ?></option>
                                                  <?php endforeach; ?>
                                        </select>
                              </div>
                              <div class="col-12">
                                        <div id="lineupCreatePlayerError" class="text-danger small d-none"></div>
                              </div>
                              <div class="col-6">
                                        <label class="form-label text-light mb-0">Captain</label>
                                        <div class="form-check form-switch text-light">
                                                  <input type="checkbox" name="is_captain" id="lineupCreatePlayerCaptain" class="form-check-input">
                                                  <label class="form-check-label" for="lineupCreatePlayerCaptain">Mark as captain</label>
                                        </div>
                              </div>
                              <div class="col-12 d-flex justify-content-end gap-2">
                                        <button type="button" class="btn btn-secondary-soft" data-lineup-modal-close>Cancel</button>
                                        <button type="submit" class="btn btn-primary-soft">Create player</button>
                              </div>
                    </form>
</div>
</div>

<div id="lineupQuickAddModal" aria-hidden="true" role="dialog" style="display:none; position:fixed; inset:0; z-index:2200; background:var(--modal-backdrop); align-items:center; justify-content:center;">
          <div class="panel p-3 rounded-md" style="max-width:420px; width:100%; margin:0 16px;">
                              <div class="d-flex align-items-center justify-content-between mb-3">
                                        <div>
                                        <div class="text-lg text-white fw-semibold lineup-quick-add-title" id="lineupQuickAddTitle">Add player</div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-secondary-soft" data-lineup-quick-add-close aria-label="Close modal">×</button>
                              </div>
                    <form id="lineupQuickAddForm" class="row g-3">
                              <input type="hidden" name="lineup_entry_id" id="lineupQuickAddEntryId" value="">
                              <div class="col-12">
                                        <label class="form-label text-light" for="lineupQuickAddPlayer">Select player</label>
                                        <div class="d-flex align-items-center gap-2 lineup-quick-add-select-row">
                                                  <select name="player_id" id="lineupQuickAddPlayer" class="form-select select-dark flex-grow-1">
                                                            <option value="">Select player</option>
                                                  </select>
                                        <button type="button" class="btn btn-secondary-soft btn-sm lineup-quick-add-create" data-lineup-quick-add-create aria-label="Add new player">
                                                  <i class="fa-solid fa-plus" aria-hidden="true"></i>
                                        </button>
                                        </div>
                              </div>
                              <input type="hidden" name="position_label" id="lineupQuickAddPosition" value="">
                              <div class="col-6">
                                        <label class="form-label text-light" for="lineupQuickAddNumber">Jersey number</label>
                              </div>
                              <div class="col-6 d-flex justify-content-end align-items-center">
                                        <span class="form-label text-light mb-0">Captain</span>
                              </div>
                              <div class="col-6">
                                        <input type="text" inputmode="numeric" pattern="^[1-9][0-9]?$" maxlength="2" name="shirt_number" id="lineupQuickAddNumber" class="form-control input-dark" placeholder="Number (optional)" min="1" max="99">
                              </div>
                              <div class="col-6 d-flex justify-content-end">
                                        <div class="form-check form-switch text-light d-flex align-items-center gap-2 quick-add-captain-toggle">
                                                  <input type="checkbox" name="is_captain" id="lineupQuickAddCaptain" class="form-check-input" aria-label="Captain">
                                        </div>
                              </div>
                              <div class="col-12 d-flex justify-content-end gap-2">
                                        <button type="button" class="btn btn-secondary-soft" data-lineup-quick-add-close>Cancel</button>
                                        <button type="submit" class="btn btn-primary-soft" data-lineup-quick-add-submit>Save</button>
                              </div>
                    </form>
          </div>
</div>

<div id="lineupSubstituteModal" aria-hidden="true" role="dialog" style="display:none; position:fixed; inset:0; z-index:2200; background:var(--modal-backdrop); align-items:center; justify-content:center;">
          <div class="panel p-3 rounded-md" style="max-width:420px; width:100%; margin:0 16px;">
                              <div class="d-flex align-items-center justify-content-between mb-3">
                                        <div>
                                                  <div class="text-lg text-white fw-semibold" id="lineupSubstituteTitle">Add substitute</div>
                                                  <div class="text-xs text-muted-alt" id="lineupSubstituteSubtitle">Select a player to add to the bench</div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-secondary-soft" data-lineup-sub-close aria-label="Close modal">×</button>
                              </div>
                    <form id="lineupSubstituteForm" class="row g-3">
                              <input type="hidden" name="lineup_entry_id" id="lineupSubstituteEntryId" value="">
                              <input type="hidden" name="team_side" id="lineupSubstituteSide" value="">
                              <div class="col-12">
                                        <label class="form-label text-light" for="lineupSubstitutePlayer">Select player</label>
                                        <select name="player_id" id="lineupSubstitutePlayer" class="form-select select-dark">
                                                  <option value="">Select player</option>
                                        </select>
                              </div>
                              <div class="col-12">
                                        <label class="form-label text-light" for="lineupSubstituteNumber">Jersey number</label>
                                        <input type="text" inputmode="numeric" pattern="^[1-9][0-9]?$" maxlength="2" name="shirt_number" id="lineupSubstituteNumber" class="form-control input-dark" placeholder="Number (optional)">
                              </div>
                              <div class="col-12 d-flex justify-content-end gap-2">
                                        <button type="button" class="btn btn-secondary-soft" data-lineup-sub-close>Cancel</button>
                                        <button type="submit" class="btn btn-primary-soft">Save substitute</button>
                              </div>
                    </form>
          </div>
</div>

<div id="lineupSubstitutionModal" aria-hidden="true" role="dialog" style="display:none; position:fixed; inset:0; z-index:2200; background:var(--modal-backdrop); align-items:center; justify-content:center;">
          <div class="panel p-3 rounded-md" style="max-width:420px; width:100%; margin:0 16px;">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                              <div>
                                        <div class="text-lg text-white fw-semibold">Make substitution</div>
                                        <div class="text-xs text-muted-alt">Select players and timing</div>
                              </div>
                              <button type="button" class="btn btn-sm btn-secondary-soft" data-lineup-substitution-close aria-label="Close modal">×</button>
                    </div>
                    <form id="lineupSubstitutionForm" class="row g-3">
                              <input type="hidden" name="team_side" value="">
                              <div class="col-12">
                                        <div class="row g-3">
                                                  <div class="col-6">
                                                            <h6 class="text-light mb-2">Players on pitch</h6>
                                                            <div class="sub-player-list lineup-substitution-list" data-sub-list="off"></div>
                                                  </div>
                                                  <div class="col-6">
                                                            <h6 class="text-light mb-2">Available subs</h6>
                                                            <div class="sub-player-list lineup-substitution-list" data-sub-list="on"></div>
                                                  </div>
                                        </div>
                              </div>
                              <div class="col-6">
                                        <label class="form-label text-light text-xs" for="lineupSubstitutionMinute">Minute</label>
                                        <input type="number" id="lineupSubstitutionMinute" name="minute" class="form-control input-dark" min="0" step="1" required>
                              </div>
                              <div class="col-6">
                                        <label class="form-label text-light text-xs" for="lineupSubstitutionMinuteExtra">Minute extra</label>
                                        <input type="number" id="lineupSubstitutionMinuteExtra" name="minute_extra" class="form-control input-dark" min="0" step="1">
                              </div>
                              <div class="col-12">
                                        <label class="form-label text-light text-xs" for="lineupSubstitutionReason">Reason</label>
                                        <select id="lineupSubstitutionReason" name="reason" class="form-select select-dark">
                                                  <option value="tactical">Tactical</option>
                                                  <option value="injury">Injury</option>
                                                  <option value="fitness">Fitness</option>
                                                  <option value="disciplinary">Disciplinary</option>
                                                  <option value="unknown">Unknown</option>
                                        </select>
                              </div>
                              <div class="col-12 d-flex justify-content-end gap-2">
                                        <button type="button" class="btn btn-secondary-soft" data-lineup-substitution-close>Cancel</button>
                                        <button type="submit" class="btn btn-primary-soft" data-lineup-substitution-submit disabled>Confirm</button>
                              </div>
                    </form>
          </div>
</div>
