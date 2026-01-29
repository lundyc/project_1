<?php
require_role('platform_admin');
require_once dirname(__DIR__, 4) . '/lib/club_repository.php';
require_once dirname(__DIR__, 4) . '/lib/team_repository.php';

$teamId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($teamId <= 0) {
          http_response_code(404);
          echo '404 Not Found';
          exit;
}

$team = get_team_by_id($teamId);
if (!$team) {
          http_response_code(404);
          echo '404 Not Found';
          exit;
}

$clubs = get_all_clubs();

$flashError = $_SESSION['team_form_error'] ?? null;
$flashSuccess = $_SESSION['team_form_success'] ?? null;
$formInput = $_SESSION['team_form_input'] ?? [];

unset($_SESSION['team_form_error'], $_SESSION['team_form_success'], $_SESSION['team_form_input']);

$title = 'Edit Team';
$base = base_path();

$values = [
          'name' => $formInput['name'] ?? $team['name'] ?? '',
          'club_id' => $formInput['club_id'] ?? $team['club_id'] ?? '',
          'team_type' => $formInput['team_type'] ?? $team['team_type'] ?? 'club',
];

$createdAt = $team['created_at'] ?? '';
$updatedAt = $team['updated_at'] ?? '';

ob_start();
?>
<div class="flex items-center justify-between mb-6">
          <div>
                    <h1 class="mb-1">Edit Team</h1>
                    <p class="text-muted-alt text-sm mb-0">Update team details and assignments.</p>
          </div>
        <a href="<?= htmlspecialchars($base) ?>/admin/teams" class="inline-flex items-center gap-2 bg-bg-secondary text-text-primary border border-border-soft px-4 py-2 rounded-md hover:bg-bg-secondary/80 transition">Back to teams</a>
</div>

<?php if ($flashError): ?>
          <div class="alert alert-danger mb-4"><?= htmlspecialchars($flashError) ?></div>
<?php elseif ($flashSuccess): ?>
          <div class="alert alert-success mb-4"><?= htmlspecialchars($flashSuccess) ?></div>
<?php endif; ?>

<div class="panel p-4">
          <form method="post" action="<?= htmlspecialchars($base) ?>/api/admin/teams/update">
                    <input type="hidden" name="id" value="<?= $teamId ?>">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                              <div>
                                        <label class="form-label">Team name</label>
                                        <input type="text" name="name" value="<?= htmlspecialchars($values['name']) ?>" class="input-dark w-full" required>
                              </div>
                              <div>
                                        <label class="form-label">Club</label>
                                        <select name="club_id" class="select-dark w-full" required>
                                                  <option value="">Select club</option>
                                                  <?php foreach ($clubs as $club): ?>
                                                            <option value="<?= (int)$club['id'] ?>" <?= (int)$values['club_id'] === (int)$club['id'] ? 'selected' : '' ?>><?= htmlspecialchars($club['name']) ?></option>
                                                  <?php endforeach; ?>
                                        </select>
                              </div>
                              <div>
                                        <label class="form-label">Team type</label>
                                        <select name="team_type" class="select-dark w-full" required>
                                                  <option value="club" <?= $values['team_type'] === 'club' ? 'selected' : '' ?>>club</option>
                                                  <option value="opponent" <?= $values['team_type'] === 'opponent' ? 'selected' : '' ?>>opponent</option>
                                        </select>
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
                            <button type="submit" class="inline-flex items-center gap-2 bg-accent-primary text-white px-4 py-2 rounded-md hover:bg-accent-primary/80 transition">Update team</button>
                    </div>
          </form>

          <hr class="border-secondary my-4">

          <form method="post" action="<?= htmlspecialchars($base) ?>/api/admin/teams/delete" onsubmit="return confirm('Delete this team? This will fail if it has matches or related data.');">
                    <input type="hidden" name="id" value="<?= $teamId ?>">
                    <button type="submit" class="inline-flex items-center gap-2 bg-accent-danger text-white px-4 py-2 rounded-md hover:bg-accent-danger/80 transition">Delete team</button>
          </form>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../../layout.php';
