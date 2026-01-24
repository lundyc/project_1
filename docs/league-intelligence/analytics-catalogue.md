# Analytics Catalogue

This catalogue defines all analytics supported in League Intelligence v1.

---

## Form

- **Current Streak**
  - Definition: The team's ongoing sequence of wins, draws, or losses.
  - Inputs: Last N match results.
  - Output: Label (e.g., "W3", "L2").

- **Last 5 Results**
  - Definition: Results of the most recent 5 matches.
  - Inputs: Match outcomes.
  - Output: List of labels (W/D/L).

- **Points Per Game**
  - Definition: Average points earned per match.
  - Inputs: Total points, matches played.
  - Output: Number (e.g., 1.75).

---

## Performance

- **Goals For/Against**
  - Definition: Total goals scored/conceded.
  - Inputs: Match results.
  - Output: Number.

- **Goal Difference**
  - Definition: Goals For minus Goals Against.
  - Inputs: Goals For, Goals Against.
  - Output: Number.

- **Clean Sheets**
  - Definition: Matches with zero goals conceded.
  - Inputs: Match results.
  - Output: Number.

- **Average Goals per Match**
  - Definition: Goals scored divided by matches played.
  - Inputs: Goals For, matches played.
  - Output: Number.

---

## Context

- **League Position**
  - Definition: Team’s current rank in the league.
  - Inputs: League table.
  - Output: Number.

- **Strength of Schedule**
  - Definition: Average league position of opponents played/upcoming.
  - Inputs: Opponent positions, fixtures.
  - Output: Number.

- **Home/Away Split**
  - Definition: Performance metrics separated by venue.
  - Inputs: Match venue, results.
  - Output: Table (home vs away).

---

## Comparison

- **Head-to-Head Record**
  - Definition: Results against a specific opponent.
  - Inputs: Match history vs opponent.
  - Output: Record (W-D-L).

- **League Trends**
  - Definition: League-wide stats (e.g., average goals per match).
  - Inputs: All match results.
  - Output: Number, trend.

---
