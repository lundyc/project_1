<?php
require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/match_permissions.php';
require_once __DIR__ . '/../../lib/match_substitution_repository.php';
require_once __DIR__ . '/../../lib/api_response.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

require_auth();

$user = current_user();
$roles = $_SESSION['roles'] ?? [];

$input = json_decode(file_get_contents('php://input'), true);

$matchId = (int)($input['match_id'] ?? 0);
$teamSide = trim((string)($input['team_side'] ?? ''));
$minute = (int)($input['minute'] ?? 0);
$playerOffId = (int)($input['player_off_match_player_id'] ?? 0);
$playerOnId = (int)($input['player_on_match_player_id'] ?? 0);
$reason = trim((string)($input['reason'] ?? ''));

if (!$matchId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Match ID required']);
    exit;
}

if (!in_array($teamSide, ['home', 'away'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid team side']);
    exit;
}

if (!$playerOffId || !$playerOnId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Both players required']);
    exit;
}

// Get match and check permissions
$conn = db_connect();
$stmt = $conn->prepare('SELECT club_id FROM matches WHERE id = ?');
$stmt->bind_param('i', $matchId);
$stmt->execute();
$result = $stmt->get_result();
$match = $result->fetch_assoc();
$stmt->close();

if (!$match) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Match not found']);
    exit;
}

if (!can_manage_match_for_club($user, $roles, (int)$match['club_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Permission denied']);
    exit;
}

try {
    // Create substitution
    $stmt = $conn->prepare('
        INSERT INTO match_substitutions 
        (match_id, team_side, minute, player_off_match_player_id, player_on_match_player_id, reason, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
    ');
    
    $reasonValue = $reason ?: null;
    $stmt->bind_param('isiiss', $matchId, $teamSide, $minute, $playerOffId, $playerOnId, $reasonValue);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to create substitution: ' . $stmt->error);
    }
    
    $subId = $stmt->insert_id;
    $stmt->close();
    
    // Update is_starting flags for the players
    $stmt = $conn->prepare('UPDATE match_players SET is_starting = 0 WHERE id = ?');
    $stmt->bind_param('i', $playerOffId);
    $stmt->execute();
    $stmt->close();
    
    $stmt = $conn->prepare('UPDATE match_players SET is_starting = 1 WHERE id = ?');
    $stmt->bind_param('i', $playerOnId);
    $stmt->execute();
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'substitution_id' => $subId
    ]);
    
} catch (Exception $e) {
    error_log('[match-substitutions/create] Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
