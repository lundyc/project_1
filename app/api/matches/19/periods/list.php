<?php
// Debug endpoint to confirm rewrite
header('Content-Type: application/json');
echo json_encode([
    'ok' => true,
    'msg' => 'You hit the physical file! Rewrite is not working if you see this.',
    'get' => $_GET,
    'server' => $_SERVER,
]);
