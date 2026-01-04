<?php
require_role('platform_admin');
require_once dirname(__DIR__, 3) . '/lib/club_repository.php';

$base = base_path();
$clubs = get_all_clubs();
$clubCount = count($clubs);

$error = $_SESSION['club_form_error'] ?? null;
$success = $_SESSION['club_form_success'] ?? null;
unset($_SESSION['club_form_error'], $_SESSION['club_form_success']);

$title = 'Manage Clubs';

ob_start();
?>
<div class="d-flex align-items-start justify-content-between mb-4 gap-3">
          <div>
                    <h1 class="mb-1">Clubs</h1>
                    <p class="text-muted-alt text-sm mb-0">Create clubs for analysts to use.</p>
          </div>
          <button type="button" class="btn btn-primary-soft btn-sm">+ Club</button>
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
                                                  <label class="form-label">Club name</label>
                                                  <input type="text" placeholder="Search" class="form-control input-dark">
                                        </div>
                                        <div class="filter-group">
                                                  <label class="form-label">Status</label>
                                                  <select class="form-select select-dark">
                                                            <option>Any</option>
                                                            <option>Active</option>
                                                            <option>Needs review</option>
                                                  </select>
                                        </div>
                                        <div class="filter-group">
                                                  <label class="form-label">Created before</label>
                                                  <input type="date" class="form-control input-dark">
                                        </div>
                                        <div class="filter-group">
                                                  <label class="form-label">Created after</label>
                                                  <input type="date" class="form-control input-dark">
                                        </div>
                                        <div class="filter-group">
                                                  <label class="form-label">Club ID</label>
                                                  <input type="text" placeholder="Exact match" class="form-control input-dark">
                                        </div>
                                        <div class="filter-actions mt-auto d-flex gap-2">
                                                  <button type="submit" class="btn btn-primary-soft flex-fill">Apply filters</button>
                                                  <button type="reset" class="btn btn-secondary-soft flex-fill">Clear</button>
                                        </div>
                              </form>
                    </section>
          </div>

          <div class="col-lg-8">
                    <section class="panel p-4 d-flex flex-column gap-4 clubs-panel">
                              <div class="manager-list-header">
                                        <div>
                                                  <h5 class="mb-1 text-light">Clubs</h5>
                                                  <p class="text-muted-alt text-sm mb-0">Manage brand spaces used by analysts.</p>
                                        </div>
                                        <button type="button" class="btn btn-secondary-soft btn-sm">View all</button>
                              </div>

                              <div class="admin-card p-3">
                                        <h6 class="text-muted-alt mb-3">Create club</h6>
                                        <form method="post" action="<?= htmlspecialchars($base) ?>/api/admin/clubs/create" class="row g-3">
                                                  <div class="col-12">
                                                            <label class="form-label">Club name</label>
                                                            <input type="text" name="name" class="form-control input-dark" required>
                                                  </div>
                                                  <div class="col-12 d-flex gap-2">
                                                            <button class="btn btn-primary-soft flex-fill">Create</button>
                                                            <button type="reset" class="btn btn-secondary-soft flex-fill">Reset</button>
                                                  </div>
                                        </form>
                              </div>

                              <div class="table-responsive manager-table">
                                        <table class="table table-borderless align-middle mb-0">
                                                  <thead>
                                                            <tr>
                                                                      <th>Club</th>
                                                                      <th>ID</th>
                                                                      <th class="text-end">Actions</th>
                                                            </tr>
                                                  </thead>
                                                  <tbody>
                                                            <?php if ($clubCount === 0): ?>
                                                                      <tr>
                                                                                <td colspan="3" class="text-muted-alt">No clubs have been created yet.</td>
                                                                      </tr>
                                                            <?php else: ?>
                                                                      <?php foreach ($clubs as $club): ?>
                                                                                <?php
                                                                                $clubName = $club['name'] ?? 'Club';
                                                                                $initialChar = function_exists('mb_substr')
                                                                                          ? mb_substr($clubName, 0, 2)
                                                                                          : substr($clubName, 0, 2);
                                                                                ?>
                                                                                <tr>
                                                                                          <td>
                                                                                                    <div class="d-flex align-items-center gap-3">
                                                                                                              <span class="avatar-badge text-uppercase small"><?= htmlspecialchars($initialChar) ?></span>
                                                                                                              <div>
                                                                                                                        <div><?= htmlspecialchars($clubName) ?></div>
                                                                                                                        <span class="text-muted-alt text-sm">Brand space</span>
                                                                                                              </div>
                                                                                                    </div>
                                                                                          </td>
                                                                                          <td class="text-muted-alt text-sm">#<?= (int)$club['id'] ?></td>
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
                                        <span>Rows per page <strong><?= $clubCount ?></strong> &sdot; out of <?= $clubCount ?></span>
                                        <div class="pagination-chips">
                                                  <button type="button" class="pagination-chip active">1</button>
                                                  <button type="button" class="pagination-chip">2</button>
                                                  <span class="text-muted-alt">…</span>
                                                  <button type="button" class="pagination-chip">10</button>
                                        </div>
                              </div>
                    </section>
          </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../layout.php';
