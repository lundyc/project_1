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

$matchId = isset($matchId) ? (int)$matchId : (int)($input['match_id'] ?? 0);

function respond_match_json(int $status, array $payload): void
{
          http_response_code($status);
          header('Content-Type: application/json');
          echo json_encode($payload);
          exit;
}

/**
 * Handle validation errors with unified response format
 */
function handle_validation_error(string $message, int $matchId, bool $json = false): void
{
          if ($json) {
                    respond_match_json(422, ['ok' => false, 'error' => $message]);
          }
          $_SESSION['match_form_error'] = $message;
          redirect('/matches/' . $matchId . '/edit');
}

/**
 * Validate and parse kickoff datetime
 */
function parse_and_validate_kickoff(string $kickoffRaw): ?string
{
          if ($kickoffRaw === '') {
                    return null;
          }

          try {
                    // Try parsing ISO 8601 format first (from HTML5 datetime-local)
                    $dt = new DateTime($kickoffRaw);
                    $parsed = $dt->format('Y-m-d H:i:s');
                    return $parsed;
          } catch (Exception $e) {
                    throw new Exception('Invalid kickoff date format. Please use YYYY-MM-DD HH:MM.');
          }
}

if ($matchId <= 0) {
          if ($wantsJson) {
                    respond_match_json(400, ['ok' => false, 'error' => 'Invalid match ID']);
          }
          http_response_code(400);
          exit;
}

$user = current_user();
$roles = $_SESSION['roles'] ?? [];
$isPlatformAdmin = in_array('platform_admin', $roles, true);

// Check rate limiting (5 second cooldown per user per match)
$rateLimitKey = 'match_update_' . $matchId . '_' . $user['id'];
$apcuAvailable = function_exists('apcu_enabled') && apcu_enabled();

if ($apcuAvailable && function_exists('apcu_fetch') && apcu_fetch($rateLimitKey)) {
          if ($wantsJson) {
                    respond_match_json(429, ['ok' => false, 'error' => 'Please wait before updating again']);
          }
          $_SESSION['match_form_error'] = 'Please wait before updating again';
          redirect('/matches/' . $matchId . '/edit');
}

$existing = get_match($matchId);
if (!$existing) {
          if ($wantsJson) {
                    respond_match_json(404, ['ok' => false, 'error' => 'Match not found']);
          }
          http_response_code(404);
          echo 'Match not found';
          exit;
}

if (!can_manage_match_for_club($user, $roles, (int)$existing['club_id'])) {
          if ($wantsJson) {
                    respond_match_json(403, ['ok' => false, 'error' => 'Forbidden']);
          }
          http_response_code(403);
          exit;
}

// Parse and validate input
$clubId = $isPlatformAdmin ? (int)($input['club_id'] ?? $existing['club_id']) : (int)$existing['club_id'];
$seasonId = isset($input['season_id']) && $input['season_id'] !== '' ? (int)$input['season_id'] : null;
$competitionId = isset($input['competition_id']) && $input['competition_id'] !== '' ? (int)$input['competition_id'] : null;
$homeTeamId = (int)($input['home_team_id'] ?? 0);
$awayTeamId = (int)($input['away_team_id'] ?? 0);
$kickoffRaw = trim($input['kickoff_at'] ?? '');
$venue = substr(trim($input['venue'] ?? ''), 0, 255);
$referee = substr(trim($input['referee'] ?? ''), 0, 255);
$attendanceRaw = trim($input['attendance'] ?? '');
$status = $input['status'] ?? $existing['status'];
$videoType = normalize_video_source_type($input['video_source_type'] ?? ($existing['video_source_type'] ?? 'upload'));
$videoPathRaw = trim($input['video_source_path'] ?? '');
$videoUrlRaw = trim($input['video_source_url'] ?? '');

$existingVideoType = $existing['video_source_type'] ?? 'upload';
$existingVideoPath = $existing['video_source_path'] ?? null;
$existingVideoUrl = $existing['video_source_url'] ?? null;

// Preserve existing video unless explicitly changed
$videoPath = $videoPathRaw === '' ? null : $videoPathRaw;
$videoUrl = $videoUrlRaw === '' ? null : $videoUrlRaw;

if ($videoType === 'upload') {
          if ($videoPath === null && $existingVideoType === 'upload' && $existingVideoPath) {
                    $videoPath = $existingVideoPath;
          }
          $videoUrl = null;
}

if ($videoType === 'veo') {
          if ($videoUrl === null && $existingVideoType === 'veo' && $existingVideoUrl) {
                    $videoUrl = $existingVideoUrl;
          }
          if ($existingVideoType === 'upload' && $videoPathRaw === '') {
                    $videoPath = null;
          }
}

