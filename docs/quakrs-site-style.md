# Quakrs Site Visual Identity (quakrs.com)

## Current baseline (March 17, 2026)
- Direction: **acid brutalist dark**, technical/editorial, high-contrast.
- Visual tone: tense, clean, sharp edges, minimal blur/glow.
- Keep dark dominance; acid colors are functional accents, never full-page decoration.

## Purpose
- Document the active visual identity for `quakrs-site`.
- Ensure new UI work preserves palette logic, hierarchy, and readability.

## Source files
- `/quakrs-site/assets/css/styles.css`
- `/quakrs-site/partials/head.php`
- `/quakrs-site/partials/topbar.php`
- `/quakrs-site/partials/footer.php`
- `/quakrs-site/pages/*.php`

## Font stack (unchanged)
- Body/default: `"Manrope", sans-serif`
- Display/headings/metrics/brand: `"Space Grotesk", sans-serif`

## Active palette tokens

### Core dark surfaces
- `--bg-0: #050816` (primary background)
- `--bg-1: #081021` (main panels)
- `--bg-2: #0d1630` (secondary surfaces)
- `--bg-3: #111c3d` (raised/hover)

### Lines and text
- `--line-soft: rgba(120, 160, 255, 0.14)`
- `--line-strong: rgba(120, 220, 255, 0.38)`
- `--text-1: #f3f7ff`
- `--text-2: #b7c3e0`
- `--text-3: #7f8cad`

### Acid accents
- `--acid-lime: #b7ff00`
- `--acid-cyan: #20e0ff`
- `--acid-magenta: #ff2bd6`
- `--acid-yellow: #ffe600`
- `--acid-orange: #ff7a00`

### Semantic state accents
- `--success-acid: #7dff3a`
- `--danger-acid: #ff4d6d`
- `--warning-acid: #ffd400`
- `--info-acid: #39d5ff`

### Hazard mapping
- `--hazard-earthquake: var(--acid-lime)`
- `--hazard-volcano: var(--acid-magenta)`
- `--hazard-tsunami: var(--acid-cyan)`
- `--hazard-space: var(--acid-yellow)`
- `--hazard-critical: var(--acid-orange)`

## Global variants
- Active site variant class on body: `site-acid-balanced`
- Optional stronger variant class: `site-acid-stronger`
- Home keeps matching local classes:
  - default: `home-acid-balanced`
  - optional: `home-acid-stronger`

## Distribution rule
- 75% dark surfaces
- 15% neutral text/lines
- 10% acid accents

## UI behavior rules

### Background and atmosphere
- Body remains very dark (`--bg-0` base) with restrained radial atmosphere.
- No heavy global glow; ambient effects must stay subtle.

### Cards, map frames, rows
- Use dark fills (`--bg-1` / `--bg-2`) and thin lines (`--line-soft`).
- Emphasis via border/top-border/left-border accents, not saturated full-card fills.
- Rows (`.event-item`, `.snapshot-row`, `.timeline-row`, `.region-row`) stay compact and readable.

### Buttons and links
- Primary CTA: acid orange fill, dark text.
- Secondary/ghost CTA: dark fill + cyan line accent.
- Inline/utility links: cyan to lime on hover.

### Metrics and tags
- Big numbers can use hazard/acid accents by context.
- Labels/supporting copy stay neutral (`--text-2` / `--text-3`).
- Badges/tags remain compact; bright fills only when semantically needed.

### Charts and legends
- Use semantic acid mapping for live/forecast/critical lines and dots.
- Keep chart backgrounds dark and unobtrusive.

### Footer
- Footer remains editorial and calmer than main content.
- Acid usage in footer is subtle (brand/link accents only), not dominant.

## Explicitly avoid
- Glassmorphism.
- Soft SaaS-style glows/shadows.
- Generic cyberpunk rainbow treatment.
- Unstructured multicolor buttons/tags without semantic mapping.
- Long text on saturated acid backgrounds.

## Mandatory rules for future UI work
- Reuse existing classes/variables before adding new ones.
- Prefer token-driven color changes over ad-hoc hardcoded colors.
- Do not change layout structure/spacing unless explicitly requested.
- Do not introduce new fonts.
- Keep editorial clarity first, visual punch second.
- Avoid edge-touching text inside cards: keep the same page-family paddings used across existing sections (for KPI cards and content cards) so labels/values never sit on card borders.

## Scope
- Applies only to `/quakrs-site`.
- `/quakrs-extension` is independent and not bound by this guide.
- Read this file before any UI changes in `/quakrs-site`.
