<?php

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/competition_repository.php';
require_once __DIR__ . '/../../lib/season_repository.php';
require_once __DIR__ . '/../../lib/match_permissions.php';

auth_boot();
require_auth();

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
$seasonId = isset($input['season_id']) ? (int)$input['season_id'] : 0;
$name = isset($input['name']) ? trim((string)$input['name']) : '';
$type = isset($input['type']) ? strtolower(trim((string)$input['type'])) : 'cup';

if (!in_array($type, ['league', 'cup'], true)) {
          $type = 'cup';
}

if ($clubId <= 0 || $seasonId <= 0 || $name === '') {
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

if (!is_season_in_club($seasonId, $clubId)) {
          respond_json(422, ['ok' => false, 'error' => 'Invalid season for this club']);
}

try {
          $competitionId = create_competition_for_club($clubId, $seasonId, $name, $type);
          respond_json(200, ['ok' => true, 'competition' => [
                    'id' => $competitionId,
                    'name' => $name,
                    'season_id' => $seasonId,
                    'type' => $type,
          ]]);
} catch (\Throwable $e) {
          respond_json(500, ['ok' => false, 'error' => 'Unable to create competition']);
}
