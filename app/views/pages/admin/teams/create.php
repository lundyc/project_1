<?php
require_role('platform_admin');
require_once dirname(__DIR__, 4) . '/lib/club_repository.php';

$base = base_path();
$clubs = get_all_clubs();

$flashError = $_SESSION['team_form_error'] ?? null;
$formInput = $_SESSION['team_form_input'] ?? [];
unset($_SESSION['team_form_error'], $_SESSION['team_form_input']);

$title = 'Create Team';

ob_start();
?>
<div class="flex items-center justify-between mb-6">
          <div>
                    <h1 class="mb-1">Create Team</h1>
                    <p class="text-muted-alt text-sm mb-0">Add a new team and assign it to a club.</p>
          </div>
          <a href="<?= htmlspecialchars($base) ?>/admin/teams" class="btn btn-secondary-soft btn-sm">Back to teams</a>
</div>

<?php if ($flashError): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($flashError) ?></div>
<?php endif; ?>

<div class="panel p-4">
          <form method="post" action="<?= htmlspecialchars($base) ?>/api/admin/teams/create">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                              <div>
                                        <label class="form-label">Team name</label>
                                        <input type="text" name="name" value="<?= htmlspecialchars($formInput['name'] ?? '') ?>" class="input-dark w-full" required>
                              </div>
                              <div>
                                        <label class="form-label">Club</label>
                                        <select name="club_id" class="select-dark w-full" required>
                                                  <option value="">Select club</option>
                                                  <?php foreach ($clubs as $club): ?>
                                                            <option value="<?= (int)$club['id'] ?>" <?= isset($formInput['club_id']) && (int)$formInput['club_id'] === (int)$club['id'] ? 'selected' : '' ?>><?= htmlspecialchars($club['name']) ?></option>
                                                  <?php endforeach; ?>
                                        </select>
                              </div>
                              <div>
                                        <label class="form-label">Team type</label>
                                        <select name="team_type" class="select-dark w-full" required>
                                                  <?php
                                                  $selectedType = $formInput['team_type'] ?? 'club';
                                                  ?>
                                                  <option value="club" <?= $selectedType === 'club' ? 'selected' : '' ?>>club</option>
                                                  <option value="opponent" <?= $selectedType === 'opponent' ? 'selected' : '' ?>>opponent</option>
                                        </select>
                              </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4">
                              <span class="text-muted-alt text-sm">Team type affects match grouping and filters.</span>
                              <button type="submit" class="btn btn-primary-soft btn-sm">Create team</button>
                    </div>
          </form>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../../layout.php';
