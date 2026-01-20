<?php
require_once __DIR__ . '/../../../middleware/require_admin.php';
require_admin();

$title = 'Platform Admin';
$base = base_path();

ob_start();
?>
<div class="flex items-center justify-between mb-6">
          <div>
                    <h1 class="mb-1">Platform Admin</h1>
                    <p class="text-muted-alt text-sm mb-0">Manage platform-level data for clubs and users.</p>
          </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
          <div>
                    <div class="panel admin-card h-full">
                              <div class="p-4">
                                        <h5 class="text-light mb-2 font-semibold">Clubs</h5>
                                        <p class="text-muted-alt text-sm mb-3">Create new clubs and browse the directory.</p>
                                        <a href="<?= htmlspecialchars($base) ?>/admin/clubs" class="btn btn-primary-soft btn-sm">Go to clubs</a>
                              </div>
                    </div>
          </div>
          <div>
                    <div class="panel admin-card h-full">
                              <div class="p-4">
                                        <h5 class="text-light mb-2 font-semibold">Users</h5>
                                        <p class="text-muted-alt text-sm mb-3">Create users, assign clubs, and grant roles.</p>
                                        <a href="<?= htmlspecialchars($base) ?>/admin/users" class="btn btn-primary-soft btn-sm">Go to users</a>
                              </div>
                    </div>
          </div>
          <div>
                    <div class="panel admin-card h-full">
                              <div class="p-4">
                                        <h5 class="text-light mb-2 font-semibold">Seasons</h5>
                                        <p class="text-muted-alt text-sm mb-3">Add, edit, or archive club seasons.</p>
                                        <a href="<?= htmlspecialchars($base) ?>/admin/seasons" class="btn btn-primary-soft btn-sm">Manage seasons</a>
                              </div>
                    </div>
          </div>
          <div>
                    <div class="panel admin-card h-full">
                              <div class="p-4">
                                        <h5 class="text-light mb-2 font-semibold">Leagues &amp; Cups</h5>
                                        <p class="text-muted-alt text-sm mb-3">Create competitions, set types, and assign teams.</p>
                                        <a href="<?= htmlspecialchars($base) ?>/admin/competitions" class="btn btn-primary-soft btn-sm">Manage competitions</a>
                              </div>
                    </div>
          </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../layout.php';
