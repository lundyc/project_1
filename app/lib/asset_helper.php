<?php

declare(strict_types=1);

/**
 * Build a stable cache-busting query string for local static assets.
 *
 * Intent: use file modification time so URLs only change when the file changes,
 * allowing browsers/CDNs to cache aggressively between deployments.
 */
function asset_version(string $path): string
{
    if ($path === '') {
        return '';
    }

    // Skip external URLs; we cannot resolve them on disk.
    if (preg_match('#^(https?:)?//#i', $path) === 1) {
        return '';
    }

    static $publicRoot = null;
    if ($publicRoot === null) {
        $resolved = realpath(__DIR__ . '/../../public');
        $publicRoot = $resolved !== false ? $resolved : dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public';
    }

    // Remove any existing query string or fragment before resolving to disk.
    $cleanPath = strtok($path, '?#');
    if ($cleanPath === false || $cleanPath === '') {
        return '';
    }

    $relativePath = '/' . ltrim($cleanPath, '/');
    $absolutePath = $publicRoot . $relativePath;

    if (!is_file($absolutePath)) {
        // Missing assets should not break rendering; just omit the version.
        return '';
    }

    $mtime = filemtime($absolutePath);
    if ($mtime === false) {
        return '';
    }

    return '?v=' . $mtime;
}
