<?php
require_auth();
require_once __DIR__ . '/../../lib/match_repository.php';
require_once __DIR__ . '/../../lib/match_permissions.php';
require_once __DIR__ . '/../../lib/db.php';

$user = current_user();
$roles = $_SESSION['roles'] ?? [];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method Not Allowed';
    exit;
}

$matchId = (int)($_POST['match_id'] ?? 0);

if (!$matchId) {
    $_SESSION['match_form_error'] = 'Invalid match ID';
    redirect('/matches');
    exit;
}

$match = get_match($matchId);

if (!$match) {
    http_response_code(404);
    echo '404 Not Found';
    exit;
}

if (!can_manage_match_for_club($user, $roles, (int)$match['club_id'])) {
    http_response_code(403);
    echo '403 Forbidden';
    exit;
}

// Get form data
$homeTeamId = (int)($_POST['home_team_id'] ?? 0);
$awayTeamId = (int)($_POST['away_team_id'] ?? 0);
$kickoffAt = $_POST['kickoff_at'] ?? null;
$status = $_POST['status'] ?? 'draft';
$venue = $_POST['venue'] ?? null;
$referee = $_POST['referee'] ?? null;
$attendance = $_POST['attendance'] !== '' && $_POST['attendance'] !== null ? (int)$_POST['attendance'] : null;
$seasonId = $_POST['season_id'] !== '' && $_POST['season_id'] !== null ? (int)$_POST['season_id'] : null;
$competitionId = $_POST['competition_id'] !== '' && $_POST['competition_id'] !== null ? (int)$_POST['competition_id'] : null;
$action = $_POST['action'] ?? 'save';

// Validate required fields
if (!$homeTeamId || !$awayTeamId) {
    $_SESSION['match_form_error'] = 'Both home and away teams are required';
    redirect('/matches/' . $matchId . '/edit');
    exit;
}

if ($homeTeamId === $awayTeamId) {
    $_SESSION['match_form_error'] = 'Home and away teams must be different';
    redirect('/matches/' . $matchId . '/edit');
    exit;
}

// Season validation
if ($seasonId !== null) {
    $seasonCheck = db()->prepare('SELECT id FROM seasons WHERE id = :id AND club_id = :club_id LIMIT 1');
    $seasonCheck->execute(['id' => $seasonId, 'club_id' => (int)$match['club_id']]);
    if (!$seasonCheck->fetch()) {
        $_SESSION['match_form_error'] = 'Invalid season for this club';
        redirect('/matches/' . $matchId . '/edit');
        exit;
    }
}

// Competition validation + season alignment
if ($competitionId !== null) {
    $competitionCheck = db()->prepare('SELECT id, season_id FROM competitions WHERE id = :id AND club_id = :club_id LIMIT 1');
    $competitionCheck->execute(['id' => $competitionId, 'club_id' => (int)$match['club_id']]);
    $competitionRow = $competitionCheck->fetch();
    if (!$competitionRow) {
        $_SESSION['match_form_error'] = 'Invalid competition for this club';
        redirect('/matches/' . $matchId . '/edit');
        exit;
    }

    $competitionSeasonId = (int)$competitionRow['season_id'];
    if ($seasonId !== null && $competitionSeasonId !== $seasonId) {
        $_SESSION['match_form_error'] = 'Competition must belong to the selected season';
        redirect('/matches/' . $matchId . '/edit');
        exit;
    }

    if ($seasonId === null) {
        $seasonId = $competitionSeasonId;
    }
}

// Update match
try {
    $stmt = db()->prepare('
        UPDATE matches 
        SET home_team_id = :home_team_id,
            away_team_id = :away_team_id,
            kickoff_at = :kickoff_at,
            status = :status,
            venue = :venue,
            referee = :referee,
            attendance = :attendance,
            season_id = :season_id,
            competition_id = :competition_id
        WHERE id = :id
    ');

    $stmt->execute([
        'home_team_id' => $homeTeamId,
        'away_team_id' => $awayTeamId,
        'kickoff_at' => $kickoffAt,
        'status' => $status,
        'venue' => $venue,
        'referee' => $referee,
        'attendance' => $attendance,
        'season_id' => $seasonId,
        'competition_id' => $competitionId,
        'id' => $matchId,
    ]);

    $_SESSION['match_form_success'] = 'Match details updated successfully';

    // Redirect based on action
    if ($action === 'save_and_continue') {
        redirect('/matches/' . $matchId . '/video');
    } else {
        redirect('/matches');
    }
} catch (Exception $e) {
    error_log('Match update error: ' . $e->getMessage());
    $_SESSION['match_form_error'] = 'Failed to update match details';
    redirect('/matches/' . $matchId . '/edit');
}
