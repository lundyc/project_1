<?php
require_role('platform_admin');
require_once dirname(__DIR__, 3) . '/lib/admin_user_repository.php';
require_once dirname(__DIR__, 3) . '/lib/club_repository.php';

$base = base_path();
$roles = get_roles();
$roleOptions = [];
foreach ($roles as $roleRow) {
    $roleKey = $roleRow['role_key'] ?? null;
    if ($roleKey !== null) {
        $roleOptions[$roleKey] = $roleKey;
    }
}
$clubs = get_all_clubs();

// Get user ID from query param
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$userId) {
    http_response_code(404);
    echo 'User not found.';
    exit;
}

$user = get_user_by_id($userId);
if (!$user) {
    http_response_code(404);
    echo 'User not found.';
    exit;
}

$error = $_SESSION['user_form_error'] ?? null;
$success = $_SESSION['user_form_success'] ?? null;
unset($_SESSION['user_form_error'], $_SESSION['user_form_success']);

$title = 'Edit User';

ob_start();
?>
<?php
$headerTitle = 'Edit User';
$headerDescription = 'Update user details, club, and roles.';
$headerButtons = [
    '<a href="' . htmlspecialchars($base) . '/admin/users" class="stats-tab w-full justify-start text-left px-4 py-2.5 text-sm font-medium rounded-lg border transition-all duration-200 bg-slate-700 border-slate-600 text-white flex">Back to Users</a>'
];
include __DIR__ . '/../../partials/header.php';
?>
<div class="w-full mt-4 text-slate-200">
    <div class="max-w-2xl mx-auto">
        <?php if ($error): ?>
            <div class="rounded-lg bg-red-900/80 border border-red-700 text-red-200 px-4 py-3 mb-4 text-sm"><?= htmlspecialchars($error) ?></div>
        <?php elseif ($success): ?>
            <div class="rounded-lg bg-emerald-900/80 border border-emerald-700 text-emerald-200 px-4 py-3 mb-4 text-sm"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <form method="post" action="<?= htmlspecialchars($base) ?>/api/admin/update_user.php" class="space-y-6 bg-slate-800 border border-white/10 rounded-xl p-6">
            <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
            <div>
                <label class="block text-slate-400 text-xs mb-1">Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" class="block w-full rounded-md bg-slate-900/60 border border-white/20 px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-white/30" required>
            </div>
            <div>
                <label class="block text-slate-400 text-xs mb-1">Display Name</label>
                <input type="text" name="display_name" value="<?= htmlspecialchars($user['display_name'] ?? '') ?>" class="block w-full rounded-md bg-slate-900/60 border border-white/20 px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-white/30" required>
            </div>
            <div>
                <label class="block text-slate-400 text-xs mb-1">Club</label>
                <select name="club_id" class="block w-full rounded-md bg-slate-900/60 border border-white/20 px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-white/30">
                    <option value="">Unassigned</option>
                    <?php foreach ($clubs as $club): ?>
                        <option value="<?= (int)$club['id'] ?>" <?= ($user['club_id'] ?? null) == $club['id'] ? 'selected' : '' ?>><?= htmlspecialchars($club['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-slate-400 text-xs mb-1">Roles</label>
                <select name="roles[]" multiple class="block w-full rounded-md bg-slate-900/60 border border-white/20 px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-white/30">
                    <?php foreach ($roleOptions as $roleKey => $roleLabel): ?>
                        <option value="<?= htmlspecialchars($roleKey) ?>" <?= in_array($roleKey, $user['roles'] ?? []) ? 'selected' : '' ?>><?= htmlspecialchars($roleLabel) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="text-xs text-slate-500 mt-1">Hold Ctrl (Windows) or Cmd (Mac) to select multiple roles.</div>
            </div>
            <div>
                <label class="block text-slate-400 text-xs mb-1">Password <span class="text-slate-500">(leave blank to keep current)</span></label>
                <input type="password" name="password" autocomplete="new-password" class="block w-full rounded-md bg-slate-900/60 border border-white/20 px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-white/30">
            </div>
                        <div class="flex items-center gap-3 mt-2">
                                <label for="is_active" class="text-slate-400 text-xs">Active</label>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" id="is_active" name="is_active" value="1" class="sr-only peer" <?= !isset($user['is_active']) || $user['is_active'] ? 'checked' : '' ?>>
                                    <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-indigo-500 rounded-full peer peer-checked:bg-indigo-600 transition-all duration-200"></div>
                                    <div class="absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition-all duration-200 peer-checked:translate-x-5"></div>
                                </label>
                        </div>
            <div class="flex flex-col gap-1 text-xs text-slate-500 mt-2">
                <div>Created: <?= htmlspecialchars($user['created_at'] ?? '-') ?></div>
                <div>Last Updated: <?= htmlspecialchars($user['updated_at'] ?? '-') ?></div>
            </div>
            <div class="flex gap-2 justify-end mt-4">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="<?= htmlspecialchars($base) ?>/admin/users" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../layout.php';
