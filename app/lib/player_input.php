<?php

require_once __DIR__ . '/team_repository.php';

function normalize_player_payload(array $input, int $clubId): array
{
          $payload = [
                    'first_name' => isset($input['first_name']) && trim((string)$input['first_name']) !== '' ? trim((string)$input['first_name']) : null,
                    'last_name' => isset($input['last_name']) && trim((string)$input['last_name']) !== '' ? trim((string)$input['last_name']) : null,
                    'dob' => isset($input['dob']) && trim((string)$input['dob']) !== '' ? trim((string)$input['dob']) : null,
                    'primary_position' => isset($input['primary_position']) && trim((string)$input['primary_position']) !== '' ? trim((string)$input['primary_position']) : null,
                    'team_id' => null,
                    'is_active' => isset($input['is_active']) && ((string)$input['is_active'] === '1') ? 1 : 0,
          ];

          if (isset($input['team_id']) && $input['team_id'] !== '') {
                    $teamId = (int)$input['team_id'];
                    if (is_team_in_club($teamId, $clubId)) {
                              $payload['team_id'] = $teamId;
                    }
          }

          return $payload;
}
