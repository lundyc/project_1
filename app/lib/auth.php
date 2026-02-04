<?php

function auth_boot()
{
          if (session_status() === PHP_SESSION_NONE) {
                    $config = require __DIR__ . '/../../config/config.php';
                    
                    // Set secure session cookie parameters
                    $lifetime = (int)($config['session']['lifetime'] ?? 0);
                    $isSecure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
                    
                    session_set_cookie_params([
                              'lifetime' => $lifetime,
                              'path' => '/',
                              'domain' => '',
                              'secure' => $isSecure,  // Only transmit over HTTPS
                              'httponly' => true,     // Prevent JavaScript access
                              'samesite' => 'Strict'  // CSRF protection
                    ]);
                    
                    session_name($config['session']['name']);
                    session_start();
          }
          
          // Session timeout validation (1 hour of inactivity)
          $timeout = 3600; // 1 hour
          if (isset($_SESSION['last_activity'])) {
                    if (time() - $_SESSION['last_activity'] > $timeout) {
                              session_unset();
                              session_destroy();
                              if (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) {
                                        http_response_code(401);
                                        echo json_encode(['ok' => false, 'error' => 'session_expired']);
                                        exit;
                              }
                              return; // Will be caught by require_auth()
                    }
          }
          $_SESSION['last_activity'] = time();
          
          // Regenerate session on privilege escalation
          if (isset($_SESSION['pending_role_change'])) {
                    session_regenerate_id(true);
                    unset($_SESSION['pending_role_change']);
          }

          // Set default club ID for platform admins if not set
          if (isset($_SESSION['roles']) && in_array('platform_admin', $_SESSION['roles'], true)) {
                    if (empty($_SESSION['admin_player_club_id'])) {
                              require_once __DIR__ . '/club_repository.php';
                              $clubs = get_all_clubs();
                              if (!empty($clubs)) {
                                        $_SESSION['admin_player_club_id'] = (int)$clubs[0]['id'];
                              }
                    }
          }
}

/*
|--------------------------------------------------------------------------
| Auth state helpers
|--------------------------------------------------------------------------
*/

function current_user(): ?array
{
          return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
          return current_user() !== null;
}

function logout()
{
          session_destroy();
          redirect('/login');
}

/*
|--------------------------------------------------------------------------
| Guards
|--------------------------------------------------------------------------
*/

function require_auth()
{
          if (!is_logged_in()) {
                    redirect('/login');
          }
}

function require_role(string $roleKey)
{
          require_auth();

          $roles = $_SESSION['roles'] ?? [];

          if (!in_array($roleKey, $roles, true)) {
                    // Log unauthorized access attempt
                    if (function_exists('log_unauthorized_access')) {
                              $resource = $_SERVER['REQUEST_URI'] ?? 'unknown';
                              log_unauthorized_access($_SESSION['user_id'] ?? null, $resource, $roleKey);
                    }
                    http_response_code(403);
                    echo '403 Forbidden';
                    exit;
          }
}

function user_has_role(string $roleKey): bool
{
          $roles = $_SESSION['roles'] ?? [];

          return in_array($roleKey, $roles, true);
}

function require_club_admin_access(): array
{
          auth_boot();
          require_auth();

          $roles = $_SESSION['roles'] ?? [];
          $user = current_user();
          $isPlatformAdmin = in_array('platform_admin', $roles, true);
          $isClubAdmin = in_array('club_admin', $roles, true);

          if (!$isPlatformAdmin && !$isClubAdmin) {
                    log_access_denied('require_club_admin_access', 'missing-role');
                    http_response_code(403);
                    echo '403 Forbidden';
                    exit;
          }

          $clubId = $isClubAdmin ? (int)($user['club_id'] ?? 0) : 0;

          if ($isPlatformAdmin) {
                    $requestedClubId = isset($_GET['club_id']) ? (int)$_GET['club_id'] : 0;

                    if ($requestedClubId > 0) {
                              require_once __DIR__ . '/club_repository.php';
                              if (!get_club_by_id($requestedClubId)) {
                                        log_access_denied('require_club_admin_access', 'invalid-club');
                                        http_response_code(403);
                                        echo '403 Forbidden';
                                        exit;
                              }

                              $clubId = $requestedClubId;
                              $_SESSION['admin_player_club_id'] = $clubId;
                    } elseif (!empty($_SESSION['admin_player_club_id'])) {
                              $clubId = (int)$_SESSION['admin_player_club_id'];
                    }

                    if ($clubId <= 0) {
                              require_once __DIR__ . '/club_repository.php';
                              $clubs = get_all_clubs();
                              if (!empty($clubs)) {
                                        $clubId = (int)($clubs[0]['id'] ?? 0);
                                        $_SESSION['admin_player_club_id'] = $clubId;
                              }
                    }
          }

          if ($clubId <= 0) {
                    log_access_denied('require_club_admin_access', 'missing-club');
                    http_response_code(403);
                    echo '403 Forbidden';
                    exit;
          }

          $_SESSION['admin_player_club_id'] = $clubId;

          return [
                    'user' => $user,
                    'roles' => $roles,
                    'club_id' => $clubId,
          ];
}


function base_path(): string
{
          static $base;

          if ($base !== null) {
                    return $base;
          }

          $config = require __DIR__ . '/../../config/config.php';
          $base = rtrim($config['app']['base_path'] ?? '', '/');

          return $base;
}

function redirect(string $path)
{
          if ($path === '' || $path[0] !== '/') {
                    $path = '/' . ltrim($path, '/');
          }

          $base = base_path();
          $location = ($base ? $base : '') . $path;

          header('Location: ' . $location);
          exit;
}

function log_access_denied(string $middleware, string $result): void
{
          auth_boot();

          $route = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
          $userId = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : 0;
          $roles = $_SESSION['roles'] ?? [];
          $isAdmin = in_array('platform_admin', $roles, true) || in_array('club_admin', $roles, true);
          $message = sprintf(
                    '[access-denied] route=%s middleware=%s result=%s user_id=%d is_admin=%d',
                    $route,
                    $middleware,
                    $result,
                    $userId,
                    $isAdmin ? 1 : 0
          );
          error_log($message);
}
