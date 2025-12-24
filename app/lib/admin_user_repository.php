<?php

require_once __DIR__ . '/db.php';

function get_roles(): array
{
          $stmt = db()->query('SELECT id, role_key FROM roles ORDER BY role_key ASC');

          return $stmt->fetchAll();
}

function get_all_users(): array
{
          $stmt = db()->query(
                    'SELECT u.id,
                            u.email,
                            u.display_name,
                            u.club_id,
                            c.name AS club_name,
                            r.role_key
                     FROM users u
                     LEFT JOIN clubs c ON c.id = u.club_id
                     LEFT JOIN user_roles ur ON ur.user_id = u.id
                     LEFT JOIN roles r ON r.id = ur.role_id
                     ORDER BY u.id DESC, r.role_key ASC'
          );

          $users = [];

          foreach ($stmt->fetchAll() as $row) {
                    $id = (int)$row['id'];

                    if (!isset($users[$id])) {
                              $users[$id] = [
                                        'id' => $id,
                                        'email' => $row['email'],
                                        'display_name' => $row['display_name'],
                                        'club_id' => $row['club_id'],
                                        'club_name' => $row['club_name'],
                                        'roles' => [],
                              ];
                    }

                    if (!empty($row['role_key'])) {
                              $users[$id]['roles'][] = $row['role_key'];
                    }
          }

          return array_values($users);
}

function create_user(string $email, string $password, string $display_name, ?int $club_id, array $role_ids): int
{
          $pdo = db();

          $pdo->beginTransaction();

          try {
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                    $stmt = $pdo->prepare(
                              'INSERT INTO users (email, password_hash, display_name, club_id, is_active)
                     VALUES (:email, :password_hash, :display_name, :club_id, 1)'
                    );

                    $stmt->execute([
                              'email' => $email,
                              'password_hash' => $passwordHash,
                              'display_name' => $display_name,
                              'club_id' => $club_id,
                    ]);

                    $userId = (int)$pdo->lastInsertId();

                    $roleIds = array_unique(array_map('intval', $role_ids));

                    if ($roleIds) {
                              $roleStmt = $pdo->prepare(
                                        'INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)'
                              );

                              foreach ($roleIds as $roleId) {
                                        $roleStmt->execute([
                                                  'user_id' => $userId,
                                                  'role_id' => $roleId,
                                        ]);
                              }
                    }

                    $pdo->commit();

                    return $userId;
          } catch (\Throwable $e) {
                    $pdo->rollBack();
                    throw $e;
          }
}
