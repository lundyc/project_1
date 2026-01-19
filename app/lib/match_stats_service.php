<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/match_period_repository.php';

function normalize_team_side_value(?string $team): string
{
          $value = strtolower((string)$team);
          if (in_array($value, ['home', 'away'], true)) {
                    return $value;
          }

          return 'unknown';
}

function guess_type_key_from_label(?string $label): ?string
{
          $lower = strtolower((string)$label);
          if ($lower === '') {
                    return null;
          }
          if (str_contains($lower, 'goal')) return 'goal';
          if (str_contains($lower, 'shot')) return 'shot';
          if (str_contains($lower, 'chance')) return 'chance';
          if (str_contains($lower, 'corner')) return 'corner';
          if (str_contains($lower, 'free')) return 'free_kick';
          if (str_contains($lower, 'penalty')) return 'penalty';
          if (str_contains($lower, 'foul')) return 'foul';
          if (str_contains($lower, 'yellow')) return 'yellow_card';
          if (str_contains($lower, 'red')) return 'red_card';
          if (str_contains($lower, 'mistake') || str_contains($lower, 'error')) return 'mistake';
          if (str_contains($lower, 'good') || str_contains($lower, 'play')) return 'good_play';
          if (str_contains($lower, 'highlight')) return 'highlight';

          return null;
}

function new_team_counts(): array
{
          return ['home' => 0, 'away' => 0, 'unknown' => 0];
}

function resolve_period_category(array $period): string
{
          $key = strtolower((string)($period['period_key'] ?? ''));
          $label = strtolower((string)($period['label'] ?? ''));

          if (str_contains($key, 'et') || str_contains($label, 'extra time') || str_contains($label, 'extra')) {
                    return 'ET';
          }
          if (str_contains($key, 'sh') || str_contains($label, 'second') || str_contains($label, '2nd') || str_contains($label, '2h')) {
                    return '2H';
          }
          if (str_contains($key, 'fh') || str_contains($label, 'first') || str_contains($label, '1st') || str_contains($label, '1h')) {
                    return '1H';
          }

          return 'other';
}

