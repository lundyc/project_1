<?php
require_once __DIR__ . '/../../app/lib/auth.php';
auth_boot();
require_auth();

header('Content-Type: application/json');

function user_has_admin_role(): bool {
    $roles = $_SESSION['roles'] ?? [];
    return in_array('platform_admin', $roles, true) || in_array('club_admin', $roles, true);
}

if (!user_has_admin_role()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Forbidden']);
    exit;
}

$result = [
    'ok' => true,
    'actions' => [],
];

// Clear PHP OPcache if available
if (function_exists('opcache_reset')) {
    $ok = @opcache_reset();
    $result['actions'][] = ['action' => 'opcache_reset', 'status' => $ok ? 'ok' : 'failed'];
} else {
    $result['actions'][] = ['action' => 'opcache_reset', 'status' => 'unavailable'];
}

// Clear APCu cache if available
if (function_exists('apcu_clear_cache')) {
    @apcu_clear_cache();
    $result['actions'][] = ['action' => 'apcu_clear_cache', 'status' => 'ok'];
} else {
    $result['actions'][] = ['action' => 'apcu_clear_cache', 'status' => 'unavailable'];
}

// Small header-based cache bust for proxies/CDNs
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

echo json_encode($result);
?>
