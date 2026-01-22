<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/match_period_repository.php';

/**
 * Auto-calculate period and stoppage time based on event_time and period definitions.
 * 
 * @param int $matchId
 * @param int $matchSecond The event time in seconds
 * @return array{period_id: ?int, minute_extra: int} Period ID and calculated stoppage minutes
 */
function calculate_period_from_event_time(int $matchId, int $matchSecond): array
{
          $periods = get_match_periods($matchId);
          $periodId = null;
          $minuteExtra = 0;

          // Find the period that contains this event
          foreach ($periods as $period) {
                    $start = $period['start_second'] ?? null;
                    $end = $period['end_second'] ?? null;

                    if ($start === null) {
                              continue;
                    }

                    // Event is within this period's time range
                    if ($end !== null && $matchSecond >= $start && $matchSecond <= $end) {
                              $periodId = (int)$period['id'];
                              break;
                    }
                    // Event is after period start but period hasn't ended yet
                    if ($end === null && $matchSecond >= $start) {
                              $periodId = (int)$period['id'];
                              break;
                    }
                    // Event is after period end - it's stoppage time
                    if ($end !== null && $matchSecond > $end) {
                              $periodId = (int)$period['id'];
                              // Calculate how many minutes past the end
                              $secondsPastEnd = $matchSecond - $end;
                              $minuteExtra = (int)ceil($secondsPastEnd / 60);
                              break;
                    }
          }

          return [
                    'period_id' => $periodId,
                    'minute_extra' => $minuteExtra,
          ];
}

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

          $isPenalty = isset($data['is_penalty']) ? (int)$data['is_penalty'] : 0;
          $isPenalty = $isPenalty ? 1 : 0;

          return [
                    'period_id' => isset($data['period_id']) && $data['period_id'] !== '' ? (int)$data['period_id'] : null,
                    'match_second' => $matchSecond,
                    'minute' => $minute,
                    'minute_extra' => $minuteExtra,
                    'team_side' => $teamSide,
                    'event_type_id' => $eventTypeId,
                    'importance' => $importance,
                    'phase' => $phase,
                    'is_penalty' => $isPenalty,
                    'match_player_id' => isset($data['match_player_id']) && $data['match_player_id'] !== '' ? (int)$data['match_player_id'] : null,
                    'opponent_detail' => $data['opponent_detail'] ?? null,
                    'outcome' => $data['outcome'] ?? null,
                    'zone' => $data['zone'] ?? null,
                    'notes' => $data['notes'] ?? null,
          ];
}

/**
 * Return the type_key for a given event type id.
 *
 * @param int $eventTypeId
 * @return string|null
 */
function event_validation_get_event_type_key(int $eventTypeId): ?string
{
          static $cache = [];

          if (array_key_exists($eventTypeId, $cache)) {
                    return $cache[$eventTypeId];
          }

          $stmt = db()->prepare('SELECT type_key FROM event_types WHERE id = :id LIMIT 1');
          $stmt->execute(['id' => $eventTypeId]);
          $row = $stmt->fetch(\PDO::FETCH_ASSOC);
          $cache[$eventTypeId] = $row['type_key'] ?? null;

          return $cache[$eventTypeId];
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

          $typeKey = event_validation_get_event_type_key($eventTypeId);
          if ($matchSecond === 0 && $typeKey !== 'period_start') {
                    error_log(sprintf(
                              'Event created at 0s outside period marker | match=%d event_type_id=%d type_key=%s',
                              $matchId,
                              $eventTypeId,
                              $typeKey ?? 'unknown'
                    ));
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
                    // Auto-calculate period and stoppage time from match_second
                    $calculated = calculate_period_from_event_time($matchId, $normalized['match_second']);
                    $normalized['period_id'] = $calculated['period_id'];
                    // Only set minute_extra if not already provided and we calculated stoppage time
                    if (($normalized['minute_extra'] ?? 0) === 0 && $calculated['minute_extra'] > 0) {
                              $normalized['minute_extra'] = $calculated['minute_extra'];
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
