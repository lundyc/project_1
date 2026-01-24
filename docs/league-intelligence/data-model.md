# Data Model

## Core Entities

- **Team**: Represents a football club or squad participating in a competition.
- **Match**: A single fixture between two teams, with result and context.
- **Competition**: A league or cup in which teams compete.
- **Season**: A time-bounded instance of a competition.

## Relationships

- A **Competition** contains many **Seasons**.
- A **Season** contains many **Matches** and **Teams**.
- A **Match** links two **Teams** (home and away) and belongs to a **Season**.
- **Teams** participate in many **Matches** per **Season**.

## Stored vs Derived Data

- **Stored**: Raw match results, fixtures, team and competition metadata.
- **Derived**: League tables, form, streaks, trends, and all analytics metrics.

## Data Philosophy

- **Match data is the foundation**: All analytics and trends are derived from the set of stored match results and fixtures.
- No event-level or player-level data is required or assumed.

---
