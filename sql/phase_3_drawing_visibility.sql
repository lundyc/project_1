-- Phase 3 – Track before/after visibility windows for drawings.

ALTER TABLE `annotations`
  ADD COLUMN `show_before_seconds` int(11) unsigned NOT NULL DEFAULT 5 AFTER `show_to_second`,
  ADD COLUMN `show_after_seconds` int(11) unsigned NOT NULL DEFAULT 5 AFTER `show_before_seconds`;

UPDATE `annotations`
  SET `show_before_seconds` = 5,
      `show_after_seconds` = 5
  WHERE `show_before_seconds` IS NULL OR `show_after_seconds` IS NULL;
