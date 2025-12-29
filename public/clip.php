<?php
require_once __DIR__ . '/../app/lib/auth.php';

// Simple secured clip streamer for Video Lab mini player.
// Usage: /clip.php?match=1&event=188&clip=1

auth_boot();
require_auth();

$matchId = isset($_GET['match']) ? (int)$_GET['match'] : 0;
$eventId = isset($_GET['event']) ? (int)$_GET['event'] : 0;
$clipId = isset($_GET['clip']) ? (int)$_GET['clip'] : 0;

if ($matchId <= 0 || $eventId <= 0 || $clipId <= 0) {
    http_response_code(400);
    echo 'Bad request';
    exit;
}

$projectRoot = realpath(__DIR__ . '/..');
$storagePath = $projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'clips'
    . DIRECTORY_SEPARATOR . 'match_' . $matchId
    . DIRECTORY_SEPARATOR . 'event_' . $eventId
    . DIRECTORY_SEPARATOR . $clipId . '.mp4';

$real = realpath($storagePath);
if ($real === false || !is_file($real)) {
    http_response_code(404);
    echo 'Not found';
    exit;
}

// Ensure file is inside the expected storage/clips directory
$allowedPrefix = $projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'clips';
if (!str_starts_with($real, $allowedPrefix)) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

$size = filesize($real);
$fp = fopen($real, 'rb');
if ($fp === false) {
    http_response_code(500);
    echo 'Unable to open file';
    exit;
}

header('Content-Type: video/mp4');
header('Content-Length: ' . $size);
header('Content-Disposition: inline; filename="' . basename($real) . '"');
// No caching for now
header('Cache-Control: no-cache, no-store, must-revalidate');

// Stream file
while (!feof($fp)) {
    echo fread($fp, 8192);
    flush();
}
fclose($fp);

exit;
