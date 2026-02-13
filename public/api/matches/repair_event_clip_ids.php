<?php
// Repair script: set events.clip_id for all events in a match based on clips.event_id
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../../../app/lib/db.php';
require_once __DIR__ . '/../../../app/lib/auth.php';

header('Content-Type: application/json');
auth_boot();
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
    exit;
}

$matchId = isset($_GET['match_id']) ? (int)$_GET['match_id'] : 0;
if ($matchId <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'match_id_required']);
    exit;
}

try {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT id, event_id FROM clips WHERE match_id = ? AND event_id IS NOT NULL');
    $stmt->execute([$matchId]);
    $clips = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $updated = 0;
    foreach ($clips as $clip) {
        $clipId = (int)$clip['id'];
        $eventId = (int)$clip['event_id'];
        if ($eventId > 0) {
            $update = $pdo->prepare('UPDATE events SET clip_id = :clip_id WHERE id = :event_id');
            $update->execute(['clip_id' => $clipId, 'event_id' => $eventId]);
            $updated++;
        }
    }
    echo json_encode(['ok' => true, 'updated' => $updated]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}