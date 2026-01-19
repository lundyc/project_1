<?php

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/formation_repository.php';

auth_boot();
require_auth();

function respond_json(int $status, array $payload): void
{
          http_response_code($status);
          header('Content-Type: application/json');
          echo json_encode($payload);
          exit;
}

$filters = [];
if (isset($_GET['format'])) {
          $filters['format'] = trim((string)$_GET['format']);
}
if (isset($_GET['is_fixed'])) {
          $value = strtolower(trim((string)$_GET['is_fixed']));
          $filters['is_fixed'] = $value === '1' || $value === 'true' || $value === 'yes';
}

$formations = get_formations_with_positions($filters);
respond_json(200, ['ok' => true, 'formations' => $formations]);
