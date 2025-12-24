<?php
require_role('platform_admin');
require_once dirname(__DIR__, 3) . '/lib/club_repository.php';

$base = base_path();
$clubs = get_all_clubs();

$error = $_SESSION['club_form_error'] ?? null;
$success = $_SESSION['club_form_success'] ?? null;
unset($_SESSION['club_form_error'], $_SESSION['club_form_success']);

$title = 'Manage Clubs';

ob_start();
?>
<div class="d-flex align-items-center justify-content-between mb-4">
          <div>
                    <h1 class="mb-1">Clubs</h1>
                    <p class="text-muted-alt text-sm mb-0">Create clubs for analysts to use.</p>
          </div>
</div>

<?php if ($error): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php elseif ($success): ?>
          <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="row g-4">
          <div class="col-md-4">
                    <div class="panel p-3 rounded-md">
                              <div class="panel-body">
                                        <h5 class="text-light mb-3">Create Club</h5>
                                        <form method="post" action="<?= htmlspecialchars($base) ?>/api/admin/clubs/create" class="d-flex flex-column gap-3">
                                                  <div>
                                                            <label class="form-label text-light">Club name</label>
                                                            <input type="text" name="name" class="form-control input-dark" required>
                                                  </div>
                                                  <button class="btn btn-primary-soft w-100">Create</button>
                                        </form>
                              </div>
                    </div>
          </div>

          <div class="col-md-8">
                    <div class="panel p-3 rounded-md">
                              <div class="panel-body">
                                        <h5 class="text-light mb-3">All Clubs</h5>
                                        <div class="table-responsive">
                                                  <table class="table table-dark table-sm align-middle mb-0">
                                                            <thead>
                                                                      <tr>
                                                                                <th scope="col">Name</th>
                                                                      </tr>
                                                            </thead>
                                                            <tbody>
                                                                      <?php if (empty($clubs)): ?>
                                                                                <tr>
                                                                                          <td class="text-muted">No clubs have been created yet.</td>
                                                                                </tr>
                                                                      <?php else: ?>
                                                                                <?php foreach ($clubs as $club): ?>
                                                                                          <tr>
                                                                                                    <td><?= htmlspecialchars($club['name']) ?></td>
                                                                                          </tr>
                                                                                <?php endforeach; ?>
                                                                      <?php endif; ?>
                                                            </tbody>
                                                  </table>
                                        </div>
                              </div>
                    </div>
          </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../layout.php';
