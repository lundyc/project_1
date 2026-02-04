<?php
// Only display errors in development environment
$isDevelopment = (getenv('APP_ENV') ?: 'production') === 'local';
ini_set('display_errors', $isDevelopment ? '1' : '0');
ini_set('display_startup_errors', $isDevelopment ? '1' : '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/match_repository.php';
require_once __DIR__ . '/../../lib/match_permissions.php';
require_once __DIR__ . '/../../lib/event_repository.php';
require_once __DIR__ . '/../../lib/event_validation.php';
require_once __DIR__ . '/../../lib/api_response.php';
require_once __DIR__ . '/../../lib/csrf.php';
require_once __DIR__ . '/../../lib/match_lock_service.php';
require_once __DIR__ . '/../../lib/match_lock_guard.php';
require_once __DIR__ . '/../../lib/match_version_service.php';
require_once __DIR__ . '/../../lib/audit_service.php';
require_once __DIR__ . '/../../lib/event_action_stack.php';
require_once __DIR__ . '/../../lib/match_period_repository.php';
require_once __DIR__ . '/../../lib/clip_generation_service.php';
require_once __DIR__ . '/../../lib/clip_job_service.php';
require_once __DIR__ . '/../../lib/rate_limit.php';

auth_boot();
require_auth();

// Rate limit event creation to prevent spam
require_rate_limit('event_create', 100, 60); // 100 events per minute

header('Content-Type: application/json');

try {
          require_csrf_token();
} catch (CsrfException $e) {
          api_error('invalid_csrf', 403, [], $e);
}

// Handle JSON input
$input = $_POST;
$raw = file_get_contents('php://input');
if (empty($input) && $raw) {
          $decoded = json_decode($raw, true);
          if (is_array($decoded)) {
                    $input = $decoded;
          }
}

$matchId = isset($matchId) ? (int)$matchId : (int)($input['match_id'] ?? 0);

if ($matchId <= 0) {
          api_error('invalid_match', 400);
}

$match = get_match($matchId);
if (!$match) {
          api_error('not_found', 404);
}

$user = current_user();
$roles = $_SESSION['roles'] ?? [];
$canEdit = in_array('platform_admin', $roles, true) || in_array('club_admin', $roles, true) || in_array('analyst', $roles, true);
$canManage = $canEdit && can_manage_match_for_club($user, $roles, (int)$match['club_id']);

if (!$canManage) {
          api_error('forbidden', 403);
}

// Skip lock requirement for match edit page
// try {
//           require_match_lock($matchId, (int)$user['id']);
// } catch (MatchLockException $e) {
//           error_log(sprintf('[lock-failed] match=%d user=%d reason=%s', $matchId, (int)$user['id'], $e->getMessage()));
//           api_error('lock_required', 200);
// }

$tagIds = array_filter(array_map('intval', $input['tag_ids'] ?? []));

try {
                    $payload = validate_event_payload($input, $matchId);
} catch (\RuntimeException $e) {
          error_log(sprintf('[event-validation] match=%d user=%d error=%s', $matchId, (int)$user['id'], $e->getMessage()));
          api_error($e->getMessage(), 422);
}

try {
                    $pdo = db();
                    $transactionOwned = !$pdo->inTransaction();
                    if ($transactionOwned) {
                                        $pdo->beginTransaction();
                    }

                    $createdEvents = [];
                    $autoRedCreated = false;

                    // Primary event
                    $eventId = event_create($matchId, $payload, $tagIds, (int)$user['id'], false);
                    $event = event_get_by_id($eventId);
                    if ($event) {
                                        $createdEvents[] = $event;
                                        record_event_action($matchId, (int)$user['id'], [
                                                            'type' => 'create',
                                                            'before' => null,
                                                            'after' => $event,
                                        ]);
                                        if (array_key_exists('shot_origin_x', $payload) || array_key_exists('shot_target_x', $payload)) {
                                                            error_log(sprintf(
                                                                                '[shot-location] create event=%d origin=(%s,%s) target=(%s,%s)',
                                                                                (int)$event['id'],
                                                                                $event['shot_origin_x'] ?? 'null',
                                                                                $event['shot_origin_y'] ?? 'null',
                                                                                $event['shot_target_x'] ?? 'null',
                                                                                $event['shot_target_y'] ?? 'null'
                                                            ));
                                        }
                    }

                    // Auto-red on second yellow
                    $isYellow = event_validation_get_event_type_key((int)$payload['event_type_id']) === 'yellow_card';
                    $matchPlayerId = $payload['match_player_id'] ?? null;
                    if ($isYellow && $matchPlayerId) {
                                        // Count yellows for this player (including the one just created)
                                        $yellowCountStmt = $pdo->prepare(
                                                            'SELECT COUNT(*)
                                                             FROM events e
                                                             JOIN event_types et ON et.id = e.event_type_id
                                                             WHERE e.match_id = :match_id
                                                                 AND e.match_player_id = :match_player_id
                                                                 AND et.type_key = :yellow_key'
                                        );
                                        $yellowCountStmt->execute([
                                                            'match_id' => $matchId,
                                                            'match_player_id' => $matchPlayerId,
                                                            'yellow_key' => 'yellow_card',
                                        ]);
                                        $yellowCount = (int)$yellowCountStmt->fetchColumn();

                                        // Only create a red if this is the second yellow and no red already exists for this player in this match
                                        if ($yellowCount >= 2) {
                                                            $existingRedStmt = $pdo->prepare(
                                                                                'SELECT COUNT(*)
                                                                                 FROM events e
                                                                                 JOIN event_types et ON et.id = e.event_type_id
                                                                                 WHERE e.match_id = :match_id
                                                                                     AND e.match_player_id = :match_player_id
                                                                                     AND et.type_key = :red_key'
                                                            );
                                                            $existingRedStmt->execute([
                                                                                'match_id' => $matchId,
                                                                                'match_player_id' => $matchPlayerId,
                                                                                'red_key' => 'red_card',
                                                            ]);

                                                            if ((int)$existingRedStmt->fetchColumn() === 0) {
                                                                                $redTypeId = ensure_event_type_exists((int)$match['club_id'], 'red_card', 'Red Card', 5);
                                                                                $redPayload = $payload;
                                                                                $redPayload['event_type_id'] = $redTypeId;
                                                                                $redPayload['outcome'] = $redPayload['outcome'] ?? null;

                                                                                $redEventId = event_create($matchId, $redPayload, [], (int)$user['id'], false);
                                                                                $redEvent = event_get_by_id($redEventId);
                                                                                if ($redEvent) {
                                                                                                    $autoRedCreated = true;
                                                                                                    $createdEvents[] = $redEvent;
                                                                                                    record_event_action($matchId, (int)$user['id'], [
                                                                                                                        'type' => 'create',
                                                                                                                        'before' => null,
                                                                                                                        'after' => $redEvent,
                                                                                                    ]);
                                                                                }
                                                            }
                                        }
                    }

                    if ($transactionOwned) {
                                        $pdo->commit();
                    }

                    $version = bump_events_version($matchId);

                    foreach ($createdEvents as $created) {
                                        audit((int)$match['club_id'], (int)$user['id'], 'event', (int)$created['id'], 'create', null, json_encode($created));
                                        try {
                                                            clip_generation_service_handle_event_save($match, $created);
                                        } catch (\Throwable $e) {
                                                            error_log(sprintf('[clip-generation] match=%d event=%d create-error=%s', $matchId, (int)$created['id'], $e->getMessage()));
                                        }

                                        try {
                                                            ClipJobService::createFromEvent($created);
                                        } catch (\Throwable $e) {
                                                            error_log(sprintf('[clip-job] match=%d event=%d create-error=%s', $matchId, (int)$created['id'], $e->getMessage()));
                                        }
                    }

                    api_success([
                                        'event' => $event,
                                        'auto_red_created' => $autoRedCreated,
                                        'meta' => [
                                                            'events_version' => $version,
                                                            'action_stack' => get_event_action_stack_status($matchId, (int)$user['id']),
                                        ],
                    ]);
} catch (\Throwable $e) {
                    if (isset($pdo) && $pdo->inTransaction()) {
                                        $pdo->rollBack();
                    }
          api_error('server_error', 500, [], $e);
}
