<?php

require_once __DIR__ . '/db.php';

function ensure_event_penalties_table(PDO $db): void
{
    $db->exec(
        'CREATE TABLE IF NOT EXISTS event_penalties (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            event_id BIGINT UNSIGNED NOT NULL,
            taker_match_player_id BIGINT UNSIGNED NULL,
            fouled_match_player_id BIGINT UNSIGNED NULL,
            fouler_match_player_id BIGINT UNSIGNED NULL,
            goalkeeper_match_player_id BIGINT UNSIGNED NULL,
            penalty_reason VARCHAR(50) NULL,
            outcome ENUM(\'scored\',\'saved\',\'missed\',\'post\',\'retaken\') NOT NULL,
            placement_zone VARCHAR(30) NULL,
            keeper_dive_direction VARCHAR(20) NULL,
            keeper_touched_ball TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    $db->exec(
        'ALTER TABLE event_penalties
            MODIFY COLUMN taker_match_player_id BIGINT UNSIGNED NULL'
    );
}

/**
 * @param array<string, mixed> $data
 * @return array<string, mixed>
 */
function create_event_penalty(PDO $db, array $data): array
{
    $stmt = $db->prepare(
        'INSERT INTO event_penalties
            (event_id, taker_match_player_id, fouled_match_player_id, fouler_match_player_id, goalkeeper_match_player_id,
             penalty_reason, outcome, placement_zone, keeper_dive_direction, keeper_touched_ball)
         VALUES
            (:event_id, :taker_match_player_id, :fouled_match_player_id, :fouler_match_player_id, :goalkeeper_match_player_id,
             :penalty_reason, :outcome, :placement_zone, :keeper_dive_direction, :keeper_touched_ball)'
    );

    $stmt->execute([
        'event_id' => $data['event_id'],
        'taker_match_player_id' => $data['taker_match_player_id'],
        'fouled_match_player_id' => $data['fouled_match_player_id'],
        'fouler_match_player_id' => $data['fouler_match_player_id'],
        'goalkeeper_match_player_id' => $data['goalkeeper_match_player_id'],
        'penalty_reason' => $data['penalty_reason'],
        'outcome' => $data['outcome'],
        'placement_zone' => $data['placement_zone'],
        'keeper_dive_direction' => $data['keeper_dive_direction'],
        'keeper_touched_ball' => $data['keeper_touched_ball'],
    ]);

    $id = (int)$db->lastInsertId();
    $fetch = $db->prepare('SELECT * FROM event_penalties WHERE id = ?');
    $fetch->execute([$id]);
    $row = $fetch->fetch(PDO::FETCH_ASSOC);

    return $row ?: [];
}
