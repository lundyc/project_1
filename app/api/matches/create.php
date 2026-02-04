<?php

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/match_repository.php';
require_once __DIR__ . '/../../lib/match_permissions.php';
require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../../lib/csrf.php';

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

// Rate limit match creation to prevent abuse
require_once __DIR__ . '/../../lib/rate_limit.php';
require_rate_limit('match_create', 10, 300); // 10 matches per 5 minutes

// Validate CSRF token for state-changing operation
try {
          require_csrf_token();
} catch (CsrfException $e) {
          log_match_wizard_event(null, 'match_creation_csrf', 'CSRF token validation failed', [
                    'user_id' => (int)($_SESSION['user_id'] ?? 0),
                    'error' => $e->getMessage()
          ]);
          http_response_code(403);
          die('Invalid CSRF token');
}

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
$clubTeamId = (int)($input['club_team_id'] ?? 0);
$opponentTeamId = (int)($input['opponent_team_id'] ?? 0);
$clubSide = strtolower(trim((string)($input['club_side'] ?? 'home')));
$clubSide = $clubSide === 'away' ? 'away' : 'home';

$homeTeamId = 0;
$awayTeamId = 0;
if ($clubTeamId > 0 || $opponentTeamId > 0) {
          if ($clubTeamId > 0 && $opponentTeamId > 0) {
                    if ($clubSide === 'away') {
                              $homeTeamId = $opponentTeamId;
                              $awayTeamId = $clubTeamId;
                    } else {
                              $homeTeamId = $clubTeamId;
                              $awayTeamId = $opponentTeamId;
                    }
          }
} else {
          $homeTeamId = (int)($input['home_team_id'] ?? 0);
          $awayTeamId = (int)($input['away_team_id'] ?? 0);
}
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

if ($clubTeamId > 0 || $opponentTeamId > 0) {
          if (!$clubTeamId || !$opponentTeamId) {
                    log_match_wizard_event(null, 'match_creation_validation', 'Club or opponent team missing', [
                              'club_team_id' => $clubTeamId,
                              'opponent_team_id' => $opponentTeamId,
                    ]);
                    if ($wantsJson) {
                              respond_match_json(422, [
                                        'ok' => false,
                                        'error' => 'Your club team and opponent team are required',
                                        'error_code' => 'match_creation_missing_fields',
                              ]);
                    }
                    $_SESSION['match_form_error'] = 'Your club team and opponent team are required';
                    redirect('/matches/create');
          }
}

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

// Validate team ownership (one team from club, one from another club)
$teamStmt = db()->prepare('SELECT id, club_id FROM teams WHERE id = :id LIMIT 1');
$teamStmt->execute(['id' => $homeTeamId]);
$homeTeamRow = $teamStmt->fetch(PDO::FETCH_ASSOC);
$teamStmt->execute(['id' => $awayTeamId]);
$awayTeamRow = $teamStmt->fetch(PDO::FETCH_ASSOC);

if (!$homeTeamRow || !$awayTeamRow) {
          log_match_wizard_event(null, 'match_creation_validation', 'Team not found', [
                    'home_team_id' => $homeTeamId,
                    'away_team_id' => $awayTeamId,
          ]);
          if ($wantsJson) {
                    respond_match_json(422, [
                              'ok' => false,
                              'error' => 'Selected teams are invalid',
                              'error_code' => 'match_creation_invalid_teams',
                    ]);
          }
          $_SESSION['match_form_error'] = 'Selected teams are invalid';
          redirect('/matches/create?club_id=' . $clubId);
}

$homeIsClub = (int)$homeTeamRow['club_id'] === $clubId;
$awayIsClub = (int)$awayTeamRow['club_id'] === $clubId;

if (!$homeIsClub && !$awayIsClub) {
          log_match_wizard_event(null, 'match_creation_validation', 'Club team missing from matchup', [
                    'club_id' => $clubId,
                    'home_team_id' => $homeTeamId,
                    'away_team_id' => $awayTeamId,
          ]);
          if ($wantsJson) {
                    respond_match_json(422, [
                              'ok' => false,
                              'error' => 'Your club must be one of the teams',
                              'error_code' => 'match_creation_invalid_teams',
                    ]);
          }
          $_SESSION['match_form_error'] = 'Your club must be one of the teams';
          redirect('/matches/create?club_id=' . $clubId);
}

if ($homeIsClub && $awayIsClub) {
          log_match_wizard_event(null, 'match_creation_validation', 'Opponent team belongs to club', [
                    'club_id' => $clubId,
                    'home_team_id' => $homeTeamId,
                    'away_team_id' => $awayTeamId,
          ]);
          if ($wantsJson) {
                    respond_match_json(422, [
                              'ok' => false,
                              'error' => 'Opponent must be from another club',
                              'error_code' => 'match_creation_invalid_teams',
                    ]);
          }
          $_SESSION['match_form_error'] = 'Opponent must be from another club';
          redirect('/matches/create?club_id=' . $clubId);
}

if ($clubTeamId > 0 || $opponentTeamId > 0) {
          if ($clubSide === 'home' && !$homeIsClub) {
                    if ($wantsJson) {
                              respond_match_json(422, [
                                        'ok' => false,
                                        'error' => 'Your club must be selected as the home team',
                                        'error_code' => 'match_creation_invalid_teams',
                              ]);
                    }
                    $_SESSION['match_form_error'] = 'Your club must be selected as the home team';
                    redirect('/matches/create?club_id=' . $clubId);
          }

          if ($clubSide === 'away' && !$awayIsClub) {
                    if ($wantsJson) {
                              respond_match_json(422, [
                                        'ok' => false,
                                        'error' => 'Your club must be selected as the away team',
                                        'error_code' => 'match_creation_invalid_teams',
                              ]);
                    }
                    $_SESSION['match_form_error'] = 'Your club must be selected as the away team';
                    redirect('/matches/create?club_id=' . $clubId);
          }
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
          $competitionCheck = db()->prepare('SELECT id, season_id FROM competitions WHERE id = :id AND club_id = :club_id LIMIT 1');
          $competitionCheck->execute(['id' => $competitionId, 'club_id' => $clubId]);
          $competitionRow = $competitionCheck->fetch();
          if (!$competitionRow) {
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

          $competitionSeasonId = (int)$competitionRow['season_id'];
          if ($seasonId !== null && $competitionSeasonId !== $seasonId) {
                    if ($wantsJson) {
                              respond_match_json(422, [
                                        'ok' => false,
                                        'error' => 'Competition must belong to the selected season',
                                        'error_code' => 'match_creation_competition_season_mismatch',
                              ]);
                    }
                    $_SESSION['match_form_error'] = 'Competition must belong to the selected season';
                    redirect('/matches/create?club_id=' . $clubId);
          }

          if ($seasonId === null) {
                    $seasonId = $competitionSeasonId;
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
