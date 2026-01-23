<?php
require_once __DIR__ . '/../../../../lib/auth.php';
require_once __DIR__ . '/../../../../lib/player_repository.php';

$context = require_club_admin_access();
$clubId = $context['club_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method Not Allowed';
    exit;
}

$firstName = trim($_POST['first_name'] ?? '');
$lastName = trim($_POST['last_name'] ?? '');
$primaryPosition = trim($_POST['primary_position'] ?? '');
$dob = trim($_POST['dob'] ?? '');
$teamId = isset($_POST['team_id']) && $_POST['team_id'] !== '' ? (int)$_POST['team_id'] : null;
$isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

$formInput = [
    'first_name' => $firstName,
    'last_name' => $lastName,
    'primary_position' => $primaryPosition,
    'dob' => $dob,
    'team_id' => $teamId,
    'is_active' => $isActive,
];

if ($firstName === '' || $lastName === '') {
    $_SESSION['player_create_error'] = 'First name and last name are required.';
    $_SESSION['player_create_input'] = $formInput;
    header('Location: ' . base_path() . '/admin/players/create.php');
    exit;
}

$playerId = create_player([
    'first_name' => $firstName,
    'last_name' => $lastName,
    'primary_position' => $primaryPosition,
    'dob' => $dob,
    'team_id' => $teamId,
    'is_active' => $isActive,
    'club_id' => $clubId,
]);

if ($playerId) {
    $_SESSION['player_flash_success'] = 'Player created successfully.';
    header('Location: ' . base_path() . '/admin/players/list.php');
    exit;
} else {
    $_SESSION['player_create_error'] = 'Failed to create player.';
    $_SESSION['player_create_input'] = $formInput;
    header('Location: ' . base_path() . '/admin/players/create.php');
    exit;
}
