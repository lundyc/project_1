<?php
// Usage: include this partial and set $pageTitle, $pageDescription, $clubContextName, $showClubSelector, $availableClubs, $selectedClubId
?>
<header class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 px-4 md:px-6 mb-6">
    <div>
        <h1 class="text-2xl md:text-3xl font-bold tracking-tight"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></h1>
        <p class="text-slate-400 text-sm"><?= htmlspecialchars($pageDescription ?? '') ?></p>
    </div>
    <div class="flex items-center gap-3">
        <p class="text-slate-400 text-xs">
            Viewing for: <span class="font-semibold text-slate-200"><?= htmlspecialchars($clubContextName ?? 'Saltcoats Victoria F.C.') ?></span>.
        </p>
        <div>
            <form method="get" id="club-context-form">
                <label for="club-context-selector" class="block text-slate-400 text-xs mb-1">Switch club context</label>
                <select name="club_id" id="club-context-selector" class="block w-48 rounded-md bg-slate-900/60 border border-white/20 px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-white/30" onchange="this.form.submit()">
                    <?php if (!empty($availableClubs)): ?>
                        <?php foreach ($availableClubs as $club): ?>
                            <option value="<?= (int)$club['id'] ?>" <?= (isset($selectedClubId) && (int)$club['id'] === (int)$selectedClubId ? 'selected' : '') ?>>
                                <?= htmlspecialchars($club['name'] ?? 'Club') ?>
                            </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="1" selected>Saltcoats Victoria F.C.</option>
                        <option value="2">Test</option>
                    <?php endif; ?>
                </select>
            </form>
        </div>
    </div>
</header>
