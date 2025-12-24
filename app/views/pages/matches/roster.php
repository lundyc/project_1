<?php
require_auth();
require_once __DIR__ . '/../../../lib/match_permissions.php';
require_once __DIR__ . '/../../../lib/match_player_repository.php';

$user = current_user();
$roles = $_SESSION['roles'] ?? [];
$canManage = can_manage_match_for_club($user, $roles, (int)$match['club_id']);
$base = base_path();

$clubPlayers = get_club_players((int)$match['club_id']);
$clubPlayerMap = [];
foreach ($clubPlayers as $cp) {
          $clubPlayerMap[(int)$cp['id']] = $cp['display_name'];
}

$matchPlayers = get_match_players((int)$match['id']);
$homePlayers = array_values(array_filter($matchPlayers, fn($p) => $p['team_side'] === 'home'));
$awayPlayers = array_values(array_filter($matchPlayers, fn($p) => $p['team_side'] === 'away'));

function roster_row_value(array $rows, int $index, string $key): string
{
          return isset($rows[$index][$key]) ? (string)$rows[$index][$key] : '';
}

$homeRowCount = max(count($homePlayers) + 4, 12);
$awayRowCount = max(count($awayPlayers) + 4, 12);

$success = $_SESSION['roster_success'] ?? null;
$error = $_SESSION['roster_error'] ?? null;
unset($_SESSION['roster_success'], $_SESSION['roster_error']);

$title = 'Match Roster';

ob_start();
?>
<div class="d-flex align-items-center justify-content-between mb-4">
          <div>
                    <h1 class="mb-1">Match Roster</h1>
                    <p class="text-muted-alt text-sm mb-0"><?= htmlspecialchars($match['home_team']) ?> vs <?= htmlspecialchars($match['away_team']) ?></p>
          </div>
          <a href="<?= htmlspecialchars($base) ?>/matches" class="btn btn-secondary-soft btn-sm">Back to matches</a>
</div>

