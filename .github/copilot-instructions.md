# Analytics Desk Copilot Instructions

## Project Overview
Sports analytics platform for video-based match tagging and statistics. Core features: real-time event tagging, automated clip generation, multiplayer session sync, and comprehensive stats dashboards.

**Stack:** PHP (procedural + minimal OOP), MySQL, Vanilla JS, Tailwind CSS, Node.js WebSocket server, Python FFmpeg workers

## Architecture

### Request Flow
- **Public Entry:** [public/index.php](public/index.php) - Single entry point with procedural routing via `route()` function
- **Routing:** Custom lightweight router ([app/lib/router.php](app/lib/router.php)) supports exact paths, `{id}` placeholders, and regex patterns
- **API Endpoints:** Standalone PHP files in `app/api/*` (not routed through index.php) - each handles its own auth, JSON responses, and database access
- **Views:** Server-side rendered PHP templates in `app/views/` - includes/partials pattern, no template engine

### Data Layer
- **Database:** Direct PDO via `db()` singleton ([app/lib/db.php](app/lib/db.php))
- **Repositories:** Procedural functions grouped by domain in `app/lib/*_repository.php` (e.g., `match_repository.php`, `event_repository.php`)
- **Services:** Stateless classes for complex operations (e.g., `ClipGenerationService`, `LeagueIntelligenceService`)

### Authentication & Authorization
- **Session Auth:** `auth_boot()` initializes session ([app/lib/auth.php](app/lib/auth.php))
- **Guards:** Use `require_auth()`, `require_role('platform_admin')`, `require_club_admin_access()` at route entry
- **CSRF:** API mutations require `X-CSRF-Token` header validated via `require_csrf_token()` ([app/lib/csrf.php](app/lib/csrf.php))
- **Roles:** `platform_admin` (super user), `club_admin` (club scoped), `analyst` (match editing), `viewer` (read-only)

### Event Timing Convention
**CRITICAL:** All event timing uses `match_second` (integer seconds into match) as single source of truth. `minute` and `minute_extra` columns were removed (see [CHANGELOG-MINUTE-REMOVAL.md](CHANGELOG-MINUTE-REMOVAL.md)). Display minutes as `floor(match_second / 60)`.

### Real-Time Session Sync
Server-authoritative WebSocket system for multi-device playback sync:
- **Node Server:** [scripts/match-session-server.js](scripts/match-session-server.js) (Socket.IO + MySQL)
- **Bootstrap:** PHP endpoint `/api/matches/{id}/session` generates session tokens
- **Client:** [public/assets/js/desk-session.js](public/assets/js/desk-session.js) handles state sync
- **TV Mode:** `/matches/{id}/tv` is viewer-only (no controls)
- **Analyst Mode:** `/matches/{id}/desk` controls playback with lock acquisition
- **Systemd Service:** `systemctl status match-session.service`

### Video Clip Pipeline
1. **Trigger:** Event save in desk UI calls [app/lib/clip_generation_service.php](app/lib/clip_generation_service.php)
2. **Job Queue:** Inserts row into `clip_jobs` table with payload (start/end times, paths)
3. **Worker:** Python daemon [py/clip_worker/worker.py](py/clip_worker/worker.py) polls queue, runs FFmpeg
4. **Phase 3 Flag:** Controlled by `PHASE_3_VIDEO_LAB_ENABLED` env var (enables/disables clip generation)

## Code Conventions

### PHP Style
- **No autoloading:** Use `require_once __DIR__ . '/path/to/file.php'` at top of every file
- **Functions not classes:** Prefer procedural functions (`get_match()`, `save_event()`) over OOP except for Controllers/Services
- **Return early:** Validate/guard at function start, avoid deep nesting
- **Type hints:** Use sparingly (only for class methods and complex services)

### API Response Pattern
All JSON APIs use standardized helpers from [app/lib/api_response.php](app/lib/api_response.php):
```php
api_success(['data' => $result]);  // Sets ok:true, adds request_id
api_error('invalid_input', 400);    // Logs, sets HTTP status, exits
```

### Database Access
```php
$pdo = db();  // Get singleton PDO instance
$stmt = $pdo->prepare('SELECT * FROM matches WHERE id = :id');
$stmt->execute(['id' => $matchId]);
$match = $stmt->fetch();  // Returns associative array
```

