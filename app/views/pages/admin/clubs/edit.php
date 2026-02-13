<?php
require_role('platform_admin');
require_once dirname(__DIR__, 4) . '/lib/club_repository.php';
require_once dirname(__DIR__, 4) . '/lib/team_repository.php';

$clubId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($clubId <= 0) {
          http_response_code(404);
          echo '404 Not Found';
          exit;
}

if ($clubId === 3) {
          http_response_code(403);
          echo '403 Forbidden';
          exit;
}

$club = get_club_by_id($clubId);
if (!$club) {
          http_response_code(404);
          echo '404 Not Found';
          exit;
}

$assignedTeams = get_teams_by_club($clubId);
$availableTeams = get_teams_not_in_club($clubId);

$flashError = $_SESSION['club_form_error'] ?? null;
$flashSuccess = $_SESSION['club_form_success'] ?? null;
$teamError = $_SESSION['club_team_error'] ?? null;
$teamSuccess = $_SESSION['club_team_success'] ?? null;

unset(
          $_SESSION['club_form_error'],
          $_SESSION['club_form_success'],
          $_SESSION['club_team_error'],
          $_SESSION['club_team_success']
);

$title = 'Edit Club';
$base = base_path();

$createdAt = $club['created_at'] ?? '';
$updatedAt = $club['updated_at'] ?? '';

ob_start();
?>
<div class="flex items-center justify-between mb-6">
          <div>
                    <h1 class="mb-1">Edit Club</h1>
                    <p class="text-muted-alt text-sm mb-0">Update club details and manage team assignments.</p>
          </div>
          <?php
          $label = 'Back to clubs';
          $href = $base . '/admin/clubs';
          $variant = 'secondary';
          $size = 'sm';
          include __DIR__ . '/../../../partials/ui-button.php';
          ?>
</div>

<?php if ($flashError): ?>
          <div class="alert alert-danger mb-4"><?= htmlspecialchars($flashError) ?></div>
<?php elseif ($flashSuccess): ?>
          <div class="alert alert-success mb-4"><?= htmlspecialchars($flashSuccess) ?></div>
<?php endif; ?>

<?php if ($teamError): ?>
          <div class="alert alert-danger mb-4"><?= htmlspecialchars($teamError) ?></div>
<?php elseif ($teamSuccess): ?>
          <div class="alert alert-success mb-4"><?= htmlspecialchars($teamSuccess) ?></div>
<?php endif; ?>

