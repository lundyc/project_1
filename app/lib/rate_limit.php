<?php

/**
 * Rate Limiting System
 * 
 * Provides IP-based and user-based rate limiting to prevent brute force attacks.
 * Uses Redis-like TTL logic stored in `rate_limit_attempts` table.
 */

const RATE_LIMIT_MAX_ATTEMPTS = 5;
const RATE_LIMIT_WINDOW_SECONDS = 900; // 15 minutes
const RATE_LIMIT_LOCKOUT_SECONDS = 900; // 15 minutes

/**
 * Check if an action is rate limited
 * 
 * @param string $identifier Email, IP, or user identifier
 * @param string $action Action type (login, api_call, etc)
 * @return array ['allowed' => bool, 'attempts' => int, 'reset_in' => int (seconds)]
 */
function check_rate_limit(string $identifier, string $action = 'login', bool $failOpen = true): array
{
    try {
        $pdo = db();
        
        // Clean up expired entries
        $stmt = $pdo->prepare(
            'DELETE FROM rate_limit_attempts 
             WHERE identifier = :identifier 
             AND action = :action 
             AND created_at < NOW() - INTERVAL ' . RATE_LIMIT_WINDOW_SECONDS . ' SECOND'
        );
        $stmt->execute(['identifier' => $identifier, 'action' => $action]);
        
        // Get current attempt count
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) as count, MIN(created_at) as first_attempt 
             FROM rate_limit_attempts 
             WHERE identifier = :identifier 
             AND action = :action 
             AND created_at > NOW() - INTERVAL ' . RATE_LIMIT_WINDOW_SECONDS . ' SECOND'
        );
        $stmt->execute(['identifier' => $identifier, 'action' => $action]);
        $result = $stmt->fetch();
        
        $attempts = (int)$result['count'];
        
        // If under limit, allow the attempt
        if ($attempts < RATE_LIMIT_MAX_ATTEMPTS) {
            return [
                'allowed' => true,
                'attempts' => $attempts,
                'reset_in' => 0
            ];
        }
        
        // Over limit – calculate reset time
        $firstAttempt = strtotime($result['first_attempt']);
        $resetTime = $firstAttempt + RATE_LIMIT_WINDOW_SECONDS;
        $secondsUntilReset = max(0, $resetTime - time());
        
        return [
            'allowed' => false,
            'attempts' => $attempts,
            'reset_in' => $secondsUntilReset
        ];
        
    } catch (Exception $e) {
        error_log('Rate limit check failed: ' . $e->getMessage());
        if ($failOpen) {
            // Fail open for non-auth flows to avoid cascading outages.
            return [
                'allowed' => true,
                'attempts' => 0,
                'reset_in' => 0
            ];
        }

        // Fail closed for sensitive flows (e.g., login) when limiter is unavailable.
        return [
            'allowed' => false,
            'attempts' => RATE_LIMIT_MAX_ATTEMPTS,
            'reset_in' => RATE_LIMIT_WINDOW_SECONDS
        ];
    }
}

/**
 * Record a failed attempt
 * 
 * @param string $identifier Email, IP, or user identifier
 * @param string $action Action type
 * @return void
 */
function record_failed_attempt(string $identifier, string $action = 'login'): void
{
    try {
        $pdo = db();
        $stmt = $pdo->prepare(
            'INSERT INTO rate_limit_attempts (identifier, action, ip_address, created_at) 
             VALUES (:identifier, :action, :ip_address, NOW())'
        );
        $stmt->execute([
            'identifier' => $identifier,
            'action' => $action,
            'ip_address' => get_client_ip()
        ]);
    } catch (Exception $e) {
        error_log('Failed to record rate limit attempt: ' . $e->getMessage());
    }
}

/**
 * Clear rate limit for an identifier (call on successful login)
 * 
 * @param string $identifier Email, IP, or user identifier
 * @param string $action Action type
 * @return void
 */
