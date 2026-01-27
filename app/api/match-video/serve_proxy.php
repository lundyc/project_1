<?php
// Serve the correct video variant (proxy or original) based on context
// Usage: /app/api/match-video/serve_proxy.php?match_id=18&context=desk

$matchId = isset($_GET['match_id']) ? intval($_GET['match_id']) : null;
$context = isset($_GET['context']) ? $_GET['context'] : 'desk';

if (!$matchId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing match_id']);
    exit;
}

$baseDir = realpath(__DIR__ . '/../../../public/videos/matches');
$proxyPath = "$baseDir/match_{$matchId}_proxy.mp4";
$originalPath = "$baseDir/match_{$matchId}_standard.mp4";

// Desk/TV: Prefer proxy, fallback to original
if (in_array($context, ['desk', 'tv'])) {
    if (file_exists($proxyPath)) {
        $servePath = $proxyPath;
    } elseif (file_exists($originalPath)) {
        $servePath = $originalPath;
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'No video found']);
        exit;
    }
} else {
    // All other contexts: serve original
    if (file_exists($originalPath)) {
        $servePath = $originalPath;
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'No video found']);
        exit;
    }
}

// Serve the file
header('Content-Type: video/mp4');
header('Content-Length: ' . filesize($servePath));
header('Content-Disposition: inline; filename="' . basename($servePath) . '"');
readfile($servePath);
exit;