if ($videoType === 'none') {
          $videoPath = null;
          $videoUrl = null;
}

// Validate required fields
if (!$clubId || !$homeTeamId || !$awayTeamId) {
          handle_validation_error('Club, home team, and away team are required', $matchId, $wantsJson);
}

if ($homeTeamId === $awayTeamId) {
          handle_validation_error('Home and away teams must be different', $matchId, $wantsJson);
}

// Validate status enum
$validStatuses = ['draft', 'ready'];
if (!in_array($status, $validStatuses, true)) {
          handle_validation_error('Invalid match status', $matchId, $wantsJson);
}

// Parse and validate kickoff
$kickoffAt = null;
if ($kickoffRaw !== '') {
          try {
                    $kickoffAt = parse_and_validate_kickoff($kickoffRaw);
          } catch (Exception $e) {
                    handle_validation_error($e->getMessage(), $matchId, $wantsJson);
          }
}

// Validate and parse attendance
$attendance = null;
if ($attendanceRaw !== '') {
          $attendance = (int)$attendanceRaw;
          if ($attendance < 0 || $attendance > 1000000) {
                    handle_validation_error('Attendance must be between 0 and 1,000,000', $matchId, $wantsJson);
          }
}

$videoPath = $videoPath === '' ? null : $videoPath;

$teamCheck = db()->prepare('SELECT COUNT(*) AS cnt FROM teams WHERE club_id = :club_id AND id IN (:home_id, :away_id)');
$teamCheck->execute([
          'club_id' => $clubId,
          'home_id' => $homeTeamId,
          'away_id' => $awayTeamId,
]);
$teamCount = (int)$teamCheck->fetchColumn();
if ($teamCount < 2) {
          handle_validation_error('Teams must belong to the selected club', $matchId, $wantsJson);
}

if ($seasonId !== null) {
          $seasonCheck = db()->prepare('SELECT id FROM seasons WHERE id = :id AND club_id = :club_id LIMIT 1');
          $seasonCheck->execute(['id' => $seasonId, 'club_id' => $clubId]);
          if (!$seasonCheck->fetch()) {
                    handle_validation_error('Invalid season for this club', $matchId, $wantsJson);
          }
}

if ($competitionId !== null) {
          $competitionCheck = db()->prepare('SELECT id, season_id FROM competitions WHERE id = :id AND club_id = :club_id LIMIT 1');
          $competitionCheck->execute(['id' => $competitionId, 'club_id' => $clubId]);
          $competitionRow = $competitionCheck->fetch();
          if (!$competitionRow) {
                    handle_validation_error('Invalid competition for this club', $matchId, $wantsJson);
          }

          $competitionSeasonId = (int)$competitionRow['season_id'];
          if ($seasonId !== null && $competitionSeasonId !== $seasonId) {
                    handle_validation_error('Competition must belong to the selected season', $matchId, $wantsJson);
          }

          if ($seasonId === null) {
                    $seasonId = $competitionSeasonId;
          }
}

try {
          // Build change log for audit
          $changes = [];
          $updateData = [
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
                    'video_source_type' => $videoType,
                    'video_source_path' => $videoPath,
                    'video_source_url' => $videoUrl,
          ];

          // Track what changed
          foreach ($updateData as $key => $value) {
                    if ($existing[$key] !== $value) {
                              $changes[$key] = [
                                        'old' => $existing[$key],
                                        'new' => $value,
                              ];
                    }
          }

          update_match($matchId, $updateData);

          // Log audit trail if changes were made
          if (!empty($changes) && function_exists('audit_log')) {
                    audit_log('match_updated', [
                              'match_id' => $matchId,
                              'user_id' => $user['id'],
                              'club_id' => $clubId,
                              'changes' => $changes,
                              'timestamp' => date('Y-m-d H:i:s'),
                    ]);
          }

          // Set rate limit
          if ($apcuAvailable && function_exists('apcu_store')) {
                    apcu_store($rateLimitKey, true, 5);
          }

          if ($wantsJson) {
                    respond_match_json(200, ['ok' => true, 'match_id' => $matchId, 'changes' => count($changes)]);
          }

          $_SESSION['match_form_success'] = 'Match updated successfully';
          redirect('/matches');
} catch (\Throwable $e) {
          error_log('Match update error for match ' . $matchId . ': ' . $e->getMessage());
          if ($wantsJson) {
                    respond_match_json(500, ['ok' => false, 'error' => 'Unable to update match']);
          }
          $_SESSION['match_form_error'] = 'Unable to update match: ' . $e->getMessage();
          redirect('/matches/' . $matchId . '/edit');
}
