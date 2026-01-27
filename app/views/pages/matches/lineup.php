<?php
require_auth();
require_once __DIR__ . '/../../../lib/match_permissions.php';
require_once __DIR__ . '/../../../lib/team_repository.php';
require_once __DIR__ . '/../../../lib/formation_repository.php';
require_once __DIR__ . '/../../../lib/asset_helper.php';

if (!isset($match) || !is_array($match)) {
          http_response_code(404);
          echo 'Match not found';
          exit;
}

$user = current_user();
$roles = $_SESSION['roles'] ?? [];
$clubId = (int)($match['club_id'] ?? 0);
$matchId = (int)$match['id'];

if (!can_view_match($user, $roles, $clubId)) {
          http_response_code(403);
          echo '403 Forbidden';
          exit;
}

$base = base_path();
$canEditRoles = in_array('platform_admin', $roles, true)
          || in_array('club_admin', $roles, true)
          || in_array('analyst', $roles, true);
$canManage = $canEditRoles && can_manage_match_for_club($user, $roles, $clubId);

$teams = get_teams_by_club($clubId);
$homeTeamName = $match['home_team'] ?? 'Home team';
$awayTeamName = $match['away_team'] ?? 'Away team';

$homeFormation = $homeFormation ?? null;
$awayFormation = $awayFormation ?? null;
$homeFormationLabel = $homeFormation['label'] ?? 'Unset';
$awayFormationLabel = $awayFormation['label'] ?? 'Unset';

$formations = get_formations_with_positions([
          'format' => '11-a-side',
          'is_fixed' => 1,
]);

$lineupConfig = [
          'basePath' => $base,
          'clubId' => $clubId,
          'matchId' => $matchId,
          'homeTeamId' => (int)($match['home_team_id'] ?? 0),
          'awayTeamId' => (int)($match['away_team_id'] ?? 0),
          'matchPlayers' => [
                    'list' => $base . '/api/match-players/list',
                    'add' => $base . '/api/match-players/add',
                    'update' => $base . '/api/match-players/update',
                    'delete' => $base . '/api/match-players/delete',
          ],
          'matchSubstitutions' => [
                    'list' => $base . '/api/match-substitutions/list',
                    'create' => $base . '/api/matches/{match_id}/substitutions',
          ],
          'players' => [
                    'list' => $base . '/api/players/list',
                    'create' => $base . '/api/players/create',
          ],
          'overviewPathTemplate' => $base . '/matches/{match_id}',
          'analysisDeskPathTemplate' => $base . '/matches/{match_id}/desk',
          'stateKey' => 'lineupPage',
          'canEdit' => $canManage,
          'formations' => [
                    'list' => $formations,
                    'matchFormations' => [
                               'home' => isset($homeFormation['formation_id']) ? (int)$homeFormation['formation_id'] : null,
                               'away' => isset($awayFormation['formation_id']) ? (int)$awayFormation['formation_id'] : null,
                    ],
                    'listUrl' => $base . '/api/formations/list',
                    'selectUrl' => $base . '/api/match-formations/update',
                    'timing' => [
                              'match_period_id' => null,
                              'match_second' => 0,
                              'minute' => 0,
                              'minute_extra' => 0,
                    ],
          ],
];

$lineupState = [
          'home' => [
                    'format' => $homeFormation['format'] ?? null,
                    'formation_key' => $homeFormation['formation_key'] ?? null,
          ],
          'away' => [
                    'format' => $awayFormation['format'] ?? null,
                    'formation_key' => $awayFormation['formation_key'] ?? null,
          ],
];
$footerScripts = '<script>window.LINEUP_STATE = ' . json_encode($lineupState) . ';</script>';
$footerScripts .= '<script>window.MatchWizardLineupConfig = ' . json_encode($lineupConfig) . ';</script>';
$footerScripts .= '<script>window.MATCH_ID = ' . json_encode($matchId) . ';</script>';
// Filemtime-based versions keep the JS URL stable between changes.
$footerScripts .= '<script src="' . htmlspecialchars($base) . '/assets/js/wizard-lineup.js' . asset_version('/assets/js/wizard-lineup.js') . '"></script>';

$title = 'Lineup';
$bodyAttributes = 'data-lineup-page="true" data-match-id="' . (int)$matchId . '"';

ob_start();
?>
<div class="d-flex align-items-center justify-content-between mb-4">
          <div>
                    <h1 class="mb-1">Lineup</h1>
                    <p class="text-secondary mb-0">Manage the <?= htmlspecialchars($homeTeamName) ?> and <?= htmlspecialchars($awayTeamName) ?> lineups.</p>
          </div>
          <div class="d-flex gap-2">
                    <a href="<?= htmlspecialchars($base) ?>/matches/<?= $matchId ?>/desk" class="btn btn-secondary-soft btn-sm">Analysis desk</a>
                    <a href="<?= htmlspecialchars($base) ?>/matches/<?= $matchId ?>" class="btn btn-primary-soft btn-sm">Match overview</a>
          </div>
          <div class="mt-3">
                    <span id="lineupStatusBadge" class="wizard-status wizard-status-pending">Pending</span>
          </div>
</div>
<?php if (!$canManage): ?>
          <div class="alert alert-info">You can view lineup details. Only match editors can add or adjust players.</div>
<?php endif; ?>
<div id="lineupRoot" class="lineup-page-root" data-lineup-root data-match-id="<?= (int)$matchId ?>"
          data-lineup-can-edit="<?= $canManage ? '1' : '0' ?>"
          data-lineup-home-team-id="<?= (int)($match['home_team_id'] ?? 0) ?>"
          data-lineup-away-team-id="<?= (int)($match['away_team_id'] ?? 0) ?>"
          data-lineup-home-team-name="<?= htmlspecialchars($homeTeamName) ?>"
          data-lineup-away-team-name="<?= htmlspecialchars($awayTeamName) ?>">
          <?php require __DIR__ . '/wizard-step-lineup.php'; ?>
</div>
<?php require __DIR__ . '/wizard-step-lineup-modal.php'; ?>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../layout.php';
