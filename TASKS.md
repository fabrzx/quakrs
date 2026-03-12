# TASKS.md — Quakrs Navigation Refactor Backlog

Derived from `ROADMAP.md` on 2026-03-09.

## Current State Snapshot

Current top navigation in `quakrs-site/partials/topbar.php`:

- Live (`/`)
- Monitors (`/earthquakes.php`, `/volcanoes.php`, `/tsunami.php`, `/space-weather.php`)
- Maps (`/maps.php`, `/maps-heatmap.php`, `/maps-plates.php`, `/maps-depth.php`)
- Cams (`/cams-volcanoes.php`, `/cams-hotspots.php`)
- Data (`/data-archive.php`, `/data-energy.php`, `/data-reports.php`, `/data-clusters.php`, `/data-api.php`)
- Resources (`/resources-safety.php`, `/resources-glossary.php`, `/resources-bulletins.php`)
- About (`/about-sources.php`, `/about-methodology.php`)

Current implementation coverage:

- Implemented: `Live`, `Monitors`, `Maps`, `Cams`, `About` core pages and submenu wiring.
- Implemented: `Data` baseline (`Archive`, `Energy`, `Reports`, `Clusters`, `API`).
- Implemented: `Resources` baseline (`Safety Guides`, `Glossary`, `Bulletins`) with `api/bulletins.php`.
- Implemented quality pass on 2026-03-10: `Volcanoes` and `Data > Clusters` upgraded with live KPI/list modules from APIs; glossary and safety resources expanded with additional operational terms/checklists.
- Implemented polish pass on 2026-03-10: `Data > Energy`, `Data > Archive`, and `Resources > Bulletins` upgraded with narrative insight blocks, quick presets, category/source pulse modules, and stronger visual readability beyond raw KPI rows.

## Navigation Refactor Tasks (Replacement, Not Duplication)

1. Replace top-level menu architecture
- Goal: Replace current nav with `Live`, `Monitors`, `Maps`, `Cams`, `Data`, `Resources`, `About`.
- Status: Implemented baseline topbar IA on 2026-03-09 (dropdown hierarchy + legacy route mapping). Pending: wire new destination pages as they are created.
- Steps:
1. Refactor menu config to hierarchical structure.
2. Remove obsolete standalone items (`Global Tremors`, `Analytics`) from top-level nav.
3. Keep routes reachable by relocating them under new sections.
- Files:
- `quakrs-site/partials/topbar.php`
- `quakrs-site/assets/css/styles.css`
- `quakrs-site/assets/js/main.js` (if nav interactions are added)

2. Map existing pages to new IA without content loss
- Goal: Preserve useful existing pages while re-homing them.
- Status: Canonical routes introduced on 2026-03-09 for `Data > Energy`, `Data > Clusters`, `About > Sources`, `About > Methodology`, with legacy aliases (`/analytics.php`, `/tremors.php`, `/about.php`) redirected to canonical pages.
- Mapping plan:
- `/` (Home) -> `Live`
- `/earthquakes.php` -> `Monitors > Earthquakes`
- `/volcanoes.php` -> `Monitors > Volcanoes`
- `/maps.php` -> `Maps > Global Map` (base)
- `/analytics.php` + `/tremors.php` -> `Data > Energy/Clusters` components (split by feature)
- `/about.php` -> split into `About > Sources` and `About > Methodology`
- Steps:
1. Create route aliases or new files before removing old links.
2. Migrate module sections from `analytics.php` and `tremors.php` into new destination pages.
3. Remove duplicated entry points after migration is validated.
- Files:
- `quakrs-site/index.php`
- `quakrs-site/earthquakes.php`
- `quakrs-site/volcanoes.php`
- `quakrs-site/maps.php`
- `quakrs-site/analytics.php`
- `quakrs-site/tremors.php`
- `quakrs-site/about.php`
- `quakrs-site/pages/*.php`

3. Build missing `Monitors` pages
- Goal: Add `Tsunami Alerts` and `Space Weather`.
- Status: Implemented on 2026-03-10 (`/tsunami.php`, `/space-weather.php`, cache-first APIs, nav wiring, refresh wiring).
- Steps:
1. Create pages and API endpoints with cache-first strategy.
2. Display required fields from roadmap.
3. Link from `Monitors` submenu and `Live` quick links.
- Files:
- `quakrs-site/pages/tsunami.php` (new)
- `quakrs-site/pages/space-weather.php` (new)
- `quakrs-site/api/tsunami.php` (new)
- `quakrs-site/api/space-weather.php` (new)
- `quakrs-site/config/feeds.php`

4. Expand `Maps` into 4 dedicated views
- Goal: Add `Heatmap`, `Tectonic Plates`, `Depth View` while keeping existing global map.
- Status: Implemented with real Leaflet maps on 2026-03-10 (`/maps.php`, `/maps-heatmap.php`, `/maps-plates.php`, `/maps-depth.php`) and live data-driven overlays from earthquakes feed. Pending: optional further visual refinement.
- Steps:
1. Keep current `/maps.php` as `Global Map`.
2. Add separate pages/modules for heatmap, plates overlays, depth visualization.
3. Reuse earthquake data pipeline and add overlay dataset loader for plates/faults.
- Files:
- `quakrs-site/pages/maps.php` (adapt as Global Map)
- `quakrs-site/pages/maps-heatmap.php` (new)
- `quakrs-site/pages/maps-plates.php` (new)
- `quakrs-site/pages/maps-depth.php` (new)
- `quakrs-site/assets/js/main.js`

