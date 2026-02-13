<?php
require_once __DIR__ . '/app/lib/db.php';
$pdo = db();
$matchId = 24;
$stmt = $pdo->prepare('SELECT id, match_id, event_type_id, clip_id FROM events WHERE match_id = ? LIMIT 10');
$stmt->execute([$matchId]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    echo json_encode($row) . PHP_EOL;
}
