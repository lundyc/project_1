# PHASE 2 – Approved Layout Patterns

## 1. Standard 3-Column Layout
- **Structure:**
  - Left sidebar (filters, navigation): `col-span-2`
  - Main content: `col-span-7`
  - Right sidebar (context, actions): `col-span-3`
- **Grid:** `grid grid-cols-12 gap-2 px-4 md:px-6 lg:px-8 w-full`
- **Usage:** Matches index, stats pages, league pages

## 2. Standard 2-Column Admin Layout
- **Structure:**
  - Sidebar (navigation, filters): `col-span-3`
  - Main content: `col-span-9`
- **Grid:** `grid grid-cols-12 gap-2 px-4 md:px-6 lg:px-8 w-full`
- **Usage:** Admin pages (teams, players, clubs, etc.)

## 3. Full-Width Single-Column Layout
- **Structure:**
  - Main content only, centered or full-width
- **Grid:** `w-full max-w-5xl mx-auto px-4`
- **Usage:** Dashboards, login, simple pages

---

All layouts use only PHASE 1 design tokens for spacing, colour, and typography. No new tokens or layout types are introduced. All pages must map to one of these patterns.
