<?php
// Public-facing proxy for VEO download progress
// Forwards to app/api/videos/veo-progress.php

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

require_once __DIR__ . '/../../app/api/videos/veo-progress.php';
