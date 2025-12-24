<div id="lineupCreatePlayerModal" aria-hidden="true" role="dialog" style="display:none; position:fixed; inset:0; z-index:2100; background:rgba(0,0,0,0.6); align-items:center; justify-content:center;">
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
                                        <input type="text" name="primary_position" id="lineupPlayerPosition" class="form-control input-dark">
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
                              <div class="col-12 d-flex justify-content-end gap-2">
                                        <button type="button" class="btn btn-secondary-soft" data-lineup-modal-close>Cancel</button>
                                        <button type="submit" class="btn btn-primary-soft">Create player</button>
                              </div>
                    </form>
          </div>
</div>
