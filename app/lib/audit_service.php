<?php

require_once __DIR__ . '/db.php';

function audit(int $clubId, int $userId, string $entityType, int $entityId, string $action, $before, $after): void
{
          $stmt = db()->prepare(
                    'INSERT INTO audit_log (club_id, user_id, entity_type, entity_id, action, before_json, after_json)
             VALUES (:club_id, :user_id, :entity_type, :entity_id, :action, :before_json, :after_json)'
          );

          $stmt->execute([
                    'club_id' => $clubId,
                    'user_id' => $userId,
                    'entity_type' => $entityType,
                    'entity_id' => $entityId,
                    'action' => $action,
                    'before_json' => $before,
                    'after_json' => $after,
          ]);
}
