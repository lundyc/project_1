<?php

require_once __DIR__ . '/../../lib/auth.php';
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

$user = current_user();
$clubId = isset($_GET['club_id']) ? (int)$_GET['club_id'] : (int)($user['club_id'] ?? 0);
$query = isset($_GET['q']) ? trim((string)$_GET['q']) : '';

if ($clubId <= 0) {
          respond_json(400, ['ok' => false, 'error' => 'Club required']);
}

if (strlen($query) < 2) {
          respond_json(200, ['ok' => true, 'players' => []]);
}


$teamId = isset($_GET['team_id']) ? (int)$_GET['team_id'] : 0;
$sql = 'SELECT id, first_name, last_name, is_active, primary_position FROM players WHERE club_id = :club_id AND (first_name LIKE :query_first OR last_name LIKE :query_last)';
if ($teamId > 0) {
    $sql .= ' AND team_id = :team_id';
}
$sql .= ' ORDER BY is_active DESC, first_name ASC, last_name ASC LIMIT 20';
$stmt = db()->prepare($sql);
$params = [
    'club_id' => $clubId,
    'query_first' => '%' . $query . '%',
    'query_last' => '%' . $query . '%',
];
if ($teamId > 0) {
    $params['team_id'] = $teamId;
}
$stmt->execute($params);

$results = $stmt->fetchAll();

$players = array_map(function($p) {
          $fullName = build_full_name($p['first_name'], $p['last_name']);
          return [
                    'id' => (int)$p['id'],
                    'display_name' => $fullName,
                    'full_name' => $fullName,
                    'is_active' => (int)$p['is_active'],
                    'position' => $p['primary_position'],
          ];
}, $results);

respond_json(200, ['ok' => true, 'players' => $players]);
