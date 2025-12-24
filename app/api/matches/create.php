<?php

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/match_repository.php';
require_once __DIR__ . '/../../lib/match_permissions.php';
require_once __DIR__ . '/../../lib/db.php';

auth_boot();
require_auth();

$wantsJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
$input = $_POST;
$rawInput = file_get_contents('php://input');
if (empty($input) && $rawInput) {
          $decoded = json_decode($rawInput, true);
          if (is_array($decoded)) {
                    $input = $decoded;
          }
}

function respond_match_json(int $status, array $payload): void
{
          http_response_code($status);
          header('Content-Type: application/json');
          echo json_encode($payload);
          exit;
}

$user = current_user();
$roles = $_SESSION['roles'] ?? [];
$isPlatformAdmin = in_array('platform_admin', $roles, true);

if (!can_manage_matches($user, $roles)) {
          http_response_code(403);
          exit;
}

$clubId = $isPlatformAdmin ? (int)($input['club_id'] ?? 0) : (int)($user['club_id'] ?? 0);
$seasonId = isset($input['season_id']) && $input['season_id'] !== '' ? (int)$input['season_id'] : null;
$competitionId = isset($input['competition_id']) && $input['competition_id'] !== '' ? (int)$input['competition_id'] : null;
$homeTeamId = (int)($input['home_team_id'] ?? 0);
$awayTeamId = (int)($input['away_team_id'] ?? 0);
$kickoffRaw = trim($input['kickoff_at'] ?? '');
$venue = trim($input['venue'] ?? '');
$referee = trim($input['referee'] ?? '');
$attendanceRaw = trim($input['attendance'] ?? '');
$status = $input['status'] ?? 'draft';
$videoType = normalize_video_source_type($input['video_source_type'] ?? 'upload');
$videoPath = trim($input['video_source_path'] ?? '');

if (!$clubId || !$homeTeamId || !$awayTeamId) {
          if ($wantsJson) {
                    respond_match_json(422, ['ok' => false, 'error' => 'Club, home team, and away team are required']);
          }
          $_SESSION['match_form_error'] = 'Club, home team, and away team are required';
          redirect('/matches/create');
}

if ($homeTeamId === $awayTeamId) {
          if ($wantsJson) {
                    respond_match_json(422, ['ok' => false, 'error' => 'Home and away teams must be different']);
          }
          $_SESSION['match_form_error'] = 'Home and away teams must be different';
          redirect('/matches/create');
}

$kickoffAt = null;
if ($kickoffRaw !== '') {
          $parsedKickoff = strtotime(str_replace('T', ' ', $kickoffRaw));
          if ($parsedKickoff !== false) {
                    $kickoffAt = date('Y-m-d H:i:s', $parsedKickoff);
          }
}

$attendance = $attendanceRaw === '' ? null : (int)$attendanceRaw;
$status = in_array($status, ['draft', 'ready'], true) ? $status : 'draft';
$videoPath = $videoPath === '' ? null : $videoPath;

// Validate club ownership for related records
$teamCheck = db()->prepare('SELECT COUNT(*) AS cnt FROM teams WHERE club_id = :club_id AND id IN (:home_id, :away_id)');
$teamCheck->execute([
          'club_id' => $clubId,
          'home_id' => $homeTeamId,
          'away_id' => $awayTeamId,
]);
$teamCount = (int)$teamCheck->fetchColumn();
if ($teamCount < 2) {
          if ($wantsJson) {
                    respond_match_json(422, ['ok' => false, 'error' => 'Teams must belong to the selected club']);
          }
          $_SESSION['match_form_error'] = 'Teams must belong to the selected club';
          redirect('/matches/create?club_id=' . $clubId);
}

if ($seasonId !== null) {
          $seasonCheck = db()->prepare('SELECT id FROM seasons WHERE id = :id AND club_id = :club_id LIMIT 1');
          $seasonCheck->execute(['id' => $seasonId, 'club_id' => $clubId]);
          if (!$seasonCheck->fetch()) {
                    if ($wantsJson) {
                              respond_match_json(422, ['ok' => false, 'error' => 'Invalid season for this club']);
                    }
                    $_SESSION['match_form_error'] = 'Invalid season for this club';
                    redirect('/matches/create?club_id=' . $clubId);
          }
}

if ($competitionId !== null) {
          $competitionCheck = db()->prepare('SELECT id FROM competitions WHERE id = :id AND club_id = :club_id LIMIT 1');
          $competitionCheck->execute(['id' => $competitionId, 'club_id' => $clubId]);
          if (!$competitionCheck->fetch()) {
                    if ($wantsJson) {
                              respond_match_json(422, ['ok' => false, 'error' => 'Invalid competition for this club']);
                    }
                    $_SESSION['match_form_error'] = 'Invalid competition for this club';
                    redirect('/matches/create?club_id=' . $clubId);
          }
}

try {
          $matchId = create_match([
                    'club_id' => $clubId,
                    'season_id' => $seasonId,
                    'competition_id' => $competitionId,
                    'home_team_id' => $homeTeamId,
                    'away_team_id' => $awayTeamId,
                    'kickoff_at' => $kickoffAt,
                    'venue' => $venue !== '' ? $venue : null,
                    'referee' => $referee !== '' ? $referee : null,
                    'attendance' => $attendance,
                    'status' => $status,
                    'created_by' => (int)$user['id'],
                    'video_source_type' => $videoType,
                    'video_source_path' => $videoPath,
          ]);

          if ($wantsJson) {
                    respond_match_json(200, ['ok' => true, 'match_id' => $matchId]);
          }

          $_SESSION['match_form_success'] = 'Match created';
          redirect('/matches');
} catch (\Throwable $e) {
          if ($wantsJson) {
                    respond_match_json(500, ['ok' => false, 'error' => 'Unable to create match']);
          }
          $_SESSION['match_form_error'] = 'Unable to create match';
          redirect('/matches/create?club_id=' . $clubId);
}
