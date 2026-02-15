<?php

require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/csrf.php';
require_once __DIR__ . '/../../lib/match_permissions.php';
require_once __DIR__ . '/../../lib/api_response.php';
require_once __DIR__ . '/../../lib/event_penalty_repository.php';

auth_boot();
require_auth();

try {
    require_csrf_token();
} catch (CsrfException $e) {
    api_error('invalid_csrf', 403, [], $e);
}

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = [];
}

$eventId = (int)($input['event_id'] ?? 0);
$takerRaw = $input['taker_match_player_id'] ?? null;
$takerId = ($takerRaw !== null && $takerRaw !== '') ? (int)$takerRaw : null;
$fouledId = (int)($input['fouled_match_player_id'] ?? 0);
$foulerId = (int)($input['fouler_match_player_id'] ?? 0);
$goalkeeperId = (int)($input['goalkeeper_match_player_id'] ?? 0);
$penaltyReason = trim((string)($input['penalty_reason'] ?? ''));
$outcome = trim((string)($input['outcome'] ?? ''));
$placementZone = trim((string)($input['placement_zone'] ?? ''));
$keeperDive = trim((string)($input['keeper_dive_direction'] ?? ''));
$keeperTouched = $input['keeper_touched_ball'] ?? 0;

if ($eventId <= 0) {
    api_error('invalid_payload', 422);
}

$validOutcomes = ['scored', 'saved', 'missed', 'post', 'retaken'];
if ($outcome === '' || !in_array($outcome, $validOutcomes, true)) {
    api_error('invalid_outcome', 422);
}

if ($penaltyReason !== '' && mb_strlen($penaltyReason) > 50) {
    api_error('invalid_penalty_reason', 422);
}
if ($placementZone !== '' && mb_strlen($placementZone) > 30) {
    api_error('invalid_placement_zone', 422);
}
if ($keeperDive !== '' && mb_strlen($keeperDive) > 20) {
    api_error('invalid_keeper_dive_direction', 422);
}

$keeperTouchedBool = filter_var($keeperTouched, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
if ($keeperTouchedBool === null) {
    $keeperTouchedBool = false;
}

$db = db();

$eventStmt = $db->prepare('
    SELECT e.match_id, m.club_id
    FROM events e
    JOIN matches m ON m.id = e.match_id
    WHERE e.id = ?
    LIMIT 1
');
$eventStmt->execute([$eventId]);
$eventRow = $eventStmt->fetch(PDO::FETCH_ASSOC);
if (!$eventRow) {
    api_error('event_not_found', 404);
}

$user = current_user();
$roles = $_SESSION['roles'] ?? [];
if (!can_manage_match_for_club($user, $roles, (int)$eventRow['club_id'])) {
    api_error('forbidden', 403);
}

ensure_event_penalties_table($db);

$record = create_event_penalty($db, [
    'event_id' => $eventId,
    'taker_match_player_id' => ($takerId && $takerId > 0) ? $takerId : null,
    'fouled_match_player_id' => $fouledId > 0 ? $fouledId : null,
    'fouler_match_player_id' => $foulerId > 0 ? $foulerId : null,
    'goalkeeper_match_player_id' => $goalkeeperId > 0 ? $goalkeeperId : null,
    'penalty_reason' => $penaltyReason !== '' ? $penaltyReason : null,
    'outcome' => $outcome,
    'placement_zone' => $placementZone !== '' ? $placementZone : null,
    'keeper_dive_direction' => $keeperDive !== '' ? $keeperDive : null,
    'keeper_touched_ball' => $keeperTouchedBool ? 1 : 0,
]);

api_success(['penalty' => $record]);
