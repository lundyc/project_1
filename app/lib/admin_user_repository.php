<?php

function get_user_by_id(int $id): ?array
{
        $pdo = db();
        $stmt = $pdo->prepare('SELECT u.*, c.name AS club_name FROM users u LEFT JOIN clubs c ON c.id = u.club_id WHERE u.id = :id');
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();
        if (!$user) {
                return null;
        }
        // Get roles
        $rolesStmt = $pdo->prepare('SELECT r.role_key FROM user_roles ur JOIN roles r ON ur.role_id = r.id WHERE ur.user_id = :id');
        $rolesStmt->execute(['id' => $id]);
        $user['roles'] = $rolesStmt->fetchAll(PDO::FETCH_COLUMN);
        return $user;
}
function update_user(int $id, string $email, string $display_name, ?int $club_id, array $role_keys, ?string $password = null, int $is_active = 1): void
{
        $pdo = db();
        $pdo->beginTransaction();
        try {
                $fields = [
                        'email' => $email,
                        'display_name' => $display_name,
                        'club_id' => $club_id,
                        'is_active' => $is_active,
                        'updated_at' => date('Y-m-d H:i:s'),
                        'id' => $id,
                ];
                $set = 'email = :email, display_name = :display_name, club_id = :club_id, is_active = :is_active, updated_at = :updated_at';
                if ($password !== null && $password !== '') {
                        $fields['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
                        $set .= ', password_hash = :password_hash';
                }
                $stmt = $pdo->prepare("UPDATE users SET $set WHERE id = :id");
                $stmt->execute($fields);

                // Remove existing roles
                $pdo->prepare('DELETE FROM user_roles WHERE user_id = :id')->execute(['id' => $id]);

                // Add new roles
                if (!empty($role_keys)) {
                        $roleStmt = $pdo->prepare('SELECT id FROM roles WHERE role_key = :role_key');
                        $insertStmt = $pdo->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)');
                        foreach ($role_keys as $roleKey) {
                                $roleStmt->execute(['role_key' => $roleKey]);
                                $roleId = $roleStmt->fetchColumn();
                                if ($roleId) {
                                        $insertStmt->execute([
                                                'user_id' => $id,
                                                'role_id' => $roleId,
                                        ]);
                                }
                        }
                }

                $pdo->commit();
        } catch (\Throwable $e) {
                $pdo->rollBack();
                throw $e;
        }
}

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
