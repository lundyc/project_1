<?php

require_once __DIR__ . '/db.php';

function get_default_keyboard_profile(int $userId): ?array
{
          $stmt = db()->prepare(
                    'SELECT id, user_id, name, bindings_json, is_default
             FROM keyboard_profiles
             WHERE user_id = :user_id AND is_default = 1
             ORDER BY id DESC
             LIMIT 1'
          );

          $stmt->execute(['user_id' => $userId]);

          $profile = $stmt->fetch();

          return $profile ?: null;
}

function create_default_keyboard_profile(int $userId, array $bindings): array
{
          $stmt = db()->prepare(
                    'INSERT INTO keyboard_profiles (user_id, name, bindings_json, is_default)
             VALUES (:user_id, :name, :bindings_json, 1)'
          );

          $payload = [
                    'user_id' => $userId,
                    'name' => 'Default',
                    'bindings_json' => json_encode($bindings),
          ];

          $stmt->execute($payload);

          $id = (int)db()->lastInsertId();

          return [
                    'id' => $id,
                    'user_id' => $userId,
                    'name' => 'Default',
                    'bindings_json' => json_encode($bindings),
                    'is_default' => 1,
          ];
}

function ensure_default_keyboard_profile(int $userId): array
{
          $profile = get_default_keyboard_profile($userId);

          if ($profile) {
                    return $profile;
          }

          $bindings = [
                    'play_pause' => 'Space',
                    'seek_back_2' => 'ArrowLeft',
                    'seek_fwd_2' => 'ArrowRight',
                    'event_quick_goal' => 'Digit1',
                    'event_quick_shot' => 'Digit2',
                    'set_clip_in' => 'KeyI',
                    'set_clip_out' => 'KeyO',
                    'save_event' => 'Enter',
          ];

          return create_default_keyboard_profile($userId, $bindings);
}
