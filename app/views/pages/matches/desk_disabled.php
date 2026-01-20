<?php
require_auth();
require_once __DIR__ . '/../../../lib/match_repository.php';
require_once __DIR__ . '/../../../lib/match_permissions.php';

$user = current_user();
$roles = $_SESSION['roles'] ?? [];

if (!isset($match)) {
    http_response_code(404);
    echo 'Match not found';
    exit;
}

$base = base_path();
$canView = can_view_match($user, $roles, (int)$match['club_id']);
$canManage = in_array('platform_admin', $roles, true) || in_array('club_admin', $roles, true) || in_array('analyst', $roles, true);

if (!$canView && !$canManage) {
    http_response_code(403);
    echo '403 Forbidden';
    exit;
}

$title = 'Match Overview';
$matchId = (int)$match['id'];
$homeTeam = htmlspecialchars($match['home_team'] ?? 'Team A');
$awayTeam = htmlspecialchars($match['away_team'] ?? 'Team B');
$status = htmlspecialchars($match['status'] ?? 'draft');

ob_start();
?>
<div class="container mt-4">
    <div class="panel p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="mb-2"><?= $homeTeam ?> vs <?= $awayTeam ?></h1>
                <p class="text-muted-alt text-sm mb-0">Match Overview</p>
            </div>
            <div>
                <span class="badge bg-secondary px-3 py-2">Analysis Disabled</span>
            </div>
        </div>

        <div class="alert alert-info mt-3" role="status">
            <strong>Analysis temporarily disabled</strong><br>
            The video analysis desk is currently unavailable. The match details below remain accessible.
        </div>

        <div class="row g-3 mt-4">
            <div class="col-12 col-md-6">
                <div class="panel-secondary p-3">
                    <h5 class="mb-3">Match Information</h5>
                    <dl class="match-info">
                        <dt>Status</dt>
                        <dd>
                            <span class="badge bg-secondary"><?= ucfirst($status) ?></span>
                        </dd>
                        <?php if (!empty($match['kickoff_at'])): ?>
                            <dt>Kickoff</dt>
                            <dd><?= htmlspecialchars(date('M j, Y \a\t H:i', strtotime($match['kickoff_at']))) ?></dd>
                        <?php endif; ?>
                        <?php if (!empty($match['venue'])): ?>
                            <dt>Venue</dt>
                            <dd><?= htmlspecialchars($match['venue']) ?></dd>
                        <?php endif; ?>
                        <?php if (!empty($match['competition'])): ?>
                            <dt>Competition</dt>
                            <dd><?= htmlspecialchars($match['competition']) ?></dd>
                        <?php endif; ?>
                    </dl>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="panel-secondary p-3">
                    <h5 class="mb-3">Actions</h5>
                    <a href="<?= htmlspecialchars($base) ?>/matches/<?= $matchId ?>/lineup" class="btn btn-secondary btn-sm mb-2">View Lineup</a>
                    <a href="<?= htmlspecialchars($base) ?>/matches/<?= $matchId ?>/stats" class="btn btn-secondary btn-sm mb-2">View Stats</a>
                    <?php if (in_array('platform_admin', $roles, true) || in_array('club_admin', $roles, true) || in_array('analyst', $roles, true)): ?>
                        <a href="<?= htmlspecialchars($base) ?>/matches/<?= $matchId ?>/edit" class="btn btn-secondary btn-sm">Edit Match</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <a href="<?= htmlspecialchars($base) ?>/matches" class="btn btn-outline-light btn-sm">Back to Matches</a>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../layout.php';
?>
