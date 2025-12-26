<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/match_period_repository.php';

/**
 * Normalize incoming event data to satisfy NOT NULL columns and enums.
 *
 * @param array<string, mixed> $data
 * @return array<string, mixed>
 */
function normalize_event_payload(array $data): array
{
          $matchSecond = isset($data['match_second']) && $data['match_second'] !== '' ? (int)$data['match_second'] : 0;
          if ($matchSecond < 0) {
                    $matchSecond = 0;
          }

          $minute = isset($data['minute']) && $data['minute'] !== '' ? (int)$data['minute'] : (int)floor($matchSecond / 60);
          if ($minute < 0) {
                    $minute = 0;
          }

          $minuteExtra = isset($data['minute_extra']) && $data['minute_extra'] !== '' ? (int)$data['minute_extra'] : 0;
          if ($minuteExtra < 0) {
                    $minuteExtra = 0;
          }

          $teamSide = isset($data['team_side']) ? (string)$data['team_side'] : 'unknown';
          if (!in_array($teamSide, ['home', 'away', 'unknown'], true)) {
                    $teamSide = 'unknown';
          }

          $eventTypeId = isset($data['event_type_id']) ? (int)$data['event_type_id'] : 0;
          if ($eventTypeId <= 0) {
                    throw new \InvalidArgumentException('event_type_required');
          }

          $importance = isset($data['importance']) && $data['importance'] !== '' ? (int)$data['importance'] : 3;
          $importance = max(1, min(5, $importance));

          $phase = isset($data['phase']) && $data['phase'] !== '' ? (string)$data['phase'] : 'unknown';
          $phase = in_array($phase, ['unknown', 'build_up', 'transition', 'defensive_block', 'set_piece'], true) ? $phase : 'unknown';

          return [
                    'period_id' => isset($data['period_id']) && $data['period_id'] !== '' ? (int)$data['period_id'] : null,
                    'match_second' => $matchSecond,
                    'minute' => $minute,
                    'minute_extra' => $minuteExtra,
                    'team_side' => $teamSide,
                    'event_type_id' => $eventTypeId,
                    'importance' => $importance,
                    'phase' => $phase,
                    'match_player_id' => isset($data['match_player_id']) && $data['match_player_id'] !== '' ? (int)$data['match_player_id'] : null,
                    'opponent_detail' => $data['opponent_detail'] ?? null,
                    'outcome' => $data['outcome'] ?? null,
                    'zone' => $data['zone'] ?? null,
                    'notes' => $data['notes'] ?? null,
          ];
}

/**
 * Trim a string and return null when the result is empty.
 *
 * @param mixed $value
 * @return string|null
 */
function event_validation_trim_nullable_string($value): ?string
{
          if ($value === null) {
                    return null;
          }

          $trimmed = trim((string)$value);
          return $trimmed === '' ? null : $trimmed;
}

/**
 * Validate and normalise an event payload coming from the APIs.
 *
 * @param array<string, mixed> $input
 * @param int $matchId
 * @return array<string, mixed>
 * @throws \RuntimeException
 */
function validate_event_payload(array $input, int $matchId): array
{
          $matchSecond = isset($input['match_second']) && $input['match_second'] !== '' ? (int)$input['match_second'] : 0;
          if ($matchSecond < 0) {
                    throw new \RuntimeException('invalid_payload');
          }

          $eventTypeId = isset($input['event_type_id']) && $input['event_type_id'] !== '' ? (int)$input['event_type_id'] : 0;
          if ($eventTypeId <= 0) {
                    throw new \RuntimeException('invalid_payload');
          }

          $periodId = isset($input['period_id']) && $input['period_id'] !== '' ? (int)$input['period_id'] : null;
          $minute = isset($input['minute']) && $input['minute'] !== '' ? (int)$input['minute'] : null;
          $minuteExtra = isset($input['minute_extra']) && $input['minute_extra'] !== '' ? (int)$input['minute_extra'] : null;
          $teamSide = $input['team_side'] ?? 'unknown';
          if (!in_array($teamSide, ['home', 'away', 'unknown'], true)) {
                    $teamSide = 'unknown';
          }

          $importance = isset($input['importance']) && $input['importance'] !== '' ? (int)$input['importance'] : 3;
          $importance = max(1, min(5, $importance));

          $phase = trim((string)($input['phase'] ?? ''));
          $matchPlayerId = isset($input['match_player_id']) && $input['match_player_id'] !== '' ? (int)$input['match_player_id'] : null;

          $prepared = [
                    'period_id' => $periodId,
                    'match_second' => $matchSecond,
                    'minute' => $minute,
                    'minute_extra' => $minuteExtra,
                    'team_side' => $teamSide,
                    'event_type_id' => $eventTypeId,
                    'importance' => $importance,
                    'phase' => $phase !== '' ? $phase : null,
                    'match_player_id' => $matchPlayerId,
                    'opponent_detail' => event_validation_trim_nullable_string($input['opponent_detail'] ?? null),
                    'outcome' => event_validation_trim_nullable_string($input['outcome'] ?? null),
                    'zone' => event_validation_trim_nullable_string($input['zone'] ?? null),
                    'notes' => event_validation_trim_nullable_string($input['notes'] ?? null),
          ];

          try {
                    $normalized = normalize_event_payload($prepared);
          } catch (\InvalidArgumentException $e) {
                    throw new \RuntimeException('invalid_payload', 0, $e);
          }

          if ($normalized['period_id'] !== null) {
                    $stmt = db()->prepare('SELECT id FROM match_periods WHERE id = :id AND match_id = :match_id LIMIT 1');
                    $stmt->execute(['id' => $normalized['period_id'], 'match_id' => $matchId]);
                    if (!$stmt->fetch()) {
                              throw new \RuntimeException('invalid_period');
                    }
          } else {
                    $autoPeriods = get_match_periods($matchId);
                    foreach ($autoPeriods as $period) {
                              $start = $period['start_second'] ?? null;
                              $end = $period['end_second'] ?? null;
                              if ($start !== null && $end !== null && $normalized['match_second'] >= $start && $normalized['match_second'] <= $end) {
                                        $normalized['period_id'] = (int)$period['id'];
                                        break;
                              }
                              if ($start !== null && $end === null && $normalized['match_second'] >= $start) {
                                        $normalized['period_id'] = (int)$period['id'];
                                        break;
                              }
                    }
          }

          if ($normalized['match_player_id'] !== null) {
                    $stmt = db()->prepare('SELECT id FROM match_players WHERE id = :id AND match_id = :match_id LIMIT 1');
                    $stmt->execute(['id' => $normalized['match_player_id'], 'match_id' => $matchId]);
                    if (!$stmt->fetch()) {
                              throw new \RuntimeException('invalid_player');
                    }
          }

          return $normalized;
}