function build_match_stats_from_events(array $events, array $eventTypes, array $periods, int $eventsVersion): array
{
          $categories = [
                    'goal' => ['goal'],
                    'shot' => ['shot', 'goal', 'shot_on_target', 'shot_off_target'],
                    'shot_on_target' => ['shot_on_target'],
                    'shot_off_target' => ['shot_off_target'],
                    'chance' => ['chance', 'big_chance'],
                    'corner' => ['corner'],
                    'free_kick' => ['free_kick', 'freekick'],
                    'penalty' => ['penalty', 'spot_kick'],
                    'foul' => ['foul'],
                    'yellow_card' => ['yellow_card', 'yellow'],
                    'red_card' => ['red_card', 'red'],
                    'mistake' => ['mistake', 'error', 'turnover', 'own_goal'],
                    'good_play' => ['good_play', 'assist', 'positive'],
                    'highlight' => ['highlight', 'other'],
          ];

          $byType = [];
          $importanceByType = [];
          foreach (array_keys($categories) as $key) {
                    $byType[$key] = new_team_counts();
                    $importanceByType[$key] = new_team_counts();
          }

          $periodCategoryMap = [];
          foreach ($periods as $period) {
                    if (empty($period['id'])) {
                              continue;
                    }
                    $periodCategoryMap[(int)$period['id']] = resolve_period_category($period);
          }

          $periodGroups = ['1H', '2H', 'ET', 'other'];
          $byPeriod = [];
          foreach ($periodGroups as $group) {
                    $byPeriod[$group] = new_team_counts();
          }

          $per15Buckets = [];
          $importanceTotals = new_team_counts();

          foreach ($events as $ev) {
                    $team = normalize_team_side_value($ev['team_side'] ?? 'unknown');
                    $importance = max(1, min(5, (int)($ev['importance'] ?? 3)));
                    $importanceTotals[$team] += $importance;

                    $matchSecond = max(0, (int)($ev['match_second'] ?? 0));
                    $bucketIndex = (int)floor($matchSecond / 900);
                    $bucketLabel = sprintf('%d-%d', $bucketIndex * 15, ($bucketIndex + 1) * 15);
                    if (!isset($per15Buckets[$bucketIndex])) {
                              $per15Buckets[$bucketIndex] = [
                                        'label' => $bucketLabel,
                                        'home' => 0,
                                        'away' => 0,
                                        'unknown' => 0,
                              ];
                    }
                    $per15Buckets[$bucketIndex][$team]++;

                    $periodId = isset($ev['period_id']) ? (int)$ev['period_id'] : 0;
                    $periodGroup = $periodCategoryMap[$periodId] ?? 'other';
                    $byPeriod[$periodGroup][$team]++;

                    $typeId = (int)($ev['event_type_id'] ?? 0);
                    $typeKey = strtolower((string)($ev['event_type_key'] ?? ''));
                    if (!$typeKey && $typeId && isset($eventTypes[$typeId])) {
                              $typeKey = strtolower((string)($eventTypes[$typeId]['type_key'] ?? ''));
                              if (!$typeKey) {
                                        $guess = guess_type_key_from_label($eventTypes[$typeId]['label'] ?? null);
                                        if ($guess) {
                                                  $typeKey = $guess;
                                        }
                              }
                    }
                    if ($typeKey === '') {
                              $typeKey = guess_type_key_from_label($ev['event_type_label'] ?? null) ?? '';
                    }
                    $outcomeKey = strtolower(trim((string)($ev['outcome'] ?? '')));
                    if ($typeKey === 'shot' && in_array($outcomeKey, ['on_target', 'off_target'], true)) {
                              $typeKey = 'shot_' . $outcomeKey;
                    }
                    if ($typeKey === '') {
                              continue;
                    }

                    foreach ($categories as $bucket => $aliases) {
                              if (in_array($typeKey, $aliases, true)) {
                                        $byType[$bucket][$team]++;
                                        $importanceByType[$bucket][$team] += $importance;
                              }
                    }
          }

          ksort($per15Buckets, SORT_NUMERIC);
          $per15List = array_values(array_map(function ($bucket) {
                    return [
                              'label' => $bucket['label'],
                              'home' => $bucket['home'],
                              'away' => $bucket['away'],
                              'unknown' => $bucket['unknown'],
                    ];
          }, $per15Buckets));

          // Set pieces combine corners, free kicks, and penalties per side.
          $setPieces = [
                    'home' => $byType['corner']['home'] + $byType['free_kick']['home'] + $byType['penalty']['home'],
                    'away' => $byType['corner']['away'] + $byType['free_kick']['away'] + $byType['penalty']['away'],
                    'unknown' => $byType['corner']['unknown'] + $byType['free_kick']['unknown'] + $byType['penalty']['unknown'],
          ];

          // Cards group yellow and red counts together.
          $cards = [
                    'home' => $byType['yellow_card']['home'] + $byType['red_card']['home'],
                    'away' => $byType['yellow_card']['away'] + $byType['red_card']['away'],
                    'unknown' => $byType['yellow_card']['unknown'] + $byType['red_card']['unknown'],
          ];

          $highlightsTotal = $byType['highlight']['home'] + $byType['highlight']['away'] + $byType['highlight']['unknown'];

          return [
                    'computed_at' => gmdate('Y-m-d H:i:s'),
                    'events_version_used' => $eventsVersion,
                    'by_type_team' => $byType,
                    'phase_2' => [
                              // Totals split by first half, second half, extra time, or other periods.
                              'by_period' => $byPeriod,
                              // Match events grouped into 15-minute buckets for trend analysis.
                              'per_15_minute' => $per15List,
                              // Importance-weighted counts help highlight high-value activity.
                              'importance_weighted' => [
                                        'by_team' => $importanceTotals,
                                        'by_type_team' => $importanceByType,
                              ],
                    ],
                    'totals' => [
                              'set_pieces' => $setPieces,
                              'cards' => $cards,
                              'highlights' => [
                                        'total' => $highlightsTotal,
                                        'by_team' => $byType['highlight'],
                              ],
                    ],
          ];
}

function fetch_cached_match_stats(int $matchId, int $eventsVersion): ?array
{
          $stmt = db()->prepare(
                    'SELECT payload_json
             FROM derived_stats
             WHERE match_id = :match_id AND events_version_used = :events_version
             ORDER BY id DESC
             LIMIT 1'
          );
          $stmt->execute(['match_id' => $matchId, 'events_version' => $eventsVersion]);
          $row = $stmt->fetch();

          if (!$row) {
                    return null;
          }

          $decoded = json_decode((string)$row['payload_json'], true);

          return is_array($decoded) ? $decoded : null;
}

function store_match_stats(int $matchId, int $eventsVersion, array $payload): void
{
          $stmt = db()->prepare(
                    'INSERT INTO derived_stats (match_id, events_version_used, computed_at, payload_json)
             VALUES (:match_id, :events_version, :computed_at, :payload)'
          );

          $stmt->execute([
                    'match_id' => $matchId,
                    'events_version' => $eventsVersion,
                    'computed_at' => gmdate('Y-m-d H:i:s'),
                    'payload' => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
          ]);

          db()->prepare('UPDATE matches SET derived_version = :events_version WHERE id = :id')
                    ->execute(['events_version' => $eventsVersion, 'id' => $matchId]);
}

function get_or_compute_match_stats(int $matchId, int $eventsVersion, array $events, array $eventTypes, bool $force = false): array
{
          if (!$force) {
                    $cached = fetch_cached_match_stats($matchId, $eventsVersion);
                    if ($cached) {
                              return $cached;
                    }
          }

          $typeMap = [];
          foreach ($eventTypes as $type) {
                    $typeMap[(int)$type['id']] = $type;
          }

          $periods = get_match_periods($matchId);
          $stats = build_match_stats_from_events($events, $typeMap, $periods, $eventsVersion);
          store_match_stats($matchId, $eventsVersion, $stats);

          return $stats;
}
