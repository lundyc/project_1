<?php

require_once __DIR__ . '/error_logger.php';

/**
 * Log exceptions raised during API handling.
 */
function api_log_exception(\Throwable $exception): void
{
          error_log(sprintf('[API ERROR] %s in %s:%d', $exception->getMessage(), $exception->getFile(), $exception->getLine()));
}

/**
 * Send a normalized success response.
 *
 * @param array<string, mixed> $payload
 */
function api_success(array $payload = []): void
{
          $response = array_merge(['ok' => true, 'request_id' => get_request_id()], $payload);
          echo json_encode($response);
}

/**
 * Send a normalized error response and halt execution.
 *
 * @param string $errorCode
 * @param int $httpStatus
 * @param array<string, mixed> $meta
 * @param \Throwable|null $exception
 * @param array<string, mixed> $debug
 */
function api_error(string $errorCode, int $httpStatus = 400, array $meta = [], ?\Throwable $exception = null, array $debug = []): void
{
          if ($exception !== null) {
                    api_log_exception($exception);
          }

          http_response_code($httpStatus);
          $response = [
                    'ok' => false,
                    'error' => $errorCode,
                    'request_id' => get_request_id(),
                    'meta' => (object)$meta,
          ];

          if (!empty($debug) && is_debug_enabled()) {
                    $response['debug'] = $debug;
          }

          echo json_encode($response);

          exit;
}