<div class="grid grid-cols-12 gap-4">
          <div class="col-span-8 space-y-4">
                    <div class="panel p-4">
                              <h5 class="text-light mb-3">Club details</h5>
                              <form method="post" action="<?= htmlspecialchars($base) ?>/api/admin/clubs/update">
                                        <input type="hidden" name="id" value="<?= $clubId ?>">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                  <div>
                                                            <label class="form-label">Club name</label>
                                                            <input type="text" name="name" value="<?= htmlspecialchars($club['name'] ?? '') ?>" class="input-dark w-full" required>
                                                  </div>
                                                  <div>
                                                            <label class="form-label">Created</label>
                                                            <div class="text-muted-alt text-sm pt-2">
                                                                      <?= $createdAt ? htmlspecialchars(date('Y-m-d H:i', strtotime($createdAt))) : 'N/A' ?>
                                                            </div>
                                                  </div>
                                                  <div>
                                                            <label class="form-label">Updated</label>
                                                            <div class="text-muted-alt text-sm pt-2">
                                                                      <?= $updatedAt ? htmlspecialchars(date('Y-m-d H:i', strtotime($updatedAt))) : 'N/A' ?>
                                                            </div>
                                                  </div>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mt-4">
                                                  <span class="text-muted-alt text-sm">Changes apply immediately.</span>
                                                  <?php
                                                  $label = 'Update club';
                                                  $href = null;
                                                  $variant = 'primary';
                                                  $size = 'sm';
                                                  $type = 'submit';
                                                  include __DIR__ . '/../../../partials/ui-button.php';
                                                  ?>
                                        </div>
                              </form>
                    </div>

                    <div class="panel p-4">
                              <h5 class="text-light mb-3">Assigned teams</h5>
                              <?php if (empty($assignedTeams)): ?>
                                        <div class="text-muted-alt text-sm">No teams assigned to this club yet.</div>
                              <?php else: ?>
                                        <div class="table-responsive">
                                                  <table class="table table-sm table-dark align-middle mb-0">
                                                            <thead>
                                                                      <tr>
                                                                                <th>Name</th>
                                                                                <th>Type</th>
                                                                                <th class="text-end">Actions</th>
                                                                      </tr>
                                                            </thead>
                                                            <tbody>
                                                                      <?php foreach ($assignedTeams as $team): ?>
                                                                                <tr>
                                                                                          <td><?= htmlspecialchars($team['name'] ?? 'Team') ?></td>
                                                                                          <td class="text-muted-alt text-sm"><?= htmlspecialchars($team['team_type'] ?? 'club') ?></td>
                                                                                          <td class="text-end">
                                                                                                    <div class="d-flex justify-content-end gap-2">
                                                                                                              <a href="<?= htmlspecialchars($base) ?>/admin/teams/<?= (int)$team['id'] ?>/edit" class="btn btn-secondary-soft btn-sm">Edit team</a>
                                                                                                              <form method="post" action="<?= htmlspecialchars($base) ?>/api/admin/clubs/remove-team" class="d-inline" onsubmit="return confirm('Remove this team from the club?');">
                                                                                                                        <input type="hidden" name="club_id" value="<?= $clubId ?>">
                                                                                                                        <input type="hidden" name="team_id" value="<?= (int)$team['id'] ?>">
                                                                                                                        <button type="submit" class="btn btn-danger-soft btn-sm">Remove</button>
                                                                                                              </form>
                                                                                                    </div>
                                                                                          </td>
                                                                                </tr>
                                                                      <?php endforeach; ?>
                                                            </tbody>
                                                  </table>
                                        </div>
                                        <p class="text-muted-alt text-sm mt-2">Removing a team moves it to the Opponents placeholder club.</p>
                              <?php endif; ?>
                    </div>

                    <div class="panel p-4">
                              <h5 class="text-light mb-3">Assign team</h5>
                              <?php if (empty($availableTeams)): ?>
                                        <div class="text-muted-alt text-sm">No teams available to assign.</div>
                              <?php else: ?>
                                        <form method="post" action="<?= htmlspecialchars($base) ?>/api/admin/clubs/assign-team" class="row g-3 align-items-end">
                                                  <input type="hidden" name="club_id" value="<?= $clubId ?>">
                                                  <div class="col-md-8">
                                                            <label class="form-label">Select team</label>
                                                            <select name="team_id" class="form-select select-dark" required>
                                                                      <option value="">Choose a team</option>
                                                                      <?php foreach ($availableTeams as $team): ?>
                                                                                <?php
                                                                                $teamLabel = $team['name'] ?? 'Team';
                                                                                $teamClub = $team['club_name'] ?? 'Unknown club';
                                                                                $teamType = $team['team_type'] ?? 'club';
                                                                                ?>
                                                                                <option value="<?= (int)$team['id'] ?>">
                                                                                          <?= htmlspecialchars($teamLabel) ?> · <?= htmlspecialchars($teamClub) ?> · <?= htmlspecialchars($teamType) ?>
                                                                                </option>
                                                                      <?php endforeach; ?>
                                                            </select>
                                                  </div>
                                                  <div class="col-md-4">
                                                            <?php
                                                            $label = 'Assign to club';
                                                            $href = null;
                                                            $variant = 'primary';
                                                            $size = 'sm';
                                                            $type = 'submit';
                                                            $class = 'w-100';
                                                            include __DIR__ . '/../../../partials/ui-button.php';
                                                            ?>
                                                  </div>
                                        </form>
                                        <p class="text-muted-alt text-sm mt-2">Assigning a team moves it from its current club to this club.</p>
                              <?php endif; ?>
                    </div>
          </div>

          <aside class="col-span-4 min-w-0">
                    <div class="rounded-xl bg-slate-900/80 border border-white/10 p-4">
                              <h5 class="text-slate-200 font-semibold mb-1">Club Snapshot</h5>
                              <div class="text-slate-400 text-xs mb-4">Quick stats</div>
                              <div class="space-y-3">
                                        <article class="rounded-lg border border-white/10 bg-slate-800/40 px-3 py-3">
                                                  <div class="text-xs font-semibold text-slate-300 mb-2 text-center">Teams Assigned</div>
                                                  <div class="text-2xl font-bold text-slate-100 text-center"><?= count($assignedTeams) ?></div>
                                        </article>
                                        <article class="rounded-lg border border-white/10 bg-slate-800/40 px-3 py-3">
                                                  <div class="text-xs font-semibold text-slate-300 mb-2 text-center">Available Teams</div>
                                                  <div class="text-2xl font-bold text-slate-100 text-center"><?= count($availableTeams) ?></div>
                                        </article>
                              </div>
                    </div>
          </aside>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../../layout.php';
