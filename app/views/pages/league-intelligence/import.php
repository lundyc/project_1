<?php
$base = base_path();
$importRows = $importRows ?? [];
$flashSuccess = $flashSuccess ?? null;
$flashError = $flashError ?? null;
$flashInfo = $flashInfo ?? null;
$hasRows = !empty($importRows);

$formatDateTime = static function (?string $dateTime): string {
    if (empty($dateTime)) {
        return 'TBD';
    }
    $ts = strtotime($dateTime);
    if ($ts === false) {
        return $dateTime;
    }
    return date('D, M j Y H:i', $ts);
};

$footerScripts = '';
if ($hasRows) {
    $footerScripts = <<<SCRIPT
<script>
document.addEventListener('click', function (event) {
    var deleteButton = event.target.closest('[data-import-delete]');
    if (!deleteButton) {
        return;
    }
    var rowIndex = deleteButton.getAttribute('data-import-delete');
    var deleteInput = document.querySelector('[data-import-delete-input="' + rowIndex + '"]');
    var row = document.querySelector('[data-import-row="' + rowIndex + '"]');
    var label = document.querySelector('[data-import-delete-label="' + rowIndex + '"]');
    if (!deleteInput || !row) {
        return;
    }
    var isDeleted = deleteInput.value === '1';
    deleteInput.value = isDeleted ? '0' : '1';
    deleteButton.textContent = isDeleted ? 'Delete' : 'Undo';
    row.classList.toggle('opacity-40', !isDeleted);
    row.classList.toggle('bg-rose-900/20', !isDeleted);
    if (label) {
        label.classList.toggle('hidden', isDeleted);
    }
});
</script>
SCRIPT;
}
?>

