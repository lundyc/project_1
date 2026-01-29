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

$errorBadgeClass = 'bg-rose-900/30 border border-rose-700/50 text-rose-300 text-xs px-2 py-0.5 rounded-full';
$getRowState = static function (array $row): array {
    $teamFound = array_key_exists('team_found', $row) ? (bool)$row['team_found'] : true;
    $homeFound = array_key_exists('home_team_found', $row) ? (bool)$row['home_team_found'] : $teamFound;
    $awayFound = array_key_exists('away_team_found', $row) ? (bool)$row['away_team_found'] : $teamFound;
    $competitionFound = array_key_exists('competition_found', $row) ? (bool)$row['competition_found'] : true;
    $existingMatch = array_key_exists('existing_match', $row) ? (bool)$row['existing_match'] : false;
    $deleted = isset($row['deleted']) && $row['deleted'] == '1';
    $hasError = (!$homeFound || !$awayFound || !$competitionFound || $existingMatch);
    $ready = !$deleted && !$hasError;

    return [
        'deleted' => $deleted,
        'has_error' => $hasError,
        'ready' => $ready,
    ];
};

$groupedRows = [];
$filteredRowsCount = 0;
if (!empty($importRows)) {
    foreach ($importRows as $index => $row) {
        $competitionName = trim((string)($row['competition_name'] ?? ''));
        if (strcasecmp($competitionName, 'Fourth Division') !== 0) {
            continue;
        }

        $sourceTeam = trim((string)($row['source_team_name'] ?? ''));
        if ($sourceTeam === '') {
            $sourceTeam = trim((string)($row['home_team_name'] ?? ''));
        }
        if ($sourceTeam === '') {
            $sourceTeam = trim((string)($row['away_team_name'] ?? ''));
        }
        if ($sourceTeam === '') {
            $sourceTeam = 'Unknown team';
        }

        $groupKey = strtolower($sourceTeam);
        if (!isset($groupedRows[$groupKey])) {
            $groupedRows[$groupKey] = [
                'name' => $sourceTeam,
                'rows' => [],
                'summary' => [
                    'total' => 0,
                    'ready' => 0,
                    'errors' => 0,
                    'skipped' => 0,
                ],
            ];
        }

        $state = $getRowState($row);
        $groupedRows[$groupKey]['rows'][] = [
            'index' => $index,
            'row' => $row,
        ];
        $groupedRows[$groupKey]['summary']['total']++;
        $filteredRowsCount++;
        if ($state['ready']) {
            $groupedRows[$groupKey]['summary']['ready']++;
        } elseif ($state['deleted']) {
            $groupedRows[$groupKey]['summary']['skipped']++;
        } else {
            $groupedRows[$groupKey]['summary']['errors']++;
        }
    }

    uasort($groupedRows, static function (array $a, array $b): int {
        return strcasecmp($a['name'], $b['name']);
    });
}

