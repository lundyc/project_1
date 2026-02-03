-- Add shot location fields to events
ALTER TABLE `events`
  ADD COLUMN `shot_origin_x` FLOAT NULL AFTER `notes`,
  ADD COLUMN `shot_origin_y` FLOAT NULL AFTER `shot_origin_x`,
  ADD COLUMN `shot_target_x` FLOAT NULL AFTER `shot_origin_y`,
  ADD COLUMN `shot_target_y` FLOAT NULL AFTER `shot_target_x`;
