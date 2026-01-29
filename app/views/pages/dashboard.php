
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

<div class="dashboard-grid">
    <!-- Left: Quick Links or Profile -->
    <aside class="dashboard-sidebar">
        <div class="rounded-xl border border-border-soft bg-bg-secondary p-5 shadow">
            <div class="font-semibold text-base mb-2">Quick Links</div>
            <ul class="dashboard-links">
                <li><a href="<?= base_path() ?>/matches" class="dashboard-link dashboard-link--accent">Matches</a></li>
                <li><a href="<?= base_path() ?>/stats" class="dashboard-link dashboard-link--accent">Statistics</a></li>
                <li><a href="<?= base_path() ?>/admin/players" class="dashboard-link dashboard-link--accent">Players</a></li>
                <li><a href="<?= base_path() ?>/admin/teams" class="dashboard-link dashboard-link--accent">Teams</a></li>
            </ul>
        </div>
        <div class="rounded-xl border border-border-soft bg-bg-secondary p-5 shadow mt-4">
            <div class="font-semibold text-base mb-2">Profile</div>
            <div class="text-sm mb-2">Logged in as <strong><?= htmlspecialchars(current_user()['display_name']) ?></strong></div>
            <a href="<?= base_path() ?>/logout" class="btn btn-outline-gold btn-xs mt-2">Logout</a>
        </div>
    </aside>
    <!-- Main: Welcome and Activity -->
    <main class="dashboard-main">
        <div class="rounded-xl border border-border-soft bg-bg-secondary p-5 shadow mb-4">
            <h2 class="font-semibold text-lg mb-2">Welcome, <?= htmlspecialchars(current_user()['display_name']) ?>!</h2>
            <p class="text-sm mb-2">This is your club's main dashboard. Use the links on the left to quickly access matches, stats, and admin features.</p>
            <p class="text-sm">Recent activity and important updates will appear here.</p>
        </div>
        <div class="dashboard-cards grid gap-4">
            <div class="rounded-xl border border-border-soft bg-bg-secondary p-5 shadow">
                <div class="text-sm font-semibold mb-1">Recent Matches</div>
                <div class="text-base">No recent matches</div>
            </div>
            <div class="rounded-xl border border-border-soft bg-bg-secondary p-5 shadow">
                <div class="text-sm font-semibold mb-1">Upcoming Events</div>
                <div class="text-base">No upcoming events</div>
            </div>
        </div>
    </main>
    <!-- Right: Stats or Announcements -->
    <aside class="dashboard-right">
        <div class="rounded-xl border border-border-soft bg-bg-secondary p-5 shadow mb-4">
            <h5 class="font-semibold text-base mb-1">Club Stats</h5>
            <div class="text-sm font-semibold mb-4">Overview</div>
            <div class="space-y-3">
                <article class="rounded-xl border border-border-soft bg-bg-secondary p-5 shadow">
                    <div class="text-sm font-semibold mb-2 text-center">Total Matches</div>
                    <div class="text-base text-center">—</div>
                </article>
                <article class="rounded-xl border border-border-soft bg-bg-secondary p-5 shadow">
                    <div class="text-sm font-semibold mb-2 text-center">Total Players</div>
                    <div class="text-base text-center">—</div>
                </article>
            </div>
        </div>
        <div class="rounded-xl border border-border-soft bg-bg-secondary p-5 shadow">
            <h5 class="font-semibold text-base mb-1">Announcements</h5>
            <div class="text-sm font-semibold">No announcements at this time.</div>
        </div>
    </aside>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
