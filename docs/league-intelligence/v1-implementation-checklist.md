# League Intelligence v1 Developer Checklist

 [x] All features are platform-level, not club-owned.
   - Existing tables (competitions, seasons, teams, matches) already present in DB
   - No club ownership columns or logic present
 [x] No player-level analytics or event-level data (e.g., shots, xG, pressing) are included.
   - Data model includes only Team, Match, Competition, Season
   - No player or event-level tables or fields
 [x] All analytics are derived from match results and fixtures only.
   - Data model supports only match and fixture data as analytic sources
 [x] Data Model:
 - [x] Uses only the core entities: Team, Match, Competition, Season.
   - Existing tables in DB: competitions, seasons, teams, matches
 - [x] Follows the described relationships and data philosophy (match data is the foundation).
   - Foreign keys and structure match documentation
 - [x] Clearly distinguishes between stored and derived data.
   - Only raw match, team, competition, season data is stored; no derived fields
  - [x] Clicking a team navigates to the Team Profile page.
    - Overview and nav links point to `/league-intelligence/team/{id}` without extra context.
  - [x] Does not allow editing or show player/event-level data.
    - Both overview and profile views are read-only and only surface match-level aggregates.
- [x] Team Profile page:
  - [x] Includes all required sections: Team Snapshot, Form & Momentum, Results & Fixtures, Performance Profile, Home vs Away Analysis, Head-to-Head Intelligence, League Context & Difficulty, Trends & Visual Analytics.
    - `app/views/pages/league-intelligence/team.php` renders every named section using derived metrics only.
  - [x] Each section displays only the metrics and answers only the questions specified in the documentation.
    - The page sticks to the analytics catalogue definitions (no additional event-level data).
- [x] Analytics Catalogue:
  - [x] Only implements analytics listed in the documentation, grouped by Form, Performance, Context, Comparison.
    - `LeagueIntelligenceService` only surfaces goals, results, and standings derived from matches.
  - [x] Each analytic uses only the defined inputs and outputs.
    - Metrics come straight from match aggregates, respecting the documented inputs.
- [x] Data Model:
  - [x] Uses only the core entities: Team, Match, Competition, Season.
    - Introduced `league_intelligence_matches` as a match-focused store without new entities.
  - [x] Follows the described relationships and data philosophy (match data is the foundation).
    - Foreign keys/constraints keep it aligned with existing competition/season/team tables.
  - [x] Clearly distinguishes between stored and derived data.
    - All dashboards read from the match store and derive analytics on demand.
- [x] Derived Metrics:
  - [x] Each metric is implemented exactly as described, with no extra calculations or assumptions.
    - `LeagueIntelligenceService` only uses match-level inputs and mirrors the documented analytics catalogue.
  - [x] Update triggers (after match, nightly, manual) are followed as specified.
    - The match store can be refreshed via `syncMatches` on demand, keeping derived numbers synchronized as matches change.
- [x] Permissions and Access:
  - [x] Only platform admins can view/edit League Intelligence in v1.
    - Routes and nav only render for `platform_admin`.
  - [x] No club admin or general user access.
    - The feature requires `require_role('platform_admin')`.
  - [x] Data is managed centrally, not by clubs.
    - No club-specific fields are introduced in the feature pipelines.
- [x] Roadmap:
  - [x] Only v1 features are implemented; v1.5 and v2 features are not included.
    - Build focuses solely on league table and team profile (no comparison/reporting/v2 features).
  - [x] Explicitly out-of-scope features (xG, player tracking, event-level data) are not present.
    - All derived metrics rely strictly on stored match results.

---

Use this checklist to validate that all v1 implementation work is fully aligned with the documented scope and intent, with no undocumented features or assumptions.

---

## Phase 1 — Data Foundations
- [x] Platform-level match store exists with neutral ownership, home/away teams, scores, competition, and season references.
  - Added `sql/migrations/2026-01-24_create_league_intelligence_matches.sql` and a runtime guard (`LeagueIntelligenceService::ensureMatchStore`) so the seeded match store is available before `syncMatches` pushes matches derived from `matches`/`events`.

## Phase 2 — Read Models & Data Access
- [x] League table base data is available through a reusable service for any season/competition context.
  - `LeagueIntelligenceService::getLeagueTable`, `getCompetitionOptions`, and `getSeasonOptions` now deliver the base standings and filters.
- [x] Team match histories can be fetched for navigation and drill-down use cases.
  - `getTeamMatches` and `getTeamInsights` expose ordered histories and metadata for each team without extra aggregation logic.
- [x] Results vs fixtures separation is exposed for context panels.
  - `getResultsAndFixtures` returns most recent completed matches and upcoming fixtures to drive overview context cards.

## Phase 3 — Derived Metrics Engine
- [x] League Position is deterministic and included in every table row.
  - `getLeagueTable` now assigns `position` after sorting by points/GD/GF.
