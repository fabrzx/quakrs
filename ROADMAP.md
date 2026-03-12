# ROADMAP.md — Quakrs.com Development Roadmap

This file defines the long-term development roadmap for **Quakrs.com**.

If this file does not exist in the repository, **Codex must create it**.

Codex must **read this file at the beginning of every work session** to understand:

- the current architecture
- the planned features
- what has already been implemented
- what still needs to be built

If necessary, Codex must generate or update a **TASKS.md** file derived from this roadmap.

The roadmap is the **source of truth** for future development decisions.

---

# Critical rule

This roadmap describes a **refactor of the current navigation**, not an addition.

Codex must **NOT duplicate the existing menu**.

Instead it must:

1. Inspect the current navigation structure.
2. Identify current menu items and routes.
3. Map them to the new architecture.
4. Remove obsolete or duplicated entries.
5. Preserve useful pages by relocating them into the new structure.

The new structure must **replace the current menu**, not sit beside it.

---

# Target Navigation Structure

```
Live

Monitors
  Earthquakes
  Volcanoes
  Tsunami Alerts
  Space Weather

Maps
  Global Map
  Heatmap
  Tectonic Plates
  Depth View

Cams
  Volcano Cams
  Eruption Hotspots

Data
  Archive
  Energy
  Reports
  Clusters
  API

Resources
  Safety Guides
  Glossary
  Bulletins

About
  Sources
  Methodology
```

---

# Section Definitions

## Live

Primary operational dashboard.

Purpose:

- quick overview of global activity
- latest earthquakes
- active alerts
- fast entry to monitors

Suggested blocks:

- latest significant earthquakes
- active tsunami alerts
- elevated volcanic activity
- space weather status
- quick links to maps and archive

---

## Monitors

Real-time monitoring sections.

### Earthquakes

Realtime feed sourced from:

- USGS
- EMSC
- INGV

Display fields:

- magnitude
- location
- time
- depth
- optional distance

---

### Volcanoes

Volcanic activity monitoring.

Possible data sources:

- Smithsonian GVP
- INGV
- USGS volcano observatories

Display:

- volcano name
- country
- activity level
- last update
- alert status

---

### Tsunami Alerts

Active tsunami warnings.

Sources:

- NOAA
- PTWC

Display:

- region
- warning level
- issue time
- source bulletin

---

### Space Weather

Solar and geomagnetic monitoring.

Possible sources:

- NOAA SWPC

Display:

- Kp index
- storm level
- solar activity indicators

---

# Maps

Visualization tools.

### Global Map

Main interactive earthquake map.

Use existing implementation if already present.

---

### Heatmap

Density map of recent events.

Suggested window:

- last 24 hours
- last 48 hours

---

### Tectonic Plates

Overlay showing tectonic boundaries.

Optional layers:

- faults
- boundaries

---

### Depth View

Visualization focused on hypocenter depth.

Possible styles:

- color by depth
- pseudo-3D visualization

---

# Cams

Visual observation section.

---

## Volcano Cams

Directory of public volcano webcams.

Each card should contain:

- volcano name
- country
- preview image
- activity status
- last update
- source attribution

Example volcanoes:

- Etna
- Stromboli
- Kilauea
- Popocatépetl
- Sakurajima
- Merapi
- Fagradalsfjall

Possible providers:

- INGV
- USGS
- Iceland Met Office
- Smithsonian
- local observatories

Implementation suggestion:

If streaming is unreliable, use periodic snapshot caching.

---

## Eruption Hotspots

Automatically updated list of currently active volcanoes.

Fields:

- volcano
- country
- activity state
- webcam availability
- link to monitor page

---

# Data

Analytical and archival content.

---

## Archive

Searchable earthquake database.

Filters:

- time
- magnitude
- depth
- region

---

## Energy

Analytics about seismic energy release.

Possible charts:

- daily
- weekly
- monthly

---

## Reports

Periodic summaries.

Examples:

- weekly seismic summaries
- automated bulletins

---

## Clusters

Cluster / swarm detection.

Suggested output:

- area
- number of events
- time window
- maximum magnitude
- average depth

This should be treated as an **analytics feature**, not a duplicate feed.

---

## API

Technical documentation for data access.

Include:

- endpoint list
- response examples
- field descriptions

---

# Resources

Reference information.

---

## Safety Guides

Static pages.

Examples:

- earthquake safety
- tsunami response
- volcanic ash safety

---

## Glossary

Technical terminology.

Examples:

- magnitude
- hypocenter
- epicenter
- tectonic plate
- swarm
- ash advisory
- Kp index

---

## Bulletins

Important rule:

This section must aggregate **only reliable RSS feeds**.

Examples:

- USGS earthquake feeds
- NOAA tsunami alerts
- Smithsonian volcano updates
- INGV updates
- SWPC space weather alerts
- aviation ash alerts

This section must **not become a generic news page**.

No commentary or editorial content.

Only trusted institutional sources.

---

# About

Transparency pages.

---

## Sources

List of data providers:

- USGS
- EMSC
- INGV
- NOAA
- PTWC
- Smithsonian GVP
- SWPC

---

## Methodology

Explain:

- data collection
- feed normalization
- update frequency
- map logic
- clustering logic

---

# Development Workflow

At the start of each development session Codex must:

1. Read `ROADMAP.md`
2. Evaluate what sections are implemented
3. Identify missing features
4. Generate or update `TASKS.md`

`TASKS.md` must contain:

- actionable tasks
- implementation steps
- file locations if applicable

The roadmap remains the **high-level plan**, while `TASKS.md` contains **short-term tasks**.

---

# Design Constraint

The refactor must:

- keep Quakrs visual identity
- maintain a clean navigation
- prioritize live monitoring
- avoid duplicated pages

---

# Data Trust Rule

Feeds and alerts must prioritize:

- official APIs
- institutional RSS feeds
- scientific observatories

Avoid scraping generic media sites unless explicitly approved.

---

# Long Term Goal

Quakrs should evolve into a **global monitoring hub** combining:

- realtime seismic monitoring
- volcanic observation
- tsunami alerts
- space weather monitoring
- historical analytics
- visual observation through cameras
