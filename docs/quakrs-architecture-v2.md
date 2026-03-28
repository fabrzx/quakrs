# Quakrs Site Architecture Proposal v2

Last update: 2026-03-19
Baseline reference: `docs/quakrs-current-architecture.md`
Scope: IA/UX/Product architecture evolution for `/quakrs-site`.
Approach: incremental, non-destructive, preserving live/operational identity.

## 1) Design principles for v2

- Keep Quakrs operational-first, not news-first.
- Preserve macro-separation: Monitor, Mappe, Dati, Risorse, Info.
- Add missing cross-hazard horizontal layers (timeline, alerts, search, status clarity).
- Avoid route churn: prefer additive rollout + safe redirects.
- Preserve strong existing pages (`/event.php` seismic detail, `/data-italia.php` autonomous value).

## 2) Gap validation and proposed direction (delta vs baseline)

### 2.1 Earthquake event page (`/event.php`) preservation
- Baseline gap: not confirmed (explicitly protected).
- v2 direction:
  - Keep `/event.php` earthquake-centric.
  - Preserve current fast flow: click earthquake row -> immediate local tectonic context, faults, historical nearby events.
  - Improve only quality/performance/content clarity as needed, without changing core scope.

### 2.2 Cross-hazard live timeline
- Baseline gap: confirmed.
- v2 direction:
  - Add `/timeline.php` as central chronological stream.
  - Timeline cards: hazard badge, priority, status delta/new-event marker, area, time, source trust indicator, deep links.
  - Filters: hazard, priority, area scope (global/italy), status type (new/update/closed), time window.

### 2.3 Alerts/advisories center
- Baseline gap: confirmed.
- v2 direction:
  - Add `/alerts.php` with active advisories only.
  - Taxonomy: `warning`, `watch`, `advisory`, `elevated attention`, `information`.
  - Distinct from monitoring: monitor = full telemetry; alerts = current actionable alerting state.

### 2.4 Situation room / operational overview
- Baseline gap: confirmed.
- v2 direction:
  - Add `/situation.php` as compact command-view between Home and vertical monitors.
  - Modules: global status strip, active alerts summary, top evolving events, regional heat, feed health snapshot.

### 2.5 Multi-hazard archive
- Baseline gap: confirmed.
- v2 direction:
  - Move to federated archive model:
    - `/archive.php` (cross-hazard federated search shell)
    - hazard-specific archival datasets remain specialized behind unified filters.
  - Keep `/data-archive.php` temporarily as earthquake archive and redirect later when parity is achieved.

### 2.6 Sources + status + reliability clarity
- Baseline gap: confirmed.
- v2 direction:
  - Keep two pages but connect with a bridge:
    - `/about-sources.php`: provenance and source governance.
    - `/data-status.php`: live technical state.
    - `/sources-status.php`: feed cards with latency, cadence, freshness, quality flags, known limits.

### 2.7 Italy coverage (`/data-italia.php`) preservation + optional hub
- Baseline gap: not structural.
- v2 direction:
  - Keep `/data-italia.php` autonomous and central.
  - Optional complementary hub `/italia.php` only as orientation/navigation layer.
  - No replacement/absorption of `/data-italia.php`.
  - Swarm area already present in `/data-italia.php`; dedicated section can be evaluated later only if usage justifies it.

### 2.8 Personalization / My Quakrs
- Baseline gap: confirmed.
- v2 direction:
  - MVP without mandatory account: local preferences profile.
  - Add `/my-quakrs.php` (or topbar panel) with language default, preferred hazards, watch areas, thresholds, default map state.

### 2.9 Global search
- Baseline gap: confirmed.
- v2 direction:
  - Add `/search.php` and topbar quick search.
  - Grouped results: events, pages, reports, glossary terms, API docs.
  - Ranking: exact match > active/high-priority events > recency > authority.

### 2.10 Stronger technical status
- Baseline gap: confirmed.
- v2 direction:
  - Evolve `/data-status.php` into real status page: ingest state, latency/freshness, errors/backlog, degraded components, incident notes.

### 2.11 Priority model clarity (P1/P2/P3)
- Baseline gap: confirmed.
- v2 direction:
  - Keep `/priority-levels.php`, rewrite IA with operational examples, escalation/de-escalation logic, and limits.

### 2.12 Product updates/changelog
- Baseline gap: confirmed.
- v2 direction:
  - Add `/updates.php` for product evolution transparency.
  - Hybrid format: concise product updates + relevant technical notes.

## 3) Proposed sitemap v2 (target)

### Live
- `/` (home snapshot)
- `/situation.php` (new)
- `/timeline.php` (new)
- `/alerts.php` (new)

### Monitor
- `/earthquakes.php`
- `/aftershocks.php`
- `/volcanoes.php`
- `/tsunami.php`
- `/space-weather.php`
- `/data-italia.php` (preserved as strong autonomous page)
- `/italia.php` (optional complementary hub)

### Mappe
- `/maps.php`
- `/maps-heatmap.php`
- `/maps-plates.php`
- `/maps-depth.php`

### Cam
- `/cams-volcanoes.php`
- `/cams-hotspots.php`

### Dati
- `/archive.php` (new federated archive shell)
- `/data-archive.php` (temporary legacy route; future redirect)
- `/data-energy.php`
- `/data-reports.php`
- `/data-clusters.php`
- `/data-api.php`
- `/data-status.php` (strengthened)
- `/sources-status.php` (new bridge)