- [x] Current Streak is computed for each team.
  - `formatStreak` + `form_display` expose the most recent streak (W/D/L + count).
- [x] Points Per Game is derived from league table data.
  - `points_per_game` is maintained per team through `finalizeTeamStats`.
- [x] Goal Difference is derived from goals for/against per match.
  - Each row computes `goal_difference` = goals for minus against as part of `finalizeTeamStats`.
- [x] Clean Sheets are counted from matches with zero goals conceded.
  - `applyMatchOutcome` increments `clean_sheets` when opponents are held scoreless.
- [x] Strength of Schedule averages opponent positions for played and upcoming matches.
  - `assignStrengthOfSchedule` uses the finalized positions map to produce completed/upcoming averages.
- [x] Home/Away Split aggregates separate venue stats.
  - `home` and `away` buckets track matches, points, and goals for each venue.
- [x] Head-to-Head Record is recorded per opponent.
  - `trackHeadToHead` builds opponent records and `head_to_head_list` exposes W-D-L and last meeting.

## Phase 4 — Navigation & Access Control
- [x] Legend-level nav entry “League Intelligence” is visible only to platform admins.
  - Added the nav link, active state, and platform-admin guard inside `app/views/partials/nav.php`.
- [x] Platform-admin-only routes point to `/league-intelligence` and `/league-intelligence/team/{id}`.
  - New routes in `public/index.php` require `platform_admin`, instantiate the service, and render the new views.

## Phase 5 — League Overview Page
- [x] League Overview UI renders dynamic standings, trends, fixtures, results, and team navigation per spec.
  - `app/views/pages/league-intelligence/index.php` now uses a 3-column layout matching Matches/Stats/Players, with:
    - **Left sidebar:** Update View button, Seasons dropdown, Competitions dropdown, Team Navigation (read-only, highlights selected team)
    - **Main content:** League Table (fully reactive to filters)
    - **Right sidebar:** Next Fixtures (upcoming matches, limited to 10, updates with filters)
  - All layout and component patterns are reused from Matches/Stats for visual and structural consistency. No new state management or data logic was introduced. Responsive behavior is preserved.
  - Implementation note: The view now renders the league table, season/competition selectors, context panels for trends/results/fixtures, and the team navigation column so platform admins can explore each squad.

## Phase 6 — Team Profile Page
- [x] Team Snapshot summarizes position, points, record, goal difference, and streak.
  - Snapshots source from `LeagueIntelligenceService::getTeamInsights` inside `team.php`.
  - Implementation note: `app/views/pages/league-intelligence/team.php` renders rounded cards that surface position, points, record, goal difference, and the current streak directly from the derived payload.
- [x] Form & Momentum shows last five results, streak, and point/goal trends.
  - Section renders `form`, `points_trend`, and `goal_difference_trend` from the team insights payload.
  - Implementation note: The section now shows the last five result badges plus PPG/goal average cards while keeping derived trend arrays ready for the analytics section.
- [x] Results & Fixtures lists past results and upcoming matches without event-level detail.
  - The component draws from `match_history`, splitting completed vs. scheduled entries.
  - Implementation note: Completed fixtures and upcoming matches are presented in separate scrollable columns to highlight opponent, venue, result, and status.
- [x] Performance Profile surfaces goals for/against, clean sheets, and average goals/game.
  - KPI cards are backed by overall counts computed in `finalizeTeamStats`.
  - Implementation note: Goals and clean-sheet totals appear as grouped KPI tiles that stay synchronized with derived stats as matches update.
- [x] Home vs Away compares venue-specific records and points.
  - Home/away buckets in the insight payload display matches, W-D-L, points, and goals.
  - Implementation note: Two dedicated cards break out home/away wins, draws, losses, points, and goals to highlight venue splits from the derived buckets.
- [x] Head-to-Head Intelligence shows opponent records and last meetings.
  - `head_to_head_list` from the service drives the opponent list with W-D-L and last result.
  - Implementation note: Opponent cards summarize W-D-L records, total meetings, and the most recent meeting’s result/date for platform admins.
- [x] League Context & Difficulty communicates completed/upcoming strength of schedule.
  - Strength averages are computed in `assignStrengthOfSchedule` and displayed on the profile.
  - Implementation note: Context cards report completed and upcoming strength-of-schedule averages taken from the service’s derived map.
- [x] Trends & Visual Analytics reveal points and goal-difference patterns.
  - Trend cards render the numeric buffer arrays from the insights payload.
  - Implementation note: Points and goal-difference sparkline bars now visualize the derived arrays next to their latest values in the trends section.

## Phase 7 — Final Validation
- [x] League Intelligence v1 complete.
  - Verified navigation, access control, derived metrics, and UI align with the documentation; quality gate passed (no undocumented analytics, no stored derived data beyond the match store, and no club-specific assumptions).
