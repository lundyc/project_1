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
$conn = db();
$stmt = $conn->prepare('SELECT club_id FROM matches WHERE id = ?');
$stmt->execute([$matchId]);
$match = $stmt->fetch(PDO::FETCH_ASSOC);

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
        (match_id, team_side, minute, player_off_match_player_id, player_on_match_player_id, reason, created_by, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ');

    $reasonValue = $reason ?: null;
    $stmt->execute([$matchId, $teamSide, $minute, $playerOffId, $playerOnId, $reasonValue, (int)$user['id']]);
    $subId = (int)$conn->lastInsertId();

    // Update is_starting flags for the players
    $conn->prepare('UPDATE match_players SET is_starting = 0 WHERE id = ?')->execute([$playerOffId]);
    $conn->prepare('UPDATE match_players SET is_starting = 1 WHERE id = ?')->execute([$playerOnId]);

    // Fetch the created substitution with player details
    $getStmt = $conn->prepare('
        SELECT ms.id, ms.match_id, ms.team_side, ms.minute, ms.minute_extra, 
               ms.player_off_match_player_id, ms.player_on_match_player_id, ms.reason, 
               ms.created_by, ms.created_at,
               mp_off.shirt_number AS player_off_shirt,
               COALESCE(pl_off.display_name, \'\') AS player_off_name,
               mp_on.shirt_number AS player_on_shirt,
               COALESCE(pl_on.display_name, \'\') AS player_on_name
        FROM match_substitutions ms
        LEFT JOIN match_players mp_off ON mp_off.id = ms.player_off_match_player_id
        LEFT JOIN players pl_off ON pl_off.id = mp_off.player_id
        LEFT JOIN match_players mp_on ON mp_on.id = ms.player_on_match_player_id
        LEFT JOIN players pl_on ON pl_on.id = mp_on.player_id
        WHERE ms.id = ?
    ');
    $getStmt->execute([$subId]);
    $substitution = $getStmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'ok' => true,
        'success' => true,
        'substitution_id' => $subId,
        'substitution' => $substitution
    ]);
    
} catch (Exception $e) {
    error_log('[match-substitutions/create] Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'success' => false, 'error' => $e->getMessage()]);
}
