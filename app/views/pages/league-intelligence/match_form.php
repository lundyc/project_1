<?php
// League Intelligence Match Add/Edit Form
// $match is set for edit, not set for add
$editing = isset($match);
?>
<div class="max-w-lg mx-auto mt-8 bg-slate-900 p-6 rounded-xl border border-slate-700">
    <h2 class="text-xl font-semibold text-white mb-4"><?= $editing ? 'Edit' : 'Add' ?> Match</h2>
    <form method="post" action="<?= $editing ? '/league-intelligence/matches/edit/' . urlencode($match['match_id']) : '/league-intelligence/matches/add' ?>">
        <div class="mb-3">
            <label class="block text-slate-300 text-xs mb-1">Kickoff Date/Time</label>
            <input type="datetime-local" name="kickoff_at" class="w-full rounded-md border border-slate-600 bg-slate-800 text-slate-100 px-2 py-1 text-xs" value="<?= $editing ? htmlspecialchars(date('Y-m-d\TH:i', strtotime($match['kickoff_at']))) : '' ?>" required>
        </div>
        <div class="mb-3 flex gap-2">
            <div class="flex-1">
                <label class="block text-slate-300 text-xs mb-1">Home Team ID</label>
                <input type="number" name="home_team_id" class="w-full rounded-md border border-slate-600 bg-slate-800 text-slate-100 px-2 py-1 text-xs" value="<?= $editing ? htmlspecialchars($match['home_team_id']) : '' ?>" required>
            </div>
            <div class="flex-1">
                <label class="block text-slate-300 text-xs mb-1">Away Team ID</label>
                <input type="number" name="away_team_id" class="w-full rounded-md border border-slate-600 bg-slate-800 text-slate-100 px-2 py-1 text-xs" value="<?= $editing ? htmlspecialchars($match['away_team_id']) : '' ?>" required>
            </div>
        </div>
        <div class="mb-3 flex gap-2">
            <div class="flex-1">
                <label class="block text-slate-300 text-xs mb-1">Home Goals</label>
                <input type="number" name="home_goals" class="w-full rounded-md border border-slate-600 bg-slate-800 text-slate-100 px-2 py-1 text-xs" value="<?= $editing ? htmlspecialchars($match['home_goals']) : '' ?>">
            </div>
            <div class="flex-1">
                <label class="block text-slate-300 text-xs mb-1">Away Goals</label>
                <input type="number" name="away_goals" class="w-full rounded-md border border-slate-600 bg-slate-800 text-slate-100 px-2 py-1 text-xs" value="<?= $editing ? htmlspecialchars($match['away_goals']) : '' ?>">
            </div>
        </div>
        <div class="mb-3">
            <label class="block text-slate-300 text-xs mb-1">Status</label>
            <select name="status" class="w-full rounded-md border border-slate-600 bg-slate-800 text-slate-100 px-2 py-1 text-xs">
                <option value="scheduled" <?= $editing && $match['status'] === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                <option value="completed" <?= $editing && $match['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                <option value="cancelled" <?= $editing && $match['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
        </div>
        <div class="mb-3 flex gap-2">
            <div class="flex-1">
                <label class="block text-slate-300 text-xs mb-1">Competition ID</label>
                <input type="number" name="competition_id" class="w-full rounded-md border border-slate-600 bg-slate-800 text-slate-100 px-2 py-1 text-xs" value="<?= $editing ? htmlspecialchars($match['competition_id']) : '' ?>">
            </div>
            <div class="flex-1">
                <label class="block text-slate-300 text-xs mb-1">Season ID</label>
                <input type="number" name="season_id" class="w-full rounded-md border border-slate-600 bg-slate-800 text-slate-100 px-2 py-1 text-xs" value="<?= $editing ? htmlspecialchars($match['season_id']) : '' ?>">
            </div>
        </div>
        <div class="mb-4">
            <label class="inline-flex items-center text-xs text-slate-300">
                <input type="checkbox" name="neutral_location" value="1" class="mr-2" <?= $editing && !empty($match['neutral_location']) ? 'checked' : '' ?>> Neutral Location
            </label>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="rounded bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 text-sm">Save</button>
            <a href="/league-intelligence/matches" class="rounded bg-slate-700 hover:bg-slate-800 text-white px-4 py-2 text-sm">Cancel</a>
        </div>
    </form>
</div>
