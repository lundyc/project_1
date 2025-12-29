<?php
$base = base_path();
$roles = $_SESSION['roles'] ?? [];
$isPlatformAdmin = in_array('platform_admin', $roles, true);
$isClubAdmin = in_array('club_admin', $roles, true);
$canViewPlayers = $isPlatformAdmin || $isClubAdmin;
$canAccessVideoLab = in_array('analyst', $roles, true) || $isClubAdmin || $isPlatformAdmin;
$current = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$videoLabActive = str_starts_with($current, $base . '/video-lab');
?>

<nav class="sidebar-thin d-flex flex-column align-items-center">
          <div class="nav-brand text-center mt-3 mb-4">
                    <img src="<?= htmlspecialchars($base) ?>/assets/img/logo.png" alt="Logo" class="brand-logo mb-1" width="40" height="40">
          </div>

          <div class="nav-items d-flex flex-column gap-3">
                    <a href="<?= htmlspecialchars($base) ?>/" class="nav-icon <?= $current === $base . '/' || $current === $base ? 'active' : '' ?>">
                              <span class="icon-box">
                                        <i class="fa-solid fa-house"></i>
                              </span>
                              <span class="icon-label">Dashboard</span>
                    </a>
                    <a href="<?= htmlspecialchars($base) ?>/matches" class="nav-icon <?= str_starts_with($current, $base . '/matches') ? 'active' : '' ?>">
                              <span class="icon-box">
                                        <i class="fa-solid fa-play-circle"></i>
                              </span>
                              <span class="icon-label">Matches</span>
                    </a>
                    <?php if ($canViewPlayers): ?>
                              <a href="<?= htmlspecialchars($base) ?>/admin/players" class="nav-icon <?= str_starts_with($current, $base . '/admin/players') ? 'active' : '' ?>">
                                        <span class="icon-box">
                                                  <i class="fa-solid fa-shirt"></i>
                                        </span>
                                        <span class="icon-label">Players</span>
                              </a>
                    <?php endif; ?>
                    <?php if ($canAccessVideoLab): ?>
                              <a href="<?= htmlspecialchars($base) ?>/video-lab" class="nav-icon <?= $videoLabActive ? 'active' : '' ?>">
                                        <span class="icon-box">
                                                  <i class="fa-solid fa-film"></i>
                                        </span>
                                        <span class="icon-label">Video Lab</span>
                              </a>
                    <?php endif; ?>

                    <?php if ($isPlatformAdmin): ?>
                              <a href="<?= htmlspecialchars($base) ?>/admin" class="nav-icon <?= str_starts_with($current, $base . '/admin') ? 'active' : '' ?>">
                                        <span class="icon-box">
                                                  <i class="fa-solid fa-gear"></i>
                                        </span>
                                        <span class="icon-label">Admin</span>
                              </a>
                    <?php endif; ?>
          </div>
          <?php /* Video Lab moved into main nav items above; experimental block removed */ ?>
</nav>
