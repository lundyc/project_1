<?php

require_once __DIR__ . '/db.php';

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

function build_match_stats_from_events(array $events, array $eventTypes, int $eventsVersion): array
{
          $categories = [
                    'goal' => ['goal'],
                    'shot' => ['shot', 'goal'],
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
          foreach (array_keys($categories) as $key) {
                    $byType[$key] = ['home' => 0, 'away' => 0, 'unknown' => 0];
          }

          foreach ($events as $ev) {
                    $team = normalize_team_side_value($ev['team_side'] ?? 'unknown');
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
                    if ($typeKey === '') {
                              continue;
                    }

                    foreach ($categories as $bucket => $aliases) {
                              if (in_array($typeKey, $aliases, true)) {
                                        $byType[$bucket][$team]++;
                              }
                    }
          }

          $setPieces = [
                    'home' => $byType['corner']['home'] + $byType['free_kick']['home'] + $byType['penalty']['home'],
                    'away' => $byType['corner']['away'] + $byType['free_kick']['away'] + $byType['penalty']['away'],
                    'unknown' => $byType['corner']['unknown'] + $byType['free_kick']['unknown'] + $byType['penalty']['unknown'],
          ];

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
                    'payload' => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
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

          $stats = build_match_stats_from_events($events, $typeMap, $eventsVersion);
          store_match_stats($matchId, $eventsVersion, $stats);

          return $stats;
}
