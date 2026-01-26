<?php

// Simple global route store for the current request.
$GLOBALS['ROUTES'] = $GLOBALS['ROUTES'] ?? [
    'exact' => [],
    'placeholder' => [],
    'regex' => [],
];

/**
 * Register a route and its handler. The path can include parameter placeholders
 * using either `{id}` (which will be extracted and provided via `$_GET['id']`)
 * or regular expression capture groups (e.g. `(\d+)`) which will be passed
 * directly to the handler as arguments. All registered routes are normalized
 * to ensure consistent leading/trailing slashes.
 *
 * @param string   $path
 * @param callable $handler
 * @return void
 */
function route(string $path, callable $handler): void
{
    if (strpos($path, '(') !== false) {
        // Regex routes need their raw PCRE syntax preserved (e.g. `\d`)
        // so we skip normalization entirely for them.
        $GLOBALS['ROUTES']['regex'][$path] = $handler;
        return;
    }

    $normalized = normalize_path($path);
    if (str_contains($normalized, '{id}')) {
        $GLOBALS['ROUTES']['placeholder'][$normalized] = $handler;
        return;
    }

    $GLOBALS['ROUTES']['exact'][$normalized] = $handler;
}

/**
 * Dispatch the current request to the appropriate handler based on the
 * registered routes. Dispatch attempts to match the request path in three
 * stages:
 *
 * 1. Exact match – if the normalized URI matches a registered path exactly,
 *    the associated handler is invoked without parameters.
 * 2. Placeholder match – if a registered path contains `{id}`, it is
 *    transformed into a regular expression that captures a single numeric
 *    identifier. The captured value is assigned to `$_GET['id']` before the
 *    handler is executed.
 * 3. Regex match – if a registered path contains parentheses `(` and `)`,
 *    it is treated as a full regular expression. Any captured groups are
 *    passed directly to the handler as arguments via variadic unpacking.
 *
 * If no routes match, a `404 Not Found` response is returned.
 *
 * @return void
 */
