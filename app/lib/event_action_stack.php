<?php

/**
 * Session-based undo/redo stack for event mutations.
 */

require_once __DIR__ . '/event_repository.php';

/**
 * @return array{past: array, future: array}
 */
function get_event_action_stack(int $matchId, int $userId): array
{
          if (session_status() !== PHP_SESSION_ACTIVE) {
                    session_start();
          }
          if (!isset($_SESSION['event_action_stack'])) {
                    $_SESSION['event_action_stack'] = [];
          }
          if (!isset($_SESSION['event_action_stack'][$matchId])) {
                    $_SESSION['event_action_stack'][$matchId] = [];
          }
          if (!isset($_SESSION['event_action_stack'][$matchId][$userId])) {
                    $_SESSION['event_action_stack'][$matchId][$userId] = ['past' => [], 'future' => []];
          }
          return $_SESSION['event_action_stack'][$matchId][$userId];
}

/**
 * Persist the action stack for a match/user.
 *
 * @param array{past: array, future: array} $stack
 */
function set_event_action_stack(int $matchId, int $userId, array $stack): void
{
          if (session_status() !== PHP_SESSION_ACTIVE) {
                    session_start();
          }
          if (!isset($_SESSION['event_action_stack'])) {
                    $_SESSION['event_action_stack'] = [];
          }
          if (!isset($_SESSION['event_action_stack'][$matchId])) {
                    $_SESSION['event_action_stack'][$matchId] = [];
          }
          $_SESSION['event_action_stack'][$matchId][$userId] = [
                    'past' => array_values($stack['past'] ?? []),
                    'future' => array_values($stack['future'] ?? []),
          ];
}

function get_event_action_stack_status(int $matchId, int $userId): array
{
          $stack = get_event_action_stack($matchId, $userId);
          return [
                    'canUndo' => !empty($stack['past']),
                    'canRedo' => !empty($stack['future']),
          ];
}

/**
 * @param array $action
 */
function push_action_to_past(int $matchId, int $userId, array $action, bool $clearFuture = false): void
{
          $stack = get_event_action_stack($matchId, $userId);
          $stack['past'][] = $action;
          while (count($stack['past']) > 10) {
                    array_shift($stack['past']);
          }
          if ($clearFuture) {
                    $stack['future'] = [];
          }
          set_event_action_stack($matchId, $userId, $stack);
}

function push_action_to_future(int $matchId, int $userId, array $action): void
{
          $stack = get_event_action_stack($matchId, $userId);
          $stack['future'][] = $action;
          while (count($stack['future']) > 10) {
                    array_shift($stack['future']);
          }
          set_event_action_stack($matchId, $userId, $stack);
}

function pop_action_from_past(int $matchId, int $userId): ?array
{
          $stack = get_event_action_stack($matchId, $userId);
          if (empty($stack['past'])) {
                    return null;
          }
          $action = array_pop($stack['past']);
          set_event_action_stack($matchId, $userId, $stack);
          return $action;
}

function pop_action_from_future(int $matchId, int $userId): ?array
{
          $stack = get_event_action_stack($matchId, $userId);
          if (empty($stack['future'])) {
                    return null;
          }
          $action = array_pop($stack['future']);
          set_event_action_stack($matchId, $userId, $stack);
          return $action;
}

function record_event_action(int $matchId, int $userId, array $action): void
{
          $action['timestamp'] = time();
          push_action_to_past($matchId, $userId, $action, true);
}

/**
 * @return int[]
 */
function extract_event_tag_ids(array $event): array
{
          if (empty($event['tags']) || !is_array($event['tags'])) {
                    return [];
          }
          return array_values(array_map(
                    fn($tag) => isset($tag['id']) ? (int)$tag['id'] : 0,
                    $event['tags']
          ));
}

function build_event_payload_from_action(array $event): array
{
          return [
                    'period_id' => isset($event['period_id']) && $event['period_id'] !== '' ? (int)$event['period_id'] : null,
                    'match_second' => isset($event['match_second']) ? (int)$event['match_second'] : 0,
                    'minute' => isset($event['minute']) && $event['minute'] !== '' ? (int)$event['minute'] : null,
                    'minute_extra' => isset($event['minute_extra']) && $event['minute_extra'] !== '' ? (int)$event['minute_extra'] : null,
                    'team_side' => $event['team_side'] ?? 'unknown',
                    'event_type_id' => isset($event['event_type_id']) ? (int)$event['event_type_id'] : 0,
                    'importance' => isset($event['importance']) ? (int)$event['importance'] : 3,
                    'phase' => $event['phase'] ?? null,
                    'match_player_id' => isset($event['match_player_id']) && $event['match_player_id'] !== '' ? (int)$event['match_player_id'] : null,
                    'opponent_detail' => $event['opponent_detail'] ?? null,
                    'outcome' => $event['outcome'] ?? null,
                    'zone' => $event['zone'] ?? null,
                    'notes' => $event['notes'] ?? null,
          ];
}

