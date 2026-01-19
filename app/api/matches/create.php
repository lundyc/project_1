<?php

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/match_repository.php';
require_once __DIR__ . '/../../lib/match_permissions.php';
require_once __DIR__ . '/../../lib/db.php';

function match_wizard_log_path(): ?string
{
          static $path = null;
          if ($path !== null) {
                    return $path === false ? null : $path;
          }
          $root = realpath(__DIR__ . '/../../..');
          if ($root === false) {
                    $path = false;
                    return null;
          }
          $logDir = $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs';
          @mkdir($logDir, 0777, true);
          if (!is_dir($logDir) || !is_writable($logDir)) {
                    error_log('[match_wizard_log] Unable to write logs to ' . $logDir . ' (needs write permissions)');
                    $path = false;
                    return null;
          }
          $candidate = $logDir . DIRECTORY_SEPARATOR . 'match_wizard_debug.log';
          if (file_exists($candidate) && !is_writable($candidate)) {
                    error_log('[match_wizard_log] Log file exists but is not writable: ' . $candidate);
                    $path = false;
                    return null;
          }
          return $path = $candidate;
}

function log_match_wizard_event(?int $matchId, string $stage, string $message, array $context = []): void
{
          $logPath = match_wizard_log_path();
          $timestamp = date('c');
          $components = ["[$timestamp]", "[stage:$stage]"];
          if ($matchId !== null) {
                    $components[] = "[match:$matchId]";
          }
          $components[] = $message;
          if ($context) {
                    $encoded = json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                    if ($encoded !== false) {
                              $components[] = $encoded;
                    }
          }
          $line = implode(' ', $components);
          if ($logPath) {
                    file_put_contents($logPath, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
          }
          $logMessage = $matchId !== null
                    ? "[match:$matchId][$stage] $message"
                    : "[$stage] $message";
          error_log($logMessage);
}

function log_stage_entry(?int $matchId, string $stage, string $message, array $context = []): void
{
          $allowed = ['php', 'spawn', 'poll'];
          $stageLabel = in_array($stage, $allowed, true) ? $stage : 'php';
          log_match_wizard_event($matchId, $stageLabel, $message, $context);
}

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
          log_match_wizard_event(null, 'match_creation_auth', 'Unauthorized match creation attempt', ['user_id' => (int)$user['id'], 'roles' => $roles]);
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

log_match_wizard_event(null, 'match_creation_request', 'Creating match with submitted payload', [
          'user_id' => (int)$user['id'],
          'club_id' => $clubId,
          'home_team_id' => $homeTeamId,
          'away_team_id' => $awayTeamId,
          'video_type' => $videoType,
          'status' => $status,
]);

if (!$clubId || !$homeTeamId || !$awayTeamId) {
          log_match_wizard_event(null, 'match_creation_validation', 'Required team or club ID missing', [
                    'club_id' => $clubId,
                    'home_team_id' => $homeTeamId,
                    'away_team_id' => $awayTeamId,
          ]);
          if ($wantsJson) {
                    respond_match_json(422, [
                              'ok' => false,
                              'error' => 'Club, home team, and away team are required',
                              'error_code' => 'match_creation_missing_fields',
                    ]);
          }
          $_SESSION['match_form_error'] = 'Club, home team, and away team are required';
          redirect('/matches/create');
}

if ($homeTeamId === $awayTeamId) {
          log_match_wizard_event(null, 'match_creation_validation', 'Home and away teams are the same', [
                    'home_team_id' => $homeTeamId,
                    'away_team_id' => $awayTeamId,
          ]);
          if ($wantsJson) {
                    respond_match_json(422, [
                              'ok' => false,
                              'error' => 'Home and away teams must be different',
                              'error_code' => 'match_creation_duplicate_teams',
                    ]);
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
          log_match_wizard_event(null, 'match_creation_validation', 'Teams do not belong to club', [
                    'club_id' => $clubId,
                    'team_count' => $teamCount,
          ]);
          if ($wantsJson) {
                    respond_match_json(422, [
                              'ok' => false,
                              'error' => 'Teams must belong to the selected club',
                              'error_code' => 'match_creation_invalid_teams',
                    ]);
          }
          $_SESSION['match_form_error'] = 'Teams must belong to the selected club';
          redirect('/matches/create?club_id=' . $clubId);
}

if ($seasonId !== null) {
          $seasonCheck = db()->prepare('SELECT id FROM seasons WHERE id = :id AND club_id = :club_id LIMIT 1');
          $seasonCheck->execute(['id' => $seasonId, 'club_id' => $clubId]);
          if (!$seasonCheck->fetch()) {
                    log_match_wizard_event(null, 'match_creation_validation', 'Season invalid for club', [
                              'club_id' => $clubId,
                              'season_id' => $seasonId,
                    ]);
                    if ($wantsJson) {
                              respond_match_json(422, [
                                        'ok' => false,
                                        'error' => 'Invalid season for this club',
                                        'error_code' => 'match_creation_invalid_season',
                              ]);
                    }
                    $_SESSION['match_form_error'] = 'Invalid season for this club';
                    redirect('/matches/create?club_id=' . $clubId);
          }
}

if ($competitionId !== null) {
          $competitionCheck = db()->prepare('SELECT id FROM competitions WHERE id = :id AND club_id = :club_id LIMIT 1');
          $competitionCheck->execute(['id' => $competitionId, 'club_id' => $clubId]);
          if (!$competitionCheck->fetch()) {
                    log_match_wizard_event(null, 'match_creation_validation', 'Competition invalid for club', [
                              'club_id' => $clubId,
                              'competition_id' => $competitionId,
                    ]);
                    if ($wantsJson) {
                              respond_match_json(422, [
                                        'ok' => false,
                                        'error' => 'Invalid competition for this club',
                                        'error_code' => 'match_creation_invalid_competition',
                              ]);
                    }
                    $_SESSION['match_form_error'] = 'Invalid competition for this club';
                    redirect('/matches/create?club_id=' . $clubId);
          }
}
 
          $creationPayload = [
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
          ];
          log_stage_entry(null, 'php', 'Preparing match record', $creationPayload);
          try {
                    $matchId = create_match($creationPayload);

                    log_stage_entry($matchId, 'php', 'Match created', [
                              'video_type' => $videoType,
                              'match_id' => $matchId,
                    ]);
                    if ($wantsJson) {
                              respond_match_json(200, [
                                        'ok' => true,
                                        'match_id' => $matchId,
                                        'status' => 'match_created',
                                        'debug' => [
                                                  'log_path' => '/storage/logs/match_wizard_debug.log',
                                                  'match_path' => '/matches/' . $matchId,
                                        ],
                              ]);
                    }

                    $_SESSION['match_form_success'] = 'Match created';
                    redirect('/matches');
          } catch (\Throwable $e) {
                    log_stage_entry(null, 'php', 'Match creation failed', ['error' => $e->getMessage()]);
                    if ($wantsJson) {
                              respond_match_json(500, [
                                        'ok' => false,
                                        'error' => 'Unable to create match',
                                        'error_code' => 'match_creation_failed',
                              ]);
                    }
                    $_SESSION['match_form_error'] = 'Unable to create match';
                    redirect('/matches/create?club_id=' . $clubId);
          }
