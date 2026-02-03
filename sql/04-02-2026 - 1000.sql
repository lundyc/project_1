-- Drop minute and minute_extra columns from events table
-- These columns were redundant as minute can be calculated from match_second
-- and minute_extra was rarely used properly

ALTER TABLE `events`
  DROP COLUMN `minute`,
  DROP COLUMN `minute_extra`;
