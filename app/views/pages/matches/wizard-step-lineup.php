<?php $formationLabels = [
          'home' => $homeFormationLabel ?? 'Unset',
          'away' => $awayFormationLabel ?? 'Unset',
]; ?>
<div class="panel p-3 rounded-md">
          <div class="panel-body">
                    <div id="lineupFlash" class="alert d-none" role="alert"></div>

                    <div class="lineup-grid">
                              <?php foreach (['home' => 'lineupHomeLabel', 'away' => 'lineupAwayLabel'] as $side => $labelId): ?>
                                        <div class="lineup-card" data-lineup-side="<?= $side ?>">
                                                  <div class="lineup-card-header">
                                                            <div>
                                                                      <div class="text-xs text-muted-alt"><?= ucfirst($side) ?></div>
                                                                      <div id="<?= $labelId ?>" class="lineup-card-title text-light"><?= ucfirst($side) ?> lineup</div>
                                                            <div class="lineup-card-caption">
                                                                      Starting lineup · <span data-lineup-formation-name><?= htmlspecialchars($formationLabels[$side] ?? 'Unset') ?></span>
                                                            </div>
                                                            </div>
                                                            <div class="lineup-formation-selector text-end">
                                                                      <label class="form-label text-xs text-muted-alt mb-1" for="lineupFormationSelect<?= ucfirst($side) ?>">Formation</label>
                                                                      <select id="lineupFormationSelect<?= ucfirst($side) ?>" class="form-select form-select-sm select-dark" data-lineup-formation-select data-lineup-side="<?= $side ?>" data-formation-select="<?= $side ?>" aria-label="<?= ucfirst($side) ?> formation"></select>
                                                            </div>
                                                  </div>
                                                  <div class="lineup-formation formation-pitch">
                                                            <div class="formation-pitch">
                                                                      <svg width="298" height="386" viewBox="0 0 298 386" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                                <path fill-rule="evenodd" clip-rule="evenodd" d="M1.00024 373C1.00024 379.627 6.37283 385 13.0002 385L285 385C291.628 385 297 379.627 297 373L297 13C297 6.37259 291.628 1 285 1L13.0002 1C6.37283 1 1.00024 6.37259 1.00024 13L1.00024 373Z" fill="none" stroke="var(--border-muted)" stroke-width="2"></path>
                                                                                <path fill-rule="evenodd" clip-rule="evenodd" d="M122.882 314.635C134.115 291.068 164.057 292.011 175.117 314.735L122.882 314.635Z" fill="none"></path>
                                                                                <path d="M122.882 314.635C134.115 291.068 164.057 292.011 175.117 314.735" stroke="var(--border-muted)" stroke-width="2"></path>
                                                                                <path d="M228 315C232.418 315 236 318.582 236 323L236 385L62 385L62 323C62 318.582 65.5817 315 70 315L228 315Z" fill="none" stroke="var(--border-muted)" stroke-width="2"></path>
                                                                                <line x1="297" y1="150.323" x2="1.00003" y2="150.323" stroke="var(--border-muted)" stroke-width="2"></line>
                                                                                <rect x="187.177" y="113.323" width="76.3529" height="76.3529" rx="38.1765" transform="rotate(90 187.177 113.323)" fill="none" stroke="var(--border-muted)" stroke-width="2"></rect>
                                                                      </svg>
                                                                      <div class="formation-pitch-content">
                                                                                <div class="formation">
                                                                                          <div class="formation-positions" data-lineup-formation-positions data-lineup-side="<?= $side ?>"></div>
                                                                                </div>
                                                                      </div>
                                                            </div>
                                                  </div>
                                                  <div class="lineup-forms" data-lineup-side="<?= $side ?>"></div>
                                                  <button
                                                            type="button"
                                                            class="lineup-make-sub-btn btn btn-primary-soft btn-sm w-100 mb-2"
                                                            data-lineup-make-sub="<?= $side ?>">
                                                            <i class="fa-solid fa-rotate"></i>
                                                            Make substitution
                                                  </button>
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
          </div>
</div>
