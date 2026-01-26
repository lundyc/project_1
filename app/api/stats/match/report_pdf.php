<?php
// /api/stats/match/report_pdf.php
// Endpoint to generate a PDF of the match report using dompdf


require_once __DIR__ . '/../../../../vendor/autoload.php'; // dompdf autoload
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../app/lib/match_repository.php';

use Dompdf\Dompdf;
use Dompdf\Options;


// $_GET['testing'] = 1; // Remove or comment out to enable PDF output
$matchId = isset($_GET['match_id']) ? (int)$_GET['match_id'] : 0;
$clubId = isset($_GET['club_id']) ? (int)$_GET['club_id'] : null;


// Fetch match info for filename
require_once __DIR__ . '/../../../../app/lib/match_repository.php';
$match = get_match($matchId);
$homeTeam = isset($match['home_team_name']) ? $match['home_team_name'] : (isset($match['home_team']) ? $match['home_team'] : 'Home');
$awayTeam = isset($match['away_team_name']) ? $match['away_team_name'] : (isset($match['away_team']) ? $match['away_team'] : 'Away');
$date = isset($match['date']) ? $match['date'] : (isset($match['match_date']) ? $match['match_date'] : '');
if ($date) {
	$dateObj = new DateTime($date);
	$dateStr = $dateObj->format('d-m-Y');
} else {
	$dateStr = date('d-m-Y');
}
function sanitize_filename($str) {
	return preg_replace('/[\\/:*?"<>|]/', '', $str);
}
$filename = 'Match Report - ' . sanitize_filename($homeTeam) . ' vs ' . sanitize_filename($awayTeam) . ' ' . $dateStr . '.pdf';

// Render the HTML report page as a string
ob_start();
$match_for_report = $match;
$matchId_for_report = $matchId;
$clubId_for_report = $clubId;
include __DIR__ . '/../../../../app/views/pages/stats/match_report.php';
$html = ob_get_clean();

// Only output HTML if explicitly requested for debugging
if (!empty($_GET['testing'])) {
	header('Content-Type: text/html; charset=utf-8');
	echo $html;
	exit;
}

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Arial');
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Output the generated PDF (force download)
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
echo $dompdf->output();
exit;
