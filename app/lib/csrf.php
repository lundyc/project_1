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
          
          // Accept token from header or POST body only (not GET to prevent logging)
          $provided = $_SERVER['HTTP_X_CSRF_TOKEN'] 
                    ?? $_POST['csrf_token'] 
                    ?? '';

          if ($expected === '' || $provided === '' || !hash_equals($expected, $provided)) {
                    throw new CsrfException('invalid_csrf');
          }
}