### Frontend Patterns
- **No framework:** Vanilla JS with direct DOM manipulation
- **API Calls:** Native `fetch()` with CSRF token from global `window.csrfToken`
- **State Management:** Module-scoped objects (e.g., `DeskSession` in [desk-session.js](public/assets/js/desk-session.js))
- **Event Bus:** Custom events on `document` for cross-module communication

## Development Workflows

### Build Commands
```bash
# Tailwind development (watch mode)
npm run dev:tailwind

# Tailwind production build
npm run build:tailwind

# WebSocket session server
npm run ws:server  # or systemctl restart match-session.service
```

### Python Clip Worker
```bash
cd py/clip_worker
python -m clip_worker  # Polls clip_jobs table, runs FFmpeg
```

### Database Migrations
- **Location:** [sql/](sql/) directory with dated filenames (e.g., `04-02-2026 - 1000.sql`)
- **Apply:** Manually via MySQL CLI or phpMyAdmin
- **Document:** Update relevant CHANGELOG if schema affects application logic

### Debugging
- **PHP Errors:** Enabled via `display_errors=1` in [public/index.php](public/index.php)
- **API Logging:** `api_log_exception()` writes to error log with context
- **Match Wizard:** Detailed logs in `storage/logs/match_wizard_debug.log`
- **Session Server:** `journalctl -u match-session.service -n 100 --no-pager`

## Key Directories

### Backend
- `app/lib/` - Core business logic (repositories, services, helpers)
- `app/controllers/` - Route handlers for complex views (extends `Controller` base class)
- `app/api/` - JSON endpoints (each file is standalone, not class-based)
- `app/views/` - PHP templates (pages, partials, layouts)
- `config/` - Database credentials and feature flags

### Frontend
- `public/assets/js/` - Vanilla JS modules
- `public/assets/css/` - Generated Tailwind output
- `resources/css/` - Tailwind source files

### Infrastructure
- `scripts/` - Node.js servers and PHP maintenance scripts
- `py/clip_worker/` - Python FFmpeg background processor
- `storage/logs/` - Application logs
- `videos/` - Match footage and generated clips

## Common Pitfalls

1. **Don't use `minute` column** - It was removed, calculate from `match_second`
2. **API files bypass router** - They're accessed directly via `/api/path/file.php`, handle auth inline
3. **Repositories are functions** - Call `get_match($id)` not `MatchRepository->get($id)`
4. **CSRF required for mutations** - All POST/PUT/DELETE APIs must validate token
5. **Phase 3 flag** - Check `phase3_is_enabled()` before clip operations
6. **Match locks** - Acquire lock before editing match data via `match_lock_service.php`

## Database Schema

### Core Entities
Multi-tenant system with `club_id` scoping most entities:

**Organizational Hierarchy:**
- `clubs` → `seasons` → `competitions` → `matches`
- `clubs` → `teams` (one club team + opponent teams)
- `clubs` → `players` → `match_players` (roster snapshots per match)

**Match Structure:**
- `matches` - Central entity with `home_team_id`, `away_team_id`, `club_id`
  - `match_periods` - Half/quarter definitions (preset or custom)
  - `match_players` - Per-match roster with shirt numbers, positions
  - `match_substitutions` - In/out player swaps with timing
  - `match_formations` - Tactical setups (home/away)
  - `match_videos` - Video source paths and metadata
  - `match_sessions` - WebSocket session state (JSON)
  - `match_locks` - Concurrent edit protection

**Event Tagging:**
- `events` - Tagged moments with `match_second`, `event_type_id`, `match_player_id`
  - Links to `match_periods`, `clips`, optional `match_player_id`
  - `event_types` - Club-scoped taxonomy (goals, shots, fouls, etc.)
  - `event_tags` - Many-to-many with `tags` for categorization
  - `event_snapshots` - Historical versions for audit
  - `event_suggestions` - AI-generated tagging recommendations

**Video Clips:**
- `clips` - Generated video segments linked to `events`
  - `clip_jobs` - Queue for Python FFmpeg worker (`pending`/`processing`/`completed`)
  - `clip_reviews` - Quality ratings and feedback
- `playlists` - Clip collections with `playlist_clips` ordering

**Statistics:**
- `derived_stats` - Computed match metrics (shots, possession, etc.)
- `stat_overrides` - Manual stat corrections

**Audit & Access:**
- `audit_log` - Full before/after JSON for creates/updates/deletes
- `users` → `user_roles` → `roles` (platform_admin, club_admin, analyst, viewer)

