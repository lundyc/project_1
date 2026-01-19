-- Seed canonical 11-a-side formations and link existing matches to a default 4-4-2.

INSERT INTO `formations` (`format`, `formation_key`, `label`, `player_count`, `is_fixed`)
VALUES
  ('11-a-side', '4-4-2', '4-4-2', 11, 1)
ON DUPLICATE KEY UPDATE
  `label` = VALUES(`label`),
  `player_count` = VALUES(`player_count`),
  `is_fixed` = VALUES(`is_fixed`);

SET @formation_442 = (
  SELECT `id` FROM `formations`
  WHERE `format` = '11-a-side' AND `formation_key` = '4-4-2'
  LIMIT 1
);

INSERT INTO `formation_positions`
  (`formation_id`, `slot_index`, `position_label`, `left_percent`, `bottom_percent`, `rotation_deg`)
VALUES
  (@formation_442, 0, 'GK', 50.0000, 0.0000, 0),
  (@formation_442, 1, 'LB', 0.0000, 33.3333, 0),
  (@formation_442, 2, 'CB', 33.3333, 33.3333, 0),
  (@formation_442, 3, 'CB', 66.6667, 33.3333, 0),
  (@formation_442, 4, 'RB', 100.0000, 33.3333, 0),
  (@formation_442, 5, 'LM', 0.0000, 66.6667, 0),
  (@formation_442, 6, 'CM', 33.3333, 66.6667, 0),
  (@formation_442, 7, 'CM', 66.6667, 66.6667, 0),
  (@formation_442, 8, 'RM', 100.0000, 66.6667, 0),
  (@formation_442, 9, 'ST', 25.0000, 100.0000, 0),
  (@formation_442, 10, 'ST', 75.0000, 100.0000, 0)
ON DUPLICATE KEY UPDATE
  `position_label` = VALUES(`position_label`),
  `left_percent` = VALUES(`left_percent`),
  `bottom_percent` = VALUES(`bottom_percent`),
  `rotation_deg` = VALUES(`rotation_deg`);

INSERT INTO `formations` (`format`, `formation_key`, `label`, `player_count`, `is_fixed`)
VALUES
  ('11-a-side', '4-3-3', '4-3-3', 11, 1)
ON DUPLICATE KEY UPDATE
  `label` = VALUES(`label`),
  `player_count` = VALUES(`player_count`),
  `is_fixed` = VALUES(`is_fixed`);

SET @formation_433 = (
  SELECT `id` FROM `formations`
  WHERE `format` = '11-a-side' AND `formation_key` = '4-3-3'
  LIMIT 1
);

INSERT INTO `formation_positions`
  (`formation_id`, `slot_index`, `position_label`, `left_percent`, `bottom_percent`, `rotation_deg`)
VALUES
  (@formation_433, 0, 'GK', 50.0000, 0.0000, 0),
  (@formation_433, 1, 'LB', 10.0000, 30.0000, 0),
  (@formation_433, 2, 'CB', 35.0000, 30.0000, 0),
  (@formation_433, 3, 'CB', 65.0000, 30.0000, 0),
  (@formation_433, 4, 'RB', 90.0000, 30.0000, 0),
  (@formation_433, 5, 'CM', 30.0000, 60.0000, 0),
  (@formation_433, 6, 'CM', 50.0000, 70.0000, 0),
  (@formation_433, 7, 'CM', 70.0000, 60.0000, 0),
  (@formation_433, 8, 'LW', 15.0000, 90.0000, 0),
  (@formation_433, 9, 'ST', 50.0000, 100.0000, 0),
  (@formation_433, 10, 'RW', 85.0000, 90.0000, 0)
ON DUPLICATE KEY UPDATE
  `position_label` = VALUES(`position_label`),
  `left_percent` = VALUES(`left_percent`),
  `bottom_percent` = VALUES(`bottom_percent`),
  `rotation_deg` = VALUES(`rotation_deg`);

INSERT INTO `formations` (`format`, `formation_key`, `label`, `player_count`, `is_fixed`)
VALUES
  ('11-a-side', '3-5-2', '3-5-2', 11, 1)
ON DUPLICATE KEY UPDATE
  `label` = VALUES(`label`),
  `player_count` = VALUES(`player_count`),
  `is_fixed` = VALUES(`is_fixed`);

SET @formation_352 = (
  SELECT `id` FROM `formations`
  WHERE `format` = '11-a-side' AND `formation_key` = '3-5-2'
  LIMIT 1
);

INSERT INTO `formation_positions`
  (`formation_id`, `slot_index`, `position_label`, `left_percent`, `bottom_percent`, `rotation_deg`)
VALUES
  (@formation_352, 0, 'GK', 50.0000, 0.0000, 0),
  (@formation_352, 1, 'CB', 25.0000, 30.0000, 0),
  (@formation_352, 2, 'CB', 50.0000, 30.0000, 0),
  (@formation_352, 3, 'CB', 75.0000, 30.0000, 0),
  (@formation_352, 4, 'LWB', 5.0000, 55.0000, 0),
  (@formation_352, 5, 'RWB', 95.0000, 55.0000, 0),
  (@formation_352, 6, 'CM', 25.0000, 65.0000, 0),
  (@formation_352, 7, 'CM', 50.0000, 75.0000, 0),
  (@formation_352, 8, 'CM', 75.0000, 65.0000, 0),
  (@formation_352, 9, 'ST', 35.0000, 100.0000, 0),
  (@formation_352, 10, 'ST', 65.0000, 100.0000, 0)
ON DUPLICATE KEY UPDATE
  `position_label` = VALUES(`position_label`),
  `left_percent` = VALUES(`left_percent`),
  `bottom_percent` = VALUES(`bottom_percent`),
  `rotation_deg` = VALUES(`rotation_deg`);

UPDATE `matches`
SET
  `home_formation_id` = COALESCE(`home_formation_id`, @formation_442),
  `away_formation_id` = COALESCE(`away_formation_id`, @formation_442)
WHERE @formation_442 IS NOT NULL;
