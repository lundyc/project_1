<?php

require_once __DIR__ . '/../lib/auth.php';

function require_admin(): void
{
          auth_boot();

          if (!is_logged_in()) {
                    log_access_denied('require_admin', 'not-logged-in');
                    http_response_code(403);
                    echo '403 Forbidden - admin access is required.';
                    exit;
          }

          $hasAdminRole = user_has_role('platform_admin') || user_has_role('club_admin');
          if (!$hasAdminRole) {
                    log_access_denied('require_admin', 'missing-role');
                    http_response_code(403);
                    echo '403 Forbidden - admin access is required.';
                    exit;
          }
}
