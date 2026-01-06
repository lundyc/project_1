<?php

declare(strict_types=1);

/**
 * Return the request correlation id for the current execution.
 */
function get_request_id(): string
{
          static $requestId;

          if ($requestId !== null && $requestId !== '') {
                    return $requestId;
          }

          $header = trim((string)($_SERVER['HTTP_X_REQUEST_ID'] ?? ''));
          if ($header !== '') {
                    $requestId = $header;
                    return $requestId;
          }

          try {
                    $requestId = bin2hex(random_bytes(16));
          } catch (\Throwable $e) {
                    $requestId = uniqid('req_', true);
          }

          return $requestId;
}

/**
 * Return true when debug output is safe to expose to clients.
 */
function is_debug_enabled(): bool
{
          static $memo;
          if ($memo !== null) {
                    return $memo;
          }

          $env = strtolower((string)(getenv('APP_ENV') ?? ''));
          $debugFlag = getenv('DEBUG');
          $config = null;

          if ($env === '') {
                    $config = @require __DIR__ . '/../../config/config.php';
                    $env = strtolower((string)($config['app']['env'] ?? ''));
          }

          $debugFlagNormalized = filter_var($debugFlag, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
          if ($debugFlagNormalized !== null) {
                    $memo = $debugFlagNormalized;
                    return $memo;
          }

          if (is_array($config) && isset($config['app']['debug'])) {
                    $memo = (bool)$config['app']['debug'];
                    return $memo;
          }

          $memo = $env !== 'production';
          return $memo;
}

/**
 * Write a single structured JSON line for API errors.
 *
 * Accepts partial context and fills in defaults to keep the payload actionable.
 */
function log_api_error(array $ctx): void
{
          $projectRoot = dirname(__DIR__, 2);
          $logDir = $projectRoot . '/storage/logs';
          $logFile = $logDir . '/api_errors.log';

          $entry = [
                    'ts_utc' => gmdate(DATE_ATOM),
                    'request_id' => get_request_id(),
                    'route' => $ctx['route'] ?? ($_SERVER['REQUEST_URI'] ?? null),
                    'method' => $ctx['method'] ?? ($_SERVER['REQUEST_METHOD'] ?? null),
                    'ip' => $ctx['ip'] ?? ($_SERVER['REMOTE_ADDR'] ?? null),
                    'user_agent' => $ctx['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? null),
                    'layer' => $ctx['layer'] ?? null,
                    'fn' => $ctx['fn'] ?? null,
                    'message' => $ctx['message'] ?? null,
                    'level' => $ctx['level'] ?? 'error',
                    'user_id' => $ctx['user_id'] ?? null,
                    'match_id' => $ctx['match_id'] ?? null,
                    'payload' => sanitize_for_log($ctx['payload'] ?? null),
          ];

          if (isset($ctx['sql'])) {
                    $entry['sql'] = $ctx['sql'];
          }

          if (isset($ctx['params'])) {
                    $entry['params'] = sanitize_for_log($ctx['params']);
          }

          if (isset($ctx['hint'])) {
                    $entry['hint'] = $ctx['hint'];
          }

          if (isset($ctx['table'])) {
                    $entry['table'] = $ctx['table'];
          }

          if (isset($ctx['column'])) {
                    $entry['column'] = $ctx['column'];
          }

          if (isset($ctx['db_name'])) {
                    $entry['db_name'] = $ctx['db_name'];
          }

          if (isset($ctx['server'])) {
                    $entry['server'] = $ctx['server'];
          }

          $entry['exception'] = null;
          if (!empty($ctx['exception']) && $ctx['exception'] instanceof \Throwable) {
                    $exception = $ctx['exception'];
                    $exceptionEntry = [
                              'exception_class' => get_class($exception),
                              'message' => $exception->getMessage(),
                              'code' => $exception->getCode(),
                              'file' => $exception->getFile(),
                              'line' => $exception->getLine(),
                              'trace' => explode("\n", $exception->getTraceAsString()),
                    ];
                    if ($exception instanceof \PDOException) {
                              $errorInfo = $exception->errorInfo ?? null;
                              if (is_array($errorInfo)) {
                                        $exceptionEntry['sql_state'] = $errorInfo[0] ?? $exception->getCode();
                                        $exceptionEntry['driver_code'] = $errorInfo[1] ?? null;
                                        $exceptionEntry['driver_message'] = $errorInfo[2] ?? null;
                              } else {
                                        $exceptionEntry['sql_state'] = $exception->getCode();
                              }
                    }
                    $entry['exception'] = $exceptionEntry;
          }

          if ($entry['payload'] === null) {
                    unset($entry['payload']);
          }

          if ($entry['exception'] === null) {
                    unset($entry['exception']);
          }

          if ($entry['user_agent'] === null) {
                    unset($entry['user_agent']);
          }

          if ($entry['layer'] === null) {
                    unset($entry['layer']);
          }

          if ($entry['level'] === null) {
                    unset($entry['level']);
          }

          $line = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
          if ($line === false) {
                    $line = sprintf(
                              '{"ts_utc":"%s","request_id":"%s","message":"%s","error":"%s"}',
                              gmdate(DATE_ATOM),
                              addslashes(get_request_id()),
                              addslashes($ctx['message'] ?? 'log_api_error failure'),
                              addslashes(json_last_error_msg())
                    );
          }

          try {
                    if (!is_dir($logDir)) {
                              @mkdir($logDir, 0755, true);
                    }
                    @file_put_contents($logFile, $line . "\n", FILE_APPEND | LOCK_EX);
          } catch (\Throwable $ignore) {
                    // Swallow filesystem errors to avoid impacting the request.
          }
}

/**
 * Truncate strings and mask secrets before writing logs.
 *
 * @param mixed $value
 * @param int $depth
 * @return mixed
 */
function sanitize_for_log($value, int $depth = 0)
{
          if ($depth > 5) {
                    return '...(depth)';
          }

          if (is_null($value)) {
                    return null;
          }

          if (is_string($value)) {
                    return truncate_string($value);
          }

          if (is_bool($value) || is_int($value) || is_float($value)) {
                    return $value;
          }

          if (is_array($value)) {
                    $encoded = @json_encode($value);
                    if ($encoded !== false && strlen($encoded) > 8192) {
                              return '...(truncated)';
                    }

                    $sanitized = [];
                    foreach ($value as $key => $item) {
                              $normalizedKey = is_string($key) ? strtolower($key) : '';

                              if ($normalizedKey !== '' && is_sensitive_key($normalizedKey)) {
                                        $sanitized[$key] = '[redacted]';
                                        continue;
                              }

                              if ($normalizedKey === 'drawing_data' && is_array($item)) {
                                        $sanitized[$key] = sanitize_drawing_data_summary($item);
                                        continue;
                              }

                              $sanitized[$key] = sanitize_for_log($item, $depth + 1);
                    }

                    return $sanitized;
          }

          if ($value instanceof \JsonSerializable) {
                    return sanitize_for_log($value->jsonSerialize(), $depth + 1);
          }

          if (is_object($value)) {
                    return get_class($value);
          }

          if (is_resource($value)) {
                    return get_resource_type($value);
          }

          return (string)$value;
}

/**
 * Provide a small summary when drawing_data is logged.
 */
function sanitize_drawing_data_summary(array $drawingData): array
{
          $summary = [
                    'tool' => $drawingData['tool'] ?? $drawingData['tool_type'] ?? null,
          ];

          if (isset($drawingData['points'])) {
                    $summary['points_count'] = is_array($drawingData['points']) ? count($drawingData['points']) : null;
          }

          return $summary;
}

/**
 * Truncate strings longer than ~8KB.
 */
function truncate_string(string $value): string
{
          $limit = 8192;
          if (strlen($value) <= $limit) {
                    return $value;
          }

          return substr($value, 0, $limit) . '...(truncated)';
}

/**
 * Conceal sensitive keys.
 */
function is_sensitive_key(string $key): bool
{
          static $sensitive = [
                    'password',
                    'password_hash',
                    'token',
                    'auth_token',
                    'authorization',
                    'access_token',
                    'refresh_token',
                    'secret',
                    'cookie',
                    'cookies',
                    'credentials',
          ];

          return in_array($key, $sensitive, true);
}
