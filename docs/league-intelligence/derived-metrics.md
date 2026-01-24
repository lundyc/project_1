# Derived Metrics

Each metric below is derived from stored match and competition data.

---

## League Position
- **Description**: The team's current rank in the league.
- **Calculation**: Sort teams by points, then goal difference, then goals for.
- **Dependencies**: All match results in the competition.
- **Update Triggers**: After each match result.

## Current Streak
- **Description**: The team's ongoing sequence of wins, draws, or losses.
- **Calculation**: Count consecutive results of the same type from most recent match backward.
- **Dependencies**: Team’s match results.
- **Update Triggers**: After each match result.

## Points Per Game
- **Description**: Average points earned per match.
- **Calculation**: Total points divided by matches played.
- **Dependencies**: Team’s match results.
- **Update Triggers**: After each match result.

## Goal Difference
- **Description**: Goals scored minus goals conceded.
- **Calculation**: Sum of goals for minus sum of goals against.
- **Dependencies**: Team’s match results.
- **Update Triggers**: After each match result.

## Clean Sheets
- **Description**: Number of matches with zero goals conceded.
- **Calculation**: Count of matches where goals against = 0.
- **Dependencies**: Team’s match results.
- **Update Triggers**: After each match result.

## Strength of Schedule
- **Description**: Average league position of opponents played or upcoming.
- **Calculation**: Mean of opponent positions at time of match (or current for upcoming).
- **Dependencies**: Opponent positions, fixtures.
- **Update Triggers**: After each match, or when league table updates.

## Home/Away Split
- **Description**: Performance metrics separated by venue.
- **Calculation**: Aggregate metrics (points, goals, etc.) for home and away matches.
- **Dependencies**: Match venue, results.
- **Update Triggers**: After each match result.

## Head-to-Head Record
- **Description**: Results against a specific opponent.
- **Calculation**: Aggregate W-D-L for all matches vs that opponent.
- **Dependencies**: Match history vs opponent.
- **Update Triggers**: After each relevant match.

---
