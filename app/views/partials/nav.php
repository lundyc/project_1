<?php
$base = base_path();
$config = require __DIR__ . '/../../../config/config.php';
$appName = $config['app']['name'] ?? 'Analytics Desk';

$user = current_user() ?? [];
$roles = $_SESSION['roles'] ?? [];
$isPlatformAdmin = in_array('platform_admin', $roles, true);
$isClubAdmin = in_array('club_admin', $roles, true);
$canViewPlayers = $isPlatformAdmin || $isClubAdmin;
$canAccessVideoLab = in_array('analyst', $roles, true) || $isClubAdmin || $isPlatformAdmin;

$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$navBase = $base ?: '';
$dashboardHref = $navBase . '/';
$matchesHref = $navBase . '/matches';
$playersHref = $navBase . '/admin/players';
$videoLabHref = $navBase . '/video-lab';
$adminHref = $navBase . '/admin';
$settingsHref = $navBase . '/settings';
$logoutHref = $navBase . '/logout';

$dashboardActive = $currentPath === $dashboardHref || $currentPath === rtrim($navBase, '/');
$matchesActive = str_starts_with($currentPath, $matchesHref);
$playersActive = str_starts_with($currentPath, $playersHref);
$videoLabActive = str_starts_with($currentPath, $videoLabHref);
$adminActive = str_starts_with($currentPath, $adminHref) && !$playersActive;

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

$navLinks = [
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
];

if ($canViewPlayers) {
          $navLinks[] = [
                    'label' => 'Players',
                    'href' => $playersHref,
                    'active' => $playersActive,
          ];
}

if ($canAccessVideoLab) {
          $navLinks[] = [
                    'label' => 'Video Lab',
                    'href' => $videoLabHref,
                    'active' => $videoLabActive,
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
                                                  <span class="top-nav__section"><?= htmlspecialchars($title) ?></span>
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
                              <span class="top-nav__avatar"><?= htmlspecialchars($initials) ?></span>
                              <div class="top-nav__user-meta">
                                        <span class="top-nav__user-name"><?= htmlspecialchars($displayName) ?></span>
                                        <?php if ($displayEmail): ?>
                                                  <span class="top-nav__user-email"><?= htmlspecialchars($displayEmail) ?></span>
                                        <?php endif; ?>
                              </div>
                              <div class="top-nav__user-actions">
                                        <a href="<?= htmlspecialchars($settingsHref) ?>" class="top-nav__user-link">Settings</a>
                                        <span aria-hidden="true" class="top-nav__user-separator">·</span>
                                        <a href="<?= htmlspecialchars($logoutHref) ?>" class="top-nav__user-link">Logout</a>
                              </div>
                    </div>
          </div>
</nav>
