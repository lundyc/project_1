<?php

/**
 * Security Event Logging
 * Centralized logging for authentication, authorization, and security events
 */

const SECURITY_LOG_PATH = __DIR__ . '/../../storage/logs/security.log';

/**
 * Log a security event
 * 
 * @param string $eventType Type of security event (login_success, login_failed, unauthorized_access, etc)
 * @param array $context Event context (user_id, ip_address, reason, etc)
 * @return void
 */
function log_security_event(string $eventType, array $context = []): void
{
          // Ensure logs directory exists
          $logDir = dirname(SECURITY_LOG_PATH);
          @mkdir($logDir, 0755, true);

          // Build log entry
          $logEntry = [
                    'timestamp' => date('c'),
                    'event_type' => $eventType,
                    // 'ip_address' => get_client_ip(), // get_client_ip() is defined in rate_limit.php; do not redeclare here.
                    'user_id' => $_SESSION['user_id'] ?? null,
                    'session_id' => session_id(),
                    'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 255),
                    'context' => $context,
          ];

          // Write to JSON log (one entry per line for easy parsing)
          $jsonLine = json_encode($logEntry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
          @file_put_contents(SECURITY_LOG_PATH, $jsonLine, FILE_APPEND | LOCK_EX);

          // Also log critical events to error_log
          if (in_array($eventType, ['account_locked', 'unauthorized_access', 'csrf_failure', 'sql_injection_attempt'], true)) {
                    $msg = sprintf(
                              '[SECURITY] %s | ip=%s | user=%s | context=%s',
                              $eventType,
                              get_client_ip(),
                              $_SESSION['user_id'] ?? 'unknown',
                              json_encode($context)
                    );
                    error_log($msg);
          }
}

/**
 * Log a failed authentication attempt
 * 
 * @param string $identifier Email or username
 * @param string $reason Reason for failure
 * @return void
 */
function log_failed_auth(string $identifier, string $reason = 'invalid_credentials'): void
{
          log_security_event('login_failed', [
                    'identifier' => $identifier,
                    'reason' => $reason,
          ]);
}

/**
 * Log a successful authentication
 * 
 * @param int $userId User ID
 * @param array $roles User roles
 * @return void
 */
function log_successful_auth(int $userId, array $roles = []): void
{
          log_security_event('login_success', [
                    'user_id' => $userId,
                    'roles' => $roles,
          ]);
}

/**
 * Log unauthorized access attempt
 * 
 * @param int $userId User ID (if authenticated)
 * @param string $resource Resource being accessed
 * @param string $requiredRole Role that was required
 * @return void
 */
function log_unauthorized_access(?int $userId, string $resource, string $requiredRole): void
{
          log_security_event('unauthorized_access', [
                    'user_id' => $userId,
                    'resource' => $resource,
                    'required_role' => $requiredRole,
          ]);
}

/**
 * Log CSRF token validation failure
 * 
 * @param string $action Action attempted
 * @param int $userId User ID (if authenticated)
 * @return void
 */
function log_csrf_failure(string $action, ?int $userId = null): void
{
          log_security_event('csrf_failure', [
                    'action' => $action,
                    'user_id' => $userId ?? $_SESSION['user_id'] ?? null,
          ]);
}

/**
 * Log potential SQL injection attempt
 * 
 * @param string $query Query fragment that triggered detection
 * @param string $source Source of the suspicious input
 * @return void
 */
function log_sql_injection_attempt(string $query, string $source = 'unknown'): void
{
          log_security_event('sql_injection_attempt', [
                    'query_fragment' => substr($query, 0, 100),
                    'source' => $source,
          ]);
}

/**
 * Log XSS attempt detection
 * 
 * @param string $payload Suspicious payload
 * @param string $source Where it was detected
 * @return void
 */
function log_xss_attempt(string $payload, string $source = 'unknown'): void
{
          log_security_event('xss_attempt', [
                    'payload_fragment' => substr($payload, 0, 100),
                    'source' => $source,
          ]);
}

/**
 * Log rate limit exceeded
 * 
 * @param string $action Action being rate limited
 * @param int $attemptCount Number of attempts
 * @param int $resetIn Seconds until reset
 * @return void
 */
function log_rate_limit_exceeded(string $action, int $attemptCount, int $resetIn): void
{
          log_security_event('rate_limit_exceeded', [
                    'action' => $action,
                    'attempt_count' => $attemptCount,
                    'reset_in_seconds' => $resetIn,
          ]);
}

/**
 * Log privilege escalation attempt
 * 
 * @param int $userId User ID
 * @param string $attemptedRole Role that user tried to assume
 * @return void
 */
function log_privilege_escalation_attempt(int $userId, string $attemptedRole): void
{
          log_security_event('privilege_escalation_attempt', [
                    'user_id' => $userId,
                    'attempted_role' => $attemptedRole,
          ]);
}

/**
 * Get security log analysis for admin dashboard
 * Returns summary of recent security events
 * 
 * @param int $lines Number of recent lines to analyze
 * @return array Summary of security events
 */
function get_security_log_summary(int $lines = 1000): array
{
          if (!file_exists(SECURITY_LOG_PATH)) {
                    return [
                              'total_events' => 0,
                              'recent_events' => [],
                              'events_by_type' => [],
                    ];
          }

          $eventCounts = [];
          $recentEvents = [];
          $lineCount = 0;

          $handle = fopen(SECURITY_LOG_PATH, 'r');
          if (!$handle) {
                    return [];
          }

          // Seek to end and read backwards
          while (!feof($handle) && $lineCount < $lines) {
                    $line = fgets($handle);
                    if ($line === false) {
                              break;
                    }

                    $lineCount++;

                    $entry = json_decode($line, true);
                    if (!is_array($entry)) {
                              continue;
                    }

                    // Count by event type
                    $type = $entry['event_type'] ?? 'unknown';
                    $eventCounts[$type] = ($eventCounts[$type] ?? 0) + 1;

                    // Collect recent events (limit to 50)
                    if (count($recentEvents) < 50) {
                              $recentEvents[] = [
                                        'timestamp' => $entry['timestamp'] ?? 'unknown',
                                        'event_type' => $type,
                                        'ip_address' => $entry['ip_address'] ?? 'unknown',
                                        'user_id' => $entry['user_id'] ?? null,
                              ];
                    }
          }

          fclose($handle);

          return [
                    'total_events_analyzed' => $lineCount,
                    'events_by_type' => $eventCounts,
                    'recent_events' => $recentEvents,
          ];
}
