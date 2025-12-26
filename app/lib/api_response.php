<?php

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
          echo json_encode(array_merge(['ok' => true], $payload));
}

/**
 * Send a normalized error response and halt execution.
 *
 * @param string $errorCode
 * @param int $httpStatus
 * @param array<string, mixed> $meta
 * @param \Throwable|null $exception
 */
function api_error(string $errorCode, int $httpStatus = 400, array $meta = [], ?\Throwable $exception = null): void
{
          if ($exception !== null) {
                    api_log_exception($exception);
          }

          http_response_code($httpStatus);
          echo json_encode([
                    'ok' => false,
                    'error' => $errorCode,
                    'meta' => (object)$meta,
          ]);

          exit;
}
