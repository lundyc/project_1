<?php
// Public-facing proxy for VEO download logic
// Forwards to app/api/matches/video_veo.php

// Allow CORS if needed
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// Route POST to the real handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../../app/api/matches/video_veo.php';
    exit;
}

echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
http_response_code(405);
exit;
