<?php

require_once __DIR__ . '/auth.php';

class CsrfException extends \RuntimeException
{
}

/**
 * Ensure the session contains a CSRF token and return it.
 *
 * @return string
 */
function get_csrf_token(): string
{
          auth_boot();
          if (empty($_SESSION['csrf_token'])) {
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
          }

          return (string)$_SESSION['csrf_token'];
}

/**
 * Validate the CSRF header against the stored token.
 *
 * @throws CsrfException
 */
function require_csrf_token(): void
{
          auth_boot();
          $expected = $_SESSION['csrf_token'] ?? '';

          $jsonToken = '';
          $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
          if (stripos($contentType, 'application/json') !== false) {
                    $rawInput = file_get_contents('php://input');
                    if ($rawInput !== false && $rawInput !== '') {
                              $decoded = json_decode($rawInput, true);
                              if (is_array($decoded) && isset($decoded['csrf_token'])) {
                                        $jsonToken = (string)$decoded['csrf_token'];
                              }
                    }
          }

          // Accept token from header, POST body, or JSON body only (not GET to prevent logging)
          $provided = $_SERVER['HTTP_X_CSRF_TOKEN']
                    ?? $_POST['csrf_token']
                    ?? $jsonToken
                    ?? '';

          if ($expected === '' || $provided === '' || !hash_equals($expected, $provided)) {
                    throw new CsrfException('invalid_csrf');
          }
}
