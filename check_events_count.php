<?php
require_once __DIR__ . '/app/lib/db.php';
$pdo = db();
$matchId = 24;
$stmt = $pdo->prepare('SELECT COUNT(*) FROM events WHERE match_id = ?');
$stmt->execute([$matchId]);
echo 'Events: ' . $stmt->fetchColumn() . PHP_EOL;
