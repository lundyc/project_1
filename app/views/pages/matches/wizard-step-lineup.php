<?php
$formationPositions = [
          ['label' => 'GK', 'style' => 'left: 50%; bottom: 0%; transform: translate(-50%, 50%) rotate(0deg);'],
          ['label' => 'LB', 'style' => 'left: 0%; bottom: 33.3333%; transform: translate(-50%, 50%) rotate(0deg);'],
          ['label' => 'CB', 'style' => 'left: 33.3333%; bottom: 33.3333%; transform: translate(-50%, 50%) rotate(0deg);'],
          ['label' => 'CB', 'style' => 'left: 66.6667%; bottom: 33.3333%; transform: translate(-50%, 50%) rotate(0deg);'],
          ['label' => 'RB', 'style' => 'left: 100%; bottom: 33.3333%; transform: translate(-50%, 50%) rotate(0deg);'],
          ['label' => 'LM', 'style' => 'left: 0%; bottom: 66.6667%; transform: translate(-50%, 50%) rotate(0deg);'],
          ['label' => 'CM', 'style' => 'left: 33.3333%; bottom: 66.6667%; transform: translate(-50%, 50%) rotate(0deg);'],
          ['label' => 'CM', 'style' => 'left: 66.6667%; bottom: 66.6667%; transform: translate(-50%, 50%) rotate(0deg);'],
          ['label' => 'RM', 'style' => 'left: 100%; bottom: 66.6667%; transform: translate(-50%, 50%) rotate(0deg);'],
          ['label' => 'ST', 'style' => 'left: 25%; bottom: 100%; transform: translate(-50%, 50%) rotate(0deg);'],
          ['label' => 'ST', 'style' => 'left: 75%; bottom: 100%; transform: translate(-50%, 50%) rotate(0deg);'],
];
$positionOptions = array_values(array_unique(array_column($formationPositions, 'label')));
?>
<div class="panel p-3 rounded-md">
          <div class="panel-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                              <div>
                                        <div class="text-muted-alt text-sm">Step 4</div>
                                        <div class="text-lg text-white fw-semibold">Player lineup</div>
                              </div>
                              <div class="d-flex gap-2 align-items-center">
                                        <span id="lineupStatusBadge" class="wizard-status wizard-status-pending">Pending</span>
                              </div>
                    </div>
                    <div id="lineupFlash" class="alert d-none" role="alert"></div>

                    <div class="lineup-grid">
                              <?php foreach (['home' => 'lineupHomeLabel', 'away' => 'lineupAwayLabel'] as $side => $labelId): ?>
                                        <div class="lineup-card" data-lineup-side="<?= $side ?>">
                                        <div class="lineup-card-header">
                                                  <div>
                                                            <div class="text-xs text-muted-alt"><?= ucfirst($side) ?></div>
                                                            <div id="<?= $labelId ?>" class="lineup-card-title text-light"><?= ucfirst($side) ?> lineup</div>
                                                            <div class="lineup-card-caption">Starting lineup · 4-4-2</div>
                                                  </div>
                                        </div>
                                                  <div class="lineup-formation formation-pitch">
                                                            <div class="formation-pitch">
                                                                      <svg width="298" height="386" viewBox="0 0 298 386" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                                <path fill-rule="evenodd" clip-rule="evenodd" d="M1.00024 373C1.00024 379.627 6.37283 385 13.0002 385L285 385C291.628 385 297 379.627 297 373L297 13C297 6.37259 291.628 1 285 1L13.0002 1C6.37283 1 1.00024 6.37259 1.00024 13L1.00024 373Z" fill="none" stroke="#575757" stroke-width="2"></path>
                                                                                <path fill-rule="evenodd" clip-rule="evenodd" d="M122.882 314.635C134.115 291.068 164.057 292.011 175.117 314.735L122.882 314.635Z" fill="none"></path>
                                                                                <path d="M122.882 314.635C134.115 291.068 164.057 292.011 175.117 314.735" stroke="#575757" stroke-width="2"></path>
                                                                                <path d="M228 315C232.418 315 236 318.582 236 323L236 385L62 385L62 323C62 318.582 65.5817 315 70 315L228 315Z" fill="none" stroke="#575757" stroke-width="2"></path>
                                                                                <line x1="297" y1="150.323" x2="1.00003" y2="150.323" stroke="#575757" stroke-width="2"></line>
                                                                                <rect x="187.177" y="113.323" width="76.3529" height="76.3529" rx="38.1765" transform="rotate(90 187.177 113.323)" fill="none" stroke="#575757" stroke-width="2"></rect>
                                                                      </svg>
                                                                      <div class="formation-pitch-content">
                                                                                <div class="formation">
                                                            <div class="formation-positions">
                                                                      <?php foreach ($formationPositions as $position): ?>
                                                        <div class="formation-position on-pitch" style="<?= htmlspecialchars($position['style']) ?>">
                                                          <button class="formation-position-button player-select-button lineup-formation-slot" type="button" data-position-label="<?= htmlspecialchars($position['label']) ?>">
                                                                    <span class="lineup-slot-indicator" aria-hidden="true"><i class="fa-solid fa-plus"></i></span>
                                                                    <span class="lineup-slot-captain-badge" aria-hidden="true"></span>
                                                          </button>
                                                          <span class="position-label">
                                                                    <span class="position-label-name" aria-hidden="true"></span>
                                                                    <span class="position-label-role" data-default-label="<?= htmlspecialchars($position['label']) ?>"><?= htmlspecialchars($position['label']) ?></span>
                                                          </span>
                                                        </div>
                                                                      <?php endforeach; ?>
                                                            </div>
                                                                                </div>
                                                                      </div>
                                                            </div>
                                                  </div>
                                                  <div class="lineup-forms" data-lineup-side="<?= $side ?>"></div>
                                                  <div class="lineup-substitutes-column mt-3" data-lineup-substitutes-side="<?= $side ?>">
                                                            <div class="lineup-substitutes-header">
                                                                      <div>
                                                                                <div class="text-xs text-muted-alt"><?= ucfirst($side) ?> lineup</div>
                                                                                <div class="text-sm text-white fw-semibold">Substitutes</div>
                                                                      </div>
                                                                      <button type="button" class="lineup-substitutes-add" data-lineup-sub-add="<?= $side ?>" aria-label="Add <?= strtolower($side) ?> substitute">
                                                                                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                                                                      </button>
                                                            </div>
                                                            <div id="lineupSubstitutes<?= ucfirst($side) ?>" class="lineup-substitutes-list" aria-live="polite"></div>
                                                  </div>
                                        </div>
                              <?php endforeach; ?>
                    </div>
                    <div class="d-flex justify-content-between align-items-center gap-2 mt-3 flex-wrap">
                              <button type="button" class="btn btn-secondary-soft" id="lineupBackBtn">Back to download</button>
                              <div class="d-flex gap-2">
                                        <a href="#" id="lineupOverviewBtn" class="btn btn-primary-soft disabled" aria-disabled="true">Match overview</a>
                                        <a href="#" id="lineupDeskBtn" class="btn btn-secondary-soft disabled" aria-disabled="true">Analysis desk</a>
                              </div>
                    </div>
          </div>
</div>
