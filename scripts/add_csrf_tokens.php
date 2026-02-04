<?php

/**
 * Automated CSRF Token Addition Script
 * Adds CSRF token validation to all POST endpoints missing it
 */

$endpoints = [
    'app/api/competitions/create.php',
    'app/api/competitions/delete.php',
    'app/api/competitions/remove_team.php',
    'app/api/competitions/add_team.php',
    'app/api/competitions/update.php',
    'app/api/match-players/delete.php',
    'app/api/match-players/add.php',
    'app/api/match-players/update.php',
    'app/api/match-substitutions/create.php',
    'app/api/match-substitutions/delete.php',
    'app/api/clips/regenerate.php',
    'app/api/matches/delete.php',
    'app/api/matches/update-details.php',
    'app/api/matches/clips_delete.php',
    'app/api/matches/periods_preset.php',
    'app/api/matches/update-video.php',
    'app/api/matches/substitutions.php',
    'app/api/matches/clips_create.php',
    'app/api/matches/video_veo.php',
    'app/api/matches/update.php',
    'app/api/matches/roster_save.php',
    'app/api/matches/video_veo_cancel.php',
    'app/api/matches/periods_end.php',
    'app/api/matches/periods_custom.php',
    'app/api/matches/periods_start.php',
    'app/api/seasons/create.php',
    'app/api/seasons/delete.php',
    'app/api/seasons/update.php',
    'app/api/match-periods/set.php',
    'app/api/league_intelligence/fixtures_accept.php',
    'app/api/match-video/retry.php',
    'app/api/admin/update_club.php',
    'app/api/admin/delete_club.php',
    'app/api/admin/create_club.php',
    'app/api/admin/remove_club_team.php',
    'app/api/admin/create_user.php',
    'app/api/admin/players/create.php',
    'app/api/admin/players/delete.php',
    'app/api/admin/players/update.php',
    'app/api/admin/teams/create.php',
    'app/api/admin/teams/delete.php',
    'app/api/admin/teams/update.php',
    'app/api/admin/assign_club_team.php',
    'app/api/players/create.php',
    'app/api/videos/veo-download.php',
    'app/api/videos/upload.php',
    'app/api/match-formations/update.php',
    'app/api/teams/create-json.php',
    'app/api/teams/create.php',
];

$count = 0;
$errors = [];

foreach ($endpoints as $file) {
    $filepath = __DIR__ . '/' . $file;
    
    if (!file_exists($filepath)) {
        $errors[] = "File not found: $file";
        continue;
    }
    
    $content = file_get_contents($filepath);
    
    // Check if already has CSRF
    if (strpos($content, 'require_csrf_token') !== false || strpos($content, 'csrf.php') !== false) {
        echo "SKIP (already has CSRF): $file\n";
        continue;
    }
    
    // Add csrf.php require if not present
    if (strpos($content, "require_once __DIR__ . '/../lib/csrf.php';") === false &&
        strpos($content, 'require_once __DIR__ . ' . "'/../lib/csrf.php'" . ';') === false &&
        strpos($content, 'require_once __DIR__ . ' . "'/../../../lib/csrf.php'" . ';') === false) {
        
        // Find the last require_once statement
        preg_match_all('/require_once __DIR__.*?;/s', $content, $matches);
        
        if (!empty($matches[0])) {
            $lastRequire = end($matches[0]);
            $basePath = dirname(str_replace(realpath(__DIR__), '', $filepath));
            $upDirs = substr_count($basePath, '/');
            $path = str_repeat("'/../", $upDirs + 1) . "'lib/csrf.php';";
            
            $newRequire = $lastRequire . "\nrequire_once __DIR__ . " . $path;
            $content = str_replace($lastRequire, $newRequire, $content);
        }
    }
    
    // Add CSRF check after auth
    // Look for require_auth() or auth_boot() followed by processing
    if (preg_match('/require_auth\(\);/', $content)) {
        $pattern = '/(require_auth\(\);)([\s\n]*(?:\/\/[^\n]*\n)*)/';
        $replacement = "$1\n\n// Validate CSRF token for state-changing operation\ntry {\n    require_csrf_token();\n} catch (CsrfException \$e) {\n    http_response_code(403);\n    die('Invalid CSRF token');\n}\n$2";
        
        if (!preg_match('/require_csrf_token/', $content)) {
            $content = preg_replace($pattern, $replacement, $content, 1);
        }
    }
    
    if (file_put_contents($filepath, $content) === false) {
        $errors[] = "Failed to write: $file";
    } else {
        $count++;
        echo "UPDATED: $file\n";
    }
}

echo "\n✓ Updated $count endpoints\n";

if (!empty($errors)) {
    echo "\nErrors:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
}
