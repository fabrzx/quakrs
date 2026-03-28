# Quakrs Site Architecture Baseline (Current)

Last update: 2026-03-19
Scope: `/quakrs-site` (public website architecture)
Purpose: persistent baseline for future IA/UX/product iterations without rewriting current structure.

## 1) Current information architecture (as-is)

- Positioning: multi-hazard operational/editorial site (not pure news).
- Entry: Home (`/`) with live snapshot and cross-hazard modules.
- Primary navigation families:
  - Live
  - Monitor
  - Mappe
  - Cam
  - Dati
  - Risorse
  - Info (label in code: About)
- Language switch: IT/EN in topbar.
- Footer: quick links + legal links (`/privacy.php`, `/terms.php`) + live freshness notes.

## 2) Current main menu structure (from `partials/topbar.php`)

- Live
  - `/`
  - `/situation.php`
  - `/timeline.php`
  - `/alerts.php`
  - quick search popup in topbar -> `/search.php`
- Monitor
  - `/earthquakes.php`
  - `/aftershocks.php`
  - `/volcanoes.php`
  - `/tsunami.php`
  - `/space-weather.php`
- Mappe
  - `/maps.php`
  - `/maps-heatmap.php`
  - `/maps-plates.php`
  - `/maps-depth.php`
- Cam
  - `/cams-volcanoes.php`
  - `/cams-hotspots.php`
- Dati
  - `/data-italia.php`
  - `/archive.php`
  - `/data-archive.php` (seismic specialist module, still active)
  - `/data-energy.php`
  - `/data-reports.php`
  - `/data-clusters.php`
  - `/data-api.php`
  - `/data-status.php`
  - `/sources-status.php`
- Risorse
  - `/resources-safety.php`
  - `/resources-glossary.php`
  - `/resources-bulletins.php`
  - `/priority-levels.php`
  - `/about-energy.php`
- Info/About
  - `/about-sources.php`
  - `/about-methodology.php`
  - `/updates.php`
- Topbar utility actions (right side)
  - `/my-quakrs.php`
  - quick search popup -> `/search.php`

## 3) Current route inventory

### 3.1 Main public routes (sitemap + menu + direct pages)
- `/`
- `/situation.php`
- `/timeline.php`
- `/alerts.php`
- `/my-quakrs.php`
- `/search.php`
- `/earthquakes.php`
- `/aftershocks.php`
- `/volcanoes.php`
- `/tsunami.php`
- `/space-weather.php`
- `/maps.php`
- `/maps-heatmap.php`
- `/maps-plates.php`
- `/maps-depth.php`
- `/cams-volcanoes.php`
- `/cams-hotspots.php`
- `/data-italia.php`
- `/archive.php`
- `/data-archive.php`
- `/data-energy.php`
- `/data-reports.php`
- `/data-clusters.php`
- `/data-api.php`
- `/data-status.php`
- `/resources-safety.php`
- `/resources-glossary.php`
- `/resources-bulletins.php`
- `/priority-levels.php`
- `/about-sources.php`
- `/sources-status.php`
- `/about-methodology.php`
- `/about-energy.php`
- `/updates.php`
- `/privacy.php`
- `/terms.php`

### 3.2 Secondary/detail routes
- `/event.php` (earthquake-focused detail page; key site feature)
- `/data-italia-sciame.php` (single swarm detail)
- `/404.php`

### 3.3 Existing legacy redirects (HTTP 301)
- `/about.php` -> `/about-sources.php`
- `/analytics.php` -> `/data-energy.php`
- `/tremors.php` -> `/data-clusters.php`

## 4) Current architecture relations between sections

- Home aggregates top signals from Monitor + Mappe + selected data signals.
- Monitor pages are hazard-vertical real-time consoles.
- Mappe pages are visualization-specialized slices of active events.
- Dati pages are analytical/archival/diagnostic layers.
- `/archive.php` acts as federated multi-hazard archive entry.
- `/data-archive.php` remains the advanced seismic archive workflow.
- Risorse pages are explanatory and operational support material.
- Info/About pages explain sources and methodology.
- `/event.php` is a strong, immediate seismic drill-down flow tied to earthquake rows.
- `/data-italia.php` is a strong autonomous page and already includes swarm coverage.

## 5) Current strengths to preserve

- Clear macro-separation (Monitor/Mappe/Dati/Risorse/Info).
- Strong live posture with high refresh cadence.
- Good breadth of hazard verticals and map variants.
- Existing transparency pages (sources/methodology/status).
- Strong seismic event detail UX (`/event.php`).
- Strong Italy data page autonomy (`/data-italia.php`).

## 6) Current architecture gaps (priority baseline)

1. Cross-hazard timeline exists as MVP (`/timeline.php`) and requires iterative hardening.
2. Active alerts center (`/alerts.php`) now includes stricter taxonomy and deterministic ranking; needs next-step hardening on policy thresholds and historical state persistence.
3. Operational overview exists as MVP (`/situation.php`) and requires compaction/incident depth upgrades.
4. Federated archive entry now exists (`/archive.php`), but hazard parity and unified filter grammar still need iterative hardening.
5. Sources/status/reliability split now has a bridge page (`/sources-status.php`), but needs iterative standardization (field consistency + tighter taxonomy).
6. Lightweight personalization layer now exists (`/my-quakrs.php`, local-only); lacks cross-device sync and deeper per-page preference application.
7. Global search exists as MVP (`/search.php`) and requires ranking/category refinement.
8. Status page now includes persistent MVP incident history (`opened/updated/resolved`) plus component/feed degradation summary; still missing SLA-style uptime reporting and richer long-range analytics.
9. Priority model (P1/P2/P3) can be more operationally explicit.
10. Product updates/changelog page exists as MVP (`/updates.php`); needs editorial governance and release taxonomy over time.

## 7) Explicit non-gaps (protected decisions)

- Do not generalize `/event.php` into a generic multi-hazard event page.
- Do not treat `/data-italia.php` as a weak page to absorb/replace.
- Swarm coverage already exists in `/data-italia.php`; dedicated swarm section is optional future enhancement, not current structural requirement.

## 8) Baseline governance rules for next iterations

- Do not rewrite this current snapshot from scratch in future cycles.
- Update this file only when the current architecture actually changes.
- Keep proposed future architecture in separate files (v2, v3, etc.).
- Every proposal must reference deltas against this baseline.
