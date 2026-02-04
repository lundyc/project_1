<?php

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/csrf.php';
require_once __DIR__ . '/../../lib/competition_repository.php';
require_once __DIR__ . '/../../lib/team_repository.php';
require_once __DIR__ . '/../../lib/match_permissions.php';

auth_boot();
require_auth();

// Validate CSRF token for state-changing operation
try {
    require_csrf_token();
} catch (CsrfException $e) {
    http_response_code(403);
    die('Invalid CSRF token');
}

function respond_json(int $status, array $payload): void
{
          http_response_code($status);
          header('Content-Type: application/json');
          echo json_encode($payload);
          exit;
}

$input = $_POST;
$raw = file_get_contents('php://input');
if (empty($input) && $raw) {
          $decoded = json_decode($raw, true);
          if (is_array($decoded)) {
                    $input = $decoded;
          }
}

$clubId = isset($input['club_id']) ? (int)$input['club_id'] : 0;
$competitionId = isset($input['competition_id']) ? (int)$input['competition_id'] : 0;
$teamId = isset($input['team_id']) ? (int)$input['team_id'] : 0;

if ($clubId <= 0 || $competitionId <= 0 || $teamId <= 0) {
          respond_json(422, ['ok' => false, 'error' => 'Invalid input']);
}

$user = current_user();
$roles = $_SESSION['roles'] ?? [];
$isPlatformAdmin = in_array('platform_admin', $roles, true);

if (!can_manage_matches($user, $roles)) {
          respond_json(403, ['ok' => false, 'error' => 'Unauthorized']);
}

if (!$isPlatformAdmin && (!isset($user['club_id']) || (int)$user['club_id'] !== $clubId)) {
          respond_json(403, ['ok' => false, 'error' => 'Unauthorized']);
}

if (!is_competition_in_club($competitionId, $clubId)) {
          respond_json(404, ['ok' => false, 'error' => 'Competition not found for this club']);
}

if (!is_team_in_club($teamId, $clubId)) {
          respond_json(422, ['ok' => false, 'error' => 'Team is not in this club']);
}

try {
          $ok = add_team_to_competition($competitionId, $teamId);
          if (!$ok) {
                    respond_json(422, ['ok' => false, 'error' => 'Unable to add team to competition']);
          }

          respond_json(200, ['ok' => true]);
} catch (\Throwable $e) {
          respond_json(500, ['ok' => false, 'error' => 'Unable to add team to competition']);
}