### Risorse
- `/resources-safety.php`
- `/resources-glossary.php`
- `/resources-bulletins.php`
- `/priority-levels.php` (rewritten content architecture)
- `/about-energy.php`

### Info
- `/about-sources.php`
- `/about-methodology.php`
- `/updates.php` (new)
- `/privacy.php`
- `/terms.php`

### Cross-cutting routes
- `/event.php` (preserved as seismic event detail)
- `/search.php` (new)
- `/my-quakrs.php` (new, MVP personalization)

## 4) Proposed final main menu v2

- Live
  - Home
  - Situation
  - Timeline
  - Alerts
- Monitor
  - Earthquakes
  - Aftershocks
  - Volcanoes
  - Tsunami
  - Space Weather
  - Italia Data
  - Italia (hub, optional)
- Mappe
  - Priority Map
  - Heatmap
  - Plates
  - Depth
- Cam
  - Volcano Cams
  - Hotspots
- Dati
  - Archive
  - Energy
  - Reports
  - Clusters
  - API
  - Data Status
  - Sources & Reliability
- Risorse
  - Safety
  - Glossary
  - Bulletins
  - Priority Levels
  - About Energy
- Info
  - Sources
  - Methodology
  - Updates

## 5) Route changes and migration policy

### New pages
- `/situation.php`
- `/timeline.php`
- `/alerts.php`
- `/archive.php`
- `/sources-status.php`
- `/search.php`
- `/my-quakrs.php`
- `/updates.php`
- `/italia.php` (optional, complementary)

### Rename/reposition candidates
- Primary archive UX label from `Data Archive` to `Archive` (route target `/archive.php`).
- Keep `/data-archive.php` as legacy route during migration.

### Merge/federation candidates
- Functional federation between `/about-sources.php` + `/data-status.php` via `/sources-status.php` (no hard merge at first).

### Explicitly preserved routes
- `/event.php` remains seismic-focused.
- `/data-italia.php` remains autonomous and first-class.

### Redirect plan (post-rollout)
- `/data-archive.php` -> `/archive.php` (when parity achieved).
- Keep existing redirects already active.

## 6) Prioritization (recommended delivery waves)

### Wave A (highest impact / low-medium complexity)
1. `/timeline.php`
2. `/alerts.php`
3. Strengthen `/data-status.php`
4. Rewrite IA of `/priority-levels.php`
5. Add `/sources-status.php`

### Wave B (structural consolidation)
1. `/situation.php`
2. `/search.php`
3. `/archive.php` federated rollout
4. `/updates.php`

### Wave C (expansion)
1. `/my-quakrs.php` MVP preferences
2. `/italia.php` optional navigation hub (only if validated by usage data)

## 7) UX guardrails for implementation

- Keep operational density high; avoid editorial bloat.
- Add complementary layers without replacing strong existing pages.
- Preserve seismic event immediacy and Italy page autonomy.
- Maintain compatibility with current routes via redirects and phased adoption.

## 8) Implementation artifacts (in progress)

- Step 1 timeline blueprint: `docs/quakrs-timeline-blueprint.md`
- Step 1 runtime status: `/timeline.php` MVP implemented (filters + cross-hazard aggregation + 60s refresh)
- Step 2 alerts blueprint: `docs/quakrs-alerts-blueprint.md`
- Step 2 runtime status: `/alerts.php` MVP implemented (active list + level/hazard/priority filters + ranking + 60s refresh)
- Step 3 data status blueprint: `docs/quakrs-data-status-blueprint.md`
- Step 3 runtime status: `/data-status.php` upgraded with operational impact banner + component matrix via `/api/health.php` extensions
- Step 4 priority levels blueprint: `docs/quakrs-priority-levels-blueprint.md`
- Step 4 runtime status: `/priority-levels.php` upgraded with operational IA (quick read, escalation/de-escalation, Priority vs Alerts, product links)
- Wave B runtime status: `/situation.php` MVP implemented (global status, alerts snapshot, evolving signals, component health; 60s refresh)
- Wave B runtime status: `/search.php` MVP implemented (cross-source index + category/hazard/scope filters + relevance ordering + 60s refresh)
- Wave B runtime status: topbar quick search popup implemented (instant input modal + advanced-search link to `/search.php`)
- Wave B runtime status: `/archive.php` federated archive entry implemented (menu routing + cross-hazard archive navigation + preserved `/data-archive.php` specialist flow)
- Wave A runtime status: `/sources-status.php` implemented as bridge page (source provenance + cadence/latency targets + freshness + known limits + links to `/about-sources.php` and `/data-status.php`)
- Wave B runtime status: `/updates.php` implemented as product-evolution log (navigation entry + structured release notes, non-news positioning)
- Wave C runtime status: `/my-quakrs.php` MVP implemented (local-only preferences, quick launch presets, no-account personalization baseline)
- Wave A runtime status: `/alerts.php` taxonomy/ranking upgraded (canonical level mapping + weighted deterministic rank + rule visibility in-page)
- Wave A runtime status: `/data-status.php` incident-log MVP added (auto incidents from degraded feeds/components + severity + degraded-since hints)
- Wave A runtime status: `/api/health.php` incident persistence added (active-state file + history file + opened/updated/resolved lifecycle exposed to status UI)
