# Quakrs Site Visual Identity (quakrs.com)

## Current baseline (March 11, 2026)
- Direction: dark, restrained, scientific, editorial.
- Preferred UI rhythm: fewer visible containers, more whitespace, stronger type hierarchy.
- Accent usage (`--cyan`, `--yellow`): only for focus/state/data emphasis, never as broad decorative fill.
- Avoid heavy glow, glossy gradients, legacy dashboard gauge aesthetics, and dense equal-weight card grids.

## Purpose
- Document the current visual identity of `quakrs.com`.
- Preserve existing visual behavior when adding or updating UI in `quakrs-site`.
- Record current palette, fonts, UI patterns, and global layout rules from the live codebase.

## Source files used
- `/quakrs-site/assets/css/styles.css`
- `/quakrs-site/partials/head.php`
- `/quakrs-site/partials/topbar.php`
- `/quakrs-site/pages/*.php`

## Current font stack (already in use)
- Body/default text: `"Manrope", sans-serif`
- Display/headings/brand/metrics: `"Space Grotesk", sans-serif`
- Loaded in head from Google Fonts:
  - `Manrope:wght@400;600;700;800`
  - `Space Grotesk:wght@500;700`

## Current palette (already in CSS)

### Core CSS variables (`:root`)
- `--bg: #070b14`
- `--surface: rgba(16, 26, 45, 0.88)`
- `--surface-2: rgba(22, 34, 58, 0.9)`
- `--line: rgba(154, 178, 214, 0.2)`
- `--text: #ecf3ff`
- `--muted: #9bb0d0`
- `--cyan: #5de4c7`
- `--yellow: #ff5f45`

### Additional hardcoded colors currently used
- `#03201c`, `#0d121a`, `#121923`, `#1a0704`, `#1b6b68`, `#1f355f`
- `#22d3ee`, `#50e3c2`, `#6e7e96`, `#94f1dd`, `#f7d21e`
- `#ff5f45`, `#ff7a5f`, `#ff895b`
- `rgba(255, 255, 255, 0.9)`

## Main UI patterns (already established)

### Global containers and page shell
- Shared max width: `.topbar`, `.hero`, `.panel`, `.launch` use `width: min(1240px, 94vw); margin: 0 auto;`
- Background style on `body`: restrained radial atmosphere plus `var(--bg)`.
- Ambient glows are intentionally subtle (low opacity), not dominant.

### Navigation and actions
- Brand: `.brand` uses `Space Grotesk`, bold, slight tracking.
- Primary nav links: compact dark surfaces with restrained active state (no bright gradient pills).
- Buttons: compact radii (`8-10px`), flatter fills, low-contrast borders.

### Cards, rows, borders, radii
- Base card pattern: `.card`
  - restrained border (`var(--line)` mixed with transparency)
  - `border-radius: 14px`
  - flatter dark surface
  - around `1rem` padding
- Map frame: `.map-wrap`
  - dark flat panel with moderate border contrast
- Event rows: `.event-item`
  - compact, lighter border contrast
  - flatter background, minimal visual noise
- Region/timeline rows: `.region-row`, `.timeline-row`
  - same restrained treatment as event rows
- Form input/button pair in launch section:
  - inputs and controls stay flat dark with subtle focus ring/border shift

### Lists and chart structures
- Lists are compact and editorial:
  - `.events-list` uses tight rhythm and avoids boxed-overboxed stacks
  - `.regions-list`, `.timeline-list` gap `0.44rem`
- Bar chart rows (`.bar-row`) use a 3-column compact grid.
- Bar tracks are pill-shaped (`border-radius: 999px`) with bordered dark surface.
- Bar fills use a multicolor gradient (`#22d3ee -> #f7d21e -> #ff7a5f`).

### Spacing rhythm
- Moderate spacing with clearer separation between major content blocks.
- Section spacing examples:
  - `.hero` has larger breathing room than legacy dashboard variants
  - `.panel` gap around `1rem`
  - `.launch` feels like a light continuation, not a heavy boxed section

### Footer tone
- Footer should be visually light:
  - no heavy card container around the entire footer body
  - low-contrast text and links
  - minimal structural lines only

## Global layout rules already present
- Grid systems:
  - `.page-grid`: 3 columns desktop
  - `.panel-kpi`: 4 columns desktop
  - `.panel-main`: 2-column split (`1.45fr` + side column)
  - `.panel-charts`: 3 columns desktop
- Responsive breakpoints:
  - `@media (max-width: 1120px)`:
    - `.page-grid` and `.panel-kpi` drop to 2 columns
    - `.panel-main` and `.panel-charts` become single column
  - `@media (max-width: 760px)`:
    - top bar wraps
    - hero becomes vertical
    - `.panel-kpi` and `.page-grid` become 1 column
    - `.map-legend` goes from 6 columns to 3

## Mandatory rules for future UI work
- The Quakrs website already has an established visual identity.
- Future UI work must reuse existing CSS classes and variables.
- Do not introduce new colors unless absolutely necessary.
- Do not introduce new fonts.
- New components must visually match existing ones.
- Prefer one dominant main content area per page over many equal-weight cards.
- Avoid legacy enterprise/SaaS dashboard look (excess outlines, pills, glowing widgets).

## Scope and enforcement
- This document applies only to website work in `/quakrs-site`.
- `/quakrs-extension` remains independent and is not bound by these style rules.
- Before generating UI changes in `/quakrs-site`, read this file first.
