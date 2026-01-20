<?php
require_auth();
require_once __DIR__ . '/../../lib/match_repository.php';
require_once __DIR__ . '/../../lib/match_permissions.php';
require_once __DIR__ . '/../../lib/db.php';

$user = current_user();
$roles = $_SESSION['roles'] ?? [];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method Not Allowed';
    exit;
}

$matchId = (int)($_POST['match_id'] ?? 0);

if (!$matchId) {
    $_SESSION['match_form_error'] = 'Invalid match ID';
    redirect('/matches');
    exit;
}

$match = get_match($matchId);

if (!$match) {
    http_response_code(404);
    echo '404 Not Found';
    exit;
}

if (!can_manage_match_for_club($user, $roles, (int)$match['club_id'])) {
    http_response_code(403);
    echo '403 Forbidden';
    exit;
}

// Get form data
$videoMode = $_POST['video_mode'] ?? 'upload';
$videoSourcePath = $_POST['video_source_path'] ?? null;
$veoUrl = $_POST['veo_url'] ?? null;

$db = db();

try {
    // Check if match_videos record exists
    $stmt = $db->prepare('SELECT id FROM match_videos WHERE match_id = :match_id LIMIT 1');
    $stmt->execute(['match_id' => $matchId]);
    $existingVideo = $stmt->fetch();

    if ($videoMode === 'veo' && $veoUrl) {
        // VEO download mode
        if ($existingVideo) {
            $stmt = $db->prepare('
                UPDATE match_videos 
                SET source_type = :source_type,
                    source_url = :source_url,
                    source_path = NULL,
                    download_status = :download_status,
                    download_progress = 0,
                    error_message = NULL
                WHERE match_id = :match_id
            ');
            $stmt->execute([
                'source_type' => 'veo',
                'source_url' => $veoUrl,
                'download_status' => 'pending',
                'match_id' => $matchId,
            ]);
        } else {
            $stmt = $db->prepare('
                INSERT INTO match_videos (match_id, source_type, source_url, download_status, download_progress)
                VALUES (:match_id, :source_type, :source_url, :download_status, 0)
            ');
            $stmt->execute([
                'match_id' => $matchId,
                'source_type' => 'veo',
                'source_url' => $veoUrl,
                'download_status' => 'pending',
            ]);
        }

        // Trigger download process (you may need to call a separate service/script)
        // For now, just set the status to pending

        $_SESSION['match_form_success'] = 'VEO download started';
    } else {
        // Upload mode
        if ($existingVideo) {
            $stmt = $db->prepare('
                UPDATE match_videos 
                SET source_type = :source_type,
                    source_path = :source_path,
                    source_url = NULL,
                    download_status = :download_status,
                    download_progress = 100
                WHERE match_id = :match_id
            ');
            $stmt->execute([
                'source_type' => 'upload',
                'source_path' => $videoSourcePath,
                'download_status' => 'completed',
                'match_id' => $matchId,
            ]);
        } else {
            $stmt = $db->prepare('
                INSERT INTO match_videos (match_id, source_type, source_path, download_status, download_progress)
                VALUES (:match_id, :source_type, :source_path, :download_status, 100)
            ');
            $stmt->execute([
                'match_id' => $matchId,
                'source_type' => 'upload',
                'source_path' => $videoSourcePath,
                'download_status' => 'completed',
            ]);
        }

        $_SESSION['match_form_success'] = 'Video source updated successfully';
    }

    redirect('/matches');
} catch (Exception $e) {
    error_log('Video update error: ' . $e->getMessage());
    $_SESSION['match_form_error'] = 'Failed to update video source';
    redirect('/matches/' . $matchId . '/video');
}
