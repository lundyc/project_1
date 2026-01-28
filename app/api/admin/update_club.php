<?php

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/club_repository.php';

auth_boot();
require_role('platform_admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
          http_response_code(405);
          exit;
}

$clubId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$name = trim($_POST['name'] ?? '');

if ($clubId <= 0) {
          $_SESSION['club_form_error'] = 'Club ID is required';
          redirect('/admin/clubs');
}

if ($clubId === 3) {
          $_SESSION['club_form_error'] = 'Opponents club cannot be edited';
          redirect('/admin/clubs');
}

if ($name === '') {
          $_SESSION['club_form_error'] = 'Club name is required';
          redirect('/admin/clubs/' . $clubId . '/edit');
}

if (!get_club_by_id($clubId)) {
          $_SESSION['club_form_error'] = 'Club not found';
          redirect('/admin/clubs');
}

try {
          $ok = update_club($clubId, $name);
          if (!$ok) {
                    $_SESSION['club_form_error'] = 'Unable to update club';
                    redirect('/admin/clubs/' . $clubId . '/edit');
          }
          $_SESSION['club_form_success'] = 'Club updated successfully';
} catch (\Throwable $e) {
          $_SESSION['club_form_error'] = 'Unable to update club';
          redirect('/admin/clubs/' . $clubId . '/edit');
}

redirect('/admin/clubs/' . $clubId . '/edit');
