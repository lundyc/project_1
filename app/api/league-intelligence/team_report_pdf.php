<?php
// League Intelligence Team PDF Export API
require_once __DIR__ . '/../../../vendor/autoload.php'; // dompdf autoload
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../app/lib/auth.php';
require_once __DIR__ . '/../../../app/lib/api_response.php';
require_once __DIR__ . '/../../../app/lib/league_intelligence_service.php';
require_once __DIR__ . '/../../../app/lib/team_repository.php';
require_once __DIR__ . '/../../../app/lib/club_repository.php';
require_once __DIR__ . '/../../../app/lib/season_repository.php';
require_once __DIR__ . '/../../../app/lib/competition_repository.php';
require_once __DIR__ . '/../../../app/lib/match_permissions.php';

use Dompdf\Dompdf;

auth_boot();
require_auth();

$teamId = isset($_GET['team_id']) ? (int)$_GET['team_id'] : 0;
$seasonId = isset($_GET['season_id']) ? (int)$_GET['season_id'] : 0;
$competitionId = isset($_GET['competition_id']) ? (int)$_GET['competition_id'] : 0;
$clubId = isset($_GET['club_id']) ? (int)$_GET['club_id'] : 0;

if ($teamId <= 0) {
    api_error('missing_team_id', 400);
}

$team = get_team_by_id($teamId);
if (!$team) {
    api_error('team_not_found', 404);
}


$teamClubId = (int)($team['club_id'] ?? 0);
$clubIdForAuth = $clubId > 0 ? $clubId : $teamClubId;

$user = current_user() ?? [];
$roles = $_SESSION['roles'] ?? [];
if (!in_array('platform_admin', $roles, true)) {
    if ($clubId > 0 && $teamClubId !== $clubId) {
        api_error('forbidden', 403);
    }
}

$user = current_user() ?? [];
$roles = $_SESSION['roles'] ?? [];
if (!can_view_match($user, $roles, $clubIdForAuth)) {
    api_error('forbidden', 403);
}


// Use LeagueIntelligenceService as in the UI controller
$service = new LeagueIntelligenceService($seasonId, $competitionId);
$insights = $service->getTeamInsights($teamId);
if (!$insights) {
    api_error('no_insights', 404);
}

ob_start();
$headerTitle = 'Team Intelligence Report';
$headerDescription = $team['name'] ?? '';
$headerButtons = [];
require __DIR__ . '/../../views/pages/league-intelligence/team_pdf.php';
$html = ob_get_clean();

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="team_report_' . $teamId . '.pdf"');
echo $dompdf->output();
exit;
