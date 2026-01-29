# PHASE 0 – UI Inventory & Risks

## Layouts (Page-level Patterns)
- 3-column grid (filters / main / sidebar): e.g. matches index, stats
- 2-column admin grids: e.g. admin/players, admin/teams
- Full-width dashboards: e.g. dashboard.php
- Login page: custom flex/grid layout
- Standard layout wrapper: app/views/layout.php

## Components
- Cards: summary-score-card, stat cards, info panels
- Tables: admin lists, match stats, player lists
- Buttons: header buttons, form actions, icon buttons
- Sidebars: navigation, filters, right/left side panels
- Page headers: partials/header.php, section headers
- Action icons: SVGs for events, cards, goals, etc.

## Styling Sources
- Tailwind utility classes (primary)
- Custom CSS: assets/css/app.css
- CSS variables: e.g. --border-danger, --shadow-strong
- Legacy Bootstrap classes (some remain)
- Inline styles (for dynamic/conditional styling)

## Risks & Known Issues
- Mixed colour usage (Tailwind, CSS vars, legacy)
- Inconsistent spacing and grid gaps
- Some pages use legacy Bootstrap or inline styles
- PHP warnings/errors can leak into UI
- Not all components are reused (some page-specific variants)
- Some visual elements (e.g. cards, tables) have inconsistent borders/shadows
- Layout wrappers not always applied consistently