function dispatch(): void
{
    $rawRequestUri = $_SERVER['REQUEST_URI'] ?? '/';
    $parsedUriPath = parse_url($rawRequestUri, PHP_URL_PATH) ?? '/';
    $uri = $parsedUriPath;

    // Compute base path for subdirectory deployments.
    $basePath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $basePath = rtrim($basePath, '/');
    if ($basePath === '/') {
        $basePath = '';
    }

    // Strip base path from URI if present.
    if ($basePath && str_starts_with($uri, $basePath)) {
        $uri = substr($uri, strlen($basePath));
    }

    $uri = normalize_path($uri);
    $finalNormalizedPath = $uri;

    $routeStore = $GLOBALS['ROUTES'] ?? [
        'exact' => [],
        'placeholder' => [],
        'regex' => [],
    ];

    $exactRoutes = $routeStore['exact'] ?? [];
    $preparedPlaceholderRoutes = [];
    foreach ($routeStore['placeholder'] ?? [] as $path => $handler) {
        $pattern = preg_quote($path, '#');
        $pattern = str_replace('\\{id\\}', '([0-9]+)', $pattern);
        $preparedPlaceholderRoutes[$path] = [
            'pattern' => '#^' . $pattern . '$#',
            'handler' => $handler,
        ];
    }
    $preparedRegexRoutes = [];
    foreach ($routeStore['regex'] ?? [] as $path => $handler) {
        $preparedRegexRoutes[$path] = [
            'pattern' => '#^' . $path . '$#',
            'handler' => $handler,
            'matched' => false,
        ];
    }
    // Higher priority should be given to more specific regex routes (longer patterns)
    uksort($preparedRegexRoutes, fn (string $a, string $b) => strlen($b) <=> strlen($a));

    // 1. Exact match
    $exactMatchSucceeded = isset($exactRoutes[$uri]);
    if ($exactMatchSucceeded) {
        ($exactRoutes[$uri])();
        return;
    }

    // 2. Placeholder match – `{id}`
    $placeholderMatchSucceeded = false;
    foreach ($preparedPlaceholderRoutes as $path => $routeInfo) {
        if (preg_match($routeInfo['pattern'], $uri, $matches)) {
            $_GET['id'] = isset($matches[1]) ? (int)$matches[1] : null;
            $placeholderMatchSucceeded = true;
            $routeInfo['handler']();
            return;
        }
    }

    // 3. Regex match – any path that was registered as a PCRE pattern
    $regexMatchSucceeded = false;
    foreach ($preparedRegexRoutes as $path => &$routeInfo) {
        $matched = preg_match($routeInfo['pattern'], $uri, $matches);
        $routeInfo['matched'] = (bool)$matched;
        if ($matched) {
            $regexMatchSucceeded = true;
            array_shift($matches);
            $routeInfo['handler'](...$matches);
            unset($routeInfo);
            return;
        }
    }
    unset($routeInfo);

    // If nothing matched, return 404
    http_response_code(404);
    // ===== ROUTER DEBUG BLOCK (404) =====
    // Toggle the flag to disable or remove this entire debug block easily.
    $routerDebug404Enabled = true;
    if ($routerDebug404Enabled) {
        $debugLines = [];
        $debugLines[] = '===== ROUTER DEBUG (404) =====';
        $debugLines[] = '';
        $debugLines[] = 'Raw REQUEST_URI: ' . $rawRequestUri;
        $debugLines[] = 'Parsed URI path: ' . $parsedUriPath;
        $debugLines[] = 'Calculated basePath: ' . ($basePath === '' ? '/' : $basePath);
        $debugLines[] = 'Final normalized path: ' . $finalNormalizedPath;
        $debugLines[] = '';
        $debugLines[] = 'Exact routes:';
        if ($exactRoutes === []) {
            $debugLines[] = '  (none)';
        } else {
            foreach (array_keys($exactRoutes) as $route) {
                $debugLines[] = '  - ' . $route;
            }
        }
        $debugLines[] = '';
        $debugLines[] = '{id} placeholder routes:';
        if ($preparedPlaceholderRoutes === []) {
            $debugLines[] = '  (none)';
        } else {
            foreach ($preparedPlaceholderRoutes as $route => $info) {
                $debugLines[] = '  - ' . $route;
                $debugLines[] = '      pattern: ' . $info['pattern'];
            }
        }
        $debugLines[] = '';
        $debugLines[] = 'Regex routes:';
        if ($preparedRegexRoutes === []) {
            $debugLines[] = '  (none)';
        } else {
            foreach ($preparedRegexRoutes as $route => $info) {
                $debugLines[] = '  - ' . $route;
                $debugLines[] = '      pattern: ' . $info['pattern'];
                $debugLines[] = '      preg_match: ' . ($info['matched'] ? 'succeeded' : 'failed');
            }
        }
        $debugLines[] = '';
        $debugLines[] = 'Exact match failed: ' . ($exactMatchSucceeded ? 'no' : 'yes');
        $debugLines[] = 'Placeholder match failed: ' . ($placeholderMatchSucceeded ? 'no' : 'yes');
        $debugLines[] = 'Regex match failed: ' . ($regexMatchSucceeded ? 'no' : 'yes');
        echo '<pre>' . implode("\n", $debugLines) . '</pre>';
    }
    // ===== END ROUTER DEBUG BLOCK =====
    echo '404 Not Found';
}

/**
 * Normalize a path by ensuring it starts with a forward slash, trimming
 * trailing slashes, and collapsing empty paths to `/`. This helper is used
 * by the router to maintain a consistent route key format.
 *
 * @param string $path
 * @param bool   $preserveBackslashes Skip the `\` → `/` conversion (useful for regex routes).
 * @return string
 */
function normalize_path(string $path, bool $preserveBackslashes = false): string
{
    $path = $preserveBackslashes ? $path : str_replace('\\', '/', $path);

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
