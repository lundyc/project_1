# PHASE 1 – Design System Foundation

## Colour System
- **Primary Accent:** `--accent-primary: #1FB6FF` (Tailwind: sky-400)
- **Success:** `--accent-success: #22C55E` (Tailwind: emerald-500)
- **Warning:** `--accent-warning: #FACC15` (Tailwind: yellow-400)
- **Danger:** `--accent-danger: #EF4444` (Tailwind: red-500)
- **Backgrounds:**
  - `--bg-primary: #101624` (main background)
  - `--bg-secondary: #182033` (panel background)
  - `--bg-tertiary: #232B3E` (surface background)
- **Borders:**
  - `--border-subtle: #273043` (soft border)
  - `--border-soft: var(--border-subtle)`
- **Text:**
  - `--text-primary: #F4F7FA`
  - `--text-secondary: #B0B8C1`
  - `--text-muted: var(--text-secondary)`
- **All cards, tables, and panels use:**
  - Background: `var(--bg-surface)` or `var(--bg-panel)`
  - Border: `var(--border-soft)`

## Typography
- **Heading Scale:**
  - H1: `text-3xl font-extrabold`
  - H2: `text-2xl font-bold`
  - H3: `text-xl font-semibold`
  - H4: `text-lg font-semibold`
- **Body Text:**
  - `text-base` (default)
- **Muted/Secondary Text:**
  - `text-muted` (maps to `--text-muted`)
- **No ad-hoc font sizes/weights.**

## Spacing Scale
- **Base scale:** 4 / 8 / 16 / 24 px (Tailwind: 1, 2, 4, 6)
- **Card/Panel Padding:** `p-4` (16px)
- **Section Vertical Spacing:** `mb-6` (24px) or `space-y-4` (16px)
- **No custom or inconsistent spacing.**

## Core Visual Tokens
- **Border Radius:**
  - Small: `--radius-sm: 6px` (Tailwind: rounded-md)
  - Medium: `--radius-md: 12px` (Tailwind: rounded-xl)
- **Shadows:**
  - Use Tailwind shadow utilities (`shadow`, `shadow-lg`, `shadow-xl`)
  - No custom box-shadow unless tokenized
- **Icon Size:**
  - Standard: `w-5 h-5` (20px)
  - Colour: `fill-current` or `text-[accent|danger|success|warning]`

## Notes
- All tokens above are defined in `public/assets/css/app.css` and used via Tailwind or CSS variables.
- No layout or component refactors in this phase.
- No new features or responsive changes.

---

This document establishes the authoritative design system for the desktop UI. All future UI work must use these tokens and rules.