function clear_rate_limit(string $identifier, string $action = 'login'): void
{
    try {
        $pdo = db();
        $stmt = $pdo->prepare(
            'DELETE FROM rate_limit_attempts 
             WHERE identifier = :identifier 
             AND action = :action'
        );
        $stmt->execute(['identifier' => $identifier, 'action' => $action]);
    } catch (Exception $e) {
        error_log('Failed to clear rate limit: ' . $e->getMessage());
    }
}

/**
 * Helper: Get client IP address
 * 
 * @return string Client IP address
 */
function get_client_ip(): string
{
    // Check for IP from shared internet
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    // Check for IP passed from proxy
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // Handle multiple IPs (take the first one)
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ips[0]);
    }
    // Check for remote address
    elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    else {
        $ip = '0.0.0.0';
    }

    // Validate IP
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        $ip = '0.0.0.0';
    }

    return $ip;
}

/**
 * Check if account is locked (convenience function)
 * 
 * @param string $identifier User identifier
 * @param string $action Action type
 * @return bool True if locked
 */
function is_account_locked(string $identifier, string $action = 'login'): bool
{
    $result = check_rate_limit($identifier, $action);
    return !$result['allowed'];
}

/**
 * Rate limit middleware for API endpoints
 * 
 * Automatically checks rate limit and responds with 429 if exceeded.
 * Use this at the start of mutation API endpoints.
 * 
 * @param string $action Action type (e.g., 'api_write', 'match_create')
 * @param int $maxAttempts Maximum attempts allowed
 * @param int $windowSeconds Time window in seconds
 * @return void Exits with 429 response if rate limited
 */
function require_rate_limit(string $action = 'api_write', int $maxAttempts = 30, int $windowSeconds = 60): void
{
    $identifier = get_client_ip();
    
    // Override constants for custom limits
    if ($maxAttempts !== 30 || $windowSeconds !== 60) {
        // Custom check logic here
        $pdo = db();
        
        $stmt = $pdo->prepare(
            'DELETE FROM rate_limit_attempts 
             WHERE identifier = :identifier 
             AND action = :action 
             AND created_at < NOW() - INTERVAL ' . $windowSeconds . ' SECOND'
        );
        $stmt->execute(['identifier' => $identifier, 'action' => $action]);
        
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) as count, MIN(created_at) as first_attempt 
             FROM rate_limit_attempts 
             WHERE identifier = :identifier 
             AND action = :action 
             AND created_at > NOW() - INTERVAL ' . $windowSeconds . ' SECOND'
        );
        $stmt->execute(['identifier' => $identifier, 'action' => $action]);
        $result = $stmt->fetch();
        
        $attempts = (int)$result['count'];
        
        if ($attempts >= $maxAttempts) {
            $firstAttempt = strtotime($result['first_attempt']);
            $resetTime = $firstAttempt + $windowSeconds;
            $secondsUntilReset = max(0, $resetTime - time());
            
            if (function_exists('api_error')) {
                require_once __DIR__ . '/api_response.php';
                api_error('rate_limit_exceeded', 429, [
                    'reset_in' => $secondsUntilReset,
                    'retry_after' => $secondsUntilReset
                ]);
            } else {
                http_response_code(429);
                header('Retry-After: ' . $secondsUntilReset);
                echo json_encode([
                    'ok' => false,
                    'error' => 'rate_limit_exceeded',
                    'reset_in' => $secondsUntilReset
                ]);
                exit;
            }
        }
        
        return;
    }
    
    // Use default rate limit check
    $rateLimit = check_rate_limit($identifier, $action);
    
    if (!$rateLimit['allowed']) {
        if (function_exists('api_error')) {
            require_once __DIR__ . '/api_response.php';
            api_error('rate_limit_exceeded', 429, [
                'reset_in' => $rateLimit['reset_in'],
                'retry_after' => $rateLimit['reset_in']
            ]);
        } else {
            http_response_code(429);
            header('Retry-After: ' . $rateLimit['reset_in']);
            echo json_encode([
                'ok' => false,
                'error' => 'rate_limit_exceeded',
                'reset_in' => $rateLimit['reset_in']
            ]);
            exit;
        }
    }
    
    // Record this attempt
    record_failed_attempt($identifier, $action);
}
