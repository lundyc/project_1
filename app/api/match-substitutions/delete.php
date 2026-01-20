<?php
require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/match_permissions.php';
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
$subId = (int)($input['id'] ?? 0);

if (!$matchId || !$subId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Match ID and substitution ID required']);
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
    // Get substitution details before deleting
    $stmt = $conn->prepare('
        SELECT player_off_match_player_id, player_on_match_player_id 
        FROM match_substitutions 
        WHERE id = ? AND match_id = ?
    ');
    $stmt->bind_param('ii', $subId, $matchId);
    $stmt->execute();
    $result = $stmt->get_result();
    $sub = $result->fetch_assoc();
    $stmt->close();
    
    if (!$sub) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Substitution not found']);
        exit;
    }
    
    // Delete substitution
    $stmt = $conn->prepare('DELETE FROM match_substitutions WHERE id = ? AND match_id = ?');
    $stmt->bind_param('ii', $subId, $matchId);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to delete substitution');
    }
    
    $stmt->close();
    
    // Optionally restore is_starting flags (revert the substitution)
    // This is a design choice - you may want to keep the current state
    $stmt = $conn->prepare('UPDATE match_players SET is_starting = 1 WHERE id = ?');
    $stmt->bind_param('i', $sub['player_off_match_player_id']);
    $stmt->execute();
    $stmt->close();
    
    $stmt = $conn->prepare('UPDATE match_players SET is_starting = 0 WHERE id = ?');
    $stmt->bind_param('i', $sub['player_on_match_player_id']);
    $stmt->execute();
    $stmt->close();
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    error_log('[match-substitutions/delete] Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
