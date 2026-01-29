<?php
require_once __DIR__ . '/../../../../lib/auth.php';
require_once __DIR__ . '/../../../../lib/player_repository.php';
require_once __DIR__ . '/../../../../lib/team_repository.php';

$context = require_club_admin_access();
$clubId = $context['club_id'];

$teams = get_teams_by_club($clubId);

$flashError = $_SESSION['player_create_error'] ?? null;
$formInput = $_SESSION['player_create_input'] ?? [];
unset($_SESSION['player_create_error'], $_SESSION['player_create_input']);

function field_value(array $formInput, string $key, $default = null)
{
    return isset($formInput[$key]) && $formInput[$key] !== '' ? $formInput[$key] : $default;
}

$values = [
    'first_name' => field_value($formInput, 'first_name', ''),
    'last_name' => field_value($formInput, 'last_name', ''),
    'primary_position' => field_value($formInput, 'primary_position', ''),
    'dob' => field_value($formInput, 'dob', ''),
    'team_id' => field_value($formInput, 'team_id', ''),
    'is_active' => field_value($formInput, 'is_active', 1),
];

$title = 'Add Player';
$base = base_path();

ob_start();
?>
<div class="w-full mt-4 text-slate-200">
    <div class="max-w-xl mx-auto">
        <h1 class="text-2xl font-bold mb-4">Add Player</h1>
        <?php if ($flashError): ?>
            <div class="rounded-lg bg-red-900/80 border border-red-700 text-red-200 px-4 py-3 mb-4 text-sm"><?= htmlspecialchars($flashError) ?></div>
        <?php endif; ?>
        <form method="post" action="<?= $base ?>/admin/players/create_action.php" class="space-y-4">
            <div>
                <label class="block text-slate-400 text-xs mb-1">First Name</label>
                <input type="text" name="first_name" class="input-dark w-full" value="<?= htmlspecialchars($values['first_name']) ?>" required>
            </div>
            <div>
                <label class="block text-slate-400 text-xs mb-1">Last Name</label>
                <input type="text" name="last_name" class="input-dark w-full" value="<?= htmlspecialchars($values['last_name']) ?>" required>
            </div>
            <div>
                <label class="block text-slate-400 text-xs mb-1">Primary Position</label>
                <input type="text" name="primary_position" class="input-dark w-full" value="<?= htmlspecialchars($values['primary_position']) ?>">
            </div>
            <div>
                <label class="block text-slate-400 text-xs mb-1">Date of Birth</label>
                <input type="date" name="dob" class="input-dark w-full" value="<?= htmlspecialchars($values['dob']) ?>">
            </div>
            <div>
                <label class="block text-slate-400 text-xs mb-1">Team</label>
                <select name="team_id" class="input-dark w-full">
                    <option value="">-- None --</option>
                    <?php foreach ($teams as $team): ?>
                        <option value="<?= (int)$team['id'] ?>" <?= $values['team_id'] == $team['id'] ? 'selected' : '' ?>><?= htmlspecialchars($team['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-slate-400 text-xs mb-1">Active</label>
                <select name="is_active" class="input-dark w-full">
                    <option value="1" <?= $values['is_active'] == 1 ? 'selected' : '' ?>>Yes</option>
                    <option value="0" <?= $values['is_active'] == 0 ? 'selected' : '' ?>>No</option>
                </select>
            </div>
            <div class="flex justify-end gap-2">
                <a href="<?= $base ?>/admin/players/list.php" class="inline-flex items-center gap-2 bg-bg-secondary text-text-primary border border-border-soft px-4 py-2 rounded-md hover:bg-bg-secondary/80 transition">Cancel</a>
                <button type="submit" class="inline-flex items-center gap-2 bg-accent-primary text-white px-4 py-2 rounded-md hover:bg-accent-primary/80 transition">Create Player</button>
            </div>
        </form>
    </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../../../layout.php';
