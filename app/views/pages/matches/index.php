<?php
require_auth();
require_once __DIR__ . '/../../../lib/match_repository.php';
require_once __DIR__ . '/../../../lib/match_permissions.php';

$user = current_user();
$roles = $_SESSION['roles'] ?? [];
$matches = get_matches_for_user($user);
$canManage = can_manage_matches($user, $roles);
$base = base_path();
$canAccessVideoLab = in_array('platform_admin', $roles, true)
          || in_array('club_admin', $roles, true)
          || in_array('analyst', $roles, true);

$success = $_SESSION['match_form_success'] ?? null;
$error = $_SESSION['match_form_error'] ?? null;
unset($_SESSION['match_form_success'], $_SESSION['match_form_error']);

$title = 'Matches';

ob_start();
?>
<div class="matches-top-row">
          <div>
                    <h1 class="mb-1">Matches</h1>
                    <p class="text-muted-alt text-sm mb-0">Matches visible to your club context.</p>
          </div>
          <div class="matches-top-actions">
                    <div class="matches-search">
                              <span class="matches-search-icon"><i class="fa-solid fa-filter"></i></span>
                              <input type="search" placeholder="Search matches..." class="form-control input-dark matches-search-input">
                              <button type="button" class="matches-search-button">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                              </button>
                    </div>
                    <?php if ($canManage): ?>
                              <a href="<?= htmlspecialchars($base) ?>/matches/create" class="btn btn-primary-soft btn-sm">Create match</a>
                    <?php endif; ?>
          </div>
</div>

<?php if ($error): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php elseif ($success): ?>
          <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if (empty($matches)): ?>
          <div class="panel match-panel p-3 text-muted-alt text-sm">No matches yet.</div>
<?php else: ?>
          <div class="matches-grid">
                    <?php foreach ($matches as $match): ?>
                              <?php
                                        $kickoffTs = $match['kickoff_at'] ? strtotime($match['kickoff_at']) : null;
                                        $kickoffDate = $kickoffTs ? date('M j, Y', $kickoffTs) : 'TBD';
                                        $kickoffTime = $kickoffTs ? date('H:i', $kickoffTs) : 'TBD';
                                        $deskUrl = $base . '/matches/' . (int)$match['id'] . '/desk';
                                        $summaryUrl = $base . '/matches/' . (int)$match['id'] . '/summary';
                                        $videoLabUrl = $base . '/video-lab/match/' . (int)$match['id'];
                                        $hasVideo = !empty($match['has_video']);
                                        $canManageMatch = can_manage_match_for_club($user, $roles, (int)$match['club_id']);
                                        $statusText = $match['status'] ?? 'Unknown';
                                        $matchLabel = trim(($match['home_team'] ?? '') . ' vs ' . ($match['away_team'] ?? ''));
                                        $competitionLabel = $match['competition'] ?? 'Competition';
                                        $progressWidth = $hasVideo ? 82 : 32;
                                        $progressLabel = $hasVideo ? 'Video ready' : 'Processing';
                                        $distanceLabel = number_format($progressWidth / 3, 1) . ' km';
                              ?>
                              <article class="match-card">
                                        <div class="match-card-media">
                                                  <div class="match-card-media-overlay">
                                                            <span class="match-card-media-status"><?= htmlspecialchars($statusText) ?></span>
                                                            <span class="match-card-media-competition"><?= htmlspecialchars($competitionLabel) ?></span>
                                                  </div>
                                        </div>
                                        <div class="match-card-body">
                                                  <div class="match-card-heading">
                                                            <div>
                                                                      <h3><?= htmlspecialchars($matchLabel) ?></h3>
                                                                      <p class="match-card-date"><?= htmlspecialchars($kickoffDate) ?> · <?= htmlspecialchars($kickoffTime) ?></p>
                                                            </div>
                                                            <span class="match-card-distance"><?= htmlspecialchars($distanceLabel) ?></span>
                                                  </div>
                                                  <div class="match-card-progress">
                                                            <div class="match-card-progress-track">
                                                                      <div class="match-card-progress-fill" style="width: <?= htmlspecialchars((string)$progressWidth) ?>%;"></div>
                                                            </div>
                                                            <span class="match-card-progress-label"><?= htmlspecialchars($progressLabel) ?></span>
                                                  </div>
                                                  <div class="match-card-action-row">
                                                            <div class="match-card-actions">
                                                                      <a href="<?= htmlspecialchars($deskUrl) ?>" class="btn btn-primary-soft btn-sm">Watch</a>
                                                                      <a href="<?= htmlspecialchars($summaryUrl) ?>" class="btn btn-secondary-soft btn-sm">Stats</a>
                                                            </div>
                                                            <div class="match-card-extra-actions">
                                                                      <?php if ($canAccessVideoLab && $hasVideo): ?>
                                                                                <a href="<?= htmlspecialchars($videoLabUrl) ?>" class="btn-icon btn-icon-secondary btn-sm" aria-label="Open in Video Lab">
                                                                                          <i class="fa-solid fa-film"></i>
                                                                                </a>
                                                                      <?php endif; ?>
                                                                      <?php if ($canManageMatch): ?>
                                                                                <a href="<?= htmlspecialchars($base) ?>/matches/<?= (int)$match['id'] ?>/edit" class="btn-icon btn-icon-secondary btn-sm" aria-label="Edit match">
                                                                                          <i class="fa-solid fa-pen"></i>
                                                                                </a>
                                                                                <form method="post" action="<?= htmlspecialchars($base) ?>/api/matches/<?= (int)$match['id'] ?>/delete" class="match-card-delete" onsubmit="return confirm('Delete this match?');">
                                                                                          <input type="hidden" name="match_id" value="<?= (int)$match['id'] ?>">
                                                                                          <button type="submit" class="btn-icon btn-icon-danger btn-sm" aria-label="Delete match">
                                                                                                    <i class="fa-solid fa-trash"></i>
                                                                                          </button>
                                                                                </form>
                                                                      <?php endif; ?>
                                                            </div>
                                                  </div>
                                        </div>
                              </article>
                    <?php endforeach; ?>
          </div>
<?php endif; ?>
<?php
$content = ob_get_clean();
$footerScripts = '';
require __DIR__ . '/../../layout.php';
