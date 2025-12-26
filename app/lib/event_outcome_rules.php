<?php

/**
 * Build a lookup of allowed outcome options keyed by the canonical event type id.
 *
 * This relies on event_types.type_key for semantics but the map itself is keyed
 * and cached by the authoritative event_types.id value.
 */
function get_outcome_options_by_event_type_id(PDO $db): array
{
          static $cache = [];

          $connectionHash = spl_object_hash($db);
          if (isset($cache[$connectionHash])) {
                    return $cache[$connectionHash];
          }

          $definitions = [
                    'shot' => ['on_target', 'off_target', 'blocked'],
                    'pass' => ['complete', 'incomplete'],
                    'tackle' => ['won', 'lost'],
                    'cross' => ['successful', 'unsuccessful'],
                    'duel' => ['won', 'lost'],
                    'dribble' => ['successful', 'unsuccessful'],
                    'interception' => ['successful', 'unsuccessful'],
                    'clearance' => ['effective', 'ineffective'],
                    'goal' => [],
                    'foul' => [],
                    'turnover' => [],
                    'period_start' => [],
                    'period_end' => [],
                    'corner' => [],
                    'free_kick' => [],
                    'penalty' => [],
          ];

          $rows = $db->query('SELECT id, type_key FROM event_types')->fetchAll(PDO::FETCH_ASSOC);
          $outcomeOptionsByTypeId = [];

          foreach ($rows as $row) {
                    $typeId = isset($row['id']) ? (int)$row['id'] : 0;
                    if ($typeId <= 0) {
                              continue;
                    }

                    $typeKey = strtolower((string)($row['type_key'] ?? ''));
                    $outcomeOptionsByTypeId[$typeId] = $definitions[$typeKey] ?? [];
          }

          $cache[$connectionHash] = $outcomeOptionsByTypeId;
          return $outcomeOptionsByTypeId;
}
