<?php

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/csrf.php';
require_once __DIR__ . '/../../lib/match_repository.php';
require_once __DIR__ . '/../../lib/match_permissions.php';
require_once __DIR__ . '/../../lib/match_period_repository.php';

auth_boot();
require_auth();

// Validate CSRF token for state-changing operation
try {
    require_csrf_token();
} catch (CsrfException $e) {
    http_response_code(403);
    die('Invalid CSRF token');
}

$acceptsJson = (bool)preg_match('#json#i', $_SERVER['HTTP_ACCEPT'] ?? '') || (bool)preg_match('#json#i', $_SERVER['CONTENT_TYPE'] ?? '');
if ($acceptsJson) {
          header('Content-Type: application/json');
}

$payload = [];
$raw = file_get_contents('php://input');
if ($raw) {
          $decoded = json_decode($raw, true);
          if (is_array($decoded)) {
                    $payload = $decoded;
          }
}

$matchId = isset($matchId) ? (int)$matchId : (int)($_POST['match_id'] ?? ($payload['match_id'] ?? 0));

if ($matchId <= 0) {
          http_response_code(400);
          if ($acceptsJson) {
                    echo json_encode(['ok' => false, 'error' => 'match_id_required']);
                    exit;
          }
          exit;
}

$match = get_match($matchId);
if (!$match) {
          http_response_code(404);
          if ($acceptsJson) {
                    echo json_encode(['ok' => false, 'error' => 'not_found']);
                    exit;
          }
          echo 'Match not found';
          exit;
}

$user = current_user();
$roles = $_SESSION['roles'] ?? [];

if (!can_manage_match_for_club($user, $roles, (int)$match['club_id'])) {
          http_response_code(403);
          if ($acceptsJson) {
                    echo json_encode(['ok' => false, 'error' => 'forbidden']);
                    exit;
          }
          exit;
}

$payload = [];
$raw = file_get_contents('php://input');
if ($raw) {
          $decoded = json_decode($raw, true);
          if (is_array($decoded)) {
                    $payload = $decoded;
          }
}

$labels = $_POST['label'] ?? [];
$startMinutes = $_POST['start_minute'] ?? [];
$endMinutes = $_POST['end_minute'] ?? [];
$minutes = $_POST['minutes_planned'] ?? [];
$jsonPeriods = isset($payload['periods']) && is_array($payload['periods']) ? $payload['periods'] : null;

$periods = [];
if ($jsonPeriods !== null) {
          foreach ($jsonPeriods as $idx => $row) {
                    $label = trim((string)($row['label'] ?? ''));
                    if ($label === '') {
                              continue;
                    }
                    $startMinute = isset($row['start_minute']) && $row['start_minute'] !== '' ? (int)$row['start_minute'] : null;
                    $endMinute = isset($row['end_minute']) && $row['end_minute'] !== '' ? (int)$row['end_minute'] : null;
                    $minutesPlanned = $row['minutes_planned'] ?? null;

                    if ($minutesPlanned === null && $startMinute !== null && $endMinute !== null) {
                              $minutesPlanned = max(0, $endMinute - $startMinute);
                    } elseif ($minutesPlanned === null && $endMinute !== null) {
                              $minutesPlanned = $endMinute;
                    }

                    $periods[] = [
                              'period_index' => count($periods),
                              'period_type' => $row['period_type'] ?? 'normal',
                              'label' => $label,
                              'minutes_planned' => $minutesPlanned !== null ? (int)$minutesPlanned : null,
                    ];
          }
} else {
          foreach ($labels as $i => $label) {
                    $label = trim((string)$label);
                    $minutesPlannedRaw = $minutes[$i] ?? null;
                    $minutesPlanned = null;

                    if ($label === '') {
                              continue;
                    }

                    if ($minutesPlannedRaw !== null && $minutesPlannedRaw !== '') {
                              if (!is_numeric($minutesPlannedRaw)) {
                                        if ($acceptsJson) {
                                                  http_response_code(422);
                                                  echo json_encode(['ok' => false, 'error' => 'Minutes must be numeric']);
                                                  exit;
                                        }
                                        $_SESSION['periods_error'] = 'Minutes must be numeric';
                                        redirect('/matches/' . $matchId . '/periods');
                              }
                              $minutesPlanned = (int)$minutesPlannedRaw;
                    }

                    $periods[] = [
                              'period_index' => count($periods),
                              'period_type' => 'normal',
                              'label' => $label,
                              'minutes_planned' => $minutesPlanned,
                    ];
          }
}

if (empty($periods)) {
          if ($acceptsJson) {
                    http_response_code(422);
                    echo json_encode(['ok' => false, 'error' => 'Add at least one period']);
                    exit;
          }
          $_SESSION['periods_error'] = 'Add at least one period';
          redirect('/matches/' . $matchId . '/periods');
}

try {
          replace_match_periods($matchId, $periods);
          if ($acceptsJson) {
                    echo json_encode(['ok' => true, 'periods' => get_match_periods($matchId)]);
                    exit;
          }
          $_SESSION['periods_success'] = 'Custom periods saved';
} catch (\Throwable $e) {
          if ($acceptsJson) {
                    http_response_code(500);
                    echo json_encode(['ok' => false, 'error' => 'Unable to save periods']);
                    exit;
          }
          $_SESSION['periods_error'] = 'Unable to save periods';
}

redirect('/matches/' . $matchId . '/periods');