### Key Relationships
```sql
matches.club_id → clubs.id (ON UPDATE CASCADE)
matches.home_team_id → teams.id
matches.away_team_id → teams.id
events.match_id → matches.id (ON DELETE CASCADE)
events.match_player_id → match_players.id (ON DELETE SET NULL)
events.clip_id → clips.id (ON DELETE SET NULL)
clips.event_id → events.id (ON DELETE CASCADE)
clip_jobs.match_id → matches.id (ON DELETE CASCADE)
```

## Match Wizard Flow

Multi-step match creation process with detailed logging to `storage/logs/match_wizard_debug.log`:

### Phase 1: Match Record Creation
1. **Validation** ([app/api/matches/create.php](app/api/matches/create.php)):
   - Check user permissions (`can_manage_matches()`)
   - Validate club ownership of at least one team
   - Ensure home/away teams are different
   - Verify season/competition belong to club
   - Validate competition is within selected season
2. **Creation:**
   - Insert into `matches` table via `create_match()` ([app/lib/match_repository.php](app/lib/match_repository.php))
   - Generate unique ID, set initial `status` (`draft` or `ready`)
   - Store `video_source_type` (`upload`, `veo`, `youtube`) and path
3. **Logging:**
   - Each step logs to `match_wizard_debug.log` with stage tags: `[php]`, `[spawn]`, `[poll]`
   - Tracks: user ID, club ID, teams, video type, validation failures

### Phase 2: Video Processing (if applicable)
- **Upload:** Direct file upload to `videos/matches/{match_id}/`
- **Veo:** External downloader via [py/veo_downloader.py](py/veo_downloader.py) (spawned process)
- **YouTube:** Similar external download workflow
- **Progress Tracking:** Poll `/api/matches/{id}/video_status.php` for completion

### Phase 3: Match Setup
- **Periods:** Define match structure (e.g., 2x45min halves) via `/matches/{id}/periods`
- **Lineup:** Assign players to match roster via `/matches/{id}/lineup`
  - Creates `match_players` rows with positions, shirt numbers
  - Enforces substitution locking (can't remove players involved in subs)
- **Formations:** Optional tactical setup via `/matches/{id}/roster`

### Phase 4: Desk (Tagging)
- Navigate to `/matches/{id}/desk` to begin event tagging
- Real-time session sync if WebSocket server running
- Events auto-generate clips if `PHASE_3_VIDEO_LAB_ENABLED=true`

### Error Handling
- All validation errors set `$_SESSION['match_form_error']` and redirect
- JSON API mode returns `{ok: false, error_code: 'match_creation_*'}` responses
- Detailed logs include context arrays (team IDs, validation failures)

## League Intelligence Integration

External sports data import system for enriching match context:

**Purpose:** Import fixtures, team stats, league tables from external sources (e.g., league websites, APIs)

**Components:**
- **Service:** [app/lib/league_intelligence_service.php](app/lib/league_intelligence_service.php) - Aggregates team stats, computes league tables
- **Storage:** `league_intelligence_matches` table stores external fixture data
- **UI:** `/league-intelligence/matches` - CRUD interface for platform admins
- **Import Flow:** Manual import via web scraping or API → stored in database → displayed in match preview

**Key Features:**
- Form guide (last 5 results)
- Head-to-head history
- League position and points
- Goal statistics and trends

**Integration Point:** Match preview pages can pull intelligence data to display opponent context before/during analysis

## Example Workflows

### Add a new API endpoint
1. Create `app/api/domain/action.php`
2. Add `require_once` for `auth.php`, `api_response.php`, relevant repos
3. Call `auth_boot()` and `require_auth()` or `require_role()`
4. Validate CSRF if mutating: `require_csrf_token()`
5. Use `api_success()` / `api_error()` for responses

### Add a new match view
1. Define route in [public/index.php](public/index.php): `route('/matches/(\d+)/my-view', function($id) { ... })`
2. Load match: `$match = get_match((int)$id);`
3. Check permissions: `can_view_match($user, $roles, $match['club_id'])`
4. Set `$match` variable and include view: `require __DIR__ . '/../app/views/pages/matches/my-view.php';`

### Modify event timing logic
1. Ensure `match_second` is always set and validated
2. Calculate display minute as `floor($event['match_second'] / 60)`
3. Update both backend validation ([app/lib/event_validation.php](app/lib/event_validation.php)) and frontend form handling
4. Test with period boundaries (e.g., half-time, extra time)
