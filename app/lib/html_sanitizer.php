<?php

/**
 * HTML Sanitization Utilities
 * 
 * Provides safe methods for rendering user-controlled data in HTML context
 */

/**
 * Escape HTML special characters
 * Safe for use in HTML element content and attributes
 * 
 * @param mixed $value Value to escape
 * @param int $flags ENT_* flags (default: ENT_QUOTES | ENT_SUBSTITUTE)
 * @return string Escaped HTML
 */
function escape_html($value, int $flags = ENT_QUOTES | ENT_SUBSTITUTE): string
{
          return htmlspecialchars((string)$value, $flags, 'UTF-8');
}

/**
 * Escape attribute value for safe insertion into HTML attributes
 * 
 * @param mixed $value Value to escape
 * @return string Escaped value safe for HTML attributes
 */
function escape_attr($value): string
{
          return escape_html($value, ENT_QUOTES | ENT_SUBSTITUTE);
}

/**
 * Escape URL for use in href, src, etc.
 * Prevents javascript: and data: protocols
 * 
 * @param string $url URL to escape
 * @return string Safe URL or empty string if dangerous
 */
function escape_url(string $url): string
{
          $url = trim($url);
          
          // Block dangerous protocols
          $dangerousProtocols = ['javascript:', 'data:', 'vbscript:', 'file:'];
          $lowerUrl = strtolower($url);
          
          foreach ($dangerousProtocols as $protocol) {
                    if (strpos($lowerUrl, $protocol) === 0) {
                              return '';
                    }
          }
          
          return escape_html($url);
}

/**
 * Escape JavaScript string for safe insertion into JS code
 * Use this when embedding PHP data into <script> tags
 * 
 * @param mixed $value Value to escape
 * @return string Escaped JavaScript string (without quotes)
 */
function escape_js($value): string
{
          $value = (string)$value;
          
          // Escape backslashes first
          $value = str_replace('\\', '\\\\', $value);
          
          // Escape quotes
          $value = str_replace('"', '\\"', $value);
          $value = str_replace("'", "\\'", $value);
          
          // Escape newlines and carriage returns
          $value = str_replace("\n", '\\n', $value);
          $value = str_replace("\r", '\\r', $value);
          
          // Escape tab
          $value = str_replace("\t", '\\t', $value);
          
          // Escape </script> to prevent breaking out of script tags
          $value = str_replace('</', '<\\/', $value);
          
          return $value;
}

/**
 * Create a safe JSON string for use in HTML
 * 
 * @param mixed $data Data to encode as JSON
 * @param int $flags json_encode flags
 * @return string JSON string safe to embed in HTML
 */
function json_encode_safe($data, int $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE): string
{
          $json = json_encode($data, $flags);
          if ($json === false) {
                    return '{}';
          }
          return $json;
}

/**
 * Create a safe data attribute for HTML
 * 
 * @param mixed $data Data to encode
 * @return string Safe for use as data-* attribute value
 */
function data_attr($data): string
{
          if (is_array($data) || is_object($data)) {
                    $json = json_encode_safe($data);
          } else {
                    $json = json_encode_safe((string)$data);
          }
          
          return escape_attr($json);
}

/**
 * Validate and sanitize class names
 * Prevents injection via class attributes
 * 
 * @param string $classes Space-separated class names
 * @return string Sanitized class names
 */
function sanitize_classes(string $classes): string
{
          // Remove any potentially dangerous characters
          $classes = trim($classes);
          
          // Only allow alphanumeric, hyphen, underscore
          $classes = preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $classes);
          
          // Collapse multiple spaces
          $classes = preg_replace('/\s+/', ' ', $classes);
          
          return trim($classes);
}

/**
 * Create safe inline styles
 * Only allows basic color and size properties
 * 
 * @param array $styles Key-value style pairs
 * @return string Inline style attribute value
 */
function inline_styles(array $styles): string
{
          // Whitelist of allowed CSS properties
          $allowed = [
                    'color',
                    'background-color',
                    'font-size',
                    'font-weight',
                    'text-align',
                    'padding',
                    'margin',
                    'width',
                    'height',
                    'display',
          ];
          
          $safe = [];
          
          foreach ($styles as $property => $value) {
                    $property = strtolower(trim($property));
                    
                    // Only allow whitelisted properties
                    if (!in_array($property, $allowed, true)) {
                              continue;
                    }
                    
                    // Block dangerous values
                    $value = strtolower((string)$value);
                    if (strpos($value, 'expression') !== false || 
                        strpos($value, 'javascript:') !== false ||
                        strpos($value, '&{') !== false) {
                              continue;
                    }
                    
                    // Escape and add
                    $safe[] = escape_html($property) . ': ' . escape_html($value);
          }
          
          return implode('; ', $safe);
}

/**
 * Strip all HTML tags from string
 * 
 * @param mixed $value Value to strip
 * @param string $allowed Allowed tags (standard PHP strip_tags format)
 * @return string Text-only content
 */
function strip_html($value, string $allowed = ''): string
{
          return strip_tags((string)$value, $allowed);
}

/**
 * Truncate HTML-safe text with ellipsis
 * 
 * @param string $text Text to truncate
 * @param int $length Maximum length
 * @param string $ellipsis Ellipsis string
 * @return string Truncated text
 */
function truncate_text(string $text, int $length = 100, string $ellipsis = '…'): string
{
          if (strlen($text) <= $length) {
                    return $text;
          }
          
          return substr($text, 0, $length) . $ellipsis;
}
