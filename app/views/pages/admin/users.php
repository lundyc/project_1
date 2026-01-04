<?php
require_role('platform_admin');
require_once dirname(__DIR__, 3) . '/lib/admin_user_repository.php';
require_once dirname(__DIR__, 3) . '/lib/club_repository.php';

$base = base_path();
$roles = get_roles();
$clubs = get_all_clubs();
$users = get_all_users();
$userCount = count($users);

$error = $_SESSION['user_form_error'] ?? null;
$success = $_SESSION['user_form_success'] ?? null;
unset($_SESSION['user_form_error'], $_SESSION['user_form_success']);

$title = 'Manage Users';

ob_start();
?>
<div class="d-flex align-items-start justify-content-between mb-4 gap-3">
          <div>
                    <h1 class="mb-1">Users</h1>
                    <p class="text-muted-alt text-sm mb-0">Create users, assign clubs, and grant roles.</p>
          </div>
          <div class="d-flex gap-2 flex-wrap">
                    <button type="button" class="btn btn-secondary-soft btn-sm">List view</button>
                    <button type="button" class="btn btn-secondary-soft btn-sm">Segment</button>
                    <button type="button" class="btn btn-primary-soft btn-sm">+ Add user</button>
          </div>
</div>

<?php if ($error): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php elseif ($success): ?>
          <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="row g-4 align-items-start">
          <div class="col-lg-4">
                    <section class="panel filters-panel p-4 h-100 d-flex flex-column">
                              <div class="d-flex align-items-center justify-content-between mb-3">
                                        <h5 class="mb-0 text-light">Filters</h5>
                                        <button type="button" class="btn btn-flat btn-sm">Reset</button>
                              </div>
                              <form method="get" class="flex-fill d-flex flex-column gap-3">
                                        <div class="filter-group">
                                                  <label class="form-label">Tags</label>
                                                  <input type="text" placeholder="VIP, Finance, Manager" class="form-control input-dark">
                                        </div>
                                        <div class="filter-group">
                                                  <label class="form-label">Email</label>
                                                  <input type="text" placeholder="Contains" class="form-control input-dark">
                                        </div>
                                        <div class="filter-group">
                                                  <label class="form-label">First name</label>
                                                  <input type="text" placeholder="Starts with" class="form-control input-dark">
                                        </div>
                                        <div class="filter-group">
                                                  <label class="form-label">Last name</label>
                                                  <input type="text" placeholder="Starts with" class="form-control input-dark">
                                        </div>
                                        <div class="filter-group">
                                                  <label class="form-label">Creation date</label>
                                                  <div class="row g-2">
                                                            <div class="col">
                                                                      <input type="date" class="form-control input-dark">
                                                            </div>
                                                            <div class="col">
                                                                      <input type="date" class="form-control input-dark">
                                                            </div>
                                                  </div>
                                        </div>
                                        <div class="filter-group">
                                                  <label class="form-label">Client ID</label>
                                                  <input type="text" placeholder="Exact match" class="form-control input-dark">
                                        </div>
                                        <div class="filter-group">
                                                  <label class="form-label">Verification</label>
                                                  <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" id="verified_only">
                                                            <label class="form-check-label" for="verified_only">Verified</label>
                                                  </div>
                                                  <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" id="unverified_only">
                                                            <label class="form-check-label" for="unverified_only">Not verified</label>
                                                  </div>
                                        </div>
                                        <div class="filter-actions mt-auto d-flex gap-2">
                                                  <button type="submit" class="btn btn-primary-soft flex-fill">Apply filters</button>
                                                  <button type="reset" class="btn btn-secondary-soft flex-fill">Clear</button>
                                        </div>
                              </form>
                    </section>
          </div>

          <div class="col-lg-8">
                    <section class="panel p-4 d-flex flex-column gap-4 users-panel">
                              <div class="manager-list-header">
                                        <div>
                                                  <h5 class="mb-1 text-light">Clients</h5>
                                                  <p class="text-muted-alt text-sm mb-0">Overview of every teammate and club connection.</p>
                                        </div>
                                        <div class="d-flex gap-2 flex-wrap">
                                                  <button type="button" class="btn btn-secondary-soft btn-sm">List view</button>
                                                  <button type="button" class="btn btn-secondary-soft btn-sm">Segments</button>
                                        </div>
                              </div>

                              <div class="admin-card p-3">
                                        <h6 class="text-muted-alt mb-3">Create a new user</h6>
                                        <form method="post" action="<?= htmlspecialchars($base) ?>/api/admin/users/create" class="row g-3">
                                                  <div class="col-md-6">
                                                            <label class="form-label">Display name</label>
                                                            <input type="text" name="display_name" class="form-control input-dark" required>
                                                  </div>
                                                  <div class="col-md-6">
                                                            <label class="form-label">Email</label>
                                                            <input type="email" name="email" class="form-control input-dark" required>
                                                  </div>
                                                  <div class="col-md-6">
                                                            <label class="form-label">Password</label>
                                                            <input type="password" name="password" class="form-control input-dark" required>
                                                  </div>
                                                  <div class="col-md-6">
                                                            <label class="form-label">Club (optional)</label>
                                                            <select name="club_id" class="form-select select-dark">
                                                                      <option value="">Unassigned</option>
                                                                      <?php foreach ($clubs as $club): ?>
                                                                                <option value="<?= (int)$club['id'] ?>"><?= htmlspecialchars($club['name']) ?></option>
                                                                      <?php endforeach; ?>
                                                            </select>
                                                  </div>
                                                  <div class="col-12">
                                                            <label class="form-label d-block pb-1">Roles</label>
                                                            <?php if (empty($roles)): ?>
                                                                      <p class="text-muted-alt text-sm mb-0">No roles available.</p>
                                                            <?php else: ?>
                                                                      <div class="d-flex flex-wrap gap-3">
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
                                                  <div class="col-12 d-flex gap-2">
                                                            <button class="btn btn-primary-soft flex-fill" <?= empty($roles) ? 'disabled' : '' ?>>Create user</button>
                                                            <button type="reset" class="btn btn-secondary-soft flex-fill">Reset</button>
                                                  </div>
                                        </form>
                              </div>

                              <div class="table-responsive manager-table">
                                        <table class="table table-borderless align-middle mb-0">
                                                  <thead>
                                                            <tr>
                                                                      <th>Name</th>
                                                                      <th>Club</th>
                                                                      <th>Roles</th>
                                                                      <th>ID</th>
                                                                      <th class="text-end"></th>
                                                            </tr>
                                                  </thead>
                                                  <tbody>
                                                            <?php if ($userCount === 0): ?>
                                                                      <tr>
                                                                                <td colspan="5" class="text-muted-alt">No users found.</td>
                                                                      </tr>
                                                            <?php else: ?>
                                                                      <?php foreach ($users as $user): ?>
                                                                                <?php
                                                                                $displayName = $user['display_name'] ?? 'User';
                                                                                $email = $user['email'] ?? '';
                                                                                $initialSource = $displayName !== '' ? $displayName : $email;
                                                                                $initialChar = '';
                                                                                if ($initialSource !== '') {
                                                                                          $initialChar = function_exists('mb_substr')
                                                                                                    ? mb_substr($initialSource, 0, 1)
                                                                                                    : substr($initialSource, 0, 1);
                                                                                }
                                                                                $initial = strtoupper($initialChar);
                                                                                if ($initial === '') {
                                                                                          $initial = 'U';
                                                                                }
                                                                                ?>
                                                                                <tr>
                                                                                          <td>
                                                                                                    <div class="d-flex align-items-center gap-3">
                                                                                                              <span class="avatar-badge text-uppercase"><?= htmlspecialchars($initial) ?></span>
                                                                                                              <div>
                                                                                                                        <div><?= htmlspecialchars($displayName) ?></div>
                                                                                                                        <div class="text-muted-alt text-sm"><?= htmlspecialchars($email) ?></div>
                                                                                                              </div>
                                                                                                    </div>
                                                                                          </td>
                                                                                          <td><?= htmlspecialchars($user['club_name'] ?? 'Unassigned') ?></td>
                                                                                          <td>
                                                                                                    <?php if (empty($user['roles'])): ?>
                                                                                                              <span class="text-muted-alt text-sm">No roles yet</span>
                                                                                                    <?php else: ?>
                                                                                                              <div class="d-flex flex-wrap gap-2">
                                                                                                                        <?php foreach ($user['roles'] as $roleKey): ?>
                                                                                                                                  <span class="tag-pill"><?= htmlspecialchars($roleKey) ?></span>
                                                                                                                        <?php endforeach; ?>
                                                                                                              </div>
                                                                                                    <?php endif; ?>
                                                                                          </td>
                                                                                          <td class="text-muted-alt text-sm">#<?= (int)$user['id'] ?></td>
                                                                                          <td class="text-end">
                                                                                                    <button type="button" class="btn btn-icon btn-icon-secondary">
                                                                                                              <i class="fa-solid fa-chevron-right"></i>
                                                                                                    </button>
                                                                                          </td>
                                                                                </tr>
                                                                      <?php endforeach; ?>
                                                            <?php endif; ?>
                                                  </tbody>
                                        </table>
                              </div>

                              <div class="manager-footer d-flex flex-wrap align-items-center justify-content-between text-muted-alt text-sm gap-3">
                                        <span>Rows per page <strong><?= $userCount ?></strong> &sdot; out of <?= $userCount ?></span>
                                        <div class="pagination-chips">
                                                  <button type="button" class="pagination-chip active">1</button>
                                                  <button type="button" class="pagination-chip">2</button>
                                                  <button type="button" class="pagination-chip">3</button>
                                                  <span class="text-muted-alt">…</span>
                                                  <button type="button" class="pagination-chip">15</button>
                                        </div>
                              </div>
                    </section>
          </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../layout.php';
