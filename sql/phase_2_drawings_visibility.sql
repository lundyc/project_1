-- Phase 2 – Track rendering windows for drawing annotations.

ALTER TABLE `annotations`
  ADD COLUMN `show_from_second` int(11) unsigned NOT NULL DEFAULT 0 AFTER `timestamp_second`,
  ADD COLUMN `show_to_second` int(11) unsigned NOT NULL DEFAULT 0 AFTER `show_from_second`;

UPDATE `annotations`
  SET `show_from_second` = GREATEST(0, `timestamp_second` - 5),
      `show_to_second` = `timestamp_second` + 5;
