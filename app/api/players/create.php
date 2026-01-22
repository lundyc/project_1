<?php

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/match_permissions.php';
require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../../lib/player_name_helper.php';

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
$rawInput = file_get_contents('php://input');
if (empty($input) && $rawInput) {
          $decoded = json_decode($rawInput, true);
          if (is_array($decoded)) {
                    $input = $decoded;
          }
}

$user = current_user();
$roles = $_SESSION['roles'] ?? [];
$clubId = isset($input['club_id']) ? (int)$input['club_id'] : (int)($user['club_id'] ?? 0);

if ($clubId <= 0) {
          respond_json(400, ['ok' => false, 'error' => 'Club required']);
}

if (!can_manage_match_for_club($user, $roles, $clubId)) {
          respond_json(403, ['ok' => false, 'error' => 'Unauthorized']);
}

$firstName = isset($input['first_name']) ? trim((string)$input['first_name']) : null;
$lastName = isset($input['last_name']) ? trim((string)$input['last_name']) : null;
$primaryPosition = isset($input['primary_position']) ? trim((string)$input['primary_position']) : null;
$teamId = isset($input['team_id']) && $input['team_id'] !== '' ? (int)$input['team_id'] : null;
$isActive = isset($input['is_active']) ? (int)$input['is_active'] : 1;

if (!$firstName && !$lastName) {
          respond_json(422, ['ok' => false, 'error' => 'First name or last name required']);
}

$stmt = db()->prepare(
          'INSERT INTO players (club_id, first_name, last_name, primary_position, team_id, is_active)
           VALUES (:club_id, :first_name, :last_name, :primary_position, :team_id, :is_active)'
);
$stmt->execute([
          'club_id' => $clubId,
          'first_name' => $firstName,
          'last_name' => $lastName,
          'primary_position' => $primaryPosition,
          'team_id' => $teamId,
          'is_active' => $isActive,
]);

$playerId = (int)db()->lastInsertId();
$fullName = build_full_name($firstName, $lastName);

respond_json(200, [
          'ok' => true,
          'player' => [
                    'id' => $playerId,
                    'display_name' => $fullName,
                    'primary_position' => $primaryPosition,
          ],
]);
