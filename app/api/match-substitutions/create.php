<?php
require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/csrf.php';
require_once __DIR__ . '/../../lib/match_permissions.php';
require_once __DIR__ . '/../../lib/match_substitution_repository.php';
require_once __DIR__ . '/../../lib/api_response.php';
require_once __DIR__ . '/../../lib/player_name_helper.php';

auth_boot();
require_auth();

// Validate CSRF token for state-changing operation
try {
    require_csrf_token();
} catch (CsrfException $e) {
    http_response_code(403);
    die('Invalid CSRF token');
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

    // Football logic: Substitutions must NOT change is_starting. Starters are fixed at kickoff.
    // Validation: Ensure player_off is on pitch, player_on is not yet on pitch at this time.
    // (Basic validation, can be extended for more complex scenarios)
    $onPitch = [];
    $stmt = $conn->prepare('SELECT id, is_starting FROM match_players WHERE match_id = ? AND team_side = ?');
    $stmt->execute([$matchId, $teamSide]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $onPitch[$row['id']] = (int)$row['is_starting'] === 1;
    }

    // Get all substitutions for this match/team, ordered by minute
    $subsStmt = $conn->prepare('SELECT minute, player_off_match_player_id, player_on_match_player_id FROM match_substitutions WHERE match_id = ? AND team_side = ? ORDER BY minute ASC, id ASC');
    $subsStmt->execute([$matchId, $teamSide]);
    $subs = $subsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Simulate pitch state up to this substitution
    foreach ($subs as $sub) {
        if ((int)$sub['minute'] >= $minute) break;
        if (isset($onPitch[$sub['player_off_match_player_id']])) {
            $onPitch[$sub['player_off_match_player_id']] = false;
        }
        if (isset($onPitch[$sub['player_on_match_player_id']])) {
            $onPitch[$sub['player_on_match_player_id']] = true;
        }
    }

    if (empty($onPitch[$playerOffId])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Player to be substituted off is not currently on the pitch.']);
        exit;
    }
    if (!empty($onPitch[$playerOnId])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Player to be substituted on is already on the pitch.']);
        exit;
    }

    // Fetch the created substitution with player details
    $getStmt = $conn->prepare('
        SELECT ms.id, ms.match_id, ms.team_side, ms.minute, ms.minute_extra, 
               ms.player_off_match_player_id, ms.player_on_match_player_id, ms.reason, 
               ms.created_by, ms.created_at,
               mp_off.shirt_number AS player_off_shirt,
               pl_off.first_name AS player_off_first_name,
               pl_off.last_name AS player_off_last_name,
               mp_on.shirt_number AS player_on_shirt,
               pl_on.first_name AS player_on_first_name,
               pl_on.last_name AS player_on_last_name
        FROM match_substitutions ms
        LEFT JOIN match_players mp_off ON mp_off.id = ms.player_off_match_player_id
        LEFT JOIN players pl_off ON pl_off.id = mp_off.player_id
        LEFT JOIN match_players mp_on ON mp_on.id = ms.player_on_match_player_id
        LEFT JOIN players pl_on ON pl_on.id = mp_on.player_id
        WHERE ms.id = ?
    ');
    $getStmt->execute([$subId]);
    $substitution = $getStmt->fetch(PDO::FETCH_ASSOC);

    // Compute display names
    if ($substitution) {
        $substitution['player_off_name'] = build_full_name($substitution['player_off_first_name'], $substitution['player_off_last_name']);
        $substitution['player_on_name'] = build_full_name($substitution['player_on_first_name'], $substitution['player_on_last_name']);
    }

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
