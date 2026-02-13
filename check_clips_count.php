<?php
require_once __DIR__ . '/app/lib/db.php';
$pdo = db();
$matchId = 24;
$stmt = $pdo->prepare('SELECT COUNT(*) FROM clips WHERE match_id = ?');
$stmt->execute([$matchId]);
echo 'Clips: ' . $stmt->fetchColumn() . PHP_EOL;