function undo_last_action(int $matchId, int $userId): ?array
{
          $action = pop_action_from_past($matchId, $userId);
          if (!$action || empty($action['type'])) {
                    return null;
          }
          $resultEvent = null;
          $auditBefore = null;
          $auditAfter = null;
          $auditType = null;
          $auditEventId = null;

          switch ($action['type']) {
                    case 'create':
                              $eventId = isset($action['after']['id']) ? (int)$action['after']['id'] : 0;
                              if ($eventId <= 0) {
                                        return null;
                              }
                              $auditBefore = $action['after'];
                              $auditAfter = null;
                              $auditType = 'delete';
                              $auditEventId = $eventId;
                              event_delete($eventId, $userId);
                              $resultEvent = null;
                              break;

                    case 'delete':
                              $payload = build_event_payload_from_action($action['before'] ?? []);
                              $tagIds = extract_event_tag_ids($action['before'] ?? []);
                              $newEventId = event_create($matchId, $payload, $tagIds, $userId);
                              $restoredEvent = event_get_by_id($newEventId);
                              if (!$restoredEvent) {
                                        return null;
                              }
                              $action['before'] = $restoredEvent;
                              $auditBefore = null;
                              $auditAfter = $restoredEvent;
                              $auditType = 'create';
                              $auditEventId = $newEventId;
                              $resultEvent = $restoredEvent;
                              break;

                    case 'update':
                              $eventId = isset($action['before']['id']) ? (int)$action['before']['id'] : 0;
                              if ($eventId <= 0) {
                                        return null;
                              }
                              $payload = build_event_payload_from_action($action['before']);
                              $tagIds = extract_event_tag_ids($action['before']);
                              $auditBefore = $action['after'];
                              $auditAfter = $action['before'];
                              $auditType = 'update';
                              $auditEventId = $eventId;
                              event_update($eventId, $payload, $tagIds, $userId);
                              $resultEvent = event_get_by_id($eventId);
                              break;

                    default:
                              return null;
          }

          $action['timestamp'] = time();
          push_action_to_future($matchId, $userId, $action);

          return [
                    'event' => $resultEvent,
                    'audit_type' => $auditType,
                    'audit_before' => $auditBefore,
                    'audit_after' => $auditAfter,
                    'audit_event_id' => $auditEventId,
          ];
}

function redo_last_action(int $matchId, int $userId): ?array
{
          $action = pop_action_from_future($matchId, $userId);
          if (!$action || empty($action['type'])) {
                    return null;
          }
          $resultEvent = null;
          $auditBefore = null;
          $auditAfter = null;
          $auditType = null;
          $auditEventId = null;

          switch ($action['type']) {
                    case 'create':
                              $payload = build_event_payload_from_action($action['after'] ?? []);
                              $tagIds = extract_event_tag_ids($action['after'] ?? []);
                              $newEventId = event_create($matchId, $payload, $tagIds, $userId);
                              $restoredEvent = event_get_by_id($newEventId);
                              if (!$restoredEvent) {
                                        return null;
                              }
                              $action['after'] = $restoredEvent;
                              $auditBefore = null;
                              $auditAfter = $restoredEvent;
                              $auditType = 'create';
                              $auditEventId = $newEventId;
                              $resultEvent = $restoredEvent;
                              break;

                    case 'delete':
                              $eventId = isset($action['before']['id']) ? (int)$action['before']['id'] : 0;
                              if ($eventId <= 0) {
                                        return null;
                              }
                              $targetEvent = event_get_by_id($eventId);
                              if (!$targetEvent) {
                                        return null;
                              }
                              $action['before'] = $targetEvent;
                              $auditBefore = $targetEvent;
                              $auditAfter = null;
                              $auditType = 'delete';
                              $auditEventId = $eventId;
                              event_delete($eventId, $userId);
                              $resultEvent = null;
                              break;

                    case 'update':
                              $eventId = isset($action['after']['id']) ? (int)$action['after']['id'] : 0;
                              if ($eventId <= 0) {
                                        return null;
                              }
                              $payload = build_event_payload_from_action($action['after']);
                              $tagIds = extract_event_tag_ids($action['after']);
                              $auditBefore = $action['before'];
                              $auditType = 'update';
                              $auditEventId = $eventId;
                              event_update($eventId, $payload, $tagIds, $userId);
                              $updatedEvent = event_get_by_id($eventId);
                              if (!$updatedEvent) {
                                        return null;
                              }
                              $action['after'] = $updatedEvent;
                              $auditAfter = $updatedEvent;
                              $resultEvent = $updatedEvent;
                              break;

                    default:
                              return null;
          }

          $action['timestamp'] = time();
          push_action_to_past($matchId, $userId, $action);

          return [
                    'event' => $resultEvent,
                    'audit_type' => $auditType,
                    'audit_before' => $auditBefore,
                    'audit_after' => $auditAfter,
                    'audit_event_id' => $auditEventId,
          ];
}
