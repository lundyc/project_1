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
$pdo = db();
$stmt = $pdo->prepare('SELECT club_id FROM matches WHERE id = :id');
$stmt->execute(['id' => $matchId]);
$match = $stmt->fetch(\PDO::FETCH_ASSOC);

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
    $stmt = $pdo->prepare('
        SELECT player_off_match_player_id, player_on_match_player_id 
        FROM match_substitutions 
        WHERE id = :id AND match_id = :match_id
    ');
    $stmt->execute(['id' => $subId, 'match_id' => $matchId]);
    $sub = $stmt->fetch(\PDO::FETCH_ASSOC);
    
    if (!$sub) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Substitution not found']);
        exit;
    }
    
    // Delete substitution
    $stmt = $pdo->prepare('DELETE FROM match_substitutions WHERE id = :id AND match_id = :match_id');
    if (!$stmt->execute(['id' => $subId, 'match_id' => $matchId])) {
        throw new Exception('Failed to delete substitution');
    }
    
    // Optionally restore is_starting flags (revert the substitution)
    // This is a design choice - you may want to keep the current state
    $stmt = $pdo->prepare('UPDATE match_players SET is_starting = 1 WHERE id = :id');
    $stmt->execute(['id' => $sub['player_off_match_player_id']]);
    
    $stmt = $pdo->prepare('UPDATE match_players SET is_starting = 0 WHERE id = :id');
    $stmt->execute(['id' => $sub['player_on_match_player_id']]);
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    error_log('[match-substitutions/delete] Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
