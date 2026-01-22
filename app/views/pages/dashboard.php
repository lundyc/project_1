
<?php
require_auth();

$title = 'Dashboard';

ob_start();
?>
<?php
    $pageTitle = 'Dashboard';
    $pageDescription = 'Welcome to your club dashboard.';
    $availableClubs = $availableClubs ?? [];
    $selectedClubId = $selectedClubId ?? 1;
    $selectedClub = null;
    foreach ($availableClubs as $club) {
        if ((int)$club['id'] === (int)$selectedClubId) {
            $selectedClub = $club;
            break;
        }
    }
    $clubContextName = $selectedClub['name'] ?? 'Saltcoats Victoria F.C.';
    include __DIR__ . '/../partials/club_context_header.php';
?>

<div class="grid grid-cols-12 gap-2 px-4 md:px-6 lg:px-8 w-full">
    <!-- Left: Quick Links or Profile -->
    <aside class="col-span-2 space-y-4 min-w-0">
        <div class="rounded-xl border border-white/10 bg-slate-800/40 p-4">
            <div class="font-semibold text-slate-200 mb-2">Quick Links</div>
            <ul class="space-y-2 text-sm">
                <li><a href="<?= base_path() ?>/matches" class="hover:underline text-indigo-400">Matches</a></li>
                <li><a href="<?= base_path() ?>/stats" class="hover:underline text-indigo-400">Statistics</a></li>
                <li><a href="<?= base_path() ?>/admin/players" class="hover:underline text-indigo-400">Players</a></li>
                <li><a href="<?= base_path() ?>/admin/teams" class="hover:underline text-indigo-400">Teams</a></li>
            </ul>
        </div>
        <div class="rounded-xl border border-white/10 bg-slate-800/40 p-4 mt-4">
            <div class="font-semibold text-slate-200 mb-2">Profile</div>
            <div class="text-slate-300 text-sm mb-1">Logged in as <strong><?= htmlspecialchars(current_user()['display_name']) ?></strong></div>
            <a href="<?= base_path() ?>/logout" class="inline-flex items-center rounded-lg px-3 py-1.5 text-xs border border-gray-300 bg-transparent text-white hover:bg-gray-700 transition mt-2">Logout</a>
        </div>
    </aside>
    <!-- Main: Welcome and Activity -->
    <main class="col-span-7 space-y-4 min-w-0">
        <div class="rounded-xl border border-white/10 bg-slate-800/40 p-6 mb-4">
            <h2 class="text-xl font-bold text-slate-100 mb-2">Welcome, <?= htmlspecialchars(current_user()['display_name']) ?>!</h2>
            <p class="text-slate-400 mb-2">This is your club's main dashboard. Use the links on the left to quickly access matches, stats, and admin features.</p>
            <p class="text-slate-400">Recent activity and important updates will appear here.</p>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div class="rounded-lg border border-white/10 bg-slate-900/60 p-4">
                <div class="text-xs text-slate-400 mb-1">Recent Matches</div>
                <div class="text-slate-200 font-semibold">No recent matches</div>
            </div>
            <div class="rounded-lg border border-white/10 bg-slate-900/60 p-4">
                <div class="text-xs text-slate-400 mb-1">Upcoming Events</div>
                <div class="text-slate-200 font-semibold">No upcoming events</div>
            </div>
        </div>
    </main>
    <!-- Right: Stats or Announcements -->
    <aside class="col-span-3 min-w-0">
        <div class="rounded-xl bg-slate-900/80 border border-white/10 p-4 mb-4">
            <h5 class="text-slate-200 font-semibold mb-1">Club Stats</h5>
            <div class="text-slate-400 text-xs mb-4">Overview</div>
            <div class="space-y-3">
                <article class="rounded-lg border border-white/10 bg-slate-800/40 px-3 py-3">
                    <div class="text-xs font-semibold text-slate-300 mb-2 text-center">Total Matches</div>
                    <div class="text-2xl font-bold text-slate-100 text-center">—</div>
                </article>
                <article class="rounded-lg border border-white/10 bg-slate-800/40 px-3 py-3">
                    <div class="text-xs font-semibold text-slate-300 mb-2 text-center">Total Players</div>
                    <div class="text-2xl font-bold text-slate-100 text-center">—</div>
                </article>
            </div>
        </div>
        <div class="rounded-xl bg-slate-800/40 border border-white/10 p-4">
            <h5 class="text-slate-200 font-semibold mb-1">Announcements</h5>
            <div class="text-slate-400 text-xs">No announcements at this time.</div>
        </div>
    </aside>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
