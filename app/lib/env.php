<?php

/**
 * Simple .env file loader
 * Loads environment variables from .env file into PHP's environment
 */

function load_env_file(string $path = null): void
{
    if ($path === null) {
        $path = __DIR__ . '/../../.env';
    }
    
    if (!file_exists($path)) {
        return; // No .env file, rely on system environment
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse KEY=VALUE format
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove quotes if present
            if (preg_match('/^(["\'])(.*)\\1$/', $value, $matches)) {
                $value = $matches[2];
            }
            
            // Set in environment if not already set
            if (!getenv($key)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}

// Auto-load when this file is included
load_env_file();
