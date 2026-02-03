<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/match_period_repository.php';

/**
 * Auto-calculate period and stoppage time based on event_time and period definitions.
 * 
 * Strategy:
 * 1. EXACT MATCH: Find period where matchSecond falls within [start_second, end_second]
 * 2. ONGOING PERIOD: Find period where start_second is set but end_second is null (still active)
 * 3. STOPPAGE TIME: Find period where matchSecond > end_second and no subsequent period has started yet
 * 
 * This ensures events are assigned to the correct period even when there's a gap between periods (e.g., halftime).
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

          // STRATEGY 1: First pass - look for EXACT MATCH (event within period range)
          foreach ($periods as $period) {
                    $start = $period['start_second'] ?? null;
                    $end = $period['end_second'] ?? null;

                    // Event is within this period's completed time range
                    if ($start !== null && $end !== null && $matchSecond >= $start && $matchSecond <= $end) {
                              return [
                                        'period_id' => (int)$period['id'],
                                        'minute_extra' => 0,
                              ];
                    }
          }

          // STRATEGY 2: Second pass - look for ONGOING PERIOD (started but not ended)
          foreach ($periods as $period) {
                    $start = $period['start_second'] ?? null;
                    $end = $period['end_second'] ?? null;

                    // Period has started but no end recorded yet, and event is after start
                    if ($start !== null && $end === null && $matchSecond >= $start) {
                              return [
                                        'period_id' => (int)$period['id'],
                                        'minute_extra' => 0,
                              ];
                    }
          }

          // STRATEGY 3: Third pass - look for STOPPAGE TIME (after a period ends but before next period starts)
          foreach ($periods as $period) {
                    $start = $period['start_second'] ?? null;
                    $end = $period['end_second'] ?? null;

                    // Event is after this period's end
                    if ($end !== null && $matchSecond > $end) {
                              // Check if any subsequent period has started
                              $nextPeriodStarted = false;
                              foreach ($periods as $p) {
                                        $nextStart = $p['start_second'] ?? null;
                                        // If there's a later period that has started before our event
                                        if ($nextStart !== null && $nextStart > $end && $matchSecond >= $nextStart) {
                                                  $nextPeriodStarted = true;
                                                  break;
                                        }
                              }

                              // If no subsequent period has started, this is stoppage time of current period
                              if (!$nextPeriodStarted) {
                                        $secondsPastEnd = $matchSecond - $end;
                                        $minuteExtra = (int)ceil($secondsPastEnd / 60);
                                        return [
                                                  'period_id' => (int)$period['id'],
                                                  'minute_extra' => $minuteExtra,
                                        ];
                              }
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

          $normalized = [
                    'period_id' => isset($data['period_id']) && $data['period_id'] !== '' ? (int)$data['period_id'] : null,
                    'match_second' => $matchSecond,
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

          $shotCoordFields = ['shot_origin_x', 'shot_origin_y', 'shot_target_x', 'shot_target_y'];
          foreach ($shotCoordFields as $field) {
                    if (!array_key_exists($field, $data)) {
                              continue;
                    }
                    $value = $data[$field];
                    $normalized[$field] = ($value === '' || $value === null) ? null : floatval($value);
          }

          return $normalized;
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
/**
 * Validate and normalise an event payload coming from the APIs.
 * Adds support for advanced shot schema for shot-type events.
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

        $shotCoordFields = ['shot_origin_x', 'shot_origin_y', 'shot_target_x', 'shot_target_y'];
        foreach ($shotCoordFields as $field) {
            if (!array_key_exists($field, $input)) {
                continue;
            }
            $value = $input[$field];
            if ($value === null || $value === '') {
                $prepared[$field] = null;
                continue;
            }
            if (!is_numeric($value)) {
                throw new \RuntimeException('shot_location_invalid');
            }
            $floatVal = floatval($value);
            if ($floatVal < 0.0 || $floatVal > 1.0 || is_nan($floatVal)) {
                throw new \RuntimeException('shot_location_out_of_range');
            }
            $prepared[$field] = $floatVal;
        }

        // --- Advanced Shot Data Model (Phase 1) ---
        // Only for shot-type events: add validated 'shot' field to payload if present and valid
        $shotEventKeys = ['shot', 'goal', 'shot_on_target'];
        if (in_array((string)$typeKey, $shotEventKeys, true)) {
            if (isset($input['shot']) && is_array($input['shot'])) {
                $shot = $input['shot'];
                $finalShot = [];
                // Required: outcome
                if (!isset($shot['outcome']) || !is_string($shot['outcome'])) {
                    throw new \RuntimeException('shot_outcome_required');
                }
                $validOutcomes = ['goal', 'saved', 'blocked', 'off_target', 'on_target', 'woodwork'];
                if (!in_array($shot['outcome'], $validOutcomes, true)) {
                    throw new \RuntimeException('shot_outcome_invalid');
                }
                $finalShot['outcome'] = $shot['outcome'];

                // Optional: body_part
                if (isset($shot['body_part'])) {
                    $validBodyParts = ['left_foot', 'right_foot', 'head', 'other'];
                    if (!in_array($shot['body_part'], $validBodyParts, true)) {
                        throw new \RuntimeException('shot_body_part_invalid');
                    }
                    $finalShot['body_part'] = $shot['body_part'];
                }

                // Optional: is_big_chance, is_one_on_one, from_set_piece (all bool)
                foreach (['is_big_chance', 'is_one_on_one', 'from_set_piece'] as $flag) {
                    if (array_key_exists($flag, $shot)) {
                        if (!is_bool($shot[$flag])) {
                            throw new \RuntimeException('shot_flag_invalid');
                        }
                        $finalShot[$flag] = $shot[$flag];
                    }
                }

                // Optional: location
                if (isset($shot['location'])) {
                    if (!is_array($shot['location'])) {
                        throw new \RuntimeException('shot_location_invalid');
                    }
                    $loc = $shot['location'];
                    // Both start and end must exist
                    if (!isset($loc['start'], $loc['end']) || !is_array($loc['start']) || !is_array($loc['end'])) {
                        throw new \RuntimeException('shot_location_start_end_required');
                    }
                    // Validate start
                    foreach (['x', 'y'] as $axis) {
                        if (!isset($loc['start'][$axis]) || !is_numeric($loc['start'][$axis])) {
                            throw new \RuntimeException('shot_location_start_invalid');
                        }
                        $val = floatval($loc['start'][$axis]);
                        if ($val < 0.0 || $val > 1.0 || is_nan($val)) {
                            throw new \RuntimeException('shot_location_start_out_of_range');
                        }
                        $loc['start'][$axis] = $val;
                    }
                    // Validate end
                    foreach (['x', 'y'] as $axis) {
                        if (!isset($loc['end'][$axis]) || !is_numeric($loc['end'][$axis])) {
                            throw new \RuntimeException('shot_location_end_invalid');
                        }
                        $val = floatval($loc['end'][$axis]);
                        if ($val < 0.0 || $val > 1.0 || is_nan($val)) {
                            throw new \RuntimeException('shot_location_end_out_of_range');
                        }
                        $loc['end'][$axis] = $val;
                    }
                    $finalShot['location'] = [
                        'start' => $loc['start'],
                        'end' => $loc['end'],
                    ];
                }

                // Never allow empty shot object
                if (count($finalShot) > 0) {
                    $prepared['shot'] = $finalShot;
                }
            }
        }
        // --- End Advanced Shot Data Model ---

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
