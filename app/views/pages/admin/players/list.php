<?php
require_once __DIR__ . '/../../../../lib/auth.php';
require_once __DIR__ . '/../../../../lib/player_repository.php';
require_once __DIR__ . '/../../../../lib/team_repository.php';
require_once __DIR__ . '/../../../../lib/season_repository.php';
require_once __DIR__ . '/../../../../lib/club_repository.php';

$context = require_club_admin_access();
$user = $context['user'];
$clubId = $context['club_id'];
$isPlatformAdmin = in_array('platform_admin', $context['roles'], true);
$clubs = $isPlatformAdmin ? get_all_clubs() : [];

$filters = [
          'active' => $_GET['active'] ?? '',
          'team_id' => $_GET['team_id'] ?? '',
          'season_id' => $_GET['season_id'] ?? '',
          'club_id' => $isPlatformAdmin ? (isset($_GET['club_id']) ? (int)$_GET['club_id'] : $clubId) : $clubId,
];

$teams = get_teams_by_club($clubId);
$seasons = get_seasons_by_club($clubId);
$players = get_players_for_club($clubId, $filters);

$success = $_SESSION['player_flash_success'] ?? null;
$error = $_SESSION['player_flash_error'] ?? null;
unset($_SESSION['player_flash_success'], $_SESSION['player_flash_error']);

$title = 'Players';
$base = base_path();

ob_start();
?>
<div class="d-flex align-items-center justify-content-between mb-4">
          <div>
                    <h1 class="mb-1">Players</h1>
                    <p class="text-muted-alt text-sm mb-0">Manage club-wide players, positions, and assignments.</p>
          </div>
          <a href="<?= htmlspecialchars($base) ?>/admin/players/create" class="btn btn-primary-soft btn-sm">Create Player</a>
</div>

<form method="get" class="row g-3 mb-4">
          <?php if ($isPlatformAdmin): ?>
                    <div class="col-sm-4">
                              <label class="form-label">Club</label>
                              <select name="club_id" class="form-select select-dark">
                                        <?php foreach ($clubs as $club): ?>
                                                  <option value="<?= (int)$club['id'] ?>" <?= (int)$filters['club_id'] === (int)$club['id'] ? 'selected' : '' ?>><?= htmlspecialchars($club['name']) ?></option>
                                        <?php endforeach; ?>
                              </select>
                    </div>
          <?php endif; ?>
          <div class="col-sm-4">
                    <label class="form-label">Status</label>
                    <select name="active" class="form-select select-dark">
                              <option value="">All players</option>
                              <option value="1" <?= $filters['active'] === '1' ? 'selected' : '' ?>>Active</option>
                              <option value="0" <?= $filters['active'] === '0' ? 'selected' : '' ?>>Inactive</option>
                    </select>
          </div>
          <div class="col-sm-4">
                    <label class="form-label">Team</label>
                    <select name="team_id" class="form-select select-dark">
                              <option value="">All teams</option>
                              <?php foreach ($teams as $team): ?>
                                        <option value="<?= (int)$team['id'] ?>" <?= $filters['team_id'] === (string)$team['id'] ? 'selected' : '' ?>><?= htmlspecialchars($team['name']) ?></option>
                              <?php endforeach; ?>
                    </select>
          </div>
          <div class="col-sm-4">
                    <label class="form-label">Season</label>
                    <select name="season_id" class="form-select select-dark">
                              <option value="">All seasons</option>
                              <?php foreach ($seasons as $season): ?>
                                        <option value="<?= (int)$season['id'] ?>" <?= $filters['season_id'] === (string)$season['id'] ? 'selected' : '' ?>><?= htmlspecialchars($season['name']) ?></option>
                              <?php endforeach; ?>
                    </select>
          </div>
          <div class="col-sm-12 text-end">
                    <button type="submit" class="btn btn-secondary-soft btn-sm">Filter</button>
          </div>
</form>

<?php if ($error): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php elseif ($success): ?>
          <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if (empty($players)): ?>
          <div class="panel p-3 text-muted-alt text-sm">No players added yet.</div>
<?php else: ?>
          <div class="match-table-wrap">
                    <table class="match-table mb-0">
                              <thead class="match-table-head">
                                        <tr>
                                                  <th>Name</th>
                                                  <th>Position</th>
                                                  <th>Team</th>
                                                  <th>Active</th>
                                                  <th class="text-end">Actions</th>
                                        </tr>
                              </thead>
                              <tbody>
                                        <?php foreach ($players as $player): ?>
                                                  <tr>
                                                              <td><?= htmlspecialchars($player['display_name']) ?></td>
                                                              <td><?= htmlspecialchars($player['primary_position'] ?? '—') ?></td>
                                                              <td><?= htmlspecialchars($player['team_name'] ?? '—') ?></td>
                                                              <td><?= $player['is_active'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>' ?></td>
                                                              <td class="text-end">
                                                                        <div class="d-flex justify-content-end gap-2">
                                                                                  <a href="<?= htmlspecialchars($base) ?>/admin/players/<?= (int)$player['id'] ?>" class="btn-icon btn-icon-secondary" aria-label="View player">
                                                                                            <i class="fa-solid fa-eye"></i>
                                                                                  </a>
                                                                                  <a href="<?= htmlspecialchars($base) ?>/admin/players/<?= (int)$player['id'] ?>/edit" class="btn-icon btn-icon-secondary" aria-label="Edit player">
                                                                                            <i class="fa-solid fa-pen"></i>
                                                                                  </a>
                                                                                  <form method="post" action="<?= htmlspecialchars($base) ?>/admin/players/<?= (int)$player['id'] ?>/delete" class="d-inline" onsubmit="return confirm('Mark this player as inactive?');">
                                                                                            <button type="submit" class="btn-icon btn-icon-danger" aria-label="Delete player">
                                                                                                      <i class="fa-solid fa-trash"></i>
                                                                                            </button>
                                                                                  </form>
                                                                        </div>
                                                              </td>
                                                  </tr>
                                        <?php endforeach; ?>
                              </tbody>
                    </table>
          </div>
<?php endif; ?>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../../layout.php';