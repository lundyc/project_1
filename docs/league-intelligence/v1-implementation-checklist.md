# League Intelligence v1 Developer Checklist

- [ ] All features are platform-level, not club-owned.
- [ ] Access is restricted to platform admins during development.
- [ ] No player-level analytics or event-level data (e.g., shots, xG, pressing) are included.
- [ ] All analytics are derived from match results and fixtures only.
- [ ] League Overview page:
  - [ ] Displays league table with: Position, Team, Played, Wins, Draws, Losses, Goals For, Goals Against, Goal Difference, Points, Form (last 5), Streaks.
  - [ ] Supports sorting (Points, Goal Difference, Goals For) and filtering (season, competition, round).
  - [ ] Includes context panels: League Trends, Upcoming Fixtures, Recent Results.
  - [ ] Clicking a team navigates to the Team Profile page.
  - [ ] Does not allow editing or show player/event-level data.
- [ ] Team Profile page:
  - [ ] Includes all required sections: Team Snapshot, Form & Momentum, Results & Fixtures, Performance Profile, Home vs Away Analysis, Head-to-Head Intelligence, League Context & Difficulty, Trends & Visual Analytics.
  - [ ] Each section displays only the metrics and answers only the questions specified in the documentation.
- [ ] Analytics Catalogue:
  - [ ] Only implements analytics listed in the documentation, grouped by Form, Performance, Context, Comparison.
  - [ ] Each analytic uses only the defined inputs and outputs.
- [ ] Data Model:
  - [ ] Uses only the core entities: Team, Match, Competition, Season.
  - [ ] Follows the described relationships and data philosophy (match data is the foundation).
  - [ ] Clearly distinguishes between stored and derived data.
- [ ] Derived Metrics:
  - [ ] Each metric is implemented exactly as described, with no extra calculations or assumptions.
  - [ ] Update triggers (after match, nightly, manual) are followed as specified.
- [ ] Permissions and Access:
  - [ ] Only platform admins can view/edit League Intelligence in v1.
  - [ ] No club admin or general user access.
  - [ ] Data is managed centrally, not by clubs.
- [ ] Roadmap:
  - [ ] Only v1 features are implemented; v1.5 and v2 features are not included.
  - [ ] Explicitly out-of-scope features (xG, player tracking, event-level data) are not present.

---

Use this checklist to validate that all v1 implementation work is fully aligned with the documented scope and intent, with no undocumented features or assumptions.
