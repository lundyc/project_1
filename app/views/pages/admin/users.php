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
<?php
$headerTitle = 'Platform Users';
$headerDescription = 'Create users, assign clubs, and grant roles.';
$headerButtons = [];
    // Add User button links to new page
    $headerButtons[] = '<a href="' . htmlspecialchars($base) . '/admin/users/create" class="stats-tab w-full justify-start text-left px-4 py-2.5 text-sm font-medium rounded-lg border transition-all duration-200 bg-indigo-600 border-indigo-500 text-white shadow-lg shadow-indigo-500/20 flex">Add User</a>';
include __DIR__ . '/../../partials/header.php';
?>
<div class="w-full mt-4 text-slate-200">
    <div class="max-w-full">
        <div class="flex gap-6 px-4 md:px-6 lg:px-8 w-full">
            <!-- Left: Filters & User Summary -->
            <aside class="space-y-4 min-w-0 flex-shrink-0 w-1/5 max-w-xs">
                <form method="get" class="space-y-4">
                    <div>
                        <label class="block text-slate-400 text-xs mb-1">Email</label>
                        <input type="text" name="email" placeholder="Contains" class="block w-full rounded-md bg-slate-900/60 border border-white/20 px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-white/30">
                    </div>
                    <div>
                        <label class="block text-slate-400 text-xs mb-1">First name</label>
                        <input type="text" name="first_name" placeholder="Starts with" class="block w-full rounded-md bg-slate-900/60 border border-white/20 px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-white/30">
                    </div>
                    <div>
                        <label class="block text-slate-400 text-xs mb-1">Last name</label>
                        <input type="text" name="last_name" placeholder="Starts with" class="block w-full rounded-md bg-slate-900/60 border border-white/20 px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-white/30">
                    </div>
                    <div>
                        <label class="block text-slate-400 text-xs mb-1">Tags</label>
                        <input type="text" name="tags" placeholder="VIP, Finance, Manager" class="block w-full rounded-md bg-slate-900/60 border border-white/20 px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-white/30">
                    </div>
                    <div>
                        <label class="block text-slate-400 text-xs mb-1">Client ID</label>
                        <input type="text" name="client_id" placeholder="Exact match" class="block w-full rounded-md bg-slate-900/60 border border-white/20 px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-white/30">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="btn btn-primary-soft btn-sm flex-1">Apply</button>
                        <a href="<?= htmlspecialchars($base) ?>/admin/users" class="btn btn-secondary-soft btn-sm flex-1">Clear</a>
                    </div>
                </form>
                <div class="rounded-xl bg-slate-900/80 border border-white/10 p-4 mt-6">
                    <h5 class="text-slate-200 font-semibold mb-1">User Summary</h5>
                    <div class="text-slate-400 text-xs mb-4">Quick overview</div>
                    <div class="space-y-3">
                        <article class="rounded-lg border border-white/10 bg-slate-800/40 px-3 py-3">
                            <div class="text-xs font-semibold text-slate-300 mb-2 text-center">Total Users</div>
                            <div class="text-2xl font-bold text-slate-100 text-center"><?= $userCount ?></div>
                        </article>
                    </div>
                </div>
            </aside>

            <!-- Main: Users Table -->
            <main class="flex-1 space-y-4 min-w-0">
                <?php if ($error): ?>
                    <div class="rounded-lg bg-red-900/80 border border-red-700 text-red-200 px-4 py-3 mb-4 text-sm"><?= htmlspecialchars($error) ?></div>
                <?php elseif ($success): ?>
                    <div class="rounded-lg bg-emerald-900/80 border border-emerald-700 text-emerald-200 px-4 py-3 mb-4 text-sm"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
                <div class="rounded-xl bg-slate-800 border border-white/10 p-3">
                    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-2 mb-2">
                        <div>
                            <h1 class="text-2xl font-semibold text-white mb-1">Users</h1>
                            <p class="text-xs text-slate-400">
                                All users.
                            </p>
                        </div>
                    </div>
                <div class="overflow-x-auto rounded-xl border border-white/10 bg-slate-800/40 p-2">
                    <table class="w-full text-sm text-slate-200">
                        <thead class="sticky top-0 bg-slate-900/95 border-b border-white/10">
                            <tr>
                                <th class="px-6 py-3 text-left font-semibold uppercase tracking-wide text-slate-300">Name</th>
                                <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide text-slate-300">Email</th>
                                <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide text-slate-300">Club</th>
                                <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide text-slate-300">Roles</th>
                                <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide text-slate-300">ID</th>
                                <th class="px-4 py-3 text-right font-semibold uppercase tracking-wide text-slate-300">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($userCount === 0): ?>
                                <tr>
                                    <td colspan="6" class="text-muted-alt text-center">No users found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <?php
                                    $displayName = $user['display_name'] ?? 'User';
                                    $email = $user['email'] ?? '';
                                    $clubName = $user['club_name'] ?? 'Unassigned';
                                    $rolesList = $user['roles'] ?? [];
                                    ?>
                                    <tr class="border-b border-white/10 hover:bg-slate-800/50 transition-colors">
                                        <td class="px-6 py-3 font-semibold text-slate-100"><?= htmlspecialchars($displayName) ?></td>
                                        <td class="px-4 py-3 text-slate-300"><?= htmlspecialchars($email) ?></td>
                                        <td class="px-4 py-3 text-slate-300"><?= htmlspecialchars($clubName) ?></td>
                                        <td class="px-4 py-3">
                                            <?php if (empty($rolesList)): ?>
                                                <span class="text-muted-alt text-sm">No roles yet</span>
                                            <?php else: ?>
                                                <div class="flex flex-wrap gap-2">
                                                    <?php foreach ($rolesList as $roleKey): ?>
                                                        <span class="tag-pill"><?= htmlspecialchars($roleKey) ?></span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-3 text-slate-400">#<?= (int)$user['id'] ?></td>
                                        <td class="px-4 py-3 text-right">
                                            <a href="<?= htmlspecialchars($base) ?>/admin/users/<?= (int)$user['id'] ?>/edit" class="inline-flex items-center rounded-md bg-indigo-700/60 px-2 py-1 text-xs text-white hover:bg-indigo-700 transition" aria-label="Edit user">
                                                <i class="fa-solid fa-pen"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </main>

            <!-- Right column removed -->
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../layout.php';
