<?php
// Usage: php regenerate_playlist_mp4s.php <playlist_id>
require_once __DIR__ . '/../app/lib/playlist_repository.php';
require_once __DIR__ . '/../app/lib/clip_mp4_service.php';

if ($argc < 2) {
    echo "Usage: php regenerate_playlist_mp4s.php <playlist_id>\n";
    exit(1);
}

$playlistId = (int)$argv[1];
if ($playlistId <= 0) {
    echo "Invalid playlist_id.\n";
    exit(1);
}

$clips = playlist_get_clips($playlistId);
if (empty($clips)) {
    echo "No clips found for playlist $playlistId.\n";
    exit(0);
}

$success = 0;
$fail = 0;
foreach ($clips as $clip) {
    try {
        ensure_clip_mp4_exists($clip);
        echo "Generated mp4 for clip ID {$clip['id']}\n";
        $success++;
    } catch (Exception $e) {
        echo "Failed to generate mp4 for clip ID {$clip['id']}: {$e->getMessage()}\n";
        $fail++;
    }
}
echo "Done. Success: $success, Failed: $fail\n";
