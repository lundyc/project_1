<?php

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/match_repository.php';
require_once __DIR__ . '/../../lib/match_permissions.php';

auth_boot();
require_auth();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
          http_response_code(405);
          exit;
}

$matchId = isset($matchId) ? (int)$matchId : (int)($_POST['match_id'] ?? 0);

if ($matchId <= 0) {
          $_SESSION['match_form_error'] = 'Invalid match request';
          redirect('/matches');
}

function delete_match_video_file(string $root, string $path): void
{
          if (!$root || trim($path) === '') {
                    return;
          }

          $candidate = $root . DIRECTORY_SEPARATOR . ltrim($path, '/\\');
          if (!is_file($candidate)) {
                    return;
          }

          $resolved = realpath($candidate);
          if (!$resolved || !str_starts_with($resolved, $root)) {
                    return;
          }

          @unlink($resolved);
}

$match = get_match($matchId);
if (!$match) {
          $_SESSION['match_form_error'] = 'Match not found';
          redirect('/matches');
}

$user = current_user();
$roles = $_SESSION['roles'] ?? [];

if (!can_manage_match_for_club($user, $roles, (int)$match['club_id'])) {
          $_SESSION['match_form_error'] = 'You do not have permission to delete this match';
          redirect('/matches');
}

try {
          $projectRoot = realpath(dirname(__DIR__, 3));
          if ($projectRoot) {
                    delete_match_video_file($projectRoot, $match['video_source_path'] ?? '');
                    $progressFile = $projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'video_progress' . DIRECTORY_SEPARATOR . $matchId . '.json';
                    if (is_file($progressFile)) {
                              @unlink($progressFile);
                    }
          }

          delete_match($matchId);
          $_SESSION['match_form_success'] = 'Match deleted';
} catch (\Throwable $e) {
          $_SESSION['match_form_error'] = 'Unable to delete match';
}

redirect('/matches');