// No longer needed: JS is now at the bottom and rewritten
ob_start();
?>
<div class="space-y-6">
    <header class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <p class="text-xs uppercase tracking-widest text-slate-500">League Intelligence</p>
            <h1 class="text-2xl font-semibold text-white">WOSFL Import</h1>
            <p class="text-sm text-slate-400">Pull West of Scotland Football League fixtures and results for preview.</p>
        </div>
        <div class="flex flex-wrap gap-2 text-sm">
            <a href="<?= htmlspecialchars($base) ?>/league-intelligence" class="inline-block rounded-lg border border-slate-500/30 bg-slate-800/60 px-3 py-1.5 text-slate-200 hover:bg-slate-700 transition">Back to overview</a>
            <?php if (!$hasRows): ?>
                <form method="post" action="<?= htmlspecialchars($base) ?>/league-intelligence/import/run">
                    <button type="submit" class="inline-block rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 transition">Import All Fixtures &amp; Results</button>
                </form>
            <?php endif; ?>
            <form method="post" action="<?= htmlspecialchars($base) ?>/league-intelligence/update-week">
                <button type="submit" class="inline-block rounded-lg border border-slate-500/30 bg-slate-800/60 px-3 py-1.5 text-slate-200 hover:bg-slate-700 transition">Update This Week</button>
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
                <button type="submit" class="inline-block rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 transition">Import All Fixtures &amp; Results</button>
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
                    <div class="text-xs text-slate-400"><?= $filteredRowsCount ?> rows</div>
                </div>
                <div class="flex flex-col gap-6">
                    <?php foreach ($groupedRows as $groupKey => $group): ?>
                        <?php
                            $summary = $group['summary'];
                            $summaryParts = [
                                sprintf('%d matches', $summary['total']),
                                sprintf('%d ready', $summary['ready']),
                            ];
                            if ($summary['errors'] > 0) {
                                $summaryParts[] = sprintf('%d errors', $summary['errors']);
                            }
                            if ($summary['skipped'] > 0) {
                                $summaryParts[] = sprintf('%d skipped', $summary['skipped']);
                            }
                            $summaryText = implode(' — ', $summaryParts);
                            $acceptAllDisabled = $summary['ready'] === 0;
                        ?>
                        <div class="rounded-xl border border-white/10 bg-slate-900/70 p-4 space-y-4" data-team-group="<?= htmlspecialchars($groupKey) ?>">
                            <div class="flex flex-wrap gap-3 items-center justify-between">
                                <div class="min-w-[12rem]">
                                    <h3 class="text-base font-semibold text-white"><?= htmlspecialchars($group['name']) ?></h3>
                                    <p class="text-xs text-slate-400"><?= htmlspecialchars($summaryText) ?></p>
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <button type="button"
                                        class="inline-flex items-center justify-center rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 text-xs font-semibold uppercase tracking-wide disabled:bg-emerald-300 disabled:cursor-not-allowed transition"
                                        data-team-accept-all="1" <?= $acceptAllDisabled ? 'disabled' : '' ?>>
                                        Accept All
                                    </button>
                                </div>
                            </div>
                            <div class="flex flex-col gap-3">
                                <?php foreach ($group['rows'] as $entry): ?>
                                    <?php
                                        $index = $entry['index'];
                                        $row = $entry['row'];
                                        $kickoffAt = $row['date_time'] ?? '';
                                        $homeTeam = $row['home_team_name'] ?? '';
                                        $awayTeam = $row['away_team_name'] ?? '';
                                        $competition = $row['competition_name'] ?? '';
                                        $status = $row['status'] ?? '';
                                        $homeGoals = $row['home_goals'] ?? null;
                                        $awayGoals = $row['away_goals'] ?? null;
                                        $teamFound = array_key_exists('team_found', $row) ? (bool)$row['team_found'] : true;
                                        $homeTeamFound = array_key_exists('home_team_found', $row) ? (bool)$row['home_team_found'] : $teamFound;
                                        $awayTeamFound = array_key_exists('away_team_found', $row) ? (bool)$row['away_team_found'] : $teamFound;
                                        $homeTeamAmbiguous = array_key_exists('home_team_ambiguous', $row) ? (bool)$row['home_team_ambiguous'] : false;
                                        $awayTeamAmbiguous = array_key_exists('away_team_ambiguous', $row) ? (bool)$row['away_team_ambiguous'] : false;
                                        $competitionFound = array_key_exists('competition_found', $row) ? (bool)$row['competition_found'] : true;
                                        $competitionAmbiguous = array_key_exists('competition_ambiguous', $row) ? (bool)$row['competition_ambiguous'] : false;
                                        $existingMatch = array_key_exists('existing_match', $row) ? (bool)$row['existing_match'] : false;
                                        $deleted = isset($row['deleted']) && $row['deleted'] == '1';
                                        $rowHasError = (!$homeTeamFound || !$awayTeamFound || !$competitionFound || $existingMatch);
                                        $rowReady = (!$deleted && !$rowHasError);
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
                                        $notes = [];
                                        if (!$homeTeamFound) {
                                            $notes[] = $homeTeamAmbiguous ? ('Home team ambiguous: ' . $homeTeam) : ('Home team missing: ' . $homeTeam);
                                        }
                                        if (!$awayTeamFound) {
                                            $notes[] = $awayTeamAmbiguous ? ('Away team ambiguous: ' . $awayTeam) : ('Away team missing: ' . $awayTeam);
                                        }
                                        if (!$competitionFound) {
                                            $notes[] = $competitionAmbiguous ? ('Competition ambiguous: ' . $competition) : ('Competition missing: ' . $competition);
                                        }
                                        if ($existingMatch) {
                                            $notes[] = 'Duplicate match skipped';
                                        }
                                    ?>
                                    <div class="rounded-xl bg-slate-800/80 border border-white/10 p-4 flex flex-col gap-2 <?= $deleted ? 'opacity-40 bg-slate-900/50 border-slate-700/60' : '' ?>" data-import-row="<?= (int)$index ?>" data-row-ready="<?= $rowReady ? '1' : '0' ?>" data-row-error="<?= $rowHasError ? '1' : '0' ?>" data-row-deleted="<?= $deleted ? '1' : '0' ?>">
                                        <!-- Header Row -->
                                        <div class="flex flex-wrap gap-2 items-center">
                                            <input type="text" name="rows[<?= (int)$index ?>][date_time]" value="<?= htmlspecialchars($kickoffInput) ?>" class="text-xs h-9 rounded-md border border-slate-600 bg-slate-900 text-slate-100 px-2 w-40 focus:ring-2 focus:ring-indigo-500" placeholder="YYYY-MM-DD HH:MM" <?= $deleted ? 'disabled' : '' ?> />
                                            <select name="rows[<?= (int)$index ?>][status]" class="text-xs h-9 rounded-md border border-slate-600 bg-slate-900 text-slate-100 px-2 w-32 focus:ring-2 focus:ring-indigo-500" <?= $deleted ? 'disabled' : '' ?> >
                                                <?php foreach ($statusOptions as $value => $label): ?>
                                                    <option value="<?= htmlspecialchars($value) ?>" <?= $statusValue === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="text-xs text-slate-500 ml-2 mt-1">
                                                <?= htmlspecialchars($formatDateTime($kickoffAt)) ?>
                                            </div>
                                        </div>
                                        <!-- Teams & Score Row -->
                                        <div class="flex flex-wrap gap-2 items-center">
                                            <div class="flex-1 flex items-center gap-1 max-w-[12rem] w-full">
                                                <input type="text" name="rows[<?= (int)$index ?>][home_team_name]" value="<?= htmlspecialchars($homeTeam) ?>" class="text-xs h-9 rounded-md border border-slate-600 bg-slate-900 text-slate-100 px-2 max-w-[12rem] w-full focus:ring-2 focus:ring-indigo-500 <?= !$homeTeamFound ? 'border-rose-700 bg-rose-900/30' : '' ?>" placeholder="Home team" <?= $deleted ? 'disabled' : '' ?> />
                                                <?php if (!$homeTeamFound): ?>
                                                    <button type="button" class="inline-block rounded bg-rose-700 hover:bg-rose-800 text-white px-2 py-1 text-xs ml-1 transition" data-add-team="<?= (int)$index ?>" data-team-type="home">Add</button>
                                                <?php endif; ?>
                                            </div>
                                            <input type="number" min="0" name="rows[<?= (int)$index ?>][home_goals]" value="<?= htmlspecialchars($homeGoalsValue) ?>" class="text-xs h-9 rounded-md border border-slate-600 bg-slate-900 text-slate-100 px-2 w-16 text-center focus:ring-2 focus:ring-indigo-500" placeholder="0" <?= $deleted ? 'disabled' : '' ?> />
                                            <span class="text-slate-500">-</span>
                                            <input type="number" min="0" name="rows[<?= (int)$index ?>][away_goals]" value="<?= htmlspecialchars($awayGoalsValue) ?>" class="text-xs h-9 rounded-md border border-slate-600 bg-slate-900 text-slate-100 px-2 w-16 text-center focus:ring-2 focus:ring-indigo-500" placeholder="0" <?= $deleted ? 'disabled' : '' ?> />
                                            <div class="flex-1 flex items-center gap-1 max-w-[12rem] w-full">
                                                <input type="text" name="rows[<?= (int)$index ?>][away_team_name]" value="<?= htmlspecialchars($awayTeam) ?>" class="text-xs h-9 rounded-md border border-slate-600 bg-slate-900 text-slate-100 px-2 max-w-[12rem] w-full focus:ring-2 focus:ring-indigo-500 <?= !$awayTeamFound ? 'border-rose-700 bg-rose-900/30' : '' ?>" placeholder="Away team" <?= $deleted ? 'disabled' : '' ?> />
                                                <?php if (!$awayTeamFound): ?>
                                                    <button type="button" class="inline-block rounded bg-rose-700 hover:bg-rose-800 text-white px-2 py-1 text-xs ml-1 transition" data-add-team="<?= (int)$index ?>" data-team-type="away">Add</button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <!-- Competition Row -->
                                        <div class="flex flex-wrap gap-2 items-center">
                                            <div class="flex-1 flex items-center gap-1">
                                                <input type="text" name="rows[<?= (int)$index ?>][competition_name]" value="<?= htmlspecialchars($competition) ?>" class="text-xs h-9 rounded-md border border-slate-600 bg-slate-900 text-slate-100 px-2 w-full focus:ring-2 focus:ring-indigo-500 <?= !$competitionFound ? 'border-rose-700 bg-rose-900/30' : '' ?>" placeholder="Competition" <?= $deleted ? 'disabled' : '' ?> />
                                                <?php if (!$competitionFound): ?>
                                                    <button type="button" class="inline-block rounded bg-rose-700 hover:bg-rose-800 text-white px-2 py-1 text-xs ml-1 transition" data-add-competition="<?= (int)$index ?>">Add</button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <!-- Notes & Actions Row -->
                                        <!-- Error Area -->
                                        <div class="flex flex-col gap-1">
                                            <div class="flex flex-wrap gap-2 error-area">
                                                <?php if (!empty($notes)): ?>
                                                    <?php foreach ($notes as $note): ?>
                                                        <span class="<?= $errorBadgeClass ?>"> <?= htmlspecialchars($note) ?> </span>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <span class="text-xs text-slate-500 no-issues">No issues</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex flex-wrap gap-2 items-center justify-between mt-1">
                                                <div class="flex flex-wrap gap-2">
                                                    <?php if ($deleted): ?>
                                                        <span class="rounded bg-slate-600/60 px-2 py-0.5 text-xs text-slate-200">Skipped</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="flex gap-2">
                                                    <button type="button"
                                                        class="h-8 px-3 rounded-md text-xs font-medium bg-emerald-600 hover:bg-emerald-700 text-white disabled:bg-emerald-300 disabled:cursor-not-allowed transition-colors"
                                                        data-import-accept="<?= (int)$index ?>" <?= ($deleted || $existingMatch) ? 'disabled' : '' ?>>
                                                        Accept
                                                    </button>
                                                    <button type="button"
                                                        class="h-8 px-3 rounded-md text-xs font-medium bg-rose-600 hover:bg-rose-700 text-white disabled:bg-rose-300 disabled:cursor-not-allowed transition-colors"
                                                        data-import-delete="<?= (int)$index ?>">
                                                        <?= $deleted ? 'Undo' : 'Delete' ?>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Hidden fields -->
                                        <input type="hidden" name="rows[<?= (int)$index ?>][team_found]" value="<?= $teamFound ? '1' : '0' ?>">
                                        <input type="hidden" name="rows[<?= (int)$index ?>][competition_found]" value="<?= $competitionFound ? '1' : '0' ?>">
                                        <input type="hidden" name="rows[<?= (int)$index ?>][existing_match]" value="<?= $existingMatch ? '1' : '0' ?>">
                                        <input type="hidden" name="rows[<?= (int)$index ?>][home_team_id]" value="<?= htmlspecialchars((string)($row['home_team_id'] ?? '')) ?>">
                                        <input type="hidden" name="rows[<?= (int)$index ?>][away_team_id]" value="<?= htmlspecialchars((string)($row['away_team_id'] ?? '')) ?>">
                                        <input type="hidden" name="rows[<?= (int)$index ?>][competition_id]" value="<?= htmlspecialchars((string)($row['competition_id'] ?? '')) ?>">
                                        <input type="hidden" name="rows[<?= (int)$index ?>][source_url]" value="<?= htmlspecialchars((string)($row['source_url'] ?? '')) ?>">
                                        <input type="hidden" name="rows[<?= (int)$index ?>][deleted]" value="<?= $deleted ? '1' : '0' ?>" data-import-delete-input="<?= (int)$index ?>">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <div class="flex flex-wrap gap-3">
                <button type="submit" class="inline-block rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 transition">Save Import</button>
                <a href="<?= htmlspecialchars($base) ?>/league-intelligence" class="inline-block rounded-lg border border-slate-500/30 bg-slate-800/60 px-4 py-2 text-slate-200 hover:bg-slate-700 transition">Cancel</a>
            </div>
        </form>
    <?php endif; ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('add-entity-modal');
    const form = document.getElementById('add-entity-form');
    const nameInput = document.getElementById('add-entity-name');
    const typeInput = document.getElementById('add-entity-type');
    const indexInput = document.getElementById('add-entity-index');
    const title = document.getElementById('add-entity-modal-title');
    const errorBadgeClass = 'bg-rose-900/30 border border-rose-700/50 text-rose-300 text-xs px-2 py-0.5 rounded-full';

    const buildRowPayload = (row) => {
        const inputs = row.querySelectorAll('input, select');
        const data = {};
        inputs.forEach(input => {
            if (!input.name || input.disabled) return;
            const match = input.name.match(/^rows\[(\d+)\]\[(.+)\]$/);
            if (match) {
                data[match[2]] = input.type === 'checkbox' ? (input.checked ? '1' : '0') : input.value;
            }
        });

        const formData = new URLSearchParams();
        const csrfInput = document.querySelector('input[name="csrf_token"]');
        if (csrfInput) {
            formData.append('csrf_token', csrfInput.value);
        }
        Object.keys(data).forEach(key => {
            formData.append(`rows[0][${key}]`, data[key]);
        });

        return formData;
    };

    const sendRow = async (row) => {
        const formData = buildRowPayload(row);
        const response = await fetch('/league-intelligence/import/save', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: formData,
        });

        let payload = null;
        try {
            payload = await response.json();
        } catch (e) {}

        if (!response.ok || !payload || payload.success === false) {
            let msg = 'Failed to save row';
            if (payload && payload.error) {
                msg = payload.error;
            }
            throw new Error(msg);
        }

        return payload;
    };

    const clearNoIssues = (row) => {
        const placeholder = row.querySelector('.no-issues');
        if (placeholder) {
            placeholder.remove();
        }
    };

    const markRowSuccess = (row) => {
        row.classList.remove('bg-slate-800/80');
        row.classList.add('bg-emerald-800/60');
        row.dataset.rowReady = '0';
        row.querySelectorAll('input, select, button').forEach(el => { el.disabled = true; });
        const errorArea = row.querySelector('.error-area');
        if (errorArea && !row.querySelector('.imported-badge')) {
            clearNoIssues(row);
            const badge = document.createElement('span');
            badge.className = 'bg-emerald-700/80 border border-emerald-500/40 text-emerald-100 text-xs px-2 py-0.5 rounded-full imported-badge';
            badge.textContent = 'Imported';
            errorArea.appendChild(badge);
        }
        setTimeout(() => {
            row.style.transition = 'opacity 0.5s, height 0.5s, margin 0.5s, padding 0.5s';
            row.style.opacity = 0;
            row.style.height = 0;
            row.style.margin = 0;
            row.style.padding = 0;
            setTimeout(() => row.remove(), 500);
        }, 2000);
    };

    const markRowError = (row, message) => {
        const errorArea = row.querySelector('.error-area');
        if (!errorArea) return;
        clearNoIssues(row);
        let badge = row.querySelector('.import-error-badge');
        if (!badge) {
            badge = document.createElement('span');
            badge.className = `${errorBadgeClass} import-error-badge`;
            errorArea.appendChild(badge);
        }
        badge.textContent = message || 'Failed to save row';
    };

    const acceptRow = async (row, acceptBtn, options = {}) => {
        if (!row || !acceptBtn || acceptBtn.disabled) {
            return { status: 'skipped' };
        }
        const deletedInput = row.querySelector('[data-import-delete-input]');
        if (deletedInput && deletedInput.value === '1') {
            return { status: 'skipped' };
        }
        if (options.requireReady && row.dataset.rowReady !== '1') {
            return { status: 'skipped' };
        }

        acceptBtn.disabled = true;
        try {
            await sendRow(row);
            markRowSuccess(row);
            return { status: 'ok' };
        } catch (err) {
            markRowError(row, err.message);
            acceptBtn.disabled = false;
            return { status: 'error' };
        }
    };

    document.body.addEventListener('click', function (event) {
        const acceptAllBtn = event.target.closest('[data-team-accept-all]');
        if (acceptAllBtn) {
            if (acceptAllBtn.disabled) return;
            const group = acceptAllBtn.closest('[data-team-group]');
            if (!group) return;
            const rows = Array.from(group.querySelectorAll('[data-import-row]'));
            const readyRows = rows.filter(row => row.dataset.rowReady === '1');
            if (readyRows.length === 0) return;

            const originalText = acceptAllBtn.textContent;
            acceptAllBtn.disabled = true;
            acceptAllBtn.textContent = 'Accepting...';

            (async () => {
                for (const row of readyRows) {
                    const rowAcceptBtn = row.querySelector('[data-import-accept]');
                    await acceptRow(row, rowAcceptBtn, { requireReady: true });
                }
                acceptAllBtn.textContent = originalText;
                const remainingReady = group.querySelectorAll('[data-import-row][data-row-ready="1"]').length;
                acceptAllBtn.disabled = remainingReady === 0;
            })();
            return;
        }

        const acceptBtn = event.target.closest('[data-import-accept]');
        if (acceptBtn) {
            const row = acceptBtn.closest('[data-import-row]');
            acceptRow(row, acceptBtn);
            return;
        }

        const deleteBtn = event.target.closest('[data-import-delete]');
        if (deleteBtn) {
            const row = deleteBtn.closest('[data-import-row]');
            if (!row) return;
            const deleteInput = row.querySelector('[data-import-delete-input]');
            if (!deleteInput) return;
            const wasDeleted = deleteInput.value === '1';
            deleteInput.value = wasDeleted ? '0' : '1';
            const isDeleted = deleteInput.value === '1';
            deleteBtn.textContent = isDeleted ? 'Undo' : 'Delete';
            row.classList.toggle('opacity-40', isDeleted);
            row.classList.toggle('bg-slate-900/50', isDeleted);
            row.classList.toggle('border-slate-700/60', isDeleted);
            row.dataset.rowDeleted = isDeleted ? '1' : '0';
            row.dataset.rowReady = (!isDeleted && row.dataset.rowError !== '1') ? '1' : '0';
            const acceptBtn = row.querySelector('[data-import-accept]');
            if (acceptBtn) acceptBtn.disabled = isDeleted;
            return;
        }

        const addBtn = event.target.closest('[data-add-team], [data-add-competition]');
        if (addBtn) {
            if (!modal || !nameInput || !typeInput || !indexInput || !title) return;
            modal.classList.remove('hidden');
            const idx = addBtn.getAttribute('data-add-team') || addBtn.getAttribute('data-add-competition');
            const type = addBtn.hasAttribute('data-add-team') ? 'team' : 'competition';
            const teamType = addBtn.getAttribute('data-team-type') || '';
            typeInput.value = type + (teamType ? '-' + teamType : '');
            indexInput.value = idx;
            let inputSelector = '';
            if (type === 'team') {
                inputSelector = `[name="rows[${idx}][${teamType}_team_name]"]`;
            } else {
                inputSelector = `[name="rows[${idx}][competition_name]"]`;
            }
            const input = document.querySelector(inputSelector);
            nameInput.value = input ? input.value : '';
            title.textContent = 'Add ' + (type === 'team' ? (teamType === 'home' ? 'Home Team' : 'Away Team') : 'Competition');
            setTimeout(() => nameInput.focus(), 100);
            return;
        }

        if (event.target && event.target.id === 'add-entity-cancel') {
            if (modal) modal.classList.add('hidden');
        }
    });

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (!typeInput || !nameInput) return;
            const type = typeInput.value;
            const name = nameInput.value;
            if (type.startsWith('team')) {
                const teamType = type.split('-')[1];
                document.querySelectorAll(`[name$='[${teamType}_team_name]']`).forEach(inp => {
                    inp.value = name;
                    inp.classList.remove('border-rose-700', 'bg-rose-900/30');
                });
            } else if (type === 'competition') {
                document.querySelectorAll(`[name$='[competition_name]']`).forEach(inp => {
                    inp.value = name;
                    inp.classList.remove('border-rose-700', 'bg-rose-900/30');
                });
            }
            if (modal) modal.classList.add('hidden');
        });
    }
});
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../layout.php';
