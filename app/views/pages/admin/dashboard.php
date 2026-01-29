<?php
require_once __DIR__ . '/../../../middleware/require_admin.php';
require_admin();

$title = 'Platform Admin';
$base = base_path();

ob_start();
?>

<!-- Club context header removed. -->
<?php $pageTitle = 'Platform Admin'; $pageDescription = 'Manage platform-level data for clubs and users.'; ?>

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
          <div>
                    <div class="panel admin-card h-full">
                              <div class="p-4">
                                        <h5 class="text-light mb-2 font-semibold">Clubs</h5>
                                        <p class="text-muted-alt text-sm mb-3">Create new clubs and browse the directory.</p>
                                        <a href="<?= htmlspecialchars($base) ?>/admin/clubs" class="inline-flex items-center gap-2 bg-accent-primary text-white px-4 py-2 rounded-md hover:bg-accent-primary/80 transition">Go to clubs</a>
                              </div>
                    </div>
          </div>
          <div>
                    <div class="panel admin-card h-full">
                              <div class="p-4">
                                        <h5 class="text-light mb-2 font-semibold">Teams</h5>
                                        <p class="text-muted-alt text-sm mb-3">Create teams, update types, and move between clubs.</p>
                                        <a href="<?= htmlspecialchars($base) ?>/admin/teams" class="inline-flex items-center gap-2 bg-accent-primary text-white px-4 py-2 rounded-md hover:bg-accent-primary/80 transition">Manage teams</a>
                              </div>
                    </div>
          </div>
          <div>
                    <div class="panel admin-card h-full">
                              <div class="p-4">
                                        <h5 class="text-light mb-2 font-semibold">Users</h5>
                                        <p class="text-muted-alt text-sm mb-3">Create users, assign clubs, and grant roles.</p>
                                        <a href="<?= htmlspecialchars($base) ?>/admin/users" class="inline-flex items-center gap-2 bg-accent-primary text-white px-4 py-2 rounded-md hover:bg-accent-primary/80 transition">Go to users</a>
                              </div>
                    </div>
          </div>
          <div>
                    <div class="panel admin-card h-full">
                              <div class="p-4">
                                        <h5 class="text-light mb-2 font-semibold">Seasons</h5>
                                        <p class="text-muted-alt text-sm mb-3">Add, edit, or archive club seasons.</p>
                                        <a href="<?= htmlspecialchars($base) ?>/admin/seasons" class="inline-flex items-center gap-2 bg-accent-primary text-white px-4 py-2 rounded-md hover:bg-accent-primary/80 transition">Manage seasons</a>
                              </div>
                    </div>
          </div>
          <div>
                    <div class="panel admin-card h-full">
                              <div class="p-4">
                                        <h5 class="text-light mb-2 font-semibold">Leagues &amp; Cups</h5>
                                        <p class="text-muted-alt text-sm mb-3">Create competitions, set types, and assign teams.</p>
                                        <a href="<?= htmlspecialchars($base) ?>/admin/competitions" class="inline-flex items-center gap-2 bg-accent-primary text-white px-4 py-2 rounded-md hover:bg-accent-primary/80 transition">Manage competitions</a>
                              </div>
                    </div>
          </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../layout.php';
