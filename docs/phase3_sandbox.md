# Phase 3 Sandbox Enforcement

## Kill switch
Phase 3 now honours a single flag: `PHASE_3_VIDEO_LAB_ENABLED=true`. If that environment variable is set to anything other than `0`, `false`, or `no`, Phase 3 stays live; otherwise the feature is frozen in read-only mode.

- Clip generation, regeneration, and review routes all consult `phase3_is_enabled()` (`app/lib/phase3.php`), and the Video Lab match view (`app/views/pages/video_lab/match.php`) disables action buttons when the flag is off.
- When disabled, the UI still loads, existing clips remain visible, and analysts continue tagging events or recomputing snapshots—no Phase 3 background work can touch the `clips`, `clip_reviews`, or `event_snapshots` tables.

## Hard data boundaries

Phase 3 follows a one-way pipeline: `events` → `event_snapshots` → `clips` → `clip_reviews`. No business logic in `clip_generation_service`, `clip_regeneration_service`, or `clip_review_service` updates `events.*`, derived stats, or timeline state. In particular:

- `events.outcome`, `events.zone`, `events.importance`, and `matches.events_version` are never written by Phase 3 code.
- Regeneration routines delete only their own `clips`, `clip_reviews`, and snapshots; they never cascade back into `events` or stats.

## Failure containment

Failures stay contained:

- Snapshot failures abort only the clip generation transaction (`clip_generation_service_handle_event_save`).
- Clip insert failures roll back any snapshot writes before bubbling up.
- Review failures are logged and returned without touching other clips.
- Missing video metadata or unknown duration simply short-circuit clip workflows and let analysis proceed.
- No exception from Phase 3 ever escapes to event saves, timeline redraws, or analysis pages.

## Audit & observability

Every clip lifecycle step now produces a low-noise audit entry:

- `phase3_log_clip_action(..., 'generated')` right after a new clip is committed.
- `phase3_log_clip_action(..., 'regenerated')` after matching user-triggered regenerations.
- The existing review audit call records `clip_review` actions (`review_approved`, `review_rejected`) without embedding large JSON blobs.
- When the kill switch is off and a clip action was attempted, `phase3_log_clip_action(..., 'phase3_disabled')` records the clip ID for observability.

These entries appear in `audit_log` with `entity_type='clip'`, the clip’s `id`, and no sensitive payloads.

## Safe rollback procedure

1. Set the flag to `false` so Video Lab runs read-only: export `PHASE_3_VIDEO_LAB_ENABLED=false` (or edit `config/config.php` and restart PHP-FPM/Apache).
2. Optionally truncate downstream tables to remove Phase 3 artifacts while preserving all core analytics:

```bash
mysql -h 127.0.0.1 -uproject_1 -p'<your password>' project_1 <<'SQL'
SET foreign_key_checks = 0;
TRUNCATE TABLE clip_reviews;
TRUNCATE TABLE clips;
TRUNCATE TABLE event_snapshots;
SET foreign_key_checks = 1;
SQL
```

3. Verify that `events`, `derived_stats`, and `matches` remain untouched; restart any workers if needed.

This rollback sequence is non-destructive, reversible (re-enable the flag and optionally re-run clip generation), and scriptable via the snippet above.
