<?php

require_once __DIR__ . '/db.php';

function find_user_by_email(string $email): ?array
{
          $stmt = db()->prepare(
                    'SELECT * FROM users WHERE email = :email AND is_active = 1 LIMIT 1'
          );
          $stmt->execute(['email' => $email]);

          $user = $stmt->fetch();
          return $user ?: null;
}

function load_user_roles(int $userId): array
{
          $stmt = db()->prepare(
                    'SELECT r.role_key
         FROM user_roles ur
         JOIN roles r ON r.id = ur.role_id
         WHERE ur.user_id = :uid'
          );
          $stmt->execute(['uid' => $userId]);

          return array_column($stmt->fetchAll(), 'role_key');
}
