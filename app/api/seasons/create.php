<?php

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/csrf.php';
require_once __DIR__ . '/../../lib/season_repository.php';
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
$name = isset($input['name']) ? trim((string)$input['name']) : '';
$startDate = isset($input['start_date']) && $input['start_date'] !== '' ? (string)$input['start_date'] : null;
$endDate = isset($input['end_date']) && $input['end_date'] !== '' ? (string)$input['end_date'] : null;

if ($clubId <= 0 || $name === '') {
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

try {
          $seasonId = create_season_for_club($clubId, $name, $startDate, $endDate);
          respond_json(200, ['ok' => true, 'season' => [
                    'id' => $seasonId,
                    'name' => $name,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
          ]]);
} catch (\Throwable $e) {
          respond_json(500, ['ok' => false, 'error' => 'Unable to create season']);
}
