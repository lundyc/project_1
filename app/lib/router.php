<?php

// Simple global route store for the current request.
$GLOBALS['ROUTES'] = $GLOBALS['ROUTES'] ?? [];

function route(string $path, callable $handler): void
{
          $normalized = normalize_path($path);
          $GLOBALS['ROUTES'][$normalized] = $handler;
}

function dispatch(): void
{
          $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

          $basePath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
          $basePath = rtrim($basePath, '/');
          if ($basePath === '/') {
                    $basePath = '';
          }

          if ($basePath && str_starts_with($uri, $basePath)) {
                    $uri = substr($uri, strlen($basePath));
          }

          $uri = normalize_path($uri);

          if (isset($GLOBALS['ROUTES'][$uri])) {
                    $GLOBALS['ROUTES'][$uri]();
                    return;
          }

          foreach ($GLOBALS['ROUTES'] as $path => $handler) {
                    if (!str_contains($path, '{id}')) {
                              continue;
                    }

                    $pattern = preg_quote($path, '#');
                    $pattern = str_replace('\{id\}', '([0-9]+)', $pattern);

                    if (preg_match('#^' . $pattern . '$#', $uri, $matches)) {
                              $_GET['id'] = isset($matches[1]) ? (int)$matches[1] : null;
                              $handler();
                              return;
                    }
          }

          http_response_code(404);
          echo '404 Not Found';
}

function normalize_path(string $path): string
{
          $path = str_replace('\\', '/', $path);

          if ($path === '') {
                    return '/';
          }

          if ($path[0] !== '/') {
                    $path = '/' . $path;
          }

          $path = rtrim($path, '/');
          if ($path === '') {
                    return '/';
          }

          return $path === '/' ? '/' : $path;
}
