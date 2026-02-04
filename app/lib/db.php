<?php

// Load .env file if it exists
require_once __DIR__ . '/env.php';

function db(): PDO
{
          static $pdo;

          if ($pdo) {
                    return $pdo;
          }

          // Read from environment variables first, fallback to config file
          $host = getenv('DB_HOST') ?: null;
          $port = getenv('DB_PORT') ?: 3306;
          $name = getenv('DB_NAME') ?: null;
          $user = getenv('DB_USER') ?: null;
          $pass = getenv('DB_PASS') ?: null;
          $charset = getenv('DB_CHARSET') ?: 'utf8mb4';

          // Fallback to config file if env vars not set
          if (!$host || !$name || !$user) {
                    $config = require __DIR__ . '/../../config/config.php';
                    $db = $config['db'];
                    $host = $host ?: $db['host'];
                    $name = $name ?: $db['name'];
                    $user = $user ?: $db['user'];
                    $pass = $pass !== null ? $pass : $db['pass'];
                    $charset = $charset ?: $db['charset'];
          }

          $appEnv = getenv('APP_ENV') ?: 'local';
          if ($appEnv === 'production' && (!$host || !$name || !$user || $pass === null || $pass === '')) {
                    throw new RuntimeException('Database credentials must be provided via environment variables in production.');
          }

          $dsn = sprintf(
                    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                    $host,
                    (int)$port,
                    $name,
                    $charset
          );

          $pdo = new PDO(
                    $dsn,
                    $user,
                    $pass,
                    [
                              PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                              PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                              PDO::ATTR_EMULATE_PREPARES => false,
                              PDO::ATTR_TIMEOUT => 5,
                    ]
          );

          return $pdo;
}