5. Add `Cams` section
- Goal: Create `Volcano Cams` and `Eruption Hotspots`.
- Status: Implemented baseline on 2026-03-10 (`/cams-volcanoes.php`, `/cams-hotspots.php`, `api/volcano-cams.php`, `api/hotspots.php`, nav wiring, refresh wiring).
- Steps:
1. Implement curated webcam directory cards with source attribution.
2. Add snapshot fallback strategy for unreliable streams.
3. Generate hotspot list linked to `Monitors > Volcanoes`.
- Files:
- `quakrs-site/pages/cams-volcanoes.php` (new)
- `quakrs-site/pages/cams-hotspots.php` (new)
- `quakrs-site/api/volcano-cams.php` (new)
- `quakrs-site/api/hotspots.php` (new)

6. Add `Data` section pages
- Goal: Implement `Archive`, `Energy`, `Reports`, `Clusters`, `API`.
- Status: Implemented baseline on 2026-03-10 (`/data-archive.php`, `/data-energy.php`, `/data-reports.php`, `/data-clusters.php`, `/data-api.php`) with nav wiring and searchable archive filters.
- Steps:
1. Move existing charts from analytics into `Energy`.
2. Convert tremor cluster concepts into `Clusters`.
3. Add searchable archive with filters (time, magnitude, depth, region).
4. Add API documentation page for existing endpoints.
- Files:
- `quakrs-site/pages/data-archive.php` (new)
- `quakrs-site/pages/data-energy.php` (new)
- `quakrs-site/pages/data-reports.php` (new)
- `quakrs-site/pages/data-clusters.php` (new)
- `quakrs-site/pages/data-api.php` (new)
- `quakrs-site/api/bootstrap.php`
- `quakrs-site/api/earthquakes.php`
- `quakrs-site/api/volcanoes.php`
- `quakrs-site/api/tremors.php`

7. Add `Resources` section pages
- Goal: Implement `Safety Guides`, `Glossary`, `Bulletins`.
- Status: Implemented baseline on 2026-03-10 (`/resources-safety.php`, `/resources-glossary.php`, `/resources-bulletins.php`, `/api/bulletins.php`) with institutional feed aggregation and no editorial transforms.
- Steps:
1. Create static safety and glossary pages.
2. Create bulletins aggregator from trusted institutional RSS only.
3. Enforce no editorial/news commentary in bulletins content.
- Files:
- `quakrs-site/pages/resources-safety.php` (new)
- `quakrs-site/pages/resources-glossary.php` (new)
- `quakrs-site/pages/resources-bulletins.php` (new)
- `quakrs-site/api/bulletins.php` (new)
- `quakrs-site/config/feeds.php`

8. Split and harden `About` section
- Goal: Replace generic about page with `Sources` + `Methodology`.
- Status: Implemented on 2026-03-10 with expanded provider coverage in `Sources` and explicit pipeline/caching/refresh/map+cluster documentation in `Methodology`. Legacy `/about.php` remains canonical redirect to `/about-sources.php`.
- Steps:
1. Extract provider list into Sources page.
2. Document normalization, caching, refresh frequency, map and clustering logic.
3. Link both under `About` menu only.
- Files:
- `quakrs-site/pages/about-sources.php` (new)
- `quakrs-site/pages/about-methodology.php` (new)
- `quakrs-site/pages/about.php` (deprecate after migration)

9. Upgrade `Live` page as operational dashboard
- Goal: Make `/` a multi-hazard mission panel.
- Status: Implemented on 2026-03-10 with operational hazard status blocks (volcano, tsunami, space weather), explicit quick links to `Monitors`/`Data Archive`, and bootstrap preloading extended to `tsunami` + `space-weather`.
- Steps:
1. Keep existing earthquake KPIs and snapshot.
2. Add tsunami, volcano, and space-weather status blocks.
3. Add quick links to Monitors and Data Archive.
- Files:
- `quakrs-site/pages/home.php`
- `quakrs-site/partials/footer.php` (extend bootstrap payload)
- `quakrs-site/assets/js/main.js`

10. Cleanup and de-duplication pass
- Goal: Ensure no duplicate menu structures or orphan links.
- Status: Implemented and re-validated on 2026-03-10. Legacy routes (`/analytics.php`, `/tremors.php`, `/about.php`) remain permanent redirects (301) to canonical destinations; topbar submenu routes were checked against filesystem targets; obsolete home hero `Live status` code path removed from JS/CSS to avoid future drift.
- Steps:
1. Remove old menu labels and redundant pages after migration.
2. Check every topbar/submenu item resolves to exactly one canonical page.
3. Verify no section duplicates feed functionality unnecessarily.
- Files:
- `quakrs-site/partials/topbar.php`
- `quakrs-site/pages/*.php`
- `quakrs-site/*.php`

## Execution Order

1. Task 1 (nav refactor shell)
2. Task 2 (route mapping and migration)
3. Tasks 3-9 (feature completion by section)
4. Task 10 (cleanup + canonicalization)

## Session Rule

At each new session:

1. Read `ROADMAP.md`.
2. Re-check implemented pages/routes.
3. Update this `TASKS.md` status and next actionable items.
