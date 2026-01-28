# League Intelligence

**League Intelligence** is a platform-level analytics feature designed to provide modern, contextual insights into football competitions. It is built for platform administrators and league managers who need a comprehensive, competition-centric view of teams, results, and trends.

## Who Is It For?
- Platform administrators (during development)
- League and competition managers
- Product and analytics teams

## Design Principles
- **Modern**: Uses up-to-date analytics concepts and visualizations.
- **Informative**: Surfaces actionable insights, not just raw data.
- **Contextual**: Always relates analytics to league, team, and match context.
- **Competition-Centric**: Focuses on leagues and competitions, not individual clubs or players.
- **Transparent**: Clearly distinguishes between stored and derived data.

## How to Use These Docs
- As the single source of truth for building, maintaining, and validating League Intelligence.
- To guide product, design, and engineering decisions.
- To ensure all stakeholders share a common understanding of scope, purpose, and data dependencies.
- To validate that implementation matches product intent.

## Importing Fixtures & Results (WOSFL)
League Intelligence fixtures/results are pulled from the West of Scotland Football League (WOSFL) via the admin UI.

### Full Import (Preview + Save)
1. Go to **League Intelligence** in the admin nav.
2. Click **Import All Fixtures & Results** to load a preview.
3. Review the preview, optionally delete any rows, then click **Save Import**.
4. The league table is rebuilt automatically after saving.

### Weekly Update (±7 Days)
1. Go to **League Intelligence** in the admin nav.
2. Click **Update This Week**.
3. The system immediately updates existing matches or adds new matches within ±7 days of today.
4. The league table is rebuilt automatically after the update completes.

### CLI
There are no CLI commands for the WOSFL import or weekly update yet. Use the UI routes above.
