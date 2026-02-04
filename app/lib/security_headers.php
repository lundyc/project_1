<?php

/**
 * Content Security Policy (CSP) Header Configuration
 * Provides functions to set CSP headers for enhanced security
 */

/**
 * Generate and store CSP nonce for this request
 * 
 * @return string Base64 encoded nonce
 */
function get_csp_nonce(): string
{
          if (!isset($_SESSION['csp_nonce'])) {
                    $_SESSION['csp_nonce'] = base64_encode(random_bytes(16));
          }
          return $_SESSION['csp_nonce'];
}

/**
 * Set default CSP headers for HTML responses
 * Prevents XSS, clickjacking, and other injection attacks
 * 
 * @param array $customDirectives Optional override directives
 * @return void
 */
function set_csp_headers(array $customDirectives = []): void
{
          // Generate nonce for inline scripts
          $nonce = get_csp_nonce();
          
          // Default CSP directives with nonce-based inline script support
          $directives = [
                    // Use nonce instead of unsafe-inline for scripts
                    'script-src' => ["'self'", "'nonce-$nonce'", 'https://cdn.jsdelivr.net', 'https://cdnjs.cloudflare.com', 'https://code.jquery.com'],
                    
                    // Allow styles from self and CDNs (keep unsafe-inline for now due to Tailwind)
                    'style-src' => ["'self'", "'unsafe-inline'", 'https://fonts.googleapis.com', 'https://cdnjs.cloudflare.com'],
                    
                    // Font sources
                    'font-src' => ["'self'", 'https://fonts.gstatic.com', 'https://cdnjs.cloudflare.com'],
                    
                    // Images can be from self, data URIs, and common CDNs
                    'img-src' => ["'self'", 'data:', 'https:'],
                    
                    // Media (video/audio) sources
                    'media-src' => ["'self'", 'blob:'],
                    
                    // Prevent embedding in frames (clickjacking protection)
                    'frame-ancestors' => ["'none'"],
                    
                    // Form submissions only to same origin
                    'form-action' => ["'self'"],
                    
                    // Default fallback for uncovered directives
                    'default-src' => ["'self'"],
                    
                    // Upgrade insecure requests in production
                    'upgrade-insecure-requests' => [],
          ];

          // Merge with custom directives if provided
          $directives = array_merge($directives, $customDirectives);

          // Build header value
          $header_parts = [];
          foreach ($directives as $directive => $sources) {
                    // Ensure sources is array
                    if (!is_array($sources)) {
                              $sources = [$sources];
                    }
                    
                    // Skip empty directives
                    if (empty($sources)) {
                              continue;
                    }
                    
                    $header_parts[] = $directive . ' ' . implode(' ', $sources);
          }

          $csp = implode('; ', $header_parts);

          // Set both report-only and enforce headers
          // Use report-only first to validate in production
          header('Content-Security-Policy: ' . $csp);
}

/**
 * Set CSP report-only header (for testing without blocking)
 * 
 * @param array $customDirectives Optional override directives
 * @return void
 */
function set_csp_report_only(array $customDirectives = []): void
{
          $directives = [
                    'script-src' => ["'self'", 'https://cdn.jsdelivr.net'],
                    'style-src' => ["'self'", "'unsafe-inline'"],
                    'default-src' => ["'self'"],
          ];

          $directives = array_merge($directives, $customDirectives);

          $header_parts = [];
          foreach ($directives as $directive => $sources) {
                    if (!is_array($sources)) {
                              $sources = [$sources];
                    }
                    if (empty($sources)) {
                              continue;
                    }
                    $header_parts[] = $directive . ' ' . implode(' ', $sources);
          }

          $csp = implode('; ', $header_parts);
          header('Content-Security-Policy-Report-Only: ' . $csp);
}

/**
 * Set additional security headers
 * 
 * @return void
 */
function set_security_headers(): void
{
          // Prevent clickjacking
          header('X-Frame-Options: DENY');

          // Prevent content type sniffing
          header('X-Content-Type-Options: nosniff');

          // Enable XSS protection in older browsers
          header('X-XSS-Protection: 1; mode=block');

          // Require HTTPS for all future requests
          $appEnv = getenv('APP_ENV') ?: 'development';
          if ($appEnv === 'production' || $appEnv === 'prod') {
                    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
          }

          // Prevent referrer leakage
          header('Referrer-Policy: strict-origin-when-cross-origin');

          // Permissions policy (formerly Feature Policy)
          header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
}

/**
 * Set all security headers at once
 * Call this early in page load before any content output
 * 
 * @return void
 */
function set_all_security_headers(): void
{
          set_csp_headers();
          set_security_headers();
}
