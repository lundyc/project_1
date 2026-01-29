<?php
$base = base_path();
$config = require __DIR__ . '/../../../config/config.php';
$appName = $config['app']['name'] ?? 'Analytics Desk';

$user = current_user() ?? [];
$roles = $_SESSION['roles'] ?? [];
$isPlatformAdmin = in_array('platform_admin', $roles, true);
$isClubAdmin = in_array('club_admin', $roles, true);
$canViewPlayers = $isPlatformAdmin || $isClubAdmin;

$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$navBase = $base ?: '';
$dashboardHref = $navBase . '/';
$matchesHref = $navBase . '/matches';
$statsHref = $navBase . '/stats';
$playersHref = $navBase . '/admin/players';
$adminHref = $navBase . '/admin';
$settingsHref = $navBase . '/settings';
$logoutHref = $navBase . '/logout';
$leagueHref = $navBase . '/league-intelligence';

$dashboardActive = $currentPath === $dashboardHref || $currentPath === rtrim($navBase, '/');
$matchesActive = str_starts_with($currentPath, $matchesHref);
$statsActive = str_starts_with($currentPath, $statsHref);
$playersActive = str_starts_with($currentPath, $playersHref);
$adminActive = str_starts_with($currentPath, $adminHref) && !$playersActive;
$leagueActive = str_starts_with($currentPath, $leagueHref);

$displayName = trim($user['display_name'] ?? 'User');
$displayEmail = $user['email'] ?? '';
$nameParts = preg_split('/\s+/', $displayName, -1, PREG_SPLIT_NO_EMPTY);
$initials = '';
foreach ($nameParts as $part) {
          $initials .= strtoupper(function_exists('mb_substr') ? mb_substr($part, 0, 1) : substr($part, 0, 1));
          if (strlen($initials) >= 2) {
                    break;
          }
}
$initials = $initials ?: 'U';

/*
 * Primary navigation items for all logged-in users.
 * Adding Stats here ensures the section renders alongside Dashboard/Matches.
 */
$primaryNavLinks = [
          [
                    'label' => 'Dashboard',
                    'href' => $dashboardHref,
                    'active' => $dashboardActive,
          ],
          [
                    'label' => 'Matches',
                    'href' => $matchesHref,
                    'active' => $matchesActive,
          ],
          [
                    'label' => 'Stats',
                    'href' => $statsHref,
                    'active' => $statsActive,
          ],
];

if ($isPlatformAdmin) {
           $primaryNavLinks[] = [
                     'label' => 'League',
                     'href' => $leagueHref,
                     'active' => $leagueActive,
           ];
}

$navLinks = $primaryNavLinks;

if ($canViewPlayers) {
          $navLinks[] = [
                    'label' => 'Players',
                    'href' => $playersHref,
                    'active' => $playersActive,
          ];
}

if ($isPlatformAdmin) {
          $navLinks[] = [
                    'label' => 'Admin',
                    'href' => $adminHref,
                    'active' => $adminActive,
          ];
}
?>

<nav class="top-nav" aria-label="Primary navigation">
          <div class="top-nav__inner">
                    <div class="top-nav__brand">
                              <img src="<?= htmlspecialchars($base) ?>/assets/img/logo.png" alt="<?= htmlspecialchars($appName) ?> logo" class="top-nav__logo" width="36" height="36">
                              <div class="top-nav__brand-text">
                                        <span class="top-nav__brand-name"><?= htmlspecialchars($appName) ?></span>
                                        <?php if (!empty($title)): ?>
                                                  <span class="top-nav__section"></span>
                                        <?php endif; ?>
                              </div>
                    </div>

                    <div class="top-nav__links-wrapper">
                              <div class="top-nav__links">
                                        <?php foreach ($navLinks as $item): ?>
                                                  <a href="<?= htmlspecialchars($item['href']) ?>" class="top-nav__link <?= $item['active'] ? 'is-active' : '' ?>">
                                                            <?= htmlspecialchars($item['label']) ?>
                                                  </a>
                                        <?php endforeach; ?>
                              </div>
                    </div>

                    <div class="top-nav__user">
                        <div class="top-nav__user-meta">
                            <span class="top-nav__user-name"><?= htmlspecialchars($displayName) ?></span>
                            <?php if ($isPlatformAdmin): ?>
                                <span class="block text-xs text-slate-400 mt-0.5"><?= htmlspecialchars($clubContextName ?? 'Club') ?></span>
                            <?php endif; ?>
                            <?php if ($displayEmail): ?>
                                <span class="top-nav__user-email"><?= htmlspecialchars($displayEmail) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="top-nav__user-actions">
                            <?php if ($isPlatformAdmin): ?>
                                <div class="relative inline-block text-left ml-2">
                                    <button type="button" class="top-nav__user-link" id="club-switcher-btn" aria-haspopup="true" aria-expanded="false">
                                        <span class="font-semibold">Club</span>
                                        <svg class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                    <div id="club-switcher-dropdown" class="hidden absolute right-0 z-10 mt-2 w-48 rounded-md shadow-lg bg-slate-900 ring-1 ring-black ring-opacity-5 focus:outline-none">
                                        <form method="get" id="club-context-form-nav" class="py-1">
                                            <label class="block px-4 py-2 text-xs text-slate-400">Change Club</label>
                                            <?php
                                            require_once __DIR__ . '/../../../app/lib/club_repository.php';
                                            $availableClubs = get_all_clubs();
                                            $selectedClubId = $_SESSION['stats_club_id'] ?? ($availableClubs[0]['id'] ?? 1);
                                            foreach ($availableClubs as $club): ?>
                                                <button type="submit" name="club_id" value="<?= (int)$club['id'] ?>" class="block w-full text-left px-4 py-2 text-sm <?= ((int)$club['id'] === (int)$selectedClubId) ? 'bg-indigo-600 text-white font-semibold' : 'text-slate-200 hover:bg-slate-800' ?>">
                                                    <?= htmlspecialchars($club['name']) ?><?= ((int)$club['id'] === (int)$selectedClubId) ? ' (current)' : '' ?>
                                                </button>
                                            <?php endforeach; ?>
                                        </form>
                                    </div>
                                </div>
                                <script>
                                // Simple dropdown toggle
                                document.addEventListener('DOMContentLoaded', function() {
                                    var btn = document.getElementById('club-switcher-btn');
                                    var dropdown = document.getElementById('club-switcher-dropdown');
                                    if (btn && dropdown) {
                                        btn.addEventListener('click', function(e) {
                                            e.preventDefault();
                                            dropdown.classList.toggle('hidden');
                                        });
                                        document.addEventListener('click', function(e) {
                                            if (!btn.contains(e.target) && !dropdown.contains(e.target)) {
                                                dropdown.classList.add('hidden');
                                            }
                                        });
                                    }
                                });
                                </script>
                            <?php endif; ?>
                            <span aria-hidden="true" class="top-nav__user-separator">·</span>
                            <a href="<?= htmlspecialchars($logoutHref) ?>" class="top-nav__user-link">Logout</a>
                        </div>
                    </div>
          </div>
</nav>