<?php if ($error): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php elseif ($success): ?>
          <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if ($canManage): ?>
          <form method="post" action="<?= htmlspecialchars($base) ?>/api/matches/<?= (int)$match['id'] ?>/roster/save">
                    <input type="hidden" name="match_id" value="<?= (int)$match['id'] ?>">
                    <div class="row g-3">
                              <div class="col-lg-6">
                                        <div class="panel p-3 rounded-md h-100">
                                                  <div class="panel-body">
                                                            <h5 class="text-light mb-1">Home (<?= htmlspecialchars($match['home_team']) ?>)</h5>
                                                            <p class="text-muted-alt text-sm">Choose club players or type a name. Leave blank rows empty to skip.</p>
                                                            <div class="table-responsive">
                                                                      <table class="table table-dark table-sm align-middle mb-0">
                                                                                <thead>
                                                                                          <tr>
                                                                                                    <th scope="col">Player</th>
                                                                                                    <th scope="col">Display name</th>
                                                                                                    <th scope="col">#</th>
                                                                                                    <th scope="col">Pos</th>
                                                                                                    <th scope="col">Start</th>
                                                                                          </tr>
                                                                                </thead>
                                                                                <tbody>
                                                                                          <?php for ($i = 0; $i < $homeRowCount; $i++): ?>
                                                                                                    <?php
                                                                                                    $row = $homePlayers[$i] ?? null;
                                                                                                    $playerId = $row['player_id'] ?? null;
                                                                                                    $displayName = roster_row_value($homePlayers, $i, 'display_name');
                                                                                                    $shirt = roster_row_value($homePlayers, $i, 'shirt_number');
                                                                                                    $pos = roster_row_value($homePlayers, $i, 'position_label');
                                                                                                    $starting = $row ? (bool)$row['is_starting'] : false;
                                                                                                    ?>
                                                                                                    <tr>
                                                                                                              <td style="min-width:160px">
                                                                                                                        <select name="home_player_id[<?= $i ?>]" class="form-select form-select-sm select-dark">
                                                                                                                                  <option value="">Select player</option>
                                                                                                                                  <?php foreach ($clubPlayers as $p): ?>
                                                                                                                                            <option value="<?= (int)$p['id'] ?>" <?= (int)$p['id'] === (int)$playerId ? 'selected' : '' ?>>
                                                                                                                                                      <?= htmlspecialchars($p['display_name']) ?>
                                                                                                                                            </option>
                                                                                                                                  <?php endforeach; ?>
                                                                                                                        </select>
                                                                                                              </td>
                                                                                                              <td>
                                                                                                                        <input type="text" name="home_display_name[<?= $i ?>]" class="form-control form-control-sm input-dark" value="<?= htmlspecialchars($displayName) ?>" placeholder="Name" <?= $i >= $homeRowCount ? 'disabled' : '' ?>>
                                                                                                              </td>
                                                                                                              <td style="max-width:70px">
                                                                                                                        <input type="number" name="home_shirt_number[<?= $i ?>]" class="form-control form-control-sm input-dark" min="0" step="1" value="<?= htmlspecialchars($shirt) ?>">
                                                                                                              </td>
                                                                                                              <td style="max-width:100px">
                                                                                                                        <input type="text" name="home_position_label[<?= $i ?>]" class="form-control form-control-sm input-dark" value="<?= htmlspecialchars($pos) ?>" placeholder="Pos">
                                                                                                              </td>
                                                                                                              <td class="text-center" style="width:70px">
                                                                                                                        <input class="form-check-input" type="checkbox" name="home_is_starting[<?= $i ?>]" value="1" <?= $starting ? 'checked' : '' ?>>
                                                                                                              </td>
                                                                                                    </tr>
                                                                                          <?php endfor; ?>
                                                                                </tbody>
                                                                      </table>
                                                            </div>
                                                  </div>
                                        </div>
                              </div>

                              <div class="col-lg-6">
                                        <div class="panel p-3 rounded-md h-100">
                                                  <div class="panel-body">
                                                            <h5 class="text-light mb-1">Away (<?= htmlspecialchars($match['away_team']) ?>)</h5>
                                                            <p class="text-muted-alt text-sm">Enter opponent names or pick a known player.</p>
                                                            <div class="table-responsive">
                                                                      <table class="table table-dark table-sm align-middle mb-0">
                                                                                <thead>
                                                                                          <tr>
                                                                                                    <th scope="col">Player</th>
                                                                                                    <th scope="col">Display name</th>
                                                                                                    <th scope="col">#</th>
                                                                                                    <th scope="col">Pos</th>
                                                                                                    <th scope="col">Start</th>
                                                                                          </tr>
                                                                                </thead>
                                                                                <tbody>
                                                                                          <?php for ($i = 0; $i < $awayRowCount; $i++): ?>
                                                                                                    <?php
                                                                                                    $row = $awayPlayers[$i] ?? null;
                                                                                                    $playerId = $row['player_id'] ?? null;
                                                                                                    $displayName = roster_row_value($awayPlayers, $i, 'display_name');
                                                                                                    $shirt = roster_row_value($awayPlayers, $i, 'shirt_number');
                                                                                                    $pos = roster_row_value($awayPlayers, $i, 'position_label');
                                                                                                    $starting = $row ? (bool)$row['is_starting'] : false;
                                                                                                    ?>
                                                                                                    <tr>
                                                                                                              <td style="min-width:160px">
                                                                                                                        <select name="away_player_id[<?= $i ?>]" class="form-select form-select-sm select-dark">
                                                                                                                                  <option value="">Select player</option>
                                                                                                                                  <?php foreach ($clubPlayers as $p): ?>
                                                                                                                                            <option value="<?= (int)$p['id'] ?>" <?= (int)$p['id'] === (int)$playerId ? 'selected' : '' ?>>
                                                                                                                                                      <?= htmlspecialchars($p['display_name']) ?>
                                                                                                                                            </option>
                                                                                                                                  <?php endforeach; ?>
                                                                                                                        </select>
                                                                                                              </td>
                                                                                                              <td>
                                                                                                                        <input type="text" name="away_display_name[<?= $i ?>]" class="form-control form-control-sm input-dark" value="<?= htmlspecialchars($displayName) ?>" placeholder="Name">
                                                                                                              </td>
                                                                                                              <td style="max-width:70px">
                                                                                                                        <input type="number" name="away_shirt_number[<?= $i ?>]" class="form-control form-control-sm input-dark" min="0" step="1" value="<?= htmlspecialchars($shirt) ?>">
                                                                                                              </td>
                                                                                                              <td style="max-width:100px">
                                                                                                                        <input type="text" name="away_position_label[<?= $i ?>]" class="form-control form-control-sm input-dark" value="<?= htmlspecialchars($pos) ?>" placeholder="Pos">
                                                                                                              </td>
                                                                                                              <td class="text-center" style="width:70px">
                                                                                                                        <input class="form-check-input" type="checkbox" name="away_is_starting[<?= $i ?>]" value="1" <?= $starting ? 'checked' : '' ?>>
                                                                                                              </td>
                                                                                                    </tr>
                                                                                          <?php endfor; ?>
                                                                                </tbody>
                                                                      </table>
                                                            </div>
                                                  </div>
                                        </div>
                              </div>
                    </div>

                    <div class="mt-3">
                              <button class="btn btn-primary-soft">Save roster</button>
                    </div>
          </form>
<?php else: ?>
          <div class="panel p-3 rounded-md">
                    <div class="panel-body">
                              <h5 class="text-light mb-2">Roster (read-only)</h5>
                              <div class="row g-3">
                                        <div class="col-lg-6">
                                                  <h6 class="text-muted-alt text-sm">Home</h6>
                                                  <ul class="list-group list-group-flush">
                                                            <?php if (empty($homePlayers)): ?>
                                                                      <li class="list-group-item bg-black text-muted">No home players listed.</li>
                                                            <?php else: ?>
                                                                     <?php foreach ($homePlayers as $p): ?>
                                                                               <li class="list-group-item bg-black text-light d-flex justify-content-between">
                                                                                         <span><?= htmlspecialchars($p['display_name']) ?></span>
                                                                                         <span class="text-muted small"><?= htmlspecialchars($p['position_label'] ?? '') ?></span>
                                                                               </li>
                                                                     <?php endforeach; ?>
                                                            <?php endif; ?>
                                                  </ul>
                                        </div>
                                        <div class="col-lg-6">
                                                  <h6 class="text-muted-alt text-sm">Away</h6>
                                                  <ul class="list-group list-group-flush">
                                                            <?php if (empty($awayPlayers)): ?>
                                                                      <li class="list-group-item bg-black text-muted">No away players listed.</li>
                                                            <?php else: ?>
                                                                      <?php foreach ($awayPlayers as $p): ?>
                                                                                <li class="list-group-item bg-black text-light d-flex justify-content-between">
                                                                                          <span><?= htmlspecialchars($p['display_name']) ?></span>
                                                                                          <span class="text-muted small"><?= htmlspecialchars($p['position_label'] ?? '') ?></span>
                                                                                </li>
                                                                      <?php endforeach; ?>
                                                            <?php endif; ?>
                                                  </ul>
                                        </div>
                              </div>
                    </div>
          </div>
<?php endif; ?>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../layout.php';
