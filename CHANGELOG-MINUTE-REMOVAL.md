# Removal of Redundant minute and minute_extra Columns

## Summary
Successfully removed the `minute` and `minute_extra` columns from the events table and updated all code to rely solely on `match_second` for event timing.

## Rationale
- `minute` was entirely redundant - it was always calculated as `FLOOR(match_second / 60)` and could be derived on demand
- `minute_extra` was confusing and incorrectly handled, often conflicting with actual period assignments
- Consolidating on `match_second` (seconds into the match) provides a single source of truth for event timing

## Changes Made

### Database Migration
**File:** `sql/04-02-2026 - 1000.sql`
- Dropped `minute` column from events table
- Dropped `minute_extra` column from events table

### Backend Code Updates

#### app/lib/event_validation.php
- Removed minute/minute_extra handling from `normalize_event_payload()` function
- Removed minute/minute_extra handling from `validate_event_payload()` function
- Removed minute_extra assignment in period calculation logic

#### app/lib/event_repository.php
- Removed `minute` and `minute_extra` from INSERT statement
- Updated parameter binding to exclude these columns

### Frontend Code Updates

#### app/views/pages/matches/desk.php
- Removed hidden input fields for `minute` and `minute_extra`
- Updated comment to clarify that `match_second` is the canonical source

#### public/assets/js/desk-events.js
- Removed jQuery selectors for `$minute` and `$minuteExtra`
- Removed `$minuteExtraDisplay` selector
- Simplified `updateTimeFromSeconds()` to only set `match_second`
- Emptied `updateMinuteExtraFields()` function (kept for compatibility)

### View Updates

#### app/views/partials/match-summary-stats.php
- Updated goal/card sorting to use `match_second` instead of `minute`
- Updated displayed time calculations: `floor(match_second / 60)` instead of reading `minute` field

#### app/views/pages/stats/match.php
- Updated minute calculations to derive from `match_second` only
- Changed JavaScript template literals to calculate minute on the fly
- Line 702: Now uses `Math.floor(match_second / 60)`
- Line 1883, 1889: Updated goal/card display templates to use `match_second`
- Line 2552: Removed conditional check for `minute` field

#### app/views/pages/stats/match_report.php
- Updated goal minute display to use `match_second` with fallback to `minute` for legacy data
- Updated substitution minute display to derive from `match_second`

#### app/views/pages/league-intelligence/match-preview.php
- Updated late goal detection logic to use `match_second / 60 >= 75` calculation

#### app/views/pages/matches/lineup.php
- Removed `minute` and `minute_extra` from timing object initialization

## Testing
✅ Validation function test passed
✅ Database migration successful
✅ Table structure verified - minute and minute_extra columns removed
✅ API normalization working correctly

## Migration Impact
- **Breaking Change:** Any external code reading `minute` or `minute_extra` from events API will fail
- **Non-Breaking:** All display logic updated to calculate minute from `match_second` on demand
- **Backward Compatibility:** Views include fallback logic for legacy data that might still have minute values

## Future Considerations
- If additional data structures reference minute/minute_extra, they should be updated to use match_second
- Consider adding a helper function `getEventMinute(matchSecond, minuteExtra = null)` if needed by multiple consumers