<?php ob_start(); ?>
<div class="space-y-6">
    <header class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <p class="text-xs uppercase tracking-[0.3em] text-slate-500">League Intelligence</p>
            <h1 class="text-2xl font-semibold text-white">WOSFL Import</h1>
            <p class="text-sm text-slate-400">Pull West of Scotland Football League fixtures and results for preview.</p>
        </div>
        <div class="flex flex-wrap gap-2 text-sm">
            <a href="<?= htmlspecialchars($base) ?>/league-intelligence" class="btn btn-secondary-soft btn-sm">Back to overview</a>
            <?php if (!$hasRows): ?>
                <form method="post" action="<?= htmlspecialchars($base) ?>/league-intelligence/import/run">
                    <button type="submit" class="btn btn-primary-soft btn-sm">Import All Fixtures &amp; Results</button>
                </form>
            <?php endif; ?>
            <form method="post" action="<?= htmlspecialchars($base) ?>/league-intelligence/update-week">
                <button type="submit" class="btn btn-secondary-soft btn-sm">Update This Week</button>
            </form>
        </div>
    </header>

    <?php if ($flashSuccess): ?>
        <div class="rounded-xl border border-emerald-500/40 bg-emerald-500/30 p-4 text-sm text-emerald-200">
            <?= htmlspecialchars($flashSuccess) ?>
        </div>
    <?php endif; ?>
    <?php if ($flashError): ?>
        <div class="rounded-xl border border-rose-700/50 bg-rose-900/20 p-4 text-sm text-rose-400">
            <?= htmlspecialchars($flashError) ?>
        </div>
    <?php endif; ?>
    <?php if ($flashInfo): ?>
        <div class="rounded-xl border border-slate-500/30 bg-slate-800/60 p-4 text-sm text-slate-200">
            <?= htmlspecialchars($flashInfo) ?>
        </div>
    <?php endif; ?>

    <?php if (!$hasRows): ?>
        <section class="rounded-xl bg-slate-900/80 border border-white/10 p-5">
            <h2 class="text-lg font-semibold text-white mb-2">Start an import</h2>
            <p class="text-sm text-slate-400 mb-4">
                Click “Import All Fixtures &amp; Results” to scrape the WOSFL site and preview the fixtures before saving.
            </p>
            <form method="post" action="<?= htmlspecialchars($base) ?>/league-intelligence/import/run">
                <button type="submit" class="btn btn-primary-soft">Import All Fixtures &amp; Results</button>
            </form>
        </section>
    <?php else: ?>
        <form method="post" action="<?= htmlspecialchars($base) ?>/league-intelligence/import/save" class="space-y-4">
            <section class="rounded-xl bg-slate-900/80 border border-white/10 p-5 space-y-4">
                <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-white">Import preview</h2>
                        <p class="text-sm text-slate-400">Review imported fixtures and results before saving.</p>
                    </div>
                    <div class="text-xs text-slate-400"><?= count($importRows) ?> rows</div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[900px] text-left text-sm text-slate-300">
                        <thead>
                            <tr class="text-[11px] uppercase tracking-[0.2em] text-slate-500">
                                <th class="px-3 py-2 w-28">Date / Time</th>
                                <th class="px-3 py-2 w-28">Home Team</th>
                                <th class="px-3 py-2 w-28">Away Team</th>
                                <th class="px-3 py-2 w-28">Competition</th>
                                <th class="px-3 py-2 w-28">Score</th>
                                <th class="px-3 py-2 w-28">Status</th>
                                <th class="px-3 py-2 w-28">Notes</th>
                                <th class="px-3 py-2 w-28">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <?php foreach ($importRows as $index => $row): ?>
                                <?php
                                    $kickoffAt = $row['date_time'] ?? '';
                                    $homeTeam = $row['home_team_name'] ?? '';
                                    $awayTeam = $row['away_team_name'] ?? '';
                                    $competition = $row['competition_name'] ?? '';
                                    $status = $row['status'] ?? '';
                                    $homeGoals = $row['home_goals'] ?? null;
                                    $awayGoals = $row['away_goals'] ?? null;
                                    $teamFound = array_key_exists('team_found', $row) ? (bool)$row['team_found'] : true;
                                    $competitionFound = array_key_exists('competition_found', $row) ? (bool)$row['competition_found'] : true;
                                    $existingMatch = array_key_exists('existing_match', $row) ? (bool)$row['existing_match'] : false;
                                    $notes = [];
                                    if (!$teamFound) {
                                        $notes[] = 'Team missing';
                                    }
                                    if (!$competitionFound) {
                                        $notes[] = 'Competition missing';
                                    }
                                    if ($existingMatch) {
                                        $notes[] = 'Already imported';
                                    }
                                    $kickoffInput = $kickoffAt ?: '';
                                    $homeGoalsValue = $homeGoals !== null ? (string)$homeGoals : '';
                                    $awayGoalsValue = $awayGoals !== null ? (string)$awayGoals : '';
                                    $statusValue = $status ?: (($homeGoals !== null && $awayGoals !== null) ? 'completed' : 'scheduled');
                                    $statusOptions = [
                                        'scheduled' => 'Scheduled',
                                        'completed' => 'Completed',
                                        'cancelled' => 'Cancelled',
                                    ];
                                    if ($statusValue !== '' && !isset($statusOptions[$statusValue])) {
                                        $statusOptions[$statusValue] = ucfirst($statusValue);
                                    }
                                ?>
                                <tr class="align-top text-sm text-slate-200" data-import-row="<?= (int)$index ?>">
                                    <td class="px-3 py-3 w-28">
                                        <input type="text" name="rows[<?= (int)$index ?>][date_time]" value="<?= htmlspecialchars($kickoffInput) ?>" class="input-dark w-28 text-xs" placeholder="YYYY-MM-DD HH:MM">
                                        <div class="text-xs text-slate-500 mt-1"><?= htmlspecialchars($formatDateTime($kickoffAt)) ?></div>
                                    </td>
                                    <td class="px-3 py-3 w-28">
                                        <input type="text" name="rows[<?= (int)$index ?>][home_team_name]" value="<?= htmlspecialchars($homeTeam) ?>" class="input-dark w-28 text-xs" placeholder="Home team">
                                    </td>
                                    <td class="px-3 py-3 w-28">
                                        <input type="text" name="rows[<?= (int)$index ?>][away_team_name]" value="<?= htmlspecialchars($awayTeam) ?>" class="input-dark w-28 text-xs" placeholder="Away team">
                                    </td>
                                    <td class="px-3 py-3 w-28">
                                        <input type="text" name="rows[<?= (int)$index ?>][competition_name]" value="<?= htmlspecialchars($competition) ?>" class="input-dark w-28 text-xs" placeholder="Competition">
                                    </td>
                                    <td class="px-3 py-3 w-28">
                                        <div class="flex items-center gap-2">
                                            <input type="text" min="0" name="rows[<?= (int)$index ?>][home_goals]" value="<?= htmlspecialchars($homeGoalsValue) ?>" class="input-dark w-12 text-center text-xs" placeholder="0">
                                            <span class="text-slate-500">-</span>
                                            <input type="text" min="0" name="rows[<?= (int)$index ?>][away_goals]" value="<?= htmlspecialchars($awayGoalsValue) ?>" class="input-dark w-12 text-center text-xs" placeholder="0">
                                        </div>
                                    </td>
                                    <td class="px-3 py-3 w-28">
                                        <select name="rows[<?= (int)$index ?>][status]" class="select-dark w-28 text-xs">
                                            <?php foreach ($statusOptions as $value => $label): ?>
                                                <option value="<?= htmlspecialchars($value) ?>" <?= $statusValue === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td class="px-3 py-3 w-28">
                                        <?php if (empty($notes)): ?>
                                            —
                                        <?php else: ?>
                                            <div class="flex flex-wrap gap-2">
                                                <?php foreach ($notes as $note): ?>
                                                    <span class="rounded-full bg-rose-900/30 px-2 py-0.5 text-xs text-rose-200"><?= htmlspecialchars($note) ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                        <input type="hidden" name="rows[<?= (int)$index ?>][team_found]" value="<?= $teamFound ? '1' : '0' ?>">
                                        <input type="hidden" name="rows[<?= (int)$index ?>][competition_found]" value="<?= $competitionFound ? '1' : '0' ?>">
                                        <input type="hidden" name="rows[<?= (int)$index ?>][existing_match]" value="<?= $existingMatch ? '1' : '0' ?>">
                                        <input type="hidden" name="rows[<?= (int)$index ?>][home_team_id]" value="<?= htmlspecialchars((string)($row['home_team_id'] ?? '')) ?>">
                                        <input type="hidden" name="rows[<?= (int)$index ?>][away_team_id]" value="<?= htmlspecialchars((string)($row['away_team_id'] ?? '')) ?>">
                                        <input type="hidden" name="rows[<?= (int)$index ?>][competition_id]" value="<?= htmlspecialchars((string)($row['competition_id'] ?? '')) ?>">
                                        <input type="hidden" name="rows[<?= (int)$index ?>][source_url]" value="<?= htmlspecialchars((string)($row['source_url'] ?? '')) ?>">
                                    </td>
                                    <td class="px-3 py-3 w-28">
                                        <div class="flex flex-col gap-2">
                                            <div class="flex gap-2">
                                                <button type="button" class="btn btn-secondary-soft btn-sm" data-import-delete="<?= (int)$index ?>">Delete</button>
                                            </div>
                                            <span class="text-xs text-rose-300 hidden" data-import-delete-label="<?= (int)$index ?>">Marked for deletion</span>
                                        </div>
                                        <input type="hidden" name="rows[<?= (int)$index ?>][deleted]" value="0" data-import-delete-input="<?= (int)$index ?>">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            <div class="flex flex-wrap gap-3">
                <button type="submit" class="btn btn-primary-soft">Save Import</button>
                <a href="<?= htmlspecialchars($base) ?>/league-intelligence" class="btn btn-secondary-soft">Cancel</a>
            </div>
        </form>
    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../layout.php';
