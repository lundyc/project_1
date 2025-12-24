<?php

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/match_repository.php';
require_once __DIR__ . '/../../lib/match_permissions.php';
require_once __DIR__ . '/../../lib/match_player_repository.php';

auth_boot();
require_auth();

$matchId = isset($matchId) ? (int)$matchId : (int)($_POST['match_id'] ?? 0);

if ($matchId <= 0) {
          http_response_code(400);
          exit;
}

$match = get_match($matchId);
if (!$match) {
          http_response_code(404);
          echo 'Match not found';
          exit;
}

$user = current_user();
$roles = $_SESSION['roles'] ?? [];

if (!can_manage_match_for_club($user, $roles, (int)$match['club_id'])) {
          http_response_code(403);
          exit;
}

$clubPlayers = get_club_players((int)$match['club_id']);
$clubPlayerMap = [];
foreach ($clubPlayers as $cp) {
          $clubPlayerMap[(int)$cp['id']] = $cp['display_name'];
}

$rows = [];

$sides = [
          'home' => [
                    'player_ids' => $_POST['home_player_id'] ?? [],
                    'names' => $_POST['home_display_name'] ?? [],
                    'numbers' => $_POST['home_shirt_number'] ?? [],
                    'positions' => $_POST['home_position_label'] ?? [],
                    'starting' => $_POST['home_is_starting'] ?? [],
          ],
          'away' => [
                    'player_ids' => $_POST['away_player_id'] ?? [],
                    'names' => $_POST['away_display_name'] ?? [],
                    'numbers' => $_POST['away_shirt_number'] ?? [],
                    'positions' => $_POST['away_position_label'] ?? [],
                    'starting' => $_POST['away_is_starting'] ?? [],
          ],
];

foreach ($sides as $teamSide => $payload) {
          foreach ($payload['names'] as $idx => $name) {
                    $displayName = trim((string)$name);
                    $playerIdRaw = $payload['player_ids'][$idx] ?? '';
                    $playerId = $playerIdRaw === '' ? null : (int)$playerIdRaw;
                    $numberRaw = trim((string)($payload['numbers'][$idx] ?? ''));
                    $positionLabel = trim((string)($payload['positions'][$idx] ?? ''));
                    $isStarting = isset($payload['starting'][$idx]) ? 1 : 0;

                    if ($displayName === '') {
                              if ($playerId !== null && isset($clubPlayerMap[$playerId])) {
                                        $displayName = $clubPlayerMap[$playerId];
                              } else {
                                        continue;
                              }
                    }

                    if ($playerId !== null && !isset($clubPlayerMap[$playerId])) {
                              $_SESSION['roster_error'] = 'Invalid player selection';
                              redirect('/matches/' . $matchId . '/roster');
                    }

                    $shirtNumber = $numberRaw === '' ? null : (int)$numberRaw;

                    $rows[] = [
                              'team_side' => $teamSide,
                              'player_id' => $playerId,
                              'display_name' => $displayName,
                              'shirt_number' => $shirtNumber,
                              'position_label' => $positionLabel !== '' ? $positionLabel : null,
                              'is_starting' => $isStarting,
                    ];
          }
}

try {
          replace_match_players($matchId, $rows);
          $_SESSION['roster_success'] = 'Roster saved';
} catch (\Throwable $e) {
          $_SESSION['roster_error'] = 'Unable to save roster';
}

redirect('/matches/' . $matchId . '/roster');
