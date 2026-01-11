<?php
require_once __DIR__ . '/../../../middleware/require_admin.php';
require_admin();

$title = 'Platform Admin';
$base = base_path();

ob_start();
?>
<div class="d-flex align-items-center justify-content-between mb-4">
          <div>
                    <h1 class="mb-1">Platform Admin</h1>
                    <p class="text-muted-alt text-sm mb-0">Manage platform-level data for clubs and users.</p>
          </div>
</div>

<div class="row g-3">
          <div class="col-md-6">
                    <div class="panel admin-card h-100">
                              <div class="card-body p-3">
                                        <h5 class="card-title text-light mb-2">Clubs</h5>
                                        <p class="text-muted-alt small mb-3">Create new clubs and browse the directory.</p>
                                        <a href="<?= htmlspecialchars($base) ?>/admin/clubs" class="btn btn-primary-soft btn-sm">Go to clubs</a>
                              </div>
                    </div>
          </div>
          <div class="col-md-6">
                    <div class="panel admin-card h-100">
                              <div class="card-body p-3">
                                        <h5 class="card-title text-light mb-2">Users</h5>
                                        <p class="text-muted-alt small mb-3">Create users, assign clubs, and grant roles.</p>
                                        <a href="<?= htmlspecialchars($base) ?>/admin/users" class="btn btn-primary-soft btn-sm">Go to users</a>
                              </div>
                    </div>
          </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../layout.php';
