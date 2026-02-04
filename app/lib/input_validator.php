<?php

/**
 * Input Validation Library
 * Centralized validation functions for common data types
 */

/**
 * Validate and sanitize email address
 * 
 * @param string $email Email to validate
 * @return string|false Validated email or false if invalid
 */
function validate_email(string $email): string|false
{
          $email = trim(strtolower($email));
          
          // Check length (max 254 chars per RFC 5321)
          if (strlen($email) > 254) {
                    return false;
          }
          
          // Use PHP filter
          if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    return false;
          }
          
          return $email;
}

/**
 * Validate integer value within range
 * 
 * @param mixed $value Value to validate
 * @param int $min Minimum value
 * @param int $max Maximum value
 * @return int|false Validated integer or false
 */
function validate_integer($value, int $min = PHP_INT_MIN, int $max = PHP_INT_MAX): int|false
{
          $validated = filter_var($value, FILTER_VALIDATE_INT, [
                    'options' => [
                              'min_range' => $min,
                              'max_range' => $max
                    ]
          ]);
          
          return $validated !== false ? (int)$validated : false;
}

/**
 * Validate URL
 * 
 * @param string $url URL to validate
 * @param array $allowedSchemes Allowed URL schemes (default: http, https)
 * @return string|false Validated URL or false
 */
function validate_url(string $url, array $allowedSchemes = ['http', 'https']): string|false
{
          $url = trim($url);
          
          $parsed = parse_url($url);
          if (!$parsed || !isset($parsed['scheme'])) {
                    return false;
          }
          
          // Verify scheme is allowed
          if (!in_array(strtolower($parsed['scheme']), $allowedSchemes, true)) {
                    return false;
          }
          
          // Use PHP filter
          if (!filter_var($url, FILTER_VALIDATE_URL)) {
                    return false;
          }
          
          return $url;
}

/**
 * Validate string length
 * 
 * @param string $value String to validate
 * @param int $minLength Minimum length
 * @param int $maxLength Maximum length
 * @return string|false Validated string or false
 */
function validate_string_length(string $value, int $minLength = 0, int $maxLength = 1000): string|false
{
          $value = trim($value);
          $len = strlen($value);
          
          if ($len < $minLength || $len > $maxLength) {
                    return false;
          }
          
          return $value;
}

/**
 * Validate password strength
 * Requirements: 8+ chars, at least one uppercase, one lowercase, one number
 * 
 * @param string $password Password to validate
 * @return string|false Validated password or false
 */
function validate_password(string $password): string|false
{
          // Check length
          if (strlen($password) < 8 || strlen($password) > 128) {
                    return false;
          }
          
          // Check for uppercase
          if (!preg_match('/[A-Z]/', $password)) {
                    return false;
          }
          
          // Check for lowercase
          if (!preg_match('/[a-z]/', $password)) {
                    return false;
          }
          
          // Check for digit
          if (!preg_match('/[0-9]/', $password)) {
                    return false;
          }
          
          return $password;
}

/**
 * Validate that value matches one of allowed options
 * 
 * @param mixed $value Value to validate
 * @param array $allowedValues Allowed values
 * @param bool $strict Use strict comparison
 * @return mixed Validated value or false
 */
function validate_choice($value, array $allowedValues, bool $strict = true)
{
          if (in_array($value, $allowedValues, $strict)) {
                    return $value;
          }
          
          return false;
}

/**
 * Validate date in YYYY-MM-DD format
 * 
 * @param string $date Date string to validate
 * @param string $minDate Minimum date (YYYY-MM-DD)
 * @param string $maxDate Maximum date (YYYY-MM-DD)
 * @return string|false Validated date or false
 */
function validate_date(string $date, ?string $minDate = null, ?string $maxDate = null): string|false
{
          // Check format
          if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                    return false;
          }
          
          // Parse and validate
          $parsed = date_parse_from_format('Y-m-d', $date);
          if ($parsed['error_count'] > 0) {
                    return false;
          }
          
          // Check range if specified
          if ($minDate !== null && $date < $minDate) {
                    return false;
          }
          
          if ($maxDate !== null && $date > $maxDate) {
                    return false;
          }
          
          return $date;
}

/**
 * Validate JSON string
 * 
 * @param string $jsonString JSON string to validate
 * @return array|false Decoded object/array or false
 */
function validate_json(string $jsonString)
{
          $decoded = json_decode($jsonString, true);
          
          if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
                    return false;
          }
          
          return $decoded !== null ? $decoded : false;
}

/**
 * Validate file upload
 * 
 * @param array $file $_FILES array element
 * @param array $allowedMimes Allowed MIME types
 * @param int $maxSizeBytes Maximum file size in bytes
 * @return string|false File path or false if invalid
 */
function validate_file_upload(array $file, array $allowedMimes = [], int $maxSizeBytes = 5242880): string|false
{
          // Check for upload errors
          if (($file['error'] ?? null) !== UPLOAD_ERR_OK) {
                    return false;
          }
          
          // Check file exists and is readable
          if (!is_uploaded_file($file['tmp_name'])) {
                    return false;
          }
          
          // Check size
          if ($file['size'] > $maxSizeBytes) {
                    return false;
          }
          
          // Check MIME type if specified
          if (!empty($allowedMimes)) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mimeType = finfo_file($finfo, $file['tmp_name']);
                    finfo_close($finfo);
                    
                    if (!in_array($mimeType, $allowedMimes, true)) {
                              return false;
                    }
          }
          
          return $file['tmp_name'];
}

/**
 * Validate IP address
 * 
 * @param string $ip IP address to validate
 * @return string|false Validated IP or false
 */
function validate_ip(string $ip): string|false
{
          $ip = trim($ip);
          
          if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                    return false;
          }
          
          return $ip;
}

/**
 * Validate match_second (event timing in seconds)
 * 
 * @param mixed $matchSecond Value to validate
 * @param int $maxSeconds Maximum match duration (default 5400 = 90 minutes)
 * @return int|false Validated integer or false
 */
function validate_match_second($matchSecond, int $maxSeconds = 5400): int|false
{
          $result = validate_integer($matchSecond, 0, $maxSeconds);
          
          return $result !== false ? $result : false;
}

/**
 * Sanitize input for display (not for database)
 * This is an alias for escape_html from html_sanitizer
 * 
 * @param mixed $value Value to sanitize
 * @return string Sanitized value
 */
function sanitize_display($value): string
{
          if (function_exists('escape_html')) {
                    return escape_html($value);
          }
          
          return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Validate and extract numeric ID from request
 * 
 * @param mixed $id ID to validate
 * @param string $paramName Parameter name for error message
 * @return int|false Validated ID or false
 */
function validate_numeric_id($id, string $paramName = 'id'): int|false
{
          $validated = validate_integer($id, 1);
          
          if ($validated === false) {
                    error_log(sprintf('Invalid %s format: expected positive integer, got: %s', $paramName, var_export($id, true)));
          }
          
          return $validated;
}
