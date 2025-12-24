<?php
require_role('platform_admin');
require_once dirname(__DIR__, 3) . '/lib/admin_user_repository.php';
require_once dirname(__DIR__, 3) . '/lib/club_repository.php';

$base = base_path();
$roles = get_roles();
$clubs = get_all_clubs();
$users = get_all_users();

$error = $_SESSION['user_form_error'] ?? null;
$success = $_SESSION['user_form_success'] ?? null;
unset($_SESSION['user_form_error'], $_SESSION['user_form_success']);

$title = 'Manage Users';

ob_start();
?>
<div class="d-flex align-items-center justify-content-between mb-4">
          <div>
                    <h1 class="mb-1">Users</h1>
                    <p class="text-muted-alt text-sm mb-0">Create users, assign clubs, and grant roles.</p>
          </div>
</div>

<?php if ($error): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php elseif ($success): ?>
          <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="row g-4">
          <div class="col-lg-5">
                    <div class="panel p-3 rounded-md">
                              <div class="panel-body">
                                        <h5 class="text-light mb-3">Create User</h5>
                                        <form method="post" action="<?= htmlspecialchars($base) ?>/api/admin/users/create">
                                                  <div class="mb-3">
                                                            <label class="form-label text-light">Display name</label>
                                                            <input type="text" name="display_name" class="form-control input-dark" required>
                                                  </div>
                                                  <div class="mb-3">
                                                            <label class="form-label text-light">Email</label>
                                                            <input type="email" name="email" class="form-control input-dark" required>
                                                  </div>
                                                  <div class="mb-3">
                                                            <label class="form-label text-light">Password</label>
                                                            <input type="password" name="password" class="form-control input-dark" required>
                                                  </div>
                                                  <div class="mb-3">
                                                            <label class="form-label text-light">Club (optional)</label>
                                                            <select name="club_id" class="form-select select-dark">
                                                                      <option value="">Unassigned</option>
                                                                      <?php foreach ($clubs as $club): ?>
                                                                                <option value="<?= (int)$club['id'] ?>"><?= htmlspecialchars($club['name']) ?></option>
                                                                      <?php endforeach; ?>
                                                            </select>
                                                  </div>
                                                  <div class="mb-3">
                                                            <label class="form-label text-light d-block">Roles</label>
                                                            <?php if (empty($roles)): ?>
                                                                      <p class="text-muted-alt text-sm mb-0">No roles available.</p>
                                                            <?php else: ?>
                                                                      <div class="d-flex flex-wrap gap-2">
                                                                                <?php foreach ($roles as $role): ?>
                                                                                          <div class="form-check">
                                                                                                    <input class="form-check-input" type="checkbox" name="role_ids[]" value="<?= (int)$role['id'] ?>" id="role_<?= (int)$role['id'] ?>">
                                                                                                    <label class="form-check-label" for="role_<?= (int)$role['id'] ?>">
                                                                                                              <?= htmlspecialchars($role['role_key']) ?>
                                                                                                    </label>
                                                                                          </div>
                                                                                <?php endforeach; ?>
                                                                      </div>
                                                            <?php endif; ?>
                                                  </div>
                                                  <button class="btn btn-primary-soft w-100" <?= empty($roles) ? 'disabled' : '' ?>>Create</button>
                                        </form>
                              </div>
                    </div>
          </div>
          <div class="col-lg-7">
                    <div class="panel p-3 rounded-md">
                              <div class="panel-body">
                                        <h5 class="text-light mb-3">All Users</h5>
                                        <div class="table-responsive">
                                                  <table class="table table-dark table-sm align-middle mb-0">
                                                            <thead>
                                                                      <tr>
                                                                                <th scope="col">Name</th>
                                                                                <th scope="col">Email</th>
                                                                                <th scope="col">Club</th>
                                                                                <th scope="col">Roles</th>
                                                                      </tr>
                                                            </thead>
                                                            <tbody>
                                                                      <?php if (empty($users)): ?>
                                                                                <tr>
                                                                                          <td colspan="4" class="text-muted">No users found.</td>
                                                                                </tr>
                                                                      <?php else: ?>
                                                                                <?php foreach ($users as $user): ?>
                                                                                          <tr>
                                                                                                    <td><?= htmlspecialchars($user['display_name']) ?></td>
                                                                                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                                                                                    <td><?= htmlspecialchars($user['club_name'] ?? 'Unassigned') ?></td>
                                                                                                    <td>
                                                                                                              <?php if (empty($user['roles'])): ?>
                                                                                                                        <span class="text-muted">None</span>
                                                                                                              <?php else: ?>
                                                                                                                        <div class="d-flex flex-wrap gap-1">
                                                                                                                                  <?php foreach ($user['roles'] as $roleKey): ?>
                                                                                                                                            <span class="badge bg-secondary text-uppercase"><?= htmlspecialchars($roleKey) ?></span>
                                                                                                                                  <?php endforeach; ?>
                                                                                                                        </div>
                                                                                                              <?php endif; ?>
                                                                                                    </td>
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
