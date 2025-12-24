<div class="panel p-3 rounded-md">
          <div class="panel-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                              <div>
                                        <div class="text-muted-alt text-sm">Step 4</div>
                                        <div class="text-lg text-white fw-semibold">Player lineup</div>
                              </div>
                              <div class="d-flex gap-2 align-items-center">
                                        <button type="button" class="btn btn-sm btn-outline-light" id="lineupCreatePlayerBtn">Create player</button>
                                        <span id="lineupStatusBadge" class="wizard-status wizard-status-pending">Pending</span>
                              </div>
                    </div>
                    <div id="lineupFlash" class="alert d-none" role="alert"></div>

                    <div class="row g-3">
                              <div class="col-lg-6">
                                        <div class="lineup-section" data-lineup-side="home">
                                                  <div class="d-flex align-items-center justify-content-between mb-2">
                                                            <div>
                                                                      <div class="text-xs text-muted-alt">Home</div>
                                                                      <div id="lineupHomeLabel" class="fw-semibold text-light">Home lineup</div>
                                                            </div>
                                                            <button type="button" class="btn btn-sm btn-outline-light" data-lineup-add="home">+ Add player</button>
                                                  </div>
                                                  <div class="lineup-list border border-soft rounded-md bg-black" data-lineup-side="home">
                                                            <div class="text-muted text-sm px-3 py-2" data-lineup-empty>Lineup pending.</div>
                                                  </div>
                                        </div>
                              </div>
                              <div class="col-lg-6">
                                        <div class="lineup-section" data-lineup-side="away">
                                                  <div class="d-flex align-items-center justify-content-between mb-2">
                                                            <div>
                                                                      <div class="text-xs text-muted-alt">Away</div>
                                                                      <div id="lineupAwayLabel" class="fw-semibold text-light">Away lineup</div>
                                                            </div>
                                                            <button type="button" class="btn btn-sm btn-outline-light" data-lineup-add="away">+ Add player</button>
                                                  </div>
                                                  <div class="lineup-list border border-soft rounded-md bg-black" data-lineup-side="away">
                                                            <div class="text-muted text-sm px-3 py-2" data-lineup-empty>Lineup pending.</div>
                                                  </div>
                                        </div>
                              </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center gap-2 mt-3 flex-wrap">
                              <button type="button" class="btn btn-secondary-soft" id="lineupBackBtn">Back to download</button>
                              <div class="d-flex gap-2">
                                        <a href="#" id="lineupOverviewBtn" class="btn btn-primary-soft disabled" aria-disabled="true">Match overview</a>
                                        <a href="#" id="lineupDeskBtn" class="btn btn-outline-secondary-soft disabled" aria-disabled="true">Analysis desk</a>
                              </div>
                    </div>
          </div>
</div>
